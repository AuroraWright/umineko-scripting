#!/usr/bin/env python3
"""Extract Japanese Tips and character descriptions from the Saku SNR table."""

from __future__ import annotations

import argparse
import json
import re
import struct
from collections import defaultdict
from pathlib import Path


ROOT = Path(__file__).resolve().parent.parent
DEFAULT_SNR = ROOT / "snr" / "source" / "saku.snr"
DEFAULT_MENU = ROOT / "script" / "en" / "menu.txt"
DEFAULT_FLOW = ROOT / "script" / "umi_ftr.txt"
DEFAULT_OUTPUT = ROOT / "update-manager" / "jp-menu-text.json"


def u32(data: bytes, offset: int) -> int:
    return struct.unpack_from("<I", data, offset)[0]


def read_string(data: bytes, offset: int) -> tuple[str, int]:
    length = struct.unpack_from("<H", data, offset)[0]
    start = offset + 2
    raw = data[start : start + length]
    if not raw or raw[-1] != 0:
        raise ValueError(f"unterminated SNR string at 0x{offset:x}")
    return raw[:-1].decode("cp932"), start + length


def parse_characters(data: bytes) -> list[dict]:
    offset = u32(data, 0x4C)
    byte_size = u32(data, offset)
    count = u32(data, offset + 4)
    offset += 8
    records: list[dict] = []

    for _ in range(count):
        episode = data[offset] + 1
        offset += 1
        state = None
        while True:
            tag = data[offset]
            offset += 1
            if tag == 0:
                break
            if tag == 1:
                state = {
                    "episode": episode,
                    "state": data[offset],
                    "assets": [],
                    "name": None,
                    "description": None,
                }
                records.append(state)
                offset += 1
            elif tag == 2:
                if state is None:
                    raise ValueError("sprite variant encountered before character state")
                offset += 2  # variant index and portrait display mode
                _, offset = read_string(data, offset)  # grid portrait texture
                asset, offset = read_string(data, offset)
                state["assets"].append(asset.lower())
            elif tag == 3:
                if state is None:
                    raise ValueError("character text encountered before character state")
                state["name"], offset = read_string(data, offset)
                state["description"], offset = read_string(data, offset)
            else:
                raise ValueError(f"unknown character-table tag 0x{tag:02x} at 0x{offset - 1:x}")

    expected_end = u32(data, 0x4C) + 4 + byte_size
    if offset != expected_end:
        raise ValueError(f"character table ended at 0x{offset:x}, expected 0x{expected_end:x}")
    if any(record["name"] is None or record["description"] is None for record in records):
        raise ValueError("character state without text")
    return records


def parse_tips(data: bytes) -> list[dict]:
    offset = u32(data, 0x54)
    byte_size = u32(data, offset)
    count = u32(data, offset + 4)
    offset += 8
    records = []
    episode_counts: defaultdict[int, int] = defaultdict(int)

    for _ in range(count):
        episode = data[offset] + 1
        title_index = struct.unpack_from("<H", data, offset + 1)[0]
        offset += 3
        title, offset = read_string(data, offset)
        content, offset = read_string(data, offset)
        episode_counts[episode] += 1
        records.append(
            {
                "episode": episode,
                "number": episode_counts[episode],
                "title_index": title_index,
                "title": title,
                "content": content,
            }
        )

    expected_end = u32(data, 0x54) + 4 + byte_size
    if offset != expected_end:
        raise ValueError(f"Tips table ended at 0x{offset:x}, expected 0x{expected_end:x}")
    return records


def ons_text(text: str) -> str:
    def ruby(match: re.Match[str]) -> str:
        reading, base = match.groups()
        return f"{{ruby:{reading}:{base}}}" if reading else base

    text = re.sub(r"@c900\.(.*?)@c\.", r"{c:FF0000:\1}", text, flags=re.DOTALL)
    text = re.sub(r"@z85\.(.*?)@z100\.", r"{e/085/\1}", text, flags=re.DOTALL)
    text = re.sub(r"@b(.*?)\.@<(.*?)@>", ruby, text, flags=re.DOTALL)
    text = text.replace("@r", "{n}")
    controls = sorted(set(re.findall(r"@.", text)))
    if controls:
        raise ValueError(f"unhandled SNR text controls: {controls}")
    return text


def parse_menu_aliases(menu: str) -> dict[str, str]:
    return {
        match.group(1): match.group(2)
        for match in re.finditer(r'^stralias (chars_[A-Za-z0-9_]+),"(.*)"', menu, re.MULTILINE)
    }


def image_alias_candidates(flow: str) -> dict[str, list[str]]:
    result: dict[str, list[str]] = {}
    for block in re.split(r"(?m)^(?=\*)", flow):
        images: defaultdict[int, list[str]] = defaultdict(list)
        aliases: dict[int, str] = {}
        for line in block.splitlines():
            image = re.search(
                r'condition\]\s*=\s*(\d+).*graphics\\big_chars\\([^"\\]+)\.png',
                line,
                re.IGNORECASE,
            )
            if image:
                images[int(image.group(1))].append(image.group(2).lower())
            alias = re.search(
                r"condition\]\s*=\s*(\d+)\s+mov \$r_txt_path,(chars_[A-Za-z0-9_]+)",
                line,
            )
            if alias:
                aliases[int(alias.group(1))] = alias.group(2)
        for condition, alias in aliases.items():
            result[alias] = images[condition]
    return result


def map_character_aliases(records: list[dict], menu: str, flow: str) -> dict[str, dict]:
    menu_aliases = parse_menu_aliases(menu)
    wanted = {
        alias
        for alias in menu_aliases
        if alias != "chars_caption" and not alias.startswith("chars_bernquiz_")
    }
    image_candidates = image_alias_candidates(flow)
    all_assets = {asset for record in records for asset in record["assets"]}
    mapped: dict[str, dict] = {}

    def assets_in_image(image: str) -> set[str]:
        return {asset for asset in all_assets if image == asset or image.endswith("_" + asset)}

    for alias, images in image_candidates.items():
        episode_match = re.match(r"chars_([1-8])_", alias)
        if not episode_match:
            continue
        episode = int(episode_match.group(1))
        assets = set().union(*(assets_in_image(image) for image in images))
        candidates = [
            record
            for record in records
            if record["episode"] == episode and assets.intersection(record["assets"])
        ]
        if len(candidates) == 1:
            mapped[alias] = candidates[0]

    # The English menu deliberately repeats many unchanged descriptions while
    # using a later episode's portrait. Reuse an already identified Japanese
    # record whenever the full English alias is identical.
    equal_english: defaultdict[str, list[str]] = defaultdict(list)
    for alias, value in menu_aliases.items():
        equal_english[value].append(alias)
    changed = True
    while changed:
        changed = False
        for group in equal_english.values():
            source = next((alias for alias in group if alias in mapped), None)
            if source is None:
                continue
            for alias in group:
                if alias in wanted and alias not in mapped:
                    mapped[alias] = mapped[source]
                    changed = True

    record_by_key = {
        (record["episode"], asset, record["state"]): record
        for record in records
        for asset in record["assets"]
    }
    overrides = {
        "chars_4_geo_1": (4, "ep4_1_geo", 1),
        "chars_4_2_bea_1": (4, "ep4_2_bea", 1),
        "chars_4_2_bea_2": (4, "ep4_2_bea", 2),
        "chars_4_2_s55_1": (4, "ep4_2_s55", 1),
        "chars_5_2_dla_1": (5, "ep5_2_dra", 1),
        "chars_6_2_dla_1": (6, "ep5_2_dra", 1),
        "chars_6_2_eri_2": (6, "ep6_2_eri_1", 1),
        "chars_6_2_fur_2": (6, "ep6_2_ful_1", 1),
        "chars_6_2_zep_2": (6, "ep6_2_zep_1", 1),
        "chars_6_3_enj_1": (6, "ep6_3_enj_1", 1),
        "chars_6_3_enj_2": (6, "ep6_3_enj_1", 1),
        "chars_6_3_fea_1": (6, "ep6_3_fea_1", 1),
        "chars_7_2_cur_1": (7, "ep7_2_cur_1", 1),
        "chars_7_2_wil_1": (7, "ep7_2_wil_1", 1),
        "chars_8_2_dla_1": (8, "ep5_2_dra", 1),
    }
    for alias, key in overrides.items():
        mapped[alias] = record_by_key[key]

    missing = sorted(wanted - mapped.keys())
    if missing:
        raise ValueError("unmapped character aliases: " + ", ".join(missing))
    return {alias: mapped[alias] for alias in sorted(wanted)}


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--snr", type=Path, default=DEFAULT_SNR)
    parser.add_argument("--menu", type=Path, default=DEFAULT_MENU)
    parser.add_argument("--flow", type=Path, default=DEFAULT_FLOW)
    parser.add_argument("--output", type=Path, default=DEFAULT_OUTPUT)
    args = parser.parse_args()

    data = args.snr.read_bytes()
    menu = args.menu.read_text(encoding="utf-8")
    flow = args.flow.read_text(encoding="utf-8")
    characters = map_character_aliases(parse_characters(data), menu, flow)
    tips = parse_tips(data)

    aliases: dict[str, str] = {}
    tip_titles: dict[str, str] = {}
    for tip in tips:
        alias = f"tips_{tip['episode']}_{tip['number']}_1"
        title = ons_text(tip["title"])
        content = ons_text(tip["content"])
        # Ruby increases the list row height. Keep the PS3 reading in the
        # description instead, including unannotated parts of the name.
        ruby = r"\{ruby:([^{}:]+):([^{}]+)\}"
        if re.search(ruby, title):
            reading = re.sub(ruby, r"\1", title)
            title = re.sub(ruby, r"\2", title)
            content = "(" + reading + "){n}{n}" + content
        aliases[alias] = ":s;#FFFFFF`{p:5:" + content + "}"
        title_alias = f"r_tips_{tip['episode']}_{tip['number']}"
        tip_titles[title_alias] = title
    for alias, record in characters.items():
        aliases[alias] = (
            ":s;#FFFFFF`{p:3:"
            + ons_text(record["name"])
            + "{n}{n}"
            + ons_text(record["description"])
            + "}"
        )

    payload = {
        "source": str(args.snr.relative_to(ROOT)),
        "tips_count": len(tips),
        "tip_title_alias_count": len(tip_titles),
        "character_alias_count": len(characters),
        "tip_titles": tip_titles,
        "aliases": dict(sorted(aliases.items())),
    }
    args.output.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(
        f"Extracted {len(tips)} Tips and {len(characters)} character aliases "
        f"from {args.snr.relative_to(ROOT)}"
    )


if __name__ == "__main__":
    main()

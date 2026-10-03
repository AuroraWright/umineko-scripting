#!/usr/bin/env python3
"""Build graphics_jp/locale_jp from the Japanese PS3 bitmap extraction."""

from __future__ import annotations

import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont, ImageOps


ROOT = Path(__file__).resolve().parent.parent
SOURCE = ROOT / "jp_graphics_ps3"
RONDO_EXTRACT = ROOT / "data_extract_ps3"
RUSSIAN = ROOT / "graphics" / "locale"
ENGLISH = ROOT / "graphics" / "locale_en"
OUTPUT = ROOT / "graphics_jp" / "locale_jp"
TXA_EXTRACTOR = ROOT / "umi_ps3_extract"
CHIRU_EXTRACT = ROOT / "data_extract_chiru_ps3"


ENDING_PARTS = {
    "end_1a": (6120, 6120, 6120),
    "end_2a": (6120, 6120, 6120),
    "end_2c": (6372, 6371),
    "end_3a": (8192, 8192, 8192, 2311),
}

COPIED_PROJECT_ART = {
    "project_logo.png": ENGLISH / "project_logo.png",
}

OMITTED_REFERENCE_ART = {
    "circle_logo_ga.png",
    "circle_logo_ga_2.png",
    "murderer/murderer_thumb_all.png",
}


def save_png(image: Image.Image, destination: Path) -> None:
    destination.parent.mkdir(parents=True, exist_ok=True)
    if image.mode == "RGBA" and image.getchannel("A").getextrema() == (255, 255):
        image = image.convert("RGB")
    image.save(destination, format="PNG", compress_level=6)


def open_bitmap(path: Path) -> Image.Image:
    image = Image.open(path)
    image.load()
    return image


def copy_project_art(relative: str, source: Path, destination: Path) -> None:
    destination.parent.mkdir(parents=True, exist_ok=True)
    if relative != "project_logo.png":
        shutil.copy2(source, destination)
        return

    image = open_bitmap(source).convert("RGB")
    background = image.getpixel((0, 0))
    ImageDraw.Draw(image).rectangle(
        (1220, 1015, image.width, image.height), fill=background
    )
    save_png(image, destination)


def reference_files() -> dict[Path, Path]:
    """Return the union of both established locale layouts."""
    references: dict[Path, Path] = {}
    for directory in (ENGLISH, RUSSIAN):
        for path in sorted(directory.rglob("*.png")):
            relative = path.relative_to(directory)
            if relative.as_posix() not in OMITTED_REFERENCE_ART:
                references.setdefault(relative, path)
    return references


def source_for(stem: str) -> Path | None:
    # Root assets are the 1920x1080/long-ending variants used by this port.
    for candidate in (SOURCE / f"{stem}.bmp", SOURCE / "t" / f"{stem}.bmp"):
        if candidate.exists():
            return candidate
    return None


def convert_direct(source: Path, reference: Path, destination: Path) -> None:
    image = open_bitmap(source)
    expected_size = Image.open(reference).size
    if image.size != expected_size:
        image = image.resize(expected_size, Image.Resampling.LANCZOS)
    save_png(image, destination)


def generate_text006() -> None:
    """Add the port unlock notice in the style of PS3 text004/text005."""
    canvas = open_bitmap(SOURCE / "chess1.bmp").convert("RGBA")
    if canvas.size != (1920, 1080):
        canvas = canvas.resize((1920, 1080), Image.Resampling.LANCZOS)
    scale = 4
    font = ImageFont.truetype(str(ROOT / "FOT-Seurat Pro M.otf"), 30 * scale)
    # The unlock wording is a port adaptation; the new-elements wording is
    # transcribed from official text004. Match its roughly 51px line spacing.
    lines = (
        (700, "■新たなゲーム盤の追加"),
        (751, "次のエピソードをご用意いたしました。"),
        (853, "■新要素の追加"),
        (904, "新要素については、タイトル画面よりご"),
        (955, "確認ください。"),
    )
    mask = Image.new("L", (canvas.width * scale, canvas.height * scale))
    draw = ImageDraw.Draw(mask)
    for y, text in lines:
        if 1168 + draw.textlength(text, font=font) / scale > 1810:
            raise ValueError(f"text006 notice exceeds its text area: {text}")
        draw.text((1168 * scale, y * scale), text, font=font, fill=255, anchor="lt")
    mask = mask.resize(canvas.size, Image.Resampling.LANCZOS)
    # Broad, softly feathered backing matches the halo in text004/text005.
    shadow = Image.new("RGBA", canvas.size, "black")
    shadow.putalpha(mask.filter(ImageFilter.MaxFilter(41)).filter(ImageFilter.GaussianBlur(18)))
    canvas.alpha_composite(shadow)
    lettering = Image.new("RGBA", canvas.size, "white")
    lettering.putalpha(mask)
    canvas.alpha_composite(lettering)
    save_png(canvas, OUTPUT / "text006.png")


def generate_circle_logo() -> None:
    """Convert and invert the official PS3 07th Expansion logo."""
    source = RONDO_EXTRACT / "logos" / "07th.pic"
    if not source.is_file():
        raise RuntimeError(f"missing PS3 07th Expansion logo: {source}")
    if not TXA_EXTRACTOR.is_file():
        raise RuntimeError(f"missing PS3 picture extractor: {TXA_EXTRACTOR}")
    with tempfile.TemporaryDirectory(prefix="umineko-07th-logo-") as temporary:
        bitmap = Path(temporary) / "07th.bmp"
        subprocess.run([str(TXA_EXTRACTOR), str(source), str(bitmap)], check=True)
        logo = open_bitmap(bitmap).convert("RGB")
        save_png(ImageOps.invert(logo), OUTPUT / "circle_logo.png")


def crop_endings() -> None:
    for stem, heights in ENDING_PARTS.items():
        image = open_bitmap(SOURCE / f"{stem}.bmp")
        offset = 0
        for index, height in enumerate(heights, 1):
            part = image.crop((0, offset, image.width, offset + height))
            save_png(part, OUTPUT / f"{stem}_{index}.png")
            offset += height
        if offset != image.height:
            raise RuntimeError(f"{stem}: cut {offset} rows from {image.height}")

    # The established hotspots allow 12,930 rows. The PS3 image has 340
    # completely blank rows at the top which can be removed without losing art.
    image = open_bitmap(SOURCE / "end_3b.bmp").crop((0, 340, 1920, 13270))
    save_png(image.crop((0, 0, 1920, 6635)), OUTPUT / "end_3b_1.png")
    save_png(image.crop((0, 6635, 1920, 12930)), OUTPUT / "end_3b_2.png")

    # The low-memory path doubles this image's Y scale at runtime. This is the
    # exact transform used by the existing localized asset.
    image = open_bitmap(SOURCE / "end_8a.bmp")
    image = image.resize((1920, 14400), Image.Resampling.LANCZOS)
    save_png(image.crop((0, 540, 1920, 14400)), OUTPUT / "end_8a_small.png")

    # These aliases are retained by the common header even though current
    # scenarios use the segmented variants.
    for stem in ("end_2a", "end_2b", "end_2c", "end_3a"):
        save_png(open_bitmap(SOURCE / f"{stem}.bmp"), OUTPUT / f"{stem}.png")
    revised = open_bitmap(SOURCE / "reend_1c.bmp")
    save_png(revised.crop((0, 0, 1920, 8192)), OUTPUT / "end_1c_1.png")
    save_png(revised.crop((0, 8192, 1920, revised.height)), OUTPUT / "end_1c_2.png")


def make_murderer_thumbnails() -> None:
    output_dir = OUTPUT / "murderer"
    output_dir.mkdir(parents=True, exist_ok=True)
    if not TXA_EXTRACTOR.is_file():
        raise RuntimeError(f"missing TXA extractor: {TXA_EXTRACTOR}")
    archive = CHIRU_EXTRACT / "murderer.txa"
    with tempfile.TemporaryDirectory(prefix="umineko-murderer-") as temporary:
        prefix = Path(temporary) / "murderer"
        subprocess.run([str(TXA_EXTRACTOR), str(archive), str(prefix)], check=True)
        for suffix in ("1", "2", "4", "56", "7", "8"):
            source = prefix.with_name(f"murderer_thumb_{suffix}.bmp")
            save_png(open_bitmap(source), output_dir / f"murderer_thumb_{suffix}.png")


def main() -> int:
    for required in (SOURCE, RUSSIAN, ENGLISH):
        if not required.is_dir():
            print(f"missing required directory: {required}", file=sys.stderr)
            return 1

    if OUTPUT.exists():
        shutil.rmtree(OUTPUT)
    OUTPUT.mkdir(parents=True)

    references = reference_files()
    special = set(COPIED_PROJECT_ART)
    special.update({"text006.png", "circle_logo.png", "end_8a_small.png"})
    special.update(
        f"{stem}_{index}.png"
        for stem, heights in ENDING_PARTS.items()
        for index in range(1, len(heights) + 1)
    )
    special.update({"end_3b_1.png", "end_3b_2.png"})
    special.update(
        f"murderer/murderer_thumb_{suffix}.png"
        for suffix in ("1", "2", "4", "56", "7", "8")
    )

    for relative, reference in references.items():
        key = relative.as_posix()
        if key in special:
            continue
        stem = relative.stem
        # The port uses the revised Episode 1 credits under both aliases.
        if stem == "end_1c":
            source = SOURCE / "reend_1c.bmp"
        else:
            source = source_for(stem)
        if source is None:
            raise RuntimeError(f"no Japanese source for {relative}")
        convert_direct(source, reference, OUTPUT / relative)

    generate_text006()
    generate_circle_logo()
    for relative, source in COPIED_PROJECT_ART.items():
        destination = OUTPUT / relative
        copy_project_art(relative, source, destination)

    crop_endings()
    make_murderer_thumbnails()

    generated = len(list(OUTPUT.rglob("*.png")))
    expected = len(references) + 6
    if generated != expected:
        raise RuntimeError(f"generated {generated} PNGs; expected {expected}")
    print(f"Generated {generated} Japanese locale graphics in {OUTPUT}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

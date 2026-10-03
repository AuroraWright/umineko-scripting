#!/usr/bin/env python3
"""Build active Japanese menu graphics from the PS3 assets."""

from __future__ import annotations

import shutil
import subprocess
import tempfile
import html
import re
from pathlib import Path

from PIL import Image, ImageChops, ImageDraw, ImageFilter, ImageFont
from ps3_font import PS3Font


ROOT = Path(__file__).resolve().parent.parent
EXTRACTOR = ROOT / "umi_ps3_extract"
QUESTION = ROOT / "data_extract_ps3"
ANSWER = ROOT / "data_extract_chiru_ps3"
ENGLISH = ROOT / "graphics" / "menu_en"
JAPANESE_BITMAPS = ROOT / "jp_graphics_ps3"
OUTPUT = ROOT / "graphics_jp" / "menu_jp"
SYSTEM_WND = ROOT / "graphics_jp" / "system" / "wnd"
SYSTEM_LOGO = ROOT / "graphics_jp" / "system" / "logo"
REFERENCE_SYSTEM_WND = ROOT / "graphics" / "system" / "wnd"
SNR_XML = (ROOT / "snr" / "output" / "script_rondo.xml", ROOT / "snr" / "output" / "script_chiru.xml")


def first_available_font(
    candidates: tuple[tuple[Path, int], ...], size: int, description: str
) -> ImageFont.FreeTypeFont:
    for path, index in candidates:
        if path.is_file():
            return ImageFont.truetype(path, size, index=index)
    raise RuntimeError(f"No {description} font found")


def title_panel_fonts() -> tuple[ImageFont.FreeTypeFont, ImageFont.FreeTypeFont]:
    """Fonts used by the text baked into the PS3 title description panels."""
    font = first_available_font(
        (
            (ROOT / "FOT-Greco Std B.otf", 0),
        ),
        32,
        "FOT-Greco Std B title-panel font",
    )
    return font, font


def title_panel_background() -> Image.Image:
    """Return the PS3 panel frame with its original text area cleared."""
    background = Image.open(OUTPUT / "title/title1_text_config.png").convert("RGBA")
    if background.size != (880, 500):
        raise ValueError("Unexpected PS3 title panel dimensions")
    # The panel interior is black at 179/255 alpha. Keep the soft frame intact.
    background.paste((0, 0, 0, 179), (25, 20, 850, 130))
    return background


def write_title_panel(
    path: Path,
    heading: str,
    lines: tuple[str, ...],
    *,
    color: str | tuple[int, int, int, int] = "white",
) -> None:
    heading_font, body_font = title_panel_fonts()
    canvas = title_panel_background()
    foreground = Image.new("RGBA", canvas.size)
    draw = ImageDraw.Draw(foreground)
    draw.text((29, 21), heading, font=heading_font, fill=color)
    for row, line in enumerate(lines):
        draw.text((29, 85 + row * 34), line, font=body_font, fill=color)
    canvas.alpha_composite(foreground)
    save_image(canvas, path)


def generate_combined_episode_panels() -> None:
    """Translate the two port-only combined episode description panels."""
    panels = (
        (
            OUTPUT / "title" / "chiru" / "title1_text_ep1_4.png",
            "Episodes 1-4",
            (
                "過去のゲームを振り返れば、",
                "学ぶべきことは数多くございます。",
                "",
                "新たな視点で盤面を見つめ直した時、",
                "何が見えてくるでしょうか。",
                "",
                "お相手を理解されたなら、",
                "今度はチェス盤をひっくり返してみましょう。",
            ),
        ),
        (
            OUTPUT / "title" / "title1_text_ep5_8.png",
            "Episodes 5-8",
            (
                "おはようございます。",
                "物語はいよいよ後半へ。",
                "黄金の魔女が紡ぐ物語は、すでに語り尽くされました。",
                "",
                "あとは、それらを理解するだけでございます。",
                "",
                "新たなゲームマスターならば、",
                "また違った盤面を見せて下さるかもしれません。",
            ),
        ),
    )
    for path, heading, lines in panels:
        write_title_panel(path, heading, lines)


def generate_port_title_panels() -> None:
    """Translate title descriptions that exist only in the PC port."""
    panels = (
        (
            OUTPUT / "title/title1_text_exit.png",
            "Exit",
            (
                "ゲームを終了します。",
                "",
                "またのお越しを、心よりお待ちしております。",
            ),
            "white",
        ),
        (
            OUTPUT / "title/title1_text_unlock.png",
            "Unlock",
            (
                "このエピソードのロックを解除します。",
                "",
                "エピソードを順番通りに進めなかった場合、",
                "物語がわからなくなり、興味を失うおそれがあります。",
                "",
                "この機能は、それまでのエピソードを",
                "プレイ済みの方のみご利用になることを",
                "強くお勧めします。",
            ),
            "white",
        ),
        (
            OUTPUT / "title/title1_text_warning.png",
            "Warning!",
            (
                "このエピソードはロックされています。",
                "",
                "ロックは、エピソードをクリアするたびに解除されます。",
                "まずは前のエピソードを最後までお読み下さい。",
                "",
                "ただし、以前のエピソードをすでにプレイ済みの場合は、",
                "Unlockボタンでロックを解除できます。",
            ),
            (255, 0, 0, 255),
        ),
        (
            OUTPUT / "title/title1_text_web.png",
            "Official Site",
            (
                "07th Expansionの公式サイトへようこそ。",
                "",
                "掲示板では、六軒島で過ごした思い出について",
                "語り合うことができます。",
                "",
                "他の方々の意見に耳を傾ければ、",
                "新たな発見があるかもしれません。",
                "",
                "ご自身の考えを披露し、",
                "議論に参加するのもまた一興でしょう。",
            ),
            "white",
        ),
    )
    for path, heading, lines, color in panels:
        write_title_panel(path, heading, lines, color=color)


def generate_unlock_prompt() -> None:
    """Translate the port-only episode-unlock confirmation panel."""
    source = Image.open(ENGLISH / "title_menu/unlock_kaku_bg.png").convert("RGBA")
    if source.size != (454, 278):
        raise ValueError("Unexpected episode-unlock panel dimensions")
    source.paste((0, 0, 0, 179), (20, 20, 434, 75))
    font, _ = title_panel_fonts()
    foreground = Image.new("RGBA", source.size)
    ImageDraw.Draw(foreground).text(
        (51, 27), "ロックを解除しますか？", font=font, fill="white"
    )
    source.alpha_composite(foreground)
    save_image(source, OUTPUT / "title_menu/unlock_kaku_bg.png")


def generate_system_buttons() -> None:
    """Japanese counterparts for the port's active SystemBtn text."""
    font_dir = Path.home() / "Library/Fonts"
    bold = first_available_font(((font_dir / "SHINJIMinchoW7.otf", 0),), 36 * 4,
                                "SHINJIMinchoW7 SystemBtn confirmation")
    prompt_font = first_available_font(((ROOT / "FOT-Greco Std B.otf", 0),),
                                      32 * 4, "FOT-Greco Std B SystemBtn prompt")

    def text_mask(text: str, font: ImageFont.FreeTypeFont,
                  width_scale: float = 1.0) -> Image.Image:
        box = font.getbbox(text)
        mask = Image.new("L", (box[2] - box[0], box[3] - box[1]))
        ImageDraw.Draw(mask).text((-box[0], -box[1]), text, font=font, fill=255)
        return mask.resize((round(mask.width / 4 * width_scale), round(mask.height / 4)),
                           Image.Resampling.LANCZOS)

    for name, text in (("yes", "はい"), ("no", "いいえ")):
        # :c/2 sprites have opaque normal/hover backgrounds, not alpha keys.
        canvas = Image.open(ENGLISH / f"SystemBtn/{name}.png").convert("RGBA")
        mask = text_mask(text, bold, 0.85)
        for state, color in enumerate(((55, 27, 27, 255), (255, 41, 41, 255))):
            cell = Image.new("RGBA", (376, 58), color)
            ink = Image.new("RGBA", cell.size)
            shadow = Image.new("L", cell.size)
            shadow.paste(mask, (22, 14))
            shade = Image.new("RGBA", cell.size, "black")
            shade.putalpha(shadow.filter(ImageFilter.GaussianBlur(1)))
            ink.alpha_composite(shade)
            white = Image.new("RGBA", mask.size, "white")
            white.putalpha(mask)
            ink.alpha_composite(white, (20, 12))
            cell.alpha_composite(ink)
            canvas.paste(cell, (state * 376, 0))
        save_image(canvas, OUTPUT / f"SystemBtn/{name}.png")
        # Keep the separate title-menu copy used by every other locale.
        shutil.copy2(
            OUTPUT / f"SystemBtn/{name}.png",
            OUTPUT / f"title_menu/{name}.png",
        )

    canvas = Image.open(ENGLISH / "SystemBtn/title_bg.png").convert("RGBA")
    canvas.paste((0, 0, 0, 179), (20, 20, 434, 75))
    mask = text_mask("タイトル画面に戻りますか？", prompt_font)
    white = Image.new("RGBA", mask.size, "white")
    white.putalpha(mask)
    canvas.alpha_composite(white, ((canvas.width - mask.width) // 2, 30))
    save_image(canvas, OUTPUT / "SystemBtn/title_bg.png")


def generate_trophy_caption() -> None:
    """Recreate the port caption in the style of PS3 BGM/CG captions."""
    canvas = Image.open(ENGLISH / "trophy/trophy_caption.png").convert("RGBA")
    # The pill's gradient is horizontal. Its unobstructed bottom row restores
    # the central area behind the old, vertically centered English lettering.
    gradient = canvas.crop((160, 100, 480, 101))
    # Preserve the pill's translucent shadow above/below the gradient instead
    # of introducing an opaque rectangle behind the new heading.
    for y in range(16, 106):
        canvas.paste(canvas.getpixel((100, y)), (160, y, 480, y + 1))
    canvas.paste(gradient.resize((320, 76)), (160, 26))

    def lettering(text: str, size: int, tracking: int, top: int) -> None:
        scale = 4
        font = first_available_font(
            ((ROOT / "FOT-NewCezanne Pro B.otf", 0),), size * scale,
            "FOT-NewCezanne Pro B caption",
        )
        mask = Image.new("L", (640 * scale, 128 * scale))
        draw = ImageDraw.Draw(mask)
        x = 0.0
        for char in text:
            draw.text((x, 0), char, font=font, fill=255)
            x += font.getlength(char) + tracking * scale
        mask = mask.crop(mask.getbbox())
        mask = mask.resize((round(mask.width / scale), round(mask.height / scale)),
                           Image.Resampling.LANCZOS)
        positioned = Image.new("L", canvas.size)
        positioned.paste(mask, ((640 - mask.width) // 2, top))
        outline = positioned.filter(ImageFilter.MaxFilter(9))
        shadow = Image.new("RGBA", canvas.size, "black")
        shadow.putalpha(outline.filter(ImageFilter.GaussianBlur(1)))
        canvas.alpha_composite(shadow)
        ink = Image.new("RGBA", canvas.size, "white")
        ink.putalpha(positioned)
        canvas.alpha_composite(ink)

    lettering("Trophies", 64, 0, 13)
    lettering("トロフィー", 24, 4, 84)
    save_image(canvas, OUTPUT / "trophy/trophy_caption.png")


def snr_chapters() -> dict[int, list[str]]:
    chapters: dict[int, list[str]] = {}
    current: int | None = None
    pattern = re.compile(r'<ins type="SECTION_START"[^>]*section_type="(EPISODE|CHAPTER)" section="([^"]*)"')
    for path in SNR_XML:
        for section_type, escaped_text in pattern.findall(path.read_text(encoding="utf-8")):
            text = html.unescape(escaped_text)
            if section_type == "EPISODE":
                current = None
                match = re.match(r"^Episode([1-8])\b", text)
                if match and int(match.group(1)) not in chapters:
                    current = int(match.group(1))
                    chapters[current] = []
            elif current is not None:
                chapters[current].append(text)
    expected = {1: 18, 2: 19, 3: 19, 4: 20, 5: 16, 6: 19, 7: 19, 8: 17}
    if {episode: len(items) for episode, items in chapters.items()} != expected:
        raise RuntimeError("unexpected PS3 chapter-section counts")
    return chapters


DATE_PATTERN = re.compile(
    r"^(?:(?:1986 )?\d{1,2}/\d{1,2}(?:（[^）]+）)?\s*(?:\d{1,2}:\d{2}|--:--)|1986 \d{1,2}/\d{1,2})"
)


def write_chapter_card(path: Path, font: PS3Font, lines: list[str]) -> None:
    canvas = Image.new("RGBA", (1920, 1080))
    y_positions = (770, 860, 955) if len(lines) == 3 else (810, 920)
    for text, y in zip(lines, y_positions):
        glyphs = font.render(text)
        mask = Image.new("L", (glyphs.width + 6, glyphs.height + 6))
        mask.paste(glyphs, (3, 3))
        layer = Image.new("RGBA", mask.size)
        layer.putalpha(mask.filter(ImageFilter.MaxFilter(7)))
        fill = Image.new("RGBA", mask.size, "white")
        fill.putalpha(mask)
        layer = Image.alpha_composite(layer, fill)
        x = 1865 - layer.width
        if x < 0 or y + layer.height > canvas.height:
            raise ValueError(f"Chapter text exceeds the port canvas: {path.name}: {text}")
        canvas.alpha_composite(layer, (x, y))
    canvas.convert("LA").save(path, "PNG", compress_level=6)


def generate_chapter_cards() -> None:
    font = PS3Font(QUESTION / "default.fnt")
    if (QUESTION / "default.fnt").read_bytes() != (ANSWER / "default.fnt").read_bytes():
        raise ValueError("Rondo/Chiru chapter fonts differ; select a font per episode")
    chapters = snr_chapters()
    directory = OUTPUT / "r_click_menu" / "chapters"
    for episode, sections in chapters.items():
        for chapter, section in enumerate(sections[1:], 1):
            date_match = DATE_PATTERN.match(section)
            date = date_match.group(0) if date_match else ""
            title = section[date_match.end() :].strip() if date_match else section
            lines = [f"Episode {episode}", title]
            if date:
                lines.append(date)
            write_chapter_card(directory / f"{episode}_{chapter:02}.png", font, lines)
        write_chapter_card(directory / f"{episode}_op.png", font, [f"Episode {episode}", sections[0]])
        write_chapter_card(directory / f"{episode}_tea.png", font, [f"Episode {episode}", "Tea party"])
        write_chapter_card(directory / f"{episode}_ura.png", font, [f"Episode {episode}", "????"])
    write_chapter_card(directory / "1_omake.png", font, ["おまけ1"])
    for episode in range(2, 9):
        (directory / f"{episode}_omake.png").unlink(missing_ok=True)


def generate_config_main() -> None:
    canvas = Image.new("LA", (5760, 1080), (255, 0))
    draw = ImageDraw.Draw(canvas)
    font = PS3Font(QUESTION / "default.fnt")
    if (QUESTION / "default.fnt").read_bytes() != (ANSWER / "default.fnt").read_bytes():
        raise ValueError("Rondo/Chiru config fonts differ; select one per panel")

    def text_mask(text: str, height: int) -> Image.Image:
        source = font.render(text)
        width = round(source.width * height / source.height)
        return source.resize((width, height), Image.Resampling.LANCZOS)

    panels = [
        (0, "システム設定", []),
        # Slider images use :a/2 (two horizontal states). The visible last
        # cell ends at x=1687 for circles and x=1696 for arrows, not at the
        # full sprite-sheet width. Keep the right captions beside that cell.
        # Chiru's config.txa calls this category 「音響設定」.
        (1920, "音響設定", [(1150, 234, "オフ"), (1707, 235, "最大"), (1150, 314, "オフ"), (1707, 315, "最大"), (1150, 394, "オフ"), (1707, 395, "最大")]),
        (3840, "ゲーム設定", [(1183, 239, "弱"), (1713, 240, "強"), (1183, 329, "遅"), (1713, 330, "速"), (1183, 416, "遅"), (1713, 417, "速")]),
    ]
    for offset, heading, labels in panels:
        heading_mask = text_mask(heading, 52)
        draw.line((offset + 120, 190, offset + 188, 190), fill=(255, 255), width=4)
        canvas.paste((255, 255), (offset + 210, 162), heading_mask)
        draw.line((offset + 230 + heading_mask.width, 190, offset + 1730, 190), fill=(255, 255), width=4)
        for x, y, text in labels:
            mask = text_mask(text, 42)
            canvas.paste((255, 255), (offset + x, y + 6), mask)
    canvas.save(OUTPUT / "config" / "config_main.png", "PNG", compress_level=6)


def save_png(source: Path, destination: Path, reference: Path | None = None) -> None:
    image = Image.open(source)
    image.load()
    save_image(image, destination, reference)


def save_image(image: Image.Image, destination: Path, reference: Path | None = None) -> None:
    if image.mode == "RGBA" and image.getchannel("A").getextrema() == (255, 255):
        image = image.convert("RGB")
    if reference is not None:
        expected = Image.open(reference).size
        if image.size != expected:
            canvas = Image.new("RGBA", expected, (0, 0, 0, 0))
            x = (expected[0] - image.width) // 2
            y = (expected[1] - image.height) // 2
            canvas.alpha_composite(image.convert("RGBA"), (x, y))
            image = canvas
    destination.parent.mkdir(parents=True, exist_ok=True)
    image.save(destination, "PNG", compress_level=6)


def generate_quiz_graphics(quiz_atlas: Path) -> None:
    atlas = Image.open(quiz_atlas).convert("RGBA")
    # Chiru stores the selected cell first and offsets the normal cell five
    # pixels farther right. The port expects normal,selected with identical
    # origins, so reorder the cells and align their opaque bounds at x=5.
    normal = atlas.crop((367, 389, 730, 553))
    selected_source = atlas.crop((4, 389, 362, 553))
    selected = Image.new("RGBA", (363, 164), (255, 255, 255, 0))
    selected.alpha_composite(selected_source, (5, 0))
    ask_auntie = Image.new("RGBA", (726, 164), (255, 255, 255, 0))
    ask_auntie.alpha_composite(normal, (0, 0))
    ask_auntie.alpha_composite(selected, (363, 0))
    if ask_auntie.size != (726, 164):
        raise RuntimeError("unexpected ask-auntie sprite dimensions")
    save_image(ask_auntie, OUTPUT / "quiz" / "ask_auntie_eva.png")
    for name in ("m1", "m2"):
        save_png(JAPANESE_BITMAPS / f"{name}.bmp", OUTPUT / "quiz" / f"{name}.png")


def generate_saveload_logos() -> None:
    for area_name, logo_name in (
        ("saveload_area.png", "cgmode_logo.png"),
        ("saveload_area_5.png", "cgmode_logo_chiru.png"),
    ):
        area = Image.open(ENGLISH / "save" / area_name).convert("RGBA")
        logo = Image.open(OUTPUT / "cgmode" / logo_name).convert("RGB")
        logo = logo.resize((310, 174), Image.Resampling.LANCZOS)
        # The original composite reserves x=11..320, y=13..186 for its logo.
        area.paste(logo, (11, 13))
        save_image(area, OUTPUT / "save" / area_name)


def generate_ep1_jump() -> None:
    """Use the original Japanese ending artwork for EP1's final jump cell."""
    source = Image.open(ENGLISH / "jump" / "EP1.png").convert("RGBA")
    ending = Image.open(ROOT / "graphics_jp" / "locale_jp" / "end_1a_2.png").convert("RGBA")
    if source.size != (640, 6480) or ending.size != (1920, 6120):
        raise RuntimeError("unexpected EP1 jump-sheet or ending-art dimensions")
    # Match the English thumbnail's viewport within the scrolling ending art.
    cell = ending.crop((0, 4071, 1920, 5151)).resize((640, 360), Image.Resampling.LANCZOS)
    source.paste(cell, (0, 6120))
    save_image(source, OUTPUT / "jump" / "EP1.png")


def generate_message_windows(question_temp: Path, answer_temp: Path) -> None:
    for archive_root, temporary, destination, reference in (
        (QUESTION, question_temp, "msgwnd_jp.png", "msgwnd_en.png"),
        (ANSWER, answer_temp, "msgwnd_ep5_jp.png", "msgwnd_ep5_en.png"),
    ):
        assets = extract(archive_root / "msgwnd.txa", temporary)
        save_png(
            assets["msgwnd"],
            SYSTEM_WND / destination,
            REFERENCE_SYSTEM_WND / reference,
        )


def generate_cinema_logos() -> None:
    # The PS3's cinema_logo2 is another Rondo logo; cinema_logo3 is Chiru.
    for source, destination in (
        ("cinema_logo.bmp", "cinema_logo_jp.png"),
        ("cinema_logo3.bmp", "cinema_logo2_jp.png"),
    ):
        logo = Image.open(JAPANESE_BITMAPS / source).convert("RGBA")
        # Retain the port's 960x280 canvas and hotspot without clipping or
        # stretching Chiru's taller 960x320 artwork.
        logo.thumbnail((960, 280), Image.Resampling.LANCZOS)
        canvas = Image.new("RGBA", (960, 280))
        canvas.alpha_composite(logo, ((960 - logo.width) // 2, (280 - logo.height) // 2))
        save_image(canvas, SYSTEM_LOGO / destination)


def quiz2_highlight(base: Image.Image) -> Image.Image:
    """Match the English hover pair's narrow outline and brighter silver fill."""
    base = base.convert("RGBA")
    highlighted = base.copy()

    def round_expand(mask: Image.Image, radius: int) -> Image.Image:
        expanded = mask.copy()
        for dy in range(-radius, radius + 1):
            for dx in range(-radius, radius + 1):
                if dx * dx + dy * dy <= radius * radius:
                    shifted = Image.new("L", mask.size)
                    shifted.paste(mask, (dx, dy))
                    expanded = ImageChops.lighter(expanded, shifted)
        return expanded

    # Padding preserves the tops of the Japanese glyphs above the runtime
    # button rectangles. Only the neutral lettering/arrows receive the effect.
    for box in ((285, 816, 887, 1005), (1034, 816, 1638, 1005)):
        button = base.crop(box)
        pixels = button.tobytes()
        width, height = button.size
        coverage = bytearray(width * height)
        for y in range(16, 177):
            row = pixels[y * width * 4 : (y + 1) * width * 4]
            green = sorted(row[1::4])
            # Estimate the silver gradient independently for every scanline.
            # A fixed cutoff also selects the existing bloom and loses dark
            # parts of the glyphs, producing broken/boxy contours.
            position = (width - 1) * 0.98
            index = int(position)
            level = max(55, green[index] + (green[index + 1] - green[index]) * (position - index))
            for x in range(width):
                red, value, blue, alpha = row[x * 4 : x * 4 + 4]
                if alpha and red - value < 25:
                    amount = max(0, min(1, (value - level * 0.5) / (level * 0.2)))
                    coverage[y * width + x] = int(amount * 255)
        mask = Image.frombytes("L", button.size, bytes(coverage))
        mask = mask.filter(ImageFilter.GaussianBlur(1))
        mask = mask.point(lambda value: round(max(0, min(255, (value - 63.75) * 2))))
        ring = ImageChops.subtract(round_expand(mask, 5), round_expand(mask, 2))
        # The reference has a three-pixel pale outline and a slight white lift
        # inside the letters, not an additional broad Gaussian glow.
        opacity = ImageChops.add(
            ring.point(lambda value: round(value * 0.18)),
            mask.point(lambda value: round(value * 0.16)),
        )
        overlay = Image.new("RGBA", button.size, (255, 255, 255, 0))
        overlay.putalpha(opacity)
        button = Image.alpha_composite(button, overlay)
        button.putalpha(base.crop(box).getchannel("A"))
        highlighted.paste(button, box)
    return highlighted


def generate_quiz2_highlight() -> None:
    """Recreate the port's hover sprite, absent from the PS3 last.txa archive."""
    base = Image.open(OUTPUT / "quiz2" / "quiz2_front.png")
    save_image(quiz2_highlight(base), OUTPUT / "quiz2" / "quiz2_front_2.png")


def verify_no_unused_graphics() -> None:
    expected = set()
    references = set()
    pattern = re.compile(r'graphics[\\\\/]menu_jp[\\\\/]([^"`;]+?\.png)', re.IGNORECASE)
    for script in (ROOT / "script" / "jp").glob("*.txt"):
        references.update(match.replace("\\", "/") for match in pattern.findall(script.read_text()))
    # The shared footer is localized from menu_en to menu_jp during assembly.
    references.update(("config/config_left.png", "config/config_right.png"))
    # quiz_letter is a runtime filename prefix followed by 1.png through 6.png.
    references.update(f"quiz/quizbtn{number}.png" for number in range(1, 7))
    files = {str(path.relative_to(OUTPUT)) for path in OUTPUT.rglob("*.png")}
    unused = files - references
    if unused != expected:
        raise RuntimeError(f"unexpected unused Japanese menu graphics: {sorted(unused)}")

    # copytree can preserve empty source directories. Remove those artifacts
    # from the active Japanese menu tree.
    directories = sorted(
        (path for path in OUTPUT.rglob("*") if path.is_dir()),
        key=lambda path: len(path.parts),
        reverse=True,
    )
    for directory in directories:
        if not any(directory.iterdir()):
            directory.rmdir()


def extract(archive: Path, directory: Path) -> dict[str, Path]:
    directory.mkdir(parents=True, exist_ok=True)
    prefix = directory / archive.stem
    subprocess.run([str(EXTRACTOR), str(archive), str(prefix)], check=True)
    result = {}
    for path in directory.glob(f"{archive.stem}_*.bmp"):
        result[path.stem.removeprefix(archive.stem + "_")] = path
    return result


def install(
    assets: dict[str, Path], source_name: str, destination: str, *, reference: bool = True
) -> None:
    target = OUTPUT / destination
    save_png(assets[source_name], target, ENGLISH / destination if reference else None)


def main() -> None:
    for required in (EXTRACTOR, QUESTION, ANSWER, ENGLISH, JAPANESE_BITMAPS):
        if not required.exists():
            raise RuntimeError(f"missing required input: {required}")

    if OUTPUT.exists():
        shutil.rmtree(OUTPUT)
    shutil.copytree(ENGLISH, OUTPUT, ignore=shutil.ignore_patterns(".DS_Store"))
    # Bundle the transparent stub with the Japanese menu assets.
    shutil.copy2(ROOT / "graphics/system/empty.png", OUTPUT / "empty.png")
    # These hidden UI aliases load the transparent stub instead.
    for relative in (
        "SystemBtn/grimoire_btn.png",
        "SystemBtn/on.png",
        "SystemBtn/on_1.png",
        "SystemBtn/off.png",
        "SystemBtn/off_1.png",
        "notes/notes_caption.png",
    ):
        (OUTPUT / relative).unlink()

    with tempfile.TemporaryDirectory(prefix="umineko-menu-jp-") as temporary:
        temp = Path(temporary)
        question_temp = temp / "question"
        answer_temp = temp / "answer"
        q_title = extract(QUESTION / "title1.txa", question_temp)
        a_title = extract(ANSWER / "title1.txa", answer_temp)
        generate_message_windows(question_temp, answer_temp)

        for name in (
            "start",
            "ep1",
            "ep2",
            "ep3",
            "ep4",
            "load",
            "bgm",
            "cg",
            "chars",
            "config",
            "tea",
            "tips",
            "ura",
        ):
            install(q_title, f"text_{name}", f"title/title1_text_{name}.png")
        install(q_title, "logo", "title/title1_logo.png")

        for name in ("ep5", "ep6", "ep7", "ep8"):
            install(a_title, f"text_{name}", f"title/chiru/title1_text_{name}.png")
        install(a_title, "logo", "title/chiru/title1_logo.png")

        archive_mappings = {
            "bgmmode.txa": [("caption", "bgmmode/bgmmode_caption.png")],
            "cgmode.txa": [
                ("caption", "cgmode/cgmode_caption.png"),
                ("logo", "cgmode/cgmode_logo_chiru.png"),
                ("logox", "cgmode/cgmode_logox.png"),
            ],
            "chars.txa": [("caption", "chars/chars_caption.png")],
            "config.txa": [("caption", "config/config_caption.png")],
            "logview.txa": [("caption", "logview/logview_caption.png")],
            "saveload.txa": [("caption", "save/saveload_caption.png")],
            "tips.txa": [("caption", "tips/tips_caption.png")],
        }
        for archive_name, mappings in archive_mappings.items():
            assets = extract(ANSWER / archive_name, answer_temp)
            for source_name, destination in mappings:
                install(assets, source_name, destination)

        q_cgmode = extract(QUESTION / "cgmode.txa", question_temp)
        install(q_cgmode, "logo", "cgmode/cgmode_logo.png")

        quiz = extract(ANSWER / "quiz.txa", answer_temp)
        generate_quiz_graphics(quiz["quiz"])

        last = extract(ANSWER / "last.txa", answer_temp)
        for source_name, destination in (
            ("back", "quiz2/quiz2_back.png"),
            ("front", "quiz2/quiz2_front.png"),
        ):
            install(last, source_name, destination)
        generate_quiz2_highlight()

    generate_chapter_cards()
    generate_config_main()
    generate_combined_episode_panels()
    generate_port_title_panels()
    generate_unlock_prompt()
    generate_system_buttons()
    generate_trophy_caption()
    generate_saveload_logos()
    generate_ep1_jump()
    generate_cinema_logos()
    verify_no_unused_graphics()

    print(f"Generated {len(list(OUTPUT.rglob('*.png')))} active Japanese menu graphics")


if __name__ == "__main__":
    main()

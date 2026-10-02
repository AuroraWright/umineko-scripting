#!/usr/bin/env python3
"""Generate cache screenshots from the complete Japanese PS3 memory screens."""

from pathlib import Path


ROOT = Path(__file__).resolve().parent.parent
OUTPUT = ROOT / "script" / "jp" / "caches.txt"

GOA_MEMORIES = range(1, 5)
KAKERA_MEMORIES = (6, 7, 9, 1, 2, 3, 4, 5, 8, 10)


def screenshot(alias: str, destination: str) -> list[str]:
    return [
        f"\tlsp s0_3,{alias},0,0",
        "\tprint 1",
        "\tgetscreenshot 1920,1080",
        f'\tsavescreenshot "{destination}"',
        "\t_csp s0_3",
    ]


def main() -> None:
    lines = [
        '\tlsp 10,":s;#FFFFFF`{p:8:{a:c:しばらくお待ちください。{n}データをキャッシュしています。}}",150,450',
        "\tprint 1",
        "\tscreenflip 0",
        "\t_csp 10",
    ]
    for number in GOA_MEMORIES:
        lines.extend(screenshot(f"goa_memory{number}_src", f"caches\\goa_memory{number}.png"))

    lines.append("\tif %CHIRU_MODE = 0 goto *cache_skip_chiru")
    for number in KAKERA_MEMORIES:
        lines.extend(
            screenshot(
                f"kakera_memory{number}_src",
                f"caches\\kakera_memory{number}.png",
            )
        )

    lines.extend(
        [
            "\t*cache_skip_chiru",
            "\t_csp s0_3",
            "\tprint 1",
            "\tscreenflip 1",
            "\tmov $font_preference,$Free1",
            "",
        ]
    )
    OUTPUT.write_text("\n".join(lines))
    print(f"Generated Japanese cache routine in {OUTPUT}")


if __name__ == "__main__":
    main()

"""Render the original Umineko PS3 FNT3 bitmap glyphs (no system fonts)."""

from functools import lru_cache
from pathlib import Path
import struct

from PIL import Image, ImageChops


class PS3Font:
    def __init__(self, path: Path):
        self.data = path.read_bytes()
        magic, size, version, reserved = struct.unpack_from('<4sIII', self.data)
        if (magic, size, version, reserved) != (b'FNT3', len(self.data), 1, 0):
            raise ValueError(f'Unexpected PS3 font header: {path}')
        first = struct.unpack_from('<I', self.data, 16)[0]
        count = (first - 16) // 4
        if count != 8180:
            raise ValueError(f'Unexpected PS3 font table size: {count}')
        self.offsets = struct.unpack_from(f'<{count}I', self.data, 16)

    @staticmethod
    def index(character: str) -> int:
        code = character.encode('cp932')
        if len(code) == 1 and 0x20 <= code[0] <= 0x7f:
            return code[0] - 0x20
        if len(code) == 2:
            hi, lo = code
            row = hi - 0x81 if 0x81 <= hi <= 0x9f else hi - 0xe0 + 31
            if 0 <= row < 43 and 0x40 <= lo <= 0xfc and lo != 0x7f:
                return 96 + row * 188 + lo - 0x40 - (lo > 0x7f)
        raise ValueError(f'Character not supported by PS3 font: {character!r}')

    @lru_cache(maxsize=None)
    def glyph(self, index: int) -> tuple[int, int, int, Image.Image]:
        offset = self.offsets[index]
        x, y, width, height, advance, unused, size = struct.unpack_from(
            '<bbBBBBH', self.data, offset)
        if unused != 0:
            raise ValueError(f'Unexpected glyph flags at {index}')
        expected = ((width + 1) // 2) * height
        if size:
            packed = self.data[offset + 8:offset + 8 + size]
            pixels = bytearray()
            pos = 0
            while pos < len(packed):
                flags = packed[pos]
                pos += 1
                for bit in range(8):
                    if pos == len(packed):
                        break
                    value = packed[pos]
                    pos += 1
                    if flags & (1 << bit):
                        # FNT3: low six bits are length; high two bits extend
                        # the following byte's backwards distance.
                        distance = ((value & 0xc0) << 2 | packed[pos]) + 1
                        pos += 1
                        if distance > len(pixels):
                            raise ValueError(f'Invalid glyph backreference: {index}')
                        for _ in range((value & 0x3f) + 3):
                            pixels.append(pixels[-distance])
                    else:
                        pixels.append(value)
                    if len(pixels) > expected:
                        raise ValueError(f'Glyph overflow: {index}')
        else:
            pixels = self.data[offset + 8:offset + 8 + expected]
        if len(pixels) != expected:
            raise ValueError(f'Wrong glyph size: {index}')
        alpha = bytes(
            ((pixels[row * ((width + 1) // 2) + col // 2]
              >> (4 if col % 2 == 0 else 0)) & 15) * 17
            for row in range(height) for col in range(width)
        )
        return x, y, advance, Image.frombytes('L', (width, height), alpha)

    def render(self, text: str) -> Image.Image:
        glyphs = [self.glyph(self.index(c)) for c in text]
        pen = 0
        left = top = 0
        right = bottom = 1
        for x, y, advance, mask in glyphs:
            left, top = min(left, pen + x), min(top, y)
            right, bottom = max(right, pen + x + mask.width), max(bottom, y + mask.height)
            pen += advance
        canvas = Image.new('L', (max(right, pen) - left, bottom - top))
        pen = -left
        for x, y, advance, mask in glyphs:
            box = (pen + x, y - top, pen + x + mask.width, y - top + mask.height)
            canvas.paste(ImageChops.lighter(canvas.crop(box), mask), box)
            pen += advance
        return canvas

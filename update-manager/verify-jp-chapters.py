#!/usr/bin/env python3
"""Check PS3 font decoding and that chapter PNGs match their source renderer."""

import hashlib
import importlib.util
from pathlib import Path
import tempfile

from PIL import Image
from ps3_font import PS3Font


ROOT = Path(__file__).resolve().parent.parent
spec = importlib.util.spec_from_file_location(
    'menu_graphics', ROOT / 'update-manager/generate-jp-menu-graphics.py')
builder = importlib.util.module_from_spec(spec)
spec.loader.exec_module(builder)

font_path = ROOT / 'data_extract_ps3/default.fnt'
assert hashlib.sha256(font_path.read_bytes()).hexdigest() == (
    'b3cb269e39ede0c93db4974e4fafe7bb8b2bea45fab99502cb92d1ad5f3c6fce')
font = PS3Font(font_path)
# Check character-table boundaries separately from decompression/rendering.
assert [font.index(c) for c in ' A　あア'] == [0, 33, 96, 379, 473]
for index in range(len(font.offsets)):
    font.glyph(index)  # validates compressed lengths and backreferences

active = ROOT / 'graphics_jp/menu_jp/r_click_menu/chapters'
with tempfile.TemporaryDirectory(prefix='ps3-chapter-check-') as temp:
    builder.OUTPUT = Path(temp)
    generated = builder.OUTPUT / 'r_click_menu/chapters'
    generated.mkdir(parents=True)
    builder.generate_chapter_cards()
    names = {p.name for p in generated.glob('*.png')}
    assert len(names) == 163
    assert names == {p.name for p in active.glob('*.png')}
    for name in sorted(names):
        with Image.open(generated / name) as expected, Image.open(active / name) as actual:
            assert actual.size == (1920, 1080) and actual.mode == 'LA', name
            assert actual.tobytes() == expected.tobytes(), f'Stale chapter card: {name}'
            bounds = actual.getchannel('A').getbbox()
            assert bounds and 0 < bounds[0] < bounds[2] < 1920, name
            assert 0 < bounds[1] < bounds[3] < 1080, name
print('Verified 8180 PS3 glyph entries and 163 chapter cards; no canvas clipping')

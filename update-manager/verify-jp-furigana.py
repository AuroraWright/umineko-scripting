#!/usr/bin/env python3
"""Verify the assembled Japanese dialogue against the PS3 ruby manifest."""
from pathlib import Path
import json
import re
import sys

root = Path(__file__).resolve().parent.parent
script = Path(sys.argv[1]) if len(sys.argv) > 1 else root / 'jp.txt'
text = script.read_text()
manifest = json.loads((root / 'update-manager/jp-furigana.json').read_text())
blocks = {}
for match in re.finditer(r'^\*d(\d+)\n(d2? [^\n]*)', text, re.M):
    number = int(match[1])
    if number in blocks:
        raise RuntimeError(f'Duplicate dialogue d{number}')
    blocks[number] = match[2]
count = 0
for entry in manifest['entries']:
    number = entry['dialogue']
    assert blocks.get(number) == entry['after'], f'Missing/changed furigana in d{number}'
    assert re.sub(r'\{ruby:[^{}]*:([^{}]*)\}', r'\1', entry['after']) == entry['before']
    count += entry['after'].count('{ruby:')
assert count == 4674 and len(manifest['entries']) == 4234
assert '立て{ruby:こ:篭}もる' in blocks[6244]
print(f'Verified {count} PS3 readings in 4234 dialogues; base wording and commands preserved')

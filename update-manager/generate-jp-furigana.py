#!/usr/bin/env python3
"""Recover per-dialogue PS3 ruby without changing the port's wording or logic."""
from pathlib import Path
import difflib
import html
import json
import re

ROOT = Path(__file__).resolve().parent.parent
SYMBOLS = dict(zip(
    '｢｣ｧｨｩｪｫｬｭｮｱｲｳｴｵｶｷｸｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜｦﾝｰｯ､ﾟﾞ･?｡',
    '「」ぁぃぅぇぉゃゅょあいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよらりるれろわをんーっ、？！…　。'))


def ps3_text(raw):
    raw = raw.split('r', 1)[1]
    text, spans = '', []
    i = 0
    while i < len(raw):
        c = raw[i]
        if c == '!':
            text += SYMBOLS.get(raw[i + 1], raw[i + 1]); i += 2
        elif c == 'b' and raw[i + 1] != '.':
            m = re.match(r'b((?:!.|[^.])+?)\.<((?:!.|[^>])*)>\.', raw[i:])
            if not m:
                raise ValueError(raw[i:])
            reading, base = (s.replace('!', '') for s in m.groups())
            spans.append((len(text), len(text) + len(base), reading, base))
            text += base; i += len(m[0])
        elif c in 'abcovwsz':
            end = raw.find('.', i)
            if end < 0: raise ValueError(raw[i:])
            i = end + 1
        elif c in 'ekrtyY,.|{}[]\\':
            i += 1
        elif c in '�&':
            text += '　'; i += 1
        else:
            text += SYMBOLS.get(c, c); i += 1
    return text, spans


def visible(line):
    text, positions = '', []
    for part in re.finditer(r'`([^`]*)`', line):
        s = part[1]
        # Preserve positions in the original command, skipping only ONS styles.
        for m in re.finditer(r'\{[^{}]*?:|\{[^{}]*\}|\}|([^{}])', s):
            if m[1] is not None:
                text += m[1]; positions.append(part.start(1) + m.start(1))
    return text, positions


def main():
    blocks = {}
    for path in sorted((ROOT / 'game/main').glob('*.txt')):
        for m in re.finditer(r'^\*d(\d+)\n(d2? [^\n]*)', path.read_text(), re.M):
            blocks[int(m[1])] = (path.name, m[2])
    entries, omitted, errors = [], [], []
    for edition, offset in [('rondo', 0), ('chiru', 35000)]:
        xml = (ROOT / f'snr/output/script_{edition}.xml').read_text()
        for m in re.finditer(r'<ins type="DIALOGUE"[^>]*num="(\d+)"[^>]*data="([^"]*)"', xml):
            raw = html.unescape(m[2])
            if not re.search(r'b[^.]+\.<', raw): continue
            source, spans = ps3_text(raw)
            if not spans: continue
            number = int(m[1]) + offset
            # Existing extractor inserts Bern's omitted EP5 line after d40330.
            if edition == 'chiru' and number > 40330: number += 1
            if number not in blocks:
                omitted.append({'dialogue': number, 'reason': 'Dialogue absent from port', 'ruby': spans})
                continue
            filename, before = blocks[number]
            target, positions = visible(before)
            mapping = {}
            for match in difflib.SequenceMatcher(None, source, target, autojunk=False).get_matching_blocks():
                mapping.update((match.a + n, match.b + n) for n in range(match.size))
            inserts = []
            for start, end, reading, base in spans:
                indices = [mapping.get(n) for n in range(start, end)]
                if any(n is None for n in indices) or indices != list(range(indices[0], indices[0] + len(base))):
                    if number == 13728 and base == '自棄':
                        omitted.append({'dialogue': number, 'reason': 'Port omits this base word', 'reading': reading, 'base': base})
                    else: errors.append((number, reading, base, source, target))
                    continue
                a, b = positions[indices[0]], positions[indices[-1]] + 1
                if before[a:b] != base:
                    errors.append((number, 'Style or dialogue boundary inside ruby', base)); continue
                inserts.append((a, b, '{ruby:' + reading + ':' + base + '}'))
            after = before
            for a, b, annotation in sorted(inserts, reverse=True):
                after = after[:a] + annotation + after[b:]
            if inserts:
                assert re.sub(r'\{ruby:[^{}]*:([^{}]*)\}', r'\1', after) == before
                entries.append({'dialogue': number, 'file': filename, 'before': before, 'after': after})
    if errors:
        print(json.dumps(errors, ensure_ascii=False, indent=2))
        raise RuntimeError(f'{len(errors)} unresolved ruby spans; nothing written')
    out = ROOT / 'update-manager/jp-furigana.json'
    out.write_text(json.dumps({'entries': entries, 'omitted': omitted}, ensure_ascii=False, indent=2) + '\n')
    print(f'Recovered {sum(e["after"].count("{ruby:") for e in entries)} readings in {len(entries)} dialogues; {len(omitted)} port omissions')


if __name__ == '__main__':
    main()

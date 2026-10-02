#!/usr/bin/env python3
"""Compare menu sprite allocations before and after Japanese assembly."""

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent


def slots(text, mode):
    start = text.index('mov %0,100\nnumalias title_back_yes_lsp')
    end = text.index('numalias r_used_lsp,', start)
    block = text[start:text.index('\n', end)]
    values = {}
    aliases = {}
    skipping = False
    for line in block.splitlines():
        code = line.split(';')[0].strip()
        if code == '~':
            skipping = False
            continue
        if skipping or not code:
            continue
        condition = re.fullmatch(r'if %CHIRU_MODE != (\d) jumpf', code)
        if condition:
            skipping = mode != int(condition[1])
            continue
        condition = re.fullmatch(r'if %CHIRU_MODE = (\d) (.*)', code)
        if condition:
            if mode != int(condition[1]):
                continue
            code = condition[2]
        for command in code.split(':'):
            command = command.strip()
            move = re.fullmatch(r'mov %(\w+),(%\w+|\d+)', command)
            increment = re.fullmatch(r'(inc|add) %(\w+)(?:,(\d+))?', command)
            alias = re.fullmatch(r'numalias (\w+),%0', command)
            if move:
                values[move[1]] = values[move[2][1:]] if move[2].startswith('%') else int(move[2])
            elif increment:
                values[increment[2]] += int(increment[3]) if increment[3] else 1
            elif alias:
                aliases[alias[1]] = values['0']
            else:
                raise ValueError(f'Unsupported allocation instruction: {command}')
    return aliases


source = (ROOT / 'script/umi_ftr.txt').read_text()
assembled = Path(sys.argv[1] if len(sys.argv) > 1 else ROOT / 'jp.txt').read_text()
for mode in (0, 1):
    expected = slots(source, mode)
    actual = slots(assembled, mode)
    retained = expected
    if actual != retained:
        raise AssertionError(f'Menu sprite allocations changed for CHIRU_MODE={mode}')
    if mode == 1 and actual['r_cha_back_lsp'] <= 202:
        raise AssertionError('Bern background covers answer text sprites 201/202')
    print(f'CHIRU_MODE={mode}: {len(actual)} sprite aliases preserved; background={actual["r_cha_back_lsp"]}')

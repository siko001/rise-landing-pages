#!/usr/bin/env python3
"""Stamp an explicit tag, or choose the next release version after a main push."""
import argparse
import json
from pathlib import Path
import re
import subprocess

ROOT = Path(__file__).resolve().parent.parent
VERSION = r'(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)'


def prepare(root, tag=None):
    package = json.loads((root / 'package.json').read_text())
    if tag is not None:
        if not re.fullmatch('v' + VERSION, tag):
            raise ValueError('Release tags must use stable vX.Y.Z versions.')
        version = tag[1:]
    else:
        source = package['version']
        if not re.fullmatch(VERSION, source):
            raise ValueError('The source version must use stable X.Y.Z.')
        tags = subprocess.check_output(['git', 'tag', '--list'], cwd=root, text=True).splitlines()
        versions = [tuple(map(int, t[1:].split('.'))) for t in tags if re.fullmatch('v' + VERSION, t)]
        current = tuple(map(int, source.split('.')))
        latest = max(versions, default=(-1, -1, -1))
        chosen = current if current > latest else (latest[0], latest[1], latest[2] + 1)
        version = '.'.join(map(str, chosen))
        tag = 'v' + version
    main = root / 'rise-landing-pages.php'
    text = main.read_text()
    text, headers = re.subn(r'(?m)^( \* Version: )\S+', lambda m: m[1] + version, text)
    text, constants = re.subn(r"(define\( 'RISE_LP_VERSION', ')[^']+(' \);)", lambda m: m[1] + version + m[2], text)
    if headers != 1 or constants != 1:
        raise ValueError('Could not locate the plugin version fields.')
    main.write_text(text)
    readme = root / 'readme.txt'
    text, stable = re.subn(r'(?m)^(Stable tag: )\S+', lambda m: m[1] + version, readme.read_text())
    if stable != 1:
        raise ValueError('Could not locate the stable tag.')
    readme.write_text(text)
    for name in ('package.json', 'package-lock.json'):
        file = root / name
        data = json.loads(file.read_text())
        old = data['version']
        text = file.read_text().replace('"version": "' + old + '"', '"version": "' + version + '"', 2 if name == 'package-lock.json' else 1)
        file.write_text(text)
    return tag


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--tag')
    args = parser.parse_args()
    print(prepare(ROOT, args.tag))

#!/usr/bin/env python3
"""Validate a stable version and package the WordPress runtime deterministically."""

import argparse
import json
from pathlib import Path
import re
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo


ROOT = Path(__file__).resolve().parent.parent
SLUG = 'rise-landing-pages'
STABLE_VERSION = r'(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)'
INCLUDE = (
    'rise-landing-pages.php', 'README.md', 'readme.txt', 'DEPLOYMENT.md', 'LICENSE',
    'config/github-updater.php', 'src', 'blocks', 'assets', 'build', 'templates',
)
REQUIRED = (
    'build/editor.js', 'build/editor.asset.php', 'build/editor.css',
    'build/editor-rtl.css', 'build/smooth-scroll.js', 'build/smooth-scroll.asset.php',
    'assets/frontend.css', 'assets/frontend.js', 'assets/admin-list.js',
    'assets/chrome-preview-frame.js', 'assets/editor-divi.css',
    'assets/fitness-editor-font.css', 'assets/fonts/F37Judge-Bold.ttf',
    'assets/marquee.js', 'assets/media.js', 'assets/settings.css', 'assets/settings.js',
    'assets/rise-mark.svg', 'assets/rise-wordmark.svg',
    'assets/rise-medical-icon.png', 'assets/rise-physio-icon.png',
    'assets/logos/rise-medical.svg', 'assets/logos/rise-physio.png',
    'assets/vendor/lenis-LICENSE.txt',
)
SOURCE_ONLY = {'assets/editor.js', 'assets/editor.css', 'assets/smooth-scroll.js'}
EXCLUDED_DIRS = {'node_modules', '__pycache__', 'tests', 'test-results', 'coverage'}


def fail(message):
    raise SystemExit(message)


def matched_version(pattern, contents, label):
    match = re.search(pattern, contents, re.MULTILINE)
    if not match:
        fail(f'Missing version in {label}.')
    return match.group(1)


def validate_versions(root, expected=None):
    main = (root / 'rise-landing-pages.php').read_text()
    readme = (root / 'readme.txt').read_text()
    versions = {
        'plugin header': matched_version(r'^\s*\*\s*Version:\s*(\S+)', main, 'plugin header'),
        'RISE_LP_VERSION': matched_version(
            r"define\(\s*['\"]RISE_LP_VERSION['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)",
            main, 'RISE_LP_VERSION',
        ),
        'readme.txt stable tag': matched_version(r'^Stable tag:\s*(\S+)', readme, 'readme.txt'),
    }
    for name in ('package.json', 'package-lock.json'):
        metadata = json.loads((root / name).read_text())
        versions[name] = metadata.get('version')
        if name == 'package-lock.json':
            versions['package-lock.json root package'] = metadata.get('packages', {}).get('', {}).get('version')

    version = versions['plugin header']
    if not re.fullmatch(STABLE_VERSION, version):
        fail(f'Expected a stable X.Y.Z version, got {version!r}.')
    if expected is not None:
        if not re.fullmatch('v?' + STABLE_VERSION, expected):
            fail(f'Expected a stable X.Y.Z or vX.Y.Z release tag, got {expected!r}.')
        versions['requested release'] = expected.removeprefix('v')
    mismatches = [f'{label}={value!r}' for label, value in versions.items() if value != version]
    if mismatches:
        fail(f'Version mismatch (plugin header is {version}): ' + ', '.join(mismatches))
    return version


def is_runtime_file(path):
    if any(part.startswith('.') or part in EXCLUDED_DIRS for part in path.parts):
        return False
    if path.suffix in {'.map', '.zip', '.log', '.pyc'}:
        return False
    return path.as_posix() not in SOURCE_ONLY and path.parts[:2] != ('assets', 'editor')


def collect_files(root):
    files = []
    for name in INCLUDE:
        path = root / name
        if not path.exists():
            fail(f'Missing package input: {name}.')
        candidates = [path] if path.is_file() else [path, *path.rglob('*')]
        for candidate in candidates:
            if candidate.is_symlink() or root not in candidate.resolve().parents:
                fail(f'Refusing symlink or path outside plugin root: {candidate}')
            if candidate.is_file() and is_runtime_file(candidate.relative_to(root)):
                files.append(candidate)
    relative_files = {path.relative_to(root).as_posix() for path in files}
    missing = [name for name in REQUIRED if name not in relative_files or not (root / name).stat().st_size]
    if missing:
        fail('Missing or empty runtime assets: ' + ', '.join(missing) + '; run npm ci && npm run build first.')
    return sorted(files)


def package(root, expected=None):
    root = root.resolve()
    version = validate_versions(root, expected)
    files = collect_files(root)
    output_dir = root / 'dist'
    if output_dir.is_symlink():
        fail('Refusing symlink for dist directory.')
    output_dir.mkdir(exist_ok=True)
    output = output_dir / f'{SLUG}.zip'
    temporary = output.with_suffix('.zip.tmp')
    if output.is_symlink() or temporary.is_symlink():
        fail('Refusing symlink for package output.')
    try:
        with ZipFile(temporary, 'w', compression=ZIP_DEFLATED, compresslevel=9) as archive:
            for path in files:
                # Fixed timestamps and permissions keep identical inputs byte-for-byte reproducible.
                entry = ZipInfo(f'{SLUG}/{path.relative_to(root).as_posix()}', (1980, 1, 1, 0, 0, 0))
                entry.create_system = 3
                entry.external_attr = 0o100644 << 16
                entry.compress_type = ZIP_DEFLATED
                archive.writestr(entry, path.read_bytes())
        with ZipFile(temporary) as archive:
            if archive.testzip() is not None:
                fail('Package integrity check failed.')
        temporary.replace(output)
    finally:
        temporary.unlink(missing_ok=True)
    print(f'{output}: version {version}, {len(files)} files, {output.stat().st_size:,} bytes')
    return output


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--version', help='Require this stable version/tag (for example 1.1.80 or v1.1.80).')
    args = parser.parse_args()
    try:
        package(ROOT, args.version)
    except (OSError, ValueError) as error:
        fail(f'Cannot package plugin: {error}')


if __name__ == '__main__':
    main()

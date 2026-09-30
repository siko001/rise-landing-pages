#!/usr/bin/env python3
"""Check automatic release numbering and package version alignment in isolation."""
import importlib.util
from pathlib import Path
import shutil
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parent.parent


def module(name):
    spec = importlib.util.spec_from_file_location(name, ROOT / 'scripts' / (name + '.py'))
    loaded = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(loaded)
    return loaded


prepare = module('prepare-release')
package = module('package')


class ReleaseTests(unittest.TestCase):
    def setUp(self):
        self.directory = tempfile.TemporaryDirectory()
        self.addCleanup(self.directory.cleanup)
        self.root = Path(self.directory.name)
        for name in ('rise-landing-pages.php', 'readme.txt', 'package.json', 'package-lock.json'):
            shutil.copyfile(ROOT / name, self.root / name)
        prepare.prepare(self.root, 'v1.1.81')

    def automatic(self, tags, expected):
        with patch.object(prepare.subprocess, 'check_output', return_value='\n'.join(tags)):
            tag = prepare.prepare(self.root)
        self.assertEqual(tag, expected)
        self.assertEqual(package.validate_versions(self.root, tag), tag[1:])

    def test_next_patch(self):
        self.automatic(['v1.1.80', 'v1.1.81'], 'v1.1.82')

    def test_numeric_order_and_nonstable_tags(self):
        self.automatic(['v1.1.9', 'v1.1.100', 'v9.0.0-beta', 'unrelated'], 'v1.1.101')

    def test_new_source_version(self):
        prepare.prepare(self.root, 'v2.0.0')
        self.automatic(['v1.1.81'], 'v2.0.0')

    def test_initial_release(self):
        self.automatic([], 'v1.1.81')

    def test_manual_tag(self):
        tag = prepare.prepare(self.root, 'v1.2.0')
        self.assertEqual(package.validate_versions(self.root, tag), '1.2.0')

    def test_invalid_manual_tag_leaves_files_unchanged(self):
        before = {p.name: p.read_bytes() for p in self.root.iterdir()}
        with self.assertRaises(ValueError):
            prepare.prepare(self.root, 'v1.2.0-beta')
        self.assertEqual(before, {p.name: p.read_bytes() for p in self.root.iterdir()})


if __name__ == '__main__':
    unittest.main()

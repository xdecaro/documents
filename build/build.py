#!/usr/bin/env python3
from __future__ import annotations

import hashlib
import shutil
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VERSION = (ROOT / 'VERSION').read_text(encoding='utf-8').strip()
DIST = ROOT / 'dist'
FIXED_TIME = (1980, 1, 1, 0, 0, 0)


def zip_tree(source: Path, destination: Path) -> None:
    destination.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(destination, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in sorted(p for p in source.rglob('*') if p.is_file()):
            rel = path.relative_to(source).as_posix()
            info = zipfile.ZipInfo(rel, FIXED_TIME)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, path.read_bytes())


def package_zip(component_zip: Path, analytics_zip: Path, destination: Path) -> None:
    entries = {
        'pkg_decarodocuments.xml': (ROOT / 'package/pkg_decarodocuments.xml').read_bytes(),
        'script.php': (ROOT / 'package/script.php').read_bytes(),
        'com_decarodocuments.zip': component_zip.read_bytes(),
        'plg_xdecaroanalytics_decarodocuments.zip': analytics_zip.read_bytes(),
    }
    with zipfile.ZipFile(destination, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name in sorted(entries):
            info = zipfile.ZipInfo(name, FIXED_TIME)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, entries[name])


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open('rb') as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b''):
            digest.update(chunk)
    return digest.hexdigest()


def main() -> None:
    if not VERSION:
        raise SystemExit('VERSION is empty')
    shutil.rmtree(DIST, ignore_errors=True)
    DIST.mkdir(parents=True)

    component_zip = DIST / f'com_decarodocuments_{VERSION}.zip'
    analytics_zip = DIST / f'plg_xdecaroanalytics_decarodocuments_{VERSION}.zip'
    package = DIST / f'pkg_decarodocuments_{VERSION}.zip'

    zip_tree(ROOT / 'component', component_zip)
    zip_tree(ROOT / 'plugins/xdecaroanalytics/decarodocuments', analytics_zip)
    package_zip(component_zip, analytics_zip, package)

    assets = [component_zip, analytics_zip, package]
    (DIST / 'SHA256SUMS.txt').write_text(
        ''.join(f'{sha256(asset)}  {asset.name}\n' for asset in assets),
        encoding='utf-8',
    )
    for asset in assets:
        print(asset)
    print('Package SHA-256:', sha256(package))


if __name__ == '__main__':
    main()

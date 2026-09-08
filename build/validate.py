#!/usr/bin/env python3
from __future__ import annotations

import re
import sys
import zipfile
from pathlib import Path
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
VERSION = (ROOT / 'VERSION').read_text(encoding='utf-8').strip()


def fail(message: str) -> None:
    print('ERROR:', message, file=sys.stderr)
    raise SystemExit(1)


def xml_version(path: Path) -> str:
    root = ET.parse(path).getroot()
    return (root.findtext('version') or '').strip()


def validate_source() -> None:
    if VERSION != '1.1.0':
        fail(f'Unexpected release version {VERSION!r}')

    component = ROOT / 'component/decarodocuments.xml'
    package = ROOT / 'package/pkg_decarodocuments.xml'
    for path in (component, package):
        ET.parse(path)
        if xml_version(path) != VERSION:
            fail(f'{path.relative_to(ROOT)} version does not match VERSION')

    package_root = ET.parse(package).getroot()
    if (package_root.findtext('packagename') or '').strip() != 'decarodocuments':
        fail('Package name is not stable')
    files = package_root.findall('./files/file')
    if len(files) != 1 or (files[0].get('id') or '') != 'com_decarodocuments' or (files[0].text or '').strip() != 'com_decarodocuments.zip':
        fail('Package child definition is invalid')
    update_servers = [(n.text or '').strip() for n in package_root.findall('./updateservers/server')]
    expected_update = 'https://raw.githubusercontent.com/xdecaro/documents/main/updates/pkg_decarodocuments.xml'
    if update_servers != [expected_update]:
        fail('Package update server is invalid')

    feed = ET.parse(ROOT / 'updates/pkg_decarodocuments.xml').getroot().find('update')
    if feed is None:
        fail('Update feed has no update node')
    if (feed.findtext('version') or '').strip() != VERSION:
        fail('Update feed version mismatch')
    if (feed.findtext('element') or '').strip() != 'pkg_decarodocuments':
        fail('Update feed element mismatch')
    expected_download = f'https://github.com/xdecaro/documents/releases/download/v{VERSION}/pkg_decarodocuments_{VERSION}.zip'
    if (feed.findtext('./downloads/downloadurl') or '').strip() != expected_download:
        fail('Update feed download URL mismatch')

    required = [
        'component/admin/access.xml',
        'component/admin/config.xml',
        'component/admin/forms/document.xml',
        'component/admin/services/provider.php',
        'component/admin/sql/install.mysql.utf8mb4.sql',
        'component/admin/sql/updates/mysql/1.0.0.sql',
        'component/admin/sql/updates/mysql/1.1.0.sql',
        'component/admin/src/Controller/DocumentController.php',
        'component/admin/src/Helper/CoreUiHelper.php',
        'component/admin/src/Model/DocumentModel.php',
        'component/admin/src/Model/DocumentsModel.php',
        'component/admin/src/Model/InformationModel.php',
        'component/admin/src/Service/CoreIntegrationService.php',
        'component/admin/src/Service/StorageService.php',
        'component/admin/src/Table/DocumentTable.php',
        'component/admin/tmpl/document/edit.php',
        'component/admin/tmpl/documents/default.php',
        'component/admin/tmpl/information/default.php',
        'package/script.php',
    ]
    for rel in required:
        if not (ROOT / rel).is_file():
            fail(f'Missing required source file {rel}')

    if (ROOT / 'component/media').exists():
        fail('Documents must not introduce a duplicate local design system/media bundle')

    package_script = (ROOT / 'package/script.php').read_text(encoding='utf-8')
    for marker in ('MINIMUM_CORE', "'1.3.0'", 'pkg_xdecarocore', 'xdecaro\\Core\\Version'):
        if marker not in package_script:
            fail(f'Core dependency guard missing {marker}')

    core_helper = (ROOT / 'component/admin/src/Helper/CoreUiHelper.php').read_text(encoding='utf-8')
    for marker in ('xdecaro\\Core\\Asset\\AssetService', 'useComponents', "'1.3.0'"):
        if marker not in core_helper:
            fail(f'Core UI integration missing {marker}')

    info = (ROOT / 'component/admin/src/Model/InformationModel.php').read_text(encoding='utf-8')
    relation = (ROOT / 'component/admin/src/Service/CoreIntegrationService.php').read_text(encoding='utf-8')
    for text, label in ((package_script, 'package installer'), (core_helper, 'Core UI helper'), (info, 'Information model'), (relation, 'Core relation adapter')):
        if re.search(r'Xdecaro\\+Core', text):
            fail(f'Legacy Core namespace remains in {label}')

    storage = (ROOT / 'component/admin/src/Service/StorageService.php').read_text(encoding='utf-8')
    for marker in ('dirname(JPATH_ROOT)', 'is_uploaded_file', 'FILEINFO_MIME_TYPE', 'move_uploaded_file', "hash_file('sha256'", 'MAX_FILE_SIZE'):
        if marker not in storage:
            fail(f'Storage security marker missing {marker}')
    if 'JPATH_ROOT . DIRECTORY_SEPARATOR' in storage:
        fail('Private storage must not be rooted inside the Joomla public root')

    model = (ROOT / 'component/admin/src/Model/DocumentModel.php').read_text(encoding='utf-8')
    for marker in ("authorise('core.edit.state'", 'random_bytes(16)', 'storeUploadedFile', 'parent::save'):
        if marker not in model:
            fail(f'Document save security marker missing {marker}')

    controller = (ROOT / 'component/admin/src/Controller/DocumentController.php').read_text(encoding='utf-8')
    for marker in ("authorise('core.manage'", 'getAuthorisedViewLevels', 'X-Content-Type-Options', 'Content-Disposition'):
        if marker not in controller:
            fail(f'Download authorization marker missing {marker}')

    edit_template = (ROOT / 'component/admin/tmpl/document/edit.php').read_text(encoding='utf-8')
    list_template = (ROOT / 'component/admin/tmpl/documents/default.php').read_text(encoding='utf-8')
    for text, label in ((edit_template, 'edit'), (list_template, 'list')):
        if "HTMLHelper::_('form.token')" not in text:
            fail(f'CSRF token missing from {label} template')
        if 'xdecaro-scope' not in text:
            fail(f'Core scope missing from {label} template')

    sql = (ROOT / 'component/admin/sql/install.mysql.utf8mb4.sql').read_text(encoding='utf-8')
    for marker in ('#__decarodocuments_documents', '#__decarodocuments_relations', 'FOREIGN KEY (`document_id`)', 'target_component'):
        if marker not in sql:
            fail(f'Database schema missing {marker}')
    if re.search(r'FOREIGN KEY.*target_', sql, re.I | re.S):
        fail('Cross-product target columns must not have foreign keys')
    if 'DROP TABLE' in sql.upper():
        fail('Install/update SQL must not drop tables')

    for marker in ("COMPONENT = 'com_decarodocuments'", "MINIMUM_CORE = '1.3.0'", 'xdecaro\\Core\\Integration\\EntityReference', 'xdecaro\\Core\\Integration\\RelationReference'):
        if marker not in relation:
            fail(f'Core relation adapter missing {marker}')

    for p in ROOT.rglob('*.xml'):
        if 'dist' not in p.parts:
            ET.parse(p)

    print(f'Documents {VERSION} source validation OK')


def validate_dist() -> None:
    dist = ROOT / 'dist'
    component_zip = dist / f'com_decarodocuments_{VERSION}.zip'
    package_zip = dist / f'pkg_decarodocuments_{VERSION}.zip'
    sums = dist / 'SHA256SUMS.txt'
    for p in (component_zip, package_zip, sums):
        if not p.is_file():
            fail(f'Missing build artifact {p.name}')

    with zipfile.ZipFile(component_zip) as archive:
        names = set(archive.namelist())
        for required in ('decarodocuments.xml', 'admin/services/provider.php', 'admin/src/Service/StorageService.php', 'admin/tmpl/documents/default.php'):
            if required not in names:
                fail(f'Component ZIP missing {required}')

    with zipfile.ZipFile(package_zip) as archive:
        names = set(archive.namelist())
        if names != {'pkg_decarodocuments.xml', 'script.php', 'com_decarodocuments.zip'}:
            fail(f'Unexpected package ZIP contents: {sorted(names)}')

    print(f'Documents {VERSION} dist validation OK')


if __name__ == '__main__':
    validate_source()
    if '--dist' in sys.argv:
        validate_dist()

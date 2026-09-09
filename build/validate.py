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


def require_markers(text: str, markers: tuple[str, ...], label: str) -> None:
    for marker in markers:
        if marker not in text:
            fail(f'{label} missing {marker}')


def validate_source() -> None:
    if VERSION != '1.3.0':
        fail(f'Unexpected release version {VERSION!r}')

    component = ROOT / 'component/decarodocuments.xml'
    package = ROOT / 'package/pkg_decarodocuments.xml'
    analytics_manifest = ROOT / 'plugins/xdecaroanalytics/decarodocuments/decarodocuments.xml'
    for path in (component, package, analytics_manifest):
        ET.parse(path)
        if xml_version(path) != VERSION:
            fail(f'{path.relative_to(ROOT)} version does not match VERSION')

    component_root = ET.parse(component).getroot()
    install_sql = component_root.find('./install/sql/file')
    if install_sql is None or (install_sql.get('driver') or '') != 'mysql' or (install_sql.get('charset') or '') != 'utf8':
        fail('Joomla install SQL manifest must use driver="mysql" charset="utf8"')
    if (install_sql.text or '').strip() != 'sql/install.mysql.utf8mb4.sql':
        fail('Component install SQL path changed unexpectedly')

    package_root = ET.parse(package).getroot()
    if (package_root.findtext('packagename') or '').strip() != 'decarodocuments':
        fail('Package name is not stable')
    children = {(node.get('type'), node.get('id'), node.get('group'), (node.text or '').strip()) for node in package_root.findall('./files/file')}
    expected_children = {
        ('component', 'com_decarodocuments', None, 'com_decarodocuments.zip'),
        ('plugin', 'decarodocuments', 'xdecaroanalytics', 'plg_xdecaroanalytics_decarodocuments.zip'),
    }
    if children != expected_children:
        fail(f'Package child definition is invalid: {children}')
    expected_update = 'https://raw.githubusercontent.com/xdecaro/documents/main/updates/pkg_decarodocuments.xml'
    update_servers = [(n.text or '').strip() for n in package_root.findall('./updateservers/server')]
    if update_servers != [expected_update]:
        fail('Package update server is invalid')

    feed = ET.parse(ROOT / 'updates/pkg_decarodocuments.xml').getroot().find('update')
    if feed is None or (feed.findtext('version') or '').strip() != VERSION:
        fail('Update feed version mismatch')
    if (feed.findtext('element') or '').strip() != 'pkg_decarodocuments':
        fail('Update feed element mismatch')
    expected_download = f'https://github.com/xdecaro/documents/releases/download/v{VERSION}/pkg_decarodocuments_{VERSION}.zip'
    if (feed.findtext('./downloads/downloadurl') or '').strip() != expected_download:
        fail('Update feed download URL mismatch')

    required = [
        'component/admin/access.xml', 'component/admin/config.xml', 'component/admin/forms/document.xml',
        'component/admin/services/provider.php', 'component/admin/sql/install.mysql.utf8mb4.sql',
        'component/admin/sql/updates/mysql/1.0.0.sql', 'component/admin/sql/updates/mysql/1.1.0.sql',
        'component/admin/sql/updates/mysql/1.2.0.sql', 'component/admin/sql/updates/mysql/1.2.1.sql',
        'component/admin/sql/updates/mysql/1.2.2.sql', 'component/admin/sql/updates/mysql/1.3.0.sql',
        'component/admin/src/Controller/DocumentController.php', 'component/admin/src/Extension/DecarodocumentsComponent.php',
        'component/admin/src/Helper/CoreUiHelper.php', 'component/admin/src/Model/DocumentModel.php',
        'component/admin/src/Model/DocumentsModel.php', 'component/admin/src/Model/InformationModel.php',
        'component/admin/src/Service/CoreIntegrationService.php', 'component/admin/src/Service/RelationService.php',
        'component/admin/src/Service/StorageService.php', 'component/admin/src/Service/AnalyticsSourceService.php',
        'component/admin/src/Service/CrossProductIntegrationService.php', 'component/admin/src/Table/DocumentTable.php',
        'component/admin/tmpl/document/edit.php', 'component/admin/tmpl/documents/default.php',
        'component/admin/tmpl/information/default.php', 'package/script.php',
        'plugins/xdecaroanalytics/decarodocuments/services/provider.php',
        'plugins/xdecaroanalytics/decarodocuments/src/Extension/Decarodocuments.php',
        'plugins/xdecaroanalytics/decarodocuments/src/Provider/DocumentsProvider.php',
    ]
    for rel in required:
        if not (ROOT / rel).is_file():
            fail(f'Missing required source file {rel}')

    if (ROOT / 'component/media').exists():
        fail('Documents must not introduce a duplicate local design system/media bundle')

    package_script = (ROOT / 'package/script.php').read_text(encoding='utf-8')
    require_markers(package_script, ('final class pkg_decarodocumentsInstallerScript', 'MINIMUM_CORE', "'1.3.0'", 'pkg_xdecarocore', 'xdecaro\\Core\\Version', 'return false;', 'postflight', "'xdecaroanalytics'", "'decarodocuments'", "'discover_install'"), 'Package installer')
    if 'class PkgDecarodocumentsInstallerScript' in package_script:
        fail('Incorrect legacy package installer class name must not return')

    core_helper = (ROOT / 'component/admin/src/Helper/CoreUiHelper.php').read_text(encoding='utf-8')
    require_markers(core_helper, ('xdecaro\\Core\\Asset\\AssetService', 'useComponents', "'1.3.0'"), 'Core UI integration')

    info = (ROOT / 'component/admin/src/Model/InformationModel.php').read_text(encoding='utf-8')
    core_integration = (ROOT / 'component/admin/src/Service/CoreIntegrationService.php').read_text(encoding='utf-8')
    relation_service = (ROOT / 'component/admin/src/Service/RelationService.php').read_text(encoding='utf-8')
    extension = (ROOT / 'component/admin/src/Extension/DecarodocumentsComponent.php').read_text(encoding='utf-8')
    provider = (ROOT / 'component/admin/services/provider.php').read_text(encoding='utf-8')
    cross = (ROOT / 'component/admin/src/Service/CrossProductIntegrationService.php').read_text(encoding='utf-8')
    analytics = (ROOT / 'component/admin/src/Service/AnalyticsSourceService.php').read_text(encoding='utf-8')
    analytics_provider = (ROOT / 'plugins/xdecaroanalytics/decarodocuments/src/Provider/DocumentsProvider.php').read_text(encoding='utf-8')

    for text, label in ((package_script, 'package installer'), (core_helper, 'Core UI helper'), (info, 'Information model'), (core_integration, 'Core relation adapter'), (relation_service, 'Relation service'), (extension, 'Documents component extension')):
        if re.search(r'Xdecaro\\+Core', text):
            fail(f'Legacy Core namespace remains in {label}')

    require_markers(core_integration, ("COMPONENT = 'com_decarodocuments'", "MINIMUM_CORE = '1.3.0'", 'xdecaro\\Core\\Integration\\EntityReference', 'xdecaro\\Core\\Integration\\RelationReference', 'CapabilityRegistry', 'documents.relations.attach', 'documents.relations.detach', 'documents.relations.query', 'documents.analytics.provider', 'documents.notifications.bridge', 'documents.tasks.bridge'), 'Core integration')
    require_markers(provider, ('DecarodocumentsComponent', 'RelationService::class', 'AnalyticsSourceService::class', 'CrossProductIntegrationService::class', 'DatabaseInterface::class', 'setRelationService', 'setAnalyticsSourceService', 'setCrossProductIntegrationService'), 'DI provider')
    require_markers(extension, ('extends MVCComponent', 'getRelationService()', 'setRelationService(', 'getAnalyticsSourceService()', 'getCrossProductIntegrationService()'), 'Documents component extension')
    require_markers(relation_service, ("authorise($action, CoreIntegrationService::COMPONENT)", '#__decarodocuments_relations', '#__decarodocuments_documents', 'RelationReference', 'EntityReference', 'relationExists', 'target_component', 'target_entity', 'target_id', 'relation_type'), 'Relation service')
    if 'stored_name' in relation_service or 'JPATH_ROOT' in relation_service:
        fail('Relation API must not expose private storage paths')

    require_markers(cross, ("bootComponent('com_xdecaronotifications')", "bootComponent('com_xdecarotasks')", 'getNotificationService', 'getTaskService', 'source_component'), 'Cross-product bridge')
    if '#__xdecaronotifications_' in cross or '#__xdecarotasks_' in cross:
        fail('Cross-product bridge must not access private external tables')
    require_markers(analytics, ('assertAuthorised', '#__decarodocuments_documents', '#__decarodocuments_relations', 'documents.total', 'documents.by_mime_type'), 'Analytics source')
    require_markers(analytics_provider, ('AnalyticsProviderInterface', "return 'documents'", 'AnalyticsSourceService'), 'Analytics provider adapter')
    if '#__decarodocuments_' in analytics_provider:
        fail('Analytics plugin must delegate to the Documents-owned source service')

    storage = (ROOT / 'component/admin/src/Service/StorageService.php').read_text(encoding='utf-8')
    require_markers(storage, ('dirname(JPATH_ROOT)', 'is_uploaded_file', 'FILEINFO_MIME_TYPE', 'move_uploaded_file', "hash_file('sha256'", 'MAX_FILE_SIZE'), 'Storage security')
    if 'JPATH_ROOT . DIRECTORY_SEPARATOR' in storage:
        fail('Private storage must not be rooted inside the Joomla public root')

    model = (ROOT / 'component/admin/src/Model/DocumentModel.php').read_text(encoding='utf-8')
    require_markers(model, ("authorise('core.edit.state'", 'random_bytes(16)', 'storeUploadedFile', 'parent::save'), 'Document save security')
    controller = (ROOT / 'component/admin/src/Controller/DocumentController.php').read_text(encoding='utf-8')
    require_markers(controller, ("authorise('core.manage'", 'getAuthorisedViewLevels', 'X-Content-Type-Options', 'Content-Disposition'), 'Download authorization')

    for rel in ('component/admin/tmpl/document/edit.php', 'component/admin/tmpl/documents/default.php'):
        text = (ROOT / rel).read_text(encoding='utf-8')
        require_markers(text, ("HTMLHelper::_('form.token')", 'xdecaro-scope'), rel)

    install_schema = (ROOT / 'component/admin/sql/install.mysql.utf8mb4.sql').read_text(encoding='utf-8')
    repair_schema = (ROOT / 'component/admin/sql/updates/mysql/1.2.1.sql').read_text(encoding='utf-8')
    for schema, label in ((install_schema, 'Database schema'), (repair_schema, '1.2.1 repair schema')):
        require_markers(schema, ('CREATE TABLE IF NOT EXISTS `#__decarodocuments_documents`', 'CREATE TABLE IF NOT EXISTS `#__decarodocuments_relations`', 'FOREIGN KEY (`document_id`)', 'target_component', 'DEFAULT CHARSET=utf8mb4'), label)
        if re.search(r'FOREIGN KEY.*target_', schema, re.I | re.S):
            fail('Cross-product target columns must not have foreign keys')

    for marker_name in ('1.2.2.sql', '1.3.0.sql'):
        marker = (ROOT / 'component/admin/sql/updates/mysql' / marker_name).read_text(encoding='utf-8')
        if re.search(r'\b(?:CREATE|ALTER|DROP|TRUNCATE|DELETE|UPDATE|INSERT)\b', marker, re.I):
            fail(f'{marker_name} must remain a schema-version marker without database mutations')

    for sql_file in (ROOT / 'component/admin/sql').rglob('*.sql'):
        if re.search(r'\b(?:DROP\s+TABLE|TRUNCATE\s+TABLE)\b', sql_file.read_text(encoding='utf-8'), re.I):
            fail(f'Destructive SQL found in {sql_file.relative_to(ROOT)}')

    for path in ROOT.rglob('*.xml'):
        if 'dist' not in path.parts:
            ET.parse(path)

    print(f'Documents {VERSION} source validation OK')


def validate_dist() -> None:
    dist = ROOT / 'dist'
    component_zip = dist / f'com_decarodocuments_{VERSION}.zip'
    analytics_zip = dist / f'plg_xdecaroanalytics_decarodocuments_{VERSION}.zip'
    package_zip = dist / f'pkg_decarodocuments_{VERSION}.zip'
    sums = dist / 'SHA256SUMS.txt'
    for path in (component_zip, analytics_zip, package_zip, sums):
        if not path.is_file():
            fail(f'Missing build artifact {path.name}')

    with zipfile.ZipFile(component_zip) as archive:
        names = set(archive.namelist())
        for required in ('decarodocuments.xml', 'admin/services/provider.php', 'admin/src/Extension/DecarodocumentsComponent.php', 'admin/src/Service/StorageService.php', 'admin/src/Service/RelationService.php', 'admin/src/Service/AnalyticsSourceService.php', 'admin/src/Service/CrossProductIntegrationService.php', 'admin/tmpl/documents/default.php', f'admin/sql/updates/mysql/{VERSION}.sql'):
            if required not in names:
                fail(f'Component ZIP missing {required}')

    with zipfile.ZipFile(analytics_zip) as archive:
        names = set(archive.namelist())
        for required in ('decarodocuments.xml', 'services/provider.php', 'src/Extension/Decarodocuments.php', 'src/Provider/DocumentsProvider.php'):
            if required not in names:
                fail(f'Analytics plugin ZIP missing {required}')

    with zipfile.ZipFile(package_zip) as archive:
        names = set(archive.namelist())
        expected = {'pkg_decarodocuments.xml', 'script.php', 'com_decarodocuments.zip', 'plg_xdecaroanalytics_decarodocuments.zip'}
        if names != expected:
            fail(f'Unexpected package ZIP contents: {sorted(names)}')
        installer = archive.read('script.php').decode('utf-8')
        if 'final class pkg_decarodocumentsInstallerScript' not in installer:
            fail('Package ZIP installer class does not match Joomla package element resolution')

    print(f'Documents {VERSION} dist validation OK')


if __name__ == '__main__':
    validate_source()
    if '--dist' in sys.argv:
        validate_dist()

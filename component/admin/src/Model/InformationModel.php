<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Version;
use Xdecaro\Component\Decarodocuments\Administrator\Service\StorageService;

final class InformationModel extends BaseDatabaseModel
{
    public function getInfo(): array
    {
        $db = $this->getDatabase();
        $tables = array_flip($db->getTableList());

        $documentsTable = $db->replacePrefix('#__decarodocuments_documents');
        $relationsTable = $db->replacePrefix('#__decarodocuments_relations');
        $versionsTable = $db->replacePrefix('#__decarodocuments_versions');
        $auditTable = $db->replacePrefix('#__decarodocuments_audit');

        $documentCount = $this->countTable($documentsTable, '#__decarodocuments_documents', $tables);
        $versionCount = $this->countTable($versionsTable, '#__decarodocuments_versions', $tables);
        $expiringCount = 0;

        if (isset($tables[$documentsTable])) {
            $now = Factory::getDate()->toSql();
            $until = Factory::getDate('+30 days')->toSql();
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__decarodocuments_documents'))
                ->where($db->quoteName('state') . ' >= 0')
                ->where($db->quoteName('expires_at') . ' IS NOT NULL')
                ->where($db->quoteName('expires_at') . ' >= ' . $db->quote($now))
                ->where($db->quoteName('expires_at') . ' <= ' . $db->quote($until));
            $expiringCount = (int) $db->setQuery($query)->loadResult();
        }

        $storage = new StorageService();
        $coreVersion = class_exists(\xdecaro\Core\Version::class) ? (string) \xdecaro\Core\Version::VERSION : '';
        $coreApi = class_exists(\xdecaro\Core\Integration\EntityReference::class)
            && class_exists(\xdecaro\Core\Integration\RelationReference::class)
            && class_exists(\xdecaro\Core\Asset\AssetService::class);

        return [
            'version' => $this->getInstalledVersion(),
            'joomla_version' => (new Version())->getShortVersion(),
            'php_version' => PHP_VERSION,
            'core_version' => $coreVersion,
            'core_compatible' => $coreVersion !== '' && version_compare($coreVersion, '1.3.0', '>='),
            'core_api' => $coreApi,
            'documents_table' => isset($tables[$documentsTable]),
            'relations_table' => isset($tables[$relationsTable]),
            'versions_table' => isset($tables[$versionsTable]),
            'audit_table' => isset($tables[$auditTable]),
            'document_count' => $documentCount,
            'version_count' => $versionCount,
            'expiring_count' => $expiringCount,
            'storage_ready' => $storage->isReady(),
            'storage_location' => 'private-outside-web-root',
            'component_id' => 'com_decarodocuments',
            'package_id' => 'pkg_decarodocuments',
        ];
    }

    private function countTable(string $physicalName, string $logicalName, array $tables): int
    {
        if (!isset($tables[$physicalName])) {
            return 0;
        }

        $db = $this->getDatabase();

        return (int) $db->setQuery(
            $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName($logicalName))
        )->loadResult();
    }

    private function getInstalledVersion(): string
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('manifest_cache'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_decarodocuments'));

        $manifest = json_decode((string) $db->setQuery($query, 0, 1)->loadResult(), true);

        return is_array($manifest) && !empty($manifest['version'])
            ? (string) $manifest['version']
            : '—';
    }
}

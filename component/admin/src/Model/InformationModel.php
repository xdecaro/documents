<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Model;

defined('_JEXEC') or die;

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
        $count = 0;

        if (isset($tables[$documentsTable])) {
            $count = (int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__decarodocuments_documents')))->loadResult();
        }

        $storage = new StorageService();
        $coreVersion = class_exists(\xdecaro\Core\Version::class) ? (string) \xdecaro\Core\Version::VERSION : '';
        $coreApi = class_exists(\xdecaro\Core\Integration\EntityReference::class)
            && class_exists(\xdecaro\Core\Integration\RelationReference::class)
            && class_exists(\xdecaro\Core\Asset\AssetService::class);

        return [
            'version' => '1.1.0',
            'joomla_version' => (new Version())->getShortVersion(),
            'php_version' => PHP_VERSION,
            'core_version' => $coreVersion,
            'core_compatible' => $coreVersion !== '' && version_compare($coreVersion, '1.3.0', '>='),
            'core_api' => $coreApi,
            'documents_table' => isset($tables[$documentsTable]),
            'relations_table' => isset($tables[$relationsTable]),
            'document_count' => $count,
            'storage_ready' => $storage->isReady(),
            'storage_location' => 'private-outside-web-root',
            'component_id' => 'com_decarodocuments',
            'package_id' => 'pkg_decarodocuments',
        ];
    }
}

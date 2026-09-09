<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

/**
 * Documents-owned analytics data source.
 *
 * This service is the only supported analytics read surface for Documents.
 * External consumers must not query Documents tables directly.
 */
final class AnalyticsSourceService
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /** @return array<int,array<string,mixed>> */
    public function getMetrics(): array
    {
        $this->assertAuthorised();

        return [
            ['key' => 'documents.total', 'label' => 'Documents'],
            ['key' => 'documents.published', 'label' => 'Published documents'],
            ['key' => 'documents.storage_bytes', 'label' => 'Stored bytes', 'unit' => 'bytes'],
            ['key' => 'documents.relations', 'label' => 'Document relations'],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function getDatasets(): array
    {
        $this->assertAuthorised();

        return [
            ['key' => 'documents.by_mime_type', 'label' => 'Documents by MIME type'],
            ['key' => 'documents.recent', 'label' => 'Recent documents'],
        ];
    }

    /** @return array<string,mixed> */
    public function getMetric(string $key, array $context = []): array
    {
        $this->assertAuthorised();

        switch ($key) {
            case 'documents.total':
                return $this->countMetric('#__decarodocuments_documents', 'Documents');

            case 'documents.published':
                $query = $this->db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($this->db->quoteName('#__decarodocuments_documents'))
                    ->where($this->db->quoteName('state') . ' = 1');

                return ['value' => (int) $this->db->setQuery($query)->loadResult(), 'label' => 'Published documents'];

            case 'documents.storage_bytes':
                $query = $this->db->getQuery(true)
                    ->select('COALESCE(SUM(' . $this->db->quoteName('file_size') . '), 0)')
                    ->from($this->db->quoteName('#__decarodocuments_documents'))
                    ->where($this->db->quoteName('state') . ' >= 0');

                return [
                    'value' => (int) $this->db->setQuery($query)->loadResult(),
                    'label' => 'Stored bytes',
                    'unit' => 'bytes',
                ];

            case 'documents.relations':
                return $this->countMetric('#__decarodocuments_relations', 'Document relations');
        }

        throw new \InvalidArgumentException('Unknown Documents analytics metric: ' . $key);
    }

    /** @return array<int,array<string,mixed>> */
    public function getDataset(string $key, array $context = []): array
    {
        $this->assertAuthorised();
        $limit = max(1, min(500, (int) ($context['limit'] ?? 100)));

        if ($key === 'documents.by_mime_type') {
            $query = $this->db->getQuery(true)
                ->select([
                    'COALESCE(NULLIF(' . $this->db->quoteName('mime_type') . ", ''), 'unknown') AS mime_type",
                    'COUNT(*) AS total',
                    'COALESCE(SUM(' . $this->db->quoteName('file_size') . '), 0) AS bytes',
                ])
                ->from($this->db->quoteName('#__decarodocuments_documents'))
                ->where($this->db->quoteName('state') . ' >= 0')
                ->group($this->db->quoteName('mime_type'))
                ->order('total DESC');

            return array_values((array) $this->db->setQuery($query, 0, $limit)->loadAssocList());
        }

        if ($key === 'documents.recent') {
            $query = $this->db->getQuery(true)
                ->select([
                    $this->db->quoteName('id'),
                    $this->db->quoteName('title'),
                    $this->db->quoteName('mime_type'),
                    $this->db->quoteName('file_size'),
                    $this->db->quoteName('state'),
                    $this->db->quoteName('created'),
                ])
                ->from($this->db->quoteName('#__decarodocuments_documents'))
                ->where($this->db->quoteName('state') . ' >= 0')
                ->order($this->db->quoteName('created') . ' DESC, ' . $this->db->quoteName('id') . ' DESC');

            $rows = (array) $this->db->setQuery($query, 0, $limit)->loadAssocList();
            foreach ($rows as &$row) {
                $row['id'] = (int) $row['id'];
                $row['file_size'] = (int) $row['file_size'];
                $row['state'] = (int) $row['state'];
            }
            unset($row);

            return array_values($rows);
        }

        throw new \InvalidArgumentException('Unknown Documents analytics dataset: ' . $key);
    }

    /** @return array<string,mixed> */
    private function countMetric(string $table, string $label): array
    {
        $query = $this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName($table));

        return ['value' => (int) $this->db->setQuery($query)->loadResult(), 'label' => $label];
    }

    private function assertAuthorised(): void
    {
        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.manage', 'com_decarodocuments')
            && !$user->authorise('core.edit', 'com_decarodocuments')
            && !$user->authorise('core.admin', 'com_decarodocuments')) {
            throw new RuntimeException('Not authorised to read Documents analytics.', 403);
        }
    }
}

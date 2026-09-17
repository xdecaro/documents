<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;
use Xdecaro\Component\Decarodocuments\Administrator\Service\AuditService;
use Xdecaro\Component\Decarodocuments\Administrator\Service\StorageService;

final class DocumentModel extends AdminModel
{
    public function getTable($name = 'Document', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_decarodocuments.document', 'document', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData(): array
    {
        $app = Factory::getApplication();
        $data = $app->getUserState('com_decarodocuments.edit.document.data', []);

        if (empty($data)) {
            $item = $this->getItem();
            $data = $item ? (array) $item : [];
        }

        return $data;
    }

    /** @return array<int,object> */
    public function getVersions(): array
    {
        $id = (int) $this->getState($this->getName() . '.id');

        if ($id <= 0) {
            $id = Factory::getApplication()->input->getInt('id');
        }

        if ($id <= 0) {
            return [];
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('v.id'),
                $db->quoteName('v.version_number'),
                $db->quoteName('v.original_name'),
                $db->quoteName('v.mime_type'),
                $db->quoteName('v.file_size'),
                $db->quoteName('v.sha256'),
                $db->quoteName('v.note'),
                $db->quoteName('v.is_current'),
                $db->quoteName('v.created'),
                $db->quoteName('v.created_by'),
                $db->quoteName('u.name', 'created_by_name'),
            ])
            ->from($db->quoteName('#__decarodocuments_versions', 'v'))
            ->leftJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('v.created_by'))
            ->where($db->quoteName('v.document_id') . ' = :documentId')
            ->bind(':documentId', $id, ParameterType::INTEGER)
            ->order($db->quoteName('v.version_number') . ' DESC');

        return (array) $db->setQuery($query)->loadObjectList();
    }

    public function getVersion(int $versionId): ?object
    {
        if ($versionId <= 0) {
            return null;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('v.id'),
                $db->quoteName('v.document_id'),
                $db->quoteName('v.version_number'),
                $db->quoteName('v.original_name'),
                $db->quoteName('v.stored_name'),
                $db->quoteName('v.mime_type'),
                $db->quoteName('v.file_size'),
                $db->quoteName('v.sha256'),
                $db->quoteName('v.is_current'),
                $db->quoteName('d.uuid', 'document_uuid'),
                $db->quoteName('d.access'),
            ])
            ->from($db->quoteName('#__decarodocuments_versions', 'v'))
            ->join('INNER', $db->quoteName('#__decarodocuments_documents', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('v.document_id'))
            ->where($db->quoteName('v.id') . ' = :versionId')
            ->bind(':versionId', $versionId, ParameterType::INTEGER);

        $row = $db->setQuery($query, 0, 1)->loadObject();

        return $row ?: null;
    }

    public function save($data): bool
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $now = Factory::getDate()->toSql();

        if (!$user->authorise('core.edit.state', 'com_decarodocuments')) {
            if ((int) ($data['id'] ?? 0) > 0) {
                $current = $this->getItem((int) $data['id']);
                $data['state'] = (int) ($current->state ?? 0);
            } else {
                $data['state'] = 0;
            }
        }

        foreach (['document_date', 'valid_from', 'expires_at'] as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $data[$field] = null;
            }
        }

        $versionNote = trim((string) ($data['version_note'] ?? ''));
        unset($data['version_note']);

        if (!$user->authorise('core.admin', 'com_decarodocuments')) {
            $requestedAccess = (int) ($data['access'] ?? 0);
            if ($requestedAccess <= 0 || !in_array($requestedAccess, $user->getAuthorisedViewLevels(), true)) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));
                return false;
            }
        }

        $id = (int) ($data['id'] ?? 0);
        $table = $this->getTable();
        $currentVersion = 0;

        if ($id > 0 && $table->load($id)) {
            if (!$user->authorise('core.admin', 'com_decarodocuments')
                && !in_array((int) $table->access, $user->getAuthorisedViewLevels(), true)) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));
                return false;
            }
            $currentVersion = max(1, (int) ($table->current_version ?? 1));

            if (empty($data['uuid'])) {
                $data['uuid'] = (string) $table->uuid;
            }

            $data['created'] = (string) $table->created;
            $data['created_by'] = (int) $table->created_by;
            $data['modified'] = $now;
            $data['modified_by'] = (int) $user->id;
        } else {
            $data['uuid'] = $this->createUuidV4();
            $data['created'] = $now;
            $data['created_by'] = (int) $user->id;
            $data['modified'] = null;
            $data['modified_by'] = 0;
            $data['current_version'] = 1;
        }

        $files = (array) $app->input->files->get('jform', [], 'array');
        $upload = isset($files['upload']) && is_array($files['upload']) ? $files['upload'] : null;
        $hasUpload = $upload !== null && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($id === 0 && !$hasUpload) {
            $this->setError(Text::_('COM_DECARODOCUMENTS_ERROR_FILE_REQUIRED'));
            return false;
        }

        $newStoredName = '';
        $versionUuid = '';
        $versionNumber = $id > 0 ? $currentVersion : 1;

        if ($hasUpload) {
            try {
                $versionUuid = $id === 0 ? (string) $data['uuid'] : $this->createUuidV4();
                $stored = (new StorageService())->storeUploadedFile($upload, $versionUuid);
                $data = array_merge($data, $stored);
                $newStoredName = (string) $stored['stored_name'];
                $versionNumber = $id > 0 ? $currentVersion + 1 : 1;
                $data['current_version'] = $versionNumber;
            } catch (\Throwable $e) {
                $this->setError($e->getMessage());
                return false;
            }
        } elseif ($id > 0) {
            foreach (['original_name', 'stored_name', 'mime_type', 'file_size', 'sha256', 'current_version'] as $field) {
                $data[$field] = $table->{$field};
            }
        }

        $db = $this->getDatabase();
        $db->transactionStart();

        try {
            if (!parent::save($data)) {
                $db->transactionRollback();

                if ($newStoredName !== '') {
                    $this->removeNewBlob($newStoredName);
                }

                return false;
            }

            $savedId = (int) $this->getState($this->getName() . '.id');
            if ($savedId <= 0) {
                throw new \RuntimeException(Text::_('COM_DECARODOCUMENTS_ERROR_SAVE_ID'));
            }

            $versionId = null;

            if ($hasUpload) {
                $clearCurrent = $db->getQuery(true)
                    ->update($db->quoteName('#__decarodocuments_versions'))
                    ->set($db->quoteName('is_current') . ' = 0')
                    ->where($db->quoteName('document_id') . ' = :documentId')
                    ->bind(':documentId', $savedId, ParameterType::INTEGER);
                $db->setQuery($clearCurrent)->execute();

                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__decarodocuments_versions'))
                    ->columns([
                        $db->quoteName('document_id'),
                        $db->quoteName('version_number'),
                        $db->quoteName('version_uuid'),
                        $db->quoteName('original_name'),
                        $db->quoteName('stored_name'),
                        $db->quoteName('mime_type'),
                        $db->quoteName('file_size'),
                        $db->quoteName('sha256'),
                        $db->quoteName('note'),
                        $db->quoteName('is_current'),
                        $db->quoteName('created'),
                        $db->quoteName('created_by'),
                    ])
                    ->values(':documentId, :versionNumber, :versionUuid, :originalName, :storedName, :mimeType, :fileSize, :sha256, :note, 1, :created, :createdBy')
                    ->bind(':documentId', $savedId, ParameterType::INTEGER)
                    ->bind(':versionNumber', $versionNumber, ParameterType::INTEGER)
                    ->bind(':versionUuid', $versionUuid)
                    ->bind(':originalName', $data['original_name'])
                    ->bind(':storedName', $data['stored_name'])
                    ->bind(':mimeType', $data['mime_type'])
                    ->bind(':fileSize', $data['file_size'], ParameterType::INTEGER)
                    ->bind(':sha256', $data['sha256'])
                    ->bind(':note', $versionNote)
                    ->bind(':created', $now)
                    ->bind(':createdBy', $user->id, ParameterType::INTEGER);
                $db->setQuery($query)->execute();
                $versionId = (int) $db->insertid();
            }

            (new AuditService($db))->record(
                $savedId,
                (string) $data['uuid'],
                $id === 0 ? 'created' : ($hasUpload ? 'new_version' : 'updated'),
                $versionId,
                $hasUpload ? ['version' => $versionNumber] : []
            );

            $db->transactionCommit();
        } catch (\Throwable $e) {
            $db->transactionRollback();

            if ($newStoredName !== '') {
                $this->removeNewBlob($newStoredName);
            }

            $this->setError($e->getMessage());
            return false;
        }

        return true;
    }

    public function delete(&$pks): bool
    {
        $pks = array_values(array_filter(array_map('intval', (array) $pks), static fn (int $id): bool => $id > 0));

        if ($pks === []) {
            return true;
        }

        $db = $this->getDatabase();
        $table = $this->getTable();
        $documents = [];
        $storedNames = [];

        foreach ($pks as $id) {
            if ($table->load($id)) {
                $documents[$id] = (string) $table->uuid;

                if ((string) $table->stored_name !== '') {
                    $storedNames[] = (string) $table->stored_name;
                }
            }
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName('stored_name'))
            ->from($db->quoteName('#__decarodocuments_versions'))
            ->where($db->quoteName('document_id') . ' IN (' . implode(',', $pks) . ')');
        $storedNames = array_values(array_unique(array_merge($storedNames, (array) $db->setQuery($query)->loadColumn())));

        if (!parent::delete($pks)) {
            return false;
        }

        $audit = new AuditService($db);
        foreach ($documents as $documentId => $uuid) {
            try {
                $audit->record($documentId, $uuid, 'deleted');
            } catch (\Throwable $e) {
                Log::add('Documents audit warning: ' . $e->getMessage(), Log::WARNING, 'com_decarodocuments');
            }
        }

        $storage = new StorageService();
        foreach ($storedNames as $storedName) {
            try {
                $storage->delete((string) $storedName);
            } catch (\Throwable $e) {
                Log::add('Documents file cleanup warning: ' . $e->getMessage(), Log::WARNING, 'com_decarodocuments');
            }
        }

        return true;
    }

    protected function canDelete($record): bool
    {
        $identity = Factory::getApplication()->getIdentity();

        if (!$identity->authorise('core.delete', 'com_decarodocuments')) {
            return false;
        }

        return $identity->authorise('core.admin', 'com_decarodocuments')
            || in_array((int) ($record->access ?? 0), $identity->getAuthorisedViewLevels(), true);
    }

    protected function canEditState($record): bool
    {
        $identity = Factory::getApplication()->getIdentity();

        if (!$identity->authorise('core.edit.state', 'com_decarodocuments')) {
            return false;
        }

        return $identity->authorise('core.admin', 'com_decarodocuments')
            || in_array((int) ($record->access ?? 0), $identity->getAuthorisedViewLevels(), true);
    }

    private function removeNewBlob(string $storedName): void
    {
        try {
            (new StorageService())->delete($storedName);
        } catch (\Throwable $e) {
            Log::add('Documents rollback cleanup warning: ' . $e->getMessage(), Log::WARNING, 'com_decarodocuments');
        }
    }

    private function createUuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
    }
}

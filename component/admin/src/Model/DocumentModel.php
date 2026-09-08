<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\AdminModel;
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

        $id = (int) ($data['id'] ?? 0);
        $table = $this->getTable();
        $oldStoredName = '';

        if ($id > 0 && $table->load($id)) {
            $oldStoredName = (string) $table->stored_name;
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
        }

        $files = (array) $app->input->files->get('jform', [], 'array');
        $upload = isset($files['upload']) && is_array($files['upload']) ? $files['upload'] : null;
        $hasUpload = $upload !== null && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($id === 0 && !$hasUpload) {
            $this->setError('A file is required when creating a document.');
            return false;
        }

        $newStoredName = '';
        if ($hasUpload) {
            try {
                $storage = new StorageService();
                $stored = $storage->storeUploadedFile($upload, (string) $data['uuid']);
                $data = array_merge($data, $stored);
                $newStoredName = (string) $stored['stored_name'];
            } catch (\Throwable $e) {
                $this->setError($e->getMessage());
                return false;
            }
        } elseif ($id > 0) {
            foreach (['original_name', 'stored_name', 'mime_type', 'file_size', 'sha256'] as $field) {
                $data[$field] = $table->{$field};
            }
        }

        if (!parent::save($data)) {
            if ($newStoredName !== '') {
                try {
                    (new StorageService())->delete($newStoredName);
                } catch (\Throwable) {
                }
            }
            return false;
        }

        if ($newStoredName !== '' && $oldStoredName !== '' && $oldStoredName !== $newStoredName) {
            try {
                (new StorageService())->delete($oldStoredName);
            } catch (\Throwable $e) {
                Log::add('Old Documents file cleanup warning: ' . $e->getMessage(), Log::WARNING, 'com_decarodocuments');
            }
        }

        return true;
    }

    public function delete(&$pks): bool
    {
        $pks = array_values(array_filter(array_map('intval', (array) $pks), static fn (int $id): bool => $id > 0));
        if ($pks === []) {
            return true;
        }

        $table = $this->getTable();
        $storedNames = [];
        foreach ($pks as $id) {
            if ($table->load($id)) {
                $storedNames[] = (string) $table->stored_name;
            }
        }

        if (!parent::delete($pks)) {
            return false;
        }

        $storage = new StorageService();
        foreach ($storedNames as $storedName) {
            try {
                $storage->delete($storedName);
            } catch (\Throwable $e) {
                Log::add('Documents file cleanup warning: ' . $e->getMessage(), Log::WARNING, 'com_decarodocuments');
            }
        }
        return true;
    }

    protected function canDelete($record): bool
    {
        return Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_decarodocuments');
    }

    protected function canEditState($record): bool
    {
        return Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_decarodocuments');
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

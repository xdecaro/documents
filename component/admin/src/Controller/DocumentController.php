<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\Database\DatabaseInterface;
use RuntimeException;
use Xdecaro\Component\Decarodocuments\Administrator\Service\AuditService;
use Xdecaro\Component\Decarodocuments\Administrator\Service\StorageService;

final class DocumentController extends FormController
{
    public function getModel($name = 'Document', $prefix = 'Administrator', $config = ['ignore_request' => false])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function download(): void
    {
        $app = Factory::getApplication();
        $id = $app->input->getInt('id');

        if ($id <= 0) {
            throw new RuntimeException(Text::_('COM_DECARODOCUMENTS_ERROR_INVALID_DOCUMENT'), 400);
        }

        $item = $this->getModel()->getItem($id);
        if (!$item || empty($item->stored_name)) {
            throw new RuntimeException(Text::_('COM_DECARODOCUMENTS_ERROR_FILE_NOT_FOUND'), 404);
        }

        $this->assertDownloadAccess((int) $item->access);
        $this->recordAudit($id, (string) $item->uuid, 'downloaded', null, ['version' => (int) ($item->current_version ?? 1)]);
        $this->streamFile((string) $item->stored_name, (string) $item->original_name, (string) $item->mime_type);
    }

    public function downloadVersion(): void
    {
        $app = Factory::getApplication();
        $id = $app->input->getInt('id');
        $versionId = $app->input->getInt('version_id');

        if ($id <= 0 || $versionId <= 0) {
            throw new RuntimeException(Text::_('COM_DECARODOCUMENTS_ERROR_INVALID_VERSION'), 400);
        }

        $version = $this->getModel()->getVersion($versionId);
        if (!$version || (int) $version->document_id !== $id || empty($version->stored_name)) {
            throw new RuntimeException(Text::_('COM_DECARODOCUMENTS_ERROR_FILE_NOT_FOUND'), 404);
        }

        $this->assertDownloadAccess((int) $version->access);
        $this->recordAudit(
            $id,
            (string) $version->document_uuid,
            'version_downloaded',
            $versionId,
            ['version' => (int) $version->version_number]
        );
        $this->streamFile((string) $version->stored_name, (string) $version->original_name, (string) $version->mime_type);
    }

    private function assertDownloadAccess(int $access): void
    {
        $user = Factory::getApplication()->getIdentity();

        if (!$user->authorise('core.manage', 'com_decarodocuments') && !$user->authorise('core.edit', 'com_decarodocuments')) {
            throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        if (!$user->authorise('core.admin', 'com_decarodocuments')
            && !in_array($access, $user->getAuthorisedViewLevels(), true)) {
            throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }

    private function streamFile(string $storedName, string $originalName, string $mimeType): void
    {
        $app = Factory::getApplication();
        $path = (new StorageService())->resolve($storedName);
        $filename = $this->safeDownloadName($originalName);
        $mime = preg_match('/^[a-z0-9.+-]+\/[a-z0-9.+-]+$/iD', $mimeType) ? $mimeType : 'application/octet-stream';

        $app->setHeader('Content-Type', $mime, true);
        $app->setHeader('Content-Length', (string) filesize($path), true);
        $app->setHeader('Content-Disposition', "attachment; filename*=UTF-8''" . rawurlencode($filename), true);
        $app->setHeader('X-Content-Type-Options', 'nosniff', true);
        $app->setHeader('Cache-Control', 'private, no-store, max-age=0', true);
        $app->setHeader('Pragma', 'no-cache', true);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        readfile($path);
        $app->close();
    }

    private function recordAudit(int $documentId, string $uuid, string $action, ?int $versionId, array $context): void
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            (new AuditService($db))->record($documentId, $uuid, $action, $versionId, $context);
        } catch (\Throwable $e) {
            Log::add('Documents audit warning: ' . $e->getMessage(), Log::WARNING, 'com_decarodocuments');
        }
    }

    private function safeDownloadName(string $name): string
    {
        $name = trim(str_replace(["\0", '/', '\\'], '', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?: 'document';

        return $name !== '' ? $name : 'document';
    }
}

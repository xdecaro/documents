<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use RuntimeException;
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
        $user = $app->getIdentity();

        if (!$user->authorise('core.manage', 'com_decarodocuments') && !$user->authorise('core.edit', 'com_decarodocuments')) {
            throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $id = $app->input->getInt('id');
        if ($id <= 0) {
            throw new RuntimeException(Text::_('COM_DECARODOCUMENTS_ERROR_INVALID_DOCUMENT'), 400);
        }

        $item = $this->getModel()->getItem($id);
        if (!$item || empty($item->stored_name)) {
            throw new RuntimeException(Text::_('COM_DECARODOCUMENTS_ERROR_FILE_NOT_FOUND'), 404);
        }

        if (!$user->authorise('core.admin', 'com_decarodocuments') && !in_array((int) $item->access, $user->getAuthorisedViewLevels(), true)) {
            throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $path = (new StorageService())->resolve((string) $item->stored_name);
        $filename = $this->safeDownloadName((string) $item->original_name);
        $mime = preg_match('/^[a-z0-9.+-]+\/[a-z0-9.+-]+$/iD', (string) $item->mime_type) ? (string) $item->mime_type : 'application/octet-stream';

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

    private function safeDownloadName(string $name): string
    {
        $name = trim(str_replace(["\0", '/', '\\'], '', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?: 'document';
        return $name !== '' ? $name : 'document';
    }
}

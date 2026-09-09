<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class PkgDecarodocumentsInstallerScript
{
    private const MINIMUM_CORE = '1.3.0';

    public function preflight($type, $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        $version = $this->getInstalledCoreVersion();
        if ($version !== '' && version_compare($version, self::MINIMUM_CORE, '>=')) {
            return true;
        }

        Factory::getApplication()->enqueueMessage(
            'Documents by xdecaro requires Core by xdecaro ' . self::MINIMUM_CORE . ' or later. Install or update Core before installing Documents.',
            'error'
        );
        return false;
    }

    public function postflight($type, $parent): void
    {
        if ($type !== 'install') {
            return;
        }

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $enabled = 1;
            $folder = 'xdecaroanalytics';
            $element = 'decarodocuments';
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = :enabled')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = :folder')
                ->where($db->quoteName('element') . ' = :element')
                ->bind(':enabled', $enabled, \Joomla\Database\ParameterType::INTEGER)
                ->bind(':folder', $folder)
                ->bind(':element', $element);
            $db->setQuery($query)->execute();
        } catch (\Throwable $exception) {
            Factory::getApplication()->enqueueMessage(
                'Documents was installed, but its optional Analytics provider could not be enabled automatically.',
                'warning'
            );
        }
    }

    private function getInstalledCoreVersion(): string
    {
        if (class_exists(\xdecaro\Core\Version::class)) {
            return trim((string) \xdecaro\Core\Version::VERSION);
        }

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('package'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('pkg_xdecarocore'));
            $cache = (string) $db->setQuery($query, 0, 1)->loadResult();
            $manifest = json_decode($cache, true);
            return is_array($manifest) ? trim((string) ($manifest['version'] ?? '')) : '';
        } catch (\Throwable $exception) {
            return '';
        }
    }
}

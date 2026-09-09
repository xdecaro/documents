<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Package dependency guard plus first-install activation of optional adapters. */
final class pkg_decarodocumentsInstallerScript
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
        // Existing administrator choices are never overwritten on package updates.
        if (!in_array((string) $type, ['install', 'discover_install'], true)) {
            return;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $enabled = 1;
            $pluginType = 'plugin';
            $folder = 'xdecaroanalytics';
            $element = 'decarodocuments';
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = :enabled')
                ->where($db->quoteName('type') . ' = :type')
                ->where($db->quoteName('folder') . ' = :folder')
                ->where($db->quoteName('element') . ' = :element')
                ->bind(':enabled', $enabled, ParameterType::INTEGER)
                ->bind(':type', $pluginType)
                ->bind(':folder', $folder)
                ->bind(':element', $element);
            $db->setQuery($query)->execute();
        } catch (\Throwable $exception) {
            Log::add('Documents optional Analytics plugin could not be enabled automatically: ' . $exception->getMessage(), Log::WARNING, 'com_decarodocuments.integration');
        }
    }

    private function getInstalledCoreVersion(): string
    {
        if (class_exists(\xdecaro\Core\Version::class)) {
            return trim((string) \xdecaro\Core\Version::VERSION);
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('package'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('pkg_xdecarocore'));
            $cache = (string) $db->setQuery($query, 0, 1)->loadResult();
            $manifest = json_decode($cache, true);
            return is_array($manifest) ? trim((string) ($manifest['version'] ?? '')) : '';
        } catch (\Throwable) {
            return '';
        }
    }
}

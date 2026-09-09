<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * The legacy installer class name must match the package element exactly.
 * Joomla resolves pkg_decarodocuments to pkg_decarodocumentsInstallerScript.
 */
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

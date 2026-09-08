<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use RuntimeException;

final class CoreUiHelper
{
    public static function useComponents(): void
    {
        if (!class_exists(\xdecaro\Core\Asset\AssetService::class)
            || !class_exists(\xdecaro\Core\Version::class)
            || version_compare((string) \xdecaro\Core\Version::VERSION, '1.3.0', '<')) {
            throw new RuntimeException('Documents by xdecaro requires Core by xdecaro 1.3.0 or later.');
        }

        $assets = new \xdecaro\Core\Asset\AssetService();
        $webAssets = Factory::getApplication()->getDocument()->getWebAssetManager();

        if (!$assets->useComponents($webAssets)) {
            throw new RuntimeException('Core by xdecaro Web Asset Manager assets are unavailable.');
        }
    }
}

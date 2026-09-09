<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Xdecaro\Plugin\Xdecaroanalytics\Decarodocuments\Extension\Decarodocuments;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Decarodocuments::class, static function (): Decarodocuments {
                return new Decarodocuments((array) PluginHelper::getPlugin('xdecaroanalytics', 'decarodocuments'));
            })
        );
    }
};

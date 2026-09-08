<?php
/**
 * @package     com_decarodocuments
 * @subpackage  Administrator
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Xdecaro\Component\Decarodocuments\Administrator\Service\CoreIntegrationService;
use Xdecaro\Component\Decarodocuments\Administrator\Service\RelationService;
use Xdecaro\Component\Decarodocuments\Administrator\Service\StorageService;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Xdecaro\\Component\\Decarodocuments'));
        $container->registerServiceProvider(new MVCFactory('\\Xdecaro\\Component\\Decarodocuments'));

        $container->share(StorageService::class, static fn (): StorageService => new StorageService());
        $container->share(CoreIntegrationService::class, static fn (): CoreIntegrationService => new CoreIntegrationService());
        $container->share(
            RelationService::class,
            static fn (Container $container): RelationService => new RelationService($container->get(DatabaseInterface::class))
        );

        $container->set(
            ComponentInterface::class,
            static function (Container $container): ComponentInterface {
                $component = new MVCComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));

                return $component;
            }
        );
    }
};

<?php
namespace Xdecaro\Plugin\Xdecaroanalytics\Decarodocuments\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Throwable;
use xdecaro\Component\Analytics\Administrator\Event\RegisterProvidersEvent;
use Xdecaro\Component\Decarodocuments\Administrator\Extension\DecarodocumentsComponent;
use Xdecaro\Plugin\Xdecaroanalytics\Decarodocuments\Provider\DocumentsProvider;

final class Decarodocuments extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [RegisterProvidersEvent::NAME => 'onRegisterProviders'];
    }

    public function onRegisterProviders(RegisterProvidersEvent $event): void
    {
        try {
            $component = Factory::getApplication()->bootComponent('com_decarodocuments');
            if (!$component instanceof DecarodocumentsComponent) {
                return;
            }

            $event->getRegistry()->register(new DocumentsProvider($component->getAnalyticsSourceService()));
        } catch (Throwable $exception) {
            Factory::getApplication()->getLogger()->warning(
                'Documents Analytics provider was not registered: ' . $exception->getMessage(),
                ['category' => 'plg_xdecaroanalytics_decarodocuments']
            );
        }
    }
}

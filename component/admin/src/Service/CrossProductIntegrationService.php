<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Throwable;

/**
 * Optional bridge from Documents to shared xdecaro services.
 *
 * It only calls documented public component services. No external tables are
 * read or written and absence of an optional product never breaks Documents.
 */
final class CrossProductIntegrationService
{
    public function notificationsAvailable(): bool
    {
        return ComponentHelper::isEnabled('com_xdecaronotifications');
    }

    public function tasksAvailable(): bool
    {
        return ComponentHelper::isEnabled('com_xdecarotasks');
    }

    public function publishNotification(array $notification): ?int
    {
        if (!$this->notificationsAvailable()) {
            return null;
        }

        $notification['source_component'] = CoreIntegrationService::COMPONENT;

        try {
            $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');
            if (!is_object($component) || !method_exists($component, 'getNotificationService')) {
                return null;
            }

            $service = $component->getNotificationService();
            return is_object($service) && method_exists($service, 'create')
                ? (int) $service->create($notification)
                : null;
        } catch (Throwable $exception) {
            Log::add(
                'Documents could not publish an optional notification: ' . $exception->getMessage(),
                Log::WARNING,
                'com_decarodocuments.integration'
            );
            return null;
        }
    }

    public function createTask(array $task, ?array $assignee = null, int $actorUserId = 0): ?int
    {
        if (!$this->tasksAvailable()) {
            return null;
        }

        $task['source_component'] = CoreIntegrationService::COMPONENT;

        try {
            $component = Factory::getApplication()->bootComponent('com_xdecarotasks');
            if (!is_object($component) || !method_exists($component, 'getTaskService')) {
                return null;
            }

            $service = $component->getTaskService();
            if (!is_object($service) || !method_exists($service, 'create')) {
                return null;
            }

            $taskId = (int) $service->create($task, max(0, $actorUserId));

            if ($taskId > 0 && is_array($assignee) && method_exists($service, 'assign')) {
                $type = trim((string) ($assignee['type'] ?? ''));
                $id = trim((string) ($assignee['id'] ?? ''));
                if ($type !== '' && $id !== '') {
                    $service->assign($taskId, $type, $id, max(0, $actorUserId), true);
                }
            }

            return $taskId > 0 ? $taskId : null;
        } catch (Throwable $exception) {
            Log::add(
                'Documents could not create an optional task: ' . $exception->getMessage(),
                Log::WARNING,
                'com_decarodocuments.integration'
            );
            return null;
        }
    }
}

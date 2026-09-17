<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;

final class AuditService
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    public function record(
        ?int $documentId,
        string $documentUuid,
        string $action,
        ?int $versionId = null,
        array $context = []
    ): void {
        $documentUuid = strtolower(trim($documentUuid));
        $action = strtolower(trim($action));

        if (!preg_match('/^[a-f0-9-]{36}$/D', $documentUuid)) {
            throw new RuntimeException('Invalid audit document UUID.');
        }

        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $action)) {
            throw new RuntimeException('Invalid audit action.');
        }

        $identity = Factory::getApplication()->getIdentity();
        $actor = $identity ? (int) $identity->id : 0;
        $created = Factory::getDate()->toSql();
        $json = $context === [] ? null : json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (is_string($json) && strlen($json) > 8000) {
            $json = json_encode(['truncated' => true], JSON_UNESCAPED_SLASHES);
        }

        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__decarodocuments_audit'))
            ->columns([
                $this->db->quoteName('document_id'),
                $this->db->quoteName('document_uuid'),
                $this->db->quoteName('version_id'),
                $this->db->quoteName('action'),
                $this->db->quoteName('actor_user_id'),
                $this->db->quoteName('context_json'),
                $this->db->quoteName('created'),
            ])
            ->values(':documentId, :documentUuid, :versionId, :action, :actor, :contextJson, :created')
            ->bind(':documentId', $documentId, ParameterType::INTEGER)
            ->bind(':documentUuid', $documentUuid)
            ->bind(':versionId', $versionId, ParameterType::INTEGER)
            ->bind(':action', $action)
            ->bind(':actor', $actor, ParameterType::INTEGER)
            ->bind(':contextJson', $json)
            ->bind(':created', $created);

        $this->db->setQuery($query)->execute();
    }
}

<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;
use Throwable;
use xdecaro\Core\Integration\EntityReference;
use xdecaro\Core\Integration\RelationReference;

/**
 * Public Documents relation API for cross-product integrations.
 *
 * Documents owns persistence and authorization. Core only supplies immutable
 * references; knowing a reference never grants access to the related document.
 */
final class RelationService
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    public function attach(RelationReference $relation): void
    {
        $this->assertPermission('core.edit');
        [$documentId, $target, $type] = $this->normaliseRelation($relation);
        $this->assertDocumentExists($documentId);

        if ($this->relationExists($documentId, $target, $type)) {
            return;
        }

        $query = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__decarodocuments_relations'))
            ->columns([
                $this->db->quoteName('document_id'),
                $this->db->quoteName('target_component'),
                $this->db->quoteName('target_entity'),
                $this->db->quoteName('target_id'),
                $this->db->quoteName('relation_type'),
                $this->db->quoteName('created'),
            ])
            ->values(':documentId, :component, :entity, :targetId, :relationType, :created')
            ->bind(':documentId', $documentId, ParameterType::INTEGER)
            ->bind(':component', $target->getComponent())
            ->bind(':entity', $target->getEntity())
            ->bind(':targetId', $target->getId())
            ->bind(':relationType', $type)
            ->bind(':created', Factory::getDate()->toSql());

        try {
            $this->db->setQuery($query)->execute();
        } catch (Throwable $exception) {
            // The unique key makes attach idempotent even under a concurrent race.
            if ($this->relationExists($documentId, $target, $type)) {
                return;
            }

            throw new RuntimeException('The document relation could not be stored.', 0, $exception);
        }
    }

    public function detach(RelationReference $relation): void
    {
        $this->assertPermission('core.edit');
        [$documentId, $target, $type] = $this->normaliseRelation($relation);

        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__decarodocuments_relations'))
            ->where($this->db->quoteName('document_id') . ' = :documentId')
            ->where($this->db->quoteName('target_component') . ' = :component')
            ->where($this->db->quoteName('target_entity') . ' = :entity')
            ->where($this->db->quoteName('target_id') . ' = :targetId')
            ->where($this->db->quoteName('relation_type') . ' = :relationType')
            ->bind(':documentId', $documentId, ParameterType::INTEGER)
            ->bind(':component', $target->getComponent())
            ->bind(':entity', $target->getEntity())
            ->bind(':targetId', $target->getId())
            ->bind(':relationType', $type);

        $this->db->setQuery($query)->execute();
    }

    /**
     * Returns document metadata related to an external entity.
     *
     * This administrator API deliberately does not return private filesystem
     * paths. File access must still go through Documents authorization/download.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findDocuments(EntityReference $target, ?string $relationType = null): array
    {
        $this->assertPermission('core.manage');
        $relationType = $relationType === null ? null : $this->normaliseRelationType($relationType);

        $query = $this->db->getQuery(true)
            ->select([
                'd.' . $this->db->quoteName('id'),
                'd.' . $this->db->quoteName('uuid'),
                'd.' . $this->db->quoteName('title'),
                'd.' . $this->db->quoteName('description'),
                'd.' . $this->db->quoteName('original_name'),
                'd.' . $this->db->quoteName('mime_type'),
                'd.' . $this->db->quoteName('file_size'),
                'd.' . $this->db->quoteName('sha256'),
                'd.' . $this->db->quoteName('state'),
                'd.' . $this->db->quoteName('access'),
                'r.' . $this->db->quoteName('relation_type'),
                'r.' . $this->db->quoteName('created', 'relation_created'),
            ])
            ->from($this->db->quoteName('#__decarodocuments_relations', 'r'))
            ->join('INNER', $this->db->quoteName('#__decarodocuments_documents', 'd') . ' ON d.' . $this->db->quoteName('id') . ' = r.' . $this->db->quoteName('document_id'))
            ->where('r.' . $this->db->quoteName('target_component') . ' = :component')
            ->where('r.' . $this->db->quoteName('target_entity') . ' = :entity')
            ->where('r.' . $this->db->quoteName('target_id') . ' = :targetId')
            ->bind(':component', $target->getComponent())
            ->bind(':entity', $target->getEntity())
            ->bind(':targetId', $target->getId())
            ->order('d.' . $this->db->quoteName('id') . ' DESC');

        if ($relationType !== null) {
            $query->where('r.' . $this->db->quoteName('relation_type') . ' = :relationType')
                ->bind(':relationType', $relationType);
        }

        return $this->db->setQuery($query)->loadAssocList() ?: [];
    }

    /** @return array{0:int,1:EntityReference,2:string} */
    private function normaliseRelation(RelationReference $relation): array
    {
        $source = $relation->getSource();

        if ($source->getComponent() !== CoreIntegrationService::COMPONENT || $source->getEntity() !== 'document') {
            throw new RuntimeException('The relation source must be a Documents document reference.');
        }

        if (!ctype_digit($source->getId()) || (int) $source->getId() <= 0) {
            throw new RuntimeException('The Documents relation source ID must be a positive integer.');
        }

        return [(int) $source->getId(), $relation->getTarget(), $this->normaliseRelationType($relation->getType())];
    }

    private function normaliseRelationType(string $relationType): string
    {
        $relationType = trim($relationType);

        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $relationType)) {
            throw new RuntimeException('Invalid document relation type.');
        }

        return $relationType;
    }

    private function assertDocumentExists(int $documentId): void
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__decarodocuments_documents'))
            ->where($this->db->quoteName('id') . ' = :documentId')
            ->bind(':documentId', $documentId, ParameterType::INTEGER);

        if ((int) $this->db->setQuery($query)->loadResult() !== 1) {
            throw new RuntimeException('The referenced Documents record does not exist.');
        }
    }

    private function relationExists(int $documentId, EntityReference $target, string $relationType): bool
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__decarodocuments_relations'))
            ->where($this->db->quoteName('document_id') . ' = :documentId')
            ->where($this->db->quoteName('target_component') . ' = :component')
            ->where($this->db->quoteName('target_entity') . ' = :entity')
            ->where($this->db->quoteName('target_id') . ' = :targetId')
            ->where($this->db->quoteName('relation_type') . ' = :relationType')
            ->bind(':documentId', $documentId, ParameterType::INTEGER)
            ->bind(':component', $target->getComponent())
            ->bind(':entity', $target->getEntity())
            ->bind(':targetId', $target->getId())
            ->bind(':relationType', $relationType);

        return (int) $this->db->setQuery($query)->loadResult() > 0;
    }

    private function assertPermission(string $action): void
    {
        $identity = Factory::getApplication()->getIdentity();

        if (!$identity || !$identity->authorise($action, CoreIntegrationService::COMPONENT)) {
            throw new RuntimeException('Not authorised to manage Documents relations.', 403);
        }
    }
}

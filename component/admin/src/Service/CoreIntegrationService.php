<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Service;

defined('_JEXEC') or die;

use xdecaro\Core\Integration\Capability;
use xdecaro\Core\Integration\CapabilityRegistry;
use xdecaro\Core\Integration\EntityReference;
use xdecaro\Core\Integration\RelationReference;

/**
 * Adapter between Documents and stable Core reference/capability contracts.
 *
 * Documents owns relation persistence and authorization. Core only supplies
 * domain-neutral value objects and in-memory capability discovery.
 */
final class CoreIntegrationService
{
    public const COMPONENT = 'com_decarodocuments';
    public const MINIMUM_CORE = '1.3.0';

    public function isAvailable(): bool
    {
        return class_exists(\xdecaro\Core\Version::class)
            && version_compare((string) \xdecaro\Core\Version::VERSION, self::MINIMUM_CORE, '>=')
            && class_exists(EntityReference::class)
            && class_exists(RelationReference::class);
    }

    public function hasCapabilityRegistry(): bool
    {
        return class_exists(Capability::class) && class_exists(CapabilityRegistry::class);
    }

    /** @return array<int,Capability> */
    public function getCapabilities(): array
    {
        if (!$this->hasCapabilityRegistry()) {
            return [];
        }

        return [
            new Capability(self::COMPONENT, 'documents.relations.attach', '1'),
            new Capability(self::COMPONENT, 'documents.relations.detach', '1'),
            new Capability(self::COMPONENT, 'documents.relations.query', '1'),
        ];
    }

    public function registerCapabilities(CapabilityRegistry $registry): void
    {
        $registry->registerMany($this->getCapabilities());
    }

    public function createDocumentReference(int|string $id): EntityReference
    {
        $this->assertAvailable();

        return new EntityReference(self::COMPONENT, 'document', $id);
    }

    public function createRelation(
        int|string $documentId,
        string $targetComponent,
        string $targetEntity,
        int|string $targetId,
        string $relationType
    ): RelationReference {
        $this->assertAvailable();

        return new RelationReference(
            $this->createDocumentReference($documentId),
            new EntityReference($targetComponent, $targetEntity, $targetId),
            $relationType
        );
    }

    private function assertAvailable(): void
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException(
                'Core by xdecaro 1.3.0 or later is required for Documents integration.'
            );
        }
    }
}

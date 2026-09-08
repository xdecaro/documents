<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Service;

defined('_JEXEC') or die;

/**
 * Adapter between Documents and the stable Core by xdecaro reference contract.
 *
 * Documents owns relation persistence and semantics. Core only supplies the
 * generic cross-product reference value objects.
 */
final class CoreIntegrationService
{
    public const COMPONENT = 'com_decarodocuments';
    public const MINIMUM_CORE = '1.3.0';

    public function isAvailable(): bool
    {
        return class_exists(\xdecaro\Core\Version::class)
            && version_compare((string) \xdecaro\Core\Version::VERSION, self::MINIMUM_CORE, '>=')
            && class_exists(\xdecaro\Core\Integration\EntityReference::class)
            && class_exists(\xdecaro\Core\Integration\RelationReference::class);
    }

    public function createDocumentReference(int|string $id): object
    {
        $this->assertAvailable();

        return new \xdecaro\Core\Integration\EntityReference(self::COMPONENT, 'document', $id);
    }

    public function createRelation(
        int|string $documentId,
        string $targetComponent,
        string $targetEntity,
        int|string $targetId,
        string $relationType
    ): object {
        $this->assertAvailable();

        return new \xdecaro\Core\Integration\RelationReference(
            $this->createDocumentReference($documentId),
            new \xdecaro\Core\Integration\EntityReference($targetComponent, $targetEntity, $targetId),
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

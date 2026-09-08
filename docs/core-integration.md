# Core by xdecaro integration

Documents by xdecaro keeps the stable Joomla identifiers introduced in 1.0.0:

- component: `com_decarodocuments`;
- package: `pkg_decarodocuments`;
- PHP namespace: `Xdecaro\Component\Decarodocuments`.

Core by xdecaro `1.3.0+` remains the mandatory runtime dependency. The package installer checks the installed Core version before install/update and fails with a controlled administrator message when the dependency is missing or too old.

Documents consumes only canonical Core contracts:

- `xdecaro\Core\Asset\AssetService` for shared administrator Web Asset Manager styles;
- `.xdecaro-scope` and Core UI primitives;
- `xdecaro\Core\Integration\EntityReference`;
- `xdecaro\Core\Integration\RelationReference`;
- Core 1.4 `Capability` / `CapabilityRegistry` when capability discovery is available.

The deprecated `Xdecaro\Core` compatibility namespace is not used by Documents runtime code. Documents does **not** copy Core CSS or JS into its package.

Documents remains owner of document records and UUIDs, uploaded files/private storage, MIME validation, file hashes and size metadata, access levels/lifecycle state, document relationships, authorization and downloads.

## Public relation API from 1.2.0

`Xdecaro\Component\Decarodocuments\Administrator\Service\RelationService` is the first provider-owned cross-product API.

It supports:

- idempotent attachment of a Documents document to an external `EntityReference` through a Core `RelationReference`;
- detaching the same relation;
- querying document metadata related to an external entity.

The service enforces Documents ACL server-side. Query results never return `stored_name` or filesystem paths. Actual file delivery continues to use Documents' protected download path and access-level checks.

Consumers do not read the Documents DI container directly. They boot the public Joomla component and ask its extension facade for the service:

```php
$documents = Factory::getApplication()->bootComponent('com_decarodocuments');

if (!method_exists($documents, 'getRelationService')) {
    // Documents is absent/older or the public relation API is unavailable.
}

$relations = $documents->getRelationService();
```

This keeps the component container private while providing a stable Joomla-level integration surface. Optional consumers must guard component availability and method existence before using it.

Core 1.4 capability discovery advertises:

- `documents.relations.attach@1`;
- `documents.relations.detach@1`;
- `documents.relations.query@1`.

Capability discovery only means that the provider API exists; it never grants permission to invoke it.

The `#__decarodocuments_relations` table stores target references as `component/entity/id/relation type` values. It deliberately has no foreign keys to Forms, Courses, Competitions, Membership, Events or other independent components. The only foreign key is from a relation to its owning Documents record.

Consumers must use published identifiers and APIs and must not store private filesystem paths or directly depend on Documents internal storage filenames.

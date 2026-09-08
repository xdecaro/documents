# Core by xdecaro integration

Documents by xdecaro keeps the stable Joomla identifiers introduced in 1.0.0:

- component: `com_decarodocuments`;
- package: `pkg_decarodocuments`;
- PHP namespace: `Xdecaro\Component\Decarodocuments`.

From Documents 1.1.0, Core by xdecaro `1.3.0+` is the mandatory runtime dependency. The package installer checks the installed Core version before install/update and fails with a controlled administrator message when the dependency is missing or too old.

Documents consumes only the canonical Core 1.3 namespace:

- `xdecaro\Core\Asset\AssetService` for shared administrator Web Asset Manager styles;
- `.xdecaro-scope` and Core UI primitives;
- `xdecaro\Core\Integration\EntityReference`;
- `xdecaro\Core\Integration\RelationReference`.

The deprecated `Xdecaro\Core` compatibility namespace is not used by Documents 1.1.0. Documents does **not** copy Core CSS or JS into its package.

Documents remains owner of document records and UUIDs, uploaded files/private storage, MIME validation, file hashes and size metadata, access levels/lifecycle state, document relationships, authorization and downloads.

The `#__decarodocuments_relations` table stores target references as `component/entity/id/relation type` values. It deliberately has no foreign keys to Forms, Courses, Competitions, Membership, Events or other independent components. The only foreign key is from a relation to its owning Documents record.

Consumers must use published identifiers and APIs and must not store private filesystem paths or directly depend on Documents internal storage filenames.

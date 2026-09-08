# Core by xdecaro integration

Documents by xdecaro 1.0.0 establishes the stable Joomla identifiers:

- component: `com_decarodocuments`;
- package: `pkg_decarodocuments`.

Core by xdecaro `1.1.0+` is a mandatory runtime dependency for Documents 1.0.0. The package installer checks the installed Core version before install/update and fails with a controlled administrator message when the dependency is missing or too old.

Documents uses Core only for genuinely shared infrastructure:

- `Xdecaro\Core\Asset\AssetService` for opt-in administrator Web Asset Manager styles;
- `.xdecaro-scope` and Core UI primitives;
- `Xdecaro\Core\Integration\EntityReference`;
- `Xdecaro\Core\Integration\RelationReference`.

Documents does **not** copy Core CSS or JS into its package.

Documents remains owner of:

- document records and stable UUIDs;
- uploaded files and private storage;
- document MIME/type validation;
- file hashes and size metadata;
- document access levels and lifecycle state;
- relationships between documents and external entities;
- document-specific authorization and downloads.

The `#__decarodocuments_relations` table stores target references as `component/entity/id/relation type` values. It deliberately has no foreign keys to Forms, Courses, Competitions, Membership, Events or other independent components. The only foreign key is from a relation to its owning Documents record.

For Competitions, the stable target component identifier remains `com_decarodcl`.

Future public Documents services may expose stable document IDs/UUIDs and relation operations. Consumers must not store private filesystem paths or directly depend on Documents internal storage filenames.

# Changelog

## 1.2.1 - 2026-09-09

- Fixed Joomla SQL manifest compatibility by declaring the install SQL file with `charset="utf8"` while retaining `utf8mb4` table definitions.
- Added a non-destructive `1.2.1.sql` repair migration using `CREATE TABLE IF NOT EXISTS` for both Documents-owned tables.
- Existing Documents data is preserved; no table is dropped, truncated or recreated during update.
- Added runtime regression coverage for clean installation and repair upgrade from the published 1.2.0 package on Joomla 5.4.8 and 6.1.3.
- Preserved the Documents 1.2 public relation API, mandatory Core 1.3.0+ policy, stable Joomla identifiers and private storage behavior.

## 1.2.0 - 2026-09-09

- Added the first provider-owned cross-product relation API through `RelationService`.
- Added ACL-protected, idempotent attach/detach operations using Core `RelationReference` values.
- Added ACL-protected relation lookup by external `EntityReference` without exposing private storage paths.
- Added Core 1.4 Capability Registry declarations for `documents.relations.attach`, `documents.relations.detach` and `documents.relations.query`.
- Registered the relation service through Joomla dependency injection.
- Reused the existing `#__decarodocuments_relations` schema; no data/table migration is required.
- Preserved mandatory Core 1.3.0+ installation policy and all existing Documents identifiers.

## 1.1.0 - 2026-09-09

- Migrated all Core API consumption to the canonical `xdecaro\Core` namespace introduced by Core 1.3.0.
- Raised the existing mandatory Core dependency from 1.1.0+ to 1.3.0+ in installer preflight, UI integration, diagnostics and relation adapter.
- Preserved `com_decarodocuments`, `pkg_decarodocuments`, `Xdecaro\Component\Decarodocuments` and `#__decarodocuments_*`.
- No database schema or document-domain behavior changes; 1.1.0 SQL is a Joomla schema-version marker only.

## 1.0.0 - 2026-09-08

- First stable installable Documents by xdecaro package.
- Added administrator document list, editor, upload replacement and protected download.
- Added server-side MIME/extension validation, random storage names, SHA-256 metadata and a 25 MiB upload limit.
- Added storage outside the Joomla public document root by default; uninstall preserves document data and files.
- Added Joomla ACL, access-level metadata, CSRF-protected forms and bound database queries.
- Added document-owned cross-product relation table without foreign keys to other Joomla components.
- Added mandatory Core by xdecaro 1.1.0+ installer check and Core Web Asset Manager UI usage.
- Added Information/Diagnostics view without exposing storage paths or sensitive file metadata.
- Added deterministic package build, CI, Joomla update feed and release workflow.

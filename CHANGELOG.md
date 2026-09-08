# Changelog

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

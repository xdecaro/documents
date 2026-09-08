# Changelog

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

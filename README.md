# Documents by xdecaro

Documents by xdecaro is the reusable document-management component for the xdecaro Joomla ecosystem.

Version 1.2.2 preserves the existing Documents 1.2 feature set and fixes enforcement of its mandatory Core by xdecaro 1.3.0+ installation requirement on real Joomla 5/6 installations.

Main capabilities remain unchanged:

- administrator document list and editor;
- secure upload validation and controlled download;
- storage outside the Joomla public document root by default;
- Joomla ACL, CSRF-aware form handling and access levels;
- stable document UUIDs and SHA-256 file integrity metadata;
- document-owned relationship storage for cross-product integrations;
- mandatory Core by xdecaro 1.3.0+ for shared infrastructure and UI primitives;
- no duplicated local design system.

Technical identifiers remain stable:

- component: `com_decarodocuments`;
- package: `pkg_decarodocuments`;
- namespace: `Xdecaro\Component\Decarodocuments`;
- database prefix: `#__decarodocuments_*`.

Documents owns document records, file storage, access, relationships and lifecycle. Core remains shared infrastructure only.

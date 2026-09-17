# Documents by xdecaro

Documents by xdecaro is the reusable document-management component for the xdecaro Joomla ecosystem.

Version 1.4.0 targets Joomla 6 and keeps Documents focused on the document domain: secure storage, access, lifecycle, immutable file versions, auditability and generic links to records owned by other components.

Main capabilities:

- administrator document list and editor with search and lifecycle filters;
- secure server-side upload validation and controlled downloads;
- private storage outside the Joomla public document root by default;
- Joomla ACL, CSRF-aware forms, access levels and confidentiality classification;
- stable document UUIDs and SHA-256 file integrity metadata;
- immutable stored-file versions with protected historical downloads;
- document type, reference, document/validity/expiry dates and Joomla language metadata;
- lifecycle states from draft through review/approval/publication/archive/expiry;
- activity audit records for security-relevant document actions;
- document-owned generic relationship storage for cross-product integrations;
- optional bridges to Notifications, Tasks and Analytics through public services;
- mandatory Core by xdecaro 1.3.0+ for shared infrastructure and UI primitives;
- no duplicated local design system.

Technical identifiers remain stable:

- component: `com_decarodocuments`;
- package: `pkg_decarodocuments`;
- namespace: `Xdecaro\Component\Decarodocuments`;
- database prefix: `#__decarodocuments_*`.

Documents owns document records, file storage, versions, access, relationships and lifecycle. A consuming component owns the business reason a document exists and must use the public Documents integration surface rather than private Documents tables or filesystem paths.

# Documents — Codex Repository Rules

## Xdecaro Core integration

Documents is part of the Xdecaro Joomla ecosystem and should use **Xdecaro Core** for infrastructure that is genuinely shared across multiple Xdecaro extensions.

Core is infrastructure. Documents is a separate reusable product responsible for the document domain.

Before implementing reusable technical infrastructure, inspect whether it already exists in Core or clearly belongs there.

Good Core candidates include:

- shared design tokens and `.xdecaro-*` UI primitives;
- light/dark mode foundations;
- responsive administrator UI helpers;
- shared buttons, badges, cards, tables, modals, alerts and loading states;
- shared Web Asset Manager registration;
- generic JavaScript utilities;
- Joomla-compliant AJAX/CSRF helpers;
- dependency/version checks;
- common diagnostics;
- Xdecaro extension registry;
- shared information/update UI;
- genuinely generic cross-extension contracts or events.

Keep Documents-specific business logic in this repository, including:

- document entities and metadata;
- file/document versions;
- document status and lifecycle;
- document categories/types when they belong to the Documents domain;
- ownership and document relationships;
- access rules specific to documents;
- sensitive/confidential document handling;
- document retention or expiry rules;
- document audit/history;
- document attachments;
- document previews/download behavior;
- document-specific search/filtering;
- document-specific import/export;
- document-domain integrations.

Do not move these domain concepts into Core.

Documents may be used by many Xdecaro components. Reuse across multiple products does **not** automatically make Documents functionality part of Core.

Core provides generic infrastructure. Documents provides reusable document management.

## Integration role

Forms, Courses, Competitions, Membership, Events, Bookings and future Xdecaro products may use Documents for attachments or managed documents.

Prefer a stable Documents public API, service contract or event interface over direct access to Documents internal database tables or implementation classes.

Avoid circular dependencies.

A consuming component remains responsible for the business reason a document exists. Documents remains responsible for storing and managing the document according to its own domain rules.

Examples:

- Forms decides that a submission requires an attachment; Documents manages the stored document when integration is enabled.
- Courses decides that an enrollment requires a certificate/document; Documents manages the document lifecycle.
- Competitions decides that a participant or competition requires a document; Documents manages the document itself.
- Membership decides that a member practice requires identity/certification documents; Documents manages storage, access and lifecycle.

Do not duplicate a full document engine inside each consuming component once a stable Documents API provides the required capability.

## Generic relationships

If cross-component entity relationships are implemented, prefer a stable generic reference shape such as component/entity/id/relation type only when it is genuinely reusable and does not leak one product's domain into another.

Evaluate whether a very low-level generic relation contract belongs in Core, but keep document-specific relation semantics and persistence in Documents unless there is a proven broader need.

Do not create hard foreign-key coupling across independent Joomla components unless the lifecycle and uninstall/update behavior are explicitly designed for it.

## Public Documents API

Treat the Documents integration surface as a stable public contract once published.

Before changing public:

- PHP services/interfaces;
- events;
- relation/reference structures;
- API payloads;
- asset identifiers;
- document identifiers;
- storage abstractions;

inspect impact on Forms, Courses, Competitions, Membership, Events and other consumers.

For incompatible changes prefer:

1. introduce the new API;
2. keep the previous API temporarily;
3. mark the previous API as deprecated;
4. remove it only in a future major release.

## Security and sensitive documents

Documents can handle sensitive files, so security is critical.

Check where relevant:

- server-side ACL for every view/download/change operation;
- Joomla CSRF tokens for state-changing requests;
- input filtering and validation;
- output escaping;
- upload extension validation;
- MIME type validation using server-side evidence;
- filename normalization;
- safe generated filenames;
- file-size limits;
- storage path validation;
- directory traversal prevention;
- executable file prevention;
- access-controlled downloads;
- authorization before exposing file metadata or content;
- safe preview behavior;
- auditability of sensitive operations.

Never rely on a hidden URL or frontend JavaScript as document access control.

Do not expose private filesystem paths, credentials, tokens or sensitive metadata in diagnostics or error messages.

## Storage

Design storage so the implementation can evolve without forcing consuming components to know filesystem details.

Prefer stable document IDs and service APIs over consumers storing raw server paths.

Do not trust client-supplied paths.

Preserve original files when transformations/previews require derivatives unless the product specification explicitly defines otherwise.

If multiple storage backends are introduced later, keep the abstraction inside Documents rather than Core unless the same storage abstraction has proven non-document consumers.

## Database

Use `#__` for Joomla tables.

Keep Documents-domain tables in Documents.

Do not move document records, versions, retention data, confidentiality state or document relations into Core merely because multiple products consume Documents.

Database updates must preserve existing data and configuration.

Do not drop/recreate normal production tables during routine updates when a safe migration is possible.

Check indexes, joins, duplicate queries and queries inside loops, especially for document lists, relationships, version history and search.

## Version 1.0.0 stability

Version `1.0.0` establishes the first stable public baseline.

From 1.0.0 onward, treat published integration contracts conservatively.

Use Semantic Versioning:

- PATCH for backward-compatible fixes;
- MINOR for backward-compatible functionality;
- MAJOR for intentional incompatible changes.

Do not silently break a consuming component in a PATCH or MINOR release.

Keep manifest versions, package metadata, changelog, update server, SQL updates, tags, GitHub Releases and generated ZIPs coherent.

Never distribute different code with the same version number.

## Dependency policy

If Core is mandatory, declare and enforce a documented minimum Core version through package/manifests/update behavior.

Missing or incompatible Core versions must fail safely with a clear Joomla administrator message.

Do not make Forms, Courses, Competitions, Membership or Events mandatory dependencies merely because Documents can integrate with them.

Cross-product integrations should be optional unless a product is explicitly designed otherwise.

## UI and assets

Prefer Core for shared visual primitives and asset infrastructure when available, while keeping document-specific screens and workflows local.

Verify:

- administrator and frontend document lists;
- upload/download/preview flows;
- status and access indicators;
- responsive layouts;
- accessibility;
- light mode;
- dark mode.

Load shared Core assets through Joomla Web Asset Manager and avoid duplicate CSS/JS registration.

## Regression rule

A Core-related or API-related change is complete only when affected Documents behavior remains verified.

Check as applicable:

- clean installation;
- update installation;
- supported Joomla versions;
- upload;
- download;
- preview;
- access control;
- sensitive document restrictions;
- metadata editing;
- document relationships;
- version/history behavior;
- optional integration with real consuming components;
- assets loaded once;
- AJAX;
- ACL and CSRF;
- database migrations;
- PHP errors/warnings;
- JavaScript Console;
- desktop/tablet/smartphone;
- light/dark mode;
- final installable ZIP contents.

Do not combine an opportunistic Core integration with unrelated large refactors.

## Working rule

When the user says **“procedi”**, execute the requested work directly after inspecting the relevant code and dependencies.

Do not ask for another confirmation when requirements are already clear.

If a proposed technical approach is weaker than a safer or more maintainable alternative, explain the issue and use or recommend the stronger approach.

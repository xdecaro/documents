# Xdecaro Core integration

Documents uses the Xdecaro Core cross-product reference contract to associate managed documents with entities owned by Forms, Courses, Competitions, Membership, Events and future Xdecaro products.

Documents must not invent its Joomla component element from the repository name. The first real component manifest must define the stable installed element; from that point cross-product references must use that exact identifier.

Use:

- `Xdecaro\Core\Integration\EntityReference` for `component/entity/id` references;
- `Xdecaro\Core\Integration\RelationReference` for typed links between references.

Documents remains the owner of document records, files, versions, document access, confidentiality, expiry/retention, previews/downloads and document lifecycle.

Typical integrations include:

- document -> Forms form or submission;
- document -> Courses course, edition or enrollment;
- document -> Competitions participant, team, season, match or another published Competitions entity, using component element `com_decarodcl`;
- document -> Membership member, application or renewal;
- document -> Events event or registration after Events publishes its stable API.

The consuming product decides why a document is required and how it affects its workflow. Documents decides how the document itself is stored, secured, versioned and exposed.

Do not use raw filesystem paths or another product's private table IDs as an undocumented integration protocol. Prefer stable document IDs plus Core EntityReference values and the Documents public API.

Do not make Forms, Courses, Competitions, Membership or Events mandatory dependencies simply because Documents can integrate with them. Optional integrations must fail gracefully.

Because Documents starts from the 1.0.0 stable line, published entity/reference/API contracts must be changed conservatively according to Semantic Versioning.

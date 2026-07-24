# VS-002 implementation baseline

Status: REVIEW CANDIDATE — NOT SELF-APPROVED.

VS-002 selects `CAP-D05-02-02` and `KU-D05-004` under the stable D05 hierarchy. It implements one synthetic, local-only request contract: `GET /api/case-files/{caseFileId}`. The slice reuses the Task-006 modular monolith and the corrected VS-001 publication, simulator, evidence, and learning primitives.

The runtime adds no module, service boundary, connector, scanner, public target, dynamic expression language, AI path, or broad CRUD. New persistence is limited to endpoint-contract revisions, authorization-policy revisions, security findings, finding verifications, and VS-002 fields on existing run/evidence records.

All generated learning and run evidence is `SIMULATED`. The Enterprise Baseline is read before and after every run and its digest must remain equal.

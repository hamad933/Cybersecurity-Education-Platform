# Product Definition v1 Candidate

Status: **REVIEW CANDIDATE — NOT APPROVED**  
Product: **Cybersecurity Learning and Operations Simulation Platform**

## 1. Mission and value

The product turns authorized source material into reviewed, versioned knowledge; connects that knowledge to capability-centred learning; and lets a local owner practise institutional decisions in a stateful simulator while retaining provenance, evidence origin, and failure history. It is valuable when a learner can explain, perform, observe, analyse, defend, verify, retain, and transfer a bounded capability—not merely open or complete content.

## 2. Binding boundaries

The product is independent and is not a CARE rebuild, conventional LMS, notes system, question bank, static output simulator, production security tool, offensive-execution platform, or replacement for authorized real environments. Historical CARE artifacts are lessons only after explicit review. Core v1 works without public Internet.

`v1 Product Completeness` means the agreed platform workflows are coherent, secured, recoverable, and exercised through tested vertical slices. It does **not** mean complete curriculum across all 16 Domains. The 16 Domains, 53 Capability Clusters, 106 Capabilities, and 96 provisional KU candidates are governed expansion architecture, not finished lessons.

## 3. Users and access

The primary v1 user is one local owner acting as learner, author, scenario designer, and reviewer at distinct workflow moments. Secondary personas are a curriculum reviewer and professional-workflow reviewer, but v1 does not add separate accounts for them. A local owner account, modern password hashing, protected session cookies, CSRF defence, rate-limited authentication, and explicit sign-out are minimum requirements. Any bypass is development-only, off by default, visibly indicated, and impossible in a release profile.

Application authorization is separate from simulated roles. The owner controls product records; simulated roles such as `SOC Analyst` or `AD Security Engineer` affect only Scenario Run permissions and outcomes. v1 excludes public registration, SaaS multi-tenancy, billing, social features, marketplace features, and Internet collaboration.

## 4. Core journeys

1. Register an authorized source, preserve the immutable original and digest, extract a safe representation, review segments and claims, and publish or reject a versioned knowledge revision.
2. Create or revise a structured lesson from canonical knowledge, compare the draft with its published revision, review provenance, and publish an immutable new revision.
3. Choose a capability through the daily queue, complete micro practice, run a Guided Simulator Lab, submit evidence, receive a capability-specific mastery transition, and re-enter failure-based review when required.
4. Create an Enterprise Baseline, author a Scenario Definition Revision visually, fork an isolated Scenario Run, act under a simulated identity, inspect deterministic consequences, reset/replay, and propose—never silently apply—baseline improvements.
5. Export a bounded Manual AI Prompt Package, process it manually through ChatGPT Plus, import a structured result, validate structure and source references, inspect the proposed diff, and accept, partially accept, reject, or supersede it through human review.
6. Export portable packages, create a protected backup, verify restore into an isolated target, and audit the result.

## 5. Product modules

The Modular Monolith contains ten bounded modules: Platform Identity and Access; Source Library and Ingestion; Knowledge and Publication; Curriculum and Capability; Learning and Mastery; Enterprise Catalog; Simulation and Scenario; Evidence and Portfolio; Manual AI Bridge; and Platform Services. Ownership and contracts are normative in `PRODUCT_MODULE_CATALOG.md`, `MODULAR_MONOLITH_BOUNDARIES.md`, and the Task 004 planning TSVs.

## 6. Source, authority, and publication

The runtime separates Original Source, Extracted Representation, Source Segment, Claim/Relationship, Canonical Knowledge, Educational Presentation, and Learner State. Originals are immutable custody objects. Every derivative records source, digest, extractor status, warnings, stable page/line/timestamp anchors, authority assessment, applicability, conflicts, and review state. Filenames are never authority.

Canonical knowledge is singular and contextual reuse is relational. Drafts may change; published Knowledge Revisions, Lesson Revisions, Enterprise Baseline Revisions, and Scenario Definition Revisions are immutable. Corrections create new revisions with an impact assessment and an explicit restoration pointer. Markdown is import/export, not the sole runtime model.

## 7. Structured content

Lessons use ordered, addressable blocks with a controlled `block_type`, schema version, typed validated payload, provenance links, locale/direction metadata, and nesting constraints. `JSONB` is allowed only for registered type-specific payload schemas; unknown keys, excessive depth/size, raw executable HTML, and arbitrary polymorphic dumps are rejected. Required block types and validation are specified in `CONTENT_AND_REVISION_MODEL.md`.

## 8. Curriculum, daily learning, evidence, and mastery

The hierarchy is `Domain -> Capability Cluster -> Capability -> Knowledge Unit`. The lifecycle is `KU -> Lesson Revision -> Micro Practice -> Guided Simulator Lab -> optional Selective Real-Lab Validation -> Evidence-Based Mastery -> Failure-Based Review`; stages are selected per KU. Integrated projects and institutional scenarios combine multiple KUs without duplicating canonical knowledge.

Mastery is an evidence ledger plus capability state, not percentage complete. Candidate states are `UNASSESSED`, `CAN_EXPLAIN`, `CAN_REPRODUCE`, `CAN_OBSERVE`, `CAN_ANALYZE`, `CAN_DEFEND_AND_VERIFY`, and `RETAINED_AND_TRANSFERABLE`. Promotion requires configured evidence rules; decay or failed evidence creates review triggers. Thresholds remain provisional and configurable until empirically calibrated.

## 9. Enterprise, scenario, and simulator

Persistent catalogs cover organizations, sites, networks, zones, assets, systems, services, applications, identities, accounts, groups, roles, privileges, trusts, data, policies, controls, risks, threats, findings, vulnerabilities, detections, alerts, incidents, procedures, runbooks, evidence requirements, ownership, and responsibility.

An immutable Enterprise Baseline Revision is referenced by an immutable Scenario Definition Revision. Starting a Scenario Run snapshots the necessary state into an isolated run namespace. Actions never mutate the baseline. The simulator is default for Guided Labs, stateful and deterministic where practical, and evaluates baseline state, run state, device, identity, permissions, action, policy/control rules, and prior state to emit new state, output, logs, findings, alerts, evidence, and consequences. Unsupported state is explicit; display-only commands are never executed.

Real-Lab Validation is `OPTIONAL`, `RECOMMENDED`, or `REQUIRED_FOR_SPECIFIC_CLAIM`, never universally mandatory. Evidence origin is always one of `SIMULATED`, `REAL_LAB`, `MANUAL_ASSESSMENT`, or `SOURCE_REVIEW`.

## 10. Manual AI Bridge

For v1 and the approved roadmap, `AIInteractionPort` has exactly one workflow: Manual AI Bridge. There are no provider credentials, API calls, provider selection, polling, metering, embeddings, vector databases, or local models. The Product Charter's earlier replaceable-provider language is superseded by this decision. Imported output is untrusted proposal data until structural validation, provenance validation, and human review complete.

## 11. Import/export, backup, and operations

Imports are bounded, quarantined, type-checked, size-limited, path-normalized, digest-verified, and non-executing. Portable packages are versioned, manifested, and hash-checked. Backups include PostgreSQL data, application blobs, configuration metadata excluding secrets, and a manifest; restore is staged, verified, and auditable. Core services bind locally by default.

## 12. Security, privacy, accessibility, and language

No private source is silently transmitted. Manual AI export shows and records exact scope. Untrusted content is safely rendered without script execution; command and code blocks are display-only. Audit records cover authentication, imports, publication, Manual AI transitions, scenario lifecycle, evidence decisions, exports, backups, and restores.

The interface is Arabic-first and WCAG-oriented. Arabic prose is native RTL and right-aligned; English technical identifiers, paths, APIs, code, commands, logs, and packet fields are isolated LTR without manual reversal. Semantic HTML, keyboard access, visible focus, labelled controls, adequate contrast/touch targets, responsive reflow, and non-colour status cues are acceptance requirements.

## 13. v1 / post-v1 boundary

v1 includes the coherent platform workflows above and three deep slices (`VS-001` through `VS-003`) plus release hardening. v1 contains only bounded seed content needed by those flows. Post-v1 includes broad curriculum authoring across all Domains, controlled multi-user use, collaboration, optional PWA features, specialized workers proven necessary by measurement, and claim-specific real-lab integrations. Real-execution connectors, SaaS, marketplaces, automated AI, and generalized provider frameworks are outside the approved roadmap.

## 14. Success meaning and limitations

Success is established only by the testable criteria in `V1_SUCCESS_CRITERIA.md`, Task 004 catalogs, and later implementation evidence. This candidate makes no claim of implementation, complete technical truth, complete curriculum, calibrated mastery, real-environment competence, or production readiness. VS-001 still requires a target Windows baseline and Microsoft/Open Specifications verification before technical publication or real-transfer claims.


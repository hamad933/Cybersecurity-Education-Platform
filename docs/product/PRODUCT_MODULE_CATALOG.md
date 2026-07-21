# Product Module Catalog

The normative row-level catalog is `planning/task004/MODULE_BOUNDARIES.tsv`.

| ID | Module | Purpose | v1 boundary | VS-001 |
|---|---|---|---|---|
| MOD-IAM | Platform Identity and Access | Owner authentication, application session, authorization policy | One local owner; no tenant or public registration | Protects all author/reviewer actions |
| MOD-SRC | Source Library and Ingestion | Immutable custody, extraction state, segments, authority, provenance | Safe bounded local import; no broad crawler/OCR | Supplies selected reviewed source evidence |
| MOD-KNO | Knowledge and Publication | Claims, canonical records, blocks, drafts, reviews, immutable publication | Structured lesson revisions and impact | Publishes `KU-AD-02` lesson revision after blockers resolve |
| MOD-CUR | Curriculum and Capability | Domain/cluster/capability/KU/path definitions | Expansion architecture plus slice seeds | Owns `CAP-D03-03-01` and `KU-AD-02` definitions |
| MOD-LRN | Learning and Mastery | Diagnostics, practice, attempts, state transitions, review queue | Evidence rules and provisional configurable thresholds | Micro practice, lab attempt, failure review |
| MOD-ENT | Enterprise Catalog | Reusable enterprise entities and immutable baseline revisions | Catalog CRUD/versioning, no production discovery | Principal, groups, asset, object, policy context |
| MOD-SIM | Simulation and Scenario | Studio, definitions, isolated Runs, deterministic engine contracts | Simulated actions only; no real connectors | Windows authorization decision trace/reset/replay |
| MOD-EVD | Evidence and Portfolio | Evidence records, origin, integrity, decisions, portfolio views | `SIMULATED`, `REAL_LAB`, `MANUAL_ASSESSMENT`, `SOURCE_REVIEW` labels | Owns simulated trace evidence |
| MOD-AIB | Manual AI Bridge | Manual prompt-package exchange and human review | Manual ChatGPT Plus exchange only | Optional content proposal support, never simulator authority |
| MOD-PLT | Platform Services | Audit, files, search, queue, import/export, backup/restore, health | PostgreSQL/local storage; no Redis/search cluster | Audit, search, processing and recoverability |

No module may write another module's tables directly. Cross-module change occurs through public application services inside one process and recorded internal messages. Shared code is limited to identifiers, clocks, digests, locale/value objects, result types, and transactional/outbox contracts.


# Data Ownership and Lifecycle

## Lifecycle classes

| Class | Examples | Mutability | Deletion/retention rule |
|---|---|---|---|
| Custody | OriginalSource, imported package blob | Immutable bytes and digest | No in-place replace; removal is explicit, audited, dependency-checked, and policy-controlled |
| Working draft | KnowledgeDraft, LessonDraft, ScenarioDraft | Mutable with optimistic version | May be discarded with audit; never confused with published |
| Published revision | KnowledgeRevision, LessonRevision, BaselineRevision, ScenarioDefinitionRevision | Immutable | Retained; supersession creates a new revision |
| Run/attempt | ScenarioRun, Attempt, transition/event | Append-only state transitions; terminal lock | Reset creates a new run epoch or explicit reset event; evidence retained |
| Learner state | MasteryState, ReviewSchedule | Current projection plus immutable decision history | Recomputed only from retained evidence/rules with audit |
| Audit/integrity | AuditRecord, digest manifest | Append-only | Retention policy cannot erase required provenance silently |

## Transaction boundaries

Publication validates source references, block schemas, review decision, optimistic version, and impact flags in one transaction; it writes the immutable revision, advances the publication pointer, audit record, and outbox message atomically. Starting a Scenario Run resolves and hashes the baseline/scenario revisions in one transaction before any action. Evidence recording binds origin/digest/criteria atomically; mastery evaluation consumes committed evidence idempotently.

## Restoration and correction

Restoring a historical publication copies its content into a new draft, records `based_on_revision_id`, passes current validation/review, then publishes a new immutable revision. Blob repair requires digest comparison, quarantine, and an explicit custody incident; database pointers are never silently redirected.

## Export and erasure

Exports are manifested snapshots with ownership and revision IDs. v1 local owner deletion requests must surface downstream citations, lessons, evidence, and packages. Published/audit records may be tombstoned or access-restricted only according to an approved retention decision; hard-delete defaults are prohibited where they would break truthfulness.


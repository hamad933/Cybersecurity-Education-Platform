# Modular Monolith Boundaries

## Contract rules

- Only an owning module creates or mutates its entities/tables.
- Read models may join through an owner-provided query service or explicitly owned projection; ad hoc cross-module writes are forbidden.
- Public application services accept typed commands/queries and return typed results without exposing ORM models.
- Synchronous calls are used for invariants requiring one user transaction. Durable internal messages are used for impact analysis, projections, queueing, and retryable follow-up.
- Every message has an ID, schema version, causation/correlation IDs, occurred time, producer, and idempotent consumer state.
- Stable shared primitives are limited to identifier, digest, clock, locale/direction, actor, pagination, money-free result/error, and transaction/outbox contracts.

## Module contracts

| Module | Public services | Inbound dependencies | Outbound dependencies | Important messages | Security boundary |
|---|---|---|---|---|---|
| MOD-IAM | AuthenticateOwner, EndSession, AuthorizeAction | MOD-PLT audit/clock | MOD-PLT | OwnerAuthenticated, SessionRevoked | Password/session material visible only here |
| MOD-SRC | RegisterSource, StartExtraction, ReviewAuthority, ResolveSegment | MOD-IAM, MOD-PLT | MOD-KNO by query/message | SourceRegistered, ExtractionCompleted, SourceReviewChanged | Untrusted bytes and authorization metadata |
| MOD-KNO | CreateDraft, ValidateBlocks, ReviewRevision, PublishRevision | MOD-SRC, MOD-IAM, MOD-PLT | MOD-CUR, MOD-LRN | KnowledgePublished, LessonPublished, ImpactRequested | Publication approval and safe rendering |
| MOD-CUR | ResolveCapability, PublishPathTemplate | MOD-KNO, MOD-PLT | MOD-LRN | CapabilityDefinitionChanged | Taxonomy integrity and singular KU identity |
| MOD-LRN | StartAttempt, EvaluateMastery, ScheduleReview | MOD-CUR, MOD-KNO, MOD-SIM, MOD-EVD | MOD-EVD, MOD-PLT | AttemptCompleted, MasteryChanged, ReviewScheduled | Provisional threshold and learner-state integrity |
| MOD-ENT | ManageCatalog, PublishBaselineRevision, ProposeBaselineChange | MOD-IAM, MOD-PLT | MOD-SIM | BaselinePublished, BaselineChangeProposed | Persistent truth cannot be changed by run |
| MOD-SIM | PublishScenarioRevision, StartRun, ApplySimulatedAction, ResetRun, ReplayRun | MOD-ENT, MOD-CUR, MOD-IAM, MOD-PLT | MOD-EVD, MOD-LRN | RunStarted, SimulatedActionEvaluated, RunCompleted | No host/real execution; isolated run namespace |
| MOD-EVD | RegisterEvidence, VerifyDigest, DecideEvidence, BuildPortfolioView | MOD-SIM, MOD-LRN, MOD-IAM, MOD-PLT | MOD-LRN | EvidenceRecorded, EvidenceDecisionChanged | Origin, blob integrity, decision audit |
| MOD-AIB | DraftPackage, ExportPackage, ImportResult, ValidateResult, DecideProposal | MOD-SRC, MOD-KNO, MOD-IAM, MOD-PLT | Owning modules through draft commands | PackageExported, ResultValidated, ProposalDecided | Explicit export scope; untrusted import; no automatic call |
| MOD-PLT | Audit, BlobStore, Search, Queue, Export, Backup, Restore, Health | all modules | none as domain owner | ProcessingRunChanged, BackupVerified | Secrets, local exposure, recoverability |

## Forbidden coupling

Modules may not share mutable aggregates, use a global `misc` schema, infer authority from filenames, let a Run update baseline tables, let AIB publish, or let a rendered command reach a system shell. Database foreign keys may enforce stable identifiers across schemas only when they do not confer mutation ownership; application contracts remain authoritative.


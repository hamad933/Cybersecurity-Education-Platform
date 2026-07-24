# VS-001 Targeted Corrections

Status: **REVIEW EVIDENCE — NOT SELF-APPROVAL**  
Date: **2026-07-22**

Task-008 began by implementing C7-001 through C7-008 before any VS-002 implementation. The exact row-level result is `planning/task008/VS001_CORRECTION_RESULTS.tsv`.

- Replay now loads the scenario, simulator rule, enterprise baseline, case definition, seed, and ordered actions pinned by the original run. Publishing a changed revision does not alter historical replay.
- Controllers and coordinators no longer import cross-module ORM models. Module-owned application services own their reads and writes, and the architecture scan covers `app/Application`, controllers, jobs, listeners, and modules.
- Micro-practice submissions use a bounded six-field answer and a versioned answer key. Outcome, decisive step/ACE, masks, and rationale concepts are evaluated server-side.
- Review triggers are derived from actual practice or trusted observation failures, retain actor/KU/source/rule/reason links, and deduplicate active actor/KU/failure/case rows.
- The lesson editor has real restore, edit, optimistic-lock, submit, return-with-rationale, approve, publish, immutable-published, and restore-as-new-draft paths. Every service transition used through HTTP is audit-backed.
- Runs and evidence carry the learner actor. Evidence decisions retain the reviewer actor, and mastery reads only accepted evidence for the requested actor.
- An idempotency key returns the same result only for an equivalent actor and payload. A conflicting actor or payload produces a controlled validation result without duplicate run, evidence, or outbox records.
- The historical Task-007 acceptance file and packet remain untouched. The additive external recheck is `planning/task008/VS001_EXTERNAL_ACCEPTANCE_RECHECK.tsv`.

Evidence summary: PHPUnit `106/106` tests and `1191` assertions passed; the targeted correction suite passed `49/49` with `701` assertions; PHPStan, Pint, Composer validation, PostgreSQL fresh/rollback/reapply, ESLint, Prettier, vue-tsc, Vitest `10/10`, Vite build, Composer audit, npm audit, Compose static validation, and the bounded fallback secret scan passed.

Residual limits: Docker runtime is `DOCKER_RUNTIME_UNAVAILABLE`; gitleaks is unavailable so the documented deterministic fallback is not equivalent to gitleaks; true parallel HTTP duplicate pressure was not forced, though request-level equivalence/conflict and the PostgreSQL unique-race recovery path are implemented.

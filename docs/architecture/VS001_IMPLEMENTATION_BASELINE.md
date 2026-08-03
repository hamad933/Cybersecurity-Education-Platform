# VS-001 implementation baseline

Status: `REVIEW CANDIDATE — NOT SELF-APPROVED`.

VS-001 resolves stable IDs `VS-001`, `CAP-D03-03-01`, and `KU-AD-02`. It demonstrates one deep lifecycle only:

```text
reviewed source -> published Arabic lesson revision -> micro practice
-> guided Institutional Simulator lab -> SIMULATED evidence
-> evidence decision -> versioned mastery evaluation -> failure-specific review
```

## Implemented persistence

The two Task-007 migrations create 19 bounded tables: `source_records`, `source_claims`, `knowledge_units`, `lesson_revisions`, `curriculum_placements`, `enterprise_baseline_revisions`, `improvement_proposals`, `simulator_rule_revisions`, `scenario_revisions`, `scenario_runs`, `decision_traces`, `replay_records`, `evidence_records`, `evidence_decisions`, `micro_practices`, `practice_attempts`, `mastery_rule_revisions`, `mastery_states`, and `review_triggers`, plus the previously approved foundation tables. PostgreSQL check/unique/foreign-key constraints enforce states, IDs, origins, and idempotency.

Published lesson, rule-set, scenario, baseline, and accepted evidence content is immutable. Draft lesson updates use optimistic locking. Restore creates a derived new draft. No generic untyped ingestion, broad entity catalog, real connector, real lab, AI call, or future slice is present.

## User workspaces

- Source and claim review.
- Lesson revision editor and immutable restore.
- Arabic-first lesson reader.
- Bounded micro practice with rationale.
- Guided 12-case lab, decision trace, and replay.
- Evidence decision, provisional mastery, and review triggers.

All protected routes require the single local owner and mutating routes use CSRF, bounded validation, mass-assignment allowlists, and rate limits. Public liveness remains non-sensitive.

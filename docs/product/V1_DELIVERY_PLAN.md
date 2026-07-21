# v1 Delivery Plan — Tasks 006–010

This is sequencing, not authorization to implement. Each task stops at an external review candidate.

## TASK-006 — Repository Foundation

- Outcome: establish the Laravel/PHP modular-monolith repository, Vue 3 + TypeScript + Inertia shell, PostgreSQL, Tailwind foundation, local Docker Compose/WSL2 workflow, version locks, testing harnesses, module boundaries, owner authentication, audit primitives, and CI-equivalent local gates.
- Includes: MOD-IAM and MOD-PLT foundations; empty bounded module shells/contracts only where needed for compile-time structure.
- Excludes: source ingestion workflows, lesson authoring, simulator behavior, VS-001, production content, automated AI, real connectors.
- Dependencies: approved Task 004; Task 006 environment inspection and currently supported compatible version selection.
- Evidence: recorded versions/lockfiles, clean install, tests, architecture-boundary checks, local exposure/session tests, review packet.
- Stop gate: `STOP-REPOSITORY-FOUNDATION-006`.
- Risks: WSL2/Docker mismatch, incompatible framework versions, premature abstractions. Complexity: **HIGH**.

## TASK-007 — VS-001 Windows Authorization Decision

- Outcome: implement the complete source-to-mastery lifecycle for `CAP-D03-03-01` / `KU-AD-02` with deterministic simulated access-check reasoning.
- Includes: minimum MOD-SRC/KNO/CUR/LRN/ENT/SIM/EVD flows and bounded VS-001 data.
- Excludes: authoritative claims until target Windows baseline and Microsoft/Open Specifications are approved; real Windows execution.
- Dependencies: Task 006 approval and authority blockers for publishable technical content.
- Evidence: positive/negative/unsupported cases, provenance, immutable lesson revision, simulated trace, reset/replay, mastery/review tests, screenshots.
- Stop gate: `STOP-VS001-007`. Risks: oversimplified ACE semantics and false authority. Complexity: **VERY_HIGH**.

## TASK-008 — VS-002 Web/API Trust-Boundary and Access-Control Failure

- Outcome: prove the architecture generalizes to a second Domain with request-context, trust-boundary, access decision, detection, remediation, and verification.
- Includes: D05 bounded content, new simulator rules and evidence types, reuse of publication/mastery contracts.
- Excludes: live exploitation, production scanning, generalized attack tooling.
- Dependencies: approved VS-001 patterns and selected current primary web/API authorities.
- Evidence: positive/negative authorization paths, stored-XSS-safe lesson rendering, deterministic logs/findings, review trigger, regression of VS-001.
- Stop gate: `STOP-VS002-008`. Risks: simulator turning into static examples; unsafe payload rendering. Complexity: **HIGH**.

## TASK-009 — VS-003 Authentication Anomaly Investigation

- Outcome: integrate identities, telemetry, detection, triage decisions, evidence preservation, and reporting in an institutional scenario.
- Includes: D03/D08/D09/D16 bounded content, multi-device simulated contexts, scenario timeline and professional workflow.
- Excludes: production SIEM, credential attacks, real incident operations.
- Dependencies: Tasks 007–008 and selected authorities/dataset.
- Evidence: deterministic anomaly paths, permission-aware investigation, evidence chain, alternative decisions, remediation/verification, prior-slice regressions.
- Stop gate: `STOP-VS003-009`. Risks: unrealistic telemetry and role conflation. Complexity: **VERY_HIGH**.

## TASK-010 — Integration, Hardening, Backup/Restore, Accessibility, v1 Release

- Outcome: integrate the three slices, complete safe import/export, Manual AI Bridge, search/queue, backup/restore, accessibility, operations, and release evidence.
- Includes: cross-module hardening, threat-model tests, migration/recovery drills, performance bounds, Arabic/English QA.
- Excludes: new curriculum breadth, multi-user/SaaS, automated AI, real connectors, post-v1 infrastructure.
- Dependencies: approved Tasks 006–009.
- Evidence: aggregate test gate, dependency/security review, restore drill, accessibility report, performance limits, release packet and rollback plan.
- Stop gate: `STOP-V1-RELEASE-010`. Risks: late integration defects and incomplete recovery evidence. Complexity: **VERY_HIGH**.


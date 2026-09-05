# TRANSPORT COPY — GOOGLE AI STUDIO OPERATIONAL GUIDE

Status: `NON_CANONICAL_TRANSPORT_COPY__REFRESHED_2026-09-05`
Canonical owner: Google Drive `10LsQRYzsQbl00eaFwyumeDwgh_gGkZHS` under `05_EXECUTION_SYSTEM`.
Purpose: stable filesystem/Git transport for AI Studio execution. This copy never overrides the canonical Drive owner.

---

# Google AI Studio — Operational Execution Guide

Status: `ACTIVE TOOL-SPECIFIC OPERATIONAL GUIDE — DYNAMIC PLATFORM FACTS MUST BE REVERIFIED`
Owner: `05_EXECUTION_SYSTEM/_AI_EXECUTION_CONTROL/SHARED_EXECUTION_METHODS/TOOL_EXECUTION_GUIDES/`
Last consolidated: `2026-09-05`

## Purpose
Use Google AI Studio / Agents as a hosted execution environment when it materially accelerates an authority-bounded task. Treat hosted workspace/runtime state as Execution System truth, never as project authority. GitHub remains repository truth; Drive remains governed control/knowledge truth.

## Provider capability posture
Provider behavior is volatile. Reverify only capabilities that materially affect the current task. Do not rebuild a fully verified environment merely because a new task starts in the same retained environment.

Current dated provider/docs knowledge includes GitHub import/link/sync and multi-file agent execution. Existing non-React repositories may be handled through the agent/Linux workflow when that preserves the repository architecture; do not force a framework conversion merely because Build mode has a preferred stack.

## Mandatory authority binding
Before substantial product mutation establish:

```text
PROJECT / ROUTE
REPOSITORY IDENTITY
AUTHORIZED BASELINE / BRANCH / SHA
CURRENT WORKSPACE CONTENT
WRITE AUTHORITY
writeScope / PROHIBITIONS
REQUIRED EVIDENCE
STOP GATE
```

Tool capability never grants authority to widen scope, overwrite unrelated history, merge, release, deploy, publish publicly, or mutate canonical Drive state.

## Environment lifecycle — verify once, then reuse
A fresh environment requires a real bootstrap. A previously proven environment does not require the same bootstrap before every surface.

Rule:

```text
FIRST USE / MATERIAL ENVIRONMENT CHANGE
→ FULL ENVIRONMENT PREFLIGHT
→ CAPABILITY RECEIPT
→ REUSE SAME ENVIRONMENT
→ TASK-LOCAL FRESHNESS CHECK ONLY
```

For a retained environment, check only the task-critical facts that could have changed, normally repository HEAD/ref/cleanliness plus the one or two runtimes the task actually needs. Do not repeatedly reinstall Composer/npm dependencies, rerun full environment diagnostics, or repeat broad test suites when the same state is directly reusable.

If retained state is lost, rebuild only the missing capability. `ENVIRONMENT EXISTS != CAPABILITY PROVEN`, but equally `NEW TASK != FULL PREFLIGHT REQUIRED`.

## Attachment semantics — folder selection is individual-file transport
Verified user-observed behavior on `2026-09-05`; reverify if the UI changes:

When the Owner selects a folder for attachment, Google AI Studio receives the folder's contents as individual attached files rather than one browsable folder/package object. Therefore operational prompts MUST:
- refer to `all attached files` or exact filenames, not assume a mounted folder path;
- not instruct the agent to browse a Drive folder that it cannot access;
- not depend on attachment hierarchy surviving as a directory tree;
- make the read order explicit by filename when order matters;
- treat transport copies as convenience evidence only; canonical originals remain in Drive/GitHub.

## Execution economy — execution first, validation last
Once environment + authority are proven, scarce agent capacity should be spent on real implementation, not redundant proof.

Default prompt allocation:
- majority: exact objectives, priority order, concrete defects/features to implement, preserved value, writeScope/prohibitedScope, completion criteria;
- minority: targeted validation, evidence, publication handoff.

Execution rule:
1. start with the highest-value correctness/root-cause work;
2. continue through all safe independent in-scope work; do not stop after the first fix, first PASS, or first finding;
3. use targeted tests while developing only when they help correctness;
4. run broader regression checks after a meaningful work block and near handoff, not after every micro-edit;
5. capture visual evidence at meaningful milestones/final candidate, not on every small CSS change;
6. do not let verification become the primary task unless the task itself is assurance/validation.

Canonical ledger rows are an execution backlog, not 1:1 verification ceremonies. Resolve actionable rows by implementation; hand off only genuinely shared/authority-gated/evidence-only rows.

## Limitation adjudication — do not accept shallow limitations, do not over-investigate
A printed tool limitation is evidence, not automatically an irreducible blocker.

If a reported limitation blocks meaningful execution, allow one bounded product-neutral recovery attempt that tests the underlying capability rather than the failed convenience mechanism. Examples:
- package/service-manager failure may still leave usable binaries;
- systemd failure does not prove the server process cannot run manually;
- missing Docker does not matter when the required service can run natively;
- a UI sync status does not prove the Git remote state.

If the limitation does not block the current work, do not spend a full execution cycle trying to eliminate it.

## PostgreSQL in AI Studio — verified user-space recovery pattern
CEP execution on `2026-09-05` proved a real PostgreSQL 15.19 server can run inside the AI Studio Linux sandbox without Docker or systemd.

Observed successful pattern:
- server binaries were already unpacked under `/usr/lib/postgresql/15/bin` despite a `postgresql-common`/`/var/log/postgresql` package-service failure;
- binaries were exposed through user-space paths;
- a cluster was initialized outside the repository under `/app/.cep-postgresql/`;
- PostgreSQL was started with `pg_ctl` as a normal user-space process;
- bound only to `127.0.0.1` on a non-conflicting port (`55432` in the verified CEP environment);
- disposable databases ending in `_test` satisfied the repository destructive-database guard;
- PDO, Laravel DB access, migrations, Unit, Integration, Architecture, Repository Safety and representative Feature tests all ran against real PostgreSQL.

Operational lesson:
`SYSTEMD_OR_PACKAGE_SCRIPT_FAILURE != POSTGRESQL_CAPABILITY_FAILURE`.

On the same retained environment, prefer a lightweight startup/readiness check of the existing cluster. Do not reinstall PostgreSQL unless direct inspection proves the cluster/binaries were lost. Paths/ports are environment-specific examples, not universal constants.

## GitHub authentication and publication — late bind credentials
Public read/fetch does not require write credentials. Do not spend the implementation window creating/testing push credentials before there is a real candidate to publish.

Preferred sequence:

```text
IMPLEMENT
→ TARGETED VALIDATION
→ FINAL DIFF/SCOPE REVIEW
→ LOCAL COMMIT/ARTIFACT
→ ONLY THEN BIND WRITE CREDENTIAL
→ ONE AUTHORIZED NON-FORCE PUSH/SYNC
→ GITHUB REMOTE-REF READBACK
```

Platform-native GitHub integration may be used when reliable, but its UI status is not authority. If native sync is unavailable or unreliable, a repository-scoped SSH Deploy Key is an acceptable publication mechanism when authorized.

For a reusable Owner-managed Deploy Key pattern:
- generate/retain one Owner-controlled keypair rather than generating a new keypair inside every temporary environment;
- register only the public key on the intended repository/account surface with the required write scope;
- inject the private key only at the publication gate, outside the repository, with restrictive file permissions;
- never commit the key, put it in tracked `.env`, or embed it in source/task artifacts;
- do not perform dummy pushes merely to prove credentials;
- never blind-retry an uncertain push; inspect GitHub authoritative post-state first.

## Repository and dependency discipline
Before product mutation verify only what is needed:

```text
git rev-parse --show-toplevel
git status --short --branch
git rev-parse HEAD
git remote -v
```

Install dependencies from lockfiles only when missing. Do not mutate lockfiles/source as a side effect of environment setup unless explicitly authorized. Platform metadata files outside the task should remain untracked and uncommitted.

## Visual/runtime evidence
For user-facing/reference-driven work, implementation remains the main objective. After a material visual milestone or review-ready candidate, compare the exact candidate against governed references under current PORT-METHOD-032/033. Tests do not close visual findings. Do not fake semantic/data gaps with CSS or fabricated canonical truth.

## Candidate publication
After authorized publication, GitHub remains technical truth. Read back exact remote ref/full SHA and changed paths before Controller review.

```text
AI STUDIO EXECUTION
→ TARGETED VALIDATION
→ LOCAL CANDIDATE
→ AUTHORIZED GITHUB SYNC/PUSH
→ REMOTE-REF READBACK
→ FREEZE FULL SHA
→ CONTROLLER REVIEW
```

`PUSH != ACCEPTANCE != MERGE`.

## Deployment boundary
Cloud Run deployment, public sharing, merge, release and final acceptance are separate authority gates. Never perform them merely because the provider exposes a button.

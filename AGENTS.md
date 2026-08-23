# CEP Repository Execution Contract

Status: **canonical repository execution governance**.

This repository implements the owner-approved Cybersecurity Education Platform (CEP). The governing product/visual decisions are retained in Google Drive; repository executors must not rewrite those decisions or treat legacy pages as product authority.

## Mandatory reading order

Before changing code, read in this order:

1. `AGENTS.md`.
2. `CONTRIBUTING.md`.
3. `docs/governance/PARALLEL_EXECUTION_MODEL.md`.
4. `docs/governance/GITHUB_GOVERNANCE_AND_RULESET.md`.
5. `docs/development/TESTING_AND_QUALITY_GATES.md`.
6. Only the architecture/module files named by the active Workstream Contract.
7. The Controller-supplied approved product/visual authority references for that workstream.

Stop reading unrelated historical task folders once the active workstream has enough direct authority. Historical Task/VS documents are evidence and reuse inputs, not permission to restore old UX or semantics.

## Current product authority references

The Controller must provide the applicable accepted authority. Current accepted identifiers are:

- `CEP v0.3.1` preserved product architecture baseline.
- `CEP-PRD-001-A01 — APPROVED`.
- `CEP-PRD-001-A02 — APPROVED`.
- `CEP-PRD-001-A03 — APPROVED`.
- `CEP-VIS-001-FINAL — APPROVED — CEP-DEC-027`.

Do not copy or modify the canonical Drive records in the repository. Use them as external governing references supplied by the Controller.

## Real application rule

All implementation work is for the **real runnable application**. Do not deliver image mockups, disconnected prototypes, static HTML demos, fake-only dashboards, or hardcoded screenshot reconstructions as completion evidence.

A UI workstream must connect to real Laravel/Inertia application routes and real domain/application state appropriate to its scope, with tests. Synthetic seed/test data is allowed; fake product behavior presented as implemented truth is not.

## Architecture invariants

- One deployable Laravel modular monolith for V1.
- Vue 3 + TypeScript + Inertia in the same deployable application.
- PostgreSQL is the primary database.
- Local-first, single-owner product. Public repository visibility is code visibility only and does not authorize public registration, SaaS, multi-tenancy, public or cloud deployment, automatic AI providers, external simulation execution, production security connectors, or other runtime expansion.
- Manual AI Bridge remains manual-only; no provider API, credentials, polling, embeddings, or autonomous publication.
- No real offensive execution connectors, SSH/WinRM/cloud execution, VM/hypervisor orchestration, or production security connectors in V1.
- Published/history-sensitive truth is revisioned or immutable; never overwrite accepted evidence, review decisions, run results, audit truth, or published revisions in place.
- Canonical objects are not duplicated merely because they appear in another workspace.

## Visual / interaction invariants

The accepted visual contract is external authority. At minimum, every implementation must preserve:

- `TOP = current tools / workflow actions`.
- `LEFT = structure / navigation only`.
- `CENTER = primary work surface`.
- `RIGHT = unique contextual information only`.
- `BOTTOM = temporary deep workspace, closed by default`.
- One information item has one authoritative permanent display location.
- Arabic-first UI with correct mixed RTL/LTR behavior.

Do not recreate image-generator typography defects. UI strings must be application-controlled text.

## Parallel branch model

During the active CEP real-application build program:

- `main` remains the canonical released/integrated branch.
- `build/cep-v1-integration` is the controlled integration branch for parallel implementation waves.
- Each Builder chat owns exactly one workstream branch created from the recorded integration baseline.
- Builder PRs target `build/cep-v1-integration`, not `main`.
- Only the Controller may authorize the final integration PR from `build/cep-v1-integration` to `main`.
- Never push directly to `main` or the integration branch.
- Never force-push or rewrite shared history.

## Shared-path ownership

Only the workstream explicitly assigned as Shared Foundation may modify shared shell/framework paths such as:

- global application shell/layouts;
- global navigation composition;
- shared design tokens/components;
- generic route-registration infrastructure;
- cross-domain contracts used by more than one workstream.

Domain workstreams must remain in their assigned module, route, page, test, and migration boundaries. If a domain needs a shared-path change, stop and request a Controller-coordinated dependency change instead of editing the shared path concurrently.

## Module boundary rule

The machine-readable dependency registry remains `planning/task006/MODULE_DEPENDENCIES_LOCKED.tsv` until an explicitly approved technical architecture change updates it. If a workstream changes module ownership or dependencies, update the registry, `config/platform.php`, the governing architecture rationale, and architecture tests together.

No coordinator, controller, job, or listener may bypass module ownership by importing another module's ORM models directly when an application contract should be used.

## Database and migrations

- Use PostgreSQL semantics; do not validate database behavior against SQLite.
- Migrations must be additive/reversible or have a documented restore strategy.
- A workstream owns only migrations for its bounded domain unless the Workstream Contract states otherwise.
- Do not silently rename or reinterpret legacy identifiers required for historical truth.
- Canonical import/migration of B09/B10 or `MISSION-007-C01` remains prohibited until separately authorized.

## Tests and evidence

Every workstream must add or update appropriate automated tests and run the repository-controlled gates applicable to its scope. GitHub-hosted Actions are the acceptance evidence authority; local runs are diagnostic.

Never turn `FAIL`, `BLOCKED`, `SKIPPED`, `CANCELLED`, `INCOMPLETE`, or a missing check into `PASS`.

Execution Handoff must reference:

- repository;
- branch;
- commit SHA;
- PR;
- changed paths/diff;
- tests and GitHub Actions runs;
- browser/runtime evidence when applicable;
- limitations;
- reviewer entry point;
- exact stop state.

Do not commit generated evidence bundles, screenshots, logs, review packets, `.env`, credentials, database dumps, private source files, `vendor`, or `node_modules`.

## Prohibitions

An Executor must not:

- update canonical Google Drive state;
- self-approve its PR or gate;
- merge its own workstream;
- expand scope into another Builder branch;
- change product architecture or the accepted visual contract silently;
- replace real application behavior with a prototype;
- claim CI/runtime/visual acceptance without direct evidence;
- modify `main`, release, or deploy unless a later explicit Controller/owner authorization says so.

## Stop conditions

Stop and return to the Controller when:

- required authority is missing or contradictory;
- a shared-path change is required but not assigned;
- a module dependency or canonical ownership change is needed;
- a migration/import crosses the authorized boundary;
- the baseline branch/commit differs from the Workstream Contract;
- required tests cannot run or a required gate fails;
- the work would require real external execution/connectors;
- the workstream reaches its stated Stop Gate.

<!-- CEP_EXECUTION_REFERENCE_MIRROR:BEGIN -->
## CEP Execution Reference Mirror

Authorized exception: `docs/reference/cep/` is a `NON_CANONICAL_EXECUTION_REFERENCE_MIRROR` published
for GitHub-native executors and reviewers. It is not a second source of product truth, current state,
or acceptance evidence. Google Drive remains canonical for the visual/reference library.

For UI/product/reviewer work involving CEP visual references:
- read `docs/reference/cep/README.md`;
- read `docs/reference/cep/AI_RENDERING_EXCLUSIONS.md`;
- read the applicable per-reference note before using an image;
- use images for structure, hierarchy, information ownership, interaction intent, density, and visual grammar;
- never copy AI-generation artifacts, fake data, malformed glyphs, duplicate/impossible controls, or accidental geometry/spacing;
- resolve conflicts in favor of approved product architecture, real application semantics, the approved visual contract, then per-reference notes, then the image;
- never treat visual similarity alone as acceptance evidence.

Committed files under this one path are governed reference assets, not runtime screenshots, transient
test evidence, CI artifacts, or executor handoff bundles.
<!-- CEP_EXECUTION_REFERENCE_MIRROR:END -->

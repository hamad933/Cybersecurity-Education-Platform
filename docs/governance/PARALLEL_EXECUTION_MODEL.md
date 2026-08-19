# CEP Parallel Execution Model

Status: **canonical repository execution governance for the real-application build program**.

## 1. Purpose

Enable multiple Builder chats to implement the real CEP application concurrently without competing ownership, silent architecture drift, cross-branch overwrites, or concurrent mutation of the same parent feature branch.

The stable model has five canonical parent domain branches. A Controller may optionally authorize bounded child-branch / child-PR fan-out beneath a parent when that reduces critical-path time without weakening ownership or review control.

## 2. Canonical parent branch topology

The five-parent topology remains canonical:

```text
main
└── build/cep-v1-integration
    ├── feat/cep-shared-foundation
    ├── feat/cep-knowledge-learning
    ├── feat/cep-simulation-enterprise
    ├── feat/cep-progress-evidence
    └── feat/cep-system-operations
```

Parent Builder branches start from the exact integration baseline recorded by their Workstream Contracts.

Parent Builder PRs target:

```text
build/cep-v1-integration
```

Only the Controller may authorize integration into `main` after the integration branch has passed the required gates and owner/controller review.

### 2.1 Optional bounded child fan-out

A parent workstream may add a temporary child layer only when the Controller issues a Workstream Contract that explicitly authorizes the fan-out. The child layer does not create new canonical domains and does not replace the five parent branches.

Example shape:

```text
build/cep-v1-integration
└── feat/cep-knowledge-learning            # canonical parent branch
    ├── fanout/cep-w02-library-editor-c01  # optional child branch
    └── fanout/cep-w02-learn-assessment-c01
```

Every authorized child must obey all of the following:

1. **Exact frozen parent HEAD.** The child branch is created from the exact parent commit recorded in its Workstream Contract, not merely from the current branch name.
2. **One writer, one child branch.** Each child writer owns exactly one child branch and one bounded write set.
3. **Child PR targets parent.** The child pull request targets the relevant parent feature branch. It does not target `build/cep-v1-integration` or `main` directly.
4. **Parent is read-only to child writers.** Child writers never push or otherwise mutate the parent feature branch directly. Concurrent child work must remain isolated on child branches.
5. **No concurrent mutation of the same parent ref.** Multiple writers must not race to advance the same parent branch/HEAD.
6. **Disjoint write sets preferred.** Child write sets should be disjoint whenever practical. Shared critical surfaces should not be split merely to maximize parallelism.
7. **Overlap requires serialization.** If children must touch overlapping shared files, that overlap is serialized or assigned to a Controller-designated parent/domain integration writer for bounded reconciliation on its own authorized branch/write set. That role does not receive merge authority.
8. **Exact-state recovery before substantial writes.** Before every substantial write, the child writer recovers the live parent state and the child branch state needed to verify the expected exact HEAD/preconditions.
9. **Moved parent means stop.** If the live parent HEAD differs from the frozen parent HEAD in the Workstream Contract, the child writer stops with `CONTROLLER REBASELINE REQUIRED`. It does not silently adopt the newer parent.
10. **Unknown writes are recovered, not retried blindly.** If a write was sent but its result was not confirmed, set `WRITE_OUTCOME = UNKNOWN`, recover live GitHub state, prove whether it landed, and only then decide whether retry is safe.
11. **Controller integration authority.** Only the Controller may authorize integration of a child PR into its parent branch. Child writers and other Executors do not merge child PRs. Any designated integration writer may prepare a bounded reconciliation candidate only; final integration remains outside Executor authority.
12. **Acceptance boundaries remain distinct.** A child merge is only bounded composition into the parent branch; it is not parent-domain acceptance. Parent-domain acceptance is not integration-branch acceptance. Integration-branch acceptance is not `main` acceptance, release authorization, or deployment authorization.
13. **No Drive writes or self-approval.** Child writers do not update canonical Google Drive state and do not approve their own work or gates.
14. **Repository governance is the stable rule source.** Workstream Contracts may supply more specific execution details for a bounded fan-out, but do not silently change approved product/visual architecture or grant broader authority.

A current Workstream Contract may therefore authorize bounded fan-out more specifically than this generic section. The more specific Contract governs the bounded execution detail, while these repository-level safety and authority boundaries remain in force unless explicitly superseded by a separate authority decision.

## 3. Workstream ownership

### A. `feat/cep-shared-foundation`

Owns:

- global CEP shell and five-destination navigation;
- Today / orchestration foundation;
- shared layouts and design tokens;
- shared UI primitives;
- global RTL/LTR infrastructure;
- shared route-registration mechanism;
- cross-workspace temporary/context workspace primitives;
- shared browser/accessibility contract tests.

Must not implement domain workflows owned by the other branches.

`CEP-BUILD-001-W01` remains the sole owner of global shell/navigation/shared UI infrastructure unless the Controller explicitly transfers a specific path or bounded shared-file change. Child fan-out under another parent does not inherit authority over W01-owned shared surfaces.

### B. `feat/cep-knowledge-learning`

Owns product implementation for:

```text
Knowledge & Learning
→ Library
→ Learn
→ Visualize
→ Research & Quality
```

Primary legacy module reuse candidates:

```text
MOD-SRC
MOD-KNO
MOD-CUR
learning-journey portions of MOD-LRN
```

Does not own formal Evidence Review, Mastery governance, Simulation runtime, or System operations.

### C. `feat/cep-simulation-enterprise`

Owns:

```text
Simulation & Enterprise
→ Enterprise
→ Scenarios
→ Labs
→ Runs
→ Results
```

Primary legacy module reuse candidates:

```text
MOD-ENT
MOD-SIM
```

Must preserve V1 internal high-fidelity simulation only. No real-runtime connector architecture.

### D. `feat/cep-progress-evidence`

Owns:

```text
Progress & Evidence
→ Evidence
→ Reviews
→ Mastery
→ Portfolio
```

Primary legacy module reuse candidates:

```text
MOD-EVD
mastery-governance portions currently located in MOD-LRN
```

The workstream may refactor mastery-governance code out of legacy learning services when required by the approved A03 ownership model, but must not change the product semantics.

### E. `feat/cep-system-operations`

Owns:

```text
System & Operations
→ Health
→ Processing
→ Validation
→ AI Bridge
→ Backup & Restore
→ Audit
→ Releases
→ Configuration
```

Primary legacy reuse candidates:

```text
MOD-PLT
MOD-AIB
Release Center primitives
```

Does not own knowledge-quality judgment or domain business workflows.

### Child ownership inheritance

A child writer receives only the bounded slice explicitly stated by its Controller-issued Workstream Contract. It does not acquire a new domain or alter canonical parent ownership. The child write set is a temporary partition of work inside the parent's authority, not a transfer of product architecture.

## 4. Conflict-avoidance rules

### Shared foundation protected paths

While `feat/cep-shared-foundation` is active, other parent branches and their child writers must not modify shared shell files unless the Controller explicitly transfers ownership of a specific path or bounded change. Examples include:

```text
resources/js/layouts/**
resources/js/components/shared/**
resources/css/** global tokens
bootstrap/app.php route-registration infrastructure
routes/web.php
shared navigation composition
shared i18n/RTL utilities
```

Exact paths may be refined by the Shared Foundation PR, but ownership must remain singular.

### Parallel-safe workspace route files

`routes/web.php` loads workspace route files mechanically. Each workspace file has exactly one canonical parent owner:

```text
routes/workspaces/today.php
→ CEP-BUILD-001-W01
→ feat/cep-shared-foundation

routes/workspaces/knowledge-learning.php
→ CEP-BUILD-001-W02
→ feat/cep-knowledge-learning

routes/workspaces/simulation-enterprise.php
→ CEP-BUILD-001-W03
→ feat/cep-simulation-enterprise

routes/workspaces/progress-evidence.php
→ CEP-BUILD-001-W04
→ feat/cep-progress-evidence

routes/workspaces/system-operations.php
→ CEP-BUILD-001-W05
→ feat/cep-system-operations
```

A child may edit its parent's route file only if its Workstream Contract assigns that exact file to the child write set and no sibling child concurrently owns it. Otherwise the parent/domain integration writer owns the reconciliation work on its own authorized branch/write set, without direct parent mutation or merge authority.

The existing root/dashboard behavior is temporarily preserved from `routes/workspaces/today.php` until W01 replaces it with the approved Today workspace. Existing Release Center endpoints are preserved in `routes/workspaces/system-operations.php` as `REFACTOR_FOR_REUSE` inputs for W05. Legacy `/vs001`, `/vs002`, `/vs003` routes remain in `routes/web.php` as reference/reuse surfaces and are not the target product IA.

### Domain-local paths

Each domain should prefer dedicated namespaces such as:

```text
app/Modules/<OwnedModule>/**
resources/js/pages/<Domain>/**
routes/workspaces/<assigned-workspace>.php
tests/Feature/<Domain>/**
tests/Integration/<Domain>/**
```

Do not edit another domain's files simply to avoid creating an application contract.

### Cross-domain contracts

When a domain requires another domain's information:

1. consume an existing application contract if present;
2. otherwise stop and request a Controller-owned contract change;
3. never import another module's ORM models as a shortcut;
4. never duplicate canonical data into a second domain store merely for display.

### Shared child files

Within one parent fan-out, files touched by more than one child are shared integration surfaces. The Controller must either:

- serialize those child changes;
- remove the file from all but one child write set; or
- assign the file to a designated parent/domain integration writer that prepares bounded reconciliation on its own authorized branch/write set and returns it for Controller review.

The designated integration writer does not mutate the parent branch directly and does not merge its own or another writer's PR.

Do not use repeated rebases, force-pushes, or competing edits to turn an ownership problem into an ad hoc merge problem.

## 5. Integration and acceptance order

Parallel development is allowed, but merge/integration order is controlled.

### Child to parent

Before any child composition, the Controller recovers live GitHub state for the parent target, child PR, exact child HEAD, expected frozen parent/base ancestry, changed paths, and relevant checks. A child whose reviewed HEAD changed must be re-verified before integration.

Recommended child composition sequence:

1. freeze the accepted child SHA;
2. verify parent target and ancestry;
3. verify changed paths remain inside the child write set;
4. verify sibling overlap has an explicit integration plan;
5. authorize integration only after Controller review;
6. verify the exact resulting parent HEAD after the authorized integration action;
7. run or await the parent-level checks required by the parent workstream.

Child writers and other Executors do not perform or authorize the merge action.

### Parent to integration branch

Recommended order into `build/cep-v1-integration` remains:

1. Shared Foundation contract slice.
2. Domain workstreams that do not conflict with the foundation diff.
3. Cross-domain integration corrections as separate bounded commits/PRs.
4. Full integration regression and real-browser validation.
5. Final integration PR to `main` only after Controller authorization.

A parent domain PR may remain open while another PR merges. Before parent merge, the domain branch must be brought current with the integration baseline as directed by the Controller and rerun required checks.

### Acceptance boundaries

The following are separate control events and must never be conflated:

```text
child integration into parent
!= parent-domain acceptance
!= parent PR integration into build/cep-v1-integration
!= integration-branch acceptance
!= main acceptance / release / deployment
```

No Executor receives implicit merge, `main`, release, or deployment authority from a successful lower-level gate.

## 6. Real-app evidence requirement

A domain workstream is not complete because a page renders. Required proof must include the real application route and real backend/application-state path for the implemented slice, plus appropriate automated tests.

Static mock screens, disconnected storybook-style surfaces, HTML prototypes, image reconstructions, and fake in-memory flows are not acceptance evidence.

## 7. Handoff contract

Every Builder or child writer stops with an Execution Handoff containing the applicable fields:

```text
WORKSTREAM
BASELINE
PARENT BRANCH              # child fan-out only
FROZEN PARENT HEAD         # child fan-out only
BRANCH
HEAD COMMIT
PR
CHANGED PATHS
MIGRATIONS
TESTS
CI RUNS
BROWSER/RUNTIME EVIDENCE
KNOWN LIMITATIONS
CROSS-WORKSTREAM DEPENDENCIES
WRITE_OUTCOME UNKNOWN      # only when applicable
STOP GATE
```

The handoff must identify the exact reviewer entry point and the next authority. The Builder or child writer does not merge and does not write Google Drive control state.

## 8. Controller responsibilities

The Controller:

- owns the integration baseline;
- preserves the five canonical parent domain branches;
- authorizes whether a parent workstream may use child fan-out;
- freezes the parent HEAD used by each child;
- resolves shared-path ownership and overlapping child write sets;
- reviews child and parent PR diffs and evidence;
- is the only authority that may authorize child-to-parent integration, parent-to-integration composition, and final integration progression;
- records meaningful control events in Drive;
- opens remediation workstreams when repository governance or architecture boundaries are insufficient;
- prepares the final integration/acceptance gate.

A Controller may designate a parent/domain integration writer to prepare reconciliation on a separate authorized branch/write set. That writer returns a bounded candidate for review and receives no merge, `main`, release, deployment, acceptance, or Drive authority.

## 9. Recovery rules

Exact live GitHub state is authoritative for mutable repository facts.

- Recover the required exact HEADs before every substantial write.
- If the expected parent or base moved, stop and request Controller rebaseline rather than silently adopting drift.
- If a write was attempted but acknowledgment is missing or ambiguous, record `WRITE_OUTCOME = UNKNOWN`; do not assume failure and do not retry blindly.
- Resolve an `UNKNOWN` outcome by rereading the target branch/PR/object and proving whether the mutation landed.
- After interruption or chat rotation, recover live state before continuing and do not repeat completed work unless live evidence requires it.

## 10. Stop rule

Stop the affected workstream and return to the Controller when:

- parallelism creates hidden coupling, shared-file collisions, duplicated canonical state, or conflicting migrations;
- a child requires a path outside its bounded write set;
- the parent HEAD moved from the child's frozen authority;
- two writers would concurrently mutate the same parent branch/ref;
- an overlapping shared file lacks serialization or a designated integration owner;
- a write outcome remains unresolved as `UNKNOWN`;
- required checks or authority are missing for the next integration step.

Parallel speed never overrides authority, exact-state safety, product correctness, or acceptance boundaries.

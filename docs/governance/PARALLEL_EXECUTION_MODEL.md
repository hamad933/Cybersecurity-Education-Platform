# CEP Parallel Execution Model

Status: **canonical repository execution governance for the real-application build program**.

## 1. Purpose

Enable multiple Builder chats to implement the real CEP application concurrently without competing ownership, silent architecture drift, or cross-branch overwrites.

## 2. Branch topology

```text
main
└── build/cep-v1-integration
    ├── feat/cep-shared-foundation
    ├── feat/cep-knowledge-learning
    ├── feat/cep-simulation-enterprise
    ├── feat/cep-progress-evidence
    └── feat/cep-system-operations
```

All five Builder branches start from the same recorded integration baseline commit.

Builder PRs target:

```text
build/cep-v1-integration
```

Only the Controller may authorize integration into `main` after the integration branch has passed the required gates and owner/controller review.

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

## 4. Conflict-avoidance rules

### Shared foundation protected paths

While `feat/cep-shared-foundation` is active, other branches must not modify shared shell files unless the Controller explicitly transfers ownership. Examples include:

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

`routes/web.php` loads workspace route files mechanically. Each workspace file has exactly one owner:

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

Builders modify only their assigned route file. They must not edit another Builder's route file or the shared loader in `routes/web.php`.

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

## 5. Integration order

Parallel development is allowed, but merge/integration order is controlled.

Recommended order into `build/cep-v1-integration`:

1. Shared Foundation contract slice.
2. Domain workstreams that do not conflict with the foundation diff.
3. Cross-domain integration corrections as separate bounded commits/PRs.
4. Full integration regression and real-browser validation.
5. Final integration PR to `main` only after Controller authorization.

A domain PR may remain open while another PR merges. Before merge, the domain branch must be brought current with the integration baseline and rerun required checks.

## 6. Real-app evidence requirement

A domain workstream is not complete because a page renders. Required proof must include the real application route and real backend/application-state path for the implemented slice, plus appropriate automated tests.

Static mock screens, disconnected storybook-style surfaces, HTML prototypes, image reconstructions, and fake in-memory flows are not acceptance evidence.

## 7. Handoff contract

Every Builder stops with an Execution Handoff containing:

```text
WORKSTREAM
BASELINE
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
STOP GATE
```

The Builder does not merge and does not write Google Drive control state.

## 8. Controller responsibilities

The Controller:

- owns the integration baseline;
- resolves shared-path ownership;
- reviews PR diffs and evidence;
- authorizes merge order;
- records meaningful control events in Drive;
- opens remediation workstreams when repository governance or architecture boundaries are insufficient;
- prepares the final integration/acceptance gate.

## 9. Stop rule

If parallelism begins to create hidden coupling, shared-file collisions, duplicated canonical state, or conflicting migrations, stop the affected workstream and resolve the dependency centrally. Parallel speed never overrides authority or correctness.

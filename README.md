# Cybersecurity Education Platform — CEP

This public GitHub repository is the canonical codebase for the real CEP application. Repository visibility exposes source code only; it does not authorize public CEP registration, SaaS, multi-tenancy, public or cloud deployment, automatic AI providers, external simulation execution, production security connectors, or any broader runtime authority. The target product is governed by the owner-approved CEP product architecture and the owner-approved `CEP-VIS-001-FINAL` Visual & Interaction Contract retained in Google Drive. Legacy VS-001/VS-002/VS-003 pages and task artifacts remain reuse candidates and evidence; they are not the target information architecture.

## Product implementation direction

CEP remains:

- local-first;
- single-owner for the current roadmap;
- Arabic-first with correct mixed RTL/LTR behavior;
- one Laravel modular monolith;
- Vue 3 + TypeScript + Inertia in the same deployable application;
- PostgreSQL-backed;
- governed by real application state and tests, not static prototypes.

Current accepted product/visual identifiers supplied by the Controller are:

- `CEP v0.3.1` preserved product architecture baseline;
- `CEP-PRD-001-A01 — APPROVED`;
- `CEP-PRD-001-A02 — APPROVED`;
- `CEP-PRD-001-A03 — APPROVED`;
- `CEP-VIS-001-FINAL — APPROVED — CEP-DEC-027`.

The canonical accepted documents remain in Drive; they are not duplicated here.

## Real-app rule

Implementation completion requires runnable Laravel/Inertia behavior, domain/application state, PostgreSQL-backed behavior where applicable, automated tests, and GitHub Actions evidence.

Image mockups, disconnected HTML demos, screenshot reconstructions, fake-only dashboards, and prototype-only flows are not implementation completion evidence.

## Parallel build model

The active real-application program uses:

```text
main
└── build/cep-v1-integration
    ├── feat/cep-shared-foundation
    ├── feat/cep-knowledge-learning
    ├── feat/cep-simulation-enterprise
    ├── feat/cep-progress-evidence
    └── feat/cep-system-operations
```

Read `AGENTS.md`, `CONTRIBUTING.md`, and `docs/governance/PARALLEL_EXECUTION_MODEL.md` before making changes. Builder pull requests target `build/cep-v1-integration`; only a Controller-authorized integration PR targets `main`.

## V1 safety boundaries

CEP V1 does **not** require or authorize:

- production security operations;
- live attack execution;
- SSH/WinRM/cloud execution connectors;
- VM/hypervisor/Kubernetes orchestration;
- automatic AI-provider integration;
- multi-tenant SaaS behavior.

The Manual AI Bridge remains manual-only. Simulation remains internal high-fidelity simulation unless a later owner decision changes the roadmap.

## Local start

Exact supported versions are controlled by `docs/development/TECHNOLOGY_VERSION_DECISION.md`, `composer.lock`, and `package-lock.json`.

```text
copy .env.example .env
php artisan key:generate
# replace local PostgreSQL placeholders in .env
composer setup
php artisan owner:create
composer start
```

Docker operation requires locally generated secrets. Do not commit `.env` or runtime credentials.

## Quality gates

`composer quality` is the repository-controlled local diagnostic command chain. Authoritative remote evidence is produced by:

- `.github/workflows/core-ci.yml`;
- `.github/workflows/release-verification.yml`;
- `docs/development/GITHUB_ACTIONS_EVIDENCE_MODEL.md`.

Required remote gates cover PHP, frontend, PostgreSQL integration/architecture tests, Compose validation, repository secret scanning, containerized release verification, and real Chromium browser evidence. No production deployment workflow is authorized.

## Legacy reuse

Existing modules, services, routes, migrations, tests, and vertical-slice behavior may be classified as:

```text
REUSE_AS_IS
REFACTOR_FOR_REUSE
REFERENCE_ONLY
REJECT
```

Do not preserve a legacy route, page, workflow, status model, or ownership boundary merely because it already exists.

## Governance

- `AGENTS.md` — canonical execution contract.
- `CONTRIBUTING.md` — branch, PR, evidence, and safety rules.
- `docs/governance/PARALLEL_EXECUTION_MODEL.md` — parallel Builder ownership and integration model.
- `docs/governance/GITHUB_GOVERNANCE_AND_RULESET.md` — GitHub settings and `main` protection recommendation.
- `docs/development/TESTING_AND_QUALITY_GATES.md` — command/check contract.

No Builder may self-approve, merge, release, deploy, or update canonical Drive state.

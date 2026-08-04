# Cybersecurity Learning and Operations Simulation Platform

This repository contains the canonical private source for the bounded local-first V1 runtime: one authenticated owner, the shared platform foundation, VS-001, VS-002, VS-003, and the integrated Release Center. The application remains an Arabic-first Laravel modular monolith with Vue/Inertia and PostgreSQL.

## Runtime and scope

Exact supported versions are controlled by `docs/development/TECHNOLOGY_VERSION_DECISION.md`, `composer.lock`, and `package-lock.json`. The V1 runtime provides deterministic educational simulations, governed knowledge/evidence workflows, safe package boundaries, local search and queue processing, audit chaining, backup staging, and an isolated restore drill.

It does **not** provide production security operations, live attack execution, production connectors, automatic AI-provider integration, Google Drive integration, multi-tenant SaaS behavior, or complete curriculum/runtime convergence.

## Quality gates

`composer quality` remains the repository-controlled local command contract. Authoritative remote verification is defined by:

- `.github/workflows/core-ci.yml`
- `.github/workflows/release-verification.yml`
- `docs/development/GITHUB_ACTIONS_EVIDENCE_MODEL.md`

The workflows use GitHub-hosted runners, locked repository dependencies, PostgreSQL 18.4, the release Compose topology, real Chromium, structured evidence artifacts, and truthful failure propagation. No production deployment is configured.

## Current status

- Three-slice V1 runtime and Release Center: **implemented local release candidate**.
- Canonical GitHub source baseline: commit `f257283de4a83054312d979d462c5de1d848bcb0` on `main` before CEP-GH-001 changes.
- Historical Task-010 browser result: **`BLOCKED_BROWSER_UNAVAILABLE`**, preserved unchanged in its historical records.
- Remote GitHub automation: introduced for owner review by CEP-GH-001; it is not accepted merely because workflow files exist.
- Production readiness and deployment authorization: **not granted**.
- Broader curriculum/runtime convergence, production connectors, automatic AI, and Google Drive integration: future bounded work only.

Repository governance and the exact recommended `main` Ruleset are documented in `docs/governance/GITHUB_GOVERNANCE_AND_RULESET.md`.

# Testing and quality gates

## Repository command contract

| Gate | Command | Purpose |
|---|---|---|
| setup | `composer setup` | Install locked dependencies, create ignored environment, generate key, migrate, and build |
| start / stop | `composer start`, `composer stop` | Docker Compose lifecycle |
| format | `composer format` | Pint and Prettier writes |
| lint | `composer lint` | Pint check, ESLint, Prettier check |
| analyse | `composer analyse` | Larastan/PHPStan level 6 |
| typecheck | `composer typecheck` | Vue TypeScript no-emit check |
| test | `composer test` | PHP Unit and Feature suites |
| test:integration | `composer test:integration` | PostgreSQL integration contracts |
| test:architecture | `composer test:architecture` | Module graph, historical preservation, and repository safety |
| security | `composer security` | Composer/npm advisories and repository fallback secret scan |
| compose:validate | `composer compose:validate` | Repository Compose policy checks |
| build | `composer build` | Production Vite assets |
| quality | `composer quality` | Full repository-controlled command chain |
| diagnose | `php artisan app:diagnose` | Non-sensitive foundation health checks |

PostgreSQL tests use a dedicated database ending `_test`; SQLite is not used. Migration, constraints, database queue, outbox, UUID, package, audit, backup, and active application paths are exercised by the relevant suites. Historical validators and evidence remain read-only unless a separately approved correction explicitly authorizes change.

## Authoritative GitHub checks

The required remote checks are:

1. `Core CI / PHP quality and tests`
2. `Core CI / Frontend quality and build`
3. `Core CI / Compose structural validation`
4. `Core CI / Repository secret scan`
5. `Core CI / Required gate summary`
6. `Release and Browser Verification / Containerized release verification`
7. `Release and Browser Verification / Real Chromium browser evidence`
8. `Release and Browser Verification / Required release gate summary`

Core CI validates manifests and locks, installs exact approved toolchains and locked dependencies, and runs each quality/test/security/build class separately. Release verification uses `compose.release.yaml`, fresh synthetic state, the real queue worker, safe backup export/stage, isolated restoration, health checks, and cleanup. Browser verification logs into the actual containerized application with real Chromium and captures runtime screenshots and diagnostics, not prototype images.

Every required result propagates truthfully. Artifacts are machine-readable, checksummed, retained for 14 days, and bound to commit SHA, run ID, and attempt. See `GITHUB_ACTIONS_EVIDENCE_MODEL.md` for the complete artifact and sanitation contract.

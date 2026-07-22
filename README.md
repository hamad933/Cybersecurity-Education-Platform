# Cybersecurity Learning and Operations Simulation Platform

Task 006 establishes a local-first, Arabic-first Laravel modular-monolith foundation. Only `MOD-IAM` and `MOD-PLT` contain executable module code. Product workflows, VS-001, automated AI, real-execution connectors, and multi-user features are intentionally absent.

## Local start

Requirements and exact versions are recorded in `docs/development/TECHNOLOGY_VERSION_DECISION.md`.

```text
copy .env.example .env
php artisan key:generate
# replace the local PostgreSQL placeholders in .env
composer setup
php artisan owner:create
composer start
```

Docker operation requires a locally generated `APP_KEY` and `DB_PASSWORD`; neither is committed. The web port binds to `127.0.0.1` only, and PostgreSQL has no host port.

## Gates

`composer quality` runs formatting checks, PHP static analysis, TypeScript checks, frontend and backend tests, PostgreSQL integration/architecture suites, security audits, secret scanning, and the production asset build. See `docs/development/TESTING_AND_QUALITY_GATES.md`.

## Status

External review status: **REVIEW CANDIDATE — NOT SELF-APPROVED**. Task 007 has not started.

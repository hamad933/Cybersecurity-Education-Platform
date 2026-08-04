# Contributing

This private repository uses review-first, evidence-backed changes. Respect the root `AGENTS.md`, approved scope, module ownership, historical decision records, and stop gates.

## Branches and commits

- Never implement directly on `main`.
- Branch from the exact approved baseline using `feat/`, `fix/`, `chore/`, `docs/`, `test/`, `refactor/`, `build/`, or `ci/` plus a short purpose.
- Use the matching Conventional Commit prefix. Keep commits bounded and never rewrite shared history or force-push.
- Do not mix dependency refreshes, product behavior, governance changes, and generated evidence without an explicit scope reason.

## Pull requests

Open a draft pull request to `main`, complete the repository template, and keep failed, blocked, skipped, cancelled, or incomplete checks named truthfully. Do not change accepted behavior merely to make CI green. Correct only verified defects and preserve historical records.

Required evidence comes from GitHub-hosted Actions. Local PHP, Composer, Node, npm, PostgreSQL, Docker, Chromium, tests, builds, or screenshots are diagnostic only and are not acceptance evidence.

## Security and repository safety

Never commit or attach `.env` files, credentials, application keys, passwords, cookies, private uploaded sources, database dumps, browser profiles, runtime storage, Git bundles, review-packet archives, `vendor`, or `node_modules`. Use synthetic data and ephemeral CI credentials. Security defects belong in a private security advisory, not an issue containing exploit details.

Database-dependent behavior must run against PostgreSQL. Module graph changes require the governing registries, rationale, architecture records, and tests to change together. Dependency changes require both manifests and lockfiles and must pass the full required-check set.

See `docs/governance/GITHUB_GOVERNANCE_AND_RULESET.md` and `docs/development/GITHUB_ACTIONS_EVIDENCE_MODEL.md`.

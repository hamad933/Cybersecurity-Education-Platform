# Contributing

This private repository uses review-first, evidence-backed changes. Respect the root `AGENTS.md`, the active Controller-issued Workstream Contract, approved scope, module ownership, historical decision records, and stop gates.

## Mandatory reading

Before implementation read:

1. `AGENTS.md`;
2. this file;
3. `docs/governance/PARALLEL_EXECUTION_MODEL.md`;
4. `docs/governance/GITHUB_GOVERNANCE_AND_RULESET.md`;
5. `docs/development/TESTING_AND_QUALITY_GATES.md`;
6. only the architecture/module files listed by the active Workstream Contract.

Do not use legacy task documents or old visual pages as authority for the approved target product unless the Controller explicitly classifies them for reuse.

## Branches and commits

- Never implement directly on `main`.
- During the CEP parallel real-application build program, Builder branches are created from the exact recorded `build/cep-v1-integration` baseline.
- Builder pull requests target `build/cep-v1-integration`; only the Controller-authorized integration PR targets `main`.
- Branch names use `feat/`, `fix/`, `chore/`, `docs/`, `test/`, `refactor/`, `build/`, or `ci/` plus a short purpose.
- Use the matching Conventional Commit prefix. Keep commits bounded and never rewrite shared history or force-push.
- Do not mix dependency refreshes, product behavior, governance changes, migrations from unrelated domains, and generated evidence without an explicit scope reason.

## Parallel work ownership

One workstream owns one branch and one bounded set of paths. Shared shell/framework paths have one assigned owner at a time. If another workstream needs a shared-path or cross-domain contract change, stop and return to the Controller rather than making a competing edit.

See `docs/governance/PARALLEL_EXECUTION_MODEL.md` for the canonical branch topology and domain ownership.

## Pull requests

Open a draft pull request to the workstream's authorized base branch, complete the repository template, and keep failed, blocked, skipped, cancelled, or incomplete checks named truthfully. Do not change accepted behavior merely to make CI green. Correct only verified defects and preserve historical records.

A page rendering is not sufficient completion evidence. Product work must exercise the real Laravel/Inertia application and real application/domain state appropriate to the scope. Static mockups, image reconstructions, disconnected prototypes, and fake-only dashboard flows are not accepted implementation output.

Required evidence comes from GitHub-hosted Actions. Local PHP, Composer, Node, npm, PostgreSQL, Docker, Chromium, tests, builds, or screenshots are diagnostic only and are not acceptance evidence.

## Security and repository safety

Never commit or attach `.env` files, credentials, application keys, passwords, cookies, private uploaded sources, database dumps, browser profiles, runtime storage, Git bundles, review-packet archives, `vendor`, or `node_modules`. Use synthetic data and ephemeral CI credentials. Security defects belong in a private security advisory, not an issue containing exploit details.

Database-dependent behavior must run against PostgreSQL. Module graph changes require the governing registries, rationale, architecture records, and tests to change together. Dependency changes require both manifests and lockfiles and must pass the full required-check set.

## Handoff and merge authority

Every Builder publishes an Execution Handoff with branch, commit, PR, changed paths, tests/CI, runtime/browser evidence where applicable, limitations, dependencies, and Stop Gate. Builders do not update canonical Drive state, approve their own work, or merge their own PRs.

See `docs/development/GITHUB_ACTIONS_EVIDENCE_MODEL.md` for evidence handling.

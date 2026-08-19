# Contributing

This public code repository uses review-first, evidence-backed changes. Repository visibility is code visibility only; it does not broaden CEP product, deployment, runtime, connector, or data-sharing authority. Respect the root `AGENTS.md`, the active Controller-issued Workstream Contract, approved scope, module ownership, historical decision records, and stop gates.

When a Controller-issued Workstream Contract is more specific than generic repository execution guidance, its bounded execution details take precedence. It does not silently replace approved product/visual authority or grant merge, release, deployment, or canonical Drive-write authority.

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
- During the CEP parallel real-application build program, the five canonical parent Builder branches are created from the exact recorded `build/cep-v1-integration` baseline.
- Parent Builder pull requests target `build/cep-v1-integration`; only the Controller-authorized integration PR targets `main`.
- A Controller-authorized bounded child writer creates its child branch from the exact frozen parent HEAD and opens its child PR against that parent feature branch.
- Branch names use `feat/`, `fix/`, `chore/`, `docs/`, `test/`, `refactor/`, `build/`, `ci/`, or `governance/` plus a short purpose.
- Use the matching Conventional Commit prefix. Governance-only changes normally use `docs` or `chore` as appropriate. Keep commits bounded and never rewrite shared history or force-push.
- Do not mix dependency refreshes, product behavior, governance changes, migrations from unrelated domains, and generated evidence without an explicit scope reason.

## Parallel work ownership

The five canonical parent domain branches remain the stable top-level workstream topology. One parent workstream owns one branch and one bounded set of domain paths.

A parent workstream may use optional child fan-out only when its Controller-issued Workstream Contract authorizes it. In that mode:

- one child writer owns one child branch and one bounded write set;
- the child starts from the exact frozen parent HEAD stated by the Contract;
- the child PR targets the parent feature branch;
- the child writer treats the parent feature branch as read-only and never mutates it directly;
- direct concurrent mutation of the same parent branch is forbidden;
- child write sets should be disjoint whenever practical;
- overlapping shared files are serialized or assigned to a Controller-designated parent/domain integration writer;
- before every substantial write, recover the exact live parent/child state required by the Contract;
- if the parent HEAD moved, stop for Controller rebaseline;
- if a write outcome is ambiguous, recover live GitHub state before retrying;
- only the Controller may authorize child integration; child writers do not self-merge or self-approve.

Shared shell/framework paths have one assigned owner at a time. `CEP-BUILD-001-W01` remains the sole owner of global shell, navigation, shared UI infrastructure, global styling/token infrastructure, and generic route-registration surfaces unless the Controller explicitly transfers a specific path. If another parent workstream or child writer needs a shared-path or cross-domain contract change, stop and return to the Controller rather than making a competing edit.

See `docs/governance/PARALLEL_EXECUTION_MODEL.md` for the canonical branch topology, child-fan-out contract, and domain ownership.

## Pull requests

Open a draft pull request to the workstream's authorized base branch, complete the repository template, and keep failed, blocked, skipped, cancelled, or incomplete checks named truthfully. Parent Builder PRs target `build/cep-v1-integration`; authorized child PRs target their parent feature branch. Do not retarget a child PR directly to `build/cep-v1-integration` or `main`.

A child PR merge, when separately authorized by the Controller, only integrates the bounded child change into the parent branch. It is not parent-domain acceptance. Parent-domain acceptance is not integration-branch acceptance, and integration-branch acceptance is not `main` acceptance.

Do not change accepted behavior merely to make CI green. Correct only verified defects and preserve historical records.

A page rendering is not sufficient completion evidence. Product work must exercise the real Laravel/Inertia application and real application/domain state appropriate to the scope. Static mockups, image reconstructions, disconnected prototypes, and fake-only dashboard flows are not accepted implementation output.

Required evidence comes from GitHub-hosted Actions. Local PHP, Composer, Node, npm, PostgreSQL, Docker, Chromium, tests, builds, or screenshots are diagnostic only and are not acceptance evidence.

## Security and repository safety

Never commit or attach `.env` files, credentials, application keys, passwords, cookies, private uploaded sources, database dumps, browser profiles, runtime storage, Git bundles, review-packet archives, `vendor`, or `node_modules`. Use synthetic data and ephemeral CI credentials. Security defects belong in a private security advisory, not an issue containing exploit details.

Database-dependent behavior must run against PostgreSQL. Module graph changes require the governing registries, rationale, architecture records, and tests to change together. Dependency changes require both manifests and lockfiles and must pass the full required-check set.

## Handoff and merge authority

Every Builder or child writer publishes an Execution Handoff with branch, commit, PR, changed paths, tests/CI, runtime/browser evidence where applicable, limitations, dependencies, and Stop Gate. A child handoff also records its parent branch and frozen parent HEAD.

Builders and child writers do not update canonical Drive state, approve their own work, or merge their own PRs. Only the Controller may authorize integration of child PRs, parent domain PRs, the integration PR, or any later release/deployment step. Executors receive no implicit merge, `main`, release, or deployment authority.

See `docs/development/GITHUB_ACTIONS_EVIDENCE_MODEL.md` for evidence handling.

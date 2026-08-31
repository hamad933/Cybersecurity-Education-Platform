# AGENTS.md — CEP Repository Governance

Status: ACTIVE REPOSITORY GOVERNANCE
Scope: entire repository unless a future nested `AGENTS.md` explicitly narrows rules for its subtree.

## Authority and truth ownership

- GitHub is the technical truth owner for repository identity, code, branches, commits, pull requests, diffs, CI, and technical history.
- Governed project state, decisions, gates, accepted evidence, and continuity are owned outside the repository by the current `PERSONAL:CEP` control state in the portfolio Drive.
- Runtime/executor output is evidence only. Printed success is not verified success.
- Before state-dependent or write-capable work, reconstruct the current task authority and exact baseline rather than relying on chat, memory, old task text, screenshots, cached SHAs, or session labels.

## Writer contract

Every writer must have an explicit task/workstream contract defining:
- objective;
- exact baseline/ref;
- owned `writeScope`;
- prohibited/reserved scope;
- validation/tests;
- evidence handoff;
- dependencies;
- Stop Gate.

One writer owns a write domain at a time. Do not widen scope because a tool can edit more paths. If a required change is outside the authorized scope, return it as a dependency unless the Controller explicitly reserves that ownership.

Never directly mutate a frozen reviewed PR head unless current authority explicitly says otherwise. Do not force-push, rewrite history, merge, release, deploy, publish, or perform destructive actions without explicit authority.

`PUSH != ACCEPTANCE != MERGE`.

## Repository safety

- Never commit `.env`, credentials, tokens, private keys, provider secrets, cookies, runtime data, source-vault material, temporary Drive downloads, executor helper files, scratch notes, generated handoffs, or vendor dependencies.
- Keep provider/task bootstrap material outside the product repository unless a task explicitly owns a repository path for it.
- Do not use blind retries after an unknown write outcome. Read the authoritative post-state first.
- Preserve exact branch/SHA evidence for material writes and verify remote refs after publication.
- Keep changes minimal to the owned domain; do not perform opportunistic unrelated refactors.

## Architecture and database validation

Respect the practical modular-monolith boundaries and the machine-readable dependency registry. Update boundary metadata only when the active task explicitly authorizes a module-boundary change.

Database-dependent behavior must be verified on PostgreSQL. SQLite is not accepted as a substitute for PostgreSQL semantics.

Before review, run the task-targeted checks plus `composer quality` when the task/runtime supports it. If a required check cannot run, report `NOT_RUN` with the direct reason rather than claiming PASS.

## Controller and Jules infrastructure

Files under `.github/workflows/cep-jules-*.yml` are Controller infrastructure, not product acceptance authority.

Jules Stage B sessions are reviewable workspaces. Unless a current task explicitly authorizes publication, Stage B must not automatically commit, push, open a PR, merge, release, or deploy.

For Jules control:
- `LOGICAL_TASK != PROVIDER_SESSION`;
- replan/correct inside the same recoverable session;
- do not create replacement sessions merely to improve a plan;
- a replacement session requires direct proof of an irrecoverable provider/input/isolation condition;
- preserve one writer per write domain across local, Codex, Jules, and other executors.

## Jules Google Drive bootstrap

The canonical Jules↔Drive access method is `00_JULES_DRIVE_ACCESS_ATLAS_v1.0`, Drive ID `1P272cptZ7C8lZf98aY0WODrNsPaq6EzgTcIUVEKMV_s`.

When a task references a Google Drive file/folder ID, Drive-owned authority/state/evidence, or a governed visual reference:
- use the pre-provisioned `/home/jules/.local/bin/jules-drive` capability and read-only Drive credentials;
- fetch the canonical Atlas first, then resolve only the minimum required authoritative files/assets for the task route;
- keep Drive helpers/downloads in `/tmp` or the configured temporary cache and never stage them in the repository;
- if visual/layout/interaction/Bidi fidelity is material, writers/correctors as well as reviewers may fetch and inspect the exact governed reference images;
- return a bounded non-secret Drive access receipt identifying materially used file IDs/freshness and visual references opened when applicable;
- if `jules-drive`, Drive auth, or a required source is unavailable, report `DRIVE_ACCESS_UNAVAILABLE` (or the exact missing-source state) and do not claim the source was inspected.

A Drive ID in a prompt is not proof of access. Writer inspection of a reference image is useful implementation grounding but is not final visual acceptance; final adjudication requires fresh exact-candidate runtime/screenshots compared against the exact governed reference.

Controller review must use direct evidence when available: exact plan, activity/changeSet patch, changed paths, tests/command outputs, runtime/browser evidence, branch SHA, PR state, and CI state. Agent summaries are supporting evidence, not acceptance decisions.

## Stop rule

Continue safe independent work inside the authorized objective until the task Stop Gate is reached or a genuine authority/scope decision is required. A blocked lane does not block unrelated authorized lanes.

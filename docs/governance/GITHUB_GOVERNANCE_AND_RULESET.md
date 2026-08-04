# GitHub governance and recommended Ruleset

Status: **repository-controlled recommendation; GitHub repository settings are not claimed as applied until verified in the GitHub UI or API**.

## Canonical workflow

- `main` is the protected canonical branch. No implementation commit is pushed directly to it.
- Work begins from an exact approved commit on a dedicated branch. Branch names use `type/short-purpose`, such as `chore/github-automation-foundation`.
- Pull requests remain draft until all required gates execute. A skipped, cancelled, timed-out, blocked, or incomplete gate is not a pass.
- Commits use Conventional Commit prefixes: `feat`, `fix`, `chore`, `docs`, `test`, `refactor`, `build`, `ci`, or `revert`. Each commit must be bounded, reviewable, and history-preserving.
- Generated evidence is never committed. GitHub Actions artifacts are the runtime evidence authority.

## Evidence naming and truthfulness

Artifacts use:

`Cybersecurity-Education-Platform-<commit-sha>-<run-id>-<attempt>-<evidence-class>`

Every artifact contains `gate-summary.json`, `ARTIFACT_MANIFEST.json`, and `SHA256SUMS.txt`. Evidence must not contain environment files, credentials, application keys, passwords, cookies, private uploaded sources, database dumps, Git bundles, review packets, dependency directories, browser profiles, or uncontrolled logs.

A required job fails when its gate fails. `continue-on-error` is not used to reinterpret a required failure. Informational jobs require an explicit policy and remain visibly informational.

## Dependency policy

- Composer and npm installations use committed lockfiles. Lockfiles are changed only in a dependency-specific pull request or an explicitly scoped product change.
- Runtime versions remain those recorded in `docs/development/TECHNOLOGY_VERSION_DECISION.md` until an approved version decision changes them.
- Dependabot opens weekly Composer, npm, and GitHub Actions pull requests. Minor and patch updates may be grouped; majors remain separately reviewed.
- Dependency updates must pass all required checks. Security advisories do not authorize silent behavior changes or lockfile bypasses.
- Workflow actions are referenced by full commit SHA. Container images use repository-authorized exact tags; future digest pinning may strengthen this without changing product behavior.
- Caching is disabled in the initial foundation. Any later cache must be read-only for untrusted pull requests and keyed by the relevant lockfile digest plus toolchain and runner identity.

## Recommended Ruleset for `main`

Create one branch Ruleset named `main-canonical-protection`, targeting the default branch, active immediately, with:

1. Restrict deletions and block force pushes.
2. Require a pull request before merging.
3. Require at least one approving review.
4. Dismiss stale approvals when new reviewable commits are pushed.
5. Require review from Code Owners.
6. Require all conversations to be resolved.
7. Require the branch to be up to date before merge.
8. Require linear history.
9. Require these status checks exactly:
   - `Core CI / PHP quality and tests`
   - `Core CI / Frontend quality and build`
   - `Core CI / Compose structural validation`
   - `Core CI / Repository secret scan`
   - `Core CI / Required gate summary`
   - `Release and Browser Verification / Containerized release verification`
   - `Release and Browser Verification / Real Chromium browser evidence`
   - `Release and Browser Verification / Required release gate summary`
10. Require deployments to succeed: **disabled**, because this repository has no authorized production deployment workflow.
11. Require signed commits: recommended after the owner confirms a usable signing method; do not enable it in a way that strands the canonical branch.
12. Bypass: repository owner only, for an emergency recovery action, with a linked decision record. Do not grant GitHub Apps or broad teams unconditional bypass.

Also enable private vulnerability reporting and keep repository visibility private. Auto-merge may remain disabled.

## One-time owner UI action

The connected repository tooling used for CEP-GH-001 can create branches, commits, workflows, and pull requests, but this implementation does not claim it applied a Ruleset. The owner must open **Settings → Rules → Rulesets → New branch ruleset**, enter the settings above, save it as Active, then verify the required-check names after their first completed runs. Open **Settings → Code security and analysis** and enable private vulnerability reporting if it is not already enabled.

## Rollback

Before merge, delete or close the draft pull request and delete its branch. After a later authorized merge, revert the merge commit through a new pull request; do not rewrite `main`. Repository settings are rolled back independently in the Ruleset UI and must not be represented as code rollback.

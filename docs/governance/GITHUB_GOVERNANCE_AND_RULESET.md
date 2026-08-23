# GitHub governance and recommended Ruleset

Status: **repository-controlled recommendation; GitHub repository settings are not claimed as applied until directly verified in the GitHub UI or API**.

## Repository visibility and product authority

The GitHub repository is public. Public repository visibility governs source-code visibility only; it does not authorize public CEP registration, SaaS, multi-tenancy, public or cloud deployment, automatic AI providers, external simulation execution, production security connectors, or any broader runtime authority.

Repository-native governance remains binding even when equivalent GitHub platform enforcement is absent or has not been verified. Do not describe `main`, the integration branch, required reviews, status checks, or a Ruleset as platform-enforced unless the current GitHub state has been directly verified.

## Canonical workflow

- `main` is the canonical branch. Direct implementation pushes to it are not authorized.
- Work begins from an exact approved commit on a dedicated branch. Branch names use `type/short-purpose`, such as `chore/github-automation-foundation`.
- Pull requests remain draft until all required gates execute. A skipped, cancelled, timed-out, blocked, or incomplete gate is not a pass.
- Commits use Conventional Commit prefixes: `feat`, `fix`, `chore`, `docs`, `test`, `refactor`, `build`, `ci`, or `revert`. Each commit must be bounded, reviewable, and history-preserving.
- Generated evidence is never committed. GitHub Actions artifacts are the runtime evidence authority.
- During the parallel build program, Builders must not write directly to `build/cep-v1-integration`; Controller-authorized PR integration is required.

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

## Single-owner review model

This repository is currently a public, user-owned, single-owner repository. The sole recorded Code Owner is `@hamad933`, who is also the author of owner-created pull requests. GitHub does not permit pull-request authors to approve their own pull requests.

Therefore, while the repository remains single-owner:

- a pull request is required by repository governance before `main` changes;
- required approving reviews are set to `0` / disabled in the recommended Ruleset;
- required Code Owner review is disabled in the recommended Ruleset;
- dismissal of stale approvals is disabled because no approval is required;
- approval from someone other than the last pusher is disabled;
- `CODEOWNERS` remains as an ownership and future-governance record, not as an impossible merge prerequisite.

If a second qualified maintainer with write access is added later, one required approval and Code Owner review may be enabled through a separately authorized governance change after confirming that the reviewer can satisfy the rule without bypass.

## Ruleset verification and enforcement state

GitHub Rulesets and branch protection are external platform settings. The presence of this document does not activate either mechanism.

Before claiming platform enforcement, verify directly in the GitHub API or **Settings → Rules → Rulesets** that an **Active** Ruleset applies to `main` and matches the approved configuration below. Also verify the branch's effective protection state and required-check names against completed workflow runs.

If no applicable Active Ruleset is returned, branch protection is disabled, or the setting cannot be verified, do not claim enforcement. Keep the bounded operational override in force:

- no direct push or merge to `main`;
- no direct Builder write to `build/cep-v1-integration`;
- Controller-authorized PR integration only;
- required CI results remain truthful and must not be weakened to compensate for missing platform enforcement.

Creation or activation of the Ruleset is an owner/platform action and must be verified after the setting is saved.

## Recommended Ruleset for `main`

Create one branch Ruleset named `main-canonical-protection`, targeting the default branch (`main`), with enforcement set to **Active** and the following exact configuration:

1. Restrict deletions: **enabled**.
2. Block force pushes: **enabled**.
3. Require a pull request before merging: **enabled**.
4. Required approving reviews: **0 / disabled**.
5. Dismiss stale approvals: **disabled**.
6. Require review from Code Owners: **disabled** while the repository remains single-owner.
7. Require approval of the most recent reviewable push: **disabled**.
8. Require all conversations to be resolved: **enabled**.
9. Require the branch to be up to date before merge: **enabled** (strict status-check mode).
10. Require linear history: **enabled**. Use squash or rebase integration; do not introduce merge commits on `main`.
11. Require these status checks exactly:
    - `Core CI / PHP quality and tests`
    - `Core CI / Frontend quality and build`
    - `Core CI / Compose structural validation`
    - `Core CI / Repository secret scan`
    - `Core CI / Required gate summary`
    - `Release and Browser Verification / Containerized release verification`
    - `Release and Browser Verification / Real Chromium browser evidence`
    - `Release and Browser Verification / Required release gate summary`
12. Require deployments to succeed: **disabled**, because this repository has no authorized production deployment workflow.
13. Require signed commits: **disabled** until the owner confirms and verifies a usable signing workflow that will not strand `main`.
14. Merge queue: **disabled**.
15. Auto-merge: **disabled**.
16. Routine bypass actors: **none**.

For an exceptional recovery action, the repository owner may temporarily alter or disable the Ruleset only with a linked decision record, perform the minimum recovery action, and restore the approved Ruleset. Do not configure an unconditional day-to-day bypass for the owner, GitHub Apps, or broad teams.

## Security reporting alignment

The repository is public, but vulnerability handling remains private. Security concerns must be disclosed privately to the repository owner or handled through a GitHub repository security advisory when available and authorized. Credentials, session material, private uploaded source content, database dumps, and usable exploit details must never be posted in public issues or pull-request discussions.

GitHub Private Vulnerability Reporting may be evaluated for this public repository as a separate owner/platform setting. Do not claim it is enabled until its current state has been directly verified.

## Owner/platform action

1. Verify the current Rulesets API or **Settings → Rules → Rulesets** state for this public repository.
2. If `main-canonical-protection` is absent or inactive, create or activate it with the exact settings above.
3. Verify the eight required-check names against completed workflow runs before relying on the Ruleset.
4. Verify effective `main` protection after activation; do not infer protection from repository documentation.
5. Treat Private Vulnerability Reporting as a separate optional security setting and verify it independently before making any claim about its state.

These settings are not represented as applied until verified in GitHub after activation.

## Rollback

Before merge, close the draft pull request and delete its branch if the proposed governance remediation is rejected. After a later authorized squash or rebase integration, revert the resulting integration commit or commits through a new pull request; never rewrite `main`.

Repository settings are rolled back independently through the Ruleset UI or API and must not be represented as code rollback.

# Security scanning

No trusted `gitleaks` installation or Docker engine was available. Task 006 therefore implements the prompt's third-choice deterministic fallback in `scripts/secret_scan.php` and labels it `LIMITED_FALLBACK_SECRET_SCAN`.

It scans tracked plus non-ignored worktree files, Git revisions created in this repository, configuration templates, Docker/Compose files, common private-key/token forms, assigned secret fields, and explicit `.env` tracking. It excludes dependencies, compiled assets, runtime storage, and the handoff staging directory. Findings report only path and rule name, never matched values.

Known limitation: deterministic patterns do not provide the entropy rules, allowlist corpus, or provider coverage of gitleaks. A future environment with Docker may run a pinned official gitleaks container in addition to—not instead of—the committed fallback.

Dependency gates are `composer audit --locked` and `npm audit --audit-level=high`. `.env`, database data, runtime logs, sessions, caches, browser profiles, dependencies, and generated handoffs are ignored and prohibited from the review ZIP.

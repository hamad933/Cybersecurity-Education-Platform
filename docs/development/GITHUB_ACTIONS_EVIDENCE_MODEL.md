# GitHub Actions execution and evidence model

GitHub-hosted runners are the authoritative runtime for remote CI. Local PHP, Node, PostgreSQL, Docker, browser, test, build, and screenshot results are not substitutes for these workflow conclusions.

## Workflows

### Core CI

- PHP 8.5.8 and Composer 2.10.2 with verified Composer PHAR digest.
- PostgreSQL 18.4 service database ending `_test`.
- Composer validation and locked installation.
- Pint, PHPStan/Larastan, Unit, Feature, Integration, Architecture, repository-safety, Composer audit, and repository fallback secret scan.
- Node 24.18.0 and npm 11.16.0.
- `npm ci`, Prettier, ESLint, Vue/TypeScript no-emit checking, Vitest, npm audit, and Vite production build.
- Docker Compose structural validation and pinned gitleaks repository scan.

### Release and Browser Verification

- Builds the repository release image and starts application, queue worker, and PostgreSQL through `compose.release.yaml`.
- Generates ephemeral application key, database password, and owner password. Values are masked, written only under the runner temporary directory, and deleted during unconditional cleanup.
- Runs fresh migrations and synthetic seeds, release readiness, an actual queued `FoundationSmokeJob`, safe backup export/staged import, and an isolated `_restore_drill` recovery exercise.
- Captures container state and sanitized logs, then destroys containers, volumes, temporary credentials, and the local image.
- Runs real headless Chromium against the actual release application. It performs login, visits the Release Center and principal VS-001, VS-002, and VS-003 pages at desktop and mobile viewports, and records screenshots, console errors, failed network requests, accessibility-tree findings, RTL/LTR facts, focus visibility, overflow, and HTTP security headers.
- When browser verification fails, diagnostic JSON, Chromium output, console/network evidence, and the last bounded DOM diagnostic are retained. Static prototypes are not runtime browser evidence.

## Artifact contract

Artifact retention is 14 days. Names bind repository, commit SHA, workflow run ID, attempt, and evidence class. Machine-readable files use UTF-8 JSON, XML, TSV, or checksum text. `ARTIFACT_MANIFEST.json` records each payload file and digest; `SHA256SUMS.txt` covers all artifact files except itself.

Artifacts are rejected before upload when paths or content suggest environment files, credentials, private keys, authorization headers, cookies, database dumps, private source vaults, review packets, browser profiles, dependency directories, or archives.

## Failure handling

Required jobs use explicit timeouts and concurrency cancellation. A job cancellation, infrastructure failure, failed assertion, failed audit, browser defect, or incomplete artifact remains visible in the workflow conclusion and aggregate summary. Corrective commits may repair verified defects on the same pull-request branch, but may not weaken an accepted product behavior or remove a gate merely to obtain green status.

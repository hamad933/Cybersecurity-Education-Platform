# Task-010 Local Release Runbook

## Prerequisites

- Windows PowerShell 5.1 or later.
- Docker Desktop with WSL2 integration.
- Existing clean repository at Task-009 checkpoint `83b932a079bf2237dbfa033a4322c6bded042842`.
- Local `.env` values remain outside the bundle and Git.

## Development verification

The Task-010 runner reuses the current development images when possible, installs only locked dependencies when the named dependency volume is missing, creates the isolated test database, runs targeted tests, then performs exactly one full release gate. A second full run is permitted only when the first full run exposes a verified defect that was repaired.

## Release startup

1. Copy `.env.release.example` to a local ignored `.env.release`.
2. Generate a Laravel `APP_KEY` and a random PostgreSQL password.
3. Run:

```powershell
Docker compose --env-file .env.release -f compose.release.yaml up -d --build
```

4. Run migrations and seeds once:

```powershell
Docker compose --env-file .env.release -f compose.release.yaml exec -T app php artisan migrate --force --seed
```

5. Evaluate readiness:

```powershell
Docker compose --env-file .env.release -f compose.release.yaml exec -T app php artisan platform:release-check --json
```

6. Open `http://127.0.0.1:8081` unless `APP_PORT` was changed.

## Backup and restore drill

- Web backup creates and catalogs a verified package.
- Web restore only stages and verifies.
- The included restore-drill script creates an isolated database ending in `_restore_drill`, migrates it, applies the package through `platform:restore-apply`, verifies counts/blobs/audit, records duration, and destroys the drill database unless evidence retention is requested.

## Rollback

- Stop the V1 Compose project.
- Preserve the latest verified backup package and review packet.
- Check out tag `task009-review-candidate` or the pre-Task-010 backup commit created by the runner.
- Start the previous Compose definition.
- Do not apply a V1 data downgrade to a live database. Restore the prior verified backup into a separate database and switch only after human verification.

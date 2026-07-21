# Deployment and Operations Baseline

## Local-first topology

The v1 release unit is one Laravel/Inertia application plus PostgreSQL, database-backed queue worker, and application-controlled local blob directory under Docker Compose in WSL2. Browser access is through a local address. Public Internet is not required for authentication, source review, publication, learning, simulation, evidence, search, export, backup, or restore.

Services bind to loopback by default. Remote/LAN access is deferred until an approved TLS, firewall, identity, privacy, and backup decision. Development conveniences are off in release mode and visibly signalled when active.

## Configuration and secrets

Task 006 chooses exact supported versions and locks images/dependencies. Configuration is typed, validated at startup, separated by development/test/release profile, and documented without secrets. Passwords/keys remain outside source control and structured logs. A development-only auth bypass, if ever created, defaults off, cannot activate in release, and produces an unmistakable banner/audit event.

## Processing and health

Long extraction, search indexing, export, and backup actions create `ProcessingRun` rows with input revision/digest, status, progress, attempt, lease, idempotency key, error code, retry eligibility, and output digest. Health distinguishes web, DB, queue freshness, writable blob path, and migration state without leaking secrets.

## Backup and restore

A backup captures consistent PostgreSQL state, referenced blobs, schema/application version, checksums, and configuration metadata excluding secrets. The package is access-restricted and optionally encrypted according to a Task 010 decision. Restore never overwrites the live workspace first: stage, verify manifest/digests/schema/blob references, run integrity tests, then explicitly activate or abandon. Recovery objectives remain unresolved until measured in Task 010.

## Observability and maintenance

Use local structured logs, audit views, failed-processing queue, storage/DB metrics, and diagnostic exports. No cloud telemetry dependency. Migrations require forward/rollback or restore strategy and data-loss review. Update procedures record current/target versions, backup verification, migration outcome, and rollback decision.


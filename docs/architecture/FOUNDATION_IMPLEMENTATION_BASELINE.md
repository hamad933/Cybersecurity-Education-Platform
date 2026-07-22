# Task 006 foundation implementation baseline

Status: `REVIEW CANDIDATE — NOT SELF-APPROVED`.

## Runtime boundary

One Laravel 13 application deploys the PHP backend and Vue 3/TypeScript/Inertia UI as one unit. PostgreSQL 18 is the sole database. Database sessions, database queues, local private blob storage, and a transactional outbox require no Redis, broker, search cluster, API application, WebSocket service, automated AI, or real-execution connector.

Only `MOD-IAM` and `MOD-PLT` contain code. All ten planned modules are registered with the acyclic graph in `config/platform.php` and the locked Task 006 TSVs. Planned modules have no empty directories or implementation placeholders.

## Identity and access

`owner_accounts` permits exactly one active owner through a PostgreSQL partial unique index. `owner:create` has no credential arguments, requests hidden password and confirmation, applies a 14-character mixed-case/number/symbol rule, and writes through the framework `hashed` cast. The explicit hashing baseline is adaptive bcrypt with cost 12 and rehash-on-login; tests use cost 4 only.

Login requires an active owner, is limited to five attempts per normalized email/IP per minute, regenerates the session, and records safe success/failure audit events. Failure subjects are SHA-256 normalized-email digests. Logout writes audit, logs out, invalidates the database session, and regenerates the CSRF token. There is no registration, reset, email verification, social login, token, role, tenant, or development bypass path. `UR-004-007` is resolved as `NO_DEVELOPMENT_AUTH_BYPASS`.

## Persistence ownership

UUIDv7 is the single foundation entity/message identifier. Framework queue/session keys remain framework-defined. PostgreSQL timestamp-with-time-zone columns represent UTC instants.

- `MOD-IAM`: `owner_accounts`, `application_sessions`.
- `MOD-PLT`: `audit_records`, `blob_objects`, `processing_runs`, `outbox_messages`, `jobs`, `job_batches`, `failed_jobs`.

Foundation migrations add status and digest CHECK constraints, normalized email and single-owner constraints, idempotency uniqueness, current access-path indexes, and owner/session foreign-key behavior. Down migrations reverse the dependency order. No Task 004 product entities were created.

## Platform primitives

- Audit is application-level append-only, bounded to 4 KiB safe metadata, and rejects known sensitive keys. No cryptographic tamper-proof claim is made.
- `ProcessingRun` supports only pending-to-running/cancelled and running-to-completed/failed/cancelled transitions, bounded safe failure text, attempt count, and unique idempotency.
- Outbox supports the typed, harmless `foundation.ping.v1` message only, 16 KiB database payload limit, unique idempotency, dispatch states, leases/retries, and one idempotent consumer.
- `BlobStore` generates UUIDv7 keys, stages then atomically moves a stream, computes SHA-256/size, and rejects absolute, Windows-drive, backslash, and traversal keys. It has no source-vault or ingestion dependency.
- `FoundationSmokeJob` uses the database queue, three attempts, bounded backoff, `ProcessingRun` correlation, completion idempotency, and safe failure state.
- `/health/live` is deliberately minimal. `app:diagnose` checks configuration, PostgreSQL, queue table, runtime storage, blob write, migrations, and profile without revealing values; runs are audited when the audit table exists.

## Shell

The actual UI is Arabic-first, semantic, responsive, keyboard-focused, and mixed-direction safe through `<bdi dir="auto">`. It exposes only login, first-owner CLI guidance, protected foundation health, local/environment indicators, owner menu, and logout. No Task 004 workspace or VS-001 workflow is represented as implemented.

## Deployment baseline

`compose.yaml` defines the application and pinned PostgreSQL 18.4 only. The web port binds to loopback and PostgreSQL has no host port. Secrets are mandatory substitutions, not defaults. Images are pinned, health checks are defined, volumes are named, and the PHP runtime uses `www-data`. Docker/WSL were unavailable; only structural validation and a native PostgreSQL execution fallback were completed.

Task 007 and VS-001 were not started.

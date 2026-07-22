# Migration and PostgreSQL results

Executed against portable PostgreSQL 18.4 on `127.0.0.1:5432`, never SQLite:

- Fresh creation of migrations table and all four Task 006 migration batches: PASS.
- Full rollback of four migrations in reverse order: PASS.
- Reapply: PASS.
- Nine expected foundation tables: present.
- UUIDv7 database round-trip: PASS.
- Normalized email, status, digest/payload, single-owner, and unique idempotency constraints: exercised; invalid records rejected.
- Database session, queue row, transactional outbox, and idempotent job/consumer paths: PASS.

The test cluster was disposable, trust-authenticated, and bound to loopback only. It is not a deployment credential or production configuration.

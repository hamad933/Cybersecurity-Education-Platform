# Migration and PostgreSQL results

Result: PASS on PostgreSQL 18.4 at loopback port 55432.

The lifecycle test ran guarded `migrate:fresh`, verified all 28 application/queue tables, rolled back all six migrations, and reapplied them. PostgreSQL UUID, JSONB, check, unique, foreign-key, idempotency, and locking behaviors are exercised. SQLite was not substituted.

The destructive guard runs before database refresh and rejects wrong environment, connection, database name, and host. The only test warning is the known framework dotenv read warning because no local `.env` file is present; the environment is injected explicitly and all 98 PHP tests pass.

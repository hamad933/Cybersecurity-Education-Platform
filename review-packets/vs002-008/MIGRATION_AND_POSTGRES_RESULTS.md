# Migration and PostgreSQL results

PASS on native PostgreSQL 18.4. Eight migrations complete fresh, eight-step rollback and reapply. The VS-002 migration adds four bounded tables and typed constraints/FKs/indexes, extends existing run/evidence rows, and preserves PostgreSQL-specific UUID/JSONB/check behavior. SQLite was not substituted.

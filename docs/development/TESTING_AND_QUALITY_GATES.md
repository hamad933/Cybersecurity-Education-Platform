# Testing and quality gates

| Gate | Command | Purpose |
|---|---|---|
| setup | `composer setup` | Install locked dependencies, create ignored environment, generate key, migrate, and build |
| start / stop | `composer start`, `composer stop` | Docker Compose lifecycle |
| format | `composer format` | Pint and Prettier writes |
| lint | `composer lint` | Pint check, ESLint, Prettier check |
| analyse | `composer analyse` | Larastan/PHPStan level 6 |
| typecheck | `composer typecheck` | Vue TypeScript no-emit check |
| test | `composer test` | PHP unit and feature suites |
| test:integration | `composer test:integration` | Real PostgreSQL contracts |
| test:architecture | `composer test:architecture` | Module graph, preservation, and repository safety |
| security | `composer security` | Composer/npm advisories plus limited fallback secret scanner |
| build | `composer build` | Production Vite assets |
| quality | `composer quality` | All local review gates plus Compose structural validation |
| diagnose | `php artisan app:diagnose` | Non-sensitive foundation health checks (kept distinct from Composer's own `diagnose` command) |

PostgreSQL tests use `cyber_platform_test` on loopback by default. They execute fresh migration, full four-step rollback, reapply, PostgreSQL CHECK/unique violations, database queue persistence, transactional outbox, UUIDv7 round-trip, and the active application paths. SQLite is not used.

The repository safety suite rehashes every Task 004 handoff source except the explicitly authorized root `AGENTS.md` update. Prior validators are executed read-only and their outputs are captured without regenerating earlier artifacts.

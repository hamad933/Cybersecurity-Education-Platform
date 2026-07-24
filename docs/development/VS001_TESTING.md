# VS-001 testing

Use a dedicated PostgreSQL database whose name contains `test`; the destructive guard rejects unsafe connection, host, or database values before `RefreshDatabase` can run.

Run:

```text
composer validate --strict
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
php artisan test --testsuite=Unit,Feature
php artisan test --testsuite=Integration
php artisan test --testsuite=Architecture
npm run format:check
npm run lint
npm run typecheck
npm test
npm run build
composer audit --locked
npm audit --audit-level=high
php scripts/secret_scan.php
php scripts/validate_compose.php
```

Authorization cases are parameterized and assert exact outcomes/rule IDs, ordered mask effects, unsupported behavior, and digest determinism. Feature tests cover source provenance, revision publication, run isolation/idempotency/replay, evidence locking/decisions, mastery insufficiency/sufficiency, all nine review-trigger classes, protected routes, and PostgreSQL constraints. Browser capture checks required desktop/mobile dimensions, 200% reflow simulation, no document-level horizontal overflow, focus, and RTL/LTR isolation.

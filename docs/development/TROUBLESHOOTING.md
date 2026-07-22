# Troubleshooting

- `Docker` or `wsl` not found: this was the observed Task 006 host state. Do not change system settings implicitly. Use the documented native fallback or install/configure prerequisites only with explicit owner approval.
- `node`, `npm`, `php`, or `composer` not found: install a supported local toolchain or use an approved project-local portable runtime; never alter machine-wide state silently.
- PowerShell blocks `npm.ps1`: invoke `npm.cmd` or use a policy already approved by the operator; do not weaken execution policy.
- PostgreSQL connection refused: confirm the PostgreSQL 18 service is running on the configured loopback host and port and that the database/user exist. Do not fall back to SQLite for integration behavior.
- Session table missing: run `php artisan migrate`; the configured table is `application_sessions`.
- Owner already exists: the single-active-owner constraint is intentional. Do not edit the index or add registration.
- Vite manifest missing: run `npm ci --ignore-scripts && npm run build`.
- Diagnostic fails: inspect only the named check (`configuration`, `database`, `queue`, `storage`, `blob`, `migrations`, or `profile`); `app:diagnose` never prints environment values.
- Composer cache warning in a restricted environment: a read-only cache warning is acceptable when locked packages remain available; do not redirect caches into the repository.

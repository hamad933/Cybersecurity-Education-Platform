# VS-002 local workflow

Use the locked PHP/Node dependencies and PostgreSQL. Configure a database name ending in `_test` for destructive test commands. Run `php artisan migrate:fresh --seed --force`, create the single local owner with `php artisan owner:create`, build assets with `npm run build`, then use the authenticated `/vs002/*` workspaces.

Recommended review sequence: sources, lesson/editor, lesson, practice, lab CASE-WEB-002, remediation, evidence verification, evidence decisions, replay, mastery evaluation. The lab is synthetic and must remain loopback/local-only.

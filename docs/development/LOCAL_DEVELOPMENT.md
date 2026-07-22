# Local development

## Docker workflow (governed target)

Create `.env` locally, generate `APP_KEY`, and choose a random `DB_PASSWORD`. Neither value belongs in Git.

```text
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan owner:create
docker compose down
```

The application maps only `127.0.0.1:${APP_PORT:-8080}`. PostgreSQL has no host port. `composer start` and `composer stop` wrap the normal start/stop commands. Docker was unavailable during Task 006, so this topology has structural validation only.

## Bounded native fallback

Use the exact supported PHP, Composer, Node/npm, and PostgreSQL versions in the version decision. Copy `.env.example` to the ignored `.env`, replace placeholders, generate the application key, and point the database variables at a loopback PostgreSQL 18 database.

```text
composer install
npm ci --ignore-scripts
php artisan key:generate
php artisan migrate
php artisan owner:create
npm run build
php artisan serve --host=127.0.0.1 --port=8000
php artisan queue:work --tries=3
```

No password argument exists on `owner:create`; both password and confirmation are interactive and hidden. Use `php artisan app:diagnose` for detailed CLI-only diagnostics. `/health/live` intentionally returns only `{"status":"ok"}`.

Resetting a local database destroys local state and therefore is deliberately not wrapped as an automatic script. Stop the application and explicitly run `php artisan migrate:fresh` only against a disposable local database.

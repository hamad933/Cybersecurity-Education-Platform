# VS-001 local workflow

Required locked runtimes are PHP 8.5, Composer 2.10, PostgreSQL 18, Node 24.18.0, and npm 11.16.0. Copy `.env.example`, configure a dedicated PostgreSQL database, run `composer install`, `npm ci --ignore-scripts`, `php artisan key:generate`, `php artisan migrate --seed`, and interactively run `php artisan owner:create`. Owner credentials are never seeded.

Start the local application with `php artisan serve` and the asset watcher with `npm run dev`, or use the production assets from `npm run build`. Open `/vs001/sources` after authentication. The normal workflow is source review, lesson, practice, lab run/replay, evidence decision, and mastery evaluation.

Docker Compose is the preferred foundation workflow when Docker is available. It was unavailable on the execution host for this candidate, so native locked runtimes were used and the status is recorded as `DOCKER_RUNTIME_UNAVAILABLE`; no Docker runtime pass is claimed.

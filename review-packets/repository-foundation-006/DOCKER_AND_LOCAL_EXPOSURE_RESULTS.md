# Docker and local exposure results

Status: `UNAVAILABLE — STRUCTURAL VALIDATION PASS, RUNTIME NOT EXECUTED`.

WSL reported not installed. Docker and Compose commands were absent. No system settings, distributions, services, or installations were changed.

The deterministic Compose validator passed: application port explicitly uses `127.0.0.1`; PostgreSQL has no host port; PostgreSQL tag is 18.4; health check and named database volume exist; application and database secrets are required substitutions with no defaults; prohibited services are absent; final PHP image runs as `www-data`; image versions are pinned.

Container build, pull, migration, service health, volume persistence, and shutdown remain unexecuted and must not be inferred from structural success.

# Release, backup, restore, and rollback results

- Release Compose gate: **PASS**.
- Release URL used by the bounded gate: http://127.0.0.1:18081.
- App, queue, and PostgreSQL are validated through the release Compose profile.
- Queue HTTP health inheritance is disabled; worker liveness is proven by running state plus one bounded processed job.
- Release queue smoke: **PASS**.
- Final service-health gate: **PASS**.
- Logical backup and isolated restore drill: **PASS**.
- Restore target was restricted to cyber_platform_restore_drill and removed after validation.
- Declared RPO/RTO claim: **NOT_DECLARED_MEASUREMENT_ONLY**.
- Source application creates a timestamped pre-apply backup ZIP before overwriting existing files.
- Rollback for Task-010 source changes uses that pre-apply ZIP against the exact Task-009 checkpoint.
- Browser evidence status: **BLOCKED_BROWSER_UNAVAILABLE**.

# Security and dependency results

- Targeted verification: **PASS**.
- Single full PHP regression: **PASS**; exactly one full run was performed and no second full run was used.
- Composer locked audit: recorded in the full-release gate and required to pass.
- npm high-severity audit: recorded in the full-release gate and required to pass.
- Repository secret scan: recorded in the full-release gate and required to pass.
- Safe ZIP/package controls: path traversal rejection, declared-member enforcement, digest/size checks, compression bounds, and actor binding are covered by targeted tests.
- Audit integrity: chained SHA-256 records are verified and direct tampering is detected by targeted tests.
- Manual AI boundary: manual export/import plus human decision only; no network provider and no automatic publication.
- Release network boundary: loopback-only application port; PostgreSQL has no host port.
- Release queue smoke: **PASS**; one bounded database-queue job was processed by the release worker.
- Final app/queue/PostgreSQL state gate: **PASS**.
- Browser gate: **BLOCKED_BROWSER_UNAVAILABLE**.

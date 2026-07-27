# V1 Threat Model and Security Gates

| Threat | Control | Verification |
|---|---|---|
| ZIP traversal / overwrite | Portable relative path guard and destination containment | Unit and integration package tests |
| ZIP bomb | file, archive, total size, depth, count, and compression-ratio limits | malformed package tests |
| Duplicate/case-collision entry | case-folded uniqueness | package test |
| Manifest substitution | canonical manifest digest and per-file SHA-256 | package test |
| AI result for wrong prompt/actor | actor + package + revision + input digest binding | Manual AI integration test |
| Automatic AI publication | only human `ACCEPT_AS_DRAFT`; no provider registration | architecture + integration tests |
| External evidence mislabeled as simulation | origin allowlist excludes `SIMULATED` | external evidence test |
| Audit deletion/update/gap | append-only model + chained sequence/hash | chain and tamper checks |
| Backup/data/blob mismatch | table counts + blob size/hash + audit verification | isolated restore drill |
| Restore over live database | `_restore_drill` suffix guard and no web activation method | architecture test + command failure test |
| Secret disclosure | package exclusions, audit metadata key rejection, secret scan | security gate |
| Public local service exposure | loopback app binding and no PostgreSQL host port | Compose architecture test |
| Privileged container escape | non-root app/queue, `cap_drop: ALL`, `no-new-privileges` | Compose architecture test |
| Stored/DOM XSS | validated typed lesson blocks, Vue text interpolation, CSP, no `v-html` | frontend and browser tests |
| Brute force / resource abuse | existing login limiter and bounded release action throttles | feature tests |

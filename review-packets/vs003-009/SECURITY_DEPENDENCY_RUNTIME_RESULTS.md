# Security, dependency, and runtime results

- Composer advisory audit: PASS
- npm advisory audit: PASS
- Deterministic source secret scan: PASS
- PostgreSQL migration lifecycle: PASS
- Frontend production build: PASS
- Docker development runtime: reused existing images; no rebuild was requested by the completion scripts.
- Browser runtime: BLOCKED_PRIOR_SINGLE_ATTEMPT; NOT_RERUN; no second attempt and no screenshot-pass claim.
- Handoff packaging: PASS

Network-blocked advisory audits are recorded as blocked, never as passes. A real advisory finding is a failure and blocks packaging.

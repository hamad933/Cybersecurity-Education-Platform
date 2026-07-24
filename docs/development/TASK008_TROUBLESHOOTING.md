# Task-008 troubleshooting

- `The case requires a remediation policy revision`: create remediation before running a case pinned to Policy Revision 2.
- `Idempotency key already exists`: reuse the exact same actor and payload or choose a new bounded key.
- publication blocked: bind `WEB-API-AUTHORITY-2026-07-22-V1` and include all approved `WEB-AUTH-*` claims.
- verification mismatch: select the access-control finding, its vulnerable run, and the immutable secure policy revision.
- Vite manifest missing a VS-002 page: run the locked production build.
- Docker unavailable: record `DOCKER_RUNTIME_UNAVAILABLE`; use native locked runtimes and do not retry Docker.
- Browser capture unavailable: report the environment blocker; never manufacture screenshots or infer console/network results.

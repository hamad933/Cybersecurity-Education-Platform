# Residual limitations

- The authorized second full PHP regression run ended with INCOMPLETE_TIMEOUT; it has no completion summary and is not represented as a pass. Missing/directly affected groups were verified independently.
- Browser runtime evidence is BLOCKED_PRIOR_SINGLE_ATTEMPT; NOT_RERUN. The required five-screenshot set was not produced, no second attempt was made, and no browser-runtime pass is claimed.
- Advisory audits may be BLOCKED_NETWORK when registry/advisory services are unreachable; see SECURITY_DEPENDENCY_RUNTIME_RESULTS.md.
- The development runtime uses Docker Compose, a source bind mount, isolated named dependency volumes, and isolated PostgreSQL database cyber_platform_test; production image behavior was not redesigned.
- All security actions are simulated and non-destructive. No production connector, real credential, automated AI, generic SIEM/SOAR expansion, VS-004, or Task-010 work is included.

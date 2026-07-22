# Authentication security results

Passed behaviors: interactive first owner creation; strong confirmed password; normalized email; framework adaptive hash; second active owner rejected by action and PostgreSQL; registration absent; guest redirect; correct login; wrong login; five-attempt throttle; session regeneration; protected dashboard; safe failure audit; logout audit; session invalidation and CSRF-token regeneration; no release/development bypass.

Credentials were not seeded, logged, audited, written to reports, committed, or included in the handoff. Login-failure audit uses a SHA-256 subject digest and empty safe metadata. Registration, resets, email verification, OAuth, roles, tenants, simulated roles, and API tokens are absent.

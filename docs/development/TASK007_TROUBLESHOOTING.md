# Task-007 troubleshooting

- `BLOCKED_VS001_AUTHORITY_VERIFICATION`: do not publish or approve rules. Re-check the exact official URLs and the claim scope in `VS001_AUTHORITY_CLAIMS.tsv`.
- PostgreSQL connection rejected by tests: use a dedicated database containing `test`, loopback/approved host, and `pgsql`; never weaken `DestructiveDatabaseGuard`.
- Revision conflict: reload the draft and retry with its current `lock_version`. Never edit a published row.
- `INSUFFICIENT_STATE`: provide the missing SID, descriptor, requested mask, group attributes, or approved generic mapping. Do not replace it with a guessed decision.
- `UNSUPPORTED_STATE`: the case uses a privilege, object/mapping/ACE type outside the approved subset. Preserve it as a review item.
- Replay mismatch: compare scenario/rule/baseline revisions, seed, normalized input, and ordered actions. A replay receives a new run ID, but the decision digest should match.
- Browser capture startup: use a new temporary Chrome profile and isolated debug port. Never store the screenshot account password or browser profile in the repository/handoff.
- Docker missing: record `DOCKER_RUNTIME_UNAVAILABLE`, use the locked native fallback, and do not claim a Docker runtime pass.

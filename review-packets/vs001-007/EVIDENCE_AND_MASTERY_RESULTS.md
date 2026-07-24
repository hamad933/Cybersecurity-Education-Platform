# Evidence and mastery results

Result: PASS.

PostgreSQL constrains evidence origin to `SIMULATED`; no `REAL_LAB` label or connector exists. Evidence binds capability, KU, scenario/rule/baseline revisions, run/case, input/trace/content digests, result, limitations, and source claims. Accepted evidence becomes immutable. `ACCEPTED`, `REJECTED`, and `NEEDS_REVIEW` paths are tested.

The provisional mastery rule requires accepted ALLOW, accepted DENY, accepted unsupported-state handling, provenance, and matching replay. Tests prove positive-only/empty and balanced-but-no-replay inputs are insufficient, while the complete set reaches `MASTERED`. Nine explicit failure classes create source-anchored scheduled ReviewTriggers; an unregistered generic failure is rejected.

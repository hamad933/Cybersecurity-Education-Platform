# VS-002 request and authorization model

The deterministic sequence is: normalize HTTP boundary; authenticate server context; look up the synthetic resource; authorize subject, action, resource and ownership; serialize only the registered shape; emit a bounded finding.

Authentication and authorization are separate. An authenticated identity is not sufficient for cross-owner access. Policy Revision 1 deliberately models the reviewed vulnerable condition. Policy Revision 2 uses default deny and allows only an owner match or an explicit server-side admin role. Client-supplied role and owner fields are recorded as ignored context and never change the decision.

Outcomes are `ALLOW`, `DENY`, `UNAUTHENTICATED`, `NOT_FOUND`, `INSUFFICIENT_STATE`, and `UNSUPPORTED_STATE`. The latter states never fall through to a guessed allow. Each trace binds scenario, rule, endpoint contract, enterprise baseline, policy, input, seed, ordered actions, decision, serializer fields, findings, remediation and verification context into SHA-256.

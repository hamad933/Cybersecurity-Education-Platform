# Simulator and rule results

Result: PASS.

Parameterized/golden tests cover explicit user allow, enabled-group allow, decisive deny before later allow, cumulative allow, remaining mask, missing principal, missing descriptor, missing generic mapping, approved file generic mapping, unsupported ACE, unsupported privilege, non-applicable ACE, deny-only group, and malformed SID. Exact decisive rule IDs, ordered ACE effects, mask changes, limitations, and deterministic output digests are asserted.

Supported results are `ALLOW`, `DENY`, `INSUFFICIENT_STATE`, and `UNSUPPORTED_STATE`. The engine is a pure PHP evaluator with no I/O. It does not emulate inheritance, conditional/object ACEs, privileges, integrity labels, restricted tokens, claims, central policy, share permissions, or Windows kernel behavior.

# Detection, remediation, and verification

An access-control finding is created only when the vulnerable policy allows an authenticated non-owner who is not a server admin. Its deterministic finding key deduplicates the same proven condition. A separate serializer finding records excluded non-approved fields. Stored safe details contain actor/resource/action or excluded field names; no password, cookie, token, authorization header, or request body is retained.

Remediation never edits Policy Revision 1. It creates immutable Revision 2 with a link to the revision it remediates. Verification replays the vulnerable run's exact input, scenario, endpoint contract, rule set, seed and ordered actions under Revision 2. Closure requires `ALLOW` in the vulnerable run, `DENY` in the fixed run, unchanged Enterprise Baseline digests, and a persisted link among finding, both runs, and the remediation policy revision.

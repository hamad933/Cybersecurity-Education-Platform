# Scenario run isolation results

PASS. Runs bind learner actor, scenario, rules, enterprise baseline, policy, endpoint contract, case, input digest, request digest, seed and ordered actions. Equivalent actor/payload idempotency returns the same run; conflicts are controlled. Replay resolves exact historical IDs after a newer policy is published. The Enterprise Baseline digest is unchanged.

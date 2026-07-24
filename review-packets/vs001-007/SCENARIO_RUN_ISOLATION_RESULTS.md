# Scenario run isolation results

Result: PASS.

Every run binds published scenario/rule/baseline revision IDs, seed, ordered actions, normalized input digest, and baseline hash before/after. Feature tests prove the baseline digest is unchanged, duplicate submissions return the same run/evidence, reset/replay creates a distinct run with the same decision digest, and remediation creates only an `improvement_proposals` row.

The decision digest excludes the new run ID to permit valid replay comparison; the persisted trace still records the actual run ID. The run has no baseline update capability.

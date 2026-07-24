# Queue and outbox results

Result: PASS.

The database queue ran real worker processes for retry-success and terminal failure, including `failed_jobs` and correlated ProcessingRun evidence. Versioned outbox publishing now accepts validated event types/producers and bounded payloads while preserving first-write idempotency. VS-001 emits `vs001.scenario.completed.v1` from `MOD-SIM` and `vs001.evidence.decided.v1` from `MOD-EVD`.

No Redis, Kafka, or external broker was introduced.

# VS-001 module interaction

The locked ten-module graph remains acyclic. VS-001 activates `MOD-SRC`, `MOD-KNO`, `MOD-CUR`, `MOD-ENT`, `MOD-SIM`, `MOD-EVD`, and `MOD-LRN` alongside foundation modules `MOD-IAM` and `MOD-PLT`. `MOD-AIB` is not activated or implemented.

The application coordination layer (`App\Application\Vs001`) passes identifiers and immutable value payloads across module owners. Module implementations do not import another module's ORM models and raw writes remain owned by the module that owns the table.

```text
MOD-SRC -> reviewed claims
MOD-KNO -> reviewed/published lesson revision
MOD-CUR -> CAP-D03-03-01 / KU-AD-02 lifecycle placement
MOD-ENT -> immutable Windows scenario baseline
MOD-SIM -> published cases + approved rules + isolated run + deterministic trace/replay
MOD-EVD -> SIMULATED evidence + human evidence decision
MOD-LRN -> micro practice + provisional mastery + failure-specific review trigger
MOD-PLT -> audit/outbox/queue/blob primitives
```

The simulator has no import of Learning or Evidence implementation and no enterprise repository capability. Cross-module notifications use versioned outbox messages (`vs001.scenario.completed.v1`, `vs001.evidence.decided.v1`).

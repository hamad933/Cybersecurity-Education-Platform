# Task 006 Module Dependency Resolution

Status: **LOCKED FOR TASK 006 IMPLEMENTATION**  
Direction: `consumer_module -> allowed_dependency_module`

## Resolution

The Task 004 `inbound_dependencies` and `outbound_dependencies` columns expressed intent but were not safe as a compile-time import graph. Task 006 does not modify that audit input. It locks this single interpretation:

```text
MOD-PLT -> NONE
MOD-IAM -> MOD-PLT
MOD-SRC -> MOD-PLT
MOD-KNO -> MOD-SRC;MOD-PLT
MOD-CUR -> MOD-KNO;MOD-PLT
MOD-ENT -> MOD-PLT
MOD-SIM -> MOD-ENT;MOD-CUR;MOD-PLT
MOD-EVD -> MOD-PLT
MOD-LRN -> MOD-CUR;MOD-KNO;MOD-PLT
MOD-AIB -> MOD-SRC;MOD-KNO;MOD-CUR;MOD-PLT
```

`planning/task006/MODULE_DEPENDENCIES_LOCKED.tsv` is the machine-readable registry. The graph is acyclic with the following valid topological layers:

1. `MOD-PLT`
2. `MOD-IAM`, `MOD-SRC`, `MOD-ENT`, `MOD-EVD`
3. `MOD-KNO`
4. `MOD-CUR`
5. `MOD-SIM`, `MOD-LRN`, `MOD-AIB`

No edge points from an earlier layer to a later dependency, and depth-first cycle validation is an automated architecture test.

## Cycle-breaking contracts

- `MOD-SIM` emits versioned run/transition messages and never imports Learning or Evidence implementation classes.
- `MOD-EVD` records evidence from durable messages or stable contracts and never calls Learning internals.
- `MOD-LRN` consumes evidence decisions through messages or a consumer-owned read port and never imports Evidence implementation classes.
- Domain modules do not import Identity and Access. The authenticated actor is supplied at the application boundary as the Platform `ActorContext` primitive.
- `MOD-PLT` never imports a domain module. Composition-root providers perform wiring at the application boundary.
- Cross-module reads use consumer-owned ports returning typed data, never another module's ORM models.
- Cross-module change uses durable internal messages/outbox; direct writes to another module's tables are forbidden.

Only `MOD-IAM` and `MOD-PLT` contain Task 006 code. The other module IDs, names, ownership metadata, and dependency rules exist only in the registry; Task 006 creates no empty CRUD or ceremonial module trees for them.

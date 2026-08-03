# Module implementation conventions

Executable code exists only in `App\Modules\IdentityAccess` (`MOD-IAM`) and `App\Modules\Platform` (`MOD-PLT`). Planned module metadata lives in `config/platform.php`; no empty module trees or CRUD placeholders are allowed.

Dependencies always mean `consumer_module -> allowed_dependency_module`. A module may import itself and its locked dependencies only. `MOD-PLT` imports no domain module. Cross-module ORM models and writes are forbidden. Stable IDs, actor context, consumer-owned ports, read-only query contracts, composition-root wiring, and durable outbox messages are the permitted collaboration mechanisms.

Tables are owned as recorded in `planning/task006/FOUNDATION_ENTITY_OWNERSHIP.tsv`. Raw queries must stay within the owner. JSONB is allowed only for the bounded audit metadata and typed outbox payload schemas. Every new message type requires a versioned schema, payload limit, idempotency key, producer, and consumer tests.

Module-boundary tests scan namespaces/imports, active directories, graph cycles, ORM model imports, and raw table writes. A graph change requires an approved phase decision plus updates to the registry, locked TSVs, rationale, architecture baseline, and tests.

# CEP Workspace Route Ownership

This directory is the parallel-safe route entry layer for `CEP-BUILD-001`.

Each file has exactly one Builder owner:

```text
today.php                  → CEP-BUILD-001-W01 → feat/cep-shared-foundation
knowledge-learning.php     → CEP-BUILD-001-W02 → feat/cep-knowledge-learning
simulation-enterprise.php  → CEP-BUILD-001-W03 → feat/cep-simulation-enterprise
progress-evidence.php      → CEP-BUILD-001-W04 → feat/cep-progress-evidence
system-operations.php      → CEP-BUILD-001-W05 → feat/cep-system-operations
```

## Stable Wave-1 URL contract

Use these authenticated global destination entry paths so W01 can build one shared navigation while the domain branches implement in parallel:

```text
Today                    → /
Knowledge & Learning     → /knowledge
Simulation & Enterprise  → /simulation
Progress & Evidence      → /progress
System & Operations      → /system
```

Primary-area child paths are owned by the corresponding domain Builder and must remain beneath that destination prefix unless an existing compatibility route is intentionally retained.

## Rules

- Builders modify only their assigned route file.
- `routes/web.php` is Controller/shared-foundation infrastructure and is not a parallel domain edit surface.
- Do not move another workstream's routes into your file.
- Legacy `/vs001`, `/vs002`, `/vs003` routes may remain reachable as reuse/reference behavior during the build; they are not the target product IA.
- New workspace routes must be real authenticated Laravel routes backed by real controllers/application state.
- Route aliases or redirects used for compatibility must not imply that legacy ownership semantics remain canonical.
- Domain route names should use a stable `cep.<workspace>.*` namespace where practical; do not reuse `vs001.*`, `vs002.*`, or `vs003.*` as target product names.
- If a cross-workspace route contract is needed, stop and request Controller coordination rather than editing another workstream file.

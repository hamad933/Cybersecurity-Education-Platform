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

Rules:

- Builders modify only their assigned route file.
- `routes/web.php` is Controller/shared-foundation infrastructure and is not a parallel domain edit surface.
- Do not move another workstream's routes into your file.
- Legacy `/vs001`, `/vs002`, `/vs003` routes may remain reachable as reuse/reference behavior during the build; they are not the target product IA.
- New workspace routes must be real authenticated Laravel routes backed by real controllers/application state.
- Route aliases or redirects used for compatibility must not imply that legacy ownership semantics remain canonical.
- If a cross-workspace route contract is needed, stop and request Controller coordination rather than editing another workstream file.

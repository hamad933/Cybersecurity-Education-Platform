# Proposed Stable AGENTS.md Patch After Task 004 Approval

Do **not** apply this text during Task 004. Add only after explicit approval:

```text
- Use a single deployable Modular Monolith for v1 with explicit module ownership and internal contracts; do not add microservices or separate frontend/backend deployment units without an approved measured need.
- The approved preferred foundation is Laravel/PHP, Vue 3 + TypeScript through Inertia.js, PostgreSQL, HTML/CSS/Tailwind CSS, and WSL2 + Docker Compose. Task 006 must inspect the environment, select supported compatible versions, lock dependencies, and record the actual versions.
- v1 is a local single-owner workspace. Application permissions and simulated scenario roles are separate models; SaaS multi-tenancy, public registration, billing, social, marketplace, and Internet collaboration are outside v1.
- v1 product completeness means coherent, secured, recoverable core workflows and tested vertical slices; it does not mean finished curriculum across all 16 Domains or promotion of all provisional Knowledge Units.
- Static HTML/CSS/JavaScript artifacts under design-prototypes are design proofs, never product implementation or implementation evidence.
- Use PostgreSQL relational storage, validated JSONB only for registered typed payloads, PostgreSQL full-text search, database-backed queues, and local blob abstraction initially. Do not add Redis, Kafka, Kubernetes, graph databases, search clusters, or other infrastructure without an approved blocking or measured requirement.
- Task 004 is an architecture and UX candidate only; no Task 006 scope or candidate may be treated as approved without the named external review gate.
```

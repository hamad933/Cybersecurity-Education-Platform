# Architecture Decision Summary

Fourteen proposed ADRs recommend:

1. One deployable Modular Monolith with ten data-owning modules.
2. Laravel/PHP backend and Vue 3 + TypeScript through Inertia.
3. PostgreSQL relational integrity, bounded JSONB, full-text search, and database-backed queue.
4. Local-first operation and local blob abstraction.
5. Registered structured content blocks and immutable publication revisions.
6. Institutional Simulator as the default lab; isolated, deterministic Scenario Runs.
7. Manual AI Bridge as the only AI workflow.
8. One local owner in v1, strict source custody/provenance, and no real-execution connectors.

No microservices, Kafka, Kubernetes, graph database, dedicated search cluster, Redis requirement, separate frontend deployment, event-sourced primary store, provider framework, or second backend language is introduced. Exact versions remain a Task 006 environment-validation decision. All ADRs are proposed and require human approval.

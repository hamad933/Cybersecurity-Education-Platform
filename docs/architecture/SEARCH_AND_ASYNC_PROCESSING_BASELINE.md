# Search and Async Processing Baseline

## Search

PostgreSQL full-text search is sufficient for v1 source segments, canonical knowledge, lessons, capabilities, enterprise catalog names/descriptions, scenario metadata, and audit-safe metadata. Each searchable document records owner module, entity/revision ID, locale, visibility, provenance link, and indexing status. Arabic and English configuration must be measured with representative queries; fallback normalization must not corrupt technical tokens.

Authorization is applied before or with result retrieval, never only by hiding UI links. Search results show entity type, publication/review state, source/provenance context, and matched snippet safely encoded. Unpublished or private records cannot leak through snippets/counts.

A dedicated search cluster, graph database, embeddings, and vector database are prohibited for v1. Revisit only after measured corpus/query relevance or latency cannot meet approved targets and PostgreSQL tuning has been exhausted.

## Async processing

Laravel database-backed queues initially handle extraction, safe preview, indexing, impact analysis, export, and backup work. Each job points to a `ProcessingRun` and uses stable input revision/digest plus idempotency key. Claims are leased with timeout/heartbeat, bounded attempts, deterministic retry classification, cancellation, and terminal failure requiring visible review.

Domain publication and baseline mutation never occur merely because a job succeeds; the owning application service completes validated transitions transactionally. Outbox rows make post-commit messages durable. Redis/message broker is not required. Revisit only if measured concurrency/latency/availability demands exceed database-queue limits.


# TASK-010 — V1 Integration, Hardening, Recovery, and Local Release

## Stop gate

`STOP-V1-RELEASE-010`

## Baseline

- Required repository checkpoint: `83b932a079bf2237dbfa033a4322c6bded042842`.
- Required tag: `task009-review-candidate`.
- Task-009 evidence remains historically truthful: the prior browser attempt was blocked and the prior full PHP regression ended as `INCOMPLETE_TIMEOUT`.

## Included scope

1. Integrate VS-001, VS-002, and VS-003 through one authenticated local release center.
2. Implement safe source custody with extension, MIME, signature, size, hash, quarantine, audit, and immutable provenance.
3. Implement portable packages with canonical manifests, per-file digests, bounded expansion, duplicate/path traversal rejection, and actor binding.
4. Implement the Manual AI Bridge only: export prompt package, manual execution outside the product, import structured result, validation, human decision, and optional new draft. No network provider and no automatic publication.
5. Add PostgreSQL full-text search and an explainable actor-bound daily review queue.
6. Add external evidence import with explicit non-simulated origins and human review.
7. Add tamper-evident append-only audit chaining.
8. Add logical backup, blob integrity inventory, staged web restore, and isolated CLI restore drill.
9. Add local release Compose with a shared application image, internal PostgreSQL, loopback web binding, non-root application processes, dropped capabilities, and queue worker.
10. Add Arabic RTL / English LTR application QA, security headers, bounded release checks, recovery evidence, performance evidence, and rollback documentation.

## Explicit exclusions

- No OpenAI API adapter, local AI adapter, provider selection, automatic inference, automatic acceptance, or automatic publication.
- No production connector, credentialed enterprise integration, live attack execution, production telemetry ingestion, Kafka, graph database, or microservice split.
- No restore activation from the web interface.
- No claim that a blocked browser attempt passed.
- No claim that backup encryption, retention, RPO, or RTO are finalized; these remain documented limitations for the local V1 release.

## Acceptance gates

| Gate | Required evidence |
|---|---|
| A1 | Exact clean Task-009 checkpoint before patch application |
| A2 | Bundle hashes and requested-file manifest verified before overwrite |
| A3 | Fresh PostgreSQL migration and rollback lifecycle passes |
| A4 | Targeted Task-010 PHP, architecture, frontend, formatting, type, lint, and build gates pass |
| A5 | Package traversal, duplicate path, undeclared file, digest, expansion, actor binding, and provenance tests pass |
| A6 | Manual AI result remains pending until a human decision and can create only a draft |
| A7 | Search returns bounded local results and daily queue exposes ranking reasons |
| A8 | Audit chain verification passes and a tamper test detects a gap or changed record |
| A9 | Backup package integrity passes and isolated restore drill verifies tables, blobs, and audit chain |
| A10 | Composer audit, npm audit, and secret scan pass or are recorded truthfully as externally blocked |
| A11 | Release image starts app and queue from the same image; PostgreSQL has no host port |
| A12 | Browser evidence is attempted once; success or exact blocker is preserved truthfully |
| A13 | Review packet ZIP is complete, integrity checked, and ends at `STOP-V1-RELEASE-010` |

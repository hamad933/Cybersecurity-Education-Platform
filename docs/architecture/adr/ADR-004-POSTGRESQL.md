# ADR-004 — PostgreSQL Persistence

Status: **PROPOSED**. Use PostgreSQL for relational integrity, transactions, JSONB only for registered typed payloads, full-text search, and database-backed queue state. Graph/search clusters and event-sourced primary persistence are rejected for v1.


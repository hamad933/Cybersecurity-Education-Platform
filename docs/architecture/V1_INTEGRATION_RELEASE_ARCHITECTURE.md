# V1 Integration and Release Architecture

## Control-plane shape

The V1 remains one Laravel modular monolith. PostgreSQL stores operational state, the database queue executes bounded asynchronous work, and the local filesystem adapter stores content-addressed evidence and package blobs. The release adds integration surfaces without introducing a remote provider or production connector.

## Trust boundaries

1. **Browser → authenticated Laravel route**: owner-only session, CSRF, rate limits, validation, security headers.
2. **Uploaded source → quarantine/acceptance decision**: extension, bounded size, server-side MIME, signature, UTF-8/JSON validation, SHA-256, blob registry, and audit.
3. **Portable ZIP → verified package**: relative paths only; no duplicate case-folded names; no directories, traversal, absolute/drive paths, undeclared files, oversized entry, excessive expansion ratio, invalid manifest, or digest mismatch.
4. **Manual AI boundary**: the product exports a deterministic prompt package. A human runs it outside the product. Imported output must match the exact prompt package, revision, input digest, actor, schema, and declared scope. It remains `pending_review` until a human records `ACCEPT_AS_DRAFT` or `REJECT`. Acceptance creates a new draft only.
5. **External evidence boundary**: imported evidence cannot use the simulator origin. Only `REAL_LAB`, `MANUAL_ASSESSMENT`, and `SOURCE_REVIEW` are accepted and all require human review.
6. **Backup boundary**: logical database data and registered blobs are hashed into a portable package. The web can only stage and verify. Activation is possible only through a CLI command while connected to a database whose name ends in `_restore_drill`.
7. **Release boundary**: web is bound to loopback. PostgreSQL is internal. App and queue use the same image and non-root identity with Linux capabilities dropped.

## Audit model

Each new audit record has a monotonically increasing sequence, prior-record hash, and canonical current-record hash. The writer serializes appends under a PostgreSQL table lock. The verifier replays the entire chain and returns the first invalid sequence. The chain is evidence of tampering or gaps, not a replacement for external immutable logging.

## Search and queue

`search_documents.search_vector` is a PostgreSQL generated `tsvector` using the `simple` dictionary, indexed with GIN. This preserves Arabic and English tokens without claiming language-specific stemming. The daily queue ranks open failure-specific reviews first, then non-mastered or unevaluated knowledge units, and exposes both a reason code and a human-readable reason.

## Known V1 limitations

- Backup confidentiality depends on restrictive local storage; package encryption and lifecycle policy are unresolved.
- Search relevance is bounded and measured but not linguistically optimized.
- Browser evidence depends on locally available Chromium/Edge and may remain externally blocked.
- Production connectors, live telemetry, and automatic AI are explicitly absent.

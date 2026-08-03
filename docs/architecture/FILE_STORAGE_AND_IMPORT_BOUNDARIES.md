# File Storage and Import Boundaries

## Storage classes

- Custody blobs: immutable original bytes keyed by digest and referenced by `OriginalSource`.
- Derived blobs: extracted representations, safe previews, diagrams, and generated exports tied to input/output digests.
- Evidence blobs: immutable evidence payloads tied to origin and producer/run/attempt.
- Temporary quarantine: non-addressable by the normal renderer; bounded lifetime and permissions.
- Backup/package files: manifested exports outside active storage.

The initial implementation is a local filesystem adapter behind `BlobStore`; paths are generated from internal IDs/digests, never user filenames. Database rows carry storage key, size, media type, digest, state, and lifecycle owner. Moving to another object store is deferred and must preserve these semantics.

## Import pipeline

`Select -> consent/scope -> quarantine -> path/type/size/signature validation -> digest -> duplicate check -> custody commit -> safe extraction -> anchor creation -> review`. Archive entries reject absolute paths, `..`, Windows device names, alternate streams, symlinks/hardlinks, excessive depth/count/ratio, encrypted items without an approved workflow, and nested limits. Failed extraction records warnings/errors and never alters custody or a published revision.

File parsers run without execution, network access, or write access outside controlled temporary/output areas where feasible. PDF/DOCX/HTML previews use converted or sandboxed representations. URLs and external lookup are separate explicit user actions; v1 is not a crawler.

## Export/import packages

Packages use a versioned schema, safe relative paths, manifest, per-file SHA-256, total byte/file counts, source/application versions, and explicit content scope. Import verifies before draft creation. Unknown schema versions fail closed. Package contents never gain authority or publication status from successful transport validation alone.


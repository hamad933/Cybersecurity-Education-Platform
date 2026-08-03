# Manual AI Bridge Architecture

## Binding scope

`AIInteractionPort` has one v1 implementation concept: manual file exchange through ChatGPT Plus. It does not submit, poll, authenticate to, meter, select, or configure any model/provider. The Product Charter's earlier provider-replacement language is recorded as superseded by the current Manual-AI-only decision.

## Aggregate and status model

`PromptPackage` owns versioned `PromptPackageRevision` records. A revision contains purpose, selected SourceSegment/Knowledge IDs and digests, explicit exclusions, redaction decisions, requested output schema/version, instructions, manifest, exported files, package digest, actor, and audit correlation.

Allowed statuses are:

`DRAFT -> EXPORTED -> AWAITING_MANUAL_PROCESSING -> RESULT_IMPORTED -> {STRUCTURAL_VALIDATION_FAILED | PROVENANCE_VALIDATION_FAILED | AWAITING_HUMAN_REVIEW} -> {PARTIALLY_ACCEPTED | ACCEPTED | REJECTED} -> SUPERSEDED`.

Failure may return to a new package/result revision; history is never rewritten. `EXPORTED` and later records are immutable except for controlled status transitions and linked decisions.

## Export

The owner sees every selected source/revision, sensitivity marker, bytes, and redaction before confirmation. Export uses an allowlisted directory layout, UTF-8 structured files, requested schema, manifest, per-file SHA-256, package digest, and a warning that processing occurs outside the local product. No source outside visible selection is included.

## Import and validation

Imports enter quarantine. Validation checks archive/path/size/type limits, manifest and digest, requested schema/version, unknown fields, entity IDs, every cited source segment/digest/anchor, affected entity ownership, and declared limitations. A structurally valid result with an invalid citation enters `PROVENANCE_VALIDATION_FAILED`; it is not partially trusted.

## Human decision and publication

The reviewer sees source evidence, proposed block/entity diff, conflicts, confidence as proposal metadata, and downstream impact. Decisions are per proposed change plus package-level outcome: accept, partially accept, reject, edit into a new draft, defer, or request evidence. Acceptance invokes owning-module services to create drafts only. Normal validation/review/publication remains mandatory. Audit links package, import digest, validations, affected entities, human decisions, and resulting draft/publication IDs.


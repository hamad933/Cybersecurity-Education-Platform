# Content and Revision Model

## Layers and publication

`Original Source -> Extracted Representation -> Source Segment -> Claim/Relationship -> Canonical Knowledge -> Educational Presentation -> Learner State` are separate ownership layers. A lesson draft references published knowledge or explicitly marked provisional claims. Publication requires resolved block schemas, citations for technical claims, review decision, applicability/authority notes, and impact assessment. Published revisions are immutable.

## Block envelope

Every block has `block_id`, `revision_id`, `block_type`, `schema_version`, `position`, optional `parent_block_id`, `locale`, `direction`, typed `payload`, provenance links, and review state. The registry fixes allowed fields, value types, allowed child types, maximum depth/bytes, renderer, sanitizer policy, and migration function. Unknown types/versions/keys are rejected.

| Type | Minimum validation |
|---|---|
| Heading | text; level 1–6; no executable markup |
| Paragraph | sanitized rich text; locale/direction required |
| Toggle | title and permitted child blocks; bounded nesting |
| Callout | semantic tone allowlist plus content |
| Warning | warning severity and content; cannot be hidden by default |
| Definition | term and definition; stable concept link optional |
| Comparison | labelled sides and comparable rows |
| Table | bounded rows/columns; header semantics; captions |
| Code | language allowlist; plain display text; LTR |
| Command | shell/context label; display-only text; LTR; no execute control |
| Configuration | format/context; display-only; secret-redaction marker |
| Output | producer/context; simulated/real label when applicable; LTR |
| Log | timestamp/field semantics; redaction; LTR |
| Protocol/packet | layer/field/value schema; LTR technical fields |
| Diagram/image reference | local asset ID, alt text, caption, digest |
| Citation | SourceSegment ID, exact anchor, source/revision digest |
| Lab step | objective, expected state, hint policy, verification link |
| Evidence requirement | capability, origin rule, acceptance description |
| Review question | prompt, response type, rationale; no hidden source claim |
| Relationship | typed source/target IDs and contextual reason |

`Learning objective`, diagnostic, practice, detection, defence, verification, control/policy/scenario links, version notes, and conflict notes may be registered later as first-class types using the same rule—not arbitrary JSON.

## Rendering and bidi

Raw imported HTML is never trusted. Renderers emit semantic allowlisted HTML with context-sensitive encoding. Arabic prose inherits `dir="rtl"`; technical tokens use `<bdi dir="ltr">`; code/command/log containers use `dir="ltr"`. Strings are never manually reversed. Copy returns logical source order.

## Revision comparison and impact

Diffs operate at block identity, order, payload field, provenance, and relationship levels. A changed supporting claim can mark dependent lessons/practices/labs/scenarios as `UNAFFECTED`, `REVIEW_RECOMMENDED`, `POTENTIALLY_OUTDATED`, or `BLOCKED_PENDING_RESOLUTION`. Acceptance may be selective but every resulting draft must validate as a whole.


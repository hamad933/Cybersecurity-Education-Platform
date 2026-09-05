PROJECT: Cybersecurity Education Platform (CEP)
ROUTE: PERSONAL:CEP
SURFACE: W02 Library + Unified Editor
AUDIT MODE: EXHAUSTIVE_READ_ONLY_DEEP_AUDIT
EXACT GITHUB SHA: `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
EXPECTED PARENT: `7fa8714dc6d0beec6ec77ba8a673140301b066cf`
PRODUCT MUTATION: NONE
WRITER DISPATCH: NONE
ACCEPTANCE: NOT GRANTED
DATE: 2026-09-04

# 09 — Remediation Requirements Input — NOT Execution Plan

> هذه الوثيقة requirements input فقط. لا تحتوي sequencing، branch ownership، writer assignment، commit plan أو implementation packet.

## RQ-01 — Preserve exact authority boundaries
أي remediation لاحق يجب أن يبدأ من SHA معتمد جديد يحدده Controller B، وألا يعامل هذا التدقيق كإذن تعديل.

## RQ-02 — Protect data integrity first
يجب إغلاق `W02-DA-001` و`002` قبل أي قبول للـ Editor:
- stale valid recovery preserved as conflict;
- storage failure bounded and truthful;
- no silent deletion;
- explicit discard/acknowledged-save semantics.

## RQ-03 — Restore document dominance
الخطة اللاحقة يجب أن تعالج findings `007–015` كمتطلبات مستقلة:
- useful CENTER area;
- fixed dead-space behavior;
- nested framing;
- cumulative padding;
- desktop proportions;
- vertical chrome;
- side-panel density under representative data.

لا يكفي تغيير margin واحد أو screenshot fixture واحد.

## RQ-04 — Establish a coherent type/rhythm system
يجب معالجة `016–021` و`019` ضمن نظام type/spacing قابل للتحقق:
- title/body relationships;
- metadata legibility;
- muted contrast;
- mixed-block rhythm;
- technical IDs subordinate to human semantics;
- remove/debug-gate implementation-facing footer copy.

## RQ-05 — Normalize command and icon grammar
يجب أن تغطي الخطة:
- TOP action priority;
- 1024 compaction;
- layout-controls vs task-controls hierarchy;
- editor toolbar grouping;
- move/nest affordance;
- disabled-state visibility;
- one icon system, avoiding platform emoji for core semantics unless explicitly approved.

## RQ-06 — Do not fake hierarchy semantics
Human Domain/Cluster/Capability labels must come from one authoritative shared read source.
Prohibited:
- hardcoded labels in Library;
- ID-as-title;
- synthetic tree solely for screenshot similarity.

Until authority is present, unresolved state remains valid but cannot serve as the only normal-state acceptance evidence.

## RQ-07 — Keep RIGHT bounded but rich
Do not recreate RQ inside Library. Requirements:
- exactly one RQ deep link;
- realistic Overview/Sources density;
- realistic source/claim cardinality;
- safe link/provenance rendering;
- 1024 RIGHT overlay direct evidence.

## RQ-08 — Treat 1024 as desktop, not mobile fallback
Later remediation must preserve:
- usable CENTER width;
- compact global/primary/TOP chrome;
- discoverable panel controls;
- explicit RIGHT open/close;
- code and mixed-content readability.

## RQ-09 — Preserve allowed/overridden visual differences
Do not “fix”:
- TOP→BOTTOM task ownership by adding duplicate BOTTOM launcher;
- bounded RIGHT by copying broader reference tabs;
- default RIGHT collapse at 1024 if overlay remains usable.

## RQ-10 — Use representative but governed acceptance data
Visual closure dataset must:
- be explicitly non-canonical if fixture-only;
- have realistic titles/content;
- exercise multiple hierarchy levels;
- exercise long documents;
- exercise multiple sources/claims/revisions;
- carry deterministic run receipt/hash.

Do not mutate canonical B09 or bypass import authority for visual similarity.

## RQ-11 — Separate canonical knowledge from runtime proof
Any later plan must explicitly state one of:
- canonical runtime projection is authorized and define its deterministic chain; or
- it is not yet authorized and acceptance remains fixture-only.

No ambiguous middle state.

## RQ-12 — Evidence closure is state-specific
Tests alone cannot close visual findings. Required future evidence is defined in `07_EVIDENCE_GAPS_AND_FUTURE_VALIDATION_REQUIREMENTS.md`.

## RQ-13 — Bidi closure must be interactive
Static `dir`/CSS is insufficient. Later validation must include Arabic/English/technical caret, selection, deletion, Home/End and IME behavior.

## RQ-14 — Modal accessibility closure
Link/reference dialogs require:
- focus containment;
- deterministic focus return;
- Escape/Cancel semantics;
- error/success states;
- 1024 evidence.

## RQ-15 — Shared dependency adjudication before code
Any change touching:
- `CepWorkspaceLayout`,
- `KnowledgeTabs`,
- shared design tokens/icons,
- hierarchy read model,
- RQ boundary,
- runtime import/bootstrap,
must be explicitly owned through Controller B to avoid collision.

## RQ-16 — Do not collapse findings
The 65-row ledger is the minimum audit granularity for later planning. A future plan may group implementation work only if it preserves one-to-one closure criteria/evidence for every material finding.


## RQ-17 — Preserve structured table/list semantics
Any later plan that projects B09-shaped content must explicitly resolve `W02-DA-064` and `065`.

Requirements:
- table row/column relationships must not be flattened into decorative bullet text;
- list item identity/hierarchy must not be reduced to glyphs inside an undifferentiated paragraph;
- the representation must be shared/compatible across Knowledge storage, Library editor and Learn renderer;
- editing, save/restore, Bidi, keyboard and accessibility semantics must be validated;
- if Controller B intentionally excludes first-class table/list blocks, it must approve an equivalent **lossless** structured representation rather than silent semantic loss.

This is a content-contract requirement, not a request to copy B09 directly into production runtime.

## RQ-18 — Acceptance prohibition
This requirements input is not:
- an execution plan;
- a writer prompt;
- a PR plan;
- a merge instruction;
- a freeze recommendation;
- product acceptance.

`STOP_GATE=LIBRARY_EDITOR_DEEP_AUDIT_COMPLETE__CONTROLLER_B_REVIEW_REQUIRED__NO_PRODUCT_MUTATION__NO_WRITER_DISPATCH__NO_ACCEPTANCE`

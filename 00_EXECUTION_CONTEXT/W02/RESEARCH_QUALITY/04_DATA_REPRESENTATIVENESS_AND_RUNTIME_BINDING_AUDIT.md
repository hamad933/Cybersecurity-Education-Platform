# W02 Research & Quality — Deep Audit

**Project:** Cybersecurity Education Platform  
**Project ID:** CEP  
**Route:** PERSONAL:CEP  
**Audit date:** 2026-09-04  
**Mode:** READ-ONLY DISCOVERY  
**Verified branch:** `work/cep-w02-library-work-visual-r01`  
**Verified SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`  
**Verified parent:** `7fa8714dc6d0beec6ec77ba8a673140301b066cf`  
**Baseline drift:** `NONE` at direct GitHub verification  

## 1. Canonical→runtime chain

`B09 canonical KU/claim/source evidence` → `W02 prepared acceptance payload` → `W02AcceptanceSeeder` → `SourceRecord/SourceClaim + LessonRevision citations` → `KnowledgeQualityService::workspace()` → `ResearchQualityWorkbench::analyze()` → Inertia props → R&Q Vue components → current screenshots.

## 2. Canonical evidence richness

B09 contains 2,603 claim rows and 669 conflict/variant rows. Claim evidence includes claim text, lineage, source reference/artifact, exact locator, authority/version/freshness and support state. Separate indexes preserve gaps, limitations and source deltas. This proves that the intended workbench depth is not merely decorative reference imagery.

## 3. Runtime narrowing

Current `SourceClaim` persists only `source_record_id`, `claim_id`, `segment_ref`, `supported_scope`, `excluded_semantics`, and `assessment`. `KnowledgeQualityService` exposes source metadata and claims, while analysis reduces comparison to per-source counts and provenance to locator/digest plus anchor identifiers. Conflict detection is synthesized from fingerprint differences rather than canonical variant semantics.

## 4. Fixture distortion

`W02AcceptanceSeeder::seedClaim()` creates **one SourceRecord per claim**, hard-codes `Internal Reviewed Support`, stores no external URL, writes `w02-acceptance/<claim_id>` as a relative path, and repeats the same acceptance disclaimer as excluded semantics. This is a major `DATA_FIXTURE_REPRESENTATIVENESS_GAP`: it makes source selection look like claim selection and prevents honest evaluation of multi-claim source grouping, source-size variation, authority diversity and locator diversity. **Same-claim multi-source support is not attributed to the fixture alone**: the exact migration makes `source_claims.claim_id` globally unique, so that capability is separately schema-gated.

## 5. Product defects that remain even with a better fixture

A richer fixture alone would not fix: global source scoping, unrelated fallback, global conflict computation, aggregate source-count Compare, lack of selected claim/relation route state, lack of claim×source relation projection, absence of canonical claim text, absence of coverage/gap/limitation context, or missing BOTTOM deep inspection.

## 6. Schema/authority boundary

Two durable capabilities are not authorized by this audit: (a) cardinality/richer persisted claim×source relation/excerpt taxonomy—including more than one source-support row for the same claim—and (b) immutable revision→source-support historical provenance. A third authority decision governs any durable R&Q reviewer decision/reconciliation record. These must not block unrelated static/design/runtime projection corrections.

## 7. Data-realism classification summary

- Fixture gaps: **5**
- Canonical runtime binding gaps: **8**
- Schema authority limitations: **2**
- Separate authority decision required: **1**

## 8. High-risk runtime facts

1. `workspace()` loads all `SourceRecord` rows globally.
2. If no requested/active-match source resolves, the first global source may become active.
3. Conflict analysis runs across all loaded sources and is not restricted to canonical claim IDs.
4. Compare rows are one-per-source aggregates.
5. Revision reasoning is IDs/counts, not pairwise claim-set diff.
6. Canonical evidence is materially richer than current frontend projection.

## 8. Final QA strengthening

- `source_claims.claim_id UNIQUE` means a persisted same-claim/multi-source Conflict topology cannot be honestly produced by the current DB model; any fixture requirement that assumes otherwise is invalid until schema authority changes.
- The fixture can still be improved now by grouping multiple different claims under one real source and by exercising existing authority classes, locator types and supported/partial/excluded/unresolved states.
- Claims mode needs a separate claim-summary projection (`RQ-DP-065`) even after fixture correction.
- Conflict explainability (`RQ-FN-066`) is independent from conflict classification correctness (`RQ-RB-054`).
- Revision pair route identity (`RQ-FN-064`) is independent from the pairwise diff payload itself (`RQ-RB-057`).

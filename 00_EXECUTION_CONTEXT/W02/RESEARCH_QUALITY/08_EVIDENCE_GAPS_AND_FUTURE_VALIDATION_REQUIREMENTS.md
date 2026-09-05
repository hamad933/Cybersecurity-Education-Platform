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

## Evidence gaps and future validation requirements

The audit deliberately continues static/design discovery even where runtime evidence is incomplete. The following gaps prevent PASS claims but do not block unrelated remediation requirements.

### Browser-state evidence required

1. `LOADING` / `INITIALIZING`.
2. `ERROR` / `UNAVAILABLE`.
3. `EMPTY`.
4. `REQUESTED_UNIT_NOT_FOUND_FALLBACK`.
5. `REQUESTED_SOURCE_NOT_FOUND_FALLBACK`.
6. Claims mode with 6+ claims.
7. Compare mode with 3+ sources and 4+ claims once relation projection exists.
8. Conflict mode with a governed explainable review case and a non-conflicting difference negative case. If same-claim multi-source remains blocked by the current schema, mark that exact state `AUTHORITY_GATED` rather than fabricate persisted variants.
9. Revision pair with two explicit route-bound revision identities plus added/removed/unchanged claims.
10. Provenance with HTTPS URL, internal path, missing locator, long locator, digest, multiple anchors.

### Interaction evidence required

- keyboard focus for mode/source/matrix/deep inspection;
- selected/hover/disabled states;
- Back/Forward/refresh/deep-link restoration;
- invalid object/source/claim/revision parameters;
- focus restoration when RIGHT/BOTTOM collapses or moves;
- no color-only support/conflict semantics;
- table/grid accessible row/column relationships.

### Responsive/Bidi evidence required

- 1440 Arabic and English;
- ~1024 Arabic and English;
- no page-level horizontal overflow;
- internal matrix scrolling preserves claim/source identity;
- logical alignment for headers/cells;
- Bidi isolation for IDs, hashes, URLs, authority/status tokens and code-like state;
- long tokens do not force uncontrolled vertical expansion.

### Data evidence required

A representative local/test fixture should publish topology counts before screenshots: number of KUs, claims, sources, claims/source, current-schema sources/claim, authority classes, locator types, support/scope/exclusion states and missing-anchor cases. While `source_claims.claim_id` remains globally unique, same-claim multi-source rows/conflicts must be reported as schema-gated rather than synthesized. No new semantic truth may be invented solely to satisfy visual variety.

### Schema/authority evidence required

RQ-05 remains unresolved until a Controller-approved persistence contract exists or the product explicitly limits historical provenance to what current rows can truthfully support. Durable reconciliation remains separately unauthorized.

### State-specific visual evidence gap (`RQ-EI-067`)

Current exact-candidate evidence is not state-matched for Compare, Conflicts, Revision and BOTTOM-open. These states require fresh originals bound to exact SHA, route/query, representative data, locale/direction, configured viewport, measured image dimensions and artifact hash before mode-specific visual closure.

### Claims and conflict validation strengthening

- Claims mode must include multiple canonical claims with varied **representable** support coverage and prove claim-centric scan/selection (`RQ-DP-065`).
- Every surfaced conflict/review row must expose a testable reason/relation basis, and benign differences must remain negative cases (`RQ-FN-066`).
- Revision testing must prove exact pair restoration through refresh/Back/Forward/deep link (`RQ-FN-064`).

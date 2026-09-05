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

## Code root-cause and ownership binding

| Root area | Exact-SHA symbol/path | Findings | Primary owner | Collision / dependency |
|---|---|---|---|---|
| Local nested workstation | `resources/js/pages/KnowledgeLearning/ResearchQuality.vue` grid and ordering | DP-001/002/015/022/030–035 | R&Q Frontend | Shared `CepWorkspaceLayout` ownership; integration required for shared regions. |
| LEFT/source selection | `ResearchQuality.vue` source list + `KnowledgeQualityService::workspace()` | DP-004–006, FN-036/037/039 | R&Q Frontend + SourceGovernance Application | Canonical claim scope; do not invent queue entities. |
| Claims mode | `ResearchQualityWorkbench.vue` claims branch + missing claim-summary projection | DP-008–010/065, RB-050/056 | R&Q Frontend + Knowledge/SourceGovernance binding | Claim statement and excerpt availability. |
| Compare mode | `SourceComparisonTable.vue` + `ResearchQualityWorkbench::comparisonRow()` | DP-011–014, FN-043, RB-055 | R&Q Analysis + Frontend | Must preserve no truth ranking. |
| Conflict mode | `ResearchQualityWorkbench::conflicts()` + conflict UI | DP-024, FN-038/066, RB-054 | R&Q Analysis | Canonical relation taxonomy; avoid false conflict labels. |
| Revision mode | `revision_reasoning` analysis + UI + missing route pair | DP-023, FN-064, RB-057 | R&Q Analysis + Knowledge Revision Owner | Historical source provenance gated by RQ-05. |
| Route/task state | controller/workspace route accepts only `object`/`source`; local `mode` ref; no revision-pair task DTO | FN-040–042/064 | R&Q Frontend + Controller | Normalize/allowlist; no localStorage as canonical state. |
| Provenance | `ProvenancePanel.vue`, source projection | DP-016/025/027–029, RB-051/052/056 | R&Q Frontend + SourceGovernance | BOTTOM region + canonical metadata binding. |
| Acceptance data | `database/seeders/W02AcceptanceSeeder.php::seedClaim()` | FX-045–049 | W02 Fixture Owner | Improve only representable topology; same-claim multi-source remains schema-gated. |
| Schema boundary | `SourceClaim` + exact migration (`claim_id UNIQUE`, cascade delete), current revision/source model | SA-058/059 | SourceGovernance Schema Owner | Explicit Controller authority before migrations. |
| Durable reconciliation | current persistence boundary | AD-060 | Controller B / Product Authority | No writer authority from this audit. |

## High-confidence code facts

- `KnowledgeQualityService::workspace()` loads all sources globally.
- `ResearchQualityWorkbench::comparisonRow()` returns source-level counts.
- `conflicts()` does not receive/use canonical claim IDs to scope conflicts.
- Controller R&Q action consumes `object` and `source` only.
- Frontend `mode` is local component state.
- Frontend types contain no canonical claim statement, relation object, excerpt field, gap/limitation context, or historical revision-source binding.
- `ProvenancePanel` displays full locator/digest/anchors persistently in RIGHT.
- Acceptance fixture generates one source row per claim and one authority class.

- Exact migration confirms `source_claims.claim_id` is globally unique and source-owned rows cascade on source deletion; this is why same-claim multi-source support and historical retention cannot be “fixed” by fixture data alone.
- Conflict analysis emits no governed `reason/relation_basis`, producing the separate explainability defect `RQ-FN-066`.
- Revision task state has no explicit pair identity, producing `RQ-FN-064` in addition to the shallow diff payload `RQ-RB-057`.

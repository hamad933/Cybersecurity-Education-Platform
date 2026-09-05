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

## Shared dependencies and cross-surface handoff

This document is **not writer dispatch**. It records dependencies that a later Controller may adjudicate.

| Dependency | Why R&Q needs it | Owner boundary | Collision risk | Safe handoff requirement |
|---|---|---|---|---|
| `CepWorkspaceLayout` | One authoritative LEFT/CENTER/RIGHT/BOTTOM owner, responsive behavior, focus order. | Shared Shell | HIGH | R&Q may change its own composition; shared-shell edits require Controller-owned integration. |
| Knowledge Library / draft workflow | Correct continuation after transient reconciliation. | Library / Unified Editor | HIGH | Reuse existing draft lifecycle; no R&Q revision store, no silent restore/fork. |
| Knowledge canonical claim projection | Stable claim statement, explicit revision-pair identity/diff, gap/limitation context. | Knowledge domain | HIGH | Read-only projection; no B09 mutation. |
| SourceGovernance | Source scoping, relation projection, authority/freshness/provenance. | SourceGovernance | HIGH | Preserve no truth ranking; no unauthorized schema migration. |
| Fixture / acceptance data | Representative multi-source/multi-claim visual state. | W02 Acceptance Data Owner | MEDIUM | Local/testing only; topology checks and governed values. |
| Localization/Bidi | Logical alignment and technical-token isolation. | Shared UI/localization | MEDIUM | Arabic/English runtime validation; no global direction hack. |
| Accessibility validation | Focus, reading order, table semantics, moved-region behavior. | Validation / shared design system | MEDIUM | Exact-SHA browser evidence; static classes are not PASS evidence. |
| RQ-05 persistence | Historical revision→source support. | Schema/Product authority | HIGH | Separate adjudication; this audit grants no migration authority. |

## Collision notes

1. R&Q cannot independently refactor `CepWorkspaceLayout` while Library/Learn/Visualize depend on the same region grammar.
2. A claim×source matrix can be built from existing bounded fields, but durable new relation semantics/excerpts cannot be persisted without authority.
3. Library continuation must call an owner-approved workflow rather than reproduce draft creation logic.
4. Fixture corrections must not be mistaken for canonical runtime import authorization.
5. Research & Quality must remain distinct from Progress/Evidence review and Mastery judgment.

## Final QA dependency clarification

- `RQ-FN-064` requires only a truthful route/task contract unless a broader shared-navigation helper change is proven necessary.
- `RQ-FN-066` can expose a reason only from governed/derivable semantics; relation classes that need new durable schema remain under SourceGovernance authority.
- Fixture owners must not be tasked with producing impossible same-claim multi-source rows while `source_claims.claim_id` remains globally unique.

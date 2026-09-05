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


## Finding counts

| Classification | Count |
|---|---:|
| `PRODUCT_VISUAL_DESIGN_DEFECT` | 36 |
| `FUNCTIONAL_DEFECT` | 11 |
| `DATA_FIXTURE_REPRESENTATIVENESS_GAP` | 5 |
| `CANONICAL_RUNTIME_BINDING_GAP` | 8 |
| `SCHEMA_AUTHORITY_LIMITATION` | 2 |
| `AUTHORITY_DECISION_REQUIRED` | 1 |
| `EVIDENCE_INSUFFICIENT` | 4 |

## Complete material finding ledger


### RQ-DP-001 — Nested workspace duplicates shared region hierarchy

| Field | Value |
|---|---|
| finding_id | RQ-DP-001 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL/LTR shell |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | ResearchQuality.vue creates its own LEFT/CENTER/RIGHT grid inside CepWorkspaceLayout CENTER. |
| governed expected state | One authoritative TOP/LEFT/CENTER/RIGHT/BOTTOM region owner; R&Q supplies semantic content without a second frame. |
| material delta | Observed: ResearchQuality.vue creates its own LEFT/CENTER/RIGHT grid inside CepWorkspaceLayout CENTER. Expected: One authoritative TOP/LEFT/CENTER/RIGHT/BOTTOM region owner; R&Q supplies semantic content without a second frame. |
| user consequence | Competing hierarchy weakens CENTER dominance and makes responsive ownership inconsistent. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | ResearchQuality.vue local grid plus shared CepWorkspaceLayout region contract. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend + Shared Shell Owner |
| shared dependency | CepWorkspaceLayout |
| collision risk | HIGH |
| prohibited shortcut | Do not patch shared shell from the R&Q lane without Controller-owned integration. |
| future validation | Fresh 1440 and ~1024 region-ownership screenshots plus DOM/focus-order inspection. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Fresh 1440 and ~1024 region-ownership screenshots plus DOM/focus-order inspection. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-SHARED-01 |
| discovery status | KNOWN_AND_ADEQUATE |

### RQ-DP-002 — CENTER is not sufficiently dominant at 1440

| Field | Value |
|---|---|
| finding_id | RQ-DP-002 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Local grid reserves 285 px LEFT and 315 px RIGHT inside shared CENTER, while evidence work remains sparse. |
| governed expected state | CENTER must visually dominate the evidence task; side regions are subordinate navigation/context. |
| material delta | Observed: Local grid reserves 285 px LEFT and 315 px RIGHT inside shared CENTER, while evidence work remains sparse. Expected: CENTER must visually dominate the evidence task; side regions are subordinate navigation/context. |
| user consequence | Primary comparison work reads as one panel among peers rather than the workstation core. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Hard-coded xl grid proportions combined with nested workspace composition. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Shared Shell |
| collision risk | MEDIUM |
| prohibited shortcut | Do not hide evidence to make CENTER look larger. |
| future validation | Measure region proportions in fresh 1440 browser capture with representative multi-source data. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440. |
| closure criterion | Close only when: Measure region proportions in fresh 1440 browser capture with representative multi-source data. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-01 / visual forensics |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-003 — CENTER has material dead space under representative task framing

| Field | Value |
|---|---|
| finding_id | RQ-DP-003 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Claims mode often shows one/few tall claim cards with large unused center area. |
| governed expected state | Dense claim/source evidence should consume the main workspace with scannable rows/cells. |
| material delta | Observed: Claims mode often shows one/few tall claim cards with large unused center area. Expected: Dense claim/source evidence should consume the main workspace with scannable rows/cells. |
| user consequence | Reviewer must scroll between sparse cards instead of scanning evidence relationships. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Card-based selected-source projection plus non-representative fixture. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Fixture Owner |
| collision risk | MEDIUM |
| prohibited shortcut | Do not fill dead space with decorative cards or fake content. |
| future validation | Render a governed multi-claim/multi-source fixture and compare information density against reference intent. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440. |
| closure criterion | Close only when: Render a governed multi-claim/multi-source fixture and compare information density against reference intent. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-01 / Primary work depth |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-004 — LEFT source cards are too tall and card-heavy

| Field | Value |
|---|---|
| finding_id | RQ-DP-004 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Each source consumes a bordered rounded card with title and two badges. |
| governed expected state | LEFT should maximize task/navigation density and preserve quick source/claim scanning. |
| material delta | Observed: Each source consumes a bordered rounded card with title and two badges. Expected: LEFT should maximize task/navigation density and preserve quick source/claim scanning. |
| user consequence | A modest source set consumes excessive vertical space and reduces discoverable task context. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | p-3 rounded-xl source cards with per-item badge block. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | None |
| collision risk | LOW |
| prohibited shortcut | Do not remove authority data entirely; relocate/detail it by hierarchy. |
| future validation | Test 10+ source rows at 1440 and ~1024 without excessive vertical scrolling. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Test 10+ source rows at 1440 and ~1024 without excessive vertical scrolling. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-01 / LEFT role |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-005 — LEFT lacks governed grouping and filter facets for research review

| Field | Value |
|---|---|
| finding_id | RQ-DP-005 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | LEFT is a flat list of SourceRecord entries with no claim grouping, authority grouping, coverage grouping, or task filter. |
| governed expected state | Structural/task navigation should support claim-linked scope and deliberate wider comparison. |
| material delta | Observed: LEFT is a flat list of SourceRecord entries with no claim grouping, authority grouping, coverage grouping, or task filter. Expected: Structural/task navigation should support claim-linked scope and deliberate wider comparison. |
| user consequence | Users cannot quickly reduce a large corpus to the evidence subset relevant to the current review question. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Frontend renders quality.sources directly; backend supplies a flat global source list. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend + R&Q Application |
| shared dependency | SourceGovernance |
| collision risk | MEDIUM |
| prohibited shortcut | Do not invent durable queues or unsupported authority classes. |
| future validation | Representative 20+ source fixture; verify filter/group semantics from existing governed fields only. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Representative 20+ source fixture; verify filter/group semantics from existing governed fields only. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-01 / LEFT role |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-006 — LEFT behaves as a mini status dashboard rather than structural navigation

| Field | Value |
|---|---|
| finding_id | RQ-DP-006 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Authority class and review_status badges are repeated on every source card while claim/task structure is absent. |
| governed expected state | LEFT should prioritize where the reviewer is in the source/claim structure; detailed authority belongs in contextual inspection. |
| material delta | Observed: Authority class and review_status badges are repeated on every source card while claim/task structure is absent. Expected: LEFT should prioritize where the reviewer is in the source/claim structure; detailed authority belongs in contextual inspection. |
| user consequence | Repeated status decoration crowds out navigation signal. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Source-card template exposes status badges as primary per-row metadata. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | None |
| collision risk | LOW |
| prohibited shortcut | Do not duplicate the same authority/status detail in LEFT, RIGHT, and CENTER. |
| future validation | Verify one authoritative presentation of source status after region rebalance. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Verify one authoritative presentation of source status after region rebalance. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-01 / LEFT role |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-007 — Selected source replaces canonical KU identity in the main heading

| Field | Value |
|---|---|
| finding_id | RQ-DP-007 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | H1 prefers quality.active_source.title over active.title_ar. |
| governed expected state | Canonical Knowledge Unit identity remains stable; selected source is subordinate context. |
| material delta | Observed: H1 prefers quality.active_source.title over active.title_ar. Expected: Canonical Knowledge Unit identity remains stable; selected source is subordinate context. |
| user consequence | Reviewers can lose which canonical object is under review while navigating sources. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | ResearchQuality.vue H1 fallback order. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Knowledge Library |
| collision risk | LOW |
| prohibited shortcut | Do not duplicate KU identity in several competing headings. |
| future validation | Navigate among sources and confirm stable KU header with source shown as selected context. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Navigate among sources and confirm stable KU header with source shown as selected context. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Reference Delta: Canonical object identity |
| discovery status | KNOWN_AND_ADEQUATE |

### RQ-DP-008 — No visually persistent selected-claim focal state

| Field | Value |
|---|---|
| finding_id | RQ-DP-008 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Claims are rendered as independent articles; none becomes the persistent workbench focal object. |
| governed expected state | Selected claim should be visually obvious across matrix, provenance, relations, and drilldown. |
| material delta | Observed: Claims are rendered as independent articles; none becomes the persistent workbench focal object. Expected: Selected claim should be visually obvious across matrix, provenance, relations, and drilldown. |
| user consequence | Reviewer cannot maintain a stable evidence question while switching source/context. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | No selected-claim state in frontend props or route. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | R&Q Application |
| collision risk | MEDIUM |
| prohibited shortcut | Do not simulate selection with color only or unsaved hidden local state. |
| future validation | Select claim, switch mode/source, Back/Forward, and confirm persistent visual selection. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Select claim, switch mode/source, Back/Forward, and confirm persistent visual selection. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-04 / task context |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-009 — Claim rows use excessive vertical padding for a dense workbench

| Field | Value |
|---|---|
| finding_id | RQ-DP-009 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Claim article p-4 plus nested scope cards creates high row height. |
| governed expected state | Claims should be compact enough to compare several at once while preserving readable excerpts. |
| material delta | Observed: Claim article p-4 plus nested scope cards creates high row height. Expected: Claims should be compact enough to compare several at once while preserving readable excerpts. |
| user consequence | Fewer claims fit above the fold, weakening comparison and coverage awareness. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | ResearchQualityWorkbench.vue card geometry. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | None |
| collision risk | LOW |
| prohibited shortcut | Do not shrink text below accessible sizes to gain density. |
| future validation | Compare claim count visible above fold before/after with 6+ claims. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Compare claim count visible above fold before/after with 6+ claims. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Primary work depth |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-010 — Nested support/exclusion cards create card soup

| Field | Value |
|---|---|
| finding_id | RQ-DP-010 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Each claim article contains two additional rounded bordered cards for supported and excluded scope. |
| governed expected state | Support/exclusion should be represented as compact relation fields/cells within primary evidence structure. |
| material delta | Observed: Each claim article contains two additional rounded bordered cards for supported and excluded scope. Expected: Support/exclusion should be represented as compact relation fields/cells within primary evidence structure. |
| user consequence | Repeated nested containers dilute hierarchy and make all evidence blocks look equally important. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Card-inside-card composition in Claims mode. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | None |
| collision risk | LOW |
| prohibited shortcut | Do not remove exclusion semantics; compact their presentation. |
| future validation | Visual hierarchy review with at least 6 claims and multiple sources. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Visual hierarchy review with at least 6 claims and multiple sources. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Primary work depth |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-011 — Claim/source relationship lacks a scannable matrix visual grammar

| Field | Value |
|---|---|
| finding_id | RQ-DP-011 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Current primary work never shows claims as one axis and sources as the other. |
| governed expected state | A claim×source relationship structure exposes support, anchor, scope/exclusion, and authority without truth ranking. |
| material delta | Observed: Current primary work never shows claims as one axis and sources as the other. Expected: A claim×source relationship structure exposes support, anchor, scope/exclusion, and authority without truth ranking. |
| user consequence | Users must mentally join separate screens/cards instead of reading relationships directly. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Frontend modes split selected-source claim cards from source aggregate table. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | R&Q Analysis Projection |
| collision risk | HIGH |
| prohibited shortcut | Do not implement truth scores/preferred-source ranking. |
| future validation | Browser evidence with 4+ claims × 3+ sources and no page-level horizontal overflow. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Browser evidence with 4+ claims × 3+ sources and no page-level horizontal overflow. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-01 / Compare semantics |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-012 — Compare table row and cell padding are too loose for evidence density

| Field | Value |
|---|---|
| finding_id | RQ-DP-012 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Rows use px-4 py-3 and header py-3.5 for six mostly scalar fields. |
| governed expected state | Comparison density should prioritize more relationships per viewport. |
| material delta | Observed: Rows use px-4 py-3 and header py-3.5 for six mostly scalar fields. Expected: Comparison density should prioritize more relationships per viewport. |
| user consequence | Current table consumes space without delivering corresponding evidence depth. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | SourceComparisonTable.vue padding and typography. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | None |
| collision risk | LOW |
| prohibited shortcut | Do not compensate solely by tiny fonts. |
| future validation | Evaluate 12+ rows at 1440 and ~1024. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Evaluate 12+ rows at 1440 and ~1024. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-01 / Compare density |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-013 — Compare column balance overweights source identity and underweights evidence meaning

| Field | Value |
|---|---|
| finding_id | RQ-DP-013 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Five narrow scalar columns follow a wide source cell; no evidence-bearing columns exist. |
| governed expected state | Column proportions should reflect claim relation, excerpt/anchor, scope/exclusion, and authority importance. |
| material delta | Observed: Five narrow scalar columns follow a wide source cell; no evidence-bearing columns exist. Expected: Column proportions should reflect claim relation, excerpt/anchor, scope/exclusion, and authority importance. |
| user consequence | Visual scanning optimizes counts rather than the decision question. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Aggregate comparison schema determines low-value column set. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | R&Q Analysis Projection |
| collision risk | MEDIUM |
| prohibited shortcut | Do not add arbitrary columns unsupported by governed data. |
| future validation | Validate column widths with representative claim/source matrix content. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440. |
| closure criterion | Close only when: Validate column widths with representative claim/source matrix content. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No direct prior item |
| discovery status | MISSED_NEW |

### RQ-DP-014 — Compare view has no stable claim axis or sticky relationship context

| Field | Value |
|---|---|
| finding_id | RQ-DP-014 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Only table header is sticky; rows are source summaries and there is no claim axis. |
| governed expected state | A wide/scrolling evidence matrix must preserve claim identity and column context during inspection. |
| material delta | Observed: Only table header is sticky; rows are source summaries and there is no claim axis. Expected: A wide/scrolling evidence matrix must preserve claim identity and column context during inspection. |
| user consequence | Horizontal/vertical inspection would lose relationship context once density increases. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Current table structure is source-row-only. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | R&Q Analysis Projection |
| collision risk | MEDIUM |
| prohibited shortcut | Do not solve by page-level horizontal scrolling. |
| future validation | With a representative wide matrix, verify sticky identifiers and internal scrolling. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: With a representative wide matrix, verify sticky identifiers and internal scrolling. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No direct prior item |
| discovery status | MISSED_NEW |

### RQ-DP-015 — RIGHT is too large and persistent for contextual evidence

| Field | Value |
|---|---|
| finding_id | RQ-DP-015 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | RIGHT reserves 315 px and min-height 740 for locator/digest/anchors plus boundary warnings. |
| governed expected state | RIGHT should stay compact and unique to selected context; technical depth is temporary BOTTOM work. |
| material delta | Observed: RIGHT reserves 315 px and min-height 740 for locator/digest/anchors plus boundary warnings. Expected: RIGHT should stay compact and unique to selected context; technical depth is temporary BOTTOM work. |
| user consequence | Secondary metadata competes continuously with primary comparison. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Fixed xl right column plus always-rendered ProvenancePanel/boundary sections. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Shared Shell |
| collision risk | MEDIUM |
| prohibited shortcut | Do not delete provenance; relocate deep detail. |
| future validation | 1440 capture with RIGHT compact and CENTER dominant. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440. |
| closure criterion | Close only when: 1440 capture with RIGHT compact and CENTER dominant. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Reference Delta: RIGHT role |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-016 — Raw locator/digest presentation overweights technical metadata

| Field | Value |
|---|---|
| finding_id | RQ-DP-016 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Full URL/path and full sha256 appear as large persistent RIGHT cards. |
| governed expected state | Technical provenance remains available but subordinate, summarized until deep inspection. |
| material delta | Observed: Full URL/path and full sha256 appear as large persistent RIGHT cards. Expected: Technical provenance remains available but subordinate, summarized until deep inspection. |
| user consequence | Long technical strings consume prime visual space and distract from evidence meaning. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | ProvenancePanel.vue always expands locator and digest. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | None |
| collision risk | LOW |
| prohibited shortcut | Do not remove integrity information from deep inspection. |
| future validation | Verify compact summary + accessible drilldown/copy in BOTTOM. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Verify compact summary + accessible drilldown/copy in BOTTOM. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Reference Delta: RIGHT role |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-017 — Engineering boundary warnings dominate product-facing RIGHT

| Field | Value |
|---|---|
| finding_id | RQ-DP-017 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Multiple rose/amber cards explain Evidence Review separation, truth-decision limits, and persistence ownership. |
| governed expected state | Product UI should express only actionable boundary guidance; implementation-state diagnostics stay out of primary workspace. |
| material delta | Observed: Multiple rose/amber cards explain Evidence Review separation, truth-decision limits, and persistence ownership. Expected: Product UI should express only actionable boundary guidance; implementation-state diagnostics stay out of primary workspace. |
| user consequence | Reviewer attention is pulled from evidence to internal governance mechanics. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | ResearchQuality.vue boundary section. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Controller language authority |
| collision risk | LOW |
| prohibited shortcut | Do not weaken the actual semantic boundary; rewrite/relocate rather than remove meaning. |
| future validation | Browser evidence shows concise product copy and no internal state tokens. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Browser evidence shows concise product copy and no internal state tokens. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-02 / Raw persistence state |
| discovery status | KNOWN_AND_ADEQUATE |

### RQ-DP-018 — Uniform borders/elevation flatten evidence hierarchy

| Field | Value |
|---|---|
| finding_id | RQ-DP-018 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Almost every region/article/sub-card uses rounded border + dark panel + shadow. |
| governed expected state | Containers should distinguish primary work, selection, metadata, warnings, and deep inspection by hierarchy rather than identical card treatment. |
| material delta | Observed: Almost every region/article/sub-card uses rounded border + dark panel + shadow. Expected: Containers should distinguish primary work, selection, metadata, warnings, and deep inspection by hierarchy rather than identical card treatment. |
| user consequence | All blocks compete visually and the eye lacks a clear evidence reading path. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Repeated rounded-xl/2xl border/bg/shadow grammar. |
| root-cause confidence | MEDIUM |
| owner | R&Q Frontend |
| shared dependency | Design System |
| collision risk | LOW |
| prohibited shortcut | Do not pixel-clone the reference; preserve CEP design tokens. |
| future validation | Visual review against governed hierarchy at 1440 and ~1024. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Visual review against governed hierarchy at 1440 and ~1024. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior “exact typography/card styling” treated too broadly as intentional deviation |
| discovery status | MISCLASSIFIED |

### RQ-DP-019 — Raw enum/status badges are visually noisy and insufficiently translated

| Field | Value |
|---|---|
| finding_id | RQ-DP-019 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | authority_class, review_status, assessment and traceability_state are frequently shown as raw strings. |
| governed expected state | User-facing status labels should have stable semantics, hierarchy, and bidi-safe display while retaining inspectable raw values only where needed. |
| material delta | Observed: authority_class, review_status, assessment and traceability_state are frequently shown as raw strings. Expected: User-facing status labels should have stable semantics, hierarchy, and bidi-safe display while retaining inspectable raw values only where needed. |
| user consequence | Engineering vocabulary slows scanning and makes fixture artifacts look like product terminology. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Direct interpolation of raw backend strings. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | SourceGovernance vocabulary |
| collision risk | MEDIUM |
| prohibited shortcut | Do not silently remap authority semantics. |
| future validation | Verify governed label mapping with raw value available only in deep inspection where needed. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Verify governed label mapping with raw value available only in deep inspection where needed. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No direct prior item |
| discovery status | MISSED_NEW |

### RQ-DP-020 — Emoji mode icons weaken professional visual grammar

| Field | Value |
|---|---|
| finding_id | RQ-DP-020 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Modes use 📋 ⚖️ ⚠️ 🔄 glyphs. |
| governed expected state | Use the governed icon system and consistent stroke/size/alignment for workstation controls. |
| material delta | Observed: Modes use 📋 ⚖️ ⚠️ 🔄 glyphs. Expected: Use the governed icon system and consistent stroke/size/alignment for workstation controls. |
| user consequence | Platform appearance becomes inconsistent across OS/font rendering and sibling surfaces. |
| severity | LOW |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Hard-coded emoji strings in modes array. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Design System |
| collision risk | LOW |
| prohibited shortcut | Do not introduce a new icon library solely for this surface. |
| future validation | Cross-platform browser capture and sibling-surface grammar check. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Cross-platform browser capture and sibling-surface grammar check. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No direct prior item |
| discovery status | MISSED_NEW |

### RQ-DP-021 — TOP modes are equally weighted and weakly task-oriented

| Field | Value |
|---|---|
| finding_id | RQ-DP-021 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Claims/Compare/Conflicts/Revision are same-sized segmented buttons with little indication of current review goal/context. |
| governed expected state | TOP should communicate current mode, selected task context, and primary continuation without becoming a dashboard. |
| material delta | Observed: Claims/Compare/Conflicts/Revision are same-sized segmented buttons with little indication of current review goal/context. Expected: TOP should communicate current mode, selected task context, and primary continuation without becoming a dashboard. |
| user consequence | Reviewer knows the tab but not the active claim/relation/revision task. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Local toolbar only models mode. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | R&Q Route State |
| collision risk | MEDIUM |
| prohibited shortcut | Do not add unauthorized durable queue/actions. |
| future validation | Verify selected task context in TOP after claim/relation route state exists. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Verify selected task context in TOP after claim/relation route state exists. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No direct prior item |
| discovery status | MISSED_NEW |

### RQ-DP-022 — BOTTOM deep workspace is not realized as an independent inspection region

| Field | Value |
|---|---|
| finding_id | RQ-DP-022 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Deep provenance lives in RIGHT; revision/conflict content replaces CENTER rather than opening temporary BOTTOM depth. |
| governed expected state | BOTTOM owns temporary full provenance, diff, trace, diagnostics, and deep relation detail. |
| material delta | Observed: Deep provenance lives in RIGHT; revision/conflict content replaces CENTER rather than opening temporary BOTTOM depth. Expected: BOTTOM owns temporary full provenance, diff, trace, diagnostics, and deep relation detail. |
| user consequence | Users cannot inspect deep evidence while preserving primary comparison context. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | R&Q local frame does not consume shared BOTTOM region. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend + Shared Shell Owner |
| shared dependency | CepWorkspaceLayout |
| collision risk | HIGH |
| prohibited shortcut | Do not create another nested bottom drawer inside CENTER if shared BOTTOM exists. |
| future validation | Open/close deep inspection while retaining selected matrix cell and keyboard focus. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Open/close deep inspection while retaining selected matrix cell and keyboard focus. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Reference Delta: Bottom deep workspace |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-023 — Revision mode reads as KPI dashboard instead of revision inspection

| Field | Value |
|---|---|
| finding_id | RQ-DP-023 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Revision mode shows three count cards then claim_id→source_id chips. |
| governed expected state | Revision mode should expose pairwise claim-set change/reasoning with clear current/target context; historical source trace remains gated. |
| material delta | Observed: Revision mode shows three count cards then claim_id→source_id chips. Expected: Revision mode should expose pairwise claim-set change/reasoning with clear current/target context; historical source trace remains gated. |
| user consequence | Counts do not explain what changed, why, or which claims need review. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | ResearchQualityWorkbench.vue revision section and analysis.revision_reasoning shape. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | R&Q Analysis Projection |
| collision risk | MEDIUM |
| prohibited shortcut | Do not fabricate historical source provenance. |
| future validation | Use two governed revisions and verify added/removed/unchanged claim-set inspection. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Use two governed revisions and verify added/removed/unchanged claim-set inspection. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-03 / revision reasoning |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-024 — Conflict variants are too vertically heavy for side-by-side reasoning

| Field | Value |
|---|---|
| finding_id | RQ-DP-024 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Conflict article contains nested variant cards, explanatory paragraph, textarea and copy action. |
| governed expected state | Conflict comparison should keep variants compact and relation-focused, with rationale secondary. |
| material delta | Observed: Conflict article contains nested variant cards, explanatory paragraph, textarea and copy action. Expected: Conflict comparison should keep variants compact and relation-focused, with rationale secondary. |
| user consequence | The user scrolls through containers instead of comparing differences at a glance. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Conflict-mode card stack. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Relation semantics |
| collision risk | MEDIUM |
| prohibited shortcut | Do not imply truth winner or persist reviewer rationale without authority. |
| future validation | Representative 3-variant conflict capture at 1440/~1024. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Representative 3-variant conflict capture at 1440/~1024. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-03 / conflict work |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-025 — Long technical tokens create excessive visual height

| Field | Value |
|---|---|
| finding_id | RQ-DP-025 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | break-all URLs, paths, sha256 and persistence tokens wrap across narrow cards. |
| governed expected state | Technical tokens should be isolated, truncated/summarized with accessible reveal/copy, and confined to deep inspection. |
| material delta | Observed: break-all URLs, paths, sha256 and persistence tokens wrap across narrow cards. Expected: Technical tokens should be isolated, truncated/summarized with accessible reveal/copy, and confined to deep inspection. |
| user consequence | RIGHT and 1024 layouts become tall and difficult to scan. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | ProvenancePanel break-all plus boundary raw tokens. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | None |
| collision risk | LOW |
| prohibited shortcut | Do not truncate without a way to inspect/copy the full value. |
| future validation | Test worst-case 200+ character locator at both viewports. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Test worst-case 200+ character locator at both viewports. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No direct prior item |
| discovery status | MISSED_NEW |

### RQ-DP-026 — Metadata typography is too small for sustained review

| Field | Value |
|---|---|
| finding_id | RQ-DP-026 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Numerous labels/tokens use 9–11 px classes. |
| governed expected state | Metadata hierarchy may be compact but must remain legible under normal zoom and long review sessions. |
| material delta | Observed: Numerous labels/tokens use 9–11 px classes. Expected: Metadata hierarchy may be compact but must remain legible under normal zoom and long review sessions. |
| user consequence | Important provenance/status cues become hard to read and are visually de-emphasized beyond usefulness. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | text-[9px], text-[10px], text-[11px] across controls, chips, provenance. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Design System / Accessibility |
| collision risk | LOW |
| prohibited shortcut | Do not compensate density by reducing font size further. |
| future validation | Browser check at 100%/125% zoom and accessibility review. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Browser check at 100%/125% zoom and accessibility review. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No direct prior item |
| discovery status | MISSED_NEW |

### RQ-DP-027 — Physical text-right alignment is inconsistent with Bidi-safe logical layout

| Field | Value |
|---|---|
| finding_id | RQ-DP-027 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Comparison TH cells use text-right even for English headers in a mixed-direction table. |
| governed expected state | Use logical start/end alignment according to locale and content islands. |
| material delta | Observed: Comparison TH cells use text-right even for English headers in a mixed-direction table. Expected: Use logical start/end alignment according to locale and content islands. |
| user consequence | Header/cell alignment can feel reversed or inconsistent across Arabic/English. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | SourceComparisonTable.vue hard-coded text-right. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Locale direction runtime |
| collision risk | LOW |
| prohibited shortcut | Do not globally force LTR/RTL to fix one table. |
| future validation | Arabic and English captures with logical alignment. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Arabic and English captures with logical alignment. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior Bidi qualification only |
| discovery status | MISSED_NEW |

### RQ-DP-028 — Source-card authority/review tokens are not explicitly bidi-isolated

| Field | Value |
|---|---|
| finding_id | RQ-DP-028 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | authority_class and review_status spans in LEFT lack dir/bdi despite potentially English/code-like values. |
| governed expected state | Technical/status islands must be isolated so punctuation/order remains stable inside RTL rows. |
| material delta | Observed: authority_class and review_status spans in LEFT lack dir/bdi despite potentially English/code-like values. Expected: Technical/status islands must be isolated so punctuation/order remains stable inside RTL rows. |
| user consequence | Mixed-language badges can reorder or read awkwardly. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | ResearchQuality.vue source-card spans. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Locale direction runtime |
| collision risk | LOW |
| prohibited shortcut | Do not wrap entire LEFT in LTR. |
| future validation | Use mixed Arabic/English authority strings and inspect ordering. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Use mixed Arabic/English authority strings and inspect ordering. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior Bidi qualification only |
| discovery status | MISSED_NEW |

### RQ-DP-029 — Inline English engineering terms are inconsistently isolated inside Arabic copy

| Field | Value |
|---|---|
| finding_id | RQ-DP-029 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Some prose includes provenance/reconciliation/system terms without consistent bidi isolation. |
| governed expected state | Arabic prose and LTR technical terms should remain visually stable and punctuated correctly. |
| material delta | Observed: Some prose includes provenance/reconciliation/system terms without consistent bidi isolation. Expected: Arabic prose and LTR technical terms should remain visually stable and punctuated correctly. |
| user consequence | Mixed-direction explanatory copy can reorder punctuation and reduce comprehension. |
| severity | LOW |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Boundary and helper copy has mixed isolation practices. |
| root-cause confidence | MEDIUM |
| owner | R&Q Frontend |
| shared dependency | Localization |
| collision risk | LOW |
| prohibited shortcut | Do not translate governed technical identifiers into misleading Arabic. |
| future validation | Arabic browser capture with punctuation and screen-reader order inspection. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Arabic browser capture with punctuation and screen-reader order inspection. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior Bidi qualification only |
| discovery status | MISSED_NEW |

### RQ-DP-030 — ~1024 RIGHT relocation is materially harmful in the current composition

| Field | Value |
|---|---|
| finding_id | RQ-DP-030 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | ~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | RIGHT becomes md:col-span-2 below LEFT+CENTER while still containing full provenance and boundary cards. |
| governed expected state | Responsive relocation is allowed only if CENTER remains dominant and moved context stays compact/unique. |
| material delta | Observed: RIGHT becomes md:col-span-2 below LEFT+CENTER while still containing full provenance and boundary cards. Expected: Responsive relocation is allowed only if CENTER remains dominant and moved context stays compact/unique. |
| user consequence | The workbench becomes a long document and source context is no longer concurrently visible. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | ResearchQuality.vue responsive grid and oversized RIGHT content. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Shared Shell |
| collision risk | HIGH |
| prohibited shortcut | Do not treat any responsive reflow as acceptable merely because there is no clipping. |
| future validation | Fresh ~1024 capture with representative content and bounded page height. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for ~1024. |
| closure criterion | Close only when: Fresh ~1024 capture with representative content and bounded page height. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior ledger marked relocation “allowed in principle” |
| discovery status | MISCLASSIFIED |

### RQ-DP-031 — ~1024 contextual panel expands into a giant full-width secondary section

| Field | Value |
|---|---|
| finding_id | RQ-DP-031 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | ~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | The 315 px contextual concept becomes full two-column width beneath main work. |
| governed expected state | Moved context should use compact disclosure/deep inspection patterns. |
| material delta | Observed: The 315 px contextual concept becomes full two-column width beneath main work. Expected: Moved context should use compact disclosure/deep inspection patterns. |
| user consequence | Secondary metadata receives more visual acreage than primary evidence. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | md:col-span-2 + min-h-[740px] on RIGHT. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Shared Shell |
| collision risk | MEDIUM |
| prohibited shortcut | Do not simply reduce content via omission; preserve accessible drilldown. |
| future validation | ~1024 screenshot measuring contextual section height versus primary work. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for ~1024. |
| closure criterion | Close only when: ~1024 screenshot measuring contextual section height versus primary work. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior 1024 qualification |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-032 — ~1024 CENTER is constrained by fixed LEFT before comparison density is addressed

| Field | Value |
|---|---|
| finding_id | RQ-DP-032 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | ~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | At md the grid reserves 220 px for LEFT and leaves the remainder for CENTER; RIGHT is deferred below. |
| governed expected state | CENTER should remain dominant and structural navigation should compact/collapse as needed. |
| material delta | Observed: At md the grid reserves 220 px for LEFT and leaves the remainder for CENTER; RIGHT is deferred below. Expected: CENTER should remain dominant and structural navigation should compact/collapse as needed. |
| user consequence | Primary comparison is narrow while a persistent source rail remains fixed. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | md:grid-cols-[220px_minmax(0,1fr)]. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Shared Shell |
| collision risk | MEDIUM |
| prohibited shortcut | Do not hide source selection without an accessible replacement. |
| future validation | ~1024 multi-source/matrix state with no page-level horizontal overflow. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for ~1024. |
| closure criterion | Close only when: ~1024 multi-source/matrix state with no page-level horizontal overflow. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior responsive qualification |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-033 — ~1024 mode toolbar relies on horizontal scrolling for core modes

| Field | Value |
|---|---|
| finding_id | RQ-DP-033 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | ~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Top mode control is flex-nowrap + overflow-x-auto. |
| governed expected state | Core review modes should remain discoverable without hidden horizontal scroll at required desktop-half width. |
| material delta | Observed: Top mode control is flex-nowrap + overflow-x-auto. Expected: Core review modes should remain discoverable without hidden horizontal scroll at required desktop-half width. |
| user consequence | Users may miss Conflicts/Revision or lose selected-state visibility. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | ResearchQuality.vue mode toolbar classes. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | None |
| collision risk | LOW |
| prohibited shortcut | Do not abbreviate labels into ambiguous icons only. |
| future validation | ~1024 Arabic/English capture with all modes discoverable. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for ~1024. |
| closure criterion | Close only when: ~1024 Arabic/English capture with all modes discoverable. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior responsive qualification |
| discovery status | UNDER_SPECIFIED |

### RQ-DP-034 — ~1024 wide comparison lacks a designed matrix-overflow strategy

| Field | Value |
|---|---|
| finding_id | RQ-DP-034 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | ~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Current aggregate table uses overflow-auto, but no claim axis/cell selection exists to preserve context when a real matrix becomes wider. |
| governed expected state | Use internal scrolling with sticky identifiers and no page-level overflow. |
| material delta | Observed: Current aggregate table uses overflow-auto, but no claim axis/cell selection exists to preserve context when a real matrix becomes wider. Expected: Use internal scrolling with sticky identifiers and no page-level overflow. |
| user consequence | A future richer table would otherwise become unusable at the required secondary viewport. |
| severity | MEDIUM |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Current table does not encode the necessary responsive matrix grammar. |
| root-cause confidence | MEDIUM |
| owner | R&Q Frontend |
| shared dependency | R&Q Analysis Projection |
| collision risk | MEDIUM |
| prohibited shortcut | Do not solve by dropping evidence columns at 1024 unless governed prioritization exists. |
| future validation | Representative 4×4+ matrix at ~1024 with sticky context. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for ~1024. |
| closure criterion | Close only when: Representative 4×4+ matrix at ~1024 with sticky context. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No direct prior item |
| discovery status | MISSED_NEW |

### RQ-DP-035 — ~1024 vertical rhythm turns the workstation into a long-form page

| Field | Value |
|---|---|
| finding_id | RQ-DP-035 |
| category/subcategory | Visual Design / Workbench / composition / density / responsive / Bidi |
| surface state | Current exact candidate / discovery audit |
| viewport | ~1024 |
| direction | RTL |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | LEFT/CENTER followed by 740 px+ RIGHT plus nested cards creates extended page scrolling. |
| governed expected state | A workstation should keep current task, source context, and deep inspection bounded and recoverable. |
| material delta | Observed: LEFT/CENTER followed by 740 px+ RIGHT plus nested cards creates extended page scrolling. Expected: A workstation should keep current task, source context, and deep inspection bounded and recoverable. |
| user consequence | Reviewers lose spatial orientation and repeatedly scroll between evidence and provenance. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | Responsive ordering + fixed min-heights + card-heavy content. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Shared Shell |
| collision risk | MEDIUM |
| prohibited shortcut | Do not enforce fixed viewport heights that clip content. |
| future validation | Measure scroll depth with representative data and verify focus restoration. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for ~1024. |
| closure criterion | Close only when: Measure scroll depth with representative data and verify focus restoration. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No direct prior item |
| discovery status | MISSED_NEW |

### RQ-FN-036 — Default source set is globally unscoped to the active KU/claims

| Field | Value |
|---|---|
| finding_id | RQ-FN-036 |
| category/subcategory | Functional / Scope / state / navigation / workflow |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | N/A |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | KnowledgeQualityService::workspace() loads every SourceRecord before any active-claim scoping. |
| governed expected state | Default R&Q scope must be claim-linked/current-object sources; wider corpus comparison is explicit. |
| material delta | Observed: KnowledgeQualityService::workspace() loads every SourceRecord before any active-claim scoping. Expected: Default R&Q scope must be claim-linked/current-object sources; wider corpus comparison is explicit. |
| user consequence | Unrelated sources can enter LEFT, Compare, Provenance and conflict analysis. |
| severity | HIGH |
| classification | FUNCTIONAL_DEFECT |
| root cause or NOT_YET_PROVEN | SourceRecord::query()->with(claims)->get() in workspace(). |
| root-cause confidence | HIGH |
| owner | R&Q Application / SourceGovernance |
| shared dependency | Canonical citations |
| collision risk | HIGH |
| prohibited shortcut | Do not infer relevance from title or first-record position. |
| future validation | Backend tests with unrelated sources present. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Backend tests with unrelated sources present. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-01 |
| discovery status | KNOWN_AND_ADEQUATE |

### RQ-FN-037 — Fallback can select the first unrelated global source

| Field | Value |
|---|---|
| finding_id | RQ-FN-037 |
| category/subcategory | Functional / Scope / state / navigation / workflow |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | N/A |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | If no requested/matching active-revision source is found, active = sources[0]. |
| governed expected state | No-source/no-match should produce explicit bounded state or a claim-linked default, not unrelated fallback. |
| material delta | Observed: If no requested/matching active-revision source is found, active = sources[0]. Expected: No-source/no-match should produce explicit bounded state or a claim-linked default, not unrelated fallback. |
| user consequence | The main H1 and claims can silently pivot to unrelated evidence. |
| severity | HIGH |
| classification | FUNCTIONAL_DEFECT |
| root cause or NOT_YET_PROVEN | KnowledgeQualityService::workspace() DEFAULTED_TO_FIRST_SOURCE. |
| root-cause confidence | HIGH |
| owner | R&Q Application |
| shared dependency | Selection-state UX |
| collision risk | HIGH |
| prohibited shortcut | Do not silently substitute “nearest” global source. |
| future validation | Test KU with citations but no matching SourceClaim and with unrelated sources present. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Test KU with citations but no matching SourceClaim and with unrelated sources present. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-01 did not isolate fallback defect |
| discovery status | MISSED_NEW |

### RQ-FN-038 — Conflict detection runs over global sources rather than current canonical claims

| Field | Value |
|---|---|
| finding_id | RQ-FN-038 |
| category/subcategory | Functional / Scope / state / navigation / workflow |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | N/A |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | ResearchQualityWorkbench::conflicts($sources) ignores canonicalClaimIds. |
| governed expected state | Conflict work should be bounded to current review scope unless wider comparison is explicitly selected. |
| material delta | Observed: ResearchQualityWorkbench::conflicts($sources) ignores canonicalClaimIds. Expected: Conflict work should be bounded to current review scope unless wider comparison is explicitly selected. |
| user consequence | A reviewer can see conflicts unrelated to the active KU. |
| severity | HIGH |
| classification | FUNCTIONAL_DEFECT |
| root cause or NOT_YET_PROVEN | analyze() passes all sources to conflicts() without canonical filter. |
| root-cause confidence | HIGH |
| owner | R&Q Analysis |
| shared dependency | Source scoping |
| collision risk | HIGH |
| prohibited shortcut | Do not suppress conflicts by arbitrary source limit; scope semantically. |
| future validation | Inject unrelated conflicting claim and verify it does not appear in current-object conflict mode. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Inject unrelated conflicting claim and verify it does not appear in current-object conflict mode. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-03 |
| discovery status | KNOWN_AND_ADEQUATE |

### RQ-FN-039 — Compare mode includes global sources rather than current-task sources

| Field | Value |
|---|---|
| finding_id | RQ-FN-039 |
| category/subcategory | Functional / Scope / state / navigation / workflow |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | N/A |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | comparison.rows maps every source returned by workspace(). |
| governed expected state | Default compare should reflect active claim/KU scope with an explicit wider-corpus action. |
| material delta | Observed: comparison.rows maps every source returned by workspace(). Expected: Default compare should reflect active claim/KU scope with an explicit wider-corpus action. |
| user consequence | Irrelevant rows dilute comparison and can mislead coverage interpretation. |
| severity | HIGH |
| classification | FUNCTIONAL_DEFECT |
| root cause or NOT_YET_PROVEN | ResearchQualityWorkbench::comparison rows over global source list. |
| root-cause confidence | HIGH |
| owner | R&Q Analysis |
| shared dependency | Source scoping |
| collision risk | HIGH |
| prohibited shortcut | Do not merely sort relevant sources first while retaining unrelated default rows. |
| future validation | Backend/UI test with unrelated sources. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Backend/UI test with unrelated sources. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-01 grouped this with scoping |
| discovery status | MISSED_NEW |

### RQ-FN-040 — Mode state is not URL-backed or navigation-stable

| Field | Value |
|---|---|
| finding_id | RQ-FN-040 |
| category/subcategory | Functional / Scope / state / navigation / workflow |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | mode is a local Vue ref; controller consumes only object/source. |
| governed expected state | Mode must survive refresh, deep-link, Back/Forward and locale navigation through normalized route state. |
| material delta | Observed: mode is a local Vue ref; controller consumes only object/source. Expected: Mode must survive refresh, deep-link, Back/Forward and locale navigation through normalized route state. |
| user consequence | Reviewer returns to a different task than the one shared/bookmarked. |
| severity | HIGH |
| classification | FUNCTIONAL_DEFECT |
| root cause or NOT_YET_PROVEN | ResearchQuality.vue local ref + controller query shape. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend + Controller |
| shared dependency | Route state |
| collision risk | MEDIUM |
| prohibited shortcut | Do not use localStorage as canonical navigation truth. |
| future validation | Refresh/back/forward/deep-link tests for all modes. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Refresh/back/forward/deep-link tests for all modes. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-04 |
| discovery status | KNOWN_AND_ADEQUATE |

### RQ-FN-041 — Selected claim has no route-backed state

| Field | Value |
|---|---|
| finding_id | RQ-FN-041 |
| category/subcategory | Functional / Scope / state / navigation / workflow |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | No claim query parameter, normalized selection, or persistent selected-claim state exists. |
| governed expected state | Selected claim should be shareable/restorable and coordinate CENTER/RIGHT/BOTTOM. |
| material delta | Observed: No claim query parameter, normalized selection, or persistent selected-claim state exists. Expected: Selected claim should be shareable/restorable and coordinate CENTER/RIGHT/BOTTOM. |
| user consequence | Evidence drilldown cannot maintain a stable review target across mode/source navigation. |
| severity | HIGH |
| classification | FUNCTIONAL_DEFECT |
| root cause or NOT_YET_PROVEN | Controller/workspace/frontend only model object/source/mode local. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend + Controller |
| shared dependency | R&Q Analysis |
| collision risk | MEDIUM |
| prohibited shortcut | Do not keep claim selection in an opaque component-only ref. |
| future validation | Deep-link to valid/invalid claim and verify explicit selection state. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Deep-link to valid/invalid claim and verify explicit selection state. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-04 mentioned claim generically |
| discovery status | MISSED_NEW |

### RQ-FN-042 — Selected claim×source relation is not a first-class interaction state

| Field | Value |
|---|---|
| finding_id | RQ-FN-042 |
| category/subcategory | Functional / Scope / state / navigation / workflow |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | There is no relation/cell identity, selection state, or drilldown contract. |
| governed expected state | A matrix cell/relation should drive contextual provenance and deep inspection without inventing persistence. |
| material delta | Observed: There is no relation/cell identity, selection state, or drilldown contract. Expected: A matrix cell/relation should drive contextual provenance and deep inspection without inventing persistence. |
| user consequence | Reviewer cannot inspect one support/exclusion relationship as a task object. |
| severity | HIGH |
| classification | FUNCTIONAL_DEFECT |
| root cause or NOT_YET_PROVEN | No relation type in frontend/backend projection and no route parameter. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend + R&Q Analysis |
| shared dependency | Schema authority for durable semantics |
| collision risk | HIGH |
| prohibited shortcut | Do not invent a persisted relation record to satisfy UI selection. |
| future validation | Select a derived relation cell and verify context can be restored from bounded route state. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Select a derived relation cell and verify context can be restored from bounded route state. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No prior decomposition |
| discovery status | MISSED_NEW |

### RQ-FN-043 — Compare rows are non-interactive dead-end summaries

| Field | Value |
|---|---|
| finding_id | RQ-FN-043 |
| category/subcategory | Functional / Scope / state / navigation / workflow |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | SourceComparisonTable rows contain no Link/button/cell action to inspect source/claim relation. |
| governed expected state | Comparison should permit direct transition to selected evidence context. |
| material delta | Observed: SourceComparisonTable rows contain no Link/button/cell action to inspect source/claim relation. Expected: Comparison should permit direct transition to selected evidence context. |
| user consequence | Users must leave compare mentally and re-find a source in LEFT. |
| severity | MEDIUM |
| classification | FUNCTIONAL_DEFECT |
| root cause or NOT_YET_PROVEN | Static table row template. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend |
| shared dependency | Route selection state |
| collision risk | LOW |
| prohibited shortcut | Do not make whole rows clickable without keyboard/table semantics. |
| future validation | Keyboard/mouse activation from compare to selected context. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Keyboard/mouse activation from compare to selected context. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No direct prior item |
| discovery status | MISSED_NEW |

### RQ-FN-044 — Reconciliation has no governed continuation into authoring

| Field | Value |
|---|---|
| finding_id | RQ-FN-044 |
| category/subcategory | Functional / Scope / state / navigation / workflow |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Conflict mode offers ephemeral textarea/copy only. |
| governed expected state | Transient rationale may continue into the existing Library draft workflow without creating R&Q persistence. |
| material delta | Observed: Conflict mode offers ephemeral textarea/copy only. Expected: Transient rationale may continue into the existing Library draft workflow without creating R&Q persistence. |
| user consequence | Review insight becomes a dead end or manual clipboard workflow. |
| severity | HIGH |
| classification | FUNCTIONAL_DEFECT |
| root cause or NOT_YET_PROVEN | ResearchQualityWorkbench local reconciliationNotes; no Library continuation action. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend + Library Integration Owner |
| shared dependency | Library draft workflow |
| collision risk | HIGH |
| prohibited shortcut | Do not add an R&Q-owned durable decision store or silently fork revisions. |
| future validation | Feature tests for existing draft / published-only / no-revision pathways. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Feature tests for existing draft / published-only / no-revision pathways. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-02 |
| discovery status | KNOWN_AND_ADEQUATE |

### RQ-FX-045 — Acceptance fixture creates one SourceRecord per claim rather than realistic source cardinality

| Field | Value |
|---|---|
| finding_id | RQ-FX-045 |
| category/subcategory | Data Realism / Acceptance fixture representativeness |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | N/A |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | W02AcceptanceSeeder::seedClaim() creates a new SourceRecord for each claim. |
| governed expected state | Visual acceptance data should exercise multiple claims per source and multiple sources per claim where governed. |
| material delta | Observed: W02AcceptanceSeeder::seedClaim() creates a new SourceRecord for each claim. Expected: Visual acceptance data should exercise multiple claims per source and multiple sources per claim where governed. |
| user consequence | Current screenshots make “source” resemble a claim wrapper and cannot honestly test source comparison density. |
| severity | HIGH |
| classification | DATA_FIXTURE_REPRESENTATIVENESS_GAP |
| root cause or NOT_YET_PROVEN | Acceptance seeder structure, not product runtime requirement. |
| root-cause confidence | HIGH |
| owner | W02 Fixture / Acceptance Data Owner |
| shared dependency | SourceGovernance test data |
| collision risk | MEDIUM |
| prohibited shortcut | Do not alter production schema to accommodate a fixture artifact. |
| future validation | Representative fixture manifest with cardinality checks: sources, claims/source, sources/claim. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Representative fixture manifest with cardinality checks: sources, claims/source, sources/claim. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No prior fixture finding |
| discovery status | MISSED_NEW |

### RQ-FX-046 — Acceptance fixture uses one authority class for all seeded sources

| Field | Value |
|---|---|
| finding_id | RQ-FX-046 |
| category/subcategory | Data Realism / Acceptance fixture representativeness |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | N/A |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Seeder hard-codes authority_class = Internal Reviewed Support. |
| governed expected state | Fixture should exercise multiple governed authority types/classes when available. |
| material delta | Observed: Seeder hard-codes authority_class = Internal Reviewed Support. Expected: Fixture should exercise multiple governed authority types/classes when available. |
| user consequence | Authority badges/filter hierarchy cannot be visually or functionally evaluated. |
| severity | MEDIUM |
| classification | DATA_FIXTURE_REPRESENTATIVENESS_GAP |
| root cause or NOT_YET_PROVEN | W02AcceptanceSeeder hard-coded authority_class. |
| root-cause confidence | HIGH |
| owner | W02 Fixture / Acceptance Data Owner |
| shared dependency | Governed authority vocabulary |
| collision risk | LOW |
| prohibited shortcut | Do not invent new authority classes merely for variation. |
| future validation | Fixture validation proves at least 2–3 existing governed authority classes where source data supports them. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Fixture validation proves at least 2–3 existing governed authority classes where source data supports them. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No prior fixture finding |
| discovery status | MISSED_NEW |

### RQ-FX-047 — Acceptance fixture does not exercise realistic external locators

| Field | Value |
|---|---|
| finding_id | RQ-FX-047 |
| category/subcategory | Data Realism / Acceptance fixture representativeness |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | LTR islands |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Seeder sets exact_url = null and relative_path = w02-acceptance/<claim_id>. |
| governed expected state | Fixture should include governed HTTPS and internal-path locator cases to exercise provenance UI safely. |
| material delta | Observed: Seeder sets exact_url = null and relative_path = w02-acceptance/<claim_id>. Expected: Fixture should include governed HTTPS and internal-path locator cases to exercise provenance UI safely. |
| user consequence | URL wrapping, link affordance, provenance hierarchy and Bidi behavior remain untested. |
| severity | MEDIUM |
| classification | DATA_FIXTURE_REPRESENTATIVENESS_GAP |
| root cause or NOT_YET_PROVEN | W02AcceptanceSeeder locator mapping. |
| root-cause confidence | HIGH |
| owner | W02 Fixture / Acceptance Data Owner |
| shared dependency | Provenance UI |
| collision risk | LOW |
| prohibited shortcut | Do not use arbitrary live/public URLs; use safe representative governed or local fixtures. |
| future validation | Browser screenshots for HTTPS locator, internal path, missing locator. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Browser screenshots for HTTPS locator, internal path, missing locator. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No prior fixture finding |
| discovery status | MISSED_NEW |

### RQ-FX-048 — Fixture repeats an engineering disclaimer as excluded semantics

| Field | Value |
|---|---|
| finding_id | RQ-FX-048 |
| category/subcategory | Data Realism / Acceptance fixture representativeness |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Every SourceClaim excluded_semantics is the same acceptance-fixture disclaimer. |
| governed expected state | Fixture should contain realistic scope/exclusion differences derived from governed data. |
| material delta | Observed: Every SourceClaim excluded_semantics is the same acceptance-fixture disclaimer. Expected: Fixture should contain realistic scope/exclusion differences derived from governed data. |
| user consequence | Claim cards overrepresent boilerplate and cannot test meaningful exclusion comparison. |
| severity | MEDIUM |
| classification | DATA_FIXTURE_REPRESENTATIVENESS_GAP |
| root cause or NOT_YET_PROVEN | W02AcceptanceSeeder hard-coded excluded_semantics. |
| root-cause confidence | HIGH |
| owner | W02 Fixture / Acceptance Data Owner |
| shared dependency | Canonical source payload |
| collision risk | LOW |
| prohibited shortcut | Do not invent exclusions that alter semantic truth. |
| future validation | Fixture evidence shows varied governed exclusions or explicit “not available” states. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Fixture evidence shows varied governed exclusions or explicit “not available” states. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No prior fixture finding |
| discovery status | MISSED_NEW |

### RQ-FX-049 — Acceptance fixture does not exercise realistic multi-claim source grouping or relation density within the current schema

| Field | Value |
|---|---|
| finding_id | RQ-FX-049 |
| category/subcategory | Data Realism / Acceptance fixture representativeness |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | N/A |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA `W02AcceptanceSeeder::seedClaim()` creates a new SourceRecord for each claim; exact migration makes `source_claims.claim_id` globally unique. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | The prepared acceptance topology is one source per claim, so sources do not exercise realistic multi-claim grouping, mixed claim density, or source-level inspection depth. Same-claim multi-source support is additionally blocked by the current global-unique claim_id schema and is therefore not a fixture-only defect. |
| governed expected state | Within the current authorized schema, acceptance data should exercise realistic multi-claim sources, varied source density, authority/locator diversity and representable support/scope/exclusion states. Same-claim multi-source variants must remain explicitly schema-gated until RQ-05/SourceGovernance authority resolves cardinality. |
| material delta | The fixture is materially too one-to-one even before the separate schema gate is reached. |
| user consequence | Source grouping, source-level density and comparison geometry can look deceptively simple; forcing same-claim variants into the fixture would also fabricate a persistence capability the runtime does not possess. |
| severity | HIGH |
| classification | DATA_FIXTURE_REPRESENTATIVENESS_GAP |
| root cause or NOT_YET_PROVEN | `W02AcceptanceSeeder::seedClaim()` creates SourceRecord inside the per-claim loop. |
| root-cause confidence | HIGH |
| owner | W02 Fixture / Acceptance Data Owner |
| shared dependency | `RQ-SA-058` / `RQ-SA-059` for any same-claim multi-source persistence beyond the current schema. |
| collision risk | MEDIUM |
| prohibited shortcut | Do not bypass the global unique claim_id constraint, insert impossible duplicate claims, or manufacture conflicts merely for visual variety. |
| future validation | Assert representable topology such as multiple claims per source, varied source sizes/classes/locators and mixed supported/partial/excluded/unresolved rows; if same-claim multi-source remains schema-gated, record that explicitly instead of faking it. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English with multiple source sizes and claim densities that the current schema can honestly represent. |
| closure criterion | Close when the fixture no longer encodes source identity as a one-to-one alias of claim identity, while schema-gated same-claim multi-source behavior remains truthful and separately adjudicated. |
| prior finding mapping | No prior fixture finding; prior RQ-05 supplies the separate schema gate. |
| discovery status | MISSED_NEW |


### RQ-RB-050 — Canonical claim statement is not a first-class R&Q projection field

| Field | Value |
|---|---|
| finding_id | RQ-RB-050 |
| category/subcategory | Runtime Binding / Canonical→runtime→UI projection |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Frontend Claim type has no canonical claim_text; acceptance data happens to copy claim text into supported_scope/title. |
| governed expected state | R&Q should display the canonical claim statement independently from a source’s support scope. |
| material delta | Observed: Frontend Claim type has no canonical claim_text; acceptance data happens to copy claim text into supported_scope/title. Expected: R&Q should display the canonical claim statement independently from a source’s support scope. |
| user consequence | Reviewer sees IDs and scope descriptions without a stable statement of the claim being evaluated. |
| severity | HIGH |
| classification | CANONICAL_RUNTIME_BINDING_GAP |
| root cause or NOT_YET_PROVEN | SourceClaim/runtime projection omits canonical claim body join. |
| root-cause confidence | HIGH |
| owner | Knowledge + SourceGovernance Application |
| shared dependency | Canonical knowledge service |
| collision risk | HIGH |
| prohibited shortcut | Do not treat SourceRecord.title or supported_scope as canonical claim text. |
| future validation | Integration test joins canonical claim text to relation projection without duplicating persistence. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Integration test joins canonical claim text to relation projection without duplicating persistence. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior primary-work-depth finding |
| discovery status | MISSED_NEW |

### RQ-RB-051 — Canonical lineage/source-ref/source-artifact fields do not reach R&Q

| Field | Value |
|---|---|
| finding_id | RQ-RB-051 |
| category/subcategory | Runtime Binding / Canonical→runtime→UI projection |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | LTR islands |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | B09 claim index carries lineage_class, source_ref, source_artifact; R&Q types/projectors omit them. |
| governed expected state | Where governed/current, lineage and source identity context should be inspectable as provenance. |
| material delta | Observed: B09 claim index carries lineage_class, source_ref, source_artifact; R&Q types/projectors omit them. Expected: Where governed/current, lineage and source identity context should be inspectable as provenance. |
| user consequence | The workbench cannot explain how a canonical claim was derived from its source lineage. |
| severity | HIGH |
| classification | CANONICAL_RUNTIME_BINDING_GAP |
| root cause or NOT_YET_PROVEN | Canonical→runtime projection gap; current SourceClaim shape is narrower. |
| root-cause confidence | HIGH |
| owner | Knowledge + SourceGovernance Application |
| shared dependency | Canonical import/binding authority |
| collision risk | HIGH |
| prohibited shortcut | Do not duplicate B09 wholesale into UI or invent runtime persistence. |
| future validation | Trace one governed claim end-to-end from B09 to browser payload with provenance fields. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Trace one governed claim end-to-end from B09 to browser payload with provenance fields. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior provenance depth was generic |
| discovery status | MISSED_NEW |

### RQ-RB-052 — Authority freshness/support-state metadata is retained but not meaningfully projected

| Field | Value |
|---|---|
| finding_id | RQ-RB-052 |
| category/subcategory | Runtime Binding / Canonical→runtime→UI projection |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Acceptance source metadata stores authority_version_freshness and support_state, but frontend Source type exposes generic metadata and UI does not render these fields. |
| governed expected state | Current authority/version/freshness/support context should be shown where governed, with clear scope. |
| material delta | Observed: Acceptance source metadata stores authority_version_freshness and support_state, but frontend Source type exposes generic metadata and UI does not render these fields. Expected: Current authority/version/freshness/support context should be shown where governed, with clear scope. |
| user consequence | Reviewer cannot distinguish stale/current or support-state nuance beyond flattened assessment. |
| severity | HIGH |
| classification | CANONICAL_RUNTIME_BINDING_GAP |
| root cause or NOT_YET_PROVEN | Metadata exists but has no typed analysis/UI projection. |
| root-cause confidence | HIGH |
| owner | SourceGovernance Application + R&Q Frontend |
| shared dependency | Authority vocabulary |
| collision risk | MEDIUM |
| prohibited shortcut | Do not infer freshness from file timestamps or titles. |
| future validation | Typed projection tests using governed metadata values. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Typed projection tests using governed metadata values. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior authority display finding was generic |
| discovery status | MISSED_NEW |

### RQ-RB-053 — Canonical gaps/limitations/coverage context is absent from R&Q workbench

| Field | Value |
|---|---|
| finding_id | RQ-RB-053 |
| category/subcategory | Runtime Binding / Canonical→runtime→UI projection |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | B09 baseline has 1,217 gaps and 1,646 limitation rows, but R&Q payload has no gap/limitation/coverage context. |
| governed expected state | R&Q should expose relevant incompleteness context without becoming a second knowledge editor. |
| material delta | Observed: B09 baseline has 1,217 gaps and 1,646 limitation rows, but R&Q payload has no gap/limitation/coverage context. Expected: R&Q should expose relevant incompleteness context without becoming a second knowledge editor. |
| user consequence | Sparse support can look complete even when canonical knowledge records explicit gaps/limitations. |
| severity | HIGH |
| classification | CANONICAL_RUNTIME_BINDING_GAP |
| root cause or NOT_YET_PROVEN | KnowledgeQualityService/ResearchAnalysis projection has no such fields. |
| root-cause confidence | HIGH |
| owner | Knowledge + SourceGovernance Application |
| shared dependency | Canonical coverage model |
| collision risk | HIGH |
| prohibited shortcut | Do not fabricate coverage percentages or reinterpret gap semantics. |
| future validation | Trace a KU with known gap/limitation from B09 to a bounded UI context. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Trace a KU with known gap/limitation from B09 to a bounded UI context. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No prior decomposition |
| discovery status | MISSED_NEW |

### RQ-RB-054 — Canonical conflict/variant semantics are replaced by heuristic fingerprint conflicts

| Field | Value |
|---|---|
| finding_id | RQ-RB-054 |
| category/subcategory | Runtime Binding / Canonical→runtime→UI projection |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | N/A |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Backend flags a conflict whenever multiple variants differ in supported_scope/excluded_semantics/assessment fingerprints. |
| governed expected state | Conflict/scope/complementarity labels require governed relation semantics; heuristic differences may remain “needs review” only under explicit rule. |
| material delta | Observed: Backend flags a conflict whenever multiple variants differ in supported_scope/excluded_semantics/assessment fingerprints. Expected: Conflict/scope/complementarity labels require governed relation semantics; heuristic differences may remain “needs review” only under explicit rule. |
| user consequence | Ordinary scope wording differences can be overstated as conflict. |
| severity | HIGH |
| classification | CANONICAL_RUNTIME_BINDING_GAP |
| root cause or NOT_YET_PROVEN | ResearchQualityWorkbench::conflicts() ignores B09 record_type/resolution_state/review_trigger. |
| root-cause confidence | HIGH |
| owner | R&Q Analysis + Canonical Binding Owner |
| shared dependency | Relation taxonomy |
| collision risk | HIGH |
| prohibited shortcut | Do not label every difference “conflict” or choose a winner. |
| future validation | Positive/negative semantic test corpus including variants and non-conflicting differences. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Positive/negative semantic test corpus including variants and non-conflicting differences. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-03 |
| discovery status | KNOWN_AND_ADEQUATE |

### RQ-RB-055 — Comparison analysis collapses claim relations into source-level counts

| Field | Value |
|---|---|
| finding_id | RQ-RB-055 |
| category/subcategory | Runtime Binding / Canonical→runtime→UI projection |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | N/A |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | comparisonRow() outputs claim_count, active_revision_claim_count, anchor_count and digest boolean. |
| governed expected state | Analysis should provide claim×source relation rows/cells derived from governed existing fields. |
| material delta | Observed: comparisonRow() outputs claim_count, active_revision_claim_count, anchor_count and digest boolean. Expected: Analysis should provide claim×source relation rows/cells derived from governed existing fields. |
| user consequence | Frontend cannot render the intended workbench even with richer fixture data. |
| severity | HIGH |
| classification | CANONICAL_RUNTIME_BINDING_GAP |
| root cause or NOT_YET_PROVEN | ResearchQualityWorkbench::comparisonRow() aggregation. |
| root-cause confidence | HIGH |
| owner | R&Q Analysis |
| shared dependency | Frontend matrix |
| collision risk | HIGH |
| prohibited shortcut | Do not fabricate relation types unavailable from governed data. |
| future validation | Unit tests assert matrix projection preserves claim_id, source_id, anchor, scope/exclusion, authority. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Unit tests assert matrix projection preserves claim_id, source_id, anchor, scope/exclusion, authority. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-01 captured outcome but not projection loss separately |
| discovery status | MISSED_NEW |

### RQ-RB-056 — Provenance projection contains anchor identifiers but no evidence excerpt/snippet

| Field | Value |
|---|---|
| finding_id | RQ-RB-056 |
| category/subcategory | Runtime Binding / Canonical→runtime→UI projection |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | ProvenanceRow has locator, sha256 and anchors[{claim_id, segment_ref}] only. |
| governed expected state | Primary/deep inspection should expose governed excerpt/snippet or clearly state excerpt unavailable. |
| material delta | Observed: ProvenanceRow has locator, sha256 and anchors[{claim_id, segment_ref}] only. Expected: Primary/deep inspection should expose governed excerpt/snippet or clearly state excerpt unavailable. |
| user consequence | Reviewer cannot evaluate what the anchor actually says without leaving the workbench. |
| severity | HIGH |
| classification | CANONICAL_RUNTIME_BINDING_GAP |
| root cause or NOT_YET_PROVEN | Current runtime types and SourceClaim schema lack excerpt content. |
| root-cause confidence | HIGH |
| owner | SourceGovernance Application |
| shared dependency | Schema/authority for excerpt persistence |
| collision risk | HIGH |
| prohibited shortcut | Do not synthesize quotations from claim text or locator. |
| future validation | End-to-end test with a source that has a governed excerpt payload, or explicit unavailable state if not authorized. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: End-to-end test with a source that has a governed excerpt payload, or explicit unavailable state if not authorized. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Reference Delta source excerpts was generic |
| discovery status | MISSED_NEW |

### RQ-RB-057 — Revision reasoning is an ID/count map rather than pairwise claim-set diff

| Field | Value |
|---|---|
| finding_id | RQ-RB-057 |
| category/subcategory | Runtime Binding / Canonical→runtime→UI projection |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | analysis.revision_reasoning exposes canonical/resolved/unresolved IDs and claim_sources map only. |
| governed expected state | Current, supportable revision mode should compare claim sets pairwise; historical source/anchor diff remains gated. |
| material delta | Observed: analysis.revision_reasoning exposes canonical/resolved/unresolved IDs and claim_sources map only. Expected: Current, supportable revision mode should compare claim sets pairwise; historical source/anchor diff remains gated. |
| user consequence | User cannot see added/removed/unchanged claims or why a revision changed. |
| severity | HIGH |
| classification | CANONICAL_RUNTIME_BINDING_GAP |
| root cause or NOT_YET_PROVEN | ResearchQualityWorkbench::analyze() revision_reasoning shape. |
| root-cause confidence | HIGH |
| owner | R&Q Analysis + Knowledge Revision Owner |
| shared dependency | RQ-05 boundary |
| collision risk | MEDIUM |
| prohibited shortcut | Do not infer historical source support from current rows. |
| future validation | Pairwise revision feature tests with added/removed claims. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Pairwise revision feature tests with added/removed claims. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-03 revision reasoning |
| discovery status | UNDER_SPECIFIED |

### RQ-SA-058 — Current schema cardinality cannot persist multi-source support for one claim or richer durable claim×source relation semantics

| Field | Value |
|---|---|
| finding_id | RQ-SA-058 |
| category/subcategory | Schema Authority / Persistence boundary |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | N/A |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | SourceClaim stores source_record_id, claim_id, segment_ref, supported_scope, excluded_semantics and assessment, while the migration makes claim_id globally unique and cascade-deletes source claims with their source. |
| governed expected state | The runtime cannot truthfully persist more than one source-support row for the same claim under the current unique claim_id constraint; any cardinality, relation-type, excerpt, opposition/complementarity or richer provenance expansion requires owner-approved schema authority. |
| material delta | Observed: SourceClaim stores source_record_id, claim_id, segment_ref, supported_scope, excluded_semantics and assessment, while the migration makes claim_id globally unique and cascade-deletes source claims with their source. Expected: The runtime cannot truthfully persist more than one source-support row for the same claim under the current unique claim_id constraint; any cardinality, relation-type, excerpt, opposition/complementarity or richer provenance expansion requires owner-approved schema authority. |
| user consequence | Same-claim multi-source Compare/Conflict semantics cannot be truthfully persisted from the current DB model, and richer UI requirements cannot safely be satisfied by inventing fields or in-memory-only variants. |
| severity | HIGH |
| classification | SCHEMA_AUTHORITY_LIMITATION |
| root cause or NOT_YET_PROVEN | `source_claims.claim_id UNIQUE`, source-owned cascade deletion and the current SourceClaim field set. |
| root-cause confidence | HIGH |
| owner | SourceGovernance Schema Owner |
| shared dependency | Controller B authority |
| collision risk | HIGH |
| prohibited shortcut | Do not create migrations/schema fields from visual reference implication. |
| future validation | Controller adjudication plus schema contract/tests before any durable model change. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Controller adjudication plus schema contract/tests before any durable model change. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-05 adjacent, not separately decomposed |
| discovery status | MISSED_NEW |

### RQ-SA-059 — Historical revision→source-support provenance is not persistently representable

| Field | Value |
|---|---|
| finding_id | RQ-SA-059 |
| category/subcategory | Schema Authority / Persistence boundary |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | N/A |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Lesson revisions cite claim IDs, while current source support is mutable/source-owned and not revision-bound. |
| governed expected state | Historical source/anchor claims must remain unavailable or explicitly gated until a governed immutable relation exists. |
| material delta | Observed: Lesson revisions cite claim IDs, while current source support is mutable/source-owned and not revision-bound. Expected: Historical source/anchor claims must remain unavailable or explicitly gated until a governed immutable relation exists. |
| user consequence | A historical UI could present false provenance after source/support change or deletion. |
| severity | HIGH |
| classification | SCHEMA_AUTHORITY_LIMITATION |
| root cause or NOT_YET_PROVEN | Current persistence cardinality/deletion/version model. |
| root-cause confidence | HIGH |
| owner | SourceGovernance Schema Owner |
| shared dependency | Knowledge Revision Owner + Controller B |
| collision risk | HIGH |
| prohibited shortcut | Do not synthesize historical source truth from current SourceClaim rows. |
| future validation | Controller-approved persistence design or explicit product-level unavailable state. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Controller-approved persistence design or explicit product-level unavailable state. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | RQ-05 |
| discovery status | AUTHORITY_DECISION_REQUIRED |

### RQ-AD-060 — Durable R&Q reconciliation/decision ownership remains unauthorised

| Field | Value |
|---|---|
| finding_id | RQ-AD-060 |
| category/subcategory | Authority / Owner decision gate |
| surface state | Current exact candidate / discovery audit |
| viewport | All |
| direction | N/A |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | B09 structural/canonical evidence 143XnqYySfgYM04AslzvMxq03gWpBNZpd / archive 1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6; governed PRD/Visual Contract. |
| current evidence | Exact-SHA GitHub code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e; current screenshots 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL/1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V where visually observable. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Current experience explicitly has no persistent owner/decision record; note is ephemeral. |
| governed expected state | Keep transient rationale + Library continuation unless Controller grants separate durable decision authority. |
| material delta | Observed: Current experience explicitly has no persistent owner/decision record; note is ephemeral. Expected: Keep transient rationale + Library continuation unless Controller grants separate durable decision authority. |
| user consequence | Implementing queue/note/decision persistence now would create unauthorized product semantics. |
| severity | HIGH |
| classification | AUTHORITY_DECISION_REQUIRED |
| root cause or NOT_YET_PROVEN | Governance boundary, not a missing frontend field. |
| root-cause confidence | HIGH |
| owner | Controller B / Product Authority |
| shared dependency | Library + SourceGovernance |
| collision risk | HIGH |
| prohibited shortcut | Do not implement RQ-05, reviewer queue, persistent note, accept-support or confirm-conflict store. |
| future validation | Explicit authority decision before any persistence design; otherwise verify no durable writes. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for All. |
| closure criterion | Close only when: Explicit authority decision before any persistence design; otherwise verify no durable writes. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Reference Delta durable decisions / RQ-02 boundary |
| discovery status | AUTHORITY_DECISION_REQUIRED |

### RQ-EI-061 — Hover/focus/disabled visual quality is not evidenced sufficiently

| Field | Value |
|---|---|
| finding_id | RQ-EI-061 |
| category/subcategory | Evidence / Validation evidence gap |
| surface state | Current exact candidate / discovery audit |
| viewport | Interactive states |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Code includes hover/focus-ring/disabled classes, but provided screenshots show resting states only. |
| governed expected state | Material interactive states must be visually checked on exact-SHA browser evidence. |
| material delta | Observed: Code includes hover/focus-ring/disabled classes, but provided screenshots show resting states only. Expected: Material interactive states must be visually checked on exact-SHA browser evidence. |
| user consequence | Cannot close selected/focus/disabled hierarchy or keyboard-visible focus from static captures. |
| severity | MEDIUM |
| classification | EVIDENCE_INSUFFICIENT |
| root cause or NOT_YET_PROVEN | NOT_YET_PROVEN — evidence gap. |
| root-cause confidence | NOT_YET_PROVEN |
| owner | Validation Owner |
| shared dependency | Browser runtime |
| collision risk | LOW |
| prohibited shortcut | Do not mark PASS from class names alone. |
| future validation | Capture keyboard focus, hover, disabled, selected states for modes/source/table/deep inspection. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for Interactive states. |
| closure criterion | Close only when: Capture keyboard focus, hover, disabled, selected states for modes/source/table/deep inspection. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior accessibility qualification only |
| discovery status | MISSED_NEW |

### RQ-EI-062 — Loading/error/empty/fallback compositions are not visually evidenced

| Field | Value |
|---|---|
| finding_id | RQ-EI-062 |
| category/subcategory | Evidence / Validation evidence gap |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 states |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | ResearchQuality.vue contains LOADING/ERROR/EMPTY/fallback banners, but no governed screenshots were provided for those states. |
| governed expected state | Each state must preserve workbench hierarchy, locale direction, and clear recovery semantics. |
| material delta | Observed: ResearchQuality.vue contains LOADING/ERROR/EMPTY/fallback banners, but no governed screenshots were provided for those states. Expected: Each state must preserve workbench hierarchy, locale direction, and clear recovery semantics. |
| user consequence | Cannot determine whether exceptional states collapse layout or create misleading fallback context. |
| severity | MEDIUM |
| classification | EVIDENCE_INSUFFICIENT |
| root cause or NOT_YET_PROVEN | NOT_YET_PROVEN — code exists, browser evidence absent. |
| root-cause confidence | NOT_YET_PROVEN |
| owner | Validation Owner |
| shared dependency | Fixture/runtime state harness |
| collision risk | LOW |
| prohibited shortcut | Do not claim the states are missing; they exist in code. |
| future validation | Exact-SHA captures for LOADING, ERROR/UNAVAILABLE, EMPTY, requested-source-not-found fallback. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024 states. |
| closure criterion | Close only when: Exact-SHA captures for LOADING, ERROR/UNAVAILABLE, EMPTY, requested-source-not-found fallback. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | No prior decomposition |
| discovery status | MISSED_NEW |

### RQ-EI-063 — Full Bidi/accessibility reading order at both required viewports is not evidenced

| Field | Value |
|---|---|
| finding_id | RQ-EI-063 |
| category/subcategory | Evidence / Validation evidence gap |
| surface state | Current exact candidate / discovery audit |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference 16INkI_mgjhbCNig2PUqSvbzOdLEKJ1mQ; use for work-depth/geometry only under higher authorities. |
| current evidence | Current 1440 13ZJD2on5ognURDITO17Dg8nRrr4mT8CL; current ~1024 AR 1eiIsSxdB7ZZILTmeVHydaW4Lb2REcO6V; exact-SHA code at ca36e75c116a9ba00b5d25d358bd68c10990bd6e. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Arabic ~1024 screenshot exists and code uses several bdi islands, but keyboard/AT reading order and full English/Arabic state matrix are not demonstrated. |
| governed expected state | Acceptance requires runtime reading order, logical alignment, token isolation, focus order and no page-level overflow. |
| material delta | Observed: Arabic ~1024 screenshot exists and code uses several bdi islands, but keyboard/AT reading order and full English/Arabic state matrix are not demonstrated. Expected: Acceptance requires runtime reading order, logical alignment, token isolation, focus order and no page-level overflow. |
| user consequence | Static evidence cannot close accessibility or bidi correctness. |
| severity | MEDIUM |
| classification | EVIDENCE_INSUFFICIENT |
| root cause or NOT_YET_PROVEN | NOT_YET_PROVEN — requires browser/accessibility validation. |
| root-cause confidence | NOT_YET_PROVEN |
| owner | Validation Owner |
| shared dependency | Locale runtime + Shared Shell |
| collision risk | LOW |
| prohibited shortcut | Do not infer screen-reader order from DOM snippets or filenames. |
| future validation | Arabic/English 1440/~1024 keyboard and accessibility-tree inspection. |
| required browser state | Authenticated `/knowledge/research-quality` with representative current-object context. |
| required screenshot state | 1440 primary and ~1024 Arabic/English; state-specific capture for 1440/~1024. |
| closure criterion | Close only when: Arabic/English 1440/~1024 keyboard and accessibility-tree inspection. The observed condition no longer reproduces and no prohibited shortcut is used. |
| prior finding mapping | Prior Bidi/accessibility qualification only |
| discovery status | MISSED_NEW |

### RQ-FN-064 — Revision comparison pair is not route-backed or restorable

| Field | Value |
|---|---|
| finding_id | RQ-FN-064 |
| category/subcategory | Functional / Route state / revision comparison |
| surface state | Revision mode / current exact candidate |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | PRD/Visual Contract and prior target interaction contract; revision is a contextual task that must preserve exact pair identity. |
| current evidence | Exact-SHA `KnowledgeLearningController::researchQuality()` and `KnowledgeLearningWorkspace::researchQuality()` consume only object/source; `ResearchQuality.vue` receives no normalized revision-pair task state. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Revision mode has no explicit, normalized base/compare revision pair in route/application state. |
| governed expected state | A pairwise revision comparison must bind both revision identities (or a separately governed deterministic pair policy) to restorable route/task state, with explicit invalid-pair handling. |
| material delta | Observed: Revision mode has no explicit, normalized base/compare revision pair in route/application state. Expected: A pairwise revision comparison must bind both revision identities (or a separately governed deterministic pair policy) to restorable route/task state, with explicit invalid-pair handling. |
| user consequence | Revision review cannot be deep-linked, refreshed, returned to, or audited with confidence that the same two revisions remain selected. |
| severity | HIGH |
| classification | FUNCTIONAL_DEFECT |
| root cause or NOT_YET_PROVEN | R&Q controller/workspace task contract models object/source only; no revision-pair DTO/state exists. |
| root-cause confidence | HIGH |
| owner | R&Q Controller/Application + Knowledge Revision Owner |
| shared dependency | Shared navigation + revision identity |
| collision risk | MEDIUM |
| prohibited shortcut | Do not silently compare “current vs previous” or another implicit pair when the user/task requires explicit revision identity. |
| future validation | Deep-link two valid revisions; refresh; Back/Forward; invalid/missing pair; locale switch; prove identical pair restoration. |
| required browser state | Authenticated `/knowledge/research-quality` in Revision mode with two explicit revisions. |
| required screenshot state | 1440 and ~1024 EN/AR Revision state showing both selected revision identities. |
| closure criterion | Close only when the same governed pair is reconstructible from navigation state and invalid pairs fail explicitly without unrelated fallback. |
| prior finding mapping | RQ-03 + RQ-04 were directionally correct but did not isolate revision-pair route identity. |
| discovery status | UNDER_SPECIFIED |


### RQ-DP-065 — Claims mode is source-centric and lacks claim-level support/completeness/review-needed summary

| Field | Value |
|---|---|
| finding_id | RQ-DP-065 |
| category/subcategory | Visual Design / Claims workbench / information hierarchy |
| surface state | Claims mode / current exact candidate |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | Original R&Q reference plus prior governed target interaction spec: Claims mode is claim-centric and exposes support count/selected relation/review need without truth ranking. |
| current evidence | Current Claims branch iterates `source.claims` inside one selected source and shows claim_id, segment_ref, assessment, supported_scope and excluded_semantics only. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Claims mode organizes work under the active source and does not provide a canonical-claim row summary of support count/completeness, selected source relation, or whether deeper compare/review is needed. |
| governed expected state | Claims mode should let the reviewer scan canonical claims first, see bounded support/completeness context and open the selected source/relation without serially switching sources; any “needs review” signal must be transparently derived, not a truth score. |
| material delta | Observed: Claims mode organizes work under the active source and does not provide a canonical-claim row summary of support count/completeness, selected source relation, or whether deeper compare/review is needed. Expected: Claims mode should let the reviewer scan canonical claims first, see bounded support/completeness context and open the selected source/relation without serially switching sources; any “needs review” signal must be transparently derived, not a truth score. |
| user consequence | The reviewer cannot prioritize under-supported or ambiguous claims and must mentally reconstruct completeness by navigating sources one at a time. |
| severity | HIGH |
| classification | PRODUCT_VISUAL_DESIGN_DEFECT |
| root cause or NOT_YET_PROVEN | `ResearchQualityWorkbench.vue` Claims branch is source-first; backend analysis lacks a claim-summary projection for the active canonical claim set. |
| root-cause confidence | HIGH |
| owner | R&Q Frontend + R&Q Analysis |
| shared dependency | Canonical claim list + scoped source relations |
| collision risk | MEDIUM |
| prohibited shortcut | Do not turn support count into truth ranking, priority score, or invented completeness percentage. |
| future validation | Representative current-object data with multiple claims and different support coverage; verify claim-centric scan, selected relation context and transparent review-needed cues. |
| required browser state | Authenticated `/knowledge/research-quality` in Claims mode with multiple canonical claims and varied support coverage. |
| required screenshot state | 1440 primary plus ~1024 EN/AR Claims state with 6+ claims where the claim-level hierarchy remains scannable. |
| closure criterion | Close only when Claims mode answers “which canonical claims need attention and what supports them?” without serial source hunting or truth ranking. |
| prior finding mapping | Old “primary work depth” / RQ-01 target covered the idea but not this independent Claims-mode closure unit. |
| discovery status | UNDER_SPECIFIED |


### RQ-FN-066 — Conflict rows do not expose an explainable relation basis for why they were flagged

| Field | Value |
|---|---|
| finding_id | RQ-FN-066 |
| category/subcategory | Functional / Conflict explainability / review semantics |
| surface state | Conflicts mode / current exact candidate |
| viewport | All |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | PRD/Visual Contract and prior target interaction spec require conservative, explainable knowledge-quality review semantics. |
| current evidence | Exact-SHA backend emits `status=requires_human_reconciliation` plus variants; UI displays variants and a generic human-judgment warning but no governed reason/relation_basis explaining why the row appears. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | A reviewer sees a conflict/reconciliation candidate but not a first-class, defensible explanation of the rule or relation that caused the flag. |
| governed expected state | Every review candidate must expose a conservative, governed reason such as support-gap or an approved structured relation basis; arbitrary fingerprint difference is not sufficient and the UI must explain what is being compared. |
| material delta | Observed: A reviewer sees a conflict/reconciliation candidate but not a first-class, defensible explanation of the rule or relation that caused the flag. Expected: Every review candidate must expose a conservative, governed reason such as support-gap or an approved structured relation basis; arbitrary fingerprint difference is not sufficient and the UI must explain what is being compared. |
| user consequence | Users cannot distinguish a genuine governed conflict from ordinary scope wording differences, making review prioritization and auditability weak. |
| severity | HIGH |
| classification | FUNCTIONAL_DEFECT |
| root cause or NOT_YET_PROVEN | `ResearchQualityWorkbench::conflicts()` emits no reason/relation basis, and the frontend has no field to present one. |
| root-cause confidence | HIGH |
| owner | R&Q Analysis + R&Q Frontend |
| shared dependency | `RQ-RB-054` canonical relation semantics / schema authority where richer relation classes are needed |
| collision risk | HIGH |
| prohibited shortcut | Do not generate free-text/NLP conflict reasons that imply truth, and do not relabel every difference as a governed conflict. |
| future validation | Positive/negative semantic corpus: support gap, equivalent support, benign scope difference, and only owner-approved structured relation classes; assert visible reason for every surfaced row. |
| required browser state | Authenticated `/knowledge/research-quality` in Conflicts mode with representable review candidates. |
| required screenshot state | 1440 and ~1024 EN/AR Conflicts state with the visible flagging reason for each row. |
| closure criterion | Close only when every surfaced conflict/review row has a testable governed reason and false-positive ordinary differences do not appear as direct conflict. |
| prior finding mapping | RQ-03 identified heuristic conflict semantics but did not isolate user-facing explainability as its own closure unit. |
| discovery status | UNDER_SPECIFIED |


### RQ-EI-067 — Matching exact-candidate visual evidence is missing for Compare, Conflicts, Revision and BOTTOM states

| Field | Value |
|---|---|
| finding_id | RQ-EI-067 |
| category/subcategory | Evidence Sufficiency / State-specific visual proof |
| surface state | Non-primary R&Q modes / exact candidate |
| viewport | 1440/~1024 |
| direction | RTL/LTR |
| authority | PRD 1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV + Visual Contract 1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P + Correction Overlays 1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW- + W02 Operating Model 1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn + Current State 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX |
| reference evidence | PORT-METHOD-032/033 and the R&Q review contract require state-to-state exact-candidate evidence for material visual closure. |
| current evidence | Manifest current screenshots are primary/Claims-oriented 1440 and corrected ~1024 evidence; code exposes Compare/Conflicts/Revision/BOTTOM, but no matching exact-candidate visual capture for each of those states is part of this audit evidence set. |
| exact candidate SHA | ca36e75c116a9ba00b5d25d358bd68c10990bd6e |
| observed state | Mode-specific layout, density, focus and responsive behavior can be reasoned about statically but not visually closed against a matching current screenshot. |
| governed expected state | Material mode-specific visual findings must remain evidence-bounded until fresh exact-SHA state captures exist; static/code evidence may support defect discovery but not visual PASS. |
| material delta | Observed: Mode-specific layout, density, focus and responsive behavior can be reasoned about statically but not visually closed against a matching current screenshot. Expected: Material mode-specific visual findings must remain evidence-bounded until fresh exact-SHA state captures exist; static/code evidence may support defect discovery but not visual PASS. |
| user consequence | Without matching captures, hidden overflow, focus order, long-content wrapping, responsive stacking or visual regressions in non-primary modes could remain unseen. |
| severity | MEDIUM |
| classification | EVIDENCE_INSUFFICIENT |
| root cause or NOT_YET_PROVEN | NOT_YET_PROVEN — this is an evidence gap, not a product root cause. |
| root-cause confidence | NOT_YET_PROVEN |
| owner | Controller B / Independent Assurance |
| shared dependency | Runtime/browser-capable exact-SHA capture lane |
| collision risk | LOW |
| prohibited shortcut | Do not synthesize screenshots, reuse stale/other-SHA captures, or treat code inspection as runtime visual proof. |
| future validation | Capture Compare, Conflicts, Revision and BOTTOM-open states on the exact candidate with representative data at required viewports/locales; bind route/state/SHA/viewport/hash. |
| required browser state | Authenticated exact-candidate R&Q with each non-primary mode explicitly selected and meaningful data loaded. |
| required screenshot state | At minimum 1440 EN plus ~1024 EN/AR for each material mode; add 1440 AR where Controller closure requires it. |
| closure criterion | Close only when state-matched original screenshots/runtime evidence are directly inspectable and bound to the exact candidate. |
| prior finding mapping | Prior R&Q v3 explicitly noted `NOT VERIFIED — MATCHING CURRENT SCREENSHOT MISSING`; later package carried validation need but not a separate evidence finding. |
| discovery status | UNDER_SPECIFIED |


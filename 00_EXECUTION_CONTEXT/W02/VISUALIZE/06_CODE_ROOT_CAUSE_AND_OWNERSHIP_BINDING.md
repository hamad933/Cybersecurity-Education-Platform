# 05_CODE_ROOT_CAUSE_AND_OWNERSHIP_BINDING

**Project:** Cybersecurity Education Platform (`CEP`)  
**Route:** `PERSONAL:CEP`  
**Role:** W02 VISUALIZE — Deep Information-Visualization / Visual-System / Interaction-Design Auditor  
**Audit type:** READ-ONLY / BLIND-FIRST / exact-candidate assurance  
**Repository:** `hamad933/Cybersecurity-Education-Platform`  
**Branch:** `work/cep-w02-library-work-visual-r01`  
**Verified SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`  
**Verified parent:** `7fa8714dc6d0beec6ec77ba8a673140301b066cf`  
**Baseline drift:** `NONE / NO_BASELINE_DRIFT`  
**Product mutation:** `NONE`  
**Writer dispatch:** `NONE`  
**Acceptance / merge / release / deploy:** `NONE`


## 1. Root-cause ledger

| Root ID | Root cause | Exact source binding | Finding IDs | Default owner | Consequence | Prohibited shortcut |
|---|---|---|---|---|---|---|
| `RC-01` | Structural label projection | `CurriculumKnowledgeService::typedGraph(), technicalNode()` | VIS-T-003,T-005 | Shared Curriculum/data contract | Human labels absent/fallback duplication | Do not invent labels. |
| `RC-02` | Tree parent reduction | `viewModels.ts::buildTree()` | VIS-T-006 | Visualize + projection-contract owner | First incoming containment wins silently | Do not arbitrarily choose/duplicate parents. |
| `RC-03` | Path semantics provider gap | `CurriculumKnowledgeService::boundedWorldMemberIds(), typedGraph()` | VIS-P-001,P-002 | Shared Path/Journey/Curriculum owner | Pathway membership exists without typed order | No inferred authored order. |
| `RC-04` | Path staging algorithm | `viewModels.ts::derivePathStages()` | VIS-P-010,P-011 | Visualize local | Cycle → ordinary final stage; lexical peer sort | Do not hide cycles or imply order. |
| `RC-05` | Path presentation | `PathView.vue` | VIS-P-003..P-009,P-012 | Visualize local | Fixed horizontal stage cards; weak connectors; raw IDs | Do not fabricate status/milestones. |
| `RC-06` | Graph focused layout | `viewModels.ts::layoutFocusedGraph()` | VIS-G-004,G-005,G-008,G-009,G-012 | Visualize local | Fixed 960 coordinates; selected edge cannot drive focus | Preserve semantic endpoints/direction. |
| `RC-07` | Graph rendering | `GraphView.vue` | VIS-G-002,G-003,G-006,G-007,G-010,G-011 | Visualize local | No legend; tiny midpoint labels; limited type grammar | Do not rely on color or hide labels. |
| `RC-08` | Canvas grammar | `CanvasView.vue` | VIS-C-001..C-006 | Visualize local | Reuses Graph layout; no pointer pan/edge labels/legend/selected-edge styling | No canonical coordinate/persistence invention. |
| `RC-09` | Map/provider call path | `KnowledgeLearningWorkspace::visualize()` + `VisualizationProjection` | VIS-C-008,VIS-S-003,S-004 | Shared runtime/authority owners | savedMap=null; external overlaySignals=[] | No localStorage or unauthorized providers. |
| `RC-10` | Local-vs-shared workspace ownership | `Visualize.vue` + `CepWorkspaceLayout.vue` | VIS-T-011,G-009,C-009,S-005,S-007,S-008 | Serialized shared shell owner | Nested TOP/LEFT/CENTER/RIGHT | Visualize-local scope must not edit shared shell. |
| `RC-11` | Evidence fixture and browser matrix | DOM/evidence manifests/W02AcceptanceSeeder | VIS-T-001,T-002,T-012,P-013,G-001,G-013,G-014,C-007,C-010,S-001,S-002,S-006,S-009,S-010 | Assurance/test owner | testing/local fixture + no ~1024 all-view matrix | No canonical mutation for screenshots. |
| `RC-PROTECT-01` | Typed route/history | `routeState.ts`, `VisualizeRouteState.php`, `Visualize.vue` | Protected VIS-03 | Visualize | Edge select/Back/Forward/stale prune proven | Do not rewrite without reproduced failure. |
| `RC-PROTECT-02` | Overlay validation | `OverlayProjector.php`, `OverlayPanel.vue` | Protected VIS-04/VIS-06 source | Provider/Visualize | Truthful NO_DATA/NO_AUTHORITY and target validation | Do not fake zero/negative data. |

## 2. High-risk code semantics

### `buildTree()`
The `incoming` set converts the structural graph into a single-parent forest. This is acceptable only if authoritative containment is guaranteed single-parent. Upstream placement logic can represent multiple capability placements, so silent first-parent suppression is unsafe without an explicit projection rule.

### `derivePathStages()`
The function performs a topological sort. Two hidden semantics matter:
- same-rank peers are lexical-ID sorted;
- cyclic leftovers are appended as a normal final stage.

Both can visually overclaim valid order.

### `layoutFocusedGraph()`
The layout:
- picks selected node, else first KU, else first node;
- places inbound at x≈130, focus at x≈480, outbound at x≈830;
- places all remaining nodes in a lower grid;
- uses fixed logical width≈960.

Therefore an **edge** selection cannot influence focus because there is no edge-focused input.

### `CanvasView`
Canvas starts from `layoutFocusedGraph(..., null)`. The mode is spatially draggable, but its initial arrangement is borrowed from Graph. This is the clearest source-level reason to reject the assumption that all four modes already have completely distinct visualization grammars.

### `KnowledgeLearningWorkspace::visualize()`
Current route passes:
- no `savedMap`;
- no external `overlaySignals`.

This is a runtime-binding boundary, not permission to invent missing persistence/providers.

## 3. Ownership principles

- Visualize-local presentation defects: `Visualize.vue` and `components/visualize/**` only after authorization.
- shared shell geometry: shared/integration owner.
- Path canonical-order source: Path/Journey/Curriculum authority.
- structural human labels: canonical data-contract owner.
- Saved Map: Parent/Controller-selected owner only.
- fixture and screenshots: assurance/test owner.
- evidence gap ≠ automatic code change.

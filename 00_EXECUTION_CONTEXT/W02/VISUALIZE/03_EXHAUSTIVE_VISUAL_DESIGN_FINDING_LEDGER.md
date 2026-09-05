# 02_EXHAUSTIVE_VISUAL_DESIGN_FINDING_LEDGER

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


## Ledger summary

- Tree: **12**
- Path: **13**
- Graph: **14**
- Canvas / Map semantic: **10**
- Shared Visualize: **10**
- Total: **59**

Reconciliation:
- `KNOWN`: **16**
- `UNDER_SPECIFIED`: **18**
- `MISSED_NEW`: **25**
- `REGRESSED`: **0**

`REGRESSED=0` لأن الحزمة السابقة والحالية تمت مراجعتهما على الـ exact same candidate SHA؛ العيوب غير المذكورة سابقًا هي missed/under-specified findings، وليست temporal regressions.

---

## VIS-T-001 — Tree — Representative data / Unproven full hierarchy depth

- **finding ID:** `VIS-T-001`
- **view/mode:** Tree
- **category/subcategory:** Representative data / Unproven full hierarchy depth
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **reference evidence:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **current evidence:** Current Tree screenshot 1RBv38HF0y6kWhezktFOPREeVc72X5Ss_ + visualize_dom.html 1kLyx28r5mjljnck02Y3kkxt-F1mx3r9N
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Exact runtime renders one capability `PATH-001` with six KU leaves; no Domain or Capability Cluster layer is exercised.
- **expected intent:** Prove lawful Domain → Capability Cluster → Capability → KU depth wherever authoritative source data provides those levels.
- **material visual delta:** Current state materially diverges from the governed intent: Exact runtime renders one capability `PATH-001` with six KU leaves; no Domain or Capability Cluster layer is exercised.
- **user consequence:** Controller cannot freeze Tree hierarchy fidelity or deep disclosure behavior from a two-level fixture.
- **severity:** `HIGH`
- **classification:** `EVIDENCE_GAP`
- **root-cause binding:** Runtime fixture/data shape; recursive renderer can nest, but the evidenced world is shallow.
- **confidence:** `HIGH`
- **owner:** Assurance/test evidence owner
- **shared dependency:** SD-05 rich deterministic fixture
- **collision:** Possible shared Curriculum fixture ownership
- **prohibited shortcut:** Do not fabricate parents, copy reference labels, or mutate canonical production data solely to create a deeper screenshot.
- **validation requirement:** Capture an authorized 4-level Tree with expanded/collapsed branches, selected node/relation, RIGHT context, 1440 and ~1024; prove every containment edge provenance.
- **screenshot closure state:** `PARTIAL—1440 shallow only; ~1024 missing`
- **prior finding mapping:** Prior T-01 / VIS-01
- **reconciliation:** `KNOWN`


## VIS-T-002 — Tree — Representative data / Test-fixture-only visual evidence

- **finding ID:** `VIS-T-002`
- **view/mode:** Tree
- **category/subcategory:** Representative data / Test-fixture-only visual evidence
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **reference evidence:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **current evidence:** Current Tree screenshot 1RBv38HF0y6kWhezktFOPREeVc72X5Ss_ + visualize_dom.html 1kLyx28r5mjljnck02Y3kkxt-F1mx3r9N
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** DOM is `testing/local`; labels are `Test KU 1..6` and the only structural parent is `PATH-001`, although the selected IDs match the bounded ACCEPTANCE_BALANCED_6 profile.
- **expected intent:** Use a lawful deterministic fixture that is representative enough to judge hierarchy, labels, density and relationships; clearly distinguish fixture proof from canonical runtime proof.
- **material visual delta:** Current state materially diverges from the governed intent: DOM is `testing/local`; labels are `Test KU 1..6` and the only structural parent is `PATH-001`, although the selected IDs match the bounded ACCEPTANCE_BALANCED_6 profile.
- **user consequence:** Sparse synthetic content can hide layout, hierarchy and Bidi defects or exaggerate whitespace.
- **severity:** `HIGH`
- **classification:** `TEST_FIXTURE_ONLY`
- **root-cause binding:** Assurance runtime fixture, not the Tree renderer.
- **confidence:** `HIGH`
- **owner:** Assurance/test harness owner
- **shared dependency:** SD-05
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Manifest fixture identity/source/classification; include multiple structural levels, human labels and relations without implying full canonical import.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior VIS-01 shallow fixture notes
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-T-003 — Tree — Information hierarchy / Structural human-label provenance gap

- **finding ID:** `VIS-T-003`
- **view/mode:** Tree
- **category/subcategory:** Information hierarchy / Structural human-label provenance gap
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **reference evidence:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **current evidence:** Current Tree screenshot 1RBv38HF0y6kWhezktFOPREeVc72X5Ss_ + visualize_dom.html 1kLyx28r5mjljnck02Y3kkxt-F1mx3r9N
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** `domain` and `capability_cluster` nodes are built by `technicalNode()` with display label equal to the technical ID; capability can also fall back to its ID.
- **expected intent:** Use an authorized human title as primary display where one exists; retain stable technical ID separately and LTR-isolated; keep truthful fallback if no title authority exists.
- **material visual delta:** Current state materially diverges from the governed intent: `domain` and `capability_cluster` nodes are built by `technicalNode()` with display label equal to the technical ID; capability can also fall back to its ID.
- **user consequence:** Hierarchy becomes implementation-token centric and hard to scan.
- **severity:** `HIGH`
- **classification:** `PRODUCT_DEFECT + SHARED_DATA_DEPENDENCY`
- **root-cause binding:** `app/Modules/Curriculum/Application/CurriculumKnowledgeService.php::typedGraph(), technicalNode()`.
- **confidence:** `HIGH`
- **owner:** Shared Curriculum/data-contract owner; Visualize consumes only authorized labels
- **shared dependency:** SD-04 structural label authority
- **collision:** Shared provider may affect Library/Learn
- **prohibited shortcut:** Do not invent Arabic/English titles or copy generated-reference labels.
- **validation requirement:** Test canonical-label and fallback cases, provenance, long Arabic/English titles, and separate human label vs technical ID.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior T-02 / VIS-02
- **reconciliation:** `KNOWN`


## VIS-T-004 — Tree — Visual grammar / Domain and cluster visually collapse into one class

- **finding ID:** `VIS-T-004`
- **view/mode:** Tree
- **category/subcategory:** Visual grammar / Domain and cluster visually collapse into one class
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **reference evidence:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **current evidence:** Current Tree screenshot 1RBv38HF0y6kWhezktFOPREeVc72X5Ss_ + visualize_dom.html 1kLyx28r5mjljnck02Y3kkxt-F1mx3r9N
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Tree kind styling distinguishes KU and capability, but `domain` and `capability_cluster` fall into the same neutral visual treatment.
- **expected intent:** Keep each governed hierarchy level perceptible without relying only on indentation or raw kind text; use non-color cues as well.
- **material visual delta:** Current state materially diverges from the governed intent: Tree kind styling distinguishes KU and capability, but `domain` and `capability_cluster` fall into the same neutral visual treatment.
- **user consequence:** Deep trees make domain and cluster boundaries difficult to perceive.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `TreeBranch.vue` node-kind class mapping.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Render a 4-level hierarchy and verify distinct domain/cluster/capability/KU grammar at 1440/~1024 and in grayscale.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** VIS-01 broad hierarchy requirement
- **reconciliation:** `MISSED_NEW`


## VIS-T-005 — Tree — Information hierarchy / Technical fallback duplicates the same token

- **finding ID:** `VIS-T-005`
- **view/mode:** Tree
- **category/subcategory:** Information hierarchy / Technical fallback duplicates the same token
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **reference evidence:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **current evidence:** Current Tree screenshot 1RBv38HF0y6kWhezktFOPREeVc72X5Ss_ + visualize_dom.html 1kLyx28r5mjljnck02Y3kkxt-F1mx3r9N
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** When `label_source=technical_fallback` and `label == technical_label`, Tree renders the same ID twice; current root visibly shows `PATH-001` twice.
- **expected intent:** Show truthful fallback once while preserving technical identity/provenance without redundant duplicate copy.
- **material visual delta:** Current state materially diverges from the governed intent: When `label_source=technical_fallback` and `label == technical_label`, Tree renders the same ID twice; current root visibly shows `PATH-001` twice.
- **user consequence:** Technical IDs dominate hierarchy rows without adding information.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `TreeBranch.vue` always displays both label and technical_label.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Test canonical human label and technical-fallback paths; ensure fallback remains explicit but non-duplicative.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior T-02 / M9
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-T-006 — Tree — Semantic projection / Multi-parent containment is silently reduced to first parent

- **finding ID:** `VIS-T-006`
- **view/mode:** Tree
- **category/subcategory:** Semantic projection / Multi-parent containment is silently reduced to first parent
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **reference evidence:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **current evidence:** Current Tree screenshot 1RBv38HF0y6kWhezktFOPREeVc72X5Ss_ + visualize_dom.html 1kLyx28r5mjljnck02Y3kkxt-F1mx3r9N
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** `buildTree()` ignores any later structural incoming edge after the first `incoming.has(edge.to)` match, while upstream `typedGraph()` can emit multiple lawful capability→KU placements.
- **expected intent:** If multi-placement is legal, Tree must represent every authoritative placement or apply an explicit governed projection rule; it must not silently drop placements based on edge order.
- **material visual delta:** Current state materially diverges from the governed intent: `buildTree()` ignores any later structural incoming edge after the first `incoming.has(edge.to)` match, while upstream `typedGraph()` can emit multiple lawful capability→KU placements.
- **user consequence:** Users can miss valid capability placement/context and infer incomplete canonical structure.
- **severity:** `HIGH`
- **classification:** `PRODUCT_SEMANTIC_DEFECT`
- **root-cause binding:** `viewModels.ts::buildTree()` first-incoming suppression + `CurriculumKnowledgeService::typedGraph()`.
- **confidence:** `HIGH`
- **owner:** Visualize + canonical projection contract owner
- **shared dependency:** Shared Curriculum semantics if multi-placement is legal
- **collision:** Potential projection-policy collision
- **prohibited shortcut:** Do not arbitrarily duplicate canonical identity, choose a parent by incidental edge order, or alter canonical placements.
- **validation requirement:** Controlled fixture: one KU with two authoritative containment parents; prove deterministic truthful presentation and RIGHT context for both.
- **screenshot closure state:** `OPEN—code-proven; not exercised by current fixture`
- **prior finding mapping:** VIS-01 did not decompose multi-parent behavior
- **reconciliation:** `MISSED_NEW`


## VIS-T-007 — Tree — Interaction design / Expand/collapse affordance is detached from row identity

- **finding ID:** `VIS-T-007`
- **view/mode:** Tree
- **category/subcategory:** Interaction design / Expand/collapse affordance is detached from row identity
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **reference evidence:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **current evidence:** Current Tree screenshot 1RBv38HF0y6kWhezktFOPREeVc72X5Ss_ + visualize_dom.html 1kLyx28r5mjljnck02Y3kkxt-F1mx3r9N
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Disclosure is exposed as a separate `N فروع` summary below the parent row instead of a row-bound hierarchy chevron/affordance.
- **expected intent:** Make expand/collapse discoverable as part of the parent row identity while preserving native disclosure accessibility.
- **material visual delta:** Current state materially diverges from the governed intent: Disclosure is exposed as a separate `N فروع` summary below the parent row instead of a row-bound hierarchy chevron/affordance.
- **user consequence:** Users must parse a second line to discover branch affordance, slowing long-tree navigation.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_INTERACTION_DEFECT`
- **root-cause binding:** `TreeBranch.vue` `<details>/<summary>` composition.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not remove keyboard/ARIA disclosure semantics for cosmetic similarity.
- **validation requirement:** Keyboard/pointer expand-collapse across several depths; verify visible affordance, `aria-expanded`, focus order and selected state.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior T-01 only said collapsible levels
- **reconciliation:** `MISSED_NEW`


## VIS-T-008 — Tree — Information density / Repeated containment pills compete with hierarchy

- **finding ID:** `VIS-T-008`
- **view/mode:** Tree
- **category/subcategory:** Information density / Repeated containment pills compete with hierarchy
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **reference evidence:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **current evidence:** Current Tree screenshot 1RBv38HF0y6kWhezktFOPREeVc72X5Ss_ + visualize_dom.html 1kLyx28r5mjljnck02Y3kkxt-F1mx3r9N
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Every child row prominently renders a `contains` relation pill even though containment is already encoded by tree nesting.
- **expected intent:** Keep relationship selection/provenance available but let hierarchy identity remain the dominant visual grammar.
- **material visual delta:** Current state materially diverges from the governed intent: Every child row prominently renders a `contains` relation pill even though containment is already encoded by tree nesting.
- **user consequence:** Rows become noisier and less scannable, especially with technical IDs.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `TreeBranch.vue` relation-button ordering/presentation.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Stress a 20+ row tree; relation remains selectable/accessible without dominating human labels.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior Tree density concerns only
- **reconciliation:** `MISSED_NEW`


## VIS-T-009 — Tree — Information density / Secondary metadata grammar is too thin for a workbench

- **finding ID:** `VIS-T-009`
- **view/mode:** Tree
- **category/subcategory:** Information density / Secondary metadata grammar is too thin for a workbench
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **reference evidence:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **current evidence:** Current Tree screenshot 1RBv38HF0y6kWhezktFOPREeVc72X5Ss_ + visualize_dom.html 1kLyx28r5mjljnck02Y3kkxt-F1mx3r9N
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Rows expose label, technical ID, kind cue and relation pill but lack a compact grammar for lawful secondary context that could use the wide CENTER surface.
- **expected intent:** Use available authoritative metadata selectively to support expert scanning; never clone reference counts/statuses that have no provider.
- **material visual delta:** Current state materially diverges from the governed intent: Rows expose label, technical ID, kind cue and relation pill but lack a compact grammar for lawful secondary context that could use the wide CENTER surface.
- **user consequence:** Current Tree reads more like a card list than a dense structural workbench.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_DESIGN_GAP`
- **root-cause binding:** Tree row presentation plus provider availability.
- **confidence:** `HIGH`
- **owner:** Visualize for presentation; shared provider only if new metadata is required
- **shared dependency:** Provider authority only when adding new fields
- **collision:** Low
- **prohibited shortcut:** Do not fabricate status, mastery, coverage or counts to imitate the reference.
- **validation requirement:** Representative hierarchy with source-backed metadata only; verify no duplication and readable density at 1440/~1024.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior T-03 allowed intentional deviation on exact values
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-T-010 — Tree — State semantics / Active, selected and focused states are not coherently related

- **finding ID:** `VIS-T-010`
- **view/mode:** Tree
- **category/subcategory:** State semantics / Active, selected and focused states are not coherently related
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **reference evidence:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **current evidence:** Current Tree screenshot 1RBv38HF0y6kWhezktFOPREeVc72X5Ss_ + visualize_dom.html 1kLyx28r5mjljnck02Y3kkxt-F1mx3r9N
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Active KU is highlighted in LEFT/header while RIGHT stays neutral until an explicit node/edge selection; Tree rows do not expose a distinct current/focused state.
- **expected intent:** Differentiate current scope, focus and selection, and define useful neutral RIGHT behavior without duplicating identity/actions.
- **material visual delta:** Current state materially diverges from the governed intent: Active KU is highlighted in LEFT/header while RIGHT stays neutral until an explicit node/edge selection; Tree rows do not expose a distinct current/focused state.
- **user consequence:** Users can see an active object yet believe context is empty or unrelated.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_INTERACTION_DEFECT`
- **root-cause binding:** `Visualize.vue` active object vs `routeState.selection`; RIGHT is selection-only.
- **confidence:** `HIGH`
- **owner:** Visualize + shared context owner
- **shared dependency:** ARCH-SHARED-01
- **collision:** Visualize.vue collides with shared shell migration
- **prohibited shortcut:** Do not simply duplicate header/LEFT identity into RIGHT.
- **validation requirement:** Active-only, selected-node, selected-edge, cleared selection, view-switch and ~1024 drawer states; one information item has one authoritative location.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior M5 declared selected RIGHT satisfied but did not decompose active/current semantics
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-T-011 — Tree — Workspace geometry / CENTER under-utilization is partly structural, not only sparse data

- **finding ID:** `VIS-T-011`
- **view/mode:** Tree
- **category/subcategory:** Workspace geometry / CENTER under-utilization is partly structural, not only sparse data
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **reference evidence:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **current evidence:** Current Tree screenshot 1RBv38HF0y6kWhezktFOPREeVc72X5Ss_ + visualize_dom.html 1kLyx28r5mjljnck02Y3kkxt-F1mx3r9N
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** The Tree is boxed inside a nested panel with large unused side/lower area; sparse fixture contributes, but nested region ownership and row composition amplify the dead zone.
- **expected intent:** CENTER should remain the dominant visualization surface and expand naturally with representative hierarchy depth/density.
- **material visual delta:** Current state materially diverges from the governed intent: The Tree is boxed inside a nested panel with large unused side/lower area; sparse fixture contributes, but nested region ownership and row composition amplify the dead zone.
- **user consequence:** Desktop feels like a small form inside a larger workspace and shows fewer useful structural cues than the reference.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_DESIGN_DEFECT + DATA_SHAPE_FACTOR`
- **root-cause binding:** `Visualize.vue` nested workspace + Tree composition + sparse fixture.
- **confidence:** `HIGH`
- **owner:** Visualize + shared shell owner
- **shared dependency:** ARCH-SHARED-01 + SD-05
- **collision:** High with shared slot migration
- **prohibited shortcut:** Do not fill whitespace with fabricated nodes or decorative fake metrics.
- **validation requirement:** Re-evaluate center share/visible rows with representative Tree before and after shared-shell convergence.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior center-use/M4 partial
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-T-012 — Tree — Responsive/scale / Long-tree and ~1024 behavior are unproven

- **finding ID:** `VIS-T-012`
- **view/mode:** Tree
- **category/subcategory:** Responsive/scale / Long-tree and ~1024 behavior are unproven
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **reference evidence:** Tree owner-final corrected v2 (Drive 1ltKZYzU5Ho2025W8rHo2We1oOWm9JUyG) + PRD/Visual Contract/Correction Overlay
- **current evidence:** Current Tree screenshot 1RBv38HF0y6kWhezktFOPREeVc72X5Ss_ + visualize_dom.html 1kLyx28r5mjljnck02Y3kkxt-F1mx3r9N
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Current Tree has only 7 nodes and the evidence folder contains no `screenshot_1024_Visualize*`.
- **expected intent:** Prove long-tree scrolling, disclosure persistence, context access and no document-level horizontal overflow at 1440 and ~1024.
- **material visual delta:** Current state materially diverges from the governed intent: Current Tree has only 7 nodes and the evidence folder contains no `screenshot_1024_Visualize*`.
- **user consequence:** Clipping, overflow and focus defects remain possible at the mandated secondary viewport.
- **severity:** `HIGH`
- **classification:** `EVIDENCE_GAP`
- **root-cause binding:** Evidence gap; responsive source breakpoints are not runtime visual proof.
- **confidence:** `HIGH`
- **owner:** Assurance/evidence owner
- **shared dependency:** SD-05 + SD-06
- **collision:** Shared shell integration changes geometry
- **prohibited shortcut:** Do not infer visual closure from CSS or unrelated ~1024 screenshots.
- **validation requirement:** Long nested Tree at both viewports with keyboard focus, RIGHT context access and scroll-boundary evidence.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior R-01 / VIS-01
- **reconciliation:** `KNOWN`


## VIS-P-001 — Path — Semantic model / Canonical authored path semantics are not available

- **finding ID:** `VIS-P-001`
- **view/mode:** Path
- **category/subcategory:** Semantic model / Canonical authored path semantics are not available
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Current mode is prerequisite-derived topological progression only.
- **expected intent:** Either bind an authorized canonical Path/Journey order/current-step source or explicitly retain a derived prerequisite fallback that never claims authored path order.
- **material visual delta:** Current state materially diverges from the governed intent: Current mode is prerequisite-derived topological progression only.
- **user consequence:** Users can mistake dependency order for curriculum journey order.
- **severity:** `HIGH`
- **classification:** `AUTHORITY_DEPENDENCY + PRODUCT_SEMANTIC_GAP`
- **root-cause binding:** `CurriculumKnowledgeService::typedGraph()` emits no canonical pathway-order relation.
- **confidence:** `HIGH`
- **owner:** Controller + shared Path/Journey/Curriculum owner
- **shared dependency:** SD-03 canonical Path authority
- **collision:** Shared provider may intersect Learn/Journey work
- **prohibited shortcut:** Do not infer order from pathway ID, catalog order, x-position or prerequisite direction and label it canonical.
- **validation requirement:** Controller adjudicates Path source; capture either canonical mode or clearly derived fallback at 1440/~1024.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior P-01 / VIS-01
- **reconciliation:** `KNOWN`


## VIS-P-002 — Path — Runtime binding / Pathway metadata is membership-only and never reaches Path as typed order

- **finding ID:** `VIS-P-002`
- **view/mode:** Path
- **category/subcategory:** Runtime binding / Pathway metadata is membership-only and never reaches Path as typed order
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** `pathway.id` can widen the bounded world, but `typedGraph()` emits containment and prerequisite edges only; PathView never receives pathway order/branch relations.
- **expected intent:** Project authorized pathway/order semantics as distinct typed relations with provenance, separately from prerequisites.
- **material visual delta:** Current state materially diverges from the governed intent: `pathway.id` can widen the bounded world, but `typedGraph()` emits containment and prerequisite edges only; PathView never receives pathway order/branch relations.
- **user consequence:** Renderer support for `pathway` is theoretical; runtime cannot demonstrate authored Path behavior.
- **severity:** `HIGH`
- **classification:** `RUNTIME_BINDING_GAP`
- **root-cause binding:** `CurriculumKnowledgeService::boundedWorldMemberIds(), typedGraph()` + `viewModels.ts::edgeSupportsView()`.
- **confidence:** `HIGH`
- **owner:** Shared Path/Curriculum provider owner
- **shared dependency:** SD-03
- **collision:** Shared backend/provider seam
- **prohibited shortcut:** Do not synthesize pathway edges from membership alone.
- **validation requirement:** Provider-contract test with explicit order/branch provenance; Path visibly separates pathway relation from prerequisite.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior P-01 root cause
- **reconciliation:** `KNOWN`


## VIS-P-003 — Path — Workspace geometry / Stage strip leaves most CENTER unused

- **finding ID:** `VIS-P-003`
- **view/mode:** Path
- **category/subcategory:** Workspace geometry / Stage strip leaves most CENTER unused
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** A compact horizontal strip occupies the top of the 560px+ work area while most of CENTER remains empty.
- **expected intent:** Use width/height intentionally to clarify stages, branches and current context; local horizontal scrolling only when necessary.
- **material visual delta:** Current state materially diverges from the governed intent: A compact horizontal strip occupies the top of the 560px+ work area while most of CENTER remains empty.
- **user consequence:** Path reads as a small carousel-like widget instead of a journey workspace.
- **severity:** `MEDIUM-HIGH`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `PathView.vue` fixed stage-grid composition plus sparse fixture.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** ARCH-SHARED-01 + SD-05
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Representative branching Path at 1440/~1024; measure visible stages and local-vs-document overflow.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior P-03
- **reconciliation:** `KNOWN`


## VIS-P-004 — Path — Visual grammar / Connector grammar does not connect actual nodes

- **finding ID:** `VIS-P-004`
- **view/mode:** Path
- **category/subcategory:** Visual grammar / Connector grammar does not connect actual nodes
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Stage transition is a short generic header stub; lines do not connect specific cards or represent exact edge geometry.
- **expected intent:** Visual connectors should map authoritative sequence/dependency edges at node/group level and support fork/join comprehension.
- **material visual delta:** Current state materially diverges from the governed intent: Stage transition is a short generic header stub; lines do not connect specific cards or represent exact edge geometry.
- **user consequence:** Users must decode raw relation text to understand which node leads to which.
- **severity:** `HIGH`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `PathView.vue` stage-header connector composition.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not draw authored connectors for relations that are not authoritative.
- **validation requirement:** Branch/join fixture with multiple incoming/outgoing prerequisites; every drawn connector maps to an exact edge.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior P-03 only broadly mentioned branch/relationship grammar
- **reconciliation:** `MISSED_NEW`


## VIS-P-005 — Path — Visual grammar / Branch/fork state is only implicit same-stage grouping

- **finding ID:** `VIS-P-005`
- **view/mode:** Path
- **category/subcategory:** Visual grammar / Branch/fork state is only implicit same-stage grouping
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Parallel nodes are simply placed in the same stage column; there is no explicit fork/join grammar.
- **expected intent:** When source edges prove non-linear structure, distinguish forks/joins from merely concurrent same-rank nodes.
- **material visual delta:** Current state materially diverges from the governed intent: Parallel nodes are simply placed in the same stage column; there is no explicit fork/join grammar.
- **user consequence:** Parallel cards can be mistaken for alternatives, duplicates or unrelated items.
- **severity:** `HIGH`
- **classification:** `PRODUCT_DESIGN_DEFECT + EVIDENCE_GAP`
- **root-cause binding:** `PathView.vue` stage grouping without routed branch geometry.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** SD-05; SD-03 if canonical Path
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Controlled fork + join fixture; verify branch edges, selection and RIGHT context.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior P-03 broad branch requirement
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-P-006 — Path — State semantics / No current/previous/next progression distinction

- **finding ID:** `VIS-P-006`
- **view/mode:** Path
- **category/subcategory:** State semantics / No current/previous/next progression distinction
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Stages are visually generic; no current, previous or next state exists beyond selection.
- **expected intent:** When an authorized current-step source exists, current/previous/next must be distinct; without authority, explicitly avoid implying such state.
- **material visual delta:** Current state materially diverges from the governed intent: Stages are visually generic; no current, previous or next state exists beyond selection.
- **user consequence:** Users cannot orient within an actual journey and may confuse selection with progress.
- **severity:** `MEDIUM-HIGH`
- **classification:** `PRODUCT_DESIGN_GAP + AUTHORITY_DEPENDENT`
- **root-cause binding:** Path stage model/UI has no current-stage channel and no authorized progress provider.
- **confidence:** `HIGH`
- **owner:** Visualize after progress/path authority
- **shared dependency:** SD-03 + future progress authority
- **collision:** Low
- **prohibited shortcut:** Do not infer current step from active KU or visual order.
- **validation requirement:** Test no-current-authority state plus authorized current-step state if provider exists; selection remains a separate visual channel.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior P-01/P-02 broad current-path grammar
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-P-007 — Path — Interaction state / Selection and focus are too close to potential progress semantics

- **finding ID:** `VIS-P-007`
- **view/mode:** Path
- **category/subcategory:** Interaction state / Selection and focus are too close to potential progress semantics
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Selected card is mainly a subtle border change; no distinct focused-stage grammar exists.
- **expected intent:** Keep interaction selection/focus visually distinct from semantic progress/current state.
- **material visual delta:** Current state materially diverges from the governed intent: Selected card is mainly a subtle border change; no distinct focused-stage grammar exists.
- **user consequence:** Inspection can be mistaken for learning progress once richer data is present.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_INTERACTION_DEFECT`
- **root-cause binding:** `PathView.vue` selection classes.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Keyboard focus, selected node, current step (if authorized) and active scope combinations with non-color cues.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior state-grammar broad findings
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-P-008 — Path — Information hierarchy / Prerequisite labels are raw technical endpoint IDs

- **finding ID:** `VIS-P-008`
- **view/mode:** Path
- **category/subcategory:** Information hierarchy / Prerequisite labels are raw technical endpoint IDs
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Incoming prerequisite buttons show tiny raw tokens like `ku:... → ku:...` rather than human from/type/to labels.
- **expected intent:** Make human labels and relation semantics primary; retain stable IDs as secondary LTR metadata.
- **material visual delta:** Current state materially diverges from the governed intent: Incoming prerequisite buttons show tiny raw tokens like `ku:... → ku:...` rather than human from/type/to labels.
- **user consequence:** Users must decode IDs to understand prerequisite meaning.
- **severity:** `HIGH`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `PathView.vue` incoming relation presentation.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not remove stable relation identity or reverse arrow semantics under RTL.
- **validation requirement:** Mixed Arabic/English node labels; relation button accessible name includes human from/type/to and stable ID remains available.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-P-009 — Path — Visual grammar / Path model cannot represent milestone/stage roles

- **finding ID:** `VIS-P-009`
- **view/mode:** Path
- **category/subcategory:** Visual grammar / Path model cannot represent milestone/stage roles
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Every topological rank is a generic `STAGE n`; `PathStage` only contains index/nodes/incoming.
- **expected intent:** Support authorized milestone/checkpoint/terminal roles when such metadata exists; otherwise clearly remain a generic derived stage.
- **material visual delta:** Current state materially diverges from the governed intent: Every topological rank is a generic `STAGE n`; `PathStage` only contains index/nodes/incoming.
- **user consequence:** Long journeys lack landmarks and terminal/decision states cannot be expressed.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_DESIGN_GAP`
- **root-cause binding:** `viewModels.ts::PathStage` + `PathView.vue`.
- **confidence:** `HIGH`
- **owner:** Path authority/provider + Visualize
- **shared dependency:** SD-03
- **collision:** Low
- **prohibited shortcut:** Do not invent milestone labels from the reference.
- **validation requirement:** Contract state with and without authorized stage-role metadata; generic derived mode stays truthful.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-P-010 — Path — Algorithmic semantics / Cyclic prerequisites are normalized into an ordinary final stage

- **finding ID:** `VIS-P-010`
- **view/mode:** Path
- **category/subcategory:** Algorithmic semantics / Cyclic prerequisites are normalized into an ordinary final stage
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** `derivePathStages()` appends all unvisited cyclic nodes as the final stage with no cycle flag, warning or separate diagnostic state.
- **expected intent:** Surface dependency cycles explicitly; do not make a circular prerequisite set look like a valid terminal progression stage.
- **material visual delta:** Current state materially diverges from the governed intent: `derivePathStages()` appends all unvisited cyclic nodes as the final stage with no cycle flag, warning or separate diagnostic state.
- **user consequence:** Users can follow an impossible dependency loop as if it were legitimate sequence.
- **severity:** `HIGH`
- **classification:** `PRODUCT_SEMANTIC_DEFECT`
- **root-cause binding:** `viewModels.ts::derivePathStages()` cyclic append.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not silently break/delete cycle edges or arbitrarily order them.
- **validation requirement:** 2-node and 3-node cycle fixtures; cycle is announced, exact edges retained, normal-stage appearance avoided.
- **screenshot closure state:** `OPEN—code-proven; no runtime cycle evidence`
- **prior finding mapping:** Prior validation mentioned cycle case but no atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-P-011 — Path — Ordering semantics / Lexical ID tie-break can imply false order

- **finding ID:** `VIS-P-011`
- **view/mode:** Path
- **category/subcategory:** Ordering semantics / Lexical ID tie-break can imply false order
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Parallel frontier nodes are sorted lexically by ID before rendering.
- **expected intent:** Parallel/no-authored-order nodes should be visually grouped or marked unordered so deterministic rendering does not imply curriculum priority.
- **material visual delta:** Current state materially diverges from the governed intent: Parallel frontier nodes are sorted lexically by ID before rendering.
- **user consequence:** Users can infer 'first/second' priority among topologically equivalent peers.
- **severity:** `MEDIUM-HIGH`
- **classification:** `PRODUCT_SEMANTIC_DEFECT`
- **root-cause binding:** `viewModels.ts::derivePathStages()` `frontier.sort()`.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not replace lexical sort with another arbitrary order and imply semantics.
- **validation requirement:** Two or more same-rank peers; UI communicates parallel/unordered relation independent of technical ID sort.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior P-01 noted authority but not tie-break artifact
- **reconciliation:** `MISSED_NEW`


## VIS-P-012 — Path — Responsive / ~1024 overflow and context behavior are unproven

- **finding ID:** `VIS-P-012`
- **view/mode:** Path
- **category/subcategory:** Responsive / ~1024 overflow and context behavior are unproven
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Source uses a bounded local horizontal scroller, but no exact-current ~1024 Visualize screenshot exists.
- **expected intent:** Prove local overflow only, no page-level horizontal overflow, discoverable TOP controls and usable context drawer.
- **material visual delta:** Current state materially diverges from the governed intent: Source uses a bounded local horizontal scroller, but no exact-current ~1024 Visualize screenshot exists.
- **user consequence:** Stages or RIGHT context may be inaccessible at the mandatory medium viewport.
- **severity:** `HIGH`
- **classification:** `EVIDENCE_GAP`
- **root-cause binding:** Evidence absence; CSS breakpoints alone are insufficient.
- **confidence:** `HIGH`
- **owner:** Assurance/evidence owner
- **shared dependency:** SD-06 / ARCH-SHARED-01
- **collision:** Shared shell changes final geometry
- **prohibited shortcut:** Do not infer responsive acceptance from source or unrelated RQ screenshots.
- **validation requirement:** Path ~1024 with >4 stages, keyboard local scroller, selected relation/node and context drawer.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior R-01 / P-03
- **reconciliation:** `KNOWN`


## VIS-P-013 — Path — Representative data / Representative data does not exercise branch/merge/cycle/no-path states

- **finding ID:** `VIS-P-013`
- **view/mode:** Path
- **category/subcategory:** Representative data / Representative data does not exercise branch/merge/cycle/no-path states
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **reference evidence:** Path supporting component reference (Drive 1ceCA9VjB9irbdFcCGJ4OxMX2_xMAL2Id) + PRD MAP/VIEW/OVERLAY contract
- **current evidence:** Current Path screenshot 1V2dIgz-3wg96jM_EKqOeFExCr4vC7s9Z + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Current prerequisite fixture is a simple six-KU progression; no authoritative branch, merge, cycle, empty-path or mixed relation case is evidenced.
- **expected intent:** Use legal deterministic fixtures that exercise the full Path grammar without mutating production truth.
- **material visual delta:** Current state materially diverges from the governed intent: Current prerequisite fixture is a simple six-KU progression; no authoritative branch, merge, cycle, empty-path or mixed relation case is evidenced.
- **user consequence:** Several real Path defects can remain latent behind a simple chain.
- **severity:** `HIGH`
- **classification:** `EVIDENCE_GAP / TEST_FIXTURE_ONLY`
- **root-cause binding:** Evidence fixture coverage.
- **confidence:** `HIGH`
- **owner:** Assurance/test harness owner
- **shared dependency:** SD-05
- **collision:** Low
- **prohibited shortcut:** Do not manufacture product data solely for cosmetic density.
- **validation requirement:** Branch, merge, no prerequisite, cycle, long Path and mixed-language states at 1440/~1024.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior rich-fixture/cycle evidence dependency
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-G-001 — Graph — Evidence / Node-focused reference state is not visually closed

- **finding ID:** `VIS-G-001`
- **view/mode:** Graph
- **category/subcategory:** Evidence / Node-focused reference state is not visually closed
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Code implements selected-node focus, but exact-current evidence only shows edge-selected/unselected Graph states.
- **expected intent:** Capture a selected node with meaningful inbound/outbound neighborhood and synchronized RIGHT context.
- **material visual delta:** Current state materially diverges from the governed intent: Code implements selected-node focus, but exact-current evidence only shows edge-selected/unselected Graph states.
- **user consequence:** Controller cannot verify the primary focused-graph interaction against its supporting reference.
- **severity:** `HIGH`
- **classification:** `EVIDENCE_GAP`
- **root-cause binding:** Evidence state gap; no source defect assumed.
- **confidence:** `HIGH`
- **owner:** Assurance/evidence owner
- **shared dependency:** SD-05
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Pointer + keyboard node selection at 1440/~1024; focus node central, neighbors legible, RIGHT synchronized.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior G-01 / VIS-01
- **reconciliation:** `KNOWN`


## VIS-G-002 — Graph — Visual grammar / No legend for typed edge/node encodings

- **finding ID:** `VIS-G-002`
- **view/mode:** Graph
- **category/subcategory:** Visual grammar / No legend for typed edge/node encodings
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Containment/prerequisite differ by color/dash and nodes use type styling, but no legend explains the encoding.
- **expected intent:** Provide a persistent/discoverable semantic key; color remains supplementary to labels/shapes/ARIA.
- **material visual delta:** Current state materially diverges from the governed intent: Containment/prerequisite differ by color/dash and nodes use type styling, but no legend explains the encoding.
- **user consequence:** Users must infer relation types from tiny labels or guess visual encoding.
- **severity:** `HIGH`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `GraphView.vue` edge/node encodings with no legend component.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not invent unsupported relation types or rely on color alone.
- **validation requirement:** Legend with every authorized relation type, non-color cues, screen-reader labels and ~1024 fit.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-G-003 — Graph — Legibility / Edge labels are too small at default fit

- **finding ID:** `VIS-G-003`
- **view/mode:** Graph
- **category/subcategory:** Legibility / Edge labels are too small at default fit
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Edge labels exist but render at tiny scale and are difficult to read in the 1440 screenshot amid crossings.
- **expected intent:** Keep relation type readable at working fit/zoom through typography, adaptive disclosure or selection emphasis.
- **material visual delta:** Current state materially diverges from the governed intent: Edge labels exist but render at tiny scale and are difficult to read in the 1440 screenshot amid crossings.
- **user consequence:** Users see lines before readable relationship semantics; low-vision impact is material.
- **severity:** `HIGH`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `GraphView.vue` small midpoint label styling + fixed layout scale.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Readability at default fit plus zoom range and ~1024; labels remain legible without occluding nodes.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-G-004 — Graph — Topology layout / Static topology has no collision-aware routing

- **finding ID:** `VIS-G-004`
- **view/mode:** Graph
- **category/subcategory:** Topology layout / Static topology has no collision-aware routing
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Fixed inbound/focus/outbound coordinates with straight edges create visible crossings; no routing optimization exists.
- **expected intent:** Use deterministic collision-aware focused layout/routing suitable for representative bounded neighborhoods.
- **material visual delta:** Current state materially diverges from the governed intent: Fixed inbound/focus/outbound coordinates with straight edges create visible crossings; no routing optimization exists.
- **user consequence:** Users can associate crossing edges with the wrong endpoint as density grows.
- **severity:** `HIGH`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `viewModels.ts::layoutFocusedGraph()` + straight SVG paths.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Stress 7/15/30-node worlds with multiple inbound/outbound; record node/label overlap and edge crossings while preserving semantic direction.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-G-005 — Graph — Interaction semantics / Selecting an edge does not make its endpoints the layout focus

- **finding ID:** `VIS-G-005`
- **view/mode:** Graph
- **category/subcategory:** Interaction semantics / Selecting an edge does not make its endpoints the layout focus
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Graph layout receives only `selectedNodeId`; when selection is an edge, focus falls back to the first KU instead of the selected relation/endpoints.
- **expected intent:** Edge selection should establish a relation-centered inspection neighborhood without silently converting selection into a node.
- **material visual delta:** Current state materially diverges from the governed intent: Graph layout receives only `selectedNodeId`; when selection is an edge, focus falls back to the first KU instead of the selected relation/endpoints.
- **user consequence:** RIGHT can describe one relation while CENTER remains spatially centered on an unrelated fallback node.
- **severity:** `HIGH`
- **classification:** `PRODUCT_INTERACTION_DEFECT`
- **root-cause binding:** `GraphView.vue` + `layoutFocusedGraph(selectedNodeId)`; edge selection has no focus input.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not secretly replace the user's edge selection with a node selection.
- **validation requirement:** Select an edge far from the fallback KU in a larger graph; endpoints remain visible/dominant and Back/Forward restore relation-centered state.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior G-01 focused only node selection
- **reconciliation:** `MISSED_NEW`


## VIS-G-006 — Graph — Selection state / Selected edge dominance is weak in dense topology

- **finding ID:** `VIS-G-006`
- **view/mode:** Graph
- **category/subcategory:** Selection state / Selected edge dominance is weak in dense topology
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Selected edge mainly gains stroke emphasis while crossing edges and labels remain visually competitive.
- **expected intent:** Make selected relationship and endpoint association obvious with non-color cues while retaining surrounding context.
- **material visual delta:** Current state materially diverges from the governed intent: Selected edge mainly gains stroke emphasis while crossing edges and labels remain visually competitive.
- **user consequence:** Users can lose track of which line the RIGHT panel describes.
- **severity:** `MEDIUM-HIGH`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `GraphView.vue` selected-edge styling + static crossing topology.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Selected/unselected comparison across increasing density and keyboard edge selection.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior G-01/M5 selection context broad
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-G-007 — Graph — Visual grammar / Structural node kinds are visually flattened

- **finding ID:** `VIS-G-007`
- **view/mode:** Graph
- **category/subcategory:** Visual grammar / Structural node kinds are visually flattened
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Knowledge units have a distinct violet treatment; domain/cluster/capability share a common neutral structural style.
- **expected intent:** Different structural roles should be perceivable through governed shape/icon/border grammar, not tiny kind text alone.
- **material visual delta:** Current state materially diverges from the governed intent: Knowledge units have a distinct violet treatment; domain/cluster/capability share a common neutral structural style.
- **user consequence:** Users cannot perceive structural boundaries or clusters at a glance.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `GraphView.vue` node class mapping.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Representative domain/cluster/capability/KU graph with non-color distinction and mixed-language labels.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-G-008 — Graph — Topology comprehension / Neighbor grouping is geometric but semantically unlabeled

- **finding ID:** `VIS-G-008`
- **view/mode:** Graph
- **category/subcategory:** Topology comprehension / Neighbor grouping is geometric but semantically unlabeled
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Inbound and outbound nodes occupy opposite sides, but there are no stable group labels/legend cues for neighbor roles.
- **expected intent:** Make relationship grouping perceptible when authorized without copying unsupported categories from the reference.
- **material visual delta:** Current state materially diverges from the governed intent: Inbound and outbound nodes occupy opposite sides, but there are no stable group labels/legend cues for neighbor roles.
- **user consequence:** Dense graph inspection requires reading each edge individually to understand why nodes are grouped.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_DESIGN_GAP`
- **root-cause binding:** `layoutFocusedGraph()` grouping + Graph rendering.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not infer prerequisite/related meaning from left/right position alone.
- **validation requirement:** Authorized mixed relation fixture; grouping/legend matches exact edge semantics.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Focused-neighbor grammar broad finding
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-G-009 — Graph — Workspace geometry / Hard-coded 960-unit layout is not container-aware

- **finding ID:** `VIS-G-009`
- **view/mode:** Graph
- **category/subcategory:** Workspace geometry / Hard-coded 960-unit layout is not container-aware
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Focused layout fixes x positions at 130/480/830 and minimum width 960, independent of actual CENTER aspect ratio.
- **expected intent:** Adapt deterministic layout to container/density while preserving focus and readable labels.
- **material visual delta:** Current state materially diverges from the governed intent: Focused layout fixes x positions at 130/480/830 and minimum width 960, independent of actual CENTER aspect ratio.
- **user consequence:** At 1440 topology occupies a small area; at medium width it can shrink text rather than recompose.
- **severity:** `MEDIUM-HIGH`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `viewModels.ts::layoutFocusedGraph()` fixed geometry.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** ARCH-SHARED-01
- **collision:** High with shared shell migration
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Container-aspect tests at 1440/~1024 and dense neighborhoods; define minimum readable node/label size.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior M4/CENTER-use broad finding
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-G-010 — Graph — Label placement / Midpoint edge labels have no collision handling

- **finding ID:** `VIS-G-010`
- **view/mode:** Graph
- **category/subcategory:** Label placement / Midpoint edge labels have no collision handling
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Every relationship label is placed at its edge midpoint; coincident/crossing edges can stack labels.
- **expected intent:** Use deterministic label placement/routing that avoids material occlusion at representative density.
- **material visual delta:** Current state materially diverges from the governed intent: Every relationship label is placed at its edge midpoint; coincident/crossing edges can stack labels.
- **user consequence:** Relation type becomes unreadable exactly where topology is most complex.
- **severity:** `HIGH`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `GraphView.vue` midpoint label placement.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not hide semantic relation type without an accessible alternative.
- **validation requirement:** Parallel/crossing-edge stress fixture at fit and zoom levels; zero material label collisions.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-G-011 — Graph — Interaction design / No hover/pre-inspection state for dense topology

- **finding ID:** `VIS-G-011`
- **view/mode:** Graph
- **category/subcategory:** Interaction design / No hover/pre-inspection state for dense topology
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Graph supports click and keyboard selection but no hover/focus preview that temporarily emphasizes a node neighborhood or edge path.
- **expected intent:** Provide a deliberate low-commitment exploration state if useful, with keyboard-equivalent focus; never make critical semantics hover-only.
- **material visual delta:** Current state materially diverges from the governed intent: Graph supports click and keyboard selection but no hover/focus preview that temporarily emphasizes a node neighborhood or edge path.
- **user consequence:** Pointer users must commit selection to trace crossing edges, making exploration slower.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_INTERACTION_GAP`
- **root-cause binding:** `GraphView.vue` lacks hover state.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Dense graph pointer hover/focus-equivalent test; no tooltip/preview clipping at ~1024 and no hover-only semantics.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-G-012 — Graph — State coverage / Isolated/non-focus nodes fall into an unexplained bottom grid

- **finding ID:** `VIS-G-012`
- **view/mode:** Graph
- **category/subcategory:** State coverage / Isolated/non-focus nodes fall into an unexplained bottom grid
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Nodes outside the chosen focus neighborhood are placed in a generic lower grid without explicit meaning.
- **expected intent:** Deliberately communicate 'outside focus neighborhood' or equivalent truthful status while preserving access to all in-scope nodes.
- **material visual delta:** Current state materially diverges from the governed intent: Nodes outside the chosen focus neighborhood are placed in a generic lower grid without explicit meaning.
- **user consequence:** Users can infer a second cluster or relationship that does not exist.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_DESIGN_GAP + EVIDENCE_GAP`
- **root-cause binding:** `layoutFocusedGraph()` `remaining` grid.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not hide in-scope canonical nodes without a disclosed filter/projection rule.
- **validation requirement:** Fixture with isolated and weakly connected nodes; explain placement and preserve selection/context.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior Graph evidence broadly considered topology only
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-G-013 — Graph — Representative data / Only two relationship types are exercised

- **finding ID:** `VIS-G-013`
- **view/mode:** Graph
- **category/subcategory:** Representative data / Only two relationship types are exercised
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Current world proves containment and prerequisite edges only; related/pathway and richer type combinations are not evidenced.
- **expected intent:** Exercise each relation type the product actually claims to support, or narrow the runtime claim.
- **material visual delta:** Current state materially diverges from the governed intent: Current world proves containment and prerequisite edges only; related/pathway and richer type combinations are not evidenced.
- **user consequence:** Legend, grouping and collision behavior for untested relation types may fail.
- **severity:** `HIGH`
- **classification:** `EVIDENCE_GAP / PROVEN_PARTIAL`
- **root-cause binding:** Current fixture/backend emitted relation set.
- **confidence:** `HIGH`
- **owner:** Assurance/test harness + provider owners
- **shared dependency:** SD-05 + SD-03
- **collision:** Low
- **prohibited shortcut:** Do not invent related/pathway edges solely to fill the graph.
- **validation requirement:** Controlled relation-type matrix with provenance and supported-view checks at 1440/~1024.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior G-02 allowed topology differences; evidence matrix not decomposed by type
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-G-014 — Graph — Viewport interactions / Pan/zoom/fit runtime matrix and ~1024 proof are incomplete

- **finding ID:** `VIS-G-014`
- **view/mode:** Graph
- **category/subcategory:** Viewport interactions / Pan/zoom/fit runtime matrix and ~1024 proof are incomplete
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **reference evidence:** Focused Graph supporting reference (Drive 1HDCuHAJqqIeH4CH95ay_AQSOzjQi9IKh) + PRD/Visual Contract
- **current evidence:** Current edge-selected Graph 1AJnmNDUcrOU_UsQzw1EQNppxc1UalrAz + DataClone manifest 1TI7E9QHL_blHxCO3K3m1ZW4mAyL2dWnh
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Source supports pointer pan, keyboard pan/zoom, wheel zoom and content-bound FIT, but exact browser interaction proof is partial and ~1024 is absent.
- **expected intent:** Close browser evidence without rewriting mechanisms that are already code/test-proven.
- **material visual delta:** Current state materially diverges from the governed intent: Source supports pointer pan, keyboard pan/zoom, wheel zoom and content-bound FIT, but exact browser interaction proof is partial and ~1024 is absent.
- **user consequence:** Viewport regressions or focus loss may remain despite green tests.
- **severity:** `MEDIUM-HIGH`
- **classification:** `EVIDENCE_GAP`
- **root-cause binding:** `useSvgViewport.ts` + evidence gap.
- **confidence:** `HIGH`
- **owner:** Assurance/evidence owner
- **shared dependency:** SD-06
- **collision:** Low
- **prohibited shortcut:** Do not regress to a percent-label reset or claim runtime proof from source inspection alone.
- **validation requirement:** Pointer/keyboard pan, wheel/keyboard zoom, FIT with node/edge selection at 1440/~1024; no focus loss/errors.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior VIS-01/G-03 + validation matrix
- **reconciliation:** `KNOWN`


## VIS-C-001 — Canvas — Visualization grammar / Canvas default layout is literally the Graph focused layout

- **finding ID:** `VIS-C-001`
- **view/mode:** Canvas
- **category/subcategory:** Visualization grammar / Canvas default layout is literally the Graph focused layout
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **reference evidence:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **current evidence:** Current Canvas screenshot 1fNQK-9Wutze3n8WlPhyCzhbV0ZNv8iXS + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** `CanvasView.vue` seeds every unmoved node from `layoutFocusedGraph(..., null)`.
- **expected intent:** Canvas should have a distinct spatial grammar or explicitly disclose that its initial arrangement is a borrowed graph-layout projection.
- **material visual delta:** Current state materially diverges from the governed intent: `CanvasView.vue` seeds every unmoved node from `layoutFocusedGraph(..., null)`.
- **user consequence:** Canvas can appear to encode spatial meaning even though its initial coordinates are only a Graph-layout default.
- **severity:** `HIGH`
- **classification:** `PRODUCT_SEMANTIC_DESIGN_DEFECT`
- **root-cause binding:** `CanvasView.vue::defaultLayout` → `viewModels.ts::layoutFocusedGraph()`.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not persist default layout as canonical coordinates or invent spatial semantics.
- **validation requirement:** Compare fresh Graph vs fresh Canvas layout; document default-layout provenance and test reset/fit/moved-state semantics.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior C-01 only validated representation-only movement
- **reconciliation:** `MISSED_NEW`


## VIS-C-002 — Canvas — Interaction design / Pointer camera pan is inconsistent with Graph

- **finding ID:** `VIS-C-002`
- **view/mode:** Canvas
- **category/subcategory:** Interaction design / Pointer camera pan is inconsistent with Graph
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **reference evidence:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **current evidence:** Current Canvas screenshot 1fNQK-9Wutze3n8WlPhyCzhbV0ZNv8iXS + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Canvas uses `useSvgViewport`, but pointer handlers are consumed by node drag; it does not expose Graph-like background pointer pan.
- **expected intent:** Spatial controls should mirror Graph or intentionally define a different, discoverable Canvas camera model.
- **material visual delta:** Current state materially diverges from the governed intent: Canvas uses `useSvgViewport`, but pointer handlers are consumed by node drag; it does not expose Graph-like background pointer pan.
- **user consequence:** Mouse users can move nodes but cannot naturally drag the background to move the viewport.
- **severity:** `MEDIUM-HIGH`
- **classification:** `PRODUCT_INTERACTION_DEFECT`
- **root-cause binding:** `CanvasView.vue` viewport integration and pointer handlers.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not overload node drag so attempted camera movement mutates node positions.
- **validation requirement:** Background pointer pan vs node drag; keyboard camera pan vs keyboard node movement; pointer capture at both viewports.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-C-003 — Canvas — Relationship grammar / Canvas edges have no visible relation-type labels

- **finding ID:** `VIS-C-003`
- **view/mode:** Canvas
- **category/subcategory:** Relationship grammar / Canvas edges have no visible relation-type labels
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **reference evidence:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **current evidence:** Current Canvas screenshot 1fNQK-9Wutze3n8WlPhyCzhbV0ZNv8iXS + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Edges use color/dash/arrow styling but render no relationship-type text.
- **expected intent:** Make relation type visible/adaptively discoverable and accessible without overwhelming the spatial canvas.
- **material visual delta:** Current state materially diverges from the governed intent: Edges use color/dash/arrow styling but render no relationship-type text.
- **user consequence:** Users must infer containment vs prerequisite from line style alone.
- **severity:** `HIGH`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `CanvasView.vue` edge rendering.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not rely on color/dash alone or blindly copy Graph labels if they create collisions.
- **validation requirement:** Mixed relation fixture at default zoom; type is readable/discoverable for pointer and keyboard selection.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-C-004 — Canvas — Visual grammar / No legend for spatial relationship/overlay encodings

- **finding ID:** `VIS-C-004`
- **view/mode:** Canvas
- **category/subcategory:** Visual grammar / No legend for spatial relationship/overlay encodings
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **reference evidence:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **current evidence:** Current Canvas screenshot 1fNQK-9Wutze3n8WlPhyCzhbV0ZNv8iXS + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Canvas uses multiple stroke styles/colors but provides no semantic key.
- **expected intent:** Provide a discoverable legend/equivalent explanation; color is supplementary.
- **material visual delta:** Current state materially diverges from the governed intent: Canvas uses multiple stroke styles/colors but provides no semantic key.
- **user consequence:** Spatial encodings are unexplained, especially with Overlay active.
- **severity:** `MEDIUM-HIGH`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `CanvasView.vue` has no legend.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not invent canonical data, broaden authority, or mutate shared/product state outside the governed owner.
- **validation requirement:** Legend with containment/prerequisite and active overlay; non-color cues and ~1024 visibility.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-C-005 — Canvas — Selection state / Selected edge has no visual selected state in CENTER

- **finding ID:** `VIS-C-005`
- **view/mode:** Canvas
- **category/subcategory:** Selection state / Selected edge has no visual selected state in CENTER
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **reference evidence:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **current evidence:** Current Canvas screenshot 1fNQK-9Wutze3n8WlPhyCzhbV0ZNv8iXS + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Edge click updates selection/RIGHT, but Canvas line styling ignores `selection`; only overlay targeting changes stroke weight.
- **expected intent:** Selected relationship must be visually identifiable in the canvas independent of Overlay state.
- **material visual delta:** Current state materially diverges from the governed intent: Edge click updates selection/RIGHT, but Canvas line styling ignores `selection`; only overlay targeting changes stroke weight.
- **user consequence:** Users cannot tell which crossing line RIGHT is describing.
- **severity:** `HIGH`
- **classification:** `PRODUCT_INTERACTION_DEFECT`
- **root-cause binding:** `CanvasView.vue` line styles do not check `props.selection`.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not conflate selected state with overlay-target state.
- **validation requirement:** Pointer/keyboard edge selection, visible line/endpoints, active Overlay simultaneously, Back/Forward restoration.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior C-03 was only movement evidence
- **reconciliation:** `MISSED_NEW`


## VIS-C-006 — Canvas — Information hierarchy / Ephemeral x/y coordinates are overly prominent

- **finding ID:** `VIS-C-006`
- **view/mode:** Canvas
- **category/subcategory:** Information hierarchy / Ephemeral x/y coordinates are overly prominent
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **reference evidence:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **current evidence:** Current Canvas screenshot 1fNQK-9Wutze3n8WlPhyCzhbV0ZNv8iXS + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Every node permanently displays `x=... · y=...` although positions are explicitly session-only representation metadata.
- **expected intent:** Keep coordinates available for precision/diagnostics but subordinate them to node/relationship meaning and persistence status.
- **material visual delta:** Current state materially diverges from the governed intent: Every node permanently displays `x=... · y=...` although positions are explicitly session-only representation metadata.
- **user consequence:** Users can over-read coordinates as persistent/canonical despite the representation-only notice.
- **severity:** `MEDIUM`
- **classification:** `PRODUCT_DESIGN_DEFECT`
- **root-cause binding:** `CanvasView.vue` node-card metadata.
- **confidence:** `HIGH`
- **owner:** Visualize local
- **shared dependency:** Saved Map authority affects future persistence meaning
- **collision:** Low
- **prohibited shortcut:** Do not hide the representation-only disclaimer or imply coordinates are curriculum semantics.
- **validation requirement:** Usability review before/after movement; persistence state remains obvious and coordinates remain accessible when needed.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-C-007 — Canvas — Evidence / Pointer/keyboard movement and canonical invariance lack browser closure

- **finding ID:** `VIS-C-007`
- **view/mode:** Canvas
- **category/subcategory:** Evidence / Pointer/keyboard movement and canonical invariance lack browser closure
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **reference evidence:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **current evidence:** Current Canvas screenshot 1fNQK-9Wutze3n8WlPhyCzhbV0ZNv8iXS + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Source/tests prove representation-only movement, but no exact before/after runtime sequence proves pointer drag, keyboard movement and unchanged canonical placement/relations.
- **expected intent:** Capture direct interaction evidence while preserving the current session-only truth.
- **material visual delta:** Current state materially diverges from the governed intent: Source/tests prove representation-only movement, but no exact before/after runtime sequence proves pointer drag, keyboard movement and unchanged canonical placement/relations.
- **user consequence:** A runtime wiring defect could still violate a critical truth invariant.
- **severity:** `HIGH`
- **classification:** `EVIDENCE_GAP`
- **root-cause binding:** Evidence gap; `VisualizationProjection::moveVisualNode()` and tests are positive source evidence.
- **confidence:** `HIGH`
- **owner:** Assurance/evidence owner
- **shared dependency:** None
- **collision:** Low
- **prohibited shortcut:** Do not implement persistence or mutate canonical records just to produce evidence.
- **validation requirement:** Before/after pointer drag + Arrow + Shift+Arrow; coordinates change, selection stable, canonical placement/relationship hashes or rows unchanged.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior C-03 / validation matrix
- **reconciliation:** `KNOWN`


## VIS-C-008 — Canvas / Map — Map semantics / Saved Map remains a governance-gated authority gap

- **finding ID:** `VIS-C-008`
- **view/mode:** Canvas / Map
- **category/subcategory:** Map semantics / Saved Map remains a governance-gated authority gap
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **reference evidence:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **current evidence:** Current Canvas screenshot 1fNQK-9Wutze3n8WlPhyCzhbV0ZNv8iXS + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Runtime is truthfully `UNSAVED_PROJECTION`; workspace supplies `savedMap=null`; no repository, actor access, Select/New/Save/Load flow exists.
- **expected intent:** Keep fail-closed unsaved behavior until controlling authority designates a real Map object/store/access contract; only then validate saved/stale/not-found/unauthorized states.
- **material visual delta:** Current state materially diverges from the governed intent: Runtime is truthfully `UNSAVED_PROJECTION`; workspace supplies `savedMap=null`; no repository, actor access, Select/New/Save/Load flow exists.
- **user consequence:** Users cannot persist a Map today, but false pseudo-persistence would violate governance and security.
- **severity:** `HIGH`
- **classification:** `AUTHORITY_DEPENDENCY / NOT_AUTHORIZED_FOR_THIS_AUDIT`
- **root-cause binding:** `KnowledgeLearningWorkspace::visualize()` + `VisualizationProjection::resolveMap()`.
- **confidence:** `HIGH`
- **owner:** Controller/Parent + future shared Map owner
- **shared dependency:** SD-02
- **collision:** Routes/workspace/storage are shared seams
- **prohibited shortcut:** No localStorage/client registry, fake Save/New controls, Map-ID-only authorization, schema invention or migration in this audit.
- **validation requirement:** If later authorized: actor-safe create/load/save, stale/not-found/unauthorized, visual_positions round-trip and canonical invariance; otherwise preserve UNSAVED_PROJECTION.
- **screenshot closure state:** `OPEN BY GOVERNANCE`
- **prior finding mapping:** Prior C-02 / M-01/M-02 / VIS-02
- **reconciliation:** `KNOWN`


## VIS-C-009 — Canvas / Map / View / Overlay — Semantic IA / Map, filter, View and Overlay are visually too easy to conflate

- **finding ID:** `VIS-C-009`
- **view/mode:** Canvas / Map / View / Overlay
- **category/subcategory:** Semantic IA / Map, filter, View and Overlay are visually too easy to conflate
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **reference evidence:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **current evidence:** Current Canvas screenshot 1fNQK-9Wutze3n8WlPhyCzhbV0ZNv8iXS + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** TOP exposes View plus filter chip `عالم العرض` and Overlay; actual Map identity/state appears in LEFT as `عالم العرض الحالي`.
- **expected intent:** Make Map/world identity, View representation, Filter projection subset and Overlay analytical layer unmistakably distinct without inventing Saved Map persistence.
- **material visual delta:** Current state materially diverges from the governed intent: TOP exposes View plus filter chip `عالم العرض` and Overlay; actual Map identity/state appears in LEFT as `عالم العرض الحالي`.
- **user consequence:** Users can interpret a filter toggle as changing Map/world membership or persistence scope.
- **severity:** `MEDIUM-HIGH`
- **classification:** `PRODUCT_INFORMATION_ARCHITECTURE_DEFECT`
- **root-cause binding:** `Visualize.vue` control labels/placement + shared region ownership.
- **confidence:** `HIGH`
- **owner:** Visualize + shared shell owner
- **shared dependency:** ARCH-SHARED-01
- **collision:** Visualize.vue is a shared integration collision point
- **prohibited shortcut:** Do not add fake Map persistence just to solve terminology.
- **validation requirement:** Map/view/filter/overlay transition matrix; URL tokens and LEFT identity remain consistent; Arabic terminology review.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior Map/LEFT findings did not isolate filter terminology collision
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-C-010 — Canvas / Overlay — Overlay evidence / Active Overlay visual effect is not runtime-proven

- **finding ID:** `VIS-C-010`
- **view/mode:** Canvas / Overlay
- **category/subcategory:** Overlay evidence / Active Overlay visual effect is not runtime-proven
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **reference evidence:** PRD MAP/VIEW/OVERLAY + final Visual Contract + correction overlay; no direct pixel reference
- **current evidence:** Current Canvas screenshot 1fNQK-9Wutze3n8WlPhyCzhbV0ZNv8iXS + exact-SHA source
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Source applies `overlayTargets`, but there is no exact Canvas screenshot with prerequisite Overlay active or unavailable reason interaction.
- **expected intent:** Prove Overlay emphasis without changing relation type, node position or canonical semantics.
- **material visual delta:** Current state materially diverges from the governed intent: Source applies `overlayTargets`, but there is no exact Canvas screenshot with prerequisite Overlay active or unavailable reason interaction.
- **user consequence:** Selection and overlay emphasis could be visually indistinguishable or fail in runtime.
- **severity:** `MEDIUM-HIGH`
- **classification:** `EVIDENCE_GAP`
- **root-cause binding:** Evidence coverage only.
- **confidence:** `HIGH`
- **owner:** Assurance/evidence owner
- **shared dependency:** SD-05/provider
- **collision:** Low
- **prohibited shortcut:** Do not fabricate coverage/progress/evidence/mastery signals.
- **validation requirement:** Prerequisite active Canvas at 1440/~1024, simultaneous selected edge, plus one unavailable-layer reason.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior VIS-06 / O-02
- **reconciliation:** `KNOWN`


## VIS-S-001 — Shared Visualize — Representative data / All current screenshots are test-fixture evidence, not representative canonical-runtime evidence

- **finding ID:** `VIS-S-001`
- **view/mode:** Shared Visualize
- **category/subcategory:** Representative data / All current screenshots are test-fixture evidence, not representative canonical-runtime evidence
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **reference evidence:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **current evidence:** Exact evidence folder 1PFzCmQakLV9-M-O5HuOIvsBMX6iRYIe6 + source at exact SHA
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** DOM identifies testing/local and Test KU labels; all four modes reuse the same sparse world.
- **expected intent:** Classify current screenshots as mechanism/UI evidence and use a richer lawful deterministic fixture for design closure.
- **material visual delta:** Current state materially diverges from the governed intent: DOM identifies testing/local and Test KU labels; all four modes reuse the same sparse world.
- **user consequence:** The same sparse input can systematically hide mode-specific defects across Tree, Path, Graph and Canvas.
- **severity:** `HIGH`
- **classification:** `TEST_FIXTURE_ONLY`
- **root-cause binding:** Assurance runtime fixture.
- **confidence:** `HIGH`
- **owner:** Assurance/test harness owner
- **shared dependency:** SD-05
- **collision:** Low
- **prohibited shortcut:** Do not relabel test data as canonical or import B09 without runtime authority.
- **validation requirement:** Fixture manifest records source/classification and exercises hierarchy depth, branch/cycle, mixed labels, multiple relations and density.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior package called fixture shallow but did not formalize representativeness class
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-S-002 — Shared Visualize — Runtime binding / B09 canonical baseline does not prove B09→runtime→Visualize binding

- **finding ID:** `VIS-S-002`
- **view/mode:** Shared Visualize
- **category/subcategory:** Runtime binding / B09 canonical baseline does not prove B09→runtime→Visualize binding
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **reference evidence:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **current evidence:** Exact evidence folder 1PFzCmQakLV9-M-O5HuOIvsBMX6iRYIe6 + source at exact SHA
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** B09 structural summary proves 224 KUs and 192 capabilities, but current Visualize runtime is fixture-backed; W02AcceptanceSeeder explicitly refuses B09/B10 archive import.
- **expected intent:** Maintain separate provenance states: B09 structural baseline can be PROVEN_FULL while full canonical runtime import remains NOT_PROVEN.
- **material visual delta:** Current state materially diverges from the governed intent: B09 structural summary proves 224 KUs and 192 capabilities, but current Visualize runtime is fixture-backed; W02AcceptanceSeeder explicitly refuses B09/B10 archive import.
- **user consequence:** Controller could falsely infer that canonical source coverage is represented in screenshots merely because B09 exists.
- **severity:** `HIGH`
- **classification:** `RUNTIME_BINDING_GAP`
- **root-cause binding:** B09 summary 143X... + `W02AcceptanceSeeder.php` + current DOM.
- **confidence:** `HIGH`
- **owner:** Shared data/runtime authority + assurance
- **shared dependency:** Canonical runtime mapping/import authority
- **collision:** High if anyone attempts runtime import; not authorized here
- **prohibited shortcut:** Do not import B09 or mutate mappings solely to satisfy Visualize evidence.
- **validation requirement:** Source→DB→service→projection→View→screenshot trace from an approved runtime source; otherwise retain NOT_PROVEN.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic data-lineage finding
- **reconciliation:** `MISSED_NEW`


## VIS-S-003 — Shared Visualize — Runtime binding / Workspace call path cannot exercise Saved Map or external overlay providers

- **finding ID:** `VIS-S-003`
- **view/mode:** Shared Visualize
- **category/subcategory:** Runtime binding / Workspace call path cannot exercise Saved Map or external overlay providers
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **reference evidence:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **current evidence:** Exact evidence folder 1PFzCmQakLV9-M-O5HuOIvsBMX6iRYIe6 + source at exact SHA
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** `KnowledgeLearningWorkspace::visualize()` calls curriculum visualization with `overlaySignals=[]` and `savedMap=null`.
- **expected intent:** Keep fail-closed truth but record provider seams as explicit runtime-binding gaps, not renderer defects.
- **material visual delta:** Current state materially diverges from the governed intent: `KnowledgeLearningWorkspace::visualize()` calls curriculum visualization with `overlaySignals=[]` and `savedMap=null`.
- **user consequence:** Controls can appear broader than the data/provider capabilities actually connected to the route.
- **severity:** `HIGH`
- **classification:** `RUNTIME_BINDING_GAP / AUTHORITY_DEPENDENT`
- **root-cause binding:** `app/Application/KnowledgeLearning/KnowledgeLearningWorkspace.php::visualize()`.
- **confidence:** `HIGH`
- **owner:** Shared application/runtime owner
- **shared dependency:** SD-02 + overlay-provider authority
- **collision:** Shared route/workspace seam
- **prohibited shortcut:** Do not wire unauthorized providers or fake signals.
- **validation requirement:** Provider-by-provider binding matrix: source authority, runtime call path, unavailable reason, direct UI evidence.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior VIS-02/VIS-04 discussed each separately
- **reconciliation:** `UNDER_SPECIFIED`


## VIS-S-004 — Shared Visualize — Overlay / Overlay breadth is PROVEN_PARTIAL only

- **finding ID:** `VIS-S-004`
- **view/mode:** Shared Visualize
- **category/subcategory:** Overlay / Overlay breadth is PROVEN_PARTIAL only
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **reference evidence:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **current evidence:** Exact evidence folder 1PFzCmQakLV9-M-O5HuOIvsBMX6iRYIe6 + source at exact SHA
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Prerequisite is available; coverage/progress are `NO_DATA`; evidence/mastery are `NO_AUTHORITY`.
- **expected intent:** Preserve truthful absence reasons; evaluate additional layers only when authoritative providers exist.
- **material visual delta:** Current state materially diverges from the governed intent: Prerequisite is available; coverage/progress are `NO_DATA`; evidence/mastery are `NO_AUTHORITY`.
- **user consequence:** Four advertised analytical concepts cannot be substantively evaluated in the current world.
- **severity:** `MEDIUM`
- **classification:** `PROVEN_PARTIAL / RUNTIME_BINDING_GAP`
- **root-cause binding:** `OverlayProjector` + DOM provider state.
- **confidence:** `HIGH`
- **owner:** Relevant provider owners; Visualize owns truthful unavailable grammar
- **shared dependency:** Provider authority
- **collision:** Low
- **prohibited shortcut:** Do not synthesize zero coverage/progress/mastery/evidence from absence.
- **validation requirement:** Active prerequisite across views plus explicit NO_DATA and NO_AUTHORITY interactions; future providers only when authorized.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior VIS-04/O-01/O-03
- **reconciliation:** `KNOWN`


## VIS-S-005 — Shared Visualize — Workspace architecture / Visualize rebuilds a second workspace inside shared CENTER

- **finding ID:** `VIS-S-005`
- **view/mode:** Shared Visualize
- **category/subcategory:** Workspace architecture / Visualize rebuilds a second workspace inside shared CENTER
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **reference evidence:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **current evidence:** Exact evidence folder 1PFzCmQakLV9-M-O5HuOIvsBMX6iRYIe6 + source at exact SHA
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Shared `CepWorkspaceLayout` already owns TOP/LEFT/CENTER/RIGHT/BOTTOM geometry, while Visualize recreates local header, LEFT, CENTER and RIGHT.
- **expected intent:** One shared region owner; Visualize supplies surface-specific content while retaining semantic map/view/overlay/filter/selection state.
- **material visual delta:** Current state materially diverges from the governed intent: Shared `CepWorkspaceLayout` already owns TOP/LEFT/CENTER/RIGHT/BOTTOM geometry, while Visualize recreates local header, LEFT, CENTER and RIGHT.
- **user consequence:** CENTER is constrained, context mechanics duplicate, and ~1024 behavior can diverge from other W02 surfaces.
- **severity:** `HIGH`
- **classification:** `SHARED_DEPENDENCY / PRODUCT_ARCHITECTURE_DEFECT`
- **root-cause binding:** `Visualize.vue` vs `resources/js/layouts/CepWorkspaceLayout.vue`.
- **confidence:** `HIGH`
- **owner:** Serialized shared shell/integration owner
- **shared dependency:** ARCH-SHARED-01
- **collision:** High—Visualize.vue may be touched by local remediation and shared migration
- **prohibited shortcut:** Do not widen a Visualize-local audit/writer to mutate shared shell or global CSS.
- **validation requirement:** Composed candidate at 1440/~1024; shared panel resize/collapse/context toggle; Library/Learn/RQ non-regression.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** ARCH-SHARED-01
- **reconciliation:** `KNOWN`


## VIS-S-006 — Shared Visualize — Responsive evidence / No exact-current ~1024 Visualize evidence exists

- **finding ID:** `VIS-S-006`
- **view/mode:** Shared Visualize
- **category/subcategory:** Responsive evidence / No exact-current ~1024 Visualize evidence exists
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **reference evidence:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **current evidence:** Exact evidence folder 1PFzCmQakLV9-M-O5HuOIvsBMX6iRYIe6 + source at exact SHA
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Evidence folder contains no `screenshot_1024_Visualize*` for any of the four modes.
- **expected intent:** Prove all four views plus context drawer/control wrapping at the explicitly governed secondary viewport.
- **material visual delta:** Current state materially diverges from the governed intent: Evidence folder contains no `screenshot_1024_Visualize*` for any of the four modes.
- **user consequence:** A responsive defect can remain in every mode despite correct-looking 1440 screenshots.
- **severity:** `HIGH`
- **classification:** `EVIDENCE_GAP`
- **root-cause binding:** Evidence absence.
- **confidence:** `HIGH`
- **owner:** Assurance/evidence owner
- **shared dependency:** ARCH-SHARED-01 / SD-06
- **collision:** Shared shell changes exact geometry
- **prohibited shortcut:** Do not reuse RQ ~1024 screenshots or infer from CSS.
- **validation requirement:** Tree/Path/Graph/Canvas at ~1024 with no page horizontal overflow, RIGHT access, keyboard focus and Bidi.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior R-01 / SD-06
- **reconciliation:** `KNOWN`


## VIS-S-007 — Shared Visualize — Context semantics / RIGHT neutral state wastes context while an active scope exists

- **finding ID:** `VIS-S-007`
- **view/mode:** Shared Visualize
- **category/subcategory:** Context semantics / RIGHT neutral state wastes context while an active scope exists
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **reference evidence:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **current evidence:** Exact evidence folder 1PFzCmQakLV9-M-O5HuOIvsBMX6iRYIe6 + source at exact SHA
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** With no explicit selection, a large RIGHT panel is neutral even though active KU/current scope is prominent in LEFT/header.
- **expected intent:** Define useful neutral-scope context without duplicating selected identity or actions; preserve selection as the inspection owner.
- **material visual delta:** Current state materially diverges from the governed intent: With no explicit selection, a large RIGHT panel is neutral even though active KU/current scope is prominent in LEFT/header.
- **user consequence:** Users lose contextual continuity and a large region appears inert.
- **severity:** `MEDIUM-HIGH`
- **classification:** `PRODUCT_INFORMATION_ARCHITECTURE_DEFECT`
- **root-cause binding:** `Visualize.vue` RIGHT selection-only state + nested local shell.
- **confidence:** `HIGH`
- **owner:** Visualize + shared context owner
- **shared dependency:** ARCH-SHARED-01
- **collision:** High with shell migration
- **prohibited shortcut:** Do not copy the same current-object details into header, LEFT and RIGHT.
- **validation requirement:** Active-only vs selected node/edge vs cleared state at 1440/~1024; one authoritative location per information item.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior M5 closed selected RIGHT but did not decompose active-neutral state
- **reconciliation:** `MISSED_NEW`


## VIS-S-008 — Shared Visualize — State grammar / Arabic label `عالم العرض` collides between filter and world/Map concept

- **finding ID:** `VIS-S-008`
- **view/mode:** Shared Visualize
- **category/subcategory:** State grammar / Arabic label `عالم العرض` collides between filter and world/Map concept
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **reference evidence:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **current evidence:** Exact evidence folder 1PFzCmQakLV9-M-O5HuOIvsBMX6iRYIe6 + source at exact SHA
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** The `all` filter is labeled `عالم العرض`, while LEFT names the current world/Map state `عالم العرض الحالي`.
- **expected intent:** Use unmistakably different terminology for Map/world identity versus projection filter.
- **material visual delta:** Current state materially diverges from the governed intent: The `all` filter is labeled `عالم العرض`, while LEFT names the current world/Map state `عالم العرض الحالي`.
- **user consequence:** Users can believe a filter changes Map membership or persistence scope.
- **severity:** `MEDIUM-HIGH`
- **classification:** `PRODUCT_INFORMATION_ARCHITECTURE_DEFECT`
- **root-cause binding:** `Visualize.vue` Arabic control copy.
- **confidence:** `HIGH`
- **owner:** Visualize + shared shell owner
- **shared dependency:** ARCH-SHARED-01
- **collision:** Visualize.vue shared integration seam
- **prohibited shortcut:** Do not introduce persistence or new schema to fix terminology.
- **validation requirement:** Arabic UX terminology review + route-state transition matrix.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** No prior atomic finding
- **reconciliation:** `MISSED_NEW`


## VIS-S-009 — Shared Visualize — Overlay evidence / Active/unavailable Overlay visual states remain evidence-open

- **finding ID:** `VIS-S-009`
- **view/mode:** Shared Visualize
- **category/subcategory:** Overlay evidence / Active/unavailable Overlay visual states remain evidence-open
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **reference evidence:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **current evidence:** Exact evidence folder 1PFzCmQakLV9-M-O5HuOIvsBMX6iRYIe6 + source at exact SHA
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** View-aware source behavior exists, but all-view active prerequisite screenshots and unavailable-reason interactions are missing.
- **expected intent:** Capture active prerequisite in Tree/Path/Graph/Canvas and explicit NO_DATA/NO_AUTHORITY reason behavior.
- **material visual delta:** Current state materially diverges from the governed intent: View-aware source behavior exists, but all-view active prerequisite screenshots and unavailable-reason interactions are missing.
- **user consequence:** Source-level correctness can hide visual styling or state-sync failures.
- **severity:** `HIGH`
- **classification:** `EVIDENCE_GAP`
- **root-cause binding:** Evidence coverage.
- **confidence:** `HIGH`
- **owner:** Assurance/evidence owner
- **shared dependency:** SD-05/provider
- **collision:** Low
- **prohibited shortcut:** Do not fabricate unsupported provider semantics.
- **validation requirement:** Four active-view captures + one NO_DATA + one NO_AUTHORITY + deep-link/view normalization.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior VIS-06/O-02
- **reconciliation:** `KNOWN`


## VIS-S-010 — Shared Visualize — RTL/Bidi/accessibility evidence / RTL/Bidi/accessibility proof is too weak because labels are trivial

- **finding ID:** `VIS-S-010`
- **view/mode:** Shared Visualize
- **category/subcategory:** RTL/Bidi/accessibility evidence / RTL/Bidi/accessibility proof is too weak because labels are trivial
- **viewport/direction:** 1440 RTL primary; ~1024 RTL secondary where applicable
- **reference authority:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **reference evidence:** PRD + final Visual Contract + W02 Operating Model + PORT-METHOD-032/033
- **current evidence:** Exact evidence folder 1PFzCmQakLV9-M-O5HuOIvsBMX6iRYIe6 + source at exact SHA
- **exact SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- **observed state:** Source uses RTL/LTR isolation well, but current fixture has short English `Test KU` labels; no ~1024 or long mixed Arabic/English focus/ARIA matrix is evidenced.
- **expected intent:** Prove mixed-content wrapping, technical-token isolation, semantic arrows, keyboard focus and medium-layout context access.
- **material visual delta:** Current state materially diverges from the governed intent: Source uses RTL/LTR isolation well, but current fixture has short English `Test KU` labels; no ~1024 or long mixed Arabic/English focus/ARIA matrix is evidenced.
- **user consequence:** Latent word reversal, clipping, focus traps or inaccessible status can remain unseen.
- **severity:** `MEDIUM-HIGH`
- **classification:** `EVIDENCE_GAP / PROVEN_PARTIAL`
- **root-cause binding:** Evidence data/interaction coverage.
- **confidence:** `HIGH`
- **owner:** Assurance/accessibility owner; Visualize if defect reproduced
- **shared dependency:** SD-05 + SD-06
- **collision:** Shared shell affects focus order/drawer
- **prohibited shortcut:** Do not treat short English Test KU strings as RTL acceptance evidence.
- **validation requirement:** Long Arabic/English labels, IDs, relation arrows, Canvas coordinates, overlay reason, keyboard-only traversal and ARIA at 1440/~1024.
- **screenshot closure state:** `OPEN`
- **prior finding mapping:** Prior Bidi/accessibility evidence requirements
- **reconciliation:** `UNDER_SPECIFIED`


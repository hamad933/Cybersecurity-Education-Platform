# 07_EVIDENCE_GAPS_AND_FUTURE_VALIDATION_REQUIREMENTS

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


## 1. Evidence verdict

1440 mechanism evidence exists for Tree / Path / Graph / Canvas, but the acceptance matrix is incomplete. Missing evidence is recorded independently from known product defects.

## 2. Required all-view matrix

| View | 1440 | ~1024 | Required controlled states |
|---|---|---|---|
| Tree | current shallow capture exists | MISSING | 4-level hierarchy; expanded/collapsed; long tree; selected node/relation; active vs selected RIGHT. |
| Path | current derived capture exists | MISSING | branch; join; cycle; no prerequisite; selected node/relation; truthful derived/canonical mode. |
| Graph | edge-selected exists | MISSING | node-selected focus; edge-centered inspection; isolated node; dense relations; legend/label legibility; pan/zoom/FIT. |
| Canvas | static capture exists | MISSING | pointer move; keyboard move; selected edge; camera pan; active overlay; canonical invariance. |

## 3. State matrix

Must be directly checked on the exact successor candidate before acceptance:

- active-only current scope.
- selected node.
- selected edge.
- cleared selection.
- Back/Forward node state.
- Back/Forward edge state.
- view switch with stale selection.
- filter hides selected node.
- filter hides selected edge endpoint.
- active prerequisite Overlay.
- unavailable `NO_DATA`.
- unavailable `NO_AUTHORITY`.
- no canonical scope.
- no eligible relation in current View.
- no prerequisite Path.
- Path cycle.
- Path fork/join.
- Graph isolated node.
- dense graph collision case.
- Canvas pointer movement.
- Canvas keyboard movement.
- Canvas selection + Overlay simultaneously.
- long mixed Arabic/English labels.
- long technical IDs.
- ~1024 context drawer.
- local Path scroller.
- no document-level horizontal overflow.

## 4. Saved Map conditional matrix

**Do not execute unless authority explicitly authorizes Saved Map.**

If authorized later, then evidence must include:
- authorized owner create/list/load/save/save-as.
- unauthorized actor negative access with no metadata leak.
- not found.
- stale world/recipe/membership mismatch.
- visual_positions persistence round-trip.
- default View restoration.
- canonical Curriculum placement/relationship invariance.

If not authorized, acceptance evidence must simply show truthful `UNSAVED_PROJECTION` and absence of fake persistence.

## 5. Responsive acceptance

At ~1024:
- TOP remains discoverable.
- LEFT/CENTER remain usable.
- RIGHT content is reachable through the governed context toggle/drawer.
- Graph/Canvas controls do not collapse into inaccessible overflow.
- Path scrolling remains local to the Path surface.
- no accidental document-level horizontal overflow.
- no hidden selected state.
- mixed Arabic/English labels wrap without reversing IDs.

## 6. RTL / Bidi / accessibility

For every mode:
- Arabic natural text = RTL/auto.
- IDs, URLs, relation tokens, hashes, x/y = LTR-isolated.
- semantic arrows remain `from → to`, unaffected by page RTL.
- visible keyboard focus on View/filter/Overlay/node/edge/disclosure controls.
- Tree disclosure reports expanded/collapsed state.
- Path relation buttons have meaningful accessible names.
- Graph edge hit targets remain keyboard selectable.
- Canvas node movement has keyboard alternative distinct from camera pan.
- status notices do not steal focus.
- unavailable Overlay reason is discoverable.

## 7. Browser/runtime health

Each final evidence run must bind:
- repository.
- branch/ref.
- exact full SHA.
- route.
- viewport.
- fixture/profile identity.
- active Map/View/Filter/Overlay/Selection state.
- timestamp/run ID.
- console material errors.
- page errors.
- network failures.
- no `DataCloneError`.
- no render/hydration mismatch.

## 8. Acceptance rule

A source-level pass is not sufficient for screenshot closure. A screenshot is not sufficient for semantic provenance. Final acceptance requires both:
`governed source truth + exact runtime evidence`.

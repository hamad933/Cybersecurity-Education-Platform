PROJECT: CEP — ROUTE: PERSONAL:CEP — W02
SURFACE: Library + Unified Editor
PACKET TYPE: STATIC WRITER EXECUTION PACKET
STATUS: STATIC_ONLY__READY_FOR_CONTROLLER_B_ADJUDICATION__NOT_DISPATCHED
PRODUCT MUTATION BY THIS REVIEW: NONE
WRITER DISPATCH BY THIS REVIEW: NONE

# 07 — Static Writer Execution Packet

## 1. Objective
After Controller B adjudication and explicit dispatch only, perform the minimum bounded Library + Unified Editor correction needed to:
1. eliminate silent stale-recovery deletion and make recovery storage failures explicit;
2. remove the duplicate Library-to-RQ deep link and restore bounded Library-local return state;
3. integrate authoritative human hierarchy labels only after Controller B supplies the correct shared read owner;
4. correct the link/reference modal focus lifecycle;
5. produce the exact browser/reference evidence required to close remaining Library/Editor findings under `PORT-METHOD-032`.

The writer is not authorized by this static packet alone.

## 2. Exact baseline
- Repository: `hamad933/Cybersecurity-Education-Platform`.
- Branch: `work/cep-w02-library-work-visual-r01`.
- Required start SHA: `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`.
- Required parent: `7fa8714dc6d0beec6ec77ba8a673140301b066cf`.
- Branch was re-read directly during second-pass assurance and still resolved to the required SHA.
- Stop immediately with `BASELINE_DRIFT` if the branch/ref differs at dispatch time. Controller B must then issue a new baseline; the writer must not silently rebase or reinterpret this packet.

## 3. Governing references / authority IDs
Writer must directly inspect these originals before material visual/frontend mutation:
- START_HERE: `1M5g8nVOIeT5zs8vD6RlqS9rkRAxABoK4tVg8vjCXxG4`.
- Root surface-review topology: `1ZssuF0Y93SUZaLb1DCLcoDZsR6pqGRnx8cl5q63FEdQ`.
- Approved PRD: `1bTmKuLGWJ9JnLmEkP0a5E1_M2p1cGaoV`.
- Approved Visual & Interaction Contract: `1hJQzFnwN1VNtbAJi3wiAtBQy07IxLD1P`.
- Current Visual Reference Register: `1l97eSpCZ0tsNGDgEhHXmiyjhoCgpuEz4`.
- Correction overlays: `1jevDUhpsOePe0L4-WdZG-lMTtY5t_yW-`.
- `PORT-METHOD-032`: `1h3jPYDojdZd8N2q2wdbQUXUmFS_XlyJq`.
- W02 staged convergence model: `1NEBfWyOlGLmR6AZjlQDJkOt4fx_6iTRn`.
- W02 Master Plan v2: `1kEsr5kxuBR9diQOoH_YLOuWZ7fWFfIQR`.
- Historical Library independent review v3: `18uy33xh5QC1ZGYhXXu7Zrbb94gzVLg6Z`.
- Governed Library visual reference: `1-1EUeL56tcRKUOFDaLa-1Aey6zABnXPJ`, `OWNER_CONFIRMED_FINAL_REFERENCE`, SHA-256 `6976f17f84d8eae1d9bb0a98bcb07932f20aa8c6fa92793055a38bab15f4b1da`.

## 4. Current exact-candidate screenshot / evidence IDs
- 1440 Library primary: `1pyYAIfWkuTHc1Zsddria0xOgVpJ5YgQs`.
- 1440 Library Editor AR: `1D5GbUPDpJeoeinV9plYuy1FnEd57XK_U`.
- Current post-publication evidence folder: `1PFzCmQakLV9-M-O5HuOIvsBMX6iRYIe6`.
- Current assurance manifest: `1OuWjw1dwNr_O5Sf6dWa0vnqKBUmmlwXg` (`CLEAN_EXACT_SHA`, but the initial Unified Editor interaction trace is recorded as `UNINDUCIBLE`; this is not visual acceptance).

## 5. Reference-binding receipt required from writer
Before material mutation, return/preserve a compact receipt containing:
`surface + exact_base_sha + reference_id/hash/authority_class + correction_overlay_id + current_candidate_evidence_ids + route/state/theme/direction/viewport + writeScope + shared_collision_boundary`.

## 6. Finding ledger and full quality-gate binding

### `LIB-01 — PARTIAL — P1 workspace continuity / P2 duplicate action`
- **Evidence:** `Library.vue` local refs; `KnowledgeTabs::withObject()` preserves only `object`; two adjacent identical RQ links in Sources lens; `CepWorkspaceLayout` already persists panel geometry.
- **Authority/reference basis:** approved PRD/Visual Contract require useful back-navigation state and one authoritative action/information location.
- **User consequence:** return to the same KU can lose task/lens/scroll context; duplicate action hierarchy adds noise and ambiguity.
- **Root cause:** Library task state is not persisted under an object/revision-compatible namespace; duplicated template markup renders the same RQ link twice.
- **Exact files/symbols:** `resources/js/pages/KnowledgeLearning/Library.vue` (`searchQuery`, `lens`, `shelfOpen`, `shelfTab`, `compareRevisionId`, scroll/active-block state; Sources lens duplicate link blocks); `resources/js/pages/KnowledgeLearning/components/KnowledgeTabs.vue::withObject` is shared and reserved unless Controller B assigns ownership.
- **Target design/behavior:** one RQ deep link; bounded Library-local state survives leave/return when compatible with URL/canonical selection; URL truth always wins over stale local state.
- **Write owner:** Library writer for local state/duplicate action; Controller-B-assigned shared owner for any cross-surface tab registry.
- **Dependencies:** `SD-02` for cross-surface registry; current shared shell remains read dependency.
- **Prohibited shortcuts:** do not change Learn/Visualize/RQ semantics; do not overwrite canonical `object/revision` from stale storage; do not mutate `KnowledgeTabs` without shared ownership.
- **Validation/tests:** duplicate-link count=1; local-state namespace/compatibility tests; leave/return browser trace.
- **Required visual evidence:** 1440 Sources lens; 1440/1024 leave/return state; 1024 RIGHT closed/open.
- **Closure criterion:** one deep link only; authorized Library-local task state restores deterministically without overriding canonical route truth; shared cross-surface portion is either implemented by its assigned owner or explicitly remains a handed-off dependency.

### `LIB-02 — PARTIAL — P0 recovery integrity`
- **Evidence:** `Library.vue::submitRevision()` uses `submittedSnapshot` and queues later edits; `loadRecovery()` deletes parsed records on lock mismatch; storage calls are direct and unguarded.
- **Authority/reference basis:** approved Unified Editor requires autosave/recovery/history integrity; preservation rules prohibit silent loss; visual contract requires clear state.
- **User consequence:** a recoverable local draft can disappear exactly when server lock advancement makes conflict inspection necessary; storage exceptions can break recovery-state truthfulness.
- **Root cause:** recovery model is binary valid/garbage instead of typed current/stale-conflict/invalid/storage-error; storage API is not failure-bounded.
- **Exact files/symbols:** `Library.vue::{recoveryKey,persistRecovery,loadRecovery,removeRecovery,submitRevision,recoverDraft,discardRecovery}`; optional new Library-owned composable under `resources/js/pages/KnowledgeLearning/composables/` if extraction reduces state complexity.
- **Target design/behavior:** versioned recovery record with revision/base lock/base digest where available; typed states `none/current/stale_conflict/invalid/storage_error`; no silent stale-record deletion; explicit discard; exact submitted-snapshot ACK preserved.
- **Write owner:** Library/Unified Editor writer.
- **Dependencies:** optional Controller-B decision on actor/session recovery namespace; no dependency is allowed to weaken the minimum P0 conflict-safe behavior.
- **Prohibited shortcuts:** no optimistic-lock bypass; no automatic merge; no published-revision mutation; no canonical schema/database migration solely for local recovery; no deleting stale valid recovery on load.
- **Validation/tests:** delayed save + new edit; stale lock reload; explicit discard; invalid record; storage get/set/remove failure; save ACK never marks newer local snapshot saved.
- **Required visual/runtime evidence:** Recovery current; Recovery stale conflict; save-latency trace; reload trace.
- **Closure criterion:** no false saved state; no syntactically valid recovery snapshot is silently lost; storage failure is visible/non-fatal; stale conflict requires explicit user decision.

### `LIB-03 — SATISFIED — no remediation write`
- **Evidence:** Contract V2 stable IDs, server normalization/validation, restore-as-new-draft, exact-ID compare, conservative legacy compare, tests.
- **Authority/reference basis:** revision/history integrity requirements.
- **Closure:** preserve current implementation and regression tests; do not rewrite published history.

### `LIB-04 — PARTIAL — evidence-only unless a runtime defect is induced`
- **Evidence:** static Bidi tests and logical direction primitives exist; exact runtime caret/selection/IME evidence is missing and prior trace was `UNINDUCIBLE`.
- **Authority/reference basis:** Arabic-first + mixed RTL/LTR correctness in PRD/Visual Contract and W02 convergence model.
- **User consequence if defective:** cursor movement, selection, deletion or inserted formatting could corrupt logical editing order.
- **Root cause:** `NOT_YET_PROVEN`; no runtime product defect may be invented from missing evidence.
- **Exact files/symbols under observation:** `Library.vue` textarea/editor operations; `LessonContentRenderer.vue`; direction/Bidi CSS; no write unless a reproducible defect is found.
- **Target behavior:** logical text preserved through typing, selection, Home/End, Delete/Backspace, inline formatting, link/reference insertion, undo/redo and reload.
- **Write owner:** Library/Editor only if a bounded reproduced defect is proven; otherwise evidence executor/reviewer only.
- **Dependencies:** browser/runtime capable of inducing the matrix.
- **Prohibited shortcuts:** no preemptive bidi rewrite without failure; no canonical-content mutation for test strings.
- **Validation/tests:** existing Bidi suites + browser matrix.
- **Required visual/runtime evidence:** mixed Arabic/English/code/request/response; caret/selection/Home/End/Delete/Backspace; IME/direction switching.
- **Closure criterion:** required browser matrix passes on exact candidate, or any induced defect is corrected/reverified before closure.

### `LIB-05 — PARTIAL — P1 SHARED_DEPENDENCY`
- **Evidence:** exact candidate reports parent hierarchy unavailable; `KnowledgeLearningWorkspace::hierarchyContexts()` can populate title fields using IDs.
- **Authority/reference basis:** canonical hierarchy `Domain → Capability Cluster → Capability → Knowledge Unit`; visible semantic labels must be truthful; Reference requires human hierarchy.
- **User consequence:** users cannot reliably understand placement if parent names are missing; ID-as-title would misrepresent technical identifiers as human labels.
- **Root cause:** no proven current authoritative human-label read contract in the Library surface path.
- **Exact files/symbols:** `app/Application/KnowledgeLearning/KnowledgeLearningWorkspace.php::hierarchyContexts`; `app/Modules/Knowledge/Application/Library/LibraryHierarchyProjector.php::normalizeContext`; hierarchy UI is a consumer.
- **Target behavior:** human labels come from one authoritative shared read projection; IDs remain secondary LTR atoms; unresolved stays truthful when authority is absent.
- **Write owner:** Controller-B-assigned integration owner; Library may consume only after ownership decision.
- **Dependencies:** `SD-01`; current Curriculum/Knowledge integration authority.
- **Prohibited shortcuts:** no synthetic translation; no hard-coded taxonomy; no Curriculum canonical mutation; no ID-as-title fallback.
- **Validation/tests:** hierarchy label input vs unresolved fallback; same truth in LEFT/CENTER/RIGHT.
- **Required visual evidence:** 1440 + 1024 Library showing human hierarchy or truthful unresolved state according to the approved integration outcome.
- **Closure criterion:** approved labels are projected consistently, or Controller B explicitly defers the dependency while preserving truthful unresolved UI.

### `LIB-06 — PARTIAL — implementation present, visual proof open`
- **Evidence:** semantic heading folding, chevrons, auto-expand and tests exist.
- **Authority/reference basis:** document-dominant/progressive-disclosure Library reference and contract.
- **User consequence:** without representative proof, long-document scanability and editor density are not established on the exact candidate.
- **Root cause:** evidence incompleteness; no additional product defect proven.
- **Exact files/symbols:** `Library.vue::{collapsedEditorSections,toggleEditorSection,isEditorBlockVisible}` and editor block template.
- **Target behavior:** major sections are scannable; focused section expands; folding never alters canonical content.
- **Write owner:** none unless browser proof induces a material defect.
- **Dependencies:** representative authorized acceptance corpus/evidence environment.
- **Prohibited shortcuts:** no canonical content truncation; no data mutation for screenshot mimicry.
- **Validation/tests:** retain static folding tests; long-document browser review.
- **Required visual evidence:** long mixed-content dark 1440 and 1024, collapsed/expanded.
- **Closure criterion:** representative exact-candidate document demonstrates reference-consistent dominance/density/folding.

### `LIB-07 — PARTIAL — bounded provenance implementation present, visual proof open`
- **Evidence:** `KnowledgeLearningWorkspace::librarySourceProjection()` strips broad metadata/digests and emits bounded source/claim context; Library Sources lens renders it.
- **Authority/reference basis:** RIGHT is unique context only; RQ owns deeper provenance/reconciliation.
- **User consequence:** without Sources-lens proof, usefulness and non-duplication of Library provenance cannot be visually adjudicated.
- **Root cause:** evidence incompleteness plus duplicate RQ deep link already bound to `LIB-01`.
- **Exact files/symbols:** `KnowledgeLearningWorkspace::librarySourceProjection`; `Library.vue` Sources lens.
- **Target behavior:** compact substantive source summaries, bounded payload, one optional RQ handoff.
- **Write owner:** Library only for duplicate action/UI; backend projection remains unchanged unless a proven bounded defect appears.
- **Dependencies:** RQ remains foreign surface; no RQ mutation.
- **Prohibited shortcuts:** no broad metadata serialization; no RQ workbench duplication; no invented source anchors.
- **Validation/tests:** serialized prop census + Sources render test + duplicate link count=1.
- **Required visual evidence:** dark 1440 Sources lens; dark 1024 context open if relevant.
- **Closure criterion:** bounded payload and useful source context are proven on exact candidate with one RQ handoff and no duplicated workbench.

### `LIB-08 — PARTIAL — History implementation present, browser proof open`
- **Evidence:** `shelfTab=history|compare|diagnostics`; TOP openers; independent History timeline; static test task separation.
- **Authority/reference basis:** Unified Editor requires revision history, compare and restore-as-new-revision; BOTTOM is temporary deep workspace.
- **User consequence:** without exact browser proof, task separation/discoverability and 1024 usability are not visually closed.
- **Root cause:** evidence incompleteness; no current code defect proven in task separation.
- **Exact files/symbols:** `Library.vue::{shelfOpen,shelfTab,openShelf,prepareComparison,loadComparison,restoreRevision}` + BOTTOM template.
- **Target behavior:** History inspect is independent; Compare loads only on explicit action; Restore creates new draft; Recovery diagnostics separate.
- **Write owner:** none unless exact browser evidence exposes a defect.
- **Dependencies:** correct exact-candidate runtime/evidence environment.
- **Prohibited shortcuts:** no published-history mutation; no auto-compare on History selection; no permanent 1024 third row.
- **Validation/tests:** retain static task tests + browser open-state matrix.
- **Required visual evidence:** History open, Compare open, Recovery open at 1440; BOTTOM task at 1024.
- **Closure criterion:** exact-candidate browser states prove independent tasks, safe restore boundary and usable responsive deep workspace.

### `LIB-09 — NEW CURRENT_DEFECT — P2 accessibility`
- **Evidence:** modal open focuses `editor-dialog-value`; close clears dialog state but no invoker restoration/focus containment is implemented.
- **Authority/reference basis:** accessible keyboard continuity and unified same-surface editing.
- **User consequence:** keyboard focus can leave editing context after Cancel/Escape and can escape the modal while open.
- **Root cause:** dialog lifecycle lacks invoker reference and bounded Tab cycle.
- **Exact files/symbols:** `Library.vue::{openEditorDialog,closeEditorDialog,applyEditorDialog}` + dialog template.
- **Target behavior:** capture invoker; focus input; Tab/Shift+Tab contained; Escape/Cancel close; deterministic focus return; submit restores edited selection/context.
- **Write owner:** Library/Editor writer.
- **Dependencies:** none beyond existing editor state.
- **Prohibited shortcuts:** no global focus hijack; no mouse-only behavior; no new external modal framework unless separately justified.
- **Validation/tests:** keyboard unit/component tests + browser trace.
- **Required visual/runtime evidence:** keyboard focus trace for open, cycle, Cancel/Escape, submit.
- **Closure criterion:** deterministic keyboard focus lifecycle with no context loss.

## 7. Exact / derived writeScope after Controller B dispatch
### Direct Library-owned paths
- `resources/js/pages/KnowledgeLearning/Library.vue`.
- Optional new Library-owned composable under `resources/js/pages/KnowledgeLearning/composables/` for recovery/local workspace state if Controller B accepts extraction.
- Targeted Library/editor tests under `resources/js/tests/`.

### Conditional shared/integration paths — NOT writable until Controller B explicitly assigns ownership
- `app/Application/KnowledgeLearning/KnowledgeLearningWorkspace.php` for authoritative hierarchy projection integration only after `SD-01` is resolved.
- `app/Modules/Knowledge/Application/Library/LibraryHierarchyProjector.php` only if the approved read-contract shape requires an adapter change.
- `resources/js/pages/KnowledgeLearning/components/KnowledgeTabs.vue` only if Controller B assigns the shared cross-surface return-state writer.

### Read dependencies / reserved by default
- `resources/js/layouts/CepWorkspaceLayout.vue`.
- `resources/js/components/shared/CepTemporaryWorkspace.vue`.
- `resources/css/app.css`.
These are not proposed write paths. Current CEP state records a W01↔W02 shared-frame collision boundary; any later shared-shell mutation requires Parent/Controller-B coordination and a newly bounded write contract.

## 8. Prohibited scope
- No Learn, Visualize or RQ product implementation.
- No canonical Curriculum mutation.
- No published revision rewrite.
- No database/schema migration solely for local recovery or screenshots.
- No production data import to make screenshots resemble the reference.
- No filter/tag/header feature without explicit authority.
- No optimistic-lock bypass or automatic conflict merge.
- No branch creation/commit/push/PR/publication unless separately authorized by Controller B/Parent at dispatch/publication gates.
- No merge, acceptance, release or deploy.

## 9. Target design / behavior summary
- TOP: editor task identity + History/Compare/Recovery + Undo/Redo + save state + explicit Save.
- LEFT: search + authoritative hierarchy only.
- CENTER: canonical KU document and same-surface editor; dominant and progressively disclosed.
- RIGHT: unique Library context + bounded provenance; one RQ handoff.
- BOTTOM: temporary History/Compare/Recovery deep work, closed by default.
- 1024: LEFT+CENTER stable; RIGHT on demand; no permanent third row/overflow.
- Recovery: typed, non-destructive conflict handling.
- Bidi: logical order preserved; technical atoms isolated LTR.
- Accessibility: modal focus contained and returned.

## 10. Validation / tests
Retain/re-run relevant existing suites and add targeted regression coverage for:
- stale recovery survives lock mismatch;
- storage get/set/remove failure;
- delayed save + subsequent local edit never reports false saved;
- explicit recovery discard;
- local state namespace/compatibility;
- duplicate RQ link count=1;
- modal focus return/Tab containment;
- hierarchy labels vs truthful unresolved fallback;
- Bidi logical order + browser interaction matrix;
- no published-history mutation / stable V2 identity regression.

Run the repository's applicable JS test/lint and PHP test gates. A green suite is necessary evidence, not visual acceptance.

## 11. Required browser states / screenshots
All exact-candidate evidence must record SHA, route/state, theme, direction, viewport and artifact identity:
1. dark 1440 Library primary after correction;
2. dark 1440 Sources lens;
3. dark 1440 representative long mixed-content editor, folded and expanded;
4. History open;
5. Compare open;
6. Recovery current;
7. Recovery stale conflict;
8. dark 1024 Library with RIGHT closed;
9. dark 1024 RIGHT open;
10. dark 1024 BOTTOM task open;
11. mixed RTL/LTR visual state;
12. caret/selection/Home/End/Delete/Backspace/IME trace;
13. modal keyboard focus trace;
14. save-latency + reload/recovery-conflict trace;
15. leave/return state continuity trace.

## 12. `PORT-METHOD-032` visual closure gate
- Writer must directly inspect governed reference originals and correction overlays.
- Preserve `REFERENCE_BINDING_RECEIPT`.
- Establish/identify a healthy representative first-render baseline.
- Compare latest exact candidate directly against the governed Library reference.
- Update `03_REFERENCE_DELTA_LEDGER.md` semantics on the writer candidate; do not drop material deltas because tests pass.
- Writer may declare review-ready only when no unresolved writer-owned material `CURRENT_DEFECT` remains; authority/shared dependencies must be explicitly handed off.
- Independent reviewer and Controller B must repeat direct original-evidence comparison before freeze recommendation.

## 13. Evidence handoff
Writer returns evidence to Controller B; it does not self-accept. Handoff must include:
- exact start and resulting local/remote candidate SHA as authorized;
- parent SHA and changed-path census;
- exact diff/writeScope confirmation;
- tests/lint results with commands and exit status;
- `REFERENCE_BINDING_RECEIPT`;
- governed reference ID/hash + correction overlay IDs;
- all required browser artifact IDs/hashes/viewport-state metadata;
- updated Reference Delta Ledger dispositions;
- unresolved `SHARED_DEPENDENCY` / authority decisions;
- explicit statement of no foreign-surface/shared-path mutation outside assigned ownership;
- writer Stop Gate.

Controller B chooses the actual governed evidence destination/publication mechanism at dispatch time; this static packet does not authorize product publication or evidence acceptance.

## 14. Dependencies / collision handoff
- `SD-01`: authoritative human hierarchy-label owner.
- `SD-02`: shared cross-surface return-state registry owner, if required.
- `SD-03`: shared shell paths remain read-only; current W01↔W02 collision must be respected.
- `SD-04`: RQ workbench remains out of Library ownership.
- `SD-05`: exact browser/evidence environment must induce required states.
- Optional authority: recovery actor/session namespace.

## 15. Writer Stop Gate
Writer stops after bounded implementation + exact validation/evidence handoff. It must not accept, freeze product surface, merge, release or deploy.

STATIC_PACKET_STOP_GATE=READY_FOR_CONTROLLER_B_ADJUDICATION__NOT_DISPATCHED__NO_PRODUCT_MUTATION_BY_REVIEWER

When later dispatched, the writer's terminal product action is evidence handoff only; Controller B retains adjudication and any later freeze recommendation authority.

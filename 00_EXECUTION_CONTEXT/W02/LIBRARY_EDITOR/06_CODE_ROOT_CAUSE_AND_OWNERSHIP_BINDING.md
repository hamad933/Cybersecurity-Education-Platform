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

# 05 — Code Root Cause and Ownership Binding

## Proven root-cause bindings
| Root | Findings | Exact-SHA path/symbol | Confidence | Owner |
|---|---|---|---|---|
| RC-01 destructive stale recovery | DA-001 | `Library.vue::loadRecovery/removeRecovery` | `PROVEN` | Library/Editor |
| RC-02 unbounded storage failure | DA-002 | `Library.vue::persistRecovery/loadRecovery/removeRecovery` | `PROVEN` | Library/Editor |
| RC-03 incomplete return state | DA-003 | `Library.vue` refs + `KnowledgeTabs.vue::withObject` | `PROVEN` | Library + shared nav |
| RC-04 search semantic scope | DA-006 | `Library.vue::filterItems/filteredStructure` | `PROVEN` | Library |
| RC-05 fixed dead-space reservoir | DA-008 | `.library-document-body { min-height:32rem }` | `PROVEN` | Library |
| RC-06 nested framed work planes | DA-007/010/011/014 | `.cep-primary-surface` + `.library-document` + cumulative padding | `PROVEN` | Library + shared shell |
| RC-07 global responsive wrap | DA-025/039/040 | `app.css` media queries + medium grid | `PROVEN` | shared shell |
| RC-08 editor toolbar hierarchy | DA-027/028/030/031 | `Library.vue` toolbar/editor-tool markup/styles | `PROVEN` | Library/Editor |
| RC-09 hierarchy label authority | DA-033/062 | `KnowledgeLearningWorkspace::hierarchyContexts`, `LibraryHierarchyProjector` | `PROVEN` | shared hierarchy read model |
| RC-10 emoji visual language | DA-022/023 | `KnowledgeTabs.vue`, `LibraryHierarchyTree.vue` | `PROVEN` | shared W02 design system |
| RC-11 RIGHT bounded projection | DA-035/037/054 | `librarySourceProjection()` + Library RIGHT lenses | `PROVEN` | Library + RQ boundary |
| RC-12 local/test acceptance boundary | DA-055/056/061/063 | `W02AcceptanceSeeder.php`, `DatabaseSeeder.php`, `Vs003Seeder.php` | `PROVEN`/`PROVEN_PARTIAL` | data/runtime authority |
| RC-13 hard-coded Arabic shell | DA-045 | `CepWorkspaceLayout.vue` root `lang="ar" dir="rtl"` | `PROVEN` | shared shell / locale authority |
| RC-14 evidence-label duplication | DA-044 | final exact-candidate freeze manifest AR/EN hashes | `PROVEN` | evidence pipeline |
| RC-15 Markdown table semantic flattening | DA-064 | `W02AcceptanceSeeder::markdownBlocks` table transform + `LessonContentContract::BLOCK_REGISTRY` + `LessonContentRenderer.vue` | `PROVEN` | content contract + acceptance adapter + shared renderer |
| RC-16 Markdown list semantic flattening | DA-065 | `W02AcceptanceSeeder::markdownBlocks` list transform + `LessonContentContract::BLOCK_REGISTRY` + `LessonContentRenderer.vue` | `PROVEN` | content contract + acceptance adapter + shared renderer |

## Root causes not yet proven
The following must remain explicitly `NOT_YET_PROVEN` until runtime evidence exists:
- exact database/seed path that produced `Test KU 1..6`;
- why `KU-D03-0001` is paired with `VS3-AUTH-001` in the reviewed screenshot;
- production canonical B09 import/projection owner;
- exact reason any future normal hierarchy may still miss human labels after shared authority is supplied;
- browser-only defects in hover/focus/IME or 1024 RIGHT overlay composition.

## Non-material source hygiene retained, not promoted
The source contains two consecutive `watch(activeBlockIndex, ...)` handlers with the same ancestor-auto-expand intent. The prior review correctly treated this as non-material source hygiene because the second execution is effectively idempotent after the first. It is **not** counted among the 63 material findings and must not widen scope by itself.

## Verified non-root-causes
Do not reopen these without new direct evidence:
- nested-workspace ownership was corrected by `CepWorkspaceLayout`;
- V2 block identity is implemented;
- History and Compare are distinct tasks;
- Library provenance payload is intentionally narrowed before serialization;
- medium 1024 RIGHT default collapse is allowed responsive compaction, not itself a defect.

## Ownership law
A proven root cause does not grant write authority. This document is audit evidence only. Shared-shell, shared-hierarchy, data/runtime and RQ-boundary roots require Controller B adjudication before any future remediation packet is authored.

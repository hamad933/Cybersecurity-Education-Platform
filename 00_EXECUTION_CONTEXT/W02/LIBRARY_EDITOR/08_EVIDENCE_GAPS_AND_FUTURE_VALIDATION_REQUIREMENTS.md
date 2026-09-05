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

# 07 — Evidence Gaps and Future Validation Requirements

## Evidence rule
`EVIDENCE_INSUFFICIENT` لا يعني defect proven ولا PASS. لا يُغلق finding باختبار unit/static فقط عندما يكون المطلوب browser/visual/runtime evidence.

## Exact evidence already available
- Owner reference original: `1-1EUeL56tcRKUOFDaLa-1Aey6zABnXPJ`
- 1440 Library primary: `1pyYAIfWkuTHc1Zsddria0xOgVpJ5YgQs`
- 1440 Unified Editor AR: `1D5GbUPDpJeoeinV9plYuy1FnEd57XK_U`
- 1024 Library evidence: `1HRvqqX7ay4n8q__PX6Z1lJUwFZc47vKa`
- final freeze manifest with AR/EN hash duplication: `1KpivulqWabpfB2T_a0KDY4wUX6u9HUQ_`

The prior package’s statement that an exact dark 1024 Library primary was missing is therefore no longer a complete current statement. Newer exact-candidate 1024 evidence exists.

## Required future evidence matrix
| Finding | Required state | Viewport | Proof |
|---|---|---|---|
| `W02-DA-032` | State-matrix capture session on exact SHA. | 1440 + 1024 | Annotated evidence set of focus/hover/disabled states. |
| `W02-DA-034` | Normal multi-domain hierarchy and a separate unresolved test case. | 1440 + 1024 | Normal + exception LEFT evidence pair. |
| `W02-DA-037` | Representative active KU with ≥4 sources and mixed claim assessments. | 1440 + 1024 | Sources lens 1440 and 1024 RIGHT-open. |
| `W02-DA-041` | 1024 Library with RIGHT overlay opened, long Sources/Overview content. | 1024 | RIGHT-open and RIGHT-closed exact pair. |
| `W02-DA-043` | Editable mixed RTL/LTR paragraph and technical code block. | 1440 + 1024 | Representative selection/caret states plus recorded interaction trace. |
| `W02-DA-044` | AR state and, only if governed, independent EN state. | 1440 + 1024 | Distinct evidence or a documented single-locale scope. |
| `W02-DA-045` | Governed locale modes only. | all | Evidence matching the approved locale scope. |
| `W02-DA-046` | 1024 editable block with selected text, Link and Citation modal error/success. | 1024 | 1024 modal normal/error states. |
| `W02-DA-057` | BOTTOM History open at 1440 and 1024. | 1440 + 1024 | History open full-width deep workspace. |
| `W02-DA-058` | BOTTOM Compare open at 1440 and 1024. | 1440 + 1024 | Compare normal, loading and error states. |
| `W02-DA-059` | BOTTOM Recovery open through each state. | 1440 + 1024 | State-specific recovery evidence. |
| `W02-DA-060` | Empty catalog; no revision; invalid object; invalid revision; save validation error; unavailable source/context. | 1440 + 1024 | One exact screenshot per material non-happy state at primary viewport; 1024 for layout-sensitive states. |
| `W02-DA-064` | Representative multi-column B09-shaped table through view/edit/save round-trip. | 1440 + 1024 | Screenshot pair + DOM/accessibility structure + persisted-block receipt proving row/column semantics are preserved. |
| `W02-DA-065` | Representative flat and nested B09-shaped lists with Arabic/LTR tokens. | 1440 + 1024 | Screenshot pair + keyboard/accessibility structure + persisted-block receipt proving list item/hierarchy semantics are preserved. |

## Additional mandatory stress states
1. Representative long B09-shaped document: top/middle/bottom positions.
2. Heading/prose/code/callout/rules/boundaries mix, plus semantic table and flat/nested list cases.
3. Search by KU and by parent hierarchy semantic labels after authority exists.
4. LEFT normal hierarchy + unresolved exception.
5. RIGHT Overview and Sources with realistic cardinalities.
6. RIGHT-open overlay at 1024, with long content and keyboard focus.
7. History/Compare/Recovery open states at 1440 and 1024.
8. Link and Citation modal success/error states.
9. Dirty/saving/saved/save-error command states.
10. Recovery stale-conflict and storage-failure states after implementation exists.
11. Bidi caret/selection/Home/End/Delete/IME on Arabic prose, English tokens, URLs, IDs and code.
12. Empty catalog / no revision / invalid object / invalid revision / unavailable source context.
13. 100%, 125%, and 200% zoom for metadata/control legibility.
14. Pointer hover + keyboard focus-visible for every primary action class.

## Locale evidence correction
AR/EN Library images are byte-identical at both primary tested widths. Future evidence must either:
- capture genuinely distinct governed locale states; or
- explicitly say that the product scope is Arabic-first with LTR technical islands and stop presenting duplicate pixels as EN coverage.

## Evidence integrity requirement
Every future screenshot set should carry:
- exact Git SHA;
- viewport dimensions and zoom;
- route/query state;
- exact fixture/data receipt ID/hash;
- browser/engine;
- selected KU/revision;
- open lens/deep-work state;
- relevant localStorage reset/persistence state.

Without this, visual evidence can be exact-SHA but still data-state ambiguous.

# CEP W02 Learn — Evidence Gaps and Future Validation Requirements

- Project: CEP / W02 Learn
- Mode: READ-ONLY DEEP AUDIT / DISCOVERY ONLY
- Candidate: `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- Parent: `7fa8714dc6d0beec6ec77ba8a673140301b066cf`
- Baseline drift: `NO_BASELINE_DRIFT`
- Date: 2026-09-04


## Evidence gaps
### E01 — ~1024 exact-candidate Learn
Current status: `EVIDENCE_INSUFFICIENT`.
Required: Dark ~1024, LEFT usable + dominant CENTER, RIGHT closed by default and same RIGHT owner on demand, no stacked context wall, no horizontal overflow, shared BOTTOM closed.

### E02 — representative rich runtime state
Current status: `EVIDENCE_INSUFFICIENT`.
Required deterministic state: at least three semantic lesson sections separated by paragraph/code/callout blocks, two Practices where selected A differs from backend recommended B, objective, prerequisite, at least two sources with authority/review state, Assessment unavailable/preview, Lab reference/non-executable, meaningful pathway context.

### E03 — dynamic interaction/accessibility
Required traces: keyboard journey selection; focus transfer after explicit activity selection; no focus theft during passive scroll; hover/focus/disabled states; named progressbar; expanded shared BOTTOM; context toggle identity; natural-scroll → storage → reload → viewport restore; account/session isolation.

## Required screenshot states
1. `learn_1440_dark_lesson_rich.png`
2. `learn_1440_dark_practice.png`
3. `learn_1440_dark_assessment_unavailable.png`
4. `learn_1440_dark_lab_preview.png`
5. `learn_1440_dark_bottom_open_context.png`
6. `learn_1024_dark_context_closed.png`
7. `learn_1024_dark_context_open_same_owner.png`
8. `learn_1024_dark_practice.png`
9. `learn_multi_placement_or_ambiguous_context.png`
10. `learn_resume_before_reload.png`
11. `learn_resume_after_reload.png`
12. `learn_bidi_rich_mixed_content.png`

## Required runtime assertions
- Branch/SHA identity exactly bound to every capture.
- One semantic section owner in LEFT.
- `selectedActivity === visible CENTER owner`.
- `selectedActivity != recommendedNext` when backend returns another Practice.
- Source authority/review state is visible where governed.
- No raw English diagnostic copy in Arabic product state.
- No Completion → Mastery mutation.
- No Assessment/Lab executable CTA without authoritative executable contract.
- 1024 `scrollWidth <= clientWidth` at the intended viewport.
- Same RIGHT DOM/owner is used when toggled at medium viewport.
- Progressbar has a localized accessible name and `aria-valuetext`, and its denominator is semantic-section progress rather than raw block count.
- Select Practice B → Visualize/RQ → Back/return restores Practice B while preserving an independent lesson reading anchor.
- Practice ordering and backend `journey.next` match the authority-approved pedagogical sequence, including a fixture where lexical ID order differs from intended sequence.
- TOP/RIGHT/CENTER action hierarchy is visually unambiguous: resume/current action, contextual quick access and activity controls do not compete.

## Evidence classification rule
No future screenshot alone closes a finding. Closure requires the corresponding contract/state assertion, exact candidate identity, DOM/runtime behavior, and screenshot to agree.

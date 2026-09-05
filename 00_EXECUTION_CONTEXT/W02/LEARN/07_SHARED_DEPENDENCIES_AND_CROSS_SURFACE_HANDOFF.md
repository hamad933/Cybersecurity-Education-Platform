# CEP W02 Learn — Shared Dependencies and Cross-Surface Handoff

- Project: CEP / W02 Learn
- Mode: READ-ONLY DEEP AUDIT / DISCOVERY ONLY
- Candidate: `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- Parent: `7fa8714dc6d0beec6ec77ba8a673140301b066cf`
- Baseline drift: `NO_BASELINE_DRIFT`
- Date: 2026-09-04


## Shared dependency register
| Dependency | Why Learn depends on it | Current truth | Collision risk | Audit handoff |
|---|---|---|---|---|
| `CepWorkspaceLayout.vue` / `app.css` | Real TOP/LEFT/RIGHT/BOTTOM ownership and ~1024 context toggle | Capability already exists | HIGH | Prefer consumption unchanged; shared edit only if future exact evidence proves a defect. |
| `KnowledgeTabs.vue` | Cross-surface object/return continuity and icon/tab grammar | Preserves `object` only; uses Emoji icon literals | HIGH | Navigation visual system is shared; Learn transient state should not leak into all surfaces without contract. |
| `CurriculumKnowledgeService.php` | selected curriculum context, pathway order, prerequisites | placements exist; no Learn pathwayJourney contract | HIGH | A01/context/order policy required; do not use Visualize graph as completion truth. |
| `KnowledgeLibraryService.php` | published lesson availability + reason code | emits English diagnostic sentence | MEDIUM | Additive machine reason may affect other consumers; Learn presentation remains Learn-owned. |
| W03 Simulation/Enterprise | Lab executable handoff | unavailable in baseline | HIGH | Preview only; no invented href/payload. |
| Assessment authority | Attempt/result persistence | unassigned/absent | HIGH | Presentation only until authoritative contract. |
| W04 Progress/Evidence/Mastery | completion/mastery semantics | Mastery external hard boundary | HIGH | Never derive Mastery from Learn reading/practice activity. |
| Identity/session context | browser resume isolation | actorId available server-side, no scoped client key | MEDIUM | Any opaque scope must not expose secrets/tokens. |
| Canonical ingestion/runtime | full B09 coverage | 224 canonical structurally proven; runtime full import not proven | HIGH | Do not claim 224 Learn runtime integration; create separate integration evidence when governed. |

## Cross-surface consistency findings
The Learn audit finds two shared visual concerns that should not be fixed by a Learn-local fork:
1. Secondary W02 tab grammar is card/pill-heavy and uses platform-dependent Emoji. This is visible on Learn but shared with Library/Visualize/RQ.
2. Shared workspace capabilities exist and should become the one region owner; Learn-local responsive/grid behavior is a collision risk.

## Safe current handoffs
- Preserve canonical object `object=<KU id>` across surfaces.
- Keep Completion != Mastery.
- Keep Lab `executable=false` while W03 authority is absent.
- Keep Assessment non-executable while canonical persistence is absent.
- Do not rewrite shared files from this audit.

## Controller B attention
Highest collision-risk items: workspace-region ownership, curriculum context/pathway projection, shared tabs/icon grammar, W03 Lab handoff, and any future canonical importer/runtime integration.
## Additional completeness bindings
- **Selected-activity return state (`F09`)**: Learn-owned history/session state is preferred; `KnowledgeTabs` becomes a shared dependency only if an opaque return token is proven necessary. Canonical `object` identity must remain separate from transient Learn state.
- **Practice ordering (`R06`)**: lexical `practice_id` must not be promoted into pedagogical authority. Controller B/Learning authority must approve an explicit sequence source or ratify the identifier invariant before “next” is treated as pedagogical truth.
- **Progress accessibility (`V31`)**: Learn-owned; no shared-shell change is required to give the progressbar a localized accessible name/value text and semantic denominator.
- **Action/control hierarchy (`V32`)**: consume shared TOP/RIGHT ownership first. Any `KnowledgeTabs` visual/systemic change must be shared once across W02, not patched locally in Learn.


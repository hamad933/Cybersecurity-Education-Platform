# CEP W02 Learn — Code Root Cause and Ownership Binding

- Project: CEP / W02 Learn
- Mode: READ-ONLY DEEP AUDIT / DISCOVERY ONLY
- Candidate: `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- Parent: `7fa8714dc6d0beec6ec77ba8a673140301b066cf`
- Baseline drift: `NO_BASELINE_DRIFT`
- Date: 2026-09-04


## Root-cause synthesis
The dominant local root cause is not missing CSS. `Learn.vue` builds a second workstation grid inside the shared CENTER even though `CepWorkspaceLayout.vue` already exposes region slots and medium responsive behavior. This single architecture choice causes nested containment, outer dead space, disconnected TOP/BOTTOM ownership and future 1024 risk.

A second root cause is state-model fragmentation: `selectedStep`, `selectedBlockIndex`, `lessonOutline` semantic indices, loop ordinals, backend `journey.next`, lifecycle blueprints, and browser resume are not normalized into one set of explicit contracts. This causes wrong section state, selection/CENTER mismatch, recommendation conflation, resume defects and duplicated activity representations.

A third root cause is data/projection incompleteness rather than pure UI styling: first-placement lifecycle selection, lack of ordered pathway journey, intentionally absent Assessment persistence, unavailable W03 Lab handoff, and lack of full B09 runtime import proof.

## Exact code observations
- `Learn.vue`: nested grid; local LEFT/CENTER/RIGHT/BOTTOM; selectedStep; raw selectedBlockIndex; direct unavailable_reason rendering; shallow source rendering.
- `CepWorkspaceLayout.vue`: shared region owners already exist; consumption gap is primary.
- `LearningPathNode.vue`: only locked/available/completed + active; literal check/lock glyphs; no explicit aria-current.
- `ProgressIndicator.vue`: unnamed progressbar; raw current/total percentage input.
- `KnowledgeLearningWorkspace.php`: first placement lifecycle; shallow pathway context.
- `KnowledgeJourneyService.php`: persisted practice truth, journey.next, explicit Assessment gap, Lab reference-only handoff.
- `KnowledgeLibraryService.php`: persisted KU/published revision; English diagnostic reason.
- `W02AcceptanceSeeder.php`: six-KU local/test representative subset; no B09/B10 import.
- `KnowledgeTabs.vue`: object continuity only; literal Emoji icon system.

## Finding-to-root ownership table
| Finding | Root cause | Owner | Shared dependency | Collision risk |
|---|---|---|---|---|
| LRN-DA-V01 | Learn.vue يمرر primaryNavigation فقط ثم ينشئ grid محلياً؛ البنية المشتركة موجودة لكنها غير مستهلكة. | LEARN_OWNED | CepWorkspaceLayout/app.css | HIGH |
| LRN-DA-V02 | nested local grid + outer padding + card containment المتكرر. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V03 | عدم استهلاك #top في CepWorkspaceLayout. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V04 | selected activity architecture غير موحدة + fixture sparse. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V05 | مزيج من fixture sparsity ، card heights/padding ، ونقص contextual channels. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V06 | لا يوجد ordered pathway projection في Learn payload/UI. | LEARN_OWNED | Curriculum projection authority | HIGH |
| LRN-DA-V07 | إضافة component جديد بجوار القائمة القديمة بدلاً من استبدالها. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V08 | component state model فقير بالنسبة لدلالة journey. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V09 | selectedStep مخصص للممارسة ولا يوجد SelectedActivity موحد. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V10 | context.pathway shallow ، وعدم وجود current activity model أو breadcrumb projection. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V11 | objective يعتمد على lifecycle data ؛ sparse fixture لا يمده. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V12 | لا يوجد normalized readiness projection للنشاط المحدد. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V13 | backend يرسل IDs/titles منفصلة دون presentation model لحالة dependency. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V14 | Lesson ثابت كمالك CENTER و selectedStep لا يغيّر activity surface. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V15 | authoritative assessment state غير مستهلك كـ selected activity. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V16 | selectedLab لا يملك CENTER ولا يوجد activity union. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V17 | frontend context projection محدود + fixture sparse + لا توجد contextual subviews. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V18 | cross-surface links ليست في contextual owner المقصود. | LEARN_OWNED | KnowledgeTabs/return state if shared change needed | LOW |
| LRN-DA-V19 | BOTTOM model محلي ومحدود إلى activity summary/reading position. | LEARN_OWNED | Shared CepTemporaryWorkspace + evidence/curriculum projections | HIGH |
| LRN-DA-V20 | local drawer لديه toggle واحد بلا channel model. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V21 | nested shell + generous spacing + fixture sparse + missing contextual channels. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V22 | component grammar يعتمد card كحل افتراضي. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V23 | visual system local composition overuses borders. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V24 | typographic scale غير مضبوطة على dense desktop target. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V25 | default spacious utility composition rather than workstation-specific density. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V26 | الرموز معرفة كنص Unicode بدلاً من icon components. | LEARN_OWNED | Shared W02 icon/navigation system | MEDIUM |
| LRN-DA-V27 | shared tabs component uses chip/card grammar. | SHARED_W02_NAV_OWNED | KnowledgeTabs across Library/Learn/Visualize/RQ | HIGH |
| LRN-DA-V28 | local fixed inner widths + nested workspace. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V29 | الدعم الساكن موجود، لكن representative runtime Bidi evidence ناقص. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V30 | غياب evidence ديناميكي؛ ليس دليلاً على فشل كل state. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V31 | `ProgressIndicator.vue` lacks accessible name/value text and is fed raw-block progress semantics. | LEARN_OWNED | NONE | LOW |
| LRN-DA-V32 | absence of shared TOP consumption + scattered CTA placement + high-chrome shared gateways. | LEARN_OWNED | KnowledgeTabs if shared styling/return contract changes | MEDIUM |
| LRN-DA-F01 | خلط loop ordinal مع raw block index. | LEARN_OWNED | NONE | LOW |
| LRN-DA-F02 | لا يوجد SelectedActivity union. | LEARN_OWNED | NONE | LOW |
| LRN-DA-F03 | conflation بين UI selection و recommendation state. | LEARN_OWNED | NONE | LOW |
| LRN-DA-F04 | localStorage index-only + no observer/scroll restoration. | LEARN_OWNED | NONE | LOW |
| LRN-DA-F05 | storage namespace غير scoped للهوية/الجلسة. | LEARN_OWNED | Bounded identity/session scope | MEDIUM |
| LRN-DA-F06 | presentation consumes backend diagnostic sentence. | LEARN_OWNED | KnowledgeLibraryService additive reason code may be shared | LOW |
| LRN-DA-F07 | frontend drops available provenance fields at rendering layer. | LEARN_OWNED | NONE | LOW |
| LRN-DA-F08 | مصدر truth authoritative غير مربوط مباشرة بالعرض. | LEARN_OWNED | NONE | LOW |
| LRN-DA-F09 | `selectedStepId` is local-only; `KnowledgeTabs.withObject()` preserves only canonical object identity. | LEARN_OWNED | KnowledgeTabs only if shared return token is necessary | MEDIUM |
| LRN-DA-D01 | browser evidence ran with sparse test fixture, not a representative canonical/rich acceptance profile. | EVIDENCE_FIXTURE_OWNER | W02 acceptance dataset/browser harness | MEDIUM |
| LRN-DA-D02 | fixture runtime record لا يعكس canonical B09 record ذي الهوية نفسها. | EVIDENCE_FIXTURE_OWNER | NONE | LOW |
| LRN-DA-D03 | acceptance profile متعمد ومحدود ومفصول عن B09/B10 import. | DATA_EVIDENCE_OWNER | NONE | LOW |
| LRN-DA-D04 | evidence fixture sparse. | EVIDENCE_FIXTURE_OWNER | NONE | LOW |
| LRN-DA-D05 | browser evidence matrix incomplete for activity variants. | EVIDENCE_FIXTURE_OWNER | NONE | LOW |
| LRN-DA-R01 | لا يوجد في المسار المراجع دليل تشغيل full B09 importer ؛ default seeder لا يستدعي W02AcceptanceSeeder أصلاً. | CANONICAL_RUNTIME_INTEGRATION_OWNER | Knowledge ingestion/runtime integration | HIGH |
| LRN-DA-R02 | runtime services موصولة بجداول التطبيق، لكن upstream canonical ingestion coverage غير مثبت. | CANONICAL_RUNTIME_INTEGRATION_OWNER | NONE | LOW |
| LRN-DA-R03 | first-row database order مستخدم كسياسة semantic. | CURRICULUM_CONTEXT_AUTHORITY | CurriculumKnowledgeService + Controller A01 | HIGH |
| LRN-DA-R04 | canonical Assessment persistence/owner غير موجود ضمن المعمارية الحالية. | ASSESSMENT_AUTHORITY_UNASSIGNED | Future authoritative Assessment contract | HIGH |
| LRN-DA-R05 | W03 handoff authority غير متاح في baseline. | W03_SIMULATION_ENTERPRISE_OWNER | W03 authoritative handoff | HIGH |
| LRN-DA-R06 | `KnowledgeJourneyService` uses lexical `practice_id` order without proven pedagogical sequence authority. | LEARNING_ORDER_AUTHORITY / CONTROLLER_B_DECISION | Learning/Curriculum ordering contract | HIGH |
| LRN-DA-E01 | غياب exact 1024 capture على candidate. | VALIDATION_EVIDENCE_OWNER | NONE | LOW |
| LRN-DA-E02 | evidence capture لم يشمل representative dataset. | VALIDATION_EVIDENCE_OWNER | NONE | LOW |
| LRN-DA-E03 | browser evidence coverage محدود. | VALIDATION_EVIDENCE_OWNER | NONE | LOW |

## Ownership boundary
This audit assigns **future ownership labels only**. It does not dispatch a writer and does not authorize edits. Any path shared with Library/Visualize/RQ or Curriculum/W03/W04 remains a shared/external dependency that Controller B must assign deliberately.

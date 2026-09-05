# CEP W02 Learn — Data Representativeness and Runtime Binding Audit

- Project: CEP / W02 Learn
- Mode: READ-ONLY DEEP AUDIT / DISCOVERY ONLY
- Candidate: `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- Parent: `7fa8714dc6d0beec6ec77ba8a673140301b066cf`
- Baseline drift: `NO_BASELINE_DRIFT`
- Date: 2026-09-04


## Determination
The reviewed Learn state is **not one single data status**. Each layer has a different proof level:

| Layer | Status | Direct evidence | Judgment |
|---|---|---|---|
| B09 physical canonical corpus | PROVEN_FULL | Drive `143XnqYySfgYM04AslzvMxq03gWpBNZpd` + archive `1P9RW1rIAVdJNuoQqqgaJZ_IZcDpm-Si6` | 224 physical KUs, 2,603 claims, 192 capability bindings structurally proven. |
| Active B09 `KU-D03-0001` richness | PROVEN_FULL for that canonical record | Direct archive read | Title, capability, 8 claims, prerequisites/relationships/limitations/coverage all present. |
| DB-backed Learn object/revision path | PROVEN_PARTIAL | `KnowledgeLibraryService.php` | Persisted KnowledgeUnit + latest published LessonRevision are used. |
| Curriculum context projection | PROVEN_PARTIAL | `CurriculumKnowledgeService.php` + `KnowledgeLearningWorkspace.php` | Placements are persisted; Learn currently consumes first placement lifecycle silently. |
| Practice/activity projection | PROVEN_PARTIAL | `KnowledgeJourneyService.php` | Persisted MicroPractice and PracticeAttempt are read; latest practice revision selected. |
| Practice pedagogical ordering / next semantics | PROVEN_PARTIAL / AUTHORITY_DECISION_REQUIRED | `KnowledgeJourneyService.php` + prior A02 | Current service orders by lexical `practice_id`; no reviewed authority proves that lexical order is pedagogical sequence. |
| Assessment runtime | PROVEN_PARTIAL / GAP | `KnowledgeJourneyService.php` | Explicitly no canonical Assessment persistence in current architecture. |
| Lab handoff | PROVEN_PARTIAL / GAP | `KnowledgeJourneyService.php` | Reference-only; W03 handoff unavailable, executable=false/href=null. |
| W02 acceptance data | REPRESENTATIVE_SUBSET | `W02AcceptanceSeeder.php` | Six exact KUs only; local/testing; canonical runtime import disabled. |
| Current 1440 screenshot content | TEST_FIXTURE_ONLY | Drive `1_AgqoAWNdvztmRWzfPEb6lPG4CqvVaoF` + prior package | `Test KU 1 / Test Section / C1`; not representative of B09 richness. |
| Full 224-KU canonical → runtime import | NOT_PROVEN | Seeder explicitly says it never imports B09/B10 | No full integration claim is allowed. |
| 1024 runtime UI | EVIDENCE_INSUFFICIENT | No exact-candidate Learn ~1024 screenshot | Must remain evidence gap. |

## End-to-end trace as far as proven
### Canonical KU → claims/capability
B09 `KU-D03-0001` resolves to Domain `D03`, Cluster `CC-D03-001`, Capability `CAP-D03-0001`; the record contains eight normalized claims and relationship/limitation structures. This proves canonical structure, not runtime delivery.

### Curriculum placement
`CurriculumPlacement` is queried by `CurriculumKnowledgeService`. W02 acceptance data can populate lifecycle fields including domain, cluster, capability, pathway, prerequisites, objectives, assessment blueprints and lab blueprints. Learn's `learningContext()` then selects **only `placements[0].lifecycle`**, creating a real multi-placement binding risk.

### Lesson/activity structures
`KnowledgeLibraryService::learningUnit()` fetches the latest **published** LessonRevision for a persisted KnowledgeUnit. `LessonContentRenderer.vue` supports headings, prose, code-like/technical blocks, callouts and mixed inline content, so current visual sparsity is not evidence that the renderer cannot display rich lessons.

### Practice/Assessment/Lab runtime
`KnowledgeJourneyService` reads persisted practices/attempts. Completion is explicitly activity-only and not Mastery. Practice ordering is currently lexical by `practice_id`; this is deterministic but not proven pedagogically authoritative and is therefore an explicit A02 decision/gap. Assessment remains non-executable because canonical persistence is absent. Labs are contextual references and remain non-executable until W03 authority exists.

### Frontend
`Learn.vue` consumes all of the above, but loses/underuses some binding richness: source `authority_class/review_status` are available in the typed context but not rendered; `journey.next` exists but recommendation UI is driven by `selectedStep`; authoritative `journey.assessments` exists but assessment UI is largely lifecycle-blueprint driven.

### Screenshot
The current screenshot cannot be treated as canonical content evidence. The same visible technical object id `KU-D03-0001` corresponds to a rich B09 canonical record, while the screenshot presents `Test KU 1 / Test Section / C1`. This is a **representativeness problem**, not proof that B09 is absent, and not permission to treat all visual sparsity as fixture-only.

## Binding classification rules for Controller B
1. Do not state "224 KUs are integrated in Learn" — **NOT_PROVEN**.
2. Do not state "Learn has no canonical runtime binding" — DB-backed canonical object continuity is **PROVEN_PARTIAL**.
3. Do not treat the six-KU acceptance seeder as canonical import — it is explicitly `REPRESENTATIVE_SUBSET` and import authorization is false.
4. Do not use Test KU visual emptiness to close visual-design findings. Re-run with rich deterministic data.
5. Do not convert Assessment/Lab integration gaps into fabricated UI behavior.

## Relevant findings
`LRN-DA-D01`..`D05`, `LRN-DA-R01`..`R06`, `LRN-DA-F07`, `LRN-DA-F08`, `LRN-DA-F09`, and evidence findings `E01`..`E03`.

# CEP W02 Learn — Prior Finding Coverage and Missed Defect Matrix

- Project: CEP / W02 Learn
- Mode: READ-ONLY DEEP AUDIT / DISCOVERY ONLY
- Candidate: `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
- Parent: `7fa8714dc6d0beec6ec77ba8a673140301b066cf`
- Baseline drift: `NO_BASELINE_DRIFT`
- Date: 2026-09-04


## Reconciliation result
The prior package was materially useful for behavior/architecture, but it **under-decomposed the obvious visual/design distance**. In particular, broad findings such as shared-region ownership, selected activity, and sparse fixture were carrying many separate visual consequences that require independent closure. The deep audit therefore preserves known behavioral findings while splitting design/data consequences into separate auditable findings.

### Status totals
- KNOWN: 22
- UNDER_SPECIFIED: 14
- MISSED_NEW: 17
- REGRESSED: 2

### Important corrections to prior conclusions
1. `Test KU` content difference is **not merely ALLOWED_INTENTIONAL_DEVIATION** for audit-evidence quality. The content itself may differ intentionally, but the **fixture representativeness gap** is a separate `DATA_FIXTURE_REPRESENTATIVENESS_GAP` because it prevents reliable visual/data-pressure evaluation.
2. Prior "richer contextual tab strip = allowed deviation" is too broad. Exact tab count is optional, but the **loss of contextual depth/information** remains a required design outcome.
3. Prior bottom review focused on shared ownership. The **deep-work channel/content affordance** is separately under-specified.
4. Icon glyph parity is optional, but the current use of literal platform-dependent Emoji is a **systemic product-design consistency issue**, not merely a shade/glyph mismatch.
5. The prior package did not complete a B09 224 → runtime integration classification. This audit proves B09 structural fullness and the six-KU acceptance subset while leaving full runtime import `NOT_PROVEN`.

## Complete mapping
| Deep finding | Prior mapping | Status | Primary classification | Deep-audit determination |
|---|---|---|---|---|
| LRN-DA-V01 | LRN-01 + ARCH-SHARED-01-S1 | KNOWN | PRODUCT_VISUAL_DESIGN_DEFECT | Learn يعيد بناء LEFT/CENTER/RIGHT/BOTTOM داخل الـ default CENTER في CepWorkspaceLayout. |
| LRN-DA-V02 | Prior ledger bundled this only under shared-region ownership; geometry/span delta not independently specified. | MISSED_NEW | PRODUCT_VISUAL_DESIGN_DEFECT | السطح الحالي محاط بهوامش/padding خارجية واضحة وبـ rounded container يترك planes فارغة حول محطة التعلم. |
| LRN-DA-V03 | Prior Reference Delta: resume copy inside CENTER / TOP required | KNOWN | PRODUCT_VISUAL_DESIGN_DEFECT | لا توجد طبقة TOP محلية فعالة؛ resume/current-activity metadata موزعة داخل CENTER. |
| LRN-DA-V04 | LRN-01-S1 / CENTER selectedActivity was behavioral; visual dominance not decomposed | UNDER_SPECIFIED | PRODUCT_VISUAL_DESIGN_DEFECT | CENTER هو الأكبر هندسياً لكنه لا يهيمن معرفياً: درس قصير جداً يليه بطاقات نشاط وحالة كثيرة وفراغ رأسي كبير. |
| LRN-DA-V05 | Prior only said sparse fixture/structural gap; dead-space not isolated | MISSED_NEW | PRODUCT_VISUAL_DESIGN_DEFECT | توجد مساحة فارغة ممتدة تحت محتوى Test Section وفي مناطق CENTER/RIGHT مع محتوى قليل جداً. |
| LRN-DA-V06 | LRN-N06 + Reference Delta LEFT previous/current/next | KNOWN | PRODUCT_VISUAL_DESIGN_DEFECT | LEFT يمثل outline للوحدة الحالية ولا يعرض previous/current/next KUs في مسار تعليمي مرتب. |
| LRN-DA-V07 | LRN-C01 | REGRESSED | PRODUCT_VISUAL_DESIGN_DEFECT | Test Section يظهر مرتين في LEFT: LearningPathNode كبير ثم صف 01 Test Section. |
| LRN-DA-V08 | LRN-C01 described index/state bug but not full selected-state visual grammar | UNDER_SPECIFIED | PRODUCT_VISUAL_DESIGN_DEFECT | current/selected يعتمد على ring/card واحد مع state colors عامة، بينما الخط البصري للرحلة غير متصل ولا يوضح current مقابل available/locked بوضوح. |
| LRN-DA-V09 | LRN-01-S1 + Assessment/Lab non-selectable | KNOWN | PRODUCT_VISUAL_DESIGN_DEFECT | Practice/Assessment/Lab في LEFT تظهر كصفوف حالة منفصلة وغير متجانسة مع lesson nodes ؛ Assessment/Lab غير قابلة للاختيار. |
| LRN-DA-V10 | Prior covered pathway and canonical object but did not isolate route-orientation presentation | MISSED_NEW | PRODUCT_VISUAL_DESIGN_DEFECT | تظهر هوية KU التقنية لكن لا يوجد breadcrumb/path orientation غني يربط Domain/Capability/Pathway/KU/current activity كما في المرجع. |
| LRN-DA-V11 | Reference Delta RIGHT current objective REQUIRED_MATCH | KNOWN | PRODUCT_VISUAL_DESIGN_DEFECT | اللقطة الحالية لا تعرض objective فعلياً؛ objective section تختفي بالكامل عندما fixture.lifecycle.objectives فارغة. |
| LRN-DA-V12 | Prior: Lab readiness REQUIRED_MATCH; broader activity readiness not decomposed | UNDER_SPECIFIED | PRODUCT_VISUAL_DESIGN_DEFECT | لا توجد readiness summary موحدة للنشاط؛ readiness موزعة بين unavailable labels و Lab blueprint/assessment states. |
| LRN-DA-V13 | Prior mentioned prerequisites but did not decompose relation/readiness visual semantics | UNDER_SPECIFIED | PRODUCT_VISUAL_DESIGN_DEFECT | prerequisites تظهر كسياق بسيط/empty message ولا ترتبط بصرياً بالـ current activity أو lock/readiness. |
| LRN-DA-V14 | LRN-01-S1 / Practice does not replace CENTER | KNOWN | PRODUCT_VISUAL_DESIGN_DEFECT | Practice في CENTER عبارة عن sibling card/list أسفل lesson وليست focused practice surface. |
| LRN-DA-V15 | LRN-03-S1 PARTIAL / prior focused truthfulness more than composition | UNDER_SPECIFIED | PRODUCT_VISUAL_DESIGN_DEFECT | Assessment يظهر كـ preview/unavailable card مضاف أسفل lesson وصف حالة في LEFT ، دون selected Assessment workspace. |
| LRN-DA-V16 | LRN-03-S1 + Lab readiness REQUIRED_MATCH | UNDER_SPECIFIED | PRODUCT_VISUAL_DESIGN_DEFECT | Lab يظهر كـ blueprint/unavailable card وصف حالة، لا كـ preview/readiness activity selected surface. |
| LRN-DA-V17 | Prior allowed exact tab count deviation but did not quantify/decompose context-depth loss | UNDER_SPECIFIED | PRODUCT_VISUAL_DESIGN_DEFECT | RIGHT الحالي ثلاثة/أربعة أقسام بسيطة بلا طبقات contextual navigation أو summaries كثيفة، بينما المرجع يضم objective/prereqs/sources/labs/evidence/notes/history وغيرها. |
| LRN-DA-V18 | Prior Reference Delta quick access REQUIRED_MATCH | KNOWN | PRODUCT_VISUAL_DESIGN_DEFECT | لا توجد مجموعة quick access واضحة في RIGHT لنفس canonical object عبر Library/Visualize/RQ. |
| LRN-DA-V19 | Prior only said shared bottom owner; deep-work channel/content parity under-specified | UNDER_SPECIFIED | PRODUCT_VISUAL_DESIGN_DEFECT | BOTTOM الحالي bar محلي واحد يفتح أربع status cards ؛ لا يقدم channels مثل Knowledge/Relations/Practice/Assessment/Labs/Evidence/Claims التي تجعل deep-work usable. |
| LRN-DA-V20 | Prior judged closed presentation close to target; missed information-affordance gap | MISSED_NEW | PRODUCT_VISUAL_DESIGN_DEFECT | الحالة المغلقة تظهر كسطر عام “مساحة السياق المؤقتة” بلا counts/active channel/context cue. |
| LRN-DA-V21 | Not decomposed in prior ledger | MISSED_NEW | PRODUCT_VISUAL_DESIGN_DEFECT | الكثافة الحالية منخفضة: أسطح كبيرة، نص قليل، أشرطة/بطاقات متباعدة، ومعلومات سياق قليلة لكل viewport. |
| LRN-DA-V22 | Not independently identified | MISSED_NEW | PRODUCT_VISUAL_DESIGN_DEFECT | تقريباً كل مجموعة محاطة ب rounded border card ، بما فيها navigation/status ، فتتساوى مستويات الأهمية. |
| LRN-DA-V23 | Not independently identified | MISSED_NEW | PRODUCT_VISUAL_DESIGN_DEFECT | حدود متعددة ومتكررة حول panels/cards بينما separators الداخلية وال row hierarchy أقل قوة. |
| LRN-DA-V24 | Not decomposed | MISSED_NEW | PRODUCT_VISUAL_DESIGN_DEFECT | H1 والعناوين/labels كبيرة نسبياً مقارنة بحجم المعلومات؛ مستويات text كثيرة لكنها لا تنتج hierarchy كثيفاً مثل المرجع. |
| LRN-DA-V25 | Not decomposed | MISSED_NEW | PRODUCT_VISUAL_DESIGN_DEFECT | padding/gaps كبيرة ومتكررة بين cards وال sections وتنتج rhythm بطيئاً. |
| LRN-DA-V26 | Prior treated exact iconography as allowed deviation; deep audit finds platform-dependent glyph system issue | MISSED_NEW | PRODUCT_VISUAL_DESIGN_DEFECT | التبويبات وبعض الأفعال تستخدم Emoji (📖🎓🕸️⚖️ وغيرها) داخل واجهة enterprise. |
| LRN-DA-V27 | Prior did not decompose tab geometry | MISSED_NEW | PRODUCT_VISUAL_DESIGN_DEFECT | بوابات W02 تظهر كبطاقات pill منفصلة بارتفاع/padding ملحوظ داخل top shell ، بدلاً من tab strip مضغوط. |
| LRN-DA-V28 | Prior shared-layout finding did not isolate future rich-data width pressure | UNDER_SPECIFIED | PRODUCT_VISUAL_DESIGN_DEFECT | الـ inner three-column grid 280/flex/300 داخل shell padded يقلل effective CENTER width مقارنة بالمرجع الواسع ويجعل RIGHT ضيقاً لمحتوى غني. |
| LRN-DA-V29 | Prior validation matrix requires mixed Arabic/English; no rich Learn screenshot | UNDER_SPECIFIED | EVIDENCE_INSUFFICIENT | الكود يستخدم bdi/dir=ltr في كثير من IDs ، لكن التغطية البصرية لم تُثبت rich mixed strings داخل objectives/sources/pathway/labels. |
| LRN-DA-V30 | Prior V-40..V-45 evidence requirements | KNOWN | EVIDENCE_INSUFFICIENT | اللقطة لا تثبت hover/focus/disabled/keyboard-selected states للنشاطات واللوحات، وبعض nodes غير interactive أصلاً. |
| LRN-DA-V31 | LRN-C-A11Y-01 | KNOWN | PRODUCT_VISUAL_DESIGN_DEFECT | `ProgressIndicator.vue` has no accessible name/value-text contract and uses raw-block progress semantics. |
| LRN-DA-V32 | Prior TOP-resume + RIGHT-quick-access findings covered ownership but not control hierarchy | UNDER_SPECIFIED | PRODUCT_VISUAL_DESIGN_DEFECT | Actions and controls are scattered across CENTER/LEFT/top gateways without a coherent primary/contextual hierarchy. |
| LRN-DA-F01 | LRN-C01 | REGRESSED | FUNCTIONAL_DEFECT | LearningPathNode v-for ordinal يُقارن بـ selectedBlockIndex الخام عبر جميع blocks. |
| LRN-DA-F02 | LRN-01-S1 | KNOWN | FUNCTIONAL_DEFECT | selectedStepId يمكن أن يشير إلى Practice بينما CENTER يظل Lesson. |
| LRN-DA-F03 | LRN-N01 | KNOWN | FUNCTIONAL_DEFECT | RIGHT “الخطوة التالية المقترحة” مشتقة من selectedStep لا من journey.next. |
| LRN-DA-F04 | LRN-02-S1 | KNOWN | FUNCTIONAL_DEFECT | resume يحفظ selectedBlockIndex عند explicit selection ولا يلتقط natural scroll ؛ restore يضبط index ولا يعيد viewport فعلياً. |
| LRN-DA-F05 | LRN-N04 / A07 | KNOWN | FUNCTIONAL_DEFECT | مفتاح resume يحتوي revision id فقط ولا يضم user/session scope. |
| LRN-DA-F06 | LRN-N05 | KNOWN | FUNCTIONAL_DEFECT | KnowledgeLibraryService يرسل unavailable_reason إنجليزي كامل و Learn يعرضه مباشرة في Arabic UI. |
| LRN-DA-F07 | Prior required source/context generally but did not identify bound fields being discarded | MISSED_NEW | FUNCTIONAL_DEFECT | SourceContext يتضمن authority_class و review_status ، لكن RIGHT يرسم title فقط؛ fixture يظهر C1. |
| LRN-DA-F08 | LRN-03-S1 | KNOWN | FUNCTIONAL_DEFECT | backend يرسل journey.assessments authoritative state ، بينما UI يعتمد lifecycle assessment_blueprints لأول placement ولا يعرض journey.assessments كمالك للحالة. |
| LRN-DA-F09 | LRN-02-S3 | KNOWN | FUNCTIONAL_DEFECT | selected activity is local-only; cross-surface navigation preserves the KU object but not the selected Learn activity/return state. |
| LRN-DA-D01 | Prior ledger: labels/content differ from Test KU fixture = allowed deviation; deep audit reclassifies representativeness separately | UNDER_SPECIFIED | DATA_FIXTURE_REPRESENTATIVENESS_GAP | لقطة Learn الحالية تستخدم Test KU 1 / Test Section / C1 و1/1؛ لا تمثل canonical learning content الحقيقي. |
| LRN-DA-D02 | No prior item bound Test KU identity to the exact B09 KU richness | MISSED_NEW | DATA_FIXTURE_REPRESENTATIVENESS_GAP | الـ object id الظاهر KU-D03-0001 يطابق B09 record غني بعنوان Authentication Protocol Ceremonies and Trust Boundaries مع 8 normalized claims وعلاقات/limitations ؛ اللقطة تعرض Test KU 1 ومحتوى فقيراً. |
| LRN-DA-D03 | Prior package required rich fixture but did not classify the six-KU seeder against the 224-KU canonical baseline | MISSED_NEW | DATA_FIXTURE_REPRESENTATIVENESS_GAP | W02AcceptanceSeeder يزرع 6 KUs فقط في local/testing ويشترط canonical_runtime_import_authorized=false. |
| LRN-DA-D04 | Prior V-02..V-11 + rich fixture requirements | KNOWN | DATA_FIXTURE_REPRESENTATIVENESS_GAP | لقطة primary لا تحتوي multiple lesson sections أو two Practices أو objective/prerequisite richness أو selected!=recommended scenario. |
| LRN-DA-D05 | Prior V-05/V-06/V-31/V-32 | UNDER_SPECIFIED | DATA_FIXTURE_REPRESENTATIVENESS_GAP | primary screenshot يثبت unavailable labels فقط ولا يعرض selected Assessment أو Lab preview/readiness داخل CENTER/RIGHT. |
| LRN-DA-R01 | Prior package did not audit B09 224→runtime completeness directly | MISSED_NEW | CANONICAL_RUNTIME_BINDING_GAP | B09 يثبت 224 physical KUs و2603 claims structur ياً، لكن W02AcceptanceSeeder يعلن صراحة أنه لا يستورد B09/B10 وأن canonical runtime import غير مصرح. |
| LRN-DA-R02 | Prior acknowledged canonical continuity but not end-to-end binding classification | MISSED_NEW | CANONICAL_RUNTIME_BINDING_GAP | Learn runtime يقرأ persisted KnowledgeUnit + latest published LessonRevision + CurriculumPlacement + MicroPractice/Attempts + source quality ؛ هذا الربط PROVEN_PARTIAL فقط. |
| LRN-DA-R03 | LRN-N03 / A01 | KNOWN | CANONICAL_RUNTIME_BINDING_GAP | learningContext يقرأ placements[0].lifecycle لكل pathway/objectives/prereqs/blueprints. |
| LRN-DA-R04 | A05 + allowed intentional deviation | KNOWN | CANONICAL_RUNTIME_BINDING_GAP | journey.assessments = NO_CANONICAL_ASSESSMENT_PERSISTENCE_IN_CURRENT_ARCHITECTURE / AUTHORITATIVE_ASSESSMENT_CONTRACT_REQUIRED / executable=false. |
| LRN-DA-R05 | A06 + fake Lab guardrail | KNOWN | CANONICAL_RUNTIME_BINDING_GAP | lab references تأتي من practice definition ؛ prepare_run_handoff = PARENT_INTEGRATION_REQUIRED, executable=false, href=null. |
| LRN-DA-R06 | Practice ordering / A02 | KNOWN | CANONICAL_RUNTIME_BINDING_GAP | lexical `practice_id` ordering drives Practice sequence/next without a proven pedagogical ordering authority. |
| LRN-DA-E01 | Prior 1024 EVIDENCE_GAP | KNOWN | EVIDENCE_INSUFFICIENT | لا توجد لقطة Learn exact-candidate عند ~1024 في evidence folder ؛ static Learn grid عند md يتحول إلى 2 columns ثم RIGHT full-width row ، بينما shared layout لديه context-toggle mode. |
| LRN-DA-E02 | Prior asked richer fixture but did not record absence as separate end-to-end evidence classification | MISSED_NEW | EVIDENCE_INSUFFICIENT | لا توجد لقطة exact-candidate تثبت Learn مع W02AcceptanceSeeder six-KU أو fixture غني comparable للـ canonical structure ؛ primary screenshot ما زالت Test KU. |
| LRN-DA-E03 | Prior validation matrix, but no consolidated evidence-insufficiency finding | UNDER_SPECIFIED | EVIDENCE_INSUFFICIENT | لا يوجد direct runtime proof لـ focus order, keyboard activity selection, disabled/hover, expanded BOTTOM, context toggle identity, passive-scroll behavior. |

## Prior finding-ID completeness matrix
Every material prior finding/guardrail is explicitly adjudicated below; none is silently inherited.

| Prior item | Deep-audit determination | Coverage quality | Deep mapping / disposition |
|---|---|---|---|
| LRN-01 | still materially valid | behavior/architecture was correct but visual consequences were broader | `V01`, `V02`, `V19`, `V21`, `V28` |
| LRN-01-S1 | still materially valid | behavior was correct; Practice/Assessment/Lab composition required deeper split | `V09`, `V14`–`V16`, `F02` |
| LRN-02-S1 | still materially valid | complete at root-cause level | `F04` |
| LRN-02-S2 | still materially valid | prior progress vocabulary incomplete because accessible-name semantics were separate | `F01`, `V07`, `V31` |
| LRN-02-S3 | valid and previously not promoted into the first deep ledger | **corrected by meta-assurance** | `F09` |
| LRN-03-S1 | still materially valid | prior truthfulness focus under-specified visual composition | `V15`, `V16`, `F08`, `R04`, `R05` |
| ARCH-SHARED-01-S1 | still materially valid | shared capability exists; Learn consumption and responsive proof remain open | `V01`, `V28`, `E01` |
| LRN-N01 | still materially valid | complete | `F03` |
| LRN-N02 | still materially valid | complete with duplicate/index-state split | `V07`, `F01` |
| LRN-N03 | still materially valid | authority-deferred, not silently corrected | `R03` |
| LRN-N04 | still materially valid | complete | `F05` |
| LRN-N05 | still materially valid | complete | `F06` |
| LRN-N06 | still materially valid | prior behavior finding plus deeper journey design decomposition | `V06`, `V08`, `V10` |
| Completion != Mastery guardrail | genuinely complete and must be preserved | non-defect semantic guardrail | preserved; `G03/G04` forbid inventing percentage/checkmarks from Mastery |
| no fake Lab execution guardrail | genuinely complete and must be preserved | non-defect semantic guardrail | `R05`, `G07` |
| Practice ordering | authority issue remained real and was missing from first deep ledger | **corrected by meta-assurance** | `R06` / A02 |
| Overall journey percentage | correctly authority-deferred | no defect in current omission | `G03` |
| LRN-C01 | real regression | complete and split into visual + functional consequences | `V07`, `F01` |
| LRN-C-A11Y-01 | real P2 defect and was missing from first deep ledger | **corrected by meta-assurance** | `V31` |

## Prior reference-delta completeness matrix
The prior Reference Delta Ledger also contained material image-vs-contract conclusions not all expressed as finding IDs. They are reconciled explicitly here.

| Prior reference delta | Deep-audit determination | Deep mapping |
|---|---|---|
| LEFT lacks previous/current/next units | correct; still required | `V06`, `R03` |
| LEFT duplicate section | correct; regression | `V07`, `F01` |
| overall 28% omitted | prior authority decision was correct | `G03` |
| previous-unit completion checks absent | prior authority decision was correct; unknown must remain unknown | `G04` |
| CENTER should show one active activity | correct but visually under-decomposed | `V04`, `V14`–`V16`, `F02` |
| Practice selection does not replace CENTER | correct | `V14`, `F02` |
| Assessment/Lab rows non-selectable | correct; composition/readiness consequences deeper | `V09`, `V15`, `V16` |
| RIGHT current objective missing | correct | `V11` |
| recommended next derived from selection | correct | `F03` |
| Lab readiness weak | correct but under-specified | `V12`, `V16`, `R05` |
| RIGHT quick access missing | correct; control hierarchy also under-specified | `V18`, `V32` |
| resume copy belongs in TOP | correct; control hierarchy also under-specified | `V03`, `V32` |
| BOTTOM bypasses shared owner | correct; deep-work breadth also under-specified | `V19`, `V20` |
| Save/Undo visible in reference | prior override was correct | `G01 = CONTRACT_OVERRIDES_IMAGE` |
| richer contextual tab strip | exact tab count may deviate, but context-depth loss remains actionable | `G02` + `V17` |
| Test KU content difference | literal text variance may deviate, but evidence representativeness was under-classified | `G08` + `D01`–`D05` |
| exact icon/color shade differs | pixel parity may deviate, but platform-dependent Emoji remains a separate defect | `G05` + `V26` |
| 1/1 LEFT vs 1 of 2 CENTER | correct progress-semantic problem | `F01`, `V31` |
| ~1024 quality unknown | correct evidence gap | `E01` |
| no executable Assessment | correct intentional limitation | `G06`, `R04` |
| no executable Lab | correct intentional limitation | `G07`, `R05` |

The reconciliation therefore covers **all material prior finding IDs and all material prior reference-delta conclusions**; nothing is preserved solely because a previous reviewer said it.

## Reconciliation verdict
- Prior behavioral/root-cause coverage: **substantive but not complete**.
- Prior visual decomposition: **materially under-specified**.
- Prior data-realism classification: **insufficiently separated from visual intent**.
- New findings are not implementation instructions; Controller B must adjudicate scope/priority.

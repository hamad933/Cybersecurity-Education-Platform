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

# 01 — Blind Reference vs Candidate Forensics

> هذه الوثيقة تسجل المقارنة المستقلة التي أُنجزت قبل فتح findings السابقة.

## 1. Macro workspace geometry
المرجع الحاكم يقدم مساحة عمل ذات كثافة عالية: LEFT شجرة دلالية عميقة، CENTER وثيقة معرفة ممتلئة ومتعددة الأقسام، RIGHT سياق غني، وBOTTOM شريط deep-work واضح. المرشح الحالي صحح ownership البنيوي، لكنه لا يحقق بعد الإحساس نفسه بسيادة CENTER أو كثافة العمل.

المشكلة ليست اختلاف pixel-level. الفجوة المادية هي أن المرشح يعرض حاويات صحيحة وظيفيًا حول محتوى قليل جدًا؛ لذلك يصبح الـ frame نفسه هو العنصر البصري المهيمن بدل المعرفة.

## 2. CENTER dominance and useful area
في المرجع، يبدأ المحتوى الفعلي بسرعة ويملأ معظم CENTER: عنوان، metadata، toolbar، فقرات، code، قوائم وأقسام مرتبطة. في المرشح 1440 تظهر `Test KU 1` ثم كتلتان صغيرتان فقط، بينما يبقى الجزء الأكبر من document body فارغًا.

المسار البرمجي يؤكد أن `.library-document-body` يفرض `min-height: 32rem`. إضافةً إلى ذلك توجد طبقات padding متراكبة من `.cep-workspace` و`.cep-primary-surface` ثم document header/body.

النتيجة: dead space ليس مجرد نقص fixture؛ الآلية البصرية نفسها تضخم أثر fixture صغير.

## 3. Layering / cards / elevation
المرشح يضع `.library-document` داخل `.cep-primary-surface`، وكلاهما bordered/rounded/shadowed. هذا ينتج Card داخل Card ويقلل إحساس الوثيقة كـ primary work plane. المرجع أكثر استمرارية، مع حدود وظيفية واضحة لكن دون تضخيم الحاوية على حساب المحتوى.

## 4. Typography and rhythm
- عنوان KU كبير جدًا مقارنةً بحجم body الحالي.
- metadata وIDs كثيرة عند 9–10px وبألوان muted.
- بعض diagnostic/footer copy صغير جدًا ويعرض مصطلحات هندسية داخل واجهة المستخدم.
- block/editor toolbar يمزج selector كبيرًا مع micro-actions صغيرة جدًا.
- spacing في prose والtechnical/callout blocks غير مُثبت على مستند طويل حقيقي.

## 5. LEFT hierarchy
المرجع يجعل hierarchy أداة اكتشاف حقيقية: parent groups كثيفة، counts، nested items، اختيار واضح.
المرشح الحالي يضع كل الوحدات الست داخل unresolved warning. هذا صادق مع غياب hierarchy authority، لكنه لا يثبت normal-state grammar.

الكود يثبت وجود renderer متدرج Domain→Cluster→Capability→KU، لكن `KnowledgeLearningWorkspace::hierarchyContexts()` يملأ human-title fields بالـ IDs نفسها. لذلك normal human hierarchy غير مربوط بسلطة دلالية حقيقية.

## 6. RIGHT context
المرشح يلتزم boundary أفضل من المرجع في عدم نسخ RQ deep work. هذا **ليس** سببًا لإعادة tabs المرجع. لكن Overview الحالي شديد الفراغ: أربعة counters صغيرة وثلاث diagnostic cards، مع fixture cardinality تافه. المطلوب لاحقًا هو richness داخل الحدود الحالية، لا نسخ RQ.

## 7. TOP and toolbar
في 1440 توجد طبقات global navigation + primary tabs + action bar. في 1024 تتضاعف المشكلة لأن global nav يلتف إلى row إضافي، ويزداد ارتفاع action bar بسبب wrapping. هذا يحافظ على الوظائف لكنه يستهلك نسبة كبيرة من desktop-height المستهدف.

## 8. 1024 behavior
عند medium desktop:
- RIGHT collapsed by design: هذا `ALLOWED_INTENTIONAL_DEVIATION` بشرط نجاح overlay/open state.
- LEFT يبقى حتى 15rem.
- CENTER يحتفظ بالـ nested padding/frame.
- global navigation wraps.

يوجد exact 1024 evidence لاحق لم يكن محسوبًا في الحزمة السابقة، لكن RIGHT-open وmodal/deep-work states ما تزال غير مثبتة.

## 9. RTL/LTR and Bidi
الكود أفضل من visual evidence: `dir=auto`، technical blocks LTR، `bdi` و`unicode-bidi: plaintext/isolate` موجودة. لكن rendering وحده لا يغلق caret/selection/IME/Home/End/Delete. هذه فجوة runtime evidence وليست حجة على أن Bidi فاشل.

## 10. Visual-language consistency
Core navigation/tree يعتمد emoji (`📖`, `🎓`, `🕸️`, `⚖️`, `📁`, `📂`, `⚡`, `🛡️`). هذا لا يطابق اللغة الاحترافية المتماسكة في المرجع ويختلف بين OS/browser. كذلك توجد عدة عائلات رموز صغيرة داخل editor بلا grammar موحدة.

## 11. Representative data realism
المرشح البصري الحالي يعرض:
- `Test KU 1..6`
- `Test Section`
- raw-looking `Lesson [cite:VS3-AUTH-001]`
- `ACCEPTANCE_BALANCED_6`
- footer contract `CEP_W02_LESSON_CONTENT_V2`

هذه ليست مجرد أسماء قبيحة؛ إنها تمنع التحقق من wrapping, density, hierarchy semantics, provenance scale, long document behavior.

## 12. Non-defect authority distinctions
لا تُعامل الفروق التالية كـ pixel defects:
- BOTTOM launcher في المرجع مقابل TOP opener الحالي: `CONTRACT_OVERRIDES_IMAGE`.
- broader RIGHT tabs في المرجع: `CONTRACT_OVERRIDES_IMAGE`.
- RIGHT collapsed افتراضيًا عند 1024: `ALLOWED_INTENTIONAL_DEVIATION` بشرط إثبات open overlay.
- reference-only filters/tags/actions: `AUTHORITY_DECISION_REQUIRED`.

## خلاصة Blind Audit
المسافة البصرية الأساسية بين المرجع والمرشح ليست “spacing” مفردة. إنها مزيج مستقل من:
CENTER geometry، dead-space mechanics، nested framing، type scale، metadata legibility، toolbar hierarchy، responsive chrome، semantic hierarchy binding، context richness، icon system، fixture realism، وstate evidence.

تم تفكيك هذه العناصر إلى findings مستقلة في `02_EXHAUSTIVE_VISUAL_DESIGN_FINDING_LEDGER.md`.

## 13. Structured mixed-content completeness correction
في فحص completeness اللاحق تم التحقق من B09 الفعلي ومن exact-SHA content path بدل الاكتفاء بعبارة “mixed structure” العامة.

- عينة `ACCEPTANCE_BALANCED_6` تحتوي بكثافة على Markdown lists؛ عدد bullet lines في الوحدات الست المفحوصة تراوح تقريبًا بين 24 و93.
- أربع من الوحدات الست المفحوصة تحتوي Markdown tables؛ تراوحت table lines تقريبًا بين 21 و37 في الوحدات الجدولية.
- `W02AcceptanceSeeder::markdownBlocks()` يحذف separator rows ويحوّل كل table row إلى `• cell — cell ...` داخل paragraph buffer.
- الدالة نفسها تحول `-` / `*` list items إلى `• ...` داخل paragraph buffer.
- `LessonContentContract::BLOCK_REGISTRY` لا يحتوي `table` أو `list` block type.
- `LessonContentRenderer` يعرض non-technical content غير heading كـ paragraph؛ لذلك لا توجد semantic `<table>` أو `<ul>/<ol>/<li>` path في العقد الحالي.
- code/request/response/log لها block types تقنية صريحة، بينما callout/rules/boundaries موجودة في العقد؛ لكنها ما تزال تحتاج representative browser evidence كما هو مسجل في evidence matrix.

هذه ليست ملاحظة cosmetic واحدة. تم فصلها إلى:
- `W02-DA-064` — Table semantic flattening.
- `W02-DA-065` — List semantic flattening.

## 14. Requested-dimension closure map
هذه المصفوفة تمنع وجود أبعاد صامتة غير مدققة:

| Audit dimension | Closure / finding binding |
|---|---|
| Macro workspace geometry / TOP-LEFT-CENTER-RIGHT-BOTTOM | `007–015`, `039–042`, `053–054` |
| CENTER dominance / primary work-area utilization / dead space | `007–013`, `040` |
| Information density / whitespace balance | `008–009`, `015`, `035–037`, `047–050` |
| Typography / font scale / weights / line-height / metadata | `016–021` |
| Vertical/horizontal rhythm / padding / gaps / alignment | `011–018`, `025–028` |
| Cards / borders / elevation / background layering / grouping | `010`, `014–015`, `023`, `035` |
| Toolbar density / control hierarchy / micro-actions | `024–032` |
| Active/selected/disabled/hover/focus states | `029–032`, `041`, `046` |
| Section structure / hierarchy / navigation grammar | `003`, `006`, `020`, `033–034`, `062` |
| Provenance / source context / RIGHT richness | `004`, `035–037`, `050`, `054` |
| History / Compare / Recovery / BOTTOM deep work | `001–003`, `057–059` |
| Long-document behavior | `007–009`, `013`, `018`, `048`, evidence matrix |
| Mixed-content behavior | `043`, `048`, `064–065`, evidence matrix |
| Table rendering | `064` |
| List rendering | `065` |
| Code/request/response/log rendering | contract/code path proven; representative browser closure required under `048` + evidence matrix |
| Callout/rules/boundaries rendering | contract path exists; representative browser closure required under `048` + evidence matrix |
| Editor caret/selection/IME / Bidi | `043` |
| Modal/dialog composition | `005`, `046` |
| Empty/loading/error/unavailable | `060` |
| 1440 fidelity | direct evidence + `007–038`, `047–060` |
| ~1024 fidelity / responsive compaction | `025–026`, `039–042`, `046` |
| RTL / LTR technical islands / mixed Bidi | `043–045` |
| Accessibility-visible defects | `005`, `017`, `019`, `030–032`, `043`, `065` |
| Cross-surface consistency | `003`, `022–023`, `031`, `039`, `054`, shared-dependency file |
| Representative-data richness | `036`, `047–052`, `055–056`, `061–065` |

أي بُعد لا يملك defect مثبتًا تم ربطه صراحةً بـ evidence requirement أو contract disposition بدل اختلاق finding.


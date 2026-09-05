# 01_BLIND_REFERENCE_VS_CANDIDATE_FORENSICS

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


## 1. Blind-first conclusion

الفجوة الحالية ليست «cosmetic delta» واحدة. توجد خمس طبقات مستقلة يجب فصلها:

1. **Visualization grammar:** Tree / Path / Graph / Canvas لا تستخدم المساحة، العلاقات، الحالات والـ topology بالطريقة نفسها.
2. **Semantic truth:** Path لا يساوي canonical authored path حاليًا؛ Canvas لا يساوي saved map؛ Overlay availability لا يساوي zero state.
3. **Representative data:** اللقطات الحالية تستخدم fixture اختبارية محدودة.
4. **Runtime binding:** بعض العقود موجودة في source ولكن provider/runtime wiring غير موجود أو غير مخول.
5. **Shared shell:** Visualize يعيد بناء regions داخل CENTER، ما يضعف geometry حتى لو كان renderer نفسه صحيحًا.

## 2. Tree — blind comparison

### مثبت إيجابيًا
- `TreeView` و `TreeBranch` هما renderer حقيقيان وبنية recursive، بينما يستهلك Tree علاقات containment فقط.
- expand/collapse موجود فعليًا؛ ليس صحيحًا وصفه بأنه absent.
- KU/capability لديه تمييز لوني جزئي.
- technical IDs معزولة LTR في المواضع الأساسية.
- prerequisite لا يتحول إلى parent في Tree.

### Material deltas
- الدليل الحالي لا يمر إلا عبر `PATH-001 → six Test KU`; لذلك hierarchy fidelity لا يمكن إغلاقها.
- لا يتوفر human-label provenance لـ `domain` و `capability_cluster` في projection الحالي.
- `buildTree()` يسقط أي incoming containment ثانٍ لنفس node؛ هذه ليست مشكلة fixture فقط بل projection risk حقيقي.
- fallback label يمكن أن يكرر نفس ID مرتين.
- domain/cluster لا يملكان grammar بصريًا مستقلًا.
- disclosure منفصل عن parent row.
- relation pill `contains` متكرر على كل child ويزاحم hierarchy.
- RIGHT يكون neutral رغم وجود active scope؛ active/current/selected/focused ليست grammar متماسكة.
- CENTER under-utilization ناتج من sparse data **ومن** nested region ownership/row composition معًا.

## 3. Path — blind comparison

### مثبت إيجابيًا
- Path renderer مستقل فعليًا.
- اتجاه progression مرسوم LTR داخل صفحة RTL، وهو قرار صحيح semantic direction.
- local horizontal overflow موجود بدل إجبار document-level scroll.
- prerequisite-derived copy لا تختلق completion/mastery.

### Material deltas
- backend لا ينتج canonical pathway order؛ pathway metadata تستخدم membership فقط.
- connectors الحالية هي stage-header stubs، وليست topology connectors بين cards.
- branch/fork/join ليست grammar مستقلة.
- current/previous/next غير موجودة، ولا يجوز اختلاقها بلا authority.
- incoming relations تُعرض كـ raw technical endpoint IDs.
- `derivePathStages()` يحول cycle إلى final stage عادية بلا warning.
- same-rank nodes تُرتب lexical by ID، ما قد يبدو كـ authored order.
- supporting reference يستخدم canvas كاملًا لبنية الرحلة؛ candidate يستخدم شريطًا صغيرًا في أعلى CENTER.
- no ~1024 proof، ولا branch/cycle/empty evidence.

## 4. Focused Graph / Relationship — blind comparison

### مثبت إيجابيًا
- unique visual nodes.
- typed edges.
- arrow `from → to` لا ينقلب بسبب RTL.
- edge labels موجودة فعليًا؛ وصفها بأنها absent سيكون خاطئًا.
- selected-node focused layout موجود في source.
- تتوفر pointer pan و keyboard pan/zoom و wheel zoom و FIT في source.
- تم إثبات edge selection و Back/Forward و stale pruning في DataClone evidence.

### Material deltas
- لا توجد legend رغم أن color/dash يحمل semantic type.
- default-fit labels صغيرة جدًا.
- layout ثابت على 960 logical units ولا يملك collision-aware routing.
- midpoint edge labels لا تملك collision handling.
- edge selection لا تصبح relation-centered layout لأن layout يستقبل `selectedNodeId` فقط.
- selected-edge dominance ضعيفة مقابل crossing edges.
- structural node kinds مسطحة بصريًا باستثناء KU مقابل “structural”.
- inbound/outbound geometry غير مسماة كـ semantic groups.
- no hover/pre-inspection state.
- isolated/non-focus nodes تُلقى في bottom grid بلا دلالة معلنة.
- current data exercises only containment + prerequisite.
- node-selected focused screenshot و~1024 runtime evidence مفقودان.

## 5. Canvas — blind comparison

### مثبت إيجابيًا
- Canvas ليست list؛ هي SVG spatial field.
- node movement representation-only ومقيد بالجلسة.
- لا يوجد localStorage pseudo-persistence.
- لا يوجد Save/New مزيف.
- canonical containment/relationships لا تتغير في projection tests.
- UI يصرح بأن الحركة representation-only؛ هذه نقطة صحيحة يجب حمايتها.

### Material deltas
- default Canvas layout هو حرفيًا `layoutFocusedGraph(..., null)`؛ أي أن spatial grammar تبدأ كنسخة Graph.
- pointer-background pan غير موجود رغم أن keyboard camera pan موجود.
- Canvas edges بلا relation labels.
- لا توجد legend.
- selected edge لا تتغير بصريًا في CENTER؛ selection تظهر في RIGHT فقط.
- x/y coordinates دائمة الظهور رغم أنها session representation metadata.
- movement before/after browser proof مفقود.
- Saved Map remains authority-gated؛ لا يجوز تحويل ذلك إلى local implementation request.
- UI يخلط لغويًا بين `عالم العرض` كـ filter وبين current world/Map في LEFT.

## 6. Shared geometry and semantics

- shared `CepWorkspaceLayout` يملك region/panel model، لكن Visualize يبني workspace داخليًا ثانيًا.
- هذا يفسر جزءًا من CENTER under-utilization ويخلق collision في ~1024 context model.
- current RIGHT is selection-only; active scope does not populate useful neutral context.
- no exact-current ~1024 Visualize screenshot exists.
- current fixture does not stress long Arabic/English labels, focus order or mixed Bidi.

## 7. What is **not** a defect

- عدم وجود Saved Map persistence ليس إذنًا لتنفيذها؛ هو authority dependency.
- عدم وجود coverage/progress/evidence/mastery data ليس zero state؛ current NO_DATA/NO_AUTHORITY handling صحيح.
- اختلاف عدد nodes/labels/colors عن generated reference ليس defect تلقائيًا.
- غياب pixel reference لـ Canvas ليس defect؛ contract authority is sufficient.
- استخدام test fixture لا يجعل كل whitespace defect؛ تم فصل data-shape factor عن composition defects.

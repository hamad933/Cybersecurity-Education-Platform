(function () {
  "use strict";

  var viewMeta = {
    dashboard: ["اليوم", "لوحة المتابعة"],
    sources: ["المصادر", "مراجعة المصدر"],
    knowledge: ["المعرفة", "قارئ ومحرر الدرس"],
    curriculum: ["المنهج", "بنية القدرات"],
    lab: ["المختبر", "VS-001 — Windows Authorization Decision"],
    enterprise: ["المؤسسة", "Enterprise Designer & Scenario Studio"],
    evidence: ["الأدلة", "الإتقان والمراجعة"],
    "manual-ai": ["الجسر اليدوي", "Manual AI Bridge"]
  };

  function badge(kind, text) {
    return '<span class="status-badge ' + kind + '">' + text + "</span>";
  }

  function header(title, subtitle, actions, statuses) {
    return [
      '<div class="workspace-header">',
      "<div><h1>" + title + "</h1><p>" + subtitle + "</p>",
      '<div class="status-row">' + statuses + "</div></div>",
      '<div class="workspace-actions">' + actions + "</div>",
      "</div>"
    ].join("");
  }

  function dashboard() {
    return [
      '<section class="workspace" aria-labelledby="page-title">',
      header(
        '<span id="page-title">صباح الخير، يا مالك المساحة</span>',
        "هذه قائمة عمل تفسيرية مبنية على فجوات القدرة والأدلة والمراجعات، وليست نسبة إنجاز.",
        '<button class="button primary" type="button" data-action="notice" data-message="تصميم فقط: فتح بند التعلم التالي">ابدأ بند اليوم</button>',
        badge("published", "المساحة المحلية سليمة") + badge("warning", "3 قرارات تحتاج مراجعة")
      ),
      '<div class="stats-grid">',
      '<article class="card stat"><small>مراجعات مستحقة</small><strong>4</strong><span class="subtle">فشلان + تحقق احتفاظ</span></article>',
      '<article class="card stat"><small>أدلة تحتاج قراراً</small><strong>3</strong><span class="subtle">كلها SIMULATED</span></article>',
      '<article class="card stat"><small>مسودات معرفة</small><strong>7</strong><span class="subtle">2 محجوبة بمصدر</span></article>',
      '<article class="card stat"><small>عمليات معالجة</small><strong>1</strong><span class="subtle">فهرسة محلية</span></article>',
      "</div>",
      '<div class="dashboard-grid">',
      '<article class="card card-pad"><span class="eyebrow">لماذا الآن؟</span><h2>قائمة التعلم اليومية</h2>',
      '<ul class="queue-list">',
      '<li class="queue-item"><span class="queue-icon">1</span><div><strong><bdi dir="ltr">KU-AD-02</bdi> — قرار تفويض Windows</strong><small>أخطأت في أثر Explicit Deny أمس؛ أعد حالة سالبة.</small></div>' + badge("failed", "مراجعة فشل") + "</li>",
      '<li class="queue-item"><span class="queue-icon">2</span><div><strong>الاحتفاظ: تفسير <bdi dir="ltr">DACL</bdi></strong><small>مرّ 28 يوماً على آخر دليل مقبول.</small></div>' + badge("warning", "مستحق اليوم") + "</li>",
      '<li class="queue-item"><span class="queue-icon">3</span><div><strong>إغلاق فجوة مصدر</strong><small>حدد Windows baseline قبل نشر المحتوى التقني.</small></div>' + badge("warning", "محجوب") + "</li>",
      "</ul></article>",
      '<aside class="card card-pad"><span class="eyebrow">الحقيقة التشغيلية</span><h2>حالة المساحة</h2>',
      '<dl><div class="kv"><dt>آخر نسخة احتياطية</dt><dd><bdi dir="ltr">NOT YET IMPLEMENTED</bdi></dd></div>',
      '<div class="kv"><dt>اتصال عام</dt><dd>غير مطلوب</dd></div>',
      '<div class="kv"><dt>AI</dt><dd>Manual Bridge فقط</dd></div>',
      '<div class="kv"><dt>دليل حديث</dt><dd>' + badge("simulated", "SIMULATED") + "</dd></div></dl>",
      '<div class="warning-box">لا يدّعي هذا الإثبات وجود منتج أو منهج مكتمل أو قدرة حقيقية.</div></aside>',
      "</div></section>"
    ].join("");
  }

  function sources() {
    return [
      '<section class="workspace" aria-labelledby="page-title">',
      header(
        '<span id="page-title">مكتبة المصادر ومراجعة الدليل</span>',
        "افصل الحفظ والاستخراج والادعاء والسلطة. اسم الملف لا يمنح سلطة.",
        '<button class="button" type="button" data-action="notice" data-message="تصميم فقط: مسار اختيار مصدر محلي">تسجيل مصدر</button><button class="button primary" type="button" data-action="notice" data-message="تصميم فقط: حفظ قرار المراجعة">حفظ قرار المراجعة</button>',
        badge("published", "CUSTODY VERIFIED") + badge("warning", "PRIMARY AUTHORITY GAP")
      ),
      '<div class="three-pane">',
      '<aside class="pane"><div class="pane-head"><h2>المكتبة</h2><span class="chip">80 corpus</span></div>',
      '<button class="source-row selected" type="button"><strong>CKV-022 Windows Access Control</strong><bdi dir="ltr">sha256: 9a4f…c21d</bdi></button>',
      '<button class="source-row" type="button"><strong>AD Pilot — KNOWLEDGE_UNITS</strong><bdi dir="ltr">PILOT_SCOPE_SUPPORT</bdi></button>',
      '<button class="source-row" type="button"><strong>Product Charter</strong><bdi dir="ltr">PRODUCT_REQUIREMENT_SUPPORT</bdi></button>',
      '<button class="source-row" type="button"><strong>NAC Lecture 1,2</strong><bdi dir="ltr">ACADEMIC_SUPPORT</bdi></button></aside>',
      '<article class="pane"><div class="pane-head"><h2>المقطع المحدد</h2>' + badge("draft", "REVIEWED SEGMENT") + "</div>",
      '<div class="pane-body"><p class="subtle technical">SourceSegment SE-003-041 · lines 118–164</p>',
      '<h3>Access token and ordered access-check reasoning</h3>',
      '<p>تمثيل مشتق ومختصر: يربط القرار بهوية المستخدم، مجموعات الرمز، الامتيازات، واصف الأمان، وترتيب <bdi dir="ltr">ACE</bdi> وقناع الوصول.</p>',
      '<div class="definition-callout"><strong>حد الادعاء</strong><p>دعم تقني محلي مؤقت للنطاق المحدد؛ لا يثبت سلوك كل إصدار أو كل نوع كائن.</p></div>',
      '<div class="provenance"><strong>مرساة المصدر</strong><p><bdi dir="ltr">source-vault/originals/.../CKV-022_...md#L118-L164</bdi></p><span class="chip">digest matched</span> <span class="chip">exact range</span></div>',
      '<h3>قرار المراجع</h3><div class="status-row"><button class="button" type="button" data-action="notice" data-message="تم اختيار: دعم محدود (تصميم فقط)">دعم محدود</button><button class="button" type="button" data-action="notice" data-message="تم اختيار: سياق فقط (تصميم فقط)">سياق فقط</button><button class="button danger" type="button" data-action="notice" data-message="تم اختيار: تعارض غير محسوم (تصميم فقط)">تعارض</button></div>',
      "</div></article>",
      '<aside class="pane"><div class="pane-head"><h2>السلطة والأصل</h2></div><div class="pane-body"><dl>',
      '<div class="kv"><dt>الدور</dt><dd><bdi dir="ltr">TECHNICAL_CONTENT_SUPPORT</bdi></dd></div>',
      '<div class="kv"><dt>الثقة</dt><dd><bdi dir="ltr">HIGH_WITHIN_LOCAL_SCOPE</bdi></dd></div>',
      '<div class="kv"><dt>الإصدار</dt><dd>' + badge("warning", "UNRESOLVED") + "</dd></div>",
      '<div class="kv"><dt>السلطة الأولية</dt><dd>Microsoft/Open Specs مطلوبة</dd></div>',
      '<div class="kv"><dt>الاستخراج</dt><dd><bdi dir="ltr">PASS_WITH_WARNINGS</bdi></dd></div>',
      "</dl><div class=\"warning-box\">النشر التقني لـ <bdi dir=\"ltr\">VS-001</bdi> محجوب حتى حل قرار الإصدار والسلطة.</div></div></aside>",
      "</div></section>"
    ].join("");
  }

  function knowledge() {
    return [
      '<section class="workspace" aria-labelledby="page-title">',
      header(
        '<span id="page-title"><bdi dir="ltr">Knowledge Studio</bdi> — درس قرار التفويض</span>',
        "وضع القراءة والتحرير والمقارنة في سياق واحد؛ المنشور غير قابل للتعديل.",
        '<button class="button" type="button" data-action="add-block">+ إضافة Block</button><button class="button indigo" type="button" data-action="notice" data-message="تصميم فقط: فتح مقارنة المراجعة">مقارنة المسودة</button>',
        badge("draft", "DRAFT r3") + badge("published", "PUBLISHED r2") + badge("warning", "2 unresolved citations")
      ),
      '<div class="three-pane">',
      '<aside class="pane"><div class="pane-head"><h2>مخطط الدرس</h2></div><div class="pane-body hierarchy">',
      '<button class="tree-row selected" type="button">1. نموذج القرار</button><button class="tree-row" type="button">2. Token و SIDs</button><button class="tree-row" type="button">3. DACL / ACE order</button><button class="tree-row" type="button">4. Micro Practice</button><button class="tree-row" type="button">5. Guided Lab</button>',
      "</div></aside>",
      '<article class="pane"><div class="pane-head"><h2>Structured Edit</h2><span class="chip technical">KU-AD-02</span></div>',
      '<div class="editor-toolbar" aria-label="أدوات البلوكات"><button class="tool" type="button" data-action="add-block">فقرة +</button><button class="tool" type="button" data-action="add-block">تحذير +</button><button class="tool" type="button" data-action="add-block">Command +</button><button class="tool" type="button" data-action="add-block">Citation +</button></div>',
      '<div class="pane-body" id="block-canvas">',
      '<section class="block selected" data-block><span class="block-type">HEADING · BLK-041</span><div class="block-controls"><button type="button" data-action="move-down" aria-label="نقل البلوك لأسفل">↓</button></div><h2>كيف يُفسَّر قرار الوصول؟</h2></section>',
      '<section class="block" data-block><span class="block-type">PARAGRAPH · BLK-042</span><div class="block-controls"><button type="button" data-action="move-down" aria-label="نقل البلوك لأسفل">↓</button></div><p>ابدأ بالـ <bdi dir="ltr">principal</bdi> ورمز الوصول وقناع الحقوق المطلوب، ثم افحص واصف الأمان وترتيب <bdi dir="ltr">ACE</bdi> ضمن النطاق المعتمد.</p></section>',
      '<section class="block" data-block><span class="block-type">TOGGLE · BLK-043</span><div class="block-controls"><button type="button" data-action="move-down" aria-label="نقل البلوك لأسفل">↓</button></div><button class="disclosure" type="button" data-action="toggle" aria-expanded="false"><span>عرض حالة Explicit Deny</span><span aria-hidden="true">⌄</span></button><div class="toggle-content" hidden><p>حالة تمثيلية توضّح أثر ACE رفض صريح ضمن ترتيب معتمد؛ ليست ادعاءً شاملاً عن Windows.</p></div></section>',
      '<section class="block" data-block><span class="block-type">COMMAND · BLK-044</span><div class="block-controls"><button type="button" data-action="move-down" aria-label="نقل البلوك لأسفل">↓</button></div><pre dir="ltr">whoami /groups\n# DISPLAY ONLY — NOT EXECUTED</pre></section>',
      '<section class="block" data-block><span class="block-type">CITATION · BLK-045</span><p><bdi dir="ltr">CKV-022 · SE-003-041 · lines 118–164 · sha256: 9a4f…c21d</bdi></p></section>',
      "</div></article>",
      '<aside class="pane"><div class="pane-head"><h2>Inspector</h2></div><div class="pane-body"><dl>',
      '<div class="kv"><dt>Block</dt><dd><bdi dir="ltr">BLK-041</bdi></dd></div><div class="kv"><dt>Type</dt><dd><bdi dir="ltr">HEADING@1</bdi></dd></div><div class="kv"><dt>Direction</dt><dd><bdi dir="ltr">RTL + isolated LTR</bdi></dd></div><div class="kv"><dt>Validation</dt><dd>' + badge("published", "PASS") + "</dd></div>",
      "</dl><h3>Revision impact</h3><p class=\"subtle\">Lab: review recommended<br>Practice: unaffected<br>Scenario: potentially outdated</p><div class=\"warning-box\">النشر محجوب بقرار Windows baseline والمرجع الأولي.</div></div></aside>",
      "</div></section>"
    ].join("");
  }

  function curriculum() {
    return [
      '<section class="workspace" aria-labelledby="page-title">',
      header(
        '<span id="page-title">منهج القدرات</span>',
        "هذه بنية توسع: 16 Domain و53 Cluster و106 Capability و96 KU مرشحاً، وليست منهجاً مكتملاً.",
        '<button class="button primary" type="button" data-action="notice" data-message="تصميم فقط: فتح Path Template">عرض Path Template</button>',
        badge("warning", "EXPANSION ARCHITECTURE") + badge("draft", "PROVISIONAL KUs")
      ),
      '<div class="dashboard-grid">',
      '<article class="card card-pad"><span class="eyebrow">Domain → Cluster → Capability → KU</span><div class="hierarchy">',
      '<div class="level"><span class="technical">D03</span><strong>Identity, Access and Directory Security</strong><p class="subtle">HIGH_WITHIN_LOCAL_SCOPE — لا يمتد للصدق الخارجي.</p></div>',
      '<div class="level"><span class="technical">CL-D03-03</span><strong>Authorization Decisions</strong></div>',
      '<div class="level"><span class="technical">CAP-D03-03-01</span><strong>Explain a bounded Windows authorization result</strong></div>',
      '<div class="level"><span class="technical">KU-AD-02</span><strong>Tokens, SIDs, ACLs and access masks</strong><div class="status-row">' + badge("draft", "PROVISIONAL SEED") + badge("warning", "PRIMARY SOURCE GAP") + "</div></div>",
      "</div></article>",
      '<aside class="card card-pad"><h2>حدود القدرة</h2><p>يشمل قراراً محدوداً مع principal وtoken وdescriptor وACE order وmask. يستبعد العمليات الحية، إساءة الاعتماد، والتعميم على كل Windows.</p>',
      '<dl><div class="kv"><dt>Prerequisite</dt><dd><bdi dir="ltr">KU-D03-003</bdi></dd></div><div class="kv"><dt>Related Domain</dt><dd><bdi dir="ltr">D05 · CONTEXT REUSE</bdi></dd></div><div class="kv"><dt>Lifecycle</dt><dd><bdi dir="ltr">PRACTICAL_SIMULATOR</bdi></dd></div><div class="kv"><dt>Real-Lab</dt><dd>Optional لهذا الادعاء</dd></div></dl></aside>',
      "</div>",
      '<div class="card card-pad" style="margin-top:.9rem"><div class="table-scroll"><table class="coverage-table"><caption>ملخص تغطية مختار — لا يعني اكتمال المنهج</caption><thead><tr><th>Domain</th><th>Confidence</th><th>v1</th><th>الفجوة الدقيقة</th></tr></thead><tbody>',
      '<tr><td>D03 Identity</td><td>HIGH_WITHIN_LOCAL_SCOPE</td><td>VS-001 seed</td><td>Windows baseline + Microsoft/Open Specs</td></tr>',
      '<tr><td>D10 Forensics</td><td>LOW</td><td>Selective</td><td>Artifacts, tools, version and real transfer</td></tr>',
      '<tr><td>D12 Cloud</td><td>LOW</td><td>Selective</td><td>Primary provider authorities</td></tr>',
      '<tr><td>D15 Specialized</td><td>LOW</td><td>Post-v1</td><td>Physical and practical evidence</td></tr>',
      "</tbody></table></div></div></section>"
    ].join("");
  }

  function lab() {
    return [
      '<section class="workspace" aria-labelledby="page-title">',
      header(
        '<span id="page-title"><bdi dir="ltr">VS-001 — Windows Authorization Decision</bdi></span>',
        "مختبر محاكاة حتمي يفسّر قراراً محدوداً. لا ينفّذ أوامر ولا يثبت سلوك Windows الحقيقي.",
        '<button class="button" type="button" data-action="reset-lab">إعادة ضبط</button><button class="button" type="button" data-action="replay-lab">Replay</button><button class="button primary" type="button" data-action="simulate">Evaluate simulated decision</button>',
        badge("simulated", "SIMULATED") + badge("draft", "Scenario r4") + badge("warning", "Rule set provisional")
      ),
      '<div class="warning-box" style="margin-bottom:.8rem"><strong>حد الحقيقة:</strong> Windows baseline وMicrosoft/Open Specifications غير محسومين. النتيجة التالية إثبات تصميم تمثيلي فقط.</div>',
      '<div class="lab-grid">',
      '<aside class="pane"><div class="pane-head"><h2>Decision input</h2></div><div class="pane-body">',
      '<div class="field"><label for="principal">Principal / User SID</label><input id="principal" dir="ltr" value="S-1-5-21-1001"></div>',
      '<div class="field"><label for="groups">Group SIDs</label><input id="groups" dir="ltr" value="S-1-5-32-545; S-1-5-21-2100"></div>',
      '<div class="field"><label for="privileges">Enabled privileges</label><input id="privileges" dir="ltr" value="SeChangeNotifyPrivilege"></div>',
      '<div class="field"><label for="target">Target object</label><input id="target" dir="ltr" value="OBJ-FINANCE-REPORT"></div>',
      '<div class="field"><label for="mask">Requested mask</label><input id="mask" dir="ltr" value="0x00000003 · READ|WRITE"></div>',
      '<div class="field"><label for="mapping">Generic mapping</label><select id="mapping" dir="ltr"><option>FILE_OBJECT_V1_PROVISIONAL</option><option>UNDECLARED</option></select></div>',
      '<button class="button" type="button" data-action="notice" data-message="Hint recorded: inspect Explicit Deny before allow">طلب تلميح</button>',
      "</div></aside>",
      '<article class="pane"><div class="pane-head"><h2>Ordered DACL and explanation</h2><span class="chip technical">RUN-DEMO-007</span></div><div class="pane-body">',
      '<div class="trace-step"><span class="trace-num">1</span><div><strong>Normalize requested rights</strong><p class="subtle technical">READ|WRITE → remaining 0x00000003</p></div></div>',
      '<div class="trace-step"><span class="trace-num">2</span><div><strong>ACE-01 · Explicit DENY</strong><p class="subtle technical">Trustee S-1-5-21-2100 · mask WRITE · applicable</p></div></div>',
      '<div class="trace-step"><span class="trace-num">3</span><div><strong>ACE-02 · Explicit ALLOW</strong><p class="subtle technical">Trustee S-1-5-21-1001 · mask READ|WRITE</p></div></div>',
      '<div class="result-panel" id="lab-result"><div class="warning-box"><strong>DENIED (simulated)</strong><p>الحالة التمثيلية: ACE-01 ترفض WRITE قبل أن يُستكمل الطلب. READ وحدها ليست الطلب الكامل.</p><pre dir="ltr">result: DENIED\nreason_code: EXPLICIT_DENY_MATCH\nremaining_mask: 0x00000002\norigin: SIMULATED\ntrace_digest: 8b2a…7f11</pre></div></div>',
      "</div></article>",
      '<aside class="pane"><div class="pane-head"><h2>Evidence</h2></div><div class="pane-body"><dl>',
      '<div class="kv"><dt>Origin</dt><dd>' + badge("simulated", "SIMULATED") + "</dd></div><div class=\"kv\"><dt>Baseline</dt><dd><bdi dir=\"ltr\">EBR-004</bdi></dd></div><div class=\"kv\"><dt>Scenario</dt><dd><bdi dir=\"ltr\">SDR-004</bdi></dd></div><div class=\"kv\"><dt>Rule set</dt><dd><bdi dir=\"ltr\">AUTHZ-PROV-01</bdi></dd></div><div class=\"kv\"><dt>Mastery</dt><dd>Positive + negative required</dd></div>",
      '</dl><button class="button indigo" type="button" data-action="notice" data-message="تصميم فقط: معاينة دليل SIMULATED">معاينة الدليل</button><h3>Failure review</h3><p class="subtle">إذا اخترت ALLOW أو تجاهلت ACE-01، تُجدول مراجعة محددة لأثر Explicit Deny.</p></div></aside>',
      "</div></section>"
    ].join("");
  }

  function enterprise() {
    return [
      '<section class="workspace" aria-labelledby="page-title">',
      header(
        '<span id="page-title"><bdi dir="ltr">Enterprise Designer &amp; Scenario Studio</bdi></span>',
        "الكتالوجات دائمة؛ التعريف منشور بإصدار؛ التشغيل نسخة معزولة لا تغيّر Enterprise Baseline.",
        '<button class="button" type="button" data-action="notice" data-message="Design proof validation: all references resolve; one authority warning">Validate scenario</button><button class="button primary" type="button" data-action="notice" data-message="تصميم فقط: بدء Run معزول">Fork isolated Run</button>',
        badge("published", "BASELINE r4") + badge("draft", "SCENARIO DRAFT r5") + badge("simulated", "RUN ISOLATED")
      ),
      '<div class="warning-box" style="margin-bottom:.8rem">Scenario Run ينسخ الحالة إلى نطاق مستقل. أي تحسين ينتج Baseline Change Proposal ولا يكتب في baseline.</div>',
      '<div class="studio-grid">',
      '<aside class="pane"><div class="pane-head"><h2>كتالوج المؤسسة</h2></div><div class="pane-body hierarchy">',
      '<div class="level"><strong>Organization</strong><span class="technical">ORG-NORTH</span></div><div class="level"><strong>Identity</strong><span class="technical">ID-FIN-ANALYST</span></div><div class="level"><strong>Group</strong><span class="technical">GRP-FIN-READERS</span></div><div class="level"><strong>Policy</strong><span class="technical">POL-ACCESS-04</span></div><div class="level"><strong>Control</strong><span class="technical">CTL-LEAST-PRIV</span></div>',
      "</div></aside>",
      '<article class="pane"><div class="pane-head"><h2>Graph + timeline</h2><span class="chip">list equivalent available</span></div>',
      '<div class="canvas" role="img" aria-label="رسم تمثيلي لثلاث عقد: حالة أولية ثم قرار تفويض ثم تحقق دليل"><div class="connector ab"></div><div class="connector bc"></div><div class="node a"><strong>Initial state</strong><bdi dir="ltr">BASELINE EBR-004</bdi><small>Token + object</small></div><div class="node b"><strong>Authorization decision</strong><bdi dir="ltr">ACE evaluation</bdi><small>Positive / negative</small></div><div class="node c"><strong>Verify evidence</strong><bdi dir="ltr">SIMULATED trace</bdi><small>Mastery input</small></div></div>',
      "</article>",
      '<aside class="pane"><div class="pane-head"><h2>Properties & validation</h2></div><div class="pane-body"><div class="timeline"><div class="time-row"><bdi dir="ltr">T+00</bdi> Fork baseline</div><div class="time-row"><bdi dir="ltr">T+05</bdi> Submit decision</div><div class="time-row"><bdi dir="ltr">T+07</bdi> Capture trace</div></div>',
      '<h3>Validation</h3><p>' + badge("published", "12 references resolved") + "</p><p>" + badge("warning", "Windows authority unresolved") + '</p><button class="button danger" type="button" data-action="notice" data-message="لا يوجد حذف فعلي في إثبات التصميم">حذف المسودة…</button></div></aside>',
      "</div></section>"
    ].join("");
  }

  function evidence() {
    return [
      '<section class="workspace" aria-labelledby="page-title">',
      header(
        '<span id="page-title">Evidence, Mastery & Review</span>',
        "الحالة مبنية على أدلة وقاعدة إصدارية؛ لا توجد نسبة إكمال مبسطة.",
        '<button class="button primary" type="button" data-action="notice" data-message="تصميم فقط: فتح مراجعة الفشل">ابدأ المراجعة ذات الأولوية</button>',
        badge("simulated", "3 SIMULATED") + badge("warning", "1 RETENTION DUE") + badge("failed", "2 FAILED SIGNALS")
      ),
      '<article class="card card-pad"><span class="eyebrow">Candidate mastery state</span><div class="mastery-ladder" aria-label="سلم حالات الإتقان">',
      '<div class="mastery-step done">UNASSESSED</div><div class="mastery-step done">CAN_EXPLAIN</div><div class="mastery-step done">CAN_REPRODUCE</div><div class="mastery-step current">CAN_OBSERVE</div><div class="mastery-step">CAN_ANALYZE</div><div class="mastery-step">CAN_DEFEND_AND_VERIFY</div><div class="mastery-step">RETAINED_AND_TRANSFERABLE</div>',
      '</div><p class="subtle">Rule set <bdi dir="ltr">MR-CAP-D03-03-01@2 · PROVISIONAL_UNCALIBRATED</bdi></p></article>',
      '<div class="dashboard-grid">',
      '<article class="card card-pad"><h2>Evidence ledger</h2>',
      '<div class="queue-item"><span class="queue-icon">✓</span><div><strong>Positive allow case</strong><small><bdi dir="ltr">RUN-005 · trace 71d0…8a2c</bdi></small></div>' + badge("simulated", "SIMULATED") + "</div>",
      '<div class="queue-item"><span class="queue-icon">!</span><div><strong>Explicit deny case</strong><small>القرار صحيح، لكن تفسير remaining mask ناقص.</small></div>' + badge("warning", "NEEDS REVIEW") + "</div>",
      '<div class="queue-item"><span class="queue-icon">×</span><div><strong>Unsupported ACE case</strong><small>تم التخمين بدل UNSUPPORTED_STATE.</small></div>' + badge("failed", "REJECTED") + "</div>",
      "</article>",
      '<aside class="card card-pad"><h2>Failure-based review</h2>',
      '<div class="review-item"><strong>لا تخمّن حالة غير مدعومة</strong><p class="subtle">أعد الحالة مع توضيح limitation وrule version.</p><span class="chip">الأولوية 1</span></div>',
      '<div class="review-item"><strong>Remaining access mask</strong><p class="subtle">فسّر لماذا لا يكفي READ وحده لطلب READ|WRITE.</p><span class="chip">اليوم</span></div>',
      '<div class="review-item" style="border-color:var(--amber);background:#fffbf2"><strong>Retention check</strong><p class="subtle">سياق جديد بعد 28 يوماً.</p><span class="chip">متأخر يوم</span></div>',
      "</aside></div></section>"
    ].join("");
  }

  function manualAI() {
    return [
      '<section class="workspace" aria-labelledby="page-title">',
      header(
        '<span id="page-title"><bdi dir="ltr">Manual AI Bridge</bdi></span>',
        "تصدير يدوي → معالجة يدوية عبر ChatGPT Plus → استيراد غير موثوق → تحقق → قرار بشري. لا API ولا provider framework.",
        '<button class="button" type="button" data-action="ai-import">استيراد نتيجة تمثيلية</button><button class="button primary" type="button" data-action="ai-review">فتح Human Review</button>',
        badge("imported", "MANUAL ONLY") + badge("warning", "NO AUTO PUBLISH")
      ),
      '<div class="stage-list" id="ai-stages"><div class="stage done">DRAFT</div><div class="stage done">EXPORTED</div><div class="stage done">AWAITING MANUAL</div><div class="stage current" data-ai-current>RESULT IMPORTED</div><div class="stage">VALIDATION</div><div class="stage">HUMAN REVIEW</div></div>',
      '<div class="three-pane" style="margin-top:.8rem">',
      '<aside class="pane"><div class="pane-head"><h2>Export scope</h2></div><div class="pane-body"><p><bdi dir="ltr">PromptPackage PPK-014 / r2</bdi></p>',
      '<ul class="plain-list"><li>✓ SourceSegment SE-003-041</li><li>✓ Lesson draft blocks 41–45</li><li>✓ Requested schema v1</li><li>— Original source bytes excluded</li><li>— Owner notes excluded</li></ul>',
      '<dl><div class="kv"><dt>Files</dt><dd>4</dd></div><div class="kv"><dt>Bytes</dt><dd><bdi dir="ltr">18,442</bdi></dd></div><div class="kv"><dt>Digest</dt><dd><bdi dir="ltr">c1a9…44ee</bdi></dd></div></dl>',
      '<div class="warning-box">المستخدم نقل هذه الحزمة يدوياً إلى ChatGPT Plus خارج المنتج.</div></div></aside>',
      '<article class="pane"><div class="pane-head"><h2>Proposed diff</h2>' + badge("imported", "UNTRUSTED IMPORT") + "</div><div class=\"pane-body\">",
      '<div class="diff"><div class="old"><strong>قبل</strong><p>يفحص النظام ACEs ثم يسمح أو يرفض.</p></div><div class="new"><strong>مقترح</strong><p>يسجل النظام كل ACE قابلة للتطبيق وأثرها على remaining mask، أو يعيد حالة غير كافية.</p></div></div>',
      '<div class="provenance"><strong>Source references</strong><p><bdi dir="ltr">SE-003-041 · digest MATCH</bdi></p><p><bdi dir="ltr">MS-OPEN-SPEC · NOT PROVIDED</bdi> ' + badge("failed", "PROVENANCE GAP") + "</p></div>",
      '<h3>قرار لكل تغيير</h3><div class="status-row"><button class="button" type="button" data-action="notice" data-message="تصميم فقط: قبول إلى مسودة لا نشر">قبول إلى Draft</button><button class="button" type="button" data-action="notice" data-message="تصميم فقط: قبول جزئي">قبول جزئي</button><button class="button danger" type="button" data-action="notice" data-message="تصميم فقط: رفض المقترح">رفض</button></div>',
      "</div></article>",
      '<aside class="pane"><div class="pane-head"><h2>Validation</h2></div><div class="pane-body"><dl>',
      '<div class="kv"><dt>Archive safety</dt><dd>' + badge("published", "PASS") + "</dd></div><div class=\"kv\"><dt>Manifest</dt><dd>" + badge("published", "PASS") + '</dd></div><div class="kv"><dt>Schema</dt><dd>' + badge("published", "PASS") + "</dd></div><div class=\"kv\"><dt>Source refs</dt><dd>" + badge("failed", "FAILED") + '</dd></div><div class="kv"><dt>Publication</dt><dd>' + badge("warning", "DRAFT ONLY") + "</dd></div>",
      '</dl><p class="subtle">الثقة المكتوبة في النتيجة ليست دليلاً. القرار البشري لا يلغي workflow النشر.</p></div></aside>',
      "</div></section>"
    ].join("");
  }

  var renderers = {
    dashboard: dashboard,
    sources: sources,
    knowledge: knowledge,
    curriculum: curriculum,
    lab: lab,
    enterprise: enterprise,
    evidence: evidence,
    "manual-ai": manualAI
  };

  var workspace = document.getElementById("workspace");
  var nav = document.getElementById("global-nav");
  var menuButton = document.getElementById("menu-button");
  var status = document.getElementById("sr-status");

  function currentView() {
    var value = location.hash.replace("#", "");
    return renderers[value] ? value : "dashboard";
  }

  function announce(message) {
    status.textContent = "";
    window.setTimeout(function () { status.textContent = message; }, 20);
  }

  function showToast(message) {
    var old = document.querySelector(".toast");
    if (old) old.remove();
    var toast = document.createElement("div");
    toast.className = "toast";
    toast.setAttribute("role", "status");
    toast.textContent = message;
    document.body.appendChild(toast);
    announce(message);
    window.setTimeout(function () { toast.remove(); }, 2600);
  }

  function render() {
    var view = currentView();
    workspace.innerHTML = renderers[view]();
    document.getElementById("crumb").textContent = viewMeta[view][0] + " / " + viewMeta[view][1];
    document.querySelectorAll("[data-view-link]").forEach(function (link) {
      var active = link.getAttribute("data-view-link") === view;
      link.classList.toggle("active", active);
      if (active) link.setAttribute("aria-current", "page");
      else link.removeAttribute("aria-current");
    });
    nav.classList.remove("open");
    menuButton.setAttribute("aria-expanded", "false");
    document.title = viewMeta[view][1] + " — Task 004 Design Proof";
    var params = new URLSearchParams(location.search);
    var demo = params.get("demo");
    if (demo === "menu") {
      nav.classList.add("open");
      menuButton.setAttribute("aria-expanded", "true");
    }
    if (demo === "toggle") {
      var disclosure = workspace.querySelector("[data-action=toggle]");
      if (disclosure) {
        disclosure.setAttribute("aria-expanded", "true");
        var disclosureContent = disclosure.parentElement.querySelector(".toggle-content");
        if (disclosureContent) disclosureContent.hidden = false;
      }
    }
    if (demo === "result") {
      var demoResult = workspace.querySelector("#lab-result");
      if (demoResult) demoResult.classList.add("visible");
    }
    if (params.get("focus") === "1") {
      window.setTimeout(function () {
        var target = workspace.querySelector("button, a, input, select");
        if (target) target.focus();
      }, 80);
    }
  }

  menuButton.addEventListener("click", function () {
    var open = nav.classList.toggle("open");
    menuButton.setAttribute("aria-expanded", String(open));
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && nav.classList.contains("open")) {
      nav.classList.remove("open");
      menuButton.setAttribute("aria-expanded", "false");
      menuButton.focus();
    }
  });

  workspace.addEventListener("click", function (event) {
    var control = event.target.closest("[data-action]");
    if (!control) return;
    var action = control.getAttribute("data-action");
    if (action === "notice") showToast(control.getAttribute("data-message"));
    if (action === "toggle") {
      var content = control.parentElement.querySelector(".toggle-content");
      var expanded = control.getAttribute("aria-expanded") === "true";
      control.setAttribute("aria-expanded", String(!expanded));
      content.hidden = expanded;
      announce(expanded ? "تم طي المحتوى" : "تم توسيع المحتوى");
    }
    if (action === "add-block") {
      var canvas = document.getElementById("block-canvas");
      if (!canvas) return;
      var block = document.createElement("section");
      block.className = "block selected";
      block.setAttribute("data-block", "");
      block.innerHTML = '<span class="block-type">CALLOUT · NEW DESIGN CONCEPT</span><p>بلوك تمثيلي غير محفوظ. المنتج الحقيقي سيطبق schema validation وrevision workflow.</p>';
      canvas.appendChild(block);
      block.scrollIntoView({ block: "nearest" });
      showToast("أضيف بلوك تمثيلي إلى المسودة فقط");
    }
    if (action === "move-down") {
      var item = control.closest("[data-block]");
      if (item && item.nextElementSibling) {
        item.parentElement.insertBefore(item.nextElementSibling, item);
        showToast("أعيد ترتيب البلوك محلياً — غير محفوظ");
      }
    }
    if (action === "simulate") {
      var result = document.getElementById("lab-result");
      if (result) {
        result.classList.add("visible");
        result.scrollIntoView({ block: "nearest" });
        showToast("ظهرت نتيجة تمثيلية SIMULATED");
      }
    }
    if (action === "reset-lab") {
      var panel = document.getElementById("lab-result");
      if (panel) panel.classList.remove("visible");
      showToast("إعادة ضبط تمثيلية إلى snapshot الأصلي");
    }
    if (action === "replay-lab") {
      var replay = document.getElementById("lab-result");
      if (replay) replay.classList.add("visible");
      showToast("REPLAY MATCH — trace digest 8b2a…7f11 (design proof)");
    }
    if (action === "ai-import" || action === "ai-review") {
      var current = document.querySelector("[data-ai-current]");
      if (current) {
        current.textContent = action === "ai-import" ? "VALIDATION" : "HUMAN REVIEW";
        current.className = "stage current";
      }
      showToast(action === "ai-import" ? "تمثيل استيراد غير موثوق؛ لا معالجة حقيقية" : "تمثيل فتح قرار بشري؛ لا نشر تلقائي");
    }
  });

  window.addEventListener("hashchange", render);
  render();
}());

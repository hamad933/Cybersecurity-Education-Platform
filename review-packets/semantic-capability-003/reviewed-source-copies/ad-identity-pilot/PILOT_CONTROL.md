# AD Identity Educational Pilot — Pilot Control

> **Package status:** Design complete — practical execution not authorized yet  
> **Stop gate:** `STOP-AD-PILOT-DESIGN-001`  
> **Pilot model:** `Lightweight Education Operating System — File-First, Project-Anchored, Evidence-Based`  
> **Pilot domain:** `Active Directory Authentication, Authorization, Attack, Detection, and Defense`  
> **Version:** `0.1.0`  
> **Prepared:** `2026-07-21`

---

## 1. Purpose and binding constraints

هذا الملف هو نقطة التحكم الوحيدة في حالة الـPilot. لا يثبت نجاح أي قدرة بمجرد قراءة مصدر أو مشاهدة فيديو؛ النجاح يعتمد على Evidence قابلة لإعادة الفحص.

القيود الملزمة:

- العمل داخل مختبر مصرح ومملوك للمستخدم فقط.
- لا أهداف عامة، ولا أنظمة إنتاج، ولا حسابات حقيقية خارج المختبر.
- لا تنفيذ للمختبرات قبل موافقة صريحة بعد `STOP-AD-PILOT-DESIGN-001`.
- يبقى النظام `File-First` طوال هذا الـPilot.
- لا `SQLite`، ولا تطبيق، ولا Automation في هذه المرحلة.
- لا تعديل لمستودع منصة الأمن المستقبلية.
- لا تنفيذ لكل محتويات الدورات؛ تستخدم فقط Source Segments المختارة.
- `Canonical Knowledge Vault` طبقة Canonical قابلة للتصحيح وليست المصدر الوحيد أو الحقيقة النهائية.

---

## 2. Stable identifier convention

| الكيان | النمط | مثال | قاعدة الثبات |
|---|---|---|---|
| Source Record | `SRC-AD-NNN` | `SRC-AD-021` | لا يُعاد استخدامه حتى لو أُرشف المصدر |
| Source Segment | `SEG-AD-NNN` | `SEG-AD-034` | يشير دائماً إلى جزء محدد من Source Record |
| Knowledge Unit | `KU-AD-NN` | `KU-AD-03` | رقم ثابت طوال دورة حياة الـPilot |
| Lab | `LAB-AD-NN-NN` | `LAB-AD-03-01` | أول رقم يطابق KU المالكة |
| Evidence Item | `EVD-AD-NN-NNN` | `EVD-AD-03-002` | يُحجز مسبقاً للادعاء المطلوب إثباته |
| Review Trigger | `RT-AD-NN-NN` | `RT-AD-03-04` | مرتبط بفشل محدد قابل للتشخيص |
| Diagnostic Item | `DX-AD-NNN` | `DX-AD-011` | لا يتغير بعد بدء التشخيص |
| Gate 0 Item | `G0-AD-NNN` | `G0-AD-008` | شرط جاهزية سابق لأي Lab |
| Backlog Item | `BL-AD-NNN` | `BL-AD-017` | عنصر تنفيذي واحد واضح |

### 2.1 Traceability rule

يجب أن يمكن تتبع أي Evidence بالاتجاهين:

```text
Source Segment
→ Knowledge Unit
→ Lab
→ Evidence Item
→ Mastery Claim
→ Review Trigger عند الفشل
```

ولا نضيف Schema منفصل؛ الروابط تحفظ داخل الجداول في الملفات الأربعة.

---

## 3. Declared laboratory baseline — requires Gate 0 confirmation

هذه البيانات مستخلصة من سياق المشروع السابق، وتعد **Declared Baseline** لا **Verified Baseline** حتى إكمال Gate 0.

| المكوّن | القيمة المعلنة | حالة التحقق |
|---|---|---|
| Domain Controller | `DC01 — 10.10.10.10 — AD/DNS/DHCP/NPS` | Pending |
| Management host | `MGMT01 — VLAN30` | Pending |
| SIEM | `WAZUH01 — 10.10.40.10` | Pending |
| User endpoints | `CLIENT01`, `CLIENT02 — VLAN50` | Pending |
| DMZ server | `WEB01 — VLAN60` | خارج Core Pilot إلا عند الحاجة لشاهد خدمة |
| Identity VLAN | `VLAN10 — 10.10.10.0/24` | Pending |
| Admin VLAN | `VLAN20 — 10.10.20.0/24` | Pending |
| Management VLAN | `VLAN30 — 10.10.30.0/24` | Pending |
| Security VLAN | `VLAN40 — 10.10.40.0/24` | Pending |
| User VLAN | `VLAN50 — 10.10.50.0/24` | Pending |
| Declared policy | `ADM cannot manage directly; ADM → MGMT01 only` | Pending verification |
| Existing Wazuh rules | `110001–110007`, `110103–110104`, `110201+`, `110401+` families | Pending export and validation |

المعلومات التي لا تزال مجهولة ويجب تسجيلها في Gate 0:

- Domain FQDN وForest functional level.
- إصدارات Windows الدقيقة ومستوى التحديث.
- Wazuh manager/agent versions.
- Hypervisor ونوع Snapshot/Checkpoint.
- مزامنة الوقت الفعلية ومصدر NTP.
- Audit Policy الحالية.
- Event Channels المجموعة فعلياً.
- حساب الإدارة الذي سيُنشئ OU وحسابات الاختبار.
- وجود Backup صالح لـGPO وAD objects التجريبية.

---

# 4. Gate 0 — Authorization, Safety, Baseline, and Rollback

لا ينتقل أي Lab إلى `Ready` حتى تكون كل العناصر الحرجة `PASS`.

## 4.1 Gate 0 checklist

| ID | المتطلب | Evidence متوقعة | Critical | الحالة |
|---|---|---|---:|---|
| `G0-AD-001` | تصريح مكتوب بأن النطاق مختبر محلي مصرح فقط | Scope statement داخل هذا الملف | نعم | Pending |
| `G0-AD-002` | تأكيد عدم وجود Production أوهدف غير مصرح | Signed/dated confirmation | نعم | Pending |
| `G0-AD-003` | تحديد الأجهزة والحسابات داخل النطاق بالاسم وIP/Role | Scope table نهائية | نعم | Pending |
| `G0-AD-004` | تسجيل Baseline للإصدارات والتحديثات والأدوار | System inventory export | نعم | Pending |
| `G0-AD-005` | تسجيل Domain/Forest/Site/Subnet وDNS/NTP baseline | AD baseline export | نعم | Pending |
| `G0-AD-006` | Snapshot لـ`DC01` بطريقة آمنة ومتوافقة مع بيئة الـHypervisor | Snapshot record + timestamp | نعم | Pending |
| `G0-AD-007` | Snapshots لـ`CLIENT01`, `CLIENT02`, `MGMT01`, `WAZUH01` | Snapshot records | نعم | Pending |
| `G0-AD-008` | اختبار استعادة Snapshot على جهاز غير DC أولاً | Restore validation note | نعم | Pending |
| `G0-AD-009` | توثيق خطة Rollback لـDC/GPO/OU بدون الاعتماد على Snapshot وحده | Rollback procedure + backups | نعم | Pending |
| `G0-AD-010` | إنشاء OU معزولة باسم مقترح `OU=AD-PILOT-LAB` | AD object export | نعم | Pending |
| `G0-AD-011` | إنشاء Test Users/Groups/Computers فقط داخل OU التجريبية | Object inventory | نعم | Pending |
| `G0-AD-012` | عدم استخدام `Domain Admin` في الأنشطة اليومية للـPilot | Account-use policy | نعم | Pending |
| `G0-AD-013` | إنشاء Group تجريبية محدودة مثل `LAB-Pilot-Privileged-Operators` | ACL scope proof | نعم | Pending |
| `G0-AD-014` | تقييد صلاحيات المجموعة التجريبية إلى OU التجريبية | Delegation export | نعم | Pending |
| `G0-AD-015` | Backup لـGPOs التي ستُنشأ أوتُعدل | GPO backup record | نعم | Pending |
| `G0-AD-016` | تأكيد أن `MGMT01` هو المسار الإداري المعتمد | Firewall + access matrix | نعم | Pending |
| `G0-AD-017` | منع المسار الإداري المباشر من User VLAN حسب السياسة المعلنة | Negative connectivity test | نعم | Pending |
| `G0-AD-018` | Wazuh agents متصلة وتُرسل `Security` logs | Agent status + recent raw event | نعم | Pending |
| `G0-AD-019` | التحقق من جمع Kerberos/NTLM/GroupPolicy/NTLM Operational channels المطلوبة | Channel coverage matrix | نعم | Pending |
| `G0-AD-020` | اختبار وصول حدث Raw إلى Wazuh دون كتابة Rule جديدة | Raw-event validation | نعم | Pending |
| `G0-AD-021` | Backup لإعدادات Wazuh قبل أي تغيير | Config backup record | نعم | Pending |
| `G0-AD-022` | مراجعة Safety Constraints لكل Lab | Signed safety checklist | نعم | Pending |
| `G0-AD-023` | تحديد نافذة تنفيذ لا تتعارض مع أعمال أخرى في المختبر | Execution window | لا | Pending |
| `G0-AD-024` | تسجيل Owner للـPilot وOwner للRollback | Responsibility record | نعم | Pending |

## 4.2 Safety constraints

1. لا تُجرى تغييرات على `Default Domain Policy` أو`Default Domain Controllers Policy` ضمن الـPilot.
2. لا تُنفذ تقنيات `DCSync`, `Golden Ticket`, `Silver Ticket`, `DCShadow`, `NTDS.dit extraction`, أوCredential dumping من `LSASS`.
3. لا تُنشأ Trusts أوتُعدل Forest/Domain functional levels.
4. لا تُفعل Unconstrained Delegation في البيئة الأساسية.
5. Advanced delegation تبقى Optional داخل VM مخصصة ومعزولة وبعد موافقة مستقلة.
6. لا تُعطل SMB signing أوLDAP signing أوDefender على النطاق كله.
7. أي تغيير Audit/Enforcement يبدأ بـRead/Observe ثم Audit ثم Change محدود ثم Verification.
8. لا تستخدم بيانات اعتماد أوأسماء حسابات حقيقية في Evidence المنشورة.
9. PCAPs وEvent exports تحفظ داخل `evidence/` وتُعامل كمعلومات حساسة للمختبر.
10. كل Lab يجب أن يملك Rollback واضح قبل التنفيذ.

## 4.3 Gate 0 decision

- `PASS`: جميع العناصر الحرجة مكتملة ومراجعة.
- `CONDITIONAL PASS`: عنصر غير حرج فقط ما زال Pending ولا يؤثر في السلامة.
- `FAIL`: أي عنصر حرج Pending/Failed؛ يمنع بدء المختبرات.

---

# 5. Diagnostic Baseline

## 5.1 Scoring rubric

لكل Diagnostic Item:

| الدرجة | الوصف |
|---:|---|
| `0` | لا يستطيع تعريف المفهوم أوتنفيذ الخطوة |
| `1` | يتعرف إلى المصطلح ويشرح جزئياً بمساعدة ملاحظات |
| `2` | يشرح وينفذ مع مرجع، لكنه لا يشخّص Failure أوEvidence بالكامل |
| `3` | يشرح وينفذ ويشخّص ويحدد Evidence وDefense دون مساعدة جوهرية |

الحد الأقصى: `60` نقطة من `20` بنداً.

### Critical Diagnostic Items

العناصر التالية لا يجوز Challenge-Out منها بدرجة أقل من `2`:

`DX-AD-003`, `DX-AD-005`, `DX-AD-007`, `DX-AD-009`, `DX-AD-012`, `DX-AD-014`, `DX-AD-017`, `DX-AD-019`.

## 5.2 Diagnostic items

| ID | الاختبار | KU Mapping | Critical | Evidence التشخيص |
|---|---|---|---:|---|
| `DX-AD-001` | ارسم Forest/Domain/DC/Site/Subnet/OU relationships | `KU-AD-01` | لا | Diagram |
| `DX-AD-002` | اشرح DC Locator ودور DNS SRV Records | `KU-AD-01` | لا | Written explanation |
| `DX-AD-003` | أثبت أي DC اختاره Client ولماذا | `KU-AD-01` | نعم | Command output + reasoning |
| `DX-AD-004` | ميّز SID وGUID وDN وUPN | `KU-AD-01`,`KU-AD-02` | لا | Contrast table |
| `DX-AD-005` | اشرح Token × Security Descriptor × Requested Access | `KU-AD-02` | نعم | Access decision walkthrough |
| `DX-AD-006` | فسر Group SID وPrivilege وDeny-only SID وOwner | `KU-AD-02` | لا | Short analysis |
| `DX-AD-007` | شخّص Access Denied بعد Group membership change | `KU-AD-02` | نعم | Troubleshooting sequence |
| `DX-AD-008` | ميّز AS/TGS/AP exchanges وTGT/Service Ticket | `KU-AD-03` | لا | Sequence diagram |
| `DX-AD-009` | أثبت أن Session استخدمت Kerberos واربط `4768/4769/4624` | `KU-AD-03` | نعم | Event correlation |
| `DX-AD-010` | شخّص Failure سببه DNS أوTime أوSPN | `KU-AD-03` | لا | Failure tree |
| `DX-AD-011` | اشرح NTLM challenge-response وNetlogon pass-through | `KU-AD-04` | لا | Flow explanation |
| `DX-AD-012` | أثبت Kerberos مقابل NTLM في حالتي hostname وIP | `KU-AD-04` | نعم | Logs/trace comparison |
| `DX-AD-013` | اذكر Relay preconditions وTarget-side protections | `KU-AD-04` | لا | Preconditions table |
| `DX-AD-014` | نفّذ LDAP query واشرح Bind context والحقوق المطلوبة | `KU-AD-05` | نعم | Query + authorization explanation |
| `DX-AD-015` | ميّز Simple/SASL/LDAPS/StartTLS وSigning/CBT | `KU-AD-05` | لا | Contrast table |
| `DX-AD-016` | اشرح GPC/GPT وLSDOU وSecurity/WMI filtering | `KU-AD-06` | لا | Processing model |
| `DX-AD-017` | أثبت سبب تطبيق أوعدم تطبيق GPO باستخدام `gpresult` والOperational log | `KU-AD-06` | نعم | Troubleshooting evidence |
| `DX-AD-018` | ارسم Approved admin path ويحدد credential exposure points | `KU-AD-07` | لا | Tier/path diagram |
| `DX-AD-019` | صمم Detection قابلة للاختبار لـPrivileged Logon من Source غير معتمد | `KU-AD-08` | نعم | Detection design |
| `DX-AD-020` | ابنِ Timeline من أحداث فشل ثم نجاح ثم تغيير صلاحيات | `KU-AD-09` | لا | Mini timeline |

## 5.3 Global score interpretation

| النتيجة | القرار |
|---:|---|
| `0–24` | المسار الكامل، مع مراجعة prerequisites |
| `25–39` | المسار الكامل لكن بتقليل الشرح المعروف |
| `40–50` | Selected segments + Labs كاملة |
| `51–60` وكل Critical ≥2 | Evidence Challenges بدلاً من الشرح الأساسي |

## 5.4 Per-KU Challenge-Out

يمكن تجاوز شرح KU فقط عند تحقق الشروط الثلاثة:

1. متوسط Diagnostic Items الخاصة بها ≥`2.5`.
2. لا Critical Item أقل من `2`.
3. تنفيذ Evidence Challenge المختصر المطلوب في `KNOWLEDGE_UNITS.md`.

التجاوز لا يعفي من الـLab أوEvidence الأساسية للـPilot؛ يقلل الدراسة فقط.

---

# 6. Mastery states

| الحالة | المعنى | الانتقال المطلوب |
|---|---|---|
| `M0 — Unassessed` | لم يُشخّص | Diagnostic |
| `M1 — Explain` | تفسير صحيح للمفاهيم والحدود | Diagnostic/Recall |
| `M2 — Reproduce` | تنفيذ Lab بنجاح مع مرجع | Lab result |
| `M3 — Observe` | تفسير Logs/Traffic/State | Raw Evidence + analysis |
| `M4 — Defend and Verify` | تطبيق Defense وإثبات Positive/Negative tests | Verification package |
| `M5 — Retained and Transferable` | إعادة التطبيق لاحقاً وفي سياق جديد دون وصفة حرفية | Delayed micro-lab أوCapstone reuse |

- اكتمال الـPilot التشغيلي يتطلب `M4`.
- `M5` يُقاس بعد فترة ولا يؤخر إغلاق مرحلة التنفيذ الأولى.
- Course completion لا يرفع Mastery State تلقائياً.

---

# 7. Execution backlog

## 7.1 Design and readiness backlog

| ID | العنصر | Depends on | Exit condition | الحالة |
|---|---|---|---|---|
| `BL-AD-001` | مراجعة ملفات الـPilot واعتمادها | None | Explicit approval | Complete design / awaiting approval |
| `BL-AD-002` | تنفيذ Gate 0 authorization and scope | `BL-AD-001` | `G0-AD-001..003` PASS | Blocked |
| `BL-AD-003` | تسجيل Environment Baseline | `BL-AD-002` | `G0-AD-004..005` PASS | Blocked |
| `BL-AD-004` | إنشاء/اختبار Snapshots وRollback | `BL-AD-003` | `G0-AD-006..009` PASS | Blocked |
| `BL-AD-005` | إنشاء OU وحسابات/مجموعات الاختبار | `BL-AD-004` | `G0-AD-010..015` PASS | Blocked |
| `BL-AD-006` | توثيق approved admin path | `BL-AD-005` | `G0-AD-016..017` PASS | Blocked |
| `BL-AD-007` | Wazuh telemetry validation | `BL-AD-005` | `G0-AD-018..021` PASS | Blocked |
| `BL-AD-008` | Safety review وGate 0 decision | `BL-AD-002..007` | Gate 0 PASS | Blocked |
| `BL-AD-009` | تنفيذ Diagnostic Baseline | `BL-AD-008` | Scores recorded | Blocked |

## 7.2 Learning and evidence backlog

| ID | العنصر | Depends on | Exit condition | الحالة |
|---|---|---|---|---|
| `BL-AD-010` | `KU-AD-01` | `BL-AD-009` | `M4` or approved challenge path | Not started |
| `BL-AD-011` | `KU-AD-02` | `BL-AD-010` | `M4` | Not started |
| `BL-AD-012` | `KU-AD-03` | `BL-AD-010` | `M4` | Not started |
| `BL-AD-013` | `KU-AD-04` | `BL-AD-012` | `M4` | Not started |
| `BL-AD-014` | `KU-AD-05` | `BL-AD-010`,`BL-AD-011` | `M4` | Not started |
| `BL-AD-015` | `KU-AD-06` | `BL-AD-014` | `M4` | Not started |
| `BL-AD-016` | `KU-AD-07` core tiering/path scope | `BL-AD-011`,`BL-AD-015` | `M4` | Not started |
| `BL-AD-017` | `KU-AD-08` detections | `BL-AD-012..016` | 4 tested detections | Not started |
| `BL-AD-018` | Optional delegation micro-lab decision | `BL-AD-016` | Explicit approval or defer | Deferred by default |
| `BL-AD-019` | `KU-AD-09` Capstone | `BL-AD-010..017` | Final evidence package | Not started |
| `BL-AD-020` | D+7 retention challenge | `BL-AD-019` | Reproduction score | Not started |
| `BL-AD-021` | Pilot retrospective and Technology Gate | `BL-AD-020` | Decision recorded | Not started |

## 7.3 Recommended execution order

```text
Gate 0
→ Diagnostic
→ KU-AD-01
→ KU-AD-02
→ KU-AD-03
→ KU-AD-04
→ KU-AD-05
→ KU-AD-06
→ KU-AD-07 Core
→ KU-AD-08
→ KU-AD-09 Capstone
→ Retention Review
→ Technology Decision Gate
```

`KU-AD-08` يبدأ Telemetry design مبكراً، لكن Mastery لا يغلق قبل وجود Events من الوحدات السابقة.

---

# 8. Evidence naming and storage

## 8.1 File name format

```text
<Evidence-ID>__<Lab-ID>__<short-slug>__<UTC-timestamp>.<ext>
```

مثال:

```text
EVD-AD-03-002__LAB-AD-03-01__4769-service-ticket__20260730T181500Z.evtx
```

القواعد:

- الـEvidence ID ثابت، بينما يمكن إنتاج Revision جديدة بإضافة `__r02` قبل الامتداد.
- لا توضع كلمات مرور أوSecrets أوReal-world identifiers في الاسم.
- تستخدم UTC لتجنب تعارض Timezones.
- الملفات الثنائية تبقى كما هي؛ التحليل المختصر يسجل في `PILOT_CONTROL.md` عند التنفيذ.
- Evidence مشتقة من مصدر حساس لا تُرفع خارج المختبر دون Sanitization.

## 8.2 Allowed evidence types

- `.txt`, `.md`, `.csv`, `.json`, `.xml`
- `.evtx`
- `.pcap`, `.pcapng`
- `.html` مثل `gpresult`
- `.zip` لتجميع Export رسمي واحد فقط
- `.png` عند الحاجة البصرية؛ لا تستبدل Export نصي متاح

## 8.3 Evidence acceptance criteria

كل Evidence يجب أن تجيب:

1. ما Capability Claim؟
2. ما Initial State؟
3. ما Action أوStimulus؟
4. ما Expected Result؟
5. ما Actual Result؟
6. ما Raw Artifact؟
7. كيف فُسرت؟
8. ما Negative Test؟
9. ما Rollback/Final State؟

---

# 9. Pilot completion process

## 9.1 KU review

لكل KU:

1. مراجعة Diagnostic mapping.
2. تأكيد Selected Segments فقط.
3. مراجعة Lab Safety Card.
4. تنفيذ Lab بعد Gate 0.
5. جمع Evidence IDs المحجوزة.
6. مراجعة Positive/Negative results.
7. رفع Mastery State أوإنشاء Review Trigger.
8. تسجيل Source gaps أوTool drift.

## 9.2 Pilot success criteria

- Gate 0 مكتمل دون Critical waiver.
- ثماني KUs على الأقل عند `M4`، و`KU-AD-09` مكتملة.
- أربع Detections مجرّبة على الأقل، لكل منها Positive وNegative test.
- إثبات Kerberos وNTLM من Logs أوTraffic.
- إثبات Allow/Deny داخل Authorization Lab.
- إثبات GPO من GPC/GPT/Scope إلىEndpoint state.
- إثبات approved path عبر `MGMT01` ومنع unapproved path.
- إعادة تنفيذ 80% من Core micro-labs بعد أسبوع دون وصفة حرفية.
- وقت إدارة الملفات ≤20% من إجمالي وقت الـPilot.
- لا Course completion غير ضرورية.

## 9.3 Pilot failure/redesign criteria

- أكثر من 30% من الوقت في إدارة الملفات.
- Evidence لا تربط Raw Events بالاستنتاج.
- ثلاثة Labs غير قابلة لإعادة الإنتاج.
- غياب Negative Tests.
- Course structure يقود الـPilot بدلاً من Capability outcomes.
- Hardening يكسر وظيفة مشروعة ولا يوجد Rollback.
- الحاجة إلى تطبيق/Database تنشأ من رغبة شكلية لا مشكلة مثبتة.

---

# 10. Technology Decision Gate

القرار يؤخذ بعد `BL-AD-021` فقط.

## Remain File-First

القرار الافتراضي عندما:

- Traceability يمكن إدارتها في الملفات الأربعة.
- Review Queue صغيرة.
- البحث النصي كافٍ.
- لا توجد تناقضات متكررة في State.
- الإدارة ≤20% من الوقت.

## Consider SQLite later

فقط إذا أثبت الـPilot واحداً أو أكثر:

- علاقات كثيرة تسبب أخطاء فعلية.
- استعلامات متكررة لا يمكن إدارتها بوضوح.
- عشرات Evidence items تضيع روابطها.
- Review scheduling يصبح يدوياً وغير موثوق.

حتى عندها يبقى Markdown محتوى Canonical وSQLite Index/State store فقط.

## Consider a small application later

بعد نجاح Pilotين على الأقل واستقرار Workflow مدة `6–8` أسابيع ووجود مشكلة UX/Query موثقة لا تحلها الملفات.

---

# 11. Current stop state

```text
STOP-AD-PILOT-DESIGN-001
```

الحالة عند هذا التوقف:

- البنية والـIDs والتشخيص والمصادر والوحدات والBacklog معرفة.
- لا Lab نُفذت.
- لا حسابات أوOU أوGPO أُنشئت.
- لا تغييرات على Wazuh أوActive Directory.
- القرار التالي المطلوب: **موافقة صريحة على بدء Gate 0 فقط**، وليس على بدء الهجمات أوCapstone.

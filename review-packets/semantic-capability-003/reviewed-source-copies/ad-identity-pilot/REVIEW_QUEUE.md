# AD Identity Pilot — Review Queue

> **Initial state:** Empty — no labs or diagnostics have executed.  
> **Purpose:** Failure-based retention without producing a large flashcard workload.

---

## 1. Queue record format

عند فتح Review، يضاف صف واحد فقط:

| Review ID | Trigger ID | KU | Failure type | Evidence pointer | Required action | Due | Status | Exit proof |
|---|---|---|---|---|---|---|---|---|
| مثال: `REV-AD-001` | `RT-AD-03-04` | `KU-AD-03` | Diagnostic | `EVD-...` | Micro-lab DNS/SPN/time | D+2 | Open | New diagnosis note |

`REV-AD-NNN` هو Instance للReview؛ أما `RT-AD-NN-NN` فهو Trigger ثابت.

---

# 2. Active review queue

لا توجد مراجعات مفتوحة قبل Diagnostic.

| Review ID | Trigger ID | KU | Failure type | Evidence pointer | Required action | Due | Status | Exit proof |
|---|---|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — | — | — |

---

# 3. Failure taxonomy and response

| النوع | متى يفتح | الاستجابة الدنيا | لا تفعل |
|---|---|---|---|
| `Concept` | خلط مفهومين أوMental Model ناقص | Contrast question + explanation from memory | إعادة مشاهدة Course كاملة |
| `Execution` | أمر/Lab لا يعمل ولا يعرف السبب | Micro-lab يعزل متغيراً واحداً | نسخ أوامر عشوائية |
| `Evidence` | النتيجة صحيحة لكن لا يستطيع إثباتها | تفسير Raw Event/PCAP جديد | Screenshot فقط |
| `Detection` | Rule لا تطلق أوFalse Positive | Positive + Negative + field review | تخفيض threshold عشوائياً |
| `Defense` | Hardening يكسر وظيفة أولا يمنع المسار | Rollback ثم staged reconfiguration | ترك environment broken |
| `Retention` | فشل بعد D+7/D+21 | إعادة تنفيذ مختصرة في سياق جديد | بدء KU من الصفر تلقائياً |
| `Safety` | Scope أوRollback غير واضح | إيقاف فوري والعودة لـGate 0 | الاستمرار لتوفير الوقت |

---

# 4. Fixed review trigger catalog

## KU-AD-01

- `RT-AD-01-01` — Domain/Site confusion.
- `RT-AD-01-02` — SRV/DC Locator purpose not understood.
- `RT-AD-01-03` — Cannot prove selected DC.
- `RT-AD-01-04` — Diagram differs from actual environment.

## KU-AD-02

- `RT-AD-02-01` — Stale-token/group-change misunderstanding.
- `RT-AD-02-02` — Share vs NTFS permission confusion.
- `RT-AD-02-03` — Unnecessary explicit Deny.
- `RT-AD-02-04` — Requested access/inheritance not explained.
- `RT-AD-02-05` — No machine-readable ACL export.

## KU-AD-03

- `RT-AD-03-01` — AS/TGS/AP confusion.
- `RT-AD-03-02` — PAC treated as Windows token.
- `RT-AD-03-03` — 4769/SPN/service relationship not explained.
- `RT-AD-03-04` — DNS/time/SPN omitted from diagnosis.
- `RT-AD-03-05` — Authentication success equated with authorization.

## KU-AD-04

- `RT-AD-04-01` — NTLM described as plaintext password transfer.
- `RT-AD-04-02` — NTLMv2 treated as long-term target state.
- `RT-AD-04-03` — Capture and relay conflated.
- `RT-AD-04-04` — Deny recommended before audit.
- `RT-AD-04-05` — Target-side controls not identified.

## KU-AD-05

- `RT-AD-05-01` — LDAPS and signing conflated.
- `RT-AD-05-02` — Bind type not identified.
- `RT-AD-05-03` — Delegation wider than required.
- `RT-AD-05-04` — No negative authorization test.
- `RT-AD-05-05` — Enforcement without client inventory.

## KU-AD-06

- `RT-AD-06-01` — Link treated as proof of application.
- `RT-AD-06-02` — GPC orGPT half ignored.
- `RT-AD-06-03` — `gpupdate /force` used without diagnosis.
- `RT-AD-06-04` — Default GPO modified.
- `RT-AD-06-05` — No backup/rollback.

## KU-AD-07

- `RT-AD-07-01` — Same identity used for productivity/admin.
- `RT-AD-07-02` — Trusted target administered from less-trusted host.
- `RT-AD-07-03` — VLAN treated as sole control.
- `RT-AD-07-04` — Credential exposure points not mapped.
- `RT-AD-07-05` — Advanced delegation blocking core progress.
- `RT-AD-07-06` — Allowed/denied path not both tested.

## KU-AD-08

- `RT-AD-08-01` — Rule has no hypothesis/owner.
- `RT-AD-08-02` — Alert has no raw event.
- `RT-AD-08-03` — No negative test.
- `RT-AD-08-04` — Clock/source identity ignored.
- `RT-AD-08-05` — No source-silence test.
- `RT-AD-08-06` — Default Wazuh ruleset modified directly.

## KU-AD-09

- `RT-AD-09-01` — Timeline does not join account/source/target/time.
- `RT-AD-09-02` — Alert unsupported by raw evidence.
- `RT-AD-09-03` — Remediation not retested.
- `RT-AD-09-04` — Privilege exceeds Pilot OU scope.
- `RT-AD-09-05` — Report is tool-centric, not root-cause-centric.
- `RT-AD-09-06` — Missing executive or technical evidence layer.

---

# 5. Minimal active recall model

لكل KU أربعة Prompts فقط. لا تُنشأ Cards إضافية إلا من Failure حقيقي.

| KU | Mechanism | Contrast | Evidence | Failure scenario |
|---|---|---|---|---|
| `KU-AD-01` | كيف يجد Client DC؟ | Domain vs Site | ما الذي يثبت DC selected؟ | Subnet غير معرف |
| `KU-AD-02` | كيف يقرر Windows Allow/Deny؟ | Group vs Privilege | أي Token/ACL fields تثبت القرار؟ | Membership تغيرت ولم يتغير access |
| `KU-AD-03` | كيف ينتقل Client من TGT إلىService access؟ | TGT vs Service Ticket | كيف تربط 4768/4769/4624؟ | SPN/DNS/time issue |
| `KU-AD-04` | كيف يعمل NTLM challenge-response؟ | Capture vs Relay | كيف تثبت fallback؟ | IP access أوlegacy app |
| `KU-AD-05` | كيف يصبح LDAP operation Authorization decision؟ | LDAPS vs signing/CBT | ما bind/event/ACL evidence؟ | Enforcement breaks client |
| `KU-AD-06` | كيف ينتقل GPO منGPC/GPT إلىendpoint؟ | Link vs effective application | `gpresult` + ActivityID evidence | Filtering mismatch |
| `KU-AD-07` | ما approved admin path؟ | Network segmentation vs privileged access assurance | ما الذي يثبت allowed/denied paths؟ | Admin credential علىlow-trust host |
| `KU-AD-08` | كيف تتحول hypothesis إلىtested detection؟ | Alert vs evidence | raw event/fields/test | source silence أوfalse positive |
| `KU-AD-09` | كيف تربط incident chain end-to-end؟ | containment vs remediation | timeline + retest | fix without verification |

---

# 6. Review schedule

| الوقت | نوع المراجعة | الحد الأقصى المتوقع |
|---|---|---:|
| `D+2` | Explain/Contrast دون Notes | 10–15 دقيقة |
| `D+7` | تفسير Evidence جديدة أوMicro-lab | 20–30 دقيقة |
| `D+21` | إعادة تنفيذ Core micro-lab | 30–45 دقيقة |
| `D+45` | Transfer داخل Scenario آخر عند الحاجة | لا ينفذ لكل KU تلقائياً |

القواعد:

- Failure تعيد فقط المهارة المتأثرة إلىQueue.
- نجاح D+7 لا يثبت `M5` وحده إذا كان مجرد حفظ.
- `M5` يتطلب transfer أوreproduction دون وصفة حرفية.
- High-value questions فقط؛ لا Flashcards لكل Event field أوCommand option.

---

# 7. Review exit criteria

Review تغلق عندما:

1. نُفذ action المحدد.
2. توجد Evidence جديدة أوإجابة دون الملاحظات.
3. لم يعد Trigger قابلاً لإعادة الإنتاج في test مماثل.
4. Mastery State عادت أوارتفعت.
5. لا Safety concern مفتوح.

Review لا تغلق بعبارة “راجعت الدرس”.

---

# 8. Pilot retrospective questions

تُستخدم بعد Capstone:

1. ما KUs التي استهلكت وقتاً في التنظيم أكثر من التعلم؟
2. أي Source Segments كانت أقوى أوأضعف من القرار الأولي؟
3. ما Evidence types كانت أسهل في الاسترجاع والتحقق؟
4. أين انكسرت Traceability؟
5. هل كانت Review Queue قابلة للإدارة يدوياً؟
6. هل ظهرت حاجة حقيقية لـQuery/Database؟
7. هل `KU-AD-07` بقيت مركزة على approved paths أم انجرفت إلىdelegation؟
8. هل أثبت Capstone انتقالاً من المعرفة إلىالقدرة؟
9. ما الذي يجب نقله إلىPilot التالي، وما الذي يجب حذفه؟

القرار الافتراضي بعد الـPilot يبقى `File-First` ما لم تثبت البيانات عكس ذلك.

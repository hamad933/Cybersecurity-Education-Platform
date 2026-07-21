# AD Identity Pilot — Knowledge Units

> **Status:** Design only  
> **Execution:** Prohibited until Gate 0 approval  
> **Number of KUs:** `9`

---

## 1. Global Knowledge Unit contract

كل KU تتمحور حول Capability قابلة للإثبات، وليس حول إكمال Course.

### Required fields

- Capability Outcome.
- Prerequisites.
- Selected Source Segments.
- Lab design and safety boundary.
- Attack/Failure connection عند الملاءمة.
- Detection and Defense connection.
- Evidence IDs.
- Mastery criteria.
- Review Trigger IDs.
- Challenge-Out evidence.

### Global Definition of Done

KU تصل `M4` عندما:

1. ينجح Lab الأساسي.
2. تحفظ Raw Evidence المطلوبة.
3. يفسر المستخدم النتيجة لا أن ينسخ أمراً فقط.
4. يوجد Negative Test.
5. يوجد Defense أوHardening وVerification عندما ينطبق.
6. لا يوجد Critical Review Trigger مفتوح.

---

# KU-AD-01 — Active Directory Identity Control Plane

## Capability outcome

يبني خريطة صحيحة وقابلة للتحقق لـ:

```text
Forest → Domain → Sites/Subnets → DC/DNS → Users/Groups/Computers/OUs
       → SYSVOL/NETLOGON → Authentication services → Telemetry points
```

ويستطيع إثبات DC Locator الفعلي من Client، لا الاكتفاء بالرسم.

## Prerequisites

- IPv4/DNS basics.
- Windows command line وPowerShell basics.
- معرفة مبدئية بأسماء أجهزة المختبر.

## Diagnostic mapping

`DX-AD-001..004`

## Selected source segments

`SEG-AD-001`, `SEG-AD-002`, `SEG-AD-032`, `SEG-AD-033`, `SEG-AD-034`, `SEG-AD-035`, `SEG-AD-019`, `SEG-AD-060`.

## Labs

### `LAB-AD-01-01` — Directory and topology baseline

**هدف:** توثيق Forest/Domain/DC/Site/Subnet/OU والأدوار دون تغيير البيئة.

**Scope:** Read-only على `DC01`, `CLIENT01`, `MGMT01`.

**Safety:** لا تغييرات في AD objects أوDNS.

### `LAB-AD-01-02` — DC Locator and DNS evidence

**هدف:** إثبات DNS SRV lookup، DC selection، وSite relationship من Client.

**Failure injection:** غير مطلوب في Core؛ يمكن فقط تحليل Configuration gap إن وجد.

## Attack/failure connections

- Missing/wrong subnet mapping.
- DNS misconfiguration.
- Client selecting unexpected DC.
- Excessive directory readability as reconnaissance surface.

## Detection/defense connection

- Asset and identity-plane baseline يحدد Expected State.
- توثيق DNS/DC dependency قبل أي Authentication investigation.

## Required evidence

| ID | المطلوب |
|---|---|
| `EVD-AD-01-001` | Identity-control-plane diagram |
| `EVD-AD-01-002` | Forest/domain/site/subnet/DC inventory export |
| `EVD-AD-01-003` | DNS SRV query output |
| `EVD-AD-01-004` | DC Locator/client selection output |
| `EVD-AD-01-005` | Trust-boundary and telemetry-point matrix |

## Mastery criteria

- `M1`: يشرح الفرق بين Domain/Site/OU وSID/GUID/DN.
- `M2`: ينتج inventory وDC locator evidence.
- `M3`: يفسر لماذا اختير DC محدد وما أثر DNS/Site.
- `M4`: يحدد Gap حقيقي أويثبت Baseline سليماً مع Verification.

## Review triggers

| ID | Trigger |
|---|---|
| `RT-AD-01-01` | خلط Domain معSite |
| `RT-AD-01-02` | عدم تفسير SRV record purpose |
| `RT-AD-01-03` | عدم القدرة على إثبات DC المختار |
| `RT-AD-01-04` | رسم لا يطابق البيئة الفعلية |

## Challenge-Out evidence

`EVD-AD-01-003` + `EVD-AD-01-004` + تفسير شفهي/مكتوب دون الملاحظات.

---

# KU-AD-02 — Windows Authorization Engine

## Capability outcome

يفسر ويثبت:

```text
Effective Token + Security Descriptor + Requested Access → Allow / Deny
```

مع التمييز بين Authentication وAuthorization وElevation.

## Prerequisites

`KU-AD-01`، Windows users/groups، NTFS basics.

## Diagnostic mapping

`DX-AD-004..007`

## Selected source segments

`SEG-AD-003`, `SEG-AD-004`, `SEG-AD-005`, `SEG-AD-018`, `SEG-AD-030`, `SEG-AD-031`, `SEG-AD-034`.

## Labs

### `LAB-AD-02-01` — Token and group-state observation

**هدف:** مقارنة `whoami /all` وToken state قبل/بعد تغيير Group تجريبي مع Logoff/Logon واضح.

**Safety:** Test account فقط.

### `LAB-AD-02-02` — Share + NTFS access decision matrix

**هدف:** بناء Allow/Deny/Inheritance matrix على Share/Folder تجريبي.

**Safety:** مسار تجريبي، لا تعديل لمجلدات النظام أوShares فعلية.

### `LAB-AD-02-03` — Object access auditing

**هدف:** SACL محدودة على Object تجريبي ثم Positive/Negative access tests.

## Attack/failure connections

- Over-permissive ACE.
- Wrong inheritance.
- Stale token.
- Broad privileged group.
- Rights مثل `WRITE_DAC`/ownership misunderstanding.

## Detection/defense connection

- Least privilege.
- Explicit authorization matrix.
- Object access auditing scoped to avoid noise.

## Required evidence

| ID | المطلوب |
|---|---|
| `EVD-AD-02-001` | Token export before group change |
| `EVD-AD-02-002` | Token export after new logon |
| `EVD-AD-02-003` | ACL/SDDL or `icacls` baseline |
| `EVD-AD-02-004` | Allow/Deny test matrix |
| `EVD-AD-02-005` | Raw object-access event and interpretation |
| `EVD-AD-02-006` | Restored final ACL proof |

## Mastery criteria

- `M1`: يشرح Token/SD/DACL/SACL/ACE/Privilege.
- `M2`: ينشئ access matrix قابلة للتكرار.
- `M3`: يفسر كل Allow/Deny من Token وACL.
- `M4`: يصلح misconfiguration ويثبت Allowed وDenied paths بعد الإصلاح.

## Review triggers

| ID | Trigger |
|---|---|
| `RT-AD-02-01` | اعتبار Group membership الجديدة فورية داخل Token قائم |
| `RT-AD-02-02` | الخلط بين Share وNTFS permissions |
| `RT-AD-02-03` | افتراض أن Deny دائماً الحل الأفضل |
| `RT-AD-02-04` | عدم تفسير Requested Access أوInheritance |
| `RT-AD-02-05` | الاعتماد على Screenshot دون ACL export |

## Challenge-Out evidence

حل Access Denied مجهول مسبقاً، مع Token + ACL evidence وبدون تعديل عشوائي للصلاحيات.

---

# KU-AD-03 — Kerberos Normal Authentication and Evidence

## Capability outcome

يتتبع ويثبت:

```text
AS-REQ/AS-REP → TGT → TGS-REQ/TGS-REP → Service Ticket → AP exchange
→ local authorization
```

ويشرح أن Ticket لا تعني Authorization ناجحة تلقائياً.

## Prerequisites

`KU-AD-01`, `KU-AD-02`, DNS/time basics.

## Diagnostic mapping

`DX-AD-008..010`

## Selected source segments

`SEG-AD-006`, `SEG-AD-007`, `SEG-AD-036`, `SEG-AD-037`, `SEG-AD-038`, `SEG-AD-061`.

## Labs

### `LAB-AD-03-01` — Normal Kerberos service access

**هدف:** Domain logon ثم الوصول إلى Service باسم hostname/FQDN وفحص Ticket cache.

### `LAB-AD-03-02` — Event and packet correlation

**هدف:** ربط `4768`, `4769`, `4624` مع PCAP أوNetwork trace المناسبة.

### `LAB-AD-03-03` — Controlled failure diagnosis

**هدف:** تشخيص Failure آمن وقابل للRollback، مثل wrong service name/SPN expectation أوclock check في Test VM دون العبث بـDC time.

## Attack/failure connections

- DNS/DC locator failure.
- Time skew.
- SPN mismatch/duplicate.
- Encryption/key mismatch.
- NTLM fallback كSignal.

لا يشمل Ticket forgery أوCredential dumping.

## Detection/defense connection

- Kerberos baseline.
- Service ownership/SPN inventory.
- Event field interpretation.
- DNS/time hygiene.

## Required evidence

| ID | المطلوب |
|---|---|
| `EVD-AD-03-001` | Annotated Kerberos sequence |
| `EVD-AD-03-002` | `klist` baseline |
| `EVD-AD-03-003` | Raw 4768 event |
| `EVD-AD-03-004` | Raw 4769 event |
| `EVD-AD-03-005` | Correlated 4624/service evidence |
| `EVD-AD-03-006` | PCAP/trace and filter notes |
| `EVD-AD-03-007` | Failure diagnosis and restored state |

## Mastery criteria

- `M1`: يميز TGT وService Ticket وPAC وToken.
- `M2`: يولد ويلاحظ Normal flow.
- `M3`: يربط Events/traffic/session.
- `M4`: يشخّص Failure ويثبت restoration دون دفع البيئة إلىNTLM بشكل دائم.

## Review triggers

| ID | Trigger |
|---|---|
| `RT-AD-03-01` | خلط AS وTGS وAP exchanges |
| `RT-AD-03-02` | اعتبار PAC هي Token نفسها |
| `RT-AD-03-03` | عدم ربط 4769 بالخدمة/SPN |
| `RT-AD-03-04` | تشخيص Kerberos دون DNS/time/SPN checks |
| `RT-AD-03-05` | اعتبار نجاح Authentication دليلاً على Authorization |

## Challenge-Out evidence

Correlation جديدة لـ`4768→4769→4624` مع تفسير Service/SPN وسبب Access outcome.

---

# KU-AD-04 — NTLM, Fallback, and Relay Preconditions

## Capability outcome

يحدد متى ولماذا استُخدم NTLM، ويفهم Netlogon pass-through وRelay preconditions، ويصمم Audit-first reduction plan.

## Prerequisites

`KU-AD-01`, `KU-AD-02`, `KU-AD-03`.

## Diagnostic mapping

`DX-AD-011..013`

## Selected source segments

`SEG-AD-008`, `SEG-AD-009`, `SEG-AD-021`, `SEG-AD-039`, `SEG-AD-069`.

## Labs

### `LAB-AD-04-01` — Hostname vs IP authentication comparison

**هدف:** مقارنة خدمة تجريبية عند الوصول باسم صحيح مقابل IP، ثم إثبات protocol used.

### `LAB-AD-04-02` — NTLM auditing and dependency inventory

**هدف:** تمكين/استخدام Audit فقط حسب Gate 0، جمع Events، وتصنيف dependencies.

### `LAB-AD-04-03` — Relay resistance assessment

**هدف:** Read-only assessment لـSMB signing وLDAP signing/CBT وEPA where applicable.

**Core exclusion:** لا تنفيذ Relay حي ضد `DC01` أوخدمة أساسية.

## Attack/failure connections

- IP-based access.
- Missing SPN.
- Legacy dependency.
- Name-resolution/coercion context.
- Relay يحتاج both credential flow and target weakness.

## Detection/defense connection

- Audit before deny.
- SMB signing.
- LDAP signing/channel binding.
- EPA حيث ينطبق.
- Exception ownership and sunset.

## Required evidence

| ID | المطلوب |
|---|---|
| `EVD-AD-04-001` | Hostname Kerberos evidence |
| `EVD-AD-04-002` | IP/NTLM evidence or justified non-occurrence |
| `EVD-AD-04-003` | `4776`/NTLM Operational raw event |
| `EVD-AD-04-004` | NTLM dependency inventory |
| `EVD-AD-04-005` | Relay-resistance control matrix |
| `EVD-AD-04-006` | Audit-first migration recommendation |

## Mastery criteria

- `M1`: يشرح challenge-response وNetlogon وfallback.
- `M2`: يثبت Kerberos/NTLM comparison.
- `M3`: يفسر dependency من Raw Events.
- `M4`: يصمم reduction plan مع controls وexception handling دون تعطيل الخدمة.

## Review triggers

| ID | Trigger |
|---|---|
| `RT-AD-04-01` | القول إن NTLM يرسل كلمة المرور plaintext |
| `RT-AD-04-02` | اعتبار NTLMv2 حلاً استراتيجياً كاملاً |
| `RT-AD-04-03` | خلط hash capture معrelay |
| `RT-AD-04-04` | اقتراح deny قبل audit/inventory |
| `RT-AD-04-05` | عدم تحديد target-side signing/binding controls |

## Challenge-Out evidence

Dependency case واحدة يثبت فيها سبب fallback ويقترح remediation دون service outage.

---

# KU-AD-05 — LDAP and Directory Authorization

## Capability outcome

يفسر ويثبت:

```text
LDAP Bind → Security Context → Directory Operation → Object Security Descriptor
→ Attribute/Object Rights → Result + Audit Evidence
```

## Prerequisites

`KU-AD-01`, `KU-AD-02`; يفضّل `KU-AD-03/04` لفهم SASL/SPNEGO.

## Diagnostic mapping

`DX-AD-014..015`

## Selected source segments

`SEG-AD-010`, `SEG-AD-011`, `SEG-AD-004`, `SEG-AD-040`.

## Labs

### `LAB-AD-05-01` — LDAP query and bind context

**هدف:** تنفيذ read-only queries وتوثيق Bind/transport/security context.

### `LAB-AD-05-02` — OU-scoped delegation

**هدف:** تفويض عملية محددة لحساب Test داخل `OU=AD-PILOT-LAB`، مثل Reset Password لحسابات Test فقط.

### `LAB-AD-05-03` — Signing/CBT readiness assessment

**هدف:** Audit clients/binds قبل أي enforcement.

## Attack/failure connections

- Over-broad directory read/write.
- Simple bind without protection.
- Unsigned SASL bind.
- Dangerous delegated ACE.
- Client breakage after enforcement.

## Detection/defense connection

- LDAP signing/channel binding.
- Scoped OU delegation.
- Events مثل `2887`/relevant current schema بحسب الإصدار.
- Positive/negative authorization tests.

## Required evidence

| ID | المطلوب |
|---|---|
| `EVD-AD-05-001` | Query/bind transcript |
| `EVD-AD-05-002` | OU delegation ACL export before |
| `EVD-AD-05-003` | Allowed operation result |
| `EVD-AD-05-004` | Denied out-of-scope operation result |
| `EVD-AD-05-005` | LDAP signing/CBT audit events |
| `EVD-AD-05-006` | ACL restored/final state |

## Mastery criteria

- `M1`: يميز Bind/transport/authentication/authorization.
- `M2`: ينفذ query وscoped delegation.
- `M3`: يفسر Allow/Deny وaudit events.
- `M4`: يثبت least privilege وreadiness قبل enforcement.

## Review triggers

| ID | Trigger |
|---|---|
| `RT-AD-05-01` | اعتبار LDAPS وLDAP signing الشيء نفسه |
| `RT-AD-05-02` | عدم معرفة Bind type |
| `RT-AD-05-03` | تفويض OU أوسع من Capability المطلوبة |
| `RT-AD-05-04` | عدم وجود negative authorization test |
| `RT-AD-05-05` | enforcement دون client inventory |

## Challenge-Out evidence

Delegation محدودة مثبتة بعملية Allowed وأخرى Denied خارج النطاق مع ACL export.

---

# KU-AD-06 — Group Policy Processing and Control-Plane Integrity

## Capability outcome

يفسر ويثبت:

```text
GPO = GPC in AD + GPT in SYSVOL
Scope/Link/Filtering → Client Processing → Endpoint State → Operational Evidence
```

## Prerequisites

`KU-AD-01`, `KU-AD-02`, `KU-AD-05`.

## Diagnostic mapping

`DX-AD-016..017`

## Selected source segments

`SEG-AD-012`, `SEG-AD-013`, `SEG-AD-020`, `SEG-AD-041`, `SEG-AD-042`, `SEG-AD-043`, `SEG-AD-062`, `SEG-AD-071`, `SEG-AD-053`.

## Labs

### `LAB-AD-06-01` — Safe pilot GPO lifecycle

**هدف:** إنشاء GPO غير مدمرة ومربوطة بـPilot OU فقط، مع Backup وRollback.

### `LAB-AD-06-02` — GPC/GPT and application proof

**هدف:** إثبات metadata وSYSVOL payload وversion ثم Endpoint state.

### `LAB-AD-06-03` — Processing failure diagnosis

**هدف:** بناء Failure آمن مثل Security Filtering غير مطابق، ثم التشخيص عبر `gpresult` وActivityID Operational logs.

### `LAB-AD-06-04` — GPO change detection

**هدف:** اختبار Telemetry لتغيير مصرح داخل Pilot GPO.

## Attack/failure connections

- Write permissions على GPO/GPT.
- Security/WMI filtering mistakes.
- Version mismatch/replication issue.
- GPO linked but not applied.

لا ينفذ `SharpGPOAbuse`; الديمو يستخدم لفهم الأثر فقط.

## Detection/defense connection

- Restricted GPO ownership/delegation.
- Backup and change control.
- GroupPolicy Operational correlation.
- Wazuh alert or documented telemetry gap.

## Required evidence

| ID | المطلوب |
|---|---|
| `EVD-AD-06-001` | GPO backup and metadata |
| `EVD-AD-06-002` | GPC proof |
| `EVD-AD-06-003` | GPT/SYSVOL proof |
| `EVD-AD-06-004` | `gpresult` output |
| `EVD-AD-06-005` | ActivityID-filtered Operational events |
| `EVD-AD-06-006` | Endpoint enforced state |
| `EVD-AD-06-007` | Authorized change alert or telemetry gap |
| `EVD-AD-06-008` | Rollback and recurrence test |

## Mastery criteria

- `M1`: يشرح GPC/GPT/LSDOU/filtering/CSE.
- `M2`: ينشئ GPO معزولة ويثبت التطبيق.
- `M3`: يشخّص non-application عبر evidence.
- `M4`: يكتشف تغييراً ويعيد state ويثبت rollback.

## Review triggers

| ID | Trigger |
|---|---|
| `RT-AD-06-01` | اعتبار Link دليلاً كافياً على التطبيق |
| `RT-AD-06-02` | تجاهل GPC أوGPT half |
| `RT-AD-06-03` | استخدام `gpupdate /force` دون ActivityID diagnosis |
| `RT-AD-06-04` | تعديل Default GPO |
| `RT-AD-06-05` | غياب backup/rollback |

## Challenge-Out evidence

تشخيص GPO non-application مجهول مسبقاً من `gpresult` + Operational log + GPC/GPT evidence.

---

# KU-AD-07 — Privileged Access Tiering, Approved Paths, and Credential-Exposure Control

## Capability outcome

يبني ويثبت نموذجاً مركزاً على:

```text
Privileged Identity
→ Approved Administrative Device/Intermediary
→ Approved Network Path
→ Authorized Target
→ Monitored Session
→ Credential-Exposure Boundaries
```

هذه KU لا تعتمد على Advanced Delegation لإكمال Core Pilot.

## Prerequisites

`KU-AD-01`, `KU-AD-02`, `KU-AD-06`.

## Diagnostic mapping

`DX-AD-018`

## Selected source segments

`SEG-AD-014`, `SEG-AD-015`, `SEG-AD-035`, `SEG-AD-044`, `SEG-AD-045`, `SEG-AD-046`, `SEG-AD-065`, `SEG-AD-066`, `SEG-AD-068`, `SEG-AD-070`, optional `SEG-AD-063`.

## Core labs

### `LAB-AD-07-01` — Privileged identity and target inventory

**هدف:** تحديد privileged/delegated accounts/groups والأهداف التي تديرها داخل المختبر.

### `LAB-AD-07-02` — Approved administrative path validation

**هدف:** إثبات أن الإدارة تتم عبر `ADM → MGMT01 → approved targets`.

### `LAB-AD-07-03` — Unapproved path negative test

**هدف:** إثبات منع/كشف محاولة الإدارة من `CLIENT01` أوUser VLAN.

### `LAB-AD-07-04` — Credential-exposure map

**هدف:** تحديد أين يمكن أن تظهر privileged credentials/tokens/sessions، ثم تقليل exposure.

## Optional lab — does not block KU completion

### `LAB-AD-07-90` — Isolated delegation observation

- `Optional`.
- يتطلب VM/Service مخصصة وSnapshot وموافقة منفصلة.
- لا Unconstrained Delegation في البيئة الأساسية.
- لا يؤثر تأجيله على `M4` للـKU.

## Attack/failure connections

- Admin account used on low-trust workstation.
- Direct management path bypassing `MGMT01`.
- Shared admin identity.
- Standing privilege and stale sessions.
- Credential exposure on compromised endpoint.

## Detection/defense connection

- Secure administrative host/intermediary.
- Separate admin identities.
- Least privilege and scoped delegation.
- Source-device restrictions.
- Privileged logon monitoring.
- Local admin password management where applicable.

## Required evidence

| ID | المطلوب |
|---|---|
| `EVD-AD-07-001` | Privileged identities/groups/targets inventory |
| `EVD-AD-07-002` | Approved path diagram |
| `EVD-AD-07-003` | Allowed MGMT01 path evidence |
| `EVD-AD-07-004` | Denied/detected CLIENT01 path evidence |
| `EVD-AD-07-005` | Credential exposure map |
| `EVD-AD-07-006` | Reduced-exposure configuration proof |
| `EVD-AD-07-007` | Post-change positive and negative verification |
| `EVD-AD-07-090` | Optional delegation evidence; not required |

## Mastery criteria

- `M1`: يشرح tier/security-level rationale وcredential exposure.
- `M2`: يوثق approved path ويثبت استخدامه.
- `M3`: يفسر logon/session evidence في allowed/denied paths.
- `M4`: يقلل exposure ويثبت أن المسار المشروع يعمل وغير المشروع ممنوع/مكتشف.

## Review triggers

| ID | Trigger |
|---|---|
| `RT-AD-07-01` | استخدام نفس الحساب للمستخدم والإدارة |
| `RT-AD-07-02` | إدارة trusted target من less-trusted host |
| `RT-AD-07-03` | اعتبار VLAN وحدها كافية دون identity/device controls |
| `RT-AD-07-04` | عدم تحديد credential exposure points |
| `RT-AD-07-05` | جعل Advanced Delegation شرطاً للCore Pilot |
| `RT-AD-07-06` | allowed path غير مراقبة أوnegative path غير مختبرة |

## Challenge-Out evidence

Approved/Denied path matrix مع Raw logon events وcredential-exposure analysis.

---

# KU-AD-08 — Identity Detection Engineering with Wazuh

## Capability outcome

يحوّل risk إلى Detection lifecycle:

```text
Hypothesis → Data Source → Fields → Logic → Positive Test → Negative Test
→ Alert → Triage → Response → Verification → Source-Silence Check
```

## Prerequisites

Gate 0 telemetry PASS، وEvidence من `KU-AD-03..07`.

## Diagnostic mapping

`DX-AD-019`

## Selected source segments

`SEG-AD-016`, `SEG-AD-017`, `SEG-AD-037`, `SEG-AD-038`, `SEG-AD-047`, `SEG-AD-048`, `SEG-AD-049`, `SEG-AD-050`.

## Labs

### `LAB-AD-08-01` — Telemetry coverage validation

**هدف:** إثبات arrival, parsing, field availability, timestamps, source identity.

### `LAB-AD-08-02` — Authentication failure/spray pattern

Events: `4625`, `4771`, `4776` بحسب السيناريو.

### `LAB-AD-08-03` — Success after failures

يستفيد من القواعد المعلنة `110003/110006` بعد Export وفحصها، لا يفترض صحتها.

### `LAB-AD-08-04` — Privileged group membership change

Events candidate: `4728/4729`, `4732/4733`, `4756/4757` بحسب Group scope.

### `LAB-AD-08-05` — Privileged authentication from unapproved source

Correlation: identity/group + source workstation/IP + target + approved-path list.

### `LAB-AD-08-06` — GPO/identity-control change

إما Detection تعمل، أوGap موثق مع خطة Telemetry.

## Required evidence

| ID | المطلوب |
|---|---|
| `EVD-AD-08-001` | Telemetry coverage matrix |
| `EVD-AD-08-002` | Raw event arrival and parsed fields |
| `EVD-AD-08-003` | Spray/failure detection package |
| `EVD-AD-08-004` | Success-after-failures package |
| `EVD-AD-08-005` | Group-change detection package |
| `EVD-AD-08-006` | Unapproved-source privileged logon package |
| `EVD-AD-08-007` | GPO/control-change package or gap record |
| `EVD-AD-08-008` | False-positive/negative analysis |
| `EVD-AD-08-009` | Source-silence test |

كل Detection package يحتوي Rule/Query، Raw Event، Positive Test، Negative Test، Alert، Triage questions، Response، Retest.

## Mastery criteria

- `M1`: يكتب hypothesis وrequired data.
- `M2`: يختبر rule/logic على sample/raw event.
- `M3`: يفسر fields والتوقيت والcorrelation.
- `M4`: أربع Detections تعمل بـpositive/negative tests وsource-silence coverage.

## Review triggers

| ID | Trigger |
|---|---|
| `RT-AD-08-01` | Rule بلا hypothesis أوowner |
| `RT-AD-08-02` | Alert بلا Raw Event |
| `RT-AD-08-03` | Positive test فقط |
| `RT-AD-08-04` | تجاهل clock/source identity |
| `RT-AD-08-05` | غياب source-silence monitoring |
| `RT-AD-08-06` | تعديل default Wazuh ruleset بدلاً من custom rule path |

## Challenge-Out evidence

Detection جديدة واحدة end-to-end تشمل positive/negative/source-silence test.

---

# KU-AD-09 — Capstone: Suspicious Privileged Authentication

## Capability outcome

يدمج Control Plane وAuthentication وAuthorization وApproved Paths وDetection وResponse في حالة واحدة قابلة للتقرير.

## Prerequisites

`KU-AD-01..08` عند `M3` على الأقل، وCore safety controls عند `M4` حيث تتعلق بالتغيير.

## Diagnostic mapping

`DX-AD-020`

## Selected source segments

كل segments المرتبطة بالـKUs السابقة، مع `SEG-AD-051` و`SEG-AD-053` للThreat mapping.

## Capstone lab

### `LAB-AD-09-01` — Controlled suspicious privileged authentication

**Safe scenario:**

1. حساب Test يُضاف مؤقتاً إلى `LAB-Pilot-Privileged-Operators`.
2. تحدث محاولات فاشلة محدودة ومنضبطة.
3. ينجح Logon من `CLIENT01` وهو Source غير معتمد للإدارة.
4. يحاول الحساب عملية داخل `OU=AD-PILOT-LAB` أوPilot GPO فقط.
5. Wazuh يولد Alerts المطلوبة.
6. تُبنى Timeline.
7. تتم إزالة العضوية/التفويض واحتواء الحالة.
8. يعاد الاختبار:
   - المسار غير المعتمد ممنوع/مكتشف.
   - المسار عبر `MGMT01` يعمل.
   - Detection ما تزال تعمل.

**Exclusions:** لا Domain Admin حقيقي، لا LSASS dumping، لا persistence خارج Pilot OU، لا default GPO modification.

## Required evidence

| ID | المطلوب |
|---|---|
| `EVD-AD-09-001` | Authorized scope statement |
| `EVD-AD-09-002` | Initial architecture and trust-boundary diagram |
| `EVD-AD-09-003` | Account/group initial state |
| `EVD-AD-09-004` | Raw failure events |
| `EVD-AD-09-005` | Successful unapproved-source logon event |
| `EVD-AD-09-006` | Directory/GPO operation evidence |
| `EVD-AD-09-007` | Wazuh alert set |
| `EVD-AD-09-008` | Network/flow evidence where available |
| `EVD-AD-09-009` | Incident timeline |
| `EVD-AD-09-010` | Root cause and control-gap analysis |
| `EVD-AD-09-011` | Containment/remediation record |
| `EVD-AD-09-012` | Allowed MGMT01 retest |
| `EVD-AD-09-013` | Denied/detected CLIENT01 retest |
| `EVD-AD-09-014` | Technical report |
| `EVD-AD-09-015` | One-page executive summary |

## Mastery criteria

- `M1`: يشرح scenario/control gaps.
- `M2`: ينفذ scenario بأمان وRollback.
- `M3`: يبني timeline من Raw evidence.
- `M4`: يحتوي ويصلح ويثبت allowed/denied/detected states.
- `M5`: يعيد بناء حالة مشابهة بعد D+7 دون وصفة حرفية.

## Review triggers

| ID | Trigger |
|---|---|
| `RT-AD-09-01` | Timeline لا تربط account/source/target/time |
| `RT-AD-09-02` | Alert غير مدعومة بRaw Event |
| `RT-AD-09-03` | Remediation بلا retest |
| `RT-AD-09-04` | استخدام privilege أوسع من Pilot OU |
| `RT-AD-09-05` | التقرير يصف أدوات ولا يفسر root cause |
| `RT-AD-09-06` | لا يوجد executive summary أوtechnical evidence chain |

## Challenge-Out

غير مسموح. Capstone إلزامية لإغلاق الـPilot.

---

# 2. Prerequisite graph

```text
KU-AD-01
├── KU-AD-02
├── KU-AD-03 ── KU-AD-04
└── KU-AD-05 ── KU-AD-06

KU-AD-02 + KU-AD-06
└── KU-AD-07 Core

KU-AD-03..07 + Gate 0 Telemetry
└── KU-AD-08

KU-AD-01..08
└── KU-AD-09 Capstone
```

---

# 3. Evidence claim map

| KU | Core capability claim | Lab IDs | Evidence range |
|---|---|---|---|
| `KU-AD-01` | AD control plane and DC selection are understood and verified | `LAB-AD-01-01..02` | `EVD-AD-01-001..005` |
| `KU-AD-02` | Windows Allow/Deny can be explained from token and descriptor | `LAB-AD-02-01..03` | `EVD-AD-02-001..006` |
| `KU-AD-03` | Kerberos normal flow and failure are observable | `LAB-AD-03-01..03` | `EVD-AD-03-001..007` |
| `KU-AD-04` | NTLM fallback/dependencies and controls are assessable | `LAB-AD-04-01..03` | `EVD-AD-04-001..006` |
| `KU-AD-05` | LDAP bind and directory authorization are verified | `LAB-AD-05-01..03` | `EVD-AD-05-001..006` |
| `KU-AD-06` | GPO lifecycle is provable from AD/SYSVOL to endpoint | `LAB-AD-06-01..04` | `EVD-AD-06-001..008` |
| `KU-AD-07` | Privileged access uses approved path with reduced exposure | `LAB-AD-07-01..04` | `EVD-AD-07-001..007` |
| `KU-AD-08` | Identity detections are testable and evidence-backed | `LAB-AD-08-01..06` | `EVD-AD-08-001..009` |
| `KU-AD-09` | Integrated incident can be detected, investigated, fixed, verified | `LAB-AD-09-01` | `EVD-AD-09-001..015` |

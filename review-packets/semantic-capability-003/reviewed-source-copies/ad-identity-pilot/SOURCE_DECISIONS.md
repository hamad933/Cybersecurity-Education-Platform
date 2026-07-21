# AD Identity Pilot — Source Decisions

> **Scope:** مصادر مرتبطة حصراً بـ`AD Identity Pilot`  
> **Last verified:** `2026-07-21`  
> **Rule:** لا يُعد المصدر مقروءاً كاملاً لمجرد إمكانية الوصول إلى ملفه أوفهرسه.

---

## 1. Classification model

كل مصدر يحصل على أربعة أحكام مستقلة:

| المحور | القيم |
|---|---|
| Learning Role | `Technical Authority`, `Canonical Synthesis`, `Deep Explanation`, `Practical Lab`, `Threat Mapping`, `Implementation Reference` |
| Content Condition | `Current`, `Needs Verification`, `Needs Update`, `Incomplete`, `Damaged` |
| Overlap | `Unique`, `Partially Duplicate`, `Highly Duplicate` |
| Study Decision | `Required`, `Selected Segments`, `Optional`, `Reference Only`, `Defer`, `Reject` |

لا يُستخدم الحجم أوحداثة العنوان منفردين كدليل جودة.

---

# 2. Source records

## 2.1 Project and local sources

| ID | المصدر | Accessible state | الدور | Condition | القرار |
|---|---|---|---|---|---|
| `SRC-AD-001` | `CKV-030_Active_Directory_Fundamentals.md` | Full local text | Canonical Synthesis | Needs Verification | Selected Segments |
| `SRC-AD-002` | `CKV-022_Windows_Access_Control_Internals_Tokens_SIDs_ACLs_and_SRM.md` | Full local text | Canonical Synthesis | Needs Verification | Selected Segments |
| `SRC-AD-003` | `CKV-031_Kerberos_Authentication_PAC_Tickets_and_Windows_Logon.md` | Full local text | Canonical Synthesis | Needs Verification | Selected Segments |
| `SRC-AD-004` | `CKV-032_NTLM_Netlogon_Relay_Risk_and_Authentication_Hardening.md` | Full local text | Canonical Synthesis | Needs Verification | Selected Segments |
| `SRC-AD-005` | `CKV-033_LDAP_LDAPS_Signing_Channel_Binding_and_Directory_Access.md` | Full local text | Canonical Synthesis | Needs Verification | Selected Segments |
| `SRC-AD-006` | `CKV-034_Group_Policy_Internals_and_Security.md` | Full local text | Canonical Synthesis | Needs Verification | Selected Segments |
| `SRC-AD-007` | `CKV-036_Active_Directory_Attack_Paths_and_Defensive_Monitoring.md` | Full local text | Canonical Synthesis | Needs Verification | Selected Segments |
| `SRC-AD-008` | `CKV-158_Secure_Remote_Administration_Bastion_Infrastructure.md` | Full local text | Canonical Synthesis | Needs Verification | Selected Segments |
| `SRC-AD-009` | `CKV-060_Detection_Engineering_and_Telemetry_Design.md` | Full local text | Canonical Synthesis | Needs Verification | Required segments |
| `SRC-AD-010` | `CKV-065_Security_Monitoring_Tools_and_Lab_Architecture.md` | Full local text | Canonical Synthesis | Needs Verification | Selected Segments |
| `SRC-AD-011` | `_tech/access-control.md` | Full local text | Deep Explanation | Repetitive / mixed confidence | Selected Segments |
| `SRC-AD-012` | `_tech/ad-basics.md` | Full local text | Deep Explanation | Repetitive | Selected Segments |
| `SRC-AD-013` | `_tech/gpo.md` | Full local text | Deep Explanation | Repetitive / advanced | Selected Segments |
| `SRC-AD-014` | `_tech/windows-and-ad-attacks.md` | Full local text | Attack Context | Tool drift likely | Optional segments |
| `SRC-AD-015` | Project environment declarations in current project context | Accessible conversation context | Environment Baseline | Must be verified | Required for Gate 0 |

### Project source policy

- CKV يحدد Mental Model وترتيب الموضوعات، لكنه لا يحسم خلافاً مع Microsoft protocol documentation.
- ملفات `_tech` تستخدم للتوسع والتشخيص، لا للاقتباس غير المتحقق من تفاصيل داخلية دقيقة.
- أي ادعاء عن LSASS/PAC/DSA internals يحتاج Cross-check مع Microsoft/Open Specifications قبل اعتماده كحقيقة نهائية.

## 2.2 Official technical and implementation sources

| ID | المصدر | Locator | الدور | القرار |
|---|---|---|---|---|
| `SRC-AD-020` | Microsoft — Parts of the Access Control Model | `https://learn.microsoft.com/en-us/windows/win32/secauthz/access-control-components` | Technical Authority | Required |
| `SRC-AD-021` | Microsoft — Access Tokens | `https://learn.microsoft.com/en-us/windows/win32/secauthz/access-tokens` | Technical Authority | Required |
| `SRC-AD-022` | Microsoft — AD DS Overview | `https://learn.microsoft.com/en-us/windows-server/identity/ad-ds/get-started/virtual-dc/active-directory-domain-services-overview` | Technical Authority | Required |
| `SRC-AD-023` | Microsoft — DC Locator | `https://learn.microsoft.com/en-us/windows-server/identity/ad-ds/manage/dc-locator` | Technical Authority | Required |
| `SRC-AD-024` | Microsoft — Security Principals | `https://learn.microsoft.com/en-us/windows-server/identity/ad-ds/manage/understand-security-principals` | Technical Authority | Required |
| `SRC-AD-025` | Microsoft — Security Groups | `https://learn.microsoft.com/en-us/windows-server/identity/ad-ds/manage/understand-security-groups` | Technical Authority | Selected Segments |
| `SRC-AD-026` | Microsoft — Kerberos Authentication Overview | `https://learn.microsoft.com/en-us/windows-server/security/kerberos/kerberos-authentication-overview` | Technical Authority | Required |
| `SRC-AD-027` | Microsoft — Event 4768 | `https://learn.microsoft.com/en-us/previous-versions/windows/it-pro/windows-10/security/threat-protection/auditing/event-4768` | Event Authority | Required |
| `SRC-AD-028` | Microsoft — Event 4769 | `https://learn.microsoft.com/en-us/previous-versions/windows/it-pro/windows-10/security/threat-protection/auditing/event-4769` | Event Authority | Required |
| `SRC-AD-029` | Microsoft — NTLM Audit policy | `https://learn.microsoft.com/en-us/previous-versions/windows/it-pro/windows-10/security/threat-protection/security-policy-settings/network-security-restrict-ntlm-audit-ntlm-authentication-in-this-domain` | Technical Authority | Required |
| `SRC-AD-030` | Microsoft — LDAP Signing | `https://learn.microsoft.com/en-us/windows-server/identity/ad-ds/ldap-signing` | Technical Authority | Required |
| `SRC-AD-031` | Microsoft — Group Policy Processing | `https://learn.microsoft.com/en-us/windows-server/identity/ad-ds/manage/group-policy/group-policy-processing` | Technical Authority | Required |
| `SRC-AD-032` | Microsoft — Applying Group Policy Troubleshooting | `https://learn.microsoft.com/en-us/troubleshoot/windows-server/group-policy/applying-group-policy-troubleshooting-guidance` | Implementation Reference | Required |
| `SRC-AD-033` | Microsoft — `gpresult` | `https://learn.microsoft.com/en-us/windows-server/administration/windows-commands/gpresult` | Implementation Reference | Required |
| `SRC-AD-034` | Microsoft — Secure Administrative Hosts | `https://learn.microsoft.com/en-us/windows-server/identity/ad-ds/plan/security-best-practices/implementing-secure-administrative-hosts` | Technical Authority | Required |
| `SRC-AD-035` | Microsoft — Enterprise Privileged Access Model | `https://learn.microsoft.com/en-us/security/privileged-access-workstations/privileged-access-access-model` | Technical Authority | Selected Segments |
| `SRC-AD-036` | Microsoft — AD Security Best Practices | `https://learn.microsoft.com/en-us/windows-server/identity/ad-ds/plan/security-best-practices/best-practices-for-securing-active-directory` | Technical Authority | Selected Segments |
| `SRC-AD-037` | Wazuh — Windows log collection configuration | `https://documentation.wazuh.com/current/user-manual/capabilities/log-data-collection/configuration.html` | Implementation Reference | Required |
| `SRC-AD-038` | Wazuh — `localfile` / Windows event channels | `https://documentation.wazuh.com/current/user-manual/reference/ossec-conf/localfile.html` | Implementation Reference | Required |
| `SRC-AD-039` | Wazuh — Rules syntax | `https://documentation.wazuh.com/current/user-manual/ruleset/ruleset-xml-syntax/rules.html` | Implementation Reference | Required |
| `SRC-AD-040` | Wazuh — MITRE mapping and custom rules | `https://documentation.wazuh.com/current/user-manual/ruleset/mitre.html` | Implementation Reference | Selected Segments |
| `SRC-AD-041` | MITRE ATT&CK — Valid Accounts: Domain Accounts | `https://attack.mitre.org/techniques/T1078/002/` | Threat Mapping | Reference Only |
| `SRC-AD-042` | MITRE ATT&CK — Domain Groups Discovery | `https://attack.mitre.org/techniques/T1069/002/` | Threat Mapping | Reference Only |
| `SRC-AD-043` | MITRE ATT&CK — Group Policy Modification | `https://attack.mitre.org/techniques/T1484/001/` | Threat Mapping | Reference Only |

## 2.3 Course sources — inventory access only until selected segment is inspected

| ID | المصدر | Accessible state | Copy condition | الدور | القرار |
|---|---|---|---|---|---|
| `SRC-AD-050` | `Zero Point Security — Red Team Ops 2025.2` | Directory tree and some files/segments accessible | Mixed: images, videos, VTT; not uniformly complete | Offensive Supplement | Selected Segments |
| `SRC-AD-051` | `Active Directory Protection & Tiering` | Directory tree accessible | Appears structured; full semantic review not completed | Defensive Supplement | Primary course supplement |
| `SRC-AD-052` | `Red Team Ops [CRTO]` | Book/Videos inventory accessible | Curated demos, not full course | Practical Lab Supplement | Selected demos only |
| `SRC-AD-053` | `AD Exploitation and Lateral Movement Black-Box` | Directory tree accessible | Incomplete branch observed; tool drift possible | Optional Lab Supplement | Defer unless gap remains |
| `SRC-AD-054` | `QURE Advanced Attacks Against Active Directory` | Inventory known from prior inspection | Session-style material | Advanced Follow-Up | Post-pilot |
| `SRC-AD-055` | `Red Team Ops II` | Inventory known from prior inspection | Advanced/evasion focused | Advanced Follow-Up | Out of scope |

### Course copy findings

- `SRC-AD-050`: Root inventory exposes 29 units. `Domain Reconnaissance` and `Group Policy` inspected copies are image-only; `Kerberos` includes images plus four videos and VTT files. Therefore the copy is useful but not treated as a complete uniform curriculum.
- `SRC-AD-051`: The inspected tree separates `Common AD Attacks & Tools` from `AD Protection`, with subsections `AD Tiering`, `Passwords`, `AD Features`, and `Hardening`.
- `SRC-AD-052`: The inspected `Videos` folder contains focused demonstrations including `NTLM Relaying`, `Unconstrained Delegation`, `Constrained Delegation`, `RSAT`, and `SharpGPOAbuse`; it is a demo library, not the primary conceptual source.
- `SRC-AD-053`: A known zero-size file exists in the PowerView Python branch; the course is not relied on for Pilot completeness.

---

# 3. Selected source segments

## 3.1 Canonical and project segments

| Segment ID | Parent | Locator | Use |
|---|---|---|---|
| `SEG-AD-001` | `SRC-AD-001` | Sections 4–11: mental model, components, objects, identity, OU, groups | `KU-AD-01` |
| `SEG-AD-002` | `SRC-AD-001` | Sections 13–16: Sites/Subnets, GC, FSMO, SYSVOL/NETLOGON | `KU-AD-01`,`KU-AD-06` |
| `SEG-AD-003` | `SRC-AD-002` | Sections 4–8: authorization model, SIDs, tokens, groups/privileges | `KU-AD-02` |
| `SEG-AD-004` | `SRC-AD-002` | Sections 10–18: objects, descriptors, ACL/ACE, masks, SRM, auditing | `KU-AD-02`,`KU-AD-05` |
| `SEG-AD-005` | `SRC-AD-002` | Sections 19–21: failures, troubleshooting, investigation | `KU-AD-02` |
| `SEG-AD-006` | `SRC-AD-003` | Sections 4–17: Kerberos model through service access | `KU-AD-03` |
| `SEG-AD-007` | `SRC-AD-003` | Sections 18–24: caching, time, failures, troubleshooting, hardening | `KU-AD-03` |
| `SEG-AD-008` | `SRC-AD-004` | Sections 4–13: NTLM model, Netlogon, fallback, relay concepts | `KU-AD-04` |
| `SEG-AD-009` | `SRC-AD-004` | Sections 14–21: service relationships, auditing, migration, hardening | `KU-AD-04` |
| `SEG-AD-010` | `SRC-AD-005` | Sections 4–12: LDAP model, bind, search, operations | `KU-AD-05` |
| `SEG-AD-011` | `SRC-AD-005` | Sections 13–20: LDAPS, StartTLS, signing, CBT, access control | `KU-AD-05` |
| `SEG-AD-012` | `SRC-AD-006` | Core model: GPC/GPT, scope, filtering, processing | `KU-AD-06` |
| `SEG-AD-013` | `SRC-AD-006` | Troubleshooting, security, and verification sections | `KU-AD-06` |
| `SEG-AD-014` | `SRC-AD-007` | Attack paths and defensive monitoring principles | `KU-AD-07`,`KU-AD-08`,`KU-AD-09` |
| `SEG-AD-015` | `SRC-AD-008` | Secure remote administration and bastion principles | `KU-AD-07` |
| `SEG-AD-016` | `SRC-AD-009` | Detection hypothesis, data dependency, test, evidence lifecycle | `KU-AD-08` |
| `SEG-AD-017` | `SRC-AD-010` | Wazuh/lab monitoring architecture | `KU-AD-08` |
| `SEG-AD-018` | `SRC-AD-011` | Token build and AccessCheck sections only | `KU-AD-02` deep dive |
| `SEG-AD-019` | `SRC-AD-012` | DC Locator, DNS, GC, SYSVOL, admin separation | `KU-AD-01`,`KU-AD-07` |
| `SEG-AD-020` | `SRC-AD-013` | GPC/GPT, LDAP retrieval, filtering, CSE, Operational failures | `KU-AD-06` deep dive |
| `SEG-AD-021` | `SRC-AD-014` | NTLM/LLMNR/relay preconditions and admin path context only | `KU-AD-04`,`KU-AD-07` optional |

## 3.2 Official segments

| Segment ID | Parent | Selected topic | Use |
|---|---|---|---|
| `SEG-AD-030` | `SRC-AD-020` | Access tokens + security descriptors as model components | `KU-AD-02` |
| `SEG-AD-031` | `SRC-AD-021` | Token security context and contents | `KU-AD-02` |
| `SEG-AD-032` | `SRC-AD-022` | AD DS object/directory service overview | `KU-AD-01` |
| `SEG-AD-033` | `SRC-AD-023` | Discovery and closest-site DC selection | `KU-AD-01` |
| `SEG-AD-034` | `SRC-AD-024` | Principals, SIDs, access token relation | `KU-AD-01`,`KU-AD-02` |
| `SEG-AD-035` | `SRC-AD-025` | Group types/scopes and privileged groups | `KU-AD-01`,`KU-AD-07` |
| `SEG-AD-036` | `SRC-AD-026` | Kerberos/KDC/tickets/PAC overview | `KU-AD-03` |
| `SEG-AD-037` | `SRC-AD-027` | 4768 fields and TGT evidence | `KU-AD-03`,`KU-AD-08` |
| `SEG-AD-038` | `SRC-AD-028` | 4769 fields and service-ticket evidence | `KU-AD-03`,`KU-AD-08` |
| `SEG-AD-039` | `SRC-AD-029` | Audit-first NTLM restriction | `KU-AD-04` |
| `SEG-AD-040` | `SRC-AD-030` | Signing/CBT monitoring and enforcement guidance | `KU-AD-05` |
| `SEG-AD-041` | `SRC-AD-031` | Processing order, inheritance, filtering, refresh | `KU-AD-06` |
| `SEG-AD-042` | `SRC-AD-032` | ActivityID-based Operational log troubleshooting | `KU-AD-06` |
| `SEG-AD-043` | `SRC-AD-033` | Resultant Set of Policy via `gpresult` | `KU-AD-06` |
| `SEG-AD-044` | `SRC-AD-034` | Dedicated secure admin hosts | `KU-AD-07` |
| `SEG-AD-045` | `SRC-AD-035` | Approved pathways and enterprise access model | `KU-AD-07` |
| `SEG-AD-046` | `SRC-AD-036` | Never administer trusted system from less-trusted host | `KU-AD-07` |
| `SEG-AD-047` | `SRC-AD-037` | Windows Event Channel collection behavior | `KU-AD-08` |
| `SEG-AD-048` | `SRC-AD-038` | `localfile` configuration for Event Channels | `KU-AD-08` |
| `SEG-AD-049` | `SRC-AD-039` | Rule conditions and custom rules | `KU-AD-08` |
| `SEG-AD-050` | `SRC-AD-040` | MITRE IDs inside custom rules | `KU-AD-08` |
| `SEG-AD-051` | `SRC-AD-041` | Domain account abuse mapping | `KU-AD-09` |
| `SEG-AD-052` | `SRC-AD-042` | Domain group discovery mapping | `KU-AD-01`,`KU-AD-07` |
| `SEG-AD-053` | `SRC-AD-043` | GPO modification mapping | `KU-AD-06`,`KU-AD-09` |

## 3.3 Course segments

| Segment ID | Parent | Locator | Format/condition | Use/decision |
|---|---|---|---|---|
| `SEG-AD-060` | `SRC-AD-050` | `11. Domain Reconnaissance` | Images only in inspected copy | Visual supplement for `KU-AD-01` |
| `SEG-AD-061` | `SRC-AD-050` | `16. Kerberos` | Images + 4 videos + VTT | Practical supplement for `KU-AD-03` |
| `SEG-AD-062` | `SRC-AD-050` | `19. Group Policy` | Images only in inspected copy | Visual supplement for `KU-AD-06` |
| `SEG-AD-063` | `SRC-AD-050` | `24. Local Administrator Password Solution` | Images only in inspected copy | Optional for `KU-AD-07` |
| `SEG-AD-064` | `SRC-AD-051` | `Module 2 / Overview of AD Attacks & Tools` | Directory known; semantic review pending | Context for `KU-AD-07` |
| `SEG-AD-065` | `SRC-AD-051` | `Module 3 / 1. AD Tiering` | Video/slide module | Primary defensive supplement for `KU-AD-07` |
| `SEG-AD-066` | `SRC-AD-051` | `Module 3 / 2. Passwords` | Video/slide module | Credential-exposure supplement `KU-AD-07` |
| `SEG-AD-067` | `SRC-AD-051` | `Module 3 / 3. AD Features` | Video/slide module | Selected only after source review |
| `SEG-AD-068` | `SRC-AD-051` | `Module 3 / 4. Hardening` | Video/slide module | Defense supplement `KU-AD-07` |
| `SEG-AD-069` | `SRC-AD-052` | `13.8 NTLM Relaying Demo` | Focused demo | Observe preconditions only for `KU-AD-04` |
| `SEG-AD-070` | `SRC-AD-052` | `16.5 RSAT Demo` | Focused demo | Admin tooling context `KU-AD-07` |
| `SEG-AD-071` | `SRC-AD-052` | `16.7 SharpGPOAbuse Demo` | Focused demo | Impact understanding, no execution in core `KU-AD-06` |
| `SEG-AD-072` | `SRC-AD-052` | `15.5/15.9 Delegation demos` | Focused demos | Optional post-core only |
| `SEG-AD-073` | `SRC-AD-053` | Relevant enumeration/privilege modules | Copy/tool condition uncertain | Deferred gap filler |

---

# 4. Source selection by Knowledge Unit

| KU | Primary authority | Canonical synthesis | Practical/defensive supplement | Explicitly excluded |
|---|---|---|---|---|
| `KU-AD-01` | `SEG-AD-032..035` | `SEG-AD-001..002` | `SEG-AD-019`,`SEG-AD-060` | Course-wide recon binge |
| `KU-AD-02` | `SEG-AD-030..031`,`SEG-AD-034` | `SEG-AD-003..005` | `SEG-AD-018` | Exploit-focused token abuse |
| `KU-AD-03` | `SEG-AD-036..038` | `SEG-AD-006..007` | `SEG-AD-061` | Ticket forgery |
| `KU-AD-04` | `SEG-AD-039` + Microsoft protocol guidance as needed | `SEG-AD-008..009` | `SEG-AD-021`,`SEG-AD-069` | Live relay against nonisolated systems |
| `KU-AD-05` | `SEG-AD-040` | `SEG-AD-010..011`,`SEG-AD-004` | None required | Domain-wide enforcement before audit |
| `KU-AD-06` | `SEG-AD-041..043` | `SEG-AD-012..013` | `SEG-AD-020`,`SEG-AD-062`,`SEG-AD-071` | Modifying default GPOs |
| `KU-AD-07` | `SEG-AD-044..046` | `SEG-AD-014..015` | `SEG-AD-065..068`,`SEG-AD-070` | Advanced delegation in core path |
| `KU-AD-08` | `SEG-AD-047..050` + event sources | `SEG-AD-016..017` | Existing lab rules | Rules without negative tests |
| `KU-AD-09` | Source mix from prior KUs | `SEG-AD-014`,`SEG-AD-016` | `SEG-AD-051`,`SEG-AD-053` | Real Domain Admin compromise |

---

# 5. Unresolved source gaps

| Gap ID | الوصف | أثره | قرار ما قبل التنفيذ |
|---|---|---|---|
| `SG-AD-001` | لم تُراجع كل فيديوهات/VTT في `SRC-AD-050` دلالياً | قد توجد موضوعات ناقصة أوغير مطابقة للعناوين | افحص فقط الملفات المرتبطة بـ`SEG-AD-061` عند بدء KU-03 |
| `SG-AD-002` | `SRC-AD-051` فهرسه معروف لكن المحتوى الداخلي لم يُقرأ كاملاً | لا يجوز اعتباره Primary Authority | استخدمه بعد official baseline وبالأجزاء المختارة |
| `SG-AD-003` | Wazuh versions/config الفعلية غير متاحة | قد تختلف الحقول/القواعد | Gate 0 export مطلوب |
| `SG-AD-004` | Windows Server/client versions غير مؤكدة | Event schema قد يختلف بعد تحديثات 2025+ | Gate 0 baseline مطلوب |
| `SG-AD-005` | Domain FQDN/functional levels غير متاحة | يؤثر في Lab commands/expected behavior | Gate 0 baseline مطلوب |
| `SG-AD-006` | بعض Project notes تتضمن تفاصيل داخلية غير محكّمة | خطر اعتماد تفسير غير دقيق | Cross-check before teaching claim |
| `SG-AD-007` | لا يوجد Export حالي للـWazuh rules المذكورة | لا يمكن معرفة صحة الترابط الحالي | Gate 0 read-only export |

---

# 6. Source governance during execution

عند ظهور تعارض:

1. Microsoft/Open Specifications لآلية Windows/AD.
2. Wazuh official docs لسلوك Wazuh.
3. Raw lab evidence للسلوك الفعلي في إصدار المختبر.
4. CKV لتركيب الصورة وتسلسل التعلم.
5. Project notes للتعمق والأسئلة.
6. Courses للمشاهدة والتطبيق الانتقائي.

أي تصحيح مهم يسجل داخل Source Decision table أوفي ملاحظة KU، ولا يُعدل CKV خلال الـPilot إلا في مرحلة مستقلة.

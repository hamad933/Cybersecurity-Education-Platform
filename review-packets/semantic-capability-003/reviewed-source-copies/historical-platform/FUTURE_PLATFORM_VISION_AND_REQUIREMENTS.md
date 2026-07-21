# FUTURE PLATFORM VISION AND REQUIREMENTS

## رؤية ومتطلبات المنصة السيبرانية المستقبلية المتكاملة

**نوع الوثيقة:** رؤية المنتج، المتطلبات، الضوابط المعمارية، ووثيقة تسليم للمحادثة والفريق المستقبلي  
**الحالة:** وثيقة تأسيسية قابلة للتحسين قبل بدء التنفيذ الكبير  
**لغة الشرح:** العربية مع الإبقاء على المصطلحات التقنية الأساسية بالإنجليزية  
**هوية المشروع:** منصة أمن سيبراني تشغيلية وإنتاجية `Production-Grade Cybersecurity Platform`  
**علاقته بالمشروع القديم:** إعادة تصميم مستقلة؛ المشروع القديم مصدر أفكار ودروس وأمثلة وأخطاء فقط، وليس أساساً إلزامياً  
**الجمهور:** المحادثة الجديدة، مهندسو البرمجيات، مهندسو الأمن، خبراء المجالات، المراجعون، ومسؤولو التشغيل

---

# 0. غرض الوثيقة

هذه الوثيقة هي نقطة الانطلاق الرسمية لتصميم وبناء منصة أمن سيبراني جديدة، طويلة العمر، قابلة للتوسع، صالحة للاستخدام المؤسسي الحقيقي، ومبنية على عقود واختبارات وأدلة واضحة.

ليست المهمة إصلاح مشروع `CARE` القديم أو إعادة ترتيبه أو إضافة طبقات جديدة فوقه. يجوز الاستفادة منه في:

- استخراج الأفكار الجيدة.
- معرفة المتطلبات التي جرت تجربتها سابقاً.
- اكتشاف القرارات الضعيفة والتكرار والتضخم.
- إعادة استخدام بيانات اختبار أو أمثلة أو مصطلحات مفيدة بعد مراجعتها.
- فهم محاولات الربط بين `Proactive` و`Reactive` و`Evidence` و`Capabilities`.

لكن لا يجوز اعتبار أي من الآتي نهائياً لمجرد وجوده في المشروع القديم:

- الهيكلية.
- أسماء الملفات أو الأصناف.
- لغة السياسات.
- مخططات البيانات `Schemas`.
- طريقة الربط `Mappings`.
- محرك التنفيذ.
- اختيار قاعدة البيانات.
- عدد المجلدات أو القدرات.
- التوافق مع النسخ السابقة.

على المحادثة الجديدة أن تبدأ من المتطلبات، ثم تحلل المصادر، وتقارن بدائل معمارية، وتثبت قراراتها عبر `ADR` واختبارات، ثم تبني المشروع تدريجياً على شكل `Vertical Slices` مكتملة.

---

# 1. الرؤية التنفيذية

المطلوب بناء منصة موحدة تربط الدورة الأمنية كاملة:

```text
الأصول Assets
→ سياق الأعمال Business Context
→ المعمارية Architecture
→ التهديدات Threats
→ نقاط الضعف Weaknesses
→ الضوابط Controls
→ السياسات Policies
→ الاختبارات Assertions
→ الأدلة Evidence
→ النتائج Findings
→ المخاطر Risks
→ قواعد الكشف Detections
→ الحوادث Incidents
→ الاستجابة Responses
→ الإصلاح Remediation
→ التحقق Verification
→ إعادة الاختبار Retest
→ التقارير Reports
→ التحسين المستمر Lessons Learned
```

يجب أن تجمع المنصة بين:

- الوقاية `Preventive Security`.
- الأمن الاستباقي `Proactive Security`.
- الكشف `Detective Security`.
- الأمن الاستجابي `Reactive Security`.
- الاستجابة والتعافي `Respond & Recover`.
- الحوكمة والمخاطر والامتثال `GRC`.
- الضمان الأمني `Security Assurance`.
- التحقق الهجومي المصرح به `Offensive Validation`.
- العمل البنفسجي `Purple Teaming`.
- الأدلة والتدقيق `Evidence & Audit`.
- التعلم المؤسسي والتحسين المستمر.

المنصة ليست مطالبة بأن تستبدل كل المنتجات الأمنية. قيمتها الأساسية هي أن توحد المعنى والسياق والعلاقات والقرارات والتنفيذ والتحقق بين أدوات وفرق كثيرة.

---

# 2. هوية المنتج

## 2.1 ما هي المنصة؟

هي منصة تشغيلية يمكن أن تعمل بوصفها مزيجاً مترابطاً من:

- `Cybersecurity Operations Platform`.
- `Security Assurance Platform`.
- `Continuous Control Validation Platform`.
- `Security Orchestration and Safe Automation Platform`.
- `Risk and Exposure Context Platform`.
- `Detection, Incident, Response and Verification Platform`.
- `Cybersecurity Knowledge and Relationship Platform`.
- طبقة تكامل موحدة بين الأنظمة والأجهزة والمنتجات والفرق.

## 2.2 ما ليست عليه المنصة؟

ليست:

- بديلاً بسيطاً للـ`SIEM`.
- مجموعة `SOAR Playbooks` فقط.
- قائمة امتثال ثابتة.
- ماسح ثغرات فقط.
- أداة اختبار اختراق فقط.
- مشروعاً تعليمياً فقط.
- تطبيقاً أحادياً ضخماً `Monolith` يحوي أوامر كل Vendor داخله.
- آلاف ملفات `YAML` غير المختبرة.
- نظاماً يعتبر غياب الاستثناء نجاحاً.
- نظاماً يسمح للذكاء الاصطناعي بتغيير الإنتاج دون رقابة.

## 2.3 وحدة التعلم الاختيارية

يجوز إضافة `Learning and Mastery Module` لدعم:

- `Active Recall`.
- `Spaced Review`.
- إعادة تنفيذ السيناريوهات.
- قياس المهارات.
- ربط الدروس بالحالات العملية.
- تحويل `Lessons Learned` إلى معرفة قابلة للمراجعة.

لكن هذه الوحدة ثانوية وقابلة للتعطيل. يجب أن تبقى المنصة تشغيلية وإنتاجية كاملة دونها.

---

# 3. المشكلة التي تحلها المنصة

المعرفة والعمل الأمني موزع عادةً بين:

- ملاحظات وملفات شخصية.
- سياسات ومعايير.
- جداول `GRC`.
- `CMDB`.
- تنبيهات `SIEM`.
- نتائج ماسحات الثغرات.
- إعدادات الجدران النارية والسويتشات.
- أنظمة الهوية.
- التذاكر والحوادث.
- أدلة التدقيق.
- مستودعات الكود.
- منصات السحابة.
- أدوات `DevSecOps`.
- خبرة الأفراد غير الموثقة.

يجب أن تحول المنصة هذا التشتت إلى نموذج مركزي، مترابط، قابل للتنفيذ، قابل للتحقق، ومراقب بالتاريخ والصلاحيات.

ينبغي أن تستطيع الإجابة عن أسئلة مثل:

- ما الأصول الموجودة؟ ومن مالكها؟ وما أهميتها؟
- ما الخدمات التي تعتمد عليها المؤسسة؟
- ما التهديدات المنطبقة على كل أصل؟
- ما الضوابط المطلوبة؟
- كيف نثبت أن الضابط مطبق؟
- ما الأدلة التي بُني عليها الحكم؟
- هل الحالة الحالية تختلف عن `Intended State` أو `Known-Good State`؟
- ما تقنيات `MITRE ATT&CK` غير المغطاة؟
- ماذا يحدث عند وصول تنبيه؟
- هل الاستجابة آمنة لهذا الأصل والبيئة؟
- هل نجح الإجراء فعلياً؟
- ما القواعد التي فشلت أو أحدثت ضوضاء؟
- ما الذي تغير؟ ومن وافق عليه؟

---

# 4. الأهداف الاستراتيجية

يجب أن تحقق المنصة ما يلي:

1. توحيد `Proactive` و`Reactive` دون تكرار المحركات والتكاملات.
2. إنشاء نماذج مركزية موحدة مع إبقاء منطق المجالات مستقلاً.
3. إبقاء `Core` صغيراً ومستقراً.
4. تمكين إضافة معظم المعرفة دون تعديل كود Python.
5. تمكين إضافة المنتجات عبر `Plugins`.
6. تخصيص كل مؤسسة دون إنشاء نسخة متفرعة من المشروع.
7. فصل النية الأمنية عن طريقة التنفيذ الخاصة بالمنتج.
8. فرض الأتمتة الآمنة.
9. حفظ أصل الأدلة ومسار القرار.
10. مقارنة الحالة الحالية والتاريخية والمقصودة والمعتمدة.
11. دعم بيئات المؤسسات الحقيقية والمختبرات.
12. توفير `Web UI` و`API` سهلين لكن مضبوطين.
13. استخدام Git للمحتوى التصريحي القابل للمراجعة.
14. استخدام قواعد البيانات للحالة التشغيلية.
15. استخدام `Object Storage` للأدلة الكبيرة.
16. استخدام `Vault/Secrets Manager` للأسرار.
17. دعم الرصد والاختبار والاستعادة والتدقيق.
18. دعم النشر المحلي والمختبري والمؤسسي والموزع.
19. فرض موافقة بشرية عند ارتفاع الخطر.
20. السماح بإضافة مجالات أمنية مستقبلية دون هدم المنصة.

---

# 5. المبادئ المعمارية غير القابلة للتفاوض

## 5.1 فصل النية عن التنفيذ

```text
Policy / Control / Scenario = ماذا نريد؟ ولماذا؟
Capability                  = ما العملية الأمنية المطلوبة؟
Plugin / Connector          = كيف ينفذ المنتج هذه العملية؟
Primitive                   = الشكل التنفيذي العام
Transport                   = قناة الاتصال التقنية
Safety                      = هل يسمح بالتنفيذ؟
Verification                = كيف نثبت النجاح؟
Evidence                    = ما السجل الدائم لما حدث؟
```

لا توضع أوامر Vendors أو مسارات API أو بيانات المصادقة أو تفاصيل Parser داخل السياسات والسيناريوهات العامة.

## 5.2 نواة ثابتة وأطراف قابلة للتوسع

```text
معلومة أو Control جديد        → Content
عملية قابلة لإعادة الاستخدام  → Workflow
منطق مجال جديد                → Module
منتج أو Vendor جديد           → Plugin
تنفيذ Capability جديدة        → Plugin Capability
Primitive/Transport جديد       → Core Extension نادر
```

## 5.3 لا يوجد نجاح صامت

يجب دعم حالات صريحة، منها:

```text
passed
failed
matched
no_match
not_applicable
unsupported
insufficient_data
inconclusive
suppressed
exception_allowed
duplicate
blocked
approval_required
execution_error
verification_failed
manual_review_required
```

البيانات الناقصة، فشل Parser، خطأ القاعدة، أو عدم دعم الحدث لا يجوز أن تتحول إلى `Pass`.

## 5.4 الدليل قبل الحكم

كل نتيجة مهمة يجب أن تربط بـ:

- المدخلات.
- المصدر.
- وقت الجمع.
- إصدار Parser وSchema.
- الحقائق الموحدة.
- إصدار القاعدة أو النموذج.
- مسار القرار.
- مستوى الثقة.
- مرجع الدليل الخام.
- الموافقات.
- نتيجة التنفيذ.
- نتيجة التحقق.

## 5.5 الأمان هو الوضع الافتراضي

```text
Read-only                    → يسمح بعد المصادقة وتحديد النطاق
Low-risk reversible change   → Dry-run + Policy Checks
High-risk action             → Approval + Rollback + Verification
Critical / Tier-0            → موافقات أقوى ونطاق صارم وحوكمة طوارئ
Unknown or unsupported       → Blocked
```

## 5.6 كل محتوى مهم له دورة حياة

كل `Control` و`Scenario` و`Workflow` و`Detection` و`Assertion` و`Response` و`Verification` يجب أن يحوي:

- ID ثابتاً.
- Version.
- Owner.
- Status.
- Review cadence.
- سجل تغيير.
- حالة اعتماد.
- اختبارات.
- سياسة إهمال `Deprecation`.

## 5.7 التطوير عبر Vertical Slices

لا تبنِ مئات المكونات قبل إثبات دورة واحدة كاملة:

```text
Input/Event
→ Normalize
→ Enrich
→ Decide
→ Plan Safely
→ Execute/Dry-run
→ Verify
→ Preserve Evidence
→ Report
```

---

# 6. المجالات الأمنية المطلوب دعمها

الهدف تغطية غالبية مسارات الأمن السيبراني عبر Modules مستقلة وعقود مشتركة، لا ادعاء إتقان كل شيء في الإصدار الأول.

## 6.1 الحوكمة والمخاطر والضمان

- `Asset Management` و`CMDB`.
- `Security Governance`.
- `Policy Management`.
- `Risk Management`.
- `FAIR` والتحليل الكمي والنوعي.
- `Business Impact Analysis`.
- `Control Library`.
- `Control Ownership`.
- `Continuous Control Monitoring`.
- `Audit and Assurance`.
- الاستثناءات والمخاطر المقبولة.
- ربط أطر الامتثال.
- إدارة الأدلة.
- المقاييس والنضج الأمني.
- مخاطر الأطراف الثالثة وسلسلة التوريد.
- المتطلبات القانونية والتعاقدية.
- `Information Assurance`.

## 6.2 المعمارية والأمن الوقائي

- `Security Architecture`.
- `Trust Boundaries`.
- `Defense in Depth`.
- `Zero Trust`.
- `Secure Design`.
- `Threat Modeling`.
- `STRIDE`.
- `PASTA`.
- `Attack Trees`.
- `Abuse/Misuse Cases`.
- التقسيم الشبكي.
- معمارية الهوية.
- `Secure Baselines` و`Hardening`.
- التشفير وإدارة المفاتيح.
- حماية البيانات والخصوصية.
- `Secure SDLC` و`DevSecOps`.

## 6.3 المجالات التقنية

- أمن الشبكات والتقسيم.
- أمن البروتوكولات وتحليل `PCAP`.
- Windows وWindows Internals.
- Active Directory وKerberos وNTLM وLDAP وGPO وAD CS.
- Linux Security.
- Endpoint وEDR.
- IAM وIGA وPAM وMFA وFederation.
- Web Application Security.
- API Security.
- Database Security.
- Cloud Security.
- Containers وKubernetes.
- Infrastructure as Code.
- CI/CD Security.
- Software Supply Chain وSBOM.
- SaaS Security.
- Secrets Management.
- Data Classification وDLP.
- OT/ICS/IoT بوصفها Domains اختيارية محكومة.

## 6.4 التعرض والثغرات ومسارات الهجوم

- اكتشاف الأصول.
- `ASM/EASM/CAASM`.
- `Exposure Management`.
- `Vulnerability Management`.
- CVE وCWE وCVSS وEPSS وسياق الاستغلال.
- `Configuration Drift`.
- `Attack Path Analysis`.
- مسارات صلاحيات الهوية.
- `Blast Radius`.
- `Compensating Controls`.
- `Control Validation`.
- `Breach and Attack Simulation`.
- `Adversary Emulation`.

## 6.5 الكشف والاستجابة

- إدارة Telemetry.
- Event Normalization.
- Detection Engineering.
- Detection as Code.
- Correlation.
- Behavioral Analytics.
- Anomaly Detection.
- SOC Operations.
- Alert Triage.
- Case/Incident Management.
- Threat Hunting.
- Threat Intelligence.
- Incident Response.
- Containment وEradication وRecovery.
- Digital Forensics.
- تكامل Malware Analysis.
- SOAR وSafe Automation.
- Detection Validation.
- MTTD وMTTR.
- Post-Incident Review.

## 6.6 الهجوم المصرح والعمل البنفسجي

- PTES.
- Reconnaissance.
- Vulnerability Validation.
- Controlled Exploitation داخل نطاق مصرح.
- Privilege Escalation Validation.
- Lateral Movement Validation.
- ATT&CK Mapping.
- Telemetry Validation.
- Detection Testing.
- Response Testing.
- Retesting.
- Purple Team Closed Loop.

## 6.7 الاستمرارية والتعافي

- BCP وDR.
- RTO وRPO.
- Backup Assurance.
- Recovery Testing.
- Crisis Management.
- Service Dependency Analysis.
- Tabletop Exercises.
- Operational Resilience.
- Cyber Recovery Validation.

---

# 7. الأدوار والصلاحيات

يجب أن تدعم المنصة `RBAC/ABAC` للأدوار التالية على الأقل:

- Platform Administrator.
- Security Engineer.
- SOC Analyst L1/L2/L3.
- Detection Engineer.
- Incident Responder.
- Threat Hunter.
- Vulnerability Analyst.
- Risk/GRC Analyst.
- Security Architect.
- Identity Administrator.
- Network Administrator.
- Cloud Security Engineer.
- DevSecOps Engineer.
- Red Team Operator.
- Purple Team Lead.
- Auditor.
- Asset Owner.
- Business Owner.
- Change Approver.
- Executive Viewer.
- Plugin Developer.
- Content Author/Reviewer.

الصلاحيات يجب أن تفصل بين:

```text
View
Create
Edit
Review
Approve
Publish
Execute
Rollback
Verify
Export
Manage Connectors
Manage Secret References
Emergency Override
```

---

# 8. المعمارية العليا

```text
Users / APIs / Webhooks / Schedulers / Message Queues
                         ↓
                    API + Web UI
                         ↓
          Authentication + Authorization
                         ↓
                 Request / Run Context
                         ↓
                  Orchestration Layer
                         ↓
       ┌─────────────────┼─────────────────┐
       ↓                 ↓                 ↓
 Policy Engine      Workflow Engine   Scenario Engine
       ↓                 ↓                 ↓
              Domain Modules / Decision Services
                         ↓
                 Capability Requests
                         ↓
               Capability / Plugin Registry
                         ↓
             Connector Selection and Routing
                         ↓
     Safety → Approval → Dry-run → Rate Limits
                         ↓
         Primitive Executor → Transport Adapter
                         ↓
            External Products and Infrastructure
                         ↓
        Parser → Normalize → Verify → Evidence
                         ↓
PostgreSQL / Search / Graph / Object Storage / Audit
```

---

# 9. المكونات الأساسية

## 9.1 Core Kernel

النواة صغيرة وغير مرتبطة بمجال أمني محدد، وتشمل:

- IDs.
- Time/Clock.
- Result types.
- Errors.
- Run Context.
- Lifecycle states.
- Commands/Queries/Events contracts.
- Dependency Injection.
- Transaction boundaries.
- Versioning rules.

لا تحتوي النواة على منطق Vendor أو أوامر منتجات.

## 9.2 Orchestration Layer

المسؤوليات:

- استقبال الطلبات والأحداث.
- اختيار نوع التشغيل.
- بناء Execution Plan.
- إدارة Dependencies.
- Dispatch للـWorkers.
- Checkpoints وResume.
- Retry وCompensation.
- Timeout وCancellation.
- Correlation IDs.

الـOrchestrator ينسق ولا يتحول إلى `God Object`.

## 9.3 Domain Modules

الـModule يمتلك معنى المجال وقواعده، مثل:

```text
assets
architecture
governance
risk
controls
assurance
threat_modeling
identity
active_directory
network
windows
linux
cloud
application_security
api_security
devsecops
vulnerability
detection_engineering
soc
incident_response
threat_hunting
digital_forensics
resilience
```

كل Module قد يحتوي:

- Domain Models.
- Value Objects.
- Commands.
- Queries.
- Events.
- Services.
- Policies.
- Permissions.
- Repositories.
- API routes.
- Migrations.
- Tests.

الـModule لا يتصل مباشرة بمنتج محدد.

## 9.4 Plugin System

الـPlugin يربط المنصة بمنتج أو Vendor، مثل:

- FortiGate.
- Cisco IOS.
- Palo Alto.
- Active Directory.
- Entra ID.
- Wazuh.
- Security Onion.
- Splunk/Elastic.
- Nessus/OpenVAS.
- NetBox/Nautobot.
- ServiceNow/Jira.
- AWS/Azure/GCP.
- Kubernetes.
- GitHub/GitLab.
- MISP/OpenCTI.

الـPlugin يقدم:

- Connectors.
- Capabilities.
- Parsers.
- Normalizers.
- Health Checks.
- Supported Auth.
- Rollback.
- Verification.
- Rate limits.
- Fixtures وContract Tests.

## 9.5 Capability Engine

الـCapability اسم ثابت لعملية أمنية، مثل:

```text
asset.get_context
identity.get_user
identity.disable_account
identity.revoke_tokens
network.get_interface_config
firewall.block_source_ip
siem.search_events
endpoint.isolate_host
vulnerability.run_targeted_scan
ticket.open_incident
notification.send
verification.check_account_disabled
```

السياسة أو Workflow تطلب Capability، والـPlugin يوفر تنفيذها.

محرك Capability يجب أن:

1. يتحقق من الطلب.
2. يحل Provider.
3. يختار Connector.
4. يتحقق من Parameters.
5. يطبق Policy وSafety.
6. يبني العملية التنفيذية.
7. ينفذ Primitive.
8. يحلل النتيجة.
9. يسجل Rollback.
10. يشغل Verification.
11. يحفظ Evidence.

## 9.6 Primitives وTransports

Primitives عامة:

```text
api_request
command
query
workflow
ticket
notification
manual_task
ansible_playbook
terraform_plan
kubernetes_manifest
file_operation
message_publish
```

Transports:

```text
HTTP / GraphQL / gRPC
SSH / WinRM
LDAP / SQL / SNMP
NETCONF / RESTCONF
Kubernetes API / Cloud SDK
Local Command
Message Queue
SMTP / Webhook
Ansible Runner / Terraform Runner
File Artifact
```

إضافة منتج جديد تعني غالباً Plugin، لا Primitive جديداً.

## 9.7 Content System

المحتوى التصريحي القابل للمراجعة يشمل:

- Knowledge Items.
- Controls.
- Policies.
- Baselines.
- Assertions.
- Detections.
- Correlation Rules.
- Threat Models.
- Scenarios.
- Workflows.
- Response Actions.
- Remediations.
- Verifications.
- Exceptions.
- Suppressions.
- Framework Mappings.
- Report Templates.

كل محتوى يخضع لـSchema وVersioning وReview.

## 9.8 Policy Engine

يدعم:

- Policy as Code.
- Control as Code.
- Applicability.
- Scope.
- Assertion Evaluation.
- Exceptions.
- Suppression.
- Compensating Controls.
- Simulation.
- Explainable Results.
- Conflict/Dependency Validation.

## 9.9 Scenario Engine

السيناريو حالة تشغيلية قابلة للتنفيذ والاختبار، وقد يمثل:

- Alert handling.
- Proactive assurance.
- Purple Team exercise.
- Vulnerability validation.
- Drift investigation.
- Incident response.
- Resilience test.
- Authorized attack simulation.
- Learning lab اختياري.

كل Scenario يحدد:

- Objective.
- Preconditions.
- Scope.
- Assets.
- Trigger.
- Initial State.
- Required Capabilities.
- Steps.
- Expected Evidence.
- Expected Detections.
- Decision Logic.
- Response.
- Approval.
- Rollback.
- Verification.
- Cleanup.
- Success/Failure Criteria.
- Mappings.
- Test Fixtures.
- Owner/Review.

## 9.10 Event Engine

المسؤوليات:

- Ingest من API/Webhook/File/Queue/Schedule.
- تخزين Raw Event.
- Signature/Source Validation.
- Deduplication.
- Schema Validation.
- Canonical Normalization.
- Correlation.
- Replay.
- Dead-Letter Queue.
- Parser/Normalizer failure tracking.

## 9.11 Evidence Engine

يدير:

- Raw Artifacts.
- Parsing وNormalization.
- Provenance.
- Hashing/Timestamping.
- Chain of Custody.
- Redaction/Classification.
- Retention.
- Confidence.
- Evidence Packages.
- Legal Hold.
- Integrity Verification.

## 9.12 Decision Engine

يدعم:

- Rule Evaluation.
- Confidence Scoring.
- Risk Scoring.
- Triage/Prioritization.
- Explainability.
- Human Review Routing.
- Uncertainty.
- False Positive/Negative Feedback.
- Rule/Model Version Tracking.

## 9.13 Graph and Relationship Engine

يربط:

```text
Organization
Site
Business Service
Asset
Identity
Software
Data
Threat
Technique
Weakness
Vulnerability
Control
Policy
Assertion
Evidence
Finding
Risk
Detection
Scenario
Incident
Response
Remediation
Verification
Framework Requirement
Owner
Skill
```

ويستخدم في:

- Impact Analysis.
- Attack Paths.
- Blast Radius.
- Control/Detection Coverage.
- Framework Crosswalks.
- Evidence Traceability.
- Root Cause.
- Dependency Analysis.
- Skill/Knowledge Links.

---

# 10. نموذج التخزين المركزي

## 10.1 Git: حقيقة المحتوى Versioned Content Source of Truth

يخزن:

- Controls.
- Policies.
- Scenarios.
- Workflows.
- Detections.
- Assertions.
- Mappings.
- Templates.
- Plugin manifests.
- Schemas.
- Documentation.

## 10.2 PostgreSQL: حقيقة الحالة التشغيلية Operational Source of Truth

يخزن:

- Organizations/Environments.
- Assets/Identities.
- Findings/Risks.
- Incidents/Cases.
- Approvals.
- Runs/Schedules.
- Active Versions.
- Audit Records.
- Connector Registrations.
- Review Tasks.
- Execution State.
- العلاقات التشغيلية في البداية.

## 10.3 Object Storage

يخزن:

- PCAP.
- Raw Logs.
- Scanner Exports.
- Screenshots.
- Forensic Artifacts.
- Reports.
- Evidence Packages.
- Large Backups.

## 10.4 Search Index

لـ:

- Events.
- Findings.
- Investigations.
- Evidence metadata.
- Full-text search.

## 10.5 Graph Storage

يفضل البدء بـPostgreSQL وعلاقات واضحة. لا تضاف Graph Database مستقلة إلا عند إثبات الحاجة الفعلية.

## 10.6 Secrets Manager

لا تخزن الأسرار في Git أو السياسات أو قاعدة البيانات العادية. المنصة تحفظ `secret_ref` فقط.

---

# 11. واجهة الويب وAPI

الواجهة أداة تشغيل وتأليف ومراجعة، وليست اتصالاً مباشراً بقاعدة البيانات.

المسار الإجباري:

```text
Web UI
→ API
→ Authentication
→ Authorization
→ Schema Validation
→ Business Rules
→ Version/Transaction
→ Storage
→ Audit Event
```

يجب أن تحتوي على:

- Dashboards.
- Assets/Services Graph.
- Findings/Risks.
- Alert/Incident Queue.
- Evidence Viewer.
- Control/Policy Editor.
- Assertion Builder.
- Detection Editor.
- Workflow Builder.
- Scenario Builder.
- Relationship Builder.
- Plugin/Connector Management.
- Approval Inbox.
- Dry-run Review.
- Version History.
- Publish Workflow.
- Coverage Dashboards.
- Audit Log.
- Organization Settings.
- Learning pages اختيارية.

---

# 12. النماذج الموحدة والـIDs

كل كيان مهم يمتلك ID مستقراً وVersion.

```text
ORG-
ENV-
SITE-
AST-
IDN-
APP-
DATA-
THR-
TTP-
WEAK-
VULN-
CTRL-
POL-
ASTN-
DET-
SCN-
WF-
CAP-
CONN-
FIND-
RISK-
INC-
ACT-
REM-
VER-
EVD-
RUN-
APPROVAL-
```

الحقول المشتركة:

- ID/Type/Version.
- Title/Description.
- Status.
- Owner/Maintainers.
- Organization/Environment.
- Scope/Tags/Classification.
- Created/Updated.
- Review Cadence.
- Source.
- Relationships.
- Schema Version.
- Deprecation.

---

# 13. معنى المركزية

المركزية لا تعني Monolith.

تتمركز:

- IDs.
- Schemas.
- Registries.
- Relationships.
- Policy Governance.
- Workflow State.
- Safety.
- Evidence.
- Audit.
- Active Versions.
- Access Control.

ويتوزع:

- منطق المجالات داخل Modules.
- تكامل المنتجات داخل Plugins.
- المعرفة داخل Content Packages.
- التنفيذ الطويل داخل Workers.
- الأدلة الكبيرة داخل Object Storage.

لا يسمح لكل Module باختراع تعريف مختلف للأصل أو الحدث أو الدليل أو الحادث.

---

# 14. عقد الـModule

مثال Manifest:

```yaml
module_id: identity
version: 1.0.0
engine_compatibility: ">=1.0,<2.0"
provides:
  commands: []
  queries: []
  events: []
  permissions: []
  api_routes: []
  background_jobs: []
depends_on:
  - assets
optional_dependencies:
  - risk
data_migrations: []
health_checks: []
```

يجب أن يسجل Module نفسه عبر SDK، دون تعديل Orchestrator.

يلزم كل Module:

- Documentation.
- Threat Model.
- Contract Tests.
- Migration Tests.
- Permission Tests.
- API Tests.
- Event Compatibility Tests.
- Upgrade/Deprecation Policy.

---

# 15. عقد الـPlugin

مثال:

```yaml
plugin_id: identity.active_directory
version: 1.0.0
engine_compatibility: ">=1.0,<2.0"
domain: identity
vendor: microsoft
product: active_directory
provides_capabilities:
  - identity.get_user
  - identity.disable_account
  - identity.enable_account
  - identity.remove_group_membership
  - identity.verify_account_state
supported_transports:
  - ldap
  - winrm
supported_auth:
  - kerberos
  - ldap_bind
  - winrm_kerberos
side_effects: true
config_schema: schemas/config.json
```

شروط Plugin:

1. تطبيق Capability Contract.
2. إخراج Canonical Models.
3. إعلان Read/Write/Destructive.
4. إعلان الصلاحيات المطلوبة.
5. دعم Dry-run حيث يمكن.
6. تعريف Rollback للتغييرات القابلة للعكس.
7. تعريف Verification.
8. إنتاج Evidence.
9. تطبيق Timeout/Rate Limit.
10. اجتياز Contract Tests.
11. عدم وجود Side Effects مخفية.
12. عدم تخزين أسرار صريحة.

---

# 16. دورة حياة المحتوى

```text
draft
→ validation_failed / validated
→ review_required
→ approved
→ published
→ active
→ deprecated
→ archived
```

النشر يتطلب:

- Schema Validation.
- Reference Validation.
- Dependency Validation.
- Mapping Validation.
- Security Validation.
- Tests.
- Human Review.
- Approval.
- Audit Record.

واجهة الويب قد تنشئ المحتوى، لكن الناتج يجب أن يكون Versioned وقابلاً للمراجعة والاعتماد.

---

# 17. دورات التشغيل

## 17.1 Proactive Assurance

```text
Schedule / Manual Request
→ Scope Resolution
→ Asset Selection
→ Applicable Controls
→ Assertion Selection
→ Evidence Collection
→ Parse
→ Normalize
→ Evaluate
→ Compare / Drift
→ Finding
→ Risk Context
→ Remediation Plan
→ Approval / Dry-run
→ Execute
→ Verify
→ Retest
→ Evidence Package
→ Report
```

## 17.2 Reactive Detection and Response

```text
Alert / Event / Webhook
→ Raw Ingest
→ Validate
→ Canonical Normalize
→ Enrich
→ Detect
→ Correlate
→ Triage
→ Exception / Suppression
→ Incident Decision
→ Investigation
→ Response Plan
→ Approval / Dry-run
→ Containment
→ Eradication
→ Recovery
→ Verification
→ Evidence Package
→ Reporting
→ Learning Candidate
```

## 17.3 Hybrid Scoped Assurance

عند وصول Alert لا يشغل النظام فحص Proactive كاملاً، بل:

```text
Reactive Event
→ Matched Rule
→ Select Related Assertions
→ Run Only on Affected Asset/Object
→ Compare Live / Backup / Known-Good / Intended
→ Produce Drift/Control Proof
→ Continue Triage and Response
```

## 17.4 Purple Team

```text
Threat/Technique
→ Authorized Test
→ Expected Telemetry
→ Detection
→ Correlation
→ Triage
→ Response
→ Verification
→ Remediation
→ Retest
→ Coverage Update
```

## 17.5 Resilience

```text
Failure Hypothesis
→ Business Service Scope
→ Dependency Map
→ Controlled Simulation
→ Detection
→ Continuity Action
→ Recovery
→ RTO/RPO Measurement
→ Evidence
→ Improvement
```

---

# 18. تهيئة مؤسسة جديدة

لا تعدل Core لكل مؤسسة. عملية `Organization Onboarding` تشمل:

## 18.1 تعريف المؤسسة

- Business Units.
- Sites.
- Environments.
- Time Zones.
- Working Hours.
- Maintenance Windows.
- Data Classification.
- Retention.
- Regulatory Requirements.

## 18.2 إدخال الأصول والخدمات

- CMDB Import.
- Discovery.
- Normalization.
- Ownership.
- Criticality.
- Service Dependencies.
- Zones/Tiers.
- Internet Exposure.
- Tier-0/Crown Jewels.

## 18.3 ربط المنتجات

- Register Plugins.
- Create Connector Instances.
- Endpoints.
- Secret References.
- Read Test.
- Dry-run/Write Test.
- Health Validation.
- Production Scope Approval.

## 18.4 تخصيص السياسات

- Organizational Policies.
- Framework Baselines.
- Exceptions.
- Risk Acceptance.
- Approval Roles.
- SLAs.
- Evidence Retention.
- Control-to-Asset Mapping.

## 18.5 التفعيل التدريجي

```text
Monitor-only
→ Tune
→ Validate Evidence
→ Dry-run
→ Lab Execution
→ Production Canary
→ Limited Auto-safe
→ Continuous Review
```

---

# 19. معنى السيناريو وفائدته

السيناريو ليس قصة تعليمية، بل أصغر حزمة تشغيلية كاملة تحدد:

- الحالة المهمة.
- النطاق.
- البيانات المطلوبة.
- طريقة الاختبار أو الكشف.
- الدليل المتوقع.
- القرار.
- الاستجابة.
- السلامة.
- التحقق.
- التنظيف.

مثال:

```text
Suspicious privileged login from a user workstation
```

```text
Wazuh Event
→ Canonical Auth Event
→ Identity Context
→ Asset Tier/Zone
→ Privileged Detection
→ Incident
→ Ticket
→ Approval
→ Revoke/Disable/Isolate
→ Verify
→ Evidence
```

دون Scenarios تكون لدينا Functions منفصلة. بالسيناريو تتحول إلى عملية أمنية كاملة.

---

# 20. تجربة التأليف عبر Web UI

## 20.1 Control Builder

- Title/Objectives.
- Scope/Owner.
- Applicable Assets.
- Preventive/Detective/Corrective.
- Framework Mappings.
- Required Evidence.
- Assertion.
- Failure Meaning.
- Remediation.
- Verification.
- Review Cycle.

## 20.2 Scenario Builder

```text
Objective
→ Trigger
→ Scope
→ Preconditions
→ Assets
→ Enrichment
→ Detection/Test
→ Expected Evidence
→ Triage
→ Response
→ Approval
→ Rollback
→ Verification
→ Cleanup
→ Mappings
→ Test Cases
→ Publish
```

## 20.3 Workflow Builder

يدعم:

- Steps/Dependencies.
- Conditions.
- Parallel branches.
- Timeouts/Retries.
- Approvals.
- Compensation.
- Checkpoints.
- Manual Tasks.
- Reusable Sub-workflows.
- Version Diff.

## 20.4 Relationship Builder

يربط من قوائم ورسوم:

```text
Asset → Threat → Weakness → Control → Assertion
→ Evidence → Detection → Scenario → Response → Verification
```

والـAPI يتحقق من صحة العلاقة وفق Ontology.

---

# 21. الذكاء الاصطناعي AI Assistance

الذكاء الاصطناعي اختياري ومساعد، وليس أساس القرار الحتمي.

```text
Deterministic Rules
+ Correlation
+ Behavioral Analytics
+ Anomaly Detection
+ AI Assistance
+ Human Review
```

## 21.1 الاستخدامات المفيدة

- تفسير أحداث غير معروفة.
- اقتراح Classification.
- اقتراح ATT&CK Mapping.
- تلخيص Evidence.
- توليد Investigation Questions.
- اقتراح Enrichment Queries.
- كشف Coverage Gaps.
- اقتراح Detection Candidate.
- اقتراح Scenario Draft.
- مقارنة Incident بحوادث سابقة.
- اقتراح Tuning للـFalse Positives.
- كشف Parser/Schema gaps.
- مساعدة التوثيق والتقارير.

## 21.2 ما لا يسمح للـAI بفعله افتراضياً

- نشر Rule في Production.
- قبول Risk.
- تعطيل حساب.
- عزل Endpoint.
- حظر شبكة.
- حذف مورد.
- تعديل Firewall/Cloud Permissions.
- تعديل Evidence.
- إغلاق Incident حرج.
- تحويل عدم اليقين إلى Pass.

## 21.3 دورة AI Candidate

```text
AI Suggestion
→ Candidate Object
→ Provenance/Prompt Record
→ Schema Validation
→ Safety Checks
→ Test Dataset
→ Replay/Simulation
→ FP Analysis
→ Human Review
→ Approval
→ Versioned Publication
```

---

# 22. التعامل مع الفشل والأحداث غير المعروفة

## 22.1 عدم وجود قاعدة

```text
No Exact Rule
→ Generic Context Rules
→ Behavioral Baseline
→ Correlation
→ Anomaly Layer
→ AI Assistance
→ Human Review
→ Candidate Rule
```

## 22.2 فشل القاعدة

```text
Preserve Raw Event
→ Record Rule ID/Version/Error
→ Record Affected Events
→ Dead-Letter Queue
→ Safe Retry
→ Notify Owner
→ Create Defect
→ Fallback Generic Analysis
```

## 22.3 فشل Parser/Normalizer

- حفظ Raw Input.
- تصنيف الخطأ.
- تحديد الحقول الناقصة أو غير المتوقعة.
- Parser-gap Workflow.
- عدم إسقاط الحدث.
- Replay بعد الإصلاح.

## 22.4 نقص الأدلة

النتيجة:

```text
inconclusive
```

مع:

- Missing Evidence.
- Attempted Sources.
- Reason.
- Confidence.
- Next Collection.
- Human Review.

---

# 23. معمارية السلامة

Safety مركزية ومستقلة عن كاتب المحتوى.

## 23.1 البوابات

- Authentication/Authorization.
- Organization/Environment Scope.
- Asset Criticality.
- Target Validation.
- Parameter Schema.
- Connector Capability.
- Policy Trust.
- Approval.
- Dry-run.
- Rate Limit.
- Change Window.
- Backup Requirement.
- Rollback Requirement.
- Verification Requirement.
- Max Targets/Actions.
- Evidence Retention.

## 23.2 حالات منع إلزامية

- Wildcard destructive target.
- Any/Any rule دون موافقة استثنائية.
- Tier-0 destructive action دون Emergency Governance.
- Production change خارج النطاق.
- Missing required rollback.
- Missing required verification.
- Delete without backup عند وجوب الاستعادة.
- Inline secrets.
- Untrusted plugin.
- Unsupported capability.
- AI-generated execution دون Review.

## 23.3 الموافقات

- Single approver.
- Two-person approval.
- SOC/Asset/Network/Identity/Cloud owner.
- Change Manager.
- Emergency override مع سبب وAfter-action review.
- Approval expiry.
- Separation of Duties.

## 23.4 Rollback/Compensation

تمييز:

- Direct rollback.
- Restore backup.
- Reverse capability.
- Compensating action.
- Manual recovery.
- Non-reversible action.

---

# 24. أمن المنصة نفسها

المنصة High-Value System ويجب Threat Model خاص بها.

الحد الأدنى:

- Strong Authentication + MFA.
- RBAC/ABAC.
- Least Privilege.
- Service Identities.
- mTLS عند الحاجة.
- External Secret Resolution.
- Encryption in transit/at rest.
- Signed Content/Plugins.
- Plugin Isolation.
- Audit/Tamper Evidence.
- CSRF/XSS/SSRF protection.
- Safe Template Rendering.
- Command Injection prevention.
- Path Traversal prevention.
- Safe Deserialization.
- Rate Limiting.
- Secure Sessions/APIs.
- Tenant Isolation.
- Backup/Restore.
- Dependency/Supply-chain Security.
- SBOM/Artifact Signing.
- Security Tests.
- Break-glass controls.
- Admin Action Monitoring.

---

# 25. المتطلبات غير الوظيفية

## 25.1 Reliability

- Idempotency حيث يمكن.
- Retry with backoff.
- Dead-letter handling.
- Durable workflow state.
- Checkpoints.
- Transactions.
- Graceful degradation.
- Partial-success states.
- No silent loss.
- Backup/Recovery.
- Replay.

## 25.2 Scalability

- Stateless API workers.
- Async workers.
- Queue-based tasks.
- Horizontal scaling.
- Organization partitioning.
- Retention policies.
- Pagination.
- Batch limits.
- Rate-aware connectors.

## 25.3 Performance

- Fast validation.
- Cached registries.
- Indexed queries.
- Async evidence processing.
- Bounded workflows.
- Timeouts/Query budgets.
- Priority queues.

## 25.4 Maintainability

- Bounded Modules.
- Stable Contracts.
- ADRs.
- Schema Versioning.
- Typed Code.
- CI Quality Gates.
- Generated Docs.
- No duplicate implementations.
- لا ZIP/backup files داخل Source.
- Controlled Deprecation.
- Migration Tools.

## 25.5 Portability

- Developer mode.
- Offline lab.
- Docker/Kubernetes.
- Single-node/Distributed.
- On-prem/Private Cloud/Public Cloud.

## 25.6 Observability

- Structured Logs.
- Metrics/Traces.
- Correlation IDs.
- Health checks.
- Queue depth.
- Connector health.
- Workflow status.
- Rule/Parser failure rate.
- Execution/Approval latency.
- MTTD/MTTR.
- Evidence completeness.
- Coverage metrics.

## 25.7 Privacy

- Data minimization.
- Classification/Redaction.
- Retention.
- Access logging.
- Regional storage.
- Legal hold.
- Deletion workflow.
- AI privacy policy.
- منع إرسال بيانات إلى AI خارجي دون Policy صريحة.

---

# 26. عملية إضافة معرفة جديدة

```text
Learn
→ Atomize
→ Classify
→ Map
→ Implement
→ Validate
→ Review
→ Publish
→ Retest/Review Schedule
```

التصنيف:

```text
Knowledge Item             → content/knowledge
Control                    → content/controls
Assertion                  → content/assertions
Detection                  → content/detections
Workflow                   → workflows
Scenario                   → content/scenarios
Organization Setting       → environment configuration
Product Integration        → plugin
New Domain Logic           → module
New Generic Execution Form → core primitive, rarely
```

كل مفهوم مهم يربط قدر الإمكان بـ:

- Assets.
- Threats.
- Weaknesses.
- Controls.
- Evidence.
- Detections.
- Response.
- Verification.
- Scenario.
- References.
- Owner/Review.

---

# 27. Quality Gates

## 27.1 Code

- Format/Lint.
- Type Check.
- Unit/Integration/Contract Tests.
- Security Tests.
- Dependency Scan.
- Secret Scan.
- SBOM.
- Signed Release.

## 27.2 Content

- Schema.
- Unique IDs.
- Valid References.
- Dependency/Cycle checks.
- Mapping validation.
- Owner/Review date.
- Test fixture للمحتوى التنفيذي.
- Safety metadata.
- Rollback/Verification عند اللزوم.
- Deprecation policy.

## 27.3 Plugins

- Manifest/Compatibility.
- Permission declaration.
- Sandbox review.
- Capability contracts.
- Auth/Parser tests.
- Timeout/Rate-limit tests.
- Dry-run/Rollback/Verification tests.
- Negative tests.
- Security review.

## 27.4 Scenarios

- Prerequisites/Scope/Authorization.
- Expected evidence/result.
- Cleanup.
- Lab test.
- Replay.
- Failure paths.
- Safety review.
- Production approval.

---

# 28. استراتيجية الاختبار

يلزم:

- Unit.
- Integration.
- Contract.
- Schema.
- Policy.
- Scenario.
- End-to-End.
- Security.
- Performance.
- Resilience.
- Chaos.
- Migration/Upgrade.
- Golden Regression.
- Plugin Compatibility.
- Disaster Recovery.

بيئات الاختبار:

```text
Mock
→ Simulated Connectors
→ Container Integration
→ Cyber Lab
→ Staging Organization
→ Controlled Production Canary
```

---

# 29. استراتيجية التنفيذ المرحلي

## Phase 0 — Discovery and Architecture Proof

المخرجات:

- Source Inventory.
- Requirements Traceability Matrix.
- Domain Map.
- Data Ownership Map.
- Architecture Alternatives.
- ADRs.
- Threat Model.
- Canonical Data Model Draft.
- First Vertical Slice.
- Repository Skeleton.
- CI Gates.

## Phase 1 — Foundation

- Core Contracts.
- Authentication/Authorization.
- Organization/Environment.
- Assets.
- Content Registry.
- PostgreSQL.
- Object Storage abstraction.
- Audit.
- Basic API/UI.
- Plugin SDK.
- Capability Contract.
- Safe Dry-run.

## Phase 2 — أول Vertical Slice مكتمل

السيناريو المفضل:

```text
Suspicious Privileged Login
```

التكاملات:

- Wazuh أو SIEM simulator.
- Asset Inventory.
- Active Directory lab/simulator.
- Internal Case/Ticketing.

الدورة:

```text
Event
→ Normalize
→ Enrich
→ Detect
→ Incident
→ Response Plan
→ Approval
→ Dry-run/Lab Execution
→ Verification
→ Evidence
→ Report
```

بديل مناسب:

```text
Unauthorized Firewall Configuration Change
```

## Phase 3 — Proactive Assurance

- Controls/Assertions.
- Evidence/Baselines.
- Drift.
- Remediation planning/execution.
- Verification/Reports.

## Phase 4 — Hybrid Scoped Assurance

- Event-to-Assertion mapping.
- Scoped execution.
- Live/Backup/Known-Good/Intended comparison.
- Drift proof.
- Triage integration.

## Phase 5 — توسع المجالات

- Vulnerability.
- Network/Endpoint/Identity.
- Cloud.
- AppSec/API/DevSecOps.
- Threat Intel/Hunting.
- Resilience.

## Phase 6 — Advanced Automation and AI

بعد نضج Rules وEvidence وSafety فقط.

---

# 30. معايير القبول

## 30.1 قبول المعمارية

- لا أوامر Product-specific داخل Core.
- Module يسجل دون تعديل Orchestrator.
- Plugin يسجل دون تعديل Domain Module.
- Content جديد دون Python.
- IDs/Versions لكل كيان حرج.
- ملكية التخزين واضحة.
- Security Boundaries موثقة.

## 30.2 قبول أول Vertical Slice

- Ingest حدث حقيقي/محاكى.
- حفظ Raw Event.
- Normalize.
- Resolve Asset/Identity Context.
- نتيجة Rule مفسرة.
- Incident Created/Updated.
- Response Plan.
- Safety Gates.
- Dry-run.
- Lab Execution مع Approval.
- Verification.
- Evidence Package.
- Audit Trail.
- Failure/No-match/Parser-error paths مختبرة.

## 30.3 قبول Production Readiness

- Threat Model reviewed.
- Security tests pass.
- Backup/Restore tested.
- Monitoring complete.
- Plugin health monitored.
- Rate limits.
- Secret handling reviewed.
- Tenant isolation tested إذا وجدت.
- Change management/runbooks.
- DR exercised.
- Signed release.
- Upgrade/Rollback tested.

---

# 31. Anti-Patterns ممنوعة

- Giant Orchestrator.
- Vendor commands داخل Policies.
- Runner منفصل لكل Action Name.
- Web UI يكتب مباشرة في DB.
- Database-only content دون Versioning.
- آلاف Capabilities دون Tests.
- تكرار Integrations بين Proactive/Reactive.
- Silent Pass.
- Exception = Clean Success.
- AI auto-publish/auto-destructive execution.
- Hardcoded organization assumptions.
- Plaintext secrets.
- Backup files وZIPs داخل Source.
- خلط prototypes التاريخية بالكود النشط.
- بناء كل المجالات قبل Vertical Slice.
- Schema evolution غير موثق.
- Plugin بصلاحيات غير محدودة.
- Broad production credentials.
- Action بلا Verification.
- Evidence بلا Provenance.
- تحويل المنصة إلى Training Product أولاً.

---

# 32. دور المشروع القديم والمصادر السابقة

تعامل معها كـ:

```text
Knowledge Mine
+ Prototype History
+ Requirements Source
+ Example Catalog
+ Anti-pattern Repository
```

يستخرج منها:

- Concepts.
- Terminology.
- Capability/Connector examples.
- Proactive/Reactive lessons.
- Safety/Evidence ideas.
- Scenario ideas.
- Parsers/Test data.
- Known weaknesses.

ولا يورث تلقائياً:

- Folder structure.
- Classes.
- Policy language.
- Schemas.
- Mappings.
- Pipelines.
- Database choices.
- Duplicate content.
- Generated capability volume.
- Backward compatibility.

ترتيب مصادر الحقيقة:

```text
1. Approved Future Requirements and ADRs
2. Tested Production Behavior
3. Current Contracts and Schemas
4. New Implementation and Tests
5. Existing Knowledge Sources
6. Old Project Code
7. Historical Drafts
```

---

# 33. مخرجات إلزامية قبل كتابة كود كبير

على المحادثة الجديدة أولاً إنتاج:

1. Source Inventory.
2. Requirements Traceability Matrix.
3. Functional Domain Map.
4. Data Ownership Map.
5. Core/Module/Plugin Decision Matrix.
6. Architecture Alternatives.
7. ADRs.
8. Threat Model.
9. Canonical Data Model.
10. API Boundaries.
11. Plugin SDK Contract.
12. Capability Contract.
13. Storage Strategy.
14. Safety Model.
15. Testing Strategy.
16. First Vertical Slice Specification.
17. Phased Roadmap.
18. Definition of Done.

لا يبدأ توليد آلاف الملفات قبل مراجعة هذه المخرجات.

---

# 34. تعليمات للمحادثة الجديدة

يجب أن تعمل بوصفها:

- Principal Cybersecurity Architect.
- Principal Software Architect.
- Security Platform Engineer.
- Domain-Driven Design Specialist.
- Distributed Systems Engineer.
- DevSecOps Engineer.
- Detection and Response Architect.
- GRC and Assurance Architect.
- Plugin and Integration Architect.
- Product/UX Architect.
- Quality Gate Reviewer.

ويجب أن:

1. تفحص المصادر ذات الصلة قبل القرار.
2. تميز الحقيقة من الافتراض.
3. تكتشف التعارض والقدم والتكرار.
4. تنتقد هذه المعمارية وتحسنها عند وجود سبب هندسي.
5. لا تنسخ المشروع القديم عميانياً.
6. لا تدعي Production Readiness دون Tests.
7. تفضل Vertical Slices صغيرة مكتملة.
8. تبني Security/Evidence من البداية.
9. تحفظ Decision Log.
10. تربط Requirement → Implementation → Test.
11. تمنع Architecture Drift عبر ADR/Contracts.
12. لا تولد ملفات كثيرة لإظهار الاكتمال فقط.
13. تسأل فقط عند وجود قرار يغير الصحة جذرياً.
14. تشرح للمستخدم بالعربية مع المصطلحات التقنية اللازمة.
15. تنتج كوداً قابلاً للتنفيذ والاختبار والصيانة.

---

# 35. قرارات ما زالت مفتوحة

يجب تقييمها لا افتراضها:

- Modular Monolith أم Microservices.
- PostgreSQL Graph أم Graph DB.
- Event Bus.
- Workflow Engine.
- Git Integration.
- UI Framework.
- Policy/Expression Language.
- Plugin Isolation.
- Python Packaging.
- Multi-tenancy.
- Worker/Queue system.
- Search backend.
- Object storage.
- Secrets backend.
- Deployment baseline.
- AI provider/privacy.
- Licensing.
- Product name.

الافتراض الأولي الموصى به:

```text
Modular Monolith
+ PostgreSQL
+ Object Storage Abstraction
+ Queue-backed Workers
+ Git-versioned Content
+ Strict Plugin Contracts
+ API-first Web UI
```

وذلك لتقليل التعقيد المبكر مع إبقاء إمكانية الفصل مستقبلاً.

---

# 36. النموذج التشغيلي النهائي

```text
Core
  يدير العقود والتنسيق والسلامة والأدلة ودورات الحياة.

Modules
  تفهم المجالات الأمنية وتتخذ قرارات المجال.

Plugins
  تتصل بالمنتجات الحقيقية وتطبق Capabilities.

Content
  يخزن Controls/Policies/Detections/Scenarios/Mappings/Templates بإصدارات.

Workflows
  تربط الخطوات القابلة لإعادة الاستخدام.

Scenarios
  تطبق Workflow على حالة واقعية محددة.

PostgreSQL
  يخزن الحالة التشغيلية.

Git
  يحفظ المحتوى Versioned.

Object Storage
  يحفظ الأدلة الكبيرة.

Vault
  يحمي الأسرار.

Web UI
  يوفر التأليف والتشغيل والمراجعة والموافقة.

AI Assistance
  يشرح ويقترح ولا يتحكم بصمت في Production.
```

الدورة التي تثبت نجاح المنصة:

```text
Understand Environment
→ Define What Should Be True
→ Observe What Is True
→ Detect What Happened
→ Explain Why It Matters
→ Decide Safely
→ Execute Under Control
→ Verify Outcome
→ Preserve Evidence
→ Improve Security
```

---

# 37. العبارة النهائية

يجب أن يصبح المشروع منصة أمن سيبراني إنتاجية، طويلة العمر، قابلة للتكيف مع المؤسسات، تجمع المجالات الأمنية الواسعة من خلال نواة صغيرة ثابتة، وModules متخصصة، وPlugins للمنتجات، ومحتوى تصريحي Versioned، وWorkflows محكومة، وأدلة قوية، وتنفيذ آمن.

لا يقاس النجاح بعدد المجلدات أو ملفات YAML أو Frameworks أو Integrations المكتوبة على الورق.

يقاس نجاح كل Use Case بأنه:

```text
Correct
→ Safe
→ Explainable
→ Executable
→ Verifiable
→ Auditable
→ Maintainable
→ Extensible
```

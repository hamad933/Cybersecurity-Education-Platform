---
# yaml-language-server: $schema=schemas\page.schema.json
Object type:
    - Page
Backlinks:
    - Books-Summary = CISSP 8-Domain (References)
Creation date: "2026-03-02T21:28:42Z"
Created by:
    - Perky Sparrow
id: bafyreicxphnz2pzm62ubghagud6g2skub6vwjjtd26ytxnifkqwr6jjaay
---
# domain 7   
# 0.H The daily “triage decision tree” (so you don’t drown)   
    When an alert/drift occurs, classify:   
    ### Class 1 — Tier-0 / safety critical   
    - privileged identity misuse   
    - DC changes   
    - OT boundary violation   
   
    → treat as SEV-high; coordinate immediately   
    ### Class 2 — Baseline integrity drift   
    - local admin change   
    - firewall disabled   
    - Defender exclusions   
   
    → ticket immediately; contain if suspicious   
    ### Class 3 — Noise / benign operational   
    - expected change window events   
   
    → document and close with evidence   
    Elite behavior is knowing which is which fast.   
 --- 
## CISSP Domain 7 — Security Operations (the “keep it true in production” domain)   
Domain 7 is the day-to-day discipline of **operating, monitoring, responding, and recovering** so the security contract you designed (Domain 4) and validated (Domain 6) stays true under real attackers, real outages, and real humans. The current blueprint breaks Domain 7 into **7.1–7.15** (investigations → monitoring → CM → core ops concepts → resource protection → incident management → controls ops → patch/vuln → change → recovery/DR/BC → physical security → personnel safety).   
 --- 
# 7.0 The operating model: “Prevent → Detect → Respond → Recover → Improve”   
Security Operations is a **closed-loop system**:   
1. **Prevent** (hardening, least privilege, segmentation enforcement points, allowlists)   
2. **Detect** (logs, SIEM/UEBA, IDPS, egress monitoring, threat hunting)   
3. **Respond** (contain/eradicate, coordinate comms, preserve evidence)   
4. **Recover** (restore services/data, validate integrity, resume business)   
5. **Improve** (lessons learned, tuning, baselines, training, contracts updated)   
   
Your job is to make the loop **measurable and repeatable**:   
- everything important produces telemetry,   
- every incident produces a postmortem,   
- every fix is verified and regression-proofed.   
 --- 
   
# 7.1 Understand and comply with investigations   
This is the “don’t destroy evidence, don’t break the law, don’t lose the case” component.   
## What you must operationally know   
### A) Types of investigations you’ll support   
- **Administrative/HR** (policy violations, insider threat, acceptable use)   
- **Civil/regulatory** (contract, privacy, breach notification, compliance)   
- **Criminal/law enforcement** (fraud, intrusion, extortion)   
- **Operational troubleshooting** (root-cause analysis that still requires forensic discipline)   
   
### B) Evidence concepts that are always tested   
- **Chain of custody:** who had the evidence, when, where stored, how sealed, every handoff documented.   
- **Integrity:** cryptographic hashes for images/log bundles; tamper-evident storage.   
- **Order of volatility:** collect most volatile first (memory/network state) before powering down or imaging disks.   
- **Minimal handling:** do not “peek” in ways that alter timestamps or content.   
   
### C) Forensics lifecycle (operations-grade)   
1. **Collection** (volatile + non-volatile)   
2. **Examination** (extract artifacts)   
3. **Analysis** (timeline + hypothesis testing)   
4. **Reporting** (what happened, how you know, limitations)   
   
A practical baseline for integrating forensics into incident response is the NIST forensics guide.   
## What “artifacts” means in real environments   
The blueprint explicitly expects artifacts from **data, computers, networks, and mobile devices**.   
Translate that into collections you must be able to perform safely:   
- **Endpoints/servers:** memory capture (when justified), disk images, event logs, EDR telemetry, persistence points   
- **Network:** PCAP/NetFlow, firewall logs, DNS logs, proxy logs, VPN logs   
- **Identity:** authentication logs, privileged actions, group/role changes, token issuance   
- **Cloud/SaaS:** control-plane audit logs, IAM policy history, object access logs   
- **Mobile:** MDM telemetry, device logs, backups (with legal approval), app traces   
   
## Mistakes that ruin investigations   
- Running “cleanup tools” before evidence capture   
- Logging gaps (no time sync, short retention, missing sources)   
- Mixing investigative notes with opinions; not separating facts from inference   
- Uncontrolled report distribution (leaks sensitive exploit paths)   
 --- 
   
# 7.2 Conduct logging and monitoring activities   
This is where SOCs either become **signal engines** or **noise factories**. The blueprint expects: **IDPS, SIEM, continuous monitoring/tuning, egress monitoring, log management, threat intelligence/hunting, UEBA**.   
## A) Logging architecture (what “log management” really is)   
A real log pipeline has stages; each stage must be designed:   
1. **Generate** (systems produce events you actually need)   
2. **Collect** (agents/forwarders/syslog/API pulls)   
3. **Normalize** (time, identity, host, event taxonomy)   
4. **Enrich** (asset criticality, user context, geo, threat intel)   
5. **Store** (hot/warm/cold tiers, retention, immutability)   
6. **Detect** (correlation, behavioral analytics, rules)   
7. **Respond** (tickets, playbooks, automation)   
8. **Prove** (auditability: the evidence survives scrutiny)   
   
A widely used baseline for enterprise log management practices is NIST’s log management guide.   
## B) IDPS + SIEM + UEBA: what each is “for”   
- **IDPS:** protocol/traffic-based detection + prevention (best at known patterns and policy violations; can block).   
- **SIEM:** collection + correlation + compliance reporting (best at “this set of events across sources means something”).   
- **UEBA:** behavior baselining for users/entities (best at “this is abnormal for this identity/host/service”).   
   
**Key engineering principle:** every detection must have:   
- a *data dependency list* (which logs are required),   
- a *false-positive plan* (filters/enrichment),   
- an *action plan* (what happens when it fires).   
   
## C) Egress monitoring (the “assume breach” control)   
Egress is where you catch:   
- C2 beacons   
- data exfiltration   
- anomalous DNS/HTTP patterns   
- unauthorized cloud uploads   
   
Operationally, you want **deny-by-default egress for high-value tiers** (servers/identity systems), and identity-aware proxying for user tiers.   
## D) Continuous monitoring and tuning (the only way monitoring stays real)   
Monitoring decays when:   
- systems change faster than detection content,   
- new apps appear without onboarding,   
- threat actors shift tactics,   
- teams ignore alerts.   
   
So you maintain:   
- **coverage maps** (what is logged where),   
- **detection SLAs** (triage time, containment time),   
- **rule health** (precision/recall, drift, stale rules).   
 --- 
   
# 7.3 Perform configuration management (CM): provisioning, baselining, automation   
This is “make secure state reproducible and drift-detectable.”   
## A) Provisioning and baselines (the “golden build” idea)   
- **Secure build standards** (OS, network devices, cloud templates)   
- **Baseline configurations** (what *must* be true)   
- **Hardened images/templates** (rebuild > repair for many compromises)   
- **Drift detection** (detect unauthorized change quickly)   
   
## B) Automation as a control   
- Infrastructure-as-Code and policy-as-code aren’t “DevOps extras” — they are **auditability**:   
    - change diffs are reviewable,   
    - rollbacks are possible,   
    - drift can be detected objectively.   
   
## C) Trusted recovery (ops + assurance)   
Operations must be able to restore a system to a trusted state:   
- known-good images   
- verified configs   
- validated patch level   
- confirmed logging and identity bindings re-established   
 --- 
   
# 7.4 Apply foundational security operations concepts   
Blueprint items: **need-to-know/least privilege, SoD/responsibilities, privileged account management, job rotation, SLAs.**   
## A) Least privilege in operations (where it often fails)   
- Operations accounts drift into “always-admin”   
- Shared admin credentials eliminate attribution   
- Service accounts become unowned and over-privileged   
   
Your operational countermeasures:   
- **PAM** (vaulting, check-out, session recording, JIT elevation)   
- **special privilege monitoring** (alerts on privilege grants + sensitive admin actions)   
- **separate admin planes** (workstations/jump hosts, restricted network paths)   
   
## B) Separation of duties (SoD) and anti-collusion controls   
SoD isn’t abstract — it’s about preventing a single actor from:   
- requesting + approving + implementing + validating the same sensitive change,   
- deploying code and altering logs without oversight,   
- issuing privileged access and covering tracks.   
   
Operational patterns:   
- dual approval for high-impact changes   
- break-glass accounts with high-friction monitoring   
- job rotation/mandatory vacation to surface fraud/abuse   
   
## C) SLAs as security levers   
SLAs aren’t only “uptime”:   
- incident response SLAs (MTTD/MTTR)   
- patch SLAs by severity/tier   
- log retention and ingestion SLAs   
- backup/restore SLAs (RPO/RTO)   
 --- 
   
# 7.5 Apply resource protection   
Blueprint items: **media management, media protection techniques, data at rest/data in transit.**   
## A) Media management (end-to-end lifecycle)   
- inventory and ownership   
- labeling/classification handling rules   
- storage controls (locked, tracked)   
- transport controls (sealed, logged)   
- sanitization/destruction (proof of destruction)   
   
## B) Data at rest / in transit (operations view)   
- at rest: encryption + key management + access control + monitoring of access   
- in transit: TLS/VPN/mTLS + monitoring that still preserves provability (metadata/flows)   
   
## C) DLP as an operational control   
DLP isn’t “one tool” — it’s:   
- data classification + labels,   
- detection at endpoints/network/cloud,   
- response workflows (block/quarantine/notify/escalate),   
- exception handling for business needs.   
 --- 
   
# 7.6 Conduct incident management   
Blueprint phases: **detection, response, mitigation, reporting, recovery, remediation, lessons learned.**   
A canonical incident handling lifecycle widely used in enterprises is the NIST incident handling guide.   
## A) Incident process as a control system   
**Detection → Triage → Containment → Eradication → Recovery → Post-incident improvement**   
Operationally, the key is deciding *containment strategy*:   
- isolate host vs isolate segment vs disable identity vs block egress vs revoke tokens   
   
…and doing it fast without destroying evidence.   
## B) Reporting (who must know what, when)   
- internal: SOC → IR lead → system owners → leadership   
- external: legal/privacy → regulators → customers → law enforcement (when appropriate)   
   
## C) Lessons learned is part of “closure”   
A real closure includes:   
- root cause   
- control gap (why prevention/detection failed)   
- changes to baselines/detections/runbooks   
- re-test proof (Domain 6 loop)   
 --- 
   
# 7.7 Operate and maintain detection and preventative measures   
Blueprint expects operating and maintaining: **firewalls, IDS/IPS, allow/deny lists, third-party security services, sandboxing, honeypots/honeynets, anti-malware, ML/AI-based tools.**   
**Ops-grade focus:** these controls are not “installed”; they are **maintained**:   
- rulesets age and need pruning   
- signatures need updates and tuning   
- coverage changes with network/app changes   
- attackers force you to adjust thresholds and enrichments   
 --- 
   
# 7.8 Implement and support patch and vulnerability management   
This is the operational pipeline: **identify → acquire → test → deploy → verify → report → improve**.   
A standard enterprise baseline for patch management concepts and challenges is NIST’s patch management guidance.   
Core mechanics you must operationalize:   
- tier-based patch SLAs (crown jewels vs low-risk)   
- emergency patch paths with follow-up governance   
- verification (don’t trust “installed”; validate effective version/state)   
- vulnerability intake sources (vendor advisories, threat intel, scanning)   
 --- 
   
# 7.9 Understand and participate in change management processes   
Change management is **security operations’ anti-chaos system**.   
Non-negotiables:   
- request → impact analysis → approval → implementation → validation → rollback readiness   
- separation of duties on high-risk changes   
- audit trail of what changed, who approved, and what testing proved it   
 --- 
   
# 7.10–7.13 Recovery, DR, and BC (availability is a security objective)   
Blueprint expects:   
- **recovery strategies** (backup storage, recovery sites, multiple processing sites, HA/QoS/fault tolerance)   
- **DR processes** (response, personnel, comms, assessment, restoration, training, lessons learned)   
- **DRP testing** (tabletop/read-through, walkthrough, simulation, parallel, full interruption; stakeholder comms)   
- **BC planning and exercises**   
   
Operationally, you must be able to answer:   
- What is our **RPO** and **RTO** per service?   
- Where are the dependencies (identity, DNS, network, cloud control plane)?   
- Can we restore *and prove integrity* (not just power-on)?   
   
**Key operational metrics:**   
- restore success rate (not backup success rate)   
- time-to-restore vs RTO   
- data loss window vs RPO   
- DR findings closure time   
 --- 
   
# 7.14 Implement and manage physical security   
Blueprint expects perimeter and internal controls.   
Operations-grade physical security includes:   
- facility access control + visitor management   
- monitoring (CCTV, alarms) + auditing physical access logs   
- internal controls for sensitive areas (server rooms, IDF/MDF, labs)   
- patrol/guards procedures where used (people are part of the control system)   
 --- 
   
# 7.15 Address personnel safety and security concerns   
Blueprint items include: **travel, security training/awareness (insider threat, social media impacts, 2FA fatigue), emergency management, duress.**   
Operationally this becomes:   
- travel risk processes (devices, border search posture, comms plans)   
- duress procedures (panic codes, emergency contacts, escalation)   
- insider threat awareness integrated into operations (not just HR)   
- 2FA fatigue and social engineering defenses (process + technical guardrails)   
 --- 
   
## Domain 7 “Ops artifacts index” (what you should have in a real enterprise)   
- Logging architecture + source coverage map + retention policy + detection catalog   
- Incident Response plan + playbooks + severity model + comms matrix + evidence handling SOP   
- Forensic readiness kit (tools, legal/HR hooks, chain-of-custody templates)   
- Configuration baselines + golden images + drift detection + CMDB/asset inventory   
- Patch/vuln management program (SLAs, change windows, verification, reporting)   
- Backup/restore program (restore testing schedule, RPO/RTO by service)   
- DR/BC plans + exercise calendar + after-action closure tracker   
- Physical security procedures + access logs review cadence   
- Personnel safety program for travel/duress + awareness program tied to incidents   
 --- 
   
If you want, say **“Domain 7 deep dive 7.1 + 7.2”** and I’ll go extremely technical on (a) evidence handling/forensics workflows and (b) logging/SIEM/UEBA architecture + tuning + egress monitoring, with concrete enterprise examples and verification steps.   
cover all 4 books also you can write the result in 4-5 chunks(messages) for best result   
Thought for 7m 46s   
## Domain 7 — Chunk 1/5   
### Security Operations master mental model + 7.1 Investigations (forensics-ready operations)   
Domain 7 is the “**keep security true in production**” domain. If Domain 4 defines the *security contract* (zones, conduits, least connectivity), and Domain 6 proves controls work *in tests*, Domain 7 is the reality engine: **operate, monitor, investigate, respond, recover, and continuously improve**—without breaking evidence, business, or compliance.   
### The Domain-7 control objective (operations-grade)   
**Every security-relevant event should be:**   
1. **Detectable** (telemetry exists),   
2. **Attributable** (who/what/where),   
3. **Containable** (you can stop spread),   
4. **Recoverable** (you can restore service/data),   
5. **Provable** (evidence survives scrutiny).   
 --- 
   
# 1) Security Operations ≠ “SOC only” (it’s two domains in one)   
Domain 7 blends:   
- **Operations security (OpsSec)**: protect information processing assets (people/process/tech + physical + media).   
- **Security operations**: daily tasks that keep security services reliable (logging, monitoring, vuln/patch, change, IR, recovery).   
   
If you treat it as “SOC stuff,” you’ll miss the biggest parts: **configuration/change discipline, backups/DR/BC, physical security, and investigations**.   
 --- 
# 2) 7.1 Investigations — the “don’t destroy the case” discipline   
## 2.1 Investigation types (why “type” changes everything)   
You must be able to support multiple investigation classes because each changes:   
- **standard of proof**,   
- **what you’re allowed to do**,   
- **who owns decisions**,   
- **how evidence must be handled**,   
- **who gets notified**.   
   
### A) Criminal   
- Goal: prove a crime “beyond reasonable doubt” (strictest handling).   
- Common pitfalls: evidence contamination, broken chain of custody, unauthorized access to evidence.   
   
### B) Civil   
- Goal: resolve disputes / damages / contractual issues (less strict than criminal, still disciplined).   
- Pitfalls: scope creep, privacy exposure, weak documentation.   
   
### C) Regulatory / compliance   
- Goal: show adherence to required controls and reporting obligations.   
- Pitfalls: inconsistent logs/retention, missing audit trails, uncontrolled report distribution.   
   
### D) Operational (internal / troubleshooting)   
- Goal: restore service + determine root cause.   
- Pitfalls: “fix-first” destroys volatile evidence and timeline truth.   
   
**Ops rule:** treat *every incident* as potentially criminal **until proven otherwise**, because what looks like “hardware failure” can be sabotage, and what looks like “misconfig” can be attacker action.   
 --- 
## 2.2 The evidence model (what courts and auditors actually care about)   
### The 5 evidence properties (you must preserve these)   
A practical set used across digital investigation practice is:   
1. **Admissible** (lawfully obtained, within authority)   
2. **Authentic** (it is what you claim it is)   
3. **Complete** (tells the whole story, not cherry-picked)   
4. **Reliable** (collection method doesn’t distort it)   
5. **Believable** (a competent third party can understand and trust it)   
   
If you can’t defend these five, your findings become “interesting” but not actionable in legal/regulatory contexts.   
### Types of evidence (what you may present)   
- **Real/object evidence:** physical devices/media (servers, drives, phones).   
- **Documentary evidence:** logs, emails, tickets, screenshots, reports.   
- **Testimonial evidence:** statements from witnesses/analysts (must be consistent with artifacts).   
 --- 
   
## 2.3 Chain of custody (the single easiest way to lose a case)   
Chain of custody is not “a form.” It is **continuous accountability** for:   
- what was collected,   
- where it came from,   
- who handled it,   
- when and why it moved,   
- how it was stored,   
- how integrity was preserved.   
   
**Minimum chain-of-custody fields you must capture**   
- Evidence ID (unique), description, serial numbers (if hardware)   
- Collector identity, date/time (with timezone), exact location   
- Collection method (e.g., “memory capture,” “disk image,” “log export”)   
- Hashes (where applicable), sealing method   
- Every transfer: from→to, time, purpose, storage location   
   
**Ops failure mode:** letting admins “help” by copying evidence around informally. That breaks the chain immediately.   
 --- 
## 2.4 Order of volatility (the forensic priority rule)   
Volatile data disappears first. Typical volatility priority:   
1. **CPU/registers/cache** (practically hardest to preserve)   
2. **RAM** (process memory, injected code, decrypted material)   
3. **Network state** (connections, ARP tables, NAT states, VPN sessions)   
4. **Running processes & system state** (open files, logged-on users)   
5. **Disk** (file system, logs, persistence artifacts)   
6. **Backups/archives** (slower, persistent)   
   
**Operational translation:** your IR policy must define what you do first:   
- isolate network?   
- capture RAM first?   
- pull disk image?   
   
Because the wrong first move can destroy the only proof.   
 --- 
## 2.5 Live response vs dead-box acquisition (the decision that matters)   
### Live response (system running)   
**Pros:** captures volatile artifacts (RAM, active sessions, malware in memory).   
**Cons:** higher contamination risk; actions can alter evidence.   
### Dead-box (powered down, then image)   
**Pros:** cleaner acquisition of disk state, less chance of accidental change.   
**Cons:** you lose volatile evidence (RAM-resident malware, decrypted secrets, network state).   
**Domain-7-ready policy requirement:** your incident response policy must explicitly say when you are allowed to:   
- pull the plug,   
- isolate but keep running,   
- remove from network but keep powered,   
- keep online for business continuity.   
   
(High-value environments often choose: **isolate from network, keep powered, capture volatile, then image**—but your policy must be explicit.)   
 --- 
## 2.6 Digital investigation lifecycle (how investigations actually run)   
A defensible investigation is a pipeline:   
1. **Preparation / readiness**   
    - trained personnel, tooling, templates, legal/HR contacts   
    - evidence storage + access control pre-built   
2. **Identification**   
    - what happened, what systems involved, what evidence containers exist   
3. **Collection / acquisition**   
    - volatile then persistent; repeatable methods; minimal handling   
4. **Examination**   
    - parse and extract artifacts (logs, file metadata, registry/config, memory artifacts)   
5. **Analysis**   
    - timeline reconstruction; hypothesis testing; correlation across sources   
6. **Reporting**   
    - facts vs inferences separated; limitations stated; evidence indexed   
 --- 
   
# 3) Forensic readiness (what makes Domain 7 operationally “mature”)   
## 3.1 The investigation-ready enterprise kit (minimum complete set)   
- **IR policy** that resolves the “availability vs evidence” conflict up front   
- **Forensic contact roster** (internal + external specialists)   
- **Evidence handling SOP** (how to collect, label, store, transfer)   
- **Standard evidence bundles** by system class:   
    - endpoints/servers: event logs, EDR snapshots, memory capture policy, disk acquisition path   
    - network: firewall logs, DNS logs, proxy logs, VPN logs, flow/PCAP retention rules   
    - identity: authentication logs, privileged actions, group/role change logs   
    - cloud/SaaS: audit logs, IAM policy change history, object access logs   
- **Report templates** (technical + executive) with strict access control   
- **Training + drills** (investigation is a practiced skill, not a document)   
   
## 3.2 Investigator “rules of thumb” (how professionals avoid ruining evidence)   
A disciplined methodology looks like:   
- minimize handling and corruption of original data   
- document every action and every change   
- don’t exceed your knowledge/authority   
- get written permission and follow policy   
- proceed from volatile to persistent   
- ensure actions are repeatable   
- be prepared to testify (i.e., defend your method and conclusions)   
 --- 
   
# 4) What CISSP expects you to be able to do with this (exam + real ops)   
### You must be able to explain:   
- how investigation type changes evidence handling and notifications   
- why chain of custody matters and what it contains   
- what “order of volatility” means operationally   
- live vs dead-box tradeoffs and how policy decides   
- how to produce a defensible report: facts, evidence index, scope limits, conclusions   
   
### Common exam traps   
- “Take system offline” is **not always correct**; policy + criticality decides.   
- “Collect disk first” is wrong if you needed volatile evidence.   
- “Logs are enough” is wrong if logs are incomplete or not integrity-protected.   
- “Forensics is a tool” is wrong; it’s **a repeatable process**.   
 --- 
   
## Micro cheat sheet (Chunk 1)   
- **Investigations = evidence discipline + scope + authority.**   
- **Chain of custody** is continuous accountability; break it once and the case collapses.   
- **Volatile-first** is the default principle; policy decides exact first actions.   
- **Live response** captures volatile truth but risks contamination; **dead-box** is cleaner but loses volatile truth.   
- Treat incidents as potentially criminal until proven otherwise.   
 --- 
   
Say **“next”** and I’ll deliver **Chunk 2/5: 7.2 Logging & Monitoring** (SIEM/UEBA/IDPS, continuous monitoring & tuning, egress monitoring, log integrity/retention, and how to build “provable” detection coverage that survives audits and real incidents).   
next   
Thought for 19s   
## Domain 7 — Chunk 2/5   
### 7.2 Conduct logging & monitoring activities (SIEM/UEBA/IDPS, tuning, egress, “provable detection”)   
Domain 7.2 is where security becomes **observable engineering**. If you can’t *see* reality, you can’t defend it, investigate it, or prove it.   
### 7.2 control objective   
**Every high-value event must be:**   
- **captured** (source emits it),   
- **transported** (reliably reaches your collectors),   
- **normalized** (time + identity + asset context),   
- **retained** (for investigations/compliance),   
- **actionable** (detections + response playbooks),   
- **provable** (tamper-evident enough to survive audit/investigation).   
 --- 
   
# 1) The logging architecture (the full pipeline you must design)   
Think of monitoring as an end-to-end system, not “enable logs.”   
## 1.1 Generate (what must emit events)   
You need consistent coverage across **five telemetry planes**:   
### A) Identity plane (highest value)   
- authentication success/failure   
- MFA events (push fatigue patterns, bypass/step-up)   
- privilege grants (group/role changes, JIT elevations)   
- token issuance / session creation / key usage   
   
### B) Endpoint/server plane   
- process creation / service install / scheduled tasks / drivers   
- local admin group membership changes   
- security policy changes (firewall, audit policy, AV/EDR state)   
- file integrity signals on sensitive paths   
   
### C) Network/security device plane   
- firewall allow/deny sessions (with rule ID)   
- VPN connects/disconnects   
- DNS queries/responses (resolver logs)   
- proxy logs (URL/category/user identity)   
- flow telemetry (NetFlow/IPFIX/VPC flow logs)   
   
### D) Application plane (where business impact is proven)   
- auth logs (app layer)   
- admin actions   
- data access (who accessed what record/object)   
- API gateway events (auth, rate limit, errors)   
   
### E) Cloud/SaaS control plane   
- IAM policy/role changes   
- object storage access   
- key management events   
- security group / route table changes   
   
**Rule:** If you can’t correlate *identity + endpoint + network + app*, attribution collapses.   
 --- 
## 1.2 Collect (how events leave the source safely)   
Common collection modes:   
- **Agent-based** (reliable, rich, but must be managed)   
- **Agentless pull** (API polling from cloud/SaaS)   
- **Forwarding** (Windows event forwarding / syslog relays)   
- **Mirroring/sensors** (network TAP/SPAN for IDPS)   
   
**Operational requirement:** collection must be **loss-visible**. You need counters/health signals:   
- events generated vs events received   
- parser success rate   
- queue backlog   
- drop rate   
- collector saturation   
   
If you can’t measure loss, you can’t trust detections.   
 --- 
## 1.3 Normalize (the step most SOCs underfund)   
Normalization is where you turn raw logs into “security language”:   
- **time normalization** (UTC, consistent format)   
- **host identity normalization** (hostname ↔ asset ID ↔ cloud instance ID)   
- **user identity normalization** (UPN ↔ SID ↔ email ↔ SSO subject)   
- **event taxonomy** (auth, admin, network session, process, data access)   
- **field standardization** (src/dst IP, ports, user, device, outcome)   
   
This is what makes correlation possible.   
 --- 
## 1.4 Enrich (turn events into decisions)   
Enrichment adds context that makes alerts meaningful:   
- asset criticality tier   
- data classification of target system   
- owner team + on-call mapping   
- geo/IP reputation category (internal vs external)   
- known approved admin hosts (PAWs/jump boxes)   
- change window context (was there an approved change?)   
   
**Most false positives are actually missing enrichment.**   
 --- 
## 1.5 Store/retain (hot/warm/cold + integrity)   
Storage design must support:   
- **hot search** (recent incidents)   
- **warm analytics** (trend + hunting)   
- **cold retention** (compliance/investigation windows)   
   
Integrity requirements (investigation-ready):   
- access controls (least privilege for log access)   
- immutable retention for critical logs (or equivalent controls)   
- evidence bundling with hashes for incident cases   
 --- 
   
# 2) SIEM: correlation engine + compliance engine (not a magic box)   
## 2.1 SIEM is a detection production line   
A mature SIEM program treats each detection as a maintained product:   
**Detection spec must include:**   
- objective (what threat/control failure it detects)   
- required data sources (dependency list)   
- logic (rule/threshold/correlation)   
- expected false positives and filters   
- severity mapping (business impact aware)   
- response playbook link   
- test cases (how to validate it fires correctly)   
- owner and review cadence   
   
If you don’t version and review detections, your SIEM decays silently.   
## 2.2 Detection types in SIEM (you must use all three)   
1. **Signature/indicator-based**   
   
Fast, brittle, good for known bad (hashes, domains, IPs).   
1. **Behavioral/correlation-based**   
   
“Sequence-of-events” detections:   
- impossible travel patterns   
- privilege grant → new admin actions → egress spike   
- auth failures → success → sensitive access   
1. **Policy/contract violations**   
   
Your Domain-4 contract becomes detections:   
- “Guest network reached internal service” (should never happen)   
- “User subnet accessed management plane” (should never happen)   
- “New inter-zone allow rule created” (high-risk change)   
   
**Best practice:** make “contract violations” your highest-confidence, lowest-noise alerts.   
 --- 
# 3) IDPS (IDS/IPS): where to place it and what it’s good for   
## 3.1 NIDS vs HIDS (role clarity)   
- **Network IDS/IPS:** sees traffic patterns; great for protocol anomalies, known exploit signatures, scanning, policy violations.   
- **Host-based signals:** see process-level truth; better for endpoint compromise and persistence.   
   
## 3.2 Inline IPS vs passive IDS (risk tradeoff)   
- **Inline IPS** can block but can also break traffic if mis-tuned.   
- **Passive IDS** won’t break traffic but requires fast response workflows.   
   
**Architecture rule:** put “block” controls where you can tolerate false positives (e.g., egress filtering, known-bad indicators), and use passive detection where availability is critical.   
## 3.3 Placement (the “choke point” mistake)   
Classic failure: only monitoring the perimeter. Modern breaches live in:   
- east-west (internal lateral movement)   
- identity plane   
- cloud control plane   
   
So you want visibility at:   
- internet edge   
- **inter-zone boundaries** (internal segmentation points)   
- egress chokepoints (proxy/DNS/firewall)   
- critical server segments   
- identity services   
 --- 
   
# 4) UEBA: powerful, easy to misuse   
UEBA works when:   
- identities are cleanly unified (SSO ↔ directory ↔ app accounts)   
- baselines have enough stable history   
- signals include *context* (role, department, normal working hours, admin hosts)   
   
UEBA fails when:   
- identity mapping is fragmented   
- org is in high-change mode (model drift)   
- you treat UEBA “risk scores” as truth without investigation   
   
**Operational rule:** UEBA is best used to prioritize hunting, not to replace deterministic detections.   
 --- 
# 5) Threat intelligence + threat hunting (how to use them without becoming noisy)   
## 5.1 Threat intelligence   
Two useful buckets:   
- **IOCs** (domains, IPs, hashes) → short-lived, high churn   
- **TTPs** (tactics/techniques) → longer-lived, better for behavior detections   
   
**Use IOCs primarily at egress and email**, and use TTPs to build correlation detections.   
## 5.2 Threat hunting   
Hunting is structured exploration:   
- hypothesis (“we might have credential misuse in admin accounts”)   
- data sources required   
- query plan (broad → narrow)   
- validation steps (case creation, evidence pack)   
- tuning output (new detection or improved logging)   
   
Hunting without a hypothesis becomes “random searching.”   
 --- 
# 6) Egress monitoring (the “assume breach” control that actually catches real intrusions)   
Egress is where you see:   
- command-and-control beacons   
- credential exfil   
- data staging to cloud storage   
- unusual DNS tunneling patterns   
   
## 6.1 Egress control stack (from strongest to weakest)   
1. **Default-deny egress** for crown jewels (servers/identity tiers)   
2. **Proxy-based egress** with identity logging for user tiers   
3. **DNS controls** (known resolvers only; log all queries)   
4. **Firewall egress policy** (allowed destinations/ports)   
5. **Flow telemetry** (prove what left, when, how much)   
   
## 6.2 High-signal egress anomalies (low-noise patterns)   
- new domains contacted by privileged servers   
- unusual DNS query volume/spikes by a single host   
- first-time country/ASN destinations for sensitive workloads   
- large outbound transfers outside normal windows   
- repeated small periodic outbound patterns (beacon-like)   
 --- 
   
# 7) Continuous monitoring and tuning (the difference between “installed” and “operated”)   
Monitoring quality decays unless you run a tuning loop.   
## 7.1 The tuning loop (weekly operational cadence)   
1. review top alerts by volume (reduce noise)   
2. review top alerts by severity (ensure response works)   
3. check data quality SLOs (drops, parsing failures, missing sources)   
4. update detection content based on incidents/hunts   
5. validate with test cases (did the detection still fire?)   
   
## 7.2 Monitoring health metrics (what mature SOCs measure)   
- **MTTD / MTTR** (time to detect/respond)   
- alert precision (false positive rate proxy)   
- alert coverage (which tactics/control failures are detectable)   
- log source coverage % (by tier)   
- ingestion delay and drop rate   
- parser failure rate (unknown fields / broken formats)   
   
**Rule:** if ingestion is unreliable, your SIEM is a compliance collector, not a security control.   
 --- 
# 8) “Provable detection coverage” (Domain 7 meets Domain 6)   
To be audit- and investigation-ready, you must be able to show:   
- which high-value controls are monitored   
- which logs support each detection (dependency list)   
- retention and integrity posture   
- evidence that alerts were tested (synthetic events / controlled drills)   
- improvement history (rule tuning, reduced false positives, new coverage)   
   
This is how you answer the hardest question:   
> “How do you know you would detect it?”   

 --- 
## Micro cheat sheet (Chunk 2)   
- Logging is a pipeline: **generate → collect → normalize → enrich → store → detect → respond → prove**.   
- SIEM detections are products: they need owners, dependencies, test cases, and tuning.   
- IDPS placement must include **internal boundaries** and **egress**, not only the perimeter.   
- UEBA is best for prioritization and hunting, not blind automation.   
- Egress monitoring is where modern intrusions reveal themselves.   
- “Provable coverage” = detection logic + data dependencies + retention/integrity + test evidence.   
 --- 
   
Say **“next”** for **Chunk 3/5: 7.3 Configuration management + 7.4 foundational ops concepts** (baselines, drift, automation, privileged account management, SoD, job rotation, SLA-driven security operations).   
Next   
## Domain 7 — Chunk 3/5   
### 7.3 Configuration management + 7.4 Foundational ops concepts (CM, baselines, drift, PAM, SoD, job rotation, SLAs)   
This chunk is the “**make secure state reproducible**” part of Domain 7. If 7.2 is “observe reality,” 7.3–7.4 are “**control reality**”: you can’t monitor your way out of bad configuration and uncontrolled privilege.   
 --- 
# 7.3 Perform configuration management (CM)   
### 7.3 control objective   
**Every production asset has a defined secure baseline, is provisioned from controlled sources, and deviations are detected and corrected quickly—without relying on hero admins.**   
If you can’t reproduce a secure configuration, you can’t guarantee security at scale.   
 --- 
## 1) The CM lifecycle (the operational chain you must run)   
A complete CM program has a loop:   
1. **Standardize** (define secure configuration standards)   
2. **Provision** (deploy from controlled images/templates)   
3. **Harden** (apply baseline controls, remove unnecessary services)   
4. **Validate** (confirm baseline applied; compliance checks)   
5. **Monitor drift** (detect deviations continuously)   
6. **Correct drift** (re-apply baseline or remediate changes)   
7. **Govern change** (approved, logged, tested changes only)   
8. **Retire** (decommission with secure wipe and inventory updates)   
   
The failure mode you’re preventing: “security by initial build,” where a system is secure on day 1 and unsafe by day 90 due to ad-hoc changes.   
 --- 
## 2) Secure baselines (what a baseline must include)   
A baseline is not “a hardening checklist.” It’s a machine-verifiable contract for system state.   
### 2.1 Baseline categories (minimum set)   
- **Identity baseline**: authentication methods, MFA requirements, service account rules   
- **Privilege baseline**: local admins, sudoers, role assignments   
- **Network baseline**: listening ports, firewall rules, allowed egress, management plane restrictions   
- **Crypto baseline**: allowed TLS versions/ciphers, SSH settings, key sizes, certificate constraints   
- **Logging baseline**: what is logged, where shipped, retention, time sync requirements   
- **Update baseline**: patch sources, update cadence, rollback plan   
- **Integrity baseline**: file integrity monitoring scope, signed binaries where relevant   
- **Backup baseline**: backup agent presence, job schedule, restore test requirements   
   
### 2.2 Baseline “truth source”   
Mature programs define the baseline as:   
- **code** (templates, IaC, policy-as-code), not PDFs,   
- versioned with approvals and reviews,   
- diffable and auditable.   
   
This converts security into “change you can prove.”   
 --- 
## 3) Provisioning: golden images, templates, and controlled supply chain   
Provisioning is where you prevent drift before it exists.   
### 3.1 Golden images / hardened templates   
- OS images with hardened defaults   
- Cloud images (golden AMIs/images)   
- Network device baseline configs (golden configs)   
- Container base images with minimal packages and pinned versions   
   
### 3.2 Supply chain controls (the hidden CM requirement)   
- only approved repositories for packages   
- signed updates where possible   
- restricted admin ability to add arbitrary repos   
- artifact integrity checks in CI/CD   
   
**Common failure mode:** “CM is strong” but developers pull arbitrary dependencies or base images.   
 --- 
## 4) Drift detection: the part that makes CM real   
A baseline without drift detection is a one-time wish.   
### 4.1 Drift types you must detect   
- **Config drift**: setting changed (firewall disabled, audit policy lowered)   
- **Exposure drift**: new service/port opened, new route created   
- **Privilege drift**: new local admin, new role binding, new group membership   
- **Identity drift**: MFA requirement removed, auth method changed   
- **Logging drift**: agent disabled, log forwarding stopped, retention reduced   
   
### 4.2 Drift response models   
Two operational models:   
- **Detect → ticket → fix** (human-in-the-loop)   
- **Detect → auto-remediate** (safe for well-understood settings)   
   
A mature hybrid approach:   
- auto-remediate safe controls (log agent enabled, firewall on, time sync)   
- ticket for high-risk changes requiring context (service ports, routing, crypto policy)   
 --- 
   
## 5) Configuration change discipline (CM meets change management)   
CM must integrate with change management so changes don’t become “invisible security incidents.”   
Minimum CM change properties:   
- request + justification   
- impact analysis (security + availability)   
- approval (right authority)   
- implementation plan + rollback plan   
- validation plan (tests + monitoring)   
- evidence of completion (diffs, logs, screenshots)   
   
**Key rule:** production change without evidence is indistinguishable from attacker action.   
 --- 
## 6) Trusted recovery: rebuild beats repair (when compromise is plausible)   
For many compromises, the safest path is:   
- isolate   
- preserve evidence as needed   
- **rebuild from golden baseline**   
- restore data from known-good backups   
- validate logging + identity bindings re-established   
- run post-rebuild validation tests   
   
This is “operations-grade containment”: you’re removing unknown attacker persistence.   
 --- 
# 7.4 Apply foundational security operations concepts   
### 7.4 control objective   
**Operations must be governed so no single person or single process failure can silently create catastrophic risk.**   
This is where CISSP leans heavily into management + governance mechanics that prevent insider abuse and reduce operational error.   
 --- 
## 1) Need-to-know & least privilege (in operations reality)   
Least privilege isn’t theoretical. It is:   
- only the permissions needed   
- only for the time needed   
- only from the systems allowed (admin workstations/jump hosts)   
- with accountability (logged actions)   
   
### Operational anti-patterns   
- “everyone in ops is local admin everywhere”   
- shared admin passwords   
- long-lived “temporary” access   
- service accounts used by humans   
   
### The operational countermeasure stack   
- **PAM** (vaulting + approval + JIT + session recording)   
- admin roles separated by function   
- admin access limited to hardened admin endpoints   
- continuous review of privileged grants   
 --- 
   
## 2) Separation of duties (SoD): preventing single-actor control of the truth   
SoD is about preventing a single person from being able to:   
- request a change,   
- approve the change,   
- implement the change,   
- modify logs to hide the change,   
- and sign off that it worked.   
   
### Practical SoD patterns   
- change approver is not implementer for high-risk systems   
- security review required for network boundary changes   
- logs shipped to a system admins can’t edit   
- break-glass accounts exist but are heavily monitored   
 --- 
   
## 3) Job rotation and mandatory vacation (why it’s in security operations)   
These are anti-collusion and fraud discovery controls:   
- long-running insider abuse often depends on routine and secrecy   
- rotation forces others to operate the system and notice anomalies   
   
Operationally:   
- rotate key duties (backup admin, firewall admin, IAM admin)   
- require handover documentation   
- run audits during transitions (privileged access review)   
 --- 
   
## 4) Privileged account management (PAM): the operations crown jewel   
A mature PAM program enforces:   
- unique accounts (no shared)   
- MFA   
- vaulting of secrets   
- time-bounded elevation (JIT)   
- session recording for critical systems   
- approval workflows for high-risk access   
- automatic deprovisioning (privileged access removed when role ends)   
   
### PAM failure modes   
- vault exists but people keep local admin accounts “for convenience”   
- JIT implemented but exceptions become permanent   
- session recording exists but no one reviews or alerts on anomalies   
 --- 
   
## 5) SLAs in security operations (use them as enforcement levers)   
SLAs convert “best effort” into governance:   
### Security operations SLA examples   
- incident triage time by severity   
- containment time for high-confidence compromise   
- patch deployment time by criticality tier   
- logging ingestion delay maximum   
- restore test frequency for critical services   
- change approval time windows   
   
### Why SLAs matter   
- they force resourcing discussions with leadership   
- they create measurable accountability   
- they make risk visible when targets aren’t met   
 --- 
   
## Micro cheat sheet (Chunk 3)   
- CM = **baseline + controlled provisioning + drift detection + governed change**.   
- Baselines must include identity/privilege/network/crypto/logging/update/backup requirements.   
- Drift detection must cover config, exposure, privilege, identity, and logging drift.   
- SoD prevents one person from controlling request+approval+implementation+evidence.   
- PAM is the enforcement engine for least privilege in operations.   
- SLAs turn security operations into measurable commitments.   
 --- 
   
Say **“next”** for **Chunk 4/5: 7.5 Resource protection + 7.6 Incident management** (media handling, data at rest/in transit, DLP; plus IR phases, containment strategies, evidence preservation, reporting, and lessons learned).   
Next   
## Domain 7 — Chunk 4/5   
### 7.5 Resource protection + 7.6 Incident management (media, data protection, DLP, IR lifecycle, containment strategy, recovery, lessons learned)   
This chunk is the **“protect the assets + survive the incident”** core of Security Operations. It connects directly to what your four books consistently emphasize in Domain 7: **media handling, data at rest/in transit, operational protection**, and the full **incident management lifecycle** (detect → respond → recover → improve).   
 --- 
# 7.5 Apply resource protection   
### 7.5 control objective   
**Information assets remain confidential, integral, and available throughout their lifecycle**—including when stored, transported, processed, backed up, and destroyed—and you can prove this via operational evidence.   
Resource protection in operations is about preventing:   
- accidental loss (mis-handling, mislabeling, disposal mistakes),   
- intentional theft (removable media, backups, laptops),   
- silent disclosure (unencrypted storage, uncontrolled sharing),   
- integrity compromise (tampering, unauthorized changes),   
- availability loss (ransomware, destructive insiders, bad changes).   
 --- 
   
## 1) Media management (the “physical path” of information)   
### 1.1 Media lifecycle (must be controlled end-to-end)   
A complete enterprise media lifecycle includes:   
1. **Acquisition** (approved media types only; inventory on receipt)   
2. **Classification & labeling** (data class, owner, retention)   
3. **Storage** (locked, access-controlled, monitored)   
4. **Use** (who can write/copy/export; logging where feasible)   
5. **Transport** (sealed, tracked, chain-of-custody for sensitive classes)   
6. **Reuse** (sanitization standards before redeployment)   
7. **Disposal** (verified destruction / sanitization; certificates of destruction)   
   
**Operational truth:** most real breaches involving media are not “advanced hacking”—they are *lost drives, unmanaged backups, and sloppy disposal.*   
### 1.2 Media types you must treat differently   
- **Removable flash media (USB):** highest exfil risk, easiest theft   
- **External drives / NAS snapshots:** common ransomware target   
- **Tape / cold backups:** often forgotten, often unencrypted (dangerous)   
- **Mobile devices:** personal cloud sync, screenshots, messaging apps   
- **Printed output:** still a major leak vector in regulated environments   
   
### 1.3 Chain-of-custody for media (when it becomes mandatory)   
Whenever media can become evidence (investigation) or is regulated (PII/PHI/PCI), treat it like evidence:   
- unique ID, handler logs, sealed storage, integrity checks (hashes where applicable), controlled access.   
 --- 
   
## 2) Media protection techniques (how you actually reduce risk)   
### 2.1 Prevention controls (stop leaks before they happen)   
- **Removable media restrictions**   
    - block unknown USB mass storage   
    - allow only managed/encrypted devices   
    - log all mount/copy operations on high-risk endpoints   
- **Encryption as policy**   
    - all portable media encrypted by default   
    - keys managed centrally (no “personal password-only” encryption)   
- **Physical controls**   
    - locked storage, restricted rooms, cameras for sensitive media areas   
- **Process controls**   
    - checkout/checkin, approvals for export, periodic audits of media inventory   
   
### 2.2 Sanitization / destruction (the part auditors love)   
Different situations require different methods:   
- **Clearing** (logical overwrite) — suitable for lower sensitivity; may be insufficient for some media types.   
- **Purging** (stronger sanitization, often cryptographic/firmware-based) — suitable for many modern drives if done correctly.   
- **Destroying** (physical destruction, shredding, pulverizing) — required for highest sensitivity or when media cannot be reliably sanitized.   
   
**Modern practical pattern:**   
For encrypted storage, **crypto-erase/crypto-shred** (destroy keys) can be the fastest reliable “purge,” *if* encryption was correctly implemented and keys are truly destroyed and unrecoverable.   
### 2.3 Backup media is special (because ransomware targets it)   
Operational rules that work in real enterprise:   
- backups must be **segmented** from user/admin planes   
- protect backup credentials with PAM/JIT   
- immutable snapshots / WORM storage for critical tiers   
- **restore testing** is mandatory (not optional)   
 --- 
   
## 3) Data at rest (operational protection, not “checkbox encryption”)   
### 3.1 The real at-rest control stack (layered)   
To protect data at rest, you need multiple layers:   
1. **Access control**   
    - identity-based permissions (least privilege)   
    - strong admin separation   
2. **Encryption**   
    - full disk encryption (endpoints/servers)   
    - database encryption (TDE where appropriate)   
    - object storage encryption (cloud-native encryption + key control)   
3. **Key management**   
    - centralized lifecycle (rotation, revocation, escrow policy)   
    - HSM/KMS where required   
    - separation of duties: storage admins ≠ key admins   
4. **Integrity**   
    - file integrity monitoring for sensitive paths   
    - signed artifacts for critical binaries/configs   
5. **Monitoring**   
    - access logs for sensitive datasets   
    - alerts on mass access, unusual admin operations   
   
### 3.2 The most common at-rest failures (and how to fix them)   
- “Encrypted” but keys stored on the same host → attacker gets both   
   
**Fix:** centralized KMS/HSM, key access policies, separation of duties.   
- Shared storage buckets/shares with broad read access   
   
**Fix:** least privilege + periodic access review + anomaly detection on reads.   
- Backups unencrypted or accessible from the same admin plane   
   
**Fix:** encrypt backups + isolate backup networks + separate credentials.   
 --- 
## 4) Data in transit (make it safe and still observable)   
### 4.1 The transport protection layers   
- **TLS** for application traffic (ideally modern versions; avoid downgrade)   
- **mTLS** for service-to-service trust in high-value internal paths   
- **IPsec/VPN** for network-layer confidentiality where appropriate   
- **Secure admin channels** (SSH with strong configs; bastion/jump host model)   
- **Integrity and replay protection** (part of modern crypto suites)   
   
### 4.2 The observability paradox (encryption hides payload)   
If everything is encrypted, network sensors lose payload visibility. Operations must shift to:   
- flow telemetry (who talked to whom, how much, when)   
- endpoint telemetry (process + connection attribution)   
- identity telemetry (auth + token events)   
- selective decryption only at approved choke points (proxy/WAF) with strict governance   
   
**Operational success condition:** you can still prove contract compliance (allowed/denied) even when payload is encrypted.   
 --- 
## 5) DLP (Data Loss Prevention) as an operations system (not a single tool)   
### 5.1 DLP architecture (three enforcement planes)   
1. **Endpoint DLP**   
    - blocks/alerts on copy-to-USB, print, screenshot, clipboard, uploads   
2. **Network DLP**   
    - inspects egress channels (proxy, email gateways) for sensitive content patterns   
3. **Cloud DLP / CASB-style controls**   
    - monitors SaaS usage, sharing, uploads, public links, OAuth abuse   
   
### 5.2 Classification is the “engine” of DLP   
DLP is only as good as:   
- data classification schemas   
- labeling enforcement (manual + automated)   
- detection methods:   
    - regex/pattern matching (fast, noisy)   
    - dictionaries and keywords (context-sensitive)   
    - fingerprinting / exact data match (high precision for known datasets)   
    - structured data detection (PII formats with validation)   
    - ML classification (powerful, must be tuned and governed)   
   
### 5.3 DLP failure modes   
- too many false positives → teams disable it   
- “monitor only” forever → no real protection   
- no exception governance → business workarounds explode   
- no privacy governance → monitoring itself becomes a compliance risk   
   
### 5.4 DLP evidence pack (what to prove)   
- policy definitions by data class and channel   
- alerts + dispositions (true/false/benign/malicious)   
- exception register with expiry and compensating controls   
- metrics: blocked exfil attempts, top offenders, time-to-disposition   
 --- 
   
# 7.6 Conduct incident management   
### 7.6 control objective   
**Detect, triage, contain, eradicate, recover, and improve** in a way that:   
- minimizes impact,   
- preserves evidence,   
- meets legal/regulatory obligations,   
- prevents recurrence.   
 --- 
   
## 1) Incident lifecycle (operations-grade)   
A complete lifecycle looks like:   
1. **Detection**   
2. **Triage**   
3. **Containment**   
4. **Eradication**   
5. **Recovery**   
6. **Reporting**   
7. **Remediation**   
8. **Lessons learned**   
   
**Key operational point:** these steps overlap. You often contain while still triaging; you recover while still investigating.   
 --- 
## 2) Incident classification (so response is consistent)   
### 2.1 Severity model (example pattern)   
- **SEV1:** identity tier compromise, ransomware spread, massive data exfil, major outage   
- **SEV2:** limited compromise with credible escalation risk   
- **SEV3:** contained malware, single host compromise, blocked exfil attempt   
- **SEV4:** suspicious but unconfirmed events (watch/hunt)   
   
### 2.2 Incident categories (so playbooks are reusable)   
- credential theft / identity abuse   
- malware/ransomware   
- web application breach   
- insider threat / policy violation   
- data leak / DLP event   
- DoS/service disruption   
- cloud control plane compromise (keys/roles/policies)   
- supply chain compromise (dependencies/build pipeline)   
 --- 
   
## 3) Containment strategy (the hardest decision in real IR)   
Containment is choosing the smallest action that stops spread *fast enough*.   
### 3.1 Containment levers (ordered by blast radius)   
**Identity levers (often most powerful)**   
- disable user / revoke sessions / rotate keys   
- remove privileged group memberships   
- disable service principals or OAuth tokens   
   
**Endpoint levers**   
- isolate host via EDR   
- block process hash/behavior   
- quarantine device in NAC/remediation VLAN   
   
**Network levers**   
- block specific east-west protocols (SMB/RDP) at boundaries temporarily   
- egress blocks to known C2 destinations   
- segmentation tightening (“emergency default deny” between tiers)   
   
**Application levers**   
- disable risky features, rotate secrets, enforce re-authentication   
- temporarily block sensitive endpoints/APIs   
   
### 3.2 Evidence vs availability (policy must decide)   
- Some incidents require **keep running + isolate + collect volatile** (memory, sessions)   
- Some require **shutdown** to stop destructive behavior   
   
Your IR policy must predefine which systems can be taken down and when.   
 --- 
## 4) Eradication (remove persistence, not just symptoms)   
Eradication is where teams lose time by “cleaning” instead of removing root cause.   
Effective eradication patterns:   
- rebuild from golden images for compromised hosts   
- rotate credentials and secrets implicated in attack path   
- remove persistence points (scheduled tasks/services/autoruns)   
- fix the initial vulnerability (patch/misconfig/coding flaw)   
- validate no secondary footholds exist (hunt for related indicators)   
 --- 
   
## 5) Recovery (restore service + validate integrity)   
Recovery is not complete when systems “come back up.” It’s complete when:   
- services meet expected functionality   
- logs and monitoring are re-enabled   
- identity trust is re-established (MFA, tokens, certs)   
- data integrity is validated (especially for ransomware or insider events)   
- you run post-recovery “must-deny/must-allow” checks (Domain 4 contracts)   
 --- 
   
## 6) Incident reporting (internal + external)   
A mature incident program has:   
- internal notification matrix (SOC → IR lead → legal → exec)   
- external notification triggers (regulator/customer/partners as required)   
- strict handling of sensitive details (need-to-know)   
   
Deliverable outputs:   
- technical report (timeline, evidence index, root cause, actions)   
- executive report (impact, decisions, remediation plan, residual risk)   
 --- 
   
## 7) Lessons learned (the “closure gate”)   
Lessons learned is not a meeting. It is a closure gate requiring:   
- timeline: what happened and how you know   
- why controls failed (prevention/detection gaps)   
- what changed (baselines, rules, training, architecture contracts)   
- retest proof that changes worked   
- regression guard so the same class of incident can’t reoccur silently   
 --- 
   
## Micro cheat sheet (Chunk 4)   
- Resource protection = lifecycle control of **media + data** (rest, transit, DLP, backups).   
- DLP works only with **classification + tuned enforcement + exception governance**.   
- Incident management is **containment strategy + evidence discipline + recovery integrity**.   
- The fastest safe eradication often equals **rebuild + secret rotation + contract tightening**.   
- Closure requires **lessons learned + concrete changes + retest proof**.   
 --- 
   
Say **“next”** for **Chunk 5/5: 7.7–7.15** (operate preventative measures; patch/vuln; change management; recovery/DR/BC; physical security; personnel safety/duress/travel; plus a final Domain-7 operator index and exam-grade memory anchors).   
Next   
## Domain 7 — Chunk 5/5   
### 7.7–7.15 Operations “run the controls” set (preventative controls ops, patch/vuln, change, recovery/DR/BC, physical security, personnel safety) + final operator index   
This chunk completes Domain 7 by covering the rest of the blueprint topics as operational systems: **controls are not “implemented”; they are maintained, measured, and proven**.   
 --- 
# 7.7 Operate and maintain detection and preventative measures   
### 7.7 control objective   
**Preventative and detective controls remain effective over time** despite:   
- new applications,   
- new attack techniques,   
- configuration drift,   
- performance and availability pressure.   
   
## 1) Firewall operations (policy is a living contract)   
Operational requirements:   
- rule lifecycle: owner, justification, expiry, recertification   
- hit counts: remove dead rules   
- change control: review + rollback + validation   
- segmentation integrity: ensure no failover bypass path exists   
- alerting on risky rule changes (any/any, broadened source/dest)   
   
**Failure mode:** “temporary allow” becomes permanent and silently collapses segmentation.   
## 2) Allow/deny lists (the reality)   
- **Allowlists** are high-assurance but require governance (onboarding/offboarding).   
- **Denylists** are necessary but reactive and bypassable.   
   
Operational rule: use allowlists for high-value tiers and stable dependencies; use denylists for commodity blocking (known-bad infrastructure) plus rapid response.   
## 3) Anti-malware / EDR operations   
What “maintain” means:   
- coverage: agent installed and healthy everywhere required   
- policy correctness: tamper protection, cloud connectivity, update cadence   
- response workflows: isolate host, contain process, collect evidence, restore   
- tuning: reduce noise without losing signal   
   
**Failure mode:** agents exist but are disabled, outdated, or excluded widely.   
## 4) Sandboxing and detonation   
Operational use cases:   
- email attachment detonation   
- web download detonation   
- suspicious binary triage   
   
Failure mode: sandbox becomes slow and users bypass it; you must tune latency vs coverage.   
## 5) Honeypots/honeynets (deception as detection)   
Operationally useful when:   
- you can isolate them safely,   
- you treat alerts as high-confidence,   
- you can use them to map attacker movement patterns.   
   
Failure mode: “cool project” that isn’t integrated into response workflows.   
## 6) ML/AI security tools (the governance requirement)   
Use ML/AI tools as *signal amplifiers*, but enforce:   
- data quality controls   
- model drift monitoring   
- explainability requirements (why did it alert?)   
- human validation loop   
   
Failure mode: automation without explainability leads to mistrust and disabling.   
 --- 
# 7.8 Implement and support patch and vulnerability management   
### 7.8 control objective   
**Known vulnerabilities are remediated within defined SLAs based on risk**, and remediation is verified (not assumed).   
## 1) The patch/vuln pipeline (end-to-end)   
1. intake (advisories, scanning, threat intel)   
2. prioritize (criticality tier + exploitability + exposure)   
3. acquire (approved sources)   
4. test (compatibility + rollback plan)   
5. deploy (phased rollout)   
6. verify (version/state validation)   
7. report (metrics + exceptions)   
8. improve (reduce recurring vuln families)   
   
## 2) Risk-based prioritization (what matters)   
Priority depends on:   
- exploit in the wild   
- internet exposure   
- reachable from low-trust zones   
- privilege required (none vs admin)   
- asset criticality tier (identity/backup/core platforms first)   
   
## 3) Exceptions (patch deferral) must be formal   
Patch exceptions require:   
- documented reason   
- compensating controls (segmentation, WAF, monitoring, disable feature)   
- expiry + re-evaluation triggers   
- evidence of monitoring for exploitation attempts   
   
**Failure mode:** “can’t patch” becomes a permanent posture.   
 --- 
# 7.9 Understand and participate in change management processes   
### 7.9 control objective   
**Production changes are controlled, attributable, reversible, and validated**, so security and availability don’t degrade invisibly.   
## 1) Change classes (operational reality)   
- standard change (pre-approved low-risk)   
- normal change (review + testing + approval)   
- emergency change (fast path, mandatory follow-up)   
   
## 2) Change security gates (the minimal set)   
- risk impact analysis (security + availability)   
- separation of duties for high-risk changes   
- logging of what changed (diff evidence)   
- validation tests (must-allow/must-deny, synthetic checks)   
- rollback readiness   
   
**Failure mode:** emergency changes without follow-up become the biggest long-term security debt.   
 --- 
# 7.10 Implement recovery strategies   
### 7.10 control objective   
**You can restore services and data to a trustworthy state within business requirements**, even during simultaneous security events.   
## Recovery strategy options (enterprise patterns)   
- backups (full/incremental/differential; snapshots)   
- replication (hot/warm)   
- multiple processing sites   
- high availability and fault tolerance   
- QoS (for critical traffic/services)   
   
Operational truth: recovery strategy is only real if **restore tests** succeed and meet RTO/RPO.   
 --- 
# 7.11 Implement disaster recovery processes   
### 7.11 control objective   
**DR is executable under stress**: people know roles, comms works, dependencies are mapped, and restoration steps are validated.   
Minimum DR process elements:   
- response and escalation procedures   
- personnel roles and contact lists   
- communication plans (internal and external)   
- damage assessment and prioritization   
- restoration plan and sequencing   
- training and awareness   
- lessons learned and updates   
 --- 
   
# 7.12 Test disaster recovery plan   
### 7.12 control objective   
**DR plans are not documents; they are rehearsed capabilities.**   
Common test progression:   
- tabletop / read-through   
- walkthrough   
- simulation   
- parallel test   
- full interruption   
   
Operational success criteria:   
- achieved RTO/RPO   
- validated dependencies (identity, DNS, network, cloud control plane)   
- documented issues with owners and deadlines   
- re-test proof after fixes   
 --- 
   
# 7.13 Participate in business continuity (BC) planning and exercises   
### 7.13 control objective   
**Critical business functions continue** through disruption, not just IT services.   
BC requires:   
- business impact analysis (BIA)   
- critical process identification   
- manual workarounds and alternate procedures   
- supplier/third-party continuity   
- exercise schedule and improvement loop   
   
Common failure mode: DR exists but BC fails because business processes weren’t practiced.   
 --- 
# 7.14 Implement and manage physical security   
### 7.14 control objective   
**Physical access to systems and facilities is controlled, monitored, and auditable**, preventing theft, tampering, and sabotage.   
## 1) Perimeter and facility controls   
- fences, gates, lighting   
- guards/patrols (where used)   
- CCTV and alarms   
- visitor management (badges, escorts, logs)   
   
## 2) Internal controls for sensitive areas   
- server rooms, network closets (IDF/MDF), labs   
- mantraps for high-security zones   
- locked racks/cages   
- environmental monitoring (fire, HVAC, water sensors)   
   
## 3) Operational evidence   
- badge/access logs reviewed on cadence   
- visitor logs matched to escort logs   
- alarm events investigated and documented   
- periodic physical audits (unmanaged devices, rogue patching)   
   
Failure mode: “we have cameras” but no one reviews logs or correlates anomalies.   
 --- 
# 7.15 Address personnel safety and security concerns   
### 7.15 control objective   
**People-related risks (travel, duress, social engineering, insider risks) are managed operationally** with procedures and training that actually change outcomes.   
## 1) Travel security (practical operational controls)   
- travel risk assessment by destination   
- device hygiene (loaner devices for high-risk travel)   
- minimal data on device; encrypted storage; strong auth   
- comms plan and escalation contacts   
- border search awareness and response guidance (per org legal policy)   
   
## 2) Duress and emergency management   
- duress codes / panic procedures (where relevant)   
- emergency contacts and escalation trees   
- clear authority to shut down accounts/devices fast   
- coordination with physical security and HR   
   
## 3) Social media and 2FA fatigue (modern ops reality)   
- awareness training targeted to real patterns (phishing, MFA fatigue prompts)   
- technical guardrails: number matching, phishing-resistant MFA where possible   
- incident playbooks for “suspected account takeover” including token revocation   
   
## 4) Insider threat support   
- separation of duties   
- monitoring of privileged actions (policy-based)   
- job rotation / mandatory vacation   
- clear reporting channels and response workflows   
 --- 
   
# Final Domain 7 Operator Index (what you should have “on the shelf”)   
## A) Monitoring & detection   
- log source coverage map (identity/endpoint/network/app/cloud)   
- detection catalog (dependencies, owners, test cases, tuning cadence)   
- egress monitoring policy and dashboards   
- SOC playbooks and escalation matrix   
   
## B) Investigations & IR   
- forensic readiness kit (tools + templates + chain of custody)   
- incident response plan + severity model + comms plan   
- containment playbooks (identity/endpoint/network)   
- evidence storage with integrity controls   
- post-incident review template + closure tracking   
   
## C) Configuration & change   
- baseline standards and golden images/templates   
- drift detection and auto-remediation boundaries   
- change management gates and validation tests   
- privileged access model + PAM workflows   
   
## D) Vulnerability & patch   
- tier-based patch SLAs and exception workflow   
- scanning schedule + retest requirements   
- exploit-intel intake and emergency patch process   
   
## E) Resilience   
- backup architecture + restore testing schedule   
- RTO/RPO mapping by business service   
- DR/BC plans + exercise calendar + after-action closure tracking   
   
## F) Physical & personnel   
- facility access controls + review cadence   
- visitor management procedures   
- travel/duress procedures + contacts   
- awareness program mapped to incidents   
 --- 
   
# Domain 7 Memory Anchors (exam-grade)   
- **Ops is a loop:** prevent → detect → respond → recover → improve.   
- **Logging is a system:** generate → collect → normalize → enrich → store → detect → respond → prove.   
- **Change without evidence** looks like attacker action.   
- **Backups aren’t real until restores pass**.   
- **Physical + people controls** are part of security operations, not “separate topics.”   
 --- 
   
If you want, say **“Domain 7 master matrix”** and I’ll compress all 5 chunks into **one single matrix**: objective → controls → evidence → metrics → failure modes → validation tests, so you can use it as your permanent Domain-7 reference sheet.   
Next   
قام بالتفكير لمدة 27s   
Below is the **Domain 7 master matrix** (all 5 chunks compressed into **one operator-grade table**) with the exact structure you asked for:   
**Objective → Controls → Evidence → Metrics → Failure modes → Validation tests**, plus a final column showing **coverage across the 4 books** (**AIO8 / OSG7 / SG4 / CBK4**).   
 --- 
## Domain 7 Master Matrix — Security Operations (7.1–7.15)   
|                                          7.x Area   <br> |                                                                     Control objective (what must be true)   <br> |                                                                                                                        Key operational controls (what you run in production)   <br> |                                                                                                   Evidence / artifacts (what you must be able to show)   <br> |                                                                                                KPIs / KRIs (how you measure health/risk)   <br> |                                                                     Common failure modes (what breaks in real orgs)   <br> |                                                         Validation tests / drills (how you prove it works)   <br> | 4-book coverage (AIO8 / OSG7 / SG4 / CBK4)   <br> |
|:---------------------------------------------------------|:-----------------------------------------------------------------------------------------------------------------|:------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|:--------------------------------------------------------------------------------------------------------------------------------------------------------------|:------------------------------------------------------------------------------------------------------------------------------------------------|:---------------------------------------------------------------------------------------------------------------------------|:------------------------------------------------------------------------------------------------------------------|:--------------------------------------------------|
|                 **Cross-cutting operating model**   <br> |                                   Security stays true under change, attack, and failure via a closed loop   <br> |                                                               Prevent→Detect→Respond→Recover→Improve loop; runbooks; ownership/RACI; on-call; postmortems; regression guards   <br> |                                                                          Ops playbooks; escalation matrix; postmortems; action tracker; evidence index   <br> |                                                                         MTTD/MTTR; recurrence rate; % actions closed; control coverage %   <br> |                                                  “One-time hardening”; no tuning; no closure loop; tribal knowledge   <br> |                      Quarterly “ops readiness” review; postmortem quality checks; control regression tests   <br> |                                  **All 4**   <br> |
|                            **7.1 Investigations**   <br> |                         Incidents can be investigated without destroying evidence; results are defensible   <br> |                                           Forensic readiness; chain of custody; order of volatility procedures; evidence storage integrity; legal/HR hooks; scope discipline   <br> |                                  Chain-of-custody forms; evidence IDs; hashes; evidence storage ACLs; investigation notes separating fact vs inference   <br> |                             KPI: time-to-preserve key artifacts; KRI: evidence handling violations; KRI: missing logs for critical tiers   <br> |                            “Fix-first” destroys evidence; weak custody; uncontrolled report sharing; time sync gaps   <br> |                                   IR drill with evidence capture; mock legal review; random custody audits   <br> |                                  **All 4**   <br> |
|                      **7.2 Logging & monitoring**   <br> |                 High-value events are captured, correlated, retained, and actionable (provable detection)   <br> |                    Log pipeline (generate→collect→normalize→enrich→store→detect→respond); SIEM use cases; UEBA enrichment; IDPS placement; egress monitoring; tuning cadence   <br> |        Log source inventory; parsing/ingest health; retention policy; detection catalog (dependencies + owners + test cases); “golden queries” library   <br> | KPI: log coverage % by tier; KPI: ingest delay/drop rate; KPI: alert precision proxy; KRI: missing identity/endpoint/network correlation   <br> |     “Enable logs” without ingestion; noisy detections; stale rules; perimeter-only visibility; no egress visibility   <br> | Detection unit tests (synthetic events); weekly tuning reviews; red/purple simulations to verify detection   <br> |                                  **All 4**   <br> |
|                  **7.3 Configuration management**   <br> |                                      Secure state is reproducible and drift is detected/corrected quickly   <br> |                         Baselines; golden images/templates; IaC/policy-as-code; drift detection; auto-remediation for safe controls; controlled provisioning; CMDB integrity   <br> |                                    Baseline standards; template repos; change diffs; drift reports; build attestations; restore-from-golden procedures   <br> |                                          KPI: baseline compliance %; KRI: drift age; KPI: rebuild time; KRI: unauthorized config changes   <br> |                        “Secure on day 1”; manual snowflakes; no drift detection; emergency changes never reconciled   <br> |                   Drift injection test; rebuild drill; baseline compliance scan + remediation verification   <br> |                                  **All 4**   <br> |
|                 **7.4 Foundational ops concepts**   <br> |            Privilege and responsibility are controlled so single actors can’t silently create catastrophe   <br> |                           Least privilege; need-to-know; separation of duties; PAM (vault/JIT/session recording); job rotation/mandatory vacation; SLA-driven accountability   <br> |                                   PAM logs; privileged session recordings; SoD mappings; access review outputs; SLA dashboards; break-glass procedures   <br> |                                    KPI: JIT adoption %; KRI: standing admin accounts; KPI: access review completion; KRI: SoD violations   <br> |                             Shared admin creds; “everyone is admin”; permanent exceptions; approvals rubber-stamped   <br> |                Privilege escalation simulation; SoD sampling; break-glass exercise with alert verification   <br> |                                  **All 4**   <br> |
|                       **7.5 Resource protection**   <br> |   Media and data are protected through lifecycle (rest/transit/export/disposal) and leakage is controlled   <br> |             Media lifecycle controls; encryption at rest + KMS; secure transport (TLS/VPN/mTLS); DLP (endpoint/network/cloud); removable media controls; backup immutability   <br> | Media inventory; sanitization/destruction proofs; encryption/KMS policy; key rotation evidence; DLP policies + dispositions; backup integrity evidence   <br> |                              KPI: % encrypted devices/media; KPI: restore-test pass %; KRI: DLP high-sev incidents; KRI: untracked media   <br> |                          “Backups forgotten”; keys stored with data; DLP too noisy so disabled; poor media disposal   <br> |                              Restore tests; DLP simulation exercises; removable media control verification   <br> |                                  **All 4**   <br> |
|                       **7.6 Incident management**   <br> |                  Incidents are handled consistently: detect→triage→contain→eradicate→recover→report→learn   <br> | Severity model; containment playbooks (identity/endpoint/network); evidence preservation; comms plan; eradication via rebuild+secret rotation; post-incident lessons learned   <br> |                                       Incident tickets; timelines; containment actions; evidence bundles; comms logs; lessons learned + action closure   <br> |                  KPI: time-to-contain by severity; KPI: time-to-eradicate; KRI: repeated incident class; KRI: uncontrolled spread events   <br> |                  No containment authority; slow identity actions; “cleaning” not eradication; weak comms; no retest   <br> |              Tabletop + live containment drills; ransomware simulation; postmortem regression verification   <br> |                                  **All 4**   <br> |
|     **7.7 Operate preventive/detective measures**   <br> |                                     Security tools remain effective and aligned to architecture contracts   <br> |             Firewall/proxy rule lifecycle; IDS/IPS tuning; allow/deny list governance; EDR health; sandbox workflows; honeypots integration; third-party services monitoring   <br> |                                                       Rule owners/expiry; change evidence; tool health dashboards; exceptions register; tuning history   <br> |                                        KPI: rule recertification rate; KRI: “any/any” drift; KPI: EDR coverage/health; KRI: bypass paths   <br> |                   “Set-and-forget”; tools deployed but not maintained; exceptions become permanent; failover bypass   <br> |                               Rule-drift tests; tool health audits; adversary emulation against detections   <br> |                                  **All 4**   <br> |
|          **7.8 Patch & vulnerability management**   <br> |                                 Vulnerabilities are reduced within risk-based SLAs and fixes are verified   <br> |                    Intake (advisories/scans/intel); prioritization by exposure/tier/exploitability; staged rollout; emergency patch path; verification; exception governance   <br> |                                      Patch deployment reports; vuln scan deltas; change tickets; exception approvals with expiry; verification outputs   <br> |                                  KPI: time-to-patch by tier; KRI: exploitable vuln backlog; KPI: retest success rate; KRI: exception age   <br> |                               Patch “installed” not verified; too many deferrals; weak inventory; no emergency lane   <br> |                               Emergency patch drill; “exploit-in-the-wild” response exercise; retest gates   <br> |                                  **All 4**   <br> |
|           **7.9 Change management participation**   <br> |                                Production changes are controlled, attributable, reversible, and validated   <br> |                            Change types (standard/normal/emergency); risk impact analysis; approvals; rollback plans; pre/post validation; segregation for high-risk changes   <br> |                                                                         Change records; approvals; diffs; validation evidence; rollback execution logs   <br> |                                      KPI: % changes with validation; KRI: emergency changes without follow-up; KRI: unauthorized changes   <br> |                         CAB theater; emergency changes become normal; no rollback readiness; no security validation   <br> |                                     Change game-day; random sampling for traceability; rollback simulation   <br> |                                  **All 4**   <br> |
|                      **7.10 Recovery strategies**   <br> |                                         Services and data can be restored to trusted state within RTO/RPO   <br> |                                                     Backup strategy (3-2-1 where relevant); immutability; replication; HA/fault tolerance; recovery sequencing by dependency   <br> |                                                                   Backup/replication architecture; restore runbooks; restore evidence; dependency maps   <br> |                                                                 KPI: restore success rate; KPI: restore time vs RTO; KRI: RPO violations   <br> |                       “Green backups” but restores fail; backup plane reachable by attackers; untested dependencies   <br> |                                Routine restore tests; integrity validation; ransomware recovery simulation   <br> |                                  **All 4**   <br> |
|              **7.11 Disaster recovery processes**   <br> |                                                DR can be executed under stress with clear roles and comms   <br> |                                                                      DR roles/contacts; comms plan; damage assessment; phased restoration; training; lessons learned updates   <br> |                                                               DR plan versions; contact lists; comms artifacts; restoration logs; after-action reports   <br> |                                                                   KPI: DR readiness score; KRI: stale plans; KRI: unresolved DR findings   <br> |                                    Plans don’t match reality; identity/DNS/network dependencies missed; no training   <br> |                                           DR tabletop + walkthrough; comms drill; dependency failover test   <br> |                                  **All 4**   <br> |
|                             **7.12 Test DR plan**   <br> |                                        DR effectiveness is proven through exercises and measured outcomes   <br> |                                                            Tabletop → walkthrough → simulation → parallel → full interruption (as risk allows); stakeholder comms rehearsals   <br> |                                                                     Exercise scope/results; achieved RTO/RPO evidence; issue tracker with owners/dates   <br> |                                              KPI: % critical services tested; KRI: repeat DR failures; KPI: closure time for DR findings   <br> |                                           Exercises too shallow; findings never closed; “annual test” checkbox only   <br> |                                  Scheduled DR exercises with measurable targets; re-test after remediation   <br> |                                  **All 4**   <br> |
| **7.13 Business continuity planning & exercises**   <br> |                                                   Critical business functions continue through disruption   <br> |                                                                                         BIA; critical process mapping; manual workarounds; supplier continuity; BC exercises   <br> |                                                                  BIA outputs; continuity playbooks; exercise artifacts; vendor continuity attestations   <br> |                                                        KPI: BC exercise completion; KRI: unmitigated single points in business processes   <br> |                       DR exists but business can’t operate; manual processes unpracticed; vendor dependency ignored   <br> |                      BC tabletop with business units; alternate procedure drills; vendor outage simulation   <br> |                                  **All 4**   <br> |
|                        **7.14 Physical security**   <br> |                                                   Physical access is controlled, monitored, and auditable   <br> |                                   Perimeter controls; visitor management; controlled areas (server rooms/IDF/MDF); cameras/alarms; environmental monitoring; physical audits   <br> |                                                        Badge/access logs; visitor logs; CCTV retention; alarm incident records; physical audit reports   <br> |                                               KPI: access log review compliance; KRI: unauthorized access attempts; KRI: orphaned badges   <br> |                                 “Cameras exist” but no review; tailgating; unmanaged closets; weak visitor controls   <br> |                                         Physical access audit; tailgating tests; closet inspection cadence   <br> |                                  **All 4**   <br> |
|              **7.15 Personnel safety & security**   <br> | People-related risks (travel, duress, social engineering, insider) are reduced and response is executable   <br> |                        Travel security SOPs; loaner devices for high-risk travel; duress procedures; targeted awareness; MFA fatigue guardrails; insider reporting workflows   <br> |                                    Training completion + effectiveness metrics; travel briefs; duress escalation records; phishing simulation outcomes   <br> |                                KPI: report rate for phishing; KRI: repeat high-risk behaviors; KPI: time-to-disable compromised identity   <br> |                            Awareness theater; no technical guardrails; weak insider escalation; unclear duress plan   <br> |                                Phishing drills; MFA fatigue simulation response; travel readiness tabletop   <br> |                                  **All 4**   <br> |

 --- 
### If   

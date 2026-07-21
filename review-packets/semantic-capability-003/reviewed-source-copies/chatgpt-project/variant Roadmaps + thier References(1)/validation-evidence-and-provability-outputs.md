---
# yaml-language-server: $schema=schemas\page.schema.json
Object type:
    - Page
Backlinks:
    - 'start journy - From ChatGPT -Plane To start realy in Cyber-Sec '
Creation date: "2026-03-04T19:10:41Z"
Created by:
    - Perky Sparrow
id: bafyreibarqiycq3lzkzabhxyrtc7af2k4azvsnwzndyrgr5wziqk4sthwe
---
# Validation, Evidence, and “Provability” Outputs   
> other-but   

## Validation, Evidence, and “Provability” Outputs — Part 1/6   
# 12.1 Personal knowledge base: index → deep notes → labs → checklists   
## Your “security brain” as an engineered system (searchable, testable, reusable)   
This module is how you stop being “someone who learned things” and become “someone who can *operate* security.” Your outputs are the product.   
You’re building a personal knowledge base (PKB) that functions like:   
- a **source-of-truth** for models and decisions   
- a **lab notebook** for reproducible experiments   
- a **runbook library** for real incidents and change   
- a **portfolio** that proves skill without unsafe behavior   
 --- 
   
# 12.1.1 The PKB architecture (4 layers, always)   
## Layer 0 — Index (TOC + navigation)   
A single master index that answers:   
- “Where is the deep note on X?”   
- “Which lab proves X?”   
- “Which checklist validates X?”   
- “Which playbook uses X?”   
   
## Layer 1 — Deep notes (models + internals)   
These are your “truth documents”:   
- Windows tokens, ACLs, GPO internals   
- Linux DAC/ACL/caps/LSM   
- SMB/LDAP/Kerberos/HTTP negotiation models   
- Detection engineering pipeline (SIEM schema, tuning lifecycle)   
   
Rules:   
- One concept per note (atomic docs)   
- Must include diagrams/tables and invariants   
- Must end with: “What to verify” and “Common failure modes”   
   
## Layer 2 — Labs (reproducible experiments)   
Each lab is a controlled test:   
- objective   
- environment setup   
- steps (safe)   
- expected artifacts (pcaps/logs/events)   
- success criteria   
- teardown   
- what it proves (invariants)   
   
Labs are how you “prove you know it.”   
## Layer 3 — Checklists (operational validation)   
Short, field-ready validation:   
- “Is SMB signing required and observable?”   
- “Is LDAP bind policy enforced and do we see offenders?”   
- “Is endpoint baseline compliant and drift-detected?”   
   
Checklist rules:   
- minimal steps   
- clear pass/fail   
- mapped to evidence sources   
- mapped to owners (in enterprise)   
 --- 
   
# 12.1.2 PKB file/folder structure (battle-tested layout)   
Use any system (Obsidian/Notion/Markdown repo). The structure matters more than the tool.   
### Suggested top-level   
- `00\_INDEX/`   
- `10\_MODELS/` (deep notes)   
- `20\_LABS/`   
- `30\_CHECKLISTS/`   
- `40\_PLAYBOOKS/`   
- `50\_ARTIFACTS/` (diagrams, matrices, baselines)   
- `60\_REFERENCE/` (standards, RFC pointers, vendor docs)   
- `70\_CHANGELOG/` (what you updated and why)   
   
### Inside 10\_MODELS/   
Organize by planes:   
- `Identity/` (Kerberos, NTLM, tokens, SIDs, sudo/PAM)   
- `Network/` (DNS/DHCP/SMB/LDAP/HTTP, protocol research notes)   
- `Endpoint/` (Windows stack, Linux stack)   
- `Cloud\_K8s/` (RBAC, admission, audit)   
- `Detection\_IR/` (SIEM schema, IR lifecycle, playbook patterns)   
 --- 
   
# 12.1.3 The “atomic note” template (use this every time)   
### Atomic Note:    
1. **Definition** (one paragraph)   
2. **Internal model** (components + boundaries)   
3. **Wire/OS artifacts** (packets/logs/objects)   
4. **State machine** (if applicable)   
5. **Invariants** (what must always be true)   
6. **Failure modes** (how it breaks)   
7. **Controls** (where to enforce)   
8. **Evidence** (what proves it)   
9. **Validation** (lab links + checklists)   
10. **References** (RFCs/docs)   
   
This template forces “provability” into the note itself.   
 --- 
# 12.1.4 The “lab note” template (reproducible, portfolio-ready)   
### Lab:    
1. **Objective**   
2. **Scope & safety**   
3. **Topology** (diagram)   
4. **Setup** (versions, configs)   
5. **Execution steps**   
6. **Expected artifacts**   
    - pcap(s)   
    - logs (event IDs, journald queries)   
    - screenshots (optional)   
7. **Success criteria**   
8. **Teardown / cleanup**   
9. **What it proves** (link to invariants)   
10. **Extensions** (harder variations)   
 --- 
   
# 12.1.5 The “checklist” template (ops-grade)   
### Checklist:    
- **Control objective** (one line)   
- **Scope** (which hosts/zones)   
- **Verify**   
    - command/query 1 → expected output   
    - command/query 2 → expected output   
- **Evidence**   
    - log source(s)   
    - dashboard view(s)   
- **Failure modes**   
- **Remediation pointers**   
- **Cadence**   
    - daily/weekly/quarterly   
 --- 
   
# 12.1.6 Index strategy: how to make it navigable at scale   
Your master index should have:   
## A) A tree TOC (like your journey index)   
- nodes (Windows, Linux, AD, Protocols, SIEM, IR, Red Team ops…)   
- each node links to:   
    - model notes   
    - labs   
    - checklists   
    - artifacts (diagrams/matrices)   
   
## B) Cross-links by plane   
Every note should link “up” to:   
- the plane (identity/network/endpoint/detection)   
- and “sideways” to related topics (SMB ↔ Kerberos ↔ SPNEGO ↔ LDAP)   
   
## C) A “Now / Next / Backlog” queue   
Treat learning like engineering:   
- `NOW`: current sprint notes + labs   
- `NEXT`: queued modules   
- `BACKLOG`: future topics   
 --- 
   
# 12.1.7 “Provability” in your PKB: the key rule   
Every important claim in your knowledge base must be backed by **at least one** of:   
- a lab artifact (pcap/log screenshot, reproducible)   
- a verification checklist (commands + expected results)   
- a primary reference (RFC/vendor docs)   
   
This prevents “handwavy expertise.”   
 --- 
## Deliverables you should create this week (fast wins)   
1. `00\_INDEX/master\_toc.md` (the map)   
2. One atomic note: `10\_MODELS/Identity/Windows\_Token\_Model.md`   
3. One lab: `20\_LABS/Protocols/HTTP\_Negotiate\_Baseline.md`   
4. One checklist: `30\_CHECKLISTS/Windows/Defender\_Health\_and\_Tamper.md`   
   
These four establish the system.   
 --- 
If you reply **next**, I’ll go to **Part 2/6: 12.2 Architecture artifacts**:   
- trust-boundary diagrams   
- identity flow diagrams   
- segmentation contracts   
   
…and I’ll give you **templates + the exact fields each diagram must include** so your artifacts become audit-proof and engineering-usable.   
next   
## Validation, Evidence, and “Provability” Outputs — Part 2/6   
# 12.2 Architecture artifacts: trust-boundary diagrams, identity flow diagrams, segmentation contracts   
## Templates + required fields so your artifacts become engineering-usable and audit-proof   
If you want “above-CISSP,” you must communicate security as **systems engineering**. These artifacts are how you:   
- make security intent explicit,   
- make enforcement points testable,   
- and make drift detectable.   
   
You will produce three primary artifact families:   
1. **Trust-boundary diagrams** (zones, identities, control planes, data planes)   
2. **Identity flow diagrams** (authN/authZ dataflow with artifacts)   
3. **Segmentation contracts** (allowed conduits with owners, evidence, and tests)   
 --- 
   
# 12.2.1 Trust-boundary diagrams (TBD): the highest ROI artifact   
A trust-boundary diagram is not a network map. It is a **policy map**.   
## A) Required elements (non-negotiable)   
Every TBD must include:   
### 1) Security domains (zones)   
Examples (your environment):   
- User endpoints zone   
- Server zone   
- Management zone (PAWs/jumps/bastions)   
- Tier-0 identity zone (DCs, PKI, ADFS/IdP)   
- Kubernetes control plane zone   
- OT/ICS zones (enterprise IT, ICS DMZ, control network)   
   
### 2) Control planes vs data planes   
Explicitly distinguish:   
- control plane traffic (admin APIs, directory, orchestration, management)   
- data plane traffic (app data, file access, user workloads)   
   
Because compromise of control plane has disproportionate impact.   
### 3) Trust boundaries (where identity changes meaning)   
Mark boundaries where identity is minted or transformed:   
- logon/token creation (Windows LSASS)   
- LDAP bind transitions (anonymous → authenticated)   
- K8s API authn (JWT/mTLS) and authorization (RBAC)   
- proxy boundary (Forwarded headers, JWT validation)   
   
### 4) Control points (PEPs)   
For each boundary, show the enforcement points:   
- firewalls (north-south and east-west)   
- reverse proxy/API gateway   
- endpoint firewall   
- EDR (visibility/response)   
- AD GPO baseline enforcement   
- K8s admission controller   
- OT firewalls and jump servers   
   
### 5) Telemetry points   
Where evidence comes from:   
- DC logs   
- firewall logs   
- proxy logs   
- EDR   
- K8s audit logs   
- OT passive sensors   
   
**If a boundary has no telemetry, it isn’t “controlled.”**   
 --- 
## B) Diagram legend / metadata block (put this on every diagram)   
- Diagram name + version + date   
- Owner (team/person)   
- Scope (what environment)   
- Assumptions (e.g., “all admin via PAW”)   
- Threat model notes (what you’re defending against)   
- Link to segmentation contract table (12.2.3)   
- Link to control matrix (12.3)   
   
This makes the diagram auditable and prevents “diagram drift.”   
 --- 
## C) The “tiering overlay” (must include for AD enterprises)   
Overlay or annotate:   
- Tier-0 assets and allowed admin sources only   
- Tier-1 server admin sources   
- Tier-2 endpoints (no admin to Tier-0)   
   
If you don’t show tiering explicitly, your diagram won’t prevent real-world breaches.   
 --- 
# 12.2.2 Identity flow diagrams (IFD): “wire → auth artifacts → token → authorization”   
These diagrams prove you understand systems at operator level.   
## A) Required elements   
### 1) Actors   
- Client (user/device)   
- IdP/KDC (AD DC)   
- Resource server (SMB server, LDAP server, web server)   
- Intermediaries (reverse proxy, load balancer, gateway)   
   
### 2) Protocol messages (high-level but ordered)   
For example:   
- SMB: NEGOTIATE → SESSION\_SETUP (SPNEGO) → TREE\_CONNECT → CREATE…   
- LDAP: StartTLS → SASL bind → search/modify   
- HTTP: 401 Negotiate → Authorization: Negotiate → success   
- Kerberos: AS-REQ/AS-REP → TGS → AP-REQ   
   
### 3) Auth artifacts (what’s carried)   
- Kerberos tickets (TGT/TGS)   
- SPNEGO tokens (negTokenInit/Resp)   
- LDAP bind credentials (simple vs SASL)   
- HTTP Authorization/Cookie/Bearer tokens   
- Windows access token and SIDs (server-side for SMB access checks)   
   
### 4) Authorization decision point   
Explicitly mark where access is decided:   
- Windows AccessCheck (token × SD × access mask)   
- AD object ACL checks on LDAP modify/search   
- Web app authorization (RBAC/ABAC)   
- K8s RBAC authorization decision   
   
### 5) Telemetry mapping   
For each step, show:   
- which logs record it   
- which SIEM fields you normalize it into   
   
This is what turns diagrams into “provability.”   
 --- 
## B) The “failure-mode overlay” (what pros add)   
For each identity flow, add callouts:   
- “If DNS is wrong → DC locator fails → Kerberos fails → fallback behavior”   
- “If TLS termination occurs at proxy → identity assertion must be trusted and protected”   
- “If time skew → Kerberos fails (detect via error codes + NTP drift)”   
   
This makes the diagram operational.   
 --- 
# 12.2.3 Segmentation contracts (SC): the enforceable truth table of connectivity   
A segmentation contract is the written form of your network trust model. It is what makes “zero trust” measurable.   
## A) Contract schema (fields you must include)   
Each row is one allowed conduit. Everything else is denied.   
**Fields:**   
1. **Source zone** (e.g., User, Server, PAW, Tier-0, K8s Node, ICS DMZ)   
2. **Destination zone**   
3. **Source identities allowed** (users/groups/service accounts; device posture if used)   
4. **Destination service** (hostname/service role)   
5. **Protocol + ports** (e.g., TCP 445, TCP 389/636, TCP/UDP 88)   
6. **Purpose** (business justification)   
7. **Enforcement points**   
    - network firewall   
    - host firewall   
    - gateway/proxy   
    - K8s network policy (if relevant)   
8. **Authentication requirements**   
    - mTLS, Kerberos, MFA, etc.   
9. **Encryption requirements**   
10. **Logging requirements**   
    - what must be logged (allow/deny; identity mapping)   
11. **Evidence queries**   
    - which dashboards/queries prove compliance   
12. **Failure modes**   
    - what breaks if misconfigured   
13. **Owner**   
14. **Review cadence**   
15. **Exception handling**   
    - expiry date + compensating controls   
   
This is the control matrix’s “network section,” but it’s also used by ops to implement firewall rules.   
 --- 
## B) Contract examples (your arena)   
### Example 1 — PAW → DC management   
- Source zone: PAW   
- Dest zone: Tier-0 (DCs)   
- Protocols: directory/auth/mgmt required   
- Auth: privileged identity only   
- Logging: DC security logs + firewall allow logs   
- Evidence: “any DC admin logon from non-PAW = violation”   
- Owner: identity team   
- Cadence: monthly   
   
### Example 2 — User endpoints → File server (SMB)   
- Source zone: User endpoints   
- Dest zone: File servers   
- Ports: TCP 445 only (if needed)   
- Auth: Kerberos preferred; signing required for sensitive shares   
- Logging: share access logs (server) + firewall logs   
- Evidence: unusual CREATE patterns or access to restricted shares triggers alert   
- Owner: infra   
   
### Example 3 — IT → ICS DMZ brokered access   
- Source: IT management   
- Dest: ICS DMZ jump   
- Ports: RDP/SSH only via approved gateway   
- Auth: MFA + time-bound vendor access   
- Logging: session recording + firewall logs + OT sensor   
- Owner: OT security   
- Cadence: weekly review   
 --- 
   
# 12.2.4 How to keep artifacts from rotting (drift control)   
Artifacts die unless you attach them to change control.   
## The anti-drift loop   
1. Any network or identity change requires updating:   
    - trust boundary diagram (if topology/policy changes)   
    - segmentation contract row(s)   
2. CI-like validation:   
    - must-deny tests (automated connection checks)   
    - policy compliance dashboards   
3. Quarterly recertification:   
    - owners confirm necessity of conduits and exceptions expire   
   
This is “security as an engineering loop.”   
 --- 
# 12.2.5 What “provable” looks like in artifacts   
An artifact is provable when:   
- it names enforcement points   
- it names evidence sources   
- it includes verification tests   
   
**If it’s a pretty picture without evidence, it’s not a security artifact.**   
 --- 
## Deliverables you should create next (concrete)   
1. **Trust Boundary Diagram v1** (one page)   
    - zones + control points + telemetry points   
2. **Identity Flow Diagram: SMB auth to NTFS AccessCheck**   
3. **Segmentation Contract Table v1**   
    - at least 20 conduits (start with Tier-0 and management plane)   
 --- 
   
If you reply **next**, I’ll move to **Part 3/6: 12.3 Control matrices**:   
**control → threat → evidence → monitoring → failure modes**   
…and I’ll give you a ready-to-use matrix structure that unifies Windows/Linux/AD/K8s/OT in one consistent schema, plus examples of “best-in-class” rows.   
next   
## Validation, Evidence, and “Provability” Outputs — Part 3/6   
# 12.3 Control matrices: control → threat → evidence → monitoring → failure modes   
## The single table that turns “security” into engineering reality   
A control matrix is your master *systems spec* for security. It answers, for each control:   
- **What threat does it address?**   
- **Where is it enforced?**   
- **How do we prove it’s working?**   
- **How do we monitor drift and failure?**   
- **What are the failure modes and compensating controls?**   
   
If you do this well, you can:   
- onboard new engineers fast,   
- run audits without panic,   
- and run red/purple tests as structured validation.   
 --- 
   
# 12.3.1 The matrix schema (columns you should standardize)   
Use this as your canonical structure (copy/paste into your PKB or a spreadsheet):   
1. **Control ID** (stable: e.g., `ID-T0-01`, `NET-CONDUIT-07`)   
2. **Control objective** (one sentence, measurable)   
3. **Threat(s) mitigated** (category + example)   
4. **Scope** (assets/zones/tiers)   
5. **Enforcement point(s)** (where it’s implemented)   
6. **Mechanism** (how it works internally)   
7. **Evidence** (what proves enforcement)   
8. **Monitoring** (what alerts/dashboards watch drift/failure)   
9. **Failure modes** (how it breaks)   
10. **Severity/impact if broken**   
11. **Owner**   
12. **Review cadence**   
13. **Exceptions policy** (expiry + compensating controls)   
14. **Validation tests** (must-allow/must-deny + purple emulation link)   
15. **Dependencies** (DNS/time/PKI/IdP, etc.)   
   
**Rule:** if any row lacks “Evidence” and “Validation tests,” it’s not a real control.   
 --- 
# 12.3.2 Control categories (so you can cover the whole enterprise)   
Group controls into these sections:   
## A) Identity plane (Tier-0)   
- admin tiering / PAW-only admin   
- DC hardening + auditing   
- privileged group governance   
- Kerberos/LDAP/NTLM policies (where applicable)   
- PKI controls (if used)   
   
## B) Endpoint/server baselines   
- Windows: Defender/firewall/updates/UAC/admin rights   
- Linux: ssh/sudo, service minimization, auditd, LSM enforcement   
- secrets hygiene   
   
## C) Network contracts   
- segmentation conduits   
- management plane separation   
- egress control   
- OT conduits via ICS DMZ   
   
## D) AppSec/DevSecOps   
- gateway controls (auth, rate limits)   
- CI/CD signing and secrets   
- runtime posture (containers, seccomp/LSM/caps)   
   
## E) Detection & response   
- logging coverage and integrity   
- SIEM parsing/normalization health   
- IR playbooks and response automation   
- purple-team validation   
 --- 
   
# 12.3.3 What “Mechanism” should look like (internal truth, not marketing)   
The mechanism column should reference the internal model you already built.   
Examples:   
- “Windows AccessCheck uses subject token SIDs/privileges against object SD DACL; deny-only SIDs in filtered token change allow matching.”   
- “LDAP bind changes per-connection authorization state; StartTLS upgrades transport; signing/channel binding enforce integrity of bind on DC.”   
- “SMB SESSION\_SETUP carries SPNEGO tokens; dialect negotiation and pre-auth integrity prevent downgrade within supported scope.”   
   
Mechanism is where you prove you understand internals.   
 --- 
# 12.3.4 Example best-in-class rows (your arena)   
## Row 1 — Tier-0 admin isolation (high leverage)   
- **Control ID:** `ID-T0-01`   
- **Objective:** Tier-0 administrative authentication must occur only from approved PAWs/jump hosts.   
- **Threat mitigated:** credential theft → domain compromise; lateral movement to DCs   
- **Scope:** DCs, PKI/IdP, Tier-0 admin accounts   
- **Enforcement:**   
    - account logon restrictions (where feasible)   
    - network segmentation (PAW subnet to DC mgmt only)   
    - tiering policy   
- **Mechanism:** prevents privileged creds from being exposed on user endpoints; reduces attack paths.   
- **Evidence:** DC logons show privileged principals only from PAW device IDs/subnets.   
- **Monitoring:** alert on privileged logon events from non-PAW sources; weekly PAW compliance report.   
- **Failure modes:** exceptions for “urgent admin”; shared admin accounts; PAW used for browsing.   
- **Validation tests:** purple emulation “attempt admin logon from non-PAW should deny/alert.”   
- **Owner:** Identity team   
- **Cadence:** monthly   
   
## Row 2 — SMB signing/encryption posture for sensitive shares   
- **Control ID:** `NET-SMB-03`   
- **Objective:** Sensitive SMB shares must require signing and/or encryption, and policy drift must be detected.   
- **Threat mitigated:** MITM tampering, credential relay risk surfaces, integrity of SYSVOL/NETLOGON-like paths   
- **Scope:** file servers, DC shares, admin shares   
- **Enforcement:** server SMB policy; share configuration; client UNC hardening where applicable   
- **Mechanism:** SMB signing provides message integrity; SMB3 encryption provides confidentiality+integrity (visibility depends on capture point).   
- **Evidence:** negotiated session properties; server policy config; relevant logs for share access.   
- **Monitoring:** alert on downgrade/drift in negotiated properties; anomaly detection on share access patterns.   
- **Failure modes:** legacy clients require weaker settings; exception sprawl.   
- **Validation tests:** replay pcap story; must-negotiation properties match baseline.   
- **Owner:** Infra   
- **Cadence:** quarterly + change-driven   
   
## Row 3 — LDAP bind safety (StartTLS/SASL, signing/channel binding)   
- **Control ID:** `ID-LDAP-02`   
- **Objective:** Insecure LDAP binds must be prevented or detected and eliminated through migration; directory access must be attributable.   
- **Threat mitigated:** credential exposure, downgrade/stripping, directory scraping   
- **Scope:** DC LDAP endpoints + directory-integrated apps   
- **Enforcement:** DC LDAP policy; application configs; network controls   
- **Mechanism:** StartTLS provides transport protection; SASL binds use stronger mechanisms; signing/channel binding prevent unsafe binds.   
- **Evidence:** Directory Service logs identifying insecure bind clients; TLS usage evidence in telemetry.   
- **Monitoring:** dashboard of bind types over time; alert on policy violations.   
- **Failure modes:** legacy apps break; teams disable enforcement.   
- **Validation tests:** lab “insecure bind attempt should be logged/blocked; secure bind succeeds.”   
- **Owner:** Identity + App owners   
- **Cadence:** monthly until clean, then quarterly   
   
## Row 4 — Linux sudoers hygiene   
- **Control ID:** `LIN-PRIV-01`   
- **Objective:** Sudo privileges must be least-privilege, audited, and environment-safe; NOPASSWD must be scoped and time-bounded.   
- **Threat mitigated:** accidental root pathways, privilege escalation via allowed tooling, poor accountability   
- **Scope:** Linux servers, bastions, K8s nodes (if ssh allowed)   
- **Enforcement:** `/etc/sudoers.d` managed; PAM policy; file permissions; config management   
- **Mechanism:** sudo mediates privilege elevation with policy + auditing; env\_reset/secure\_path reduce execution ambiguity.   
- **Evidence:** sudo logs forwarded; audit watch on sudoers files; periodic `sudo -l` review outputs.   
- **Monitoring:** alert on sudoers file changes; unusual sudo command patterns.   
- **Failure modes:** admins add broad wildcard rules; config drift.   
- **Validation tests:** lab change triggers alert; approved command works; forbidden command denied.   
- **Owner:** Linux platform team   
- **Cadence:** quarterly + change-driven   
   
## Row 5 — Logging integrity (MES guarantee)   
- **Control ID:** `LOG-CORE-01`   
- **Objective:** Minimum Evidence Set logs must be collected off-host with retention sufficient for investigations; loss/tamper must be detectable.   
- **Threat mitigated:** blind spots, attacker log wiping, inability to reconstruct timelines   
- **Scope:** DCs, servers, endpoints, gateways, K8s audit, OT boundary   
- **Enforcement:** WEF/agents; SIEM pipelines; immutable storage where feasible   
- **Mechanism:** off-host forwarding + retention + pipeline health monitoring provides evidence integrity.   
- **Evidence:** ingestion dashboards, agent heartbeats, parse success rates, retention proofs.   
- **Monitoring:** alert on source silence, parsing failures, volume anomalies.   
- **Failure modes:** pipeline outages, agent disabled, storage retention misconfig.   
- **Validation tests:** monthly synthetic event suite appears in SIEM within SLA.   
- **Owner:** SOC platform team   
- **Cadence:** weekly monitoring, monthly test   
 --- 
   
# 12.3.5 How to build your matrix without missing anything (systematic method)   
## Step 1 — Start from planes   
Identity, endpoint, network, app, detection/response, OT.   
## Step 2 — For each plane, list “must be true” invariants   
Example invariants:   
- “Tier-0 never administered from endpoints”   
- “Only gateway terminates external web traffic”   
- “No insecure LDAP binds”   
- “Server egress constrained”   
- “Logs off-host within X minutes”   
   
## Step 3 — Convert each invariant into 1–3 controls   
Keep controls atomic.   
## Step 4 — Attach evidence and tests immediately   
Don’t allow “we’ll add monitoring later.” Monitoring and evidence are part of the control definition.   
 --- 
# 12.3.6 The “matrix-to-action” link (how it drives operations)   
This matrix becomes:   
- your audit evidence guide   
- your purple-team test catalog basis   
- your baseline drift report structure   
- your incident response evidence checklist   
   
This is why it’s the central artifact.   
 --- 
## Deliverables you should create now (concrete)   
1. A v1 matrix with **40–60 rows**, including:   
    - 10 identity plane controls   
    - 10 endpoint/server baseline controls (Windows + Linux)   
    - 10 network contract controls   
    - 5 K8s controls (if applicable)   
    - 5 OT boundary controls   
    - 10 logging/detection/IR controls   
2. For each row: at least one evidence source + one validation test link.   
 --- 
   
If you reply **next**, I’ll go to **Part 4/6: 12.4 Baselines**   
**Hardened configs + verification queries + drift detection** — and I’ll give you:   
- baseline packs per role (endpoint/server/DC/PAW/Linux server/K8s node/OT broker)   
- verification commands/queries (Windows + Linux + SIEM)   
- drift detection design (what to diff, how often, and how to alert)   
   
next   
## Validation, Evidence, and “Provability” Outputs — Part 4/6   
# 12.4 Baselines: hardened configs + verification queries + drift detection   
## Role-based baseline packs (Windows/Linux/AD/K8s/OT) with “prove it” checks   
Baselines are the “desired state” contract for systems. A baseline is not real unless you can:   
- **verify** it quickly (queries/commands),   
- **detect drift** continuously,   
- and **prove enforcement** with evidence.   
   
We’ll build this as:   
1. baseline model (what belongs in a baseline)   
2. baseline packs by role (your arena)   
3. verification queries (Windows + Linux + network + SIEM)   
4. drift detection design (what to diff, cadence, alerting)   
5. exception handling (anti-drift governance)   
 --- 
   
# 12.4.1 What a baseline contains (always the same structure)   
Each baseline pack must include:   
1. **Scope** (asset class: endpoint/server/DC/PAW, etc.)   
2. **Control objectives** (top 10 “must be true”)   
3. **Configuration items** (GPO settings, sshd\_config, firewall rules, etc.)   
4. **Verification queries** (commands + expected outputs)   
5. **Telemetry requirements** (logs that must exist + forwarding)   
6. **Drift signals** (what changes indicate drift)   
7. **Rollback plan** (what breaks and how to revert safely)   
8. **Owner & cadence** (who maintains and reviews)   
 --- 
   
# 12.4.2 Baseline Pack A — Windows Endpoint (User zone)   
## Objectives (top)   
- users are not local admins   
- Defender/EDR healthy + tamper protected   
- firewall enabled with correct profile behavior   
- risky scripting/macro paths constrained (per business)   
- logging sufficient for investigations (process + auth + persistence)   
- patch compliance in ringed deployment   
   
## Config items (typical)   
- local admin membership restricted (use LAPS for local admin)   
- Defender policy (cloud protection, tamper, ASR where feasible)   
- firewall profiles enforced; inbound deny-by-default with allowed exceptions   
- RDP off unless justified   
- application execution controls (allowlisting posture where feasible)   
   
## Verification queries (examples)   
- Local admin membership:   
    - `net localgroup administrators`   
- Defender health:   
    - PowerShell: `Get-MpComputerStatus` (key booleans)   
- Firewall:   
    - `Get-NetFirewallProfile`   
- Patch/build:   
    - `winver` / `Get-ComputerInfo`   
   
## Drift signals   
- new local admins   
- Defender exclusions added   
- firewall disabled or broad allow rules added   
- new remote management services enabled   
 --- 
   
# 12.4.3 Baseline Pack B — Windows Member Server (Server zone)   
## Objectives   
- admin access from PAWs only   
- management ports allowlisted   
- services run as least-privileged identities   
- SMB share permissions + NTFS permissions least privilege   
- logging: service/task/privilege changes visible and forwarded   
- patching in controlled windows   
   
## Verification queries (examples)   
- Allowed admin sources (network):   
    - firewall rules + network ACL evidence (SIEM)   
- Service inventory:   
    - `Get-Service`   
    - check service accounts for critical services   
- Shares:   
    - `Get-SmbShare`   
    - `icacls` on share paths   
   
## Drift signals   
- new services/tasks   
- new shares or permission changes   
- unexpected egress from servers   
- policy changes outside change window   
 --- 
   
# 12.4.4 Baseline Pack C — Domain Controllers (Tier-0)   
## Objectives   
- DCs only administered from PAWs/jumps   
- SMB signing and LDAP bind safety policies enforced   
- directory changes audited for sensitive objects   
- SYSVOL/NETLOGON integrity protected   
- logging and forwarding guaranteed (Tier-0 MES)   
- strict network exposure (only required ports from required zones)   
   
## Verification queries (examples)   
- “Privileged logons only from PAWs”   
    - SIEM correlation (DC logon source device ID/subnet)   
- LDAP bind policy:   
    - check DC policy settings; verify via Directory Service logs that offenders are identified   
- GPO integrity:   
    - validate GPC/GPT consistency monitoring exists   
- Network ports:   
    - firewall + upstream ACL compliance   
   
## Drift signals   
- AdminSDHolder or privileged group changes   
- changes to sensitive OU ACLs   
- changes to LDAP/SMB policies   
- unusual logon patterns to DCs   
 --- 
   
# 12.4.5 Baseline Pack D — PAW / Jump Hosts (Management zone)   
## Objectives   
- hardened, minimal software   
- no browsing/email if possible   
- strict egress: only management targets   
- strong logging + EDR posture   
- admin tool usage monitored   
   
## Verification queries   
- installed software inventory (minimal)   
- outbound connectivity checks (must-deny for general internet if policy requires)   
- EDR health check   
- ensure only admin users can log on   
   
## Drift signals   
- web browsing activity   
- new software installed   
- new outbound destinations   
 --- 
   
# 12.4.6 Baseline Pack E — Linux Server (Server zone)   
## Objectives   
- ssh hardening (keys, allowlists, bastion-only)   
- sudo least privilege + audited   
- service minimization + systemd sandboxing   
- secrets file hygiene (modes/owners)   
- audit watches on sensitive files + logs forwarded   
- patch compliance   
   
## Verification queries (examples)   
- SSH effective config:   
    - `sshd -T \| egrep "permitrootlogin\|passwordauthentication\|allowusers\|allowgroups\|x11forwarding\|allowtcpforwarding"`   
- Sudoers integrity:   
    - `visudo -c`   
    - `sudo -l` for admin roles (review)   
- Listening services:   
    - `ss -lntup`   
- Journal persistence:   
    - check `/var/log/journal` existence and journald config   
- Audit watches:   
    - check audit rules loaded + presence of events when test change performed   
   
## Drift signals   
- sshd\_config changes   
- sudoers changes   
- new services listening   
- new setuid binaries or capability changes   
- logging/forwarding stops   
 --- 
   
# 12.4.7 Baseline Pack F — Kubernetes Node (Linux + control-plane adjacency)   
## Objectives   
- minimal packages + services   
- runtime isolation defaults (drop caps, seccomp baseline where possible)   
- LSM enforcement (SELinux/AppArmor)   
- kubelet config protected   
- node logs forwarded (journald + container runtime)   
- admission and RBAC are enforced at cluster-level (node contributes telemetry)   
   
## Verification queries (examples)   
- Node services listening:   
    - `ss -lntup`   
- LSM status:   
    - `getenforce` / `aa-status`   
- cgroup and limits sanity checks   
- kubelet config permissions:   
    - file owner/mode verification   
   
## Drift signals   
- privileged workloads allowed unexpectedly (cluster-level drift)   
- node config changes   
- runtime changes (seccomp disabled, caps increased)   
- node-to-internet egress anomalies (if constrained)   
 --- 
   
# 12.4.8 Baseline Pack G — OT/ICS DMZ broker / gateway   
## Objectives   
- brokered remote access only (jump host)   
- strict allowlisted conduits IT↔OT   
- session recording and logs   
- minimal services   
- change control strictness   
   
## Verification queries (examples)   
- firewall rule compliance (must-deny)   
- jump host session logs present and retained   
- OT sensor visibility at boundary   
- remote access allowed only in approved windows   
   
## Drift signals   
- new conduits opened   
- vendor access outside windows   
- reduced logging at boundary   
 --- 
   
# 12.4.9 Verification queries: the “prove it pack” (how to standardize)   
Create a **role-specific verification script pack**:   
- Windows PowerShell checks   
- Linux shell checks   
- SIEM queries   
   
## Standardize outputs into:   
- PASS/FAIL   
- evidence pointers (log entries, screenshots, dashboard links)   
   
This is what makes audits and incident investigations fast.   
 --- 
# 12.4.10 Drift detection design (what to diff, how often, and how to alert)   
## A) What to diff (high signal items)   
### Windows   
- GPO versions (GPC/GPT)   
- local admin group membership   
- Defender policy/exclusions   
- firewall rules/profiles   
- services and scheduled tasks   
- sensitive registry keys (policy and autoruns)   
   
### Linux   
- sshd\_config and drop-ins   
- sudoers and sudoers.d   
- systemd unit files and overrides   
- crontabs   
- setuid files and capabilities   
- audit rules and forwarding config   
   
### Network & gateways   
- firewall ruleset diffs   
- proxy policy diffs   
- DNS resolver policy diffs   
- NAT policy changes   
   
### K8s (cluster-level)   
- RBAC bindings diffs   
- admission policy diffs   
- workload securityContext changes for privileged settings   
- audit policy diffs   
   
### OT boundary   
- conduit rule diffs   
- remote access policy diffs   
- session recording config diffs   
   
## B) Cadence (practical)   
- Critical Tier-0 and gateways: **daily**   
- Endpoints/servers: **daily/weekly** depending on scale   
- K8s policies: **continuous** (event-driven) + weekly review   
- OT conduits: **change-driven + weekly**   
   
## C) Alerting model   
- alert on drift in **critical controls**   
- ticket on drift in **non-critical controls**   
- dashboard trend for low-risk drift   
   
**Guardrail:** too many drift alerts causes ignoring; tune severity by role.   
 --- 
# 12.4.11 Exception handling (how baselines survive reality)   
Every exception must have:   
- owner   
- reason   
- compensating controls   
- expiry date   
- retest/recertification plan   
   
Your baseline program fails when exceptions become permanent and unowned.   
 --- 
## Deliverables you should create next   
1. Baseline packs (docs) for:   
    - Windows endpoint, Windows server, DC, PAW   
    - Linux server, K8s node   
    - OT DMZ broker (if applicable)   
2. Verification scripts/queries list for each pack   
3. Drift detection plan with:   
    - diff items + cadence + alert routing   
4. Exception register template   
 --- 
   
If you reply **next**, I’ll go to **Part 5/6: 12.5 Playbooks**   
(IR, access reviews, change/risk, red/purple exercise plans) — with:   
- playbook templates   
- decision trees   
- required evidence sources   
- and “closure criteria” so playbooks actually improve outcomes.   
   
next   
## Validation, Evidence, and “Provability” Outputs — Part 5/6   
# 12.5 Playbooks: IR, access reviews, change/risk, red/purple exercise plans   
## Templates + decision trees + evidence requirements + closure criteria   
Playbooks are where “security theory” becomes **operable**. A playbook must be:   
- executable under pressure,   
- tied to evidence sources,   
- and designed to close loops (fix + validate).   
   
We’ll define four playbook families:   
1. Incident Response (IR) playbooks   
2. Access review playbooks (identity governance)   
3. Change/risk playbooks (anti-drift)   
4. Red/purple exercise playbooks (measurement + improvement)   
 --- 
   
# 12.5.1 Universal playbook template (use for everything)   
Every playbook should follow the same structure:   
1. **Purpose / trigger** (what starts this playbook)   
2. **Scope** (systems/accounts/teams)   
3. **Roles & escalation** (IC, forensics, ops, comms)   
4. **Decision tree** (if/then gates, stop conditions)   
5. **Evidence to collect** (minimum set + where from)   
6. **Actions** (contain/eradicate/recover/validate)   
7. **Communications** (who to notify, when)   
8. **Closure criteria** (what proves done)   
9. **Post-action outputs** (tickets, detections, baseline updates)   
10. **Retest plan** (how to prove improvement)   
   
This uniformity is what makes teams fast.   
 --- 
# 12.5.2 IR Playbooks (tiered by asset and blast radius)   
## IR-1: Endpoint compromise (user endpoint)   
### Trigger   
- EDR alert (malicious execution / suspicious process chain)   
- suspicious logon activity linked to endpoint   
   
### Decision tree (core gates)   
1. Is there evidence of credential theft or privileged token use?   
2. Did the endpoint access server/DC management surfaces? (contract violation)   
3. Is there persistence on the endpoint? (tasks/services/autoruns)   
4. Is there data exfil evidence? (proxy/DNS/egress logs)   
   
### Evidence (minimum)   
- EDR process tree + network connections   
- recent auth events for the user/device   
- firewall/proxy/DNS logs for suspicious destinations   
- persistence indicators (services/tasks/autoruns) snapshot   
   
### Actions   
- isolate endpoint (EDR isolate)   
- reset credentials if risk threshold met   
- collect minimal forensic artifacts (as defined)   
- eradicate (remove persistence, reimage if needed)   
- recover (rejoin domain, baseline reapply)   
- validate (retest that endpoint is compliant)   
   
### Closure criteria   
- endpoint clean/rebuilt   
- credentials rotated where required   
- detection gaps addressed (if any)   
- baseline drift closed   
 --- 
   
## IR-2: Server compromise   
### Trigger   
- new service/task created   
- unusual admin logon   
- EDR alert on server   
   
### Decision tree   
1. Is server Tier-0 adjacent (domain join role, privileged service)?   
2. Do we see lateral movement attempts?   
3. Are secrets (service creds, API keys) at risk?   
   
### Evidence   
- EDR telemetry + process/network   
- service/task creation evidence   
- auth logs (who logged on, from where)   
- file/share access logs (if relevant)   
   
### Actions   
- isolate or segment server (depending on availability needs)   
- rotate secrets used by that server   
- eradicate persistence   
- patch misconfig/vuln   
- recover with integrity checks (baseline reapply + monitoring)   
   
### Closure criteria   
- no persistence remains   
- server baseline revalidated   
- control gaps ticketed and scheduled   
 --- 
   
## IR-3: Tier-0 / AD compromise (SEV-0 discipline)   
### Trigger   
- privileged group changes   
- nTSecurityDescriptor changes on sensitive objects   
- suspicious DC logons   
- GPO tampering signals   
   
### Decision tree (fast)   
1. Are Tier-0 creds suspected compromised?   
2. Are DCs showing suspicious service/task changes?   
3. Is SYSVOL/GPO integrity affected?   
4. Is containment safe without breaking auth?   
   
### Evidence   
- DC logs: auth + directory changes   
- GPO change evidence (GPC/GPT)   
- network logs around DCs   
- EDR data for DCs/jumps   
   
### Actions (high level)   
- contain: restrict Tier-0 admin paths to PAWs only   
- rotate privileged creds and key service accounts according to tiered plan   
- validate directory state and permissions   
- recover with strict integrity: rebuild if needed, re-issue trust material if required   
   
### Closure criteria   
- Tier-0 trust restored and proven   
- admin paths constrained and monitored   
- retest purple emulations pass   
 --- 
   
## IR-4: Kubernetes incident (control plane or workload)   
### Trigger   
- unusual K8s API activity in audit logs   
- privileged workload created unexpectedly   
- secrets accessed unexpectedly   
   
### Decision tree   
1. Is API server/etcd/control plane affected?   
2. Is it limited to a namespace?   
3. Are service account tokens/secrets exposed?   
   
### Evidence   
- K8s audit logs (who did what)   
- workload specs (securityContext, host\* flags)   
- node logs (runtime/kubelet)   
- registry/image provenance metadata   
   
### Actions   
- quarantine namespace/workloads   
- rotate impacted secrets and service account tokens   
- tighten RBAC and admission policies   
- validate cluster posture and retest policies   
   
### Closure criteria   
- offending resources removed   
- RBAC/admission fixes deployed   
- audit visibility validated   
 --- 
   
## IR-5: OT boundary incident (ICS DMZ)   
### Trigger   
- remote access outside window   
- new IT↔OT flows   
- OT sensor alerts   
   
### Decision tree   
1. Is safety impacted? (ops liaison gate)   
2. Is flow violating known conduits?   
3. Is vendor access involved?   
   
### Evidence   
- firewall logs, jump session logs, OT sensor data   
- change control records   
   
### Actions   
- contain by conduit closure (brokered access only)   
- coordinate with operations for any system changes   
- verify remote access workflows and recertify   
   
### Closure criteria   
- conduits restored to contract   
- session policy enforced + monitoring verified   
 --- 
   
# 12.5.3 Access review playbooks (identity governance that prevents breaches)   
## AR-1: Privileged group membership review (AD + local admins)   
### Trigger   
- scheduled monthly review or change event   
   
### Evidence   
- group membership snapshots   
- change logs (who added/removed)   
- justification tickets   
   
### Actions   
- remove unneeded members   
- enforce role-based groups   
- validate PAW-only admin policy   
- recertify exceptions   
   
### Closure criteria   
- membership matches approved roster   
- drift alerts tuned and active   
   
## AR-2: Delegation/ACL review (OUs and sensitive objects)   
### Trigger   
- quarterly review or after incident/red team   
   
### Evidence   
- ACL exports on sensitive OUs/objects   
- AdminSDHolder integrity checks   
- change logs for SD changes   
   
### Actions   
- remove dangerous rights (WRITE\_DAC/WRITE\_OWNER equivalents)   
- tighten delegation model   
- validate with lab emulations   
   
### Closure criteria   
- least-privilege delegation proven   
- directory change monitoring alerts on future drift   
 --- 
   
# 12.5.4 Change/risk playbooks (anti-drift, stable security)   
## CR-1: Emergency exception playbook   
### Trigger   
- “we must open port / disable control / add exclusion now”   
   
### Decision tree   
1. Is this Tier-0 adjacent? If yes, require higher approval.   
2. Can we add compensating controls?   
3. Can we time-box it?   
   
### Required fields   
- business owner   
- reason   
- compensating controls   
- expiry date   
- rollback plan   
- monitoring plan   
   
### Closure criteria   
- exception removed by expiry   
- baseline restored and verified   
   
## CR-2: Baseline update playbook   
### Trigger   
- new business requirement or incident-driven change   
   
### Actions   
- update baseline spec   
- test in pilot ring/lab   
- deploy gradually   
- validate telemetry and drift rules   
   
### Closure criteria   
- baseline applied across scope   
- monitoring shows compliance   
- no unacceptable operational breakage   
 --- 
   
# 12.5.5 Red/Purple exercise playbooks (repeatable validation)   
## PR-1: Assumed breach exercise plan   
### Trigger   
- scheduled monthly/quarterly exercise   
   
### Steps   
- define starting condition (e.g., compromised endpoint)   
- define objectives (containment, detection, validation)   
- run safe emulations (no destructive steps)   
- record TTD/TTri/TTC   
- create gap tickets   
- retest   
   
### Closure criteria   
- gaps ticketed with owners   
- at least one retest confirms improvement   
   
## PR-2: Protocol posture validation (from your 11.x lab track)   
### Trigger   
- monthly/after policy change   
   
### Steps   
- replay pcap stories (SMB/LDAP/Kerb/HTTP)   
- ensure invariants pass   
- ensure detections fire on anomaly stories   
- ensure dashboards show stable posture   
   
### Closure criteria   
- posture drift detected quickly   
- policy changes don’t silently weaken security   
 --- 
   
# 12.5.6 What makes playbooks “provable”   
Every playbook must link to:   
- evidence sources (exact logs/dashboards)   
- validation tests (must-deny/must-allow)   
- closure criteria that can be checked objectively   
   
**No “closed” status without proof.**   
 --- 
## Deliverables you should create now (concrete)   
1. 5 IR playbooks (endpoint/server/tier0/k8s/ot)   
2. 2 access review playbooks (priv groups + delegation/ACLs)   
3. 2 change/risk playbooks (emergency exceptions + baseline updates)   
4. 2 purple playbooks (assumed breach + protocol posture validation)   
 --- 
   
If you reply **next**, I’ll finish this module with **Part 6/6: 12.6 Portfolio of safe labs**   
Reproducible, legal labs with measurable outcomes — and I’ll give you:   
- a portfolio structure   
- 12–20 lab ideas mapped to your Windows/AD, Linux/K8s, protocol research, and detection engineering tracks   
- success criteria and what artifacts prove mastery without unsafe behavior   
   
next   
## Validation, Evidence, and “Provability” Outputs — Part 6/6   
# 12.6 Portfolio of safe labs (reproducible, legal, measurable outcomes)   
## A complete lab portfolio structure + 20 flagship labs mapped to your journey   
Your portfolio should prove you can do **security engineering + offensive literacy + detection/DFIR** without doing anything unsafe or illegal. The key is: every lab produces **artifacts** (pcaps/logs/config diffs) and **metrics** (TTD/TTri/TTC, drift detection, compliance).   
 --- 
# 12.6.1 Portfolio structure (how to present it like a professional)   
Create a repo/vault with this top-level:   
- `README.md` (who you are, what you built, what it proves)   
- `labs/` (each lab folder is self-contained)   
- `artifacts/` (shared diagrams, matrices, baselines)   
- `detections/` (SIEM rules, normalized schemas, dashboards)   
- `playbooks/` (IR + purple plans)   
- `evidence/` (sanitized screenshots, pcap excerpts, log samples)   
- `automation/` (verification scripts, drift checks)   
- `CHANGELOG.md` (what you improved and why)   
   
Each lab folder must include:   
- `objective.md`   
- `topology.png` or ascii diagram   
- `steps.md`   
- `expected\_artifacts.md`   
- `results.md` (what happened + metrics)   
- `cleanup.md`   
- `pcaps/` and `logs/` (sanitized)   
- `validation.md` (pass/fail checks + retest)   
 --- 
   
# 12.6.2 The lab quality bar (what makes a lab “portfolio-grade”)   
### A) Reproducible   
- exact versions/configs recorded   
- deterministic steps   
- can be rerun after months   
   
### B) Safe   
- no destructive actions   
- no real target systems   
- synthetic data or clearly approved test data only   
   
### C) Measurable   
- success criteria are explicit   
- outputs include metrics:   
    - detection timings (TTD/TTTri/TTC)   
    - compliance/diff results   
    - error rates / posture ratios (e.g., SMB signing ratio)   
   
### D) Evidence-backed   
- includes:   
    - pcap(s)   
    - log excerpts   
    - config diffs   
    - SIEM query outputs   
    - screenshots (optional)   
   
### E) Defensive closure included   
- every lab ends with:   
    - “what control failed/succeeded”   
    - “what detection should exist”   
    - “how to retest”   
 --- 
   
# 12.6.3 Flagship lab set (20 labs, mapped to your tracks)   
## Windows / AD (Platform mastery + identity internals)   
### Lab 1 — Windows Token Split (UAC) Proof Lab   
- Objective: compare filtered vs elevated token differences   
- Artifacts: `whoami /all` snapshots, integrity level differences   
- Metric: “which operations fail at Medium and succeed at High”   
   
### Lab 2 — NTFS Boundary Lab (DACL/SACL + inheritance)   
- Objective: demonstrate explicit vs inherited ACE outcomes   
- Artifacts: `icacls` outputs, SACL audit events for selected path   
- Metric: audit signal volume + correct event capture   
   
### Lab 3 — “Protected Objects” AD Lab (AdminSDHolder concept proof)   
- Objective: show how protected objects behave differently (in lab domain)   
- Artifacts: SD snapshots before/after, directory change logs   
- Metric: drift detection catches unexpected SD changes   
   
### Lab 4 — GPO Internals Lab (GPC vs GPT + processing order)   
- Objective: demonstrate a policy change and prove where it lives (AD object vs SYSVOL)   
- Artifacts: GPC attributes, SYSVOL file diff, client refresh evidence   
- Metric: time-to-apply + correctness proof   
   
### Lab 5 — LDAP Signing/Channel Safety Lab (migration-style)   
- Objective: identify and classify “insecure bind” attempts in lab   
- Artifacts: Directory Service logs + client behavior traces   
- Metric: offender identification accuracy + remediation validation   
 --- 
   
## Linux (Security engineering + isolation)   
### Lab 6 — DAC/ACL Mask Gotcha Lab   
- Objective: prove ACL mask limiting named entries   
- Artifacts: `getfacl` before/after, access attempts results   
- Metric: pass/fail matrix for each principal   
   
### Lab 7 — Capabilities vs setuid Lab   
- Objective: show a service can bind low ports with cap, not root   
- Artifacts: `getcap`, service config, proof of least privilege   
- Metric: capability set minimized without breaking function   
   
### Lab 8 — systemd Hardening Lab   
- Objective: apply sandbox directives and measure reduced access   
- Artifacts: unit file overrides, service behavior, denial logs (if any)   
- Metric: preserved availability + reduced access footprint   
   
### Lab 9 — SELinux/AppArmor Confinement Lab   
- Objective: show MAC blocking an action that DAC would allow   
- Artifacts: denial logs, policy/profile snippets, before/after access   
- Metric: confirmed enforcement + minimal false positives   
   
### Lab 10 — Linux Audit Watch Lab (sudoers/sshd integrity)   
- Objective: prove audit catches changes to sensitive files   
- Artifacts: audit logs, journald logs, alert rule proof   
- Metric: detection latency to SIEM (TTD)   
 --- 
   
## Enterprise protocols (your protocol research track)   
### Lab 11 — SMB Auth & Signing Posture Lab   
- Objective: capture NEGOTIATE/SESSION\_SETUP and validate signing/encryption posture   
- Artifacts: pcap, extractor CSV, posture dashboard snapshot   
- Metric: signing ratio stable; drift triggers alert   
   
### Lab 12 — LDAP StartTLS + SASL GSSAPI Lab   
- Objective: demonstrate bind transitions and prove secure bind enforcement   
- Artifacts: pcap, bind type extraction, server logs   
- Metric: insecure bind attempts are detected/blocked as designed   
   
### Lab 13 — Kerberos Failure Reason Lab (time skew)   
- Objective: generate controlled Kerberos error due to skew and detect it   
- Artifacts: pcap + logs showing error reason   
- Metric: detection rule catches skew early (before outages)   
   
### Lab 14 — HTTP Negotiate 401 Loop Lab   
- Objective: reproduce a misconfig loop and detect it as anomaly   
- Artifacts: pcap, http header extraction, SIEM alert   
- Metric: threshold tuning yields high precision   
   
### Lab 15 — Proxy Boundary Integrity Lab (Forwarded headers)   
- Objective: prove the gateway strips/sets authoritative forwarded identity   
- Artifacts: request header captures at edge vs origin, gateway config evidence   
- Metric: origin never trusts client-supplied forwarded headers   
 --- 
   
## Detection Engineering + DFIR (closing the loop)   
### Lab 16 — Minimum Evidence Set (MES) Validation Suite   
- Objective: generate known events across Windows/Linux and confirm ingestion + parsing   
- Artifacts: SIEM queries, parse success rates, “heartbeat” dashboards   
- Metric: ingestion SLA met; missing sources detected   
   
### Lab 17 — “Contract violation” detection lab (user zone → management port)   
- Objective: must-deny test and ensure firewall logs + SIEM rule fire   
- Artifacts: firewall deny log + SIEM alert   
- Metric: TTD < target; false positives low   
   
### Lab 18 — IR Drill: endpoint compromise simulation (non-destructive)   
- Objective: run IR-1 playbook end-to-end in lab   
- Artifacts: case timeline, evidence pack, containment proof   
- Metric: TTC achieved; lessons learned outputs created   
   
### Lab 19 — Purple emulation cycle (detect→harden→retest)   
- Objective: demonstrate improvement by tuning one rule/control and retesting   
- Artifacts: before/after metrics, updated rule, retest proof   
- Metric: recall improves without precision collapse   
   
### Lab 20 — “Drift detection” lab for baselines   
- Objective: intentionally introduce baseline drift (safe) and catch it   
- Artifacts: config diff, drift alert, remediation proof   
- Metric: drift detected within SLA; exception workflow works   
 --- 
   
# 12.6.4 How to score your portfolio (self-assessment rubric)   
For each lab, score 0–3 on:   
1. **Reproducibility**   
2. **Evidence completeness**   
3. **Measurement clarity**   
4. **Defensive closure**   
5. **Operational realism** (aligned to enterprise constraints)   
   
A “professional-grade” lab is ≥12/15.   
 --- 
# 12.6.5 The “portfolio narrative” (how to present it to employers/leadership)   
Your README should tell a story:   
- I built **architecture artifacts** (trust boundaries + segmentation contracts)   
- I built **baseline packs** with drift detection   
- I built **protocol posture verification** using pcaps and regression tests   
- I built **SIEM normalization + high-precision detections**   
- I built **IR playbooks** and validated them in drills   
- I run a **purple loop** and can show metrics improvements over time   
   
That narrative is rare and signals senior-level capability.   
   

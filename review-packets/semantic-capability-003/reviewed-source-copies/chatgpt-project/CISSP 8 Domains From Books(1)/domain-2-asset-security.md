---
# yaml-language-server: $schema=schemas\page.schema.json
Object type:
    - Page
Backlinks:
    - Books-Summary = CISSP 8-Domain (References)
Creation date: "2026-02-25T19:51:38Z"
Created by:
    - Perky Sparrow
id: bafyreidlf3workubywbf42o6ow6rqhyfav3eiboghkj5qw5iokbgxklu3q
---
# Domain 2-Asset Security    
> (classification + ownership + privacy + retention + data states controls + handling + sanitization)   
>    

> Domain 2 — Asset Security (Deep Dive)   

## 0) What Domain 2 is really doing in a mature enterprise   
Think of Domain 2 as **“data governance + data protection engineering.”** It’s the discipline of ensuring:   
- You know **what data/assets you have**   
- You know **who owns them**   
- You apply protection that’s **proportional to value/sensitivity/criticality**   
- You can prove correct handling across the entire **information lifecycle** (create → use → share → store → archive → dispose)   
   
Both OSG7 and AIO describe the same domain scope: **classification, ownership, privacy, retention, data security controls, and handling requirements**.
CISSP - All In One Exam Guide -…   
CISSP - Official Study Guide - …   
 --- 
## A) Classify information and supporting assets (sensitivity, criticality)   
### A.1 Why classification exists (beyond “labels”)   
OSG7 notes classification is often required for **regulatory/legal compliance**, and it helps define **access levels, authorized uses, and declassification/destruction**, and supports **data life-cycle management (retention, usage, destruction)**.
CISSP - Official Study Guide - …   
**Professional translation:**   
- Classification is not a sticker; it’s a **control-routing mechanism**.   
- It decides:   
    - who can access the data,   
    - where it may be stored (cloud region? laptop? removable media?),   
    - how it must be transmitted (TLS? VPN? encrypted email?),   
    - how it must be logged/monitored (DLP? audit trail?),   
    - how long it must be kept,   
    - and how it must be destroyed.   
   
### A.2 Classification criteria (how you decide sensitivity/criticality)   
OSG7 gives a rich “criteria set” that serious programs actually use, including: usefulness, timeliness, value/cost, age/maturity, lifetime/expiration, association with personnel, disclosure/modification damage assessment, national security implications, authorized access/restrictions, maintenance/monitoring, and storage requirements.
CISSP - Official Study Guide - …   
**Expert approach:** treat this like a scoring model:   
- **Impact-of-disclosure** (confidentiality harm)   
- **Impact-of-modification** (integrity harm)   
- **Impact-of-loss/unavailability** (availability/operational harm)   
- **Legal/regulatory impact**   
- **Business value + competitive impact**   
- **Time value** (does it “cool down” and become declassifiable?)   
   
### A.3 Government vs commercial classification (and why enterprises use “hybrids”)   
OSG7 explicitly states the two common schemes are **government/military** and **commercial/private sector**, and shows government levels like *Top Secret, Secret, Confidential, Unclassified* (with “Top Secret” highest).
CISSP - Official Study Guide - …   
CBK4 adds practical guidance for commercial programs:   
- Definitions should have **little overlap** (to reduce confusion)   
- Titles should make it obvious what belongs there, e.g.:   
    - **Private** (SSNs, bank accounts, credit cards)   
    - **Company Restricted**   
    - **Company Confidential**   
    - **Public**
Official Guide To CISSP CBK - 4…   
   
**Professional reality:** most enterprises use a hybrid like:   
- Public   
- Internal   
- Confidential   
- Restricted (or Highly Confidential)   
   
…and *then* add “data types” (PII, PCI, PHI, secrets, source code) as tags that trigger extra controls.   
### A.4 “Data classification begets system classification”   
A key “above-CISSP” point: you don’t only classify the data—you classify **the systems that store/process/transmit it**. CISSP For Dummies explicitly calls this out and gives PCI as an example: systems in scope for PCI require additional safeguards compared to others.
CISSP For Dummies - 6th Edition   
This is exactly how mature environments do it:   
- **System classification = max(data classification handled by system)** + exposure (internet-facing?) + privilege (admin plane?).   
- Your baseline hardening, logging, monitoring, and change-control rigor scales with system classification.   
 --- 
   
## A.5 Implementing classification correctly (the 7-phase model)   
OSG7 gives a **seven-step implementation** that maps cleanly to real enterprise rollouts.
CISSP - Official Study Guide - …   
### Phase 1 — Identify the custodian; define responsibilities   
This forces the question: “Who runs the controls day-to-day?” (We’ll define custodian properly in the ownership section.)   
**What “good” looks like:**   
- custodian is accountable for storage controls, backup, access enforcement, logging   
- has runbooks and KPIs (e.g., access review completion, encryption coverage)   
   
### Phase 2 — Specify evaluation criteria (your classification rubric)   
This is where you build the criteria list (value, lifetime, disclosure harm, etc.) into a usable decision guide.   
**Expert move:** encode criteria into workflows:   
- data catalog intake form   
- SDLC data classification step   
- procurement/vendor intake (what data will be shared?)   
   
### Phase 3 — Classify and label each resource (owner does; supervisor reviews)   
OSG7 says the **owner** classifies and labels, with review.
CISSP - Official Study Guide - …   
**Why review matters:** owners often over-classify or under-classify. Review reduces “label drift.”   
### Phase 4 — Document exceptions; integrate them into criteria   
This is “policy reality.” Exceptions are inevitable (legacy apps, operational constraints). If you don’t formalize them, you end up with silent noncompliance.   
### Phase 5 — Select security controls per classification   
This is the critical bridge: classification must map to controls (encryption, DLP, access approvals, logging).   
CBK4 gives a concrete example: classifying credit card data as private helps drive PCI DSS requirements like encryption.
Official Guide To CISSP CBK - 4…   
### Phase 6 — Declassify + transfer custody procedures   
OSG7 emphasizes declassification is often overlooked and warns that failure to declassify wastes resources and degrades the meaning/value of higher sensitivity levels.
CISSP - Official Study Guide - …   
**Professional rule:** define triggers like:   
- time-based (after product launch)   
- event-based (after contract ends)   
- lifecycle-based (after customer account closure)   
- legal-based (after retention minimum expires)   
   
### Phase 7 — Awareness program   
Classification fails if staff don’t understand:   
- how to classify,   
- how to handle,   
- how to destroy.   
   
That’s why OSG7 makes awareness part of classification deployment.
CISSP - Official Study Guide - …   
 --- 
## B) Determine and maintain ownership (data owners, system owners, business/mission owners)   
OSG7 explicitly calls out “determine and maintain ownership” as a core domain requirement (data owners, system owners, business/mission owners).
CISSP - Official Study Guide - …   
### B.1 Data owner (the one who makes classification and risk decisions)   
CBK4: **the data owner decides classification** because they best understand the data’s use and value.
Official Guide To CISSP CBK - 4…   
CISSP For Dummies: the data owner has **ultimate responsibility** for data security; the custodian does day-to-day administration.
CISSP For Dummies - 6th Edition   
**What data owners do in real programs:**   
- decide classification and required controls   
- approve access policies (who gets access, under what conditions)   
- decide retention requirements (often with Legal/Compliance)   
- sponsor data quality and integrity requirements (what “correct” means)   
- review classification periodically (CBK4 suggests at least annually).
Official Guide To CISSP CBK - 4…   
   
### B.2 Data custodian / processor (the operator)   
Custodian is the function/team that:   
- implements access controls   
- enforces storage rules   
- manages backups   
- runs the platform securely   
- applies approved labeling/handling workflows   
   
This division is explicit: owner = accountable and decides; custodian = administers daily security controls.
CISSP For Dummies - 6th Edition   
### B.3 System owner vs business/mission owner   
In practice:   
- **Business/mission owner** owns the *process outcome* (e.g., “payroll,” “card payments,” “identity platform”).   
- **System owner** owns a specific system’s *operation and control posture* (config, patching, availability controls, logging).   
- **Data owner** owns the information itself (classification, use constraints, retention, privacy).   
   
If you collapse these roles into one, you usually get either:   
- business decisions made with no technical feasibility awareness, or   
- technical decisions made with no legal/business accountability.   
 --- 
   
## C) Information lifecycle (why “retention + disposal” must be designed early)   
Even before we dive into retention policy details, understand this: OSG7 links classification directly to lifecycle management (retention/usage/destruction).
CISSP - Official Study Guide - …   
AIO8 reinforces this lifecycle concept: it treats disposal as a lifecycle phase commonly triggered by **data retention policies** (not “when storage is low”).
CISSP - All In One Exam Guide -…   
**Expert mental model:** every dataset has:   
- an entry point (collection/creation),   
- a use purpose,   
- allowed sharing rules,   
- storage/archival states,   
- and an end-of-life condition (disposal).   
 --- 
   
## D) Asset management and inventory (the prerequisite you can’t skip)   
CBK4 explicitly frames **inventory management** as capturing what assets exist, where they are, and who owns them.
Official Guide To CISSP CBK - 4…   
**Professional expansion:** you need *two inventories* that link together:   
1. **Data inventory / catalog** (datasets, data types, owners, classification, flows)   
2. **System inventory / CMDB** (systems, environments, owners, criticality, where data lives)   
   
If you only track systems but not data, privacy and retention become guesswork. If you only track data but not systems, protection controls can’t be verified.   
# C) Protect privacy   
The OSG7 Domain 2 blueprint breaks privacy into: **data owners**, **data processors**, **data remanence**, and **collection limitation**.
CISSP - Official Study Guide - …   
## C.1 Data owners and “privacy controller” thinking   
OSG7 defines **PII** and emphasizes the obligation to protect it (employees and customers) and ties it to breach notification requirements.
CISSP - Official Study Guide - …   
**Professional translation:** the *data owner* is the one who decides (directly or via policy) the “rules of the road” for personal data: what it is, why it exists, who may touch it, where it may flow, and when it must die. (This is very close to the GDPR concept of “controller,” though CISSP uses broader org-role language.)   
A practical, enterprise-grade “data owner privacy responsibilities” checklist:   
- **Define allowed purposes** (what business process is legitimate).   
- Approve **lawful basis** / justification (contract, legal obligation, consent, etc. where applicable).   
- Approve **data sharing boundaries** (vendors, affiliates, cross-border transfers).   
- Set **retention** (minimum required, maximum permitted) and deletion triggers.   
- Require **auditability** (who accessed what, and why).   
- Approve **exceptions** (time-bounded, compensating controls, tracked).   
   
## C.2 Data processors: training + auditing are the real privacy controls   
The All-in-One (7th ed.) points out the people most positioned to protect or compromise privacy are the ones who handle the data daily (“data processers”), and the key controls are **training and auditing**—they must know acceptable behavior *and* be routinely checked.
CISSP - All In One Exam Guide -…   
**This matters because privacy violations are often “policy drift,” not hacking:**   
- employees emailing spreadsheets to personal accounts   
- analysts exporting “just one” dataset   
- support teams taking screenshots with PII/PHI   
- vendors receiving too much data “because it’s easier”   
   
**Operating model that actually works:**   
- Role-based training (HR, support, finance, engineering) on *exactly* what they can’t do.   
- Routine audits / sampling reviews for high-risk workflows (exports, mass queries, admin actions).   
   
## C.3 Data remanence: why “delete” is almost never deletion   
All-in-One explains the core remanence problem: deletion usually just marks space as available; the bytes remain recoverable (file systems and databases behave this way), so privacy can fail even if policies exist.
CISSP - All In One Exam Guide -…   
This is why Domain 2 treats privacy and disposal as one system: privacy includes **end-of-life correctness**, not just “access controls while alive.”   
## C.4 Collection limitation: minimize what you collect, and justify everything you keep   
Modern privacy regimes make “collection limitation” and “storage limitation” central. GDPR’s Article 5 principles include **purpose limitation**, **data minimization**, and **storage limitation** (among others), i.e., collect for explicit purposes, collect only what you need, and keep it no longer than necessary.   
California’s CCPA/CPRA framing is similar operationally: consumers can ask what you collected and why, request deletion (with exceptions), opt out of sale/sharing, correct inaccuracies, and limit use/disclosure of sensitive personal information.   
**Practical “collection limitation” controls (what you implement):**   
- **Data mapping**: where PII exists, where it flows, who receives it.   
- **Field-level minimization**: don’t store SSN/birthdate unless required; don’t log secrets; avoid “free text” where PII creeps in.   
- **Default retention caps**: design systems so data auto-expires unless explicitly extended under policy.   
- **Purpose-bound access**: “support can view, not export”; “analytics uses de-identified dataset”; etc.   
 --- 
   
# D) Ensure appropriate retention (media, hardware, personnel)   
All-in-One (8th ed.) makes two points that are *exactly* how real programs run:   
- disposal is commonly triggered by **data retention policies**
CISSP - All In One Exam Guide -…   
- retention policy mu
CISSP - All In One Exam Guide -…   
    CISSP - All In One Exam Guide -…   
   
## D.1 Retention is   
CISSP - All In One Exam Guide -…
ers four questions:   
1. **What** data must we keep?   
2. **Why** must we keep it (legal/regulatory/contract/business value)?   
3. **How long** is the minimum and maximum?   
4. **What is the disposal method** (clear/purge/destroy; archive vs delete)?   
   
**Security consequences of bad retention:**   
- Over-retention increases breach impact (more records exposed).   
- Under-retention increases legal/regulatory risk and breaks investigations.   
- Uncontrolled retention breaks privacy rights workflows (you can’t delete what you can’t find).   
   
## D.2 Retention must account for media lifecycle and “ability to read it”   
All-in-One (7th ed.) explicitly warns that all media should have a conservative expected lifespan; if the information must outlive the media, it must be migrated, and even the availability of hardware to read a format matters (stable media is useless if nothing can read it).
CISSP - All In One Exam Guide -…   
That single point is
CISSP - All In One Exam Guide -…
*preservation*\* (integrity + readability + chain of custody)   
- for regulated archives, you may keep cryptographic signatures and re-verify periodically to ensure integrity over decades
CISSP - All In One Exam Guide -…   
   
## D.3 Retention a   
CISSP - All In One Exam Guide -…
cribes archiving as moving data from expensive, ready access to long-term stable formats with lower storage costs—often required when law/regulation demands retention beyond business usefulness.
CISSP - All In One Exam Guide -…   
\*\*Security archite
CISSP - All In One Exam Guide -…
ghter access (few operators)   
- immutability/WORM where appropriate   
- strong logging for access and export   
- periodic integrity checks   
 --- 
   
# E) Determine data security controls (data at rest, in transit, in use)   
OSG7 explicitly says you must protect data in three states—**at rest, in transit, and in use**—and ties “best confidentiality protection” to encryption plus strong authentication/authorization.
CISSP - Official Study Guide - …   
## E.0 Data states:   
CISSP - Official Study Guide - …
t rest\*\*: stored on media (drives, USB, SAN, backup tapes)
CISSP - Official Study Guide - …   
- **In transit**: tra
CISSP - Official Study Guide - …   
    CISSP - Official Study Guide - …   
- **In use**: in temp
CISSP - Official Study Guide - …   
    CISSP - Official Study Guide - …   
   
All-in-One (7th ed.)
CISSP - Official Study Guide - …
ncrypted (it must be decrypted to be processed), and that creates exposure to attacks including side-channel/volatile-memory style issues.
CISSP - All In One Exam Guide -…   
### What an expert   
CISSP - All In One Exam Guide -…
build a *state-by-state control stack*:   
- **At rest**: full-disk/volume/db/object encryption + key management + access control   
- **In transit**: TLS/IPsec/VPN + mutual auth where needed + downgrade protection   
- **In use**: isolate processes, minimize plaintext exposure, restrict debugging/admin access, protect memory on endpoints, and add DLP/monitoring   
   
## E.1 Baselines: preselected sets of controls that you don’t reinvent   
OSG7 uses the NIST baseline concept (illustratively) and shows that baselines include “basic security practices” such as access control policy, account management, least privilege, separation of duties, etc.
CISSP - Official Study Guide - …   
\*\*Professional move:
CISSP - Official Study Guide - …
andard user)   
- privileged admin workstation baseline   
- server baseline (prod vs non-prod)   
- “regulated data system” baseline (PII/PCI/PHI)   
- backup/archival baseline   
   
## E.2 Scoping and tailoring: how baselines fit real systems   
OSG7 defines:   
- **Scoping**: select only baseline controls that apply (don’t apply nonsense controls)
CISSP - Official Study Guide - …   
- **Tailoring**: modi
CISSP - Official Study Guide - …
here needed
CISSP - Official Study Guide - …   
   
This is where many o
CISSP - Official Study Guide - …
ailor it to cloud, remote offices, OT, etc.   
## E.3 Standards selection: external requirements constrain your controls   
OSG7 notes you must select controls that comply with external standards like PCI DSS (credit card processing). It also references older EU transfer standards (“Safe Harbor”) in that edition
CISSP - Official Study Guide - …
—but modern cross-bord
CISSP - Official Study Guide - …
n (important in real work):\*\*   
- EU-US Safe Harbor was invalidated by the CJEU in 2015 (Schrems I).   
- EU-US Privacy Shield was invalidated in 2020 (Schrems II).   
- The European Commission adopted an adequacy decision for the EU-US Data Privacy Framework in July 2023.   
- In September 2025, the EU’s General Court upheld the 2023 framework (per Reuters reporting), though legal challenges may continue.   
   
That’s the “expert posture”: you treat standards as living constraints, not static trivia.   
## E.4 Cryptography: where it belongs in Asset Security   
Even though crypto is “Domain 3,” Domain 2 uses crypto as a **data-state control**:   
- OSG7 example shows classification-driven security requirements like labeling + encrypting sensitive email, with strong algorithms such as AES-256 as an example of “strong encryption.”
CISSP - Official Study Guide - …   
   
**Expert takeaway:** crypto isn’t a checkbox; it’s a system:   
- key ownership model (who can decrypt)   
- rotation, revocation, escrow policy (if any)   
- backup encryption (keys must survive restoration)   
- vendor/cloud boundary (bring-your-own-key vs provider-managed key)   
   
## E.5 DLP as the “policy enforcement layer” across data states   
All-in-One (8th ed.) explains why endpoint DLP can observe data when it’s decrypted “in use,” d
CISSP - Official Study Guide - …
attempts; it also notes EDLP complexity vs network DLP, and that hybrid gives best coverage at higher cost/complexity.
CISSP - All In One Exam Guide -…   
**Professional pattern:**   
- Use **classification tags** + DLP to enforce “can’t send outside org,” “can’t print,” “can’t copy/paste,” etc. (exactly like OSG7’s labeled-email example pipeline).
CISSP - Official Study Guide - …   
 --- 
   
# F) Establish handling requirements (markings, labels, storage, destruction)   
OSG7 is explicit that once you classify, you must consistently \*\*mark, handle, store, and
CISSP - All In One Exam Guide -…
ractices” that prevent major losses.
CISSP - Official Study Guide - …   
## F.1 Marking and labeling (physical + logical)   
OSG7: mark as soon as possible; even systems
CISSP - Official Study Guide - …
g what classification they process.
CISSP - Official Study Guide - …   
All-in-One (8th ed.) reinforces that each classification must have its own handling and destruction requirements, and that assets (laptops/drives) should inherit the highest cl
CISSP - Official Study Guide - …   
CISSP - All In One Exam Guide -…   
**Expert implementation details:**   
- **Logical labeling**: metadata tags in documents, email classification banners, DLP-recognized headers.   
- \*\*Physical l
CISSP - Official Study Guide - …
re asset tags.   
- **Inheritance rule**: device classification = max classification of contents (unless you can prove compartmentalization).   
   
## F.2 Handling (transport) = preserve the same protection level during m   
CISSP - All In One Exam Guide -…
same protection during transport as during storage; this applies to offsite backups and also to network transmission (encrypt before sending).
CISSP - Official Study Guide - …   
**Enterprise reality: “handling failures” are common**   
- unmarked backups shipped to warehouses   
- contractors carrying drives   
- emailing “just this file” to external recipients   
   
So handling requirements typically include:   
- approved couriers + chain-of-custody logs   
- tamper-evident containers   
- encryption *before* movement (don’t rely on “secure truck”)   
- documented receipt + inventory reconciliation   
   
## F.3 Storage requiremen   
CISSP - Official Study Guide - …
es onsite/offsite backups, physical security protections against theft, and environmental controls against corruption/loss.
CISSP - Official Study Guide - …   
**Expert storage controls by classification**   
- Public: basic integrity + availability   
- Sensitive: access controls + encryption at rest + backups   
- Confidential/Private/Restricted: stronger physical security + encryption + tight access review + immutable logging + offsite storage under strict custody   
 --- 
   
# Data remanence & sanitization (how you end data safely)   
OSG7 warns that “deleting isn’t destroying” and that real dest
CISSP - Official Study Guide - …
ruction for sensitive data.
CISSP - Official Study Guide - …   
All-in-One explains why (file systems/databases keep recoverable data).
CISSP - All In One Exam Guide -…   
## The modern gold standard: NIST SP 800-88 Rev.1 (Clear / Purge / Destroy)   
NIST SP 800-88 Rev.1 (December 2014) defines a structured sanitization program and ties decisions to confidentiality impact and lifecycle planning.   
### 1) Clear (logical sanitization for repurpose   
CISSP - Official Study Guide - …
sks: overwrite with at least one fixed pattern (e.g., all zeros) and verif
CISSP - All In One Exam Guide -…   
- Routers/switches, office equipment: manufacturer reset to factory defaults (Clear)   
   
### 2) Purge (stronger than Clear; recovery infeasible with lab techniques)   
NIST notes many devices only support Clear; Purge may require media-dependent methods or **Cryptographic Erase** where supported, and must be applied carefully with manufacturer guidance.   
### 3) Destroy (physical destruction)   
NIST gives concrete destroy guidance:   
- paper: cross-cut shredding to small particle sizes or pulverize/disintegrate   
- office equipment/media: shred/disintegrate/pulverize or incinerate in licensed incinerator   
   
## Why SSDs complicate life (the “expert warning”)   
SSDs perform wear leveling and may not overwrite the same physical cells reliably. This is why modern sanitization guidance emphasizes:   
- device-supported secure erase / block erase / crypto erase (when correctly implemented)   
- verification steps   
- and, when assurance must be maximal, physical destruction (Destroy)   
   
(NIST SP 800-88 includes device-specific guidance and verification emphasis across modern media types.)   
## Documentation: “Certificate of Sanitization” mindset   
Asset Security is also auditability: you must be able to prove that media holding restricted data was sanitized correctly. NIST includes sample documentation artifacts (e.g., certificate of sanitization forms).   
 --- 
# Domain 2 “professional deliverables” you should build (so you actually master this)   
If you want to be *operationally* above CISSP, produce these artifacts:   
1. **Data classification policy** + classification rubric   
2. **Handling matrix** (per classification): mark/label, storage, transport, sharing, logging, destruction   
3. **Retention schedule** (by data type): min/max retention + deletion triggers + archive rules   
4. **Privacy program mechanics**: data mapping, purpose limitation, minimization controls, deletion workflow (DSR/CCPA/GDPR style)   
5. **Sanitization standard** aligned to NIST 800-88 (Clear/Purge/Destroy decisioning)   
6. **Baseline standards** by system class, with scoping/tailoring rules
CISSP - Official Study Guide - …   
   
> how you turn classification into real controls across endpoints, servers, cloud object storage, databases, and backups/archives—with scoping/tailoring, DLP, retention, privacy operations, and sanitization built i   

## 1) The core mechanism: build a Classification-to-Control Mapping (your “Data Protection Standard”)   
### 1.1 Start from the domain’s blueprint (what must exist)   
OSG7 explicitly enumerates the Domain 2 components you must operationalize: privacy (owners/processors/remanence/collection limitation), retention (media/hardware/personnel), and data security controls (at rest/in transit) including baselines + scoping/tailoring + standards selection + cryptography, plus handling requirements (marking/labels/storage/destruction).
CISSP - Official Study Guide - …   
### 1.2 Pick a classification scheme that has low overlap   
CBK4 warns that classification definitions should have **little overlap** and provides practical private-sector labels such as *Public / Company Confidential / Company Restricted / Private (e.g., SSNs, bank/credit cards)*.
Official Guide To CISSP CBK - 4…   
A good “enterprise default” (simple, scalable):   
- **Public** (intended for external release)   
- **Internal** (non-public operational data)   
- **Confidential** (business-sensitive; limited disclosure)   
- **Restricted** (highest sensitivity: regulated data, secrets, high-impact datasets)   
   
Then add **data-type tags** that trigger special rules:   
- PII / PHI / PCI   
- Credentials/secrets   
- Source code   
- Legal/HR sensitive   
- Security telemetry (logs, IR data)   
   
OSG7 stresses that “assets” include not just the data but also **hardware and media used to process/hold it**—so your mapping must include endpoints, servers, removable media, and backups as first-class items.
CISSP - Official Study Guide - …   
 --- 
## 2) Scoping & tailoring: baselines are mandatory, but not “one size fits all”   
### 2.1 Why scoping/tailoring exists   
OSG7 explicitly calls out that baselines don’t apply universally and that you adopt them using **scoping and tailoring**.
CISSP - Official Study Guide - …   
CBK4 explains scoping/tailoring as the mechanism that ensures controls match the system/environment and are approved by the authorizing decision authority, while avoiding unnecessary complexity/cost.
Official Guide To CISSP CBK - 4…   
**Practical rule:** you maintain **separate baselines by system class** (endpoint, server, DB, cloud storage, backup vault) and then **scope/tailor** based on:   
- classification handled (Public vs Restricted)   
- exposure (internet-facing vs internal)   
- privilege (admin plane vs user plane)   
- operational constraints (legacy apps, OT/ICS, disconnected sites)   
 --- 
   
## 3) The control stack by data state (your “physics of data”)   
OSG7 defines the three data states—**at rest, in transit, in use**—and the domain expects controls for each state (encryption + strong authz/authn as core confidentiality mechanisms).
Official Guide To CISSP CBK - 4…   
**Expert move:** don’t say “encrypt data” generically. Say:   
- At rest: where is it stored, and what is the key ownership model?   
- In transit: what protocols and mutual authentication exist?   
- In use: what prevents endpoint theft, memory scraping, unsafe exports, and unauthorized processing?   
 --- 
   
## 4) Now the real part: Classification-to-controls mapping by platform   
Below is a “best-practice” mapping approach. Treat it as the template you refine into policy/standard/baseline/procedure.   
### A) Endpoints (laptops, desktops, mobile devices)   
**Why endpoints are special:** they are where data is most likely to be **copied, cached, screenshotted, exported, printed, or stolen**—and where “data in use” is frequently exposed.   
**Controls that scale with classification**   
**Public/Internal**   
- Full-disk encryption (still recommended; prevents opportunistic theft)   
- Standard EDR + patching baseline   
- Basic logging for authentication + device posture   
   
**Confidential**   
- Full-disk encryption **mandatory**   
- Strong screen lock + short idle timeout   
- Block risky exfil paths (USB mass storage, unmanaged cloud sync)   
- Endpoint DLP for copy/paste, printing, removable media, and sensitive file movement   
- “Trusted apps” policy (restrict unknown executables where feasible)   
   
**Restricted (PII/PHI/PCI/secrets/source)**   
- Everything above, plus:   
- Only approved apps can open Restricted data (application control)   
- Strong DLP + classification labels recognized in email/docs   
- No local storage unless explicitly approved; prefer VDI/remote workspaces for highest sensitivity   
- Stricter admin model (separate admin accounts; privileged access mediated)   
- Enhanced auditing of exports and mass access   
   
**Why DLP must be informed by inventories and flows**   
AIO8 explicitly warns that DLP tooling must be driven by administrative homework: **data inventories, flows, and protection strategies** come first; otherwise orgs waste money on “solutions” that don’t fit.
CISSP - All In One Exam Guide -…   
It also describes what you evaluate in a DLP product: sensitive data discovery techniques (keywords/regex/tags/statistical methods), policy engines, interoperability, and accuracy testing in a realistic environment.
CISSP - All In One Exam Guide -…   
**Operational evidence (what proves endpoint controls work)**   
- DLP policy test results (authorized flows allowed, unauthorized blocked)
CISSP - All In One Exam Guide -…   
- MDM compliance reports (encryption enabled, OS patch level)   
- EDR coverage reports + alert triage metrics   
- USB control logs / “restricted data copied” events   
 --- 
   
### B) Servers (application servers, file servers, domain services)   
**Key idea:** servers are where **shared datasets** live, so access control and auditing matter more than on endpoints.   
**Baseline controls (all classifications)**   
- OS hardening baseline + patch SLAs   
- Least privilege service accounts   
- Central logging + time sync   
- Backup configured and tested (availability is part of asset protection)   
   
**Confidential**   
- Encryption at rest for volumes or files containing Confidential datasets   
- Strong segmentation (server in appropriate trust zone)   
- Tight RBAC: only service roles and business roles that require access   
- File integrity monitoring for sensitive directories (detect tampering)   
   
**Restricted**   
- “Two-person” change control for sensitive systems (administrative control)   
- Stronger segmentation + restricted admin access paths   
- Mandatory audit logging of sensitive access (reads, writes, export)   
- Stricter backup encryption + storage controls (see Backup section)   
- Frequent access reviews (owner sign-off)   
   
**Evidence**   
- Baseline compliance scans (secure config drift)   
- Access review attestations by data owner   
- Audit logs showing sensitive access events   
 --- 
   
### C) Cloud object storage (S3-like buckets, Azure blob-like containers)   
**Why object storage needs special handling:** it’s easy to misconfigure, easy to share, and data tends to sprawl.   
**Controls mapped to classification**   
**Public**   
- Explicit “public publishing” process (owner approval)   
- Integrity controls (versioning) if content must not be tampered   
   
**Internal**   
- Deny public access by default   
- IAM least privilege (read/write roles only where needed)   
- Centralized access logging   
   
**Confidential**   
- Encryption at rest (managed keys acceptable if policy allows)   
- Tight bucket/container policy: only approved identities and network paths   
- DLP scanning for sensitive data discovery (to find misplacements)   
- Lifecycle rules aligned to retention schedule (see retention section)   
   
**Restricted**   
- Encryption at rest with **strong key governance** (often customer-managed keys / BYOK depending on org policy)   
- Object immutability/retention controls for records that must not change   
- Strong egress controls (restrict downloads to approved environments)   
- Continuous monitoring for “public exposure” and unusual access patterns   
   
**Evidence**   
- Policy compliance reports (no public buckets; encryption enforced)   
- Key usage logs + access logs + alerting   
- DLP scan reports showing discovery and remediation   
 --- 
   
### D) Databases (structured crown jewels)   
**Database truth:** the most damaging events are usually:   
- overly broad read access   
- bulk export (“SELECT \*” exfil)   
- abuse of privileged DBAs or app service accounts   
   
**Controls by classification**   
**Internal**   
- Basic RBAC; logging of admin actions   
   
**Confidential**   
- Encryption at rest (TDE or storage encryption)   
- Strong authn + network isolation (DB not directly exposed)   
- Audit key operations (login, schema change, privileged queries)   
   
**Restricted**   
- Column/table classification (PII columns, PCI tables, etc.)   
- Stronger access controls: separate roles for read vs write vs admin   
- Strict export controls (approve and log bulk exports)   
- Data masking for non-prod (so dev/test doesn’t become an uncontrolled Restricted copy)   
- Detailed auditing for sensitive table access (who, what, when)   
- Backup encryption + retention + access restrictions (backup is a second copy of the database)   
   
**Evidence**   
- Audit reports of sensitive-table access   
- Evidence of masking/tokenization in non-prod pipelines   
- Access recertification evidence (data owner approval)   
 --- 
   
### E) Backups & archives (the most common “silent failure”)   
OSG7 makes a critical point: **backup media should be protected with the same level of protection afforded the data it contains**; merely marking it isn’t enough if it is stored insecurely (example: tapes in an unmanned warehouse).
CISSP - Official Study Guide - …   
**Controls**   
- Classification inheritance: backup is *at least* the max classification of its contents   
- Encryption **before leaving** the primary security boundary   
- Strict custody: approved offsite storage, chain-of-custody, access logs   
- Separate admin roles: backup operators shouldn’t automatically be able to restore Restricted data without approval   
- Retention schedule enforced (don’t keep “20 years of backups” when policy says 6 months—OSG7 explicitly flags this as “personnel didn’t follow retention policy”).
CISSP - Official Study Guide - …   
   
**Evidence**   
- Restore tests (availability + integrity)   
- Backup inventory + location tracking   
- Access logs for restore operations   
- Proof of encryption (key management + crypto policy)   
 --- 
   
## 5) Retention & defensible disposal (where privacy, security, and legal reality meet)   
CBK4 provides a very “real enterprise” approach: retention policies must be **communicated, implemented, monitored, managed for compliance, and audited**; and it emphasizes cross-functional ownership (business defines who can touch data and what they can do; IT implements the infrastructure).
Official Guide To CISSP CBK - 4…   
It also gives a clean **eight-step** retention policy development guide:   
1. evaluate statutory/litigation/business needs   
2. classify records   
3. determine retention periods + destruction practices   
4. draft/justify policy   
5. train staff   
6. audit retention/destruction   
7. periodically review   
8. document policy/implementation/training/audits
Official Guide To CISSP CBK - 4…   
   
**Modern privacy alignment (why retention must be minimal and purposeful)**   
- GDPR Article 5 includes principles like **purpose limitation, data minimization, storage limitation, integrity/confidentiality, accountability**.   
- The California AG’s CCPA page summarizes core consumer rights such as **opt-out of sale/sharing** and other privacy rights.   
   
**Practical outcome:** your retention schedule must be compatible with:   
- “keep only what’s necessary”   
- “delete when purpose expires”   
- “hold when litigation applies” (legal hold overrides destruction)   
 --- 
   
## 6) Sanitization / destruction (end-of-life correctness)   
NIST SP 800-88 Rev.1 frames sanitization as making access to target data infeasible and explicitly ties decisions to the confidentiality categorization of information.   
Operationally, it gives the well-known action levels: **Clear / Purge / Destroy**.   
**How you apply it in a program**   
- **Clear**: suitable when reusing media within a similar trust boundary (with verification)   
- **Purge**: stronger; often required for higher sensitivity or when leaving control   
- **Destroy**: when assurance must be maximal or media type complicates sanitization (e.g., failed drives, certain SSD scenarios)   
   
**Evidence you need**   
- Sanitization certificate / disposal records (asset ID, method, date, operator, witness where required)   
- Vendor disposal attestations (if outsourced) + audit rights   
 --- 
   
## 7) Quick “mastery checkpoint” for Domain 2 (implementation-level)   
If you can do these without guessing, you’re operating above CISSP level:   
1. For any dataset, you can state its **classification + data-type tags + owner + allowed flows + retention + disposal method**.   
2. You can show the **controls per data state** (rest/transit/use) and explain why they differ.   
3. You can show that backups follow “same protection as data” and that retention is enforced in practice.
CISSP - Official Study Guide - …   
4. You can demonstrate that DLP (if used) is driven by real **inventories + flows + testing**, not wishful policy.
CISSP - All In One Exam Guide -…   
    CISSP - All In One Exam Guide -…   
    CISSP - Official Study Guide - …
e retention and destruction are **audited and documented**.
Official Guide To CISSP CBK - 4…   
    CISSP - All In One Exam Guide -…   
    CISSP - All In One Exam Guide -…
database export request, laptop theft, vendor offboarding, backup retention m
Official Guide To CISSP CBK - 4…
les → controls → evidence → retention/disposal.   
   
>    

### What’s still missing / not fully “completed”   
1. **Data leakage / data loss paths** (AIO emphasis)   
    - Insider leakage patterns, exfil channels, shadow IT, misdelivery, misconfiguration, over-sharing, logs leaking PII, etc.   
    - How you design *prevent/detect/contain* for leakage beyond “deploy DLP”.   
2. **Storage media handling in depth** (AIO/OSG7/SG4 emphasis)   
    - Removable media control program, tape/offsite vault controls, endpoint caches, printer/fax artifacts, mobile device storage, virtualization snapshots, cloud backups/exports.   
3. **Physical security controls as they relate to assets** (OSG7 Ch.5 includes physical controls under asset protection)   
    - How physical access, environmental controls, and custody directly implement data classification/handling requirements.   
4. **Asset value / criticality tie-in** (SG4 “asset value” framing)   
    - Translating business value into classification, retention, and protection rigor.   
5. **Declassification + transfer of custody** (OSG7 classification program phases)   
    - How to safely lower classification over time and how to hand off custody without breaking control ownership and auditability.   
   
If you want “Domain 2 complete,” the clean way is: I finish these remaining sections now (below). After this, yes—Domain 2 will be complete.   
 --- 
## Domain 2 completion addendum (the missing sections)   
## 1) Data leakage and data loss prevention (beyond “DLP tool”)   
### 1.1 What “data leakage” really is   
Data leakage isn’t only “exfiltration by hackers.” It includes:   
- **Unauthorized disclosure** (sending to wrong recipient, oversharing links, public bucket)   
- **Unauthorized movement** (copying to personal devices/accounts)   
- **Unauthorized persistence** (data remains in temp files, logs, caches, backups)   
- **Unauthorized inference** (too much metadata, analytics outputs revealing sensitive info)   
   
### 1.2 The leakage attack surface (the channels you must control)   
A professional program enumerates leakage channels explicitly:   
**Human/process leakage**   
- Misaddressed email, wrong attachment   
- Copy/paste into tickets, chat, wiki   
- Screenshots in support threads   
- “Quick export to Excel” and uncontrolled sharing   
   
**Identity/authorization leakage**   
- Excessive access (broad groups, shared accounts)   
- Stale access (no deprovisioning, no periodic review)   
- Privileged users dumping data “because they can”   
   
**Technical egress channels**   
- Web uploads (personal cloud drives)   
- API bulk export   
- Removable media   
- Printing   
- Clipboard sync   
- Developer tooling (debug logs, core dumps, telemetry)   
   
**Configuration leakage**   
- Public object storage   
- Misconfigured SaaS sharing permissions   
- Data exposed by default search/indexing   
- Backup copies accessible to too many operators   
   
### 1.3 Control strategy: prevent + detect + contain + prove   
Build leakage controls around classification:   
**Prevent**   
- Default deny external sharing for Confidential/Restricted   
- Block removable media writes for Restricted datasets   
- Limit exports (rate limits, approvals, watermarking)   
- Tokenization/masking for non-prod and analytics   
   
**Detect**   
- DLP alerts (but tuned by data tags + context)   
- UEBA-lite patterns (mass reads, unusual downloads, new geo/device)   
- Cloud posture monitoring (public exposure detection)   
   
**Contain**   
- Auto revoke shared links   
- Quarantine files moved to unsafe locations   
- Suspend accounts on high-confidence exfil patterns   
   
**Prove**   
- Logs that show who accessed/exported/shared   
- Evidence that prevention controls are enforced (policy compliance reports)   
- Regular review of exceptions and false positives/negatives   
 --- 
   
## 2) Storage media and “where data hides” (the remanence reality expanded)   
### 2.1 Media types you must explicitly govern   
A complete asset security program treats these as first-class:   
- Endpoints: SSD/HDD, browser caches, temp files, offline sync folders   
- Removable: USB drives, SD cards, external disks   
- Print artifacts: printer memory/spool, scanned PDFs on MFP devices   
- Backups: tapes, backup appliances, cloud backups, snapshots   
- Virtualization: VM snapshots, templates, disk images   
- Cloud exports: data lake extracts, backups, “share to vendor” exports   
   
### 2.2 Media control program (practical)   
For Confidential/Restricted:   
- **Allowed media list** (approved encrypted USB only, or none)   
- **Custody rules** (who can issue media, how it’s tracked)   
- **Labeling rules** (media inherits highest classification of contents)   
- **Transport rules** (chain-of-custody + tamper-evident packaging)   
- **Return/destruction rules** (must be sanitized with documented method)   
   
The big failure mode: orgs secure production storage but ignore *copies* (exports, temp files, backups). Domain 2 mastery means you secure **copies by default**.   
 --- 
## 3) Physical security controls as asset controls (Domain 2 perspective)   
Even if “Physical Security” is its own chapter elsewhere, **Asset Security uses physical controls to enforce data handling rules**:   
### 3.1 Physical access is a confidentiality control   
If someone can enter:   
- the server room,   
- the backup vault,   
- the IT storage closet,   
    they can often steal **unlocked assets** (drives, tapes, laptops) and defeat logical controls.   
   
### 3.2 Environmental controls protect integrity and availability   
- Temperature/humidity control prevents media degradation   
- Fire suppression protects records and backups   
- Power conditioning protects storage systems from corruption events   
   
### 3.3 Physical evidence is part of auditability   
For high classifications:   
- access badge logs for vault rooms   
- visitor logs   
- camera retention aligned to incident investigation needs   
- chain-of-custody documentation for removed media   
 --- 
   
## 4) Asset value and criticality (SG4’s “asset value” lens)   
Classification is ultimately driven by **impact**:   
- Impact to **business operations**   
- Impact to **legal/regulatory posture**   
- Impact to **competitive position**   
- Impact to **people** (privacy harms)   
   
A practical expert method:   
- Assign **Business Criticality Tier** (Tier 0–3) and **Data Sensitivity** (Public→Restricted)   
- Then define a matrix that determines:   
    - minimum logging requirements   
    - minimum encryption requirements   
    - minimum access review frequency   
    - backup/DR requirements   
    - retention minimum/maximum   
    - disposal method (clear/purge/destroy)   
   
This ties “asset value” directly into control rigor instead of leaving it as a vague concept.   
 --- 
## 5) Declassification and transfer of custody (often skipped, but required)   
### 5.1 Declassification is not “just relabel it”   
Declassification must be triggered by explicit conditions:   
- time-based (e.g., product launch passed)   
- event-based (contract ended, case closed)   
- retention-based (legal minimum satisfied)   
   
And it requires **downstream cleanup**:   
- revoke old shares   
- update DLP/classification tags   
- move data to appropriate storage tier   
- adjust access rules/logging rules   
   
### 5.2 Transfer of custody   
When data moves between custodians (team change, vendor onboarding, system migration):   
- owner remains the authority   
- custodian responsibilities must be explicitly transferred   
- access and logging must remain continuous   
- you must preserve integrity and chain-of-custody when required (especially for regulated records)   
 --- 
   
# So: is Domain 2 complete now?   
With the addendum above, **yes**—Domain 2 is now complete at the “professional operator” level:   
- classification + ownership + privacy + retention + controls per data state + handling + sanitization **plus**   
- leakage controls + media governance + physical asset enforcement + asset value mapping + declassification/custody transfer   
   
>    

## Domain 2 checkpoint — Asset Security mastery (principal-level)   
This is a **real enterprise operator test**. For each scenario, your answer must show you can run Domain 2 as a **system**: classification → ownership → privacy → retention → controls by data state → handling → evidence → disposal/sanitization.   
Use this **7-step answer format** (same for every scenario):   
1. **Asset & data description** (what is it, where it lives, who uses it)   
2. **Classification + data-type tags** (Public/Internal/Confidential/Restricted + PII/PHI/PCI/secrets/etc.)   
3. **Owners/custodians/processors** (who decides vs who operates)   
4. **Allowed flows + handling rules** (where it may go, sharing rules, marking/labels, transport)   
5. **Controls by data state**   
    - at rest / in transit / in use (prevent + detect)   
6. **Retention + disposal** (min/max, legal hold, Clear/Purge/Destroy)   
7. **Evidence** (what proves compliance and effectiveness; cadence)   
 --- 
   
# A) 10 scenarios (answer any 3 for a strong pass, all 10 for mastery)   
### 1) Cloud bucket accidentally became public   
**Situation:** A cloud storage bucket contains customer exports. An engineer toggled “public read” for testing and forgot.   
You must cover:   
- how you classify the data and bucket   
- immediate containment steps   
- evidence of exposure scope (logs)   
- corrective prevention (guardrails + monitoring)   
- retention/deletion of leaked copies   
 --- 
   
### 2) Developer wants a full production database dump for debugging   
**Situation:** “It’s blocking a release; I’ll store it on my laptop temporarily.”   
You must cover:   
- Restricted classification inheritance to device/media   
- non-prod data strategy (mask/tokenize)   
- approval workflow + compensating controls if urgent   
- retention and destruction proof after use   
 --- 
   
### 3) HR system export shared with a vendor   
**Situation:** Vendor needs employee data for payroll processing. Data includes IDs, bank accounts, and addresses.   
You must cover:   
- privacy roles (owner/controller vs processor)   
- minimum vendor requirements + allowed data minimization   
- contract/SLA security clauses (at least conceptually)   
- retention/offboarding + destruction attestations   
 --- 
   
### 4) Lost laptop with Confidential project files   
**Situation:** A manager reports their laptop stolen from a car.   
You must cover:   
- at-rest controls (disk encryption, key mgmt)   
- in-use exposure (cached files, offline sync)   
- incident evidence to determine exposure   
- remote wipe feasibility and proof   
- notification thresholds if PII involved   
 --- 
   
### 5) Backup tapes found in an unmanaged warehouse   
**Situation:** Old tapes from 6 years ago, unknown contents, no inventory.   
You must cover:   
- classification of backups (inherit highest)   
- custody/chain-of-custody failures   
- retention policy violation handling   
- content discovery strategy (what you do without reintroducing risk)   
- disposal method (NIST 800-88 style clear/purge/destroy decision)   
- evidence/certificates   
 --- 
   
### 6) Data retention conflict: Legal says “keep,” Privacy says “delete”   
**Situation:** Customer requests deletion; Legal says a dispute might arise.   
You must cover:   
- retention schedule vs legal hold   
- documenting legal basis for retention   
- access restriction during hold   
- eventual disposal triggers and evidence   
 --- 
   
### 7) Printer/MFP stores scanned documents with PII   
**Situation:** Multifunction printer stores job history; users scan IDs and contracts.   
You must cover:   
- asset inventory inclusion   
- access controls and admin separation on device   
- retention and secure wipe procedures   
- physical controls and audit logs   
 --- 
   
### 8) Email with Restricted data sent to the wrong external recipient   
**Situation:** Finance accidentally emails a spreadsheet with customer PII to an external address.   
You must cover:   
- classification markings/labels and why they failed   
- DLP prevention/detection expectations   
- containment (recall, request deletion, legal involvement)   
- breach determination process and recordkeeping   
 --- 
   
### 9) Declassification: “This used to be secret; now it’s public”   
**Situation:** Product roadmap documents are now public after launch.   
You must cover:   
- declassification triggers and approval   
- downstream control changes (sharing rules, DLP tags, storage tier)   
- ensuring old copies and backups are handled correctly   
 --- 
   
### 10) Shadow IT: team uses personal Google Drive for internal documents   
**Situation:** You discover documents include customer tickets with PII.   
You must cover:   
- data mapping and discovery approach   
- containment and migration plan   
- user training + policy enforcement   
- prevention controls and metrics   
 --- 
   
# B) Scoring rubric (principal-level)   
Score each scenario **0–3** on each dimension (max 21):   
1. Classification correctness + inheritance   
2. Ownership clarity (owner vs custodian vs processor)   
3. Controls by data state (rest/transit/use)   
4. Handling rules (labels, sharing, transport, media)   
5. Retention + legal hold logic   
6. Disposal/sanitization correctness (Clear/Purge/Destroy choice + proof)   
7. Evidence + cadence (logs, reports, audits, attestations)   
   
**Interpretation**   
- **18–21**: principal / program owner level   
- **14–17**: strong senior engineer level (missing evidence/cadence or legal hold detail)   
- **10–13**: tactical controls only (good ideas, weak program mechanics)   
- **<10**: tool-based answers; high operational risk   
 --- 
   
# C) “Non-negotiables” to pass Domain 2   
You must demonstrate:   
- **Backups inherit classification** and must be protected equivalently   
- **Retention is enforced and audited**, not just documented   
- **Data minimization + purpose limitation** mindset for privacy   
- **Evidence**: logs, access reviews, certificates of destruction, DLP test results   
- **Legal hold overrides deletion**, with restricted access and documented basis   
- **Declassification is managed**, and old copies/backups are addressed   
   
>    

>    

>    

>    

>    

> a   

>    


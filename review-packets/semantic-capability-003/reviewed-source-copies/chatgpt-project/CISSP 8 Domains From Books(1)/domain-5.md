---
# yaml-language-server: $schema=schemas\page.schema.json
Object type:
    - Page
Backlinks:
    - Books-Summary = CISSP 8-Domain (References)
Creation date: "2026-02-25T20:26:19Z"
Created by:
    - Perky Sparrow
id: bafyreig62n2xf4gl5einwaan7rqaua3kf5sejazmget5rdmtjjmq3y5eiu
---
# Domain 5   
>    

> Identity & Access Management Concepts   

**(IAAA · access control models · access control types · identity proofing vs auth · privilege/entitlement discipline)**   
# 1) What “Access Control” really is (and why IAM is the enterprise blast-radius governor)   
## (1) Definition + purpose   
CBK4 gives the most precise baseline definition you should keep in your head:   
> Access control is the process of allowing only authorized users/programs/systems to observe, modify, or take possession of resources, and limiting resource use to authorized entities.   
> Official Guide To CISSP CBK - 4…   

That definition matters because it quietly includes **three control targets**:   
1. *Observation* (confidentiality / data disclosure)   
2. *Modification* (integrity)   
3. *Possession/use* (availability + misuse prevention)   
   
OSG7 frames IAM as the discipline that governs **granting and revoking privileges** to access data or perform actions—i.e., *who can do what, from where, and under what conditions*.
CISSP - Official Study Guide - …   
## (2) Enterprise implementation (how real orgs “build” access control)   
In mature enterprises, access control is never “one system.” It is a **stack of synchronized control planes**:   
- **Identity plane** (directory + IdP): authoritative identities, groups/roles, device identities, service identities.   
- **Auth plane**: authenticators, session issuance, token signing keys, step-up policies.   
- **AuthZ plane**: decision services and enforcement points (AD ACLs, app policy engines, cloud IAM, gateways).   
- **Accountability plane**: logging, audit trails, time sync, immutable storage, review workflows.   
   
AIO8’s Chapter 5 headings map this exactly: it explicitly spans **IAAA**, **session management**, **federation**, **IDaaS**, **access control mechanisms (DAC/MAC/RBAC/Rule/ABAC)**, access control matrices, context/content-dependent controls, and the **provisioning lifecycle (provisioning → reviews → deprovisioning)**.
CISSP - All In One Exam Guide -…   
## (3) Failure modes / attack paths (how access control breaks in practice)   
The real-world failures are consistent across orgs and across decades:   
1. **Identity is not authoritative** (duplicates, stale accounts, shared accounts, no lifecycle owner).   
2. **Authentication is weak or bypassed** (phishing, password reset abuse, token theft, MFA fatigue).   
3. **Authorization drifts** (privilege creep/aggregation, inherited rights, “temporary access” becomes permanent).   
4. **Accountability is missing** (no logs, logs not tied to stable identity, no retention, no review).   
5. **The “one control to rule them all” bottleneck** (SSO/IdP outage = enterprise outage).   
   
AIO8 calls out a classic SSO risk: if an attacker uncovers the user’s credential set, they gain access to *all resources* the user can access; and SSO can also create a bottleneck/single point of failure without redundancy.
CISSP - All In One Exam Guide -…   
## (4) Controls & mitigations (prevent/detect/respond/recover)   
Think in layered controls:   
**Prevent**   
- Make identity authoritative (HR-driven identity source-of-truth, no shared accounts).   
- Strong authentication appropriate to risk (phishing-resistant MFA for privileged paths).   
- Least privilege + separation of duties enforced through roles and entitlements.   
   
**Detect**   
- Auth anomalies (impossible travel, new device, suspicious resets)   
- Entitlement diffs (unexpected grants, group membership changes, role changes)   
- Privileged action monitoring (who used admin rights, where, when)   
   
**Respond**   
- Session revocation, credential reset, and forced re-enrollment   
- Emergency role removal and access path blocking (PAM kill-switch patterns)   
- Post-incident entitlement review focused on *what access made the incident possible*   
   
**Recover**   
- Rebuild from baselines (role catalog + policy-as-code)   
- Re-provision cleanly and re-validate logs + reviews   
   
## (5) Evidence & verification (how you prove IAM works)   
You prove IAM with **four evidence streams** (and auditors love these because they’re continuous):   
1. **Authentication evidence**: IdP sign-in logs, MFA challenges, password reset events   
2. **Authorization evidence**: entitlement snapshots + diffs (groups/roles/claims/policies)   
3. **Accountability evidence**: audit trails (privileged actions, sensitive data access)   
4. **Lifecycle evidence**: joiner/mover/leaver records and deprovisioning proofs   
   
## (6) Real examples   
- **Windows/AD**: authorization is largely enforced via ACLs + group membership + user rights; authentication often via Kerberos; accountability via Security Event Logs + directory auditing.   
- **Cloud**: authorization enforced by IAM policies/roles; authentication via IdP (OIDC/SAML); accountability via cloud audit logs (e.g., control-plane logs).   
- **Network**: RADIUS/TACACS+ centralize admin access control and accounting (we’ll go deep in a later chunk).   
 --- 
   
# 2) IAAA as a pipeline (the “identity flow” you should visualize automatically)   
OSG7 explicitly emphasizes IAAA as the center of the domain.
CISSP - Official Study Guide - …   
## Identification → Authentication → Authorization → Accountability   
### Identification (claim)   
**What it is:** Presenting an identifier (username, UPN/email, device ID, service principal).   
**Purpose:** Create an addressable subject in control logic (“who is requesting?”).   
**Enterprise build:**   
- Directory object exists (user/device/service).   
- Identifier uniqueness is enforced (UPN/email uniqueness policies).   
- Naming conventions (service principals, workload identities).   
   
**Failure modes:**   
- Shared accounts (“admin”, “helpdesk”) destroy accountability.   
- Identifier collisions and duplicate identities break entitlement tracking.   
   
**Controls:**   
- Unique IDs, no shared logins, strong joiner/mover/leaver governance.   
- Service identity standards (no “human accounts” running services).   
   
**Evidence:**   
- Directory uniqueness reports; shared-account exceptions register; identity inventory.   
 --- 
   
### Authentication (proof)   
**What it is:** Proving the subject controls an authenticator bound to the identifier.   
**Modern correction (important):** NIST separates **identity proofing** from **authentication**:   
- **IAL** = identity proofing strength   
- **AAL** = authentication strength   
- **FAL** = federation assertion strength   
    This separation is *exactly* what “above CISSP” programs do: *don’t over-collect identity proofing where it’s unnecessary; don’t under-strengthen authentication where risk is high*.   
   
**Freshness update:** NIST SP 800-63 Revision 4 (final July 2025) adds modern realities like **syncable authenticators (synced passkeys)** and **subscriber-controlled wallets** to the federation model.   
That’s a huge shift in the identity landscape you must accommodate architecturally.   
**Enterprise build:**   
- Authenticator lifecycle: enroll → bind → use → rotate/recover → revoke   
- Step-up authentication policies for sensitive actions   
- Password reset is treated like a privileged operation   
   
AIO8 gives a very concrete operational risk: help desk password resets can be abused via social engineering unless the caller is authenticated through the password management process; and the system should force a change so only the user knows the final password.
CISSP - All In One Exam Guide -…   
**Failure modes:**   
- Weak reset flows are “identity backdoors.”   
- MFA used, but not phishing-resistant for admin paths.   
- Device binding absent (token theft becomes session theft).   
   
**Controls:**   
- Phishing-resistant MFA for privileged roles; lock down resets; strong enrollment proofing where needed.   
- Risk-based authentication (signals: device health, location, impossible travel).   
   
**Evidence:**   
- Password reset logs, helpdesk tooling logs, MFA challenge logs, enroll/re-enroll events.   
 --- 
   
### Authorization (decision + enforcement)   
**What it is:** Determining if the authenticated subject is allowed to perform an operation on an object under policy.   
**Crucial professional distinction:**   
Authentication answers **“who are you?”**   
Authorization answers “are you allowed to do *this*, *now*, from ***there*?”**   
CBK reminds you: the **TGT is like a passport**—it proves authentication and enables requesting service tickets, but it does not itself grant access; authorization still happens at the application/service boundary.
Official Guide To CISSP CBK - 4…   
**Enterprise build:**   
- Central policy (RBAC/ABAC) + local enforcement (ACLs, app gates, gateway policy)   
- Change control for authorization policy (it’s security-critical code/config)   
   
**Failure modes:**   
- Privilege creep (aggregation) and “role explosion”   
- Misordered rules and precedence causing “allow overrides deny” (CBK warns about precedence/aggregation consequences).
Official Guide To CISSP CBK - 4…   
   
**Controls:**   
- Least privilege, separation of duties   
- Periodic entitlement reviews   
- Explicit deny for high-risk cases and strong exception governance   
   
**Evidence:**   
- Entitlement snapshots and diffs; access review approvals; policy version history.   
 --- 
   
### Accountability (traceability you can defend)   
**What it is:** The ability to trace actions to a subject with enough integrity to support investigations, audits, and disciplinary/legal actions.   
**Enterprise build:**   
- Stable identity in logs (unique ID)   
- Centralized logs; tamper resistance   
- Privileged activity logs (admin actions)   
   
**Failure modes:**   
- Missing logs, non-unique identities, clock drift, or uncontrolled admin access to logs.   
   
**Controls:**   
- Centralized logging; restricted log access; time sync   
- Alerting on “audit disabled” events (critical in AD/cloud)   
   
**Evidence:**   
- Immutable log storage + access control to logs + periodic reviews.   
 --- 
   
# 3) Access Control Models (DAC, MAC, RBAC, Rule-based, ABAC) — what they really mean operationally   
AIO8 explicitly lists these as the core “access control mechanisms” you must master in Domain 5.
CISSP - All In One Exam Guide -…   
## 3.1 Discretionary Access Control (DAC)   
**Definition:** The *owner* of an object decides who can access it (typical file share ACL model).   
SG4 shows the access control matrix as a classic representation and notes it can be used to quickly determine whether a requested action is authorized.
CISSP - Study Guide - 4th Editi…   
**Enterprise reality:**   
- DAC is flexible, but it’s where “permission sprawl” is born.   
- In Windows file shares, “everyone has modify” happens because owners optimize for productivity.   
   
**Failure modes:**   
- Inheritance + poor group design → massive over-permission   
- Orphaned owners (nobody is accountable)   
   
**Mitigations:**   
- Central standards for share permissions + periodic access review   
- Use groups/roles rather than individuals   
- Deny-by-default mindset (then grant minimal)   
   
**Evidence:**   
- ACL reports, effective access sampling, review sign-offs.   
 --- 
   
## 3.2 Mandatory Access Control (MAC) + lattice/labels   
**Definition:** The system enforces access based on **labels/classifications**; the owner does not get to override policy.   
SG4 ties this to multilevel security concepts like Bell-LaPadula and lattice-based access control (subjects positioned in a lattice and limited to ranges).
CISSP - Study Guide - 4th Editi…   
CISSP - Study Guide - 4th Editi…   
**Enterprise reality:**   
- Rare in general corporate IT, common in high-assurance/government contexts.   
- It is expensive and administratively heavy, but extremely strong for confidentiality separation.   
   
**Failure modes:**   
- Incorrect labeling or inconsistent classification schemes   
- Operational friction leads to bypass attempts (shadow IT)   
   
**Mitigations:**   
- Strict labeling governance, training, and tooling   
- Automated labeling where possible; auditing of label changes   
   
**Evidence:**   
- Label assignment logs, policy conformance reports, access denials review.   
 --- 
   
## 3.3 Role-Based Access Control (RBAC)   
**Definition:** Rights are assigned to roles (job functions), and users are assigned roles.   
A prep-guide explanation captures the operational benefit: it simplifies management when membership changes, because privileges are tied to the role, not the individual.
The CISSP Prep Guide - Gold Edi…   
**Enterprise reality:**   
- RBAC is the “workhorse” of enterprise IAM (AD groups, cloud roles).   
- The hard part is **role engineering** (roles that match real jobs without exploding into thousands of exceptions).   
   
**Failure modes:**   
- Role explosion (too granular)   
- Privilege creep when movers keep old roles   
- Toxic combinations (SoD violations)   
   
**Mitigations:**   
- Role catalog + SoD rules + lifecycle enforcement   
- Frequent mover reviews   
- Privileged roles separated from daily roles (admin accounts / PAM)   
   
**Evidence:**   
- Role catalog, SoD check reports, role membership diffs per HR events.   
 --- 
   
## 3.4 Rule-Based Access Control   
**Definition:** Global rules apply across subjects (often “if conditions then allow/deny”).   
OSG exam material anchors rule-based thinking to systems like firewalls, where **global rules apply to all users equally** and enforce consistent policy.
CISSP - Official Study Guide - …   
**Enterprise reality:**   
- Used heavily in network security devices and policy engines (“deny unless explicitly allowed”, geo restrictions, time windows).   
   
**Failure modes:**   
- Rule order/precedence mistakes   
- “Temporary allow” rules become permanent   
   
**Mitigations:**   
- Rule lifecycle governance, recertification, and automated policy tests   
   
**Evidence:**   
- Rule diffs, recertification approvals, policy test results.   
 --- 
   
## 3.5 Attribute-Based Access Control (ABAC)   
**Definition:** Decisions are made from attributes about subject/object/environment (department=Finance, dataType=PII, deviceCompliant=true, risk=high).   
In Windows/AD ecosystems, modern authorization also uses **claims** concepts (supporting ABAC-like controls). For example, AD Kerberos tickets can carry group membership and claims (supporting Dynamic Access Control) as part of the authorization context.
Active Directory, 5th Edition   
**Enterprise reality:**   
- ABAC is how modern “zero trust” policies actually behave (conditional access, device posture, risk-based decisions).   
- It reduces role explosion but shifts complexity into attribute correctness and policy evaluation.   
   
**Failure modes:**   
- Incorrect attributes (wrong department, stale device posture)   
- Attribute integrity not protected (client lies)   
- Policy debugging becomes hard without tooling   
   
**Mitigations:**   
- Authoritative attribute sources (HR, MDM, EDR)   
- Signed assertions/tokens and server-side evaluation   
- Policy simulators and “decision logs”   
   
**Evidence:**   
- Attribute source-of-truth reports, policy decision logs, drift detection.   
 --- 
   
# 4) Access Control Types (Administrative, Physical, Technical) — and why they must align   
CBK explicitly calls out the classic triad of access control types (administrative/physical/technical).
Official Guide To CISSP CBK - 4…   
**Enterprise reality:** you don’t “buy IAM.” You run it as an operating model:   
- **Administrative**: policies, joiner/mover/leaver processes, approvals, SoD rules   
- **Technical**: directory, IdP, PAM, token services, enforcement points   
- **Physical**: facility access controls that protect identity infrastructure (IdP, DCs, HSMs)   
 --- 
   
# 5) Privilege Management (where “good IAM” usually fails)   
## (1) Definition + purpose   
CBK4 is blunt: weak privilege management can cause “core failures” even when identification/authentication are strong; complexity of access options leads to inconsistent security unless you have clear processes and documentation.
Official Guide To CISSP CBK - 4…   
## (2) Enterprise implementation   
CBK’s operational requirements are exactly what mature PAM/IAM programs enforce:   
- identify/document privileges per system and map them to roles and job requirements   
- manage privileges by least privilege   
- maintain an authorization process + records of allocated privileges   
- use separate accounts for intermittent/special privileges rather than extending daily accounts
Official Guide To CISSP CBK - 4…   
   
## (3) Failure modes / attack paths   
- Privilege creep/aggregation: users collect rights over years (movers not cleaned up).   
- Overlapping roles create toxic combinations (SoD violations).   
- Admin privileges used from daily accounts → phishing becomes total compromise.   
   
## (4) Controls & mitigations   
- Tiered admin model + separate admin accounts + PAM vault workflows   
- Just-in-time privilege grants for sensitive roles   
- Session recording for privileged actions   
- Strong reset and recovery processes for privileged accounts   
   
## (5) Evidence & verification   
- Privileged role inventory (who has what and why)   
- Approvals for every privilege grant   
- PAM session logs + recordings + command trails   
- Quarterly “toxic combination” SoD reports   
   
## (6) Real examples   
- UNIX example in CBK: multiple accounts (daily, job-specific, root) illustrates privilege separation in practice.
Official Guide To CISSP CBK - 4…   
 --- 
   
# 6) Identity & Access Provisioning Lifecycle (provision → review → revoke)   
CBK defines the lifecycle phases explicitly as **Provisioning, Review, Revocation** and stresses that entitlement must be driven by business processes and access aggregation risk (privilege accumulation).
Official Guide To CISSP CBK - 4…   
AIO8’s Chapter 5 headings align: provisioning, user access review, system account access review, deprovisioning.
CISSP - All In One Exam Guide -…   
**Professional interpretation:** this lifecycle is the *real control* behind least privilege. If lifecycle is weak, every other IAM control becomes temporary.   
 --- 
## Modern “helped sources” (minimal but essential corrections)   
NIST SP 800-63 Revision 4 (final July 2025) explicitly modernizes identity guidance (risk updates, continuous evaluation metrics, syncable passkeys, subscriber-controlled wallets, and more).   
NIST SP 800-63-3 defines the separation of assurance levels (IAL/AAL/FAL), which is the cleanest way to reason about identity proofing vs authentication vs federation strength without mixing them up.   
   
> Implement and Manage Identity and Authentication   

*(Identity proofing · credential management · auth factors · SSO · federation · IDaaS · session management)*   
 --- 
## 1) Identity proofing vs authentication (the separation that makes programs “above CISSP”)   
### (1) Definition + purpose   
CBK4 draws a clean line:   
- **Identity proofing** happens **before** the account exists or before a credential is issued; it’s the process of verifying a real-world identity using approved evidence and rules.
Official Guide To CISSP CBK - 4…   
- **Electronic authentication (e-authentication)** happens **each time** a user logs in or uses a credential; it establishes confidence that the presenter of a credential is the rightful holder.
Official Guide To CISSP CBK - 4…   
   
This separation is not “academic.” It is the core of building scalable assurance:   
- **Proofing** is expensive and risk-laden (privacy, fraud, onboarding cost).   
- **Authentication** is frequent and must be optimized for both security and usability.   
   
### (2) Enterprise implementation   
In real enterprises, proofing is implemented as a **workflow + evidence chain**, not a UI:   
- **Joiner**: HR event triggers identity creation; proofing may be “in-person check” (high assurance) or “manager attestation + employment checks” (typical corporate).   
- **Credential issuance**: device enrollment (certificate/passkey), token issuance, or MFA registration.   
- **Privilege binding**: the stronger the privilege, the stronger the proofing requirements (“proof once, then authenticate often”).   
   
CBK4 points out proofing often doesn’t need full repetition every time, depending on relying-party policy and action sensitivity.
Official Guide To CISSP CBK - 4…   
Modern correction (authoritative): NIST formalizes this separation as **IAL (identity proofing), AAL (authentication), FAL (federation assertions)**.   
NIST SP 800-63-4 (final, 2025) further modernizes proofing to address modern fraud patterns and recommends continuous evaluation metrics; it also explicitly integrates **syncable authenticators (synced passkeys)** and **subscriber-controlled wallets** into the model.   
### (3) Failure modes / abuse paths   
- **Bad proofing = “perfect tech on a fake person.”** CBK4 is explicit: if a credential is issued from faulty identity proofing, the credential is compromised “no matter what technology it implements.”
Official Guide To CISSP CBK - 4…   
- **Privilege proofing mismatch**: weak proofing + high privilege = catastrophic insider/impersonation risk.   
- **Re-proofing gaps**: reissuing au
Official Guide To CISSP CBK - 4…
es the “side door.”   
   
### (4) Controls & mitigations   
- Risk-tier proofing: strengthen proofing only where needed (privileged/admin, finance approvals, regulated data).   
- Explicit re-issuance policy: “lost device” / “new phone” triggers identity re-verification.   
- Separate proofing from help desk convenience: help desk is high-value social engineering target (see also social engineering guidance in SG4).
CISSP - Study Guide - 4th Editi…   
    CISSP - Study Guide - 4th Editi…   
   
### (5) Evidence & verification   
- Proofing artifacts: HR records, manager attestation, identity verifi
CISSP - Study Guide - 4th Editi…   
    CISSP - Study Guide - 4th Editi…
ith compensating controls.   
   
### (6) Real examples   
- Government-grade: CBK4 discusses robust identity proofing underpinning PIV processes (FIPS 201-2 context) and emphasizes chain-of-trust in credential acceptance across agencies.
Official Guide To CISSP CBK - 4…   
    Official Guide To CISSP CBK - 4…   
- Enterprise-grade: HR + device posture + MFA enrollment as the real “proofing stack
Official Guide To CISSP CBK - 4…   
    Official Guide To CISSP CBK - 4…
s everything about **how secrets and authenticators are created, stored, presented, rotated, recovered, and revoked**—across OS, browsers, apps, and identity providers.   
   
The “above CISSP” insight: **credentials aren’t just passwords.** They include:   
- cached secrets (OS vaults, browsers),   
- tickets/tokens (Kerberos tickets, web sessions),   
- device-bound keys (certs, passkeys),   
- API keys and service credentials.   
   
### (2) Enterprise implementation (how it’s really built)   
CBK4 provides an unusually practical Windows-specific view that maps perfectly to enterprise reality:   
- **Credential Providers** in Windows collect/serialize credentials for logon UI; they are not enforcement—Local Security Authority and auth packages enforce.
Official Guide To CISSP CBK - 4…   
- Credential providers can support SSO-like behavior, network access authentication (e.g., with RADIUS), machine logon, joining domains, and UAC c
Official Guide To CISSP CBK - 4…   
    Official Guide To CISSP CBK - 4…   
   
Then you have **stored credential systems**:   
- Older “Stored User Names and Passwords” (and later Credential Manager / Windows Vault) enable multi
Official Guide To CISSP CBK - 4…
password compromises everything,” but creating a local credential store risk profile.
Official Guide To CISSP CBK - 4…   
- Credential Manager stores credentials and can automatically supply them later; if accepted, it overwrites old credentials with new ones.
Official Guide To CISSP CBK - 4…   
    Official Guide To CISSP CBK - 4…
also nails a critical security reality: credentials stored in Windows are protected by DPAPI and “any program running as that user will be ab
Official Guide To CISSP CBK - 4…   
    Official Guide To CISSP CBK - 4…   
   
### (3) Failure modes / abuse paths   
- **Local credential caches become post-compromise accelerators** (once an endpoint is compromised as the user
Official Guide To CISSP CBK - 4…
havior\*\* can replicate credential material across devices (CBK4 notes credentials can roam, but changes may not propagate cleanly across all machines—creating drift/confusion).
Official Guide To CISSP CBK - 4…   
- **Insecure “script-based SSO”** can leak credentials: both OSG7 and CBK4 warn scripts may contain credentials in clear text or be implemented ins
Official Guide To CISSP CBK - 4…   
    CISSP - Official Study Guide - …   
    Official Guide To CISSP CBK - 4…   
   
### (4) Controls & mitigations   
- Treat credential stores as **sensitive assets**:   
    CISSP - Official Study Guide - …   
    Official Guide To CISSP CBK - 4…
ce-bound or token-based auth),   
    - isolate admin activities from daily user sessions (separate accounts + PAM; deeper in Chunk 4).   
- Ban script-based credential replay except as a temporary bridge with strict controls (time-bounded, protected storage, code review, monitoring).   
- Strong reset process: SG4 emphasizes voice/social engineering attacks often push victims to reset passwords; it explicitly advises “never give out or change passwords based on voice-only communications.”
CISSP - Study Guide - 4th Editi…   
    CISSP - Study Guide - 4th Editi…   
   
### (5) Evidence & verification   
- Endpoint audits: which apps store credentials; where; who can read t
CISSP - Study Guide - 4th Editi…   
    CISSP - Study Guide - 4th Editi…
f: documented restrictions on credential storage and script-based SSO.   
   
### (6) Real examples   
- Windows: credential providers + Credential Manager flows (CBK4) as the OS-level credential reality every enterprise inherits.
Official Guide To CISSP CBK - 4…   
    Official Guide To CISSP CBK - 4…   
 --- 
   
## 3) Authentication factors (and how to engineer them like a system)   
### (1) De   
Official Guide To CISSP CBK - 4…   
Official Guide To CISSP CBK - 4…
ing you have\*\* (smartcard/token/phone),   
- **something you are** (biometric)
Official Guide To CISSP CBK - 4…   
   
Multi-factor authentication uses more than one type at the point of login for higher assurance.
Official Guide To CISSP CBK - 4…   
Official Guide To CISSP CBK - 4…
ogram is not “turn on MFA.” It’s:   
- enrollment controls (proofing),   
- authenticator lifecycle man
Official Guide To CISSP CBK - 4…
itive actions),   
- fallback rules (and minimizing fallback abuse).   
   
**Tokens (Type 2)**: SG4 details token device types (static vs dynamic one-time) and emphasizes they are “something you have.”
CISSP - Study Guide - 4th Editi…   
Operationally: tokens are only as strong as their **binding** and **recovery process**.   
**Biometrics**: SG4 provides the right technical model:   
- biomet
CISSP - Study Guide - 4th Editi…
,   
- biometrics used for **authentication** = one-to-one match.
CISSP - Study Guide - 4th Editi…   
    It also covers accuracy: FRR (Type 1) vs FAR (Type 2) and CER/EER as the comparison point across devices.
CISSP - Study Guide - 4th Editi…   
    CISSP - Study Guide - 4th Editi…
ry is the real attack surface\*\*: weak “lost phone” recovery breaks strong MFA.   
- \*\*Biometric operational fai
CISSP - Study Guide - 4th Editi…
tuning leads to false rejects/accepts (SG4’s FRR/FAR reality).
CISSP - Study Guide - 4th Editi…   
- **Token replay or session theft**: if you don’t manage sessions, strong login can still be bypassed.   
   
### (4) Controls & mitigations   
- Enforce phishing-r
CISSP - Study Guide - 4th Editi…
in many orgs; aligns with NIST emphasis on authenticator characteristics and phishing resistance at appropriate AALs).   
- Protect enrollment and reset with strong proofing and callbacks/verification practices (SG4’s voice-security guidance maps directly).
CISSP - Study Guide - 4th Editi…   
    CISSP - Study Guide - 4th Editi…   
- Biometric governance: tune thresholds per use case; monitor FAR/FRR operational impact; require fallback that isn’t trivially abused.   
   
### (5) Evidence & verification   
- MFA coverage metrics: % of users, %
CISSP - Study Guide - 4th Editi…   
    CISSP - Study Guide - 4th Editi…
lines and operational error tracking (help desk tickets are an evidence source).   
- Auth log review: unusual challenges, repeated failures, mass re-enroll attempts.   
   
### (6) Real examples   
- Physical access vs logical access: biometric identification is common at doors; biometric authentication common for workstation unlock. SG4’s one-to-many vs one-to-one distinction tells you which is which.
CISSP - Study Guide - 4th Editi…   
 --- 
   
## 4) Single Sign-On (SSO): the productivity control that can become the “keys to the kingdom”   
### (1) Definition + purpose   
SSO: one primary authentication event gives access to many resources in a session. SG4 and the Prep Guide both highlight the defini
CISSP - Study Guide - 4th Editi…
nage,   
- but compromise of the initial logon can yield broad access.
CISSP - Study Guide - 4th Editi…   
    The CISSP Prep Guide - Gold Edi…   
   
### (2) Enterprise implementation (real patterns)   
You see three broad SSO patterns across enterprises (all in the books, implicitly or explicitly):   
**A) Ticket-based / authentication server SSO**   
Kerberos
CISSP - Study Guide - 4th Editi…   
The CISSP Prep Guide - Gold Edi…
servers and encrypted tickets.
The CISSP Prep Guide - Gold Edi…   
**B) Web SSO via cookies / reverse proxy**   
The Prep Guide explicitly describes nonpersistent encrypted cookies and reverse proxy credential presentation patterns.
The CISSP Prep Guide - Gold Edi…   
**C) Script-based “SSO simulation”**
The CISSP Prep Guide - Gold Edi…
ts that replay credentials; both warn they can contain clear-text creds and must be protected.
CISSP - Official Study Guide - …   
Official Guide To CISSP CBK - 4…   
The CISSP Prep Guide - Gold Edi…   
### (3) Failure modes / abuse paths   
- **SSO blast radius**: SG4 calls it directly—once compromised, a malicious subject can gain “unrestricted access.”
CISSP - Study Guide - 4th Editi…   
    CISSP - Official Study Guide - …   
    Official Guide To CISSP CBK - 4…
entials.
Official Guide To CISSP CBK - 4…   
- **SSO availability bottleneck**: IdP/SSO outage becomes enterprise outage (opera
CISSP - Study Guide - 4th Editi…
ependence patterns).   
   
### (4) Controls & mitigations   
- Reduce blast radius: step-up authentication for sensitive acti
Official Guide To CISSP CBK - 4…
ect IdP signing keys, enforce MFA for admin, restrict token lifetimes (session management).   
- Scripted SSO exceptions: treat as temporary technical debt with documented compensating controls and expiry.   
   
### (5) Evidence & verification   
- SSO access logs (who got a session, when, what risk signals).   
- Audit of scripts/automation stores (search for clear-text secrets).   
- IdP resilience tests: failover drills, redundancy validation.   
   
### (6) Real examples   
- Enterprise web gateway: reverse proxy injects downstream creds (legacy apps) while fronting with modern MFA/SSO (exactly the Prep Guide pattern).
The CISSP Prep Guide - Gold Edi…   
 --- 
   
## 5) Federation: crossing trust boundaries with assertions (SAML / OIDC)   
### (1) Definition + purpose   
Federation exists because different organizations (or domains) need a “common language” to exchange authentication/authorization information for SS
The CISSP Prep Guide - Gold Edi…   
CISSP - Official Study Guide - …   
OSG7 describes:   
- **SAML** as XML-based and used to exchange authentication and authorization information between federated orgs (often browser SSO).
CISSP - Official Study Guide - …   
- It also mentions **SPML** (provisionin
CISSP - Official Study Guide - …   
    CISSP - Official Study Guide - …   
   
Authoritative standards confirmation:   
- OASIS SAML technical overview describes SAML as an XML-based
CISSP - Official Study Guide - …
oss domain boundaries.   
- OpenID Connect Core defines the **ID Token** as
CISSP - Official Study Guide - …
in OIDC.   
   
### (2) Enterprise implementation (actual federation flows)   
At a systems level, federation is a *three-role* story:   
- **Principal (user)**   
- **Identity Provider (IdP)** issues signed assertions/tokens   
- **Service Provider / Relying Party (SP/RP)** consumes assertions/tokens and enforces authorization   
   
AIO8 exam scenario language uses exactly those roles (principal, IdP, SP) in federation examples, showing how federated portals enable cross-company access.
CISSP - All In One Exam Guide -…   
Operationally, federation adds two critical engineering requirements:   
- **Assertion integrity** (signing, key rollover, metadata correctness)   
- **Audience/recipient binding** (token meant for App A must not be usable for App B)   
   
### (3) Failure modes / abuse paths   
- **Assertion acceptance bugs**: SP accepts unsigned/incorrectly scoped assertions.   
- **Key rollover outages**: OSG7 notes certificate chain changes/expiry can break trust until
CISSP - All In One Exam Guide -…
ion signing keys).
CISSP - Official Study Guide - …   
- **Provisioning drift**: federation gives “authN,” but if provisioning/roles aren’t synchronized, authorization is wrong.   
   
### (4) Controls & mitigations   
- Strong metadata governance: sign metadata, validate endpoints, monitor changes.   
- Constrain tokens: short lifetimes, correct audience, correct issuer.   
- Separate federation assurance: NIST formalizes FAL levels and requirements for securing federation transactions.   
   
### (5)   
CISSP - Official Study Guide - …
nt/app identifiers, auth method used.   
- SP logs: assertion validation results (issuer, audience, signature).   
- Key management evidence: rotation schedule, rollover tests, emergency revoke procedures.   
- Provisioning evidence: SPML/SCIM-style provisioning logs + role mapping diffs (SPML referenced by OSG7 as a provisioning exchange approach).
CISSP - Official Study Guide - …   
   
### (6) Real examples   
- SAML for B2B SaaS SSO (browser-based) is exactly the “common language” story OSG7 describes.
CISSP - Official Study Guide - …   
    CISSP - Official Study Guide - …   
- OIDC for modern apps: ID Token JWT carries authentication claims (OpenID Connect).   
 --- 
   
## 6) IDaaS (Identity as a Service): SSO for cloud + governance as a service   
### (1) Definition + purpose   
OSG7 defines IDaaS (Identity as a Service / Identity and Access as
CISSP - Official Study Guide - …
d—especially when internal clients access SaaS apps.
CISSP - Official Study Guide - …   
CBK
CISSP - Official Study Guide - …   
CISSP - Official Study Guide - …
dmin, authentication/authorization, and reporting; and it breaks functionality into governance/admin, access, and intelligence/logging/reporting.
Official Guide To CISSP CBK - 4…   
### (2) Enterprise implementation   
What IDaaS actually becomes in enterprises:   
- Central IdP for SaaS (SSO, MFA, conditional access)   
- Provisioning connector hub (joiner/mover/leaver to SaaS apps)   
- Central audit surface (“who accessed what and when?”)   
   
OSG7 provi
CISSP - Official Study Guide - …
identity, and enterprise integration using third-party IDaaS integrated with AD (example: Centrify integration).
CISSP - Official Study Guide - …   
### (3) Failure modes / abuse paths   
- Misconfigured connectors cause overprovisioning.   
- “Shado
Official Guide To CISSP CBK - 4…
becomes the availability bottleneck for SaaS usage.   
- Poor reporting = you can’t answer “who accessed what and when?” (CBK4 flags reporting as a core function).
Official Guide To CISSP CBK - 4…   
   
### (4) Controls & mitigations   
- Treat IDaaS as Tier-0-like critical dependency: redundancy, strict admin controls, key protection.   
- Enforce SaaS onboarding: every SaaS must integrate with central IdP and provisio
CISSP - Official Study Guide - …
explicitly (IGA + access + intelligence) per CBK4’s functional model.
Official Guide To CISSP CBK - 4…   
   
### (5) Evidence & verification   
- Provisioning logs: created/updated/deleted accounts per SaaS   
- Access logs: auth method, device/risk posture, app accessed   
- Reporting outputs: “who acce
Official Guide To CISSP CBK - 4…   
    Official Guide To CISSP CBK - 4…   
   
### (6) Real examples   
- Office 365 enterprise: AD-integrated SSO and SaaS access without re-login, exactly as described in OSG7.
CISSP - Official Study Guide - …   
 --- 
   
## 7) Session management (the part that decides whether “login security” matters)   
### (1) Defini   
Official Guide To CISSP CBK - 4…
uthenticated context remains valid and preventing someone else from riding that context.   
OSG7 explicitly connects session management to preventing unauthorized access and gives both desktop and online session examples.
CISSP - Official Study Guide - …   
Official Guide To CISSP CBK - 4…
implementation   
**Desktop sessions**: idle lock + re-authentication.   
- OSG7 explains screen savers with password-protect that force
CISSP - Official Study Guide - …   
    CISSP - Official Study Guide - …   
   
**Online sessions**: inactivity timeouts and explicit logoff.   
- OSG7 describes bank sessions terminating after inactivity and warns that failing to implement automatic logoff can leave sessions open even if a user closes a tab—creating takeover risk.
CISSP - Official Study Guide - …   
   
### (3) Fai   
CISSP - Official Study Guide - …
describe hijacking as an attacker taking over the session and assuming identity; one technique includes using cookie data when the user didn’t properly close connection.
CISSP - Official Study Guide - …   
CISSP - Official Study Guide - …   
CISSP - Study Guide - 4th Editi…   
- Weak session identifiers or long-lived sessions (web apps) → takeover risk (also echoed in practice-oriented material).   
   
### (4) Controls & mitigations   
- Idle timeouts + absolute timeouts.   
- Cookie/session protections: expire cookies
CISSP - Official Study Guide - …
s (SG4/OSG7 both tie mitigation to cookie expiration and anti-replay).
CISSP - Official Study Guide - …   
    CISSP - Study Guide - 4th Editi…   
- Secure development discipline: implement robust session
CISSP - Official Study Guide - …   
    CISSP - Study Guide - 4th Editi…
settings.   
- App logs showing session creation, refresh, termination.   
- Incident review artifacts: session takeover investigations should be reconstructable.   
   
### (6) Real examples   
- Banking-style inactivity logoff pattern (OSG7) is the “gold standard” user expectation and security baseline for sensitive services.
CISSP - Official Study Guide - …   
    CISSP - Official Study Guide - …   
    CISSP - Study Guide - 4th Editi…
ster at expert level:   
- **Kerberos** (TGT/TGS/service tickets, lifetimes, time sync dependency, KDC as SPOF)
CISSP - Official Study Guide - …   
    CISSP - Official Study Guide - …   
- **LDAP directory role + auth binds** (what directory services expose and how authorization ties to privilege)
CISSP - Study Guide - 4th Editi…   
- **RADIUS/TACACS+ and AAA separation** (remote access and network admin control-plane)   
   
> Identity Protocol Internals   

**Kerberos · LDAP (directory + binds) · RADIUS/TACACS+ (AAA)**   
 --- 
# 1) Kerberos (ticket-based authentication + SSO) — internals you must “see in your head”   
## (1) Definition + purpose (control objective)   
Kerberos is the canonical **ticket authentication** system: a trusted third party (the KDC) proves identities and issues tickets that allow clients to authenticate to services without re-sending passwords on the network. OSG7 emphasizes Kerberos as the most common ticket system and that it provides SSO protection for logon credentials using symmetric crypto (Kerberos 5 using AES) and helps resist eavesdropping/replay of authentication traffic.
CISSP - Official Study Guide - …   
\*\*Control objective:\*\**A subject authenticates once to obtain a time-limited ticket (TGT), then uses time-limited service tickets (STs) to authenticate to specific services, with centralized key control and measurable, auditable operations.*   
 --- 
## (2) Internals / mechanics (the actual “dance”: AS → TGS → Service)   
### A) Realm/KDC fundamentals (who knows which keys)   
AIO8 describes the KDC as the trusted authentication server for all principals in a **realm**, holding information about each principal and delivering keys and tickets.
CISSP - All In One Exam Guide -…   
The CISSP Prep Guide explains the same architecture: centralized servers implement KDC/TGS/AS because endpoints and cabling are not assumed secure; the KDC knows secret keys of clients and servers and issues temporary session keys.
The CISSP Prep Guide - Gold Edi…   
**Key mental model (must be automatic):**   
- Client↔KDC share a secret (derived from password or stored key material)   
- Service↔KDC share a secret (service key)   
- Client and service do **not** share a key initially; the KDC brokers a **session key**.   
   
### B) Ticket chain: “TGT is a passport; ST is your visa”   
CBK4 is very explicit:   
- The user authenticates once and receives a **TGT** and doesn’t re-authenticate while it’s valid.   
- The TGT **does not grant access** by itself; it only allows legitimate requests for service tickets—analogous to a passport that proves citizenship but doesn’t guarantee entry.   
- The **service ticket (ST)** is what the client presents to the target service; access still depends on the service’s authorization rules.
Official Guide To CISSP CBK - 4…   
   
### C) The step sequence (protocol-accurate, defender view)   
AIO8 gives a clear walkthrough:   
1. User enters creds → client contacts the **Authentication Service (AS)** on the KDC → receives a **TGT** encrypted for the TGS.
CISSP - All In One Exam Guide -…   
2. Client uses TGT to request service access from **TGS**, which returns a service ticket containing session key material protected for both client and service.
CISSP - All In One Exam Guide -…   
3. Client presents service ticket + authenticator to service; service validates it and proceeds.
CISSP - All In One Exam Guide -…   
   
OSG7 adds the critical operational details you must track:   
- TGT contains a symmetric key, expiration time, and user IP address; tickets have lifetimes and must be renewed or reissued when expired.
CISSP - Official Study Guide - …   
 --- 
   
## (3) Enterprise implementation (how Kerberos is run at scale)   
### A) Kerberos depends on directory data   
OSG7 notes Kerberos requires a database of accounts (often in a directory service).
CISSP - Official Study Guide - …   
### B) KDC resilience and AD reality   
CBK4 highlights two “real program” truths:   
- Kerberos is time-sensitive and often requires synchronized time infrastructure (NTP) to avoid auth failures.   
- KDC is a potential single point of failure; you need backup/continuity plans.   
- In Microsoft AD DS environments, **all domain controllers are KDCs**, reducing KDC SPOF risk.
Official Guide To CISSP CBK - 4…   
   
### C) “Kerberizing” apps is a real integration cost   
CBK4 calls out that integrating Kerberos into apps requires embedding system calls/libraries—“kerberizing”—and that this can be a roadblock.
Official Guide To CISSP CBK - 4…   
**Enterprise design consequence:** you will run mixed environments:   
- Some apps use Kerberos natively (Windows-integrated)   
- Some rely on LDAP binds or web federation   
- Some legacy apps are fronted by gateways/proxies   
 --- 
   
## (4) Failure modes / attack paths (how Kerberos breaks in practice)   
CBK4 gives the most operationally important failure points:   
- **Time sync failures** cause authentication failures and can be an attractive DoS vector.
Official Guide To CISSP CBK - 4…   
- **Ticket lifetime policy matters**: short lifetimes reduce replay risk; too long increases exposure; too short may cause operational pain.
Official Guide To CISSP CBK - 4…   
- **KDC hardening matters**: KDC should be physically secured and hardened (avoid non-Kerberos activity).
Official Guide To CISSP CBK - 4…   
- **Password-anchored weakness**: Kerberos ultimately depends on passwords (or equivalent secrets), making it vulnerable to password-guessing class attacks if password hygiene is weak.
Official Guide To CISSP CBK - 4…   
   
OSG7 also reminds you Kerberos is *primarily authentication* (not “logging/accountability”).
CISSP - Official Study Guide - …   
 --- 
## (5) Controls & mitigations (prevent/detect/respond/recover)   
**Prevent**   
- Enforce ticket lifetimes appropriate to risk (shorter for privileged contexts).
Official Guide To CISSP CBK - 4…   
- Harden and physically secure KDC/DCs; minimize non-essential services.
Official Guide To CISSP CBK - 4…   
- Strong password policy because Kerberos roots in password-derived secrets.
Official Guide To CISSP CBK - 4…   
   
**Detect**   
- Detect time drift and authentication failures at scale (because time issues can manifest as widespread Kerberos failures).
Official Guide To CISSP CBK - 4…   
- Monitor DC/KDC health and ticket issuance anomalies (trend-based).   
   
**Respond**   
- Contain KDC/DC incidents like “Tier-0” incidents: isolate, preserve evidence, restore from known-good, rotate secrets as required.   
   
**Recover**   
- Restore time sync infrastructure; validate Kerberos flows; verify DC redundancy.   
 --- 
   
## (6) Evidence & verification (proof pack)   
What you should be able to prove:   
- **Time sync health**: monitored skew and incident history (Kerberos is time-sensitive).
Official Guide To CISSP CBK - 4…   
- **KDC resilience**: multiple DC/KDCs and tested continuity (KDC SPOF risk).
Official Guide To CISSP CBK - 4…   
- **Ticket policy**: documented lifetimes and renewal practices (tickets have lifetimes and usage parameters).
CISSP - Official Study Guide - …   
   
**Real example (Windows/AD internals)**   
Windows Internals shows how AD and Kerberos are anchored in system components: Kerberos KDC service code (Kdcsvc.dll) and related authentication components run within LSASS, and AD DS is backed by the NTDS database (Ntds.dit) and ESE/Esent.dll.
Windows Internals Part 1\_6th Ed…   
Windows Internals Part 1\_6th Ed…   
 --- 
# 2) LDAP (directory services + authentication binds) — the identity database and query protocol   
## (1) Definition + purpose (control objective)   
CBK4 explains LDAP as the “lightweight” directory access protocol developed because X.500/DAP were complex and OSI-stack bound; LDAP provides directory services in TCP/IP environments.
Official Guide To CISSP CBK - 4…   
LDAP is both:   
- a **data model** (hierarchical entries, DN/RDN, attributes), and   
- a **protocol** to query/modify that directory.   
   
\*\*Control objective:\*\**Directory data is authoritative and consistent; authentication to the directory is protected; and directory authorization reveals only what subjects are permitted to see or change.*   
OSG7 frames an LDAP directory as a “telephone directory for network services and assets,” where subjects authenticate to query, and even then only see information based on assigned privileges.
CISSP - Official Study Guide - …   
 --- 
## (2) Internals / mechanics (tree, DN/RDN, operations, ports, and bind types)   
### A) Structure: DN/RDN and attribute model   
CBK4: LDAP uses a hierarchical tree structure; entries have DN/RDN; attributes are name/value pairs (DN, CN, DC, OU).
Official Guide To CISSP CBK - 4…   
### B) Client/server operations   
CBK4 lists standard client requests: connect/disconnect, search, compare, add/delete/modify directory info.
Official Guide To CISSP CBK - 4…   
### C) Ports and transport security   
CBK4: LDAP typically runs over TCP **389** and LDAPv3 supports **TLS** for encrypting communications.
Official Guide To CISSP CBK - 4…   
### D) Bind types (why “simple bind” is a recurring enterprise incident cause)   
Active Directory, 5th Edition (deep practical tooling view) describes LDP binds and makes the critical security point:   
- **Simple bind has no mechanism to protect credentials** unless you use LDAP over SSL; otherwise credentials are sent in clear text.
Active Directory, 5th Edition   
    It also points out that if you want to encrypt with SSL, use the SSL option and port **636**.
Active Directory, 5th Edition   
 --- 
   
## (3) Enterprise implementation (how LDAP is used in IAM)   
### A) LDAP as centralized access control substrate   
OSG7: Many directory services are LDAP based; AD DS is LDAP based; directory services are central authorization databases supporting SSO capabilities.
CISSP - Official Study Guide - …   
CBK4: AD DS is an LDAP implementation providing central authentication/authorization enterprise-wide and can enforce organizational security/configuration policies in a uniform, auditable way.
Official Guide To CISSP CBK - 4…   
### B) LDAP + PKI   
OSG7 ties LDAP to PKI usage for certificate lifecycle queries.
CISSP - Official Study Guide - …   
### C) AD database internals (what runs where)   
Windows Internals states AD is the Windows LDAP directory implementation and is implemented as a database (%SystemRoot%\Ntds\Ntds.dit) managed by the directory service running in LSASS, backed by ESE/Esent.dll.
Windows Internals Part 1\_6th Ed…   
Windows Internals Part 1\_6th Ed…   
 --- 
## (4) Failure modes / attack paths   
1. **Cleartext credential exposure via simple binds** when TLS/LDAPS is not enforced.
Active Directory, 5th Edition   
2. **Directory abuse as “mapping engine”**: LDAP is a phonebook; if too much is readable, attackers (or insiders) gain high-quality targeting info (service locations, group structure, trust bridges). OSG7 explicitly notes the directory only reveals certain info based on privileges—meaning privilege design controls reconnaissance.
CISSP - Official Study Guide - …   
3. **Availability and integrity dependency**: the Prep Guide notes LDAP availability/integrity matter for PKI because DoS on LDAP can prevent CRL access and permit use of revoked certificates.
The CISSP Prep Guide - Gold Edi…   
 --- 
   
## (5) Controls & mitigations   
**Prevent**   
- Require LDAPv3 with TLS (LDAPS/StartTLS) for credentials and sensitive queries.
Official Guide To CISSP CBK - 4…   
    Active Directory, 5th Edition   
- Restrict anonymous and overly-broad read access; apply least privilege to directory queries and attribute visibility (OSG’s “reveal only certain information” becomes a policy requirement).
CISSP - Official Study Guide - …   
   
**Detect**   
- Alert on insecure bind usage (simple binds without encryption) and on unusual query volume patterns.   
- Monitor directory integrity changes (schema, privileged groups, sensitive attributes).   
   
**Respond**   
- If insecure binds detected: enforce TLS, rotate exposed credentials, and validate app compatibility.   
- If directory integrity compromised: treat as identity-plane incident (Tier-0 response).   
   
**Recover**   
- Restore directory services from known-good backups/replication; validate policy and access controls; re-issue impacted credentials/certs if required.   
 --- 
   
## (6) Evidence & verification (proof pack)   
- Evidence that binds are protected: LDAPS usage and that simple binds without SSL are not used.
Active Directory, 5th Edition   
- Evidence directory access is least-privilege: sampling of attribute visibility and change authorization.   
- Evidence PKI dependency resilience: LDAP/CRL availability monitoring (DoS on LDAP affecting CRL access is a known concern).
The CISSP Prep Guide - Gold Edi…   
   
**Real example**   
- Using LDP: connect to DC and bind; enabling SSL uses port 636; simple bind without SSL exposes credentials.
Active Directory, 5th Edition   
    Active Directory, 5th Edition   
 --- 
   
# 3) RADIUS vs TACACS+ (AAA) — remote access and network device control-plane security   
## (1) Definition + purpose (control objective)   
OSG7 frames centralized remote authentication services (RADIUS/TACACS+) as adding a security layer between remote clients and private networks, and emphasizes separation from LAN/local authentication: if these servers are compromised, only **remote connectivity** is affected.
CISSP - Official Study Guide - …   
\*\*Control objective:\*\**Remote access and network-device administration are centrally authenticated and authorized with auditable accounting, and compromise is contained to the remote access boundary.*   
 --- 
## (2) Internals / mechanics (AAA pipeline and protocol properties)   
### A) RADIUS architecture (who is “client”, who is “server”)   
OSG7: in RADIUS architecture, the **network access server** acts as the client and the RADIUS server is the authentication server providing AAA.
CISSP - Official Study Guide - …   
### B) What RADIUS protects (and what it doesn’t)   
AIO8 provides a key security property:   
- RADIUS encrypts the **user’s password** only in transit from client to server, but other data (username, accounting, authorized services) can be passed in cleartext, enabling capture/replay risks; TACACS+ encrypts all data between client and server.
CISSP - All In One Exam Guide -…   
   
CBK4 adds a “risk register” style assessment of RADIUS:   
- subject to replay attacks,   
- lacks integrity protection,   
- encrypts only specific fields.
Official Guide To CISSP CBK - 4…   
   
### C) TACACS/TACACS+/XTACACS evolution and AAA separation   
Both OSG7 and AIO8 explain the generational split:   
- TACACS integrates authentication and authorization   
- XTACACS separates authentication, authorization, accounting   
- TACACS+ improves XTACACS (OSG7: adds two-factor; AIO8: supports dynamic one-time passwords)
CISSP - Official Study Guide - …   
    CISSP - All In One Exam Guide -…   
   
AIO8 also gives a very practical differentiator:   
- TACACS+ can define more granular profiles (even down to which commands a user can run on network devices).
CISSP - All In One Exam Guide -…   
   
### D) Transport choice matters operationally   
AIO8 notes:   
- TACACS+ uses TCP, while RADIUS uses UDP; UDP-based systems must handle packet loss/corruption concerns in application logic.
CISSP - All In One Exam Guide -…   
 --- 
   
## (3) Enterprise implementation (how AAA is deployed for real)   
### Use case A: Remote user access (RAS/VPN/Wi-Fi)   
- Central AAA backs VPN concentrators and wireless controllers   
- Accounting logs become your attribution trail   
   
### Use case B: Network device administration   
- TACACS+ often preferred where command-authorization and full payload protection are needed (per AIO’s encryption and granularity points).
CISSP - All In One Exam Guide -…   
    CISSP - All In One Exam Guide -…   
   
### Integration with directory auth (single identity store)   
AIO8 explains the value of AAA separation: you can authenticate remote users against the same domain controller identity source (Kerberos-backed) rather than maintaining separate credential databases.
CISSP - All In One Exam Guide -…   
 --- 
## (4) Failure modes / attack paths   
- **RADIUS replay + integrity weaknesses** if not wrapped in additional protections (CBK4’s warning).
Official Guide To CISSP CBK - 4…   
- **Partial-field encryption** (AIO8): capturing accounting/authorized-services metadata can enable replay-style abuse or intelligence gathering.
CISSP - All In One Exam Guide -…   
- **AAA logs not centralized** → you lose accountability (especially catastrophic for network device admin).   
 --- 
   
## (5) Controls & mitigations   
**Prevent**   
- Choose protocol based on risk:   
    - prefer TACACS+ when you need full session confidentiality and granular command authorization.
CISSP - All In One Exam Guide -…   
        CISSP - All In One Exam Guide -…   
    - if RADIUS is used, explicitly compensate for replay/integrity weaknesses (CBK4).
Official Guide To CISSP CBK - 4…   
- Strong shared-secret management and rotation; isolate AAA servers in protected segments (management plane).   
   
**Detect**   
- Monitor AAA anomalies: unusual logins, impossible travel, repeated failures, new device admin patterns.   
- Alert on changes to authorization profiles.   
   
**Respond**   
- Rapid disable of remote access paths (containment lever) and key rotation.   
- “Network device admin breach” response: revoke admin profiles, force re-auth, review command logs.   
   
**Recover**   
- Restore AAA configs from baselines; validate all network devices point to correct AAA servers; retest.   
 --- 
   
## (6) Evidence & verification (proof pack)   
- Proof of architecture roles: NAS is the RADIUS client; RADIUS server provides AAA.
CISSP - Official Study Guide - …   
- Evidence of encryption posture:   
    - if using RADIUS, document field-level encryption limitations and compensating controls.
CISSP - All In One Exam Guide -…   
        Official Guide To CISSP CBK - 4…   
    - if using TACACS+, show full payload protection and command authorization design.
CISSP - All In One Exam Guide -…   
- Evidence of accountability: centralized accounting logs retained and reviewed.   
   
> Implement and Manage Authorization Mechanisms   

# Access Control Technologies   
# Privileged Access Management   
# Manage Access Control Systems   
*(authorization concepts · enforcement patterns · PAM vault flows · account lifecycle · access reviews · monitoring)*   
### Chunk deliverables (for depth consistency)   
- **Internal flow walkthrough #1:** “AuthN succeeded → AuthZ decision → enforcement → audit” (policy decision pipeline)   
- **Internal flow walkthrough #2:** OAuth/OIDC token issuance + audience/issuer validation and where it fails   
- **Internal flow walkthrough #3:** PAM vault checkout → JIT elevation → session recording → revoke   
- **Failure story:** “SSO token theft + overbroad entitlements → silent lateral privilege”   
- **Evidence pack:** minimum artifacts to prove authorization & PAM work continuously   
 --- 
   
# 1) Authorization Concepts (AuthZ is a decision + an enforcement action, not a checkbox)   
## (1) Definition + control objective   
**Authorization** is the process of determining whether an authenticated subject may perform a specific action on a specific object, under current conditions.   
\*\*Control objective:\*\**Every sensitive action is governed by an explicit policy; the enforcement point is correct and non-bypassable; and decisions are attributable and reviewable.*   
The “above-CISSP” framing: **AuthZ has two parts** you must never conflate:   
1. **Decision** (policy evaluation)   
2. **Enforcement** (the point where access is actually allowed/blocked)   
   
You can have “perfect policy” and still fail if enforcement is bypassable (e.g., direct DB access around the gateway).   
## (2) Internals / mechanics (the policy decision pipeline)   
Think of the authorization pipeline as a deterministic machine:   
**Input context**   
- Subject attributes (user ID, groups/roles, risk score, device posture)   
- Object attributes (resource type, data classification, owner, sensitivity label)   
- Action (read/write/delete/admin/export)   
- Environment (network location, time, MFA state, session assurance)   
   
**Decision**   
- Policy engine evaluates (RBAC/ABAC/rules)   
- Produces decision: **Allow / Deny / Allow with obligations**   
    - *Obligation examples*: “log this access,” “require step-up MFA,” “mask data,” “watermark export,” “limit rate.”   
   
**Enforcement**   
- Must happen where bypass is difficult:   
    - application gate (best for semantic control)   
    - API gateway (good central choke point)   
    - OS enforcement (ACLs, privileges)   
    - database enforcement (least desirable as the only layer)   
    - network enforcement (coarse, but useful as backstop)   
   
**Audit**   
- Write “decision logs” (why it was allowed/denied) + “action logs” (what actually occurred)   
   
## (3) Enterprise implementation (where AuthZ lives in real orgs)   
You rarely have a single authorization system. You have **layers**:   
- **Platform / OS**: local privileges, file ACLs, kernel objects, admin rights   
- **Directory**: group membership and delegated admin   
- **Application**: roles/permissions, business rules (e.g., “approve payments < $10K”)   
- **Data plane**: row/column-level controls, tokenization gates, export controls   
- **Cloud control plane**: IAM policies for API calls   
   
Professional implementation pattern:   
- Use **RBAC** for base job function access   
- Use **ABAC / conditional access** for dynamic constraints (device compliant, risk score, location)   
- Use **explicit denies** for toxic combinations and high-risk actions   
- Maintain a **policy catalog** (what policies exist, owners, test cases, change control)   
   
## (4) Failure modes / abuse cases   
- **AuthN succeeded → but AuthZ overbroad**: the user is legitimately authenticated yet has “too much.” This is the most common breach pattern in mature identity environments (credential theft + entitlement sprawl).   
- **Enforcement bypass**: policy exists at gateway, but internal service is reachable directly.   
- **Policy precedence bugs**: allow rules override denies due to ordering or inheritance.   
- **Attribute integrity failure (ABAC)**: client-supplied attributes are trusted when they should be server-sourced (e.g., device posture spoofed).   
   
## (5) Controls & mitigations (prevent/detect/respond/recover)   
**Prevent**   
- “Policy + enforcement pairing” rule: every policy must name its enforcement point(s) and bypass controls.   
- Least privilege + SoD (separation of duties) baked into role design.   
- Normalize policy changes via change control and automated tests (policy-as-code).   
   
**Detect**   
- Decision logging: “why allowed?” (critical for incident reconstruction)   
- Entitlement drift detection: diffs of groups/roles/claims   
- Alert on “new access path” (new role grants, new group membership, new direct connectivity)   
   
**Respond**   
- Kill-switch for entitlements (rapid revoke of roles/groups)   
- Step-up policies tightened during incident (temporary elevation restrictions)   
- Token/session revocation (invalidate active sessions)   
   
**Recover**   
- Rebuild roles/policies from baseline catalogs   
- Close bypass paths (network segmentation + service mesh/gateway enforcement)   
   
## (6) Evidence & verification (proof pack)   
- Policy catalog + version history + owners   
- Decision logs (allow/deny reasons) + action logs (what occurred)   
- Access review records (who approved which role and why)   
- “Bypass tests” proving you cannot reach the resource without the enforcement point   
- KPIs/KRIs:   
    - KPI: % of sensitive actions covered by decision logging + enforcement   
    - KRI: growth of privileges per user / # of direct-to-data paths   
 --- 
   
# 2) Access Control Technologies (how authorization is represented and enforced)   
This section unifies the CBK/OSG/SG/AIO “control technologies” into the real mechanisms you will encounter.   
## 2.1 Access Control Matrix, ACLs, and “effective access”   
### (1) Definition + purpose   
- **Access control matrix**: conceptual model mapping subjects → objects → permitted actions.   
- **Access Control Lists (ACLs)**: the practical implementation where objects store “who can do what.”   
   
\*\*Control objective:\*\**Permissions are predictable, reviewable, and computable as “effective access.”*   
### (2) Internals / mechanics   
“Effective access” is the actual evaluation result after:   
- group membership expansion (nested groups)   
- inheritance (parent → child objects)   
- explicit denies vs allows precedence   
- conditional constraints (claims/attributes)   
- local vs domain policy interactions   
   
This is where many orgs fail: they review *intended permissions* rather than *effective permissions*.   
### (3) Enterprise implementation   
- Standardize group strategy (“role groups” vs “resource groups”).   
- Avoid direct user-to-object permissions; use groups/roles for reviewability.   
- Use periodic effective access sampling on crown jewels (sensitive shares, finance apps, admin consoles).   
   
### (4) Failure modes / abuse cases   
- Inheritance creates “accidental broad access.”   
- Orphaned permissions remain after org changes.   
- Nested groups become unreviewable without tooling.   
- “Everyone/Authenticated Users” style broad groups creep into sensitive ACLs.   
   
### (5) Controls & mitigations   
- Permission standards + templated ACLs for common resource types   
- Automated access review tooling that computes effective access   
- Time-bound access grants (especially to sensitive data)   
   
### (6) Evidence & verification   
- ACL baselines + deviations report   
- Effective access reports for sampled resources   
- Change logs for permissions (who changed what, when, ticket)   
 --- 
   
## 2.2 Capability-based access control (less common, extremely powerful)   
### (1) Definition + purpose   
A **capability** is an unforgeable token/handle that grants the holder specific rights to an object (think: “possession of a specific capability is authorization”).   
\*\*Control objective:\*\**Authorization is embodied in unforgeable, least-privilege tokens that can be constrained and audited.*   
### (2) Internals / mechanics   
Capabilities shift the question from “is Alice allowed?” to “does this process/session possess the right capability?”   
This is powerful in distributed systems, but revocation and auditing become design-critical.   
### (3) Enterprise implementation   
- API access tokens with scoped permissions resemble capabilities.   
- Service meshes and internal gateways often approximate capability-based control (service identity + scoped permission sets).   
   
### (4) Failure modes / abuse cases   
- Token leakage becomes equivalent to privilege leakage.   
- Weak revocation means stolen capability remains valid.   
   
### (5) Controls & mitigations   
- Short lifetimes, rotation, and revocation   
- Token scoping and audience restriction   
- Binding tokens to strong identities and sessions   
   
### (6) Evidence & verification   
- Token issuance logs, scope/audience logs, revocation events   
- Proof tokens are short-lived and rotated   
 --- 
   
# 3) Authorization in modern web ecosystems (OAuth/OIDC) — deep flow + correct safety checks   
This is the “helped source” area where modern reality goes beyond older CISSP books’ era, but it directly supports Domain 5’s **SSO/federation/authorization** goals.   
## (1) Definition + control objective   
OAuth-family systems allow clients to obtain limited access to resources through an authorization service, typically using tokens. OAuth 2.1 is explicitly an effort to consolidate and simplify OAuth 2.0 best practices, and the IETF draft states it is intended to replace/obsolete OAuth 2.0 core specs RFC 6749 and RFC 6750 (status: still a draft, but authoritative direction).   
\*\*Control objective:\*\**Tokens are correctly scoped, correctly audience-bound, correctly issuer-validated, short-lived, and revocable; authorization is least-privilege by design; and token misuse is detectable.*   
## (2) Internals / mechanics (flow walkthrough: issuance → validation → enforcement)   
### Internal flow walkthrough #2 (defender view)   
**Actors**   
- Resource Owner (user)   
- Client (app)   
- Authorization Server (AS)   
- Resource Server (API/service)   
   
**1) Authorization + token issuance**   
- Client requests authorization; AS authenticates user and applies policy.   
- AS issues tokens (access token; often refresh token).   
   
**2) Token presentation**   
- Client sends access token to resource server.   
   
**3) Validation (must happen server-side)**   
Resource server (or gateway) validates:   
- token signature (if JWT) or introspection result (if opaque)   
- **issuer** (who minted it)   
- **audience** (who it’s intended for)   
- expiry and not-before times   
- scope/claims   
- any additional constraints (tenant, device binding)   
   
**4) Authorization decision**   
- Map scopes/claims to permissions   
- Apply ABAC constraints (risk, device, location)   
- Enforce decision; log   
   
### Why “issuer validation” matters (modern correction)   
RFC 9207 introduces an `iss` parameter in authorization responses to mitigate “mix-up attacks” by explicitly identifying the authorization server issuer.   
This is a modern “above CISSP” lesson: many token failures are **trust confusion** failures, not cryptography failures.   
## (3) Enterprise implementation   
- Standardize token patterns:   
    - JWT vs opaque tokens   
    - gateway validation vs service validation   
    - consistent claims strategy   
- Central key management and rotation for signing keys   
- Scope design discipline:   
    - “read:orders” is not “admin”   
    - avoid wildcard scopes   
- Continuous auditability:   
    - token issuance logs (who, what client, what scopes)   
    - resource access logs (what was accessed, token subject)   
   
## (4) Failure modes / abuse cases   
- **Audience confusion**: token for service A accepted by service B.   
- **Issuer confusion**: accepting tokens minted by the wrong AS (multi-tenant confusion).   
- **Over-scoped tokens**: one token grants huge blast radius.   
- **Long-lived refresh tokens**: persistence and replay windows grow.   
- **Bearer token theft**: stolen token = stolen access (capability leakage).   
   
## (5) Controls & mitigations   
**Prevent**   
- Strict issuer + audience validation; adopt RFC 9207-style issuer identification controls where applicable.   
- Scope minimization + short lifetimes + refresh token protection   
- Centralized authorization policy and consistent enforcement points   
   
**Detect**   
- Token anomalies: unusual client IDs, unusual scope patterns, unusual geography/device   
- “Impossible concurrency” for token usage   
- Sudden spikes in token issuance for a user/client   
   
**Respond**   
- Token revocation + session kill; key rotation if signing key compromise suspected   
- Tighten scopes and require step-up auth for sensitive actions   
   
**Recover**   
- Re-baseline token policies and enforce consistent validation across services   
   
## (6) Evidence & verification   
- Token validation test suite (automated) proving issuer/audience checks are enforced   
- Key rotation drill evidence (signing key rollover without outage)   
- Logs:   
    - AS issuance logs   
    - API access logs with token subject + client + scopes   
- KPI/KRI:   
    - KPI: % services enforcing issuer+audi validation   
    - KRI: count of wildcard scopes and long-lived tokens   
 --- 
   
# 4) Privileged Access Management (PAM) — the enterprise “root control plane”   
## (1) Definition + control objective   
PAM is the control system governing **high-impact privileges** (domain admin, cloud admin, network admin, database admin, key management, break-glass accounts).   
\*\*Control objective:\*\**Privileged access is time-bound, least-privilege, mediated, recorded, reviewable, and quickly revocable—without relying on “trusting admins.”*   
## (2) Internals / mechanics (vault flows you must master)   
### Internal flow walkthrough #3: Vault checkout → JIT elevation → session recording → revoke   
**A) Request**   
- Admin requests privileged task (ticket + justification)   
- System evaluates policy (role, SoD constraints, time window, target scope)   
   
**B) Grant**   
Two dominant patterns:   
1. **Credential checkout (vault)**: vault releases a credential for limited time   
2. **JIT elevation**: system grants membership/role for limited time (preferred when possible)   
   
**C) Use**   
- Session broker/jump host enforces:   
    - MFA at the moment of privilege use (step-up)   
    - command filtering (on network devices, database consoles)   
    - session recording   
   
**D) Revoke**   
- Time expires or task ends → role removed / credential rotated   
- Audit record sealed and stored immutably   
   
**E) Post-check**   
- Review: high-risk sessions are reviewed; anomalies trigger investigation   
   
## (3) Enterprise implementation (real operating model)   
- Separate admin identities from daily identities   
- Tiered admin model (identity tier, server tier, workstation tier)   
- PAM integrates with:   
    - directory groups/roles   
    - cloud IAM roles   
    - network device AAA (TACACS+ with command authorization)   
    - SIEM + case management   
   
## (4) Failure modes / abuse cases   
- **Standing privilege**: permanent admin membership = inevitable misuse path.   
- **Shared admin credentials**: destroys accountability.   
- **No session recording**: you cannot prove what happened.   
- **Break-glass unmanaged**: emergency accounts become attacker persistence.   
   
Failure story (common):   
An attacker steals a user session, discovers the user has “temporary but never removed” admin entitlements, uses allowed admin channels, and there’s no PAM trail—only scattered endpoint logs. The incident becomes a forensic argument instead of a forensic proof.   
## (5) Controls & mitigations   
**Prevent**   
- Replace standing privileges with JIT grants   
- Separate admin workstations (hardened) and restricted admin network paths   
- Rotate privileged credentials automatically   
- Require MFA and session brokering for privileged actions   
   
**Detect**   
- Alerts on privilege grants (especially outside normal windows)   
- Alerts on “new admin path” usage (new device, new location, unusual targets)   
- Session recording review triggers for sensitive actions   
   
**Respond**   
- Kill sessions; remove privileges; rotate secrets   
- Validate no persistence (check all privileged group memberships and tokens)   
   
**Recover**   
- Rebuild PAM configurations; re-issue secrets; revalidate tiering and separation   
   
## (6) Evidence & verification   
- Privileged access inventory (who can become admin, why, approval)   
- JIT grant logs + expiry enforcement proof   
- Session recordings + command trails + immutable storage   
- Quarterly break-glass drills and audits:   
    - prove break-glass can be used when needed   
    - prove it is rotated and monitored afterward   
 --- 
   
# 5) Manage Access Control Systems (the lifecycle machine: accounts, provisioning, reviews)   
This is where “IAM becomes real” operationally.   
## 5.1 Administrative controls (policy + SoD + governance)   
### (1) Definition + control objective   
Administrative controls define the rules, approvals, SoD, and processes that constrain technical systems.   
\*\*Control objective:\*\**Access decisions are governed, repeatable, and auditable across joiner/mover/leaver events and exceptions.*   
### (2) Internals / mechanics   
- SoD rules are essentially “forbidden role combinations” and “approval constraints”   
- Exception handling is a controlled mechanism (owner + expiry + compensating controls)   
   
### (3) Enterprise implementation   
- Role catalog and SoD matrix   
- Access request workflow integrated with ticketing   
- Ownership model:   
    - data owner approves access   
    - security defines standards   
    - IAM team implements and monitors   
   
### (4) Failure modes   
- No SoD: fraud becomes easy.   
- No exception expiry: “temporary access” becomes permanent.   
   
### (5) Controls   
- SoD checks on each request and periodically   
- Mandatory attestation for sensitive access   
   
### (6) Evidence   
- SoD reports + approvals   
- Exception register with expiries and compensations   
 --- 
   
## 5.2 Account management (joiner/mover/leaver done right)   
### (1) Definition + control objective   
Account management ensures accounts are created, modified, and disabled correctly.   
\*\*Control objective:\*\**No orphaned accounts, no stale privileged accounts, and deprovisioning is provable.*   
### (2) Internals / mechanics   
- Identity objects tie to HR records   
- Mover events require both “grant new” and “remove old” (most orgs forget the second)   
   
### (3) Enterprise implementation   
- Automated provisioning from HR to directory/IdP   
- Separate process for service accounts and privileged accounts   
- Controlled break-glass accounts   
   
### (4) Failure modes   
- Orphan accounts (contractors, interns)   
- Privilege creep in movers   
- Shared service accounts   
   
### (5) Controls   
- Disable accounts on termination immediately   
- Scheduled audits for inactive accounts   
- Separate admin identities and policies   
   
### (6) Evidence   
- Termination disablement logs with timestamps   
- Inactive-account reports and remediation tracking   
- Privileged account inventory   
 --- 
   
## 5.3 Provisioning and deprovisioning (the entitlement “supply chain”)   
### (1) Definition + control objective   
Provisioning = granting access; deprovisioning = removing it. The security goal is minimizing time-to-grant (for productivity) while minimizing time-to-revoke (for safety).   
\*\*Control objective:\*\**Every access grant is justified and time-bound where appropriate; every access removal is fast and verifiable.*   
### (2) Internals / mechanics   
- Provisioning creates entitlements in multiple systems (directory groups, app roles, cloud roles)   
- Deprovisioning must remove all of them, including:   
    - group memberships   
    - app-specific roles   
    - API keys   
    - device certificates   
    - active sessions/tokens   
   
### (3) Enterprise implementation   
- IGA (Identity Governance & Administration) style workflows:   
    - request → approval → provisioning → verification   
- “Leaver pipeline” includes:   
    - disable identity   
    - revoke tokens   
    - rotate shared secrets if exposed   
    - reassign ownership of data/resources   
   
### (4) Failure modes   
- Deprovisioning misses one system → attacker persists   
- Token remains valid after disablement   
- Shared secrets unchanged after personnel exit   
   
### (5) Controls   
- Automated deprovisioning connectors (HR-driven)   
- Session revocation and key rotation policies   
- Periodic reconciliation (compare HR roster vs active accounts)   
   
### (6) Evidence   
- Provisioning logs (who approved, what granted, when)   
- Deprovisioning proof (disable events, entitlement diffs, token revocation logs)   
- Reconciliation reports (HR vs directory vs SaaS)   
 --- 
   
## 5.4 Review and monitoring (access reviews are the “audit heartbeat”)   
### (1) Definition + control objective   
Access reviews ensure ongoing least privilege and detect drift.   
\*\*Control objective:\*\**Entitlements remain correct over time and drift is detected early.*   
### (2) Internals / mechanics   
- Snapshot entitlements   
- Diff against previous period   
- Identify anomalies:   
    - new privileged grants   
    - toxic combinations   
    - inactive privileged accounts   
    - broad group membership expansions   
   
### (3) Enterprise implementation   
- Quarterly reviews for normal access; more frequent for privileged access   
- Separate reviews:   
    - user access reviews   
    - system/service account reviews   
    - privileged admin role reviews   
   
### (4) Failure modes   
- Reviews become “rubber stamp” (no real scrutiny)   
- Reviews focus on “accounts” not “entitlements”   
- No remediation tracking (findings never close)   
   
### (5) Controls   
- Require justification updates for re-approval   
- Tie reviews to SoD checks   
- Remediation SLAs with escalation   
   
### (6) Evidence   
- Signed/attested review records   
- Entitlement diff reports   
- Remediation tickets + proof of closure   
 --- 
   
# Minimal modern “helped source” addendum: Passkeys and portability (auth affects IAM ops)   
Passkeys are FIDO cryptographic credentials tied to a user’s account and unlocked by device biometrics/PIN; they’re designed to reduce phishing and password reuse.   
Operationally, passkeys introduce IAM lifecycle considerations (enrollment, device change, recovery, portability). Industry work on passkey portability (e.g., draft efforts around credential transfer) shows this remains an evolving operational area rather than “solved forever.”   
 --- 
## Consolidated Evidence Pack (what “above CISSP” IAM must produce)   
If you’re running authorization + PAM correctly, you can produce:   
1. **Policy catalog** (AuthZ policies, owners, versions, test cases)   
2. **Entitlement snapshots + diffs** (groups/roles/claims/policies over time)   
3. **SoD matrix + violations report + approvals**   
4. **PAM artifacts**: JIT grants, credential rotations, session recordings, break-glass drill evidence   
5. **Token evidence (web)**: issuer/audience validation tests, RFC 9207 issuer identification adoption where relevant   
6. **Lifecycle evidence**: joiner/mover/leaver timelines with disablement proof + token/session revocation proof   
7. **Monitoring**: alerts on new privileged grants, unusual admin sessions, and “new access paths”   
   
> Domain 5 as an Operating System   

**(Unified IAM architecture · end-to-end identity flows · lifecycle discipline · PAM as root control plane · monitoring & evidence · Domain 5 checkpoint)**   
This chunk is the “stitching layer”: it turns the Domain 5 topics from CBK4 + OSG7 + AIO8 + SG4 into a single, internally consistent **enterprise IAM operating model** you can actually run, measure, and defend.   
 --- 
# 1) The IAM Reference Architecture (what “good” looks like as a system)   
## (1) Definition + control objective   
An IAM program is a **distributed control system** that governs:   
- identity creation (who exists),   
- authentication (who is present now),   
- authorization (what they can do),   
- and accountability (what they actually did),   
    across employees, contractors, devices, workloads, and third parties.   
   
\*\*Control objective:\*\**Identity is authoritative; access is least-privilege and time-bound where needed; privileged activity is mediated; and everything is provable through logs, reviews, and diffs.*   
## (2) Internals / mechanics (the IAM control planes)   
You should visualize IAM as **five coupled planes**:   
1. **Authoritative Identity Source (People)**   
    HR/ERP (joiner/mover/leaver events) → the “truth” that drives lifecycle.   
2. **Directory / Identity Store (Objects + relationships)**   
    Users, groups, roles, devices, service principals; group nesting; delegated admin boundaries.   
3. **Identity Provider (Authentication + session issuance)**   
    MFA, device/risk policies, SSO sessions, token signing keys.   
4. **Authorization Surfaces (Enforcement points)**   
    OS ACLs, apps, APIs/gateways, cloud IAM, network device AAA, databases.   
5. **Governance + Evidence Plane (IGA + monitoring)**   
    Access requests/approvals, SoD checks, access reviews, entitlement diffs, privileged session records, retention, audit trails.   
   
If one plane is weak, the whole system lies. Example: perfect MFA doesn’t help if authorization is overbroad and nobody reviews entitlements.   
## (3) Enterprise implementation (how orgs deploy this)   
A mature enterprise runs IAM with these **foundational components**:   
- **Directory service** as the object graph (users/devices/groups/services)   
- **IdP / SSO** as the authentication and session authority for workforce and SaaS   
- **Federation** for cross-domain SSO (B2B, multi-tenant access)   
- **Provisioning/IGA** to automate joiner/mover/leaver and perform access reviews   
- **PAM** for privileged access: JIT elevation, vaulting, session recording   
- **AAA** (RADIUS/TACACS+) for network access and device administration   
- **Central logging + SIEM** with identity-aware correlation (the accountability plane)   
   
## (4) Failure modes / abuse cases   
- **Identity sprawl** (shadow accounts in SaaS, local accounts on servers) breaks offboarding and reviews.   
- **Entitlement drift** (movers keep old roles; contractors accumulate access).   
- **PAM bypass** (admins “just SSH directly,” no session recording).   
- **SSO blast radius** (one compromised identity = access everywhere).   
- **Token and session persistence** (you disable the account but sessions survive).   
   
## (5) Controls & mitigations   
**Prevent**   
- Make HR-driven lifecycle non-negotiable.   
- Gate privileged actions through PAM; separate admin identities.   
- Enforce “no unmanaged SaaS”: every app must integrate with SSO + provisioning.   
   
**Detect**   
- Entitlement diffs and anomaly detection (new privileged grants, SoD violations).   
- Auth anomalies (reset storms, impossible travel, unusual MFA patterns).   
- Privileged session monitoring (command sets, unusual targets).   
   
**Respond**   
- Fast revoke primitives: disable identity, revoke sessions/tokens, strip roles/groups, rotate secrets.   
- “Kill-switch” for privileged roles and break-glass containment.   
   
**Recover**   
- Restore from role catalogs + baseline policies; rebuild provisioning connectors; retest reviews.   
   
## (6) Evidence & verification   
Your “always-ready” proof set:   
- identity inventory (HR roster ↔ directory ↔ SaaS)   
- entitlement snapshots + diffs (weekly/monthly; privileged daily where needed)   
- access review and SoD records with remediation closure   
- PAM session records + JIT grant logs   
- IdP logs + token issuance logs   
- deprovisioning evidence (timestamped disablement + token/session revocation)   
 --- 
   
# 2) End-to-end identity flow walkthroughs (the internals you must master)   
## Flow A — Workforce SSO to SaaS (OIDC-style)   
### (1) Definition + control objective   
This is the “most common” modern enterprise experience: one login grants access to many apps.   
\*\*Control objective:\*\**Authentication is strong and risk-aware; tokens are correctly scoped; authorization is least privilege; sessions are bounded and revocable; and everything is logged.*   
### (2) Internals / mechanics (what actually happens)   
At a high level:   
- **Authentication result** is represented by an **ID Token** in OpenID Connect; the spec states the authentication result is returned in an ID Token and it contains claims such as issuer, subject, and authentication time.   
- **Authorization to call APIs** is typically via OAuth access tokens (scoped).   
   
OAuth 2.1 consolidates OAuth 2.0 improvements and explicitly states it **replaces/obsoletes** RFC 6749 and RFC 6750.   
**Where it breaks (the “above CISSP” checks):**   
- If a resource server doesn’t validate **issuer** and **audience**, tokens can be accepted in the wrong place.   
- RFC 9207 defines the `iss` parameter for issuer identification in authorization responses to mitigate mix-up attacks.   
   
### (3) Enterprise implementation (where you enforce)   
- Enforce **authentication policy** at IdP (MFA, device posture, risk).   
- Enforce **authorization policy** at the app/API gateway and/or the service.   
- Enforce **token validation** everywhere: issuer, audience, expiry, signature, scopes/claims.   
   
### (4) Failure modes / abuse cases   
- Token theft (bearer token = capability leak).   
- Over-scoped tokens (“admin” scopes everywhere).   
- Logout illusion: closing a tab doesn’t end sessions everywhere (session persistence).   
   
### (5) Controls & mitigations   
- Short lifetimes + revocation strategy.   
- Strict issuer/audience validation (and adopt issuer identification patterns where applicable).   
- Step-up authentication for sensitive actions (payments, admin changes).   
   
### (6) Evidence & verification   
- Token validation test suite results (per service).   
- IdP logs: auth method, device/risk, token issuance.   
- API logs: subject, client, scopes, decision outcome.   
- Drill: “disable user and prove tokens stop working within X minutes.”   
 --- 
   
## Flow B — Identity proofing vs authentication (assurance separation)   
### (1) Definition + control objective   
Identity proofing establishes *who the person is*; authentication proves *the person present now controls an authenticator*.   
NIST’s Digital Identity Guidelines explicitly cover proofing, authentication, and federation; SP 800-63-4 (final) is current authoritative guidance.   
\*\*Control objective:\*\**High privilege requires strong proofing and strong authenticators; low-risk access avoids unnecessary proofing while remaining secure.*   
### (2) Internals / mechanics   
The mature lens is “assurance levels,” not generic MFA:   
- stronger privilege → stronger proofing + stronger authenticators + stronger recovery controls   
- weaker privilege → minimal friction while keeping acceptable risk   
   
### (3) Enterprise implementation   
- Proofing workflow + evidence chain for privileged roles.   
- Re-issuance workflow for lost devices and re-enrollment.   
   
### (4) Failure modes   
- Weak helpdesk resets and re-enrollment become “identity backdoors.”   
- Fraudsters exploit proofing gaps to obtain valid credentials.   
   
### (5) Controls   
- Re-issuance requires re-verification proportional to privilege.   
- Strong recovery controls and monitoring.   
   
### (6) Evidence   
- Proofing audit trail, enrollment logs, recovery logs, exception register.   
 --- 
   
# 3) Authorization at scale (how you avoid role explosion and entitlement drift)   
## (1) Definition + control objective   
Authorization is the policy that determines what actions a subject can take.   
\*\*Control objective:\*\**RBAC provides stable job-function access; ABAC/conditional rules provide dynamic constraints; and privilege creep is continuously reversed.*   
## (2) Internals / mechanics   
The key mechanism for “not drifting into chaos” is **diff thinking**:   
- capture entitlements as snapshots   
- compute diffs over time   
- correlate diffs to HR events and tickets   
- automatically flag unexplained drift   
   
## (3) Enterprise implementation   
- Role catalog (RBAC) with owners and business meaning.   
- Attributes from authoritative sources (HR, device management) for ABAC.   
- Enforce “mover cleanup”: remove old roles automatically.   
   
## (4) Failure modes   
- Movers keep old access indefinitely.   
- Exceptions become permanent.   
- Policies exist but enforcement points are bypassable.   
   
## (5) Controls   
- Time-bound grants for sensitive access.   
- SoD checks on every request + periodic SoD audits.   
- Bypass tests: confirm you cannot reach resource directly without control point.   
   
## (6) Evidence   
- Entitlement diffs and review attestations.   
- SoD violation reports and remediation tickets.   
- Policy decision logs (“why allowed”) + action logs (“what happened”).   
 --- 
   
# 4) PAM as the root control plane (privileged access must be mediated)   
## (1) Definition + control objective   
PAM controls high-impact privileges: domain admin, cloud admin, network admin, DB admin, keys/secrets systems.   
\*\*Control objective:\*\**No standing privilege without justification; privileged sessions are recorded; secrets rotate; emergency access is controlled and audited.*   
## (2) Internals / mechanics (the minimum PAM flow)   
- Request → policy/SoD evaluation → JIT elevation or credential checkout   
- Session brokering → recording → command controls (where applicable)   
- Revoke → rotate → review   
   
## (3) Enterprise implementation   
- Separate admin identities + hardened admin workstations.   
- JIT where possible; vaulting where necessary.   
- “Break-glass” with strict monitoring and mandatory rotation after use.   
   
## (4) Failure modes   
- Shared admin accounts, unmanaged break-glass, direct-to-target admin without recording.   
- Standing membership in high-privilege groups.   
   
## (5) Controls   
- Tiering (identity tier vs server tier vs workstation tier)   
- JIT elevation + session recording + secret rotation   
- Alert on privilege grants and on “new admin path” usage   
   
## (6) Evidence   
- JIT grant logs + expiry enforcement   
- session recordings/command logs   
- break-glass drill artifacts   
- privileged access review cadence and closure proof   
 --- 
   
# 5) Logging & monitoring for IAM (accountability as an engineered subsystem)   
## (1) Definition + control objective   
Accountability means actions are traceable to identities with integrity and context.   
\*\*Control objective:\*\**Every privileged action and every sensitive data access is attributable, reconstructable, and reviewable.*   
## (2) Internals / mechanics (what must be logged)   
Minimum “identity telemetry” that makes investigations possible:   
- Auth events: success/failure, MFA method, risk signals   
- Token events: issuance, refresh, revocation, key rollover   
- Entitlement changes: group/role membership, policy edits, admin delegation changes   
- Privileged use: PAM checkouts, JIT grants, session start/stop, commands (where possible)   
- Access to sensitive resources: admin APIs, data exports, permission changes   
   
## (3) Enterprise implementation   
- Centralize logs (IdP, directory, PAM, apps, cloud audit logs).   
- Normalize identity keys (unique IDs, not just display names).   
- Protect log integrity (restricted write access, retention policies, immutable archives for critical logs).   
   
## (4) Failure modes   
- Logs exist but not correlated (no stable identifiers).   
- Tokens issued without sufficient audit trail (blind spot).   
- Entitlement changes not monitored (privilege escalation via “paper cuts”).   
   
## (5) Controls   
- Alerting on:   
    - privileged grants   
    - MFA enrollment changes   
    - password reset anomalies   
    - policy changes to IdP and token signing keys   
- Continuous reconciliation (HR roster ↔ active accounts).   
   
## (6) Evidence   
- Sample investigation: “show the full chain from auth → entitlement → action → outcome”   
- Retention proofs + access controls on logs   
- Monthly/quarterly audit packs   
 --- 
   
# Domain 5 Checkpoint (prove you’re “above CISSP” in IAM)   
### Required answer format   
1. Risk framing (CIA + business impact)   
2. Identity flow (who issues what, where enforcement lives)   
3. Controls (Prevent/Detect/Respond/Recover; admin/tech/physical)   
4. Evidence plan (logs, diffs, reviews, attestations)   
5. Cadence (continuous + periodic)   
6. Exception handling (time-bound + compensating controls)   
 --- 
   
## 10 scenarios   
1. **Helpdesk reset abuse risk**: attacker social-engineers a reset for a finance user. Design the reset and re-issuance policy + detection.   
2. **Mover privilege creep**: engineer changes teams; retains old production access. Build mover cleanup and review proof.   
3. **SSO compromise blast radius**: user’s session token stolen; attacker accesses multiple SaaS apps. Reduce blast radius and prove revocation works.   
4. **Federation trust confusion**: multi-tenant environment accepts tokens from wrong issuer. Design issuer/audience validation and monitoring. (Include RFC 9207 issuer identification rationale.)   
5. **Service account sprawl**: shared service credentials across many services. Move to managed identities / rotation strategy and prove it.   
6. **Privileged admin bypasses PAM**: engineer uses direct SSH/RDP to servers. Enforce PAM mediation and detect bypass.   
7. **Cloud IAM over-permission**: broad “admin” roles granted to engineers. Redesign into RBAC + ABAC constraints and run SoD checks.   
8. **Break-glass used during outage**: how do you allow emergency access but prevent it becoming permanent persistence? Include rotation and review evidence.   
9. **Token validation inconsistency**: some APIs validate signature but not audience; others validate audience but not issuer. Build a uniform validation program and test suite.   
10. **Deprovisioning failure**: contractor leaves; SaaS accounts remain active. Build reconciliation + automated deprovisioning + proof.   
 --- 
   
## Grading rubric (0–3 each)   
1. Flow accuracy (identity + token/session mechanics)   
2. Authorization correctness (least privilege + SoD)   
3. PAM rigor (JIT/recording/rotation/break-glass discipline)   
4. Evidence strength (logs + diffs + attestations)   
5. Operational cadence (continuous + periodic reviews)   
6. Resilience (IdP/PAM dependency risk + failover thinking)   
7. Exception discipline (time-bound + compensating controls)   
8. Incident readiness (revocation speed + reconstruction ability)   
   
**Interpretation**   
- 22–24: principal IAM architect/operator   
- 18–21: strong senior; minor gaps in proof/cadence   
- 14–17: solid practitioner; still “tool-driven”   
- <14: fragile; likely to fail audits/incidents   
 --- 
   
## Non-negotiables for Domain 5 mastery   
1. HR-driven identity lifecycle (joiner/mover/leaver) with reconciliation   
2. Strong reset/re-issuance controls (helpdesk is a high-risk control point)   
3. Explicit authorization catalog + enforcement-point mapping + bypass tests   
4. Privileged access mediated by PAM (JIT + recording + rotation)   
5. Continuous entitlement diffs + periodic access reviews + SoD checks   
6. Token/session revocation that is tested and measurable (not “best effort”)   
7. Identity telemetry centralized and usable for investigations   
   
>    

>    

   

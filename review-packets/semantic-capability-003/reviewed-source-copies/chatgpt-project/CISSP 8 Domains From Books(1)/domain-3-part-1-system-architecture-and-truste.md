---
# yaml-language-server: $schema=schemas\page.schema.json
Object type:
    - Page
Backlinks:
    - Books-Summary = CISSP 8-Domain (References)
Creation date: "2026-02-25T19:51:39Z"
Created by:
    - Perky Sparrow
id: bafyreibwaawek5a4ydeq6d62jgxjdd6qbxc4lvlammdgevh5avbdx3cj2i
---
# Domain 3 Part 1 — System architecture and Trusted Computing Base   
   
   
   
>    

> Part 1: System architecture → Trusted Computing Base (TCB) → Security mechanisms → Security models (this is the “core spine” everything else depends on).   

# System architecture and Trusted Computing Base   
## 1) System architecture: the “shape” of trust, dependencies, and failure modes   
### 1.1 Enterprise architecture vs system architecture (why both matter)   
Security architecture doesn’t exist in a vacuum. The CBK emphasizes that architects need **standardized methodologies and a common vocabulary** so other architects, business owners, and auditors can validate the design process and deliverables.
Official Guide To CISSP CBK - 4…   
- **Enterprise architecture**: the organization-wide “city plan” (business/data/app/technology views).   
- **System architecture**: a single service/product’s structure—components, interactions, boundaries, and where trust is placed.   
   
AIO explicitly uses that “city planning” analogy: you can’t just drop security tools into a complex environment without understanding the organization’s architecture first.
CISSP - All In One Exam Guide -…   
### 1.2 Architecture frameworks you should think in (expert-level communication tool)   
CBK4 highlights common frameworks and why they exist: they’re **structures/methods** for building a target architecture as an integrated set of systems and components, with tools and vocabulary to make designs understandable and reviewable.
Official Guide To CISSP CBK - 4…   
It then calls out:   
- **Zachman Framework** (organizes “descriptive representations” for different audiences/levels of detail)
Official Guide To CISSP CBK - 4…   
- **SABSA** (security architecture lifecycle starting from business requirements and building a **chain of traceability** through strategy → design → implementation → metrics)
Official Guide To CISSP CBK - 4…   
- **TOGAF** (enterprise architecture development method; requirements analysis is central)
Official Guide To CISSP CBK - 4…   
   
**Why this matters for security engineering:** frameworks force you to answer “why does this control exist?” and trace it:   
> business requirement → risk → control objective → control design → implementation → evidence/metrics   

That traceability chain is how you become “above CISSP”: you can defend every security decision.   
 --- 
## 2) The real unit of security architecture: boundaries + trust   
### 2.1 Security boundaries and trust boundaries   
A boundary is where assumptions change:   
- internal ↔ external network   
- user ↔ admin   
- workload ↔ control plane   
- one tenant ↔ another tenant   
- “trusted code” ↔ “untrusted input”   
   
**Rule:** Most catastrophic failures happen where boundaries are weak or implicit.   
### 2.2 Security domains   
A **security domain** is a set of subjects/objects governed by the same security policy and enforcement mechanisms.   
- Example: “Production PCI cardholder data environment” is a tighter domain than “internal dev.”   
   
Domains map to:   
- separate identity realms / roles   
- network segmentation and routing constraints   
- separate key hierarchies   
- separate logging and approval workflows   
- separate hardening baselines   
 --- 
   
## 3) Trusted Computing Base (TCB): what must be trusted for the system to be secure   
SG4’s Domain-3 lineage explicitly includes TCB as a first-class topic, along with security models and evaluation/assurance thinking.
CISSP - Study Guide - 4th Editi…   
### 3.1 Definition (TCB = “the stuff that can break your security guarantees”)   
The TCB is the collection of hardware/firmware/software that **must work correctly** to enforce the security policy.   
If the TCB is compromised, your security policy becomes advisory.   
**Examples of typical TCB components**   
- CPU privilege model, memory protection (MMU), IOMMU (DMA boundaries)   
- kernel / hypervisor   
- security kernel (if present)   
- authentication subsystem (credential verification)   
- access control enforcement points (ACL checks, token checks)   
- cryptographic module + key storage (HSM/TPM/keystore)   
- audit/logging protection mechanisms   
   
### 3.2 TCB minimization (the single most important TCB strategy)   
Smaller TCB ⇒ fewer bugs ⇒ easier verification ⇒ higher assurance.   
**Engineering pattern**   
- push as much code as possible *out of* the TCB into untrusted user space   
- keep enforcement points centralized and small   
- reduce privileged services (disable what you don’t need)   
   
### 3.3 TCB vs “trusted path”   
A trusted path is a mechanism that ensures the user is interacting with the real security function (not a spoofed UI) when entering credentials or performing sensitive actions.   
In modern systems, “trusted path” ideas show up as:   
- secure attention sequences (OS-level)   
- hardware-backed UI for auth on mobile devices   
- WebAuthn/FIDO flows anchored in authenticators   
 --- 
   
## 4) Security mechanisms inside the TCB: how systems actually enforce policy   
### 4.1 Reference monitor concept (the enforcement ideal)   
A reference monitor is the conceptual mechanism that mediates all access between subjects and objects.   
A correct reference monitor must effectively have these properties (classic security engineering):   
- **Complete mediation**: every access request is checked   
- **Tamper resistance**: cannot be altered by untrusted subjects   
- **Verifiability**: small/simple enough to test and evaluate   
   
**Why you care:** every real access control system is a “reference monitor implementation” with gaps. Attackers live in those gaps (bypassing checks, abusing caching, exploiting confused deputies).   
### 4.2 Security kernel   
The security kernel is the portion of the OS that implements the reference monitor properties.   
- In some designs it’s a small core; in monolithic kernels it’s more diffuse.   
   
**Expert distinction**   
- “Policy” = what rules should be enforced   
- “Mechanism” = how rules are enforced (ACL checks, token evaluation, MAC enforcement)   
    Keeping these separable improves maintainability and assurance.   
   
### 4.3 Protection mechanisms (hardware → firmware → OS → application)   
SG4’s “Principles of Computer Design” coverage explicitly separates architecture layers (hardware, I/O structures, firmware) and security protection mechanisms—because real enforcement is layered.
CISSP - Study Guide - 4th Editi…   
**Practical mapping**   
- Hardware: privilege rings, MMU, NX bit, TPM, HSM   
- Firmware: secure boot chain, measured boot   
- OS: process isolation, access tokens/ACLs, sandboxing, kernel-mode drivers boundary   
- App: authorization checks, input validation, secure defaults   
 --- 
   
# 5) Security models: the “math/logic” that defines what secure behavior means   
Security models are not trivia—they are **formal ways to reason about policy**, and they map directly to what a system must enforce.   
SG4 lists the core models you’re expected to understand (and you should treat them as “policy archetypes”): state machine, information flow, noninterference, take-grant, access control matrix, Bell-LaPadula, Biba, Clark-Wilson, Brewer-Nash (Chinese Wall).
CISSP - Study Guide - 4th Editi…   
## 5.1 The “policy problem” models solve   
Without a model, people say “secure” but mean different things:   
- confidentiality-focused orgs think “no read up”   
- integrity-focused orgs think “no write down”   
- commercial orgs think “only well-formed transactions”   
- conflict-of-interest orgs think “don’t let analysts see both sides”   
   
Models make those statements precise.   
## 5.2 Model-to-real-world mapping (what each model is for)   
### Bell-LaPadula (BLP) — confidentiality policy   
- Focus: prevent information leakage from higher sensitivity to lower sensitivity.   
- Core rules (conceptually): no read up, no write down.   
   
**Where it shows up**   
- Mandatory access control environments   
- data classification enforcement   
- multilevel security concepts   
   
### Biba — integrity policy   
- Focus: prevent contamination of high-integrity objects by lower-integrity subjects.   
- Conceptually: no read down, no write up.   
   
**Where it shows up**   
- protecting high-integrity systems (financial records, configuration repos)   
- separating “untrusted input” from “trusted decisions”   
   
### Clark-Wilson — commercial integrity   
- Focus: integrity through well-formed transactions + separation of duties.   
- Key idea: subjects don’t directly manipulate objects; they use controlled transformation procedures.   
   
**Where it shows up**   
- financial apps, ERP workflows   
- approvals, workflows, change control, dual control   
   
### Brewer-Nash (Chinese Wall) — conflict of interest   
- Focus: prevent access that creates conflict-of-interest through dynamic access decisions based on prior access history.   
   
**Where it shows up**   
- consulting, legal, investment research (client conflict rules)   
   
### Information flow model / Noninterference   
- Focus: preventing forbidden flows (including indirect leakage).   
- Where it shows up: side-channel thinking, compartmentalization, multi-tenant isolation reasoning.   
   
### Take-Grant / Access control matrix   
- Focus: formal reasoning about rights propagation (who can grant what to whom).   
- Where it shows up: permissions delegation analysis, capability systems.   
   
> Part 2 (deep dive): Assurance + evaluation criteria + certification/accreditation + how experts use these in architecture and procurement.   

## 1) Assurance: the difference between “security features exist” vs “you can trust them”   
### 1.1 “Functionality” vs “assurance” (two different questions)   
The evaluation criteria families separate two ideas:   
- **Functionality**: *what security functions/capabilities exist?* (access control, labeling, auditing, etc.)   
- **Assurance**: *how confident are we those functions are correct, consistently enforced, and resistant to bypass?*   
   
ITSEC makes this separation explicit by rating **functionality** and **assurance** independently and calling the evaluated system the **Target of Evaluation (TOE)**.
CISSP - Official Study Guide - …   
CISSP - Official Study Guide - …   
### 1.2 Why the environment matters (the “same product ≠ same security” reality)   
OSG7 stresses that evaluation depends on many factors, including **installation process, physical environment, and configuration**—so two identical systems can receive different assessments due to configuration/installation differences.
CISSP - Official Study Guide - …   
**Expert takeaway:** assurance claims must always include:   
- configuration baseline   
- environment assumptions   
- operational controls (admin, physical, procedural)   
- and a process for maintaining that assurance as things change   
 --- 
   
## 2) Certification and accreditation: assurance as a lifecycle decision, not a one-time badge   
OSG7 defines the formal evaluation process as **two phases**: **certification** then **accreditation**, and emphasizes the need for both phases and for criteria to evaluate systems.
CISSP - Official Study Guide - …   
### 2.1 Certification (phase 1): “technical evaluation + documentation”   
Certification is defined as a **comprehensive evaluation of technical and nontechnical security features** supporting accreditation, to determine how well design/implementation meet specified security requirements.
CISSP - Official Study Guide - …   
OSG7 then makes this extremely important point: certification includes evaluation of **hardware, software, configuration**, and **all controls**—administrative, technical, and physical.
CISSP - Official Study Guide - …   
**Professional translation:** certification is where you prove:   
- your architecture and implementation match the policy/control objectives   
- the security mechanisms work in the *real* environment   
- your nontechnical controls (people/process/physical) close the gaps technical controls can’t   
   
### 2.2 Certification is configuration-bound (change can invalidate it)   
OSG7 explicitly states certification is valid only for a system in a **specific environment and configuration**; changes can invalidate it.
CISSP - Official Study Guide - …   
**This is why serious programs connect evaluation to change management**:   
- change introduces new attack surface   
- a patch can fix risk *or* introduce drift   
- assurance is maintained by a control loop (baseline → change review → retest → reaccept)   
   
### 2.3 Accreditation (phase 2): “management acceptance of the certified config”   
OSG7 states that after certification, management compares system capabilities to organizational needs and formally accepts the configuration through accreditation (formal acceptance by designated authority).
CISSP - Official Study Guide - …   
CISSP - Official Study Guide - …   
**Professional translation:** accreditation/authorization is **risk acceptance with accountability**:   
- the authority accepts the residual risk of operating the system   
- the acceptance is tied to the certified configuration and operating constraints   
 --- 
   
## 3) Product evaluation models: Rainbow Series → TCSEC → ITSEC → Common Criteria   
OSG7 introduces three product evaluation models: **TCSEC, ITSEC, Common Criteria**, describing TCSEC historically and Common Criteria as the replacement (with TCSEC kept mainly as historical reference).
CISSP - Official Study Guide - …   
### 3.1 Rainbow Series: why it exists   
OSG7 explains that since the 1980s, organizations needed minimum security criteria for procurement; TCSEC was created by the U.S. DoD, followed by many publications known as the **Rainbow Series** (named after cover colors).
CISSP - Official Study Guide - …   
**Expert use today:** not to worship “old books,” but to understand:   
- why modern assurance tries to be “repeatable and comparable”   
- why procurement standards exist   
- how “security capability vs trust in capability” became formalized   
 --- 
   
## 4) TCSEC (Orange Book): confidentiality-centric assurance + TCB-driven evaluation   
### 4.1 What TCSEC fundamentally did   
CBK4 explains that TCSEC was prescriptive: it defined specific types/levels of controls and emphasized formal verification and reliability; it introduced **Trusted Computing Base (TCB)** into product evaluation—because some functions must be correct for security to be consistently enforced.
Official Guide To CISSP CBK - 4…   
**Key architectural consequence:** TCSEC’s higher levels pushed systems toward:   
- stronger formal policy enforcement   
- smaller, more verifiable trusted components (TCB discipline)   
    …and that made it impractical for highly complex distributed systems under strict TCSEC assumptions.
Official Guide To CISSP CBK - 4…   
   
### 4.2 Why TCSEC had major limitations   
OSG7 critiques TCSEC clearly:   
- It emphasized controlling access but didn’t control what users do *after* access is granted.
CISSP - Official Study Guide - …   
- It focused almost entirely on **confidentiality**, which often doesn’t fit commercial environments where integrity can be more important.
CISSP - Official Study Guide - …   
- It didn’t address personnel/physical/procedural safeguards well, and didn’t handle networking issues (network extensions came later, e.g., “Red Book”).
CISSP - Official Study Guide - …   
   
**Expert takeaway:** TCSEC teaches you *why* assurance models evolved:   
- they had to become less confidentiality-only   
- they had to address lifecycle/change   
- they had to scale beyond standalone systems   
 --- 
   
## 5) ITSEC: separate “what it does” from “how much you trust it” (and expand to CIA)   
OSG7 describes ITSEC as Europe’s evaluation approach and explains:   
- ITSEC rates systems (TOE) in **two categories**: functionality and assurance
CISSP - Official Study Guide - …   
- Functionality scale: **F-D through F-B3**; assurance scale: **E0 through E6**
CISSP - Official Study Guide - …   
   
### 5.1 Why ITSEC was an improvement (conceptually)   
OSG7 lists important differences vs TCSEC:   
- ITSEC covers integrity and availability in addition to confidentiality (full CIA)
CISSP - Official Study Guide - …   
- ITSEC does not require a TCB concept / isolation of security components into a TCB
CISSP - Official Study Guide - …   
- ITSEC includes coverage for maintaining targets of evaluation after changes without requiring a full new evaluation every time
CISSP - Official Study Guide - …   
   
**Expert takeaway:** ITSEC pushes you toward a lifecycle mindset:   
- security isn’t static   
- evaluation must tolerate controlled change   
 --- 
   
## 6) Common Criteria (ISO/IEC 15408): global product evaluation with levels of assurance   
OSG7 explains Common Criteria (CC) as a global effort that enables purchase of CC-evaluated products; CC defines levels of testing/confirmation, but even the highest ratings are not a guarantee of “no vulnerabilities.”
CISSP - Official Study Guide - …   
OSG7 also notes the international recognition arrangement (1998) and that ISO converted it into **ISO 15408**.
CISSP - Official Study Guide - …   
### 6.1 CC’s “buyer value”: confidence + reduced duplication   
SG4 lists the objectives of CC guidance, including:   
- increase buyer confidence in evaluated products   
- eliminate duplicate evaluations   
- keep evaluations cost-effective and consistent   
- promote availability of evaluated products   
- evaluate both functionality and assurance of the TOE
CISSP - Study Guide - 4th Editi…   
   
### 6.2 The two key CC elements: Protection Profiles and Security Targets   
SG4 explicitly states Common Criteria is based on **Protection Profiles (PPs)** and **Security Targets (STs)**.
CISSP - Study Guide - 4th Editi…   
**How experts interpret this:**   
- **Protection Profile (PP):** “What a category of product should provide in an environment.”   
- **Security Target (ST):** “What this specific product claims it provides, and how it will be evaluated.”   
   
This is huge for procurement and architecture:   
- You don’t buy “secure.” You buy a product evaluated against a declared security target aligned to your environment.   
   
### 6.3 Don’t misuse EALs   
OSG7 warns directly: CC’s levels indicate what testing/confirmation was done, but even highest ratings don’t imply “completely secure.”
CISSP - Official Study Guide - …   
**Professional rule:** treat CC as evidence of disciplined evaluation, not a substitute for:   
- your threat model   
- your architecture review   
- your hardening baseline   
- your operational monitoring   
 --- 
   
## 7) How to use assurance models in real architecture work   
### 7.1 When evaluation criteria matter (practical triggers)   
- You’re buying security-critical components: crypto modules, smartcards, HSMs, secure boot components, trusted OS/hypervisors.   
- You’re in a regulated environment requiring provable controls and auditable assurance arguments.   
- You need to compare vendors on a consistent basis.   
   
### 7.2 “Evaluated configuration” discipline (non-negotiable)   
Because certification is configuration-bound and changes can invalidate it
CISSP - Official Study Guide - …
, an expert program builds:   
- baseline configs (secure templates)   
- controlled change process   
- periodic retesting / recertification triggers   
- continuous monitoring of drift   
   
### 7.3 The “assurance stack” you should be able to articulate   
For any system, you should be able to state:   
- what is in the TCB   
- what protects the TCB (isolation, minimality, trusted paths)   
- what evidence exists for correctness (evaluation results, tests, code review, formal verification where applicable)   
- what operational controls keep the system inside the evaluated assumptions   
 --- 
   
## 8) Mini add-on: open vs closed systems + process confinement (security engineering primitives)   
OSG7’s exam essentials explicitly calls out:   
- **open systems** (industry standards, easier integration) vs **closed systems** (proprietary, unpublished specs)
CISSP - Official Study Guide - …   
- **confinement, bounds, isolation** as memory/process restriction concepts
CISSP - Official Study Guide - …   
   
**Why these matter in Domain 3:**   
- Open vs closed affects your ability to evaluate, integrate, and independently assess security.   
- Confinement/bounds/isolation are core enforcement mechanisms behind sandboxes, containers, VMs, and OS process isolation—the practical tools that keep non-TCB code from violating security policy.   
   
> Part 3 (as deep as possible): Cryptography + Key Management + PKI + Hardware Root of Trust   

## 1) Cryptography as engineering (not math trivia)   
### 1.1 The core rule (Kerckhoffs / “enemy knows the system”)   
A secure cryptosystem must remain secure even if the attacker knows everything about the algorithms and system design—**except the key**. The CBK emphasizes this explicitly and ties security strength to key secrecy and key size, not obscurity.
Official Guide To CISSP CBK - 4…   
**Engineering implication:**   
When crypto breaks in enterprises, it’s usually one of these:   
- weak/randomness failure during key generation   
- key stored where attackers can read it (config files, repos, logs, build pipelines)   
- key reused too long / too broadly   
- revocation/rotation not operationally possible (so compromised keys live forever)   
- “trust” not anchored (MITM due to bad certificate validation, wrong trust store, no pinning where needed)   
 --- 
   
## 2) Cryptographic primitives: what each one actually provides   
### 2.1 Hashes vs MACs vs signatures (do not mix these up)   
- **Hash**: integrity fingerprint (no secret; anyone can compute).   
- **MAC / HMAC**: integrity + authentication *between parties who share a secret*; it **does not** provide nonrepudiation. SG4 calls HMAC a “partial digital signature”: integrity yes, nonrepudiation no.
CISSP - Study Guide - 4th Editi…   
   
**Why this matters in design:**   
If you need “the sender cannot deny it,” you need **digital signatures** (public key) and the signer’s private key must be uniquely controlled.   
### 2.2 Symmetric encryption (bulk confidentiality)   
SG4 explains symmetric crypto uses a **shared secret** for both encryption and decryption; it’s efficient and used for bulk encryption, and the practical security depends heavily on **key length** and keeping the key secret.
CISSP - Study Guide - 4th Editi…   
**Key-length reality (why security “ages”)**   
SG4 notes 56-bit DES was once considered sufficient but is no longer secure, and modern systems use much longer keys (e.g., 128-bit+).
CISSP - Study Guide - 4th Editi…   
This naturally leads to “crypto agility” (being able to migrate algorithms/key sizes without rewriting everything).   
### 2.3 Asymmetric crypto (identity + key distribution + signatures)   
CBK4 describes the public/private key idea: encrypt with recipient’s public key → decrypt with private key; sign with private key → verify with public key; this enables confidentiality and digital signatures in “open network” environments where parties don’t share a secret ahead of time.
Official Guide To CISSP CBK - 4…   
 --- 
## 3) Hybrid cryptography (how the real world actually works)   
OSG7 explicitly calls out “hybrid cryptography”: combining asymmetric crypto with symmetric crypto + hashing + certificates to enable secure communication between parties previously unknown to each other.
CISSP - Official Study Guide - …   
**Reality:**   
- Asymmetric is used to **establish trust and exchange/derive session keys**   
- Symmetric is used for **fast bulk encryption**   
- Hash/HMAC/signatures are used for **integrity/authentication/nonrepudiation**   
 --- 
   
## 4) Digital signatures: what they guarantee (and what they don’t)   
OSG7 notes digital signatures provide **integrity, authentication, and nonrepudiation**, but **not confidentiality** by themselves (you’d encrypt separately if you need privacy).
CISSP - Official Study Guide - …   
OSG7 also summarizes the Digital Signature Standard (DSS): federally approved signature algorithms and the use of SHA-2 hashing, with DSA/RSA/ECDSA recognized in that context.
CISSP - Official Study Guide - …   
**Enterprise design patterns**   
- **Code signing:** build system signs artifacts; endpoints verify signature before executing.   
- **Document signing:** legal and high-assurance workflows (timestamping often layered in).   
- **TLS:** servers (and sometimes clients) authenticate with certificates; handshake authenticates keys.   
 --- 
   
## 5) Key management: the real “cryptosystem”   
CBK4 is blunt: key management is “perhaps the most important part” of crypto—controlling issuance, revocation, recovery, distribution, and history of keys.
Official Guide To CISSP CBK - 4…   
AIO8 gives practical “rules for keys” that match what professionals implement:   
- key length must match required protection   
- keys stored/transmitted securely   
- keys should be extremely random and use full keyspace   
- key lifetime must match data sensitivity and usage frequency   
- keys should be escrowed/backed up for emergencies   
- keys destroyed at end of life
CISSP - All In One Exam Guide -…   
   
### 5.1 Key recovery & escrow (how to do it without creating a backdoor)   
AIO8 describes **multiparty key recovery** (dual control): requiring multiple people to recover a key, ideally across management/security/IT to reduce insider abuse and require collusion.
CISSP - All In One Exam Guide -…   
CISSP - All In One Exam Guide -…   
CBK4 provides the “m-of-n” concept for key recovery: multiple managers each contribute before recovery occurs—again, enforcing dual/multiparty control.
Official Guide To CISSP CBK - 4…   
### 5.2 Modern guidance (keep your crypto posture current)   
NIST maintains the key-management recommendations and updates transition guidance over time. The official “Key Management Guidelines” page notes an **initial public draft** of SP 800-57 Part 1 Rev. 6 was available (Dec 5, 2025) for comment through Feb 5, 2026.   
NIST’s transition guidance SP 800-131A also exists specifically to help organizations move away from weaker algorithms/keys over time.   
**Practical “above CISSP” takeaway:** you must build crypto systems so you can rotate algorithms/keys without outages (crypto agility).   
 --- 
## 6) PKI: “trust at scale” (CA/RA/certs/revocation)   
### 6.1 What PKI is for (3 primary purposes)   
CBK4 defines PKI as systems/software/protocols needed to **use/manage/control public key crypto**, with three primary purposes:   
1. publish public keys/certificates   
2. certify that a key is tied to an identity/entity   
3. provide verification of validity of a public key
Official Guide To CISSP CBK - 4…   
   
### 6.2 CA and RA roles (how identity proofing scales)   
CBK4 explains:   
- the **CA** sign
Official Guide To CISSP CBK - 4…
ifferent assurance levels exist based on identity validation strength
Official Guide To CISSP CBK - 4…   
- **RA** services can handle enrollment requests and validate accuracy, improving scalab
Official Guide To CISSP CBK - 4…   
   
Official Guide To CISSP CBK - 4…   
### 6.3 Certificate contents (X.509 fields you must know)   
OSG7 lists key X.509 certific
Official Guide To CISSP CBK - 4…
m identifier, issuer name, validity period, subject name (DN), subject public key, and extensions in X.509v3.
CISSP - Official Study Guide - …   
For Internet PKI, the definitive profile is RFC 5280.
CISSP - Official Study Guide - …
n people think)   
CBK4 covers multiple approaches:   
- remove cert from directory   
- publish CRLs   
- real-time status checking with CA/OCSP
Official Guide To CISSP CBK - 4…   
   
It also warns about a real OCSP risk: **replay attacks** against OCSP “good” responses, and notes nonce support isn’t consistently used, which can l
Official Guide To CISSP CBK - 4…   
Official Guide To CISSP CBK - 4…   
**Enterprise reality:** revocation isn’t just a protocol choice—it’s an operational capability:   
- can clients reach OCSP/CRL endpoints?   
- what happe
Official Guide To CISSP CBK - 4…
you revoke certs during incident response?   
   
### 6.5 Trust model: CA vs KDC analogy (helps your intuition)   
AIO8 explicitly explains: in Kerberos, principals trust the KDC; in PKI, parties don’t trust each other directly but trust the CA, which vouches for identities via certificates.
CISSP - All In One Exam Guide -…   
 --- 
## 7) Hardware root of trust: HSMs, TPMs, and secure boot chains   
### 7.1 HSMs (hardware security modules)   
OSG7 defines an HSM as a cryptoproc
CISSP - All In One Exam Guide -…
esp. large asymmetric), improve authentication, and includes tamper protection; it even notes a TPM is one example of an HSM, and gives common use cases (CAs, ATM/POS terminals, SSL accelerators, DNSSEC systems).
CISSP - Official Study Guide - …   
**Architect’s view:** HSMs exist because software key stores are usually the weakest link (malware, admins, backups, virtualization snapshots, memory
CISSP - Official Study Guide - …
cribes TPM as a protected microcontroller designed to store/process sensitive items (keys/passwords/certificates), strengthening the system’s root of trust and helping detect malicious configuration changes.
CISSP - All In One Exam Guide -…   
It details two major TPM use patterns:   
- **Binding**: disk content is tied to a specific system by keeping decryption keys in TPM; moving the drive
CISSP - All In One Exam Guide -…   
   
CISSP - All In One Exam Guide -…   
- **Sealing**: TPM stores hashes of system configuration and only activates when integrity matches (measured boot idea)
CISSP - All In One Exam Guide -…   
   
CISSP - All In One Exam Guide -…
terials like EK (endorsement key), SRK (storage root key), AIK, and PCRs for measured state, which are foundational for
CISSP - All In One Exam Guide -…   
CISSP - All In One Exam Guide -…   
### 7.3 A concrete real-world implementation: BitLocker + TPM (Windows internals)   
Windows Internals explains TPM is a cryptographic coprocessor use
CISSP - All In One Exam Guide -…
em hasn’t been tampered with while offline; it also outlines TPM/BitLocker architecture components and key hierarchy (FVEK, VMK, etc.).
Windows Internals Part 2\_6th Ed…   
Windows Internals Part 2\_6th Ed…   
**Why this matters in Domain 3:** hardware-backed key release + measured boot is one of the clean
Windows Internals Part 2\_6th Ed…   
Windows Internals Part 2\_6th Ed…
nning (because “strong today” isn’t strong forever)   
### 8.1 Deprecations happen (you must plan for them)   
NIST announced withdrawal of SP 800-67 Rev.2 (Triple DES/TDEA spec) effective Jan 1, 2024 (with limited allowances for legacy decryption/unwrapping/verification).   
This is exactly why Domain 3 pushes you to treat crypto as a lifecycle, not a feature.   
### 8.2 Post-quantum crypto is now official standards (plan the migration path)   
NIST released its first finalized post-quantum crypto standards in August 2024.   
The Federal Register notice states FIPS 203/204/205 are effective Aug 14, 2024.   
**Practical enterprise approach:**   
- inventory where public key crypto is used (TLS, VPN, S/MIME, code signing, SSH, internal PKI)   
- identify long-lived trust anchors (root CAs, firmware signing, code-signing roots)   
- plan dual-stack periods (classical + PQ where supported)   
- upgrade key management and HSM/PKI tooling first (because everything depends on it)   
 --- 
   
## 9) Domain 3 Part 3 mastery check (quick)   
If you can do these, you’re operating above CISSP level:   
1. Explain **which primitive** you need for each security property (hash vs HMAC vs signature) and why.
CISSP - Study Guide - 4th Editi…   
2. Design key management end-to-end: generation → storage → rotation → recovery → revocation → destruction.
Official Guide To CISSP CBK - 4…   
   
CISSP - All In One Exam Guide -…   
1. Build a PKI lifecycle plan including revocation strategy and its failure modes (CRL/OCSP, replay risks).
Official Guide To CISSP CBK - 4…   
2. Justify when you need **HSM vs TPM vs software keystore**, and tie it back to threat models and assurance requirements.
CISSP - Official Study Guide - …   
   
CISSP - Study Guide - 4th Editi…   
CISSP - All In One Exam Guide -…   
1. Show crypto agility and transition planning using NIST guidance as the
Official Guide To CISSP CBK - 4…   
   
CISSP - All In One Exam Guide -…
Domain 3 into the remaining high-impact sections:   
- **Secure networking protocols** (TLS/SSH/IPsec, what each
Official Guide To CISSP CBK - 4…
y & site design\*\* as part of “security engineering”   
- **Distributed systems security** (multi-tenant isolation, microservices
CISSP - Official Study Guide - …   
   
CISSP - All In One Exam Guide -…   
> cryptography applied to real systems (TLS/SSH/IPsec/email), then the architecture problems that appear at scale (distributed systems/cloud), and finally physical/site engineering (because the TCB and keys don’t matter if someone can walk off with the gear)   

## 1) Link encryption vs end-to-end encryption: where encryption “sits” in the stack   
Your OSG7 makes the key distinction:   
- **Link encryption** encrypts the *entire circuit* (including headers/routing), but requires decrypt/re-encrypt at each hop (slower; devices along the path see plaintext).   
- **End-to-end encryption** protects *only payload between endpoints* (faster; headers visible; protects against monitoring on “secure side” of links).   
   
**How architects use this distinction in real enterprise design**   
- Link encryption is great for **carrier/WAN circuits** and “pipe protection” between sites where you don’t control the intermediate network.   
- End-to-end encryption is mandatory for **application trust** (TLS to web servers, SSH admin sessions, DB TLS, etc.) because you assume internal networks can be monitored too.   
   
**Pro rule:** you often deploy **both**: link encryption for the transport path + end-to-end encryption for the application session (defense in depth).   
 --- 
## 2) TLS: the modern “default secure channel” (and what breaks it)   
OSG7 describes TLS as the replacement for SSL, used beneath HTTPS, and calls out that SSL became unacceptable (e.g., POODLE), leading many orgs to disable SSL and rely on TLS.   
### 2.1 The core TLS trust problem   
TLS is **two systems negotiating security parameters + authenticating the server (and sometimes the client)**. OSG7’s SSL/TLS discussion explains the classic certificate-based setup: browser gets server cert, extracts server public key, establishes a symmetric key, and then bulk traffic uses the symmetric key (hybrid crypto).   
Even if you know TLS “exists,” *real security* depends on:   
- **certificate validation** (chain + hostname + expiry + revocation strategy)   
- **strong key exchange and cipher suites**   
- **downgrade resistance**   
- **server private key protection** (HSM/TPM/locked-down key store)   
- **correct client behavior** (clients are often the weak link)   
   
### 2.2 TLS versions (what’s current)   
- **TLS 1.3** is standardized in RFC 8446.   
- NIST SP 800-52 Rev.2 requires **TLS 1.2 configured with approved algorithms as the minimum**, and it also states **support for TLS 1.3 by Jan 1, 2024** in its guidance.   
   
**Practical takeaway:** “We support TLS” is not enough. A modern program requires:   
- TLS 1.2+ only (prefer 1.3 where possible)   
- removal of obsolete ciphers and weak key exchanges   
- automated testing (TLS scanners, CI gates, config-as-code)   
   
### 2.3 TLS failure modes you must think like an attacker about   
1. **Downgrade paths**   
    OSG7 notes older TLS versions supported fallback to SSLv3 and that TLS v1.2 dropped that backward compatibility; POODLE exploited SSL fallback behavior.   
2. **Misissued/abused certificates + weak validation**   
    If your client doesn’t val   
3. **Key compromise vs session compromise**   
    If long-term keys are compromised, you want **forward secrecy** (ephemeral key exchange), so old captured traffic cannot be decrypted later.   
4. **Operational breaks**   
    - cert expiry outages   
    - broken OCSP/CRL reachability   
    - “soft-fail” revocation behaviors that attackers can exploit   
 --- 
   
## 3) SSH: encrypted admin plane and secure file transfer   
OSG7 calls SSH “a good example of end-to-end encryption” and explicitly says **SSH1 is insecure** while SSH2 modernized algorithm support.   
### 3.1 What SSH actually provides   
RFC 4253 describes SSH as secure remote login andng encryption, server authentication, and integrity protection\*\*.   
### 3.2 The real SSH trust anchor: host keys (not “the password”)   
SSH is often compromised because:   
- admins accept unknown host keys (“just type yes”)   
- private keys live on endpoints without strong protection   
- agent forwarding and shared bastions expand blast radius   
   
**Enterprise-grade SSH posture**   
- enforce modern algorithms   
- pin or centrally manage host keys   
- disable password auth where feasible; use certificates or short-lived keys   
- strong session logging for privileged bastions   
 --- 
   
## 4) IPsec: security at the IP layer (VPNs, host-to-host, site-to-site)   
OSG7 frames IPsec as an IETF architecture for secure channels between systems/routers/gateways and notes it commonly underpins VPNs, operating in **transport or tunnel mode**.   
OSG7 also gives the core IPsec mechanics you must know:   
- **AH**: integrity/authentication, access control, anti-replay (and it even associates it wiality + integrity, plus limited authentication, anti-replay   
- **Transport mode** encrypts payload; **Tunnel mode** encrypts the entire packet (gateway-to-gateway)   
- Security Associations (**SAs**) are **simplex**; bidirectional traffic needs two SAs (and more if you use both AH and ESP).   
   
AIO7 reinforces IPsec’s suite view and explicitly lists the set:   
- AH (integrity, origin auth, replay protection)   
- ESP (confidentiality + integrE for authenticated keying material   
   
### 4.1 IPsec “where it lives” in real enterprise architecture   
- **Site-to-site VPN** (tunnel mode): branch ↔ HQ, datacenter ↔ cloud interconnect   
    -dmin plane protection   
- **Isolation inside enterprise**: per-SA policy can restrict which protocols/services can traverse the secured channel   
   
### 4.2 Windows/IPsec internals (why SAs and negotiation matter)   
Windows Internals explains the practical runtime picture:   
- IKE negotiates SAs whetil negotiation succeeds   
- SAs define mutually agreed settings + keys, and are **one-way (simplex)**   
- There’s an IKE “main mode” SA (protects negotiation) and “quick mode” SAs (protect application traffic).   
   
This is the “professional mindset”: IPsec security is not only crypto—**it’s policy + SA lifecycle + negotiation hardening + monitoring**.   
 --- 
## 5) Secutly policy + PKI)   
OSG7 explicitly states that Internet email is insecure unless you secure it; you need nonrepudiation, integrity, authentication, delivery verification, and classification/handling rules—implemented via policy and procedures. It also lists **S/MIME and PGP** as mechanisms to improve email security.   
OSG7 also notes a real-world constraint: S/MIME has limited widespread adoption partly because major webmail systems don’t support it “out of the bo   
For up-to-date protocol grounding, S/MIME v4.0 is defined in RFC 8551.   
**The expert reality about email crypto**   
- perate at enterprise scale   
- S/MIME: enterprise-friendly due to CA-based PKI, but operationally heavy (certificate issuance, revocation, lifecycle, UX)   
   
Email security failures are usually:   
- no enforced classification labels   
- users forwarding sensitive data externally   
- lack of DLP and lack of retention controls   
- compromised endpoints stealing decrypted email   
 --- 
   
## 6) Distributed systems security: why “scale multiplies weaknesses”   
CBK4 makes the “force multiplier” point: parallel/distributed systems (clusters, grids, cloud, CPS/M2M) increase security weaknesses “to an unprecedented scale” because vulnerabilities can be shared across the entire connected system; architects must account for the multiplied impact on CIA and the difficulty of enforcing common policies across nodes.   
### 6.1 Big data / cluster reality (security is harder because of copies)   
CBK4 notes distributed data environments store **multiple copies of data across nodes** for fail-safe operation and move queries to thnges.   
**Architect’s takeaway:** in distributed data systems:   
- “least privilege” must exist at **every node**   
- keys and secrets exist **everywhere** unless you deliberately centralize them   
- logging must be centrali### 6.2 Cloud computing: shared responsibility becomes a security design constraint   
    CBK4 explains that as you move from IaaS → PaaS → SaaS, your ability to implement specific controls shifts toward the provider, requiring awareness of which controls you can modify and which need compensating controls; it gives an example where if encrypted storage isn’t offered, you may need to encrypt before transmission (or select a provider that meets required controls).   
   
OSG7 adds that cloud computing introduces issues like privacy and regulatory compliance challenges and questions about whether cloud data is truly secured, and it lays out service models (PaaS/SaaS/IaaS).6.3 Grid computing as a “warning model”   
OSG7 explicitly warns that grid computing exposes work packets broadly and is not appropriate for private/confidential/proprietary data because participants can keep   
### 6.4 Virtualization and hypervisors: the TCB shrinks or explodes depending on design   
A hypervisor’s job is to create/manage VMs and ensure isolation between them. A virtualization text in your sources descr and ensuring each VM is isolated such that problems in one VM do not affect others.   
Windows Internals shows how Hyper-V tries to keep the hypervisor small/modular (microkernel-like) and moves much of the work into a “parent partition” stack; a Server Core parent partition reduc   
**Security implication:** virtualization/cloud security is often “control-plane security.” If an attacker compromises:   
- the hypervisor management plane   
- the cloud control plane   
    they can bypass workload-level contrhat makes all the above real   
   
OSG7 is blunt: *if someone gains physical access to your facility or equipment, they own it*, and it divides physical security controls into **administrative, technical, and physical**, with examples (CCTV, alarms, HVAC, power, fire detection/suppression, locks, mantraps, guards).   
OSG7 also calls out high-value infrastructure areas:   
- wiring closets and server rooms must be protected with locks, surveillance, access control, and inspections   
- media storage security needs checkout syste   
   
### 7.1 Environmental controls (availability and integrity engineering)   
OSG7 highlights water risk (leaks/flooding), recommends locating critical equipment away from water sources/pipes, and suggests water detuppression and the “fire triangle.”   
AIO8 adds practical engineering details:   
- HVAC should integrate with fire alarm/suppression (HVAC can feed oxygen and spread smoke)   
- plenum areas need detectors and plenum-rated cabling   
- halon is no longaffect safety and infrastructure damage   
   
**Expert mindset:** physical security is not “guards and cameras.” It’s **availability engineering**, **safety engineering**, and \*\*tamper contr we go next   
If you say **next** again, I’ll continue Domain 3 into the remaining “big rocks”:   
- **Steganography + watermarking + DRM as cryptographic applications** (OSG7 treats these explicitly)   
- **Architecture flaws and protection mechanisms** (attack surface reduction, isolation, covert channels, etc.)   
- Then we transition cleanly into \*\*Dsign.   
   
> steganography/watermarking/DRM (crypto applications) and covert channels + information flow + noninterference (the “leakage through behavior” problem)   

## 1) Steganography: hiding the existence of data   
### 1.1 What steganography is (and how it differs from cryptography)   
**Cryptography** makes data unreadable to a third party; **steganography** hides the *existence* of the data so a third party may not even realize a secret message is present.
Official Guide To CISSP CBK - 4…   
That difference matters in architecture:   
- Crypto protects **confidentiality** even if the attacker intercepts the data.   
- Steganography protects **covert communication** by avoiding detection (which is often the attacker/defender’s *first* hurdle).   
   
CBK4 notes steganography can be as simple as covert channels, hidden text in webpages, “plain sight” file hiding, or null ciphers, but modern steganography can hide **large amounts** inside image/audio files and is often combined with cryptography (encrypt first, then hide).
Official Guide To CISSP CBK - 4…   
### 1.2 The “cover medium” model (how steganography is constructed)   
CBK4 provides the generic model:   
> cover_medium + hidden_data + stego_key = stego_medium   
> Official Guide To CISSP CBK - 4…   

Meaning:   
- **cover\_medium**: the innocent file (often image/audio)   
- **hidden\_data**: the secret payload (often encrypted)   
- **stego\_key**: optional secret to embed/extract   
- **stego\_medium**: the final file that “looks normal”   
   
This directly maps to real attacker behavior: exfiltration payloads embedded in images, audio, or files that pass through content filters because they “look benign.”   
### 1.3 Real-world security uses vs abuse   
OSG7 explicitly says steganography embeds messages inside images/WAVs and can be used for illegal activities (e.g., espionage), but also legitimate uses like watermarking for IP protection; the embedded watermark may be used to detect unauthorized copying and trace a leak back to a recipient if individualized watermarks are used.
CISSP - Official Study Guide - …   
**Professional security takeaway:**   
- Steganography is an **exfiltration technique** (red team / malware).   
- It can also be a **forensic/ownership technique** (watermarking).   
- It’s mostly invisible to humans: OSG7 notes embedded messages can be impossible to detect visually.
CISSP - Official Study Guide - …   
   
### 1.4 Defensive thinking: how you detect or constrain stego channels (enterprise view)   
You usually don’t “detect all stego.” You constrain the *opportunities*:   
- **Egress controls**: prevent arbitrary outbound uploads from sensitive zones.   
- **Content pipeline controls**: for high-risk environments, treat outbound media transfers as controlled workflows (approval + scanning).   
- **DLP/inspection reality check**: stego can bypass “keyword/regex” DLP because the payload is not visible text.   
- **Behavioral detection**: unusual outbound image/audio volumes, repeated uploads, odd file sizes/patterns.   
- **Policy control**: restrict which apps/services can upload media from restricted endpoints.   
 --- 
   
## 2) Digital watermarking: authenticity and ownership, not confidentiality   
CBK4 describes watermarking as a widely used application of steganography: embed an image/logo/text signature to later prove ownership and authenticate the source of a document/image (e.g., a graphic artist embeds a signature in a sample image).
Official Guide To CISSP CBK - 4…   
**Key architectural property:** watermarking is about:   
- provenance (who created it),   
- leak tracing (who was given which copy),   
- tamper-evidence (some watermark schemes break if modified),   
    not about keeping content secret.   
   
OSG7 describes the “unique watermark per recipient” pattern: if an unauthorized copy appears, you can potentially trace it back to the source recipient.
CISSP - Official Study Guide - …   
**Enterprise implementation patterns**   
- Watermark all external-facing “confidential preview” docs.   
- Embed recipient-specific watermarking for controlled distribution (partners, contractors).   
- Use watermarks for insider risk deterrence (users know content is traceable).   
 --- 
   
## 3) DRM: encryption applied as an access-control enforcement mechanism   
OSG7 defines **Digital Rights Management (DRM)** as software that uses **encryption** to enforce copyright restrictions on digital media (music, movies, books), and notes widespread DRM deployments often failed due to user backlash (intrusive/obstructive).
CISSP - Official Study Guide - …   
This matters for security engineering because DRM is a real example of:   
- encryption + key management + authorization policies   
- enforced in clients (players/readers/apps)   
- combined with revocation/expiration (subscription models)   
   
OSG7 gives an example of how DRM persists mainly in subscription contexts: access can be revoked when the subscription ends.
CISSP - Official Study Guide - …   
**Security lesson (important):**   
DRM shows the tension between **security goals** and **usability/acceptance**. Even technically strong controls can fail socially if they block legitimate user behavior (which then drives bypasses and black markets).   
 --- 
## 4) Covert channels: when information leaks through “side effects” of shared resources   
CBK4 lists **covert channels** as one technique under steganography broadly (hiding information).
Official Guide To CISSP CBK - 4…   
But covert channels are especially critical in **high-assurance systems**, because they break the idea that “the reference monitor mediates all access.”   
### 4.1 The two types you must know: storage vs timing   
CBK4 explicitly defines the two main types:   
- **Covert storage channel**: two processes read/write the same storage location (directly or indirectly), often involving a finite shared resource like memory locations or disk sectors shared across security levels.
Official Guide To CISSP CBK - 4…   
- **Covert timing channel**: one process modulates its use of CPU/memory/I/O so another process can infer information from observed response time changes; harder to detect but lower bandwidth.
Official Guide To CISSP CBK - 4…   
   
CBK4 also emphasizes the broader idea: covert channels can be *intentional* (insider leaks) or *unintentional* (an unauthorized person infers facts by observing system activity).
Official Guide To CISSP CBK - 4…   
### 4.2 Why covert channels defeat “perfect access control”   
Even if your mandatory access control says:   
- “Low can’t read High”   
- “High can’t write Low”   
    …covert channels allow “High” to signal “Low” by altering:   
- resource availability,   
- scheduling behavior,   
- cache effects,   
- lock contention,   
- disk allocation patterns,   
- network latency.   
   
So the *system behavior* becomes the channel.   
### 4.3 Defensive strategy: how high-assurance systems reduce covert channels   
You usually can’t eliminate all covert channels, but you can reduce and bound them:   
**For storage channels**   
- Avoid shared writable resources across security levels.   
- Partition resources per security domain (dedicated memory pools, dedicated file systems, dedicated queues).   
- Enforce **object reuse controls**: clear/overwrite memory and storage before reallocation.   
   
CBK4 explicitly describes memory reuse risk: when memory is deallocated then reallocated, residual data may carry over; the OS should zero/overwrite memory before a new process accesses it.
Official Guide To CISSP CBK - 4…   
**For timing channels**   
- Reduce precision of observable timing (introduce noise/jitter).   
- Use fixed-time scheduling for sensitive operations where feasible.   
- Rate-limit sensitive operations and isolate high/low workloads onto separate hardware when the threat model demands it.   
   
**The architectural “hard truth”:**   
Strong mitigation often requires *less sharing* (and that increases cost). That’s why covert channel protection is most common in military/MLS systems and high-risk multi-tenant environments.   
 --- 
## 5) Information Flow Model and Noninterference: the theory behind covert channel prevention   
OSG7 explains **information flow models** are designed to prevent unauthorized information flow (within same classification or across classifications). They allow all authorized flows and prevent unauthorized flows, and they explicitly address covert channels by excluding nondefined flow pathways.
CISSP - Official Study Guide - …   
### 5.1 Noninterference model: “High actions should not be observable by Low”   
OSG7 describes noninterference as focusing on how actions at a higher security level affect system state or the actions at a lower security level: High actions should not affect Low actions or even be noticed by Low. If Low can deduce facts about High, that becomes information leakage and implicitly a covert channel.
CISSP - Official Study Guide - …   
**Practical mapping**   
- Multi-tenant cloud isolation: one tenant should not infer another tenant’s workload/state.   
- Secret-dependent operations: Low should not infer secrets from timing differences.   
- High assurance data pipelines: changes in the “high side” shouldn’t be visible on the “low side” via resource behavior.   
   
### 5.2 Composition theories (systems interacting create new flows)   
OSG7 notes composition theories describe information flow between systems (outputs of one become inputs of another). It lists:   
- **Cascading** (output of one is input to another)   
- **Feedback** (systems exchange inputs/outputs)   
- **Hookup** (system sends input to another and also to external entities)
CISSP - Official Study Guide - …   
   
**Why this matters in modern architecture**   
This is the blueprint for understanding data leaks in distributed systems:   
- logs shipped to external analytics,   
- telemetry “hookups” that export sensitive metadata,   
- feedback loops where sanitization fails in one system and contaminates others.   
   
> Domain 3 finish — Common architecture flaws and how to engineer them out   

## 1) Security policy must drive architecture and be testable   
A core Domain-3 idea is: you can’t design “secure” without a **security policy** that defines what the system must enforce. AIO explains that a security policy sets concrete security goals (least privilege, auditing, trusted paths, no covert channels, integrity rules, etc.) and then becomes the baseline for evaluating the system after it’s built.
CISSP - All In One Exam Guide -…   
**Expert translation**   
- Policy → control objectives → architecture → mechanisms → tests → evidence.   
- If you can’t derive test cases from the policy, the “policy” is not specific enough.   
 --- 
   
## 2) “Complete mediation” failures (bypassing authorization checks)   
CBK4 defines **complete mediation** as the property that no subject can access any object without authorization, typically enforced by a **reference monitor** implemented by a security kernel; every access attempt is checked, compared to authorization, and logged.
Official Guide To CISSP CBK - 4…   
### How this fails in real systems   
- **Inconsistent enforcement points**: app checks permissions in one API path but not in another path.   
- **Caching mistakes**: access decisions cached too broadly (e.g., cached “allowed” beyond the intended scope/time).   
- **“Confused deputy” design**: a privileged component is tricked into using its authority on behalf of an untrusted caller.   
   
### Engineering countermeasures   
- Centralize decisions in a small number of enforceable checks (policy decision point / policy enforcement point pattern).   
- Make “deny by default” the baseline and enforce consistent checks across all code paths.   
- Log decisions where they matter (privileged actions, sensitive data access), because accountability is part of the reference monitor concept.
Official Guide To CISSP CBK - 4…   
 --- 
   
## 3) Memory/object reuse (residual data leakage across security boundaries)   
CBK4 explains **memory reuse/object reuse**: memory is allocated to one process, deallocated, then reused by another; if residual data remains, the next process may read sensitive data. The OS should zero or overwrite memory before a new process can access it.
Official Guide To CISSP CBK - 4…   
It also warns that “reuse” is not only RAM: disk space reuse and swap/paging files can leak enormous amounts of sensitive information if left unprotected.
Official Guide To CISSP CBK - 4…   
### Engineering countermeasures   
- **Zero-on-alloc / zero-on-free** policies at OS/hypervisor boundaries.   
- Protect swap/page files (encryption, access control) and treat them as sensitive.   
- When media is reassigned, deletion/formatting is insufficient; assurance requires degaussing/overwriting/physical destruction depending on media and assurance needs.
Official Guide To CISSP CBK - 4…   
 --- 
   
## 4) Covert channels (leaking through shared resource behavior)   
CBK4 defines two main covert channel types:   
- **Storage**: two processes share a storage location or finite resource (memory/disk sectors) across security levels.
Official Guide To CISSP CBK - 4…   
- **Timing**: one process modulates resource use (CPU/memory/I/O), and another infers information from response-time changes; lower bandwidth but harder to detect/control.
Official Guide To CISSP CBK - 4…   
   
### Engineering countermeasures (what “high-assurance” does)   
- Reduce cross-domain sharing of resources (partitioning, dedicated queues, dedicated hardware for very sensitive domains).   
- Add noise/jitter or reduce timing precision for sensitive operations.   
- Treat object reuse prevention as also reducing covert channels (CBK explicitly notes the relationship).
Official Guide To CISSP CBK - 4…   
 --- 
   
## 5) Malformed input and boundary failures (classic “untrusted input” problem)   
CBK4 explains that many modern attacks rely on **unusual input representations** that bypass filters (Unicode vs ASCII), SQL syntax tricks that bypass naive filtering, and active scripting inputs that become XSS; buffer overflows are also a form of malformed input.
Official Guide To CISSP CBK - 4…   
### Engineering countermeasures   
- Treat *all* external input as untrusted: canonicalize first, validate against strict schemas, and use parameterized interfaces.   
- Defensive encoding at output boundaries (HTML/JS contexts, SQL contexts).   
- Make security checks live where the data is interpreted (e.g., DB parameterization), not only at “perimeter filters.”   
 --- 
   
## 6) Secure memory management and isolation (why buffer overflows still matter)   
CBK4 notes most systems share a common memory pool where subjects and objects share memory, making isolation hard; this is one reason buffer overflows are successful. It calls out techniques like processor states, layering, data hiding, and also mentions ASLR as a protective technique (but requires programs to be designed/configured to benefit).
Official Guide To CISSP CBK - 4…   
### Engineering countermeasures   
- Strong process isolation (OS primitives) and **hardware-enforced** separation when necessary.   
- Compiler and platform hardening features (ASLR/DEP, etc.), plus secure coding (Domain 8 will go deeper).   
- Keep privileged code minimal (TCB minimization) so memory corruption in non-privileged code can’t become full compromise.   
 --- 
   
## 7) Least privilege and separation of privilege in OS design (user mode vs supervisor mode)   
OSG7 emphasizes **least privilege applied to system modes**: keep processes in user mode whenever possible; the more privileged-mode processes you have, the more exploitable surfaces exist for gaining supervisory access.
CISSP - Official Study Guide - …   
OSG7 also summarizes:   
- least privilege limits how many processes run in supervisory mode,   
- separation of privilege increases granularity of secure operations,   
- accountability requires audit trails.
CISSP - Official Study Guide - …   
   
### Engineering countermeasures   
- Split privileged operations into small, well-reviewed, well-protected services and expose them via narrow APIs.   
- Require multiple conditions (separation of privilege) for sensitive actions: e.g., admin approval + secure channel + hardware-backed auth.   
- Instrument privileged paths with strong audit logging (not just “errors”).   
 --- 
   
## 8) Process isolation, layering, abstraction, data hiding, hardware segmentation   
OSG7 explicitly calls out these architecture mechanisms and what they do:   
- **Process isolation**: processes access only their own data   
- **Layering**: separate security realms and limit cross-layer communication   
- **Abstraction**: “black-box” interfaces to reduce complexity exposure   
- **Data hiding**: prevent reading across security levels   
- **Hardware segmentation**: enforce isolation via physical controls
CISSP - Official Study Guide - …   
   
### Engineering countermeasures   
- Use isolation as your default stance: isolate services, isolate tenants, isolate admin planes.   
- Layer defenses so single failures don’t collapse everything (segmentation + auth + encryption + monitoring).   
- Prefer well-defined, narrow abstractions: smaller trusted interfaces are easier to verify.   
 --- 
   
## 9) Special environments where architecture breaks easily   
### 9.1 Cloud and distributed architectures (risk multiplies at scale)   
OSG7 notes cloud introduces issues like privacy and regulatory compliance, open/closed-source concerns, open standards, and whether cloud data is “secure (or securable).”
CISSP - Official Study Guide - …   
**Architect’s reality in cloud/distributed systems**   
- Control-plane security is everything (IAM, keys, logging, policy as code).   
- Misconfiguration is the dominant failure mode; therefore continuous monitoring of configuration drift becomes part of “assurance.”   
   
### 9.2 Grid computing (why “open participation” breaks secrecy)   
OSG7 warns grid work packets can be exposed to participants and copied/examined; therefore grid is not appropriate for private/confidential/proprietary data.
CISSP - Official Study Guide - …   
### 9.3 BYOD and mobile devices (architecture + governance collide)   
OSG7 summarizes BYOD as a policy that increases security risk and brings issues like data/support ownership, patching, forensics, privacy, onboarding/offboarding, legal, and acceptable use into the architecture.
CISSP - Official Study Guide - …   
**Engineering countermeasures**   
- MDM/MAM control plane (device posture, encryption, remote wipe, application control).   
- Separate “corporate container” from personal space (segmentation) and enforce data handling rules in the managed container.   
   
### 9.4 Embedded and static environments (where patching is hard)   
OSG7 notes embedded/static environments need specific security management: segmentation, security layers, application firewalls, manual updates, firmware version control, wrappers, redundancy/diversity.
CISSP - Official Study Guide - …   
 --- 
# Domain 3 checkpoint — prove you can think like a security architect/engineer   
Answer each scenario using this format:   
1. **Security objectives** (CIA priorities + business constraints)   
2. **Policy** (what must be enforced; what’s forbidden)   
3. **Trust boundaries + TCB** (what must be trusted; what must be isolated)   
4. **Mechanisms** (complete mediation, isolation, crypto, logging, physical controls)   
5. **Assurance** (how you test/validate; what changes force re-eval)   
6. **Failure modes** (how attackers bypass; how you detect/contain)   
7. **Evidence** (logs, configs, reviews, evaluation results)   
   
## Scenarios (do any 3 for a pass; all 8 for mastery)   
1. **Design a “high-integrity financial posting system”**   
    Pick the security model(s) and mechanisms to enforce integrity (workflows, separation of privilege, auditability). (Hint: don’t answer “Bell-LaPadula.”)   
2. **Multi-tenant SaaS: prevent cross-tenant data inference**   
    Show where covert timing/storage channels appear and what practical mitigations you’d apply (partitioning, noise, isolation). Use the covert channel definitions as your baseline.
Official Guide To CISSP CBK - 4…   
3. **Procurement: choose a crypto module for a CA**   
    What assurance evidence do you require (evaluation/certification), and what operational controls must exist so the evaluated configuration stays true over time?   
4. **Build an authorization boundary for an internal API platform**   
    Demonstrate complete mediation: how every request is intercepted, authorized, and logged. Tie it to reference monitor behavior.
Official Guide To CISSP CBK - 4…   
5. **Memory leakage incident: “process A can read process B’s prior data”**   
    Explain the object reuse failure and your technical + operational fixes (zeroing, swap protection, privileged isolation).
Official Guide To CISSP CBK - 4…   
6. **Web gateway filter bypassed by Unicode/encoding tricks**   
    Explain the malformed input class of problem and how to fix it at the right layer (canonicalization, strict validation).
Official Guide To CISSP CBK - 4…   
7. **Legacy embedded system can’t be patched frequently**   
    Give an architecture plan: segmentation, wrappers, redundancy/diversity, firmware version control, monitoring.
CISSP - Official Study Guide - …   
8. **Least privilege in OS/platform design**   
    Explain how you keep most processes out of supervisor mode and why this reduces exploitability.
CISSP - Official Study Guide - …   
   
## Scoring rubric (0–3 each, max 21)   
- Correct model/policy choice   
- Clear trust boundaries & minimized TCB   
- Correct mechanisms (mediation/isolation/crypto/logging)   
- Assurance plan (tests + change triggers)   
- Realistic failure modes and detection/containment   
- Operational viability (works at scale)   
- Evidence plan   

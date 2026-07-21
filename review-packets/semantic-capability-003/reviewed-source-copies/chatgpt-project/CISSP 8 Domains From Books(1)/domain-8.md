---
# yaml-language-server: $schema=schemas\page.schema.json
Object type:
    - Page
Backlinks:
    - Books-Summary = CISSP 8-Domain (References)
Creation date: "2026-02-25T20:26:32Z"
Created by:
    - Perky Sparrow
id: bafyreicek7o3ctc4yn4povxuqknhzulj5uj5eu63edyrm2qlteericfdsy
---
# Domain 8   
## Domain 8 — Chunk 1/5   
### Software Development Security: the control objective + 8.1 Integrate security into the SDLC (methodologies, maturity, gates, and “security by construction”)   
Domain 8 is the **software assurance** domain: security isn’t something you “add” with a scanner at the end—it’s something you **design, build, verify, ship, operate, and retire** with provable controls.   
The official Domain 8 structure is: **8.1–8.5** (integrate security into SDLC; apply controls in the development ecosystem; assess effectiveness; assess acquired software; secure coding standards).   
 --- 
# 0) Domain-8 control objective (the “secure software contract”)   
**Control objective:** Every software release has:   
- an explicit **security requirement set** (what must be true),   
- an explicit **threat model** (what could go wrong and how),   
- an explicit **verification plan** (how you prove it),   
- an explicit **release integrity chain** (how you prevent tampering),   
- an explicit **maintenance + vulnerability response process** (how you keep it safe after shipping).   
   
Domain 8 explicitly includes: development methodologies (Agile/Waterfall/DevOps/DevSecOps/SAFe), maturity models (CMM, SAMM), operation & maintenance, change management, integrated product teams, source-code weaknesses, API security, secure coding practices, languages/libraries/toolsets/IDE/runtime/CI/CD/config management/code repos, application security testing (SAST/DAST/SCA/IAST), auditing/logging of changes, and risk analysis/mitigation, including COTS/open-source/third-party.   
 --- 
# 8.1 Integrate security into the SDLC (the “how to build it so it stays true” part)   
## 1) SDLC models: what changes for security (and what must never change)   
Domain 8 expects you to understand how security integrates into multiple methodologies.   
**Security principle across all models:** the phases may rearrange, but the security **work products** must still exist.   
### 1.1 Waterfall (sequential)   
**Strength:** clear phase gates, strong documentation, stable requirements.   
**Risk:** security issues discovered late become expensive; change is painful.   
**Security integration strategy**   
- front-load requirements/threat modeling   
- enforce formal design review gates   
- treat “security regression” as release-blocking   
   
### 1.2 Spiral / iterative risk-driven   
**Strength:** explicitly cycles through risk; great fit for security thinking.   
**Risk:** can become unbounded if risk decisions aren’t controlled.   
**Security integration strategy**   
- each iteration must include: threat updates + test updates + risk acceptance decisions   
- track security requirements like functional requirements   
   
### 1.3 Agile   
**Strength:** rapid feedback loops, continuous improvement.   
**Risk:** “move fast” can degrade architecture and allow security debt to pile up.   
**Security integration strategy**   
- security user stories + abuse stories (misuse cases)   
- “Definition of Done” includes security tests, threat model update triggers, and log/telemetry requirements   
- security backlog has ownership and burn-down targets   
   
### 1.4 DevOps / DevSecOps   
**Strength:** automation + repeatability + fast patching.   
**Risk:** pipeline compromise becomes catastrophic; “everything is code” includes secrets and supply chain.   
**Security integration strategy**   
- treat pipeline, repositories, build system, artifacts, signing keys as **Tier-0**   
- continuous verification in CI/CD (SAST/DAST/SCA/IAST as appropriate)   
- immutable audit trails for every build and release   
   
### 1.5 Scaled Agile (SAFe)   
**Strength:** coordinates many teams; formalizes governance and portfolio.   
**Risk:** security becomes a separate “central team” and loses day-to-day influence.   
**Security integration strategy**   
- embed security champions / integrated product team roles   
- standardize shared security architecture patterns and reusable controls   
- organization-wide pipeline and baseline standards   
 --- 
   
## 2) The “security work products” (deliverables) you must produce in any SDLC   
### 2.1 Security requirements (what must be true)   
Requirements must be:   
- **measurable** (testable)   
- **traceable** (from requirement → design → code → test → release)   
- **risk-driven** (why this control exists)   
   
Minimum requirement categories:   
- **authN/authZ** (roles, least privilege, session rules)   
- **data protection** (classification, encryption, key management expectations)   
- **logging/monitoring** (what must be logged, what is an alert, retention)   
- **availability** (rate limits, DoS protections, graceful degradation)   
- **secure defaults** (no insecure-by-default features)   
- **privacy** (data minimization, retention, consent where relevant)   
- **supply chain** (dependency rules, signing, provenance)   
   
### 2.2 Threat modeling (turn assumptions into explicit controls)   
Threat modeling is the design step that prevents “security by accident.”   
Minimum threat model artifacts:   
- **data-flow diagrams** (DFDs)   
- **trust boundaries** (where identity/privilege changes)   
- **asset list** (what matters)   
- **threat list** (abuse paths)   
- **mitigations** mapped to design decisions   
- **verification mapping** (how you’ll test each mitigation)   
   
Trigger events for updates:   
- new external interface (new API)   
- new identity integration   
- new data type/classification   
- new dependency/service   
- major architecture change   
   
### 2.3 Architecture & design reviews (catch “class-of-bug” risks early)   
Design review focuses on preventing families of vulnerabilities by construction:   
- strong trust boundaries, least privilege   
- input validation strategy (where and how)   
- secure session/token design   
- error handling policy (avoid information leaks)   
- crypto usage patterns (what algorithms, where keys live)   
- logging architecture (what evidence exists during an incident)   
- dependency boundaries (what third parties can affect you)   
 --- 
   
## 3) Secure SDLC gates (how you stop unsafe releases from shipping)   
A mature program defines **release gates** (automated where possible) and **exception governance**.   
### 3.1 Common gates (by phase)   
**Before code merges**   
- required code review (security-critical changes need specialized review)   
- secret scanning (block committing credentials)   
- dependency checks (known-vulnerable / policy violations)   
- unit tests + security unit tests (input validation, auth decisions)   
   
**During build**   
- build from pinned, trusted dependencies   
- artifact hashing + provenance capture   
- signing keys protected (HSM/KMS/PAM patterns)   
   
**Before release**   
- SAST/SCA results meet threshold (risk-tiered)   
- DAST/IAST where applicable   
- threat model updated for major changes   
- logging requirements verified   
- “break-glass” release exception process (time-bounded, monitored)   
   
**After deployment**   
- monitoring dashboards updated   
- vulnerability response process active (triage → patch → verify)   
- regression tests prevent reintroducing known classes of bugs   
   
### 3.2 Why “few SDLCs address security explicitly” matters   
Secure SDLC is usually not “built-in” to the methodology—security practices must be **added and integrated** into any SDLC implementation.   
That is exactly why frameworks like SSDF exist: to provide a core set of practices that can be integrated into any SDLC.   
 --- 
## 4) A practical organizing backbone (extended source you can map to any SDLC)   
A clean way to structure an entire secure SDLC program is to group practices into:   
- **Prepare the Organization**   
- **Protect the Software**   
- **Produce Well-Secured Software**   
- **Respond to Vulnerabilities**   
   
This gives you a “program spine” that aligns governance + engineering + operations:   
- org readiness (roles, training, environments, policies)   
- protection of code/build/release assets   
- secure engineering and verification   
- vulnerability handling after release   
 --- 
   
## Micro cheat sheet (Chunk 1)   
- Domain 8 covers SDLC models + maturity + operations/maintenance + change management + teams + source-code vulnerabilities + API security + secure coding + tooling/CI/CD/repos + testing + logging of changes + acquired software risk.   
- Security must produce **work products** (requirements, threat model, verification plan, release integrity, vulnerability response) regardless of methodology.   
- DevSecOps shifts the highest-risk asset from “the app server” to **the pipeline + repos + signing keys**.   
- Use SDLC gates + exception governance to prevent “we’ll fix it later” from becoming permanent risk.   
 --- 
   
Say **“next”** for **Chunk 2/5: 8.2 Security controls in the software development ecosystem** (toolchains, IDEs, libraries, CI/CD, code repositories, software configuration management, audit logging of changes, pipeline integrity, secrets, and supply-chain controls).   
Next   
## Domain 8 — Chunk 2/5   
### 8.2 Enforce security controls in the software development ecosystem (toolchain, repos, CI/CD, dependencies, secrets, build integrity, auditability)   
This chunk is **DevSecOps as an attack surface**. In modern orgs, attackers often don’t “hack prod first” — they compromise **code, builds, or release paths** so prod becomes compromised *by design*. Domain 8.2 is how you prevent that.   
 --- 
# 1) The core threat model: “software supply chain attack chains”   
Design your ecosystem controls to break these common chains:   
1. **Developer identity compromise** → push malicious code → passes weak review → ships   
2. **Repo compromise** (token leak / weak branch protections) → direct commit to main → ships   
3. **Dependency compromise** (typosquat / hijacked package / poisoned update) → build pulls it → ships   
4. **CI runner compromise** (shared runners, weak isolation) → steals secrets/signing keys → ships signed malware   
5. **Pipeline definition compromise** (CI config edit) → disables tests/scans → injects artifact → ships   
6. **Artifact repo compromise** → swap binaries after build → deploy trusted-looking malware   
7. **Deploy credential compromise** → attacker deploys from pipeline path → logs look “legit”   
8. **Environment drift** → “works in CI” but unsafe in prod; or test data leaks   
   
**Domain 8.2 objective:** make each of those chains require multiple independent failures (identity + review + signing + immutability + monitoring), so compromise is either blocked or loud.   
 --- 
# 2) Ecosystem security = five control planes (treat them as separate trust zones)   
Think of the development ecosystem as **five planes** you must isolate and govern:   
1. **Identity plane**: who can act (humans, bots, service accounts)   
2. **Source plane**: what is “the code” (repos, branches, PR rules, commit signing)   
3. **Build plane**: how code becomes artifacts (compilers, runners, build scripts, secrets)   
4. **Artifact plane**: where outputs live (registries, package repos, SBOMs, signatures)   
5. **Deploy plane**: how artifacts become running systems (CD, approvals, environment controls)   
   
If any plane can fully compromise the next plane without barriers, you don’t have “DevSecOps”—you have a fast malware distribution system.   
 --- 
# 3) Identity and access controls (the “who can change reality” layer)   
## 3.1 Human identity (developers, reviewers, release managers)   
Minimum enterprise controls:   
- **MFA everywhere** (repo, CI, artifact registry, cloud accounts)   
- **least privilege RBAC**: read/write/admin separated   
- **no shared accounts** (especially for repo admins and pipeline admins)   
- **break-glass accounts**: rare use, high friction, high monitoring   
- **strong session auditability**: every privileged action attributable to a person   
   
## 3.2 Non-human identity (CI bots, deploy agents, automation)   
These are frequently the real compromise path.   
Controls:   
- separate service accounts per pipeline/service (no “global CI token”)   
- short-lived tokens (OIDC-style federation to cloud where possible)   
- strict scoping (token can only do what that job needs)   
- rotate secrets; eliminate long-lived static keys   
- restrict where the bot can run (only approved runners)   
   
## 3.3 Separation of duties (SoD) in the dev ecosystem   
You prevent silent supply-chain compromise by ensuring:   
- PR author ≠ sole approver for sensitive code   
- pipeline definition changes require independent approval   
- release signing keys are controlled by a small, audited group   
- production deployment requires approval gates or controlled promotion rules   
 --- 
   
# 4) Source control security (repos, branches, reviews, provenance)   
## 4.1 Branch protection (the core enforcement mechanism)   
For “main / release” branches:   
- forbid direct pushes (PR only)   
- require approvals (minimum #, from CODEOWNERS where applicable)   
- require passing checks (tests, scans, build)   
- require up-to-date merges (avoid bypass via stale base)   
- require signed commits (where your platform supports verification)   
   
## 4.2 Code review that actually reduces risk   
Effective review is not “LGTM.”   
For security-sensitive repos define:   
- mandatory reviewers by ownership (CODEOWNERS)   
- explicit checklist items for auth/data/crypto/logging changes   
- “change risk labels” (high-risk changes need more scrutiny)   
- prohibit self-approval on critical paths   
- enforce review on infra/pipeline-as-code changes   
   
## 4.3 Repo admin hardening   
- limit repo admin count (tiny)   
- monitor admin actions (branch rule changes, token creation, permission grants)   
- periodic access recertification   
- disable legacy auth paths (basic auth, weak tokens)   
   
## 4.4 Audit logging of change events   
You need immutable visibility into:   
- who changed code   
- who changed branch rules   
- who changed pipeline definitions   
- who created/rotated tokens   
- who approved what release   
- who deployed to where   
   
**If you can’t reconstruct “who shipped this binary,” you can’t investigate or prove integrity.**   
 --- 
# 5) Dependency and library controls (SCA reality)   
## 5.1 Dependency governance (what you allow into the build)   
Controls that work in practice:   
- **lockfiles / pinned versions** for repeatability   
- **internal mirrors/proxies** for package registries (reduce direct internet pull)   
- allowlist trusted sources; block known risky sources   
- dependency review for critical libraries (crypto, auth, serialization, parsers)   
   
## 5.2 SCA (Software Composition Analysis) as a gate   
SCA isn’t just “find CVEs.”   
Use it to enforce:   
- vulnerable version bans   
- license policy rules   
- “must have maintainer activity” rules for high-risk components   
- forbid dependency confusion patterns (internal package names protected)   
   
## 5.3 SBOM + provenance (what was shipped)   
Operationally, you want each build to produce:   
- SBOM (what components are inside)   
- build provenance/attestation (how it was built, by which pipeline, from which commit)   
   
This is how you later answer: “Are we affected by CVE-X?” and “Is this artifact authentic?”   
 --- 
# 6) CI/CD security (where most modern breaches escalate)   
## 6.1 Pipeline-as-code is a high-value target   
Pipeline definitions must be governed like production code:   
- stored in version control   
- PR-reviewed (with required owners)   
- tested (no ad-hoc edits in the UI)   
- changes generate alerts and tickets   
   
## 6.2 Runner isolation (shared runners are an attack surface)   
Hardening principles:   
- prefer ephemeral runners (new VM/container per job)   
- isolate builds per tenant/project; avoid cross-project secret exposure   
- restrict outbound network from runners (no arbitrary internet during builds for high-risk pipelines)   
- run builds with least privilege (no host mounts unless required)   
   
## 6.3 Secret handling in pipelines (the #1 failure mode)   
Rules that prevent disasters:   
- never store secrets in repo   
- inject secrets at runtime from a vault   
- restrict secrets to jobs that need them   
- do not print secrets in logs; redact aggressively   
- rotate secrets on exposure or role changes   
- prefer short-lived federated credentials over static cloud keys   
   
## 6.4 Environment promotion model (dev → test → staging → prod)   
Treat environments as trust zones:   
- only promote **immutable artifacts** (never rebuild “the same version”)   
- require approvals for prod promotion   
- require test evidence (security + functional) before promotion   
- record promotion events (who approved, which artifact hash)   
 --- 
   
# 7) Build integrity and release integrity (the “artifact is the truth” layer)   
## 7.1 Reproducible / hermetic builds (high assurance pattern)   
Best practice patterns:   
- build from pinned inputs (compiler, dependencies)   
- isolate build environment (containerized/toolchain)   
- produce deterministic artifacts where possible   
- record build metadata (commit hash, tool versions, dependency graph)   
   
## 7.2 Artifact repository hardening (stop “swap the binary”)   
Artifact repos/registries must enforce:   
- immutability (can’t overwrite a version tag)   
- strong access control (publish rights minimal)   
- signing/verification (reject unsigned artifacts where policy demands)   
- audit logs for publish/delete/permission changes   
- replication/backup (artifact repo is crown-jewel infrastructure)   
   
## 7.3 Signing keys and trust anchors (Tier-0 assets)   
Controls:   
- store signing keys in HSM/KMS where feasible   
- limit access to keys (very small set)   
- dual control for key operations (rotate/revoke)   
- alert on signing events that don’t match expected pipelines   
- rotate on suspicion immediately   
   
If attackers get signing keys, they can ship malware that looks legitimate.   
 --- 
# 8) Development and test environment protections (the “don’t leak prod” layer)   
## 8.1 Dev/test/prod separation (data and identity)   
Rules:   
- dev/test must not use real production secrets   
- prod credentials must never be accessible from dev tooling   
- test data must be synthetic or properly masked   
- network segmentation prevents lateral movement from dev to prod control planes   
   
## 8.2 Secure test data   
Controls:   
- data minimization (only what’s needed)   
- tokenization/masking for regulated fields   
- access logging to test datasets (yes, even in test)   
- retention rules (test data shouldn’t live forever)   
 --- 
   
# 9) Configuration management in software (SCM) beyond “git”   
Domain 8.2 includes software configuration management concepts in practice:   
- versioning strategy (semantic versioning, release branches)   
- build number integrity (build IDs map to commit IDs)   
- change control for configs (config-as-code, reviewed changes)   
- environment configuration drift detection (IaC drift checks)   
   
**If config changes aren’t controlled, you can ship secure code into an insecure runtime.**   
 --- 
# 10) Operational “prove it” pack for 8.2 (what you should be able to demonstrate)   
If your ecosystem is secure, you can show:   
- repo policies (protected branches, required reviews, signed commits)   
- CI/CD RBAC and audit logs   
- secret management architecture and rotation proof   
- dependency governance rules + SCA gate results   
- artifact immutability + signatures + provenance metadata   
- promotion logs (who approved what to prod, which artifact hash)   
- incident playbooks for supply chain events (token leak, runner compromise, dependency compromise)   
 --- 
   
## Micro cheat sheet (Chunk 2)   
- Treat dev ecosystem as **Tier-0**: compromising CI/CD or repos is equivalent to compromising production.   
- Control planes: **identity → source → build → artifact → deploy** (each needs isolation + auditability).   
- Most common failures: weak branch protection, pipeline secrets leakage, shared runners, mutable artifacts, unmanaged dependencies.   
- High assurance comes from: **review gates + pinned inputs + signed/immutable artifacts + provenance + strict RBAC + strong audit trails**.   
 --- 
   
Say **“next”** for **Chunk 3/5: 8.3 Assess effectiveness of software security** (metrics, maturity, risk-based thresholds, test strategy across SAST/DAST/SCA/IAST/fuzzing, threat-model validation, and how to prove “secure enough to ship”).   
Next   
## Domain 8 — Chunk 3/5   
### 8.3 Assess the effectiveness of software security (prove it’s secure enough to ship, and stays secure)   
This chunk answers the hardest question in software security:   
> “How do you know your SDLC security controls are actually working?”   

Not “we ran a scanner,” not “we have a policy,” but **measurable, repeatable assurance** that:   
- vulnerabilities are prevented by design where possible,   
- discovered early when not preventable,   
- fixed fast and correctly,   
- not reintroduced,   
- and the supply chain/release path can be trusted.   
 --- 
   
# 1) 8.3 control objective (what effectiveness means)   
**Effectiveness = controls produce the intended outcomes under real constraints.**   
In practice, you must be able to prove **four outcomes**:   
1. **Prevention outcome**   
   
The system resists common classes of attack by construction (authZ, input validation, secure defaults, least privilege, safe error handling).   
1. **Detection outcome**   
   
When weaknesses exist, your processes reliably detect them (SAST/DAST/SCA/IAST/fuzzing/manual review) before release, and production monitoring catches exploitation attempts.   
1. **Response outcome**   
   
When vulnerabilities are found (pre- or post-release), you triage, patch, release, and verify quickly within SLAs.   
1. **Integrity outcome**   
   
Builds and releases are tamper-resistant: artifact provenance is known, signatures are trustworthy, and “who shipped what” is provable.   
 --- 
# 2) The “effectiveness model”: leading vs lagging indicators (you need both)   
You can’t measure software security with one number. Mature programs use:   
## 2.1 Leading indicators (predict future risk)   
These measure whether the *process* is likely to produce secure outcomes:   
- % of projects with current threat model (and updated on trigger events)   
- % of critical code paths covered by security unit tests / negative tests   
- % of builds producing SBOM + provenance attestations   
- % of repos with protected branches + required reviews + pipeline gates   
- dependency hygiene (pinned versions, policy compliance, critical dependency review)   
- secrets management compliance (no secrets in repo; vault usage; rotation)   
- code review quality metrics (coverage of security-critical files by required reviewers)   
   
**Leading indicators tell you if the factory is healthy.**   
## 2.2 Lagging indicators (what actually happened)   
These measure the real-world outcome:   
- vulnerability escape rate (bugs found after release)   
- time-to-triage and time-to-fix (by severity)   
- recurrence rate (same class reintroduced)   
- incident rate tied to software defects (auth bypass, injection, SSRF, RCE)   
- exploitability in the wild for shipped vulnerabilities   
- production security events tied to app (WAF blocks, auth anomalies, abuse spikes)   
   
**Lagging indicators tell you if the product was safe.**   
 --- 
# 3) The “evidence spine” for 8.3 (what you must be able to show)   
When asked “prove security,” you should produce an **assurance pack** per release (or per major milestone):   
### 3.1 Requirements traceability   
- security requirements list   
- mapping: requirement → design decision → tests (pass criteria) → release evidence   
   
### 3.2 Threat model traceability   
- data-flow diagram + trust boundaries   
- top threats and mitigations   
- verification mapping: each mitigation has at least one test type proving it   
   
### 3.3 Testing record with thresholds (risk-tiered)   
- SAST/DAST/SCA/IAST/fuzz results   
- manual review results for high-risk components   
- penetration test / BAS results when required   
- exceptions register (what failed, why accepted, expiry, compensating controls)   
   
### 3.4 Release integrity proof   
- artifact hash + signature verification logs   
- SBOM + provenance/attestation   
- promotion chain (dev→stage→prod) showing the same immutable artifact moved forward   
   
### 3.5 Post-release readiness   
- vuln intake channels (bug bounty, reports, scanning)   
- patch SLAs and emergency release process   
- monitoring dashboards and alert coverage for abuse/exploitation signals   
 --- 
   
# 4) Effectiveness assessment methods (how you evaluate the program)   
8.3 expects you to assess effectiveness across **controls + process + outcomes**. Here is the most reliable set of methods (use more than one):   
## 4.1 Control testing effectiveness (quality of testing program)   
You evaluate:   
- **coverage**: what code/apps/interfaces/dependencies were actually assessed?   
- **depth**: were tests meaningful (auth’d scanning, real threat scenarios) or superficial?   
- **precision**: false positive rate and triage efficiency (noise kills programs)   
- **repeatability**: can the same tests run every build (security regression suite)?   
   
## 4.2 Defect discovery timing (shift-left reality check)   
Measure “where bugs are found”:   
- caught in design review (best)   
- caught in code review / unit tests   
- caught in CI scanning   
- caught in staging validation   
- caught after production release (worst)   
   
A healthy program pushes issues earlier, reducing cost and risk.   
## 4.3 Root-cause analysis of vulnerabilities (prevention maturity)   
For every significant vuln class found, categorize root cause:   
- missing requirement   
- threat model gap   
- design flaw (trust boundary mistake)   
- implementation flaw (validation, authZ logic)   
- dependency vulnerability   
- insecure configuration/deployment   
- pipeline/control failure (gate bypass, exception misuse)   
   
Then verify the fix wasn’t just “patch the symptom,” but improved the system:   
- new unit tests   
- new gate   
- new baseline rule   
- new reusable secure component   
   
## 4.4 “Abuse-case validation” (do your controls stop realistic attacker behavior?)   
Run:   
- misuse case tests (negative testing)   
- authZ boundary tests (horizontal/vertical access)   
- API abuse tests (rate limit, auth replay, token misuse)   
- SSRF/data exfil path tests (cloud metadata, internal services)   
- deserialization/parser tests where relevant   
- business-logic abuse tests (workflow bypass)   
   
This is how you assess effectiveness beyond “scanner results.”   
## 4.5 Independent validation (objectivity)   
You periodically use:   
- external penetration tests for critical apps   
- red/purple exercises for detection/response   
- bug bounty programs (for public-facing services)   
- third-party audits for process integrity (especially regulated)   
   
**Effectiveness improves when independence is built into the measurement loop.**   
 --- 
# 5) Metrics that actually work (operator-grade, not vanity)   
## 5.1 Core engineering metrics   
- **MTTT / MTTF**: mean time to triage / fix (by severity)   
- **Escape rate**: vulns found after release / total found   
- **Reopen rate**: fixed then reappeared (bad fixes or missing regression tests)   
- **Regression coverage**: % of top vuln classes with automated regression tests   
- **Security test coverage**: % of critical endpoints/functions exercised by security tests   
   
## 5.2 Supply chain + pipeline integrity metrics   
- % builds with SBOM + provenance   
- % artifacts signed and signature verified at deploy   
- number of pipeline “policy bypass” events (should be near zero)   
- secret leak incidents per quarter (should trend down)   
- dependency policy violations per build (should trend down)   
   
## 5.3 Design maturity metrics   
- % projects with current threat model   
- % high-risk changes that triggered threat model update   
- % new external interfaces with security review completed before release   
   
## 5.4 Production feedback metrics (closing the loop)   
- WAF/API gateway blocked attack rates (by endpoint)   
- auth anomalies (credential stuffing, token replay patterns)   
- abuse rate limiting triggers   
- security incident count attributable to app defects   
   
**Important:** interpret these carefully. “More blocked attacks” can mean “more attacks” not “worse code.” Trend them alongside releases and exposure changes.   
 --- 
# 6) Risk-tiered “release readiness” (how you decide secure enough to ship)   
A common reason programs fail is treating every system equally. Effectiveness assessment must be **risk-tiered**.   
## Tier 0/1 (crown jewels: identity, payments, control planes)   
Release gates typically require:   
- threat model updated and reviewed   
- SAST + SCA gates pass (no critical/high without approved exception)   
- DAST/IAST where applicable   
- targeted manual review of auth/authZ and sensitive flows   
- penetration test or adversary emulation on major changes   
- SBOM + provenance + signing mandatory   
- explicit rollback and emergency patch plan   
   
## Tier 2 (important business apps)   
- automated gates + focused manual review for sensitive components   
- periodic independent testing   
- SBOM + signing recommended/required depending on policy   
   
## Tier 3 (low-risk internal tools)   
- baseline controls and minimal gates   
- still must not leak secrets or bypass identity standards   
- “safe by default” templates reduce burden   
   
**Effectiveness is proven when the tiered gates produce low escape rates without paralyzing delivery.**   
 --- 
# 7) Maturity models (how you assess program capability over time)   
Domain 8 expects maturity-model awareness (e.g., CMM-style). In practice, software security maturity is assessed by how consistently you can produce secure outcomes across teams.   
A pragmatic maturity progression:   
- **Level 1: Ad hoc** — security is best effort; late discoveries; inconsistent results   
- **Level 2: Repeatable** — basic gates, some standards, some measurement   
- **Level 3: Defined** — formal secure SDLC with required work products; consistent processes   
- **Level 4: Managed** — metrics-driven decisions; predictable outcomes; strong evidence packs   
- **Level 5: Optimizing** — continuous improvement; automation + learning loop; low escape rates   
   
Effectiveness assessment is how you prove you’re moving up levels.   
 --- 
## Micro cheat sheet (Chunk 3)   
- 8.3 = **prove security controls are effective** (not just present).   
- Use **leading indicators** (factory health) + **lagging indicators** (real outcomes).   
- Evidence spine: requirements → threat model → testing thresholds → release integrity → post-release response.   
- Measure **escape rate, time-to-fix, recurrence, regression coverage**, and pipeline integrity.   
- Decide “secure enough to ship” with **risk-tiered gates**, not one-size-fits-all.   
- Mature programs perform RCA and turn findings into **regression guards** (tests, gates, baselines).   
 --- 
   
Say **“next”** for **Chunk 4/5: 8.4 Assess security impact of acquired software** (COTS/SaaS/OSS/third-party libraries, supplier assurance, SBOM, licensing, evaluation, acceptance criteria, and continuous monitoring of vendor risk).   
Next   
## Domain 8 — Chunk 4/5   
### 8.4 Assess security impact of acquired software (COTS/SaaS/OSS/third-party) — how to “buy safely” and keep it safe   
This chunk is the **build vs buy vs integrate** security problem. Most enterprises run more *acquired* software than custom code, so your SDLC security is incomplete unless procurement, vendor assurance, and dependency governance are as rigorous as coding standards.   
 --- 
# 1) 8.4 control objective (what must be true)   
**Any acquired component (product, service, library, SDK, container, API) must:**   
1. **fit your security requirements** (auth, logging, encryption, privacy, availability),   
2. have **known and acceptable risk** (documented, approved, time-bounded exceptions),   
3. be **verifiable and maintainable** (patching, updates, monitoring, support lifecycle),   
4. not compromise **supply chain integrity** (provenance, signing, controlled update channels),   
5. be continuously reassessed over its lifecycle (new CVEs, new vendor practices, new usage scope).   
   
In other words: **you don’t “approve software once.” You approve a risk relationship.**   
 --- 
# 2) Acquired software types and their unique risk signatures   
## 2.1 COTS / packaged software (installed binaries)   
**Risk profile:**   
- limited visibility into internals (black box)   
- patch cadence and update integrity are vendor-controlled   
- config complexity can create insecure deployments   
- EOL/EOS risk (no updates = guaranteed security debt)   
   
**Key question:** can you harden and monitor it sufficiently even without source code?   
## 2.2 SaaS / cloud services (vendor-run runtime)   
**Risk profile:**   
- shared responsibility: vendor secures platform, you secure identity, configs, data usage   
- data residency, privacy, and access logging are critical   
- API keys, OAuth grants, and integrations become high-risk “side doors”   
- outages become business continuity events   
   
**Key question:** do you have enough visibility + contractual leverage + configuration control?   
## 2.3 Open-source software (OSS) components   
**Risk profile:**   
- “free” doesn’t mean maintained or secure   
- maintainer takeover, typosquatting, dependency confusion, poisoned updates   
- transitive dependencies explode the attack surface   
- license compliance becomes a legal risk dimension   
   
**Key question:** can you govern versioning, provenance, and update behavior at scale?   
## 2.4 Third-party libraries/SDKs (embedded into your code)   
**Risk profile:**   
- becomes part of your attack surface and your liability   
- often handles sensitive functions (auth, crypto, serialization, parsing)   
- vulnerabilities can be exploited through your app even if you “didn’t write the code”   
- hidden network calls/telemetry can create compliance and exfil risks   
   
**Key question:** can you constrain what it can do (permissions, network egress, sandboxing)?   
## 2.5 Appliances / managed security tools   
**Risk profile:**   
- privileged position (often sees credentials, traffic, secrets)   
- supply chain compromise can become catastrophic   
- vendor remote access and update channels are critical risks   
   
**Key question:** is the tool itself a Tier-0 asset with strong isolation and monitoring?   
 --- 
# 3) The acquisition lifecycle (how to evaluate risk correctly)   
## Phase A — Define “what good looks like” (requirements before vendor selection)   
Before you evaluate products, you need:   
- security requirements (authN/authZ, encryption, logging, retention, admin roles)   
- data classification and privacy requirements   
- integration requirements (SSO, MFA, SCIM provisioning, API constraints)   
- availability requirements (SLA, RTO/RPO expectations)   
- operational requirements (monitoring hooks, exportable logs, incident response support)   
   
**If you skip this, you’ll buy something that works for features but breaks security by design.**   
## Phase B — Due diligence (security evaluation before contract)   
A defensible due diligence package includes:   
- vendor security posture review (policies, secure SDLC, vulnerability response)   
- evidence of testing practices (how they find and fix bugs)   
- support lifecycle (patch cadence, EOL policy)   
- incident/breach disclosure procedures   
- data handling and privacy commitments (collection, retention, sub-processors)   
- access control model (RBAC, MFA, least privilege)   
- audit/logging capabilities (exportability, integrity, retention)   
   
## Phase C — Acceptance criteria (go/no-go decision)   
Your acceptance criteria must be:   
- measurable   
- mapped to your controls   
- enforceable contractually (if SaaS/managed)   
- validated technically (where possible)   
   
## Phase D — Deployment hardening (secure configuration and environment integration)   
Most “vendor-secure products” become insecure because of default configs:   
- insecure default roles   
- overly broad API keys   
- weak logging   
- open network exposure   
- insecure integration patterns   
   
So you must apply:   
- hardening baseline   
- least privilege IAM   
- egress restrictions where feasible   
- telemetry onboarding (logs to SIEM)   
- admin access restrictions (PAM/JIT)   
   
## Phase E — Continuous monitoring (the “relationship” phase)   
- vulnerability monitoring (for product + dependencies)   
- configuration drift monitoring (especially SaaS admin settings)   
- supplier risk changes (ownership changes, policy changes, incident history)   
- usage drift (new data types, new integrations, new business units)   
 --- 
   
# 4) Technical evaluation methods (what you can actually test)   
## 4.1 Black-box security testing (most common for COTS/SaaS)   
- authenticated scanning (if web/API)   
- API security testing (authZ boundaries, rate limits, token misuse)   
- integration abuse testing (SSO flows, SCIM provisioning, OAuth scopes)   
- network and egress behavior inspection (unexpected outbound calls)   
- configuration review against your baseline   
   
## 4.2 Binary and behavior analysis (for installed software)   
- verify signing and integrity of installers/updates   
- sandbox execution to observe process/network/file/registry behaviors   
- validate privilege requirements (does it demand admin unnecessarily?)   
- validate update mechanism security (TLS, signature validation, update source)   
   
## 4.3 Dependency transparency and provenance checks (for OSS/libraries)   
- enforce pinned versions (lockfiles)   
- generate and review SBOMs   
- review critical dependency maintainership/activity signals   
- restrict allowed registries and require internal mirrors where feasible   
- block known-vulnerable versions and risky packages   
   
## 4.4 Cloud/SaaS control-plane review (for SaaS)   
- admin audit logs enabled and exportable   
- MFA and conditional access supported and enforced   
- role model supports least privilege (no “everyone is admin”)   
- data access logs exist (who accessed what)   
- keys/tokens are scannable and manageable (rotation, scoping)   
 --- 
   
# 5) Contractual and governance controls (how you make requirements enforceable)   
For acquired software, especially SaaS/managed services, security is often **contract-enforced**:   
## 5.1 Security clauses that matter   
- breach notification timelines and cooperation obligations   
- vulnerability disclosure and patch expectations   
- right to audit / evidence access (or acceptable audit reports)   
- sub-processor controls and change notification   
- data ownership, retention, deletion guarantees   
- encryption requirements (at rest/in transit) and key-management commitments   
- incident response support and evidence preservation support   
- uptime/availability SLAs and penalty clauses (where feasible)   
   
## 5.2 Risk acceptance and exceptions (the only safe way to “ship with risk”)   
If a requirement cannot be met:   
- document exception, compensating controls, expiry, re-evaluation triggers   
- require monitoring to detect exploitation paths   
- ensure the right authority signs acceptance   
 --- 
   
# 6) Integration risk (where “safe software” becomes unsafe)   
Acquired software is rarely isolated. Integration creates new attack surfaces:   
## 6.1 Identity integrations (highest risk)   
- SSO misconfig can grant excessive access   
- SCIM provisioning errors can create orphan accounts   
- OAuth consent can grant overly broad access to mail/files/calendar   
   
**Controls:**   
- least privilege scopes   
- admin consent restrictions   
- periodic review of OAuth grants and app permissions   
- disable unused integrations   
   
## 6.2 Data flows and trust boundaries   
- confirm where data is stored/processed   
- confirm who can access it (vendor admins? support engineers?)   
- define what logs prove access   
- prevent shadow exports (uncontrolled connectors)   
   
## 6.3 Network connectivity and egress   
- restrict inbound exposure (no public admin panels unless required and hardened)   
- restrict outbound egress where possible (especially from servers)   
- monitor DNS/proxy logs for unexpected destinations   
 --- 
   
# 7) Continuous assurance (the part most programs miss)   
You need “always-on” monitoring for acquired software:   
## 7.1 Vulnerability intelligence loop   
- track product CVEs and dependency CVEs   
- maintain patch SLAs   
- validate patches applied and effective   
- maintain compensating controls during patch windows   
   
## 7.2 Configuration drift loop (especially SaaS)   
- periodic review of security settings (MFA, sharing defaults, retention policies)   
- alert on privilege changes and new admin accounts   
- review external sharing and public link creation   
- ensure audit logs remain enabled and exported   
   
## 7.3 Vendor risk change triggers   
Reassess when:   
- vendor is acquired/merged   
- terms of service change materially   
- major outage or breach occurs   
- new sub-processors introduced   
- you expand usage scope (new data types, new regions, new criticality)   
 --- 
   
# 8) Practical acceptance checklist (use this as your go/no-go gate)   
For each acquired product/service/library, require answers to:   
### Security capabilities   
- supports MFA/SSO and least-privilege roles   
- produces audit logs for admin actions and data access   
- supports secure configuration and hardened defaults   
- has secure update mechanism (signed, verifiable, controlled)   
   
### Operational readiness   
- patch cadence and EOL policy are acceptable   
- incident support and evidence cooperation defined   
- logs can be exported to your monitoring platform   
- backup/recovery story is acceptable (especially for SaaS data)   
   
### Supply chain controls   
- SBOM/provenance available (or internally generated for dependencies)   
- dependencies are pinned and sourced from controlled registries   
- artifact integrity is verifiable (hash/signature)   
   
### Legal/privacy   
- data residency and retention controls   
- deletion guarantees and offboarding process   
- sub-processor governance and notice   
   
### Risk decision   
- open risks documented, compensated, time-bounded, and approved   
 --- 
   
## Micro cheat sheet (Chunk 4)   
- 8.4 is **build/buy/integrate security**: you’re approving a *risk relationship*, not a one-time purchase.   
- Different acquisition types have different primary risks (COTS black box, SaaS shared responsibility, OSS supply chain, SDK embedded liability).   
- Effectiveness requires **requirements → due diligence → acceptance criteria → secure deployment → continuous monitoring**.   
- The hardest failures are integration failures: **SSO/OAuth/SCIM, APIs, tokens, and data flows**.   
- Continuous assurance is mandatory: CVEs, config drift, vendor risk changes, and usage drift.   
 --- 
   
Say **“next”** for **Chunk 5/5: 8.5 Secure coding guidelines and standards** — the deep “bug-class prevention” layer (input validation, authZ, crypto misuse, memory safety, concurrency, error handling, logging, secrets, API security patterns, and how to turn them into enforceable coding standards + code review gates).   
Next   
## Domain 8 — Chunk 5/5   
### 8.5 Secure coding guidelines and standards (bug-class prevention + enforceable engineering rules)   
This chunk is the “**prevent whole classes of vulnerabilities by construction**” part of Domain 8.5: you’re not just telling developers “be careful,” you’re defining **standards that can be enforced** in code review and CI/CD gates.   
 --- 
# 1) 8.5 control objective   
**Every codebase follows secure coding standards that:**   
- prevent common bug classes,   
- enforce consistent security decisions (especially authZ),   
- require safe defaults and safe failure behavior,   
- are testable (unit/negative tests),   
- are enforceable (lint, static checks, code review gates),   
- and evolve based on incidents and newly discovered vuln classes.   
 --- 
   
# 2) The secure coding standards stack (how to make “guidelines” real)   
A mature org has layered standards:   
1. **Language-level standard**   
   
Rules specific to Java/C#/Go/Python/JS/C++ (memory safety, type safety, concurrency patterns).   
1. **Framework-level standard**   
   
Rules for the chosen web framework, ORM, auth middleware, logging libraries, API gateways.   
1. **Org-level standard**   
   
Cross-cutting rules: secrets, crypto usage, authZ design, input validation, logging, privacy, dependency policy.   
1. **Service-level standard**   
   
Threat-model-informed constraints specific to an application (data types, trust boundaries, rate limits, abuse protections).   
**If you only have level 4 (service rules), you will never scale.**   
**If you only have level 1 (generic rules), you will miss real threats.**   
 --- 
# 3) The Top secure coding domains (what standards must cover)   
## 3.1 Input validation and output encoding (the “trust boundary law”)   
### Rule set (enforceable)   
- Validate **at trust boundaries** (entry points): API handlers, message consumers, file parsers, webhooks.   
- Prefer **allowlists** over denylists (known-good formats).   
- Normalize before validate (canonicalize path/encoding).   
- Treat all external data as hostile: headers, cookies, query params, JSON, XML, multipart, file uploads.   
- Encode on output for the correct context (HTML/JS/URL/SQL/LDAP/etc.).   
   
### Common failure modes   
- “We validated in the UI” (bypass via API).   
- Partial validation (checks type but not length/range/charset).   
- Multiple parsers interpret input differently (smuggling-style bugs).   
   
### Verification hooks   
- negative tests for invalid inputs   
- fuzzing on parsers and boundary endpoints   
- schema validation (JSON schema / protobuf validation patterns where used)   
 --- 
   
## 3.2 Authentication (authN) correctness (identity proof)   
### Standards you must enforce   
- Use a central auth mechanism (SSO/OIDC/SAML) rather than rolling your own.   
- Don’t store plaintext secrets; strong hashing for passwords when applicable.   
- MFA for privileged roles and sensitive actions.   
- Session security rules:   
    - secure cookies, short-lived tokens, rotation on privilege changes   
    - logout invalidates sessions; “remember me” is risk-governed   
- Account recovery is treated as high-risk (rate-limited, audited).   
   
### Failure modes   
- “Password reset” is an attacker’s favorite backdoor.   
- Token leakage via logs, URLs, referrers, client-side storage.   
 --- 
   
## 3.3 Authorization (authZ) correctness (the #1 breach class)   
AuthZ bugs are usually business-impact catastrophic because they violate data boundaries.   
### Enforceable authZ rules   
- **Default deny**: no implicit access.   
- Centralized policy: use a consistent authorization library/middleware.   
- Every request must be authorized at the **resource level**, not just “user is logged in.”   
- Separate:   
    - **horizontal access**: user A must not access user B’s objects   
    - **vertical access**: non-admin must not access admin actions   
- Require explicit checks on:   
    - object ownership   
    - tenant/account boundary   
    - role/permission scopes   
    - action-specific requirements (e.g., “transfer money” requires step-up auth)   
   
### Patterns that prevent authZ drift   
- “Policy engine” pattern (attribute-based or role-based) used consistently.   
- “Resource handle” pattern: accessors require authorization context.   
- “Service-to-service identity” for internal calls (don’t trust internal network).   
   
### Verification hooks   
- authorization unit tests for every sensitive endpoint   
- negative tests for cross-tenant and privilege escalation attempts   
- property-based tests for access invariants (“no user can access another user’s record”)   
 --- 
   
## 3.4 Secrets management (do not let secrets become code)   
### Enforceable rules   
- No secrets in source control (block via secret scanning gates).   
- Secrets are injected at runtime from a vault/KMS.   
- Short-lived credentials preferred (token federation) over static keys.   
- Rotation is mandatory and proven.   
- Never log secrets or tokens; redact.   
   
### Failure modes   
- CI logs leak secrets.   
- “temporary debug key” stays forever.   
- Shared keys across environments (dev key works in prod).   
 --- 
   
## 3.5 Cryptography usage (don’t invent crypto; don’t misuse it)   
### Enforceable rules   
- Use vetted libraries; never implement cryptographic primitives.   
- Use modern protocols and safe modes (AEAD where applicable).   
- Key management is part of crypto:   
    - keys stored in KMS/HSM   
    - rotation and revocation procedures   
    - separation of duties for key admins vs app admins   
- Randomness must be cryptographically strong for secrets.   
   
### Failure modes   
- hard-coded keys   
- weak random number generation   
- “encryption” without integrity (tampering possible)   
- reusing nonces/IVs in AEAD modes   
   
### Verification hooks   
- crypto linting rules (approved algorithms only)   
- automated scanning for weak algorithms and hard-coded keys   
- key access audit logs reviewed   
 --- 
   
## 3.6 Error handling and safe failure (don’t leak, don’t crash)   
### Enforceable rules   
- Fail closed for security decisions (deny on error).   
- Return generic errors to users; log detailed errors internally.   
- No stack traces or debug endpoints in production.   
- Rate limit and circuit-breaker patterns for resilience.   
   
### Failure modes   
- verbose errors leak internal structure (SQL, paths, stack traces).   
- exception paths bypass authorization checks.   
 --- 
   
## 3.7 Logging, auditing, and non-repudiation (build for investigations)   
### Enforceable logging rules   
- Log security events:   
    - auth successes/failures   
    - privilege changes   
    - admin actions   
    - sensitive data access   
    - configuration changes   
- Logs must include:   
    - identity (user/service)   
    - request ID/correlation ID   
    - source IP/device context   
    - action outcome   
- Logs must not include secrets or sensitive payloads.   
- Logs are immutable once shipped (ops control).   
   
### Failure modes   
- logs too sparse to investigate   
- logs too verbose and leak secrets/PII   
- inconsistent identifiers prevent correlation   
 --- 
   
## 3.8 API security (your external contract surface)   
### Enforceable API rules   
- strong authN for APIs; avoid long-lived static API keys for critical functions   
- mTLS for high-value service-to-service paths when appropriate   
- strict authorization per endpoint and per object   
- input validation schemas + size limits   
- rate limiting, quotas, abuse detection   
- versioning and deprecation policy   
- protect against replay where relevant   
   
### Failure modes   
- “UI is secure but API is open”   
- insecure default endpoints (debug/admin)   
- missing rate limits → credential stuffing and scraping   
 --- 
   
## 3.9 Memory safety and language-specific bug classes   
### C/C++ (classic)   
- buffer overflows, use-after-free, integer overflow   
- enforce safe libraries, bounds checks, fuzzing on parsers   
- prefer memory-safe languages for new components handling untrusted inputs   
   
### Managed languages (Java/C#/Go)   
- injection, deserialization, authZ logic bugs still dominate   
- concurrency and unsafe reflection patterns   
- dependency and serialization frameworks can reintroduce high-risk bugs   
   
### Scripting (Python/JS)   
- injection and sandbox escapes   
- dependency/supply chain risk   
- insecure deserialization and template injection patterns   
   
**Standard strategy:** select language/tooling that reduces whole bug classes for the most exposed components.   
 --- 
## 3.10 Concurrency and race conditions (often missed in “web-only” thinking)   
- time-of-check vs time-of-use (TOCTOU)   
- idempotency for retries   
- safe locking patterns   
- transaction boundaries in DB operations   
- distributed race conditions in microservices   
   
**Verification hooks:** targeted race tests, chaos tests for retries, idempotency tests.   
 --- 
# 4) How to enforce secure coding standards (this is what makes 8.5 “real”)   
## 4.1 Definition of Done (DoD) includes security   
For security-critical work:   
- threat model updated if triggers occur   
- security tests added (unit + negative)   
- SAST/SCA gates pass   
- logging requirements satisfied   
- secrets policy satisfied   
   
## 4.2 Code review gates (human enforcement)   
- CODEOWNERS for sensitive modules (auth, crypto, access control, pipeline)   
- ban self-approval on critical paths   
- require “security checklist” fields on PR templates   
- require evidence links (tests run, scanning results)   
   
## 4.3 CI/CD enforcement (automated enforcement)   
- secret scanning blocks merges   
- SCA blocks vulnerable dependencies above threshold   
- SAST blocks critical patterns (e.g., unsafe deserialization, SQL concat)   
- unit tests + security tests required   
- artifact signing and immutability enforced before deploy   
   
## 4.4 Secure libraries and shared components (scale mechanism)   
To scale secure coding:   
- create approved, maintained shared libraries:   
    - auth middleware   
    - authorization policy engine   
    - logging wrapper with safe defaults   
    - input validation utilities   
    - crypto wrappers using approved algorithms   
- teams consume these rather than inventing their own   
   
**Security at scale is mostly building reusable secure primitives.**   
 --- 
# 5) The “secure coding standards pack” (what you should output as a CISSP-level designer)   
A mature program produces:   
1. **Secure coding standard document** (organization-wide)   
2. **Language-specific supplements** (Java/C#/Go/JS/Python/C++)   
3. **Secure PR checklist** for sensitive changes   
4. **Approved crypto and secret handling standard**   
5. **Logging and audit event standard**   
6. **Dependency policy** (allowed registries, pinned versions, license rules)   
7. **Secure CI/CD gate policy** (what blocks release)   
8. **Exception process** (time-bounded, monitored, re-approved)   
9. **Training plan** (role-based: developers, reviewers, release managers)   
 --- 
   
## Micro cheat sheet (Chunk 5)   
- 8.5 = **bug-class prevention** + enforceable standards.   
- The biggest real breach class is **authorization logic**—standardize and test it.   
- Standards must cover: validation/encoding, authN/authZ, secrets, crypto, error handling, logging, API security, dependency hygiene, and language-specific risks.   
- Enforcement requires: **DoD + review gates + CI gates + shared secure libraries**.   
- “Secure coding” without enforcement becomes optional guidance and fails at scale.   
   
   
   
   
standards).   
|                                                 8.x Area   <br> |                                                Control objective (what must be true)   <br> |                                                                                                                                     Key operational controls (what you implement)   <br> |                                                                               Evidence / artifacts (what you must be able to show)   <br> |                                                                                       KPIs / KRIs (how you measure health/risk)   <br> |                                              Common failure modes (what breaks in real orgs)   <br> |                                                                   Validation tests / drills (how you prove it works)   <br> |                               4-book coverage   <br> |
|:----------------------------------------------------------------|:--------------------------------------------------------------------------------------------|:-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|:------------------------------------------------------------------------------------------------------------------------------------------|:---------------------------------------------------------------------------------------------------------------------------------------|:----------------------------------------------------------------------------------------------------|:----------------------------------------------------------------------------------------------------------------------------|:-----------------------------------------------------|
|                          **Cross-cutting program spine**   <br> |   Secure software is produced predictably across teams and stays secure post-release   <br> |          Security governance for SDLC; roles/RACI (product, dev, security, SRE); risk tiering; release gating policy; exception governance; secure-by-default templates; training   <br> | Secure SDLC policy; control catalog; RACI; exception register; tiering model; gate definitions; security backlog; training records   <br> |     KPI: % projects onboarded to secure SDLC; KRI: unmanaged projects; KPI: exception expiry compliance; KRI: “forever” waivers   <br> | Security is optional; inconsistent practices across teams; no ownership; no exception expiry   <br> |         Annual program audit; sampling: trace requirement→code→test→release→monitoring; exception re-approval drills   <br> |                                     **All 4**   <br> |
|             **8.1 SDLC integration (all methodologies)**   <br> |               Security work products exist regardless of Agile/Waterfall/DevOps/SAFe   <br> |     Security requirements as stories; abuse stories; threat model triggers; secure “Definition of Done”; design review gates; change control integration; secure maintenance plan   <br> |             SDLC playbook; DoD checklist; secure backlog; threat model artifacts; design review notes; release readiness checklist   <br> |                    KPI: % epics with threat model update; KRI: major changes without threat review; KPI: security DoD adherence   <br> |                    “Agile means no docs”; security pushed to end; threat model never updated   <br> |                                   Sprint-level security review; pre-release gate simulation; “change trigger” audits   <br> |                                     **All 4**   <br> |
|                         **8.1 Integrated product teams**   <br> |  Security decisions are made where engineering happens (not as late external audits)   <br> |                            Security champions; embedded security review in sprint rituals; architecture guilds; reusable secure components; escalation path for high-risk changes   <br> |                                          Champion roster; review cadence; shared security libraries; architecture decision records   <br> |                                        KPI: review turnaround time; KRI: bypassed reviews; KPI: reuse rate of secure components   <br> |                             Central security bottleneck; teams bypass; inconsistent patterns   <br> |                                   Random PR sampling for required reviewer compliance; champion effectiveness review   <br> |                            **OSG7/CBK4/AIO8**   <br> |
|             **8.1 Maturity models and improvement loop**   <br> |                             Security capability is measurable and improves over time   <br> |                                                                     Maturity baseline; quarterly improvement plan; metrics-driven adjustments; incident-to-standard feedback loop   <br> |                                          Maturity assessment results; roadmap; quarterly scorecards; post-incident control updates   <br> |                                                     KPI: escape rate trend; KPI: time-to-fix trend; KRI: recurring vuln classes   <br> |                                   “Maturity” as vanity scoring; no changes based on findings   <br> |                                                 Quarterly maturity review with evidence; regression-proof validation   <br> |                                     **All 4**   <br> |
|                        **8.1 Ops & maintenance in SDLC**   <br> |   Security doesn’t end at release; patching, monitoring, and deprecation are planned   <br> |                                                 Vulnerability response SLAs; secure config baselines; observability requirements; incident hooks; EOL policy; secure decommission   <br> |                                              Vuln intake process; patch SLAs; monitoring dashboards; runbooks; deprecation records   <br> |                                                KPI: time-to-triage/fix; KRI: unpatched known exploited; KPI: logging coverage %   <br> |              “Ship and forget”; no ownership post-release; missing logs block investigations   <br> |                                              Emergency patch drill; production incident simulation for app telemetry   <br> |                                     **All 4**   <br> |
|                        **8.2 Dev ecosystem plane model**   <br> |           Dev ecosystem is treated as Tier-0 (identity→source→build→artifact→deploy)   <br> |                                                           Separate control planes; RBAC per plane; segmentation of runners; immutable artifacts; provenance; controlled promotion   <br> |                                                           Architecture diagram; RBAC matrices; plane boundaries; pipeline policies   <br> |                                                                  KRI: cross-plane privilege creep; KPI: % immutable deployments   <br> |                Flat trust: repo admin can also deploy prod; pipeline becomes malware highway   <br> |                                                  Tabletop “supply chain compromise” exercise; access review sampling   <br> |                                     **All 4**   <br> |
|                       **8.2 Source control protections**   <br> |                            Only reviewed, traceable changes reach protected branches   <br> |                                          Branch protection; required reviews; CODEOWNERS; signed commits (where feasible); PR templates; enforced checks; admin action monitoring   <br> |                                                    Branch rules; repo audit logs; CODEOWNERS; PR review records; commit signatures   <br> |                                                          KPI: % merges meeting policy; KRI: direct pushes to protected branches   <br> |                       Self-approval; bypass via admin; weak branch rules; missing audit logs   <br> |                                                     “Bypass attempt” drills; random PR audits; break-glass use audit   <br> |                                     **All 4**   <br> |
|                      **8.2 Pipeline-as-code governance**   <br> |                              Pipeline definitions are protected like production code   <br> |                                                     Pipeline configs in VCS; required approvals for pipeline changes; restricted CI admin roles; change alerts for pipeline edits   <br> |                                                                           Pipeline repos; approvals; CI audit logs; change tickets   <br> |                                                      KRI: pipeline policy bypass events; KPI: pipeline change review compliance   <br> |                         CI UI edits bypass review; attacker disables scans; shadow pipelines   <br> |                                                     Controlled test: attempt to remove gate; must be blocked + alert   <br> |                            **OSG7/CBK4/AIO8**   <br> |
|                              **8.2 CI runner isolation**   <br> |                     Build environment can’t leak secrets or cross-contaminate builds   <br> |                                                             Ephemeral runners; project isolation; restricted network egress; least-privileged runner permissions; hardened images   <br> |                                                             Runner configs; isolation proofs; egress rules; runner image baselines   <br> |                                                               KRI: runner reuse across tenants; KPI: secrets exposure incidents   <br> |                           Shared runners leak secrets; runner compromise steals signing keys   <br> |                                                         Red team “runner breakout” simulation; secret exposure tests   <br> |                                 **CBK4/OSG7**   <br> |
|                               **8.2 Secrets management**   <br> |                     No secrets in code; secrets are short-lived, scoped, and rotated   <br> |                                                     Secret scanning; vault/KMS injection; short-lived tokens; rotation policies; logging redaction; least-privilege secret access   <br> |                                                                   Secret scan logs; vault policies; rotation evidence; access logs   <br> |                                                     KPI: secret leak rate; KPI: rotation compliance; KRI: long-lived keys in CI   <br> |                   Secrets committed “temporarily”; logs leak tokens; shared global CI tokens   <br> |                                             “Canary secret” detection test; rotation drill; log redaction validation   <br> |                                     **All 4**   <br> |
|                      **8.2 Dependency governance (SCA)**   <br> |                        Dependencies are controlled, repeatable, and policy-compliant   <br> |                                                     Pinned versions/lockfiles; approved registries; internal mirrors; vulnerability & license policy; critical dependency reviews   <br> |                                                                      Dependency policy; lockfiles; SCA reports; exception register   <br> |                          KPI: policy violations per build; KRI: vulnerable dependency backlog; KPI: mean age of vulnerable deps   <br> |                          Typosquatting/poisoned updates; unpinned deps; “upgrade later” debt   <br> |                                                       Dependency confusion test; “vuln in dependency” response drill   <br> |                                     **All 4**   <br> |
| **8.2 SBOM + provenance + signing (artifact integrity)**   <br> |                             You can prove what shipped and prevent artifact swapping   <br> |                                           SBOM generation; build attestations; artifact signing; immutable registries; signature verification at deploy; key management (HSM/KMS)   <br> |                                             SBOMs; attestations; signatures; registry immutability config; signing key access logs   <br> |                                 KPI: % releases signed+verified; KPI: % builds with SBOM+attestation; KRI: unsigned deployments   <br> |                 Mutable tags allow swapping; signing keys exposed; deploys skip verification   <br> |                           “Swap artifact” attempt test; signature verification failure test; key compromise tabletop   <br> | **CBK4/OSG7 + extended (SLSA/Sigstore/SPDX)**   <br> |
|           **8.2 Environment separation (dev/test/prod)**   <br> |                 Dev cannot reach prod secrets; test data doesn’t leak regulated data   <br> |                                                          Network segmentation; separate identities; no prod creds in dev; masked/synthetic test data; least privilege data access   <br> |                                                           Env boundary documentation; secret scopes; test data generation policies   <br> |                                                                KRI: prod secrets accessed from dev; KPI: % masked test datasets   <br> |                             Dev uses prod snapshots; shared credentials; shadow integrations   <br> |                                                         “Prod credential in dev” detection test; data leakage drills   <br> |                                     **All 4**   <br> |
|                          **8.2 Auditability of changes**   <br> |      You can reconstruct “who changed what, who approved, who shipped, who deployed”   <br> |                                                                                  Immutable audit logs; correlation IDs; promotion logs; admin action monitoring; retention policy   <br> |                                                        Repo/CI/CD/artifact/deploy audit logs; release manifests; promotion records   <br> |                                                                  KPI: traceability completeness %; KRI: untraceable deployments   <br> |                                   No single source of truth; logs scattered; short retention   <br> |                                                       Random traceability challenge: pick release → prove full chain   <br> |                                     **All 4**   <br> |
|                       **8.3 Effectiveness (definition)**   <br> |                Security controls produce intended outcomes and detect failures early   <br> |                                                                       Risk-tiered thresholds; test strategy; independence where needed; feedback loop from incidents to standards   <br> |                                                              Release readiness criteria; control objective mapping; evidence packs   <br> |                                                                    KPI: escape rate; KPI: time-to-fix; KRI: repeat vuln classes   <br> |                              “Scanner passed” treated as secure; no context; no independence   <br> |                                      Independent spot checks; post-release defect RCA with regression guard creation   <br> |                                     **All 4**   <br> |
|                         **8.3 Test strategy across AST**   <br> |   Testing coverage is complete enough for risk tier (SAST/DAST/SCA/IAST/fuzz/manual)   <br> |                                                        Risk-tiered AST matrix; authenticated testing; negative tests; fuzzing for parsers; manual review for critical auth/crypto   <br> |                                                                 AST reports; coverage summaries; manual review notes; fuzz results   <br> |                                                       KPI: defects found pre-release vs post; KPI: false positive handling time   <br> |                                 Unauth DAST only; shallow scanning; noise leads to disabling   <br> |                                           Controlled “known vuln” test to ensure tools catch; auth’d DAST validation   <br> |                                     **All 4**   <br> |
|                          **8.3 Threat model validation**   <br> |                               Threat model isn’t shelfware; mitigations are verified   <br> |                                                                                  Threat-to-test mapping; abuse-case tests; security unit tests; review triggers for major changes   <br> |                                                         Threat model + DFD; mitigation mapping; test cases proving each mitigation   <br> |                                                         KPI: % mitigations with tests; KRI: major changes without threat update   <br> |                                                 Threat model done once; mitigations untested   <br> |                                                                    Trigger-event audits; abuse-case regression suite   <br> |                            **OSG7/CBK4/AIO8**   <br> |
|                             **8.3 Metrics & dashboards**   <br> |                                           Metrics drive decisions; no vanity metrics   <br> |                                                                                 Leading + lagging indicators; targets/owners; escalation thresholds; gaming-resistant measurement   <br> |                                                                              Metric catalog; owners; trend dashboards; action logs   <br> |                                                   KPI: MTTT/MTTF; KRI: critical findings backlog; KPI: regression test coverage   <br> |                                             Metrics exist but no actions; teams game numbers   <br> |                                                              Quarterly metric review with “action required” outcomes   <br> |                                     **All 4**   <br> |
|                   **8.3 Release readiness & exceptions**   <br> |            “Secure enough to ship” is defined, enforced, and exceptions are governed   <br> |                                                                                            Release gates; exception process with expiry; compensating controls; monitored waivers   <br> |                                                             Release checklists; gate results; exception approvals; monitoring plan   <br> |                                                            KPI: gate pass rate; KRI: active exceptions; KRI: expired exceptions   <br> |                                         Break-glass becomes default; exceptions never expire   <br> |                                                             Exception expiry drill; release gate bypass attempt test   <br> |                                     **All 4**   <br> |
|                       **8.3 Post-release feedback loop**   <br> |                       Findings become permanent improvements (tests/gates/standards)   <br> |                                                                                             RCA; add regression tests; update secure libraries; update pipelines; update training   <br> |                                                                   RCA docs; new tests; updated standards; library version rollouts   <br> |                                                                          KPI: recurrence rate; KPI: time-to-regression-test-add   <br> |                                     Same bug class returns; fixes are local and not systemic   <br> |                                                                Regression suite verifies fixed class never reappears   <br> |                                     **All 4**   <br> |
|              **8.4 Acquisition program (COTS/SaaS/OSS)**   <br> |           Acquired software fits security requirements and lifecycle risk is managed   <br> |                                      Requirements-first procurement; security questionnaires; technical validation; contract clauses; onboarding baselines; continuous monitoring   <br> |                                               Vendor assessment package; acceptance criteria; contract clauses; onboarding records   <br> |                                                                KPI: % vendors assessed; KRI: critical vendors without assurance   <br> |                    “Buy first, assess later”; vendor scope mismatch; no enforcement leverage   <br> |                                                   Mock vendor reassessment; evidence sampling from vendor assurances   <br> |                                     **All 4**   <br> |
|                                  **8.4 COTS evaluation**   <br> |                     Packaged software can be hardened/monitored and patched reliably   <br> |                                                                    Secure config baselines; patch/EOL policy; integrity checks for installers/updates; least privilege deployment   <br> |                                                                 Hardening guide; patch cadence; update integrity proof; audit logs   <br> |                                                                                  KPI: patch timeliness; KRI: EOL software count   <br> |                                  Default configs insecure; updates not verified; EOL ignored   <br> |                                                              Update integrity validation; hardening compliance scans   <br> |                            **AIO8/OSG7/CBK4**   <br> |
|                       **8.4 SaaS/shared responsibility**   <br> |         SaaS is configured securely with strong identity, logging, and data controls   <br> |                                                                       Enforce SSO/MFA; SCIM; least privilege roles; audit logs exported; sharing controls; OAuth scope governance   <br> |                                          SaaS security config snapshots; admin audit logs; sharing reports; OAuth grants inventory   <br> |                                                       KPI: admin role count; KRI: public sharing links; KRI: OAuth risky grants   <br> |                                             Shadow admins; logs disabled; overly broad OAuth   <br> |                                                          “New admin” alert test; public link creation detection test   <br> |                                 **OSG7/CBK4**   <br> |
|                               **8.4 OSS/library intake**   <br> |                OSS is governed (provenance, version pinning, policy, and monitoring)   <br> |                                                                                      Approved registries; pin versions; review critical deps; license compliance; SBOM generation   <br> |                                                                   SCA outputs; license reports; SBOMs; dependency approval records   <br> |                                                                                KPI: vulnerable deps age; KRI: policy violations   <br> |                                        Typosquats; maintainer takeover; dependency confusion   <br> |                                             Dependency confusion simulation; critical dependency change review drill   <br> |                                     **All 4**   <br> |
|                   **8.4 Supplier assurance & contracts**   <br> |                                     Vendor obligations are enforceable and monitored   <br> |                                  Security clauses (notification, IR support, audit reports); right-to-audit or acceptable assurances; subprocessor governance; deletion/retention   <br> |                                                  Signed contracts; assurance reports; subprocessor lists; breach notice procedures   <br> |                                                                           KPI: vendor assurance freshness; KRI: expired reports   <br> |                              Blind acceptance of reports; scope mismatch; stale attestations   <br> |                                                         Annual vendor evidence sampling; scope-to-use mapping review   <br> |                             **SG4/CBK4/OSG7**   <br> |
|                          **8.4 Continuous reassessment**   <br> |                                    Vendor risk changes are detected and responded to   <br> |                                                                        Monitoring triggers (breach/news, policy changes, mergers); usage drift review; configuration drift checks   <br> |                                                                   Reassessment logs; change triggers; updated acceptance decisions   <br> |                                                                                           KRI: high-risk changes not reassessed   <br> |                                     “Approved once” mindset; new integrations added silently   <br> |                                           Trigger drill: simulate vendor incident; verify response and re-evaluation   <br> |                                     **All 4**   <br> |
|              **8.5 Secure coding standards (org-level)**   <br> |                             Standards prevent common bug classes and are enforceable   <br> |                                                     Org secure coding standard + language supplements; approved crypto; secrets policy; logging standard; error handling standard   <br> |                                                                           Published standards; training; PR checklists; lint rules   <br> |                                                                            KPI: training completion; KRI: repeated coding flaws   <br> |                                    Standards exist but unenforced; inconsistent across teams   <br> |                                                Quarterly standard compliance sampling; “bad pattern” detection tests   <br> |                                     **All 4**   <br> |
|               **8.5 Input validation & output encoding**   <br> |                                Untrusted inputs cannot cause injection/parsing abuse   <br> |                                                                                    Schema validation; allowlist validation; canonicalization; context-aware encoding; size limits   <br> |                                                                                   Validation libraries; unit tests; negative tests   <br> |                                                                KRI: injection findings; KPI: % endpoints with schema validation   <br> |                               Validate only in UI; missing size limits; inconsistent parsers   <br> |                                                           Fuzzing on parsers/endpoints; negative test suite required   <br> |                                     **All 4**   <br> |
|                                **8.5 AuthN correctness**   <br> |                                      Identity proof is robust and resistant to abuse   <br> |                                                                                      Standard auth libraries; MFA for privileged; secure session/token rules; safe recovery flows   <br> |                                                                         Auth design docs; session policies; recovery policy; tests   <br> |                                                                       KRI: account takeover incidents; KPI: recovery abuse rate   <br> |                                    Weak recovery; tokens stored insecurely; session fixation   <br> |                                              Auth flow test harness; token leakage checks; recovery abuse simulation   <br> |                                     **All 4**   <br> |
|             **8.5 AuthZ correctness (top breach class)**   <br> |      Authorization is consistent and cannot be bypassed (horizontal/vertical/tenant)   <br> |                                                                                  Central policy engine/middleware; default deny; object-level checks; service-to-service identity   <br> |                                                                      Policy definitions; authZ unit tests; access invariants tests   <br> |                                                                           KRI: IDOR/priv-esc findings; KPI: authZ test coverage   <br> |                        “Logged in = authorized”; inconsistent checks; internal calls trusted   <br> |                               Property-based access tests; cross-tenant negative tests; least-privilege verification   <br> |                                     **All 4**   <br> |
|                                 **8.5 Secrets handling**   <br> |                              Secrets never live in code/logs; are rotated and scoped   <br> |                                                                                                        Secret scanning; vault injection; redact logs; short-lived creds; rotation   <br> |                                                                Secret scan results; vault policies; rotation logs; redaction tests   <br> |                                                                              KPI: secret leak incidents; KRI: long-lived tokens   <br> |                                                Debug keys persist; logs leak; shared secrets   <br> |                                              Canary secret detection drill; rotation drill; log redaction validation   <br> |                                     **All 4**   <br> |
|                               **8.5 Cryptography usage**   <br> |               Crypto is used correctly with safe defaults and key lifecycle controls   <br> |                                                                                                 Approved algorithms; vetted libs; AEAD use; KMS/HSM; nonce/IV rules; key rotation   <br> |                                                                                     Crypto standard; key usage logs; static checks   <br> |                                                                        KRI: weak algorithms found; KPI: key rotation compliance   <br> |                                               Homegrown crypto; hard-coded keys; nonce reuse   <br> |                                           Crypto lint checks; “forbidden algorithm” CI gate; key compromise tabletop   <br> |                                     **All 4**   <br> |
|                    **8.5 Error handling & safe failure**   <br> |                         Fail closed; no sensitive leaks; resilience patterns applied   <br> |                                                                                  Generic external errors; detailed internal logs; no debug in prod; rate limits; circuit breakers   <br> |                                                        Error handling standard; tests ensuring no stack traces; rate limit configs   <br> |                                                                                 KRI: info leak bugs; KPI: stability under abuse   <br> |                                        Verbose errors; exception bypass; missing rate limits   <br> |                                                      Negative tests; abuse simulation (burst traffic); chaos testing   <br> |                                     **All 4**   <br> |
|                         **8.5 Logging/auditing in code**   <br> |                Security-relevant actions are attributable and support investigations   <br> |                                                                    Audit events for auth, admin, sensitive access; correlation IDs; privacy-safe logging; immutability after ship   <br> |                                                                       Logging standard; sample logs; dashboards; retention mapping   <br> |                                                                    KPI: audit event coverage; KRI: missing logs for key actions   <br> |                                     Logs too sparse; logs leak PII/secrets; inconsistent IDs   <br> |                                                              Logging contract tests; “incident reconstruction” drill   <br> |                                     **All 4**   <br> |
|                                     **8.5 API security**   <br> |                       APIs enforce auth, validation, rate limits, and abuse controls   <br> |                                                                    Strong auth for APIs; scope-based access; mTLS where required; quotas; replay defenses; versioning/deprecation   <br> |                                                                                      API specs; gateway policies; abuse dashboards   <br> |                                                                      KRI: scraping/credential stuffing; KPI: abuse blocked rate   <br> |                                     UI secure but API open; missing rate limits; weak scopes   <br> |                                                     AuthZ boundary tests; rate-limit tests; fuzz tests on API inputs   <br> |                                     **All 4**   <br> |
|                   **8.5 Memory safety & language risks**   <br> |                     High-risk bug classes are minimized via language/tooling choices   <br> |                                                                                       Prefer memory-safe languages for exposed parsers; sanitizers; safe libraries; bounds checks   <br> |                                                                    Language selection rationale; compiler flags; sanitizer reports   <br> |                                                                                                 KRI: memory corruption findings   <br> |                                                  Unsafe C/C++ in exposed parsers; no fuzzing   <br> |                                                                        Fuzzing + sanitizer CI; crash triage pipeline   <br> |                                 **AIO8/CBK4**   <br> |
|                    **8.5 Concurrency & race conditions**   <br> |                                    Race/TOCTOU bugs are prevented with safe patterns   <br> |                                                                                                          Idempotency; transaction boundaries; locking patterns; retry-safe design   <br> |                                                                              Concurrency guidelines; tests for retries/idempotency   <br> |                                                                                                   KRI: race condition incidents   <br> |                                                  Distributed race bugs; inconsistent retries   <br> |                                                                          Chaos/retry tests; concurrency stress tests   <br> |                                 **CBK4/OSG7**   <br> |

 --- 
### “   

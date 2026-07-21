# CKV-043 — DevSecOps, Secure SDLC, SAST, DAST, SCA, and Security Gates

## 1. Purpose

This file defines **DevSecOps and Secure SDLC** as a canonical software-security operating model, not as a vendor pipeline tutorial, exploit guide, scanning-tool manual, or application-vulnerability taxonomy.

It exists to help security engineers, application-security reviewers, developers, architects, release managers, auditors, and interview candidates reason about how security is integrated into the software lifecycle:

- Which security work belongs in requirements, design, development, test, release, deployment, operations, and retirement.
- Which gates prevent unsafe code, unsafe dependencies, unsafe infrastructure, unsafe containers, leaked secrets, and untrusted artifacts from reaching production.
- Which testing technique is appropriate for the risk: SAST, DAST, SCA, IaC scanning, image scanning, secrets scanning, manual review, or penetration testing.
- Which findings must block release, which may be accepted temporarily, and which require compensating controls.
- Which evidence proves security was performed and not merely claimed.
- Which future CKV file owns deeper implementation detail.

Canonical purpose:

```text
DevSecOps = security built into engineering flow
          = requirements + design + code + pipeline + artifact + deployment + operations
          + automated checks
          + human review where automation is blind
          + risk-based gates
          + evidence
          + feedback loops
```

This file owns the **lifecycle and gating layer**. It does not own the complete API-control catalog, the full vulnerability-management program, detection-engineering methodology, incident-response workflow, or digital-forensics chain of custody.

This file does not provide:

- Vendor-specific CI/CD syntax.
- Tool-specific scanner operation manuals.
- Exploit payloads or offensive walkthroughs.
- Complete SAST/DAST/SCA product comparison.
- Full OWASP Web Top 10 or OWASP API Top 10 taxonomy.
- Full API authorization, OAuth, OIDC, JWT, gateway, or schema-validation control implementation.

Canonical exam/interview answer:

```text
Secure SDLC makes security a release requirement.
DevSecOps makes security repeatable, automated, measurable, and owned inside the delivery pipeline.
Security gates decide whether risk is acceptable before software is promoted.
```

## 2. Core Definition

**DevSecOps** is the integration of security practices, controls, automation, ownership, and evidence into DevOps workflows so that software can be designed, built, tested, released, deployed, and operated with continuous security assurance.

**Secure SDLC** is the lifecycle model that embeds security activities into every stage of software development, from initial requirements through retirement.

Canonical definition:

```text
Secure SDLC = security requirements
            + threat modeling
            + secure design review
            + secure coding standards
            + code review
            + automated testing
            + dependency governance
            + release gates
            + deployment validation
            + operational monitoring
            + vulnerability response
            + evidence retention
```

**SAST** analyzes source code, bytecode, or compiled code without executing the running application.

**DAST** tests a running application or service from the outside by interacting with exposed functionality.

**SCA** identifies third-party components, dependencies, licenses, known vulnerabilities, package metadata, and sometimes supply-chain risk signals.

**Security gate** is a policy decision point that controls whether work may proceed from one lifecycle stage to another.

```text
Finding discovered -> triage -> severity + exploitability + asset/data context -> owner -> fix/mitigate/accept -> verify -> close -> evidence
```

Security gates must be explicit. A scanner warning alone is not a gate. A gate requires:

- A defined policy.
- Risk thresholds.
- Ownership.
- Exception process.
- Evidence capture.
- Enforcement behavior.
- Retest or validation requirement.

## 3. Why DevSecOps and Secure SDLC Matter

Software risk is expensive when discovered late. A design flaw found during requirements can often be fixed through one design decision. The same flaw discovered after production may require emergency patches, data cleanup, customer notification, legal review, compensating controls, incident response, and architectural rework.

DevSecOps matters because modern software delivery is fast, automated, dependency-heavy, cloud-connected, and pipeline-driven.

Modern software risk includes:

- Insecure requirements.
- Missing abuse-case analysis.
- Weak architecture decisions.
- Unsafe code patterns.
- Broken authorization logic.
- Dependency vulnerabilities.
- Malicious or compromised packages.
- Leaked secrets.
- Unsafe infrastructure-as-code.
- Vulnerable container images.
- Misconfigured CI/CD runners.
- Mutable build artifacts.
- Weak release approval.
- Unmonitored runtime behavior.
- Incomplete evidence.

Security cannot depend on a single final penetration test. A final test can find symptoms, but it cannot reliably fix the lifecycle that produced them.

Secure SDLC reduces risk by moving security decisions earlier and making them repeatable:

```text
Late testing finds bugs.
Secure SDLC prevents classes of bugs.
DevSecOps automates prevention and proof.
```

Business value:

- Faster remediation because defects are found near the developer.
- Lower cost because security is part of normal work.
- Fewer emergency releases.
- Stronger auditability.
- Better release confidence.
- Better supply-chain visibility.
- Better incident readiness.
- Better developer learning through feedback loops.

Security value:

- Threats are considered during design, not only after deployment.
- Common bug classes become coding-standard requirements.
- Dependencies and images are monitored before release.
- Secrets are blocked before exposure becomes permanent.
- Build artifacts are traceable to source, build process, approvals, and release.
- Exceptions are visible, time-bound, and owned.

## 4. DevSecOps Mental Model

DevSecOps is not “put scanners in CI.” Scanners are only one signal source.

DevSecOps is a control system around software delivery:

```text
People        -> developers, reviewers, AppSec, platform, operations, risk owners
Process       -> requirements, design, code, review, test, release, deploy, operate
Technology    -> repositories, pipelines, scanners, artifact stores, deployment platforms
Policy        -> standards, gates, thresholds, exceptions, approvals
Evidence      -> findings, approvals, artifacts, SBOMs, attestations, logs, tickets
Feedback      -> lessons learned, standards updates, regression tests, metrics
```

The central mental model:

```text
Every code change is a security event.
Every dependency change is a supply-chain event.
Every pipeline change is a production-control-plane event.
Every release should be explainable after the fact.
```

DevSecOps has three simultaneous goals:

1. **Prevent unsafe changes.**
   - Enforce secure defaults, standards, branch protection, peer review, and mandatory tests.

2. **Detect unsafe changes early.**
   - Use SAST, SCA, secrets scanning, IaC scanning, container scanning, DAST, and manual review.

3. **Prove release integrity.**
   - Link source, commit, reviewer, build, SBOM, artifact, signature, approval, deployment, and runtime validation.

Security ownership model:

| Actor | Owns |
|---|---|
| Developers | Secure implementation, unit security tests, dependency choices, fixing findings |
| AppSec / Security Engineering | Standards, threat modeling, gates, reviews, risk guidance, validation |
| Platform / DevOps | Pipeline security, runner hardening, artifact integrity, deployment controls |
| Product / Business Owner | Risk acceptance, priority, business abuse cases, release tradeoffs |
| Operations / SRE | Runtime validation, monitoring, rollback readiness, operational evidence |
| Governance / Risk | Policy, exceptions, auditability, metrics, escalation |

Bad mental model:

```text
Security team scans at the end and tells developers what is wrong.
```

Correct mental model:

```text
Security requirements, controls, tests, gates, and evidence are embedded in normal delivery.
```

## 5. Secure SDLC Lifecycle Map

A secure SDLC maps security work to lifecycle stages.

Canonical lifecycle map:

| SDLC Stage | Security Work Product | Primary Question |
|---|---|---|
| Initiation / Planning | Security scope, data classification, risk context | What are we building and why does it matter? |
| Requirements | Security requirements, abuse cases, compliance constraints | What must never happen? |
| Design | Threat model, trust boundaries, architecture review | Is the design safe before code exists? |
| Development | Secure coding, SAST, secrets scanning, peer review | Is code being written safely? |
| Dependency / Build | SCA, SBOM, provenance, artifact signing | What components are inside and how was it built? |
| Test / Verification | DAST, integration tests, security regression tests | Does the running system resist expected abuse? |
| Release | Gates, approvals, exception review, release evidence | Is the residual risk acceptable to ship? |
| Deployment | IaC validation, image validation, config validation | Is the deployed environment controlled? |
| Operations | Monitoring, vulnerability intake, patching, telemetry | Is runtime behavior visible and maintained? |
| Retirement | Decommission, secrets revoke, endpoint removal | Is obsolete software removed safely? |

Secure SDLC does not require one specific development methodology. It can operate with waterfall, Agile, DevOps, DevSecOps, SAFe, or hybrid models.

Rule:

```text
The methodology controls timing.
Security controls the required work products and evidence.
```

Minimum secure SDLC work products:

- Security requirements.
- Threat model or risk review.
- Secure design review record.
- Secure coding standard mapping.
- Code review evidence.
- SAST/SCA/secrets scan evidence.
- DAST or runtime test evidence where applicable.
- Dependency and SBOM evidence.
- Release gate result.
- Exception record if release risk is accepted.
- Deployment validation evidence.
- Monitoring and vulnerability intake path.

## 6. Security Requirements

Security requirements define what the system must enforce, prevent, log, protect, or recover from.

Security requirements must be written alongside functional requirements. They are not generic statements such as “application must be secure.” They must be testable.

Good security requirement pattern:

```text
Subject + action + object + condition + expected control + evidence
```

Examples of requirement types:

| Requirement Type | Examples of Security Meaning |
|---|---|
| Authentication | Which users, services, clients, and systems must prove identity |
| Authorization | Which subject may act on which object under which condition |
| Data protection | What data requires encryption, masking, retention, or deletion |
| Input handling | What validation, canonicalization, size limits, and encoding are required |
| Secrets | Where credentials are stored, rotated, scoped, and injected |
| Logging | Which security events must be recorded and correlated |
| Availability | Rate limits, quotas, timeouts, retries, graceful failure |
| Supply chain | Approved dependency sources, SBOM, provenance, signing |
| Deployment | Required hardening, network controls, runtime identity, configuration validation |
| Recovery | Rollback, backups, restore validation, failover expectations |

Security requirements must be:

- Traceable to risk, regulation, control, or abuse case.
- Testable by manual or automated validation.
- Owned by a role.
- Included in acceptance criteria.
- Updated when design or business logic changes.

Weak requirement:

```text
Use secure authentication.
```

Strong requirement:

```text
Privileged administrative actions must require authenticated administrator identity, server-side authorization, MFA-backed session age not exceeding the defined threshold, audit logging of actor/action/object/result, and denied-case tests.
```

Security requirements become the source for:

- Threat modeling questions.
- Design review criteria.
- Code review checklist.
- Automated test cases.
- Release gates.
- Monitoring expectations.
- Audit evidence.

## 7. Threat Modeling Placement in SDLC

Threat modeling identifies what can go wrong before software is finalized.

In Secure SDLC, threat modeling belongs primarily in design, but it must be updated whenever the system changes materially.

Trigger conditions:

- New application or service.
- New sensitive data flow.
- New authentication or authorization model.
- New API exposure.
- New third-party integration.
- New trust boundary.
- New cloud resource pattern.
- New administrative workflow.
- Major dependency or platform change.
- Critical vulnerability class found after release.

Threat modeling should produce engineering decisions, not only diagrams.

Minimum threat-model outputs:

- Assets and data types.
- Users, roles, service identities, and trust levels.
- Entry points and exposed interfaces.
- Trust boundaries.
- Data flows.
- Abuse cases.
- Threat assumptions.
- Required mitigations.
- Residual risks.
- Validation tests.
- Logging requirements.

Threat modeling placement:

```text
Requirements -> define security objectives and abuse cases
Design       -> model flows, boundaries, privileges, dependencies
Development  -> convert threats into code patterns and tests
Testing      -> validate mitigations and negative cases
Release      -> verify unresolved threats are accepted or closed
Operations   -> update model using incidents, findings, and drift
```

Threat modeling is especially important where scanners are weak:

- Authorization logic.
- Tenant isolation.
- Business-flow abuse.
- Fraud paths.
- Workflow state changes.
- Cryptographic design.
- Trust in third-party APIs.
- SSRF impact boundaries.
- Privileged admin paths.
- Supply-chain and build-system threats.

Rule:

```text
If the risk depends on business logic, workflow meaning, or trust relationships, do not rely on scanners alone.
```

## 8. Secure Design Review Gates

A secure design review gate evaluates whether architecture is safe enough before implementation proceeds.

Design review is not a final code review. It answers whether the proposed design can enforce security requirements.

Design gate inputs:

- Business purpose.
- Data classification.
- User and service identities.
- Trust-boundary diagram.
- Data-flow diagram.
- Authentication and authorization model.
- Secrets model.
- Dependency and integration list.
- Deployment model.
- Logging and monitoring plan.
- Recovery and rollback assumptions.

Design gate questions:

- What is the security boundary?
- What must be authenticated?
- What must be authorized server-side?
- Which data is sensitive and where does it flow?
- Which components trust each other?
- Which component can change state?
- Which component can reach internal services?
- How are secrets stored and accessed?
- How are dependencies introduced and updated?
- How are administrative actions controlled?
- What can fail open?
- What evidence will prove controls work?

Design gate outcomes:

| Outcome | Meaning |
|---|---|
| Approved | Design meets security requirements and residual risk is acceptable |
| Approved with conditions | Specific controls or tests must be completed before release |
| Rework required | Design cannot enforce required controls safely |
| Risk acceptance required | Business owner must formally accept documented residual risk |

Design review must be evidence-producing. A mature design review leaves behind:

- Decision records.
- Threat model updates.
- Required controls.
- Required tests.
- Required telemetry.
- Risk acceptance records where applicable.

## 9. Secure Coding Standards at Lifecycle Level

Secure coding standards define approved and prohibited coding patterns. This file owns secure coding at the **lifecycle level**, not every language-specific rule.

A secure coding standard should cover:

- Input validation and canonicalization.
- Output encoding.
- SQL/query safety.
- Command execution safety.
- XML/parser safety.
- File upload handling.
- Deserialization restrictions.
- Authentication and authorization patterns.
- Session and token handling.
- Secret handling.
- Error handling.
- Logging and privacy.
- Cryptographic library usage.
- Dependency usage.
- Concurrency and race-condition precautions.
- Memory-safe patterns where relevant.
- Secure defaults.

Lifecycle-level requirement:

```text
Coding standard -> developer checklist -> code review rules -> SAST rules -> unit/security tests -> release gate evidence
```

A standard that is not enforceable becomes documentation debt.

Enforcement methods:

- Pull-request templates.
- Peer review checklists.
- Secure code review for high-risk changes.
- SAST rule sets.
- Linting for dangerous patterns.
- Approved libraries.
- Central security wrappers.
- Unit tests for validation and authorization.
- Training tied to recurring findings.

Strong standards include examples of safe patterns and prohibited patterns, but secure SDLC governance must avoid turning standards into outdated copy-paste recipes.

Rule:

```text
A secure coding standard should prevent recurring bug classes, not merely document them after they recur.
```

## 10. Peer Review and Security Code Review

Peer review checks whether code meets engineering standards. Security code review checks whether code preserves security properties.

They overlap but are not identical.

Peer review focuses on:

- Correctness.
- Maintainability.
- Test coverage.
- Readability.
- Architecture consistency.
- Performance.
- Operational behavior.

Security code review focuses on:

- Trust boundaries.
- Authorization decisions.
- Sensitive data exposure.
- Input handling.
- Unsafe API usage.
- Secrets handling.
- Cryptography usage.
- Logging quality.
- Error handling.
- Dependency changes.
- Privileged functionality.
- Administrative workflows.

High-risk changes require stronger review:

- Authentication or session logic.
- Authorization logic.
- Payment or financial flows.
- Tenant isolation.
- User provisioning or privilege management.
- Crypto/key handling.
- File upload or parsing.
- Deserialization.
- CI/CD pipeline definitions.
- Deployment/IaC changes.
- Secrets or identity configuration.
- Administrative functions.

Security code review outputs:

- Reviewed commit or merge request.
- Reviewer identity.
- Risk notes.
- Required changes.
- Linked findings.
- Test evidence.
- Approval record.

Anti-patterns:

- Rubber-stamp approvals.
- Same author approving own high-risk change.
- Reviewing generated diff without understanding security context.
- Treating scanner clean result as equivalent to review.
- Ignoring dependency and pipeline changes.

Rule:

```text
Automation finds known patterns.
Reviewers reason about intent, trust, context, and business logic.
```

## 11. SAST: Purpose, Strengths, Weaknesses, and Placement

**Static Application Security Testing (SAST)** analyzes code or compiled artifacts without requiring the application to run.

SAST is best for identifying unsafe coding patterns early.

Typical SAST strengths:

- Runs early in development.
- Gives file/line-level feedback.
- Fits pull request and CI workflows.
- Finds known dangerous patterns.
- Helps enforce secure coding standards.
- Can detect injection-prone code paths, insecure APIs, unsafe crypto calls, hardcoded secrets depending on capability, and dangerous deserialization patterns.

Typical SAST weaknesses:

- Limited business-logic understanding.
- May produce false positives.
- May miss runtime configuration issues.
- May miss authorization flaws that require domain context.
- May struggle with dynamic languages, frameworks, reflection, generated code, or complex data flow.
- May not prove exploitability.

Best lifecycle placement:

```text
Developer IDE / local pre-check -> pull request -> CI gate -> periodic full scan -> rule tuning feedback
```

SAST gating model:

| Context | Gate Behavior |
|---|---|
| New critical/high finding in changed code | Usually block unless accepted with expiry |
| Existing backlog finding | Track separately; do not silently normalize |
| Low-confidence noisy finding | Triage and tune rules; avoid alert fatigue |
| Security-critical module | Higher threshold and manual review required |
| Generated/test code | Separate rules or exclusions with justification |

SAST evidence should include:

- Tool and rule version.
- Code revision scanned.
- Findings and severity.
- Triage decision.
- Owner.
- Suppression justification if any.
- Fix commit.
- Rescan proof.

SAST is not a replacement for threat modeling, authorization testing, DAST, SCA, or manual review.

## 12. DAST: Purpose, Strengths, Weaknesses, and Placement

**Dynamic Application Security Testing (DAST)** tests a running application from the outside by sending requests and observing responses.

DAST is best for validating runtime exposure and externally observable behavior.

Typical DAST strengths:

- Tests deployed/running behavior.
- Finds configuration and runtime issues.
- Does not require source code access.
- Can validate some injection, XSS, header, TLS, authentication, access, and misconfiguration risks depending on coverage.
- Helps prove that vulnerabilities are reachable in an environment.

Typical DAST weaknesses:

- Coverage depends on crawling, API definitions, authentication configuration, and test data.
- May miss hidden routes, stateful workflows, and business logic.
- Can create false negatives if unauthenticated only.
- Can disrupt systems if run aggressively.
- May require test environment isolation.
- Can produce noise without context.

Best lifecycle placement:

```text
Test/staging environment -> authenticated scan -> API-definition-driven scan -> pre-release gate -> scheduled production-safe checks
```

DAST configuration requirements:

- Known target scope.
- Authentication method.
- Test user accounts and roles.
- Safe test data.
- Excluded destructive operations.
- Rate and intensity limits.
- Scan profile aligned to application technology.
- API definitions where applicable.
- Baseline expected findings.

DAST evidence should include:

- Environment scanned.
- Build/release version.
- Authentication context.
- Scope.
- Scan profile.
- Findings.
- False-positive decisions.
- Fix validation.
- Residual risk.

DAST is strongest when combined with:

- Threat model.
- ASVS-style requirements.
- Authenticated role-based testing.
- API contract definitions.
- Manual business-logic testing.
- Regression tests for previously found defects.

## 13. SCA: Purpose, Strengths, Weaknesses, and Placement

**Software Composition Analysis (SCA)** identifies third-party components and dependency risk.

SCA is not only “find CVEs.” Mature SCA supports dependency governance.

SCA should reason about:

- Component name.
- Version.
- Package source.
- Direct vs transitive dependency.
- Known vulnerabilities.
- License obligations.
- End-of-life or abandoned packages.
- Package reputation and maintainer risk where available.
- Vulnerable function reachability where supported.
- Upgrade path.
- Policy compliance.
- SBOM generation.

SCA strengths:

- Exposes inherited risk.
- Scales across repositories.
- Supports SBOM creation.
- Helps respond to newly disclosed vulnerabilities.
- Supports dependency policy enforcement.
- Helps identify outdated, vulnerable, or unapproved components.

SCA weaknesses:

- CVE matching can be noisy or incomplete.
- Package naming and version ranges can be ambiguous.
- Vulnerability presence does not always mean vulnerable execution path.
- Reachability analysis may be limited.
- Private packages may lack metadata.
- Transitive dependency upgrades can break applications.
- License findings may require legal review, not security-only handling.

Best lifecycle placement:

```text
Dependency proposal -> pull request -> CI gate -> SBOM generation -> release record -> continuous monitoring after release
```

SCA gating model:

| Finding Type | Gate Direction |
|---|---|
| Critical exploitable dependency in reachable runtime path | Block release or require exception with compensating control |
| Critical dependency in build-only tooling | Risk-review based on build-system exposure |
| License policy violation | Route to legal/compliance owner |
| Unsupported/end-of-life dependency | Require migration plan and timeline |
| New unapproved package source | Block until approved |
| Transitive vulnerability with no fix | Mitigate, monitor, or accept with expiry |

SCA evidence should include:

- Manifest/lockfile scanned.
- Dependency tree.
- Direct/transitive classification.
- Finding source.
- Severity and exploitability context.
- Reachability/context if available.
- Decision.
- Fix or exception.
- SBOM output.

## 14. IaC Scanning at Lifecycle Level

**Infrastructure-as-Code (IaC) scanning** checks infrastructure definitions before deployment.

IaC risk matters because cloud, container, and platform configuration often define the real security boundary.

IaC scanning should evaluate:

- Public exposure.
- Network ingress and egress.
- Security groups and firewall-like rules.
- IAM policies and role trust.
- Storage bucket exposure.
- Encryption settings.
- Logging settings.
- Key-management settings.
- Database exposure.
- Kubernetes security context and network policy where applicable.
- Secrets embedded in configuration.
- Drift from approved baselines.

Lifecycle placement:

```text
IaC authoring -> pull request -> policy scan -> plan review -> approval -> deployment -> drift detection
```

Strengths:

- Prevents insecure infrastructure before it exists.
- Produces reviewable diffs.
- Supports policy-as-code.
- Helps prove infrastructure controls were intended.

Weaknesses:

- May not see runtime drift.
- May miss provider defaults or generated resources.
- May lack context about business purpose.
- May detect configuration without validating effective reachability.

IaC gate examples:

- Block public storage without approved exception.
- Block wildcard administrative IAM permissions in production.
- Block unencrypted sensitive data stores.
- Block disabled logging on critical resources.
- Require review for changes to network boundaries.

This file owns IaC scanning only at lifecycle level. Cloud security architecture and product-specific controls belong to CKV-050 and CKV-051.

## 15. Container and Image Scanning at Lifecycle Level

Container and image scanning evaluates images, base layers, packages, configuration, and sometimes runtime metadata before deployment.

Container image risk commonly comes from:

- Vulnerable base images.
- Excessive packages.
- Running as root.
- Embedded secrets.
- Unpinned tags.
- Untrusted registries.
- Mutable tags.
- Missing provenance.
- Insecure image build process.
- Excessive container capabilities.
- Unsafe entrypoints.

Lifecycle placement:

```text
Base image selection -> Dockerfile/containerfile review -> build scan -> registry scan -> admission/deployment gate -> runtime monitoring
```

Strong image governance requires:

- Approved base images.
- Minimal images.
- Pinned versions or digests where appropriate.
- Rebuild cadence.
- Image signing or attestation where applicable.
- Registry access control.
- Separation between build and runtime secrets.
- Vulnerability and policy scan evidence.

Common gate rules:

- Block critical reachable runtime vulnerabilities unless approved.
- Block images from untrusted registries.
- Block embedded secrets.
- Block privileged/root defaults where policy forbids them.
- Require signed artifacts for production deployment.

Image scanning is not enough by itself. Runtime controls, orchestration hardening, network policy, admission controls, and cloud/container architecture belong to later CKV topics.

## 16. Secrets Scanning at Lifecycle Level

Secrets scanning detects credentials, tokens, keys, passwords, certificates, private keys, and sensitive connection material in code, history, artifacts, logs, containers, and configuration.

Secrets are high-risk because exposure often grants direct access without exploiting a vulnerability.

Secrets scanning should cover:

- Pre-commit or local developer checks.
- Pull request scanning.
- Repository history scanning.
- CI/CD logs.
- Build artifacts.
- Container images.
- IaC files.
- Configuration files.
- Documentation and examples.

Strong secrets control requires:

- No hardcoded secrets.
- Central secret management.
- Short-lived credentials where possible.
- Scoped credentials.
- Rotation on exposure.
- Automatic revocation workflow.
- Masked pipeline logs.
- Separation between build-time and runtime secrets.
- Least privilege for pipeline identities.

Secrets scanning gate behavior:

```text
Detected valid secret in code or artifact -> block -> revoke/rotate -> remove from history where required -> investigate usage -> prove closure
```

False positives must be tuned carefully, but leaked secrets cannot be treated as ordinary low-priority findings.

Evidence should include:

- Secret type.
- Location.
- Validity status if safely verified.
- Revocation/rotation proof.
- Affected systems.
- Log review for misuse.
- Prevention update.

## 17. SBOM Purpose and Lifecycle Use

A **Software Bill of Materials (SBOM)** is a structured inventory of software components and relationships used to understand what is inside a software product.

SBOM purpose:

```text
Know what was built.
Know what was shipped.
Know what dependencies exist.
Know what risk appears when a dependency becomes vulnerable.
```

SBOM is not a vulnerability scanner by itself. It is an inventory and transparency artifact that enables vulnerability response, procurement review, license review, supply-chain risk management, and incident scoping.

SBOM lifecycle uses:

| Lifecycle Stage | SBOM Use |
|---|---|
| Development | Understand dependencies and transitive components |
| Build | Generate SBOM tied to artifact and commit |
| Release | Store SBOM with release evidence |
| Operations | Match new vulnerability disclosures to deployed software |
| Procurement | Request and evaluate supplier component transparency |
| Incident response | Determine whether affected components are present |
| Retirement | Confirm obsolete components are removed |

Strong SBOM practice requires:

- Generated from reliable build inputs.
- Tied to artifact identity.
- Versioned and stored.
- Machine-readable.
- Available to vulnerability response.
- Protected against tampering.
- Updated when software changes.

SBOM limitations:

- May be incomplete.
- May not prove runtime reachability.
- May not include dynamically loaded components.
- May not include proprietary or vendor-obscured components.
- May not include deployment configuration risk.
- May be stale if not tied to release process.

SBOM must integrate with SCA, vulnerability management, artifact governance, procurement, and incident response.

## 18. CI/CD Pipeline Security at Vendor-Neutral Level

CI/CD pipelines are production control planes. If an attacker can change the pipeline, steal pipeline secrets, replace artifacts, or bypass gates, they may compromise production through the approved delivery path.

Pipeline security assets:

- Source repositories.
- Branch protection rules.
- Pull request workflows.
- CI/CD definitions.
- Runners/build agents.
- Build scripts.
- Secrets and tokens.
- Artifact repositories.
- Container registries.
- Signing keys.
- Deployment credentials.
- Approval records.
- Logs and attestations.

Pipeline hardening principles:

- Least privilege for users and service accounts.
- Separate build, test, release, and production permissions.
- Require review for pipeline definition changes.
- Protect main/release branches.
- Use short-lived credentials where possible.
- Prevent untrusted code from accessing production secrets.
- Avoid shared global pipeline tokens.
- Harden runners.
- Restrict runner network access for high-risk builds.
- Log all build and release actions.
- Store immutable artifacts.
- Enforce signed or attested releases where required.
- Monitor gate bypasses and failed checks.

Pipeline risk examples:

| Risk | Security Meaning |
|---|---|
| Unprotected release branch | Unauthorized change can become production code |
| Mutable artifact | Built artifact can be swapped after approval |
| Overprivileged runner | Build job can access unrelated systems or secrets |
| Static cloud key in CI | Repository compromise can become cloud compromise |
| Pipeline change without review | Tests and security gates can be disabled silently |
| Shared deployment credential | Weak accountability and broad blast radius |

Security principle:

```text
Treat the pipeline as a Tier 0 software delivery control plane.
```

## 19. Build Artifact Integrity and Provenance

Build artifact integrity answers whether the thing deployed is the thing that was reviewed, built, tested, approved, and intended.

Artifact integrity controls:

- Reproducible or controlled builds where possible.
- Artifact hashing.
- Artifact signing.
- Immutable artifact storage.
- Build logs.
- Build provenance/attestation.
- Release approval record.
- Deployment traceability.
- Registry access control.
- Promotion from trusted repositories only.

Provenance should connect:

```text
source repository + commit + branch/tag + builder identity + build process + dependencies + artifact digest + timestamp + signer + approval + deployment target
```

Artifact integrity matters because a clean code review does not protect against:

- Compromised build script.
- Malicious dependency substitution.
- Runner compromise.
- Artifact repository tampering.
- Registry tag overwrite.
- Deployment from unapproved artifact.
- Signing key misuse.

Release confidence requires proof:

```text
I know what source was built.
I know who approved it.
I know which pipeline built it.
I know which artifact was produced.
I know that artifact was not replaced.
I know what was deployed.
```

Build integrity is the bridge between application security and software supply-chain security.

## 20. Security Gates and Release Criteria

A **security gate** is an enforceable decision point.

A gate should answer:

```text
Can this change proceed to the next stage with acceptable security risk?
```

Gate inputs:

- Requirements completion.
- Threat model status.
- Code review status.
- SAST results.
- SCA results.
- Secrets scan results.
- IaC scan results.
- Container scan results.
- DAST results.
- Manual review findings.
- Open vulnerability backlog.
- Exception records.
- Release criticality.
- Asset/data classification.
- Compensating controls.
- Business risk decision.

Common gate types:

| Gate | Placement | Purpose |
|---|---|---|
| Design gate | Before implementation | Prevent unsafe architecture |
| Pull request gate | Before merge | Prevent unsafe code and dependency changes |
| Build gate | During CI | Stop failing tests/scans |
| Artifact gate | Before publish | Ensure artifact integrity and SBOM/provenance |
| Release gate | Before production | Decide residual risk |
| Deployment gate | Before apply/deploy | Validate infrastructure and environment controls |
| Operational gate | After deployment | Confirm monitoring, rollback, and vulnerability intake |

Release criteria should define:

- Which severity levels block release.
- Whether only new findings block or all findings block.
- How exploitability and exposure modify severity.
- Which controls require manual review.
- Which exceptions are allowed.
- Who can approve exceptions.
- What evidence is required to close a finding.
- When re-scan or regression tests are required.

Weak gate:

```text
No high vulnerabilities.
```

Stronger gate:

```text
No unresolved critical/high findings in changed code or production-reachable runtime dependencies unless documented exception exists with owner, expiry, compensating control, monitoring, business approval, and remediation date.
```

## 21. Risk-Based Gating vs Absolute Gating

**Absolute gating** blocks based on fixed rules.

Examples:

- Any detected secret blocks merge.
- Unsigned production artifact blocks deployment.
- Public storage without approved exception blocks deployment.
- Critical vulnerability in internet-facing production service blocks release.

**Risk-based gating** evaluates context before deciding.

Risk factors:

- Asset criticality.
- Data sensitivity.
- Internet exposure.
- Authentication requirement.
- Exploitability.
- Runtime reachability.
- Existing compensating controls.
- Business deadline.
- Regulatory impact.
- Active exploitation.
- Blast radius.
- Recovery capability.

Both are required.

```text
Absolute gates enforce non-negotiable safety rules.
Risk-based gates handle context-sensitive decisions.
```

Absolute gates are best for:

- Secret leakage.
- Unsigned production releases where signing is mandatory.
- Missing required approval.
- Critical policy violations.
- Known malicious packages.
- Disabled mandatory logging.

Risk-based gates are best for:

- Dependency vulnerabilities with unclear reachability.
- DAST findings requiring context.
- Low-confidence SAST findings.
- Security debt migration.
- Legacy application constraints.
- Temporary business exceptions.

A mature program avoids two extremes:

- Blocking everything and forcing teams to bypass security.
- Allowing everything and calling it “risk-based.”

Risk-based gating must be documented, time-bounded, and measurable.

## 22. False Positives, False Negatives, Tuning, and Ownership

Security tools produce signals, not truth.

Definitions:

| Term | Meaning |
|---|---|
| False positive | Tool reports a problem that is not actually a security issue in context |
| False negative | Tool fails to report a real issue |
| True positive | Tool reports a real issue |
| True negative | Tool correctly reports no issue |
| Noise | Findings with low value, poor confidence, duplicates, or missing context |

False positives create alert fatigue. False negatives create false confidence.

Tuning goals:

- Reduce noise without hiding real risk.
- Preserve high-confidence blocking rules.
- Separate experimental rules from release gates.
- Track suppressions and justifications.
- Review suppressions periodically.
- Convert recurring true positives into standards and tests.

Ownership rules:

- Each finding needs a technical owner.
- Each risk acceptance needs a business owner.
- Each scanner rule set needs a maintainer.
- Each suppression needs justification and expiry where applicable.
- Each gate needs an escalation path.

Suppression anti-patterns:

- Permanent “won’t fix” without risk owner.
- Suppressing entire directories to reduce noise.
- Excluding generated code without proving generated code cannot affect production.
- Disabling critical rules because they block a release.
- Treating tool limits as proof of safety.

Good tuning evidence:

- Rule configuration change.
- Reason for change.
- Expected effect.
- Approval.
- Test repository or known-bad cases proving the rule still catches important defects.

## 23. Developer Feedback Loops

DevSecOps succeeds when developers receive actionable feedback early enough to fix issues without disrupting delivery.

Good feedback is:

- Specific.
- Timely.
- Reproducible.
- Prioritized.
- Mapped to code or design location.
- Linked to safe patterns.
- Routed to the correct owner.
- Integrated into normal workflow.

Bad feedback is:

- Late.
- Generic.
- Tool-centric.
- Unprioritized.
- Duplicated.
- Without remediation guidance.
- Without context.
- Delivered outside the development workflow.

Developer feedback loop:

```text
Finding -> explanation -> fix guidance -> code change -> validation -> regression test -> standard update if recurring
```

Feedback loops should convert incidents and findings into prevention:

| Event | Feedback Output |
|---|---|
| Repeated injection bug | New validation library, coding standard update, SAST rule, unit tests |
| Repeated authorization bug | Authorization pattern review, framework helper, test template |
| Repeated secrets leak | Pre-commit scan, training, secret manager adoption |
| Dependency emergency | Dependency review policy, SBOM response process, update cadence |
| Pipeline bypass | Approval rule update, monitoring alert, branch protection change |

DevSecOps maturity is visible when the same bug class becomes harder to reintroduce after every finding.

## 24. Vulnerability Intake from Pipelines

Pipeline findings are vulnerability intake events. They must be triaged and managed, but this file does not own the full vulnerability-management lifecycle.

Pipeline intake must answer:

- What found it?
- Which repository, branch, commit, build, artifact, image, or environment is affected?
- Is the finding new or pre-existing?
- Is it in production-reachable code?
- Is it exploitable or reachable?
- Who owns the component?
- Is release blocked?
- Is there an exception?
- What validates closure?

Pipeline intake states:

```text
new -> triaged -> assigned -> fixed/mitigated/accepted -> verified -> closed
```

Required metadata:

- Finding ID.
- Tool source.
- Code/artifact reference.
- Severity.
- Confidence.
- Affected asset/product.
- Owner.
- Decision.
- SLA or due date.
- Exception ID if accepted.
- Verification evidence.

Pipeline findings should be deduplicated across tools. The same root cause may appear in SAST, DAST, and manual review. Closure should focus on root cause, not merely closing duplicate tickets.

Important distinction:

```text
Pipeline intake = receive and triage software findings from delivery flow.
Vulnerability management = broader enterprise lifecycle across assets, scanners, exposure, prioritization, remediation, and reporting.
```

CKV-082 owns the full vulnerability-management program.

## 25. Exception Handling in Secure SDLC

An exception allows software to proceed despite an unresolved security requirement, finding, or control gap.

Exceptions are sometimes necessary, but unmanaged exceptions become permanent security debt.

A valid Secure SDLC exception requires:

- Clear description of the unresolved issue.
- Affected application, component, version, asset, or release.
- Risk rating.
- Business justification.
- Compensating controls.
- Monitoring requirements.
- Owner.
- Approver with decision authority.
- Expiry date.
- Remediation plan.
- Review cadence.
- Closure evidence.

Exception types:

| Exception Type | Example |
|---|---|
| Release exception | Ship with known finding due to business deadline |
| Dependency exception | Temporarily keep vulnerable dependency with mitigation |
| Control exception | Missing control due to platform limitation |
| Test exception | Scan cannot run due to technical constraint |
| Architecture exception | Legacy design cannot meet new requirement immediately |
| Pipeline exception | Temporary bypass of gate under controlled approval |

Exception anti-patterns:

- No expiry date.
- No owner.
- No compensating control.
- Approved by the same person who benefits from release.
- Hidden in chat or email only.
- Reused across unrelated releases.
- Not reviewed after deployment.
- Not visible to risk reporting.

Relationship to change management:

```text
Change management governs release/change approval.
Secure SDLC exceptions govern unresolved software-security risk inside that change.
```

CKV-005 owns the full change-management and exception-governance model.

## 26. Metrics, Evidence, and Auditability

DevSecOps must produce evidence. Evidence proves that controls were performed and decisions were authorized.

Evidence categories:

- Requirements records.
- Threat model records.
- Design review decisions.
- Pull request reviews.
- SAST results.
- DAST results.
- SCA results.
- Secrets scan results.
- IaC scan results.
- Container scan results.
- SBOMs.
- Build logs.
- Artifact signatures and hashes.
- Provenance/attestation records.
- Gate pass/fail records.
- Release approvals.
- Exception records.
- Deployment records.
- Post-deployment validation.
- Monitoring configuration.
- Vulnerability intake tickets.
- Closure validation.

Metrics should include both leading and lagging indicators.

Leading indicators:

- Percentage of repositories covered by required scans.
- Percentage of releases with threat model/design review where required.
- Percentage of production artifacts with SBOM.
- Percentage of critical dependencies with owners.
- Percentage of privileged pipeline changes reviewed.
- Gate bypass count.
- Active exceptions nearing expiry.

Lagging indicators:

- Production vulnerabilities by severity.
- Repeat finding rate.
- Time to triage.
- Time to remediate.
- Reopened findings.
- Incidents caused by preventable bug classes.
- Dependency emergency response time.

Bad metrics:

- Raw number of findings without context.
- Number of scans run as a success metric.
- High finding count used to punish teams.
- Low finding count treated as proof of security.
- Closure count without validation quality.

Good metrics drive action:

```text
Metric -> threshold -> owner -> escalation -> improvement action -> evidence
```

Auditability means a reviewer can reconstruct:

```text
what changed -> who reviewed -> what tests ran -> what risks remained -> who accepted -> what shipped -> what evidence proves it
```

## 27. Shift-Left and Shift-Right Relationship

**Shift-left** means moving security earlier in the lifecycle.

Examples:

- Security requirements during planning.
- Threat modeling during design.
- SAST and secrets scanning during development.
- SCA before dependency adoption.
- IaC scanning before deployment.
- Secure defaults in templates.

**Shift-right** means validating and improving security after deployment.

Examples:

- Runtime monitoring.
- Production-safe DAST or exposure checks.
- Attack-surface monitoring.
- Vulnerability disclosure intake.
- Incident lessons learned.
- Real-world abuse telemetry.
- Chaos/security resilience exercises where appropriate.

Correct relationship:

```text
Shift-left prevents known and foreseeable failure classes.
Shift-right validates real behavior and feeds lessons back into design and gates.
```

Bad interpretation:

```text
Shift-left = developers own all security and security team disappears.
```

Correct interpretation:

```text
Shift-left = developers get earlier security guidance and automation.
Security engineering still owns standards, high-risk review, validation, and governance.
```

Shift-left without shift-right creates blind confidence. Shift-right without shift-left creates expensive reactive cleanup.

Mature DevSecOps connects both:

```text
Runtime incident -> root cause -> new requirement -> new test -> new gate -> new metric -> reduced recurrence
```

## 28. DevSecOps Maturity Model at Practical Level

DevSecOps maturity measures how reliably security is integrated into software delivery.

Practical maturity levels:

| Level | Name | Characteristics |
|---|---|---|
| 0 | Ad hoc | Security happens late, manually, inconsistently, with little evidence |
| 1 | Baseline | Basic standards, some scanning, some review, partial ownership |
| 2 | Integrated | Security work embedded into SDLC stages and CI/CD workflows |
| 3 | Risk-based | Gates use asset, data, exposure, exploitability, and business context |
| 4 | Optimized | Evidence, metrics, automation, developer feedback, and continuous improvement are mature |

Maturity dimension map:

| Dimension | Low Maturity | High Maturity |
|---|---|---|
| Requirements | Generic security statements | Testable security requirements tied to risk |
| Threat modeling | Rare or after incidents | Trigger-based and design-stage integrated |
| Scanning | Tool run occasionally | Automated, tuned, evidence-producing, risk-based |
| SCA | CVE list only | Dependency governance, SBOM, provenance, response workflow |
| Pipeline security | Shared secrets and broad access | Least privilege, protected pipelines, attestation, logging |
| Gates | Informal approvals | Policy-driven gates with exceptions and evidence |
| Metrics | Raw findings | Actionable indicators tied to improvement |
| Feedback | Late tickets | Developer-centered guidance and regression prevention |
| Operations | Separate from development | Runtime findings feed requirements and gates |

Maturity does not mean every team has identical gates. It means every team has appropriate gates for risk.

Practical maturity target:

```text
High-risk systems get stronger controls.
Low-risk systems get baseline controls.
All systems get ownership, inventory, evidence, and vulnerability intake.
```

## 29. Common Mistakes

1. **Treating DevSecOps as scanner installation.**
   Scanners are inputs. DevSecOps requires policy, gates, owners, exceptions, evidence, and feedback.

2. **Running tools without release criteria.**
   Findings without a decision model create noise, not security.

3. **Blocking everything.**
   Overly rigid gates cause bypasses and shadow releases.

4. **Allowing everything under “risk-based.”**
   Risk-based gating without evidence and owners is unmanaged acceptance.

5. **Ignoring pipeline security.**
   A secure application can be compromised by an insecure build or deployment path.

6. **Treating dependencies as developer convenience only.**
   Dependencies are inherited code and inherited risk.

7. **Generating SBOMs that nobody uses.**
   SBOMs must feed vulnerability response, procurement, incident scoping, and release evidence.

8. **Using unauthenticated DAST only.**
   Many serious application flaws require authenticated roles and stateful workflows.

9. **Trusting SAST to find business logic flaws.**
   Authorization, tenant isolation, and workflow abuse often require design review and targeted tests.

10. **Suppressing findings permanently.**
    Suppressions require justification, ownership, and review.

11. **Failing to scan pipeline definitions and IaC.**
    The deployment path and infrastructure code can create production exposure even when application code is clean.

12. **Letting emergency releases bypass evidence.**
    Emergency does not mean invisible. It means faster approval, stronger tracking, and post-release validation.

13. **Missing developer feedback loops.**
    Findings that do not improve standards, tests, or training are likely to recur.

14. **Confusing vulnerability management with DevSecOps.**
    DevSecOps creates pipeline-originated software findings. Vulnerability management owns enterprise-wide prioritization and remediation lifecycle.

15. **Using one gate for every application.**
    Risk differs by data sensitivity, exposure, regulatory impact, and business criticality.

## 30. Must-Memorize Facts

1. DevSecOps integrates security into software delivery; it is not only CI scanning.
2. Secure SDLC embeds security requirements, threat modeling, secure design, secure coding, testing, release gates, and operational feedback.
3. SAST analyzes code without running the application.
4. DAST tests a running application from the outside.
5. SCA identifies third-party components, dependency vulnerabilities, license issues, and component inventory.
6. Secrets scanning must block exposed valid secrets and trigger rotation/revocation.
7. IaC scanning checks infrastructure definitions before deployment.
8. Container/image scanning checks base images, packages, configuration, embedded secrets, and policy compliance.
9. SBOMs are component inventories; they are not vulnerability scanners by themselves.
10. CI/CD pipelines are high-value control planes and must be hardened like production systems.
11. Build provenance ties artifacts to source, builder, process, dependencies, and output digest.
12. Security gates must have policy, thresholds, ownership, exceptions, and evidence.
13. Risk-based gating does not mean bypassing security; it means decision-making based on context and documented residual risk.
14. False positives require tuning; false negatives require humility and layered validation.
15. Developer feedback loops convert findings into safer patterns, tests, and standards.
16. Exceptions must have owner, approval, compensating controls, expiry, and closure evidence.
17. Shift-left prevents defects earlier; shift-right validates production reality and feeds improvements back into SDLC.
18. Metrics must drive action, not vanity reporting.
19. Secure code review is still needed where scanners lack business context.
20. Release evidence should reconstruct what changed, what ran, what passed, what failed, who approved, and what shipped.

## 31. Interview / Exam Points

1. **Define DevSecOps.**

Strong answer:

```text
DevSecOps embeds security controls, automation, ownership, and evidence into DevOps workflows so software can be delivered quickly without bypassing security requirements.
```

2. **Define Secure SDLC.**

Strong answer:

```text
Secure SDLC integrates security activities into every software lifecycle stage: requirements, design, development, testing, release, deployment, operations, and retirement.
```

3. **Explain SAST vs DAST.**

Strong answer:

```text
SAST inspects code without running the application and is useful early in development. DAST tests a running application and validates externally observable runtime behavior.
```

4. **Explain SCA.**

Strong answer:

```text
SCA identifies third-party components, versions, vulnerabilities, licenses, and dependency relationships so inherited software risk can be governed.
```

5. **Why is threat modeling needed if scanners exist?**

Strong answer:

```text
Scanners detect known technical patterns. Threat modeling reasons about design, trust boundaries, business logic, authorization, and abuse cases that scanners often miss.
```

6. **What makes a security gate valid?**

Strong answer:

```text
A valid gate has explicit policy, severity/risk thresholds, ownership, enforcement behavior, exception path, validation requirement, and evidence.
```

7. **When should a finding block release?**

Strong answer:

```text
When the risk exceeds defined release criteria, such as critical exploitable risk in exposed code, leaked valid secrets, missing mandatory approval, or violation of non-negotiable control policy.
```

8. **What is the difference between risk-based and absolute gates?**

Strong answer:

```text
Absolute gates block fixed non-negotiable failures. Risk-based gates evaluate context such as exposure, data sensitivity, exploitability, compensating controls, and business impact.
```

9. **Why is CI/CD security important?**

Strong answer:

```text
A compromised pipeline can ship trusted malicious artifacts to production. Repositories, runners, secrets, artifacts, and deployment credentials are part of the production control plane.
```

10. **What is SBOM used for?**

Strong answer:

```text
An SBOM records software components and relationships so teams can respond to dependency vulnerabilities, support procurement review, and understand what was shipped.
```

11. **Why is DAST sometimes weak for APIs?**

Strong answer:

```text
API DAST requires endpoint definitions, authentication, roles, parameters, and business context. Without those, scanners miss hidden or stateful API behavior.
```

12. **How should false positives be handled?**

Strong answer:

```text
Triage them, tune rules, document suppressions, review suppressions periodically, and avoid disabling high-value checks without proof.
```

13. **What is secure release evidence?**

Strong answer:

```text
Evidence linking source, review, tests, scans, SBOM, artifact, approval, exceptions, deployment, and validation.
```

14. **Why are exceptions dangerous?**

Strong answer:

```text
Exceptions become permanent security debt if they lack owner, expiry, compensating controls, monitoring, and closure evidence.
```

15. **What does shift-left mean?**

Strong answer:

```text
Shift-left means moving security earlier into requirements, design, development, and CI so defects are prevented or found before release.
```

16. **What does shift-right mean?**

Strong answer:

```text
Shift-right means validating security in production and operations through monitoring, feedback, vulnerability response, and runtime evidence.
```

## 32. Expert-Level Insights

1. **The pipeline is part of the product.**
   A product is not only source code. It includes dependency resolution, build logic, runners, secrets, artifact storage, release approval, and deployment path.

2. **Security gates are governance encoded into engineering.**
   A gate turns abstract policy into repeatable operational decision-making.

3. **Automation should reduce judgment load, not eliminate judgment.**
   Tools can classify patterns. Humans must still reason about business logic, architecture, abuse, and risk acceptance.

4. **Security debt must be visible debt.**
   Accepted findings must have owners, expiry, compensating controls, and reporting. Hidden debt becomes incident material.

5. **A scanner clean result is not proof of secure design.**
   A system can pass scans and still have broken authorization, unsafe trust relationships, business-flow abuse, or supply-chain exposure.

6. **The best gates are close to the decision they protect.**
   Secrets scanning should run before merge. Artifact integrity checks should run before publish/deploy. DAST should run against realistic runtime environments.

7. **Supply-chain security is inseparable from AppSec.**
   Dependencies, registries, build systems, and artifacts are part of application security because they become part of the application.

8. **SBOM without response workflow is inventory without action.**
   SBOM value appears when a new vulnerability is disclosed and the organization can rapidly identify affected products and versions.

9. **Developer experience is a security control.**
   If secure behavior is slower, unclear, or painful, teams will bypass it. Good DevSecOps makes secure behavior the easiest path.

10. **Risk-based gating requires better data than absolute gating.**
    To make contextual decisions, the program must know asset criticality, exposure, data sensitivity, reachability, compensating controls, and business impact.

11. **False negatives are more dangerous than false positives.**
    False positives cause fatigue; false negatives create unjustified confidence. Layered validation reduces both risks.

12. **Security review should follow change risk.**
    Authentication, authorization, crypto, pipeline, IaC, dependency, and deployment changes deserve more scrutiny than cosmetic UI changes.

13. **High maturity means faster safe delivery, not slower delivery.**
    Mature programs automate low-risk decisions and reserve human attention for high-risk decisions.

14. **Every incident should harden the lifecycle.**
    If a production incident does not create better requirements, tests, gates, or monitoring, the same failure class remains likely.

15. **Release integrity is a chain, not a checkbox.**
    Source control, review, build, artifact, signature, approval, deployment, and runtime validation must connect.

## 33. Internal References to Future CKV Files

This file owns DevSecOps, Secure SDLC, SAST, DAST, SCA, CI/CD security gates, SBOM lifecycle use, and release-assurance workflow. The following CKV files own detailed expansion areas.

- **CKV-001 — Security Engineering Role and Operating Model**  
  Owns the security-engineering operating model, cross-team ownership, advisory role, assurance responsibilities, and the human workflow behind DevSecOps adoption.

- **CKV-002 — Security Principles and Secure-by-Design Thinking**  
  Owns defense-in-depth, secure defaults, least privilege, complete mediation, fail-safe defaults, trust boundaries, and design reasoning used by secure SDLC gates.

- **CKV-003 — Risk Management and Security Governance**  
  Owns risk rating, risk ownership, governance, risk acceptance, executive decision-making, and unresolved software-risk treatment.

- **CKV-004 — Asset Management and Attack Surface Inventory**  
  Owns application inventory, service ownership, exposure mapping, data ownership, critical application mapping, and asset-to-telemetry relationships needed for risk-based gates.

- **CKV-005 — Change Management and Security Exceptions**  
  Owns production change approval, emergency releases, rollback planning, security exceptions, compensating controls, expiry, and closure evidence beyond the Secure SDLC context.

- **CKV-006 — Business Continuity, Disaster Recovery, and Resilience**  
  Owns continuity, recovery objectives, backup/restore validation, ransomware recovery readiness, and resilience evidence for critical software-supported services.

- **CKV-017 — Network Design, Segmentation, DMZs, and Hard Controls**  
  Owns segmentation, zones, trust boundaries, choke points, management-plane isolation, and network hard-control design that may be required by software threat models.

- **CKV-025 — Windows Security Stack: Updates, Defender, Firewall, SmartScreen, BitLocker, TPM, VSS**  
  Owns Windows host security controls for development workstations, build servers, test systems, and Windows-hosted application workloads.

- **CKV-026 — Linux Fundamentals and Hardening for Security**  
  Owns Linux hardening, services, permissions, logs, SSH, host firewalls, and Linux server investigation context for build hosts, runners, and application workloads.

- **CKV-040 — HTTP, Web Fundamentals, Sessions, and Cookies**  
  Owns HTTP request/response behavior, web communication, methods, headers, sessions, cookies, caching, redirects, CORS, same-origin concepts, and TLS/web context used by web testing.

- **CKV-041 — OWASP Web Top 10 Canonical Security Model**  
  Owns OWASP Web Top 10 taxonomy, web risk categories, root-cause reasoning, and category-level control direction used as SDLC requirements and test categories.

- **CKV-042 — OWASP API Security Top 10 Canonical Model**  
  Owns OWASP API Security Top 10 taxonomy, API object/function/business-flow failure framing, and API-specific category-level design-review logic.

- **CKV-044 — API Security Controls: Authentication, Authorization, Schema, Rate Limits**  
  Owns reusable API control implementation patterns such as object authorization, function authorization, property authorization, schema validation, DTOs, OAuth/OIDC/JWT validation, gateways, rate limits, quotas, and API monitoring implementation.

- **CKV-050 — Cloud Fundamentals: IaaS, PaaS, SaaS, Compute, Storage, IAM**  
  Owns cloud service models, managed application hosting context, cloud IAM foundations, and cloud workload concepts that affect deployment pipelines.

- **CKV-051 — Cloud Security Architecture and Hard Controls**  
  Owns cloud segmentation, security groups, private endpoints, cloud guardrails, workload isolation, metadata service protections, cloud-native firewalling, and architecture controls for cloud-hosted software.

- **CKV-060 — Detection Engineering and Telemetry Design**  
  Owns detection methodology, telemetry mapping, alert logic, correlation, signal quality, coverage validation, and detection-use-case engineering for software and pipeline events.

- **CKV-061 — Incident Response Lifecycle and Playbook Design**  
  Owns triage, containment, eradication, recovery, escalation, communication, and playbooks for application, pipeline, dependency, and supply-chain incidents.

- **CKV-063 — Digital Forensics and Evidence Handling**  
  Owns evidence preservation, chain of custody, forensic acquisition, timeline analysis, and deeper evidence handling for application, build, artifact, and pipeline incidents.

- **CKV-064 — SOAR, Automation, Validation, and Provability Outputs**  
  Owns automation workflows, approval-gated response, validation outputs, evidence packages, provable remediation reporting, and automated control validation beyond the SDLC gate model.

- **CKV-080 — Malware, APT Lifecycle, Botnets, and Advanced Threat Controls**  
  Owns malware behavior, APT lifecycle, botnet behavior, command-and-control, persistence context, malicious uploads, web shells, build-system compromise, and supply-chain compromise relationships beyond SDLC governance.

- **CKV-081 — Firewalls, WAFs, IDS/IPS, and Network Security Controls**  
  Owns WAFs, firewalls, IDS/IPS, proxies, inspection depth, control tuning, and layered network/application enforcement that may compensate for or monitor application risk.

- **CKV-082 — Vulnerability Management, Scanning, Prioritization, and Remediation**  
  Owns enterprise vulnerability scanning, prioritization, remediation workflow, exposure validation, compensating controls, closure evidence, and broader vulnerability lifecycle management beyond pipeline-originated findings.

- **CKV-090 — Command-Line and Built-in Administration Tools for Security Work**  
  Owns approved administrative and validation tooling workflows without re-teaching DevSecOps lifecycle concepts or vendor-specific CI/CD syntax.

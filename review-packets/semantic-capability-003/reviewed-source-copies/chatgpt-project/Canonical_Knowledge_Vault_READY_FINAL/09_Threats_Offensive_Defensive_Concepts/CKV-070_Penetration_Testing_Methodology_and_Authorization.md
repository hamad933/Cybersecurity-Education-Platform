# CKV-070 — Penetration Testing Methodology and Authorization

## 1. Purpose

This file defines the **canonical model for professional, authorized penetration testing**. It explains how a penetration test is planned, authorized, scoped, executed safely, evidenced, reported, remediated, and retested without becoming an unauthorized attack, a destructive experiment, or a tool-driven checklist.

This file answers:

```text
What makes a penetration test lawful and professional?
What must be defined before testing starts?
How are scope, rules of engagement, safety constraints, and success criteria controlled?
How does a pentest differ from vulnerability assessment, red team, audit, and threat hunting?
How should recon, scanning, validation, proof of concept, evidence, severity, reporting, remediation, and retest be handled safely?
How does pentesting create defensive value for engineering, detection, incident response, vulnerability management, and risk governance?
```

Penetration testing is not “hacking with permission” in a loose sense. It is a controlled security assessment performed under explicit authorization to determine whether defined weaknesses can be validated, how they affect business risk, which controls failed, and what remediation evidence must prove closure.

Canonical purpose:

```text
Penetration testing = authorization
                    + scope
                    + rules of engagement
                    + safe methodology
                    + controlled validation
                    + evidence
                    + risk-rated findings
                    + remediation guidance
                    + retest proof
                    + defensive improvement
```

The output of a professional penetration test is not access for its own sake. The output is decision-quality evidence that helps the organization reduce real security risk.

## 2. Core Definition

**Penetration testing** is an authorized, scoped, controlled security assessment that uses adversary-informed techniques to validate whether weaknesses in systems, applications, networks, identities, cloud services, processes, or controls can produce meaningful security impact.

A penetration test must have:

- Written authorization.
- Defined scope.
- Rules of engagement.
- Named stakeholders.
- Communication and escalation paths.
- Safety constraints.
- Evidence-handling rules.
- Business-impact limits.
- Defined objectives.
- Defined success criteria.
- Reporting and retest expectations.

Canonical definition:

```text
Pentest finding = validated security weakness
                + affected scope object
                + reproducible evidence at safe level
                + business impact
                + root cause
                + likelihood/preconditions
                + severity/risk rating
                + remediation guidance
                + retest criteria
```

A penetration test is invalid when it lacks authorization, exceeds scope, causes avoidable harm, hides evidence from stakeholders, ignores business constraints, or reports dramatic outcomes without engineering-usable remediation.

## 3. Why Penetration Testing Matters

Penetration testing matters because many security controls look effective on paper but fail when connected to real systems, real users, real identities, real configurations, real trust boundaries, and real operational constraints.

A penetration test can reveal:

- Whether known vulnerabilities are exploitable in the actual environment.
- Whether access controls enforce the intended policy.
- Whether segmentation blocks realistic paths.
- Whether authentication and authorization are implemented correctly.
- Whether sensitive data is exposed through reachable paths.
- Whether cloud resources are reachable beyond intended boundaries.
- Whether AD or identity configuration creates privilege paths.
- Whether web/API controls fail under attacker-like validation.
- Whether monitoring sees important activity.
- Whether incident-response handoff works.
- Whether remediation priorities are correct.

Canonical security reality:

```text
A vulnerability scan says: this may be weak.
A penetration test says: under authorized conditions, this weakness can or cannot produce impact.
A report says: here is the evidence, root cause, business risk, fix path, and retest proof.
```

Penetration testing is valuable only when it closes the loop:

```text
test → evidence → finding → remediation → retest → validated risk reduction
```

If the output cannot be fixed, verified, or used for governance decisions, the test did not produce enough security value.

## 4. Penetration Testing Mental Model

Professional penetration testing is a **controlled validation loop**, not a random sequence of tools.

Canonical mental model:

```text
Authorization defines what may be tested.
Scope defines where testing may happen.
Rules of engagement define how testing may happen.
Objectives define why testing is happening.
Safety controls define what must not be harmed.
Evidence defines what was proven.
Findings define what must change.
Retesting defines whether risk was reduced.
```

A mature penetration test balances three forces:

| Force | Meaning | Failure mode when ignored |
|---|---|---|
| Security realism | Tests should resemble plausible abuse paths within scope | Pure checkbox testing misses real risk |
| Operational safety | Tests must avoid unnecessary disruption and harm | Testing becomes an incident |
| Engineering usefulness | Findings must be fixable and verifiable | Report becomes storytelling |

Penetration testing should be treated as an engineering assessment of security assumptions:

```text
Architecture assumption: only trusted admins can reach management services.
Pentest validation: verify reachability, authentication, authorization, logging, and segmentation evidence.
Finding if false: unauthorized path exists, with evidence and remediation.
```

The best penetration tests are not measured by how “advanced” they look. They are measured by how clearly they expose control failures and drive durable fixes.

## 5. Authorization-First Principle

The first rule of penetration testing is **authorization before action**.

Authorization must be explicit, written, current, and specific. Verbal approval, vague trust, public exposure, curiosity, employment status, or technical ability does not create permission.

Authorization must define:

- Who is allowed to test.
- Who owns the tested environment.
- What systems are in scope.
- What systems are out of scope.
- What techniques are permitted.
- What techniques are forbidden.
- When testing may occur.
- What data may be accessed.
- What evidence may be collected.
- What actions require additional approval.
- Who can pause or stop the test.
- Who receives notifications and reports.

Canonical rule:

```text
No written permission → no test.
Ambiguous permission → stop and clarify.
Changed scope → update authorization before continuing.
Unexpected sensitive impact → pause and escalate.
```

Authorization is not bureaucracy. It is the control that separates professional testing from unauthorized activity.

## 6. Legal Permission, Written Authorization, and Scope Control

Legal permission is the documented authority to perform testing on defined assets under defined constraints. It protects the organization, the tester, customers, third parties, and operations teams.

A strong authorization package includes:

- Statement of work or engagement agreement.
- Letter of authorization.
- Rules of engagement.
- Approved scope list.
- Explicit out-of-scope list.
- Dates and time windows.
- Named approving authority.
- Emergency contacts.
- Data-handling requirements.
- Third-party constraints.
- Cloud/SaaS acceptable-use constraints.
- Reporting and retention expectations.

Scope control prevents accidental expansion. Scope should include multiple dimensions:

| Scope dimension | Examples |
|---|---|
| Asset scope | IP ranges, domains, applications, APIs, cloud accounts, tenants, AD domains, wireless networks |
| Identity scope | Test accounts, role levels, MFA handling, service-account restrictions |
| Data scope | Synthetic data, masked production data, prohibited data classes, retention limits |
| Technique scope | Recon, scanning, web validation, phishing simulation, password testing, cloud testing, physical testing |
| Time scope | Test windows, blackout periods, maintenance windows |
| Location/source scope | Approved source IPs, VPN, jump hosts, test devices |
| Third-party scope | Hosting providers, SaaS platforms, vendors, customers, supply-chain dependencies |

Common scope-control failures:

- Testing a domain that points to a third-party provider without explicit third-party permission.
- Treating all discovered subdomains as automatically in scope.
- Testing production with lab-level assumptions.
- Continuing after discovering sensitive data not authorized for access.
- Running high-impact tests outside approved time windows.
- Assuming a cloud account includes every connected SaaS, tenant, or managed service.

Scope must be updated whenever reality diverges from the plan.

## 7. Rules of Engagement

**Rules of Engagement (RoE)** define how the penetration test will be conducted safely and professionally.

A complete RoE should define:

- Engagement objective.
- Scope and exclusions.
- Authorized tester identities.
- Approved source networks and tools at category level.
- Test windows and blackout periods.
- Rate limits and load limits.
- Authentication attempt limits.
- Data-handling rules.
- Evidence-handling rules.
- Communication schedule.
- Escalation path.
- Kill switch.
- Change-freeze coordination.
- Notification model.
- Restrictions on destructive actions.
- Restrictions on persistence and sensitive-data access.
- Restrictions for OT, safety-critical systems, medical systems, payment systems, or regulated environments.
- Retest expectations.

Canonical RoE safety clauses:

```text
No denial-of-service unless separately authorized.
No destructive modification unless separately authorized.
No persistence unless explicitly authorized and safely removed.
No credential dumping unless explicitly authorized and tightly controlled.
No access to regulated data unless explicitly authorized.
No third-party testing without explicit approval.
No production-impacting action without escalation.
Stop when safety threshold is reached.
```

The RoE is not optional. It is the operational contract for the test.

## 8. Testing Objectives and Success Criteria

Testing objectives define what the organization wants to learn. Success criteria define what evidence proves the objective was met.

Good objectives are specific:

- Validate whether the external attack surface exposes exploitable paths to sensitive systems.
- Validate whether segmentation prevents unauthorized access from user zones to server management interfaces.
- Validate whether API authorization enforces tenant isolation.
- Validate whether standard users can obtain administrative access through misconfiguration.
- Validate whether cloud storage exposure controls prevent public data access.
- Validate whether detection and response teams observe defined testing activity.

Weak objectives are vague:

- “Hack us.”
- “Find everything.”
- “Test security.”
- “Prove we are safe.”

Canonical success criteria:

```text
Objective: Validate whether external web exposure can lead to unauthorized sensitive data access.
Success evidence: confirmed access-control failure or confirmed controls resisted tested paths.
Deliverable: findings, affected assets, evidence, impact, root cause, fix, and retest plan.
```

Success does not always mean compromise. A test can be successful when it proves that controls worked and records the evidence.

## 9. Stakeholders, Roles, and Communication

Penetration testing involves more than testers. Stakeholder alignment prevents confusion, business disruption, and uncontrolled risk.

Core roles:

| Role | Responsibility |
|---|---|
| Executive sponsor | Authorizes the test and accepts business-level risk |
| System/application owner | Confirms scope, supports remediation, validates impact |
| Security owner | Defines security objectives and receives findings |
| Test lead | Owns methodology, safety, evidence, and delivery quality |
| Operations contact | Handles production stability, emergency escalation, and maintenance coordination |
| SOC/detection contact | Coordinates alert handling and deconfliction when applicable |
| Legal/privacy contact | Advises on data access, evidence, contracts, and regulatory constraints |
| Compliance/audit contact | Aligns deliverables to control requirements when needed |
| Change manager | Coordinates test windows, approvals, freezes, and rollback expectations |
| Third-party/vendor contact | Approves provider-owned or shared systems when in scope |

Communication model should define:

- Kickoff meeting.
- Daily or periodic status updates.
- Emergency escalation channel.
- Finding escalation threshold.
- Real-time critical finding notification.
- Scope-change process.
- Test pause process.
- Draft report review.
- Final report delivery.
- Remediation and retest coordination.

Communication failure is one of the most common causes of penetration-test harm.

## 10. Pre-Engagement Planning

Pre-engagement planning turns the test from a vague activity into a controlled assessment.

Planning should define:

- Business objectives.
- Security objectives.
- Engagement type.
- Scope boundaries.
- Rules of engagement.
- Test methodology.
- Communication plan.
- Safety plan.
- Evidence plan.
- Credential plan.
- Test accounts.
- Source infrastructure.
- Data-handling requirements.
- Legal and privacy constraints.
- Escalation model.
- Reporting format.
- Retest expectations.

Pre-engagement planning should answer:

```text
What are we testing?
Why are we testing it?
Who approved it?
What is out of scope?
What can harm production?
How do we stop if something goes wrong?
What evidence is allowed?
Who needs to know when critical risk is found?
What does a successful deliverable look like?
```

A penetration test without pre-engagement planning becomes uncontrolled discovery.

## 11. Risk and Safety Planning

Risk and safety planning identifies how testing could harm business operations and defines controls to prevent it.

Safety concerns include:

- Service degradation.
- Account lockouts.
- Data corruption.
- Excessive log volume.
- Monitoring overload.
- Change windows.
- Backup windows.
- Third-party abuse-detection triggers.
- Legal or privacy constraints.
- Safety-critical operations.
- Customer-facing disruption.
- Cloud cost spikes.
- Rate-limit exhaustion.
- Email deliverability impacts.
- Production data exposure.

Safety controls include:

- Progressive intensity.
- Rate limits.
- Test windows.
- Canary testing.
- Read-only validation where possible.
- Non-production validation where sufficient.
- Explicit stop conditions.
- Emergency contacts.
- Kill switch.
- Backout plan.
- Data minimization.
- Evidence redaction.
- Approval gates for high-risk actions.

Canonical safety rule:

```text
Use the lowest-impact method that proves the security claim with sufficient evidence.
```

A test should never maximize harm to prove a point already proven at a safer level.

## 12. Test Windows, Change Freezes, and Business-Impact Controls

Test windows define when activity may occur. They must align with operational risk.

Test timing should consider:

- Business peak hours.
- Maintenance windows.
- Backup jobs.
- Batch processing.
- Financial close periods.
- Product releases.
- Customer events.
- Regulatory blackout periods.
- Incident-response staffing.
- SOC coverage.
- Vendor support availability.

Change-freeze coordination matters because penetration testing can be confused with production changes, incidents, or release defects. Testing during a freeze may be forbidden unless explicitly approved.

Business-impact controls include:

- Restricting high-risk validation to low-traffic windows.
- Limiting authentication attempts.
- Limiting scan rates.
- Using dedicated test accounts.
- Coordinating with SOC and operations.
- Monitoring service health during testing.
- Pausing on instability.
- Recording all impactful actions.

Canonical operating rule:

```text
If production behavior becomes unstable, stop testing first, then troubleshoot with the owner.
```

## 13. Target Identification and Scope Boundaries

Target identification translates business scope into testable objects.

Target inventory may include:

- Domains.
- Subdomains.
- IP ranges.
- Applications.
- APIs.
- Mobile backends.
- Cloud accounts/projects/subscriptions.
- SaaS tenants.
- AD domains.
- VPN endpoints.
- Wireless networks.
- Physical facilities.
- User roles.
- Test accounts.
- Network zones.
- Data stores.

Target validation must distinguish:

```text
Known in-scope asset
Potentially related asset needing confirmation
Explicitly out-of-scope asset
Third-party asset requiring separate authorization
Unknown asset that must be escalated before testing
```

Boundary rules:

- Discovery does not automatically expand scope.
- Ownership must be verified before active testing.
- Third-party infrastructure is not testable by assumption.
- Shared hosting and SaaS platforms require provider constraints.
- Cloud environments may contain multiple tenants, accounts, projects, and services with different authorization boundaries.
- Production and non-production may require different safety rules.

Scope discipline is part of professional ethics.

## 14. Reconnaissance at Authorized and Non-Invasive Level

Reconnaissance in a professional pentest is controlled information gathering used to understand exposure, ownership, technology, and test paths.

Authorized recon should support:

- Asset inventory validation.
- Exposure mapping.
- Trust-boundary identification.
- Technology fingerprinting at safe level.
- Public information review.
- Dependency discovery.
- Control-point identification.
- Test planning.

Recon categories:

| Category | Defensive purpose | Safety constraint |
|---|---|---|
| Passive recon | Learn what is publicly visible without direct probing | Avoid unauthorized collection and privacy violations |
| Active recon | Confirm reachable services and surface behavior within scope | Rate-limit, stay in scope, avoid disruption |
| Credentialed recon | Understand authorized views using approved accounts | Respect account roles and data limits |
| Architecture recon | Map trust boundaries and control points | Validate with owners before assuming |

Recon outputs should be structured as:

```text
Observed asset/exposure
Evidence source
Ownership confidence
Scope status
Security relevance
Recommended next validation step
```

Recon is not a license to enumerate everything on the internet. It must remain bounded by authorization and purpose.

## 15. Scanning and Enumeration at Methodology Level

Scanning and enumeration identify reachable services, exposed interfaces, visible configuration, and potential weaknesses. In a professional pentest, scanning is controlled, rate-limited, scoped, and interpreted with context.

Scanning methodology should define:

- Source locations.
- Target ranges.
- Allowed scan types.
- Rate limits.
- Authentication usage.
- Time windows.
- Exclusions.
- Service-impact constraints.
- Logging expectations.
- Error-handling process.

Enumeration should answer:

- What services are exposed?
- Which versions or behaviors are visible?
- Which authentication boundaries exist?
- Which control points are present?
- Which unexpected services exist?
- Which assets deviate from approved inventory?
- Which paths require deeper validation?

Scanning is not validation by itself.

Canonical distinction:

```text
Scanner output = signal.
Enumeration output = context.
Validation output = evidence.
Finding output = risk decision.
```

False positives and false negatives must be expected. Scanner confidence must be checked against live behavior, configuration evidence, asset criticality, exploitability context, and business impact.

## 16. Vulnerability Validation at Safe and Authorized Level

Vulnerability validation determines whether a suspected weakness is real, reachable, relevant, and impactful within the authorized scope.

Safe validation should prove enough to support remediation without causing unnecessary harm.

Validation questions:

- Is the affected asset in scope?
- Is the weakness present?
- Is it reachable under realistic conditions?
- What privilege or precondition is required?
- What data, control, or availability impact could result?
- Is there a compensating control?
- Is exploitation necessary to prove impact, or is configuration evidence sufficient?
- What is the safest proof method?
- What evidence can be recorded without exposing secrets or sensitive data?

Validation evidence may include:

- Configuration evidence.
- Response behavior.
- Access-control decision evidence.
- Version and patch evidence.
- Proof of unauthorized read/write at minimal safe level.
- Control bypass evidence at non-destructive level.
- Logs showing activity.
- Screenshots with sensitive data redacted.
- Owner confirmation.

Canonical rule:

```text
Validate impact, not damage.
```

A professional tester should stop when the security claim is proven at the agreed level.

## 17. Exploitation Concept at High-Level Authorized Validation Level

In penetration testing, exploitation means using a controlled validation method to demonstrate that a weakness can produce security impact. It must be authorized, scoped, safe, and proportionate.

This file treats exploitation as a governance-controlled validation concept, not as a payload library or offensive walkthrough.

Exploitation validation may demonstrate, at safe level:

- Unauthorized access to a resource.
- Authentication bypass.
- Authorization failure.
- Privilege boundary failure.
- Segmentation bypass.
- Sensitive configuration exposure.
- Controlled code execution in a lab or agreed-safe environment.
- Controlled data access using synthetic or minimal test data.
- Cloud misconfiguration impact.
- Identity privilege path existence.

Exploitation must not include unless explicitly authorized and safely controlled:

- Destructive payloads.
- Denial-of-service.
- Persistence.
- Credential dumping.
- Data exfiltration.
- Malware deployment.
- Lateral movement beyond scope.
- Changes to business data.
- Unapproved access to regulated data.

Canonical decision rule:

```text
If a lower-risk proof demonstrates the same security conclusion, use the lower-risk proof.
```

Exploitation for drama is unprofessional. Validation for risk reduction is professional.

## 18. Proof-of-Concept Safety Boundaries

A proof of concept (PoC) is a controlled demonstration that a finding is real. It should be minimal, reversible, and safe.

PoC safety boundaries:

- Use synthetic test data when possible.
- Avoid production data exposure.
- Avoid persistent changes.
- Avoid destructive operations.
- Avoid uncontrolled payloads.
- Avoid broad spraying or mass exploitation.
- Avoid bypassing safety limits.
- Use dedicated test accounts when possible.
- Record every action and timestamp.
- Stop at agreed proof threshold.
- Revert any authorized temporary changes.
- Escalate immediately if unexpected impact occurs.

A safe PoC should prove:

```text
The weakness exists.
The weakness is reachable.
The weakness can produce defined impact.
The impact matters to the business.
The fix can be verified.
```

A PoC should not become a post-exploitation exercise unless that is explicitly part of a separately authorized engagement.

## 19. Privilege, Credential, and Sensitive-Data Handling Rules

Penetration tests often encounter privileged access, credentials, secrets, tokens, personal data, business data, or regulated information. Handling rules must be defined before testing starts.

Credential rules should define:

- Whether credentials will be provided.
- Whether password testing is allowed.
- Attempt limits.
- MFA handling.
- Storage requirements.
- Rotation requirements.
- Prohibited credential actions.
- Secret discovery handling.
- Reporting and redaction requirements.
- Destruction after engagement.

Sensitive-data rules should define:

- Whether production data may be viewed.
- Whether data may be copied.
- Whether samples may be retained.
- Redaction requirements.
- Encryption requirements.
- Access control for evidence.
- Retention period.
- Deletion requirements.
- Breach escalation threshold.

Privilege rules should define:

- Whether privilege escalation validation is in scope.
- Whether administrative access may be used.
- Whether role changes may be attempted.
- Whether new accounts may be created.
- Whether service accounts may be tested.
- Whether tokens, tickets, or keys may be handled.

Canonical rule:

```text
Do not collect or retain sensitive material unless it is necessary, authorized, minimized, protected, and reported.
```

A professional report should prove the risk without unnecessarily exposing the organization further.

## 20. Social Engineering Authorization Boundaries at High Level

Social engineering tests require separate authorization because they involve people, privacy, HR concerns, legal risk, reputational risk, and possible business disruption.

Social engineering scope must define:

- Allowed techniques.
- Target population.
- Excluded users.
- Approved pretexts or themes.
- Prohibited content.
- Communication channels.
- Safety and welfare constraints.
- Credential collection rules.
- Reporting model.
- HR/legal involvement.
- Awareness follow-up.
- Debrief approach.

High-level social engineering categories:

- Phishing simulation.
- Vishing simulation.
- Smishing simulation.
- Physical pretext testing.
- Tailgating assessment.
- Helpdesk process validation.

Forbidden unless explicitly authorized and ethically reviewed:

- Harassment.
- Threatening content.
- Medical, family, or trauma themes.
- Collection of real passwords.
- Public shaming.
- Targeting vulnerable individuals.
- Unapproved impersonation of law enforcement, regulators, legal counsel, or executives.

Social engineering must improve security culture, not punish users.

## 21. Web and API Testing Scope Considerations at High Level

Web and API testing scope must define which applications, APIs, environments, roles, tenants, data classes, and test accounts are authorized.

Scope considerations:

- Application URLs and domains.
- API base paths and versions.
- Authentication methods.
- User roles.
- Tenant boundaries.
- Test accounts.
- Data restrictions.
- Rate limits.
- Business workflows.
- Prohibited transactions.
- Third-party integrations.
- Payment, messaging, and production action restrictions.

High-level validation themes:

- Authentication and session control.
- Object-level authorization.
- Function-level authorization.
- Property-level authorization.
- Input validation.
- Business-logic abuse at safe level.
- Error handling.
- Sensitive data exposure.
- Configuration weakness.
- Dependency and component exposure.
- Logging and monitoring visibility.

Do not assume that web scope includes API scope. APIs often expose different authorization, data, rate-limit, and business-flow risks.

## 22. Cloud Testing Scope Considerations at High Level

Cloud testing requires strong scope control because cloud environments are multi-tenant, API-driven, dynamic, and deeply connected to provider terms, shared responsibility, and billing risk.

Cloud scope should define:

- Cloud provider accounts/projects/subscriptions.
- Tenants and organizations.
- Regions.
- Resource groups.
- In-scope services.
- Out-of-scope services.
- Test identities.
- Permissions granted.
- Logging requirements.
- Provider acceptable-use restrictions.
- Cost controls.
- Data access restrictions.
- Managed service boundaries.
- Third-party marketplace or SaaS dependencies.

High-level validation themes:

- Public exposure.
- IAM privilege paths.
- Static credentials.
- Storage access control.
- Network reachability.
- Secrets handling.
- Logging and audit coverage.
- Metadata-service exposure.
- Insecure workload configuration.
- Inadequate environment separation.
- Backup and delete-protection assumptions.

Cloud pentesting must avoid provider-impacting activity, unmanaged cost spikes, and cross-tenant effects.

## 23. Active Directory and Identity Testing Scope Considerations at High Level

AD and identity testing must be scoped carefully because identity systems are control planes for the enterprise.

Identity scope should define:

- Domains and forests.
- Test accounts and privilege levels.
- Allowed authentication testing.
- Allowed enumeration level.
- Password testing constraints.
- Service account testing constraints.
- Delegation review boundaries.
- Group Policy review boundaries.
- AD CS review boundaries.
- Production account restrictions.
- Sensitive account restrictions.
- Domain controller restrictions.

High-level validation themes:

- Privileged group exposure.
- Excessive delegation.
- Weak service account governance.
- Dangerous ACLs.
- Kerberos and NTLM hardening gaps.
- LDAP signing/channel binding gaps.
- GPO edit risk.
- AD CS template and CA risk.
- Local admin and session exposure.
- Tier 0 boundary failures.

Identity testing should avoid unsafe credential handling and should prioritize configuration evidence, graph reasoning, and owner-approved validation.

## 24. Network and Wireless Testing Scope Considerations at High Level

Network testing scope must define boundaries across IP ranges, VLANs, routing domains, firewalls, VPNs, wireless SSIDs, remote access, and third-party connectivity.

Network scope should define:

- IP ranges.
- Network zones.
- VLANs.
- Wireless SSIDs.
- VPN endpoints.
- Firewalls and security controls.
- Allowed source locations.
- Rate limits.
- Prohibited tests.
- Critical infrastructure exclusions.
- OT/ICS boundaries.
- Monitoring deconfliction.

High-level validation themes:

- Unexpected exposure.
- Segmentation bypass.
- Management-plane reachability.
- Weak remote access paths.
- Wireless access-control gaps.
- Misconfigured network services.
- Inadequate egress control.
- DNS/DHCP/ARP-related control gaps at safe conceptual level.
- Firewall rule drift.
- Lack of monitoring visibility.

DoS, stress testing, unsafe fuzzing, and disruptive wireless tests require separate authorization and safety controls.

## 25. Physical Testing Scope Considerations at High Level

Physical testing is high-risk because it involves people, facilities, safety, law enforcement confusion, employee trust, and potential physical harm.

Physical scope should define:

- Facilities.
- Dates and times.
- Target areas.
- Excluded areas.
- Badge/access-control testing limits.
- Tailgating rules.
- Lock testing rules.
- Photography rules.
- Device placement rules.
- Social interaction boundaries.
- Emergency contacts.
- Safety rules.
- Law enforcement notification model.
- Stop phrase or verification contact.

High-level validation themes:

- Visitor process weakness.
- Badge control weakness.
- Tailgating susceptibility.
- Unattended sensitive areas.
- Exposed network ports.
- Insecure equipment rooms.
- Weak escort process.
- Lack of reporting culture.

Physical testing should never create safety risk, panic, property damage, or humiliation.

## 26. Evidence Collection for Pentest Reporting

Evidence is the factual basis of a penetration-test report. Evidence must be accurate, minimal, protected, and traceable.

Evidence should answer:

```text
What was observed?
Where was it observed?
When was it observed?
How was it validated?
What was the impact?
What was the scope object?
What proof supports the claim?
What sensitive data was avoided or redacted?
```

Evidence may include:

- Screenshots with redaction.
- HTTP request/response excerpts at safe level.
- Configuration excerpts.
- Log references.
- Timestamps.
- Asset identifiers.
- User role identifiers.
- Scope object references.
- Minimal proof output.
- Hashes of created test artifacts.
- Network or application behavior evidence.
- Owner confirmation.

Evidence rules:

- Do not overcollect.
- Do not expose secrets in reports.
- Do not store evidence unencrypted.
- Do not retain evidence beyond approved period.
- Do not mix client evidence across engagements.
- Do not alter timestamps or findings to look stronger.
- Clearly mark uncertainty.

Evidence should support remediation, not create a new breach risk.

## 27. Finding Severity and Risk Rating

Severity should represent real risk, not tool output or dramatic language.

Risk rating should consider:

- Technical impact.
- Business impact.
- Asset criticality.
- Exposure.
- Required privileges.
- Required user interaction.
- Exploit complexity.
- Preconditions.
- Data sensitivity.
- Compensating controls.
- Detection and response visibility.
- Likelihood.
- Remediation urgency.

Canonical rating logic:

```text
Risk = impact × likelihood × exposure × business context × control weakness
```

Finding severity should not be based only on:

- CVSS score.
- Scanner severity.
- Tool name.
- Whether exploitation was possible in a lab.
- Fear-based wording.
- Screenshots alone.

A strong finding includes:

- Title.
- Summary.
- Affected assets.
- Scope and environment.
- Evidence.
- Impact.
- Preconditions.
- Root cause.
- Severity and rationale.
- Remediation guidance.
- Retest method.
- References where useful.

Severity must be defensible to both engineers and leadership.

## 28. Remediation Guidance and Retest Workflow

Remediation guidance must tell owners how to reduce risk, not merely state the vulnerability category.

Good remediation guidance includes:

- Specific control objective.
- Owner or responsible team.
- Fix options.
- Priority.
- Dependencies.
- Change-management considerations.
- Compensating controls when immediate fix is not possible.
- Validation criteria.
- Retest procedure at safe level.
- Evidence needed for closure.

Weak remediation guidance says only:

- “Patch this.”
- “Sanitize input.”
- “Use secure configuration.”
- “Implement least privilege.”
- “Upgrade software.”

Retest workflow:

```text
1. Owner applies fix.
2. Owner provides change evidence if available.
3. Tester validates the original weakness no longer works or no longer exists.
4. Tester checks for regression or partial fix where appropriate.
5. Closure evidence is recorded.
6. Residual risk or exception is documented if not fully fixed.
```

Closure requires evidence, not just ticket status.

## 29. Reporting Structure

A professional penetration-test report should serve multiple audiences while preserving technical accuracy.

Canonical report structure:

1. Executive summary.
2. Objectives.
3. Scope.
4. Methodology summary.
5. Rules of engagement summary.
6. Limitations.
7. Overall risk themes.
8. Key findings.
9. Detailed findings.
10. Evidence appendix.
11. Remediation roadmap.
12. Retest plan.
13. Positive observations.
14. Control gaps and systemic issues.
15. Detection/response observations where applicable.
16. Appendix for asset list, timelines, and references.

Each detailed finding should include:

- Finding ID.
- Title.
- Severity.
- Affected assets.
- Business impact.
- Technical description.
- Evidence.
- Root cause.
- Remediation.
- Retest criteria.

A report should be clear enough for leaders to prioritize and specific enough for engineers to fix.

## 30. Executive vs Technical Reporting

Executive reporting and technical reporting serve different decisions.

Executive reporting should focus on:

- Business risk.
- Impact themes.
- Critical exposure.
- Systemic control gaps.
- Risk ownership.
- Remediation priorities.
- Resourcing implications.
- Program-level recommendations.

Technical reporting should focus on:

- Reproducible evidence at safe level.
- Affected assets.
- Root causes.
- Configuration or code-level weakness description.
- Security control failure.
- Fix guidance.
- Verification steps.
- Logs or evidence references.

Canonical distinction:

```text
Executive report answers: why should leadership care and what must be prioritized?
Technical report answers: what exactly failed, where, why, and how do we prove it is fixed?
```

A single report can contain both, but the language and level of detail must match the audience.

## 31. Debrief and Lessons Learned

The debrief converts test results into improvement actions.

Debrief should cover:

- What was tested.
- What was not tested.
- What worked well.
- What failed.
- Which risks were most important.
- Which assumptions were invalid.
- Which controls prevented impact.
- Which detections fired.
- Which alerts were missed.
- Which findings require immediate action.
- Which findings require architectural change.
- Which scope or process issues affected testing.
- Which improvements should be made before the next test.

Lessons learned should produce:

- Remediation backlog.
- Detection improvement tasks.
- Hardening tasks.
- Asset inventory corrections.
- Change-management improvements.
- Secure SDLC improvements.
- IR/playbook improvements.
- Retest schedule.

A debrief is not a blame meeting. It is an engineering feedback loop.

## 32. Penetration Test vs Vulnerability Assessment vs Red Team vs Audit vs Threat Hunting

These activities are related but not interchangeable.

| Activity | Primary question | Output |
|---|---|---|
| Vulnerability assessment | What known weaknesses appear present? | Prioritized findings and remediation list |
| Penetration test | Can scoped weaknesses be validated into impact? | Evidence-backed exploitability and risk findings |
| Red team | Can realistic adversary objectives be achieved while testing detection and response? | Control, detection, and response gap analysis |
| Audit | Are required controls present and operating according to criteria? | Compliance/control assurance findings |
| Threat hunting | Is there evidence of suspicious behavior not already detected? | Hunt findings, detections, or incident handoff |
| Purple team | Can offense-informed testing improve defensive controls collaboratively? | Improved detections, controls, and validation evidence |

Common confusion:

- A vulnerability scan is not a penetration test.
- A penetration test is not automatically a red team.
- A red team is not just a stealthy pentest.
- An audit is not proof that systems resist abuse.
- Threat hunting is not authorized exploitation.

Choose the engagement type based on the business question.

## 33. Coordination with Detection Engineering, IR, Vulnerability Management, and Risk Governance

Penetration testing should feed security operations and governance.

Coordination with detection engineering:

- Identify missed telemetry.
- Validate whether important actions were observable.
- Produce detection improvement requirements.
- Provide safe test cases for future validation.

Coordination with incident response:

- Define when pentest activity should be deconflicted.
- Identify escalation and triage gaps.
- Improve playbooks where handoffs failed.
- Clarify containment safety expectations.

Coordination with vulnerability management:

- Convert validated findings into remediation tickets.
- Deduplicate scanner findings.
- Improve prioritization with exploitability evidence.
- Define retest criteria.

Coordination with risk governance:

- Map findings to business risk.
- Track accepted residual risk.
- Require accountable ownership.
- Support exception and compensating-control decisions.

Canonical closed loop:

```text
Pentest finding → owner routing → remediation → detection/control improvement → retest → risk record updated
```

A pentest that does not integrate with the security operating model becomes a point-in-time report with limited value.

## 34. Professional Ethics and Safety Constraints

Professional penetration testing requires ethics, restraint, accuracy, and respect for business operations.

Ethical principles:

- Test only with authorization.
- Stay within scope.
- Minimize harm.
- Protect sensitive information.
- Report truthfully.
- Preserve evidence integrity.
- Avoid conflicts of interest.
- Do not exaggerate findings.
- Do not conceal mistakes.
- Stop when required.
- Respect privacy.
- Leave systems no weaker than found.

Safety constraints:

- No unauthorized access.
- No unnecessary data collection.
- No destructive actions without explicit approval.
- No uncontrolled malware or persistence.
- No credential abuse beyond scope.
- No third-party testing by assumption.
- No production-impacting actions outside RoE.
- No public disclosure without authorization.

Professional maturity is shown by knowing when not to proceed.

## 35. Common Penetration-Testing Failures

Common failures include:

- Testing without explicit written authorization.
- Poorly defined scope.
- Ignoring third-party ownership.
- Tool-first testing without objectives.
- Excessive scanning that disrupts services.
- Treating scanner output as validated findings.
- Collecting sensitive data unnecessarily.
- Reporting secrets without redaction.
- Failing to notify stakeholders of critical risk.
- Overstating impact.
- Understating operational risk.
- Providing generic remediation.
- No retest criteria.
- No evidence ledger.
- No escalation path.
- No kill switch.
- No deconfliction with SOC or operations.
- Confusing pentest with red team.
- Leaving test artifacts behind.
- Ignoring business context.
- Failing to convert findings into risk reduction.

Canonical failure pattern:

```text
unclear scope + aggressive tools + weak communication + poor evidence = business risk without security value
```

## 36. Common Mistakes

Do not think:

- Permission is implied because an asset is public.
- Internal employees may test anything they can reach.
- A list of IPs is sufficient scope.
- A scanner report is a pentest report.
- Exploitation must always be performed to prove a finding.
- More severe impact demonstration always creates more value.
- All discovered assets are automatically in scope.
- Production data may be copied if access was obtained.
- Critical findings can wait until the final report.
- Detection alerts should be ignored because the test is authorized.
- Remediation guidance can be generic.
- Retest means rerunning the same scanner only.
- Red team, pentest, and vulnerability assessment are synonyms.
- Social engineering is acceptable without special authorization.
- Cloud provider resources are testable without provider and tenant constraints.
- Physical testing is safe because it is “only security testing.”

Correct thinking:

```text
Authorization bounds the test.
Scope controls target selection.
RoE controls behavior.
Evidence supports claims.
Safety controls preserve operations.
Reporting drives remediation.
Retesting proves closure.
```

## 37. Must-Memorize Facts

- Penetration testing requires explicit written authorization.
- Scope is a contract, not a suggestion.
- Rules of engagement define safe operational behavior.
- Testing objectives define what the test is meant to prove.
- Success criteria define what evidence is sufficient.
- Reconnaissance must remain authorized and purpose-driven.
- Scanning is signal, not proof.
- Vulnerability validation must be safe and proportional.
- Exploitation in a pentest is controlled impact validation, not unrestricted compromise.
- Proof of concept should be minimal, reversible, and non-destructive.
- Sensitive data handling must be defined before testing.
- Social engineering requires special authorization.
- Cloud, SaaS, physical, wireless, and third-party testing require extra scope care.
- Evidence must be accurate, protected, minimized, and traceable.
- Severity must consider impact, likelihood, exposure, asset criticality, and compensating controls.
- Remediation must be specific and verifiable.
- Retesting proves whether risk was reduced.
- Executive reports drive business decisions.
- Technical reports drive engineering fixes.
- A pentest is not a vulnerability assessment, red team, audit, or threat hunt.
- Professional ethics require staying in scope and minimizing harm.

## 38. Interview / Exam Points

### What is the first requirement before penetration testing?

Written authorization and defined scope. Without permission and scope, testing is unauthorized.

### What belongs in rules of engagement?

Scope, test windows, allowed techniques, forbidden actions, rate limits, data-handling rules, communications, escalation, emergency stop process, evidence rules, and reporting expectations.

### How is a penetration test different from a vulnerability assessment?

A vulnerability assessment identifies and prioritizes likely weaknesses. A penetration test validates whether scoped weaknesses can produce security impact under controlled authorized conditions.

### How is a penetration test different from a red team?

A penetration test usually validates vulnerabilities within defined scope. A red team is objective-driven adversary emulation designed to test detection, response, and resilience, often under different disclosure and stealth conditions.

### Why is scope control important?

It prevents unauthorized testing, third-party impact, production harm, legal exposure, and invalid findings.

### Why is scanner output not enough?

Scanners can produce false positives, miss context, ignore business impact, and fail to prove exploitability or control failure.

### What makes a finding useful?

Clear evidence, affected asset, business impact, root cause, severity rationale, specific remediation, and retest criteria.

### What is safe proof-of-concept behavior?

Use the minimum action needed to prove the issue, avoid destruction, avoid sensitive data collection, remain in scope, and stop at the agreed proof threshold.

### What should happen after critical risk is discovered?

Follow the escalation path defined in the RoE; do not wait silently for the final report if immediate business risk exists.

### What proves remediation closure?

Retest evidence showing the original weakness no longer exists or no longer produces the reported impact, plus documentation of any residual risk.

## 39. Expert-Level Insights

### 1. Authorization is a security control

Authorization is not paperwork after the fact. It is the first control in the testing system. It determines what activity is lawful, safe, bounded, and useful.

### 2. The best pentest reports root causes, not just symptoms

A weak report says:

```text
XSS found on page A.
```

A strong report says:

```text
Output encoding is inconsistent across server-rendered templates because the application lacks a centralized encoding standard and security review gate.
```

Root causes create durable fixes.

### 3. Pentesting should test assumptions

Every architecture has assumptions:

- This admin interface is internal only.
- This API enforces tenant isolation.
- This storage bucket cannot be public.
- This user role cannot approve transactions.
- This network zone cannot reach Tier 0.

Penetration testing is strongest when it validates the most important assumptions.

### 4. Evidence quality matters more than technique complexity

A simple, clearly evidenced authorization bypass is more valuable than a complex story with weak proof.

### 5. “No finding” is still useful when evidence is strong

If a test shows that a control resisted authorized validation, that is valuable assurance. Reports should include positive observations where they support risk decisions.

### 6. Safety is a mark of expertise

Expert testers know how to prove impact with minimal operational risk. Causing avoidable production harm usually indicates poor methodology, not skill.

### 7. Retesting is part of the engagement lifecycle

A penetration test is incomplete if the organization cannot verify whether remediation reduced risk.

### 8. Pentest outputs should become reusable control tests

Strong findings can become:

- Regression tests.
- Detection test cases.
- Secure design review questions.
- CI/CD security checks.
- Vulnerability-management prioritization rules.
- Tabletop scenarios.

### 9. Scope exceptions are risk decisions

If a high-risk system is excluded from testing, that exclusion should be visible. Out-of-scope does not mean out-of-risk.

### 10. Professional offensive work is defensive engineering feedback

The purpose is not to glorify compromise. The purpose is to improve architecture, controls, monitoring, response, and governance.

## 40. Internal References to Future CKV Files

This file owns authorized penetration-testing methodology and professional operating boundaries. The following CKV files own detailed expansion areas. CKV IDs and topic meanings follow the approved `MASTER_INDEX_FIXES.md` generation map.

- **CKV-003 — Risk Management and Security Governance**  
  Owns risk ownership, governance decisions, risk acceptance, business impact, and executive-level decision rights that determine why a penetration test is needed and who may accept residual risk.

- **CKV-004 — Asset Management and Attack Surface Inventory**  
  Owns asset inventory, ownership, criticality, exposure mapping, and attack-surface relationships used to define pentest targets and validate scope.

- **CKV-005 — Change Management and Security Exceptions**  
  Owns test-window coordination, change freezes, rollback planning, exception handling, compensating controls, and governance for remediation changes.

- **CKV-010 — Networking Fundamentals and Encapsulation**  
  Owns foundational traffic-flow, addressing, encapsulation, and network communication reasoning used when interpreting network test scope and evidence.

- **CKV-017 — Network Design, Segmentation, DMZs, and Hard Controls**  
  Owns segmentation, trust boundaries, zones, conduits, default-deny design, and hard-control architecture that penetration tests may validate at a high level.

- **CKV-018 — Network Protocol Capture, Structures, and Analysis**  
  Owns packet capture, protocol dissection, capture placement, and evidence interpretation for network-level validation and troubleshooting.

- **CKV-020 — Windows Fundamentals for Security**  
  Owns Windows OS concepts, event logs, remote administration overview, and baseline Windows investigation context referenced during host testing.

- **CKV-026 — Linux Fundamentals and Hardening for Security**  
  Owns Linux OS concepts, permissions, services, logs, SSH, hardening, and investigation context referenced during Linux host testing.

- **CKV-030 — Active Directory Fundamentals**  
  Owns AD forests, domains, domain controllers, objects, OUs, groups, trusts, DNS dependency, and administrative model used for identity-scope planning.

- **CKV-031 — Kerberos Authentication, PAC, Tickets, and Windows Logon**  
  Owns Kerberos flows, tickets, PAC, SPNs, Windows logon relationship, and Kerberos failure logic referenced during identity testing.

- **CKV-032 — NTLM, Netlogon, Relay Risk, and Authentication Hardening**  
  Owns NTLM, Netlogon, fallback behavior, relay exposure, signing/sealing, auditing, and hardening relationships at defensive level.

- **CKV-033 — LDAP, LDAPS, Signing, Channel Binding, and Directory Access**  
  Owns LDAP/LDAPS access, binds, searches, signing, channel binding, directory access control, and enumeration-risk context.

- **CKV-034 — Group Policy Internals and Security**  
  Owns GPO architecture, processing, filtering, delegation, SYSVOL relationship, hardening, drift, and validation evidence.

- **CKV-035 — AD Delegation: Unconstrained, Constrained, and RBCD**  
  Owns Kerberos delegation concepts, S4U relationships, delegation attributes, sensitive account restrictions, inventory, review, and hardening.

- **CKV-036 — Active Directory Attack Paths and Defensive Monitoring**  
  Owns AD attack-path reasoning, Tier 0 exposure, graph relationships, defensive monitoring, exposure reduction, and validation logic.

- **CKV-037 — AD CS and PKI Security**  
  Owns AD CS, certificate templates, certificate-based authentication, private key risk, CA administration, and PKI exposure patterns.

- **CKV-040 — HTTP, Web Fundamentals, Sessions, and Cookies**  
  Owns HTTP behavior, web communication, sessions, cookies, headers, status codes, browser/server relationships, and web traffic reasoning.

- **CKV-041 — OWASP Web Top 10 Canonical Security Model**  
  Owns OWASP web vulnerability taxonomy, risk-category thinking, root causes, and baseline control direction for web findings.

- **CKV-042 — OWASP API Security Top 10 Canonical Model**  
  Owns API-specific vulnerability taxonomy, object/property/function/business-flow failure framing, and API Top 10 category reasoning.

- **CKV-043 — DevSecOps, Secure SDLC, SAST, DAST, SCA, and Security Gates**  
  Owns secure SDLC, security gates, SAST/DAST/SCA, SBOM, pipeline findings, and how pentest results feed development lifecycle improvements.

- **CKV-044 — API Security Controls: Authentication, Authorization, Schema, Rate Limits**  
  Owns API authentication, authorization, token validation, schema validation, rate limiting, gateway patterns, and control implementation expectations.

- **CKV-050 — Cloud Fundamentals: IaaS, PaaS, SaaS, Compute, Storage, IAM**  
  Owns cloud service models, accounts, regions, compute, storage, IAM basics, logging, and cloud operational concepts needed for cloud testing scope.

- **CKV-051 — Cloud Security Architecture and Hard Controls**  
  Owns cloud landing zones, guardrails, IAM hard controls, network segmentation, metadata protections, KMS, object storage controls, logging, backup, and cloud hard-control validation.

- **CKV-060 — Detection Engineering and Telemetry Design**  
  Owns telemetry design, detection use cases, alert logic, coverage mapping, enrichment, tuning, and detection-output evidence used to measure whether pentest activity was observable.

- **CKV-061 — Incident Response Lifecycle and Playbook Design**  
  Owns incident declaration, triage, containment, eradication, recovery, escalation, communications, and playbook structure that may be validated or informed by pentest outcomes.

- **CKV-062 — Threat Hunting Methodology**  
  Owns hypothesis-driven hunting, weak-signal analysis, pivots, hunt findings, and hunt-to-detection or hunt-to-incident handoff after pentest-informed scenarios.

- **CKV-063 — Digital Forensics and Evidence Handling**  
  Owns forensic preservation, chain of custody, acquisition, evidence integrity, timeline analysis, and forensic reporting beyond pentest evidence collection.

- **CKV-064 — SOAR, Automation, Validation, and Provability Outputs**  
  Owns automation workflows, approval gates, dry-run, verification, evidence packages, provability outputs, and safe response validation.

- **CKV-065 — Security Monitoring Tools and Lab Architecture**  
  Owns SIEM/SOAR/EDR/NDR/IDS/IPS/logging tool roles, monitoring lab topology, telemetry pipelines, and validation workflows used for controlled testing environments.

- **CKV-071 — Red Teaming, Campaign Design, OPSEC, and Defensive Value**  
  Owns red-team campaign design, adversary emulation, OPSEC at governance level, detection/response measurement, purple-team value, and defensive outcomes beyond standard penetration testing.

- **CKV-072 — Network Attack Concepts and Defensive Controls**  
  Owns defensive-normalized network attack concepts, control points, telemetry, and mitigations for ARP/DHCP/DNS/VLAN/LLMNR/ICMP-tunnel style risk without exploit automation.

- **CKV-073 — Credential Attack Concepts and Defensive Controls**  
  Owns credential-risk concepts, password attack categories, hash and secret exposure, credential storage risk, relay relationships, hardening, and detection at defensive level.

- **CKV-074 — Privilege Escalation, Persistence, and Lateral Movement Concepts**  
  Owns defensive-normalized post-exploitation concepts, privilege boundary failures, persistence surfaces, lateral movement paths, telemetry, hardening, and IR handling without operational attack walkthroughs.

- **CKV-075 — Social Engineering and Security Awareness**  
  Owns human-risk modeling, phishing/social-engineering concepts, awareness programs, reporting culture, and authorized social-engineering governance beyond high-level pentest boundaries.

- **CKV-080 — Malware, APT Lifecycle, Botnets, and Advanced Threat Controls**  
  Owns malware behavior, APT lifecycle, botnet behavior, command-and-control, persistence context, malicious uploads, web shells, and advanced threat-control relationships beyond standard pentest methodology.

- **CKV-082 — Vulnerability Management, Scanning, Prioritization, and Remediation**  
  Owns vulnerability-management lifecycle, scanning models, prioritization, remediation routing, exceptions, compensating controls, emergency vulnerability response, and closure evidence.

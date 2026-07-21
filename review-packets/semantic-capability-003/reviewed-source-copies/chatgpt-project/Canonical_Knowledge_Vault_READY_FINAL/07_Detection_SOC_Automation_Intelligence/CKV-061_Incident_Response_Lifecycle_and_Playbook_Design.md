# CKV-061 — Incident Response Lifecycle and Playbook Design

## 1. Purpose

This file defines the **canonical model for incident response lifecycle and playbook design**. It explains how a security organization turns detection output into a governed, evidence-aware, safety-controlled, business-aligned response process.

This file answers:

```text
When does an alert become an incident?
Who owns the response?
How is severity declared?
What must be preserved before containment?
Which containment option is safe enough for the business context?
How do eradication, recovery, verification, and closure happen without guessing?
How does a playbook become repeatable, auditable, and improvable?
```

This file does not provide a library of incident-specific playbooks. It defines the structure, lifecycle, roles, decisions, evidence expectations, safety gates, and handoffs that every mature playbook must follow.

Canonical purpose:

```text
Incident response = declared security event
                  + authority
                  + triage
                  + scope
                  + evidence preservation
                  + containment decision
                  + eradication
                  + recovery validation
                  + communication
                  + lessons learned
                  + control improvement
```

The goal of incident response is not only to close a ticket. The goal is to reduce harm, preserve truth, restore trustworthy operations, document decisions, and reduce recurrence.

## 2. Core Definition

**Incident response** is the coordinated process for managing cybersecurity incidents from preparation through detection handoff, declaration, triage, analysis, containment, eradication, recovery, communication, closure, and post-incident improvement.

**Incident response lifecycle** is the repeatable operating model that defines what happens before, during, and after an incident.

**Playbook design** is the disciplined process of converting a known incident class into a repeatable workflow containing triggers, triage questions, evidence requirements, decision points, response actions, safety gates, escalation paths, verification checks, and closure outputs.

Canonical definition:

```text
Incident = event or alert that meets defined impact, threat, policy, legal, or business criteria
           and requires coordinated response beyond normal monitoring.

Playbook = repeatable response workflow
         + inputs
         + decisions
         + actions
         + evidence
         + verification
         + handoffs
         + closure criteria
```

A mature incident response process is:

- Authorized.
- Time-bound.
- Evidence-aware.
- Risk-based.
- Business-aligned.
- Legally aware.
- Technically specific enough to act.
- Safe enough to avoid unnecessary harm.
- Measurable.
- Continuously improved.

## 3. Why Incident Response Matters

Incident response matters because security failures occur under uncertainty, time pressure, incomplete telemetry, business disruption, attacker adaptation, legal exposure, and communication pressure.

Without an incident response lifecycle:

- Alerts remain unowned.
- Analysts improvise during high-pressure situations.
- Evidence is destroyed by well-intentioned cleanup.
- Containment either happens too late or breaks production unnecessarily.
- Recovery restores compromised state.
- Legal, privacy, HR, and executive stakeholders are notified too late.
- Lessons learned become meetings instead of engineering changes.
- The same incident class returns because root causes are not fixed.

Incident response converts security operations from reaction into controlled execution.

Core value:

```text
Detection says: something may be wrong.
Incident response decides: what it means, who acts, what is safe, what is preserved, what is restored, and what must change afterward.
```

## 4. Incident Response Mental Model

Incident response is a **control system under stress**.

It balances five forces:

| Force | Question |
|---|---|
| Harm reduction | What must be stopped immediately? |
| Evidence preservation | What truth will be lost if we act too fast? |
| Business continuity | What operations must remain available? |
| Legal/compliance duty | Who must be notified, and when? |
| Long-term risk reduction | What control gap allowed this to happen? |

Canonical mental model:

```text
Alert / report / observation
        ↓
Triage and validation
        ↓
Incident declaration
        ↓
Scope and impact estimate
        ↓
Containment decision
        ↓
Eradication and root-cause removal
        ↓
Recovery and business validation
        ↓
Closure, reporting, lessons learned
        ↓
Control, detection, playbook, and training improvements
```

Incident response is not purely technical. It is a coordinated operating discipline across SOC, IT, identity, network, endpoint, cloud, application, legal, privacy, compliance, HR, communications, executive leadership, vendors, and affected business owners.

## 5. Event vs Alert vs Case vs Incident vs Finding

These terms must not be used interchangeably.

| Term | Meaning | Typical Owner | Response Meaning |
|---|---|---|---|
| Event | A recorded occurrence in telemetry, system state, or user activity | Logging/telemetry system | May be normal or abnormal |
| Alert | A detection output that requires review | SOC / monitoring team | Needs triage |
| Case | A container for investigation records, evidence, tasks, notes, and decisions | SOC / case owner | May or may not become an incident |
| Incident | A declared cybersecurity event requiring coordinated response | Incident commander / IR team | Activates IR process |
| Finding | A discovered weakness, control gap, vulnerability, exposure, or noncompliance | Security engineering / risk owner | Requires remediation or risk treatment |

Canonical distinction:

```text
Event = something happened.
Alert = something may matter.
Case = someone is investigating.
Incident = coordinated response is required.
Finding = something must be fixed or accepted.
```

A single incident may contain many events, many alerts, several cases, and multiple findings.

## 6. Incident Response Lifecycle Map

Modern incident response should be aligned to a continuous risk-management model, not treated only as a one-way four-step diagram.

Canonical lifecycle:

```text
GOVERN / PREPARE
  policy, authority, roles, playbooks, communications, legal hooks, tooling, exercises

IDENTIFY / UNDERSTAND
  assets, business functions, dependencies, criticality, owners, data sensitivity

PROTECT / REDUCE LIKELIHOOD
  controls, baselines, access restrictions, resilience, segmentation, backups

DETECT / HANDOFF
  alerts, telemetry, reports, detections, adverse-event analysis, declaration criteria

RESPOND / MANAGE INCIDENT
  triage, classify, analyze, contain, eradicate, communicate, document decisions

RECOVER / RESTORE TRUST
  restore services, verify integrity, validate business function, communicate recovery

IMPROVE / LEARN
  root cause, control gaps, detection improvements, playbook updates, exercises
```

The older operational shorthand remains useful:

```text
Preparation → Detection and Analysis → Containment, Eradication, Recovery → Post-Incident Activity
```

But the canonical CKV model treats incident response as a continuous loop that touches governance, asset knowledge, protection, detection, response, recovery, and improvement.

## 7. Preparation at IR-Program Level

Preparation is the work done before an incident so the organization does not improvise during one.

Preparation must define:

- Incident response policy.
- Incident declaration criteria.
- Severity model.
- Roles and decision rights.
- Escalation paths.
- Communications channels.
- Legal/privacy/compliance notification routes.
- Evidence preservation expectations.
- Case management process.
- Playbook library.
- Emergency contact roster.
- Vendor and third-party contact paths.
- Approved containment options.
- Approval gates for disruptive actions.
- Recovery validation criteria.
- Tabletop and exercise cadence.
- Metrics and review cadence.

Preparation artifacts:

```text
IR policy
IR plan
severity model
role matrix
contact matrix
communications templates
playbook library
case templates
evidence checklist
containment decision matrix
recovery validation checklist
tabletop schedule
lessons-learned template
```

A response team is not prepared because tools exist. It is prepared when authority, process, people, evidence, and safe decision paths exist before pressure arrives.

## 8. Detection Output Handoff into Incident Response

Detection output becomes useful to IR only when it carries enough context to support triage and decision-making.

Minimum handoff fields:

| Field | Purpose |
|---|---|
| Alert name / use case ID | Identifies the detection that fired |
| Time window | Defines the initial investigation period |
| Affected entity | Host, user, IP, workload, application, tenant, account, service, API, or mailbox |
| Evidence summary | Explains what matched and why |
| Severity / confidence | Guides triage urgency |
| Data sources | Shows telemetry used and telemetry missing |
| MITRE / behavior mapping | Supports behavior classification, not proof by itself |
| Related alerts | Shows clustering and possible campaign context |
| Known asset criticality | Adds business impact context |
| Suggested triage questions | Helps analyst validate quickly |
| Suggested playbook | Starts the response path |

Detection-to-IR handoff must avoid two extremes:

- Throwing raw alerts over the wall without context.
- Auto-declaring incidents without analyst or rule-confidence safeguards.

Canonical handoff rule:

```text
No response action should depend only on an alert name.
Response decisions require evidence, scope, confidence, impact, and safety context.
```

## 9. Incident Declaration and Classification

An incident is declared when defined criteria indicate that coordinated response is required.

Declaration criteria may include:

- Confirmed compromise.
- High-confidence malicious behavior.
- Unauthorized access to sensitive systems or data.
- Privileged credential compromise.
- Tier 0 or identity-control-plane exposure.
- Malware execution with spread potential.
- Ransomware or destructive activity.
- Data exfiltration or likely exposure.
- Critical service disruption.
- Regulatory or contractual notification trigger.
- Safety or OT impact.
- Executive, legal, or business-owner escalation requirement.

Classification should capture:

- Incident type.
- Affected environment.
- Business impact.
- Data sensitivity.
- Threat behavior.
- Initial access hypothesis.
- Active vs historical status.
- Suspected adversary objective.
- Required stakeholders.
- Required playbook.

Classification is not final truth. It is an initial operational decision that should be revised as evidence improves.

## 10. Triage Workflow

Triage answers whether the case is real, urgent, scoped enough, and ready for incident declaration or escalation.

Canonical triage sequence:

```text
1. Validate alert quality.
2. Identify affected entities.
3. Determine active vs historical status.
4. Check asset criticality and data sensitivity.
5. Look for credential, privilege, lateral movement, exfiltration, or destructive signals.
6. Correlate related telemetry.
7. Estimate initial scope.
8. Assign severity, priority, and confidence.
9. Select playbook or investigation path.
10. Decide: close, monitor, escalate case, or declare incident.
```

Triage questions:

- What exactly triggered the alert?
- Which evidence supports it?
- Which evidence contradicts it?
- Is the activity still ongoing?
- Is a privileged identity involved?
- Is a critical system involved?
- Is a protected data set involved?
- Is there evidence of spread?
- Is there evidence of exfiltration?
- Is there evidence of persistence?
- Is there a known approved change or maintenance window?
- What telemetry is missing?
- What must be preserved before action?

Triage failure modes:

- Closing based on alert title only.
- Treating asset criticality as unknown.
- Ignoring time synchronization issues.
- Assuming user activity is legitimate without context.
- Acting before preserving volatile evidence.
- Waiting for perfect certainty while harm continues.

## 11. Severity, Priority, Impact, Urgency, and Confidence in IR Context

IR decision-making requires separate but related values.

| Term | Meaning |
|---|---|
| Severity | Intrinsic seriousness of the incident based on harm, scope, privilege, data, safety, and business impact |
| Priority | Order in which the team should work, based on severity plus urgency, resources, and business context |
| Impact | Actual or potential damage to confidentiality, integrity, availability, safety, legal duties, reputation, or business function |
| Urgency | How quickly action is required to prevent additional harm |
| Confidence | Strength of evidence supporting the incident hypothesis |

Example severity factors:

- Tier 0 systems involved.
- Privileged credentials exposed.
- Ransomware or destructive behavior observed.
- Active lateral movement.
- Sensitive data exfiltration suspected.
- Public-facing exploitation underway.
- Critical production service affected.
- OT safety or physical process impact.
- Widespread endpoint compromise.

Canonical severity model:

```text
SEV-0 = existential, safety, Tier 0, ransomware outbreak, major exfiltration, or crisis-level impact
SEV-1 = high-impact confirmed incident requiring cross-functional response
SEV-2 = limited confirmed compromise or contained high-risk activity
SEV-3 = suspicious activity requiring investigation but no confirmed major impact
SEV-4 = low-risk event, hygiene issue, or non-incident finding
```

Severity should be revised as scope and evidence improve.

## 12. Scoping and Impact Analysis

Scoping determines the boundary of the incident.

Scoping answers:

- Which identities are affected?
- Which devices or workloads are affected?
- Which applications, APIs, mailboxes, tenants, databases, or cloud accounts are affected?
- Which time window matters?
- Which data may have been accessed?
- Which business processes are affected?
- Which systems are adjacent or reachable?
- Which accounts or tokens were used?
- Which persistence mechanisms may exist?
- Which controls failed?

Impact analysis answers:

- What was interrupted?
- What was accessed?
- What was changed?
- What was destroyed?
- What could still be compromised?
- What must be reported?
- What must be restored?
- What must be monitored after recovery?

Scoping evidence categories:

- Identity logs.
- Endpoint telemetry.
- Network flows.
- DNS/proxy/firewall logs.
- Cloud audit logs.
- SaaS audit logs.
- Application/API logs.
- Email security logs.
- EDR/NDR alerts.
- Change records.
- Asset inventory and owner mapping.
- Backup and restore metadata.

Scoping must be iterative. Early scope estimates should be documented as provisional.

## 13. Evidence Preservation at Response-Workflow Level

This file owns evidence preservation at the response workflow level, not full forensic acquisition.

IR must preserve enough evidence to support:

- Accurate scoping.
- Containment decisions.
- Root-cause analysis.
- Legal and regulatory needs.
- Insurance or contractual needs.
- Lessons learned.
- Control improvement.

Minimum response-level evidence principles:

```text
Preserve before destructive action.
Record who did what and when.
Separate facts from assumptions.
Protect evidence integrity and access.
Capture volatile evidence when required and feasible.
Do not let cleanup erase root-cause truth.
```

Evidence preservation examples:

| Area | Response-Level Evidence |
|---|---|
| Endpoint | Process tree snapshot, EDR timeline, running network connections, recent logons |
| Identity | Authentication logs, privileged group changes, token/session data, MFA events |
| Network | Flow records, firewall decisions, DNS queries, proxy logs, VPN logs |
| Cloud | Control-plane audit logs, identity activity, resource changes, storage access logs |
| Application/API | Authentication events, request IDs, administrative changes, suspicious transactions |
| Email/SaaS | Mailbox rules, OAuth grants, sharing links, login history, admin audit events |

Evidence preservation is not a reason to delay urgent containment indefinitely. It is a reason to capture the minimum viable evidence before actions that may destroy it when safety and business conditions allow.

## 14. Containment Strategy

Containment stops or limits additional harm.

Containment must be chosen based on:

- Active harm.
- Evidence needs.
- Business impact.
- Safety impact.
- Confidence level.
- Scope certainty.
- Attacker adaptability.
- Operational dependencies.
- Available rollback path.
- Approval requirements.

Containment layers:

| Layer | Examples of Containment Intent |
|---|---|
| Identity | Disable account, revoke session, force password reset, rotate secrets, restrict privilege |
| Endpoint | Isolate host, stop malicious process, block execution path, quarantine workload |
| Network | Block destination, restrict segment, disable exposed path, enforce egress control |
| Application | Disable vulnerable function, rotate API key, suspend suspicious integration |
| Cloud/SaaS | Disable token, restrict role, quarantine resource, block public exposure |
| Data | Revoke sharing, freeze access, protect backup, preserve logs |

Containment decision rule:

```text
Use the least disruptive action that stops the known or likely harm, unless delay creates higher risk than disruption.
```

Containment must be documented with:

- Who approved it.
- What was changed.
- Why it was chosen.
- Expected impact.
- Evidence preserved before action.
- Rollback or restoration plan.
- Verification result.

## 15. Short-Term Containment vs Long-Term Containment

Short-term containment is immediate damage control.

Long-term containment is controlled risk reduction while eradication and recovery are prepared.

| Type | Purpose | Example Intent |
|---|---|---|
| Short-term containment | Stop active damage quickly | Isolate affected host, revoke compromised token, block C2, disable exposed integration |
| Long-term containment | Keep business operating while risk is reduced | Segment affected system, restrict admin paths, require jump-host access, add monitoring, reduce privileges |

Short-term containment characteristics:

- Fast.
- Often tactical.
- May be disruptive.
- Requires rapid verification.
- Should preserve evidence if feasible.

Long-term containment characteristics:

- More planned.
- Business-aware.
- Often paired with compensating controls.
- Requires owner approval.
- Must not become permanent unreviewed exception.

Common containment mistake:

```text
Short-term containment is applied, but long-term containment and eradication never happen.
```

This leaves the organization in a fragile state.

## 16. Eradication Strategy

Eradication removes the attacker’s access, persistence, exploited condition, and root cause.

Eradication is not simply deleting malware or closing an alert.

Canonical eradication targets:

- Unauthorized accounts.
- Compromised credentials.
- Active sessions and tokens.
- Persistence mechanisms.
- Malicious files or processes.
- Unauthorized services or scheduled tasks.
- Rogue OAuth grants or API keys.
- Misconfigured exposed services.
- Vulnerable software paths.
- Weak identity permissions.
- Unsafe firewall or routing paths.
- Abused cloud roles or service principals.
- Unapproved GPO or configuration changes.
- Exploited application flaws.

Eradication steps should answer:

- What gave the attacker access?
- What allowed persistence?
- What allowed privilege expansion?
- What allowed movement?
- What allowed command-and-control or exfiltration?
- What control gap must be removed before recovery?

Eradication must be verified. A cleanup action without validation is only a hypothesis.

## 17. Recovery Coordination

Recovery restores systems, services, data, access, and business operations to a trustworthy state.

Recovery is not only availability. Recovery must restore trust.

Recovery coordination includes:

- Recovery owner assignment.
- Business service prioritization.
- Dependency validation.
- Backup selection and integrity verification.
- Credential rotation sequencing.
- Baseline reapplication.
- Patch or configuration remediation.
- Service restoration.
- User communication.
- Heightened monitoring period.
- Final recovery acceptance.

Recovery questions:

- What is safe to restore?
- Which backups are known good?
- Which credentials must be rotated first?
- Which systems must be rebuilt instead of repaired?
- Which controls must be enabled before reconnecting?
- Which business owner confirms service readiness?
- What monitoring confirms no reinfection?

Recovery failure pattern:

```text
The service returns, but the compromise path remains.
```

That is not trusted recovery.

## 18. Business Validation After Recovery

Technical restoration is not enough. Business validation confirms that the affected service is usable, correct, safe, and accepted by the business owner.

Business validation checks:

- Service is reachable through approved paths.
- Critical transactions work.
- Data integrity is confirmed.
- Users can perform required business functions.
- No unsafe temporary access remains.
- Monitoring is active.
- Backups and restore points are updated after clean recovery.
- Legal/compliance communications are complete if required.
- Business owner signs off or records conditions.

Technical team statement:

```text
System is online.
```

Business validation statement:

```text
The business function is restored, operating correctly, and accepted under defined risk conditions.
```

Incident closure should not occur until both technical recovery and business validation criteria are met or an explicit risk acceptance is documented.

## 19. Lessons Learned and Post-Incident Improvement

Lessons learned is an engineering process, not a ceremonial meeting.

Required outputs:

- Final timeline.
- Root-cause analysis.
- Control gap list.
- Detection gap list.
- Telemetry gap list.
- Playbook gap list.
- Response timing analysis.
- Communication review.
- Remediation backlog.
- Owner and due date for every action.
- Risk acceptance record for unresolved items.
- Exercise or validation plan.

Root cause should be expressed as a control failure, not only an attacker action.

Weak root-cause statement:

```text
The incident happened because of malware.
```

Stronger root-cause statement:

```text
The incident happened because user endpoints had local admin sprawl, privileged credentials were used outside PAWs, endpoint-to-server management ports were not restricted, and process telemetry was missing on key servers.
```

Lessons learned must produce change:

- New or tuned detections.
- Hardening baseline updates.
- Architecture changes.
- Playbook updates.
- Tabletop scenario updates.
- Training needs.
- Asset inventory correction.
- Change-management improvement.
- Risk register update.

If no control, detection, process, or training artifact changes after a serious incident, the lessons learned process failed.

## 20. Incident Roles and Responsibilities

Incident response requires named roles, not vague team ownership.

Common roles:

| Role | Responsibility |
|---|---|
| Incident Commander | Owns coordination, priorities, decision cadence, and response execution |
| Technical Lead | Coordinates technical investigation and response workstreams |
| SOC Lead | Owns alert review, triage support, and detection context |
| Forensics Lead | Owns preservation requirements and forensic handoff |
| Identity Lead | Coordinates account, token, privilege, and authentication response |
| Endpoint Lead | Coordinates endpoint containment, eradication, and recovery |
| Network Lead | Coordinates routing, firewall, DNS, proxy, VPN, and segmentation actions |
| Cloud/SaaS Lead | Coordinates cloud tenant, IAM, resource, and SaaS response |
| Application Owner | Coordinates application-specific impact, fixes, and recovery validation |
| Business Owner | Defines business impact and accepts restoration |
| Legal/Privacy Lead | Advises on privilege, notifications, evidence, and regulatory duties |
| Communications Lead | Owns internal/external messaging through approved channels |
| Executive Sponsor | Provides authority for high-impact decisions |
| Scribe / Case Manager | Maintains timeline, decisions, tasks, evidence references, and status |

Role assignment must be documented early. A severe incident without an incident commander becomes parallel improvisation.

## 21. Escalation Model

Escalation ensures the right authority and expertise are engaged at the right time.

Escalation dimensions:

- Technical severity.
- Business impact.
- Legal or regulatory trigger.
- Data sensitivity.
- Executive visibility.
- Media or customer impact.
- Safety or OT impact.
- Third-party involvement.
- Uncertainty and confidence.
- Required destructive or disruptive action.

Escalation triggers:

- Privileged identity compromise.
- Domain controller / identity provider compromise.
- Public-facing system compromise.
- Confirmed exfiltration.
- Ransomware or destructive activity.
- Production outage caused by security event.
- Sensitive data exposure.
- Critical supplier involvement.
- Law enforcement or regulator notification possibility.
- Need to isolate major business service.

Escalation must not only go upward. It also goes sideways to teams with required knowledge: identity, network, cloud, application, legal, HR, and business operations.

## 22. Communications Model

Incident communications must be accurate, controlled, timely, and audience-specific.

Communication types:

| Audience | Communication Need |
|---|---|
| IR team | Tactical updates, tasks, evidence, blockers |
| IT operations | Required actions, service impact, recovery coordination |
| Business owners | Business impact, expected downtime, validation needs |
| Executives | Risk, impact, decisions needed, external exposure |
| Legal/privacy/compliance | Notification risk, evidence handling, privileged communications |
| Employees | Clear user instructions when behavior is required |
| Customers/partners | Approved external messaging when required |
| Regulators/law enforcement | Formal reporting when legally required |

Communication rules:

- Do not speculate as fact.
- Separate confirmed facts, working hypotheses, and unknowns.
- Keep a decision log.
- Use approved secure channels.
- Assume attacker may monitor normal communications during severe incidents.
- Do not disclose unnecessary technical detail externally.
- Coordinate with legal/privacy before regulated communications.

Bad communication can create legal, reputational, and operational harm even if the technical response succeeds.

## 23. Legal, Privacy, Compliance, HR, and Executive Coordination at High Level

Incident response often crosses nontechnical boundaries.

Legal coordination may be needed for:

- Privileged investigation direction.
- External counsel engagement.
- Breach notification analysis.
- Law enforcement engagement.
- Contractual duties.
- Evidence preservation duties.
- Litigation hold.

Privacy coordination may be needed for:

- Personal data exposure.
- Data subject impact.
- Regulatory notice timing.
- Cross-border data issues.
- Sensitive employee data.

Compliance coordination may be needed for:

- PCI, HIPAA, SOC 2, ISO, contractual, sector, or government reporting obligations.
- Control evidence.
- Audit response.
- Customer assurance.

HR coordination may be needed for:

- Insider risk.
- Employee misuse.
- Disciplinary actions.
- Workforce communication.
- Access revocation coordination.

Executive coordination may be needed for:

- High-impact containment decisions.
- Business service shutdown.
- Public messaging.
- Ransomware/extortion governance.
- Customer, board, or regulator engagement.

The IR team does not need to become legal, HR, or public relations. It must know when those authorities must be engaged.

## 24. Incident Timeline and Decision Log

The timeline is the factual backbone of the incident record.

Timeline entries should include:

- Timestamp and timezone.
- Source of timestamp.
- Event description.
- Evidence reference.
- Actor or system involved.
- Confidence level.
- Analyst interpretation if applicable.
- Related decision or action.

Decision log entries should include:

- Timestamp.
- Decision made.
- Decision owner.
- Evidence available at the time.
- Alternatives considered.
- Expected impact.
- Approval record.
- Validation result.

Canonical timeline rule:

```text
If a statement matters, tie it to evidence, time, and confidence.
```

Common timeline mistakes:

- Mixing local time and UTC without labels.
- Recording only analyst notes without evidence references.
- Deleting uncertainty after the fact.
- Failing to record containment decisions.
- Recording tool output without source or collection context.
- Treating alert time as event start time.

## 25. Playbook Design Model

A playbook converts an incident class into a repeatable response workflow.

A strong playbook is:

- Triggerable.
- Evidence-aware.
- Decision-driven.
- Role-aware.
- Safe.
- Verifiable.
- Auditable.
- Updateable.

Playbook design model:

```text
Incident class
  → triggers
  → triage questions
  → required evidence
  → severity gates
  → scope logic
  → containment options
  → approval gates
  → eradication targets
  → recovery validation
  → communications
  → closure criteria
  → improvement actions
```

A playbook is not a static checklist. It is a controlled decision workflow for a class of incidents.

Playbook design inputs:

- Threat model.
- Asset criticality.
- Telemetry availability.
- Existing controls.
- Legal/compliance requirements.
- Business dependencies.
- Recovery capabilities.
- Approved response tools.
- Risk appetite.

## 26. Playbook Anatomy

A complete playbook should include:

| Component | Purpose |
|---|---|
| Name and ID | Stable reference for cases, audits, and automation |
| Scope | Defines incident class and applicable environments |
| Assumptions | States required tools, telemetry, permissions, and dependencies |
| Triggers | Defines what starts the playbook |
| Entry criteria | Defines when the playbook is applicable |
| Exit criteria | Defines when the playbook is complete |
| Roles | Assigns owners and supporting teams |
| Triage questions | Guides validation and initial classification |
| Required evidence | Lists minimum evidence before major actions |
| Severity gates | Defines escalation thresholds |
| Decision points | Controls branching logic |
| Response actions | Defines approved actions by phase |
| Safety gates | Prevents harmful containment or automation |
| Approval requirements | Defines who can authorize disruptive actions |
| Verification steps | Proves containment, eradication, and recovery |
| Communications | Defines internal/external messaging paths |
| Handoffs | Links forensics, hunting, SOAR, VM, BCDR, legal, and engineering |
| Metrics | Measures time, quality, and outcome |
| Lessons learned outputs | Turns incident into improvement work |

Minimum playbook quality bar:

```text
A playbook must say what to check, what evidence to preserve, who decides, what action is safe, how to verify, and when to escalate.
```

## 27. Trigger, Triage, Decision, Response, Verification, Evidence, and Closure Structure

Every playbook should follow the same high-level structure.

```text
Trigger
  What caused the playbook to start?

Triage
  Is it real, active, severe, scoped, and aligned to this playbook?

Decision
  Which branch applies, and who approves the next action?

Response
  What containment, eradication, recovery, or communication actions are performed?

Verification
  How do we prove the action worked?

Evidence
  What evidence supports the conclusion and the response record?

Closure
  What criteria prove the incident is resolved or handed off?
```

Closure requires:

- Incident scope documented.
- Containment verified.
- Eradication verified.
- Recovery validated.
- Business owner acceptance or risk acceptance recorded.
- Required notifications completed.
- Evidence package indexed.
- Lessons learned completed or scheduled.
- Remediation tickets created.
- Detection/playbook updates assigned.

A closed ticket without closure criteria is not incident resolution.

## 28. Containment Safety Gates and Approval Logic

Containment can create harm. Mature playbooks define safety gates before action.

Containment safety questions:

- Could this action destroy volatile evidence?
- Could this action interrupt critical business service?
- Could this action affect safety or OT operations?
- Could this action tip off an adversary before scoping is complete?
- Could this action violate legal, privacy, or HR constraints?
- Is the affected entity correctly identified?
- Is there a rollback or restoration path?
- Is approval required?
- Has the business owner been notified when required?
- How will success be verified?

Approval levels:

| Action Type | Typical Approval Need |
|---|---|
| Read-only investigation | Analyst or case owner approval |
| Low-impact containment | SOC lead or incident commander approval |
| User/session revocation | Identity owner or incident commander approval depending on severity |
| Host isolation | Endpoint owner / incident commander approval, with emergency exceptions |
| Network block affecting production | Network owner + incident commander + business owner for broad changes |
| Service shutdown | Executive or delegated business authority |
| Public communication | Legal/comms/executive approval |
| Destructive cleanup | Technical lead + forensics/legal consideration |

Safety rule:

```text
Automated containment must be approval-gated unless the action is preapproved, scoped, reversible, low-risk, and verified by high-confidence evidence.
```

## 29. Response Verification and Recovery Validation

Response actions must be proven.

Containment verification examples:

- Compromised account cannot authenticate.
- Token/session is revoked.
- Host is isolated from network paths.
- Blocked destination is no longer reachable.
- Public exposure is removed.
- Suspicious process no longer runs.
- Suspicious traffic stops.

Eradication verification examples:

- Persistence location removed.
- Unauthorized account removed.
- Vulnerable service patched or disabled.
- Malicious mailbox rule removed.
- Rogue OAuth grant revoked.
- Unsafe cloud role removed.
- Baseline drift corrected.

Recovery validation examples:

- Service is restored through approved paths.
- Backup integrity verified before restore.
- Restored system passes baseline checks.
- Credentials rotated as required.
- Monitoring confirms normal behavior.
- Business owner validates function.
- No repeat indicators observed during heightened monitoring.

Verification principle:

```text
Response is not complete because an action was executed.
Response is complete when the intended security and business outcome is verified.
```

## 30. Handoff to Forensics, SOAR, Threat Hunting, Vulnerability Management, and BCDR

Incident response coordinates with adjacent disciplines.

| Handoff | When It Happens | CKV Boundary |
|---|---|---|
| Digital forensics | Deep preservation, acquisition, timeline, memory/disk analysis, chain of custody | CKV-063 owns full forensic workflow |
| SOAR / automation | Ticketing, enrichment, approvals, automated evidence collection, safe response orchestration | CKV-064 owns automation and provability outputs |
| Threat hunting | Unknown scope, weak signals, hypothesis-driven exploration beyond current alert | CKV-062 owns hunting methodology |
| Vulnerability management | Exploited vulnerability, patch prioritization, exposure validation, remediation tracking | CKV-082 owns VM lifecycle |
| BCDR | Major outage, crisis coordination, continuity, restore strategy, business resilience | CKV-006 owns BCDR depth |
| Detection engineering | New rule, telemetry gap, tuning, coverage validation | CKV-060 owns detection methodology |

Handoff does not mean dumping work to another team. It means transferring a defined objective, context, evidence, authority, and expected output.

## 31. Endpoint Incident Playbook Considerations at High Level

Endpoint incidents include suspicious execution, malware, credential theft, EDR alerts, local persistence, suspicious scripts, or unauthorized tools.

Endpoint playbook considerations:

- Preserve process tree and EDR timeline.
- Determine user context and privilege level.
- Check lateral movement evidence.
- Check persistence surfaces at high level.
- Decide isolate vs monitor vs collect first.
- Identify whether credentials used on the endpoint require rotation.
- Determine whether rebuild is safer than cleanup.
- Validate endpoint baseline after recovery.
- Monitor for reinfection or similar alerts.

Endpoint evidence examples:

- Process execution telemetry.
- Parent-child process relationships.
- Network connections.
- File writes.
- Logon events.
- Script execution records.
- EDR containment record.

Do not treat endpoint containment as the end of the incident if credentials, lateral movement, or shared infrastructure may be involved.

## 32. Identity and Active Directory Incident Playbook Considerations at High Level

Identity incidents include suspicious authentication, privileged account misuse, AD object change, Kerberos/NTLM abuse signals, GPO change, delegation exposure, AD CS exposure, or identity provider compromise.

Identity/AD playbook considerations:

- Treat Tier 0 compromise as severe until disproven.
- Preserve authentication and directory change logs.
- Identify affected principals, sessions, tokens, devices, and admin paths.
- Review privileged group changes and delegated rights.
- Check for lateral movement using privileged credentials.
- Plan credential reset sequencing carefully.
- Coordinate with business and IT before broad identity changes.
- Verify that access paths are closed after containment.
- Validate administrative tiering and privileged access controls after recovery.

Identity containment must be precise. Disabling the wrong identity or rotating credentials in the wrong order can cause outages or fail to remove attacker access.

## 33. Network, DNS, and Firewall Incident Playbook Considerations at High Level

Network-related incidents include suspicious traffic, command-and-control, DNS tunneling, rogue DHCP, firewall policy violation, exfiltration, segmentation breach, or network control failure.

Network/DNS/firewall playbook considerations:

- Establish source, destination, protocol, time, direction, and owner.
- Correlate flow, DNS, proxy, firewall, VPN, and endpoint context.
- Determine whether traffic is allowed by design or by drift.
- Block only with awareness of business dependency.
- Prefer scoped containment before broad network changes.
- Preserve relevant logs before retention windows expire.
- Validate that blocked paths are actually blocked.
- Update allowed-path governance if a legitimate dependency is discovered.

Network containment without asset ownership context can break business functions. Network investigation without endpoint and identity context can misattribute activity.

## 34. Cloud and SaaS Incident Playbook Considerations at High Level

Cloud and SaaS incidents include control-plane abuse, suspicious IAM activity, public exposure, compromised access keys, malicious resource creation, storage exposure, mailbox compromise, OAuth grant abuse, or tenant misconfiguration.

Cloud/SaaS playbook considerations:

- Preserve audit/activity logs quickly.
- Identify tenant/account/project/subscription/resource scope.
- Determine principal type: user, service account, role, managed identity, application, token, API key.
- Revoke sessions, keys, tokens, grants, or roles as appropriate.
- Check for persistence through IAM, access keys, automation, app registrations, mailbox rules, integrations, or policies.
- Validate public exposure and storage access.
- Check for cost/resource abuse.
- Apply heightened monitoring after containment.
- Verify that cloud guardrails and logging were not disabled.

Cloud response frequently fails when teams disable a resource but leave the credential or role path intact.

## 35. Web and API Incident Playbook Considerations at High Level

Web and API incidents include suspicious authentication, access-control failure, injection indicators, SSRF indicators, excessive data access, exposed admin function, token abuse, API key compromise, or application-layer DoS.

Web/API playbook considerations:

- Identify affected endpoint, route, tenant, user, object, function, and time window.
- Preserve request IDs, application logs, WAF/proxy logs, API gateway logs, and authentication context.
- Determine whether sensitive data was accessed or modified.
- Check whether abuse is authenticated or unauthenticated.
- Rotate API keys/tokens only with dependency awareness.
- Disable or restrict vulnerable functions with business-owner approval.
- Validate patch, configuration, or control change before full recovery.
- Monitor for repeated abuse after remediation.

Do not confuse blocking a request pattern with fixing the application root cause.

## 36. Incident Metrics and Program Health

Incident metrics must measure speed, quality, outcome, and improvement.

Core timing metrics:

- Mean time to detect.
- Mean time to triage.
- Mean time to declare.
- Mean time to contain.
- Mean time to eradicate.
- Mean time to recover.
- Mean time to close.

Quality metrics:

- Incidents with complete timeline.
- Incidents with documented decision log.
- Incidents with required evidence preserved.
- Incidents with business validation recorded.
- Incidents with lessons learned completed.
- Remediation actions closed on time.
- Playbooks tested within review period.
- Repeat incidents by root-cause category.

Outcome metrics:

- Scope reduction over time.
- Containment effectiveness.
- Recovery integrity success.
- Detection gaps closed.
- Control gaps closed.
- Tabletop findings resolved.
- Reduction in recurring incident classes.

Bad metric pattern:

```text
Counting closed tickets as success while incidents recur from the same control gap.
```

Good metrics connect incidents to improved controls.

## 37. Tabletop Exercises and Playbook Validation

Tabletop exercises validate decision-making, roles, escalation, communications, and playbook usability before real pressure.

Tabletop exercise design:

- Scenario aligned to credible threat and business impact.
- Clear objectives.
- Defined participants.
- Injects that force decisions.
- Evidence samples.
- Communication prompts.
- Legal/privacy triggers.
- Containment trade-offs.
- Recovery validation requirements.
- After-action report.
- Remediation tracking.

Playbook validation methods:

- Read-through review.
- Walkthrough exercise.
- Tabletop exercise.
- Technical simulation.
- Purple-team validation.
- Post-incident review.

Validation questions:

- Did everyone know their role?
- Were escalation paths clear?
- Were containment approvals defined?
- Was evidence preservation understood?
- Did communications stay accurate?
- Did the playbook contain enough decisions to act?
- Were recovery and business validation criteria clear?
- Were improvement actions assigned?

A tabletop without tracked corrective actions is only awareness training, not playbook validation.

## 38. Common Incident-Response Failures

Common failures:

| Failure | Consequence |
|---|---|
| No incident declaration criteria | Alerts drift without ownership |
| No incident commander | Parallel teams make conflicting decisions |
| No severity model | Escalation is inconsistent |
| No evidence checklist | Cleanup destroys truth |
| Fix-first behavior | Root cause remains unknown |
| Partial containment | Attacker retains alternate access |
| Credential reset without sequencing | Outages occur or attacker access remains |
| Recovery without integrity checks | Reinfection or recurring compromise |
| No communications discipline | Confusion, legal risk, or reputational harm |
| No business validation | Technical recovery does not equal business recovery |
| No lessons learned tracking | Same incident repeats |
| Unsafe automation | Response causes more damage than incident |

Elite teams prevent failures through preparation, role clarity, evidence discipline, approval gates, verification, and post-incident engineering.

## 39. Common Mistakes

- Treating every alert as an incident.
- Treating no alert as proof of no incident.
- Closing incidents because the original alert stopped.
- Rebooting systems before preserving required evidence.
- Isolating systems without understanding business impact.
- Waiting for perfect attribution before containing active harm.
- Rotating only user passwords while service secrets remain exposed.
- Rebuilding systems while leaving identity or network access paths intact.
- Treating malware deletion as eradication.
- Treating uptime as recovery.
- Mixing facts, assumptions, and opinions in the incident record.
- Not assigning one incident commander.
- Not recording decisions.
- Not involving legal/privacy/compliance when required.
- Using tabletop exercises as presentations instead of decision drills.
- Creating playbooks that are long documents but not executable workflows.
- Automating containment without confidence, scope, approval, rollback, or verification.

## 40. Must-Memorize Facts

- An event is not an alert; an alert is not automatically an incident.
- Incident declaration requires defined criteria.
- Incident response is a coordinated lifecycle, not a single technical action.
- Severity, priority, impact, urgency, and confidence are related but different.
- Containment stops harm; eradication removes cause and persistence; recovery restores trusted operations.
- Evidence preservation must be considered before disruptive or destructive actions.
- Short-term containment is not a substitute for eradication.
- Recovery must include integrity validation.
- Business validation is required before confident closure.
- A playbook must include triggers, triage, decisions, actions, verification, evidence, and closure criteria.
- Legal, privacy, HR, compliance, and executives may be required stakeholders.
- Lessons learned must produce control, detection, process, or training changes.
- Automation must be safe, scoped, approval-aware, and verifiable.
- A closed case without root-cause and remediation tracking is weak closure.
- Repeated incidents are evidence that lessons learned failed.

## 41. Interview / Exam Points

Common interview prompts:

```text
Explain the difference between event, alert, case, incident, and finding.
Describe the incident response lifecycle.
What must happen before containment?
How do you decide severity?
What is the difference between containment, eradication, and recovery?
What belongs in an incident playbook?
How do you avoid destroying evidence during response?
How do you validate recovery?
What is a lessons learned output?
When should legal/privacy/compliance be engaged?
How should SOAR containment be controlled safely?
```

Strong answers should emphasize:

- Defined incident criteria.
- Evidence-aware triage.
- Severity and business impact.
- Incident commander and clear roles.
- Containment safety gates.
- Eradication of root cause.
- Recovery validation.
- Communications discipline.
- Lessons learned as engineering changes.
- Handoff boundaries to forensics, hunting, SOAR, BCDR, and vulnerability management.

Weak answers usually:

- Recite a lifecycle without decisions.
- Ignore legal/privacy and business owners.
- Treat tool execution as response.
- Skip evidence preservation.
- Skip recovery validation.
- Do not explain how the organization improves afterward.

## 42. Expert-Level Insights

1. **Incident response is decision engineering.** Tools collect data and execute actions, but mature IR depends on making good decisions under uncertainty.

2. **Containment is a risk trade-off, not a reflex.** Fast isolation may be correct for ransomware but harmful for fragile OT, critical healthcare, or evidence-sensitive investigations.

3. **Identity containment often has the highest return.** Many incidents persist through credentials, sessions, tokens, keys, roles, and service accounts even after hosts are cleaned.

4. **Recovery without trust is only uptime.** If credentials, baselines, persistence, and segmentation are not validated, the service is available but not trustworthy.

5. **The timeline is a product.** A high-quality timeline allows scope, legal analysis, root cause, recovery validation, and lessons learned to converge.

6. **The incident commander protects the response from chaos.** Without a coordination owner, multiple teams optimize locally and create global failure.

7. **Playbooks should be decision trees, not narratives.** A playbook must tell responders how to branch based on evidence, severity, confidence, and business impact.

8. **Lessons learned must be measurable.** If remediation tickets, detection updates, or control validations do not appear, the lesson was not operationalized.

9. **Automation should increase discipline, not bypass it.** SOAR is valuable when it preserves evidence, enforces approvals, records actions, validates outcomes, and prevents unsafe improvisation.

10. **Good IR starts before detection.** Asset inventory, logging, access control, segmentation, backups, legal contacts, and exercises determine whether response succeeds.

## 43. Internal References to Future CKV Files

This file owns incident response lifecycle and playbook design. The following CKV files own supporting or adjacent knowledge areas. CKV IDs and topic meanings follow the approved `MASTER_INDEX_FIXES.md` generation map.

- **CKV-001 — Security Engineering Role and Operating Model**  
  Owns the security-engineering operating model, cross-team responsibilities, advisory role, and governance relationship that support incident response ownership.

- **CKV-002 — Security Principles and Secure-by-Design Thinking**  
  Owns defense-in-depth, least privilege, fail-safe defaults, secure defaults, and trust-boundary reasoning used when selecting response and containment actions.

- **CKV-003 — Risk Management and Security Governance**  
  Owns risk ownership, risk acceptance, governance escalation, and business-risk framing used when incident decisions require management acceptance.

- **CKV-004 — Asset Management and Attack Surface Inventory**  
  Owns asset inventory, owners, criticality, exposure mapping, and asset-to-telemetry relationships required for scoping and business impact analysis.

- **CKV-005 — Change Management and Security Exceptions**  
  Owns emergency change handling, rollback planning, drift detection, and exception governance that affect incident containment and remediation changes.

- **CKV-006 — Business Continuity, Disaster Recovery, and Resilience**  
  Owns continuity, disaster recovery, BIA, RTO, RPO, backup strategy, restore testing, ransomware recovery readiness, and resilience exercises beyond IR workflow.

- **CKV-010 — Networking Fundamentals and Encapsulation**  
  Owns networking foundations and traffic-flow reasoning used when interpreting network incident scope and path behavior.

- **CKV-017 — Network Design, Segmentation, DMZs, and Hard Controls**  
  Owns segmentation, zones, conduits, trust boundaries, choke points, default-deny design, and architecture-level containment paths.

- **CKV-018 — Network Protocol Capture, Structures, and Analysis**  
  Owns packet capture methodology, capture placement, protocol dissection, packet/flow distinction, and capture evidence interpretation.

- **CKV-020 — Windows Fundamentals for Security**  
  Owns Windows OS fundamentals, event logs, audit policy, PowerShell relevance, Windows attack surface overview, and investigation basics.

- **CKV-024 — Windows Registry, Services, Scheduled Tasks, and Persistence Surfaces**  
  Owns Windows persistence surfaces, registry/services/tasks investigation logic, and drift surfaces that endpoint incident playbooks reference.

- **CKV-026 — Linux Fundamentals and Hardening for Security**  
  Owns Linux users, sudo, systemd, cron, logs, auditd, SSH, host firewall, and baseline Linux investigation surfaces.

- **CKV-030 — Active Directory Fundamentals**  
  Owns AD structure, domain controllers, groups, trusts, SYSVOL, DNS dependency, and administrative model used in identity incident scoping.

- **CKV-031 — Kerberos Authentication, PAC, Tickets, and Windows Logon**  
  Owns Kerberos flows, tickets, PAC, Windows logon relationship, failure modes, and Kerberos investigation logic.

- **CKV-032 — NTLM, Netlogon, Relay Risk, and Authentication Hardening**  
  Owns NTLM, Netlogon, relay exposure, NTLM auditing/restriction, and authentication-hardening concepts used during identity incident response.

- **CKV-033 — LDAP, LDAPS, Signing, Channel Binding, and Directory Access**  
  Owns LDAP access, binds, searches, directory enumeration risk, signing/channel binding, and LDAP telemetry used in directory incident analysis.

- **CKV-034 — Group Policy Internals and Security**  
  Owns GPO architecture, SYSVOL, processing, GPO delegation, security risks, and change/drift evidence used in AD incident response.

- **CKV-035 — AD Delegation: Unconstrained, Constrained, and RBCD**  
  Owns delegation models, S4U relationships, delegation attributes, protected users, and delegation risk used in identity response scoping.

- **CKV-036 — Active Directory Attack Paths and Defensive Monitoring**  
  Owns AD attack-path reasoning, Tier 0 exposure, privilege graph concepts, AD telemetry, exposure reduction, and AD defensive monitoring.

- **CKV-037 — AD CS and PKI Security**  
  Owns AD CS, certificate templates, enrollment, PKINIT relationship, certificate mapping, CA administration, PKI exposure patterns, and AD CS monitoring.

- **CKV-040 — HTTP, Web Fundamentals, Sessions, and Cookies**  
  Owns HTTP, sessions, cookies, browser/server behavior, origins, CORS, redirects, and web traffic reasoning used in web incident triage.

- **CKV-041 — OWASP Web Top 10 Canonical Security Model**  
  Owns OWASP Web Top 10 vulnerability categories, root causes, conceptual controls, and web-risk taxonomy used in web incident classification.

- **CKV-042 — OWASP API Security Top 10 Canonical Model**  
  Owns OWASP API Top 10 taxonomy, API object/property/function/business-flow risk framing, and API-specific failure categories.

- **CKV-043 — DevSecOps, Secure SDLC, SAST, DAST, SCA, and Security Gates**  
  Owns secure SDLC, pipeline findings, SBOM, scanner outputs, build integrity, release gates, and development-side remediation intake.

- **CKV-044 — API Security Controls: Authentication, Authorization, Schema, Rate Limits**  
  Owns API authentication, authorization, schema validation, rate limits, gateway patterns, webhook controls, response hygiene, and API logging expectations.

- **CKV-050 — Cloud Fundamentals: IaaS, PaaS, SaaS, Compute, Storage, IAM**  
  Owns cloud resource, tenant, account, compute, storage, IAM, logging, audit trail, and troubleshooting fundamentals used during cloud incident scoping.

- **CKV-051 — Cloud Security Architecture and Hard Controls**  
  Owns cloud guardrails, IAM hard controls, KMS, immutable backups, logging architecture, object storage controls, public exposure controls, and cloud validation evidence.

- **CKV-060 — Detection Engineering and Telemetry Design**  
  Owns detection use cases, telemetry design, alert enrichment, severity/confidence at detection-output level, coverage validation, and detection handoff into IR.

- **CKV-062 — Threat Hunting Methodology**  
  Owns hypothesis-driven hunting, weak-signal investigation, hunt planning, IOC/TTP handling, and converting hunt findings into detections and incidents.

- **CKV-063 — Digital Forensics and Evidence Handling**  
  Owns forensic acquisition, chain of custody, volatile data, disk/memory artifacts, timelines, evidence integrity, and forensic reporting.

- **CKV-064 — SOAR, Automation, Validation, and Provability Outputs**  
  Owns automation workflows, approval gates, response validation, evidence packages, proof outputs, ticketing, notifications, and safe automated outcomes.

- **CKV-065 — Security Monitoring Tools and Lab Architecture**  
  Owns SIEM/SOAR/EDR/NDR/lab architecture, Security Onion/Wazuh/Splunk/Zeek/Suricata-style tool roles, and telemetry pipeline lab validation.

- **CKV-070 — Penetration Testing Methodology and Authorization**  
  Owns authorized testing methodology, rules of engagement, scope control, reporting, and safe validation boundaries that may produce incident-response lessons.

- **CKV-071 — Red Teaming, Campaign Design, OPSEC, and Defensive Value**  
  Owns red-team campaign design, adversary emulation value, OPSEC, detection/response evaluation, and purple-team handoff to IR improvement.

- **CKV-072 — Network Attack Concepts and Defensive Controls**  
  Owns defensive summaries of network attack concepts and control relationships that may appear in network incident playbooks.

- **CKV-073 — Credential Attack Concepts and Defensive Controls**  
  Owns credential attack concept taxonomy, defensive controls, and credential-risk framing that identity incident playbooks reference.

- **CKV-074 — Privilege Escalation, Persistence, and Lateral Movement Concepts**  
  Owns defensive conceptual summaries of privilege escalation, persistence, and lateral movement patterns used when scoping incidents.

- **CKV-075 — Social Engineering and Security Awareness**  
  Owns phishing/social-engineering concepts, awareness, reporting behavior, and user-driven incident triggers.

- **CKV-080 — Malware, APT Lifecycle, Botnets, and Advanced Threat Controls**  
  Owns malware behavior, APT lifecycle, botnet behavior, command-and-control, persistence context, malicious uploads, web shells, and supply-chain compromise relationships.

- **CKV-081 — Firewalls, WAFs, IDS/IPS, and Network Security Controls**  
  Owns firewall, WAF, IDS/IPS, proxy, inspection, tuning, rule design, and network security control behavior used in containment and validation.

- **CKV-082 — Vulnerability Management, Scanning, Prioritization, and Remediation**  
  Owns vulnerability scanning, prioritization, remediation, exposure validation, verification, and vulnerability-driven incident remediation workflow.

- **CKV-090 — Command-Line and Built-in Administration Tools for Security Work**  
  Owns built-in security investigation commands, administrative tools, command interpretation, and safe command usage during triage and validation.

- **CKV-091 — Virtualization, Lab Design, and Safe Practice Environments**  
  Owns lab design, safe practice environments, virtualization, controlled testing, and exercise environments for tabletop and playbook validation.

# CKV-060 — Detection Engineering and Telemetry Design

## 1. Purpose

This file defines the **canonical model for detection engineering and telemetry design**. It explains how security teams convert logs, events, flows, metrics, traces, packets, and artifacts into reliable detections, useful alerts, measurable coverage, and response-ready evidence.

This file answers:

```text
What must be logged?
Where must telemetry come from?
Which fields must exist for attribution and correlation?
How is a detection use case designed, tested, tuned, owned, and retired?
How do alerts become actionable evidence instead of noise?
How can a security team prove detection coverage instead of assuming it?
```

This file does not provide a full SIEM query library. It defines the engineering model behind useful detections, regardless of platform.

Canonical purpose:

```text
Detection engineering = threat/control hypothesis
                      + required telemetry
                      + normalized schema
                      + detection logic
                      + enrichment
                      + severity/confidence model
                      + testing
                      + tuning
                      + coverage validation
                      + response handoff
```

Telemetry design is the foundation. Detection engineering fails when telemetry is incomplete, late, untrusted, unparsed, unnormalized, or missing the fields required for attribution.

## 2. Core Definition

**Detection engineering** is the disciplined process of designing, implementing, validating, tuning, measuring, and maintaining detection logic that identifies suspicious behavior, malicious activity, policy violations, control failures, and security-relevant anomalies from available telemetry.

**Telemetry design** is the disciplined process of deciding what events, fields, logs, metrics, traces, flows, packets, and artifacts must be generated, collected, normalized, retained, enriched, protected, and made searchable to support detection, investigation, response, assurance, and auditability.

Canonical definition:

```text
Detection = condition over trusted telemetry
          + context
          + expected meaning
          + severity/confidence
          + evidence output
          + response path
```

A detection is not only a query. A real detection includes:

- Purpose.
- Threat or control-failure hypothesis.
- Data-source dependencies.
- Required fields.
- Normalization assumptions.
- Logic.
- Expected baseline.
- Exceptions and suppressions.
- Severity and confidence model.
- Triage questions.
- Test cases.
- Owner.
- Review cadence.
- Evidence output.
- Response handoff.

A detection that cannot be tested, tuned, attributed, or explained is not mature detection content.

## 3. Why Detection Engineering and Telemetry Design Matter

Security controls do not matter operationally if their failures cannot be observed.

Detection engineering matters because organizations must know:

- When authentication is abused.
- When privileged access is misused.
- When malware executes.
- When persistence is created.
- When a security control is disabled.
- When data is accessed unusually.
- When cloud control-plane state changes.
- When network paths violate design.
- When an application exposes sensitive behavior.
- When a system stops producing logs.

Telemetry design matters because the best rule cannot detect what the environment never records.

Canonical security reality:

```text
No telemetry  = no detection.
Bad telemetry = bad attribution.
No context    = noisy alerts.
No testing    = unproven detection.
No tuning     = detection decay.
No evidence   = weak response handoff.
```

Detection engineering is the bridge between architecture and operations:

```text
Architecture says: this should never happen.
Telemetry proves: it happened or did not happen.
Detection says: this event matters.
Triage says: this is true, false, or unknown.
Response says: now act safely.
```

For security engineering, detections are not random signatures. They are observable controls.

## 4. Detection Engineering Mental Model

Detection engineering begins with a precise question:

```text
Which observable behavior proves a threat, policy violation, or control failure may be occurring?
```

The mature mental model is:

```text
Threat behavior / control failure
        ↓
Required observable events
        ↓
Required fields and context
        ↓
Detection logic
        ↓
Alert output
        ↓
Triage decision
        ↓
Evidence package
        ↓
Response or tuning action
```

A detection engineer must think across five layers:

1. **Environment reality**
   - Assets, identities, applications, network paths, cloud resources, data stores, business processes.

2. **Telemetry reality**
   - Which systems emit events, what fields exist, how reliable they are, and how fast they arrive.

3. **Logic reality**
   - Whether a condition, sequence, threshold, correlation, or baseline actually represents meaningful behavior.

4. **Operational reality**
   - Whether analysts can triage the alert, whether context exists, and whether response actions are safe.

5. **Program reality**
   - Whether coverage, false positives, false negatives, rule health, and data health are measured over time.

Canonical detection design formula:

```text
Good detection = observable behavior
               + reliable fields
               + meaningful context
               + bounded false positives
               + tested logic
               + actionable output
```

## 5. Monitoring vs Alerting vs Detection Engineering vs Threat Hunting vs Incident Response

These terms are related but not interchangeable.

| Concept | Canonical meaning | Primary output |
|---|---|---|
| Monitoring | Continuous observation of systems, controls, events, and health signals. | Dashboards, logs, metrics, status indicators. |
| Alerting | Notification that a condition crossed a threshold or matched a rule. | Alert, ticket, notification, queue item. |
| Detection engineering | Design and maintenance of reliable logic that identifies security-relevant behavior. | Detection content, test cases, coverage map, evidence output. |
| Threat hunting | Analyst-led search for suspicious activity based on hypotheses, weak signals, or threat intelligence. | Hunt findings, new hypotheses, improved detections. |
| Incident response | Structured handling of confirmed or suspected incidents. | Triage decision, containment, eradication, recovery, lessons learned. |

Boundary rule:

```text
Monitoring watches.
Alerting notifies.
Detection engineering builds reliable signal.
Threat hunting searches where detections may not exist.
Incident response acts on confirmed or high-risk events.
```

Detection engineering does not replace hunting. Hunting often discovers patterns that become detections.

Detection engineering does not replace incident response. It produces evidence and context so responders can act faster and safer.

## 6. Telemetry Mental Model: Event, Log, Metric, Trace, Flow, Packet, and Artifact

Telemetry is not one thing.

| Telemetry type | Meaning | Security use |
|---|---|---|
| Event | A discrete occurrence recorded by a system. | Logon, process creation, policy change, file write, API call. |
| Log | A recorded stream or collection of events, messages, or audit entries. | Investigation history, alert input, audit evidence. |
| Metric | Numeric measurement over time. | CPU spikes, failed login counts, queue backlog, data volume. |
| Trace | Linked path of execution across services or components. | Application/API request path, distributed-service troubleshooting. |
| Flow | Summary of communication between endpoints over time. | Network session evidence, volume, direction, protocol, ports. |
| Packet | Captured network unit with headers and payload visibility where available. | Deep protocol analysis, troubleshooting, forensic validation. |
| Artifact | Preserved evidence object outside the normal event stream. | Memory image, disk image, exported logs, PCAP, configuration snapshot. |

Canonical distinction:

```text
Event  = something happened.
Metric = how much/how often/how long.
Trace  = where a request traveled.
Flow   = who talked to whom.
Packet = what was on the wire.
Artifact = preserved object for proof or analysis.
```

Detection engineering usually begins with events and logs, but mature investigations require multiple telemetry types.

Example:

```text
Suspicious API token use:
- Event: token used from new location.
- Log: identity-provider audit log.
- Metric: spike in API request rate.
- Trace: affected application request path.
- Flow: outbound connection volume.
- Artifact: exported audit evidence attached to case.
```

## 7. Detection Lifecycle

A detection has a lifecycle. Unmanaged detections decay.

Canonical lifecycle:

```text
1. Identify requirement
2. Define hypothesis
3. Map data sources
4. Validate telemetry fields
5. Build logic
6. Baseline behavior
7. Test with known cases
8. Deploy at controlled severity
9. Triage and measure outcomes
10. Tune and suppress carefully
11. Review coverage and drift
12. Retire, replace, or promote
```

### 1. Identify requirement

A requirement may come from:

- Threat model.
- Control objective.
- Incident lessons learned.
- Audit requirement.
- Vulnerability exposure.
- Red/purple team validation.
- Cloud/identity/network architecture rule.
- Known adversary behavior.

### 2. Define hypothesis

A detection hypothesis must be observable:

```text
Weak:
Detect attackers.

Strong:
Detect privileged account authentication to Tier 0 systems from devices outside the approved admin workstation group.
```

### 3. Map data sources

Define required telemetry before writing the rule.

### 4. Validate fields

A rule must not assume fields exist. Required fields must be tested.

### 5. Build logic

Choose the correct logic type: condition, threshold, sequence, correlation, baseline, aggregation, or stateful detection.

### 6. Baseline behavior

Run in report-only mode to learn expected volume and normal sources.

### 7. Test

Test positive and negative cases. A detection without tests is an assumption.

### 8. Deploy

Start with staged severity unless the condition is already proven high-confidence.

### 9. Measure

Track true positives, false positives, analyst time, missed cases, and data gaps.

### 10. Tune

Tune with ownership and evidence. Do not blindly suppress.

### 11. Review

Quarterly or risk-based recertification prevents rule rot.

### 12. Retire

Retire obsolete detections when technology, architecture, or threat models change.

## 8. Detection Use-Case Design

A detection use case is a structured statement of what should be detected and why.

Minimum use-case fields:

```text
Detection ID:
Name:
Objective:
Threat/control-failure hypothesis:
Related assets:
Related identities:
Required data sources:
Required fields:
Logic summary:
Expected baseline:
Severity model:
Confidence model:
Enrichment required:
False-positive sources:
Suppression/exception rules:
Triage questions:
Response handoff:
Test cases:
Owner:
Review cadence:
ATT&CK mapping if useful:
Control mapping if useful:
Evidence output:
```

Good use cases are specific.

Weak use case:

```text
Detect suspicious PowerShell.
```

Strong use case:

```text
Detect PowerShell launched by Office, browser, script host, or archive utility with network activity, encoded command indicators, or download/execution behavior on user endpoints where such parent-child relationships are not expected.
```

Use-case design must define the expected decision:

```text
Alert means: analyst should check X.
Alert proves: condition Y occurred.
Alert does not prove: incident confirmed.
```

## 9. Data-Source Selection and Coverage Mapping

Data-source selection decides whether a detection is possible.

Canonical selection questions:

1. **Can the source observe the behavior?**
   - Endpoint telemetry sees process execution better than firewall logs.
   - DNS logs see name resolution better than endpoint file logs.

2. **Does the source include required fields?**
   - Actor, target, action, outcome, time, device, source, destination.

3. **Is the source reliable?**
   - Loss, latency, retention, tamper risk, parsing quality.

4. **Is the source deployed widely enough?**
   - A detection covering 20% of assets is not enterprise coverage.

5. **Can it support correlation?**
   - Identity, host, session, network, object, request IDs.

Coverage mapping must be explicit:

```text
Detection coverage = target population
                   + required telemetry coverage
                   + field completeness
                   + rule deployment scope
                   + validation result
```

Coverage dimensions:

- Asset coverage.
- Identity coverage.
- Network-zone coverage.
- Cloud account/subscription/project coverage.
- Application/API coverage.
- Log-source coverage.
- Control coverage.
- ATT&CK technique coverage, when useful.
- Critical-asset coverage.

Coverage map example:

| Detection objective | Required telemetry | Covered population | Gap |
|---|---|---|---|
| Privileged logon from non-admin workstation | Identity logs, endpoint inventory, admin workstation tags | Domain controllers, admin accounts | Missing workstation ownership tags |
| Public cloud storage exposure | Cloud audit logs, resource inventory, policy state | Production cloud accounts | Dev sandbox logging incomplete |
| DNS tunneling suspicion | Resolver logs, endpoint identity, egress flow | Corporate endpoints | Guest network resolver not integrated |

Never claim detection coverage only because a rule exists.

## 10. Telemetry Source Categories

A mature security program designs telemetry across multiple planes.

### Endpoint telemetry

Typical sources:

- Process creation.
- Command-line execution.
- Parent-child process relationship.
- File creation/modification/deletion.
- Module/library loads.
- Registry or configuration changes.
- Service and scheduled-task changes.
- Local user/group changes.
- Security-product state changes.
- Network connections from host perspective.

Security value:

- Malware execution.
- Persistence.
- Credential access indicators.
- Admin tool usage.
- Endpoint policy drift.

### Identity telemetry

Typical sources:

- Authentication success/failure.
- MFA prompts and outcomes.
- Token issuance.
- Privilege grants.
- Group/role membership changes.
- Service principal changes.
- Conditional access results.
- Directory object changes.

Security value:

- Account takeover.
- Privilege escalation.
- Lateral movement.
- Tier 0 exposure.
- Identity-control-plane compromise.

### Network telemetry

Typical sources:

- Firewall logs.
- NetFlow/IPFIX/VPC flow logs.
- VPN logs.
- IDS/IPS/NDR alerts.
- Proxy logs.
- NAT translation logs.
- Load balancer logs.

Security value:

- Unauthorized paths.
- Egress anomalies.
- C2 patterns.
- Scanning.
- Lateral movement evidence.

### DNS telemetry

Typical sources:

- Recursive resolver logs.
- DNS query/response logs.
- NXDOMAIN rate.
- Domain reputation enrichment.
- DNS filtering logs.

Security value:

- Malware beaconing.
- DGA behavior.
- DNS tunneling suspicion.
- Policy violations.
- Host attribution when joined to endpoint and DHCP data.

### Proxy and web-gateway telemetry

Typical sources:

- URL requests.
- User identity mapping.
- Category and reputation.
- HTTP method and status.
- Uploaded/downloaded bytes.
- TLS inspection metadata where lawful and approved.

Security value:

- Data exfiltration signals.
- Malware download paths.
- User-driven web risk.
- Suspicious destinations.

### Firewall and boundary-control telemetry

Typical sources:

- Allow/deny decisions.
- Rule ID.
- Interface/zone.
- Source/destination.
- NAT mapping.
- Application identification where available.

Security value:

- Segmentation violations.
- Internet exposure.
- Egress governance.
- Rule drift.

### Email telemetry

Typical sources:

- Message headers.
- Sender/recipient.
- Attachment metadata.
- URL rewrite/click logs.
- Delivery/quarantine outcomes.
- Authentication results such as SPF/DKIM/DMARC at high level.

Security value:

- Phishing campaigns.
- Business email compromise.
- Malware delivery.
- User-targeting patterns.

### Cloud telemetry

Typical sources:

- Cloud audit/activity logs.
- IAM policy changes.
- Resource configuration changes.
- Network flow logs.
- Storage access logs.
- KMS/key usage logs.
- Security service findings.
- Control-plane API calls.

Security value:

- Control-plane abuse.
- Public exposure.
- Key misuse.
- Privilege escalation.
- Logging or backup tampering.

### SaaS telemetry

Typical sources:

- Login events.
- Admin actions.
- Sharing changes.
- Data export events.
- OAuth app consent.
- Mailbox or collaboration access.

Security value:

- Account compromise.
- Unauthorized sharing.
- Data exfiltration.
- Shadow application access.

### Application and API telemetry

Typical sources:

- Authentication and authorization decisions.
- Sensitive object access.
- Administrative actions.
- API gateway events.
- Rate-limit outcomes.
- Schema validation failures.
- Error rates.
- Business workflow events.

Security value:

- Broken access control indicators.
- Business abuse.
- Sensitive data access.
- API abuse patterns.

### EDR and NDR telemetry

EDR focuses on endpoint truth. NDR focuses on network behavior. Neither replaces the other.

Canonical relationship:

```text
EDR answers: what did this host/process do?
NDR answers: what did this network conversation look like?
SIEM answers: how do these signals correlate across the environment?
```

## 11. Normalized Telemetry Fields and Event-Schema Thinking

Normalization converts vendor-specific logs into stable security language.

Minimum canonical field families:

```text
Time:
  event.time
  ingest.time
  observed.time

Event:
  event.id
  event.category
  event.type
  event.action
  event.outcome
  event.severity
  event.provider
  event.raw_id

Actor / principal:
  actor.id
  actor.name
  actor.type
  actor.domain
  actor.privilege_tier
  actor.session_id

Source:
  src.ip
  src.port
  src.hostname
  src.asset_id
  src.zone
  src.cloud_resource_id

Destination:
  dst.ip
  dst.port
  dst.hostname
  dst.asset_id
  dst.zone
  dst.cloud_resource_id

Process:
  process.name
  process.path
  process.command_line
  process.parent.name
  process.parent.path
  process.hash
  process.user

Object:
  object.type
  object.id
  object.name
  object.path
  object.owner
  object.sensitivity

Network:
  network.protocol
  network.direction
  network.bytes_in
  network.bytes_out
  network.nat.src_ip
  network.nat.dst_ip

Cloud:
  cloud.provider
  cloud.account_id
  cloud.region
  cloud.service
  cloud.resource_id
  cloud.api_call

Application/API:
  http.method
  http.status_code
  url.path
  api.endpoint
  api.operation
  api.tenant_id
  api.object_id
  api.authz_result

Detection:
  rule.id
  rule.name
  rule.version
  rule.confidence
  rule.severity
  rule.owner
```

Normalization principles:

- Preserve raw logs for traceability.
- Normalize only fields needed for detection, correlation, investigation, and reporting.
- Avoid destructive parsing that discards original meaning.
- Track parser version.
- Monitor parser failure rate.
- Use consistent time representation.
- Use stable asset and identity identifiers, not only display names.

Event-schema thinking prevents rules from being tied to one vendor’s field names.

## 12. Signal Quality: Fidelity, Precision, Recall, Noise, Context, Timeliness, and Completeness

Detection quality depends on signal quality.

| Quality property | Meaning | Failure symptom |
|---|---|---|
| Fidelity | The event accurately represents what happened. | Logs are ambiguous or misleading. |
| Precision | Alerts are likely to be true positives. | Analysts drown in false positives. |
| Recall | Real suspicious behavior is likely to be detected. | Incidents happen with no alert. |
| Noise | Volume of irrelevant or low-value events. | Alert fatigue, ignored queues. |
| Context | Additional facts needed to decide meaning. | Analysts cannot determine severity. |
| Timeliness | Event arrives fast enough for action. | Alert arrives after damage is done. |
| Completeness | Required fields and sources exist. | Attribution, correlation, or scope fails. |

Precision and recall tradeoff:

```text
High precision, low recall:
  fewer alerts, but misses many cases.

High recall, low precision:
  catches many cases, but overwhelms analysts.

Mature detection:
  risk-tiered balance based on asset criticality, threat model, and response capacity.
```

Context examples:

- Is the user privileged?
- Is the device managed?
- Is the target a crown jewel?
- Is the action inside an approved change window?
- Is the source an admin workstation?
- Is the destination normally contacted?
- Is this the first time this relationship appeared?

Most false positives are caused by missing context, weak baselines, or wrong assumptions.

## 13. Detection Logic: Conditions, Thresholds, Sequences, Correlations, Baselines, Aggregations, and Stateful Logic

Detection logic must match the behavior being detected.

### Conditions

A direct match against one or more properties.

```text
Example concept:
Security logging service disabled.
```

Best for:

- High-signal policy violations.
- Known dangerous configuration changes.
- Known bad indicators.

Weakness:

- Brittle if field quality is poor or adversary behavior changes.

### Thresholds

Counts or rates crossing a limit.

```text
Example concept:
More than N failed logons for same principal within T minutes.
```

Best for:

- Brute-force patterns.
- Resource abuse.
- Volume anomalies.

Weakness:

- Bad thresholds create noise or blind spots.

### Sequences

Ordered events over time.

```text
Example concept:
Privilege granted → new login → sensitive data export.
```

Best for:

- Attack chains.
- Suspicious workflows.
- Multi-stage abuse.

Weakness:

- Requires stable time and session correlation.

### Correlations

Joining events across sources.

```text
Example concept:
Endpoint process creates outbound connection to domain recently queried through DNS.
```

Best for:

- Attribution.
- Lateral movement.
- Cloud/identity/app relationships.

Weakness:

- Requires normalized keys and enrichment.

### Baselines

Expected behavior learned over time.

```text
Example concept:
This server normally never authenticates to external SaaS admin APIs.
```

Best for:

- Environment-specific anomalies.
- User/entity behavior.
- Service behavior.

Weakness:

- Baselines drift and can learn bad behavior if not controlled.

### Aggregations

Summary statistics over grouped events.

```text
Example concept:
Top external destinations by byte volume per user per day.
```

Best for:

- Outliers.
- Data movement.
- High-volume sources.

Weakness:

- Can hide small but critical events.

### Stateful logic

Logic that remembers prior state.

```text
Example concept:
First time this service account used from this host.
```

Best for:

- First-seen detections.
- Rare relationships.
- Drift detection.

Weakness:

- Requires state storage and aging policy.

## 14. Detection Content Anatomy

A mature detection content object includes more than logic.

Canonical anatomy:

```yaml
id: CKV-style unique detection ID
title: Human-readable name
version: Semantic or date-based version
status: experimental | test | production | deprecated
owner: Team/person responsible
objective: What the detection is intended to identify
hypothesis: Observable behavior statement
data_sources:
  - required source
required_fields:
  - field name
logic_summary: Vendor-neutral logic explanation
severity_model: How severity is assigned
confidence_model: How confidence is assigned
enrichment:
  - asset criticality
  - identity tier
  - change window
false_positive_sources:
  - expected admin activity
suppressions:
  - condition, owner, expiry
triage_questions:
  - question
response_handoff: IR/playbook reference
testing:
  positive_cases:
  negative_cases:
coverage:
  population:
  known_gaps:
references:
  - ATT&CK/control/internal reference if useful
review_cadence: Quarterly/risk-based
```

Detection content should also declare what it does **not** prove.

Example:

```text
This alert proves that a privileged account authenticated from an unapproved source.
It does not prove credential theft by itself.
```

This prevents overreaction and improves triage quality.

## 15. Rule Types: Signature, Behavioral, Statistical, Anomaly, Correlation, Graph/State, and ML-Assisted

Different rule types serve different purposes.

### Signature rules

Match known indicators, names, patterns, hashes, domains, strings, or specific event combinations.

Strengths:

- Fast to deploy.
- Easy to explain.
- Good for known bad activity.

Weaknesses:

- Easy to evade.
- Low coverage for novel behavior.
- Can become stale.

### Behavioral rules

Detect meaningful behavior rather than exact artifacts.

Strengths:

- More resilient than static indicators.
- Better for living-off-the-land and misuse.

Weaknesses:

- Requires context and baselining.

### Statistical rules

Detect deviations using counts, rates, distributions, or outlier thresholds.

Strengths:

- Useful for volume anomalies.
- Good for resource abuse and brute force patterns.

Weaknesses:

- Requires careful threshold design.

### Anomaly rules

Detect behavior that differs from established normal patterns.

Strengths:

- Can find unknown activity.

Weaknesses:

- High false positives if baselines are weak.
- Requires explainability for analyst trust.

### Correlation rules

Join multiple signals across time, source, or entity.

Strengths:

- Produces richer context.
- Better reflects real attack chains.

Weaknesses:

- Requires normalized fields and stable join keys.

### Graph/state rules

Detect risky relationships or state transitions.

Examples:

- New edge between privileged identity and sensitive system.
- New admin role assignment.
- First-time service account use from workstation.
- New route from user subnet to management plane.

Strengths:

- Excellent for identity, cloud, and architecture drift.

Weaknesses:

- Requires inventory, baseline, and historical state.

### ML-assisted rules

Use machine learning or statistical models to rank, cluster, or detect unusual behavior.

Strengths:

- Useful for large-scale entity behavior and rare-pattern discovery.

Weaknesses:

- Must be explainable.
- Must be validated.
- Must not replace engineering judgment.

Canonical rule:

```text
ML can assist detection engineering.
ML does not remove the need for telemetry quality, triage design, testing, and ownership.
```

## 16. MITRE ATT&CK Mapping and Limitations

MITRE ATT&CK provides a common behavior vocabulary for adversary tactics, techniques, sub-techniques, procedures, mitigations, data sources, and detection concepts.

Useful ATT&CK mapping purposes:

- Standardize detection coverage language.
- Connect detections to adversary behaviors.
- Identify coverage gaps.
- Prioritize detections based on relevant threat models.
- Compare red/purple team results to monitoring coverage.
- Communicate with threat intelligence, SOC, IR, and engineering teams.

ATT&CK mapping is useful when it is precise.

Weak mapping:

```text
Rule maps to Credential Access.
```

Strong mapping:

```text
Rule maps to a specific technique/sub-technique and states which observable behavior is actually covered.
```

Limitations:

1. **ATT&CK mapping is not detection coverage by itself.**
   - A spreadsheet cell marked green does not prove the rule works.

2. **One technique can require many detections.**
   - Different platforms, tools, permissions, and telemetry sources produce different evidence.

3. **One detection can map to multiple techniques.**
   - Overmapping can exaggerate coverage.

4. **ATT&CK does not define business impact.**
   - A low-level technique on Tier 0 may be higher priority than a noisy technique on low-value assets.

5. **ATT&CK is not a substitute for architecture-specific controls.**
   - Your own network, identity, cloud, and application contracts define high-confidence violations.

Canonical use:

```text
Use ATT&CK as a behavior taxonomy.
Use telemetry validation to prove coverage.
Use business context to prioritize.
```

## 17. Alert Enrichment and Context

Enrichment converts alerts from isolated events into decisions.

Core enrichment categories:

### Asset context

- Asset ID.
- Hostname.
- Owner.
- Business service.
- Environment: production, development, test.
- Criticality.
- Data classification.
- Exposure: internet-facing, internal, restricted.
- Zone or segment.

### Identity context

- User ID.
- Group membership.
- Privilege tier.
- Role.
- MFA state.
- Service account flag.
- Break-glass flag.
- Recent privilege changes.

### Network context

- Source and destination zone.
- NAT attribution.
- VPN session.
- Proxy user mapping.
- Geo/ASN.
- Allowed-path policy.

### Cloud context

- Account/subscription/project.
- Region.
- Resource owner.
- IAM role.
- Resource tags.
- Public/private exposure.
- Policy compliance state.

### Change context

- Approved change ticket.
- Maintenance window.
- Deployment ID.
- Emergency change record.
- Exception expiry.

### Threat context

- Known indicators.
- Campaign or malware family if confirmed.
- Technique mapping.
- Reputation.

Enrichment must be deterministic where possible. Expensive live lookups during detection execution can create latency, failure modes, and inconsistent results.

Canonical enrichment principle:

```text
Alert without context asks analysts to guess.
Alert with context asks analysts to decide.
```

## 18. Triage Questions at Detection-Output Level

Detection outputs should include the first questions an analyst must answer.

Universal triage questions:

1. What happened?
2. Who performed the action?
3. What asset or object was affected?
4. When did it begin and end?
5. Is it still active?
6. Was the action successful?
7. Is the actor privileged?
8. Is the target critical?
9. Is there an approved change or known maintenance activity?
10. Is there a policy exception?
11. Has this actor/source/target relationship occurred before?
12. Are related events present before or after the alert?
13. Is the alert reproducible from raw telemetry?
14. What evidence should be preserved?
15. Which response path applies if confirmed?

Detection-specific triage examples:

```text
Privileged logon anomaly:
- Was the source device an approved admin workstation?
- Was the account recently elevated?
- Was MFA satisfied?
- Were subsequent admin actions performed?

DNS tunneling suspicion:
- Which host generated queries?
- Are query lengths, entropy, and NXDOMAIN rates abnormal?
- Is there matching egress traffic?
- Does the host role justify this DNS behavior?

Cloud IAM policy change:
- Who changed the policy?
- Was the principal using temporary or static credentials?
- What permissions were added?
- Is the target production or Tier 0 equivalent?
```

Good detection output should reduce analyst time to first decision.

## 19. Alert Severity, Priority, Confidence, Impact, and Urgency

Alert scoring must separate related but different ideas.

| Term | Meaning |
|---|---|
| Severity | How bad the condition could be if true. |
| Priority | How quickly the team should act. |
| Confidence | How likely the alert represents the intended condition. |
| Impact | Business/security consequence if confirmed. |
| Urgency | Time sensitivity of action. |

Canonical model:

```text
Priority = severity + confidence + asset criticality + active harm + time sensitivity
```

A high-severity alert with low confidence may be routed differently from a medium-severity alert with high confidence and active impact.

Suggested severity dimensions:

- Privilege level involved.
- Asset criticality.
- Data sensitivity.
- Control-plane vs data-plane impact.
- Internet exposure.
- Lateral movement potential.
- Ransomware/destructive potential.
- Active exfiltration evidence.
- Persistence evidence.
- Scope and spread.

Suggested confidence dimensions:

- Field completeness.
- Source fidelity.
- Correlation strength.
- Historical rarity.
- Known false-positive rate.
- Validation quality.
- Presence of supporting events.

Alert records should carry both severity and confidence.

Example:

```text
Severity: High
Confidence: Medium
Reason: Privileged authentication to Tier 0 from unapproved source, but source asset tag is stale.
Action: Immediate triage and inventory verification.
```

## 20. False Positives, False Negatives, Suppression, Tuning, and Drift

### False positives

A false positive occurs when detection logic fires but the intended security condition is not present.

Common causes:

- Missing context.
- Weak baseline.
- Poor asset tags.
- Misparsed fields.
- Legitimate admin activity.
- Maintenance windows.
- Broad logic.
- Duplicate logs.

### False negatives

A false negative occurs when the intended security condition exists but no alert fires.

Common causes:

- Missing data source.
- Missing field.
- Parser failure.
- Rule disabled.
- Log delay.
- New adversary behavior.
- Overly narrow logic.
- Suppression mistake.
- Incomplete deployment coverage.

False negatives are often more dangerous than false positives because they are less visible.

### Suppression

Suppression reduces alert volume by hiding or routing known expected activity.

Suppression rules must include:

```text
Condition:
Owner:
Reason:
Approver:
Expiry:
Review cadence:
Evidence:
```

Permanent undocumented suppression is detection debt.

### Tuning

Tuning improves signal quality. Tuning actions include:

- Add context filters.
- Refine thresholds.
- Split one broad rule into multiple specific rules.
- Add asset/identity criticality.
- Change severity routing.
- Improve parser or normalization.
- Add telemetry requirements.
- Convert low-confidence rules into hunting queries.

### Drift

Detection drift happens when environment behavior changes but detection assumptions do not.

Examples:

- New admin workstation group not reflected in enrichment.
- Cloud accounts added without audit log onboarding.
- Endpoint agent removed from a server class.
- New application uses service accounts differently.
- Network segmentation changes without rule updates.

Drift control:

```text
Detection rule + data source + parser + enrichment + asset inventory + exception list must be reviewed together.
```

## 21. Detection Testing and Validation

Detection testing proves that alerts fire when they should and remain quiet when they should.

Minimum test types:

### Positive test

Proves the detection fires on expected behavior.

```text
Known controlled event → rule fires → alert contains required fields → evidence is attached.
```

### Negative test

Proves the detection does not fire on approved normal behavior.

```text
Approved admin action → no high-severity alert or correct low-priority route.
```

### Parser test

Proves raw logs become expected normalized fields.

```text
Sample raw event → parser output contains actor, target, action, outcome, time, source.
```

### Pipeline test

Proves telemetry moves end-to-end.

```text
Source generates event → collector receives → SIEM indexes → detection job can query → alert can route.
```

### Baseline test

Proves expected volume is known.

```text
Rule produces expected daily/weekly volume within acceptable range.
```

### Regression test

Proves changes did not break existing detection behavior.

```text
Rule update → test suite still passes.
```

### Purple-team validation

Uses authorized emulation or simulation to prove behavior is observable and detection fires.

Validation evidence should include:

- Test name.
- Date/time.
- Environment.
- Source system.
- Raw event IDs.
- Normalized fields.
- Rule version.
- Alert ID.
- Analyst decision.
- Result: pass/fail/partial.
- Remediation action if failed.

A detection is not production-quality until it has validation evidence.

## 22. Detection Coverage Analysis and Gap Management

Coverage analysis determines what the detection program can and cannot see.

Coverage must be measured against meaningful surfaces:

- Critical assets.
- Identity tiers.
- Network zones.
- Cloud accounts.
- SaaS tenants.
- Applications and APIs.
- ATT&CK techniques where relevant.
- Control objectives.
- Incident scenarios.
- Business-critical processes.

Coverage dimensions:

```text
1. Log-source coverage
2. Field coverage
3. Parser coverage
4. Rule coverage
5. Enrichment coverage
6. Asset population coverage
7. Validation coverage
8. Analyst workflow coverage
```

Gap categories:

| Gap type | Meaning | Example |
|---|---|---|
| Source gap | Required logs are not collected. | No DNS resolver logs. |
| Field gap | Logs exist but lack required fields. | No user identity in proxy logs. |
| Parser gap | Raw logs not normalized correctly. | Event outcome mapped inconsistently. |
| Enrichment gap | Context missing. | Asset criticality unknown. |
| Rule gap | No logic exists. | No detection for cloud logging disablement. |
| Validation gap | Rule exists but untested. | No positive/negative test evidence. |
| Workflow gap | Alert has no triage path. | Analyst does not know what to check. |

Gap management process:

```text
Identify gap → classify → assign owner → prioritize by risk → fix or accept with expiry → validate → update coverage map.
```

Coverage claims must be evidence-backed.

## 23. Detection-as-Code and Version Control at High Level

Detection-as-code treats detection content like managed engineering artifacts.

Detection-as-code includes:

- Rule files.
- Parser files.
- Schema definitions.
- Test fixtures.
- Sample logs.
- Suppression lists.
- Severity mappings.
- Documentation.
- Review history.
- Deployment pipeline.

Benefits:

- Version history.
- Peer review.
- Rollback.
- Testing before deployment.
- Change approval.
- Consistent promotion from test to production.
- Better reuse across environments.

Canonical detection-as-code lifecycle:

```text
draft → peer review → test → staging → production → monitor → tune → recertify → retire
```

Detection-as-code does not require a specific platform. The engineering principle is that detection content should be reproducible, reviewable, testable, and auditable.

Minimum repository structure concept:

```text
/detections
/parsers
/schemas
/tests
/sample_events
/enrichment
/playbooks
/docs
/changelog
```

Rules should not be edited silently in production without version history.

## 24. Telemetry Pipeline Quality at Design Level

A telemetry pipeline is a security-critical system.

Canonical pipeline:

```text
Source emits event
   ↓
Collector/agent/API receives
   ↓
Transport/forwarder moves event
   ↓
Parser extracts fields
   ↓
Normalizer maps schema
   ↓
Enrichment adds context
   ↓
Storage indexes/retains
   ↓
Detection engine evaluates
   ↓
Alert/case output routes
```

Pipeline quality requirements:

### Availability

- Collectors must be monitored.
- Queues must have capacity.
- Backpressure must be visible.
- Source silence must alert.

### Integrity

- Logs must be protected from unauthorized modification.
- Critical logs should be forwarded off-host quickly.
- Retention controls should prevent premature deletion.
- Access to logs must be audited.

### Latency

- Detection-critical sources need near-real-time delivery.
- Batch logs must declare expected delay.
- Late events must be handled correctly.

### Loss visibility

Monitor:

- Events generated vs received.
- Drop rate.
- Queue depth.
- Parse failure rate.
- Unknown event format count.
- Collector health.
- Ingestion volume anomalies.

### Retention

Retention must support:

- Triage.
- Incident investigation.
- Threat hunting.
- Compliance.
- Legal/regulatory needs.
- Long-term trend and coverage analysis.

### Time quality

Time must be normalized and trustworthy.

Problems caused by bad time:

- Broken sequences.
- Wrong incident timelines.
- Failed correlation.
- Incorrect severity decisions.
- Weak evidence.

Telemetry pipeline quality is part of detection coverage.

## 25. Detection Metrics and Program Health

Metrics must measure detection usefulness, not vanity output.

Useful detection metrics:

### Rule quality metrics

- True positive rate.
- False positive rate.
- Precision.
- Estimated recall through validation.
- Alert volume per rule.
- Analyst time per alert.
- Escalation rate.
- Closure outcome distribution.
- Rule age since last review.

### Telemetry health metrics

- Source coverage percentage.
- Field completeness percentage.
- Parser success rate.
- Ingestion latency.
- Drop rate.
- Source silence count.
- Log volume deviation.
- Retention compliance.

### Coverage metrics

- Critical assets with required telemetry.
- Privileged identities covered by identity monitoring.
- Cloud accounts with audit logs onboarded.
- Detections validated in last review period.
- ATT&CK/control objectives with proven coverage.

### Operational metrics

- Mean time to detect.
- Time to triage.
- Mean time to respond, when connected to IR.
- Queue backlog.
- Alerts per analyst.
- Reopened incidents.
- Suppression backlog.

### Program health metrics

- Detection backlog age.
- Tuning backlog age.
- Data-source onboarding backlog.
- Detection validation pass rate.
- Rules without owner.
- Rules without test cases.
- Rules without playbook.

Bad metrics:

```text
Number of alerts generated.
Number of logs collected.
Number of ATT&CK boxes colored green.
Number of dashboards created.
```

These can be useful operational facts, but they do not prove effective detection.

## 26. Evidence Outputs and Response Handoff

A detection output must support response.

Minimum alert evidence:

```text
Alert ID
Rule ID and version
Timestamp
Actor/principal
Source asset
Destination/target asset
Affected object/resource
Action
Outcome
Raw event references
Normalized fields
Supporting correlated events
Enrichment snapshot
Severity and confidence rationale
Triage questions
Recommended next evidence
Related playbook
```

Evidence handoff principles:

- Preserve raw event pointers.
- Include enough context for a responder who did not write the rule.
- Avoid irreversible conclusions in alert names.
- Distinguish suspicion from confirmation.
- Record analyst decision and rationale.
- Attach validation or reproduction data where relevant.

Poor alert title:

```text
User compromised.
```

Better alert title:

```text
Privileged account authenticated to Tier 0 system from unapproved source.
```

Response handoff must define:

- What to check first.
- What evidence to preserve.
- When to escalate.
- Which team owns containment.
- Which business owner must be notified.
- Which false-positive patterns are known.

Detection engineering ends where incident response begins, but the boundary must be clean.

## 27. Windows and Active Directory Detection Engineering at High Level

Windows and AD detection must focus on identity, privilege, execution, persistence, policy, and directory control-plane events.

High-value telemetry categories:

- Logon success/failure.
- Logon type and session context.
- Privileged group membership changes.
- Local administrator changes.
- Process creation and command line.
- Service creation and modification.
- Scheduled task creation and modification.
- Registry autorun or security setting changes.
- PowerShell/script activity at policy-approved depth.
- Security product disablement or exclusion changes.
- Directory object changes.
- GPO changes.
- Kerberos/NTLM authentication anomalies.
- LDAP insecure bind or enumeration patterns where observable.
- AD CS enrollment or template changes where deployed.

High-level detection themes:

```text
Identity abuse:
  unusual logons, privilege grants, impossible travel, stale accounts, service account misuse.

Privilege/control-plane abuse:
  privileged group changes, GPO edits, delegation changes, replication-right changes.

Execution and persistence:
  suspicious process relationships, new services, scheduled tasks, autoruns.

Defense evasion/control failure:
  audit policy changes, log clearing, EDR/AV disablement, firewall changes.

Lateral movement indicators:
  unusual admin protocols, session patterns, remote service creation, abnormal authentication paths.
```

Do not detect AD by only watching domain controllers. Many attack paths involve endpoints, admin workstations, servers, file shares, identity providers, and network paths.

Minimum context for Windows/AD alerts:

- Account privilege tier.
- Source workstation/server role.
- Target asset criticality.
- Logon type.
- Authentication package where available.
- Group membership state.
- Change window.
- Related process or network event.

## 28. Network, DNS, Proxy, and Firewall Detection Engineering at High Level

Network detection must focus on paths, direction, identity attribution, volume, protocol behavior, and policy violations.

High-value telemetry categories:

- Firewall allow/deny logs.
- DNS resolver logs.
- Proxy logs.
- Flow logs.
- VPN logs.
- NAT translation logs.
- Load balancer logs.
- IDS/IPS/NDR alerts.
- Network device admin/configuration logs.

High-level detection themes:

```text
Segmentation violation:
  traffic crosses a path that architecture says should not exist.

C2 / beaconing suspicion:
  repeated outbound connections, rare destination, unusual timing, matching DNS behavior.

DNS abuse:
  DGA-like patterns, high NXDOMAIN, unusual query length, unexpected resolver use.

Exfiltration suspicion:
  unusual outbound volume, rare destination, sensitive source, protocol mismatch.

Scanning:
  many ports/hosts contacted, abnormal failed connection patterns, reconnaissance signals.

Network control drift:
  firewall rule changed, logging disabled, route/path created, VPN policy changed.
```

Attribution is the hard part.

Needed joins:

- IP to asset.
- IP to user.
- DHCP lease where relevant.
- NAT translation.
- Proxy authentication.
- VPN session.
- Cloud resource ID.
- Zone and owner.

Network alerts without attribution become slow investigations.

## 29. Cloud and IAM Detection Engineering at High Level

Cloud and IAM detection must prioritize the control plane.

High-value telemetry categories:

- Cloud audit/activity logs.
- IAM policy and role changes.
- Service account/service principal changes.
- Access key creation and use.
- Federation and token activity.
- Security group/firewall/routing changes.
- Public storage or public endpoint changes.
- KMS/key policy and key usage events.
- Logging configuration changes.
- Backup/delete protection changes.
- Secrets access.
- Admin console/API access.
- SaaS admin and sharing events.

High-level detection themes:

```text
Privilege escalation:
  role/policy creation, new admin grants, privilege boundary changes.

Static credential risk:
  new access key, old key use, key used from unusual source.

Public exposure:
  public storage, public IP, open security group, exposed admin interface.

Defense evasion:
  audit logs disabled, retention changed, security service disabled.

Persistence:
  new service principal, external trust, OAuth app consent, long-lived token.

Data access:
  unusual object storage reads, bulk exports, cross-region transfer.

Recovery sabotage:
  backup deletion, vault policy change, object lock removal where possible.
```

Cloud detections need cloud-specific context:

- Account/subscription/project.
- Organization/folder/management group.
- Principal type.
- Authentication method.
- Credential type.
- Region.
- Resource tags.
- Public exposure status.
- Guardrail compliance status.
- Change ticket or deployment pipeline ID.

Cloud detection must distinguish human users, workload identities, automation, managed services, and external identities.

## 30. Web and API Detection Engineering at High Level

Web and API detection must focus on authentication, authorization, sensitive-object access, abuse patterns, resource consumption, error signals, and business-flow misuse.

High-value telemetry categories:

- Web server access logs.
- Application security logs.
- API gateway logs.
- Authentication and authorization decision logs.
- WAF logs.
- Rate-limit outcomes.
- Input validation failures.
- Error logs.
- Business transaction logs.
- Database access logs where appropriate.
- Distributed traces for critical flows.

High-level detection themes:

```text
Authentication abuse:
  credential stuffing, password spraying, MFA fatigue, token misuse.

Authorization failure:
  repeated denied access to objects, cross-tenant access attempts, role mismatch.

Object abuse:
  sequential object access, unusual object volume, sensitive object reads.

Business-flow abuse:
  impossible or high-frequency workflow execution, automation against protected flows.

Injection/SSRF suspicion:
  validation errors, blocked payload classes, unusual outbound requests from application tier.

API resource abuse:
  rate-limit violations, query complexity spikes, large payload attempts.

Misconfiguration signal:
  debug endpoints, verbose errors, unexpected admin paths.
```

Web/API detections need application context:

- User ID.
- Tenant ID.
- Role/scopes/claims.
- Endpoint/operation.
- Object ID and object owner.
- Authorization outcome.
- Request ID/correlation ID.
- Source IP and device context.
- Rate-limit result.
- Response status and size.

A web/API alert should not rely only on URI strings. It should map request behavior to business objects, authorization decisions, and user/tenant context where possible.

## 31. Common Detection-Engineering Failures

1. **Collecting logs without designing detections.**
   - Storage grows while security value stays low.

2. **Writing rules before validating telemetry.**
   - Logic assumes fields that are absent or unreliable.

3. **No normalized schema.**
   - Rules become vendor-specific and correlation breaks.

4. **No asset or identity enrichment.**
   - Analysts cannot judge impact or priority.

5. **Overusing low-confidence alerts.**
   - SOC queues become noise sinks.

6. **No positive and negative testing.**
   - Teams do not know whether rules work.

7. **Treating ATT&CK mapping as proof.**
   - Coverage claims become slideware.

8. **No owner or review cadence.**
   - Rules decay silently.

9. **Permanent suppressions.**
   - Suppression becomes a blind spot.

10. **Ignoring telemetry pipeline health.**
    - Missing logs look like a quiet environment.

11. **No response handoff.**
    - Alerts do not lead to decisions.

12. **No drift detection.**
    - Environment changes invalidate rule assumptions.

13. **Only detecting known bad indicators.**
    - Threat behavior changes faster than indicator lists.

14. **No critical-asset focus.**
    - Detection energy is wasted on low-risk surfaces.

15. **Treating detection as a SOC-only problem.**
    - Engineering, identity, cloud, network, application, and asset teams all own telemetry quality.

## 32. Common Mistakes

- Assuming the SIEM automatically creates security value.
- Believing more logs always mean better detection.
- Building detections without an owner.
- Building detections without test cases.
- Alerting on every anomaly at high severity.
- Failing to distinguish severity from confidence.
- Suppressing alerts without expiry.
- Ignoring log latency.
- Ignoring source silence.
- Ignoring parser failures.
- Ignoring field completeness.
- Mapping to ATT&CK too broadly.
- Not preserving raw event references.
- Not documenting false-positive causes.
- Not linking detections to response playbooks.
- Not measuring analyst workload.
- Not reviewing detections after architecture changes.
- Not requiring telemetry during system design.
- Treating application logs as optional.
- Treating cloud audit logs as only compliance evidence.
- Treating packet capture as a substitute for endpoint or identity telemetry.

## 33. Must-Memorize Facts

1. Detection engineering starts with an observable hypothesis, not a query.
2. Telemetry design determines what detection is possible.
3. A detection rule without required fields is fragile.
4. A detection without tests is unproven.
5. A detection without an owner will decay.
6. ATT&CK mapping is taxonomy, not proof of coverage.
7. Severity and confidence are different.
8. Precision measures alert correctness; recall measures missed real cases.
9. False negatives are often harder to see than false positives.
10. Source silence is a detection event.
11. Parser failures are security failures.
12. Normalization enables cross-source correlation.
13. Enrichment converts alerts into decisions.
14. Asset criticality changes alert priority.
15. Identity context is essential for modern detection.
16. Network telemetry needs attribution to be useful.
17. Cloud detection must prioritize control-plane activity.
18. Web/API detection must include business-object and authorization context where possible.
19. Suppressions must have owner, reason, and expiry.
20. Detection coverage must be validated, not assumed.
21. Detection-as-code improves review, testing, rollback, and auditability.
22. Telemetry integrity matters because attackers may tamper with logs.
23. Response handoff requires raw evidence and normalized context.
24. Rules must be reviewed after environment, parser, or data-source changes.
25. Mature detection programs measure data health, rule health, coverage, and analyst impact.

## 34. Interview / Exam Points

### Explain detection engineering in one sentence.

Detection engineering is the lifecycle of designing, validating, tuning, and maintaining detection logic over trusted telemetry so security-relevant behavior produces actionable alerts and evidence.

### What is the difference between monitoring and detection?

Monitoring observes system state and events. Detection applies security logic to identify suspicious behavior, control failures, or policy violations.

### What is the difference between detection and threat hunting?

Detection is prebuilt logic that continuously evaluates telemetry. Threat hunting is hypothesis-driven investigation, often used to find unknown activity and create new detections.

### What makes telemetry high quality?

High-quality telemetry is accurate, timely, complete, normalized, attributable, retained, protected, and enriched with asset, identity, and business context.

### Why is normalization important?

Normalization maps vendor-specific fields into a stable schema so detections and correlations can work across sources.

### Why is enrichment important?

Enrichment adds asset, identity, network, cloud, threat, and change context so analysts can decide severity, priority, and response.

### Why is ATT&CK mapping useful but insufficient?

ATT&CK provides behavior vocabulary and coverage structure, but it does not prove telemetry exists, fields are complete, detections work, or alerts are actionable.

### What are common detection logic types?

Conditions, thresholds, sequences, correlations, baselines, aggregations, stateful logic, signatures, behavioral rules, statistical rules, anomaly detection, graph/state rules, and ML-assisted analytics.

### What should every production detection include?

Objective, hypothesis, data sources, required fields, logic, expected baseline, severity/confidence model, false-positive guidance, triage questions, test cases, owner, review cadence, and evidence output.

### What is detection drift?

Detection drift occurs when environment, telemetry, enrichment, or threat behavior changes and the detection no longer works as intended.

### What is a telemetry gap?

A telemetry gap is a missing source, missing field, parser failure, incomplete coverage, or pipeline weakness that prevents detection, attribution, investigation, or response.

### What is the right response to noisy detections?

Classify false-positive causes, improve context, refine logic, add suppressions with expiry, adjust severity routing, or move weak signals to hunting instead of deleting the detection blindly.

## 35. Expert-Level Insights

1. **The best detections often come from architecture contracts.**
   - “This path should never exist” is usually higher-confidence than generic suspicious behavior.

2. **Control-plane events are often more important than data-plane noise.**
   - IAM changes, logging disablement, backup deletion, GPO edits, and firewall rule changes are high-value signals.

3. **Detection engineering is data engineering plus security reasoning.**
   - Parsing, schema design, latency, loss, retention, and enrichment are as important as rule syntax.

4. **A detection should declare what it proves and what it does not prove.**
   - This prevents both underreaction and overreaction.

5. **Suppression is a risk decision.**
   - Every suppression hides some signal and must be governed like an exception.

6. **Coverage is multi-dimensional.**
   - Rule existence, log-source onboarding, field completeness, enrichment, validation, and analyst workflow must all exist.

7. **High-confidence contract violations are SOC force multipliers.**
   - Admin account used outside PAW, user zone reaching management plane, public storage created in production, or logging disabled are examples of architecture-aware detections.

8. **Every incident should improve detections.**
   - Lessons learned should produce new rules, better telemetry, improved enrichment, or retired false assumptions.

9. **Detection without response path creates anxiety, not security.**
   - Alerts must have triage, escalation, containment, and evidence paths.

10. **Telemetry must be designed before incidents.**
    - You cannot retroactively log events that were never collected.

11. **Cloud and SaaS detection require identity and API thinking.**
    - The most important events are often API calls, role changes, consent grants, key creation, and policy edits.

12. **Application telemetry is where business abuse becomes visible.**
    - Infrastructure logs cannot always show unauthorized object access or workflow abuse.

13. **Detection-as-code is not only automation.**
    - It is governance: peer review, tests, versioning, rollback, audit trail, and reproducibility.

14. **A quiet SOC may mean a healthy environment or a broken telemetry pipeline.**
    - Source silence and volume anomalies must be monitored.

15. **Good detection programs measure the cost of alerting.**
    - Analyst time is a scarce resource. Low-value alerts consume defense capacity.

## 36. Internal References to Future CKV Files

This file owns detection-engineering and telemetry-design methodology. The following CKV files own adjacent or deeper topics.

- **CKV-001 — Security Engineering Role and Operating Model**  
  Owns the security-engineering role, operating model, ownership expectations, cross-team coordination, and how detection engineering fits into security operations.

- **CKV-002 — Security Principles and Secure-by-Design Thinking**  
  Owns defense-in-depth, least privilege, secure defaults, complete mediation, fail-safe behavior, and design principles that detection logic often validates.

- **CKV-003 — Risk Management and Security Governance**  
  Owns risk framing, governance, control ownership, residual risk, and executive decision logic that prioritize detection coverage.

- **CKV-004 — Asset Management and Attack Surface Inventory**  
  Owns asset inventory, criticality, ownership, exposure mapping, and asset-to-telemetry relationships required for detection enrichment and coverage.

- **CKV-005 — Change Management and Security Exceptions**  
  Owns approved change windows, exception expiry, drift, rollback, and change evidence used to tune alerts and distinguish authorized activity from suspicious change.

- **CKV-006 — Business Continuity, Disaster Recovery, and Resilience**  
  Owns continuity and recovery requirements that affect detection priorities for ransomware, backup tampering, restore readiness, and crisis signals.

- **CKV-010 — Networking Fundamentals and Encapsulation**  
  Owns network communication foundations needed to understand source, destination, protocol, and traffic-flow evidence.

- **CKV-014 — TCP, UDP, Ports, and Transport Troubleshooting**  
  Owns transport behavior, ports, sockets, sessions, resets, timeouts, and flow reasoning used in network detection logic.

- **CKV-015 — DNS Architecture, Resolution, Attacks, and Defense**  
  Owns DNS architecture, DNS abuse, DNS telemetry, cache behavior, filtering, RPZ, sinkholing, and DNS troubleshooting.

- **CKV-017 — Network Design, Segmentation, DMZs, and Hard Controls**  
  Owns segmentation, zones, conduits, trust boundaries, choke points, allowed paths, and network architecture contracts that create high-confidence detections.

- **CKV-018 — Network Protocol Capture, Structures, and Analysis**  
  Owns packet capture methodology, protocol dissection, PCAP evidence, capture placement, and deep packet/header analysis.

- **CKV-020 — Windows Fundamentals for Security**  
  Owns Windows OS fundamentals, event logs, audit policy, PowerShell relevance, Windows services overview, and baseline Windows investigation concepts.

- **CKV-024 — Windows Registry, Services, Scheduled Tasks, and Persistence Surfaces**  
  Owns Windows registry, services, scheduled tasks, WMI surfaces, persistence locations, and change/drift surfaces that generate high-value endpoint detections.

- **CKV-025 — Windows Security Stack: Updates, Defender, Firewall, SmartScreen, BitLocker, TPM, VSS**  
  Owns Windows Defender, Firewall, updates, ASR, SmartScreen, BitLocker, TPM, VSS, and security-stack state that detection engineering monitors for control failure.

- **CKV-026 — Linux Fundamentals and Hardening for Security**  
  Owns Linux users, sudo, processes, systemd, cron, logs, auditd, SSH, host firewall, and Linux hardening surfaces that generate Linux detections.

- **CKV-030 — Active Directory Fundamentals**  
  Owns AD objects, domains, domain controllers, OUs, groups, trusts, SYSVOL, DC locator, and AD administrative model that detection engineering monitors.

- **CKV-031 — Kerberos Authentication, PAC, Tickets, and Windows Logon**  
  Owns Kerberos tickets, PAC, AS/TGS/AP flows, time dependency, Windows logon relationship, and Kerberos troubleshooting concepts used in identity detections.

- **CKV-032 — NTLM, Netlogon, Relay Risk, and Authentication Hardening**  
  Owns NTLM, Netlogon, relay exposure, pass-through authentication, auditing, restriction, and hardening concepts used in authentication detections.

- **CKV-033 — LDAP, LDAPS, Signing, Channel Binding, and Directory Access**  
  Owns LDAP access, binds, searches, signing, channel binding, LDAPS trust, directory access control, LDAP telemetry, and directory enumeration risk.

- **CKV-034 — Group Policy Internals and Security**  
  Owns GPO architecture, GPC/GPT, SYSVOL, processing, security filtering, delegation, GPO risks, and GPO change/drift evidence.

- **CKV-035 — AD Delegation: Unconstrained, Constrained, and RBCD**  
  Owns Kerberos delegation models, S4U relationships, delegation attributes, protected users, delegation inventory, and delegation hardening.

- **CKV-036 — Active Directory Attack Paths and Defensive Monitoring**  
  Owns AD attack-path reasoning, Tier 0 exposure, privilege graph concepts, AD telemetry sources, and AD-specific defensive monitoring logic.

- **CKV-037 — AD CS and PKI Security**  
  Owns AD CS, certificate templates, enrollment, PKINIT relationship, certificate mapping, CA administration, AD CS monitoring, and PKI exposure patterns.

- **CKV-040 — HTTP, Web Fundamentals, Sessions, and Cookies**  
  Owns HTTP, sessions, cookies, browser/server behavior, caching, redirects, origins, CORS, and web traffic reasoning needed before web detections.

- **CKV-041 — OWASP Web Top 10 Canonical Security Model**  
  Owns OWASP Web Top 10 taxonomy, web vulnerability categories, root causes, and conceptual controls that inform web detection use cases.

- **CKV-042 — OWASP API Security Top 10 Canonical Model**  
  Owns OWASP API Top 10 taxonomy, API-specific risk categories, API object/property/function/business-flow framing, and API abuse categories.

- **CKV-043 — DevSecOps, Secure SDLC, SAST, DAST, SCA, and Security Gates**  
  Owns secure SDLC, scanner outputs, pipeline evidence, release gates, SBOM, SCA, SAST, DAST, and vulnerability intake from software pipelines.

- **CKV-044 — API Security Controls: Authentication, Authorization, Schema, Rate Limits**  
  Owns API authentication, authorization, schema validation, rate limits, gateways, DTOs, response hygiene, and API logging expectations at control level.

- **CKV-050 — Cloud Fundamentals: IaaS, PaaS, SaaS, Compute, Storage, IAM**  
  Owns cloud operating concepts, accounts, regions, compute, storage, IAM basics, logging basics, billing, quotas, and cloud troubleshooting foundations.

- **CKV-051 — Cloud Security Architecture and Hard Controls**  
  Owns cloud guardrails, IAM hard controls, logging architecture, KMS, immutable backups, storage controls, network hard controls, metadata protections, and cloud validation evidence.

- **CKV-061 — Incident Response Lifecycle and Playbook Design**  
  Owns incident response lifecycle, triage workflow, containment, eradication, recovery, playbooks, escalation, and lessons learned after detection output.

- **CKV-062 — Threat Hunting Methodology**  
  Owns hunting hypotheses, hunt planning, weak-signal investigation, IOC/TTP handling, hunt reporting, and conversion of hunt findings into detections.

- **CKV-063 — Digital Forensics and Evidence Handling**  
  Owns forensic evidence handling, chain of custody, timelines, volatile data, disk/memory artifacts, preservation, and forensic investigation workflow.

- **CKV-064 — SOAR, Automation, Validation, and Provability Outputs**  
  Owns automation workflows, approval gates, response validation, evidence packages, proof outputs, ticketing, notifications, and provable automated outcomes.

- **CKV-065 — Security Monitoring Tools and Lab Architecture**  
  Owns SIEM/SOAR/EDR/NDR/lab tool roles, Security Onion/Wazuh/Splunk/Zeek/Suricata-style architecture, telemetry pipelines, and lab verification workflow.

- **CKV-080 — Malware, APT Lifecycle, Botnets, and Advanced Threat Controls**  
  Owns malware behavior, APT lifecycle, botnets, command-and-control, persistence context, advanced threat controls, and malware-related detection relationships.

- **CKV-081 — Firewalls, WAFs, IDS/IPS, and Network Security Controls**  
  Owns firewall, WAF, IDS/IPS, proxy, inspection, tuning, network security controls, and control coverage validation beyond detection methodology.

- **CKV-082 — Vulnerability Management, Scanning, Prioritization, and Remediation**  
  Owns vulnerability scanning, prioritization, remediation, verification, exposure management, and how vulnerability findings feed detection priorities.

- **CKV-090 — Command-Line and Built-in Administration Tools for Security Work**  
  Owns command-line/admin tooling, built-in OS utilities, security investigation commands, and safe administrative command interpretation used during triage and validation.

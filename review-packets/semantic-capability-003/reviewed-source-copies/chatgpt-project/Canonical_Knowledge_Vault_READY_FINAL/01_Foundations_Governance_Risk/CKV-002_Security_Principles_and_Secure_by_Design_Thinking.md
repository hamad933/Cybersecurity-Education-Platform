# CKV-002 — Security Principles and Secure-by-Design Thinking

## 1. Purpose

Security principles are the stable decision rules that keep security engineering from becoming tool-first, reactive, inconsistent, or vendor-driven. They define how a system should be designed, built, operated, tested, and changed so that security is part of the structure of the system rather than a late add-on.

This file owns the canonical foundation for secure-by-design thinking. Later CKV files will expand risk management, asset inventory, change management, incident response, business continuity, network architecture, identity architecture, and software security. This file intentionally keeps those topics as references and focuses on the principles that constrain them.

A security engineer should use this file to answer:

- What does “secure design” actually mean?
- Which principles should guide security decisions before tools are selected?
- How do principles become architecture, controls, tests, evidence, and operations?
- How do business needs and security principles coexist without turning security into a blocker?

## 2. Core Definition

**Security principles** are reusable design rules that convert security objectives into consistent engineering decisions.

**Secure-by-design** means that security requirements, trust assumptions, failure behavior, access boundaries, abuse cases, logging needs, and verification criteria are built into the system from the beginning instead of being bolted on after deployment.

A secure-by-design system is not simply a system with security tools attached. It is a system whose normal structure makes unsafe behavior harder, unauthorized access constrained, failure safer, misuse visible, and control effectiveness testable.

The canonical model is:

```text
Business objective
  -> security objective
  -> security principle
  -> control objective
  -> architecture pattern
  -> implementation mechanism
  -> test and evidence
  -> continuous assurance
```

Security principles are not slogans. A principle is useful only when it changes a design decision.

Example:

```text
Principle: Least privilege
Bad interpretation: Give users fewer permissions.
Engineering interpretation: Every identity, process, service, workload, API token, firewall rule, and admin role receives only the permissions required for a defined purpose, for a defined scope, for a defined duration, with review and revocation.
```

## 3. Why Security Principles Exist

Security principles exist because systems fail in predictable ways:

- People make mistakes.
- Software contains defects.
- Credentials leak.
- Assumptions become outdated.
- Business pressure creates exceptions.
- Architecture changes over time.
- Attackers use legitimate paths, not only exploits.
- Controls drift from their original intent.

Without principles, security becomes a checklist of isolated controls. With principles, controls have a reason, a boundary, an owner, and a test.

Security principles solve five major problems.

### 3.1 They create consistency

Different teams may own identity, endpoints, networks, cloud, software, OT, and monitoring. Principles give all teams a shared design language. “Deny by default,” “least privilege,” and “complete mediation” mean the same thing whether the control is a firewall, an API gateway, a Kubernetes admission controller, an AD ACL, or a SaaS permission model.

### 3.2 They prevent late security patches

Security added late is usually incomplete because the system’s trust boundaries, data flows, roles, and failure modes are already embedded. Secure-by-design forces threat modeling, boundary decisions, access model decisions, logging design, and failure behavior before production.

### 3.3 They reduce avoidable complexity

Complex security mechanisms fail because humans cannot reason about them, administrators misconfigure them, and developers bypass them. Principles such as economy of mechanism, secure defaults, and centralized security services reduce the number of places where security can silently break.

### 3.4 They make security business-aligned

Security exists to protect mission outcomes, not to block the business. A principle-based decision can explain why a control exists, what risk it reduces, what business objective it supports, and what evidence proves it works.

### 3.5 They support assurance

A principle must be testable. If a system claims “least privilege,” there should be access reviews, role definitions, entitlement diffs, service-account scopes, and failed-access evidence. If a network claims “default deny,” there should be deny tests, rule owners, hit counts, and boundary logs.

## 4. Core Security Principles

### 4.1 Confidentiality, Integrity, and Availability as objectives

The CIA triad is not a complete design method, but it is the baseline language for what security protects.

- **Confidentiality:** prevent unauthorized disclosure.
- **Integrity:** prevent unauthorized or improper modification.
- **Availability:** keep required services and data usable when needed.

Secure design begins by deciding which objective dominates for a given system. A hospital patient-record system, an industrial control system, a financial posting system, and a public website do not have the same priority order.

Design implication:

```text
If confidentiality dominates -> strict access, encryption, data minimization, leakage monitoring.
If integrity dominates -> controlled workflows, separation of duties, transaction validation, audit trails.
If availability dominates -> redundancy, graceful degradation, capacity, DDoS resilience, recovery design.
```

### 4.2 Least privilege

Least privilege means every subject receives only the minimum authority required to perform an approved function.

Subjects include:

- users
- administrators
- service accounts
- workloads
- API tokens
- processes
- containers
- devices
- network flows
- automation pipelines
- third-party integrations

Least privilege must include **scope**, **time**, **purpose**, and **review**.

```text
Bad: App service account can read all databases.
Better: App service account can read only required tables in one database.
Best: App workload identity has scoped access, short-lived credentials, monitored use, rotation, and owner review.
```

Least privilege fails when permissions accumulate through role creep, nested groups, shared accounts, emergency exceptions, broad cloud IAM policies, or permanent administrative roles.

Operational rule:

```text
No privilege should exist without owner, purpose, scope, duration, approval path, and evidence of review.
```

### 4.3 Need-to-know

Need-to-know is the data-focused companion to least privilege. Least privilege asks: “What action can this subject perform?” Need-to-know asks: “Does this subject have a legitimate reason to access this information?”

A user may be authenticated and still not have a need-to-know for a dataset. Authentication proves identity; authorization and need-to-know prove legitimate purpose.

Design implication:

- separate identity from authorization
- classify data
- bind access to job function
- log sensitive access
- periodically review entitlement-to-purpose mapping

### 4.4 Defense in depth

Defense in depth means using multiple independent or semi-independent controls so that one failed control does not collapse the system.

A mature layered design includes:

```text
Prevent -> Detect -> Respond -> Recover
Identity -> Endpoint -> Network -> Application -> Data -> Monitoring -> Recovery
```

Defense in depth does not mean stacking random tools. Layers must cover different failure modes.

Weak layering:

```text
Firewall + another firewall + another firewall
```

Stronger layering:

```text
MFA + least privilege + segmentation + secure configuration + logging + anomaly detection + tested recovery
```

Design rule:

```text
Every critical path should have at least one preventive control, one detective signal, one response action, and one recovery/rollback path.
```

### 4.5 Separation of duties

Separation of duties prevents one person or process from controlling all parts of a sensitive transaction. It reduces fraud, abuse, mistake impact, and unchecked administrative power.

Examples:

- one person requests a payment, another approves it
- one engineer proposes a firewall rule, another approves it
- developers cannot directly deploy to production without pipeline controls
- privileged access requires approval plus logging
- certificate issuance requires separate requester and approver roles

Separation of duties is not only an HR control. It is an architecture principle for financial systems, identity systems, CI/CD pipelines, change management, cloud administration, and production access.

### 4.6 Separation of privilege

Separation of privilege requires multiple independent conditions before a sensitive action is allowed.

Examples:

- admin role + phishing-resistant MFA
- production deployment approval + signed artifact
- privileged command + session recording
- firewall rule change + ticket + peer approval
- key export attempt + HSM policy + quorum approval

Separation of privilege is stronger than simple role checks because it avoids relying on one control or one authority.

### 4.7 Fail-safe defaults and fail securely

Fail-safe defaults mean access is denied unless explicitly allowed. Fail securely means that when something breaks, the failure mode does not silently create unauthorized access.

Examples:

- firewall failure should not create an unrestricted path
- authorization service failure should not allow all requests
- policy engine timeout should not bypass critical checks
- failed identity lookup should not map to an anonymous high-privilege state
- logging pipeline failure should trigger alerting, not silent blindness

Design rule:

```text
Unknown state = deny, degrade, isolate, or require human approval.
```

Fail-secure must be balanced with availability and safety. In OT, medical, aviation, and life-safety systems, “fail closed” can be dangerous if it stops a critical physical process. The correct rule is not always “block everything.” The correct rule is: define the safest failure state for the mission and document why.

### 4.8 Secure defaults

Secure defaults mean the system is safe before customization.

Secure defaults include:

- default deny instead of default allow
- no default shared passwords
- no anonymous access unless explicitly justified
- encryption enabled by default
- strong logging enabled by default
- public exposure disabled by default
- least privilege roles by default
- safe session timeout defaults
- secure cookie and token settings by default
- hardened baselines before production

Secure defaults reduce dependence on perfect administrators and perfect deployment scripts.

### 4.9 Complete mediation

Complete mediation means every access request is checked by an enforcement mechanism before access is granted.

This principle is associated with the reference monitor concept:

```text
Subject -> request -> enforcement point -> authorization decision -> object
```

A correct mediation mechanism should be:

- always invoked
- tamper resistant
- small/simple enough to verify
- consistent across all access paths
- logged where accountability matters

Common failures:

- API endpoint checks authorization in one route but not another
- cached access decisions outlive the intended session or scope
- backend trusts the frontend to hide unauthorized functions
- service-to-service calls bypass user authorization
- admin endpoints are exposed on a separate path with weaker controls

Engineering rule:

```text
Every path to the object must pass the same authorization policy, not just the most common path.
```

### 4.10 Economy of mechanism

Economy of mechanism means security mechanisms should be as simple and small as possible while still meeting requirements.

Complexity is a security liability because it creates:

- misconfiguration
- hidden dependencies
- inconsistent enforcement
- unreviewable code paths
- privilege sprawl
- operational confusion
- fragile recovery

Practical applications:

- centralize authorization logic instead of duplicating it across services
- minimize the trusted computing base
- keep privileged components small
- prefer clear role models over hundreds of special cases
- remove unused services and exposed interfaces
- avoid unnecessary transitive trust relationships

### 4.11 Least common mechanism

Least common mechanism means users, tenants, processes, or security domains should share as few mechanisms as possible when sharing creates leakage or influence risk.

Examples:

- avoid shared admin accounts
- isolate tenant data paths
- avoid shared secrets across environments
- separate production and development credentials
- separate management and user planes
- isolate build runners that handle secrets
- avoid reusing one service account across unrelated services

This principle reduces blast radius and covert influence through shared resources.

### 4.12 Open design

Open design means security should not depend on hiding the design. Secrets must be secret, but algorithms, architecture, and control logic should remain secure even when understood by attackers.

Examples:

- encryption should rely on secret keys, not secret algorithms
- authorization should not depend on hidden URLs
- admin security should not rely on “nobody knows this endpoint exists”
- network security should not rely only on undocumented firewall rules

Open design supports review, audit, peer validation, and long-term maintainability.

### 4.13 Psychological acceptability and usable security

Security controls must be usable enough that legitimate users can follow them under real working conditions.

A control that is too painful creates bypass behavior:

- passwords written down
- shared admin accounts
- shadow IT
- unapproved SaaS use
- disabling endpoint controls
- permanent emergency exceptions

Usable security does not mean weak security. It means strong security with workable paths: SSO, phishing-resistant MFA, password managers, JIT access, clear approval workflows, self-service recovery with verification, and well-designed admin workstations.

### 4.14 Attack surface minimization

Attack surface minimization reduces the number of reachable functions, services, dependencies, identities, and paths an attacker can use.

Examples:

- disable unnecessary services
- remove unused accounts and keys
- close unneeded ports
- limit exposed APIs
- minimize installed software
- restrict egress paths
- remove legacy protocols
- reduce admin interfaces reachable from user zones

Attack surface minimization is not just hardening. It is design pressure against unnecessary reachability and unnecessary privilege.

### 4.15 Domain separation and isolation

Domain separation groups subjects, objects, and systems by different trust assumptions and applies separate controls to each domain.

Examples:

- user zone vs server zone
- development vs production
- tenant A vs tenant B
- IT vs OT
- identity plane vs data plane
- management plane vs user plane
- privileged admin environment vs normal workstation environment

Isolation can be physical, logical, cryptographic, administrative, or procedural. The security value depends on enforcement and monitoring, not on diagrams.

### 4.16 Accountability and auditability

Accountability means security-relevant actions can be attributed to the responsible subject. Auditability means evidence exists to verify that controls are working and to investigate when they fail.

Design implications:

- avoid shared accounts
- log privileged actions
- protect logs from tampering
- synchronize time
- preserve identity context across systems
- include correlation identifiers in distributed systems
- require command/session accounting for administrative paths

No accountability means no trustworthy investigation, no reliable deterrence, and weak assurance.

### 4.17 Privacy by design

Privacy by design means privacy expectations are built into collection, processing, storage, sharing, retention, and deletion decisions.

Core design behaviors:

- collect only necessary data
- define purpose before collection
- minimize sensitive fields
- restrict secondary use
- protect data in all states
- define retention and deletion rules
- apply masking/tokenization where appropriate
- make privacy controls auditable

Privacy is not separate from security. Security protects systems and data; privacy constrains what data should exist, why it exists, who can use it, and how long it should remain.

### 4.18 Zero trust as a modern expression of older principles

Zero trust is not a product category. It is a design stance:

```text
No implicit trust based on network location, device ownership, or historical access.
```

Zero trust applies older principles in modern environments:

- least privilege
- complete mediation
- continuous verification
- explicit trust boundaries
- strong identity
- device posture checks
- segmentation
- logging and analytics
- assume breach

A zero-trust design still needs architecture. Without identity quality, asset awareness, policy enforcement points, telemetry, and operational review, “zero trust” becomes branding.

## 5. Secure-by-Design Model

A secure-by-design model turns principles into a repeatable engineering pipeline.

### 5.1 Define business and mission attributes

Security begins with what the system must protect.

Examples:

- prevent unauthorized money movement
- protect identity issuance
- maintain plant safety
- preserve patient data confidentiality
- prevent production deployment tampering
- recover from ransomware without restoring compromised state

Security must be connected to business attributes, not only technical threats.

### 5.2 Identify security objectives

Translate business attributes into security objectives:

```text
Business attribute: prevent unauthorized money movement
Security objective: high transaction integrity, strong approval workflow, non-repudiation, auditability
```

```text
Business attribute: protect identity issuance
Security objective: Tier-0 isolation, privileged access control, key protection, monitoring of directory and IdP changes
```

### 5.3 Define trust boundaries early

A trust boundary exists where assumptions change. Secure design requires boundaries to be explicit before implementation.

Boundary examples:

- internet -> DMZ
- user device -> server
- application -> database
- workload -> control plane
- tenant -> tenant
- developer pipeline -> production deployment
- IT -> OT
- normal user -> administrator
- third party -> internal service

### 5.4 Select principles before controls

Do not start by asking “Which tool should we buy?” Ask:

- Where must we deny by default?
- Where is least privilege enforced?
- Where are trust boundaries crossed?
- Which component is the policy decision point?
- Which component is the policy enforcement point?
- What is the safest failure state?
- What evidence proves enforcement?
- What is the rollback path?

### 5.5 Design control objectives

A control objective is a testable statement of what must be true.

Weak control objective:

```text
Secure administrator access.
```

Strong control objective:

```text
Only approved privileged identities using phishing-resistant MFA from managed admin workstations can access Tier-0 management interfaces, and every session is logged with user, device, time, target, command/activity, and approval reference.
```

### 5.6 Map objectives to mechanisms

Mechanisms include:

- IAM policies
- firewall rules
- API gateways
- RBAC
- ABAC
- PAM/JIT access
- encryption
- secure boot
- hardened images
- Kubernetes admission policies
- CI/CD branch protections
- logging pipelines
- EDR policies
- backup immutability

A mechanism is valid only if it enforces the objective and produces usable evidence.

### 5.7 Define failure behavior

Every important dependency must have a failure decision.

Examples:

| Dependency failure | Unsafe behavior | Safer behavior |
|---|---|---|
| Authorization service unavailable | Allow all requests | Deny sensitive requests or enter approved degraded mode |
| Logging unavailable | Continue silently | Alert, queue locally, restrict high-risk changes |
| Policy engine timeout | Skip policy | Deny or require break-glass workflow |
| MFA unavailable | Disable MFA globally | Use monitored emergency access with short duration |
| Firewall HA failover | Bypass inspection | Preserve policy-equivalent routing and inspection |

### 5.8 Build assurance from the beginning

Secure-by-design includes proof design.

For every critical control, define:

- what must be logged
- where evidence is stored
- who owns review
- how often validation occurs
- what test proves allow behavior
- what test proves deny behavior
- what change forces re-review

A control that cannot be tested is a claim, not assurance.

## 6. How Security Principles Guide Engineering Decisions

Security principles should be used as decision filters.

| Engineering decision | Principle filter | Design question |
|---|---|---|
| Granting access | Least privilege / need-to-know | What exact action is required, for how long, on which object? |
| Creating a network path | Default deny / trust boundary | Why should this zone reach that zone, and what proves it? |
| Building an API | Complete mediation | Does every endpoint and method enforce the same authorization policy? |
| Selecting admin model | Separation of duties / accountability | Can one person make and hide a dangerous change? |
| Choosing architecture | Economy of mechanism | Can this be simpler without weakening security? |
| Handling failures | Fail securely | Does failure create access, blindness, corruption, or unsafe downtime? |
| Designing logs | Accountability / assurance | Which events prove control decisions and support investigation? |
| Storing data | Privacy by design / minimization | Do we need this data, and for how long? |
| Supporting operations | Psychological acceptability | Will real users follow this, or will they bypass it? |
| Cloud deployment | Shared responsibility / secure defaults | Which controls are provider-owned, customer-owned, or shared? |

The best engineering decisions are traceable:

```text
Requirement -> principle -> control objective -> implementation -> evidence -> review cadence
```

## 7. Trust Boundaries and Security Assumptions

A trust boundary is the point where security assumptions change.

A boundary exists when crossing it changes at least one of these:

- identity authority
- policy owner
- data classification
- administrative control
- device integrity expectation
- network exposure
- tenant ownership
- legal or compliance responsibility
- telemetry coverage
- failure impact
- blast radius

### 7.1 Trust boundary examples

```text
Browser -> web app
Untrusted input crosses into application logic.
```

```text
Application -> database
Application identity becomes database authority.
```

```text
User endpoint -> domain controller
Low-trust device attempts to reach Tier-0 identity infrastructure.
```

```text
CI/CD pipeline -> production
Build authority becomes production-change authority.
```

```text
IT network -> OT network
Business IT assumptions meet safety and availability constraints.
```

### 7.2 Security assumptions must be explicit

Bad assumption:

```text
It is internal, so it is trusted.
```

Better assumption:

```text
This source zone is allowed to reach this destination service only through this enforcement point, using this identity, for this business purpose, and the path is logged.
```

### 7.3 Boundary contract

Every important trust boundary should have a contract:

| Contract field | Meaning |
|---|---|
| Source | Originating user, device, workload, service, or zone |
| Destination | Target service, data store, API, management interface, or zone |
| Identity | Who or what is authenticated and authorized |
| Purpose | Business reason for the path |
| Allowed action | Protocol, method, command, transaction, or permission |
| Enforcement point | Firewall, proxy, API gateway, IAM, RBAC, admission controller, ACL |
| Decision rule | Allow/deny logic and conditions |
| Logging | Evidence required for allow and deny events |
| Owner | Business and technical owner |
| Review | Recertification cadence and change triggers |
| Failure mode | What happens if enforcement, identity, or logging fails |

Boundary contracts prevent diagrams from becoming false assurance.

## 8. Security Principles in Architecture

Security architecture is the structured design of trust, boundaries, control points, dependencies, and evidence.

### 8.1 Architecture must be traceable

A strong architecture decision can be defended using this chain:

```text
Business requirement
  -> security attribute
  -> risk scenario
  -> control objective
  -> architecture pattern
  -> implementation mechanism
  -> evidence
  -> recurring assurance test
```

If a control cannot be traced to a business or security objective, it may be unnecessary complexity.

### 8.2 Trusted Computing Base minimization

The Trusted Computing Base is the set of components that must work correctly for security policy to hold.

Typical TCB elements:

- CPU privilege model
- firmware and secure boot chain
- kernel or hypervisor
- identity provider
- authentication subsystem
- authorization engine
- policy decision and enforcement points
- cryptographic module and key store
- logging integrity mechanism
- management plane

Design rule:

```text
Shrink and protect the TCB.
```

Practical implications:

- reduce privileged code
- isolate management interfaces
- protect identity systems as Tier-0
- prefer short-lived credentials
- separate build authority from production authority
- harden and monitor policy engines
- make policy changes rare, approved, logged, and reversible

### 8.3 Reference monitor thinking

A reference monitor is the ideal enforcement mechanism that mediates access between subjects and objects. In real systems, the reference monitor idea appears as:

- OS access checks
- AD authorization
- API gateways
- IAM policy engines
- Kubernetes API server authorization and admission
- database permissions
- firewall policy engines
- service mesh policy

Architecture must identify where mediation actually occurs. A control that exists but is bypassable is not a true enforcement point.

### 8.4 Control planes require special protection

Modern compromise often targets control planes because control planes change reality.

Control planes include:

- identity provider admin plane
- domain controllers and GPO
- cloud IAM and organization policy
- firewall managers
- Kubernetes API server
- CI/CD platform
- hypervisor management
- backup management console
- PKI and certificate authority systems

Control-plane design must enforce:

- separate admin identities
- privileged access workstations or managed admin paths
- phishing-resistant MFA where possible
- just-in-time elevation
- strict logging
- change approval
- drift detection
- emergency access monitoring

### 8.5 Design patterns produced by principles

| Principle | Architecture pattern |
|---|---|
| Least privilege | RBAC/ABAC, JIT access, scoped tokens, segmented admin roles |
| Defense in depth | Multiple control layers across identity, endpoint, network, app, data, recovery |
| Complete mediation | Centralized authorization, API gateway, reference monitor, policy enforcement points |
| Fail securely | Default deny, safe degraded mode, monitored break-glass |
| Economy of mechanism | Small TCB, narrow interfaces, centralized security services |
| Domain separation | Zones, tenants, namespaces, enclaves, admin tiers |
| Accountability | Per-user admin, audit logs, command accounting, protected log storage |
| Privacy by design | Data minimization, purpose limitation, retention limits, masking/tokenization |

## 9. Security Principles in Operations

Operations keep secure design true after deployment.

### 9.1 Controls must survive change

Security weakens when changes bypass the original design assumptions. Operational security must validate that principles remain true over time.

Operational checks:

- Are privileged roles still justified?
- Are firewall rules still owned and used?
- Are exceptions expired or recertified?
- Are logs still arriving?
- Are default-deny tests still passing?
- Are hardened baselines still applied?
- Are service accounts still scoped?
- Are emergency accounts still monitored?

### 9.2 Exceptions must remain controlled

Business needs may require exceptions, but exceptions must not destroy the principle.

An acceptable exception has:

- business justification
- owner
- expiration date
- compensating controls
- approval
- monitoring
- review cadence
- rollback plan

A permanent, unmonitored exception is not an exception; it is a new insecure design.

Detailed exception and change processes belong to future CKV files.

### 9.3 Evidence is part of operations

Operational security is not proven by intent. It is proven by evidence.

Examples:

| Claim | Evidence |
|---|---|
| Admin access is controlled | PAM logs, MFA logs, admin workstation logs, privileged session logs |
| Default deny exists | Firewall policy, deny test results, flow logs, rule reviews |
| Least privilege exists | Entitlement reviews, role definitions, group membership diffs |
| Secure configuration exists | Baseline scan results, configuration state, drift alerts |
| Complete mediation exists | API authorization test results, gateway logs, denied endpoint tests |
| Detection exists | Alert rules, event coverage map, test events, response records |

### 9.4 Human behavior is part of design

Humans are not outside the security system. They create, operate, approve, bypass, and attack systems. Operational design must assume:

- mistakes happen
- fatigue causes shortcuts
- confusing controls are bypassed
- unclear ownership creates gaps
- excessive friction creates shadow processes

Secure operations require usable workflows, clear ownership, training, monitoring, and well-designed escalation paths.

## 10. Security Principles in Software and DevSecOps

Secure software is built by applying security principles to code, pipelines, dependencies, deployment, and runtime behavior.

### 10.1 Threat modeling before implementation

Threat modeling identifies assets, actors, entry points, trust boundaries, data flows, abuse cases, and mitigations before the design becomes expensive to change.

Minimum threat-model questions:

- What are the critical assets?
- Who are the actors?
- Where does untrusted input enter?
- Where do trust boundaries exist?
- What can go wrong?
- Which principles reduce the risk?
- What must be logged?
- What must be tested?

### 10.2 Secure input handling

Input validation implements trust-boundary control.

Rules:

- treat external input as untrusted
- canonicalize before validation
- validate against strict allowlists or schemas
- use parameterized database queries
- encode output in the correct context
- perform authorization after identity is established and before sensitive action

### 10.3 Authorization must not rely on the frontend

The backend must enforce authorization. A UI can hide buttons, but it cannot be the control.

Common failure:

```text
Frontend hides admin function.
Backend accepts admin request anyway.
```

Secure pattern:

```text
Request -> authentication -> authorization -> business rule validation -> action -> audit log
```

### 10.4 Secure CI/CD

A pipeline is a production authority path. Secure-by-design DevSecOps applies principles to the pipeline itself.

Controls:

- branch protection
- code review
- signed commits or artifacts where required
- dependency scanning
- secret scanning
- SAST/DAST/IAST where useful
- infrastructure-as-code review
- isolated runners
- least-privilege deployment identities
- artifact provenance
- environment separation
- deployment approvals for high-risk systems
- rollback plan

### 10.5 Secure defaults in software

Applications should ship with safe behavior:

- authentication required for sensitive functions
- least-privilege default roles
- safe error messages
- secure session cookies
- strong password and MFA options
- logging enabled for critical actions
- debug mode disabled
- no sample credentials
- no default admin password
- no public admin interface by default

## 11. Security Principles in Identity and Access

Identity is the control plane for most modern security. If identity is compromised, many technical controls become irrelevant.

### 11.1 Authentication is not authorization

Authentication answers: “Who or what is this?”

Authorization answers: “What is this subject allowed to do in this context?”

Accounting answers: “What did this subject do, when, where, and against what object?”

Strong identity design needs all three.

### 11.2 Least privilege in IAM

IAM least privilege applies to:

- users
- groups
- roles
- service accounts
- workload identities
- OAuth scopes
- API keys
- cloud policies
- directory ACLs
- local rights
- privileged roles

High-risk permissions should be:

- separated
- justified
- time-bound
- monitored
- reviewed
- revocable

### 11.3 Privileged access must be a separate design

Administrator access is not normal user access with more permissions. It needs a separate architecture.

Design requirements:

- separate admin accounts
- no shared admin credentials
- phishing-resistant MFA where possible
- privileged access workstations or trusted admin paths
- JIT/JEA where possible
- session recording for high-risk administration
- alerting on privileged role changes
- protected break-glass accounts

### 11.4 Separation of duties in identity

Identity systems should prevent one actor from creating, approving, using, and hiding privileged access.

Examples:

- HR source controls joiner/mover/leaver triggers
- IAM team manages role definitions
- resource owner approves access
- security monitors high-risk grants
- audit reviews privileged changes

### 11.5 Trust boundaries in identity

Identity trust boundaries include:

- domain boundaries
- forest boundaries
- tenant boundaries
- federation boundaries
- third-party identity provider boundaries
- service-account delegation boundaries
- certificate trust boundaries

Trust must never mean blanket access. A trust relationship only allows a path for authentication or claims exchange; authorization must still be explicit and minimal.

## 12. Security Principles in Network Design

Secure network design applies principles to reachability.

### 12.1 Route does not mean allow

Routing makes communication possible. Security policy decides whether communication is allowed.

Secure design separates:

- reachability
- authorization
- inspection
- logging
- ownership
- review

### 12.2 Default deny between zones

Inter-zone communication should be denied unless explicitly allowed by a contract.

A strong network allow rule includes:

- source zone
- destination zone
- service/protocol
- direction
- identity or workload binding where possible
- business purpose
- owner
- expiry/review
- logging requirement
- test case

### 12.3 Segmentation must limit blast radius

Segmentation is valuable only when it stops unwanted movement.

Segmentation targets:

- user networks
- server tiers
- databases
- identity systems
- management interfaces
- backup environments
- development and production
- OT/ICS networks
- guest/BYOD networks
- cloud VPC/VNet boundaries
- Kubernetes namespaces and network policies

### 12.4 Management plane isolation

Management interfaces must not be reachable from ordinary user networks.

Examples:

- network device management
- hypervisor management
- cloud admin portals
- firewall managers
- backup consoles
- Kubernetes API server
- domain controllers
- CI/CD admin interfaces

Management access should use controlled admin paths, MFA, per-user accountability, logging, and explicit approval where risk requires.

### 12.5 Egress control is part of secure design

Ingress filtering protects entry. Egress control limits command-and-control, data exfiltration, malware staging, and unauthorized integrations.

Egress design should define:

- allowed destinations
- DNS resolver policy
- proxy requirements
- server outbound restrictions
- cloud metadata access restrictions
- logging and attribution
- exception process

### 12.6 Network telemetry is a design requirement

A secure network must prove what was allowed and denied.

Evidence sources:

- firewall logs
- proxy logs
- VPN/ZTNA logs
- DNS logs
- NetFlow/IPFIX
- IDS/IPS/NDR alerts
- NAC events
- DHCP and ARP security logs
- cloud flow logs
- Kubernetes network policy and audit logs

## 13. Security Principles in Incident Readiness

Incident readiness is not the full incident response lifecycle. In this file, it means designing systems so incidents can be detected, contained, investigated, and recovered from.

### 13.1 Design for visibility

If a system cannot generate useful evidence, it cannot be defended well.

Security-relevant events must be logged for:

- authentication
- authorization decisions
- privileged actions
- policy changes
- configuration changes
- sensitive data access
- failed access attempts
- admin sessions
- service-account use
- security control failures

### 13.2 Design for containment

Containment is easier when architecture already has boundaries.

Design for containment by:

- segmenting zones
- separating admin planes
- using scoped credentials
- avoiding shared secrets
- limiting outbound paths
- isolating workloads
- preserving emergency deny controls
- preparing rollback procedures

### 13.3 Design for evidence integrity

Incident evidence must be trustworthy.

Requirements:

- protected logs
- time synchronization
- immutable or write-protected storage for critical logs
- chain-of-custody process where needed
- correlation identifiers
- sufficient retention
- access controls around investigation data

### 13.4 Design for graceful degradation

Security should define safe degraded operation.

Examples:

- read-only mode when write integrity is uncertain
- isolate suspicious workload while preserving logs
- revoke session tokens after identity compromise
- disable risky integration while core service continues
- block nonessential outbound paths during containment

Full incident response, forensics, and recovery workflows belong to future CKV files.

## 14. Common Mistakes

1. **Treating security as a tool purchase**  
   Tools enforce decisions; they do not replace secure design.

2. **Using “internal network” as a trust decision**  
   Internal is a location, not a permission.

3. **Confusing authentication with authorization**  
   A valid identity is not automatically allowed to perform every action.

4. **Applying least privilege only to users**  
   Services, workloads, APIs, tokens, devices, and pipelines also need least privilege.

5. **Relying on the frontend for security**  
   Clients can be modified. Enforcement must happen on trusted server-side control points.

6. **Creating layered tools without layered failure coverage**  
   Defense in depth must cover different failure modes, not duplicate the same control.

7. **Allowing permanent exceptions**  
   Exceptions without expiration and monitoring become the real policy.

8. **Failing open without realizing it**  
   Availability pressure often creates unsafe fallback behavior.

9. **Ignoring management planes**  
   Attackers target systems that can change other systems.

10. **Not designing evidence**  
   If you cannot prove the control worked, you cannot rely on it during audit or incident response.

11. **Overcomplicating roles and rules**  
   Complex policy models become unreviewable and eventually insecure.

12. **Treating compliance as the goal**  
   Compliance can guide control selection, but secure design must protect real mission outcomes.

13. **Trusting shared accounts**  
   Shared accounts destroy accountability and make least privilege difficult.

14. **Assuming encryption fixes authorization**  
   Encryption protects data from disclosure in certain states; it does not decide who should access the data.

15. **Designing only for prevention**  
   Prevention fails. Detection, containment, and recovery must be designed too.

## 15. Must-Memorize Facts

- Security principles are decision rules, not slogans.
- Secure-by-design means security is part of requirements, architecture, implementation, testing, and operations.
- Least privilege applies to every subject: users, services, workloads, processes, devices, tokens, and administrators.
- Need-to-know is data-purpose authorization, not just identity verification.
- Defense in depth requires independent layers that cover different failure modes.
- Separation of duties prevents one actor from controlling all parts of a sensitive process.
- Separation of privilege requires multiple conditions before high-risk action.
- Fail-safe defaults mean deny unless explicitly allowed.
- Fail securely means failure does not silently create unauthorized access or blindness.
- Secure defaults reduce reliance on perfect configuration.
- Complete mediation means every access path is checked.
- Economy of mechanism means simpler mechanisms are easier to verify and operate.
- Least common mechanism reduces shared components that can leak or amplify risk.
- Open design means security should not depend on secrecy of architecture or algorithms.
- Psychological acceptability means security must be usable enough to avoid bypass.
- Attack surface minimization removes unnecessary exposure, services, privileges, and paths.
- Trust boundaries are where assumptions change.
- “Internal” is not a permission.
- Route does not mean allow.
- Control-plane and management-plane compromise can become enterprise compromise.
- A control without evidence is an assertion, not assurance.

## 16. Interview / Exam Points

### 16.1 High-value interview answers

**What does secure by design mean?**  
Security requirements and assumptions are built into the system from the start: trust boundaries, least privilege, secure defaults, fail-secure behavior, logging, threat modeling, and verification are design inputs, not post-deployment patches.

**What is least privilege?**  
Grant the minimum access required for a defined task, scope, and duration. It applies to people, processes, service accounts, APIs, cloud roles, and network paths. It must be reviewed to prevent privilege drift.

**What is defense in depth?**  
Multiple complementary controls protect against different failure modes so that one failed control does not collapse the system. It should include prevention, detection, response, and recovery.

**What is complete mediation?**  
Every access request to an object must be checked by an authorization mechanism. Bypasses, inconsistent endpoint checks, and stale cached decisions break complete mediation.

**What is fail secure?**  
When a component fails, it should not create unauthorized access, uncontrolled modification, or silent loss of visibility. The safest failure state depends on the mission.

**Why is usability a security principle?**  
Controls that are too hard to use create bypass behavior. Good security creates safe paths that real users and administrators can follow.

**How do trust boundaries affect design?**  
They identify where assumptions change and where enforcement, validation, logging, and stricter controls must exist.

### 16.2 CISSP-style distinctions

| Concept | Exam distinction |
|---|---|
| Least privilege | Minimum rights required to perform a job |
| Need-to-know | Legitimate business reason to access specific information |
| Separation of duties | Split sensitive process steps across different people/roles |
| Separation of privilege | Require multiple conditions/authorities for sensitive action |
| Defense in depth | Multiple layers of different controls |
| Fail-safe defaults | Default deny unless explicitly allowed |
| Complete mediation | Every access request checked |
| Economy of mechanism | Keep design simple and verifiable |
| Open design | Do not rely on secret design for security |
| Psychological acceptability | Security must be usable enough to be followed |
| Privacy by design | Minimize and govern personal data throughout lifecycle |
| Zero trust | No implicit trust; continuously verify access context |

### 16.3 Scenario answer pattern

For architecture scenarios, answer in this order:

```text
1. Business objective and CIA priority
2. Trust boundaries
3. Least-privilege access model
4. Default-deny / secure-default stance
5. Control points and enforcement
6. Failure modes and fail-secure behavior
7. Logging and evidence
8. Assurance tests and review cadence
```

## 17. Expert-Level Insights

### 17.1 Security is a property of relationships, not isolated components

A hardened server can still be part of an insecure system if it trusts the wrong identity provider, exposes a management interface, accepts broad service-account permissions, or sends logs nowhere. Security lives in dependencies, boundaries, flows, and authority relationships.

### 17.2 The most important assets are often authority systems

Crown jewels are not only databases. Identity providers, certificate authorities, CI/CD systems, backup controllers, cloud organization policies, firewall managers, and domain controllers are authority systems. They can change what other systems trust.

### 17.3 Secure design is mostly about controlling normal behavior

Attackers often use valid credentials, legitimate APIs, allowed network paths, trusted admin tools, and normal protocols. Secure design must constrain legitimate mechanisms so misuse is limited, visible, and reversible.

### 17.4 Least privilege without lifecycle is temporary

Access expands over time unless there is entitlement review, deprovisioning, role cleanup, group nesting control, service-account ownership, and exception expiration.

### 17.5 “Deny by default” must be operationally testable

A default-deny policy is not real unless teams can show deny tests, logs, rule ownership, hit-count review, and drift detection.

### 17.6 Security controls need friction budgets

Every control adds friction. Some friction is necessary; excessive friction creates bypass. Mature security engineering designs high assurance for high-risk paths and low-friction security for routine work.

### 17.7 Boundaries must align with ownership

A boundary is weak if no one owns it. Trust boundaries should have owners, enforcement points, telemetry, and review cadence.

### 17.8 Secure architecture must define what happens when controls fail

Most designs describe normal operation. Expert designs also describe control failure: identity outage, logging outage, policy engine outage, certificate expiry, backup failure, API gateway bypass, firewall failover, and emergency access.

### 17.9 Evidence is a design artifact

Logs, audit records, configuration snapshots, access reviews, and test results should be defined during design. Evidence added after incidents is usually incomplete.

### 17.10 Secure-by-design improves ROI

Security is cheaper and stronger when built early. Late security retrofits are expensive because they fight existing architecture, business workflows, user habits, and operational dependencies.

## 18. Internal References to Future CKV Files

This file owns the principles and secure-by-design thinking. The following future CKV files own detailed expansion areas. CKV IDs and topic meanings follow the approved `MASTER_INDEX_FIXES.md` generation map.

- **CKV-001 — Security Engineering Role and Operating Model**  
  Owns the security engineer role, operating responsibilities, cross-team coordination, and security as business enablement.

- **CKV-003 — Risk Management and Security Governance**  
  Owns risk identification, likelihood/impact reasoning, risk treatment, governance, risk appetite, and management decision workflows.

- **CKV-004 — Asset Management and Attack Surface Inventory**  
  Owns asset inventory, ownership, exposure, criticality, attack surface management, and asset lifecycle.

- **CKV-005 — Change Management and Security Exceptions**  
  Owns secure change workflow, exceptions, compensating controls, approvals, expiration, and drift management.

- **CKV-006 — Business Continuity, Disaster Recovery, and Resilience**  
  Owns BIA, RTO/RPO, backup strategy, restore testing, crisis management, continuity planning, and resilience/recovery concerns.

- **CKV-010 — Networking Fundamentals and Encapsulation**  
  Owns network layering, encapsulation, packet flow foundations, addressing context, and baseline network reasoning needed before security architecture topics.

- **CKV-017 — Network Design, Segmentation, DMZs, and Hard Controls**  
  Owns detailed network zones, segmentation, DMZ design, conduits, firewall policy placement, management-plane isolation, and hard network-control architecture.

- **CKV-020 — Windows Fundamentals for Security**  
  Owns Windows operating-system foundations required before NTFS, Windows access control, endpoint security, identity integration, and Windows telemetry topics.

- **CKV-022 — Windows Access Control Internals: Tokens, SIDs, ACLs, SRM**  
  Owns Windows authorization internals, access tokens, SIDs, ACLs, DACL/SACL semantics, requested access masks, and SRM/AccessCheck behavior.

- **CKV-030 — Active Directory Fundamentals**  
  Owns AD forests, domains, OUs, directory objects, basic domain services, administrative structure, and AD as an enterprise identity/security foundation.

- **CKV-040 — HTTP, Web Fundamentals, Sessions, and Cookies**  
  Owns HTTP/web foundations, request-response behavior, sessions, cookies, web state handling, and the protocol basis for later web and API security topics.

- **CKV-041 — OWASP Web Top 10 Canonical Security Model**  
  Owns web application vulnerability taxonomy, common web failure modes, and OWASP web-risk normalization.

- **CKV-042 — OWASP API Security Top 10 Canonical Model**  
  Owns API vulnerability taxonomy, API failure modes, and OWASP API-risk normalization.

- **CKV-043 — DevSecOps, Secure SDLC, SAST, DAST, SCA, and Security Gates**  
  Owns secure software lifecycle, pipeline security, SAST/DAST/SCA, dependency security, secrets handling in pipelines, and release security gates.

- **CKV-044 — API Security Controls: Authentication, Authorization, Schema, Rate Limits**  
  Owns reusable API control patterns such as object authorization, function authorization, schema validation, rate limits, and API authentication/authorization implementation patterns.

- **CKV-050 — Cloud Fundamentals: IaaS, PaaS, SaaS, Compute, Storage, IAM**  
  Owns cloud service models, compute, storage, IAM basics, and foundational cloud operating concepts.

- **CKV-051 — Cloud Security Architecture and Hard Controls**  
  Owns cloud security architecture, guardrails, segmentation equivalents, organization/account controls, cloud logging controls, and hard security boundaries in cloud environments.

- **CKV-060 — Detection Engineering and Telemetry Design**  
  Owns telemetry design, detection logic, signal quality, alert engineering, detection coverage, and evidence paths for monitoring.

- **CKV-061 — Incident Response Lifecycle and Playbook Design**  
  Owns incident lifecycle, playbooks, containment, eradication, recovery coordination, lessons learned, and response workflow design.

- **CKV-064 — SOAR, Automation, Validation, and Provability Outputs**  
  Owns security automation, response validation, evidence/proof outputs, automated workflow safety, and provable operational outcomes.

- **CKV-081 — Firewalls, WAFs, IDS/IPS, and Network Security Controls**  
  Owns network security control categories, firewall/WAF/IDS/IPS control behavior, and how these controls are selected, configured, monitored, and validated.

- **CKV-082 — Vulnerability Management, Scanning, Prioritization, and Remediation**  
  Owns vulnerability discovery, scanning, prioritization, remediation tracking, exception handling integration, and validation of vulnerability closure.

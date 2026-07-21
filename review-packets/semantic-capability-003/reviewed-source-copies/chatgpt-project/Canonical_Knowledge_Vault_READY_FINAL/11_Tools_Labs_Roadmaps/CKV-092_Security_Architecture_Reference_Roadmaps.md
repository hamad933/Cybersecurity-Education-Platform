# CKV-092 — Security Architecture Reference Roadmaps

## 1. Purpose

CKV-092 is the final roadmap layer of the Canonical Knowledge Vault. It does not teach the detailed content of the vault again. It organizes the CKV files into dependency-driven study paths, role paths, practice gates, interview preparation paths, and security architecture progression maps.

This file answers four questions:

1. Which CKV files must be mastered first?
2. Which CKV files belong together for a specific security role?
3. What readiness gate proves that a learner can move to the next layer?
4. How should the CKV be used as a long-term security architecture reference system?

CKV-092 is a navigation and mastery file. The technical truth remains inside the owning CKV topic files.

## 2. Core Definition

A security architecture reference roadmap is a structured dependency map that converts separate security topics into an ordered professional development system.

It is not a certification checklist, a tool list, or a course syllabus. It is a canonical routing layer across foundations, networking, operating systems, identity, application security, cloud, detection, response, forensics, automation, offensive-normalized defense, controls, tools, and lab design.

The roadmap model is:

```text
Foundations
  -> Network and OS reality
  -> Identity and application trust boundaries
  -> Cloud and modern infrastructure
  -> Detection, response, forensics, automation
  -> Threat-informed controls and validation
  -> Tools, labs, architecture, interviews, and role mastery
```

## 3. How to Use This Roadmap File

Use this file as the CKV navigation layer:

| Need | Use this file to choose | Then study |
|---|---|---|
| Build complete mastery | Foundation-to-expert master path | Sections 6–15 |
| Prepare for a role | Role-based roadmaps | Sections 16–30 |
| Review for CISSP-style breadth | CISSP-aligned path | Section 31 |
| Prepare for interviews | Interview roadmap | Section 32 |
| Practice safely | Hands-on roadmap and capstones | Sections 33–34 |
| Check readiness | Mastery and role indicators | Sections 35–36 |
| Find topic ownership | Final CKV reference map | Section 40 |

Usage rules:

- Do not skip foundations because later files assume them.
- Do not use offensive-normalized files as attack training; use them as defensive control, detection, and governance references.
- Do not use tool files as command cookbooks; use them for safe validation, evidence collection, and administration discipline.
- Use lab design before building any multi-system practice environment.
- Use the owning CKV file whenever technical detail is needed.

## 4. Roadmap Design Principles

| Principle | Meaning |
|---|---|
| Dependency before interest | Study prerequisites before advanced domains. |
| Breadth before specialization | A security architect must understand all major trust boundaries before specializing. |
| Defensive normalization | Attack concepts are converted into controls, detections, validation, and governance. |
| Evidence before claims | Operational maturity requires logs, proof, configuration state, and verification output. |
| Safe lab before practice | Practice belongs in isolated, authorized, documented environments. |
| Role paths are overlays | A role path reorders CKV files; it does not create new knowledge ownership. |
| Architecture over tools | Tools support architecture; they do not replace principles, threat models, and controls. |
| Current framework alignment | Use NIST CSF, CIS Controls, CISSP, OWASP, and MITRE ATT&CK as alignment anchors, not as substitutes for CKV topic ownership. |

## 5. Canonical CKV Dependency Model

The complete vault dependency model is layered:

```text
Layer 1 — Security foundations
  CKV-001 to CKV-006

Layer 2 — Network foundations and architecture
  CKV-010 to CKV-018

Layer 3 — Operating systems and endpoint security
  CKV-020 to CKV-026

Layer 4 — Identity and access security
  CKV-030 to CKV-037

Layer 5 — Application security and DevSecOps
  CKV-040 to CKV-044

Layer 6 — Cloud and cloud hard controls
  CKV-050 to CKV-051

Layer 7 — Detection, response, forensics, automation, monitoring, vulnerability management
  CKV-060 to CKV-065 and CKV-082

Layer 8 — Offensive-security defensive-normalized concepts
  CKV-070 to CKV-075, CKV-080, CKV-081

Layer 9 — Tools, labs, and roadmaps
  CKV-090 to CKV-092
```

Critical dependency gates:

| Gate | Required before | Reason |
|---|---|---|
| Foundations gate | All technical domains | Security decisions require risk, asset, exception, and resilience context. |
| Network gate | Cloud, monitoring, web, AD, controls | Most security telemetry and trust boundaries depend on network behavior. |
| OS gate | Identity, forensics, malware, endpoint control | Host state, permissions, processes, services, and logs are core evidence surfaces. |
| Identity gate | AD security, cloud IAM, web auth, incident response | Most enterprise compromise and control failures involve identity. |
| Application gate | API security, DevSecOps, WAF, cloud apps | Modern business risk often sits in HTTP, sessions, APIs, and CI/CD. |
| Cloud gate | Cloud security architecture and cloud IR | Shared responsibility and provider controls alter architecture decisions. |
| Detection/IR gate | Threat hunting, SOAR, forensics, red-team defensive value | Response maturity requires telemetry, process, evidence, and validation. |
| Lab/tool gate | Hands-on practice | Practice must be safe, reproducible, documented, and evidence-preserving. |

## 6. Foundation-to-Expert Master Path

| Phase | CKV files | Mastery outcome | Exit gate |
|---|---|---|---|
| 1 | CKV-001 to CKV-006 | Understand security engineering, principles, risk, assets, change, exceptions, BCDR. | Explain why a control exists, what asset it protects, what risk it reduces, and what exception process governs it. |
| 2 | CKV-010 to CKV-018 | Understand networks, addressing, L2/L3/L4, DNS/DHCP, segmentation, packet visibility. | Draw a secure segmented topology and explain packet path, control points, and telemetry sources. |
| 3 | CKV-020 to CKV-026 | Understand Windows/Linux fundamentals, access control, endpoint security, services, logs, hardening. | Validate host identity, permissions, running services, security state, and logs safely. |
| 4 | CKV-030 to CKV-037 | Understand AD, Kerberos, NTLM, LDAP, GPO, delegation, AD CS, monitoring. | Explain authentication, authorization, policy application, and identity attack-path control without using attack recipes. |
| 5 | CKV-040 to CKV-044 | Understand HTTP, web sessions, OWASP, APIs, DevSecOps, secure gates. | Review a web/API design for trust boundaries, session risk, access control, and pipeline controls. |
| 6 | CKV-050 to CKV-051 | Understand cloud service models, shared responsibility, IAM, storage, logging, hard controls. | Design a provider-neutral secure cloud landing-zone concept with IAM, logging, network, backup, and key controls. |
| 7 | CKV-060 to CKV-065, CKV-082 | Understand detection, IR, hunting, forensics, SOAR, monitoring tools, vulnerability management. | Convert a risk scenario into telemetry, detection, triage, evidence, response, verification, and reporting. |
| 8 | CKV-070 to CKV-075, CKV-080 to CKV-081 | Understand authorized testing, red-team value, attack concepts, controls, malware lifecycle, network controls. | Convert offensive concepts into authorization, prevention, detection, response, and validation requirements. |
| 9 | CKV-090 to CKV-092 | Operate safely with tools and labs; navigate the CKV as a reference system. | Build and document a safe lab, collect evidence, validate controls, and explain role readiness. |

## 7. Phase 1 — Security Foundations Roadmap

Study order:

1. CKV-001 — Security Engineering Role and Operating Model
2. CKV-002 — Security Principles and Secure-by-Design Thinking
3. CKV-003 — Risk Management and Security Governance
4. CKV-004 — Asset Management and Attack Surface Inventory
5. CKV-005 — Change Management and Security Exceptions
6. CKV-006 — Business Continuity, Disaster Recovery, and Resilience

Foundation outcomes:

| Outcome | Required CKV basis |
|---|---|
| Explain security as business-risk reduction | CKV-001, CKV-003 |
| Translate assets into control priorities | CKV-004 |
| Apply least privilege, defense in depth, secure defaults, fail secure | CKV-002 |
| Decide when an exception is acceptable | CKV-003, CKV-005 |
| Tie resilience to continuity and recovery | CKV-006 |

Foundation gate:

```text
Given a business system, identify assets, owners, risks, required controls, exception path,
change-management impact, resilience needs, and evidence needed for assurance.
```

## 8. Phase 2 — Networking Core Roadmap

Study order:

1. CKV-010 — Networking Fundamentals and Encapsulation
2. CKV-011 — Ethernet, Switching, VLANs, and Layer-2 Security
3. CKV-012 — IPv4, Subnetting, ARP, ICMP, and NAT
4. CKV-013 — IPv6 and Neighbor Discovery Security
5. CKV-014 — TCP, UDP, Ports, and Transport Troubleshooting
6. CKV-015 — DNS Architecture, Resolution, Attacks, and Defense
7. CKV-016 — DHCP, DHCP Snooping, and IP Source Guard
8. CKV-017 — Network Design, Segmentation, DMZs, and Hard Controls
9. CKV-018 — Network Protocol Capture, Structures, and Analysis

Networking outcomes:

| Outcome | CKV basis |
|---|---|
| Explain packet path through L2/L3/L4 and application boundary | CKV-010 to CKV-014 |
| Map trust zones and segmentation controls | CKV-011, CKV-017 |
| Diagnose DNS/DHCP/NAT connectivity without unsafe scanning | CKV-012, CKV-015, CKV-016 |
| Decide where to place sensors and enforcement points | CKV-017, CKV-018 |
| Know when packet capture is required | CKV-018 |

Networking gate:

```text
Given an IP plan and application flow, explain routing, name resolution, DHCP behavior,
ports, segmentation, firewall control points, and packet-capture locations.
```

## 9. Phase 3 — Operating Systems Security Roadmap

Study order:

1. CKV-020 — Windows Fundamentals for Security
2. CKV-026 — Linux Fundamentals and Hardening for Security
3. CKV-021 — NTFS, File Permissions, EFS, and Alternate Data Streams
4. CKV-022 — Windows Access Control Internals: Tokens, SIDs, ACLs, SRM
5. CKV-023 — UAC, Integrity Levels, and Elevation Semantics
6. CKV-024 — Windows Registry, Services, Scheduled Tasks, and Persistence Surfaces
7. CKV-025 — Windows Security Stack: Updates, Defender, Firewall, SmartScreen, BitLocker, TPM, VSS

Operating-system outcomes:

| Outcome | CKV basis |
|---|---|
| Understand host identity, user context, processes, services, filesystem, and logs | CKV-020, CKV-026 |
| Interpret permissions without confusing identity, authorization, and elevation | CKV-021 to CKV-023 |
| Recognize high-risk persistence surfaces defensively | CKV-024 |
| Validate endpoint security posture at baseline level | CKV-025 |
| Use built-in tools safely for triage and evidence | CKV-090 |

Operating-system gate:

```text
Given a Windows or Linux host, safely record system context, current user, privilege state,
network state, service state, security state, important logs, and evidence notes.
```

## 10. Phase 4 — Identity and Access Security Roadmap

Study order:

1. CKV-022 — Windows Access Control Internals: Tokens, SIDs, ACLs, SRM
2. CKV-030 — Active Directory Fundamentals
3. CKV-031 — Kerberos Authentication, PAC, Tickets, and Windows Logon
4. CKV-032 — NTLM, Netlogon, Relay Risk, and Authentication Hardening
5. CKV-033 — LDAP, LDAPS, Signing, Channel Binding, and Directory Access
6. CKV-034 — Group Policy Internals and Security
7. CKV-035 — AD Delegation: Unconstrained, Constrained, and RBCD
8. CKV-037 — AD CS and PKI Security
9. CKV-036 — Active Directory Attack Paths and Defensive Monitoring

Identity outcomes:

| Outcome | CKV basis |
|---|---|
| Separate authentication, authorization, token creation, and policy enforcement | CKV-022, CKV-030 to CKV-034 |
| Explain Kerberos/NTLM/LDAP trust and hardening at defensive level | CKV-031 to CKV-033 |
| Understand delegation and certificate-service risk as architecture issues | CKV-035, CKV-037 |
| Map AD attack paths to monitoring and control points | CKV-036 |
| Validate identity state with safe administrative tools | CKV-090 |

Identity gate:

```text
Given an AD authentication or access problem, identify the involved account, token/context,
protocol, directory object, GPO influence, trust boundary, log source, and safe validation tool.
```

## 11. Phase 5 — Application Security and DevSecOps Roadmap

Study order:

1. CKV-040 — HTTP, Web Fundamentals, Sessions, and Cookies
2. CKV-041 — OWASP Web Top 10 Canonical Security Model
3. CKV-042 — OWASP API Security Top 10 Canonical Model
4. CKV-044 — API Security Controls: Authentication, Authorization, Schema, Rate Limits
5. CKV-043 — DevSecOps, Secure SDLC, SAST, DAST, SCA, and Security Gates
6. CKV-081 — Firewalls, WAFs, IDS/IPS, and Network Security Controls
7. CKV-082 — Vulnerability Management, Scanning, Prioritization, and Remediation

Application outcomes:

| Outcome | CKV basis |
|---|---|
| Understand HTTP, sessions, cookies, origins, CORS, caching, redirects | CKV-040 |
| Map web and API risks to design controls | CKV-041, CKV-042, CKV-044 |
| Integrate security gates into SDLC and CI/CD | CKV-043 |
| Validate app-layer controls without relying on WAF alone | CKV-081 |
| Convert findings into prioritized remediation | CKV-082 |

Application gate:

```text
Given a web/API design, identify authentication, session, authorization, input/output,
rate-limit, logging, dependency, deployment, and remediation controls.
```

## 12. Phase 6 — Cloud Security Roadmap

Study order:

1. CKV-050 — Cloud Fundamentals: IaaS, PaaS, SaaS, Compute, Storage, IAM
2. CKV-051 — Cloud Security Architecture and Hard Controls
3. CKV-017 — Network Design, Segmentation, DMZs, and Hard Controls
4. CKV-030 to CKV-037 — Identity security references where hybrid identity exists
5. CKV-060 to CKV-065 — Detection, response, evidence, automation, monitoring
6. CKV-082 — Vulnerability Management, Scanning, Prioritization, and Remediation
7. CKV-091 — Virtualization, Lab Design, and Safe Practice Environments

Cloud outcomes:

| Outcome | CKV basis |
|---|---|
| Explain shared responsibility and service model differences | CKV-050 |
| Design IAM, network, logging, encryption, backup, and guardrail controls | CKV-051 |
| Connect cloud architecture to segmentation and monitoring | CKV-017, CKV-060, CKV-065 |
| Handle cloud findings through vulnerability and change processes | CKV-003, CKV-005, CKV-082 |
| Build safe cloud labs without real production secrets | CKV-091 |

Cloud gate:

```text
Given a cloud workload, identify the service model, identities, exposed resources, network path,
logging controls, key/secret controls, backup posture, and proof of secure configuration.
```

## 13. Phase 7 — Detection, Response, Forensics, and Automation Roadmap

Study order:

1. CKV-060 — Detection Engineering and Telemetry Design
2. CKV-061 — Incident Response Lifecycle and Playbook Design
3. CKV-063 — Digital Forensics and Evidence Handling
4. CKV-062 — Threat Hunting Methodology
5. CKV-064 — SOAR, Automation, Validation, and Provability Outputs
6. CKV-065 — Security Monitoring Tools and Lab Architecture
7. CKV-082 — Vulnerability Management, Scanning, Prioritization, and Remediation
8. CKV-090 — Command-Line and Built-in Administration Tools for Security Work
9. CKV-091 — Virtualization, Lab Design, and Safe Practice Environments

Detection/response outcomes:

| Outcome | CKV basis |
|---|---|
| Build detection from telemetry, logic, and expected evidence | CKV-060 |
| Run incidents through preparation, triage, containment, eradication, recovery, and lessons learned | CKV-061 |
| Preserve evidence and chain of custody | CKV-063 |
| Hunt from hypothesis to report | CKV-062 |
| Automate safely with approval, rollback, verification, and evidence | CKV-064 |
| Validate monitoring architecture in a lab | CKV-065, CKV-091 |

Detection/response gate:

```text
Given a suspicious event, identify telemetry sources, detection logic, triage steps,
evidence handling, response decision, verification proof, and reporting output.
```

## 14. Phase 8 — Offensive-Security Defensive-Normalized Roadmap

Study order:

1. CKV-070 — Penetration Testing Methodology and Authorization
2. CKV-071 — Red Teaming, Campaign Design, OPSEC, and Defensive Value
3. CKV-072 — Network Attack Concepts and Defensive Controls
4. CKV-073 — Credential Attack Concepts and Defensive Controls
5. CKV-074 — Privilege Escalation, Persistence, and Lateral Movement Concepts
6. CKV-075 — Social Engineering and Security Awareness
7. CKV-080 — Malware, APT Lifecycle, Botnets, and Advanced Threat Controls
8. CKV-081 — Firewalls, WAFs, IDS/IPS, and Network Security Controls
9. CKV-060 to CKV-065 — Detection, IR, forensics, SOAR, monitoring

This phase is defensive-normalized. It does not teach unauthorized access, exploit execution, credential theft, malware operations, evasion, C2 setup, or persistence creation. It teaches how security teams convert attacker behavior into authorization rules, controls, telemetry, detection, response, and validation.

Defensive-normalized outcomes:

| Outcome | CKV basis |
|---|---|
| Understand authorized testing boundaries | CKV-070 |
| Translate red-team results into defensive improvement | CKV-071 |
| Map network and credential attack concepts to controls | CKV-072, CKV-073 |
| Recognize post-exploitation patterns defensively | CKV-074 |
| Reduce human-risk and social-engineering exposure | CKV-075 |
| Understand advanced-threat control strategy | CKV-080, CKV-081 |

Defensive-normalized gate:

```text
Given a threat scenario, define authorization boundary, business risk, likely control failures,
telemetry gaps, detection opportunities, response actions, hardening priorities, and validation proof.
```

## 15. Phase 9 — Malware, Network Controls, Tools, and Labs Roadmap

Study order:

1. CKV-080 — Malware, APT Lifecycle, Botnets, and Advanced Threat Controls
2. CKV-081 — Firewalls, WAFs, IDS/IPS, and Network Security Controls
3. CKV-082 — Vulnerability Management, Scanning, Prioritization, and Remediation
4. CKV-090 — Command-Line and Built-in Administration Tools for Security Work
5. CKV-091 — Virtualization, Lab Design, and Safe Practice Environments
6. CKV-092 — Security Architecture Reference Roadmaps

Outcomes:

| Outcome | CKV basis |
|---|---|
| Explain malware and APT lifecycle as defensive model | CKV-080 |
| Place controls where they can observe or enforce | CKV-081 |
| Prioritize weakness remediation through risk and exposure | CKV-082 |
| Use tools safely and preserve evidence | CKV-090 |
| Build isolated labs for validation and practice | CKV-091 |
| Navigate all role paths | CKV-092 |

Phase gate:

```text
Given a defensive improvement project, build a safe lab, select controls, validate telemetry,
record evidence, document results, and map improvements back to risk and architecture.
```

## 16. Security Engineer Roadmap

Primary mission: own practical security posture across assets, controls, risk, change, tools, and validation.

| Stage | CKV files | Capability |
|---|---|---|
| Engineering foundation | CKV-001 to CKV-006 | Security role, principles, risk, assets, change, resilience. |
| Infrastructure baseline | CKV-010 to CKV-018, CKV-020 to CKV-026 | Network and OS trust boundaries. |
| Identity baseline | CKV-030 to CKV-037 | Enterprise authentication, authorization, policy, certificate risk. |
| Application/cloud baseline | CKV-040 to CKV-044, CKV-050 to CKV-051 | Modern workload security. |
| Operations | CKV-060, CKV-061, CKV-064, CKV-082 | Detection, response, automation, remediation proof. |
| Controls and tooling | CKV-080, CKV-081, CKV-090, CKV-091 | Threat-informed controls and safe validation. |

Readiness indicator:

```text
Can evaluate a business system from asset inventory through architecture, identity, endpoint,
network, application, cloud, logging, vulnerability, response, and resilience controls.
```

## 17. SOC Analyst to Detection Engineer Roadmap

Primary mission: move from alert handling to telemetry design, detection logic, investigation, tuning, and validation.

| Stage | CKV files | Capability |
|---|---|---|
| Context foundation | CKV-001, CKV-002, CKV-010 to CKV-018, CKV-020, CKV-026 | Understand what normal systems and networks look like. |
| Log and host context | CKV-021 to CKV-025, CKV-090 | Interpret host evidence safely. |
| Identity telemetry | CKV-030 to CKV-037 | Understand AD/Kerberos/NTLM/LDAP/GPO telemetry context. |
| Web/cloud telemetry | CKV-040 to CKV-044, CKV-050 to CKV-051 | Understand application and cloud logs. |
| Detection engineering | CKV-060 | Build and evaluate detection logic. |
| Investigation/response | CKV-061, CKV-063 | Triage, evidence, escalation. |
| Hunting/automation/lab | CKV-062, CKV-064, CKV-065, CKV-091 | Hypothesis-driven hunts and safe validation. |
| Threat-informed mapping | CKV-072 to CKV-074, CKV-080 | Map behaviors to controls and detections. |

Readiness indicator:

```text
Can explain why an alert fired, what data supports it, what context is missing,
how to triage it, how to tune it, and how to prove the control works.
```

## 18. Incident Responder Roadmap

Primary mission: contain, eradicate, recover, preserve evidence, coordinate decisions, and prove response effectiveness.

| Stage | CKV files | Capability |
|---|---|---|
| Governance and resilience | CKV-001, CKV-003, CKV-005, CKV-006 | Authority, exception, change, and continuity context. |
| Technical baselines | CKV-010 to CKV-018, CKV-020 to CKV-026 | Network and host investigation context. |
| Identity response | CKV-030 to CKV-037, CKV-073 | Authentication and access-control incident context. |
| Application/cloud incidents | CKV-040 to CKV-044, CKV-050 to CKV-051 | Web/API/cloud incident context. |
| Detection and IR | CKV-060, CKV-061 | Alert-to-incident lifecycle. |
| Evidence and forensics | CKV-063 | Preservation, timelines, custody. |
| Automation and tools | CKV-064, CKV-090 | Safe response actions and proof. |
| Threat and control context | CKV-074, CKV-080, CKV-081, CKV-082 | Remediation and control validation. |

Readiness indicator:

```text
Can move an incident from report to triage, evidence, containment decision, eradication,
recovery, control validation, documentation, and lessons learned.
```

## 19. Threat Hunter Roadmap

Primary mission: discover unknown or weakly signaled threats using hypotheses, baselines, telemetry, and adversary-informed reasoning.

| Stage | CKV files | Capability |
|---|---|---|
| Environment understanding | CKV-010 to CKV-018, CKV-020 to CKV-026, CKV-030 to CKV-037 | Know normal network, host, and identity behavior. |
| Application/cloud understanding | CKV-040 to CKV-044, CKV-050 to CKV-051 | Know modern workload behavior. |
| Detection foundations | CKV-060 | Understand telemetry quality and detection logic. |
| Hunting method | CKV-062 | Hypotheses, baselines, enrichment, reporting. |
| Forensic validation | CKV-063 | Evidence confidence and timeline support. |
| Threat-informed concepts | CKV-072 to CKV-074, CKV-080 | Translate behavior patterns into hunts. |
| Tools/lab | CKV-065, CKV-090, CKV-091 | Validate hunts safely. |

Readiness indicator:

```text
Can write a hypothesis, identify required data, build a baseline, test suspicious patterns,
control false positives, and produce a defensible hunt report.
```

## 20. Digital Forensics Roadmap

Primary mission: preserve and analyze evidence to answer what happened, when, where, how, and with what impact.

| Stage | CKV files | Capability |
|---|---|---|
| Evidence governance | CKV-001, CKV-003, CKV-006 | Authority, legal/business context, continuity. |
| OS artifacts | CKV-020 to CKV-026 | Host artifacts and security state. |
| Network artifacts | CKV-010 to CKV-018 | Network evidence and packet context. |
| Identity artifacts | CKV-030 to CKV-037 | Authentication, directory, and policy artifacts. |
| Web/cloud artifacts | CKV-040 to CKV-044, CKV-050 to CKV-051 | Application and cloud evidence. |
| Forensics core | CKV-063 | Evidence handling and timelines. |
| IR integration | CKV-061 | Response lifecycle alignment. |
| Tools/labs | CKV-090, CKV-091 | Safe evidence collection and practice. |

Readiness indicator:

```text
Can preserve evidence, explain artifact source and reliability, build a timeline,
separate facts from assumptions, and support incident decisions.
```

## 21. Identity / Active Directory Security Roadmap

Primary mission: secure identity trust, authentication, authorization, directory services, group policy, delegation, and certificate services.

| Stage | CKV files | Capability |
|---|---|---|
| Windows access model | CKV-020 to CKV-024 | Host identity and authorization context. |
| AD architecture | CKV-030 | Domains, forests, OUs, DCs, trusts, replication. |
| Authentication protocols | CKV-031, CKV-032 | Kerberos and NTLM risk and hardening. |
| Directory access | CKV-033 | LDAP/LDAPS signing, binding, channel binding. |
| Policy and delegation | CKV-034, CKV-035 | GPO and delegation controls. |
| Certificate services | CKV-037 | PKI and AD CS risk controls. |
| Monitoring and attack paths | CKV-036, CKV-060, CKV-073 | Detection and defensive validation. |
| Safe tools | CKV-090, CKV-091 | Administrative validation in lab and production-safe mode. |

Readiness indicator:

```text
Can explain identity trust boundaries and validate risky accounts, protocols, policies,
delegation paths, certificate exposure, and monitoring gaps without using offensive procedures.
```

## 22. Network Security Architect Roadmap

Primary mission: design segmented, monitored, resilient networks with appropriate control placement.

| Stage | CKV files | Capability |
|---|---|---|
| Network fundamentals | CKV-010 to CKV-016 | L2/L3/L4/DNS/DHCP behavior and security. |
| Architecture | CKV-017 | Segmentation, DMZs, control placement. |
| Packet analysis | CKV-018 | Visibility and protocol evidence. |
| Endpoint and identity context | CKV-020 to CKV-026, CKV-030 to CKV-037 | Host and identity dependencies. |
| Application/cloud context | CKV-040 to CKV-044, CKV-050 to CKV-051 | Modern traffic and cloud network dependencies. |
| Controls | CKV-081 | Firewalls, WAF, IDS/IPS, network controls. |
| Monitoring and lab | CKV-060, CKV-065, CKV-090, CKV-091 | Sensor placement and validation. |

Readiness indicator:

```text
Can design zones, traffic paths, firewall/control points, sensor placement, logging,
name/address services, and validation tests for a secure network architecture.
```

## 23. Application Security / DevSecOps Roadmap

Primary mission: secure software, APIs, pipelines, dependencies, deployments, and production feedback loops.

| Stage | CKV files | Capability |
|---|---|---|
| Security and risk foundation | CKV-001 to CKV-005 | Risk, secure-by-design, change control. |
| Web/API foundation | CKV-040 to CKV-044 | HTTP, sessions, OWASP, APIs, controls, SDLC. |
| Infrastructure context | CKV-010 to CKV-018, CKV-050 to CKV-051 | Network and cloud architecture. |
| Identity context | CKV-030 to CKV-033 | Authentication and authorization foundations. |
| Vulnerability lifecycle | CKV-082 | Finding triage and remediation proof. |
| Detection/response | CKV-060, CKV-061, CKV-064 | Logging, response, automation. |
| Controls and tools | CKV-081, CKV-090, CKV-091 | WAF/control validation and safe lab work. |

Readiness indicator:

```text
Can review a web/API system from design through code, dependency, pipeline, deployment,
logging, access control, vulnerability handling, and runtime monitoring.
```

## 24. Cloud Security Architect Roadmap

Primary mission: design secure cloud environments with strong identity, network boundaries, logging, encryption, backup, guardrails, and operational proof.

| Stage | CKV files | Capability |
|---|---|---|
| Foundations | CKV-001 to CKV-006 | Risk, assets, change, resilience. |
| Networking and identity | CKV-010 to CKV-018, CKV-030 to CKV-037 | Cloud network and IAM dependencies. |
| Cloud core | CKV-050 to CKV-051 | Shared responsibility and hard controls. |
| App and DevSecOps | CKV-040 to CKV-044 | Cloud-hosted application controls. |
| Monitoring/IR/SOAR | CKV-060 to CKV-065 | Cloud telemetry, response, evidence, automation. |
| Vulnerability and controls | CKV-081, CKV-082 | Control placement and remediation. |
| Labs/tools | CKV-090, CKV-091 | Safe validation and documentation. |

Readiness indicator:

```text
Can design and review a cloud workload for IAM, network exposure, encryption, logging,
backup, secrets, vulnerability handling, response, and cost/billing safety at high level.
```

## 25. Vulnerability Management Roadmap

Primary mission: turn asset exposure and weakness data into prioritized, verified risk reduction.

| Stage | CKV files | Capability |
|---|---|---|
| Governance foundation | CKV-003, CKV-004, CKV-005 | Risk, inventory, exception/change process. |
| Technical context | CKV-010 to CKV-018, CKV-020 to CKV-026, CKV-030 to CKV-037 | Network, OS, identity exposure context. |
| Application/cloud context | CKV-040 to CKV-044, CKV-050 to CKV-051 | Web/API/cloud vulnerability context. |
| Vulnerability core | CKV-082 | Scanning, prioritization, remediation, verification, reporting. |
| Detection/IR feedback | CKV-060, CKV-061 | Exploitation signal and response alignment. |
| Controls and tools | CKV-081, CKV-090, CKV-091 | Control validation and safe evidence. |

Readiness indicator:

```text
Can prioritize findings by asset criticality, exposure, exploitability, compensating controls,
business impact, remediation feasibility, and verification evidence.
```

## 26. Security Automation / SOAR Roadmap

Primary mission: automate security workflows safely with approvals, evidence, rollback, verification, and auditability.

| Stage | CKV files | Capability |
|---|---|---|
| Governance and safety | CKV-001 to CKV-006 | Authority, risk, exceptions, change, resilience. |
| Detection/IR base | CKV-060, CKV-061 | Signal-to-action lifecycle. |
| Evidence and validation | CKV-063, CKV-064 | Proof, rollback, verification, reporting. |
| Tool context | CKV-090 | Safe command and admin-tool use. |
| API/app/cloud context | CKV-040 to CKV-044, CKV-050 to CKV-051 | Automation targets and integration risks. |
| Lab validation | CKV-065, CKV-091 | Test automation before production. |

Readiness indicator:

```text
Can design an automated workflow that checks preconditions, requests approval when needed,
executes safely, records evidence, verifies result, and preserves rollback options.
```

## 27. Malware and Advanced Threat Defense Roadmap

Primary mission: understand advanced threat behavior enough to build layered controls, telemetry, response, and resilience.

| Stage | CKV files | Capability |
|---|---|---|
| Foundations | CKV-001 to CKV-006 | Risk and resilience context. |
| Technical substrate | CKV-010 to CKV-018, CKV-020 to CKV-026 | Network and host behavior. |
| Identity and movement context | CKV-030 to CKV-037, CKV-073, CKV-074 | Credential and lateral-movement risk controls. |
| Malware/threat model | CKV-080 | Lifecycle, control points, defensive model. |
| Network/endpoint controls | CKV-025, CKV-081 | Layered controls and limitations. |
| Detection/IR/forensics | CKV-060 to CKV-064 | Detect, respond, preserve evidence. |
| Safe labs | CKV-091 | Isolation and sandboxing principles only. |

Readiness indicator:

```text
Can map a malware or APT scenario to prevention, detection, containment, eradication,
recovery, evidence, and architecture improvements without executing malware.
```

## 28. Security Architecture / CISO-Track Roadmap

Primary mission: design, govern, communicate, and continuously improve a security program across business, technology, people, process, and controls.

| Stage | CKV files | Capability |
|---|---|---|
| Governance core | CKV-001 to CKV-006 | Operating model, risk, assets, exceptions, resilience. |
| Technical breadth | CKV-010 to CKV-018, CKV-020 to CKV-026, CKV-030 to CKV-037 | Infrastructure, OS, identity foundations. |
| Business systems | CKV-040 to CKV-044, CKV-050 to CKV-051 | Application, DevSecOps, cloud risk. |
| Operations | CKV-060 to CKV-065, CKV-082 | Detect, respond, automate, improve. |
| Threat-informed controls | CKV-070 to CKV-075, CKV-080, CKV-081 | Testing governance, threat-informed defense, control strategy. |
| Tools/labs/roadmaps | CKV-090 to CKV-092 | Operational validation and reference management. |

Readiness indicator:

```text
Can convert business goals into architecture principles, control objectives, risk decisions,
program priorities, evidence requirements, response capability, and improvement roadmap.
```

## 29. Penetration Testing and Red-Team Governance Roadmap at Defensive Level

Primary mission: govern authorized testing so that testing improves security without becoming unsafe, illegal, or operationally harmful.

| Stage | CKV files | Capability |
|---|---|---|
| Governance | CKV-001, CKV-003, CKV-005 | Authority, risk, change, exceptions. |
| Technical prerequisites | CKV-010 to CKV-018, CKV-020 to CKV-026, CKV-030 to CKV-037, CKV-040 to CKV-044 | Understand tested systems. |
| Authorization and methodology | CKV-070 | Scope, ROE, permission, reporting. |
| Red-team defensive value | CKV-071 | TTD/TTM, purple-team learning, campaign value. |
| Defensive concept mapping | CKV-072 to CKV-075, CKV-080 | Convert findings into controls and detections. |
| Detection and response integration | CKV-060 to CKV-065 | Measure detection, response, and evidence. |
| Lab safety | CKV-091 | Avoid unsafe or unauthorized practice. |

Readiness indicator:

```text
Can define a test scope, legal authority, safety boundaries, expected evidence, detection goals,
reporting outputs, and remediation validation without prescribing offensive procedures.
```

## 30. Tools and Lab Practice Roadmap

Primary mission: use built-in tools and safe labs to validate knowledge without harming production systems or exposing risky targets.

| Stage | CKV files | Capability |
|---|---|---|
| Safe tool use | CKV-090 | Read-only-first, context recording, evidence-preserving commands. |
| Lab design | CKV-091 | Isolation, network modes, snapshots, synthetic data, documentation. |
| Monitoring practice | CKV-065 | Tool placement and telemetry pipelines. |
| Detection/IR practice | CKV-060 to CKV-064 | Alerts, investigations, response evidence. |
| Vulnerability/control practice | CKV-081, CKV-082 | Control validation and remediation proof. |
| Roadmap integration | CKV-092 | Role-specific practice planning. |

Practice gate:

```text
A safe practice environment has documented topology, IP plan, assets, credentials policy,
snapshot plan, isolation controls, internet exposure controls, logging, and reset procedure.
```

## 31. CISSP-Aligned Review Path at High Level

This path is for broad review, not a replacement for official exam materials.

| CISSP-style domain area | CKV review nodes |
|---|---|
| Security and Risk Management | CKV-001 to CKV-006, CKV-075, CKV-076 |
| Asset Security | CKV-004, CKV-006, CKV-063, CKV-091 |
| Security Architecture and Engineering | CKV-002, CKV-006, CKV-017, CKV-020 to CKV-026, CKV-050 to CKV-051, CKV-080 to CKV-081 |
| Communication and Network Security | CKV-010 to CKV-018, CKV-081 |
| Identity and Access Management | CKV-022, CKV-030 to CKV-037, CKV-050 to CKV-051 |
| Security Assessment and Testing | CKV-060, CKV-062, CKV-070, CKV-082, CKV-090, CKV-091 |
| Security Operations | CKV-005, CKV-006, CKV-025, CKV-060 to CKV-065, CKV-082, CKV-090 |
| Software Development Security | CKV-040 to CKV-044 |

Review rule:

```text
For CISSP-style breadth, focus on purpose, risk, ownership, lifecycle, control objective,
trade-off, governance, and assurance evidence before memorizing low-level details.
```

## 32. Interview Preparation Roadmap

Interview preparation should follow the pattern: define, explain, apply, troubleshoot, secure, monitor, and validate.

| Interview area | CKV nodes | Expected answer style |
|---|---|---|
| Security engineering | CKV-001 to CKV-006 | Risk-aware, business-aware, control-aware. |
| Networking | CKV-010 to CKV-018 | Packet path, segmentation, troubleshooting, monitoring. |
| Windows/Linux | CKV-020 to CKV-026, CKV-090 | Host context, permissions, services, logs, safe tools. |
| Identity/AD | CKV-030 to CKV-037 | Authentication vs authorization, protocol risk, GPO, monitoring. |
| AppSec/API | CKV-040 to CKV-044 | HTTP, sessions, access control, OWASP, SDLC. |
| Cloud | CKV-050 to CKV-051 | Shared responsibility, IAM, logging, encryption, network controls. |
| SOC/detection | CKV-060, CKV-062, CKV-065 | Telemetry, logic, false positives, validation. |
| IR/forensics | CKV-061, CKV-063 | Lifecycle, evidence, containment, recovery, documentation. |
| SOAR | CKV-064 | Approval, safety, rollback, verification, evidence. |
| Threat-informed defense | CKV-070 to CKV-075, CKV-080 to CKV-081 | Defensive framing, authorization, controls, detection. |
| Vulnerability management | CKV-082 | Prioritization, remediation, validation, reporting. |
| Labs/tools | CKV-090 to CKV-091 | Read-only-first, isolation, documentation, proof. |

Interview answer template:

```text
Definition -> Why it matters -> Failure mode -> Defensive control -> Evidence/logs ->
Validation method -> Trade-off -> Related CKV ownership.
```

## 33. Hands-On Practice and Validation Roadmap

Practice must be authorized, isolated, documented, resettable, and defensive.

| Practice stage | CKV basis | Safe validation target |
|---|---|---|
| Build lab | CKV-091 | Isolated network, snapshots, synthetic data, documented topology. |
| Use tools safely | CKV-090 | Read-only inspection and context recording. |
| Validate network design | CKV-010 to CKV-018, CKV-081 | Connectivity, segmentation, control placement, logs. |
| Validate host baselines | CKV-020 to CKV-026 | Permissions, services, security state, logs. |
| Validate identity controls | CKV-030 to CKV-037 | Policy, authentication, directory hardening, monitoring. |
| Validate web/API controls | CKV-040 to CKV-044 | Session, access control, schema, rate-limit, logging checks. |
| Validate cloud controls | CKV-050 to CKV-051 | IAM, logging, encryption, backups, guardrails. |
| Validate detection/IR | CKV-060 to CKV-065 | Telemetry, alerts, triage, evidence package, response proof. |
| Validate vulnerability process | CKV-082 | Finding intake, prioritization, remediation, verification. |

Practice output format:

```text
Objective
Scope
Topology / assets
Assumptions
Controls being validated
Read-only evidence
Change approval if required
Validation result
Screenshots / exported logs / hashes where appropriate
Lessons learned
Rollback/reset notes
```

## 34. Safe Capstone Project Ideas

All capstones must use isolated labs, synthetic data, no real production credentials, no public exposure of vulnerable systems, and no exploit walkthroughs.

| Capstone | CKV nodes | Safe output |
|---|---|---|
| Enterprise security posture baseline | CKV-001 to CKV-006, CKV-090 | Asset/risk/control/exception report. |
| Segmented network lab validation | CKV-010 to CKV-018, CKV-081, CKV-091 | Topology, IP plan, allowed flows, blocked flows, evidence. |
| Windows/Linux hardening validation | CKV-020 to CKV-026, CKV-090 | Baseline checklist, read-only evidence, remediation plan. |
| AD security review lab | CKV-030 to CKV-037, CKV-060, CKV-090, CKV-091 | Identity architecture review and monitoring plan. |
| Web/API secure design review | CKV-040 to CKV-044 | Threat model, control map, logging requirements, test plan. |
| Cloud landing-zone review | CKV-050 to CKV-051 | IAM/network/logging/encryption/backup control map. |
| Detection-to-IR pipeline | CKV-060 to CKV-065, CKV-091 | Alert, triage, evidence, response, verification, report. |
| Vulnerability management program simulation | CKV-003 to CKV-005, CKV-082 | Prioritized remediation and exception register. |
| SOAR proof package design | CKV-064, CKV-090 | Safe workflow with approval, rollback, verification, evidence. |
| Security monitoring lab architecture | CKV-065, CKV-091 | Sensor placement, telemetry map, validation plan. |

## 35. Mastery Checkpoints

| Level | Mastery checkpoint | Evidence of readiness |
|---|---|---|
| L1 — Foundation | Can explain security principles, risk, assets, and change. | Security decision memo for a simple system. |
| L2 — Technical baseline | Can explain network and OS behavior. | Topology diagram, host triage notes, baseline evidence. |
| L3 — Enterprise trust | Can explain identity, web, API, and cloud trust boundaries. | Identity/app/cloud control map. |
| L4 — Operations | Can connect telemetry, detection, IR, forensics, SOAR, and vulnerability management. | End-to-end incident or validation report. |
| L5 — Architecture | Can design defensible controls across domains and prove they work. | Architecture review with risks, controls, evidence, and roadmap. |

Universal mastery questions:

1. What asset is protected?
2. What trust boundary exists?
3. What failure mode is expected?
4. What control reduces the risk?
5. What telemetry proves behavior?
6. What evidence proves control state?
7. What process governs change?
8. What exception path exists?
9. What response is required if it fails?
10. What CKV file owns the deep explanation?

## 36. Readiness Indicators by Role

| Role | Ready when the learner can |
|---|---|
| Security Engineer | Review a system end-to-end across risk, asset, network, OS, identity, app, cloud, logging, vulnerability, and resilience. |
| SOC Analyst | Triage alerts with context, evidence, escalation criteria, and false-positive reasoning. |
| Detection Engineer | Design telemetry-backed detections with quality, tuning, validation, and coverage mapping. |
| Incident Responder | Run a defensible incident lifecycle with containment, evidence, recovery, and lessons learned. |
| Threat Hunter | Build hypotheses, select data, test behavior, and report validated findings. |
| Forensic Analyst | Preserve evidence, build timelines, and separate facts from assumptions. |
| Identity Security Engineer | Validate AD/IAM controls, authentication protocols, GPOs, delegation, certificates, and monitoring. |
| Network Security Architect | Design segmentation, enforcement, visibility, DNS/DHCP/routing support, and control placement. |
| AppSec / DevSecOps Engineer | Review web/API design, SDLC gates, dependency risk, access control, logging, and remediation. |
| Cloud Security Architect | Design cloud IAM, network, logging, encryption, backups, secrets, and guardrails. |
| Vulnerability Manager | Prioritize findings by risk, exposure, asset criticality, compensating controls, and proof of remediation. |
| SOAR Engineer | Automate safely with approvals, rollback, verification, evidence, and auditability. |
| Security Architect / CISO Track | Align business risk, technical controls, operating model, investment roadmap, and assurance proof. |

## 37. Common Study Mistakes

| Mistake | Correction |
|---|---|
| Studying tools before concepts | Learn principles, networks, OS, and identity before tool use. |
| Memorizing attacks without controls | Convert every threat concept into prevention, detection, response, and validation. |
| Skipping asset/risk/change context | Technical controls must connect to business risk and governance. |
| Learning AD before Windows access control | Token, SID, ACL, and privilege concepts are prerequisites. |
| Learning cloud before networking/IAM | Cloud security is mostly identity, network, logging, data, and automation at scale. |
| Treating WAF/EDR/SIEM as magic | Controls have visibility, placement, tuning, and failure limits. |
| Practicing on unsafe systems | Use isolated labs, synthetic data, snapshots, and documented authorization. |
| Treating findings as remediation | Findings require prioritization, ownership, change control, validation, and closure. |
| Treating detection as a query only | Detection requires telemetry design, logic, context, quality, response, and proof. |
| Treating roadmaps as linear forever | After foundations, cycle between role practice, architecture review, and validation. |

## 38. Must-Memorize Roadmap Facts

- CKV-001 to CKV-006 are the governance and security-engineering base.
- CKV-010 to CKV-018 are the network base.
- CKV-020 to CKV-026 are the OS and endpoint base.
- CKV-030 to CKV-037 are the identity and AD base.
- CKV-040 to CKV-044 are the application security and DevSecOps base.
- CKV-050 to CKV-051 are the cloud base.
- CKV-060 to CKV-065 plus CKV-082 are the detection, response, automation, monitoring, and vulnerability-management base.
- CKV-070 to CKV-075 plus CKV-080 to CKV-081 are threat-informed defensive-normalized references.
- CKV-090 to CKV-092 are tools, labs, and roadmap references.
- Every technical domain must eventually answer: asset, trust boundary, control, evidence, telemetry, failure mode, response, validation.
- Roadmaps do not replace the owning CKV files.
- Safe practice requires isolation, authorization, snapshots, synthetic data, documentation, and rollback.

## 39. Expert-Level Study Strategy

Expert progression is not only “read more.” It is repeated control validation across domains.

Expert study loop:

```text
Pick a system
  -> define assets and owners
  -> draw trust boundaries
  -> map identities and access paths
  -> map network flows
  -> map application/API/session behavior
  -> map cloud or hosting dependencies
  -> map controls and logs
  -> identify likely failure modes
  -> validate control state safely
  -> produce evidence
  -> propose improvements
  -> document residual risk
```

Expert habits:

| Habit | Effect |
|---|---|
| Always ask “what proves this?” | Converts opinion into evidence. |
| Always map identity and network paths | Exposes real trust boundaries. |
| Always separate prevention, detection, response, and recovery | Prevents single-control thinking. |
| Always keep authorization and safety boundaries | Prevents harmful or illegal practice. |
| Always document assumptions | Keeps architecture review honest. |
| Always test rollback and recovery | Converts hardening into resilience. |
| Always review exceptions | Finds risk accepted by business process. |
| Always map tools to objectives | Prevents tool-first design. |

## 40. Final CKV Reference Map

| CKV ID | Canonical topic | Domain | File path |
|---|---|---|---|
| CKV-001 | Security Engineering Role and Operating Model | Cybersecurity | 01_Foundations/Security_Engineering_Role.md |
| CKV-002 | Security Principles and Secure-by-Design Thinking | Cybersecurity | 01_Foundations/Security_Principles_and_Secure_By_Design.md |
| CKV-003 | Risk Management and Security Governance | GRC | 01_Foundations/Risk_Management_and_Governance.md |
| CKV-004 | Asset Management and Attack Surface Inventory | GRC | 01_Foundations/Asset_Management_and_Attack_Surface.md |
| CKV-005 | Change Management and Security Exceptions | GRC | 01_Foundations/Change_Management_and_Exceptions.md |
| CKV-006 | Business Continuity, Disaster Recovery, and Resilience | GRC | 01_Foundations/BCDR_and_Resilience.md |
| CKV-010 | Networking Fundamentals and Encapsulation | Networking | 02_Networking/Networking_Fundamentals_and_Encapsulation.md |
| CKV-011 | Ethernet, Switching, VLANs, and Layer-2 Security | Networking | 02_Networking/Ethernet_Switching_VLANs_L2_Security.md |
| CKV-012 | IPv4, Subnetting, ARP, ICMP, and NAT | Networking | 02_Networking/IPv4_Subnetting_ARP_ICMP_NAT.md |
| CKV-013 | IPv6 and Neighbor Discovery Security | Networking | 02_Networking/IPv6_and_Neighbor_Discovery_Security.md |
| CKV-014 | TCP, UDP, Ports, and Transport Troubleshooting | Networking | 02_Networking/TCP_UDP_Ports_and_Troubleshooting.md |
| CKV-015 | DNS Architecture, Resolution, Attacks, and Defense | Networking | 02_Networking/DNS_Architecture_Attacks_Defense.md |
| CKV-016 | DHCP, DHCP Snooping, and IP Source Guard | Networking | 02_Networking/DHCP_and_DHCP_Snooping.md |
| CKV-017 | Network Design, Segmentation, DMZs, and Hard Controls | Networking | 02_Networking/Network_Design_Segmentation_DMZs.md |
| CKV-018 | Network Protocol Capture, Structures, and Analysis | Networking | 02_Networking/Protocol_Capture_Structures_Analysis.md |
| CKV-020 | Windows Fundamentals for Security | Operating Systems | 03_Operating_Systems/Windows_Fundamentals_for_Security.md |
| CKV-021 | NTFS, File Permissions, EFS, and Alternate Data Streams | Operating Systems | 03_Operating_Systems/NTFS_File_Permissions_EFS_ADS.md |
| CKV-022 | Windows Access Control Internals: Tokens, SIDs, ACLs, SRM | Identity and Access | 05_Identity_and_Access/Windows_Access_Control_Tokens_ACLs_SRM.md |
| CKV-023 | UAC, Integrity Levels, and Elevation Semantics | Operating Systems | 03_Operating_Systems/UAC_Integrity_and_Elevation.md |
| CKV-024 | Windows Registry, Services, Scheduled Tasks, and Persistence Surfaces | Operating Systems | 03_Operating_Systems/Windows_Registry_Services_Scheduled_Tasks.md |
| CKV-025 | Windows Security Stack: Updates, Defender, Firewall, SmartScreen, BitLocker, TPM, VSS | Endpoint Security | 04_Cybersecurity/Windows_Security_Stack.md |
| CKV-026 | Linux Fundamentals and Hardening for Security | Operating Systems | 03_Operating_Systems/Linux_Fundamentals_and_Hardening.md |
| CKV-030 | Active Directory Fundamentals | Identity and Access | 05_Identity_and_Access/Active_Directory_Fundamentals.md |
| CKV-031 | Kerberos Authentication, PAC, Tickets, and Windows Logon | Identity and Access | 05_Identity_and_Access/Kerberos_Authentication_PAC_Tickets.md |
| CKV-032 | NTLM, Netlogon, Relay Risk, and Authentication Hardening | Identity and Access | 05_Identity_and_Access/NTLM_Netlogon_Relay_and_Hardening.md |
| CKV-033 | LDAP, LDAPS, Signing, Channel Binding, and Directory Access | Identity and Access | 05_Identity_and_Access/LDAP_LDAPS_Signing_Channel_Binding.md |
| CKV-034 | Group Policy Internals and Security | Identity and Access | 05_Identity_and_Access/Group_Policy_Internals_and_Security.md |
| CKV-035 | AD Delegation: Unconstrained, Constrained, and RBCD | Identity and Access | 05_Identity_and_Access/AD_Delegation_Unconstrained_Constrained_RBCD.md |
| CKV-036 | Active Directory Attack Paths and Defensive Monitoring | Identity and Access | 05_Identity_and_Access/AD_Attack_Paths_and_Defensive_Monitoring.md |
| CKV-037 | AD CS and PKI Security | Identity and Access | 05_Identity_and_Access/ADCS_PKI_Security.md |
| CKV-040 | HTTP, Web Fundamentals, Sessions, and Cookies | Application Security | 06_Application_Security/HTTP_Web_Fundamentals_Sessions_Cookies.md |
| CKV-041 | OWASP Web Top 10 Canonical Security Model | Application Security | 06_Application_Security/OWASP_Web_Top_10_Canonical.md |
| CKV-042 | OWASP API Security Top 10 Canonical Model | Application Security | 06_Application_Security/OWASP_API_Security_Top_10_Canonical.md |
| CKV-043 | DevSecOps, Secure SDLC, SAST, DAST, SCA, and Security Gates | Application Security | 06_Application_Security/DevSecOps_Secure_SDLC_SAST_DAST_SCA.md |
| CKV-044 | API Security Controls: Authentication, Authorization, Schema, Rate Limits | Application Security | 06_Application_Security/API_Security_Controls.md |
| CKV-050 | Cloud Fundamentals: IaaS, PaaS, SaaS, Compute, Storage, IAM | Cloud | 07_Cloud/Cloud_Fundamentals.md |
| CKV-051 | Cloud Security Architecture and Hard Controls | Cloud Security | 07_Cloud/Cloud_Security_Architecture_Hard_Controls.md |
| CKV-060 | Detection Engineering and Telemetry Design | Detection and Response | 08_Detection_and_Response/Detection_Engineering_and_Telemetry.md |
| CKV-061 | Incident Response Lifecycle and Playbook Design | Detection and Response | 08_Detection_and_Response/Incident_Response_Lifecycle_Playbooks.md |
| CKV-062 | Threat Hunting Methodology | Detection and Response | 08_Detection_and_Response/Threat_Hunting_Methodology.md |
| CKV-063 | Digital Forensics and Evidence Handling | Forensics | 09_Forensics/Digital_Forensics_Evidence_Handling.md |
| CKV-064 | SOAR, Automation, Validation, and Provability Outputs | Detection and Response | 08_Detection_and_Response/SOAR_Automation_Validation_Provability.md |
| CKV-065 | Security Monitoring Tools and Lab Architecture | Detection and Response | 08_Detection_and_Response/Security_Monitoring_Tools_and_Lab_Architecture.md |
| CKV-070 | Penetration Testing Methodology and Authorization | Offensive Security | 10_Offensive_Security/Penetration_Testing_Methodology_and_Authorization.md |
| CKV-071 | Red Teaming, Campaign Design, OPSEC, and Defensive Value | Offensive Security | 10_Offensive_Security/Red_Teaming_Campaign_Design_OPSEC.md |
| CKV-072 | Network Attack Concepts and Defensive Controls | Offensive Security | 10_Offensive_Security/Network_Attack_Concepts_Defensive_Controls.md |
| CKV-073 | Credential Attack Concepts and Defensive Controls | Offensive Security | 10_Offensive_Security/Credential_Attack_Concepts_Defensive_Controls.md |
| CKV-074 | Privilege Escalation, Persistence, and Lateral Movement Concepts | Offensive Security | 10_Offensive_Security/Privilege_Escalation_Persistence_Lateral_Movement.md |
| CKV-075 | Social Engineering and Security Awareness | Offensive Security | 10_Offensive_Security/Social_Engineering_and_Awareness.md |
| CKV-076 | Physical Security and Environmental Controls | Physical Security | 04_Cybersecurity/Physical_Security_and_Environmental_Controls.md |
| CKV-080 | Malware, APT Lifecycle, Botnets, and Advanced Threat Controls | Threats | 04_Cybersecurity/Malware_APT_Lifecycle_and_Controls.md |
| CKV-081 | Firewalls, WAFs, IDS/IPS, and Network Security Controls | Network Security | 04_Cybersecurity/Firewalls_WAF_IDS_IPS_Network_Controls.md |
| CKV-082 | Vulnerability Management, Scanning, Prioritization, and Remediation | Security Operations | 08_Detection_and_Response/Vulnerability_Management.md |
| CKV-090 | Command-Line and Built-in Administration Tools for Security Work | Tools | 11_Tools/Command_Line_and_Admin_Tools.md |
| CKV-091 | Virtualization, Lab Design, and Safe Practice Environments | Infrastructure | 11_Tools/Virtualization_Lab_Design.md |
| CKV-092 | Security Architecture Reference Roadmaps | Roadmaps | 12_Roadmaps/Security_Architecture_Reference_Roadmaps.md |

Final usage rule:

```text
Use CKV-092 to choose the path.
Use the owning CKV file to learn the topic.
Use CKV-090 to operate safely.
Use CKV-091 to practice safely.
Use CKV-001 to CKV-006 to justify security decisions.
Use detection, IR, forensics, SOAR, and vulnerability CKVs to prove that controls work.
```

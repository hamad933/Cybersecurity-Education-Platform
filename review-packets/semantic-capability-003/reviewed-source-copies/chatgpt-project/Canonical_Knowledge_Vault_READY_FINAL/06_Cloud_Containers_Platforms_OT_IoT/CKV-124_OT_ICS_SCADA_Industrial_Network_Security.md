# CKV-124 — OT, ICS, SCADA, Industrial Network Security, Purdue Model, and Safety-Critical Controls

## 1. Purpose

This Canonical Knowledge Vault file defines the defensive security model for operational technology (OT), industrial control systems (ICS), SCADA environments, industrial networks, Purdue-style segmentation, safety-critical controls, and OT incident response.

The goal is to make OT security understandable as an engineering discipline, not as a generic IT security extension. OT security protects physical processes, safety, uptime, product quality, environmental controls, and critical infrastructure continuity. A control that is normal in enterprise IT can be unsafe in OT if it disrupts a controller, invalidates a safety case, interrupts a production line, corrupts process values, or blocks an operator during a safety event.

This file is designed for:

- security architects designing OT/ICS segmentation, monitoring, access, and resilience;
- SOC analysts interpreting OT alerts without treating them as ordinary IT alerts;
- auditors reviewing OT control evidence;
- incident responders coordinating with plant operations;
- engineers and administrators protecting controllers, HMIs, historians, engineering workstations, and remote access paths;
- students preparing for enterprise security, critical infrastructure, CISSP-style, and architecture interviews.

This file is defensive. It does not provide PLC manipulation, industrial protocol abuse, unsafe scanning, exploitation steps, destructive testing, process disruption, or offensive OT playbooks.

## 2. Core Definition

Operational Technology is the hardware and software used to monitor, control, or affect physical equipment, industrial processes, production systems, building systems, utilities, transportation systems, medical/industrial devices, and safety-relevant environments.

Industrial Control Systems are a subset of OT focused on industrial process control. Common ICS environments include SCADA systems, distributed control systems, programmable logic controller environments, substations, water treatment systems, manufacturing lines, oil and gas facilities, building automation, rail systems, and energy distribution.

The essential difference from normal IT security is consequence. In IT, security failures often affect confidentiality, integrity, and availability of data and services. In OT, the same failure can affect safety, environmental release, product quality, equipment damage, production continuity, regulatory obligations, and public trust.

Canonical definition:

```text
OT/ICS security protects the safe, reliable, authorized, observable, recoverable, and governed operation of cyber-physical systems by controlling identity, network paths, engineering changes, asset state, remote access, monitoring, configuration integrity, recovery, and operational decision-making.
```

## 3. Scope Ownership

This file owns the advanced security model for:

- OT/ICS safety-first cyber risk;
- Purdue model and its modern limitations;
- PLC, RTU, IED, HMI, SCADA, DCS, historian, OPC, SIS, engineering workstation, gateway, sensor, actuator, and field-device roles;
- industrial network zones and conduits;
- industrial DMZ design;
- OT protocol defensive awareness;
- passive monitoring and asset discovery;
- OT change management and management of change;
- vendor access and remote maintenance;
- controller logic backup and recovery;
- OT incident response, forensics, and evidence;
- OT governance, procurement, lifecycle, and supply chain;
- OT-focused controls, telemetry, validation, and framework mapping.

This file is the advanced OT/ICS expansion. It references prior networking, detection, identity, OS, backup, and BCDR CKVs but does not rebuild them.

## 4. What This File Does Not Own

This file does not own:

- full electrical, mechanical, chemical, manufacturing, or process-engineering curriculum;
- PLC programming instruction;
- industrial protocol exploitation;
- product-specific vendor manuals;
- safety engineering certification curriculum;
- full physical security manual;
- full IoT/embedded device security, which belongs to the future IoT/embedded CKV;
- full router/switch/firewall hardening, which belongs to network device and network-control CKVs;
- full VPN/ZTNA design, which belongs to remote access CKV;
- full SIEM/SOC engineering, which belongs to detection and IDS/NDR CKVs;
- full backup-platform architecture, which belongs to backup-platform CKV;
- offensive OT procedures.

Unsafe content deliberately excluded:

- PLC logic manipulation;
- unsafe active scanning of production systems;
- industrial protocol abuse;
- process-disruption examples;
- safety-system bypass;
- remote-access abuse;
- firmware exploitation;
- ladder-logic abuse;
- destructive validation;
- exploitation recipes.

## 5. Prerequisites and Related CKV Files

Required prerequisites:

- CKV-002 — Security Principles and Secure-by-Design Thinking
- CKV-003 — Risk Management and Security Governance
- CKV-004 — Asset Management and Attack Surface Inventory
- CKV-005 — Change Management and Security Exceptions
- CKV-006 — Business Continuity, Disaster Recovery, and Resilience
- CKV-010 — Networking Fundamentals and Encapsulation
- CKV-011 — Ethernet, Switching, VLANs, and Layer-2 Security
- CKV-014 — TCP, UDP, Ports, and Transport Troubleshooting
- CKV-017 — Network Design, Segmentation, DMZs, and Hard Controls
- CKV-018 — Network Protocol Capture, Structures, and Analysis
- CKV-060 — Detection Engineering and Telemetry Design
- CKV-061 — Incident Response Lifecycle and Playbook Design
- CKV-063 — Digital Forensics and Evidence Handling
- CKV-076 — Physical Security and Environmental Controls
- CKV-081 — Firewalls, WAFs, IDS/IPS, and Network Security Controls
- CKV-082 — Vulnerability Management
- CKV-091 — Virtualization, Lab Design, and Safe Practice Environments
- CKV-101 — VPN, Remote Access, Tunneling, ZTNA, SASE, and SSE Security
- CKV-102 — IDS/IPS/HIDS/HIPS/WIDS/WIPS/NDR Deep Technical Model
- CKV-104 — Routing Protocols and Routing Security
- CKV-105 — NAC, 802.1X, EAP, RADIUS, TACACS+, MAB, Posture, and Enterprise Access Control
- CKV-106 — PKI, Certificates, TLS, mTLS, OCSP, CRL, HSM, and Key Lifecycle Internals
- CKV-108 — Advanced Active Directory Security Internals Expansion
- CKV-117 — Linux Security Internals Advanced
- CKV-118 — Windows Security Internals Advanced
- CKV-121 — Database Security
- CKV-122 — Data Security, DLP, Classification, Discovery, Masking, Tokenization, and Privacy Controls
- CKV-123 — Backup Platform Security and Ransomware-Resilient Recovery Architecture

This file expands OT-specific safety, operational, asset, process, network, protocol, monitoring, backup, and governance details that are only referenced at a high level elsewhere.

## 6. OT/ICS Security Mental Model

OT security begins with the operating process, not with the firewall.

The core sequence is:

```text
physical process
  -> sensors observe process state
  -> controllers compute/control actions
  -> actuators modify the process
  -> operators supervise through HMI/SCADA
  -> engineering systems configure logic and parameters
  -> historians and operations systems record state
  -> business systems consume operational data
```

Security must preserve:

- **safety** — people, environment, equipment, and process must not be placed at unacceptable risk;
- **reliability** — control loops and operator functions must work predictably;
- **integrity** — controller logic, process values, alarms, historian data, recipes, and setpoints must remain trustworthy;
- **availability** — operations must continue within acceptable risk limits;
- **recoverability** — the plant must be restorable from known-good configurations and data;
- **accountability** — engineering changes, remote sessions, operator actions, and admin actions must be attributable;
- **governability** — risk decisions must be made by authorized business, engineering, safety, and security owners.

OT security is therefore a safety and reliability discipline with cybersecurity controls embedded into operational governance.

## 7. Safety-First Risk Model and Cyber-Physical Consequence Model

The OT risk model must ask what the cyber event can do to the physical process.

Common cyber-physical consequence classes:

| Consequence class | Defensive meaning |
|---|---|
| Personnel safety impact | Injury or unsafe work condition due to loss of control, misleading indication, or unsafe state. |
| Environmental impact | Release, spill, emission, contamination, or environmental control failure. |
| Equipment damage | Overload, overheating, mechanical stress, pump cavitation, actuator misuse, or unsafe sequence. |
| Production loss | Line stoppage, batch loss, product quality failure, or utility disruption. |
| Process integrity loss | False sensor values, invalid historian data, altered recipes, unauthorized setpoints, or incorrect alarms. |
| Regulatory impact | Safety, environmental, critical infrastructure, or sector reporting obligations. |
| Public impact | Service outage, critical infrastructure disruption, or community safety concern. |

Safety-first principles:

- Do not run active tests against fragile production controllers without formal OT approval.
- Do not isolate or reboot an OT asset unless plant operations confirms process impact.
- Do not block traffic based only on IT security intuition; verify process dependencies.
- Do not wipe or reimage engineering systems until project files, configuration state, and evidence are preserved.
- Do not treat an HMI alarm storm as only an IT alert; it may represent a process safety condition.
- Do not assume availability is always more important than integrity; false process values can be more dangerous than outage.

## 8. IT vs OT vs IoT vs IIoT Security Boundaries

| Domain | Primary objective | Typical assets | Security consequence |
|---|---|---|---|
| IT | Data and business service protection | endpoints, servers, SaaS, identity, databases | data loss, outage, fraud, compliance failure |
| OT | Physical process safety and reliability | controllers, HMIs, historians, engineering stations | safety impact, equipment damage, production loss |
| IoT | Connected device and sensor service control | cameras, sensors, smart appliances, embedded devices | privacy exposure, botnets, device misuse, physical exposure |
| IIoT | Industrial connectivity and analytics | industrial sensors, gateways, telemetry devices, edge compute | process visibility risk, data integrity risk, remote management risk |

Important boundary rules:

- IoT and IIoT devices may exist inside OT networks, but they are not automatically equivalent to PLCs or safety systems.
- IT controls must be adapted to OT constraints before deployment.
- OT assets often have longer lifecycles, weaker native security, and limited patch tolerance.
- IIoT/cloud analytics can create new conduits out of control networks and must be governed as data and control paths.

## 9. ICS Component Model: PLC, RTU, IED, DCS, HMI, SCADA, Engineering Workstation, Historian, OPC, SIS, Sensors, Actuators, Drives, Relays, Gateways, and Field Devices

| Component | Security role | Key risks | Defensive focus |
|---|---|---|---|
| PLC | Executes control logic for machines/processes. | unauthorized logic/config changes, mode changes, weak protocol auth, physical access. | logic backup, change control, controller mode governance, segmentation, monitoring. |
| RTU | Remote telemetry/control in geographically distributed systems. | remote exposure, unreliable links, weak authentication. | secure remote paths, authenticated protocols where available, monitoring. |
| IED | Intelligent electrical device in substations or energy systems. | protection setting changes, time sync issues, protocol exposure. | configuration governance, physical control, protocol allowlists. |
| DCS | Distributed control system for complex process control. | central controller/operator dependency, vendor ecosystem complexity. | role separation, vendor-approved patching, segmentation, backups. |
| HMI | Operator interface for process visibility and commands. | misleading displays, malware, unauthorized operator actions. | workstation hardening, access control, alarm integrity, logging. |
| SCADA server | Supervisory control and data acquisition coordination. | central control compromise, remote site dependency. | segmentation, redundancy, access governance, monitoring. |
| Engineering workstation | Configures controllers and logic. | highest impact change path, vendor tools, project files, removable media. | Tier-0-like treatment, backup, MFA/jump access, allowlisting. |
| Historian | Stores process time-series data. | integrity loss, data exfiltration, business/OT bridge risk. | database security, replication governance, access review, backups. |
| OPC server/gateway | Data exchange between OT systems and consumers. | lateral conduit, weak trust boundaries, legacy OPC exposure. | DMZ mediation, allowlists, account separation, monitoring. |
| SIS | Safety instrumented system. | loss of independence, unsafe intervention, false confidence. | independence, strict change control, safety engineering ownership. |
| Sensors | Measure process variables. | false readings, calibration drift, spoofed values. | maintenance, redundancy, plausibility checks, physical protection. |
| Actuators/drives/relays | Physically change state. | unsafe operation, unauthorized command path. | interlocks, safety limits, controller governance, local controls. |
| Gateways | Translate protocols or bridge networks. | hidden conduit, weak authentication, unmanaged firmware. | inventory, segmentation, firmware governance, logging. |

The most security-critical assets are often not the largest servers. In many plants, the most critical security objects are engineering workstations, controller project files, controller backups, vendor remote-access paths, historian interfaces, and safety-critical configuration records.

## 10. Purdue Model: Levels 0–5, Industrial DMZ, Enterprise/Operations Boundary, and Modern Limitations

The Purdue model is a reference structure for separating enterprise, operations, supervisory control, control, and physical process layers.

| Level | Typical assets | Security meaning |
|---|---|---|
| Level 0 | physical process, sensors, actuators | physical process state and direct physical effects |
| Level 1 | PLCs, RTUs, IEDs, drives, relays | direct control and local automation |
| Level 2 | HMIs, local control servers, operator stations | supervisory/operator control within cell/area |
| Level 3 | site operations, SCADA servers, historians, engineering workstations, OT AD | operations management and plant-wide OT services |
| Industrial DMZ | jump hosts, proxies, patch staging, historian replication, remote access brokers | controlled exchange between enterprise and operations |
| Level 4 | enterprise IT, business systems, ERP/MES handoff | corporate operations and business planning |
| Level 5 | external networks, internet, cloud, vendors | untrusted/external services |

Modern limitations:

- Cloud historians, vendor telemetry, IIoT gateways, and remote operations blur strict vertical layering.
- Some small sites collapse multiple levels onto shared assets.
- Managed services and OEM remote support create conduits that may bypass classic diagrams.
- Purdue is a segmentation model, not a complete security program.
- Actual risk must be validated through traffic, identity, process dependencies, and change paths.

Use Purdue as a mental model and architecture map, then verify it against real network paths, identity paths, vendor paths, data flows, and recovery dependencies.

## 11. Zones and Conduits: IEC 62443-Style Segmentation, Security Levels, Asset Grouping, and Trust Boundaries

A zone groups assets with similar security requirements. A conduit is the controlled communication path between zones.

Zone examples:

- safety system zone;
- controller cell zone;
- packaging line zone;
- utility control zone;
- engineering workstation zone;
- historian zone;
- industrial DMZ zone;
- vendor access zone;
- lab/test bench zone;
- enterprise integration zone.

Conduit examples:

- HMI-to-controller traffic;
- historian replication to DMZ;
- engineering workstation to controllers;
- vendor jump host to engineering workstation;
- OPC gateway to business analytics;
- patch staging to OT hosts;
- SIEM export from DMZ to SOC.

Zone/conduit control questions:

- Which assets share process criticality?
- Which assets require similar patch windows?
- Which flows are required for safety or production?
- Which flows are only convenience paths?
- Which conduits can be mediated, proxied, logged, or made one-way?
- Which conduits cross a safety or trust boundary?
- Who owns approval for each conduit?

Security levels should be risk-driven. A safety zone, engineering zone, and remote-access zone usually require stronger controls than a general reporting zone.

## 12. Industrial Network Architecture: Cell/Area Zones, Control Networks, Supervisory Networks, Process Control DMZ, Remote Access Zones, Vendor Access, Wireless, Serial, and Fieldbus Handoffs

A defensible OT network separates control traffic from business traffic and exposes only governed conduits.

Canonical pattern:

```text
External/vendor/cloud
  -> enterprise edge controls
  -> enterprise IT
  -> industrial DMZ
  -> OT operations services
  -> cell/area zones
  -> controllers/field devices
  -> physical process
```

Industrial network design priorities:

- keep controller traffic local where possible;
- minimize routing between unrelated process cells;
- avoid direct internet access from OT assets;
- place remote access in a controlled zone, not directly into control networks;
- keep patch repositories, jump hosts, historian replication, and file exchange in DMZ-like zones;
- monitor conduits rather than flooding fragile segments with active probes;
- document serial, radio, wireless, and fieldbus handoffs, not just Ethernet;
- treat unmanaged switches and maintenance ports as real conduits.

## 13. OT Protocols at Defensive Awareness Level: Modbus, DNP3, IEC 60870-5-104, IEC 61850, OPC Classic, OPC UA, PROFINET, EtherNet/IP, BACnet, MQTT/Sparkplug, and Proprietary Protocols

This section provides defensive awareness only. It does not provide protocol abuse methods.

| Protocol / family | Common use | Defensive concern | Control focus |
|---|---|---|---|
| Modbus/TCP | simple controller/device communication | historically limited native authentication/integrity | segmentation, allowlisting, passive monitoring, secure gateways where available |
| DNP3 | utility telemetry/control | legacy deployments may lack strong security features | secure variants where feasible, strict conduits, monitoring |
| IEC 60870-5-104 | power/utility control over TCP/IP | sensitive control telemetry over routed networks | segmentation, encryption gateways where required, baseline traffic |
| IEC 61850 | substation automation | high criticality and timing requirements | engineering governance, network design, time sync integrity |
| OPC Classic | Windows/DCOM-based data exchange | complex identity/firewall behavior, legacy dependency | DMZ mediation, hardening, account review |
| OPC UA | modern industrial data exchange | certificate and configuration governance required | certificate lifecycle, endpoint trust, role mapping |
| PROFINET | industrial Ethernet automation | real-time and device-discovery sensitivity | cell segmentation, passive monitoring, switch governance |
| EtherNet/IP | industrial Ethernet control | broad controller/device integration | allowlisted flows, identity governance, monitoring |
| BACnet | building automation | exposed building-control risk | segmentation, inventory, access control |
| MQTT/Sparkplug | IIoT publish/subscribe telemetry | broker trust and cloud conduit risk | broker auth, TLS, topic authorization, data governance |
| Proprietary protocols | vendor-specific operations | limited visibility and dependency on vendor tools | documentation, vendor guidance, passive baselines |

Defenders should know protocol purpose, normal directionality, normal peers, change windows, safety relevance, and monitoring limits.

## 14. Legacy Protocol Risk: Weak/No Authentication, Cleartext Traffic, Fragile Devices, Deterministic Process Behavior, Safety Constraints, and Monitoring Limits

Legacy OT protocol and device risk commonly comes from design assumptions, not negligence.

Common constraints:

- devices designed for physically isolated networks;
- minimal CPU/memory for cryptography;
- long-lived firmware and certification cycles;
- deterministic timing requirements;
- poor tolerance for malformed packets or aggressive scanning;
- cleartext protocol fields;
- weak or shared credentials;
- limited logging;
- proprietary management tools;
- vendor support restrictions;
- fragile serial-to-Ethernet conversions.

Defensive design response:

- compensate with segmentation, allowlists, and passive monitoring;
- place protocol translation and remote access behind controlled gateways;
- avoid uncontrolled active discovery;
- baseline process traffic during normal operation;
- restrict engineering functions to approved hosts and windows;
- use secure protocol variants where the system and safety case support them;
- record risk acceptance for controls that cannot be technically deployed.

## 15. Engineering Workstation Security: Project Files, Programming Software, Controller Logic, Credentials, Removable Media, Vendor Tools, Version Control, Signing, and Backup

Engineering workstations are often the highest-impact OT cyber assets because they can change controller logic and process configuration.

Critical assets on engineering workstations:

- controller project files;
- ladder logic/function block/project artifacts;
- vendor programming software;
- controller connection profiles;
- licensing tools;
- credentials and certificates;
- firmware packages;
- device configuration exports;
- version notes and change approvals;
- removable media histories;
- backup utilities;
- scripts and macros used for engineering tasks.

Controls:

- dedicated engineering workstations by zone or process where feasible;
- no routine email/web browsing from engineering workstations;
- strict removable-media governance;
- application allowlisting for engineering tools;
- patch and test process coordinated with vendor/operations;
- separate admin and engineering identities;
- MFA for remote access to engineering environments;
- version-controlled project files with signed/approved release baselines where supported;
- backup before and after approved changes;
- logging of project open/save/download/upload operations where available;
- periodic comparison between approved project baseline and deployed controller state.

## 16. HMI, SCADA Server, Historian, Alarm/Event Records, Process Visualization, Recipe/Batch Systems, MES/ERP Handoff, and Time-Series Data Security

HMIs and SCADA systems are the operational window into the process. Historians and alarm/event systems preserve process evidence and business visibility.

Security priorities:

- protect operator view integrity;
- preserve alarm/event fidelity;
- maintain historian timestamp accuracy;
- ensure recipes/batch instructions cannot be modified outside approved workflows;
- isolate MES/ERP handoffs through DMZ or brokered integration;
- prevent business analytics access from becoming a control-network path;
- secure database accounts used by historians and reporting tools;
- monitor bulk export and unusual query access;
- keep HMI/SCADA backups tied to known software versions and licenses.

Forensics value:

- alarm timelines;
- process variable trends;
- operator acknowledgement records;
- recipe changes;
- historian write gaps;
- engineering workstation change logs;
- time synchronization records;
- remote-access session windows.

## 17. PLC/RTU/Controller Security at Defensive Level: Firmware, Logic Backups, Controller Modes, Programming Ports, Memory/Project Protection, Physical Controls, and Change Evidence

Controller protection is about preserving known-good logic and authorized control state.

Defensive controller inventory fields:

- vendor/model;
- firmware version;
- serial number;
- physical location;
- process area;
- IP/MAC/serial path;
- controlling engineering workstation/tool;
- program/project version;
- last approved change;
- backup location;
- restore procedure owner;
- mode/status expectation;
- redundancy role;
- safety relevance;
- physical key/switch state where applicable;
- maintenance window rules.

Controller security controls:

- restrict engineering access paths;
- preserve logic backups and compare against approved baselines;
- maintain firmware inventory and vendor advisories;
- physically control programming ports and cabinets;
- enforce change approval before downloads or configuration changes;
- monitor controller mode/state changes where available;
- treat unexplained logic/configuration mismatch as a high-priority event;
- use vendor-supported access controls and project protection where feasible;
- document compensating controls for controllers that cannot enforce authentication.

## 18. Safety Systems and SIS: Safety vs Security, Independence, Safety Lifecycle, Safety-Case Impact, and No-Unsafe-Intervention Principle

Safety systems are designed to reduce risk from hazardous process conditions. Security must not undermine safety independence.

Important distinctions:

- **Safety** reduces risk from process hazards.
- **Security** reduces risk from intentional or accidental cyber interference.
- **Reliability** ensures expected operation over time.
- **Availability** ensures access to needed functions.

SIS governance principles:

- keep SIS independence from basic process control where required by safety design;
- treat SIS changes as safety lifecycle events, not routine IT changes;
- involve safety engineers in security decisions affecting SIS;
- monitor for configuration drift without intrusive testing;
- protect safety documentation and cause/effect matrices;
- preserve proof-test and maintenance records;
- do not apply security tools that can affect safety response without formal assessment.

No-unsafe-intervention principle:

```text
A cybersecurity response must not introduce a larger immediate safety hazard than the condition it is attempting to contain.
```

## 19. OT Asset Inventory: Passive Discovery, Vendor Records, Firmware, Serial Numbers, Physical Location, Process Criticality, Owner, Support Status, Network Path, and Backup Status

OT inventory must include physical, process, cyber, lifecycle, and recovery attributes.

Minimum canonical inventory fields:

| Field | Why it matters |
|---|---|
| Asset ID and tag | Links cybersecurity asset to plant asset records. |
| Vendor/model/firmware | Enables advisory and support evaluation. |
| Serial number | Supports physical verification and spare replacement. |
| Physical location | Supports incident response and maintenance. |
| Process area | Connects cyber risk to process consequence. |
| Purdue level / zone | Supports segmentation and monitoring. |
| Network path | Identifies conduits and dependencies. |
| Owner | Defines accountability. |
| Support status | Identifies end-of-support risk. |
| Criticality | Prioritizes protection and recovery. |
| Backup status | Confirms recoverability. |
| Last approved change | Supports drift detection. |
| Remote access dependency | Identifies vendor and maintenance paths. |
| Safety relevance | Flags safety review requirements. |

Discovery approach:

- prefer passive discovery on production OT networks;
- reconcile passive findings with engineering/vendor records;
- validate unknown assets with operations before action;
- maintain a review queue for candidate assets that appear in telemetry but lack approved records;
- avoid assuming that a device is safe to scan because it responds to IP traffic.

## 20. OT Vulnerability Management: Vendor Advisories, Patch Constraints, Compensating Controls, Maintenance Windows, Firmware Updates, Test Benches, Spare Parts, and Risk Acceptance

OT vulnerability management is risk-managed remediation, not automatic patch deployment.

Decision factors:

- process criticality;
- exploitability in the actual network architecture;
- exposure through conduits and remote access;
- vendor support and warranty constraints;
- safety certification or validation impact;
- required downtime;
- availability of spares;
- firmware rollback ability;
- existence of compensating segmentation/monitoring;
- test bench availability;
- business impact of deferral;
- incident history.

Patch strategy:

1. identify affected assets through inventory;
2. check vendor advisory and site-specific applicability;
3. evaluate exposure path and consequence;
4. test in a representative lab or maintenance environment where feasible;
5. prepare rollback and recovery plan;
6. obtain operations and safety approval;
7. execute during maintenance window;
8. validate process function and monitoring;
9. preserve evidence of change and result.

Compensating controls include firewall allowlists, conduit restrictions, jump-host controls, passive monitoring, vendor access restrictions, application allowlisting, offline backup, and physical access restrictions.

## 21. OT Change Management and Management of Change: Engineering Approval, Operations Approval, Safety Review, Rollback, Backup Before Change, Validation After Change, and Evidence

OT change management must protect safety, production, and configuration integrity.

High-risk OT changes:

- controller logic change;
- HMI/SCADA screen or alarm change;
- historian tag/schema change;
- firewall rule change across zones;
- remote-access path change;
- engineering workstation software update;
- firmware update;
- protocol gateway configuration change;
- safety-system configuration change;
- AD/domain/GPO change affecting OT hosts;
- backup/restore procedure change;
- time synchronization change;
- IP address/routing/VLAN change.

Required evidence:

- approved change request;
- affected asset list;
- risk and safety review;
- dependency review;
- backup before change;
- rollback plan;
- maintenance window approval;
- operations owner approval;
- vendor involvement where required;
- post-change validation;
- updated diagrams/inventory;
- final evidence package.

## 22. OT Remote Access: Vendor Access, Jump Servers, MFA, Session Recording, Time-Bound Access, Approval Workflow, Protocol Restrictions, File Transfer Governance, and Emergency Access

Remote access is one of the highest-risk OT conduits.

Secure OT remote access pattern:

```text
external user/vendor
  -> identity provider / MFA
  -> remote access gateway
  -> industrial DMZ jump host
  -> approved engineering/HMI/admin target
  -> monitored session
  -> time-bound closure
```

Controls:

- no direct vendor VPN into control networks;
- named accounts where feasible, not shared vendor accounts;
- MFA for all remote access;
- time-bound access with approval;
- session recording or equivalent logging for high-impact access;
- restricted file transfer path with malware scanning and approval;
- protocol allowlisting by role and task;
- break-glass access separated from daily access;
- emergency access reviewed after use;
- vendor contract clauses for security, notice, patching, and evidence;
- periodic review of dormant vendor access.

Emergency access must exist, but it must be governed. An emergency path that is always open becomes the normal bypass.

## 23. OT Identity and Access: Local Accounts, Shared Vendor Accounts, Engineering Accounts, Domain Integration Risks, Privileged Access, Service Accounts, Break-Glass, Role Separation, and Physical Access

OT identity is complicated by long-lived systems, vendor tooling, safety constraints, local accounts, and uptime requirements.

Identity types:

- operator accounts;
- engineering accounts;
- maintenance accounts;
- vendor accounts;
- local administrator accounts;
- service accounts;
- historian/database accounts;
- remote-access accounts;
- domain accounts for OT hosts;
- break-glass accounts;
- shared legacy accounts requiring compensating controls.

Risk patterns:

- shared vendor credentials;
- overprivileged engineering accounts;
- domain admin reuse in OT;
- service accounts with interactive login;
- stale contractor accounts;
- local admin password reuse;
- weak break-glass governance;
- physical access granting cyber access;
- poor attribution of controller changes.

Controls:

- separate IT and OT administrative identities;
- restrict domain integration for high-impact OT assets;
- use privileged access workstations or controlled jump hosts for engineering access;
- rotate local administrator credentials where supported;
- review service-account privileges and logon rights;
- require approvals for privileged engineering tasks;
- maintain physical access logs for cabinets/control rooms;
- preserve attribution for engineering changes.

## 24. OT Segmentation: Industrial DMZ, Firewall Allowlists, Unidirectional Gateways/Data Diodes, Remote Access Brokers, NAC Limits, Switchport Governance, VLANs, Routing, and Cross-Zone Traffic Control

Segmentation is a core OT control, but it must be process-aware.

Segmentation goals:

- prevent enterprise compromise from directly reaching controllers;
- limit lateral movement between process areas;
- control engineering access paths;
- separate safety systems where required;
- mediate historian and business data exchange;
- restrict vendor access to approved assets;
- preserve deterministic process traffic;
- make unauthorized conduits visible.

OT segmentation controls:

| Control | Use case | Caution |
|---|---|---|
| Industrial DMZ | controlled exchange between enterprise and OT | do not allow flat routing through DMZ |
| Firewall allowlists | permit only required flows | must be based on process dependencies |
| Unidirectional gateway/data diode | one-way reporting or historian export | not applicable for control paths needing bidirectional communication |
| Jump server | controlled admin/engineering access | must be hardened and monitored |
| NAC/MAB/802.1X | port governance | many OT devices cannot support full 802.1X |
| VLAN/VRF/routing controls | logical separation | VLAN alone is not enough without ACL/firewall policy |
| Remote access broker | vendor maintenance | must enforce approval, MFA, logging, and time bounds |

## 25. OT Monitoring: Passive Network Monitoring, Protocol Baselines, Asset Discovery, Firewall Logs, Historian Logs, Engineering Workstation Logs, Endpoint Logs, Remote Access Logs, and SOC Handoff

OT monitoring must observe without disrupting.

Primary telemetry sources:

- passive network sensors on OT conduits;
- industrial firewall logs;
- remote access gateway logs;
- jump host session logs;
- historian logs;
- HMI/SCADA alarms and event logs;
- engineering workstation logs;
- controller change logs where available;
- Windows/Linux endpoint logs;
- domain/identity logs for OT domain assets;
- wireless/maintenance access logs;
- asset inventory changes;
- backup job and restore logs;
- physical access logs;
- time synchronization logs.

Baseline categories:

- normal controller peers;
- normal HMI-to-controller relationships;
- normal engineering workstation communication windows;
- normal historian collection paths;
- normal remote vendor access windows;
- normal protocol function patterns at a high level;
- normal firmware/project change windows;
- normal backup schedules;
- normal plant shift operations.

SOC handoff rule:

```text
OT alerts must include process context, asset criticality, zone/conduit, safety relevance, engineering owner, and recommended coordination path.
```

## 26. OT Backup and Recovery: Controller Logic, HMI/SCADA Configs, Historian/Database Backups, Engineering Workstation Images, Firmware, Vendor Installers, Licenses, Spare Hardware, Offline Documentation, and Restore Testing

OT recovery requires more than server backup.

Required backup categories:

- controller logic/project files;
- controller configuration exports;
- HMI/SCADA configuration;
- historian databases and configuration;
- recipe/batch configuration;
- engineering workstation images;
- vendor software installers;
- license files and activation records;
- firmware packages;
- network device configurations;
- firewall rules and segmentation diagrams;
- AD/domain configuration for OT services where applicable;
- remote-access gateway configuration;
- safety-system configuration documentation;
- spare hardware configuration;
- offline drawings and procedures.

Recovery validation must prove:

- backup is readable;
- version matches equipment;
- license dependencies are available;
- restore process is known by named roles;
- spare hardware or virtual recovery target exists;
- restored configuration matches approved baseline;
- process owner accepts function after restoration.

## 27. OT Incident Response: Safety-First Triage, Plant Operations Coordination, Isolation Decisions, Vendor Coordination, Regulatory/Safety Reporting Handoff, Manual Operations, and Recovery Validation

OT incident response must begin with process safety and plant operations.

Triage priorities:

1. Is there an active safety or environmental condition?
2. Is the process stable?
3. Are operators seeing trustworthy information?
4. Are control commands behaving as expected?
5. Is engineering access or remote access active unexpectedly?
6. Are controller logic/configuration states known?
7. Is containment safe for the process?
8. Are backups and recovery paths intact?

Response coordination roles:

- incident commander;
- plant operations lead;
- safety lead;
- OT engineering lead;
- cyber/IR lead;
- vendor/OEM contact;
- network/firewall lead;
- identity/access lead;
- legal/regulatory liaison;
- communications lead;
- business continuity lead.

Containment examples at defensive level:

- suspend nonessential remote access;
- restrict enterprise-to-OT conduits;
- increase monitoring on critical conduits;
- preserve engineering workstation state;
- isolate a compromised host only after process impact review;
- move to manual operation only under operations/safety authority;
- restore from known-good backup after validation.

## 28. OT Forensics and Evidence: Non-Disruptive Collection, Passive Capture, Configuration Comparison, Historian/Alarm Timelines, Engineering Workstation Artifacts, Change Logs, and Chain of Custody

OT evidence collection must avoid process disruption.

Evidence priorities:

- current process state and safety status;
- historian/alarm timeline;
- remote access sessions;
- engineering workstation activity;
- controller change logs where available;
- controller project/logic versions;
- HMI/SCADA event logs;
- firewall and conduit logs;
- passive network captures from sensors;
- jump host logs;
- identity and authentication logs;
- physical access logs;
- backup job/restore history;
- configuration snapshots;
- time synchronization records.

Evidence handling rules:

- coordinate with operations before touching OT assets;
- prefer logs and passive captures before host-level acquisition;
- preserve volatile information only if safe and approved;
- record who collected evidence, from where, and under whose operational approval;
- minimize sensitive process-data exposure;
- preserve chain of custody;
- avoid changing controller state during evidence collection.

## 29. OT Governance: Asset Owner, System Integrator, Vendor, Security Team, Policies, Exceptions, Procurement, Lifecycle, End-of-Support, Third-Party Access, and Supply Chain

OT governance is shared.

| Role | Governance responsibility |
|---|---|
| Asset owner | accepts operational and safety risk, owns process consequence. |
| Plant operations | approves operational impact, maintenance windows, and safe response. |
| OT engineering | owns technical process configuration and controller logic. |
| Safety team | owns safety-case impact and unsafe-intervention review. |
| Security team | defines security controls, monitoring, incident response, and risk evidence. |
| System integrator | implements and supports OT architecture under owner requirements. |
| Vendor/OEM | provides supported patches, firmware, advisories, and secure maintenance. |
| Procurement | embeds security and lifecycle requirements into contracts. |
| Audit/compliance | verifies evidence, exceptions, control operation, and policy adherence. |

Procurement requirements should include:

- secure remote support model;
- patch and vulnerability disclosure process;
- logging capabilities;
- backup/export capabilities;
- supported authentication and role separation;
- secure protocol support where feasible;
- hardening guide;
- lifecycle and end-of-support notice;
- SBOM/firmware integrity information where available;
- incident support obligations.

## 30. OT Telemetry Sources

Canonical OT telemetry sources:

| Source | Typical evidence |
|---|---|
| Passive OT network sensor | asset discovery, protocol baselines, unusual peers, new devices, new communications. |
| Industrial firewall | allowed/blocked flows, rule hits, cross-zone attempts, policy changes. |
| Remote access gateway | login, MFA, source IP, session time, target, recording metadata. |
| Jump server | user sessions, file transfer, admin activity, tool execution. |
| HMI/SCADA | operator actions, alarms, process events, communication loss. |
| Historian | process trends, write gaps, abnormal tag changes, query/export events. |
| Engineering workstation | project access, software execution, removable media, authentication. |
| Controller logs | mode/state/configuration changes where supported. |
| Windows/Linux endpoint logs | logons, service changes, process execution, patch state. |
| Identity systems | OT domain logons, group changes, privileged access, service account use. |
| Backup platform | job status, restore points, configuration backup success, restore tests. |
| Physical access systems | control room/cabinet access correlation. |
| Time synchronization | time drift, NTP/PTP anomalies, log correlation health. |

Telemetry must be normalized with asset criticality, process area, zone/conduit, owner, safety relevance, and approved maintenance windows.

## 31. OT Threat Model

OT threat modeling focuses on cyber-physical failure modes.

Threat categories:

- unauthorized remote access;
- compromised vendor account;
- enterprise-to-OT lateral movement;
- engineering workstation compromise;
- unauthorized logic/configuration change;
- loss of HMI visibility;
- historian integrity loss;
- ransomware affecting OT Windows/Linux assets;
- backup/configuration loss;
- unsafe patch or change;
- rogue or unmanaged device on OT network;
- removable media malware;
- shared credential misuse;
- supply-chain/vendor tool compromise;
- weak segmentation between process areas;
- safety-system independence erosion;
- loss of time synchronization;
- cloud/IIoT conduit exposure;
- insider error or unauthorized action.

Threat model questions:

- What physical process can be affected?
- What controller or HMI path changes the process?
- What engineering workstation can modify that controller?
- What identity can access that workstation?
- What remote-access path can reach that identity or host?
- What backups prove recoverability?
- What telemetry proves whether a change occurred?
- What operation would be unsafe to interrupt?

## 32. Threat-to-Control Matrix

| Threat / failure mode | Precondition | Likely impact | Preventive controls | Detective controls | Response controls | Recovery controls | Telemetry | Policy owner | Validation proof | Framework mapping |
|---|---|---|---|---|---|---|---|---|---|---|
| Unauthorized engineering change | engineering workstation or vendor path can reach controller | unsafe process behavior, quality loss, downtime | change approval, role separation, jump host, logic backup | engineering logs, controller change events, passive protocol baselines | freeze remote access, preserve workstation, compare logic | restore approved logic/config | EW logs, controller logs, historian | OT engineering | baseline comparison and change record | IEC 62443, NIST 800-82, NIST CSF PR/DE/RS |
| Vendor access misuse | remote access path exists without strong approval/session control | unauthorized maintenance, lateral movement | MFA, time-bound access, session recording, vendor policy | remote access logs, jump logs, file transfer logs | disable access, review sessions, rotate credentials | restore affected configs | VPN/broker/jump logs | OT owner/security | vendor access review | IEC 62443 SR, CIS Controls access |
| Weak IT/OT segmentation | flat routing or broad firewall rules | enterprise compromise reaches OT | industrial DMZ, allowlists, data diode where appropriate | firewall logs, passive sensor alerts | restrict conduits, isolate safely | rebuild segmentation from approved design | firewall/sensor logs | network/security | conduit review | NIST 800-53 SC, IEC 62443 zones/conduits |
| Ransomware on HMI/historian | Windows assets exposed or unmanaged | operator visibility loss, data loss, downtime | hardening, backups, segmentation, application control | endpoint logs, backup anomalies, SMB anomalies | contain affected host safely | restore image/data from clean backup | EDR, Windows, backup logs | OT/security | clean restore drill | NIST CSF RC, CIS Control 11 |
| Loss of controller backups | no verified logic/config backup | extended outage, unsafe manual recovery | backup policy, version control, offline copies | missed backup reports, inventory gaps | prioritize backup capture after stabilization | restore from approved backup or vendor source | backup reports | OT engineering | restore test | NIST 800-82, CP controls |
| Unauthorized device in OT zone | unmanaged switchport or maintenance port | unknown conduit, malware/lateral movement | port governance, physical controls, NAC where feasible | passive discovery, switch logs | locate and remove after operations approval | update inventory and diagrams | sensor/switch logs | operations/network | unknown asset review | CIS inventory, IEC zones |
| Unsafe active scanning | IT-style scan hits fragile devices | device crash, process disruption | scan approval policy, passive-first discovery | sensor logs, scanner detection | stop scan, assess process state | vendor/operations recovery | network/sensor logs | security/operations | scan exception records | NIST 800-82 safety constraints |
| Historian integrity loss | weak DB/access controls | false reporting, bad decisions | DB hardening, access review, segmentation | historian/audit logs, trend gaps | preserve data, stop unauthorized writes | restore/reconcile from backups | historian/DB logs | data/OT owner | historian integrity review | NIST CSF ID/PR/DE |
| Safety-system independence erosion | SIS shares access/control path with basic control | safety-case weakening | strict architecture review, safety governance | config reviews, change records | halt unsafe change, safety review | restore independent architecture | change/config evidence | safety owner | safety approval evidence | IEC 62443 + safety lifecycle |

## 33. Preventive Controls

Preventive controls reduce the chance of unsafe or unauthorized OT actions.

Core preventive controls:

- safety-first governance and cyber-physical risk assessment;
- OT asset inventory with process criticality;
- Purdue/zones/conduits segmentation;
- industrial DMZ for IT/OT exchange;
- firewall allowlists by process need;
- passive-first discovery policy;
- remote access through approved jump/broker architecture;
- MFA and time-bound vendor access;
- engineering workstation hardening;
- removable media control;
- application allowlisting where feasible;
- controller logic backup and approval workflow;
- strict management of change;
- controller/project version control;
- physical cabinet and control-room access;
- vendor support and patch governance;
- offline/immutable backups of configurations;
- local account and privileged access governance;
- separate OT administration identities;
- secure procurement requirements;
- safety-system independence.

## 34. Detective Controls and Telemetry Sources

Detective controls reveal abnormal state, access, traffic, or configuration.

Key detections:

- new OT asset or unknown MAC/IP in critical zone;
- engineering workstation communicating outside approved window;
- controller programming/configuration traffic outside approved change;
- new IT-to-OT connection path;
- remote vendor session outside approval;
- new firewall rule or broad allow rule;
- HMI communication loss or alarm storm correlated with cyber event;
- historian data gap or unusual bulk export;
- controller mode/state change where logged;
- OT backup failure or missing backup for critical controller;
- removable media use on engineering workstation;
- privileged group/account change affecting OT assets;
- time synchronization drift;
- failed logons or abnormal admin logons on OT hosts;
- unexpected DNS/proxy/cloud communication from OT zones.

Detection quality depends on asset context. A raw IP address is not enough. OT alerts must include zone, process area, owner, asset type, criticality, and change-window status.

## 35. Corrective, Recovery, and Compensating Controls

Corrective controls:

- revoke or suspend unauthorized access;
- revert unauthorized firewall or remote-access changes;
- restore controller project from approved version;
- repair incorrect HMI/SCADA configuration;
- remove unknown devices after operations approval;
- rotate exposed OT credentials;
- update asset records and diagrams;
- patch or isolate vulnerable assets with operations approval;
- revise vendor access contracts and procedures.

Recovery controls:

- known-good controller logic backups;
- engineering workstation golden images;
- historian and SCADA backups;
- spare hardware;
- offline documentation;
- recovery order by process criticality;
- tested manual operation procedures where applicable;
- clean-room recovery for compromised Windows/Linux assets;
- post-restore process validation.

Compensating controls:

- segmentation when patching is not possible;
- monitoring when authentication cannot be added;
- physical access control when device controls are weak;
- vendor-managed maintenance window when in-house patching is risky;
- jump-host controls when direct native controls are insufficient;
- offline backup when online backup cannot be trusted.

## 36. Required Policies and Standards

Minimum OT policy set:

- OT Cybersecurity Policy
- OT Asset Management Standard
- OT Network Segmentation Standard
- Industrial DMZ and Remote Access Standard
- Vendor Remote Access Policy
- OT Change Management / Management of Change Standard
- Engineering Workstation Security Standard
- Controller Logic Backup and Version Control Standard
- OT Backup and Recovery Standard
- Removable Media Standard
- OT Vulnerability and Patch Governance Standard
- OT Identity and Privileged Access Standard
- OT Monitoring and Logging Standard
- OT Incident Response Procedure
- OT Forensic Evidence Procedure
- Safety-System Cyber Governance Procedure
- OT Procurement and Supplier Security Standard
- OT Exception and Compensating Control Standard
- OT Wireless and Maintenance Access Standard
- OT Lab/Test Bench Validation Standard

## 37. Hardening Baseline

Hardening baseline by asset class:

| Asset class | Baseline controls |
|---|---|
| Engineering workstation | dedicated use, allowlisting, restricted internet/email, removable media control, backups, MFA via jump, vendor tool inventory. |
| HMI/operator workstation | least privilege, no general browsing/email, patch governance, endpoint protection where safe, local firewall, logging. |
| SCADA server | role separation, restricted admin, backup, endpoint hardening, service account review, segmentation. |
| Historian | database hardening, access review, controlled replication, audit logging, backup, export governance. |
| Controller/PLC/RTU | physical control, firmware inventory, approved logic baseline, engineering access restriction, monitoring. |
| Jump host | MFA, session recording, no uncontrolled file transfer, hardening, logging, short-lived access. |
| Industrial firewall | allowlist rules, change approval, rule review, logging, management-plane isolation. |
| Vendor access gateway | named users, MFA, approvals, time bounds, session evidence, periodic access review. |
| Backup repository | offline/immutable copies, separate credentials, restore tests, access logging. |

## 38. Configuration Review Checklist

Review questions:

- Is every OT asset tied to an owner, process area, and criticality?
- Are Purdue levels or zones/conduits documented and validated against traffic?
- Are IT/OT conduits minimized and logged?
- Are firewall rules allowlist-based and process-justified?
- Is vendor access time-bound, MFA-protected, approved, and logged?
- Are engineering workstations dedicated and hardened?
- Are controller logic/project backups current and restorable?
- Are unmanaged switches, maintenance ports, and wireless paths documented?
- Are all remote access paths visible to the SOC or OT monitoring team?
- Are safety-system changes reviewed by safety engineering?
- Are patch exceptions documented with compensating controls?
- Are OT backups separated from normal IT administrative compromise?
- Are historian and alarm logs retained long enough for incident reconstruction?
- Are active scans prohibited unless explicitly approved by OT operations?
- Are third-party contracts aligned with incident support and evidence requirements?

## 39. Detection Logic Categories

High-level OT detection categories:

- **Asset anomaly** — unknown device, changed fingerprint, new firmware version, new communication endpoint.
- **Conduit anomaly** — traffic between zones not documented or outside maintenance window.
- **Engineering activity anomaly** — project download/upload, programming traffic, engineering tool use outside approval.
- **Remote access anomaly** — vendor login without approved ticket, unusual source, long session, file transfer.
- **Authentication anomaly** — failed logons, stale vendor account use, privileged group change.
- **Configuration anomaly** — firewall rule change, controller mode change, HMI/SCADA configuration drift.
- **Process visibility anomaly** — historian gap, alarm flood, HMI loss, time sync drift.
- **Backup anomaly** — missed controller backup, repository access, restore failure.
- **Endpoint anomaly** — malware detection, service change, suspicious process on OT Windows/Linux host.
- **Physical/cyber correlation** — cabinet access followed by network/device change.

Detection outputs should be descriptive and operationally safe, not automatic disruptive containment without approval.

## 40. Incident Response Considerations

OT response differs from IT response because containment can affect the physical process.

Key considerations:

- coordinate with operations before containment;
- identify safety state first;
- avoid powering off or isolating assets that maintain process stability;
- preserve engineering project files and logic state;
- involve vendor/OEM when proprietary systems are affected;
- use passive evidence collection first;
- treat alarms and historian data as operational evidence;
- validate backups before restoration;
- review whether manual operation is safe and authorized;
- communicate in operational language, not only cybersecurity language;
- document decisions, approvals, and safety constraints.

Response decision classes:

| Decision | Required approval |
|---|---|
| disable vendor access | security + operations, emergency exception allowed |
| block IT/OT conduit | network + operations + incident commander |
| isolate HMI/SCADA host | operations + OT engineering |
| restore controller logic | OT engineering + operations + safety where relevant |
| collect volatile evidence from OT host | IR + operations approval |
| move to manual operation | plant operations + safety authority |

## 41. Forensics and Evidence Considerations

OT evidence is fragile and often distributed across process, cyber, physical, and vendor systems.

Evidence collection principles:

- minimize interaction with controllers;
- collect from monitoring platforms, historians, jump hosts, and logs before touching field devices;
- preserve exact timestamps and time sources;
- capture asset diagrams and current network path evidence;
- record process state at time of cyber event;
- collect approvals and decision logs as evidence;
- preserve project file hashes and versions where possible;
- document any action that could alter system state.

Forensic timelines should combine:

```text
remote access
  + identity events
  + engineering workstation events
  + firewall/conduit logs
  + controller/config events
  + historian/alarm timeline
  + physical access
  + backup state
  + operations shift log
```

## 42. Validation and Safe Testing

Safe validation methods:

- tabletop exercises;
- architecture review;
- firewall rule review;
- passive traffic validation;
- asset inventory sampling;
- backup restore test in lab/test bench;
- engineering workstation hardening review;
- vendor access session review;
- remote access approval test;
- historian/alarm log retention review;
- controller backup comparison in approved environment;
- test-bench firmware validation;
- non-production protocol monitoring validation;
- documented walkthrough of recovery order.

Validation rules:

- never assume IT scanning is safe for OT;
- perform active tests only with formal OT approval and defined rollback;
- use vendor guidance for fragile devices;
- schedule tests during approved windows;
- include operations, safety, and engineering representatives;
- record evidence and lessons learned.

## 43. Lab-Safe Boundaries

Acceptable lab-safe activities:

- tabletop IR simulation;
- passive capture analysis using lab-generated or vendor sample traffic;
- review of reference Purdue diagrams;
- configuration-review exercises using sanitized examples;
- backup/restore procedure simulation on test systems;
- mock vendor-access approval workflow;
- offline logic baseline comparison using non-production artifacts;
- review of industrial firewall policy templates;
- SOC alert triage using synthetic OT telemetry.

Not acceptable without formal authority and production-safe planning:

- active scanning of production OT networks;
- controller connection attempts;
- protocol function testing on real process devices;
- safety-system interaction;
- remote access testing against vendor systems;
- reboot or isolation of production OT assets;
- destructive recovery tests;
- any activity that changes process state.

## 44. Framework and Control Mapping

Mapping format:

```text
Control family
  -> security requirement
  -> implementation evidence
  -> telemetry evidence
  -> validation method
  -> owner
```

### NIST CSF 2.0

Govern
  -> define OT risk ownership, safety authority, vendor governance, exceptions, and policy.
  -> OT policy set, risk register, owner matrix, exception approvals.
  -> governance review records.
  -> annual OT risk review and tabletop.
  -> asset owner / security governance.

Identify
  -> maintain OT asset, zone, conduit, process criticality, and dependency inventory.
  -> CMDB/OT inventory, diagrams, passive discovery reconciliation.
  -> new asset and traffic discovery logs.
  -> inventory sampling and conduit validation.
  -> OT engineering / operations.

Protect
  -> segment networks, secure remote access, harden workstations, govern changes, protect backups.
  -> firewall rules, jump-host design, backup evidence, change records.
  -> access logs, backup logs, policy change logs.
  -> access review, restore test, rule review.
  -> security / network / OT operations.

Detect
  -> monitor passive OT traffic, remote access, engineering changes, identities, historian anomalies.
  -> sensors, SIEM use cases, log retention.
  -> sensor/firewall/HMI/historian/identity alerts.
  -> alert simulation and tuning review.
  -> SOC / OT security.

Respond
  -> coordinate safety-first response and containment decisions.
  -> OT IR procedure, escalation matrix, vendor contacts.
  -> incident records and decision logs.
  -> tabletop and post-incident review.
  -> incident commander / operations.

Recover
  -> restore safe process operation from validated backups and configurations.
  -> recovery runbooks, controller backups, golden images.
  -> restore logs and validation records.
  -> recovery drills.
  -> BCDR / OT operations.

### IEC 62443

Zones and conduits
  -> group OT assets by security and process requirements and control inter-zone communication.
  -> zone/conduit diagrams, firewall rules, asset grouping.
  -> cross-zone flow logs.
  -> conduit review and passive validation.
  -> OT architecture / network.

Security levels
  -> define target protection strength based on risk and consequence.
  -> risk assessment and zone requirements.
  -> control evidence by zone.
  -> periodic reassessment.
  -> asset owner / security.

Lifecycle roles
  -> define asset owner, integrator, supplier, and maintenance responsibilities.
  -> contracts, RACI, procurement requirements.
  -> vendor access and change logs.
  -> supplier review.
  -> procurement / OT governance.

### NIST SP 800-82

OT-specific control tailoring
  -> adapt security controls to safety, reliability, and operational constraints.
  -> OT overlay controls, compensating controls, risk acceptance.
  -> control monitoring evidence.
  -> OT control assessment.
  -> security / operations.

### CIS Controls v8

Inventory and control of enterprise assets
  -> maintain complete OT asset inventory.
  -> passive inventory and vendor records.
  -> unknown device alerts.
  -> inventory sampling.
  -> OT asset owner.

Access control management
  -> govern remote access, vendor access, privileged OT accounts.
  -> account review, MFA, approvals.
  -> login and session logs.
  -> quarterly access review.
  -> IAM / OT security.

Data recovery
  -> maintain recoverable OT configurations and systems.
  -> backup jobs, restore tests, spare hardware records.
  -> backup success/failure logs.
  -> restore drill.
  -> BCDR / OT engineering.

Network monitoring and defense
  -> monitor OT conduits and industrial traffic passively.
  -> sensor placement and detections.
  -> protocol baselines and alerts.
  -> sensor validation.
  -> SOC / OT security.

### ISO/IEC 27001 Annex A

Access control, supplier relationships, logging, backup, change management, physical security, and secure configuration controls apply when adapted to OT safety and operational constraints.

### NIST 800-53 Families

- AC — access control for remote access, privileged accounts, vendors.
- AU — logging/audit for engineering changes, remote access, firewall events.
- CM — configuration and change management for controllers, HMIs, firewalls, workstations.
- CP — contingency planning and recovery for OT process restoration.
- IA — identity and authentication for OT users and services.
- IR — incident response for safety-first OT events.
- PE — physical/environmental protection for cabinets and control rooms.
- RA — risk assessment for cyber-physical consequence.
- SC — system and communications protection for zones/conduits.
- SI — system integrity for patching, malware defense, and monitoring.
- SR — supply-chain risk for vendors, integrators, and OEMs.

### MITRE ATT&CK for ICS

Use only defensively to map observed behavior, telemetry, and control coverage. Relevant defensive mapping areas include initial access paths, execution on engineering workstations, persistence on OT hosts, privilege misuse, lateral movement across IT/OT, collection of process information, command/control channels, inhibition of response functions, impairment of process control, and impact. Do not convert ATT&CK for ICS into an abuse guide.

## 45. Common Failures

- Treating OT as ordinary IT.
- Flat IT/OT routing.
- Unknown vendor remote access.
- Engineering workstations with email/web browsing.
- No current controller logic backups.
- No restore test for HMI/SCADA/historian.
- Shared vendor accounts with no attribution.
- No passive asset discovery.
- Firewall rules that are broad, old, or undocumented.
- Active scanning without OT approval.
- Patch exceptions with no compensating controls.
- Safety systems connected through convenience paths.
- Poor physical control of cabinets and maintenance ports.
- Historian treated as low-risk reporting system.
- No time synchronization validation.
- SOC alerts lacking process context.
- Vendor contracts without security and incident support requirements.

## 46. Common Mistakes

- Measuring OT risk only by CVSS without process consequence.
- Assuming Purdue diagrams match real traffic.
- Treating VLANs as complete segmentation.
- Blocking traffic without knowing process dependency.
- Ignoring serial/radio/fieldbus paths.
- Forgetting controller project files during backup design.
- Assuming the historian is only a data warehouse.
- Using domain admin accounts for routine OT administration.
- Allowing remote vendors persistent access.
- Letting patch fear become permanent inaction.
- Failing to inventory firmware and support status.
- Confusing safety controls with cybersecurity controls.
- Performing IR actions without operations approval.
- Over-collecting evidence and disrupting fragile assets.
- Creating OT policies that operations cannot execute.

## 47. Must-Memorize Facts

- OT security is safety-first, not firewall-first.
- A controller logic backup is a security control.
- Engineering workstations are high-impact assets.
- Passive monitoring is preferred for production OT discovery.
- Purdue is a reference model, not a complete security design.
- Zones and conduits express security boundaries and allowed communications.
- Legacy protocols often rely on network and operational controls for protection.
- Vendor remote access must be time-bound, approved, MFA-protected, and logged.
- Active scanning can be unsafe on fragile OT networks.
- Historians are both operational evidence and data-security assets.
- Safety systems must preserve independence and safety lifecycle governance.
- OT incident response must coordinate with operations before containment.
- Backups must include project files, firmware, licenses, and spare hardware information.
- OT exceptions must have compensating controls and business/safety owner acceptance.

## 48. Interview / Exam Points

Strong answers mention:

- safety-first decision-making;
- Purdue levels and industrial DMZ;
- IEC 62443 zones and conduits;
- passive monitoring over active scanning;
- engineering workstation criticality;
- controller logic backup and change control;
- vendor remote access governance;
- historian and alarm evidence;
- OT patch constraints and compensating controls;
- difference between IT availability and OT safety;
- incident response coordination with plant operations;
- safety-system independence;
- framework anchors: NIST SP 800-82, IEC 62443, NIST CSF, CIS Controls, MITRE ATT&CK for ICS.

Weak answers say only:

- “install antivirus on PLCs”;
- “scan all devices”;
- “patch everything immediately”;
- “just put a firewall between IT and OT”;
- “Purdue solves OT security”;
- “availability is always the only OT priority.”

## 49. Expert-Level Insights

- The real OT control plane is the chain of people, engineering tools, project files, remote access paths, and controller permissions that can change process behavior.
- Historian replication can be a quiet bridge between OT and enterprise analytics; it must be governed as a conduit.
- A flat vendor support path can be more dangerous than an internet-facing IT service because it may terminate near engineering functions.
- OT asset inventory without process criticality is incomplete; criticality determines response and recovery priority.
- Safety and security are aligned only when changes preserve the safety case; cybersecurity controls can create safety risk if deployed blindly.
- Controller recovery often fails because organizations backup servers but not project files, firmware, licenses, or engineering tool versions.
- Passive monitoring is not passive governance; detections must be tied to owners, response paths, and maintenance windows.
- Many OT “vulnerabilities” are architecture failures: ungoverned conduits, shared accounts, absent backups, unmanaged remote access, and missing change evidence.
- The best OT architecture gives operations confidence that security controls will not surprise the process.

## 50. Generation Boundaries and Unsafe Content Restrictions

This CKV intentionally does not include:

- PLC manipulation;
- protocol abuse;
- ladder-logic abuse;
- unsafe scanning procedures;
- disruption testing;
- firmware exploitation;
- controller exploitation;
- safety-system bypass;
- unauthorized remote-access workflows;
- process-disruption scenarios;
- offensive OT playbooks.

Allowed defensive content:

- architecture and segmentation design;
- passive monitoring and validation;
- change-management evidence;
- safe tabletop exercises;
- backup/recovery planning;
- remote-access governance;
- incident coordination;
- forensics and evidence preservation;
- control mapping;
- safe lab-only conceptual validation.

## 51. Quick Reference Tables

### OT asset criticality quick map

| Asset | Why critical | Primary control |
|---|---|---|
| Engineering workstation | can change controller logic | hardening, access control, backups, monitoring |
| PLC/RTU/IED | direct control path | physical control, logic baseline, segmentation |
| HMI | operator decision path | integrity, access, logging, patch governance |
| SCADA server | supervisory coordination | hardening, redundancy, backups, monitoring |
| Historian | process evidence and analytics | DB security, integrity, access review |
| Remote access gateway | external conduit | MFA, approval, session logging |
| Industrial firewall | zone boundary | allowlists, change control, logging |
| Backup repository | recovery dependency | immutability/offline, separation, restore tests |

### Safe validation quick map

| Validation goal | Safe method | Avoid |
|---|---|---|
| asset discovery | passive monitoring and record reconciliation | active scanning without approval |
| segmentation | firewall/routing review and passive flow validation | production disruption testing |
| remote access | approval/session-log review | unauthorized login attempts |
| controller integrity | approved baseline comparison | controller manipulation |
| backups | restore to test bench or isolated environment | destructive production tests |
| IR readiness | tabletop with operations/safety | surprise containment drills |

### OT evidence quick map

| Evidence | Source |
|---|---|
| process state | historian, alarms, operator logs |
| engineering change | engineering workstation, project files, controller logs |
| remote access | VPN/broker/jump/session logs |
| conduit activity | firewall, passive sensors |
| identity activity | AD/local logs, MFA logs, vendor portal logs |
| recovery state | backup jobs, restore tests, asset backup status |
| physical context | access control, shift logs, maintenance records |

## 52. Final Engineering Checklist

Use this checklist to review OT security architecture:

- [ ] Every OT asset has owner, location, process criticality, zone, firmware/support status, and backup status.
- [ ] Purdue or equivalent architecture is validated against real traffic and remote-access paths.
- [ ] Zones and conduits are documented with owners, allowed flows, and validation evidence.
- [ ] Industrial DMZ mediates enterprise/OT exchange.
- [ ] Remote access is approved, MFA-protected, time-bound, monitored, and periodically reviewed.
- [ ] Engineering workstations are hardened, dedicated, monitored, and backed up.
- [ ] Controller logic/project files are versioned, backed up, and recoverable.
- [ ] Safety-system independence and change governance are preserved.
- [ ] Active scanning is prohibited unless formally approved and safety-reviewed.
- [ ] Patch constraints are documented with compensating controls.
- [ ] Historians, HMIs, and SCADA systems have backups, access control, and logging.
- [ ] OT firewalls have allowlist rules tied to process needs.
- [ ] Passive monitoring covers critical conduits.
- [ ] SOC alerts include OT asset context, process criticality, and owner.
- [ ] Backup includes firmware, licenses, project files, golden images, and spare-hardware needs.
- [ ] Incident response runbooks require operations and safety coordination.
- [ ] Forensic procedures prioritize non-disruptive evidence collection.
- [ ] Vendor contracts include security, patch, access, and incident support requirements.
- [ ] OT exceptions include risk acceptance, compensating controls, review date, and owner.
- [ ] Recovery drills prove safe restoration, not only file availability.


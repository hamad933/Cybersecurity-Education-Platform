# CKV-017 — Network Design, Segmentation, DMZs, and Hard Controls

## 1. Purpose

Network design is the security discipline of arranging connectivity so that business communication is possible, unnecessary reachability is impossible or difficult, and critical paths are enforceable, observable, reviewable, and recoverable.

This file gives the canonical mental model for secure network architecture:

- define zones before rules;
- define trust boundaries before tools;
- define allowed paths before firewall objects;
- define evidence before assuming a control works;
- define recovery paths before accepting a design as production-ready.

Security engineering uses network design to answer five questions:

1. **What can talk to what?**
2. **Why is that path needed?**
3. **Where is the path enforced?**
4. **Where is the path observed?**
5. **How can we prove the path still matches the intended design?**

A network diagram is not enough. A secure network design must produce enforceable boundaries, explicit allowed paths, ownership, telemetry, validation tests, and change evidence.

## 2. Core Definition

**Network design** is the intentional structure of networks, zones, routes, enforcement points, management paths, and telemetry so that systems communicate through approved, controlled, and provable paths.

**Segmentation** is the separation of assets, users, services, and traffic into security domains with controlled communication between them.

**A DMZ** is a boundary zone used to expose selected services to less trusted networks while preventing direct access from those less trusted networks to internal protected zones.

**A hard control** is a technical enforcement mechanism that prevents, blocks, constrains, or forces traffic behavior. It is stronger than a guideline, label, diagram, or policy statement because it changes what the network can actually do.

Canonical equation:

```text
Secure Network Design
= Zones
+ Trust Boundaries
+ Explicit Conduits
+ Enforcement Points
+ Management-Plane Isolation
+ Telemetry
+ Validation
+ Change Control
```

## 3. Why Network Design Matters for Security

Network design matters because most compromise paths are not created by a single vulnerable host. They are created by reachable paths between weak, exposed, or overprivileged systems.

A poor design allows:

- user workstations to reach server administration ports;
- compromised endpoints to scan internal networks freely;
- public-facing systems to reach internal databases directly;
- guest or IoT devices to share trust with corporate systems;
- management interfaces to be reachable from ordinary user segments;
- backup, identity, logging, and monitoring systems to sit in the same blast radius as normal workloads;
- emergency exceptions to become permanent hidden paths;
- cloud, VPN, wireless, and branch networks to bypass inspection;
- telemetry gaps that make the real path impossible to prove.

A good design limits damage even when prevention fails:

- compromise of one endpoint does not imply compromise of every endpoint;
- compromise of a public-facing service does not imply direct internal access;
- compromise of a user account does not imply management-plane access;
- compromise of a server tier does not imply access to identity, backup, or administration systems;
- attackers must cross explicit choke points where controls and logs exist;
- containment can be applied at known boundaries.

Security value of design:

- **prevention:** removes unnecessary paths;
- **detection:** concentrates telemetry at meaningful boundaries;
- **response:** gives defenders containment points;
- **recovery:** supports restoration of known-good paths;
- **audit:** provides evidence that allowed paths are governed.

## 4. Network Architecture Mental Model

Think of network architecture as a set of contracts, not as a drawing of cables and devices.

Core objects:

| Object            | Meaning                                                                | Security Question                       |
| ----------------- | ---------------------------------------------------------------------- | --------------------------------------- |
| Asset             | A system, service, workload, user population, or network component     | What must be protected?                 |
| Zone              | A group of assets with similar trust, function, risk, or control needs | What belongs together?                  |
| Boundary          | A point where trust or policy changes                                  | Where must communication be controlled? |
| Conduit           | An approved communication path between zones                           | What is allowed and why?                |
| Enforcement point | A technical control that permits, denies, inspects, or shapes traffic  | Where is the decision made?             |
| Telemetry point   | A source of evidence about allowed, denied, or observed traffic        | Where is the proof?                     |
| Dependency        | A service required for another service to function                     | What breaks if this path is removed?    |
| Owner             | Person/team accountable for a zone, asset, path, or rule               | Who can approve or remove it?           |

Architecture reasoning order:

1. Identify critical services and users.
2. Group assets into zones.
3. Mark trust boundaries.
4. Define required paths as conduits.
5. Remove implicit any-to-any communication.
6. Place enforcement at boundary crossings.
7. Place telemetry where decisions and traffic can be proven.
8. Validate allow and deny behavior.
9. Monitor drift.
10. Govern changes.

Expert shortcut:

```text
Route = reachability.
Policy = permission.
Telemetry = proof.
Validation = assurance.
```

A route alone does not mean traffic should be allowed. A firewall rule alone does not prove traffic is safe. A diagram alone does not prove that the deployed network matches the intended design.

## 5. Trust Boundaries, Zones, and Conduits

A **trust boundary** exists wherever two systems, networks, users, applications, or administrative domains have different security assumptions.

Examples:

- Internet ↔ public edge;
- public edge ↔ DMZ;
- DMZ ↔ internal server zone;
- user zone ↔ server zone;
- server zone ↔ database zone;
- production ↔ development;
- corporate endpoints ↔ guest devices;
- IT ↔ OT;
- cloud VPC/VNet ↔ on-premises network;
- remote access landing zone ↔ internal services;
- normal user plane ↔ management plane;
- application plane ↔ backup plane;
- business network ↔ security monitoring network.

A **zone** is not just a subnet. A zone is a security domain. It should have a defined purpose, owner, asset membership, trust level, allowed ingress, allowed egress, management model, telemetry requirements, and exception process.

Common zone attributes:

| Attribute         | Required Meaning                                    |
| ----------------- | --------------------------------------------------- |
| Name              | Stable business/security name, not only VLAN number |
| Purpose           | Why the zone exists                                 |
| Asset classes     | What belongs in the zone                            |
| Trust level       | What assumptions are allowed                        |
| Data sensitivity  | What data may be processed or stored                |
| Allowed ingress   | Who may initiate traffic into the zone              |
| Allowed egress    | What systems in the zone may initiate outbound      |
| Management path   | How administration is performed                     |
| Enforcement point | Where traffic decisions happen                      |
| Telemetry         | What proves communication and denial behavior       |
| Owner             | Who approves membership and paths                   |

A **conduit** is an approved path between zones. It must be more specific than “network A can reach network B.”

A mature conduit record includes:

- source zone;
- destination zone;
- initiating side;
- protocol/service;
- destination port or application identity;
- user or workload identity where available;
- business reason;
- asset owner;
- data classification;
- enforcement control;
- logging requirement;
- allowed schedule if relevant;
- expiry or review date;
- dependency justification;
- rollback plan;
- validation test.

A conduit without an owner, reason, log source, and review date becomes uncontrolled trust.

## 6. Segmentation Design Principles

Segmentation is not achieved by labels. It is achieved by enforced and validated communication limits.

Core principles:

### 6.1 Internal Is Not Trusted

“Internal network” is not a security permission. Internal systems can be compromised, misconfigured, unmanaged, unmanaged-by-security, or controlled by an attacker.

Design rule:

```text
Every meaningful zone crossing requires explicit permission and evidence.
```

### 6.2 Default Deny Between Zones

The default state between different zones should be deny. Allowed communication should be explicitly justified and minimal.

Default deny means:

- no broad any-to-any between zones;
- no user-zone access to administrative ports by default;
- no public-zone access to private backends except through approved application paths;
- no server egress except required dependencies;
- no guest/IoT access to internal services except tightly controlled exceptions;
- no management interface access from normal user segments.

### 6.3 Segment by Risk, Function, Trust, and Blast Radius

Do not segment only by department or IP convenience.

Useful segmentation drivers:

- exposure: public-facing vs internal-only;
- function: users, applications, databases, identity, backups, management;
- sensitivity: regulated data, credentials, business-critical systems;
- trust level: managed endpoints, unmanaged devices, guest systems;
- operational domain: IT, OT, IoT, cloud, remote access;
- lifecycle: development, test, staging, production;
- blast radius: what should not be compromised together.

### 6.4 Enforce at the Right Layer

Segmentation can involve multiple layers:

| Layer                    | Segmentation Role                                                       |
| ------------------------ | ----------------------------------------------------------------------- |
| Physical/logical network | Separates media, VLANs, VRFs, overlays, fabrics                         |
| Routing                  | Defines reachability boundaries                                         |
| Stateful policy          | Allows or denies sessions between zones                                 |
| Application proxy        | Mediates application-layer access                                       |
| Identity-aware access    | Adds user/device/workload context                                       |
| Host/workload control    | Limits east-west communication near the asset                           |
| Cloud-native control     | Applies policy to virtual networks, workloads, services, and identities |

No single layer is enough for all risks.

### 6.5 Avoid Transitive Trust

Transitive trust happens when Zone A cannot directly reach Zone C, but can reach Zone B, and Zone B can reach Zone C in a way that creates an unintended bridge.

Examples:

- user workstations can reach a file server, and that file server can reach database administration ports;
- development systems can reach shared CI/CD infrastructure that can deploy to production;
- remote access users can reach a jump host that is not strongly controlled;
- monitoring or backup agents can reach many zones with excessive privilege;
- DNS, SMB, RDP, WinRM, SSH, or SNMP becomes a bridge across boundaries.

Design rule:

```text
A path is safe only if every hop in the dependency chain is safe.
```

### 6.6 Keep Shared Services From Becoming Universal Bridges

Shared services are necessary, but they are dangerous when they create broad reachability.

High-risk shared services:

- DNS resolvers;
- directory services;
- PKI and certificate services;
- backup platforms;
- monitoring collectors;
- log collectors;
- update repositories;
- file shares;
- print services;
- virtualization management;
- CI/CD runners;
- automation platforms;
- remote management tools.

Design principle:

```text
Shared service does not mean shared trust.
```

Shared services need controlled clients, restricted administrative access, strong logging, and separate management paths.

## 7. North-South and East-West Traffic

**North-south traffic** generally means traffic entering or leaving a network, data center, cloud environment, or major security domain.

Examples:

- Internet user → public web application;
- remote user → VPN/ZTNA landing zone;
- branch office → data center service;
- workload → external API;
- internal service → SaaS provider.

**East-west traffic** generally means lateral traffic between internal systems, workloads, tiers, or zones.

Examples:

- workstation → server;
- application server → database server;
- server → server;
- VM → VM;
- container → container;
- cloud workload → cloud workload;
- identity service → application service.

Security implications:

| Traffic Type       | Common Design Error                             | Security Consequence                                   |
| ------------------ | ----------------------------------------------- | ------------------------------------------------------ |
| North-south        | Strong edge, weak internal design               | Public compromise leads to internal movement           |
| East-west          | Internal any-to-any                             | Ransomware and lateral movement spread quickly         |
| Server egress      | Servers can browse or connect out freely        | C2, exfiltration, and supply-chain abuse become easier |
| Management traffic | Admin protocols reachable broadly               | Attackers pivot into control planes                    |
| Backup traffic     | Backup systems reachable from compromised zones | Recovery capability is destroyed during ransomware     |

Modern environments often have more east-west risk than north-south risk. A single perimeter is not enough when applications, identity, management, backups, cloud, and users all communicate internally.

## 8. DMZ Architecture Patterns

A **DMZ** is a controlled exposure zone. It is designed for services that must be reachable from less trusted networks but should not expose internal protected zones directly.

DMZ design goals:

- publish selected services safely;
- separate public exposure from internal assets;
- reduce direct inbound paths to private systems;
- terminate or mediate traffic at controlled front doors;
- log and inspect ingress/egress at meaningful boundaries;
- prevent compromised public systems from becoming internal footholds.

Common DMZ patterns:

### 8.1 Single DMZ Interface / Three-Legged Boundary

A boundary device or policy layer has at least three logical sides:

```text
Internet / untrusted
        |
      DMZ
        |
Internal / trusted
```

Security properties:

- public traffic is directed only to DMZ services;
- DMZ-to-internal is more restricted than Internet-to-DMZ;
- internal administration of DMZ systems should use a separate administrative path;
- outbound access from DMZ is explicitly limited.

### 8.2 Screened Host

A hardened public-facing host is exposed through a filtering layer. It is simple, but the exposed host becomes highly sensitive because compromise may create a foothold near internal services.

Use only when the exposure is minimal and compensating controls are strong.

### 8.3 Screened Subnet

A DMZ subnet is placed between an external boundary and an internal boundary.

```text
Internet
   |
External enforcement
   |
DMZ subnet
   |
Internal enforcement
   |
Internal zones
```

Security properties:

- compromise of a DMZ host still requires crossing another boundary to reach internal systems;
- inbound and outbound rules can differ;
- telemetry exists at both sides;
- internal services are not directly exposed to the Internet.

### 8.4 Multi-Tier DMZ

A multi-tier DMZ separates public front ends, application/API tiers, transaction tiers, and private backends.

Example:

```text
Internet
  -> Public reverse proxy / WAF tier
  -> Web/API tier
  -> Transaction or service tier
  -> Private backend zone
```

Security objective:

- each tier has only the minimum required path to the next tier;
- backends never receive arbitrary Internet-originated traffic;
- administrative paths do not follow public application paths;
- logs at each tier support incident reconstruction.

### 8.5 DMZ Anti-Patterns

Avoid:

- placing databases directly in a public DMZ;
- allowing DMZ hosts broad outbound access;
- managing DMZ systems from normal user networks;
- allowing DMZ systems to initiate arbitrary internal connections;
- treating a reverse proxy as a complete segmentation strategy;
- mixing unrelated services with different risk levels in one DMZ;
- allowing temporary troubleshooting rules to become permanent;
- deploying a DMZ without deny tests and telemetry.

A DMZ is not a magic safe zone. It is a controlled exposure pattern.

## 9. Routed Boundaries and Policy Enforcement Points

A **routed boundary** exists where traffic must cross a Layer-3 or logical routing decision to move between zones.

A **policy enforcement point** is where communication is technically allowed, denied, inspected, redirected, rate-limited, authenticated, or logged.

Examples of policy enforcement points:

- inter-zone firewall rules;
- router ACLs;
- proxy controls;
- VPN/ZTNA access policies;
- NAC authorization;
- host firewall rules;
- microsegmentation policy;
- cloud security groups or network ACLs;
- service mesh policy;
- API gateway policy;
- management-plane ACLs.

Important distinction:

```text
Routing decides whether a path exists.
Policy decides whether a path is permitted.
```

Common failure modes:

- route leak creates reachability around a firewall;
- asymmetric routing bypasses stateful enforcement or logging;
- backup links bypass security controls;
- cloud peering creates unintended transitive paths;
- VPN split tunneling exposes unmanaged paths;
- temporary static route creates a hidden conduit;
- SD-WAN path selection changes the inspection point;
- overlay networks bypass underlay controls;
- dual-homed systems bridge zones;
- unmanaged switches or wireless bridges connect isolated networks.

Design requirements:

- document every routed boundary;
- know where route exchange occurs;
- avoid unreviewed dynamic adjacency across trust boundaries;
- validate that backup/failover paths preserve enforcement;
- verify that traffic crosses expected inspection points;
- log both allowed and denied inter-zone attempts where meaningful;
- test path symmetry where stateful controls require it.

## 10. Default-Deny and Allowed-Path Governance

Default-deny design is not merely a firewall setting. It is an operating model.

A mature allowed-path process treats connectivity as a governed asset.

Each allowed path should answer:

| Question       | Required Answer                                                     |
| -------------- | ------------------------------------------------------------------- |
| Who initiates? | Source zone, source asset class, user/workload identity if possible |
| Who receives?  | Destination zone, destination asset/service                         |
| What service?  | Protocol, port, application, API, or named service                  |
| Why needed?    | Business function or technical dependency                           |
| Who owns it?   | Requester, service owner, control owner                             |
| How long?      | Permanent with review, temporary, emergency, or expiring            |
| How enforced?  | PEP, rule/policy reference, identity condition                      |
| How logged?    | Log source, event type, retention, correlation key                  |
| How validated? | Must-allow and must-deny tests                                      |
| How removed?   | Rollback and decommission process                                   |

Allowed-path governance prevents rule entropy.

Rule entropy indicators:

- many rules with no owner;
- rules named after old projects or tickets that no longer exist;
- broad source/destination objects;
- `any` service where only one service is needed;
- temporary rules with no expiry;
- high-risk ports allowed from user zones;
- no hit-count review;
- no deny logging for critical boundaries;
- no record of why the path exists;
- exceptions outside change control.

Design rule:

```text
Every allowed path must be owned, justified, logged, reviewed, and removable.
```

Default deny must include egress as well as ingress. Many organizations restrict inbound traffic but allow nearly unlimited outbound traffic from servers and endpoints. That weakens malware containment, exfiltration prevention, and attribution.

## 11. User, Server, Guest, IoT, OT, Cloud, and Admin Zones

Secure network design uses zones that reflect risk and function.

### 11.1 User Zones

User zones contain ordinary endpoints used for interactive work.

Typical controls:

- restricted access to server administration ports;
- limited east-west workstation communication;
- forced web/DNS egress through approved services;
- device posture or NAC where available;
- endpoint telemetry;
- separation between corporate-managed and unmanaged devices.

Design warning:

```text
User zones are high-compromise-probability zones.
```

They should not be trusted to administer infrastructure or directly access crown-jewel services.

### 11.2 Server Zones

Server zones contain application and infrastructure services.

Typical sub-zones:

- application servers;
- database servers;
- shared services;
- identity services;
- management services;
- backup services;
- monitoring/logging services;
- production, staging, development, and test environments.

Design warning:

```text
A server zone with any-to-any communication is not segmented.
```

Server-to-server flows should be intentionally mapped and validated.

### 11.3 Guest Zones

Guest zones support visitors, contractors, unmanaged devices, or BYOD systems.

Design baseline:

- Internet-only by default;
- no direct access to internal networks;
- strong isolation from corporate endpoints;
- limited DNS/DHCP dependency exposure;
- separate wireless SSID or access policy;
- strict logging and abuse handling.

Guest networks must not become a soft path into corporate networks.

### 11.4 IoT Zones

IoT zones contain cameras, printers, sensors, badge readers, conference systems, building systems, and similar devices.

Design baseline:

- isolate from user and server zones;
- deny device-to-device where not required;
- restrict management to approved admin paths;
- restrict outbound access;
- monitor abnormal east-west and Internet traffic;
- separate vendors, device classes, or risk levels when needed.

IoT devices often have weak patching, weak authentication, and long lifecycles. Treat them as high-risk even if they are physically internal.

### 11.5 OT Zones

OT zones include industrial control systems, SCADA, PLCs, engineering workstations, safety systems, and plant networks.

Design baseline:

- strong separation from IT networks;
- tightly governed conduits;
- unidirectional or mediated flows where appropriate;
- vendor access through controlled jump paths;
- conservative change windows;
- visibility that does not disrupt operations;
- strict dependency mapping.

OT segmentation is driven by safety, availability, and process integrity as much as confidentiality.

### 11.6 Cloud Zones

Cloud zones may be virtual networks, accounts, projects, subscriptions, VPCs/VNets, subnets, security groups, route tables, service endpoints, private endpoints, and workload identities.

Design baseline:

- separate environments and trust levels;
- control Internet ingress and egress;
- restrict east-west workload communication;
- isolate management access;
- avoid broad peering and transitive routing;
- log control-plane and data-plane changes;
- validate routes and security policies after deployment.

Cloud networks are programmable. That makes drift and unintended reachability faster unless guardrails and validation exist.

### 11.7 Admin and Management Zones

Admin and management zones are the most sensitive network zones because they control the systems that control everything else.

Design baseline:

- separated management network or VRF/VLAN;
- no direct management from user zones;
- jump hosts or privileged access workstations;
- MFA and strong AAA;
- session logging where appropriate;
- privileged protocol restrictions;
- device management interfaces reachable only from management paths;
- emergency break-glass process with evidence;
- restricted access to backup, virtualization, network devices, identity infrastructure, and security tools.

Admin zones should be treated as crown-jewel zones.

## 12. Management-Plane Isolation

The **management plane** contains the interfaces, protocols, accounts, APIs, and systems used to configure, administer, monitor, and automate infrastructure.

Examples:

- network device SSH/HTTPS/SNMP/NETCONF/RESTCONF;
- hypervisor and virtualization management;
- cloud control-plane APIs;
- firewall and security appliance management;
- backup console management;
- directory and PKI administration;
- EDR/SIEM/SOAR administration;
- Kubernetes control plane;
- storage management;
- out-of-band management interfaces;
- automation platforms.

Management-plane compromise is high-impact because it changes controls, visibility, routes, backups, identity, and response capability.

Management-plane isolation requirements:

- separate management zone from user and production data zones;
- restrict administrative access to approved jump or privileged workstations;
- deny management interfaces from ordinary subnets;
- use strong authentication and centralized authorization;
- log administrative sessions and configuration changes;
- enforce least privilege for administrators;
- separate break-glass access from daily access;
- protect automation credentials and service accounts;
- ensure management access survives outages but does not bypass security;
- test deny behavior from non-admin zones.

Critical rule:

```text
If users can reach management interfaces directly, segmentation is structurally weak.
```

Management-plane isolation is often more important than edge filtering because control-plane compromise lets attackers rewrite the environment.

## 13. Jump Hosts, Bastions, and Administrative Paths

A **jump host** or **bastion** is a controlled administrative intermediary used to access protected systems or management interfaces.

Security purpose:

- centralize privileged access;
- reduce direct admin exposure;
- enforce identity and MFA;
- constrain protocols;
- record or log sessions where appropriate;
- create a choke point for administrative actions;
- support emergency access without opening broad paths.

Good administrative path:

```text
Admin user
  -> privileged workstation or secure access service
  -> jump host / bastion
  -> management zone
  -> target system management interface
```

Bad administrative path:

```text
Any user workstation
  -> direct SSH/RDP/WinRM/HTTPS/SNMP
  -> servers, firewalls, hypervisors, network devices, backups
```

Jump host requirements:

- dedicated purpose;
- strong authentication;
- limited user group;
- no Internet browsing or email;
- no general productivity use;
- hardened configuration;
- monitored privileged sessions;
- restricted outbound destinations;
- patching and backup strategy;
- administrative separation between production, security, cloud, and OT where needed.

Common jump-host mistakes:

- using a jump host as a shared workstation;
- allowing file transfer without control;
- allowing admins to log in from unmanaged endpoints;
- failing to monitor commands or sessions;
- allowing the jump host to reach too many zones;
- putting the jump host in the same compromise domain as ordinary users;
- ignoring service accounts and automation paths.

A bastion is a choke point, not a universal permission bypass.

## 14. Choke Points and Hard Controls

A **choke point** is a location where important traffic must pass, making enforcement and observation possible.

Useful choke points:

- Internet edge;
- DMZ ingress and egress;
- remote access termination;
- inter-zone boundaries;
- server-to-database paths;
- identity service access paths;
- management-plane access paths;
- backup/replication paths;
- OT/IT boundary;
- cloud egress and ingress points;
- administrative jump paths;
- critical SaaS/private endpoint paths.

A **hard control** technically prevents or constrains behavior.

Examples:

- deny rule at an inter-zone boundary;
- route table that prevents reachability;
- ACL that restricts management access;
- NAC authorization that assigns a device to a restricted zone;
- proxy that forces authenticated egress;
- host firewall that blocks lateral ports;
- microsegmentation rule that restricts workload-to-workload traffic;
- cloud security group that allows only approved source identities or ranges;
- unidirectional gateway or data diode pattern where appropriate;
- private endpoint that avoids public exposure;
- disabled unused paths or ports.

Soft controls are not enough alone:

- a diagram;
- a policy document;
- a naming convention;
- a VLAN name;
- a spreadsheet;
- an informal agreement;
- a “do not use” instruction;
- a dashboard without enforcement.

Hard-control design principles:

1. Place controls where traffic must cross.
2. Prefer deny-by-default at trust boundaries.
3. Use identity and asset context where available.
4. Avoid single points of silent bypass.
5. Log control decisions.
6. Validate both allowed and denied paths.
7. Make exceptions expire.
8. Ensure failover paths preserve controls.

A hard control that cannot be tested is an assumption.

## 15. Dependency Mapping and Critical Paths

Dependency mapping identifies the services, systems, network paths, and control planes required for a business service to function securely.

Critical dependencies often include:

- DNS;
- DHCP;
- identity providers;
- directory services;
- PKI/certificate validation;
- NTP/time synchronization;
- databases;
- message queues;
- APIs;
- storage;
- backup and restore systems;
- logging and monitoring;
- EDR/SIEM/SOAR pipelines;
- update repositories;
- secrets management;
- load balancers;
- reverse proxies;
- cloud control planes;
- third-party/SaaS services;
- remote access systems.

Dependency map fields:

| Field                  | Purpose                                       |
| ---------------------- | --------------------------------------------- |
| Business service       | What depends on the path                      |
| Criticality            | Business impact if unavailable                |
| Source asset/zone      | Where communication starts                    |
| Destination asset/zone | Where communication ends                      |
| Protocol/service       | What communication is required                |
| Direction              | Which side initiates                          |
| Timing                 | Always-on, scheduled, event-driven, emergency |
| Security control       | How the path is enforced                      |
| Telemetry              | How the path is observed                      |
| Recovery need          | How restoration depends on the path           |
| Owner                  | Who approves changes                          |

Critical path examples:

- user login depends on DNS, directory services, time, and network reachability to identity systems;
- public web service depends on edge routing, reverse proxy, application tier, database tier, certificates, and logging;
- ransomware recovery depends on identity, backup console, backup repository, management network, storage access, and clean administrative workstations;
- OT monitoring depends on a safe one-way or tightly mediated path from plant networks to monitoring systems;
- cloud workload access depends on route tables, security groups, private endpoints, DNS, identity, and secrets.

Design rule:

```text
A segmentation design is incomplete until critical dependencies and recovery paths are mapped.
```

## 16. Sensor and Control Placement at Architecture Level

Security architecture must place controls and sensors intentionally. Tool deployment without placement reasoning creates blind spots.

Control placement asks:

- where can traffic be prevented;
- where can traffic be authenticated;
- where can traffic be rate-limited or shaped;
- where can egress be constrained;
- where can management access be blocked;
- where can high-value assets be isolated;
- where can emergency containment be applied.

Sensor placement asks:

- where can boundary decisions be logged;
- where can actual traffic be observed;
- where can flow data prove communication;
- where can identity attribution be captured;
- where can management actions be audited;
- where can east-west movement be detected;
- where can encrypted traffic metadata still be useful;
- where can false blind spots be avoided.

Architecture-level placement baseline:

| Location                   | Control / Visibility Goal                                                  |
| -------------------------- | -------------------------------------------------------------------------- |
| Internet edge              | Ingress/egress policy, exposure logging, external attack visibility        |
| DMZ boundaries             | Prove what reached public services and what DMZ systems reached internally |
| Remote access landing zone | User/device identity, posture, assigned access, session evidence           |
| Inter-zone boundaries      | East-west control and path evidence                                        |
| Identity zone              | Protect and monitor authentication-critical services                       |
| Management zone            | Protect administrative paths and infrastructure control                    |
| Backup zone                | Protect recovery capability and detect backup-path abuse                   |
| OT/IT boundary             | Maintain safety and tightly control plant connectivity                     |
| Cloud ingress/egress       | Validate public/private exposure, peering, and workload reachability       |
| Crown-jewel paths          | Higher-fidelity enforcement and independent telemetry                      |

Strong visibility uses more than one signal:

```text
Boundary decision log + independent traffic signal + endpoint/service evidence
```

Examples:

- firewall/session log plus flow log plus server log;
- proxy log plus DNS log plus endpoint process telemetry;
- VPN/ZTNA log plus identity log plus destination service log;
- cloud security group flow log plus workload log plus control-plane change log.

Never rely on one sensor at the edge as the complete security view.

## 17. Segmentation Validation and Provability

Segmentation must be tested. Untested segmentation is a belief.

Validation goals:

- prove required paths work;
- prove forbidden paths fail;
- prove traffic crosses expected enforcement points;
- prove logs exist for important decisions;
- prove backup/failover paths do not bypass controls;
- prove exceptions expire or are reviewed;
- prove the deployed state matches the approved design.

Validation types:

| Validation Type         | Example                                                    |
| ----------------------- | ---------------------------------------------------------- |
| Must-allow test         | Application server can reach required database port        |
| Must-deny test          | User subnet cannot reach database or management ports      |
| Path proof              | Traffic crosses the intended enforcement point             |
| Log proof               | Allowed/denied attempts create usable events               |
| Drift check             | Config/routing/rule state matches approved baseline        |
| Dependency test         | Critical service still works after restricting broad paths |
| Failover test           | Backup path preserves segmentation                         |
| Emergency rollback test | Control can be restored without opening broad trust        |

Segmentation validation artifacts:

- approved zone model;
- allowed-path register;
- rule/policy export;
- route table summary;
- network diagram with trust boundaries;
- test matrix of source/destination/protocol outcomes;
- sample allow/deny logs;
- flow log evidence;
- exception list with expiry;
- change tickets;
- rollback plan;
- current-state drift report.

Provability statement:

```text
A secure network design must be explainable, enforceable, observable, and testable.
```

Common validation schedule:

- before production release;
- after network change;
- after emergency exception;
- after firewall/routing migration;
- after cloud peering or VPN changes;
- after incident containment;
- during compliance review;
- periodically for critical conduits.

## 18. Design Review Checklist

Use this checklist before approving a network architecture or major connectivity change.

### 18.1 Zone Model

- Are all major asset classes assigned to zones?
- Are user, server, guest, IoT, OT, cloud, admin, backup, and identity zones separated where needed?
- Are production, development, and test separated where risk requires it?
- Are crown-jewel assets identified?
- Is each zone owned?
- Is zone membership controlled?

### 18.2 Trust Boundaries

- Where does trust change?
- Which boundaries separate Internet, DMZ, internal, management, and cloud environments?
- Which boundaries separate business units, regulated systems, or OT systems?
- Are trust boundaries enforced technically or only drawn on a diagram?

### 18.3 Allowed Paths

- What paths are required for business function?
- Who initiates each path?
- What protocol/service is required?
- Is the path too broad?
- Does the path have an owner and justification?
- Is there an expiry or review date?
- Are temporary paths marked as temporary?

### 18.4 Enforcement

- Where is the policy decision made?
- Is the boundary default-deny?
- Are management protocols blocked from user zones?
- Are server egress paths constrained?
- Do failover paths preserve enforcement?
- Are route leaks or alternate paths possible?
- Are dual-homed systems bridging zones?

### 18.5 Management Plane

- Are management interfaces isolated?
- Are admins forced through approved jump paths?
- Is MFA/AAA used?
- Are administrative sessions logged?
- Are automation/service accounts controlled?
- Is emergency access documented and monitored?

### 18.6 Telemetry

- Are allow and deny decisions logged at key boundaries?
- Are flow logs available for important paths?
- Are identity and device context captured where needed?
- Are logs time-synchronized?
- Are logs retained long enough for investigation?
- Is there independent visibility for crown-jewel conduits?

### 18.7 Validation

- Are must-allow tests defined?
- Are must-deny tests defined?
- Are tests automated where possible?
- Is there evidence that deployed state matches design?
- Are exceptions tracked and reviewed?
- Is drift monitored?

### 18.8 Recovery and Operations

- Can the design be restored from baseline?
- Are configuration backups protected?
- Are rollback plans tested?
- Can security controls fail without opening unsafe paths?
- Are operational teams able to troubleshoot without disabling controls permanently?

## 19. Common Network Design Mistakes

1. **Treating the perimeter as the only security boundary.**  
   The edge is one boundary, not the whole architecture.

2. **Using VLANs as if they are complete security boundaries.**  
   VLANs help separate broadcast domains, but inter-zone policy and validation determine security separation.

3. **Allowing internal any-to-any traffic.**  
   This enables lateral movement, ransomware spread, and uncontrolled discovery.

4. **Ignoring management-plane reachability.**  
   If ordinary user networks can reach administrative interfaces, attackers inherit a privileged path.

5. **Placing backup systems in the same blast radius as normal production.**  
   Ransomware often succeeds when recovery infrastructure is reachable from compromised zones.

6. **Allowing unrestricted server egress.**  
   Server-to-Internet traffic should be rare, justified, and logged.

7. **Building DMZs that can freely reach internal networks.**  
   A compromised DMZ host must not become a direct bridge to internal systems.

8. **Forgetting identity, DNS, NTP, PKI, and logging dependencies.**  
   Segmentation that breaks critical dependencies is bypassed or disabled under pressure.

9. **Allowing emergency rules to become permanent.**  
   Temporary access without expiry becomes hidden trust.

10. **Not testing deny behavior.**  
    Many teams test that applications work, but not that forbidden paths fail.

11. **Assuming cloud security groups and on-premises firewalls behave identically.**  
    Cloud routing, identity, service endpoints, and control-plane APIs change the design model.

12. **Ignoring asymmetric and failover paths.**  
    Backup connectivity can bypass controls if not designed and tested.

13. **Placing sensors where traffic is convenient instead of where trust changes.**  
    Visibility must follow boundaries, conduits, and crown jewels.

14. **Using broad shared service access.**  
    DNS, SMB, RDP, SSH, SNMP, WinRM, monitoring, backup, and automation can bridge zones.

15. **Lacking ownership.**  
    Paths without owners are hard to remove and easy to abuse.

16. **Confusing reachability with authorization.**  
    A route exists so traffic can move. A policy decides whether it should move.

17. **Designing for normal operation only.**  
    Secure design must handle failover, incidents, maintenance, restoration, and emergency access.

## 20. Must-Memorize Facts

- Network design is about controlled reachability, not just connectivity.
- A zone is a security domain, not merely a VLAN or subnet.
- A trust boundary exists wherever security assumptions change.
- A conduit is an approved communication path between zones.
- A route creates reachability; policy grants permission; telemetry provides proof.
- Internal networks are not automatically trusted.
- Default-deny between zones is the safest baseline.
- DMZs expose selected services while protecting internal zones from direct exposure.
- A compromised DMZ host should still have minimal internal reachability.
- East-west traffic is often the main lateral movement path.
- Management-plane access must be isolated from normal user and workload zones.
- Jump hosts are administrative choke points, not convenience servers.
- Hard controls technically prevent or constrain traffic behavior.
- Soft controls such as diagrams and naming conventions do not enforce boundaries by themselves.
- Shared services can become unintended bridges across zones.
- Backup, identity, logging, and management systems are crown-jewel infrastructure.
- Sensor placement must match choke points, boundaries, and critical paths.
- Segmentation is incomplete without must-allow and must-deny validation.
- Exceptions must expire or be reviewed.
- Failover paths must preserve security controls.
- Unrestricted egress weakens containment and attribution.
- Network design must produce evidence, not just diagrams.

## 21. Interview / Exam Points

### 21.1 Explain Segmentation

Strong answer:

> Segmentation separates assets and services into zones based on trust, function, sensitivity, and risk. Communication between zones is default-deny and allowed only through explicit, logged, reviewed, and validated conduits.

Weak answer:

> Segmentation means using VLANs.

Why weak: VLANs are one implementation mechanism, not the complete security design.

### 21.2 Explain DMZ

Strong answer:

> A DMZ is a controlled exposure zone for services that must be reachable from less trusted networks. It limits what external users can reach and limits what a compromised public-facing host can reach internally.

Key point:

```text
Internet-to-DMZ and DMZ-to-internal are different trust boundaries.
```

### 21.3 Explain Default Deny

Strong answer:

> Default deny means traffic between trust zones is blocked unless explicitly approved, owned, justified, logged, and tested. It applies to ingress, egress, east-west traffic, and management paths.

### 21.4 Explain Management-Plane Isolation

Strong answer:

> Management-plane isolation separates administrative access from ordinary user and workload traffic. Admins should use approved privileged paths such as jump hosts or privileged workstations, with strong authentication, authorization, logging, and deny tests from non-admin zones.

### 21.5 Explain Choke Points

Strong answer:

> Choke points are locations where important traffic must pass, allowing enforcement and observation. They are useful for segmentation, monitoring, incident containment, and evidence collection.

### 21.6 Explain Why Perimeter Is Not Enough

Strong answer:

> The perimeter controls Internet-facing traffic, but most environments have internal lateral movement, remote access, cloud peering, shared services, and management paths. Security boundaries must exist wherever trust changes, not only at the Internet edge.

### 21.7 Explain Sensor Placement

Strong answer:

> Sensors should be placed where they can prove important boundary behavior: edge, DMZ, remote access, inter-zone paths, identity, management, backup, OT/IT boundary, and crown-jewel conduits. One edge sensor cannot prove internal segmentation.

### 21.8 Explain Segmentation Validation

Strong answer:

> Validation proves both required communication and forbidden communication. It includes must-allow tests, must-deny tests, log evidence, path proof, drift checks, and failover validation.

### 21.9 Red Flags in a Design Review

- user subnets can reach server administration ports;
- DMZ systems can reach broad internal ranges;
- management interfaces are reachable from normal endpoints;
- server egress is wide open;
- guest and corporate networks share trust;
- cloud peering allows transitive reachability;
- backups are reachable from compromised production systems;
- no allowed-path owner exists;
- no deny tests exist;
- no current zone map exists;
- temporary exceptions have no expiry.

## 22. Expert-Level Insights

### 22.1 The Real Security Boundary Is the Enforced and Logged Path

A boundary drawn on a diagram is only a claim. The real boundary is where traffic is technically controlled and where the decision is observable.

Expert question:

```text
Show me the deny test and the log proving this boundary exists.
```

### 22.2 Segmentation Is a State Management Problem

Network design decays because routes, rules, assets, DNS names, cloud objects, VPNs, and exceptions change over time.

Security architecture must manage intended state vs actual state:

```text
Approved design
  vs
Current routes
  vs
Current rules
  vs
Current assets
  vs
Current observed flows
```

Drift is where many breaches find their path.

### 22.3 Management Plane Is Usually the Highest-Value Segmentation Problem

Many organizations focus on user-to-server segmentation while leaving device management, hypervisors, cloud consoles, backup platforms, and security tools reachable from broad networks.

The attacker does not need to attack every asset if they can control the systems that control the assets.

### 22.4 Allowed Paths Are Attack Paths Unless Governed

Attackers often use legitimate allowed communication:

- workstation to file share;
- server to database;
- admin workstation to management interface;
- CI/CD runner to production;
- monitoring agent to many systems;
- backup platform to all servers;
- DNS resolver to Internet;
- cloud peering to internal ranges.

Allowed does not mean safe. Allowed means it must be justified, monitored, and constrained.

### 22.5 Shared Services Need Their Own Threat Model

DNS, identity, PKI, logging, backup, update, monitoring, and automation services cross many zones. They are not ordinary infrastructure. They are trust multipliers.

Design implication:

```text
The more zones a service touches, the more it must be isolated, logged, and governed.
```

### 22.6 Egress Is a Segmentation Control

Ingress controls protect what enters. Egress controls determine what compromised systems can do next.

A mature design controls egress by zone:

- users use approved web/DNS paths;
- servers use minimal explicit dependencies;
- high-value systems have almost no outbound paths;
- DMZ systems cannot freely call internal networks or the Internet;
- OT systems use tightly mediated outbound channels;
- cloud workloads use governed private endpoints and approved egress.

### 22.7 Backup and Recovery Paths Are Security Paths

Recovery systems must be reachable during crisis but protected before crisis. This tension requires intentional design.

Bad design:

```text
Production compromise -> backup console -> backup deletion/encryption
```

Better design:

```text
Restricted admin path -> backup management zone -> protected repositories -> restore validation
```

### 22.8 Failover Can Create Silent Bypass

A design can be secure during normal operation and insecure during failover.

Always validate:

- secondary circuits;
- DR sites;
- VPN backup tunnels;
- SD-WAN alternate paths;
- cloud failover routing;
- firewall HA behavior;
- emergency NAT/routing changes;
- disaster recovery administration paths.

### 22.9 Provability Is the Difference Between Architecture and Hope

A strong design can prove:

- what zones exist;
- what assets belong to each zone;
- what paths are allowed;
- what paths are denied;
- what changed;
- who approved it;
- what logs prove enforcement;
- what tests prove behavior;
- what exceptions exist;
- when exceptions expire.

If the team cannot prove those facts, the design is not operationally mature.

### 22.10 Simplicity Is a Security Control

Overly complex network designs create hidden paths, rule entropy, troubleshooting pressure, and emergency exceptions.

Secure design favors:

- fewer trust levels with clear meaning;
- fewer shared services with broad access;
- fewer exception paths;
- clear routing domains;
- clear ownership;
- clear logs;
- repeatable validation.

Complexity must be justified by risk reduction, not by elegance or vendor features.

## 23. Internal References to Future CKV Files

This file owns network architecture, segmentation, DMZ patterns, zones and conduits, management-plane isolation, hard-control design, and segmentation validation. The following CKV files own adjacent areas and must be referenced instead of duplicated.

- **CKV-002 — Security Principles and Secure-by-Design Thinking**  
  Owns secure-by-design principles, trust boundaries, defense-in-depth, least privilege, secure defaults, complete mediation, and design reasoning used to justify segmentation and hard controls.

- **CKV-003 — Risk Management and Security Governance**  
  Owns risk appetite, risk acceptance, governance accountability, control ownership, policy authority, and formal risk decisions behind segmentation exceptions and allowed paths.

- **CKV-004 — Asset Management and Attack Surface Inventory**  
  Owns asset inventory, exposure mapping, critical asset relationships, crown-jewel identification, asset-to-control relationships, and asset-to-telemetry relationships used to decide zone membership and critical paths.

- **CKV-005 — Change Management and Security Exceptions**  
  Owns network change review, emergency connectivity, configuration drift, rollback planning, exception governance, compensating controls, and expiry/review of temporary paths.

- **CKV-006 — Business Continuity, Disaster Recovery, and Resilience**  
  Owns recovery priorities, dependency mapping for recovery, backup strategy, restore validation, crisis coordination, and resilience evidence for network and control-plane recovery.

- **CKV-010 — Networking Fundamentals and Encapsulation**  
  Owns protocol layering, encapsulation/decapsulation, traffic-flow reasoning, addressing concepts, switching vs routing at a high level, and foundational network vocabulary.

- **CKV-011 — Ethernet, Switching, VLANs, and Layer-2 Security**  
  Owns Ethernet behavior, switching, MAC learning, VLANs, trunks, Layer-2 adjacency, native VLAN behavior, STP security relevance, and Layer-2 hardening controls.

- **CKV-012 — IPv4, Subnetting, ARP, ICMP, and NAT**  
  Owns IPv4 addressing, subnetting, ARP, ICMP, NAT behavior, TTL, fragmentation, route reasoning, private/public ranges, and IPv4 troubleshooting.

- **CKV-013 — IPv6 and Neighbor Discovery Security**  
  Owns IPv6 addressing, Neighbor Discovery, Router Advertisements, SLAAC, DHCPv6 relationships, IPv6 security controls, IPv6 visibility, and IPv6 troubleshooting.

- **CKV-014 — TCP, UDP, Ports, and Transport Troubleshooting**  
  Owns TCP behavior, UDP behavior, ports, sockets, flow identity, handshakes, retransmission, resets, timeouts, and transport troubleshooting.

- **CKV-015 — DNS Architecture, Resolution, Attacks, and Defense**  
  Owns DNS architecture, recursive/authoritative resolution, DNS records, resolver behavior, split-horizon DNS, DNS security controls, DNS abuse, and DNS telemetry.

- **CKV-016 — DHCP, DHCP Snooping, and IP Source Guard**  
  Owns DHCP leasing, DHCP message flow, DHCP options, relay behavior, DHCP Snooping, binding tables, Dynamic ARP Inspection relationship, IP Source Guard, and DHCP-related access-network controls.

- **CKV-018 — Network Protocol Capture, Structures, and Analysis**  
  Owns packet capture methodology, capture placement detail, Wireshark/tcpdump analysis, protocol field interpretation, timing analysis, malformed packet analysis, and capture evidence handling.

- **CKV-030 — Active Directory Fundamentals**  
  Owns AD domains, forests, domain controllers, DNS dependencies, site topology, DC locator behavior, and identity infrastructure relationships that influence network zone and management design.

- **CKV-040 — HTTP, Web Fundamentals, Sessions, and Cookies**  
  Owns HTTP request/response behavior, web communication, methods, headers, sessions, cookies, status codes, TLS/web context, and web application traffic reasoning for DMZ and web-tier paths.

- **CKV-050 — Cloud Fundamentals: IaaS, PaaS, SaaS, Compute, Storage, IAM**  
  Owns foundational cloud service models, compute, storage, identity, virtual networking concepts, service reachability, and hosted workload context.

- **CKV-051 — Cloud Security Architecture and Hard Controls**  
  Owns cloud network security architecture, cloud segmentation, security groups, routing controls, private endpoints, cloud-native firewalling, guardrails, and cloud-specific hard-control design.

- **CKV-060 — Detection Engineering and Telemetry Design**  
  Owns telemetry design, detection logic, flow logs, DNS/proxy/firewall/endpoint telemetry mapping, detection coverage validation, and signal-quality reasoning.

- **CKV-061 — Incident Response Lifecycle and Playbook Design**  
  Owns incident response workflows, containment, eradication, recovery coordination, escalation, and playbooks that use network boundaries and choke points for response.

- **CKV-063 — Digital Forensics and Evidence Handling**  
  Owns formal evidence handling, chain of custody, forensic preservation, timeline building, and evidentiary treatment of network, log, and packet evidence.

- **CKV-064 — SOAR, Automation, Validation, and Provability Outputs**  
  Owns automation workflows, path validation, approval-gated enforcement, response validation, proof outputs, evidence packaging, and automated segmentation assurance.

- **CKV-065 — Security Monitoring Tools and Lab Architecture**  
  Owns monitoring lab topology, Security Onion/Wazuh/Splunk/Zeek/Suricata-style pipelines, sensor deployment, lab network design, and operational monitoring architecture.

- **CKV-072 — Network Attack Concepts and Defensive Controls**  
  Owns broader network attack concepts, lateral movement patterns, abuse paths, network attack taxonomies, and defensive response patterns that consume segmentation design.

- **CKV-080 — Malware, APT Lifecycle, Botnets, and Advanced Threat Controls**  
  Owns malware lifecycle, command-and-control, botnet behavior, beaconing, exfiltration patterns, and advanced threat movement that network design tries to constrain and observe.

- **CKV-081 — Firewalls, WAFs, IDS/IPS, and Network Security Controls**  
  Owns firewall, WAF, IDS/IPS, proxy, and network security control behavior, rule design, inspection depth, tuning, deployment details, validation, and control coverage.

- **CKV-082 — Vulnerability Management, Scanning, Prioritization, and Remediation**  
  Owns scanning, exposure validation, remediation prioritization, compensating controls, network-reachable vulnerability management, and remediation verification.

- **CKV-091 — Virtualization, Lab Design, and Safe Practice Environments**  
  Owns virtualization topology, lab network design, safe testing environments, isolated practice networks, and lab validation patterns for network architecture experiments.

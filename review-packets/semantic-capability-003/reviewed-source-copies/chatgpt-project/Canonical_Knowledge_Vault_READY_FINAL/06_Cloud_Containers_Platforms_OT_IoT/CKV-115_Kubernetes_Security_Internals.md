# CKV-115 — Kubernetes Security Internals
## 1. Purpose

This CKV is the advanced defensive reference for Kubernetes security internals. It explains how Kubernetes control-plane components, worker nodes, API objects, identities, authorization, admission, pod security, secrets, networking, storage, telemetry, and recovery controls work together as one security architecture.

The purpose is not to teach Kubernetes administration from scratch, provide a `kubectl` command cookbook, install a product, or describe offensive cluster abuse. The purpose is to make a security engineer able to reason about:

- where Kubernetes security boundaries actually exist and where they are only logical conventions;
- how the API server, etcd, controllers, kubelet, kube-proxy, CRI, CNI, CSI, and cloud-provider integrations create trust paths;
- how authentication, RBAC, Node authorization, service accounts, admission control, pod security, and network policy decide what is allowed;
- why namespaces are useful governance units but weak isolation boundaries unless reinforced by RBAC, admission, network policy, resource controls, and runtime isolation;
- how Kubernetes audit logs, API server logs, admission events, kubelet logs, runtime events, CNI logs, cloud logs, and workload telemetry become evidence;
- how to validate Kubernetes posture safely without cluster takeover, token theft, exploit traffic, container escape, bypass techniques, or unauthorized testing.

Kubernetes security is best understood as a converging control chain:

```text
human / workload / automation identity
  -> Kubernetes API authentication
  -> authorization decision
  -> admission decision
  -> object persistence in etcd
  -> controller reconciliation
  -> scheduler placement
  -> kubelet execution on node
  -> runtime / network / storage enforcement
  -> telemetry / evidence / recovery
```

A secure cluster is not only “pods running safely.” It is an API-centered distributed system where every desired state change is authenticated, authorized, admitted, recorded, reconciled, and continuously monitored.

## 2. Core Definition

Kubernetes security is the protection of the cluster API, control plane, nodes, workloads, identities, secrets, network paths, storage objects, admission decisions, and telemetry so that orchestrated workloads remain least-privileged, isolated, auditable, resilient, and recoverable.

Core Kubernetes security terms:

| Term | Security meaning |
|---|---|
| Cluster | Administrative and runtime boundary containing one control plane and one or more worker nodes. |
| Control plane | Components that accept desired state, store state, schedule work, and reconcile objects. |
| API server | Primary security choke point for authentication, authorization, admission, validation, audit, and object persistence. |
| etcd | Strongly sensitive key-value store containing cluster state, including secrets and authorization objects. |
| Node | Worker machine trusted to run pods and report pod/node status. A compromised node weakens all workloads on that node. |
| Pod | Smallest schedulable workload unit; may contain one or more containers sharing network and selected namespaces. |
| Namespace | Logical grouping boundary for namespaced resources, policy, quotas, and RBAC scope; not a strong security boundary by itself. |
| Service account | Kubernetes identity commonly used by pods and controllers. |
| RBAC | Role-based authorization model using Roles, ClusterRoles, RoleBindings, and ClusterRoleBindings. |
| Admission control | Request-time policy enforcement after authentication/authorization and before object persistence. |
| Pod Security Standards | Kubernetes-defined pod hardening levels: Privileged, Baseline, and Restricted. |
| NetworkPolicy | L3/L4 application-centric pod traffic policy enforced only when the CNI plugin supports it. |
| Secret | Kubernetes object intended for confidential data; requires encryption, RBAC, and workload-scoped access control. |
| Kubelet | Node agent that receives pod specifications and manages containers through the runtime. |
| CNI | Network plugin interface responsible for pod networking and often NetworkPolicy enforcement. |
| CSI | Storage plugin interface responsible for volume provisioning, attachment, and mount behavior. |
| CRD/operator | Extension model that adds new resource types and controllers; extends both functionality and risk. |

Kubernetes is API-driven. Most security failures become dangerous when they allow unauthorized API writes, unsafe object creation, overbroad workload execution, node trust abuse, secret access, or uncontrolled network/data paths.

## 3. Scope Ownership

This file owns Kubernetes-specific security internals and defensive engineering:

- Kubernetes architecture, trust boundaries, control-plane roles, worker-node roles, workload boundaries, add-on trust, and cloud-provider handoffs;
- API server authentication, authorization, admission, audit, and object-validation security mechanics;
- etcd sensitivity, encryption, backup, recovery, and evidence protection;
- scheduler, controller manager, cloud controller manager, kubelet, kube-proxy, CRI, CNI, and CSI security relationships;
- Kubernetes object security for namespaces, pods, controllers, services, ingress, ConfigMaps, Secrets, service accounts, RBAC objects, NetworkPolicies, CRDs, and operators;
- namespace isolation myths, multi-tenancy limits, service-account token posture, projected tokens, automount controls, and workload identity handoffs;
- RBAC least privilege, Node authorizer scope, aggregated roles, impersonation, escalation-sensitive verbs, and authorization review concepts at defensive taxonomy level;
- admission control, mutating/validating admission, Pod Security Admission, policy engines, image admission, resource enforcement, ordering, and failure handling;
- pod security context controls, node security, privileged DaemonSets, host namespaces, hostPath, Linux capabilities, seccomp, AppArmor/SELinux handoff, run-as controls, and read-only root filesystems;
- NetworkPolicy, ingress, egress, DNS, CoreDNS, service exposure, LoadBalancer, NodePort, ClusterIP, ExternalName, and service mesh handoff;
- managed Kubernetes handoff for EKS, AKS, and GKE at shared-responsibility level only;
- Kubernetes telemetry, detection categories, incident response, forensics, validation, framework mapping, and safe testing boundaries.

## 4. What This File Does Not Own

This file does not replace:

- full Linux kernel internals;
- full container image, runtime, namespace, cgroup, capability, or supply-chain security;
- full CI/CD, GitOps, Helm, Kustomize, or infrastructure-as-code implementation guidance;
- full cloud-provider security architecture for AWS, Azure, or GCP;
- full service mesh architecture;
- full product/vendor administration manuals;
- full SIEM, SOAR, or EDR/XDR engineering;
- exploit development, cluster takeover procedures, kubelet abuse workflows, service-account token theft, container escape instructions, privilege escalation procedures, admission bypass, NetworkPolicy bypass, secrets exfiltration, persistence setup, or offensive Kubernetes playbooks.

When those areas appear, they are treated only as control dependencies, evidence relationships, hardening considerations, or safe validation boundaries.

## 5. Prerequisites and Related CKV Files

| CKV | Dependency reason |
|---|---|
| CKV-003 | Kubernetes governance needs risk ownership, exception handling, and control assurance. |
| CKV-004 | Clusters, namespaces, nodes, workloads, service accounts, secrets, images, ingress objects, and policies are assets. |
| CKV-005 | RBAC, admission, CNI, ingress, API exposure, node-pool, and policy changes require change control. |
| CKV-010/017/018 | Kubernetes networking, service routing, traffic visibility, and packet evidence depend on network and capture concepts. |
| CKV-026 | Node security depends on Linux users, permissions, kernel boundary, namespaces, cgroups, and capabilities. |
| CKV-043 | Manifest review, policy-as-code, image trust, and deployment identity are DevSecOps handoffs. |
| CKV-044 | The Kubernetes API server is a high-impact API with authentication, authorization, admission, rate, and audit controls. |
| CKV-050/051 | Kubernetes often runs as cloud infrastructure with shared responsibility, IAM, logging, encryption, and recovery controls. |
| CKV-060/061/063 | Kubernetes detections, IR, and forensics depend on telemetry mapping and evidence preservation. |
| CKV-081 | Kubernetes ingress, egress, CNI, NetworkPolicy, load balancing, and inspection require network-control context. |
| CKV-090 | Admin tooling is validation/evidence context only, not a command cookbook. |
| CKV-091 | Kubernetes lab validation must use isolated, authorized clusters and non-production objects. |
| CKV-101 | API server private access, bastion access, ZTNA handoff, and administrator access boundaries depend on remote-access concepts. |
| CKV-102 | Kubernetes audit logs, CNI logs, runtime events, and cloud telemetry feed detection/NDR/SOC systems. |
| CKV-106/107 | Kubernetes certificates, TLS, service-account tokens, etcd encryption, and KMS providers depend on PKI and crypto. |
| CKV-110 | OIDC, workload identity, federation, claims, tokens, and service identities depend on modern IAM protocols. |
| CKV-112/113/114 | EKS, AKS, and GKE are managed Kubernetes handoffs, not replacements for Kubernetes internals. |
| CKV-116 | Container image/runtime internals are a future/related split; this file covers image/runtime only at Kubernetes control-plane handoff depth. |

Official reference anchors used for verification include Kubernetes documentation for cluster components, API access control, RBAC, Node authorization, admission control, Pod Security Standards, audit logging, NetworkPolicy, Secrets, encryption at rest, etcd backup, and securing clusters; NSA/CISA Kubernetes hardening guidance; CIS Kubernetes Benchmark concepts; MITRE ATT&CK Containers; NIST CSF, CIS Controls, ISO 27001, and NIST 800-53 control families.

## 6. Kubernetes Security Mental Model

Kubernetes turns declarative API objects into running workloads. Security therefore depends on controlling who may declare state, what state is allowed, where the workload can run, what identity it receives, what host and network access it gets, what data it can read, and what evidence is produced.

The defensive model has five layers:

| Layer | Defensive question |
|---|---|
| API layer | Who can call the API, from where, using which identity and assurance level? |
| Policy layer | Which verbs, resources, namespaces, nodes, images, security contexts, and networks are allowed? |
| Reconciliation layer | Which controllers convert desired state into actual changes? |
| Node/runtime layer | What kernel, runtime, mount, capability, network, and storage boundaries are actually enforced? |
| Evidence/recovery layer | Can the cluster explain what happened and recover safely? |

The most important Kubernetes security principle is that write access is often more powerful than it appears. Permission to create or modify pods, workloads, RoleBindings, CRDs, admission policies, nodes, secrets, service accounts, or namespaces can indirectly grant broader access than the object name suggests. Defensive review must assess effective capability, not only object labels.

## 7. Kubernetes Architecture and Trust Boundaries

Kubernetes architecture separates desired-state management from workload execution, but those layers are tightly coupled. The API server is the authoritative front door. etcd is the authoritative state store. Controllers and schedulers interpret state. Kubelets and runtimes execute state. Add-ons extend state. Cloud integrations translate cluster objects into external infrastructure.

Primary trust boundaries:

| Boundary | What crosses it | Main risk |
|---|---|---|
| User/automation to API server | Credentials, tokens, kubeconfig context, API requests | Unauthorized state changes, policy bypass through weak authn/authz. |
| API server to etcd | Object state, secrets, RBAC, admission configuration | Exposure or tampering with cluster truth. |
| API server to kubelet | Logs, exec/attach/port-forward, pod status, node operations | Node-control exposure and audit gaps if kubelet is weak. |
| Scheduler to nodes | Placement decisions | Sensitive workloads placed on weak or shared nodes. |
| Controller to external cloud | Load balancers, disks, routes, identities | Cloud resource drift or overprivileged cloud integration. |
| Pod to node kernel | Namespaces, cgroups, capabilities, mounts, devices | Workload breakout risk when pod security is weak. |
| Pod to network | Service, DNS, ingress/egress, CNI policy | Unauthorized east-west or external connectivity. |
| Pod to storage | Volumes, CSI, secrets, persistent data | Cross-tenant data exposure or backup leakage. |

A cluster boundary is only as strong as the weakest trusted component. A compromised cluster administrator, admission controller, node, image registry, or cloud controller can shift the actual risk posture of many workloads at once.

## 8. Control Plane, Worker Nodes, Workloads, Cluster Add-ons, and Cloud Provider Boundary

| Domain | Main components | Security responsibility |
|---|---|---|
| Control plane | API server, etcd, scheduler, controller manager, cloud controller manager | Authenticate and authorize API requests, persist desired state, enforce admission, schedule workloads, reconcile objects. |
| Worker nodes | kubelet, kube-proxy, container runtime, CNI, CSI, OS, kernel | Run workloads, enforce local pod/runtime/network/storage controls, report status, preserve node logs. |
| Workloads | Pods, controllers, services, jobs, app containers, sidecars | Run application code with constrained identity, filesystem, network, and runtime privileges. |
| Add-ons | DNS, ingress controllers, policy controllers, metrics/logging agents, service mesh, operators | Extend cluster behavior; often hold broad permissions and require strict governance. |
| Cloud provider | Managed control plane, cloud IAM, load balancers, disks, keys, logging, node pools | Provides infrastructure and shared-responsibility boundaries that differ by provider and service version. |

Security review must identify which entity owns each layer. In managed Kubernetes, the provider usually operates parts of the control plane, but customers still own identities, RBAC, workload policies, images, secrets, network exposure, admission configuration, application data, and many logging decisions.

## 9. API Server Defensive Internals

The API server is the cluster’s security decision hub. It exposes the Kubernetes API, terminates authenticated requests, performs authorization, invokes admission, validates objects, records audit events when configured, and persists accepted objects into etcd.

Defensive API server pipeline:

```text
request received
  -> TLS endpoint and client/server trust
  -> authentication
  -> user/group/extra identity attributes established
  -> authorization
  -> admission chain
  -> schema/defaulting/validation
  -> audit event emission according to policy
  -> etcd persistence
  -> watch/reconciliation fan-out
```

Critical security properties:

- The API server is the only intended remote endpoint for ordinary cluster state changes.
- All controllers, kubelets, administrators, CI/CD systems, operators, and workloads interact with cluster state through it.
- Anonymous access, weak authentication, overbroad RBAC, unaudited requests, or unsafe admission defaults can affect the entire cluster.
- Read access is sensitive because many Kubernetes objects expose secrets, workload configuration, environment variables, image names, node topology, service endpoints, and policy posture.
- Write access is often privilege-amplifying because it can create new workloads, bind identities, modify admission policy, or alter network/storage behavior.

API server hardening centers on private exposure, strong TLS, authenticated access only, RBAC and Node authorization, admission policy, request limits, audit policy, controlled aggregation/extension APIs, and protected configuration management.

## 10. etcd Security, Data Sensitivity, Encryption, Backup, and Recovery

etcd stores the cluster’s authoritative state. It commonly contains secrets, service-account token artifacts, RBAC bindings, admission configuration, ConfigMaps, CRDs, workload manifests, namespaces, node data, and security policy objects. Anyone who can read or tamper with etcd can often reconstruct or alter the cluster’s effective security posture.

Defensive properties:

| Area | Security requirement |
|---|---|
| Access | Restrict etcd network and administrative access to approved control-plane components and operators only. |
| TLS | Use mutually authenticated and encrypted connections between API servers and etcd members. |
| Encryption at rest | Encrypt Kubernetes API resources at rest, especially Secrets and high-value CRDs. |
| KMS integration | Prefer managed KMS envelope encryption where available instead of local static keys. |
| Backup | Encrypt, integrity-protect, access-control, and retention-govern etcd snapshots. |
| Restore | Test restore procedures in isolated environments; protect against restoring stale or compromised state. |
| Evidence | Preserve etcd-related logs and backup metadata during incidents without exposing secrets. |

etcd backups are equivalent to highly privileged cluster data. They must not be treated like ordinary operational backups. Backup compromise can reveal historical secrets, RBAC state, service identities, deployment manifests, and application configuration.

## 11. Scheduler Security Relevance

The scheduler selects nodes for pods based on constraints, availability, affinity, taints, tolerations, resource requests, topology, and policy-influenced placement. It is not usually the primary authorization engine, but its decisions can determine whether sensitive workloads land on trusted, isolated, compliant, or risky nodes.

Security-sensitive scheduling factors:

- node labels that represent trust zones, hardware class, compliance scope, environment, or tenant;
- taints and tolerations used to reserve nodes for privileged or system workloads;
- node affinity and anti-affinity used to separate sensitive workloads;
- topology spread constraints and zone placement used for resilience;
- resource requests/limits that affect noisy-neighbor and denial-of-service risk;
- admission controls that prevent unauthorized users from steering workloads to privileged nodes;
- node-pool governance in managed clusters.

A label is not automatically a security control. If users can modify node labels, namespace labels, pod node selectors, or tolerations without governance, placement policy can become a privilege path. Treat scheduling controls as security-relevant configuration.

## 12. Controller Manager and Reconciliation Security Model

Kubernetes controllers watch desired state and continuously reconcile actual state toward it. The controller manager embeds many core control loops, such as node, deployment, replica set, endpoint, service account, namespace, and garbage-collection controllers.

Security implications:

- Controllers may create or delete child objects automatically, so a single object change can cascade into many runtime effects.
- Controllers require permissions to read and modify API resources; their service accounts and credentials are high value.
- Reconciliation can re-create unsafe objects if the source desired state remains unsafe.
- Deleting a risky pod is not enough if the controller still owns a workload template that will re-create it.
- Owner references, finalizers, and garbage collection affect incident containment and forensic preservation.
- Operators extend this model with custom controllers and CRDs; operator permissions often exceed application permissions.

Defensive incident response should identify both the visible object and its controller source. The secure remediation unit is usually the owning controller, manifest, chart, GitOps source, or operator policy—not only the running pod.

## 13. Cloud Controller Manager and Managed Kubernetes Handoff

The cloud controller manager and managed-cluster integrations translate Kubernetes intent into cloud resources such as load balancers, routes, disks, node objects, public IPs, security groups, firewall rules, identities, and volume attachments.

Security handoff questions:

| Question | Why it matters |
|---|---|
| Which cloud identity does the controller use? | Overprivileged cloud roles can turn cluster changes into broad cloud changes. |
| Which resources can Kubernetes create? | Services and ingress objects may create public exposure. |
| Where are cloud logs emitted? | Cloud activity is needed to explain infrastructure side effects. |
| Who owns the managed control plane? | Shared responsibility differs between EKS, AKS, GKE, and self-managed clusters. |
| Can workloads receive cloud identities? | Workload identity misconfiguration can expose cloud data/control planes. |

Managed Kubernetes reduces some operational responsibilities but does not remove customer responsibility for workload identity, admission policy, RBAC, namespaces, secrets, network exposure, logging, and application security.

## 14. Kubelet Defensive Internals and Node Trust

The kubelet is the node agent responsible for pod lifecycle, container runtime interaction, mounted volumes, image pulls, liveness/readiness/startup probes, node status, and selected API interactions. It is a high-trust component because it bridges Kubernetes API intent to host-level execution.

Kubelet security concerns:

- kubelet credentials authorize node-specific API operations and must be scoped through Node authorization and NodeRestriction-style controls;
- kubelet serving endpoints must require authentication and authorization;
- API server to kubelet communication must be protected and certificate validation must be understood;
- kubelet configuration controls anonymous auth, read-only ports, TLS, webhook authorization, cgroup behavior, eviction, and runtime interaction;
- node compromise can expose pods, secrets mounted on that node, pod logs, workload data, and node credentials;
- privileged pods and hostPath mounts can weaken node isolation;
- node bootstrap, certificate rotation, and node decommissioning are identity lifecycle events.

A node should be treated as a security boundary with high blast radius. Do not place workloads of very different trust levels on the same node pool without explicit acceptance of shared-kernel risk.

## 15. kube-proxy, Services, Endpoints, and Cluster Traffic Model

kube-proxy implements part of the Kubernetes service traffic model on nodes, usually by programming packet forwarding behavior through iptables, IPVS, or equivalent data-plane mechanisms depending on cluster mode. Services provide stable virtual endpoints for pods whose IPs are ephemeral.

Security-relevant traffic objects:

| Object | Defensive meaning |
|---|---|
| ClusterIP Service | Internal virtual service endpoint; reachable inside the cluster depending on network policy and routing. |
| NodePort Service | Opens a port on nodes; increases node-level exposure and must be governed. |
| LoadBalancer Service | Usually asks the cloud provider to create external or internal load balancer infrastructure. |
| ExternalName Service | DNS-level aliasing; can hide external dependencies and data paths. |
| Endpoints / EndpointSlices | Backing targets for Services; useful telemetry and exposure evidence. |
| Ingress | HTTP/HTTPS routing object handled by an ingress controller; actual security depends on controller and cloud integration. |

Service exposure is a governance issue. A service type change may alter network reachability, cloud assets, DNS, firewall policy, and external attack surface.

## 16. CRI, CNI, CSI, Container Runtime, Network Plugin, and Storage Plugin Handoffs

Kubernetes delegates runtime, networking, and storage implementation to plugin boundaries. These are not minor details; they are enforcement points.

| Interface | What it controls | Security implication |
|---|---|---|
| CRI | Container runtime operations | Runtime isolation, image pull behavior, logging, container lifecycle, sandbox creation. |
| CNI | Pod networking | Pod IP assignment, routing, NetworkPolicy enforcement, egress behavior, overlay/underlay details. |
| CSI | Storage provisioning and mounts | Volume access, secrets for storage backends, attach/detach permissions, data persistence. |

The same Kubernetes manifest can have different security outcomes depending on runtime configuration, CNI policy support, CSI driver behavior, and managed-cluster defaults. Security validation must test the actual implementation, not only API object presence.

## 17. Kubernetes Object Model: Namespaces, Pods, Controllers, Services, Ingress, ConfigMaps, Secrets, and CRDs

Kubernetes security review is object-centric because almost everything is an API object.

| Object family | Security role |
|---|---|
| Namespace | Scopes many resources, RBAC bindings, quotas, Pod Security Admission labels, and NetworkPolicy selectors. |
| Pod | Runtime security context, service account, volumes, images, networking, and node placement. |
| Deployment/ReplicaSet/StatefulSet/DaemonSet | Workload templates that re-create pods and define persistence of desired runtime state. |
| Job/CronJob | Time-bound or recurring execution; may create risky periodic privileged workloads if not governed. |
| Service/Ingress | Application exposure, routing, load balancing, TLS handoff, and external reachability. |
| ConfigMap | Non-secret configuration; still sensitive when it reveals endpoints, flags, or operational secrets by mistake. |
| Secret | Confidential data object requiring encryption, RBAC, workload scoping, and rotation governance. |
| ServiceAccount | Workload identity and API access path. |
| Role/ClusterRole/Bindings | Authorization policy objects; direct control over API permissions. |
| NetworkPolicy | Pod traffic policy; dependent on CNI enforcement. |
| CRD/Operator | API extension and controller pattern; expands cluster power and attack surface. |

Controller-owned objects require special attention. If a bad pod is created by a Deployment, deleting only the pod does not fix the desired state. If a risky CRD grants an operator broad control, reviewing only built-in objects misses the real control path.

## 18. Namespace Isolation Myths and Real Security Boundaries

Namespaces organize resources, reduce naming collisions, and provide policy scope. They are not equivalent to virtual machines, separate clusters, or strong tenant isolation by themselves.

Namespace can help scope:

- RoleBindings and namespaced Roles;
- resource quotas and LimitRanges;
- Pod Security Admission labels;
- NetworkPolicy selectors;
- service-account defaulting;
- operational ownership and lifecycle;
- monitoring views and chargeback.

Namespace does not automatically isolate:

- the shared node kernel;
- cluster-wide resources;
- ClusterRoles and ClusterRoleBindings;
- nodes, persistent volumes, storage backends, ingress controllers, and some CRDs;
- CNI behavior unless NetworkPolicy is enforced;
- secrets from users who can create pods in the same namespace;
- privileged workloads that can access host resources.

Strong multi-tenancy usually requires separate clusters or heavily engineered controls: least-privilege RBAC, Pod Security Restricted/Baseline, default-deny NetworkPolicy, quotas, controlled ingress/egress, node-pool separation, admission policy, logging, and tested tenant-boundary assumptions.

## 19. Authentication: Certificates, Bearer Tokens, Service-Account Tokens, OIDC, Webhooks, kubeconfig, and Cloud Identity Handoff

Kubernetes authentication establishes the user, group, and extra attributes used by authorization and audit. Common authenticators include client certificates, bearer tokens, service-account tokens, OIDC integration, authentication webhooks, and managed cloud identity integrations.

Defensive authentication concerns:

| Mechanism | Security focus |
|---|---|
| Client certificates | Certificate issuance, expiry, subject/group mapping, revocation process, kubeconfig protection. |
| Bearer tokens | Token storage, lifetime, audience, rotation, issuer trust, leakage impact. |
| Service-account tokens | Workload identity scope, projected tokens, automount controls, audience restriction, rotation. |
| OIDC | Issuer trust, claim mapping, group claims, MFA/CA upstream, token lifetime, offboarding. |
| Webhook auth | External dependency security, availability, fail behavior, auditability. |
| kubeconfig | Local credential bundle; must be protected as privileged access material. |
| Cloud identity | Provider-specific mapping from cloud IAM to Kubernetes user/groups; requires joint IAM/RBAC review. |

Authentication is not authorization. A strongly authenticated user with overbroad RBAC is still dangerous; a weakly protected kubeconfig with cluster-admin is equivalent to direct control-plane access.

## 20. Authorization: RBAC, Node Authorizer, Webhook Authorization, Aggregated Roles, Impersonation, Bind, Escalate, and Least Privilege

Authorization decides whether an authenticated principal can perform a verb against a resource, subresource, non-resource URL, namespace, name, and API group. Kubernetes supports multiple authorizers; RBAC is the standard policy model in most clusters.

RBAC object model:

| Object | Scope | Meaning |
|---|---|---|
| Role | Namespace | Grants verbs on resources within one namespace. |
| ClusterRole | Cluster | Grants cluster-wide resources or reusable permission sets. |
| RoleBinding | Namespace | Binds a Role or ClusterRole to subjects within a namespace scope. |
| ClusterRoleBinding | Cluster | Binds a ClusterRole cluster-wide. |

High-risk authorization patterns:

- broad wildcards on verbs, resources, or API groups;
- cluster-admin equivalents;
- `secrets` get/list/watch permissions;
- permission to create pods/workloads in namespaces with privileged service accounts;
- `bind` and `escalate` permissions on RBAC resources;
- `impersonate` permission over users, groups, or service accounts;
- access to node proxy subresources;
- permission to patch namespaces where security labels drive Pod Security Admission or NetworkPolicy behavior;
- aggregated roles that silently add CRD permissions to default roles;
- ClusterRoleBindings where a RoleBinding would suffice.

Least privilege must be reviewed as effective privilege. Creating workloads, mounting secrets, using service accounts, or modifying policy objects can grant indirect capabilities that exceed the visible RBAC verb name.

## 21. Service Accounts, Projected Tokens, Token Rotation, Automount Controls, and Workload Identity

Service accounts are Kubernetes identities for workloads and controllers. A pod’s service account determines what API actions that workload can perform and, in managed clusters, may also participate in cloud workload identity mappings.

Defensive service-account model:

| Control | Purpose |
|---|---|
| Dedicated service accounts per workload | Avoid shared identity blast radius. |
| Minimal Role/RoleBinding scope | Restrict API access to required resources and verbs. |
| Disable unnecessary token automount | Prevent pods that do not need API access from receiving API credentials. |
| Use projected tokens | Prefer bounded, audience-aware, rotatable tokens. |
| Separate build/deploy/runtime identities | Prevent CI/CD identity from becoming runtime identity. |
| Review cloud workload identity mapping | Ensure Kubernetes identities do not inherit excessive cloud permissions. |
| Rotate and revoke where supported | Reduce value of leaked tokens and stale workloads. |

Workload identity bridges Kubernetes and external systems. A small RBAC error can become a cloud data-plane or control-plane error if the service account maps to a privileged cloud role.

## 22. Admission Control: Mutating, Validating, Pod Security Admission, Policy Engines, Image Admission, and Ordering

Admission control evaluates API requests after authentication and authorization but before persistence. It is the last central policy decision before an unsafe object becomes desired state.

Admission types:

| Type | Defensive role |
|---|---|
| Mutating admission | Defaults or modifies an object before validation; useful for injecting safe defaults but dangerous if opaque. |
| Validating admission | Allows or rejects an object after mutation; essential for guardrails. |
| Pod Security Admission | Enforces Kubernetes Pod Security Standards at namespace scope. |
| ValidatingAdmissionPolicy | Declarative validation inside the API server for supported use cases. |
| Image admission | Controls allowed registries, signatures, digests, provenance, vulnerability gates, or policy labels. |
| External policy engines | Provide richer policy-as-code; must be highly available and governed. |

Admission ordering matters. Mutating controllers run before validating controllers. Policy controllers cannot protect read requests. A failure mode of “ignore” may preserve availability but allow unsafe objects; a failure mode of “fail closed” may protect security but affect deployments. Production design should explicitly decide which admission controls are mandatory security gates and how exceptions are approved.

## 23. Pod Security Context: Privileged Mode, hostPID, hostIPC, hostNetwork, hostPath, Capabilities, seccomp, AppArmor/SELinux, runAsUser, runAsNonRoot, readOnlyRootFilesystem, and allowPrivilegeEscalation

Pod and container security context fields control how much of the node and kernel boundary a workload can access. They are the core Kubernetes-native way to express runtime hardening intent.

High-value controls:

| Field/control | Defensive purpose |
|---|---|
| `privileged` | Should be denied for normal workloads; grants broad host-level capability. |
| `hostPID`, `hostIPC`, `hostNetwork` | Expose host namespaces; should be tightly restricted. |
| `hostPath` | Mounts host filesystem paths; high risk unless narrowly justified. |
| Linux capabilities | Drop unnecessary capabilities; avoid broad or dangerous additions. |
| seccomp | Limit syscall surface; prefer RuntimeDefault or stricter profiles where feasible. |
| AppArmor/SELinux | Add Linux security module confinement where available and supported. |
| `runAsNonRoot` / `runAsUser` | Prevent default root execution where application-compatible. |
| `allowPrivilegeEscalation` | Should generally be false for ordinary workloads. |
| `readOnlyRootFilesystem` | Reduces persistence and tampering surface. |
| Resource limits | Reduce denial-of-service and noisy-neighbor risk. |

Pod Security Standards provide three profiles: Privileged, Baseline, and Restricted. In most enterprise namespaces, Baseline is a minimum and Restricted is the target for sensitive workloads, with documented exceptions for system components that legitimately require elevated privileges.

## 24. Secrets and ConfigMaps Security

Secrets and ConfigMaps are frequently underestimated. ConfigMaps can disclose endpoints, feature flags, environment names, internal paths, and accidental credentials. Secrets are intended for confidential values but require additional protection.

Secret security principles:

- encrypt secrets at rest in etcd;
- use external secret stores or CSI integrations where governance requires central secret custody;
- restrict Secret get/list/watch permissions;
- remember that list/watch can reveal secret contents;
- restrict workload creation in namespaces containing sensitive secrets;
- avoid mounting all secrets into broad workloads;
- prefer short-lived credentials and workload identity over static secrets;
- rotate secrets after workload or namespace compromise;
- avoid environment-variable exposure for highly sensitive values when filesystem-based or external providers are safer;
- include secrets in evidence handling without disclosing plaintext.

ConfigMaps should be reviewed for accidental secret material and operational disclosure. Treat configuration as sensitive when it reveals topology, identity names, control endpoints, feature flags, or trust relationships.

## 25. Network Policy, Ingress, Egress, DNS, CoreDNS, Service Exposure, LoadBalancer, NodePort, ClusterIP, ExternalName, and Service Mesh Handoff

Kubernetes networking is permissive by default in many clusters. NetworkPolicy is the Kubernetes-native L3/L4 policy object, but it only works when the CNI plugin enforces it.

Network security review areas:

| Area | Defensive question |
|---|---|
| Default pod connectivity | Are namespaces/pods non-isolated by default, or is default-deny enforced? |
| Ingress policy | Which pods/namespaces/IP blocks can reach sensitive workloads? |
| Egress policy | Can workloads reach the internet, metadata services, databases, or control-plane endpoints unnecessarily? |
| CNI support | Does the actual CNI enforce NetworkPolicy as expected? |
| Service type | Does NodePort or LoadBalancer expose nodes or workloads unintentionally? |
| Ingress controller | Is TLS, host routing, auth, logging, and annotation governance enforced? |
| DNS/CoreDNS | Are DNS logs available and are external name patterns governed? |
| ExternalName | Does DNS aliasing obscure external dependencies? |
| Service mesh | Are mTLS, policy, identity, sidecar, and telemetry controls aligned with Kubernetes RBAC/admission? |

A NetworkPolicy object without an enforcing CNI is documentation, not enforcement. A default-deny policy without required DNS and dependency exceptions can break workloads; validation must prove both restriction and business function.

## 26. Node Security, Node Pools, Labels, Taints, Tolerations, Privileged DaemonSets, and Workload Placement

Nodes are shared-kernel execution environments. Node security includes OS hardening, patching, kubelet configuration, runtime configuration, credential protection, host firewall posture, logging, filesystem protection, and lifecycle management.

Node-pool design should separate:

- system/control add-ons from application workloads;
- high-trust workloads from untrusted or internet-facing workloads;
- privileged infrastructure DaemonSets from ordinary applications;
- regulated data workloads from general workloads;
- GPU/special hardware workloads from ordinary workloads;
- build runners from production workloads;
- experimental/sandbox workloads from production.

Labels, taints, tolerations, affinity, and admission policy should work together. Labels used for security placement must be protected from unauthorized modification. Privileged DaemonSets should be treated as node-level administrative agents and reviewed like high-privilege infrastructure software.

## 27. Image Admission, Registry Trust, SBOM/Provenance Handoff, and Runtime Drift at Kubernetes Level

This file does not own full container supply-chain security, but Kubernetes is the enforcement point where image trust meets runtime deployment.

Kubernetes-level image controls:

- allow approved registries and disallow untrusted registries;
- prefer immutable digests over mutable tags for production;
- enforce image signature/provenance policy where supported;
- require vulnerability, license, and malware scan gates through CI/CD/admission handoff;
- block privileged images or images requiring unsafe runtime settings unless approved;
- monitor actual running images for drift from approved deployment manifests;
- govern imagePullSecrets and registry credentials;
- separate build identities from deploy identities and runtime identities.

Runtime drift is the gap between approved desired state and actual running state. Kubernetes detection should compare manifests, admission decisions, registry metadata, node runtime inventory, and cloud/container telemetry.

## 28. Multi-Tenancy Limits, Namespace Governance, Quotas, LimitRanges, and Resource Isolation

Kubernetes multi-tenancy can mean teams sharing a cluster, applications sharing a cluster, environments sharing a cluster, or customers sharing a platform. Each model has different risk.

Governance controls:

| Control | Security value |
|---|---|
| Namespace ownership | Assign accountable owner, data classification, environment, and lifecycle. |
| Quotas | Limit resource exhaustion and uncontrolled object growth. |
| LimitRanges | Provide safe default resource boundaries. |
| RBAC per tenant | Prevent cross-namespace read/write and policy modification. |
| Pod Security Admission | Enforce Baseline/Restricted according to tenant risk. |
| NetworkPolicy | Deny unnecessary cross-tenant traffic. |
| Node-pool separation | Reduce shared-kernel risk for different trust levels. |
| Admission exceptions | Centralize and time-limit deviations. |
| Tenant telemetry | Preserve per-tenant logs, events, and audit attribution. |

For hostile or highly sensitive multi-tenancy, separate clusters are usually safer than namespace-only separation. Namespace governance is a control plane convenience, not a full isolation guarantee.

## 29. Managed Kubernetes Handoff: EKS, AKS, GKE, Control-Plane Responsibility, Cloud IAM, Cloud Logging, and Cloud Networking

Managed Kubernetes changes who operates some infrastructure but not the core security model. The provider usually manages control-plane availability and some patching. Customers still own most security configuration above the managed layer.

Managed-cluster review must include:

| Area | EKS / AKS / GKE handoff question |
|---|---|
| Control-plane endpoint | Public/private exposure, authorized networks, admin access path. |
| Cloud IAM integration | Mapping between cloud roles/groups and Kubernetes RBAC. |
| Workload identity | Binding Kubernetes service accounts to cloud identities safely. |
| Node pools | OS image, patching, upgrade cadence, node identity, labels, taints, autoscaling. |
| Network | VPC/VNet design, pod IP model, private nodes, egress, ingress, firewall/security group/NSG rules. |
| Logging | API audit, control-plane logs, node logs, workload logs, cloud activity logs. |
| Secrets/KMS | Provider KMS integration, envelope encryption, key policy, rotation, evidence. |
| Add-ons | CNI, CSI, ingress, DNS, policy agents, metrics, and cloud controllers. |

Provider-specific details belong to CKV-112, CKV-113, and CKV-114. This file owns the Kubernetes meaning of those controls.

## 30. Kubernetes Telemetry Sources

Kubernetes telemetry must cover API intent, admission decisions, reconciliation, runtime execution, network behavior, node state, cloud side effects, and application logs.

| Source | Evidence value |
|---|---|
| API audit logs | Who did what to which resource, when, from where, with which user agent and decision outcome. |
| API server logs | Control-plane health, errors, request patterns, admission/authentication issues. |
| Admission controller logs/events | Policy denials, mutations, exceptions, unsafe manifests, image decisions. |
| Kubernetes Events | Scheduling, image pull, secret/config failure, probe failure, eviction, volume mount and policy issues. |
| Kubelet logs | Node workload lifecycle, pod start/stop, runtime interaction, probe and mount behavior. |
| Container runtime logs | Container creation, termination, runtime errors, image pulls. |
| Node OS logs | Host process, kernel, authentication, filesystem, network, and service evidence. |
| CNI logs / NetworkPolicy logs | Allowed/denied traffic, policy enforcement, pod IP mapping. |
| CoreDNS logs | Service discovery and suspicious domain resolution. |
| Ingress/controller logs | External access, TLS handoff, route decisions, client metadata. |
| Service mesh logs | Identity-aware service-to-service telemetry, mTLS state, policy decisions. |
| Cloud provider logs | Load balancer, IAM, firewall, disk, KMS, audit, and managed control-plane evidence. |
| Registry/admission metadata | Image provenance, scan results, signature verification, deployment source. |
| Backup logs | etcd and persistent-volume recovery evidence. |

Telemetry must preserve identity context. The analyst should be able to connect an API request to a user/service account, namespace, workload, node, image, network flow, and cloud side effect.

## 31. Kubernetes Threat Model

Kubernetes threats are usually control-plane, identity, workload, node, network, supply-chain, or cloud-integration failures.

Defensive threat categories:

- exposed or weakly authenticated API server;
- overprivileged RBAC, ClusterRoleBindings, aggregated roles, and privileged service accounts;
- unsafe workload creation permissions that indirectly expose secrets or service-account privileges;
- privileged pods, host namespaces, hostPath, unsafe capabilities, and weak Pod Security controls;
- weak kubelet authentication/authorization or node trust abuse;
- unencrypted etcd secrets, exposed backups, or weak KMS governance;
- permissive network defaults, missing egress controls, insecure ingress, or NodePort/LoadBalancer sprawl;
- image provenance gaps, untrusted registries, vulnerable images, and deployment drift;
- CRD/operator overprivilege and opaque controller behavior;
- namespace isolation assumptions without supporting controls;
- managed-cluster identity mapping errors and cloud workload identity overprivilege;
- logging gaps that prevent attribution and recovery.

This threat model is defensive. It names risk families so engineers can design controls, detections, evidence, and recovery paths; it does not provide exploitation instructions.

## 32. Threat-to-Control Matrix

| Threat / failure mode | Preventive controls | Detective evidence | Corrective / recovery response |
|---|---|---|---|
| Exposed API server | Private endpoint, network allowlists, strong auth, disable anonymous, RBAC, audit | API server logs, cloud network logs, failed auth, unusual source IPs | Restrict endpoint, rotate credentials, review audit, restore policy state. |
| Overprivileged RBAC | Least privilege, RoleBinding over ClusterRoleBinding, bind/escalate restrictions | RBAC inventory, audit of rolebinding changes, access reviews | Remove grants, rotate affected service-account tokens, revalidate access. |
| Unsafe pod security | Pod Security Admission, policy engine, restricted defaults, exceptions | Admission denials, pod specs, runtime inventory | Quarantine namespace, patch workload template, evict unsafe pods if approved. |
| Secret exposure | Encryption at rest, RBAC, external secret store, no broad workload creation | Secret access audit, pod mount evidence, admission events | Rotate secrets, revoke tokens, review namespace workload permissions. |
| Node compromise | Harden nodes, patching, node isolation, restricted privileged workloads | Kubelet/node logs, runtime alerts, EDR, cloud logs | Cordon/drain/rebuild node, rotate node credentials, inspect affected pods. |
| Network lateral movement | Default-deny NetworkPolicy, egress policy, ingress governance | CNI logs, flow logs, DNS logs, service mesh telemetry | Apply emergency policy, isolate namespace, review service exposure. |
| Operator/CRD overprivilege | Operator review, scoped permissions, admission policy, change control | CRD changes, operator service account activity, reconciliation logs | Disable/rollback operator, remove broad RBAC, review created objects. |
| Managed identity overprivilege | Workload identity least privilege, cloud IAM review, no shared identities | Cloud audit logs, token exchange logs, service-account usage | Revoke cloud role mapping, rotate credentials, contain workload. |
| Incomplete audit | Mandatory audit policy, retention, export, time sync, integrity controls | Log pipeline health, missing log alerts | Enable required logs, preserve backups, document evidence gap. |
| Broken recovery | Tested etcd/PV backups, encrypted snapshots, restore runbooks | Backup job status, restore validation results | Restore in clean environment, verify integrity, rotate secrets after restore. |

## 33. Preventive Controls

Preventive controls reduce unsafe states before they become running workloads.

- Keep API server private or tightly restricted; require strong authentication and disable unnecessary anonymous access.
- Use RBAC least privilege; minimize ClusterRoleBindings; restrict bind, escalate, impersonate, and node proxy access.
- Use Node authorization and node restriction patterns to scope kubelet permissions.
- Enforce Pod Security Standards through Pod Security Admission or equivalent policy engines.
- Default-deny NetworkPolicy for sensitive namespaces and govern egress explicitly.
- Encrypt secrets at rest and protect etcd backups.
- Use dedicated service accounts and disable automatic token mounting for workloads that do not need API access.
- Prefer projected, bounded, short-lived tokens and cloud workload identity over static credentials.
- Protect admission controllers and policy engines as critical infrastructure.
- Govern CRDs/operators through security review and scoped permissions.
- Enforce image registry trust, immutable digests, signature/provenance policy, and vulnerability gates through admission/CI handoff.
- Separate node pools by trust level and restrict privileged DaemonSets.
- Use quotas, LimitRanges, resource requests/limits, and namespace ownership metadata.
- Restrict unsafe service types and public ingress creation through admission and cloud guardrails.
- Use managed KMS where available for etcd encryption and external secrets.
- Establish change control for API exposure, RBAC, admission, CNI, ingress, service mesh, node pools, and operators.

## 34. Detective Controls and Telemetry Sources

Detective controls prove whether the cluster is behaving as designed.

Core detections should monitor:

- creation or modification of ClusterRoles, ClusterRoleBindings, Roles, RoleBindings, service accounts, and secrets;
- grant of wildcard verbs/resources or high-risk verbs such as bind, escalate, and impersonate;
- creation of privileged pods, hostPath mounts, host network/PID/IPC, added capabilities, or disabled security confinement;
- changes to namespace labels controlling Pod Security Admission or NetworkPolicy selection;
- creation of LoadBalancer, NodePort, ingress, ExternalName, or public exposure objects;
- secret reads, list/watch access, service-account token usage, and mounted secret patterns;
- admission denials, policy exceptions, policy engine failures, and fail-open behavior;
- kubelet authentication failures, node registration anomalies, node label changes, and unexpected node joins;
- CNI policy denials, unexpected egress, metadata-service access attempts, and DNS anomalies;
- CRD/operator installation or permission expansion;
- changes to etcd encryption configuration, KMS provider health, and backup status;
- managed cloud side effects such as load balancer creation, firewall/security group changes, disk attachments, and IAM role bindings.

Telemetry quality should be measured by attribution, completeness, retention, integrity, and correlation. Kubernetes evidence without user, service account, namespace, node, image, and request source context is weak.

## 35. Corrective, Recovery, and Compensating Controls

Corrective controls restore safe configuration. Recovery controls restore service and evidence. Compensating controls reduce risk when ideal controls cannot be immediately applied.

| Control type | Examples |
|---|---|
| Corrective | Remove unsafe RBAC bindings, patch workload templates, enforce Pod Security, restrict service exposure, rotate tokens/secrets, remove unsafe CRDs. |
| Recovery | Restore etcd state in clean environment, rebuild nodes, redeploy from trusted manifests, restore persistent volumes, validate workloads and policies. |
| Compensating | Extra monitoring, tighter network controls, isolated node pools, temporary admission exceptions with expiry, manual approval for high-risk manifests. |

For containment, remember controller reconciliation. Removing a running pod may not persist if a Deployment, DaemonSet, operator, or GitOps controller re-creates it. Correct the source of desired state, then reconcile runtime objects.

## 36. Required Policies and Standards

Minimum governance documents for Kubernetes security:

- Kubernetes Cluster Security Standard;
- Kubernetes RBAC and Privileged Access Standard;
- Namespace Ownership and Multi-Tenancy Standard;
- Pod Security and Runtime Hardening Standard;
- Admission Control and Policy-as-Code Standard;
- Kubernetes Network Policy and Service Exposure Standard;
- Kubernetes Secrets and Workload Identity Standard;
- Image Registry, Provenance, and Admission Standard;
- Managed Kubernetes Shared Responsibility Standard;
- Node Pool and Host Hardening Standard;
- CRD/Operator Security Review Standard;
- Kubernetes Logging, Audit, and Retention Standard;
- Kubernetes Backup and Recovery Standard;
- Kubernetes Exception and Break-Glass Procedure;
- Kubernetes Incident Response Procedure;
- Kubernetes Change Management Procedure.

Every exception should define owner, namespace/cluster, object scope, risk reason, compensating controls, expiry date, validation evidence, and rollback plan.

## 37. Hardening Baseline

A defensible Kubernetes baseline should include:

| Domain | Baseline expectation |
|---|---|
| API server | Private or restricted access, strong authn/authz, audit policy enabled, anonymous minimized, admission enforced. |
| RBAC | Least privilege, no unmanaged cluster-admin, minimal wildcards, controlled bind/escalate/impersonate. |
| Admission | Pod Security Baseline/Restricted, image trust, namespace policy, resource limits, service exposure controls. |
| Secrets | Encryption at rest, restricted access, external store where needed, rotation, no broad list/watch. |
| Service accounts | Dedicated per workload, automount disabled where unnecessary, projected tokens, cloud identity least privilege. |
| Network | Default-deny for sensitive namespaces, explicit ingress/egress, DNS governance, controlled LoadBalancer/NodePort. |
| Nodes | Hardened OS, patched images, restricted kubelet, separate node pools, no unreviewed privileged DaemonSets. |
| etcd | TLS, restricted access, encryption, encrypted backups, tested restore. |
| Images | Trusted registries, vulnerability gates, signature/provenance policy, immutable references. |
| Logging | API audit, control plane, node, runtime, ingress, CNI, DNS, and cloud logs exported and retained. |
| Recovery | Versioned manifests, etcd/PV backups, isolated restore test, secret rotation plan. |

## 38. Configuration Review Checklist

Review the cluster using these questions:

1. Is the API server reachable only from approved networks and identities?
2. Are anonymous authentication and weak legacy access paths disabled or minimized?
3. Which users, groups, and service accounts have cluster-admin or equivalent privileges?
4. Who can bind, escalate, impersonate, or modify RBAC objects?
5. Who can create workloads in namespaces containing sensitive service accounts or secrets?
6. Are Pod Security Admission labels enforced and protected from unauthorized modification?
7. Are privileged pods, hostPath, hostNetwork, hostPID, hostIPC, dangerous capabilities, and root execution justified and documented?
8. Are NetworkPolicies enforced by the actual CNI and validated with safe tests?
9. Which Services expose NodePort, LoadBalancer, Ingress, or ExternalName?
10. Are service-account tokens projected, rotated, audience-bound, and not unnecessarily mounted?
11. Are Secrets encrypted at rest and access-controlled with least privilege?
12. Are etcd backups encrypted, restricted, and restore-tested?
13. Are CRDs and operators reviewed for permissions and reconciliation behavior?
14. Are image registries, tags/digests, signatures, and admission rules governed?
15. Are node pools separated by trust level and protected against unauthorized scheduling?
16. Are audit logs exported to an immutable or protected logging destination?
17. Can incidents be reconstructed across API, node, runtime, network, cloud, and application evidence?
18. Are managed-cluster shared responsibilities documented by provider and version?

## 39. Detection Logic Categories

High-level detection categories for Kubernetes:

| Category | Signal examples |
|---|---|
| Privilege expansion | New ClusterRoleBinding, wildcard grants, bind/escalate/impersonate, service-account permission expansion. |
| Unsafe workload admission | Privileged pod, hostPath, hostNetwork/PID/IPC, added capabilities, root execution, untrusted image. |
| Secret exposure | Secret get/list/watch, unusual secret volume mounts, workload creation in secret-rich namespaces. |
| API anomaly | Unusual source IP, user agent, request volume, failed auth, off-hours admin action, new kubeconfig identity. |
| Namespace policy drift | Pod Security label change, NetworkPolicy removal, quota removal, owner metadata change. |
| Network exposure | LoadBalancer/NodePort/Ingress creation, public IP assignment, egress policy removal, DNS anomalies. |
| Node anomaly | Unexpected node join, kubelet errors, node label change, privileged DaemonSet, runtime crash loops. |
| Operator/CRD risk | New CRD, operator permission expansion, high-rate reconciliation errors, unknown controller image. |
| Managed cloud side effect | Cloud load balancer, firewall, IAM, disk, KMS, or logging change caused by Kubernetes action. |
| Recovery risk | Backup failure, etcd encryption drift, log export failure, KMS provider errors. |

Detections should map to approved ownership and expected change windows to reduce noise while preserving high-fidelity alerts.

## 40. Incident Response Considerations

Kubernetes incident response must avoid destroying evidence while also preventing reconciliation from reintroducing unsafe state.

Response sequence at defensive level:

1. Identify affected clusters, namespaces, workloads, nodes, service accounts, images, and cloud resources.
2. Preserve audit logs, Events, pod specs, controller specs, admission decisions, node logs, runtime logs, CNI logs, ingress logs, and cloud logs.
3. Determine whether the source of truth is manual API change, CI/CD, GitOps, operator, controller, or cloud integration.
4. Contain by disabling unsafe desired state, tightening admission/network policy, suspending risky identities, or isolating namespaces/nodes according to approval.
5. Rotate affected secrets, service-account tokens, cloud identities, image credentials, and certificates where exposure is plausible.
6. Rebuild compromised nodes instead of trusting in-place cleanup for high-severity incidents.
7. Restore workloads from trusted manifests and images.
8. Validate RBAC, admission, secrets, network policy, and telemetry after containment.
9. Document root cause, blast radius, control gaps, exception handling, and recovery evidence.

Kubernetes IR must include both cluster evidence and external evidence: cloud audit logs, registry events, CI/CD logs, identity provider logs, vulnerability scan results, and backup records.

## 41. Forensics and Evidence Considerations

Kubernetes evidence is volatile and distributed. Pods may be rescheduled, logs may rotate, nodes may autoscale away, and controllers may overwrite runtime state.

Evidence to preserve:

| Evidence | Why it matters |
|---|---|
| API audit logs | Primary record of cluster state changes and user/service-account actions. |
| Object manifests | Desired state at incident time, including pod specs, service accounts, RBAC, NetworkPolicy, ingress, CRDs. |
| Events | Scheduling, image pull, volume mount, admission, probe, eviction, and runtime hints. |
| Controller owner chain | Shows whether a pod came from Deployment, DaemonSet, Job, CronJob, operator, or manual action. |
| Node logs | Kubelet, runtime, kernel, auth, process, filesystem, and network evidence. |
| Container logs | Application and runtime output. Preserve before deletion/rotation. |
| Image metadata | Registry, digest, signature, scan result, SBOM/provenance handoff. |
| Secret access evidence | API accesses, mounts, workload permission path, rotation status. |
| Network evidence | Flow logs, CNI decisions, ingress logs, DNS/CoreDNS logs, service mesh telemetry. |
| Cloud evidence | IAM, load balancer, firewall, disk, KMS, and managed control-plane logs. |
| Backup evidence | etcd snapshots, PV snapshots, restore tests, backup integrity records. |

Evidence handling should avoid printing or exporting secrets in plaintext. Analysts need controlled redaction workflows and secure evidence storage.

## 42. Validation and Safe Testing

Validation confirms that controls work without using exploit procedures or unauthorized activity.

Safe validation categories:

- RBAC review: compare intended permissions to actual Roles, ClusterRoles, bindings, subjects, and high-risk verbs.
- Admission validation: submit approved benign manifests that should be allowed and policy-violating benign manifests that should be rejected in a test namespace.
- Pod Security validation: verify Baseline/Restricted behavior with non-malicious manifests representing forbidden settings.
- NetworkPolicy validation: use authorized test workloads to confirm allowed and denied paths without scanning unauthorized networks.
- Secret posture validation: confirm encryption at rest, RBAC scope, automount settings, and rotation process.
- Service-account validation: confirm workloads cannot access API resources beyond intended scope.
- API exposure validation: verify approved source restrictions and authentication requirements.
- Logging validation: generate safe administrative changes and verify audit, admission, event, node, cloud, and SIEM records.
- Backup validation: restore etcd/PV backups in an isolated environment and verify integrity.
- Managed-cluster validation: confirm provider control-plane logs, cloud IAM mapping, private endpoint, and node-pool settings.

Validation should use lab, staging, or controlled production-safe tests with explicit authorization, change record, rollback plan, and evidence capture.

## 43. Lab-Safe Boundaries

Allowed lab-safe activities:

- reviewing manifests, RBAC, NetworkPolicy, service accounts, admission settings, and audit policy;
- testing admission rejections with harmless manifests in an authorized lab namespace;
- validating NetworkPolicy using simple approved test services;
- checking that secrets are encrypted at rest using authorized administrative review;
- verifying log delivery and alert creation from benign changes;
- testing backup and restore in isolated clusters;
- comparing managed-cluster security settings against baseline;
- tabletop exercises for cluster compromise, secret exposure, unsafe workload, and node rebuild scenarios.

Not allowed in this CKV:

- cluster takeover attempts;
- exploiting kubelet, API server, container runtime, or admission controllers;
- stealing, replaying, or abusing service-account tokens;
- container escape attempts;
- bypassing NetworkPolicy or admission controls;
- unauthorized scanning or testing of production clusters;
- secrets exfiltration;
- offensive Kubernetes abuse playbooks.

## 44. Framework and Control Mapping

| Framework / reference | Kubernetes security mapping |
|---|---|
| NIST CSF 2.0 Govern | Cluster ownership, policy, risk acceptance, supplier/operator governance, exception management. |
| NIST CSF 2.0 Identify | Inventory clusters, namespaces, nodes, workloads, identities, secrets, images, CRDs, network exposures. |
| NIST CSF 2.0 Protect | RBAC, admission, Pod Security, encryption, secrets management, node hardening, NetworkPolicy. |
| NIST CSF 2.0 Detect | Audit logs, Events, runtime telemetry, CNI/ingress/DNS/cloud logs, posture findings. |
| NIST CSF 2.0 Respond | Kubernetes IR runbooks, containment, token rotation, node rebuild, policy rollback. |
| NIST CSF 2.0 Recover | etcd/PV backup, manifest redeploy, disaster recovery, validation after restore. |
| CIS Controls v8 | Inventory, secure configuration, account management, access control, audit logs, malware defenses, network monitoring, incident response. |
| ISO/IEC 27001 Annex A | Access control, privileged access, configuration management, logging, monitoring, backup, supplier/cloud operations. |
| NIST 800-53 | AC, AU, CM, CP, IA, IR, SC, SI, RA, SA families. |
| CIS Kubernetes Benchmark | Secure configuration baseline for control plane, etcd, nodes, policies, and managed-cluster posture. |
| NSA/CISA Kubernetes Hardening | Least privilege, pod/container scanning, network separation, strong auth, auditing, and hardening themes. |
| MITRE ATT&CK Containers | Defensive mapping for container/orchestrator tactics without providing execution steps. |
| CISSP CBK | IAM, architecture, network security, operations, software/deployment security, DR/BCP, incident response. |

## 45. Common Failures

- Treating namespace separation as strong tenant isolation.
- Granting cluster-admin for troubleshooting and never removing it.
- Allowing wildcard RBAC in production.
- Giving CI/CD systems permanent broad cluster credentials.
- Allowing users to create pods in namespaces with privileged service accounts.
- Missing admission controls for privileged pods and hostPath mounts.
- Creating NetworkPolicy objects while using a CNI that does not enforce them.
- Leaving egress unrestricted from sensitive namespaces.
- Exposing API server publicly without strong source restrictions and monitoring.
- Storing secrets unencrypted in etcd.
- Failing to encrypt or restrict etcd backups.
- Installing operators without reviewing their RBAC and CRDs.
- Treating managed Kubernetes as “fully provider-secured.”
- Missing audit logs or retaining them too briefly.
- Deleting pods during IR without preserving controller source and evidence.
- Rebuilding workloads from mutable tags after an incident.

## 46. Common Mistakes

| Mistake | Correct mental model |
|---|---|
| “A RoleBinding to a ClusterRole is always cluster-wide.” | A RoleBinding scopes the ClusterRole’s permissions to that namespace. |
| “Secrets are encrypted automatically.” | Secrets are often stored in etcd and require explicit encryption-at-rest configuration. |
| “List is less dangerous than get for Secrets.” | list/watch can expose secret contents and should be treated as sensitive. |
| “NetworkPolicy exists, so traffic is restricted.” | Enforcement depends on CNI support and policy selection. |
| “Deleting a pod fixes the problem.” | Controllers may recreate it; fix desired state. |
| “Managed Kubernetes means the provider owns security.” | Provider manages some layers; customer still owns workload, IAM, RBAC, network, secrets, logs, and admission. |
| “Service accounts are only internal.” | They can become cloud identities through workload identity integrations. |
| “Read-only access is harmless.” | Read access may reveal secrets, topology, images, policies, and security posture. |
| “Privileged DaemonSets are normal infrastructure.” | They are node-level privileged agents and require strict governance. |

## 47. Must-Memorize Facts

- Kubernetes is API-centered; protect the API server first.
- etcd is cluster truth and often contains secrets and security policy state.
- Admission happens after authentication and authorization but before persistence.
- Namespaces are governance boundaries, not strong security boundaries by default.
- RBAC must be evaluated by effective privilege, not by object names alone.
- Creating workloads can indirectly grant access to service accounts, secrets, volumes, and nodes.
- Pod Security Standards are Privileged, Baseline, and Restricted.
- NetworkPolicy requires an enforcing CNI; otherwise the object has no traffic-control effect.
- Secrets should be encrypted at rest, tightly RBAC-scoped, and rotated after exposure.
- Kubelet is a high-trust node agent and must require authentication/authorization.
- Node compromise can expose workloads and secrets on that node.
- Operators and CRDs extend the API and can introduce high-impact permissions.
- Managed Kubernetes changes operational responsibility but not the need for customer-side policy, identity, and workload controls.
- Audit logs are the primary source for cluster state-change attribution.

## 48. Interview / Exam Points

High-value points:

- Explain the API request path: authentication, authorization, admission, persistence, reconciliation.
- Distinguish Role, ClusterRole, RoleBinding, and ClusterRoleBinding.
- Explain why namespace isolation is not equivalent to VM isolation.
- Explain why pod creation permission can imply secret access or service-account privilege.
- Explain how Pod Security Admission relates to Pod Security Standards.
- Explain NetworkPolicy default behavior and CNI dependency.
- Explain how etcd encryption differs from secret rotation and external secret management.
- Explain kubelet’s security role and why node trust matters.
- Explain the difference between Kubernetes service accounts and cloud workload identities.
- Explain how managed Kubernetes shared responsibility differs from self-managed clusters.
- Explain why audit logs, Events, node logs, runtime logs, CNI logs, and cloud logs must be correlated.
- Explain how to safely validate Kubernetes controls without offensive tests.

## 49. Expert-Level Insights

- Kubernetes is a policy convergence system. Security must control both the initial API write and the controllers that continuously enforce desired state.
- The dangerous unit is often not the pod but the permission to create a pod with a chosen service account, volume, node selector, or security context.
- Admission policy is preventive only for writes. It cannot protect against already-existing unsafe objects unless paired with audit, drift detection, and remediation.
- A service account is an identity boundary, a secret boundary, and sometimes a cloud boundary. Treat it like a machine identity, not a Kubernetes convenience.
- etcd backup access can be equivalent to historical cluster compromise. Backup governance is a Tier 0 concern for clusters.
- CNI behavior determines whether Kubernetes network intent becomes enforcement. Always validate the real CNI.
- Operators are miniature control planes. Their RBAC, CRDs, webhooks, image source, and reconciliation behavior must be reviewed like platform components.
- Pod Security Restricted is a baseline target, not a guarantee against all runtime risk.
- Node pools are trust zones. Scheduling policy and node hardening are part of application security architecture.
- Managed cluster control planes still require customer validation of logs, IAM mapping, network exposure, admission, secrets, workload identity, and recovery.

## 50. Generation Boundaries and Unsafe Content Restrictions

This CKV intentionally excludes misuse-enabling content. It does not include:

- cluster takeover workflows;
- kubelet abuse procedures;
- service-account token theft or replay;
- container escape techniques;
- exploit payloads;
- offensive privilege escalation procedures;
- persistence setup;
- admission-controller bypass;
- NetworkPolicy bypass;
- secrets exfiltration;
- unauthorized scanning/testing;
- product-specific attack recipes.

All adversary-related terminology is normalized into defensive taxonomy, control validation, telemetry mapping, incident response, and safe lab boundaries.

## 51. Quick Reference Tables

### 51.1 Core component quick map

| Component | Owns | Security priority |
|---|---|---|
| API server | API, authn/authz/admission/audit/persistence path | Protect as primary choke point. |
| etcd | Cluster state | Encrypt, restrict, backup securely. |
| Scheduler | Pod placement | Protect labels/taints and placement policy. |
| Controller manager | Reconciliation | Secure controller identities and source of desired state. |
| Kubelet | Node workload execution | Harden, authenticate, authorize, monitor. |
| kube-proxy | Service traffic | Understand service exposure and node traffic. |
| CNI | Pod networking and often NetworkPolicy | Validate enforcement. |
| CSI | Persistent storage | Govern data access and mount behavior. |

### 51.2 High-risk Kubernetes permissions

| Permission pattern | Why high risk |
|---|---|
| cluster-admin | Full cluster control. |
| Wildcard verbs/resources | Future and unintended access. |
| secrets get/list/watch | Secret content exposure. |
| pods create | Can mount secrets, use service accounts, choose security context within allowed policy. |
| rolebindings/clusterrolebindings create | Can grant permissions if bind controls are weak. |
| roles/clusterroles escalate | Can create roles beyond own permissions. |
| impersonate | Can act as another subject. |
| nodes/proxy | Kubelet API exposure and audit/admission bypass concerns. |
| namespace patch | May alter security labels and policy selectors. |
| persistentvolumes create | May enable hostPath-like or sensitive storage access patterns depending on policy. |

### 51.3 Control evidence quick map

| Control | Evidence |
|---|---|
| RBAC least privilege | RBAC inventory, audit logs, access reviews. |
| Pod Security | Namespace labels, admission logs, pod specs. |
| NetworkPolicy | Policy objects, CNI enforcement logs, flow validation. |
| Secrets encryption | Encryption configuration, KMS logs, etcd storage validation. |
| Service-account governance | SA inventory, token settings, RBAC bindings, workload identity mapping. |
| API protection | Endpoint exposure, auth logs, network logs, failed auth events. |
| Node hardening | Node config, kubelet logs, patch state, runtime telemetry. |
| Recovery | etcd/PV backup status, restore test results, backup encryption evidence. |

## 52. Final Engineering Checklist

A Kubernetes environment is engineering-ready when all of the following are true:

- cluster ownership, risk tier, environment, data classification, and business owner are recorded;
- API server exposure is private or strictly restricted and monitored;
- authentication is integrated with governed identity and MFA/conditional access where applicable;
- RBAC has no unmanaged cluster-admin sprawl and no unnecessary bind/escalate/impersonate rights;
- namespaces have owners, quotas, Pod Security labels, NetworkPolicy posture, and exception records;
- admission controls enforce pod security, image trust, resource controls, and exposure policy;
- secrets are encrypted at rest, access-controlled, rotated, and not broadly listable/watchable;
- service accounts are workload-specific, least-privileged, and not automatically mounted where unnecessary;
- workload identity to cloud IAM is least-privileged and reviewed jointly with Kubernetes RBAC;
- NetworkPolicy is enforced by the actual CNI and validated safely;
- ingress, LoadBalancer, NodePort, and ExternalName usage is governed;
- node pools are separated by trust level and nodes are hardened, patched, logged, and rebuildable;
- privileged pods, hostPath, host networking, and privileged DaemonSets are minimized, approved, and monitored;
- CRDs/operators are security-reviewed and scoped;
- etcd and persistent-volume backups are encrypted, protected, and restore-tested;
- audit logs, Events, node/runtime/CNI/ingress/DNS/cloud logs are exported with retention and integrity controls;
- incident response can map user -> API request -> object -> controller -> pod -> node -> image -> network/cloud side effect;
- safe validation is performed regularly and documented;
- unsafe testing and offensive procedures are explicitly prohibited outside authorized security programs.

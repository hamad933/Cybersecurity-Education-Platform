# CKV-022 — Windows Access Control Internals: Tokens, SIDs, ACLs, SRM

## 1. Purpose

Windows access control internals explain how Windows decides whether a subject can perform an operation on a protected object.

This file owns the authorization layer behind Windows security decisions:

- security principals and SIDs;
- access tokens;
- primary and impersonation tokens;
- group SIDs, deny-only SIDs, restricted SIDs, and token attributes at a practical level;
- privileges and user rights as authorization gates separate from ordinary permissions;
- securable objects and security descriptors;
- owner, DACL, SACL, ACEs, access masks, and rights mapping;
- Security Reference Monitor reasoning;
- SeAccessCheck reasoning;
- requested access versus granted access;
- handle-based access behavior;
- object access auditing relationship;
- access-denied troubleshooting logic;
- token and ACL investigation logic.

This file does not own NTFS permission operations, UAC/elevation internals, Active Directory internals, Kerberos internals, NTLM internals, Group Policy internals, Windows exploitation, endpoint detection engineering, or digital forensics. Those are referenced in Section 26.

Core purpose:

```text
Make Windows authorization explainable.

Subject identity is not enough.
Group membership is not enough.
Administrative status is not enough.
The decision is made from token + object security descriptor + requested access + runtime context.
```

## 2. Core Definition

**Windows access control** is the operating-system authorization model that determines whether a security principal represented in an access token is allowed to perform a requested operation against a securable object protected by a security descriptor.

The canonical decision model is:

```text
Windows authorization decision
= Subject token
+ Object security descriptor
+ Requested access mask
+ Privilege checks where applicable
+ Mandatory/integrity behavior where applicable
+ Object-specific rules
+ Audit policy / SACL result where configured
```

At the center of the model:

```text
Subject side:
- user SID
- group SIDs
- enabled / disabled / deny-only attributes
- privileges
- restricted SIDs if present
- token type and impersonation level
- default DACL
- session/logon context

Object side:
- security descriptor
- owner
- DACL
- SACL
- control flags
- object-specific rights

Request side:
- desired access mask
- generic rights mapped to object-specific rights
- operation being attempted
- handle creation or object operation context
```

Practical rule:

```text
Authentication proves who the subject is.
Authorization decides what that subject can do.
Windows authorization is evaluated against a token, not against a username string.
```

## 3. Why Windows Access Control Internals Matter

Windows access control internals matter because most real Windows security outcomes are authorization outcomes:

- a user can open a file;
- a service can write to a registry key;
- an administrator can change ownership;
- a process can duplicate a handle;
- a thread can impersonate a client;
- a scheduled task can run as a privileged account;
- a remote request is evaluated under a server-side token;
- an audit event is generated or missed;
- access is denied even though the user appears to be in the right group;
- access is allowed because a privilege bypasses ordinary DACL reasoning;
- an old handle remains usable after permissions are changed.

Security engineers need these internals to answer:

- Which identity was actually evaluated?
- Was the process using its primary token or a thread impersonation token?
- Which SIDs were enabled, disabled, deny-only, or restricted?
- Was the requested access too broad?
- Did the DACL allow the needed rights?
- Did a deny ACE block access?
- Did the owner, a privilege, or a special right affect the result?
- Was access granted at handle-open time and cached in the handle?
- Was the failure caused by authentication, authorization, integrity, network access, share permissions, or object permissions?
- Was the action auditable, and was auditing actually configured?

Access control internals are foundational for:

- least privilege;
- privilege escalation prevention;
- service hardening;
- administrative separation;
- file, registry, service, process, and object security;
- remote administration control;
- access-denied troubleshooting;
- object access auditing;
- incident investigation;
- Windows hardening validation.

High-value security truth:

```text
Many Windows security failures are not caused by missing authentication.
They are caused by wrong authorization: wrong token, wrong ACE, wrong privilege, wrong inheritance, wrong owner, or wrong handle context.
```

## 4. Windows Authorization Mental Model

Windows authorization can be reasoned as four linked planes.

| Plane          | Question                   | Main Objects                                                                |
| -------------- | -------------------------- | --------------------------------------------------------------------------- |
| Subject plane  | Who is acting?             | Access token, user SID, group SIDs, privileges, restrictions                |
| Object plane   | What is protected?         | File, registry key, process, service, pipe, directory object, kernel object |
| Policy plane   | What rules protect it?     | Security descriptor, owner, DACL, SACL, ACEs, access masks                  |
| Decision plane | Is this operation allowed? | SRM, access check, privilege check, granted-access mask, handle             |

Canonical flow:

```text
1. A user, service, or process obtains a security context.
2. Windows represents that context as an access token.
3. A process or thread requests access to a securable object.
4. The object has a security descriptor.
5. The requested operation maps to an access mask.
6. Windows evaluates token + security descriptor + requested access.
7. If allowed, a handle is created with a granted-access mask.
8. Later operations through that handle are limited by the granted-access mask.
9. If auditing is configured, object access evidence may be generated.
```

Access control is not a single permission table. It is a runtime decision path.

Key interpretation rules:

- The evaluated subject is the effective token, not necessarily the visible logged-on user.
- A process has a primary token, but a thread may temporarily use an impersonation token.
- A group SID inside a token may be enabled, disabled, deny-only, or restricted.
- A privilege is not the same thing as an allow ACE.
- A DACL controls access; a SACL controls auditing.
- Handles store granted access after the open operation.
- Changing an ACL does not necessarily change access already granted through an existing handle.
- Access denied means a specific requested right failed in a specific runtime context.

## 5. Security Principals and SIDs

A **security principal** is an entity that can be authenticated or represented for authorization.

Common Windows security principals include:

- local users;
- domain users;
- local groups;
- domain groups;
- computer accounts;
- service accounts;
- managed service accounts;
- built-in accounts;
- built-in groups;
- logon session identities;
- service SIDs;
- application package or capability identities where applicable.

A **SID**, or Security Identifier, is the canonical identifier Windows uses for a security principal. Names are display labels. SIDs are what access control uses.

Practical SID model:

```text
Human-readable name:
  CONTOSO\Alice

Authorization identity:
  SID representing Alice

Group authorization material:
  SID for Domain Users
  SID for Finance
  SID for local Users
  SID for other enabled groups
```

Why SIDs matter:

- Names can change; SIDs remain the stable authorization identifier.
- ACEs store SIDs, not merely account names.
- Group membership becomes group SIDs in the token.
- Deleted or unresolved accounts may appear as orphaned SIDs in ACLs.
- Well-known SIDs represent built-in identities such as Everyone, Authenticated Users, SYSTEM, Administrators, Users, and Local Service/Network Service concepts.
- Service SIDs allow a service identity to be granted access without granting broad access to all services.
- Logon SIDs can distinguish one logon session from another.

Security principal reasoning:

| Principal Type         | Security Relevance                                                                    |
| ---------------------- | ------------------------------------------------------------------------------------- |
| User SID               | Represents the account directly.                                                      |
| Group SID              | Expands access through role or group membership.                                      |
| Built-in SID           | Represents platform-defined identities and groups.                                    |
| Service SID            | Enables service-specific least privilege.                                             |
| Computer SID/account   | Represents machine identity in local/domain contexts.                                 |
| Logon SID              | Represents a specific logon session boundary.                                         |
| Capability/package SID | Supports application/container-like permission boundaries in modern Windows contexts. |

Practical rule:

```text
When troubleshooting access, resolve names to SIDs and SIDs to names.
The ACL might be correct for a name but wrong for the SID actually present in the token.
```

Common SID-related mistakes:

- assuming a renamed account changed its SID;
- granting access to a user directly instead of a role/group;
- ignoring orphaned SIDs;
- confusing local Administrators with domain administrative groups;
- confusing local account identity across machines;
- forgetting that nested/domain group expansion depends on logon/authentication context;
- assuming a group exists in the token just because the directory currently shows membership.

## 6. Access Tokens

An **access token** is the Windows object that describes the security context of a process or thread.

A token commonly contains:

- user SID;
- group SIDs;
- group attributes;
- privileges;
- token type;
- impersonation level when applicable;
- default owner behavior;
- default DACL;
- restricted SIDs if applicable;
- logon/session identifiers;
- integrity-related information at a high level;
- source/authentication context metadata at a practical level.

Token mental model:

```text
Access token = "authorization passport" presented to Windows access checks.

It says:
- who the subject is;
- which groups are available for access checks;
- which privileges are available;
- whether some groups are deny-only or restricted;
- whether the token is process-level or thread-level;
- what default security should be used for new objects.
```

A token is created when Windows establishes a logon/security context. The exact authentication path may be local, domain, cached, network, service, or other context, but the authorization result is still evaluated through a token.

Important token fields at practical level:

| Token Element             | Meaning                                                                                               |
| ------------------------- | ----------------------------------------------------------------------------------------------------- |
| User SID                  | Primary identity being represented.                                                                   |
| Group SIDs                | Authorization groups available to match ACEs.                                                         |
| Group attributes          | Whether a group is enabled, disabled, deny-only, mandatory, or otherwise marked.                      |
| Privileges                | System rights such as backup, restore, debug, impersonate, take ownership, and audit/security access. |
| Default DACL              | Template used when the process creates securable objects without explicitly supplying a DACL.         |
| Restricted SIDs           | Additional limiting set used by restricted tokens.                                                    |
| Token type                | Primary token or impersonation token.                                                                 |
| Impersonation level       | How far a server thread can act as a client.                                                          |
| Logon/session identifiers | Context for the logon session and session isolation.                                                  |

Token refresh rule:

```text
A token is usually a snapshot of authorization material at logon/security-context creation time.
Changing group membership later does not automatically rewrite every existing token.
```

Security implications:

- Group membership changes may require logoff/logon, service restart, token renewal, or new network authentication.
- A service may keep using an old token until restarted.
- A remote session may not reflect recent membership changes.
- A cached/offline logon may lack current domain authorization material.
- A token may contain administrator-related SIDs as deny-only or filtered.
- A token may include privileges that change authorization outcomes independently of DACL allow entries.

## 7. Primary Tokens and Impersonation Tokens

Windows uses two major token types:

| Token Type          | Attached To | Main Purpose                                                    |
| ------------------- | ----------- | --------------------------------------------------------------- |
| Primary token       | Process     | Defines the default security context of the process.            |
| Impersonation token | Thread      | Allows a thread to act temporarily as another security context. |

A process normally runs with a primary token. When the process opens a securable object, Windows uses that primary token unless the current thread is impersonating.

Thread token rule:

```text
If a thread has an impersonation token, the impersonation token is evaluated.
If a thread does not have an impersonation token, the process primary token is evaluated.
```

This rule is critical for services, web servers, SMB servers, RPC servers, COM servers, WinRM, WMI, and any system that processes requests on behalf of clients.

Common server-side pattern:

```text
Service process primary token:
  ServiceAccount or LocalSystem

Incoming client request:
  Client authenticates to the service

Request-handling thread:
  May attach an impersonation token for the client

Access check:
  If impersonating -> checks client context
  If not impersonating -> checks service process context
```

Impersonation levels at practical level:

| Level          | Practical Meaning                                                                         |
| -------------- | ----------------------------------------------------------------------------------------- |
| Anonymous      | Server cannot identify or act as the client.                                              |
| Identification | Server can identify the client but cannot fully act as the client.                        |
| Impersonation  | Server can act as the client locally.                                                     |
| Delegation     | Server can act as the client to remote systems when policy and authentication support it. |

Security implications:

- A service may access local files as the client but remote resources as the service identity.
- A failure may be caused by delegation limitations, not by local file permissions.
- The same user may receive different results through different services because the effective token differs.
- Impersonation is powerful and must be tightly controlled because it changes the subject evaluated by access checks.
- Impersonation-related privileges and service design affect lateral access paths.

Practical access-denied question:

```text
Which token was evaluated: the process primary token or the thread impersonation token?
```

## 8. Groups, Privileges, and User Rights in Tokens

Windows authorization uses both **group-based rights** and **privileges/user rights**.

They are related but not identical.

| Concept         | What It Does                                                                 |
| --------------- | ---------------------------------------------------------------------------- |
| Group SID       | Matches allow/deny ACEs in DACLs.                                            |
| Group attribute | Controls how a group SID participates in access checks.                      |
| Privilege       | Allows or gates specific system-level operations.                            |
| User right      | Policy assignment that grants a logon right or privilege to accounts/groups. |
| Permission      | Object-specific access granted or denied by ACEs.                            |

Examples of security-relevant privileges:

- `SeBackupPrivilege` — backup-style access behavior in supported contexts;
- `SeRestorePrivilege` — restore/write ownership/security behavior in supported contexts;
- `SeDebugPrivilege` — powerful process inspection/manipulation capability;
- `SeImpersonatePrivilege` — ability to impersonate after authentication in supported contexts;
- `SeAssignPrimaryTokenPrivilege` — assigning primary tokens in controlled contexts;
- `SeTcbPrivilege` — act as part of the operating system; extremely sensitive;
- `SeTakeOwnershipPrivilege` — take ownership of protected objects;
- `SeSecurityPrivilege` — manage auditing/security log or access SACL-related security information where applicable;
- `SeLoadDriverPrivilege` — load device drivers; high impact;
- `SeServiceLogonRight` — log on as a service;
- `SeRemoteInteractiveLogonRight` — log on through Remote Desktop Services.

Important distinction:

```text
A permission grants access to an object.
A privilege grants or gates a class of system operation.
```

Group attributes matter because a SID present in the token may not behave as a normal enabled allow SID.

Common group attribute states at practical level:

| Attribute State                       | Security Meaning                                                           |
| ------------------------------------- | -------------------------------------------------------------------------- |
| Enabled                               | Can satisfy allow ACEs and can match deny ACEs.                            |
| Disabled                              | Present but not used normally for allow decisions.                         |
| Deny-only                             | Can match deny logic but not normal allow logic.                           |
| Mandatory/default-enabled indicators  | Affect how Windows treats group participation.                             |
| Integrity or label-related attributes | Affect mandatory behavior, but deep integrity semantics belong to CKV-023. |

Practical rules:

- Privileges are not ordinary DACL permissions.
- A disabled privilege may need to be enabled before use.
- Removing an ACE does not remove a privilege.
- Removing a privilege does not rewrite every DACL.
- User rights are usually assigned through local or domain policy.
- Excessive privileges on service accounts can be more dangerous than broad file permissions.

## 9. Restricted Tokens and Token Filtering at Practical Level

A **restricted token** is a token modified to reduce what the subject can access or do.

Restricted-token purpose:

- reduce attack impact;
- sandbox processes;
- limit access to only a subset of resources;
- remove or disable privileges;
- mark groups as deny-only;
- require access to satisfy both normal and restricted authorization constraints.

Practical restricted-token model:

```text
Normal token check:
  Token SIDs must satisfy object DACL.

Restricted token check:
  Normal token SIDs must satisfy access
  AND restricted SID set must also satisfy access where restriction applies.
```

Token filtering is a broader practical term for reducing token capability. It may include:

- disabling privileges;
- removing privileges;
- changing group attributes;
- marking administrative SIDs as deny-only;
- limiting default access;
- adding restricted SID behavior.

Security relevance:

- A user may be a member of a group, but the group may not grant access if it is deny-only or filtered.
- A process may fail access even when the apparent account is powerful.
- A service or application may intentionally run with a restricted token for containment.
- Some failures are caused by token filtering, not by object ACLs.

Boundary note:

```text
This file explains restricted-token and filtering effects only as access-control internals.
UAC split tokens, elevation prompts, integrity levels, and elevation semantics are owned by CKV-023.
```

## 10. Securable Objects

A **securable object** is a Windows object that can have a security descriptor and can be protected by access control.

Common securable objects include:

- files;
- directories;
- registry keys;
- processes;
- threads;
- services;
- printers;
- shares at a practical management level;
- named pipes;
- mailslots;
- events;
- mutexes;
- semaphores;
- sections/shared memory;
- jobs;
- window stations;
- desktops;
- directory service objects;
- some kernel and object-manager-managed resources.

Securable object mental model:

```text
Object
  + Security descriptor
  + Object type
  + Object-specific rights
  + Inheritance behavior if applicable
  + Audit behavior if configured
```

Why object type matters:

- A file has file-specific rights.
- A registry key has registry-specific rights.
- A process has process-specific rights.
- A service has service-specific rights.
- A directory service object has directory-specific rights.
- Generic rights such as Read, Write, Execute, and All are mapped differently depending on object type.

Practical rule:

```text
Do not read an access mask without knowing the object type.
The same generic access name can map to different specific rights on different objects.
```

Security relevance:

- Process object rights can allow sensitive inspection or manipulation.
- Service object rights can allow service reconfiguration or control.
- Registry key rights can allow configuration tampering or persistence.
- Named pipe and COM/RPC-related access can affect local privilege boundaries.
- Directory object rights can affect enterprise identity security.
- File object rights affect code execution, data exposure, and persistence paths.

## 11. Security Descriptors

A **security descriptor** is the object-side security structure that describes who owns an object, who can access it, and what access should be audited.

Core security descriptor components:

| Component           | Purpose                                                                                 |
| ------------------- | --------------------------------------------------------------------------------------- |
| Owner               | Principal that owns the object and can often change its DACL.                           |
| Primary group       | Compatibility field; rarely the main authorization driver in modern Windows operations. |
| DACL                | Discretionary Access Control List; controls allowed and denied access.                  |
| SACL                | System Access Control List; controls auditing and some mandatory policy labels.         |
| Control information | Flags describing inheritance, protection, defaulting, and presence of ACLs.             |

Security descriptor mental model:

```text
Security Descriptor
├── Owner
├── Group
├── DACL
│   ├── ACE 1
│   ├── ACE 2
│   └── ACE n
├── SACL
│   ├── audit ACEs / labels where applicable
│   └── audit rules
└── Control flags
```

Key truths:

- The DACL is the normal permission enforcement list.
- The SACL is not the normal allow/deny permission list.
- The owner is not the same thing as full data access.
- Control flags affect inheritance, protection, and whether ACLs are present or defaulted.
- Security descriptors can be inherited, protected from inheritance, explicitly configured, defaulted, or malformed/misleading in real systems.

DACL presence is security-critical:

| DACL State                      | Practical Result                                          |
| ------------------------------- | --------------------------------------------------------- |
| Proper DACL with ACEs           | Access depends on ACE evaluation.                         |
| Empty DACL                      | Usually grants no access through ordinary ACEs.           |
| NULL DACL                       | Dangerous; broadly permits access.                        |
| Missing/defaulted DACL behavior | Must be interpreted carefully in object-creation context. |

Practical rule:

```text
Do not confuse "no ACE grants access" with "no DACL protects the object."
An empty DACL and a NULL DACL are opposite security conditions.
```

## 12. Owner, DACL, SACL, and Control Information

The four most important security descriptor areas are owner, DACL, SACL, and control information.

### Owner

The owner is the principal recorded as owning the object.

Security relevance:

- ownership is powerful because the owner can usually change the DACL;
- ownership does not automatically mean ordinary read/write/execute access to object data;
- taking ownership is a sensitive operation;
- unplanned owner changes can be a sign of privilege misuse or remediation drift;
- ownership affects recovery from bad ACLs.

Practical owner rule:

```text
Owner means permission-control power, not automatic data-control permission.
```

### DACL

The DACL contains allow and deny ACEs that determine ordinary access.

DACL decides:

- whether the token can read;
- whether the token can write;
- whether the token can execute;
- whether the token can delete;
- whether the token can modify permissions;
- whether the token can take or assign ownership depending on rights and privileges;
- whether object-type-specific operations are allowed.

### SACL

The SACL controls audit behavior and security labels where applicable.

SACL relevance:

- can generate success and failure audit events;
- can record attempts to access sensitive objects;
- can support object access accountability;
- can contain mandatory label/integrity-related information in supported contexts;
- requires appropriate rights/privileges to read or modify.

Practical SACL rule:

```text
A SACL is evidence configuration, not normal access permission.
No SACL means access may happen without object-level audit evidence.
```

### Control information

Control flags describe security descriptor state.

Common control concepts:

- DACL present or not present;
- SACL present or not present;
- DACL protected from inheritance;
- SACL protected from inheritance;
- DACL auto-inherited;
- SACL auto-inherited;
- defaulted owner/DACL/SACL indicators;
- self-relative representation at storage/API level.

Security relevance:

- inheritance may silently add or remove access;
- protected DACLs may stop expected baseline inheritance;
- defaulted descriptors may not match hardening intent;
- copied/moved objects may keep or recalculate security differently depending on operation and target.

## 13. ACEs and ACL Structure

An **ACL**, or Access Control List, is an ordered list of ACEs.

An **ACE**, or Access Control Entry, is one rule inside an ACL.

Practical ACE fields:

| ACE Element        | Meaning                                                                            |
| ------------------ | ---------------------------------------------------------------------------------- |
| ACE type           | Allow, deny, audit, object-specific, inherited, callback, or other supported type. |
| SID/trustee        | Principal the ACE applies to.                                                      |
| Access mask        | Rights allowed, denied, or audited.                                                |
| Flags              | Inheritance, propagation, audit success/failure, and object-specific behavior.     |
| Object type fields | Used in object-specific ACEs such as directory service rights.                     |

DACL ACE types at practical level:

- allow ACEs grant specified rights when the SID matches and is usable;
- deny ACEs deny specified rights when the SID matches;
- inherited ACEs come from a parent object;
- explicit ACEs are directly assigned to the object;
- object-specific ACEs apply to certain object types or property/attribute scopes.

SACL ACE types at practical level:

- audit success;
- audit failure;
- audit specific rights;
- mandatory label behavior where applicable.

Canonical ACE order concept:

```text
Preferred DACL ordering:
1. Explicit deny ACEs
2. Explicit allow ACEs
3. Inherited deny ACEs
4. Inherited allow ACEs
```

Real-world warning:

```text
ACE order can affect results.
Do not evaluate an ACL as an unordered list of permissions.
```

ACE evaluation depends on:

- SID match;
- group attributes;
- deny-only behavior;
- restricted token behavior;
- requested rights;
- object type;
- inheritance and flags;
- ACE order;
- generic-right mapping;
- owner/privilege effects;
- mandatory/integrity checks where applicable.

## 14. Access Masks, Generic Rights, Standard Rights, and Specific Rights

An **access mask** is a bitmask representing requested, granted, denied, allowed, or audited rights.

Windows authorization does not simply ask “can this user access the object?” It asks whether a token is allowed to receive specific rights represented in an access mask.

Common right categories:

| Right Category         | Meaning                                                                                                        |
| ---------------------- | -------------------------------------------------------------------------------------------------------------- |
| Generic rights         | High-level rights such as Generic Read, Generic Write, Generic Execute, Generic All.                           |
| Standard rights        | Rights common across many object types, such as delete, read control, write DACL, write owner, synchronize.    |
| Specific rights        | Rights unique to an object type, such as file read data, registry set value, process terminate, service start. |
| System/security rights | Rights such as accessing SACL/security information where special privileges may be required.                   |

Generic rights must be mapped to object-specific rights.

Example concept:

```text
Generic Read on a file != Generic Read on a registry key != Generic Read on a process.

Generic label -> object type mapping -> specific rights bits.
```

Important standard rights:

| Standard Right | Practical Meaning                                                  |
| -------------- | ------------------------------------------------------------------ |
| DELETE         | Permission to delete the object where object semantics allow.      |
| READ_CONTROL   | Permission to read security descriptor information except SACL.    |
| WRITE_DAC      | Permission to change the DACL.                                     |
| WRITE_OWNER    | Permission to change ownership.                                    |
| SYNCHRONIZE    | Permission to wait on/synchronize with an object where applicable. |

Important access-request concepts:

- **Desired access** is what the caller asks for.
- **Granted access** is what Windows grants after access check.
- **Maximum allowed** asks Windows to compute the maximum rights available for the token.
- **Generic all** is often much broader than the operation actually needs.
- **Requesting too much** can cause access denied even if a narrower request would succeed.

Practical rule:

```text
Access denied may mean: "you asked for too many rights," not "you have no rights at all."
```

## 15. Security Reference Monitor and SeAccessCheck Reasoning

The **Security Reference Monitor** is the Windows kernel security component family responsible for enforcing core security decisions for securable objects and related operations.

`SeAccessCheck` reasoning is the practical access-check model used to determine whether a token should receive requested access to an object protected by a security descriptor.

Canonical access-check reasoning:

```text
Input:
- token
- security descriptor
- requested access mask
- object type / generic mapping
- object manager or subsystem context

Processing:
- map generic rights to object-specific rights
- account for owner-related behavior
- account for privilege-sensitive operations where applicable
- evaluate mandatory/integrity constraints where applicable
- walk DACL ACEs in order
- deny requested bits when matching deny ACEs apply
- satisfy requested bits when matching allow ACEs apply
- fail if any requested bits remain unsatisfied
- generate audit result if SACL/audit policy applies

Output:
- granted access mask or access denied
```

Simplified DACL walk model:

```text
remaining = requested_access

for each ACE in DACL order:
    if ACE SID matches token authorization context:
        if ACE denies any requested remaining bits:
            deny
        if ACE allows requested bits:
            remove allowed bits from remaining

if remaining is empty:
    allow
else:
    deny
```

Important caveats:

- Real Windows access checks include object-type-specific behavior.
- Privilege checks can affect certain operations outside ordinary DACL grants.
- Mandatory integrity behavior can block some write-up scenarios; full integrity semantics belong to CKV-023.
- Restricted tokens can require additional limiting checks.
- Directory service objects use Windows security descriptor concepts but have directory-specific rights and evaluation details.
- Applications may add their own authorization logic after the operating-system check.

High-value security truths:

- DACLs are evaluated against SIDs and attributes in the token.
- Deny ACEs can block access even when allow ACEs exist.
- Allow ACEs only grant the specific bits they cover.
- Owner behavior affects permission management, not automatic data access.
- Privileges can enable operations that normal DACL reasoning would not grant.
- Handle-granted access is the result that matters after open.

## 16. Requested Access vs Granted Access

**Requested access** is the access mask a caller asks for when opening or operating on an object.

**Granted access** is the access mask Windows grants after successful authorization.

This distinction is critical.

Example:

```text
Caller wants to read a file.
Bad request:
  asks for GENERIC_ALL
  -> denied because token does not have full control

Better request:
  asks for FILE_READ_DATA / GENERIC_READ equivalent
  -> allowed if token has read rights
```

Why requested access matters:

- Applications often request more rights than they need.
- Administrative tools may request broad rights.
- Security tools may require special rights to inspect objects.
- A user may be able to read but not write, write but not delete, or query but not modify.
- Troubleshooting must identify the exact denied right, not only the object path.

Common requested-access patterns:

| Request Pattern         | Security Meaning                                                                    |
| ----------------------- | ----------------------------------------------------------------------------------- |
| Read-only request       | Least risky; may succeed when broader access fails.                                 |
| Write request           | Enables modification; must be tightly controlled.                                   |
| Delete request          | Often separate from write/modify semantics.                                         |
| Write DACL request      | Allows changing permissions; highly sensitive.                                      |
| Write owner request     | Allows ownership changes; highly sensitive.                                         |
| Maximum allowed request | Computes broad available rights; useful but can hide least-privilege design errors. |
| Generic all request     | Often overbroad; should be avoided unless truly required.                           |

Practical rule:

```text
Troubleshoot access denied by identifying the denied right, not only the denied object.
```

Security engineering rule:

```text
Applications and services should request the minimum access needed for the operation.
Least privilege applies to API access masks, not only to user role design.
```

## 17. Handles and Granted Access

A **handle** is a reference to an opened object. When access is granted, the handle normally carries a granted-access mask describing what operations can be performed through that handle.

Handle mental model:

```text
Open object request
  -> access check
  -> granted access mask
  -> handle returned
  -> later operations checked against handle's granted access
```

Security implications:

- Access is commonly checked at object-open time.
- The granted-access mask is stored with the handle.
- Later use of the handle is limited by the rights granted to that handle.
- Changing the DACL after a handle is opened does not necessarily revoke rights already granted through that existing handle.
- Closing and reopening the object can produce a different authorization result.
- Handle duplication can transfer access in ways that must be controlled.
- Inherited handles can accidentally extend access into child processes.

High-value rule:

```text
ACL changes affect future access checks.
They do not automatically erase every already-open handle.
```

Investigation implications:

- A process may still access an object after permissions were tightened because it already has a handle.
- Two processes with the same user context may have different access because their handles were opened with different granted masks.
- A tool may fail because it opened the object with insufficient rights, even though the user has broader rights available.
- A malicious or misconfigured process with a powerful handle may represent a higher risk than its visible account name suggests.

Security engineering controls:

- avoid broad handle inheritance;
- minimize requested access;
- close handles when no longer needed;
- restart services when permission changes must take effect for long-running processes;
- monitor unusual process handle access where appropriate;
- treat handle duplication and process object rights as sensitive.

## 18. Object Access Auditing Relationship

Object access auditing connects access control to accountability evidence.

Object access audit evidence normally depends on:

- audit policy being enabled;
- the object having a relevant SACL;
- the requested access matching the SACL audit rule;
- success/failure audit selection;
- event log configuration and retention;
- the effective token and process context;
- collection/forwarding of relevant Windows security events.

SACL model:

```text
DACL = who is allowed or denied
SACL = which attempts should be audited
```

Object access auditing can help prove:

- who attempted access;
- which object was targeted;
- which process or logon context was involved;
- whether access succeeded or failed;
- which access mask/rights were requested or used in logged events;
- when the event occurred;
- whether sensitive access paths are being exercised.

Common audit use cases:

- sensitive file access;
- registry key access;
- service-control access;
- privileged object changes;
- permission changes;
- ownership changes;
- failed access attempts;
- high-risk administrative operations.

Audit limitations:

- No SACL means object-level access may not be logged.
- No audit policy means SACL intent may not produce useful events.
- Excessive auditing creates noise and storage pressure.
- Logs show what was recorded, not necessarily the full business reason.
- Some application-level authorization failures may not appear as object access failures.
- Network-layer or application-layer denials may occur before object access happens.

Security rule:

```text
Access control without auditability is difficult to prove.
Auditability without correct access control only records failure after exposure.
```

## 19. Common Access-Control Failure Modes

Windows access-control failures usually fall into repeatable patterns.

| Failure Mode                            | Practical Explanation                                                                         |
| --------------------------------------- | --------------------------------------------------------------------------------------------- |
| Wrong effective token                   | Thread impersonation, service identity, or scheduled task context differs from expected user. |
| Token not refreshed                     | Group or privilege change has not reached the existing token.                                 |
| Deny-only group                         | Group appears present but cannot satisfy allow ACEs.                                          |
| Disabled privilege                      | Privilege exists but is not enabled for the operation.                                        |
| Missing privilege                       | Operation requires a privilege, not only an allow ACE.                                        |
| Overbroad requested access              | Caller asks for more rights than needed and fails.                                            |
| Deny ACE blocks access                  | Explicit or inherited deny applies to a matching SID.                                         |
| Inheritance surprise                    | Parent ACL grants or blocks access unexpectedly.                                              |
| Protected DACL surprise                 | Object stopped inheriting expected baseline permissions.                                      |
| NULL DACL                               | Object is dangerously open.                                                                   |
| Empty DACL                              | Object appears configured but ordinary access is denied.                                      |
| Owner confusion                         | Owner can change DACL but does not automatically have data access.                            |
| Handle caching                          | Existing handle retains rights after ACL changes.                                             |
| Share vs object mismatch                | Network share and object permissions are confused.                                            |
| Local vs domain context mismatch        | Same name or group label means different SID/context.                                         |
| Service SID missing                     | Service-specific least privilege was not granted.                                             |
| SACL missing                            | Access happened but no object-level evidence exists.                                          |
| Integrity/elevation mismatch            | Access blocked by integrity/elevation behavior owned by CKV-023.                              |
| Directory-specific rights misunderstood | AD objects use security descriptors plus directory-specific rights.                           |

High-value troubleshooting principle:

```text
Never troubleshoot Windows access control from account name alone.
Troubleshoot from effective token + requested access + object security descriptor + handle/runtime context.
```

## 20. Access-Denied Troubleshooting Logic

Access denied must be decomposed into a specific failed authorization condition.

Canonical troubleshooting sequence:

```text
1. Identify the operation.
2. Identify the object.
3. Identify the exact requested right.
4. Identify the effective token.
5. Identify whether a primary or impersonation token was used.
6. Inspect token SIDs, group attributes, privileges, and restrictions.
7. Inspect the object's security descriptor.
8. Evaluate DACL order, allow ACEs, deny ACEs, inheritance, and owner.
9. Check privileges required for the operation.
10. Check integrity/elevation behavior if relevant.
11. Check handle state and whether an old handle is being reused.
12. Check share/network/application gates if access is remote.
13. Check SACL/audit events for success/failure evidence.
14. Reproduce with minimum requested access where safe.
```

Questions to ask:

- What object type is being accessed?
- Is the object a file, registry key, process, service, directory object, pipe, or another securable object?
- What exact operation is being attempted?
- Which access mask is being requested?
- Is the caller requesting Generic All when only Read is needed?
- Which process is making the request?
- Which thread is making the request?
- Is the thread impersonating?
- Which token is effective at the moment of the request?
- Does the token contain the required SID as enabled?
- Is a required group disabled or deny-only?
- Does the token have a required privilege?
- Is the privilege enabled?
- Does the object DACL contain an explicit deny?
- Does inheritance add or remove an ACE?
- Is the object DACL protected from inheritance?
- Is the owner relevant to DACL modification?
- Is the access denied by Windows object authorization or by application logic?
- Is the denial actually caused by share permissions, network path, authentication, delegation, or integrity behavior?
- Is the object-level audit trail configured?

Practical decision tree:

```text
Access denied
├── Authentication failed?
│   └── identity never obtained a valid security context
├── Wrong effective token?
│   └── service/thread/impersonation context mismatch
├── Requested access too broad?
│   └── narrower request may succeed
├── DACL denies or fails to allow?
│   └── ACE order/inheritance/SID attributes matter
├── Privilege missing or disabled?
│   └── operation is privilege-gated
├── Integrity/elevation issue?
│   └── CKV-023 owns deep analysis
├── Existing handle issue?
│   └── granted access differs from current ACL
├── Remote access layer issue?
│   └── share, delegation, protocol, or service gate
└── Application-specific authorization?
    └── OS allowed object access but app denies action
```

Investigation minimum evidence:

- object path or object identifier;
- object type;
- operation attempted;
- requested access where available;
- process and thread context;
- effective user/account;
- token groups and privileges;
- object owner;
- DACL and inheritance state;
- SACL/audit configuration;
- relevant Windows event logs;
- application/service logs where applicable;
- recent change records.

## 21. Token and ACL Security Investigation Logic

Token and ACL investigation asks whether a Windows authorization result matches security intent.

Investigation objectives:

- prove which identity was evaluated;
- prove which object was protected;
- prove which rights were requested;
- prove why access was granted or denied;
- detect privilege drift;
- detect overbroad access;
- detect dangerous ownership;
- detect risky privileges;
- detect unexpected inheritance;
- detect missing auditability;
- detect token/context mismatch;
- support remediation with evidence.

Token investigation checklist:

| Evidence Area       | Questions                                                              |
| ------------------- | ---------------------------------------------------------------------- |
| User SID            | Which account is represented?                                          |
| Group SIDs          | Which groups are actually in the token?                                |
| Group attributes    | Are relevant groups enabled, disabled, or deny-only?                   |
| Privileges          | Which privileges are present and enabled?                              |
| Token type          | Primary or impersonation token?                                        |
| Impersonation level | Can the thread act locally only or delegate remotely?                  |
| Restrictions        | Is the token restricted or filtered?                                   |
| Session/logon       | Which logon session created the token?                                 |
| Service context     | Is this a service, scheduled task, interactive app, or remote request? |

ACL/security descriptor checklist:

| Evidence Area | Questions                                                    |
| ------------- | ------------------------------------------------------------ |
| Owner         | Who controls permission changes?                             |
| DACL state    | Present, empty, NULL, inherited, protected, explicit?        |
| ACE order     | Are deny/allow/inherited entries ordered safely?             |
| Trustees      | Are ACE SIDs valid, orphaned, broad, or direct-user grants?  |
| Rights        | Are rights least-privilege or excessive?                     |
| Inheritance   | Are permissions inherited from the expected parent?          |
| SACL          | Is auditing configured for sensitive access?                 |
| Control flags | Is inheritance protected or defaulted unexpectedly?          |
| Object type   | Are rights interpreted according to the correct object type? |

Security investigation patterns:

- compare effective access against intended role;
- compare current security descriptor against baseline;
- identify broad trustees such as Everyone, Authenticated Users, Users, Domain Users, or overly broad service groups;
- identify direct user grants that bypass role governance;
- identify write access to high-impact objects;
- identify rights that permit permission change, ownership change, service reconfiguration, process manipulation, or registry tampering;
- identify privileged service accounts with excessive token privileges;
- identify missing SACLs on sensitive objects;
- identify objects with protected DACLs that no longer inherit baseline hardening;
- identify existing processes holding high-privilege handles.

Remediation logic:

```text
Do not blindly remove ACEs.
First prove:
- asset criticality;
- business owner;
- effective access;
- dependency impact;
- rollback path;
- audit requirement;
- change approval need;
- compensating controls if immediate fix is unsafe.
```

## 22. Common Mistakes

- Treating Windows authorization as username-based instead of token-based.
- Assuming authentication success means authorization success.
- Assuming membership shown in a directory means the SID is present and enabled in the current token.
- Forgetting that tokens are snapshots and may require refresh.
- Confusing primary tokens and impersonation tokens.
- Forgetting that a thread token can override the process primary token for access checks.
- Assuming a service accesses resources as the interactive user.
- Assuming local access and remote access use the same effective identity.
- Treating privileges as ordinary permissions.
- Ignoring powerful privileges on service accounts.
- Granting broad access because a specific operation failed.
- Requesting Generic All when read or query access is enough.
- Treating owner as full data access.
- Ignoring WRITE_DAC and WRITE_OWNER risk.
- Confusing DACL and SACL.
- Assuming auditing exists because permissions exist.
- Confusing empty DACL and NULL DACL.
- Ignoring deny-only SIDs and filtered groups.
- Ignoring restricted-token behavior.
- Ignoring existing handles after ACL changes.
- Evaluating ACLs without object type and generic-right mapping.
- Ignoring ACE order.
- Ignoring inherited ACEs.
- Ignoring protected DACLs.
- Granting access directly to users instead of groups/roles.
- Leaving orphaned SIDs unreviewed.
- Ignoring service SIDs and granting broad service access instead.
- Treating access denied as a single cause rather than a decision path.
- Fixing authorization problems without change control and rollback.

## 23. Must-Memorize Facts

- Windows authorization is evaluated against access tokens, not account names.
- The core model is token + security descriptor + requested access.
- A security principal is represented by a SID.
- ACEs reference SIDs.
- A token contains user SID, group SIDs, privileges, attributes, and context.
- A process has a primary token.
- A thread may have an impersonation token.
- If a thread impersonates, the thread token is normally the effective token for access checks.
- Privileges are not the same thing as DACL permissions.
- DACL controls allow/deny access.
- SACL controls auditing and some security label behavior.
- Owner can usually change the DACL but does not automatically get all data access.
- Generic rights must be mapped to object-specific rights.
- Requested access matters; overbroad requests can fail.
- Granted access is stored in the handle.
- Existing handles may retain rights after ACL changes.
- Deny ACEs can block access even when allow ACEs exist.
- ACE order matters.
- Empty DACL usually denies ordinary access.
- NULL DACL is dangerously permissive.
- Tokens may become stale after group or privilege changes.
- Group SIDs can be enabled, disabled, deny-only, or restricted.
- Restricted tokens can require additional limiting checks.
- Object access auditing requires audit policy and SACL configuration.
- Access denied should be troubleshot by effective token, requested access, object security descriptor, and runtime context.

## 24. Interview / Exam Points

Expected interview answer for “How does Windows decide access?”:

```text
Windows evaluates the effective token of the process/thread against the security descriptor of the target object for the requested access mask. The DACL contains ACEs that allow or deny rights to SIDs. Privileges, owner behavior, object type, generic-right mapping, integrity behavior, and restricted-token behavior may also affect the result. If access is granted, the handle receives a granted-access mask.
```

Expected answer for “What is inside an access token?”:

```text
A token contains the user SID, group SIDs and attributes, privileges, token type, impersonation level if applicable, default DACL, owner/default owner behavior, restrictions if applicable, and logon/session context.
```

Expected answer for “DACL vs SACL?”:

```text
DACL controls who is allowed or denied access. SACL controls which access attempts are audited and can also hold certain security label information. DACL is enforcement; SACL is accountability/evidence configuration.
```

Expected answer for “Primary token vs impersonation token?”:

```text
A primary token is attached to a process and defines its default security context. An impersonation token is attached to a thread and lets that thread act as another security context. If a thread is impersonating, access checks use the thread token instead of the process primary token.
```

Expected answer for “Why can access remain after permissions were removed?”:

```text
Because access is often checked when a handle is opened. The granted-access mask is stored with the handle. Changing the ACL affects future opens but does not necessarily revoke already-open handles.
```

Expected answer for “Why can an admin be denied?”:

```text
The effective token may not have the needed SID enabled, may be filtered, may lack a required privilege, may be using a non-elevated context, may be denied by an ACE, may be requesting too much access, or may be blocked by integrity/elevation behavior.
```

Expected answer for “Why does a service access fail for a user?”:

```text
The service may not be using the user’s token. It may be using the service process primary token, a thread impersonation token with limited level, or a token that cannot delegate to a remote system. The effective token must be identified before evaluating the ACL.
```

Expected answer for “What is a SID?”:

```text
A SID is the stable security identifier Windows uses to represent a principal. Names are resolved to SIDs, and ACL entries use SIDs for authorization decisions.
```

Expected answer for “What is the Security Reference Monitor?”:

```text
It is the Windows kernel security component family that enforces security decisions for securable objects, including access validation against security descriptors and privilege checks.
```

Exam traps:

- Authentication is not authorization.
- DACL and SACL are different.
- Privilege and permission are different.
- A group shown in the directory is not guaranteed to be enabled in the current token.
- Owner does not automatically mean full data access.
- Existing handles can retain access.
- Deny ACEs and ACE order can change results.
- A NULL DACL is not the same as an empty DACL.
- A service may not act as the visible user.
- Access denied can be caused by requesting excessive access.

## 25. Expert-Level Insights

1. **Windows authorization is a runtime equation, not a static ACL screenshot.**
   
   An ACL screenshot is only object-side evidence. The decision also depends on token contents, token attributes, requested access, object type, handle state, privileges, and runtime identity.

2. **The evaluated identity can change within the same process.**
   
   A service process may run as one account while a worker thread impersonates another. Troubleshooting the process account alone can be wrong.

3. **Handle-granted access is often the missing concept.**
   
   Access checks commonly happen at open time. After the handle exists, the handle’s granted-access mask is the operational boundary.

4. **Privileges explain outcomes that ACL-only analysis cannot.**
   
   Backup, restore, debug, impersonate, take ownership, and security privileges can alter what is possible without ordinary allow ACEs granting the same operation.

5. **A token is an authorization snapshot.**
   
   Group membership changes, policy changes, and privilege assignments may not affect existing tokens until a new token is created or a service/session is restarted.

6. **Deny-only groups create confusing results.**
   
   A SID can exist in the token but fail to grant allow access while still participating in deny behavior.

7. **Owner is control power, not data permission.**
   
   The owner’s main power is permission recovery/control, especially DACL modification. Treat owner changes as security-sensitive.

8. **Requested access is a design decision.**
   
   Secure software asks for minimum rights. Poor software asks for broad rights and then causes unnecessary access failures or privilege requirements.

9. **DACL correctness without SACL accountability is incomplete.**
   
   Sensitive objects need both correct enforcement and evidence strategy. Otherwise, access may be controlled but not provable.

10. **Authorization troubleshooting must separate layers.**
    
    The denial may occur at authentication, token construction, DACL evaluation, privilege check, integrity behavior, share permission, protocol layer, service logic, or application policy.

11. **Access control is also an attack-path graph.**
    
    WRITE_DAC, WRITE_OWNER, service-control rights, process rights, registry modification rights, and writable executable paths can create privilege escalation or persistence opportunities.

12. **Least privilege applies to accounts, groups, privileges, DACLs, requested access masks, handles, services, and audit visibility.**
    
    A least-privilege program that only reviews group membership misses the real Windows enforcement surface.

## 26. Internal References to Future CKV Files

This file owns Windows authorization internals. The following CKV files own related expansion areas. CKV IDs and topic meanings follow the approved `MASTER_INDEX_FIXES.md` generation map.

- **CKV-001 — Security Engineering Role and Operating Model**  
  Owns the security engineering responsibilities, operating model, and cross-team ownership needed to govern Windows access-control design and remediation.

- **CKV-002 — Security Principles and Secure-by-Design Thinking**  
  Owns least privilege, complete mediation, defense-in-depth, secure defaults, fail-safe behavior, and trust-boundary reasoning used when interpreting authorization design.

- **CKV-003 — Risk Management and Security Governance**  
  Owns formal risk acceptance, policy authority, governance, and exception approval when access-control weaknesses cannot be immediately removed.

- **CKV-004 — Asset Management and Attack Surface Inventory**  
  Owns asset criticality, ownership, exposure mapping, and asset-to-control relationships that determine which Windows authorization paths matter most.

- **CKV-005 — Change Management and Security Exceptions**  
  Owns permission-change governance, rollback planning, emergency access changes, compensating controls, exception expiry, and drift review.

- **CKV-020 — Windows Fundamentals for Security**  
  Owns Windows OS fundamentals, local users/groups, logon overview, processes/services overview, Windows Firewall/Defender overview, event logs, and baseline Windows security context.

- **CKV-021 — NTFS, File Permissions, EFS, and Alternate Data Streams**  
  Owns practical NTFS permissions, file/folder access, ownership at file-system level, inheritance, auditing, EFS, ADS, sensitive paths, and NTFS hardening.

- **CKV-023 — UAC, Integrity Levels, and Elevation Semantics**  
  Owns UAC split tokens, elevation prompts, secure desktop, integrity levels, mandatory integrity behavior, filtered admin tokens, and elevation troubleshooting.

- **CKV-024 — Windows Registry, Services, Scheduled Tasks, and Persistence Surfaces**  
  Owns registry/security descriptor use, service permissions, scheduled task security, startup persistence surfaces, WMI persistence, and operational persistence paths.

- **CKV-025 — Windows Security Stack: Updates, Defender, Firewall, SmartScreen, BitLocker, TPM, VSS**  
  Owns platform security stack controls that protect or depend on Windows authorization, including Defender, BitLocker, VSS, SmartScreen, firewall profiles, exploit protection, and security configuration.

- **CKV-030 — Active Directory Fundamentals**  
  Owns domains, forests, OUs, domain controllers, trusts, global catalog, SYSVOL, centralized identity, and domain context affecting token material.

- **CKV-031 — Kerberos Authentication, PAC, Tickets, and Windows Logon**  
  Owns Kerberos authentication, tickets, PAC authorization material, domain logon, ticket-derived group context, and Windows logon relationships.

- **CKV-032 — NTLM, Netlogon, Relay Risk, and Authentication Hardening**  
  Owns NTLM authentication, Netlogon validation, relay risk, signing/sealing relationships, and authentication hardening that can affect remote token creation.

- **CKV-033 — LDAP, LDAPS, Signing, Channel Binding, and Directory Access**  
  Owns LDAP access, directory query authorization, LDAPS, signing/channel binding, directory object visibility, and directory access-control relationships.

- **CKV-034 — Group Policy Internals and Security**  
  Owns Group Policy delivery, security templates, user-right assignment, privilege assignment, local policy enforcement, and centralized Windows security settings.

- **CKV-035 — AD Delegation: Unconstrained, Constrained, and RBCD**  
  Owns delegation behavior, double-hop identity flow, service-to-service impersonation/delegation risk, and AD delegation security.

- **CKV-036 — Active Directory Attack Paths and Defensive Monitoring**  
  Owns AD attack-path mapping, privileged group monitoring, ACL abuse at directory scale, identity-path detection, and defensive monitoring.

- **CKV-037 — AD CS and PKI Security**  
  Owns AD CS, certificate templates, enrollment authorization, PKI trust paths, certificate-based authentication risks, and PKI-related identity control.

- **CKV-043 — DevSecOps, Secure SDLC, SAST, DAST, SCA, and Security Gates**  
  Owns software delivery controls, build identity, pipeline authorization, artifact permissions, deployment gate authorization, and code-to-runtime access governance.

- **CKV-060 — Detection Engineering and Telemetry Design**  
  Owns telemetry selection, detection logic, Windows event semantics, access-control detection coverage, privilege-use monitoring, and coverage validation.

- **CKV-061 — Incident Response Lifecycle and Playbook Design**  
  Owns response workflows for unauthorized access, privilege misuse, permission tampering, containment, eradication, recovery coordination, and lessons learned.

- **CKV-063 — Digital Forensics and Evidence Handling**  
  Owns formal evidence handling, forensic preservation, timeline construction, chain of custody, artifact integrity, and courtroom-grade Windows evidence handling.

- **CKV-064 — SOAR, Automation, Validation, and Provability Outputs**  
  Owns automated access validation, approval-gated remediation, permission-drift proof, token/ACL evidence packaging, and provability outputs.

- **CKV-065 — Security Monitoring Tools and Lab Architecture**  
  Owns lab architecture and monitoring pipelines for Windows event collection, Sysmon-style telemetry, SIEM ingestion, and validation environments.

- **CKV-073 — Credential Attack Concepts and Defensive Controls**  
  Owns credential theft, credential replay, secret exposure, credential abuse patterns, and defensive controls that interact with authentication before authorization.

- **CKV-074 — Privilege Escalation, Persistence, and Lateral Movement Concepts**  
  Owns privilege escalation concepts, writable-object abuse, service/registry/task persistence, token abuse concepts, lateral movement paths, and defensive interpretation.

- **CKV-080 — Malware, APT Lifecycle, Botnets, and Advanced Threat Controls**  
  Owns malware behavior, persistence strategy, token/privilege abuse at threat-lifecycle level, payload execution, and advanced threat control mapping.

- **CKV-081 — Firewalls, WAFs, IDS/IPS, and Network Security Controls**  
  Owns network security controls that protect remote administration, SMB/RPC/WinRM/WMI paths, and network exposure of Windows authorization surfaces.

- **CKV-082 — Vulnerability Management, Scanning, Prioritization, and Remediation**  
  Owns scanning, prioritization, remediation, exposure validation, compensating controls, and remediation verification for Windows authorization weaknesses.

- **CKV-090 — Command-Line and Built-in Administration Tools for Security Work**  
  Owns practical command usage for tools such as `whoami`, `icacls`, `Get-Acl`, `Set-Acl`, `accesschk`, `Process Explorer`, `Process Monitor`, audit tools, and safe evidence-collection workflows.

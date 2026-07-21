# CKV-063 — Digital Forensics and Evidence Handling

## 1. Purpose

This file defines the **canonical model for digital forensics and evidence handling**. It explains how evidence is preserved, collected, acquired, analyzed, correlated, reported, retained, and handed off without destroying integrity, context, legal defensibility, or operational usefulness.

This file answers:

```text
What is digital forensics?
What counts as evidence?
How is evidence different from an artifact, indicator, or finding?
How do preservation, collection, acquisition, analysis, and reporting differ?
What does chain of custody prove?
Why do hashes, timestamps, volatility, and working copies matter?
How should forensic evidence support incident response, threat hunting, detection engineering, legal review, and recovery?
```

Canonical purpose:

```text
Digital forensics = authorized evidence handling
                  + preservation of original state
                  + repeatable acquisition
                  + integrity verification
                  + disciplined analysis
                  + defensible reporting
                  + controlled retention
```

Digital forensics is not only a law-enforcement activity. In enterprise security, forensic discipline is required whenever facts must survive scrutiny by executives, auditors, regulators, legal counsel, insurers, customers, or future responders.

This file does not provide a forensic tool cookbook. It defines the concepts, decision points, evidence discipline, domain evidence sources, and engineering expectations required for real DFIR work.

## 2. Core Definition

**Digital forensics** is the disciplined process of identifying, preserving, collecting, acquiring, examining, analyzing, and reporting digital evidence in a way that maintains integrity, context, repeatability, and defensibility.

Canonical definition:

```text
Digital forensics = facts from digital systems
                  + preserved context
                  + integrity controls
                  + documented handling
                  + reasoned conclusions
```

A forensic process must be able to show:

- What evidence was identified.
- Who authorized collection.
- Who collected it.
- When it was collected.
- Where it came from.
- How it was collected.
- How integrity was verified.
- Where it was stored.
- Who accessed it.
- What analysis was performed.
- Which conclusions are facts, inferences, or uncertain hypotheses.

Forensics becomes weak when it only produces screenshots, unsupported notes, undocumented exports, copied files with no hashes, or conclusions that cannot be traced back to source evidence.

Canonical equation:

```text
Evidence value = relevance + integrity + context + provenance + repeatability
```

## 3. Why Digital Forensics and Evidence Handling Matter

Digital forensics matters because security decisions often depend on evidence quality.

Forensics supports:

- Incident scoping.
- Root-cause analysis.
- Malware and persistence investigation.
- Insider-threat investigations.
- Legal and regulatory response.
- Breach notification decisions.
- Recovery validation.
- Insurance and third-party review.
- Audit and compliance proof.
- Detection improvement.
- Threat-hunting pivots.
- Control improvement after incidents.

Weak evidence handling causes:

- Lost volatile data.
- Altered timestamps.
- Broken chain of custody.
- Unverifiable file copies.
- Incomplete timelines.
- Misattribution.
- Conflicting incident narratives.
- Failed legal or audit review.
- Repeated incidents because root cause was never proven.

Security reality:

```text
A response action may stop the bleeding.
Forensics explains what bled, why, how far, and what proof remains.
```

Strong forensic discipline protects both sides of a security decision:

- It supports action when evidence is strong.
- It prevents overreaction when evidence is weak or ambiguous.

## 4. Digital Forensics Mental Model

Digital forensics is **evidence-centered reconstruction**.

Mental model:

```text
Question
  ↓
Evidence sources
  ↓
Preservation plan
  ↓
Collection/acquisition
  ↓
Integrity verification
  ↓
Examination
  ↓
Timeline and correlation
  ↓
Hypothesis testing
  ↓
Conclusion with confidence
  ↓
Report and retention
```

Core forensic questions:

```text
What happened?
When did it happen?
Where did it happen?
Which systems, accounts, data, and services were involved?
How did it happen?
What evidence supports that conclusion?
What evidence contradicts it?
What remains unknown?
```

Forensics is not guessing from one log line. It is the disciplined correlation of independent evidence sources.

Strong forensic reasoning separates:

- Observed facts.
- Derived facts.
- Analyst interpretation.
- Hypotheses.
- Confidence level.
- Unknowns.
- Limitations.

Canonical forensic rule:

```text
Do not let the urgency of response destroy the evidence needed to understand the incident.
```

## 5. Forensics vs Incident Response vs Threat Hunting vs Detection Engineering

| Discipline | Primary question | Main trigger | Main output |
|---|---|---|---|
| Detection engineering | How do we reliably detect a behavior or control failure? | Use-case design, control need, observed attack behavior | Detection content, telemetry requirements, validation tests |
| Threat hunting | What might exist that alerts have not proven? | Hypothesis, weak signal, intelligence, anomaly | Hunt findings, pivots, detections, incident handoff |
| Incident response | What happened and what must we do safely now? | Declared incident, escalated case, material risk | Containment, eradication, recovery, decision log, closure |
| Digital forensics | What evidence proves what happened and how do we preserve it? | Investigation, legal need, incident need, evidence need | Preserved evidence, timeline, analysis notes, forensic report |
| BCDR | How do we restore critical business operations? | Disruption, disaster, recovery need | Restored services, continuity decisions, recovery validation |

Canonical distinction:

```text
Detection says: this behavior matched logic.
Hunting says: this pattern deserves investigation.
Incident response says: this condition requires action.
Forensics says: this evidence supports these facts.
```

Digital forensics overlaps with incident response, but it is not the same thing:

- IR may isolate, disable, rotate, block, restore, or rebuild.
- Forensics preserves evidence before or during those actions when preservation is required.
- IR may accept operational uncertainty to reduce harm.
- Forensics documents uncertainty and tries to reduce it through evidence.

## 6. Evidence vs Artifact vs Indicator vs Finding

| Term | Meaning | Example | Common mistake |
|---|---|---|---|
| Evidence | Data preserved and documented for investigative use | Hashed disk image, exported audit logs, preserved cloud activity logs | Treating a screenshot as sufficient proof without source context |
| Artifact | A data item created by system, user, application, or service behavior | Registry key, shell history, browser history, file metadata, event log | Assuming every artifact is malicious |
| Indicator | A value or pattern associated with suspicious or malicious activity | IP, domain, hash, process name, mutex, URL path | Treating an indicator as proof of compromise by itself |
| Finding | A reasoned conclusion supported by evidence | “Account X authenticated to host Y at time Z and created process P” | Reporting findings without evidence links |
| Observable | A raw value observed in telemetry | username, hostname, source IP, file path | Treating raw observables as confirmed facts without normalization |
| Hypothesis | A testable investigative explanation | “This host was used for lateral movement” | Treating the hypothesis as conclusion before evidence testing |

Canonical relationship:

```text
artifact + context + integrity + analysis = evidence-supported finding
```

Not all artifacts become evidence. Not all indicators prove incidents. Not all findings are equally certain.

A defensible finding should include:

- Evidence references.
- Timestamp basis.
- Affected entity.
- Observed action.
- Confidence level.
- Known limitations.
- Alternative explanations considered.

## 7. Forensic Readiness

**Forensic readiness** is the organization’s ability to collect, preserve, analyze, and produce usable evidence before an incident occurs.

Forensic readiness requires:

- Logging enabled before the incident.
- Sufficient retention periods.
- Time synchronization.
- Asset ownership metadata.
- Centralized log storage.
- Tamper-resistant evidence storage.
- Documented collection procedures.
- Legal and privacy approval paths.
- Pre-approved emergency evidence collection authority.
- Trained responders.
- Known evidence sources by platform.
- Tested acquisition workflows.
- Evidence packaging standards.
- Secure case-management practices.

Forensic readiness is not only a DFIR team problem. It depends on:

- Asset management.
- Identity governance.
- Endpoint logging.
- Network logging.
- Cloud audit logs.
- SaaS audit retention.
- Email trace retention.
- Backup and snapshot architecture.
- Change management.
- Legal and privacy governance.

Canonical readiness question:

```text
If a critical incident started 30 days ago, can we still prove what happened today?
```

If the answer is no, the problem is usually not the forensic analyst. The problem is missing telemetry, weak retention, poor ownership metadata, and untested evidence workflows.

## 8. Authorization, Legal, Privacy, and Ethics at High Level

Forensics must be authorized.

Before collection, determine:

- Who has authority to request evidence collection.
- Which systems are in scope.
- Which users, data, tenants, regions, or business units are in scope.
- Whether personal data may be collected.
- Whether privileged communications may be present.
- Whether third-party systems are involved.
- Whether law enforcement, regulators, insurers, or external counsel must be engaged.
- Whether employee-monitoring rules apply.
- Whether cross-border data transfer restrictions apply.
- Whether evidence can be exported from cloud or SaaS platforms.

High-level principles:

- Collect only what is authorized and relevant.
- Preserve privacy and confidentiality.
- Use least-access for evidence handling.
- Avoid unnecessary exposure of sensitive data.
- Do not exceed scope because collection is technically possible.
- Maintain confidentiality of investigation details.
- Separate legal conclusions from technical findings.

Canonical ethics rule:

```text
Forensic capability is powerful access. Use it only under valid authority, with documented scope, and with controlled disclosure.
```

This file does not provide legal advice. Legal, HR, privacy, compliance, and executive stakeholders define authority and disclosure obligations.

## 9. Evidence Integrity and Chain of Custody

**Evidence integrity** means the evidence can be trusted as unchanged or its changes are documented and explainable.

**Chain of custody** is the documented history of evidence control.

Chain of custody records should answer:

```text
Who handled the evidence?
What exactly was handled?
When was it handled?
Where was it stored?
Why was it transferred?
How was it protected?
What integrity checks were performed?
```

A chain-of-custody record should include:

- Evidence identifier.
- Description.
- Source system or service.
- Collection method.
- Collector name and role.
- Date and time collected.
- Time zone.
- Hash values where applicable.
- Storage location.
- Access log.
- Transfer history.
- Sealing/tamper controls where applicable.
- Case identifier.
- Retention and disposal status.

Chain of custody does not prove the analyst’s conclusion. It proves evidence handling history.

Canonical distinction:

```text
Hash proves content identity.
Chain of custody proves handling accountability.
Analysis proves meaning.
```

Common weak chain-of-custody patterns:

- Evidence stored on an analyst desktop with no access logging.
- Files copied through unmanaged chat or email.
- Original media altered during examination.
- No record of who exported logs.
- No time zone recorded.
- Multiple copies with unclear authoritative original.
- Hashes generated only after analysis, not at collection.

## 10. Hashing, Verification, and Evidence Identity

A cryptographic hash is used to identify data and detect changes.

Hashing is commonly used for:

- Disk images.
- Memory images.
- Log bundles.
- Export archives.
- Malware samples.
- Configuration snapshots.
- Evidence packages.
- Working-copy verification.

Hashing expectations:

- Hash the evidence as close to acquisition time as practical.
- Record algorithm and value.
- Use modern accepted algorithms for evidence identity.
- Re-hash after transfer.
- Store hash values separately from the evidence package when appropriate.
- Hash original evidence and major derived exports.
- Never rely on filename alone as evidence identity.

Canonical verification pattern:

```text
collect/acquire → hash → record → transfer → verify hash → analyze working copy
```

Hash limitations:

- A hash does not prove lawful collection.
- A hash does not prove correct interpretation.
- A hash does not prove the source was trustworthy.
- A hash may change for live exports that include generation metadata.
- A hash of a log export proves the export, not necessarily the original system state.

Evidence identity requires more than a hash:

```text
Evidence identity = source + collection method + timestamp + collector + hash + case context
```

## 11. Order of Volatility

**Order of volatility** is the principle that evidence most likely to disappear should be collected first when safe, authorized, and operationally justified.

Typical volatility order:

| Volatility level | Evidence examples | Risk |
|---|---|---|
| Very high | CPU state, registers, live memory, active network connections, running processes | Lost on shutdown or process exit |
| High | Logged-in sessions, open files, ARP/cache tables, DNS cache, process command lines | Changes quickly during normal operation |
| Medium | Local logs, temporary files, scheduled jobs, service states, registry hives, shell history | May rotate, be overwritten, or be modified by response actions |
| Low | Disk images, backups, snapshots, archived logs, cloud audit archives | More persistent but still vulnerable to deletion or retention expiry |

Canonical decision:

```text
Collect volatile evidence before disruptive containment when volatile facts are required and collection is safe.
```

The order of volatility is not absolute. Safety, legal authority, business impact, and containment urgency can override ideal collection order.

Examples:

- If ransomware is actively spreading, containment may take priority over perfect collection.
- If a cloud audit log has short retention, export it before it expires.
- If a system is unstable, memory capture may fail or worsen impact.
- If legal hold applies, preservation may include broad storage and access restrictions before analysis.

## 12. Preservation vs Collection vs Acquisition vs Analysis vs Reporting

These terms must not be mixed.

| Phase | Meaning | Output |
|---|---|---|
| Preservation | Protect evidence from loss, alteration, deletion, or unauthorized access | Legal hold, retention lock, snapshot hold, access freeze |
| Collection | Gather relevant evidence items | Exported logs, copied artifacts, collected files, case package |
| Acquisition | Create a forensic-quality copy or capture | Disk image, memory image, snapshot export, verified evidence archive |
| Examination | Extract and prepare data for analysis | Parsed artifacts, normalized logs, recovered files, searchable indexes |
| Analysis | Interpret evidence and test hypotheses | Timeline, correlations, findings, confidence statements |
| Reporting | Communicate facts, methods, limitations, and conclusions | Forensic report, executive summary, technical appendix |
| Retention | Store evidence according to policy and legal requirements | Controlled archive, retention record, disposal record |

Canonical flow:

```text
Preserve before collecting when loss risk exists.
Collect before analyzing when analysis may alter evidence.
Analyze working copies, not originals.
Report facts separately from interpretation.
Retain according to case, legal, and policy requirements.
```

## 13. Triage Collection vs Full Forensic Acquisition

**Triage collection** captures a limited, high-value evidence set quickly.

**Full forensic acquisition** captures broader or more complete evidence for deeper analysis and stronger defensibility.

| Dimension | Triage collection | Full forensic acquisition |
|---|---|---|
| Goal | Fast scoping and decision support | Comprehensive evidence preservation and analysis |
| Speed | Faster | Slower |
| Scope | Targeted artifacts and logs | Disk image, memory image, broader snapshots or exports |
| Operational impact | Lower if designed well | Higher, depending on system and method |
| Defensibility | Useful but narrower | Stronger when done correctly |
| Use case | Initial incident scoping, rapid containment decisions | Legal, major incident, root cause, deep timeline, disputed facts |

Triage collection may include:

- Running processes.
- Network connections.
- Logged-on users.
- Recent authentication events.
- Key persistence locations.
- EDR snapshot.
- Recent file modifications.
- Relevant logs.
- Cloud control-plane events.
- SaaS activity exports.

Full acquisition may include:

- Disk image.
- Memory image.
- Full log export.
- Cloud snapshot.
- Mailbox export.
- Object storage inventory/export.
- Database export under legal and business approval.

Decision logic:

```text
Use triage when speed and scoping dominate.
Use full acquisition when completeness, legal defensibility, or deep root-cause analysis dominates.
```

## 14. Live Response at High Level

**Live response** is evidence collection from a running system.

Live response can capture evidence that is lost after shutdown:

- Processes.
- Parent/child process relationships.
- Command lines.
- Network connections.
- Loaded modules.
- Logged-on users.
- Open handles.
- Memory-resident artifacts.
- Runtime service state.
- Temporary files.
- Active malware behavior.

Live response risks:

- It changes the system.
- It may alter timestamps.
- It may trigger attacker behavior.
- It may destroy fragile evidence if done poorly.
- It may run untrusted tools on a compromised host.
- It may conflict with containment urgency.

Canonical live-response discipline:

```text
Use authorized, tested, minimal, documented collection actions.
Record every command/tool/action used.
Prefer known-good tooling and external storage.
Preserve output with hashes and timestamps.
```

Live response is not random browsing. Every action should support a question.

## 15. Volatile Data at High Level

Volatile data is data that may disappear quickly due to reboot, shutdown, process exit, log rotation, session expiration, or cloud retention behavior.

Common volatile evidence:

- Memory contents.
- Running processes.
- Process command lines.
- Network connections.
- DNS cache.
- ARP/neighbor cache.
- Routing table.
- Logged-on sessions.
- Open files.
- Mounted shares.
- Temporary directories.
- Container runtime state.
- Cloud instance metadata tokens.
- Short-retention SaaS logs.

Volatile evidence is useful for:

- Identifying active malware.
- Finding fileless execution.
- Proving live connections.
- Capturing attacker sessions.
- Finding staged data.
- Mapping lateral movement.
- Preserving active credential artifacts where lawful and authorized.

Volatile evidence must be handled carefully because collection actions may change the state being observed.

Canonical question:

```text
What evidence will be gone if the system is powered off, rebooted, isolated, or cleaned?
```

## 16. Memory Evidence at High Level

Memory evidence can contain runtime facts that are not fully present on disk or in logs.

Memory may help identify:

- Running processes.
- Injected code.
- Loaded modules.
- Network connections.
- Command history fragments.
- Decrypted content.
- Fileless malware.
- Runtime configuration.
- Handles and objects.
- Recently used credentials or secrets where collection is authorized and legally reviewed.

Memory collection considerations:

- Memory is volatile.
- Acquisition can be resource-intensive.
- Acquisition tooling affects the system.
- Sensitive data may be captured.
- Storage and access controls must be strong.
- Scope and authorization must be explicit.
- Hash and preserve the memory image.
- Analyze a working copy.

Memory evidence does not automatically prove compromise. It must be correlated with logs, disk artifacts, identity telemetry, and network evidence.

Canonical memory principle:

```text
Memory can prove runtime reality, but it also contains highly sensitive data and must be treated as high-risk evidence.
```

## 17. Disk and File-System Evidence at High Level

Disk and file-system evidence supports reconstruction of persistent state.

Common disk/file evidence:

- File content.
- File metadata.
- Creation/modification/access timestamps.
- Deleted files.
- Unallocated space.
- Prefetch-like execution artifacts where applicable.
- Application caches.
- Browser artifacts.
- User profile artifacts.
- Temporary files.
- Download locations.
- Persistence files.
- Logs stored locally.
- Configuration files.
- Database files.
- Scripts and binaries.

File-system evidence can answer:

- What files were created or modified?
- Which account owned or accessed files?
- What executable or script existed?
- Where was data staged?
- Which persistence locations were changed?
- What files align with network or process evidence?

Important limitations:

- Timestamps can be altered or ambiguous.
- Access times may be disabled or unreliable.
- Copying files can change metadata.
- Backups and sync tools may modify timestamps.
- Encryption may restrict visibility.
- Cloud sync may create local and remote evidence copies.

Canonical rule:

```text
Do not infer a full timeline from file timestamps alone.
Correlate file evidence with logs, process evidence, identity events, and network activity.
```

## 18. Forensic Images, Snapshots, Exports, and Working Copies

Evidence copies are not all equal.

| Evidence copy type | Meaning | Strength | Limitation |
|---|---|---|---|
| Forensic image | Bit-level or forensic-quality copy of storage or memory | Strong for deep analysis | Time, storage, tooling, and operational impact |
| Snapshot | Point-in-time copy of disk, VM, volume, database, or cloud resource | Useful for cloud/virtual environments | May not include memory or external dependencies |
| Export | Platform-generated output such as logs, mailbox export, SaaS audit export | Practical and common | Proves exported data, not always raw backend state |
| Working copy | Analyst copy used for examination | Protects original evidence | Must be traceable to original |
| Derived artifact | Parsed or transformed evidence output | Useful for analysis | Must preserve source reference and transformation logic |

Canonical evidence handling:

```text
Original evidence is preserved.
Analysis happens on working copies.
Derived outputs retain source references.
Reports cite evidence identifiers, not analyst memory.
```

Cloud and SaaS investigations often depend on exports and snapshots rather than traditional disk images. That makes logging configuration, retention, and export integrity critical.

## 19. Windows Forensic Artifacts at High Level

Windows evidence can include:

- Security, System, Application, PowerShell, Sysmon, Defender, and service-specific logs.
- Registry hives and keys.
- User profiles.
- NTFS metadata.
- Alternate Data Streams.
- Prefetch-like execution artifacts where applicable.
- Amcache/Shimcache-like execution traces where applicable.
- Scheduled tasks.
- Services.
- WMI persistence artifacts.
- Startup folders and autorun locations.
- Event logs for logon, process creation, service creation, privilege use, object access, and policy changes.
- Defender and firewall events.
- RDP, SMB, WinRM, WMI, and remote administration traces.
- VSS and backups where available.
- Memory images where justified.

Windows forensic analysis must account for:

- Local vs domain accounts.
- Event log retention and overwrite behavior.
- Time zone and clock sync.
- GPO-applied configuration.
- UAC and token context.
- Service accounts and machine accounts.
- Remote administration channels.
- Endpoint security telemetry.

This section does not own Windows internals. It identifies common evidence categories and their forensic role.

## 20. Linux Forensic Artifacts at High Level

Linux evidence can include:

- systemd journal.
- syslog/auth logs.
- auditd records.
- SSH logs.
- sudo logs.
- shell history where reliable.
- cron and systemd timers.
- service unit files.
- process lists.
- network connections.
- package-manager logs.
- user account files and group membership files.
- filesystem metadata.
- temporary directories.
- web server logs.
- application logs.
- container runtime logs and metadata.
- persistence locations.
- memory images where justified.

Linux forensic analysis must account for:

- Distribution differences.
- Logging configuration differences.
- Journal persistence settings.
- Log rotation.
- Time synchronization.
- sudo and privilege escalation traces.
- SSH key usage.
- Service accounts.
- Containers and ephemeral workloads.
- Configuration-management changes.

This section does not own Linux hardening or kernel internals. It defines evidence classes used during investigations.

## 21. Network, DNS, Proxy, Firewall, and Packet Evidence at High Level

Network evidence helps reconstruct communication paths.

Common network evidence:

- Firewall logs.
- Proxy logs.
- DNS query logs.
- DHCP logs.
- VPN logs.
- NetFlow/IPFIX.
- IDS/IPS alerts.
- NDR telemetry.
- Packet captures.
- Load balancer logs.
- WAF logs.
- Network device configuration snapshots.
- NAT translation logs.
- Remote access logs.

Network evidence can answer:

- Which systems communicated?
- When did communication occur?
- What domain or IP was contacted?
- Was traffic allowed, denied, proxied, inspected, or bypassed?
- Which user or device was associated with a connection?
- Which NAT or proxy translation affected attribution?
- Did data volume suggest staging or exfiltration?

Limitations:

- Encryption limits content visibility.
- NAT and proxies complicate attribution.
- DNS logs show resolution, not necessarily connection.
- Flow logs show metadata, not full payload.
- Packet captures are sensitive and storage-heavy.
- Sensor placement affects what is visible.

Canonical network-evidence principle:

```text
Network evidence proves paths and timing best when combined with endpoint, identity, DNS, proxy, and firewall context.
```

## 22. Cloud and SaaS Evidence at High Level

Cloud and SaaS evidence often comes from control-plane logs, identity logs, service logs, configuration history, snapshots, and provider exports.

Common cloud evidence:

- Organization/account/project activity logs.
- IAM policy changes.
- Role assumption events.
- Key creation and use events.
- API activity logs.
- Object storage access logs.
- Network flow logs.
- Load balancer logs.
- WAF logs.
- Cloud DNS logs.
- KMS/key usage logs.
- Database audit logs.
- Compute snapshots.
- Disk/volume snapshots.
- Container orchestration audit logs.
- Serverless invocation logs.
- Cloud security findings.
- Configuration and compliance history.

Common SaaS evidence:

- User login events.
- MFA events.
- Admin actions.
- Sharing and permission changes.
- File access and download events.
- Mailbox and message trace logs.
- OAuth application consent events.
- API token creation and usage.
- Data export events.
- Conditional-access results.
- Audit logs and retention exports.

Cloud/SaaS forensic constraints:

- You may not have disk access.
- Logs may require prior enablement.
- Retention may be short by default.
- Provider timestamps and time zones must be normalized.
- API exports may be paginated or delayed.
- Multi-tenant services require strict scope control.
- Legal and privacy constraints may affect export.

Canonical cloud-forensics rule:

```text
In cloud and SaaS, evidence readiness is mostly designed before the incident through logging, retention, snapshot, identity, and export architecture.
```

## 23. Web and API Evidence at High Level

Web and API investigations rely on application, infrastructure, identity, and network evidence.

Common web/API evidence:

- Web server access logs.
- Application logs.
- API gateway logs.
- WAF logs.
- Reverse proxy logs.
- Authentication logs.
- Session and token events where logged safely.
- Request IDs and correlation IDs.
- Error logs.
- Database audit logs.
- Object access logs.
- File upload records.
- Admin action logs.
- Deployment history.
- CI/CD release metadata.
- Source and artifact provenance.

Web/API evidence can answer:

- Which endpoint was accessed?
- Which account, token, API key, client, or session was used?
- What object or resource was targeted?
- Which request path, method, status code, and response size occurred?
- Was an authorization check bypassed or misapplied?
- Was data accessed, modified, deleted, or exported?
- Did the behavior align with deployment or change history?

Sensitive handling requirements:

- Do not expose secrets or tokens in reports.
- Redact personal data where not needed.
- Preserve raw logs securely.
- Keep request bodies only when necessary and authorized.
- Treat API keys, bearer tokens, cookies, and session identifiers as secrets.

Canonical web/API forensic principle:

```text
Web and API evidence is strongest when request IDs, identity events, object access, application logs, and deployment history can be correlated.
```

## 24. Email and Collaboration Evidence at High Level

Email and collaboration systems are common evidence sources for phishing, business email compromise, insider activity, data leakage, malware delivery, and account compromise.

Common evidence:

- Message headers.
- Message trace logs.
- Mailbox audit logs.
- Sign-in logs.
- Inbox rules and forwarding configuration.
- OAuth application consents.
- Attachment metadata.
- URL click telemetry.
- Quarantine records.
- Sender authentication results.
- Collaboration file access logs.
- Sharing links and permission changes.
- Chat and channel audit logs where authorized.
- Admin action logs.

Investigation questions:

- Was the message delivered, quarantined, forwarded, deleted, or clicked?
- Which account created rules or delegated access?
- Was MFA satisfied?
- Was access from an unusual device, region, or application?
- Were files downloaded, shared, synchronized, or externally exposed?
- Did attacker-controlled OAuth consent grant persistence?

Limitations:

- Message content may be legally sensitive.
- Collaboration logs may have limited retention.
- User-reported screenshots are not sufficient alone.
- Forwarding and sync behavior may hide original access path.
- Privacy review may be required before content inspection.

Canonical rule:

```text
Email evidence should preserve both message content/context and platform audit events that show delivery, access, action, and account state changes.
```

## 25. Log Preservation and Evidence Retention

Log evidence is fragile because logs rotate, expire, aggregate, normalize, drop fields, and may be modified by compromised systems.

Preservation actions may include:

- Exporting relevant time windows.
- Extending retention.
- Placing logs under legal hold.
- Preserving raw logs in addition to normalized records.
- Preserving parser and schema versions.
- Capturing search query logic used to extract evidence.
- Capturing source time zone and ingestion time.
- Recording gaps, delays, and dropped events.
- Protecting exports with hashes and access controls.

Retention should be based on:

- Legal requirements.
- Regulatory requirements.
- Incident severity.
- Data sensitivity.
- Business impact.
- Expected investigation duration.
- Threat dwell-time assumptions.
- Storage and privacy constraints.

Canonical log evidence principle:

```text
A SIEM view is not always the original evidence.
Preserve raw source logs when they are required to prove fields, context, and completeness.
```

Common failure:

```text
Analyst runs a search, copies results to a spreadsheet, and loses source log identity, query logic, time window, time zone, and field provenance.
```

## 26. Timestamp Interpretation and Time Normalization

Timestamps are central to forensic reconstruction.

Timestamp fields may represent:

- Event occurrence time.
- Log generation time.
- Agent collection time.
- Ingestion time.
- Index time.
- Export time.
- File creation time.
- File modification time.
- File access time.
- Cloud API event time.
- Database transaction time.

Timestamp risks:

- Time zone mismatch.
- Clock drift.
- Daylight-saving ambiguity.
- Source clock manipulation.
- Ingestion delay.
- Batch log delivery.
- Different timestamp precision.
- File-system timestamp semantics.
- Normalization errors.
- Correlation based on wrong timestamp field.

Time normalization expectations:

- Convert timelines to a declared reference time zone, often UTC.
- Preserve original timestamps where possible.
- Record source time zone.
- Record clock skew when known.
- Distinguish event time from ingestion time.
- Use precision honestly.
- Avoid false ordering when timestamps have low precision.

Canonical timestamp rule:

```text
A timeline is only as reliable as its timestamp semantics, clock quality, and source provenance.
```

## 27. Timeline Building and Event Correlation

A forensic timeline organizes evidence into a sequence of relevant events.

Timeline entries should include:

- Timestamp.
- Time zone.
- Source system.
- Evidence identifier.
- Event type.
- Entity involved.
- Action observed.
- Result or outcome.
- Confidence.
- Notes or limitations.

Strong timelines correlate:

- Endpoint events.
- Identity events.
- Network events.
- DNS/proxy/firewall events.
- Cloud/SaaS activity.
- Application logs.
- File-system metadata.
- Email/collaboration logs.
- Change/deployment records.

Timeline goals:

- Establish first known activity.
- Identify initial access window.
- Identify persistence.
- Identify privilege changes.
- Identify lateral movement.
- Identify data access or exfiltration.
- Identify containment and recovery actions.
- Identify root-cause candidates.
- Identify evidence gaps.

Canonical correlation logic:

```text
One event suggests.
Two independent sources support.
Multiple consistent sources strengthen.
Contradictory evidence must be explained.
```

A forensic timeline should not hide uncertainty. Unknowns and gaps are part of the output.

## 28. Evidence Storage, Access Control, Tamper Resistance, and Retention

Evidence storage must protect confidentiality, integrity, and availability.

Evidence storage should provide:

- Unique evidence identifiers.
- Case association.
- Access control.
- Access logging.
- Encryption at rest.
- Encryption in transit.
- Tamper resistance or immutability where appropriate.
- Separation between original evidence and working copies.
- Backup and recovery protection.
- Retention controls.
- Disposal records.
- Legal hold support.
- Redaction support for derived reports.

Access control principles:

- Least privilege.
- Need-to-know.
- Separation between investigator, approver, and unrelated administrators where possible.
- Restricted access to sensitive evidence such as memory, mailbox exports, HR data, customer data, and secrets.
- Logging of every access and transfer.

Tamper-resistance examples at conceptual level:

- Immutable storage.
- Write-once retention.
- Versioning.
- Access logs in separate security account/tenant.
- Cryptographic hashes.
- Evidence package manifests.
- Restricted administrative paths.

Canonical rule:

```text
Evidence storage is part of evidence integrity. A good collection can be ruined by weak storage.
```

## 29. Forensic Notes and Analysis Discipline

Forensic notes are part of the case record.

Good forensic notes include:

- Case identifier.
- Analyst identity.
- Date and time.
- Question being investigated.
- Evidence sources used.
- Actions performed.
- Tools or methods used at high level.
- Queries or filters used where applicable.
- Results observed.
- Screenshots only as supporting material, not primary evidence.
- Assumptions.
- Hypotheses.
- Refuted explanations.
- Confidence level.
- Open questions.
- Next actions.

Forensic notes must separate:

| Category | Example |
|---|---|
| Fact | “Log source X recorded successful authentication by account Y at time Z.” |
| Inference | “This suggests account Y was used from a new location.” |
| Hypothesis | “Account Y may be compromised.” |
| Opinion | “This activity appears abnormal for this user.” |
| Unknown | “No endpoint telemetry is available for host H during this window.” |

Bad notes create bad reports.

Common note failures:

- No time stamps on analysis actions.
- No evidence identifiers.
- Conclusions mixed with facts.
- Screenshots without source exports.
- Missing query logic.
- Missing time zone.
- No record of negative findings.
- No documentation of why evidence was not collected.

## 30. Confidence, Uncertainty, and Attribution Limits

Forensics must communicate confidence honestly.

Confidence depends on:

- Evidence quality.
- Source reliability.
- Timestamp reliability.
- Number of independent supporting sources.
- Completeness of telemetry.
- Known gaps.
- Alternative explanations.
- Analyst method quality.
- Ability to reproduce results.

Attribution limits:

- An IP address does not prove a person.
- A username does not prove the legitimate user acted.
- A device name does not prove physical possession.
- Malware family resemblance does not prove actor identity.
- Tool use does not prove a specific threat group.
- Geolocation is weak evidence by itself.
- Cloud API activity may represent automation, user activity, attacker activity, or provider activity depending on context.

Confidence language should be explicit:

| Confidence | Meaning |
|---|---|
| Confirmed | Strong evidence directly supports the finding |
| High confidence | Multiple consistent sources support the finding with minor gaps |
| Moderate confidence | Evidence supports the finding but important gaps remain |
| Low confidence | Evidence is suggestive but incomplete or ambiguous |
| Unknown | Evidence is insufficient to decide |

Canonical rule:

```text
Do not overstate identity, intent, impact, scope, or root cause beyond the evidence.
```

## 31. Forensic Reporting

A forensic report communicates evidence-backed findings to the intended audience.

A strong report includes:

- Executive summary.
- Scope and authorization.
- Method summary.
- Evidence inventory.
- Timeline summary.
- Key findings.
- Impact assessment support.
- Confidence levels.
- Limitations and gaps.
- Root-cause analysis if supported.
- Affected systems, accounts, data, and services.
- Actions already taken if relevant.
- Recommended follow-up.
- Appendices with technical detail.
- Evidence references.
- Retention and handling notes.

Report audiences may include:

- Incident commander.
- SOC leadership.
- System owners.
- Legal counsel.
- Privacy officer.
- Executives.
- Regulators.
- Auditors.
- Insurers.
- Law enforcement.

Reporting discipline:

- Match detail to audience.
- Protect sensitive technical details.
- Avoid unnecessary indicators that could expose victims or methods.
- Do not include raw secrets.
- Keep facts and interpretation separate.
- Document uncertainty.
- Cite evidence identifiers.
- Avoid speculation in final findings.

Canonical report structure:

```text
Question → Scope → Evidence → Timeline → Findings → Confidence → Limitations → Recommendations
```

## 32. Handoff to IR, Threat Hunting, Detection Engineering, SOAR, Legal/Privacy, and BCDR

Forensics is not isolated. It feeds other disciplines.

| Handoff target | Forensic output provided |
|---|---|
| Incident response | Confirmed scope, timeline, affected entities, root-cause candidates, containment evidence |
| Threat hunting | Pivots, weak signals, related entities, hypotheses, gap areas |
| Detection engineering | Behaviors to detect, telemetry gaps, field requirements, detection test evidence |
| SOAR / automation | Evidence package requirements, validation outputs, safe automation checkpoints |
| Legal / privacy | Evidence inventory, scope, personal-data exposure support, chain-of-custody records |
| BCDR / recovery | Recovery validation evidence, affected systems, restore point confidence, reinfection indicators |
| Vulnerability management | Root-cause vulnerability evidence, affected assets, exploitation timeline, remediation proof needs |
| Change management | Unauthorized change evidence, drift evidence, rollback validation needs |

Canonical handoff principle:

```text
Forensic output should be actionable without forcing the receiving team to rediscover the evidence trail.
```

A weak handoff says:

```text
Looks compromised. Check logs.
```

A strong handoff says:

```text
Evidence E-014, E-022, and E-031 support that account A authenticated to host H at 03:14 UTC, created process P, contacted destination D, and modified file F. Confidence: high. Gaps: no memory image available. Recommended IR action: verify containment and rotate affected credentials.
```

## 33. Common Evidence Sources by Domain

| Domain | Common evidence sources | Primary forensic value |
|---|---|---|
| Endpoint | Process telemetry, event logs, EDR data, memory, disk, file metadata, service/task state | Execution, persistence, user activity, malware presence |
| Windows identity | Logon events, Kerberos/NTLM events, group changes, privilege use, domain controller logs | Account use, authentication path, privilege changes |
| Active Directory | Directory changes, replication events, privileged group modifications, GPO changes, ACL changes | Identity-control-plane changes and attack paths |
| Linux | journald, syslog, auth logs, auditd, shell history, sudo logs, cron/systemd timers | Authentication, command execution, persistence, service behavior |
| Network | Flow logs, firewall logs, proxy logs, DNS logs, VPN logs, packet captures | Communication path, destination, volume, timing, access control outcome |
| Cloud | Control-plane audit logs, IAM events, object storage logs, flow logs, snapshots, KMS logs | API activity, identity use, configuration changes, data access |
| SaaS | Sign-in logs, admin audit logs, file access, sharing events, mailbox audit, app consent | Account compromise, data exposure, configuration changes |
| Web/API | Access logs, app logs, gateway logs, WAF logs, request IDs, database audit logs | Endpoint abuse, authorization failures, data access, application actions |
| Email | Headers, message trace, mailbox audit, forwarding rules, URL click logs, quarantine logs | Phishing, BEC, delivery, click, mailbox compromise |
| Backup/recovery | Backup logs, restore logs, snapshot metadata, retention policies, restore test results | Recovery confidence, ransomware impact, evidence reconstruction |
| Security tools | SIEM alerts, EDR detections, NDR events, case notes, detection rule versions | Prior detection context and analyst actions |
| Change systems | Tickets, approvals, deployment logs, CI/CD records, configuration history | Distinguishing authorized change from unauthorized activity |

Evidence sources should be mapped before incidents occur:

```text
critical system → required evidence sources → retention → owner → access path → export procedure
```

## 34. Common Evidence-Handling Failures

Common failures:

- Collecting evidence after cleanup.
- Rebooting before volatile evidence decisions.
- Running untrusted tools on compromised systems.
- Using screenshots as primary evidence.
- Failing to hash evidence.
- Failing to document chain of custody.
- Analyzing originals instead of working copies.
- Exporting logs without time zone, query, or source context.
- Losing raw logs after SIEM normalization.
- Overwriting logs due to short retention.
- Mixing unrelated cases in one evidence folder.
- Storing evidence in uncontrolled locations.
- Sharing evidence through email or chat without controls.
- Ignoring privacy scope.
- Overstating attribution.
- Ignoring contradictory evidence.
- Failing to record negative findings.
- Failing to preserve cloud/SaaS logs before retention expiry.
- Not validating restored systems after recovery.
- Not feeding forensic lessons back into detection, hardening, and playbooks.

Canonical failure pattern:

```text
The incident was handled operationally, but the organization cannot prove what happened.
```

## 35. Common Mistakes

- Treating forensic work as “just collect logs.”
- Confusing indicators with proof.
- Confusing SIEM events with original evidence.
- Ignoring time synchronization.
- Ignoring time zones.
- Collecting too broadly without authorization.
- Collecting too narrowly and losing key context.
- Waiting until legal asks before preserving evidence.
- Assuming cloud providers keep all needed logs by default.
- Assuming endpoint agents captured everything.
- Assuming deleted means unrecoverable.
- Assuming encrypted traffic has no forensic value.
- Assuming lack of evidence means lack of activity.
- Failing to preserve identity-control-plane changes.
- Failing to capture configuration and change history.
- Not documenting collection tools and methods.
- Not separating facts, inferences, and hypotheses.
- Overusing threat-actor names without evidence.
- Publishing technical details to too broad an audience.
- Letting evidence retention expire before closure.

## 36. Must-Memorize Facts

- Digital forensics is evidence-centered reconstruction.
- Evidence must preserve integrity, context, provenance, and chain of custody.
- Chain of custody documents who controlled evidence, when, where, why, and how.
- Hashes support evidence identity and change detection; they do not prove interpretation.
- Volatile evidence can disappear after reboot, isolation, process exit, or retention expiry.
- Triage collection is fast and scoped; full acquisition is broader and more defensible.
- Live response can capture volatile evidence but changes the system.
- Analyze working copies, not originals.
- A SIEM result is not always the original evidence.
- Time zone and timestamp semantics can make or break a timeline.
- One log line rarely proves the full story.
- Independent evidence sources strengthen confidence.
- Forensic conclusions must distinguish fact, inference, hypothesis, and unknown.
- Attribution requires caution; IPs, usernames, and toolmarks rarely prove actor identity alone.
- Cloud and SaaS forensics depend heavily on pre-enabled logs and retention.
- Forensic readiness is designed before the incident.
- Evidence storage must be access-controlled, logged, encrypted, and tamper-resistant where needed.
- Reports must cite evidence identifiers and limitations.
- Forensic findings should feed IR, hunting, detection engineering, SOAR, legal/privacy, BCDR, and hardening.

## 37. Interview / Exam Points

Expected interview answers:

- **Digital forensics vs incident response:** IR acts to manage the incident; forensics preserves and analyzes evidence to prove what happened.
- **Chain of custody:** documented evidence control history, including who handled it, when, where, why, and how integrity was maintained.
- **Order of volatility:** collect evidence likely to disappear first, unless safety or containment urgency overrides.
- **Triage vs full acquisition:** triage supports quick decisions; full acquisition supports deeper and more defensible analysis.
- **Hashing:** used to verify evidence identity and detect change, but does not prove the evidence was lawfully collected or correctly interpreted.
- **Working copy:** a copy used for analysis so the original evidence remains preserved.
- **Forensic readiness:** logging, retention, authorization, procedures, training, and storage prepared before incidents.
- **Timestamp risk:** time zones, clock drift, source time, ingestion time, and precision can affect timeline conclusions.
- **Evidence vs indicator:** evidence is preserved data used to support findings; an indicator is a suspicious value or pattern.
- **Cloud forensic limitation:** many artifacts are provider logs, snapshots, and exports rather than traditional disk images.

Strong answer pattern:

```text
I would first confirm authorization and scope, preserve volatile and expiring evidence, document chain of custody, hash exports/images where applicable, analyze working copies, build a normalized timeline, state confidence and limitations, then hand off findings to IR/legal/detection/hardening as appropriate.
```

Weak answer pattern:

```text
I would just run a tool, export logs, and see what happened.
```

## 38. Expert-Level Insights

1. **Forensics is a trust system, not a toolset.**  
   The most expensive tools cannot compensate for missing authorization, weak chain of custody, no hashes, poor notes, or expired logs.

2. **Evidence completeness is often an architecture decision.**  
   Logging retention, cloud audit settings, identity telemetry, endpoint visibility, and immutable storage determine forensic success before an incident begins.

3. **Forensic discipline improves response quality.**  
   Good evidence prevents both under-response and over-response.

4. **Timelines are arguments, not just sorted events.**  
   A good timeline explains why events are connected, which events are uncertain, and what gaps remain.

5. **Cloud forensics is control-plane heavy.**  
   API activity, IAM changes, object access, key usage, and configuration history often matter more than traditional host disk artifacts.

6. **SaaS forensics is retention-sensitive.**  
   Many SaaS platforms expose critical logs only for limited periods or only under higher licensing and configuration choices.

7. **Memory evidence is powerful and dangerous.**  
   It can reveal runtime truth, but it can also expose secrets and personal data; access must be tightly controlled.

8. **Absence of evidence is not evidence of absence.**  
   Missing logs, disabled telemetry, clock drift, and overwritten data must be reported as limitations.

9. **Attribution should be conservative.**  
   Forensics can often prove account use, host behavior, tool execution, and data access. Proving human identity or threat actor identity is much harder.

10. **Forensic outputs must be reusable.**  
    The best forensic work produces evidence packages, timelines, findings, and lessons that improve detection, hunting, hardening, response, governance, and recovery.

11. **Evidence handling is also insider-risk control.**  
    Evidence often contains credentials, personal data, sensitive business data, and attack paths. Evidence access must be monitored like privileged access.

12. **The strongest report explains uncertainty.**  
    A credible forensic report does not hide gaps. It states what is known, what is likely, what is possible, and what cannot be determined from available evidence.

## 39. Internal References to Future CKV Files

This file owns digital forensics and evidence handling. The following CKV files own adjacent expansion areas. CKV IDs and topic meanings follow the approved `MASTER_INDEX_FIXES.md` generation map.

- **CKV-003 — Risk Management and Security Governance**  
  Owns risk acceptance, governance accountability, compliance oversight, and management decision context for evidence retention and investigation scope.

- **CKV-004 — Asset Management and Attack Surface Inventory**  
  Owns asset inventory, ownership metadata, criticality, exposure mapping, and asset-to-telemetry relationships needed to scope forensic collection.

- **CKV-005 — Change Management and Security Exceptions**  
  Owns authorized change evidence, exception records, rollback evidence, configuration drift, and unauthorized change governance.

- **CKV-006 — Business Continuity, Disaster Recovery, and Resilience**  
  Owns restore priorities, backup strategy, ransomware recovery readiness, recovery validation, tabletop exercises, and continuity evidence.

- **CKV-018 — Network Protocol Capture, Structures, and Analysis**  
  Owns packet capture methodology, capture placement, protocol field interpretation, timestamps, directionality, and packet-level analysis.

- **CKV-020 — Windows Fundamentals for Security**  
  Owns Windows OS fundamentals, Event Viewer, event logs, audit policy, PowerShell relevance, Windows services overview, and baseline investigation logic.

- **CKV-021 — NTFS, File Permissions, EFS, and Alternate Data Streams**  
  Owns NTFS permissions, file metadata, ownership, auditing, EFS, ADS, writable paths, and Windows file-system investigation logic.

- **CKV-024 — Windows Registry, Services, Scheduled Tasks, and Persistence Surfaces**  
  Owns Windows registry, services, scheduled tasks, startup surfaces, WMI persistence overview, and Windows control-plane drift investigation.

- **CKV-025 — Windows Security Stack: Updates, Defender, Firewall, SmartScreen, BitLocker, TPM, VSS**  
  Owns Defender, Windows Firewall, BitLocker, TPM, Secure Boot, VSS, security baselines, and Windows security-control status investigation.

- **CKV-026 — Linux Fundamentals and Hardening for Security**  
  Owns Linux users, permissions, services, systemd, logs, auditd, SSH, cron/timers, SELinux/AppArmor overview, and Linux baseline investigation logic.

- **CKV-030 — Active Directory Fundamentals**  
  Owns AD structure, domain controllers, users, groups, computers, OUs, sites, replication, trusts, SYSVOL, DNS dependency, and AD administrative model.

- **CKV-031 — Kerberos Authentication, PAC, Tickets, and Windows Logon**  
  Owns Kerberos tickets, PAC, AS/TGS/AP flows, ticket lifetime, Windows logon relationship, and Kerberos troubleshooting/investigation logic.

- **CKV-032 — NTLM, Netlogon, Relay Risk, and Authentication Hardening**  
  Owns NTLM, Netlogon, pass-through authentication, relay exposure, NTLM auditing, and NTLM investigation logic.

- **CKV-033 — LDAP, LDAPS, Signing, Channel Binding, and Directory Access**  
  Owns LDAP/LDAPS directory access, binds, searches, signing, channel binding, enumeration risk, and LDAP telemetry/investigation logic.

- **CKV-034 — Group Policy Internals and Security**  
  Owns GPO architecture, GPC/GPT, SYSVOL, processing, filtering, delegation, GPO abuse surfaces, GPO change evidence, and drift validation.

- **CKV-036 — Active Directory Attack Paths and Defensive Monitoring**  
  Owns AD attack-path reasoning, Tier 0 exposure, privilege paths, AD telemetry sources, defensive monitoring logic, and exposure validation.

- **CKV-037 — AD CS and PKI Security**  
  Owns AD CS/PKI, certificate templates, enrollment, certificate-based authentication, revocation, CA administration, and AD CS monitoring/investigation logic.

- **CKV-040 — HTTP, Web Fundamentals, Sessions, and Cookies**  
  Owns HTTP request/response behavior, sessions, cookies, caching, redirects, origins, and web traffic reasoning needed to interpret web evidence.

- **CKV-044 — API Security Controls: Authentication, Authorization, Schema, Rate Limits**  
  Owns API authentication, authorization, schema validation, rate limits, gateways, webhook controls, SSRF-resistant outbound controls, and API logging expectations.

- **CKV-050 — Cloud Fundamentals: IaaS, PaaS, SaaS, Compute, Storage, IAM**  
  Owns cloud resource models, accounts/subscriptions/projects, compute, storage, IAM basics, activity logs, and cloud operations terminology.

- **CKV-051 — Cloud Security Architecture and Hard Controls**  
  Owns cloud guardrails, IAM hard controls, network hard controls, object storage hard controls, immutable backups, centralized logs, tamper resistance, and cloud validation evidence.

- **CKV-060 — Detection Engineering and Telemetry Design**  
  Owns telemetry design, detection content, normalized fields, signal quality, coverage mapping, detection testing, and evidence outputs for response handoff.

- **CKV-061 — Incident Response Lifecycle and Playbook Design**  
  Owns incident declaration, triage, scoping, containment, eradication, recovery, decision logs, playbook anatomy, and response verification.

- **CKV-062 — Threat Hunting Methodology**  
  Owns hunt hypotheses, weak-signal analysis, pivoting, entity relationships, hunt notes, hunt findings, and hunt-to-incident/detection/hardening handoff.

- **CKV-064 — SOAR, Automation, Validation, and Provability Outputs**  
  Owns SOAR workflows, response validation, approval-gated automation, evidence packages, provability outputs, and automated handoff artifacts.

- **CKV-065 — Security Monitoring Tools and Lab Architecture**  
  Owns SIEM/SOAR/EDR/NDR/logging lab architecture, tool placement, data pipelines, lab validation, and monitoring-platform implementation context.

- **CKV-080 — Malware, APT Lifecycle, Botnets, and Advanced Threat Controls**  
  Owns malware behavior, APT lifecycle, botnet behavior, command-and-control, persistence context, malicious uploads, web shells, and supply-chain compromise relationships beyond forensic evidence handling.

- **CKV-082 — Vulnerability Management, Scanning, Prioritization, and Remediation**  
  Owns vulnerability intake, scanning, prioritization, remediation, compensating controls, validation, and vulnerability-to-incident/root-cause relationships.

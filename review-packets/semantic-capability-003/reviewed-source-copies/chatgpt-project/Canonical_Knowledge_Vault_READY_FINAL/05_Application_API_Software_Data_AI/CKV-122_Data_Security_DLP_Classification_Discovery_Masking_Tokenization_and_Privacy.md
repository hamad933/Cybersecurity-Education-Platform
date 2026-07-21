# CKV-122 — Data Security, DLP, Classification, Discovery, Masking, Tokenization, and Privacy Controls

Status: Advanced Canonical Knowledge Vault expansion file.  
Scope: defensive data security, DLP, discovery, classification, masking, tokenization, retention, deletion, privacy, and data incident response.  
Mode: vendor-neutral, platform-aware, governance-aware, engineering-focused.  
Unsafe content posture: no real exfiltration workflows, no DLP bypass tactics, no sensitive data dumping, no stealth transfer procedures, no privacy evasion, no offensive data-theft playbooks.

## 1. Purpose

This CKV defines data security as an enterprise discipline that protects data throughout its lifecycle, not merely as a set of encryption settings or DLP rules.

Data is the object that most security controls ultimately protect. Identity controls protect who may access it. Network controls protect paths to it. Application controls protect how it is processed. Database controls protect structured storage. Endpoint and mobile controls protect local handling. Cloud and SaaS controls protect hosted locations. Privacy controls govern lawful and ethical processing. DLP controls detect and govern unsafe movement.

This file gives a canonical model for:

- data lifecycle governance;
- data ownership, stewardship, and accountability;
- data asset inventory and shadow-data discovery;
- classification, labeling, metadata, lineage, and flow mapping;
- DLP architecture across endpoint, email, web, cloud, SaaS, database, API, code, print, removable media, clipboard, and screenshot channels;
- DLP detection mechanics, response actions, staging, tuning, and alert quality;
- masking, tokenization, pseudonymization, anonymization, de-identification, aggregation, and re-identification risk;
- retention, deletion, legal hold, records management, and deletion proof;
- privacy impact, data minimization, purpose limitation, cross-border transfer risk, and privacy-by-design;
- data incident response, evidence minimization, regulatory notification handoff, and recovery.

The goal is to make data security usable for security architects, privacy engineers, cloud security teams, AppSec engineers, SOC analysts, auditors, GRC teams, database/security engineers, and incident responders without turning the topic into a product manual or legal-advice document.

## 2. Core Definition

Data security is the protection of data assets, data flows, data access, data processing, data sharing, data retention, data deletion, and data evidence across the full lifecycle.

It answers six operational questions:

| Question | Security meaning |
|---|---|
| What data exists? | Inventory, discovery, classification, labeling, ownership, and location. |
| Where does it move? | Lineage, flows, APIs, SaaS sync, ETL/ELT, exports, backups, email, endpoints, mobile, and third-party transfer. |
| Who may use it? | Data owner approval, least privilege, ABAC/RBAC, need-to-know, JIT access, access reviews, and service-account governance. |
| How is it protected? | Encryption, masking, tokenization, retention, DLP, access controls, data minimization, and secure deletion. |
| What detects misuse? | DLP events, storage access logs, database audit, SaaS logs, CASB/SSE logs, file-sharing logs, endpoint DLP, email DLP, and classification reports. |
| How is exposure handled? | Data incident triage, scope, affected records, containment, sharing revocation, key/token rotation, legal/privacy handoff, and recovery. |

Data security is not only confidentiality. Integrity, availability, lawful use, provenance, accuracy, retention, deletion, recovery, and accountability are also security properties.

## 3. Scope Ownership

This file owns the security-control model for enterprise data protection across platforms.

It covers:

- data lifecycle: create, collect, classify, store, process, share, transfer, archive, retain, delete, destroy, restore, and prove;
- data ownership roles: owner, steward, custodian, processor, application owner, system owner, privacy owner, legal owner, security owner, and business owner;
- data asset model: datasets, records, fields, files, documents, objects, tables, columns, logs, reports, exports, backups, archives, replicas, caches, emails, chats, tickets, code repositories, notebooks, AI/RAG datasets, and derived data;
- classification and labeling models;
- data discovery across structured, semi-structured, unstructured, SaaS, endpoint, cloud, email, file shares, databases, logs, backups, collaboration tools, and source repositories;
- lineage and data-flow mapping;
- DLP architecture, detection mechanics, response actions, tuning, and validation;
- masking, tokenization, pseudonymization, anonymization, de-identification, aggregation, and re-identification risk;
- retention, deletion, legal hold, records management, and defensible destruction;
- privacy controls and privacy-engineering boundaries;
- secrets-in-data governance;
- access governance for data;
- data-security telemetry, incident response, forensics, controls, policies, validation, and framework mapping.

This file treats data security as a cross-domain layer. It depends on IAM, cloud, SaaS, database, endpoint, mobile, backup, email, API, and detection controls, but it owns the data-specific decision model.

## 4. What This File Does Not Own

This file intentionally does not own:

- full database security internals;
- full backup platform architecture;
- full cloud provider architecture;
- full endpoint OS internals;
- full email internals;
- full API/web security;
- full SIEM, SOAR, or EDR engineering;
- full privacy-law legal advice;
- full records-management legal program;
- full data warehouse, data lake, or lakehouse engineering;
- full AI/LLM security beyond data-governance handoff;
- full DLP product administration manuals;
- full data science anonymization mathematics curriculum;
- offensive data exfiltration, DLP bypass tactics, sensitive data dumping, stealth transfer workflows, credential harvesting from datasets, privacy evasion, re-identification abuse, insider-theft playbooks, or offensive data-theft playbooks.

Adjacent CKVs remain the owners of their technical domains. This file references them only where data-security decisions depend on them.

## 5. Prerequisites and Related CKV Files

Data security depends on many earlier CKVs but must not duplicate them.

| CKV | Relationship |
|---|---|
| CKV-002 | Security principles: least privilege, secure defaults, fail secure, defense in depth, privacy by design, and separation of duties. |
| CKV-003 | Governance, risk acceptance, data owner accountability, exception governance, policy ownership, and control assurance. |
| CKV-004 | Asset inventory extended into data inventory, data-location inventory, data-owner inventory, and shadow-data discovery. |
| CKV-005 | Controlled change for labels, retention, DLP policy, masking, tokenization, deletion, sharing, and data-access rule changes. |
| CKV-006 | Resilience, restore assurance, retention, backup exposure, legal hold, deletion conflicts, and recovery proof. |
| CKV-040 / CKV-044 | Web/API exposure, uploads, downloads, exports, pagination, filtering, object authorization, API data minimization, and web DLP channels. |
| CKV-043 | Test data, synthetic data, masked production data, CI/CD artifacts, logs, secrets in pipelines, and secure release gates. |
| CKV-050 / CKV-051 / CKV-112 / CKV-113 / CKV-114 | Cloud object stores, SaaS, KMS, logs, public sharing, private access, data perimeter controls, storage access logs, and provider-native data discovery. |
| CKV-060 / CKV-102 | Detection logic, telemetry quality, alert lifecycle, CASB/SSE, DLP, storage, database, endpoint, and SaaS event mapping. |
| CKV-061 / CKV-063 | Incident response, evidence minimization, chain of custody, privacy evidence, data exposure scoping, and forensic preservation. |
| CKV-073 / CKV-075 / CKV-080 | Credential leakage in data, social oversharing, ransomware, extortion, destructive changes, and data-theft defensive treatment. |
| CKV-081 / CKV-101 | Data egress paths, SASE/SSE/CASB, DNS/proxy logs, remote access, and cloud app inspection handoff. |
| CKV-103 | Email DLP, attachment handling, external forwarding, BEC-related data leakage, and message evidence. |
| CKV-106 / CKV-107 | Encryption, tokenization dependencies, KMS/HSM, field-level crypto, key separation, hashing, and key custody. |
| CKV-110 / CKV-111 | Identity federation, app consent, SaaS access, conditional access, session revocation, and user/device context. |
| CKV-119 / CKV-120 | Endpoint/mobile DLP channels, managed apps, downloads, screenshots, clipboard, removable media, and local sync. |
| CKV-121 | Database sources, audit evidence, backups, replicas, exports, tokenized/masked fields, and test-data generation. |
| CKV-123 | Future handoff for backup-platform security and ransomware-resilient recovery beyond data-level requirements. |

Deduplication rule: this CKV owns the data-specific lifecycle, classification, DLP, privacy, and transformation-control model. It does not re-teach the platform-specific controls already owned by other CKVs.

## 6. Data Security Mental Model

Data security starts with the data object, not the tool.

A useful model is:

```text
Business process
  -> data collection
  -> data classification
  -> owner and purpose assignment
  -> storage and processing locations
  -> access policy and usage policy
  -> sharing and transfer policy
  -> monitoring and DLP policy
  -> retention and deletion policy
  -> recovery and proof policy
```

The security failure is rarely one isolated mistake. It is usually a broken chain:

```text
unknown data
  -> no owner
  -> no label
  -> broad access
  -> uncontrolled sharing
  -> weak logs
  -> unclear retention
  -> no deletion proof
  -> uncertain incident scope
```

The defensive goal is to maintain a provable chain:

```text
known data
  -> accountable owner
  -> classification and label
  -> least-privilege access
  -> controlled flow
  -> monitored movement
  -> retained only as needed
  -> deleted or archived defensibly
  -> recoverable and auditable
```

Data security should be designed as a system of controls:

| Control layer | Purpose |
|---|---|
| Inventory | Know what data exists and where it lives. |
| Classification | Assign sensitivity, business criticality, and regulatory meaning. |
| Ownership | Assign accountable decision makers. |
| Access governance | Decide who may use the data and why. |
| Transformation | Mask, tokenize, de-identify, encrypt, aggregate, or minimize data. |
| DLP | Detect and govern risky movement or sharing. |
| Privacy governance | Ensure lawful, fair, purpose-bound, and minimized processing. |
| Retention/deletion | Prevent indefinite accumulation and prove disposal. |
| Telemetry | Provide evidence of access, movement, policy action, and incident scope. |

## 7. Data Lifecycle: Create, Collect, Classify, Store, Process, Share, Transfer, Archive, Retain, Delete, Destroy, Restore, and Prove

The data lifecycle is the operating system of data governance.

| Stage | Security objective | Typical evidence |
|---|---|---|
| Create | Ensure new data is created for an approved business purpose. | Application design record, data schema, data owner, privacy review. |
| Collect | Minimize collection to what is necessary and lawful. | Collection notice, form/API schema, consent/legal-basis record where applicable. |
| Classify | Apply sensitivity and regulatory meaning early. | Label, tag, catalog entry, DLP classifier result, data owner approval. |
| Store | Place data in approved repositories with access and encryption controls. | Storage location, access policy, encryption status, retention setting. |
| Process | Use data only in authorized workflows. | Processing logs, job history, data-flow map, lineage metadata. |
| Share | Control internal and external collaboration. | Sharing logs, access review, external guest list, owner approval. |
| Transfer | Protect movement between systems, regions, third parties, and workloads. | Transfer record, contract/DPA handoff, encryption evidence, egress logs. |
| Archive | Move inactive data to controlled long-term storage. | Archive policy, access restriction, legal-hold status, integrity check. |
| Retain | Keep data only for approved timeframes. | Retention label, schedule, records classification, exception record. |
| Delete | Remove data when eligible and lawful. | Deletion request, workflow status, tombstone, purge record. |
| Destroy | Sanitize or cryptographically render data unrecoverable where required. | Destruction certificate, key-destruction evidence, media sanitization proof. |
| Restore | Recover data without violating access, retention, or privacy rules. | Restore test, backup inventory, recovery validation, reclassification check. |
| Prove | Demonstrate control effectiveness. | Audit evidence, DLP reports, access reviews, validation records. |

Lifecycle controls fail when data moves without metadata. Therefore, classification, ownership, retention, and lineage metadata must travel with the data whenever technically possible.

## 8. Data Ownership Model: Owner, Steward, Custodian, Processor, Application Owner, System Owner, Privacy Owner, Legal Owner, Security Owner, and Business Owner

Data security needs named accountability.

| Role | Decision authority | Main responsibilities |
|---|---|---|
| Data owner | Business accountability | Approves classification, access, retention, sharing, exceptions, and acceptable use. |
| Data steward | Operational data governance | Maintains definitions, metadata, quality rules, catalog entries, and lineage. |
| Data custodian | Technical operation | Operates storage, backups, platform controls, access mechanisms, and logs. |
| Data processor | Processing execution | Processes data on behalf of controller/business owner under defined terms. |
| Application owner | Application behavior | Defines app access paths, API exposure, exports, logs, and user workflows. |
| System owner | Platform boundary | Owns infrastructure, SaaS, database, endpoint, or cloud service security. |
| Privacy owner | Privacy obligations | Owns privacy risk, DPIA/PIA, data subject rights, minimization, and notice alignment. |
| Legal owner | Legal constraints | Owns hold, discovery, jurisdiction, contract, breach notice, and regulatory interpretation. |
| Security owner | Security controls | Owns DLP, encryption, monitoring, incident response, and technical control assurance. |
| Business owner | Business value | Decides business need, acceptable operational friction, and process continuity. |

Common anti-pattern: the database administrator or cloud administrator is treated as the data owner. They are usually custodians, not business owners. A custodian can implement access but cannot decide business necessity alone.

## 9. Data Asset Model: Datasets, Records, Fields, Files, Documents, Objects, Tables, Columns, Logs, Reports, Exports, Backups, Archives, Replicas, Caches, Emails, Chats, Tickets, Code, AI/RAG Datasets, and Derived Data

A data asset is any persisted, transmitted, cached, logged, transformed, indexed, summarized, embedded, exported, or backed-up representation of information.

| Asset type | Why it matters |
|---|---|
| Dataset | Main governance unit for ownership, purpose, quality, classification, and lineage. |
| Record | Unit of exposure and impact counting. |
| Field/column | Unit of fine-grained classification, masking, tokenization, encryption, and access policy. |
| File/document | Common unstructured data object with sharing, sync, versioning, and labeling risk. |
| Object/blob | Cloud object storage unit; metadata and tags often drive discovery and policy. |
| Table/collection/index | Query and access boundary; can expose sensitive fields through views or search. |
| Log/report/export | Often forgotten copies of production data. |
| Backup/archive/replica/cache | Secondary storage with high exposure and recovery value. |
| Email/chat/ticket | Human workflow channels where sensitive data is often copied. |
| Code/notebook | May contain embedded data samples, secrets, tokens, or query output. |
| AI/RAG dataset | Derived corpus that may blend source data, embeddings, prompts, metadata, and generated outputs. |
| Derived data | Aggregations or transformations that can still reveal sensitive facts. |

The asset model should include both canonical systems of record and uncontrolled derivatives. A dataset is not fully secured until its exports, copies, replicas, reports, logs, backups, and downstream consumers are understood.

## 10. Data Classification Model: Public, Internal, Confidential, Restricted, Regulated, Secret, Business-Critical, Personal, Sensitive Personal, PCI, PHI, PII, Financial, Legal, HR, Source Code, and Secrets

Classification translates business and regulatory meaning into handling rules.

A practical classification model separates three dimensions:

| Dimension | Examples | Security use |
|---|---|---|
| Sensitivity | Public, internal, confidential, restricted, secret. | Drives access, sharing, DLP, encryption, and approval. |
| Regulatory category | PII, sensitive personal data, PCI, PHI, financial, legal, HR. | Drives privacy, retention, breach notification, and compliance scope. |
| Business criticality | Business-critical, operational, archival, low impact. | Drives backup, recovery, availability, monitoring, and resilience. |

Example handling meaning:

| Class | Typical handling |
|---|---|
| Public | Approved for public release; integrity still matters. |
| Internal | Limited to organization; moderate sharing controls. |
| Confidential | Need-to-know; monitored external sharing; encryption required. |
| Restricted | Strict access, strong approval, DLP enforcement, enhanced audit. |
| Regulated | Mapped to legal/compliance obligations; retention and breach workflows defined. |
| Secret/source code/secrets | Strong access, no external sharing without formal approval, continuous scanning. |
| Business-critical | Recovery and integrity priority even if not highly sensitive. |

Classification must be simple enough for humans and precise enough for automation. Overly complex labels cause inconsistent use; overly broad labels cause control fatigue.

## 11. Data Discovery: Structured, Semi-Structured, Unstructured, Object Storage, SaaS, Endpoint, Email, File Shares, Databases, Data Lakes, Logs, Source Repositories, Collaboration Tools, Backups, and Shadow Data

Data discovery identifies where sensitive or business-critical data exists.

Discovery scope should include:

| Location | Discovery focus |
|---|---|
| Databases | Tables, columns, views, procedures, exports, replicas, backups, and query history. |
| Data lakes/object storage | Buckets, containers, prefixes, object tags, file formats, partitions, manifests, and access logs. |
| SaaS | Documents, external sharing, guest access, chat content, CRM/ERP records, exports, and app integrations. |
| Endpoints/mobile | Local downloads, sync folders, clipboard, screenshots, removable media, managed app stores. |
| Email | Attachments, forwarded messages, external recipients, shared mailboxes, auto-forwarding. |
| File shares | Legacy departmental data, stale permissions, orphaned ownership, shadow copies. |
| Logs | Accidentally logged secrets, tokens, identifiers, request/response bodies, error traces. |
| Source repositories | Secrets, test data, fixtures, notebooks, generated reports, API samples. |
| Backups/archives | Long-lived copies, outdated access, weak indexing, legal holds, obsolete retention. |
| Collaboration tools | Chat attachments, pasted credentials, screenshots, customer records, tickets. |

Discovery methods include metadata scanning, pattern detection, exact data match, fingerprinting, classifier models, file-type analysis, schema inspection, access graph analysis, and owner interviews.

Discovery output must become inventory, not just a one-time report.

## 12. Data Labeling and Metadata: Sensitivity Labels, Retention Labels, Object Tags, Classification Tags, Schema Metadata, Catalog Entries, Ownership Metadata, Lineage Metadata, and Policy Metadata

Labels and metadata are control inputs.

| Metadata type | Purpose |
|---|---|
| Sensitivity label | Defines confidentiality and handling restrictions. |
| Retention label | Defines retention, deletion, record, or legal-hold behavior. |
| Object tag | Enables cloud/object-store policy, lifecycle, access, and discovery. |
| Classification tag | Records detected or confirmed data type. |
| Schema metadata | Identifies sensitive fields and semantic meaning. |
| Catalog entry | Defines owner, business glossary, source, quality, and usage. |
| Ownership metadata | Makes access and exception decisions accountable. |
| Lineage metadata | Shows source, transformation, and downstream consumption. |
| Policy metadata | Links data to DLP, retention, privacy, access, and encryption rules. |

Good labels are:

- consistent;
- auditable;
- portable where possible;
- mapped to policy;
- visible to users when needed;
- enforced by systems where possible;
- reviewed when data changes context.

Labeling can be manual, automatic, recommended, inherited, or API-driven. Automatic labels reduce coverage gaps, but business-owner confirmation remains important for high-risk data.

## 13. Data Lineage and Flow Mapping: Source, Transformation, Enrichment, Replication, ETL/ELT, API Exposure, SaaS Sync, Analytics, Reporting, Downstream Consumers, and Derived Datasets

Lineage explains how data changes and where it flows.

A useful lineage record includes:

| Field | Meaning |
|---|---|
| Source system | Where data originated. |
| Collection purpose | Why the data was collected. |
| Transformation | How data was cleaned, joined, enriched, masked, tokenized, or aggregated. |
| Destination | Where data is stored or consumed. |
| Consumer | Application, user group, report, model, third party, or service account. |
| Classification before/after | Whether sensitivity changed after transformation. |
| Retention rule | How long the resulting dataset exists. |
| Legal/privacy basis | Purpose, notice, consent/legal basis, or contract basis where relevant. |
| Evidence | Job logs, data catalog entries, approvals, and access logs. |

Lineage matters because data can become more sensitive after joining. For example, two low-sensitivity datasets may become restricted when combined. Derived analytics can reveal information that is absent from any single source.

## 14. DLP Architecture: Endpoint, Email, Web, CASB/SSE/SASE, Cloud Storage, SaaS, Database, API, Source-Code/Secrets, Print, Removable Media, Clipboard, Screenshot, and Channel Controls

DLP is a policy enforcement and detection layer for sensitive data movement and use.

| DLP location | Controls |
|---|---|
| Endpoint DLP | Local files, removable media, print, clipboard, screenshots, uploads, sync folders, unmanaged apps. |
| Email DLP | Recipients, attachments, forwarding, sensitive content, encryption, warning, block, review. |
| Web DLP | Browser uploads, downloads, unsanctioned sites, SaaS sessions, personal cloud storage. |
| CASB/SSE/SASE DLP | Cloud app traffic, sanctioned/unsanctioned SaaS, inline session controls, API inspection. |
| Cloud storage DLP | Object stores, data lakes, public sharing, object tags, bucket/container policies. |
| SaaS DLP | Documents, chats, external sharing, guest access, third-party app access. |
| Database DLP | Sensitive table/field discovery, exports, query auditing, masking handoff. |
| API DLP | Response overexposure, bulk export, schema leaks, sensitive fields, tokenized responses. |
| Source-code/secrets scanning | Credentials, keys, tokens, sample data, notebooks, config files. |
| Print/removable media | Physical exfiltration risk and regulated-data handling. |
| Clipboard/screenshot controls | User workflow channels that bypass file-level protections. |

DLP should not be designed as a single choke point. Modern data movement crosses email, browser, SaaS, API, endpoint sync, mobile apps, collaboration platforms, cloud storage, and third-party integrations.

## 15. DLP Detection Mechanics: Exact Data Match, Fingerprinting, Regex/Patterns, Dictionaries, ML/Classifiers, Labels, OCR, Contextual Rules, User/Device/App/Location/Action Risk, and Confidence Scoring

DLP detection decides whether content and context match a policy.

| Method | Strength | Limitation |
|---|---|---|
| Regex/patterns | Good for structured identifiers. | False positives if not combined with checks/context. |
| Dictionaries | Good for known terms, project names, regulated keywords. | Misses variations and may overmatch. |
| Exact data match | Strong for known records. | Requires protected reference dataset and careful hashing/matching. |
| Fingerprinting | Useful for document/source matching. | Needs controlled source corpus and update process. |
| Labels | Strong when labels are accurate. | Weak when label coverage is poor or user-applied labels are inconsistent. |
| ML/classifiers | Useful for documents and free text. | Needs tuning, validation, explainability, and false-positive management. |
| OCR | Finds data in images/scans. | Adds cost, latency, and accuracy limitations. |
| Contextual rules | Reduces noise with user/device/app/location/action signals. | Depends on high-quality identity/device telemetry. |
| Confidence scoring | Prioritizes alerts. | Can hide low-confidence high-impact exposures. |

Detection quality improves when content signals and context signals are combined:

```text
content match + label + risky destination + unmanaged device + external sharing
  -> higher confidence and stronger response
```

DLP detection must be validated with synthetic data, never by deliberately moving real sensitive data outside approved boundaries.

## 16. DLP Response Actions: Audit, Warn, Justify, Coach, Block, Quarantine, Encrypt, Label, Revoke Sharing, Remove External Access, Ticket, Notify Owner, Legal/Privacy Escalation, and Incident Creation

DLP response should match risk and business context.

| Response | Use case |
|---|---|
| Audit | Low-risk monitoring, policy discovery, baseline building. |
| Warn | User education where accidental exposure is likely. |
| Justify | Business exceptions with accountable reason capture. |
| Coach | Real-time guidance that changes user behavior. |
| Block | High-risk transfer, prohibited destination, regulated data, or untrusted device. |
| Quarantine | Hold file/message/object for review. |
| Encrypt | Protect allowed transfer where content may leave boundary. |
| Label | Apply or upgrade classification automatically. |
| Revoke sharing | Remove external links or guest access after risky exposure. |
| Remove external access | Remove external users, public links, anonymous links, or third-party app access. |
| Ticket | Route to data owner, security, privacy, or platform team. |
| Notify owner | Let accountable owner approve or correct. |
| Legal/privacy escalation | Trigger privacy incident assessment or legal hold review. |
| Incident creation | Convert confirmed exposure into IR workflow. |

Response design should avoid two extremes: blocking everything without business context, or auditing everything without enforcement.

## 17. DLP Tuning: False Positives, False Negatives, Policy Staging, Simulation, Allowlists, Exceptions, Business Justifications, Sampling, Synthetic Tests, and Alert Fatigue

DLP tuning is a lifecycle.

| Tuning area | Control objective |
|---|---|
| Policy staging | Start in audit/simulation, measure impact, then enforce gradually. |
| False positives | Reduce noisy patterns with context, checksums, labels, and exclusions. |
| False negatives | Improve discovery coverage, classifier precision, and source fingerprinting. |
| Allowlists | Permit known safe workflows without suppressing unknown risky variants. |
| Exceptions | Time-bound, owner-approved, justified, reviewed, and logged. |
| Business justification | Capture reason before allowing risky but permitted action. |
| Sampling | Review representative alerts and missed cases. |
| Synthetic tests | Validate policies using harmless test data that resembles patterns. |
| Alert fatigue | Prioritize by sensitivity, destination, user risk, and repeat behavior. |

DLP tuning evidence should include:

- policy version;
- simulation results;
- enforcement decision;
- exception register;
- alert-quality review;
- owner signoff;
- residual risk statement.

## 18. Masking: Static, Dynamic, Deterministic, Randomized, Redaction, Truncation, Substitution, Shuffling, Date Shifting, Nulling, Format-Preserving Approaches, and Masking Quality

Masking changes data values so that sensitive information is not exposed in unauthorized contexts.

| Masking type | Meaning | Typical use |
|---|---|---|
| Static masking | Creates a masked copy of a dataset. | Lower environments, analytics, training, external testing. |
| Dynamic masking | Masks data at query/view time. | Production access minimization, support roles, limited views. |
| Deterministic masking | Same input maps to same output. | Joins and referential integrity across datasets. |
| Randomized masking | Output is random within constraints. | Stronger privacy for non-join use cases. |
| Redaction | Removes or hides values. | Documents, logs, reports, evidence packages. |
| Truncation | Shows partial value. | Last four digits, approximate identifiers. |
| Substitution | Replaces with realistic fake values. | Test data realism. |
| Shuffling | Reassigns values across records. | Limited utility; re-identification risk remains. |
| Date shifting | Offsets dates while preserving intervals. | Longitudinal analysis with reduced identifiability. |
| Nulling | Removes values completely. | Fields not needed for use case. |
| Format-preserving approaches | Preserve length/format. | Legacy systems with strict field formats. |

Masking quality is measured by:

- protection strength;
- referential integrity;
- statistical utility;
- resistance to re-identification;
- compatibility with application constraints;
- repeatability where needed;
- irreversibility unless explicitly designed otherwise.

Masking is not automatically anonymization. A masked dataset can still be personal data if re-identification is reasonably possible.

## 19. Tokenization: Vault-Based, Vaultless Concepts, Deterministic and Non-Deterministic Tokens, Token Domains, Re-Identification Control, Detokenization Authority, Token Vault Security, and PCI-Style Tokenization

Tokenization replaces sensitive values with tokens that have no independent meaning outside the tokenization system.

| Concept | Meaning |
|---|---|
| Vault-based tokenization | Mapping between original value and token is stored in a protected token vault. |
| Vaultless tokenization | Uses cryptographic or deterministic methods without a central vault; design must be carefully evaluated. |
| Deterministic token | Same input produces same token for matching and joins. |
| Non-deterministic token | Same input may produce different tokens, reducing correlation. |
| Token domain | Context boundary that prevents tokens from being reused across incompatible systems. |
| Detokenization authority | Strictly controlled role/system allowed to recover original value. |
| Token vault | High-value system requiring strong access, logging, encryption, backup, and monitoring. |
| PCI-style tokenization | Scope-reduction strategy when implemented with strong separation and controls. |

Tokenization decisions:

| Question | Why it matters |
|---|---|
| Who can detokenize? | Controls re-identification power. |
| Where is the vault? | Defines trust boundary and blast radius. |
| Are tokens deterministic? | Affects joins, analytics, and correlation risk. |
| Are tokens format-preserving? | Affects legacy compatibility and leakage risk. |
| Are token domains separated? | Prevents broad cross-system correlation. |
| How are vault backups protected? | Token vault backups are sensitive assets. |
| What logs detokenization? | Every detokenization should be attributable and reviewable. |

Tokenization fails when the token vault is treated as ordinary infrastructure or when detokenization is broadly available.

## 20. Pseudonymization, Anonymization, De-Identification, Aggregation, Generalization, k-Anonymity, l-Diversity, t-Closeness, Re-Identification Risk, and Privacy Engineering Boundaries

Privacy engineering uses transformations to reduce identifiability.

| Technique | Defensive meaning |
|---|---|
| Pseudonymization | Replaces direct identifiers but preserves linkability under controlled conditions. |
| Anonymization | Attempts to make individuals no longer identifiable by reasonable means. |
| De-identification | Removes or transforms identifiers according to a standard or risk model. |
| Aggregation | Combines records into groups or statistics. |
| Generalization | Reduces precision, such as age range instead of birth date. |
| k-anonymity | Each record is indistinguishable from at least k-1 others for chosen quasi-identifiers. |
| l-diversity | Adds diversity requirements for sensitive attributes within groups. |
| t-closeness | Requires group distributions to be close to overall distribution. |
| Differential privacy | Adds mathematically bounded noise to reduce individual inference risk; requires specialized design. |

Critical boundary: de-identified data can become identifiable again when joined with other datasets. Therefore privacy transformations require context-aware re-identification assessment.

Privacy engineering questions:

- What identifiers and quasi-identifiers remain?
- What external datasets could be joined?
- Who can access raw vs transformed data?
- Is re-identification contractually or technically restricted?
- Does the transformation preserve only the utility needed for the purpose?
- Are derived datasets also governed?

## 21. Encryption Relationship to Data Security: At Rest, In Transit, Field-Level, Client-Side, Application-Layer, Database, File, Object, Backup, and Key Custody

Encryption is one data protection mechanism, not the whole program.

| Encryption layer | Protects against | Does not solve by itself |
|---|---|---|
| In transit | Network interception and path exposure. | Overprivileged recipients or bad exports. |
| At rest | Storage media exposure and storage-layer compromise. | Authorized misuse or application-layer leaks. |
| Field-level | Specific sensitive fields. | Business logic overexposure. |
| Client-side | Provider/storage operator exposure depending on design. | Endpoint compromise or poor key custody. |
| Application-layer | App-controlled protection. | Secret leakage and key-management failures. |
| Database TDE | Database files and backups at storage level. | Query-time exposure to authorized users. |
| File/object encryption | Document/object storage confidentiality. | Bad sharing links or external access. |
| Backup encryption | Backup theft exposure. | Unauthorized restore or key loss. |

Key custody decisions matter as much as encryption state:

- Who controls keys?
- Who can use keys?
- Who can rotate keys?
- Who can disable or delete keys?
- Are key-use logs collected?
- Are encrypted backups recoverable after key rotation?
- Are duties separated between data admin and key admin?

## 22. Retention and Deletion: Retention Schedules, Legal Hold, Records Management, Defensible Deletion, Right-to-Delete Workflows, Secure Destruction, Backup/Archive Conflicts, and Deletion Proof

Retention controls reduce unnecessary exposure while preserving required records.

| Concept | Security meaning |
|---|---|
| Retention schedule | Defines how long data must or may be kept. |
| Legal hold | Suspends deletion for legal/regulatory reasons. |
| Records management | Treats certain data as official records with lifecycle rules. |
| Defensible deletion | Deletion according to approved policy, not arbitrary destruction. |
| Right-to-delete workflow | Privacy request workflow where applicable. |
| Secure destruction | Makes data unrecoverable within defined assurance. |
| Backup/archive conflict | Deleted primary data may remain in backups; policy must define treatment. |
| Deletion proof | Evidence that deletion was requested, approved, executed, and verified. |

Deletion is a workflow, not just an action.

```text
eligibility check
  -> legal hold check
  -> owner approval or automated policy
  -> deletion execution
  -> downstream/replica/cache/backup handling
  -> verification
  -> evidence record
```

Retention failures include indefinite data hoarding, stale exports, orphaned backups, untracked third-party copies, and inability to prove deletion.

## 23. Privacy Controls: PIA/DPIA Concepts, Purpose Limitation, Data Minimization, Consent/Legal Basis, Data Subject Rights, Cross-Border Transfer Risk, and Privacy-by-Design

Privacy controls govern data processing risk to individuals.

| Privacy control | Security implementation meaning |
|---|---|
| PIA/DPIA | Assess privacy risk before high-risk processing. |
| Purpose limitation | Use data only for approved purposes. |
| Data minimization | Collect and retain only what is necessary. |
| Consent/legal basis | Record lawful basis or user permission where applicable. |
| Data subject rights | Support access, correction, deletion, portability, restriction, and objection where applicable. |
| Cross-border transfer control | Review jurisdiction, contracts, residency, and transfer safeguards. |
| Privacy-by-design | Build privacy controls into systems, not after exposure. |
| Privacy-by-default | Default to minimal collection, minimal sharing, and least access. |

This file does not provide legal advice. It provides the security engineering model that supports privacy obligations. Legal and privacy officers determine jurisdiction-specific duties.

## 24. Test Data and Lower Environments: Synthetic Data, Masked Production Data, Tokenized Data, Subset Generation, Dev/Test Access, CI/CD Datasets, and Leakage Risk

Lower environments are common leakage points because they often have weaker controls than production.

| Approach | Strength | Risk |
|---|---|---|
| Synthetic data | Best when realistic enough for testing. | Poor synthetic quality can miss defects. |
| Masked production data | Preserves useful relationships. | Masking may be incomplete or reversible by context. |
| Tokenized data | Preserves operational matching. | Detokenization must be controlled. |
| Subset generation | Reduces volume. | May still include high-risk records. |
| Fabricated fixtures | Useful for unit testing. | May not cover real data complexity. |

Lower-environment controls:

- production data use requires data-owner approval;
- raw production data should not be copied to dev/test by default;
- masking/tokenization must happen before broad developer access;
- test datasets must have retention limits;
- CI/CD logs must not expose sample sensitive data;
- access must be reviewed;
- synthetic test data should be preferred when feasible.

## 25. Secrets in Data: Credentials, API Keys, Tokens, Private Keys, Passwords, Session Tokens, Connection Strings, Cloud Keys, Certificates, and Accidental Secrets in Files/Logs/Code/Tickets/Chats/Datasets

Secrets frequently appear in places that are not secret stores.

Common locations:

| Location | Example risk |
|---|---|
| Source code | Hardcoded tokens, keys, passwords, connection strings. |
| Logs | Authorization headers, cookies, session IDs, request bodies. |
| Tickets/chats | Screenshots, copied credentials, troubleshooting dumps. |
| Data exports | Credentials included in config or user tables. |
| Notebooks | API keys, dataset samples, query output. |
| Email | Shared passwords, certificates, private keys. |
| Backups | Historic secrets that remain valid. |
| Containers/artifacts | Build-time secrets or embedded config. |
| Documentation | Runbooks containing credentials. |

Controls:

- secrets scanning across code, logs, object stores, tickets, and collaboration tools;
- automatic revocation workflow for confirmed secret exposure;
- secret store adoption;
- short-lived credentials;
- redaction in logs;
- developer education;
- exception tracking for unavoidable secrets;
- evidence handling that does not spread the secret further.

## 26. Data Exposure Channels: Email, SaaS Sharing, Cloud Object Storage, File Shares, Databases, APIs, Endpoints, Mobile, Print, Removable Media, Screenshots, Clipboard, Chat, Tickets, Code Repositories, Logs, Backups, Exports, Analytics, and Third-Party Transfers

Data exposure channels are the paths through which sensitive data can leave approved boundaries.

| Channel | Defensive concern |
|---|---|
| Email | External recipients, attachments, forwarding, BEC-driven leakage. |
| SaaS sharing | Public links, guests, external collaborators, third-party apps. |
| Cloud object storage | Public buckets/containers, weak ACLs, stale pre-signed links. |
| File shares | Broad groups, inherited access, orphaned folders. |
| Databases | Overprivileged roles, unsafe exports, weak audit. |
| APIs | Excessive response data, weak object authorization, bulk export. |
| Endpoints | Downloads, sync folders, removable media, screenshots. |
| Mobile | Managed/unmanaged app boundary, local caches, screenshots. |
| Print | Physical copies and unattended printers. |
| Clipboard | Cross-app or work/personal data movement. |
| Chat/tickets | Informal sharing and copied artifacts. |
| Code repositories | Secrets and production samples. |
| Logs | Accidentally captured payloads or tokens. |
| Backups/exports | Large portable copies with broad blast radius. |
| Analytics | Derived exposure and broad analyst access. |
| Third parties | Contractual, jurisdictional, and onward-transfer risk. |

DLP must map to channels, not only data types.

## 27. Access Governance for Data: Owner Approval, Least Privilege, Need-to-Know, ABAC/RBAC, JIT Access, Access Reviews, External Sharing Reviews, Privileged Data Access, Break-Glass, and Service Account Data Access

Access governance ensures that sensitive data is used only by authorized humans and systems.

| Control | Purpose |
|---|---|
| Owner approval | Business accountability for access decisions. |
| Least privilege | Minimum access needed for task. |
| Need-to-know | Sensitivity-based access restriction beyond general role. |
| RBAC | Access by role or group. |
| ABAC | Access by attributes: user, device, label, purpose, location, risk. |
| JIT access | Temporary access with approval and audit. |
| Access reviews | Periodic validation by data owners. |
| External sharing review | Governance of guests, public links, and third parties. |
| Privileged data access | Special controls for admins and break-glass users. |
| Service-account access | Machine access tied to purpose, owner, rotation, and telemetry. |

Data access reviews should be risk-based:

- high-sensitivity data: frequent review;
- external access: frequent review;
- privileged access: strict review;
- orphaned ownership: immediate correction;
- inactive users/apps: removal;
- broad groups: owner justification.

## 28. Data Security in Cloud, SaaS, Endpoint, Mobile, Database, Backup, Email, API, and AI/RAG Handoffs Without Repeating Their CKVs

This file owns data-specific requirements; other CKVs own platform internals.

| Domain | Data-security handoff |
|---|---|
| Cloud | Object labels, KMS, public access, data perimeter, storage logs, region/residency, managed DLP. |
| SaaS | External sharing, app consent, retention, discovery, audit logs, data export, guest lifecycle. |
| Endpoint | Local sync, removable media, clipboard, screenshots, print, endpoint DLP. |
| Mobile | Managed apps, selective wipe, work/personal separation, app protection, mobile DLP signals. |
| Database | Sensitive columns, masking, tokenization, audit, exports, backups, replicas. |
| Backup | Retention, immutability, legal hold, deletion conflicts, restore access, exposure of historical data. |
| Email | DLP, encryption, external forwarding, attachment handling, message trace, BEC-related exposure. |
| API | Response minimization, object authorization, export controls, schema governance, rate limits. |
| AI/RAG | Source corpus classification, prompt/log data, embeddings, derived outputs, retrieval permissions, data minimization. |

Handoff rule: this CKV defines what must be protected and proved. Platform CKVs define how each platform implements the control.

## 29. Data Security Telemetry Sources

Data security needs telemetry from many layers.

| Source | Evidence |
|---|---|
| DLP platform | Policy match, content type, user, destination, action, response, justification. |
| CASB/SSE/SASE | SaaS app, file movement, upload/download, sharing, session control, user/device context. |
| Cloud storage logs | Object access, public access changes, bucket/container policy changes, object tags, data events. |
| Database audit | Login, failed login, role change, permission change, sensitive query/export, backup/restore. |
| SaaS audit | External sharing, guest access, file downloads, link creation, app consent, admin actions. |
| Endpoint DLP | Copy to USB, print, clipboard, screenshot, upload, local file movement. |
| Email logs | External recipients, attachments, DLP match, forwarding, message encryption. |
| File-share audit | Permission changes, bulk reads, external sync, owner changes. |
| IAM/IdP logs | Sign-in, risk, device compliance, MFA, session revocation, privileged access. |
| KMS/HSM logs | Key use, decrypt operations, key policy changes, key disable/delete. |
| Backup logs | Backup creation, restore, export, retention change, deletion, immutable lock status. |
| Data catalog | Classification, ownership, lineage, sensitivity changes, stale assets. |
| Secrets scanning | Secret type, repository/location, exposure age, rotation status. |
| Privacy workflows | PIA/DPIA, DSR, deletion request, legal hold, breach review. |

Telemetry should preserve enough context to answer: what data, which user/app, which device, what channel, what destination, what policy, what action, and what business justification.

## 30. Data Security Threat Model

Data security threats target confidentiality, integrity, availability, privacy, control evidence, and business trust.

| Threat | Defensive meaning |
|---|---|
| Unclassified data | Sensitive data lacks labels and handling rules. |
| Shadow data | Unknown copies exist outside governance. |
| Overexposure | Too many users/apps/guests can access data. |
| Unauthorized sharing | Data moves to unapproved users, devices, apps, regions, or third parties. |
| Weak retention | Data is kept longer than needed or deleted too early. |
| Backup exposure | Sensitive historic data exists in weakly governed backups. |
| Test-data leakage | Production records appear in dev/test/CI environments. |
| Secret leakage | Credentials embedded in data, logs, files, tickets, or code. |
| DLP blind spots | Important channels are not monitored. |
| DLP alert fatigue | Real exposures are lost in noise. |
| Privacy leakage | Processing violates minimization, purpose, or rights expectations. |
| Re-identification | Transformed data can be linked back to individuals. |
| Ransomware/extortion | Data is encrypted, destroyed, or stolen for leverage. |
| Insider misuse | Authorized access used for unauthorized purpose. |
| Third-party leakage | Shared data leaks outside direct control. |
| Deletion failure | Data remains after deletion is required. |
| Evidence leakage | Investigation copies spread sensitive data further. |

## 31. Threat-to-Control Matrix

| Threat | Preventive controls | Detective controls | Corrective/recovery controls |
|---|---|---|---|
| Unclassified data | Auto-classification, labels, owner assignment. | Classification reports, discovery scans. | Remediation backlog, owner review, label correction. |
| Shadow data | Data inventory, sanctioned repositories, storage guardrails. | Discovery scans, cloud/storage logs, CASB. | Migrate, classify, restrict, delete, or accept with exception. |
| Overexposure | Least privilege, ABAC/RBAC, external sharing policy. | Access reviews, sharing reports, permission-change alerts. | Revoke access, remove guests, fix groups. |
| Unsafe sharing | DLP, external sharing approval, encryption, domain allowlists. | DLP events, SaaS audit, email trace. | Revoke links, recall/quarantine, notify owner. |
| Weak retention | Retention schedules, labels, legal hold process. | Retention reports, stale-data reports. | Apply labels, purge, archive, hold, or update policy. |
| Backup exposure | Backup encryption, immutable storage, restricted restore. | Backup access logs, restore alerts. | Rotate keys, restrict restore, recover from clean copy. |
| Test data leakage | Synthetic data, masking/tokenization, lower-env restrictions. | Dev/test scans, repository scans, DLP. | Remove data, rotate exposed secrets, rebuild test datasets. |
| Secret leakage | Secret stores, redaction, scanning gates. | Secret scanning, KMS/IdP alerts. | Revoke/rotate, invalidate sessions, investigate use. |
| DLP blind spots | Channel coverage design, CASB/SSE, endpoint/email/web DLP. | Coverage reporting, control gap reviews. | Expand telemetry, tune policies, add compensating controls. |
| Privacy leakage | PIA/DPIA, minimization, purpose controls. | Privacy workflow audit, access logs, DLP. | Contain, notify privacy/legal, delete, correct records. |
| Re-identification | De-identification review, token domain separation, aggregation. | Dataset join review, access monitoring. | Re-transform, restrict, remove, reclassify. |
| Insider misuse | JIT, approval, behavior monitoring, segregation of duties. | UEBA, access anomaly, bulk access. | Suspend access, preserve evidence, HR/legal handoff. |

## 32. Preventive Controls

Preventive controls reduce the chance of unsafe data exposure.

Core preventive controls:

- data management process with named owners;
- data inventory and classification coverage;
- sensitivity labels and retention labels;
- approved repositories and sanctioned storage paths;
- least-privilege access and owner-approved access workflows;
- external sharing restrictions and guest governance;
- data minimization at collection and API response boundaries;
- DLP policies for email, endpoint, web, SaaS, cloud storage, and code/secrets;
- encryption at rest and in transit;
- field-level encryption or tokenization for high-risk fields;
- masking for lower environments;
- retention schedules and deletion workflows;
- legal hold process;
- key custody separation;
- service account access governance;
- contract and third-party data-transfer rules;
- privacy impact assessments for high-risk processing;
- secure test data standards;
- secrets scanning gates;
- backup access and encryption controls.

Preventive controls should be tied to classification. Not all data needs identical controls, but all data needs a defined handling rule.

## 33. Detective Controls and Telemetry Sources

Detective controls identify exposure, misuse, drift, and control failure.

High-value detections:

| Detection category | Example signal |
|---|---|
| Public exposure | Public object/link, anonymous share, public repository, unauthenticated storage. |
| External sharing | Sensitive file shared externally or with broad guest group. |
| Bulk access | Unusual downloads, exports, queries, or object reads. |
| Sensitive data in low-control locations | Restricted data in personal cloud, endpoint downloads, test environment, or public ticket. |
| Secret leakage | API keys/tokens/passwords/private keys in code, logs, chats, tickets, or object stores. |
| Retention drift | Data past retention period without legal hold. |
| Label drift | Sensitive content with weak/no label. |
| Permission drift | Broad groups, everyone links, stale guests, ownerless folders. |
| DLP suppression misuse | Repeated justifications or risky allowlist activity. |
| Key access anomaly | Unexpected decrypt/key-use events. |
| Backup/restore anomaly | Unusual restore/export or backup deletion attempt. |
| Privacy workflow anomaly | Deletion request stuck, DSR overdue, or legal hold conflict. |

Detection must include the data owner and business context whenever possible; otherwise alerts become hard to triage.

## 34. Corrective, Recovery, and Compensating Controls

Corrective controls fix exposure and reduce recurrence.

| Control type | Examples |
|---|---|
| Corrective | Revoke sharing, remove external users, relabel data, restrict permissions, rotate secrets, delete unauthorized copies. |
| Recovery | Restore clean data, recover from immutable backups, recover keys, rebuild masked datasets, reissue tokens. |
| Compensating | Manual review, enhanced logging, contractual restriction, temporary owner approval, network egress restrictions, additional encryption. |

Common corrective workflow:

```text
confirm sensitive data
  -> identify owner and affected channel
  -> contain access
  -> preserve minimal evidence
  -> revoke or remove exposure
  -> rotate affected secrets/keys/tokens
  -> assess notification obligations
  -> validate remediation
  -> update policy, classifier, or access rule
```

Compensating controls must be temporary, documented, owner-approved, and reviewed.

## 35. Required Policies and Standards

Data security requires policy documents that translate governance into enforceable handling rules.

| Policy / standard | Required content |
|---|---|
| Data Security Policy | Overall data lifecycle, ownership, classification, access, protection, monitoring, and evidence obligations. |
| Data Classification Standard | Classes, examples, handling rules, labels, owner duties, and exceptions. |
| Data Handling Standard | Storage, transfer, sharing, encryption, printing, mobile, endpoint, cloud, and third-party handling. |
| DLP Standard | Channels, detection methods, response actions, tuning, exceptions, and escalation. |
| Data Discovery Standard | Scan scope, frequency, tooling, owner review, and remediation. |
| Data Labeling Standard | Sensitivity labels, retention labels, inheritance, auto-labeling, manual labels, and review. |
| Data Retention and Disposal Standard | Retention schedules, legal hold, deletion, archive, destruction, and proof. |
| Privacy Engineering Standard | PIA/DPIA, minimization, purpose limitation, rights support, and privacy-by-design. |
| Test Data Standard | Synthetic/masked/tokenized production data use, lower-environment controls, and approvals. |
| Masking and Tokenization Standard | Approved methods, quality, detokenization authority, vault security, and validation. |
| Encryption and Key Custody Standard | Encryption layers, key ownership, rotation, KMS/HSM, and key-use logging. |
| Third-Party Data Sharing Standard | Contracts, transfer approvals, data processing agreements, audit rights, and exit handling. |
| Data Incident Response Procedure | Exposure triage, record counting, containment, privacy/legal handoff, notification, and recovery. |
| Exception Standard | Time-bound exceptions, owner approval, risk acceptance, compensating controls, review. |

## 36. Hardening Baseline

Minimum data-security baseline:

- maintain an inventory of sensitive and regulated data stores;
- assign data owners for high-risk datasets;
- use a practical classification model;
- apply labels or tags to sensitive data where supported;
- restrict external sharing by default for restricted data;
- require owner approval for broad access and external sharing;
- enable DLP monitoring for email, endpoint, cloud storage, SaaS, and source repositories where applicable;
- use synthetic or masked/tokenized data outside production unless formally approved;
- encrypt sensitive data at rest, in transit, and in backups;
- protect and monitor key use;
- scan for secrets in repositories, object stores, logs, tickets, and collaboration platforms;
- define retention and deletion schedules;
- test deletion and restore workflows;
- review access and sharing regularly;
- collect audit logs for sensitive repositories;
- maintain DLP exception register;
- maintain privacy incident response handoff.

Baseline strength should increase with classification level.

## 37. Configuration Review Checklist

Review data-security controls with these questions:

| Area | Review question |
|---|---|
| Inventory | Are sensitive datasets known, owned, and cataloged? |
| Classification | Are labels accurate and applied to high-risk data? |
| Discovery | Are scans covering structured, unstructured, SaaS, cloud, endpoint, logs, code, and backups? |
| Access | Is access least-privilege and owner-approved? |
| External sharing | Are public/anonymous links blocked or tightly governed? |
| DLP | Are policies staged, tuned, enforced, and monitored? |
| Masking | Is lower-environment production data masked or synthetic? |
| Tokenization | Is detokenization restricted and logged? |
| Encryption | Are keys separated from data admins where needed? |
| Retention | Are schedules defined and technically enforced? |
| Deletion | Is deletion proof available across primary and secondary locations? |
| Backups | Are backups encrypted, access-controlled, retained correctly, and restorable? |
| Logs | Do logs avoid sensitive payloads and capture required access evidence? |
| Secrets | Are secrets detected, revoked, and moved to approved secret stores? |
| Privacy | Are high-risk processing activities reviewed through PIA/DPIA or equivalent? |
| Third parties | Are transfers documented, approved, and monitored? |

## 38. Detection Logic Categories

Useful data-security detections include:

1. Sensitive data in unapproved repository.
2. Restricted label downgraded or removed.
3. External sharing of restricted or regulated data.
4. Anonymous/public link created for sensitive content.
5. Bulk download/export by unusual user or service account.
6. Unusual access from unmanaged device or risky location.
7. DLP block/warn/override repeated by same user.
8. Sensitive data uploaded to unsanctioned SaaS.
9. Sensitive data sent to personal email or external domain.
10. Secret detected in source code, logs, tickets, or object storage.
11. Production data detected in lower environment.
12. Data retained past policy without legal hold.
13. Legal hold removed or changed unexpectedly.
14. Key decrypt spike for restricted dataset.
15. Backup restore/export outside normal window.
16. External guest accesses large number of files.
17. App/service principal accesses sensitive data outside expected pattern.
18. Data classification scan coverage gap.
19. Token vault detokenization anomaly.
20. Deletion request incomplete beyond SLA.

Each detection should map to owner, asset, classification, channel, action, outcome, and response owner.

## 39. Incident Response Considerations

Data incidents require rapid scoping and careful evidence handling.

Triage questions:

- What data class is involved?
- Which fields/records/files are affected?
- How many individuals/customers/accounts/records are affected?
- Was data accessed, copied, shared, downloaded, exported, or only exposed?
- Who accessed it?
- From which device, app, identity, and location?
- Was the destination internal, external, public, third-party, or unknown?
- Is the data still accessible?
- Does encryption, masking, tokenization, or key custody reduce impact?
- Are secrets, credentials, or keys included?
- Are legal/privacy notification triggers possible?
- What copies, caches, backups, exports, logs, or downstream datasets exist?

Containment options:

- revoke external links;
- remove guest access;
- quarantine messages/files;
- disable exposed token/key/secret;
- rotate affected keys or credentials;
- restrict repository access;
- suspend risky app integration;
- block DLP channel;
- preserve minimal evidence;
- notify owner/privacy/legal as required.

## 40. Forensics and Evidence Considerations

Data evidence can itself be sensitive. Evidence handling must minimize additional exposure.

Evidence sources:

| Evidence | Use |
|---|---|
| DLP alert | Initial match, policy, user, destination, action. |
| Storage logs | Object/file access, policy changes, sharing changes. |
| SaaS audit | Link creation, guest access, downloads, admin actions. |
| Email trace | Sender, recipient, attachment, DLP action, encryption. |
| Endpoint DLP | Local transfer, USB, print, clipboard, screenshots. |
| Database audit | Query/export/access, role changes, sensitive table access. |
| KMS logs | Decrypt/key use related to dataset. |
| Backup logs | Restore/export/delete/retention events. |
| Data catalog | Classification, owner, lineage, downstream consumers. |
| Privacy workflow | DSR, breach review, legal hold, notification decision. |

Evidence minimization rules:

- collect metadata first;
- avoid copying full sensitive records unless required;
- redact evidence packages;
- preserve hashes or identifiers where possible;
- restrict case access;
- log evidence access;
- coordinate with legal/privacy for regulated data.

## 41. Validation and Safe Testing

Validation must use synthetic data or approved non-sensitive test data.

Safe validation methods:

| Validation | Method |
|---|---|
| Data inventory sampling | Select repositories and verify catalog accuracy against sample discovery results. |
| Classification test | Place synthetic classified files in approved test locations and verify label/classifier behavior. |
| DLP simulation | Use synthetic patterns that resemble sensitive formats but are not real records. |
| Access review | Verify owner approval, group membership, external sharing, service accounts, and stale access. |
| Retention proof | Confirm retention labels/schedules and deletion eligibility workflow. |
| Deletion proof | Test deletion process on synthetic records and verify primary/downstream handling. |
| Masking quality | Verify masked data cannot expose original values and maintains required utility. |
| Tokenization control | Verify detokenization authority, logging, and token domain separation with test tokens. |
| Secret scanning | Use harmless canary secrets and confirm detection and revocation workflow. |
| Backup handling | Verify encrypted backup, restore access, and retention alignment using non-sensitive data. |
| Third-party transfer review | Validate approval, contract, and logging for a controlled test transfer. |
| Incident tabletop | Simulate data exposure, privacy escalation, record counting, and containment. |

## 42. Lab-Safe Boundaries

Safe practice is allowed only in controlled environments with non-sensitive data.

Allowed:

- synthetic DLP test strings;
- fake PII-like records clearly marked as test data;
- mock datasets;
- test labels and test repositories;
- tabletop exercises;
- metadata-only access review;
- privacy workflow simulation;
- deletion proof on synthetic data;
- DLP simulation mode;
- canary secrets designed for testing.

Not allowed in this CKV:

- moving real sensitive data to test DLP;
- bypassing DLP;
- stealth transfer methods;
- dumping databases or files;
- harvesting secrets from real repositories;
- re-identifying de-identified data for abuse;
- insider-theft workflows;
- privacy evasion.

## 43. Framework and Control Mapping

Framework mapping should be evidence-based.

| Framework / standard | Data-security mapping |
|---|---|
| NIST CSF 2.0 | Govern data risk, identify data assets, protect sensitive data, detect exposure, respond to incidents, recover data services. |
| NIST Privacy Framework | Identify-P data processing, Govern-P privacy program, Control-P data processing management, Communicate-P transparency, Protect-P safeguards. |
| NIST SP 800-122 | PII identification, confidentiality impact, safeguards, minimization, incident response planning. |
| NIST SP 800-53 | AC, AU, CM, CP, IA, IR, MP, PL, PM, RA, SC, SI, and PT privacy/security controls. |
| CIS Controls v8 / v8.1 | Control 3 Data Protection; also inventory, access control, audit logs, secure configuration, incident response. |
| ISO/IEC 27001 | Asset management, access control, cryptography, logging, backup, supplier, incident, privacy-adjacent controls. |
| ISO/IEC 27701 | Privacy information management, controller/processor obligations, PII lifecycle governance. |
| PCI DSS | Cardholder data discovery, PAN protection, masking, tokenization, encryption, logging, retention, and scope reduction. |
| GDPR-style principles | Lawfulness, fairness, transparency, purpose limitation, minimization, accuracy, storage limitation, integrity/confidentiality, accountability. |
| CISSP CBK | Asset security, IAM, security architecture, software security, operations, incident response, legal/privacy. |
| OWASP ASVS/API | Data minimization, sensitive data protection, secure storage, access control, logging, API response minimization. |
| CSA CCM | Cloud data governance, encryption/key management, logging, retention, data location, privacy, third-party cloud processing. |
| MITRE ATT&CK defensive mapping | Monitor collection, exfiltration, cloud storage discovery, data from information repositories, email collection, and account/data access patterns without providing offensive steps. |

Mapping format for audit evidence:

```text
control family
  -> data security requirement
  -> implementation evidence
  -> telemetry evidence
  -> validation method
  -> owner
```

## 44. Common Failures

- Sensitive data exists without owner.
- Data classification exists only as a policy PDF.
- Labels are applied manually and inconsistently.
- DLP monitors email but ignores SaaS and endpoint channels.
- Cloud storage is scanned but backups are not.
- Dev/test contains raw production data.
- Logs contain secrets or sensitive payloads.
- DLP rules overmatch and generate alert fatigue.
- DLP rules undermatch because they rely only on regex.
- Retention schedules exist but are not technically enforced.
- Deletion requests do not cover replicas, exports, or backups.
- Token vault access is too broad.
- Masked data remains re-identifiable after joins.
- External sharing is not reviewed.
- Service accounts have broad data access.
- Privacy incident workflows are separate from security IR.
- Evidence packages spread sensitive records.
- Data catalogs are stale.
- Third-party transfers lack inventory or return/delete proof.
- Backups retain data indefinitely.

## 45. Common Mistakes

- Treating encryption as a substitute for access governance.
- Treating DLP as a single product rather than a control architecture.
- Treating privacy as only legal paperwork.
- Treating masked data as automatically safe.
- Treating tokenization as low risk while leaving detokenization broad.
- Letting engineers copy production data to lower environments for convenience.
- Creating too many classification labels for users to understand.
- Blocking all DLP events without business workflow design.
- Allowing permanent DLP exceptions.
- Ignoring derived data and analytics outputs.
- Ignoring screenshots, clipboard, print, chat, and tickets.
- Ignoring logs as a sensitive data store.
- Running discovery once and calling it inventory.
- Not testing restore and deletion workflows.
- Not involving data owners in access review.
- Not rotating exposed secrets after discovery.

## 46. Must-Memorize Facts

- Data security begins with inventory, ownership, classification, and lifecycle.
- DLP detects and governs movement; it does not replace access control.
- Classification without enforcement is only metadata.
- Encryption protects data from certain exposure paths, not from authorized misuse.
- Masking reduces exposure but does not automatically anonymize data.
- Tokenization moves risk into token vault governance and detokenization authority.
- De-identified data can be re-identified when joined with other datasets.
- Retention reduces breach impact by reducing unnecessary data.
- Deletion must consider replicas, caches, exports, archives, backups, and downstream systems.
- Logs, tickets, chats, code, notebooks, and backups are data stores.
- Secrets inside data require revocation, not just deletion.
- DLP must be tuned with synthetic test data and business context.
- Privacy incidents require evidence minimization.
- Data owners approve business use; custodians implement controls.
- Data lineage is essential for incident scope and privacy rights.

## 47. Interview / Exam Points

Expect questions around:

- difference between classification, labeling, discovery, and DLP;
- how DLP works across endpoint, email, web, SaaS, CASB/SSE, and cloud storage;
- why false positives and false negatives matter;
- difference between masking, tokenization, encryption, pseudonymization, and anonymization;
- why token vaults are high-value systems;
- how to protect test data;
- how retention and legal hold interact;
- how to scope a data breach;
- how to handle secrets found in logs or repositories;
- how data owners, stewards, custodians, security, privacy, and legal roles differ;
- how NIST Privacy Framework relates to NIST CSF;
- how CIS Control 3 maps to data protection;
- why deletion proof is difficult in backups and distributed systems;
- why data lineage matters for privacy and incident response.

Strong answer pattern:

```text
Know the data -> classify it -> assign owner -> restrict access -> control movement -> transform/minimize where possible -> monitor channels -> retain/delete defensibly -> prove with evidence.
```

## 48. Expert-Level Insights

1. **Data security is a metadata problem before it is a DLP problem.** Without owner, label, purpose, lineage, and retention metadata, policy engines cannot make reliable decisions.

2. **DLP is strongest when combined with identity and device context.** A pattern match alone is weak. A pattern match plus restricted label, risky destination, unmanaged device, and external recipient is much stronger.

3. **Deletion is harder than storage.** Copies, replicas, caches, analytics outputs, backups, and third-party transfers make deletion proof an engineering and governance problem.

4. **Data joins can create new sensitivity.** Two internal datasets can become regulated or restricted when combined.

5. **Tokenization reduces exposure but centralizes power.** The detokenization authority becomes a critical control plane.

6. **Privacy evidence should be minimized.** Incident responders can accidentally create a second exposure by over-collecting sensitive data into case systems.

7. **Test data is a leading indicator of data governance maturity.** Mature organizations can test with synthetic, masked, or tokenized data without copying raw production records.

8. **Retention is a security control.** Unnecessary historic data increases breach impact, discovery cost, privacy risk, and recovery complexity.

9. **Classification must be operational.** A label that cannot drive access, DLP, retention, encryption, or workflow decisions is weak assurance.

10. **Shadow data is often more dangerous than shadow IT.** A sanctioned tool with unsanctioned data can create more risk than an unsanctioned app with harmless data.

## 49. Generation Boundaries and Unsafe Content Restrictions

This CKV is defensive and governance-focused.

It may include:

- data lifecycle models;
- classification models;
- DLP architecture;
- detection categories;
- incident response workflows;
- evidence minimization;
- masking/tokenization concepts;
- retention/deletion governance;
- privacy engineering controls;
- synthetic validation methods;
- framework/control mapping.

It must not include:

- real data exfiltration workflows;
- DLP bypass tactics;
- stealth transfer methods;
- sensitive data dumping;
- credential harvesting from datasets;
- privacy evasion;
- re-identification abuse;
- insider-theft playbooks;
- offensive data-theft procedures;
- instructions to evade monitoring or suppress alerts.

Unsafe material from source references must be normalized into controls, detections, validation, or incident-response guidance.

## 50. Quick Reference Tables

### 50.1 Data protection method comparison

| Method | Main purpose | Reversible? | Key risk |
|---|---|---:|---|
| Encryption | Confidentiality using keys. | Yes, with key. | Key misuse or broad decrypt access. |
| Hashing | Integrity or one-way representation. | No by design. | Weak inputs can be guessed; not for all privacy needs. |
| Masking | Reduce visible sensitive values. | Usually no, but context may reveal. | Poor masking or re-identification. |
| Tokenization | Replace value with token. | Yes, through token system. | Token vault or detokenization abuse. |
| Pseudonymization | Reduce direct identity exposure. | Often linkable. | Re-identification by join. |
| Anonymization | Remove identifiability. | Intended no. | Hard to prove; context changes risk. |
| Aggregation | Reduce individual visibility. | Usually no. | Small groups can reveal individuals. |
| Redaction | Remove visible sensitive content. | No if applied correctly. | Hidden metadata or incomplete removal. |

### 50.2 DLP response ladder

| Risk level | Response |
|---|---|
| Low | Audit and measure. |
| Low/medium | Warn and coach. |
| Medium | Require justification and owner notification. |
| Medium/high | Encrypt, quarantine, or restrict sharing. |
| High | Block and create ticket. |
| Critical | Block, revoke access, incident creation, privacy/legal escalation. |

### 50.3 Data incident scoping fields

| Field | Why needed |
|---|---|
| Data class | Determines impact and obligations. |
| Data owner | Determines business authority. |
| Affected records | Determines scope. |
| Affected people/entities | Determines privacy/customer impact. |
| Channel | Determines containment control. |
| Actor | Determines account/device/app risk. |
| Destination | Determines exposure severity. |
| Duration | Determines window of access. |
| Protection state | Encryption/masking/tokenization can reduce impact. |
| Downstream copies | Determines full remediation. |
| Evidence source | Determines proof quality. |

### 50.4 Data owner approval checklist

| Question | Required answer |
|---|---|
| What data is requested? | Dataset and fields. |
| Why is it needed? | Business purpose. |
| Who needs it? | User/group/app/service account. |
| For how long? | Expiry or review date. |
| Where will it be stored? | Approved location. |
| Will it be shared externally? | Recipient and contract basis. |
| Is masking/minimization possible? | Yes/no with rationale. |
| What telemetry exists? | Logs and monitoring source. |
| What is the fallback if denied? | Alternative data or process. |

## 51. Final Engineering Checklist

Use this checklist before accepting a data-security design:

- [ ] Sensitive data inventory exists and is owner-approved.
- [ ] Data classification model is simple, documented, and enforceable.
- [ ] High-risk datasets have owners, stewards, custodians, and privacy/security contacts.
- [ ] Data discovery covers structured, semi-structured, unstructured, cloud, SaaS, endpoint, email, code, logs, and backups.
- [ ] Labels/tags map to DLP, access, retention, encryption, and sharing policies.
- [ ] Data lineage identifies source, transformation, consumers, and derived datasets.
- [ ] External sharing is restricted and reviewed.
- [ ] Endpoint, email, web, SaaS, cloud storage, source-code, and database DLP channels are addressed.
- [ ] DLP is staged, tested with synthetic data, tuned, and owner-approved before broad enforcement.
- [ ] DLP exceptions are time-bound and reviewed.
- [ ] Test/lower environments use synthetic, masked, or tokenized data by default.
- [ ] Token vault or detokenization authority is tightly controlled and logged.
- [ ] Masking quality is validated for privacy and utility.
- [ ] Re-identification risk is assessed for de-identified or aggregated data.
- [ ] Secrets scanning covers code, logs, tickets, chats, object stores, and backups.
- [ ] Confirmed secret exposure triggers revocation/rotation.
- [ ] Retention schedules and legal hold workflows are implemented.
- [ ] Deletion proof is available for primary stores and downstream copies where feasible.
- [ ] Backup and archive exposure is governed.
- [ ] Data incident response includes record counting, privacy/legal handoff, evidence minimization, and containment playbooks.
- [ ] Framework mapping connects requirement, implementation evidence, telemetry evidence, validation method, and owner.

End of CKV-122.

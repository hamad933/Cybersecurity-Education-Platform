# Product Risks and Mitigations

| Risk | Consequence | Candidate mitigation / acceptance signal |
|---|---|---|
| Product collapses into LMS/notes | Passive completion with no capability evidence | Vertical-slice gates require simulator action, evidence, mastery, and failure review |
| Curriculum breadth mistaken for product completion | Inflated v1 claims | Scope matrix separates platform completeness from Domain expansion |
| Weak source authority | Incorrect lessons | Claim-level provenance, applicability, conflict state, and publication blockers |
| Source/private-data leakage | Confidentiality loss | Local-first default, visible export scope, no silent transfer, audit and package hashes |
| Simulator presented as real competence | Misleading evidence | Mandatory origin labels and claim-specific Real-Lab classification |
| Static scripted simulator | No meaningful decision practice | State/input-dependent contract, positive/negative tests, replay determinism |
| Baseline corruption by a run | Loss of reusable enterprise truth | Immutable baseline revisions and isolated Run namespace |
| Imported content executes | Device compromise | Quarantine, safe preview, no macro/script/command execution, sanitization |
| Revision/history loss | Broken auditability | Immutable publication, transactionality, backups, restoration as new revision |
| Arbitrary JSON model | Unbounded invalid data | Registered block/scenario schemas, versioning, key/depth/size constraints |
| Premature infrastructure | Delivery delay | Modular Monolith, PostgreSQL search/queue, measured revisit triggers |
| Manual AI result trusted | Provenance forgery or content corruption | Digest/schema/source-reference validation and human review before drafts |
| Mastery gaming or false precision | Invalid capability claims | Evidence-specific rules, provisional thresholds, retained failed attempts |
| Mixed RTL/LTR corruption | Unusable technical content | Native bidi controls, isolated LTR containers, no string reversal, visual QA |
| Local service exposed | Unauthorized access | Loopback binding by default, owner auth, session/CSRF controls, explicit remote-mode decision |


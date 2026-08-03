# VS-001 Architecture Slice — Windows Authorization Decision

Status: **architecture candidate only; not implemented**. IDs: `VS-001`, `CAP-D03-03-01`, `KU-AD-02`.

## Purpose and authority boundary

VS-001 proves the deep lifecycle: selected reviewed evidence -> published KU/Lesson Revision -> Micro Practice -> Guided Simulator Lab -> `SIMULATED` Evidence -> Mastery update -> Failure-Based Review. It does not establish authoritative Windows semantics. A target Windows baseline and Microsoft/Open Specifications verification are blockers before technical content publication or real-transfer claims.

## Minimum entities by module

- MOD-SRC: OriginalSource, SourceSegment, AuthorityAssessment, ClaimSupport for selected Task 003R evidence.
- MOD-KNO: KnowledgeItem/Revision, Lesson/Revision, typed blocks, citation and publication decision.
- MOD-CUR: `CAP-D03-03-01`, `KU-AD-02`, prerequisite link, lifecycle template.
- MOD-LRN: MicroPractice, LabDefinition link, Attempt, MasteryRuleSet/State, ReviewTrigger/Schedule.
- MOD-ENT: Organization/Asset, SimulatedIdentity/Account/Group/Privilege, TargetObject, SecurityPolicy context, Baseline Revision.
- MOD-SIM: Scenario Definition Revision, Run, authorization input, rule set, transition/explanation trace, reset/replay.
- MOD-EVD: Evidence Requirement and `SIMULATED` evidence bound to run/transition.
- MOD-IAM/PLT: owner action authorization, audit, digest, queue/search primitives.

## Authorization input contract

The request includes principal; token user SID; ordered group SIDs and attributes; enabled privileges; target object/type; security descriptor; owner; ordered DACL/ACE list including type, trustee SID, mask, flags and applicability; requested access mask; and object-type generic-right mapping where applicable. Missing, unsupported, ambiguous, or version-dependent information produces `INSUFFICIENT_STATE` or `UNSUPPORTED_STATE`, never a guessed allow/deny.

## Evaluation and explanation contract

The candidate engine validates syntax/types, maps generic rights where declared, records owner/privilege handling only when the approved rule set supports it, evaluates applicable explicit deny/allow ACEs in defined order, tracks remaining requested bits, and returns allow, deny, or unresolved with decisive rule IDs. The explanation lists normalized inputs, mapping, each considered/skipped ACE and reason, mask changes, decisive state, unsupported assumptions, rule-set/source version, and pre/post digests.

This is a bounded educational model until primary authority verification. It must not silently generalize across Windows versions, object managers, inheritance/canonicalization behavior, mandatory integrity, conditional ACEs, claims, privileges, or other semantics outside the approved rule set.

## Test and evidence set

At minimum: explicit allow; explicit deny preceding allow; group-derived allow; missing group; partial mask; generic mapping; non-applicable ACE; insufficient descriptor; unsupported ACE/type; permission/action syntax failure; deterministic repeat; reset; replay. One positive and one negative case are required for mastery consideration. Evidence contains origin `SIMULATED`, run/scenario/baseline/rule versions, normalized input, ordered trace, result, state digests, test assertion, timestamp, and integrity digest.

## Publication and failure review

A draft and published Lesson Revision are visibly distinct. Publishing is blocked until the authority decision permits the technical claim. Incorrect allow/deny, missed decisive ACE, wrong mask mapping, unsupported-state guess, missing provenance, or failed delayed retention creates a specific ReviewTrigger linked to the relevant block/practice/case. Thresholds remain provisional.


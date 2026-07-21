# Task 004 External Approval Record

Status: **APPROVED BASELINE FOR BOUNDED TASK 006 IMPLEMENTATION**  
Decision: **APPROVE WITH IMPLEMENTATION CONDITIONS**  
Approval date: **2026-07-22**  
Decision authority: the externally reviewed Task 004 + Task 005 decision transmitted in `phase-packs/TASK_006_REPOSITORY_FOUNDATION.md`.

## Status transition

This record is the governing status transition for the Task 004 candidate. The approved audit inputs remain byte-for-byte unchanged and retain `CANDIDATE`, `PROPOSED`, and `NOT IMPLEMENTED` wording that was truthful when they were produced. Their names and contents are not rewritten to simulate retrospective approval.

The approval covers the product definition, v1 scope, module catalog, success criteria, delivery plan, risk register, architecture baseline, module boundaries, logical and lifecycle models, content/revision model, curriculum/learning/mastery model, enterprise/scenario/simulation model, Manual AI Bridge architecture, security/privacy threat model, deployment/operations baseline, storage/import boundaries, search/async baseline, all fourteen Task 004 ADRs, all Task 004 UX artifacts, all twelve Task 004 planning TSVs, the VS-001 architecture candidate, design proof, validators, and Task 004 review packet enumerated by `APPROVED_BASELINE_INDEX.md`.

Task 003R is the approved semantic planning baseline for downstream interpretation. Its original review-candidate artifacts also remain immutable audit history; approval is represented by this governing transition rather than by altering prior outputs.

## Binding implementation conditions

1. Resolve the Task 004 dependency-table ambiguity before scaffolding using the single direction `consumer_module -> allowed_dependency_module` and an acyclic graph.
2. Build one deployable Modular Monolith only; no microservices or separate API/frontend application.
3. Implement active code only for `MOD-IAM` and `MOD-PLT`; represent the remaining eight modules in the registry only.
4. Use supported, mutually compatible, version-locked Laravel/PHP, Vue 3/TypeScript/Inertia, PostgreSQL, and Tailwind releases verified from primary sources.
5. Provide one secure local owner, with no public registration, no default credential, and no development authentication bypass.
6. Preserve all Task 004 and earlier artifacts byte-for-byte. Do not read or modify `source-vault/originals/`.
7. Keep the Manual AI Bridge manual-only, keep the Institutional Simulator as the Guided Lab default, and preserve truthful evidence-origin and selective Real-Lab boundaries.
8. Task 006 may implement only its repository foundation. Task 007 and VS-001 remain separately gated and must not begin.
9. Task 006 ends as `REVIEW CANDIDATE — NOT SELF-APPROVED` at `STOP-REPOSITORY-FOUNDATION-006`.

## Scope of authorization

Task 006 is authorized to create the repository/application foundation, foundation persistence and platform primitives, owner authentication, the actual Arabic-first shell, quality/security gates, operational artifacts, tests, review evidence, and automatic handoff described by its prompt. It is not authorized to implement product workflows, broad domain modules, source ingestion, knowledge publication, learning/mastery, enterprise/scenario/simulator/evidence behavior, Manual AI processing, or any VS-001 semantics.

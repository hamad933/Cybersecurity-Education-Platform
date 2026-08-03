# v1 Success Criteria

All criteria are testable; none is satisfied by Task 004 documentation.

1. A clean local installation can create one owner, authenticate, protect sessions, and reject unauthorized state changes.
2. An authorized source can be imported without modifying the original; digest, anchors, authority state, warnings, and audit are retrievable.
3. A canonical item and lesson can move through draft/review/publication; a published revision cannot be mutated and restoration creates a new revision.
4. Typed content blocks reject unknown types/keys, unsafe HTML, invalid nesting, and oversized payloads while preserving Arabic prose and LTR technical tokens.
5. The daily queue explains why an item was selected and links a capability to lesson, practice, lab, evidence requirement, mastery state, and review trigger.
6. VS-001, VS-002, and VS-003 each run positive and negative paths in the Institutional Simulator and produce deterministic, origin-labelled evidence.
7. Every Scenario Run is isolated from its Enterprise Baseline and can reset/replay to the same result for the same inputs.
8. Mastery transitions are capability-specific, evidence-backed, auditable, configurable, and never represented as a single percentage-complete value.
9. Manual AI export shows exact scope and hashes; invalid/tampered imports fail; accepted content becomes a draft and never auto-publishes.
10. Search returns authorized PostgreSQL full-text results with provenance; queued processing is idempotent and exposes failure/retry state.
11. A backup and staged restore preserve database/blob consistency and pass manifest/hash verification without exposing secrets.
12. Principal workflows pass keyboard-only use, visible-focus, semantic-structure, contrast/touch-target, responsive, and Arabic RTL / technical LTR checks.
13. Threat-model controls for import, rendering, sessions, local exposure, evidence, audit, and packages pass their security tests.
14. Release evidence states limitations truthfully, including uncalibrated thresholds and any claim-specific authority or real-lab gaps.


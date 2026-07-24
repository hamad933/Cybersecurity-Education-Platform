# VS-001 rule-engine boundaries

The engine is a pure, deterministic PHP evaluator at `app/Modules/Simulator/Authorization/WindowsAuthorizationDecisionEngine.php`. It consumes validated arrays and an immutable revision context. It has no filesystem, process, shell, network, device, Windows API, repository, evidence, or learning dependency.

## Decision sequence

1. Validate trace context and the approved authority baseline.
2. Normalize and hash the complete input.
3. Reject missing/malformed principal, mask, group, or descriptor state as `INSUFFICIENT_STATE`.
4. Return `UNSUPPORTED_STATE` for privileges or an unsupported object/mapping/ACE type.
5. Map generic rights only for the declared approved FILE mapping.
6. Process DACL entries in stored order, recording every evaluated or skipped step.
7. Stop on a relevant explicit deny; otherwise accumulate relevant allows.
8. Emit an ordered trace, limitations, source claim IDs, remaining mask, outcome, and SHA-256 digest.

The digest excludes only the new run identifier so a reset/replay of the same scenario/rules/baseline/input/seed has the same decision digest. The stored trace retains the actual run identifier.

## Safety boundary

`ScenarioRun` receives revision identifiers and a baseline digest. It can only write run-owned rows. The application service re-reads the enterprise baseline and compares its digest before completing. An improvement action creates an `improvement_proposals` row; it never updates `enterprise_baseline_revisions`.

# Enterprise, Scenario, and Simulation Model

## Lab-path policy

The Institutional Simulator is the default Guided Lab path. Selective Real-Lab Validation is optional, claim-specific, and authorized only when a stated competence claim cannot be faithfully validated by the simulator. A Knowledge Unit does not acquire a Real-Lab requirement merely because a real environment is available, and simulated evidence is never relabeled as real evidence.

## Persistent enterprise catalogs

MOD-ENT owns versionable organizations, sites, networks, zones, assets, systems, services, applications, identities, accounts, groups, simulated roles, privileges, trusts, data, policies, controls, risks, threats, findings, vulnerabilities, detections, alerts, incidents, procedures, runbooks, evidence requirements, ownership, and responsibilities. They are reusable product records, not embedded only in scenarios.

## Baseline and run isolation

```text
Enterprise Baseline Revision
  -> Scenario Definition Revision
  -> isolated Scenario Run snapshot
  -> Run State + Transition Trace + Evidence
```

The baseline revision and scenario definition are immutable. A run stores their IDs/digests and copies required mutable state into a run namespace. No simulator repository/service receives baseline mutation capability. A discovered improvement creates `BaselineChangeProposal`; only the Enterprise publication workflow may create a new Baseline Revision.

## Scenario definition and package

Scenario Studio is the primary authoring path. It edits typed nodes/edges/forms for roles, identities, assets, events, injects, actions, commands, decisions, policies, controls, risks, findings, transitions, outputs/logs/alerts, evidence requirements, success/failure, remediation, verification, hints, and difficulty. Validation resolves every catalog/revision reference and tests reachability/conflicts.

A Scenario Package is a portable, versioned, manifested representation of the same model. Manual editing is optional advanced use; imports are untrusted and must pass schema/reference/digest validation before becoming a draft.

## Deterministic simulation contract

```text
Baseline snapshot + Run state + Device context + Simulated identity/permissions
+ Action/decision + Policy/control rules + Prior transition history + Engine/rule version
-> New run state + Output + Logs + Findings + Alerts + Evidence + Consequences + Explanation trace
```

Each action result is one of `APPLIED`, `DENIED_PERMISSION`, `INVALID_SYNTAX`, `INVALID_PARAMETER`, `UNSUPPORTED_STATE`, `INSUFFICIENT_STATE`, or `NO_STATE_CHANGE`, with stable reason codes. A transition records canonical pre/post-state digests, rule IDs/order, input, deterministic seed if needed, and generated artifact digests.

Reset restores the original run snapshot and records a reset epoch. Replay uses the same revisions, seed, and ordered inputs; a differing trace is a test failure. Hints are policy-controlled events and may affect assessment. Positive and negative tests, remediation, and verification are scenario-definition requirements.

## Safety and evidence

Commands are parsed by simulation grammars only; there is no shell/process/network execution port in v1. Output is visibly `SIMULATED`. Evidence includes run/revision/transition IDs, origin, rule version, state digests, and criteria. Real-Lab evidence is imported separately and can never be inferred from simulated state.

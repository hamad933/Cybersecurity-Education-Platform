# CEP Fast Control Plane (CEP-AUTO-001)

## Overview
The CEP Fast Control Plane is a fail-closed, deterministic control plane built to supervise, route, and enforce safety invariants across Jules Writer and Reviewer lineages for the Cybersecurity Education Platform.

**Target Integration Baseline**: `build/cep-v1-integration` @ `b5d53d2d44c570ebf112c50bec966da01835e5d9`

## Core Architecture & Principles
1. **Deterministic State Machine First**: AI/Jules is an executor/reviewer, not authority.
2. **Exact SHA Binding**: Every candidate PR and review packet is bound to an exact candidate head SHA. Stale review findings are strictly rejected.
3. **Fail-Closed Safety**: Unclassified input, cross-workstream collisions, baseline drift, or reviewer code mutation immediately escalate to `PARENT_REVIEW_PENDING` / `AUTHORITY_REQUIRED`.
4. **Task Budget Ledger**: Implements a hard 70-task project automation ceiling with a 15-task reserve buffer (warning threshold at 55).
5. **No Auto-Merge**: The fast control plane never merges PRs or accepts candidates. Parent Controller (ChatGPT) retains portfolio-level authority.

## Directory Structure
- `scripts/controller/models.py`: Enums (`WorkstreamState`, `JulesState`, `CIState`, `ReviewState`, `InputClassification`) and dataclasses.
- `scripts/controller/state_machine.py`: Deterministic transition engine and user feedback classifier.
- `scripts/controller/jules_adapter.py`: REST client targeting `https://jules.googleapis.com/v1alpha` with degraded GitHub-only fallback mode.
- `scripts/controller/task_budget.py`: Task budget ledger enforcing 70-task ceiling and 15-task reserve.
- `scripts/controller/idempotency.py`: SHA256 operation key engine and secret-sanitized execution receipt generator.
- `scripts/controller/safety_matcher.py`: W01–W05 workstream registry matcher and path scope violation checker.
- `scripts/controller/routing.py`: Review packet router enforcing exact candidate SHA matching and read-only reviewer isolation.
- `scripts/controller/main.py`: CLI entrypoint.

## CLI Options & Usage
```bash
python3 scripts/controller/main.py --dry-run
python3 scripts/controller/main.py --event-file payload.json --receipt-out /tmp/receipt.json --observed-tasks 12
```

## Prerequisites for Activation
- Activation on GitHub Actions (`.github/workflows/cep-jules-controller.yml`) is scheduled for ~every 10 minutes (`17,47 * * * *`).
- Candidate PRs must target `build/cep-v1-integration` as Draft PRs.
- `JULES_API_KEY` repository secret must be configured for API communication.

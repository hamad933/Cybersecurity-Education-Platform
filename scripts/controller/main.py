"""
CEP Fast Control Plane - Main CLI Entrypoint.
Standard library only (Python 3.10+).

CLI Usage:
  python3 scripts/controller/main.py [--dry-run] [--event-file PATH] [--receipt-out PATH] [--observed-tasks COUNT]
"""

import argparse
import json
import os
import sys
from typing import Any, Dict, Optional

# Ensure repository root is on sys.path
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
REPO_ROOT = os.path.dirname(os.path.dirname(SCRIPT_DIR))
if REPO_ROOT not in sys.path:
    sys.path.insert(0, REPO_ROOT)

from scripts.controller.idempotency import (
    IdempotencyEngine,
    compute_operation_key,
    generate_execution_receipt,
)
from scripts.controller.jules_adapter import JulesAdapter, JulesAdapterError
from scripts.controller.models import (
    CIState,
    CandidatePR,
    ControlCycleResult,
    JulesSessionInfo,
    JulesState,
    ReviewPacket,
    ReviewState,
    WorkstreamState,
)
from scripts.controller.routing import ReviewPacketRouter
from scripts.controller.safety_matcher import SafetyMatcher
from scripts.controller.state_machine import evaluate_control_cycle
from scripts.controller.task_budget import TaskBudgetLedger


def parse_args():
    parser = argparse.ArgumentParser(description="CEP Fast Control Plane CLI Engine")
    parser.add_argument("--dry-run", action="store_true", help="Perform evaluation without sending messages or creating sessions")
    parser.add_argument("--event-file", type=str, help="Path to GitHub event JSON file or simulation event context")
    parser.add_argument("--receipt-out", type=str, help="Path where execution receipt JSON will be written")
    parser.add_argument("--observed-tasks", type=int, default=None, help="Observed starting Jules task count")
    return parser.parse_args()


def main():
    args = parse_args()
    dry_run = args.dry_run

    print(f"[CEP Control Plane] Initializing fast control cycle (dry-run={dry_run})...")

    # Initialize components
    jules_adapter = JulesAdapter()
    budget_ledger = TaskBudgetLedger(observed_used_count=args.observed_tasks)
    safety_matcher = SafetyMatcher()
    router = ReviewPacketRouter()
    idempotency = IdempotencyEngine()

    if jules_adapter.is_degraded_mode:
        print("[CEP Control Plane] Operating in degraded GitHub-only mode (JULES_API_KEY omitted or empty).")

    # Check budget state
    budget_status = budget_ledger.check_new_task_allowed()
    budget_info = {
        "hard_ceiling": budget_status.hard_ceiling,
        "observed_used": budget_status.observed_used_count,
        "warning_threshold": budget_status.warning_threshold,
        "can_create_new_task": budget_status.can_create_new_task,
        "reason": budget_status.reason,
    }

    # Simulation / Run context defaults
    candidate_pr: Optional[CandidatePR] = None
    jules_session: Optional[JulesSessionInfo] = None
    latest_review: Optional[ReviewPacket] = None
    ci_state = CIState.NO_RUN

    if args.event_file and os.path.exists(args.event_file):
        try:
            with open(args.event_file, "r", encoding="utf-8") as f:
                event_data = json.load(f)
                # Parse PR if present in event payload
                pr_dict = event_data.get("pull_request")
                if pr_dict:
                    candidate_pr = CandidatePR(
                        number=pr_dict.get("number", 1),
                        title=pr_dict.get("title", ""),
                        workstream_id=event_data.get("workstream_id", "W01"),
                        branch=pr_dict.get("head", {}).get("ref", "work/cep-w01-feature"),
                        head_sha=pr_dict.get("head", {}).get("sha", "b5d53d2d44c570ebf112c50bec966da01835e5d9"),
                        base_branch=pr_dict.get("base", {}).get("ref", "build/cep-v1-integration"),
                        base_sha=pr_dict.get("base", {}).get("sha", "b5d53d2d44c570ebf112c50bec966da01835e5d9"),
                        is_draft=pr_dict.get("draft", True),
                        is_merged=pr_dict.get("merged", False),
                        is_closed=pr_dict.get("state") == "closed",
                        changed_files=event_data.get("changed_files", []),
                    )
        except Exception as e:
            print(f"[CEP Control Plane] Warning: Could not parse event file '{args.event_file}': {e}")

    # Evaluate Safety Matcher if candidate PR exists
    if candidate_pr:
        violations = safety_matcher.evaluate_pr_safety(candidate_pr)
        if violations:
            v_msgs = [f"[{v.violation_code}] {v.message}" for v in violations]
            print(f"[CEP Control Plane] Safety Matcher Violations: {'; '.join(v_msgs)}")
            cycle_result = ControlCycleResult(
                workstream_state=WorkstreamState.AUTHORITY_REQUIRED,
                jules_state=jules_session.state if jules_session else JulesState.UNKNOWN_JULES_STATE,
                ci_state=ci_state,
                review_state=latest_review.verdict if latest_review else ReviewState.NOT_REQUESTED,
                action_taken="ESCALATE_TO_PARENT",
                escalation_reason=f"Safety Violations: {'; '.join(v_msgs)}",
            )
        else:
            cycle_result = evaluate_control_cycle(candidate_pr, jules_session, ci_state, latest_review)
    else:
        cycle_result = evaluate_control_cycle(None, jules_session, ci_state, latest_review)

    # Calculate operation key
    op_key = compute_operation_key(
        repo=candidate_pr.repo if candidate_pr else "hamad933/Cybersecurity-Education-Platform",
        workstream_id=candidate_pr.workstream_id if candidate_pr else "GLOBAL",
        role="controller",
        pr_number=candidate_pr.number if candidate_pr else 0,
        exact_head_sha=candidate_pr.head_sha if candidate_pr else "HEAD",
        input_digest="cycle_init",
        action_type=cycle_result.action_taken,
    )

    print(f"[CEP Control Plane] Decision: {cycle_result.action_taken} | WorkstreamState: {cycle_result.workstream_state.value}")

    # Output execution receipt if requested
    receipt = generate_execution_receipt(cycle_result, candidate_pr, op_key, budget_info)
    if args.receipt_out:
        os.makedirs(os.path.dirname(os.path.abspath(args.receipt_out)), exist_ok=True)
        with open(args.receipt_out, "w", encoding="utf-8") as f:
            json.dump(receipt, f, indent=2)
        print(f"[CEP Control Plane] Execution receipt written to {args.receipt_out}")

    print("[CEP Control Plane] Control cycle completed successfully.")
    return 0


if __name__ == "__main__":
    sys.exit(main())

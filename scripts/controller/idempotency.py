"""
CEP Fast Control Plane - Idempotency Engine & Compact Receipt Generation.
Standard library only (Python 3.10+).

Operation Key format:
sha256(repo + ":" + workstream_id + ":" + role + ":" + pr_number + ":" + exact_head_sha + ":" + input_digest + ":" + action_type)
"""

import hashlib
import json
import time
from typing import Any, Dict, Optional, Set
from scripts.controller.models import ControlCycleResult, CandidatePR


def compute_operation_key(
    repo: str,
    workstream_id: str,
    role: str,
    pr_number: Optional[int],
    exact_head_sha: str,
    input_digest: str,
    action_type: str,
) -> str:
    raw_key = f"{repo}:{workstream_id}:{role}:{pr_number or 0}:{exact_head_sha}:{input_digest}:{action_type}"
    return hashlib.sha256(raw_key.encode("utf-8")).hexdigest()


class IdempotencyEngine:
    def __init__(self):
        self._executed_operation_keys: Set[str] = set()

    def record_operation(self, op_key: str):
        self._executed_operation_keys.add(op_key)

    def is_duplicate(self, op_key: str) -> bool:
        return op_key in self._executed_operation_keys


def sanitize_receipt_data(data: Dict[str, Any]) -> Dict[str, Any]:
    """
    Sanitize receipt data to prevent secret leakage or inclusion of raw code/artifacts.
    """
    sanitized = {}
    forbidden_keys = {"x-goog-api-key", "api_key", "secret", "token", "password", "credential"}

    for k, v in data.items():
        if any(f in k.lower() for f in forbidden_keys):
            sanitized[k] = "[REDACTED_SECRET]"
        elif isinstance(v, dict):
            sanitized[k] = sanitize_receipt_data(v)
        elif isinstance(v, list):
            sanitized[k] = [
                sanitize_receipt_data(item) if isinstance(item, dict) else item
                for item in v
            ]
        else:
            sanitized[k] = v
    return sanitized


def generate_execution_receipt(
    cycle_result: ControlCycleResult,
    pr: Optional[CandidatePR],
    op_key: str,
    budget_info: Dict[str, Any],
    source_timestamp: Optional[str] = None,
) -> Dict[str, Any]:
    receipt = {
        "timestamp": source_timestamp or str(int(time.time())),
        "repo": pr.repo if pr else "hamad933/Cybersecurity-Education-Platform",
        "workstream_id": pr.workstream_id if pr else "UNKNOWN",
        "pr_binding": {
            "number": pr.number if pr else None,
            "branch": pr.branch if pr else None,
            "head_sha": pr.head_sha if pr else None,
            "base_branch": pr.base_branch if pr else None,
            "base_sha": pr.base_sha if pr else None,
            "is_draft": pr.is_draft if pr else None,
        },
        "states": {
            "workstream_state": cycle_result.workstream_state.value,
            "jules_state": cycle_result.jules_state.value,
            "ci_state": cycle_result.ci_state.value,
            "review_state": cycle_result.review_state.value,
            "input_classification": cycle_result.input_classification.value if cycle_result.input_classification else None,
        },
        "action_selected": cycle_result.action_taken,
        "operation_key": op_key,
        "budget_state": budget_info,
        "escalation_reason": cycle_result.escalation_reason,
    }

    return sanitize_receipt_data(receipt)

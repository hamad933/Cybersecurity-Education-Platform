"""One-shot Parent-authorized W04 Jules waiting-input canary.

This script never creates a Jules session. It sends one deterministic continuation only
when the Owner-bound W04 session is still AWAITING_USER_FEEDBACK and the latest Jules
question matches the known Handoff Receipt testing question. Receipts contain hashes and
safe metadata only; no credential or raw question/message text is persisted.
"""

import argparse
import hashlib
import json
import os
import time
from pathlib import Path
from typing import Any

from scripts.controller.jules_adapter import JulesAdapter, JulesAdapterError


SESSION_ID = "3785206839989471108"
REPO = "hamad933/Cybersecurity-Education-Platform"
WORKSTREAM = "W04"
ROLE = "writer"
PR_NUMBER = 30
EXPECTED_REMOTE_HEAD = "2fa90272fd2c8be030220fd185ec5565b0d89822"
EXPECTED_QUESTION_MARKERS = (
    "handoff receipt id",
    "progressEvidenceService".casefold(),
    "testing infrastructure",
)

ANSWER = """Continue the SAME W04 task; this waiting question is Controller-resolved. Do not create a new task, branch, or PR.

Yes: use the real trusted Handoff Receipt application boundary in the tests. EvidenceIntakeService::receive() requires handoff_receipt_id, loads evidence_source_handoff_receipts, and validates that the receipt belongs to the subject actor. IntakeCandidateData::fromArray() then derives source_type, source_id, source_revision, source_digest, selected material references, capability, facts, and metadata from that verified receipt. Therefore the candidate payload passed to receive() should contain handoff_receipt_id plus candidate-owned fields; it should not duplicate source_type/source_id as if those were caller-authoritative.

Structure the tests with a small helper that builds a synthetic source handoff, calls ProgressEvidenceService::registerSourceHandoffReceipt($ownerId, $ownerId, $handoff), and merges the returned receipt id into the candidate payload used by EvidenceIntakeService::receive(). Keep subject/submitted/registered actor IDs aligned with the actual test actor boundary. Reuse that helper to avoid boilerplate, but exercise the public application boundary rather than seeding evidence_source_handoff_receipts directly, except in a test whose explicit purpose is receipt-persistence internals.

Preserve the existing W04 correction scope. Do not modify tests/Architecture/ModuleBoundaryTest.php or self-authorize the shared raw-table allowlist change. Continue the remaining W04-owned semantic/formatting corrections, run all compatible local validations, push one normal non-force correction commit to the SAME branch/PR #30, and let hosted CI validate the new exact head. Stop only at CEP-W04-R03_CANDIDATE_READY_FOR_CONTROLLER_PRIMARY_REVIEW unless a genuine shared-contract/scope/authority blocker is reached."""


def digest(text: str) -> str:
    return hashlib.sha256(text.encode("utf-8")).hexdigest()


def latest_agent_message(activities: list[dict[str, Any]]) -> str | None:
    candidates: list[tuple[str, str]] = []
    for activity in activities:
        event = activity.get("agentMessaged")
        if isinstance(event, dict) and isinstance(event.get("agentMessage"), str):
            candidates.append((str(activity.get("createTime", "")), event["agentMessage"]))
    if not candidates:
        return None
    candidates.sort(key=lambda item: item[0])
    return candidates[-1][1]


def message_already_observed(activities: list[dict[str, Any]]) -> bool:
    expected = digest(ANSWER)
    for activity in activities:
        event = activity.get("userMessaged")
        if isinstance(event, dict) and isinstance(event.get("userMessage"), str):
            if digest(event["userMessage"]) == expected:
                return True
    return False


def write_receipt(path: str, receipt: dict[str, Any]) -> None:
    target = Path(path)
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(json.dumps(receipt, indent=2, sort_keys=True), encoding="utf-8")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--receipt-out", required=True)
    args = parser.parse_args()

    adapter = JulesAdapter()
    answer_digest = digest(ANSWER)
    receipt: dict[str, Any] = {
        "repo": REPO,
        "workstream": WORKSTREAM,
        "role": ROLE,
        "session_id": SESSION_ID,
        "pr": PR_NUMBER,
        "expected_remote_head": EXPECTED_REMOTE_HEAD,
        "action_type": "W04_WAITING_INPUT_SAME_SESSION_CONTINUATION",
        "answer_digest": answer_digest,
        "send_attempted": False,
        "secret_persisted": False,
    }

    if adapter.is_degraded_mode:
        receipt.update({"outcome": "JULES_API_SECRET_MISSING"})
        write_receipt(args.receipt_out, receipt)
        return 2

    try:
        session = adapter.get_session(SESSION_ID)
        activities = adapter.list_activities(SESSION_ID)
    except JulesAdapterError as exc:
        receipt.update({
            "outcome": exc.classification,
            "http_status": exc.status_code,
        })
        write_receipt(args.receipt_out, receipt)
        return 2

    before_state = session.state.value
    receipt["before_state"] = before_state

    if message_already_observed(activities):
        receipt.update({
            "outcome": "ALREADY_DELIVERED_NO_DUPLICATE",
            "delivery_verified": True,
        })
        write_receipt(args.receipt_out, receipt)
        return 0

    if before_state != "AWAITING_USER_FEEDBACK":
        receipt.update({
            "outcome": "NO_ACTION_SESSION_NOT_WAITING",
            "delivery_verified": False,
        })
        write_receipt(args.receipt_out, receipt)
        return 0

    question = latest_agent_message(activities)
    if not question:
        receipt.update({"outcome": "WAITING_QUESTION_NOT_FOUND_FAIL_CLOSED"})
        write_receipt(args.receipt_out, receipt)
        return 3

    normalized_question = question.casefold()
    question_digest = digest(question)
    receipt["question_digest"] = question_digest
    receipt["operation_key"] = digest(
        "|".join([
            REPO,
            WORKSTREAM,
            ROLE,
            SESSION_ID,
            str(PR_NUMBER),
            EXPECTED_REMOTE_HEAD,
            question_digest,
            "sendMessage",
        ])
    )

    if not all(marker in normalized_question for marker in EXPECTED_QUESTION_MARKERS):
        receipt.update({"outcome": "WAITING_QUESTION_MISMATCH_FAIL_CLOSED"})
        write_receipt(args.receipt_out, receipt)
        return 3

    receipt["send_attempted"] = True
    try:
        adapter.send_message(SESSION_ID, ANSWER)
    except JulesAdapterError as exc:
        receipt["send_error_classification"] = exc.classification
        receipt["send_http_status"] = exc.status_code
        try:
            post_activities = adapter.list_activities(SESSION_ID)
        except JulesAdapterError:
            post_activities = []
        if message_already_observed(post_activities):
            receipt.update({
                "outcome": "AMBIGUOUS_WRITE_CONFIRMED_DELIVERED",
                "delivery_verified": True,
            })
            write_receipt(args.receipt_out, receipt)
            return 0
        receipt.update({
            "outcome": "WRITE_OUTCOME_UNKNOWN_NO_RETRY",
            "delivery_verified": False,
        })
        write_receipt(args.receipt_out, receipt)
        return 2

    final_state = before_state
    delivered = False
    for _ in range(5):
        time.sleep(2)
        try:
            post_session = adapter.get_session(SESSION_ID)
            post_activities = adapter.list_activities(SESSION_ID)
        except JulesAdapterError:
            continue
        final_state = post_session.state.value
        if message_already_observed(post_activities):
            delivered = True
            break

    receipt["after_state"] = final_state
    receipt["delivery_verified"] = delivered
    receipt["outcome"] = "MESSAGE_DELIVERED" if delivered else "WRITE_OUTCOME_UNKNOWN_NO_RETRY"
    write_receipt(args.receipt_out, receipt)
    return 0 if delivered else 2


if __name__ == "__main__":
    raise SystemExit(main())

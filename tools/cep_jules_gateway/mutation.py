from __future__ import annotations

from typing import Any

from .effect import intent_identity
from .envelope import RequestEnvelope
from .github import GitHubClient
from .jules import JulesClient
from .models import ErrorClassification, GatewayError, ProviderOutcome
from .plan_identity import plan_identity_from_activities
from .sanitize import sanitize_obj, sanitize_text

APPROVAL_STATE = "AWAITING_PLAN_APPROVAL"
SEND_COMPATIBLE_STATES = {"IN_PROGRESS", "AWAITING_USER_FEEDBACK", "AWAITING_PLAN_APPROVAL"}
TERMINAL_STATES = {"COMPLETED", "FAILED", "CANCELLED", "CANCELED", "ABORTED"}


def _source_present(jules: JulesClient, source_name: str) -> bool:
    return any(str(source.get("name") or "") == source_name for source in jules.list_sources())


def _require_state(session: dict[str, Any], envelope: RequestEnvelope) -> str:
    state = str(session.get("state") or "")
    if not state:
        raise GatewayError(ErrorClassification.PROVIDER_PROTOCOL_FAILED, "session state is missing from authoritative pre-read")
    if envelope.expected_state is not None and state != envelope.expected_state:
        raise GatewayError(
            ErrorClassification.INVALID_STATE,
            "provider state drifted from expected_state",
            details={"expected_state": envelope.expected_state, "actual_state": state},
        )
    return state


def _activity_snapshot(jules: JulesClient, envelope: RequestEnvelope) -> dict[str, Any]:
    result = jules.list_activities(
        envelope.session_id or "",
        page_size=envelope.options.page_size,
        max_pages=envelope.options.max_activity_pages,
        max_items=envelope.options.max_total_items,
    )
    return {
        "activities": result.items,
        "pagination": result.info.to_dict(),
        "complete": bool(result.info.complete),
        "names": sorted(str(item.get("name") or "") for item in result.items),
    }


def _approval_preconditions(envelope: RequestEnvelope, jules: JulesClient, session: dict[str, Any]) -> dict[str, Any]:
    state = _require_state(session, envelope)
    if state != APPROVAL_STATE or envelope.expected_state != APPROVAL_STATE:
        raise GatewayError(
            ErrorClassification.INVALID_STATE,
            "approve_plan is permitted only from AWAITING_PLAN_APPROVAL with an exact expected-state guard",
            details={"actual_state": state, "expected_state": envelope.expected_state},
        )
    update_time = str(session.get("updateTime") or "")
    if update_time != str(envelope.expected_session_update_time or ""):
        raise GatewayError(
            ErrorClassification.PLAN_CHANGED_SINCE_REVIEW,
            "session provider update identity changed since plan review",
            details={
                "expected_session_update_time": envelope.expected_session_update_time,
                "actual_session_update_time": update_time,
            },
        )

    snapshot = _activity_snapshot(jules, envelope)
    if not snapshot["complete"]:
        raise GatewayError(
            ErrorClassification.READ_BUDGET_EXCEEDED,
            "cannot reconstruct the exact latest plan from a partial activity collection",
            details={"pagination": snapshot["pagination"]},
        )
    plan = plan_identity_from_activities(snapshot["activities"], envelope.session_id or "")
    if plan.get("status") != ProviderOutcome.FOUND.value:
        raise GatewayError(ErrorClassification.INVALID_STATE, "no provider plan is available for approval")

    mismatches: dict[str, Any] = {}
    if plan.get("provider_identity_digest") != envelope.expected_plan_digest:
        mismatches["provider_identity_digest"] = plan.get("provider_identity_digest")
    if plan.get("activity_name") != envelope.expected_plan_activity_name:
        mismatches["activity_name"] = plan.get("activity_name")
    if str(plan.get("create_time") or "") != str(envelope.expected_plan_create_time or ""):
        mismatches["create_time"] = plan.get("create_time")
    if mismatches:
        raise GatewayError(
            ErrorClassification.PLAN_CHANGED_SINCE_REVIEW,
            "reviewed Jules plan identity no longer matches the authoritative provider plan",
            details={
                "expected_plan_digest": envelope.expected_plan_digest,
                "expected_plan_activity_name": envelope.expected_plan_activity_name,
                "expected_plan_create_time": envelope.expected_plan_create_time,
                "actual": mismatches,
            },
        )

    # Confirm that collecting activities did not span a provider session update.
    confirm = jules.get_session(envelope.session_id or "")
    confirm_state = _require_state(confirm, envelope)
    confirm_update = str(confirm.get("updateTime") or "")
    if confirm_state != state or confirm_update != update_time:
        raise GatewayError(
            ErrorClassification.PLAN_CHANGED_SINCE_REVIEW,
            "session identity changed while reconstructing the reviewed plan",
            details={
                "before_state": state,
                "after_state": confirm_state,
                "before_update_time": update_time,
                "after_update_time": confirm_update,
            },
        )

    return {
        "pre_state": state,
        "session_id": envelope.session_id,
        "session_update_time": confirm_update,
        "plan_provider_identity_digest": plan.get("provider_identity_digest"),
        "plan_display_digest": plan.get("plan_digest"),
        "plan_activity_name": plan.get("activity_name"),
        "plan_create_time": plan.get("create_time"),
        "activity_names": snapshot["names"],
        "activity_pagination": snapshot["pagination"],
    }


def preflight_mutation(
    envelope: RequestEnvelope,
    jules: JulesClient,
    github: GitHubClient,
    *,
    source_name: str,
) -> dict[str, Any]:
    if not envelope.is_mutation:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "preflight_mutation requires a v2.1 mutating action")

    if envelope.action == "create_session":
        if not _source_present(jules, source_name):
            raise GatewayError(ErrorClassification.INVALID_STATE, "configured Jules repository source is unavailable")
        # Keep branch/SHA verification as the final provider read in this preflight.
        github_precondition = github.require_branch_head(envelope.starting_branch or "", envelope.expected_sha or "")
        preconditions = {
            "pre_state": "PRE_SESSION",
            "starting_branch": envelope.starting_branch,
            "expected_sha": envelope.expected_sha,
            "actual_sha": github_precondition["actual_sha"],
            "source_name": source_name,
            "source_present": True,
        }
    elif envelope.action == "send_message":
        session = jules.get_session(envelope.session_id or "")
        state = _require_state(session, envelope)
        if state not in SEND_COMPATIBLE_STATES:
            raise GatewayError(
                ErrorClassification.INVALID_STATE,
                "send_message is not permitted in the current Jules session state",
                details={"state": state, "allowed_states": sorted(SEND_COMPATIBLE_STATES)},
            )
        update_time = str(session.get("updateTime") or "")
        if envelope.expected_session_update_time is not None and update_time != envelope.expected_session_update_time:
            raise GatewayError(ErrorClassification.INVALID_STATE, "session update identity drifted before send_message")
        preconditions = {
            "pre_state": state,
            "session_id": envelope.session_id,
            "session_update_time": update_time,
        }
    elif envelope.action == "approve_plan":
        session = jules.get_session(envelope.session_id or "")
        preconditions = _approval_preconditions(envelope, jules, session)
    else:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "unsupported mutating action")

    return sanitize_obj(
        {
            "schema_version": "cep.jules.gateway.intent/v2",
            "request": envelope.public_dict(),
            "intent_identity": intent_identity(envelope),
            "preconditions": preconditions,
            "blind_retry": False,
            "authority_event_is_reference_only": True,
        }
    )


def _same_preconditions(recorded: dict[str, Any], current: dict[str, Any]) -> None:
    if recorded != current:
        raise GatewayError(
            ErrorClassification.INVALID_STATE,
            "authoritative provider preconditions changed after durable intent was recorded",
            details={"recorded": recorded, "current": current},
        )


def _next_read(envelope: RequestEnvelope) -> dict[str, Any]:
    if envelope.action == "create_session":
        return {"action": "list_sessions", "reason": "reconcile created session identity before any replay"}
    return {"action": "inspect_bundle", "session_id": envelope.session_id, "reason": "reconcile authoritative session/activity state before any replay"}


def _receipt_base(envelope: RequestEnvelope, intent: dict[str, Any]) -> dict[str, Any]:
    return {
        "schema_version": "cep.jules.gateway.mutation_receipt/v2",
        "request_id": envelope.request_id,
        "logical_task_id": envelope.logical_task_id,
        "controller_id": envelope.controller_id,
        "lane": envelope.lane,
        "session_id": envelope.session_id,
        "action": envelope.action,
        "pre_state": (intent.get("preconditions") or {}).get("pre_state"),
        "expected_state": envelope.expected_state,
        "plan_digest": envelope.expected_plan_digest if envelope.action == "approve_plan" else None,
        "plan_activity_name": envelope.expected_plan_activity_name if envelope.action == "approve_plan" else None,
        "intent_identity": intent.get("intent_identity"),
        "blind_retry": False,
        "public_safe": True,
        "shadow_mode": "MUTATION_CANARY",
    }


def _unknown_receipt(envelope: RequestEnvelope, intent: dict[str, Any], exc: GatewayError, *, post_state: Any = None) -> dict[str, Any]:
    receipt = _receipt_base(envelope, intent)
    receipt.update(
        {
            "provider_result_class": exc.classification.value,
            "provider_http_status": exc.http_status,
            "post_state": post_state,
            "verification": ProviderOutcome.UNKNOWN.value,
            "idempotency_final_state": "UNKNOWN_WRITE_OUTCOME",
            "next_safe_read": _next_read(envelope),
            "error": {
                "classification": exc.classification.value,
                "message": sanitize_text(exc.message),
                "details": sanitize_obj(exc.details),
            },
        }
    )
    return sanitize_obj(receipt)


def _rejected_receipt(envelope: RequestEnvelope, intent: dict[str, Any], exc: GatewayError) -> dict[str, Any]:
    receipt = _receipt_base(envelope, intent)
    receipt.update(
        {
            "provider_result_class": exc.classification.value,
            "provider_http_status": exc.http_status,
            "post_state": None,
            "verification": ProviderOutcome.REJECTED.value,
            "idempotency_final_state": "COMPLETED",
            "next_safe_read": None,
            "error": {
                "classification": exc.classification.value,
                "message": sanitize_text(exc.message),
                "details": sanitize_obj(exc.details),
            },
        }
    )
    return sanitize_obj(receipt)


def execute_mutation(
    envelope: RequestEnvelope,
    intent: dict[str, Any],
    jules: JulesClient,
    github: GitHubClient,
    *,
    source_name: str,
) -> dict[str, Any]:
    if intent.get("intent_identity") != intent_identity(envelope):
        raise GatewayError(ErrorClassification.IDEMPOTENCY_CONFLICT, "intent identity does not match the supplied request envelope")

    # This is the final authoritative pre-read after durable request/effect intent.
    try:
        current_intent = preflight_mutation(envelope, jules, github, source_name=source_name)
        _same_preconditions(intent.get("preconditions") or {}, current_intent.get("preconditions") or {})
    except GatewayError as exc:
        return _rejected_receipt(envelope, intent, exc)

    current_pre = current_intent.get("preconditions") or {}
    try:
        if envelope.action == "create_session":
            created = jules.create_session(
                {
                    "prompt": envelope.prompt,
                    "title": envelope.title,
                    "sourceContext": {"source": source_name, "githubRepoContext": {"startingBranch": envelope.starting_branch}},
                    "requirePlanApproval": True,
                }
            )
            session_id = str(created.get("id") or "")
            try:
                post = jules.get_session(session_id)
            except GatewayError as exc:
                return _unknown_receipt(
                    envelope,
                    intent,
                    GatewayError(
                        ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN,
                        "create_session write returned but authoritative post-read failed",
                        http_status=exc.http_status,
                        details={"post_read_classification": exc.classification.value, "blind_retry": False},
                    ),
                )
            receipt = _receipt_base(envelope, intent)
            receipt.update(
                {
                    "session_id": session_id,
                    "provider_result_class": "CREATE_SESSION_ACCEPTED_AND_POST_READ_VERIFIED",
                    "post_state": post.get("state"),
                    "provider_update_time": post.get("updateTime"),
                    "verification": ProviderOutcome.VERIFIED.value,
                    "idempotency_final_state": "COMPLETED",
                    "next_safe_read": None,
                }
            )
            return sanitize_obj(receipt)

        if envelope.action == "approve_plan":
            jules.approve_plan(envelope.session_id or "")
        elif envelope.action == "send_message":
            jules.send_message(envelope.session_id or "", envelope.prompt or "")
        else:
            return _rejected_receipt(envelope, intent, GatewayError(ErrorClassification.INVALID_REQUEST, "unsupported mutation action"))
    except GatewayError as exc:
        if exc.classification == ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN:
            return _unknown_receipt(envelope, intent, exc)
        return _rejected_receipt(envelope, intent, exc)

    try:
        post_session = jules.get_session(envelope.session_id or "")
        post_state = str(post_session.get("state") or "")
        post_update = str(post_session.get("updateTime") or "")
        new_activity = False
        if envelope.action == "approve_plan":
            try:
                post_snapshot = _activity_snapshot(jules, envelope)
                if post_snapshot["complete"]:
                    new_activity = bool(set(post_snapshot["names"]) - set(current_pre.get("activity_names") or []))
            except GatewayError:
                new_activity = False
    except GatewayError as exc:
        return _unknown_receipt(
            envelope,
            intent,
            GatewayError(
                ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN,
                "provider write returned but authoritative post-read failed",
                http_status=exc.http_status,
                details={"post_read_classification": exc.classification.value, "blind_retry": False},
            ),
        )

    pre_update = str(current_pre.get("session_update_time") or "")
    verified = (
        post_state != APPROVAL_STATE or post_update != pre_update or new_activity
        if envelope.action == "approve_plan"
        else post_update != pre_update
    )
    if not verified:
        return _unknown_receipt(
            envelope,
            intent,
            GatewayError(
                ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN,
                "provider write returned but the immediate authoritative post-read is inconclusive",
                details={"blind_retry": False},
            ),
            post_state=post_state,
        )

    receipt = _receipt_base(envelope, intent)
    receipt.update(
        {
            "provider_result_class": "MUTATION_ACCEPTED_AND_POST_READ_VERIFIED",
            "post_state": post_state,
            "provider_update_time": post_session.get("updateTime"),
            "new_activity_observed": new_activity,
            "verification": ProviderOutcome.VERIFIED.value,
            "idempotency_final_state": "COMPLETED",
            "next_safe_read": None,
        }
    )
    return sanitize_obj(receipt)

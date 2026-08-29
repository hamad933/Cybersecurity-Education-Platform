from __future__ import annotations

from datetime import UTC, datetime
from typing import Any

from .digest import sha256_text
from .models import ErrorClassification, GatewayError, ProviderOutcome
from .plan_identity import plan_identity_from_activities
from .sanitize import sanitize_obj, sanitize_text
from .v22_contract import EnvelopeV22
from .v22_state import (
    DurableState,
    effect_key,
    intent_identity,
    request_marker,
    target_intent_identity,
    target_request_state,
)

APPROVAL_STATE = "AWAITING_PLAN_APPROVAL"
SEND_COMPATIBLE_STATES = {"IN_PROGRESS", "AWAITING_USER_FEEDBACK", "AWAITING_PLAN_APPROVAL"}


def bridge_title(env: EnvelopeV22) -> str:
    return f"CEP:{env.logical_task_id}:{effect_key(env)[-12:]}"


def bridge_message(env: EnvelopeV22, *, target: bool = False) -> str:
    if env.original_action not in {"create_session", "send_message"}:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "reference bridge is not applicable to this action")
    request_id = env.target_request_id if target else env.request_id
    lines = [
        "CEP_JULES_GATEWAY_V22_REFERENCE",
        f"request_id={request_id}",
        f"logical_task_id={env.logical_task_id}",
        f"write_domain={env.write_domain}",
        f"controller_id={env.controller_id}",
        f"lane={env.lane}",
        f"instruction_action={env.instruction_action}",
        f"instruction_ref={env.instruction_ref}",
        f"instruction_sha256={env.instruction_digest}",
        f"effect_identity={effect_key(env)}",
    ]
    if env.authority_event:
        lines.append(f"authority_event={env.authority_event}")
    if env.authority_ref:
        lines.append(f"authority_ref={env.authority_ref}")
    lines.extend(
        [
            "Retrieve the exact referenced governed instruction through the established CEP authority/source path.",
            "Verify its SHA-256 when exact bytes are available before execution.",
            "If inaccessible or mismatched, stop without widening scope and report the blocker.",
            "This public-safe bridge is routing metadata only and never expands authority.",
        ]
    )
    return "\n".join(lines)


def bridge_digest(env: EnvelopeV22, *, target: bool = False) -> str:
    return sha256_text(bridge_message(env, target=target))


def _source_present(jules: Any, source_name: str) -> bool:
    return any(str(source.get("name") or "") == source_name for source in jules.list_sources())


def _session_state(session: dict[str, Any], env: EnvelopeV22) -> str:
    state = str(session.get("state") or "")
    if not state:
        raise GatewayError(ErrorClassification.PROVIDER_PROTOCOL_FAILED, "session state is missing")
    if env.expected_state is not None and state != env.expected_state:
        raise GatewayError(
            ErrorClassification.INVALID_STATE,
            "provider state drifted from expected_state",
            details={"expected_state": env.expected_state, "actual_state": state},
        )
    return state


def _activities(jules: Any, env: EnvelopeV22) -> dict[str, Any]:
    result = jules.list_activities(env.session_id or "", page_size=100, max_pages=20, max_items=2000)
    if not bool(result.info.complete):
        raise GatewayError(
            ErrorClassification.READ_BUDGET_EXCEEDED,
            "exact mutation evidence requires a complete bounded activity collection",
            details={"pagination": result.info.to_dict()},
        )
    return {
        "items": result.items,
        "names": sorted(str(item.get("name") or "") for item in result.items),
        "pagination": result.info.to_dict(),
    }


def _approve_preconditions(env: EnvelopeV22, jules: Any, session: dict[str, Any]) -> dict[str, Any]:
    state = _session_state(session, env)
    if state != APPROVAL_STATE or env.expected_state != APPROVAL_STATE:
        raise GatewayError(ErrorClassification.INVALID_STATE, "approve_plan requires exact AWAITING_PLAN_APPROVAL state")
    update = str(session.get("updateTime") or "")
    if update != str(env.expected_session_update_time or ""):
        raise GatewayError(ErrorClassification.PLAN_CHANGED_SINCE_REVIEW, "session update identity changed since plan review")
    snapshot = _activities(jules, env)
    plan = plan_identity_from_activities(snapshot["items"], env.session_id or "")
    if plan.get("status") != ProviderOutcome.FOUND.value or not plan.get("plan_id"):
        raise GatewayError(ErrorClassification.INVALID_STATE, "no stable provider plan is available for approval")
    mismatches = {}
    if plan.get("provider_identity_digest") != env.expected_plan_digest:
        mismatches["provider_identity_digest"] = plan.get("provider_identity_digest")
    if str(plan.get("plan_id") or "") != str(env.expected_plan_id or ""):
        mismatches["plan_id"] = plan.get("plan_id")
    if plan.get("activity_name") != env.expected_plan_activity_name:
        mismatches["activity_name"] = plan.get("activity_name")
    if str(plan.get("create_time") or "") != str(env.expected_plan_create_time or ""):
        mismatches["create_time"] = plan.get("create_time")
    if mismatches:
        raise GatewayError(
            ErrorClassification.PLAN_CHANGED_SINCE_REVIEW,
            "reviewed plan identity no longer matches provider",
            details={"actual": mismatches},
        )
    confirm = jules.get_session(env.session_id or "")
    if _session_state(confirm, env) != state or str(confirm.get("updateTime") or "") != update:
        raise GatewayError(ErrorClassification.PLAN_CHANGED_SINCE_REVIEW, "session changed while reconstructing reviewed plan")
    return {
        "pre_state": state,
        "session_update_time": update,
        "plan_id": plan.get("plan_id"),
        "plan_provider_identity_digest": plan.get("provider_identity_digest"),
        "plan_activity_name": plan.get("activity_name"),
        "plan_create_time": plan.get("create_time"),
        "activity_names": snapshot["names"],
    }


def _send_preconditions(env: EnvelopeV22, jules: Any, session: dict[str, Any]) -> dict[str, Any]:
    state = _session_state(session, env)
    if state not in SEND_COMPATIBLE_STATES:
        raise GatewayError(ErrorClassification.INVALID_STATE, "send_message is not permitted in current session state")
    update = str(session.get("updateTime") or "")
    if update != str(env.expected_session_update_time or ""):
        raise GatewayError(ErrorClassification.INVALID_STATE, "session update identity changed before send_message")
    snapshot = _activities(jules, env)
    confirm = jules.get_session(env.session_id or "")
    if _session_state(confirm, env) != state or str(confirm.get("updateTime") or "") != update:
        raise GatewayError(ErrorClassification.INVALID_STATE, "session changed while collecting pre-send activities")
    return {
        "pre_state": state,
        "session_update_time": update,
        "activity_names": snapshot["names"],
        "message_identity_digest": bridge_digest(env),
    }


def preflight(env: EnvelopeV22, jules: Any, github: Any, *, source_name: str) -> dict[str, Any]:
    if not env.is_mutation:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "preflight requires a mutation")
    if env.action == "create_session":
        if not _source_present(jules, source_name):
            raise GatewayError(ErrorClassification.INVALID_STATE, "configured Jules source is unavailable")
        branch = github.require_branch_head(env.starting_branch or "", env.expected_sha or "")
        pre = {
            "pre_state": "PRE_SESSION",
            "starting_branch": env.starting_branch,
            "expected_sha": env.expected_sha,
            "actual_sha": branch["actual_sha"],
            "source_name": source_name,
            "bridge_title": bridge_title(env),
            "bridge_message_digest": bridge_digest(env),
        }
    elif env.action == "send_message":
        pre = _send_preconditions(env, jules, jules.get_session(env.session_id or ""))
    elif env.action == "approve_plan":
        pre = _approve_preconditions(env, jules, jules.get_session(env.session_id or ""))
    else:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "unsupported mutation")
    return sanitize_obj(
        {
            "schema_version": "cep.jules.gateway.intent/v2.2",
            "request": env.public_dict(),
            "intent_identity": intent_identity(env),
            "preconditions": pre,
            "blind_retry": False,
            "instruction_transport": "REFERENCE_ONLY" if env.action in {"create_session", "send_message"} else None,
        }
    )


def _base_receipt(env: EnvelopeV22, intent: dict[str, Any]) -> dict[str, Any]:
    return {
        "schema_version": "cep.jules.gateway.mutation_receipt/v2.2",
        "request_id": env.request_id,
        "logical_task_id": env.logical_task_id,
        "controller_id": env.controller_id,
        "lane": env.lane,
        "action": env.action,
        "session_id": env.session_id,
        "intent_identity": intent.get("intent_identity"),
        "instruction_ref": env.instruction_ref,
        "instruction_digest": env.instruction_digest,
        "blind_retry": False,
        "public_safe": True,
        "shadow_mode": "MUTATION_CANARY",
    }


def _unknown(env: EnvelopeV22, intent: dict[str, Any], message: str, *, post_state: str | None = None) -> dict[str, Any]:
    result = _base_receipt(env, intent)
    result.update(
        {
            "provider_result_class": ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN.value,
            "post_state": post_state,
            "verification": ProviderOutcome.UNKNOWN.value,
            "idempotency_final_state": DurableState.UNKNOWN_WRITE_OUTCOME.value,
            "effect_resolution": "UNKNOWN",
            "provider_mutation_attempted": True,
            "next_safe_read": {
                "action": "reconcile_" + env.action,
                "session_id": env.session_id,
                "target_request_id": env.request_id,
                "target_intent_identity": intent.get("intent_identity"),
            },
            "error": {"message": sanitize_text(message)},
        }
    )
    return sanitize_obj(result)


def _rejected(env: EnvelopeV22, intent: dict[str, Any], exc: GatewayError, *, attempted: bool = False) -> dict[str, Any]:
    result = _base_receipt(env, intent)
    result.update(
        {
            "provider_result_class": exc.classification.value,
            "provider_http_status": exc.http_status,
            "verification": ProviderOutcome.REJECTED.value,
            "idempotency_final_state": DurableState.COMPLETED.value,
            "effect_resolution": "NOT_APPLIED",
            "provider_mutation_attempted": attempted,
            "error": {"message": sanitize_text(exc.message), "details": sanitize_obj(exc.details)},
        }
    )
    return sanitize_obj(result)


def _new_user_message(items: list[dict[str, Any]], pre_names: set[str], digest: str) -> str | None:
    matches = []
    for activity in items:
        name = str(activity.get("name") or "")
        event = activity.get("userMessaged")
        if name in pre_names or not isinstance(event, dict):
            continue
        message = event.get("userMessage")
        if isinstance(message, str) and sha256_text(message) == digest:
            matches.append(name)
    return matches[0] if len(matches) == 1 else None


def _new_plan_approval(items: list[dict[str, Any]], pre_names: set[str], plan_id: str) -> str | None:
    matches = []
    for activity in items:
        name = str(activity.get("name") or "")
        event = activity.get("planApproved")
        if name in pre_names or not isinstance(event, dict):
            continue
        if str(event.get("planId") or "") == plan_id:
            matches.append(name)
    return matches[0] if len(matches) == 1 else None


def _verify_created_session(session: dict[str, Any], env: EnvelopeV22, source_name: str) -> dict[str, Any]:
    if str(session.get("title") or "") != bridge_title(env):
        raise GatewayError(ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN, "created session title correlation failed")
    prompt = session.get("prompt")
    if not isinstance(prompt, str) or sha256_text(prompt) != bridge_digest(env):
        raise GatewayError(ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN, "created session prompt correlation failed")
    source_verified = None
    branch_verified = None
    context = session.get("sourceContext")
    if isinstance(context, dict):
        if context.get("source") is not None:
            source_verified = str(context.get("source")) == source_name
            if not source_verified:
                raise GatewayError(ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN, "created session source mismatch")
        repo = context.get("githubRepoContext")
        if isinstance(repo, dict) and repo.get("startingBranch") is not None:
            branch_verified = str(repo.get("startingBranch")) == str(env.starting_branch)
            if not branch_verified:
                raise GatewayError(ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN, "created session branch mismatch")
    return {
        "correlation_title_verified": True,
        "correlation_prompt_digest_verified": True,
        "source_identity_verified_when_exposed": source_verified,
        "starting_branch_verified_when_exposed": branch_verified,
    }


def execute(env: EnvelopeV22, intent: dict[str, Any], jules: Any, github: Any, *, source_name: str) -> dict[str, Any]:
    if intent.get("intent_identity") != intent_identity(env):
        raise GatewayError(ErrorClassification.IDEMPOTENCY_CONFLICT, "intent identity does not match request")
    try:
        fresh = preflight(env, jules, github, source_name=source_name)
        if fresh.get("preconditions") != intent.get("preconditions"):
            raise GatewayError(ErrorClassification.INVALID_STATE, "authoritative preconditions changed after durable intent")
    except GatewayError as exc:
        return _rejected(env, intent, exc)

    pre = fresh.get("preconditions") or {}
    try:
        if env.action == "create_session":
            created = jules.create_session(
                {
                    "prompt": bridge_message(env),
                    "title": bridge_title(env),
                    "sourceContext": {"source": source_name, "githubRepoContext": {"startingBranch": env.starting_branch}},
                    "requirePlanApproval": True,
                }
            )
            sid = str(created.get("id") or "")
            post = jules.get_session(sid)
            correlation = _verify_created_session(post, env, source_name)
            result = _base_receipt(env, intent)
            result.update(
                {
                    "session_id": sid,
                    "post_state": post.get("state"),
                    "verification": ProviderOutcome.VERIFIED.value,
                    "idempotency_final_state": DurableState.COMPLETED.value,
                    "effect_resolution": "APPLIED",
                    "provider_mutation_attempted": True,
                    **correlation,
                }
            )
            return sanitize_obj(result)
        if env.action == "send_message":
            jules.send_message(env.session_id or "", bridge_message(env))
        elif env.action == "approve_plan":
            jules.approve_plan(env.session_id or "")
        else:
            raise GatewayError(ErrorClassification.INVALID_REQUEST, "unsupported mutation")
    except GatewayError as exc:
        if exc.classification == ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN:
            return _unknown(env, intent, exc.message)
        return _rejected(env, intent, exc, attempted=True)

    try:
        post = jules.get_session(env.session_id or "")
        snapshot = _activities(jules, env)
        if env.action == "send_message":
            activity = _new_user_message(snapshot["items"], set(pre.get("activity_names") or []), str(pre.get("message_identity_digest") or ""))
        else:
            activity = _new_plan_approval(snapshot["items"], set(pre.get("activity_names") or []), str(pre.get("plan_id") or ""))
    except GatewayError:
        return _unknown(env, intent, "provider write returned but exact authoritative post-read failed")
    if not activity:
        return _unknown(env, intent, "provider write returned but exact activity evidence is inconclusive", post_state=str(post.get("state") or ""))

    result = _base_receipt(env, intent)
    result.update(
        {
            "post_state": post.get("state"),
            "verification": ProviderOutcome.VERIFIED.value,
            "idempotency_final_state": DurableState.COMPLETED.value,
            "effect_resolution": "APPLIED",
            "provider_mutation_attempted": True,
            "matching_activity": activity,
        }
    )
    return sanitize_obj(result)


def _parse_time(value: Any) -> datetime | None:
    if not isinstance(value, str) or not value:
        return None
    try:
        result = datetime.fromisoformat(value.replace("Z", "+00:00"))
    except ValueError:
        return None
    return (result if result.tzinfo else result.replace(tzinfo=UTC)).astimezone(UTC)


def _target_marker_age(github: Any, env: EnvelopeV22) -> float | None:
    rows = []
    for state in (DurableState.UNKNOWN_WRITE_OUTCOME, DurableState.INTENT_RECORDED):
        rows.extend(github.list_active_artifacts_by_name(request_marker(env, state, target=True)))
    times = [_parse_time(row.get("created_at")) for row in rows]
    times = [value for value in times if value is not None]
    if not times:
        return None
    return max(0.0, (datetime.now(UTC) - max(times)).total_seconds())


def _reconcile_result(env: EnvelopeV22, state: DurableState, details: dict[str, Any], *, persist: bool) -> dict[str, Any]:
    return sanitize_obj(
        {
            "schema_version": "cep.jules.gateway.reconciliation_receipt/v2.2",
            "request_id": env.request_id,
            "target_request_id": env.target_request_id,
            "target_intent_identity": target_intent_identity(env),
            "controller_id": env.controller_id,
            "lane": env.lane,
            "logical_task_id": env.logical_task_id,
            "write_domain": env.write_domain,
            "action": env.action,
            "session_id": env.session_id,
            "reconciliation_state": state.value,
            "persist_resolution": persist,
            "provider_mutation_performed": False,
            "blind_retry": False,
            "details": details,
        }
    )


def reconcile(env: EnvelopeV22, jules: Any, github: Any, *, source_name: str) -> dict[str, Any]:
    if not env.is_reconciliation:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "reconcile requires reconciliation envelope")
    target_intent_identity(env)
    state = target_request_state(github, env)
    if state == DurableState.RECONCILED_APPLIED.value:
        return _reconcile_result(env, DurableState.RECONCILED_APPLIED, {"idempotent_replay": True}, persist=False)
    if state == DurableState.RECONCILED_NOT_APPLIED.value:
        return _reconcile_result(env, DurableState.RECONCILED_NOT_APPLIED, {"idempotent_replay": True}, persist=False)
    if state == DurableState.COMPLETED.value:
        raise GatewayError(ErrorClassification.IDEMPOTENCY_CONFLICT, "target request is already terminal")
    if state not in {DurableState.UNKNOWN_WRITE_OUTCOME.value, DurableState.INTENT_RECORDED.value}:
        raise GatewayError(ErrorClassification.NOT_FOUND, "no unresolved durable target request exists")

    if env.action == "reconcile_create_session":
        result = jules.list_sessions(page_size=100, max_pages=20, max_items=2000)
        if not bool(result.info.complete):
            return _reconcile_result(env, DurableState.RECONCILIATION_INCONCLUSIVE, {"reason": "PARTIAL_SESSION_COLLECTION"}, persist=False)
        expected_title = bridge_title(env)
        expected_prompt = bridge_digest(env, target=True)
        matches = []
        for session in result.items:
            if str(session.get("title") or "") != expected_title:
                continue
            prompt = session.get("prompt")
            if not isinstance(prompt, str) or sha256_text(prompt) != expected_prompt:
                continue
            context = session.get("sourceContext")
            if isinstance(context, dict):
                if context.get("source") is not None and str(context.get("source")) != source_name:
                    continue
                repo = context.get("githubRepoContext")
                if isinstance(repo, dict) and repo.get("startingBranch") is not None and str(repo.get("startingBranch")) != str(env.starting_branch):
                    continue
            matches.append(session)
        if len(matches) == 1:
            return _reconcile_result(
                env,
                DurableState.RECONCILED_APPLIED,
                {"session_id": str(matches[0].get("id") or ""), "bind_session": True},
                persist=True,
            )
        if len(matches) > 1:
            return _reconcile_result(env, DurableState.RECONCILIATION_INCONCLUSIVE, {"reason": "MULTIPLE_CORRELATED_SESSIONS"}, persist=False)
        age = _target_marker_age(github, env)
        if age is not None and age >= env.min_reconcile_age_seconds:
            return _reconcile_result(
                env,
                DurableState.RECONCILED_NOT_APPLIED,
                {
                    "reason": "COMPLETE_SETTLED_SESSION_COLLECTION_HAS_NO_CORRELATED_SESSION",
                    "marker_age_seconds": age,
                    "provider_consistency_guarantee": "NOT_DOCUMENTED; residual eventual-consistency risk remains",
                },
                persist=True,
            )
        return _reconcile_result(env, DurableState.RECONCILIATION_INCONCLUSIVE, {"reason": "SETTLE_WINDOW_NOT_REACHED", "marker_age_seconds": age}, persist=False)

    if env.action == "reconcile_send_message":
        session = jules.get_session(env.session_id or "")
        snapshot = _activities(jules, env)
        matches = []
        expected = bridge_digest(env, target=True)
        for activity in snapshot["items"]:
            event = activity.get("userMessaged")
            if isinstance(event, dict) and isinstance(event.get("userMessage"), str) and sha256_text(event["userMessage"]) == expected:
                matches.append(str(activity.get("name") or ""))
        if len(matches) == 1:
            return _reconcile_result(env, DurableState.RECONCILED_APPLIED, {"matching_user_messaged_activity": matches[0]}, persist=True)
        if len(matches) > 1:
            return _reconcile_result(env, DurableState.RECONCILIATION_INCONCLUSIVE, {"reason": "MULTIPLE_EXACT_MESSAGES"}, persist=False)
        age = _target_marker_age(github, env)
        if age is not None and age >= env.min_reconcile_age_seconds and str(session.get("updateTime") or "") == str(env.expected_session_update_time or ""):
            return _reconcile_result(env, DurableState.RECONCILED_NOT_APPLIED, {"reason": "NO_MESSAGE_AND_SESSION_IDENTITY_UNCHANGED", "marker_age_seconds": age}, persist=True)
        return _reconcile_result(env, DurableState.RECONCILIATION_INCONCLUSIVE, {"reason": "NO_EXACT_MESSAGE_PROOF"}, persist=False)

    snapshot = _activities(jules, env)
    matches = []
    for activity in snapshot["items"]:
        event = activity.get("planApproved")
        if isinstance(event, dict) and str(event.get("planId") or "") == str(env.expected_plan_id or ""):
            matches.append(str(activity.get("name") or ""))
    if len(matches) == 1:
        return _reconcile_result(env, DurableState.RECONCILED_APPLIED, {"matching_plan_approved_activity": matches[0]}, persist=True)
    return _reconcile_result(env, DurableState.RECONCILIATION_INCONCLUSIVE, {"reason": "NO_EXACT_PLAN_APPROVAL_PROOF"}, persist=False)

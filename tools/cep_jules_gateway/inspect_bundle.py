from __future__ import annotations

import inspect
from typing import Any, Protocol

from .budget import ExactTextBudget, HydrationPool
from .digest import sha256_text
from .envelope import MUTATION_SCHEMA_VERSION, RequestEnvelope
from .models import Completeness, ErrorClassification, GatewayError, ProviderOutcome
from .plan_identity import plan_identity_from_activities
from .sanitize import sanitize_obj, sanitize_text
from .selection import deterministic_activity_order, latest_unique_activity, provider_time, validate_activity_identity


class InspectClient(Protocol):
    observations: list[Any]

    def get_session(self, session_id: str) -> dict[str, Any]: ...

    def list_activities(self, session_id: str, *, page_size: int, max_pages: int, max_items: int = 2_000) -> Any: ...

    def get_activity(self, activity_name: str) -> dict[str, Any]: ...


def _activity_name(activity: dict[str, Any], session_id: str) -> str:
    return validate_activity_identity(activity, session_id)


def _list_activities(envelope: RequestEnvelope, client: InspectClient) -> Any:
    parameters = inspect.signature(client.list_activities).parameters
    if "max_items" in parameters:
        return client.list_activities(
            envelope.session_id or "",
            page_size=envelope.options.page_size,
            max_pages=envelope.options.max_activity_pages,
            max_items=envelope.options.max_total_items,
        )
    result = client.list_activities(
        envelope.session_id or "",
        page_size=envelope.options.page_size,
        max_pages=envelope.options.max_activity_pages,
    )
    if len(result.items) > envelope.options.max_total_items:
        raise GatewayError(
            ErrorClassification.READ_BUDGET_EXCEEDED,
            "activity result exceeds total item bound",
            details={"items": len(result.items), "max_total_items": envelope.options.max_total_items},
        )
    return result


def _recent_rows(
    activities: list[dict[str, Any]],
    session_id: str,
    *,
    field: str,
    limit: int,
) -> list[dict[str, Any]]:
    candidates = [activity for activity in activities if isinstance(activity.get(field), dict)]
    ordered = deterministic_activity_order(candidates, session_id)
    if limit <= 0:
        return []
    if len(ordered) > limit:
        before = ordered[-limit - 1]
        first_selected = ordered[-limit]
        if provider_time(before) == provider_time(first_selected):
            raise GatewayError(
                ErrorClassification.INVALID_STATE,
                "recent activity selection is ambiguous at the cutoff timestamp",
                details={"selector": field, "timestamp": provider_time(first_selected)},
            )
    return ordered[-limit:]


def _agent_messages(activities: list[dict[str, Any]], session_id: str, limit: int) -> dict[str, Any]:
    all_rows: list[dict[str, Any]] = []
    for activity in deterministic_activity_order(activities, session_id):
        message_obj = activity.get("agentMessaged")
        if not isinstance(message_obj, dict):
            continue
        message = message_obj.get("agentMessage")
        if message:
            all_rows.append(
                {
                    "activity_name": _activity_name(activity, session_id),
                    "create_time": activity.get("createTime"),
                    "message": sanitize_text(message),
                }
            )
    selected_activities = _recent_rows(activities, session_id, field="agentMessaged", limit=limit)
    selected_names = {_activity_name(activity, session_id) for activity in selected_activities}
    selected = [row for row in all_rows if row["activity_name"] in selected_names]
    return {
        "status": ProviderOutcome.FOUND.value if selected else ProviderOutcome.NOT_FOUND.value,
        "total_message_count": len(all_rows),
        "returned_message_count": len(selected),
        "messages": selected,
    }


def _changeset_markers(activities: list[dict[str, Any]], session_id: str) -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []
    for activity in deterministic_activity_order(activities, session_id):
        for artifact_index, artifact in enumerate(activity.get("artifacts") or [], start=1):
            if not isinstance(artifact, dict):
                continue
            change_set = artifact.get("changeSet")
            if not isinstance(change_set, dict):
                continue
            git_patch = change_set.get("gitPatch") if isinstance(change_set.get("gitPatch"), dict) else {}
            patch = sanitize_text(git_patch.get("unidiffPatch"))
            rows.append(
                {
                    "activity_name": _activity_name(activity, session_id),
                    "create_time": activity.get("createTime"),
                    "artifact_index": artifact_index,
                    "source": sanitize_text(change_set.get("source")),
                    "base_commit_id": str(git_patch.get("baseCommitId") or ""),
                    "suggested_commit_message": sanitize_text(git_patch.get("suggestedCommitMessage")),
                    "list_patch_available": bool(patch),
                    "list_patch_chars": len(patch),
                    "list_patch_digest": sha256_text(patch) if patch else None,
                }
            )
    return rows


def _latest_patch(
    pool: HydrationPool,
    activities: list[dict[str, Any]],
    *,
    include_patch: bool,
    max_exact_text_chars: int,
    text_budget: ExactTextBudget,
    errors: list[dict[str, Any]],
) -> dict[str, Any]:
    candidates = [
        activity
        for activity in activities
        if any(isinstance(a, dict) and isinstance(a.get("changeSet"), dict) for a in (activity.get("artifacts") or []))
    ]
    selected = latest_unique_activity(candidates, pool.session_id, selector="changeset")
    if selected is None:
        return {"status": ProviderOutcome.NOT_FOUND.value}
    name = _activity_name(selected, pool.session_id)
    try:
        full = pool.get(name)
    except GatewayError as exc:
        errors.append(_error_record("latest_changeset_patch", exc, activity_name=name))
        return {
            "status": ProviderOutcome.NOT_AVAILABLE_FROM_PROVIDER.value,
            "reason": exc.classification.value,
            "activity_name": name,
        }

    for artifact_index, artifact in enumerate(full.get("artifacts") or [], start=1):
        if not isinstance(artifact, dict):
            continue
        change_set = artifact.get("changeSet")
        if not isinstance(change_set, dict):
            continue
        git_patch = change_set.get("gitPatch") if isinstance(change_set.get("gitPatch"), dict) else {}
        patch = sanitize_text(git_patch.get("unidiffPatch"))
        if not patch:
            continue
        result: dict[str, Any] = {
            "status": ProviderOutcome.FOUND.value,
            "activity_name": str(full.get("name") or name),
            "create_time": full.get("createTime") or selected.get("createTime"),
            "artifact_index": artifact_index,
            "source": sanitize_text(change_set.get("source")),
            "base_commit_id": str(git_patch.get("baseCommitId") or ""),
            "suggested_commit_message": sanitize_text(git_patch.get("suggestedCommitMessage")),
            "patch_chars": len(patch),
            "patch_digest": sha256_text(patch),
            "patch_truncated": False,
        }
        if include_patch:
            if len(patch) > max_exact_text_chars:
                result["text_omitted_reason"] = "ITEM_TEXT_BOUND_EXCEEDED"
            elif not text_budget.include(patch):
                result["text_omitted_reason"] = ErrorClassification.OUTPUT_BUDGET_EXCEEDED.value
            else:
                result["unidiff_patch"] = patch
        return result
    return {
        "status": ProviderOutcome.NOT_AVAILABLE_FROM_PROVIDER.value,
        "reason": "PATCH_NOT_AVAILABLE_AFTER_ACTIVITY_HYDRATION",
        "activity_name": name,
    }


def _bash_markers(activities: list[dict[str, Any]], session_id: str) -> tuple[list[dict[str, Any]], list[dict[str, Any]]]:
    index: list[dict[str, Any]] = []
    candidates: list[dict[str, Any]] = []
    for activity in deterministic_activity_order(activities, session_id):
        had_bash = False
        for artifact_index, artifact in enumerate(activity.get("artifacts") or [], start=1):
            if not isinstance(artifact, dict):
                continue
            bash = artifact.get("bashOutput")
            if not isinstance(bash, dict):
                continue
            had_bash = True
            output = sanitize_text(bash.get("output"))
            index.append(
                {
                    "activity_name": _activity_name(activity, session_id),
                    "create_time": activity.get("createTime"),
                    "artifact_index": artifact_index,
                    "command": sanitize_text(bash.get("command")),
                    "exit_code": bash.get("exitCode"),
                    "list_output_available": bool(output),
                    "list_output_chars": len(output),
                    "list_output_digest": sha256_text(output) if output else None,
                }
            )
        if had_bash:
            candidates.append(activity)
    return index, candidates


def _hydrate_bash(
    pool: HydrationPool,
    candidates: list[dict[str, Any]],
    *,
    recent_limit: int,
    max_exact_text_chars: int,
    include_text: bool,
    text_budget: ExactTextBudget,
    errors: list[dict[str, Any]],
) -> tuple[list[dict[str, Any]], bool]:
    if recent_limit == 0 or not candidates:
        return [], True
    ordered = deterministic_activity_order(candidates, pool.session_id)
    rows: list[dict[str, Any]] = []
    complete = True
    for activity in reversed(ordered):
        if len(rows) >= recent_limit:
            break
        name = _activity_name(activity, pool.session_id)
        try:
            full = pool.get(name)
        except GatewayError as exc:
            errors.append(_error_record("bash_output_hydration", exc, activity_name=name))
            complete = False
            if exc.classification == ErrorClassification.READ_BUDGET_EXCEEDED:
                break
            continue
        artifacts = full.get("artifacts") or []
        for artifact_index in range(len(artifacts), 0, -1):
            artifact = artifacts[artifact_index - 1]
            if not isinstance(artifact, dict):
                continue
            bash = artifact.get("bashOutput")
            if not isinstance(bash, dict):
                continue
            output = sanitize_text(bash.get("output"))
            row: dict[str, Any] = {
                "activity_name": str(full.get("name") or name),
                "create_time": full.get("createTime") or activity.get("createTime"),
                "artifact_index": artifact_index,
                "command": sanitize_text(bash.get("command")),
                "exit_code": bash.get("exitCode"),
                "output_chars": len(output),
                "output_digest": sha256_text(output),
                "output_truncated": False,
            }
            if include_text:
                if len(output) > max_exact_text_chars:
                    row["text_omitted_reason"] = "ITEM_TEXT_BOUND_EXCEEDED"
                    complete = False
                elif not text_budget.include(output):
                    row["text_omitted_reason"] = ErrorClassification.OUTPUT_BUDGET_EXCEEDED.value
                    complete = False
                else:
                    row["output"] = output
            rows.append(row)
            if len(rows) >= recent_limit:
                break
    rows.reverse()
    return rows, complete


def _error_record(stage: str, exc: GatewayError, **extra: Any) -> dict[str, Any]:
    result: dict[str, Any] = {
        "stage": stage,
        "classification": exc.classification.value,
        "http_status": exc.http_status,
        "message": sanitize_text(exc.message),
    }
    if exc.retry is not None:
        result["retry"] = sanitize_obj(exc.retry.__dict__)
    result.update(extra)
    return result


def build_inspect_bundle(envelope: RequestEnvelope, client: InspectClient) -> dict[str, Any]:
    if envelope.action != "inspect_bundle" or envelope.session_id is None:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "build_inspect_bundle requires inspect_bundle with session_id")

    options = envelope.options
    errors: list[dict[str, Any]] = []
    session = client.get_session(envelope.session_id)
    actual_session_id = str(session.get("id") or envelope.session_id)
    if actual_session_id != envelope.session_id:
        raise GatewayError(ErrorClassification.PROVIDER_PROTOCOL_FAILED, "session pre-read identity mismatch")

    actual_state = str(session.get("state") or "")
    if envelope.expected_state is not None and actual_state != envelope.expected_state:
        raise GatewayError(
            ErrorClassification.INVALID_STATE,
            "Jules session state does not match expected_state",
            details={"expected_state": envelope.expected_state, "actual_state": actual_state},
        )

    activities_result = _list_activities(envelope, client)
    activities = activities_result.items
    for activity in activities:
        validate_activity_identity(activity, envelope.session_id)

    plan = plan_identity_from_activities(activities, envelope.session_id)
    if envelope.expected_plan_digest is not None:
        digest_field = "provider_identity_digest" if envelope.schema_version == MUTATION_SCHEMA_VERSION else "plan_digest"
        actual_plan_digest = plan.get(digest_field)
        if actual_plan_digest != envelope.expected_plan_digest:
            raise GatewayError(
                ErrorClassification.INVALID_STATE,
                "latest Jules plan digest does not match expected_plan_digest",
                details={
                    "expected_plan_digest": envelope.expected_plan_digest,
                    "actual_plan_digest": actual_plan_digest,
                    "digest_field": digest_field,
                    "plan_status": plan.get("status"),
                },
            )

    agent_messages = _agent_messages(activities, envelope.session_id, options.recent_agent_messages)
    changeset_index = _changeset_markers(activities, envelope.session_id)
    text_budget = ExactTextBudget(options.max_total_exact_text_bytes)
    pool = HydrationPool(client, envelope.session_id, options.max_hydration_reads)

    latest_patch = _latest_patch(
        pool,
        activities,
        include_patch=options.include_patch,
        max_exact_text_chars=options.max_exact_text_chars,
        text_budget=text_budget,
        errors=errors,
    )
    bash_index, bash_candidates = _bash_markers(activities, envelope.session_id)
    bash_evidence, bash_hydration_complete = _hydrate_bash(
        pool,
        bash_candidates,
        recent_limit=options.recent_bash_outputs,
        max_exact_text_chars=options.max_exact_text_chars,
        include_text=options.include_bash_output_text,
        text_budget=text_budget,
        errors=errors,
    )

    pagination_complete = bool(getattr(activities_result.info, "complete", True))
    output_partial = text_budget.exhausted or bool(latest_patch.get("text_omitted_reason"))
    completeness = Completeness.PARTIAL if errors or not bash_hydration_complete or not pagination_complete or output_partial else Completeness.COMPLETE

    provider_reads = getattr(client, "provider_reads", None)
    provider_read_limit = getattr(client, "max_provider_reads", None)
    client_cache_hits = getattr(client, "hydration_cache_hits", None)
    bundle: dict[str, Any] = {
        "schema_version": "cep.jules.inspect_bundle/v2",
        "request": envelope.public_dict(),
        "session": {
            "status": ProviderOutcome.FOUND.value,
            "id": actual_session_id,
            "name": sanitize_text(session.get("name")),
            "title": sanitize_text(session.get("title")),
            "state": actual_state,
            "update_time": session.get("updateTime"),
            "url": sanitize_text(session.get("url")),
        },
        "plan": plan,
        "agent_messages": agent_messages,
        "changesets": {
            "status": ProviderOutcome.FOUND.value if changeset_index else ProviderOutcome.NOT_FOUND.value,
            "count": len(changeset_index),
            "index": changeset_index,
            "latest_exact_patch": latest_patch,
        },
        "bash_evidence": {
            "status": ProviderOutcome.FOUND.value if bash_index else ProviderOutcome.NOT_FOUND.value,
            "index_count": len(bash_index),
            "index": bash_index,
            "recent_exact_count": len(bash_evidence),
            "recent_exact": bash_evidence,
            "hydration_complete_for_selector": bash_hydration_complete,
        },
        "budgets": {
            "hydration_reads": pool.reads,
            "hydration_cache_hits": pool.cache_hits,
            "max_hydration_reads": pool.max_reads,
            "client_activity_cache_hits": client_cache_hits,
            "provider_reads": provider_reads,
            "max_provider_reads": provider_read_limit,
            "exact_text_bytes": text_budget.used_bytes,
            "max_total_exact_text_bytes": text_budget.limit_bytes,
            "output_omissions": text_budget.omissions,
        },
        "provider": {
            "outcome": ProviderOutcome.FOUND.value,
            "completeness": completeness.value,
            "activity_count_scanned": len(activities),
            "activity_pagination": activities_result.info.to_dict(),
            "observations": [o.to_dict() if hasattr(o, "to_dict") else sanitize_obj(o) for o in getattr(client, "observations", [])],
            "errors": errors,
        },
    }
    return sanitize_obj(bundle)

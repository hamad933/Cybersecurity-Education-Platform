from __future__ import annotations

from typing import Any, Protocol

from .digest import sha256_json, sha256_text
from .envelope import RequestEnvelope
from .models import Completeness, ErrorClassification, GatewayError, ProviderOutcome
from .sanitize import sanitize_obj, sanitize_text


class InspectClient(Protocol):
    observations: list[Any]

    def get_session(self, session_id: str) -> dict[str, Any]: ...

    def list_activities(self, session_id: str, *, page_size: int, max_pages: int) -> Any: ...

    def get_activity(self, activity_name: str) -> dict[str, Any]: ...


def _activity_name(activity: dict[str, Any]) -> str:
    return str(activity.get("name") or "")


def _plan_from_activities(activities: list[dict[str, Any]]) -> dict[str, Any]:
    found: tuple[dict[str, Any], dict[str, Any]] | None = None
    for activity in activities:
        generated = activity.get("planGenerated")
        if isinstance(generated, dict):
            found = (activity, generated)
    if found is None:
        return {"status": ProviderOutcome.NOT_FOUND.value, "plan_digest": None, "steps": []}
    activity, generated = found
    plan = generated.get("plan") if isinstance(generated.get("plan"), dict) else {}
    steps: list[dict[str, Any]] = []
    for index, step in enumerate(plan.get("steps") or [], start=1):
        if not isinstance(step, dict):
            continue
        steps.append(
            {
                "index": index,
                "title": sanitize_text(step.get("title"))[:2_000],
                "description": sanitize_text(step.get("description"))[:12_000],
            }
        )
    normalized = {"steps": steps}
    return {
        "status": ProviderOutcome.FOUND.value,
        "activity_name": _activity_name(activity),
        "create_time": activity.get("createTime"),
        "step_count": len(steps),
        "plan_digest": sha256_json(normalized),
        "steps": steps,
    }


def _agent_messages(activities: list[dict[str, Any]], limit: int) -> dict[str, Any]:
    rows: list[dict[str, Any]] = []
    for activity in activities:
        message_obj = activity.get("agentMessaged")
        if not isinstance(message_obj, dict):
            continue
        message = message_obj.get("agentMessage")
        if message:
            rows.append(
                {
                    "activity_name": _activity_name(activity),
                    "create_time": activity.get("createTime"),
                    "message": sanitize_text(message),
                }
            )
    selected = rows[-limit:] if limit else []
    return {
        "status": ProviderOutcome.FOUND.value if selected else ProviderOutcome.NOT_FOUND.value,
        "total_message_count": len(rows),
        "returned_message_count": len(selected),
        "messages": selected,
    }


def _changeset_markers(activities: list[dict[str, Any]]) -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []
    for activity in activities:
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
                    "activity_name": _activity_name(activity),
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
    client: InspectClient,
    activities: list[dict[str, Any]],
    *,
    include_patch: bool,
    max_hydration_reads: int,
    max_exact_text_chars: int,
    errors: list[dict[str, Any]],
) -> tuple[dict[str, Any], int]:
    candidate_activities = [
        activity
        for activity in activities
        if any(isinstance(a, dict) and isinstance(a.get("changeSet"), dict) for a in (activity.get("artifacts") or []))
    ]
    if not candidate_activities:
        return {"status": ProviderOutcome.NOT_FOUND.value}, 0

    reads = 0
    for activity in reversed(candidate_activities):
        if reads >= max_hydration_reads:
            return {
                "status": ProviderOutcome.NOT_AVAILABLE_FROM_PROVIDER.value,
                "reason": "HYDRATION_BOUND_REACHED",
                "candidate_activity_count": len(candidate_activities),
            }, reads
        name = _activity_name(activity)
        try:
            full = client.get_activity(name)
        except GatewayError as exc:
            reads += 1
            errors.append(_error_record("latest_changeset_patch", exc, activity_name=name))
            continue
        reads += 1
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
            truncated = len(patch) > max_exact_text_chars
            safe_patch = patch[:max_exact_text_chars]
            result = {
                "status": ProviderOutcome.FOUND.value,
                "activity_name": str(full.get("name") or name),
                "create_time": full.get("createTime") or activity.get("createTime"),
                "artifact_index": artifact_index,
                "source": sanitize_text(change_set.get("source")),
                "base_commit_id": str(git_patch.get("baseCommitId") or ""),
                "suggested_commit_message": sanitize_text(git_patch.get("suggestedCommitMessage")),
                "patch_chars": len(patch),
                "patch_digest": sha256_text(patch),
                "patch_truncated": truncated,
            }
            if include_patch:
                result["unidiff_patch"] = safe_patch
            return result, reads
    return {
        "status": ProviderOutcome.NOT_AVAILABLE_FROM_PROVIDER.value,
        "reason": "PATCH_NOT_AVAILABLE_AFTER_ACTIVITY_HYDRATION",
        "candidate_activity_count": len(candidate_activities),
    }, reads


def _bash_markers(activities: list[dict[str, Any]]) -> tuple[list[dict[str, Any]], list[dict[str, Any]]]:
    index: list[dict[str, Any]] = []
    candidates: list[dict[str, Any]] = []
    for activity in activities:
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
                    "activity_name": _activity_name(activity),
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
    client: InspectClient,
    candidates: list[dict[str, Any]],
    *,
    recent_limit: int,
    max_hydration_reads: int,
    max_exact_text_chars: int,
    include_text: bool,
    errors: list[dict[str, Any]],
) -> tuple[list[dict[str, Any]], int, bool]:
    if recent_limit == 0 or not candidates:
        return [], 0, True
    rows: list[dict[str, Any]] = []
    reads = 0
    bound_reached = False
    for activity in reversed(candidates):
        if len(rows) >= recent_limit:
            break
        if reads >= max_hydration_reads:
            bound_reached = True
            break
        name = _activity_name(activity)
        try:
            full = client.get_activity(name)
        except GatewayError as exc:
            reads += 1
            errors.append(_error_record("bash_output_hydration", exc, activity_name=name))
            continue
        reads += 1
        artifacts = full.get("artifacts") or []
        for artifact_index in range(len(artifacts), 0, -1):
            artifact = artifacts[artifact_index - 1]
            if not isinstance(artifact, dict):
                continue
            bash = artifact.get("bashOutput")
            if not isinstance(bash, dict):
                continue
            output = sanitize_text(bash.get("output"))
            truncated = len(output) > max_exact_text_chars
            row = {
                "activity_name": str(full.get("name") or name),
                "create_time": full.get("createTime") or activity.get("createTime"),
                "artifact_index": artifact_index,
                "command": sanitize_text(bash.get("command")),
                "exit_code": bash.get("exitCode"),
                "output_chars": len(output),
                "output_digest": sha256_text(output),
                "output_truncated": truncated,
            }
            if include_text:
                row["output"] = output[:max_exact_text_chars]
            rows.append(row)
            if len(rows) >= recent_limit:
                break
    rows.reverse()
    return rows, reads, not bound_reached


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

    try:
        session = client.get_session(envelope.session_id)
    except GatewayError:
        raise

    actual_state = str(session.get("state") or "")
    if envelope.expected_state is not None and actual_state != envelope.expected_state:
        raise GatewayError(
            ErrorClassification.INVALID_STATE,
            "Jules session state does not match expected_state",
            details={"expected_state": envelope.expected_state, "actual_state": actual_state},
        )

    activities_result = client.list_activities(
        envelope.session_id,
        page_size=options.page_size,
        max_pages=options.max_activity_pages,
    )
    activities = activities_result.items

    plan = _plan_from_activities(activities)
    if envelope.expected_plan_digest is not None:
        actual_plan_digest = plan.get("plan_digest")
        if actual_plan_digest != envelope.expected_plan_digest:
            raise GatewayError(
                ErrorClassification.INVALID_STATE,
                "latest Jules plan digest does not match expected_plan_digest",
                details={
                    "expected_plan_digest": envelope.expected_plan_digest,
                    "actual_plan_digest": actual_plan_digest,
                    "plan_status": plan.get("status"),
                },
            )
    agent_messages = _agent_messages(activities, options.recent_agent_messages)
    changeset_index = _changeset_markers(activities)
    latest_patch, patch_reads = _latest_patch(
        client,
        activities,
        include_patch=options.include_patch,
        max_hydration_reads=options.max_hydration_reads,
        max_exact_text_chars=options.max_exact_text_chars,
        errors=errors,
    )
    bash_index, bash_candidates = _bash_markers(activities)
    bash_evidence, bash_reads, bash_hydration_complete = _hydrate_bash(
        client,
        bash_candidates,
        recent_limit=options.recent_bash_outputs,
        max_hydration_reads=options.max_hydration_reads,
        max_exact_text_chars=options.max_exact_text_chars,
        include_text=options.include_bash_output_text,
        errors=errors,
    )

    patch_bound_partial = latest_patch.get("reason") == "HYDRATION_BOUND_REACHED"
    completeness = (
        Completeness.PARTIAL
        if errors or not bash_hydration_complete or patch_bound_partial
        else Completeness.COMPLETE
    )
    bundle: dict[str, Any] = {
        "schema_version": "cep.jules.inspect_bundle/v2",
        "request": envelope.public_dict(),
        "session": {
            "status": ProviderOutcome.FOUND.value,
            "id": str(session.get("id") or envelope.session_id),
            "name": sanitize_text(session.get("name")),
            "title": sanitize_text(session.get("title")),
            "state": str(session.get("state") or ""),
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
            "hydrated_activity_reads": patch_reads,
        },
        "bash_evidence": {
            "status": ProviderOutcome.FOUND.value if bash_index else ProviderOutcome.NOT_FOUND.value,
            "index_count": len(bash_index),
            "index": bash_index,
            "recent_exact_count": len(bash_evidence),
            "recent_exact": bash_evidence,
            "hydrated_activity_reads": bash_reads,
            "hydration_complete_for_selector": bash_hydration_complete,
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

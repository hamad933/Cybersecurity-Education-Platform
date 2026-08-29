from __future__ import annotations

from typing import Any

from .models import ErrorClassification, GatewayError


def validate_activity_identity(activity: dict[str, Any], session_id: str) -> str:
    name = str(activity.get("name") or "")
    prefix = f"sessions/{session_id}/activities/"
    suffix = name[len(prefix):] if name.startswith(prefix) else ""
    if not suffix or "/" in suffix:
        raise GatewayError(
            ErrorClassification.PROVIDER_PROTOCOL_FAILED,
            "activity identity is malformed or belongs to another session",
            details={"expected_session_id": session_id},
        )
    return name


def provider_time(activity: dict[str, Any]) -> str:
    value = activity.get("createTime") or activity.get("updateTime") or ""
    return str(value)


def deterministic_activity_order(activities: list[dict[str, Any]], session_id: str) -> list[dict[str, Any]]:
    rows: list[tuple[str, str, dict[str, Any]]] = []
    for activity in activities:
        name = validate_activity_identity(activity, session_id)
        rows.append((provider_time(activity), name, activity))
    rows.sort(key=lambda row: (row[0], row[1]))
    return [row[2] for row in rows]


def latest_unique_activity(
    activities: list[dict[str, Any]],
    session_id: str,
    *,
    selector: str,
) -> dict[str, Any] | None:
    if not activities:
        return None
    ordered = deterministic_activity_order(activities, session_id)
    latest_time = provider_time(ordered[-1])
    tied = [activity for activity in ordered if provider_time(activity) == latest_time]
    if len(tied) > 1:
        raise GatewayError(
            ErrorClassification.INVALID_STATE,
            "latest provider activity is ambiguous at the highest timestamp",
            details={
                "selector": selector,
                "timestamp": latest_time or None,
                "activity_names": [str(activity.get("name") or "") for activity in tied],
            },
        )
    return ordered[-1]

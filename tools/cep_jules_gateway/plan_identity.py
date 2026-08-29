from __future__ import annotations

from typing import Any

from .digest import sha256_json
from .models import ProviderOutcome
from .sanitize import sanitize_text
from .selection import latest_unique_activity, validate_activity_identity


def plan_identity_from_activities(activities: list[dict[str, Any]], session_id: str) -> dict[str, Any]:
    candidates = [activity for activity in activities if isinstance(activity.get("planGenerated"), dict)]
    activity = latest_unique_activity(candidates, session_id, selector="plan")
    if activity is None:
        return {
            "status": ProviderOutcome.NOT_FOUND.value,
            "plan_digest": None,
            "provider_identity_digest": None,
            "plan_id": None,
            "steps": [],
        }

    activity_name = validate_activity_identity(activity, session_id)
    generated = activity["planGenerated"]
    plan = generated.get("plan") if isinstance(generated.get("plan"), dict) else {}
    plan_id = str(plan.get("id") or "") or None
    raw_steps = plan.get("steps")
    if raw_steps is None:
        raw_steps = []
    if not isinstance(raw_steps, list):
        raw_steps = []

    steps: list[dict[str, Any]] = []
    for index, step in enumerate(raw_steps, start=1):
        if not isinstance(step, dict):
            continue
        steps.append(
            {
                "index": index,
                "title": sanitize_text(step.get("title"))[:2_000],
                "description": sanitize_text(step.get("description"))[:12_000],
            }
        )

    normalized_display = {"steps": steps}
    exact_provider_material = {
        "session_id": session_id,
        "activity_name": activity_name,
        "activity_create_time": activity.get("createTime"),
        "activity_update_time": activity.get("updateTime"),
        "plan_generated": generated,
    }
    return {
        "status": ProviderOutcome.FOUND.value,
        "activity_name": activity_name,
        "create_time": activity.get("createTime"),
        "update_time": activity.get("updateTime"),
        "plan_id": plan_id,
        "step_count": len(steps),
        "plan_digest": sha256_json(normalized_display),
        "provider_identity_digest": sha256_json(exact_provider_material),
        "steps": steps,
    }

from __future__ import annotations

from datetime import UTC, datetime
from typing import Any

from .envelope import RequestEnvelope
from .models import Completeness, ErrorClassification, GatewayError, ProviderOutcome
from .sanitize import sanitize_obj, sanitize_text


def _now() -> str:
    return datetime.now(UTC).isoformat().replace("+00:00", "Z")


def receipt_from_bundle(envelope: RequestEnvelope, bundle: dict[str, Any]) -> dict[str, Any]:
    provider = bundle.get("provider") or {}
    session = bundle.get("session") or {}
    plan = bundle.get("plan") or {}
    latest_patch = ((bundle.get("changesets") or {}).get("latest_exact_patch") or {})
    exact_bash = (bundle.get("bash_evidence") or {}).get("recent_exact") or []
    receipt = {
        "schema_version": "cep.jules.gateway.receipt/v2",
        "request_schema_version": envelope.schema_version,
        "request_id": envelope.request_id,
        "logical_task_id": envelope.logical_task_id,
        "controller_id": envelope.controller_id,
        "lane": envelope.lane,
        "action": envelope.action,
        "session_id": envelope.session_id,
        "provider_outcome": provider.get("outcome", ProviderOutcome.READ_FAILED.value),
        "completeness": provider.get("completeness", Completeness.READ_FAILED.value),
        "digests": {
            "plan_display": plan.get("plan_digest"),
            "plan_provider_identity": plan.get("provider_identity_digest"),
            "patch": latest_patch.get("patch_digest"),
            "bash_evidence": [row.get("output_digest") for row in exact_bash if row.get("output_digest")],
        },
        "provider_metadata": {
            "session_state": session.get("state"),
            "provider_update_time": session.get("update_time"),
            "activity_count_scanned": provider.get("activity_count_scanned"),
            "activity_pagination": provider.get("activity_pagination"),
            "budgets": bundle.get("budgets") or {},
            "errors": provider.get("errors") or [],
        },
        "generated_at": _now(),
        "public_safe": True,
        "shadow_safe": True,
        "provider_mutation_performed": False,
    }
    return sanitize_obj({k: v for k, v in receipt.items() if v is not None})


def error_receipt(envelope: RequestEnvelope | None, error: GatewayError) -> dict[str, Any]:
    if error.classification == ErrorClassification.RATE_LIMITED:
        provider_outcome = ProviderOutcome.RATE_LIMITED.value
    elif error.classification == ErrorClassification.NOT_FOUND:
        provider_outcome = ProviderOutcome.NOT_FOUND.value
    elif error.classification in {
        ErrorClassification.INVALID_REQUEST,
        ErrorClassification.INVALID_STATE,
        ErrorClassification.PLAN_CHANGED_SINCE_REVIEW,
        ErrorClassification.IDEMPOTENCY_CONFLICT,
        ErrorClassification.RECONCILIATION_REQUIRED,
    }:
        provider_outcome = ProviderOutcome.REJECTED.value
    else:
        provider_outcome = ProviderOutcome.READ_FAILED.value
    receipt: dict[str, Any] = {
        "schema_version": "cep.jules.gateway.receipt/v2",
        "request_schema_version": envelope.schema_version if envelope else None,
        "request_id": envelope.request_id if envelope else None,
        "logical_task_id": envelope.logical_task_id if envelope else None,
        "controller_id": envelope.controller_id if envelope else None,
        "lane": envelope.lane if envelope else None,
        "action": envelope.action if envelope else None,
        "session_id": envelope.session_id if envelope else None,
        "provider_outcome": provider_outcome,
        "completeness": Completeness.READ_FAILED.value,
        "error": {
            "classification": error.classification.value,
            "http_status": error.http_status,
            "message": sanitize_text(error.message),
            "details": sanitize_obj(error.details),
            "retry": sanitize_obj(error.retry.__dict__) if error.retry else None,
        },
        "generated_at": _now(),
        "public_safe": True,
        "shadow_safe": True,
        "provider_mutation_performed": False,
    }
    return sanitize_obj({k: v for k, v in receipt.items() if v is not None})

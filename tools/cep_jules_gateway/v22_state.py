from __future__ import annotations

from dataclasses import dataclass
from enum import StrEnum
from typing import Any

from .digest import sha256_json, sha256_text
from .models import ErrorClassification, GatewayError
from .v22_contract import EnvelopeV22


class DurableState(StrEnum):
    INTENT_RECORDED = "INTENT_RECORDED"
    COMPLETED = "COMPLETED"
    UNKNOWN_WRITE_OUTCOME = "UNKNOWN_WRITE_OUTCOME"
    RECONCILED_APPLIED = "RECONCILED_APPLIED"
    RECONCILED_NOT_APPLIED = "RECONCILED_NOT_APPLIED"
    RECONCILIATION_INCONCLUSIVE = "RECONCILIATION_INCONCLUSIVE"


def effect_key(env: EnvelopeV22) -> str:
    if env.session_id:
        material = {"type": "session", "session_id": env.session_id}
    else:
        material = {
            "type": "pre_session",
            "logical_task_id": env.logical_task_id,
            "write_domain": env.write_domain,
            "starting_branch": env.starting_branch,
        }
    return "effect-" + sha256_json(material)[:32]


def request_key(env: EnvelopeV22) -> str:
    identity = env.target_request_id if env.is_reconciliation else env.request_id
    return "req-" + sha256_text(identity or env.request_id)[:32]


def intent_identity_from_public(data: dict[str, Any], key: str) -> str:
    return sha256_json({"request": data, "effect_key": key})


def intent_identity(env: EnvelopeV22) -> str:
    if not env.is_mutation:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "intent identity requires a mutation")
    return intent_identity_from_public(env.public_dict(), effect_key(env))


def target_intent_identity(env: EnvelopeV22) -> str:
    if not env.is_reconciliation:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "target intent identity requires reconciliation")
    actual = intent_identity_from_public(env.target_public_dict(), effect_key(env))
    if actual != env.target_intent_identity:
        raise GatewayError(
            ErrorClassification.IDEMPOTENCY_CONFLICT,
            "reconciliation target identity does not reconstruct exactly",
            details={"target_request_id": env.target_request_id, "actual": actual},
        )
    return actual


def request_marker(env: EnvelopeV22, state: DurableState, *, target: bool = False) -> str:
    request_id = env.target_request_id if target else env.request_id
    identity = target_intent_identity(env) if target else intent_identity(env)
    prefix = sha256_json({"request_id": request_id, "intent_identity": identity})[:32]
    return f"cep-jules-v22-idem-{prefix}-{state.value}"


def create_attempt_prefix(env: EnvelopeV22) -> str:
    return f"cep-jules-v22-create-{effect_key(env)}-"


def create_attempt_marker(env: EnvelopeV22, state: DurableState, *, target: bool = False) -> str:
    identity = target_intent_identity(env) if target else intent_identity(env)
    return f"{create_attempt_prefix(env)}{identity[:24]}-{state.value}"


def binding_prefix(session_id: str) -> str:
    return f"cep-jules-v22-session-{sha256_text(session_id)[:24]}-binding-"


def binding_marker(session_id: str, logical_task_id: str, write_domain: str) -> str:
    suffix = sha256_json({"logical_task_id": logical_task_id, "write_domain": write_domain})[:24]
    return binding_prefix(session_id) + suffix


def _active_by_name(github: Any, name: str) -> list[dict[str, Any]]:
    return github.list_active_artifacts_by_name(name)


def _active_by_prefix(github: Any, prefix: str) -> list[dict[str, Any]]:
    return github.list_active_artifacts_by_prefix(prefix)


def require_new_request(github: Any, env: EnvelopeV22) -> None:
    for state in (
        DurableState.COMPLETED,
        DurableState.RECONCILED_APPLIED,
        DurableState.RECONCILED_NOT_APPLIED,
    ):
        if _active_by_name(github, request_marker(env, state)):
            raise GatewayError(
                ErrorClassification.IDEMPOTENCY_CONFLICT,
                "v2.2 request identity is already terminal and cannot be replayed",
                details={"state": state.value},
            )
    for state in (DurableState.UNKNOWN_WRITE_OUTCOME, DurableState.INTENT_RECORDED):
        if _active_by_name(github, request_marker(env, state)):
            raise GatewayError(
                ErrorClassification.RECONCILIATION_REQUIRED,
                "v2.2 request has unresolved durable intent/outcome",
                details={"state": state.value, "blind_retry": False},
            )


def create_effect_snapshot(github: Any, env: EnvelopeV22) -> dict[str, Any]:
    prefix = create_attempt_prefix(env)
    attempts: dict[str, set[str]] = {}
    for row in _active_by_prefix(github, prefix):
        name = str(row.get("name") or "")
        suffix = name[len(prefix):] if name.startswith(prefix) else ""
        attempt, sep, state = suffix.partition("-")
        if not sep or len(attempt) != 24:
            raise GatewayError(ErrorClassification.PROVIDER_PROTOCOL_FAILED, "invalid create-effect marker identity")
        attempts.setdefault(attempt, set()).add(state)
    unresolved = sorted(
        attempt for attempt, states in attempts.items()
        if DurableState.INTENT_RECORDED.value in states
        and DurableState.RECONCILED_APPLIED.value not in states
        and DurableState.RECONCILED_NOT_APPLIED.value not in states
    )
    applied = sorted(attempt for attempt, states in attempts.items() if DurableState.RECONCILED_APPLIED.value in states)
    return {"attempts": {key: sorted(value) for key, value in attempts.items()}, "unresolved": unresolved, "applied": applied}


def require_unused_create_effect(github: Any, env: EnvelopeV22) -> None:
    snapshot = create_effect_snapshot(github, env)
    if snapshot["applied"]:
        raise GatewayError(
            ErrorClassification.IDEMPOTENCY_CONFLICT,
            "logical create effect is already applied",
            details={**snapshot, "blind_retry": False},
        )
    if snapshot["unresolved"]:
        raise GatewayError(
            ErrorClassification.RECONCILIATION_REQUIRED,
            "logical create effect has an unresolved prior attempt",
            details={**snapshot, "blind_retry": False},
        )


@dataclass(frozen=True)
class BindingDecision:
    marker: str
    needs_persist: bool


def require_session_binding(github: Any, env: EnvelopeV22, session_id: str | None = None) -> BindingDecision:
    sid = session_id or env.session_id or ""
    if not sid.isdigit():
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "session binding requires a valid session id")
    expected = binding_marker(sid, env.logical_task_id, env.write_domain)
    names = sorted({str(row.get("name") or "") for row in _active_by_prefix(github, binding_prefix(sid))})
    conflicts = [name for name in names if name and name != expected]
    if conflicts:
        raise GatewayError(
            ErrorClassification.RECONCILIATION_REQUIRED,
            "session is durably bound to a different logical task/write domain",
            details={"session_id": sid, "conflicting_bindings": conflicts, "blind_retry": False},
        )
    return BindingDecision(expected, expected not in names)


def target_request_state(github: Any, env: EnvelopeV22) -> str:
    target_intent_identity(env)
    for state in (
        DurableState.RECONCILED_APPLIED,
        DurableState.RECONCILED_NOT_APPLIED,
        DurableState.COMPLETED,
        DurableState.UNKNOWN_WRITE_OUTCOME,
        DurableState.INTENT_RECORDED,
    ):
        if _active_by_name(github, request_marker(env, state, target=True)):
            return state.value
    return "NOT_SEEN"

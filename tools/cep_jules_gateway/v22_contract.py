from __future__ import annotations

import json
import re
from dataclasses import dataclass
from typing import Any

from .models import ErrorClassification, GatewayError

SCHEMA_VERSION = "2.2"
MAX_REQUEST_BYTES = 16_384

CONTROLLER_LANES = {
    "PARENT": {"PARENT", "W01_W02", "W03_W04", "W05"},
    "A": {"W03_W04"},
    "B": {"W01_W02"},
    "C": {"W05"},
}
MUTATIONS = {"create_session", "send_message", "approve_plan"}
RECONCILIATIONS = {
    "reconcile_create_session",
    "reconcile_send_message",
    "reconcile_approve_plan",
}
INSTRUCTION_ACTIONS = {"EXECUTE", "CONTINUE", "CORRECT", "REPLAN", "REVIEW", "COLLECT_EVIDENCE"}

_ID = re.compile(r"[A-Za-z0-9][A-Za-z0-9._:-]{0,119}\Z")
_LOGICAL = re.compile(r"[A-Za-z0-9][A-Za-z0-9._:/-]{0,159}\Z")
_SESSION = re.compile(r"[0-9]{1,32}\Z")
_SHA = re.compile(r"[0-9a-f]{40}\Z")
_DIGEST = re.compile(r"[0-9a-f]{64}\Z")
_REF = re.compile(r"[A-Za-z0-9][A-Za-z0-9._:/@+\-=]{0,255}\Z")
_ACTIVITY = re.compile(r"sessions/[0-9]{1,32}/activities/[A-Za-z0-9._:-]{1,160}\Z")
_DRIVE_REF = re.compile(r"drive:[A-Za-z0-9_-]{10,200}\Z")

_ALLOWED = {
    "schema_version", "request_id", "controller_id", "lane", "logical_task_id", "action",
    "session_id", "starting_branch", "expected_sha", "expected_state", "expected_plan_digest",
    "expected_plan_id", "expected_plan_activity_name", "expected_plan_create_time",
    "expected_session_update_time", "write_domain", "authority_event", "authority_ref",
    "execution_mode", "instruction_ref", "instruction_digest", "instruction_action",
    "target_request_id", "target_intent_identity", "min_reconcile_age_seconds",
}

_FORBIDDEN_KEY_FRAGMENTS = (
    "secret", "credential", "private_key", "service_account", "api_key", "apikey",
    "access_token", "refresh_token", "password", "gdrive_sa_json", "prompt", "title",
)
_FORBIDDEN_VALUE_PATTERNS = (
    re.compile(r"(?i)\bAuthorization\s*:\s*Bearer\s+\S+"),
    re.compile(r"(?i)\bx-goog-api-key\s*[=:]\s*\S+"),
    re.compile(r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"),
    re.compile(r"\bgh[pousr]_[A-Za-z0-9]{20,}\b"),
    re.compile(r"\bAIza[0-9A-Za-z_-]{20,}\b"),
)


def invalid(message: str, **details: Any) -> GatewayError:
    return GatewayError(ErrorClassification.INVALID_REQUEST, message, details=details)


def _text(payload: dict[str, Any], key: str, limit: int, *, required: bool = False) -> str | None:
    value = payload.get(key)
    if value is None:
        if required:
            raise invalid(f"{key} is required")
        return None
    if not isinstance(value, str):
        raise invalid(f"{key} must be a string")
    value = value.strip()
    if not value or len(value) > limit:
        raise invalid(f"{key} is empty or oversized", max_chars=limit)
    return value


def _safe(payload: dict[str, Any]) -> None:
    for key, value in payload.items():
        lowered = str(key).lower()
        if any(fragment in lowered for fragment in _FORBIDDEN_KEY_FRAGMENTS):
            raise invalid("v2.2 public control envelope contains a forbidden content field", field=key)
        if isinstance(value, str):
            if len(value) > 4096:
                raise invalid("v2.2 public control value is oversized", field=key)
            if any(pattern.search(value) for pattern in _FORBIDDEN_VALUE_PATTERNS):
                raise invalid("v2.2 public control envelope appears to contain credential material", field=key)


@dataclass(frozen=True)
class EnvelopeV22:
    request_id: str
    controller_id: str
    lane: str
    logical_task_id: str
    action: str
    write_domain: str
    execution_mode: str
    session_id: str | None = None
    starting_branch: str | None = None
    expected_sha: str | None = None
    expected_state: str | None = None
    expected_plan_digest: str | None = None
    expected_plan_id: str | None = None
    expected_plan_activity_name: str | None = None
    expected_plan_create_time: str | None = None
    expected_session_update_time: str | None = None
    authority_event: str | None = None
    authority_ref: str | None = None
    instruction_ref: str | None = None
    instruction_digest: str | None = None
    instruction_action: str | None = None
    target_request_id: str | None = None
    target_intent_identity: str | None = None
    min_reconcile_age_seconds: int = 60

    @property
    def is_mutation(self) -> bool:
        return self.action in MUTATIONS

    @property
    def is_reconciliation(self) -> bool:
        return self.action in RECONCILIATIONS

    @property
    def original_action(self) -> str:
        return {
            "reconcile_create_session": "create_session",
            "reconcile_send_message": "send_message",
            "reconcile_approve_plan": "approve_plan",
        }.get(self.action, self.action)

    def public_dict(self) -> dict[str, Any]:
        data = {
            "schema_version": SCHEMA_VERSION,
            "request_id": self.request_id,
            "controller_id": self.controller_id,
            "lane": self.lane,
            "logical_task_id": self.logical_task_id,
            "action": self.action,
            "write_domain": self.write_domain,
            "execution_mode": self.execution_mode,
            "session_id": self.session_id,
            "starting_branch": self.starting_branch,
            "expected_sha": self.expected_sha,
            "expected_state": self.expected_state,
            "expected_plan_digest": self.expected_plan_digest,
            "expected_plan_id": self.expected_plan_id,
            "expected_plan_activity_name": self.expected_plan_activity_name,
            "expected_plan_create_time": self.expected_plan_create_time,
            "expected_session_update_time": self.expected_session_update_time,
            "authority_event": self.authority_event,
            "authority_ref": self.authority_ref,
            "instruction_ref": self.instruction_ref,
            "instruction_digest": self.instruction_digest,
            "instruction_action": self.instruction_action,
            "target_request_id": self.target_request_id,
            "target_intent_identity": self.target_intent_identity,
            "min_reconcile_age_seconds": self.min_reconcile_age_seconds,
        }
        return {key: value for key, value in data.items() if value is not None}

    def target_public_dict(self) -> dict[str, Any]:
        if not self.is_reconciliation or not self.target_request_id:
            raise invalid("target_public_dict requires reconciliation target identity")
        data = self.public_dict()
        data["request_id"] = self.target_request_id
        data["action"] = self.original_action
        data["execution_mode"] = "MUTATION_CANARY"
        data.pop("target_request_id", None)
        data.pop("target_intent_identity", None)
        data.pop("min_reconcile_age_seconds", None)
        return data


def parse_v22(raw: str | bytes) -> EnvelopeV22:
    raw_bytes = raw.encode("utf-8") if isinstance(raw, str) else raw if isinstance(raw, bytes) else b""
    if not raw_bytes or len(raw_bytes) > MAX_REQUEST_BYTES:
        raise invalid("v2.2 envelope is empty or oversized", max_bytes=MAX_REQUEST_BYTES)
    try:
        payload = json.loads(raw_bytes.decode("utf-8"))
    except (UnicodeDecodeError, json.JSONDecodeError) as exc:
        raise invalid("v2.2 envelope is not valid UTF-8 JSON", error=type(exc).__name__) from exc
    if not isinstance(payload, dict):
        raise invalid("v2.2 envelope root must be an object")
    unknown = sorted(set(payload) - _ALLOWED)
    if unknown:
        raise invalid("v2.2 envelope contains unsupported fields", fields=unknown)
    _safe(payload)
    if payload.get("schema_version") != SCHEMA_VERSION:
        raise invalid("unsupported v2.2 schema_version")

    request_id = _text(payload, "request_id", 120, required=True)
    controller = _text(payload, "controller_id", 16, required=True)
    lane = _text(payload, "lane", 32, required=True)
    logical = _text(payload, "logical_task_id", 160, required=True)
    action = _text(payload, "action", 64, required=True)
    write_domain = _text(payload, "write_domain", 256, required=True)
    execution_mode = _text(payload, "execution_mode", 32, required=True)

    if request_id is None or not _ID.fullmatch(request_id):
        raise invalid("request_id format is invalid")
    if controller not in CONTROLLER_LANES or lane not in CONTROLLER_LANES[controller]:
        raise invalid("controller_id/lane pairing is not authorized by v2.2 transport contract")
    if logical is None or not _LOGICAL.fullmatch(logical):
        raise invalid("logical_task_id format is invalid")
    if action not in MUTATIONS | RECONCILIATIONS:
        raise invalid("unsupported v2.2 action", action=action)
    if write_domain is None or not _REF.fullmatch(write_domain):
        raise invalid("write_domain format is invalid")
    if action in MUTATIONS and execution_mode != "MUTATION_CANARY":
        raise invalid("mutation requires execution_mode=MUTATION_CANARY")
    if action in RECONCILIATIONS and execution_mode != "RECONCILE_ONLY":
        raise invalid("reconciliation requires execution_mode=RECONCILE_ONLY")

    session_id = _text(payload, "session_id", 32)
    starting_branch = _text(payload, "starting_branch", 256)
    expected_sha = _text(payload, "expected_sha", 40)
    expected_state = _text(payload, "expected_state", 80)
    plan_digest = _text(payload, "expected_plan_digest", 64)
    plan_id = _text(payload, "expected_plan_id", 200)
    plan_activity = _text(payload, "expected_plan_activity_name", 256)
    plan_time = _text(payload, "expected_plan_create_time", 256)
    session_update = _text(payload, "expected_session_update_time", 256)
    authority_event = _text(payload, "authority_event", 256)
    authority_ref = _text(payload, "authority_ref", 256)
    instruction_ref = _text(payload, "instruction_ref", 240)
    instruction_digest = _text(payload, "instruction_digest", 64)
    instruction_action = _text(payload, "instruction_action", 32)
    target_request = _text(payload, "target_request_id", 120)
    target_intent = _text(payload, "target_intent_identity", 64)

    if session_id is not None and not _SESSION.fullmatch(session_id):
        raise invalid("session_id format is invalid")
    if starting_branch is not None and not _REF.fullmatch(starting_branch):
        raise invalid("starting_branch format is invalid")
    if expected_sha is not None:
        expected_sha = expected_sha.lower()
        if not _SHA.fullmatch(expected_sha):
            raise invalid("expected_sha format is invalid")
    if (starting_branch is None) != (expected_sha is None):
        raise invalid("starting_branch and expected_sha must be supplied together")
    if plan_digest is not None:
        plan_digest = plan_digest.lower()
        if not _DIGEST.fullmatch(plan_digest):
            raise invalid("expected_plan_digest format is invalid")
    if instruction_digest is not None:
        instruction_digest = instruction_digest.lower()
        if not _DIGEST.fullmatch(instruction_digest):
            raise invalid("instruction_digest format is invalid")
    if target_intent is not None:
        target_intent = target_intent.lower()
        if not _DIGEST.fullmatch(target_intent):
            raise invalid("target_intent_identity format is invalid")
    if plan_activity is not None and not _ACTIVITY.fullmatch(plan_activity):
        raise invalid("expected_plan_activity_name format is invalid")
    for name, value in (("expected_state", expected_state), ("expected_plan_id", plan_id),
                        ("expected_plan_create_time", plan_time), ("expected_session_update_time", session_update),
                        ("authority_event", authority_event), ("authority_ref", authority_ref)):
        if value is not None and not _REF.fullmatch(value):
            raise invalid(f"{name} must be a bounded reference")

    original = {
        "reconcile_create_session": "create_session",
        "reconcile_send_message": "send_message",
        "reconcile_approve_plan": "approve_plan",
    }.get(action, action)
    if original in {"send_message", "approve_plan"} and session_id is None:
        raise invalid(f"session_id is required for {action}")
    if original == "create_session" and (starting_branch is None or expected_sha is None):
        raise invalid(f"starting_branch and expected_sha are required for {action}")
    if original == "send_message" and (expected_state is None or session_update is None):
        raise invalid(f"expected_state and expected_session_update_time are required for {action}")
    if original == "approve_plan":
        required = (expected_state, plan_digest, plan_id, plan_activity, plan_time, session_update)
        if any(value is None for value in required):
            raise invalid(f"exact reviewed plan identity fields are required for {action}")

    if original in {"create_session", "send_message"}:
        if instruction_ref is None or not _DRIVE_REF.fullmatch(instruction_ref):
            raise invalid("instruction_ref must be an opaque drive:file_id reference")
        if instruction_digest is None or instruction_action not in INSTRUCTION_ACTIONS:
            raise invalid("instruction_digest and supported instruction_action are required")
    elif any(value is not None for value in (instruction_ref, instruction_digest, instruction_action)):
        raise invalid("instruction fields do not apply to plan approval")

    if action in RECONCILIATIONS:
        if target_request is None or not _ID.fullmatch(target_request) or target_intent is None:
            raise invalid("reconciliation requires target_request_id and target_intent_identity")
    elif target_request is not None or target_intent is not None:
        raise invalid("target reconciliation fields are not allowed on mutation requests")

    age = payload.get("min_reconcile_age_seconds", 60)
    if isinstance(age, bool) or not isinstance(age, int) or not 0 <= age <= 600:
        raise invalid("min_reconcile_age_seconds must be an integer from 0 to 600")

    return EnvelopeV22(
        request_id=request_id,
        controller_id=controller,
        lane=lane,
        logical_task_id=logical,
        action=action,
        write_domain=write_domain,
        execution_mode=execution_mode,
        session_id=session_id,
        starting_branch=starting_branch,
        expected_sha=expected_sha,
        expected_state=expected_state,
        expected_plan_digest=plan_digest,
        expected_plan_id=plan_id,
        expected_plan_activity_name=plan_activity,
        expected_plan_create_time=plan_time,
        expected_session_update_time=session_update,
        authority_event=authority_event,
        authority_ref=authority_ref,
        instruction_ref=instruction_ref,
        instruction_digest=instruction_digest,
        instruction_action=instruction_action,
        target_request_id=target_request,
        target_intent_identity=target_intent,
        min_reconcile_age_seconds=age,
    )

from __future__ import annotations

import json
import re
from dataclasses import dataclass, field
from typing import Any

from .models import ErrorClassification, GatewayError

MAX_REQUEST_BYTES = 16_384
SCHEMA_VERSION = "2.0"
MUTATION_SCHEMA_VERSION = "2.1"

_CONTROLLER_LANES = {
    "PARENT": {"PARENT", "W01", "W02", "W03", "W04", "W05", "W01_W02", "W03_W04"},
    "A": {"W01"},
    "B": {"W02"},
    "C": {"W03"},
    "D": {"W04"},
    "E": {"W05"},
}
_READ_ACTIONS = {"inspect_bundle", "get_session", "list_sessions", "list_activities"}
_MUTATION_ACTIONS = {"create_session", "send_message", "approve_plan"}
_SESSION_ACTIONS = {"inspect_bundle", "get_session", "list_activities", "send_message", "approve_plan"}
_ALLOWED_FIELDS = {
    "schema_version",
    "request_id",
    "controller_id",
    "lane",
    "logical_task_id",
    "action",
    "session_id",
    "starting_branch",
    "expected_sha",
    "expected_state",
    "expected_plan_digest",
    "expected_plan_activity_name",
    "expected_plan_create_time",
    "expected_session_update_time",
    "write_domain",
    "authority_event",
    "authority_ref",
    "execution_mode",
    "title",
    "prompt",
    "options",
}
_ALLOWED_OPTION_FIELDS = {
    "page_size",
    "max_activity_pages",
    "recent_agent_messages",
    "recent_bash_outputs",
    "max_hydration_reads",
    "max_exact_text_chars",
    "max_provider_reads",
    "max_total_items",
    "max_total_exact_text_bytes",
    "max_serialized_result_bytes",
    "include_patch",
    "include_bash_output_text",
}
_UNSAFE_KEY_FRAGMENTS = (
    "secret",
    "credential",
    "private_key",
    "service_account",
    "api_key",
    "apikey",
    "access_token",
    "refresh_token",
    "password",
    "gdrive_sa_json",
)
_UNSAFE_VALUE_PATTERNS = (
    re.compile(r"(?i)\bAuthorization\s*:\s*Bearer\s+\S+"),
    re.compile(r"(?i)\bx-goog-api-key\s*[=:]\s*\S+"),
    re.compile(r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"),
    re.compile(r"\bgh[pousr]_[A-Za-z0-9]{20,}\b"),
    re.compile(r"\bAIza[0-9A-Za-z_-]{20,}\b"),
    re.compile(r"\beyJ[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\b"),
)
_ID_RE = re.compile(r"[A-Za-z0-9][A-Za-z0-9._:-]{0,119}\Z")
_LOGICAL_RE = re.compile(r"[A-Za-z0-9][A-Za-z0-9._:/-]{0,159}\Z")
_SESSION_RE = re.compile(r"[0-9]{1,32}\Z")
_SHA_RE = re.compile(r"[0-9a-f]{40}\Z")
_DIGEST_RE = re.compile(r"[0-9a-f]{64}\Z")
_SAFE_REF_RE = re.compile(r"[A-Za-z0-9][A-Za-z0-9._:/@+\-=]{0,255}\Z")
_ACTIVITY_RE = re.compile(r"sessions/[0-9]{1,32}/activities/[A-Za-z0-9._:-]{1,160}\Z")


@dataclass(frozen=True)
class InspectOptions:
    page_size: int = 100
    max_activity_pages: int = 20
    recent_agent_messages: int = 10
    recent_bash_outputs: int = 5
    max_hydration_reads: int = 20
    max_exact_text_chars: int = 120_000
    max_provider_reads: int = 64
    max_total_items: int = 2_000
    max_total_exact_text_bytes: int = 256_000
    max_serialized_result_bytes: int = 1_500_000
    include_patch: bool = True
    include_bash_output_text: bool = True


@dataclass(frozen=True)
class RequestEnvelope:
    schema_version: str
    request_id: str
    controller_id: str
    lane: str
    action: str
    logical_task_id: str | None = None
    session_id: str | None = None
    starting_branch: str | None = None
    expected_sha: str | None = None
    expected_state: str | None = None
    expected_plan_digest: str | None = None
    expected_plan_activity_name: str | None = None
    expected_plan_create_time: str | None = None
    expected_session_update_time: str | None = None
    write_domain: str | None = None
    authority_event: str | None = None
    authority_ref: str | None = None
    execution_mode: str | None = None
    title: str | None = None
    prompt: str | None = None
    options: InspectOptions = field(default_factory=InspectOptions)

    @property
    def is_mutation(self) -> bool:
        return self.action in _MUTATION_ACTIONS

    def public_dict(self) -> dict[str, Any]:
        data = {
            "schema_version": self.schema_version,
            "request_id": self.request_id,
            "controller_id": self.controller_id,
            "lane": self.lane,
            "logical_task_id": self.logical_task_id,
            "action": self.action,
            "session_id": self.session_id,
            "starting_branch": self.starting_branch,
            "expected_sha": self.expected_sha,
            "expected_state": self.expected_state,
            "expected_plan_digest": self.expected_plan_digest,
            "expected_plan_activity_name": self.expected_plan_activity_name,
            "expected_plan_create_time": self.expected_plan_create_time,
            "expected_session_update_time": self.expected_session_update_time,
            "write_domain": self.write_domain,
            "authority_event": self.authority_event,
            "authority_ref": self.authority_ref,
            "execution_mode": self.execution_mode,
        }
        return {k: v for k, v in data.items() if v is not None}


def _invalid(message: str, **details: Any) -> GatewayError:
    return GatewayError(ErrorClassification.INVALID_REQUEST, message, details=details)


def _bounded_str(payload: dict[str, Any], key: str, max_chars: int) -> str | None:
    value = payload.get(key)
    if value is None:
        return None
    if not isinstance(value, str):
        raise _invalid(f"{key} must be a string")
    value = value.strip()
    if not value or len(value) > max_chars:
        raise _invalid(f"{key} is empty or oversized", max_chars=max_chars)
    return value


def _validate_public_safe(value: Any, path: str = "$") -> None:
    if isinstance(value, dict):
        for key, item in value.items():
            key_text = str(key)
            lowered = key_text.lower()
            if any(fragment in lowered for fragment in _UNSAFE_KEY_FRAGMENTS):
                raise _invalid("public control envelope contains a forbidden sensitive field", path=f"{path}.{key_text}")
            _validate_public_safe(item, f"{path}.{key_text}")
        return
    if isinstance(value, list):
        for index, item in enumerate(value):
            _validate_public_safe(item, f"{path}[{index}]")
        return
    if isinstance(value, str):
        if len(value) > 4_096:
            raise _invalid("individual envelope string is oversized", path=path)
        for pattern in _UNSAFE_VALUE_PATTERNS:
            if pattern.search(value):
                raise _invalid("public control envelope appears to contain credential material", path=path)


def _int_option(options: dict[str, Any], key: str, default: int, minimum: int, maximum: int) -> int:
    value = options.get(key, default)
    if isinstance(value, bool) or not isinstance(value, int) or not minimum <= value <= maximum:
        raise _invalid(f"options.{key} must be an integer in range", minimum=minimum, maximum=maximum)
    return value


def _bool_option(options: dict[str, Any], key: str, default: bool) -> bool:
    value = options.get(key, default)
    if not isinstance(value, bool):
        raise _invalid(f"options.{key} must be boolean")
    return value


def parse_envelope(raw: str | bytes) -> RequestEnvelope:
    if isinstance(raw, str):
        raw_bytes = raw.encode("utf-8")
    elif isinstance(raw, bytes):
        raw_bytes = raw
    else:
        raise _invalid("request envelope must be UTF-8 JSON text")

    if not raw_bytes or len(raw_bytes) > MAX_REQUEST_BYTES:
        raise _invalid("request envelope is empty or oversized", max_bytes=MAX_REQUEST_BYTES)
    try:
        payload = json.loads(raw_bytes.decode("utf-8"))
    except (UnicodeDecodeError, json.JSONDecodeError) as exc:
        raise _invalid("request envelope is not valid UTF-8 JSON", error=type(exc).__name__) from exc
    if not isinstance(payload, dict):
        raise _invalid("request envelope root must be an object")

    unknown = sorted(set(payload) - _ALLOWED_FIELDS)
    if unknown:
        raise _invalid("request envelope contains unsupported fields", fields=unknown)
    _validate_public_safe(payload)

    schema_version = _bounded_str(payload, "schema_version", 16)
    if schema_version not in {SCHEMA_VERSION, MUTATION_SCHEMA_VERSION}:
        raise _invalid("unsupported schema_version", supported=[SCHEMA_VERSION, MUTATION_SCHEMA_VERSION])

    request_id = _bounded_str(payload, "request_id", 120)
    if request_id is None or not _ID_RE.fullmatch(request_id):
        raise _invalid("request_id format is invalid")

    controller_id = _bounded_str(payload, "controller_id", 16)
    if controller_id not in _CONTROLLER_LANES:
        raise _invalid("controller_id is unsupported", supported=sorted(_CONTROLLER_LANES))
    lane = _bounded_str(payload, "lane", 32)
    if lane not in _CONTROLLER_LANES[controller_id]:
        raise _invalid("controller_id/lane pairing is not authorized by the public envelope contract")

    action = _bounded_str(payload, "action", 64)
    allowed_actions = _READ_ACTIONS if schema_version == SCHEMA_VERSION else (_READ_ACTIONS | _MUTATION_ACTIONS)
    if action not in allowed_actions:
        raise _invalid("action is unsupported by the selected v2 schema", supported=sorted(allowed_actions))

    logical_task_id = _bounded_str(payload, "logical_task_id", 160)
    if logical_task_id is not None and not _LOGICAL_RE.fullmatch(logical_task_id):
        raise _invalid("logical_task_id format is invalid")

    session_id = _bounded_str(payload, "session_id", 32)
    if action in _SESSION_ACTIONS:
        if session_id is None or not _SESSION_RE.fullmatch(session_id):
            raise _invalid(f"session_id is required for action {action}")
    elif session_id is not None and not _SESSION_RE.fullmatch(session_id):
        raise _invalid("session_id format is invalid")

    starting_branch = _bounded_str(payload, "starting_branch", 256)
    if starting_branch is not None and not _SAFE_REF_RE.fullmatch(starting_branch):
        raise _invalid("starting_branch format is invalid")
    expected_sha = _bounded_str(payload, "expected_sha", 40)
    if expected_sha is not None:
        expected_sha = expected_sha.lower()
        if not _SHA_RE.fullmatch(expected_sha):
            raise _invalid("expected_sha must be a 40-character hex SHA")
    if (starting_branch is None) != (expected_sha is None):
        raise _invalid("starting_branch and expected_sha must be supplied together as an exact GitHub precondition")

    expected_plan_digest = _bounded_str(payload, "expected_plan_digest", 64)
    if expected_plan_digest is not None:
        expected_plan_digest = expected_plan_digest.lower()
        if not _DIGEST_RE.fullmatch(expected_plan_digest):
            raise _invalid("expected_plan_digest must be a 64-character hex SHA-256")

    expected_state = _bounded_str(payload, "expected_state", 80)
    write_domain = _bounded_str(payload, "write_domain", 256)
    authority_event = _bounded_str(payload, "authority_event", 256)
    authority_ref = _bounded_str(payload, "authority_ref", 256)
    execution_mode = _bounded_str(payload, "execution_mode", 32)
    expected_plan_activity_name = _bounded_str(payload, "expected_plan_activity_name", 256)
    expected_plan_create_time = _bounded_str(payload, "expected_plan_create_time", 256)
    expected_session_update_time = _bounded_str(payload, "expected_session_update_time", 256)

    for name, value in (
        ("expected_state", expected_state),
        ("write_domain", write_domain),
        ("authority_event", authority_event),
        ("authority_ref", authority_ref),
        ("execution_mode", execution_mode),
        ("expected_plan_create_time", expected_plan_create_time),
        ("expected_session_update_time", expected_session_update_time),
    ):
        if value is not None and not _SAFE_REF_RE.fullmatch(value):
            raise _invalid(f"{name} must be a bounded identifier/reference, not free-form content")
    if expected_plan_activity_name is not None and not _ACTIVITY_RE.fullmatch(expected_plan_activity_name):
        raise _invalid("expected_plan_activity_name must be an exact Jules activity identity")

    title = _bounded_str(payload, "title", 240)
    prompt = _bounded_str(payload, "prompt", 4_096)

    if action in _MUTATION_ACTIONS:
        if execution_mode != "MUTATION_CANARY":
            raise _invalid("mutating v2 actions require explicit execution_mode=MUTATION_CANARY")
        if logical_task_id is None or write_domain is None:
            raise _invalid("mutating v2 actions require logical_task_id and write_domain")
        if action == "create_session":
            if starting_branch is None or expected_sha is None or title is None or prompt is None:
                raise _invalid("create_session requires starting_branch, expected_sha, title, and prompt")
        if action == "send_message":
            if expected_state is None or prompt is None:
                raise _invalid("send_message requires expected_state and prompt")
        if action == "approve_plan":
            missing = [
                name
                for name, value in (
                    ("expected_state", expected_state),
                    ("expected_plan_digest", expected_plan_digest),
                    ("expected_plan_activity_name", expected_plan_activity_name),
                    ("expected_plan_create_time", expected_plan_create_time),
                    ("expected_session_update_time", expected_session_update_time),
                )
                if value is None
            ]
            if missing:
                raise _invalid("approve_plan requires exact reviewed plan/provider identity fields", fields=missing)

    options_raw = payload.get("options") or {}
    if not isinstance(options_raw, dict):
        raise _invalid("options must be an object")
    unknown_options = sorted(set(options_raw) - _ALLOWED_OPTION_FIELDS)
    if unknown_options:
        raise _invalid("options contains unsupported fields", fields=unknown_options)
    options = InspectOptions(
        page_size=_int_option(options_raw, "page_size", 100, 1, 100),
        max_activity_pages=_int_option(options_raw, "max_activity_pages", 20, 1, 50),
        recent_agent_messages=_int_option(options_raw, "recent_agent_messages", 10, 0, 50),
        recent_bash_outputs=_int_option(options_raw, "recent_bash_outputs", 5, 0, 20),
        max_hydration_reads=_int_option(options_raw, "max_hydration_reads", 20, 0, 100),
        max_exact_text_chars=_int_option(options_raw, "max_exact_text_chars", 120_000, 1_000, 500_000),
        max_provider_reads=_int_option(options_raw, "max_provider_reads", 64, 1, 200),
        max_total_items=_int_option(options_raw, "max_total_items", 2_000, 1, 10_000),
        max_total_exact_text_bytes=_int_option(options_raw, "max_total_exact_text_bytes", 256_000, 1_000, 2_000_000),
        max_serialized_result_bytes=_int_option(options_raw, "max_serialized_result_bytes", 1_500_000, 10_000, 8_000_000),
        include_patch=_bool_option(options_raw, "include_patch", True),
        include_bash_output_text=_bool_option(options_raw, "include_bash_output_text", True),
    )

    return RequestEnvelope(
        schema_version=schema_version,
        request_id=request_id,
        controller_id=controller_id,
        lane=lane,
        action=action,
        logical_task_id=logical_task_id,
        session_id=session_id,
        starting_branch=starting_branch,
        expected_sha=expected_sha,
        expected_state=expected_state,
        expected_plan_digest=expected_plan_digest,
        expected_plan_activity_name=expected_plan_activity_name,
        expected_plan_create_time=expected_plan_create_time,
        expected_session_update_time=expected_session_update_time,
        write_domain=write_domain,
        authority_event=authority_event,
        authority_ref=authority_ref,
        execution_mode=execution_mode,
        title=title,
        prompt=prompt,
        options=options,
    )

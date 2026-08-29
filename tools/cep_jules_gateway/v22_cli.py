from __future__ import annotations

import argparse
import json
import os
from pathlib import Path
from typing import Any

from .models import ErrorClassification, GatewayError
from .receipt import error_receipt
from .sanitize import sanitize_obj
from .v22_contract import EnvelopeV22, parse_v22
from .v22_runtime import execute, preflight, reconcile
from .v22_state import (
    DurableState,
    binding_marker,
    create_attempt_marker,
    effect_key,
    intent_identity,
    request_key,
    request_marker,
    require_new_request,
    require_session_binding,
    require_unused_create_effect,
    target_intent_identity,
)
from .github import GitHubClient
from .jules import JulesClient


def _write(path: Path, value: Any) -> None:
    path.write_text(json.dumps(sanitize_obj(value), ensure_ascii=False, sort_keys=True, indent=2) + "\n", encoding="utf-8")


def _output(name: str, value: str) -> None:
    target = os.environ.get("GITHUB_OUTPUT")
    if not target:
        return
    if not name.replace("_", "").isalnum() or "\n" in value or "\r" in value:
        raise GatewayError(ErrorClassification.INVALID_STATE, "unsafe GitHub workflow output")
    with open(target, "a", encoding="utf-8") as handle:
        handle.write(f"{name}={value}\n")


def _json_output(name: str, value: dict[str, Any]) -> None:
    _output(name, json.dumps(value, ensure_ascii=False, sort_keys=True, separators=(",", ":")))


def _env_request() -> EnvelopeV22:
    return parse_v22(os.environ.get("CEP_JULES_REQUEST_JSON", ""))


def _github() -> GitHubClient:
    return GitHubClient(os.environ.get("GITHUB_TOKEN", ""), os.environ.get("CEP_REPOSITORY", ""))


def _jules() -> JulesClient:
    return JulesClient(os.environ.get("JULES_API_KEY", ""), api_base=os.environ.get("JULES_API_BASE", "https://jules.googleapis.com/v1alpha"), max_provider_reads=64)


def _source() -> str:
    value = os.environ.get("CEP_JULES_SOURCE", "sources/github/hamad933/Cybersecurity-Education-Platform")
    if not value.startswith("sources/github/") or len(value) > 300:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "configured Jules source identity is invalid")
    return value


def _typed_payload() -> dict[str, Any]:
    action = os.environ.get("CEP_INPUT_ACTION", "").strip()
    data: dict[str, Any] = {
        "schema_version": "2.2",
        "request_id": os.environ.get("CEP_INPUT_REQUEST_ID", "").strip(),
        "controller_id": os.environ.get("CEP_INPUT_CONTROLLER_ID", "").strip(),
        "lane": os.environ.get("CEP_INPUT_LANE", "").strip(),
        "logical_task_id": os.environ.get("CEP_INPUT_LOGICAL_TASK_ID", "").strip(),
        "action": action,
        "write_domain": os.environ.get("CEP_INPUT_WRITE_DOMAIN", "").strip(),
        "execution_mode": "RECONCILE_ONLY" if action.startswith("reconcile_") else "MUTATION_CANARY",
    }
    fields = {
        "session_id": "CEP_INPUT_SESSION_ID",
        "starting_branch": "CEP_INPUT_STARTING_BRANCH",
        "expected_sha": "CEP_INPUT_EXPECTED_SHA",
        "expected_state": "CEP_INPUT_EXPECTED_STATE",
        "expected_plan_digest": "CEP_INPUT_EXPECTED_PLAN_DIGEST",
        "expected_plan_id": "CEP_INPUT_EXPECTED_PLAN_ID",
        "expected_plan_activity_name": "CEP_INPUT_EXPECTED_PLAN_ACTIVITY_NAME",
        "expected_plan_create_time": "CEP_INPUT_EXPECTED_PLAN_CREATE_TIME",
        "expected_session_update_time": "CEP_INPUT_EXPECTED_SESSION_UPDATE_TIME",
        "instruction_ref": "CEP_INPUT_INSTRUCTION_REF",
        "instruction_digest": "CEP_INPUT_INSTRUCTION_DIGEST",
        "instruction_action": "CEP_INPUT_INSTRUCTION_ACTION",
        "authority_event": "CEP_INPUT_AUTHORITY_EVENT",
        "authority_ref": "CEP_INPUT_AUTHORITY_REF",
        "target_request_id": "CEP_INPUT_TARGET_REQUEST_ID",
        "target_intent_identity": "CEP_INPUT_TARGET_INTENT_IDENTITY",
    }
    for field, env_name in fields.items():
        value = os.environ.get(env_name, "").strip()
        if value:
            data[field] = value
    age = os.environ.get("CEP_INPUT_MIN_RECONCILE_AGE_SECONDS", "").strip()
    if age:
        try:
            data["min_reconcile_age_seconds"] = int(age)
        except ValueError as exc:
            raise GatewayError(ErrorClassification.INVALID_REQUEST, "min_reconcile_age_seconds must be an integer") from exc
    return data


def dispatch(out_dir: Path) -> int:
    env = parse_v22(json.dumps(_typed_payload(), ensure_ascii=False))
    public = env.public_dict()
    _write(out_dir / "request.json", public)
    _json_output("request_json", public)
    return 0


def route(out_dir: Path) -> int:
    env = _env_request()
    if env.is_reconciliation:
        target_intent_identity(env)
    data = {
        "request_key": request_key(env),
        "effect_key": effect_key(env),
        "operation_kind": "RECONCILIATION" if env.is_reconciliation else "MUTATION",
    }
    _write(out_dir / "routing.json", data)
    for key, value in data.items():
        _output(key, value)
    return 0


def preflight_phase(out_dir: Path) -> int:
    env = _env_request()
    if not env.is_mutation:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "preflight requires mutation action")
    github = _github()
    require_new_request(github, env)
    intent = preflight(env, _jules(), github, source_name=_source())
    _write(out_dir / "intent.json", intent)
    _write(out_dir / "receipt.json", {"status": DurableState.INTENT_RECORDED.value, "intent_identity": intent["intent_identity"], "public_safe": True})
    _output("proceed", "true")
    _output("intent_marker", request_marker(env, DurableState.INTENT_RECORDED))
    _output("completed_marker", request_marker(env, DurableState.COMPLETED))
    _output("unknown_marker", request_marker(env, DurableState.UNKNOWN_WRITE_OUTCOME))
    if env.action == "create_session":
        _output("create_effect_intent_marker", create_attempt_marker(env, DurableState.INTENT_RECORDED))
        _output("create_effect_applied_marker", create_attempt_marker(env, DurableState.RECONCILED_APPLIED))
        _output("create_effect_not_applied_marker", create_attempt_marker(env, DurableState.RECONCILED_NOT_APPLIED))
    else:
        _output("create_effect_intent_marker", "NONE")
        _output("create_effect_applied_marker", "NONE")
        _output("create_effect_not_applied_marker", "NONE")
    return 0


def effect_guard(out_dir: Path) -> int:
    env = _env_request()
    github = _github()
    if env.action == "create_session":
        require_unused_create_effect(github, env)
        _write(out_dir / "effect.json", {"effect_key": effect_key(env), "state": DurableState.INTENT_RECORDED.value})
        _output("bind_session", "false")
        return 0
    decision = require_session_binding(github, env)
    value = {
        "session_id": env.session_id,
        "logical_task_id": env.logical_task_id,
        "write_domain": env.write_domain,
        "binding_marker": decision.marker,
        "legacy_session_adoption": decision.needs_persist,
    }
    _write(out_dir / "session_binding.json", value)
    _output("bind_session", "true" if decision.needs_persist else "false")
    _output("session_binding_marker", decision.marker)
    return 0


def execute_phase(out_dir: Path, intent_file: Path) -> int:
    env = _env_request()
    try:
        intent = json.loads(intent_file.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        raise GatewayError(ErrorClassification.INVALID_STATE, "durable intent artifact is unreadable") from exc
    result = execute(env, intent, _jules(), _github(), source_name=_source())
    _write(out_dir / "receipt.json", result)
    _write(out_dir / "final_state.json", {"request_id": env.request_id, "intent_identity": intent.get("intent_identity"), "verification": result.get("verification"), "effect_resolution": result.get("effect_resolution")})
    final_state = str(result.get("idempotency_final_state") or "")
    resolution = str(result.get("effect_resolution") or "")
    if final_state not in {DurableState.COMPLETED.value, DurableState.UNKNOWN_WRITE_OUTCOME.value}:
        raise GatewayError(ErrorClassification.INVALID_STATE, "invalid mutation final state")
    if resolution not in {"APPLIED", "NOT_APPLIED", "UNKNOWN"}:
        raise GatewayError(ErrorClassification.INVALID_STATE, "invalid mutation effect resolution")
    _output("final_state", final_state)
    _output("effect_resolution", resolution)
    if env.action == "create_session":
        if resolution == "APPLIED":
            _output("create_effect_resolution_marker", create_attempt_marker(env, DurableState.RECONCILED_APPLIED))
        elif resolution == "NOT_APPLIED":
            _output("create_effect_resolution_marker", create_attempt_marker(env, DurableState.RECONCILED_NOT_APPLIED))
        else:
            _output("create_effect_resolution_marker", "NONE")
        session_id = str(result.get("session_id") or "")
        if resolution == "APPLIED" and session_id.isdigit():
            _output("created_session_binding_marker", binding_marker(session_id, env.logical_task_id, env.write_domain))
            _output("created_session_id", session_id)
        else:
            _output("created_session_binding_marker", "NONE")
            _output("created_session_id", "NONE")
    else:
        _output("create_effect_resolution_marker", "NONE")
        _output("created_session_binding_marker", "NONE")
        _output("created_session_id", "NONE")
    return 0


def reconcile_phase(out_dir: Path) -> int:
    env = _env_request()
    result = reconcile(env, _jules(), _github(), source_name=_source())
    _write(out_dir / "reconciliation.json", result)
    state = str(result.get("reconciliation_state") or "")
    persist = bool(result.get("persist_resolution"))
    _output("reconciliation_state", state)
    _output("persist_resolution", "true" if persist else "false")
    if state in {DurableState.RECONCILED_APPLIED.value, DurableState.RECONCILED_NOT_APPLIED.value}:
        durable = DurableState(state)
        _output("resolution_marker", request_marker(env, durable, target=True))
        if env.action == "reconcile_create_session":
            _output("create_effect_resolution_marker", create_attempt_marker(env, durable, target=True))
            sid = str(((result.get("details") or {}).get("session_id") or ""))
            if state == DurableState.RECONCILED_APPLIED.value and sid.isdigit():
                _output("bind_session", "true")
                _output("reconciled_session_id", sid)
                _output("session_binding_marker", binding_marker(sid, env.logical_task_id, env.write_domain))
            else:
                _output("bind_session", "false")
                _output("reconciled_session_id", "NONE")
                _output("session_binding_marker", "NONE")
        else:
            _output("create_effect_resolution_marker", "NONE")
            _output("bind_session", "false")
            _output("reconciled_session_id", "NONE")
            _output("session_binding_marker", "NONE")
    else:
        _output("resolution_marker", "NONE")
        _output("create_effect_resolution_marker", "NONE")
        _output("bind_session", "false")
        _output("reconciled_session_id", "NONE")
        _output("session_binding_marker", "NONE")
    return 0


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="CEP Jules Gateway v2.2 mutation/reconciliation helper")
    parser.add_argument("phase", choices=("dispatch", "route", "preflight", "effect-guard", "execute", "reconcile"))
    parser.add_argument("--output-dir", required=True)
    parser.add_argument("--intent-file")
    args = parser.parse_args(argv)
    out = Path(args.output_dir)
    out.mkdir(parents=True, exist_ok=True)
    try:
        if args.phase == "dispatch":
            return dispatch(out)
        if args.phase == "route":
            return route(out)
        if args.phase == "preflight":
            return preflight_phase(out)
        if args.phase == "effect-guard":
            return effect_guard(out)
        if args.phase == "reconcile":
            return reconcile_phase(out)
        if not args.intent_file:
            raise GatewayError(ErrorClassification.INVALID_REQUEST, "execute requires --intent-file")
        return execute_phase(out, Path(args.intent_file))
    except GatewayError as exc:
        _write(out / "receipt.json", {"status": "FAILED", "classification": exc.classification.value, "message": str(exc), "public_safe": True})
        return 2


if __name__ == "__main__":
    raise SystemExit(main())

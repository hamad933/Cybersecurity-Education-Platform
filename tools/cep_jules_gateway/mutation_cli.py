from __future__ import annotations

import argparse
import json
import os
from pathlib import Path
from typing import Any

from .effect import effect_concurrency_key, request_concurrency_key
from .envelope import parse_envelope
from .github import GitHubClient
from .idempotency import IdempotencyState, inspect_idempotency, marker_name, require_new_intent
from .jules import JulesClient
from .models import ErrorClassification, GatewayError
from .mutation import execute_mutation, preflight_mutation
from .receipt import error_receipt
from .sanitize import sanitize_obj


def _write_json(path: Path, value: Any) -> None:
    rendered = json.dumps(sanitize_obj(value), ensure_ascii=False, sort_keys=True, indent=2) + "\n"
    path.write_text(rendered, encoding="utf-8")


def _append_output(name: str, value: str) -> None:
    target = os.environ.get("GITHUB_OUTPUT")
    if not target:
        return
    if not name.replace("_", "").isalnum() or any(ch not in "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789._-" for ch in value):
        raise GatewayError(ErrorClassification.INVALID_STATE, "unsafe workflow output value")
    with open(target, "a", encoding="utf-8") as handle:
        handle.write(f"{name}={value}\n")


def _request() -> Any:
    return parse_envelope(os.environ.get("CEP_JULES_REQUEST_JSON", ""))


def _github() -> GitHubClient:
    return GitHubClient(
        os.environ.get("GITHUB_TOKEN", ""),
        os.environ.get("CEP_REPOSITORY", "hamad933/Cybersecurity-Education-Platform"),
    )


def _jules(envelope: Any) -> JulesClient:
    return JulesClient(
        os.environ.get("JULES_API_KEY", ""),
        api_base=os.environ.get("JULES_API_BASE", "https://jules.googleapis.com/v1alpha"),
        max_provider_reads=envelope.options.max_provider_reads,
    )


def _source_name() -> str:
    value = os.environ.get("CEP_JULES_SOURCE", "sources/github/hamad933/Cybersecurity-Education-Platform")
    if not value.startswith("sources/github/") or len(value) > 300:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "CEP_JULES_SOURCE is invalid")
    return value


def route(out_dir: Path) -> int:
    envelope = _request()
    if not envelope.is_mutation:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "mutation route requires a v2.1 mutating action")
    data = {
        "request_key": request_concurrency_key(envelope),
        "effect_key": effect_concurrency_key(envelope),
        "intent_marker": marker_name(envelope.request_id, IdempotencyState.INTENT_RECORDED),
        "completed_marker": marker_name(envelope.request_id, IdempotencyState.COMPLETED),
        "unknown_marker": marker_name(envelope.request_id, IdempotencyState.UNKNOWN_WRITE_OUTCOME),
    }
    _write_json(out_dir / "routing.json", data)
    for key, value in data.items():
        _append_output(key, value)
    return 0


def preflight(out_dir: Path) -> int:
    envelope = _request()
    github = _github()
    snapshot = inspect_idempotency(github, envelope.request_id)
    _write_json(out_dir / "idempotency.json", snapshot.to_dict())
    try:
        require_new_intent(snapshot)
    except GatewayError as exc:
        receipt = error_receipt(envelope, exc)
        receipt["idempotency"] = snapshot.to_dict()
        receipt["blind_retry"] = False
        if snapshot.decision_state == IdempotencyState.RECONCILIATION_REQUIRED:
            receipt["next_safe_read"] = {
                "action": "list_sessions" if envelope.action == "create_session" else "inspect_bundle",
                "session_id": envelope.session_id,
            }
        _write_json(out_dir / "receipt.json", receipt)
        _append_output("proceed", "false")
        _append_output("preflight_state", snapshot.decision_state.value)
        return 11 if snapshot.decision_state == IdempotencyState.RECONCILIATION_REQUIRED else 10

    intent = preflight_mutation(envelope, _jules(envelope), github, source_name=_source_name())
    _write_json(out_dir / "intent.json", intent)
    receipt = {
        "schema_version": "cep.jules.gateway.preflight_receipt/v2",
        **envelope.public_dict(),
        "intent_identity": intent["intent_identity"],
        "pre_state": (intent.get("preconditions") or {}).get("pre_state"),
        "idempotency_state": IdempotencyState.INTENT_RECORDED.value,
        "provider_mutation_performed": False,
        "blind_retry": False,
        "public_safe": True,
    }
    _write_json(out_dir / "receipt.json", receipt)
    _append_output("proceed", "true")
    _append_output("preflight_state", IdempotencyState.INTENT_RECORDED.value)
    return 0


def execute(out_dir: Path, intent_path: Path) -> int:
    envelope = _request()
    try:
        intent = json.loads(intent_path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        raise GatewayError(ErrorClassification.INVALID_STATE, "durable intent artifact is unreadable", details={"error": type(exc).__name__}) from exc
    if not isinstance(intent, dict):
        raise GatewayError(ErrorClassification.INVALID_STATE, "durable intent artifact must be an object")

    receipt = execute_mutation(envelope, intent, _jules(envelope), _github(), source_name=_source_name())
    _write_json(out_dir / "receipt.json", receipt)
    final_state = str(receipt.get("idempotency_final_state") or "")
    verification = str(receipt.get("verification") or "")
    if final_state not in {IdempotencyState.COMPLETED.value, IdempotencyState.UNKNOWN_WRITE_OUTCOME.value}:
        raise GatewayError(ErrorClassification.INVALID_STATE, "mutation engine returned an invalid final idempotency state")
    _write_json(
        out_dir / "final_state.json",
        {
            "request_id": envelope.request_id,
            "intent_identity": receipt.get("intent_identity"),
            "idempotency_final_state": final_state,
            "verification": verification,
            "blind_retry": False,
        },
    )
    _append_output("final_state", final_state)
    _append_output("verification", verification)
    return 0


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="CEP Jules Gateway v2 mutation-canary helper")
    parser.add_argument("phase", choices=("route", "preflight", "execute"))
    parser.add_argument("--output-dir", required=True)
    parser.add_argument("--intent-file")
    args = parser.parse_args(argv)
    out_dir = Path(args.output_dir)
    out_dir.mkdir(parents=True, exist_ok=True)
    try:
        if args.phase == "route":
            return route(out_dir)
        if args.phase == "preflight":
            return preflight(out_dir)
        if not args.intent_file:
            raise GatewayError(ErrorClassification.INVALID_REQUEST, "execute phase requires --intent-file")
        return execute(out_dir, Path(args.intent_file))
    except GatewayError as exc:
        try:
            envelope = _request()
        except GatewayError:
            envelope = None
        _write_json(out_dir / "receipt.json", error_receipt(envelope, exc))
        print(json.dumps({"status": "FAILED", "classification": exc.classification.value}, separators=(",", ":")))
        return 2


if __name__ == "__main__":
    raise SystemExit(main())

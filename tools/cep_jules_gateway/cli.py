from __future__ import annotations

import argparse
import json
import os
from pathlib import Path
from typing import Any

from .envelope import RequestEnvelope, parse_envelope
from .inspect_bundle import build_inspect_bundle
from .github import GitHubClient
from .jules import JulesClient
from .models import Completeness, ErrorClassification, GatewayError, ProviderOutcome
from .receipt import error_receipt, receipt_from_bundle
from .sanitize import sanitize_obj


def _write_json(path: Path, value: Any) -> None:
    path.write_text(json.dumps(sanitize_obj(value), ensure_ascii=False, sort_keys=True, indent=2) + "\n", encoding="utf-8")


def _run_read_action(envelope: RequestEnvelope, client: JulesClient) -> tuple[dict[str, Any] | None, dict[str, Any]]:
    if envelope.action == "inspect_bundle":
        bundle = build_inspect_bundle(envelope, client)
        return bundle, receipt_from_bundle(envelope, bundle)
    if envelope.action == "get_session":
        session = sanitize_obj(client.get_session(envelope.session_id or ""))
        receipt = {
            "schema_version": "cep.jules.gateway.receipt/v2",
            **envelope.public_dict(),
            "provider_outcome": ProviderOutcome.FOUND.value,
            "completeness": Completeness.COMPLETE.value,
            "provider_metadata": {
                "state": session.get("state"),
                "update_time": session.get("updateTime"),
                "observations": [o.to_dict() for o in client.observations],
            },
            "public_safe": True,
            "shadow_safe": True,
            "provider_mutation_performed": False,
        }
        return {"session": session}, sanitize_obj(receipt)
    if envelope.action == "list_activities":
        result = client.list_activities(
            envelope.session_id or "",
            page_size=envelope.options.page_size,
            max_pages=envelope.options.max_activity_pages,
        )
        payload = {"activities": sanitize_obj(result.items), "pagination": result.info.to_dict()}
        receipt = {
            "schema_version": "cep.jules.gateway.receipt/v2",
            **envelope.public_dict(),
            "provider_outcome": ProviderOutcome.FOUND.value,
            "completeness": Completeness.COMPLETE.value,
            "provider_metadata": {"activity_count_scanned": len(result.items), "pagination": result.info.to_dict()},
            "public_safe": True,
            "shadow_safe": True,
            "provider_mutation_performed": False,
        }
        return payload, sanitize_obj(receipt)
    if envelope.action == "list_sessions":
        result = client.list_sessions(page_size=envelope.options.page_size, max_pages=envelope.options.max_activity_pages)
        rows = [
            {
                "id": item.get("id"),
                "title": item.get("title"),
                "state": item.get("state"),
                "updateTime": item.get("updateTime"),
            }
            for item in result.items
        ]
        payload = {"sessions": sanitize_obj(rows), "pagination": result.info.to_dict()}
        receipt = {
            "schema_version": "cep.jules.gateway.receipt/v2",
            **envelope.public_dict(),
            "provider_outcome": ProviderOutcome.FOUND.value,
            "completeness": Completeness.COMPLETE.value,
            "provider_metadata": {"session_count_scanned": len(rows), "pagination": result.info.to_dict()},
            "public_safe": True,
            "shadow_safe": True,
            "provider_mutation_performed": False,
        }
        return payload, sanitize_obj(receipt)
    raise GatewayError(ErrorClassification.INVALID_REQUEST, "unsupported action after validation")


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="CEP Jules Gateway v2 shadow-safe CLI")
    parser.add_argument("--request-env", default="CEP_JULES_REQUEST_JSON")
    parser.add_argument("--output-dir", required=True)
    args = parser.parse_args(argv)

    out_dir = Path(args.output_dir)
    out_dir.mkdir(parents=True, exist_ok=True)
    envelope: RequestEnvelope | None = None
    try:
        raw = os.environ.get(args.request_env, "")
        envelope = parse_envelope(raw)
        github_precondition = None
        if envelope.starting_branch is not None and envelope.expected_sha is not None:
            github_client = GitHubClient(
                os.environ.get("GITHUB_TOKEN", ""),
                os.environ.get("CEP_REPOSITORY", "hamad933/Cybersecurity-Education-Platform"),
            )
            github_precondition = github_client.require_branch_head(
                envelope.starting_branch,
                envelope.expected_sha,
            )
            github_precondition["observations"] = [o.to_dict() for o in github_client.observations]

        client = JulesClient(
            os.environ.get("JULES_API_KEY", ""),
            api_base=os.environ.get("JULES_API_BASE", "https://jules.googleapis.com/v1alpha"),
        )
        payload, receipt = _run_read_action(envelope, client)
        if github_precondition is not None:
            receipt["github_precondition"] = sanitize_obj(github_precondition)
            if payload is not None:
                payload["github_precondition"] = sanitize_obj(github_precondition)
        if payload is not None:
            _write_json(out_dir / "result.json", payload)
        _write_json(out_dir / "receipt.json", receipt)
        print(json.dumps({"status": "OK", "request_id": envelope.request_id, "action": envelope.action}, separators=(",", ":")))
        return 0
    except GatewayError as exc:
        receipt = error_receipt(envelope, exc)
        _write_json(out_dir / "receipt.json", receipt)
        print(json.dumps({"status": "FAILED", "classification": exc.classification.value}, separators=(",", ":")))
        return 2


if __name__ == "__main__":
    raise SystemExit(main())

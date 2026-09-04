from __future__ import annotations

import json
from typing import Any

from .envelope import parse_envelope
from .inspect_bundle import build_inspect_bundle
from .legacy_issue_gateway import _client, _emit_exact_text, _emit_json, _gh, _payload, _rid, _sid
from .models import ErrorClassification, GatewayError
from .sanitize import sanitize_text

# Keep the legacy Issue surface at the maximum values permitted by the
# authoritative v2 InspectOptions contract. Do not invent a second range.
_MAX_OPTIONS: dict[str, Any] = {
    "page_size": 25,
    "max_activity_pages": 50,
    "max_total_items": 2_000,
    "max_provider_reads": 200,
    "max_hydration_reads": 100,
    "max_exact_text_chars": 500_000,
    "max_total_exact_text_bytes": 2_000_000,
    "max_serialized_result_bytes": 8_000_000,
}


def inspect_envelope(rid: str, sid: str, action: str):
    options = dict(_MAX_OPTIONS)
    if action == "get_agent_messages":
        options["recent_agent_messages"] = 50
    if action == "get_latest_changeset":
        options["include_patch"] = True
    if action == "get_bash_outputs":
        options["recent_bash_outputs"] = 20
        options["include_bash_output_text"] = True
    return parse_envelope(
        json.dumps(
            {
                "schema_version": "2.0",
                "request_id": rid,
                "controller_id": "PARENT",
                "lane": "PARENT",
                "action": "inspect_bundle",
                "session_id": sid,
                "options": options,
            }
        )
    )


def run_inspect() -> int:
    gh = _gh()
    try:
        payload = _payload()
        rid = _rid(payload)
        sid = _sid(payload)
        action = str(payload.get("action") or "").strip()
        allowed = {"get_plan", "get_agent_messages", "get_changeset_index", "get_latest_changeset", "get_bash_outputs"}
        if action not in allowed:
            raise GatewayError(ErrorClassification.INVALID_REQUEST, f"action not allowed: {action}")

        bundle = build_inspect_bundle(inspect_envelope(rid, sid, action), _client())
        receipt = f"JULES_INSPECT_RECEIPT request_id={rid}\naction={action}\nhttp_status=200\nsession_id={sid}"

        if action == "get_plan":
            _emit_json(gh, receipt, bundle["plan"])
        elif action == "get_agent_messages":
            _emit_json(gh, receipt, bundle["agent_messages"])
        elif action == "get_changeset_index":
            _emit_json(
                gh,
                receipt,
                {"activity_count_scanned": bundle["provider"]["activity_count_scanned"], **bundle["changesets"]},
            )
        elif action == "get_latest_changeset":
            latest = dict(bundle["changesets"]["latest_exact_patch"])
            text = str(latest.pop("unidiff_patch", ""))
            if text:
                _emit_exact_text(gh, receipt, "unidiffPatch", latest, text)
            else:
                _emit_json(gh, receipt, latest)
        else:
            evidence = bundle["bash_evidence"]
            exact = list(evidence.get("recent_exact") or [])
            index = []
            texts = []
            for i, original in enumerate(exact, start=1):
                row = dict(original)
                text = str(row.pop("output", ""))
                row["output_index"] = i
                index.append(row)
                texts.append((i, row, text))
            _emit_json(
                gh,
                receipt,
                {"activity_count_scanned": bundle["provider"]["activity_count_scanned"], "bash_outputs": index},
            )
            for i, meta, text in texts:
                if text:
                    _emit_exact_text(gh, receipt + f"\noutput_index={i}", "bashOutput", meta, text)
        return 0
    except GatewayError as exc:
        try:
            gh.comment(
                f"JULES_INSPECT_RECEIPT\noutcome=READ_FAILED\nreason={exc.classification.value}\ndetail={sanitize_text(exc.message)}"
            )
        except Exception:
            pass
        return 7


def main() -> int:
    return run_inspect()


if __name__ == "__main__":
    raise SystemExit(main())

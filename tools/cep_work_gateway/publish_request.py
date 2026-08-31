from __future__ import annotations

import argparse
import json
import pathlib
import sys

from .protocol import WorkGatewayError, parse_publish_request


def _parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Validate one Controller-authored CEP Work publication request issue")
    parser.add_argument("--issue-json", required=True)
    parser.add_argument("--repository-owner", required=True)
    parser.add_argument("--packet-out", required=True)
    return parser


def main(argv: list[str] | None = None) -> int:
    args = _parser().parse_args(argv)
    try:
        issue = json.loads(pathlib.Path(args.issue_json).read_text(encoding="utf-8"))
        if not isinstance(issue, dict):
            raise WorkGatewayError("publication issue payload shape is invalid")
        if str((issue.get("user") or {}).get("login") or "") != args.repository_owner or str(issue.get("author_association") or "") != "OWNER":
            raise WorkGatewayError("publication issue must be authored by the repository owner")
        if not str(issue.get("title") or "").startswith("[CEP-WORK-PUBLISH]"):
            raise WorkGatewayError("publication issue title does not have the governed prefix")
        packet = parse_publish_request(str(issue.get("body") or ""))
        output = packet.as_dict()
        output["bridge_issue_number"] = str(issue.get("number") or "")
        pathlib.Path(args.packet_out).write_text(json.dumps(output, sort_keys=True, indent=2) + "\n", encoding="utf-8")
        print(f"REQUEST_ID={packet.request_id}")
    except (json.JSONDecodeError, OSError, WorkGatewayError) as exc:
        print(f"WORK_PUBLICATION_REQUEST_FAILED: {exc}", file=sys.stderr)
        return 2
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

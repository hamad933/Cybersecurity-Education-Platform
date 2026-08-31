from __future__ import annotations

import argparse
import json
import pathlib
import sys

from .protocol import WorkGatewayError, reconstruct_handoff


def _parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Freeze one owner-authored CEP Work handoff issue into a verified immutable Actions artifact")
    parser.add_argument("--repository", required=True)
    parser.add_argument("--repository-owner", required=True)
    parser.add_argument("--issue-json", required=True)
    parser.add_argument("--comments-json", required=True)
    parser.add_argument("--run-id", required=True)
    parser.add_argument("--head-sha", required=True)
    parser.add_argument("--patch-out", required=True)
    parser.add_argument("--manifest-out", required=True)
    parser.add_argument("--receipt-out", required=True)
    return parser


def main(argv: list[str] | None = None) -> int:
    args = _parser().parse_args(argv)
    try:
        issue = json.loads(pathlib.Path(args.issue_json).read_text(encoding="utf-8"))
        comments = json.loads(pathlib.Path(args.comments_json).read_text(encoding="utf-8"))
        if not isinstance(issue, dict) or not isinstance(comments, list):
            raise WorkGatewayError("GitHub issue/comments payload shape is invalid")
        if not args.run_id.isdigit() or not args.head_sha or len(args.head_sha) != 40:
            raise WorkGatewayError("intake run provenance is malformed")
        handoff = reconstruct_handoff(issue, comments, repository_owner=args.repository_owner)

        patch_out = pathlib.Path(args.patch_out)
        manifest_out = pathlib.Path(args.manifest_out)
        receipt_out = pathlib.Path(args.receipt_out)
        for path in (patch_out, manifest_out, receipt_out):
            path.parent.mkdir(parents=True, exist_ok=True)
        patch_out.write_text(handoff.patch, encoding="utf-8", newline="")
        manifest_out.write_text(json.dumps(handoff.manifest.as_dict(), sort_keys=True, indent=2) + "\n", encoding="utf-8")
        receipt = {
            "schema_version": "cep.work.intake-receipt/v1",
            "repository": args.repository,
            "repository_owner": args.repository_owner,
            "handoff_issue_number": str(issue.get("number") or ""),
            "request_id": handoff.manifest.request_id,
            "controller_id": handoff.manifest.controller_id,
            "workspace": handoff.manifest.workspace,
            "logical_task": handoff.manifest.logical_task,
            "write_domain": handoff.manifest.write_domain,
            "target_branch": handoff.manifest.target_branch,
            "expected_base_sha": handoff.manifest.expected_base_sha,
            "patch_sha256": handoff.manifest.patch_sha256,
            "paths_sha256": handoff.manifest.paths_sha256,
            "compressed_sha256": handoff.manifest.compressed_sha256,
            "changed_paths": list(handoff.changed_paths),
            "patch_bytes": handoff.manifest.patch_bytes,
            "patch_chunks": handoff.manifest.patch_chunks,
            "local_commit_sha": handoff.manifest.local_commit_sha,
            "tests_status": handoff.manifest.tests_status,
            "intake_run_id": args.run_id,
            "intake_head_sha": args.head_sha,
            "postcondition": "PATCH_BYTES_AND_PATHS_BOUND_TO_OWNER_HANDOFF",
        }
        receipt_out.write_text(json.dumps(receipt, sort_keys=True, indent=2) + "\n", encoding="utf-8")
        print(f"REQUEST_ID={handoff.manifest.request_id}")
        print(f"PATCH_SHA256={handoff.manifest.patch_sha256}")
        print(f"PATHS_SHA256={handoff.manifest.paths_sha256}")
    except (json.JSONDecodeError, OSError, WorkGatewayError) as exc:
        print(f"WORK_HANDOFF_INTAKE_FAILED: {exc}", file=sys.stderr)
        return 2
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

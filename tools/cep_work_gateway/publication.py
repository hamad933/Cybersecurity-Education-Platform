from __future__ import annotations

import argparse
import json
import pathlib
import sys

from .protocol import (
    WorkGatewayError,
    canonical_patch_paths,
    parse_handoff_manifest,
    paths_sha256,
    sha256_text,
    validate_patch_safety,
)


def _parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Verify one frozen Work intake artifact against an exact Controller publication packet")
    parser.add_argument("--artifact-dir", required=True)
    parser.add_argument("--repository", required=True)
    parser.add_argument("--repository-owner", required=True)
    parser.add_argument("--request-id", required=True)
    parser.add_argument("--controller-id", required=True)
    parser.add_argument("--workspace", required=True)
    parser.add_argument("--logical-task", required=True)
    parser.add_argument("--write-domain", required=True)
    parser.add_argument("--handoff-issue-number", required=True)
    parser.add_argument("--intake-run-id", required=True)
    parser.add_argument("--intake-head-sha", required=True)
    parser.add_argument("--target-branch", required=True)
    parser.add_argument("--expected-remote-sha", required=True)
    parser.add_argument("--expected-patch-sha256", required=True)
    parser.add_argument("--expected-paths-sha256", required=True)
    parser.add_argument("--patch-out", required=True)
    parser.add_argument("--receipt-out", required=True)
    return parser


def _expect(receipt: dict[str, object], key: str, expected: str) -> None:
    actual = str(receipt.get(key) or "")
    if actual != expected:
        raise WorkGatewayError(f"intake receipt mismatch for {key}: expected={expected} actual={actual}")


def main(argv: list[str] | None = None) -> int:
    args = _parser().parse_args(argv)
    try:
        artifact = pathlib.Path(args.artifact_dir)
        manifest_path = artifact / "manifest.json"
        intake_receipt_path = artifact / "intake-receipt.json"
        patch_path = artifact / "reviewed.patch"
        manifest_text = manifest_path.read_text(encoding="utf-8")
        receipt = json.loads(intake_receipt_path.read_text(encoding="utf-8"))
        patch = patch_path.read_text(encoding="utf-8")
        if not isinstance(receipt, dict) or receipt.get("schema_version") != "cep.work.intake-receipt/v1":
            raise WorkGatewayError("intake receipt schema is invalid")
        manifest = parse_handoff_manifest(manifest_text)

        expected = {
            "repository": args.repository,
            "repository_owner": args.repository_owner,
            "request_id": args.request_id,
            "controller_id": args.controller_id,
            "workspace": args.workspace,
            "logical_task": args.logical_task,
            "write_domain": args.write_domain,
            "handoff_issue_number": args.handoff_issue_number,
            "intake_run_id": args.intake_run_id,
            "intake_head_sha": args.intake_head_sha,
            "target_branch": args.target_branch,
            "expected_base_sha": args.expected_remote_sha,
            "patch_sha256": args.expected_patch_sha256,
            "paths_sha256": args.expected_paths_sha256,
        }
        for key, value in expected.items():
            _expect(receipt, key, value)

        manifest_expected = {
            "request_id": args.request_id,
            "controller_id": args.controller_id,
            "workspace": args.workspace,
            "logical_task": args.logical_task,
            "write_domain": args.write_domain,
            "target_branch": args.target_branch,
            "expected_base_sha": args.expected_remote_sha,
            "patch_sha256": args.expected_patch_sha256,
            "paths_sha256": args.expected_paths_sha256,
        }
        for key, value in manifest_expected.items():
            if str(getattr(manifest, key)) != value:
                raise WorkGatewayError(f"manifest mismatch for {key}")

        if sha256_text(patch) != args.expected_patch_sha256:
            raise WorkGatewayError("artifact patch digest does not match Controller publication packet")
        paths = canonical_patch_paths(patch)
        validate_patch_safety(patch, paths)
        if paths_sha256(paths) != args.expected_paths_sha256:
            raise WorkGatewayError("artifact changed-path digest does not match Controller publication packet")
        if list(paths) != list(receipt.get("changed_paths") or []):
            raise WorkGatewayError("artifact changed paths differ from intake receipt")

        patch_out = pathlib.Path(args.patch_out)
        result_out = pathlib.Path(args.receipt_out)
        patch_out.parent.mkdir(parents=True, exist_ok=True)
        result_out.parent.mkdir(parents=True, exist_ok=True)
        patch_out.write_text(patch, encoding="utf-8", newline="")
        result = dict(receipt)
        result.update({
            "schema_version": "cep.work.publication-preflight/v1",
            "publication_postcondition": "FROZEN_INTAKE_ARTIFACT_MATCHES_CONTROLLER_PACKET",
        })
        result_out.write_text(json.dumps(result, sort_keys=True, indent=2) + "\n", encoding="utf-8")
        print("WORK_PUBLICATION_PREFLIGHT_VERIFIED")
    except (json.JSONDecodeError, OSError, WorkGatewayError) as exc:
        print(f"WORK_PUBLICATION_PREFLIGHT_FAILED: {exc}", file=sys.stderr)
        return 2
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

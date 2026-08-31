from __future__ import annotations

import base64
import gzip
import hashlib
import json
import unittest

from tools.cep_work_gateway.protocol import (
    WorkGatewayError,
    canonical_patch_paths,
    parse_publish_request,
    paths_sha256,
    reconstruct_handoff,
)


PATCH = """diff --git a/app/Example.php b/app/Example.php
index 1111111..2222222 100644
--- a/app/Example.php
+++ b/app/Example.php
@@ -1 +1 @@
-old
+new
"""


def digest(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def make_handoff(*, patch: str = PATCH, path_digest: str | None = None, controller: str = "B", workspace: str = "W02"):
    raw = patch.encode("utf-8")
    compressed = gzip.compress(raw, mtime=0)
    encoded = base64.b64encode(compressed).decode("ascii")
    chunks = [encoded[i:i + 100] for i in range(0, len(encoded), 100)]
    manifest = {
        "schema_version": "cep.work.handoff/v1",
        "request_id": "REQ-001",
        "controller_id": controller,
        "workspace": workspace,
        "logical_task": "W02-EDITOR-01",
        "write_domain": "W02-EDITOR",
        "target_branch": "work/w02-editor-01",
        "expected_base_sha": "a" * 40,
        "patch_encoding": "gzip+base64",
        "patch_sha256": digest(raw),
        "paths_sha256": path_digest or paths_sha256(canonical_patch_paths(patch)),
        "compressed_sha256": digest(compressed),
        "patch_chunks": len(chunks),
        "patch_bytes": len(raw),
        "local_commit_sha": "b" * 40,
        "tests_status": "PASS",
    }
    issue = {
        "number": 17,
        "title": "[CEP-WORK-HANDOFF] REQ-001",
        "body": json.dumps(manifest),
        "user": {"login": "owner"},
        "author_association": "OWNER",
    }
    comments = [
        {
            "body": f"CEP_WORK_PATCH_CHUNK request_id=REQ-001 index={i} total={len(chunks)}\n{chunk}",
            "user": {"login": "owner"},
            "author_association": "OWNER",
        }
        for i, chunk in enumerate(chunks, 1)
    ]
    comments.append({
        "body": f"CEP_WORK_HANDOFF_COMPLETE request_id=REQ-001 patch_sha256={manifest['patch_sha256']} compressed_sha256={manifest['compressed_sha256']}",
        "user": {"login": "owner"},
        "author_association": "OWNER",
    })
    return issue, comments


class WorkGatewayProtocolTest(unittest.TestCase):
    def test_reconstructs_valid_owner_handoff(self):
        issue, comments = make_handoff()
        result = reconstruct_handoff(issue, comments, repository_owner="owner")
        self.assertEqual(result.patch, PATCH)
        self.assertEqual(result.changed_paths, ("app/Example.php",))
        self.assertEqual(result.manifest.controller_id, "B")
        self.assertEqual(result.manifest.workspace, "W02")

    def test_rejects_current_topology_mismatch(self):
        issue, comments = make_handoff(controller="B", workspace="W03")
        with self.assertRaisesRegex(WorkGatewayError, "mapping"):
            reconstruct_handoff(issue, comments, repository_owner="owner")

    def test_rejects_missing_chunk(self):
        issue, comments = make_handoff()
        comments.pop(0)
        with self.assertRaisesRegex(WorkGatewayError, "incomplete"):
            reconstruct_handoff(issue, comments, repository_owner="owner")

    def test_rejects_reserved_gateway_path(self):
        patch = PATCH.replace("app/Example.php", ".github/workflows/pwn.yml")
        issue, comments = make_handoff(patch=patch)
        with self.assertRaisesRegex(WorkGatewayError, "reserved"):
            reconstruct_handoff(issue, comments, repository_owner="owner")

    def test_rejects_path_digest_drift(self):
        issue, comments = make_handoff(path_digest="f" * 64)
        with self.assertRaisesRegex(WorkGatewayError, "changed-path"):
            reconstruct_handoff(issue, comments, repository_owner="owner")

    def test_publish_request_uses_five_workspace_topology(self):
        value = {
            "schema_version": "cep.work.publish/v1",
            "request_id": "REQ-001",
            "controller_id": "E",
            "workspace": "W05",
            "logical_task": "W05-BACKUP-01",
            "write_domain": "W05-BACKUP",
            "handoff_issue_number": "20",
            "intake_run_id": "123456",
            "intake_head_sha": "a" * 40,
            "target_branch": "work/w05-backup-01",
            "expected_remote_sha": "b" * 40,
            "expected_patch_sha256": "c" * 64,
            "expected_paths_sha256": "d" * 64,
        }
        packet = parse_publish_request(json.dumps(value))
        self.assertEqual(packet.controller_id, "E")
        self.assertEqual(packet.workspace, "W05")

    def test_publish_request_never_targets_main(self):
        value = {
            "schema_version": "cep.work.publish/v1",
            "request_id": "REQ-001",
            "controller_id": "PARENT",
            "workspace": "PARENT",
            "logical_task": "INFRA-01",
            "write_domain": "INFRA",
            "handoff_issue_number": "20",
            "intake_run_id": "123456",
            "intake_head_sha": "a" * 40,
            "target_branch": "main",
            "expected_remote_sha": "b" * 40,
            "expected_patch_sha256": "c" * 64,
            "expected_paths_sha256": "d" * 64,
        }
        with self.assertRaisesRegex(WorkGatewayError, "never target"):
            parse_publish_request(json.dumps(value))


if __name__ == "__main__":
    unittest.main()

from __future__ import annotations

import unittest
from types import SimpleNamespace

from tools.cep_jules_gateway.publication import (
    PublicationError,
    canonical_patch_paths,
    fetch_publication_candidate,
    paths_sha256,
    sha256_text,
    validate_patch_safety,
    validate_routing,
)


PATCH = """diff --git a/app/Example.php b/app/Example.php
index 1111111..2222222 100644
--- a/app/Example.php
+++ b/app/Example.php
@@ -1 +1 @@
-old
+new
"""


class FakeClient:
    def __init__(self, patch: str = PATCH):
        self.patch = patch

    def get_session(self, session_id: str):
        return {"id": session_id, "state": "COMPLETED", "updateTime": "2026-08-29T20:00:00Z"}

    def list_activities(self, session_id: str, *, page_size: int, max_pages: int, max_items: int = 2_000):
        item = {
            "name": f"sessions/{session_id}/activities/a1",
            "createTime": "2026-08-29T19:59:00Z",
            "artifacts": [{"changeSet": {}}],
        }
        return SimpleNamespace(items=[item], info=SimpleNamespace(complete=True))

    def get_activity(self, activity_name: str):
        return {
            "name": activity_name,
            "createTime": "2026-08-29T19:59:00Z",
            "artifacts": [
                {
                    "changeSet": {
                        "source": "sources/github/hamad933/Cybersecurity-Education-Platform",
                        "gitPatch": {
                            "baseCommitId": "a" * 40,
                            "unidiffPatch": self.patch,
                        },
                    }
                }
            ],
        }


class PublicationTests(unittest.TestCase):
    def test_controller_lane_mapping_and_main_prohibition(self):
        validate_routing(
            controller_id="B",
            lane="W01_W02",
            request_id="req-1",
            logical_task="CEP-EDITOR-CORR-01",
            write_domain="W02_EDITOR",
            target_branch="work/cep-editor-corr-01-r01",
        )
        with self.assertRaises(PublicationError):
            validate_routing(
                controller_id="B",
                lane="W05",
                request_id="req-1",
                logical_task="CEP-EDITOR-CORR-01",
                write_domain="W02_EDITOR",
                target_branch="work/cep-editor-corr-01-r01",
            )
        with self.assertRaises(PublicationError):
            validate_routing(
                controller_id="PARENT",
                lane="PARENT",
                request_id="req-1",
                logical_task="CEP-X",
                write_domain="X",
                target_branch="main",
            )

    def test_path_digest_is_canonical(self):
        paths = canonical_patch_paths(PATCH)
        self.assertEqual(paths, ("app/Example.php",))
        self.assertEqual(paths_sha256(paths), sha256_text("app/Example.php\n"))

    def test_reserved_gateway_path_is_rejected(self):
        patch = PATCH.replace("app/Example.php", ".github/workflows/evil.yml")
        paths = canonical_patch_paths(patch)
        with self.assertRaises(PublicationError):
            validate_patch_safety(patch, paths)

    def test_high_confidence_secret_is_rejected(self):
        patch = PATCH.replace("+new", "+ghp_123456789012345678901234567890")
        paths = canonical_patch_paths(patch)
        with self.assertRaises(PublicationError):
            validate_patch_safety(patch, paths)

    def test_exact_quiescent_changeset_is_prepared(self):
        paths = canonical_patch_paths(PATCH)
        candidate = fetch_publication_candidate(
            FakeClient(),
            repository="hamad933/Cybersecurity-Education-Platform",
            session_id="123456",
            expected_session_state="COMPLETED",
            expected_session_update_time="2026-08-29T20:00:00Z",
            expected_base_sha="a" * 40,
            expected_review_sha256=sha256_text(PATCH),
            expected_paths_sha256=paths_sha256(paths),
        )
        self.assertEqual(candidate.base_commit_id, "a" * 40)
        self.assertEqual(candidate.changed_paths, paths)

    def test_session_update_drift_fails_closed(self):
        paths = canonical_patch_paths(PATCH)
        with self.assertRaises(PublicationError):
            fetch_publication_candidate(
                FakeClient(),
                repository="hamad933/Cybersecurity-Education-Platform",
                session_id="123456",
                expected_session_state="COMPLETED",
                expected_session_update_time="2026-08-29T20:01:00Z",
                expected_base_sha="a" * 40,
                expected_review_sha256=sha256_text(PATCH),
                expected_paths_sha256=paths_sha256(paths),
            )

    def test_patch_digest_drift_fails_closed(self):
        paths = canonical_patch_paths(PATCH)
        with self.assertRaises(PublicationError):
            fetch_publication_candidate(
                FakeClient(),
                repository="hamad933/Cybersecurity-Education-Platform",
                session_id="123456",
                expected_session_state="COMPLETED",
                expected_session_update_time="2026-08-29T20:00:00Z",
                expected_base_sha="a" * 40,
                expected_review_sha256="b" * 64,
                expected_paths_sha256=paths_sha256(paths),
            )


if __name__ == "__main__":
    unittest.main()

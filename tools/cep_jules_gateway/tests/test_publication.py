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
    def __init__(
        self,
        patch: str = PATCH,
        *,
        activities: list[dict] | None = None,
        complete: bool = True,
        max_safe_page_size: int | None = None,
    ):
        self.patch = patch
        self.activities = activities
        self.complete = complete
        self.max_safe_page_size = max_safe_page_size
        self.list_calls: list[tuple[int, int, int]] = []

    def get_session(self, session_id: str):
        return {"id": session_id, "state": "COMPLETED", "updateTime": "2026-08-29T20:00:00Z"}

    def list_activities(self, session_id: str, *, page_size: int, max_pages: int, max_items: int = 2_000):
        self.list_calls.append((page_size, max_pages, max_items))
        if self.max_safe_page_size is not None and page_size > self.max_safe_page_size:
            raise RuntimeError("simulated provider response-size overflow")
        items = self.activities
        if items is None:
            items = [
                {
                    "name": f"sessions/{session_id}/activities/a1",
                    "createTime": "2026-08-29T19:59:00Z",
                    "artifacts": [{"changeSet": {}}],
                }
            ]
        return SimpleNamespace(items=items, info=SimpleNamespace(complete=self.complete))

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
    def _validate(self, controller_id: str, lane: str) -> None:
        validate_routing(
            controller_id=controller_id,
            lane=lane,
            request_id="req-1",
            logical_task="CEP-TOPOLOGY-CHECK-01",
            write_domain="BOUNDED_DOMAIN",
            target_branch="work/cep-topology-check-r01",
        )

    def test_current_five_controller_lane_matrix(self):
        for controller_id, lane in (
            ("A", "W01"),
            ("B", "W02"),
            ("C", "W03"),
            ("D", "W04"),
            ("E", "W05"),
        ):
            with self.subTest(controller_id=controller_id, lane=lane):
                self._validate(controller_id, lane)

    def test_cross_workspace_and_legacy_child_routes_fail_closed(self):
        invalid_pairs = (
            ("A", "W02"),
            ("B", "W01"),
            ("C", "W04"),
            ("D", "W03"),
            ("E", "W01"),
            ("A", "W01_W02"),
            ("B", "W01_W02"),
            ("C", "W03_W04"),
            ("D", "W03_W04"),
        )
        for controller_id, lane in invalid_pairs:
            with self.subTest(controller_id=controller_id, lane=lane):
                with self.assertRaises(PublicationError):
                    self._validate(controller_id, lane)

    def test_parent_retains_current_and_legacy_fallback_routes(self):
        for lane in ("PARENT", "W01", "W02", "W03", "W04", "W05", "W01_W02", "W03_W04"):
            with self.subTest(lane=lane):
                self._validate("PARENT", lane)

    def test_main_target_is_prohibited(self):
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

    def test_large_session_uses_response_safe_pagination_and_selects_latest_changeset(self):
        session_id = "123456"
        activities = [
            {
                "name": f"sessions/{session_id}/activities/noop-{index:03d}",
                "createTime": "2026-08-29T19:00:00Z",
                "artifacts": [],
            }
            for index in range(400)
        ]
        activities.extend(
            [
                {
                    "name": f"sessions/{session_id}/activities/prior",
                    "createTime": "2026-08-29T19:58:00Z",
                    "artifacts": [{"changeSet": {}}],
                },
                {
                    "name": f"sessions/{session_id}/activities/latest",
                    "createTime": "2026-08-29T19:59:00Z",
                    "artifacts": [{"changeSet": {}}],
                },
            ]
        )
        client = FakeClient(activities=activities, max_safe_page_size=25)
        paths = canonical_patch_paths(PATCH)
        candidate = fetch_publication_candidate(
            client,
            repository="hamad933/Cybersecurity-Education-Platform",
            session_id=session_id,
            expected_session_state="COMPLETED",
            expected_session_update_time="2026-08-29T20:00:00Z",
            expected_base_sha="a" * 40,
            expected_review_sha256=sha256_text(PATCH),
            expected_paths_sha256=paths_sha256(paths),
        )
        self.assertEqual([(25, 80, 2_000)], client.list_calls)
        self.assertEqual(f"sessions/{session_id}/activities/latest", candidate.activity_name)

    def test_incomplete_large_session_scan_fails_closed(self):
        paths = canonical_patch_paths(PATCH)
        with self.assertRaises(PublicationError):
            fetch_publication_candidate(
                FakeClient(complete=False),
                repository="hamad933/Cybersecurity-Education-Platform",
                session_id="123456",
                expected_session_state="COMPLETED",
                expected_session_update_time="2026-08-29T20:00:00Z",
                expected_base_sha="a" * 40,
                expected_review_sha256=sha256_text(PATCH),
                expected_paths_sha256=paths_sha256(paths),
            )

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

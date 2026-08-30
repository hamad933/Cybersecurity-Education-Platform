from __future__ import annotations

import pathlib
import unittest


class V23BridgeStaticTests(unittest.TestCase):
    def setUp(self) -> None:
        self.bridge = pathlib.Path(
            ".github/workflows/cep-jules-v23-issue-publication.yml"
        ).read_text(encoding="utf-8")
        self.publisher = pathlib.Path(
            ".github/workflows/cep-jules-v2-publication.yml"
        ).read_text(encoding="utf-8")

    def test_bridge_is_owner_issue_only_with_minimal_permissions(self):
        self.assertIn("issues:\n    types: [opened]", self.bridge)
        self.assertIn("github.actor == github.repository_owner", self.bridge)
        self.assertIn("github.event.issue.author_association == 'OWNER'", self.bridge)
        self.assertIn("startsWith(github.event.issue.title, '[CEP-V23-PUBLISH]')", self.bridge)
        self.assertIn("contents: read", self.bridge)
        self.assertIn("actions: write", self.bridge)
        self.assertIn("issues: write", self.bridge)
        self.assertNotIn("contents: write", self.bridge)

    def test_bridge_validates_exact_governed_packet(self):
        self.assertIn("if set(data) != expected_fields", self.bridge)
        self.assertIn('re.fullmatch(r"[0-9]+", data["session_id"])', self.bridge)
        self.assertIn('re.fullmatch(r"[0-9a-fA-F]{40}", data["expected_remote_sha"])', self.bridge)
        self.assertIn('re.fullmatch(r"[0-9a-fA-F]{64}", data["expected_review_sha256"])', self.bridge)
        self.assertIn('re.fullmatch(r"[0-9a-fA-F]{64}", data["expected_paths_sha256"])', self.bridge)
        self.assertIn('data["expected_session_state"] not in {"COMPLETED", "AWAITING_USER_FEEDBACK"}', self.bridge)

    def test_bridge_dispatches_publisher_with_issue_provenance(self):
        self.assertIn("bridge_issue_number", self.bridge)
        self.assertIn("cep-jules-v2-publication.yml/dispatches", self.bridge)
        self.assertIn("--input dispatch.json", self.bridge)
        self.assertIn("CEP_V23_BRIDGE_WRITE_INTENT", self.bridge)
        self.assertIn("blind_retry=false", self.bridge)

    def test_publisher_preserves_direct_owner_path(self):
        self.assertIn('if [ "$ACTOR" = "$REPO_OWNER" ]; then', self.publisher)
        self.assertIn("DIRECT_OWNER_DISPATCH_VERIFIED", self.publisher)
        self.assertIn('test "${{ github.ref }}" = "refs/heads/main"', self.publisher)

    def test_publisher_allows_only_builtin_bridge_actor_for_non_owner(self):
        self.assertIn('test "$ACTOR" = "github-actions[bot]"', self.publisher)
        self.assertIn("OWNER_GATE_FAILED actor=$ACTOR", self.publisher)
        self.assertIn("BRIDGE_ISSUE_NUMBER", self.publisher)

    def test_publisher_independently_verifies_owner_issue_and_exact_body(self):
        self.assertIn('issue.get("repository_url")', self.publisher)
        self.assertIn('if "pull_request" in issue', self.publisher)
        self.assertIn('issue.get("user", {}).get("login") != owner', self.publisher)
        self.assertIn('issue.get("author_association") != "OWNER"', self.publisher)
        self.assertIn('[CEP-V23-PUBLISH]', self.publisher)
        self.assertIn("if set(body) != set(expected)", self.publisher)
        self.assertIn("if body != expected", self.publisher)
        self.assertIn("BRIDGE_PROVENANCE_VERIFIED", self.publisher)

    def test_publisher_permission_change_is_minimal(self):
        self.assertIn("permissions:\n  contents: write\n  issues: read", self.publisher)


if __name__ == "__main__":
    unittest.main()

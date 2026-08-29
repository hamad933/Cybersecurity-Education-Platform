from __future__ import annotations

import hashlib
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]


def git_blob_sha(path: Path) -> str:
    data = path.read_bytes()
    return hashlib.sha1(f"blob {len(data)}\0".encode("ascii") + data).hexdigest()


class ShadowSecurityWorkflowTests(unittest.TestCase):
    def test_v1_and_read_only_foundation_workflows_remain_byte_identical(self):
        expected = {
            ".github/workflows/cep-jules-control.yml": "931a24d00c431c8d56de86726e0aa3ac8043eed6",
            ".github/workflows/cep-jules-evidence.yml": "19030ce70a5527a27a2a4b690c3d5d624d1c28d8",
            ".github/workflows/cep-jules-inspect.yml": "dee7df76f4f90c67a0058571dbd93c85206c4ae2",
            ".github/workflows/cep-jules-v2.yml": "e461b94a34c55b8d1c4f6b80ab599afa6bdd0554",
        }
        for relative, expected_sha in expected.items():
            self.assertEqual(expected_sha, git_blob_sha(ROOT / relative), relative)

    def test_mutation_gateway_is_explicit_dispatch_only(self):
        outer = (ROOT / ".github/workflows/cep-jules-v2-mutation.yml").read_text(encoding="utf-8")
        worker = (ROOT / ".github/workflows/cep-jules-v2-mutation-worker.yml").read_text(encoding="utf-8")
        self.assertIn("workflow_dispatch:", outer)
        self.assertNotIn("issues:", outer)
        self.assertNotIn("pull_request:", outer)
        self.assertIn("workflow_call:", worker)

    def test_nested_request_and_effect_serialization_is_non_cancelling(self):
        outer = (ROOT / ".github/workflows/cep-jules-v2-mutation.yml").read_text(encoding="utf-8")
        worker = (ROOT / ".github/workflows/cep-jules-v2-mutation-worker.yml").read_text(encoding="utf-8")
        self.assertIn("needs.route.outputs.request_key", outer)
        self.assertIn("inputs.effect_key", worker)
        self.assertGreaterEqual(outer.count("cancel-in-progress: false"), 1)
        self.assertGreaterEqual(worker.count("cancel-in-progress: false"), 2)

    def test_transport_is_reference_only_and_owner_main_gated(self):
        outer = (ROOT / ".github/workflows/cep-jules-v2-mutation.yml").read_text(encoding="utf-8")
        worker = (ROOT / ".github/workflows/cep-jules-v2-mutation-worker.yml").read_text(encoding="utf-8")
        self.assertNotIn("prompt:", outer)
        self.assertNotIn("title:", outer)
        self.assertIn("instruction_ref:", outer)
        self.assertIn("instruction_digest:", outer)
        for text in (outer, worker):
            self.assertIn('test "$ACTOR" = "$OWNER"', text)
            self.assertIn('test "$REF" = "refs/heads/main"', text)

    def test_canary_has_no_repository_issue_or_pr_write_permission(self):
        combined = "\n".join(
            (ROOT / path).read_text(encoding="utf-8")
            for path in (
                ".github/workflows/cep-jules-v2-mutation.yml",
                ".github/workflows/cep-jules-v2-mutation-worker.yml",
            )
        )
        self.assertNotIn("contents: write", combined)
        self.assertNotIn("issues: write", combined)
        self.assertNotIn("pull-requests: write", combined)

    def test_only_jules_secret_is_forwarded(self):
        outer = (ROOT / ".github/workflows/cep-jules-v2-mutation.yml").read_text(encoding="utf-8")
        self.assertNotIn("secrets: inherit", outer)
        self.assertIn("JULES_API_KEY: ${{ secrets.JULES_API_KEY }}", outer)

    def test_legacy_session_binding_is_single_artifact_before_provider_write(self):
        worker = (ROOT / ".github/workflows/cep-jules-v2-mutation-worker.yml").read_text(encoding="utf-8")
        self.assertIn("Persist single atomic legacy/session binding before mutation", worker)
        self.assertLess(
            worker.index("Persist single atomic legacy/session binding before mutation"),
            worker.index("Final pre-read, one provider write, exact activity post-proof"),
        )

    def test_reconciliation_persists_effect_and_binding_before_terminal_request_resolution(self):
        worker = (ROOT / ".github/workflows/cep-jules-v2-mutation-worker.yml").read_text(encoding="utf-8")
        effect = worker.index("Persist create-effect reconciliation before request resolution")
        terminal = worker.index("Persist request reconciliation terminal state last")
        self.assertLess(effect, terminal)
        self.assertLess(worker.index("Atomically bind reconciled created session"), terminal)


if __name__ == "__main__":
    unittest.main()

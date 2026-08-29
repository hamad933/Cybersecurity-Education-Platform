from __future__ import annotations

import unittest
from pathlib import Path


class WorkflowContractTests(unittest.TestCase):
    def setUp(self):
        self.root = Path(__file__).resolve().parents[3]
        self.workflow = (self.root / ".github/workflows/cep-jules-v2.yml").read_text(encoding="utf-8")

    def test_direct_dispatch_and_thin_cli(self):
        self.assertIn("workflow_dispatch:", self.workflow)
        self.assertIn("python -m tools.cep_jules_gateway.cli", self.workflow)
        self.assertNotIn("python - <<", self.workflow)

    def test_no_v1_replacement_or_mutating_automation(self):
        lowered = self.workflow.lower()
        for forbidden in ("pull_request:", "merge", "deploy", "release", "approveplan", "sendmessage", "sessions:"):
            self.assertNotIn(forbidden, lowered)

    def test_read_only_permissions(self):
        self.assertIn("permissions:\n  contents: read", self.workflow)
        self.assertNotIn("contents: write", self.workflow)
        self.assertNotIn("issues: write", self.workflow)


if __name__ == "__main__":
    unittest.main()

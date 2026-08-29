from __future__ import annotations

import hashlib
import json
import unittest
from pathlib import Path

from tools.cep_jules_gateway.effect import intent_identity
from tools.cep_jules_gateway.envelope import parse_envelope
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError


ROOT = Path(__file__).resolve().parents[3]


def git_blob_sha(path: Path) -> str:
    data = path.read_bytes()
    return hashlib.sha1(f"blob {len(data)}\0".encode("ascii") + data).hexdigest()


class ShadowSecurityWorkflowTests(unittest.TestCase):
    def test_v1_workflows_are_byte_for_byte_unchanged_from_foundation(self):
        expected = {
            ".github/workflows/cep-jules-control.yml": "931a24d00c431c8d56de86726e0aa3ac8043eed6",
            ".github/workflows/cep-jules-evidence.yml": "19030ce70a5527a27a2a4b690c3d5d624d1c28d8",
            ".github/workflows/cep-jules-inspect.yml": "dee7df76f4f90c67a0058571dbd93c85206c4ae2",
            ".github/workflows/cep-jules-v2.yml": "e461b94a34c55b8d1c4f6b80ab599afa6bdd0554",
        }
        for relative, expected_sha in expected.items():
            self.assertEqual(expected_sha, git_blob_sha(ROOT / relative), relative)

    def test_mutation_canary_is_explicit_dispatch_only(self):
        outer = (ROOT / ".github/workflows/cep-jules-v2-mutation.yml").read_text(encoding="utf-8")
        worker = (ROOT / ".github/workflows/cep-jules-v2-mutation-worker.yml").read_text(encoding="utf-8")
        self.assertIn("workflow_dispatch:", outer)
        self.assertNotIn("issues:", outer)
        self.assertNotIn("pull_request:", outer)
        self.assertIn("workflow_call:", worker)
        self.assertNotIn("issues:", worker)
        self.assertNotIn("pull_request:", worker)

    def test_request_and_effect_serialization_are_nested_and_non_cancelling(self):
        outer = (ROOT / ".github/workflows/cep-jules-v2-mutation.yml").read_text(encoding="utf-8")
        worker = (ROOT / ".github/workflows/cep-jules-v2-mutation-worker.yml").read_text(encoding="utf-8")
        self.assertIn("needs.route.outputs.request_key", outer)
        self.assertIn("inputs.effect_key", worker)
        self.assertIn("cancel-in-progress: false", outer)
        self.assertIn("cancel-in-progress: false", worker)

    def test_session_bindings_are_durably_persisted_around_mutation(self):
        outer = (ROOT / ".github/workflows/cep-jules-v2-mutation.yml").read_text(encoding="utf-8")
        worker = (ROOT / ".github/workflows/cep-jules-v2-mutation-worker.yml").read_text(encoding="utf-8")
        self.assertIn("session_binding_marker: ${{ steps.route.outputs.session_binding_marker }}", outer)
        self.assertIn("session_binding_specific_marker: ${{ steps.route.outputs.session_binding_specific_marker }}", outer)
        self.assertIn("Persist generic session binding before provider write", worker)
        self.assertIn("Persist exact session task/domain binding before provider write", worker)
        self.assertIn("Persist binding for newly created verified session", worker)
        self.assertIn("Persist exact task/domain binding for newly created verified session", worker)
        self.assertLess(worker.index("Persist generic session binding before provider write"), worker.index("Final pre-read, one provider write"))
        self.assertLess(worker.index("Persist exact session task/domain binding before provider write"), worker.index("Final pre-read, one provider write"))

    def test_canary_has_no_repository_write_or_issue_write_permission(self):
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

    def test_only_jules_secret_is_forwarded_to_worker(self):
        outer = (ROOT / ".github/workflows/cep-jules-v2-mutation.yml").read_text(encoding="utf-8")
        self.assertNotIn("secrets: inherit", outer)
        self.assertIn("JULES_API_KEY: ${{ secrets.JULES_API_KEY }}", outer)

    def test_mutation_request_is_passed_as_environment_data_not_shell_program(self):
        combined = "\n".join(
            (ROOT / path).read_text(encoding="utf-8")
            for path in (
                ".github/workflows/cep-jules-v2-mutation.yml",
                ".github/workflows/cep-jules-v2-mutation-worker.yml",
            )
        )
        self.assertNotIn("eval ", combined)
        self.assertNotIn("bash -c", combined)
        self.assertIn("CEP_JULES_REQUEST_JSON:", combined)
        self.assertIn("python -m tools.cep_jules_gateway.mutation_cli", combined)

    def test_controller_lane_mismatch_fails_closed_in_mutation_schema(self):
        payload = {
            "schema_version": "2.1",
            "request_id": "REQ-BAD-LANE",
            "controller_id": "A",
            "lane": "W05",
            "logical_task_id": "CEP-TASK",
            "write_domain": "W05/sysops",
            "action": "send_message",
            "session_id": "123",
            "expected_state": "IN_PROGRESS",
            "execution_mode": "MUTATION_CANARY",
            "prompt": "safe",
        }
        with self.assertRaises(GatewayError) as ctx:
            parse_envelope(json.dumps(payload))
        self.assertEqual(ErrorClassification.INVALID_REQUEST, ctx.exception.classification)

    def test_secret_like_prompt_is_rejected_before_runtime(self):
        payload = {
            "schema_version": "2.1",
            "request_id": "REQ-SECRET",
            "controller_id": "A",
            "lane": "W03_W04",
            "logical_task_id": "CEP-TASK",
            "write_domain": "W03/simulator",
            "action": "send_message",
            "session_id": "123",
            "expected_state": "IN_PROGRESS",
            "execution_mode": "MUTATION_CANARY",
            "prompt": "Authorization: Bearer abcdefghijklmnopqrstuvwxyz",
        }
        with self.assertRaises(GatewayError):
            parse_envelope(json.dumps(payload))

    def test_shell_metacharacters_remain_data_and_are_not_publicly_emitted(self):
        payload = {
            "schema_version": "2.1",
            "request_id": "REQ-SHELL-DATA",
            "controller_id": "A",
            "lane": "W03_W04",
            "logical_task_id": "CEP-TASK",
            "write_domain": "W03/simulator",
            "action": "send_message",
            "session_id": "123",
            "expected_state": "IN_PROGRESS",
            "execution_mode": "MUTATION_CANARY",
            "prompt": "Treat $(touch /tmp/never) and `id` as literal review text.",
        }
        envelope = parse_envelope(json.dumps(payload))
        self.assertNotIn("prompt", envelope.public_dict())
        digest = intent_identity(envelope)
        self.assertEqual(64, len(digest))
        self.assertNotIn("touch", digest)


if __name__ == "__main__":
    unittest.main()

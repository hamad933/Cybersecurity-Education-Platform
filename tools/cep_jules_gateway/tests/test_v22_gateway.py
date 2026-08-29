from __future__ import annotations

import json
import unittest
from datetime import UTC, datetime, timedelta
from pathlib import Path

from tools.cep_jules_gateway.digest import sha256_text
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError, PaginationInfo
from tools.cep_jules_gateway.pagination import PaginationResult
from tools.cep_jules_gateway.v22_contract import parse_v22
from tools.cep_jules_gateway.v22_runtime import bridge_digest, bridge_message, bridge_title, execute, preflight, reconcile
from tools.cep_jules_gateway.v22_state import (
    DurableState,
    binding_marker,
    effect_key,
    intent_identity,
    request_marker,
    require_session_binding,
)

ROOT = Path(__file__).resolve().parents[3]


def mutation_payload(**overrides):
    value = {
        "schema_version": "2.2",
        "request_id": "REQ-V22-1",
        "controller_id": "A",
        "lane": "W03_W04",
        "logical_task_id": "CEP-W03-TASK",
        "action": "send_message",
        "write_domain": "W03/simulator",
        "execution_mode": "MUTATION_CANARY",
        "session_id": "123",
        "expected_state": "IN_PROGRESS",
        "expected_session_update_time": "u1",
        "instruction_ref": "drive:ABCDEFGHIJKLMN123456",
        "instruction_digest": "a" * 64,
        "instruction_action": "CONTINUE",
    }
    value.update(overrides)
    return value


def create_env(request_id="REQ-CREATE"):
    return parse_v22(json.dumps(mutation_payload(
        request_id=request_id,
        action="create_session",
        session_id=None,
        expected_state=None,
        expected_session_update_time=None,
        starting_branch="work/cep-w03-task",
        expected_sha="b" * 40,
        instruction_action="EXECUTE",
    )))


class FakeGitHub:
    def __init__(self):
        self.rows = {}
        self.branch_sha = "b" * 40

    def add(self, name, age_seconds=120):
        created = (datetime.now(UTC) - timedelta(seconds=age_seconds)).isoformat().replace("+00:00", "Z")
        self.rows.setdefault(name, []).append({"name": name, "expired": False, "created_at": created})

    def list_active_artifacts_by_name(self, name):
        return list(self.rows.get(name, []))

    def list_active_artifacts_by_prefix(self, prefix):
        return [item for name, items in self.rows.items() if name.startswith(prefix) for item in items]

    def require_branch_head(self, branch, expected_sha):
        if expected_sha != self.branch_sha:
            raise GatewayError(ErrorClassification.INVALID_STATE, "branch drift")
        return {"actual_sha": self.branch_sha}


class FakeJules:
    def __init__(self):
        self.session = {"id": "123", "state": "IN_PROGRESS", "updateTime": "u1", "title": "legacy"}
        self.activities = []
        self.sessions = []
        self.writes = []
        self.add_exact_message = True

    def list_sources(self):
        return [{"name": "sources/github/hamad933/Cybersecurity-Education-Platform"}]

    def get_session(self, session_id):
        if session_id == "123":
            return dict(self.session)
        for row in self.sessions:
            if str(row.get("id")) == str(session_id):
                return dict(row)
        raise GatewayError(ErrorClassification.NOT_FOUND, "session not found")

    def list_activities(self, session_id, *, page_size, max_pages, max_items):
        rows = [dict(item) for item in self.activities]
        return PaginationResult(rows, PaginationInfo(1, len(rows), True, max_pages))

    def list_sessions(self, *, page_size, max_pages, max_items):
        rows = [dict(item) for item in self.sessions]
        return PaginationResult(rows, PaginationInfo(1, len(rows), True, max_pages))

    def send_message(self, session_id, prompt):
        self.writes.append(("send_message", session_id, prompt))
        self.session["updateTime"] = "u2"
        if self.add_exact_message:
            self.activities.append({
                "name": f"sessions/{session_id}/activities/user1",
                "createTime": "2026-08-29T18:10:00Z",
                "userMessaged": {"userMessage": prompt},
            })

    def create_session(self, body):
        self.writes.append(("create_session", body))
        row = {
            "id": "999",
            "state": "QUEUED",
            "updateTime": "u2",
            "title": body["title"],
            "prompt": body["prompt"],
            "sourceContext": body["sourceContext"],
        }
        self.sessions.append(row)
        return dict(row)

    def approve_plan(self, session_id):
        self.writes.append(("approve_plan", session_id))


class V22GatewayTests(unittest.TestCase):
    def test_prompt_and_title_are_not_transport_fields(self):
        with self.assertRaises(GatewayError):
            parse_v22(json.dumps(mutation_payload(prompt="private task body")))
        with self.assertRaises(GatewayError):
            parse_v22(json.dumps(mutation_payload(title="private title")))

    def test_reference_instruction_is_required(self):
        bad = mutation_payload()
        bad.pop("instruction_ref")
        with self.assertRaises(GatewayError):
            parse_v22(json.dumps(bad))

    def test_controller_lane_mapping_remains_fail_closed(self):
        with self.assertRaises(GatewayError):
            parse_v22(json.dumps(mutation_payload(controller_id="A", lane="W05")))

    def test_bridge_contains_reference_not_instruction_body(self):
        env = parse_v22(json.dumps(mutation_payload()))
        text = bridge_message(env)
        self.assertIn("drive:ABCDEFGHIJKLMN123456", text)
        self.assertIn("instruction_sha256=" + "a" * 64, text)
        self.assertNotIn("private task body", text)

    def test_same_session_serializes_and_independent_sessions_parallelize(self):
        a = parse_v22(json.dumps(mutation_payload(request_id="A", session_id="123")))
        b = parse_v22(json.dumps(mutation_payload(request_id="B", session_id="123", controller_id="B", lane="W01_W02", logical_task_id="W02-TASK", write_domain="W02/editor")))
        c = parse_v22(json.dumps(mutation_payload(request_id="C", session_id="456")))
        self.assertEqual(effect_key(a), effect_key(b))
        self.assertNotEqual(effect_key(a), effect_key(c))

    def test_legacy_session_adoption_is_local_binding_only(self):
        env = parse_v22(json.dumps(mutation_payload()))
        github = FakeGitHub()
        first = require_session_binding(github, env)
        self.assertTrue(first.needs_persist)
        github.add(first.marker)
        second = require_session_binding(github, env)
        self.assertFalse(second.needs_persist)
        self.assertEqual(first.marker, binding_marker("123", env.logical_task_id, env.write_domain))

    def test_send_message_requires_exact_new_user_messaged_activity(self):
        env = parse_v22(json.dumps(mutation_payload()))
        github = FakeGitHub()
        jules = FakeJules()
        intent = preflight(env, jules, github, source_name="sources/github/hamad933/Cybersecurity-Education-Platform")
        result = execute(env, intent, jules, github, source_name="sources/github/hamad933/Cybersecurity-Education-Platform")
        self.assertEqual("VERIFIED", result["verification"])
        self.assertEqual(1, len(jules.writes))
        self.assertIn("matching_activity", result)

    def test_unrelated_update_without_exact_message_is_unknown(self):
        env = parse_v22(json.dumps(mutation_payload()))
        github = FakeGitHub()
        jules = FakeJules()
        jules.add_exact_message = False
        intent = preflight(env, jules, github, source_name="sources/github/hamad933/Cybersecurity-Education-Platform")
        result = execute(env, intent, jules, github, source_name="sources/github/hamad933/Cybersecurity-Education-Platform")
        self.assertEqual("UNKNOWN", result["verification"])
        self.assertEqual("UNKNOWN_WRITE_OUTCOME", result["idempotency_final_state"])

    def test_create_session_exact_correlation_and_single_write(self):
        env = create_env()
        github = FakeGitHub()
        jules = FakeJules()
        intent = preflight(env, jules, github, source_name="sources/github/hamad933/Cybersecurity-Education-Platform")
        result = execute(env, intent, jules, github, source_name="sources/github/hamad933/Cybersecurity-Education-Platform")
        self.assertEqual("VERIFIED", result["verification"])
        self.assertEqual("999", result["session_id"])
        self.assertEqual(1, len(jules.writes))
        self.assertEqual(bridge_title(env), jules.sessions[0]["title"])
        self.assertEqual(bridge_digest(env), sha256_text(jules.sessions[0]["prompt"]))

    def test_reconcile_unknown_create_applied_without_write(self):
        original = create_env("REQ-ORIG")
        target = intent_identity(original)
        env = parse_v22(json.dumps({**original.public_dict(), "request_id": "REC-1", "action": "reconcile_create_session", "execution_mode": "RECONCILE_ONLY", "target_request_id": original.request_id, "target_intent_identity": target, "min_reconcile_age_seconds": 0}))
        github = FakeGitHub()
        github.add(request_marker(env, DurableState.UNKNOWN_WRITE_OUTCOME, target=True))
        jules = FakeJules()
        jules.sessions.append({"id": "777", "state": "QUEUED", "title": bridge_title(env), "prompt": bridge_message(env, target=True), "sourceContext": {"source": "sources/github/hamad933/Cybersecurity-Education-Platform", "githubRepoContext": {"startingBranch": env.starting_branch}}})
        result = reconcile(env, jules, github, source_name="sources/github/hamad933/Cybersecurity-Education-Platform")
        self.assertEqual("RECONCILED_APPLIED", result["reconciliation_state"])
        self.assertEqual([], jules.writes)

    def test_reconcile_unknown_create_not_applied_can_resolve_after_settle(self):
        original = create_env("REQ-ORIG-2")
        target = intent_identity(original)
        env = parse_v22(json.dumps({**original.public_dict(), "request_id": "REC-2", "action": "reconcile_create_session", "execution_mode": "RECONCILE_ONLY", "target_request_id": original.request_id, "target_intent_identity": target, "min_reconcile_age_seconds": 0}))
        github = FakeGitHub()
        github.add(request_marker(env, DurableState.UNKNOWN_WRITE_OUTCOME, target=True))
        result = reconcile(env, FakeJules(), github, source_name="sources/github/hamad933/Cybersecurity-Education-Platform")
        self.assertEqual("RECONCILED_NOT_APPLIED", result["reconciliation_state"])
        self.assertTrue(result["persist_resolution"])

    def test_reconciliation_target_identity_mismatch_fails(self):
        original = create_env("REQ-ORIG-3")
        env = parse_v22(json.dumps({**original.public_dict(), "request_id": "REC-3", "action": "reconcile_create_session", "execution_mode": "RECONCILE_ONLY", "target_request_id": original.request_id, "target_intent_identity": "f" * 64}))
        with self.assertRaises(GatewayError):
            reconcile(env, FakeJules(), FakeGitHub(), source_name="sources/github/hamad933/Cybersecurity-Education-Platform")

    def test_public_workflow_has_owner_and_main_gates_and_no_freeform_request_json_input(self):
        outer = (ROOT / ".github/workflows/cep-jules-v2-mutation.yml").read_text(encoding="utf-8")
        worker = (ROOT / ".github/workflows/cep-jules-v2-mutation-worker.yml").read_text(encoding="utf-8")
        self.assertNotIn("request_json:\n        description:", outer)
        self.assertNotIn("prompt:", outer)
        self.assertNotIn("title:", outer)
        self.assertIn('test "$ACTOR" = "$OWNER"', outer)
        self.assertIn('test "$REF" = "refs/heads/main"', outer)
        self.assertIn('test "$ACTOR" = "$OWNER"', worker)
        self.assertIn('test "$REF" = "refs/heads/main"', worker)
        self.assertNotIn("secrets: inherit", outer)

    def test_reconciliation_worker_has_no_mutation_cli_phase(self):
        worker = (ROOT / ".github/workflows/cep-jules-v2-mutation-worker.yml").read_text(encoding="utf-8")
        reconcile_section = worker.split("  reconcile:\n", 1)[1]
        self.assertIn("v22_cli reconcile", reconcile_section)
        self.assertNotIn("v22_cli execute", reconcile_section)


if __name__ == "__main__":
    unittest.main()

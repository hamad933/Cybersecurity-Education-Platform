from __future__ import annotations

import os
import unittest
from pathlib import Path
from unittest.mock import patch

from tools.cep_jules_gateway.legacy_issue_gateway import run_control
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError, PaginationInfo
from tools.cep_jules_gateway.pagination import PaginationResult

ROOT = Path(__file__).resolve().parents[3]


class FakeGitHub:
    def __init__(self):
        self.comments: list[str] = []
        self.sha = "a" * 40

    def comments_text(self):
        return "\n".join(self.comments)

    def comment(self, text):
        self.comments.append(text)

    def branch_sha(self, branch):
        return self.sha


class FakeJules:
    def __init__(self):
        self.create_calls = 0
        self.send_calls = 0
        self.approve_calls = 0
        self.session_reads = []
        self.activity_reads = []
        self.created = {"id": "777", "state": "QUEUED"}

    def list_sources(self):
        return [{"name": "sources/github/hamad933/Cybersecurity-Education-Platform"}]

    def create_session(self, body):
        self.create_calls += 1
        return dict(self.created)

    def get_session(self, sid):
        if not self.session_reads:
            raise GatewayError(ErrorClassification.NOT_FOUND, "not visible yet", http_status=404)
        value = self.session_reads.pop(0)
        if isinstance(value, Exception):
            raise value
        return value

    def list_activities(self, sid, *, page_size, max_pages, max_items=2000, **kwargs):
        rows = self.activity_reads.pop(0) if self.activity_reads else []
        return PaginationResult(rows, PaginationInfo(1, len(rows), True, max_pages, max_items))

    def send_message(self, sid, prompt):
        self.send_calls += 1

    def approve_plan(self, sid):
        self.approve_calls += 1


def env(payload: str):
    return patch.dict(
        os.environ,
        {
            "ISSUE_BODY": payload,
            "ISSUE_NUMBER": "1",
            "JULES_API_KEY": "test",
            "GH_TOKEN": "test",
        },
        clear=False,
    )


class LegacyIssueGatewayTests(unittest.TestCase):
    def test_create_requires_authoritative_post_read_before_created_receipt(self):
        gh = FakeGitHub()
        jules = FakeJules()
        jules.session_reads = [{"id": "777", "state": "IN_PROGRESS", "url": "https://example.invalid"}]
        payload = '{"action":"create_session","request_id":"REQ-CREATE","task_id":"T1","starting_branch":"work/x","expected_sha":"' + "a" * 40 + '","title":"t","prompt":"p"}'
        with env(payload), patch("tools.cep_jules_gateway.legacy_issue_gateway._gh", return_value=gh), patch("tools.cep_jules_gateway.legacy_issue_gateway._client", return_value=jules):
            rc = run_control()
        self.assertEqual(0, rc)
        self.assertEqual(1, jules.create_calls)
        joined = "\n".join(gh.comments)
        self.assertIn("outcome=CREATED", joined)
        self.assertIn("verification=AUTHORITATIVE_POST_READ", joined)
        self.assertIn("blind_retry=false", joined)

    def test_create_visibility_gap_is_unknown_and_never_retries_create(self):
        gh = FakeGitHub()
        jules = FakeJules()
        payload = '{"action":"create_session","request_id":"REQ-CREATE-404","task_id":"T1","starting_branch":"work/x","expected_sha":"' + "a" * 40 + '","title":"t","prompt":"p"}'
        with env(payload), patch("tools.cep_jules_gateway.legacy_issue_gateway._gh", return_value=gh), patch("tools.cep_jules_gateway.legacy_issue_gateway._client", return_value=jules):
            rc = run_control()
        self.assertEqual(6, rc)
        self.assertEqual(1, jules.create_calls)
        joined = "\n".join(gh.comments)
        self.assertIn("UNKNOWN_WRITE_OUTCOME", joined)
        self.assertIn("POST_CREATE_READBACK_NOT_FOUND", joined)
        self.assertIn("blind_retry=false", joined)
        self.assertNotIn("outcome=CREATED", joined)

    def test_send_message_requires_exact_new_user_messaged_activity(self):
        gh = FakeGitHub()
        jules = FakeJules()
        jules.session_reads = [
            {"id": "123", "state": "IN_PROGRESS", "updateTime": "u1"},
            {"id": "123", "state": "IN_PROGRESS", "updateTime": "u1"},
            {"id": "123", "state": "IN_PROGRESS", "updateTime": "u2"},
        ]
        old = {"name": "sessions/123/activities/a1", "createTime": "t1"}
        new = {"name": "sessions/123/activities/a2", "createTime": "t2", "userMessaged": {"userMessage": "hello"}}
        jules.activity_reads = [[old], [old, new]]
        payload = '{"action":"send_message","request_id":"REQ-SEND","session_id":"123","prompt":"hello"}'
        with env(payload), patch("tools.cep_jules_gateway.legacy_issue_gateway._gh", return_value=gh), patch("tools.cep_jules_gateway.legacy_issue_gateway._client", return_value=jules):
            rc = run_control()
        self.assertEqual(0, rc)
        self.assertEqual(1, jules.send_calls)
        self.assertIn("verification=EXACT_NEW_USER_MESSAGED_ACTIVITY", "\n".join(gh.comments))

    def test_legacy_workflows_are_thin_and_share_packaged_gateway(self):
        control = (ROOT / ".github/workflows/cep-jules-control.yml").read_text(encoding="utf-8")
        inspect = (ROOT / ".github/workflows/cep-jules-inspect.yml").read_text(encoding="utf-8")
        for text, mode in ((control, "control"), (inspect, "inspect")):
            self.assertIn("actions/checkout@v4", text)
            self.assertIn("actions/setup-python@v5", text)
            self.assertIn(f"python -m tools.cep_jules_gateway.legacy_issue_gateway {mode}", text)
            self.assertNotIn("urllib.request", text)
            self.assertNotIn("pageSize=100", text)

    def test_ci_covers_legacy_issue_workflows(self):
        workflow = (ROOT / ".github/workflows/cep-jules-v2-tests.yml").read_text(encoding="utf-8")
        self.assertIn('".github/workflows/cep-jules-control.yml"', workflow)
        self.assertIn('".github/workflows/cep-jules-inspect.yml"', workflow)
        self.assertIn("Validate Gateway workflow YAML", workflow)


if __name__ == "__main__":
    unittest.main()

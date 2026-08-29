from __future__ import annotations

import json
import unittest
from dataclasses import dataclass

from tools.cep_jules_gateway.digest import sha256_json, sha256_text
from tools.cep_jules_gateway.envelope import parse_envelope
from tools.cep_jules_gateway.inspect_bundle import build_inspect_bundle
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError, PaginationInfo


@dataclass
class Result:
    items: list
    info: PaginationInfo


class FakeClient:
    def __init__(self, activities, hydrated=None, fail_names=None):
        self.activities = activities
        self.hydrated = hydrated or {}
        self.fail_names = set(fail_names or [])
        self.observations = []

    def get_session(self, session_id):
        return {"id": session_id, "state": "IN_PROGRESS", "updateTime": "2026-08-29T12:00:00Z", "title": "Task"}

    def list_activities(self, session_id, *, page_size, max_pages):
        return Result(self.activities, PaginationInfo(2, len(self.activities), True, max_pages))

    def get_activity(self, activity_name):
        if activity_name in self.fail_names:
            raise GatewayError(ErrorClassification.PROVIDER_READ_FAILED, "hydration failed", http_status=503)
        return self.hydrated.get(activity_name, next(x for x in self.activities if x.get("name") == activity_name))


def envelope(**options):
    payload = {
        "schema_version": "2.0",
        "request_id": "REQ-INSPECT",
        "controller_id": "PARENT",
        "lane": "W05",
        "action": "inspect_bundle",
        "session_id": "123",
        "options": options,
    }
    return parse_envelope(json.dumps(payload))


class InspectBundleTests(unittest.TestCase):
    def test_plan_messages_changeset_and_bash(self):
        activities = [
            {
                "name": "sessions/123/activities/a1",
                "createTime": "t1",
                "planGenerated": {"plan": {"steps": [{"title": "Inspect", "description": "Read only"}]}},
                "agentMessaged": {"agentMessage": "hello"},
                "artifacts": [
                    {"changeSet": {"source": "agent", "gitPatch": {"baseCommitId": "abc", "unidiffPatch": "diff --git a/x b/x\n+safe"}}},
                    {"bashOutput": {"command": "pytest", "exitCode": 0, "output": "2 passed"}},
                ],
            }
        ]
        client = FakeClient(activities)
        bundle = build_inspect_bundle(envelope(), client)
        self.assertEqual("FOUND", bundle["plan"]["status"])
        self.assertEqual(sha256_json({"steps": [{"index": 1, "title": "Inspect", "description": "Read only"}]}), bundle["plan"]["plan_digest"])
        self.assertEqual(sha256_text("diff --git a/x b/x\n+safe"), bundle["changesets"]["latest_exact_patch"]["patch_digest"])
        self.assertEqual(sha256_text("2 passed"), bundle["bash_evidence"]["recent_exact"][0]["output_digest"])
        self.assertEqual("COMPLETE", bundle["provider"]["completeness"])

    def test_expected_state_is_enforced(self):
        with self.assertRaises(GatewayError) as ctx:
            build_inspect_bundle(
                parse_envelope(json.dumps({
                    "schema_version": "2.0",
                    "request_id": "REQ-STATE",
                    "controller_id": "PARENT",
                    "lane": "W05",
                    "action": "inspect_bundle",
                    "session_id": "123",
                    "expected_state": "COMPLETED",
                })),
                FakeClient([]),
            )
        self.assertEqual(ErrorClassification.INVALID_STATE, ctx.exception.classification)

    def test_expected_plan_digest_is_enforced(self):
        activities = [{
            "name": "sessions/123/activities/a1",
            "planGenerated": {"plan": {"steps": [{"title": "Inspect", "description": "Read only"}]}},
            "artifacts": [],
        }]
        expected = sha256_json({"steps": [{"index": 1, "title": "Inspect", "description": "Read only"}]})
        env = parse_envelope(json.dumps({
            "schema_version": "2.0",
            "request_id": "REQ-DIGEST",
            "controller_id": "PARENT",
            "lane": "W05",
            "action": "inspect_bundle",
            "session_id": "123",
            "expected_plan_digest": expected,
        }))
        bundle = build_inspect_bundle(env, FakeClient(activities))
        self.assertEqual(expected, bundle["plan"]["plan_digest"])

    def test_plan_digest_mismatch_fails_closed(self):
        activities = [{
            "name": "sessions/123/activities/a1",
            "planGenerated": {"plan": {"steps": [{"title": "Inspect", "description": "Read only"}]}},
            "artifacts": [],
        }]
        env = parse_envelope(json.dumps({
            "schema_version": "2.0",
            "request_id": "REQ-DIGEST-BAD",
            "controller_id": "PARENT",
            "lane": "W05",
            "action": "inspect_bundle",
            "session_id": "123",
            "expected_plan_digest": "f" * 64,
        }))
        with self.assertRaises(GatewayError) as ctx:
            build_inspect_bundle(env, FakeClient(activities))
        self.assertEqual(ErrorClassification.INVALID_STATE, ctx.exception.classification)

    def test_no_plan_is_explicit(self):
        activities = [{"name": "sessions/123/activities/a1", "artifacts": []}]
        bundle = build_inspect_bundle(envelope(), FakeClient(activities))
        self.assertEqual("NOT_FOUND", bundle["plan"]["status"])

    def test_changeset_unavailable_after_hydration(self):
        listed = [{"name": "sessions/123/activities/a1", "artifacts": [{"changeSet": {"gitPatch": {}}}]}]
        hydrated = {"sessions/123/activities/a1": {"name": "sessions/123/activities/a1", "artifacts": [{"changeSet": {"gitPatch": {}}}]}}
        bundle = build_inspect_bundle(envelope(), FakeClient(listed, hydrated))
        self.assertEqual("NOT_AVAILABLE_FROM_PROVIDER", bundle["changesets"]["latest_exact_patch"]["status"])

    def test_partial_provider_hydration_failure(self):
        activities = [
            {"name": "sessions/123/activities/a1", "artifacts": [{"changeSet": {"gitPatch": {}}}, {"bashOutput": {}}]}
        ]
        bundle = build_inspect_bundle(envelope(), FakeClient(activities, fail_names={"sessions/123/activities/a1"}))
        self.assertEqual("PARTIAL", bundle["provider"]["completeness"])
        self.assertGreaterEqual(len(bundle["provider"]["errors"]), 1)

    def test_secret_is_redacted_before_patch_and_bash_digest(self):
        activities = [
            {
                "name": "sessions/123/activities/a1",
                "artifacts": [
                    {"changeSet": {"gitPatch": {"unidiffPatch": "+JULES_API_KEY=topsecret"}}},
                    {"bashOutput": {"output": "Authorization: Bearer topsecret"}},
                ],
            }
        ]
        bundle = build_inspect_bundle(envelope(), FakeClient(activities))
        rendered = json.dumps(bundle)
        self.assertNotIn("topsecret", rendered)
        self.assertIn("[REDACTED]", rendered)

    def test_bounded_recent_bash_selection(self):
        activities = []
        for i in range(5):
            activities.append(
                {
                    "name": f"sessions/123/activities/a{i}",
                    "createTime": str(i),
                    "artifacts": [{"bashOutput": {"command": f"cmd{i}", "output": f"out{i}"}}],
                }
            )
        bundle = build_inspect_bundle(envelope(recent_bash_outputs=2), FakeClient(activities))
        self.assertEqual(2, bundle["bash_evidence"]["recent_exact_count"])
        self.assertEqual(["cmd3", "cmd4"], [row["command"] for row in bundle["bash_evidence"]["recent_exact"]])


if __name__ == "__main__":
    unittest.main()

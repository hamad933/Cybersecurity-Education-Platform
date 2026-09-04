from __future__ import annotations

import json
import unittest

from tools.cep_jules_gateway.envelope import parse_envelope
from tools.cep_jules_gateway.http import HttpResponse
from tools.cep_jules_gateway.jules import JulesClient
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError, PaginationInfo
from tools.cep_jules_gateway.mutation import execute_mutation, preflight_mutation
from tools.cep_jules_gateway.pagination import PaginationResult
from tools.cep_jules_gateway.plan_identity import plan_identity_from_activities


class FakeGitHub:
    def __init__(self, sha="a" * 40, fail=False):
        self.sha = sha
        self.fail = fail

    def require_branch_head(self, branch, expected_sha):
        if self.fail or self.sha != expected_sha:
            raise GatewayError(ErrorClassification.INVALID_STATE, "branch drift")
        return {"actual_sha": self.sha, "expected_sha": expected_sha, "branch": branch}


class FakeJules:
    def __init__(self, *, session_reads=None, activities=None, activity_reads=None, sources=None):
        self.session_reads = list(session_reads or [])
        self.activities = list(activities or [])
        self.activity_reads = [list(rows) for rows in (activity_reads or [])]
        self.sources = list(sources or [{"name": "sources/github/hamad933/Cybersecurity-Education-Platform"}])
        self.send_calls = 0
        self.approve_calls = 0
        self.create_calls = 0
        self.send_error = None
        self.approve_error = None
        self.create_error = None
        self.created = {"id": "777", "state": "AWAITING_PLAN_APPROVAL"}

    def get_session(self, session_id):
        if not self.session_reads:
            raise GatewayError(ErrorClassification.PROVIDER_READ_FAILED, "no fake session read")
        value = self.session_reads.pop(0)
        if isinstance(value, Exception):
            raise value
        return value

    def list_activities(self, session_id, *, page_size, max_pages, max_items=2000):
        rows = self.activity_reads.pop(0) if self.activity_reads else list(self.activities)
        return PaginationResult(rows, PaginationInfo(1, len(rows), True, max_pages, max_items))

    def list_sources(self):
        return list(self.sources)

    def send_message(self, session_id, prompt):
        self.send_calls += 1
        if self.send_error:
            raise self.send_error

    def approve_plan(self, session_id):
        self.approve_calls += 1
        if self.approve_error:
            raise self.approve_error

    def create_session(self, body):
        self.create_calls += 1
        if self.create_error:
            raise self.create_error
        return dict(self.created)


class FakeTransport:
    def __init__(self, response):
        self.response = response
        self.calls = 0

    def request_json(self, method, url, *, headers, timeout, body=None):
        self.calls += 1
        return self.response


SOURCE = "sources/github/hamad933/Cybersecurity-Education-Platform"


def send_envelope(*, expected_state="IN_PROGRESS", expected_update=None):
    payload = {
        "schema_version": "2.1",
        "request_id": "REQ-SEND",
        "controller_id": "A",
        "lane": "W01",
        "logical_task_id": "CEP-TASK",
        "write_domain": "W01/simulator",
        "action": "send_message",
        "session_id": "123",
        "expected_state": expected_state,
        "execution_mode": "MUTATION_CANARY",
        "prompt": "Continue safely.",
    }
    if expected_update is not None:
        payload["expected_session_update_time"] = expected_update
    return parse_envelope(json.dumps(payload))


def plan_activity(description="safe", *, plan_id="plan1"):
    return {
        "name": "sessions/123/activities/p1",
        "createTime": "2026-08-29T12:00:00Z",
        "planGenerated": {"plan": {"id": plan_id, "steps": [{"title": "Implement", "description": description}]}},
    }


def plan_approved_activity(plan_id="plan1"):
    return {
        "name": "sessions/123/activities/a-approved",
        "createTime": "2026-08-29T12:01:00Z",
        "planApproved": {"planId": plan_id},
    }


def approve_envelope(activity=None, *, state="AWAITING_PLAN_APPROVAL", update="u1"):
    activity = activity or plan_activity()
    identity = plan_identity_from_activities([activity], "123")
    return parse_envelope(
        json.dumps(
            {
                "schema_version": "2.1",
                "request_id": "REQ-APPROVE",
                "controller_id": "A",
                "lane": "W01",
                "logical_task_id": "CEP-TASK",
                "write_domain": "W01/simulator",
                "action": "approve_plan",
                "session_id": "123",
                "expected_state": state,
                "expected_plan_digest": identity["provider_identity_digest"],
                "expected_plan_activity_name": identity["activity_name"],
                "expected_plan_create_time": identity["create_time"],
                "expected_session_update_time": update,
                "execution_mode": "MUTATION_CANARY",
            }
        )
    )


def create_envelope():
    return parse_envelope(
        json.dumps(
            {
                "schema_version": "2.1",
                "request_id": "REQ-CREATE",
                "controller_id": "A",
                "lane": "W01",
                "logical_task_id": "CEP-TASK",
                "write_domain": "W01/simulator",
                "action": "create_session",
                "starting_branch": "work/cep-task",
                "expected_sha": "a" * 40,
                "execution_mode": "MUTATION_CANARY",
                "title": "Bounded task",
                "prompt": "Implement only authorized files.",
            }
        )
    )


class MutationSafetyTests(unittest.TestCase):
    def test_exact_plan_binding_preflight_matches(self):
        activity = plan_activity()
        envelope = approve_envelope(activity)
        same = {"id": "123", "state": "AWAITING_PLAN_APPROVAL", "updateTime": "u1"}
        jules = FakeJules(session_reads=[same, same], activities=[activity])
        intent = preflight_mutation(envelope, jules, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(envelope.expected_plan_digest, intent["preconditions"]["plan_provider_identity_digest"])
        self.assertEqual("plan1", intent["preconditions"]["plan_id"])

    def test_plan_without_stable_id_fails_closed(self):
        activity = plan_activity(plan_id="")
        envelope = approve_envelope(activity)
        jules = FakeJules(session_reads=[{"id": "123", "state": "AWAITING_PLAN_APPROVAL", "updateTime": "u1"}], activities=[activity])
        with self.assertRaises(GatewayError) as ctx:
            preflight_mutation(envelope, jules, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(ErrorClassification.PROVIDER_PROTOCOL_FAILED, ctx.exception.classification)

    def test_plan_changed_after_review_requires_reapproval(self):
        reviewed = plan_activity("reviewed")
        changed = plan_activity("changed")
        envelope = approve_envelope(reviewed)
        jules = FakeJules(session_reads=[{"id": "123", "state": "AWAITING_PLAN_APPROVAL", "updateTime": "u1"}], activities=[changed])
        with self.assertRaises(GatewayError) as ctx:
            preflight_mutation(envelope, jules, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(ErrorClassification.PLAN_CHANGED_SINCE_REVIEW, ctx.exception.classification)

    def test_no_plan_fails_closed(self):
        envelope = approve_envelope()
        jules = FakeJules(session_reads=[{"id": "123", "state": "AWAITING_PLAN_APPROVAL", "updateTime": "u1"}], activities=[])
        with self.assertRaises(GatewayError) as ctx:
            preflight_mutation(envelope, jules, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(ErrorClassification.INVALID_STATE, ctx.exception.classification)

    def test_provider_state_incompatible_with_approval_fails_closed(self):
        activity = plan_activity()
        envelope = approve_envelope(activity)
        jules = FakeJules(session_reads=[{"id": "123", "state": "IN_PROGRESS", "updateTime": "u1"}], activities=[activity])
        with self.assertRaises(GatewayError) as ctx:
            preflight_mutation(envelope, jules, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(ErrorClassification.INVALID_STATE, ctx.exception.classification)

    def test_send_terminal_state_is_rejected(self):
        envelope = send_envelope(expected_state="COMPLETED")
        jules = FakeJules(session_reads=[{"id": "123", "state": "COMPLETED", "updateTime": "u1"}])
        with self.assertRaises(GatewayError) as ctx:
            preflight_mutation(envelope, jules, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(ErrorClassification.INVALID_STATE, ctx.exception.classification)

    def test_send_write_occurs_once_and_post_read_verifies(self):
        envelope = send_envelope()
        intent = preflight_mutation(envelope, FakeJules(session_reads=[{"id": "123", "state": "IN_PROGRESS", "updateTime": "u1"}]), FakeGitHub(), source_name=SOURCE)
        execution = FakeJules(session_reads=[{"id": "123", "state": "IN_PROGRESS", "updateTime": "u1"}, {"id": "123", "state": "IN_PROGRESS", "updateTime": "u2"}])
        receipt = execute_mutation(envelope, intent, execution, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(1, execution.send_calls)
        self.assertEqual("VERIFIED", receipt["verification"])
        self.assertEqual("COMPLETED", receipt["idempotency_final_state"])

    def test_pre_read_drift_means_no_write(self):
        envelope = send_envelope()
        intent = preflight_mutation(envelope, FakeJules(session_reads=[{"id": "123", "state": "IN_PROGRESS", "updateTime": "u1"}]), FakeGitHub(), source_name=SOURCE)
        execution = FakeJules(session_reads=[{"id": "123", "state": "AWAITING_USER_FEEDBACK", "updateTime": "u2"}])
        receipt = execute_mutation(envelope, intent, execution, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(0, execution.send_calls)
        self.assertEqual("REJECTED", receipt["verification"])

    def test_ambiguous_write_outcome_is_not_retried(self):
        envelope = send_envelope()
        intent = preflight_mutation(envelope, FakeJules(session_reads=[{"id": "123", "state": "IN_PROGRESS", "updateTime": "u1"}]), FakeGitHub(), source_name=SOURCE)
        execution = FakeJules(session_reads=[{"id": "123", "state": "IN_PROGRESS", "updateTime": "u1"}])
        execution.send_error = GatewayError(ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN, "network ambiguous", http_status=599)
        receipt = execute_mutation(envelope, intent, execution, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(1, execution.send_calls)
        self.assertEqual("UNKNOWN", receipt["verification"])
        self.assertEqual("UNKNOWN_WRITE_OUTCOME", receipt["idempotency_final_state"])
        self.assertFalse(receipt["blind_retry"])

    def test_post_read_inconclusive_becomes_unknown(self):
        envelope = send_envelope()
        intent = preflight_mutation(envelope, FakeJules(session_reads=[{"id": "123", "state": "IN_PROGRESS", "updateTime": "u1"}]), FakeGitHub(), source_name=SOURCE)
        execution = FakeJules(session_reads=[{"id": "123", "state": "IN_PROGRESS", "updateTime": "u1"}, {"id": "123", "state": "IN_PROGRESS", "updateTime": "u1"}])
        receipt = execute_mutation(envelope, intent, execution, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(1, execution.send_calls)
        self.assertEqual("UNKNOWN", receipt["verification"])

    def test_approve_plan_write_once_and_matching_plan_approved_event_verifies(self):
        activity = plan_activity()
        envelope = approve_envelope(activity)
        same = {"id": "123", "state": "AWAITING_PLAN_APPROVAL", "updateTime": "u1"}
        intent = preflight_mutation(envelope, FakeJules(session_reads=[same, same], activities=[activity]), FakeGitHub(), source_name=SOURCE)
        execution = FakeJules(
            session_reads=[same, same, {"id": "123", "state": "IN_PROGRESS", "updateTime": "u2"}],
            activity_reads=[[activity], [activity, plan_approved_activity("plan1")]],
        )
        receipt = execute_mutation(envelope, intent, execution, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(1, execution.approve_calls)
        self.assertEqual("VERIFIED", receipt["verification"])
        self.assertTrue(receipt["matching_plan_approved_activity_observed"])

    def test_approve_state_change_without_matching_plan_event_is_unknown(self):
        activity = plan_activity()
        envelope = approve_envelope(activity)
        same = {"id": "123", "state": "AWAITING_PLAN_APPROVAL", "updateTime": "u1"}
        intent = preflight_mutation(envelope, FakeJules(session_reads=[same, same], activities=[activity]), FakeGitHub(), source_name=SOURCE)
        execution = FakeJules(
            session_reads=[same, same, {"id": "123", "state": "IN_PROGRESS", "updateTime": "u2"}],
            activity_reads=[[activity], [activity]],
        )
        receipt = execute_mutation(envelope, intent, execution, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(1, execution.approve_calls)
        self.assertEqual("UNKNOWN", receipt["verification"])
        self.assertEqual("UNKNOWN_WRITE_OUTCOME", receipt["idempotency_final_state"])

    def test_plan_change_between_intent_and_write_means_no_approve(self):
        reviewed = plan_activity("reviewed")
        envelope = approve_envelope(reviewed)
        same = {"id": "123", "state": "AWAITING_PLAN_APPROVAL", "updateTime": "u1"}
        intent = preflight_mutation(envelope, FakeJules(session_reads=[same, same], activities=[reviewed]), FakeGitHub(), source_name=SOURCE)
        execution = FakeJules(session_reads=[same], activities=[plan_activity("changed")])
        receipt = execute_mutation(envelope, intent, execution, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(0, execution.approve_calls)
        self.assertEqual("REJECTED", receipt["verification"])
        self.assertEqual(ErrorClassification.PLAN_CHANGED_SINCE_REVIEW.value, receipt["provider_result_class"])

    def test_create_branch_drift_after_intent_means_no_write(self):
        envelope = create_envelope()
        intent = preflight_mutation(envelope, FakeJules(), FakeGitHub(), source_name=SOURCE)
        execution = FakeJules()
        receipt = execute_mutation(envelope, intent, execution, FakeGitHub(fail=True), source_name=SOURCE)
        self.assertEqual(0, execution.create_calls)
        self.assertEqual("REJECTED", receipt["verification"])

    def test_create_write_and_post_read_verified(self):
        envelope = create_envelope()
        intent = preflight_mutation(envelope, FakeJules(), FakeGitHub(), source_name=SOURCE)
        execution = FakeJules(session_reads=[{"id": "777", "state": "AWAITING_PLAN_APPROVAL", "updateTime": "u1"}])
        receipt = execute_mutation(envelope, intent, execution, FakeGitHub(), source_name=SOURCE)
        self.assertEqual(1, execution.create_calls)
        self.assertEqual("777", receipt["session_id"])
        self.assertEqual("VERIFIED", receipt["verification"])

    def test_mutation_429_is_unknown_and_transport_called_once(self):
        transport = FakeTransport(HttpResponse(429, {"error": "quota"}, {"retry-after": "3"}))
        client = JulesClient("runtime-secret", api_base="https://example.invalid", transport=transport)
        with self.assertRaises(GatewayError) as ctx:
            client.send_message("123", "safe")
        self.assertEqual(ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN, ctx.exception.classification)
        self.assertEqual(1, transport.calls)
        self.assertFalse(ctx.exception.details["blind_retry"])


if __name__ == "__main__":
    unittest.main()

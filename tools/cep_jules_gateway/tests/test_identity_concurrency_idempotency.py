from __future__ import annotations

import json
import unittest

from tools.cep_jules_gateway.effect import effect_concurrency_key, request_concurrency_key
from tools.cep_jules_gateway.envelope import parse_envelope
from tools.cep_jules_gateway.idempotency import (
    IdempotencyState,
    create_effect_guard_name,
    inspect_idempotency,
    marker_name,
    require_unused_create_effect,
)
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError
from tools.cep_jules_gateway.plan_identity import plan_identity_from_activities


class ArtifactReader:
    def __init__(self):
        self.rows: dict[str, list[dict]] = {}

    def add(self, name: str):
        self.rows.setdefault(name, []).append({"name": name, "expired": False})

    def list_active_artifacts_by_name(self, name: str):
        return list(self.rows.get(name, []))


def send_envelope(request_id: str, controller: str, lane: str, session_id: str):
    return parse_envelope(
        json.dumps(
            {
                "schema_version": "2.1",
                "request_id": request_id,
                "controller_id": controller,
                "lane": lane,
                "logical_task_id": "CEP-TASK-1",
                "write_domain": "shared/effect-key-test",
                "action": "send_message",
                "session_id": session_id,
                "expected_state": "IN_PROGRESS",
                "execution_mode": "MUTATION_CANARY",
                "prompt": "Continue the bounded task.",
            }
        )
    )


def create_envelope(request_id: str, *, logical_task: str = "CEP-TASK-1", write_domain: str = "W01/simulator"):
    return parse_envelope(
        json.dumps(
            {
                "schema_version": "2.1",
                "request_id": request_id,
                "controller_id": "A",
                "lane": "W01",
                "logical_task_id": logical_task,
                "write_domain": write_domain,
                "action": "create_session",
                "starting_branch": "work/cep-task-1",
                "expected_sha": "a" * 40,
                "execution_mode": "MUTATION_CANARY",
                "title": "CEP bounded task",
                "prompt": "Implement only the authorized write domain.",
            }
        )
    )


class IdentityConcurrencyIdempotencyTests(unittest.TestCase):
    def test_latest_plan_is_selected_by_provider_time_not_list_order(self):
        activities = [
            {
                "name": "sessions/123/activities/new",
                "createTime": "2026-08-29T12:00:00Z",
                "planGenerated": {"plan": {"steps": [{"title": "New", "description": "new"}]}},
            },
            {
                "name": "sessions/123/activities/old",
                "createTime": "2026-08-29T10:00:00Z",
                "planGenerated": {"plan": {"steps": [{"title": "Old", "description": "old"}]}},
            },
        ]
        plan = plan_identity_from_activities(activities, "123")
        self.assertEqual("sessions/123/activities/new", plan["activity_name"])
        self.assertEqual("New", plan["steps"][0]["title"])

    def test_plan_latest_timestamp_tie_fails_closed(self):
        activities = [
            {
                "name": "sessions/123/activities/a1",
                "createTime": "2026-08-29T12:00:00Z",
                "planGenerated": {"plan": {"steps": []}},
            },
            {
                "name": "sessions/123/activities/a2",
                "createTime": "2026-08-29T12:00:00Z",
                "planGenerated": {"plan": {"steps": []}},
            },
        ]
        with self.assertRaises(GatewayError) as ctx:
            plan_identity_from_activities(activities, "123")
        self.assertEqual(ErrorClassification.INVALID_STATE, ctx.exception.classification)

    def test_exact_provider_plan_digest_is_canonicalization_stable(self):
        generated_a = {"plan": {"steps": [{"description": "D", "title": "T"}]}, "metadata": {"b": 2, "a": 1}}
        generated_b = {"metadata": {"a": 1, "b": 2}, "plan": {"steps": [{"title": "T", "description": "D"}]}}
        a = [{"name": "sessions/123/activities/p1", "createTime": "t1", "planGenerated": generated_a}]
        b = [{"planGenerated": generated_b, "createTime": "t1", "name": "sessions/123/activities/p1"}]
        self.assertEqual(
            plan_identity_from_activities(a, "123")["provider_identity_digest"],
            plan_identity_from_activities(b, "123")["provider_identity_digest"],
        )

    def test_same_session_two_controllers_share_effect_key(self):
        a = send_envelope("REQ-A", "A", "W01", "123")
        b = send_envelope("REQ-B", "B", "W02", "123")
        self.assertEqual(effect_concurrency_key(a), effect_concurrency_key(b))

    def test_independent_sessions_remain_parallel_by_key(self):
        a = send_envelope("REQ-A", "A", "W01", "123")
        b = send_envelope("REQ-B", "B", "W02", "456")
        self.assertNotEqual(effect_concurrency_key(a), effect_concurrency_key(b))

    def test_same_pre_session_logical_effect_serializes_across_request_ids(self):
        a = create_envelope("REQ-CREATE-1")
        b = create_envelope("REQ-CREATE-2")
        self.assertEqual(effect_concurrency_key(a), effect_concurrency_key(b))
        self.assertNotEqual(request_concurrency_key(a), request_concurrency_key(b))

    def test_same_request_id_has_same_request_lock_across_transport_variants(self):
        a = create_envelope("REQ-SAME")
        b = create_envelope("REQ-SAME", logical_task="CEP-TASK-OTHER")
        self.assertEqual(request_concurrency_key(a), request_concurrency_key(b))

    def test_idempotency_not_seen(self):
        reader = ArtifactReader()
        snapshot = inspect_idempotency(reader, "REQ-1")
        self.assertEqual(IdempotencyState.NOT_SEEN, snapshot.observed_state)
        self.assertTrue(snapshot.may_record_new_intent)

    def test_idempotency_completed_blocks_replay(self):
        reader = ArtifactReader()
        reader.add(marker_name("REQ-1", IdempotencyState.COMPLETED))
        snapshot = inspect_idempotency(reader, "REQ-1")
        self.assertEqual(IdempotencyState.COMPLETED, snapshot.decision_state)
        self.assertFalse(snapshot.may_record_new_intent)

    def test_intent_and_unknown_require_reconciliation(self):
        for state in (IdempotencyState.INTENT_RECORDED, IdempotencyState.UNKNOWN_WRITE_OUTCOME):
            reader = ArtifactReader()
            reader.add(marker_name("REQ-1", state))
            snapshot = inspect_idempotency(reader, "REQ-1")
            self.assertEqual(state, snapshot.observed_state)
            self.assertEqual(IdempotencyState.RECONCILIATION_REQUIRED, snapshot.decision_state)

    def test_reconciliation_then_completed_is_terminal(self):
        reader = ArtifactReader()
        reader.add(marker_name("REQ-1", IdempotencyState.UNKNOWN_WRITE_OUTCOME))
        self.assertEqual(IdempotencyState.RECONCILIATION_REQUIRED, inspect_idempotency(reader, "REQ-1").decision_state)
        reader.add(marker_name("REQ-1", IdempotencyState.COMPLETED))
        self.assertEqual(IdempotencyState.COMPLETED, inspect_idempotency(reader, "REQ-1").decision_state)

    def test_create_effect_guard_blocks_second_logical_create(self):
        envelope = create_envelope("REQ-CREATE-1")
        effect_key = effect_concurrency_key(envelope)
        reader = ArtifactReader()
        reader.add(create_effect_guard_name(effect_key))
        with self.assertRaises(GatewayError) as ctx:
            require_unused_create_effect(reader, effect_key)
        self.assertEqual(ErrorClassification.RECONCILIATION_REQUIRED, ctx.exception.classification)


if __name__ == "__main__":
    unittest.main()

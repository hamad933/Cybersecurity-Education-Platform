from __future__ import annotations

import json
import unittest

from tools.cep_jules_gateway.envelope import MAX_REQUEST_BYTES, parse_envelope
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError


def base(**overrides):
    value = {
        "schema_version": "2.0",
        "request_id": "REQ-001",
        "controller_id": "PARENT",
        "lane": "W05",
        "action": "inspect_bundle",
        "session_id": "123456",
    }
    value.update(overrides)
    return value


class EnvelopeTests(unittest.TestCase):
    def test_valid_inspect_bundle(self):
        envelope = parse_envelope(json.dumps(base()))
        self.assertEqual(envelope.controller_id, "PARENT")
        self.assertEqual(envelope.session_id, "123456")

    def test_read_only_action_does_not_require_irrelevant_fields(self):
        envelope = parse_envelope(json.dumps(base(action="list_sessions", session_id=None)))
        self.assertEqual(envelope.action, "list_sessions")
        self.assertIsNone(envelope.expected_sha)
        self.assertIsNone(envelope.starting_branch)

    def test_controller_lane_mapping_matches_current_five_controller_topology(self):
        for controller, lane in (("A", "W01"), ("B", "W02"), ("C", "W03"), ("D", "W04"), ("E", "W05")):
            envelope = parse_envelope(json.dumps(base(controller_id=controller, lane=lane)))
            self.assertEqual(envelope.lane, lane)

    def test_legacy_aggregate_routes_are_parent_only(self):
        for lane in ("W01_W02", "W03_W04"):
            envelope = parse_envelope(json.dumps(base(controller_id="PARENT", lane=lane)))
            self.assertEqual(envelope.lane, lane)
        for controller, lane in (("A", "W03_W04"), ("B", "W01_W02"), ("C", "W05"), ("D", "W03_W04"), ("E", "W01_W02")):
            with self.assertRaises(GatewayError) as ctx:
                parse_envelope(json.dumps(base(controller_id=controller, lane=lane)))
            self.assertEqual(ctx.exception.classification, ErrorClassification.INVALID_REQUEST)

    def test_parent_can_route_current_individual_lanes(self):
        for lane in ("PARENT", "W01", "W02", "W03", "W04", "W05"):
            envelope = parse_envelope(json.dumps(base(controller_id="PARENT", lane=lane)))
            self.assertEqual(envelope.lane, lane)

    def test_cross_workspace_child_route_fails_closed(self):
        with self.assertRaises(GatewayError) as ctx:
            parse_envelope(json.dumps(base(controller_id="A", lane="W05")))
        self.assertEqual(ctx.exception.classification, ErrorClassification.INVALID_REQUEST)

    def test_session_required_by_action(self):
        with self.assertRaises(GatewayError):
            parse_envelope(json.dumps(base(session_id=None)))

    def test_unknown_fields_fail_closed(self):
        with self.assertRaises(GatewayError):
            parse_envelope(json.dumps(base(extra="nope")))

    def test_unsupported_action_fails_closed(self):
        with self.assertRaises(GatewayError):
            parse_envelope(json.dumps(base(action="approve_plan")))

    def test_public_secret_field_rejected(self):
        with self.assertRaises(GatewayError):
            parse_envelope(json.dumps(base(jules_api_key="secret")))

    def test_public_bearer_value_rejected(self):
        payload = base(authority_ref="Authorization: Bearer abcdefghijklmnop")
        with self.assertRaises(GatewayError):
            parse_envelope(json.dumps(payload))

    def test_oversized_request_rejected(self):
        raw = b"{" + b"x" * MAX_REQUEST_BYTES + b"}"
        with self.assertRaises(GatewayError):
            parse_envelope(raw)

    def test_expected_sha_and_digest_normalized(self):
        envelope = parse_envelope(
            json.dumps(
                base(
                    starting_branch="main",
                    expected_sha="A" * 40,
                    expected_plan_digest="B" * 64,
                )
            )
        )
        self.assertEqual(envelope.expected_sha, "a" * 40)
        self.assertEqual(envelope.expected_plan_digest, "b" * 64)

    def test_github_precondition_pair_is_atomic(self):
        with self.assertRaises(GatewayError):
            parse_envelope(json.dumps(base(starting_branch="main")))
        with self.assertRaises(GatewayError):
            parse_envelope(json.dumps(base(expected_sha="a" * 40)))

    def test_high_confidence_token_value_rejected(self):
        with self.assertRaises(GatewayError):
            parse_envelope(json.dumps(base(authority_ref="ghp_" + "A" * 30)))

    def test_options_are_bounded(self):
        with self.assertRaises(GatewayError):
            parse_envelope(json.dumps(base(options={"max_activity_pages": 999})))


if __name__ == "__main__":
    unittest.main()

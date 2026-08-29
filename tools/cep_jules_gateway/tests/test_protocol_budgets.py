from __future__ import annotations

import json
import unittest
from dataclasses import dataclass

from tools.cep_jules_gateway.envelope import parse_envelope
from tools.cep_jules_gateway.http import HttpResponse
from tools.cep_jules_gateway.inspect_bundle import build_inspect_bundle
from tools.cep_jules_gateway.jules import JulesClient
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError, PaginationInfo
from tools.cep_jules_gateway.pagination import Page, paginate


class FakeTransport:
    def __init__(self, responses):
        self.responses = list(responses)
        self.calls = []

    def request_json(self, method, url, *, headers, timeout, body=None):
        self.calls.append((method, url, body))
        return self.responses.pop(0)


@dataclass
class Result:
    items: list
    info: PaginationInfo


class InspectFake:
    def __init__(self, activities):
        self.activities = activities
        self.get_activity_calls = 0
        self.observations = []

    def get_session(self, session_id):
        return {"id": session_id, "state": "IN_PROGRESS", "updateTime": "u1"}

    def list_activities(self, session_id, *, page_size, max_pages):
        return Result(self.activities, PaginationInfo(1, len(self.activities), True, max_pages))

    def get_activity(self, activity_name):
        self.get_activity_calls += 1
        return next(item for item in self.activities if item["name"] == activity_name)


def inspect_envelope(**options):
    return parse_envelope(
        json.dumps(
            {
                "schema_version": "2.0",
                "request_id": "BUDGET-1",
                "controller_id": "PARENT",
                "lane": "W05",
                "action": "inspect_bundle",
                "session_id": "123",
                "options": options,
            }
        )
    )


class ProtocolAndBudgetTests(unittest.TestCase):
    def client(self, response, *, max_provider_reads=10):
        transport = FakeTransport([response])
        return JulesClient(
            "runtime-secret",
            api_base="https://example.invalid/v1alpha",
            transport=transport,
            max_provider_reads=max_provider_reads,
        ), transport

    def test_200_non_json_fails_closed(self):
        client, _ = self.client(HttpResponse(200, None, {}, "NON_JSON_PROVIDER_RESPONSE"))
        with self.assertRaises(GatewayError) as ctx:
            client.get_session("123")
        self.assertEqual(ErrorClassification.PROVIDER_PROTOCOL_FAILED, ctx.exception.classification)

    def test_200_json_array_where_object_required_fails_closed(self):
        client, _ = self.client(HttpResponse(200, [], {}))
        with self.assertRaises(GatewayError) as ctx:
            client.get_session("123")
        self.assertEqual(ErrorClassification.PROVIDER_PROTOCOL_FAILED, ctx.exception.classification)

    def test_200_malformed_session_fails_closed(self):
        client, _ = self.client(HttpResponse(200, {"state": "IN_PROGRESS"}, {}))
        with self.assertRaises(GatewayError) as ctx:
            client.get_session("123")
        self.assertEqual(ErrorClassification.PROVIDER_PROTOCOL_FAILED, ctx.exception.classification)

    def test_200_malformed_activity_collection_fails_closed(self):
        client, _ = self.client(HttpResponse(200, {"activities": {}}, {}))
        with self.assertRaises(GatewayError) as ctx:
            client.list_activities("123", page_size=100, max_pages=2)
        self.assertEqual(ErrorClassification.PROVIDER_PROTOCOL_FAILED, ctx.exception.classification)

    def test_cross_session_activity_identity_fails_closed(self):
        client, _ = self.client(
            HttpResponse(200, {"activities": [{"name": "sessions/999/activities/a1"}]}, {})
        )
        with self.assertRaises(GatewayError) as ctx:
            client.list_activities("123", page_size=100, max_pages=2)
        self.assertEqual(ErrorClassification.PROVIDER_PROTOCOL_FAILED, ctx.exception.classification)

    def test_global_provider_read_budget_is_shared(self):
        transport = FakeTransport(
            [
                HttpResponse(200, {"id": "123"}, {}),
                HttpResponse(200, {"id": "123"}, {}),
            ]
        )
        client = JulesClient("runtime-secret", api_base="https://example.invalid", transport=transport, max_provider_reads=1)
        client.get_session("123")
        with self.assertRaises(GatewayError) as ctx:
            client.get_session("123")
        self.assertEqual(ErrorClassification.READ_BUDGET_EXCEEDED, ctx.exception.classification)
        self.assertEqual(1, len(transport.calls))

    def test_total_item_bound_returns_explicit_partial_with_continuation(self):
        pages = {
            None: Page([{"id": 1}], "p2"),
            "p2": Page([{"id": 2}], "p3"),
        }
        result = paginate(lambda token: pages[token], max_pages=5, max_items=2)
        self.assertFalse(result.info.complete)
        self.assertEqual("TOTAL_ITEM_BOUND_REACHED", result.info.stop_reason)
        self.assertEqual("p3", result.info.next_page_token)

    def test_total_page_bound_returns_explicit_partial_when_total_bounds_enabled(self):
        result = paginate(lambda token: Page([{"id": 1}], "more"), max_pages=1, max_items=100)
        self.assertFalse(result.info.complete)
        self.assertEqual("TOTAL_PAGE_BOUND_REACHED", result.info.stop_reason)

    def test_shared_hydration_cache_reuses_one_activity_for_patch_and_bash(self):
        activity = {
            "name": "sessions/123/activities/a1",
            "createTime": "2026-08-29T10:00:00Z",
            "artifacts": [
                {"changeSet": {"gitPatch": {"unidiffPatch": "+safe"}}},
                {"bashOutput": {"command": "pytest", "output": "ok"}},
            ],
        }
        fake = InspectFake([activity])
        bundle = build_inspect_bundle(inspect_envelope(), fake)
        self.assertEqual(1, fake.get_activity_calls)
        self.assertEqual(1, bundle["budgets"]["hydration_reads"])
        self.assertGreaterEqual(bundle["budgets"]["hydration_cache_hits"], 1)

    def test_exact_text_total_budget_omits_instead_of_silently_truncating(self):
        activity = {
            "name": "sessions/123/activities/a1",
            "createTime": "2026-08-29T10:00:00Z",
            "artifacts": [
                {"changeSet": {"gitPatch": {"unidiffPatch": "p" * 700}}},
                {"bashOutput": {"command": "pytest", "output": "b" * 700}},
            ],
        }
        bundle = build_inspect_bundle(inspect_envelope(max_total_exact_text_bytes=1000), InspectFake([activity]))
        bash = bundle["bash_evidence"]["recent_exact"][0]
        self.assertEqual(ErrorClassification.OUTPUT_BUDGET_EXCEEDED.value, bash["text_omitted_reason"])
        self.assertNotIn("output", bash)
        self.assertEqual("PARTIAL", bundle["provider"]["completeness"])


if __name__ == "__main__":
    unittest.main()

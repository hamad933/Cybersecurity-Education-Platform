from __future__ import annotations

import urllib.parse
import unittest

from tools.cep_jules_gateway.http import HttpResponse
from tools.cep_jules_gateway.jules import JulesClient
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError


class FakeTransport:
    def __init__(self, responses):
        self.responses = list(responses)
        self.calls = []

    def request_json(self, method, url, *, headers, timeout, body=None):
        self.calls.append((method, url, headers, timeout, body))
        if not self.responses:
            raise AssertionError("unexpected transport call")
        return self.responses.pop(0)


class JulesClientTests(unittest.TestCase):
    def client(self, response):
        return JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=FakeTransport([response]))

    @staticmethod
    def activity(session_id: str, index: int):
        return {"name": f"sessions/{session_id}/activities/a{index:04d}", "createTime": f"2026-09-04T00:00:{index % 60:02d}Z"}

    def test_429_classification_and_retry_metadata(self):
        client = self.client(HttpResponse(429, {"error": "quota"}, {"retry-after": "12"}))
        with self.assertRaises(GatewayError) as ctx:
            client.get_session("123")
        self.assertEqual(ctx.exception.classification, ErrorClassification.RATE_LIMITED)
        self.assertEqual(ctx.exception.retry.retry_after_seconds, 12)

    def test_404_classification(self):
        client = self.client(HttpResponse(404, {}, {}))
        with self.assertRaises(GatewayError) as ctx:
            client.get_session("123")
        self.assertEqual(ctx.exception.classification, ErrorClassification.NOT_FOUND)

    def test_5xx_classification(self):
        client = self.client(HttpResponse(503, {}, {}))
        with self.assertRaises(GatewayError) as ctx:
            client.get_session("123")
        self.assertEqual(ctx.exception.classification, ErrorClassification.PROVIDER_READ_FAILED)

    def test_headers_are_not_exposed_in_observation(self):
        transport = FakeTransport([HttpResponse(200, {"id": "123"}, {})])
        client = JulesClient("do-not-print", api_base="https://example.invalid", transport=transport)
        client.get_session("123")
        self.assertNotIn("do-not-print", repr(client.observations))

    def test_missing_activities_is_allowed_only_on_terminal_page(self):
        session_id = "123"
        transport = FakeTransport([
            HttpResponse(200, {"activities": [self.activity(session_id, 1)], "nextPageToken": "t1"}, {}),
            HttpResponse(200, {}, {}),
        ])
        client = JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=transport)
        result = client.list_activities(session_id, page_size=25, max_pages=5)
        self.assertTrue(result.info.complete)
        self.assertEqual(len(result.items), 1)
        self.assertEqual(result.info.pages_scanned, 2)

    def test_missing_activities_with_continuation_fails_closed(self):
        client = self.client(HttpResponse(200, {"nextPageToken": "still-more"}, {}))
        with self.assertRaises(GatewayError) as ctx:
            client.list_activities("123", page_size=25, max_pages=5)
        self.assertEqual(ctx.exception.classification, ErrorClassification.PROVIDER_PROTOCOL_FAILED)
        self.assertTrue(ctx.exception.details["has_continuation"])

    def test_activities_wrong_type_fails_closed(self):
        client = self.client(HttpResponse(200, {"activities": {}}, {}))
        with self.assertRaises(GatewayError) as ctx:
            client.list_activities("123", page_size=25, max_pages=5)
        self.assertEqual(ctx.exception.classification, ErrorClassification.PROVIDER_PROTOCOL_FAILED)

    def test_response_too_large_adaptively_retries_only_read_with_smaller_page(self):
        session_id = "123"
        transport = FakeTransport([
            HttpResponse(200, None, {}, "PROVIDER_RESPONSE_TOO_LARGE"),
            HttpResponse(200, {"activities": [self.activity(session_id, 1)]}, {}),
        ])
        client = JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=transport)
        result = client.list_activities(session_id, page_size=25, max_pages=5)
        self.assertTrue(result.info.complete)
        self.assertEqual(result.info.requested_page_size, 25)
        self.assertEqual(result.info.effective_page_size, 12)
        self.assertIn("pageSize=25", transport.calls[0][1])
        self.assertIn("pageSize=12", transport.calls[1][1])
        self.assertTrue(all(call[0] == "GET" for call in transport.calls))

    def test_non_size_protocol_failure_is_not_retried(self):
        transport = FakeTransport([HttpResponse(200, None, {}, "NON_JSON_PROVIDER_RESPONSE")])
        client = JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=transport)
        with self.assertRaises(GatewayError):
            client.list_activities("123", page_size=25, max_pages=5)
        self.assertEqual(len(transport.calls), 1)

    def test_550_items_plus_empty_terminal_page_is_complete(self):
        session_id = "123"
        responses = []
        for page in range(22):
            start = page * 25
            payload = {"activities": [self.activity(session_id, start + i) for i in range(25)]}
            payload["nextPageToken"] = f"t{page + 1}"
            responses.append(HttpResponse(200, payload, {}))
        responses.append(HttpResponse(200, {}, {}))
        transport = FakeTransport(responses)
        client = JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=transport)
        result = client.list_activities(session_id, page_size=25, max_pages=80, max_items=2_000)
        self.assertTrue(result.info.complete)
        self.assertEqual(len(result.items), 550)
        self.assertEqual(result.info.pages_scanned, 23)
        self.assertEqual(client.provider_reads, 23)

    def test_repeated_token_fails_closed(self):
        session_id = "123"
        transport = FakeTransport([
            HttpResponse(200, {"activities": [self.activity(session_id, 1)], "nextPageToken": "same"}, {}),
            HttpResponse(200, {"activities": [self.activity(session_id, 2)], "nextPageToken": "same"}, {}),
        ])
        client = JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=transport)
        with self.assertRaises(GatewayError) as ctx:
            client.list_activities(session_id, page_size=25, max_pages=5)
        self.assertEqual(ctx.exception.classification, ErrorClassification.PROVIDER_READ_FAILED)

    def test_start_page_token_supports_bounded_continuation_without_claiming_durable_sync(self):
        session_id = "123"
        transport = FakeTransport([HttpResponse(200, {"activities": []}, {})])
        client = JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=transport)
        result = client.list_activities(session_id, page_size=25, max_pages=5, start_page_token="cursor+opaque")
        query = urllib.parse.parse_qs(urllib.parse.urlparse(transport.calls[0][1]).query)
        self.assertEqual(query["pageToken"], ["cursor+opaque"])
        self.assertEqual(result.info.start_page_token, "cursor+opaque")
        self.assertTrue(result.info.complete)

    def test_page_size_must_be_provider_bounded(self):
        client = self.client(HttpResponse(200, {"activities": []}, {}))
        with self.assertRaises(ValueError):
            client.list_activities("123", page_size=0, max_pages=5)
        self.assertEqual(client.provider_reads, 0)


if __name__ == "__main__":
    unittest.main()

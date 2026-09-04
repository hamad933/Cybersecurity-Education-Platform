from __future__ import annotations

import urllib.parse
import unittest

from tools.cep_jules_gateway.http import HttpResponse
from tools.cep_jules_gateway.jules import JulesClient


class FakeTransport:
    def __init__(self, responses):
        self.responses = list(responses)
        self.calls = []

    def request_json(self, method, url, *, headers, timeout, body=None):
        self.calls.append((method, url, body))
        if not self.responses:
            raise AssertionError("unexpected transport call")
        return self.responses.pop(0)


class ActivityFilterTests(unittest.TestCase):
    def test_create_time_filter_is_encoded_on_every_page_and_reported(self):
        timestamp = "2026-01-17T00:03:53.137240Z"
        transport = FakeTransport([
            HttpResponse(200, {"activities": [], "nextPageToken": "t1"}, {}),
            HttpResponse(200, {"activities": []}, {}),
        ])
        client = JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=transport)
        result = client.list_activities("123", page_size=25, max_pages=5, create_time=timestamp)
        self.assertTrue(result.info.complete)
        self.assertEqual(timestamp, result.info.activity_create_time_filter)
        for _, url, _ in transport.calls:
            query = urllib.parse.parse_qs(urllib.parse.urlparse(url).query)
            self.assertEqual([timestamp], query["createTime"])

    def test_create_time_filter_survives_response_size_page_fallback(self):
        timestamp = "2026-01-17T00:03:53Z"
        transport = FakeTransport([
            HttpResponse(200, None, {}, "PROVIDER_RESPONSE_TOO_LARGE"),
            HttpResponse(200, {"activities": []}, {}),
        ])
        client = JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=transport)
        result = client.list_activities("123", page_size=25, max_pages=5, create_time=timestamp)
        self.assertEqual(12, result.info.effective_page_size)
        self.assertEqual(timestamp, result.info.activity_create_time_filter)
        self.assertTrue(all(call[0] == "GET" for call in transport.calls))

    def test_invalid_create_time_filter_is_rejected_before_provider_read(self):
        transport = FakeTransport([HttpResponse(200, {"activities": []}, {})])
        client = JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=transport)
        with self.assertRaises(ValueError):
            client.list_activities("123", page_size=25, max_pages=5, create_time="x&nextPageToken=evil")
        self.assertEqual([], transport.calls)


if __name__ == "__main__":
    unittest.main()

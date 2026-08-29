from __future__ import annotations

import unittest

from tools.cep_jules_gateway.http import HttpResponse
from tools.cep_jules_gateway.jules import JulesClient
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError


class FakeTransport:
    def __init__(self, responses):
        self.responses = list(responses)
        self.calls = []

    def request_json(self, method, url, *, headers, timeout):
        self.calls.append((method, url, headers, timeout))
        return self.responses.pop(0)


class JulesClientTests(unittest.TestCase):
    def client(self, response):
        return JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=FakeTransport([response]))

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


if __name__ == "__main__":
    unittest.main()

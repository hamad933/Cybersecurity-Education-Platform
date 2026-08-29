from __future__ import annotations

import unittest

from tools.cep_jules_gateway.github import GitHubClient
from tools.cep_jules_gateway.http import HttpResponse
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError


class FakeTransport:
    def __init__(self, response):
        self.response = response
        self.calls = []

    def request_json(self, method, url, *, headers, timeout):
        self.calls.append((method, url, headers, timeout))
        return self.response


class GitHubClientTests(unittest.TestCase):
    def client(self, response):
        return GitHubClient(
            "runtime-token",
            "hamad933/Cybersecurity-Education-Platform",
            api_base="https://example.invalid",
            transport=FakeTransport(response),
        )

    def test_exact_branch_precondition(self):
        sha = "a" * 40
        client = self.client(HttpResponse(200, {"commit": {"sha": sha}}, {}))
        result = client.require_branch_head("feature/example", sha)
        self.assertEqual("MATCHED", result["status"])
        self.assertEqual(sha, result["actual_sha"])

    def test_branch_drift_fails_closed(self):
        client = self.client(HttpResponse(200, {"commit": {"sha": "b" * 40}}, {}))
        with self.assertRaises(GatewayError) as ctx:
            client.require_branch_head("main", "a" * 40)
        self.assertEqual(ErrorClassification.INVALID_STATE, ctx.exception.classification)

    def test_github_rate_limit_classification(self):
        client = self.client(HttpResponse(403, {}, {"x-ratelimit-remaining": "0", "x-ratelimit-reset": "123"}))
        with self.assertRaises(GatewayError) as ctx:
            client.get_branch_head("main")
        self.assertEqual(ErrorClassification.RATE_LIMITED, ctx.exception.classification)
        self.assertEqual("123", ctx.exception.retry.rate_limit_reset)


if __name__ == "__main__":
    unittest.main()

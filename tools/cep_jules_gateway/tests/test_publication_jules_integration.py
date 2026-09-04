from __future__ import annotations

import unittest

from tools.cep_jules_gateway.http import HttpResponse
from tools.cep_jules_gateway.jules import JulesClient
from tools.cep_jules_gateway.publication import (
    canonical_patch_paths,
    fetch_publication_candidate,
    paths_sha256,
    sha256_text,
)


REPOSITORY = "hamad933/Cybersecurity-Education-Platform"
SESSION_ID = "18400047935385743141"
SOURCE = f"sources/github/{REPOSITORY}"
BASE_SHA = "a" * 40
UPDATE_TIME = "2026-09-04T00:31:16.659535Z"
PATCH = """diff --git a/tests/gateway-proof.txt b/tests/gateway-proof.txt
new file mode 100644
--- /dev/null
+++ b/tests/gateway-proof.txt
@@ -0,0 +1 @@
+gateway-proof
"""


class FakeTransport:
    def __init__(self, responses):
        self.responses = list(responses)
        self.calls = []

    def request_json(self, method, url, *, headers, timeout, body=None):
        self.calls.append((method, url, body))
        if not self.responses:
            raise AssertionError("unexpected transport call")
        return self.responses.pop(0)


def activity(index: int, *, changeset: bool = False):
    value = {
        "name": f"sessions/{SESSION_ID}/activities/a{index:04d}",
        "createTime": f"2026-09-04T00:{index // 60:02d}:{index % 60:02d}Z",
    }
    if changeset:
        value["artifacts"] = [{"changeSet": {}}]
    return value


def hydrated_latest(index: int):
    value = activity(index, changeset=True)
    value["artifacts"] = [
        {
            "changeSet": {
                "source": SOURCE,
                "gitPatch": {
                    "baseCommitId": BASE_SHA,
                    "unidiffPatch": PATCH,
                },
            }
        }
    ]
    return value


def fetch(client: JulesClient):
    return fetch_publication_candidate(
        client,
        repository=REPOSITORY,
        session_id=SESSION_ID,
        expected_session_state="COMPLETED",
        expected_session_update_time=UPDATE_TIME,
        expected_base_sha=BASE_SHA,
        expected_review_sha256=sha256_text(PATCH),
        expected_paths_sha256=paths_sha256(canonical_patch_paths(PATCH)),
    )


class PublicationJulesIntegrationTests(unittest.TestCase):
    def test_w04_shaped_550_activity_scan_accepts_only_unambiguous_empty_terminal_page(self):
        responses = [
            HttpResponse(
                200,
                {"id": SESSION_ID, "state": "COMPLETED", "updateTime": UPDATE_TIME},
                {},
            )
        ]
        for page in range(22):
            start = page * 25
            items = [activity(start + i, changeset=(start + i == 549)) for i in range(25)]
            responses.append(HttpResponse(200, {"activities": items, "nextPageToken": f"t{page + 1}"}, {}))
        responses.append(HttpResponse(200, {}, {}))
        responses.append(HttpResponse(200, hydrated_latest(549), {}))

        transport = FakeTransport(responses)
        client = JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=transport)
        candidate = fetch(client)

        self.assertEqual(candidate.activity_name, f"sessions/{SESSION_ID}/activities/a0549")
        self.assertEqual(candidate.review_sha256, sha256_text(PATCH))
        self.assertEqual(client.provider_reads, 25)
        self.assertTrue(all(call[0] == "GET" for call in transport.calls))

    def test_publication_preflight_adapts_activity_page_size_only_for_safe_read_size_failure(self):
        responses = [
            HttpResponse(
                200,
                {"id": SESSION_ID, "state": "COMPLETED", "updateTime": UPDATE_TIME},
                {},
            ),
            HttpResponse(200, None, {}, "PROVIDER_RESPONSE_TOO_LARGE"),
            HttpResponse(200, {"activities": [activity(1, changeset=True)]}, {}),
            HttpResponse(200, hydrated_latest(1), {}),
        ]
        transport = FakeTransport(responses)
        client = JulesClient("runtime-secret", api_base="https://example.invalid/v1alpha", transport=transport)
        candidate = fetch(client)

        self.assertEqual(candidate.activity_name, f"sessions/{SESSION_ID}/activities/a0001")
        list_urls = [url for method, url, _ in transport.calls if "/activities?" in url]
        self.assertEqual(len(list_urls), 2)
        self.assertIn("pageSize=25", list_urls[0])
        self.assertIn("pageSize=12", list_urls[1])
        self.assertTrue(all(call[0] == "GET" for call in transport.calls))


if __name__ == "__main__":
    unittest.main()

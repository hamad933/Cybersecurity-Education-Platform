import unittest

from scripts.controller.jules_adapter import JulesAdapter


class RecordingAdapter(JulesAdapter):
    def __init__(self):
        super().__init__(api_key="test-key")
        self.calls = []
        self.pages = []

    def _make_request(self, endpoint, method="GET", payload=None):
        self.calls.append((endpoint, method, payload))
        if "/activities?" in endpoint:
            if "pageToken=" not in endpoint:
                return {
                    "activities": [{"id": "a1", "agentMessaged": {"agentMessage": "question"}}],
                    "nextPageToken": "next-token",
                }
            return {
                "activities": [{"id": "a2", "userMessaged": {"userMessage": "answer"}}]
            }
        return {}


class JulesAdapterContractTests(unittest.TestCase):
    def test_send_message_uses_current_prompt_field(self):
        adapter = RecordingAdapter()
        adapter.send_message("123", "hello")
        self.assertEqual(
            adapter.calls[-1],
            ("sessions/123:sendMessage", "POST", {"prompt": "hello"}),
        )

    def test_list_activities_follows_pagination(self):
        adapter = RecordingAdapter()
        activities = adapter.list_activities("123", page_size=20)
        self.assertEqual([item["id"] for item in activities], ["a1", "a2"])
        self.assertIn("pageSize=20", adapter.calls[0][0])
        self.assertIn("pageToken=next-token", adapter.calls[1][0])


if __name__ == "__main__":
    unittest.main()

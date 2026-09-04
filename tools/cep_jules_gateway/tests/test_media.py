from __future__ import annotations

import base64
import hashlib
import json
import tempfile
import unittest
from dataclasses import dataclass
from pathlib import Path

from tools.cep_jules_gateway.envelope import parse_envelope
from tools.cep_jules_gateway.inspect_bundle import build_inspect_bundle
from tools.cep_jules_gateway.media import collect_media_evidence
from tools.cep_jules_gateway.models import PaginationInfo


@dataclass
class Result:
    items: list
    info: PaginationInfo


class FakeClient:
    def __init__(self, activities):
        self.activities = activities
        self.observations = []

    def get_session(self, session_id):
        return {"id": session_id, "state": "IN_PROGRESS", "updateTime": "u1"}

    def list_activities(self, session_id, *, page_size, max_pages):
        return Result(self.activities, PaginationInfo(1, len(self.activities), True, max_pages))

    def get_activity(self, activity_name):
        return next(row for row in self.activities if row["name"] == activity_name)


def env():
    return parse_envelope(json.dumps({
        "schema_version": "2.0",
        "request_id": "REQ-MEDIA",
        "controller_id": "PARENT",
        "lane": "W05",
        "action": "inspect_bundle",
        "session_id": "123",
    }))


def activity(data: str, mime_type: str = "image/png"):
    return {
        "name": "sessions/123/activities/a1",
        "createTime": "2026-09-04T00:00:00Z",
        "artifacts": [{"media": {"mimeType": mime_type, "data": data}}],
    }


class MediaEvidenceTests(unittest.TestCase):
    def test_valid_media_is_hashed_and_externalized_without_base64_in_bundle(self):
        raw = b"\x89PNG\r\n\x1a\nsmall-proof"
        encoded = base64.b64encode(raw).decode("ascii")
        with tempfile.TemporaryDirectory() as tmp:
            media_dir = Path(tmp) / "media"
            bundle = build_inspect_bundle(env(), FakeClient([activity(encoded)]), media_output_dir=media_dir)
            evidence = bundle["media_evidence"]
            self.assertEqual("FOUND", evidence["status"])
            self.assertEqual(1, evidence["count"])
            row = evidence["index"][0]
            self.assertEqual("image/png", row["mime_type"])
            self.assertEqual(len(raw), row["decoded_bytes"])
            self.assertEqual(hashlib.sha256(raw).hexdigest(), row["sha256"])
            self.assertTrue(row["output_file"].startswith("media/media-0001-"))
            written = media_dir / Path(row["output_file"]).name
            self.assertEqual(raw, written.read_bytes())
            self.assertNotIn(encoded, json.dumps(bundle))
            self.assertEqual("COMPLETE", bundle["provider"]["completeness"])

    def test_invalid_base64_is_explicit_partial_evidence(self):
        bundle = build_inspect_bundle(env(), FakeClient([activity("not@@base64")]))
        self.assertEqual("PARTIAL", bundle["media_evidence"]["status"])
        self.assertEqual("MEDIA_BASE64_INVALID", bundle["media_evidence"]["errors"][0]["reason"])
        self.assertEqual("PARTIAL", bundle["provider"]["completeness"])

    def test_item_and_total_bounds_are_explicit(self):
        one = base64.b64encode(b"abcd").decode("ascii")
        rows = [
            {
                "name": "sessions/123/activities/a1",
                "createTime": "1",
                "artifacts": [
                    {"media": {"mimeType": "image/png", "data": one}},
                    {"media": {"mimeType": "image/png", "data": one}},
                ],
            }
        ]
        evidence = collect_media_evidence(rows, "123", max_item_bytes=4, max_total_bytes=4, max_items=10)
        self.assertEqual(1, evidence["count"])
        self.assertEqual("PARTIAL", evidence["status"])
        self.assertEqual("MEDIA_TOTAL_BOUND_EXCEEDED", evidence["errors"][0]["reason"])

    def test_mime_type_is_validated(self):
        encoded = base64.b64encode(b"safe").decode("ascii")
        evidence = collect_media_evidence([activity(encoded, "../../evil")], "123")
        self.assertEqual("PARTIAL", evidence["status"])
        self.assertEqual("MEDIA_MIME_TYPE_INVALID", evidence["errors"][0]["reason"])
        self.assertEqual([], evidence["index"])


if __name__ == "__main__":
    unittest.main()

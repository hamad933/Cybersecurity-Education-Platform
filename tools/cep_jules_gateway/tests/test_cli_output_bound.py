from __future__ import annotations

import json
import os
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

from tools.cep_jules_gateway import cli
from tools.cep_jules_gateway.models import PaginationInfo
from tools.cep_jules_gateway.pagination import PaginationResult


class LargeListClient:
    def __init__(self, *args, **kwargs):
        self.provider_reads = 1
        self.max_provider_reads = kwargs.get("max_provider_reads", 64)
        self.observations = []

    def list_sessions(self, *, page_size, max_pages, max_items):
        rows = [
            {"id": str(index + 1), "title": "X" * 200, "state": "IN_PROGRESS", "updateTime": "u"}
            for index in range(100)
        ]
        return PaginationResult(rows, PaginationInfo(1, len(rows), True, max_pages, max_items))


class CliOutputBoundTests(unittest.TestCase):
    def test_total_serialized_result_bound_fails_closed_without_result_file(self):
        env = os.environ.copy()
        env["CEP_JULES_REQUEST_JSON"] = json.dumps(
            {
                "schema_version": "2.0",
                "request_id": "REQ-OUTPUT-BOUND",
                "controller_id": "PARENT",
                "lane": "PARENT",
                "action": "list_sessions",
                "options": {"max_serialized_result_bytes": 10000},
            }
        )
        with tempfile.TemporaryDirectory() as tmp, patch.dict(os.environ, env, clear=True), patch.object(cli, "JulesClient", LargeListClient):
            rc = cli.main(["--request-env", "CEP_JULES_REQUEST_JSON", "--output-dir", tmp])
            self.assertEqual(2, rc)
            receipt = json.loads((Path(tmp) / "receipt.json").read_text(encoding="utf-8"))
            self.assertEqual("OUTPUT_BUDGET_EXCEEDED", receipt["error"]["classification"])
            self.assertFalse((Path(tmp) / "result.json").exists())


if __name__ == "__main__":
    unittest.main()

from __future__ import annotations

import json
import os
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

from tools.cep_jules_gateway.envelope import parse_envelope
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError
from tools.cep_jules_gateway.receipt import error_receipt


class ReceiptAndCliTests(unittest.TestCase):
    def test_public_safe_error_receipt(self):
        envelope = parse_envelope(json.dumps({
            "schema_version": "2.0",
            "request_id": "R1",
            "controller_id": "PARENT",
            "lane": "PARENT",
            "action": "list_sessions",
        }))
        receipt = error_receipt(envelope, GatewayError(ErrorClassification.AUTH_FAILED, "JULES_API_KEY=secret"))
        rendered = json.dumps(receipt)
        self.assertNotIn("secret", rendered)
        self.assertEqual(False, receipt["provider_mutation_performed"])

    def test_cli_module_path_and_fail_closed_missing_secret(self):
        env = os.environ.copy()
        env["CEP_JULES_REQUEST_JSON"] = json.dumps({
            "schema_version": "2.0",
            "request_id": "R-CLI",
            "controller_id": "PARENT",
            "lane": "PARENT",
            "action": "list_sessions",
        })
        env.pop("JULES_API_KEY", None)
        with tempfile.TemporaryDirectory() as tmp:
            proc = subprocess.run(
                [sys.executable, "-m", "tools.cep_jules_gateway.cli", "--request-env", "CEP_JULES_REQUEST_JSON", "--output-dir", tmp],
                cwd=Path(__file__).resolve().parents[3],
                env=env,
                text=True,
                capture_output=True,
                check=False,
            )
            self.assertEqual(2, proc.returncode)
            receipt = json.loads((Path(tmp) / "receipt.json").read_text(encoding="utf-8"))
            self.assertEqual("AUTH_FAILED", receipt["error"]["classification"])
            self.assertFalse(receipt["provider_mutation_performed"])


if __name__ == "__main__":
    unittest.main()

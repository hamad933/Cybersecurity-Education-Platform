from __future__ import annotations

import unittest

from tools.cep_jules_gateway.digest import sha256_json, sha256_text
from tools.cep_jules_gateway.sanitize import sanitize_obj, sanitize_text


class SanitizationTests(unittest.TestCase):
    def test_provider_specific_redaction(self):
        text = (
            "JULES_API_KEY=abc123 GDRIVE_SA_JSON_B64=qwerty "
            "Authorization: Bearer token.value x-goog-api-key: xyz"
        )
        safe = sanitize_text(text)
        self.assertNotIn("abc123", safe)
        self.assertNotIn("qwerty", safe)
        self.assertNotIn("token.value", safe)
        self.assertNotIn("xyz", safe)
        self.assertGreaterEqual(safe.count("[REDACTED]"), 4)

    def test_generic_assignment_redaction(self):
        safe = sanitize_text('client_secret="supersecret" password="hunter2"')
        self.assertNotIn("supersecret", safe)
        self.assertNotIn("hunter2", safe)

    def test_object_sensitive_key_redaction(self):
        safe = sanitize_obj({"api_key": "abc", "normal": "evidence"})
        self.assertEqual("[REDACTED]", safe["api_key"])
        self.assertEqual("evidence", safe["normal"])

    def test_high_confidence_token_redaction(self):
        text = "ghp_" + "A" * 30 + " AIza" + "B" * 30
        safe = sanitize_text(text)
        self.assertNotIn("A" * 30, safe)
        self.assertNotIn("B" * 30, safe)
        self.assertGreaterEqual(safe.count("[REDACTED]"), 2)

    def test_digest_is_deterministic(self):
        self.assertEqual(sha256_text("abc"), sha256_text("abc"))
        self.assertEqual(sha256_json({"b": 2, "a": 1}), sha256_json({"a": 1, "b": 2}))


if __name__ == "__main__":
    unittest.main()

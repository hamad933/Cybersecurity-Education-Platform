from __future__ import annotations

import unittest

from tools.cep_jules_gateway.idempotency import require_session_binding, session_binding_names
from tools.cep_jules_gateway.models import ErrorClassification, GatewayError


class Reader:
    def __init__(self):
        self.rows = {}

    def add(self, name):
        self.rows.setdefault(name, []).append({"name": name, "expired": False})

    def list_active_artifacts_by_name(self, name):
        return list(self.rows.get(name, []))


class SessionBindingTests(unittest.TestCase):
    def test_first_session_use_requires_binding_persistence(self):
        decision = require_session_binding(Reader(), "123", "CEP-TASK-1", "W03/simulator")
        self.assertTrue(decision.needs_persist)

    def test_same_task_domain_binding_is_reusable(self):
        reader = Reader()
        generic, specific = session_binding_names("123", "CEP-TASK-1", "W03/simulator")
        reader.add(generic)
        reader.add(specific)
        decision = require_session_binding(reader, "123", "CEP-TASK-1", "W03/simulator")
        self.assertFalse(decision.needs_persist)

    def test_different_task_domain_on_same_session_fails_closed(self):
        reader = Reader()
        generic, specific = session_binding_names("123", "CEP-TASK-1", "W03/simulator")
        reader.add(generic)
        reader.add(specific)
        with self.assertRaises(GatewayError) as ctx:
            require_session_binding(reader, "123", "CEP-TASK-2", "W04/progress")
        self.assertEqual(ErrorClassification.RECONCILIATION_REQUIRED, ctx.exception.classification)

    def test_partial_binding_persistence_fails_closed(self):
        reader = Reader()
        generic, _ = session_binding_names("123", "CEP-TASK-1", "W03/simulator")
        reader.add(generic)
        with self.assertRaises(GatewayError) as ctx:
            require_session_binding(reader, "123", "CEP-TASK-1", "W03/simulator")
        self.assertEqual(ErrorClassification.RECONCILIATION_REQUIRED, ctx.exception.classification)


if __name__ == "__main__":
    unittest.main()

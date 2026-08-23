"""
CEP Fast Control Plane - Jules Adapter Contract Tests.
Standard library unittest only (Python 3.10+).
"""

import os
import sys
import unittest

# Walk up from this test file directory until the repository root containing 'scripts' is found
_curr_dir = os.path.dirname(os.path.abspath(__file__))
_repo_root = _curr_dir
while _repo_root and _repo_root != os.path.dirname(_repo_root):
    if os.path.isdir(os.path.join(_repo_root, "scripts")):
        break
    _repo_root = os.path.dirname(_repo_root)

if _repo_root and _repo_root not in sys.path:
    sys.path.insert(0, _repo_root)

from scripts.controller.jules_adapter import JulesAdapter, JulesAdapterError
from scripts.controller.models import JulesSessionInfo, JulesState


class TestJulesAdapterContract(unittest.TestCase):
    def test_degraded_github_only_mode_when_key_missing(self):
        adapter = JulesAdapter(api_key="")
        self.assertTrue(adapter.is_degraded_mode)
        with self.assertRaises(JulesAdapterError) as ctx:
            adapter.list_sessions()
        self.assertEqual(ctx.exception.classification, "DEGRADED_GITHUB_ONLY_MODE")
        self.assertEqual(ctx.exception.status_code, 401)

    def test_session_state_normalization(self):
        state = JulesState.normalize("AWAITING_USER_FEEDBACK")
        self.assertEqual(state, JulesState.AWAITING_USER_FEEDBACK)

        state_unknown = JulesState.normalize("UNKNOWN_RANDOM_STATE")
        self.assertEqual(state_unknown, JulesState.UNKNOWN_JULES_STATE)


if __name__ == "__main__":
    unittest.main()

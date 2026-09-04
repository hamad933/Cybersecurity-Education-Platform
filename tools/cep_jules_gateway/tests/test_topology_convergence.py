from __future__ import annotations

import unittest
from pathlib import Path

from tools.cep_jules_gateway.envelope import _CONTROLLER_LANES as ENVELOPE_LANES
from tools.cep_jules_gateway.publication import _CONTROLLER_LANES as PUBLICATION_LANES
from tools.cep_jules_gateway.v22_contract import CONTROLLER_LANES as V22_LANES

ROOT = Path(__file__).resolve().parents[3]

EXPECTED = {
    "PARENT": {"PARENT", "W01", "W02", "W03", "W04", "W05", "W01_W02", "W03_W04"},
    "A": {"W01"},
    "B": {"W02"},
    "C": {"W03"},
    "D": {"W04"},
    "E": {"W05"},
}


class TopologyConvergenceTests(unittest.TestCase):
    def test_all_gateway_contracts_share_current_controller_lane_matrix(self):
        self.assertEqual(EXPECTED, ENVELOPE_LANES)
        self.assertEqual(EXPECTED, V22_LANES)
        self.assertEqual(EXPECTED, PUBLICATION_LANES)

    def test_v22_dispatch_choices_cover_current_controllers_and_lanes(self):
        workflow = (ROOT / ".github/workflows/cep-jules-v2-mutation.yml").read_text(encoding="utf-8")
        self.assertIn("options: [PARENT, A, B, C, D, E]", workflow)
        self.assertIn("options: [PARENT, W01, W02, W03, W04, W05, W01_W02, W03_W04]", workflow)


if __name__ == "__main__":
    unittest.main()

from __future__ import annotations

import importlib.util
from pathlib import Path
import unittest


VALIDATOR_PATH = Path(__file__).resolve().parents[1] / "validate_task004.py"
SPEC = importlib.util.spec_from_file_location("validate_task004", VALIDATOR_PATH)
if SPEC is None or SPEC.loader is None:
    raise RuntimeError("Unable to load Task 004 validator")
VALIDATOR = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(VALIDATOR)


class Task004ValidationTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.results, cls.warnings = VALIDATOR.validate_core(check_handoff=True)
        cls.text = "\n".join(cls.results)

    def test_required_artifacts_and_exact_schemas(self) -> None:
        self.assertIn("Task 004 baseline required files exist", self.text)
        self.assertEqual(sum("exact TSV schema" in line for line in self.results), 12)

    def test_identifiers_ownership_and_traceability(self) -> None:
        self.assertIn("requirement IDs are unique", self.text)
        self.assertIn("every persistent entity has exactly one ownership row", self.text)
        self.assertIn("every requirement has exactly one trace row", self.text)

    def test_product_and_architecture_invariants(self) -> None:
        for phrase in ["Manual AI Bridge is the only", "simulator is the default", "published revisions are immutable", "Modular Monolith"]:
            self.assertIn(phrase, self.text)

    def test_stack_and_no_scaffold(self) -> None:
        self.assertIn("preferred stack is consistently represented", self.text)
        self.assertIn("no product application scaffold exists", self.text)

    def test_prototype_and_rendering(self) -> None:
        for phrase in ["prototype is labeled", "all internal prototype route links resolve", "keyboard focus styles exist", "screenshot and render-report"]:
            self.assertIn(phrase, self.text)

    def test_task003r_safety(self) -> None:
        self.assertIn("Task 003R read-only core/hash validation passes", self.text)

    def test_handoff_hashes(self) -> None:
        self.assertIn("all handoff SHA-256 values verify", self.text)
        self.assertIn("handoff missing count is zero", self.text)

    def test_zip_integrity(self) -> None:
        self.assertIn("ZIP CRC integrity passes", self.text)
        self.assertIn("ZIP members and uncompressed sizes match", self.text)


if __name__ == "__main__":
    unittest.main()

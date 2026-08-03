from __future__ import annotations

import importlib.util
import sys
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[4]
VALIDATOR = ROOT / "product-repo" / "tools" / "semantic_architecture_refinement" / "validate_refined_semantic_baseline.py"


def load_validator():
    sys.path.insert(0, str(VALIDATOR.parent))
    spec = importlib.util.spec_from_file_location("task003r_validator", VALIDATOR)
    if spec is None or spec.loader is None:
        raise RuntimeError("validator import failed")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


class RefinedSemanticBaselineTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.validator = load_validator()
        cls.results, cls.warnings = cls.validator.validate_core(check_handoff=True)
        cls.tables = cls.validator.load_tables([])

    def test_full_validator_passes_many_assertions(self) -> None:
        self.assertGreater(len(self.results), 1000)

    def test_exact_required_schemas_are_present(self) -> None:
        self.assertEqual(set(self.tables), set(self.validator.SCHEMAS))

    def test_ku_count_and_vs001_ids(self) -> None:
        kus = self.tables["KNOWLEDGE_UNIT_CANDIDATES_REFINED.tsv"]
        self.assertEqual(len(kus), 96)
        self.assertIn("KU-AD-02", {row["knowledge_unit_id"] for row in kus})

    def test_capability_to_ku_is_not_one_to_one(self) -> None:
        from collections import Counter
        kus = self.tables["KNOWLEDGE_UNIT_CANDIDATES_REFINED.tsv"]
        counts = Counter(row["parent_capability_id"] for row in kus)
        self.assertEqual(sum(value > 1 for value in counts.values()), 6)
        self.assertEqual(106 - len(counts), 16)

    def test_product_charter_support_is_not_technical(self) -> None:
        rows = [row for row in self.tables["SOURCE_TO_CAPABILITY_MAP_REFINED.tsv"] if row["original_relative_path"].startswith("product-charter/")]
        self.assertTrue(rows)
        self.assertEqual({row["support_type"] for row in rows}, {"PRODUCT_REQUIREMENT_SUPPORT"})

    def test_ocr_sources_are_semantically_deferred(self) -> None:
        rows = [row for row in self.tables["UNIVERSITY_FILE_ASSESSMENTS.tsv"] if row["review_status"] == "DEFERRED_OCR_REQUIRED"]
        self.assertEqual(len(rows), 3)
        self.assertTrue(all(not row["semantic_evidence_ids"] for row in rows))

    def test_exact_repetition_is_reported_for_human_review(self) -> None:
        self.assertTrue(self.warnings)
        self.assertTrue(all(item.startswith("REVIEW exact repetition") for item in self.warnings))

    def test_handoff_zip_exists_and_passes(self) -> None:
        self.assertTrue(self.validator.HANDOFF_ZIP.is_file())


if __name__ == "__main__":
    unittest.main()

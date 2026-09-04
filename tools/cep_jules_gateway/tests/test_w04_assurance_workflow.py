from __future__ import annotations

import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]


class W04AssuranceWorkflowTests(unittest.TestCase):
    def setUp(self):
        self.text = (ROOT / ".github/workflows/cep-w04-exact-sha-assurance.yml").read_text(encoding="utf-8")

    def test_owner_main_and_exact_sha_gates_exist(self):
        self.assertIn("github.actor == github.repository_owner", self.text)
        self.assertIn("github.ref == 'refs/heads/main'", self.text)
        self.assertIn('test "$ACTUAL_SHA" = "$EXPECTED_SHA"', self.text)
        self.assertIn("persist-credentials: false", self.text)
        self.assertIn("permissions:\n  contents: read", self.text)

    def test_project_declared_runtime_versions_are_used(self):
        self.assertIn("postgres:18.4-bookworm", self.text)
        self.assertIn("docker build --target php-development", self.text)
        self.assertIn("composer install --no-interaction --no-progress --prefer-dist", self.text)
        self.assertIn("PDO::getAvailableDrivers()", self.text)
        self.assertIn("pg_backend_pid()", self.text)

    def test_w04_runtime_checks_are_material_and_exact(self):
        for command in (
            "php artisan migrate:fresh --force",
            "php artisan test tests/Feature/IntakeReview/EvidenceIntakeFeatureTest.php",
            "php artisan test tests/Integration/Evidence/EvidenceIntakeMigrationTest.php",
            "php artisan test tests/Integration/Evidence/EvidenceIntakeConcurrencyTest.php",
        ):
            self.assertIn(command, self.text)
        self.assertIn("rollback-reapply-backfill and immutability", self.text)
        self.assertIn("true multi-process PostgreSQL concurrency", self.text)

    def test_receipt_is_always_written_and_repository_is_read_only(self):
        self.assertIn('"schema_version": "cep.w04.exact_sha_assurance/v1"', self.text)
        self.assertIn('"repository_mutation_performed": False', self.text)
        self.assertIn("actions/upload-artifact@v4", self.text)
        self.assertIn("if: always()", self.text)
        self.assertIn("W04_EXACT_SHA_ASSURANCE=PASS", self.text)


if __name__ == "__main__":
    unittest.main()

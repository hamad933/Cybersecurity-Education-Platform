from __future__ import annotations

import csv
import hashlib
import sys
import tempfile
import unittest
from pathlib import Path
from unittest import mock


TOOL_ROOT = Path(__file__).resolve().parents[1]
if str(TOOL_ROOT) not in sys.path:
    sys.path.insert(0, str(TOOL_ROOT))

import inventory  # noqa: E402


class InventoryTests(unittest.TestCase):
    def setUp(self) -> None:
        self.temporary = tempfile.TemporaryDirectory()
        self.root = Path(self.temporary.name)
        self.source = self.root / "originals"
        self.manifests = self.root / "manifests"
        self.reports = self.root / "readiness"
        self.source.mkdir()

    def tearDown(self) -> None:
        self.temporary.cleanup()

    def run_tool(self, threshold: int = inventory.DEFAULT_LARGE_FILE_THRESHOLD):
        return inventory.generate(self.source, self.manifests, self.reports, threshold)

    def read_tsv(self, name: str) -> list[dict[str, str]]:
        with (self.manifests / name).open("r", encoding="utf-8", newline="") as stream:
            return list(csv.DictReader(stream, delimiter="\t"))

    def source_snapshot(self) -> dict[str, tuple[int, int, str]]:
        result = {}
        for path in sorted(self.source.rglob("*")):
            if path.is_file():
                relative = path.relative_to(self.source).as_posix()
                stat_result = path.stat()
                result[relative] = (
                    stat_result.st_size,
                    stat_result.st_mtime_ns,
                    hashlib.sha256(path.read_bytes()).hexdigest(),
                )
        return result

    def test_nested_directories_and_text_line_count(self) -> None:
        nested = self.source / "chatgpt-project" / "nested"
        nested.mkdir(parents=True)
        (nested / "notes.txt").write_text("one\ntwo\nthree", encoding="utf-8")

        self.run_tool()

        census = self.read_tsv("SOURCE_FILE_CENSUS.tsv")
        self.assertEqual(len(census), 1)
        self.assertEqual(census[0]["relative_path"], "chatgpt-project/nested/notes.txt")
        self.assertEqual(census[0]["line_count"], "3")
        self.assertEqual(census[0]["text_binary_classification"], "TEXT")
        summaries = {row["directory"]: row for row in self.read_tsv("DIRECTORY_SUMMARY.tsv")}
        self.assertEqual(summaries["."]["recursive_file_count"], "1")
        self.assertEqual(summaries["chatgpt-project/nested"]["direct_file_count"], "1")

    def test_empty_duplicate_and_large_threshold(self) -> None:
        group = self.source / "product-charter"
        group.mkdir()
        (group / "empty.txt").write_bytes(b"")
        (group / "copy-a.bin").write_bytes(b"same")
        (group / "copy-b.bin").write_bytes(b"same")

        self.run_tool(threshold=4)

        self.assertEqual(len(self.read_tsv("ZERO_BYTE_FILES.tsv")), 1)
        large_paths = {row["relative_path"] for row in self.read_tsv("LARGE_FILES.tsv")}
        self.assertEqual(large_paths, {"product-charter/copy-a.bin", "product-charter/copy-b.bin"})
        duplicates = self.read_tsv("DUPLICATE_FILE_HASHES.tsv")
        self.assertEqual(len(duplicates), 1)
        self.assertEqual(duplicates[0]["file_count"], "2")

    def test_unicode_arabic_filename_and_deterministic_ids(self) -> None:
        group = self.source / "chatgpt-project"
        group.mkdir()
        filename = "ملاحظات-é.txt"
        (group / filename).write_text("سطر أول\nسطر ثان\n", encoding="utf-8")

        self.run_tool()
        first = self.read_tsv("SOURCE_FILE_CENSUS.tsv")[0]
        self.run_tool()
        second = self.read_tsv("SOURCE_FILE_CENSUS.tsv")[0]

        self.assertEqual(first["source_record_id"], second["source_record_id"])
        self.assertIn("ملاحظات", first["relative_path"])
        self.assertEqual(
            inventory.source_record_id("folder/e\u0301.txt"),
            inventory.source_record_id("folder/é.txt"),
        )

    def test_binary_detection(self) -> None:
        group = self.source / "historical-platform"
        group.mkdir()
        (group / "payload.bin").write_bytes(b"\x00\x01\x02\xff" * 64)

        self.run_tool()

        row = self.read_tsv("SOURCE_FILE_CENSUS.tsv")[0]
        self.assertEqual(row["text_binary_classification"], "BINARY")
        self.assertEqual(row["line_count"], "")

    def test_portable_read_error_handling(self) -> None:
        target = self.source / "denied.txt"
        target.write_text("content", encoding="utf-8")
        original_open = Path.open

        def controlled_open(path: Path, *args, **kwargs):
            if path == target:
                raise PermissionError("portable simulated denial")
            return original_open(path, *args, **kwargs)

        with mock.patch.object(Path, "open", controlled_open):
            row = inventory.inspect_file(target, "denied.txt", 100)

        self.assertEqual(row["read_status"], "READ_ERROR")
        self.assertIn("portable simulated denial", row["error_message"])

    def test_university_course_aggregation(self) -> None:
        course_a = self.source / "university-courses" / "Course A"
        course_b = self.source / "university-courses" / "مقرر ب"
        course_a.mkdir(parents=True)
        course_b.mkdir(parents=True)
        (course_a / "lecture-01.txt").write_text("lesson\n", encoding="utf-8")
        (course_a / "syllabus.pdf").write_bytes(b"%PDF-mechanical-fixture")
        (course_b / "notes.md").write_text("# note\n", encoding="utf-8")

        self.run_tool()

        courses = {row["course_folder"]: row for row in self.read_tsv("UNIVERSITY_COURSE_INVENTORY.tsv")}
        self.assertEqual(courses["Course A"]["recursive_file_count"], "2")
        self.assertEqual(courses["Course A"]["apparent_lecture_file_count"], "1")
        self.assertEqual(courses["Course A"]["syllabus_like_filename_present"], "TRUE")
        self.assertEqual(courses["مقرر ب"]["completeness"], "UNKNOWN")
        self.assertEqual(courses["مقرر ب"]["semantic_quality"], "NOT_REVIEWED")

    def test_no_writes_under_source_root(self) -> None:
        group = self.source / "ad-identity-pilot"
        group.mkdir()
        (group / "source.txt").write_text("immutable\n", encoding="utf-8")
        before = self.source_snapshot()

        self.run_tool()

        after = self.source_snapshot()
        self.assertEqual(before, after)
        self.assertFalse((self.source / "SOURCE_FILE_CENSUS.tsv").exists())

    def test_overlapping_output_root_is_rejected(self) -> None:
        with self.assertRaises(inventory.InventoryError):
            inventory.generate(
                self.source,
                self.source / "manifests",
                self.reports,
            )


if __name__ == "__main__":
    unittest.main()

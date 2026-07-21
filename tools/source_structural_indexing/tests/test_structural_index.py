from __future__ import annotations

import csv
import hashlib
import sys
import tempfile
import unittest
from pathlib import Path


TOOL_ROOT = Path(__file__).resolve().parents[1]
if str(TOOL_ROOT) not in sys.path:
    sys.path.insert(0, str(TOOL_ROOT))

import structural_index as structural  # noqa: E402
from pypdf import PdfWriter  # noqa: E402
from pypdf.generic import (  # noqa: E402
    DecodedStreamObject,
    DictionaryObject,
    NameObject,
)


CENSUS_COLUMNS = (
    "source_record_id", "relative_path", "parent_directory", "filename",
    "extension", "size_bytes", "sha256", "last_modified_utc",
    "file_category", "top_level_source_group", "zero_byte", "large_file",
    "text_binary_classification", "line_count", "read_status",
    "error_message",
)


class StructuralIndexTests(unittest.TestCase):
    def setUp(self) -> None:
        self.temporary = tempfile.TemporaryDirectory()
        self.workspace = Path(self.temporary.name)
        self.source = self.workspace / "source-vault" / "originals"
        self.inputs = self.workspace / "source-vault" / "manifests"
        self.outputs = self.inputs / "structural"
        self.reports = self.workspace / "source-vault" / "derived" / "structural"
        self.source.mkdir(parents=True)
        self.inputs.mkdir(parents=True)
        self.records: list[dict[str, str]] = []

    def tearDown(self) -> None:
        self.temporary.cleanup()

    def add_file(self, relative: str, data: bytes, classification: str = "TEXT", large: bool = False) -> dict[str, str]:
        path = self.source / Path(relative)
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_bytes(data)
        normalized = relative.replace("\\", "/")
        filename = normalized.rsplit("/", 1)[-1]
        extension = Path(filename).suffix.lower()
        record = {
            "source_record_id": "src-" + structural.stable_hash(normalized)[:24],
            "relative_path": normalized,
            "parent_directory": normalized.rsplit("/", 1)[0] if "/" in normalized else ".",
            "filename": filename,
            "extension": extension,
            "size_bytes": str(len(data)),
            "sha256": hashlib.sha256(data).hexdigest(),
            "last_modified_utc": "2026-01-01T00:00:00Z",
            "file_category": "document",
            "top_level_source_group": normalized.split("/", 1)[0],
            "zero_byte": structural.bool_text(len(data) == 0),
            "large_file": structural.bool_text(large),
            "text_binary_classification": classification,
            "line_count": "",
            "read_status": "READ_OK",
            "error_message": "",
        }
        self.records.append(record)
        return record

    def write_inputs(self) -> None:
        (self.inputs / "SOURCE_FILE_CENSUS.tsv").write_text(
            structural.tsv_text(CENSUS_COLUMNS, self.records), encoding="utf-8"
        )
        large = [record for record in self.records if record["large_file"] == "TRUE"]
        (self.inputs / "LARGE_FILES.tsv").write_text(
            structural.tsv_text(CENSUS_COLUMNS, large), encoding="utf-8"
        )

    def text_pdf(self, path: Path) -> None:
        writer = PdfWriter()
        page = writer.add_blank_page(width=300, height=300)
        font = DictionaryObject({
            NameObject("/Type"): NameObject("/Font"),
            NameObject("/Subtype"): NameObject("/Type1"),
            NameObject("/BaseFont"): NameObject("/Helvetica"),
        })
        font_reference = writer._add_object(font)
        page[NameObject("/Resources")] = DictionaryObject({
            NameObject("/Font"): DictionaryObject({NameObject("/F1"): font_reference})
        })
        content = DecodedStreamObject()
        content.set_data(b"BT /F1 12 Tf 72 200 Td (Hello structural PDF) Tj ET")
        page[NameObject("/Contents")] = writer._add_object(content)
        with path.open("wb") as stream:
            writer.write(stream)

    def blank_pdf(self, path: Path) -> None:
        writer = PdfWriter()
        writer.add_blank_page(width=300, height=300)
        with path.open("wb") as stream:
            writer.write(stream)

    def snapshot(self) -> dict[str, tuple[int, int, str]]:
        result = {}
        for path in self.source.rglob("*"):
            if path.is_file():
                stat_result = path.stat()
                result[path.relative_to(self.source).as_posix()] = (
                    stat_result.st_size,
                    stat_result.st_mtime_ns,
                    hashlib.sha256(path.read_bytes()).hexdigest(),
                )
        return result

    def test_01_markdown_nested_headings_and_stable_section_ids(self) -> None:
        record = self.add_file("chatgpt-project/doc.md", b"# Root\ntext\n## Child\nmore\n")
        first_stats, first_sections, _ = structural.scan_text_file(self.source / record["relative_path"], record)
        second_stats, second_sections, _ = structural.scan_text_file(self.source / record["relative_path"], record)
        self.assertEqual(first_stats["heading_count"], 2)
        self.assertEqual([row["segment_candidate_id"] for row in first_sections], [row["segment_candidate_id"] for row in second_sections])
        self.assertEqual(first_sections[1]["heading_path_or_title"], "Root > Child")

    def test_02_heading_free_deterministic_chunk_boundaries(self) -> None:
        data = ("plain line\n" * 1601).encode("utf-8")
        record = self.add_file("chatgpt-project/plain.txt", data)
        _, sections, _ = structural.scan_text_file(self.source / record["relative_path"], record)
        self.assertGreaterEqual(len(sections), 2)
        self.assertEqual(sections[0]["end_line"], structural.HARD_CHUNK_LINES)

    def test_03_very_large_line_is_bounded_and_counted(self) -> None:
        data = b"x" * (2 * 1024 * 1024)
        record = self.add_file("chatgpt-project/huge.txt", data, large=True)
        stats, sections, _ = structural.scan_text_file(self.source / record["relative_path"], record)
        self.assertEqual(stats["line_count"], 1)
        self.assertEqual(stats["extremely_long_line_count"], 1)
        self.assertEqual(sum(int(row["end_byte"]) - int(row["start_byte"]) for row in sections), len(data))

    def test_04_arabic_unicode_heading_and_filename(self) -> None:
        record = self.add_file("chatgpt-project/مقدمة-é.md", "# مقدمة الأمن\nنص\n".encode("utf-8"))
        _, sections, headings = structural.scan_text_file(self.source / record["relative_path"], record)
        self.assertEqual(headings, ["مقدمة الأمن"])
        self.assertEqual(sections[0]["heading_path_or_title"], "مقدمة الأمن")

    def test_05_markdown_table_code_fence_and_link_counts(self) -> None:
        data = b"# H\n| a | b |\n|---|---|\n```py\nprint(1)\n```\n[link](https://example.invalid)\n"
        record = self.add_file("chatgpt-project/facts.md", data)
        stats, _, _ = structural.scan_text_file(self.source / record["relative_path"], record)
        self.assertEqual(stats["table_like_line_count"], 2)
        self.assertEqual(stats["code_fence_count"], 1)
        self.assertGreaterEqual(stats["link_count"], 1)

    def test_06_csv_tsv_streaming_header_and_rows(self) -> None:
        csv_path = self.source / "data.csv"
        csv_path.write_text("a,b\n1,2\n3,4\n", encoding="utf-8")
        result, warning = structural.inspect_delimited(csv_path, ",")
        self.assertEqual(warning, "")
        self.assertEqual(result["header"], ["a", "b"])
        self.assertEqual(result["row_count"], 2)
        self.assertEqual(result["column_count"], 2)

    def test_07_json_and_yaml_structural_key_inventory(self) -> None:
        json_path = self.source / "data.json"
        yaml_path = self.source / "data.yaml"
        json_path.write_text('{"alpha": {"beta": 1}, "items": [1, 2]}', encoding="utf-8")
        yaml_path.write_text("alpha:\n  beta: 1\nitems:\n  - one\n", encoding="utf-8")
        json_keys, count, status, warning = structural.inspect_json(json_path)
        yaml_keys, approximate = structural.inspect_top_level_keys(yaml_path, yaml_mode=True)
        self.assertEqual(status, "JSON_PARSED")
        self.assertEqual(warning, "")
        self.assertEqual(set(json_keys), {"alpha", "items"})
        self.assertGreaterEqual(count, 3)
        self.assertEqual(yaml_keys, ["alpha", "items"])
        self.assertGreater(approximate, 0)

    def test_08_python_ast_symbols_without_execution(self) -> None:
        path = self.source / "danger.py"
        path.write_text("raise RuntimeError('must not run')\nclass A: pass\ndef f(): return 1\n", encoding="utf-8")
        symbols, status, warning = structural.inspect_python(path)
        self.assertEqual(status, "PYTHON_AST_PARSED")
        self.assertEqual(warning, "")
        self.assertEqual(symbols, ["A", "f"])

    def test_09_pdf_page_count_and_text_status(self) -> None:
        path = self.source / "text.pdf"
        self.text_pdf(path)
        record = self.add_file("copy-text.pdf", path.read_bytes(), classification="BINARY")
        document, pages, sections, errors, _ = structural.inspect_pdf(self.source / record["relative_path"], record)
        self.assertEqual(document["page_count"], 1)
        self.assertGreater(pages[0]["extracted_character_count"], 0)
        self.assertEqual(document["text_availability"], "PDF_TEXT_AVAILABLE")
        self.assertEqual(len(sections), 1)
        self.assertEqual(errors, [])

    def test_10_empty_text_pdf_classified_without_ocr(self) -> None:
        path = self.source / "blank.pdf"
        self.blank_pdf(path)
        record = self.add_file("copy-blank.pdf", path.read_bytes(), classification="BINARY")
        document, pages, _, _, _ = structural.inspect_pdf(self.source / record["relative_path"], record)
        self.assertEqual(pages[0]["text_status"], "NO_EXTRACTABLE_TEXT")
        self.assertEqual(document["ocr_review_state"], "OCR_REQUIRED_FOR_SEMANTIC_REVIEW")
        self.assertNotEqual(document["parser_state"], "PDF_PARSE_ERROR")

    def test_11_university_sequence_inference_and_gap(self) -> None:
        self.add_file("university-courses/Course/lecture-01.md", b"# One\n")
        self.add_file("university-courses/Course/lecture-03.md", b"# Three\n")
        rows, gaps = structural.build_course_structure(self.records, {})
        self.assertEqual(gaps["Course"], [2])
        self.assertTrue(all("SEQUENCE_GAPS_OBSERVED" in row["structural_flags"] for row in rows))

    def test_12_placeholder_zero_byte_not_flagged(self) -> None:
        self.add_file("chatgpt-project/.gitkeep", b"")
        self.add_file("chatgpt-project/__init__.py", b"")
        rows = structural.possibly_incomplete_rows(self.records, [], {})
        self.assertEqual(rows, [])

    def test_13_task_like_zero_byte_yaml_flagged(self) -> None:
        record = self.add_file("chatgpt-project/audit_tasks.yml", b"")
        rows = structural.possibly_incomplete_rows(self.records, [], {})
        self.assertEqual(rows[0]["rule_id"], "INC-EMPTY-TASK-LIKE")
        self.assertEqual(rows[0]["source_record_id"], record["source_record_id"])
        self.assertEqual(rows[0]["review_state"], "REQUIRES_HUMAN_REVIEW")

    def test_14_multiple_provisional_domain_candidates(self) -> None:
        record = self.add_file("chatgpt-project/active-directory-network-security.md", b"# Kerberos and firewall\n")
        _, sections, headings = structural.scan_text_file(self.source / record["relative_path"], record)
        config = structural.load_domain_config(TOOL_ROOT / "domain_keywords.json")
        rows = structural.classify_domains(
            self.records,
            {record["source_record_id"]: sections},
            {record["source_record_id"]: headings},
            {},
            config,
        )
        codes = {row["candidate_domain_code"] for row in rows}
        self.assertIn("D03", codes)
        self.assertIn("D04", codes)
        self.assertTrue(all(row["state"] == "PROVISIONAL_NOT_SEMANTICALLY_VALIDATED" for row in rows))

    def test_15_unclassified_when_signal_is_insufficient(self) -> None:
        self.add_file("chatgpt-project/xyz.bin", b"\x00\x01", classification="BINARY")
        config = structural.load_domain_config(TOOL_ROOT / "domain_keywords.json")
        rows = structural.classify_domains(self.records, {}, {}, {}, config)
        self.assertEqual(len(rows), 1)
        self.assertEqual(rows[0]["candidate_domain_code"], "UNCLASSIFIED")

    def test_16_output_root_overlap_rejected(self) -> None:
        with self.assertRaises(structural.StructuralIndexError):
            structural.validate_roots(
                self.source,
                self.inputs,
                self.source / "structural",
                self.reports,
            )

    def test_17_no_writes_under_originals(self) -> None:
        self.add_file("chatgpt-project/readme.md", b"# Safe\ntext\n")
        self.write_inputs()
        before = self.snapshot()
        structural.generate(
            self.source, self.inputs, self.outputs, self.reports,
            queue_limit=10, validate_inputs=False,
        )
        self.assertEqual(before, self.snapshot())

    def test_18_deterministic_rerun_hashes(self) -> None:
        self.add_file("chatgpt-project/readme.md", b"# Safe\ntext\n")
        self.add_file("university-courses/C/lecture-01.txt", b"lesson\n")
        self.write_inputs()
        first = structural.generate(
            self.source, self.inputs, self.outputs, self.reports,
            queue_limit=10, validate_inputs=False,
        )["output_hashes_sha256"]
        second = structural.generate(
            self.source, self.inputs, self.outputs, self.reports,
            queue_limit=10, validate_inputs=False,
        )["output_hashes_sha256"]
        self.assertEqual(first, second)


if __name__ == "__main__":
    unittest.main()

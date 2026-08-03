#!/usr/bin/env python3
"""Deterministic structural indexing and bounded semantic triage.

The tool reads original sources without modification. It performs structural
inspection only and never executes source code, decompresses archives, performs
OCR, or makes semantic correctness or curriculum decisions.
"""

from __future__ import annotations

import argparse
import ast
import codecs
import csv
import ctypes
import hashlib
import io
import json
import logging
import os
import platform
import re
import sys
import tempfile
import unicodedata
from collections import Counter, defaultdict
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Iterable, Sequence

try:
    import pypdf
    from pypdf import PdfReader
except ImportError:  # pragma: no cover - covered by CLI dependency validation
    pypdf = None
    PdfReader = None


TOOL_VERSION = "2.0.0"
DEFAULT_QUEUE_LIMIT = 100
READ_CHUNK_SIZE = 64 * 1024
MAX_BUFFERED_LINE_BYTES = 64 * 1024
EXTREMELY_LONG_LINE_BYTES = 1024 * 1024
SOFT_CHUNK_LINES = 1200
HARD_CHUNK_LINES = 1500
SOFT_CHUNK_BYTES = 384 * 1024
HARD_CHUNK_BYTES = 512 * 1024
MAX_FULL_PARSE_BYTES = 8 * 1024 * 1024
MAX_INVENTORY_ITEMS = 100

TEXT_DOCUMENT_EXTENSIONS = {".md", ".txt", ".rst", ".log", ".diff", ".back"}
DELIMITED_EXTENSIONS = {".csv", ".tsv"}
YAML_EXTENSIONS = {".yaml", ".yml"}
CODE_EXTENSIONS = {
    ".bat", ".css", ".go", ".h", ".hpp", ".html", ".java", ".js",
    ".jsx", ".j2", ".lua", ".php", ".pl", ".ps1", ".py", ".r",
    ".rb", ".scss", ".sh", ".sql", ".ts", ".tsx", ".vue",
}
CONFIG_EXTENSIONS = {".cfg", ".conf", ".env", ".example", ".example2", ".ini", ".toml"}
PLACEHOLDER_FILENAMES = {".gitkeep", ".keep", ".placeholder"}

STRUCTURAL_COLUMNS = (
    "source_record_id", "relative_path", "parser_type", "parse_status",
    "line_count", "character_count", "heading_count", "code_fence_count",
    "table_like_line_count", "link_count", "extremely_long_line_count",
    "top_level_inventory", "top_level_symbol_or_key_count",
    "approximate_object_count", "row_count", "column_count", "page_count",
    "text_availability", "section_page_candidate_count", "semantic_state",
    "error_reference",
)

SECTION_COLUMNS = (
    "segment_candidate_id", "source_record_id", "relative_path",
    "candidate_type", "heading_path_or_title", "start_line", "end_line",
    "start_byte", "end_byte", "pdf_start_page", "pdf_end_page",
    "character_estimate", "line_estimate", "section_sha256",
    "selection_state",
)

LARGE_COLUMNS = (
    "source_record_id", "relative_path", "size_bytes", "parser_type",
    "structural_partition_strategy", "candidate_section_count",
    "extremely_long_line_count", "table_heavy_generated_indicator",
    "recommended_bounded_read_method",
    "manual_semantic_review_feasible_without_prechunking",
)

PDF_DOCUMENT_COLUMNS = (
    "source_record_id", "relative_path", "page_count", "encrypted",
    "text_extraction_permitted", "metadata_title", "metadata_author",
    "metadata_subject", "metadata_creator", "metadata_producer",
    "first_level_outline_titles", "outline_count", "pages_with_text",
    "pages_without_extractable_text", "total_extracted_characters",
    "text_availability", "ocr_review_state", "parser_state",
    "error_reference",
)

PDF_PAGE_COLUMNS = (
    "page_candidate_id", "source_record_id", "relative_path", "page_number",
    "extracted_character_count", "text_status", "section_sha256",
    "extraction_error_reference",
)

COURSE_COLUMNS = (
    "source_record_id", "relative_path", "course_folder",
    "file_order_candidate", "sequence_number_candidate",
    "filename_derived_title", "file_type", "size_bytes", "pdf_page_count",
    "text_availability", "duplicate_relationship",
    "apparent_missing_sequence_numbers", "syllabus_like_filename_present",
    "structural_flags", "semantic_state",
)

ERROR_COLUMNS = (
    "error_reference", "source_record_id", "relative_path", "parser_type",
    "severity", "error_code", "message",
)

INCOMPLETE_COLUMNS = (
    "rule_id", "source_record_id", "relative_path", "evidence", "severity",
    "review_state",
)

DOMAIN_COLUMNS = (
    "source_record_id", "relative_path", "segment_candidate_id",
    "candidate_domain_code", "candidate_domain_label", "matched_terms",
    "match_locations", "deterministic_score", "confidence_band", "state",
)

QUEUE_COLUMNS = (
    "queue_item_id", "source_record_id", "relative_path",
    "segment_candidate_id", "candidate_domains", "reason_for_review",
    "mechanical_priority_score", "priority_band", "required_inspection_depth",
    "state",
)


class StructuralIndexError(RuntimeError):
    """Configuration, dependency, or safety validation failure."""


class WarningCaptureHandler(logging.Handler):
    """Capture dependency warnings so they become deterministic review rows."""

    def __init__(self) -> None:
        super().__init__(level=logging.WARNING)
        self.messages: list[str] = []

    def emit(self, log_record: logging.LogRecord) -> None:
        self.messages.append(clean_cell(log_record.getMessage()))


def utc_now_text() -> str:
    return datetime.now(timezone.utc).isoformat(timespec="microseconds").replace("+00:00", "Z")


def normalize_text(value: str) -> str:
    return unicodedata.normalize("NFC", value)


def normalize_relative(value: str | Path) -> str:
    raw = str(value).replace("\\", "/")
    parts = [normalize_text(part) for part in raw.split("/") if part not in ("", ".")]
    return "/".join(parts) if parts else "."


def sort_key(value: str) -> bytes:
    return normalize_text(value).encode("utf-8")


def stable_hash(*parts: Any) -> str:
    payload = "\0".join(normalize_text(str(part)) for part in parts)
    return hashlib.sha256(payload.encode("utf-8")).hexdigest()


def bool_text(value: bool) -> str:
    return "TRUE" if value else "FALSE"


def clean_cell(value: Any, limit: int = 2000) -> str:
    text = normalize_text(str(value or "")).replace("\t", " ").replace("\r", " ").replace("\n", " ")
    return text[:limit]


def paths_overlap(first: Path, second: Path) -> bool:
    try:
        first.relative_to(second)
        return True
    except ValueError:
        pass
    try:
        second.relative_to(first)
        return True
    except ValueError:
        return False


def validate_roots(
    source_root: Path,
    input_manifest_root: Path,
    output_manifest_root: Path,
    report_root: Path,
) -> tuple[Path, Path, Path, Path]:
    source = source_root.expanduser().resolve(strict=True)
    inputs = input_manifest_root.expanduser().resolve(strict=True)
    outputs = output_manifest_root.expanduser().resolve(strict=False)
    reports = report_root.expanduser().resolve(strict=False)
    if not source.is_dir() or not inputs.is_dir():
        raise StructuralIndexError("Source and input manifest roots must be directories")
    if paths_overlap(source, outputs) or paths_overlap(source, reports):
        raise StructuralIndexError("Output roots must not overlap the strictly read-only source root")
    if paths_overlap(outputs, reports):
        raise StructuralIndexError("Structural manifest and report roots must not overlap")
    if outputs == inputs:
        raise StructuralIndexError("Structural outputs must not replace the approved input manifest root")
    return source, inputs, outputs, reports


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        while True:
            chunk = stream.read(1024 * 1024)
            if not chunk:
                break
            digest.update(chunk)
    return digest.hexdigest()


def atomic_write_text(path: Path, content: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    temporary_name: str | None = None
    try:
        with tempfile.NamedTemporaryFile(
            mode="w", encoding="utf-8", newline="", dir=path.parent,
            prefix=f".{path.name}.", suffix=".tmp", delete=False,
        ) as temporary:
            temporary.write(content)
            temporary.flush()
            os.fsync(temporary.fileno())
            temporary_name = temporary.name
        os.replace(temporary_name, path)
        temporary_name = None
    finally:
        if temporary_name:
            try:
                Path(temporary_name).unlink()
            except OSError:
                pass


def tsv_text(columns: Sequence[str], rows: Iterable[dict[str, Any]]) -> str:
    output = io.StringIO(newline="")
    writer = csv.DictWriter(
        output, fieldnames=columns, delimiter="\t", lineterminator="\n",
        extrasaction="ignore",
    )
    writer.writeheader()
    for row in rows:
        writer.writerow({column: row.get(column, "") for column in columns})
    return output.getvalue()


def read_tsv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as stream:
        return list(csv.DictReader(stream, delimiter="\t"))


def source_metadata_fingerprint(source_root: Path) -> tuple[str, int]:
    rows: list[str] = []
    for current, dirnames, filenames in os.walk(source_root, topdown=True, followlinks=False):
        current_path = Path(current)
        dirnames[:] = sorted(
            [name for name in dirnames if not (current_path / name).is_symlink()],
            key=sort_key,
        )
        for filename in sorted(filenames, key=sort_key):
            path = current_path / filename
            if path.is_symlink():
                continue
            stat_result = path.stat()
            relative = normalize_relative(path.relative_to(source_root))
            rows.append(f"{relative}\t{stat_result.st_size}\t{stat_result.st_mtime_ns}")
    rows.sort(key=sort_key)
    return hashlib.sha256("\n".join(rows).encode("utf-8")).hexdigest(), len(rows)


def process_peak_memory() -> dict[str, Any]:
    """Return best-effort process peak memory using only the standard library."""
    if os.name == "nt":
        try:
            from ctypes import wintypes

            class ProcessMemoryCounters(ctypes.Structure):
                _fields_ = [
                    ("cb", wintypes.DWORD),
                    ("PageFaultCount", wintypes.DWORD),
                    ("PeakWorkingSetSize", ctypes.c_size_t),
                    ("WorkingSetSize", ctypes.c_size_t),
                    ("QuotaPeakPagedPoolUsage", ctypes.c_size_t),
                    ("QuotaPagedPoolUsage", ctypes.c_size_t),
                    ("QuotaPeakNonPagedPoolUsage", ctypes.c_size_t),
                    ("QuotaNonPagedPoolUsage", ctypes.c_size_t),
                    ("PagefileUsage", ctypes.c_size_t),
                    ("PeakPagefileUsage", ctypes.c_size_t),
                ]

            counters = ProcessMemoryCounters()
            counters.cb = ctypes.sizeof(ProcessMemoryCounters)
            kernel32 = ctypes.WinDLL("kernel32", use_last_error=True)
            kernel32.GetCurrentProcess.restype = wintypes.HANDLE
            get_memory_info = kernel32.K32GetProcessMemoryInfo
            get_memory_info.argtypes = [
                wintypes.HANDLE,
                ctypes.POINTER(ProcessMemoryCounters),
                wintypes.DWORD,
            ]
            get_memory_info.restype = wintypes.BOOL
            handle = kernel32.GetCurrentProcess()
            success = get_memory_info(handle, ctypes.byref(counters), counters.cb)
            if success:
                return {
                    "method": "Windows GetProcessMemoryInfo PeakWorkingSetSize",
                    "peak_bytes": int(counters.PeakWorkingSetSize),
                    "limitation": "Process working-set peak includes interpreter and dependency memory and may include shared resident pages.",
                }
        except (AttributeError, OSError, ValueError):
            pass
    else:
        try:
            import resource

            usage = resource.getrusage(resource.RUSAGE_SELF)
            multiplier = 1 if sys.platform == "darwin" else 1024
            return {
                "method": "resource.getrusage ru_maxrss",
                "peak_bytes": int(usage.ru_maxrss * multiplier),
                "limitation": "ru_maxrss units are normalized by platform convention and may not include every child or shared allocation.",
            }
        except (ImportError, OSError, ValueError):
            pass
    return {
        "method": "UNAVAILABLE",
        "peak_bytes": None,
        "limitation": "The platform did not expose a supported standard-library peak-memory counter.",
    }


def approved_001_files(workspace_root: Path, input_manifest_root: Path) -> list[Path]:
    paths: list[Path] = []
    roots = (
        workspace_root / "product-repo" / "tools" / "source_readiness",
        workspace_root / "product-repo" / "review-packets" / "source-readiness-001",
        input_manifest_root.parent / "derived" / "readiness",
    )
    for root in roots:
        if root.is_dir():
            paths.extend(path for path in root.rglob("*") if path.is_file())
    paths.extend(path for path in input_manifest_root.iterdir() if path.is_file())
    return sorted(set(paths), key=lambda path: sort_key(normalize_relative(path.relative_to(workspace_root))))


def file_set_fingerprint(workspace_root: Path, paths: Sequence[Path]) -> tuple[str, int]:
    rows = [
        f"{normalize_relative(path.relative_to(workspace_root))}\t{sha256_file(path)}"
        for path in paths
    ]
    rows.sort(key=sort_key)
    return hashlib.sha256("\n".join(rows).encode("utf-8")).hexdigest(), len(rows)


def validate_approved_inputs(workspace_root: Path, input_manifest_root: Path) -> dict[str, Any]:
    required = (
        input_manifest_root / "SOURCE_FILE_CENSUS.tsv",
        input_manifest_root / "SOURCE_MANIFEST.tsv",
        input_manifest_root / "DIRECTORY_SUMMARY.tsv",
        input_manifest_root / "LARGE_FILES.tsv",
        input_manifest_root / "ZERO_BYTE_FILES.tsv",
        input_manifest_root / "UNIVERSITY_COURSE_INVENTORY.tsv",
        input_manifest_root.parent / "derived" / "readiness" / "SOURCE_READINESS_REPORT.md",
        input_manifest_root.parent / "derived" / "readiness" / "TOOL_RUN_METADATA.json",
        workspace_root / "product-repo" / "tools" / "source_readiness" / "inventory.py",
        workspace_root / "product-repo" / "tools" / "source_readiness" / "tests" / "test_inventory.py",
    )
    missing = [str(path) for path in required if not path.is_file()]
    if missing:
        raise StructuralIndexError("Missing approved Source Readiness 001 inputs: " + "; ".join(missing))
    metadata_path = input_manifest_root.parent / "derived" / "readiness" / "TOOL_RUN_METADATA.json"
    metadata = json.loads(metadata_path.read_text(encoding="utf-8"))
    for relative, expected_hash in metadata.get("output_hashes_sha256", {}).items():
        target = input_manifest_root.parent / Path(relative)
        if not target.is_file() or sha256_file(target) != expected_hash:
            raise StructuralIndexError(f"Approved 001 output hash mismatch: {relative}")
    approved_files = approved_001_files(workspace_root, input_manifest_root)
    fingerprint, count = file_set_fingerprint(workspace_root, approved_files)
    return {
        "validated": True,
        "required_input_count": len(required),
        "approved_file_count": count,
        "approved_fingerprint": fingerprint,
        "readiness_counts": metadata.get("counts", {}),
    }


def detect_encoding(path: Path) -> str:
    with path.open("rb") as stream:
        sample = stream.read(4)
    if sample.startswith((b"\x00\x00\xfe\xff", b"\xff\xfe\x00\x00")):
        return "utf-32"
    if sample.startswith((b"\xfe\xff", b"\xff\xfe")):
        return "utf-16"
    if sample.startswith(b"\xef\xbb\xbf"):
        return "utf-8-sig"
    return "utf-8"


def error_row(
    source_id: str,
    relative_path: str,
    parser_type: str,
    severity: str,
    code: str,
    message: str,
) -> dict[str, str]:
    cleaned = clean_cell(message)
    reference = "serr-" + stable_hash(source_id, relative_path, parser_type, code, cleaned)[:16]
    return {
        "error_reference": reference,
        "source_record_id": source_id,
        "relative_path": relative_path,
        "parser_type": parser_type,
        "severity": severity,
        "error_code": code,
        "message": cleaned,
    }


class CandidateBuilder:
    def __init__(
        self,
        source_id: str,
        relative_path: str,
        source_hash: str,
        ordinal: int,
        candidate_type: str,
        heading: str,
        start_line: int,
        start_byte: int,
    ) -> None:
        self.source_id = source_id
        self.relative_path = relative_path
        self.source_hash = source_hash
        self.ordinal = ordinal
        self.candidate_type = candidate_type
        self.heading = clean_cell(heading, 1000)
        self.start_line = start_line
        self.start_byte = start_byte
        self.bytes = 0
        self.lines = 0
        self.characters = 0
        self.digest = hashlib.sha256()

    def add(self, raw: bytes, character_count: int) -> None:
        self.digest.update(raw)
        self.bytes += len(raw)
        self.characters += character_count

    def finish_line(self) -> None:
        self.lines += 1

    def row(self, end_line: int, end_byte: int) -> dict[str, Any]:
        basis = self.heading if self.heading else f"chunk-{self.ordinal:06d}"
        candidate_id = "seg-" + stable_hash(
            self.source_id, basis, self.ordinal, self.source_hash
        )[:24]
        return {
            "segment_candidate_id": candidate_id,
            "source_record_id": self.source_id,
            "relative_path": self.relative_path,
            "candidate_type": self.candidate_type,
            "heading_path_or_title": self.heading,
            "start_line": self.start_line,
            "end_line": end_line,
            "start_byte": self.start_byte,
            "end_byte": end_byte,
            "pdf_start_page": "",
            "pdf_end_page": "",
            "character_estimate": self.characters,
            "line_estimate": self.lines,
            "section_sha256": self.digest.hexdigest(),
            "selection_state": "NOT_SELECTED",
        }


HEADING_RE = re.compile(r"^\s{0,3}(#{1,6})\s+(.+?)\s*#*\s*$")
FENCE_RE = re.compile(r"^\s*(```|~~~)")
LINK_RE = re.compile(r"\[[^\]]+\]\([^)]+\)|https?://", re.IGNORECASE)
RST_UNDERLINE_RE = re.compile(r"^[=\-~^`:#*+]{3,}$")


def scan_text_file(path: Path, record: dict[str, str]) -> tuple[dict[str, Any], list[dict[str, Any]], list[str]]:
    source_id = record["source_record_id"]
    relative = record["relative_path"]
    source_hash = record.get("sha256", "")
    extension = record.get("extension", "").lower()
    encoding = detect_encoding(path)
    decoder = codecs.getincrementaldecoder(encoding)(errors="replace")
    stats: dict[str, Any] = {
        "line_count": 0,
        "character_count": 0,
        "heading_count": 0,
        "code_fence_count": 0,
        "table_like_line_count": 0,
        "link_count": 0,
        "extremely_long_line_count": 0,
        "decoding_replacement_count": 0,
    }
    candidates: list[dict[str, Any]] = []
    heading_titles: list[str] = []
    heading_stack: list[str] = []
    current: CandidateBuilder | None = None
    ordinal = 0
    byte_offset = 0
    line_number = 1
    in_fence = False
    previous_short_line = ""
    pending_rst_heading: tuple[str, int] | None = None

    def finalize(end_line: int, end_byte: int) -> None:
        nonlocal current
        if current is not None and (current.bytes > 0 or current.lines > 0):
            candidates.append(current.row(end_line, end_byte))
        current = None

    def start(candidate_type: str, heading: str, start_line: int, start_byte: int) -> None:
        nonlocal current, ordinal
        ordinal += 1
        current = CandidateBuilder(
            source_id, relative, source_hash, ordinal, candidate_type,
            heading, start_line, start_byte,
        )

    def account_line(prefix_text: str, line_bytes: int) -> tuple[bool, str, int]:
        nonlocal in_fence, previous_short_line, pending_rst_heading
        stripped = prefix_text.strip().lstrip("\ufeff")
        heading_match = HEADING_RE.match(stripped) if extension in {".md", ".txt"} else None
        heading_title = ""
        heading_level = 0
        if heading_match:
            heading_level = len(heading_match.group(1))
            heading_title = clean_cell(heading_match.group(2), 500)
        if line_bytes > EXTREMELY_LONG_LINE_BYTES:
            stats["extremely_long_line_count"] += 1
        fence_match = FENCE_RE.match(stripped)
        if fence_match:
            if not in_fence:
                stats["code_fence_count"] += 1
            in_fence = not in_fence
        if not in_fence and stripped.startswith("|") and stripped.count("|") >= 2:
            stats["table_like_line_count"] += 1
        stats["link_count"] += len(LINK_RE.findall(prefix_text))
        if extension == ".rst" and previous_short_line and RST_UNDERLINE_RE.fullmatch(stripped):
            level = 1 if stripped[0] == "=" else 2
            pending_rst_heading = (previous_short_line, level)
            stats["heading_count"] += 1
            heading_titles.append(previous_short_line)
        previous_short_line = clean_cell(stripped, 500) if 0 < len(stripped) <= 500 else ""
        return bool(heading_match), heading_title, heading_level

    with path.open("rb") as stream:
        while True:
            line_start = byte_offset
            raw = stream.readline(MAX_BUFFERED_LINE_BYTES)
            if not raw:
                break

            if pending_rst_heading is not None:
                finalize(line_number - 1, line_start)
                title, level = pending_rst_heading
                heading_stack[:] = heading_stack[: level - 1]
                heading_stack.append(title)
                start("HEADING_SECTION", " > ".join(heading_stack), line_number, line_start)
                pending_rst_heading = None

            is_oversized = len(raw) == MAX_BUFFERED_LINE_BYTES and not raw.endswith(b"\n")
            decoded = decoder.decode(raw, final=False)
            stats["character_count"] += len(decoded)
            stats["decoding_replacement_count"] += decoded.count("\ufffd")
            prefix = decoded[:8192]

            if not is_oversized:
                is_heading, heading_title, heading_level = account_line(prefix, len(raw))
                if is_heading:
                    finalize(line_number - 1, line_start)
                    heading_stack[:] = heading_stack[: heading_level - 1]
                    heading_stack.append(heading_title)
                    heading_titles.append(heading_title)
                    stats["heading_count"] += 1
                    start("HEADING_SECTION", " > ".join(heading_stack), line_number, line_start)
                elif current is None:
                    start("STRUCTURAL_CHUNK", "", line_number, line_start)
                assert current is not None
                current.add(raw, len(decoded))
                current.finish_line()
                byte_offset += len(raw)
                stats["line_count"] += 1
                blank = not prefix.strip()
            else:
                if current is None:
                    start("STRUCTURAL_CHUNK", "", line_number, line_start)
                assert current is not None
                total_line_bytes = 0
                prefix_parts = [prefix]
                while True:
                    current.add(raw, len(decoded))
                    total_line_bytes += len(raw)
                    byte_offset += len(raw)
                    if raw.endswith(b"\n"):
                        break
                    raw = stream.readline(MAX_BUFFERED_LINE_BYTES)
                    if not raw:
                        break
                    decoded = decoder.decode(raw, final=False)
                    stats["character_count"] += len(decoded)
                    stats["decoding_replacement_count"] += decoded.count("\ufffd")
                    if sum(len(part) for part in prefix_parts) < 8192:
                        prefix_parts.append(decoded[: 8192 - sum(len(part) for part in prefix_parts)])
                prefix = "".join(prefix_parts)
                account_line(prefix, total_line_bytes)
                current.finish_line()
                stats["line_count"] += 1
                blank = False

            assert current is not None
            at_soft_limit = current.lines >= SOFT_CHUNK_LINES or current.bytes >= SOFT_CHUNK_BYTES
            at_hard_limit = current.lines >= HARD_CHUNK_LINES or current.bytes >= HARD_CHUNK_BYTES
            if at_hard_limit or (at_soft_limit and blank):
                finalize(line_number, byte_offset)
            line_number += 1

        tail = decoder.decode(b"", final=True)
        stats["character_count"] += len(tail)
        stats["decoding_replacement_count"] += tail.count("\ufffd")
        if current is not None:
            current.characters += len(tail)
        finalize(max(line_number - 1, 0), byte_offset)

    return stats, candidates, heading_titles


def bounded_text(path: Path, max_bytes: int = MAX_FULL_PARSE_BYTES) -> tuple[str | None, str]:
    if path.stat().st_size > max_bytes:
        return None, "BOUNDED_SCAN_ONLY"
    encoding = detect_encoding(path)
    with path.open("r", encoding=encoding, errors="replace", newline="") as stream:
        return stream.read(max_bytes + 1), "FULL_BOUNDED_READ"


def inspect_json(path: Path) -> tuple[list[str], int, str, str]:
    text, mode = bounded_text(path)
    if text is None:
        return [], 0, "BOUNDED_TEXTUAL_JSON_SCAN", ""
    try:
        value = json.loads(text)
    except (json.JSONDecodeError, UnicodeError) as exc:
        return [], 0, "JSON_PARSE_WARNING", f"{type(exc).__name__}: {exc}"
    keys = [clean_cell(key, 300) for key in value.keys()] if isinstance(value, dict) else []
    count = 0
    stack = [value]
    while stack:
        item = stack.pop()
        if isinstance(item, dict):
            count += 1
            stack.extend(item.values())
        elif isinstance(item, list):
            count += 1
            stack.extend(item)
    return keys[:MAX_INVENTORY_ITEMS], count, "JSON_PARSED", ""


YAML_KEY_RE = re.compile(r"^([A-Za-z0-9_.\-\u0600-\u06ff]+)\s*:")
GENERIC_KEY_RE = re.compile(r"^\s*([A-Za-z_][A-Za-z0-9_.\-]*)\s*[:=]")
GENERIC_SYMBOL_RE = re.compile(
    r"^\s*(?:export\s+)?(?:async\s+)?(?:class|function|interface|type|const|let|var|def)\s+([A-Za-z_$][\w$]*)"
)


def inspect_top_level_keys(path: Path, yaml_mode: bool = False) -> tuple[list[str], int]:
    keys: list[str] = []
    approximate_objects = 0
    encoding = detect_encoding(path)
    with path.open("r", encoding=encoding, errors="replace", newline="") as stream:
        for line in stream:
            if len(line) > MAX_BUFFERED_LINE_BYTES:
                line = line[:MAX_BUFFERED_LINE_BYTES]
            if yaml_mode:
                stripped = line.rstrip("\r\n")
                if stripped and not stripped[0].isspace() and not stripped.startswith(("#", "-")):
                    match = YAML_KEY_RE.match(stripped)
                    if match and len(keys) < MAX_INVENTORY_ITEMS:
                        keys.append(clean_cell(match.group(1), 300))
                if stripped.lstrip().startswith("-") or ":" in stripped:
                    approximate_objects += 1
            else:
                match = GENERIC_KEY_RE.match(line)
                if match and not line[: len(line) - len(line.lstrip())] and len(keys) < MAX_INVENTORY_ITEMS:
                    keys.append(clean_cell(match.group(1), 300))
    return sorted(set(keys), key=sort_key), approximate_objects


def inspect_python(path: Path) -> tuple[list[str], str, str]:
    text, mode = bounded_text(path)
    if text is None:
        symbols = inspect_generic_symbols(path)
        return symbols, "PYTHON_BOUNDED_SYMBOL_SCAN", ""
    try:
        tree = ast.parse(text, filename=path.name)
    except (SyntaxError, ValueError, TypeError) as exc:
        return [], "PYTHON_AST_WARNING", f"{type(exc).__name__}: {exc}"
    symbols = [
        node.name for node in tree.body
        if isinstance(node, (ast.ClassDef, ast.FunctionDef, ast.AsyncFunctionDef))
    ]
    return symbols[:MAX_INVENTORY_ITEMS], "PYTHON_AST_PARSED", ""


def inspect_generic_symbols(path: Path) -> list[str]:
    symbols: list[str] = []
    encoding = detect_encoding(path)
    with path.open("r", encoding=encoding, errors="replace", newline="") as stream:
        for line in stream:
            match = GENERIC_SYMBOL_RE.match(line[:MAX_BUFFERED_LINE_BYTES])
            if match:
                symbols.append(clean_cell(match.group(1), 300))
                if len(symbols) >= MAX_INVENTORY_ITEMS:
                    break
    return symbols


def inspect_delimited(path: Path, delimiter: str) -> tuple[dict[str, Any], str]:
    encoding = detect_encoding(path)
    header: list[str] = []
    row_count = 0
    malformed = 0
    column_count = 0
    expected_columns: int | None = None
    try:
        csv.field_size_limit(16 * 1024 * 1024)
        with path.open("r", encoding=encoding, errors="replace", newline="") as stream:
            reader = csv.reader(stream, delimiter=delimiter)
            for row in reader:
                if expected_columns is None:
                    header = [clean_cell(field, 300) for field in row[:MAX_INVENTORY_ITEMS]]
                    expected_columns = len(row)
                    column_count = len(row)
                else:
                    row_count += 1
                    if len(row) != expected_columns:
                        malformed += 1
                    column_count = max(column_count, len(row))
    except (csv.Error, OSError, UnicodeError) as exc:
        return {
            "header": header,
            "row_count": row_count,
            "column_count": column_count,
            "malformed_row_count": malformed,
        }, f"{type(exc).__name__}: {exc}"
    return {
        "header": header,
        "row_count": row_count,
        "column_count": column_count,
        "malformed_row_count": malformed,
    }, ""


def flatten_outline_titles(outline: Any) -> list[str]:
    if not isinstance(outline, list):
        return []
    titles: list[str] = []
    for item in outline:
        if isinstance(item, list):
            continue
        title = getattr(item, "title", None)
        if title and len(titles) < MAX_INVENTORY_ITEMS:
            titles.append(clean_cell(title, 500))
    return titles


def inspect_pdf(
    path: Path,
    record: dict[str, str],
) -> tuple[dict[str, Any], list[dict[str, Any]], list[dict[str, Any]], list[dict[str, str]], list[str]]:
    if PdfReader is None or pypdf is None:
        raise StructuralIndexError("pypdf is required for PDF structural inspection")
    source_id = record["source_record_id"]
    relative = record["relative_path"]
    errors: list[dict[str, str]] = []
    pages: list[dict[str, Any]] = []
    sections: list[dict[str, Any]] = []
    outline_titles: list[str] = []
    document: dict[str, Any] = {
        "source_record_id": source_id,
        "relative_path": relative,
        "page_count": 0,
        "encrypted": "UNKNOWN",
        "text_extraction_permitted": "UNKNOWN",
        "metadata_title": "",
        "metadata_author": "",
        "metadata_subject": "",
        "metadata_creator": "",
        "metadata_producer": "",
        "first_level_outline_titles": "",
        "outline_count": 0,
        "pages_with_text": 0,
        "pages_without_extractable_text": 0,
        "total_extracted_characters": 0,
        "text_availability": "UNKNOWN",
        "ocr_review_state": "NOT_ASSESSED",
        "parser_state": "PDF_PARSE_ERROR",
        "error_reference": "",
    }
    try:
        reader = PdfReader(str(path), strict=False)
        encrypted = bool(reader.is_encrypted)
        document["encrypted"] = bool_text(encrypted)
        permitted = True
        if encrypted:
            try:
                decrypt_result = reader.decrypt("")
                permitted = bool(decrypt_result)
            except Exception as exc:  # pypdf exposes format-specific exception types
                permitted = False
                errors.append(error_row(source_id, relative, "PDF_PYPDF", "ERROR", "PDF_DECRYPT_FAILED", f"{type(exc).__name__}: {exc}"))
        document["text_extraction_permitted"] = bool_text(permitted)
        if not permitted:
            document["text_availability"] = "ENCRYPTED_NOT_INSPECTED"
            document["ocr_review_state"] = "HUMAN_ACCESS_REVIEW_REQUIRED"
            document["parser_state"] = "PDF_ENCRYPTED_UNAVAILABLE"
            document["error_reference"] = ";".join(row["error_reference"] for row in errors)
            return document, pages, sections, errors, outline_titles

        metadata = reader.metadata or {}
        metadata_fields = {
            "metadata_title": "/Title",
            "metadata_author": "/Author",
            "metadata_subject": "/Subject",
            "metadata_creator": "/Creator",
            "metadata_producer": "/Producer",
        }
        for output_field, metadata_key in metadata_fields.items():
            try:
                document[output_field] = clean_cell(metadata.get(metadata_key, ""), 500)
            except Exception:
                document[output_field] = ""
        try:
            outline_titles = flatten_outline_titles(reader.outline)
        except Exception as exc:
            errors.append(error_row(source_id, relative, "PDF_PYPDF", "WARNING", "PDF_OUTLINE_WARNING", f"{type(exc).__name__}: {exc}"))
        document["first_level_outline_titles"] = " | ".join(outline_titles)
        document["outline_count"] = len(outline_titles)
        document["page_count"] = len(reader.pages)

        for page_number, page in enumerate(reader.pages, start=1):
            text = ""
            page_error_reference = ""
            try:
                text = page.extract_text() or ""
                character_count = len(text)
                if character_count == 0:
                    status = "NO_EXTRACTABLE_TEXT"
                elif character_count < 20:
                    status = "LIMITED_EXTRACTABLE_TEXT"
                else:
                    status = "TEXT_AVAILABLE"
            except Exception as exc:
                character_count = 0
                status = "TEXT_EXTRACTION_ERROR"
                row = error_row(
                    source_id, relative, "PDF_PYPDF", "WARNING",
                    "PDF_PAGE_EXTRACTION_ERROR", f"page {page_number}: {type(exc).__name__}: {exc}",
                )
                errors.append(row)
                page_error_reference = row["error_reference"]
            text_hash = hashlib.sha256(text.encode("utf-8")).hexdigest() if text else stable_hash(record.get("sha256", ""), "page", page_number, status)
            page_candidate_id = "page-" + stable_hash(source_id, page_number, record.get("sha256", ""))[:24]
            page_row = {
                "page_candidate_id": page_candidate_id,
                "source_record_id": source_id,
                "relative_path": relative,
                "page_number": page_number,
                "extracted_character_count": character_count,
                "text_status": status,
                "section_sha256": text_hash,
                "extraction_error_reference": page_error_reference,
            }
            pages.append(page_row)
            sections.append({
                "segment_candidate_id": page_candidate_id,
                "source_record_id": source_id,
                "relative_path": relative,
                "candidate_type": "PDF_PAGE",
                "heading_path_or_title": "",
                "start_line": "",
                "end_line": "",
                "start_byte": "",
                "end_byte": "",
                "pdf_start_page": page_number,
                "pdf_end_page": page_number,
                "character_estimate": character_count,
                "line_estimate": "",
                "section_sha256": text_hash,
                "selection_state": "NOT_SELECTED",
            })
            if character_count > 0:
                document["pages_with_text"] += 1
            else:
                document["pages_without_extractable_text"] += 1
            document["total_extracted_characters"] += character_count
            text = ""

        page_count = int(document["page_count"])
        no_text_pages = int(document["pages_without_extractable_text"])
        total_characters = int(document["total_extracted_characters"])
        likely_scanned = page_count > 0 and (
            total_characters < page_count * 20 or no_text_pages / page_count >= 0.8
        )
        if likely_scanned:
            document["text_availability"] = "LIKELY_SCANNED_OR_IMAGE_ONLY"
            document["ocr_review_state"] = "OCR_REQUIRED_FOR_SEMANTIC_REVIEW"
            document["parser_state"] = "PDF_PARSED_TEXT_LIMITED"
        else:
            document["text_availability"] = "PDF_TEXT_AVAILABLE"
            document["ocr_review_state"] = "OCR_NOT_CURRENTLY_REQUIRED"
            document["parser_state"] = "PDF_PARSED"
    except Exception as exc:
        errors.append(error_row(source_id, relative, "PDF_PYPDF", "ERROR", "PDF_PARSE_FAILED", f"{type(exc).__name__}: {exc}"))
        document["text_availability"] = "UNAVAILABLE_PARSE_FAILURE"
        document["ocr_review_state"] = "NOT_APPLICABLE_PARSE_FAILURE"
        document["parser_state"] = "PDF_PARSE_ERROR"
    document["error_reference"] = ";".join(row["error_reference"] for row in errors)
    return document, pages, sections, errors, outline_titles


def parser_for(record: dict[str, str]) -> str:
    extension = record.get("extension", "").lower()
    classification = record.get("text_binary_classification", "UNKNOWN")
    if extension == ".pdf":
        return "PDF_PYPDF"
    if extension in DELIMITED_EXTENSIONS:
        return "DELIMITED_STREAM"
    if extension == ".json":
        return "JSON_STANDARD_LIBRARY"
    if extension in YAML_EXTENSIONS:
        return "YAML_STRUCTURAL_STREAM"
    if extension == ".py":
        return "PYTHON_AST"
    if extension in CODE_EXTENSIONS or extension in CONFIG_EXTENSIONS:
        return "CODE_CONFIG_STRUCTURAL"
    if extension in TEXT_DOCUMENT_EXTENSIONS or classification == "TEXT":
        return "TEXT_STREAM"
    return "OPAQUE_BINARY"


def empty_structural_row(record: dict[str, str], parser_type: str) -> dict[str, Any]:
    return {
        "source_record_id": record["source_record_id"],
        "relative_path": record["relative_path"],
        "parser_type": parser_type,
        "parse_status": "NOT_STARTED",
        "line_count": "",
        "character_count": "",
        "heading_count": "",
        "code_fence_count": "",
        "table_like_line_count": "",
        "link_count": "",
        "extremely_long_line_count": "",
        "top_level_inventory": "",
        "top_level_symbol_or_key_count": 0,
        "approximate_object_count": "",
        "row_count": "",
        "column_count": "",
        "page_count": "",
        "text_availability": "NOT_APPLICABLE",
        "section_page_candidate_count": 0,
        "semantic_state": "NOT_SEMANTICALLY_INSPECTED",
        "error_reference": "",
    }


def inspect_non_pdf(
    path: Path,
    record: dict[str, str],
    parser_type: str,
) -> tuple[dict[str, Any], list[dict[str, Any]], list[str], list[dict[str, str]]]:
    row = empty_structural_row(record, parser_type)
    sections: list[dict[str, Any]] = []
    headings: list[str] = []
    errors: list[dict[str, str]] = []
    if parser_type == "OPAQUE_BINARY":
        row["parse_status"] = "OPAQUE_NOT_STRUCTURALLY_PARSED"
        row["text_availability"] = "BINARY_OR_OPAQUE"
        return row, sections, headings, errors
    try:
        text_stats, sections, headings = scan_text_file(path, record)
        row.update({key: text_stats[key] for key in (
            "line_count", "character_count", "heading_count", "code_fence_count",
            "table_like_line_count", "link_count", "extremely_long_line_count",
        )})
        row["text_availability"] = "TEXT_AVAILABLE"
        row["parse_status"] = "STRUCTURALLY_SCANNED"
        if text_stats["decoding_replacement_count"]:
            errors.append(error_row(
                record["source_record_id"], record["relative_path"], parser_type,
                "WARNING", "TEXT_DECODING_REPLACEMENTS",
                f"best-effort decoding inserted {text_stats['decoding_replacement_count']} replacement characters",
            ))

        inventory: list[str] = []
        approximate_objects: int | str = ""
        if parser_type == "DELIMITED_STREAM":
            delimiter = "\t" if record.get("extension") == ".tsv" else ","
            delimited, warning = inspect_delimited(path, delimiter)
            inventory = delimited["header"]
            row["row_count"] = delimited["row_count"]
            row["column_count"] = delimited["column_count"]
            approximate_objects = delimited["row_count"]
            if warning:
                errors.append(error_row(record["source_record_id"], record["relative_path"], parser_type, "WARNING", "DELIMITED_PARSE_WARNING", warning))
            elif delimited["malformed_row_count"]:
                errors.append(error_row(
                    record["source_record_id"], record["relative_path"], parser_type,
                    "WARNING", "DELIMITED_MALFORMED_ROWS",
                    f"{delimited['malformed_row_count']} rows differed from the header column count",
                ))
        elif parser_type == "JSON_STANDARD_LIBRARY":
            inventory, approximate_objects, status, warning = inspect_json(path)
            row["parse_status"] = status
            if warning:
                errors.append(error_row(record["source_record_id"], record["relative_path"], parser_type, "WARNING", "JSON_PARSE_WARNING", warning))
        elif parser_type == "YAML_STRUCTURAL_STREAM":
            inventory, approximate_objects = inspect_top_level_keys(path, yaml_mode=True)
            row["parse_status"] = "YAML_KEYS_STRUCTURALLY_SCANNED"
        elif parser_type == "PYTHON_AST":
            inventory, status, warning = inspect_python(path)
            row["parse_status"] = status
            if warning:
                errors.append(error_row(record["source_record_id"], record["relative_path"], parser_type, "WARNING", "PYTHON_AST_WARNING", warning))
        elif parser_type == "CODE_CONFIG_STRUCTURAL":
            if record.get("extension") in CONFIG_EXTENSIONS:
                inventory, approximate_objects = inspect_top_level_keys(path)
            else:
                inventory = inspect_generic_symbols(path)
            row["parse_status"] = "CODE_CONFIG_STRUCTURALLY_SCANNED"
        row["top_level_inventory"] = " | ".join(inventory[:MAX_INVENTORY_ITEMS])
        row["top_level_symbol_or_key_count"] = len(inventory)
        row["approximate_object_count"] = approximate_objects
    except (OSError, UnicodeError, csv.Error) as exc:
        row["parse_status"] = "STRUCTURAL_PARSE_ERROR"
        row["text_availability"] = "UNAVAILABLE_PARSE_FAILURE"
        errors.append(error_row(
            record["source_record_id"], record["relative_path"], parser_type,
            "ERROR", "STRUCTURAL_PARSE_FAILED", f"{type(exc).__name__}: {exc}",
        ))
    row["section_page_candidate_count"] = len(sections)
    row["error_reference"] = ";".join(error["error_reference"] for error in errors)
    return row, sections, headings, errors


def sequence_candidate(filename: str) -> int | None:
    stem = Path(filename).stem
    match = re.search(r"(?<!\d)(\d{1,3})(?!\d)", stem)
    return int(match.group(1)) if match else None


def filename_title(filename: str) -> str:
    stem = normalize_text(Path(filename).stem)
    title = re.sub(r"^[\s_\-]*(?:lecture|lec|lesson|week|module)?[\s_\-]*\d{1,3}[\s_\-.:]*", "", stem, flags=re.IGNORECASE)
    return clean_cell(title or stem, 500)


SYLLABUS_RE = re.compile(r"syllabus|course[\W_\-]*(?:outline|description)|منهج|مقرر", re.IGNORECASE)


def build_course_structure(
    records: Sequence[dict[str, str]],
    pdf_by_source: dict[str, dict[str, Any]],
) -> tuple[list[dict[str, Any]], dict[str, list[int]]]:
    course_records: dict[str, list[dict[str, str]]] = defaultdict(list)
    for record in records:
        parts = record["relative_path"].split("/")
        if len(parts) >= 3 and parts[0] == "university-courses":
            course_records[parts[1]].append(record)
    hash_locations: dict[str, list[tuple[str, str]]] = defaultdict(list)
    for course, items in course_records.items():
        for record in items:
            if record.get("sha256"):
                hash_locations[record["sha256"]].append((course, record["relative_path"]))

    rows: list[dict[str, Any]] = []
    gaps_by_course: dict[str, list[int]] = {}
    for course in sorted(course_records, key=sort_key):
        items = sorted(course_records[course], key=lambda row: sort_key(row["relative_path"]))
        sequences = sorted({number for number in (sequence_candidate(row["filename"]) for row in items) if number is not None})
        gaps: list[int] = []
        if len(sequences) >= 2 and sequences[-1] - sequences[0] <= 100:
            gaps = [number for number in range(sequences[0], sequences[-1] + 1) if number not in sequences]
        gaps_by_course[course] = gaps
        syllabus_present = any(SYLLABUS_RE.search(record["filename"]) for record in items)
        ordered = sorted(
            items,
            key=lambda row: (
                sequence_candidate(row["filename"]) is None,
                sequence_candidate(row["filename"]) or 0,
                sort_key(row["relative_path"]),
            ),
        )
        for order, record in enumerate(ordered, start=1):
            pdf = pdf_by_source.get(record["source_record_id"], {})
            locations = hash_locations.get(record.get("sha256", ""), [])
            courses_for_hash = {location[0] for location in locations}
            if len(locations) <= 1:
                duplicate = "UNIQUE_HASH"
            elif len(courses_for_hash) > 1:
                duplicate = "DUPLICATE_ACROSS_COURSES"
            elif locations and course in courses_for_hash:
                duplicate = "DUPLICATE_WITHIN_COURSE"
            else:
                duplicate = "DUPLICATE_OUTSIDE_COURSES"
            flags = ["STRUCTURALLY_MAPPED"]
            if gaps:
                flags.append("SEQUENCE_GAPS_OBSERVED")
            if not syllabus_present:
                flags.append("NO_SYLLABUS_FILENAME")
            if record.get("extension") == ".pdf":
                if pdf.get("ocr_review_state") == "OCR_REQUIRED_FOR_SEMANTIC_REVIEW":
                    flags.append("OCR_REQUIRED")
                elif pdf.get("text_availability") == "PDF_TEXT_AVAILABLE":
                    flags.append("PDF_TEXT_AVAILABLE")
            flags.append("SEMANTIC_REVIEW_NOT_STARTED")
            rows.append({
                "source_record_id": record["source_record_id"],
                "relative_path": record["relative_path"],
                "course_folder": course,
                "file_order_candidate": order,
                "sequence_number_candidate": sequence_candidate(record["filename"]) or "",
                "filename_derived_title": filename_title(record["filename"]),
                "file_type": record.get("extension", "") or "[no-extension]",
                "size_bytes": record.get("size_bytes", 0),
                "pdf_page_count": pdf.get("page_count", ""),
                "text_availability": pdf.get("text_availability", record.get("text_binary_classification", "UNKNOWN")),
                "duplicate_relationship": duplicate,
                "apparent_missing_sequence_numbers": ",".join(str(number) for number in gaps),
                "syllabus_like_filename_present": bool_text(syllabus_present),
                "structural_flags": ";".join(flags),
                "semantic_state": "SEMANTIC_REVIEW_NOT_STARTED",
            })
    rows.sort(key=lambda row: (sort_key(row["course_folder"]), int(row["file_order_candidate"]), sort_key(row["relative_path"])))
    return rows, gaps_by_course


def possibly_incomplete_rows(
    records: Sequence[dict[str, str]],
    pdf_documents: Sequence[dict[str, Any]],
    gaps_by_course: dict[str, list[int]],
) -> list[dict[str, str]]:
    rows: list[dict[str, str]] = []
    pdf_by_source = {row["source_record_id"]: row for row in pdf_documents}
    task_like = re.compile(r"task|playbook|lab|exercise|audit|check", re.IGNORECASE)
    for record in records:
        if record.get("zero_byte") == "TRUE":
            filename = record["filename"]
            if filename in PLACEHOLDER_FILENAMES or filename == "__init__.py":
                continue
            rule = "INC-EMPTY-TASK-LIKE" if record.get("extension") in YAML_EXTENSIONS and task_like.search(filename) else "INC-ZERO-BYTE-CONTENT"
            severity = "HIGH" if rule == "INC-EMPTY-TASK-LIKE" else "MEDIUM"
            rows.append({
                "rule_id": rule,
                "source_record_id": record["source_record_id"],
                "relative_path": record["relative_path"],
                "evidence": f"zero-byte non-placeholder file; filename={filename}",
                "severity": severity,
                "review_state": "REQUIRES_HUMAN_REVIEW",
            })
        pdf = pdf_by_source.get(record["source_record_id"])
        if pdf and pdf.get("parser_state") == "PDF_PARSE_ERROR":
            rows.append({
                "rule_id": "INC-PDF-PARSE-FAILURE",
                "source_record_id": record["source_record_id"],
                "relative_path": record["relative_path"],
                "evidence": "PDF parser could not establish document structure",
                "severity": "HIGH",
                "review_state": "REQUIRES_HUMAN_REVIEW",
            })
        elif pdf and pdf.get("parser_state") == "PDF_ENCRYPTED_UNAVAILABLE":
            rows.append({
                "rule_id": "INC-PDF-ENCRYPTED-UNINSPECTABLE",
                "source_record_id": record["source_record_id"],
                "relative_path": record["relative_path"],
                "evidence": "encrypted PDF could not be opened with an empty password for structural inspection",
                "severity": "MEDIUM",
                "review_state": "REQUIRES_HUMAN_REVIEW",
            })
    for course in sorted(gaps_by_course, key=sort_key):
        gaps = gaps_by_course[course]
        if gaps:
            rows.append({
                "rule_id": "INC-COURSE-SEQUENCE-GAP",
                "source_record_id": "",
                "relative_path": f"university-courses/{course}/",
                "evidence": "filename-derived missing sequence candidates: " + ",".join(str(number) for number in gaps),
                "severity": "MEDIUM",
                "review_state": "REQUIRES_HUMAN_REVIEW",
            })
    rows.sort(key=lambda row: (sort_key(row["rule_id"]), sort_key(row["relative_path"])))
    return rows


def load_domain_config(path: Path) -> dict[str, Any]:
    config = json.loads(path.read_text(encoding="utf-8"))
    codes = [domain["code"] for domain in config.get("domains", [])]
    if len(codes) != 16 or len(set(codes)) != 16:
        raise StructuralIndexError("domain_keywords.json must define 16 unique domain codes")
    return config


def search_form(value: str) -> str:
    normalized = normalize_text(value).casefold()
    return re.sub(r"[\s_\-./\\]+", " ", normalized)


def classify_domains(
    records: Sequence[dict[str, str]],
    sections_by_source: dict[str, list[dict[str, Any]]],
    headings_by_source: dict[str, list[str]],
    pdf_by_source: dict[str, dict[str, Any]],
    config: dict[str, Any],
) -> list[dict[str, Any]]:
    weights = config["location_weights"]
    bands = config["confidence_bands"]
    rows: list[dict[str, Any]] = []
    for record in records:
        source_id = record["source_record_id"]
        relative = record["relative_path"]
        filename = record["filename"]
        path_text = relative.rsplit("/", 1)[0] if "/" in relative else ""
        heading_values = headings_by_source.get(source_id, [])[:500]
        heading_text = " | ".join(heading_values)
        pdf = pdf_by_source.get(source_id, {})
        metadata_text = " | ".join(clean_cell(pdf.get(field, ""), 500) for field in (
            "metadata_title", "metadata_subject", "first_level_outline_titles",
        ))
        locations = {
            "path": search_form(path_text),
            "filename": search_form(filename),
            "heading": search_form(heading_text),
            "metadata": search_form(metadata_text),
        }
        matched_any = False
        for domain in config["domains"]:
            matched_terms: set[str] = set()
            matched_locations: list[str] = []
            score = 0
            matching_section = ""
            for term in domain["keywords"]:
                normalized_term = search_form(term)
                term_matched_heading = False
                for location, haystack in locations.items():
                    if normalized_term and normalized_term in haystack:
                        matched_terms.add(term)
                        matched_locations.append(f"{location}:{term}")
                        score += int(weights[location])
                        term_matched_heading = term_matched_heading or location == "heading"
                if term_matched_heading and not matching_section:
                    for section in sections_by_source.get(source_id, []):
                        if normalized_term in search_form(section.get("heading_path_or_title", "")):
                            matching_section = section["segment_candidate_id"]
                            break
            if not matched_terms:
                continue
            matched_any = True
            if score >= int(bands["HIGH_MECHANICAL_SIGNAL"]):
                confidence = "HIGH_MECHANICAL_SIGNAL"
            elif score >= int(bands["MEDIUM_MECHANICAL_SIGNAL"]):
                confidence = "MEDIUM_MECHANICAL_SIGNAL"
            else:
                confidence = "LOW_MECHANICAL_SIGNAL"
            rows.append({
                "source_record_id": source_id,
                "relative_path": relative,
                "segment_candidate_id": matching_section,
                "candidate_domain_code": domain["code"],
                "candidate_domain_label": domain["label"],
                "matched_terms": ";".join(sorted(matched_terms, key=sort_key)),
                "match_locations": ";".join(sorted(set(matched_locations), key=sort_key)),
                "deterministic_score": score,
                "confidence_band": confidence,
                "state": "PROVISIONAL_NOT_SEMANTICALLY_VALIDATED",
            })
        if not matched_any:
            rows.append({
                "source_record_id": source_id,
                "relative_path": relative,
                "segment_candidate_id": "",
                "candidate_domain_code": "UNCLASSIFIED",
                "candidate_domain_label": "Insufficient mechanical signal",
                "matched_terms": "",
                "match_locations": "",
                "deterministic_score": 0,
                "confidence_band": "LOW_MECHANICAL_SIGNAL",
                "state": "PROVISIONAL_NOT_SEMANTICALLY_VALIDATED",
            })
    rows.sort(key=lambda row: (
        sort_key(row["relative_path"]),
        sort_key(row["candidate_domain_code"]),
        sort_key(row["segment_candidate_id"]),
    ))
    return rows


def queue_priority(
    record: dict[str, str],
    structural: dict[str, Any],
    domains: Sequence[dict[str, Any]],
    pdf: dict[str, Any] | None,
) -> tuple[str, int, str, str]:
    relative = record["relative_path"]
    filename_signal = search_form(record["filename"])
    reasons: list[str] = []
    if relative.startswith("product-charter/"):
        band, base = "P0_PRODUCT_AUTHORITY", 500
        reasons.append("product-charter path signal")
    elif relative.startswith("ad-identity-pilot/") or any(term in filename_signal for term in ("canonical", "baseline", "pilot")):
        band, base = "P1_PILOT_AND_CANONICAL", 400
        reasons.append("pilot/canonical/baseline mechanical signal")
    elif relative.startswith("university-courses/"):
        band, base = "P2_UNIVERSITY_COURSE", 300
        reasons.append("university course candidate")
    elif record.get("large_file") == "TRUE" or (pdf and pdf.get("ocr_review_state") == "OCR_REQUIRED_FOR_SEMANTIC_REVIEW") or structural.get("parse_status", "").endswith(("ERROR", "WARNING")) or any(row["candidate_domain_code"] == "UNCLASSIFIED" for row in domains):
        band, base = "P3_LARGE_OR_AMBIGUOUS", 200
        reasons.append("large, structurally ambiguous, OCR-required, or unclassified")
    else:
        band, base = "P4_REFERENCE", 100
        reasons.append("remaining structural reference")

    domain_score = max((int(row["deterministic_score"]) for row in domains), default=0)
    score = base + min(domain_score, 30)
    if structural.get("error_reference"):
        score += 15
        reasons.append("parse warning/error requires human handling")
    if record.get("large_file") == "TRUE":
        score += 10
        reasons.append("bounded pre-chunking available")
    if pdf and pdf.get("ocr_review_state") == "OCR_REQUIRED_FOR_SEMANTIC_REVIEW":
        score += 20
        reasons.append("PDF has little or no extractable text")

    if pdf and pdf.get("ocr_review_state") == "OCR_REQUIRED_FOR_SEMANTIC_REVIEW":
        depth = "TRANSCRIPT_OR_OCR_REQUIRED"
    elif pdf:
        depth = "PDF_SELECTED_PAGES"
    elif record.get("large_file") == "TRUE" or int(structural.get("section_page_candidate_count") or 0) > 10:
        depth = "SELECTED_SECTIONS"
    elif structural.get("parser_type") == "OPAQUE_BINARY":
        depth = "STRUCTURE_ONLY"
    else:
        depth = "FULL_DOCUMENT"
    return band, score, "; ".join(reasons), depth


def build_review_queue(
    records: Sequence[dict[str, str]],
    structural_by_source: dict[str, dict[str, Any]],
    domain_rows: Sequence[dict[str, Any]],
    sections_by_source: dict[str, list[dict[str, Any]]],
    pdf_by_source: dict[str, dict[str, Any]],
    limit: int,
) -> tuple[list[dict[str, Any]], int]:
    domains_by_source: dict[str, list[dict[str, Any]]] = defaultdict(list)
    for row in domain_rows:
        domains_by_source[row["source_record_id"]].append(row)
    band_order = {
        "P0_PRODUCT_AUTHORITY": 0,
        "P1_PILOT_AND_CANONICAL": 1,
        "P2_UNIVERSITY_COURSE": 2,
        "P3_LARGE_OR_AMBIGUOUS": 3,
        "P4_REFERENCE": 4,
    }
    candidates: list[dict[str, Any]] = []
    for record in records:
        source_id = record["source_record_id"]
        structural = structural_by_source[source_id]
        domains = domains_by_source.get(source_id, [])
        pdf = pdf_by_source.get(source_id)
        band, score, reason, depth = queue_priority(record, structural, domains, pdf)
        segment_id = ""
        source_sections = sections_by_source.get(source_id, [])
        if depth == "TRANSCRIPT_OR_OCR_REQUIRED":
            segment_id = next((section["segment_candidate_id"] for section in source_sections if int(section.get("character_estimate") or 0) == 0), source_sections[0]["segment_candidate_id"] if source_sections else "")
        elif depth in {"PDF_SELECTED_PAGES", "SELECTED_SECTIONS"} and source_sections:
            segment_id = source_sections[0]["segment_candidate_id"]
        domain_codes = sorted({row["candidate_domain_code"] for row in domains}, key=sort_key)
        candidates.append({
            "queue_item_id": "queue-" + stable_hash(source_id, segment_id, band)[:20],
            "source_record_id": source_id,
            "relative_path": record["relative_path"],
            "segment_candidate_id": segment_id,
            "candidate_domains": ";".join(domain_codes),
            "reason_for_review": reason,
            "mechanical_priority_score": score,
            "priority_band": band,
            "required_inspection_depth": depth,
            "state": "AWAITING_SEMANTIC_REVIEW",
            "_band_order": band_order[band],
        })
    candidates.sort(key=lambda row: (
        row["_band_order"], -int(row["mechanical_priority_score"]), sort_key(row["relative_path"])
    ))
    selected = candidates[:limit]
    for row in selected:
        row.pop("_band_order", None)
    return selected, max(len(candidates) - len(selected), 0)


def large_source_rows(
    large_records: Sequence[dict[str, str]],
    structural_by_source: dict[str, dict[str, Any]],
) -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []
    for record in large_records:
        structural = structural_by_source[record["source_record_id"]]
        parser_type = structural["parser_type"]
        candidate_count = int(structural.get("section_page_candidate_count") or 0)
        line_count = int(structural.get("line_count") or 0)
        table_lines = int(structural.get("table_like_line_count") or 0)
        table_heavy = line_count > 0 and (table_lines >= 1000 or table_lines / line_count >= 0.4)
        if parser_type == "PDF_PYPDF":
            strategy = "PAGE_CANDIDATES"
            method = "pypdf per-page extraction; discard text after counts and hashes"
        elif parser_type == "OPAQUE_BINARY":
            strategy = "OPAQUE_FILE_NO_DECOMPRESSION"
            method = "metadata and existing SHA-256 only; no archive expansion"
        elif table_heavy:
            strategy = "TABLE_AWARE_BOUNDED_LINE_BYTE_PARTITIONS"
            method = "64 KiB bounded line reads; 384-512 KiB deterministic partitions"
        else:
            strategy = "HEADING_AND_BOUNDED_CHUNK_PARTITIONS"
            method = "stream headings; 1,200-1,500 line or 384-512 KiB partitions"
        rows.append({
            "source_record_id": record["source_record_id"],
            "relative_path": record["relative_path"],
            "size_bytes": record["size_bytes"],
            "parser_type": parser_type,
            "structural_partition_strategy": strategy,
            "candidate_section_count": candidate_count,
            "extremely_long_line_count": structural.get("extremely_long_line_count", ""),
            "table_heavy_generated_indicator": bool_text(table_heavy),
            "recommended_bounded_read_method": method,
            "manual_semantic_review_feasible_without_prechunking": bool_text(candidate_count <= 1 and parser_type not in {"OPAQUE_BINARY"} and not table_heavy),
        })
    rows.sort(key=lambda row: sort_key(row["relative_path"]))
    return rows


def markdown_cell(value: Any) -> str:
    return str(value).replace("|", "\\|").replace("\r", " ").replace("\n", " ")


def structural_report(
    workspace_root: Path,
    records: Sequence[dict[str, str]],
    structural_rows: Sequence[dict[str, Any]],
    sections: Sequence[dict[str, Any]],
    pdf_documents: Sequence[dict[str, Any]],
    errors: Sequence[dict[str, str]],
) -> str:
    parser_counts = Counter(row["parser_type"] for row in structural_rows)
    status_counts = Counter(row["parse_status"] for row in structural_rows)
    lines = [
        "# Structural Indexing Report", "",
        f"Workspace: `{workspace_root}`", "",
        "> Mechanical observation only. No source has been semantically validated, approved for reuse, ranked for curriculum value, or declared canonical.", "",
        "## Totals", "",
        f"- Source files indexed: {len(records)}",
        f"- Section/page candidates: {len(sections)}",
        f"- PDF documents: {len(pdf_documents)}",
        f"- Structural parse errors and warnings: {len(errors)}",
        f"- Files with one or more error references: {sum(bool(row['error_reference']) for row in structural_rows)}",
        "", "## Parser/type counts", "", "| Parser/type | Files |", "|---|---:|",
    ]
    for name in sorted(parser_counts, key=sort_key):
        lines.append(f"| {markdown_cell(name)} | {parser_counts[name]} |")
    lines.extend(["", "## Parse states", "", "| State | Files |", "|---|---:|"])
    for name in sorted(status_counts, key=sort_key):
        lines.append(f"| {markdown_cell(name)} | {status_counts[name]} |")
    lines.extend([
        "", "## Boundary", "",
        "Headings, key names, symbols, metadata, page counts, line/byte boundaries, and hashes are structural facts or best-effort mechanical signals. Source claims were not evaluated. Archives remained opaque and were not decompressed.", "",
    ])
    return "\n".join(lines)


def university_report(course_rows: Sequence[dict[str, Any]], gaps: dict[str, list[int]]) -> str:
    grouped: dict[str, list[dict[str, Any]]] = defaultdict(list)
    for row in course_rows:
        grouped[row["course_folder"]].append(row)
    lines = [
        "# University Course Readiness Report", "",
        "> Structural mapping only. Educational quality, final completeness, ordering, and curriculum suitability have not been decided.", "",
        "| Candidate course | Files | PDF pages | Filename sequence gaps | Syllabus-like filename |", "|---|---:|---:|---|---|",
    ]
    for course in sorted(grouped, key=sort_key):
        rows = grouped[course]
        pages = sum(int(row["pdf_page_count"] or 0) for row in rows)
        gap_text = ",".join(str(number) for number in gaps.get(course, [])) or "none observed"
        syllabus = "TRUE" if any(row["syllabus_like_filename_present"] == "TRUE" for row in rows) else "FALSE"
        lines.append(f"| {markdown_cell(course)} | {len(rows)} | {pages} | {gap_text} | {syllabus} |")
    lines.extend([
        "", f"Candidate courses: {len(grouped)}. Files mapped: {len(course_rows)}. Courses with observed filename-sequence gaps: {sum(bool(value) for value in gaps.values())}.", "",
        "All rows remain `SEMANTIC_REVIEW_NOT_STARTED`.", "",
    ])
    return "\n".join(lines)


def large_plan(rows: Sequence[dict[str, Any]]) -> str:
    strategies = Counter(row["structural_partition_strategy"] for row in rows)
    lines = [
        "# Large Source Handling Plan", "",
        "> File size is used only to choose a safe bounded-read method. It is not an educational-importance score.", "",
        f"Large files covered from Source Readiness 001: {len(rows)}", "",
        "| Strategy | Files |", "|---|---:|",
    ]
    for strategy in sorted(strategies, key=sort_key):
        lines.append(f"| {markdown_cell(strategy)} | {strategies[strategy]} |")
    lines.extend([
        "", "## Safety method", "",
        "Text is scanned with 64 KiB bounded reads. Candidate boundaries target 1,200-1,500 lines or 384-512 KiB. Extremely long lines are consumed in bounded fragments. PDF text is handled one page at a time and discarded after counts and hashes. Archives and opaque binaries are not expanded.", "",
    ])
    return "\n".join(lines)


def triage_report(
    domain_rows: Sequence[dict[str, Any]],
    queue: Sequence[dict[str, Any]],
    deferred: int,
) -> str:
    domain_counts = Counter(row["candidate_domain_code"] for row in domain_rows)
    source_unclassified = len({row["source_record_id"] for row in domain_rows if row["candidate_domain_code"] == "UNCLASSIFIED"})
    priority_counts = Counter(row["priority_band"] for row in queue)
    lines = [
        "# Semantic Triage Report", "",
        "> This is a bounded review queue built from transparent keyword and filesystem signals. It is not semantic validation, source-quality scoring, or a curriculum decision.", "",
        "## Queue", "",
        f"- Selected queue items: {len(queue)}",
        f"- Deferred source candidates: {deferred}",
        f"- Unclassified sources: {source_unclassified}",
        "", "| Priority band | Selected items |", "|---|---:|",
    ]
    for band in sorted(priority_counts, key=sort_key):
        lines.append(f"| {band} | {priority_counts[band]} |")
    lines.extend(["", "## Provisional domain candidate rows", "", "| Domain code | Rows |", "|---|---:|"])
    for code in sorted(domain_counts, key=sort_key):
        lines.append(f"| {code} | {domain_counts[code]} |")
    lines.extend([
        "", "Multiple domain candidates are allowed. `UNCLASSIFIED` means the configured terms did not provide sufficient mechanical signal. Human review is required before any final interpretation.", "",
    ])
    return "\n".join(lines)


def generate(
    source_root: Path,
    input_manifest_root: Path,
    output_manifest_root: Path,
    report_root: Path,
    queue_limit: int = DEFAULT_QUEUE_LIMIT,
    domain_config_path: Path | None = None,
    validate_inputs: bool = True,
) -> dict[str, Any]:
    if queue_limit <= 0:
        raise StructuralIndexError("Queue limit must be greater than zero")
    if pypdf is None:
        raise StructuralIndexError("pypdf is unavailable; install requirements.txt in a local Python 3.13 environment")
    if sys.version_info[:2] != (3, 13):
        raise StructuralIndexError(f"Python 3.13 is required; running {platform.python_version()}")

    source_root, input_manifest_root, output_manifest_root, report_root = validate_roots(
        source_root, input_manifest_root, output_manifest_root, report_root
    )
    workspace_root = source_root.parent.parent
    domain_config_path = (domain_config_path or Path(__file__).with_name("domain_keywords.json")).resolve(strict=True)
    domain_config = load_domain_config(domain_config_path)
    started = utc_now_text()

    source_fingerprint_before, source_count_before = source_metadata_fingerprint(source_root)
    input_validation = validate_approved_inputs(workspace_root, input_manifest_root) if validate_inputs else {
        "validated": False,
        "required_input_count": 0,
        "approved_file_count": 0,
        "approved_fingerprint": "NOT_VALIDATED_IN_TEST_MODE",
        "readiness_counts": {},
    }
    approved_fingerprint_before = input_validation["approved_fingerprint"]

    census_path = input_manifest_root / "SOURCE_FILE_CENSUS.tsv"
    if not census_path.is_file():
        raise StructuralIndexError(f"Missing input census: {census_path}")
    records = read_tsv(census_path)
    records.sort(key=lambda row: sort_key(row["relative_path"]))
    large_path = input_manifest_root / "LARGE_FILES.tsv"
    large_ids = {row["source_record_id"] for row in read_tsv(large_path)} if large_path.is_file() else set()

    structural_rows: list[dict[str, Any]] = []
    all_sections: list[dict[str, Any]] = []
    pdf_documents: list[dict[str, Any]] = []
    pdf_pages: list[dict[str, Any]] = []
    parse_errors: list[dict[str, str]] = []
    sections_by_source: dict[str, list[dict[str, Any]]] = defaultdict(list)
    headings_by_source: dict[str, list[str]] = defaultdict(list)
    pdf_by_source: dict[str, dict[str, Any]] = {}

    for record in records:
        source_id = record["source_record_id"]
        relative = record["relative_path"]
        path = source_root / Path(relative)
        parser_type = parser_for(record)
        if path.is_symlink():
            row = empty_structural_row(record, parser_type)
            row["parse_status"] = "SYMLINK_NOT_FOLLOWED"
            warning = error_row(source_id, relative, parser_type, "WARNING", "SYMLINK_NOT_FOLLOWED", "symbolic-link source entry was not followed")
            row["error_reference"] = warning["error_reference"]
            parse_errors.append(warning)
            structural_rows.append(row)
            continue
        if not path.is_file():
            row = empty_structural_row(record, parser_type)
            row["parse_status"] = "SOURCE_FILE_MISSING"
            error = error_row(source_id, relative, parser_type, "ERROR", "SOURCE_FILE_MISSING", "census path is not a regular file")
            row["error_reference"] = error["error_reference"]
            parse_errors.append(error)
            structural_rows.append(row)
            continue
        if parser_type == "PDF_PYPDF":
            warning_handler = WarningCaptureHandler()
            pypdf_logger = logging.getLogger("pypdf")
            previous_propagate = pypdf_logger.propagate
            pypdf_logger.addHandler(warning_handler)
            pypdf_logger.propagate = False
            try:
                document, pages, sections, errors, outlines = inspect_pdf(path, record)
            finally:
                pypdf_logger.removeHandler(warning_handler)
                pypdf_logger.propagate = previous_propagate
            for warning_number, message in enumerate(warning_handler.messages, start=1):
                warning = error_row(
                    source_id,
                    relative,
                    parser_type,
                    "WARNING",
                    "PDF_PARSER_LOG_WARNING",
                    f"warning {warning_number}: {message}",
                )
                errors.append(warning)
            if warning_handler.messages:
                existing_references = [value for value in document["error_reference"].split(";") if value]
                existing_references.extend(
                    row["error_reference"] for row in errors
                    if row["error_code"] == "PDF_PARSER_LOG_WARNING"
                )
                document["error_reference"] = ";".join(existing_references)
            pdf_documents.append(document)
            pdf_pages.extend(pages)
            all_sections.extend(sections)
            sections_by_source[source_id].extend(sections)
            headings_by_source[source_id].extend(outlines)
            parse_errors.extend(errors)
            pdf_by_source[source_id] = document
            row = empty_structural_row(record, parser_type)
            row["parse_status"] = document["parser_state"]
            row["page_count"] = document["page_count"]
            row["character_count"] = document["total_extracted_characters"]
            row["text_availability"] = document["text_availability"]
            row["section_page_candidate_count"] = len(sections)
            row["top_level_inventory"] = document["first_level_outline_titles"]
            row["top_level_symbol_or_key_count"] = document["outline_count"]
            row["error_reference"] = document["error_reference"]
            structural_rows.append(row)
        else:
            row, sections, headings, errors = inspect_non_pdf(path, record, parser_type)
            structural_rows.append(row)
            all_sections.extend(sections)
            sections_by_source[source_id].extend(sections)
            headings_by_source[source_id].extend(headings)
            parse_errors.extend(errors)

    structural_rows.sort(key=lambda row: sort_key(row["relative_path"]))
    all_sections.sort(key=lambda row: (sort_key(row["relative_path"]), sort_key(row["segment_candidate_id"])))
    pdf_documents.sort(key=lambda row: sort_key(row["relative_path"]))
    pdf_pages.sort(key=lambda row: (sort_key(row["relative_path"]), int(row["page_number"])))
    parse_errors.sort(key=lambda row: (sort_key(row["relative_path"]), sort_key(row["error_reference"])))
    structural_by_source = {row["source_record_id"]: row for row in structural_rows}

    course_rows, gaps_by_course = build_course_structure(records, pdf_by_source)
    incomplete = possibly_incomplete_rows(records, pdf_documents, gaps_by_course)
    domain_rows = classify_domains(records, sections_by_source, headings_by_source, pdf_by_source, domain_config)
    queue, deferred_count = build_review_queue(
        records, structural_by_source, domain_rows, sections_by_source,
        pdf_by_source, queue_limit,
    )
    selected_segment_ids = {row["segment_candidate_id"] for row in queue if row["segment_candidate_id"]}
    for section in all_sections:
        if section["segment_candidate_id"] in selected_segment_ids:
            section["selection_state"] = "CANDIDATE_FOR_REVIEW"
    large_records = [record for record in records if record["source_record_id"] in large_ids]
    large_rows = large_source_rows(large_records, structural_by_source)

    reports = {
        report_root / "STRUCTURAL_INDEXING_REPORT.md": structural_report(
            workspace_root, records, structural_rows, all_sections, pdf_documents, parse_errors
        ),
        report_root / "UNIVERSITY_COURSE_READINESS_REPORT.md": university_report(course_rows, gaps_by_course),
        report_root / "LARGE_SOURCE_HANDLING_PLAN.md": large_plan(large_rows),
        report_root / "SEMANTIC_TRIAGE_REPORT.md": triage_report(domain_rows, queue, deferred_count),
    }
    manifests = {
        output_manifest_root / "SOURCE_STRUCTURAL_INDEX.tsv": tsv_text(STRUCTURAL_COLUMNS, structural_rows),
        output_manifest_root / "SOURCE_SECTION_CANDIDATES.tsv": tsv_text(SECTION_COLUMNS, all_sections),
        output_manifest_root / "LARGE_SOURCE_SECTION_INDEX.tsv": tsv_text(LARGE_COLUMNS, large_rows),
        output_manifest_root / "PDF_DOCUMENT_INVENTORY.tsv": tsv_text(PDF_DOCUMENT_COLUMNS, pdf_documents),
        output_manifest_root / "PDF_PAGE_TEXT_STATUS.tsv": tsv_text(PDF_PAGE_COLUMNS, pdf_pages),
        output_manifest_root / "UNIVERSITY_COURSE_STRUCTURE.tsv": tsv_text(COURSE_COLUMNS, course_rows),
        output_manifest_root / "STRUCTURAL_PARSE_ERRORS.tsv": tsv_text(ERROR_COLUMNS, parse_errors),
        output_manifest_root / "POSSIBLY_INCOMPLETE_FILES.tsv": tsv_text(INCOMPLETE_COLUMNS, incomplete),
        output_manifest_root / "DOMAIN_CLASSIFICATION_CANDIDATES.tsv": tsv_text(DOMAIN_COLUMNS, domain_rows),
        output_manifest_root / "SEMANTIC_REVIEW_QUEUE.tsv": tsv_text(QUEUE_COLUMNS, queue),
    }
    deterministic_outputs = {**manifests, **reports}
    for path in sorted(deterministic_outputs, key=lambda item: sort_key(item.as_posix())):
        atomic_write_text(path, deterministic_outputs[path])

    output_hashes: dict[str, str] = {}
    for path in sorted(deterministic_outputs, key=lambda item: sort_key(item.as_posix())):
        if path.parent == output_manifest_root:
            key = f"manifests/structural/{path.name}"
        else:
            key = f"derived/structural/{path.name}"
        output_hashes[key] = sha256_file(path)

    source_fingerprint_after, source_count_after = source_metadata_fingerprint(source_root)
    if source_fingerprint_before != source_fingerprint_after or source_count_before != source_count_after:
        raise StructuralIndexError("Source metadata fingerprint changed during read-only indexing")
    if validate_inputs:
        approved_files_after = approved_001_files(workspace_root, input_manifest_root)
        approved_fingerprint_after, approved_count_after = file_set_fingerprint(workspace_root, approved_files_after)
        if approved_fingerprint_before != approved_fingerprint_after or approved_count_after != input_validation["approved_file_count"]:
            raise StructuralIndexError("Approved Source Readiness 001 files changed during indexing")
    else:
        approved_fingerprint_after = approved_fingerprint_before
        approved_count_after = input_validation["approved_file_count"]

    peak_memory = process_peak_memory()
    domain_sources_unclassified = len({row["source_record_id"] for row in domain_rows if row["candidate_domain_code"] == "UNCLASSIFIED"})
    metadata = {
        "tool_version": TOOL_VERSION,
        "utc_start": started,
        "utc_end": utc_now_text(),
        "workspace_root": str(workspace_root),
        "source_root": str(source_root),
        "input_manifest_root": str(input_manifest_root),
        "output_manifest_root": str(output_manifest_root),
        "report_root": str(report_root),
        "python_version": platform.python_version(),
        "platform": platform.platform(),
        "dependency": {"name": "pypdf", "version": pypdf.__version__},
        "queue_limit": queue_limit,
        "input_validation": input_validation,
        "source_safety": {
            "file_count_before": source_count_before,
            "file_count_after": source_count_after,
            "metadata_fingerprint_before": source_fingerprint_before,
            "metadata_fingerprint_after": source_fingerprint_after,
            "unchanged": True,
        },
        "approved_001_safety": {
            "file_count_before": input_validation["approved_file_count"],
            "file_count_after": approved_count_after,
            "fingerprint_before": approved_fingerprint_before,
            "fingerprint_after": approved_fingerprint_after,
            "unchanged": approved_fingerprint_before == approved_fingerprint_after,
        },
        "counts": {
            "source_files": len(records),
            "structural_index_rows": len(structural_rows),
            "section_page_candidates": len(all_sections),
            "large_source_rows": len(large_rows),
            "pdf_documents": len(pdf_documents),
            "pdf_pages": len(pdf_pages),
            "pdf_ocr_required": sum(row["ocr_review_state"] == "OCR_REQUIRED_FOR_SEMANTIC_REVIEW" for row in pdf_documents),
            "university_course_files": len(course_rows),
            "university_courses": len({row["course_folder"] for row in course_rows}),
            "courses_with_sequence_gaps": sum(bool(gaps) for gaps in gaps_by_course.values()),
            "parse_errors_and_warnings": len(parse_errors),
            "possibly_incomplete_candidates": len(incomplete),
            "domain_candidate_rows": len(domain_rows),
            "unclassified_sources": domain_sources_unclassified,
            "semantic_review_queue_items": len(queue),
            "semantic_review_deferred": deferred_count,
        },
        "peak_memory": peak_memory,
        "output_hashes_sha256": output_hashes,
        "output_hash_scope_note": "Hashes cover 10 structural TSV files and 4 deterministic Markdown reports. STRUCTURAL_RUN_METADATA.json is excluded to avoid timestamps and a self-referential hash.",
        "semantic_boundary": "PROVISIONAL_NOT_SEMANTICALLY_VALIDATED",
    }
    atomic_write_text(
        report_root / "STRUCTURAL_RUN_METADATA.json",
        json.dumps(metadata, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
    )
    return metadata


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Build a deterministic structural source index and bounded semantic-review queue."
    )
    parser.add_argument("--source-root", type=Path, required=True)
    parser.add_argument("--input-manifest-root", type=Path, required=True)
    parser.add_argument("--output-manifest-root", type=Path, required=True)
    parser.add_argument("--report-root", type=Path, required=True)
    parser.add_argument("--queue-limit", type=int, default=DEFAULT_QUEUE_LIMIT)
    parser.add_argument("--domain-config", type=Path, default=Path(__file__).with_name("domain_keywords.json"))
    parser.add_argument("--version", action="version", version=TOOL_VERSION)
    return parser


def main(argv: Sequence[str] | None = None) -> int:
    args = build_parser().parse_args(argv)
    try:
        metadata = generate(
            args.source_root,
            args.input_manifest_root,
            args.output_manifest_root,
            args.report_root,
            args.queue_limit,
            args.domain_config,
        )
    except (StructuralIndexError, OSError, json.JSONDecodeError) as exc:
        print(f"source-structural-indexing: {exc}", file=sys.stderr)
        return 2
    counts = metadata["counts"]
    print(
        "Structural indexing complete: "
        f"files={counts['source_files']} candidates={counts['section_page_candidates']} "
        f"pdfs={counts['pdf_documents']} queue={counts['semantic_review_queue_items']} "
        f"errors_and_warnings={counts['parse_errors_and_warnings']}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

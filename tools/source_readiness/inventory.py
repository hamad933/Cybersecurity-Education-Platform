#!/usr/bin/env python3
"""Deterministic, read-only inventory of source-vault/originals.

This module performs only mechanical filesystem inspection. Archives are hashed
as opaque files and are never decompressed.
"""

from __future__ import annotations

import argparse
import csv
import hashlib
import io
import json
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


TOOL_VERSION = "1.0.0"
DEFAULT_LARGE_FILE_THRESHOLD = 10 * 1024 * 1024
READ_CHUNK_SIZE = 1024 * 1024
CLASSIFICATION_SAMPLE_SIZE = 16 * 1024

EXPECTED_SOURCE_GROUPS = (
    "ad-identity-pilot",
    "chatgpt-project",
    "historical-platform",
    "product-charter",
    "university-courses",
)

TEXT_EXTENSIONS = {
    ".bat", ".cfg", ".conf", ".css", ".csv", ".env", ".go", ".h",
    ".hpp", ".htm", ".html", ".ini", ".java", ".js", ".json",
    ".jsx", ".log", ".lua", ".md", ".php", ".pl", ".properties",
    ".ps1", ".py", ".r", ".rb", ".rst", ".scss", ".sh", ".sql",
    ".svg", ".tex", ".toml", ".ts", ".tsx", ".tsv", ".txt", ".vue",
    ".xml", ".yaml", ".yml",
}

BINARY_EXTENSIONS = {
    ".7z", ".a", ".apk", ".avi", ".bin", ".bmp", ".bz2", ".class",
    ".db", ".dll", ".dmg", ".doc", ".docx", ".eot", ".exe", ".flac",
    ".gif", ".gz", ".ico", ".iso", ".jar", ".jpeg", ".jpg", ".m4a",
    ".mkv", ".mov", ".mp3", ".mp4", ".o", ".odt", ".ogg", ".otf",
    ".pdf", ".png", ".ppt", ".pptx", ".pyc", ".rar", ".so", ".sqlite",
    ".tar", ".tgz", ".tif", ".tiff", ".ttf", ".wav", ".webm", ".webp",
    ".woff", ".woff2", ".xls", ".xlsx", ".xz", ".zip",
}

CATEGORY_EXTENSIONS = {
    "archive": {".7z", ".bz2", ".gz", ".rar", ".tar", ".tgz", ".xz", ".zip"},
    "audio": {".flac", ".m4a", ".mp3", ".ogg", ".wav"},
    "configuration": {".cfg", ".conf", ".env", ".ini", ".properties", ".toml", ".yaml", ".yml"},
    "data": {".csv", ".db", ".json", ".sqlite", ".tsv", ".xml"},
    "disk_image": {".dmg", ".iso"},
    "document": {".doc", ".docx", ".md", ".odt", ".pdf", ".ppt", ".pptx", ".rst", ".tex", ".txt", ".xls", ".xlsx"},
    "executable": {".apk", ".bin", ".class", ".dll", ".exe", ".jar", ".pyc", ".so"},
    "font": {".eot", ".otf", ".ttf", ".woff", ".woff2"},
    "image": {".bmp", ".gif", ".ico", ".jpeg", ".jpg", ".png", ".svg", ".tif", ".tiff", ".webp"},
    "source_code": {".bat", ".css", ".go", ".h", ".hpp", ".htm", ".html", ".java", ".js", ".jsx", ".lua", ".php", ".pl", ".ps1", ".py", ".r", ".rb", ".scss", ".sh", ".sql", ".ts", ".tsx", ".vue"},
    "video": {".avi", ".mkv", ".mov", ".mp4", ".webm"},
}

CENSUS_COLUMNS = (
    "source_record_id",
    "relative_path",
    "parent_directory",
    "filename",
    "extension",
    "size_bytes",
    "sha256",
    "last_modified_utc",
    "file_category",
    "top_level_source_group",
    "zero_byte",
    "large_file",
    "text_binary_classification",
    "line_count",
    "read_status",
    "error_message",
)

MANIFEST_COLUMNS = (
    "source_record_id",
    "relative_path",
    "top_level_source_group",
    "file_category",
    "read_status",
    "inventory_state",
    "semantic_inspection_state",
    "selection_state",
)

DIRECTORY_COLUMNS = (
    "directory",
    "direct_file_count",
    "recursive_file_count",
    "recursive_bytes",
    "extension_distribution",
    "error_count",
)

COURSE_COLUMNS = (
    "course_folder",
    "recursive_file_count",
    "recursive_bytes",
    "extension_counts",
    "apparent_lecture_file_count",
    "syllabus_like_filename_present",
    "completeness",
    "semantic_quality",
)


class InventoryError(RuntimeError):
    """Raised for a configuration or safety failure."""


def utc_now() -> datetime:
    return datetime.now(timezone.utc)


def utc_text(value: datetime) -> str:
    return value.astimezone(timezone.utc).isoformat(timespec="microseconds").replace("+00:00", "Z")


def normalize_component(value: str) -> str:
    return unicodedata.normalize("NFC", value)


def normalize_relative_path(value: str | Path) -> str:
    raw = str(value).replace("\\", "/")
    parts = [normalize_component(part) for part in raw.split("/") if part not in ("", ".")]
    return "/".join(parts) if parts else "."


def source_record_id(relative_path: str | Path) -> str:
    normalized = normalize_relative_path(relative_path)
    digest = hashlib.sha256(normalized.encode("utf-8")).hexdigest()
    return f"src-{digest[:24]}"


def stable_sort_key(value: str) -> bytes:
    return normalize_component(value).encode("utf-8")


def bool_text(value: bool) -> str:
    return "TRUE" if value else "FALSE"


def clean_error(exc: BaseException) -> str:
    message = f"{type(exc).__name__}: {exc}"
    return message.replace("\t", " ").replace("\r", " ").replace("\n", " ")


def extension_for(filename: str) -> str:
    return normalize_component(Path(filename).suffix.lower())


def category_for(extension: str) -> str:
    for category, extensions in CATEGORY_EXTENSIONS.items():
        if extension in extensions:
            return category
    return "other"


def top_level_group(relative_path: str) -> str:
    return relative_path.split("/", 1)[0] if relative_path != "." else "."


def timestamp_from_ns(timestamp_ns: int) -> str:
    seconds, nanoseconds = divmod(timestamp_ns, 1_000_000_000)
    value = datetime.fromtimestamp(seconds, timezone.utc).replace(microsecond=nanoseconds // 1000)
    return utc_text(value)


def classify_sample(sample: bytes, extension: str) -> tuple[str, str | None]:
    """Return (classification, line-count encoding) without full-file loading."""
    if extension in BINARY_EXTENSIONS:
        return "BINARY", None
    if not sample:
        return "TEXT", "utf-8"
    if sample.startswith((b"\x00\x00\xfe\xff", b"\xff\xfe\x00\x00")):
        return "TEXT", "utf-32"
    if sample.startswith((b"\xfe\xff", b"\xff\xfe")):
        return "TEXT", "utf-16"
    if b"\x00" in sample:
        return "BINARY", None
    try:
        decoded = sample.decode("utf-8")
    except UnicodeDecodeError:
        decoded = ""
    if decoded:
        controls = sum(1 for char in decoded if ord(char) < 32 and char not in "\t\r\n\f\b")
        if controls / max(len(decoded), 1) <= 0.01:
            return "TEXT", "utf-8"
    if extension in TEXT_EXTENSIONS:
        return "TEXT", "utf-8"
    suspicious = sum(1 for byte in sample if byte < 32 and byte not in (8, 9, 10, 12, 13))
    if suspicious / len(sample) <= 0.01:
        return "TEXT", "utf-8"
    return "BINARY", None


def streaming_hash_and_sample(path: Path) -> tuple[str, bytes]:
    digest = hashlib.sha256()
    sample = bytearray()
    with path.open("rb") as stream:
        while True:
            chunk = stream.read(READ_CHUNK_SIZE)
            if not chunk:
                break
            digest.update(chunk)
            if len(sample) < CLASSIFICATION_SAMPLE_SIZE:
                remaining = CLASSIFICATION_SAMPLE_SIZE - len(sample)
                sample.extend(chunk[:remaining])
    return digest.hexdigest(), bytes(sample)


def streaming_line_count(path: Path, encoding: str) -> int:
    count = 0
    with path.open("rb") as raw:
        with io.TextIOWrapper(raw, encoding=encoding, errors="replace", newline=None) as text:
            for _line in text:
                count += 1
    return count


def base_record(relative_path: str, threshold: int) -> dict[str, Any]:
    filename = relative_path.rsplit("/", 1)[-1]
    parent = relative_path.rsplit("/", 1)[0] if "/" in relative_path else "."
    extension = extension_for(filename)
    return {
        "source_record_id": source_record_id(relative_path),
        "relative_path": relative_path,
        "parent_directory": parent,
        "filename": filename,
        "extension": extension,
        "size_bytes": 0,
        "sha256": "",
        "last_modified_utc": "",
        "file_category": category_for(extension),
        "top_level_source_group": top_level_group(relative_path),
        "zero_byte": "FALSE",
        "large_file": "FALSE",
        "text_binary_classification": "UNKNOWN",
        "line_count": "",
        "read_status": "NOT_READ",
        "error_message": "",
        "_threshold": threshold,
    }


def inspect_file(path: Path, relative_path: str, threshold: int) -> dict[str, Any]:
    record = base_record(relative_path, threshold)
    try:
        stat_result = path.lstat()
        record["size_bytes"] = stat_result.st_size
        record["last_modified_utc"] = timestamp_from_ns(stat_result.st_mtime_ns)
        record["zero_byte"] = bool_text(stat_result.st_size == 0)
        record["large_file"] = bool_text(stat_result.st_size >= threshold)
        if path.is_symlink():
            record["read_status"] = "SKIPPED_SYMLINK"
            record["error_message"] = "Symbolic-link file was not followed"
            return record
    except OSError as exc:
        record["read_status"] = "STAT_ERROR"
        record["error_message"] = clean_error(exc)
        return record

    try:
        file_hash, sample = streaming_hash_and_sample(path)
        classification, encoding = classify_sample(sample, record["extension"])
        record["sha256"] = file_hash
        record["text_binary_classification"] = classification
        if classification == "TEXT" and encoding is not None:
            record["line_count"] = streaming_line_count(path, encoding)
        record["read_status"] = "READ_OK"
    except OSError as exc:
        record["read_status"] = "READ_ERROR"
        record["error_message"] = clean_error(exc)
    return record


def walk_source(source_root: Path, threshold: int) -> tuple[list[dict[str, Any]], set[str], list[dict[str, str]]]:
    records: list[dict[str, Any]] = []
    directories: set[str] = {"."}
    traversal_errors: list[dict[str, str]] = []

    def on_walk_error(exc: OSError) -> None:
        filename = getattr(exc, "filename", None)
        if filename:
            try:
                relative = normalize_relative_path(Path(filename).relative_to(source_root))
            except (ValueError, OSError):
                relative = normalize_relative_path(filename)
        else:
            relative = "."
        traversal_errors.append({"path": relative, "error": clean_error(exc)})

    for current, dirnames, filenames in os.walk(
        source_root, topdown=True, onerror=on_walk_error, followlinks=False
    ):
        current_path = Path(current)
        current_relative = normalize_relative_path(current_path.relative_to(source_root))
        directories.add(current_relative)

        dirnames.sort(key=stable_sort_key)
        filenames.sort(key=stable_sort_key)

        retained_dirs: list[str] = []
        for dirname in dirnames:
            directory_path = current_path / dirname
            relative = normalize_relative_path(directory_path.relative_to(source_root))
            if directory_path.is_symlink():
                traversal_errors.append({
                    "path": relative,
                    "error": "Symbolic-link directory was not followed",
                })
            else:
                retained_dirs.append(dirname)
                directories.add(relative)
        dirnames[:] = retained_dirs

        for filename in filenames:
            path = current_path / filename
            relative = normalize_relative_path(path.relative_to(source_root))
            records.append(inspect_file(path, relative, threshold))

    records.sort(key=lambda row: stable_sort_key(row["relative_path"]))
    traversal_errors.sort(key=lambda row: stable_sort_key(row["path"] + "\0" + row["error"]))
    return records, directories, traversal_errors


def ancestors(directory: str) -> list[str]:
    if directory == ".":
        return ["."]
    parts = directory.split("/")
    result = ["."]
    result.extend("/".join(parts[:index]) for index in range(1, len(parts) + 1))
    return result


def format_distribution(counter: Counter[str]) -> str:
    if not counter:
        return ""
    return ";".join(f"{extension or '[no-extension]'}={counter[extension]}" for extension in sorted(counter, key=stable_sort_key))


def directory_summaries(
    records: Sequence[dict[str, Any]],
    directories: set[str],
    traversal_errors: Sequence[dict[str, str]],
) -> list[dict[str, Any]]:
    stats: dict[str, dict[str, Any]] = {
        directory: {
            "directory": directory,
            "direct_file_count": 0,
            "recursive_file_count": 0,
            "recursive_bytes": 0,
            "extensions": Counter(),
            "error_count": 0,
        }
        for directory in directories
    }
    for record in records:
        parent = record["parent_directory"]
        if parent not in stats:
            stats[parent] = {
                "directory": parent,
                "direct_file_count": 0,
                "recursive_file_count": 0,
                "recursive_bytes": 0,
                "extensions": Counter(),
                "error_count": 0,
            }
        stats[parent]["direct_file_count"] += 1
        for directory in ancestors(parent):
            if directory not in stats:
                continue
            stats[directory]["recursive_file_count"] += 1
            stats[directory]["recursive_bytes"] += int(record["size_bytes"])
            stats[directory]["extensions"][record["extension"]] += 1
            if record["read_status"] != "READ_OK":
                stats[directory]["error_count"] += 1
    for error in traversal_errors:
        error_path = error["path"]
        target = error_path if error_path in stats else (error_path.rsplit("/", 1)[0] if "/" in error_path else ".")
        for directory in ancestors(target):
            if directory in stats:
                stats[directory]["error_count"] += 1

    result = []
    for directory in sorted(stats, key=stable_sort_key):
        row = dict(stats[directory])
        row["extension_distribution"] = format_distribution(row.pop("extensions"))
        result.append(row)
    return result


def duplicate_rows(records: Sequence[dict[str, Any]]) -> list[dict[str, Any]]:
    grouped: dict[tuple[str, int], list[str]] = defaultdict(list)
    for record in records:
        if record["read_status"] == "READ_OK" and record["sha256"]:
            grouped[(record["sha256"], int(record["size_bytes"]))].append(record["relative_path"])
    rows = []
    for (file_hash, size), paths in grouped.items():
        if len(paths) < 2:
            continue
        paths = sorted(paths, key=stable_sort_key)
        rows.append({
            "duplicate_group_id": f"dup-{file_hash[:16]}-{size}",
            "sha256": file_hash,
            "size_bytes": size,
            "file_count": len(paths),
            "aggregate_bytes": size * len(paths),
            "relative_paths": " | ".join(paths),
        })
    rows.sort(key=lambda row: (-row["aggregate_bytes"], stable_sort_key(row["duplicate_group_id"])))
    return rows


LECTURE_PATTERN = re.compile(r"(?:^|[\W_\-])(lecture|lectures|lec|محاضرة|محاضرات)(?:[\W_\-]|$)", re.IGNORECASE)
SYLLABUS_PATTERN = re.compile(r"(syllabus|course[\W_\-]*outline|course[\W_\-]*description|منهج|مقرر)", re.IGNORECASE)


def university_course_rows(source_root: Path, records: Sequence[dict[str, Any]]) -> list[dict[str, Any]]:
    courses_root = source_root / "university-courses"
    if not courses_root.is_dir() or courses_root.is_symlink():
        return []
    try:
        course_directories = [path for path in courses_root.iterdir() if path.is_dir() and not path.is_symlink()]
    except OSError:
        return []
    course_directories.sort(key=lambda path: stable_sort_key(path.name))
    rows = []
    for course_path in course_directories:
        course_name = normalize_component(course_path.name)
        prefix = f"university-courses/{course_name}/"
        course_records = [record for record in records if record["relative_path"].startswith(prefix)]
        extension_counts = Counter(record["extension"] for record in course_records)
        lecture_count = sum(1 for record in course_records if LECTURE_PATTERN.search(Path(record["filename"]).stem))
        syllabus_present = any(SYLLABUS_PATTERN.search(Path(record["filename"]).stem) for record in course_records)
        rows.append({
            "course_folder": course_name,
            "recursive_file_count": len(course_records),
            "recursive_bytes": sum(int(record["size_bytes"]) for record in course_records),
            "extension_counts": format_distribution(extension_counts),
            "apparent_lecture_file_count": lecture_count,
            "syllabus_like_filename_present": bool_text(syllabus_present),
            "completeness": "UNKNOWN",
            "semantic_quality": "NOT_REVIEWED",
        })
    return rows


def tsv_text(columns: Sequence[str], rows: Iterable[dict[str, Any]]) -> str:
    output = io.StringIO(newline="")
    writer = csv.DictWriter(output, fieldnames=columns, delimiter="\t", lineterminator="\n", extrasaction="ignore")
    writer.writeheader()
    for row in rows:
        writer.writerow({column: row.get(column, "") for column in columns})
    return output.getvalue()


def markdown_cell(value: Any) -> str:
    return str(value).replace("|", "\\|").replace("\r", " ").replace("\n", " ")


def report_text(
    records: Sequence[dict[str, Any]],
    duplicates: Sequence[dict[str, Any]],
    courses: Sequence[dict[str, Any]],
    traversal_errors: Sequence[dict[str, str]],
    threshold: int,
) -> str:
    total_bytes = sum(int(record["size_bytes"]) for record in records)
    unreadable = [record for record in records if record["read_status"] != "READ_OK"]
    zero = [record for record in records if record["zero_byte"] == "TRUE"]
    large = [record for record in records if record["large_file"] == "TRUE"]
    classifications = Counter(record["text_binary_classification"] for record in records)
    source_groups: dict[str, dict[str, int]] = defaultdict(lambda: {"files": 0, "bytes": 0, "errors": 0})
    for record in records:
        group = source_groups[record["top_level_source_group"]]
        group["files"] += 1
        group["bytes"] += int(record["size_bytes"])
        group["errors"] += int(record["read_status"] != "READ_OK")
    largest = sorted(records, key=lambda row: (-int(row["size_bytes"]), stable_sort_key(row["relative_path"])))[:20]

    lines = [
        "# Source Readiness Report",
        "",
        "> Scope boundary: this is a mechanical filesystem inventory. It is not semantic inspection, curriculum review, source selection, archive extraction, or lab execution.",
        "",
        "## Mechanical totals",
        "",
        f"- Files: {len(records)}",
        f"- Bytes: {total_bytes}",
        f"- Readable files: {len(records) - len(unreadable)}",
        f"- File read/stat/symlink errors: {len(unreadable)}",
        f"- Traversal errors or skipped symlink directories: {len(traversal_errors)}",
        f"- Text-classified files: {classifications.get('TEXT', 0)}",
        f"- Binary-classified files: {classifications.get('BINARY', 0)}",
        f"- Unknown-classified files: {classifications.get('UNKNOWN', 0)}",
        f"- Zero-byte files: {len(zero)}",
        f"- Large files (threshold {threshold} bytes): {len(large)}",
        f"- Duplicate hash-and-size groups: {len(duplicates)}",
        f"- Files participating in duplicate groups: {sum(int(row['file_count']) for row in duplicates)}",
        "",
        "## Source-group totals",
        "",
        "| Source group | Files | Bytes | Errors |",
        "|---|---:|---:|---:|",
    ]
    for group_name in sorted(source_groups, key=stable_sort_key):
        group = source_groups[group_name]
        lines.append(f"| {markdown_cell(group_name)} | {group['files']} | {group['bytes']} | {group['errors']} |")

    lines.extend([
        "",
        "## Largest files",
        "",
        "| Relative path | Bytes | SHA-256 |",
        "|---|---:|---|",
    ])
    if largest:
        for record in largest:
            lines.append(f"| {markdown_cell(record['relative_path'])} | {record['size_bytes']} | {record['sha256']} |")
    else:
        lines.append("| _None_ | 0 | |")

    lines.extend([
        "",
        "## Duplicate files",
        "",
        "Duplicate reporting is informational only. No file was deleted or modified.",
        "",
        "| Group | Files | Size each | SHA-256 | Paths |",
        "|---|---:|---:|---|---|",
    ])
    if duplicates:
        for row in duplicates[:20]:
            lines.append(f"| {row['duplicate_group_id']} | {row['file_count']} | {row['size_bytes']} | {row['sha256']} | {markdown_cell(row['relative_paths'])} |")
        if len(duplicates) > 20:
            lines.append(f"\n_Only the first 20 of {len(duplicates)} groups are shown here; see `DUPLICATE_FILE_HASHES.tsv` for all groups._")
    else:
        lines.append("| _None_ | 0 | 0 | | |")

    lines.extend([
        "",
        "## Zero-byte files",
        "",
    ])
    if zero:
        lines.extend(f"- `{record['relative_path']}`" for record in zero[:50])
        if len(zero) > 50:
            lines.append(f"- _{len(zero) - 50} additional paths are listed in `ZERO_BYTE_FILES.tsv`._")
    else:
        lines.append("No zero-byte files were observed.")

    lines.extend([
        "",
        "## Unreadable or skipped paths",
        "",
    ])
    if unreadable or traversal_errors:
        for record in unreadable:
            lines.append(f"- `{record['relative_path']}` — {record['read_status']}: {record['error_message']}")
        for error in traversal_errors:
            lines.append(f"- `{error['path']}` — traversal: {error['error']}")
    else:
        lines.append("No unreadable or skipped paths were observed.")

    lines.extend([
        "",
        "## University course totals",
        "",
        f"- Candidate course folders: {len(courses)}",
        f"- Files under candidate course folders: {sum(int(row['recursive_file_count']) for row in courses)}",
        f"- Bytes under candidate course folders: {sum(int(row['recursive_bytes']) for row in courses)}",
        f"- Apparent lecture files by filename only: {sum(int(row['apparent_lecture_file_count']) for row in courses)}",
        f"- Candidate folders with a syllabus-like filename: {sum(row['syllabus_like_filename_present'] == 'TRUE' for row in courses)}",
        "",
        "All course `completeness` values remain `UNKNOWN`, and all `semantic_quality` values remain `NOT_REVIEWED`.",
        "",
    ])
    return "\n".join(lines)


def gaps_text(
    source_root: Path,
    records: Sequence[dict[str, Any]],
    directories: set[str],
    courses: Sequence[dict[str, Any]],
    traversal_errors: Sequence[dict[str, str]],
) -> str:
    observed_groups = {record["top_level_source_group"] for record in records}
    observed_groups.update(directory.split("/", 1)[0] for directory in directories if directory != ".")
    missing_groups = [group for group in EXPECTED_SOURCE_GROUPS if group not in observed_groups]
    unreadable = [record for record in records if record["read_status"] != "READ_OK"]
    zero = [record for record in records if record["zero_byte"] == "TRUE"]
    empty_directories = []
    for directory in directories:
        prefix = "" if directory == "." else f"{directory}/"
        if not any(record["relative_path"].startswith(prefix) for record in records):
            empty_directories.append(directory)
    empty_courses = [row for row in courses if int(row["recursive_file_count"]) == 0]

    lines = [
        "# Source Gaps",
        "",
        "> This report contains observable filesystem conditions only. It does not identify subject-matter, curriculum, quality, coverage, or semantic gaps.",
        "",
        "## Missing expected top-level folders",
        "",
    ]
    if missing_groups:
        lines.extend(f"- `{group}/` is not present." for group in missing_groups)
    else:
        lines.append("All expected top-level source folders are present.")

    lines.extend(["", "## Unreadable or skipped filesystem paths", ""])
    if unreadable or traversal_errors:
        for record in unreadable:
            lines.append(f"- `{record['relative_path']}` — {record['read_status']}: {record['error_message']}")
        for error in traversal_errors:
            lines.append(f"- `{error['path']}` — {error['error']}")
    else:
        lines.append("No unreadable or skipped filesystem paths were observed.")

    lines.extend(["", "## Zero-byte files", ""])
    if zero:
        lines.extend(f"- `{record['relative_path']}`" for record in zero)
    else:
        lines.append("No zero-byte files were observed.")

    lines.extend(["", "## Empty directories", ""])
    if empty_directories:
        lines.extend(f"- `{directory}`" for directory in sorted(empty_directories, key=stable_sort_key))
    else:
        lines.append("No empty directories were observed.")

    lines.extend(["", "## Empty university-course candidate folders", ""])
    if empty_courses:
        lines.extend(f"- `{row['course_folder']}`" for row in empty_courses)
    else:
        lines.append("No empty university-course candidate folders were observed.")

    lines.extend([
        "",
        "## Boundary",
        "",
        f"The inspected root was `{source_root}`. Archive contents were not opened or decompressed, and no claim is made about source meaning, completeness, correctness, or fitness.",
        "",
    ])
    return "\n".join(lines)


def atomic_write_text(path: Path, content: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    temporary_name: str | None = None
    try:
        with tempfile.NamedTemporaryFile(
            mode="w",
            encoding="utf-8",
            newline="",
            dir=path.parent,
            prefix=f".{path.name}.",
            suffix=".tmp",
            delete=False,
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


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        while True:
            chunk = stream.read(READ_CHUNK_SIZE)
            if not chunk:
                break
            digest.update(chunk)
    return digest.hexdigest()


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


def validate_roots(source_root: Path, manifest_root: Path, report_root: Path) -> tuple[Path, Path, Path]:
    source = source_root.expanduser().resolve(strict=True)
    if not source.is_dir():
        raise InventoryError(f"Source root is not a directory: {source}")
    manifest = manifest_root.expanduser().resolve(strict=False)
    report = report_root.expanduser().resolve(strict=False)
    if paths_overlap(source, manifest) or paths_overlap(source, report):
        raise InventoryError("Output roots must not overlap the read-only source root")
    if paths_overlap(manifest, report):
        raise InventoryError("Manifest and report roots must not overlap")
    return source, manifest, report


def generate(
    source_root: Path,
    manifest_root: Path,
    report_root: Path,
    threshold: int = DEFAULT_LARGE_FILE_THRESHOLD,
) -> dict[str, Any]:
    if threshold <= 0:
        raise InventoryError("Large-file threshold must be greater than zero")
    source_root, manifest_root, report_root = validate_roots(source_root, manifest_root, report_root)
    started = utc_now()

    records, directories, traversal_errors = walk_source(source_root, threshold)
    summaries = directory_summaries(records, directories, traversal_errors)
    duplicates = duplicate_rows(records)
    large_files = [record for record in records if record["large_file"] == "TRUE"]
    zero_files = [record for record in records if record["zero_byte"] == "TRUE"]
    courses = university_course_rows(source_root, records)

    manifest_rows = [
        {
            "source_record_id": record["source_record_id"],
            "relative_path": record["relative_path"],
            "top_level_source_group": record["top_level_source_group"],
            "file_category": record["file_category"],
            "read_status": record["read_status"],
            "inventory_state": "MECHANICALLY_INVENTORIED",
            "semantic_inspection_state": "NOT_SEMANTICALLY_INSPECTED",
            "selection_state": "REQUIRES_SELECTION",
        }
        for record in records
    ]

    duplicate_columns = (
        "duplicate_group_id", "sha256", "size_bytes", "file_count",
        "aggregate_bytes", "relative_paths",
    )
    subset_columns = CENSUS_COLUMNS

    outputs: dict[Path, str] = {
        manifest_root / "SOURCE_FILE_CENSUS.tsv": tsv_text(CENSUS_COLUMNS, records),
        manifest_root / "SOURCE_MANIFEST.tsv": tsv_text(MANIFEST_COLUMNS, manifest_rows),
        manifest_root / "DIRECTORY_SUMMARY.tsv": tsv_text(DIRECTORY_COLUMNS, summaries),
        manifest_root / "DUPLICATE_FILE_HASHES.tsv": tsv_text(duplicate_columns, duplicates),
        manifest_root / "LARGE_FILES.tsv": tsv_text(subset_columns, large_files),
        manifest_root / "ZERO_BYTE_FILES.tsv": tsv_text(subset_columns, zero_files),
        manifest_root / "UNIVERSITY_COURSE_INVENTORY.tsv": tsv_text(COURSE_COLUMNS, courses),
        report_root / "SOURCE_READINESS_REPORT.md": report_text(records, duplicates, courses, traversal_errors, threshold),
        report_root / "SOURCE_GAPS.md": gaps_text(source_root, records, directories, courses, traversal_errors),
    }
    for output_path in sorted(outputs, key=lambda path: stable_sort_key(path.as_posix())):
        atomic_write_text(output_path, outputs[output_path])

    output_hashes: dict[str, str] = {}
    for output_path in sorted(outputs, key=lambda path: stable_sort_key(path.as_posix())):
        if output_path.parent == manifest_root:
            key = f"manifests/{output_path.name}"
        else:
            key = f"derived/readiness/{output_path.name}"
        output_hashes[key] = sha256_file(output_path)

    unreadable = [record for record in records if record["read_status"] != "READ_OK"]
    ended = utc_now()
    metadata: dict[str, Any] = {
        "tool_version": TOOL_VERSION,
        "utc_start": utc_text(started),
        "utc_end": utc_text(ended),
        "source_root": str(source_root),
        "manifest_root": str(manifest_root),
        "report_root": str(report_root),
        "large_file_threshold_bytes": threshold,
        "python_version": platform.python_version(),
        "platform": platform.platform(),
        "counts": {
            "source_files": len(records),
            "source_bytes": sum(int(record["size_bytes"]) for record in records),
            "directories": len(directories),
            "readable_files": len(records) - len(unreadable),
            "file_errors_or_skips": len(unreadable),
            "traversal_errors_or_skips": len(traversal_errors),
            "large_files": len(large_files),
            "zero_byte_files": len(zero_files),
            "duplicate_groups": len(duplicates),
            "duplicate_file_instances": sum(int(row["file_count"]) for row in duplicates),
            "university_course_candidates": len(courses),
        },
        "errors": {
            "files": [
                {
                    "relative_path": record["relative_path"],
                    "status": record["read_status"],
                    "message": record["error_message"],
                }
                for record in unreadable
            ],
            "traversal": list(traversal_errors),
        },
        "output_hashes_sha256": output_hashes,
        "output_hash_scope_note": "Hashes cover all generated TSV and Markdown outputs; TOOL_RUN_METADATA.json is excluded to avoid a self-referential hash.",
    }
    metadata_path = report_root / "TOOL_RUN_METADATA.json"
    atomic_write_text(metadata_path, json.dumps(metadata, ensure_ascii=False, indent=2, sort_keys=True) + "\n")
    return metadata


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Create a deterministic mechanical inventory without modifying source files."
    )
    parser.add_argument("--source-root", type=Path, required=True)
    parser.add_argument("--manifest-root", type=Path, required=True)
    parser.add_argument("--report-root", type=Path, required=True)
    parser.add_argument(
        "--large-file-threshold-bytes",
        type=int,
        default=DEFAULT_LARGE_FILE_THRESHOLD,
        help=f"Large-file threshold in bytes (default: {DEFAULT_LARGE_FILE_THRESHOLD})",
    )
    parser.add_argument("--version", action="version", version=TOOL_VERSION)
    return parser


def main(argv: Sequence[str] | None = None) -> int:
    args = build_parser().parse_args(argv)
    try:
        metadata = generate(
            source_root=args.source_root,
            manifest_root=args.manifest_root,
            report_root=args.report_root,
            threshold=args.large_file_threshold_bytes,
        )
    except (InventoryError, OSError) as exc:
        print(f"source-readiness: {exc}", file=sys.stderr)
        return 2
    counts = metadata["counts"]
    print(
        "Source readiness inventory complete: "
        f"files={counts['source_files']} bytes={counts['source_bytes']} "
        f"errors={counts['file_errors_or_skips'] + counts['traversal_errors_or_skips']}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

#!/usr/bin/env python3
"""Deterministic integrity and safety validator for TASK-003 outputs."""

from __future__ import annotations

import csv
import hashlib
import json
import os
import sys
import unicodedata
from collections import Counter
from pathlib import Path


ROOT = Path(__file__).resolve().parents[3]
ORIGINALS = ROOT / "source-vault" / "originals"
SEM = ROOT / "source-vault" / "manifests" / "semantic"
REPORTS = ROOT / "source-vault" / "derived" / "semantic"
PACKET = ROOT / "product-repo" / "review-packets" / "semantic-capability-003"

TSV_SCHEMAS = {
    "SEMANTIC_REVIEW_CORPUS.tsv": ["source_record_id","original_relative_path","authority_tier","selection_reason","review_depth","planned_units","actual_units_reviewed","deferred_reason","corpus_status"],
    "SEMANTIC_REVIEW_EVIDENCE.tsv": ["semantic_evidence_id","source_record_id","original_relative_path","task002_segment_or_page_id","heading_path","reviewed_line_or_page_range","unit_sha256_or_source_hash","review_depth","reviewer_state","findings_reference"],
    "SOURCE_AUTHORITY_REGISTER.tsv": ["source_record_id","original_relative_path","authority_tier","review_depth","semantic_quality","currency_and_applicability","scope_relevance","provenance_confidence","reuse_decision","limitations","semantic_evidence_ids"],
    "SEMANTIC_SOURCE_ASSESSMENTS.tsv": ["assessment_id","source_record_id","original_relative_path","semantic_evidence_ids","actual_coverage","audience_and_depth","content_mode","concrete_elements","internal_coherence","duplication_assessment","currency_and_applicability","suitability","supports_domains","does_not_support","external_verification_needed","finding_type","review_depth"],
    "SOURCE_TO_DOMAIN_MAP.tsv": ["source_record_id","original_relative_path","domain_code","mapping_state","semantic_evidence_ids","mapping_rationale"],
    "CAPABILITY_CLUSTER_CATALOG.tsv": ["cluster_id","domain_code","cluster_name","purpose","scope","exclusions_and_boundaries","related_clusters","supported_professional_roles","supporting_sources","coverage_status"],
    "CAPABILITY_CATALOG.tsv": ["capability_id","parent_cluster_id","capability_statement","scope_and_boundaries","prerequisites","expected_evidence","simulator_suitability","real_lab_classification","related_roles","related_domains","supporting_sources","coverage_confidence","v1_priority"],
    "KNOWLEDGE_UNIT_CANDIDATES.tsv": ["knowledge_unit_id","title","primary_domain","related_domains","parent_capability_id","capability_centered_learning_outcome","prerequisites","proposed_lesson_scope","micro_practice","simulator_lab_suitability","real_lab_classification","evidence_types","mastery_criteria_concept","failure_and_review_triggers","supporting_sources","source_gaps","lifecycle_template","v1_priority","status"],
    "CROSS_DOMAIN_RELATIONSHIPS.tsv": ["relationship_id","canonical_knowledge_unit_id","primary_domain","related_domain","relationship_type","context_note","duplicate_ku_created"],
    "SOURCE_TO_CAPABILITY_MAP.tsv": ["source_record_id","original_relative_path","capability_id","support_type","semantic_evidence_ids","coverage_confidence"],
    "DOMAIN_COVERAGE_MATRIX.tsv": ["domain_code","domain_name","cluster_ids","capability_ids","knowledge_unit_ids","reviewed_supporting_sources","university_course_support","simulator_feasibility","real_lab_dependence","coverage_confidence","major_gaps","v1_post_v1_position"],
    "VS001_SOURCE_SELECTION.tsv": ["selection_id","source_record_id","original_relative_path","semantic_evidence_ids","requirement_id","requirement_statement","capability_id","knowledge_unit_id","acceptance_need","selection_role","out_of_scope"],
    "SEMANTIC_DEFERRED_QUEUE.tsv": ["deferred_id","source_path_or_scope","deferred_state","reason","future_trigger"],
    "UNRESOLVED_SOURCE_ISSUES.tsv": ["issue_id","source_path_or_scope","issue","impact","resolution_needed","status"],
}

REPORT_FILES = [
    "SEMANTIC_REVIEW_REPORT.md", "SOURCE_AUTHORITY_POLICY.md", "CYBERSECURITY_DOMAIN_TAXONOMY_V1.md",
    "CAPABILITY_ARCHITECTURE_BASELINE.md", "ADAPTIVE_LEARNING_MODEL.md", "UNIVERSITY_COURSE_SEMANTIC_ASSESSMENT.md",
    "CURRICULUM_COVERAGE_AND_GAPS.md", "VS001_SOURCE_SELECTION.md", "TASK003_DECISION_REGISTER.md", "UNRESOLVED_DECISIONS.md",
]
PACKET_FILES = [
    "CODEX_FINAL_REPORT.md", "CHANGED_FILES.txt", "TEST_RESULTS.txt", "REVIEW_CORPUS_SUMMARY.md",
    "SOURCE_AUTHORITY_SUMMARY.md", "CAPABILITY_OUTPUT_COUNTS.md", "COVERAGE_SUMMARY.md", "SOURCE_SAFETY_RESULTS.md",
    "RESIDUAL_LIMITATIONS.md", "REVIEWED_SOURCE_FILES_MANIFEST.tsv",
]


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def read_tsv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as stream:
        return list(csv.DictReader(stream, delimiter="\t"))


def source_metadata_fingerprint() -> tuple[str, int]:
    def sort_key(value: str) -> bytes:
        return unicodedata.normalize("NFC", value).encode("utf-8")

    rows = []
    for current, dirnames, filenames in os.walk(ORIGINALS, topdown=True, followlinks=False):
        current_path = Path(current)
        dirnames[:] = sorted([name for name in dirnames if not (current_path / name).is_symlink()], key=sort_key)
        for filename in sorted(filenames, key=sort_key):
            path = current_path / filename
            if path.is_symlink():
                continue
            stat = path.stat()
            relative = "/".join(unicodedata.normalize("NFC", part) for part in path.relative_to(ORIGINALS).parts)
            rows.append(f"{relative}\t{stat.st_size}\t{stat.st_mtime_ns}")
    rows.sort(key=sort_key)
    return hashlib.sha256("\n".join(rows).encode("utf-8")).hexdigest(), len(rows)


def require(condition: bool, message: str, results: list[str]) -> None:
    if not condition:
        raise AssertionError(message)
    results.append(f"PASS: {message}")


def validate_recorded_outputs(metadata_path: Path, results: list[str], label: str) -> None:
    metadata = json.loads(metadata_path.read_text(encoding="utf-8"))
    for relative, expected in metadata["output_hashes_sha256"].items():
        path = ROOT / "source-vault" / relative
        require(path.is_file() and sha256(path) == expected, f"{label} output unchanged: {relative}", results)


def main() -> int:
    results: list[str] = []
    require((ROOT / "AGENTS.md").is_file(), "root AGENTS.md exists", results)
    for name in TSV_SCHEMAS:
        require((SEM / name).is_file(), f"semantic manifest exists: {name}", results)
    for name in REPORT_FILES:
        require((REPORTS / name).is_file(), f"semantic report exists: {name}", results)
    for name in PACKET_FILES:
        if name != "CHANGED_FILES.txt":
            require((PACKET / name).is_file(), f"review packet file exists: {name}", results)

    tables = {}
    for name, expected_schema in TSV_SCHEMAS.items():
        path = SEM / name
        with path.open("r", encoding="utf-8", newline="") as stream:
            reader = csv.DictReader(stream, delimiter="\t")
            require(reader.fieldnames == expected_schema, f"TSV schema exact: {name}", results)
            tables[name] = list(reader)

    corpus = tables["SEMANTIC_REVIEW_CORPUS.tsv"]
    evidence = tables["SEMANTIC_REVIEW_EVIDENCE.tsv"]
    authority = tables["SOURCE_AUTHORITY_REGISTER.tsv"]
    assessments = tables["SEMANTIC_SOURCE_ASSESSMENTS.tsv"]
    clusters = tables["CAPABILITY_CLUSTER_CATALOG.tsv"]
    capabilities = tables["CAPABILITY_CATALOG.tsv"]
    kus = tables["KNOWLEDGE_UNIT_CANDIDATES.tsv"]
    relationships = tables["CROSS_DOMAIN_RELATIONSHIPS.tsv"]
    coverage = tables["DOMAIN_COVERAGE_MATRIX.tsv"]
    src_cap = tables["SOURCE_TO_CAPABILITY_MAP.tsv"]
    vs001 = tables["VS001_SOURCE_SELECTION.tsv"]
    deferred = tables["SEMANTIC_DEFERRED_QUEUE.tsv"]

    require(len(corpus) == 80, "review corpus respects exact 80-file bounded selection", results)
    require(len({row["original_relative_path"] for row in corpus}) == len(corpus), "review corpus paths unique", results)
    require(len(evidence) <= 250, "semantic review units do not exceed 250", results)
    require(len({row["semantic_evidence_id"] for row in evidence}) == len(evidence), "semantic evidence IDs unique", results)
    evidence_ids = {row["semantic_evidence_id"] for row in evidence}
    evidence_by_path = Counter(row["original_relative_path"] for row in evidence)

    for row in corpus:
        depth = row["review_depth"]
        units = int(row["actual_units_reviewed"])
        require(units == evidence_by_path[row["original_relative_path"]], f"corpus actual unit count resolves: {row['original_relative_path']}", results)
        if depth == "REVIEWED_FULL":
            matching = [item for item in evidence if item["original_relative_path"] == row["original_relative_path"]]
            require(len(matching) == 1 and matching[0]["heading_path"] == "COMPLETE_FILE" and matching[0]["reviewed_line_or_page_range"].startswith("lines 1-"), f"full review has complete-file evidence: {row['original_relative_path']}", results)
        if depth == "DEFERRED_OCR_REQUIRED":
            require(units == 0 and row["corpus_status"] == "DEFERRED", f"OCR source has no semantic-review claim: {row['original_relative_path']}", results)

    for row in authority:
        require(bool(row["authority_tier"] and row["review_depth"] and row["reuse_decision"]), f"source authority and depth recorded: {row['original_relative_path']}", results)
        for evidence_id in filter(None, row["semantic_evidence_ids"].split(";")):
            require(evidence_id in evidence_ids, f"authority evidence resolves: {evidence_id}", results)
    assessment_by_path = {row["original_relative_path"]: row for row in assessments}
    for row in corpus:
        require(row["original_relative_path"] in assessment_by_path, f"source assessment exists: {row['original_relative_path']}", results)
        assessment = assessment_by_path[row["original_relative_path"]]
        if row["corpus_status"] == "REVIEW_COMPLETE":
            require(bool(assessment["semantic_evidence_ids"]), f"reviewed source assessment cites evidence: {row['original_relative_path']}", results)
    for row in src_cap + vs001:
        require(all(item in evidence_ids for item in filter(None, row["semantic_evidence_ids"].split(";"))), f"semantic mapping evidence resolves: {row.get('original_relative_path','')}", results)

    domain_ids = {f"D{number:02d}" for number in range(1, 17)}
    require({row["domain_code"] for row in coverage} == domain_ids, "all 16 approved Domains represented exactly", results)
    cluster_ids = {row["cluster_id"] for row in clusters}
    capability_ids = {row["capability_id"] for row in capabilities}
    ku_ids = {row["knowledge_unit_id"] for row in kus}
    require(len(cluster_ids) == len(clusters), "cluster IDs unique", results)
    require(len(capability_ids) == len(capabilities), "capability IDs unique", results)
    require(len(ku_ids) == len(kus), "Knowledge Unit IDs unique", results)
    require(all(row["domain_code"] in domain_ids for row in clusters), "every cluster belongs to an approved Domain", results)
    require(all(row["parent_cluster_id"] in cluster_ids for row in capabilities), "every capability belongs to one existing cluster", results)
    require(all(row["parent_capability_id"] in capability_ids for row in kus), "every Knowledge Unit belongs to one existing capability", results)
    require(len(kus) <= 120, "Knowledge Unit maximum of 120 respected", results)
    require("KU-AD-02" in ku_ids, "VS-001 pilot KU-AD-02 preserved", results)
    require(all(row["canonical_knowledge_unit_id"] in ku_ids and row["duplicate_ku_created"] == "FALSE" for row in relationships), "cross-domain mappings resolve without duplicate canonical KUs", results)
    require(all(row["real_lab_classification"] != "REQUIRED" for row in capabilities + kus), "Real-Lab is not universally mandatory", results)

    for name in REPORT_FILES:
        body = (REPORTS / name).read_text(encoding="utf-8")
        for label in ("FACT FROM SOURCE", "REVIEWER INTERPRETATION", "PROJECT DECISION", "PROVISIONAL RECOMMENDATION", "UNRESOLVED GAP"):
            require(label in body, f"report distinguishes statement label {label}: {name}", results)
    authored = (ROOT / "AGENTS.md").read_text(encoding="utf-8") + "\n" + (REPORTS / "ADAPTIVE_LEARNING_MODEL.md").read_text(encoding="utf-8")
    require("Manual AI Bridge" in authored, "Manual AI Bridge remains the only named v1 AI execution path", results)
    require("OpenAI API Adapter" not in authored and "Local AI Adapter" not in authored, "no automated/provider AI adapter introduced", results)

    deferred_by_path = {row["source_path_or_scope"]: row["deferred_state"] for row in deferred}
    require(deferred_by_path.get("chatgpt-project/Cybersecurity-for-dummies.pdf") == "DEFERRED_AES_PARSE_DEPENDENCY", "AES-failed PDF remains explicitly AES parse-deferred", results)

    copy_manifest_path = PACKET / "REVIEWED_SOURCE_FILES_MANIFEST.tsv"
    with copy_manifest_path.open("r", encoding="utf-8", newline="") as stream:
        reader = csv.DictReader(stream, delimiter="\t")
        expected_header = ["original_relative_path","copied_relative_path","source_sha256","file_size_bytes","review_depth","reviewed_line_or_page_ranges","semantic_evidence_ids","copy_status"]
        require(reader.fieldnames == expected_header, "reviewed-source manifest schema exact", results)
        copy_rows = list(reader)
    require(len(copy_rows) == len({row["original_relative_path"] for row in copy_rows}), "each reviewed source copied once", results)
    reviewed_paths = {row["original_relative_path"] for row in corpus if row["corpus_status"] == "REVIEW_COMPLETE"}
    require({row["original_relative_path"] for row in copy_rows} == reviewed_paths, "copies include exactly semantically reviewed source files", results)
    copied_bytes = 0
    for row in copy_rows:
        original = ORIGINALS / row["original_relative_path"]
        copied = PACKET / Path(row["copied_relative_path"])
        require(original.is_file() and copied.is_file(), f"original and full copy exist: {row['original_relative_path']}", results)
        require(original.stat().st_size == copied.stat().st_size == int(row["file_size_bytes"]), f"copy size exact: {row['original_relative_path']}", results)
        require(sha256(original) == sha256(copied) == row["source_sha256"], f"copy SHA-256 matches original: {row['original_relative_path']}", results)
        require(row["copy_status"] == "VERIFIED_SHA256_MATCH", f"copy status verified: {row['original_relative_path']}", results)
        require(all(item in evidence_ids for item in row["semantic_evidence_ids"].split(";") if item), f"copy evidence IDs resolve: {row['original_relative_path']}", results)
        copied_bytes += int(row["file_size_bytes"])

    fingerprint, source_count = source_metadata_fingerprint()
    structural_metadata = json.loads((ROOT / "source-vault" / "derived" / "structural" / "STRUCTURAL_RUN_METADATA.json").read_text(encoding="utf-8"))
    require(source_count == 2083 and source_count == structural_metadata["source_safety"]["file_count_after"], "original source count unchanged", results)
    require(fingerprint == structural_metadata["source_safety"]["metadata_fingerprint_after"] == "97a4013d72c5c1516410e93f57cbede3beb5f5f38dda611aab943ba1351c2f72", "original source fingerprint unchanged", results)
    validate_recorded_outputs(ROOT / "source-vault" / "derived" / "readiness" / "TOOL_RUN_METADATA.json", results, "Task 001")
    validate_recorded_outputs(ROOT / "source-vault" / "derived" / "structural" / "STRUCTURAL_RUN_METADATA.json", results, "Task 002")

    safety = f"""# Source Safety Results\n\n- Original source count: {source_count} - PASS\n- Original source fingerprint: `{fingerprint}` - PASS\n- Task 001 recorded outputs unchanged: PASS\n- Task 002 recorded outputs unchanged: PASS\n- Reviewed full-source copies: {len(copy_rows)}\n- Reviewed full-source bytes: {copied_bytes}\n- Original/copied SHA-256 mismatches: 0\n- Originals modified by TASK-003: 0\n"""
    (PACKET / "SOURCE_SAFETY_RESULTS.md").write_text(safety, encoding="utf-8", newline="\n")

    displayed_assertions = len(results) + 1  # includes the final CHANGED_FILES existence assertion below
    test_summary = [
        "TASK-003 semantic baseline build: PASS",
        f"Deterministic assertions: {displayed_assertions} PASS, 0 FAIL",
        f"Corpus limits: {len(corpus)}/80 files, {len(evidence)}/250 units",
        f"Architecture integrity: {len(clusters)} clusters, {len(capabilities)} capabilities, {len(kus)} KUs",
        f"Reviewed-source copies: {len(copy_rows)} files, {copied_bytes} bytes, 0 hash mismatches",
        "Original fingerprint regression: PASS",
        "Task 001 output regression: PASS",
        "Task 002 output regression: PASS",
        "Task 001 unit regression: 8/8 PASS on bundled Python 3.12",
        "Task 002 unit regression: 18/18 PASS on required Python 3.13 with existing bundled pypdf 6.10.0",
        "Environment note: an initial Task 002 attempt on Python 3.12 passed 16 tests and correctly hit 2 runtime-version gates; the final required-runtime run passed 18/18",
        "Product application build: NOT RUN (prohibited)",
    ]
    (PACKET / "TEST_RESULTS.txt").write_text("\n".join(test_summary) + "\n", encoding="utf-8", newline="\n")

    final_report = (PACKET / "CODEX_FINAL_REPORT.md").read_text(encoding="utf-8")
    validation_sentence = f"Deterministic validation passed: {displayed_assertions} assertions, 0 failures. Original fingerprint, Task 001 outputs, Task 002 outputs, all copied-file hashes, TSV schemas, stable IDs, foreign keys, claim evidence, corpus limits, Real-Lab selectivity, and Manual AI Bridge constraints passed."
    final_report = final_report.replace("Deterministic validation is pending the separate validator run. Original and Task 001/002 safety must pass before the stop gate.", validation_sentence)
    final_report = final_report.replace("Deterministic validation passed: 1643 assertions, 0 failures. Original fingerprint, Task 001 outputs, Task 002 outputs, all copied-file hashes, TSV schemas, stable IDs, foreign keys, claim evidence, corpus limits, Real-Lab selectivity, and Manual AI Bridge constraints passed.", validation_sentence)
    if "Task 001 regression suite: 8/8" not in final_report:
        final_report = final_report.replace(
            "## Known limitations and unresolved decisions",
            "- Task 001 regression suite: 8/8 passed.\n- Task 002 regression suite: 18/18 passed on its required Python 3.13 runtime with the existing bundled pypdf 6.10.0.\n\n## Known limitations and unresolved decisions",
        )
    (PACKET / "CODEX_FINAL_REPORT.md").write_text(final_report, encoding="utf-8", newline="\n")

    allowed_files = [ROOT / "AGENTS.md"]
    for base in [ROOT / "product-repo" / "tools" / "semantic_architecture_validation", PACKET, SEM, REPORTS]:
        allowed_files.extend(path for path in base.rglob("*") if path.is_file())
    changed = sorted({path.relative_to(ROOT).as_posix() for path in allowed_files} | {"product-repo/review-packets/semantic-capability-003/CHANGED_FILES.txt"}, key=str.casefold)
    (PACKET / "CHANGED_FILES.txt").write_text("\n".join(changed) + "\n", encoding="utf-8", newline="\n")
    require((PACKET / "CHANGED_FILES.txt").is_file(), "review packet file exists: CHANGED_FILES.txt", results)

    print(f"PASS assertions={len(results)} sources={len(corpus)} units={len(evidence)} copies={len(copy_rows)} bytes={copied_bytes}")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(f"FAIL: {type(exc).__name__}: {exc}", file=sys.stderr)
        raise

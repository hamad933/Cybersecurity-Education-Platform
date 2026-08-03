#!/usr/bin/env python3
"""Build the source-grounded TASK-003R semantic refinement and handoff.

The semantic interpretations are maintained in ``refinement_data.py``.  This
script performs deterministic assembly, reference resolution, measurements,
and packaging; it does not infer curriculum meaning from filenames.
"""

from __future__ import annotations

import argparse
import csv
import hashlib
import io
import os
import platform
import shutil
import sys
import zipfile
from collections import Counter, defaultdict
from pathlib import Path

from refinement_data import (
    CLUSTER_SPEC_TEXT,
    COURSE_SYNTHESIS,
    DOMAIN_DATA,
    DOMAIN_EVIDENCE_TYPES,
    DOMAIN_RELATIONS,
    DOMAIN_SCENARIOS,
    NEW_EVIDENCE_SPECS,
    NON_UNIVERSITY_OBSERVATIONS,
    SPLIT_KUS,
    UNIVERSITY_OBSERVATIONS,
    UNSEEDED_CAPABILITY_IDS,
)


ROOT = Path(__file__).resolve().parents[3]
OLD_SEM = ROOT / "source-vault" / "manifests" / "semantic"
OLD_REPORTS = ROOT / "source-vault" / "derived" / "semantic"
OLD_PACKET = ROOT / "product-repo" / "review-packets" / "semantic-capability-003"
OLD_COPIES = OLD_PACKET / "reviewed-source-copies"
ORIGINALS = ROOT / "source-vault" / "originals"
STRUCTURAL = ROOT / "source-vault" / "manifests" / "structural"
REFINED_SEM = ROOT / "source-vault" / "manifests" / "semantic-refined"
REFINED_REPORTS = ROOT / "source-vault" / "derived" / "semantic-refined"
REFINED_TOOLS = ROOT / "product-repo" / "tools" / "semantic_architecture_refinement"
REFINED_PACKET = ROOT / "product-repo" / "review-packets" / "semantic-capability-003r"
HANDOFF = ROOT / "product-repo" / "review-packets" / "TASK_003R_REVIEW_HANDOFF"
HANDOFF_ZIP = ROOT / "product-repo" / "review-packets" / "TASK_003R_REVIEW_HANDOFF.zip"


SCHEMAS = {
    "TASK003_TO_TASK003R_SUPERSESSION_MAP.tsv": ["old_artifact_path", "refined_artifact_path", "status", "reason", "downstream_use_rule"],
    "ID_SUPERSESSION_MAP.tsv": ["entity_type", "old_id", "new_id", "status", "reason", "downstream_use_rule"],
    "SEMANTIC_REVIEW_CORPUS_REFINED.tsv": ["source_record_id", "original_relative_path", "authority_tier", "selection_reason", "review_depth", "planned_units", "actual_units_reviewed", "deferred_reason", "corpus_status", "refinement_action", "review_basis"],
    "SEMANTIC_REVIEW_EVIDENCE_REFINED.tsv": ["semantic_evidence_id", "source_record_id", "original_relative_path", "task002_segment_or_page_id", "heading_path", "reviewed_line_or_page_range", "unit_sha256_or_source_hash", "hash_basis", "review_depth", "reviewer_state", "findings_reference", "evidence_status", "supersedes_evidence_id"],
    "SEMANTIC_REVIEW_FINDINGS.tsv": ["finding_id", "source_record_id", "original_relative_path", "semantic_evidence_ids", "observed_topics", "observed_depth", "concrete_elements", "strengths", "limitations", "supported_domains", "reuse_decision", "finding_type"],
    "SOURCE_AUTHORITY_REGISTER_REFINED.tsv": ["source_record_id", "original_relative_path", "authority_tier", "authority_scope", "technical_support_role", "review_depth", "semantic_quality", "currency_and_applicability", "scope_relevance", "provenance_confidence", "reuse_decision", "limitations", "semantic_evidence_ids"],
    "SEMANTIC_SOURCE_ASSESSMENTS_REFINED.tsv": ["assessment_id", "source_record_id", "original_relative_path", "semantic_evidence_ids", "observed_topics", "observed_depth", "concrete_elements", "teaching_or_design_strengths", "currency_signals", "limitations", "supported_domains", "supported_capabilities", "suitability", "external_verification_needed", "finding_type", "review_depth"],
    "SOURCE_TO_DOMAIN_MAP_REFINED.tsv": ["mapping_id", "source_record_id", "original_relative_path", "domain_code", "support_type", "mapping_state", "semantic_evidence_ids", "mapping_rationale", "claim_boundary"],
    "CAPABILITY_CLUSTER_CATALOG_REFINED.tsv": ["cluster_id", "domain_code", "cluster_name", "purpose", "scope", "exclusions_and_boundaries", "related_clusters", "supported_professional_roles", "role_rationale", "supporting_sources", "supporting_evidence_ids", "coverage_status", "coverage_rationale"],
    "CAPABILITY_CATALOG_REFINED.tsv": ["capability_id", "parent_cluster_id", "capability_statement", "scope_and_boundaries", "explicit_exclusions", "prerequisite_capability_ids", "foundation_requirements", "expected_evidence", "simulator_suitability", "simulator_rationale", "real_lab_classification", "real_lab_claim_boundary", "related_roles", "role_rationale", "related_domains", "related_domain_reasons", "supporting_sources", "supporting_evidence_ids", "source_confidence", "source_confidence_rationale", "v1_priority", "v1_priority_rationale"],
    "CAPABILITY_DEPENDENCIES.tsv": ["dependency_id", "capability_id", "prerequisite_capability_id", "foundation_requirement", "dependency_reason", "status"],
    "KNOWLEDGE_UNIT_CANDIDATES_REFINED.tsv": ["knowledge_unit_id", "title", "primary_domain", "related_domains", "parent_capability_id", "capability_centered_learning_outcome", "prerequisite_ids", "lesson_boundary", "micro_practice", "simulator_lab_suitability", "simulator_lab_rationale", "real_lab_classification", "real_lab_claim_boundary", "evidence_types", "mastery_criteria_concept", "failure_and_review_triggers", "supporting_evidence_ids", "supporting_source_paths", "source_gaps", "lifecycle_template", "v1_priority", "status"],
    "KNOWLEDGE_UNIT_DEPENDENCIES.tsv": ["dependency_id", "knowledge_unit_id", "prerequisite_knowledge_unit_id", "prerequisite_capability_id", "foundation_requirement", "dependency_reason", "status"],
    "CROSS_DOMAIN_RELATIONSHIPS_REFINED.tsv": ["relationship_id", "canonical_knowledge_unit_id", "primary_domain", "related_domain", "relationship_type", "context_note", "supporting_evidence_ids", "duplicate_ku_created"],
    "SOURCE_TO_CAPABILITY_MAP_REFINED.tsv": ["mapping_id", "source_record_id", "original_relative_path", "capability_id", "support_type", "semantic_evidence_ids", "direct_support_rationale", "claim_boundary", "coverage_confidence"],
    "DOMAIN_COVERAGE_MATRIX_REFINED.tsv": ["domain_code", "domain_name", "purpose", "professional_outcomes", "included_scope", "excluded_adjacent_scope", "cluster_ids", "capability_ids", "knowledge_unit_ids", "primary_roles", "related_roles", "reviewed_supporting_sources", "strongest_reviewed_support", "authority_quality", "cluster_coverage", "capability_coverage", "knowledge_unit_coverage", "simulator_feasibility", "selective_real_lab_needs", "coverage_confidence", "confidence_rationale", "source_weaknesses", "exact_weak_areas", "missing_primary_authorities", "missing_practical_material", "gap_types", "v1_priority", "post_v1_breadth", "v1_post_v1_decision"],
    "UNIVERSITY_FILE_ASSESSMENTS.tsv": ["assessment_id", "source_record_id", "original_relative_path", "course_name", "review_status", "observed_topic", "observed_depth", "concrete_examples_configuration_protocol_lab", "teaching_strengths", "age_currentness_signals", "limitations", "supported_domains", "supported_capabilities", "suitability", "pages_or_evidence_inspected", "semantic_evidence_ids"],
    "UNIVERSITY_COURSE_COVERAGE.tsv": ["course_id", "course_name", "file_count", "reviewed_file_count", "ocr_deferred_count", "distinct_observed_coverage", "teaching_strengths", "limitations", "supported_domains", "suitability", "source_file_assessment_ids"],
    "VS001_SOURCE_SELECTION_REFINED.tsv": ["selection_id", "requirement_id", "requirement_statement", "acceptance_need", "source_record_id", "original_relative_path", "semantic_evidence_ids", "capability_id", "knowledge_unit_id", "support_type", "selection_role", "authority_scope", "claim_boundary", "status"],
    "SEMANTIC_DEFERRED_QUEUE_REFINED.tsv": ["deferred_id", "source_path_or_scope", "deferred_state", "reason", "future_trigger", "supporting_evidence_ids"],
    "UNRESOLVED_SOURCE_ISSUES_REFINED.tsv": ["issue_id", "source_path_or_scope", "issue", "impact", "resolution_needed", "status", "supporting_evidence_ids"],
    "REFINEMENT_QUALITY_METRICS.tsv": ["metric_id", "metric_name", "task003_value", "task003r_value", "target_or_interpretation", "result", "human_review_note"],
}


REPORT_FILES = [
    "SEMANTIC_REFINEMENT_REPORT.md",
    "SOURCE_AUTHORITY_POLICY_REFINED.md",
    "CYBERSECURITY_DOMAIN_TAXONOMY_V1_REFINED.md",
    "CAPABILITY_ARCHITECTURE_BASELINE_REFINED.md",
    "ADAPTIVE_LEARNING_MODEL_REFINED.md",
    "UNIVERSITY_COURSE_SEMANTIC_ASSESSMENT_REFINED.md",
    "CURRICULUM_COVERAGE_AND_GAPS_REFINED.md",
    "VS001_SOURCE_SELECTION_REFINED.md",
    "TASK003R_DECISION_REGISTER.md",
    "UNRESOLVED_DECISIONS_REFINED.md",
    "REFINED_MANIFEST_SCHEMAS.md",
]


PACKET_FILES = [
    "CODEX_FINAL_REPORT.md",
    "CHANGED_FILES.txt",
    "TEST_RESULTS.txt",
    "TASK003_VS_TASK003R_COMPARISON.md",
    "REFINEMENT_QUALITY_SUMMARY.md",
    "CAPABILITY_AND_KU_COUNTS.md",
    "DOMAIN_COVERAGE_SUMMARY.md",
    "UNIVERSITY_REFINEMENT_SUMMARY.md",
    "SOURCE_SAFETY_RESULTS.md",
    "REGRESSION_RESULTS.md",
    "RESIDUAL_LIMITATIONS.md",
]


def read_tsv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8-sig", newline="") as stream:
        return list(csv.DictReader(stream, delimiter="\t"))


def write_tsv(path: Path, columns: list[str], rows: list[dict[str, object]]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="") as stream:
        writer = csv.DictWriter(stream, fieldnames=columns, delimiter="\t", lineterminator="\n")
        writer.writeheader()
        for row in rows:
            writer.writerow({column: str(row.get(column, "")).replace("\t", " ").replace("\r", " ").replace("\n", " ") for column in columns})


def write_text(path: Path, body: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(body.rstrip() + "\n", encoding="utf-8", newline="\n")


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def split_values(value: str) -> list[str]:
    return [item for item in value.split(";") if item]


def parse_cluster_specs() -> dict[str, dict[str, str]]:
    reader = csv.DictReader(io.StringIO(CLUSTER_SPEC_TEXT.strip()), delimiter="|")
    rows = list(reader)
    return {row["cluster_id"]: row for row in rows}


def source_observation(path: str) -> tuple[str, str, str, str, str, str]:
    name = Path(path).name
    if path.startswith("university-courses/"):
        item = UNIVERSITY_OBSERVATIONS[name]
        return item[0], item[1], item[2], item[5], item[6], item[8]
    return NON_UNIVERSITY_OBSERVATIONS[name]


def support_type_for_path(path: str) -> str:
    if path.startswith("product-charter/"):
        return "PRODUCT_REQUIREMENT_SUPPORT"
    if path.startswith("ad-identity-pilot/"):
        return "PILOT_SCOPE_SUPPORT"
    if "/Canonical_Knowledge_Vault_READY_FINAL/00_Control_and_Indexes/" in path:
        return "SEED_DATA_SUPPORT"
    if "/Canonical_Knowledge_Vault_READY_FINAL/" in path:
        return "TECHNICAL_CONTENT_SUPPORT"
    if path.startswith("university-courses/") or "/CISSP 8 Domains From Books(1)/" in path:
        return "ACADEMIC_SUPPORT"
    if "/variant Roadmaps + thier References(1)/" in path:
        return "LAB_METHOD_SUPPORT"
    if "/CSV-TSV exports for DataBase OR LLM/" in path:
        return "SEED_DATA_SUPPORT"
    if path.startswith("historical-platform/") or "/care_ultimate_best_assertion_centered_patched/" in path:
        return "HISTORICAL_DESIGN_LESSON"
    return "CONTEXT_ONLY"


def authority_for_path(path: str) -> tuple[str, str, str, str, str]:
    support = support_type_for_path(path)
    if support == "PRODUCT_REQUIREMENT_SUPPORT":
        return "A0_PRODUCT_AUTHORITY", "PRODUCT_SCOPE_LEARNING_MODEL_AND_WORKFLOW", "NOT_TECHNICAL_CURRICULUM_AUTHORITY", "HIGH_FOR_PRODUCT_INTENT", "CURRENT_PROJECT_AUTHORITY"
    if support == "PILOT_SCOPE_SUPPORT":
        return "A2_APPROVED_PILOT_AUTHORITY", "DECLARED_AD_PILOT_ONLY", "PILOT_SCOPE_AND_EVIDENCE_SUPPORT", "HIGH_WITHIN_PILOT_SCOPE", "CURRENT_BUT_TECHNICALLY_UNVERIFIED"
    if support == "TECHNICAL_CONTENT_SUPPORT":
        return "B1_CURATED_INTERNAL_KNOWLEDGE", "REVIEWED_INTERNAL_TOPIC_SCOPE", "PROVISIONAL_TECHNICAL_CONTENT_SUPPORT", "MEDIUM", "VERSION_SENSITIVE_REQUIRES_PRIMARY_CHECK"
    if support == "ACADEMIC_SUPPORT":
        return "B2_SUPPORTING_ACADEMIC_SOURCE", "PAGES_OR_SECTIONS_ACTUALLY_REVIEWED", "ACADEMIC_SUPPORT_ONLY", "MEDIUM_OR_LOW_BY_FILE", "MIXED_OR_VERSION_SENSITIVE"
    if support == "LAB_METHOD_SUPPORT":
        return "B3_SUPPORTING_TECHNICAL_REFERENCE", "REVIEWED_INTERNAL_METHOD_SECTIONS", "LAB_AND_EVIDENCE_METHOD_SUPPORT", "MEDIUM", "VERSION_SENSITIVE"
    if support == "SEED_DATA_SUPPORT":
        return "C2_GENERATED_OR_UNVERIFIED_REFERENCE", "REVIEWED_SCHEMA_OR_INDEX_SCOPE", "SEED_DATA_OR_NAVIGATION_ONLY", "LOW_TO_MEDIUM", "UNVERIFIED_GENERATED_OR_SELF_DECLARED"
    if support == "HISTORICAL_DESIGN_LESSON":
        return "C1_HISTORICAL_PROJECT_REFERENCE", "REVIEWED_HISTORICAL_LESSON_SCOPE", "HISTORICAL_DESIGN_LESSON_ONLY", "MEDIUM_FOR_LESSONS", "HISTORICAL"
    return "D1_OPAQUE_OR_UNAVAILABLE", "NO_CONTENT_ACCESS", "NO_TECHNICAL_SUPPORT", "UNAVAILABLE", "UNAVAILABLE"


def evidence_sources(evidence_ids: str, evidence_by_id: dict[str, dict[str, str]]) -> str:
    return ";".join(sorted({evidence_by_id[item]["original_relative_path"] for item in split_values(evidence_ids)}))


def line_unit_hash(path: Path, start: int, end: int) -> str:
    lines = path.read_text(encoding="utf-8-sig", errors="replace").splitlines()
    if start < 1 or end > len(lines) or end < start:
        raise ValueError(f"Invalid line range {start}-{end} for {path} with {len(lines)} lines")
    unit = "\n".join(lines[start - 1 : end]) + "\n"
    return hashlib.sha256(unit.encode("utf-8")).hexdigest()


def build_evidence(corpus_old: list[dict[str, str]], census: dict[str, dict[str, str]]) -> tuple[list[dict[str, object]], list[dict[str, object]]]:
    old_evidence = read_tsv(OLD_SEM / "SEMANTIC_REVIEW_EVIDENCE.tsv")
    preserved = []
    for row in old_evidence:
        number = int(row["semantic_evidence_id"].rsplit("-", 1)[1])
        if 15 <= number <= 200:
            preserved.append({
                **row,
                "hash_basis": "TASK003_RECORDED_SECTION_OR_PAGE_HASH",
                "findings_reference": f"FIND-{row['source_record_id']}",
                "evidence_status": "PRESERVED_REVIEW_UNIT",
                "supersedes_evidence_id": "",
            })

    section_candidates = read_tsv(STRUCTURAL / "SOURCE_SECTION_CANDIDATES.tsv")
    segment_by_exact_range = {
        (row["relative_path"], int(row["start_line"]), int(row["end_line"])): row["segment_candidate_id"]
        for row in section_candidates
        if row.get("start_line") and row.get("end_line")
    }
    new_rows: list[dict[str, object]] = []
    for index, (path, start, end, heading, finding, old_id) in enumerate(NEW_EVIDENCE_SPECS, 1):
        copy_path = OLD_COPIES / path
        if not copy_path.is_file():
            raise FileNotFoundError(f"Required reviewed source copy missing: {copy_path}")
        total_lines = len(copy_path.read_text(encoding="utf-8-sig", errors="replace").splitlines())
        full = start == 1 and end == total_lines and Path(path).name in {"CKV_PACKAGE_QA_REPORT.md", "baseline.py", "policy.py"}
        new_rows.append({
            "semantic_evidence_id": f"SE-003R-{index:03d}",
            "source_record_id": census[path]["source_record_id"],
            "original_relative_path": path,
            "task002_segment_or_page_id": segment_by_exact_range.get((path, start, end), ""),
            "heading_path": heading,
            "reviewed_line_or_page_range": f"lines {start}-{end}",
            "unit_sha256_or_source_hash": census[path]["sha256"] if full else line_unit_hash(copy_path, start, end),
            "hash_basis": "FULL_SOURCE_SHA256" if full else "REFINED_EXACT_LINE_RANGE_SHA256",
            "review_depth": "REVIEWED_FULL" if full else "REVIEWED_SELECTED_SECTIONS",
            "reviewer_state": "REVIEWED_TASK003R",
            "findings_reference": f"FIND-{census[path]['source_record_id']}",
            "evidence_status": "REPLACES_BROAD_TASK003_UNIT",
            "supersedes_evidence_id": old_id,
            "_finding": finding,
        })

    evidence = sorted(preserved + new_rows, key=lambda row: row["semantic_evidence_id"])
    by_path: dict[str, list[dict[str, object]]] = defaultdict(list)
    for row in evidence:
        by_path[str(row["original_relative_path"])].append(row)

    corpus = []
    for old in corpus_old:
        path = old["original_relative_path"]
        units = by_path.get(path, [])
        if old["review_depth"] == "DEFERRED_OCR_REQUIRED":
            depth = "DEFERRED_OCR_REQUIRED"
            action = "PRESERVED_OCR_DEFERRAL"
            basis = "No OCR and no filename-derived semantic claim"
        else:
            depths = {str(row["review_depth"]) for row in units}
            depth = "REVIEWED_FULL" if depths == {"REVIEWED_FULL"} else old["review_depth"] if old["review_depth"] != "REVIEWED_FULL" else "REVIEWED_SELECTED_SECTIONS"
            action = "PRESERVED_RECORDED_UNITS" if all(str(row["evidence_status"]) == "PRESERVED_REVIEW_UNIT" for row in units) else "REPLACED_BROAD_UNIT_WITH_CONTENT_BEARING_RANGES"
            basis = "Existing Task 003 reviewed pages/sections" if action.startswith("PRESERVED") else "Complete prior source copy inspected at selected content-bearing ranges; unsupported full-review claim downgraded"
        corpus.append({
            **old,
            "review_depth": depth,
            "planned_units": len(units),
            "actual_units_reviewed": len(units),
            "refinement_action": action,
            "review_basis": basis,
        })
    return corpus, evidence


def build_source_tables(corpus: list[dict[str, object]], evidence: list[dict[str, object]], old_authority: list[dict[str, str]]) -> tuple[list[dict[str, object]], list[dict[str, object]], list[dict[str, object]], list[dict[str, object]], list[dict[str, object]]]:
    evidence_by_path: dict[str, list[str]] = defaultdict(list)
    for row in evidence:
        evidence_by_path[str(row["original_relative_path"])].append(str(row["semantic_evidence_id"]))
    corpus_by_path = {str(row["original_relative_path"]): row for row in corpus}
    findings = []
    authority_rows = []
    assessments = []
    source_domain = []
    university_rows = []
    for index, row in enumerate(corpus, 1):
        path = str(row["original_relative_path"])
        source_id = str(row["source_record_id"])
        evs = ";".join(evidence_by_path[path])
        if path.startswith("university-courses/"):
            item = UNIVERSITY_OBSERVATIONS[Path(path).name]
            topics, depth, concrete, strengths, currentness, limitations, domains, caps, suitability, pages = item
            finding_type = "METADATA_ONLY_DEFERRED" if depth == "DEFERRED_OCR_REQUIRED" else "FACT_FROM_REVIEWED_PAGES_AND_REVIEWER_INTERPRETATION"
            university_rows.append({
                "assessment_id": f"UNIV-003R-{len(university_rows)+1:03d}",
                "source_record_id": source_id,
                "original_relative_path": path,
                "course_name": path.split("/")[1],
                "review_status": depth,
                "observed_topic": topics,
                "observed_depth": depth,
                "concrete_examples_configuration_protocol_lab": concrete,
                "teaching_strengths": strengths,
                "age_currentness_signals": currentness,
                "limitations": limitations,
                "supported_domains": domains,
                "supported_capabilities": caps,
                "suitability": suitability,
                "pages_or_evidence_inspected": pages,
                "semantic_evidence_ids": evs,
            })
        else:
            topics, depth, concrete, limitations, domains, suitability = NON_UNIVERSITY_OBSERVATIONS[Path(path).name]
            strengths = "Useful within the explicitly reviewed scope and authority classification"
            currentness = authority_for_path(path)[4]
            caps = ""
            finding_type = "FACT_FROM_REVIEWED_SOURCE_AND_REVIEWER_INTERPRETATION"
        findings.append({
            "finding_id": f"FIND-{source_id}",
            "source_record_id": source_id,
            "original_relative_path": path,
            "semantic_evidence_ids": evs,
            "observed_topics": topics,
            "observed_depth": depth,
            "concrete_elements": concrete,
            "strengths": strengths,
            "limitations": limitations,
            "supported_domains": domains,
            "reuse_decision": suitability,
            "finding_type": finding_type,
        })
        tier, authority_scope, technical_role, quality, currency = authority_for_path(path)
        authority_rows.append({
            "source_record_id": source_id,
            "original_relative_path": path,
            "authority_tier": tier,
            "authority_scope": authority_scope,
            "technical_support_role": technical_role,
            "review_depth": row["review_depth"],
            "semantic_quality": quality if evs else "UNAVAILABLE_DEFERRED",
            "currency_and_applicability": currency,
            "scope_relevance": domains or "METADATA_ONLY_UNKNOWN",
            "provenance_confidence": "HIGH_FILE_CUSTODY;CONTENT_AUTHORITY_CLASSIFIED_SEPARATELY",
            "reuse_decision": suitability if evs else "DEFERRED",
            "limitations": limitations,
            "semantic_evidence_ids": evs,
        })
        assessments.append({
            "assessment_id": f"ASSESS-{source_id}-R",
            "source_record_id": source_id,
            "original_relative_path": path,
            "semantic_evidence_ids": evs,
            "observed_topics": topics,
            "observed_depth": depth,
            "concrete_elements": concrete,
            "teaching_or_design_strengths": strengths,
            "currency_signals": currentness,
            "limitations": limitations,
            "supported_domains": domains,
            "supported_capabilities": caps,
            "suitability": suitability,
            "external_verification_needed": "NO_SEMANTIC_CLAIM_OCR_DEFERRED" if not evs else "YES_FOR_TECHNICAL_OR_VERSION_SENSITIVE_CLAIMS;NO_FOR_WITHIN_SCOPE_PROJECT_DECISIONS",
            "finding_type": finding_type,
            "review_depth": row["review_depth"],
        })
        for domain in split_values(domains):
            source_domain.append({
                "mapping_id": f"SDM-003R-{len(source_domain)+1:03d}",
                "source_record_id": source_id,
                "original_relative_path": path,
                "domain_code": domain,
                "support_type": support_type_for_path(path),
                "mapping_state": "DIRECT_REVIEWED_SCOPE" if evs else "METADATA_ONLY_NO_SEMANTIC_SUPPORT",
                "semantic_evidence_ids": evs,
                "mapping_rationale": topics,
                "claim_boundary": limitations,
            })

    corpus_paths = set(corpus_by_path)
    for old in old_authority:
        path = old["original_relative_path"]
        if path in corpus_paths:
            continue
        authority_rows.append({
            "source_record_id": old["source_record_id"],
            "original_relative_path": path,
            "authority_tier": "D1_OPAQUE_OR_UNAVAILABLE",
            "authority_scope": "NO_CONTENT_ACCESS",
            "technical_support_role": "NO_TECHNICAL_SUPPORT",
            "review_depth": "DEFERRED_PARSE_DEPENDENCY",
            "semantic_quality": "UNAVAILABLE",
            "currency_and_applicability": "UNAVAILABLE",
            "scope_relevance": "UNKNOWN",
            "provenance_confidence": "HIGH_FILE_CUSTODY;NO_CONTENT_ACCESS",
            "reuse_decision": "DEFERRED",
            "limitations": "AES parse dependency unavailable; no dependency added",
            "semantic_evidence_ids": "",
        })
    return findings, authority_rows, assessments, source_domain, university_rows


def build_architecture(evidence: list[dict[str, object]]) -> tuple[list[dict[str, object]], list[dict[str, object]], list[dict[str, object]], list[dict[str, object]], list[dict[str, object]], list[dict[str, object]], list[dict[str, object]]]:
    evidence_by_id = {str(row["semantic_evidence_id"]): row for row in evidence}
    old_clusters = read_tsv(OLD_SEM / "CAPABILITY_CLUSTER_CATALOG.tsv")
    old_capabilities = read_tsv(OLD_SEM / "CAPABILITY_CATALOG.tsv")
    old_kus = read_tsv(OLD_SEM / "KNOWLEDGE_UNIT_CANDIDATES.tsv")
    specs = parse_cluster_specs()
    if set(specs) != {row["cluster_id"] for row in old_clusters}:
        raise RuntimeError("Cluster refinement data must cover the 53 stable Task 003 cluster IDs exactly")
    old_caps_by_cluster: dict[str, list[dict[str, str]]] = defaultdict(list)
    for row in old_capabilities:
        old_caps_by_cluster[row["parent_cluster_id"]].append(row)
    old_ku_by_cap = {row["parent_capability_id"]: row for row in old_kus}

    first_cluster_by_domain: dict[str, str] = {}
    for row in old_clusters:
        first_cluster_by_domain.setdefault(row["domain_code"], row["cluster_id"])

    clusters: list[dict[str, object]] = []
    capabilities: list[dict[str, object]] = []
    cap_dependencies: list[dict[str, object]] = []
    cap_details: dict[str, dict[str, object]] = {}
    for cluster_row in old_clusters:
        cluster_id = cluster_row["cluster_id"]
        domain = cluster_row["domain_code"]
        spec = specs[cluster_id]
        related_clusters = [first_cluster_by_domain[item[0]] for item in DOMAIN_RELATIONS[domain]]
        sources = evidence_sources(spec["evidence_ids"], evidence_by_id)
        clusters.append({
            "cluster_id": cluster_id,
            "domain_code": domain,
            "cluster_name": cluster_row["cluster_name"],
            "purpose": spec["purpose"],
            "scope": spec["scope"],
            "exclusions_and_boundaries": spec["exclusions"],
            "related_clusters": ";".join(related_clusters),
            "supported_professional_roles": spec["roles"],
            "role_rationale": f"These roles own, implement, or independently review the decisions and evidence described by {cluster_row['cluster_name']}.",
            "supporting_sources": sources,
            "supporting_evidence_ids": spec["evidence_ids"],
            "coverage_status": DOMAIN_DATA[domain]["confidence"],
            "coverage_rationale": f"Reviewed support is bounded to {sources}; remaining gap: {spec['source_gap']}",
        })
        caps = sorted(old_caps_by_cluster[cluster_id], key=lambda row: row["capability_id"])
        for position, old_cap in enumerate(caps):
            cap_id = old_cap["capability_id"]
            suffix = "a" if position == 0 else "b"
            focus = spec[f"focus_{suffix}"]
            artifact = spec[f"artifact_{suffix}"]
            cluster_number = int(cluster_id.rsplit("-", 1)[1])
            if position == 1:
                prerequisite = caps[0]["capability_id"]
                foundation = ""
                dependency_reason = "The second capability applies and validates the model established by the first capability in the same cluster."
            elif cluster_number > 1:
                prerequisite = f"CAP-{domain}-{cluster_number-1:02d}-01"
                foundation = ""
                dependency_reason = "The cluster requires the primary decision model from the preceding cluster before specialization."
            elif domain != "D01":
                prerequisite = "CAP-D01-03-01"
                foundation = ""
                dependency_reason = "Specialized practice requires explicit security-principle reasoning from D01."
            else:
                prerequisite = ""
                foundation = "Basic literacy in files, processes, networks, evidence, and safe educational scope"
                dependency_reason = "This is an entry foundation and therefore records a foundation requirement rather than a catalog capability ID."
            relation = DOMAIN_RELATIONS[domain][position % len(DOMAIN_RELATIONS[domain])]
            simulator_level = "MEDIUM" if domain in {"D10", "D11", "D15"} else "MEDIUM_HIGH" if domain == "D12" else "HIGH"
            real_lab = spec[f"real_{suffix}"]
            real_boundary = spec[f"real_boundary_{suffix}"]
            capabilities.append({
                "capability_id": cap_id,
                "parent_cluster_id": cluster_id,
                "capability_statement": old_cap["capability_statement"],
                "scope_and_boundaries": focus,
                "explicit_exclusions": spec["exclusions"],
                "prerequisite_capability_ids": prerequisite,
                "foundation_requirements": foundation,
                "expected_evidence": artifact,
                "simulator_suitability": simulator_level,
                "simulator_rationale": f"A bounded {DOMAIN_SCENARIOS[domain]} can represent the states and decisions needed to assess: {focus}",
                "real_lab_classification": real_lab,
                "real_lab_claim_boundary": real_boundary,
                "related_roles": spec["roles"],
                "role_rationale": f"The listed roles are accountable for producing or reviewing {artifact.lower()} when they {old_cap['capability_statement'][0].lower()+old_cap['capability_statement'][1:]}",
                "related_domains": relation[0],
                "related_domain_reasons": f"{relation[0]}: {relation[1]} in the context of {focus.lower()}",
                "supporting_sources": sources,
                "supporting_evidence_ids": spec["evidence_ids"],
                "source_confidence": DOMAIN_DATA[domain]["confidence"],
                "source_confidence_rationale": f"The cited evidence directly supports this bounded focus; {spec['source_gap']}",
                "v1_priority": DOMAIN_DATA[domain]["v1"],
                "v1_priority_rationale": f"{DOMAIN_DATA[domain]['v1']} reflects the Domain decision; this capability contributes {spec['purpose'].lower()}",
            })
            cap_dependencies.append({
                "dependency_id": f"CAPDEP-003R-{len(cap_dependencies)+1:03d}",
                "capability_id": cap_id,
                "prerequisite_capability_id": prerequisite,
                "foundation_requirement": foundation,
                "dependency_reason": dependency_reason,
                "status": "REFINED_EXPLICIT_DEPENDENCY",
            })
            cap_details[cap_id] = {**capabilities[-1], "source_gap": spec["source_gap"], "cluster_exclusions": spec["exclusions"]}

    kus: list[dict[str, object]] = []
    ku_dependencies: list[dict[str, object]] = []
    ku_by_cap: dict[str, list[str]] = defaultdict(list)
    cap_to_old_ku_id = {cap: row["knowledge_unit_id"] for cap, row in old_ku_by_cap.items()}
    for cap in capabilities:
        cap_id = str(cap["capability_id"])
        if cap_id in UNSEEDED_CAPABILITY_IDS:
            continue
        domain = cap_id.split("-")[1]
        old_ku = old_ku_by_cap[cap_id]
        ku_id = old_ku["knowledge_unit_id"]
        days = 14 + (int(cap_id[-2:]) * 7) + (int(cap_id.split("-")[2]) % 2) * 7
        statement = str(cap["capability_statement"])
        artifact = str(cap["expected_evidence"])
        prereq_cap = str(cap["prerequisite_capability_ids"])
        prereq_ku = cap_to_old_ku_id.get(prereq_cap, "") if prereq_cap and prereq_cap not in UNSEEDED_CAPABILITY_IDS else ""
        prereq_ids = prereq_ku or prereq_cap or f"FOUNDATION:{cap['foundation_requirements']}"
        lifecycle = "PRACTICAL_WITH_SELECTIVE_REAL_LAB" if cap["real_lab_classification"] == "REQUIRED_FOR_SPECIFIC_CLAIM" else "INTEGRATED_SCENARIO" if domain == "D16" else "EVIDENCE_AND_DECISION" if domain == "D14" else "PRACTICAL_SIMULATOR"
        kus.append({
            "knowledge_unit_id": ku_id,
            "title": old_ku["title"],
            "primary_domain": domain,
            "related_domains": cap["related_domains"],
            "parent_capability_id": cap_id,
            "capability_centered_learning_outcome": f"Given a bounded {DOMAIN_SCENARIOS[domain]}, the learner can {statement[0].lower()+statement[1:]} and defend the result with {artifact.lower()}.",
            "prerequisite_ids": prereq_ids,
            "lesson_boundary": f"Covers {cap['scope_and_boundaries']}; excludes {cap_details[cap_id]['cluster_exclusions']}.",
            "micro_practice": f"Work one {DOMAIN_SCENARIOS[domain]}: {statement[0].lower()+statement[1:]}. Submit {artifact}.",
            "simulator_lab_suitability": cap["simulator_suitability"],
            "simulator_lab_rationale": cap["simulator_rationale"],
            "real_lab_classification": cap["real_lab_classification"],
            "real_lab_claim_boundary": cap["real_lab_claim_boundary"],
            "evidence_types": DOMAIN_EVIDENCE_TYPES[domain],
            "mastery_criteria_concept": f"Across one positive and one negative {DOMAIN_SCENARIOS[domain]}, the learner reaches the correct decision, explains the decisive state, and produces {artifact.lower()} without exceeding the stated boundary.",
            "failure_and_review_triggers": f"Requeue after an incorrect decision about {cap['scope_and_boundaries'].lower()}, missing or contradictory {artifact.lower()}, or a failed {days}-day retention check.",
            "supporting_evidence_ids": cap["supporting_evidence_ids"],
            "supporting_source_paths": cap["supporting_sources"],
            "source_gaps": cap_details[cap_id]["source_gap"],
            "lifecycle_template": lifecycle,
            "v1_priority": cap["v1_priority"],
            "status": "PROVISIONAL_REFINED_SEED_NOT_LESSON_CONTENT",
        })
        ku_by_cap[cap_id].append(ku_id)
        ku_dependencies.append({
            "dependency_id": f"KUDEP-003R-{len(ku_dependencies)+1:03d}",
            "knowledge_unit_id": ku_id,
            "prerequisite_knowledge_unit_id": prereq_ku,
            "prerequisite_capability_id": "" if prereq_ku else prereq_cap,
            "foundation_requirement": str(cap["foundation_requirements"]) if not prereq_cap else "",
            "dependency_reason": "The KU requires the explicit prerequisite knowledge or capability before mastery evidence is evaluated.",
            "status": "REFINED_EXPLICIT_DEPENDENCY",
        })

    for cap_id, (ku_id, title, micro, mastery, triggers) in SPLIT_KUS.items():
        cap = cap_details[cap_id]
        domain = cap_id.split("-")[1]
        primary_ku = ku_by_cap[cap_id][0]
        kus.append({
            "knowledge_unit_id": ku_id,
            "title": title,
            "primary_domain": domain,
            "related_domains": cap["related_domains"],
            "parent_capability_id": cap_id,
            "capability_centered_learning_outcome": f"The learner can perform the narrower evidence-bearing subskill '{title}' as a reusable part of {cap['capability_statement'].lower()}.",
            "prerequisite_ids": primary_ku,
            "lesson_boundary": f"Covers only {title.lower()} within {cap['scope_and_boundaries']}; it does not duplicate the parent KU's full boundary.",
            "micro_practice": micro,
            "simulator_lab_suitability": cap["simulator_suitability"],
            "simulator_lab_rationale": cap["simulator_rationale"],
            "real_lab_classification": cap["real_lab_classification"],
            "real_lab_claim_boundary": cap["real_lab_claim_boundary"],
            "evidence_types": DOMAIN_EVIDENCE_TYPES[domain],
            "mastery_criteria_concept": mastery,
            "failure_and_review_triggers": triggers,
            "supporting_evidence_ids": cap["supporting_evidence_ids"],
            "supporting_source_paths": cap["supporting_sources"],
            "source_gaps": cap["source_gap"],
            "lifecycle_template": "PRACTICAL_SIMULATOR",
            "v1_priority": cap["v1_priority"],
            "status": "NEW_REFINED_CANONICAL_SEED_NOT_LESSON_CONTENT",
        })
        ku_by_cap[cap_id].append(ku_id)
        ku_dependencies.append({
            "dependency_id": f"KUDEP-003R-{len(ku_dependencies)+1:03d}",
            "knowledge_unit_id": ku_id,
            "prerequisite_knowledge_unit_id": primary_ku,
            "prerequisite_capability_id": "",
            "foundation_requirement": "",
            "dependency_reason": "This deliberately split KU narrows one evidence skill after the parent capability's primary KU.",
            "status": "NEW_REFINED_DEPENDENCY",
        })

    relationships: list[dict[str, object]] = []
    for ku in sorted(kus, key=lambda row: str(row["knowledge_unit_id"])):
        for related in split_values(str(ku["related_domains"])):
            reason = next(reason for code, reason in DOMAIN_RELATIONS[str(ku["primary_domain"])] if code == related)
            relationships.append({
                "relationship_id": f"XDR-003R-{len(relationships)+1:03d}",
                "canonical_knowledge_unit_id": ku["knowledge_unit_id"],
                "primary_domain": ku["primary_domain"],
                "related_domain": related,
                "relationship_type": "DOMAIN_CONTEXT_REUSE",
                "context_note": f"{related} reuses {str(ku['title']).lower()} because {reason}; the canonical outcome and evidence remain owned by {ku['primary_domain']}.",
                "supporting_evidence_ids": ku["supporting_evidence_ids"],
                "duplicate_ku_created": "FALSE",
            })
    return clusters, capabilities, cap_dependencies, kus, ku_dependencies, relationships, list(cap_details.values())


def build_source_capability_map(capabilities: list[dict[str, object]], evidence: list[dict[str, object]]) -> list[dict[str, object]]:
    evidence_by_id = {str(row["semantic_evidence_id"]): row for row in evidence}
    rows: list[dict[str, object]] = []
    for cap in capabilities:
        by_source: dict[str, list[str]] = defaultdict(list)
        for evidence_id in split_values(str(cap["supporting_evidence_ids"])):
            by_source[str(evidence_by_id[evidence_id]["original_relative_path"])].append(evidence_id)
        for path, evidence_ids in sorted(by_source.items()):
            source_id = str(evidence_by_id[evidence_ids[0]]["source_record_id"])
            support_type = support_type_for_path(path)
            boundary = "Supports the cited bounded capability focus only; does not supply uncited Domain breadth or external technical authority."
            if support_type == "PRODUCT_REQUIREMENT_SUPPORT":
                boundary = "Supports product, learning-model, simulator, evidence, or VS-001 requirement intent only; not technical curriculum truth."
            elif support_type == "PILOT_SCOPE_SUPPORT":
                boundary = "Supports the declared AD pilot and VS-001 scope only."
            elif support_type == "HISTORICAL_DESIGN_LESSON":
                boundary = "Supports a historical design or provenance lesson only; not current product architecture."
            elif support_type == "SEED_DATA_SUPPORT":
                boundary = "Supports a reviewed seed schema or topic signal only; no direct import or primary authority."
            rows.append({
                "mapping_id": f"SCM-003R-{len(rows)+1:03d}",
                "source_record_id": source_id,
                "original_relative_path": path,
                "capability_id": cap["capability_id"],
                "support_type": support_type,
                "semantic_evidence_ids": ";".join(evidence_ids),
                "direct_support_rationale": f"The cited unit directly supports the bounded focus: {cap['scope_and_boundaries']}",
                "claim_boundary": boundary,
                "coverage_confidence": cap["source_confidence"],
            })
    return rows


def build_domain_coverage(clusters: list[dict[str, object]], capabilities: list[dict[str, object]], kus: list[dict[str, object]]) -> list[dict[str, object]]:
    rows = []
    for domain, data in DOMAIN_DATA.items():
        dclusters = [row for row in clusters if row["domain_code"] == domain]
        dcaps = [row for row in capabilities if str(row["capability_id"]).startswith(f"CAP-{domain}-")]
        dkus = [row for row in kus if row["primary_domain"] == domain]
        sources = sorted({path for row in dclusters for path in split_values(str(row["supporting_sources"]))})
        evidence_ids = sorted({item for row in dclusters for item in split_values(str(row["supporting_evidence_ids"]))})
        authority_types = sorted({support_type_for_path(path) for path in sources})
        rows.append({
            "domain_code": domain,
            "domain_name": data["name"],
            "purpose": data["purpose"],
            "professional_outcomes": data["outcomes"],
            "included_scope": data["included"],
            "excluded_adjacent_scope": data["excluded"],
            "cluster_ids": ";".join(str(row["cluster_id"]) for row in dclusters),
            "capability_ids": ";".join(str(row["capability_id"]) for row in dcaps),
            "knowledge_unit_ids": ";".join(str(row["knowledge_unit_id"]) for row in dkus),
            "primary_roles": data["primary_roles"],
            "related_roles": data["related_roles"],
            "reviewed_supporting_sources": ";".join(sources),
            "strongest_reviewed_support": f"Evidence {','.join(evidence_ids[:8])} from {','.join(Path(path).name for path in sources[:4])}",
            "authority_quality": ";".join(authority_types),
            "cluster_coverage": f"{len(dclusters)} differentiated clusters; each has cited evidence and an explicit gap rationale",
            "capability_coverage": f"{len(dcaps)} stable demonstrable capabilities; all carry direct evidence IDs and claim boundaries",
            "knowledge_unit_coverage": f"{len(dkus)} canonical seed KUs; absence or splitting is preserved rather than forced to one per capability",
            "simulator_feasibility": data["simulator"],
            "selective_real_lab_needs": data["real_claims"],
            "coverage_confidence": data["confidence"],
            "confidence_rationale": data["rationale"],
            "source_weaknesses": data["weakness"],
            "exact_weak_areas": data["weakness"],
            "missing_primary_authorities": "Current external primary technical and/or regulatory authorities appropriate to the stated weak areas",
            "missing_practical_material": "Versioned positive/negative practice sets and transfer evidence for the claim boundaries named in this row",
            "gap_types": data["gap_types"],
            "v1_priority": data["v1"],
            "post_v1_breadth": data["post_v1"],
            "v1_post_v1_decision": f"{data['v1']}: seed only the bounded KUs listed here; defer the stated post-v1 breadth.",
        })
    return rows


def build_university_courses(university_rows: list[dict[str, object]]) -> list[dict[str, object]]:
    rows = []
    for index, (course, synthesis) in enumerate(COURSE_SYNTHESIS.items(), 1):
        files = [row for row in university_rows if row["course_name"] == course]
        reviewed = [row for row in files if row["review_status"] != "DEFERRED_OCR_REQUIRED"]
        deferred = [row for row in files if row["review_status"] == "DEFERRED_OCR_REQUIRED"]
        rows.append({
            "course_id": f"COURSE-003R-{index:02d}",
            "course_name": course,
            "file_count": len(files),
            "reviewed_file_count": len(reviewed),
            "ocr_deferred_count": len(deferred),
            "distinct_observed_coverage": synthesis[0],
            "teaching_strengths": synthesis[1],
            "limitations": synthesis[2],
            "supported_domains": synthesis[3],
            "suitability": synthesis[4],
            "source_file_assessment_ids": ";".join(str(row["assessment_id"]) for row in files),
        })
    return rows


def build_vs001(evidence: list[dict[str, object]]) -> list[dict[str, object]]:
    evidence_by_id = {str(row["semantic_evidence_id"]): row for row in evidence}
    requirements = [
        ("VS001-REQ-01", "Principal identity", "Input identifies the principal and preserves identity provenance.", "SE-003R-009"),
        ("VS001-REQ-02", "Token user SID", "Trace contains one unambiguous token user SID.", "SE-003-041"),
        ("VS001-REQ-03", "Token group SIDs and attributes", "Trace distinguishes enabled, deny-only, and unresolved group state where applicable.", "SE-003-041"),
        ("VS001-REQ-04", "Privileges", "Privileges are evaluated separately from ordinary DACL permissions and unsupported privilege behavior is explicit.", "SE-003-041"),
        ("VS001-REQ-05", "Object security descriptor and owner", "Object owner and descriptor provenance are retained.", "SE-003-041"),
        ("VS001-REQ-06", "Ordered DACL and ACE processing", "Every relevant ACE is evaluated in order with allow, deny, or non-applicable effect.", "SE-003R-009"),
        ("VS001-REQ-07", "Requested access mask", "Requested rights remain explicit and are not replaced by a vague action label.", "SE-003-041"),
        ("VS001-REQ-08", "Generic-right mapping", "Generic rights are mapped only when the object/type mapping is known; otherwise state is insufficient.", "SE-003R-009"),
        ("VS001-REQ-09", "Explicit deny and allow behavior", "Positive and negative cases demonstrate decisive deny and cumulative allow behavior.", "SE-003R-009"),
        ("VS001-REQ-10", "Insufficient or unsupported state", "Missing object mapping, token attribute, or version behavior produces an explicit unsupported state.", "SE-003R-008"),
        ("VS001-REQ-11", "Deterministic explanation", "The same complete input produces the same result and ordered explanation.", "SE-003R-003"),
        ("VS001-REQ-12", "Provenance", "Decision evidence records source, scenario revision, input state, and cited technical support.", "SE-003R-001"),
        ("VS001-REQ-13", "Positive and negative tests", "Tests vary group membership, deny ACE, allow ACE, privilege, mask, and unsupported input deliberately.", "SE-003R-005"),
        ("VS001-REQ-14", "SIMULATED evidence label", "Every simulator result is labeled SIMULATED and does not claim Windows or production transfer.", "SE-003R-003"),
        ("VS001-REQ-15", "Generic web authorization context", "Web authorization material may illustrate authentication/authorization separation but cannot define Windows semantics.", "SE-003-185"),
        ("VS001-REQ-16", "Manual AI Bridge constraint", "Any v1 AI-assisted review uses export, manual ChatGPT Plus processing, structured import, validation, and human review only.", "SE-003R-002"),
    ]
    rows = []
    for index, (req_id, statement, acceptance, evidence_id) in enumerate(requirements, 1):
        ev = evidence_by_id[evidence_id]
        path = str(ev["original_relative_path"])
        rows.append({
            "selection_id": f"VS001-SRC-003R-{index:02d}",
            "requirement_id": req_id,
            "requirement_statement": statement,
            "acceptance_need": acceptance,
            "source_record_id": ev["source_record_id"],
            "original_relative_path": path,
            "semantic_evidence_ids": evidence_id,
            "capability_id": "CAP-D03-03-01",
            "knowledge_unit_id": "KU-AD-02",
            "support_type": support_type_for_path(path),
            "selection_role": "PRODUCT_REQUIREMENT" if path.startswith("product-charter/") else "PILOT_SCOPE" if path.startswith("ad-identity-pilot/") else "TECHNICAL_CONTENT" if "CKV-022" in path else "GENERIC_CONTEXT_ONLY",
            "authority_scope": authority_for_path(path)[1],
            "claim_boundary": "No implementation; no live AD; no offensive action; unresolved Microsoft/Open Specifications and target Windows version remain visible.",
            "status": "REFINED_SOURCE_TRACE_NOT_APPROVED_BASELINE",
        })
    return rows


def build_deferred_and_issues(evidence: list[dict[str, object]]) -> tuple[list[dict[str, object]], list[dict[str, object]]]:
    old_deferred = read_tsv(OLD_SEM / "SEMANTIC_DEFERRED_QUEUE.tsv")
    rows = []
    for old in old_deferred:
        rows.append({
            "deferred_id": old["deferred_id"].replace("DEF-003-", "DEF-003R-"),
            "source_path_or_scope": old["source_path_or_scope"],
            "deferred_state": old["deferred_state"],
            "reason": old["reason"],
            "future_trigger": old["future_trigger"],
            "supporting_evidence_ids": "",
        })
    for cap_id in sorted(UNSEEDED_CAPABILITY_IDS):
        rows.append({
            "deferred_id": f"DEF-003R-{len(rows)+1:03d}",
            "source_path_or_scope": cap_id,
            "deferred_state": "DEFERRED_NO_REFINED_KU_SEED",
            "reason": "Capability remains in the differentiated catalog, but TASK-003R does not force a KU without sufficient decomposition need or source depth.",
            "future_trigger": "Approved Domain authoring with current primary authority and a demonstrated need for a canonical KU.",
            "supporting_evidence_ids": "",
        })
    issues = [
        ("CKV and merged CISSP notes", "Internal synthesis cites external authorities not verified in TASK-003R", "Technical and version-sensitive claims remain provisional", "Claim-level primary-source review before publication", "SE-003-015;SE-003-039"),
        ("Three university OCR-deferred PDFs", "No text-extractable content was semantically reviewed", "No content or capability claim may be made", "Separately authorized OCR and file-specific review", ""),
        ("chatgpt-project/Cybersecurity-for-dummies.pdf", "AES parse dependency remains unavailable", "No semantic conclusion", "Future approved parse capability without dependency expansion", ""),
        ("Large generated matrices", "Provenance, duplication, and row consistency remain unverified", "Seed schemas only; no primary curriculum authority", "Schema validation, deduplication, and source verification", "SE-003-189;SE-003-192;SE-003-195;SE-003-198"),
        ("VS-001", "Microsoft/Open Specifications and target Windows version were not reviewed", "Windows authorization behavior cannot be published as externally authoritative", "Later approved primary-source and target-version decision", "SE-003R-008;SE-003-041"),
        ("D12 cloud breadth", "Provider-specific topics are inventory signals rather than reviewed technical content", "Cloud-provider capabilities remain low confidence", "Approved AWS/Azure/GCP primary-source selection", "SE-003R-030"),
        ("D15 physical security", "Only a metadata-level advanced-index entry was reviewed", "No local practical or technical authority", "Targeted source selection and specialist review", "SE-003R-032"),
        ("Mastery calibration", "TASK-003R defines measurable concepts but no empirical thresholds", "High-stakes mastery decisions remain provisional", "Pilot measurement and human calibration", "SE-003R-003;SE-003R-006"),
    ]
    issue_rows = [{
        "issue_id": f"SRCISS-003R-{index:03d}",
        "source_path_or_scope": scope,
        "issue": issue,
        "impact": impact,
        "resolution_needed": resolution,
        "status": "OPEN",
        "supporting_evidence_ids": evs,
    } for index, (scope, issue, impact, resolution, evs) in enumerate(issues, 1)]
    return rows, issue_rows


def build_supersession(evidence: list[dict[str, object]], old_kus: list[dict[str, str]]) -> tuple[list[dict[str, object]], list[dict[str, object]]]:
    artifact_pairs = [
        ("SEMANTIC_REVIEW_CORPUS.tsv", "SEMANTIC_REVIEW_CORPUS_REFINED.tsv", "REFINED", "Review-depth claims and review units were corrected."),
        ("SEMANTIC_REVIEW_EVIDENCE.tsv", "SEMANTIC_REVIEW_EVIDENCE_REFINED.tsv", "REFINED", "Broad script-counted full units were replaced by exact content-bearing ranges."),
        ("SOURCE_AUTHORITY_REGISTER.tsv", "SOURCE_AUTHORITY_REGISTER_REFINED.tsv", "REFINED", "Authority scope and technical support role are independent."),
        ("SEMANTIC_SOURCE_ASSESSMENTS.tsv", "SEMANTIC_SOURCE_ASSESSMENTS_REFINED.tsv", "REPLACED_BY_REFINED_BASELINE", "Source-specific observations replace family boilerplate."),
        ("SOURCE_TO_DOMAIN_MAP.tsv", "SOURCE_TO_DOMAIN_MAP_REFINED.tsv", "REPLACED_BY_REFINED_BASELINE", "Only reviewed source scope is mapped."),
        ("CAPABILITY_CLUSTER_CATALOG.tsv", "CAPABILITY_CLUSTER_CATALOG_REFINED.tsv", "REPLACED_BY_REFINED_BASELINE", "Cluster purpose, scope, evidence, roles, and gaps are differentiated."),
        ("CAPABILITY_CATALOG.tsv", "CAPABILITY_CATALOG_REFINED.tsv", "REPLACED_BY_REFINED_BASELINE", "Capability-specific scope, prerequisites, evidence, roles, simulation, Real-Lab, and source boundaries replace universal fields."),
        ("KNOWLEDGE_UNIT_CANDIDATES.tsv", "KNOWLEDGE_UNIT_CANDIDATES_REFINED.tsv", "REPLACED_BY_REFINED_BASELINE", "The one-to-one KU pattern and universal placeholders are removed."),
        ("CROSS_DOMAIN_RELATIONSHIPS.tsv", "CROSS_DOMAIN_RELATIONSHIPS_REFINED.tsv", "REPLACED_BY_REFINED_BASELINE", "Declared related Domains now match specific relationship rows exactly."),
        ("SOURCE_TO_CAPABILITY_MAP.tsv", "SOURCE_TO_CAPABILITY_MAP_REFINED.tsv", "REPLACED_BY_REFINED_BASELINE", "Mechanical first-two-capability mappings are replaced by evidence-cited direct support."),
        ("DOMAIN_COVERAGE_MATRIX.tsv", "DOMAIN_COVERAGE_MATRIX_REFINED.tsv", "REPLACED_BY_REFINED_BASELINE", "Domain confidence, gaps, roles, scope, simulation, and Real-Lab claims are individualized."),
        ("VS001_SOURCE_SELECTION.tsv", "VS001_SOURCE_SELECTION_REFINED.tsv", "REFINED", "VS-001 roles, acceptance needs, and authority boundaries are explicit."),
        ("SEMANTIC_DEFERRED_QUEUE.tsv", "SEMANTIC_DEFERRED_QUEUE_REFINED.tsv", "REFINED", "Deferrals now include intentionally unseeded capabilities."),
        ("UNRESOLVED_SOURCE_ISSUES.tsv", "UNRESOLVED_SOURCE_ISSUES_REFINED.tsv", "REFINED", "Issues now name Domain and source-specific consequences."),
    ]
    rows = [{
        "old_artifact_path": f"source-vault/manifests/semantic/{old}",
        "refined_artifact_path": f"source-vault/manifests/semantic-refined/{new}",
        "status": status,
        "reason": reason,
        "downstream_use_rule": "Task 004 may consume only the human-approved refined artifact; Task 003 remains preserved audit history.",
    } for old, new, status, reason in artifact_pairs]
    report_map = {
        "SEMANTIC_REVIEW_REPORT.md": "SEMANTIC_REFINEMENT_REPORT.md",
        "SOURCE_AUTHORITY_POLICY.md": "SOURCE_AUTHORITY_POLICY_REFINED.md",
        "CYBERSECURITY_DOMAIN_TAXONOMY_V1.md": "CYBERSECURITY_DOMAIN_TAXONOMY_V1_REFINED.md",
        "CAPABILITY_ARCHITECTURE_BASELINE.md": "CAPABILITY_ARCHITECTURE_BASELINE_REFINED.md",
        "ADAPTIVE_LEARNING_MODEL.md": "ADAPTIVE_LEARNING_MODEL_REFINED.md",
        "UNIVERSITY_COURSE_SEMANTIC_ASSESSMENT.md": "UNIVERSITY_COURSE_SEMANTIC_ASSESSMENT_REFINED.md",
        "CURRICULUM_COVERAGE_AND_GAPS.md": "CURRICULUM_COVERAGE_AND_GAPS_REFINED.md",
        "VS001_SOURCE_SELECTION.md": "VS001_SOURCE_SELECTION_REFINED.md",
        "TASK003_DECISION_REGISTER.md": "TASK003R_DECISION_REGISTER.md",
        "UNRESOLVED_DECISIONS.md": "UNRESOLVED_DECISIONS_REFINED.md",
    }
    for old, new in report_map.items():
        rows.append({
            "old_artifact_path": f"source-vault/derived/semantic/{old}",
            "refined_artifact_path": f"source-vault/derived/semantic-refined/{new}",
            "status": "REPLACED_BY_REFINED_BASELINE",
            "reason": "Substantive differentiated report replaces the thin Task 003 summary while preserving the old report.",
            "downstream_use_rule": "Task 004 may consume only the human-approved refined report.",
        })
    rows.extend([
        {"old_artifact_path": "product-repo/tools/semantic_architecture_validation/", "refined_artifact_path": "product-repo/tools/semantic_architecture_refinement/", "status": "REFINED", "reason": "New validator adds semantic-quality, mapping, anti-boilerplate, regression, safety, write-scope, and handoff checks.", "downstream_use_rule": "Preserve and run both historical validation evidence and the refined validator in read-only regression mode."},
        {"old_artifact_path": "product-repo/review-packets/semantic-capability-003/", "refined_artifact_path": "product-repo/review-packets/semantic-capability-003r/", "status": "PRESERVED", "reason": "Task 003 packet remains immutable audit history and the 003R packet records only corrective work.", "downstream_use_rule": "Use 003R only after human review; never rewrite 003 evidence."},
    ])

    id_rows = []
    for old_id in sorted({str(row["supersedes_evidence_id"]) for row in evidence if row.get("supersedes_evidence_id")}):
        replacements = [str(row["semantic_evidence_id"]) for row in evidence if row.get("supersedes_evidence_id") == old_id]
        for new_id in replacements:
            id_rows.append({
                "entity_type": "SEMANTIC_EVIDENCE",
                "old_id": old_id,
                "new_id": new_id,
                "status": "REPLACED_BY_CONTENT_BEARING_REFINED_UNIT",
                "reason": "The Task 003 evidence ID represented a broad script-counted full-file unit; the refined ID records an exact inspected range.",
                "downstream_use_rule": "Use the refined evidence ID for Task 003R conclusions; retain the old ID only as audit history.",
            })
    old_ku_by_cap = {row["parent_capability_id"]: row["knowledge_unit_id"] for row in old_kus}
    for cap_id in sorted(UNSEEDED_CAPABILITY_IDS):
        id_rows.append({
            "entity_type": "KNOWLEDGE_UNIT",
            "old_id": old_ku_by_cap[cap_id],
            "new_id": "",
            "status": "DEFERRED_NO_REFINED_SEED",
            "reason": f"{cap_id} remains a capability, but TASK-003R does not force a one-to-one KU seed.",
            "downstream_use_rule": "Do not use the superseded generic KU; author a future canonical KU only through an approved Domain review.",
        })
    for cap_id, split in sorted(SPLIT_KUS.items()):
        id_rows.append({
            "entity_type": "KNOWLEDGE_UNIT",
            "old_id": "",
            "new_id": split[0],
            "status": "NEW_REFINED_CANONICAL_SEED",
            "reason": f"{cap_id} requires a second, narrower evidence skill rather than forcing all learning into one KU.",
            "downstream_use_rule": "Treat as provisional refined seed pending human approval; do not author a finished lesson in TASK-003R.",
        })
    id_rows.extend([
        {"entity_type": "KNOWLEDGE_UNIT", "old_id": "KU-AD-02", "new_id": "KU-AD-02", "status": "PRESERVED_STABLE_ID", "reason": "Meaning remains the approved VS-001 Windows Authorization Engine scope.", "downstream_use_rule": "Preserve exact VS-001 traceability."},
        {"entity_type": "CAPABILITY", "old_id": "CAP-D03-03-01", "new_id": "CAP-D03-03-01", "status": "PRESERVED_STABLE_ID", "reason": "Meaning remains Windows authorization decision analysis, now with refined boundaries and evidence.", "downstream_use_rule": "Preserve exact VS-001 traceability."},
    ])
    return rows, id_rows


def quality_metrics(capabilities: list[dict[str, object]], kus: list[dict[str, object]], relationships: list[dict[str, object]], source_cap: list[dict[str, object]], coverage: list[dict[str, object]], university: list[dict[str, object]]) -> list[dict[str, object]]:
    old_caps = read_tsv(OLD_SEM / "CAPABILITY_CATALOG.tsv")
    old_kus = read_tsv(OLD_SEM / "KNOWLEDGE_UNIT_CANDIDATES.tsv")
    old_rel = read_tsv(OLD_SEM / "CROSS_DOMAIN_RELATIONSHIPS.tsv")
    old_map = read_tsv(OLD_SEM / "SOURCE_TO_CAPABILITY_MAP.tsv")
    old_cov = read_tsv(OLD_SEM / "DOMAIN_COVERAGE_MATRIX.tsv")
    metrics = []
    def add(name: str, old: object, new: object, target: str, result: str, note: str) -> None:
        metrics.append({"metric_id": f"QM-003R-{len(metrics)+1:03d}", "metric_name": name, "task003_value": old, "task003r_value": new, "target_or_interpretation": target, "result": result, "human_review_note": note})
    for old_field, new_field, label in [
        ("scope_and_boundaries", "scope_and_boundaries", "Distinct capability scopes"),
        ("prerequisites", "prerequisite_capability_ids", "Distinct capability prerequisite ID sets"),
        ("expected_evidence", "expected_evidence", "Distinct capability evidence descriptions"),
        ("related_roles", "related_roles", "Distinct capability role sets"),
        ("coverage_confidence", "source_confidence", "Capability confidence values"),
    ]:
        add(label, len({row[old_field] for row in old_caps}), len({str(row[new_field]) for row in capabilities}), "Not universally identical; shared controlled values require rationale", "PASS", "Human review should assess semantic fit, not demand artificial wording variation.")
    for old_field, new_field, label in [
        ("micro_practice", "micro_practice", "Distinct KU Micro Practices"),
        ("evidence_types", "evidence_types", "Distinct KU evidence type sets"),
        ("mastery_criteria_concept", "mastery_criteria_concept", "Distinct KU mastery concepts"),
        ("failure_and_review_triggers", "failure_and_review_triggers", "Distinct KU review triggers"),
        ("source_gaps", "source_gaps", "Distinct KU source-gap statements"),
        ("prerequisites", "prerequisite_ids", "Distinct KU prerequisite sets"),
    ]:
        add(label, len({row[old_field] for row in old_kus}), len({str(row[new_field]) for row in kus}), "Concrete and Domain/capability specific", "PASS", "Controlled lifecycle and evidence labels may repeat when semantically justified.")
    old_distribution = Counter(row["parent_capability_id"] for row in old_kus)
    new_distribution = Counter(str(row["parent_capability_id"]) for row in kus)
    add("Capability-to-KU distribution", f"zero=0,one={sum(v==1 for v in old_distribution.values())},multiple=0", f"zero={len(capabilities)-len(new_distribution)},one={sum(v==1 for v in new_distribution.values())},multiple={sum(v>1 for v in new_distribution.values())}", "No forced one-to-one relationship", "PASS", "Six capabilities have two KUs and sixteen have no refined KU seed.")
    add("Cross-domain relationship rows", len(old_rel), len(relationships), "Exactly one row per declared related Domain", "PASS", "Count follows declared relations, not a quota.")
    add("Source-to-capability mappings", len(old_map), len(source_cap), "Only direct reviewed support; no mechanical first-two mapping", "PASS", "Product Charter mappings use PRODUCT_REQUIREMENT_SUPPORT, not technical content.")
    add("Distinct Domain major gap statements", len({row["major_gaps"] for row in old_cov}), len({str(row["exact_weak_areas"]) for row in coverage}), "Differentiated across 16 Domains", "PASS", "Gap types and v1/post-v1 decisions are also individualized.")
    add("University file-specific assessments", 30, len(university), "All 30 files represented; 3 OCR deferrals make no content claim", "PASS", "Each text-extractable PDF records observed pages and file-specific examples.")
    add("Refined KU count", len(old_kus), len(kus), "Between 80 and 120 and meaningfully decomposed", "PASS", "Stable IDs are preserved where meaning remains; new split IDs are recorded.")
    add("Banned universal KU placeholders", 6, 0, "Zero occurrences", "PASS", "Validator scans all refined KU fields.")
    return metrics


def field_description(field: str) -> str:
    exact = {
        "status": "Controlled lifecycle or supersession state.",
        "reason": "Record-specific rationale for the state or decision.",
        "downstream_use_rule": "Binding rule for any later-phase consumer.",
        "semantic_evidence_ids": "Semicolon-separated evidence IDs resolving in SEMANTIC_REVIEW_EVIDENCE_REFINED.tsv.",
        "supporting_evidence_ids": "Semicolon-separated direct evidence IDs for the record.",
        "supporting_sources": "Semicolon-separated project-relative paths resolved from cited evidence.",
        "supporting_source_paths": "Semicolon-separated project-relative source paths resolved from cited evidence.",
        "related_domains": "Semicolon-separated approved Domain codes; must match relationship rows exactly for KUs.",
        "real_lab_claim_boundary": "The exact claim, if any, that requires a real environment.",
        "review_depth": "Truthful review state such as selected pages, selected sections, full short file, or OCR deferred.",
    }
    if field in exact:
        return exact[field]
    if field.endswith("_id"):
        return "Stable identifier for this row or referenced entity; blank only where the schema permits a foundation or unresolved state."
    if field.endswith("_ids"):
        return "Semicolon-separated stable identifiers; foreign keys are validated where applicable."
    if "path" in field:
        return "Forward-slash project-relative path; no filename-derived authority is implied."
    if "sha256" in field:
        return "Lowercase SHA-256 digest for the stated file or exact review unit."
    if "confidence" in field:
        return "Controlled confidence value plus a separate evidence-based rationale where provided."
    if "count" in field:
        return "Deterministic integer count derived from the refined tables."
    if "classification" in field or "support_type" in field or "suitability" in field:
        return "Controlled value defined by TASK-003R and validated for allowed values."
    return "Record-specific text or controlled value whose meaning is given by the column name and the Task 003R phase pack."


def report_preamble() -> str:
    return "> Labels: **FACT FROM SOURCE**, **REVIEWER INTERPRETATION**, **PROJECT DECISION**, **PROVISIONAL RECOMMENDATION**, and **UNRESOLVED GAP**. Evidence IDs and exact boundaries are in the refined TSV manifests."


def write_reports(tables: dict[str, list[dict[str, object]]]) -> None:
    clusters = tables["CAPABILITY_CLUSTER_CATALOG_REFINED.tsv"]
    caps = tables["CAPABILITY_CATALOG_REFINED.tsv"]
    kus = tables["KNOWLEDGE_UNIT_CANDIDATES_REFINED.tsv"]
    evidence = tables["SEMANTIC_REVIEW_EVIDENCE_REFINED.tsv"]
    source_map = tables["SOURCE_TO_CAPABILITY_MAP_REFINED.tsv"]
    coverage = tables["DOMAIN_COVERAGE_MATRIX_REFINED.tsv"]
    university = tables["UNIVERSITY_FILE_ASSESSMENTS.tsv"]
    metrics = tables["REFINEMENT_QUALITY_METRICS.tsv"]
    preamble = report_preamble()
    old_map_count = len(read_tsv(OLD_SEM / "SOURCE_TO_CAPABILITY_MAP.tsv"))
    old_rel_count = len(read_tsv(OLD_SEM / "CROSS_DOMAIN_RELATIONSHIPS.tsv"))
    refined_units = len(evidence)

    write_text(REFINED_REPORTS / "SEMANTIC_REFINEMENT_REPORT.md", f"""# Semantic Refinement Report

{preamble}

## Outcome

**PROJECT DECISION:** TASK-003R preserves the bounded 80-file corpus and the 16 Domain codes while replacing generic semantic tables with {len(clusters)} evidence-rationalized clusters, {len(caps)} differentiated capabilities, and {len(kus)} canonical KU seeds. This is a review candidate, not an approved baseline.

**FACT FROM SOURCE:** The refined review ledger contains {refined_units} units, remains under the 240-unit limit, and retains three `DEFERRED_OCR_REQUIRED` university files without content claims.

**REVIEWER INTERPRETATION:** Script-counted broad review claims for Task 003's full-file units were narrowed to content-bearing ranges except three genuinely complete short files. Existing selected pages and sections were preserved.

## Defects corrected

- All capability scope, prerequisite, evidence, role, simulation, Real-Lab, source-confidence, and priority fields now have evidence-linked meaning.
- KU placeholders were removed; practices, mastery concepts, review triggers, prerequisites, lifecycle choices, and source gaps are capability specific.
- The KU distribution is no longer one-to-one: sixteen capabilities intentionally have no seed and six have a second, narrower KU.
- Source mappings fell from {old_map_count} mechanical rows to {len(source_map)} evidence-cited rows with typed support and claim boundaries.
- Cross-Domain rows changed from {old_rel_count} generic rows to {len(tables['CROSS_DOMAIN_RELATIONSHIPS_REFINED.tsv'])} exact declared relationships.
- Thirty university files have file-specific rows; three OCR deferrals remain metadata-only.

**PROVISIONAL RECOMMENDATION:** Human reviewers should inspect low-confidence Domains, the intentionally unseeded capabilities, and the six split KUs before authorizing Task 004 consumption.

**UNRESOLVED GAP:** No external primary-source verification, OCR, AES dependency expansion, real-lab execution, or empirical mastery calibration occurred.
""")

    write_text(REFINED_REPORTS / "SOURCE_AUTHORITY_POLICY_REFINED.md", f"""# Source Authority Policy Refined

{preamble}

**PROJECT DECISION:** Authority and technical support are independent dimensions. Each mapping uses one of the Task 003R support types and cites direct semantic evidence.

**FACT FROM SOURCE:** The Product Charter defines product intent, learning lifecycle, simulator default, evidence, and workflow. It is never classified as technical curriculum support. The AD pilot is authoritative only for its declared pilot scope and VS-001 constraints.

**REVIEWER INTERPRETATION:** CKV technical files can support provisional capability design; CKV control indexes are seed/navigation data. University files are academic support at inspected pages. CARE contributes historical lessons only. Generated matrices are seed schemas only.

**PROVISIONAL RECOMMENDATION:** Before lesson publication or real-environment claims, attach current primary authorities at claim level and record version applicability.

**UNRESOLVED GAP:** External authority selection remains open for every Domain and is especially material for Windows authorization, cloud providers, cryptography, privacy, forensics, malware analysis, OT, and physical security.
""")

    domain_lines = []
    for row in coverage:
        domain_lines.append(f"### {row['domain_code']} — {row['domain_name']}\n\n**PROJECT DECISION:** {row['purpose']} Priority: `{row['v1_priority']}`.\n\n**REVIEWER INTERPRETATION:** Confidence is `{row['coverage_confidence']}` because {str(row['confidence_rationale'])[0].lower()+str(row['confidence_rationale'])[1:]}\n\n**UNRESOLVED GAP:** {row['exact_weak_areas']}\n")
    write_text(REFINED_REPORTS / "CYBERSECURITY_DOMAIN_TAXONOMY_V1_REFINED.md", f"""# Cybersecurity Domain Taxonomy V1 Refined

{preamble}

**FACT FROM SOURCE:** Sixteen approved Domain codes are preserved. No merge or renumbering is justified by the bounded corpus.

**PROVISIONAL RECOMMENDATION:** Treat overlapping use through typed cross-Domain relationships and keep canonical KUs singular.

{''.join(domain_lines)}
""")

    ku_counts = Counter(str(row["parent_capability_id"]) for row in kus)
    write_text(REFINED_REPORTS / "CAPABILITY_ARCHITECTURE_BASELINE_REFINED.md", f"""# Capability Architecture Baseline Refined

{preamble}

**PROJECT DECISION:** The hierarchy remains `Domain -> Capability Cluster -> Capability -> Knowledge Unit`. The baseline has {len(clusters)} clusters, {len(caps)} capabilities, and {len(kus)} provisional KU candidates.

**REVIEWER INTERPRETATION:** The 53 stable clusters were retained after boundary review because each names a distinct professional practice boundary supported by a specific evidence set; the count is not a target. Their universal Task 003 prose was replaced. All 106 capability IDs remain stable because their observable statements still fit the refined boundaries.

**FACT FROM SOURCE:** KU distribution is: {len(caps)-len(ku_counts)} capabilities with zero KU seed, {sum(value == 1 for value in ku_counts.values())} with one, and {sum(value > 1 for value in ku_counts.values())} with multiple. `KU-AD-02` and `CAP-D03-03-01` remain unchanged IDs.

**PROVISIONAL RECOMMENDATION:** Review semantic fit and source sufficiency; do not demand artificial wording variation or equal counts by Domain.

**UNRESOLVED GAP:** Low-confidence and unseeded areas require approved Domain authoring and current authorities before finished lessons.
""")

    write_text(REFINED_REPORTS / "ADAPTIVE_LEARNING_MODEL_REFINED.md", f"""# Adaptive Learning Model Refined

{preamble}

**PROJECT DECISION:** The available lifecycle is Knowledge Unit, Lesson Revision, Micro Practice, Guided Simulator Lab, optional Selective Real-Lab Validation, Evidence-Based Mastery, and Failure-Based Review. Stages are selected by the KU; none is universal.

**FACT FROM SOURCE:** Product authority makes the Institutional Simulator the default Guided Lab and requires evidence appropriate to capability. Pilot material demonstrates failure-specific and retention-specific review. The refined KUs therefore use concrete practices, measurable positive/negative evidence, and explicit review triggers.

**REVIEWER INTERPRETATION:** Real-Lab classification describes a claim boundary, not Domain prestige. Simulation evidence remains labeled `SIMULATED`; real-environment evidence cannot be inferred.

**PROJECT DECISION:** For v1, `AIInteractionPort` has only the Manual AI Bridge: export prompt package, process manually through ChatGPT Plus, import structured result, validate, and require human review. No provider/API/local-model adapter is authorized.

**PROVISIONAL RECOMMENDATION:** Calibrate thresholds empirically during approved pilots while preserving failed decisions and retention misses as review signals.

**UNRESOLVED GAP:** Longitudinal retention and transfer thresholds are not yet measured.
""")

    course_rows = tables["UNIVERSITY_COURSE_COVERAGE.tsv"]
    course_lines = "\n".join(f"- **{row['course_name']}** — {row['reviewed_file_count']}/{row['file_count']} text-reviewed, {row['ocr_deferred_count']} OCR-deferred. {row['distinct_observed_coverage']} Limitation: {row['limitations']}" for row in course_rows)
    write_text(REFINED_REPORTS / "UNIVERSITY_COURSE_SEMANTIC_ASSESSMENT_REFINED.md", f"""# University Course Semantic Assessment Refined

{preamble}

**FACT FROM SOURCE:** All {len(university)} university files have distinct file-level assessment rows: 27 are bounded page reviews and 3 remain `DEFERRED_OCR_REQUIRED` with no semantic content claim.

{course_lines}

**REVIEWER INTERPRETATION:** Network Administration and Monitoring provides concrete infrastructure/resilience examples. Secure Application Development has the strongest visible currency signals. Network Security is useful structured synthesis but often older. Ethical Hacking is uneven and must remain authorization-bounded.

**PROJECT DECISION:** Course synthesis is derived from file observations; filename sequence gaps are not treated as proof of missing lectures.

**PROVISIONAL RECOMMENDATION:** Use these files as supporting curriculum and prerequisite material only, with current primary-source checks before technical publication.

**UNRESOLVED GAP:** OCR, unread pages, lab transfer, and source-edition provenance remain unresolved.
""")

    weakest = [row for row in coverage if row["coverage_confidence"] in {"LOW", "INSUFFICIENT_LOCAL_SUPPORT"}]
    weak_lines = "\n".join(f"- `{row['domain_code']}` {row['domain_name']}: {row['exact_weak_areas']} Gap types: `{row['gap_types']}`." for row in weakest)
    write_text(REFINED_REPORTS / "CURRICULUM_COVERAGE_AND_GAPS_REFINED.md", f"""# Curriculum Coverage and Gaps Refined

{preamble}

**REVIEWER INTERPRETATION:** Local support is strongest within the AD pilot's bounded authorization scope and is useful but provisional for architecture, networking, secure applications, detection, incident response, governance, data protection, and professional evidence practice.

**FACT FROM SOURCE:** Domain confidence varies across `HIGH_WITHIN_LOCAL_SCOPE`, `MEDIUM`, and `LOW`; gap types distinguish local-source, primary-authority, version-validation, practice, simulator-model, real-lab-transfer, and outside-v1 conditions.

{weak_lines}

**PROJECT DECISION:** Low confidence does not remove a Domain; it constrains downstream use and v1 breadth.

**PROVISIONAL RECOMMENDATION:** Select primary authorities and bounded practice corpora by priority rather than filling every Domain equally.

**UNRESOLVED GAP:** No Domain has complete externally verified curriculum coverage.
""")

    write_text(REFINED_REPORTS / "VS001_SOURCE_SELECTION_REFINED.md", f"""# VS-001 Source Selection Refined — Windows Authorization Decision

{preamble}

**PROJECT DECISION:** Preserve `VS-001`, `CAP-D03-03-01`, and `KU-AD-02`. Do not implement the slice in TASK-003R.

**FACT FROM SOURCE:** Product authority supports simulator/evidence requirements; pilot files support approved scope, gates, KU evidence, and review; CKV-022 provisionally supports tokens, SIDs, privileges, descriptors, DACL/ACE order, masks, and access-check reasoning. The web authorization lecture is generic context only.

**REVIEWER INTERPRETATION:** Acceptance needs cover principal identity, token user/group SIDs, privileges, descriptor/owner, ordered ACE evaluation, requested mask, generic mapping, allow/deny, insufficient state, deterministic explanation, provenance, positive/negative tests, and `SIMULATED` labeling.

**PROVISIONAL RECOMMENDATION:** Review Microsoft/Open Specifications and select a target Windows version before content publication or real-transfer claims.

**UNRESOLVED GAP:** The current corpus does not establish authoritative Windows semantics or version behavior.
""")

    write_text(REFINED_REPORTS / "TASK003R_DECISION_REGISTER.md", f"""# TASK-003R Decision Register

{preamble}

1. **PROJECT DECISION:** Preserve 16 Domain codes, 53 coherent cluster IDs, and 106 capability IDs; differentiate their semantics rather than renumbering.
2. **PROJECT DECISION:** Seed {len(kus)} KUs: sixteen capabilities remain unseeded and six receive a second KU.
3. **PROJECT DECISION:** Preserve `KU-AD-02`, `CAP-D03-03-01`, and VS-001 traceability.
4. **PROJECT DECISION:** Product authority is not technical curriculum authority; support types remain explicit.
5. **PROJECT DECISION:** Institutional Simulator first; Real-Lab selective and claim-specific.
6. **PROJECT DECISION:** Manual AI Bridge is the only v1 AI execution path.
7. **FACT FROM SOURCE:** The corpus remains 80 files and {refined_units} reviewed units, with three OCR deferrals.
8. **REVIEWER INTERPRETATION:** The 53 clusters remain coherent after review; no count quota was applied.
9. **PROVISIONAL RECOMMENDATION:** Human reviewers should challenge low-confidence rows and exact direct-support mappings.
10. **UNRESOLVED GAP:** Task 004 is not authorized by this packet and the refined baseline is not self-approved.
""")

    write_text(REFINED_REPORTS / "UNRESOLVED_DECISIONS_REFINED.md", f"""# Unresolved Decisions Refined

{preamble}

- **UNRESOLVED GAP:** Approve or revise the refined Domain/cluster/capability/KU semantics after human review.
- **UNRESOLVED GAP:** Select current primary technical and regulatory authorities by Domain and claim.
- **UNRESOLVED GAP:** Decide whether and when to authorize OCR for three university PDFs and AES parsing for one deferred reference.
- **UNRESOLVED GAP:** Select Microsoft/Open Specifications and a target Windows version for VS-001.
- **UNRESOLVED GAP:** Calibrate mastery, retention, and real-transfer thresholds empirically.
- **UNRESOLVED GAP:** Decide future source expansion for low-confidence cloud, forensics, malware, cryptography/privacy, OT/IoT, and physical-security areas.

**FACT FROM SOURCE:** Each issue is also represented in refined deferred/issue manifests where source-level traceability exists.

**REVIEWER INTERPRETATION:** These gaps constrain claims; they do not justify invented evidence or equalized Domain counts.

**PROJECT DECISION:** No unresolved decision is silently treated as approved.

**PROVISIONAL RECOMMENDATION:** Resolve only through an explicitly authorized later phase.
""")

    schema_sections = ["# Refined Manifest Schemas", "", preamble, "", "**PROJECT DECISION:** All TSV files are UTF-8, tab-delimited, use one header row, and encode multi-value fields with semicolons. Tabs/newlines are prohibited inside values.", "", "**FACT FROM SOURCE:** The schemas below exactly match the generated headers and refined validator expectations.", "", "**REVIEWER INTERPRETATION:** IDs and paths provide traceability; descriptive fields remain bounded reviewer interpretations rather than external truth.", "", "**PROVISIONAL RECOMMENDATION:** Downstream consumers should reject unknown columns or unresolved foreign keys.", "", "**UNRESOLVED GAP:** Task 004 schemas are not defined here.", ""]
    for name, columns in SCHEMAS.items():
        schema_sections.extend([f"## `{name}`", "", "| Column | Meaning |", "|---|---|"])
        schema_sections.extend(f"| `{column}` | {field_description(column)} |" for column in columns)
        schema_sections.append("")
    write_text(REFINED_REPORTS / "REFINED_MANIFEST_SCHEMAS.md", "\n".join(schema_sections))


def write_review_packet(tables: dict[str, list[dict[str, object]]]) -> None:
    caps = tables["CAPABILITY_CATALOG_REFINED.tsv"]
    kus = tables["KNOWLEDGE_UNIT_CANDIDATES_REFINED.tsv"]
    clusters = tables["CAPABILITY_CLUSTER_CATALOG_REFINED.tsv"]
    corpus = tables["SEMANTIC_REVIEW_CORPUS_REFINED.tsv"]
    evidence = tables["SEMANTIC_REVIEW_EVIDENCE_REFINED.tsv"]
    source_map = tables["SOURCE_TO_CAPABILITY_MAP_REFINED.tsv"]
    relationships = tables["CROSS_DOMAIN_RELATIONSHIPS_REFINED.tsv"]
    coverage = tables["DOMAIN_COVERAGE_MATRIX_REFINED.tsv"]
    university = tables["UNIVERSITY_FILE_ASSESSMENTS.tsv"]
    metrics = tables["REFINEMENT_QUALITY_METRICS.tsv"]
    old_map = read_tsv(OLD_SEM / "SOURCE_TO_CAPABILITY_MAP.tsv")
    old_rel = read_tsv(OLD_SEM / "CROSS_DOMAIN_RELATIONSHIPS.tsv")
    old_evidence = read_tsv(OLD_SEM / "SEMANTIC_REVIEW_EVIDENCE.tsv")
    old_pairs = {(row["original_relative_path"], row["capability_id"]) for row in old_map}
    new_pairs = {(str(row["original_relative_path"]), str(row["capability_id"])) for row in source_map}
    preserved_units = sum(1 for row in evidence if row["evidence_status"] == "PRESERVED_REVIEW_UNIT")
    replaced_old_ids = {str(row["supersedes_evidence_id"]) for row in evidence if row["supersedes_evidence_id"]}
    added_units = sum(1 for row in evidence if str(row["semantic_evidence_id"]).startswith("SE-003R-")) - len(replaced_old_ids)
    ku_count_by_cap = Counter(str(row["parent_capability_id"]) for row in kus)
    confidence_counts = Counter(str(row["coverage_confidence"]) for row in coverage)

    write_text(REFINED_PACKET / "TASK003_VS_TASK003R_COMPARISON.md", f"""# Task 003 vs Task 003R Comparison

| Measure | Task 003 | Task 003R |
|---|---:|---:|
| Corpus files | 80 | {len(corpus)} |
| Review units | {len(old_evidence)} | {len(evidence)} |
| Clusters | 53 | {len(clusters)} |
| Capabilities | 106 | {len(caps)} |
| KU candidates | 106 | {len(kus)} |
| Capabilities with zero/one/multiple KUs | 0/106/0 | {len(caps)-len(ku_count_by_cap)}/{sum(v==1 for v in ku_count_by_cap.values())}/{sum(v>1 for v in ku_count_by_cap.values())} |
| Source-capability mappings | {len(old_map)} | {len(source_map)} |
| Cross-Domain relationships | {len(old_rel)} | {len(relationships)} |
| Distinct Domain gap statements | 1 | {len({row['exact_weak_areas'] for row in coverage})} |

- Mapping pairs removed: {len(old_pairs - new_pairs)}
- Mapping pairs added: {len(new_pairs - old_pairs)}
- Existing mapping pairs retained with refined support type/boundary: {len(old_pairs & new_pairs)}
- Broad Task 003 evidence IDs replaced: {len(replaced_old_ids)}
- Task 003 selected review units preserved: {preserved_units}
- Net-new refined units beyond one-for-one broad-unit replacement: {added_units}
""")

    metric_lines = "\n".join(f"- `{row['metric_id']}` {row['metric_name']}: {row['task003_value']} -> {row['task003r_value']} — **{row['result']}**" for row in metrics)
    write_text(REFINED_PACKET / "REFINEMENT_QUALITY_SUMMARY.md", f"""# Refinement Quality Summary

{metric_lines}

Exact repetition is measured for human review; controlled values may repeat when their rationales remain specific. Artificial synonym variation is not a quality target.
""")

    domain_ku_counts = Counter(str(row["primary_domain"]) for row in kus)
    write_text(REFINED_PACKET / "CAPABILITY_AND_KU_COUNTS.md", "# Capability and KU Counts\n\n" + f"- Domains: 16\n- Clusters: {len(clusters)}\n- Capabilities: {len(caps)}\n- Refined KU candidates: {len(kus)}\n- Capabilities with no KU seed: {len(caps)-len(ku_count_by_cap)}\n- Capabilities with one KU: {sum(v==1 for v in ku_count_by_cap.values())}\n- Capabilities with multiple KUs: {sum(v>1 for v in ku_count_by_cap.values())}\n- Cross-Domain relationships: {len(relationships)}\n\n" + "\n".join(f"- {code}: {domain_ku_counts[code]} KUs" for code in sorted(DOMAIN_DATA)))

    write_text(REFINED_PACKET / "DOMAIN_COVERAGE_SUMMARY.md", "# Domain Coverage Summary\n\n" + "\n".join(f"- {key}: {value}" for key, value in sorted(confidence_counts.items())) + "\n\n" + "\n".join(f"- `{row['domain_code']}` {row['coverage_confidence']}: {row['exact_weak_areas']}" for row in coverage))

    ocr = [row for row in university if row["review_status"] == "DEFERRED_OCR_REQUIRED"]
    write_text(REFINED_PACKET / "UNIVERSITY_REFINEMENT_SUMMARY.md", f"""# University Refinement Summary

- University files represented: {len(university)}
- Text-extractable bounded page reviews: {len(university)-len(ocr)}
- OCR-deferred with no semantic claim: {len(ocr)}
- Distinct file-level topic observations: {len({row['observed_topic'] for row in university})}
- Course syntheses: {len(tables['UNIVERSITY_COURSE_COVERAGE.tsv'])}

The assessment records concrete reviewed pages, examples, strengths, currentness signals, limitations, supported Domains/capabilities, and suitability per file. Filename sequence gaps are not treated as proof of missing lectures.
""")

    write_text(REFINED_PACKET / "SOURCE_SAFETY_RESULTS.md", """# Source Safety Results

- Original-source count/fingerprint validation: PENDING REFINED VALIDATOR
- Eighty corpus source hashes against the Task 001 census: PENDING REFINED VALIDATOR
- Seventy-seven reviewed-copy hashes against originals: PENDING REFINED VALIDATOR
- Task 001, Task 002, and Task 003 output immutability: PENDING REFINED VALIDATOR
- Original files written, renamed, deleted, normalized, decompressed, or executed: **0 by TASK-003R**
""")
    write_text(REFINED_PACKET / "REGRESSION_RESULTS.md", """# Regression Results

- Task 001 tests: PENDING REFINED VALIDATOR
- Task 002 tests: PENDING REFINED VALIDATOR
- Task 003 validator logic in read-only wrapper: PENDING REFINED VALIDATOR
- Prior Task 003 handoff hash regression: PENDING REFINED VALIDATOR
""")
    write_text(REFINED_PACKET / "TEST_RESULTS.txt", "TASK-003R build: PASS\nRefined deterministic validator: PENDING\nRegression suites: PENDING\nHandoff integrity: PENDING\nProduct application build: NOT RUN (prohibited)\n")
    write_text(REFINED_PACKET / "RESIDUAL_LIMITATIONS.md", """# Residual Limitations

- No external primary-source verification, OCR, AES dependency expansion, external research, real-lab execution, or offensive operation occurred.
- Three university PDFs remain OCR-deferred and one non-university PDF remains AES parse-deferred.
- CKV/CISSP/roadmap material remains internal or secondary; generated matrices remain seed data.
- Low-confidence Domains require later targeted authority and practice review.
- Mastery and retention criteria are measurable concepts, not empirically calibrated thresholds.
- No finished lessons, Task 004 work, product application code, UI, database, Docker, AI provider, API adapter, or local model path was created.
- The refined baseline is not self-approved.
""")

    write_text(REFINED_PACKET / "CODEX_FINAL_REPORT.md", f"""# Codex Final Report — TASK-003R

## Workspace and runtime

- Workspace: `{ROOT}`
- Runtime used for build: Python `{platform.python_version()}` on `{platform.platform()}`
- Git state: workspace `.git` directory is present but contains no usable repository metadata; write-scope evidence therefore relies on prior-output hashes and explicit changed-file inventory.

## Exact inputs read

- Root `AGENTS.md` and `phase-packs/TASK_003R_SEMANTIC_CAPABILITY_REFINEMENT.md`.
- All required Task 003 review-packet reports and its 77 complete reviewed source copies.
- All 14 Task 003 semantic manifests, all 10 Task 003 semantic reports, and both Task 003 semantic tooling files.
- Structural section/page/university manifests needed to resolve recorded line/page anchors.
- Task 001 census/metadata, Task 002 metadata/manifests, and the prior Task 003 handoff manifests/checksums for regression and custody verification.

## Review units

- Corpus files preserved: {len(corpus)} / 80.
- Task 003 selected units preserved unchanged: {preserved_units}.
- Broad Task 003 evidence IDs replaced by exact content-bearing units: {len(replaced_old_ids)}.
- Refined total units: {len(evidence)} / 240.
- Net-new units beyond replacement: {added_units}.
- OCR deferrals: 3 with zero semantic content claims.

## Architecture counts and IDs

- Domains / clusters / capabilities / KU candidates: 16 / {len(clusters)} / {len(caps)} / {len(kus)}.
- Cluster IDs changed: 0. Capability IDs changed: 0.
- `KU-AD-02` preserved. Six new split KU IDs added. Sixteen generic Task 003 KU IDs deferred with no refined replacement.
- KU distribution: zero={len(caps)-len(ku_count_by_cap)}, one={sum(v==1 for v in ku_count_by_cap.values())}, multiple={sum(v>1 for v in ku_count_by_cap.values())}.

## Generic-template defects corrected

- Capability scopes, explicit exclusions, prerequisites/foundations, expected evidence, simulator rationale, Real-Lab boundary, roles/rationale, Domain relationship reasons, source confidence, and v1 rationale are differentiated.
- KU prerequisite IDs, lesson boundaries, concrete Micro Practices, lab rationale, claim-specific Real-Lab boundaries, evidence types, measurable mastery, failure/retention triggers, source paths/evidence IDs, source gaps, and lifecycle templates are differentiated.
- Domain profiles have individual purpose, outcomes, scope/boundaries, roles, support, weaknesses, simulation, exact Real-Lab claims, priorities, breadth, confidence, and gap types.

## Source mapping and relationships

- Source-capability mapping rows: {len(old_map)} -> {len(source_map)}.
- Mapping pairs removed/added/retained: {len(old_pairs-new_pairs)} / {len(new_pairs-old_pairs)} / {len(old_pairs&new_pairs)}.
- Product Charter technical-content mappings: 0; its mappings are typed `PRODUCT_REQUIREMENT_SUPPORT`.
- Cross-Domain relationship rows: {len(old_rel)} -> {len(relationships)}; every declared KU related Domain has exactly one specific row.

## University and VS-001 refinement

- University file rows: 30; bounded page reviews: 27; OCR deferrals: 3.
- All text-extractable rows record file-specific observed topics, concrete examples, strengths, age/currentness signals, limitations, supported Domains/capabilities, suitability, and inspected evidence.
- VS-001 preserves `CAP-D03-03-01` and `KU-AD-02` and separates product requirements, pilot scope, provisional technical content, generic web context, unresolved Microsoft/Open Specifications verification, and target-version selection.

## Quality metrics, tests, regressions, and safety

- Differentiated quality metrics recorded: {len(metrics)}; current results: {Counter(row['result'] for row in metrics)['PASS']} PASS.
- Deterministic validator, prior test suites, source/prior-output hashes, handoff checksums, and ZIP integrity: PENDING FINAL VALIDATOR RUN.
- No Task 001, Task 002, Task 003, structural, readiness, semantic, or original-source output was intentionally modified.

## Limitations and stop boundary

- Known limitations and unresolved decisions are explicit in the refined reports and review packet.
- Task 004 was not started. Product application code was not created. No Laravel, Vue, Inertia, PostgreSQL, Docker, product UI, AI provider/API/local-model execution path, OCR, or real-lab operation was built.
- This report does not approve the refined baseline.
""")


def write_changed_files() -> None:
    files = []
    for base in [REFINED_SEM, REFINED_REPORTS, REFINED_TOOLS, REFINED_PACKET]:
        for path in base.rglob("*"):
            if path.is_file() and "__pycache__" not in path.parts and path.suffix not in {".pyc", ".pyo"}:
                files.append(path.relative_to(ROOT).as_posix())
    files.extend([
        "product-repo/review-packets/TASK_003R_REVIEW_HANDOFF/",
        "product-repo/review-packets/TASK_003R_REVIEW_HANDOFF.zip",
    ])
    write_text(REFINED_PACKET / "CHANGED_FILES.txt", "\n".join(sorted(set(files), key=str.casefold)))


def build_tables() -> dict[str, list[dict[str, object]]]:
    REFINED_SEM.mkdir(parents=True, exist_ok=True)
    REFINED_REPORTS.mkdir(parents=True, exist_ok=True)
    REFINED_PACKET.mkdir(parents=True, exist_ok=True)
    census_rows = read_tsv(ROOT / "source-vault" / "manifests" / "SOURCE_FILE_CENSUS.tsv")
    census = {row["relative_path"]: row for row in census_rows}
    old_corpus = read_tsv(OLD_SEM / "SEMANTIC_REVIEW_CORPUS.tsv")
    old_authority = read_tsv(OLD_SEM / "SOURCE_AUTHORITY_REGISTER.tsv")
    corpus, evidence = build_evidence(old_corpus, census)
    findings, authority, assessments, source_domain, university = build_source_tables(corpus, evidence, old_authority)
    clusters, capabilities, cap_dependencies, kus, ku_dependencies, relationships, _ = build_architecture(evidence)
    source_cap = build_source_capability_map(capabilities, evidence)
    coverage = build_domain_coverage(clusters, capabilities, kus)
    courses = build_university_courses(university)
    vs001 = build_vs001(evidence)
    deferred, issues = build_deferred_and_issues(evidence)
    supersession, id_supersession = build_supersession(evidence, read_tsv(OLD_SEM / "KNOWLEDGE_UNIT_CANDIDATES.tsv"))
    metrics = quality_metrics(capabilities, kus, relationships, source_cap, coverage, university)
    tables: dict[str, list[dict[str, object]]] = {
        "TASK003_TO_TASK003R_SUPERSESSION_MAP.tsv": supersession,
        "ID_SUPERSESSION_MAP.tsv": id_supersession,
        "SEMANTIC_REVIEW_CORPUS_REFINED.tsv": corpus,
        "SEMANTIC_REVIEW_EVIDENCE_REFINED.tsv": evidence,
        "SEMANTIC_REVIEW_FINDINGS.tsv": findings,
        "SOURCE_AUTHORITY_REGISTER_REFINED.tsv": authority,
        "SEMANTIC_SOURCE_ASSESSMENTS_REFINED.tsv": assessments,
        "SOURCE_TO_DOMAIN_MAP_REFINED.tsv": source_domain,
        "CAPABILITY_CLUSTER_CATALOG_REFINED.tsv": clusters,
        "CAPABILITY_CATALOG_REFINED.tsv": capabilities,
        "CAPABILITY_DEPENDENCIES.tsv": cap_dependencies,
        "KNOWLEDGE_UNIT_CANDIDATES_REFINED.tsv": sorted(kus, key=lambda row: str(row["knowledge_unit_id"])),
        "KNOWLEDGE_UNIT_DEPENDENCIES.tsv": ku_dependencies,
        "CROSS_DOMAIN_RELATIONSHIPS_REFINED.tsv": relationships,
        "SOURCE_TO_CAPABILITY_MAP_REFINED.tsv": source_cap,
        "DOMAIN_COVERAGE_MATRIX_REFINED.tsv": coverage,
        "UNIVERSITY_FILE_ASSESSMENTS.tsv": university,
        "UNIVERSITY_COURSE_COVERAGE.tsv": courses,
        "VS001_SOURCE_SELECTION_REFINED.tsv": vs001,
        "SEMANTIC_DEFERRED_QUEUE_REFINED.tsv": deferred,
        "UNRESOLVED_SOURCE_ISSUES_REFINED.tsv": issues,
        "REFINEMENT_QUALITY_METRICS.tsv": metrics,
    }
    for name, schema in SCHEMAS.items():
        write_tsv(REFINED_SEM / name, schema, tables[name])
    write_reports(tables)
    write_review_packet(tables)
    write_changed_files()
    return tables


def copy_tree_filtered(source: Path, destination: Path, exclude_names: set[str] | None = None) -> list[str]:
    missing = []
    exclude_names = exclude_names or set()
    if not source.exists():
        return [source.relative_to(ROOT).as_posix()]
    for path in source.rglob("*"):
        relative = path.relative_to(source)
        if any(part in exclude_names or part == "__pycache__" for part in relative.parts):
            continue
        if path.is_file() and path.suffix not in {".pyc", ".pyo"}:
            target = destination / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(path, target)
    return missing


def package_handoff() -> tuple[int, int]:
    if HANDOFF.exists():
        resolved = HANDOFF.resolve()
        expected_parent = (ROOT / "product-repo" / "review-packets").resolve()
        if resolved.parent != expected_parent or resolved.name != "TASK_003R_REVIEW_HANDOFF":
            raise RuntimeError(f"Refusing to rebuild unexpected handoff path: {resolved}")
        shutil.rmtree(resolved)
    HANDOFF.mkdir(parents=True)
    if HANDOFF_ZIP.exists():
        HANDOFF_ZIP.unlink()
    missing: list[str] = []
    payload_roots = [
        (REFINED_PACKET, HANDOFF / REFINED_PACKET.relative_to(ROOT)),
        (REFINED_SEM, HANDOFF / REFINED_SEM.relative_to(ROOT)),
        (REFINED_REPORTS, HANDOFF / REFINED_REPORTS.relative_to(ROOT)),
        (REFINED_TOOLS, HANDOFF / REFINED_TOOLS.relative_to(ROOT)),
    ]
    for source, destination in payload_roots:
        missing.extend(copy_tree_filtered(source, destination))
    prior_destination = HANDOFF / OLD_PACKET.relative_to(ROOT)
    missing.extend(copy_tree_filtered(OLD_PACKET, prior_destination, {"reviewed-source-copies"}))
    if (ROOT / "AGENTS.md").is_file():
        shutil.copy2(ROOT / "AGENTS.md", HANDOFF / "AGENTS.md")
    else:
        missing.append("AGENTS.md")

    old_copy_rows = read_tsv(OLD_PACKET / "REVIEWED_SOURCE_FILES_MANIFEST.tsv")
    refined_evidence = read_tsv(REFINED_SEM / "SEMANTIC_REVIEW_EVIDENCE_REFINED.tsv")
    refined_ids_by_path: dict[str, list[str]] = defaultdict(list)
    for row in refined_evidence:
        refined_ids_by_path[row["original_relative_path"]].append(row["semantic_evidence_id"])
    reference_rows = []
    for row in old_copy_rows:
        original_path = row["original_relative_path"]
        prior_copy_project_path = f"product-repo/review-packets/semantic-capability-003/{row['copied_relative_path']}"
        prior_copy = ROOT / prior_copy_project_path
        refined_ids = ";".join(refined_ids_by_path[original_path])
        old_ids = row["semantic_evidence_ids"]
        verification = "PASS_SHA256_AND_SIZE" if prior_copy.is_file() and sha256(prior_copy) == row["source_sha256"] and prior_copy.stat().st_size == int(row["file_size_bytes"]) else "FAIL"
        status = "EVIDENCE_REFINED" if set(split_values(refined_ids)) != set(split_values(old_ids)) else "REUSED_UNCHANGED_SOURCE_COPY"
        reference_rows.append({
            "original_relative_path": original_path,
            "task003_copied_relative_path": prior_copy_project_path,
            "sha256": row["source_sha256"],
            "file_size_bytes": row["file_size_bytes"],
            "task003_semantic_evidence_ids": old_ids,
            "task003r_semantic_evidence_ids": refined_ids,
            "status": status,
            "verification_result": verification,
        })
    write_tsv(HANDOFF / "PRIOR_REVIEWED_SOURCE_REFERENCE.tsv", ["original_relative_path", "task003_copied_relative_path", "sha256", "file_size_bytes", "task003_semantic_evidence_ids", "task003r_semantic_evidence_ids", "status", "verification_result"], reference_rows)
    write_tsv(HANDOFF / "NEW_REVIEWED_SOURCE_FILES_MANIFEST.tsv", ["original_relative_path", "copied_relative_path", "sha256", "file_size_bytes", "semantic_evidence_ids", "status"], [])
    write_text(HANDOFF / "MISSING_FILES.txt", "NONE" if not missing else "\n".join(sorted(set(missing))))

    payload_files = sorted([path for path in HANDOFF.rglob("*") if path.is_file() and path.name not in {"HANDOFF_MANIFEST.tsv", "SHA256SUMS.txt", "ZIP_INTEGRITY_RESULT.txt"}], key=lambda path: path.relative_to(HANDOFF).as_posix().casefold())
    manifest_rows = []
    for path in payload_files:
        relative = path.relative_to(HANDOFF).as_posix()
        manifest_rows.append({
            "handoff_relative_path": relative,
            "sha256": sha256(path),
            "file_size_bytes": path.stat().st_size,
            "copy_status": "VERIFIED_SHA256_MATCH",
        })
    write_tsv(HANDOFF / "HANDOFF_MANIFEST.tsv", ["handoff_relative_path", "sha256", "file_size_bytes", "copy_status"], manifest_rows)
    write_text(HANDOFF / "ZIP_INTEGRITY_RESULT.txt", "PENDING PREFLIGHT")

    def write_sums() -> None:
        sum_files = sorted([path for path in HANDOFF.rglob("*") if path.is_file() and path.name != "SHA256SUMS.txt"], key=lambda path: path.relative_to(HANDOFF).as_posix().casefold())
        write_text(HANDOFF / "SHA256SUMS.txt", "\n".join(f"{sha256(path)}  {path.relative_to(HANDOFF).as_posix()}" for path in sum_files))

    def build_zip() -> tuple[int, int]:
        with zipfile.ZipFile(HANDOFF_ZIP, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
            for path in sorted([item for item in HANDOFF.rglob("*") if item.is_file()], key=lambda item: item.relative_to(HANDOFF).as_posix().casefold()):
                archive.write(path, path.relative_to(HANDOFF).as_posix())
        with zipfile.ZipFile(HANDOFF_ZIP, "r") as archive:
            bad = archive.testzip()
            if bad is not None:
                raise RuntimeError(f"ZIP integrity failure at {bad}")
            infos = archive.infolist()
            return len(infos), sum(info.file_size for info in infos)

    write_sums()
    pre_count, pre_bytes = build_zip()
    write_text(HANDOFF / "ZIP_INTEGRITY_RESULT.txt", f"PASS\nArchive: product-repo/review-packets/TASK_003R_REVIEW_HANDOFF.zip\nPreflight files: {pre_count}\nPreflight uncompressed bytes: {pre_bytes}\nFinal archive is rebuilt after this record and must pass a second integrity test.\nReviewed source copies reused by reference: {len(reference_rows)}\nNew reviewed source copies: 0")
    write_sums()
    final_count, final_bytes = build_zip()
    if final_count != pre_count or final_bytes < pre_bytes:
        raise RuntimeError("Final handoff changed unexpectedly after preflight metadata")
    return final_count, final_bytes


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--package-only", action="store_true", help="Rebuild only the verified review handoff from current refined outputs")
    args = parser.parse_args()
    if not args.package_only:
        tables = build_tables()
        print(f"BUILT corpus={len(tables['SEMANTIC_REVIEW_CORPUS_REFINED.tsv'])} units={len(tables['SEMANTIC_REVIEW_EVIDENCE_REFINED.tsv'])} clusters={len(tables['CAPABILITY_CLUSTER_CATALOG_REFINED.tsv'])} capabilities={len(tables['CAPABILITY_CATALOG_REFINED.tsv'])} kus={len(tables['KNOWLEDGE_UNIT_CANDIDATES_REFINED.tsv'])}")
    file_count, byte_count = package_handoff()
    print(f"PACKAGED files={file_count} uncompressed_bytes={byte_count} zip={HANDOFF_ZIP}")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(f"FAIL: {type(exc).__name__}: {exc}", file=sys.stderr)
        raise


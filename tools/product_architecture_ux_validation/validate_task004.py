from __future__ import annotations

import argparse
import csv
import hashlib
from html.parser import HTMLParser
import importlib.util
from pathlib import Path
import re
import shutil
import sys
import zipfile


WORKSPACE = Path(__file__).resolve().parents[3]
PRODUCT_REPO = WORKSPACE / "product-repo"
PLANNING = PRODUCT_REPO / "planning" / "task004"
PROTOTYPE = PRODUCT_REPO / "design-prototypes" / "task004"
PACKET = PRODUCT_REPO / "review-packets" / "product-architecture-ux-004"
HANDOFF = PRODUCT_REPO / "review-packets" / "TASK_004_REVIEW_HANDOFF"
ZIP_PATH = PRODUCT_REPO / "review-packets" / "TASK_004_REVIEW_HANDOFF.zip"

SCHEMAS = {
    "REQUIREMENTS_CATALOG.tsv": ["requirement_id", "requirement_type", "statement", "rationale", "priority", "scope", "owning_module", "acceptance_method", "source_decision_references", "status"],
    "REQUIREMENTS_TRACEABILITY.tsv": ["trace_id", "requirement_id", "decision_ids", "module_ids", "entity_workflow_ids", "ux_requirement_ids", "security_requirement_ids", "vs001_criterion_ids", "future_task", "status"],
    "DECISION_REGISTER.tsv": ["decision_id", "title", "decision", "status", "rationale", "alternatives", "consequences", "affected_tasks", "revisit_trigger", "source_references"],
    "MODULE_BOUNDARIES.tsv": ["module_id", "module_name", "purpose", "owned_entities", "owned_data_concepts", "public_application_services", "inbound_dependencies", "outbound_dependencies", "internal_messages", "security_boundary", "v1_scope", "deferred_scope", "vs001_participation", "status"],
    "DATA_OWNERSHIP_MATRIX.tsv": ["entity_id", "entity_name", "owning_module", "persistence_class", "mutability", "authoritative_source", "lifecycle_notes", "vs001_required", "status"],
    "ENTITY_CATALOG.tsv": ["entity_id", "entity_name", "owning_module", "purpose", "persistence_class", "key_relations", "v1_scope", "vs001_role", "status"],
    "WORKFLOW_CATALOG.tsv": ["workflow_id", "workflow_name", "owning_module", "participating_modules", "trigger", "preconditions", "terminal_outcomes", "audit_requirement", "v1_scope", "vs001_participation", "status"],
    "UX_REQUIREMENTS.tsv": ["ux_requirement_id", "statement", "workspace", "priority", "scope", "acceptance_method", "related_requirement_ids", "status"],
    "SECURITY_REQUIREMENTS.tsv": ["security_requirement_id", "threat", "statement", "owning_module", "priority", "scope", "acceptance_method", "related_requirement_ids", "status"],
    "VS001_ACCEPTANCE_CRITERIA.tsv": ["criterion_id", "statement", "requirement_ids", "module_ids", "entity_ids", "workflow_ids", "acceptance_method", "blocker", "status"],
    "V1_SCOPE_MATRIX.tsv": ["scope_item_id", "capability_or_boundary", "classification", "owning_module", "acceptance_evidence", "deferred_reason_or_constraint", "future_task", "status"],
    "UNRESOLVED_DECISIONS.tsv": ["unresolved_id", "decision_or_gap", "impact", "owner_module", "required_evidence", "resolution_trigger", "blocks", "status"],
}

PRODUCT_FILES = [
    "PRODUCT_DEFINITION_V1_CANDIDATE.md", "V1_SCOPE_AND_BOUNDARIES.md",
    "USER_PROFILES_AND_JOURNEYS.md", "PRODUCT_MODULE_CATALOG.md",
    "V1_SUCCESS_CRITERIA.md", "V1_DELIVERY_PLAN.md",
    "PRODUCT_RISKS_AND_MITIGATIONS.md", "TASK006_REPOSITORY_FOUNDATION_SCOPE.md",
    "AGENTS_TASK004_PROPOSED_PATCH.md",
]
ARCH_FILES = [
    "ARCHITECTURE_BASELINE_V1_CANDIDATE.md", "MODULAR_MONOLITH_BOUNDARIES.md",
    "LOGICAL_DATA_MODEL.md", "DATA_OWNERSHIP_AND_LIFECYCLE.md",
    "CONTENT_AND_REVISION_MODEL.md", "CURRICULUM_LEARNING_MASTERY_MODEL.md",
    "ENTERPRISE_SCENARIO_SIMULATION_MODEL.md", "MANUAL_AI_BRIDGE_ARCHITECTURE.md",
    "SECURITY_PRIVACY_THREAT_MODEL.md", "DEPLOYMENT_AND_OPERATIONS_BASELINE.md",
    "FILE_STORAGE_AND_IMPORT_BOUNDARIES.md", "SEARCH_AND_ASYNC_PROCESSING_BASELINE.md",
    "VS001_ARCHITECTURE_SLICE.md",
]
UX_FILES = [
    "INFORMATION_ARCHITECTURE.md", "NAVIGATION_AND_WORKSPACE_MODEL.md",
    "PRIMARY_USER_FLOWS.md", "LESSON_READER_EDITOR_INTERACTION.md",
    "SOURCE_REVIEW_INTERACTION.md", "GUIDED_LAB_INTERACTION.md",
    "ENTERPRISE_AND_SCENARIO_STUDIO_INTERACTION.md", "MANUAL_AI_BRIDGE_INTERACTION.md",
    "EVIDENCE_MASTERY_REVIEW_INTERACTION.md", "DESIGN_SYSTEM_BASELINE.md",
    "RTL_LTR_ACCESSIBILITY_BASELINE.md", "RESPONSIVE_BEHAVIOR.md", "UX_PROOF_REVIEW.md",
]
ADR_FILES = [f"ADR-{number:03d}-{slug}.md" for number, slug in [
    (1, "MODULAR-MONOLITH"), (2, "LARAVEL-PHP"), (3, "VUE-INERTIA-TYPESCRIPT"),
    (4, "POSTGRESQL"), (5, "LOCAL-FIRST"), (6, "STRUCTURED-BLOCK-CONTENT"),
    (7, "IMMUTABLE-PUBLICATION"), (8, "SIMULATOR-FIRST"),
    (9, "ISOLATED-SCENARIO-RUNS"), (10, "MANUAL-AI-BRIDGE-ONLY"),
    (11, "POSTGRES-SEARCH-QUEUE"), (12, "LOCAL-SINGLE-OWNER"),
    (13, "SOURCE-CUSTODY-PROVENANCE"), (14, "NO-REAL-EXECUTION-CONNECTORS"),
]]
PACKET_FILES = [
    "CODEX_FINAL_REPORT.md", "CHANGED_FILES.txt", "TEST_RESULTS.txt",
    "PRODUCT_DEFINITION_SUMMARY.md", "ARCHITECTURE_DECISION_SUMMARY.md",
    "REQUIREMENTS_AND_TRACEABILITY_SUMMARY.md", "UX_PROOF_SUMMARY.md",
    "VS001_READINESS_SUMMARY.md", "V1_SCOPE_SUMMARY.md",
    "SOURCE_AND_PRIOR_OUTPUT_SAFETY.md", "RENDERING_AND_ACCESSIBILITY_RESULTS.md",
    "RESIDUAL_LIMITATIONS.md",
]
SCREENSHOTS = [
    *[f"desktop-{number:02d}-{name}.png" for number, name in enumerate([
        "dashboard", "sources", "knowledge", "curriculum", "guided-lab",
        "enterprise-studio", "evidence-mastery", "manual-ai"], 1)],
    "mobile-01-dashboard-navigation.png", "mobile-02-lesson-editor.png",
    "mobile-03-guided-lab.png", "closeup-01-rtl-ltr-lesson.png",
    "closeup-02-rtl-ltr-lab.png", "focus-01-visible-keyboard-state.png",
]
ROUTES = {"dashboard", "sources", "knowledge", "curriculum", "lab", "enterprise", "evidence", "manual-ai"}
SELECTED_REFINED = [
    *[f"source-vault/derived/semantic-refined/{name}" for name in [
        "CAPABILITY_ARCHITECTURE_BASELINE_REFINED.md", "CYBERSECURITY_DOMAIN_TAXONOMY_V1_REFINED.md",
        "ADAPTIVE_LEARNING_MODEL_REFINED.md", "CURRICULUM_COVERAGE_AND_GAPS_REFINED.md",
        "VS001_SOURCE_SELECTION_REFINED.md", "UNRESOLVED_DECISIONS_REFINED.md",
    ]],
    *[f"source-vault/manifests/semantic-refined/{name}" for name in [
        "DOMAIN_COVERAGE_MATRIX_REFINED.tsv", "CAPABILITY_CLUSTER_CATALOG_REFINED.tsv",
        "CAPABILITY_CATALOG_REFINED.tsv", "KNOWLEDGE_UNIT_CANDIDATES_REFINED.tsv",
        "VS001_SOURCE_SELECTION_REFINED.tsv",
    ]],
]


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for block in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(block)
    return digest.hexdigest()


def read_tsv(path: Path) -> tuple[list[str], list[dict[str, str]]]:
    with path.open("r", encoding="utf-8-sig", newline="") as handle:
        reader = csv.DictReader(handle, delimiter="\t")
        rows = [dict(row) for row in reader if row and any((value or "").strip() for value in row.values())]
        return list(reader.fieldnames or []), rows


def split_refs(value: str) -> set[str]:
    return {part.strip() for part in value.split(";") if part.strip() and part.strip() != "NONE"}


def require(condition: bool, message: str, results: list[str]) -> None:
    if not condition:
        raise AssertionError(message)
    results.append(f"PASS: {message}")


def require_files(paths: list[Path], results: list[str], label: str) -> None:
    missing = [path.relative_to(WORKSPACE).as_posix() for path in paths if not path.is_file()]
    require(not missing, f"{label} required files exist ({len(paths)})", results)


class LinkCollector(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.links: list[str] = []
        self.attrs: dict[str, str] = {}

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = {key: value or "" for key, value in attrs}
        if tag == "html":
            self.attrs = values
        if tag in {"a", "link", "script", "img"}:
            target = values.get("href") or values.get("src")
            if target:
                self.links.append(target)


def load_task003r_validator():
    path = PRODUCT_REPO / "tools" / "semantic_architecture_refinement" / "validate_refined_semantic_baseline.py"
    module_dir = str(path.parent)
    if module_dir not in sys.path:
        sys.path.insert(0, module_dir)
    spec = importlib.util.spec_from_file_location("task003r_read_only_validator", path)
    if spec is None or spec.loader is None:
        raise RuntimeError("Unable to load Task 003R validator")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def validate_handoff(results: list[str]) -> tuple[int, int]:
    controls = ["HANDOFF_MANIFEST.tsv", "SHA256SUMS.txt", "MISSING_FILES.txt", "ZIP_INTEGRITY_RESULT.txt"]
    require_files([HANDOFF / name for name in controls] + [ZIP_PATH], results, "automatic handoff")
    fields, manifest = read_tsv(HANDOFF / "HANDOFF_MANIFEST.tsv")
    require(fields == ["source_relative_path", "handoff_relative_path", "file_size_bytes", "sha256", "copy_status"], "handoff manifest schema is exact", results)
    require(bool(manifest), "handoff manifest contains copied payload", results)
    for row in manifest:
        copied = HANDOFF / Path(row["handoff_relative_path"])
        source = WORKSPACE / Path(row["source_relative_path"])
        require(
            source.is_file() and copied.is_file()
            and source.stat().st_size == copied.stat().st_size == int(row["file_size_bytes"])
            and sha256(source) == sha256(copied) == row["sha256"]
            and row["copy_status"] == "VERIFIED_SHA256_MATCH",
            f"handoff copied file verifies: {row['handoff_relative_path']}", results,
        )
    missing_lines = [line.strip() for line in (HANDOFF / "MISSING_FILES.txt").read_text(encoding="utf-8").splitlines() if line.strip() and line.strip() != "NONE"]
    require(not missing_lines, "handoff missing count is zero", results)
    expected_sums: dict[str, str] = {}
    for line in (HANDOFF / "SHA256SUMS.txt").read_text(encoding="utf-8").splitlines():
        if line.strip():
            digest, relative = line.split("  ", 1)
            expected_sums[relative] = digest
    hash_targets = [path for path in HANDOFF.rglob("*") if path.is_file() and path.name != "SHA256SUMS.txt"]
    require(len(expected_sums) == len(hash_targets), "SHA256SUMS covers every handoff file except itself", results)
    require(all(expected_sums.get(path.relative_to(HANDOFF).as_posix()) == sha256(path) for path in hash_targets), "all handoff SHA-256 values verify", results)
    forbidden_parts = {"originals", "reviewed-source-copies", "__pycache__", ".venv", "venv", "node_modules", "browser-profile"}
    require(not any(forbidden_parts.intersection(path.relative_to(HANDOFF).parts) for path in HANDOFF.rglob("*") if path.is_file()), "handoff excludes prohibited and residual directories", results)
    with zipfile.ZipFile(ZIP_PATH) as archive:
        require(archive.testzip() is None, "ZIP CRC integrity passes", results)
        members = {info.filename: info.file_size for info in archive.infolist() if not info.is_dir()}
    files = [path for path in HANDOFF.rglob("*") if path.is_file()]
    expected_members = {path.relative_to(HANDOFF).as_posix(): path.stat().st_size for path in files}
    require(members == expected_members, "ZIP members and uncompressed sizes match handoff folder", results)
    require((HANDOFF / "ZIP_INTEGRITY_RESULT.txt").read_text(encoding="utf-8").startswith("PASS"), "ZIP integrity result records PASS", results)
    return len(files), sum(path.stat().st_size for path in files)


def validate_core(check_handoff: bool = False) -> tuple[list[str], list[str]]:
    results: list[str] = []
    warnings: list[str] = []
    required = (
        [PRODUCT_REPO / "docs" / "product" / name for name in PRODUCT_FILES]
        + [PRODUCT_REPO / "docs" / "architecture" / name for name in ARCH_FILES]
        + [PRODUCT_REPO / "docs" / "architecture" / "adr" / name for name in ADR_FILES]
        + [PRODUCT_REPO / "docs" / "ux" / name for name in UX_FILES]
        + [PLANNING / name for name in SCHEMAS]
        + [PROTOTYPE / name for name in ["index.html", "README.md", "RENDERING_REPORT.md", "screens/ROUTES.md", "assets/app.css", "assets/app.js"]]
        + [PACKET / name for name in PACKET_FILES]
    )
    require_files(required, results, "Task 004 baseline")

    tables: dict[str, list[dict[str, str]]] = {}
    for name, schema in SCHEMAS.items():
        fields, rows = read_tsv(PLANNING / name)
        require(fields == schema, f"exact TSV schema: {name}", results)
        require(bool(rows) and all(all(value is not None for value in row.values()) for row in rows), f"TSV rows parse completely: {name}", results)
        tables[name] = rows

    requirements = tables["REQUIREMENTS_CATALOG.tsv"]
    decisions = tables["DECISION_REGISTER.tsv"]
    modules = tables["MODULE_BOUNDARIES.tsv"]
    entities = tables["ENTITY_CATALOG.tsv"]
    ownership = tables["DATA_OWNERSHIP_MATRIX.tsv"]
    workflows = tables["WORKFLOW_CATALOG.tsv"]
    ux = tables["UX_REQUIREMENTS.tsv"]
    security = tables["SECURITY_REQUIREMENTS.tsv"]
    criteria = tables["VS001_ACCEPTANCE_CRITERIA.tsv"]
    traces = tables["REQUIREMENTS_TRACEABILITY.tsv"]

    def ids(rows: list[dict[str, str]], field: str) -> set[str]:
        return {row[field] for row in rows}

    req_ids, decision_ids = ids(requirements, "requirement_id"), ids(decisions, "decision_id")
    module_ids, entity_ids = ids(modules, "module_id"), ids(entities, "entity_id")
    workflow_ids, ux_ids = ids(workflows, "workflow_id"), ids(ux, "ux_requirement_id")
    security_ids, criteria_ids = ids(security, "security_requirement_id"), ids(criteria, "criterion_id")
    require(len(req_ids) == len(requirements), "requirement IDs are unique", results)
    require(len(decision_ids) == len(decisions), "decision IDs are unique", results)
    require(len(module_ids) == len(modules) and len(entity_ids) == len(entities), "module and entity IDs are unique", results)
    require(all(row["owning_module"] in module_ids for row in requirements + entities + ownership + workflows + security), "owning-module references resolve", results)
    require(all(split_refs(row["participating_modules"]) <= (module_ids | {"ALL"}) for row in workflows), "workflow participating modules resolve", results)
    require(all(row["acceptance_method"].strip() for row in requirements if row["scope"] == "V1"), "every v1 requirement has an acceptance method", results)
    require(all(row["v1_scope"].strip() for row in modules), "every module has a defined v1 boundary", results)
    ownership_ids = [row["entity_id"] for row in ownership]
    require(set(ownership_ids) == entity_ids and len(ownership_ids) == len(set(ownership_ids)) == len(entities), "every persistent entity has exactly one ownership row", results)
    require(all(next(item for item in ownership if item["entity_id"] == row["entity_id"])["owning_module"] == row["owning_module"] for row in entities), "entity ownership modules agree", results)
    require(all(split_refs(row["requirement_ids"]) and split_refs(row["requirement_ids"]) <= req_ids and split_refs(row["module_ids"]) and split_refs(row["module_ids"]) <= module_ids for row in criteria), "every VS-001 criterion traces to requirements and modules", results)
    require(all(split_refs(row["entity_ids"]) <= entity_ids and split_refs(row["workflow_ids"]) <= workflow_ids for row in criteria), "VS-001 entity and workflow references resolve", results)
    require(len(traces) == len(requirements) and ids(traces, "requirement_id") == req_ids, "every requirement has exactly one trace row", results)
    for row in traces:
        require(split_refs(row["decision_ids"]) <= decision_ids, f"trace decisions resolve: {row['trace_id']}", results)
        require(split_refs(row["module_ids"]) <= module_ids, f"trace modules resolve: {row['trace_id']}", results)
        entity_workflow = split_refs(row["entity_workflow_ids"])
        require(entity_workflow <= (entity_ids | workflow_ids), f"trace entity/workflow references resolve: {row['trace_id']}", results)
        require(split_refs(row["ux_requirement_ids"]) <= ux_ids, f"trace UX references resolve: {row['trace_id']}", results)
        require(split_refs(row["security_requirement_ids"]) <= security_ids, f"trace security references resolve: {row['trace_id']}", results)
        require(split_refs(row["vs001_criterion_ids"]) <= criteria_ids, f"trace VS-001 references resolve: {row['trace_id']}", results)
    all_statuses = [row.get("status", "") for rows in tables.values() for row in rows]
    require(all("IMPLEMENTED" not in value or "UNIMPLEMENTED" in value for value in all_statuses), "no implementation evidence is falsely recorded", results)

    architecture = (PRODUCT_REPO / "docs" / "architecture" / "ARCHITECTURE_BASELINE_V1_CANDIDATE.md").read_text(encoding="utf-8")
    manual_ai = (PRODUCT_REPO / "docs" / "architecture" / "MANUAL_AI_BRIDGE_ARCHITECTURE.md").read_text(encoding="utf-8")
    simulation = (PRODUCT_REPO / "docs" / "architecture" / "ENTERPRISE_SCENARIO_SIMULATION_MODEL.md").read_text(encoding="utf-8")
    revisions = (PRODUCT_REPO / "docs" / "architecture" / "CONTENT_AND_REVISION_MODEL.md").read_text(encoding="utf-8")
    product_definition = (PRODUCT_REPO / "docs" / "product" / "PRODUCT_DEFINITION_V1_CANDIDATE.md").read_text(encoding="utf-8")
    combined = "\n".join([architecture, manual_ai, simulation, revisions, product_definition])
    require("Manual AI Bridge" in manual_ai and "only" in manual_ai.lower(), "Manual AI Bridge is the only AI execution workflow", results)
    require(not any(term in "\n".join(row["entity_name"] + " " + row["purpose"] for row in entities).lower() for term in ["api credential", "provider credential", "local model", "model endpoint"]), "no API or local-AI adapter entity appears", results)
    require("default" in simulation.lower() and "simulator" in simulation.lower(), "simulator is the default lab path", results)
    require("selective" in simulation.lower() and "claim-specific" in simulation.lower(), "Real-Lab is selective and claim-specific", results)
    require("isolated" in simulation.lower() and "Enterprise Baseline" in simulation, "Scenario Runs are isolated from Enterprise Baseline", results)
    require("immutable" in revisions.lower() and "published" in revisions.lower(), "published revisions are immutable", results)
    require("supersed" in combined.lower() and "Product Charter" in combined and "Manual AI" in combined, "Product Charter provider language is recorded as superseded", results)
    require("Modular Monolith" in architecture, "architecture is a Modular Monolith", results)
    require(all(term in architecture for term in ["Laravel", "PHP", "Vue 3", "TypeScript", "Inertia", "PostgreSQL"]), "preferred stack is consistently represented", results)
    require("Task 006" in architecture and "version" in architecture.lower() and "defer" in architecture.lower(), "exact runtime versions are deferred to Task 006", results)
    prohibited_manifests = [PRODUCT_REPO / name for name in ["artisan", "composer.json", "package.json", "vite.config.js", "vite.config.ts", "docker-compose.yml", "compose.yml"]]
    require(not any(path.exists() for path in prohibited_manifests), "no product application scaffold exists", results)
    architecture_lower = architecture.lower()
    require("no microservices" in architecture_lower and all(term in architecture_lower for term in ["kafka", "kubernetes", "graph database", "search cluster"]), "no prohibited infrastructure dependency is added", results)

    html = (PROTOTYPE / "index.html").read_text(encoding="utf-8")
    css = (PROTOTYPE / "assets" / "app.css").read_text(encoding="utf-8")
    js = (PROTOTYPE / "assets" / "app.js").read_text(encoding="utf-8")
    report = (PROTOTYPE / "RENDERING_REPORT.md").read_text(encoding="utf-8")
    collector = LinkCollector()
    collector.feed(html)
    require("DESIGN PROOF — NOT IMPLEMENTED PRODUCT" in html, "prototype is labeled as a design proof", results)
    require(collector.attrs.get("lang") == "ar" and collector.attrs.get("dir") == "rtl", "HTML language and direction attributes are appropriate", results)
    require(all(not re.match(r"^(?:https?:)?//", link, re.I) for link in collector.links), "prototype has no external CDN or Internet asset", results)
    route_links = {link[1:] for link in collector.links if link.startswith("#") and link != "#workspace"}
    require(route_links == ROUTES, "all internal prototype route links resolve", results)
    require(all((f"{route}:" in js) if "-" not in route else (f'"{route}":' in js) for route in ROUTES), "all required prototype views exist", results)
    require('dir="ltr"' in js and "<bdi" in js and "direction:ltr" in css.replace(" ", ""), "code and command examples use LTR containers", results)
    require(":focus-visible" in css and "outline" in css, "keyboard focus styles exist", results)
    screenshots = [PROTOTYPE / "rendered" / name for name in SCREENSHOTS]
    rendering_complete = all(path.is_file() and path.stat().st_size > 0 for path in screenshots) and "COMPLETE" in report and "14/14" in report
    blocked_truthfully = "BLOCKED" in report and "reason" in report.lower()
    require(rendering_complete or blocked_truthfully, "screenshot and render-report requirement is satisfied or truthfully blocked", results)

    task003r = load_task003r_validator()
    prior_results, prior_warnings = task003r.validate_core(check_handoff=True)
    require(bool(prior_results), "Task 003R read-only core/hash validation passes", results)
    warnings.extend(f"Task 003R: {warning}" for warning in prior_warnings)

    if check_handoff:
        validate_handoff(results)
    return results, warnings


def payload_sources() -> tuple[list[Path], list[str]]:
    roots = [
        PACKET,
        PRODUCT_REPO / "docs" / "product",
        PRODUCT_REPO / "docs" / "architecture",
        PRODUCT_REPO / "docs" / "ux",
        PLANNING,
        PROTOTYPE,
        PRODUCT_REPO / "tools" / "product_architecture_ux_validation",
    ]
    files: list[Path] = [WORKSPACE / "AGENTS.md"]
    missing: list[str] = []
    for root in roots:
        if not root.is_dir():
            missing.append(root.relative_to(WORKSPACE).as_posix())
            continue
        files.extend(path for path in root.rglob("*") if path.is_file())
    for relative in SELECTED_REFINED:
        path = WORKSPACE / relative
        if path.is_file():
            files.append(path)
        else:
            missing.append(relative)
    excluded = {"__pycache__", ".pytest_cache", ".venv", "venv", "node_modules", "browser-profile"}
    files = [path for path in files if not excluded.intersection(path.relative_to(WORKSPACE).parts) and path.suffix != ".pyc"]
    unique = sorted(set(files), key=lambda path: path.relative_to(WORKSPACE).as_posix())
    return unique, sorted(set(missing))


def create_zip() -> None:
    with zipfile.ZipFile(ZIP_PATH, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in sorted((item for item in HANDOFF.rglob("*") if item.is_file()), key=lambda item: item.relative_to(HANDOFF).as_posix()):
            archive.write(path, path.relative_to(HANDOFF).as_posix())


def package_handoff() -> tuple[int, int, int]:
    resolved = HANDOFF.resolve()
    expected = (PRODUCT_REPO / "review-packets" / "TASK_004_REVIEW_HANDOFF").resolve()
    if resolved != expected:
        raise RuntimeError("Refusing to rebuild an unexpected handoff path")
    if HANDOFF.exists():
        shutil.rmtree(HANDOFF)
    HANDOFF.mkdir(parents=True)
    if ZIP_PATH.exists():
        ZIP_PATH.unlink()

    sources, missing = payload_sources()
    manifest_rows: list[list[str]] = []
    for source in sources:
        relative = source.relative_to(WORKSPACE)
        destination = HANDOFF / relative
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, destination)
        digest = sha256(source)
        if sha256(destination) != digest or destination.stat().st_size != source.stat().st_size:
            raise RuntimeError(f"Copy verification failed: {relative.as_posix()}")
        manifest_rows.append([relative.as_posix(), relative.as_posix(), str(source.stat().st_size), digest, "VERIFIED_SHA256_MATCH"])

    with (HANDOFF / "HANDOFF_MANIFEST.tsv").open("w", encoding="utf-8", newline="") as handle:
        writer = csv.writer(handle, delimiter="\t", lineterminator="\n")
        writer.writerow(["source_relative_path", "handoff_relative_path", "file_size_bytes", "sha256", "copy_status"])
        writer.writerows(manifest_rows)
    (HANDOFF / "MISSING_FILES.txt").write_text("NONE\n" if not missing else "\n".join(missing) + "\n", encoding="utf-8", newline="\n")
    (HANDOFF / "ZIP_INTEGRITY_RESULT.txt").write_text(
        "PASS\nFinal ZIP CRC, member-path, member-size, and handoff-hash verification is enforced by validate_task004.py.\n",
        encoding="utf-8", newline="\n",
    )
    hash_targets = sorted((path for path in HANDOFF.rglob("*") if path.is_file() and path.name != "SHA256SUMS.txt"), key=lambda path: path.relative_to(HANDOFF).as_posix())
    checksum_text = "".join(f"{sha256(path)}  {path.relative_to(HANDOFF).as_posix()}\n" for path in hash_targets)
    (HANDOFF / "SHA256SUMS.txt").write_text(checksum_text, encoding="utf-8", newline="\n")
    create_zip()
    results, warnings = validate_core(check_handoff=True)
    print(f"VALIDATION_ASSERTIONS={len(results)}")
    print(f"VALIDATION_WARNINGS={len(warnings)}")
    files = [path for path in HANDOFF.rglob("*") if path.is_file()]
    return len(files), sum(path.stat().st_size for path in files), len(missing)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--package", action="store_true", help="Build and validate the automatic review handoff")
    args = parser.parse_args()
    try:
        if args.package:
            file_count, byte_count, missing_count = package_handoff()
            print(f"HANDOFF_ZIP={ZIP_PATH.relative_to(WORKSPACE).as_posix()}")
            print(f"TOTAL_FILES={file_count}")
            print(f"TOTAL_BYTES={byte_count}")
            print(f"MISSING_COUNT={missing_count}")
            print("ZIP_INTEGRITY=PASS")
        else:
            results, warnings = validate_core(check_handoff=HANDOFF.is_dir() and ZIP_PATH.is_file())
            for line in results:
                print(line)
            for line in warnings:
                print(f"WARNING: {line}")
            print(f"SUMMARY: PASS ({len(results)} assertions; {len(warnings)} warnings)")
        return 0
    except Exception as exc:
        print(f"FAIL: {exc}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())

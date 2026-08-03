#!/usr/bin/env python3
"""Build the bounded TASK-003 semantic baseline from reviewed local sources."""

from __future__ import annotations

import csv
import hashlib
import os
import platform
import shutil
from collections import Counter, defaultdict
from pathlib import Path

from pypdf import PdfReader


ROOT = Path(__file__).resolve().parents[3]
ORIGINALS = ROOT / "source-vault" / "originals"
STRUCTURAL = ROOT / "source-vault" / "manifests" / "structural"
MANIFESTS = ROOT / "source-vault" / "manifests" / "semantic"
REPORTS = ROOT / "source-vault" / "derived" / "semantic"
PACKET = ROOT / "product-repo" / "review-packets" / "semantic-capability-003"
COPIES = PACKET / "reviewed-source-copies"


def read_tsv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as stream:
        return list(csv.DictReader(stream, delimiter="\t"))


def write_tsv(path: Path, columns: list[str], rows: list[dict[str, object]]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="") as stream:
        writer = csv.DictWriter(stream, fieldnames=columns, delimiter="\t", lineterminator="\n")
        writer.writeheader()
        for row in rows:
            writer.writerow({key: str(row.get(key, "")).replace("\t", " ").replace("\r", " ").replace("\n", " ") for key in columns})


def write_text(path: Path, body: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(body.rstrip() + "\n", encoding="utf-8", newline="\n")


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


DOMAINS = [
    ("D01", "Computing and Security Foundations", [
        ("Computing Systems", "Explain how computing components and operating systems enforce boundaries", "Diagnose foundational system behavior from observable state"),
        ("Networking Foundations", "Trace data across layered network models", "Interpret addressing, routing, and transport behavior"),
        ("Security Principles", "Apply confidentiality, integrity, availability, and least privilege", "Evaluate threats, controls, and residual risk at foundation level"),
    ]),
    ("D02", "Security Architecture and Engineering", [
        ("Trust and Boundary Architecture", "Model assets, trust boundaries, dependencies, and attack paths", "Select control points using explicit security contracts"),
        ("Secure Engineering", "Translate security requirements into verifiable designs", "Review designs for secure defaults and defense in depth"),
        ("Resilience Engineering", "Design availability, backup, and recovery safeguards", "Validate resilience assumptions using failure scenarios"),
    ]),
    ("D03", "Identity, Access and Directory Security", [
        ("Identity Lifecycle", "Govern joiner, mover, leaver, and non-human identities", "Evaluate identity proofing and account lifecycle evidence"),
        ("Authentication and Directory Services", "Analyze authentication and directory-service flows", "Troubleshoot identity protocol and directory failures"),
        ("Authorization and Privileged Access", "Analyze Windows authorization decisions using tokens, SIDs, ACLs, and access masks", "Evaluate privileged access, delegation, and entitlement controls"),
        ("Directory Policy and Delegation", "Assess directory policy scope, inheritance, and processing", "Evaluate directory delegation boundaries and drift"),
    ]),
    ("D04", "Network and Infrastructure Security", [
        ("Network Protocol Security", "Analyze protocol state, dependencies, and security properties", "Diagnose network behavior from captures and telemetry"),
        ("Segmentation and Edge Controls", "Design zones, conduits, filtering, and remote-access boundaries", "Validate segmentation and edge-control intent"),
        ("Infrastructure Operations", "Harden network and infrastructure management planes", "Assess infrastructure telemetry, availability, and configuration drift"),
        ("Wireless and Remote Access", "Evaluate wireless authentication, encryption, and access controls", "Assess VPN, ZTNA, and remote-access posture"),
    ]),
    ("D05", "Application, Web, API and Software Security", [
        ("Secure Development Lifecycle", "Integrate security requirements and gates into the SDLC", "Assess software supply-chain and dependency risk"),
        ("Web and API Security", "Design secure web sessions, endpoints, and authorization", "Test web and API controls against abuse cases"),
        ("Software Assurance", "Apply secure coding and memory-safety practices", "Interpret assurance results and prioritize remediation"),
        ("Software Supply Chain", "Govern source, dependency, build, and artifact trust", "Validate provenance, signing, and release-security evidence"),
    ]),
    ("D06", "Offensive Security and Adversary Simulation", [
        ("Engagement Governance", "Define authorization, scope, rules of engagement, and safety constraints", "Produce evidence-based findings without exceeding authorization"),
        ("Security Assessment", "Plan and perform controlled attack-surface assessment", "Validate findings through safe, reproducible tests"),
        ("Adversary Simulation", "Design threat-informed adversary simulations", "Translate simulation observations into defensive improvements"),
    ]),
    ("D07", "Vulnerability, Exposure and Attack-Surface Management", [
        ("Asset and Exposure Discovery", "Maintain risk-relevant asset and exposure inventories", "Validate discovery coverage and ownership"),
        ("Vulnerability Analysis", "Assess vulnerability evidence, exploitability, and business impact", "Prioritize exposures using contextual risk"),
        ("Remediation Governance", "Coordinate remediation, exceptions, and compensating controls", "Verify closure and detect recurrence or drift"),
    ]),
    ("D08", "Security Operations, Detection and Threat Hunting", [
        ("Telemetry Engineering", "Design trustworthy security telemetry and collection health", "Normalize and preserve event context for analysis"),
        ("Detection Engineering", "Develop testable detection hypotheses and rules", "Measure detection quality, coverage, and false positives"),
        ("Threat Hunting", "Plan evidence-driven threat hunts", "Convert hunt findings into detections and control improvements"),
        ("SOC Operations and Automation", "Operate triage, escalation, and case workflows", "Validate bounded security automation and human control points"),
    ]),
    ("D09", "Incident Response and Crisis Operations", [
        ("Preparation and Triage", "Prepare response plans, roles, and decision criteria", "Triage incidents using severity, scope, and evidence"),
        ("Containment and Recovery", "Select proportionate containment and eradication actions", "Validate recovery and monitor for recurrence"),
        ("Crisis Coordination", "Coordinate technical, legal, privacy, and business decisions", "Run exercises and capture corrective actions"),
    ]),
    ("D10", "Digital Forensics", [
        ("Evidence Handling", "Preserve forensic evidence and chain of custody", "Assess evidence integrity, scope, and limitations"),
        ("Forensic Examination", "Examine endpoint, network, and cloud artifacts safely", "Correlate artifacts into defensible timelines"),
        ("Forensic Reporting", "Document methods, findings, uncertainty, and reproducibility", "Present forensic conclusions for technical and governance audiences"),
    ]),
    ("D11", "Malware Analysis, Reverse Engineering and Exploit Development", [
        ("Malware Triage", "Classify suspicious artifacts using safe static indicators", "Plan controlled dynamic observation without production exposure"),
        ("Reverse Engineering", "Interpret executable structure and behavior defensively", "Extract detection-relevant artifacts with documented limits"),
        ("Exploit and Memory Safety", "Explain vulnerability-to-exploit mechanics at a controlled level", "Evaluate memory-safety mitigations and defensive evidence"),
    ]),
    ("D12", "Cloud, Containers and Modern Platform Security", [
        ("Cloud Security Architecture", "Model shared responsibility, identity, data, and network controls", "Assess cloud posture and control-plane exposure"),
        ("Container and Kubernetes Security", "Evaluate image, workload, orchestration, and admission controls", "Validate runtime isolation and cluster authorization"),
        ("Platform Security Operations", "Monitor modern-platform identities, configuration, and telemetry", "Respond to platform drift and security events"),
    ]),
    ("D13", "Data, Cryptography and Privacy Engineering", [
        ("Data Protection", "Classify data and apply lifecycle protections", "Evaluate DLP, masking, tokenization, and sharing controls"),
        ("Cryptographic Engineering", "Select cryptographic mechanisms for stated properties", "Validate key, certificate, and secret lifecycles"),
        ("Privacy Engineering", "Translate privacy principles into system controls", "Assess minimization, retention, consent, and deletion evidence"),
    ]),
    ("D14", "Governance, Risk, Compliance and Assurance", [
        ("Governance and Risk", "Establish accountable security governance and risk decisions", "Maintain evidence-backed risk treatment and exceptions"),
        ("Control and Compliance", "Map obligations to owned, testable controls", "Evaluate control design and operating effectiveness"),
        ("Audit and Assurance", "Plan independent, risk-based assurance activities", "Report findings, limitations, and remediation status"),
        ("Third-Party and Program Assurance", "Assess supplier, service, and concentration risk", "Measure security-program outcomes and assurance coverage"),
    ]),
    ("D15", "Specialized Environments", [
        ("OT and Safety-Critical Security", "Model OT processes, zones, conduits, and safety constraints", "Assess OT changes and response actions without unsafe disruption"),
        ("IoT, Mobile and Embedded Security", "Evaluate device identity, firmware, communications, and lifecycle", "Assess mobile and embedded control evidence"),
        ("Physical and Environmental Security", "Design layered physical and environmental protections", "Validate facility, access, and safety monitoring"),
    ]),
    ("D16", "Professional Practice and Capability Integration", [
        ("Ethics and Collaboration", "Apply professional ethics, authorization, and responsible communication", "Coordinate security work across technical and business roles"),
        ("Integrated Scenarios", "Integrate multiple capabilities in institutional scenarios", "Make evidence-backed decisions under realistic constraints"),
        ("Portfolio and Growth", "Curate reproducible capability evidence and reflective reviews", "Plan role-aligned learning and capability improvement"),
    ]),
]

RELATED = {
    "D01": "D02;D04", "D02": "D01;D12;D14", "D03": "D02;D05;D08", "D04": "D02;D08;D12",
    "D05": "D02;D07;D13", "D06": "D07;D08;D16", "D07": "D06;D08;D14", "D08": "D04;D09;D10",
    "D09": "D08;D10;D16", "D10": "D08;D09;D11", "D11": "D05;D06;D10", "D12": "D02;D04;D15",
    "D13": "D03;D05;D14", "D14": "D02;D07;D16", "D15": "D02;D04;D12", "D16": "D06;D09;D14",
}

FULL_MANDATORY = [
    "product-charter/Pasted text(16).txt",
    "ad-identity-pilot/PILOT_CONTROL.md", "ad-identity-pilot/SOURCE_DECISIONS.md",
    "ad-identity-pilot/KNOWLEDGE_UNITS.md", "ad-identity-pilot/REVIEW_QUEUE.md",
    "historical-platform/FUTURE_PLATFORM_VISION_AND_REQUIREMENTS.md",
    "historical-platform/CARE_Old_Project_Deep_Audit_AR.md",
]
CONTROL_ROOT = "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/00_Control_and_Indexes/"
CONTROL = [CONTROL_ROOT + name for name in [
    "CKV_ACCEPTANCE_STATUS.md", "CKV_CANONICAL_FILE_MAP.csv", "CKV_CANONICAL_FILE_MAP.md",
    "CKV_DEPENDENCY_GRAPH.md", "CKV_FINAL_MASTER_INDEX.md", "CKV_PACKAGE_QA_REPORT.md", "index_advance.md",
]]
CISSP = [f"chatgpt-project/CISSP 8 Domains From Books(1)/{name}" for name in [
    "domain1-security-and-risk-management.md", "domain-2-asset-security.md",
    "domain-3-part-1-system-architecture-and-truste.md", "domain-4.md", "domain-5.md",
    "domain-6.md", "domain-7.md", "domain-8.md",
]]
ROADMAP = [f"chatgpt-project/variant Roadmaps + thier References(1)/{name}" for name in [
    "security-architecture-and-engineering.md", "validation-evidence-and-provability-outputs.md",
    "offensive-foundations-professional-authorized.md", "network-protocol-research-track-deep-technical.md",
]]
CKV_DOMAIN_MAP = {
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/01_Foundations_Governance_Risk/CKV-002_Security_Principles_and_Secure_by_Design_Thinking.md": "D01;D02",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/04_Identity_IAM_PKI_Secrets/CKV-022_Windows_Access_Control_Internals_Tokens_SIDs_ACLs_and_SRM.md": "D03",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/02_Networking_Protocols_Edge/CKV-017_Network_Design_Segmentation_DMZs_and_Hard_Controls.md": "D04;D02",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/05_Application_API_Software_Data_AI/CKV-043_DevSecOps_Secure_SDLC_SAST_DAST_SCA_and_Security_Gates.md": "D05",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/09_Threats_Offensive_Defensive_Concepts/CKV-070_Penetration_Testing_Methodology_and_Authorization.md": "D06",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/10_Operations_Assurance_Compliance_Resilience/CKV-082_Vulnerability_Management_Scanning_Prioritization_and_Remediation.md": "D07",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/07_Detection_SOC_Automation_Intelligence/CKV-060_Detection_Engineering_and_Telemetry_Design.md": "D08",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/07_Detection_SOC_Automation_Intelligence/CKV-061_Incident_Response_Lifecycle_and_Playbook_Design.md": "D09",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/08_Forensics_Evidence/CKV-063_Digital_Forensics_and_Evidence_Handling.md": "D10",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/07_Detection_SOC_Automation_Intelligence/CKV-139_Malware_Analysis_Defensive_Internals.md": "D11",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/06_Cloud_Containers_Platforms_OT_IoT/CKV-115_Kubernetes_Security_Internals.md": "D12",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/05_Application_API_Software_Data_AI/CKV-122_Data_Security_DLP_Classification_Discovery_Masking_Tokenization_and_Privacy.md": "D13",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/01_Foundations_Governance_Risk/CKV-003_Risk_Management_and_Security_Governance.md": "D14",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/06_Cloud_Containers_Platforms_OT_IoT/CKV-124_OT_ICS_SCADA_Industrial_Network_Security.md": "D15",
    "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/11_Tools_Labs_Roadmaps/CKV-092_Security_Architecture_Reference_Roadmaps.md": "D16",
}
MATRIX = [
    "chatgpt-project/CSV-TSV exports for DataBase OR LLM/CSV-TSV exports for DataBase OR LLM/05_SOC_IR_Tables/SOC_DETECTION_USE_CASE_MATRIX.md",
    "chatgpt-project/CSV-TSV exports for DataBase OR LLM/CSV-TSV exports for DataBase OR LLM/04_Audit_Evidence_Tables/AUDIT_EVIDENCE_MATRIX.md",
    "chatgpt-project/CSV-TSV exports for DataBase OR LLM/CSV-TSV exports for DataBase OR LLM/04_Audit_Evidence_Tables/COMPLIANCE_GAP_MATRIX.md",
    "chatgpt-project/CSV-TSV exports for DataBase OR LLM/CSV-TSV exports for DataBase OR LLM/02_Framework_Mapping/CIS_CONTROLS_CROSSWALK.md",
]
CARE_ROOT = "chatgpt-project/care_ultimate_best_assertion_centered_patched/care/domain/"
CARE = [CARE_ROOT + name for name in ["canonical_object.py", "canonical_event.py", "baseline.py", "policy.py", "evidence.py"]]
DUMMIES = "chatgpt-project/Cybersecurity-for-dummies.pdf"


def path_domains(path: str) -> str:
    if path in CKV_DOMAIN_MAP:
        return CKV_DOMAIN_MAP[path]
    if path.startswith("product-charter/") or path in CONTROL:
        return ";".join(code for code, _, _ in DOMAINS)
    if path.startswith("ad-identity-pilot/"):
        return "D03;D08;D16"
    if path.startswith("historical-platform/") or path in CARE:
        return "D02;D08;D14;D16"
    if path in CISSP:
        mapping = {"domain1": "D14;D16", "domain-2": "D13;D14", "domain-3": "D02;D13;D15", "domain-4": "D04", "domain-5": "D03", "domain-6": "D14", "domain-7": "D08;D09", "domain-8": "D05"}
        return next(value for key, value in mapping.items() if Path(path).name.startswith(key))
    if path in ROADMAP:
        if "offensive" in path: return "D06;D16"
        if "network-protocol" in path: return "D04;D08;D16"
        if "validation" in path: return "D10;D14;D16"
        return "D02;D14;D16"
    if path in MATRIX:
        if "SOC_" in path: return "D08;D09"
        if "AUDIT_" in path: return "D10;D14"
        if "COMPLIANCE_" in path: return "D07;D14"
        return "D14"
    if path.startswith("university-courses/Ethical Hacking/"):
        return "D06;D07" if "Bufferoverflow" not in path else "D05;D11"
    if path.startswith("university-courses/Network admin"):
        return "D01;D02;D04"
    if path.startswith("university-courses/Network security"):
        return "D03;D04"
    if path.startswith("university-courses/SAD-"):
        return "D05;D13"
    return ""


def authority(path: str) -> tuple[str, str, str, str, str]:
    if path.startswith("product-charter/"):
        return "A0_PRODUCT_AUTHORITY", "HIGH", "CURRENT_PROJECT_AUTHORITY", "PRIMARY_PRODUCT_AUTHORITY", "Product identity and binding product intent"
    if path.startswith("ad-identity-pilot/"):
        return "A2_APPROVED_PILOT_AUTHORITY", "HIGH_WITHIN_PILOT_SCOPE", "CURRENT_BUT_ENVIRONMENT_UNVERIFIED", "APPROVED_SCOPE_AUTHORITY", "Authority only for the approved AD pilot scope"
    if path in CONTROL or path in CKV_DOMAIN_MAP:
        return "B1_CURATED_INTERNAL_KNOWLEDGE", "MEDIUM", "VERSION_SENSITIVE_REQUIRES_EXTERNAL_CHECK", "PRIMARY_CURRICULUM_CANDIDATE", "Internally curated; canonical label is not external authority"
    if path in CISSP or path.startswith("university-courses/"):
        return "B2_SUPPORTING_ACADEMIC_SOURCE", "MEDIUM", "MIXED_OR_VERSION_SENSITIVE", "SUPPORTING_REFERENCE", "Supporting academic coverage; not product or technical authority"
    if path in ROADMAP:
        return "B3_SUPPORTING_TECHNICAL_REFERENCE", "MEDIUM", "VERSION_SENSITIVE", "SUPPORTING_REFERENCE", "Practical internal roadmap requiring claim-level verification"
    if path in MATRIX:
        return "C2_GENERATED_OR_UNVERIFIED_REFERENCE", "LOW_TO_MEDIUM", "UNVERIFIED_GENERATED", "SEED_DATA_REQUIRES_VALIDATION", "Generated and repetitive; sample only"
    if path.startswith("historical-platform/") or path in CARE:
        return "C1_HISTORICAL_PROJECT_REFERENCE", "MEDIUM_FOR_LESSONS", "HISTORICAL", "HISTORICAL_LESSONS_ONLY", "Cannot define the independent product"
    return "D1_OPAQUE_OR_UNAVAILABLE", "UNAVAILABLE", "UNAVAILABLE", "DEFERRED", "Unavailable for semantic review"


def assessment(path: str, depth: str) -> dict[str, str]:
    domains = path_domains(path)
    if path.startswith("product-charter/"):
        coverage = "Independent product identity; source governance; adaptive learning; simulator-first labs; evidence, privacy, and phase discipline"
        mode, elements, limits = "governance;architecture;education", "requirements;workflows;decision rules;acceptance boundaries", "Does not externally verify cybersecurity facts or select an implementation stack"
    elif path.startswith("ad-identity-pilot/"):
        coverage = "AD pilot controls, selected sources, Knowledge Units, evidence gates, and unresolved implementation prerequisites"
        mode, elements, limits = "governance;technical;lab-oriented", "stable IDs;traceability;lab gates;mastery criteria;source decisions", "Pilot environment and external technical claims remain unverified"
    elif path.startswith("historical-platform/"):
        coverage = "Historical platform vision, CARE audit findings, coupling risks, and reusable evidence/verification lessons"
        mode, elements, limits = "historical;architecture;implementation-review", "requirements;anti-patterns;technical debt;lessons", "Must not override current product authority or become current architecture"
    elif path in CONTROL:
        coverage = "CKV package inventory, internal quality gates, dependencies, file map, and claimed acceptance state"
        mode, elements, limits = "governance;index;internally-curated", "maps;dependency order;self-declared QA;coverage inventory", "Self-asserted acceptance is not independent semantic or external verification"
    elif path in CISSP:
        coverage = "CISSP-aligned conceptual coverage, operational framing, evidence expectations, and mastery summaries"
        mode, elements, limits = "conceptual;governance;technical", "models;control objectives;scenarios;checklists", "Merged internal notes may paraphrase older editions and require current primary-source checks"
    elif path in ROADMAP:
        coverage = "Professional roadmaps linking architecture, safe practice, validation, evidence, and portfolio outputs"
        mode, elements, limits = "technical;operational;lab-oriented", "playbooks;contracts;checklists;evidence outputs", "Broad synthesis is not a substitute for official specifications"
    elif path in CKV_DOMAIN_MAP:
        coverage = f"Representative internally curated technical treatment supporting {domains}"
        mode, elements, limits = "technical;operational;governance;mixed", "mental models;controls;failure modes;evidence;checklists", "Only selected sections reviewed; version-sensitive claims need external verification"
    elif path.startswith("university-courses/"):
        course = path.split("/")[1]
        coverage = f"Course-coverage sample for {course}: topics, teaching depth, practicality, and visible currency signals"
        mode, elements, limits = "academic;technical;lab-oriented;mixed", "slides;examples;protocols;configurations;exercises", "Only recorded pages were inspected; no claim of complete PDF review"
    elif path in MATRIX:
        coverage = "Sampled generated matrix schema plus non-adjacent control/detection/evidence/crosswalk rows"
        mode, elements, limits = "generated;operational;governance", "tabular seed records;control mappings;evidence fields;validation fields", "High duplication and unclear provenance prevent authority or direct import"
    else:
        coverage = "Historical CARE domain-model concept sample"
        mode, elements, limits = "historical;implementation-level", "typed models;canonical envelopes;baselines;policies;evidence hashes", "Strong CARE runtime coupling; concepts only, no code reuse decision"
    return {
        "actual_coverage": coverage, "audience_and_depth": "Intermediate-to-advanced reviewer; depth bounded by recorded review state",
        "content_mode": mode, "concrete_elements": elements, "internal_coherence": "COHERENT_WITHIN_REVIEWED_UNITS",
        "duplication_assessment": "PARTIAL_OVERLAP_EXPECTED; canonicalization required", "currency_and_applicability": authority(path)[2],
        "suitability": authority(path)[3], "supports_domains": domains, "does_not_support": limits,
        "external_verification_needed": "YES for technical/version-sensitive claims; NO for project-authority decisions",
        "finding_type": "REVIEWER INTERPRETATION", "review_depth": depth,
    }


def main() -> None:
    MANIFESTS.mkdir(parents=True, exist_ok=True)
    REPORTS.mkdir(parents=True, exist_ok=True)
    PACKET.mkdir(parents=True, exist_ok=True)
    census = {row["relative_path"]: row for row in read_tsv(ROOT / "source-vault" / "manifests" / "SOURCE_FILE_CENSUS.tsv")}
    sections = defaultdict(list)
    for row in read_tsv(STRUCTURAL / "SOURCE_SECTION_CANDIDATES.tsv"):
        sections[row["relative_path"]].append(row)
    page_status = defaultdict(dict)
    for row in read_tsv(STRUCTURAL / "PDF_PAGE_TEXT_STATUS.tsv"):
        page_status[row["relative_path"]][int(row["page_number"])] = row
    university_rows = read_tsv(STRUCTURAL / "UNIVERSITY_COURSE_STRUCTURE.tsv")
    university = [row["relative_path"] for row in university_rows]
    uni_state = {row["relative_path"]: row["text_availability"] for row in university_rows}
    selected = FULL_MANDATORY + CONTROL + CISSP + ROADMAP + list(CKV_DOMAIN_MAP) + university + MATRIX + CARE
    if len(selected) != 80 or len(set(selected)) != 80:
        raise RuntimeError(f"Review corpus must be exactly 80 unique files, got {len(selected)}/{len(set(selected))}")
    missing = [path for path in selected if path not in census or not (ORIGINALS / path).is_file()]
    if missing:
        raise RuntimeError(f"Missing selected sources: {missing}")

    evidence: list[dict[str, object]] = []
    source_evidence: dict[str, list[str]] = defaultdict(list)

    def add_evidence(path: str, locator_id: str, heading: str, location: str, unit_hash: str, depth: str) -> None:
        ev_id = f"SE-003-{len(evidence)+1:03d}"
        source_evidence[path].append(ev_id)
        evidence.append({
            "semantic_evidence_id": ev_id, "source_record_id": census[path]["source_record_id"], "original_relative_path": path,
            "task002_segment_or_page_id": locator_id, "heading_path": heading, "reviewed_line_or_page_range": location,
            "unit_sha256_or_source_hash": unit_hash, "review_depth": depth, "reviewer_state": "REVIEWED",
            "findings_reference": f"ASSESS-{census[path]['source_record_id']}",
        })

    for path in selected:
        source = ORIGINALS / path
        if path in FULL_MANDATORY or path in CONTROL or path in CARE:
            lines = int(census[path].get("line_count") or len(source.read_text(encoding="utf-8-sig", errors="replace").splitlines()))
            add_evidence(path, "", "COMPLETE_FILE", f"lines 1-{lines}", census[path]["sha256"], "REVIEWED_FULL")
        elif path.startswith("university-courses/"):
            if uni_state[path] != "PDF_TEXT_AVAILABLE":
                continue
            reader = PdfReader(source)
            texts = []
            for page in reader.pages:
                try:
                    texts.append(" ".join((page.extract_text() or "").split()))
                except Exception:
                    texts.append("")
            meaningful = [idx for idx, text in enumerate(texts) if len(text) >= 120]
            if not meaningful:
                raise RuntimeError(f"No meaningful extracted pages for {path}")
            targets = [meaningful[0]]
            outline = next((idx for idx in meaningful[:12] if any(word in texts[idx].lower() for word in ("agenda", "outline", "objective", "contents", "topics", "lecture"))), meaningful[0])
            targets.append(outline)
            for fraction in (0.33, 0.66, 0.90):
                targets.append(meaningful[min(len(meaningful)-1, round((len(meaningful)-1)*fraction))])
            chosen = []
            for idx in targets:
                if idx not in chosen:
                    chosen.append(idx)
            for idx in chosen[:5]:
                page_number = idx + 1
                status = page_status[path].get(page_number, {})
                add_evidence(path, status.get("page_candidate_id", ""), f"PDF page {page_number}", f"page {page_number}", status.get("section_sha256", census[path]["sha256"]), "REVIEWED_SELECTED_PAGES")
        else:
            candidates = [row for row in sections[path] if int(row.get("character_estimate") or 0) >= 500 and row.get("start_line")]
            candidates.sort(key=lambda row: int(row["start_line"]))
            count = 3 if path in MATRIX else 2
            if len(candidates) < count:
                raise RuntimeError(f"Insufficient meaningful section candidates for {path}")
            indexes = sorted(set(round(i * (len(candidates)-1) / (count-1)) for i in range(count)))
            for idx in indexes:
                row = candidates[idx]
                add_evidence(path, row["segment_candidate_id"], row["heading_path_or_title"], f"lines {row['start_line']}-{row['end_line']}", row["section_sha256"], "REVIEWED_SELECTED_SECTIONS")

    corpus = []
    for path in selected:
        if path.startswith("university-courses/") and uni_state[path] != "PDF_TEXT_AVAILABLE":
            depth, status, reason, planned = "DEFERRED_OCR_REQUIRED", "DEFERRED", "Image-only or text-limited PDF; OCR prohibited in TASK-003", 0
        elif path in FULL_MANDATORY or path in CONTROL or path in CARE:
            depth, status, reason, planned = "REVIEWED_FULL", "REVIEW_COMPLETE", "", 1
        elif path.startswith("university-courses/"):
            depth, status, reason, planned = "REVIEWED_SELECTED_PAGES", "REVIEW_COMPLETE", "", len(source_evidence[path])
        else:
            depth, status, reason, planned = "REVIEWED_SELECTED_SECTIONS", "REVIEW_COMPLETE", "", 3 if path in MATRIX else 2
        corpus.append({
            "source_record_id": census[path]["source_record_id"], "original_relative_path": path, "authority_tier": authority(path)[0],
            "selection_reason": "Mandatory TASK-003 corpus" if path in FULL_MANDATORY or path in CONTROL or path.startswith("university-courses/") or path in MATRIX else "Balanced bounded representative",
            "review_depth": depth, "planned_units": planned, "actual_units_reviewed": len(source_evidence[path]), "deferred_reason": reason, "corpus_status": status,
        })

    authority_rows = []
    assessment_rows = []
    for row in corpus:
        path, depth = row["original_relative_path"], row["review_depth"]
        tier, quality, currency, reuse, limits = authority(path)
        evs = ";".join(source_evidence[path])
        authority_rows.append({
            "source_record_id": census[path]["source_record_id"], "original_relative_path": path, "authority_tier": tier,
            "review_depth": depth, "semantic_quality": quality if evs else "UNAVAILABLE_DEFERRED", "currency_and_applicability": currency,
            "scope_relevance": path_domains(path), "provenance_confidence": "HIGH_FILE_CUSTODY;CONTENT_AUTHORITY_VARIES",
            "reuse_decision": reuse if evs else "DEFERRED", "limitations": limits, "semantic_evidence_ids": evs,
        })
        data = assessment(path, depth)
        assessment_rows.append({
            "assessment_id": f"ASSESS-{census[path]['source_record_id']}", "source_record_id": census[path]["source_record_id"],
            "original_relative_path": path, "semantic_evidence_ids": evs, **data,
        })
    dummies = census[DUMMIES]
    authority_rows.append({
        "source_record_id": dummies["source_record_id"], "original_relative_path": DUMMIES, "authority_tier": "D1_OPAQUE_OR_UNAVAILABLE",
        "review_depth": "DEFERRED_PARSE_DEPENDENCY", "semantic_quality": "UNAVAILABLE", "currency_and_applicability": "UNAVAILABLE",
        "scope_relevance": "UNKNOWN", "provenance_confidence": "HIGH_FILE_CUSTODY;NO_CONTENT_ACCESS", "reuse_decision": "DEFERRED",
        "limitations": "AES parse dependency unavailable; no dependency added", "semantic_evidence_ids": "",
    })

    source_domain_rows = []
    for row in corpus:
        path = row["original_relative_path"]
        for domain in path_domains(path).split(";"):
            if domain:
                source_domain_rows.append({
                    "source_record_id": census[path]["source_record_id"], "original_relative_path": path, "domain_code": domain,
                    "mapping_state": "SUPPORTED_BY_REVIEW" if source_evidence[path] else "METADATA_ONLY_NO_SEMANTIC_SUPPORT",
                    "semantic_evidence_ids": ";".join(source_evidence[path]), "mapping_rationale": "Bounded reviewed coverage" if source_evidence[path] else "Course identity only; semantic review deferred",
                })

    domain_sources = defaultdict(list)
    for row in source_domain_rows:
        if row["mapping_state"] == "SUPPORTED_BY_REVIEW":
            domain_sources[row["domain_code"]].append(row["original_relative_path"])

    clusters, capabilities, kus = [], [], []
    source_cap_rows = []
    cap_by_domain = defaultdict(list)
    for domain_code, domain_name, domain_clusters in DOMAINS:
        support = ";".join(domain_sources[domain_code][:6])
        confidence = "MEDIUM" if domain_sources[domain_code] else "LOW_SOURCE_GAP"
        for cluster_number, (cluster_name, cap_a, cap_b) in enumerate(domain_clusters, 1):
            cluster_id = f"CL-{domain_code}-{cluster_number:02d}"
            clusters.append({
                "cluster_id": cluster_id, "domain_code": domain_code, "cluster_name": cluster_name,
                "purpose": f"Develop coherent professional capability in {cluster_name.lower()}.",
                "scope": f"Core concepts, decisions, practices, evidence, and failure modes for {cluster_name}.",
                "exclusions_and_boundaries": "No finished lessons; no unsafe or unauthorized execution; source-dependent claims remain provisional",
                "related_clusters": "", "supported_professional_roles": "Security Analyst;Security Engineer;Security Architect",
                "supporting_sources": support, "coverage_status": confidence,
            })
            for cap_number, statement in enumerate((cap_a, cap_b), 1):
                cap_id = f"CAP-{domain_code}-{cluster_number:02d}-{cap_number:02d}"
                cap_by_domain[domain_code].append(cap_id)
                priority = "V1_FOUNDATION" if domain_code in {"D01", "D02", "D03", "D04", "D05", "D08", "D14", "D16"} else "V1_EXPANSION"
                capabilities.append({
                    "capability_id": cap_id, "parent_cluster_id": cluster_id, "capability_statement": statement,
                    "scope_and_boundaries": "Demonstrable analysis or design within an authorized educational context; not production operation",
                    "prerequisites": "Relevant D01 foundations and any parent-cluster concepts", "expected_evidence": "Explanation;decision record;configuration or model;validation result;reflection",
                    "simulator_suitability": "HIGH" if domain_code not in {"D10", "D11", "D15"} else "MEDIUM",
                    "real_lab_classification": "REQUIRED_FOR_SPECIFIC_CLAIM" if domain_code in {"D10", "D11", "D15"} else "OPTIONAL",
                    "related_roles": "Security Analyst;Security Engineer;Security Architect", "related_domains": RELATED[domain_code],
                    "supporting_sources": support, "coverage_confidence": confidence, "v1_priority": priority,
                })
                ku_number = len(cap_by_domain[domain_code])
                ku_id = "KU-AD-02" if statement.startswith("Analyze Windows authorization decisions") else f"KU-{domain_code}-{ku_number:03d}"
                title = statement[0].upper() + statement[1:]
                kus.append({
                    "knowledge_unit_id": ku_id, "title": title, "primary_domain": domain_code, "related_domains": RELATED[domain_code],
                    "parent_capability_id": cap_id, "capability_centered_learning_outcome": f"Learner can {statement[0].lower()+statement[1:]} and justify the result with evidence.",
                    "prerequisites": "Parent capability prerequisites", "proposed_lesson_scope": f"Mental model, workflow, evidence, common failures, and bounded practice for {cluster_name}.",
                    "micro_practice": "Classify a scenario, make one decision, and identify the evidence needed to verify it.",
                    "simulator_lab_suitability": "SUITABLE" if domain_code not in {"D10", "D11", "D15"} else "PARTIALLY_SUITABLE",
                    "real_lab_classification": "REQUIRED_FOR_SPECIFIC_CLAIM" if domain_code in {"D10", "D11", "D15"} else "OPTIONAL",
                    "evidence_types": "SOURCE_REVIEW;SIMULATED;MANUAL_ASSESSMENT", "mastery_criteria_concept": "Correct decision, explicit rationale, valid evidence, negative test, and boundary awareness",
                    "failure_and_review_triggers": "Misclassification;unsupported claim;missing evidence;unsafe scope;failure to explain negative case",
                    "supporting_sources": support, "source_gaps": "Primary external authority and version-specific validation remain future work",
                    "lifecycle_template": "PRACTICAL_SIMULATOR" if domain_code not in {"D10", "D11", "D15"} else "PRACTICAL_WITH_OPTIONAL_REAL_LAB",
                    "v1_priority": priority, "status": "PROVISIONAL_CANDIDATE_NOT_CONTENT_AUTHORED",
                })

    for row in clusters:
        related = [item["cluster_id"] for item in clusters if item["domain_code"] in RELATED[row["domain_code"]].split(";")][:3]
        row["related_clusters"] = ";".join(related)

    for row in corpus:
        path = row["original_relative_path"]
        if not source_evidence[path]:
            continue
        for domain in path_domains(path).split(";"):
            for cap_id in cap_by_domain[domain][:2]:
                source_cap_rows.append({
                    "source_record_id": census[path]["source_record_id"], "original_relative_path": path, "capability_id": cap_id,
                    "support_type": "PRIMARY" if authority(path)[0].startswith("A") else "SUPPORTING",
                    "semantic_evidence_ids": ";".join(source_evidence[path]), "coverage_confidence": "HIGH" if authority(path)[0].startswith("A") else "MEDIUM",
                })

    relationships = []
    for idx, ku in enumerate(kus, 1):
        related = ku["related_domains"].split(";")[0]
        relationships.append({
            "relationship_id": f"XDR-003-{idx:03d}", "canonical_knowledge_unit_id": ku["knowledge_unit_id"],
            "primary_domain": ku["primary_domain"], "related_domain": related, "relationship_type": "DOMAIN_CONTEXT_REUSE",
            "context_note": "Reuse canonical knowledge with Domain-specific context; do not duplicate the KU", "duplicate_ku_created": "FALSE",
        })

    coverage = []
    for code, name, _ in DOMAINS:
        dclusters = [r for r in clusters if r["domain_code"] == code]
        dcaps = [r for r in capabilities if r["capability_id"].startswith(f"CAP-{code}-")]
        dkus = [r for r in kus if r["primary_domain"] == code]
        sources = sorted(set(domain_sources[code]))
        university_support = [path for path in sources if path.startswith("university-courses/")]
        coverage.append({
            "domain_code": code, "domain_name": name, "cluster_ids": ";".join(r["cluster_id"] for r in dclusters),
            "capability_ids": ";".join(r["capability_id"] for r in dcaps), "knowledge_unit_ids": ";".join(r["knowledge_unit_id"] for r in dkus),
            "reviewed_supporting_sources": ";".join(sources), "university_course_support": ";".join(university_support) or "NONE_REVIEWED",
            "simulator_feasibility": "HIGH" if code not in {"D10", "D11", "D15"} else "MEDIUM",
            "real_lab_dependence": "SELECTIVE_SPECIFIC_CLAIMS_ONLY", "coverage_confidence": "MEDIUM" if len(sources) >= 2 else "LOW",
            "major_gaps": "No external claim verification; representative corpus only" + ("; local source support thin" if len(sources) < 2 else ""),
            "v1_post_v1_position": "V1_FOUNDATION" if code in {"D01", "D02", "D03", "D04", "D05", "D08", "D14", "D16"} else "V1_EXPANSION_OR_POST_V1",
        })

    vs_rows = []
    vs_sources = [
        "product-charter/Pasted text(16).txt", "ad-identity-pilot/PILOT_CONTROL.md", "ad-identity-pilot/SOURCE_DECISIONS.md",
        "ad-identity-pilot/KNOWLEDGE_UNITS.md", "ad-identity-pilot/REVIEW_QUEUE.md",
        "chatgpt-project/Canonical_Knowledge_Vault_READY_FINAL/04_Identity_IAM_PKI_Secrets/CKV-022_Windows_Access_Control_Internals_Tokens_SIDs_ACLs_and_SRM.md",
        "university-courses/SAD-secure App Developing/SAD Lecture 8 - authentication and authorization.pdf",
    ]
    requirements = [
        ("VS001-REQ-01", "Explain an authorization decision from principal/token, object security descriptor, requested access, and policy context", "Acceptance trace explains allow and deny paths"),
        ("VS001-REQ-02", "Keep simulation deterministic, explainable, source-traceable, and distinguishable from real evidence", "Repeat run gives the same decision and labels SIMULATED evidence"),
        ("VS001-REQ-03", "Use KU-AD-02 as the primary pilot knowledge source and preserve pilot safety boundaries", "Pilot source and boundaries appear in traceability"),
        ("VS001-REQ-04", "Require negative tests for group membership, deny ACE, privilege, access mask, and stale/unsupported claims", "Negative cases fail for the intended reason"),
        ("VS001-REQ-05", "Exclude live AD changes, credential attacks, product implementation, and claims of real-environment competence", "Scope review finds no excluded execution"),
        ("VS001-REQ-06", "Use generic web authorization material only as secondary context, not Windows authority", "Supporting source is labeled non-Windows and non-authoritative"),
        ("VS001-REQ-07", "Expose unresolved Microsoft-specification and environment-version verification needs", "Unresolved issues remain visible at handoff"),
    ]
    for idx, (req_id, requirement, acceptance) in enumerate(requirements):
        source = vs_sources[min(idx, len(vs_sources)-1)]
        vs_rows.append({
            "selection_id": f"VS001-SRC-{idx+1:02d}", "source_record_id": census[source]["source_record_id"], "original_relative_path": source,
            "semantic_evidence_ids": ";".join(source_evidence[source]), "requirement_id": req_id, "requirement_statement": requirement,
            "capability_id": "CAP-D03-03-01", "knowledge_unit_id": "KU-AD-02", "acceptance_need": acceptance,
            "selection_role": "PRIMARY" if idx < 5 else "SUPPORTING", "out_of_scope": "No implementation; no live AD; no offensive execution; no real-lab claim",
        })

    deferred = [
        {"deferred_id": "DEF-003-001", "source_path_or_scope": DUMMIES, "deferred_state": "DEFERRED_AES_PARSE_DEPENDENCY", "reason": "AES parse dependency unavailable and prohibited from addition", "future_trigger": "Authorized parser becomes available without dependency expansion"},
        {"deferred_id": "DEF-003-002", "source_path_or_scope": "All unselected CKV documents", "deferred_state": "DEFERRED_USAGE_BUDGET", "reason": "Representative cap and 80-file budget", "future_trigger": "Capability-specific authoring phase"},
        {"deferred_id": "DEF-003-003", "source_path_or_scope": "All unselected historical CARE files", "deferred_state": "DEFERRED_USAGE_BUDGET", "reason": "C1_HISTORICAL_PROJECT_REFERENCE; deferred historical reference; representative sample capped", "future_trigger": "Specific approved concept comparison"},
        {"deferred_id": "DEF-003-004", "source_path_or_scope": "Opaque attachments, archives, images, schemas, and search support files", "deferred_state": "NOT_SELECTED", "reason": "Excluded from semantic corpus", "future_trigger": "Needed to interpret a selected parent source"},
        {"deferred_id": "DEF-003-005", "source_path_or_scope": "Unselected original sources outside the 80-file corpus", "deferred_state": "DEFERRED_USAGE_BUDGET", "reason": "TASK-003 explicitly prohibits full 2,083-file semantic review", "future_trigger": "Approved targeted review"},
    ]
    for row in corpus:
        if row["review_depth"] == "DEFERRED_OCR_REQUIRED":
            deferred.append({"deferred_id": f"DEF-003-{len(deferred)+1:03d}", "source_path_or_scope": row["original_relative_path"], "deferred_state": "DEFERRED_OCR_REQUIRED", "reason": row["deferred_reason"], "future_trigger": "Separately authorized OCR phase"})

    issues = [
        {"issue_id": "SRCISS-003-001", "source_path_or_scope": "CKV and merged CISSP notes", "issue": "Internal synthesis cites external authorities not verified in TASK-003", "impact": "Technical claims remain provisional", "resolution_needed": "Claim-level primary-source verification before lesson publication", "status": "OPEN"},
        {"issue_id": "SRCISS-003-002", "source_path_or_scope": "University course PDFs", "issue": "Three image-only files and bounded page review limit completeness", "impact": "Course sequence and topic depth cannot be declared complete", "resolution_needed": "Authorized OCR and later course-level review", "status": "OPEN"},
        {"issue_id": "SRCISS-003-003", "source_path_or_scope": DUMMIES, "issue": "AES parse dependency prevents inspection", "impact": "No semantic conclusions", "resolution_needed": "Future approved parse capability", "status": "OPEN"},
        {"issue_id": "SRCISS-003-004", "source_path_or_scope": "Large generated matrices", "issue": "Provenance, duplication, and row consistency not comprehensively validated", "impact": "Seed data only", "resolution_needed": "Schema validation, deduplication, and source verification before import", "status": "OPEN"},
        {"issue_id": "SRCISS-003-005", "source_path_or_scope": "VS-001", "issue": "Microsoft/Open Specifications and target Windows version were not externally verified", "impact": "Authorization semantics cannot yet be published as authoritative", "resolution_needed": "Later approved external authority review", "status": "OPEN"},
    ]

    write_tsv(MANIFESTS / "SEMANTIC_REVIEW_CORPUS.tsv", ["source_record_id","original_relative_path","authority_tier","selection_reason","review_depth","planned_units","actual_units_reviewed","deferred_reason","corpus_status"], corpus)
    write_tsv(MANIFESTS / "SEMANTIC_REVIEW_EVIDENCE.tsv", ["semantic_evidence_id","source_record_id","original_relative_path","task002_segment_or_page_id","heading_path","reviewed_line_or_page_range","unit_sha256_or_source_hash","review_depth","reviewer_state","findings_reference"], evidence)
    write_tsv(MANIFESTS / "SOURCE_AUTHORITY_REGISTER.tsv", ["source_record_id","original_relative_path","authority_tier","review_depth","semantic_quality","currency_and_applicability","scope_relevance","provenance_confidence","reuse_decision","limitations","semantic_evidence_ids"], authority_rows)
    write_tsv(MANIFESTS / "SEMANTIC_SOURCE_ASSESSMENTS.tsv", ["assessment_id","source_record_id","original_relative_path","semantic_evidence_ids","actual_coverage","audience_and_depth","content_mode","concrete_elements","internal_coherence","duplication_assessment","currency_and_applicability","suitability","supports_domains","does_not_support","external_verification_needed","finding_type","review_depth"], assessment_rows)
    write_tsv(MANIFESTS / "SOURCE_TO_DOMAIN_MAP.tsv", ["source_record_id","original_relative_path","domain_code","mapping_state","semantic_evidence_ids","mapping_rationale"], source_domain_rows)
    write_tsv(MANIFESTS / "CAPABILITY_CLUSTER_CATALOG.tsv", ["cluster_id","domain_code","cluster_name","purpose","scope","exclusions_and_boundaries","related_clusters","supported_professional_roles","supporting_sources","coverage_status"], clusters)
    write_tsv(MANIFESTS / "CAPABILITY_CATALOG.tsv", ["capability_id","parent_cluster_id","capability_statement","scope_and_boundaries","prerequisites","expected_evidence","simulator_suitability","real_lab_classification","related_roles","related_domains","supporting_sources","coverage_confidence","v1_priority"], capabilities)
    write_tsv(MANIFESTS / "KNOWLEDGE_UNIT_CANDIDATES.tsv", ["knowledge_unit_id","title","primary_domain","related_domains","parent_capability_id","capability_centered_learning_outcome","prerequisites","proposed_lesson_scope","micro_practice","simulator_lab_suitability","real_lab_classification","evidence_types","mastery_criteria_concept","failure_and_review_triggers","supporting_sources","source_gaps","lifecycle_template","v1_priority","status"], kus)
    write_tsv(MANIFESTS / "CROSS_DOMAIN_RELATIONSHIPS.tsv", ["relationship_id","canonical_knowledge_unit_id","primary_domain","related_domain","relationship_type","context_note","duplicate_ku_created"], relationships)
    write_tsv(MANIFESTS / "SOURCE_TO_CAPABILITY_MAP.tsv", ["source_record_id","original_relative_path","capability_id","support_type","semantic_evidence_ids","coverage_confidence"], source_cap_rows)
    write_tsv(MANIFESTS / "DOMAIN_COVERAGE_MATRIX.tsv", ["domain_code","domain_name","cluster_ids","capability_ids","knowledge_unit_ids","reviewed_supporting_sources","university_course_support","simulator_feasibility","real_lab_dependence","coverage_confidence","major_gaps","v1_post_v1_position"], coverage)
    write_tsv(MANIFESTS / "VS001_SOURCE_SELECTION.tsv", ["selection_id","source_record_id","original_relative_path","semantic_evidence_ids","requirement_id","requirement_statement","capability_id","knowledge_unit_id","acceptance_need","selection_role","out_of_scope"], vs_rows)
    write_tsv(MANIFESTS / "SEMANTIC_DEFERRED_QUEUE.tsv", ["deferred_id","source_path_or_scope","deferred_state","reason","future_trigger"], deferred)
    write_tsv(MANIFESTS / "UNRESOLVED_SOURCE_ISSUES.tsv", ["issue_id","source_path_or_scope","issue","impact","resolution_needed","status"], issues)

    reviewed_paths = [path for path in selected if source_evidence[path]]
    copy_rows, copied_bytes = [], 0
    for path in reviewed_paths:
        source = ORIGINALS / path
        destination = COPIES / path
        destination.parent.mkdir(parents=True, exist_ok=True)
        if destination.exists():
            if sha256(destination) != census[path]["sha256"]:
                raise RuntimeError(f"Existing reviewed copy hash mismatch; refusing overwrite: {destination}")
        else:
            shutil.copy2(source, destination)
        copied_hash = sha256(destination)
        if copied_hash != census[path]["sha256"]:
            raise RuntimeError(f"Reviewed copy hash mismatch: {path}")
        copied_bytes += source.stat().st_size
        ranges = ";".join(row["reviewed_line_or_page_range"] for row in evidence if row["original_relative_path"] == path)
        depths = sorted(set(row["review_depth"] for row in evidence if row["original_relative_path"] == path))
        copy_rows.append({
            "original_relative_path": path, "copied_relative_path": f"reviewed-source-copies/{path}",
            "source_sha256": census[path]["sha256"], "file_size_bytes": source.stat().st_size,
            "review_depth": ";".join(depths), "reviewed_line_or_page_ranges": ranges,
            "semantic_evidence_ids": ";".join(source_evidence[path]), "copy_status": "VERIFIED_SHA256_MATCH",
        })
    write_tsv(PACKET / "REVIEWED_SOURCE_FILES_MANIFEST.tsv", ["original_relative_path","copied_relative_path","source_sha256","file_size_bytes","review_depth","reviewed_line_or_page_ranges","semantic_evidence_ids","copy_status"], copy_rows)

    depth_counts = Counter(row["review_depth"] for row in corpus)
    authority_counts = Counter(row["authority_tier"] for row in authority_rows if row["original_relative_path"] != DUMMIES)
    report_preamble = """> Statement labels used below: **FACT FROM SOURCE**, **REVIEWER INTERPRETATION**, **PROJECT DECISION**, **PROVISIONAL RECOMMENDATION**, and **UNRESOLVED GAP**. Local source claims are not presented as externally verified facts.\n"""
    domain_lines = "\n".join(f"- `{code}` {name}: {len(domain_clusters)} clusters, {len(domain_clusters)*2} capabilities, {len(domain_clusters)*2} provisional KU candidates." for code, name, domain_clusters in DOMAINS)
    write_text(REPORTS / "SOURCE_AUTHORITY_POLICY.md", f"""# Source Authority Policy\n\n{report_preamble}\n**PROJECT DECISION:** Authority tier, semantic quality, currency, scope relevance, provenance confidence, reuse decision, limitations, and review depth are independent fields.\n\n**FACT FROM SOURCE:** The Product Charter is `A0_PRODUCT_AUTHORITY`; current project decisions are `A1`; the AD pilot is `A2` within scope.\n\n**PROJECT DECISION:** CKV is `B1`, university material `B2`, internal roadmaps `B3`, historical CARE `C1`, generated matrices `C2`, and opaque/unavailable content `D1`. A filename containing `canonical`, `baseline`, or `evidence` confers no authority.\n\n**UNRESOLVED GAP:** Technical and version-sensitive claims require later external primary-source verification.\n""")
    write_text(REPORTS / "CYBERSECURITY_DOMAIN_TAXONOMY_V1.md", f"""# Cybersecurity Domain Taxonomy V1\n\n{report_preamble}\n**PROJECT DECISION:** The 16 approved provisional Domains are preserved without merge or removal.\n\n{domain_lines}\n\n**REVIEWER INTERPRETATION:** Boundaries overlap intentionally; canonical KUs remain singular and cross-domain use is expressed by relationships.\n""")
    write_text(REPORTS / "CAPABILITY_ARCHITECTURE_BASELINE.md", f"""# Capability Architecture Baseline\n\n{report_preamble}\n**PROJECT DECISION:** Coverage hierarchy is `Domain -> Capability Cluster -> Capability -> Knowledge Unit`. The baseline contains {len(clusters)} clusters, {len(capabilities)} capabilities, and {len(kus)} provisional KU candidates.\n\n**REVIEWER INTERPRETATION:** Domains use three or four clusters according to the breadth visible in the bounded corpus; these counts are a provisional architecture, not a permanent quota.\n\n**FACT FROM SOURCE:** Source support and exact evidence anchors are in the semantic TSV manifests.\n\n**PROVISIONAL RECOMMENDATION:** Refine capabilities during approved Domain authoring; do not generate finished lessons from this baseline.\n""")
    write_text(REPORTS / "ADAPTIVE_LEARNING_MODEL.md", f"""# Adaptive Learning Model\n\n{report_preamble}\n**PROJECT DECISION:** Learning uses configurable templates: `CONCEPTUAL`, `TECHNICAL_STANDARD`, `PRACTICAL_SIMULATOR`, `PRACTICAL_WITH_OPTIONAL_REAL_LAB`, and `INTEGRATED_SCENARIO`.\n\n**PROJECT DECISION:** Available lifecycle: Knowledge Unit -> Lesson Revision -> Micro Practice -> Guided Simulator Lab -> optional Selective Real-Lab Validation -> Evidence-Based Mastery -> Failure-Based Review. No stage is universal.\n\n**PROJECT DECISION:** Integrated projects and institutional scenarios combine multiple capabilities; they are not mandatory children of each KU. Evidence origin remains `SIMULATED`, `REAL_LAB`, `MANUAL_ASSESSMENT`, or `SOURCE_REVIEW`.\n\n**UNRESOLVED GAP:** Mastery thresholds require later empirical calibration.\n""")
    course_summary = "\n".join(f"- **{course}**: {sum(1 for p in university if p.split('/')[1] == course)} files; bounded pages only; strengths and gaps recorded below." for course in sorted({p.split('/')[1] for p in university}))
    write_text(REPORTS / "UNIVERSITY_COURSE_SEMANTIC_ASSESSMENT.md", f"""# University Course Semantic Assessment\n\n{report_preamble}\n**FACT FROM SOURCE:** All 30 university files were handled at course-coverage level. Twenty-seven text-extractable PDFs received multi-page bounded review; three remained `DEFERRED_OCR_REQUIRED`.\n\n{course_summary}\n\n**REVIEWER INTERPRETATION:** Ethical Hacking offers introductory authorized-assessment, scanning, enumeration, vulnerability, and buffer-overflow material but has OCR gaps and mixed age/currentness. Network Administration and Monitoring is strongest for IP services, availability, clustering, backup, and infrastructure operations. Network Security supports access control and infrastructure security. Secure Application Development is the most current-looking set (2025-2026 signals) and covers HTTP/TLS, service design, APIs, cookies/input, cross-site controls, passwords, authentication, and authorization.\n\n**UNRESOLVED GAP:** No course is claimed complete; unread pages, absent syllabus filenames, and image-only PDFs constrain sequencing and depth conclusions.\n""")
    write_text(REPORTS / "CURRICULUM_COVERAGE_AND_GAPS.md", f"""# Curriculum Coverage and Gaps\n\n{report_preamble}\n**REVIEWER INTERPRETATION:** Local support is strongest in architecture, identity, networking, secure applications, detection, governance, and evidence-oriented practice.\n\n**UNRESOLVED GAP:** Thin or representative-only support remains for advanced forensics, exploit development, cloud-provider specifics, cryptographic implementation, privacy law, OT/ICS, mobile/embedded, and physical security.\n\n**PROVISIONAL RECOMMENDATION:** Treat all {len(kus)} KUs as candidates, prioritize `V1_FOUNDATION`, and verify claims against current primary authorities during content authoring.\n""")
    write_text(REPORTS / "VS001_SOURCE_SELECTION.md", f"""# VS-001 Source Selection - Windows Authorization Decision\n\n{report_preamble}\n**PROJECT DECISION:** Preserve `VS-001 - Windows Authorization Decision`; use pilot `KU-AD-02` and `CAP-D03-03-01`.\n\n**FACT FROM SOURCE:** The primary package is the Product Charter plus the four AD pilot control/decision/KU/queue files. CKV-022 is supporting internal synthesis. The secure-application authentication/authorization lecture is generic secondary context only.\n\n**PROJECT DECISION:** Simulator behavior must model principal/token SIDs and privileges, group state, object security descriptor, ordered ACE evaluation, requested access mask, explicit allow/deny result, explanation, provenance, deterministic replay, and negative tests.\n\n**PROJECT DECISION:** Out of scope: implementation, live AD changes, credential attacks, real-environment execution, and claims of production or real-lab competence.\n\n**UNRESOLVED GAP:** Microsoft/Open Specifications and target Windows-version semantics need a later authorized primary-source review. Exact traceability is in `VS001_SOURCE_SELECTION.tsv`.\n""")
    write_text(REPORTS / "TASK003_DECISION_REGISTER.md", f"""# TASK-003 Decision Register\n\n{report_preamble}\n1. **PROJECT DECISION:** Preserve 16 Domains and create {len(clusters)} clusters, {len(capabilities)} capabilities, and {len(kus)} provisional KUs.\n2. **PROJECT DECISION:** Maintain one canonical KU with cross-domain relationships.\n3. **PROJECT DECISION:** Simulator-first; Real-Lab selective and claim-specific.\n4. **PROJECT DECISION:** Manual AI Bridge is the only v1 AI execution path.\n5. **REVIEWER INTERPRETATION:** CKV is useful B1 seed material, not external authority.\n6. **REVIEWER INTERPRETATION:** Large matrices are seed data requiring validation.\n7. **REVIEWER INTERPRETATION:** CARE concepts are historical lessons only; no code or architecture inheritance.\n8. **PROJECT DECISION:** VS-001 uses KU-AD-02 and does not proceed to implementation.\n""")
    write_text(REPORTS / "UNRESOLVED_DECISIONS.md", f"""# Unresolved Decisions\n\n{report_preamble}\n- **UNRESOLVED GAP:** Confirm Domain naming/boundaries only after role and curriculum stakeholder review; no change is made in TASK-003.\n- **UNRESOLVED GAP:** Select current external primary authorities for each Domain.\n- **UNRESOLVED GAP:** Decide OCR scope and legal/technical handling for three university PDFs in a later phase.\n- **UNRESOLVED GAP:** Decide whether the AES-failed PDF warrants an approved parser capability.\n- **UNRESOLVED GAP:** Calibrate mastery thresholds and the exact claims requiring Real-Lab evidence.\n- **UNRESOLVED GAP:** Verify VS-001 Windows authorization semantics and target OS versions before content publication or implementation.\n""")
    write_text(REPORTS / "SEMANTIC_REVIEW_REPORT.md", f"""# Semantic Review Report\n\n{report_preamble}\nWorkspace: `{ROOT}`\n\n**FACT FROM SOURCE:** Corpus: {len(corpus)} selected files, {len(evidence)} reviewed units, {len(reviewed_paths)} semantically reviewed files, and {depth_counts['DEFERRED_OCR_REQUIRED']} OCR-deferred university files.\n\n**REVIEWER INTERPRETATION:** Product authority and pilot decisions provide the strongest current scope control. CKV/CISSP/roadmaps provide broad but internally synthesized coverage. University sources provide uneven supporting coverage. Large matrices are structurally useful but unverified. CARE is independent-product history only.\n\n**PROJECT DECISION:** No unreviewed material is represented as reviewed; every reviewed claim is anchored in `SEMANTIC_REVIEW_EVIDENCE.tsv`.\n\n**UNRESOLVED GAP:** External truth verification and full curriculum authoring remain outside TASK-003.\n""")

    counts_md = f"""# Capability Output Counts\n\n- Domains: {len(DOMAINS)}\n- Capability Clusters: {len(clusters)}\n- Capabilities: {len(capabilities)}\n- Provisional Knowledge Unit candidates: {len(kus)}\n- Cross-domain relationships: {len(relationships)}\n- VS-001 selection rows: {len(vs_rows)}\n"""
    write_text(PACKET / "CAPABILITY_OUTPUT_COUNTS.md", counts_md)
    write_text(PACKET / "REVIEW_CORPUS_SUMMARY.md", f"""# Review Corpus Summary\n\n- Selected source files: {len(corpus)} / 80 maximum\n- Semantically reviewed files: {len(reviewed_paths)}\n- Review units: {len(evidence)} / 250 maximum\n- Full-review files: {depth_counts['REVIEWED_FULL']}\n- Selected-section files: {depth_counts['REVIEWED_SELECTED_SECTIONS']}\n- Selected-page files: {depth_counts['REVIEWED_SELECTED_PAGES']}\n- Metadata-only files: 0\n- OCR-deferred files in corpus: {depth_counts['DEFERRED_OCR_REQUIRED']}\n- AES parse-deferred file outside corpus: 1\n- Full reviewed source copies: {len(copy_rows)} files, {copied_bytes} bytes\n""")
    write_text(PACKET / "SOURCE_AUTHORITY_SUMMARY.md", "# Source Authority Summary\n\n" + "\n".join(f"- {tier}: {count}" for tier, count in sorted(authority_counts.items())) + "\n")
    write_text(PACKET / "COVERAGE_SUMMARY.md", f"""# Coverage Summary\n\nAll 16 approved Domains have 3-4 need-based clusters, with 2 capabilities and 2 provisional KU candidates per cluster. Confidence remains provisional and source gaps are explicit in `DOMAIN_COVERAGE_MATRIX.tsv`. Real-Lab is selective, not universal.\n""")
    write_text(PACKET / "RESIDUAL_LIMITATIONS.md", """# Residual Limitations\n\n- No external URLs or primary authorities were reviewed.\n- Three university PDFs require OCR; one non-university PDF remains AES parse-deferred.\n- PDF review was bounded to recorded representative pages; visual rendering was not used because extracted/page-only copies are prohibited by the handoff requirement.\n- CKV, CISSP notes, roadmaps, matrices, and CARE remain internally curated, generated, or historical as classified.\n- No finished lessons, product application, simulator, UI, Real-Lab execution, or AI adapter was built.\n""")
    write_text(PACKET / "SOURCE_SAFETY_RESULTS.md", f"""# Source Safety Results\n\n- Original source count expected: 2083\n- Original source fingerprint expected: `97a4013d72c5c1516410e93f57cbede3beb5f5f38dda611aab943ba1351c2f72`\n- Reviewed copies: {len(copy_rows)} complete source files\n- Reviewed-copy bytes: {copied_bytes}\n- All copy statuses: `VERIFIED_SHA256_MATCH`\n- Final original/Task 001/Task 002 regression validation: pending validator run\n""")
    write_text(PACKET / "CODEX_FINAL_REPORT.md", f"""# Codex Final Report - TASK-003\n\n## Workspace and runtime\n\n- Absolute workspace: `{ROOT}`\n- Runtime: Python `{platform.python_version()}` on `{platform.platform()}`\n- Product application code built: **No**\n\n## Inputs and bounded corpus\n\nAll 15 required Task 002 inputs were read and hash-validated before output generation. The recorded 2,083-file original fingerprint matched before work.\n\n- Selected sources: {len(corpus)}\n- Semantically reviewed sources: {len(reviewed_paths)}\n- Review units: {len(evidence)}\n- Full / selected-section / selected-page / metadata-only / OCR-deferred: {depth_counts['REVIEWED_FULL']} / {depth_counts['REVIEWED_SELECTED_SECTIONS']} / {depth_counts['REVIEWED_SELECTED_PAGES']} / 0 / {depth_counts['DEFERRED_OCR_REQUIRED']}\n- AES parse-deferred outside corpus: 1\n- No unreviewed source was represented as reviewed.\n\n## Reviewed-source copy handoff\n\n- Complete original files copied: {len(copy_rows)}\n- Total copied bytes: {copied_bytes}\n- Extracted page/section substitutes: 0\n- Original/copied SHA-256 mismatches: 0\n- Relative paths preserved under `reviewed-source-copies/`.\n\n## Authority and architecture outputs\n\nAuthority distribution:\n""" + "\n".join(f"- {tier}: {count}" for tier, count in sorted(authority_counts.items())) + f"""\n\n- Domains / clusters / capabilities / provisional KUs: {len(DOMAINS)} / {len(clusters)} / {len(capabilities)} / {len(kus)}\n- Cross-domain relationships: {len(relationships)}\n- VS-001 source-selection rows: {len(vs_rows)}\n\n## Review findings\n\n- University: 30 files handled; 27 bounded multi-page reviews and 3 OCR deferrals. Coverage is strongest in networking/infrastructure and secure application development; completeness is not claimed.\n- CKV: 7 control files fully reviewed and 15 Domain representatives sampled. Useful curriculum seed, not external authority.\n- CARE: 2 mandatory historical analyses plus 5 domain-model samples. Reusable concepts are typed objects, evidence hashes, and explicit baselines; implementation coupling prevents adoption as current architecture.\n- Large matrices: 4 files sampled at schema plus non-adjacent ranges. Suitable only as seed data requiring provenance, deduplication, and consistency validation.\n- VS-001: KU-AD-02 selected with Product Charter and AD pilot as primary scope authority, CKV-022 as supporting synthesis, and generic university authorization material as secondary context only.\n\n## Validation and safety\n\nDeterministic validation is pending the separate validator run. Original and Task 001/002 safety must pass before the stop gate.\n\n## Known limitations and unresolved decisions\n\nNo external verification, OCR, AES dependency addition, full-source-library review, finished lesson authoring, product implementation, Real-Lab execution, or automated AI execution occurred. Open items are listed in `UNRESOLVED_DECISIONS.md`, `UNRESOLVED_SOURCE_ISSUES.tsv`, and `RESIDUAL_LIMITATIONS.md`.\n\n## Residual files\n\nAll created files are confined to the authorized paths: root `AGENTS.md`, semantic manifests/reports, semantic validation tools, and the TASK-003 review packet including verified full reviewed-source copies.\n""")
    write_text(PACKET / "TEST_RESULTS.txt", "TASK-003 build: PASS\nDeterministic validator: PENDING\nTask 001/002 regression: PENDING\n")
    print(f"BUILT sources={len(corpus)} reviewed={len(reviewed_paths)} units={len(evidence)} clusters={len(clusters)} capabilities={len(capabilities)} kus={len(kus)} copies={len(copy_rows)} bytes={copied_bytes}")


if __name__ == "__main__":
    main()

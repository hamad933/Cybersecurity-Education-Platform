"""
CEP Fast Control Plane - Conflict / Safety Matcher & CEP Initial Policy Matrix.
Standard library only (Python 3.10+).

Workstream Registry & Scope Boundaries:
- W01: work/cep-w01-*, owns shared UI / shell / nav + routes/workspaces/today.php
- W02: work/cep-w02-*, Knowledge & Learning + routes/workspaces/knowledge-learning.php
- W03: work/cep-w03-*, Simulation & Enterprise + routes/workspaces/simulation-enterprise.php
- W04: work/cep-w04-*, Progress & Evidence + routes/workspaces/progress-evidence.php
- W05: work/cep-w05-*, System & Operations + routes/workspaces/system-operations.php
"""

import fnmatch
from dataclasses import dataclass, field
from typing import Dict, List, Optional, Set, Tuple
from scripts.controller.models import CandidatePR


@dataclass
class WorkstreamDefinition:
    workstream_id: str
    branch_pattern: str
    allowed_route_files: List[str]
    allowed_path_patterns: List[str]
    disallowed_path_patterns: List[str] = field(default_factory=list)


WORKSTREAM_REGISTRY: Dict[str, WorkstreamDefinition] = {
    "W01": WorkstreamDefinition(
        workstream_id="W01",
        branch_pattern="work/cep-w01-*",
        allowed_route_files=["routes/workspaces/today.php"],
        allowed_path_patterns=[
            "resources/js/components/*",
            "resources/js/pages/Dashboard.vue",
            "resources/js/pages/Auth/*",
            "routes/workspaces/today.php",
            "tests/Feature/Vs001WorkspaceTest.php",
        ],
    ),
    "W02": WorkstreamDefinition(
        workstream_id="W02",
        branch_pattern="work/cep-w02-*",
        allowed_route_files=["routes/workspaces/knowledge-learning.php"],
        allowed_path_patterns=[
            "resources/js/pages/Vs001/*",
            "routes/workspaces/knowledge-learning.php",
            "tests/Feature/Vs001LifecycleTest.php",
            "tests/Feature/Vs001PublicationWorkflowTest.php",
        ],
    ),
    "W03": WorkstreamDefinition(
        workstream_id="W03",
        branch_pattern="work/cep-w03-*",
        allowed_route_files=["routes/workspaces/simulation-enterprise.php"],
        allowed_path_patterns=[
            "resources/js/pages/Vs002/*",
            "routes/workspaces/simulation-enterprise.php",
            "tests/Feature/Vs002LifecycleTest.php",
            "tests/Feature/Vs002WorkspaceTest.php",
            "tests/Feature/Vs002CorrectionGateTest.php",
        ],
    ),
    "W04": WorkstreamDefinition(
        workstream_id="W04",
        branch_pattern="work/cep-w04-*",
        allowed_route_files=["routes/workspaces/progress-evidence.php"],
        allowed_path_patterns=[
            "resources/js/pages/Vs003/*",
            "routes/workspaces/progress-evidence.php",
            "tests/Feature/Vs003InvestigationTest.php",
        ],
    ),
    "W05": WorkstreamDefinition(
        workstream_id="W05",
        branch_pattern="work/cep-w05-*",
        allowed_route_files=["routes/workspaces/system-operations.php"],
        allowed_path_patterns=[
            "routes/workspaces/system-operations.php",
            "tests/Integration/AuditIntegrityTest.php",
            "tests/Integration/BackupRestoreTest.php",
            "tests/Integration/ManualAiBridgeTest.php",
        ],
    ),
}

# Forbidden directories and files for candidate writers
PROHIBITED_GLOBAL_PATHS = [
    "app/*",
    "resources/*",
    "routes/*",
    "database/*",
    "composer.json",
    "composer.lock",
    "package.json",
    "package-lock.json",
    ".github/workflows/core-ci.yml",
    ".github/workflows/release-verification.yml",
    "AGENTS.md",
    "CONTRIBUTING.md",
    "docs/*",
]

# Protected workspace routes that must not be touched cross-workstream
WORKSPACE_ROUTE_FILES = {
    "routes/workspaces/today.php",
    "routes/workspaces/knowledge-learning.php",
    "routes/workspaces/simulation-enterprise.php",
    "routes/workspaces/progress-evidence.php",
    "routes/workspaces/system-operations.php",
}


@dataclass
class MatcherViolation:
    violation_code: str
    message: str


class SafetyMatcher:
    def evaluate_pr_safety(
        self,
        pr: CandidatePR,
        other_active_prs: List[CandidatePR] = None,
    ) -> List[MatcherViolation]:
        violations: List[MatcherViolation] = []
        other_active_prs = other_active_prs or []

        # 1. Target Branch Validation
        if pr.branch in ("main", "build/cep-v1-integration"):
            violations.append(
                MatcherViolation(
                    "TARGET_BRANCH_PROHIBITED",
                    f"Candidate branch '{pr.branch}' cannot directly target or be named integration/main.",
                )
            )

        # 2. Workstream Identity & Branch Pattern Verification
        ws_def = WORKSTREAM_REGISTRY.get(pr.workstream_id)
        if not ws_def:
            if pr.workstream_id != "AUTOMATION_PLANE":
                violations.append(
                    MatcherViolation(
                        "UNKNOWN_WORKSTREAM_ID",
                        f"Unregistered workstream ID '{pr.workstream_id}'.",
                    )
                )
        else:
            if not fnmatch.fnmatch(pr.branch, ws_def.branch_pattern):
                violations.append(
                    MatcherViolation(
                        "BRANCH_WORKSTREAM_MISMATCH",
                        f"Branch '{pr.branch}' does not match pattern '{ws_def.branch_pattern}' for workstream '{pr.workstream_id}'.",
                    )
                )

        # 3. Path Scope Violation & Prohibited Path Checking
        for path in pr.changed_files:
            # Check for governance / locked files
            if path in ("composer.json", "composer.lock", "package.json", "package-lock.json", "AGENTS.md", "CONTRIBUTING.md"):
                violations.append(
                    MatcherViolation(
                        "LOCKED_SYSTEM_FILE_MUTATION",
                        f"Attempted mutation of locked project/governance file: '{path}'.",
                    )
                )

            # Check workspace route cross-boundary touching
            if path in WORKSPACE_ROUTE_FILES:
                if ws_def and path not in ws_def.allowed_route_files:
                    violations.append(
                        MatcherViolation(
                            "CROSS_WORKSPACE_ROUTE_MUTATION",
                            f"Workstream '{pr.workstream_id}' attempted to modify unauthorized workspace route '{path}'.",
                        )
                    )

        # 4. Shared Path Collision & Multi-Writer Conflict Detection
        for other in other_active_prs:
            if other.number == pr.number:
                continue

            if other.branch == pr.branch:
                violations.append(
                    MatcherViolation(
                        "DUPLICATE_BRANCH_WRITER_CONFLICT",
                        f"Multiple active writers targeted the same branch '{pr.branch}'.",
                    )
                )

            shared_paths = set(pr.changed_files).intersection(set(other.changed_files))
            if shared_paths:
                violations.append(
                    MatcherViolation(
                        "SHARED_PATH_COLLISION",
                        f"Path collision detected between PR #{pr.number} and PR #{other.number} on paths: {list(shared_paths)}",
                    )
                )

        return violations

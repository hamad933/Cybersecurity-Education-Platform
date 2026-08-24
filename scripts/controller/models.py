"""
CEP Fast Control Plane - Core State Models & Enums.
Standard library only (Python 3.10+).
"""

from dataclasses import dataclass, field
from enum import Enum
from typing import Any, Dict, List, Optional


class WorkstreamState(str, Enum):
    DISCOVERED = "DISCOVERED"
    NO_CANDIDATE = "NO_CANDIDATE"
    WRITER_ACTIVE = "WRITER_ACTIVE"
    CANDIDATE_PUBLISHED = "CANDIDATE_PUBLISHED"
    CANDIDATE_DRAFT = "CANDIDATE_DRAFT"
    CANDIDATE_NON_DRAFT_UNAUTHORIZED = "CANDIDATE_NON_DRAFT_UNAUTHORIZED"
    PUBLICATION_BASE_MISMATCH = "PUBLICATION_BASE_MISMATCH"
    BASELINE_DRIFT = "BASELINE_DRIFT"
    HEAD_DRIFT = "HEAD_DRIFT"
    CANDIDATE_STALE = "CANDIDATE_STALE"
    STOP_GATE_REACHED = "STOP_GATE_REACHED"
    PARENT_REVIEW_PENDING = "PARENT_REVIEW_PENDING"
    AUTHORITY_REQUIRED = "AUTHORITY_REQUIRED"
    TERMINAL_ACCEPTED_EXTERNALLY = "TERMINAL_ACCEPTED_EXTERNALLY"


class JulesState(str, Enum):
    QUEUED = "QUEUED"
    PLANNING = "PLANNING"
    AWAITING_PLAN_APPROVAL = "AWAITING_PLAN_APPROVAL"
    AWAITING_USER_FEEDBACK = "AWAITING_USER_FEEDBACK"
    IN_PROGRESS = "IN_PROGRESS"
    PAUSED = "PAUSED"
    COMPLETED = "COMPLETED"
    FAILED = "FAILED"
    UNKNOWN_JULES_STATE = "UNKNOWN_JULES_STATE"

    @classmethod
    def normalize(cls, val: Optional[str]) -> "JulesState":
        if not val:
            return cls.UNKNOWN_JULES_STATE
        val_upper = str(val).upper().strip()
        # Handle API string mappings if necessary
        for member in cls:
            if member.value == val_upper:
                return member
        return cls.UNKNOWN_JULES_STATE


class CIState(str, Enum):
    NO_RUN = "NO_RUN"
    QUEUED = "QUEUED"
    IN_PROGRESS = "IN_PROGRESS"
    SUCCESS = "SUCCESS"
    FAILURE = "FAILURE"
    CANCELLED = "CANCELLED"
    TIMED_OUT = "TIMED_OUT"
    ACTION_REQUIRED = "ACTION_REQUIRED"
    STALE_SHA = "STALE_SHA"
    EXTERNAL_TRANSIENT_FAILURE = "EXTERNAL_TRANSIENT_FAILURE"
    WORKSTREAM_OWNED_FAILURE = "WORKSTREAM_OWNED_FAILURE"
    SHARED_OR_EXTERNAL_FAILURE = "SHARED_OR_EXTERNAL_FAILURE"
    UNKNOWN_CI_STATE = "UNKNOWN_CI_STATE"

    @classmethod
    def normalize(cls, val: Optional[str]) -> "CIState":
        if not val:
            return cls.NO_RUN
        val_upper = str(val).upper().strip()
        for member in cls:
            if member.value == val_upper:
                return member
        return cls.UNKNOWN_CI_STATE


class ReviewState(str, Enum):
    NOT_REQUESTED = "NOT_REQUESTED"
    REVIEW_QUEUED = "REVIEW_QUEUED"
    REVIEW_IN_PROGRESS = "REVIEW_IN_PROGRESS"
    PASS_FOR_PARENT_REVIEW = "PASS_FOR_PARENT_REVIEW"
    PASS_WITH_MINOR_POLISH = "PASS_WITH_MINOR_POLISH"
    REVISION_REQUIRED = "REVISION_REQUIRED"
    BLOCKING_FAILURE = "BLOCKING_FAILURE"
    NOT_VERIFIABLE = "NOT_VERIFIABLE"
    STALE_REVIEW = "STALE_REVIEW"
    REVIEW_SCOPE_MISMATCH = "REVIEW_SCOPE_MISMATCH"
    REVIEWER_MUTATION_DETECTED = "REVIEWER_MUTATION_DETECTED"

    @classmethod
    def normalize(cls, val: Optional[str]) -> "ReviewState":
        if not val:
            return cls.NOT_REQUESTED
        val_upper = str(val).upper().strip()
        for member in cls:
            if member.value == val_upper:
                return member
        return cls.NOT_VERIFIABLE


class InputClassification(str, Enum):
    POLICY_RESOLVABLE = "POLICY_RESOLVABLE"
    REVIEW_DEPENDENT = "REVIEW_DEPENDENT"
    CI_EVIDENCE_DEPENDENT = "CI_EVIDENCE_DEPENDENT"
    ENVIRONMENT_MISMATCH = "ENVIRONMENT_MISMATCH"
    SHARED_CONTRACT_REQUIRED = "SHARED_CONTRACT_REQUIRED"
    CROSS_WORKSTREAM_CONFLICT = "CROSS_WORKSTREAM_CONFLICT"
    BASELINE_OR_HEAD_DRIFT = "BASELINE_OR_HEAD_DRIFT"
    WRITE_OUTCOME_UNKNOWN = "WRITE_OUTCOME_UNKNOWN"
    SCOPE_CHANGE_REQUIRED = "SCOPE_CHANGE_REQUIRED"
    OWNER_OR_CONTROLLER_AUTHORITY_REQUIRED = "OWNER_OR_CONTROLLER_AUTHORITY_REQUIRED"
    UNCLASSIFIED_INPUT_REQUIRED = "UNCLASSIFIED_INPUT_REQUIRED"


@dataclass
class CandidatePR:
    number: int
    title: str
    workstream_id: str
    branch: str
    head_sha: str
    base_branch: str
    base_sha: str
    is_draft: bool
    is_merged: bool
    is_closed: bool
    changed_files: List[str] = field(default_factory=list)
    repo: str = "hamad933/Cybersecurity-Education-Platform"


@dataclass
class ReviewPacket:
    workstream_id: str
    repo: str
    candidate_branch: str
    candidate_full_sha: str
    review_role: str
    verdict: ReviewState
    finding_ids: List[str] = field(default_factory=list)
    severity: str = "medium"
    affected_paths: List[str] = field(default_factory=list)
    root_cause_group: str = "default"
    required_correction: str = ""
    preservation_requirements: List[str] = field(default_factory=list)
    evidence_pointers: List[str] = field(default_factory=list)
    authority_required: bool = False
    timestamp: str = ""
    source_identity: str = ""
    reviewer_mutated_code: bool = False


@dataclass
class JulesSessionInfo:
    session_id: str
    name: str
    title: str
    state: JulesState
    create_time: str
    update_time: str
    workstream_id: Optional[str] = None
    role: str = "writer"
    branch: Optional[str] = None
    head_sha: Optional[str] = None
    latest_user_feedback: Optional[str] = None


@dataclass
class ControlCycleResult:
    workstream_state: WorkstreamState
    jules_state: JulesState
    ci_state: CIState
    review_state: ReviewState
    input_classification: Optional[InputClassification] = None
    action_taken: str = "NO_ACTION"
    operation_key: str = ""
    escalation_reason: Optional[str] = None
    receipt: Dict[str, Any] = field(default_factory=dict)

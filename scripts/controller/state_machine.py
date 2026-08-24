"""
CEP Fast Control Plane - Deterministic State Machine Engine.
Standard library only (Python 3.10+).
"""

from typing import Optional, Tuple
from scripts.controller.models import (
    CIState,
    CandidatePR,
    ControlCycleResult,
    InputClassification,
    JulesSessionInfo,
    JulesState,
    ReviewPacket,
    ReviewState,
    WorkstreamState,
)


def classify_user_feedback(feedback_text: str) -> InputClassification:
    """
    Classify free-text user feedback or questions into safe internal enum.
    """
    if not feedback_text:
        return InputClassification.UNCLASSIFIED_INPUT_REQUIRED

    text_lower = feedback_text.lower()

    if "php" in text_lower and ("version" in text_lower or "8.5" in text_lower or "8.2" in text_lower or "8.1" in text_lower):
        return InputClassification.ENVIRONMENT_MISMATCH

    if "postgres" in text_lower or "pg" in text_lower or "database" in text_lower or "db" in text_lower:
        return InputClassification.ENVIRONMENT_MISMATCH

    if "review" in text_lower or "finding" in text_lower:
        return InputClassification.REVIEW_DEPENDENT

    if "ci" in text_lower or "test" in text_lower or "lint" in text_lower or "build" in text_lower:
        return InputClassification.CI_EVIDENCE_DEPENDENT

    if "drift" in text_lower or "rebase" in text_lower or "ahead" in text_lower or "behind" in text_lower:
        return InputClassification.BASELINE_OR_HEAD_DRIFT

    if "contract" in text_lower or "api" in text_lower or "interface" in text_lower:
        return InputClassification.SHARED_CONTRACT_REQUIRED

    if "conflict" in text_lower or "ownership" in text_lower or "workstream" in text_lower:
        return InputClassification.CROSS_WORKSTREAM_CONFLICT

    if "merge" in text_lower or "deploy" in text_lower or "main" in text_lower or "authority" in text_lower:
        return InputClassification.OWNER_OR_CONTROLLER_AUTHORITY_REQUIRED

    return InputClassification.UNCLASSIFIED_INPUT_REQUIRED


def evaluate_control_cycle(
    pr: Optional[CandidatePR],
    jules_session: Optional[JulesSessionInfo],
    ci_state: CIState,
    latest_review: Optional[ReviewPacket],
    expected_base_sha: str = "b5d53d2d44c570ebf112c50bec966da01835e5d9",
    expected_base_branch: str = "build/cep-v1-integration",
) -> ControlCycleResult:
    """
    Evaluates current states deterministically and returns action decision.
    """
    j_state = jules_session.state if jules_session else JulesState.UNKNOWN_JULES_STATE
    r_state = latest_review.verdict if latest_review else ReviewState.NOT_REQUESTED

    # 1. Terminal / Merged / External check
    if pr and (pr.is_merged or pr.is_closed):
        return ControlCycleResult(
            workstream_state=WorkstreamState.TERMINAL_ACCEPTED_EXTERNALLY,
            jules_state=j_state,
            ci_state=ci_state,
            review_state=r_state,
            action_taken="NO_OP_EXTERNALLY_TERMINATED",
        )

    # 2. Baseline & Branch Verification
    if pr:
        if pr.base_branch != expected_base_branch:
            return ControlCycleResult(
                workstream_state=WorkstreamState.PUBLICATION_BASE_MISMATCH,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=r_state,
                action_taken="ESCALATE_TO_PARENT",
                escalation_reason=f"PR base branch {pr.base_branch} does not match required {expected_base_branch}",
            )
        if pr.base_sha != expected_base_sha:
            return ControlCycleResult(
                workstream_state=WorkstreamState.BASELINE_DRIFT,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=r_state,
                action_taken="ESCALATE_TO_PARENT",
                escalation_reason=f"PR base SHA {pr.base_sha} does not match expected baseline {expected_base_sha}",
            )

    # 3. Non-Draft Check (Draft enforcement)
    if pr and not pr.is_draft:
        return ControlCycleResult(
            workstream_state=WorkstreamState.CANDIDATE_NON_DRAFT_UNAUTHORIZED,
            jules_state=j_state,
            ci_state=ci_state,
            review_state=r_state,
            action_taken="CONVERT_TO_DRAFT_METADATA_ONLY",
            escalation_reason="Candidate PR was unexpectedly marked ready for review / non-draft without Parent authority.",
        )

    # 4. Reviewer Safety & Validation
    if latest_review:
        if latest_review.reviewer_mutated_code:
            return ControlCycleResult(
                workstream_state=WorkstreamState.AUTHORITY_REQUIRED,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=ReviewState.REVIEWER_MUTATION_DETECTED,
                action_taken="ESCALATE_TO_PARENT",
                escalation_reason="Reviewer mutated code; review isolation boundary violated.",
            )

        if pr and latest_review.candidate_full_sha != pr.head_sha:
            r_state = ReviewState.STALE_REVIEW
        elif latest_review.authority_required:
            return ControlCycleResult(
                workstream_state=WorkstreamState.AUTHORITY_REQUIRED,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=latest_review.verdict,
                action_taken="ESCALATE_TO_PARENT",
                escalation_reason="Review packet requested Parent/Owner authority.",
            )

    # 5. Jules User Feedback & Plan Approval Checks
    input_class: Optional[InputClassification] = None

    if j_state == JulesState.AWAITING_USER_FEEDBACK:
        feedback = jules_session.latest_user_feedback if jules_session else ""
        input_class = classify_user_feedback(feedback or "")

        if input_class == InputClassification.ENVIRONMENT_MISMATCH:
            return ControlCycleResult(
                workstream_state=WorkstreamState.WRITER_ACTIVE,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=r_state,
                input_classification=input_class,
                action_taken="AUTO_ANSWER_ENVIRONMENT_MISMATCH",
            )
        elif input_class in (
            InputClassification.OWNER_OR_CONTROLLER_AUTHORITY_REQUIRED,
            InputClassification.CROSS_WORKSTREAM_CONFLICT,
            InputClassification.SHARED_CONTRACT_REQUIRED,
            InputClassification.UNCLASSIFIED_INPUT_REQUIRED,
        ):
            return ControlCycleResult(
                workstream_state=WorkstreamState.AUTHORITY_REQUIRED,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=r_state,
                input_classification=input_class,
                action_taken="ESCALATE_TO_PARENT",
                escalation_reason=f"Jules feedback required authority or unclassified input: {input_class.value}",
            )
        else:
            return ControlCycleResult(
                workstream_state=WorkstreamState.WRITER_ACTIVE,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=r_state,
                input_classification=input_class,
                action_taken="CONTINUE_SAME_SESSION",
            )

    if j_state == JulesState.AWAITING_PLAN_APPROVAL:
        return ControlCycleResult(
            workstream_state=WorkstreamState.WRITER_ACTIVE,
            jules_state=j_state,
            ci_state=ci_state,
            review_state=r_state,
            action_taken="APPROVE_PLAN",
        )

    # 6. Reviewer Finding Evaluation (when PR exists)
    if pr and latest_review and r_state in (ReviewState.REVISION_REQUIRED, ReviewState.BLOCKING_FAILURE):
        if r_state == ReviewState.BLOCKING_FAILURE:
            return ControlCycleResult(
                workstream_state=WorkstreamState.AUTHORITY_REQUIRED,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=r_state,
                action_taken="ESCALATE_TO_PARENT",
                escalation_reason="Reviewer returned BLOCKING_FAILURE.",
            )
        return ControlCycleResult(
            workstream_state=WorkstreamState.CANDIDATE_PUBLISHED,
            jules_state=j_state,
            ci_state=ci_state,
            review_state=r_state,
            action_taken="SEND_REVIEW_FINDINGS_TO_WRITER",
        )

    if pr and latest_review and r_state in (ReviewState.PASS_FOR_PARENT_REVIEW, ReviewState.PASS_WITH_MINOR_POLISH):
        return ControlCycleResult(
            workstream_state=WorkstreamState.PARENT_REVIEW_PENDING,
            jules_state=j_state,
            ci_state=ci_state,
            review_state=r_state,
            action_taken="ESCALATE_TO_PARENT_FOR_REVIEW",
            escalation_reason="Review passed; ready for Parent Controller portfolio review.",
        )

    # 7. CI State Decisions (when PR exists)
    if pr:
        if ci_state == CIState.WORKSTREAM_OWNED_FAILURE:
            return ControlCycleResult(
                workstream_state=WorkstreamState.CANDIDATE_PUBLISHED,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=r_state,
                action_taken="SEND_CI_FAILURE_TO_WRITER",
            )
        elif ci_state == CIState.EXTERNAL_TRANSIENT_FAILURE:
            return ControlCycleResult(
                workstream_state=WorkstreamState.CANDIDATE_PUBLISHED,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=r_state,
                action_taken="RETRY_CI_WORKFLOW_SAME_SHA",
            )
        elif ci_state in (CIState.FAILURE, CIState.SHARED_OR_EXTERNAL_FAILURE, CIState.TIMED_OUT, CIState.UNKNOWN_CI_STATE):
            return ControlCycleResult(
                workstream_state=WorkstreamState.AUTHORITY_REQUIRED,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=r_state,
                action_taken="ESCALATE_TO_PARENT",
                escalation_reason=f"Unresolved or ambiguous CI state: {ci_state.value}",
            )
        elif ci_state in (CIState.QUEUED, CIState.IN_PROGRESS):
            return ControlCycleResult(
                workstream_state=WorkstreamState.CANDIDATE_PUBLISHED,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=r_state,
                action_taken="WAIT_FOR_CI",
            )
        elif ci_state == CIState.SUCCESS and r_state in (ReviewState.NOT_REQUESTED, ReviewState.STALE_REVIEW):
            return ControlCycleResult(
                workstream_state=WorkstreamState.CANDIDATE_PUBLISHED,
                jules_state=j_state,
                ci_state=ci_state,
                review_state=r_state,
                action_taken="DISPATCH_INDEPENDENT_REVIEWER",
            )

    # 8. Jules Active Writing State Check (if no specific PR CI/Review action triggered)
    if j_state in (JulesState.QUEUED, JulesState.PLANNING, JulesState.IN_PROGRESS):
        return ControlCycleResult(
            workstream_state=WorkstreamState.WRITER_ACTIVE if pr else WorkstreamState.DISCOVERED,
            jules_state=j_state,
            ci_state=ci_state,
            review_state=r_state,
            action_taken="WAIT_FOR_JULES",
        )

    # 9. Default fallback
    return ControlCycleResult(
        workstream_state=WorkstreamState.CANDIDATE_DRAFT if pr else WorkstreamState.DISCOVERED,
        jules_state=j_state,
        ci_state=ci_state,
        review_state=r_state,
        action_taken="MONITOR",
    )

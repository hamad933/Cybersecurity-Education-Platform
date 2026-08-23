"""
CEP Fast Control Plane - Test Suite.
Standard library unittest only (Python 3.10+). Zero third-party dependencies.
Covers 25+ distinct requirement scenarios.
"""

import os
import sys
import unittest
from typing import Optional

# Add repo root to sys.path
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
REPO_ROOT = os.path.dirname(os.path.dirname(SCRIPT_DIR))
if REPO_ROOT not in sys.path:
    sys.path.insert(0, REPO_ROOT)

from scripts.controller.idempotency import (
    IdempotencyEngine,
    compute_operation_key,
    generate_execution_receipt,
    sanitize_receipt_data,
)
from scripts.controller.jules_adapter import JulesAdapter, JulesAdapterError
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
from scripts.controller.routing import ReviewPacketRouter
from scripts.controller.safety_matcher import MatcherViolation, SafetyMatcher
from scripts.controller.state_machine import classify_user_feedback, evaluate_control_cycle
from scripts.controller.task_budget import TaskBudgetLedger


class TestCEPControlPlane(unittest.TestCase):
    def setUp(self):
        self.default_pr = CandidatePR(
            number=42,
            title="W01 Shell Refactor",
            workstream_id="W01",
            branch="work/cep-w01-shell",
            head_sha="a1b2c3d4e5f67890123456789012345678901234",
            base_branch="build/cep-v1-integration",
            base_sha="b5d53d2d44c570ebf112c50bec966da01835e5d9",
            is_draft=True,
            is_merged=False,
            is_closed=False,
            changed_files=["routes/workspaces/today.php", "resources/js/components/AppShell.vue"],
        )
        self.default_session = JulesSessionInfo(
            session_id="sess_123",
            name="sessions/sess_123",
            title="W01 Shell Refactor",
            state=JulesState.IN_PROGRESS,
            create_time="2026-08-23T00:00:00Z",
            update_time="2026-08-23T00:00:00Z",
            workstream_id="W01",
            branch="work/cep-w01-shell",
        )

    # 1. Writer active -> wait for Jules when active session working
    def test_writer_active_wait_for_jules(self):
        res = evaluate_control_cycle(self.default_pr, self.default_session, CIState.NO_RUN, None)
        self.assertEqual(res.action_taken, "WAIT_FOR_JULES")

    # 2. Awaiting safe policy feedback -> same-session continuation
    def test_awaiting_safe_policy_feedback_continuation(self):
        sess = JulesSessionInfo(
            session_id="sess_123",
            name="sessions/sess_123",
            title="W01 Shell Refactor",
            state=JulesState.AWAITING_USER_FEEDBACK,
            create_time="2026-08-23T00:00:00Z",
            update_time="2026-08-23T00:00:00Z",
            latest_user_feedback="Should I run vitest frontend tests for this change?",
        )
        res = evaluate_control_cycle(self.default_pr, sess, CIState.NO_RUN, None)
        self.assertEqual(res.action_taken, "CONTINUE_SAME_SESSION")
        self.assertEqual(res.input_classification, InputClassification.CI_EVIDENCE_DEPENDENT)

    # 3. Awaiting authority decision -> escalation only
    def test_awaiting_authority_decision_escalates(self):
        sess = JulesSessionInfo(
            session_id="sess_123",
            name="sessions/sess_123",
            title="W01 Shell Refactor",
            state=JulesState.AWAITING_USER_FEEDBACK,
            create_time="2026-08-23T00:00:00Z",
            update_time="2026-08-23T00:00:00Z",
            latest_user_feedback="Can I merge this feature branch directly to main?",
        )
        res = evaluate_control_cycle(self.default_pr, sess, CIState.NO_RUN, None)
        self.assertEqual(res.action_taken, "ESCALATE_TO_PARENT")
        self.assertEqual(res.workstream_state, WorkstreamState.AUTHORITY_REQUIRED)
        self.assertEqual(res.input_classification, InputClassification.OWNER_OR_CONTROLLER_AUTHORITY_REQUIRED)

    # 4. CI owned failure -> writer correction
    def test_ci_owned_failure_routes_to_writer(self):
        completed_sess = JulesSessionInfo(
            session_id="sess_123",
            name="sessions/sess_123",
            title="W01 Shell Refactor",
            state=JulesState.COMPLETED,
            create_time="2026-08-23T00:00:00Z",
            update_time="2026-08-23T00:00:00Z",
        )
        res = evaluate_control_cycle(self.default_pr, completed_sess, CIState.WORKSTREAM_OWNED_FAILURE, None)
        self.assertEqual(res.action_taken, "SEND_CI_FAILURE_TO_WRITER")

    # 5. CI external transient -> bounded same-SHA retry
    def test_ci_external_transient_retry(self):
        completed_sess = JulesSessionInfo(
            session_id="sess_123",
            name="sessions/sess_123",
            title="W01 Shell Refactor",
            state=JulesState.COMPLETED,
            create_time="2026-08-23T00:00:00Z",
            update_time="2026-08-23T00:00:00Z",
        )
        res = evaluate_control_cycle(self.default_pr, completed_sess, CIState.EXTERNAL_TRANSIENT_FAILURE, None)
        self.assertEqual(res.action_taken, "RETRY_CI_WORKFLOW_SAME_SHA")

    # 6. CI success -> reviewer route
    def test_ci_success_dispatches_reviewer(self):
        completed_sess = JulesSessionInfo(
            session_id="sess_123",
            name="sessions/sess_123",
            title="W01 Shell Refactor",
            state=JulesState.COMPLETED,
            create_time="2026-08-23T00:00:00Z",
            update_time="2026-08-23T00:00:00Z",
        )
        res = evaluate_control_cycle(self.default_pr, completed_sess, CIState.SUCCESS, None)
        self.assertEqual(res.action_taken, "DISPATCH_INDEPENDENT_REVIEWER")

    # 7. Reviewer revision required -> exact-SHA finding packet -> same writer
    def test_reviewer_revision_required_same_writer(self):
        pkt = ReviewPacket(
            workstream_id="W01",
            repo="hamad933/Cybersecurity-Education-Platform",
            candidate_branch="work/cep-w01-shell",
            candidate_full_sha="a1b2c3d4e5f67890123456789012345678901234",
            review_role="reviewer",
            verdict=ReviewState.REVISION_REQUIRED,
            finding_ids=["FIND_001"],
            required_correction="Fix syntax in AppShell.vue",
        )
        router = ReviewPacketRouter()
        decision, reason = router.evaluate_routing(pkt, self.default_pr)
        self.assertEqual(decision, "ROUTE_TO_SAME_WRITER_SESSION")

        completed_sess = JulesSessionInfo(
            session_id="sess_123",
            name="sessions/sess_123",
            title="W01 Shell Refactor",
            state=JulesState.COMPLETED,
            create_time="2026-08-23T00:00:00Z",
            update_time="2026-08-23T00:00:00Z",
        )
        res = evaluate_control_cycle(self.default_pr, completed_sess, CIState.SUCCESS, pkt)
        self.assertEqual(res.action_taken, "SEND_REVIEW_FINDINGS_TO_WRITER")

    # 8. Reviewer stale SHA -> reject
    def test_reviewer_stale_sha_rejected(self):
        pkt = ReviewPacket(
            workstream_id="W01",
            repo="hamad933/Cybersecurity-Education-Platform",
            candidate_branch="work/cep-w01-shell",
            candidate_full_sha="old_sha_123456789012345678901234567890123",
            review_role="reviewer",
            verdict=ReviewState.REVISION_REQUIRED,
        )
        router = ReviewPacketRouter()
        decision, reason = router.evaluate_routing(pkt, self.default_pr)
        self.assertEqual(decision, "REJECT_STALE_SHA")

    # 9. Reviewer mutation -> reject/escalate
    def test_reviewer_mutation_rejected_and_escalated(self):
        pkt = ReviewPacket(
            workstream_id="W01",
            repo="hamad933/Cybersecurity-Education-Platform",
            candidate_branch="work/cep-w01-shell",
            candidate_full_sha="a1b2c3d4e5f67890123456789012345678901234",
            review_role="reviewer",
            verdict=ReviewState.REVISION_REQUIRED,
            reviewer_mutated_code=True,
        )
        router = ReviewPacketRouter()
        decision, reason = router.evaluate_routing(pkt, self.default_pr)
        self.assertEqual(decision, "REJECT_REVIEWER_MUTATION")

        res = evaluate_control_cycle(self.default_pr, self.default_session, CIState.SUCCESS, pkt)
        self.assertEqual(res.action_taken, "ESCALATE_TO_PARENT")
        self.assertEqual(res.review_state, ReviewState.REVIEWER_MUTATION_DETECTED)

    # 10. Correction new SHA -> old review invalidated -> re-review
    def test_correction_new_sha_invalidates_old_review(self):
        new_pr = CandidatePR(
            number=42,
            title="W01 Shell Refactor",
            workstream_id="W01",
            branch="work/cep-w01-shell",
            head_sha="new_sha_98765432109876543210987654321098765",
            base_branch="build/cep-v1-integration",
            base_sha="b5d53d2d44c570ebf112c50bec966da01835e5d9",
            is_draft=True,
            is_merged=False,
            is_closed=False,
        )
        old_pkt = ReviewPacket(
            workstream_id="W01",
            repo="hamad933/Cybersecurity-Education-Platform",
            candidate_branch="work/cep-w01-shell",
            candidate_full_sha="a1b2c3d4e5f67890123456789012345678901234",
            review_role="reviewer",
            verdict=ReviewState.REVISION_REQUIRED,
        )
        completed_sess = JulesSessionInfo(
            session_id="sess_123",
            name="sessions/sess_123",
            title="W01 Shell Refactor",
            state=JulesState.COMPLETED,
            create_time="2026-08-23T00:00:00Z",
            update_time="2026-08-23T00:00:00Z",
        )
        res = evaluate_control_cycle(new_pr, completed_sess, CIState.SUCCESS, old_pkt)
        self.assertEqual(res.action_taken, "DISPATCH_INDEPENDENT_REVIEWER")

    # 11. PASS reviewer -> Parent review pending, never acceptance
    def test_reviewer_pass_escalates_to_parent_review(self):
        pkt = ReviewPacket(
            workstream_id="W01",
            repo="hamad933/Cybersecurity-Education-Platform",
            candidate_branch="work/cep-w01-shell",
            candidate_full_sha="a1b2c3d4e5f67890123456789012345678901234",
            review_role="reviewer",
            verdict=ReviewState.PASS_FOR_PARENT_REVIEW,
        )
        completed_sess = JulesSessionInfo(
            session_id="sess_123",
            name="sessions/sess_123",
            title="W01 Shell Refactor",
            state=JulesState.COMPLETED,
            create_time="2026-08-23T00:00:00Z",
            update_time="2026-08-23T00:00:00Z",
        )
        res = evaluate_control_cycle(self.default_pr, completed_sess, CIState.SUCCESS, pkt)
        self.assertEqual(res.action_taken, "ESCALATE_TO_PARENT_FOR_REVIEW")
        self.assertEqual(res.workstream_state, WorkstreamState.PARENT_REVIEW_PENDING)

    # 12. PR non-draft unexpectedly -> flag/correct metadata
    def test_pr_non_draft_converts_to_draft_metadata(self):
        non_draft_pr = CandidatePR(
            number=42,
            title="W01 Shell Refactor",
            workstream_id="W01",
            branch="work/cep-w01-shell",
            head_sha="a1b2c3d4e5f67890123456789012345678901234",
            base_branch="build/cep-v1-integration",
            base_sha="b5d53d2d44c570ebf112c50bec966da01835e5d9",
            is_draft=False,
            is_merged=False,
            is_closed=False,
        )
        res = evaluate_control_cycle(non_draft_pr, self.default_session, CIState.SUCCESS, None)
        self.assertEqual(res.action_taken, "CONVERT_TO_DRAFT_METADATA_ONLY")
        self.assertEqual(res.workstream_state, WorkstreamState.CANDIDATE_NON_DRAFT_UNAUTHORIZED)

    # 13. Base mismatch / integration drift -> fail closed
    def test_base_mismatch_fails_closed(self):
        drift_pr = CandidatePR(
            number=42,
            title="W01 Shell Refactor",
            workstream_id="W01",
            branch="work/cep-w01-shell",
            head_sha="a1b2c3d4e5f67890123456789012345678901234",
            base_branch="main",
            base_sha="b5d53d2d44c570ebf112c50bec966da01835e5d9",
            is_draft=True,
            is_merged=False,
            is_closed=False,
        )
        res = evaluate_control_cycle(drift_pr, self.default_session, CIState.SUCCESS, None)
        self.assertEqual(res.action_taken, "ESCALATE_TO_PARENT")
        self.assertEqual(res.workstream_state, WorkstreamState.PUBLICATION_BASE_MISMATCH)

    # 14. Writer scope violation / shared path collision -> escalation
    def test_writer_scope_violation_and_collision(self):
        matcher = SafetyMatcher()
        bad_pr = CandidatePR(
            number=99,
            title="W01 Illegal Mutation",
            workstream_id="W01",
            branch="work/cep-w01-shell",
            head_sha="a1b2c3d4e5f67890123456789012345678901234",
            base_branch="build/cep-v1-integration",
            base_sha="b5d53d2d44c570ebf112c50bec966da01835e5d9",
            is_draft=True,
            is_merged=False,
            is_closed=False,
            changed_files=["composer.json", "routes/workspaces/knowledge-learning.php"],
        )
        violations = matcher.evaluate_pr_safety(bad_pr)
        self.assertTrue(any(v.violation_code == "LOCKED_SYSTEM_FILE_MUTATION" for v in violations))
        self.assertTrue(any(v.violation_code == "CROSS_WORKSPACE_ROUTE_MUTATION" for v in violations))

    # 15. Duplicate operation replay -> no duplicate message/task
    def test_duplicate_operation_replay_prevented(self):
        idempotency = IdempotencyEngine()
        key1 = compute_operation_key("repo", "W01", "writer", 42, "sha123", "digest", "SEND_MESSAGE")
        idempotency.record_operation(key1)
        self.assertTrue(idempotency.is_duplicate(key1))

    # 16. Task budget near reserve -> no noncritical new task
    def test_task_budget_near_reserve_blocks_noncritical(self):
        ledger = TaskBudgetLedger(observed_used_count=58)
        status = ledger.check_new_task_allowed(is_critical_assurance=False)
        self.assertFalse(status.can_create_new_task)

        status_critical = ledger.check_new_task_allowed(is_critical_assurance=True)
        self.assertTrue(status_critical.can_create_new_task)

    # 17. Task budget exhausted or unknown -> no automatic new session
    test_task_budget_unknown_blocks_new_task = lambda self: self.assertFalse(TaskBudgetLedger(observed_used_count=None).check_new_task_allowed().can_create_new_task)

    # 18. Missing Jules API key -> safe degraded behavior
    def test_missing_jules_api_key_degraded_mode(self):
        adapter = JulesAdapter(api_key="")
        self.assertTrue(adapter.is_degraded_mode)
        with self.assertRaises(JulesAdapterError) as ctx:
            adapter.list_sessions()
        self.assertEqual(ctx.exception.classification, "DEGRADED_GITHUB_ONLY_MODE")

    # 19. Environment mismatch auto-answer
    def test_environment_mismatch_feedback_auto_answer(self):
        sess = JulesSessionInfo(
            session_id="sess_123",
            name="sessions/sess_123",
            title="W01 Shell Refactor",
            state=JulesState.AWAITING_USER_FEEDBACK,
            create_time="2026-08-23T00:00:00Z",
            update_time="2026-08-23T00:00:00Z",
            latest_user_feedback="Local PHP version is 8.2 which is older than repo requirements.",
        )
        res = evaluate_control_cycle(self.default_pr, sess, CIState.NO_RUN, None)
        self.assertEqual(res.action_taken, "AUTO_ANSWER_ENVIRONMENT_MISMATCH")
        self.assertEqual(res.input_classification, InputClassification.ENVIRONMENT_MISMATCH)

    # 20. Execution receipt secret sanitization
    def test_execution_receipt_secret_sanitization(self):
        raw_receipt = {
            "api_key": "secret_12345",
            "repo": "hamad933/Cybersecurity-Education-Platform",
            "nested": {"token": "bearer_abc"},
        }
        sanitized = sanitize_receipt_data(raw_receipt)
        self.assertEqual(sanitized["api_key"], "[REDACTED_SECRET]")
        self.assertEqual(sanitized["nested"]["token"], "[REDACTED_SECRET]")

    # 21. Multi-writer duplicate branch conflict detection
    def test_multi_writer_duplicate_branch_conflict(self):
        matcher = SafetyMatcher()
        pr1 = CandidatePR(
            number=42,
            title="W01 Shell Refactor",
            workstream_id="W01",
            branch="work/cep-w01-shell",
            head_sha="a1b2c3d4e5f67890123456789012345678901234",
            base_branch="build/cep-v1-integration",
            base_sha="b5d53d2d44c570ebf112c50bec966da01835e5d9",
            is_draft=True,
            is_merged=False,
            is_closed=False,
        )
        pr2 = CandidatePR(
            number=43,
            title="W01 Shell Refactor Duplicate",
            workstream_id="W01",
            branch="work/cep-w01-shell",
            head_sha="b2c3d4e5f6789012345678901234567890123456",
            base_branch="build/cep-v1-integration",
            base_sha="b5d53d2d44c570ebf112c50bec966da01835e5d9",
            is_draft=True,
            is_merged=False,
            is_closed=False,
        )
        violations = matcher.evaluate_pr_safety(pr1, [pr2])
        self.assertTrue(any(v.violation_code == "DUPLICATE_BRANCH_WRITER_CONFLICT" for v in violations))

    # 22. Unknown future Jules state handling
    def test_unknown_future_jules_state_handling(self):
        norm = JulesState.normalize("FUTURE_UNSEEN_STATE_XYZ")
        self.assertEqual(norm, JulesState.UNKNOWN_JULES_STATE)

    # 23. Unknown future CI state handling
    def test_unknown_future_ci_state_handling(self):
        norm = CIState.normalize("FUTURE_CI_STATE_123")
        self.assertEqual(norm, CIState.UNKNOWN_CI_STATE)

    # 24. Task budget ceiling reached
    def test_task_budget_ceiling_reached(self):
        ledger = TaskBudgetLedger(observed_used_count=70)
        status = ledger.check_new_task_allowed(is_critical_assurance=True)
        self.assertFalse(status.can_create_new_task)
        self.assertTrue(status.is_exhausted_or_unknown)

    # 25. Same branch with multiple related findings -> one coherent continuation prompt
    def test_same_branch_coherent_continuation_prompt(self):
        pkt = ReviewPacket(
            workstream_id="W01",
            repo="hamad933/Cybersecurity-Education-Platform",
            candidate_branch="work/cep-w01-shell",
            candidate_full_sha="a1b2c3d4e5f67890123456789012345678901234",
            review_role="reviewer",
            verdict=ReviewState.REVISION_REQUIRED,
            finding_ids=["FIND_001", "FIND_002"],
            severity="high",
            affected_paths=["resources/js/components/AppShell.vue", "routes/workspaces/today.php"],
            required_correction="Fix syntax in AppShell.vue and update route binding in today.php",
            preservation_requirements=["Preserve physical LTR grid and RTL text handling"],
        )
        router = ReviewPacketRouter()
        prompt = router.build_writer_continuation_prompt(pkt)
        self.assertIn("FIND_001, FIND_002", prompt)
        self.assertIn("resources/js/components/AppShell.vue", prompt)
        self.assertIn("Preserve physical LTR grid", prompt)


if __name__ == "__main__":
    unittest.main()

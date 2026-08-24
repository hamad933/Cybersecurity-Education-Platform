"""
CEP Fast Control Plane - Reviewer Packet Router.
Standard library only (Python 3.10+).

Contract:
- Workstream ID
- Repo
- Candidate Branch
- Candidate Full SHA
- Review Role/Type
- Verdict
- Finding IDs
- Severity
- Affected Paths/Surfaces
- Root-cause Group
- Required Correction
- Preservation Requirements
- Evidence Pointers
- Authority Required Boolean
- Timestamp / Source Identity
"""

from typing import Dict, List, Optional, Tuple
from scripts.controller.models import CandidatePR, ReviewPacket, ReviewState


class ReviewRouterError(Exception):
    pass


class ReviewPacketRouter:
    def validate_packet(self, packet: ReviewPacket) -> List[str]:
        errors = []
        if not packet.workstream_id:
            errors.append("Missing workstream_id")
        if not packet.repo:
            errors.append("Missing repo")
        if not packet.candidate_branch:
            errors.append("Missing candidate_branch")
        if not packet.candidate_full_sha:
            errors.append("Missing candidate_full_sha")
        if not packet.verdict:
            errors.append("Missing verdict")
        return errors

    def evaluate_routing(
        self,
        packet: ReviewPacket,
        current_pr: Optional[CandidatePR],
    ) -> Tuple[str, Optional[str]]:
        """
        Evaluates structured review packet against current PR binding.
        Returns tuple: (routing_decision, optional_rejection_reason)
        """
        validation_errors = self.validate_packet(packet)
        if validation_errors:
            return "REJECT_INVALID_PACKET", f"Validation failed: {', '.join(validation_errors)}"

        # 1. Reviewer Mutation Check
        if packet.reviewer_mutated_code:
            return (
                "REJECT_REVIEWER_MUTATION",
                "Reviewer attempted code mutation. Independent review must remain strictly read-only.",
            )

        # 2. PR Binding Check
        if not current_pr:
            return "REJECT_NO_BOUND_PR", f"No candidate PR found bound to workstream '{packet.workstream_id}'"

        if packet.repo != current_pr.repo:
            return (
                "REJECT_REPO_MISMATCH",
                f"Review packet repo '{packet.repo}' does not match PR repo '{current_pr.repo}'",
            )

        if packet.candidate_branch != current_pr.branch:
            return (
                "REJECT_BRANCH_MISMATCH",
                f"Review packet branch '{packet.candidate_branch}' does not match PR branch '{current_pr.branch}'",
            )

        # 3. Exact Candidate SHA Binding
        if packet.candidate_full_sha != current_pr.head_sha:
            return (
                "REJECT_STALE_SHA",
                f"Review packet SHA '{packet.candidate_full_sha}' does not match active head SHA '{current_pr.head_sha}'",
            )

        # 4. Verdict Routing
        if packet.verdict in (ReviewState.REVISION_REQUIRED, ReviewState.BLOCKING_FAILURE):
            if packet.authority_required:
                return "ESCALATE_TO_PARENT", "Reviewer findings require Parent/Owner authority."
            return "ROUTE_TO_SAME_WRITER_SESSION", None

        if packet.verdict in (ReviewState.PASS_FOR_PARENT_REVIEW, ReviewState.PASS_WITH_MINOR_POLISH):
            return "ROUTE_TO_PARENT_REVIEW", None

        return "ESCALATE_TO_PARENT", f"Unhandled review verdict: {packet.verdict.value}"

    def build_writer_continuation_prompt(self, packet: ReviewPacket) -> str:
        """
        Builds a structured, coherent continuation prompt for the writer session.
        Groups related findings into a single message.
        """
        prompt_lines = [
            f"### Reviewer Findings for Candidate SHA `{packet.candidate_full_sha[:10]}`",
            f"**Verdict**: {packet.verdict.value}",
            f"**Severity**: {packet.severity}",
            f"**Root Cause Group**: {packet.root_cause_group}",
            "",
            "#### Required Corrections:",
            packet.required_correction or "Address reported review findings.",
        ]

        if packet.finding_ids:
            prompt_lines.append(f"\n**Finding IDs**: {', '.join(packet.finding_ids)}")

        if packet.affected_paths:
            prompt_lines.append("\n**Affected Paths**:")
            for p in packet.affected_paths:
                prompt_lines.append(f"- `{p}`")

        if packet.preservation_requirements:
            prompt_lines.append("\n**Preservation Requirements (DO NOT BREAK)**:")
            for req in packet.preservation_requirements:
                prompt_lines.append(f"- {req}")

        if packet.evidence_pointers:
            prompt_lines.append("\n**Evidence Pointers**:")
            for ev in packet.evidence_pointers:
                prompt_lines.append(f"- {ev}")

        return "\n".join(prompt_lines)

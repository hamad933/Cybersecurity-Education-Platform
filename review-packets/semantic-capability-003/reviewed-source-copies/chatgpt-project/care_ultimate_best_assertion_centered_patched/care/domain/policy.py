"""Canonical policy domain exports.

CARE no longer keeps a separate ``care.domain.policy`` model family.  The
assertion-centered model in ``care.policy.types`` is the source of truth.  This
module re-exports it so existing imports that use ``care.domain.*`` continue to
work without reintroducing ``care.models.*`` compatibility folders.
"""
from __future__ import annotations

from care.policy.types import (  # noqa: F401
    Assertion,
    AssertionCommand,
    AssertionEvidence,
    AssertionEvaluation,
    AssertionFailure,
    AssertionNormalize,
    AssertionOutput,
    AssertionRemediation,
    AssertionVerification,
    EvidenceCompose,
    EvidenceRequirement,
    EvidenceStep,
    EvidenceTask,
    EvaluationLogic,
    EvaluationResultConfig,
    EvaluationRule,
    FactMapping,
    Policy,
    PolicyAssertion,
    PolicyMetadata,
    PolicyScope,
    PolicySet,
    RemediationSpec,
)

NormalizeRule = FactMapping


__all__ = [
    "Assertion", "Policy", "PolicySet", "PolicyAssertion", "PolicyMetadata", "PolicyScope",
    "AssertionCommand", "AssertionEvidence", "AssertionEvaluation", "AssertionFailure",
    "AssertionNormalize", "AssertionOutput", "AssertionRemediation", "AssertionVerification",
    "EvidenceCompose", "EvidenceStep", "EvidenceTask", "EvaluationLogic",
    "EvaluationResultConfig", "EvaluationRule", "FactMapping", "NormalizeRule", "EvidenceRequirement", "RemediationSpec",
]

from __future__ import annotations

from dataclasses import dataclass
from enum import StrEnum
from typing import Any, Protocol

from .digest import sha256_text
from .models import ErrorClassification, GatewayError


class IdempotencyState(StrEnum):
    NOT_SEEN = "NOT_SEEN"
    INTENT_RECORDED = "INTENT_RECORDED"
    COMPLETED = "COMPLETED"
    UNKNOWN_WRITE_OUTCOME = "UNKNOWN_WRITE_OUTCOME"
    RECONCILIATION_REQUIRED = "RECONCILIATION_REQUIRED"


class ArtifactReader(Protocol):
    def list_active_artifacts_by_name(self, name: str) -> list[dict[str, Any]]: ...


@dataclass(frozen=True)
class IdempotencySnapshot:
    observed_state: IdempotencyState
    decision_state: IdempotencyState
    marker_counts: dict[str, int]

    @property
    def may_record_new_intent(self) -> bool:
        return self.decision_state == IdempotencyState.NOT_SEEN

    def to_dict(self) -> dict[str, Any]:
        return {
            "observed_state": self.observed_state.value,
            "decision_state": self.decision_state.value,
            "marker_counts": dict(self.marker_counts),
            "may_record_new_intent": self.may_record_new_intent,
        }


def marker_prefix(request_id: str) -> str:
    return "cep-jules-v2-idem-" + sha256_text(request_id)[:32]


def marker_name(request_id: str, state: IdempotencyState) -> str:
    if state not in {
        IdempotencyState.INTENT_RECORDED,
        IdempotencyState.COMPLETED,
        IdempotencyState.UNKNOWN_WRITE_OUTCOME,
    }:
        raise ValueError("only durable states have artifact markers")
    return f"{marker_prefix(request_id)}-{state.value}"


def create_effect_guard_name(effect_key: str) -> str:
    if not effect_key.startswith("effect-") or len(effect_key) > 80:
        raise ValueError("invalid effect key")
    return f"cep-jules-v2-create-{effect_key}-INTENT_RECORDED"


def inspect_idempotency(reader: ArtifactReader, request_id: str) -> IdempotencySnapshot:
    states = (
        IdempotencyState.COMPLETED,
        IdempotencyState.UNKNOWN_WRITE_OUTCOME,
        IdempotencyState.INTENT_RECORDED,
    )
    counts = {state.value: len(reader.list_active_artifacts_by_name(marker_name(request_id, state))) for state in states}
    if counts[IdempotencyState.COMPLETED.value]:
        observed = IdempotencyState.COMPLETED
        decision = IdempotencyState.COMPLETED
    elif counts[IdempotencyState.UNKNOWN_WRITE_OUTCOME.value]:
        observed = IdempotencyState.UNKNOWN_WRITE_OUTCOME
        decision = IdempotencyState.RECONCILIATION_REQUIRED
    elif counts[IdempotencyState.INTENT_RECORDED.value]:
        observed = IdempotencyState.INTENT_RECORDED
        decision = IdempotencyState.RECONCILIATION_REQUIRED
    else:
        observed = IdempotencyState.NOT_SEEN
        decision = IdempotencyState.NOT_SEEN
    return IdempotencySnapshot(observed, decision, counts)


def require_new_intent(snapshot: IdempotencySnapshot) -> None:
    if snapshot.decision_state == IdempotencyState.COMPLETED:
        raise GatewayError(
            ErrorClassification.IDEMPOTENCY_CONFLICT,
            "request_id already has a completed repository-wide v2 receipt; replay is prohibited",
            details=snapshot.to_dict(),
        )
    if snapshot.decision_state == IdempotencyState.RECONCILIATION_REQUIRED:
        raise GatewayError(
            ErrorClassification.RECONCILIATION_REQUIRED,
            "request_id has prior durable write intent or unknown outcome; reconcile before any replay",
            details={**snapshot.to_dict(), "blind_retry": False},
        )


def require_unused_create_effect(reader: ArtifactReader, effect_key: str) -> None:
    marker = create_effect_guard_name(effect_key)
    count = len(reader.list_active_artifacts_by_name(marker))
    if count:
        raise GatewayError(
            ErrorClassification.RECONCILIATION_REQUIRED,
            "pre-session logical write effect already has durable create intent; refusing a second session create",
            details={"effect_key": effect_key, "active_effect_intents": count, "blind_retry": False},
        )

from __future__ import annotations

from dataclasses import asdict, dataclass, field
from enum import StrEnum
from typing import Any


class ProviderOutcome(StrEnum):
    FOUND = "FOUND"
    NOT_FOUND = "NOT_FOUND"
    NOT_AVAILABLE_FROM_PROVIDER = "NOT_AVAILABLE_FROM_PROVIDER"
    READ_FAILED = "READ_FAILED"
    RATE_LIMITED = "RATE_LIMITED"
    VERIFIED = "VERIFIED"
    UNKNOWN = "UNKNOWN"
    REJECTED = "REJECTED"


class Completeness(StrEnum):
    COMPLETE = "COMPLETE"
    PARTIAL = "PARTIAL"
    READ_FAILED = "READ_FAILED"


class ErrorClassification(StrEnum):
    RATE_LIMITED = "RATE_LIMITED"
    AUTH_FAILED = "AUTH_FAILED"
    NOT_FOUND = "NOT_FOUND"
    INVALID_STATE = "INVALID_STATE"
    PROVIDER_READ_FAILED = "PROVIDER_READ_FAILED"
    PROVIDER_PROTOCOL_FAILED = "PROVIDER_PROTOCOL_FAILED"
    PROVIDER_WRITE_OUTCOME_UNKNOWN = "PROVIDER_WRITE_OUTCOME_UNKNOWN"
    PAGINATION_LIMIT_EXCEEDED = "PAGINATION_LIMIT_EXCEEDED"
    READ_BUDGET_EXCEEDED = "READ_BUDGET_EXCEEDED"
    OUTPUT_BUDGET_EXCEEDED = "OUTPUT_BUDGET_EXCEEDED"
    PLAN_CHANGED_SINCE_REVIEW = "PLAN_CHANGED_SINCE_REVIEW__REAPPROVAL_REQUIRED"
    IDEMPOTENCY_CONFLICT = "IDEMPOTENCY_CONFLICT"
    RECONCILIATION_REQUIRED = "RECONCILIATION_REQUIRED"
    INVALID_REQUEST = "INVALID_REQUEST"


@dataclass(frozen=True)
class SafeRetryMetadata:
    retry_after_seconds: int | None = None
    rate_limit_limit: str | None = None
    rate_limit_remaining: str | None = None
    rate_limit_reset: str | None = None


@dataclass(frozen=True)
class ProviderObservation:
    operation: str
    http_status: int
    classification: str | None = None
    retry: SafeRetryMetadata | None = None

    def to_dict(self) -> dict[str, Any]:
        data = asdict(self)
        if self.retry is None:
            data.pop("retry", None)
        return data


@dataclass(frozen=True)
class PaginationInfo:
    pages_scanned: int
    items_scanned: int
    complete: bool
    limit_pages: int
    limit_items: int | None = None
    next_page_token: str | None = None
    stop_reason: str | None = None

    def to_dict(self) -> dict[str, Any]:
        return {key: value for key, value in asdict(self).items() if value is not None}


@dataclass
class GatewayError(Exception):
    classification: ErrorClassification
    message: str
    http_status: int | None = None
    retry: SafeRetryMetadata | None = None
    details: dict[str, Any] = field(default_factory=dict)

    def __str__(self) -> str:
        return f"{self.classification}: {self.message}"

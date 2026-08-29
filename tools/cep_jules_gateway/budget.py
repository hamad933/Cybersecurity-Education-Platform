from __future__ import annotations

from dataclasses import dataclass, field
from typing import Any, Protocol

from .models import ErrorClassification, GatewayError
from .selection import validate_activity_identity


class ActivityReader(Protocol):
    def get_activity(self, activity_name: str) -> dict[str, Any]: ...


@dataclass
class ExactTextBudget:
    limit_bytes: int
    used_bytes: int = 0
    omissions: int = 0

    def include(self, text: str) -> bool:
        size = len(text.encode("utf-8"))
        if self.used_bytes + size > self.limit_bytes:
            self.omissions += 1
            return False
        self.used_bytes += size
        return True

    @property
    def exhausted(self) -> bool:
        return self.omissions > 0


@dataclass
class HydrationPool:
    client: ActivityReader
    session_id: str
    max_reads: int
    reads: int = 0
    cache_hits: int = 0
    cache: dict[str, dict[str, Any]] = field(default_factory=dict)

    def get(self, activity_name: str) -> dict[str, Any]:
        if activity_name in self.cache:
            self.cache_hits += 1
            return self.cache[activity_name]
        if self.reads >= self.max_reads:
            raise GatewayError(
                ErrorClassification.READ_BUDGET_EXCEEDED,
                "shared inspect_bundle hydration budget exhausted",
                details={"hydration_reads": self.reads, "max_hydration_reads": self.max_reads},
            )
        self.reads += 1
        value = self.client.get_activity(activity_name)
        validate_activity_identity(value, self.session_id)
        if str(value.get("name") or "") != activity_name:
            raise GatewayError(
                ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                "hydrated activity identity differs from requested identity",
                details={"requested_activity": activity_name},
            )
        self.cache[activity_name] = value
        return value

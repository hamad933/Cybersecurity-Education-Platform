"""Baseline domain objects."""
from __future__ import annotations
from dataclasses import dataclass, field
from typing import Any
from .common import SerializableMixin, utc_now

@dataclass
class BaselineItem(SerializableMixin):
    key: str = ""
    expected: Any = None
    description: str = ""
    evidence_required: list[str] = field(default_factory=list)
    metadata: dict[str, Any] = field(default_factory=dict)

@dataclass
class Baseline(SerializableMixin):
    baseline_id: str = ""
    name: str = ""
    platform: str = "generic"
    version: str = "1.0"
    items: list[BaselineItem] = field(default_factory=list)
    created_at: str = field(default_factory=utc_now)
    metadata: dict[str, Any] = field(default_factory=dict)

    def get(self, key: str) -> BaselineItem | None:
        return next((item for item in self.items if item.key == key), None)

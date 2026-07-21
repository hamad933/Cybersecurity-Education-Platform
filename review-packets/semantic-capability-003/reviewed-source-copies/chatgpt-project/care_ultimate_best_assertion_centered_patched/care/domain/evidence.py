"""Evidence artifact and chain-of-custody models."""
from __future__ import annotations

from dataclasses import dataclass, field
from hashlib import sha256
from typing import Any

from .common import SerializableMixin, new_id, stable_hash, utc_now
from .enums import EvidenceType


def hash_bytes(data: bytes) -> str:
    return sha256(data).hexdigest()


@dataclass(slots=True)
class EvidenceItem(SerializableMixin):
    evidence_id: str = field(default_factory=lambda: new_id("EVID"))
    evidence_type: EvidenceType | str = EvidenceType.OTHER
    title: str = ""
    source: str = "unknown"
    path: str | None = None
    content_hash: str | None = None
    collected_at: str = field(default_factory=utc_now)
    assertion_id: str | None = None
    policy_id: str | None = None
    asset_id: str | None = None
    command: str | None = None
    metadata: dict[str, Any] = field(default_factory=dict)

    @classmethod
    def from_content(cls, title: str, content: str | bytes, **kwargs: Any) -> "EvidenceItem":
        raw = content.encode("utf-8") if isinstance(content, str) else content
        return cls(title=title, content_hash=hash_bytes(raw), **kwargs)

    @classmethod
    def from_collection(cls, raw: Any, title: str | None = None) -> "EvidenceItem":
        payload_hash = stable_hash(getattr(raw, "payload", None))
        return cls(
            evidence_type=EvidenceType.ASSERTION_EVIDENCE,
            title=title or f"Evidence for {getattr(raw, 'assertion_id', 'unknown')}",
            source=getattr(raw, "source", "unknown"),
            content_hash=payload_hash,
            collected_at=getattr(raw, "collected_at", utc_now()),
            assertion_id=getattr(raw, "assertion_id", None),
            policy_id=getattr(raw, "policy_id", None),
            asset_id=getattr(raw, "asset_id", None),
            command=getattr(raw, "command", None),
            metadata={"raw_metadata": getattr(raw, "metadata", {})},
        )


@dataclass(slots=True)
class EvidenceSet(SerializableMixin):
    items: list[EvidenceItem] = field(default_factory=list)
    metadata: dict[str, Any] = field(default_factory=dict)

    def add(self, item: EvidenceItem) -> EvidenceItem:
        self.items.append(item)
        return item

    def append(self, item: EvidenceItem) -> None:
        self.add(item)

    def refs(self) -> list[str]:
        return [item.evidence_id for item in self.items]

    def by_assertion(self, assertion_id: str) -> list[EvidenceItem]:
        return [item for item in self.items if item.assertion_id == assertion_id]


@dataclass(slots=True)
class AssertionEvidenceRecord(SerializableMixin):
    assertion_id: str
    asset_id: str
    raw_collection_id: str | None = None
    parsed_ref: str | None = None
    normalized_hash: str | None = None
    evaluation_ref: str | None = None
    evidence_refs: list[str] = field(default_factory=list)
    created_at: str = field(default_factory=utc_now)
    metadata: dict[str, Any] = field(default_factory=dict)


@dataclass(slots=True)
class EvidencePackage(SerializableMixin):
    package_id: str = field(default_factory=lambda: new_id("EPKG"))
    run_id: str = "unknown"
    asset_id: str | None = None
    items: list[EvidenceItem] = field(default_factory=list)
    assertion_records: list[AssertionEvidenceRecord] = field(default_factory=list)
    manifest_hash: str | None = None
    created_at: str = field(default_factory=utc_now)
    chain: list[str] = field(default_factory=list)
    metadata: dict[str, Any] = field(default_factory=dict)

    def add(self, item: EvidenceItem) -> EvidenceItem:
        self.items.append(item)
        return item

    def append(self, item: EvidenceItem) -> None:
        self.add(item)

    def add_record(self, record: AssertionEvidenceRecord) -> AssertionEvidenceRecord:
        self.assertion_records.append(record)
        return record

    def compute_manifest_hash(self) -> str:
        joined = "|".join(sorted(item.content_hash or item.evidence_id for item in self.items))
        joined += "|" + "|".join(sorted(stable_hash(record.to_dict()) for record in self.assertion_records))
        self.manifest_hash = sha256(joined.encode("utf-8")).hexdigest()
        return self.manifest_hash


EvidenceBundle = EvidencePackage
EvidenceManifest = EvidencePackage

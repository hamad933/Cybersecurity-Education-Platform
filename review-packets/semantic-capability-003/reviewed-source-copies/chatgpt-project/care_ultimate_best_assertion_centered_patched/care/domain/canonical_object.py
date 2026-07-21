"""Canonical object and routing candidate domain models for CARE.

These objects are intentionally platform-neutral.  Parsers and normalizers may
produce vendor-specific fields, but reactive routing, assertion selection,
coverage accounting, and evidence packages should communicate through these
canonical objects.
"""
from __future__ import annotations

from dataclasses import dataclass, field as dc_field
from typing import Any, Mapping

from .common import SerializableMixin, ensure_dict, stable_hash, stringify_list, utc_now


@dataclass(slots=True)
class CanonicalThreatCandidate(SerializableMixin):
    """A candidate threat classification produced by mapping/routing.

    Candidate objects are evidence-bearing suggestions, not final authority.
    The selected threat remains explainable through score, confidence, and
    reasons.
    """

    threat_id: str
    name: str = ""
    domain: str = "unknown"
    tactics: list[str] = dc_field(default_factory=list)
    techniques: list[str] = dc_field(default_factory=list)
    sources: list[str] = dc_field(default_factory=list)
    score: float = 0.0
    confidence: str = "medium"
    reasons: list[str] = dc_field(default_factory=list)
    metadata: dict[str, Any] = dc_field(default_factory=dict)

    @property
    def id(self) -> str:
        return self.threat_id

    @classmethod
    def from_dict(cls, data: Mapping[str, Any] | str | None) -> "CanonicalThreatCandidate":
        if isinstance(data, str):
            value = {"id": data}
        else:
            value = ensure_dict(data)
        return cls(
            threat_id=str(value.get("threat_id") or value.get("id") or "unknown"),
            name=str(value.get("name") or value.get("title") or ""),
            domain=str(value.get("domain") or "unknown"),
            tactics=stringify_list(value.get("tactics")),
            techniques=stringify_list(value.get("techniques")),
            sources=stringify_list(value.get("sources")),
            score=float(value.get("score") or 0.0),
            confidence=str(value.get("confidence") or "medium"),
            reasons=stringify_list(value.get("reasons")),
            metadata=ensure_dict(value.get("metadata")),
        )


@dataclass(slots=True)
class CanonicalControlCandidate(SerializableMixin):
    """A candidate control/policy assertion target derived from an event."""

    control_id: str
    name: str = ""
    domain: str = "unknown"
    framework_refs: list[str] = dc_field(default_factory=list)
    assertions: list[str] = dc_field(default_factory=list)
    detections: list[str] = dc_field(default_factory=list)
    responses: list[str] = dc_field(default_factory=list)
    recoveries: list[str] = dc_field(default_factory=list)
    score: float = 0.0
    confidence: str = "medium"
    reasons: list[str] = dc_field(default_factory=list)
    metadata: dict[str, Any] = dc_field(default_factory=dict)

    @property
    def id(self) -> str:
        return self.control_id

    @classmethod
    def from_dict(cls, data: Mapping[str, Any] | str | None) -> "CanonicalControlCandidate":
        if isinstance(data, str):
            value = {"id": data}
        else:
            value = ensure_dict(data)
        return cls(
            control_id=str(value.get("control_id") or value.get("id") or "unknown"),
            name=str(value.get("name") or value.get("title") or ""),
            domain=str(value.get("domain") or "unknown"),
            framework_refs=stringify_list(value.get("framework_refs") or value.get("frameworks")),
            assertions=stringify_list(value.get("assertions")),
            detections=stringify_list(value.get("detections")),
            responses=stringify_list(value.get("responses")),
            recoveries=stringify_list(value.get("recoveries")),
            score=float(value.get("score") or 0.0),
            confidence=str(value.get("confidence") or "medium"),
            reasons=stringify_list(value.get("reasons")),
            metadata=ensure_dict(value.get("metadata")),
        )


@dataclass(slots=True)
class CanonicalObject(SerializableMixin):
    """Platform-neutral object affected by an event, drift, alert, or proof.

    Examples: interface Gi1/0/24, AD user alice, firewall policy 101,
    Kubernetes namespace payments, AWS IAM role AppDeployRole.
    """

    object_type: str = "unknown"
    object_id: str = "unknown"
    name: str | None = None
    domain: str = "unknown"
    platform: str = "unknown"
    vendor: str | None = None
    product: str | None = None
    canonical_path: str | None = None
    parent_path: str | None = None
    parent_section: str | None = None
    field: str | None = None
    scope: dict[str, Any] = dc_field(default_factory=dict)
    role: str | None = None
    zone: str | None = None
    tier: str | None = None
    tags: list[str] = dc_field(default_factory=list)
    attributes: dict[str, Any] = dc_field(default_factory=dict)
    evidence_refs: list[str] = dc_field(default_factory=list)
    metadata: dict[str, Any] = dc_field(default_factory=dict)
    observed_at: str = dc_field(default_factory=utc_now)

    @classmethod
    def from_dict(cls, data: Mapping[str, Any] | None) -> "CanonicalObject":
        value = ensure_dict(data)
        return cls(
            object_type=str(value.get("object_type") or value.get("type") or "unknown"),
            object_id=str(value.get("object_id") or value.get("id") or value.get("name") or "unknown"),
            name=value.get("name"),
            domain=str(value.get("domain") or "unknown"),
            platform=str(value.get("platform") or "unknown"),
            vendor=value.get("vendor"),
            product=value.get("product"),
            canonical_path=value.get("canonical_path"),
            parent_path=value.get("parent_path"),
            parent_section=value.get("parent_section"),
            field=value.get("field"),
            scope=ensure_dict(value.get("scope")),
            role=value.get("role"),
            zone=value.get("zone"),
            tier=value.get("tier"),
            tags=stringify_list(value.get("tags")),
            attributes=ensure_dict(value.get("attributes")),
            evidence_refs=stringify_list(value.get("evidence_refs")),
            metadata=ensure_dict(value.get("metadata")),
            observed_at=str(value.get("observed_at") or utc_now()),
        )

    def fingerprint(self) -> str:
        return stable_hash(
            {
                "domain": self.domain,
                "platform": self.platform,
                "object_type": self.object_type,
                "object_id": self.object_id,
                "canonical_path": self.canonical_path,
                "field": self.field,
            }
        )

    def routing_key(self) -> str:
        clean_path = self.canonical_path or f"{self.object_type}.{self.object_id}"
        return f"{self.domain}:{self.platform}:{clean_path}"


__all__ = [
    "CanonicalControlCandidate",
    "CanonicalObject",
    "CanonicalThreatCandidate",
]

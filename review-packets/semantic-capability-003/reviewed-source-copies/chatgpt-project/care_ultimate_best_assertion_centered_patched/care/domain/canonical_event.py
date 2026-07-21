"""Canonical reactive event schema v2 for CARE."""
from __future__ import annotations

from dataclasses import dataclass, field
from typing import Any, Mapping

from .canonical_config import CanonicalChange
from .canonical_object import CanonicalControlCandidate, CanonicalObject, CanonicalThreatCandidate
from .common import SerializableMixin, ensure_dict, listify, new_id, stable_hash, stringify_list, utc_now


def _nested_candidate_list(data: Mapping[str, Any], *paths: str) -> list[Any]:
    for path in paths:
        current: Any = data
        for part in path.split('.'):
            if isinstance(current, Mapping):
                current = current.get(part)
            else:
                current = None
                break
        if current not in (None, '', [], {}):
            return listify(current)
    return []



@dataclass(slots=True)
class CanonicalAsset(SerializableMixin):
    """Normalized asset identity and business/security context."""

    id: str = "unknown"
    hostname: str | None = None
    ip: str | None = None
    mac: str | None = None
    platform: str = "unknown"
    vendor: str | None = None
    product: str | None = None
    role: str | None = None
    zone: str | None = None
    tier: str | None = None
    environment: str | None = None
    criticality: str = "medium"
    owner: str | None = None
    business_service: str | None = None
    tags: list[str] = field(default_factory=list)
    metadata: dict[str, Any] = field(default_factory=dict)

    @classmethod
    def from_dict(cls, data: Mapping[str, Any] | None) -> "CanonicalAsset":
        value = ensure_dict(data)
        return cls(
            id=str(value.get("id") or value.get("asset_id") or value.get("hostname") or value.get("ip") or "unknown"),
            hostname=value.get("hostname") or value.get("host") or value.get("name"),
            ip=value.get("ip") or value.get("address"),
            mac=value.get("mac"),
            platform=str(value.get("platform") or "unknown"),
            vendor=value.get("vendor"),
            product=value.get("product"),
            role=value.get("role"),
            zone=value.get("zone"),
            tier=value.get("tier"),
            environment=value.get("environment"),
            criticality=str(value.get("criticality") or "medium"),
            owner=value.get("owner"),
            business_service=value.get("business_service"),
            tags=stringify_list(value.get("tags")),
            metadata=ensure_dict(value.get("metadata")),
        )


@dataclass(slots=True)
class CanonicalActor(SerializableMixin):
    """Normalized actor/user/process/source context."""

    user: str | None = None
    user_id: str | None = None
    domain: str | None = None
    account_type: str | None = None
    privilege_level: str | None = None
    source_ip: str | None = None
    source_mac: str | None = None
    process: str | None = None
    process_id: str | None = None
    parent_process: str | None = None
    api_key_id: str | None = None
    service_account: str | None = None
    metadata: dict[str, Any] = field(default_factory=dict)

    @classmethod
    def from_dict(cls, data: Mapping[str, Any] | None) -> "CanonicalActor":
        value = ensure_dict(data)
        return cls(
            user=value.get("user") or value.get("username") or value.get("name"),
            user_id=value.get("user_id") or value.get("sid") or value.get("id"),
            domain=value.get("domain"),
            account_type=value.get("account_type"),
            privilege_level=value.get("privilege_level"),
            source_ip=value.get("source_ip") or value.get("src_ip"),
            source_mac=value.get("source_mac") or value.get("src_mac"),
            process=value.get("process"),
            process_id=value.get("process_id") or value.get("pid"),
            parent_process=value.get("parent_process"),
            api_key_id=value.get("api_key_id"),
            service_account=value.get("service_account"),
            metadata=ensure_dict(value.get("metadata")),
        )


@dataclass(slots=True)
class CanonicalEventSource(SerializableMixin):
    trigger_source: str = "unknown"
    vendor: str | None = None
    product: str | None = None
    collector: str | None = None
    sensor: str | None = None
    parser_profile: str | None = None
    normalizer_profile: str | None = None
    trust: str = "medium"
    freshness: dict[str, Any] = field(default_factory=dict)
    raw_ref: str | None = None
    metadata: dict[str, Any] = field(default_factory=dict)

    @classmethod
    def from_dict(cls, data: Mapping[str, Any] | None) -> "CanonicalEventSource":
        value = ensure_dict(data)
        return cls(
            trigger_source=str(value.get("trigger_source") or value.get("source") or "unknown"),
            vendor=value.get("vendor"),
            product=value.get("product"),
            collector=value.get("collector"),
            sensor=value.get("sensor"),
            parser_profile=value.get("parser_profile"),
            normalizer_profile=value.get("normalizer_profile"),
            trust=str(value.get("trust") or "medium"),
            freshness=ensure_dict(value.get("freshness")),
            raw_ref=value.get("raw_ref"),
            metadata=ensure_dict(value.get("metadata")),
        )


@dataclass(slots=True)
class CanonicalEventClassification(SerializableMixin):
    domain: str = "unknown"
    event_class: str = "unknown"  # security_alert, config_change, violation, vulnerability, telemetry_gap, availability, business_risk
    event_type: str = "unknown"
    semantic_type: str = "unknown"
    lifecycle: str = "reactive"
    severity: str = "medium"
    confidence: str = "medium"
    security_relevance: str = "unknown"
    tags: list[str] = field(default_factory=list)
    metadata: dict[str, Any] = field(default_factory=dict)

    @classmethod
    def from_dict(cls, data: Mapping[str, Any] | None) -> "CanonicalEventClassification":
        value = ensure_dict(data)
        return cls(
            domain=str(value.get("domain") or "unknown"),
            event_class=str(value.get("event_class") or value.get("category") or "unknown"),
            event_type=str(value.get("event_type") or value.get("type") or "unknown"),
            semantic_type=str(value.get("semantic_type") or "unknown"),
            lifecycle=str(value.get("lifecycle") or "reactive"),
            severity=str(value.get("severity") or "medium"),
            confidence=str(value.get("confidence") or "medium"),
            security_relevance=str(value.get("security_relevance") or "unknown"),
            tags=stringify_list(value.get("tags")),
            metadata=ensure_dict(value.get("metadata")),
        )


@dataclass(slots=True)
class CanonicalEventV2(SerializableMixin):
    """CARE event envelope used after ingest/event-normalize.

    The object is intentionally verbose enough to route arbitrary alerts, syslog
    messages, config drifts, telemetry-health events, violations, vulnerability
    events, and business-risk events without requiring a custom model per event
    type.
    """

    event_id: str = field(default_factory=lambda: new_id("EVT"))
    schema_version: str = "care.event/v2"
    timestamp: str = field(default_factory=utc_now)
    received_at: str = field(default_factory=utc_now)
    source: CanonicalEventSource = field(default_factory=CanonicalEventSource)
    classification: CanonicalEventClassification = field(default_factory=CanonicalEventClassification)
    asset: CanonicalAsset = field(default_factory=CanonicalAsset)
    actor: CanonicalActor = field(default_factory=CanonicalActor)
    object: CanonicalObject = field(default_factory=CanonicalObject)
    changes: list[CanonicalChange] = field(default_factory=list)
    threat_candidates: list[CanonicalThreatCandidate] = field(default_factory=list)
    control_candidates: list[CanonicalControlCandidate] = field(default_factory=list)
    assertion_candidates: list[str] = field(default_factory=list)
    detection_candidates: list[str] = field(default_factory=list)
    response_candidates: list[str] = field(default_factory=list)
    recovery_candidates: list[str] = field(default_factory=list)
    facts: dict[str, Any] = field(default_factory=dict)
    raw: dict[str, Any] = field(default_factory=dict)
    evidence_refs: list[str] = field(default_factory=list)
    warnings: list[str] = field(default_factory=list)
    errors: list[str] = field(default_factory=list)
    metadata: dict[str, Any] = field(default_factory=dict)

    @classmethod
    def from_dict(cls, data: Mapping[str, Any] | None) -> "CanonicalEventV2":
        value = ensure_dict(data)
        raw_classification = value.get("classification") or value.get("event") or {}
        return cls(
            event_id=str(value.get("event_id") or value.get("id") or new_id("EVT")),
            schema_version=str(value.get("schema_version") or "care.event/v2"),
            timestamp=str(value.get("timestamp") or value.get("time") or utc_now()),
            received_at=str(value.get("received_at") or utc_now()),
            source=CanonicalEventSource.from_dict(value.get("source")),
            classification=CanonicalEventClassification.from_dict(raw_classification),
            asset=CanonicalAsset.from_dict(value.get("asset")),
            actor=CanonicalActor.from_dict(value.get("actor")),
            object=CanonicalObject.from_dict(value.get("object") or value.get("config")),
            changes=[CanonicalChange.from_dict(item) for item in listify(value.get("changes") or value.get("change")) if item],
            threat_candidates=[CanonicalThreatCandidate.from_dict(item) for item in _nested_candidate_list(value, "threat_candidates", "threat.candidate_threats", "threats.candidate_threats", "threat.candidates")],
            control_candidates=[CanonicalControlCandidate.from_dict(item) for item in _nested_candidate_list(value, "control_candidates", "controls.candidate_controls", "control.candidate_controls", "controls.candidates")],
            assertion_candidates=stringify_list(_nested_candidate_list(value, "assertion_candidates", "assertions.candidate_assertions", "assertion.candidate_assertions") or (value.get("assertions") if isinstance(value.get("assertions"), list) else [])),
            detection_candidates=stringify_list(_nested_candidate_list(value, "detection_candidates", "detections.candidate_detections", "detection.candidate_detections") or (value.get("detections") if isinstance(value.get("detections"), list) else [])),
            response_candidates=stringify_list(_nested_candidate_list(value, "response_candidates", "response.candidate_profiles", "responses.candidate_profiles", "response.candidate_responses") or (value.get("responses") if isinstance(value.get("responses"), list) else [])),
            recovery_candidates=stringify_list(_nested_candidate_list(value, "recovery_candidates", "recovery.candidate_profiles", "recoveries.candidate_profiles", "recovery.candidate_recoveries") or (value.get("recoveries") if isinstance(value.get("recoveries"), list) else [])),
            facts=ensure_dict(value.get("facts")),
            raw=ensure_dict(value.get("raw")),
            evidence_refs=stringify_list(value.get("evidence_refs")),
            warnings=stringify_list(value.get("warnings")),
            errors=stringify_list(value.get("errors")),
            metadata=ensure_dict(value.get("metadata")),
        )

    @property
    def event_class(self) -> str:
        return self.classification.event_class

    @property
    def event_type(self) -> str:
        return self.classification.event_type

    @property
    def severity(self) -> str:
        return self.classification.severity

    @property
    def confidence(self) -> str:
        return self.classification.confidence

    def changed_paths(self) -> list[str]:
        paths: list[str] = []
        if self.object.canonical_path:
            paths.append(self.object.canonical_path)
        for change in self.changes:
            if change.canonical_path:
                paths.append(change.canonical_path)
        return sorted(set(paths))

    def routing_fingerprint(self) -> str:
        return stable_hash(
            {
                "source": self.source.trigger_source,
                "domain": self.classification.domain,
                "event_class": self.classification.event_class,
                "event_type": self.classification.event_type,
                "asset": self.asset.id,
                "platform": self.asset.platform,
                "object": self.object.routing_key(),
                "paths": self.changed_paths(),
            }
        )

    def to_runtime_event(self) -> dict[str, Any]:
        """Return a dict shaped for existing reactive rule/profile contexts."""
        data = self.to_dict()
        data.setdefault("id", self.event_id)
        data.setdefault("category", self.classification.event_class)
        data.setdefault("type", self.classification.event_type)
        data.setdefault("severity", self.classification.severity)
        data.setdefault("confidence", self.classification.confidence)
        data.setdefault("trigger_source", self.source.trigger_source)
        return data


__all__ = [
    "CanonicalActor",
    "CanonicalAsset",
    "CanonicalEventClassification",
    "CanonicalEventSource",
    "CanonicalEventV2",
]

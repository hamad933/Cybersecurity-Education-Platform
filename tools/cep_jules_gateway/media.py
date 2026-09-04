from __future__ import annotations

import base64
import binascii
import hashlib
import re
from pathlib import Path
from typing import Any

from .models import ProviderOutcome
from .selection import deterministic_activity_order, validate_activity_identity

DEFAULT_MAX_MEDIA_ITEM_BYTES = 5 * 1024 * 1024
DEFAULT_MAX_MEDIA_TOTAL_BYTES = 20 * 1024 * 1024
DEFAULT_MAX_MEDIA_ITEMS = 50
_MIME_RE = re.compile(r"[A-Za-z0-9][A-Za-z0-9!#$&^_.+-]{0,126}/[A-Za-z0-9][A-Za-z0-9!#$&^_.+-]{0,126}\Z")


def _error(activity_name: str, artifact_index: int, reason: str, **details: Any) -> dict[str, Any]:
    return {
        "activity_name": activity_name,
        "artifact_index": artifact_index,
        "reason": reason,
        **details,
    }


def collect_media_evidence(
    activities: list[dict[str, Any]],
    session_id: str,
    *,
    output_dir: Path | None = None,
    max_item_bytes: int = DEFAULT_MAX_MEDIA_ITEM_BYTES,
    max_total_bytes: int = DEFAULT_MAX_MEDIA_TOTAL_BYTES,
    max_items: int = DEFAULT_MAX_MEDIA_ITEMS,
) -> dict[str, Any]:
    if max_item_bytes < 1 or max_total_bytes < 1 or max_items < 1:
        raise ValueError("media evidence bounds must be positive")

    if output_dir is not None:
        output_dir.mkdir(parents=True, exist_ok=True)

    rows: list[dict[str, Any]] = []
    errors: list[dict[str, Any]] = []
    total_bytes = 0
    media_seen = 0

    for activity in deterministic_activity_order(activities, session_id):
        activity_name = validate_activity_identity(activity, session_id)
        create_time = activity.get("createTime")
        artifacts = activity.get("artifacts") or []
        if not isinstance(artifacts, list):
            errors.append(_error(activity_name, 0, "ARTIFACTS_FIELD_NOT_ARRAY"))
            continue
        for artifact_index, artifact in enumerate(artifacts, start=1):
            if not isinstance(artifact, dict) or "media" not in artifact:
                continue
            media_seen += 1
            if media_seen > max_items:
                errors.append(_error(activity_name, artifact_index, "MEDIA_ITEM_LIMIT_EXCEEDED", max_items=max_items))
                continue
            media = artifact.get("media")
            if not isinstance(media, dict):
                errors.append(_error(activity_name, artifact_index, "MEDIA_NOT_OBJECT"))
                continue
            mime_type = media.get("mimeType")
            encoded = media.get("data")
            if not isinstance(mime_type, str) or not _MIME_RE.fullmatch(mime_type):
                errors.append(_error(activity_name, artifact_index, "MEDIA_MIME_TYPE_INVALID"))
                continue
            if not isinstance(encoded, str) or not encoded:
                errors.append(_error(activity_name, artifact_index, "MEDIA_BASE64_DATA_MISSING"))
                continue

            # Reject obviously oversized base64 before allocating decoded bytes.
            max_encoded_chars = ((max_item_bytes + 2) // 3) * 4 + 4
            if len(encoded) > max_encoded_chars:
                errors.append(
                    _error(
                        activity_name,
                        artifact_index,
                        "MEDIA_ITEM_BOUND_EXCEEDED",
                        max_item_bytes=max_item_bytes,
                    )
                )
                continue
            try:
                decoded = base64.b64decode(encoded, validate=True)
            except (binascii.Error, ValueError):
                errors.append(_error(activity_name, artifact_index, "MEDIA_BASE64_INVALID"))
                continue
            size = len(decoded)
            if size > max_item_bytes:
                errors.append(
                    _error(
                        activity_name,
                        artifact_index,
                        "MEDIA_ITEM_BOUND_EXCEEDED",
                        decoded_bytes=size,
                        max_item_bytes=max_item_bytes,
                    )
                )
                continue
            if total_bytes + size > max_total_bytes:
                errors.append(
                    _error(
                        activity_name,
                        artifact_index,
                        "MEDIA_TOTAL_BOUND_EXCEEDED",
                        decoded_bytes=size,
                        total_bytes_before=total_bytes,
                        max_total_bytes=max_total_bytes,
                    )
                )
                continue

            digest = hashlib.sha256(decoded).hexdigest()
            row: dict[str, Any] = {
                "activity_name": activity_name,
                "create_time": create_time,
                "artifact_index": artifact_index,
                "mime_type": mime_type,
                "decoded_bytes": size,
                "sha256": digest,
            }
            if output_dir is not None:
                filename = f"media-{len(rows) + 1:04d}-{digest[:16]}.bin"
                path = output_dir / filename
                path.write_bytes(decoded)
                row["output_file"] = f"media/{filename}"
            rows.append(row)
            total_bytes += size

    if not rows and not errors:
        status = ProviderOutcome.NOT_FOUND.value
    elif errors:
        status = "PARTIAL"
    else:
        status = ProviderOutcome.FOUND.value
    return {
        "status": status,
        "count": len(rows),
        "total_decoded_bytes": total_bytes,
        "max_item_bytes": max_item_bytes,
        "max_total_bytes": max_total_bytes,
        "max_items": max_items,
        "index": rows,
        "errors": errors,
    }

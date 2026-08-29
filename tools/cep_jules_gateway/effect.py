from __future__ import annotations

from .digest import sha256_json, sha256_text
from .envelope import RequestEnvelope
from .models import ErrorClassification, GatewayError


def request_concurrency_key(envelope: RequestEnvelope) -> str:
    return "req-" + sha256_text(envelope.request_id)[:32]


def effect_concurrency_key(envelope: RequestEnvelope) -> str:
    if not envelope.is_mutation:
        return "read-" + sha256_text(envelope.request_id)[:32]
    if envelope.session_id:
        material = {"effect_type": "session", "session_id": envelope.session_id}
    else:
        if not envelope.logical_task_id or not envelope.write_domain or not envelope.starting_branch:
            raise GatewayError(
                ErrorClassification.INVALID_REQUEST,
                "pre-session mutation cannot derive a deterministic effect identity",
            )
        material = {
            "effect_type": "pre_session_write_domain",
            "logical_task_id": envelope.logical_task_id,
            "write_domain": envelope.write_domain,
            "starting_branch": envelope.starting_branch,
        }
    return "effect-" + sha256_json(material)[:32]


def intent_identity(envelope: RequestEnvelope) -> str:
    private_digests = {
        "title_digest": sha256_text(envelope.title) if envelope.title is not None else None,
        "prompt_digest": sha256_text(envelope.prompt) if envelope.prompt is not None else None,
    }
    return sha256_json(
        {
            "request": envelope.public_dict(),
            "private_digests": private_digests,
            "effect_key": effect_concurrency_key(envelope),
        }
    )

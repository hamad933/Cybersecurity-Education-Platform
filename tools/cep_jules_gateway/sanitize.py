from __future__ import annotations

import re
from typing import Any

_REDACTED = "[REDACTED]"

# Ordered from high-confidence provider-specific patterns to generic assignments.
_PATTERNS: tuple[tuple[re.Pattern[str], str], ...] = (
    (
        re.compile(r"(?i)(\bJULES_API_KEY\b\s*[=:]\s*)(?:['\"])?[^\s'\";,}]+"),
        rf"\1{_REDACTED}",
    ),
    (
        re.compile(r"(?i)(\bGDRIVE_SA_JSON_B64\b\s*[=:]\s*)(?:['\"])?[^\s'\";,}]+"),
        rf"\1{_REDACTED}",
    ),
    (
        re.compile(r"(?i)(\bAuthorization\s*:\s*Bearer\s+)[A-Za-z0-9._~+\-/]+=*"),
        rf"\1{_REDACTED}",
    ),
    (
        re.compile(r"(?i)(\bx-goog-api-key\b\s*[=:]\s*)(?:['\"])?[^\s'\";,}]+"),
        rf"\1{_REDACTED}",
    ),
    (
        re.compile(
            r"(?i)(\b(?:api[_-]?key|access[_-]?token|refresh[_-]?token|client[_-]?secret|password)\b"
            r"\s*[=:]\s*)(?:['\"])([^'\"\r\n]{4,})(?:['\"])"
        ),
        rf"\1{_REDACTED}",
    ),
    (
        re.compile(r"\bgh[pousr]_[A-Za-z0-9]{20,}\b"),
        _REDACTED,
    ),
    (
        re.compile(r"\bAIza[0-9A-Za-z_-]{20,}\b"),
        _REDACTED,
    ),
    (
        re.compile(r"\beyJ[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\b"),
        _REDACTED,
    ),
    (
        re.compile(
            r"(?is)-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----.*?"
            r"-----END (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"
        ),
        _REDACTED,
    ),
)

_SENSITIVE_KEYS = {
    "jules_api_key",
    "gdrive_sa_json_b64",
    "authorization",
    "x-goog-api-key",
    "api_key",
    "apikey",
    "access_token",
    "refresh_token",
    "client_secret",
    "password",
    "private_key",
}


def sanitize_text(value: Any) -> str:
    text = str(value or "")
    for pattern, replacement in _PATTERNS:
        text = pattern.sub(replacement, text)
    return text


def sanitize_obj(value: Any) -> Any:
    if isinstance(value, dict):
        cleaned: dict[str, Any] = {}
        for key, item in value.items():
            key_text = str(key)
            if key_text.lower() in _SENSITIVE_KEYS:
                cleaned[key_text] = _REDACTED
            else:
                cleaned[key_text] = sanitize_obj(item)
        return cleaned
    if isinstance(value, list):
        return [sanitize_obj(item) for item in value]
    if isinstance(value, tuple):
        return [sanitize_obj(item) for item in value]
    if isinstance(value, str):
        return sanitize_text(value)
    return value

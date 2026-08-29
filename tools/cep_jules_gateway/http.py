from __future__ import annotations

import json
import urllib.error
import urllib.request
from dataclasses import dataclass
from typing import Any, Mapping, Protocol

from .models import ErrorClassification, SafeRetryMetadata


@dataclass(frozen=True)
class HttpResponse:
    status: int
    payload: dict[str, Any]
    headers: dict[str, str]


class JsonTransport(Protocol):
    def request_json(
        self,
        method: str,
        url: str,
        *,
        headers: Mapping[str, str],
        timeout: float,
    ) -> HttpResponse: ...


class UrllibJsonTransport:
    def __init__(self, *, max_response_bytes: int = 8 * 1024 * 1024):
        self.max_response_bytes = max_response_bytes

    def request_json(
        self,
        method: str,
        url: str,
        *,
        headers: Mapping[str, str],
        timeout: float,
    ) -> HttpResponse:
        request = urllib.request.Request(url, method=method)
        for name, value in headers.items():
            request.add_header(name, value)
        try:
            with urllib.request.urlopen(request, timeout=timeout) as response:
                raw = response.read(self.max_response_bytes + 1)
                if len(raw) > self.max_response_bytes:
                    return HttpResponse(502, {"error": "PROVIDER_RESPONSE_TOO_LARGE"}, _safe_headers(response.headers))
                return HttpResponse(response.status, _decode_payload(raw), _safe_headers(response.headers))
        except urllib.error.HTTPError as exc:
            raw = exc.read(self.max_response_bytes + 1)
            if len(raw) > self.max_response_bytes:
                payload = {"error": "PROVIDER_ERROR_RESPONSE_TOO_LARGE"}
            else:
                payload = _decode_payload(raw)
            return HttpResponse(exc.code, payload, _safe_headers(exc.headers))
        except (urllib.error.URLError, TimeoutError) as exc:
            return HttpResponse(599, {"transport_error": type(exc).__name__}, {})


def _decode_payload(raw: bytes) -> dict[str, Any]:
    if not raw:
        return {}
    try:
        value = json.loads(raw.decode("utf-8"))
    except (UnicodeDecodeError, json.JSONDecodeError):
        return {"error": "NON_JSON_PROVIDER_RESPONSE"}
    return value if isinstance(value, dict) else {"value": value}


def _safe_headers(headers: Mapping[str, str] | None) -> dict[str, str]:
    if headers is None:
        return {}
    allowed = {
        "retry-after",
        "x-ratelimit-limit",
        "x-ratelimit-remaining",
        "x-ratelimit-reset",
        "x-goog-request-id",
        "date",
    }
    return {str(k).lower(): str(v) for k, v in headers.items() if str(k).lower() in allowed}


def classify_response(response: HttpResponse) -> ErrorClassification | None:
    status = response.status
    remaining = {k.lower(): v for k, v in response.headers.items()}.get("x-ratelimit-remaining")
    if 200 <= status < 300:
        return None
    if status == 429 or (status == 403 and remaining == "0"):
        return ErrorClassification.RATE_LIMITED
    if status in {401, 403}:
        return ErrorClassification.AUTH_FAILED
    if status == 404:
        return ErrorClassification.NOT_FOUND
    if status in {409, 412, 422}:
        return ErrorClassification.INVALID_STATE
    return ErrorClassification.PROVIDER_READ_FAILED


def retry_metadata(response: HttpResponse) -> SafeRetryMetadata | None:
    headers = {k.lower(): v for k, v in response.headers.items()}
    retry_after = headers.get("retry-after")
    parsed_retry: int | None = None
    if retry_after and retry_after.isdigit():
        parsed_retry = min(int(retry_after), 86_400)
    values = SafeRetryMetadata(
        retry_after_seconds=parsed_retry,
        rate_limit_limit=headers.get("x-ratelimit-limit"),
        rate_limit_remaining=headers.get("x-ratelimit-remaining"),
        rate_limit_reset=headers.get("x-ratelimit-reset"),
    )
    if all(value is None for value in values.__dict__.values()):
        return None
    return values

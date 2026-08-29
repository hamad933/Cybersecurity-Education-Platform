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
    payload: Any
    headers: dict[str, str]
    protocol_error: str | None = None


class JsonTransport(Protocol):
    def request_json(
        self,
        method: str,
        url: str,
        *,
        headers: Mapping[str, str],
        timeout: float,
        body: Any | None = None,
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
        body: Any | None = None,
    ) -> HttpResponse:
        data = None if body is None else json.dumps(body, ensure_ascii=False, separators=(",", ":"), allow_nan=False).encode("utf-8")
        request = urllib.request.Request(url, data=data, method=method)
        for name, value in headers.items():
            request.add_header(name, value)
        if data is not None:
            request.add_header("Content-Type", "application/json")
        try:
            with urllib.request.urlopen(request, timeout=timeout) as response:
                raw = response.read(self.max_response_bytes + 1)
                safe_headers = _safe_headers(response.headers)
                if len(raw) > self.max_response_bytes:
                    return HttpResponse(response.status, None, safe_headers, "PROVIDER_RESPONSE_TOO_LARGE")
                payload, protocol_error = _decode_payload(raw)
                return HttpResponse(response.status, payload, safe_headers, protocol_error)
        except urllib.error.HTTPError as exc:
            raw = exc.read(self.max_response_bytes + 1)
            safe_headers = _safe_headers(exc.headers)
            if len(raw) > self.max_response_bytes:
                return HttpResponse(exc.code, None, safe_headers, "PROVIDER_ERROR_RESPONSE_TOO_LARGE")
            payload, protocol_error = _decode_payload(raw)
            return HttpResponse(exc.code, payload, safe_headers, protocol_error)
        except (urllib.error.URLError, TimeoutError, ConnectionError) as exc:
            return HttpResponse(599, {"transport_error": type(exc).__name__}, {}, "TRANSPORT_OUTCOME_AMBIGUOUS")


def _decode_payload(raw: bytes) -> tuple[Any, str | None]:
    if not raw:
        return {}, None
    try:
        return json.loads(raw.decode("utf-8")), None
    except (UnicodeDecodeError, json.JSONDecodeError):
        return None, "NON_JSON_PROVIDER_RESPONSE"


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
        if response.protocol_error:
            return ErrorClassification.PROVIDER_PROTOCOL_FAILED
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

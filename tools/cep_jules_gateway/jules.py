from __future__ import annotations

import urllib.parse
from dataclasses import replace
from typing import Any

from .http import JsonTransport, UrllibJsonTransport, classify_response, retry_metadata
from .models import ErrorClassification, GatewayError, ProviderObservation
from .pagination import Page, PaginationResult, paginate

DEFAULT_API_BASE = "https://jules.googleapis.com/v1alpha"
_MAX_PROVIDER_PAGE_SIZE = 100
_MIN_ADAPTIVE_ACTIVITY_PAGE_SIZE = 1
_MAX_ACTIVITY_SIZE_FALLBACKS = 5


class JulesClient:
    def __init__(
        self,
        api_key: str,
        *,
        api_base: str = DEFAULT_API_BASE,
        transport: JsonTransport | None = None,
        timeout_seconds: float = 45.0,
        max_provider_reads: int = 10_000,
    ):
        if not api_key:
            raise GatewayError(ErrorClassification.AUTH_FAILED, "JULES_API_KEY is unavailable to the workflow runtime")
        self._api_key = api_key
        self.api_base = api_base.rstrip("/")
        self.transport = transport or UrllibJsonTransport()
        self.timeout_seconds = min(max(float(timeout_seconds), 1.0), 60.0)
        self.max_provider_reads = max(1, int(max_provider_reads))
        self.provider_reads = 0
        self.hydration_cache_hits = 0
        self._activity_cache: dict[str, dict[str, Any]] = {}
        self.observations: list[ProviderObservation] = []

    def _consume_read(self, operation: str) -> None:
        if self.provider_reads >= self.max_provider_reads:
            raise GatewayError(
                ErrorClassification.READ_BUDGET_EXCEEDED,
                "global Jules provider-read budget exhausted",
                details={
                    "operation": operation,
                    "provider_reads": self.provider_reads,
                    "max_provider_reads": self.max_provider_reads,
                },
            )
        self.provider_reads += 1

    def _observe(self, operation: str, response: Any, classification: ErrorClassification | None) -> None:
        self.observations.append(
            ProviderObservation(
                operation=operation,
                http_status=response.status,
                classification=classification.value if classification else None,
                retry=retry_metadata(response),
            )
        )

    def _get(self, operation: str, path: str) -> dict[str, Any]:
        self._consume_read(operation)
        response = self.transport.request_json(
            "GET",
            self.api_base + path,
            headers={"x-goog-api-key": self._api_key, "Accept": "application/json"},
            timeout=self.timeout_seconds,
        )
        classification = classify_response(response)
        retry = retry_metadata(response)
        self._observe(operation, response, classification)
        if classification is not None:
            raise GatewayError(
                classification,
                f"Jules provider read failed during {operation}",
                http_status=response.status,
                retry=retry,
                details={"operation": operation, "protocol_error": response.protocol_error},
            )
        if not isinstance(response.payload, dict):
            raise GatewayError(
                ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                f"Jules provider returned an unexpected top-level type during {operation}",
                http_status=response.status,
                details={"operation": operation, "expected_type": "object"},
            )
        return response.payload

    def _post(self, operation: str, path: str, body: dict[str, Any]) -> dict[str, Any] | None:
        response = self.transport.request_json(
            "POST",
            self.api_base + path,
            headers={"x-goog-api-key": self._api_key, "Accept": "application/json"},
            timeout=self.timeout_seconds,
            body=body,
        )
        classification = classify_response(response)
        retry = retry_metadata(response)
        self._observe(operation, response, classification)

        if 200 <= response.status < 300 and response.protocol_error is None:
            if response.payload in ({}, None):
                return None
            if not isinstance(response.payload, dict):
                raise GatewayError(
                    ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN,
                    "Jules mutation returned an unexpected successful response shape; outcome is unknown",
                    http_status=response.status,
                    details={"operation": operation, "blind_retry": False},
                )
            return response.payload

        ambiguous = response.status == 599 or response.status == 429 or response.status >= 500 or (
            200 <= response.status < 300 and response.protocol_error is not None
        )
        if ambiguous:
            raise GatewayError(
                ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN,
                "Jules mutation outcome is ambiguous; blind retry is prohibited",
                http_status=response.status,
                retry=retry,
                details={
                    "operation": operation,
                    "provider_classification": classification.value if classification else None,
                    "protocol_error": response.protocol_error,
                    "blind_retry": False,
                },
            )
        raise GatewayError(
            classification or ErrorClassification.INVALID_STATE,
            f"Jules provider rejected mutation during {operation}",
            http_status=response.status,
            retry=retry,
            details={"operation": operation, "blind_retry": False},
        )

    @staticmethod
    def _valid_session_id(value: Any) -> str:
        text = str(value or "")
        if not text.isdigit() or len(text) > 32:
            raise GatewayError(ErrorClassification.PROVIDER_PROTOCOL_FAILED, "provider session response is missing a valid id")
        return text

    @staticmethod
    def _activity_name_for_session(name: Any, session_id: str) -> str:
        text = str(name or "")
        prefix = f"sessions/{session_id}/activities/"
        suffix = text[len(prefix):] if text.startswith(prefix) else ""
        if not suffix or "/" in suffix or len(suffix) > 160:
            raise GatewayError(
                ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                "provider returned a malformed or cross-session activity identity",
                details={"expected_session_id": session_id},
            )
        return text

    @staticmethod
    def _next_token(payload: dict[str, Any]) -> str | None:
        value = payload.get("nextPageToken")
        if value is None or value == "":
            return None
        if not isinstance(value, str) or len(value) > 4_096:
            raise GatewayError(ErrorClassification.PROVIDER_PROTOCOL_FAILED, "provider returned an invalid nextPageToken")
        return value

    @staticmethod
    def _bounded_page_size(value: int) -> int:
        if isinstance(value, bool):
            raise ValueError("page_size must be an integer between 1 and 100")
        try:
            parsed = int(value)
        except (TypeError, ValueError) as exc:
            raise ValueError("page_size must be an integer between 1 and 100") from exc
        if parsed < 1 or parsed > _MAX_PROVIDER_PAGE_SIZE:
            raise ValueError("page_size must be an integer between 1 and 100")
        return parsed

    @staticmethod
    def _is_safe_response_size_failure(exc: GatewayError) -> bool:
        return (
            exc.classification == ErrorClassification.PROVIDER_PROTOCOL_FAILED
            and exc.details.get("protocol_error") == "PROVIDER_RESPONSE_TOO_LARGE"
        )

    def get_session(self, session_id: str) -> dict[str, Any]:
        payload = self._get("get_session", f"/sessions/{urllib.parse.quote(session_id, safe='')}")
        actual = self._valid_session_id(payload.get("id"))
        if actual != session_id:
            raise GatewayError(
                ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                "provider session identity does not match the requested session",
                details={"expected_session_id": session_id, "actual_session_id": actual},
            )
        return payload

    def get_activity(self, activity_name: str) -> dict[str, Any]:
        parts = activity_name.split("/")
        if len(parts) != 4 or parts[0] != "sessions" or parts[2] != "activities" or not parts[1].isdigit() or not parts[3]:
            raise GatewayError(ErrorClassification.INVALID_STATE, "provider returned an invalid activity identity")
        session_id = parts[1]
        if activity_name in self._activity_cache:
            self.hydration_cache_hits += 1
            return self._activity_cache[activity_name]
        safe_path = "/" + "/".join(urllib.parse.quote(part, safe="") for part in parts)
        payload = self._get("get_activity", safe_path)
        actual = self._activity_name_for_session(payload.get("name"), session_id)
        if actual != activity_name:
            raise GatewayError(
                ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                "hydrated activity identity differs from requested activity",
                details={"requested_activity": activity_name},
            )
        self._activity_cache[activity_name] = payload
        return payload

    def list_activities(
        self,
        session_id: str,
        *,
        page_size: int,
        max_pages: int,
        max_items: int = 2_000,
        start_page_token: str | None = None,
    ) -> PaginationResult:
        sid = urllib.parse.quote(session_id, safe="")
        requested_page_size = self._bounded_page_size(page_size)
        active_page_size = requested_page_size

        def fetch(token: str | None) -> Page:
            nonlocal active_page_size
            size_fallbacks = 0
            while True:
                query: dict[str, str | int] = {"pageSize": active_page_size}
                if token:
                    query["pageToken"] = token
                try:
                    payload = self._get(
                        "list_activities",
                        f"/sessions/{sid}/activities?{urllib.parse.urlencode(query)}",
                    )
                    break
                except GatewayError as exc:
                    if not self._is_safe_response_size_failure(exc):
                        raise
                    if active_page_size <= _MIN_ADAPTIVE_ACTIVITY_PAGE_SIZE or size_fallbacks >= _MAX_ACTIVITY_SIZE_FALLBACKS:
                        raise
                    next_size = max(_MIN_ADAPTIVE_ACTIVITY_PAGE_SIZE, active_page_size // 2)
                    if next_size == active_page_size:
                        raise
                    active_page_size = next_size
                    size_fallbacks += 1

            next_token = self._next_token(payload)
            if "activities" not in payload:
                if next_token is None:
                    # Google list methods can surface a successful empty object at a
                    # terminal boundary. Treat only an unambiguous no-continuation
                    # response as the empty terminal page; never suppress evidence
                    # when the provider says another page exists.
                    return Page([], None)
                raise GatewayError(
                    ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                    "Jules activity collection omitted activities while advertising continuation",
                    details={"collection": "activities", "has_continuation": True},
                )
            raw_items = payload["activities"]
            if not isinstance(raw_items, list):
                raise GatewayError(
                    ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                    "Jules activity collection activities field is not an array",
                    details={"collection": "activities", "has_continuation": next_token is not None},
                )
            items: list[dict[str, Any]] = []
            for item in raw_items:
                if not isinstance(item, dict):
                    raise GatewayError(
                        ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                        "Jules activity collection contains a non-object item",
                    )
                self._activity_name_for_session(item.get("name"), session_id)
                items.append(item)
            return Page(items, next_token)

        result = paginate(
            fetch,
            max_pages=max_pages,
            max_items=max_items,
            start_page_token=start_page_token,
        )
        return PaginationResult(
            result.items,
            replace(
                result.info,
                requested_page_size=requested_page_size,
                effective_page_size=active_page_size,
            ),
        )

    def list_sessions(
        self,
        *,
        page_size: int = 100,
        max_pages: int = 20,
        max_items: int = 2_000,
        start_page_token: str | None = None,
    ) -> PaginationResult:
        bounded_page_size = self._bounded_page_size(page_size)

        def fetch(token: str | None) -> Page:
            query: dict[str, str | int] = {"pageSize": bounded_page_size}
            if token:
                query["pageToken"] = token
            payload = self._get("list_sessions", f"/sessions?{urllib.parse.urlencode(query)}")
            next_token = self._next_token(payload)
            if "sessions" not in payload:
                raise GatewayError(
                    ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                    "Jules session collection response omitted the mandatory sessions array",
                    details={"collection": "sessions", "has_continuation": next_token is not None},
                )
            raw_items = payload["sessions"]
            if not isinstance(raw_items, list):
                raise GatewayError(
                    ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                    "Jules session collection sessions field is not an array",
                )
            items: list[dict[str, Any]] = []
            for item in raw_items:
                if not isinstance(item, dict):
                    raise GatewayError(ErrorClassification.PROVIDER_PROTOCOL_FAILED, "Jules session collection contains a non-object item")
                self._valid_session_id(item.get("id"))
                items.append(item)
            return Page(items, next_token)

        result = paginate(fetch, max_pages=max_pages, max_items=max_items, start_page_token=start_page_token)
        return PaginationResult(
            result.items,
            replace(
                result.info,
                requested_page_size=bounded_page_size,
                effective_page_size=bounded_page_size,
            ),
        )

    def list_sources(self) -> list[dict[str, Any]]:
        payload = self._get("list_sources", "/sources")
        sources = payload.get("sources")
        if not isinstance(sources, list) or any(not isinstance(item, dict) for item in sources):
            raise GatewayError(ErrorClassification.PROVIDER_PROTOCOL_FAILED, "Jules source collection response is structurally invalid")
        return list(sources)

    def create_session(self, body: dict[str, Any]) -> dict[str, Any]:
        payload = self._post("create_session", "/sessions", body)
        if not isinstance(payload, dict):
            raise GatewayError(
                ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN,
                "create_session returned no reconstructable session identity; outcome is unknown",
                details={"blind_retry": False},
            )
        self._valid_session_id(payload.get("id"))
        return payload

    def send_message(self, session_id: str, prompt: str) -> None:
        self._post("send_message", f"/sessions/{urllib.parse.quote(session_id, safe='')}:sendMessage", {"prompt": prompt})

    def approve_plan(self, session_id: str) -> None:
        self._post("approve_plan", f"/sessions/{urllib.parse.quote(session_id, safe='')}:approvePlan", {})

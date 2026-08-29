from __future__ import annotations

import urllib.parse
from typing import Any

from .http import JsonTransport, UrllibJsonTransport, classify_response, retry_metadata
from .models import (
    ErrorClassification,
    GatewayError,
    ProviderObservation,
)
from .pagination import Page, PaginationResult, paginate

DEFAULT_API_BASE = "https://jules.googleapis.com/v1alpha"


class JulesClient:
    def __init__(
        self,
        api_key: str,
        *,
        api_base: str = DEFAULT_API_BASE,
        transport: JsonTransport | None = None,
        timeout_seconds: float = 45.0,
    ):
        if not api_key:
            raise GatewayError(ErrorClassification.AUTH_FAILED, "JULES_API_KEY is unavailable to the workflow runtime")
        self._api_key = api_key
        self.api_base = api_base.rstrip("/")
        self.transport = transport or UrllibJsonTransport()
        self.timeout_seconds = min(max(float(timeout_seconds), 1.0), 60.0)
        self.observations: list[ProviderObservation] = []

    def _get(self, operation: str, path: str) -> dict[str, Any]:
        response = self.transport.request_json(
            "GET",
            self.api_base + path,
            headers={"x-goog-api-key": self._api_key, "Accept": "application/json"},
            timeout=self.timeout_seconds,
        )
        classification = classify_response(response)
        retry = retry_metadata(response)
        self.observations.append(
            ProviderObservation(
                operation=operation,
                http_status=response.status,
                classification=classification.value if classification else None,
                retry=retry,
            )
        )
        if 200 <= response.status < 300:
            return response.payload
        raise GatewayError(
            classification or ErrorClassification.PROVIDER_READ_FAILED,
            f"Jules provider read failed during {operation}",
            http_status=response.status,
            retry=retry,
            details={"operation": operation},
        )

    def get_session(self, session_id: str) -> dict[str, Any]:
        return self._get("get_session", f"/sessions/{urllib.parse.quote(session_id, safe='')}")

    def get_activity(self, activity_name: str) -> dict[str, Any]:
        prefix = "sessions/"
        if not activity_name.startswith(prefix) or "/activities/" not in activity_name:
            raise GatewayError(ErrorClassification.INVALID_STATE, "provider returned an invalid activity identity")
        safe_path = "/" + "/".join(urllib.parse.quote(part, safe="") for part in activity_name.split("/"))
        return self._get("get_activity", safe_path)

    def list_activities(self, session_id: str, *, page_size: int, max_pages: int) -> PaginationResult:
        sid = urllib.parse.quote(session_id, safe="")

        def fetch(token: str | None) -> Page:
            query: dict[str, str | int] = {"pageSize": page_size}
            if token:
                query["pageToken"] = token
            payload = self._get(
                "list_activities",
                f"/sessions/{sid}/activities?{urllib.parse.urlencode(query)}",
            )
            items = [item for item in payload.get("activities", []) if isinstance(item, dict)]
            return Page(items, str(payload.get("nextPageToken") or "") or None)

        return paginate(fetch, max_pages=max_pages)

    def list_sessions(self, *, page_size: int = 100, max_pages: int = 20) -> PaginationResult:
        def fetch(token: str | None) -> Page:
            query: dict[str, str | int] = {"pageSize": page_size}
            if token:
                query["pageToken"] = token
            payload = self._get("list_sessions", f"/sessions?{urllib.parse.urlencode(query)}")
            items = [item for item in payload.get("sessions", []) if isinstance(item, dict)]
            return Page(items, str(payload.get("nextPageToken") or "") or None)

        return paginate(fetch, max_pages=max_pages)


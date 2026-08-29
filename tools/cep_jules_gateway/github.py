from __future__ import annotations

import urllib.parse
from typing import Any

from .http import JsonTransport, UrllibJsonTransport, classify_response, retry_metadata
from .models import ErrorClassification, GatewayError, ProviderObservation

DEFAULT_API_BASE = "https://api.github.com"


class GitHubClient:
    """Minimal read-only GitHub client for CEP v2 preconditions and durable marker readback."""

    def __init__(
        self,
        token: str,
        repository: str,
        *,
        api_base: str = DEFAULT_API_BASE,
        transport: JsonTransport | None = None,
        timeout_seconds: float = 30.0,
    ):
        if not token:
            raise GatewayError(ErrorClassification.AUTH_FAILED, "GITHUB_TOKEN is unavailable to the workflow runtime")
        if "/" not in repository or len(repository) > 200:
            raise GatewayError(ErrorClassification.INVALID_REQUEST, "CEP_REPOSITORY is invalid")
        self._token = token
        self.repository = repository
        self.api_base = api_base.rstrip("/")
        self.transport = transport or UrllibJsonTransport()
        self.timeout_seconds = min(max(float(timeout_seconds), 1.0), 60.0)
        self.observations: list[ProviderObservation] = []

    def _get(self, operation: str, path: str) -> dict[str, Any]:
        response = self.transport.request_json(
            "GET",
            self.api_base + path,
            headers={
                "Authorization": f"Bearer {self._token}",
                "Accept": "application/vnd.github+json",
                "X-GitHub-Api-Version": "2022-11-28",
            },
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
        if classification is not None:
            raise GatewayError(
                classification,
                f"GitHub provider read failed during {operation}",
                http_status=response.status,
                retry=retry,
                details={"operation": operation, "protocol_error": response.protocol_error},
            )
        if not isinstance(response.payload, dict):
            raise GatewayError(
                ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                f"GitHub returned an unexpected top-level type during {operation}",
                http_status=response.status,
                details={"operation": operation},
            )
        return response.payload

    def get_branch_head(self, branch: str) -> str:
        owner, repo = self.repository.split("/", 1)
        payload = self._get(
            "github_get_branch_head",
            "/repos/"
            + urllib.parse.quote(owner, safe="")
            + "/"
            + urllib.parse.quote(repo, safe="")
            + "/branches/"
            + urllib.parse.quote(branch, safe=""),
        )
        commit = payload.get("commit")
        actual = str(commit.get("sha") or "").lower() if isinstance(commit, dict) else ""
        if len(actual) != 40 or any(ch not in "0123456789abcdef" for ch in actual):
            raise GatewayError(ErrorClassification.PROVIDER_PROTOCOL_FAILED, "GitHub branch response did not contain a valid commit SHA")
        return actual

    def require_branch_head(self, branch: str, expected_sha: str) -> dict[str, Any]:
        actual_sha = self.get_branch_head(branch)
        if actual_sha != expected_sha.lower():
            raise GatewayError(
                ErrorClassification.INVALID_STATE,
                "GitHub branch head does not match expected_sha",
                details={"branch": branch, "expected_sha": expected_sha.lower(), "actual_sha": actual_sha},
            )
        return {
            "status": "MATCHED",
            "repository": self.repository,
            "branch": branch,
            "expected_sha": expected_sha.lower(),
            "actual_sha": actual_sha,
            "read_only": True,
        }

    def _list_artifact_pages(self, *, name: str | None, max_pages: int) -> list[dict[str, Any]]:
        owner, repo = self.repository.split("/", 1)
        rows: list[dict[str, Any]] = []
        for page in range(1, max_pages + 1):
            params: dict[str, Any] = {"per_page": 100, "page": page}
            if name is not None:
                params["name"] = name
            query = urllib.parse.urlencode(params)
            payload = self._get(
                "github_list_idempotency_artifacts",
                f"/repos/{urllib.parse.quote(owner, safe='')}/{urllib.parse.quote(repo, safe='')}/actions/artifacts?{query}",
            )
            artifacts = payload.get("artifacts")
            if not isinstance(artifacts, list):
                raise GatewayError(
                    ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                    "GitHub artifact collection is structurally invalid",
                )
            for item in artifacts:
                if not isinstance(item, dict) or not isinstance(item.get("name"), str):
                    raise GatewayError(
                        ErrorClassification.PROVIDER_PROTOCOL_FAILED,
                        "GitHub artifact collection contains an invalid item",
                    )
                if not bool(item.get("expired")):
                    rows.append(item)
            if len(artifacts) < 100:
                return rows
        raise GatewayError(
            ErrorClassification.READ_BUDGET_EXCEEDED,
            "GitHub artifact marker lookup exceeded its bounded pagination limit",
            details={"name_filter": name, "max_pages": max_pages},
        )

    def list_active_artifacts_by_name(self, name: str, *, max_pages: int = 20) -> list[dict[str, Any]]:
        if not name or len(name) > 220:
            raise GatewayError(ErrorClassification.INVALID_REQUEST, "artifact idempotency marker name is invalid")
        return [item for item in self._list_artifact_pages(name=name, max_pages=max_pages) if item.get("name") == name]

    def list_active_artifacts_by_prefix(self, prefix: str, *, max_pages: int = 20) -> list[dict[str, Any]]:
        if not prefix or len(prefix) > 180:
            raise GatewayError(ErrorClassification.INVALID_REQUEST, "artifact idempotency marker prefix is invalid")
        return [
            item
            for item in self._list_artifact_pages(name=None, max_pages=max_pages)
            if str(item.get("name") or "").startswith(prefix)
        ]

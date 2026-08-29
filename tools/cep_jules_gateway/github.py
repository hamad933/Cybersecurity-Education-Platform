from __future__ import annotations

import urllib.parse
from typing import Any

from .http import JsonTransport, UrllibJsonTransport, classify_response, retry_metadata
from .models import ErrorClassification, GatewayError, ProviderObservation

DEFAULT_API_BASE = "https://api.github.com"


class GitHubClient:
    """Minimal read-only GitHub client for authority/baseline preconditions."""

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
        if 200 <= response.status < 300:
            return response.payload
        raise GatewayError(
            classification or ErrorClassification.PROVIDER_READ_FAILED,
            f"GitHub provider read failed during {operation}",
            http_status=response.status,
            retry=retry,
            details={"operation": operation},
        )

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
        actual = str(((payload.get("commit") or {}).get("sha") or "")).lower()
        if len(actual) != 40 or any(ch not in "0123456789abcdef" for ch in actual):
            raise GatewayError(ErrorClassification.INVALID_STATE, "GitHub branch response did not contain a valid commit SHA")
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

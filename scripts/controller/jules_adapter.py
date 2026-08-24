"""
CEP Fast Control Plane - Jules API Adapter & Degraded GitHub Fallback.
Standard library only (Python 3.10+). Target API: https://jules.googleapis.com/v1alpha
"""

import json
import os
import urllib.error
import urllib.request
from typing import Any, Dict, List, Optional
from scripts.controller.models import JulesSessionInfo, JulesState


class JulesAdapterError(Exception):
    def __init__(self, message: str, status_code: Optional[int] = None, classification: str = "JULES_API_ERROR"):
        super().__init__(message)
        self.status_code = status_code
        self.classification = classification


class JulesAdapter:
    BASE_URL = "https://jules.googleapis.com/v1alpha"

    def __init__(self, api_key: Optional[str] = None):
        self.api_key = api_key or os.environ.get("JULES_API_KEY", "").strip()
        self.is_degraded_mode = not bool(self.api_key)

    def _make_request(
        self, endpoint: str, method: str = "GET", payload: Optional[Dict[str, Any]] = None
    ) -> Dict[str, Any]:
        if self.is_degraded_mode:
            raise JulesAdapterError(
                "Jules API Key is missing. Operating in GitHub-only degraded mode.",
                status_code=401,
                classification="DEGRADED_GITHUB_ONLY_MODE",
            )

        url = f"{self.BASE_URL}/{endpoint.lstrip('/')}"
        headers = {
            "x-goog-api-key": self.api_key,
            "Content-Type": "application/json",
            "User-Agent": "CEP-Fast-Control-Plane/1.0",
        }

        data = json.dumps(payload).encode("utf-8") if payload is not None else None
        req = urllib.request.Request(url, data=data, headers=headers, method=method)

        try:
            with urllib.request.urlopen(req, timeout=30) as response:
                resp_body = response.read().decode("utf-8")
                return json.loads(resp_body) if resp_body else {}
        except urllib.error.HTTPError as e:
            err_body = e.read().decode("utf-8") if e.fp else ""
            if e.code == 401:
                classification = "JULES_UNAUTHORIZED"
            elif e.code == 403:
                classification = "JULES_FORBIDDEN"
            elif e.code == 404:
                classification = "JULES_NOT_FOUND"
            elif e.code == 429:
                classification = "JULES_RATE_LIMITED"
            elif e.code >= 500:
                classification = "JULES_SERVER_ERROR"
            else:
                classification = "JULES_API_ERROR"
            raise JulesAdapterError(
                f"Jules API HTTP {e.code}: {e.reason} - {err_body}",
                status_code=e.code,
                classification=classification,
            ) from e
        except urllib.error.URLError as e:
            raise JulesAdapterError(
                f"Jules API Connection Error: {e.reason}",
                status_code=503,
                classification="JULES_NETWORK_ERROR",
            ) from e

    def list_sessions(self) -> List[JulesSessionInfo]:
        """List sessions from Jules API."""
        resp = self._make_request("sessions")
        sessions_data = resp.get("sessions", [])
        results = []
        for s in sessions_data:
            state_str = s.get("state", s.get("sessionState", "UNKNOWN_JULES_STATE"))
            results.append(
                JulesSessionInfo(
                    session_id=s.get("name", "").split("/")[-1] if "/" in s.get("name", "") else s.get("name", ""),
                    name=s.get("name", ""),
                    title=s.get("title", s.get("displayName", "")),
                    state=JulesState.normalize(state_str),
                    create_time=s.get("createTime", ""),
                    update_time=s.get("updateTime", ""),
                    workstream_id=s.get("metadata", {}).get("workstream_id"),
                    role=s.get("metadata", {}).get("role", "writer"),
                    branch=s.get("metadata", {}).get("branch"),
                    head_sha=s.get("metadata", {}).get("head_sha"),
                    latest_user_feedback=s.get("latestUserFeedback"),
                )
            )
        return results

    def get_session(self, session_id: str) -> JulesSessionInfo:
        """Get single session details."""
        resource_path = session_id if session_id.startswith("sessions/") else f"sessions/{session_id}"
        s = self._make_request(resource_path)
        state_str = s.get("state", s.get("sessionState", "UNKNOWN_JULES_STATE"))
        return JulesSessionInfo(
            session_id=s.get("name", "").split("/")[-1] if "/" in s.get("name", "") else s.get("name", ""),
            name=s.get("name", ""),
            title=s.get("title", s.get("displayName", "")),
            state=JulesState.normalize(state_str),
            create_time=s.get("createTime", ""),
            update_time=s.get("updateTime", ""),
            workstream_id=s.get("metadata", {}).get("workstream_id"),
            role=s.get("metadata", {}).get("role", "writer"),
            branch=s.get("metadata", {}).get("branch"),
            head_sha=s.get("metadata", {}).get("head_sha"),
            latest_user_feedback=s.get("latestUserFeedback"),
        )

    def send_message(self, session_id: str, message: str) -> Dict[str, Any]:
        """Send message to an active Jules session."""
        resource_path = session_id if session_id.startswith("sessions/") else f"sessions/{session_id}"
        endpoint = f"{resource_path}:sendMessage"
        payload = {"message": message}
        return self._make_request(endpoint, method="POST", payload=payload)

    def create_session(
        self,
        prompt: str,
        workstream_id: str,
        branch: str,
        title: str,
        require_plan_approval: bool = False,
    ) -> JulesSessionInfo:
        """
        Create a new Jules session bound to a workstream/branch.
        """
        endpoint = "sessions"
        payload = {
            "title": title,
            "prompt": prompt,
            "requirePlanApproval": require_plan_approval,
            "metadata": {
                "workstream_id": workstream_id,
                "branch": branch,
                "role": "writer",
            },
        }
        s = self._make_request(endpoint, method="POST", payload=payload)
        state_str = s.get("state", s.get("sessionState", "QUEUED"))
        return JulesSessionInfo(
            session_id=s.get("name", "").split("/")[-1] if "/" in s.get("name", "") else s.get("name", ""),
            name=s.get("name", ""),
            title=s.get("title", title),
            state=JulesState.normalize(state_str),
            create_time=s.get("createTime", ""),
            update_time=s.get("updateTime", ""),
            workstream_id=workstream_id,
            role="writer",
            branch=branch,
        )

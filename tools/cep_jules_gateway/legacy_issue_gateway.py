from __future__ import annotations

import json
import os
import re
import sys
import urllib.parse
from typing import Any

from .digest import sha256_text
from .envelope import parse_envelope
from .http import UrllibJsonTransport, classify_response
from .inspect_bundle import build_inspect_bundle
from .jules import JulesClient
from .models import ErrorClassification, GatewayError
from .plan_identity import plan_identity_from_activities
from .sanitize import sanitize_obj, sanitize_text

_REPO = "hamad933/Cybersecurity-Education-Platform"
_SOURCE = "sources/github/hamad933/Cybersecurity-Education-Platform"
_REQUEST_ID = re.compile(r"[A-Za-z0-9][A-Za-z0-9._:-]{0,119}\Z")
_SHA = re.compile(r"[0-9a-f]{40}\Z")
_SESSION = re.compile(r"[0-9]{1,32}\Z")
_ACTIVITY = re.compile(r"sessions/[0-9]+/activities/[A-Za-z0-9._:-]+\Z")
_COMMENT_CHUNK = 34_000


class GitHubIssueClient:
    def __init__(self, token: str, issue_number: str, *, repository: str = _REPO):
        if not token:
            raise GatewayError(ErrorClassification.AUTH_FAILED, "GITHUB_TOKEN is unavailable")
        self.token = token
        self.issue_number = str(issue_number)
        self.repository = repository
        self.transport = UrllibJsonTransport(max_response_bytes=2 * 1024 * 1024)

    def _request(self, method: str, path: str, body: Any | None = None):
        response = self.transport.request_json(
            method,
            f"https://api.github.com/repos/{self.repository}{path}",
            headers={
                "Authorization": f"Bearer {self.token}",
                "Accept": "application/vnd.github+json",
                "X-GitHub-Api-Version": "2022-11-28",
            },
            timeout=30,
            body=body,
        )
        if not 200 <= response.status < 300 or response.protocol_error:
            raise GatewayError(
                ErrorClassification.PROVIDER_READ_FAILED if method == "GET" else ErrorClassification.PROVIDER_WRITE_OUTCOME_UNKNOWN,
                f"GitHub Issue transport failed during {method} {path}",
                http_status=response.status,
                details={"protocol_error": response.protocol_error, "blind_retry": False},
            )
        return response.payload

    def branch_sha(self, branch: str) -> str:
        payload = self._request("GET", "/branches/" + urllib.parse.quote(branch, safe=""))
        if not isinstance(payload, dict):
            return ""
        return str((payload.get("commit") or {}).get("sha") or "").lower()

    def comments_text(self) -> str:
        bodies: list[str] = []
        for page in range(1, 11):
            payload = self._request("GET", f"/issues/{self.issue_number}/comments?per_page=100&page={page}")
            if not isinstance(payload, list):
                raise GatewayError(ErrorClassification.PROVIDER_PROTOCOL_FAILED, "GitHub comments response is not an array")
            for row in payload:
                if isinstance(row, dict):
                    bodies.append(str(row.get("body") or ""))
            if len(payload) < 100:
                break
        return "\n".join(bodies)

    def comment(self, text: str) -> None:
        safe = sanitize_text(text)
        if len(safe) > 65_000:
            raise GatewayError(ErrorClassification.OUTPUT_BUDGET_EXCEEDED, "legacy Issue comment exceeds safe bound")
        self._request("POST", f"/issues/{self.issue_number}/comments", {"body": safe})


def _payload() -> dict[str, Any]:
    try:
        value = json.loads(os.environ.get("ISSUE_BODY") or "")
    except json.JSONDecodeError as exc:
        raise GatewayError(ErrorClassification.INVALID_REQUEST, f"invalid JSON: {exc}") from exc
    if not isinstance(value, dict):
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "Issue body must be a JSON object")
    return value


def _rid(payload: dict[str, Any]) -> str:
    rid = str(payload.get("request_id") or "").strip()
    if not _REQUEST_ID.fullmatch(rid):
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "request_id format is invalid")
    return rid


def _sid(payload: dict[str, Any]) -> str:
    sid = str(payload.get("session_id") or "").strip()
    if not _SESSION.fullmatch(sid):
        raise GatewayError(ErrorClassification.INVALID_REQUEST, "valid session_id is required")
    return sid


def _client() -> JulesClient:
    return JulesClient(
        os.environ.get("JULES_API_KEY", ""),
        api_base=os.environ.get("JULES_API_BASE", "https://jules.googleapis.com/v1alpha"),
        max_provider_reads=10_000,
    )


def _gh() -> GitHubIssueClient:
    return GitHubIssueClient(
        os.environ.get("GH_TOKEN", ""),
        os.environ.get("ISSUE_NUMBER", ""),
        repository=os.environ.get("CEP_REPOSITORY", _REPO),
    )


def _activity_name(row: dict[str, Any], sid: str) -> str:
    name = str(row.get("name") or "")
    if not _ACTIVITY.fullmatch(name) or not name.startswith(f"sessions/{sid}/activities/"):
        raise GatewayError(ErrorClassification.PROVIDER_PROTOCOL_FAILED, "malformed or cross-session activity identity")
    return name


def _activities(client: JulesClient, sid: str):
    return client.list_activities(sid, page_size=25, max_pages=100, max_items=2_000)


def _emit_json(gh: GitHubIssueClient, receipt: str, payload: Any) -> None:
    data = json.dumps(sanitize_obj(payload), ensure_ascii=False, separators=(",", ":"))
    if len(data) <= 50_000:
        gh.comment(f"{receipt}\n```json\n{data}\n```")
        return
    parts = [data[i : i + _COMMENT_CHUNK] for i in range(0, len(data), _COMMENT_CHUNK)]
    for i, part in enumerate(parts, start=1):
        fragment = json.dumps({"part": i, "parts": len(parts), "json_fragment": part}, ensure_ascii=False, separators=(",", ":"))
        gh.comment(f"{receipt}\npart={i}/{len(parts)}\n```json\n{fragment}\n```")


def _emit_exact_text(gh: GitHubIssueClient, receipt: str, kind: str, metadata: dict[str, Any], text: str) -> None:
    safe = sanitize_text(text)
    parts = [safe[i : i + _COMMENT_CHUNK] for i in range(0, len(safe), _COMMENT_CHUNK)] or [""]
    header = dict(metadata)
    header.update({"text_kind": kind, "text_chars": len(safe), "text_sha256": sha256_text(safe), "parts": len(parts)})
    _emit_json(gh, receipt + "\nmetadata", header)
    for i, part in enumerate(parts, start=1):
        _emit_json(gh, receipt + f"\n{kind}_part={i}/{len(parts)}", {"part": i, "parts": len(parts), "text_fragment": part})


def _unknown(gh: GitHubIssueClient, rid: str, action: str, *, sid: str = "", reason: str) -> int:
    gh.comment(
        f"JULES_CONTROL_UNKNOWN_WRITE_OUTCOME\nrequest_id={rid}\naction={action}"
        + (f"\nsession_id={sid}" if sid else "")
        + f"\nreason={reason}\nblind_retry=false\nnext=AUTHORITATIVE_PROVIDER_READ_AND_RECONCILE_BEFORE_ANY_REPLAY"
    )
    return 6


def _preexisting_write_guard(gh: GitHubIssueClient, rid: str, action: str) -> int | None:
    text = gh.comments_text()
    receipt = f"JULES_CONTROL_RECEIPT request_id={rid}"
    intent = f"JULES_CONTROL_WRITE_INTENT request_id={rid}"
    if receipt in text:
        return 0
    if action in {"create_session", "send_message", "approve_plan"} and intent in text:
        gh.comment(
            f"JULES_CONTROL_BLOCKED\nrequest_id={rid}\nreason=UNKNOWN_PRIOR_WRITE_OUTCOME__AUTHORITATIVE_PROVIDER_READ_REQUIRED\nblind_retry=false"
        )
        return 3
    return None


def run_control() -> int:
    gh = _gh()
    try:
        payload = _payload()
        rid = _rid(payload)
        action = str(payload.get("action") or "").strip()
        allowed = {"create_session", "get_session", "list_sessions", "list_activities", "send_message", "approve_plan"}
        if action not in allowed:
            raise GatewayError(ErrorClassification.INVALID_REQUEST, f"action not allowed: {action}")
        guarded = _preexisting_write_guard(gh, rid, action)
        if guarded is not None:
            return guarded
        client = _client()
        receipt = f"JULES_CONTROL_RECEIPT request_id={rid}"
        intent = f"JULES_CONTROL_WRITE_INTENT request_id={rid}"

        if action == "create_session":
            required = ["task_id", "starting_branch", "expected_sha", "title", "prompt"]
            missing = [key for key in required if not str(payload.get(key) or "").strip()]
            if missing:
                raise GatewayError(ErrorClassification.INVALID_REQUEST, "missing fields: " + ",".join(missing))
            branch = str(payload["starting_branch"]).strip()
            expected_sha = str(payload["expected_sha"]).strip().lower()
            if not _SHA.fullmatch(expected_sha):
                raise GatewayError(ErrorClassification.INVALID_REQUEST, "expected_sha format is invalid")
            actual_sha = gh.branch_sha(branch)
            if actual_sha != expected_sha:
                gh.comment(f"JULES_CONTROL_REJECTED\nrequest_id={rid}\nreason=BRANCH_HEAD_DRIFT\nbranch={branch}\nexpected_sha={expected_sha}\nactual_sha={actual_sha or 'MISSING'}")
                return 4
            sources = {str(item.get("name") or "") for item in client.list_sources()}
            if _SOURCE not in sources:
                gh.comment(f"JULES_CONTROL_BLOCKED\nrequest_id={rid}\nreason=JULES_SOURCE_MISSING")
                return 5
            gh.comment(f"{intent}\naction=create_session\nbranch={branch}\nexpected_sha={expected_sha}\nrequire_plan_approval=true\nauto_create_pr=false\nblind_retry=false")
            body = {
                "prompt": str(payload["prompt"]),
                "title": str(payload["title"]),
                "sourceContext": {"source": _SOURCE, "githubRepoContext": {"startingBranch": branch}},
                "requirePlanApproval": True,
            }
            try:
                created = client.create_session(body)
            except GatewayError as exc:
                return _unknown(gh, rid, action, reason=exc.classification.value)
            sid = str(created.get("id") or "")
            if not _SESSION.fullmatch(sid):
                return _unknown(gh, rid, action, reason="CREATE_RESPONSE_MISSING_SESSION_ID")
            try:
                confirmed = client.get_session(sid)
            except GatewayError as exc:
                return _unknown(gh, rid, action, sid=sid, reason=f"POST_CREATE_READBACK_{exc.classification.value}")
            if str(confirmed.get("id") or "") != sid:
                return _unknown(gh, rid, action, sid=sid, reason="POST_CREATE_IDENTITY_MISMATCH")
            gh.comment(
                f"{receipt}\naction=create_session\noutcome=CREATED\nverification=AUTHORITATIVE_POST_READ\ntask_id={payload['task_id']}"
                f"\nsession_id={sid}\nstate={confirmed.get('state','')}\nurl={sanitize_text(confirmed.get('url'))}"
                f"\nstarting_branch={branch}\nexpected_sha={expected_sha}\nrequire_plan_approval=true\nauto_create_pr=false\nblind_retry=false"
            )
            return 0

        if action == "list_sessions":
            result = client.list_sessions(page_size=100, max_pages=20, max_items=2_000)
            rows = [{"id": x.get("id"), "title": x.get("title"), "state": x.get("state"), "updateTime": x.get("updateTime")} for x in result.items[-100:]]
            _emit_json(gh, f"{receipt}\naction=list_sessions\nhttp_status=200", {"sessions": rows, "pagination": result.info.to_dict()})
            return 0

        sid = _sid(payload)
        if action == "get_session":
            out = client.get_session(sid)
            gh.comment(f"{receipt}\naction=get_session\nhttp_status=200\nsession_id={sid}\nstate={out.get('state','')}\nupdate_time={out.get('updateTime','')}\nurl={sanitize_text(out.get('url'))}")
            return 0

        if action == "list_activities":
            result = _activities(client, sid)
            rows = []
            for row in result.items[-40:]:
                _activity_name(row, sid)
                rows.append({
                    "createTime": row.get("createTime"),
                    "originator": row.get("originator"),
                    "description": sanitize_text(row.get("description")),
                    "hasPlan": bool(row.get("planGenerated")),
                    "hasAgentMessage": bool(row.get("agentMessaged")),
                    "hasChangeSet": any(isinstance(z, dict) and isinstance(z.get("changeSet"), dict) for z in (row.get("artifacts") or [])),
                })
            _emit_json(gh, f"{receipt}\naction=list_activities\nhttp_status=200\nsession_id={sid}", {"activities": rows, "pagination": result.info.to_dict()})
            return 0

        pre_session = client.get_session(sid)
        pre_update = str(pre_session.get("updateTime") or "")
        pre_activities = _activities(client, sid)
        pre_names = {_activity_name(row, sid) for row in pre_activities.items}

        if action == "send_message":
            prompt = str(payload.get("prompt") or "")
            if not prompt:
                raise GatewayError(ErrorClassification.INVALID_REQUEST, "prompt is required")
            gh.comment(f"{intent}\naction=send_message\nsession_id={sid}\nblind_retry=false")
            final_session = client.get_session(sid)
            if str(final_session.get("updateTime") or "") != pre_update:
                gh.comment(f"JULES_CONTROL_REJECTED\nrequest_id={rid}\nreason=SESSION_DRIFT_BEFORE_WRITE\nsession_id={sid}")
                return 4
            try:
                client.send_message(sid, prompt)
            except GatewayError as exc:
                return _unknown(gh, rid, action, sid=sid, reason=exc.classification.value)
            try:
                post_session = client.get_session(sid)
                post_activities = _activities(client, sid)
            except GatewayError as exc:
                return _unknown(gh, rid, action, sid=sid, reason=f"POST_WRITE_READBACK_{exc.classification.value}")
            matches = []
            for row in post_activities.items:
                name = _activity_name(row, sid)
                if name in pre_names:
                    continue
                message = row.get("userMessaged")
                if isinstance(message, dict) and str(message.get("userMessage") or "") == prompt:
                    matches.append(name)
            if len(matches) != 1:
                return _unknown(gh, rid, action, sid=sid, reason="EXACT_USER_MESSAGE_ACTIVITY_NOT_UNIQUE")
            gh.comment(
                f"{receipt}\naction=send_message\noutcome=SENT\nverification=EXACT_NEW_USER_MESSAGED_ACTIVITY"
                f"\nsession_id={sid}\nactivity_name={matches[0]}\npost_update_time={post_session.get('updateTime','')}\nblind_retry=false"
            )
            return 0

        if action == "approve_plan":
            identity = plan_identity_from_activities(pre_activities.items, sid)
            plan_id = str(identity.get("plan_id") or "")
            if identity.get("status") != "FOUND" or not plan_id:
                gh.comment(f"JULES_CONTROL_REJECTED\nrequest_id={rid}\nreason=STABLE_PLAN_ID_REQUIRED\nsession_id={sid}")
                return 4
            gh.comment(f"{intent}\naction=approve_plan\nsession_id={sid}\nplan_id={plan_id}\nblind_retry=false")
            final_session = client.get_session(sid)
            final_activities = _activities(client, sid)
            final_identity = plan_identity_from_activities(final_activities.items, sid)
            if str(final_session.get("updateTime") or "") != pre_update or final_identity.get("provider_identity_digest") != identity.get("provider_identity_digest"):
                gh.comment(f"JULES_CONTROL_REJECTED\nrequest_id={rid}\nreason=PLAN_OR_SESSION_DRIFT_BEFORE_WRITE\nsession_id={sid}")
                return 4
            final_names = {_activity_name(row, sid) for row in final_activities.items}
            try:
                client.approve_plan(sid)
            except GatewayError as exc:
                return _unknown(gh, rid, action, sid=sid, reason=exc.classification.value)
            try:
                post_session = client.get_session(sid)
                post_activities = _activities(client, sid)
            except GatewayError as exc:
                return _unknown(gh, rid, action, sid=sid, reason=f"POST_WRITE_READBACK_{exc.classification.value}")
            matches = []
            for row in post_activities.items:
                name = _activity_name(row, sid)
                if name in final_names:
                    continue
                approved = row.get("planApproved")
                if isinstance(approved, dict) and str(approved.get("planId") or "") == plan_id:
                    matches.append(name)
            if len(matches) != 1:
                return _unknown(gh, rid, action, sid=sid, reason="EXACT_PLAN_APPROVED_ACTIVITY_NOT_UNIQUE")
            gh.comment(
                f"{receipt}\naction=approve_plan\noutcome=APPROVED\nverification=EXACT_NEW_PLAN_APPROVED_ACTIVITY"
                f"\nsession_id={sid}\nplan_id={plan_id}\nactivity_name={matches[0]}\npost_update_time={post_session.get('updateTime','')}\nblind_retry=false"
            )
            return 0
        raise AssertionError("unreachable")
    except GatewayError as exc:
        try:
            gh.comment(f"JULES_CONTROL_REJECTED\nreason={exc.classification.value}\ndetail={sanitize_text(exc.message)}")
        except Exception:
            pass
        return 2


def _inspect_envelope(rid: str, sid: str, action: str):
    options: dict[str, Any] = {
        "page_size": 25,
        "max_activity_pages": 100,
        "max_total_items": 2_000,
        "max_provider_reads": 10_000,
        "max_hydration_reads": 100,
        "max_exact_text_chars": 1_000_000,
        "max_total_exact_text_bytes": 4_000_000,
        "max_serialized_result_bytes": 8_000_000,
    }
    if action == "get_agent_messages":
        options["recent_agent_messages"] = 100
    if action == "get_latest_changeset":
        options["include_patch"] = True
    if action == "get_bash_outputs":
        options["recent_bash_outputs"] = 50
        options["include_bash_output_text"] = True
    return parse_envelope(json.dumps({
        "schema_version": "2.0",
        "request_id": rid,
        "controller_id": "PARENT",
        "lane": "PARENT",
        "action": "inspect_bundle",
        "session_id": sid,
        "options": options,
    }))


def run_inspect() -> int:
    gh = _gh()
    try:
        payload = _payload()
        rid = _rid(payload)
        sid = _sid(payload)
        action = str(payload.get("action") or "").strip()
        allowed = {"get_plan", "get_agent_messages", "get_changeset_index", "get_latest_changeset", "get_bash_outputs"}
        if action not in allowed:
            raise GatewayError(ErrorClassification.INVALID_REQUEST, f"action not allowed: {action}")
        bundle = build_inspect_bundle(_inspect_envelope(rid, sid, action), _client())
        receipt = f"JULES_INSPECT_RECEIPT request_id={rid}\naction={action}\nhttp_status=200\nsession_id={sid}"
        if action == "get_plan":
            _emit_json(gh, receipt, bundle["plan"])
        elif action == "get_agent_messages":
            _emit_json(gh, receipt, bundle["agent_messages"])
        elif action == "get_changeset_index":
            _emit_json(gh, receipt, {"activity_count_scanned": bundle["provider"]["activity_count_scanned"], **bundle["changesets"]})
        elif action == "get_latest_changeset":
            latest = bundle["changesets"]["latest_exact_patch"]
            text = str(latest.pop("unidiff_patch", "")) if isinstance(latest, dict) else ""
            if text:
                _emit_exact_text(gh, receipt, "unidiffPatch", latest, text)
            else:
                _emit_json(gh, receipt, latest)
        else:
            evidence = bundle["bash_evidence"]
            exact = list(evidence.get("recent_exact") or [])
            index = []
            texts = []
            for i, row in enumerate(exact, start=1):
                row = dict(row)
                text = str(row.pop("output", ""))
                row["output_index"] = i
                index.append(row)
                texts.append((i, row, text))
            _emit_json(gh, receipt, {"activity_count_scanned": bundle["provider"]["activity_count_scanned"], "bash_outputs": index})
            for i, meta, text in texts:
                if text:
                    _emit_exact_text(gh, receipt + f"\noutput_index={i}", "bashOutput", meta, text)
        return 0
    except GatewayError as exc:
        try:
            gh.comment(f"JULES_INSPECT_RECEIPT\noutcome=READ_FAILED\nreason={exc.classification.value}\ndetail={sanitize_text(exc.message)}")
        except Exception:
            pass
        return 7


def main(argv: list[str] | None = None) -> int:
    argv = list(sys.argv[1:] if argv is None else argv)
    if argv == ["control"]:
        return run_control()
    if argv == ["inspect"]:
        return run_inspect()
    print("usage: python -m tools.cep_jules_gateway.legacy_issue_gateway {control|inspect}", file=sys.stderr)
    return 2


if __name__ == "__main__":
    raise SystemExit(main())

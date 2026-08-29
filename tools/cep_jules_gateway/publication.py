from __future__ import annotations

import argparse
import hashlib
import json
import os
import pathlib
import re
import sys
from dataclasses import dataclass
from typing import Any, Protocol

_SHA_RE = re.compile(r"^[0-9a-f]{40}$")
_SHA256_RE = re.compile(r"^[0-9a-f]{64}$")
_ID_RE = re.compile(r"^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$")
_BRANCH_RE = re.compile(r"^[A-Za-z0-9][A-Za-z0-9._/-]{0,159}$")
_ALLOWED_BRANCH_PREFIXES = ("work/", "fix/", "checkpoint/", "candidate/")
_CONTROLLER_LANES = {
    "PARENT": {"PARENT", "W01_W02", "W03_W04", "W05"},
    "A": {"W03_W04"},
    "B": {"W01_W02"},
    "C": {"W05"},
}
_FORBIDDEN_PATH_PREFIXES = (
    ".git/",
    ".github/",
    "tools/cep_jules_gateway/",
    "vendor/",
    "node_modules/",
)
_FORBIDDEN_EXACT_PATHS = {".env", ".env.local", ".env.production"}
_HIGH_CONFIDENCE_SECRET_PATTERNS = (
    re.compile(r"\bgh[pousr]_[A-Za-z0-9]{20,}\b"),
    re.compile(r"\bAIza[0-9A-Za-z_-]{20,}\b"),
    re.compile(r"\beyJ[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\b"),
    re.compile(
        r"(?is)-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----.*?"
        r"-----END (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"
    ),
)
_MAX_PATCH_BYTES = 2_000_000


class PublicationError(RuntimeError):
    pass


class PublicationClient(Protocol):
    def get_session(self, session_id: str) -> dict[str, Any]: ...

    def list_activities(
        self,
        session_id: str,
        *,
        page_size: int,
        max_pages: int,
        max_items: int = 2_000,
    ) -> Any: ...

    def get_activity(self, activity_name: str) -> dict[str, Any]: ...


@dataclass(frozen=True)
class PublicationCandidate:
    session_id: str
    session_state: str
    session_update_time: str
    activity_name: str
    source: str
    base_commit_id: str
    review_sha256: str
    paths_sha256: str
    changed_paths: tuple[str, ...]
    patch: str


def sha256_text(value: str) -> str:
    return hashlib.sha256(value.encode("utf-8")).hexdigest()


def _require_sha(value: str, label: str) -> str:
    value = value.strip().lower()
    if not _SHA_RE.fullmatch(value):
        raise PublicationError(f"{label} must be an exact lowercase 40-hex commit SHA")
    return value


def _require_sha256(value: str, label: str) -> str:
    value = value.strip().lower()
    if not _SHA256_RE.fullmatch(value):
        raise PublicationError(f"{label} must be an exact lowercase SHA-256")
    return value


def _require_id(value: str, label: str) -> str:
    value = value.strip()
    if not _ID_RE.fullmatch(value):
        raise PublicationError(f"{label} is not a bounded public-safe identifier")
    return value


def validate_routing(
    *,
    controller_id: str,
    lane: str,
    request_id: str,
    logical_task: str,
    write_domain: str,
    target_branch: str,
) -> None:
    if controller_id not in _CONTROLLER_LANES:
        raise PublicationError("controller_id is not recognized")
    if lane not in _CONTROLLER_LANES[controller_id]:
        raise PublicationError("controller_id/lane mapping is not authorized")
    _require_id(request_id, "request_id")
    _require_id(logical_task, "logical_task")
    _require_id(write_domain, "write_domain")
    if not _BRANCH_RE.fullmatch(target_branch):
        raise PublicationError("target_branch is not a bounded Git branch name")
    if ".." in target_branch or "//" in target_branch or "@{" in target_branch or target_branch.endswith(("/", ".")):
        raise PublicationError("target_branch contains a prohibited ref sequence")
    if target_branch in {"main", "master"} or target_branch.startswith(("release/", "refs/", "tags/")):
        raise PublicationError("publication may never target main/master/release/ref namespaces")
    if not target_branch.startswith(_ALLOWED_BRANCH_PREFIXES):
        raise PublicationError("target_branch must be an isolated work/fix/checkpoint/candidate branch")


def canonical_patch_paths(patch: str) -> tuple[str, ...]:
    paths: set[str] = set()
    saw_header = False
    for line in patch.splitlines():
        if not line.startswith("diff --git "):
            continue
        saw_header = True
        match = re.fullmatch(r"diff --git a/(\S+) b/(\S+)", line)
        if match is None:
            raise PublicationError("quoted, spaced, or malformed diff paths are not publishable")
        for path in match.groups():
            if path == "/dev/null":
                continue
            if path.startswith("/") or path.startswith("../") or "/../" in path or "\\" in path:
                raise PublicationError("patch contains an unsafe repository path")
            paths.add(path)
    if not saw_header or not paths:
        raise PublicationError("provider changeSet contains no canonical git diff paths")
    return tuple(sorted(paths))


def paths_sha256(paths: tuple[str, ...]) -> str:
    return sha256_text("".join(f"{path}\n" for path in paths))


def validate_patch_safety(patch: str, paths: tuple[str, ...]) -> None:
    patch_bytes = len(patch.encode("utf-8"))
    if patch_bytes <= 0 or patch_bytes > _MAX_PATCH_BYTES:
        raise PublicationError("provider patch is empty or exceeds the publication byte bound")
    if "GIT binary patch" in patch:
        raise PublicationError("binary patches are not accepted by the candidate publisher")
    if re.search(r"(?m)^(?:new file mode|old mode) 120000$", patch) or "160000" in patch:
        raise PublicationError("symlink or gitlink/submodule mode changes are prohibited")
    for path in paths:
        if path in _FORBIDDEN_EXACT_PATHS or path.startswith(_FORBIDDEN_PATH_PREFIXES):
            raise PublicationError(f"candidate publication path is reserved: {path}")
    for pattern in _HIGH_CONFIDENCE_SECRET_PATTERNS:
        if pattern.search(patch):
            raise PublicationError("provider patch contains a high-confidence secret/token pattern")


def _activity_name(activity: dict[str, Any], session_id: str) -> str:
    name = str(activity.get("name") or "")
    prefix = f"sessions/{session_id}/activities/"
    suffix = name[len(prefix):] if name.startswith(prefix) else ""
    if not suffix or "/" in suffix:
        raise PublicationError("provider returned malformed or cross-session activity identity")
    return name


def _has_changeset(activity: dict[str, Any]) -> bool:
    artifacts = activity.get("artifacts") or []
    return any(isinstance(item, dict) and isinstance(item.get("changeSet"), dict) for item in artifacts)


def _latest_changeset_activity(activities: list[dict[str, Any]], session_id: str) -> dict[str, Any]:
    candidates: list[dict[str, Any]] = []
    for activity in activities:
        _activity_name(activity, session_id)
        if _has_changeset(activity):
            candidates.append(activity)
    if not candidates:
        raise PublicationError("provider session has no changeSet activity")
    candidates.sort(key=lambda item: (str(item.get("createTime") or ""), _activity_name(item, session_id)))
    latest_time = str(candidates[-1].get("createTime") or "")
    tied = [item for item in candidates if str(item.get("createTime") or "") == latest_time]
    if len(tied) != 1:
        raise PublicationError("latest provider changeSet selection is ambiguous at the newest timestamp")
    return tied[0]


def fetch_publication_candidate(
    client: PublicationClient,
    *,
    repository: str,
    session_id: str,
    expected_session_state: str,
    expected_session_update_time: str,
    expected_base_sha: str,
    expected_review_sha256: str,
    expected_paths_sha256: str,
) -> PublicationCandidate:
    if not session_id.isdigit() or len(session_id) > 32:
        raise PublicationError("session_id must be the exact numeric Jules session identity")
    expected_base_sha = _require_sha(expected_base_sha, "expected_base_sha")
    expected_review_sha256 = _require_sha256(expected_review_sha256, "expected_review_sha256")
    expected_paths_sha256 = _require_sha256(expected_paths_sha256, "expected_paths_sha256")
    if expected_session_state not in {"COMPLETED", "AWAITING_USER_FEEDBACK"}:
        raise PublicationError("only quiescent COMPLETED/AWAITING_USER_FEEDBACK sessions may publish")
    if not expected_session_update_time or len(expected_session_update_time) > 96:
        raise PublicationError("expected_session_update_time is required and bounded")

    session = client.get_session(session_id)
    if str(session.get("id") or "") != session_id:
        raise PublicationError("Jules session identity changed during publication preflight")
    state = str(session.get("state") or "")
    update_time = str(session.get("updateTime") or "")
    if state != expected_session_state:
        raise PublicationError("Jules session state drifted since Controller review")
    if update_time != expected_session_update_time:
        raise PublicationError("Jules session update identity drifted since Controller review")

    result = client.list_activities(session_id, page_size=100, max_pages=20, max_items=2_000)
    info = getattr(result, "info", None)
    if info is not None and getattr(info, "complete", True) is not True:
        raise PublicationError("provider activity pagination is incomplete; latest changeSet cannot be proven")
    activities = list(getattr(result, "items", []))
    selected = _latest_changeset_activity(activities, session_id)
    name = _activity_name(selected, session_id)
    full = client.get_activity(name)
    if _activity_name(full, session_id) != name:
        raise PublicationError("hydrated changeSet activity identity mismatch")

    patches: list[tuple[str, str, str]] = []
    for artifact in full.get("artifacts") or []:
        if not isinstance(artifact, dict):
            continue
        change_set = artifact.get("changeSet")
        if not isinstance(change_set, dict):
            continue
        git_patch = change_set.get("gitPatch")
        if not isinstance(git_patch, dict):
            continue
        patch = git_patch.get("unidiffPatch")
        if isinstance(patch, str) and patch:
            patches.append((str(change_set.get("source") or ""), str(git_patch.get("baseCommitId") or ""), patch))
    if len(patches) != 1:
        raise PublicationError("latest hydrated changeSet must contain exactly one publishable git patch")

    source, base_commit_id, patch = patches[0]
    if source != f"sources/github/{repository}":
        raise PublicationError("provider changeSet repository source does not match the CEP repository")
    if base_commit_id.lower() != expected_base_sha:
        raise PublicationError("provider patch baseCommitId does not match the reviewed remote branch SHA")
    review_sha = sha256_text(patch)
    if review_sha != expected_review_sha256:
        raise PublicationError("provider patch digest drifted since Controller review")
    paths = canonical_patch_paths(patch)
    validate_patch_safety(patch, paths)
    actual_paths_sha = paths_sha256(paths)
    if actual_paths_sha != expected_paths_sha256:
        raise PublicationError("provider changed-path identity drifted since Controller review")

    return PublicationCandidate(
        session_id=session_id,
        session_state=state,
        session_update_time=update_time,
        activity_name=name,
        source=source,
        base_commit_id=base_commit_id.lower(),
        review_sha256=review_sha,
        paths_sha256=actual_paths_sha,
        changed_paths=paths,
        patch=patch,
    )


def _write_candidate(candidate: PublicationCandidate, patch_out: pathlib.Path, receipt_out: pathlib.Path) -> None:
    patch_out.parent.mkdir(parents=True, exist_ok=True)
    receipt_out.parent.mkdir(parents=True, exist_ok=True)
    patch_out.write_text(candidate.patch, encoding="utf-8", newline="")
    receipt_out.write_text(
        json.dumps(
            {
                "schema_version": "cep.jules.publication-preflight/v1",
                "session_id": candidate.session_id,
                "session_state": candidate.session_state,
                "session_update_time": candidate.session_update_time,
                "activity_name": candidate.activity_name,
                "source": candidate.source,
                "base_commit_id": candidate.base_commit_id,
                "review_sha256": candidate.review_sha256,
                "paths_sha256": candidate.paths_sha256,
                "changed_paths": list(candidate.changed_paths),
                "patch_bytes": len(candidate.patch.encode("utf-8")),
            },
            sort_keys=True,
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )


def _parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Prepare one reviewed Jules changeSet for trusted GitHub candidate publication")
    parser.add_argument("--repository", required=True)
    parser.add_argument("--controller-id", required=True)
    parser.add_argument("--lane", required=True)
    parser.add_argument("--request-id", required=True)
    parser.add_argument("--logical-task", required=True)
    parser.add_argument("--write-domain", required=True)
    parser.add_argument("--session-id", required=True)
    parser.add_argument("--expected-session-state", required=True)
    parser.add_argument("--expected-session-update-time", required=True)
    parser.add_argument("--target-branch", required=True)
    parser.add_argument("--expected-base-sha", required=True)
    parser.add_argument("--expected-review-sha256", required=True)
    parser.add_argument("--expected-paths-sha256", required=True)
    parser.add_argument("--patch-out", required=True)
    parser.add_argument("--receipt-out", required=True)
    return parser


def main(argv: list[str] | None = None) -> int:
    args = _parser().parse_args(argv)
    try:
        validate_routing(
            controller_id=args.controller_id,
            lane=args.lane,
            request_id=args.request_id,
            logical_task=args.logical_task,
            write_domain=args.write_domain,
            target_branch=args.target_branch,
        )
        from .jules import JulesClient

        client = JulesClient(os.environ.get("JULES_API_KEY", ""), max_provider_reads=2_100)
        candidate = fetch_publication_candidate(
            client,
            repository=args.repository,
            session_id=args.session_id,
            expected_session_state=args.expected_session_state,
            expected_session_update_time=args.expected_session_update_time,
            expected_base_sha=args.expected_base_sha,
            expected_review_sha256=args.expected_review_sha256,
            expected_paths_sha256=args.expected_paths_sha256,
        )
        _write_candidate(candidate, pathlib.Path(args.patch_out), pathlib.Path(args.receipt_out))
    except PublicationError as exc:
        print(f"PUBLICATION_PREFLIGHT_FAILED: {exc}", file=sys.stderr)
        return 2
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

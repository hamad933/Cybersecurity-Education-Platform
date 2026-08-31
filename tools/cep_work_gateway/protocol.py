from __future__ import annotations

import base64
import binascii
import gzip
import hashlib
import io
import json
import re
from dataclasses import dataclass
from typing import Any, Iterable

_SHA_RE = re.compile(r"^[0-9a-f]{40}$")
_SHA256_RE = re.compile(r"^[0-9a-f]{64}$")
_ID_RE = re.compile(r"^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$")
_BRANCH_RE = re.compile(r"^[A-Za-z0-9][A-Za-z0-9._/-]{0,159}$")
_ALLOWED_BRANCH_PREFIXES = ("work/", "fix/", "checkpoint/", "candidate/")
_CONTROLLER_WORKSPACES = {
    "PARENT": {"PARENT", "W01", "W02", "W03", "W04", "W05"},
    "A": {"W01"},
    "B": {"W02"},
    "C": {"W03"},
    "D": {"W04"},
    "E": {"W05"},
}
_FORBIDDEN_PATH_PREFIXES = (
    ".git/",
    ".github/",
    "tools/cep_work_gateway/",
    "tools/cep_jules_gateway/",
    "vendor/",
    "node_modules/",
    "storage/framework/",
    "storage/logs/",
)
_FORBIDDEN_EXACT_PATHS = {
    ".env",
    ".env.local",
    ".env.production",
    ".gitmodules",
    "AGENTS.md",
}
_HIGH_CONFIDENCE_SECRET_PATTERNS = (
    re.compile(r"\bgh[pousr]_[A-Za-z0-9]{20,}\b"),
    re.compile(r"\bgithub_pat_[A-Za-z0-9_]{20,}\b"),
    re.compile(r"\bAIza[0-9A-Za-z_-]{20,}\b"),
    re.compile(r"\beyJ[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\b"),
    re.compile(
        r"(?is)-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----.*?"
        r"-----END (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"
    ),
)
_MAX_PATCH_BYTES = 2_000_000
_MAX_COMPRESSED_BYTES = 2_500_000
_MAX_CHUNKS = 64
_MAX_CHUNK_TEXT = 52_000

_CHUNK_RE = re.compile(
    r"\ACEP_WORK_PATCH_CHUNK request_id=(?P<request>[A-Za-z0-9][A-Za-z0-9._:-]{0,127}) "
    r"index=(?P<index>[0-9]{1,3}) total=(?P<total>[0-9]{1,3})\n(?P<payload>[A-Za-z0-9+/=\r\n]+)\Z"
)
_COMPLETE_RE = re.compile(
    r"\ACEP_WORK_HANDOFF_COMPLETE request_id=(?P<request>[A-Za-z0-9][A-Za-z0-9._:-]{0,127}) "
    r"patch_sha256=(?P<patch>[0-9a-f]{64}) compressed_sha256=(?P<compressed>[0-9a-f]{64})\Z"
)

_HANDOFF_KEYS = {
    "schema_version",
    "request_id",
    "controller_id",
    "workspace",
    "logical_task",
    "write_domain",
    "target_branch",
    "expected_base_sha",
    "patch_encoding",
    "patch_sha256",
    "paths_sha256",
    "compressed_sha256",
    "patch_chunks",
    "patch_bytes",
    "local_commit_sha",
    "tests_status",
}

_PUBLISH_KEYS = {
    "schema_version",
    "request_id",
    "controller_id",
    "workspace",
    "logical_task",
    "write_domain",
    "handoff_issue_number",
    "intake_run_id",
    "intake_head_sha",
    "target_branch",
    "expected_remote_sha",
    "expected_patch_sha256",
    "expected_paths_sha256",
}


class WorkGatewayError(RuntimeError):
    pass


@dataclass(frozen=True)
class HandoffManifest:
    schema_version: str
    request_id: str
    controller_id: str
    workspace: str
    logical_task: str
    write_domain: str
    target_branch: str
    expected_base_sha: str
    patch_encoding: str
    patch_sha256: str
    paths_sha256: str
    compressed_sha256: str
    patch_chunks: int
    patch_bytes: int
    local_commit_sha: str
    tests_status: str

    def as_dict(self) -> dict[str, Any]:
        return {
            "schema_version": self.schema_version,
            "request_id": self.request_id,
            "controller_id": self.controller_id,
            "workspace": self.workspace,
            "logical_task": self.logical_task,
            "write_domain": self.write_domain,
            "target_branch": self.target_branch,
            "expected_base_sha": self.expected_base_sha,
            "patch_encoding": self.patch_encoding,
            "patch_sha256": self.patch_sha256,
            "paths_sha256": self.paths_sha256,
            "compressed_sha256": self.compressed_sha256,
            "patch_chunks": self.patch_chunks,
            "patch_bytes": self.patch_bytes,
            "local_commit_sha": self.local_commit_sha,
            "tests_status": self.tests_status,
        }


@dataclass(frozen=True)
class ReconstructedHandoff:
    manifest: HandoffManifest
    patch: str
    changed_paths: tuple[str, ...]


@dataclass(frozen=True)
class PublishRequest:
    schema_version: str
    request_id: str
    controller_id: str
    workspace: str
    logical_task: str
    write_domain: str
    handoff_issue_number: str
    intake_run_id: str
    intake_head_sha: str
    target_branch: str
    expected_remote_sha: str
    expected_patch_sha256: str
    expected_paths_sha256: str

    def as_dict(self) -> dict[str, str]:
        return {key: str(getattr(self, key)) for key in sorted(_PUBLISH_KEYS)}


def sha256_bytes(value: bytes) -> str:
    return hashlib.sha256(value).hexdigest()


def sha256_text(value: str) -> str:
    return sha256_bytes(value.encode("utf-8"))


def _parse_json_object(body: str, *, label: str) -> dict[str, Any]:
    try:
        value = json.loads(body)
    except json.JSONDecodeError as exc:
        raise WorkGatewayError(f"{label} must be one strict JSON object") from exc
    if not isinstance(value, dict):
        raise WorkGatewayError(f"{label} must be one strict JSON object")
    return value


def _require_exact_keys(value: dict[str, Any], expected: set[str], *, label: str) -> None:
    if set(value) != expected:
        missing = sorted(expected - set(value))
        extra = sorted(set(value) - expected)
        raise WorkGatewayError(f"{label} keys mismatch missing={missing} extra={extra}")


def _require_id(value: Any, label: str) -> str:
    if not isinstance(value, str) or not _ID_RE.fullmatch(value):
        raise WorkGatewayError(f"{label} is not a bounded public-safe identifier")
    return value


def _require_sha(value: Any, label: str) -> str:
    if not isinstance(value, str) or not _SHA_RE.fullmatch(value):
        raise WorkGatewayError(f"{label} must be exact lowercase 40-hex SHA")
    return value


def _require_sha256(value: Any, label: str) -> str:
    if not isinstance(value, str) or not _SHA256_RE.fullmatch(value):
        raise WorkGatewayError(f"{label} must be exact lowercase SHA-256")
    return value


def _require_decimal_id(value: Any, label: str) -> str:
    if not isinstance(value, str) or not value.isdigit() or len(value) > 24 or int(value) <= 0:
        raise WorkGatewayError(f"{label} must be a bounded positive decimal string")
    return value


def validate_routing(
    *, controller_id: str, workspace: str, request_id: str, logical_task: str,
    write_domain: str, target_branch: str,
) -> None:
    if controller_id not in _CONTROLLER_WORKSPACES:
        raise WorkGatewayError("controller_id is not recognized")
    if workspace not in _CONTROLLER_WORKSPACES[controller_id]:
        raise WorkGatewayError("controller_id/workspace mapping is not authorized")
    _require_id(request_id, "request_id")
    _require_id(logical_task, "logical_task")
    _require_id(write_domain, "write_domain")
    if not isinstance(target_branch, str) or not _BRANCH_RE.fullmatch(target_branch):
        raise WorkGatewayError("target_branch is not a bounded Git branch name")
    if ".." in target_branch or "//" in target_branch or "@{" in target_branch or target_branch.endswith(("/", ".")):
        raise WorkGatewayError("target_branch contains a prohibited ref sequence")
    if target_branch in {"main", "master"} or target_branch.startswith(("release/", "refs/", "tags/")):
        raise WorkGatewayError("publication may never target main/master/release/ref namespaces")
    if not target_branch.startswith(_ALLOWED_BRANCH_PREFIXES):
        raise WorkGatewayError("target_branch must be an isolated work/fix/checkpoint/candidate branch")


def parse_handoff_manifest(body: str) -> HandoffManifest:
    value = _parse_json_object(body, label="handoff issue body")
    _require_exact_keys(value, _HANDOFF_KEYS, label="handoff manifest")
    if value["schema_version"] != "cep.work.handoff/v1":
        raise WorkGatewayError("unsupported handoff schema_version")

    request_id = _require_id(value["request_id"], "request_id")
    controller_id = str(value["controller_id"])
    workspace = str(value["workspace"])
    logical_task = _require_id(value["logical_task"], "logical_task")
    write_domain = _require_id(value["write_domain"], "write_domain")
    target_branch = str(value["target_branch"])
    validate_routing(
        controller_id=controller_id,
        workspace=workspace,
        request_id=request_id,
        logical_task=logical_task,
        write_domain=write_domain,
        target_branch=target_branch,
    )
    expected_base_sha = _require_sha(value["expected_base_sha"], "expected_base_sha")
    patch_sha256 = _require_sha256(value["patch_sha256"], "patch_sha256")
    paths_sha256_value = _require_sha256(value["paths_sha256"], "paths_sha256")
    compressed_sha256 = _require_sha256(value["compressed_sha256"], "compressed_sha256")
    local_commit_sha = _require_sha(value["local_commit_sha"], "local_commit_sha")
    if value["patch_encoding"] != "gzip+base64":
        raise WorkGatewayError("patch_encoding must be gzip+base64")
    if not isinstance(value["patch_chunks"], int) or isinstance(value["patch_chunks"], bool):
        raise WorkGatewayError("patch_chunks must be an integer")
    if not 1 <= value["patch_chunks"] <= _MAX_CHUNKS:
        raise WorkGatewayError("patch_chunks is outside the protocol bound")
    if not isinstance(value["patch_bytes"], int) or isinstance(value["patch_bytes"], bool):
        raise WorkGatewayError("patch_bytes must be an integer")
    if not 1 <= value["patch_bytes"] <= _MAX_PATCH_BYTES:
        raise WorkGatewayError("patch_bytes is outside the protocol bound")
    if value["tests_status"] not in {"PASS", "PARTIAL", "NOT_RUN"}:
        raise WorkGatewayError("tests_status must be PASS, PARTIAL, or NOT_RUN")

    return HandoffManifest(
        schema_version="cep.work.handoff/v1",
        request_id=request_id,
        controller_id=controller_id,
        workspace=workspace,
        logical_task=logical_task,
        write_domain=write_domain,
        target_branch=target_branch,
        expected_base_sha=expected_base_sha,
        patch_encoding="gzip+base64",
        patch_sha256=patch_sha256,
        paths_sha256=paths_sha256_value,
        compressed_sha256=compressed_sha256,
        patch_chunks=value["patch_chunks"],
        patch_bytes=value["patch_bytes"],
        local_commit_sha=local_commit_sha,
        tests_status=value["tests_status"],
    )


def canonical_patch_paths(patch: str) -> tuple[str, ...]:
    paths: set[str] = set()
    saw_header = False
    for line in patch.splitlines():
        if not line.startswith("diff --git "):
            continue
        saw_header = True
        match = re.fullmatch(r"diff --git a/(\S+) b/(\S+)", line)
        if match is None:
            raise WorkGatewayError("quoted, spaced, or malformed diff paths are not publishable")
        for path in match.groups():
            if path.startswith("/") or path.startswith("../") or "/../" in path or "\\" in path:
                raise WorkGatewayError("patch contains an unsafe repository path")
            paths.add(path)
    if not saw_header or not paths:
        raise WorkGatewayError("handoff patch contains no canonical git diff paths")
    return tuple(sorted(paths))


def paths_sha256(paths: tuple[str, ...]) -> str:
    return sha256_text("".join(f"{path}\n" for path in paths))


def validate_patch_safety(patch: str, paths: tuple[str, ...]) -> None:
    patch_size = len(patch.encode("utf-8"))
    if patch_size <= 0 or patch_size > _MAX_PATCH_BYTES:
        raise WorkGatewayError("handoff patch is empty or exceeds the publication byte bound")
    if "GIT binary patch" in patch:
        raise WorkGatewayError("binary patches are not accepted by the Work gateway")
    if re.search(r"(?m)^(?:new file mode|old mode) 120000$", patch) or "160000" in patch:
        raise WorkGatewayError("symlink or gitlink/submodule mode changes are prohibited")
    for path in paths:
        if path in _FORBIDDEN_EXACT_PATHS or path.startswith(_FORBIDDEN_PATH_PREFIXES):
            raise WorkGatewayError(f"candidate publication path is reserved: {path}")
    for pattern in _HIGH_CONFIDENCE_SECRET_PATTERNS:
        if pattern.search(patch):
            raise WorkGatewayError("handoff patch contains a high-confidence secret/token pattern")


def _owned_comment(comment: dict[str, Any], repository_owner: str) -> bool:
    return (
        isinstance(comment, dict)
        and str((comment.get("user") or {}).get("login") or "") == repository_owner
        and str(comment.get("author_association") or "") == "OWNER"
    )


def _bounded_streaming_gunzip(compressed: bytes) -> bytes:
    if not compressed or len(compressed) > _MAX_COMPRESSED_BYTES:
        raise WorkGatewayError("compressed patch is empty or exceeds the compressed byte bound")
    try:
        with gzip.GzipFile(fileobj=io.BytesIO(compressed), mode="rb") as stream:
            raw = stream.read(_MAX_PATCH_BYTES + 1)
    except (OSError, EOFError) as exc:
        raise WorkGatewayError("compressed patch is not a valid gzip stream") from exc
    if len(raw) > _MAX_PATCH_BYTES:
        raise WorkGatewayError("decompressed patch exceeds the publication byte bound")
    return raw


def reconstruct_handoff(
    issue: dict[str, Any], comments: Iterable[dict[str, Any]], *, repository_owner: str,
) -> ReconstructedHandoff:
    if str((issue.get("user") or {}).get("login") or "") != repository_owner or str(issue.get("author_association") or "") != "OWNER":
        raise WorkGatewayError("handoff issue must be authored by the repository owner")
    if not str(issue.get("title") or "").startswith("[CEP-WORK-HANDOFF]"):
        raise WorkGatewayError("handoff issue title does not have the governed prefix")
    manifest = parse_handoff_manifest(str(issue.get("body") or ""))

    chunks: dict[int, str] = {}
    completions: list[re.Match[str]] = []
    for comment in comments:
        if not _owned_comment(comment, repository_owner):
            continue
        body = str(comment.get("body") or "")
        chunk_match = _CHUNK_RE.fullmatch(body)
        if chunk_match is not None and chunk_match.group("request") == manifest.request_id:
            index = int(chunk_match.group("index"))
            total = int(chunk_match.group("total"))
            if total != manifest.patch_chunks or not 1 <= index <= total:
                raise WorkGatewayError("patch chunk index/total does not match the manifest")
            payload = "".join(chunk_match.group("payload").split())
            if len(payload) > _MAX_CHUNK_TEXT:
                raise WorkGatewayError("patch chunk exceeds the comment payload bound")
            if index in chunks:
                raise WorkGatewayError("duplicate patch chunk index")
            chunks[index] = payload
            continue
        complete_match = _COMPLETE_RE.fullmatch(body)
        if complete_match is not None and complete_match.group("request") == manifest.request_id:
            completions.append(complete_match)

    if len(completions) != 1:
        raise WorkGatewayError("handoff requires exactly one owner-authored completion marker")
    completion = completions[0]
    if completion.group("patch") != manifest.patch_sha256 or completion.group("compressed") != manifest.compressed_sha256:
        raise WorkGatewayError("completion marker digest does not match the manifest")
    expected_indexes = set(range(1, manifest.patch_chunks + 1))
    if set(chunks) != expected_indexes:
        raise WorkGatewayError("handoff patch chunks are incomplete")

    encoded = "".join(chunks[index] for index in sorted(chunks))
    try:
        compressed = base64.b64decode(encoded, validate=True)
    except (ValueError, binascii.Error) as exc:
        raise WorkGatewayError("handoff patch base64 is invalid") from exc
    if sha256_bytes(compressed) != manifest.compressed_sha256:
        raise WorkGatewayError("compressed patch digest mismatch")
    raw = _bounded_streaming_gunzip(compressed)
    if len(raw) != manifest.patch_bytes:
        raise WorkGatewayError("decompressed patch byte length does not match the manifest")
    try:
        patch = raw.decode("utf-8")
    except UnicodeDecodeError as exc:
        raise WorkGatewayError("handoff patch must be strict UTF-8 text") from exc
    if sha256_bytes(raw) != manifest.patch_sha256:
        raise WorkGatewayError("handoff patch digest mismatch")

    paths = canonical_patch_paths(patch)
    validate_patch_safety(patch, paths)
    if paths_sha256(paths) != manifest.paths_sha256:
        raise WorkGatewayError("handoff changed-path identity does not match the manifest")
    return ReconstructedHandoff(manifest=manifest, patch=patch, changed_paths=paths)


def parse_publish_request(body: str) -> PublishRequest:
    value = _parse_json_object(body, label="publication issue body")
    _require_exact_keys(value, _PUBLISH_KEYS, label="publication request")
    if value["schema_version"] != "cep.work.publish/v1":
        raise WorkGatewayError("unsupported publication schema_version")
    request_id = _require_id(value["request_id"], "request_id")
    controller_id = str(value["controller_id"])
    workspace = str(value["workspace"])
    logical_task = _require_id(value["logical_task"], "logical_task")
    write_domain = _require_id(value["write_domain"], "write_domain")
    target_branch = str(value["target_branch"])
    validate_routing(
        controller_id=controller_id,
        workspace=workspace,
        request_id=request_id,
        logical_task=logical_task,
        write_domain=write_domain,
        target_branch=target_branch,
    )
    return PublishRequest(
        schema_version="cep.work.publish/v1",
        request_id=request_id,
        controller_id=controller_id,
        workspace=workspace,
        logical_task=logical_task,
        write_domain=write_domain,
        handoff_issue_number=_require_decimal_id(value["handoff_issue_number"], "handoff_issue_number"),
        intake_run_id=_require_decimal_id(value["intake_run_id"], "intake_run_id"),
        intake_head_sha=_require_sha(value["intake_head_sha"], "intake_head_sha"),
        target_branch=target_branch,
        expected_remote_sha=_require_sha(value["expected_remote_sha"], "expected_remote_sha"),
        expected_patch_sha256=_require_sha256(value["expected_patch_sha256"], "expected_patch_sha256"),
        expected_paths_sha256=_require_sha256(value["expected_paths_sha256"], "expected_paths_sha256"),
    )

# CEP Work Gateway v1 — GitHub Issue Handoff + Trusted Candidate Publisher

Status: **Controller infrastructure**. This gateway is a deterministic transport for an already-authorized Work writer. It does not grant product acceptance, merge, release, deploy, or final-acceptance authority.

## Why it exists

ChatGPT Work can perform `clone -> edit -> test -> local commit`, and the linked GitHub app has been capability-tested for Issue creation/comment/readback. A Work terminal may still lack Git credentials for `git push`. This gateway separates those concerns:

`Work writer -> owner-authored GitHub handoff issue -> immutable intake artifact -> Controller review -> Controller publication issue -> GitHub Actions apply/commit/non-force push -> remote SHA readback`

No Jules or Codex task is required merely to transport an already-produced Work patch.

## Current CEP topology

- `PARENT -> PARENT | W01 | W02 | W03 | W04 | W05`
- `A -> W01`
- `B -> W02`
- `C -> W03`
- `D -> W04`
- `E -> W05`

The Work gateway intentionally uses the current five-workspace topology. Existing Jules gateway compatibility is not changed by this package.

## Authority boundary

A Work task is a writer only when the Controller gives it an exact writer contract: objective, exact base SHA, target isolated branch, `writeScope`, prohibited scope, validation, evidence, dependencies, and Stop Gate.

Work may create only the handoff issue, patch-chunk comments, evidence comments, and the final completion marker for that authorized task. Work must **not** create `[CEP-WORK-PUBLISH]` issues, approve itself, mutate `main`, merge, release, deploy, or modify Gateway/governance paths.

The Controller reviews the frozen intake artifact and only then creates the exact publication issue. `PUSH != ACCEPTANCE != MERGE` remains true.

## Handoff issue protocol

Title:

```text
[CEP-WORK-HANDOFF] <request_id>
```

Body: one strict JSON object using schema `cep.work.handoff/v1` with exactly:

```json
{
  "schema_version": "cep.work.handoff/v1",
  "request_id": "REQ-001",
  "controller_id": "B",
  "workspace": "W02",
  "logical_task": "W02-EDITOR-01",
  "write_domain": "W02-EDITOR",
  "target_branch": "work/w02-editor-01",
  "expected_base_sha": "<40-hex>",
  "patch_encoding": "gzip+base64",
  "patch_sha256": "<64-hex>",
  "paths_sha256": "<64-hex>",
  "compressed_sha256": "<64-hex>",
  "patch_chunks": 1,
  "patch_bytes": 1234,
  "local_commit_sha": "<40-hex>",
  "tests_status": "PASS"
}
```

Generate the patch from the exact authorized base, not from an assumed branch:

```bash
git diff --no-ext-diff --full-index "$BASE_SHA" HEAD > reviewed.patch
python - <<'PY'
import gzip, pathlib
raw = pathlib.Path('reviewed.patch').read_bytes()
pathlib.Path('reviewed.patch.gz').write_bytes(gzip.compress(raw, compresslevel=9, mtime=0))
PY
base64 -w 0 reviewed.patch.gz > reviewed.patch.gz.b64
```

Split the base64 into chunks no larger than 50,000 characters. Each owner-authored comment must be exactly:

```text
CEP_WORK_PATCH_CHUNK request_id=<request_id> index=<N> total=<TOTAL>
<base64 chunk>
```

After every chunk is written and read back, add exactly one completion comment:

```text
CEP_WORK_HANDOFF_COMPLETE request_id=<request_id> patch_sha256=<sha256(raw patch)> compressed_sha256=<sha256(gzip bytes)>
```

The intake workflow reconstructs the bytes, validates gzip/base64/digests/current Controller topology/branch namespace/changed paths/reserved paths/secret patterns, then freezes `reviewed.patch`, `manifest.json`, and `intake-receipt.json` in a short-retention GitHub Actions artifact. Live Issue edits after intake do not alter that frozen artifact.

## Controller publication issue

After direct review, the Controller creates:

```text
[CEP-WORK-PUBLISH] <request_id>
```

with one strict JSON object using `cep.work.publish/v1`. It binds the exact handoff Issue, exact successful intake run/head SHA, exact current remote target-branch SHA, and exact patch/path digests. The issue bridge verifies provenance and dispatches the trusted publisher.

The publisher:

1. verifies the publication issue and frozen intake-run provenance;
2. downloads exactly one expected intake artifact;
3. re-verifies manifest, receipt, patch digest, path digest, and reserved-path rules;
4. verifies the target branch still equals the Controller-reviewed remote SHA;
5. `git apply --check` then `git apply --index`;
6. verifies staged changed paths and `git diff --check`;
7. creates one bounded commit;
8. performs a non-force push to the isolated branch;
9. reads the authoritative remote ref back and requires equality with the local commit SHA;
10. emits a machine-readable publication receipt.

It never merges, releases, deploys, or writes to `main`.

## Reserved paths

The external Work publisher rejects `.github/`, both gateway packages, `AGENTS.md`, secrets/env files, vendor dependencies, symlinks/submodules, binary patches, and high-confidence token/private-key patterns. Gateway/governance changes remain a separately controlled infrastructure workstream.

## Validation

```bash
python -m compileall -q tools/cep_work_gateway
python -m unittest discover -s tools/cep_work_gateway/tests -p 'test_*.py' -v
ruby -e 'require "yaml"; Dir[".github/workflows/cep-work-*.yml"].sort.each { |p| YAML.load_file(p, aliases: true); puts "YAML_OK=#{p}" }'
```

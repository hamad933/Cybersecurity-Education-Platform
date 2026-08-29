# CEP Jules Gateway v2 Foundation

This package is a **shadow-safe Controller infrastructure foundation**. It does not replace or disable the active v1 Jules workflows and it does not grant acceptance, merge, release, deployment, publication, or product-mutation authority.

## Public request envelope

The GitHub Actions transport accepts only a bounded, public-safe JSON control envelope. Secrets, credentials, private Drive content, service-account material, prompts/payloads, and free-form confidential data do not belong in the envelope.

Example read-only request:

```json
{
  "schema_version": "2.0",
  "request_id": "CEP-JULES-V2-INSPECT-001",
  "controller_id": "PARENT",
  "lane": "W05",
  "logical_task_id": "CEP-SYSOPS-DEEP-REREVIEW-CORR-01",
  "action": "inspect_bundle",
  "session_id": "9373382717583941738",
  "authority_event": "CEP-AUTH-EVENT-REFERENCE",
  "options": {
    "recent_agent_messages": 10,
    "recent_bash_outputs": 5,
    "include_patch": true,
    "include_bash_output_text": true
  }
}
```

Supported foundation actions are `inspect_bundle`, `get_session`, `list_sessions`, and `list_activities`. All are provider reads. Write-capable Jules actions are intentionally outside this foundation.

## Local invocation

```bash
CEP_JULES_REQUEST_JSON='{"schema_version":"2.0","request_id":"R1","controller_id":"PARENT","lane":"PARENT","action":"list_sessions"}' \
JULES_API_KEY='runtime-secret-only' \
python -m tools.cep_jules_gateway.cli --request-env CEP_JULES_REQUEST_JSON --output-dir /tmp/cep-jules-v2
```

The CLI reads the envelope and secret from environment variables so request content and secrets are not interpolated into shell commands. Output is sanitized and written as JSON. `receipt.json` is the compact machine-readable receipt; `result.json` carries the sanitized read result or `inspect_bundle` evidence.

## Safety and completeness

- Activity and session collections follow provider `nextPageToken` until exhausted.
- Pagination has a fail-closed page bound and raises `PAGINATION_LIMIT_EXCEEDED` instead of silently truncating.
- Provider 429, auth failures, 404, invalid-state responses, and generic read failures are classified separately.
- Retry headers are captured as metadata only. No blind automatic mutation retry exists.
- Exact patch/Bash evidence is sanitized before serialization and digesting.
- `inspect_bundle` reports activity pagination and hydration completeness; unavailable provider data is never fabricated.

When `starting_branch` and `expected_sha` are supplied, v2 performs a read-only GitHub branch-head precondition before the Jules read. The pair is atomic: supplying only one is rejected as ambiguous. `expected_state` and `expected_plan_digest` are likewise enforced fail-closed by `inspect_bundle` when present.

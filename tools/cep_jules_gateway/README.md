# CEP Jules Gateway v2 — Shadow Safety Layer

This package is **Controller infrastructure**, not product authority. v1 remains present and unchanged. v2 does not grant acceptance, merge, release, deployment, publication, cutover, or product-scope authority.

## Transport split

- `.github/workflows/cep-jules-v2.yml` remains the explicit **read-only shadow** transport using schema `2.0`.
- `.github/workflows/cep-jules-v2-mutation.yml` is a separate, manual `workflow_dispatch` **mutation canary** using schema `2.1`.
- The mutation canary does not subscribe to Issue traffic and does not replace the active v1 workflows.
- `execution_mode=MUTATION_CANARY` is mandatory for every v2.1 provider mutation.

A future cutover, if separately authorized, can follow:

`SHADOW -> READ PARITY -> MUTATION CANARY -> PARENT REVIEW -> DEFAULT SWITCH -> V1 FALLBACK RETENTION -> LATER RETIREMENT`

This candidate stops before `DEFAULT SWITCH`.

## Authority envelope

The structural controller/lane mapping is:

- `PARENT -> PARENT | W01_W02 | W03_W04 | W05`
- `A -> W03_W04`
- `B -> W01_W02`
- `C -> W05`

This mapping catches obviously inconsistent transport requests. It does **not** replace Drive authority. `authority_event` and `authority_ref` are bounded references, not self-authenticating grants.

Schema `2.0` keeps the Foundation read contract: `inspect_bundle`, `get_session`, `list_sessions`, and `list_activities`.

Schema `2.1` additionally permits explicit-canary `create_session`, `send_message`, and `approve_plan`. Mutation prompts/titles are bounded public-safe runtime inputs; they are excluded from `public_dict`, routing output, intent receipts, and mutation receipts. Secrets/private Drive material are prohibited in the request envelope.

## Effect concurrency

v2 uses two nested non-cancelling serialization scopes:

1. `request_id` lock: serializes duplicate transport invocations of the same logical request for the whole reusable worker lifetime.
2. mutation effect lock:
   - existing session: hash of exact `session_id`;
   - pre-session create: hash of `logical_task_id + write_domain + starting_branch`.

`cancel-in-progress` is always `false`.

Therefore two Controllers cannot concurrently mutate the same session through v2, while independent sessions retain different effect keys and remain parallel. There is no global lock.

For `create_session`, a durable create-effect intent marker is written inside the effect lock before the provider write. A later request with a different `request_id` but the same pre-session logical effect is blocked rather than creating a second Jules session blindly.

## Repository-wide idempotency

v2 uses bounded GitHub Actions artifacts as the smallest CEP-specific durable reconciliation store. Marker names contain a hash of `request_id`, not request content.

States:

- `NOT_SEEN`
- `INTENT_RECORDED`
- `COMPLETED`
- `UNKNOWN_WRITE_OUTCOME`
- `RECONCILIATION_REQUIRED`

Before any provider write, v2 publishes an `INTENT_RECORDED` artifact. A verified or deterministically rejected request receives a `COMPLETED` marker. An ambiguous write receives `UNKNOWN_WRITE_OUTCOME`. A later invocation that sees `INTENT_RECORDED` or `UNKNOWN_WRITE_OUTCOME` returns reconciliation-required and never replays the mutation blindly.

Durable idempotency/effect artifacts use 90-day retention; short diagnostic evidence uses 14 days. This intentionally bounds state growth. The resulting limitation is that request/effect deduplication is retention-window bounded, so very old identifiers must not be recycled.

## Exact plan approval binding

The Foundation `plan_digest` remains for display/backward-compatible read evidence. It is computed from sanitized normalized steps.

A separate `provider_identity_digest` is computed **in memory before sanitization** from canonical provider material containing:

- exact session identity;
- exact plan-generating activity identity;
- activity creation/update metadata;
- the complete provider `planGenerated` object.

Only the digest is emitted. Original provider material is never logged or persisted by the digest path.

`approve_plan` requires all of:

- `session_id`;
- `expected_state=AWAITING_PLAN_APPROVAL`;
- `expected_plan_digest` equal to the reviewed `provider_identity_digest`;
- `expected_plan_activity_name`;
- `expected_plan_create_time`;
- `expected_session_update_time`.

The worker performs a final direct session/activity read after durable intent, reconstructs the exact identity again, compares it with the reviewed values and recorded preflight, then calls `approvePlan` at most once. Any mismatch returns `PLAN_CHANGED_SINCE_REVIEW__REAPPROVAL_REQUIRED` or a deterministic state rejection with no provider write.

### Residual approval race

The observed Jules interface exposes session-scoped `approvePlan` but no atomic plan revision/precondition token that can be supplied with that mutation. Therefore a narrow TOCTOU remains between the final provider read and the `approvePlan` request. v2 does not claim to eliminate this provider-level race. It fails closed whenever the reviewed identity cannot be reconstructed or differs before the write.

## Provider protocol and deterministic selection

HTTP 2xx alone is not success evidence. Expected JSON reads fail closed when the response is non-JSON, has the wrong top-level type, omits a mandatory collection/session field, contains malformed items, or returns an activity identity outside the requested session.

Latest plan/changeSet selection is based on provider timestamp plus exact activity identity rather than list order. An ambiguous highest-timestamp tie for a single latest selection fails closed. Recent collections use deterministic timestamp/activity ordering and reject ambiguous cutoffs where implemented.

## Shared read and output budgets

`inspect_bundle` uses one request-scoped provider-read budget and one shared activity hydration pool. A hydrated activity, including a recorded hydration failure, is reused across changeSet and Bash evidence selectors.

Bounds include:

- total provider reads;
- total activity pages;
- total items;
- shared hydration reads;
- per-item exact-text characters;
- total exact-text bytes;
- total serialized result bytes.

Bounds never silently truncate exact text. If text cannot be included safely, the digest/length metadata is retained and an explicit omission/budget reason is emitted. Pagination with explicit total bounds returns `PARTIAL` plus continuation metadata when the provider token contract permits it.

## Mutation transition contract

Every mutation follows:

`AUTHORITATIVE PRE-READ -> VALIDATE -> DURABLE INTENT -> FINAL AUTHORITATIVE PRE-READ -> ONE WRITE -> AUTHORITATIVE POST-READ -> DURABLE FINAL STATE`

No provider mutation has an automatic retry. HTTP 429, transport ambiguity, 5xx, malformed successful mutation response, or inconclusive post-read is classified as `UNKNOWN_WRITE_OUTCOME`. The receipt includes `blind_retry=false` and the exact safe next read (`list_sessions` for create, `inspect_bundle` for session mutations).

## Rate limits and waiting

Safe provider retry metadata is preserved for reads. This candidate deliberately does **not** implement `wait_for_state`: avoiding a polling loop keeps the P0 mutation-safety work smaller and reduces rate-limit/429 exposure. Controllers can make bounded explicit reads until a separately reviewed wait primitive is justified.

## v1 coexistence limitation

v1 is byte-for-byte retained and therefore does not participate in the new v2 effect lock. During shadow/canary operation, the same Jules session must not be concurrently mutated through v1 and v2. Removing this limitation would require an explicitly reviewed v1/cutover change and is outside this candidate.

## Validation

The scoped test suite is synthetic and secret-free:

```bash
python -m compileall -q tools/cep_jules_gateway
python -m unittest discover -s tools/cep_jules_gateway/tests -p 'test_*.py' -v
```

It covers the Foundation tests plus malformed 2xx protocol responses, provider/session identity, shared budgets/cache, total result bounds, deterministic selection, concurrency keys, durable idempotency, plan binding, state drift, one-write semantics, ambiguous write outcomes, shadow compatibility, v1 blob identity, and shell/secret handling.

Hosted CI for this candidate is not live Jules acceptance evidence; the test workflow intentionally does not call Jules with a secret or perform a provider mutation.

# CEP Jules Gateway v2 — Controller Safety and Evidence Layer

This package is **Controller infrastructure**, not product authority. It does not grant acceptance, merge, release, deployment, publication, cutover, or product-scope authority.

## Transport split

- `.github/workflows/cep-jules-v2.yml` is the explicit **read-only shadow** transport using schema `2.0`.
- `.github/workflows/cep-jules-v2-mutation.yml` is the separate, manual `workflow_dispatch` **mutation canary**. Its current public transport is v2.2 reference-only metadata; private task bodies stay behind governed references.
- `.github/workflows/cep-jules-v2-publication.yml` plus the v2.3 issue bridge are the trusted candidate-publication transport already present on `main`.
- Mutation transports do not subscribe to arbitrary Issue traffic and do not replace product acceptance authority.
- Reusable mutation workers receive only explicitly required secrets; callers do not use `secrets: inherit`.

A future default-transport cutover, if separately authorized, still follows a governed sequence rather than being implied by code availability.

## Current Controller / lane topology

All packaged Gateway contracts now share one current structural mapping:

- `PARENT -> PARENT | W01 | W02 | W03 | W04 | W05 | W01_W02 | W03_W04`
- `A -> W01`
- `B -> W02`
- `C -> W03`
- `D -> W04`
- `E -> W05`

`W01_W02` and `W03_W04` are retained only as Parent legacy aggregate fallbacks. Child Controllers cannot use those legacy aggregate routes.

This mapping catches inconsistent transport requests. It does **not** replace Drive authority. `authority_event` and `authority_ref` remain bounded references, not self-authenticating grants.

Schema `2.0` keeps the read contract: `inspect_bundle`, `get_session`, `list_sessions`, and `list_activities`.

Mutation schemas permit bounded `create_session`, `send_message`, and `approve_plan` only through explicit mutation transports. Secrets/private Drive material are prohibited in public envelopes.

## Provider collection robustness

A successful HTTP response is not sufficient evidence by itself. Expected JSON reads fail closed on wrong top-level types, malformed items, cross-session activity identities, invalid continuation tokens, repeated pagination tokens, or ambiguous collection shapes.

For Jules activities specifically:

- a successful response that omits `activities` is accepted as an empty **terminal** page only when no `nextPageToken` exists;
- omitting `activities` while advertising continuation fails closed;
- a non-array `activities` field fails closed;
- activity identities must remain inside the requested session;
- the total page/item/provider-read budgets remain enforced.

This closes the observed long-session failure mode where a quiescent session with hundreds of activities could be enumerated by one reader but rejected by another at a terminal collection boundary.

### Response-size adaptation

The shared HTTP transport keeps its 8 MiB provider-response bound. `list_activities` may adaptively reduce `pageSize` only when a **read-only GET** fails specifically with `PROVIDER_RESPONSE_TOO_LARGE`. The fallback is bounded and never converts into a mutation retry.

All provider mutations still follow the one-write rule. HTTP 429, transport ambiguity, 5xx, malformed successful mutation responses, or inconclusive post-reads remain `UNKNOWN_WRITE_OUTCOME`; blind retry stays prohibited.

### Incremental activity-read primitives

`list_activities` supports two optional bounded read primitives:

- an opaque `start_page_token` for continuation inside the provider pagination contract;
- a `create_time` filter passed as Jules `createTime` for bounded delta-style scans.

The applied filter and requested/effective page sizes are emitted in pagination metadata. These primitives do **not** claim that a provider page token is a durable long-term checkpoint, and no persistent cache/cutover guarantee is inferred from them.

## Media evidence

Jules activities may contain `media` artifacts in addition to changeSets and Bash output. The inspect bundle no longer silently ignores them.

Media handling is bounded and evidence-oriented:

- `mimeType` is structurally validated;
- `data` is strict base64-decoded;
- per-item decoded size is limited to 5 MiB;
- total decoded media is limited to 20 MiB;
- at most 50 media artifacts are admitted per inspect bundle;
- decoded bytes are SHA-256 hashed;
- result JSON contains only metadata (`activity_name`, `artifact_index`, MIME type, decoded size, digest, output file), never raw base64;
- when the CLI has an output directory, decoded media is externalized under `media/*.bin` so Actions artifact upload can preserve it without Issue/log inflation.

Malformed or over-budget media is explicit `PARTIAL` evidence with a reason; it is never silently discarded.

## Effect concurrency

Mutation execution uses two nested non-cancelling serialization scopes:

1. `request_id` lock: serializes duplicate transport invocations of the same logical request for the worker lifetime.
2. mutation effect lock:
   - existing session: hash of exact `session_id`;
   - pre-session create: hash of `logical_task_id + write_domain + starting_branch`.

`cancel-in-progress` is always `false`.

Therefore two Controllers cannot concurrently mutate the same session through the same governed v2 transport, while independent sessions retain different effect keys and can remain parallel. There is no global mutation lock.

For `create_session`, a durable create-effect intent marker is written inside the effect lock before the provider write. A later request with a different `request_id` but the same pre-session logical effect is blocked rather than creating a second Jules session blindly.

For existing sessions, the first governed mutation persists a session-binding marker plus a binding-specific marker derived from `logical_task_id + write_domain`. Later mutations of that session must reproduce the same binding. A mismatched task/domain or partially persisted binding fails closed with `RECONCILIATION_REQUIRED`.

## Repository-wide idempotency

Bounded GitHub Actions artifacts are used as the smallest CEP-specific durable reconciliation store. Marker names contain hashes/identities rather than private request bodies.

States include:

- `NOT_SEEN`
- `INTENT_RECORDED`
- `COMPLETED`
- `UNKNOWN_WRITE_OUTCOME`
- `RECONCILIATION_REQUIRED`

Before a provider write, the mutation transport persists intent. A verified or deterministically rejected request receives terminal state; an ambiguous write receives `UNKNOWN_WRITE_OUTCOME`. Later invocations reconcile rather than replay blindly.

Durable idempotency/effect/session-binding artifacts are retention-window bounded. Old request/effect identifiers therefore must not be recycled as if state were permanent.

## Exact plan approval binding

The Foundation `plan_digest` remains display/backward-compatible read evidence. A separate `provider_identity_digest` is computed from canonical provider identity material before sanitization and includes the exact session, plan activity identity, provider plan object and stable plan ID when available.

`approve_plan` requires exact reviewed plan/session identities. Immediately before the single POST, the worker reconstructs the provider identity again and fails closed on drift. After approval, `VERIFIED` requires a newly observed `planApproved` activity whose `planId` exactly matches the reviewed plan.

The Jules API still exposes session-scoped `approvePlan` without an atomic plan-revision conditional token, so the narrow provider-level TOCTOU is documented rather than hidden.

## Shared read and output budgets

`inspect_bundle` uses one request-scoped provider-read budget and one shared activity hydration pool. A hydrated activity, including a recorded hydration failure, is reused across changeSet and Bash selectors.

Bounds cover:

- total provider reads;
- total activity pages and items;
- shared hydration reads;
- per-item and total exact-text bytes;
- total serialized JSON bytes;
- media item count, per-item bytes, and total decoded media bytes.

Bounds never silently truncate exact text or silently drop media. Exact text that cannot be emitted keeps digest/length metadata plus an omission reason. Media that cannot be admitted produces explicit partial evidence.

## Mutation transition contract

Every governed mutation follows:

`AUTHORITATIVE PRE-READ -> VALIDATE -> DURABLE INTENT -> FINAL AUTHORITATIVE PRE-READ -> ONE WRITE -> AUTHORITATIVE POST-READ -> DURABLE FINAL STATE`

No provider mutation has an automatic retry.

`send_message` is fail-closed to known compatible session states plus the caller's exact `expected_state`. Unknown and terminal states are rejected before the provider write.

## Performance and parallelism

- long activity histories use response-safe page sizing plus bounded read-only adaptive fallback;
- callers can opt into `createTime` delta-style activity scans instead of always rescanning immutable history;
- `inspect_bundle` shares one hydration cache for patch/Bash evidence;
- media bytes are externalized instead of inflating result JSON or Issue comments;
- request serialization affects only the same request identity;
- effect serialization affects only the same session/pre-session write effect;
- independent sessions and unrelated logical effects remain parallel across Controllers A–E;
- unknown mutation outcomes convert to reconciliation reads rather than retry churn.

There is no global busy-poll loop.

## v1 coexistence limitation

Legacy transports do not automatically participate in every newer v2 effect lock. During coexistence, the same Jules session must not be concurrently mutated through independent legacy and v2 write paths. Removing that limitation requires an explicitly reviewed cutover, not an implicit code assumption.

## Validation

The scoped CI is synthetic and secret-free:

```bash
ruby -e 'require "yaml"; Dir[".github/workflows/cep-jules-v2*.yml"].sort.each { |path| YAML.load_file(path, aliases: true); puts "YAML_OK=#{path}" }'
python -m compileall -q tools/cep_jules_gateway
python -m unittest discover -s tools/cep_jules_gateway/tests -p 'test_*.py' -v
```

Coverage includes malformed 2xx protocol responses, long-session/terminal-page activity pagination, provider response-size fallback, pagination loops/bounds, provider/session identity, W04-shaped publication preflight through the real `JulesClient`, current A–E topology convergence, shared budgets/cache, exact result bounds, request/effect concurrency, durable idempotency, session binding, exact plan approval binding, one-write semantics, ambiguous outcomes, v2.2 reconciliation, media extraction/digests/bounds, and secret handling.

Hosted CI is not live Jules mutation acceptance evidence. The test workflow intentionally records when no live provider mutation was executed.

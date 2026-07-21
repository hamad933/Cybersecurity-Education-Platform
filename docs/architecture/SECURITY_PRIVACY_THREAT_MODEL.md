# Security and Privacy Threat Model

## Assets and boundaries

Protected assets include private originals, source authorization, canonical knowledge, owner credentials/session, enterprise/scenario definitions, run/evidence history, publication/audit history, Manual AI packages, backups, and secrets. Boundaries are browser/session, untrusted import, safe renderer, Manual AI export/import, simulation engine, database/blob binding, local network exposure, queue worker, and backup/restore.

| Threat | Required mitigation | Acceptance evidence |
|---|---|---|
| Malicious/untrusted file | quarantine; no execution/macros; size/type parsers allowlist; least-privilege processing | crafted-file tests and process policy |
| Path traversal / archive links | normalize canonical relative paths; reject absolute, parent, device, ADS, symlink entries | traversal archive corpus |
| Archive bomb / large source DoS | compressed/uncompressed count, ratio, depth, per-file and aggregate limits; timeout/cancel | limit tests and processing-run failure |
| MIME confusion | compare declared type, signature, extension, parser result; quarantine mismatch | mismatch tests |
| Stored XSS / unsafe Markdown/HTML | structured renderers, allowlist sanitizer, context encoding, CSP; raw HTML off | XSS payload component/e2e tests |
| Command/code display executes | display-only block contract; no shell/process service; copy only | architecture scan and click tests |
| Unsafe preview | sandboxed/converted preview; download explicit; no active content | preview isolation tests |
| Source leakage | local default, module authorization, redacted logs, explicit export scope | access/export tests |
| Manual AI over-export | itemized preview, sensitivity warnings, allowlisted selection, manifest/audit | scope-diff test |
| Tampered AI/package import | quarantine, SHA-256 manifest, schema/source-reference validation | tamper tests |
| Provenance forgery | resolve cited ID, revision, digest, anchor; reject mismatch | forged citation tests |
| Authorization bypass | server-side policy on every mutation; do not trust UI/simulated role | negative authorization tests |
| Scenario/real confusion | no real connector; visible simulated label; origin enum | UI and schema tests |
| Evidence tampering | immutable digest, origin, producer/run links, append-only decisions | mutation/tamper tests |
| Revision/history loss | immutable publications, optimistic concurrency, backup/restore | revision and restore tests |
| Sensitive logs | structured allowlist, secret/PII redaction, no source bodies/tokens | log inspection tests |
| Secrets exposure | environment/secret store boundary, never packages/audit/backups by default | secret scan |
| Dependency risk | locked supported versions, vulnerability review, minimal packages | SBOM/lock review in Task 006+ |
| Backup exposure | explicit destination, restrictive permissions/encryption decision, no secrets, manifest | backup access/tamper tests |
| Local service exposure | loopback binding default, authenticated owner, fail closed | socket/bind and auth tests |
| CSRF/session theft/fixation | SameSite/HttpOnly/Secure where applicable, rotation, CSRF token, expiry, logout | framework security tests |
| Password attack | modern adaptive hash, rate limit/backoff, no plaintext/logging | hash and rate tests |
| Queue replay/duplicate | idempotency key, attempt limits, lease/heartbeat, terminal failure state | duplicate/retry tests |
| Database corruption | constraints, transactions, checksums for blobs, backup/restore validation | injected-failure/recovery tests |
| Audit gaps/tampering | append-only owner, atomic transaction/outbox, sequence/correlation checks | coverage and gap detector |

## Privacy rules

Collect only data needed for the local owner's learning and audit. No telemetry or external transfer is implicit. Export, deletion, retention, backup location, and optional remote access require visible owner decisions. Evidence and logs use synthetic representative data by default. A later multi-user mode requires a new access/privacy decision and threat-model revision.

## Residual risk

Local ownership does not remove malware, browser, dependency, backup, or physical-device risk. Task 004 provides controls, not assurance. Parser choices, CSP, cryptographic storage, remote access, and retention values are finalized and tested in implementation phases.


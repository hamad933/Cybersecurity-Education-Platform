# VS-002 Web/API Authority Baseline

Baseline ID: `WEB-API-AUTHORITY-2026-07-22-V1`
Access date: `2026-07-22`
Status: **CURRENT OFFICIAL AUTHORITIES VERIFIED — BOUNDED REVIEW CANDIDATE**

## Governing authorities

1. IETF RFC 9110, *HTTP Semantics*, Internet Standard, June 2022: request method and target-resource semantics, response status classes, authentication framework, and bounded use of 401/403/404/405. This does not define application object ownership policy.
2. OWASP API Security Top 10 2023, *API1:2023 Broken Object Level Authorization*: an endpoint receiving an object ID must check that the authenticated subject can perform the action on that object. OWASP is security guidance, not an HTTP protocol specification.
3. OWASP Authorization Cheat Sheet, current official project guidance accessed 2026-07-22: deny by default, validate permission on every request, and prefer relationship/attribute-aware decisions. It does not authorize any live exploitation.
4. Laravel 13.x official Authentication, Authorization, and Validation documentation: authentication establishes the request user; authorization remains a separate action/resource decision; bounded request validation rejects invalid inputs. VS-002 models this behavior inside a synthetic simulator and does not expose a public API.
5. Vue 3 official Security guidance: interpolated text is escaped; untrusted templates and untrusted `v-html` are unsafe; URLs require server-side scheme validation before storage. VS-002 renders typed text/code/request/response/log blocks through interpolation only.
6. WHATWG Fetch Living Standard: origin is a defined request context concept. VS-002 records only a synthetic normalized origin label and makes no CORS, cookie, credential, or cross-origin enforcement claim.

## Implemented semantic boundary

The synthetic endpoint is `GET /api/case-files/{caseFileId}`. The simulator separates route normalization, server-side session authentication, resource lookup, subject/action/resource authorization, bounded serialization, and finding emission. Client-supplied role or owner fields are recorded as ignored and never become server authorization inputs.

Outcomes are `ALLOW`, `DENY`, `UNAUTHENTICATED`, `NOT_FOUND`, `INSUFFICIENT_STATE`, and `UNSUPPORTED_STATE`. The response status is a bounded teaching mapping: 200, 401, 403, 404, 405, or 422 as documented in the rule catalog. Real middleware order, proxy behavior, cookie attributes, CORS, CSP, OAuth/OIDC, tokens, production error bodies, and browser engine edge cases are excluded.

## Internal reviewed support

`CAP-D05-02-02` and `KU-D05-004` retain the reviewed academic support IDs `SE-003-154..158`, `SE-003-164..178`, and `SE-003-184..188` from the five selected SAD lecture paths. They are `ACADEMIC_SUPPORT`, not current primary authority. The refined catalog explicitly says current standards and framework behavior require external primary verification; this baseline supplies that verification for only the claims listed in `planning/task008/VS002_AUTHORITY_CLAIMS.tsv`.

## Publication rule

The KU-D05-004 lesson may publish only when every `WEB-AUTH-001..007` claim is present, the exact baseline ID is bound, review is explicitly approved, and the typed-block validator passes. Absence of any claim or baseline blocks publication.

# Security policy

Report suspected vulnerabilities privately to the designated repository owner or through a GitHub repository security advisory when that channel is available and explicitly authorized. Do not place credentials, session material, private source content, database dumps, or usable exploit details in an issue or pull-request discussion.

GitHub Private Vulnerability Reporting for external researchers is not treated as an applicable setting while this repository remains private. If repository visibility is later changed to public through a separately authorized decision, enabling Private Vulnerability Reporting may then be evaluated and recorded as a separate settings change.

## Current boundary

The bounded V1 runtime is a private, local-first, single-owner learning and simulation platform. It includes authenticated VS-001, VS-002, VS-003, the Release Center, safe import/package controls, database queue processing, tamper-evident audit chaining, and backup/restore verification. Simulated evidence remains visibly `SIMULATED` and does not authorize real-system claims.

There is no public registration, multi-tenant SaaS mode, production connector, live target execution, automatic AI provider, production deployment, or Google Drive integration. A future phase must not infer authorization for those capabilities from this repository.

## CI handling

GitHub Actions uses synthetic temporary credentials and isolated PostgreSQL databases. Workflows do not use `pull_request_target`, do not expose repository secrets to untrusted pull-request code, and do not deploy. Evidence artifacts are sanitized and rejected when they contain forbidden secret-bearing paths or patterns.

Run the repository security gates through `Core CI / Repository secret scan`, Composer audit, npm audit, the fallback scanner, package/import tests, architecture tests, and release/browser security-header evidence. Rotate and revoke any value accidentally disclosed, remove it from Git history through an explicitly authorized incident procedure, and preserve the incident truthfully.

## Reporting and response

- Use direct private owner disclosure or an authorized repository security advisory.
- Never disclose a vulnerability through a public issue or pull-request discussion.
- Do not include live credentials, session cookies, private source materials, database contents, or weaponized exploit details in ordinary repository communications.
- Preserve enough sanitized evidence to reproduce and assess the issue.
- Record containment, revocation, remediation, and verification actions truthfully.

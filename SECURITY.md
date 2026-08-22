# Security policy

Report suspected vulnerabilities privately to the designated repository owner or through a GitHub repository security advisory when that channel is available and explicitly authorized. Do not place credentials, session material, private source content, database dumps, or usable exploit details in an issue or pull-request discussion.

This repository is public. GitHub Private Vulnerability Reporting is a separate repository setting and must not be claimed as enabled unless directly verified in GitHub. Public source visibility does not make public issues or pull-request discussions an acceptable vulnerability-disclosure channel.

## Current boundary

The bounded V1 runtime is local-first and single-owner. Public repository visibility does not authorize public product registration, SaaS, multi-tenancy, public or cloud deployment, external simulation execution, automatic AI providers, production security connectors, or any other runtime expansion. Simulated evidence remains visibly `SIMULATED` and does not authorize real-system claims.

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

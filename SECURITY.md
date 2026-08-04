# Security policy

Report suspected vulnerabilities privately through GitHub private vulnerability reporting or directly to the designated repository owner. Do not place credentials, session material, private source content, database dumps, or usable exploit details in an issue or pull-request discussion.

## Current boundary

The bounded V1 runtime is a private, local-first, single-owner learning and simulation platform. It includes authenticated VS-001, VS-002, VS-003, the Release Center, safe import/package controls, database queue processing, tamper-evident audit chaining, and backup/restore verification. Simulated evidence remains visibly `SIMULATED` and does not authorize real-system claims.

There is no public registration, multi-tenant SaaS mode, production connector, live target execution, automatic AI provider, production deployment, or Google Drive integration. A future phase must not infer authorization for those capabilities from this repository.

## CI handling

GitHub Actions uses synthetic temporary credentials and isolated PostgreSQL databases. Workflows do not use `pull_request_target`, do not expose repository secrets to untrusted pull-request code, and do not deploy. Evidence artifacts are sanitized and rejected when they contain forbidden secret-bearing paths or patterns.

Run the repository security gates through `Core CI / Repository secret scan`, Composer audit, npm audit, the fallback scanner, package/import tests, architecture tests, and release/browser security-header evidence. Rotate and revoke any value accidentally disclosed, remove it from Git history through an explicitly authorized incident procedure, and preserve the incident truthfully.

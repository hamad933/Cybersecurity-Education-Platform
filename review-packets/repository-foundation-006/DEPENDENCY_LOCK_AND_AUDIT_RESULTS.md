# Dependency lock and audit results

- `composer.json` plus `composer.lock`: present and strict-valid; framework 13.21.1 and direct tools exactly locked.
- `package.json` plus `package-lock.json`: present; npm 11 lock; no alternate JavaScript lockfile.
- `composer audit --locked`: PASS, no advisories.
- `npm audit --audit-level=high`: PASS, zero vulnerabilities.
- npm peer resolution: TypeScript 7.0.2 was rejected as incompatible; stable compatible 6.0.3 selected without force or legacy-peer override.
- Moderate/low findings: none from audit.
- Deprecation: transitive `glob@10.5.0` from `@vue/test-utils -> js-beautify`; no advisory, tracked for a future compatible upstream refresh.
- No Redis, Kafka, broker, GraphQL, WebSocket, automated AI, real connector, browser package, or module framework dependency was added.

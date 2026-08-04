# CEP-GH-001-R1A closure register

Scope: deterministic Core CI remediation on draft PR #1 only.

Starting evidence:

- Head: `0458abb5d01963d7313d57661ab1bf01fd0b0373`
- Protected `main`: `f257283de4a83054312d979d462c5de1d848bcb0`
- Core CI run: `30870148595`
- Gitleaks `8.28.0` stopped with `unknown flag: --source`; this was scanner execution failure, not a secret finding.
- Unit: 44 tests passed. Feature and Integration discovered tests but failed where Laravel required `public/build/manifest.json`.
- Architecture failures referenced pre-migration review packets, an external/root `AGENTS.md`, and Git metadata unavailable to the PHP checkout.

## Bounded corrections

| Finding | Original invariant | Obsolete assumption or defect | Equivalent or stronger invariant implemented |
|---|---|---|---|
| R1 | Scan the complete repository history and fail truthfully on secrets or scanner failure. | `gitleaks git --source=/repo` is unsupported by pinned Gitleaks `8.28.0`. | Execute `gitleaks git .` from `/repo`, retain full history and 100% redaction, record version/help/command/exit codes, distinguish findings from execution failure, and fail both cases. |
| R2 | Lint CI scripts against their real runtime APIs. | The flat ESLint configuration supplied neither Node nor web-compatible globals to the exact browser-evidence CI script. | Add read-only `process`, `Buffer`, and `setTimeout` plus genuine `fetch` and `WebSocket` globals only for `scripts/ci/browser_evidence.mjs`; allow only intentional empty catch blocks in that exact file. |
| R3 | Every named PHP suite must execute real tests and manifest-dependent HTTP tests must receive production assets. | CI used Laravel's Artisan wrapper, lacked a separately named repository-safety suite, and ran Feature/Integration without Vite output. | Define Unit, Feature, Integration, Architecture, and Repository Safety suites; run pinned `vendor/bin/phpunit` with `--fail-on-empty-test-suite`; capture JUnit counts and durations; build and transfer CI-only `public/build` before PHP tests. |
| R5 | Preserve source safety, canonical evidence boundaries, and forbidden-runtime checks. | Assertions depended on deleted Task 004/006 packet paths, a parent `AGENTS.md`, a pre-migration package script, and a checkout without `.git`. | Validate current governance/evidence documents, live workflows, root lockfiles, tracked-file exclusions, current handoff path policy, absence of recreated obsolete archives, and real Git inventory after a full-history checkout. |

## Files changed

- `.github/workflows/core-ci.yml`
- `eslint.config.js`
- `phpunit.xml`
- `composer.json`
- `scripts/ci/core_php.sh`
- `tests/Architecture/FoundationCorrectionsTest.php`
- `tests/Architecture/RepositorySafetyTest.php`
- `docs/development/CEP_GH_001_R1A_CLOSURE_REGISTER.md`

## Boundaries preserved

- R4 dependency advisories remain out of scope and are not suppressed.
- R6, R7, and R8 release/browser behavior is unchanged.
- No product test is deleted or disabled.
- Generated frontend output remains an ephemeral GitHub Actions artifact and is not committed.
- No local-device evidence is authorized or used.

Closure requires fresh GitHub-hosted execution evidence for all four findings.

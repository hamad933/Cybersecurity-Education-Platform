# TASK-006 Foundation Corrections

Date: 2026-07-22  
Scope: mandatory pre-VS-001 gate

## FND-007-001

- finding_id: `FND-007-001`
- original_behavior: Composer dependencies were installed before application source with scripts disabled; the runtime installed only `pdo_pgsql`; host-generated files could enter the build context.
- risk: incomplete authoritative autoload, skipped package discovery, platform mismatch, and non-reproducible runtime state.
- files_changed: `Dockerfile`, `.dockerignore`, `tests/Architecture/FoundationCorrectionsTest.php`.
- tests_added: source-before-autoload ordering, script/extension/static-context assertions; Compose 10/10 structural validator; clean production staging install.
- result: `PASS_WITH_RUNTIME_LIMITATION`. Production install after full source, optimized autoload, Composer scripts, package discovery, `composer check-platform-reqs --no-dev`, config cache, and native PostgreSQL 18.4 all passed. Docker executable was absent, so the exact status is `DOCKER_RUNTIME_UNAVAILABLE`.
- residual_limitation: image build/start/restart/history inspection remains a release limitation until a Docker-capable host runs it.

## FND-007-002

- finding_id: `FND-007-002`
- original_behavior: login accepted `remember` although `owner_accounts` had no `remember_token`.
- risk: unsupported persistent-login contract and an untested schema write path.
- files_changed: `AuthenticatedSessionController.php`, `resources/js/pages/Auth/Login.vue`, login and architecture tests.
- tests_added: source/UI absence assertions plus the existing successful login regression.
- result: `PASS`; request validation, `Auth::attempt`, UI copy/control, and behavior no longer expose remember-me.
- residual_limitation: none; persistent login is intentionally outside the current scope.

## FND-007-003

- finding_id: `FND-007-003`
- original_behavior: every dashboard health refresh wrote a durable orphan blob.
- risk: hidden persistent growth caused by a read page.
- files_changed: `FoundationHealth.php`, `DashboardController.php`, `BlobStore.php`, `LocalBlobStore.php`.
- tests_added: dashboard storage count is unchanged; diagnostic write/read/hash/delete leaves count unchanged; controlled read failure still invokes delete.
- result: `PASS`; dashboard checks are read-only and the detailed probe cleans in `finally`.
- residual_limitation: the detailed CLI probe intentionally performs a temporary write and reports failure if cleanup cannot be confirmed.

## FND-007-004

- finding_id: `FND-007-004`
- original_behavior: `DiagnoseCommand` called `Schema::hasTable` outside a failure boundary.
- risk: an uncaught database exception and misleading/nonzero behavior during an outage.
- files_changed: `FoundationHealth.php`, `DiagnoseCommand.php`, `PlatformPrimitivesTest.php`.
- tests_added: available DB, configuration failure, unavailable DB, missing-table, audit-skip, and `--no-audit` paths.
- result: `PASS`; failure is categorized, safe, nonzero, and never attempts audit when the connection is unavailable.
- residual_limitation: output deliberately omits DSNs, paths, and credentials, so operators must use local configuration to investigate.

## FND-007-005

- finding_id: `FND-007-005`
- original_behavior: `migrate:fresh` depended on local PHPUnit defaults without a hard safety guard.
- risk: destructive migration against a non-test database.
- files_changed: `tests/Support/DestructiveDatabaseGuard.php`, `tests/TestCase.php`, `MigrationLifecycleTest.php`, `phpunit.xml`, development documentation.
- tests_added: allowed PostgreSQL test context plus hard failures for wrong environment, connection, database suffix, and host.
- result: `PASS`; the guard executes before Laravel database test traits and again immediately before explicit `migrate:fresh`.
- residual_limitation: allow-listed connections/hosts are operator-injectable environment configuration and must stay narrow.

## FND-007-006

- finding_id: `FND-007-006`
- original_behavior: the test manually called `handle()` and checked retry properties without running a worker.
- risk: unsupported claims about worker attempts and terminal failure.
- files_changed: `tests/Fixtures/QueueLifecycleProbeJob.php`, `PlatformPrimitivesTest.php`.
- tests_added: database dispatch, real `queue:work --once`, retry then success, three-attempt terminal failure, `failed_jobs`, and ProcessingRun correlation.
- result: `PASS`; both real retry-success and terminal-failure lifecycles passed on PostgreSQL 18.4.
- residual_limitation: evidence covers the database queue on the local single-owner topology, not an external broker.

## FND-007-007

- finding_id: `FND-007-007`
- original_behavior: the Task-006 packager selected generated caches, views, blobs, logs, and frontend output.
- risk: secrets/private runtime data and non-reproducible handoffs.
- files_changed: `scripts/package_task006_handoff.php`, `scripts/Support/HandoffPathPolicy.php`, packaging policy tests.
- tests_added: automated prohibited/safe path matrix covering environment files, Git, dependencies, build output, caches, private blobs, sessions, logs, volumes, profiles, historical ZIPs, traversal, and safe placeholders.
- result: `PASS`; the reusable policy is enforced during selection and final ZIP member verification. The final Task-007 packager will additionally compare the complete folder/ZIP member set and hashes.
- residual_limitation: historical Task-006 handoff content was not regenerated or rewritten.

## FND-007-008

- finding_id: `FND-007-008`
- original_behavior: historical Task-006 `PRIOR_OUTPUT_SAFETY.md` contained a differing Task-004 SHA-sums hash.
- risk: ambiguous provenance if the transcription is treated as artifact authority.
- files_changed: new external-review and correction records only; historical packets were not changed.
- tests_added: direct `hash_file` assertion against the immutable Task-004 artifact.
- result: `PASS`; 14,415-byte artifact hash is `896E800B2810EBB789E875B3A227C0B402DBB12B2218D2EA8DCA386E41925108`.
- residual_limitation: the historical typo remains visible by design and is superseded by these records.

## FND-007-009

- finding_id: `FND-007-009`
- original_behavior: historical screenshots predated the final width fix and mixed-direction closure.
- risk: responsive clipping and evidence that did not match final source.
- files_changed: login layout, CSS, Vitest configuration/tests, screenshot automation, and six replacement images.
- tests_added: no `overflow-x: hidden` masking, exact screenshot dimensions, 1440/1024/390 overflow checks, 512px 200% reflow simulation, focus and Bidi visual review.
- result: `PASS`; six exact `foundation-*.png` images were captured from the live app and visually inspected, with `HORIZONTAL_OVERFLOW=0` and `REFLOW_200_PERCENT=PASS`.
- residual_limitation: the in-app Browser connector was unavailable due an internal asset-path error; local Chrome headless capture completed successfully and left no browser profile in the repository or temp prefix.

## Historical preservation

No file under `review-packets/repository-foundation-006/**`, `review-packets/TASK_004_REVIEW_HANDOFF/**`, or `source-vault/originals/**` was edited. Repository-safety rehash tests passed.

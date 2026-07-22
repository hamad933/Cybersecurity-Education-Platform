# Residual limitations

1. Docker/WSL were unavailable. Compose/Dockerfile validation is structural only; no image build, pull, container health, migration, volume, or shutdown evidence exists.
2. The deterministic fallback secret scanner is intentionally narrower than gitleaks. It passed worktree and Git-history patterns, but a pinned full-engine scan remains required before release when available.
3. Mobile browser evidence exposed overflow, then source was corrected after the browser session was finalized. Static/component gates cover the correction; final browser screenshots must be recaptured before treating responsive rendering as closed.
4. Audit append-only behavior is enforced by application model paths and permissions assumptions, not database triggers or cryptographic linkage. No tamper-proof claim is made; retention and integrity policy remain unapproved.
5. Local PHP development server and ephemeral trust-authenticated loopback PostgreSQL were verification fallbacks only, not deployment baselines.
6. The transitive deprecated `glob@10.5.0` under Vue Test Utils/js-beautify has no current audit advisory; monitor the upstream chain.
7. Task 007 and VS-001 are unstarted by design. All product workflows beyond the bounded foundation are absent.

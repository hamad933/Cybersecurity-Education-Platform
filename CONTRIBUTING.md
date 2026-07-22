# Contributing

Respect the root `AGENTS.md`, the active phase prompt, and all stop gates. Keep changes inside the practical modular monolith, update the machine-readable dependency registry when an approved module boundary changes, and add tests before opening a review candidate.

Before review run `composer quality`. Never commit `.env`, credentials, runtime data, source-vault material, vendor dependencies, or generated handoffs. Database-dependent behavior must be verified on PostgreSQL, not inferred from SQLite.

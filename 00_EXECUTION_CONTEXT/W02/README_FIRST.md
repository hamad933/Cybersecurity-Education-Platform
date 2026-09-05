# W02 Google AI Studio Execution Context — ALL SURFACES

Status: `NON_CANONICAL_TRANSPORT_CONTEXT__ALL_W02_SURFACES__R01`
Generated: `2026-09-05`
Repository: `hamad933/Cybersecurity-Education-Platform`
Context branch target: `context/cep-w02-aistudio-all-surfaces-r01`
Frozen product baseline used to anchor this context: `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`

## Authority boundary

This branch is an **execution transport/cache**, not a new truth owner.

- Drive remains authoritative for governed current state, decisions, gates, stable project knowledge, and accepted evidence/releases.
- GitHub remains authoritative for repository/code/branch/commit/PR/CI technical truth.
- Files under this context tree are frozen transport copies or execution evidence inputs only.
- Any file containing `UNADMITTED` or `UNADMITTED_EVIDENCE` remains unadmitted evidence and MUST NOT be auto-applied.
- A payload file never grants product mutation, merge, release, deploy, publication, or final-acceptance authority.

## Surface payloads

- `LIBRARY_EDITOR/` — 18 files.
- `LEARN/` — 17 files.
- `VISUALIZE/` — 21 files.
- `RESEARCH_QUALITY/` — 19 files.
- `COMMON/` — shared Google AI Studio operational/dispatch transport copies.

The complete SHA-256 inventory is recorded in `PAYLOAD_MANIFEST.json` and `PAYLOAD_SHA256SUMS.txt` at the ZIP root before publication.

## AI Studio usage

Do not search the product filesystem for chat attachments. Once this context branch is published, make the context physically available through a separate worktree without touching the active product worktree:

```bash
git fetch origin context/cep-w02-aistudio-all-surfaces-r01
CONTEXT_DIR=/app/.cep-w02-context
[ ! -e "$CONTEXT_DIR" ] || { echo "CONTEXT_DIR_ALREADY_EXISTS: $CONTEXT_DIR"; exit 1; }
git worktree add --detach "$CONTEXT_DIR" "origin/context/cep-w02-aistudio-all-surfaces-r01"
```

Then read files directly, for example:

```bash
sed -n '1,220p' /app/.cep-w02-context/00_EXECUTION_CONTEXT/W02/LIBRARY_EDITOR/03_EXHAUSTIVE_VISUAL_DESIGN_FINDING_LEDGER.md
```

This does not switch, reset, restore, or modify the active product worktree.

## Stop rules

- Do not switch the active product worktree to the context branch.
- Do not implement product changes in `/app/.cep-w02-context`.
- Do not treat the context branch as acceptance or current-state authority.
- Re-resolve Drive/GitHub authority before any material state-dependent decision.

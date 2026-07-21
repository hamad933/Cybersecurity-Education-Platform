# Enterprise and Scenario Studio Interaction

Enterprise Designer exposes persistent catalogs and approved Baseline Revisions. Catalog changes and scenario-run state use distinct visual language. Starting a Run shows: `This forks an isolated snapshot; the baseline will not change.` Any discovered improvement creates a Baseline Change Proposal for separate review.

Scenario Studio uses three panes on desktop: catalog/context tree; graph/timeline/form canvas; properties/validation inspector. A user can select baseline, role, identities/assets, policies/controls, events/injects, actions/decisions, rules, outputs/logs/findings/alerts, evidence requirements, success/failure, remediation, verification, hints, and difficulty. Graph nodes and timeline events have equivalent keyboard-accessible list/form editing.

Validation reports missing references, unreachable states, contradictory transitions, unsupported rule versions, missing negative paths, and missing evidence. Publishing creates an immutable Scenario Definition Revision. Package export/import is advanced and uses the same model; imports become drafts only.


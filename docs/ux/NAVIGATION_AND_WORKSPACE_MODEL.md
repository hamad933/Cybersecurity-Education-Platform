# Navigation and Workspace Model

## Shell

- Skip link and semantic landmarks.
- Collapsible global navigation with the eight destinations.
- Workspace header with title, entity ID/revision, primary action, breadcrumbs, and state badges.
- Context pane for hierarchy/list/filters when useful.
- Main working area for the current task.
- Inspector pane for provenance, validation, properties, history, or evidence when complexity justifies it.
- Persistent processing/audit/status region and non-modal notifications.

Three panes are used for source review, structured lesson editing, and Scenario Studio on wide screens. Dashboard, evidence summaries, and narrow flows do not force three panes. Tablet collapses inspector into a labelled drawer; mobile uses one logical column with context and inspector as explicit sheets. Primary actions remain in the workspace header; hidden destructive gestures are prohibited.

Navigation state is addressable by local URL/hash in the design proof and by route in the product. Back/forward preserves workspace selection and unsaved-draft warnings. Switching workspaces never implies publishing, accepting evidence, or committing a Scenario Run action.


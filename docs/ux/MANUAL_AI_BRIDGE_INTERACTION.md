# Manual AI Bridge Interaction

The workspace is a visible status pipeline: `DRAFT`, `EXPORTED`, `AWAITING_MANUAL_PROCESSING`, `RESULT_IMPORTED`, validation failures, `AWAITING_HUMAN_REVIEW`, terminal human outcomes, and `SUPERSEDED`.

Export selection shows each source segment/knowledge revision, digest, sensitivity, bytes, exclusion/redaction, requested schema, and a clear notice that the owner will process files manually through ChatGPT Plus. Confirmation names the exact external scope.

Import uses drag/select into quarantine and shows manifest/digest/schema checks before parsing content. Structural and provenance failures are separate and actionable. Review is a three-pane diff: affected entities, proposed content/relationship changes with source evidence, and decision inspector. Per-change accept/edit/reject/defer rolls up to partial/accepted/rejected. Acceptance creates owning-module drafts only; no control suggests automatic publication or API submission.


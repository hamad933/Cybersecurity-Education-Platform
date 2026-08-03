# Lesson Reader and Editor Interaction

The same Knowledge workspace offers `Study`, `Inline Edit`, `Structured Edit`, `Review`, `Source Comparison`, and `Revision Comparison`. Switching modes preserves scroll position, selected block, lesson/KU context, and unsaved state.

Wide Structured Edit uses lesson outline, block canvas, and inspector. Blocks show type, stable ID, validation and provenance. The insertion palette offers only registered types. Keyboard commands provide insert-before/after, move, nest/unnest within allowed rules, duplicate-as-draft, and delete-with-confirmation. Toggle blocks use native button semantics and `aria-expanded`; code, command, log, and output remain LTR and copyable.

Published content is read-only. Edit creates a draft based on a revision. Compare shows block additions, removals, moves, payload/provenance changes, unresolved conflicts, and downstream impact. Publication requires validation plus an explicit human decision and creates a new immutable revision. Restoring a prior revision creates a new draft.


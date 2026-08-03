# RTL/LTR and Accessibility Baseline

- Document language is Arabic-first (`lang="ar"`, `dir="rtl"`) where the overall shell is Arabic; English alternatives change language/direction semantically.
- Arabic prose is right-aligned RTL. Do not manually reverse strings or punctuation.
- Technical identifiers use `<bdi dir="ltr">`; blocks for code, command, config, output, log, protocol, packet, hash, path, SID, mask, API, and JSON use `dir="ltr"` and left alignment.
- Mixed labels isolate only the technical token, preserving screen-reader and copy order.
- Semantic landmarks, one logical H1, hierarchical headings, explicit labels, table headers/captions, status live regions, and error associations are required.
- All actions work by keyboard; graph interactions have a list/form equivalent. Focus is visible and restored after dialogs/drawers.
- Contrast targets WCAG AA; state and evidence origin never rely on color. Zoom/reflow to 200% must retain content/actions.
- Images/diagrams require meaningful alt text or explicit decorative status. Reduced motion is honored.

Acceptance combines automated checks with keyboard, screen-reader spot checks, copied mixed-text comparison, and visual screenshots. The Task 004 proof demonstrates the baseline but does not establish product conformance.


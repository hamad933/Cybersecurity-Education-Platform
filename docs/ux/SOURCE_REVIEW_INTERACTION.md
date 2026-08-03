# Source Review Interaction

The Source Library lists custody status, type, bytes, digest fragment, extraction state, authority/review state, and warnings without treating filename as authority. Opening a source shows:

- context pane: representation outline, segments, claims, conflicts;
- main pane: safe extracted/previewed content with exact page/line/timestamp anchor;
- inspector: custody digest, authorization, extraction tool/status, authority/applicability, supporting/contradicting claims, and audit.

Review actions are `support`, `contradict`, `context only`, `defer`, `mark unresolved`, and `request evidence`. Each action requires a claim boundary and evidence anchor. Unsafe original download/preview is explicit. Extraction failure never offers publication. Additional original-source reads must be recorded as separately authorized work; Task 004 performed none beyond the required Product Charter.


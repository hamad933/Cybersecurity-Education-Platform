# Determinism Results

Two complete production runs were compared after the final structural-output
logic was in place. A final metadata-only peak-memory adjustment was followed by
another complete run and comparison against the same deterministic baseline.

- Deterministic outputs compared: 14
- Matching SHA-256 hashes: 14
- Differences: 0
- Result: PASS

`STRUCTURAL_RUN_METADATA.json` is excluded because it contains UTC timestamps
and process peak memory, and because hashing itself would be self-referential.

| Output | SHA-256 |
|---|---|
| `derived/structural/LARGE_SOURCE_HANDLING_PLAN.md` | `51aade5287e5850c2aeafb7c90b3e81a59ebb30b70610df844e8627790962ede` |
| `derived/structural/SEMANTIC_TRIAGE_REPORT.md` | `3ff3a22d21ab2c3a3735b002424144e523e8540c1749760dbb16859d65973b81` |
| `derived/structural/STRUCTURAL_INDEXING_REPORT.md` | `1010498f8a7a965e736f6e8cefe5790683d24d403f653e222e4761614ba144dc` |
| `derived/structural/UNIVERSITY_COURSE_READINESS_REPORT.md` | `40974d3a2d9ce77f5815e9960c4664bfa87bd554a1b55e4fe562357858ef2a21` |
| `manifests/structural/DOMAIN_CLASSIFICATION_CANDIDATES.tsv` | `43540ce90c8b9d5d8102b58d5eccba59f7123b5032017001525bba7a486014ad` |
| `manifests/structural/LARGE_SOURCE_SECTION_INDEX.tsv` | `43ce9c9aa911def0d6756b0f360ffef7ee3cc80f36ec6369e48eb1ae517bcd7a` |
| `manifests/structural/PDF_DOCUMENT_INVENTORY.tsv` | `0f0290b7550245b0a756f01180c5af4b5b73f1e1bd0fd437cac1f74fc6c3ea42` |
| `manifests/structural/PDF_PAGE_TEXT_STATUS.tsv` | `44eba05a190003f46083516827fdcc69c23bba4aa15b43613b8793a4316d0a57` |
| `manifests/structural/POSSIBLY_INCOMPLETE_FILES.tsv` | `7e7d37ab97e5e5b8fff6c972b633ab23edb09ff150dc75d7b30edd390c54ed67` |
| `manifests/structural/SEMANTIC_REVIEW_QUEUE.tsv` | `e375e42d37bbeaebbc80dc7e7dcc9a016e07ece20ad80c906e25602b44d3fd2f` |
| `manifests/structural/SOURCE_SECTION_CANDIDATES.tsv` | `916b6a8037471bbecfff982f41838e52d3a213f0e01f4f5168fe073a3378e5af` |
| `manifests/structural/SOURCE_STRUCTURAL_INDEX.tsv` | `fd1accc709b1e2011c95b3de844e31d4acf296a181c11b2b426a5ac48836992d` |
| `manifests/structural/STRUCTURAL_PARSE_ERRORS.tsv` | `e3213b2e21be84fed2fb212b37bb9aded0f970b0974b9b1b57357a09b26b38e5` |
| `manifests/structural/UNIVERSITY_COURSE_STRUCTURE.tsv` | `24c5a9c16b600efc11fcd207982cde4654a3685e85397d390e1d0eac12938c3a` |

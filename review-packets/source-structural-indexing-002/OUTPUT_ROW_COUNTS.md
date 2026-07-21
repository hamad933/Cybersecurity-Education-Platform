# Output Row Counts

| Output | Data rows |
|---|---:|
| `SOURCE_STRUCTURAL_INDEX.tsv` | 2,083 |
| `SOURCE_SECTION_CANDIDATES.tsv` | 45,086 |
| `LARGE_SOURCE_SECTION_INDEX.tsv` | 39 |
| `PDF_DOCUMENT_INVENTORY.tsv` | 36 |
| `PDF_PAGE_TEXT_STATUS.tsv` | 2,431 |
| `UNIVERSITY_COURSE_STRUCTURE.tsv` | 30 |
| `STRUCTURAL_PARSE_ERRORS.tsv` | 49 |
| `POSSIBLY_INCOMPLETE_FILES.tsv` | 7 |
| `DOMAIN_CLASSIFICATION_CANDIDATES.tsv` | 3,696 |
| `SEMANTIC_REVIEW_QUEUE.tsv` | 100 |

## Candidate-type counts

| Candidate type | Rows |
|---|---:|
| `HEADING_SECTION` | 38,465 |
| `STRUCTURAL_CHUNK` | 4,190 |
| `PDF_PAGE` | 2,431 |

## Section selection states

| State | Rows |
|---|---:|
| `CANDIDATE_FOR_REVIEW` | 43 |
| `NOT_SELECTED` | 45,043 |

Selection means only that the bounded queue references the candidate. It is not
semantic approval.

## PDF state counts

| State | Documents |
|---|---:|
| `PDF_TEXT_AVAILABLE` | 32 |
| `OCR_REQUIRED_FOR_SEMANTIC_REVIEW` | 3 |
| `PDF_PARSE_ERROR` | 1 |

Total PDF pages: 2,431.

## Conservative incomplete-file rules

| Rule | Severity | Rows |
|---|---|---:|
| `INC-EMPTY-TASK-LIKE` | HIGH | 4 |
| `INC-COURSE-SEQUENCE-GAP` | MEDIUM | 2 |
| `INC-PDF-PARSE-FAILURE` | HIGH | 1 |

All rows require human review and make no final completeness claim.

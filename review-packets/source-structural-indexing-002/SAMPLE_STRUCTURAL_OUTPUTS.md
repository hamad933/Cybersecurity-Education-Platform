# Sample Structural Outputs

These samples illustrate schemas and mechanical states only. They do not quote
source bodies or make semantic decisions.

## Structural index sample shape

```text
source_record_id | relative_path | parser_type | parse_status | structural counts | text_availability | candidate_count | semantic_state | error_reference
```

All 2,083 source rows use `NOT_SEMANTICALLY_INSPECTED` as the semantic state.

## Section/page candidate sample shape

```text
segment_candidate_id | source_record_id | candidate_type | heading/title | line/byte or page range | character estimate | section SHA-256 | selection_state
```

Candidate types observed:

- `HEADING_SECTION`: 38,465
- `STRUCTURAL_CHUNK`: 4,190
- `PDF_PAGE`: 2,431

Example mechanical identifiers:

- `seg-00db198e6643a81b92c24890` — heading section candidate
- `page-*` — stable PDF page candidates derived from source ID, page number,
  and source SHA-256

No full section or page text is copied into the TSV.

## PDF status sample shape

```text
source_record_id | page_count | encrypted | extraction_permitted | metadata fields | per-page character counts | OCR review state | parser state
```

Observed states:

- 32 `PDF_TEXT_AVAILABLE`
- 3 `OCR_REQUIRED_FOR_SEMANTIC_REVIEW`
- 1 `PDF_PARSE_ERROR`

The parse-error row is retained for human review; no damaged-file conclusion is
made.

## University-course sample summary

| Candidate course | Files | PDF pages | Apparent missing filename sequence numbers | Syllabus-like filename |
|---|---:|---:|---|---|
| `Ethical Hacking` | 8 | 390 | `3,5,6,7,8,9,10,11` | FALSE |
| `Network admin and monitoring` | 9 | 259 | none observed | FALSE |
| `Network security` | 5 | 69 | `2,4,6,8` | FALSE |
| `SAD-secure App Developing` | 8 | 327 | none observed | FALSE |

These are filename-derived sequence candidates, not final completeness claims.

## Provisional domain sample shape

```text
source_record_id | optional segment ID | candidate domain code | matched terms | match locations | deterministic score | confidence band | PROVISIONAL_NOT_SEMANTICALLY_VALIDATED
```

- Candidate rows: 3,696
- Unclassified sources: 1,144
- Multiple domain candidates are allowed.

## Review queue sample shape

```text
queue_item_id | source_record_id | optional segment ID | candidate domains | deterministic reason | mechanical priority score | priority band | inspection depth | AWAITING_SEMANTIC_REVIEW
```

Initial queue: 100 items; deferred candidates: 1,983.

Inspection-depth counts:

- `FULL_DOCUMENT`: 55
- `PDF_SELECTED_PAGES`: 29
- `SELECTED_SECTIONS`: 12
- `STRUCTURE_ONLY`: 1
- `TRANSCRIPT_OR_OCR_REQUIRED`: 3

Queue inclusion is a review-routing decision only, not semantic approval.

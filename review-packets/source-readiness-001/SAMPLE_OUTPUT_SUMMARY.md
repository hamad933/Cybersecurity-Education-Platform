# Sample Output Summary

This is a compact review aid for the generated mechanical inventory. The TSV
files and readiness reports remain the authoritative generated outputs.

## Output row counts

| Output | Data rows |
|---|---:|
| `SOURCE_FILE_CENSUS.tsv` | 2083 |
| `SOURCE_MANIFEST.tsv` | 2083 |
| `DIRECTORY_SUMMARY.tsv` | 456 |
| `DUPLICATE_FILE_HASHES.tsv` | 247 |
| `LARGE_FILES.tsv` | 39 |
| `ZERO_BYTE_FILES.tsv` | 16 |
| `UNIVERSITY_COURSE_INVENTORY.tsv` | 4 |

## Source-group totals

| Source group | Files | Bytes |
|---|---:|---:|
| `ad-identity-pilot` | 4 | 78,749 |
| `chatgpt-project` | 2041 | 1,466,876,083 |
| `historical-platform` | 7 | 383,772 |
| `product-charter` | 1 | 50,937 |
| `university-courses` | 30 | 96,201,518 |

## University course candidates

| Course folder | Files | Bytes | Apparent lecture files | Syllabus-like filename | Completeness | Semantic quality |
|---|---:|---:|---:|---|---|---|
| `Ethical Hacking` | 8 | 63,033,481 | 0 | FALSE | UNKNOWN | NOT_REVIEWED |
| `Network admin and monitoring` | 9 | 21,815,426 | 0 | FALSE | UNKNOWN | NOT_REVIEWED |
| `Network security` | 5 | 776,530 | 0 | FALSE | UNKNOWN | NOT_REVIEWED |
| `SAD-secure App Developing` | 8 | 10,576,081 | 8 | FALSE | UNKNOWN | NOT_REVIEWED |

The apparent lecture and syllabus values are derived only from filenames. No
course material was semantically inspected.

## Deterministic output hashes

| Output | SHA-256 |
|---|---|
| `derived/readiness/SOURCE_GAPS.md` | `eb050b61afe0ce14eaf11251138bfb029084123978590b404efa3b06c80e07cb` |
| `derived/readiness/SOURCE_READINESS_REPORT.md` | `9dd3703619eecfd20d1661460a62ab87255f5406f25370d325393e6fdd8de345` |
| `manifests/DIRECTORY_SUMMARY.tsv` | `ac57db7b079dd8e7a3dbf457a44072d4c0c444e9ed10d8b2f5607396ae6ac3fc` |
| `manifests/DUPLICATE_FILE_HASHES.tsv` | `0df4f92e0ac428f07b8499eab2faeb79af5342d5e57a277931ef21c9bf475bad` |
| `manifests/LARGE_FILES.tsv` | `6b6cd536c5d5258511b3d91852507181f54240d5ca5a282732eaa42463484886` |
| `manifests/SOURCE_FILE_CENSUS.tsv` | `722f750d547f9aa386d92bc5270fe6f8b87acd3cfa2b205efc310e122c2e28a7` |
| `manifests/SOURCE_MANIFEST.tsv` | `28e315ef0d17a7ea6481fe7b3cf3754bc6068d834dc05a4bb11433fdb067042a` |
| `manifests/UNIVERSITY_COURSE_INVENTORY.tsv` | `cd006cc79f7383696f58e6b6bdd4b4da7473a47e8e8fc88407d7255311f89bf8` |
| `manifests/ZERO_BYTE_FILES.tsv` | `016aeba1bdcd79b216c943334426740503bdef2710605b2c8bf7fde199ac200f` |

The hashes matched across two complete inventory runs. UTC timestamps in
`TOOL_RUN_METADATA.json` are run-specific and are excluded from this
deterministic comparison.

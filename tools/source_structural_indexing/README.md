# Source Structural Indexing and Semantic Triage

This Python 3.13 tool extends the approved Source Readiness 001 mechanical
inventory with deterministic structural indexing and a bounded semantic-review
queue. It does not make semantic correctness, source-quality, curriculum, or
canonical-truth decisions.

The implementation reads `source-vault/originals/` without modifying it. It
does not follow symbolic links, decompress archives, perform OCR, crawl URLs,
execute source code or labs, or build the product application.

## Local environment

From the workspace root:

```powershell
python -m venv product-repo/tools/source_structural_indexing/.venv
product-repo/tools/source_structural_indexing/.venv/Scripts/python.exe -m pip install --requirement product-repo/tools/source_structural_indexing/requirements.txt
```

The only runtime parsing dependency is `pypdf==6.10.0`. All non-PDF work uses
the Python standard library.

## Run

```powershell
product-repo/tools/source_structural_indexing/.venv/Scripts/python.exe `
  product-repo/tools/source_structural_indexing/structural_index.py `
  --source-root source-vault/originals `
  --input-manifest-root source-vault/manifests `
  --output-manifest-root source-vault/manifests/structural `
  --report-root source-vault/derived/structural
```

The initial semantic-review queue defaults to 100 items. Override it with
`--queue-limit`; the run metadata records both selected and deferred counts.

## Tests

```powershell
product-repo/tools/source_structural_indexing/.venv/Scripts/python.exe -B -m unittest discover `
  -s product-repo/tools/source_structural_indexing/tests `
  -p "test_*.py" -v

product-repo/tools/source_structural_indexing/.venv/Scripts/python.exe -B -m unittest discover `
  -s product-repo/tools/source_readiness/tests `
  -p "test_*.py" -v
```

## Deterministic domain scoring

`domain_keywords.json` is the editable source of domain codes, labels,
keywords, and location weights. Matching is case-insensitive after Unicode NFC
normalization. Each distinct keyword/location pair contributes its configured
location weight:

- filename: 3
- path: 2
- heading or structural title: 2
- PDF metadata or outline: 1

Scores of 8 or more are `HIGH_MECHANICAL_SIGNAL`; 4-7 are
`MEDIUM_MECHANICAL_SIGNAL`; 1-3 are `LOW_MECHANICAL_SIGNAL`. Sources with no
match remain `UNCLASSIFIED`. Scores are signals for triage, never quality
ratings.

## Deterministic review priority

The queue ranks one candidate per source using fixed bands and tie-breaking by
normalized relative path:

1. `P0_PRODUCT_AUTHORITY`: files under `product-charter/`.
2. `P1_PILOT_AND_CANONICAL`: files under `ad-identity-pilot/` or with explicit
   `canonical`, `baseline`, or `pilot` filename signals.
3. `P2_UNIVERSITY_COURSE`: files under `university-courses/`.
4. `P3_LARGE_OR_AMBIGUOUS`: large files, OCR-required PDFs, parse failures, or
   unclassified sources.
5. `P4_REFERENCE`: remaining structurally indexed references.

Within a band, bounded mechanical bonuses cover parse warnings, large-file
partitioning, PDF text status, and domain-signal strength. File size alone never
asserts educational importance.

# Source Readiness Toolkit

This directory contains a deterministic, read-only filesystem inventory tool for
`source-vault/originals`.

The toolkit records mechanical facts only. It does not decompress archives,
inspect source material semantically, execute labs, crawl links, or build the
product application. Source files are opened only for binary reads needed for
streaming SHA-256 hashing, best-effort text/binary classification, and streaming
line counting.

## Requirements

- Python 3.9 or newer
- Python standard library only

## Run from the workspace root

```powershell
python product-repo/tools/source_readiness/inventory.py `
  --source-root source-vault/originals `
  --manifest-root source-vault/manifests `
  --report-root source-vault/derived/readiness
```

The default large-file threshold is 10 MiB. Override it with
`--large-file-threshold-bytes`, for example:

```powershell
python product-repo/tools/source_readiness/inventory.py `
  --source-root source-vault/originals `
  --manifest-root source-vault/manifests `
  --report-root source-vault/derived/readiness `
  --large-file-threshold-bytes 5242880
```

The tool rejects output roots that overlap the source root. It does not follow
symbolic-link directories, and symbolic-link files are recorded as skipped
rather than followed.

## Tests

```powershell
python -m unittest discover `
  -s product-repo/tools/source_readiness/tests `
  -p "test_*.py" -v
```

Reruns against unchanged source inputs produce equivalent TSV and Markdown
outputs. UTC run timestamps in `TOOL_RUN_METADATA.json` are intentionally
run-specific.

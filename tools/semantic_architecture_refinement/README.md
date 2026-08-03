# TASK-003R Semantic Architecture Refinement

This tooling assembles and validates the source-grounded corrective baseline
authorized by `TASK_003R_SEMANTIC_CAPABILITY_REFINEMENT.md`. It does not build
product application code, perform OCR, access external sources, execute reviewed
source code, or perform real-lab/offensive activity.

`refinement_data.py` contains the hand-curated Domain, cluster, source, course,
and KU-split interpretations. `build_refined_semantic_baseline.py` resolves those
interpretations to the preserved Task 003 evidence, writes the versioned outputs,
and creates the handoff. It does not infer semantic authority from paths.

Run from the workspace root with the bundled Python 3.13 runtime:

```powershell
$python = 'C:\Users\User\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe'
$env:PYTHONDONTWRITEBYTECODE = '1'
& $python -B product-repo/tools/semantic_architecture_refinement/build_refined_semantic_baseline.py
& $python -B product-repo/tools/semantic_architecture_refinement/validate_refined_semantic_baseline.py
& $python -B -m unittest discover -s product-repo/tools/semantic_architecture_refinement/tests -p 'test_*.py' -v
```

The validator checks exact schemas, IDs, foreign keys, evidence/source hashes,
Task 001/002/003 regressions, authority boundaries, anti-boilerplate metrics,
cross-Domain exactness, university file specificity, VS-001 traceability,
authorized write scope, handoff checksums, and ZIP integrity. It runs the existing
Task 003 validator behind a write blocker so preserved Task 003 audit artifacts
cannot be rewritten.

The generated baseline is a review candidate only. The stop gate does not
self-approve Task 003R or authorize Task 004.


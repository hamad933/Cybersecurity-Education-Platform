# Task 004 Product Architecture and UX Validation

This standard-library-only validator checks the Task 004/005 candidate baseline without creating product application code or rerunning semantic generation.

Run core validation:

```powershell
python -B product-repo/tools/product_architecture_ux_validation/validate_task004.py
```

After the review packet is complete, build and verify the automatic review handoff:

```powershell
python -B product-repo/tools/product_architecture_ux_validation/validate_task004.py --package
```

Run unit tests after packaging:

```powershell
python -B -m unittest discover -s product-repo/tools/product_architecture_ux_validation/tests -p test_*.py -v
```

The packaging command replaces only the authorized `TASK_004_REVIEW_HANDOFF` directory and ZIP, copies the prescribed payload with project-relative paths, hashes and verifies every copied file, tests the final ZIP, and reports file and byte totals. It excludes originals, reviewed-source copies, caches, virtual environments, dependencies, and browser profiles.

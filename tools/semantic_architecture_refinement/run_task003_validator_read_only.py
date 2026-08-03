#!/usr/bin/env python3
"""Execute the existing TASK-003 validator without permitting its report writes."""

from __future__ import annotations

import importlib.util
from pathlib import Path


ROOT = Path(__file__).resolve().parents[3]
VALIDATOR = ROOT / "product-repo" / "tools" / "semantic_architecture_validation" / "validate_semantic_baseline.py"


def main() -> int:
    spec = importlib.util.spec_from_file_location("task003_validator_read_only", VALIDATOR)
    if spec is None or spec.loader is None:
        raise RuntimeError(f"Cannot load {VALIDATOR}")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    original_write_text = Path.write_text

    def blocked_write(self: Path, data: str, *args: object, **kwargs: object) -> int:
        resolved = self.resolve()
        old_packet = (ROOT / "product-repo" / "review-packets" / "semantic-capability-003").resolve()
        if old_packet == resolved or old_packet in resolved.parents:
            return len(data)
        raise RuntimeError(f"TASK-003 read-only wrapper blocked unexpected write: {resolved}")

    Path.write_text = blocked_write  # type: ignore[method-assign]
    try:
        result = module.main()
    finally:
        Path.write_text = original_write_text  # type: ignore[method-assign]
    return int(result)


if __name__ == "__main__":
    raise SystemExit(main())


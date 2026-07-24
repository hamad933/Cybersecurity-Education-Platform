# Module boundary results

Result: PASS, 31 architecture tests / 448 assertions in the full Architecture suite.

All ten governed modules remain in the registry and the graph remains acyclic. Active implementation directories are `MOD-IAM`, `MOD-PLT`, `MOD-SRC`, `MOD-KNO`, `MOD-CUR`, `MOD-ENT`, `MOD-SIM`, `MOD-EVD`, and `MOD-LRN`. `MOD-AIB` is absent.

Tests scan every PHP file under `app/Modules` for dependency legality, Platform-to-domain imports, cross-module ORM imports, and raw table ownership. Simulator imports no Learning or Evidence implementation. Application coordination passes identifiers/value payloads across owners and the Simulator has no Enterprise baseline update repository.

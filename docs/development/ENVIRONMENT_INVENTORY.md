# Task 006 environment inventory

Inspection date: 2026-07-22 (Asia/Riyadh). Workspace: `C:\Users\User\Desktop\Enterprise-Projects\Cybersecurity-Education-Platform`. Repository: `C:\Users\User\Desktop\Enterprise-Projects\Cybersecurity-Education-Platform\product-repo`. Both are on the Windows filesystem, not a WSL filesystem.

| Item | Observed state |
|---|---|
| Host | Windows NT 10.0 build 26100; DisplayVersion 24H2; registry `ProductName` reported `Windows 10 Home` (recorded literally because this field is historically ambiguous) |
| Architecture / CPU | AMD64; 24 logical processors reported by the process environment |
| Virtualization details | CIM and `systeminfo` queries were access-denied in the managed sandbox; operational status not inferred |
| Git | 2.51.0.windows.2 at `C:\programs\Git\cmd\git.exe` |
| Repository | New Git repository initialized exactly at `product-repo/`, branch `main` |
| WSL | `wsl.exe` reported WSL not installed; no distributions available |
| Docker / Compose | Commands absent; engine, image pull, build, health, and runtime topology unavailable |
| Host PHP / Composer | Not on `PATH` |
| Host Node / npm | Not on `PATH` |
| Host PostgreSQL client | Not on `PATH` |
| Portable verification PHP | PHP 8.5.8 NTS x64, OpenSSL 3.5.7, extensions including `pdo_pgsql`, `pgsql`, `mbstring`, `intl`, `curl`, and `zip` |
| Portable Composer | 2.10.2 |
| Portable Node / npm | Node 24.18.0 / npm 11.16.0 |
| Portable PostgreSQL | PostgreSQL 18.4; ephemeral cluster bound to `127.0.0.1:5432` for Task 006 tests |
| Ports at preflight | 3000, 5173, 8000, 8080, and 5432 were available |
| Environment variables | Relevant variable names were inspected without printing values; full enumeration was partially blocked by case-duplicate keys in the managed Windows environment |

No system-wide install, administrator configuration change, Docker setting change, WSL change, or project move was made. Portable tools and PostgreSQL data were held below the user's temporary directory. PostgreSQL initialization required execution outside the filesystem sandbox because the sandbox token could not create PostgreSQL's restricted Windows token; this did not install or configure a system service. The temporary test cluster used trust authentication on loopback only and is not a deployment configuration.

## Scaffold preservation

Laravel skeleton `13.8.0` was created under a controlled temporary directory, compared, and intentionally merged. The only collisions were `.editorconfig`, `.gitattributes`, and `.gitignore`; all three differed and the existing governed repository copies were retained. The temporary scaffold was not used as runtime storage and is excluded from Git and the handoff.

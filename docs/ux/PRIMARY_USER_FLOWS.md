# Primary User Flows

| Flow | Visible stages | Required exception states |
|---|---|---|
| Source to canonical publication | select; quarantine; digest; extract; review; draft; compare; publish | type mismatch; extraction warning; authority gap; conflict; rejected publication |
| Lesson edit and publish | read; edit; insert/reorder; cite; validate; compare; review; publish | invalid block; lost optimistic version; unresolved citation; blocked authority |
| Daily learning | why selected; lesson; practice; lab; evidence; mastery; review | prerequisite block; failed practice; missing evidence; retention due |
| Enterprise scenario | baseline; Studio; validate; publish; fork Run; act; verify; reset/replay | unresolved reference; unsupported action; Run/baseline warning; replay mismatch |
| Manual AI | select scope; preview; export; manual process; import; validate; review diff; create drafts | tampered package; schema failure; provenance failure; partial acceptance; rejection |
| Backup/restore | scope; create; hash; verify; stage; validate; activate/abandon | missing blob; digest mismatch; schema incompatibility; failed restore |

Each transition states who/what decided, whether the state is mutable, its evidence/provenance, and the next reversible or irreversible effect. Confirmation copy names the exact target and consequence.


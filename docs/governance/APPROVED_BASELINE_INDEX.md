# Approved Baseline Index

Status: **GOVERNING INDEX FOR TASK 006**  
Access date: **2026-07-22**

## Cryptographic anchors

| Baseline | Artifact | Bytes | SHA-256 |
|---|---|---:|---|
| Task 004 | `review-packets/TASK_004_REVIEW_HANDOFF.zip` | 1,390,313 | `55754797BF5A24B94800F2C49B03CE080F940DFD9B8A82EB6F4D923DD6F3B923` |
| Task 004 | `review-packets/TASK_004_REVIEW_HANDOFF/HANDOFF_MANIFEST.tsv` | 24,074 | `7AA3E1E9E064FD127B143F838966F9687924A3A3E02043FBB22B5FD08EA81883` |
| Task 004 | `review-packets/TASK_004_REVIEW_HANDOFF/SHA256SUMS.txt` | 14,415 | `896E800B2810EBB789E875B3A227C0B402DBB12B2218D2EA8DCA386E41925108` |
| Task 003R | `review-packets/TASK_003R_REVIEW_HANDOFF.zip` | 344,225 | `5AAC7863BB319D7B9752155581B60925EB8DA6157C51935D165C2382B122B991` |
| Task 003R | `review-packets/TASK_003R_REVIEW_HANDOFF/HANDOFF_MANIFEST.tsv` | 10,629 | `CA8869702F214E2ED6070A876E03A7CA4A9274BA8E2A401D4C45211B8BEB9860` |
| Task 003R | `review-packets/TASK_003R_REVIEW_HANDOFF/SHA256SUMS.txt` | 9,076 | `F5E4E9F235706A75ABB679851CFC1D4827256AA5310835C9DAEFF493F9C1912B` |

The Task 004 `HANDOFF_MANIFEST.tsv` is the exact row-level path, byte-size, and SHA-256 index for every approved copied artifact. The Task 004 validator verified 435 assertions, all copied hashes, missing count zero, and ZIP CRC/member/size integrity before implementation. The Task 003R validator independently passed 3,931 assertions on 2026-07-22.

## Approved artifact families

| Family | Approved paths | Governing interpretation |
|---|---|---|
| Product | `docs/product/*.md` as enumerated in the Task 004 handoff manifest | Approved product, v1 boundary, module, delivery, risk, success, and Task 006 scope inputs |
| Architecture | `docs/architecture/*.md` and `docs/architecture/adr/ADR-001` through `ADR-014` as enumerated | Approved architecture candidate for bounded implementation; historical status text remains immutable |
| UX | `docs/ux/*.md` as enumerated | Approved interaction, design-token, Arabic RTL/LTR, accessibility, and responsive principles |
| Planning | `planning/task004/*.tsv` as enumerated | Approved Task 004 planning baseline; never modified by Task 006 |
| VS-001 | `docs/architecture/VS001_ARCHITECTURE_SLICE.md` and `planning/task004/VS001_ACCEPTANCE_CRITERIA.tsv` | Approved candidate reference only; Task 007 remains gated and no semantics are authorized here |
| Design proof | `design-prototypes/task004/**` as enumerated | Design proof only, never runtime implementation evidence |
| Validation | `tools/product_architecture_ux_validation/**` as enumerated | Read-only regression authority for the prior baseline |
| Review | `review-packets/product-architecture-ux-004/**` as enumerated | Approval input and prior execution evidence |
| Semantic planning | Task 003R handoff artifacts and the selected Task 003R references copied into the Task 004 handoff | Approved semantic planning baseline; original candidate wording and evidence remain immutable |

## Preservation rule

The row-level manifests are the authority for byte preservation. Task 006 may add governance records and implementation artifacts only in its authorized paths. Root `AGENTS.md` is the sole approved prior-file modification and receives exactly the stable Task 004 patch. Any mismatch elsewhere is a Task 006 failure.

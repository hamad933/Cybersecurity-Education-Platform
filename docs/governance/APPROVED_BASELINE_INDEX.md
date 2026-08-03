# Approved Baseline Index

Status: **GOVERNING INDEX FOR TASK 008 TARGETED VS-001 CORRECTIONS AND BOUNDED VS-002 REVIEW-CANDIDATE WORK**
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
| Task 007 | `review-packets/TASK_007_REVIEW_HANDOFF.zip` | 2,800,319 | `4AE5FEA9DD8D193E36E8D4F82BBA499B6EDE7043D2441BD82DE9D3B1501EBF3C` |
| Task 007 | `review-packets/TASK_007_REVIEW_HANDOFF/HANDOFF_MANIFEST.tsv` | 32,626 | `776B1A185B23B5C2FBEB901DEB04F4D03D6BA69A174285B1E2A468AF5DED2834` |
| Task 007 | `review-packets/TASK_007_REVIEW_HANDOFF/SHA256SUMS.txt` | 18,777 | `FF1D1ABCC46C677185E9E99FEF4AFAF162CD037222DF9360D2FB00ADD0B6BD92` |

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

## Task-006 continuation decision

`docs/governance/TASK006_EXTERNAL_REVIEW_RECORD.md` records `APPROVE WITH TARGETED REWORK IN TASK-007`. The historical Task-006 review packet remains immutable. The targeted corrections and their `FOUNDATION_CORRECTION_GATE: PASS` evidence are additive Task-007 records under `review-packets/vs001-007/**` and `planning/task007/**`.

The correct Task-004 `SHA256SUMS.txt` artifact hash is the cryptographic anchor already shown above: `896E800B2810EBB789E875B3A227C0B402DBB12B2218D2EA8DCA386E41925108`. The differing value in the historical Task-006 `PRIOR_OUTPUT_SAFETY.md` is a superseded transcription typo, not an artifact mismatch.

Docker runtime remains unexecuted on this host and is labeled `DOCKER_RUNTIME_UNAVAILABLE`. Static Docker validation, a clean production staging install, and native PostgreSQL 18.4 runtime tests passed; this limitation is not promoted to a Docker runtime pass.

## Preservation rule

The row-level manifests are the authority for byte preservation. Historical Task-003R, Task-004, and Task-006 review packets are not rewritten by Task-007. Task-007 may modify only its authorized implementation, governance, planning, testing, and review paths. The Task-007 stop gate is `STOP-VS001-007`; this index grants no TASK-008 authority.

## Task-007 external review and Task-008 correction gate

`docs/governance/TASK007_EXTERNAL_REVIEW_RECORD.md` records `APPROVE WITH TARGETED REWORK IN TASK-008`. The historical Task-007 `24/24 PASS` remains immutable and is superseded only by the additive external recheck in `planning/task008/VS001_EXTERNAL_ACCEPTANCE_RECHECK.tsv`.

The Task-008 correction gate is authoritative only when `review-packets/vs002-008/VS001_CORRECTION_GATE.md` states `VS001_CORRECTION_GATE: PASS`. This permits the bounded VS-002 review-candidate implementation described by the active Task-008 prompt; it is not final external approval and grants no TASK-009 or VS-003 authority.

## Task-008 external review and Task-009 correction gate

`docs/governance/TASK008_EXTERNAL_REVIEW_RECORD.md` records `APPROVE WITH TARGETED REWORK IN TASK-009`. The historical Task-008 packet remains immutable. The additive Task-009 correction evidence is recorded in `planning/task009/VS002_CORRECTION_RESULTS.tsv` and `review-packets/vs003-009/TASK008_CORRECTION_GATE.md`.

VS-003 authority is granted only when the latter record states `TASK008_CORRECTION_GATE: PASS`. It now records `PASS` with 62 passing targeted VS-001/VS-002 regression tests and 524 assertions in the Docker development runtime against isolated PostgreSQL. This is a correction-gate result, not external approval.

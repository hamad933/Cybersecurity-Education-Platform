# 04_PRIOR_FINDING_COVERAGE_AND_MISSED_DEFECT_MATRIX

**Project:** Cybersecurity Education Platform (`CEP`)  
**Route:** `PERSONAL:CEP`  
**Role:** W02 VISUALIZE — Deep Information-Visualization / Visual-System / Interaction-Design Auditor  
**Audit type:** READ-ONLY / BLIND-FIRST / exact-candidate assurance  
**Repository:** `hamad933/Cybersecurity-Education-Platform`  
**Branch:** `work/cep-w02-library-work-visual-r01`  
**Verified SHA:** `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`  
**Verified parent:** `7fa8714dc6d0beec6ec77ba8a673140301b066cf`  
**Baseline drift:** `NONE / NO_BASELINE_DRIFT`  
**Product mutation:** `NONE`  
**Writer dispatch:** `NONE`  
**Acceptance / merge / release / deploy:** `NONE`


## 1. Reconciliation rule

تمت قراءة الحزمة السابقة **بعد** انتهاء Blind-First. لم تُستخدم كـ checklist مسبق. لا يتم تغيير lineage القديم أو اختراع replacement IDs له؛ الـ 59 finding هنا هي atomic assurance decomposition مع `prior finding mapping` صريح.

## 2. Counts

- `KNOWN`: 16
- `UNDER_SPECIFIED`: 18
- `MISSED_NEW`: 25
- `REGRESSED`: 0

سبب `REGRESSED=0`: الـ prior package والـ deep audit الحالي يراجعان نفس SHA `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`. لذلك discovery الجديد لا يثبت regression زمنيًا؛ إما أنه كان معروفًا، أو كان مخفيًا داخل broad finding، أو missed entirely.

## 3. Coverage matrix

| Prior finding / delta | Prior meaning | New atomic mapping | Deep-audit conclusion | Class |
|---|---|---|---|---|
| VIS-01 / prior T-01 | Genuine Tree hierarchy + distinct View semantics | VIS-T-001, T-002, T-004, T-006, T-007, T-010, T-012; VIS-P-001..P-013; VIS-G-001,G-008,G-009,G-012,G-014 | Broad finding contained separate hierarchy-depth, multi-parent, disclosure, state, Path algorithm, Graph layout and evidence defects. | `UNDER_SPECIFIED` |
| VIS-02 / prior T-02 / M9 | Typed graph, labels, Map identity/persistence | VIS-T-003,T-005; VIS-C-008,C-009; VIS-S-002,S-003 | Label provenance and Saved Map were known, but runtime lineage and filter-vs-world IA were not decomposed. | `UNDER_SPECIFIED` |
| VIS-03 | Route/history/selection reconciliation | Protected current value; supports G-005/C-005 validation | Current edge selection/Back/Forward/stale prune remains valid. New findings do not reopen old route-state implementation globally. | `KNOWN / PROTECT` |
| VIS-04 | Authoritative Overlay provider | VIS-S-004 | Prerequisite provider is correct; other absent layers remain truthful NO_DATA/NO_AUTHORITY. | `KNOWN / PROTECT` |
| VIS-05 | Map membership vs Library browsing | Protected; related semantic issue VIS-S-008 is different | Old lie is closed. New issue is terminology collision between filter `عالم العرض` and world/Map identity. | `MISSED_NEW for S-008` |
| VIS-06 / O-02 | View-aware Overlay rendering | VIS-C-010, VIS-S-009 | Old source behavior was judged satisfied but browser evidence remains open. | `KNOWN` |
| ARCH-SHARED-01 | Nested workspace regions | VIS-T-011, VIS-G-009, VIS-S-005,S-006,S-007 | Prior package correctly identified ownership defect, but downstream center/context/responsive consequences are now decomposed. | `UNDER_SPECIFIED` |
| Prior P-01 | Path canonical order authority | VIS-P-001,P-002,P-006,P-011 | Old finding captured authority gap; tie-break semantics and current-state consequences were not atomic. | `UNDER_SPECIFIED` |
| Prior P-03 | Path underuses CENTER | VIS-P-003,P-004,P-005,P-012 | ‘Underuses center’ concealed independent connector, branch, overflow and geometry defects. | `UNDER_SPECIFIED` |
| Prior G-01 | Focused node evidence | VIS-G-001,G-005,G-006 | Old finding asked for node-focused proof; edge-centered focus inconsistency was not identified. | `MISSED_NEW for G-005` |
| Prior G-03 | Arrow direction / RTL | No open defect; preserve | Source correctly uses marker-end from canonical endpoints; this is a positive protected behavior. | `KNOWN / CLOSED` |
| Prior C-01 | Representation-only Canvas | VIS-C-001,C-006 | Session-only truth is correct; old review did not question reuse of Graph layout or coordinate prominence. | `MISSED_NEW` |
| Prior C-03 | Canvas movement evidence | VIS-C-007 | Still evidence-open. | `KNOWN` |
| Prior M-01/M-02 | Saved Map absent/authority | VIS-C-008 | Still authority-gated; this audit does not authorize persistence. | `KNOWN` |
| Prior R-01 | ~1024 missing | VIS-T-012,P-012,G-014,S-006,S-010 | Prior single evidence gap affects every view plus Bidi/context closure. | `UNDER_SPECIFIED` |

## 4. Broad prior findings that concealed multiple defects

### 4.1 “Prove genuine hierarchy”
لم تعد finding واحدة. انقسمت إلى:
- depth evidence.
- fixture authenticity.
- human-label provenance.
- domain/cluster visual distinction.
- technical fallback duplication.
- multi-parent containment loss.
- disclosure affordance.
- relation-token prominence.
- secondary metadata grammar.
- active/selected/focused state.
- center geometry.
- long-tree/~1024 closure.

### 4.2 “Path underuses CENTER”
انقسمت إلى:
- stage geometry.
- connectors.
- branch/fork semantics.
- current/previous/next semantics.
- selection vs progress.
- raw prerequisite labels.
- milestone model.
- cycle handling.
- lexical tie-break.
- overflow behavior.
- non-linear representative data.

### 4.3 “Focused Graph”
انقسمت إلى:
- missing node-focused evidence.
- missing legend.
- tiny edge labels.
- collision routing.
- edge-centered focus defect.
- selected-edge dominance.
- structural node-type grammar.
- neighbor grouping.
- fixed 960 layout.
- edge-label collision.
- hover/pre-inspection.
- isolated-node state.
- relation-type coverage.
- viewport runtime matrix.

### 4.4 “Canvas representation-only”
تم الحفاظ على الحقيقة الصحيحة، لكن deep audit وجد مستقلًا:
- Canvas default layout reuses Graph.
- no pointer camera pan.
- no edge labels.
- no legend.
- selected edge has no visual state.
- coordinates are over-prominent.
- movement evidence remains open.
- Saved Map is authority-gated.
- Map/filter/View/Overlay terminology collision.
- active Overlay evidence open.

## 5. Historical findings explicitly **not** reopened

- `VIS-03` typed route/history semantics: protected.
- `VIS-04` prerequisite provider truth: protected.
- `VIS-05` Map membership vs Library browsing separation: protected.
- semantic edge arrow direction under RTL: protected.
- content-aware FIT mechanism: protected.
- Canvas canonical-invariance design: protected.

هذه protected behaviors يجب ألا تضيع في أي remediation لاحق.

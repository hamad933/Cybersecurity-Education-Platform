PROJECT: Cybersecurity Education Platform (CEP)
ROUTE: PERSONAL:CEP
SURFACE: W02 Library + Unified Editor
AUDIT MODE: EXHAUSTIVE_READ_ONLY_DEEP_AUDIT
EXACT GITHUB SHA: `ca36e75c116a9ba00b5d25d358bd68c10990bd6e`
EXPECTED PARENT: `7fa8714dc6d0beec6ec77ba8a673140301b066cf`
PRODUCT MUTATION: NONE
WRITER DISPATCH: NONE
ACCEPTANCE: NOT GRANTED
DATE: 2026-09-04

# 06 — Shared Dependencies and Cross-Surface Handoff

هذه الوثيقة ليست writer handoff. هي dependency handoff إلى Controller B فقط.

## SD-01 — Authoritative hierarchy label read contract
**Findings:** `W02-DA-033`, `062`, plus visual consequences `020`, `029`, `034`.

Library لا يملك سلطة اختراع Domain/Cluster/Capability titles. المطلوب لاحقًا read contract واحد يربط IDs بالعناوين البشرية المعتمدة. إلى أن يوجد ذلك، unresolved state أصدق من ID-as-title.

**Collision risk:** HIGH — Curriculum, Learn, Visualize, RQ قد تستهلك hierarchy نفسها.

## SD-02 — Shared global/primary navigation and icon grammar
**Findings:** `022`, `023`, `031`, `039`.

أي تغيير في global shell أو KnowledgeTabs أو icon system قد يؤثر Learn/Visualize/RQ. لا يجوز معالجة 1024 أو emoji عبر Library-only fork إذا كان المكوّن مشتركًا.

**Collision risk:** HIGH.

## SD-03 — Shared cross-surface return-state ownership
**Finding:** `003`.

`KnowledgeTabs.vue` يحافظ على `object` فقط. أي registry لعودة search/lens/task/scroll يجب تحديد مالكه وحدوده؛ لا يجوز تمرير state عشوائيًا بين الأسطح.

**Collision risk:** HIGH.

## SD-04 — RQ provenance boundary
**Findings:** `004`, `035`, `037`, `050`, `054`.

Library RIGHT يجب أن يظل contextual. RQ يملك deep research. المطلوب لاحقًا تحسين richness/cardinality داخل Library دون نسخ RQ أو تكرار actions.

**Collision risk:** HIGH.

## SD-05 — Runtime/canonical data authority
**Findings:** `052`, `055`, `056`, `061`, `062`, `063`.

B09 authority وruntime import وacceptance fixture ثلاثة أشياء منفصلة. Controller B يجب أن يحدد:
- هل/متى يوجد production canonical projection؟
- ما المسموح من B09 داخل acceptance dataset؟
- ما run receipt المطلوب لإثبات exact browser data؟
- ما default bootstrap مقابل W02 acceptance bootstrap؟

**Collision risk:** CRITICAL بسبب إمكانية خلط canonical truth مع fixture truth.

## SD-06 — Shared responsive shell
**Findings:** `011`, `012`, `014`, `025`, `026`, `039`, `040`, `041`, `042`.

المشكلة ليست فقط Library CSS. `.cep-global-header`, `.cep-primary-navigation`, `.cep-action-bar`, grid medium behavior مشتركة. أي remediation يجب أن يحمي الأسطح الأخرى.

**Collision risk:** HIGH.

## SD-07 — Locale/evidence contract
**Findings:** `043`, `044`, `045`.

Arabic-first + LTR technical islands مثبتة كنية. Full EN locale غير مثبتة. evidence pipeline سمّى AR/EN صورًا byte-identical. يجب توحيد product locale scope مع evidence labels.

**Collision risk:** MEDIUM/HIGH.

## SD-08 — Design token / accessibility-visible state
**Findings:** `017`, `019`, `023`, `030`, `031`, `032`.

Text contrast, icon system, disabled state and micro-action grammar يفضل أن تُحل عبر shared tokens/patterns، لا hardcoded Library-only exceptions.

**Collision risk:** MEDIUM/HIGH.


## SD-09 — Shared canonical content-structure grammar
**Findings:** `064`, `065`, with representative-content dependency `048`.

`LessonContentContract` is shared semantic infrastructure between Library editing and Learn projection. Table/list semantics therefore cannot be repaired safely through Library-only CSS or fixture-only string formatting. Controller B must decide the lossless structured representation and ownership before any implementation plan.

**Collision risk:** HIGH — Knowledge storage, Library editor, Learn renderer, acceptance import and accessibility all intersect.

## Handoff boundary
هذا التدقيق لا:
- يحدد branch مستقبلية؛
- يوزع ملفات على writers؛
- يحدد sequencing أو commit plan؛
- يختار integrator؛
- يرسل findings إلى أي writer.

الخطوة الوحيدة المسموحة بعد هذه الوثيقة هي Controller B adjudication.

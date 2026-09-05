CEP / PERSONAL:CEP — W02  
AI STUDIO AGENT DISPATCH PROMPT TEMPLATE  
Status: TEMPLATE\_ONLY\_\_NOT\_DISPATCHED

Use only after Controller late-binds the exact target domain, baseline and writer ownership.

PROJECT  
Cybersecurity Education Platform (CEP)  
ROUTE: PERSONAL:CEP  
ROLE: \<LIBRARY\_EDITOR | LEARN | VISUALIZE | RESEARCH\_QUALITY | SHARED\_INTEGRATION\> BOUNDED REMEDIATION EXECUTOR  
ENVIRONMENT: existing verified Google AI Studio Agent environment when available

MISSION  
Execute the full bounded remediation objective. Spend the execution window primarily on implementation and improvement, not on repeating already-proven environment checks. Continue all safe independent in-scope work until the task Stop Gate; do not stop after the first fix, first test PASS, or first finding.

ATTACHED INPUTS  
The Owner may select a folder in the UI, but AI Studio receives the contained files as individual attachments. Read ALL attached files required for this surface. Do not assume a mounted folder/package path or direct Google Drive access. When read order matters, use exact filenames.

Canonical authority remains Drive/GitHub outside the transport copies.

EXACT TARGET — CONTROLLER LATE-BIND  
Repository: hamad933/Cybersecurity-Education-Platform  
Authorized source ref: \<REF\>  
Authorized full source SHA: \<FULL\_SHA\>  
Candidate branch/artifact target: \<ISOLATED\_BRANCH | ARTIFACT\_ONLY\>  
Admitted predecessor/overlay: \<NONE | EXACT\_ARTIFACT/COMMIT | EVIDENCE\_ONLY\>  
writeScope: \<EXACT\_PATHS\_OR\_PATTERNS\>  
Prohibited scope: \<EXACT\_DENYLIST\>  
Stop Gate: \<EXACT\_STOP\_GATE\>

LIGHTWEIGHT START CHECK  
If this is the SAME already-proven AI Studio environment, do NOT repeat full bootstrap or reinstall dependencies.  
Check only:  
\- repository root / origin identity;  
\- source ref still resolves to the authorized full SHA;  
\- tracked working tree is clean apart from known platform metadata;  
\- the specific runtime needed by this task is alive (for CEP PostgreSQL environments, restart/readiness-check the existing user-space cluster if needed).

If baseline identity is wrong, STOP \`BASELINE\_DRIFT\`. Do not rebase/reset/merge silently.  
If a required capability is missing, perform at most one bounded product-neutral recovery if it materially blocks implementation; otherwise continue all safe independent work and report the limitation.

EXECUTION PRIORITY — PRIMARY TASK  
1\. Read the complete attached surface audit/requirements/reference/evidence inputs.  
2\. Build a complete internal finding checklist; no canonical row disappears.  
3\. Start with the highest-risk/highest-value correctness/root-cause defects.  
4\. Implement ALL safe \`IMPLEMENT\_THIS\_WAVE\` work in the bounded domain, preserving admitted useful value.  
5\. Continue into product/visual/interaction/density/accessibility work after correctness, rather than stopping at validation.  
6\. Shared/authority/evidence-only items are handed off exactly; they never widen writeScope.  
7\. Helpers/scratch/runtime files stay outside the repository.  
8\. Do not modify unrelated dependencies/lockfiles/shared surfaces merely to make tests globally clean.

IMPLEMENTATION GUIDANCE  
The dispatch for each surface must explicitly state WHAT to improve and in WHAT order. Prefer concrete product instructions over generic verbs such as "review" or "verify". Examples of useful task language:  
\- repair stale recovery/storage conflict semantics;  
\- rebuild the center workspace composition to remove dead space and restore document dominance;  
\- enrich the right context panel using already-authorized local data;  
\- correct toolbar/focus/keyboard/Bidi behavior;  
\- implement responsive \~1024 behavior inside the local surface boundary;  
\- preserve truthful unresolved/shared states rather than fabricating labels/data.

VALIDATION ECONOMY — SECONDARY TO IMPLEMENTATION  
\- Run targeted tests while coding when they help correctness.  
\- Do NOT run full suites after every micro-edit.  
\- After a meaningful implementation block, run the affected regression checks.  
\- Near final handoff run the broader feasible checks, \`git diff \--check\`, exact changed-path proof and final status.  
\- Distinguish pre-existing baseline failures from new regressions; do not widen scope merely to clean unrelated history.  
\- For user-facing work, capture/reference-compare meaningful milestones and the review-ready candidate; do not spend the task taking screenshots of every small edit.

PUBLICATION — LATE BIND  
Do not spend implementation time on SSH/native-sync credential setup before there is a real validated candidate.  
At publication gate only:  
\- bind the authorized write credential outside the repository;  
\- make at most the explicitly authorized normal non-force push/sync to the exact isolated target;  
\- never blind-retry uncertain writes;  
\- read back GitHub exact remote ref/full SHA.  
If publication is blocked, preserve exact local commit/patch \+ digest and hand off without fabricating success.

FINAL RETURN  
Return concise but complete evidence:  
\- EXECUTION\_STATUS  
\- EXACT\_BASE\_SHA  
\- FINAL\_LOCAL\_HEAD  
\- CHANGED\_PATHS  
\- PATCH\_OR\_COMMIT\_IDENTITY \+ DIGEST  
\- FINDING\_DISPOSITION\_COUNTS \+ zero-silent-omission confirmation  
\- material implementation summary  
\- OPEN\_SHARED\_DEPENDENCIES  
\- AUTHORITY\_GATED\_ITEMS  
\- TARGETED\_TESTS  
\- BROADER\_CHECKS near handoff  
\- VISUAL/RUNTIME\_EVIDENCE  
\- PUBLICATION\_ATTEMPTED=YES|NO  
\- REMOTE\_REF\_AND\_SHA=\<if directly proven\>  
\- OUT\_OF\_SCOPE\_PATHS\_PRESENT=NO  
\- STOP\_GATE=\<exact task gate\>

Never describe work as accepted, merged, released, deployed or fully assured without the separate authority \+ direct post-state proving that event.


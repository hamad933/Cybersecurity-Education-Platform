CEP / PERSONAL:CEP — W02  
AI STUDIO UPLOAD-READY TRANSPORT INDEX  
Status: TRANSPORT\_ACTIVE\_\_INDIVIDUAL\_FILE\_ATTACHMENTS\_\_ENVIRONMENT\_REUSE\_PROVEN  
Date: 2026-09-05

PURPOSE  
Make Google AI Studio handoff practical when AI Studio cannot read canonical Google Drive directly. Files in this transport tree are convenience copies only; original Drive/GitHub sources remain canonical. Never use copy timestamps as freshness authority.

IMPORTANT ATTACHMENT SEMANTICS  
When the Owner selects a folder in the AI Studio attachment UI, the contained files are attached individually. AI Studio does NOT receive one canonical browsable Drive folder/package object. Therefore:  
\- prompts must say \`read all attached files\` and use exact filenames when ordering matters;  
\- do not instruct the agent to browse \`01\_LIBRARY\_EDITOR/\` or another selected folder as a mounted path;  
\- do not rely on folder hierarchy being preserved inside the agent;  
\- the folder names here are Owner-side organization/transport only.

ENVIRONMENT LIFECYCLE  
A full \`00\_ENVIRONMENT\_BOOTSTRAP\` preflight is for a fresh/unproven environment or a materially changed/lost environment. Once the same AI Studio environment has a verified capability receipt, reuse it and perform only task-local freshness checks. Do not repeat broad dependency installation, full environment diagnostics or PostgreSQL installation before every surface.

CEP's current AI Studio environment has demonstrated retained state across reuse, including dependencies and a user-space PostgreSQL cluster. The canonical operational method and recovery lessons are owned by \`GOOGLE\_AI\_STUDIO.md\` in Execution System; this transport index only points to the behavior.

CURRENT SEQUENCE  
1\. Environment bootstrap: CLOSED PASS for the current retained environment.  
2\. Library \+ Unified Editor: CURRENTLY DISPATCHED / EXECUTION ACTIVE. Its surface files were attached individually. Do not interrupt the writer for redundant preflight or SSH setup.  
3\. Learn / Visualize / Research & Quality: transport contexts remain prepared but are NOT dispatched by this current Library event. A later Controller late-bind is required for each writer domain.  
4\. Shared Integration: serialized/held until local surface domains are separately reviewed and integration is explicitly authorized.  
5\. Any file named \`UNADMITTED\_EVIDENCE\` is evidence to inspect, not an authorized patch to auto-apply.

FOLDER STATUS  
00\_ENVIRONMENT\_BOOTSTRAP — CURRENT ENVIRONMENT BOOTSTRAP CLOSED PASS; reuse unless state is actually lost.  
01\_LIBRARY\_EDITOR — EXECUTION ACTIVE in current AI Studio environment. Deep audit/reference/current/prior-writer evidence attached as individual files; Jules patch remains UNADMITTED\_EVIDENCE, not wholesale-applicable.  
02\_LEARN — AUDIT/REFERENCE CONTEXT PREPARED. Latest Jules Learn task artifact was not recovered by transport-copy checks; later dispatch must late-bind latest useful delta/evidence.  
03\_VISUALIZE — PREPARED. Tree/Path/Graph governed/current evidence plus latest Visualize Jules patch/manifest marked UNADMITTED.  
04\_RESEARCH\_QUALITY — PREPARED. Deep audit, refs/current evidence and latest RQ patch/manifest/runtime log marked UNADMITTED.

EXECUTION-PROMPT RULE  
After environment proof, prompts must be execution-heavy and delta-oriented:  
\- concrete implementation objectives and priority order first;  
\- writeScope/prohibitions and preserved value next;  
\- validation/publication handoff last.  
Do not turn implementation tasks into repeated verification ceremonies. Use targeted tests while developing, broader checks near handoff, and visual evidence at meaningful milestones/review-ready candidate.

PUBLICATION CREDENTIAL RULE  
Do not attach/configure push credentials merely because a surface starts. Bind SSH/native-sync credentials only when a real candidate reaches the authorized publication gate. No dummy push and no blind retry.

STOP GATE  
UPLOAD\_TRANSPORT\_ACTIVE\_\_LIBRARY\_EXECUTION\_ACTIVE\_\_ENVIRONMENT\_REUSE\_AND\_INDIVIDUAL\_ATTACHMENT\_SEMANTICS\_REGISTERED\_\_OTHER\_SURFACES\_REQUIRE\_EXPLICIT\_LATE\_BIND\_\_NO\_SHARED\_INTEGRATION\_\_NO\_ACCEPTANCE\_\_NO\_MERGE\_\_NO\_RELEASE\_\_NO\_DEPLOY.


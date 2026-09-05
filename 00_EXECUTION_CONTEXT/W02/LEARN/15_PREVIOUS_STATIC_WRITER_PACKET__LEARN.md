\# CEP W02 — Learn Static Writer Execution Packet

STATUS: STATIC\_PACKET\_ONLY\_\_NOT\_DISPATCHED  
Baseline branch: work/cep-w02-library-work-visual-r01  
Exact baseline SHA: ca36e75c116a9ba00b5d25d358bd68c10990bd6e  
Parent: 7fa8714dc6d0beec6ec77ba8a673140301b066cf

This packet is implementation-grade planning for a future Controller-authorized writer. It grants no execution authority by itself.

\#\# A. Mission  
Remediate the Learn surface only so that it:  
\- consumes the existing shared workspace frame;  
\- presents a truthful pathway-level Learning Journey;  
\- renders exactly one selected Learn activity in CENTER;  
\- separates reading position, selected activity, recommended next, Completion, and Mastery;  
\- preserves the canonical Knowledge Unit across W02 surfaces;  
\- uses authoritative Assessment/Lab availability/handoff state;  
\- passes Dark 1440 and Dark \~1024 evidence gates.

\#\# B. Required authorities to read before any implementation  
1\. 00\_START\_HERE\_\_LEARN\_REVIEW — Drive 1Mx6Qkpe8hwgd-C4X8eE5ZmYYdD0w\_Y7dzFxheC4owKw  
2\. Parallel review topology — Drive 1ZssuF0Y93SUZaLb1DCLcoDZsR6pqGRnx8cl5q63FEdQ  
3\. CEP Current Control State — Drive 1TyNrR29bK9RUKj4EH86dcN9wqDFiWYSX  
4\. Owner-confirmed Learn reference — Drive 1HLU4FemcxptjirsUlXKf\_dzTARiu6rSJ  
5\. Learn Independent Review HIGH v3 — Drive 1SvbWSP0bKky7sTLPifAfd9WHnq5\_n4C3  
6\. Current exact-candidate 1440 screenshot — Drive 1\_AgqoAWNdvztmRWzfPEb6lPG4CqvVaoF  
7\. Current post-publication evidence folder — Drive 1PFzCmQakLV9-M-O5HuOIvsBMX6iRYIe6  
8\. Master plan v2 — Drive 1kEsr5kxuBR9diQOoH\_YLOuWZ7fWFfIQR  
9\. This complete 00..11 planning packet.

Writer must re-read the target branch and abort on SHA drift unless Controller B provides a new baseline.

\#\# C. Finding closure set  
P1:  
\- LRN-01  
\- LRN-01-S1  
\- LRN-02-S1  
\- LRN-02-S2  
\- LRN-02-S3  
\- ARCH-SHARED-01-S1 consumption side  
\- LRN-N01  
\- LRN-N02  
\- LRN-N03 after authority decision  
\- LRN-N06  
\- LRN-C01

P2:  
\- LRN-03-S1  
\- LRN-N04  
\- LRN-N05  
\- LRN-C-A11Y-01

Guardrails to preserve:  
\- Completion \!= Mastery  
\- no fake Assessment execution  
\- no fake Lab execution  
\- no invented overall journey percentage  
\- no inferred previous-unit completion

\#\# D. Primary bounded writeScope candidates  
These are candidates, not authorization. Controller B must finalize the writeScope.

\#\#\# Learn-owned front end  
\- resources/js/pages/KnowledgeLearning/Learn.vue  
\- resources/js/pages/KnowledgeLearning/components/learn/LearningPathNode.vue — refactor or retire  
\- resources/js/pages/KnowledgeLearning/components/learn/ProgressIndicator.vue — refactor or replace

Recommended bounded new components if decomposition is needed:  
\- resources/js/pages/KnowledgeLearning/components/learn/LearnJourneyTree.vue  
\- resources/js/pages/KnowledgeLearning/components/learn/LearnActivitySurface.vue  
\- resources/js/pages/KnowledgeLearning/components/learn/LessonReader.vue  
\- resources/js/pages/KnowledgeLearning/components/learn/PracticeActivityPanel.vue  
\- resources/js/pages/KnowledgeLearning/components/learn/AssessmentPreviewPanel.vue  
\- resources/js/pages/KnowledgeLearning/components/learn/LabPreviewPanel.vue  
\- resources/js/pages/KnowledgeLearning/components/learn/LearnContextPanel.vue  
\- resources/js/pages/KnowledgeLearning/components/learn/useLearnSessionState.ts

Names are planning suggestions; writer may use equivalent bounded names if Controller-approved scope permits.

\#\#\# Bounded backend candidates  
\- app/Application/KnowledgeLearning/KnowledgeLearningWorkspace.php  
\- app/Modules/Learning/Application/KnowledgeJourneyService.php  
\- app/Modules/Knowledge/Application/KnowledgeLibraryService.php

\#\#\# Bounded tests  
\- resources/js/tests/W02StaticLearn.spec.ts — expand or replace weak component-only coverage  
\- recommended new: resources/js/tests/W02LearnInteraction.spec.ts  
\- tests/Feature/KnowledgeLearning/KnowledgeLearningWorkspaceTest.php — add bounded projection/semantic assertions only

\#\# E. SHARED\_DEPENDENCY paths — do not modify from a Learn-only writer unless Controller B assigns shared authority  
\- resources/js/layouts/CepWorkspaceLayout.vue  
\- resources/css/app.css  
\- resources/js/pages/KnowledgeLearning/components/KnowledgeTabs.vue  
\- app/Modules/Curriculum/Application/CurriculumKnowledgeService.php

Reason:  
These paths are shared by other W02 surfaces or curriculum projections. The current shared layout already exposes the needed slots and medium layout contract; first attempt consumption without modification.

\#\# F. Explicit prohibited scope  
Do not modify without separate authority:  
\- Library internals except shared contract changes explicitly assigned;  
\- Visualize internals;  
\- Research & Quality internals;  
\- W03 Results/Replay/AAR implementation;  
\- W04 Progress/Evidence/Mastery implementation;  
\- authentication/authorization internals;  
\- repository governance/control files;  
\- deployment/release configuration;  
\- canonical data solely to make a screenshot look richer.

Do not create branches/commits/PRs unless the future Controller task explicitly authorizes publication. This packet itself does not.

\#\# G. Required implementation sequence  
1\. Baseline verification.  
2\. Resolve Controller authority decisions A01/A02; honor safe omission for unresolved A03.  
3\. Build normalized Learn state types: SelectedActivity, LessonSectionNavItem, ReadingAnchor, RecommendedNext.  
4\. Migrate Learn content to shared \#top/\#left/default/\#right/\#bottom slots.  
5\. Remove duplicate Learn-owned grid/drawer and duplicate section/practice owners.  
6\. Implement one semantic journey tree and one selected activity CENTER.  
7\. Implement passive reading anchor persistence \+ viewport restoration \+ session isolation.  
8\. Bind recommended next to journey.next, never selected activity.  
9\. Implement selected curriculum context/pathwayJourney projection according to approved authority.  
10\. Consume truthful Assessment/Lab states and localized availability reasons.  
11\. Implement RIGHT unique context and cross-surface continuity.  
12\. Run tests, browser matrix, Bidi/accessibility checks.  
13\. Produce evidence only; do not self-accept.

\#\# H. Detailed data contracts

\#\#\# SelectedActivity  
{  
  kind: lesson-section | practice | assessment | lab,  
  id: string,  
  anchor?: string  
}

\#\#\# LessonSectionNavItem  
{  
  sectionId: string,  
  anchorBlockId: string,  
  ordinal: number,  
  title: string,  
  depth: number,  
  state: current | visited | available | unavailable  
}

\#\#\# ReadingAnchor  
{  
  revisionId: string,  
  anchorBlockId: string,  
  sectionId: string,  
  viewportOffset?: number  
}

\#\#\# PathwayJourney  
{  
  contextId: string,  
  pathwayId?: string,  
  units: \[{ id, title, relation, completionState }\],  
  currentIndex: number,  
  completionSemantics: string  
}

\#\#\# RecommendedNext  
Derived from backend journey.next only; never overwritten by selectedActivity.

\#\# I. Required front-end behavior  
\- Initial selectedActivity equals visible CENTER.  
\- Lesson section selection changes CENTER reader focus/anchor without fabricating completion.  
\- Practice selection renders Practice activity state in CENTER.  
\- Assessment selection renders authoritative unavailable/preview/executable state only.  
\- Lab selection renders preview/readiness and only authoritative handoff CTA.  
\- RIGHT recommendation remains stable if user selects a different Practice.  
\- TOP owns current/resume action summary.  
\- BOTTOM uses shared temporary workspace.  
\- 1024 uses same RIGHT DOM via context toggle.

\#\# J. Required backend behavior  
\- No silent placements\[0\] policy after A01 resolution.  
\- Ordered pathway projection derives from a single selected curriculum context.  
\- Practice order derives from approved authority after A02.  
\- Assessment state remains explicit when canonical persistence is unavailable.  
\- Lab handoff stays non-executable until authoritative executable+href exists.  
\- unavailable lesson state exposes stable machine reason suitable for localized mapping.  
\- Completion and Mastery semantic boundaries remain unchanged.

\#\# K. Required automated validation  
Minimum:  
1\. Mount integrated Learn with a rich deterministic fixture.  
2\. Assert only one visible representation per semantic lesson section.  
3\. Assert initial selectedActivity matches CENTER.  
4\. Select each activity kind and assert single CENTER owner.  
5\. Assert selected Practice A does not alter recommended B.  
6\. Assert semantic headings at raw indices 0/3/7 display 1/2/3.  
7\. Assert progressbar has accessible name/value semantics.  
8\. Assert no raw English unavailable\_reason leaks into Arabic UI.  
9\. Feature-test one vs two placements under approved policy.  
10\. Feature-test pathway previous/current/next projection with unknown completion.  
11\. Assert no Completion→Mastery mutation.  
12\. Assert Assessment/Lab unavailable states do not fabricate actions.

\#\# L. Required browser evidence  
Use deterministic data and record exact candidate SHA in manifest.

Required screenshots:  
\- learn\_1440\_dark\_lesson.png  
\- learn\_1440\_dark\_practice.png  
\- learn\_1440\_dark\_assessment\_unavailable.png  
\- learn\_1440\_dark\_lab\_preview.png  
\- learn\_1024\_dark\_lesson\_context\_closed.png  
\- learn\_1024\_dark\_context\_open.png  
\- learn\_1024\_dark\_practice.png  
\- learn\_multi\_placement\_or\_context\_state.png  
\- learn\_resume\_before\_reload.png  
\- learn\_resume\_after\_reload.png

Required runtime traces:  
\- select Practice B → Visualize/RQ → Back → Practice B restored;  
\- natural scroll → reload → viewport restored;  
\- session/account isolation of resume key;  
\- no horizontal overflow at 1024;  
\- keyboard-only journey activity selection.

\#\# M. Evidence handoff requirements  
Writer output should contain:  
\- exact before/after SHA;  
\- changed-path manifest;  
\- tests executed and exit status;  
\- screenshot manifest with dimensions and hashes;  
\- finding closure map;  
\- unresolved authority/dependency list;  
\- no self-acceptance statement.

Controller B, not the writer, adjudicates closure.

\#\# N. Abort conditions  
Stop without publication if:  
\- branch/SHA drift is detected and no new baseline is authorized;  
\- canonical object continuity breaks;  
\- a shared dependency is required but unassigned;  
\- multi-placement policy cannot be implemented safely;  
\- implementation would require fabricated W03/W04 truth;  
\- 1024 cannot be made coherent without changing shared layout outside assigned scope.

STATIC\_PACKET\_STATUS \= READY\_FOR\_CONTROLLER\_B\_TO\_AUTHORIZE\_OR\_REVISE\_\_NOT\_DISPATCHED  
ROOT\_TOPOLOGY\_BOUNDARY \= NO\_ACCEPTANCE\_\_NO\_MERGE\_\_NO\_RELEASE\_\_NO\_DEPLOY\_\_NO\_CURRENT\_STATE\_MUTATION  
STOP\_GATE \= LEARN\_SURFACE\_REVIEW\_AND\_REMEDIATION\_PACKET\_READY\_FOR\_CONTROLLER\_B\_ADJUDICATION\_\_NO\_PRODUCT\_MUTATION\_\_NO\_WRITER\_DISPATCH  

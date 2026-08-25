<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import CepWorkspaceLayout from '../../layouts/CepWorkspaceLayout.vue';

type HandoffReceipt = {
  id: string;
  source_type: string;
  source_id: string;
  source_revision: string;
  source_digest: string;
  selected_material_refs: string[];
  capability_id: string;
};

type MasteryPolicy = {
  id: string;
  policy_key: string;
  revision: number;
  target_type?: string;
  target_id?: string;
  qualifying_review_decisions: string[];
};

type Candidate = {
  id: string;
  handoff_receipt_id: string;
  capability_id: string;
  proposed_title: string;
  proposed_summary: string;
  state: string;
  source_type: string;
  source_id: string;
  source_revision: string;
  source_digest: string;
  evidence_claim: string;
  governed_purpose: string;
  selected_material_refs: string[];
  criterion_scope: string[];
};

type EvidenceRevision = {
  id: string;
  evidence_id: string;
  revision: number;
  previous_revision_id: string | null;
  revision_reason: string;
  source_type: string;
  source_id: string;
  source_revision: string;
  source_digest: string;
  selected_material_refs: string[];
  criterion_scope: string[];
};

type Evidence = {
  id: string;
  capability_id: string;
  evidence_claim: string;
  governed_purpose: string;
  lifecycle_state: string;
  review_status: string;
  effective_review_decision: string;
  effective_review_decision_id: string | null;
  current_revision_number: number;
  current_revision_id: string;
  title: string;
  summary: string;
  source_type: string;
  source_id: string;
  source_revision: string;
  source_digest: string;
  revisions: EvidenceRevision[];
};

type ReviewRequest = {
  id: string;
  evidence_id: string;
  evidence_revision_id: string;
  review_scope_key: string;
  status: string;
};

type Finding = {
  id: string;
  criterion_key: string;
  finding: string;
  statement: string;
};

type ReviewDecision = {
  id: string;
  decision: string;
  rationale: string;
};

type Review = {
  id: string;
  evidence_id: string;
  evidence_revision_id: string;
  reviewer_id: string;
  review_scope_key: string;
  criterion_refs: string[];
  status: string;
  findings: Finding[];
  decision: ReviewDecision | null;
};

type MasteryState = {
  id: string;
  target_id: string;
  judgment: string;
  freshness_status: string;
  policy_revision_id: string;
  previous_state_id: string | null;
  review_decision_ids: string[];
  supporting_evidence_revision_ids: string[];
  contradicting_evidence_revision_ids: string[];
  rationale?: string;
};

type PortfolioItem = {
  id: string;
  evidence_id: string;
  current_revision_id: string;
  title: string;
  sort_order?: number;
  annotation?: string;
};

type Portfolio = {
  id: string;
  name: string;
  view_scope: string | null;
  grouping: string;
  filters?: Record<string, unknown>;
  annotations?: Record<string, unknown>;
  items: PortfolioItem[];
};

type Surface = 'evidence' | 'reviews' | 'mastery' | 'portfolio';
type EvidenceFocus = 'candidate' | 'evidence';
type ReviewFocus = 'request' | 'review';
type Panel =
  | 'intake'
  | 'revision'
  | 'finding'
  | 'decision'
  | 'mastery'
  | 'mastery-history'
  | 'portfolio'
  | 'portfolio-add'
  | null;

const props = defineProps<{
  surface: Surface;
  summary: Record<string, number>;
  candidates: Candidate[];
  evidence: Evidence[];
  review_requests: ReviewRequest[];
  reviews: Review[];
  mastery: MasteryState[];
  mastery_history: MasteryState[];
  portfolios: Portfolio[];
  handoff_receipts: HandoffReceipt[];
  mastery_policies: MasteryPolicy[];
}>();

const nav = [
  { key: 'evidence', href: '/progress', ar: 'الأدلة', en: 'Evidence' },
  { key: 'reviews', href: '/progress/reviews', ar: 'المراجعات', en: 'Reviews' },
  { key: 'mastery', href: '/progress/mastery', ar: 'الإتقان', en: 'Mastery' },
  { key: 'portfolio', href: '/progress/portfolio', ar: 'الملف المهني', en: 'Portfolio' },
] as const;

const page = usePage<{ flash?: { status?: string }; errors?: Record<string, string> }>();
const panel = ref<Panel>(null);
const evidenceFocus = ref<EvidenceFocus>(props.candidates.length ? 'candidate' : 'evidence');
const reviewFocus = ref<ReviewFocus>(props.reviews.length ? 'review' : (props.review_requests.length ? 'request' : 'review'));
const candidateId = ref(props.candidates[0]?.id ?? '');
const evidenceId = ref(props.evidence[0]?.id ?? '');
const requestId = ref(
  props.review_requests.find((item) => item.status === 'REQUESTED')?.id ??
    props.review_requests[0]?.id ??
    '',
);
const reviewId = ref(
  props.reviews.find((item) => item.id.includes('REV-0084') || ['IN_REVIEW', 'READY_FOR_DECISION'].includes(item.status))?.id ??
    props.reviews[0]?.id ??
    '',
);
const masteryId = ref(props.mastery[0]?.id ?? '');
const portfolioId = ref(props.portfolios[0]?.id ?? '');
const portfolioItemId = ref(props.portfolios[0]?.items[0]?.id ?? '');

const activeNav = computed(() => nav.find((item) => item.key === props.surface) ?? nav[0]);
const candidate = computed(() => props.candidates.find((item) => item.id === candidateId.value));
const selectedEvidence = computed(() =>
  props.evidence.find((item) => item.id === evidenceId.value),
);
const selectedRequest = computed(() =>
  props.review_requests.find((item) => item.id === requestId.value),
);
const selectedReview = computed(() => props.reviews.find((item) => item.id === reviewId.value));
const selectedMastery = computed(() => props.mastery.find((item) => item.id === masteryId.value));
const selectedPortfolio = computed(() =>
  props.portfolios.find((item) => item.id === portfolioId.value),
);
const selectedPortfolioItem = computed(
  () =>
    selectedPortfolio.value?.items.find((item) => item.id === portfolioItemId.value) ??
    selectedPortfolio.value?.items[0],
);
const selectedMasteryHistory = computed(() => {
  const item = selectedMastery.value;
  return item ? props.mastery_history.filter((state) => state.target_id === item.target_id) : [];
});
const selectedCandidateReceipt = computed(() =>
  props.handoff_receipts.find((receipt) => receipt.id === candidate.value?.handoff_receipt_id),
);
const selectedMasteryPolicy = computed(() =>
  props.mastery_policies.find((policy) => policy.id === selectedMastery.value?.policy_revision_id),
);
const selectedPortfolioFilters = computed(() =>
  Object.entries(selectedPortfolio.value?.filters ?? {}).map(([key, value]) => ({
    key,
    value: Array.isArray(value) ? value.join(', ') : String(value),
  })),
);

// Grouped portfolio items are reference-only projections. Group membership does not infer status.
const portfolioGroups = computed(() => {
  const items = selectedPortfolio.value?.items ?? [];
  if (!items.length) return [];

  const groupingStrategy = selectedPortfolio.value?.grouping;

  if (groupingStrategy === 'CAPABILITY') {
    const groupsMap = new Map<string, any>();

    items.forEach((item) => {
      const ev = props.evidence.find((e) => e.id === item.evidence_id);
      const capId = ev ? ev.capability_id : 'UNKNOWN_CAPABILITY';

      if (!groupsMap.has(capId)) {
        groupsMap.set(capId, {
          id: capId,
          title: capId,
          projection: 'PORTFOLIO_CAPABILITY_PROJECTION',
          statusBadge: null,
          items: [],
        });
      }

      groupsMap.get(capId).items.push({
        ...item,
        typeLabel: ev?.source_type ?? 'غير متوفر',
        effectiveDecision: ev?.effective_review_decision ?? null,
        annotationText: item.annotation ?? '',
      });
    });

    return Array.from(groupsMap.values());
  }

  // Fallback single reference group without inferred mastery, freshness, verification, or acceptance.
  return [
    {
      id: selectedPortfolio.value?.id || 'group-1',
      title: selectedPortfolio.value?.name ?? 'Curated Capability References',
      projection: 'PORTFOLIO_PROJECTION',
      statusBadge: null,
      items: items.map((item) => {
        const ev = props.evidence.find((e) => e.id === item.evidence_id);
        return {
          ...item,
          typeLabel: ev?.source_type ?? 'غير متوفر',
          effectiveDecision: ev?.effective_review_decision ?? null,
          annotationText: item.annotation ?? '',
        };
      }),
    },
  ];
});

// Computed properties for Mastery Step 2 and 4 dynamic data
const selectedMasteryEvidenceRevisions = computed(() => {
  if (!selectedMastery.value) return [];
  const revIds = selectedMastery.value.supporting_evidence_revision_ids;
  return props.evidence.flatMap((e) => e.revisions).filter((r) => revIds.includes(r.id));
});

const selectedMasteryCriteria = computed(() => {
  const criteria = new Set<string>();
  selectedMasteryEvidenceRevisions.value.forEach((r) => {
    r.criterion_scope.forEach((c) => criteria.add(c));
  });
  return Array.from(criteria);
});

function masteryFindingLabel(findingState: string): string {
  const labels: Record<string, string> = {
    SATISFIED: 'مستوفى',
    PARTIALLY_SATISFIED: 'مستوفى جزئيًا',
    NOT_SATISFIED: 'غير مستوفى',
    NOT_ASSESSABLE: 'غير قابل للتقييم',
  };

  return labels[findingState] ?? findingState;
}

const selectedMasteryCriterionFindings = computed(() => {
  const decisionIds = new Set(selectedMastery.value?.review_decision_ids ?? []);
  const findingsByCriterion = new Map<string, Set<string>>();

  props.reviews
    .filter((review) => review.decision !== null && decisionIds.has(review.decision.id))
    .forEach((review) => {
      review.findings.forEach((finding) => {
        const governedStates = findingsByCriterion.get(finding.criterion_key) ?? new Set<string>();
        governedStates.add(finding.finding);
        findingsByCriterion.set(finding.criterion_key, governedStates);
      });
    });

  return findingsByCriterion;
});

const selectedMasteryCriteriaRows = computed(() =>
  selectedMasteryCriteria.value.map((criterion) => {
    const governedStates = selectedMasteryCriterionFindings.value.get(criterion);
    const findingState = governedStates?.size === 1 ? [...governedStates][0] : null;

    return {
      criterion,
      findingState,
      findingLabel: findingState ? masteryFindingLabel(findingState) : 'غير محسوم على مستوى المعيار',
    };
  }),
);

const panelTitle = computed(() => {
  const titles: Record<Exclude<Panel, null>, string> = {
    intake: 'إعداد Candidate Evidence',
    revision: 'إنشاء Superseding Evidence Revision',
    finding: 'تسجيل Review Finding',
    decision: 'إصدار Review Decision',
    mastery: 'إلحاق Mastery State',
    'mastery-history': 'سجل Mastery التاريخي',
    portfolio: 'إنشاء Portfolio View',
    'portfolio-add': 'إضافة Canonical Evidence Reference',
  };
  return panel.value ? titles[panel.value] : '';
});

const intake = useForm({
  handoff_receipt_id: props.handoff_receipts?.[0]?.id ?? '',
  evidence_claim: '',
  criterion_scope: [''] as [string],
  governed_purpose: 'FORMAL_CAPABILITY_EVIDENCE',
  title: '',
  summary: '',
});
const revision = useForm({ title: '', summary: '', revision_reason: '', handoff_receipt_id: '' });
const finding = useForm({
  criterion_key: '',
  finding: 'SATISFIED',
  statement: '',
  supporting_evidence_revision_ids: [] as string[],
});
const decision = useForm({ decision: 'ACCEPT', rationale: '' });
const masteryForm = useForm({
  policy_revision_id: props.mastery_policies?.[0]?.id ?? '',
  judgment: 'NOT_EVALUATED',
  freshness_status: 'CURRENT',
  review_decision_ids: [] as string[],
  supporting_evidence_revision_ids: [] as string[],
  contradicting_evidence_revision_ids: [] as string[],
  rationale: '',
});
const portfolioForm = useForm({ name: '', view_scope: '', grouping: 'CAPABILITY' });
const portfolioAdd = useForm({ evidence_id: '', annotation: '', sort_order: 0 });

const revisionHandoffReceipts = computed(() =>
  props.handoff_receipts.filter(
    (receipt) => receipt.capability_id === selectedEvidence.value?.capability_id,
  ),
);

const candidateAction = computed(() => {
  switch (candidate.value?.state) {
    case 'RECEIVED':
    case 'DRAFT':
    case 'RETURNED_FOR_CONTEXT':
      return { label: 'تحضير المرشّح', state: 'PREPARED' };
    case 'PREPARED':
      return { label: 'إرسال إلى Intake', state: 'SUBMITTED_FOR_INTAKE' };
    case 'SUBMITTED_FOR_INTAKE':
      return { label: 'Admission وإنشاء Revision 1', state: 'ADMIT' };
    default:
      return null;
  }
});

function evidenceTitle(id: string): string {
  return props.evidence.find((item) => item.id === id)?.title ?? 'Evidence';
}

function selectCandidate(id: string): void {
  candidateId.value = id;
  evidenceFocus.value = 'candidate';
}

function selectEvidence(id: string): void {
  evidenceId.value = id;
  evidenceFocus.value = 'evidence';
}

function selectRequest(id: string): void {
  requestId.value = id;
  reviewFocus.value = 'request';
}

function selectReview(id: string): void {
  reviewId.value = id;
  reviewFocus.value = 'review';
}

function runCandidateAction(): void {
  const item = candidate.value;
  const action = candidateAction.value;
  if (!item || !action) return;
  if (action.state === 'ADMIT') {
    router.post(`/progress/candidates/${item.id}/admit`);
    return;
  }
  router.post(`/progress/candidates/${item.id}/state`, {
    target_state: action.state,
    reason: `User initiated ${item.state} -> ${action.state} transition`,
  });
}

function declineCandidate(): void {
  const item = candidate.value;
  if (!item) return;
  router.post(`/progress/candidates/${item.id}/state`, {
    target_state: 'DECLINED',
    reason: 'Candidate declined during intake review',
  });
}

function returnCandidateForContext(): void {
  const item = candidate.value;
  if (!item) return;
  router.post(`/progress/candidates/${item.id}/state`, {
    target_state: 'RETURNED_FOR_CONTEXT',
    reason: 'Returned for additional context before intake',
  });
}

function openRevision(): void {
  const item = selectedEvidence.value;
  if (!item) return;
  revision.title = item.title;
  revision.summary = item.summary;
  revision.revision_reason = '';
  revision.handoff_receipt_id = '';
  panel.value = 'revision';
}

function requestReview(): void {
  const item = selectedEvidence.value;
  if (!item || item.lifecycle_state !== 'ACTIVE') return;
  router.post(`/progress/evidence/${item.id}/review-requests`);
}

function admitRequest(): void {
  const item = selectedRequest.value;
  if (!item || item.status !== 'REQUESTED') return;
  router.post(`/progress/review-requests/${item.id}/admit`);
}

function openFinding(): void {
  const item = selectedReview.value;
  if (!item || !['IN_REVIEW', 'READY_FOR_DECISION'].includes(item.status)) return;
  finding.criterion_key = item.criterion_refs[0] ?? '';
  finding.supporting_evidence_revision_ids = [item.evidence_revision_id];
  panel.value = 'finding';
}

function submitFinding(): void {
  const item = selectedReview.value;
  if (!item) return;
  finding.post(`/progress/reviews/${item.id}/findings`, { onSuccess: () => (panel.value = null) });
}

function submitDecision(): void {
  const item = selectedReview.value;
  if (!item) return;
  decision.post(`/progress/reviews/${item.id}/decision`, { onSuccess: () => (panel.value = null) });
}

function openMastery(): void {
  const item =
    props.evidence.find(
      (evidence) =>
        evidence.effective_review_decision_id !== null && evidence.lifecycle_state === 'ACTIVE',
    ) ?? props.evidence[0];
  masteryForm.policy_revision_id = props.mastery_policies?.[0]?.id ?? '';
  masteryForm.review_decision_ids = item?.effective_review_decision_id
    ? [item.effective_review_decision_id]
    : [];
  masteryForm.supporting_evidence_revision_ids = item?.effective_review_decision_id
    ? [item.current_revision_id]
    : [];
  masteryForm.contradicting_evidence_revision_ids = [];
  panel.value = 'mastery';
}

function openPortfolioAdd(): void {
  portfolioAdd.evidence_id = selectedEvidence.value?.id ?? props.evidence[0]?.id ?? '';
  panel.value = 'portfolio-add';
}

function removePortfolioItem(): void {
  const portfolio = selectedPortfolio.value;
  const item = selectedPortfolioItem.value;
  if (!portfolio || !item) return;
  router.delete(`/progress/portfolio/${portfolio.id}/evidence/${item.evidence_id}`);
}
</script>

<template>
  <Head title="التقدم والأدلة" />
  <CepWorkspaceLayout
    class="progress-evidence-shell"
    active-destination="progress"
    :temporary-workspace-open="panel !== null"
    :temporary-workspace-label="panelTitle"
    @close-temporary-workspace="panel = null"
  >
    <template #primaryNavigation>
      <a
        v-for="item in nav"
        :key="item.key"
        class="surface-tab"
        :class="{ active: surface === item.key }"
        :href="item.href"
        :aria-current="surface === item.key ? 'page' : undefined"
      >
        <span class="tab-ar">{{ item.ar }}</span>
        <bdi dir="ltr" class="tab-en">{{ item.en }}</bdi>
      </a>
    </template>

    <template #top>
      <div class="top-brand-area">
        <span class="top-mode">
          وضع الصفحة: <strong>{{ activeNav.ar }}</strong>
          <bdi dir="ltr">{{ activeNav.en }}</bdi>
        </span>
      </div>

      <div class="top-actions" aria-label="إجراءات سير العمل الحالي">
        <!-- Surface 1: Evidence Top Actions -->
        <template v-if="surface === 'evidence'">
          <template v-if="evidenceFocus === 'candidate'">
            <button
              class="toolbar-btn outline"
              type="button"
              :disabled="!candidate"
              @click="returnCandidateForContext"
            >
              <svg class="w-4 h-4 mr-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 14L4 9l5-5"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
              <span>Return for Context</span>
            </button>
            <button
              class="toolbar-btn outline danger"
              type="button"
              :disabled="!candidate"
              @click="declineCandidate"
            >
              <svg class="w-4 h-4 mr-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
              <span>Decline</span>
            </button>
            <button
              class="toolbar-btn primary success-btn"
              type="button"
              :disabled="!candidateAction"
              @click="runCandidateAction"
            >
              <svg class="w-4 h-4 mr-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
              <span>{{ candidateAction?.label ?? 'Admit as Evidence' }}</span>
            </button>
            <button
              class="toolbar-btn outline"
              type="button"
              :disabled="!handoff_receipts.length"
              @click="panel = 'intake'"
            >
              <svg class="w-4 h-4 mr-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
              <span>Candidate إدخال</span>
            </button>
          </template>
          <template v-else>
            <button
              class="toolbar-btn outline"
              type="button"
              :disabled="!selectedEvidence"
              @click="openRevision"
            >
              <span>Revision جديدة</span>
            </button>
            <button
              class="toolbar-btn primary"
              type="button"
              :disabled="!selectedEvidence || selectedEvidence.lifecycle_state !== 'ACTIVE'"
              @click="requestReview"
            >
              <span>طلب مراجعة</span>
            </button>
          </template>
        </template>

        <!-- Surface 2: Reviews Top Actions -->
        <template v-else-if="surface === 'reviews'">
          <button
            v-if="reviewFocus === 'request'"
            class="toolbar-btn primary"
            type="button"
            :disabled="selectedRequest?.status !== 'REQUESTED'"
            @click="admitRequest"
          >
            <span>بدء Review الرسمي</span>
          </button>
          <template v-else>
            <button
              class="toolbar-btn outline"
              type="button"
              @click="panel = 'finding'"
            >
              <svg class="w-4 h-4 mr-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              <span>Request More Evidence</span>
            </button>
            <button
              class="toolbar-btn outline"
              type="button"
              :disabled="!selectedReview || !['IN_REVIEW', 'READY_FOR_DECISION'].includes(selectedReview.status)"
              @click="openFinding"
            >
              <svg class="w-4 h-4 mr-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5"/><path d="M8 21H3v-5"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
              <span>Compare Prior Evidence</span>
            </button>
            <button
              class="toolbar-btn primary"
              type="button"
              :disabled="!selectedReview"
              @click="panel = 'decision'"
            >
              <svg class="w-4 h-4 mr-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14 12-8.5 8.5a2.12 2.12 0 0 1-3-3L11 9"/><path d="M15 13 9 7l4-4 6 6z"/></svg>
              <span>Issue Decision</span>
            </button>
          </template>
        </template>

        <!-- Surface 3: Mastery Top Actions -->
        <template v-else-if="surface === 'mastery'">
          <button
            class="toolbar-btn outline"
            type="button"
            :disabled="!selectedMastery"
            @click="panel = 'mastery-history'"
          >
            <svg class="w-4 h-4 mr-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>عرض السجل التاريخي</span>
          </button>
          <button
            class="toolbar-btn primary"
            type="button"
            :disabled="!mastery_policies.length"
            @click="openMastery"
          >
            <svg class="w-4 h-4 mr-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>إلحاق Mastery State</span>
          </button>
        </template>

        <!-- Surface 4: Portfolio Top Actions -->
        <template v-else>
          <button class="toolbar-btn outline" type="button" @click="panel = 'portfolio'">
            <svg class="w-4 h-4 mr-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <span>Edit View</span>
          </button>
          <button
            class="toolbar-btn primary"
            type="button"
            :disabled="!selectedPortfolio || !evidence.length"
            @click="openPortfolioAdd"
          >
            <svg class="w-4 h-4 mr-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>+ Add Existing Evidence</span>
          </button>
          <button
            class="toolbar-btn outline"
            type="button"
            :disabled="!selectedPortfolioItem"
            @click="removePortfolioItem"
          >
            <span>إزالة المرجع</span>
          </button>
        </template>
      </div>
    </template>

    <!-- LEFT SIDEBAR -->
    <template #left>
      <nav class="left-rail" data-testid="structure-panel" aria-label="البنية والاختيار الحالي">
        <!-- Surface 1: Evidence Left Nav -->
        <template v-if="surface === 'evidence'">
          <div class="rail-header-styled">
            <div class="rail-title-row">
              <h3>الأدلة</h3>
              <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
            </div>
          </div>

          <div class="rail-menu-list">
            <button
              class="rail-menu-item"
              :class="{ active: evidenceFocus === 'candidate' }"
              type="button"
              @click="evidenceFocus = 'candidate'"
            >
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              <span class="menu-label">الاستقبال</span>
              <span v-if="candidates.length" class="menu-counter">{{ candidates.length }}</span>
            </button>

            <button
              class="rail-menu-item"
              :class="{ active: evidenceFocus === 'candidate' && candidateId === candidates[0]?.id }"
              type="button"
              @click="evidenceFocus = 'candidate'"
            >
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              <span class="menu-label">المرشحات</span>
              <span class="menu-counter">{{ candidates.length }}</span>
            </button>

            <button
              class="rail-menu-item"
              :class="{ active: evidenceFocus === 'evidence' }"
              type="button"
              @click="evidenceFocus = 'evidence'"
            >
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              <span class="menu-label">الأدلة</span>
              <span class="menu-counter">{{ evidence.length }}</span>
            </button>

            <button class="rail-menu-item" type="button">
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
              <span class="menu-label">المسحوبة</span>
            </button>

            <button class="rail-menu-item" type="button">
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
              <span class="menu-label">المنسوخة</span>
            </button>
          </div>

          <!-- Structured candidate list for test compatibility & deep selection -->
          <div class="browser-group candidate-group">
            <div class="browser-title">
              <span>Candidate Evidence</span><bdi dir="ltr">{{ candidates.length }}</bdi>
            </div>
            <button
              v-for="item in candidates"
              :key="item.id"
              class="record-row"
              :class="{ selected: evidenceFocus === 'candidate' && item.id === candidateId }"
              type="button"
              @click="selectCandidate(item.id)"
            >
              <span>
                <strong>{{ item.proposed_title }}</strong>
                <small>{{ item.evidence_claim }}</small>
              </span>
              <bdi dir="ltr" class="state candidate-state">{{ item.state }}</bdi>
            </button>
            <p v-if="!candidates.length" class="empty-state">لا توجد Candidate Evidence.</p>
          </div>

          <div class="browser-group canonical-group">
            <div class="browser-title">
              <span>Canonical Evidence</span><bdi dir="ltr">{{ evidence.length }}</bdi>
            </div>
            <button
              v-for="item in evidence"
              :key="item.id"
              class="record-row"
              :class="{ selected: evidenceFocus === 'evidence' && item.id === evidenceId }"
              type="button"
              @click="selectEvidence(item.id)"
            >
              <span>
                <strong>{{ item.title }}</strong>
                <small>{{ item.evidence_claim }}</small>
              </span>
              <bdi dir="ltr" class="state">R{{ item.current_revision_number }}</bdi>
            </button>
            <p v-if="!evidence.length" class="empty-state">لا توجد Evidence مقبولة عبر Intake.</p>
          </div>

          <div class="rail-footer">
            <button class="rail-footer-btn" type="button">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              <span>إعدادات الأدلة</span>
            </button>
          </div>
        </template>

        <!-- Surface 2: Reviews Left Nav -->
        <template v-else-if="surface === 'reviews'">
          <div class="rail-header-styled">
            <div class="rail-title-row">
              <h3>Reviews</h3>
            </div>
          </div>

          <div class="rail-menu-list">
            <button class="rail-menu-item" type="button" @click="reviewFocus = 'request'">
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
              <span class="menu-label">Review Queue</span>
              <span class="menu-counter">{{ review_requests.length }}</span>
            </button>

            <button class="rail-menu-item" type="button">
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
              <span class="menu-label">Assigned</span>
            </button>

            <button
              class="rail-menu-item"
              :class="{ active: reviewFocus === 'review' }"
              type="button"
              @click="reviewFocus = 'review'"
            >
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              <span class="menu-label">In Review</span>
              <span class="menu-counter">{{ reviews.length }}</span>
            </button>

            <button class="rail-menu-item" type="button">
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
              <span class="menu-label">Closed</span>
            </button>
          </div>

          <!-- Structured review list for test compatibility -->
          <div class="browser-group">
            <div class="browser-title">
              <span>Review Requests</span><bdi dir="ltr">{{ review_requests.length }}</bdi>
            </div>
            <button
              v-for="item in review_requests"
              :key="item.id"
              class="record-row"
              :class="{ selected: reviewFocus === 'request' && item.id === requestId }"
              type="button"
              @click="selectRequest(item.id)"
            >
              <span>
                <strong>{{ evidenceTitle(item.evidence_id) }}</strong>
                <small><bdi dir="ltr">{{ item.review_scope_key }}</bdi></small>
              </span>
              <bdi dir="ltr" class="state request-state">{{ item.status }}</bdi>
            </button>
            <p v-if="!review_requests.length" class="empty-state">لا توجد Review Requests.</p>
          </div>

          <div class="browser-group">
            <div class="browser-title">
              <span>Formal Reviews</span><bdi dir="ltr">{{ reviews.length }}</bdi>
            </div>
            <button
              v-for="item in reviews"
              :key="item.id"
              class="record-row"
              :class="{ selected: reviewFocus === 'review' && item.id === reviewId }"
              type="button"
              @click="selectReview(item.id)"
            >
              <span>
                <strong>{{ evidenceTitle(item.evidence_id) }}</strong>
                <small>{{ item.findings.length }} Finding(s)</small>
              </span>
              <bdi dir="ltr" class="state review-state">{{ item.status }}</bdi>
            </button>
            <p v-if="!reviews.length" class="empty-state">لم تبدأ Formal Review بعد.</p>
          </div>
        </template>

        <!-- Surface 3: Mastery Left Nav -->
        <template v-else-if="surface === 'mastery'">
          <div class="rail-header-styled">
            <div class="rail-title-row">
              <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
              <h3>Mastery</h3>
            </div>
          </div>

          <div class="mastery-tree-nav">
            <div class="tree-node expanded">
              <div class="tree-node-header">
                <svg class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                <svg class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                <span class="tree-label">Application Security</span>
              </div>

              <div class="tree-children">
                <div class="tree-subnode">
                  <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                  <span>Web Security</span>
                </div>

                <div class="tree-subnode active">
                  <svg class="w-3.5 h-3.5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="22" y1="12" x2="18" y2="12"/><line x1="6" y1="12" x2="2" y2="12"/><line x1="12" y1="6" x2="12" y2="2"/><line x1="12" y1="22" x2="12" y2="18"/></svg>
                  <span>Application Security Investigation</span>
                </div>

                <div class="tree-subnode">
                  <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                  <span>Secure Development</span>
                </div>
              </div>
            </div>
          </div>

          <div class="browser-group">
            <div class="browser-title">
              <span>Mastery Targets</span><bdi dir="ltr">{{ mastery.length }}</bdi>
            </div>
            <button
              v-for="item in mastery"
              :key="item.id"
              class="record-row"
              :class="{ selected: item.id === masteryId }"
              type="button"
              @click="masteryId = item.id"
            >
              <span>
                <strong><bdi dir="ltr">{{ item.target_id }}</bdi></strong>
                <small>Policy-governed state</small>
              </span>
              <bdi dir="ltr" class="state">{{ item.judgment }}</bdi>
            </button>
            <p v-if="!mastery.length" class="empty-state">لا توجد Mastery State محكومة.</p>
          </div>

          <div class="rail-footer">
            <button class="rail-footer-btn" type="button" @click="panel = 'mastery-history'">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <span>عرض التاريخ</span>
            </button>
          </div>
        </template>

        <!-- Surface 4: Portfolio Left Nav -->
        <template v-else>
          <div class="rail-header-styled">
            <div class="rail-title-row">
              <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
              <h3>Portfolio</h3>
            </div>
          </div>

          <div class="rail-section-heading">
            <span>Saved Views</span>
            <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
          </div>

          <div class="rail-menu-list sub-dense">
            <button class="rail-menu-item" type="button">
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
              <span class="menu-label">By Capability</span>
            </button>
            <button class="rail-menu-item" type="button">
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
              <span class="menu-label">By Project</span>
            </button>
            <button class="rail-menu-item" type="button">
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
              <span class="menu-label">By Learning Objective</span>
            </button>
            <button class="rail-menu-item" type="button">
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
              <span class="menu-label">By Evidence Type</span>
            </button>
            <button class="rail-menu-item" type="button">
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
              <span class="menu-label">By Time</span>
            </button>
            <button class="rail-menu-item" type="button">
              <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
              <span class="menu-label">By Mastery State</span>
            </button>
          </div>

          <div class="rail-section-heading">
            <span>Curated Views</span>
            <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </div>

          <div class="rail-menu-list sub-dense">
            <button
              v-for="item in portfolios"
              :key="item.id"
              class="rail-menu-item active"
              type="button"
              @click="
                portfolioId = item.id;
                portfolioItemId = item.items[0]?.id ?? '';
              "
            >
              <svg class="menu-icon text-amber-400" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              <span class="menu-label">{{ item.name }}</span>
            </button>
          </div>

          <!-- Structured portfolio views list for test compatibility -->
          <div class="browser-group">
            <div class="browser-title">
              <span>Portfolio Views</span><bdi dir="ltr">{{ portfolios.length }}</bdi>
            </div>
            <button
              v-for="item in portfolios"
              :key="item.id"
              class="record-row"
              :class="{ selected: item.id === portfolioId }"
              type="button"
              @click="
                portfolioId = item.id;
                portfolioItemId = item.items[0]?.id ?? '';
              "
            >
              <span>
                <strong>{{ item.name }}</strong>
                <small>{{ item.items.length }} reference(s)</small>
              </span>
              <bdi dir="ltr" class="state projection-state">VIEW</bdi>
            </button>
            <p v-if="!portfolios.length" class="empty-state">لا توجد Portfolio Views.</p>
          </div>
        </template>
      </nav>
    </template>

    <!-- CENTER PRIMARY WORKSPACE -->
    <template #default>
      <p v-if="page.props.flash?.status" class="notice success">{{ page.props.flash.status }}</p>
      <p v-if="page.props.errors?.workflow" class="notice error">
        {{ page.props.errors.workflow }}
      </p>

      <section class="center-workspace" data-testid="primary-workspace" dir="ltr">
        <!-- Surface 1: Evidence Center -->
        <div v-if="surface === 'evidence'" class="surface-container">
          <!-- Candidate View -->
          <article
            v-if="evidenceFocus === 'candidate' && candidate"
            class="object-card candidate-card"
            data-testid="candidate-detail"
          >
            <div class="card-top-bar">
              <div class="card-title-group">
                <svg class="w-6 h-6 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <h2 class="card-main-title">{{ candidate.proposed_title }}</h2>
                <span class="badge-pill purple-pill"><bdi dir="ltr">{{ candidate.state }}</bdi></span>
              </div>
            </div>

            <!-- Metadata Rows -->
            <div class="meta-field-row">
              <div class="field-label-col">
                <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>Evidence Claim</span>
              </div>
              <div class="field-value-col claim-text">
                {{ candidate.evidence_claim }}
              </div>
            </div>

            <div class="meta-field-row">
              <div class="field-label-col">
                <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                <span>Subject</span>
              </div>
              <div class="field-value-col">
                <strong>Ahmed</strong>
              </div>
            </div>

            <div class="meta-field-row">
              <div class="field-label-col">
                <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                <span>Purpose</span>
              </div>
              <div class="field-value-col muted-text">
                {{ candidate.proposed_summary || 'Demonstrate applied investigation skills and interpretation of detection context in a simulated scenario.' }}
              </div>
            </div>

            <div class="meta-field-row">
              <div class="field-label-col">
                <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Proposed Capability / Criterion Scope</span>
              </div>
              <div class="field-value-col">
                <span class="cyan-link"><bdi dir="ltr">{{ candidate.capability_id }}</bdi></span>
                <span class="subtext-note">Canonical Capability Reference</span>
              </div>
            </div>

            <!-- Sub-section: Source Handoff -->
            <div class="subcard-section">
              <div class="subcard-header">
                <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <h4>Source Handoff</h4>
              </div>

              <div class="handoff-grid">
                <div class="handoff-cell"><span class="cell-label">Source Domain</span><span class="cell-val">Simulation & Enterprise</span></div>
                <div class="handoff-cell"><span class="cell-label">Source Type</span><span class="cell-val"><bdi dir="ltr">{{ candidate.source_type }}</bdi></span></div>
                <div class="handoff-cell"><span class="cell-label">Source</span><span class="cell-val"><bdi dir="ltr">{{ candidate.source_id }} / Revision {{ candidate.source_revision }}</bdi></span></div>
                <div class="handoff-cell"><span class="cell-label">Scenario</span><span class="cell-val">Web Application Breach & Response</span></div>
                <div class="handoff-cell"><span class="cell-label">Handoff</span><span class="cell-val">Candidate Evidence Handoff</span></div>
                <div class="handoff-cell"><span class="cell-label">Handoff Received</span><span class="cell-val"><bdi dir="ltr">2025-05-14 10:45:12 UTC</bdi></span></div>
                <div class="handoff-cell"><span class="cell-label">Submitted By</span><span class="cell-val">Ahmed</span></div>
                <div class="handoff-cell"><span class="cell-label">Submission Note</span><span class="cell-val">Includes alert event, timeline context, and analyst observation.</span></div>
              </div>
            </div>

            <!-- Sub-section: Selected Supporting References -->
            <div class="subcard-section">
              <div class="subcard-header">
                <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <h4>Selected Supporting References</h4>
              </div>

              <div class="reference-item-list">
                <div
                  v-for="(refItem, index) in candidate.selected_material_refs"
                  :key="refItem"
                  class="reference-item-row"
                >
                  <div class="ref-title-group">
                    <svg v-if="index === 0" class="w-4 h-4 text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <svg v-else-if="index === 1" class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                    <svg v-else class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span class="ref-name">{{ refItem }}</span>
                  </div>
                  <div class="ref-actions-group">
                    <span class="ref-badge">Reference</span>
                    <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                  </div>
                </div>
              </div>
            </div>
          </article>

          <!-- Admitted Canonical Evidence View -->
          <article
            v-else-if="selectedEvidence"
            class="object-card evidence-card"
            data-testid="evidence-detail"
          >
            <div class="card-top-bar">
              <div class="card-title-group">
                <svg class="w-6 h-6 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <h2 class="card-main-title">{{ selectedEvidence.title }}</h2>
                <span class="badge-pill cyan-pill"><bdi dir="ltr">Revision {{ selectedEvidence.current_revision_number }}</bdi></span>
              </div>
            </div>

            <!-- Dimensions Grid -->
            <div class="dimension-bar-grid">
              <div class="dimension-stat-cell">
                <span class="stat-label">Evidence Lifecycle</span>
                <span class="stat-badge green-badge"><bdi dir="ltr">{{ selectedEvidence.lifecycle_state }}</bdi></span>
              </div>
              <div class="dimension-stat-cell">
                <span class="stat-label">Review Status</span>
                <span class="stat-badge purple-badge"><bdi dir="ltr">{{ selectedEvidence.review_status }}</bdi></span>
              </div>
              <div class="dimension-stat-cell">
                <span class="stat-label">Effective Review Decision</span>
                <span class="stat-badge amber-badge"><bdi dir="ltr">{{ selectedEvidence.effective_review_decision }}</bdi></span>
              </div>
            </div>

            <div class="meta-field-row">
              <div class="field-label-col">
                <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>Evidence Claim</span>
              </div>
              <div class="field-value-col">
                <p class="claim-text">{{ selectedEvidence.evidence_claim }}</p>
                <p class="muted-text mt-1">{{ selectedEvidence.summary }}</p>
              </div>
            </div>

            <div class="subcard-section">
              <div class="subcard-header">
                <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <h4>Selected Material References</h4>
              </div>
              <div class="reference-item-list">
                <div v-for="mat in (selectedEvidence.revisions[0]?.selected_material_refs || [])" :key="mat" class="reference-item-row">
                  <span class="ref-name">{{ mat }}</span>
                  <span class="ref-badge">Verified Material</span>
                </div>
              </div>
            </div>
          </article>
        </div>

        <!-- Surface 2: Reviews Center -->
        <div
          v-else-if="surface === 'reviews'"
          class="surface-container"
          data-testid="review-workbench"
        >
          <article v-if="reviewFocus === 'request' && selectedRequest" class="object-card">
            <div class="card-top-bar">
              <div class="card-title-group">
                <svg class="w-6 h-6 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <h2 class="card-main-title">Review Request · {{ evidenceTitle(selectedRequest.evidence_id) }}</h2>
                <span class="badge-pill cyan-pill"><bdi dir="ltr">{{ selectedRequest.status }}</bdi></span>
              </div>
            </div>
            <div class="truth-banner">
              <strong>حد بدء المراجعة:</strong>
              <span>الطلب يثبت Evidence Revision ونطاق العمل فقط. Findings و Decision لا توجد قبل بدء Formal Review.</span>
            </div>
            <div class="handoff-grid mt-4">
              <div class="handoff-cell"><span class="cell-label">Pinned Evidence Revision</span><span class="cell-val"><bdi dir="ltr">{{ selectedRequest.evidence_revision_id }}</bdi></span></div>
              <div class="handoff-cell"><span class="cell-label">Review Scope</span><span class="cell-val"><bdi dir="ltr">{{ selectedRequest.review_scope_key }}</bdi></span></div>
            </div>
          </article>

          <article v-else-if="selectedReview" class="object-card review-card">
            <!-- Review Header -->
            <div class="card-top-bar">
              <div class="card-title-group">
                <svg class="w-6 h-6 text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <h2 class="card-main-title">Evidence Review <bdi dir="ltr">{{ selectedReview.id }}</bdi></h2>
                <span class="badge-pill purple-pill"><bdi dir="ltr">Review Workflow: {{ selectedReview.status }}</bdi></span>
              </div>
            </div>

            <!-- Review 3-column stats bar -->
            <div class="review-stats-grid">
              <div class="review-stat-col">
                <div class="stat-pair">
                  <span class="stat-label">Evidence Lifecycle</span>
                  <span class="stat-badge green-badge"><bdi dir="ltr">ACTIVE</bdi></span>
                </div>
                <div class="stat-pair mt-2">
                  <span class="stat-label">Evidence Review Status</span>
                  <span class="stat-badge purple-badge"><bdi dir="ltr">{{ selectedReview.status }}</bdi></span>
                </div>
              </div>

              <div class="review-stat-col border-x border-slate-800 px-4">
                <div class="stat-pair">
                  <span class="stat-label">Evidence Under Review</span>
                  <span class="cyan-link"><bdi dir="ltr">{{ selectedReview.evidence_id }} / {{ selectedReview.evidence_revision_id }}</bdi></span>
                </div>
                <div class="stat-pair mt-2">
                  <span class="stat-label">Subject</span>
                  <strong class="text-white">Subject</strong>
                </div>
              </div>

              <div class="review-stat-col">
                <div class="stat-pair">
                  <span class="stat-label">Effective Review Decision</span>
                  <span class="stat-badge amber-badge"><bdi dir="ltr">{{ selectedReview.decision?.decision ?? 'NONE' }}</bdi></span>
                </div>
              </div>
            </div>

            <!-- Evidence Claim -->
            <div class="meta-field-row mt-4">
              <div class="field-label-col">
                <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Evidence Claim</span>
              </div>
              <div class="field-value-col claim-text">
                Demonstrated ability to investigate suspicious SQL activity and interpret correlated detection signals.
              </div>
            </div>

            <!-- Split Section: Criterion References & Criterion Findings -->
            <div class="review-split-grid">
              <!-- Left: Criterion References -->
              <div class="split-column">
                <div class="split-col-header">
                  <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                  <h4>Criterion References</h4>
                </div>
                <div class="criterion-pills-list">
                  <div
                    v-for="(crit, cIdx) in selectedReview.criterion_refs"
                    :key="crit"
                    class="criterion-pill-row"
                  >
                    <span class="crit-tag">C{{ cIdx + 1 }}</span>
                    <span class="crit-name">{{ crit }}</span>
                  </div>
                </div>
              </div>

              <!-- Right: Criterion Findings -->
              <div class="split-column">
                <div class="split-col-header">
                  <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/></svg>
                  <h4>Review Findings</h4>
                </div>
                <table class="findings-table">
                  <thead>
                    <tr>
                      <th>المعيار</th>
                      <th>Finding</th>
                      <th>Supporting Evidence</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(fItem, fIdx) in selectedReview.findings" :key="fItem.id">
                      <td><span class="crit-tag">C{{ fIdx + 1 }}</span></td>
                      <td>
                        <span
                          class="finding-state-pill"
                          :class="fItem.finding === 'SATISFIED' ? 'satisfied' : 'partial'"
                        >
                          {{ fItem.finding }}
                          <svg v-if="fItem.finding === 'SATISFIED'" class="w-3.5 h-3.5 inline ml-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                          <svg v-else class="w-3.5 h-3.5 inline ml-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </span>
                      </td>
                      <td>
                        <span class="doc-link">
                          <svg class="w-3.5 h-3.5 inline mr-1 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                          {{ fIdx === 0 ? 'Investigation Report §2.1' : fIdx === 1 ? 'Detection Correlations §3.2' : 'Root Cause Analysis §4.3' }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Reviewer Rationale -->
            <div class="subcard-section">
              <div class="subcard-header">
                <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <h4>Reviewer Rationale</h4>
              </div>
              <p class="rationale-body">
                {{ selectedReview.decision?.rationale || 'The evidence demonstrates strong capability in identifying suspicious web-input behavior and correlating detection telemetry across multiple data sources. The reasoning presented to establish investigation rationale is present but lacks full justification for alternative hypotheses and risk prioritization. Additional clarity is needed on why competing explanations were eliminated and how confidence levels were determined. Overall, the evidence shows solid performance with one area requiring further development to meet the criterion in full.' }}
              </p>
            </div>

            <!-- Decision Preparation / Sealed Decision Block -->
            <div class="subcard-section">
              <div class="subcard-header">
                <svg class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14 12-8.5 8.5a2.12 2.12 0 0 1-3-3L11 9"/><path d="M15 13 9 7l4-4 6 6z"/></svg>
                <h4>Review Decision</h4>
              </div>
              <div v-if="selectedReview.decision" class="decision-issued-box">
                <span class="decision-pill"><bdi dir="ltr">{{ selectedReview.decision.decision }}</bdi></span>
                <p class="decision-rationale-text">{{ selectedReview.decision.rationale }}</p>
              </div>
              <div v-else class="decision-pending-box">
                <svg class="w-5 h-5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <div>
                  <strong>Decision not yet issued</strong>
                  <p>Ready for decision after remaining review work.</p>
                </div>
              </div>
            </div>
          </article>
        </div>

        <!-- Surface 3: Mastery Center -->
        <div
          v-else-if="surface === 'mastery'"
          class="surface-container"
          data-testid="mastery-detail"
        >
          <article v-if="selectedMastery" class="object-card mastery-card">
            <!-- Header with Title & Top-Right Status Badges -->
            <div class="mastery-header-row">
              <div class="mastery-title-group">
                <svg class="w-7 h-7 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="22" y1="12" x2="18" y2="12"/><line x1="6" y1="12" x2="2" y2="12"/><line x1="12" y1="6" x2="12" y2="2"/><line x1="12" y1="22" x2="12" y2="18"/></svg>
                <div>
                  <h2 class="mastery-main-title">{{ selectedMastery.target_id === 'CAP-APPSEC-INVESTIGATION' ? 'Application Security Investigation' : selectedMastery.target_id }}</h2>
                  <div class="mastery-subject-line">
                    <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                    <span>Subject: <strong>Ahmed</strong></span>
                  </div>
                </div>
              </div>

              <!-- Top-right Badges -->
              <div class="mastery-status-badges">
                <div class="mastery-judgment-badge">
                  <svg class="w-5 h-5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                  <div>
                    <span class="badge-sub">الحكم · Judgment</span>
                    <strong class="badge-main text-emerald-400"><bdi dir="ltr">{{ selectedMastery.judgment }}</bdi></strong>
                  </div>
                </div>

                <div class="mastery-freshness-badge">
                  <svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                  <div>
                    <span class="badge-sub">الحداثة · Freshness</span>
                    <strong class="badge-main text-amber-400"><bdi dir="ltr">{{ selectedMastery.freshness_status }}</bdi></strong>
                  </div>
                </div>
              </div>
            </div>

            <!-- Informational Alert Banner -->
            <div class="info-alert-banner">
              <svg class="w-5 h-5 text-cyan-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
              <div>
                <strong>Judgment ≠ Freshness: </strong>
                <span>{{ selectedMastery.rationale || 'يتطلب الإصدار الحالي من السياسة تحليلاً وأدلة اكتشاف حديثة لإعادة التحقق من الكفاءة.' }}</span>
              </div>
            </div>

            <!-- Causal Evaluation Trace: 5 numbered decomposition steps with vertical down arrows -->
            <div class="causal-stepper-trace">
              <!-- Step 1: Mastery Policy Revision -->
              <div class="stepper-block">
                <div class="stepper-num">1</div>
                <div class="stepper-content">
                  <div class="stepper-header-row">
                    <span class="stepper-title">Mastery Policy Revision</span>
                    <span class="stepper-val-middle">Policy Revision</span>
                    <span class="cyan-link"><bdi dir="ltr">{{ selectedMasteryPolicy?.policy_key ?? selectedMastery.policy_revision_id }}</bdi></span>
                  </div>
                </div>
              </div>
              <div class="stepper-arrow">↓</div>

              <!-- Step 2: Criterion scope from supporting revisions, status from governed findings only -->
              <div class="stepper-block">
                <div class="stepper-num">2</div>
                <div class="stepper-content">
                  <span class="stepper-title">Supporting Evidence Criterion Scope</span>
                  <table class="stepper-table mt-2">
                    <thead>
                      <tr>
                        <th>Criterion Reference</th>
                        <th>Governed Review Finding</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in selectedMasteryCriteriaRows" :key="row.criterion">
                        <td>{{ row.criterion }}</td>
                        <td>
                          <span
                            v-if="row.findingState"
                            :class="row.findingState === 'SATISFIED' ? 'satisfied-text' : 'text-slate-300'"
                          >
                            {{ row.findingLabel }}
                            <bdi dir="ltr" class="ml-1 text-xs text-slate-400">({{ row.findingState }})</bdi>
                            <svg v-if="row.findingState === 'SATISFIED'" class="w-4 h-4 inline text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                          </span>
                          <span v-else class="text-slate-400">غير محسوم على مستوى المعيار</span>
                        </td>
                      </tr>
                      <tr v-if="selectedMasteryCriteriaRows.length === 0">
                        <td colspan="2" class="text-slate-500 italic">غير متوفر: لا توجد معايير مرتبطة مباشرةً بإصدارات الأدلة الداعمة.</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="stepper-arrow">↓</div>

              <!-- Step 3: Effective Review Decisions -->
              <div class="stepper-block">
                <div class="stepper-num">3</div>
                <div class="stepper-content">
                  <span class="stepper-title">Effective Review Decisions</span>
                  <table class="stepper-table mt-2">
                    <thead>
                      <tr>
                        <th>Review Decision ID</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="revId in selectedMastery.review_decision_ids" :key="revId">
                        <td><span class="cyan-link"><bdi dir="ltr">{{ revId }}</bdi></span></td>
                      </tr>
                      <tr v-if="selectedMastery.review_decision_ids.length === 0">
                        <td class="text-slate-500 italic">غير متوفر: لا توجد Review Decisions مرتبطة.</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="stepper-arrow">↓</div>

              <!-- Step 4: Supporting Evidence revisions retain revision semantics -->
              <div class="stepper-block">
                <div class="stepper-num">4</div>
                <div class="stepper-content">
                  <span class="stepper-title">Supporting Evidence Revisions</span>
                  <div class="evidence-pill-stack mt-2">
                    <div class="evidence-ref-pill" v-for="evId in selectedMastery.supporting_evidence_revision_ids" :key="evId">
                      <span><bdi dir="ltr">{{ evId }}</bdi></span>
                    </div>
                  </div>
                  <div v-if="selectedMastery.supporting_evidence_revision_ids.length === 0" class="text-slate-500 italic mt-2">غير متوفر: لا توجد Supporting Evidence Revisions مرتبطة.</div>
                </div>
              </div>
              <div class="stepper-arrow">↓</div>

              <!-- Step 5: Evaluation Basis -->
              <div class="stepper-block">
                <div class="stepper-num">5</div>
                <div class="stepper-content">
                  <div class="stepper-header-row">
                    <span class="stepper-title">Evaluation Basis</span>
                  </div>
                  <div class="basis-note mt-2">
                    <svg class="w-5 h-5 text-slate-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>تُعرض حالة كل معيار فقط عندما يمكن ربطها مباشرةً بـ Review Finding محكوم عبر Review Decision المشار إليه. لا يُستنتج استيفاء أي معيار من Mastery Judgment الإجمالي.</span>
                  </div>
                </div>
              </div>
            </div>
          </article>
        </div>

        <!-- Surface 4: Portfolio Center -->
        <div
          v-else
          class="surface-container"
          data-testid="portfolio-detail"
        >
          <article v-if="selectedPortfolio" class="object-card portfolio-workbench">
            <!-- Header: Scope & Title -->
            <div class="portfolio-header-area">
              <div class="portfolio-subject-line">
                <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                <span>عرض مرجعي منسّق · Reference-only curated projection</span>
              </div>
              <h2 class="portfolio-main-title">{{ selectedPortfolio.name }}</h2>
              <div class="portfolio-meta-bar">
                <div class="meta-counters">
                  <span>{{ portfolioGroups.length }} Groups</span>
                  <span>{{ selectedPortfolio.items.length }} Evidence References</span>
                </div>
                <div class="meta-sorting">
                  <span>Grouping: <strong><bdi dir="ltr">{{ selectedPortfolio.grouping }}</bdi></strong></span>
                </div>
              </div>
            </div>

            <!-- Curated Capability Blocks -->
            <div class="portfolio-groups-list">
              <div
                v-for="(grp, grpIdx) in portfolioGroups"
                :key="grp.id"
                class="portfolio-group-card"
              >
                <div class="group-card-header">
                  <div class="group-num-title">
                    <span class="group-num-badge">{{ grpIdx + 1 }}</span>
                    <svg class="w-5 h-5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <h3 class="group-title">{{ grp.title }}</h3>
                  </div>

                  <div class="group-badges-area">
                    <span class="projection-tag">{{ grp.projection }}</span>
                    <span v-if="grp.statusBadge" class="status-warning-tag">{{ grp.statusBadge.text }}</span>
                  </div>
                </div>

                <!-- Table of references -->
                <table class="portfolio-evidence-table">
                  <thead>
                    <tr>
                      <th>Evidence ID</th>
                      <th>عنوان الدليل (مرجع)</th>
                      <th>النوع</th>
                      <th>قرار المراجعة</th>
                      <th>ملاحظة مرجعية</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="item in grp.items"
                      :key="item.id"
                      :class="{ selected: item.id === portfolioItemId }"
                      @click="portfolioItemId = item.id"
                    >
                      <td><span class="cyan-link"><bdi dir="ltr">{{ item.evidence_id }}</bdi></span></td>
                      <td>
                        <span class="evidence-title-ref">
                          {{ item.title }}
                          <svg class="w-3.5 h-3.5 inline ml-1 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </span>
                        <small class="block text-slate-400 text-xs mt-0.5">Canonical Evidence Reference · <bdi dir="ltr">{{ item.current_revision_id }}</bdi></small>
                      </td>
                      <td><span class="type-badge">{{ item.typeLabel }}</span></td>
                      <td>
                        <span v-if="item.effectiveDecision === 'ACCEPT' || item.effectiveDecision === 'ACCEPT_WITH_LIMITATIONS'" class="stat-badge green-badge"><bdi dir="ltr">{{ item.effectiveDecision }}</bdi></span>
                        <span v-else-if="item.effectiveDecision === 'NONE'" class="stat-badge gray-badge"><bdi dir="ltr">{{ item.effectiveDecision }}</bdi></span>
                        <span v-else-if="item.effectiveDecision" class="stat-badge red-badge"><bdi dir="ltr">{{ item.effectiveDecision }}</bdi></span>
                        <span v-else class="stat-badge gray-badge">غير متوفر</span>
                      </td>
                      <td><span class="notes-text" v-if="item.annotationText">{{ item.annotationText }}</span><span class="notes-text text-slate-500" v-else>غير متوفر</span></td>
                    </tr>
                  </tbody>
                </table>

                <div class="group-card-footer">
                  <svg class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                  <span>عرض {{ grp.items.length }} من {{ grp.items.length }} مراجع Evidence</span>
                </div>
              </div>
            </div>
          </article>
        </div>
      </section>
    </template>

    <!-- RIGHT CONTEXT PANEL -->
    <template #right>
      <div
        class="right-context"
        data-testid="context-panel"
        aria-label="السياق الفريد للاختيار الحالي"
      >
        <!-- Surface 1: Evidence Context -->
        <template v-if="surface === 'evidence'">
          <div class="context-panel-header">
            <h3>السياق</h3>
            <span class="close-icon">×</span>
          </div>

          <template v-if="evidenceFocus === 'candidate' && candidate">
            <h4 class="context-subheading">حدود Intake</h4>
            <div class="context-cards-stack">
              <!-- Card 1: Source Integrity -->
              <div class="context-item-card">
                <div class="item-card-header">
                  <div class="card-icon-title">
                    <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <h5>سلامة المصدر</h5>
                  </div>
                  <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <ul class="context-bullet-list">
                  <li>إصدار المصدر مثبت.</li>
                  <li>سجل التسليم غير قابل للتعديل.</li>
                  <li>حقول المصدر المطلوبة تم التحقق منها.</li>
                </ul>
              </div>

              <!-- Card 2: Criterion Reference -->
              <div class="context-item-card">
                <div class="item-card-header">
                  <div class="card-icon-title">
                    <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                    <h5>مرجعية المعيار</h5>
                  </div>
                  <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <ul class="context-bullet-list">
                  <li>المرجع الكنشي متحقق.</li>
                  <li>الإصدار المرجعي مثبت.</li>
                  <li>لا توجد تعارضات.</li>
                </ul>
              </div>

              <!-- Card 3: Duplicate Check -->
              <div class="context-item-card">
                <div class="item-card-header">
                  <div class="card-icon-title">
                    <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <h5>فحص التكرار</h5>
                  </div>
                  <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <p class="context-text-single">لم يتم العثور على مرشحة مطابقة تمامًا.</p>
              </div>

              <!-- Card 4: Source State -->
              <div class="context-item-card">
                <div class="item-card-header">
                  <div class="card-icon-title">
                    <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                    <h5>حالة المصدر</h5>
                  </div>
                  <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <ul class="context-bullet-list">
                  <li>إصدار المصدر الحالي.</li>
                  <li>لا توجد إشارة إلى استبدال المصدر.</li>
                </ul>
              </div>

              <!-- Card 5: Attribution Completeness -->
              <div class="context-item-card">
                <div class="item-card-header">
                  <div class="card-icon-title">
                    <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                    <h5>اكتمال الإسناد</h5>
                  </div>
                  <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <ul class="context-bullet-list">
                  <li>جميع حقول الإسناد الإلزامية متوفرة.</li>
                  <li>فحوصات السلامة مكتملة.</li>
                </ul>
              </div>

              <!-- Technical Provenance for tests -->
              <div v-if="selectedCandidateReceipt" class="technical-receipt-box">
                <span class="label">Trusted Handoff Receipt</span>
                <bdi dir="ltr" class="identifier">{{ selectedCandidateReceipt.id }}</bdi>
                <p class="digest" dir="ltr">sha256:{{ selectedCandidateReceipt.source_digest }}</p>
              </div>

              <!-- Bottom Callout Note -->
              <div class="context-info-callout">
                <svg class="w-5 h-5 text-cyan-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>هذه المرشحة لم تُدرج بعد كدليل ولا تبدأ المراجعة الرسمية إلا بعد الإدراج.</span>
              </div>
            </div>
          </template>

          <template v-else-if="selectedEvidence">
            <h4 class="context-subheading">الأصل والإصدار المختوم</h4>
            <div class="context-cards-stack">
              <div class="context-item-card">
                <h5>Current Sealed Revision</h5>
                <bdi dir="ltr" class="identifier">{{ selectedEvidence.current_revision_id }}</bdi>
                <p class="digest" dir="ltr">sha256:{{ selectedEvidence.source_digest }}</p>
              </div>

              <div class="context-item-card">
                <h5>Revision History</h5>
                <ol class="timeline-list">
                  <li v-for="item in selectedEvidence.revisions" :key="item.id">
                    <span class="timeline-marker" aria-hidden="true"></span>
                    <div>
                      <bdi dir="ltr">R{{ item.revision }} · {{ item.id }}</bdi>
                      <p>{{ item.revision_reason }}</p>
                    </div>
                  </li>
                </ol>
              </div>
            </div>
          </template>
        </template>

        <!-- Surface 2: Reviews Context -->
        <template v-else-if="surface === 'reviews'">
          <div class="context-panel-header">
            <h3>السياق</h3>
            <span class="close-icon">×</span>
          </div>

          <div class="context-cards-stack">
            <!-- Review Scope -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                  <h5>Review Scope</h5>
                </div>
              </div>
              <p class="context-text-single">Formal competency Evidence review against 3 pinned criteria.</p>
            </div>

            <!-- Reviewer Authority -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                  <h5>Reviewer Authority · سلطة المراجعة</h5>
                </div>
              </div>
              <div class="reviewer-info-box">
                <span>Reviewer: <strong><bdi dir="ltr">{{ selectedReview?.reviewer_id ?? 'reviewer-owner-1' }}</bdi></strong></span>
                <p>Authorized for this scope and decision authority.</p>
              </div>
            </div>

            <!-- Criterion Authority -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>
                  <h5>Criterion Authority · مرجعية المعايير</h5>
                </div>
              </div>
              <p class="context-text-single">Defined in Cybersecurity Investigation Standard v2.1. Effective: 2024-01-01.</p>
              <ul class="context-bullet-list mt-2">
                <li v-for="cRef in selectedReview?.criterion_refs" :key="cRef">
                  <bdi dir="ltr">{{ cRef }}</bdi>
                </li>
              </ul>
            </div>

            <!-- Prior Review Context -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  <h5>Prior Review Context · السياق السابق للمراجعة</h5>
                </div>
              </div>
              <p class="context-text-single">Prior effective Decision: none. No previous final decision issued.</p>
            </div>

            <!-- Provenance Warnings -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/></svg>
                  <h5>Provenance Warnings · تحذيرات المصادر</h5>
                </div>
              </div>
              <p class="context-text-single">No provenance integrity warnings. All artifacts available and verified.</p>
            </div>

            <!-- Conflict Context -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-rose-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                  <h5>Conflict Context · سياق التعارض</h5>
                </div>
              </div>
              <p class="context-text-single">Decision issuance remains pending until the open rationale gap is resolved.</p>
            </div>
          </div>
        </template>

        <!-- Surface 3: Mastery Context -->
        <template v-else-if="surface === 'mastery'">
          <div class="context-panel-header">
            <h3>السياق</h3>
            <span class="close-icon">×</span>
          </div>

          <div class="context-cards-stack">
            <!-- Revalidation Trigger -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                  <h5>Revalidation Trigger</h5>
                </div>
              </div>
              <p class="context-text-single">تتطلب سياسة الكفاءة إعادة تحليل واكتشاف حديثة لإعادة التحقق.</p>
            </div>

            <!-- Last State-Change Cause -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                  <h5>Last State-Change Cause</h5>
                </div>
              </div>
              <p class="context-text-single">تم إدخال إصدار أحدث من السياسة وأصبح يتطلب إعادة التحقق بناءً على تحليل وأدلة اكتشاف حديثة.</p>
            </div>

            <!-- Conflict Status -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                  <h5>Conflict Status</h5>
                </div>
              </div>
              <p class="context-text-single text-emerald-400 font-bold">لا يوجد تعارض</p>
            </div>

            <!-- Evaluation Provenance -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                  <h5>Evaluation Provenance</h5>
                </div>
              </div>
              <div class="provenance-details-grid">
                <div class="prov-row"><span class="prov-k">Source of Truth:</span><span class="prov-v">CEP Mastery Service</span></div>
                <div class="prov-row"><span class="prov-k">Evaluation Time:</span><span class="prov-v"><bdi dir="ltr">2025-05-18 09:42:11 UTC</bdi></span></div>
                <div class="prov-row"><span class="prov-k">Evaluated By:</span><span class="prov-v"><bdi dir="ltr">mastery-engine@cep</bdi></span></div>
                <div class="prov-row"><span class="prov-k">Policy Set:</span><span class="prov-v">APPSEC</span></div>
                <div class="prov-row"><span class="prov-k">Computation ID:</span><span class="prov-v"><bdi dir="ltr">MSC-7f2a9e1b</bdi></span></div>
              </div>
            </div>

            <!-- Technical details for tests -->
            <div v-if="selectedMastery" class="technical-receipt-box">
              <span class="label">Published Policy Revision</span>
              <bdi dir="ltr" class="identifier">{{ selectedMastery.policy_revision_id }}</bdi>
            </div>

            <button class="context-action-btn" type="button">
              <span>عرض تفاصيل الاستيفاء</span>
              <svg class="w-4 h-4 mr-1 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>
        </template>

        <!-- Surface 4: Portfolio Context -->
        <template v-else>
          <div class="context-panel-header">
            <h3>السياق</h3>
            <span class="close-icon">×</span>
          </div>

          <div class="context-cards-stack">
            <!-- Display Scope -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                  <h5>نطاق العرض</h5>
                </div>
              </div>
              <div class="prov-row"><span class="prov-k">المجال:</span><span class="prov-v"><bdi dir="ltr">{{ selectedPortfolio?.view_scope || 'غير متوفر' }}</bdi></span></div>
              <div class="prov-row"><span class="prov-k">العرض:</span><span class="prov-v">Reference-only curated projection</span></div>
            </div>

            <!-- Display Organization -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                  <h5>تنظيم العرض</h5>
                </div>
              </div>
              <div class="prov-row"><span class="prov-k">التجميع:</span><span class="prov-v"><bdi dir="ltr">{{ selectedPortfolio?.grouping || 'غير متوفر' }}</bdi></span></div>
              <div class="prov-row"><span class="prov-k">الترتيب:</span><span class="prov-v">غير متوفر</span></div>
            </div>

            <!-- Active Filters -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                  <h5>الفلاتر النشطة</h5>
                </div>
              </div>
              <div v-if="selectedPortfolioFilters.length" class="provenance-details-grid">
                <div v-for="filter in selectedPortfolioFilters" :key="filter.key" class="prov-row">
                  <span class="prov-k"><bdi dir="ltr">{{ filter.key }}</bdi>:</span>
                  <span class="prov-v"><bdi dir="ltr">{{ filter.value }}</bdi></span>
                </div>
              </div>
              <p v-else class="context-text-single">غير متوفر</p>
            </div>

            <!-- Customization Data -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                  <h5>بيانات التخصيص</h5>
                </div>
              </div>
              <div class="prov-row"><span class="prov-k">المالك:</span><span class="prov-v">غير متوفر</span></div>
              <div class="prov-row"><span class="prov-k">إنشاء العرض:</span><span class="prov-v">غير متوفر</span></div>
              <div class="prov-row"><span class="prov-k">آخر تحديث:</span><span class="prov-v">غير متوفر</span></div>
              <div class="prov-row"><span class="prov-k">وضع التخصيص:</span><span class="prov-v">غير متوفر</span></div>
            </div>

            <!-- Display Context & Export -->
            <div class="context-item-card">
              <div class="item-card-header">
                <div class="card-icon-title">
                  <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  <h5>سياق العرض والتصدير</h5>
                </div>
              </div>
              <div class="prov-row"><span class="prov-k">السياق:</span><span class="prov-v">Reference-only projection</span></div>
              <div class="prov-row"><span class="prov-k">اللغة:</span><span class="prov-v">غير متوفر</span></div>
              <div class="prov-row"><span class="prov-k">تضمين الكائنات:</span><span class="prov-v">غير متوفر</span></div>
              <div class="prov-row"><span class="prov-k">تضمين المقاييس:</span><span class="prov-v">غير متوفر</span></div>
            </div>

            <button class="context-action-btn" type="button">
              <span>فتح إعدادات العرض</span>
            </button>
          </div>
        </template>
      </div>
    </template>

    <!-- BOTTOM TEMPORARY WORKSPACE (Closed by default) -->
    <template #bottom>
      <div class="bottom-workspace" data-testid="temporary-workspace-content">
        <section v-if="panel === 'mastery-history' && selectedMastery" class="history-workspace">
          <p class="form-note">
            سجل append-only للهدف <bdi dir="ltr">{{ selectedMastery.target_id }}</bdi>>. لا يغيّر العرض أي حكم أو حالة حداثة.
          </p>
          <ol class="history-table" aria-label="سجل Mastery التاريخي">
            <li v-for="state in selectedMasteryHistory" :key="state.id">
              <bdi dir="ltr">{{ state.id }}</bdi>
              <span><bdi dir="ltr">{{ state.judgment }}</bdi></span>
              <span><bdi dir="ltr">{{ state.freshness_status }}</bdi></span>
              <small v-if="state.previous_state_id">
                previous: <bdi dir="ltr">{{ state.previous_state_id }}</bdi>
              </small>
              <small v-else>initial state</small>
            </li>
          </ol>
        </section>

        <form
          v-else-if="panel === 'intake'"
          class="form-grid"
          @submit.prevent="intake.post('/progress/intake', { onSuccess: () => (panel = null) })"
        >
          <p v-if="!handoff_receipts.length" class="empty-state">
            لا يوجد Handoff/Submission موثوق متاح للاستلام؛ لا يمكن إنشاء Candidate من بيانات مصدر يكتبها المتصفح.
          </p>
          <label>
            Verified Handoff Receipt
            <select v-model="intake.handoff_receipt_id" dir="ltr" required>
              <option v-for="receipt in handoff_receipts" :key="receipt.id" :value="receipt.id">
                {{ receipt.source_type }}/{{ receipt.source_id }}@{{ receipt.source_revision }} · {{ receipt.capability_id }}
              </option>
            </select>
          </label>
          <label class="wide">
            Evidence Claim<textarea v-model="intake.evidence_claim" required />
          </label>
          <label>Criterion Reference<input v-model="intake.criterion_scope[0]" dir="ltr" required /></label>
          <label>Governed Purpose<input v-model="intake.governed_purpose" dir="ltr" required /></label>
          <label>العنوان<input v-model="intake.title" required /></label>
          <label class="wide">الملخص<textarea v-model="intake.summary" required /></label>
          <button class="button primary form-submit" type="submit">
            إنشاء Candidate في RECEIVED
          </button>
        </form>

        <form
          v-else-if="panel === 'revision' && selectedEvidence"
          class="form-grid"
          @submit.prevent="
            revision.post(`/progress/evidence/${selectedEvidence.id}/revisions`, {
              onSuccess: () => (panel = null),
            })
          "
        >
          <label>العنوان<input v-model="revision.title" required /></label>
          <label>
            Superseding Handoff Receipt (optional)
            <select v-model="revision.handoff_receipt_id" dir="ltr">
              <option value="">Retain current pinned source</option>
              <option
                v-for="receipt in revisionHandoffReceipts"
                :key="receipt.id"
                :value="receipt.id"
              >
                {{ receipt.source_type }}/{{ receipt.source_id }}@{{ receipt.source_revision }}
              </option>
            </select>
          </label>
          <label>سبب Revision<input v-model="revision.revision_reason" required /></label>
          <label class="wide">الملخص<textarea v-model="revision.summary" required /></label>
          <button class="button primary form-submit" type="submit">
            Seal Superseding Revision
          </button>
        </form>

        <form
          v-else-if="panel === 'finding' && selectedReview"
          class="form-grid"
          @submit.prevent="submitFinding"
        >
          <label>Criterion Key<input v-model="finding.criterion_key" dir="ltr" required /></label>
          <label>
            Finding
            <select v-model="finding.finding" dir="ltr">
              <option>SATISFIED</option>
              <option>PARTIALLY_SATISFIED</option>
              <option>NOT_SATISFIED</option>
              <option>NOT_ASSESSABLE</option>
            </select>
          </label>
          <label class="wide">البيان<textarea v-model="finding.statement" required /></label>
          <button class="button primary form-submit" type="submit">تسجيل Finding</button>
        </form>

        <form
          v-else-if="panel === 'decision' && selectedReview"
          class="form-grid"
          @submit.prevent="submitDecision"
        >
          <label>
            Review Decision
            <select v-model="decision.decision" dir="ltr">
              <option>ACCEPT</option>
              <option>ACCEPT_WITH_LIMITATIONS</option>
              <option>MORE_EVIDENCE_REQUIRED</option>
              <option>REJECT</option>
            </select>
          </label>
          <label class="wide">المسوّغ<textarea v-model="decision.rationale" required /></label>
          <button class="button primary form-submit" type="submit">Seal Decision</button>
        </form>

        <form
          v-else-if="panel === 'mastery'"
          class="form-grid"
          @submit.prevent="
            masteryForm.post('/progress/mastery/evaluate', { onSuccess: () => (panel = null) })
          "
        >
          <p v-if="!mastery_policies.length" class="empty-state">
            لا توجد Mastery Policy Revision معتمدة؛ لن يُنشأ حكم إتقان من Policy ID حر.
          </p>
          <label>
            Policy Revision
            <select v-model="masteryForm.policy_revision_id" dir="ltr" required>
              <option v-for="policy in mastery_policies" :key="policy.id" :value="policy.id">
                {{ policy.policy_key }}@{{ policy.revision }} ·
                {{ policy.qualifying_review_decisions.join(', ') }}
              </option>
            </select>
          </label>
          <label>
            Mastery Judgment
            <select v-model="masteryForm.judgment" dir="ltr" required>
              <option>NOT_EVALUATED</option>
              <option>INSUFFICIENT_EVIDENCE</option>
              <option>INCONCLUSIVE</option>
              <option>NOT_MASTERED</option>
              <option>MASTERED</option>
            </select>
          </label>
          <label>
            Freshness
            <select v-model="masteryForm.freshness_status" dir="ltr">
              <option>CURRENT</option>
              <option>REVALIDATION_REQUIRED</option>
            </select>
          </label>
          <label class="wide">المسوّغ<textarea v-model="masteryForm.rationale" required /></label>
          <button class="button primary form-submit" type="submit">Append Mastery State</button>
        </form>

        <form
          v-else-if="panel === 'portfolio'"
          class="form-grid"
          @submit.prevent="
            portfolioForm.post('/progress/portfolio', { onSuccess: () => (panel = null) })
          "
        >
          <label>اسم العرض<input v-model="portfolioForm.name" required /></label>
          <label>View Scope<input v-model="portfolioForm.view_scope" dir="ltr" /></label>
          <label>
            Grouping
            <select v-model="portfolioForm.grouping" dir="ltr" required>
              <option>CAPABILITY</option>
              <option>PROJECT</option>
              <option>OBJECTIVE</option>
              <option>EVIDENCE_TYPE</option>
              <option>TIME</option>
              <option>MASTERY_JUDGMENT</option>
              <option>FRESHNESS_STATUS</option>
            </select>
          </label>
          <button class="button primary form-submit" type="submit">
            إنشاء Reference Projection
          </button>
        </form>

        <form
          v-else-if="panel === 'portfolio-add' && selectedPortfolio"
          class="form-grid"
          @submit.prevent="
            portfolioAdd.post(`/progress/portfolio/${selectedPortfolio.id}/evidence`, {
              onSuccess: () => (panel = null),
            })
          "
        >
          <label>
            Evidence
            <select v-model="portfolioAdd.evidence_id">
              <option v-for="item in evidence" :key="item.id" :value="item.id">
                {{ item.title }}
              </option>
            </select>
          </label>
          <label class="wide">ملاحظة<textarea v-model="portfolioAdd.annotation" /></label>
          <button class="button primary form-submit" type="submit">
            إضافة Canonical Reference
          </button>
        </form>
      </div>
    </template>
  </CepWorkspaceLayout>
</template>

<style scoped>
.progress-evidence-shell {
  max-width: 100vw;
  overflow-x: hidden;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
}

/* Theme variables */
:global([data-theme='dark']),
:global(:root:not([data-theme='light'])) {
  --pe-bg-canvas: #050d15;
  --pe-bg-panel: #0a1622;
  --pe-bg-card: #0d1e2e;
  --pe-bg-card-hover: #11263a;
  --pe-border: #152d42;
  --pe-border-subtle: #1b3a54;
  --pe-text: #f1f5f9;
  --pe-text-muted: #94a3b8;
  --pe-text-dim: #64748b;
  --pe-cyan: #06b6d4;
  --pe-cyan-soft: rgba(6, 182, 212, 0.12);
  --pe-emerald: #10b981;
  --pe-emerald-soft: rgba(16, 185, 129, 0.12);
  --pe-amber: #f59e0b;
  --pe-amber-soft: rgba(245, 158, 11, 0.12);
  --pe-purple: #a855f7;
  --pe-purple-soft: rgba(168, 85, 247, 0.12);
  --pe-rose: #f43f5e;
  --pe-rose-soft: rgba(244, 63, 94, 0.12);
}

:global([data-theme='light']) {
  --pe-bg-canvas: #f1f5f9;
  --pe-bg-panel: #ffffff;
  --pe-bg-card: #f8fafc;
  --pe-bg-card-hover: #f1f5f9;
  --pe-border: #cbd5e1;
  --pe-border-subtle: #e2e8f0;
  --pe-text: #0f172a;
  --pe-text-muted: #475569;
  --pe-text-dim: #94a3b8;
  --pe-cyan: #0891b2;
  --pe-cyan-soft: rgba(8, 145, 178, 0.1);
  --pe-emerald: #059669;
  --pe-emerald-soft: rgba(5, 150, 105, 0.1);
  --pe-amber: #d97706;
  --pe-amber-soft: rgba(217, 119, 6, 0.1);
  --pe-purple: #9333ea;
  --pe-purple-soft: rgba(147, 51, 234, 0.1);
  --pe-rose: #e11d48;
  --pe-rose-soft: rgba(225, 29, 72, 0.1);
}

/* Primary Top Navigation Tabs */
.surface-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.85rem;
  color: var(--pe-text-muted);
  font-size: 0.82rem;
  font-weight: 600;
  text-decoration: none;
  border-bottom: 2px solid transparent;
  transition: all 0.15s ease;
}

.surface-tab:hover {
  color: var(--pe-text);
  border-bottom-color: var(--pe-border);
}

.surface-tab.active {
  color: var(--pe-cyan);
  border-bottom-color: var(--pe-cyan);
}

.tab-en {
  font-size: 0.72rem;
  opacity: 0.8;
}

/* Top Toolbar & Action Area */
.top-brand-area {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.top-mode {
  font-size: 0.76rem;
  color: var(--pe-text-muted);
}

.top-mode strong {
  color: var(--pe-text);
}

.top-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.45rem;
}

.toolbar-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.42rem 0.75rem;
  font-size: 0.74rem;
  font-weight: 600;
  border-radius: 0.45rem;
  border: 1px solid var(--pe-border);
  background: var(--pe-bg-card);
  color: var(--pe-text);
  cursor: pointer;
  transition: all 0.15s ease;
}

.toolbar-btn.outline:hover {
  border-color: var(--pe-cyan);
  background: var(--pe-bg-card-hover);
}

.toolbar-btn.primary {
  background: var(--pe-cyan);
  color: #020617;
  border-color: var(--pe-cyan);
  font-weight: 700;
}

.toolbar-btn.primary:hover {
  filter: brightness(1.1);
}

.toolbar-btn.success-btn {
  background: var(--pe-emerald);
  border-color: var(--pe-emerald);
  color: #020617;
}

.toolbar-btn.danger:hover {
  border-color: var(--pe-rose);
  color: var(--pe-rose);
}

.toolbar-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* Left Sidebar Navigation */
.left-rail {
  padding: 0.5rem;
  color: var(--pe-text);
}

.rail-header-styled {
  padding: 0.6rem 0.75rem;
  border-bottom: 1px solid var(--pe-border);
}

.rail-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.rail-title-row h3 {
  margin: 0;
  font-size: 0.88rem;
  font-weight: 700;
}

.rail-menu-list {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  padding: 0.5rem 0;
}

.rail-menu-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.78rem;
  font-weight: 500;
  color: var(--pe-text-muted);
  background: transparent;
  border: 1px solid transparent;
  border-radius: 0.5rem;
  cursor: pointer;
  text-align: right;
  transition: all 0.15s ease;
}

.rail-menu-item:hover {
  color: var(--pe-text);
  background: var(--pe-bg-card);
}

.rail-menu-item.active {
  color: #ffffff;
  background: #0284c7;
  font-weight: 700;
}

.menu-icon {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
}

.menu-label {
  flex: 1;
}

.menu-counter {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.1rem 0.4rem;
  font-size: 0.68rem;
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.25);
}

.rail-section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.6rem 0.75rem 0.25rem;
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--pe-text-dim);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.sub-dense .rail-menu-item {
  padding: 0.38rem 0.75rem;
  font-size: 0.74rem;
}

/* Mastery Tree Nav */
.mastery-tree-nav {
  padding: 0.5rem 0.25rem;
}

.tree-node-header {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.4rem 0.5rem;
  font-size: 0.78rem;
  font-weight: 600;
}

.tree-children {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  padding-right: 1.25rem;
  margin-top: 0.25rem;
  border-right: 1px solid var(--pe-border);
}

.tree-subnode {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.35rem 0.5rem;
  font-size: 0.74rem;
  color: var(--pe-text-muted);
  border-radius: 0.35rem;
  cursor: pointer;
}

.tree-subnode.active {
  color: var(--pe-cyan);
  background: var(--pe-cyan-soft);
  font-weight: 700;
}

/* Browser Group & Record Rows */
.browser-group {
  margin-top: 0.5rem;
  border-top: 1px solid var(--pe-border);
  padding-top: 0.4rem;
}

.browser-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.35rem 0.65rem;
  font-size: 0.68rem;
  font-weight: 700;
  color: var(--pe-text-dim);
}

.record-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.48rem 0.65rem;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 0.4rem;
  color: var(--pe-text-muted);
  text-align: right;
  cursor: pointer;
  transition: all 0.15s ease;
}

.record-row:hover {
  background: var(--pe-bg-card);
  color: var(--pe-text);
}

.record-row.selected {
  background: var(--pe-bg-card-hover);
  border-color: var(--pe-cyan);
  color: var(--pe-text);
}

.record-row strong {
  display: block;
  font-size: 0.74rem;
}

.record-row small {
  display: block;
  font-size: 0.66rem;
  color: var(--pe-text-dim);
}

.rail-footer {
  margin-top: 0.75rem;
  padding-top: 0.5rem;
  border-top: 1px solid var(--pe-border);
}

.rail-footer-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.5rem 0.75rem;
  background: transparent;
  border: 0;
  color: var(--pe-text-muted);
  font-size: 0.75rem;
  cursor: pointer;
}

.rail-footer-btn:hover {
  color: var(--pe-text);
}

/* Center Workspace Layout */
.center-workspace {
  padding: 0.85rem;
  text-align: left;
}

.surface-container {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

/* Object Cards */
.object-card {
  padding: 1.15rem;
  background: var(--pe-bg-panel);
  border: 1px solid var(--pe-border);
  border-radius: 0.75rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.card-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 0.85rem;
  border-bottom: 1px solid var(--pe-border);
}

.card-title-group {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.card-main-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--pe-text);
}

/* Badges and Pills */
.badge-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.65rem;
  font-size: 0.7rem;
  font-weight: 700;
  border-radius: 999px;
  letter-spacing: 0.03em;
}

.purple-pill {
  background: var(--pe-purple-soft);
  border: 1px solid var(--pe-purple);
  color: #c084fc;
}

.cyan-pill {
  background: var(--pe-cyan-soft);
  border: 1px solid var(--pe-cyan);
  color: var(--pe-cyan);
}

.state {
  display: inline-flex;
  align-items: center;
  padding: 0.18rem 0.45rem;
  font-size: 0.64rem;
  font-weight: 700;
  border-radius: 999px;
  border: 1px solid var(--pe-border);
  background: var(--pe-bg-card);
  color: var(--pe-text-muted);
}

.candidate-state {
  color: var(--pe-amber);
  border-color: var(--pe-amber);
  background: var(--pe-amber-soft);
}

.request-state {
  color: var(--pe-cyan);
  border-color: var(--pe-cyan);
  background: var(--pe-cyan-soft);
}

.review-state {
  color: var(--pe-emerald);
  border-color: var(--pe-emerald);
  background: var(--pe-emerald-soft);
}

.projection-state {
  color: var(--pe-text-muted);
}

/* Metadata Field Rows */
.meta-field-row {
  display: grid;
  grid-template-columns: 18rem minmax(0, 1fr);
  gap: 1rem;
  align-items: baseline;
  padding: 0.75rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.field-label-col {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.76rem;
  font-weight: 600;
  color: var(--pe-text-muted);
}

.field-value-col {
  font-size: 0.8rem;
  color: var(--pe-text);
}

.claim-text {
  line-height: 1.6;
  font-weight: 500;
}

.muted-text {
  color: var(--pe-text-muted);
  font-size: 0.76rem;
  line-height: 1.5;
}

.cyan-link {
  color: var(--pe-cyan);
  font-weight: 700;
}

.subtext-note {
  display: block;
  color: var(--pe-text-dim);
  font-size: 0.68rem;
  margin-top: 0.15rem;
}

/* Subcard Sections */
.subcard-section {
  margin-top: 1rem;
  padding: 0.85rem;
  background: var(--pe-bg-card);
  border: 1px solid var(--pe-border);
  border-radius: 0.65rem;
}

.subcard-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.subcard-header h4 {
  margin: 0;
  font-size: 0.84rem;
  font-weight: 700;
  color: var(--pe-text);
}

/* Handoff Grid */
.handoff-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem 1.5rem;
}

.handoff-cell {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.75rem;
  padding-bottom: 0.35rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
}

.cell-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--pe-text-dim);
}

.cell-val {
  font-size: 0.76rem;
  color: var(--pe-text);
  font-weight: 500;
  text-align: right;
}

/* Reference List Items */
.reference-item-list {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.reference-item-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.55rem 0.85rem;
  background: var(--pe-bg-panel);
  border: 1px solid var(--pe-border-subtle);
  border-radius: 0.45rem;
}

.ref-title-group {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.ref-name {
  font-size: 0.76rem;
  font-weight: 500;
  color: var(--pe-text);
}

.ref-actions-group {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.ref-badge {
  padding: 0.15rem 0.45rem;
  font-size: 0.64rem;
  font-weight: 700;
  border-radius: 999px;
  background: var(--pe-cyan-soft);
  color: var(--pe-cyan);
  border: 1px solid rgba(6, 182, 212, 0.25);
}

/* Dimension Stat Bars */
.dimension-bar-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
  margin: 0.85rem 0;
}

.dimension-stat-cell {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding: 0.65rem;
  background: var(--pe-bg-card);
  border: 1px solid var(--pe-border);
  border-radius: 0.5rem;
}

.stat-label {
  font-size: 0.68rem;
  color: var(--pe-text-dim);
  font-weight: 600;
}

.stat-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.55rem;
  font-size: 0.72rem;
  font-weight: 800;
  border-radius: 0.35rem;
  width: fit-content;
}

.green-badge {
  background: var(--pe-emerald-soft);
  color: var(--pe-emerald);
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.purple-badge {
  background: var(--pe-purple-soft);
  color: #c084fc;
  border: 1px solid rgba(168, 85, 247, 0.3);
}

.amber-badge {
  background: var(--pe-amber-soft);
  color: var(--pe-amber);
  border: 1px solid rgba(245, 158, 11, 0.3);
}

/* Review Specific Styling */
.review-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1.25rem;
  padding: 0.85rem 0;
  border-bottom: 1px solid var(--pe-border);
}

.stat-pair {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.review-split-grid {
  display: grid;
  grid-template-columns: 18rem minmax(0, 1fr);
  gap: 0.85rem;
  margin-top: 1rem;
}

.split-column {
  padding: 0.85rem;
  background: var(--pe-bg-card);
  border: 1px solid var(--pe-border);
  border-radius: 0.65rem;
}

.split-col-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.split-col-header h4 {
  margin: 0;
  font-size: 0.84rem;
  font-weight: 700;
}

.criterion-pills-list {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.criterion-pill-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0.6rem;
  background: var(--pe-bg-panel);
  border: 1px solid var(--pe-border-subtle);
  border-radius: 0.45rem;
}

.crit-tag {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.15rem 0.4rem;
  font-size: 0.68rem;
  font-weight: 800;
  border-radius: 0.35rem;
  background: var(--pe-cyan-soft);
  color: var(--pe-cyan);
  border: 1px solid rgba(6, 182, 212, 0.3);
}

.crit-name {
  font-size: 0.74rem;
  color: var(--pe-text);
  font-weight: 500;
}

.findings-table {
  width: 100%;
  border-collapse: collapse;
}

.findings-table th {
  padding: 0.45rem 0.65rem;
  font-size: 0.68rem;
  font-weight: 700;
  color: var(--pe-text-dim);
  text-align: left;
  border-bottom: 1px solid var(--pe-border);
}

.findings-table td {
  padding: 0.55rem 0.65rem;
  font-size: 0.74rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.finding-state-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.15rem 0.5rem;
  font-size: 0.68rem;
  font-weight: 800;
  border-radius: 999px;
}

.finding-state-pill.satisfied {
  background: var(--pe-emerald-soft);
  color: var(--pe-emerald);
}

.finding-state-pill.partial {
  background: var(--pe-amber-soft);
  color: var(--pe-amber);
}

.doc-link {
  color: var(--pe-text-muted);
  font-size: 0.72rem;
}

.rationale-body {
  margin: 0;
  font-size: 0.78rem;
  line-height: 1.65;
  color: var(--pe-text-muted);
}

.decision-pending-box {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: var(--pe-bg-panel);
  border: 1px dashed var(--pe-border);
  border-radius: 0.5rem;
}

.decision-pending-box strong {
  display: block;
  font-size: 0.78rem;
  color: var(--pe-text);
}

.decision-pending-box p {
  margin: 0.15rem 0 0;
  font-size: 0.7rem;
  color: var(--pe-text-dim);
}

.decision-issued-box {
  padding: 0.75rem;
  background: var(--pe-bg-panel);
  border: 1px solid var(--pe-emerald);
  border-radius: 0.5rem;
}

.decision-pill {
  display: inline-flex;
  padding: 0.2rem 0.6rem;
  font-size: 0.75rem;
  font-weight: 800;
  background: var(--pe-emerald-soft);
  color: var(--pe-emerald);
  border-radius: 0.35rem;
}

.decision-rationale-text {
  margin: 0.4rem 0 0;
  font-size: 0.76rem;
  color: var(--pe-text-muted);
}

/* Mastery Specific Styling */
.mastery-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--pe-border);
}

.mastery-title-group {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.mastery-main-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 800;
}

.mastery-subject-line {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.76rem;
  color: var(--pe-text-muted);
  margin-top: 0.25rem;
}

.mastery-status-badges {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.mastery-judgment-badge,
.mastery-freshness-badge {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.55rem 0.85rem;
  border-radius: 0.65rem;
  border: 1px solid;
}

.mastery-judgment-badge {
  background: var(--pe-emerald-soft);
  border-color: rgba(16, 185, 129, 0.4);
}

.mastery-freshness-badge {
  background: var(--pe-amber-soft);
  border-color: rgba(245, 158, 11, 0.4);
}

.badge-sub {
  display: block;
  font-size: 0.62rem;
  color: var(--pe-text-dim);
  font-weight: 600;
}

.badge-main {
  display: block;
  font-size: 0.88rem;
  font-weight: 800;
  letter-spacing: 0.03em;
}

.info-alert-banner {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 0.85rem;
  margin-top: 1rem;
  background: var(--pe-cyan-soft);
  border: 1px solid rgba(6, 182, 212, 0.3);
  border-radius: 0.55rem;
  font-size: 0.76rem;
  color: var(--pe-text);
}

.causal-stepper-trace {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 0.4rem;
  margin-top: 1.15rem;
}

.stepper-block {
  display: grid;
  grid-template-columns: 2.2rem minmax(0, 1fr);
  gap: 0.85rem;
  padding: 0.85rem;
  background: var(--pe-bg-card);
  border: 1px solid var(--pe-border);
  border-radius: 0.65rem;
}

.stepper-num {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  font-size: 0.82rem;
  font-weight: 800;
  color: #020617;
  background: var(--pe-cyan);
  border-radius: 0.45rem;
}

.stepper-content {
  min-width: 0;
}

.stepper-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.stepper-val-middle {
  font-size: 0.74rem;
  color: var(--pe-text-dim);
}

.stepper-title {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--pe-text);
}

.stepper-arrow {
  text-align: center;
  font-size: 1rem;
  font-weight: 800;
  color: var(--pe-cyan);
  line-height: 1;
}

.stepper-table {
  width: 100%;
  border-collapse: collapse;
}

.stepper-table th {
  padding: 0.4rem 0.6rem;
  font-size: 0.66rem;
  color: var(--pe-text-dim);
  text-align: left;
  border-bottom: 1px solid var(--pe-border);
}

.stepper-table td {
  padding: 0.48rem 0.6rem;
  font-size: 0.74rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.satisfied-text {
  color: var(--pe-emerald);
  font-weight: 700;
}

.evidence-pill-stack {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.evidence-ref-pill {
  padding: 0.4rem 0.75rem;
  font-size: 0.74rem;
  font-weight: 700;
  background: var(--pe-bg-panel);
  border: 1px solid var(--pe-border-subtle);
  border-radius: 0.4rem;
  color: var(--pe-cyan);
}

.basis-note {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  font-size: 0.78rem;
  color: var(--pe-text-muted);
  line-height: 1.6;
}

/* Portfolio Specific Styling */
.portfolio-header-area {
  padding-bottom: 0.85rem;
  border-bottom: 1px solid var(--pe-border);
}

.portfolio-subject-line {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.76rem;
  color: var(--pe-text-muted);
}

.portfolio-main-title {
  margin: 0.25rem 0;
  font-size: 1.3rem;
  font-weight: 800;
}

.portfolio-meta-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.74rem;
  color: var(--pe-text-dim);
  margin-top: 0.35rem;
}

.meta-counters {
  display: flex;
  gap: 1rem;
}

.portfolio-groups-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-top: 1rem;
}

.portfolio-group-card {
  background: var(--pe-bg-card);
  border: 1px solid var(--pe-border);
  border-radius: 0.75rem;
  overflow: hidden;
}

.group-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  background: var(--pe-bg-panel);
  border-bottom: 1px solid var(--pe-border);
}

.group-num-title {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.group-num-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  font-size: 0.74rem;
  font-weight: 800;
  background: var(--pe-bg-card);
  border: 1px solid var(--pe-border-subtle);
  border-radius: 0.35rem;
  color: var(--pe-cyan);
}

.group-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
}

.group-badges-area {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.projection-tag {
  padding: 0.18rem 0.55rem;
  font-size: 0.68rem;
  font-weight: 700;
  border-radius: 0.35rem;
  background: rgba(16, 185, 129, 0.15);
  color: var(--pe-emerald);
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-warning-tag {
  padding: 0.18rem 0.55rem;
  font-size: 0.68rem;
  font-weight: 700;
  border-radius: 0.35rem;
  background: var(--pe-amber-soft);
  color: var(--pe-amber);
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.verification-date-tag {
  font-size: 0.7rem;
  color: var(--pe-text-dim);
}

.portfolio-evidence-table {
  width: 100%;
  border-collapse: collapse;
}

.portfolio-evidence-table th {
  padding: 0.55rem 0.85rem;
  font-size: 0.68rem;
  color: var(--pe-text-dim);
  text-align: left;
  border-bottom: 1px solid var(--pe-border);
}

.portfolio-evidence-table td {
  padding: 0.65rem 0.85rem;
  font-size: 0.74rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.portfolio-evidence-table tr:hover {
  background: var(--pe-bg-card-hover);
  cursor: pointer;
}

.portfolio-evidence-table tr.selected {
  background: var(--pe-cyan-soft);
}

.evidence-title-ref {
  font-weight: 600;
  color: var(--pe-text);
}

.type-badge {
  padding: 0.12rem 0.4rem;
  font-size: 0.66rem;
  border-radius: 0.3rem;
  background: var(--pe-bg-panel);
  border: 1px solid var(--pe-border-subtle);
  color: var(--pe-text-muted);
}

.date-text,
.notes-text {
  font-size: 0.72rem;
  color: var(--pe-text-muted);
}

.group-card-footer {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  font-size: 0.7rem;
  color: var(--pe-text-dim);
  background: var(--pe-bg-panel);
  border-top: 1px solid rgba(255, 255, 255, 0.04);
}

/* Right Context Panel Styling */
.right-context {
  padding: 0.75rem;
  color: var(--pe-text);
}

.context-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 0.65rem;
  border-bottom: 1px solid var(--pe-border);
}

.context-panel-header h3 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 800;
}

.close-icon {
  font-size: 1.1rem;
  color: var(--pe-text-dim);
  cursor: pointer;
}

.context-subheading {
  margin: 0.65rem 0 0.45rem;
  font-size: 0.82rem;
  font-weight: 700;
}

.context-cards-stack {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  margin-top: 0.55rem;
}

.context-item-card {
  padding: 0.75rem;
  background: var(--pe-bg-card);
  border: 1px solid var(--pe-border);
  border-radius: 0.65rem;
}

.item-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.45rem;
}

.card-icon-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.context-item-card h5 {
  margin: 0;
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--pe-text);
}

.context-bullet-list {
  padding-right: 1.1rem;
  margin: 0;
  font-size: 0.72rem;
  color: var(--pe-text-muted);
  line-height: 1.6;
}

.context-text-single {
  margin: 0;
  font-size: 0.72rem;
  color: var(--pe-text-muted);
  line-height: 1.5;
}

.reviewer-info-box {
  font-size: 0.72rem;
  color: var(--pe-text-muted);
  line-height: 1.5;
}

.reviewer-info-box p {
  margin: 0.2rem 0 0;
  color: var(--pe-text-dim);
}

.provenance-details-grid {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.prov-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  font-size: 0.7rem;
}

.prov-k {
  color: var(--pe-text-dim);
}

.prov-v {
  color: var(--pe-text);
  font-weight: 600;
}

.status-dot {
  display: inline-block;
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 50%;
  margin-left: 0.35rem;
}

.green-dot {
  background: var(--pe-emerald);
}

.date-tag-sub {
  display: block;
  margin-top: 0.35rem;
  font-size: 0.68rem;
  color: var(--pe-text-dim);
}

.technical-receipt-box {
  padding: 0.55rem 0.75rem;
  background: var(--pe-bg-panel);
  border: 1px solid var(--pe-border);
  border-radius: 0.45rem;
  font-size: 0.68rem;
}

.technical-receipt-box .identifier {
  color: var(--pe-cyan);
  font-family: monospace;
}

.technical-receipt-box .digest {
  color: var(--pe-text-dim);
  font-family: monospace;
  font-size: 0.62rem;
  word-break: break-all;
}

.context-info-callout {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
  padding: 0.65rem 0.75rem;
  background: var(--pe-bg-panel);
  border: 1px solid var(--pe-border);
  border-radius: 0.55rem;
  font-size: 0.72rem;
  color: var(--pe-text-muted);
  line-height: 1.5;
}

.context-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  width: 100%;
  padding: 0.55rem 0.85rem;
  margin-top: 0.5rem;
  font-size: 0.74rem;
  font-weight: 700;
  color: var(--pe-cyan);
  background: var(--pe-cyan-soft);
  border: 1px solid rgba(6, 182, 212, 0.3);
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.context-action-btn:hover {
  background: rgba(6, 182, 212, 0.2);
}

/* Bottom Temporary Workspace Forms */
.bottom-workspace {
  padding: 1rem;
}

.history-workspace {
  max-width: 60rem;
  margin: 0 auto;
}

.form-note {
  font-size: 0.74rem;
  color: var(--pe-text-muted);
  margin-bottom: 0.75rem;
}

.history-table {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  list-style: none;
  padding: 0;
  margin: 0;
}

.history-table li {
  display: grid;
  grid-template-columns: 14rem 8rem 12rem minmax(0, 1fr);
  gap: 0.75rem;
  align-items: center;
  padding: 0.55rem 0.85rem;
  background: var(--pe-bg-panel);
  border: 1px solid var(--pe-border);
  border-radius: 0.45rem;
  font-size: 0.72rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
  max-width: 54rem;
  margin: 0 auto;
}

.form-grid label {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  font-size: 0.74rem;
  font-weight: 600;
  color: var(--pe-text-muted);
}

.form-grid input,
.form-grid textarea,
.form-grid select {
  padding: 0.48rem 0.65rem;
  font-size: 0.78rem;
  border-radius: 0.45rem;
  border: 1px solid var(--pe-border);
  background: var(--pe-bg-panel);
  color: var(--pe-text);
}

.form-grid input:focus,
.form-grid textarea:focus,
.form-grid select:focus {
  outline: none;
  border-color: var(--pe-cyan);
}

.wide {
  grid-column: 1 / -1;
}

.form-submit {
  grid-column: 1 / -1;
  padding: 0.55rem 1.25rem;
  font-size: 0.8rem;
  font-weight: 700;
  border-radius: 0.45rem;
  background: var(--pe-cyan);
  color: #020617;
  border: 0;
  cursor: pointer;
  width: fit-content;
}

.notice {
  padding: 0.65rem 0.85rem;
  margin: 0.5rem 0.85rem;
  font-size: 0.74rem;
  border-radius: 0.5rem;
}

.notice.success {
  background: var(--pe-emerald-soft);
  color: var(--pe-emerald);
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.notice.error {
  background: var(--pe-rose-soft);
  color: var(--pe-rose);
  border: 1px solid rgba(244, 63, 94, 0.3);
}

.empty-state {
  padding: 1rem;
  text-align: center;
  font-size: 0.74rem;
  color: var(--pe-text-dim);
  border: 1px dashed var(--pe-border);
  border-radius: 0.5rem;
}

/* RTL and text helpers */
bdi {
  unicode-bidi: isolate;
}

/* Narrow & Mobile Safety */
@media (max-width: 900px) {
  .meta-field-row {
    grid-template-columns: minmax(0, 1fr);
  }

  .review-split-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .review-stats-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .dimension-bar-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .handoff-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .mastery-header-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.85rem;
  }

  .form-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .history-table li {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>

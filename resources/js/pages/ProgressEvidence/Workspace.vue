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
const reviewFocus = ref<ReviewFocus>(props.review_requests.length ? 'request' : 'review');
const candidateId = ref(props.candidates[0]?.id ?? '');
const evidenceId = ref(props.evidence[0]?.id ?? '');
const requestId = ref(
  props.review_requests.find((item) => item.status === 'REQUESTED')?.id ??
    props.review_requests[0]?.id ??
    '',
);
const reviewId = ref(
  props.reviews.find((item) => ['IN_REVIEW', 'READY_FOR_DECISION'].includes(item.status))?.id ??
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
        <span>{{ item.ar }}</span>
        <bdi dir="ltr">{{ item.en }}</bdi>
      </a>
    </template>

    <template #top>
      <span class="top-mode">
        وضع الصفحة: <strong>{{ activeNav.ar }}</strong>
        <bdi dir="ltr">{{ activeNav.en }}</bdi>
      </span>
      <div class="top-actions" aria-label="إجراءات سير العمل الحالي">
        <template v-if="surface === 'evidence'">
          <button
            class="button secondary"
            type="button"
            :disabled="!handoff_receipts.length"
            @click="panel = 'intake'"
          >
            Candidate إدخال
          </button>
          <template v-if="evidenceFocus === 'candidate'">
            <button
              class="button primary"
              type="button"
              :disabled="!candidateAction"
              @click="runCandidateAction"
            >
              {{ candidateAction?.label ?? 'لا يوجد انتقال متاح' }}
            </button>
          </template>
          <template v-else>
            <button
              class="button secondary"
              type="button"
              :disabled="!selectedEvidence"
              @click="openRevision"
            >
              Revision جديدة
            </button>
            <button
              class="button primary"
              type="button"
              :disabled="!selectedEvidence || selectedEvidence.lifecycle_state !== 'ACTIVE'"
              @click="requestReview"
            >
              طلب مراجعة
            </button>
          </template>
        </template>

        <template v-else-if="surface === 'reviews'">
          <button
            v-if="reviewFocus === 'request'"
            class="button primary"
            type="button"
            :disabled="selectedRequest?.status !== 'REQUESTED'"
            @click="admitRequest"
          >
            بدء Review الرسمي
          </button>
          <template v-else>
            <button
              class="button secondary"
              type="button"
              :disabled="
                !selectedReview ||
                !['IN_REVIEW', 'READY_FOR_DECISION'].includes(selectedReview.status)
              "
              @click="openFinding"
            >
              إضافة Finding
            </button>
            <button
              class="button primary"
              type="button"
              :disabled="
                selectedReview?.status !== 'READY_FOR_DECISION' || !selectedReview.findings.length
              "
              @click="panel = 'decision'"
            >
              Issue Decision
            </button>
          </template>
        </template>

        <template v-else-if="surface === 'mastery'">
          <button
            class="button secondary"
            type="button"
            :disabled="!selectedMastery"
            @click="panel = 'mastery-history'"
          >
            عرض السجل التاريخي
          </button>
          <button
            class="button primary"
            type="button"
            :disabled="!mastery_policies.length"
            @click="openMastery"
          >
            إلحاق Mastery State
          </button>
        </template>

        <template v-else>
          <button class="button secondary" type="button" @click="panel = 'portfolio'">
            إنشاء Portfolio View
          </button>
          <button
            class="button primary"
            type="button"
            :disabled="!selectedPortfolio || !evidence.length"
            @click="openPortfolioAdd"
          >
            إضافة Evidence Reference
          </button>
          <button
            class="button ghost"
            type="button"
            :disabled="!selectedPortfolioItem"
            @click="removePortfolioItem"
          >
            إزالة المرجع
          </button>
        </template>
      </div>
    </template>

    <template #left>
      <nav class="left-rail" data-testid="structure-panel" aria-label="البنية والاختيار الحالي">
        <div class="rail-heading">
          <span>البنية والاختيار</span>
          <bdi dir="ltr">{{ activeNav.en }}</bdi>
        </div>

        <template v-if="surface === 'evidence'">
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
              <span
                ><strong>{{ item.proposed_title }}</strong
                ><small>{{ item.evidence_claim }}</small></span
              >
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
              <span
                ><strong>{{ item.title }}</strong
                ><small>{{ item.evidence_claim }}</small></span
              >
              <bdi dir="ltr" class="state">R{{ item.current_revision_number }}</bdi>
            </button>
            <p v-if="!evidence.length" class="empty-state">لا توجد Evidence مقبولة عبر Intake.</p>
          </div>
        </template>

        <template v-else-if="surface === 'reviews'">
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
              <span
                ><strong>{{ evidenceTitle(item.evidence_id) }}</strong
                ><small
                  ><bdi dir="ltr">{{ item.review_scope_key }}</bdi></small
                ></span
              >
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
              <span
                ><strong>{{ evidenceTitle(item.evidence_id) }}</strong
                ><small>{{ item.findings.length }} Finding(s)</small></span
              >
              <bdi dir="ltr" class="state review-state">{{ item.status }}</bdi>
            </button>
            <p v-if="!reviews.length" class="empty-state">لم تبدأ Formal Review بعد.</p>
          </div>
        </template>

        <template v-else-if="surface === 'mastery'">
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
            <span
              ><strong
                ><bdi dir="ltr">{{ item.target_id }}</bdi></strong
              ><small>Policy-governed state</small></span
            >
            <bdi dir="ltr" class="state">{{ item.judgment }}</bdi>
          </button>
          <p v-if="!mastery.length" class="empty-state">لا توجد Mastery State محكومة.</p>
        </template>

        <template v-else>
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
            <span
              ><strong>{{ item.name }}</strong
              ><small>{{ item.items.length }} reference(s)</small></span
            >
            <bdi dir="ltr" class="state projection-state">VIEW</bdi>
          </button>
          <p v-if="!portfolios.length" class="empty-state">لا توجد Portfolio Views.</p>
        </template>
      </nav>
    </template>

    <template #default>
      <p v-if="page.props.flash?.status" class="notice success">{{ page.props.flash.status }}</p>
      <p v-if="page.props.errors?.workflow" class="notice error">
        {{ page.props.errors.workflow }}
      </p>

      <section class="center-workspace" data-testid="primary-workspace">
        <div class="surface-heading">
          <div>
            <p class="eyebrow">مساحة العمل الأساسية</p>
            <h2>{{ activeNav.ar }}</h2>
          </div>
          <p v-if="surface === 'evidence'">
            Candidate ليست Evidence، وAdmission لا يعني Review أو Acceptance أو Mastery.
          </p>
          <p v-else-if="surface === 'reviews'">
            CENTER هو موضع Findings والعمل الرسمي وReview Decision.
          </p>
          <p v-else-if="surface === 'mastery'">
            Judgment وFreshness بعدان مستقلان، ولا توجد نقاط أو نسب إكمال بديلة عن الحكم.
          </p>
          <p v-else>Portfolio عرض مُنسّق فوق المراجع الكنسية، وليس مخزن Evidence ثانياً.</p>
        </div>

        <div v-if="surface === 'evidence'" class="workbench-grid">
          <article
            v-if="evidenceFocus === 'candidate' && candidate"
            class="object-workbench"
            data-testid="candidate-detail"
          >
            <div class="object-heading">
              <div>
                <p class="eyebrow">Candidate Evidence · قبل Admission</p>
                <h3>{{ candidate.proposed_title }}</h3>
              </div>
              <bdi dir="ltr" class="state candidate-state">{{ candidate.state }}</bdi>
            </div>
            <div class="truth-banner candidate-banner">
              <strong>حقيقة الحالة</strong>
              <span
                >هذا سجل Candidate فقط. لم تُنشأ منه Evidence كنسية بعد، لذلك لا تُعرض عليه أبعاد
                Evidence الكنسية.</span
              >
            </div>
            <section class="detail-block">
              <span class="label">Evidence Claim المقترحة</span>
              <p>{{ candidate.evidence_claim }}</p>
            </section>
            <div class="detail-grid">
              <section class="detail-block compact">
                <span class="label">Source Handoff Reference</span>
                <bdi dir="ltr" class="identifier"
                  >{{ candidate.source_id }}@{{ candidate.source_revision }}</bdi
                >
                <p class="muted">مرجع إلى المصدر؛ لا توجد نسخة ثانية من Result هنا.</p>
              </section>
              <section class="detail-block compact">
                <span class="label">Capability</span>
                <bdi dir="ltr" class="identifier">{{ candidate.capability_id }}</bdi>
              </section>
            </div>
            <div class="detail-grid">
              <section class="detail-block compact">
                <span class="label">Selected Material References</span>
                <ul class="reference-list">
                  <li v-for="reference in candidate.selected_material_refs" :key="reference">
                    <bdi dir="ltr">{{ reference }}</bdi>
                  </li>
                </ul>
              </section>
              <section class="detail-block compact">
                <span class="label">Criterion Scope</span>
                <ul class="reference-list">
                  <li v-for="criterion in candidate.criterion_scope" :key="criterion">
                    <bdi dir="ltr">{{ criterion }}</bdi>
                  </li>
                </ul>
              </section>
            </div>
          </article>

          <article
            v-else-if="selectedEvidence"
            class="object-workbench"
            data-testid="evidence-detail"
          >
            <div class="object-heading">
              <div>
                <p class="eyebrow">Canonical Evidence · sealed</p>
                <h3>{{ selectedEvidence.title }}</h3>
              </div>
              <bdi dir="ltr" class="state">R{{ selectedEvidence.current_revision_number }}</bdi>
            </div>
            <section class="detail-block">
              <span class="label">Evidence Claim</span>
              <p>{{ selectedEvidence.evidence_claim }}</p>
              <p class="muted">{{ selectedEvidence.summary }}</p>
            </section>
            <div class="dimension-grid" aria-label="أبعاد حالة Evidence المستقلة">
              <section class="dimension-card">
                <span>Evidence Lifecycle</span
                ><bdi dir="ltr">{{ selectedEvidence.lifecycle_state }}</bdi>
              </section>
              <section class="dimension-card">
                <span>Review Status</span><bdi dir="ltr">{{ selectedEvidence.review_status }}</bdi>
              </section>
              <section class="dimension-card">
                <span>Effective Review Decision</span
                ><bdi dir="ltr">{{ selectedEvidence.effective_review_decision }}</bdi>
              </section>
            </div>
          </article>

          <div v-else class="empty-workbench">
            اختر Candidate أو Evidence لعرض العمل الكنسي المناسب.
          </div>
        </div>

        <div
          v-else-if="surface === 'reviews'"
          class="workbench-grid"
          data-testid="review-workbench"
        >
          <article v-if="reviewFocus === 'request' && selectedRequest" class="object-workbench">
            <div class="object-heading">
              <div>
                <p class="eyebrow">Review Request</p>
                <h3>{{ evidenceTitle(selectedRequest.evidence_id) }}</h3>
              </div>
              <bdi dir="ltr" class="state request-state">{{ selectedRequest.status }}</bdi>
            </div>
            <div class="truth-banner">
              <strong>حد بدء المراجعة</strong
              ><span
                >الطلب يثبت Evidence Revision ونطاق العمل فقط. Findings وDecision لا توجد قبل بدء
                Formal Review.</span
              >
            </div>
            <div class="detail-grid">
              <section class="detail-block compact">
                <span class="label">Pinned Evidence Revision</span
                ><bdi dir="ltr" class="identifier">{{ selectedRequest.evidence_revision_id }}</bdi>
              </section>
              <section class="detail-block compact">
                <span class="label">Review Scope</span
                ><bdi dir="ltr" class="identifier">{{ selectedRequest.review_scope_key }}</bdi>
              </section>
            </div>
          </article>

          <article v-else-if="selectedReview" class="object-workbench">
            <div class="object-heading">
              <div>
                <p class="eyebrow">Formal Evidence Review</p>
                <h3>{{ evidenceTitle(selectedReview.evidence_id) }}</h3>
              </div>
              <bdi dir="ltr" class="state review-state">{{ selectedReview.status }}</bdi>
            </div>
            <section class="detail-block">
              <span class="label">Review Findings</span>
              <div class="finding-stack">
                <article
                  v-for="item in selectedReview.findings"
                  :key="item.id"
                  class="finding-card"
                >
                  <div class="finding-heading">
                    <bdi dir="ltr">{{ item.criterion_key }}</bdi
                    ><bdi dir="ltr" class="finding-value">{{ item.finding }}</bdi>
                  </div>
                  <p>{{ item.statement }}</p>
                </article>
                <p v-if="!selectedReview.findings.length" class="empty-state">
                  لا توجد Findings مسجلة بعد.
                </p>
              </div>
            </section>
            <section class="decision-block">
              <span class="label">Review Decision</span>
              <div v-if="selectedReview.decision" class="decision-content">
                <bdi dir="ltr" class="decision-value">{{ selectedReview.decision.decision }}</bdi>
                <p>{{ selectedReview.decision.rationale }}</p>
              </div>
              <p v-else class="empty-state">لم يصدر Review Decision بعد.</p>
            </section>
          </article>

          <div v-else class="empty-workbench">اختر Review Request أو Formal Review.</div>
        </div>

        <div v-else-if="surface === 'mastery'" class="workbench-grid mastery-grid">
          <article v-if="selectedMastery" class="object-workbench" data-testid="mastery-detail">
            <div class="object-heading">
              <div>
                <p class="eyebrow">Canonical Mastery Target</p>
                <h3>
                  <bdi dir="ltr">{{ selectedMastery.target_id }}</bdi>
                </h3>
              </div>
              <bdi dir="ltr" class="state">{{ selectedMastery.id }}</bdi>
            </div>
            <div class="truth-banner mastery-banner">
              <strong>Judgment ≠ Freshness</strong
              ><span
                >يمكن أن تكون الحالة <bdi dir="ltr">MASTERED + REVALIDATION_REQUIRED</bdi> بصورة
                قانونية؛ Completion لا تساوي Mastery.</span
              >
            </div>
            <div class="mastery-dimensions">
              <section class="mastery-dimension judgment-card">
                <span>الحكم · Judgment</span><bdi dir="ltr">{{ selectedMastery.judgment }}</bdi>
              </section>
              <section class="mastery-dimension freshness-card">
                <span>الحداثة · Freshness</span
                ><bdi dir="ltr">{{ selectedMastery.freshness_status }}</bdi>
              </section>
            </div>
            <section class="detail-block">
              <span class="label">Causal Evaluation Trace</span>
              <ol class="causal-steps">
                <li>
                  <span class="step-number">01</span>
                  <div>
                    <strong>Mastery Policy</strong
                    ><bdi dir="ltr">{{ selectedMastery.policy_revision_id }}</bdi>
                  </div>
                </li>
                <li>
                  <span class="step-number">02</span>
                  <div>
                    <strong>Effective Review Decisions</strong>
                    <ul class="reference-list">
                      <li v-for="id in selectedMastery.review_decision_ids" :key="id">
                        <bdi dir="ltr">{{ id }}</bdi>
                      </li>
                    </ul>
                  </div>
                </li>
                <li>
                  <span class="step-number">03</span>
                  <div>
                    <strong>Supporting Evidence</strong>
                    <ul class="reference-list">
                      <li v-for="id in selectedMastery.supporting_evidence_revision_ids" :key="id">
                        <bdi dir="ltr">{{ id }}</bdi>
                      </li>
                    </ul>
                  </div>
                </li>
                <li>
                  <span class="step-number">04</span>
                  <div>
                    <strong>Contradicting Evidence</strong>
                    <ul class="reference-list">
                      <li
                        v-for="id in selectedMastery.contradicting_evidence_revision_ids"
                        :key="id"
                      >
                        <bdi dir="ltr">{{ id }}</bdi>
                      </li>
                      <li
                        v-if="!selectedMastery.contradicting_evidence_revision_ids.length"
                        class="muted"
                      >
                        لا توجد مراجع متعارضة مسجلة.
                      </li>
                    </ul>
                  </div>
                </li>
              </ol>
            </section>
          </article>
          <div v-else class="empty-workbench">لا توجد Mastery State لعرضها.</div>
        </div>

        <div v-else class="workbench-grid portfolio-grid">
          <article v-if="selectedPortfolio" class="object-workbench" data-testid="portfolio-detail">
            <div class="object-heading">
              <div>
                <p class="eyebrow">Curated Projection</p>
                <h3>{{ selectedPortfolio.name }}</h3>
              </div>
              <bdi dir="ltr" class="state projection-state">REFERENCE VIEW</bdi>
            </div>
            <div class="truth-banner portfolio-banner">
              <strong>لا يوجد Evidence store ثانٍ</strong
              ><span
                >كل عنصر أدناه Canonical Evidence Reference. إزالة العنصر من العرض لا تمس Evidence
                أو Review أو Mastery history.</span
              >
            </div>
            <section class="detail-block">
              <span class="label">Canonical Evidence References</span>
              <div class="portfolio-reference-list">
                <button
                  v-for="entry in selectedPortfolio.items"
                  :key="entry.id"
                  class="portfolio-reference"
                  :class="{ selected: entry.id === portfolioItemId }"
                  type="button"
                  @click="portfolioItemId = entry.id"
                >
                  <span
                    ><strong>{{ entry.title }}</strong
                    ><small>Canonical Evidence Reference</small></span
                  >
                  <bdi dir="ltr">{{ entry.current_revision_id }}</bdi>
                </button>
                <p v-if="!selectedPortfolio.items.length" class="empty-state">
                  هذا العرض لا يحتوي مراجع بعد.
                </p>
              </div>
            </section>
          </article>
          <div v-else class="empty-workbench">أنشئ أو اختر Portfolio View.</div>
        </div>
      </section>
    </template>

    <template #right>
      <div
        class="right-context"
        data-testid="context-panel"
        aria-label="السياق الفريد للاختيار الحالي"
      >
        <template v-if="surface === 'evidence'">
          <p class="eyebrow">السياق الفريد</p>
          <template v-if="evidenceFocus === 'candidate' && candidate">
            <h2>حدود Intake</h2>
            <div class="context-callout warning-context">
              <span class="context-icon">!</span>
              <p>
                Candidate لم تعبر Admission بعد. لا يجوز إسناد قيم أبعاد Evidence الكنسية إليها قبل
                Admission.
              </p>
            </div>
            <div v-if="selectedCandidateReceipt" class="context-section">
              <span class="label">Trusted Handoff Receipt</span>
              <bdi dir="ltr" class="identifier">{{ selectedCandidateReceipt.id }}</bdi>
              <p class="digest" dir="ltr">sha256:{{ selectedCandidateReceipt.source_digest }}</p>
            </div>
            <div class="context-section">
              <span class="label">المعنى الحاكم</span>
              <p>
                Admission ينشئ Evidence Revision 1 مختومة، لكنه لا يبدأ Review ولا يصدر Decision ولا
                يغيّر Mastery.
              </p>
            </div>
          </template>
          <template v-else-if="selectedEvidence">
            <h2>الأصل والإصدار المختوم</h2>
            <div class="context-callout">
              <span class="context-icon">i</span>
              <p>الحالات الثلاث المعروضة في CENTER مستقلة. لا تُختزل في Status واحد.</p>
            </div>
            <div class="context-section">
              <span class="label">Current Sealed Revision</span>
              <bdi dir="ltr" class="identifier">{{ selectedEvidence.current_revision_id }}</bdi>
              <p class="digest" dir="ltr">sha256:{{ selectedEvidence.source_digest }}</p>
            </div>
            <div class="context-section">
              <span class="label">Revision History</span>
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
            <div class="context-section">
              <span class="label">Review eligibility</span>
              <p>طلب المراجعة عملية صريحة على Evidence النشطة؛ لا تُنشأ تلقائيًا عند Admission.</p>
            </div>
          </template>
        </template>

        <template v-else-if="surface === 'reviews'">
          <p class="eyebrow">Authority Context</p>
          <template v-if="reviewFocus === 'review' && selectedReview">
            <h2>سلطة المراجعة</h2>
            <div class="authority-block">
              <span>Reviewer</span
              ><bdi dir="ltr" class="identifier">{{ selectedReview.reviewer_id }}</bdi>
            </div>
            <div class="context-section">
              <span class="label">Review Scope</span
              ><bdi dir="ltr" class="identifier">{{ selectedReview.review_scope_key }}</bdi>
            </div>
            <div class="context-section">
              <span class="label">Criterion Authority</span>
              <ul class="reference-list context-references">
                <li v-for="criterion in selectedReview.criterion_refs" :key="criterion">
                  <bdi dir="ltr">{{ criterion }}</bdi>
                </li>
              </ul>
            </div>
          </template>
          <template v-else-if="selectedRequest">
            <h2>سياق الطلب</h2>
            <div class="context-callout">
              <span class="context-icon">i</span>
              <p>لا يوجد Reviewer أو Finding أو Decision كحقيقة للمراجعة قبل بدء Formal Review.</p>
            </div>
            <div class="context-section">
              <span class="label">Pinned Revision</span
              ><bdi dir="ltr" class="identifier">{{ selectedRequest.evidence_revision_id }}</bdi>
            </div>
          </template>
        </template>

        <template v-else-if="surface === 'mastery' && selectedMastery">
          <p class="eyebrow">Policy Authority</p>
          <h2>سياق الحكم المحكوم</h2>
          <p class="context-intro">
            الحكم الحالي مؤسس على Policy Revision منشورة ومراجع Decision وEvidence محددة، وليس على
            نسبة نشاط أو إكمال.
          </p>
          <div class="context-section">
            <span class="label">Published Policy Revision</span>
            <bdi dir="ltr" class="identifier">{{ selectedMastery.policy_revision_id }}</bdi>
          </div>
          <div v-if="selectedMasteryPolicy" class="context-section">
            <span class="label">Qualifying Review Decisions</span>
            <ul class="reference-list">
              <li
                v-for="outcome in selectedMasteryPolicy.qualifying_review_decisions"
                :key="outcome"
              >
                <bdi dir="ltr">{{ outcome }}</bdi>
              </li>
            </ul>
          </div>
          <div v-if="selectedMastery.rationale" class="context-section">
            <span class="label">Evaluation Rationale</span>
            <p>{{ selectedMastery.rationale }}</p>
          </div>
        </template>

        <template v-else-if="surface === 'portfolio' && selectedPortfolio">
          <p class="eyebrow">View Configuration</p>
          <h2>سياق الإسقاط</h2>
          <div class="context-section no-border">
            <span class="label">View Scope</span
            ><bdi dir="ltr" class="identifier">{{
              selectedPortfolio.view_scope || 'UNSCOPED'
            }}</bdi>
          </div>
          <div class="context-section">
            <span class="label">Grouping</span
            ><bdi dir="ltr" class="identifier">{{ selectedPortfolio.grouping }}</bdi>
          </div>
          <div v-if="selectedPortfolioFilters.length" class="context-section">
            <span class="label">Governed Filters</span>
            <ul class="reference-list">
              <li v-for="filter in selectedPortfolioFilters" :key="filter.key">
                <bdi dir="ltr">{{ filter.key }}: {{ filter.value }}</bdi>
              </li>
            </ul>
          </div>
          <div class="context-callout">
            <span class="context-icon">i</span>
            <p>
              Portfolio يحتفظ بالتنظيم والـcuration فقط. الحقيقة القانونية تبقى في Evidence
              وMastery.
            </p>
          </div>
        </template>

        <p v-else class="empty-state">لا يوجد سياق فريد للاختيار الحالي.</p>
      </div>
    </template>

    <template #bottom>
      <div class="bottom-workspace" data-testid="temporary-workspace-content">
        <section v-if="panel === 'mastery-history' && selectedMastery" class="history-workspace">
          <p class="form-note">
            سجل append-only للهدف <bdi dir="ltr">{{ selectedMastery.target_id }}</bdi
            >. لا يغيّر العرض أي حكم أو حالة حداثة.
          </p>
          <ol class="history-table" aria-label="سجل Mastery التاريخي">
            <li v-for="state in selectedMasteryHistory" :key="state.id">
              <bdi dir="ltr">{{ state.id }}</bdi>
              <span
                ><bdi dir="ltr">{{ state.judgment }}</bdi></span
              >
              <span
                ><bdi dir="ltr">{{ state.freshness_status }}</bdi></span
              >
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
            لا يوجد Handoff/Submission موثوق متاح للاستلام؛ لا يمكن إنشاء Candidate من بيانات مصدر
            يكتبها المتصفح.
          </p>
          <label>
            Verified Handoff Receipt
            <select v-model="intake.handoff_receipt_id" dir="ltr" required>
              <option v-for="receipt in handoff_receipts" :key="receipt.id" :value="receipt.id">
                {{ receipt.source_type }}/{{ receipt.source_id }}@{{ receipt.source_revision }} ·
                {{ receipt.capability_id }}
              </option>
            </select>
          </label>
          <label class="wide"
            >Evidence Claim<textarea v-model="intake.evidence_claim" required />
          </label>
          <label
            >Criterion Reference<input v-model="intake.criterion_scope[0]" dir="ltr" required
          /></label>
          <label
            >Governed Purpose<input v-model="intake.governed_purpose" dir="ltr" required
          /></label>
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
          <label
            >Finding<select v-model="finding.finding" dir="ltr">
              <option>SATISFIED</option>
              <option>PARTIALLY_SATISFIED</option>
              <option>NOT_SATISFIED</option>
              <option>NOT_ASSESSABLE</option>
            </select></label
          >
          <label class="wide">البيان<textarea v-model="finding.statement" required /></label>
          <button class="button primary form-submit" type="submit">تسجيل Finding</button>
        </form>

        <form
          v-else-if="panel === 'decision' && selectedReview"
          class="form-grid"
          @submit.prevent="submitDecision"
        >
          <label
            >Review Decision<select v-model="decision.decision" dir="ltr">
              <option>ACCEPT</option>
              <option>ACCEPT_WITH_LIMITATIONS</option>
              <option>MORE_EVIDENCE_REQUIRED</option>
              <option>REJECT</option>
            </select></label
          >
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
          <label
            >Freshness<select v-model="masteryForm.freshness_status" dir="ltr">
              <option>CURRENT</option>
              <option>REVALIDATION_REQUIRED</option>
            </select></label
          >
          <label class="wide">المسوّغ<textarea v-model="masteryForm.rationale" required /></label>
          <p class="wide form-note">
            <bdi dir="ltr">MASTERED + REVALIDATION_REQUIRED</bdi> حالة قانونية؛ البعدان مستقلان.
          </p>
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
          <label
            >Evidence<select v-model="portfolioAdd.evidence_id">
              <option v-for="item in evidence" :key="item.id" :value="item.id">
                {{ item.title }}
              </option>
            </select></label
          >
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
}

:global([data-theme='dark']) {
  --pe-code: #a8c5d7;
  --pe-positive: #a7e5cc;
  --pe-positive-border: #2d6754;
  --pe-positive-bg: rgb(35 105 76 / 0.12);
  --pe-danger: #f1b4bc;
  --pe-danger-border: #7c3b47;
  --pe-danger-bg: rgb(118 48 61 / 0.12);
  --pe-warning: #e2c580;
  --pe-warning-border: #66532e;
  --pe-warning-bg: rgb(106 77 29 / 0.14);
  --pe-info: #9edfe1;
  --pe-info-border: #315c66;
  --pe-info-bg: rgb(43 107 112 / 0.13);
}

:global([data-theme='light']) .progress-evidence-shell {
  --pe-code: #355269;
  --pe-positive: #166534;
  --pe-positive-border: #86b998;
  --pe-positive-bg: rgb(22 101 52 / 0.08);
  --pe-danger: #9f1239;
  --pe-danger-border: #d7a0ad;
  --pe-danger-bg: rgb(159 18 57 / 0.07);
  --pe-warning: #854d0e;
  --pe-warning-border: #d1b176;
  --pe-warning-bg: rgb(133 77 14 / 0.08);
  --pe-info: #0e7490;
  --pe-info-border: #77afbb;
  --pe-info-bg: rgb(14 116 144 / 0.08);
}

:global(body) {
  margin: 0;
  background: var(--cep-bg-canvas);
}

* {
  box-sizing: border-box;
}

.workspace {
  min-height: 100vh;
  padding: 1rem;
  color: var(--cep-text);
  background: var(--cep-bg-canvas);
}

.panel,
.top-bar {
  border: 1px solid #1b3042;
  background: linear-gradient(180deg, rgba(11, 24, 36, 0.98), rgba(7, 17, 27, 0.98));
  box-shadow: 0 14px 38px rgba(0, 0, 0, 0.2);
}

.top-bar {
  display: flex;
  gap: 1rem;
  align-items: center;
  justify-content: space-between;
  min-height: 4.5rem;
  padding: 0.85rem 1rem;
  border-radius: 0.8rem;
}

.workspace-identity,
.object-heading,
.surface-heading,
.bottom-header,
.finding-heading {
  display: flex;
  gap: 0.9rem;
  align-items: center;
  justify-content: space-between;
}

.workspace-identity > div {
  border-right: 1px solid #20384b;
  padding-right: 0.9rem;
}

h1,
h2,
h3,
p {
  margin-top: 0;
}

h1 {
  margin-bottom: 0.2rem;
  font-size: 1.2rem;
}

h2 {
  margin-bottom: 0;
  font-size: 1.05rem;
}

h3 {
  margin-bottom: 0;
  font-size: 1rem;
}

.workspace-identity p,
.surface-heading > p,
.context-intro {
  margin: 0;
  color: var(--cep-text-muted);
  font-size: 0.72rem;
  line-height: 1.6;
}

.eyebrow,
.label {
  margin: 0;
  color: var(--cep-accent);
  font-size: 0.66rem;
  font-weight: 800;
  letter-spacing: 0.03em;
}

.label {
  display: block;
  margin-bottom: 0.35rem;
  color: var(--cep-text-muted);
}

.top-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  justify-content: flex-end;
}

.top-mode {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  align-items: center;
  color: var(--cep-text-muted);
  font-size: 0.72rem;
}

.top-mode strong {
  color: var(--cep-text);
}

.surface-tab {
  display: inline-flex;
  flex: 0 0 auto;
  gap: 0.45rem;
  align-items: center;
  min-height: 2.35rem;
  padding: 0.45rem 0.7rem;
  color: var(--cep-text-muted);
  font-size: 0.78rem;
  font-weight: 750;
  text-decoration: none;
  border-bottom: 2px solid transparent;
}

.surface-tab:hover,
.surface-tab.active {
  color: var(--cep-accent);
  background: var(--cep-accent-soft);
  border-bottom-color: var(--cep-accent);
}

.surface-tab bdi {
  font-size: 0.64rem;
}

.button {
  min-height: 2.25rem;
  padding: 0.48rem 0.72rem;
  color: var(--cep-text);
  font: inherit;
  font-size: 0.72rem;
  font-weight: 700;
  border: 1px solid var(--cep-border-strong);
  border-radius: 0.45rem;
  background: var(--cep-bg-panel-strong);
  cursor: pointer;
}

.button.primary {
  color: var(--cep-bg-canvas);
  border-color: var(--cep-accent);
  background: var(--cep-accent);
}

.button.secondary:hover,
.button.ghost:hover {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
}

.button.ghost {
  background: transparent;
}

.button:disabled {
  cursor: not-allowed;
  opacity: 0.38;
}

.notice {
  padding: 0.7rem 0.85rem;
  margin: 0.7rem 0 0;
  font-size: 0.74rem;
  border: 1px solid;
  border-radius: 0.5rem;
}

.notice.success {
  color: var(--pe-positive);
  border-color: var(--pe-positive-border);
  background: var(--pe-positive-bg);
}

.notice.error {
  color: var(--pe-danger);
  border-color: var(--pe-danger-border);
  background: var(--pe-danger-bg);
}

.workspace-grid {
  direction: ltr;
  display: grid;
  grid-template-areas: 'left center right';
  grid-template-columns: 12rem minmax(0, 1fr) 17.5rem;
  gap: 0.75rem;
  align-items: start;
  margin-top: 0.75rem;
}

.left-rail,
.center-workspace,
.right-context {
  direction: rtl;
  min-width: 0;
  border-radius: 0.8rem;
}

:deep(.cep-primary-surface) {
  padding: 0;
}

.left-rail {
  overflow: hidden;
}

.rail-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.72rem;
  color: var(--cep-text-muted);
  font-size: 0.64rem;
  border-bottom: 1px solid var(--cep-border);
}

.area-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 3.2rem;
  padding: 0.72rem;
  color: var(--cep-text-muted);
  font-size: 0.76rem;
  text-decoration: none;
  border-bottom: 1px solid #172a3a;
}

.area-link:last-child {
  border-bottom: 0;
}

.area-link.active {
  color: var(--cep-text);
  background: var(--cep-accent-soft);
  box-shadow: inset 3px 0 var(--cep-accent);
}

.area-link bdi {
  color: var(--cep-text-muted);
  font-size: 0.62rem;
}

.center-workspace {
  overflow: hidden;
}

.surface-heading {
  min-height: 4.2rem;
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--cep-border);
}

.surface-heading > p {
  max-width: 34rem;
  text-align: left;
}

.workbench-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  min-height: 35rem;
}

.record-browser {
  min-width: 0;
  border-left: 1px solid #1d3143;
  background: rgba(5, 14, 23, 0.52);
}

.browser-group + .browser-group {
  border-top: 1px solid #1d3143;
}

.browser-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.65rem 0.75rem;
  color: var(--cep-text-muted);
  font-size: 0.66rem;
  background: var(--cep-bg-panel-strong);
}

.record-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.55rem;
  align-items: center;
  width: 100%;
  padding: 0.68rem 0.75rem;
  color: var(--cep-text-muted);
  text-align: right;
  border: 0;
  border-top: 1px solid #172a3a;
  background: transparent;
  cursor: pointer;
}

.record-row:hover,
.record-row.selected {
  background: var(--cep-accent-soft);
}

.record-row.selected {
  box-shadow: inset -2px 0 var(--cep-accent);
}

.record-row span,
.portfolio-reference span {
  min-width: 0;
}

.record-row strong,
.record-row small {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.record-row strong {
  color: var(--cep-text);
  font-size: 0.73rem;
}

.record-row small {
  margin-top: 0.2rem;
  color: var(--cep-text-muted);
  font-size: 0.65rem;
}

.state {
  display: inline-flex;
  max-width: 10rem;
  padding: 0.25rem 0.42rem;
  color: var(--pe-info);
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.61rem;
  border: 1px solid var(--pe-info-border);
  border-radius: 999px;
  background: var(--pe-info-bg);
  overflow-wrap: anywhere;
}

.candidate-state {
  color: var(--pe-warning);
  border-color: var(--pe-warning-border);
  background: var(--pe-warning-bg);
}

.request-state {
  color: var(--pe-info);
  border-color: var(--pe-info-border);
}

.review-state {
  color: var(--pe-positive);
  border-color: var(--pe-positive-border);
}

.projection-state {
  color: var(--pe-code);
  border-color: var(--cep-border-strong);
}

.object-workbench {
  min-width: 0;
  padding: 1rem;
}

.object-heading {
  padding-bottom: 0.85rem;
  border-bottom: 1px solid var(--cep-border);
}

.truth-banner,
.context-callout {
  padding: 0.72rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: 0.55rem;
  background: var(--cep-accent-soft);
}

.truth-banner {
  display: grid;
  gap: 0.2rem;
  margin-top: 0.8rem;
}

.truth-banner strong {
  color: var(--cep-text);
  font-size: 0.72rem;
}

.truth-banner span,
.context-callout p {
  margin: 0;
  color: var(--cep-text-muted);
  font-size: 0.72rem;
  line-height: 1.65;
}

.candidate-banner {
  border-color: var(--pe-warning-border);
  background: var(--pe-warning-bg);
}

.detail-block,
.decision-block {
  padding: 0.8rem;
  margin-top: 0.75rem;
  border: 1px solid var(--cep-border);
  border-radius: 0.55rem;
  background: var(--cep-bg-panel-strong);
}

.detail-block.compact {
  margin-top: 0;
}

.detail-block p,
.decision-block p,
.context-section p {
  margin-bottom: 0;
  color: var(--cep-text-muted);
  font-size: 0.75rem;
  line-height: 1.65;
}

.detail-grid,
.dimension-grid,
.mastery-dimensions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.65rem;
  margin-top: 0.65rem;
}

.dimension-grid {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.identifier,
.reference-list bdi,
.timeline-list bdi,
.portfolio-reference bdi,
.history-chain bdi,
.causal-steps bdi {
  color: var(--pe-code);
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.68rem;
  overflow-wrap: anywhere;
}

.muted {
  color: var(--cep-text-muted) !important;
  font-size: 0.68rem !important;
}

.reference-list {
  display: grid;
  gap: 0.3rem;
  padding: 0;
  margin: 0.45rem 0 0;
  list-style: none;
}

.reference-list li {
  padding: 0.35rem 0.42rem;
  border: 1px solid var(--cep-border);
  border-radius: 0.35rem;
  background: var(--cep-bg-panel-strong);
}

.dimension-card,
.mastery-dimension {
  display: grid;
  gap: 0.4rem;
  align-content: center;
  min-height: 4.2rem;
  padding: 0.65rem;
  border: 1px solid var(--cep-border);
  border-radius: 0.5rem;
  background: var(--cep-bg-panel-strong);
}

.dimension-card span,
.mastery-dimension span {
  color: var(--cep-text-muted);
  font-size: 0.63rem;
}

.dimension-card bdi,
.mastery-dimension bdi {
  color: var(--cep-text);
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.71rem;
  font-weight: 800;
  overflow-wrap: anywhere;
}

.timeline-list,
.causal-steps,
.history-chain {
  padding: 0;
  margin: 0.55rem 0 0;
  list-style: none;
}

.timeline-list {
  display: grid;
  gap: 0.5rem;
}

.timeline-list li {
  display: grid;
  grid-template-columns: 0.5rem minmax(0, 1fr);
  gap: 0.55rem;
}

.timeline-marker {
  width: 0.4rem;
  height: 0.4rem;
  margin-top: 0.25rem;
  border: 2px solid var(--cep-accent);
  border-radius: 50%;
}

.timeline-list p {
  margin: 0.15rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.68rem;
}

.finding-stack {
  display: grid;
  gap: 0.5rem;
  margin-top: 0.55rem;
}

.finding-card {
  padding: 0.6rem;
  border: 1px solid var(--cep-border);
  border-radius: 0.45rem;
  background: var(--cep-bg-panel-strong);
}

.finding-value,
.decision-value {
  color: var(--pe-positive);
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.68rem;
}

.decision-block {
  border-color: var(--cep-border-strong);
  background: var(--cep-bg-panel-strong);
}

.decision-content {
  margin-top: 0.55rem;
}

.decision-value {
  display: inline-flex;
  padding: 0.25rem 0.4rem;
  border: 1px solid var(--pe-positive-border);
  border-radius: 0.35rem;
}

.mastery-dimension {
  min-height: 5rem;
}

.judgment-card {
  border-color: var(--pe-info-border);
  background: var(--pe-info-bg);
}

.freshness-card {
  border-color: var(--pe-warning-border);
  background: var(--pe-warning-bg);
}

.causal-steps {
  display: grid;
  gap: 0.45rem;
}

.causal-steps > li {
  display: grid;
  grid-template-columns: 1.7rem minmax(0, 1fr);
  gap: 0.55rem;
  padding: 0.55rem;
  border: 1px solid var(--cep-border);
  border-radius: 0.45rem;
  background: var(--cep-bg-panel-strong);
}

.step-number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.55rem;
  height: 1.55rem;
  color: var(--cep-accent);
  font-size: 0.62rem;
  border: 1px solid var(--pe-info-border);
  border-radius: 50%;
}

.causal-steps strong {
  display: block;
  margin-bottom: 0.25rem;
  color: var(--cep-text);
  font-size: 0.69rem;
}

.portfolio-reference-list {
  display: grid;
  gap: 0.45rem;
  margin-top: 0.55rem;
}

.portfolio-reference {
  display: flex;
  gap: 0.7rem;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.65rem;
  color: var(--cep-text);
  text-align: right;
  border: 1px solid var(--cep-border);
  border-radius: 0.48rem;
  background: var(--cep-bg-panel-strong);
  cursor: pointer;
}

.portfolio-reference.selected,
.portfolio-reference:hover {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
}

.portfolio-reference strong,
.portfolio-reference small {
  display: block;
}

.portfolio-reference small {
  margin-top: 0.15rem;
  color: var(--cep-text-muted);
  font-size: 0.62rem;
}

.empty-state,
.empty-workbench {
  color: var(--cep-text-muted);
  font-size: 0.7rem;
  text-align: center;
  border: 1px dashed var(--cep-border-strong);
  border-radius: 0.45rem;
}

.empty-state {
  padding: 0.65rem;
  margin: 0.5rem;
}

.empty-workbench {
  display: grid;
  place-items: center;
  margin: 1rem;
  padding: 2rem;
}

.right-context {
  min-width: 0;
  padding: 0;
}

.right-context h2 {
  margin: 0.28rem 0 0;
  font-size: 0.95rem;
}

.context-section {
  padding-top: 0.7rem;
  margin-top: 0.7rem;
  border-top: 1px solid var(--cep-border);
}

.context-section.no-border {
  border-top: 0;
}

.context-callout {
  display: grid;
  grid-template-columns: 1.55rem minmax(0, 1fr);
  gap: 0.5rem;
  align-items: start;
  margin-top: 0.75rem;
}

.warning-context {
  border-color: var(--pe-warning-border);
  background: var(--pe-warning-bg);
}

.context-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.4rem;
  height: 1.4rem;
  color: var(--cep-accent);
  font-size: 0.68rem;
  font-weight: 900;
  border: 1px solid var(--pe-info-border);
  border-radius: 50%;
}

.authority-block {
  display: grid;
  gap: 0.3rem;
  margin-top: 0.75rem;
}

.authority-block > span {
  color: var(--cep-text-muted);
  font-size: 0.64rem;
}

.history-chain {
  display: grid;
  gap: 0.38rem;
}

.history-chain li {
  display: grid;
  gap: 0.18rem;
  padding: 0.5rem;
  border: 1px solid var(--cep-border);
  border-radius: 0.4rem;
  background: var(--cep-bg-panel-strong);
}

.history-chain small {
  color: var(--cep-text-muted);
  font-size: 0.6rem;
}

.digest {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.62rem !important;
  overflow-wrap: anywhere;
}

.history-workspace {
  max-width: 72rem;
  margin: 0 auto;
}

.history-table {
  display: grid;
  gap: 0.45rem;
  padding: 0;
  margin: 0.75rem 0 0;
  list-style: none;
}

.history-table li {
  display: grid;
  grid-template-columns: minmax(12rem, 1.5fr) repeat(2, minmax(9rem, 0.75fr)) minmax(12rem, 1fr);
  gap: 0.6rem;
  align-items: center;
  padding: 0.65rem;
  color: var(--cep-text-muted);
  font-size: 0.7rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
}

.history-table bdi {
  color: var(--cep-text);
}

.bottom-workspace {
  min-width: 0;
}

.bottom-header {
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--cep-border);
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.65rem;
  max-width: 68rem;
  padding-top: 0.8rem;
  margin: 0 auto;
}

.form-grid label {
  display: grid;
  gap: 0.28rem;
  color: var(--cep-text-muted);
  font-size: 0.68rem;
}

.form-grid input,
.form-grid textarea,
.form-grid select {
  width: 100%;
  padding: 0.58rem 0.62rem;
  color: var(--cep-text);
  font: inherit;
  border: 1px solid var(--cep-border-strong);
  border-radius: 0.42rem;
  background: var(--cep-bg-panel);
}

.form-grid textarea {
  min-height: 5rem;
  resize: vertical;
}

.wide {
  grid-column: 1 / -1;
}

.form-submit {
  width: fit-content;
  min-width: 12rem;
}

.form-note {
  margin: 0;
  color: var(--cep-text-muted);
  font-size: 0.68rem;
}

bdi {
  unicode-bidi: isolate;
}

@media (max-width: 1240px) {
  .workspace-grid {
    grid-template-areas: 'left center' 'left right';
    grid-template-columns: 11.5rem minmax(0, 1fr);
  }

  .right-context {
    position: static;
  }
}

@media (max-width: 900px) {
  .workspace {
    padding: 0.7rem;
  }

  .top-bar,
  .surface-heading,
  .object-heading {
    display: grid;
    align-items: stretch;
  }

  .top-actions {
    justify-content: flex-start;
  }

  .workspace-grid {
    grid-template-areas: 'left' 'center' 'right';
    grid-template-columns: minmax(0, 1fr);
  }

  .left-rail {
    position: static;
    display: block;
  }

  .rail-heading {
    display: none;
  }

  .area-link {
    border-left: 1px solid #1d3143;
    border-bottom: 0;
  }

  .workbench-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .record-browser {
    border-left: 0;
    border-bottom: 1px solid #1d3143;
  }
}

@media (max-width: 620px) {
  .left-rail,
  .detail-grid,
  .dimension-grid,
  .mastery-dimensions,
  .form-grid,
  .history-table li {
    grid-template-columns: minmax(0, 1fr);
  }

  .wide {
    grid-column: auto;
  }

  .portfolio-reference {
    display: grid;
  }
}
</style>

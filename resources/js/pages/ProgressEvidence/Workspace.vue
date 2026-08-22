<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
};

type Candidate = {
  id: string;
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
  items: PortfolioItem[];
};

type Surface = 'evidence' | 'reviews' | 'mastery' | 'portfolio';
type EvidenceFocus = 'candidate' | 'evidence';
type ReviewFocus = 'request' | 'review';
type Panel =
  'intake' | 'revision' | 'finding' | 'decision' | 'mastery' | 'portfolio' | 'portfolio-add' | null;

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

const panelTitle = computed(() => {
  const titles: Record<Exclude<Panel, null>, string> = {
    intake: 'إعداد Candidate Evidence',
    revision: 'إنشاء Superseding Evidence Revision',
    finding: 'تسجيل Review Finding',
    decision: 'إصدار Review Decision',
    mastery: 'إلحاق Mastery State',
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
  capability_id: '',
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

const selectedIntakeReceipt = computed(() =>
  props.handoff_receipts.find((receipt) => receipt.id === intake.handoff_receipt_id),
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
  masteryForm.capability_id = item?.capability_id ?? selectedMastery.value?.target_id ?? '';
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
  <main class="workspace" dir="rtl">
    <header class="top-bar">
      <div class="workspace-identity">
        <p class="eyebrow"><bdi dir="ltr">PROGRESS &amp; EVIDENCE</bdi></p>
        <div>
          <h1>التقدم والأدلة</h1>
          <p>
            {{ activeNav.ar }} <span>·</span> <bdi dir="ltr">{{ activeNav.en }}</bdi>
          </p>
        </div>
      </div>

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

        <button
          v-else-if="surface === 'mastery'"
          class="button primary"
          type="button"
          :disabled="!mastery_policies.length"
          @click="openMastery"
        >
          إلحاق Mastery State
        </button>

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
    </header>

    <p v-if="page.props.flash?.status" class="notice success">{{ page.props.flash.status }}</p>
    <p v-if="page.props.errors?.workflow" class="notice error">{{ page.props.errors.workflow }}</p>

    <div class="workspace-grid">
      <nav class="left-rail panel" aria-label="المناطق الأساسية للتقدم والأدلة">
        <div class="rail-heading">
          <span>المناطق الأساسية</span>
          <bdi dir="ltr">4 AREAS</bdi>
        </div>
        <a
          v-for="item in nav"
          :key="item.key"
          class="area-link"
          :class="{ active: surface === item.key }"
          :href="item.href"
        >
          <span>{{ item.ar }}</span>
          <bdi dir="ltr">{{ item.en }}</bdi>
        </a>
      </nav>

      <section class="center-workspace panel">
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
          <aside class="record-browser" aria-label="قائمة Evidence">
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
          </aside>

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
            <section class="detail-block">
              <span class="label">Sealed Revision History</span>
              <ol class="timeline-list">
                <li v-for="item in selectedEvidence.revisions" :key="item.id">
                  <span class="timeline-marker" aria-hidden="true"></span>
                  <div>
                    <bdi dir="ltr">R{{ item.revision }} · {{ item.id }}</bdi>
                    <p>{{ item.revision_reason }}</p>
                  </div>
                </li>
              </ol>
            </section>
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
          <aside class="record-browser" aria-label="قائمة المراجعات">
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
          </aside>

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
          <aside class="record-browser" aria-label="حالات Mastery">
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
              <bdi dir="ltr" class="state">{{ item.id }}</bdi>
            </button>
            <p v-if="!mastery.length" class="empty-state">لا توجد Mastery State محكومة.</p>
          </aside>

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
          <aside class="record-browser" aria-label="عروض Portfolio">
            <div class="browser-title">
              <span>Saved Views</span><bdi dir="ltr">{{ portfolios.length }}</bdi>
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
          </aside>

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

      <aside
        class="right-context panel"
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
            <div class="context-section">
              <span class="label">المعنى الحاكم</span>
              <p>
                Admission ينشئ Evidence Revision 1 مختومة، لكنه لا يبدأ Review ولا يصدر Decision ولا
                يغيّر Mastery.
              </p>
            </div>
          </template>
          <template v-else-if="selectedEvidence">
            <h2>سياق الحوكمة</h2>
            <div class="context-callout">
              <span class="context-icon">i</span>
              <p>الحالات الثلاث المعروضة في CENTER مستقلة. لا تُختزل في Status واحد.</p>
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
          <p class="eyebrow">State Provenance</p>
          <h2>السجل التاريخي</h2>
          <p class="context-intro">
            هذه السلسلة توضّح append-only provenance فقط؛ الحكم والحداثة لهما Home دائم في CENTER.
          </p>
          <ol class="history-chain">
            <li v-for="state in selectedMasteryHistory" :key="state.id">
              <bdi dir="ltr">{{ state.id }}</bdi
              ><small v-if="state.previous_state_id"
                >previous: <bdi dir="ltr">{{ state.previous_state_id }}</bdi></small
              ><small v-else>initial state</small>
            </li>
          </ol>
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
          <div class="context-callout">
            <span class="context-icon">i</span>
            <p>
              Portfolio يحتفظ بالتنظيم والـcuration فقط. الحقيقة القانونية تبقى في Evidence
              وMastery.
            </p>
          </div>
        </template>

        <p v-else class="empty-state">لا يوجد سياق فريد للاختيار الحالي.</p>
      </aside>
    </div>

    <section v-if="panel" class="bottom-workspace panel" aria-label="مساحة العمل المؤقتة">
      <div class="bottom-header">
        <div>
          <p class="eyebrow">Temporary Deep Workspace</p>
          <h2>{{ panelTitle }}</h2>
        </div>
        <button class="button ghost" type="button" @click="panel = null">إغلاق</button>
      </div>

      <form
        v-if="panel === 'intake'"
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
        <button class="button primary form-submit" type="submit">Seal Superseding Revision</button>
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
        <label>Capability ID<input v-model="masteryForm.capability_id" dir="ltr" required /></label>
        <p v-if="!mastery_policies.length" class="empty-state">
          لا توجد Mastery Policy Revision معتمدة؛ لن يُنشأ حكم إتقان من Policy ID حر.
        </p>
        <label>Capability ID<input v-model="masteryForm.capability_id" dir="ltr" required /></label>
        <label>
          Policy Revision
          <select v-model="masteryForm.policy_revision_id" dir="ltr" required>
            <option v-for="policy in mastery_policies" :key="policy.id" :value="policy.id">
              {{ policy.policy_key }}@{{ policy.revision }} ·
              {{ policy.qualifying_review_decisions.join(', ') }}
            </option>
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
        <label>Grouping<input v-model="portfolioForm.grouping" dir="ltr" required /></label>
        <button class="button primary form-submit" type="submit">إنشاء Reference Projection</button>
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
        <button class="button primary form-submit" type="submit">إضافة Canonical Reference</button>
      </form>
    </section>
  </main>
</template>

<style scoped>
:global(body) {
  margin: 0;
  background: #050b12;
}

* {
  box-sizing: border-box;
}

.workspace {
  min-height: 100vh;
  padding: 1rem;
  color: #d6e1ea;
  background:
    radial-gradient(circle at 55% -20%, rgba(36, 99, 116, 0.15), transparent 34rem), #050b12;
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
  color: #7890a4;
  font-size: 0.72rem;
  line-height: 1.6;
}

.eyebrow,
.label {
  margin: 0;
  color: #6fc5cd;
  font-size: 0.66rem;
  font-weight: 800;
  letter-spacing: 0.03em;
}

.label {
  display: block;
  margin-bottom: 0.35rem;
  color: #7690a5;
}

.top-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  justify-content: flex-end;
}

.button {
  min-height: 2.25rem;
  padding: 0.48rem 0.72rem;
  color: #c9d8e3;
  font: inherit;
  font-size: 0.72rem;
  font-weight: 700;
  border: 1px solid #2b475a;
  border-radius: 0.45rem;
  background: #0a1824;
  cursor: pointer;
}

.button.primary {
  color: #062027;
  border-color: #61c3c8;
  background: #70cbd0;
}

.button.secondary:hover,
.button.ghost:hover {
  border-color: #4d7e94;
  background: #0d2130;
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
  color: #a7e5cc;
  border-color: #2d6754;
  background: rgba(35, 105, 76, 0.12);
}

.notice.error {
  color: #f1b4bc;
  border-color: #7c3b47;
  background: rgba(118, 48, 61, 0.12);
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

.left-rail {
  grid-area: left;
  position: sticky;
  top: 1rem;
  overflow: hidden;
}

.rail-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.72rem;
  color: #70889b;
  font-size: 0.64rem;
  border-bottom: 1px solid #1d3143;
}

.area-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 3.2rem;
  padding: 0.72rem;
  color: #8da2b4;
  font-size: 0.76rem;
  text-decoration: none;
  border-bottom: 1px solid #172a3a;
}

.area-link:last-child {
  border-bottom: 0;
}

.area-link.active {
  color: #e5f1f7;
  background: linear-gradient(90deg, rgba(77, 183, 193, 0.16), rgba(12, 32, 46, 0.65));
  box-shadow: inset 3px 0 #66c5cd;
}

.area-link bdi {
  color: #627b8f;
  font-size: 0.62rem;
}

.center-workspace {
  grid-area: center;
  overflow: hidden;
}

.surface-heading {
  min-height: 4.2rem;
  padding: 0.85rem 1rem;
  border-bottom: 1px solid #1d3143;
}

.surface-heading > p {
  max-width: 34rem;
  text-align: left;
}

.workbench-grid {
  display: grid;
  grid-template-columns: 16rem minmax(0, 1fr);
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
  color: #6f879b;
  font-size: 0.66rem;
  background: rgba(14, 31, 44, 0.58);
}

.record-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.55rem;
  align-items: center;
  width: 100%;
  padding: 0.68rem 0.75rem;
  color: #aebfcb;
  text-align: right;
  border: 0;
  border-top: 1px solid #172a3a;
  background: transparent;
  cursor: pointer;
}

.record-row:hover,
.record-row.selected {
  background: #0c2030;
}

.record-row.selected {
  box-shadow: inset -2px 0 #62c4cb;
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
  color: #cddce7;
  font-size: 0.73rem;
}

.record-row small {
  margin-top: 0.2rem;
  color: #6f879b;
  font-size: 0.65rem;
}

.state {
  display: inline-flex;
  max-width: 10rem;
  padding: 0.25rem 0.42rem;
  color: #9edfe1;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.61rem;
  border: 1px solid #315c66;
  border-radius: 999px;
  background: rgba(43, 107, 112, 0.13);
  overflow-wrap: anywhere;
}

.candidate-state {
  color: #e2c580;
  border-color: #66532e;
  background: rgba(106, 77, 29, 0.14);
}

.request-state {
  color: #9fd3ef;
  border-color: #315976;
}

.review-state {
  color: #9ce4c9;
  border-color: #2b6452;
}

.projection-state {
  color: #a8cde1;
  border-color: #3a586d;
}

.object-workbench {
  min-width: 0;
  padding: 1rem;
}

.object-heading {
  padding-bottom: 0.85rem;
  border-bottom: 1px solid #1d3143;
}

.truth-banner,
.context-callout {
  padding: 0.72rem;
  border: 1px solid #2a4b60;
  border-radius: 0.55rem;
  background: rgba(25, 66, 86, 0.12);
}

.truth-banner {
  display: grid;
  gap: 0.2rem;
  margin-top: 0.8rem;
}

.truth-banner strong {
  color: #d6e6ef;
  font-size: 0.72rem;
}

.truth-banner span,
.context-callout p {
  margin: 0;
  color: #839aab;
  font-size: 0.72rem;
  line-height: 1.65;
}

.candidate-banner {
  border-color: #66532e;
  background: rgba(98, 72, 29, 0.12);
}

.detail-block,
.decision-block {
  padding: 0.8rem;
  margin-top: 0.75rem;
  border: 1px solid #1d3447;
  border-radius: 0.55rem;
  background: rgba(5, 15, 24, 0.6);
}

.detail-block.compact {
  margin-top: 0;
}

.detail-block p,
.decision-block p,
.context-section p {
  margin-bottom: 0;
  color: #9aafbf;
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
  color: #a8c5d7;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.68rem;
  overflow-wrap: anywhere;
}

.muted {
  color: #687f92 !important;
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
  border: 1px solid #1b3042;
  border-radius: 0.35rem;
  background: #07131e;
}

.dimension-card,
.mastery-dimension {
  display: grid;
  gap: 0.4rem;
  align-content: center;
  min-height: 4.2rem;
  padding: 0.65rem;
  border: 1px solid #223c50;
  border-radius: 0.5rem;
  background: #081724;
}

.dimension-card span,
.mastery-dimension span {
  color: #6d8497;
  font-size: 0.63rem;
}

.dimension-card bdi,
.mastery-dimension bdi {
  color: #dce9f1;
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
  border: 2px solid #64c1ca;
  border-radius: 50%;
}

.timeline-list p {
  margin: 0.15rem 0 0;
  color: #7890a3;
  font-size: 0.68rem;
}

.finding-stack {
  display: grid;
  gap: 0.5rem;
  margin-top: 0.55rem;
}

.finding-card {
  padding: 0.6rem;
  border: 1px solid #20394c;
  border-radius: 0.45rem;
  background: #071522;
}

.finding-value,
.decision-value {
  color: #a3e5cc;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.68rem;
}

.decision-block {
  border-color: #29495d;
  background: rgba(9, 29, 42, 0.74);
}

.decision-content {
  margin-top: 0.55rem;
}

.decision-value {
  display: inline-flex;
  padding: 0.25rem 0.4rem;
  border: 1px solid #316553;
  border-radius: 0.35rem;
}

.mastery-dimension {
  min-height: 5rem;
}

.judgment-card {
  border-color: #355b74;
  background: rgba(28, 70, 94, 0.17);
}

.freshness-card {
  border-color: #66532e;
  background: rgba(101, 74, 28, 0.14);
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
  border: 1px solid #1e3547;
  border-radius: 0.45rem;
  background: #07141f;
}

.step-number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.55rem;
  height: 1.55rem;
  color: #86d8df;
  font-size: 0.62rem;
  border: 1px solid #2c6670;
  border-radius: 50%;
}

.causal-steps strong {
  display: block;
  margin-bottom: 0.25rem;
  color: #c4d6e2;
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
  color: #b9cad6;
  text-align: right;
  border: 1px solid #203749;
  border-radius: 0.48rem;
  background: #071522;
  cursor: pointer;
}

.portfolio-reference.selected,
.portfolio-reference:hover {
  border-color: #3d7084;
  background: #0b2030;
}

.portfolio-reference strong,
.portfolio-reference small {
  display: block;
}

.portfolio-reference small {
  margin-top: 0.15rem;
  color: #647e92;
  font-size: 0.62rem;
}

.empty-state,
.empty-workbench {
  color: #617a8e;
  font-size: 0.7rem;
  text-align: center;
  border: 1px dashed #294052;
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
  grid-area: right;
  position: sticky;
  top: 1rem;
  min-width: 0;
  padding: 0.85rem;
}

.right-context h2 {
  margin: 0.28rem 0 0;
  font-size: 0.95rem;
}

.context-section {
  padding-top: 0.7rem;
  margin-top: 0.7rem;
  border-top: 1px solid #1d3143;
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
  border-color: #66532e;
  background: rgba(91, 67, 24, 0.12);
}

.context-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.4rem;
  height: 1.4rem;
  color: #86d8df;
  font-size: 0.68rem;
  font-weight: 900;
  border: 1px solid #356779;
  border-radius: 50%;
}

.authority-block {
  display: grid;
  gap: 0.3rem;
  margin-top: 0.75rem;
}

.authority-block > span {
  color: #71899c;
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
  border: 1px solid #1e3547;
  border-radius: 0.4rem;
  background: #07141f;
}

.history-chain small {
  color: #627b8f;
  font-size: 0.6rem;
}

.bottom-workspace {
  padding: 0.9rem;
  margin-top: 0.75rem;
  border-radius: 0.8rem;
  box-shadow: 0 -16px 44px rgba(0, 0, 0, 0.24);
}

.bottom-header {
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #1d3143;
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
  color: #91a8b8;
  font-size: 0.68rem;
}

.form-grid input,
.form-grid textarea,
.form-grid select {
  width: 100%;
  padding: 0.58rem 0.62rem;
  color: #e5eff5;
  font: inherit;
  border: 1px solid #2a4558;
  border-radius: 0.42rem;
  background: #050e17;
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
  color: #8198a9;
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
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
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
  .form-grid {
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

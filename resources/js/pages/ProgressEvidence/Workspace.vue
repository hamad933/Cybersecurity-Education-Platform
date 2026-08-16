<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Candidate = {
  id: string;
  capability_id: string;
  proposed_title: string;
  proposed_summary: string;
  state: string;
  source_id: string;
  source_revision: string;
  evidence_claim: string;
  selected_material_refs: string[];
  criterion_scope: string[];
};

type EvidenceRevision = {
  id: string;
  revision: number;
  previous_revision_id: string | null;
  revision_reason: string;
};

type Evidence = {
  id: string;
  capability_id: string;
  evidence_claim: string;
  lifecycle_state: string;
  review_status: string;
  effective_review_decision: string;
  effective_review_decision_id: string | null;
  current_revision_number: number;
  current_revision_id: string;
  title: string;
  summary: string;
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
type Panel =
  | 'intake'
  | 'revision'
  | 'finding'
  | 'decision'
  | 'mastery'
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
}>();

const page = usePage<{ flash?: { status?: string }; errors?: Record<string, string> }>();
const panel = ref<Panel>(null);
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

const intake = useForm({
  source_type: 'SOURCE_HANDOFF',
  source_id: '',
  source_revision: '',
  source_digest: '',
  selected_material_refs: [''] as [string],
  capability_id: '',
  evidence_claim: '',
  criterion_scope: [''] as [string],
  governed_purpose: 'FORMAL_CAPABILITY_EVIDENCE',
  title: '',
  summary: '',
});
const revision = useForm({ title: '', summary: '', revision_reason: '' });
const finding = useForm({
  criterion_key: '',
  finding: 'SATISFIED',
  statement: '',
  supporting_evidence_revision_ids: [] as string[],
});
const decision = useForm({ decision: 'ACCEPT', rationale: '' });
const masteryForm = useForm({
  capability_id: '',
  policy_revision_id: '',
  judgment: 'NOT_EVALUATED',
  freshness_status: 'CURRENT',
  review_decision_ids: [] as string[],
  supporting_evidence_revision_ids: [] as string[],
  contradicting_evidence_revision_ids: [] as string[],
  rationale: '',
});
const portfolioForm = useForm({
  name: '',
  view_scope: '',
  grouping: 'CAPABILITY',
});
const portfolioAdd = useForm({ evidence_id: '', annotation: '', sort_order: 0 });

const nav = [
  { key: 'evidence', href: '/progress', ar: 'الأدلة', en: 'Evidence' },
  { key: 'reviews', href: '/progress/reviews', ar: 'المراجعات', en: 'Reviews' },
  { key: 'mastery', href: '/progress/mastery', ar: 'الإتقان', en: 'Mastery' },
  { key: 'portfolio', href: '/progress/portfolio', ar: 'الملف المهني', en: 'Portfolio' },
] as const;

const candidateAction = computed(() => {
  switch (candidate.value?.state) {
    case 'RECEIVED':
    case 'DRAFT':
    case 'RETURNED_FOR_CONTEXT':
      return { label: 'تحضير Candidate', state: 'PREPARED' };
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

function runCandidateAction(): void {
  const item = candidate.value;
  const action = candidateAction.value;
  if (!item || !action) return;

  if (action.state === 'ADMIT') {
    router.post(`/progress/candidates/${item.id}/admit`);

    return;
  }

  router.post(`/progress/candidates/${item.id}/state`, { state: action.state });
}

function openRevision(): void {
  const item = selectedEvidence.value;
  if (!item) return;

  revision.title = item.title;
  revision.summary = item.summary;
  revision.revision_reason = '';
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

  finding.post(`/progress/reviews/${item.id}/findings`, {
    onSuccess: () => (panel.value = null),
  });
}

function submitDecision(): void {
  const item = selectedReview.value;
  if (!item) return;

  decision.post(`/progress/reviews/${item.id}/decision`, {
    onSuccess: () => (panel.value = null),
  });
}

function openMastery(): void {
  const item =
    selectedEvidence.value ??
    props.evidence.find(
      (evidence) =>
        evidence.effective_review_decision_id !== null && evidence.lifecycle_state === 'ACTIVE',
    );

  masteryForm.capability_id = item?.capability_id ?? selectedMastery.value?.target_id ?? '';
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
    <header class="workspace-header">
      <div>
        <p class="eyebrow">PROGRESS &amp; EVIDENCE / A03</p>
        <h1>التقدم والأدلة المحكومة</h1>
      </div>
      <div class="actions">
        <template v-if="surface === 'evidence'">
          <button class="action-button" @click="panel = 'intake'">إدخال Candidate</button>
          <button class="action-button" :disabled="!candidateAction" @click="runCandidateAction">
            {{ candidateAction?.label ?? 'لا يوجد انتقال متاح' }}
          </button>
          <button class="action-button" :disabled="!selectedEvidence" @click="openRevision">
            Revision جديدة
          </button>
          <button
            class="action-button"
            :disabled="!selectedEvidence || selectedEvidence.lifecycle_state !== 'ACTIVE'"
            @click="requestReview"
          >
            طلب مراجعة
          </button>
        </template>
        <template v-else-if="surface === 'reviews'">
          <button
            class="action-button"
            :disabled="selectedRequest?.status !== 'REQUESTED'"
            @click="admitRequest"
          >
            بدء Review
          </button>
          <button
            class="action-button"
            :disabled="
              !selectedReview || !['IN_REVIEW', 'READY_FOR_DECISION'].includes(selectedReview.status)
            "
            @click="openFinding"
          >
            إضافة Finding
          </button>
          <button
            class="action-button"
            :disabled="
              selectedReview?.status !== 'READY_FOR_DECISION' || !selectedReview.findings.length
            "
            @click="panel = 'decision'"
          >
            تسجيل Decision
          </button>
        </template>
        <button v-else-if="surface === 'mastery'" class="action-button" @click="openMastery">
          إلحاق Mastery State
        </button>
        <template v-else>
          <button class="action-button" @click="panel = 'portfolio'">إنشاء Portfolio View</button>
          <button
            class="action-button"
            :disabled="!selectedPortfolio || !evidence.length"
            @click="openPortfolioAdd"
          >
            إضافة Evidence Reference
          </button>
          <button
            class="action-button"
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
      <nav class="surface-nav" aria-label="بنية التقدم والأدلة">
        <a
          v-for="item in nav"
          :key="item.key"
          :href="item.href"
          :class="{ active: surface === item.key }"
        >
          <span>{{ item.ar }}</span>
          <bdi>{{ item.en }}</bdi>
        </a>
      </nav>

      <section class="content-panel">
        <template v-if="surface === 'evidence'">
          <p class="intro">
            Candidate → Admission → sealed Evidence Revision. Admission لا يعني Review أو Mastery.
          </p>
          <h2>Candidate Evidence</h2>
          <button
            v-for="item in candidates"
            :key="item.id"
            class="record"
            :class="{ selected: item.id === candidateId }"
            @click="candidateId = item.id"
          >
            <span>
              <strong>{{ item.proposed_title }}</strong>
              <small>{{ item.evidence_claim }}</small>
            </span>
            <span class="record-state">
              <bdi>{{ item.capability_id }}</bdi>
              <span>{{ item.state }}</span>
            </span>
          </button>
          <p v-if="!candidates.length" class="empty-state">لا توجد Candidate Evidence.</p>

          <h2 class="section-title">Canonical Evidence</h2>
          <button
            v-for="item in evidence"
            :key="item.id"
            class="record"
            :class="{ selected: item.id === evidenceId }"
            @click="evidenceId = item.id"
          >
            <span>
              <strong>{{ item.title }}</strong>
              <small>{{ item.evidence_claim }}</small>
              <small>
                Revision {{ item.current_revision_number }} · {{ item.revisions.length }} sealed
                revision(s)
              </small>
            </span>
            <span class="record-state">
              <span>{{ item.lifecycle_state }}</span>
              <span>{{ item.review_status }}</span>
              <span>{{ item.effective_review_decision }}</span>
            </span>
          </button>
        </template>

        <template v-else-if="surface === 'reviews'">
          <p class="intro">كل Review Request يثبت Evidence Revision ونطاقًا ومعايير محددة.</p>
          <h2>Review Requests</h2>
          <button
            v-for="item in review_requests"
            :key="item.id"
            class="record"
            :class="{ selected: item.id === requestId }"
            @click="requestId = item.id"
          >
            <strong>{{ evidenceTitle(item.evidence_id) }}</strong>
            <small>
              <bdi>{{ item.evidence_revision_id }}</bdi> · {{ item.review_scope_key }} ·
              {{ item.status }}
            </small>
          </button>

          <h2 class="section-title">Evidence Reviews</h2>
          <button
            v-for="item in reviews"
            :key="item.id"
            class="record"
            :class="{ selected: item.id === reviewId }"
            @click="reviewId = item.id"
          >
            <strong>{{ evidenceTitle(item.evidence_id) }}</strong>
            <small>{{ item.findings.length }} Finding(s) · {{ item.status }}</small>
            <bdi v-if="item.decision">{{ item.decision.decision }}</bdi>
          </button>
        </template>

        <template v-else-if="surface === 'mastery'">
          <p class="intro">
            Judgment ≠ Freshness، وكل تقييم يولّد Mastery State جديدة بدل overwrite.
          </p>
          <button
            v-for="item in mastery"
            :key="item.id"
            class="record"
            :class="{ selected: item.id === masteryId }"
            @click="masteryId = item.id"
          >
            <strong><bdi>{{ item.target_id }}</bdi></strong>
            <span class="dimensions">
              <bdi>{{ item.judgment }}</bdi>
              <bdi>{{ item.freshness_status }}</bdi>
            </span>
            <small>
              {{ item.review_decision_ids.length }} Decision ref(s) ·
              {{ item.supporting_evidence_revision_ids.length }} supporting Revision ref(s)
            </small>
          </button>
        </template>

        <template v-else>
          <p class="intro">Portfolio هو projection مرجعي ولا يكرر Evidence أو Mastery truth.</p>
          <article
            v-for="item in portfolios"
            :key="item.id"
            class="portfolio-card"
            :class="{ selected: item.id === portfolioId }"
            @click="portfolioId = item.id"
          >
            <h2>{{ item.name }}</h2>
            <button
              v-for="entry in item.items"
              :key="entry.id"
              class="portfolio-item"
              :class="{ selected: entry.id === portfolioItemId }"
              @click.stop="portfolioId = item.id; portfolioItemId = entry.id"
            >
              <strong>{{ entry.title }}</strong>
              <bdi>{{ entry.current_revision_id }}</bdi>
            </button>
          </article>
        </template>
      </section>

      <aside class="context-panel" aria-label="السياق الفريد">
        <template v-if="surface === 'evidence' && selectedEvidence">
          <p class="eyebrow">Sealed Revision History</p>
          <h2>السجل غير القابل لإعادة الكتابة</h2>
          <ol>
            <li v-for="item in selectedEvidence.revisions" :key="item.id">
              <bdi>R{{ item.revision }}</bdi> — {{ item.revision_reason }}
            </li>
          </ol>
        </template>
        <template v-else-if="surface === 'evidence' && candidate">
          <p class="eyebrow">Candidate Semantic Identity</p>
          <p>
            <bdi>{{ candidate.source_id }}@{{ candidate.source_revision }}</bdi>
          </p>
          <p>
            {{ candidate.selected_material_refs.length }} material ref(s) ·
            {{ candidate.criterion_scope.length }} criterion ref(s)
          </p>
        </template>
        <template v-else-if="surface === 'reviews' && selectedReview">
          <p class="eyebrow">Reviewer Authority</p>
          <p>Reviewer: <bdi>{{ selectedReview.reviewer_id }}</bdi></p>
          <p>Revision: <bdi>{{ selectedReview.evidence_revision_id }}</bdi></p>
          <p>Scope: <bdi>{{ selectedReview.review_scope_key }}</bdi></p>
        </template>
        <template v-else-if="surface === 'mastery' && selectedMastery">
          <p class="eyebrow">Mastery Provenance</p>
          <p>Policy: <bdi>{{ selectedMastery.policy_revision_id }}</bdi></p>
          <p>History: {{ selectedMasteryHistory.length }} state(s)</p>
          <p>Decisions: {{ selectedMastery.review_decision_ids.length }}</p>
          <p>Supporting: {{ selectedMastery.supporting_evidence_revision_ids.length }}</p>
          <p>Contradicting: {{ selectedMastery.contradicting_evidence_revision_ids.length }}</p>
        </template>
        <template v-else-if="surface === 'portfolio' && selectedPortfolio">
          <p class="eyebrow">View Configuration</p>
          <p>Scope: {{ selectedPortfolio.view_scope || 'غير مقيّد' }}</p>
          <p>Grouping: <bdi>{{ selectedPortfolio.grouping }}</bdi></p>
        </template>
      </aside>
    </div>

    <section v-if="panel" class="operation-panel">
      <div class="operation-header">
        <h2>إجراء محكوم</h2>
        <button class="action-button" @click="panel = null">إغلاق</button>
      </div>

      <form
        v-if="panel === 'intake'"
        class="form-grid"
        @submit.prevent="intake.post('/progress/intake', { onSuccess: () => (panel = null) })"
      >
        <label>Source Type<input v-model="intake.source_type" dir="ltr" required /></label>
        <label>Source ID<input v-model="intake.source_id" dir="ltr" required /></label>
        <label>Source Revision<input v-model="intake.source_revision" dir="ltr" required /></label>
        <label>
          Source SHA-256
          <input v-model="intake.source_digest" dir="ltr" minlength="64" maxlength="64" required />
        </label>
        <label>
          Selected Material<input v-model="intake.selected_material_refs[0]" dir="ltr" required />
        </label>
        <label>Capability ID<input v-model="intake.capability_id" dir="ltr" required /></label>
        <label class="wide">Evidence Claim<textarea v-model="intake.evidence_claim" required /></label>
        <label>Criterion Ref<input v-model="intake.criterion_scope[0]" dir="ltr" required /></label>
        <label>Governed Purpose<input v-model="intake.governed_purpose" dir="ltr" required /></label>
        <label>العنوان<input v-model="intake.title" required /></label>
        <label>الملخص<textarea v-model="intake.summary" required /></label>
        <button class="primary-button">إنشاء Candidate في RECEIVED</button>
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
        <label>Revision Reason<input v-model="revision.revision_reason" required /></label>
        <label class="wide">الملخص<textarea v-model="revision.summary" required /></label>
        <button class="primary-button">Seal Superseding Revision</button>
      </form>

      <form
        v-else-if="panel === 'finding' && selectedReview"
        class="form-grid"
        @submit.prevent="submitFinding"
      >
        <label>Criterion Key<input v-model="finding.criterion_key" dir="ltr" required /></label>
        <label>
          Finding
          <select v-model="finding.finding">
            <option>SATISFIED</option>
            <option>PARTIALLY_SATISFIED</option>
            <option>NOT_SATISFIED</option>
            <option>NOT_ASSESSABLE</option>
          </select>
        </label>
        <label class="wide">البيان<textarea v-model="finding.statement" required /></label>
        <button class="primary-button">تسجيل Finding</button>
      </form>

      <form
        v-else-if="panel === 'decision' && selectedReview"
        class="form-grid"
        @submit.prevent="submitDecision"
      >
        <label>
          Review Decision
          <select v-model="decision.decision">
            <option>ACCEPT</option>
            <option>ACCEPT_WITH_LIMITATIONS</option>
            <option>MORE_EVIDENCE_REQUIRED</option>
            <option>REJECT</option>
          </select>
        </label>
        <label>المسوّغ<textarea v-model="decision.rationale" required /></label>
        <button class="primary-button">Seal Decision</button>
      </form>

      <form
        v-else-if="panel === 'mastery'"
        class="form-grid"
        @submit.prevent="
          masteryForm.post('/progress/mastery/evaluate', { onSuccess: () => (panel = null) })
        "
      >
        <label>Capability ID<input v-model="masteryForm.capability_id" dir="ltr" required /></label>
        <label>
          Policy Revision<input v-model="masteryForm.policy_revision_id" dir="ltr" required />
        </label>
        <label>
          Judgment
          <select v-model="masteryForm.judgment">
            <option>NOT_EVALUATED</option>
            <option>INSUFFICIENT_EVIDENCE</option>
            <option>INCONCLUSIVE</option>
            <option>NOT_MASTERED</option>
            <option>MASTERED</option>
          </select>
        </label>
        <label>
          Freshness
          <select v-model="masteryForm.freshness_status">
            <option>CURRENT</option>
            <option>REVALIDATION_REQUIRED</option>
          </select>
        </label>
        <label class="wide">المسوّغ<textarea v-model="masteryForm.rationale" required /></label>
        <p class="wide"><bdi>MASTERED + REVALIDATION_REQUIRED</bdi> حالة قانونية في A03.</p>
        <button class="primary-button">Append Mastery State</button>
      </form>

      <form
        v-else-if="panel === 'portfolio'"
        class="form-grid"
        @submit.prevent="portfolioForm.post('/progress/portfolio', { onSuccess: () => (panel = null) })"
      >
        <label>اسم العرض<input v-model="portfolioForm.name" required /></label>
        <label>View Scope<input v-model="portfolioForm.view_scope" /></label>
        <label>Grouping<input v-model="portfolioForm.grouping" dir="ltr" required /></label>
        <button class="primary-button">إنشاء Reference Projection</button>
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
        <label>ملاحظة<textarea v-model="portfolioAdd.annotation" /></label>
        <button class="primary-button">إضافة Canonical Reference</button>
      </form>
    </section>
  </main>
</template>

<style scoped>
.workspace {
  min-height: 100vh;
  padding: 1.25rem;
  color: #e2e8f0;
  background: #020617;
}

.workspace-header,
.content-panel,
.context-panel,
.surface-nav,
.operation-panel {
  border: 1px solid #1e293b;
  border-radius: 0.75rem;
  background: #0f172a;
}

.workspace-header {
  display: flex;
  gap: 1rem;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  margin-bottom: 1rem;
}

.workspace-header h1,
.content-panel h2,
.context-panel h2,
.operation-panel h2 {
  margin: 0.25rem 0;
  font-weight: 700;
}

.eyebrow {
  margin: 0;
  color: #67e8f9;
  font-size: 0.75rem;
  font-weight: 700;
}

.actions,
.dimensions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.action-button,
.primary-button,
.record,
.portfolio-item {
  border: 1px solid #334155;
  border-radius: 0.5rem;
  color: inherit;
  background: transparent;
  cursor: pointer;
}

.action-button,
.primary-button {
  padding: 0.6rem 0.8rem;
}

.primary-button {
  color: #082f49;
  font-weight: 700;
  background: #67e8f9;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.notice {
  padding: 0.75rem;
  margin: 0 0 1rem;
  border: 1px solid;
  border-radius: 0.5rem;
}

.success {
  border-color: #047857;
}

.error {
  border-color: #be123c;
}

.workspace-grid {
  display: grid;
  grid-template-columns: 11rem minmax(0, 1fr) 19rem;
  gap: 1rem;
}

.surface-nav,
.content-panel,
.context-panel {
  padding: 1rem;
}

.surface-nav a {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem;
  color: #94a3b8;
  text-decoration: none;
  border-radius: 0.5rem;
}

.surface-nav a.active,
.record.selected,
.portfolio-card.selected,
.portfolio-item.selected {
  border-color: #22d3ee;
  color: #f8fafc;
}

.surface-nav a.active {
  background: #1e293b;
}

.intro,
.context-panel p,
.record small {
  color: #94a3b8;
  line-height: 1.7;
}

.record {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 1rem;
  width: 100%;
  padding: 0.85rem;
  margin-top: 0.5rem;
  text-align: right;
}

.record strong,
.record small,
.record bdi,
.record-state span,
.record-state bdi,
.portfolio-item strong,
.portfolio-item bdi {
  display: block;
}

.record-state {
  text-align: left;
}

.record-state,
.dimensions,
bdi {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}

.section-title {
  padding-top: 1rem;
  margin-top: 1.25rem !important;
  border-top: 1px solid #1e293b;
}

.empty-state {
  padding: 1rem;
  color: #64748b;
  text-align: center;
  border: 1px dashed #334155;
  border-radius: 0.5rem;
}

.portfolio-card {
  padding: 0.75rem;
  margin-top: 0.75rem;
  border: 1px solid #334155;
  border-radius: 0.5rem;
}

.portfolio-item {
  width: 100%;
  padding: 0.75rem;
  margin-top: 0.5rem;
  text-align: right;
}

.context-panel ol {
  padding-right: 1.25rem;
}

.context-panel li {
  padding: 0.5rem;
  margin-top: 0.5rem;
  border: 1px solid #1e293b;
  border-radius: 0.5rem;
}

.operation-panel {
  padding: 1rem;
  margin-top: 1rem;
}

.operation-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.form-grid label {
  display: grid;
  gap: 0.25rem;
}

.form-grid input,
.form-grid textarea,
.form-grid select {
  width: 100%;
  padding: 0.65rem;
  color: #f1f5f9;
  border: 1px solid #334155;
  border-radius: 0.5rem;
  background: #020617;
}

.form-grid textarea {
  min-height: 5rem;
}

.wide {
  grid-column: 1 / -1;
}

@media (max-width: 1100px) {
  .workspace-grid {
    grid-template-columns: 1fr;
  }

  .surface-nav {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (max-width: 700px) {
  .workspace-header,
  .form-grid {
    display: grid;
    grid-template-columns: 1fr;
  }

  .surface-nav {
    grid-template-columns: repeat(2, 1fr);
  }

  .wide {
    grid-column: auto;
  }
}
</style>
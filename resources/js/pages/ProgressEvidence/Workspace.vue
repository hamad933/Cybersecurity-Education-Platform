<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
  revision: number;
  previous_revision_id: string | null;
  revision_reason: string;
  content_digest: string;
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
  content_digest: string;
  facts: Record<string, unknown>;
  revisions: EvidenceRevision[];
};

type ReviewRequest = {
  id: string;
  evidence_id: string;
  evidence_revision_id: string;
  review_scope_key: string;
  criterion_refs: string[];
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
  review_request_id: string;
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
  rationale: string;
};

type PortfolioItem = {
  id: string;
  evidence_id: string;
  current_revision_id: string;
  title: string;
  annotation: string | null;
  lifecycle_state: string;
  effective_review_decision: string;
};

type Portfolio = {
  id: string;
  name: string;
  view_scope: string | null;
  grouping: string;
  filters: Record<string, unknown>;
  annotations: Record<string, unknown>;
  items: PortfolioItem[];
};

type Surface = 'evidence' | 'reviews' | 'mastery' | 'portfolio';
type Panel = 'intake' | 'revision' | 'finding' | 'decision' | 'mastery' | 'portfolio' | 'portfolio-add' | null;

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
  props.review_requests.find((item) => item.status === 'REQUESTED')?.id ?? props.review_requests[0]?.id ?? '',
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
const selectedEvidence = computed(() => props.evidence.find((item) => item.id === evidenceId.value));
const selectedRequest = computed(() => props.review_requests.find((item) => item.id === requestId.value));
const selectedReview = computed(() => props.reviews.find((item) => item.id === reviewId.value));
const selectedMastery = computed(() => props.mastery.find((item) => item.id === masteryId.value));
const selectedPortfolio = computed(() => props.portfolios.find((item) => item.id === portfolioId.value));
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
  facts: {} as Record<string, unknown>,
  metadata: {} as Record<string, unknown>,
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
  filters: {} as Record<string, unknown>,
  annotations: {} as Record<string, unknown>,
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
  finding.post(`/progress/reviews/${item.id}/findings`, { onSuccess: () => (panel.value = null) });
}

function submitDecision(): void {
  const item = selectedReview.value;
  if (!item) return;
  decision.post(`/progress/reviews/${item.id}/decision`, { onSuccess: () => (panel.value = null) });
}

function openMastery(): void {
  const item =
    selectedEvidence.value ??
    props.evidence.find(
      (evidence) => evidence.effective_review_decision_id !== null && evidence.lifecycle_state === 'ACTIVE',
    );
  masteryForm.capability_id = item?.capability_id ?? selectedMastery.value?.target_id ?? '';
  masteryForm.review_decision_ids = item?.effective_review_decision_id ? [item.effective_review_decision_id] : [];
  masteryForm.supporting_evidence_revision_ids = item?.effective_review_decision_id ? [item.current_revision_id] : [];
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
  <main class="min-h-screen bg-slate-950 p-5 text-slate-100" dir="rtl">
    <header class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-900 p-4">
      <div>
        <p class="text-xs font-semibold text-cyan-300">PROGRESS &amp; EVIDENCE / A03</p>
        <h1 class="mt-1 text-2xl font-bold">التقدم والأدلة المحكومة</h1>
      </div>
      <div class="flex flex-wrap gap-2">
        <template v-if="surface === 'evidence'">
          <button class="rounded-lg border border-slate-700 px-3 py-2" @click="panel = 'intake'">إدخال Candidate</button>
          <button
            class="rounded-lg border border-cyan-500 px-3 py-2 disabled:opacity-40"
            :disabled="!candidateAction"
            @click="runCandidateAction"
          >
            {{ candidateAction?.label ?? 'لا يوجد انتقال متاح' }}
          </button>
          <button class="rounded-lg border border-slate-700 px-3 py-2" :disabled="!selectedEvidence" @click="openRevision">
            Revision جديدة
          </button>
          <button
            class="rounded-lg border border-slate-700 px-3 py-2 disabled:opacity-40"
            :disabled="!selectedEvidence || selectedEvidence.lifecycle_state !== 'ACTIVE'"
            @click="requestReview"
          >
            طلب مراجعة
          </button>
        </template>
        <template v-else-if="surface === 'reviews'">
          <button
            class="rounded-lg border border-slate-700 px-3 py-2 disabled:opacity-40"
            :disabled="selectedRequest?.status !== 'REQUESTED'"
            @click="admitRequest"
          >
            بدء Review
          </button>
          <button
            class="rounded-lg border border-slate-700 px-3 py-2 disabled:opacity-40"
            :disabled="!selectedReview || !['IN_REVIEW', 'READY_FOR_DECISION'].includes(selectedReview.status)"
            @click="openFinding"
          >
            إضافة Finding
          </button>
          <button
            class="rounded-lg border border-slate-700 px-3 py-2 disabled:opacity-40"
            :disabled="selectedReview?.status !== 'READY_FOR_DECISION' || !selectedReview.findings.length"
            @click="panel = 'decision'"
          >
            تسجيل Decision
          </button>
        </template>
        <button v-else-if="surface === 'mastery'" class="rounded-lg border border-cyan-500 px-3 py-2" @click="openMastery">
          إلحاق Mastery State
        </button>
        <template v-else>
          <button class="rounded-lg border border-slate-700 px-3 py-2" @click="panel = 'portfolio'">إنشاء Portfolio View</button>
          <button
            class="rounded-lg border border-slate-700 px-3 py-2 disabled:opacity-40"
            :disabled="!selectedPortfolio || !evidence.length"
            @click="openPortfolioAdd"
          >
            إضافة Evidence Reference
          </button>
          <button class="rounded-lg border border-slate-700 px-3 py-2 disabled:opacity-40" :disabled="!selectedPortfolioItem" @click="removePortfolioItem">
            إزالة المرجع
          </button>
        </template>
      </div>
    </header>

    <p v-if="page.props.flash?.status" class="mb-4 rounded-lg border border-emerald-700 bg-emerald-950/40 p-3 text-sm">
      {{ page.props.flash.status }}
    </p>
    <p v-if="page.props.errors?.workflow" class="mb-4 rounded-lg border border-rose-700 bg-rose-950/40 p-3 text-sm">
      {{ page.props.errors.workflow }}
    </p>

    <div class="grid gap-4 lg:grid-cols-[180px_minmax(0,1fr)_300px]">
      <nav class="rounded-xl border border-slate-800 bg-slate-900 p-2" aria-label="بنية التقدم والأدلة">
        <a
          v-for="item in nav"
          :key="item.key"
          :href="item.href"
          class="flex justify-between rounded-lg px-3 py-3 text-sm text-slate-400"
          :class="{ 'bg-slate-800 text-white': surface === item.key }"
        >
          <span>{{ item.ar }}</span>
          <bdi class="font-mono text-xs">{{ item.en }}</bdi>
        </a>
      </nav>

      <section class="min-w-0 rounded-xl border border-slate-800 bg-slate-900 p-5">
        <template v-if="surface === 'evidence'">
          <p class="text-sm leading-7 text-slate-400">
            Candidate → Admission → sealed Evidence Revision. Admission لا يعني Review أو Mastery.
          </p>
          <h2 class="mt-5 font-bold">Candidate Evidence</h2>
          <button
            v-for="item in candidates"
            :key="item.id"
            class="mt-2 grid w-full grid-cols-[minmax(0,1fr)_auto] gap-3 rounded-lg border border-slate-700 p-3 text-right"
            :class="{ 'border-cyan-500': item.id === candidateId }"
            @click="candidateId = item.id"
          >
            <span>
              <strong class="block">{{ item.proposed_title }}</strong>
              <small class="mt-1 block text-slate-400">{{ item.evidence_claim }}</small>
            </span>
            <span class="text-left"><bdi class="block font-mono text-xs">{{ item.capability_id }}</bdi>{{ item.state }}</span>
          </button>
          <p v-if="!candidates.length" class="mt-2 rounded-lg border border-dashed border-slate-700 p-4 text-center text-slate-500">
            لا توجد Candidate Evidence.
          </p>

          <h2 class="mt-6 border-t border-slate-800 pt-5 font-bold">Canonical Evidence</h2>
          <button
            v-for="item in evidence"
            :key="item.id"
            class="mt-2 grid w-full grid-cols-[minmax(0,1fr)_auto] gap-3 rounded-lg border border-slate-700 p-3 text-right"
            :class="{ 'border-cyan-500': item.id === evidenceId }"
            @click="evidenceId = item.id"
          >
            <span>
              <strong class="block">{{ item.title }}</strong>
              <small class="mt-1 block text-slate-400">Revision {{ item.current_revision_number }} · {{ item.revisions.length }} sealed revision(s)</small>
            </span>
            <span class="grid gap-1 text-left font-mono text-xs">
              <span>{{ item.lifecycle_state }}</span><span>{{ item.review_status }}</span><span>{{ item.effective_review_decision }}</span>
            </span>
          </button>
        </template>

        <template v-else-if="surface === 'reviews'">
          <p class="text-sm leading-7 text-slate-400">كل Review Request يثبت Evidence Revision ونطاقًا ومعايير محددة.</p>
          <h2 class="mt-5 font-bold">Review Requests</h2>
          <button
            v-for="item in review_requests"
            :key="item.id"
            class="mt-2 w-full rounded-lg border border-slate-700 p-3 text-right"
            :class="{ 'border-cyan-500': item.id === requestId }"
            @click="requestId = item.id"
          >
            <strong>{{ evidenceTitle(item.evidence_id) }}</strong>
            <span class="mt-1 block font-mono text-xs text-slate-400">{{ item.evidence_revision_id }} · {{ item.review_scope_key }} · {{ item.status }}</span>
          </button>
          <h2 class="mt-6 border-t border-slate-800 pt-5 font-bold">Evidence Reviews</h2>
          <button
            v-for="item in reviews"
            :key="item.id"
            class="mt-2 w-full rounded-lg border border-slate-700 p-3 text-right"
            :class="{ 'border-cyan-500': item.id === reviewId }"
            @click="reviewId = item.id"
          >
            <strong>{{ evidenceTitle(item.evidence_id) }}</strong>
            <span class="mt-1 block text-sm text-slate-400">{{ item.findings.length }} Finding(s) · {{ item.status }}</span>
            <span v-if="item.decision" class="mt-1 block font-mono text-xs text-cyan-300">{{ item.decision.decision }}</span>
          </button>
        </template>

        <template v-else-if="surface === 'mastery'">
          <p class="text-sm leading-7 text-slate-400">Judgment ≠ Freshness، وكل تقييم يولّد Mastery State جديدة بدل overwrite.</p>
          <button
            v-for="item in mastery"
            :key="item.id"
            class="mt-3 w-full rounded-lg border border-slate-700 p-3 text-right"
            :class="{ 'border-cyan-500': item.id === masteryId }"
            @click="masteryId = item.id"
          >
            <strong class="font-mono">{{ item.target_id }}</strong>
            <span class="mt-2 flex flex-wrap gap-2 font-mono text-xs text-cyan-200">
              <span>{{ item.judgment }}</span><span>{{ item.freshness_status }}</span>
            </span>
            <small class="mt-2 block text-slate-400">{{ item.review_decision_ids.length }} Decision ref(s) · {{ item.supporting_evidence_revision_ids.length }} supporting Revision ref(s)</small>
          </button>
        </template>

        <template v-else>
          <p class="text-sm leading-7 text-slate-400">Portfolio هو projection مرجعي ولا يكرر Evidence أو Mastery truth.</p>
          <article
            v-for="item in portfolios"
            :key="item.id"
            class="mt-3 rounded-lg border border-slate-700 p-3"
            :class="{ 'border-cyan-500': item.id === portfolioId }"
            @click="portfolioId = item.id"
          >
            <h2 class="font-bold">{{ item.name }}</h2>
            <button
              v-for="entry in item.items"
              :key="entry.id"
              class="mt-2 w-full rounded-lg border border-slate-800 p-3 text-right"
              :class="{ 'border-cyan-700': entry.id === portfolioItemId }"
              @click.stop="portfolioId = item.id; portfolioItemId = entry.id"
            >
              <strong>{{ entry.title }}</strong>
              <span class="mt-1 block font-mono text-xs text-slate-400">{{ entry.current_revision_id }}</span>
            </button>
          </article>
        </template>
      </section>

      <aside class="rounded-xl border border-slate-800 bg-slate-900 p-4 text-sm" aria-label="السياق الفريد">
        <template v-if="surface === 'evidence' && selectedEvidence">
          <p class="font-semibold text-cyan-300">Sealed Revision History</p>
          <h2 class="mt-1 text-lg font-bold">السجل غير القابل لإعادة الكتابة</h2>
          <p class="mt-3 text-slate-400">{{ selectedEvidence.evidence_claim }}</p>
          <ol class="mt-4 space-y-2">
            <li v-for="item in selectedEvidence.revisions" :key="item.id" class="rounded-lg border border-slate-800 p-2">
              <bdi class="font-mono">R{{ item.revision }}</bdi> — {{ item.revision_reason }}
            </li>
          </ol>
        </template>
        <template v-else-if="surface === 'evidence' && candidate">
          <p class="font-semibold text-cyan-300">Candidate Semantic Identity</p>
          <p class="mt-3"><bdi class="font-mono">{{ candidate.source_id }}@{{ candidate.source_revision }}</bdi></p>
          <p class="mt-2 text-slate-400">{{ candidate.selected_material_refs.length }} material ref(s) · {{ candidate.criterion_scope.length }} criterion ref(s)</p>
        </template>
        <template v-else-if="surface === 'reviews' && selectedReview">
          <p class="font-semibold text-cyan-300">Reviewer Authority</p>
          <p class="mt-3 font-mono text-xs">Reviewer: {{ selectedReview.reviewer_id }}</p>
          <p class="mt-2 font-mono text-xs">Revision: {{ selectedReview.evidence_revision_id }}</p>
          <p class="mt-2 font-mono text-xs">Scope: {{ selectedReview.review_scope_key }}</p>
        </template>
        <template v-else-if="surface === 'mastery' && selectedMastery">
          <p class="font-semibold text-cyan-300">Mastery Provenance</p>
          <p class="mt-3 font-mono text-xs">Policy: {{ selectedMastery.policy_revision_id }}</p>
          <p class="mt-2">History: {{ selectedMasteryHistory.length }} state(s)</p>
          <p class="mt-2">Decisions: {{ selectedMastery.review_decision_ids.length }}</p>
          <p class="mt-2">Supporting: {{ selectedMastery.supporting_evidence_revision_ids.length }}</p>
          <p class="mt-2">Contradicting: {{ selectedMastery.contradicting_evidence_revision_ids.length }}</p>
        </template>
        <template v-else-if="surface === 'portfolio' && selectedPortfolio">
          <p class="font-semibold text-cyan-300">View Configuration</p>
          <p class="mt-3">Scope: {{ selectedPortfolio.view_scope || 'غير مقيّد' }}</p>
          <p class="mt-2 font-mono text-xs">Grouping: {{ selectedPortfolio.grouping }}</p>
        </template>
      </aside>
    </div>

    <section v-if="panel" class="mt-4 rounded-xl border border-slate-700 bg-slate-900 p-5">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="font-bold">إجراء محكوم</h2>
        <button class="rounded-lg border border-slate-700 px-3 py-2" @click="panel = null">إغلاق</button>
      </div>

      <form v-if="panel === 'intake'" class="grid gap-3 md:grid-cols-2" @submit.prevent="intake.post('/progress/intake', { onSuccess: () => (panel = null) })">
        <label>Source Type<input v-model="intake.source_type" class="field" dir="ltr" required /></label>
        <label>Source ID<input v-model="intake.source_id" class="field" dir="ltr" required /></label>
        <label>Source Revision<input v-model="intake.source_revision" class="field" dir="ltr" required /></label>
        <label>Source SHA-256<input v-model="intake.source_digest" class="field" dir="ltr" minlength="64" maxlength="64" required /></label>
        <label>Selected Material<input v-model="intake.selected_material_refs[0]" class="field" dir="ltr" required /></label>
        <label>Capability ID<input v-model="intake.capability_id" class="field" dir="ltr" required /></label>
        <label class="md:col-span-2">Evidence Claim<textarea v-model="intake.evidence_claim" class="field" required /></label>
        <label>Criterion Ref<input v-model="intake.criterion_scope[0]" class="field" dir="ltr" required /></label>
        <label>Governed Purpose<input v-model="intake.governed_purpose" class="field" dir="ltr" required /></label>
        <label>العنوان<input v-model="intake.title" class="field" required /></label>
        <label>الملخص<textarea v-model="intake.summary" class="field" required /></label>
        <button class="rounded-lg bg-cyan-300 px-4 py-2 font-bold text-slate-950">إنشاء Candidate في RECEIVED</button>
      </form>

      <form
        v-else-if="panel === 'revision' && selectedEvidence"
        class="grid gap-3 md:grid-cols-2"
        @submit.prevent="revision.post(`/progress/evidence/${selectedEvidence.id}/revisions`, { onSuccess: () => (panel = null) })"
      >
        <label>العنوان<input v-model="revision.title" class="field" required /></label>
        <label>Revision Reason<input v-model="revision.revision_reason" class="field" required /></label>
        <label class="md:col-span-2">الملخص<textarea v-model="revision.summary" class="field" required /></label>
        <button class="rounded-lg bg-cyan-300 px-4 py-2 font-bold text-slate-950">Seal Superseding Revision</button>
      </form>

      <form v-else-if="panel === 'finding' && selectedReview" class="grid gap-3 md:grid-cols-2" @submit.prevent="submitFinding">
        <label>Criterion Key<input v-model="finding.criterion_key" class="field" dir="ltr" required /></label>
        <label>Finding<select v-model="finding.finding" class="field"><option>SATISFIED</option><option>PARTIALLY_SATISFIED</option><option>NOT_SATISFIED</option><option>NOT_ASSESSABLE</option></select></label>
        <label class="md:col-span-2">البيان<textarea v-model="finding.statement" class="field" required /></label>
        <button class="rounded-lg bg-cyan-300 px-4 py-2 font-bold text-slate-950">تسجيل Finding</button>
      </form>

      <form v-else-if="panel === 'decision' && selectedReview" class="grid gap-3 md:grid-cols-2" @submit.prevent="submitDecision">
        <label>Review Decision<select v-model="decision.decision" class="field"><option>ACCEPT</option><option>ACCEPT_WITH_LIMITATIONS</option><option>MORE_EVIDENCE_REQUIRED</option><option>REJECT</option></select></label>
        <label>المسوّغ<textarea v-model="decision.rationale" class="field" required /></label>
        <button class="rounded-lg bg-cyan-300 px-4 py-2 font-bold text-slate-950">Seal Decision</button>
      </form>

      <form v-else-if="panel === 'mastery'" class="grid gap-3 md:grid-cols-2" @submit.prevent="masteryForm.post('/progress/mastery/evaluate', { onSuccess: () => (panel = null) })">
        <label>Capability ID<input v-model="masteryForm.capability_id" class="field" dir="ltr" required /></label>
        <label>Policy Revision<input v-model="masteryForm.policy_revision_id" class="field" dir="ltr" required /></label>
        <label>Judgment<select v-model="masteryForm.judgment" class="field"><option>NOT_EVALUATED</option><option>INSUFFICIENT_EVIDENCE</option><option>INCONCLUSIVE</option><option>NOT_MASTERED</option><option>MASTERED</option></select></label>
        <label>Freshness<select v-model="masteryForm.freshness_status" class="field"><option>CURRENT</option><option>REVALIDATION_REQUIRED</option></select></label>
        <label class="md:col-span-2">المسوّغ<textarea v-model="masteryForm.rationale" class="field" required /></label>
        <p class="md:col-span-2 text-sm text-slate-400"><bdi>MASTERED + REVALIDATION_REQUIRED</bdi> حالة قانونية في A03.</p>
        <button class="rounded-lg bg-cyan-300 px-4 py-2 font-bold text-slate-950">Append Mastery State</button>
      </form>

      <form v-else-if="panel === 'portfolio'" class="grid gap-3 md:grid-cols-2" @submit.prevent="portfolioForm.post('/progress/portfolio', { onSuccess: () => (panel = null) })">
        <label>اسم العرض<input v-model="portfolioForm.name" class="field" required /></label>
        <label>View Scope<input v-model="portfolioForm.view_scope" class="field" /></label>
        <label>Grouping<input v-model="portfolioForm.grouping" class="field" dir="ltr" required /></label>
        <button class="rounded-lg bg-cyan-300 px-4 py-2 font-bold text-slate-950">إنشاء Reference Projection</button>
      </form>

      <form
        v-else-if="panel === 'portfolio-add' && selectedPortfolio"
        class="grid gap-3 md:grid-cols-2"
        @submit.prevent="portfolioAdd.post(`/progress/portfolio/${selectedPortfolio.id}/evidence`, { onSuccess: () => (panel = null) })"
      >
        <label>Evidence<select v-model="portfolioAdd.evidence_id" class="field"><option v-for="item in evidence" :key="item.id" :value="item.id">{{ item.title }}</option></select></label>
        <label>ملاحظة<textarea v-model="portfolioAdd.annotation" class="field" /></label>
        <button class="rounded-lg bg-cyan-300 px-4 py-2 font-bold text-slate-950">إضافة Canonical Reference</button>
      </form>
    </section>
  </main>
</template>

<style scoped>
.field {
  margin-top: 0.25rem;
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid rgb(51 65 85);
  background: rgb(15 23 42);
  padding: 0.65rem;
  color: rgb(241 245 249);
}
</style>

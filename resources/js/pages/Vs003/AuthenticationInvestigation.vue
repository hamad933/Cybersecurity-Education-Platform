<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppShell from '../../components/AppShell.vue';
import BidiText from '../../components/BidiText.vue';

interface TelemetryEvent {
  id: string;
  event_id: number;
  occurred_at: string;
  computer: string | null;
  account_sid: string | null;
  logon_type: number | null;
  source_address: string | null;
  duplicate_of: string | null;
  late: boolean;
  contradicts: string | null;
}

interface InvestigationTrace {
  run_id: string;
  outcome: string;
  alert_state: string;
  severity: string;
  scope: string;
  confidence: string;
  telemetry_health: string;
  alternative_hypotheses: string[];
  missing_data: string[];
  timeline_digest: string;
  events: TelemetryEvent[];
  quality: Record<string, string | number>;
  verification?: {
    original_run_id: string;
    control_revision_id: string;
    result: string;
    live_action_performed: boolean;
  };
}

interface TriageRecord {
  id: string;
  scenario_run_id: string;
  outcome: string;
  severity: string;
  scope: string;
  confidence: string;
  rationale: string;
}

interface InvestigationRun {
  id: string;
  case_id: string;
  outcome: string;
  status: string;
  trace_digest: string;
  completed_at: string;
  trace: InvestigationTrace | null;
  triage: TriageRecord | null;
  alert: { state: string; severity: string } | null;
}

interface EvidenceRecord {
  id: string;
  run_id: string;
  case_id: string;
  result: string;
  origin: string;
  trace_digest: string;
  locked: boolean;
}

interface CustodyRecord {
  id: string;
  scenario_run_id: string;
  origin: string;
  source_digest: string;
  copy_kind: string;
  storage_reference: string;
}

interface ContainmentProposal {
  id: string;
  scenario_run_id: string;
  state: string;
  expected_effect: string;
  risk: string;
  rollback_condition: string;
}

interface ControlRevision {
  id: string;
  control_id: string;
  revision: number;
  state: string;
  remediates_run_id: string;
  digest: string;
}

interface VerificationReplay {
  id: string;
  original_run_id: string;
  verification_run_id: string;
  control_revision_id: string;
  passed: boolean;
  digest: string;
}

interface PracticeAttempt {
  id: string;
  outcome: string;
  failure_class: string | null;
}

interface MasteryState {
  status: string;
  evaluation_digest: string;
}

interface ReviewTrigger {
  id: string;
  failure_class: string;
  status: string;
  schedule_reason: string;
}

interface Workspace {
  simulation: { runs: InvestigationRun[] };
  evidence: {
    evidence: EvidenceRecord[];
    custody: CustodyRecord[];
    proposals: ContainmentProposal[];
    controls: ControlRevision[];
    verification_replays: VerificationReplay[];
  };
  learning: {
    practice: { id: string; revision: number; definition: Record<string, unknown> };
    attempts: PracticeAttempt[];
    mastery: MasteryState | null;
    review_triggers: ReviewTrigger[];
  };
}

const props = defineProps<{
  cases: string[];
  outcomes: string[];
  telemetryHealthValues: string[];
  alternativeHypotheses: string[];
  evidenceOrigin: string;
  baseline: string;
  requestKeys: { run: string; verification: string };
  workspace: Workspace;
}>();

interface SharedPageProps {
  flash?: { status?: string };
  errors?: Record<string, string>;
  [key: string]: unknown;
}

const page = usePage<SharedPageProps>();
const serverErrors = computed(() => Object.entries(page.props.errors ?? {}));
const runs = computed(() => props.workspace.simulation.runs);
const incidentRuns = computed(() =>
  runs.value.filter((run) => run.outcome === 'INCIDENT_CONFIRMED'),
);
const latestRun = computed(() => runs.value[0] ?? null);
const latestIncident = computed(() => incidentRuns.value[0] ?? null);

const runForm = useForm({
  case_id: props.cases[0] ?? 'VS3-SUSPICIOUS',
  seed: 9003,
  idempotency_key: props.requestKeys.run,
});
const triageForm = useForm({
  run_id: latestRun.value?.id ?? '',
  outcome: latestRun.value?.outcome ?? 'SUSPICIOUS',
  rationale: 'النتيجة مقيدة بالدليل الاصطناعي، مع تسجيل الفرضيات البديلة والبيانات الناقصة.',
});
const custodyForm = useForm({ run_id: latestIncident.value?.id ?? '' });
const containmentForm = useForm({
  run_id: latestIncident.value?.id ?? '',
  expected_effect: 'خفض التعرض المستمر للمسار الاصطناعي المشبوه دون تنفيذ حي.',
  risk: 'قد يؤدي المقترح إلى تعطيل المسار الاصطناعي المستخدم في السيناريو فقط.',
  rollback_condition: 'التراجع عند عدم تحقق أثر الضبط داخل إعادة التشغيل الاصطناعية.',
});
const practiceForm = useForm({
  outcome: 'SUSPICIOUS',
  telemetry_health: 'HEALTHY',
  alternative_hypothesis: 'legitimate_user_error',
});
const actionForm = useForm({});

function submitRun() {
  runForm.post('/vs003/lab/run', {
    preserveScroll: true,
    onSuccess: () => {
      runForm.idempotency_key = `vs003:ui:run:${crypto.randomUUID()}`;
    },
  });
}

function submitTriage() {
  triageForm.post('/vs003/triage', { preserveScroll: true });
}

function preserveEvidence() {
  custodyForm.post('/vs003/evidence/preserve', { preserveScroll: true });
}

function proposeContainment() {
  containmentForm.post('/vs003/containment/propose', { preserveScroll: true });
}

function approveProposal(proposalId: string) {
  actionForm.post(`/vs003/containment/${proposalId}/approve`, { preserveScroll: true });
}

function verifyProposal(proposal: ContainmentProposal) {
  router.post(
    `/vs003/containment/${proposal.id}/verify`,
    {
      original_run_id: proposal.scenario_run_id,
      idempotency_key: `vs003:verify:${proposal.id}`,
    },
    { preserveScroll: true },
  );
}

function submitPractice() {
  practiceForm.post('/vs003/practice', { preserveScroll: true });
}

function evaluateMastery() {
  actionForm.post('/vs003/mastery/evaluate', { preserveScroll: true });
}
</script>

<template>
  <Head title="VS-003 | تحقيق شذوذ المصادقة" />
  <AppShell>
    <article dir="rtl" class="space-y-8" aria-labelledby="vs003-title">
      <header class="rounded-2xl border border-slate-700 bg-slate-950/70 p-5 sm:p-7">
        <p class="text-sm font-bold text-amber-300">
          SIMULATED · لا توجد بيانات تشغيلية حية ولا احتواء تلقائي
        </p>
        <h1 id="vs003-title" class="mt-3 text-3xl font-bold">تحقيق شذوذ المصادقة</h1>
        <p class="mt-3 max-w-4xl leading-7 text-slate-300">
          افحص جودة القياس أولاً، ثم ثبّت قرار الفرز والفرضية البديلة وسلسلة الحيازة قبل أي مقترح
          احتواء غير تنفيذي.
        </p>
        <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
          <div class="rounded-xl border border-slate-800 p-3">
            <dt class="text-slate-400">خط أساس السلطة</dt>
            <dd class="mt-1"><BidiText :value="baseline" /></dd>
          </div>
          <div class="rounded-xl border border-slate-800 p-3">
            <dt class="text-slate-400">مصدر الدليل</dt>
            <dd class="mt-1 font-bold text-emerald-300">{{ evidenceOrigin }}</dd>
          </div>
        </dl>
        <p
          v-if="page.props.flash?.status"
          class="mt-5 rounded-xl border border-emerald-700 bg-emerald-950/40 p-3 text-emerald-100"
          role="status"
        >
          {{ page.props.flash.status }}
        </p>
        <div
          v-if="serverErrors.length > 0"
          class="mt-5 rounded-xl border border-rose-700 bg-rose-950/40 p-3 text-rose-100"
          role="alert"
          aria-live="assertive"
        >
          <p class="font-bold">تعذر إكمال الإجراء:</p>
          <ul class="mt-2 space-y-1">
            <li v-for="entry in serverErrors" :key="entry[0]">{{ entry[1] }}</li>
          </ul>
        </div>
      </header>

      <section class="grid gap-6 xl:grid-cols-2" aria-label="التشغيل والفرز">
        <div class="rounded-2xl border border-slate-700 bg-slate-950/70 p-5">
          <h2 class="text-xl font-bold">1. تشغيل حالة موجهة</h2>
          <form class="mt-5 space-y-4" @submit.prevent="submitRun">
            <label class="block">
              <span class="text-sm font-medium">الحالة الاصطناعية</span>
              <select
                v-model="runForm.case_id"
                class="form-input focus-ring mt-2"
                aria-label="الحالة الاصطناعية"
              >
                <option v-for="caseId in cases" :key="caseId" :value="caseId">{{ caseId }}</option>
              </select>
            </label>
            <label class="block">
              <span class="text-sm font-medium">البذرة الحتمية</span>
              <input
                v-model.number="runForm.seed"
                class="form-input focus-ring mt-2"
                dir="ltr"
                type="number"
                min="1"
                max="4294967295"
                inputmode="numeric"
              />
            </label>
            <p v-if="runForm.errors.case_id" class="text-rose-300" role="alert">
              {{ runForm.errors.case_id }}
            </p>
            <p v-if="runForm.errors.seed" class="text-rose-300" role="alert">
              {{ runForm.errors.seed }}
            </p>
            <p v-if="runForm.errors.idempotency_key" class="text-rose-300" role="alert">
              {{ runForm.errors.idempotency_key }}
            </p>
            <p v-if="page.props.errors?.run" class="text-rose-300" role="alert">
              {{ page.props.errors?.run }}
            </p>
            <button
              class="focus-ring w-full rounded-xl bg-cyan-400 px-4 py-3 font-bold text-slate-950 disabled:opacity-50"
              type="submit"
              :disabled="runForm.processing"
            >
              تشغيل آمن
            </button>
          </form>
        </div>

        <div class="rounded-2xl border border-slate-700 bg-slate-950/70 p-5">
          <h2 class="text-xl font-bold">2. تثبيت قرار الفرز</h2>
          <form class="mt-5 space-y-4" @submit.prevent="submitTriage">
            <label class="block">
              <span class="text-sm font-medium">تشغيل مملوك للممثل الحالي</span>
              <select v-model="triageForm.run_id" class="form-input focus-ring mt-2" dir="ltr">
                <option value="">اختر تشغيلًا</option>
                <option v-for="run in runs" :key="run.id" :value="run.id">
                  {{ run.case_id }} · {{ run.outcome }} · {{ run.id }}
                </option>
              </select>
            </label>
            <label class="block">
              <span class="text-sm font-medium">النتيجة المختارة</span>
              <select v-model="triageForm.outcome" class="form-input focus-ring mt-2" dir="ltr">
                <option v-for="outcome in outcomes" :key="outcome" :value="outcome">
                  {{ outcome }}
                </option>
              </select>
            </label>
            <label class="block">
              <span class="text-sm font-medium">المبرر والفرضيات البديلة</span>
              <textarea
                v-model="triageForm.rationale"
                class="form-input focus-ring mt-2 min-h-28"
                minlength="12"
                maxlength="1000"
              />
            </label>
            <p v-if="triageForm.errors.run_id" class="text-rose-300" role="alert">
              {{ triageForm.errors.run_id }}
            </p>
            <p v-if="triageForm.errors.outcome" class="text-rose-300" role="alert">
              {{ triageForm.errors.outcome }}
            </p>
            <p v-if="triageForm.errors.rationale" class="text-rose-300" role="alert">
              {{ triageForm.errors.rationale }}
            </p>
            <p v-if="page.props.errors?.triage" class="text-rose-300" role="alert">
              {{ page.props.errors?.triage }}
            </p>
            <button
              class="focus-ring w-full rounded-xl border border-cyan-500 px-4 py-3 font-bold text-cyan-200 disabled:opacity-50"
              type="submit"
              :disabled="triageForm.processing || !triageForm.run_id"
            >
              تثبيت الفرز
            </button>
          </form>
        </div>
      </section>

      <section
        class="rounded-2xl border border-slate-700 bg-slate-950/70 p-5"
        aria-labelledby="timeline-title"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 id="timeline-title" class="text-xl font-bold">الخط الزمني وجودة القياس</h2>
          <span class="rounded-full border border-slate-700 px-3 py-1 text-sm">UTC</span>
        </div>
        <div
          v-if="runs.length === 0"
          class="mt-4 rounded-xl border border-dashed border-slate-700 p-5 text-slate-400"
        >
          لم تُشغّل أي حالة بعد.
        </div>
        <div v-else class="mt-5 space-y-5">
          <article v-for="run in runs" :key="run.id" class="rounded-xl border border-slate-800 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="font-bold">{{ run.case_id }} — {{ run.outcome }}</p>
                <p class="mt-1 text-sm text-slate-400">Run: <BidiText :value="run.id" /></p>
              </div>
              <span class="rounded-full border border-slate-700 px-3 py-1 text-sm">
                {{ run.alert?.state ?? 'NO_ALERT' }} / {{ run.alert?.severity ?? 'N/A' }}
              </span>
            </div>
            <div v-if="run.trace" class="mt-4 grid gap-3 md:grid-cols-4">
              <div class="rounded-lg bg-slate-900 p-3">
                <p class="text-xs text-slate-400">جودة القياس</p>
                <p class="mt-1 font-bold">{{ run.trace.telemetry_health }}</p>
              </div>
              <div class="rounded-lg bg-slate-900 p-3">
                <p class="text-xs text-slate-400">الشدة</p>
                <p class="mt-1 font-bold">{{ run.trace.severity }}</p>
              </div>
              <div class="rounded-lg bg-slate-900 p-3">
                <p class="text-xs text-slate-400">النطاق</p>
                <p class="mt-1 font-bold">{{ run.trace.scope }}</p>
              </div>
              <div class="rounded-lg bg-slate-900 p-3">
                <p class="text-xs text-slate-400">الثقة</p>
                <p class="mt-1 font-bold">{{ run.trace.confidence }}</p>
              </div>
            </div>
            <div v-if="run.trace" class="mt-4 grid gap-3 lg:grid-cols-3">
              <div class="rounded-lg border border-slate-800 p-3">
                <p class="text-xs font-bold text-slate-400">حالات جودة البيانات</p>
                <ul class="mt-2 space-y-1 text-sm" dir="ltr">
                  <li v-for="(value, key) in run.trace.quality" :key="key">
                    {{ key }}={{ value }}
                  </li>
                </ul>
              </div>
              <div class="rounded-lg border border-slate-800 p-3">
                <p class="text-xs font-bold text-slate-400">الفرضيات البديلة</p>
                <ul class="mt-2 space-y-1 text-sm" dir="ltr">
                  <li v-for="hypothesis in run.trace.alternative_hypotheses" :key="hypothesis">
                    {{ hypothesis }}
                  </li>
                </ul>
              </div>
              <div class="rounded-lg border border-slate-800 p-3">
                <p class="text-xs font-bold text-slate-400">البيانات الناقصة</p>
                <p v-if="run.trace.missing_data.length === 0" class="mt-2 text-sm">NONE</p>
                <ul v-else class="mt-2 space-y-1 text-sm" dir="ltr">
                  <li v-for="item in run.trace.missing_data" :key="item">{{ item }}</li>
                </ul>
              </div>
            </div>
            <div
              v-if="run.trace"
              class="mt-4 max-h-80 overflow-auto rounded-lg border border-slate-800"
            >
              <table class="w-full min-w-[760px] text-sm">
                <thead class="sticky top-0 bg-slate-900 text-slate-300">
                  <tr>
                    <th class="p-3 text-right">Event ID</th>
                    <th class="p-3 text-right">Time</th>
                    <th class="p-3 text-right">Computer</th>
                    <th class="p-3 text-right">Account SID</th>
                    <th class="p-3 text-right">Source</th>
                    <th class="p-3 text-right">Quality</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="event in run.trace.events"
                    :key="event.id"
                    class="border-t border-slate-800"
                  >
                    <td class="p-3" dir="ltr">{{ event.event_id }} / {{ event.id }}</td>
                    <td class="p-3" dir="ltr">{{ event.occurred_at }}</td>
                    <td class="p-3" dir="ltr">{{ event.computer ?? 'MISSING' }}</td>
                    <td class="p-3" dir="ltr">{{ event.account_sid ?? 'MISSING' }}</td>
                    <td class="p-3" dir="ltr">{{ event.source_address ?? 'MISSING' }}</td>
                    <td class="p-3">
                      <span v-if="event.duplicate_of">DUPLICATE</span>
                      <span v-else-if="event.late">LATE</span>
                      <span v-else-if="event.contradicts">CONTRADICTORY</span>
                      <span v-else>DECLARED</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-if="run.trace" class="mt-3 text-xs break-all text-slate-400" dir="ltr">
              trace={{ run.trace.timeline_digest }}
            </p>
          </article>
        </div>
      </section>

      <section class="grid gap-6 xl:grid-cols-2" aria-label="الدليل والاحتواء">
        <div class="rounded-2xl border border-slate-700 bg-slate-950/70 p-5">
          <h2 class="text-xl font-bold">3. حفظ الدليل وسلسلة الحيازة</h2>
          <form class="mt-5 space-y-4" @submit.prevent="preserveEvidence">
            <label class="block">
              <span class="text-sm font-medium">تشغيل الحادث الاصطناعي</span>
              <select v-model="custodyForm.run_id" class="form-input focus-ring mt-2" dir="ltr">
                <option value="">اختر تشغيلًا</option>
                <option v-for="run in incidentRuns" :key="run.id" :value="run.id">
                  {{ run.id }}
                </option>
              </select>
            </label>
            <p v-if="page.props.errors?.evidence" class="text-rose-300" role="alert">
              {{ page.props.errors?.evidence }}
            </p>
            <button
              class="focus-ring w-full rounded-xl border border-emerald-600 px-4 py-3 font-bold text-emerald-200 disabled:opacity-50"
              type="submit"
              :disabled="custodyForm.processing || !custodyForm.run_id"
            >
              حفظ النسخة الأصلية SIMULATED
            </button>
          </form>
          <div class="mt-5 rounded-xl border border-slate-800 p-3">
            <p class="text-sm font-bold">سجلات الدليل المقفلة للممثل الحالي</p>
            <ul class="mt-3 space-y-2 text-sm">
              <li
                v-for="record in workspace.evidence.evidence"
                :key="record.id"
                class="rounded-lg bg-slate-900 p-3"
              >
                <span
                  >{{ record.case_id }} · {{ record.result }} ·
                  {{ record.locked ? 'LOCKED' : 'UNLOCKED' }}</span
                >
                <span class="mt-1 block text-xs break-all text-slate-400" dir="ltr">
                  {{ record.trace_digest }}
                </span>
              </li>
            </ul>
          </div>
          <ul class="mt-5 space-y-2 text-sm">
            <li
              v-for="record in workspace.evidence.custody"
              :key="record.id"
              class="rounded-lg bg-slate-900 p-3"
            >
              <span class="font-bold">{{ record.copy_kind }}</span>
              <span class="mt-1 block break-all text-slate-400" dir="ltr">{{
                record.storage_reference
              }}</span>
            </li>
          </ul>
        </div>

        <div class="rounded-2xl border border-slate-700 bg-slate-950/70 p-5">
          <h2 class="text-xl font-bold">4. مقترح احتواء وموافقة صريحة</h2>
          <form class="mt-5 space-y-4" @submit.prevent="proposeContainment">
            <label class="block">
              <span class="text-sm font-medium">تشغيل حادث مُفرز</span>
              <select v-model="containmentForm.run_id" class="form-input focus-ring mt-2" dir="ltr">
                <option value="">اختر تشغيلًا</option>
                <option v-for="run in incidentRuns" :key="run.id" :value="run.id">
                  {{ run.id }}
                </option>
              </select>
            </label>
            <label class="block">
              <span class="text-sm font-medium">الأثر المتوقع</span>
              <textarea
                v-model="containmentForm.expected_effect"
                class="form-input focus-ring mt-2 min-h-20"
                maxlength="500"
              />
            </label>
            <label class="block">
              <span class="text-sm font-medium">المخاطر</span>
              <textarea
                v-model="containmentForm.risk"
                class="form-input focus-ring mt-2 min-h-20"
                maxlength="500"
              />
            </label>
            <label class="block">
              <span class="text-sm font-medium">شرط التراجع</span>
              <textarea
                v-model="containmentForm.rollback_condition"
                class="form-input focus-ring mt-2 min-h-20"
                maxlength="500"
              />
            </label>
            <p v-if="page.props.errors?.containment" class="text-rose-300" role="alert">
              {{ page.props.errors?.containment }}
            </p>
            <button
              class="focus-ring w-full rounded-xl bg-amber-300 px-4 py-3 font-bold text-slate-950 disabled:opacity-50"
              type="submit"
              :disabled="containmentForm.processing || !containmentForm.run_id"
            >
              إنشاء مقترح غير تنفيذي
            </button>
          </form>
          <div class="mt-5 space-y-3">
            <article
              v-for="proposal in workspace.evidence.proposals"
              :key="proposal.id"
              class="rounded-lg border border-slate-800 p-3"
            >
              <p class="font-bold">{{ proposal.state }}</p>
              <p class="mt-1 text-sm text-slate-400">{{ proposal.expected_effect }}</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <button
                  v-if="proposal.state === 'PROPOSED'"
                  class="focus-ring rounded-lg border border-amber-500 px-3 py-2 text-sm"
                  type="button"
                  @click="approveProposal(proposal.id)"
                >
                  موافقة صريحة
                </button>
                <button
                  v-if="proposal.state === 'APPROVED'"
                  class="focus-ring rounded-lg border border-cyan-500 px-3 py-2 text-sm"
                  type="button"
                  @click="verifyProposal(proposal)"
                >
                  نشر مراجعة ضبط وإعادة تحقق
                </button>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section class="grid gap-6 xl:grid-cols-2" aria-label="الممارسة والإتقان">
        <div class="rounded-2xl border border-slate-700 bg-slate-950/70 p-5">
          <h2 class="text-xl font-bold">5. Micro Practice منظمة</h2>
          <form class="mt-5 space-y-4" @submit.prevent="submitPractice">
            <label class="block">
              <span class="text-sm font-medium">النتيجة</span>
              <select v-model="practiceForm.outcome" class="form-input focus-ring mt-2" dir="ltr">
                <option v-for="outcome in outcomes" :key="outcome" :value="outcome">
                  {{ outcome }}
                </option>
              </select>
            </label>
            <label class="block">
              <span class="text-sm font-medium">حالة جودة القياس</span>
              <select
                v-model="practiceForm.telemetry_health"
                class="form-input focus-ring mt-2"
                dir="ltr"
              >
                <option v-for="health in telemetryHealthValues" :key="health" :value="health">
                  {{ health }}
                </option>
              </select>
            </label>
            <label class="block">
              <span class="text-sm font-medium">فرضية بديلة</span>
              <select
                v-model="practiceForm.alternative_hypothesis"
                class="form-input focus-ring mt-2"
                dir="ltr"
              >
                <option
                  v-for="hypothesis in alternativeHypotheses"
                  :key="hypothesis"
                  :value="hypothesis"
                >
                  {{ hypothesis }}
                </option>
              </select>
            </label>
            <button
              class="focus-ring w-full rounded-xl border border-violet-500 px-4 py-3 font-bold text-violet-200"
              type="submit"
              :disabled="practiceForm.processing"
            >
              تقييم الإجابة على الخادم
            </button>
          </form>
          <ul class="mt-5 space-y-2 text-sm">
            <li
              v-for="attempt in workspace.learning.attempts"
              :key="attempt.id"
              class="rounded-lg bg-slate-900 p-3"
            >
              {{ attempt.outcome
              }}<span v-if="attempt.failure_class"> · {{ attempt.failure_class }}</span>
            </li>
          </ul>
        </div>

        <div class="rounded-2xl border border-slate-700 bg-slate-950/70 p-5">
          <h2 class="text-xl font-bold">6. الإتقان والمراجعة المبنية على الفشل</h2>
          <p class="mt-3 leading-7 text-slate-300">
            يقرأ التقييم السجلات المحفوظة فقط؛ لا يقبل نتيجة Replay من المتصفح.
          </p>
          <button
            class="focus-ring mt-5 w-full rounded-xl bg-violet-300 px-4 py-3 font-bold text-slate-950"
            type="button"
            @click="evaluateMastery"
          >
            تقييم الإتقان من الأدلة المحفوظة
          </button>
          <div class="mt-5 rounded-xl border border-slate-800 p-4">
            <p class="text-sm text-slate-400">الحالة الحالية</p>
            <p class="mt-1 text-2xl font-bold">
              {{ workspace.learning.mastery?.status ?? 'NOT_EVALUATED' }}
            </p>
          </div>
          <ul class="mt-5 space-y-2 text-sm">
            <li
              v-for="review in workspace.learning.review_triggers"
              :key="review.id"
              class="rounded-lg bg-slate-900 p-3"
            >
              <span class="font-bold">{{ review.failure_class }}</span>
              <span class="mt-1 block text-slate-400">{{ review.schedule_reason }}</span>
            </li>
          </ul>
        </div>
      </section>

      <section
        class="rounded-2xl border border-slate-700 bg-slate-950/70 p-5"
        aria-labelledby="verification-title"
      >
        <h2 id="verification-title" class="text-xl font-bold">الضوابط وإعادات التحقق المثبتة</h2>
        <div class="mt-5 grid gap-4 lg:grid-cols-2">
          <article
            v-for="control in workspace.evidence.controls"
            :key="control.id"
            class="rounded-xl border border-slate-800 p-4"
          >
            <p class="font-bold">{{ control.control_id }} · revision {{ control.revision }}</p>
            <p class="mt-2 text-xs break-all text-slate-400" dir="ltr">{{ control.digest }}</p>
            <p class="mt-2 text-sm">لا يوجد تنفيذ حي؛ المراجعة مرتبطة بتشغيل اصطناعي فقط.</p>
          </article>
          <article
            v-for="replay in workspace.evidence.verification_replays"
            :key="replay.id"
            class="rounded-xl border border-slate-800 p-4"
          >
            <p class="font-bold">Verification {{ replay.passed ? 'PASS' : 'FAIL' }}</p>
            <p class="mt-2 text-xs break-all text-slate-400" dir="ltr">{{ replay.digest }}</p>
          </article>
        </div>
      </section>
    </article>
  </AppShell>
</template>

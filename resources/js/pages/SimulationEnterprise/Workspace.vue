<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type NavigationItem = { key: string; label: string; href: string };
type JsonMap = Record<string, unknown>;
type EventItem = {
  sequence: number;
  event_type: string;
  payload: JsonMap;
  occurred_at: string;
};
type SnapshotItem = {
  id: string;
  sequence: number;
  event_sequence: number;
  state_digest: string;
  captured_at: string;
};
type EnterpriseItem = {
  id: string;
  slug: string;
  name_ar: string;
  description_ar?: string | null;
  definition: JsonMap;
  is_fixture: boolean;
  digital_twin_revision?: {
    id: string;
    revision: number;
    digest: string;
    topology: JsonMap;
  } | null;
  baseline?: { id: string; revision: number; digest: string; state: JsonMap } | null;
};
type ScenarioItem = {
  id: string;
  slug: string;
  title_ar: string;
  revision: number;
  baseline_id: string;
  digest: string;
  orchestration: JsonMap;
  validation: JsonMap;
  lab_module_references: Array<{
    reference_id: string;
    module_key: string;
    ordinal: number;
    policy: JsonMap;
    lab_definition_id: string;
    lab_title_ar: string;
  }>;
};
type LabItem = {
  id: string;
  slug: string;
  title_ar: string;
  revision: number;
  baseline_id: string;
  digest: string;
  configuration: JsonMap;
  validation: JsonMap;
};
type RuntimeState = JsonMap & {
  engine?: string;
  trace_digest?: string;
  telemetry?: Record<string, unknown>;
  validation?: Record<string, unknown>;
};
type RunItem = {
  id: string;
  run_type: string;
  lifecycle: string;
  definition_title_ar: string;
  enterprise_id: string;
  digital_twin_revision_id: string;
  baseline_id: string;
  scenario_definition_id?: string | null;
  standalone_lab_definition_id?: string | null;
  seed: number;
  execution_policies: JsonMap;
  runtime_state: RuntimeState;
  input_digest: string;
  available_actions: string[];
  events: EventItem[];
  snapshots: SnapshotItem[];
  result_id?: string | null;
};
type ResultItem = {
  id: string;
  run_id: string;
  run_type: string;
  run_lifecycle: string;
  outcome: string;
  score?: number | null;
  summary_ar: string;
  sealed_payload: JsonMap;
  replay_timeline: EventItem[];
  artifacts: unknown[];
  sealed_at: string;
  candidate_evidence_handoff?: {
    id: string;
    status: string;
    candidate_manifest: JsonMap;
    intake_contract_ref?: string | null;
  } | null;
};
type PostPayload = Parameters<typeof router.post>[1];

const props = defineProps<{
  section: string;
  navigation: NavigationItem[];
  enterprises: EnterpriseItem[];
  scenarios: ScenarioItem[];
  labs: LabItem[];
  runs: RunItem[];
  results: ResultItem[];
  outcomes: string[];
}>();

const selectedId = ref<string | null>(null);
const seed = ref(20260814);
const mode = ref('GUIDED');
const resultOutcome = ref('NOT_EVALUATED');
const resultSummary = ref('لم يُطبّق تقييم نهائي بعد؛ تم ختم الحقائق التشغيلية كما هي.');
const resultScore = ref<number | null>(null);
const handoffClaim = ref(
  'مرشح دليل مشتق من نتيجة المحاكاة المختومة؛ يخضع لاحقًا لعملية Intake في Progress & Evidence.',
);

const records = computed<Array<EnterpriseItem | ScenarioItem | LabItem | RunItem | ResultItem>>(
  () => {
    if (props.section === 'enterprise') return props.enterprises;
    if (props.section === 'scenarios') return props.scenarios;
    if (props.section === 'labs') return props.labs;
    if (props.section === 'runs') return props.runs;
    if (props.section === 'results') return props.results;
    return [];
  },
);

watch(
  records,
  (items) => {
    if (items.length === 0) {
      selectedId.value = null;
      return;
    }
    if (!selectedId.value || !items.some((item) => item.id === selectedId.value)) {
      selectedId.value = items[0].id;
    }
  },
  { immediate: true },
);

const selectedEnterprise = computed(() => {
  if (props.section !== 'enterprise') return null;
  return props.enterprises.find((item) => item.id === selectedId.value) ?? null;
});
const selectedScenario = computed(() => {
  if (props.section !== 'scenarios') return null;
  return props.scenarios.find((item) => item.id === selectedId.value) ?? null;
});
const selectedLab = computed(() => {
  if (props.section !== 'labs') return null;
  return props.labs.find((item) => item.id === selectedId.value) ?? null;
});
const selectedRun = computed(() => {
  if (props.section !== 'runs') return null;
  return props.runs.find((item) => item.id === selectedId.value) ?? null;
});
const selectedResult = computed(() => {
  if (props.section !== 'results') return null;
  return props.results.find((item) => item.id === selectedId.value) ?? null;
});

const pageTitle = computed(
  () =>
    ({
      enterprise: 'المؤسسة والنسخة الرقمية',
      scenarios: 'السيناريوهات',
      labs: 'المختبرات',
      runs: 'التشغيلات',
      results: 'النتائج وإعادة العرض',
    })[props.section] ?? 'المحاكاة والمؤسسة',
);

const jsonText = (value: unknown) => JSON.stringify(value ?? {}, null, 2);
const hasKeys = (value: unknown) =>
  typeof value === 'object' && value !== null && Object.keys(value).length > 0;
const runTypeLabel = (value: string) =>
  value === 'Scenario Run' ? 'Scenario Run' : 'Standalone Lab Run';

function post(path: string, data?: PostPayload): void {
  router.post(path, data, { preserveScroll: true });
}

function prepareScenario(id: string): void {
  post(`/simulation/scenarios/${id}/runs`, { seed: seed.value, mode: mode.value });
}

function prepareLab(id: string): void {
  post(`/simulation/labs/${id}/runs`, { seed: seed.value, mode: mode.value });
}

function runAction(run: RunItem, action: string): void {
  post(`/simulation/runs/${run.id}/${action}`);
}

function sealSelectedRun(): void {
  if (!selectedRun.value) return;
  post(`/simulation/runs/${selectedRun.value.id}/result`, {
    outcome: resultOutcome.value,
    summary_ar: resultSummary.value,
    score: resultScore.value,
  });
}

function createHandoff(): void {
  if (!selectedResult.value) return;
  post(`/simulation/results/${selectedResult.value.id}/candidate-evidence-handoff`, {
    claim_ar: handoffClaim.value,
    artifact_refs: [],
    intake_contract_ref: 'progress-evidence-intake:v1',
  });
}
</script>

<template>
  <Head :title="`CEP — ${pageTitle}`" />
  <div class="workspace" dir="rtl">
    <header class="topbar">
      <div>
        <p class="eyebrow">Simulation &amp; Enterprise</p>
        <h1>{{ pageTitle }}</h1>
        <p class="subtitle">
          تنفيذ داخلي عالي الدقة قائم على الحالة والسببية والأحداث والـ Telemetry والتحقق.
        </p>
      </div>
      <div
        v-if="section === 'scenarios' || section === 'labs'"
        class="prepare-tools"
        aria-label="أدوات التهيئة"
      >
        <label
          >Seed
          <input v-model.number="seed" class="technical input-small" type="number" min="0"
        /></label>
        <label
          >Mode
          <select v-model="mode">
            <option>GUIDED</option>
            <option>UNGUIDED</option>
            <option>SOLO</option>
            <option>TEAM</option>
            <option>ROLE_BASED</option>
          </select>
        </label>
      </div>
      <div
        v-else-if="section === 'runs' && selectedRun"
        class="run-actions"
        aria-label="إجراءات التشغيل الحالية"
      >
        <button
          v-if="selectedRun.available_actions.includes('ready')"
          type="button"
          @click="runAction(selectedRun, 'ready')"
          >اعتماد الجاهزية</button
        >
        <button
          v-if="selectedRun.available_actions.includes('start')"
          type="button"
          @click="runAction(selectedRun, 'start')"
          >بدء</button
        >
        <button
          v-if="selectedRun.available_actions.includes('pause')"
          type="button"
          @click="runAction(selectedRun, 'pause')"
          >إيقاف مؤقت</button
        >
        <button
          v-if="selectedRun.available_actions.includes('resume')"
          type="button"
          @click="runAction(selectedRun, 'resume')"
          >استئناف</button
        >
        <button
          v-if="selectedRun.available_actions.includes('complete')"
          type="button"
          @click="runAction(selectedRun, 'complete')"
          >إكمال المحاكاة الداخلية</button
        >
        <button
          v-if="selectedRun.available_actions.includes('snapshot')"
          type="button"
          @click="runAction(selectedRun, 'snapshot')"
          >حفظ Snapshot</button
        >
        <button
          v-if="selectedRun.available_actions.includes('stop')"
          class="button-muted"
          type="button"
          @click="runAction(selectedRun, 'stop')"
          >إيقاف</button
        >
      </div>
    </header>

    <div class="body-grid">
      <aside class="left-rail" aria-label="بنية المحاكاة والمؤسسة">
        <p class="rail-title">المحاكاة والمؤسسة</p>
        <nav>
          <Link
            v-for="item in navigation"
            :key="item.key"
            :href="item.href"
            class="nav-link"
            :class="{ active: item.key === section }"
            >{{ item.label }}</Link
          >
        </nav>
        <div class="boundary-note">
          <strong>V1</strong>
          <span>Internal High-Fidelity Simulation</span>
          <small>لا يوجد Runtime خارجي أو Provider Connector.</small>
        </div>
      </aside>

      <main class="center-surface">
        <div v-if="records.length === 0" class="empty-state">
          <h2>لا توجد بيانات محفوظة بعد</h2>
          <p>
            هذه الواجهة تقرأ الحالة الفعلية من PostgreSQL. لم تُنشأ تعريفات منشورة في هذا النطاق بعد.
          </p>
        </div>

        <template v-if="section === 'enterprise'">
          <article
            v-for="enterprise in enterprises"
            :key="enterprise.id"
            class="record-card"
            :class="{ selected: selectedId === enterprise.id }"
            @click="selectedId = enterprise.id"
          >
            <div class="record-heading">
              <div>
                <span v-if="enterprise.is_fixture" class="fixture-badge">بيانات تجريبية</span>
                <h2>{{ enterprise.name_ar }}</h2>
              </div>
              <span class="technical slug">{{ enterprise.slug }}</span>
            </div>
            <div class="causal-strip">
              <span>Enterprise</span><b>←</b
              ><span
                >Digital Twin Revision
                {{ enterprise.digital_twin_revision?.revision ?? '—' }}</span
              ><b>←</b><span>Baseline {{ enterprise.baseline?.revision ?? '—' }}</span>
            </div>
            <section class="topology-card">
              <h3>الحالة البنيوية المثبتة</h3>
              <pre class="technical">{{
                jsonText(enterprise.digital_twin_revision?.topology ?? {})
              }}</pre>
            </section>
            <section class="state-card">
              <h3>Baseline State</h3>
              <pre class="technical">{{ jsonText(enterprise.baseline?.state ?? {}) }}</pre>
            </section>
          </article>
        </template>

        <template v-else-if="section === 'scenarios'">
          <article
            v-for="scenario in scenarios"
            :key="scenario.id"
            class="record-card"
            :class="{ selected: selectedId === scenario.id }"
            @click="selectedId = scenario.id"
          >
            <div class="record-heading">
              <h2>{{ scenario.title_ar }}</h2>
              <span class="technical">Revision {{ scenario.revision }}</span>
            </div>
            <p class="technical id-line">{{ scenario.slug }}</p>
            <h3>Lab Module References</h3>
            <div v-if="scenario.lab_module_references.length" class="module-list">
              <div
                v-for="module in scenario.lab_module_references"
                :key="module.reference_id"
                class="module-row"
              >
                <span>{{ module.lab_title_ar }}</span><code>{{ module.module_key }}</code
                ><small>Reference → Lab Definition</small>
              </div>
            </div>
            <p v-else class="muted">لا توجد Lab Module References لهذا السيناريو.</p>
            <button type="button" @click.stop="prepareScenario(scenario.id)">
              Prepare Scenario Run
            </button>
          </article>
        </template>

        <template v-else-if="section === 'labs'">
          <article
            v-for="lab in labs"
            :key="lab.id"
            class="record-card"
            :class="{ selected: selectedId === lab.id }"
            @click="selectedId = lab.id"
          >
            <div class="record-heading">
              <h2>{{ lab.title_ar }}</h2>
              <span class="technical">Revision {{ lab.revision }}</span>
            </div>
            <p class="technical id-line">{{ lab.slug }}</p>
            <h3>تعريف المختبر</h3>
            <pre class="technical">{{ jsonText(lab.configuration) }}</pre>
            <button type="button" @click.stop="prepareLab(lab.id)">
              Prepare Standalone Lab Run
            </button>
          </article>
        </template>

        <template v-else-if="section === 'runs'">
          <article
            v-for="run in runs"
            :key="run.id"
            class="record-card run-card"
            :class="{ selected: selectedId === run.id }"
            @click="selectedId = run.id"
          >
            <div class="record-heading">
              <div>
                <h2>{{ run.definition_title_ar }}</h2>
                <p class="technical id-line">{{ run.id }}</p>
              </div>
              <div class="status-stack">
                <span class="technical run-type">{{ runTypeLabel(run.run_type) }}</span>
                <strong class="technical lifecycle">{{ run.lifecycle }}</strong>
              </div>
            </div>
            <div class="runtime-facts">
              <span>Seed <b class="technical">{{ run.seed }}</b></span>
              <span>Events <b>{{ run.events.length }}</b></span>
              <span>Snapshots <b>{{ run.snapshots.length }}</b></span>
            </div>
            <div
              v-if="run.runtime_state.telemetry && hasKeys(run.runtime_state.telemetry)"
              class="telemetry-grid"
            >
              <div
                v-for="(value, key) in run.runtime_state.telemetry"
                :key="String(key)"
              >
                <small class="technical">{{ key }}</small
                ><strong class="technical">{{ value }}</strong>
              </div>
            </div>
          </article>
        </template>

        <template v-else-if="section === 'results'">
          <article
            v-for="result in results"
            :key="result.id"
            class="record-card result-card"
            :class="{ selected: selectedId === result.id }"
            @click="selectedId = result.id"
          >
            <div class="record-heading">
              <div>
                <h2>نتيجة تشغيل مختومة</h2>
                <p class="technical id-line">Run {{ result.run_id }}</p>
              </div>
              <div class="status-stack">
                <span class="technical">{{ runTypeLabel(result.run_type) }}</span>
                <strong class="technical outcome">{{ result.outcome }}</strong>
              </div>
            </div>
            <div class="runtime-facts">
              <span
                >Run Lifecycle <b class="technical">{{ result.run_lifecycle }}</b></span
              >
              <span>Score <b class="technical">{{ result.score ?? '—' }}</b></span>
              <span>Sealed <b class="technical">{{ result.sealed_at }}</b></span>
            </div>
            <h3>Event-Semantic Replay Timeline</h3>
            <ol class="timeline">
              <li v-for="event in result.replay_timeline" :key="event.sequence">
                <code>{{ event.sequence }}</code
                ><strong class="technical">{{ event.event_type }}</strong
                ><span>{{ event.occurred_at }}</span>
              </li>
            </ol>
          </article>
        </template>
      </main>

      <aside class="right-context" aria-label="السياق الفريد">
        <template v-if="selectedEnterprise">
          <h2>سياق المؤسسة</h2>
          <p>
            {{
              selectedEnterprise.description_ar ||
              'تعريف محاكاة مؤسسية داخلية مثبتة وقابلة لإعادة التنفيذ.'
            }}
          </p>
          <h3>خصائص التعريف</h3>
          <pre class="technical">{{ jsonText(selectedEnterprise.definition) }}</pre>
          <h3>سلامة النسخ</h3>
          <p>
            Digital Twin وBaseline مثبتان بواسطة digests، ولا يتم اشتقاقهما من حالة Run المتغيرة.
          </p>
        </template>
        <template v-else-if="selectedScenario">
          <h2>تعريف السيناريو</h2>
          <h3>Orchestration</h3>
          <pre class="technical">{{ jsonText(selectedScenario.orchestration) }}</pre>
          <h3>Validation</h3>
          <pre class="technical">{{ jsonText(selectedScenario.validation) }}</pre>
          <p class="context-rule">
            Scenario هو كائن orchestration مستقل؛ مرجع Lab لا يتحول إلى Standalone Lab Run داخل
            Scenario Run.
          </p>
        </template>
        <template v-else-if="selectedLab">
          <h2>سياق المختبر</h2>
          <h3>Validation</h3>
          <pre class="technical">{{ jsonText(selectedLab.validation) }}</pre>
          <h3>مرجع Baseline</h3>
          <code class="technical wrap">{{ selectedLab.baseline_id }}</code>
          <h3>Digest</h3>
          <code class="technical wrap">{{ selectedLab.digest }}</code>
        </template>
        <template v-else-if="selectedRun">
          <h2>سياق التشغيل</h2>
          <h3>Lineage المثبت</h3>
          <dl>
            <dt>Digital Twin Revision</dt>
            <dd class="technical">{{ selectedRun.digital_twin_revision_id }}</dd>
            <dt>Baseline</dt>
            <dd class="technical">{{ selectedRun.baseline_id }}</dd>
            <dt>Input Digest</dt>
            <dd class="technical">{{ selectedRun.input_digest }}</dd>
          </dl>
          <h3>Execution Policies</h3>
          <pre class="technical">{{ jsonText(selectedRun.execution_policies) }}</pre>
          <form
            v-if="
              ['COMPLETED', 'STOPPED', 'FAILED'].includes(selectedRun.lifecycle) &&
              !selectedRun.result_id
            "
            class="action-form"
            @submit.prevent="sealSelectedRun"
          >
            <h3>ختم Result</h3>
            <label
              >Outcome
              <select v-model="resultOutcome">
                <option v-for="outcome in outcomes" :key="outcome">{{ outcome }}</option>
              </select>
            </label>
            <label
              >التفسير
              <textarea v-model="resultSummary" rows="4" />
            </label>
            <label
              >Score
              <input
                v-model.number="resultScore"
                type="number"
                min="0"
                max="100"
                step="0.01"
              />
            </label>
            <button type="submit">ختم النتيجة التاريخية</button>
          </form>
          <p v-else-if="selectedRun.result_id" class="sealed-note">
            لهذا التشغيل Result مختوم بالفعل؛ لا يمكن إعادة كتابة التاريخ.
          </p>
        </template>
        <template v-else-if="selectedResult">
          <h2>التفسير وحدّ Evidence</h2>
          <p>{{ selectedResult.summary_ar }}</p>
          <h3>Candidate Evidence Handoff</h3>
          <div v-if="selectedResult.candidate_evidence_handoff" class="handoff-state">
            <strong class="technical">{{
              selectedResult.candidate_evidence_handoff.status
            }}</strong>
            <p>
              تم إنشاء Handoff فقط. قبول Evidence والمراجعة وMastery ليست ملكًا لـ Simulation &amp;
              Enterprise.
            </p>
          </div>
          <form v-else class="action-form" @submit.prevent="createHandoff">
            <label
              >Candidate claim
              <textarea v-model="handoffClaim" rows="5" />
            </label>
            <button type="submit">Prepare Candidate Evidence Handoff</button>
          </form>
        </template>
        <p v-else class="muted">اختر سجلًا من سطح العمل لعرض سياقه دون تكرار الحقائق الأساسية.</p>
      </aside>
    </div>

    <details v-if="section === 'runs' && selectedRun" class="bottom-workspace">
      <summary>مساحة العمل العميقة المؤقتة — Timeline / Snapshots</summary>
      <div class="bottom-grid">
        <section>
          <h3>Event Timeline</h3>
          <ol class="timeline">
            <li v-for="event in selectedRun.events" :key="event.sequence">
              <code>{{ event.sequence }}</code
              ><strong class="technical">{{ event.event_type }}</strong>
              <pre class="technical">{{ jsonText(event.payload) }}</pre>
            </li>
          </ol>
        </section>
        <section>
          <h3>Runtime Snapshots</h3>
          <div
            v-for="snapshot in selectedRun.snapshots"
            :key="snapshot.id"
            class="snapshot-row"
          >
            <strong>Snapshot {{ snapshot.sequence }}</strong>
            <code class="technical wrap">{{ snapshot.state_digest }}</code>
          </div>
        </section>
      </div>
    </details>
    <details
      v-else-if="section === 'results' && selectedResult"
      class="bottom-workspace"
    >
      <summary>مساحة Replay العميقة المؤقتة — Artifacts / Frozen Payload</summary>
      <div class="bottom-grid">
        <section>
          <h3>Artifacts</h3>
          <pre class="technical">{{ jsonText(selectedResult.artifacts) }}</pre>
        </section>
        <section>
          <h3>Frozen Result Payload</h3>
          <pre class="technical">{{ jsonText(selectedResult.sealed_payload) }}</pre>
        </section>
      </div>
    </details>
  </div>
</template>

<style scoped>
.workspace {
  min-height: 100vh;
  background: #0b1118;
  color: #e7edf4;
  font-family: Inter, "Noto Sans Arabic", system-ui, sans-serif;
}
.topbar {
  min-height: 108px;
  padding: 24px 28px;
  border-bottom: 1px solid #263240;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 28px;
  background: #101923;
}
.eyebrow {
  margin: 0 0 5px;
  direction: ltr;
  text-align: right;
  color: #8da2b8;
  font-size: 12px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
h1,
h2,
h3,
p {
  margin-top: 0;
}
h1 {
  margin-bottom: 6px;
  font-size: 25px;
}
h2 {
  font-size: 18px;
  margin-bottom: 10px;
}
h3 {
  font-size: 13px;
  color: #b6c5d5;
  margin: 18px 0 8px;
}
.subtitle {
  margin: 0;
  color: #9fb0c2;
  max-width: 780px;
}
.body-grid {
  display: grid;
  grid-template-columns: 190px minmax(0, 1fr) 310px;
  min-height: calc(100vh - 108px);
}
.left-rail,
.right-context {
  background: #0e161f;
  padding: 22px 18px;
}
.left-rail {
  border-left: 1px solid #263240;
}
.right-context {
  border-right: 1px solid #263240;
}
.rail-title {
  color: #8da2b8;
  font-size: 12px;
  margin-bottom: 16px;
}
.nav-link {
  display: block;
  padding: 10px 12px;
  margin-bottom: 4px;
  border-radius: 8px;
  color: #c4d0dc;
  text-decoration: none;
  border: 1px solid transparent;
}
.nav-link.active {
  background: #172433;
  border-color: #36506a;
  color: #fff;
}
.boundary-note {
  margin-top: 28px;
  padding: 12px;
  border: 1px solid #263a4e;
  border-radius: 8px;
  display: grid;
  gap: 5px;
  color: #aebdcb;
}
.boundary-note strong,
.boundary-note span {
  direction: ltr;
  unicode-bidi: isolate;
}
.boundary-note small {
  line-height: 1.7;
}
.center-surface {
  padding: 24px;
  overflow: auto;
}
.record-card {
  border: 1px solid #263746;
  background: #111b25;
  border-radius: 12px;
  padding: 18px;
  margin-bottom: 14px;
  cursor: pointer;
}
.record-card.selected {
  border-color: #57789a;
  box-shadow: 0 0 0 1px #314d67 inset;
}
.record-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
}
.record-heading h2 {
  margin-bottom: 4px;
}
.slug,
.id-line {
  color: #8397aa;
  font-size: 12px;
}
.fixture-badge {
  display: inline-block;
  margin-bottom: 7px;
  font-size: 11px;
  color: #d6c79a;
  border: 1px solid #6d6040;
  border-radius: 999px;
  padding: 2px 8px;
}
.causal-strip {
  display: flex;
  gap: 9px;
  flex-wrap: wrap;
  align-items: center;
  direction: ltr;
  unicode-bidi: isolate;
  margin: 14px 0;
  font-size: 12px;
  color: #afc0d0;
}
.topology-card,
.state-card {
  border-top: 1px solid #243443;
}
.module-list {
  display: grid;
  gap: 8px;
}
.module-row {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 12px;
  align-items: center;
  background: #0d151e;
  padding: 10px;
  border-radius: 8px;
}
.module-row code,
.module-row small {
  direction: ltr;
  unicode-bidi: isolate;
}
button {
  border: 1px solid #486681;
  border-radius: 8px;
  padding: 9px 12px;
  background: #1a3146;
  color: #f1f6fb;
  cursor: pointer;
  font: inherit;
}
button:hover {
  background: #22405a;
}
.button-muted {
  background: transparent;
  border-color: #534f55;
}
.prepare-tools,
.run-actions {
  display: flex;
  align-items: end;
  gap: 10px;
  flex-wrap: wrap;
}
.prepare-tools label {
  display: grid;
  gap: 5px;
  color: #9fb0c2;
  font-size: 12px;
}
input,
select,
textarea {
  border: 1px solid #34485b;
  border-radius: 7px;
  background: #0b141d;
  color: #eef4fa;
  padding: 8px;
  font: inherit;
}
.input-small {
  width: 120px;
}
.action-form {
  display: grid;
  gap: 10px;
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid #263746;
}
.action-form label {
  display: grid;
  gap: 5px;
  color: #aebdcb;
  font-size: 12px;
}
pre {
  margin: 7px 0 0;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  background: #091018;
  border: 1px solid #202f3c;
  border-radius: 8px;
  padding: 10px;
  color: #b9cada;
  font-size: 11px;
  line-height: 1.55;
}
.technical {
  direction: ltr;
  unicode-bidi: isolate;
  text-align: left;
}
.wrap {
  overflow-wrap: anywhere;
  white-space: normal;
}
.right-context {
  overflow: auto;
}
.right-context p {
  color: #aebdcb;
  line-height: 1.75;
  font-size: 13px;
}
.right-context dl {
  margin: 0;
}
.right-context dt {
  color: #8397aa;
  font-size: 11px;
  margin-top: 10px;
}
.right-context dd {
  margin: 3px 0 0;
  font-size: 11px;
  overflow-wrap: anywhere;
}
.context-rule,
.sealed-note,
.handoff-state {
  border: 1px solid #33475a;
  border-radius: 8px;
  padding: 11px;
  background: #111d28;
}
.handoff-state strong {
  display: block;
  margin-bottom: 8px;
}
.status-stack {
  display: grid;
  justify-items: end;
  gap: 6px;
}
.run-type {
  color: #9fb1c4;
  font-size: 11px;
}
.lifecycle,
.outcome {
  border: 1px solid #526b84;
  border-radius: 999px;
  padding: 4px 9px;
  font-size: 11px;
}
.runtime-facts {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  padding-top: 12px;
  margin-top: 12px;
  border-top: 1px solid #243443;
  color: #8fa2b4;
  font-size: 12px;
}
.runtime-facts b {
  color: #dce8f2;
  margin-inline-start: 4px;
}
.telemetry-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 8px;
  margin-top: 12px;
}
.telemetry-grid div {
  background: #0c151e;
  padding: 9px;
  border-radius: 7px;
  display: grid;
  gap: 5px;
}
.telemetry-grid small {
  color: #8095a8;
}
.timeline {
  margin: 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 7px;
}
.timeline li {
  display: grid;
  grid-template-columns: 38px minmax(130px, auto) 1fr;
  gap: 10px;
  align-items: start;
  border-right: 2px solid #36516b;
  padding: 8px 10px;
  background: #0d161f;
  font-size: 11px;
}
.timeline li span {
  color: #71869a;
  direction: ltr;
  text-align: left;
}
.bottom-workspace {
  border-top: 1px solid #263240;
  background: #0a121a;
  padding: 0 28px;
}
.bottom-workspace summary {
  cursor: pointer;
  padding: 15px 0;
  color: #b7c6d5;
}
.bottom-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
  padding-bottom: 22px;
}
.snapshot-row {
  display: grid;
  gap: 5px;
  padding: 10px;
  border-bottom: 1px solid #263240;
}
.empty-state {
  max-width: 700px;
  margin: 60px auto;
  text-align: center;
  border: 1px dashed #354a5d;
  border-radius: 12px;
  padding: 30px;
  color: #9fb0c2;
}
.empty-state code {
  direction: ltr;
  unicode-bidi: isolate;
  display: inline-block;
  margin-top: 10px;
}
.muted {
  color: #71869a;
}
@media (max-width: 1100px) {
  .body-grid {
    grid-template-columns: 160px minmax(0, 1fr);
  }
  .right-context {
    grid-column: 1 / -1;
    border-right: 0;
    border-top: 1px solid #263240;
  }
  .topbar {
    align-items: flex-start;
    flex-direction: column;
  }
  .bottom-grid {
    grid-template-columns: 1fr;
  }
}
</style>

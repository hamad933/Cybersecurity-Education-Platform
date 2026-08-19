<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ContextInspector from './components/ContextInspector.vue';
import DeepWorkspace from './components/DeepWorkspace.vue';
import EnterpriseCanvas from './components/EnterpriseCanvas.vue';
import LabCanvas from './components/LabCanvas.vue';
import ResultsCanvas from './components/ResultsCanvas.vue';
import RunCanvas from './components/RunCanvas.vue';
import ScenarioCanvas from './components/ScenarioCanvas.vue';
import type {
  EnterpriseItem,
  LabItem,
  NavigationItem,
  ResultItem,
  RunItem,
  ScenarioItem,
  WorkspaceRecord,
} from './types';
import { recordLabel, recordMeta } from './utils';

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

const PRIMARY_KEYS = ['enterprise', 'scenarios', 'labs', 'runs', 'results'] as const;
const selectedId = ref<string | null>(null);
const seed = ref(20260814);
const mode = ref('GUIDED');

const primaryNavigation = computed(() =>
  PRIMARY_KEYS.map((key) => props.navigation.find((item) => item.key === key)).filter(
    (item): item is NavigationItem => item !== undefined,
  ),
);

const records = computed<WorkspaceRecord[]>(() => {
  if (props.section === 'enterprise') return props.enterprises;
  if (props.section === 'scenarios') return props.scenarios;
  if (props.section === 'labs') return props.labs;
  if (props.section === 'runs') return props.runs;
  if (props.section === 'results') return props.results;
  return [];
});

watch(
  records,
  (items) => {
    if (!items.length) {
      selectedId.value = null;
    } else if (!selectedId.value || !items.some((item) => item.id === selectedId.value)) {
      selectedId.value = items[0].id;
    }
  },
  { immediate: true },
);

const selectedEnterprise = computed(() =>
  props.section === 'enterprise'
    ? (props.enterprises.find((item) => item.id === selectedId.value) ?? null)
    : null,
);
const selectedScenario = computed(() =>
  props.section === 'scenarios'
    ? (props.scenarios.find((item) => item.id === selectedId.value) ?? null)
    : null,
);
const selectedLab = computed(() =>
  props.section === 'labs' ? (props.labs.find((item) => item.id === selectedId.value) ?? null) : null,
);
const selectedRun = computed(() =>
  props.section === 'runs' ? (props.runs.find((item) => item.id === selectedId.value) ?? null) : null,
);
const selectedResult = computed(() =>
  props.section === 'results'
    ? (props.results.find((item) => item.id === selectedId.value) ?? null)
    : null,
);

const pageTitle = computed(
  () =>
    ({
      enterprise: 'المؤسسة',
      scenarios: 'السيناريوهات',
      labs: 'المختبرات',
      runs: 'التشغيلات',
      results: 'النتائج',
    })[props.section] ?? 'المحاكاة والمؤسسة',
);

function post(path: string, data?: PostPayload): void {
  router.post(path, data, { preserveScroll: true });
}
function prepareScenario(id: string): void {
  post(`/simulation/scenarios/${id}/runs`, { seed: seed.value, mode: mode.value });
}
function prepareLab(id: string): void {
  post(`/simulation/labs/${id}/runs`, { seed: seed.value, mode: mode.value });
}
function runAction(action: string): void {
  if (selectedRun.value) post(`/simulation/runs/${selectedRun.value.id}/${action}`);
}
function sealRun(payload: { outcome: string; summary_ar: string; score: number | null }): void {
  if (selectedRun.value) post(`/simulation/runs/${selectedRun.value.id}/result`, payload);
}
function createHandoff(claim: string): void {
  if (!selectedResult.value) return;
  post(`/simulation/results/${selectedResult.value.id}/candidate-evidence-handoff`, {
    claim_ar: claim,
    artifact_refs: [],
    intake_contract_ref: 'progress-evidence-intake:v1',
  });
}
</script>

<template>
  <Head :title="`CEP — ${pageTitle}`" />
  <div class="simulation-workspace" dir="rtl">
    <header class="top-zone" data-zone="top">
      <div>
        <p class="eyebrow" dir="ltr">SIMULATION &amp; ENTERPRISE</p>
        <div class="title-row"><h1>{{ pageTitle }}</h1><span class="scope-badge">Internal High-Fidelity Simulation</span></div>
        <p class="subtitle">سطح عمل تشغيلي يميّز التعريفات المثبتة، وحالة التشغيل، والحقائق التاريخية المختومة.</p>
      </div>
      <div v-if="section === 'scenarios' || section === 'labs'" class="top-controls">
        <label><span>Seed</span><input v-model.number="seed" class="technical compact-input" type="number" min="0" /></label>
        <label><span>Mode</span><select v-model="mode" class="technical"><option>GUIDED</option><option>UNGUIDED</option><option>SOLO</option><option>TEAM</option><option>ROLE_BASED</option></select></label>
      </div>
      <div v-else-if="selectedRun" class="top-context"><span>الحالة الحالية</span><strong class="technical state-chip" dir="ltr">{{ selectedRun.lifecycle }}</strong></div>
      <div v-else-if="selectedResult" class="top-context"><span>تاريخ مختوم</span><strong class="technical state-chip" dir="ltr">{{ selectedResult.outcome }}</strong></div>
    </header>

    <div class="body-grid">
      <aside class="left-zone" data-zone="left" aria-label="التنقل وبنية النطاق">
        <p class="rail-kicker">المناطق الرئيسية</p>
        <nav class="primary-nav" data-testid="simulation-primary-nav" aria-label="المناطق الرئيسية">
          <Link v-for="item in primaryNavigation" :key="item.key" :href="item.href" class="nav-link" :class="{ active: item.key === section }"><span>{{ item.label }}</span><small class="technical" dir="ltr">{{ item.key }}</small></Link>
        </nav>
        <section class="catalog-section">
          <p class="rail-kicker">الفهرس الحالي</p><h2>{{ pageTitle }}</h2>
          <div v-if="records.length" class="record-list" role="listbox" :aria-label="pageTitle">
            <button v-for="record in records" :key="record.id" type="button" class="record-option" :class="{ selected: selectedId === record.id }" :aria-pressed="selectedId === record.id" @click="selectedId = record.id"><strong>{{ recordLabel(record) }}</strong><small class="technical" dir="ltr">{{ recordMeta(record) }}</small></button>
          </div>
          <p v-else class="rail-empty">لا توجد سجلات مستلمة من الخادم في هذا النطاق.</p>
        </section>
      </aside>

      <main class="center-zone" data-zone="center">
        <section v-if="!records.length" class="empty-state"><span>—</span><h2>لا توجد حالة تشغيلية متاحة</h2><p>تعرض هذه الصفحة بيانات الخادم الفعلية فقط، ولن تنشئ تعريفات أو قياسات بديلة.</p></section>
        <EnterpriseCanvas v-else-if="selectedEnterprise" :enterprise="selectedEnterprise" />
        <ScenarioCanvas v-else-if="selectedScenario" :scenario="selectedScenario" @prepare="prepareScenario" />
        <LabCanvas v-else-if="selectedLab" :lab="selectedLab" @prepare="prepareLab" />
        <RunCanvas v-else-if="selectedRun" :run="selectedRun" :outcomes="outcomes" @action="runAction" @seal="sealRun" />
        <ResultsCanvas v-else-if="selectedResult" :result="selectedResult" :results="results" />
      </main>

      <ContextInspector
        :enterprise="selectedEnterprise"
        :scenario="selectedScenario"
        :lab="selectedLab"
        :run="selectedRun"
        :result="selectedResult"
        @handoff="createHandoff"
      />
    </div>

    <DeepWorkspace :run="selectedRun" :result="selectedResult" />
  </div>
</template>

<style src="./Workspace.css"></style>

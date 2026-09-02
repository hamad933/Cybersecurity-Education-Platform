<script setup lang="ts">
import { ref } from 'vue';

import type {
  DigitalTwinRevisionItem,
  LabItem,
  ResultItem,
  ResultMode,
  RunWorkspaceMode,
  RunItem,
  ScenarioItem,
  SimulationSection,
} from '../types';

const props = withDefaults(
  defineProps<{
    section: SimulationSection;
    scenario: ScenarioItem | null;
    lab: LabItem | null;
    twinRevision: DigitalTwinRevisionItem | null;
    run: RunItem | null;
    result: ResultItem | null;
    resultsMode?: ResultMode;
    resultsLoading?: boolean;
    runMode?: RunWorkspaceMode;
    pending: boolean;
  }>(),
  {
    resultsMode: 'overview',
    resultsLoading: false,
    runMode: 'operations',
  },
);

const emit = defineEmits<{
  runAction: [action: string];
  definitionAction: [target: 'lab' | 'digital-twin', action: string];
  setResultsMode: [mode: ResultMode];
  setRunMode: [mode: RunWorkspaceMode];
  openRunPreflight: [type: 'scenario' | 'standalone-lab', definitionId: string];
  openBottom: [];
}>();

const modeTabRefs = ref<HTMLButtonElement[]>([]);

const actionLabels: Record<string, string> = {
  ready: 'اعتماد الجاهزية',
  start: 'بدء التشغيل',
  pause: 'إيقاف مؤقت',
  resume: 'استئناف',
  complete: 'إكمال',
  snapshot: 'حفظ Snapshot',
  stop: 'إيقاف',
};

const resultModes: Array<{ key: ResultMode; label: string }> = [
  { key: 'overview', label: 'نظرة عامة' },
  { key: 'replay', label: 'Replay' },
  { key: 'aar', label: 'AAR' },
  { key: 'compare', label: 'Compare' },
  { key: 'candidate-evidence', label: 'Candidate Evidence' },
];

function setModeTabRef(element: unknown, index: number): void {
  if (element instanceof HTMLButtonElement) {
    modeTabRefs.value[index] = element;
  }
}

function activateResultMode(mode: ResultMode): void {
  if (props.resultsLoading || mode === props.resultsMode) return;
  emit('setResultsMode', mode);
}

function moveResultMode(event: KeyboardEvent, index: number): void {
  if (props.resultsLoading || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
    return;
  }
  event.preventDefault();
  const next =
    event.key === 'Home'
      ? 0
      : event.key === 'End'
        ? resultModes.length - 1
        : (index + (event.key === 'ArrowLeft' ? 1 : -1) + resultModes.length) % resultModes.length;
  modeTabRefs.value[next]?.focus();
  activateResultMode(resultModes[next].key);
}
</script>

<template>
  <div class="sim-toolbar" data-testid="workspace-toolbar">
    <div class="sim-toolbar__identity">
      <span class="sim-domain-mark" aria-hidden="true" />
      <div><small>Simulation &amp; Enterprise</small><strong>تنفيذ داخلي عالي الدقة</strong></div>
    </div>

    <div
      v-if="section === 'scenarios' && scenario"
      class="sim-toolbar__controls"
      data-testid="scenario-preflight-entry"
    >
      <span class="sim-target-lock">
        <small>Scenario Revision</small>
        <code>REV {{ scenario.revision }} · {{ scenario.digest }}</code>
      </span>
      <button
        type="button"
        class="sim-button"
        :disabled="pending || !scenario.preparation_targets.length"
        @click="emit('openRunPreflight', 'scenario', scenario.id)"
      >
        فتح Run Preflight
      </button>
      <button type="button" class="sim-button sim-button--quiet" @click="emit('openBottom')">
        العقد وOrchestration الخام
      </button>
    </div>

    <form
      v-else-if="section === 'labs' && lab"
      class="sim-toolbar__controls"
      data-testid="lab-prepare-controls"
      @submit.prevent="
        lab.can_prepare !== false &&
        lab.status === 'PUBLISHED' &&
        emit('openRunPreflight', 'standalone-lab', lab.id)
      "
    >
      <span class="sim-target-lock">
        <small>{{ lab.environment_binding_mode ?? 'Definition' }}</small>
        <code>REV {{ lab.revision }} · {{ lab.status ?? 'LEGACY' }}</code>
      </span>
      <button
        v-if="lab.status === 'DRAFT'"
        type="button"
        class="sim-button"
        :disabled="pending"
        @click="emit('definitionAction', 'lab', 'validate')"
      >
        Validate definition
      </button>
      <button
        v-if="lab.status === 'VALIDATED'"
        type="button"
        class="sim-button"
        :disabled="pending"
        @click="emit('definitionAction', 'lab', 'publish')"
      >
        Publish immutable revision
      </button>
      <button
        v-if="lab.status === 'PUBLISHED'"
        type="button"
        class="sim-button sim-button--quiet"
        :disabled="pending"
        @click="emit('definitionAction', 'lab', 'clone')"
      >
        Clone as new revision
      </button>
      <button
        type="submit"
        class="sim-button"
        :disabled="pending || lab.can_prepare === false || lab.status !== 'PUBLISHED'"
      >
        فتح Standalone Lab Preflight
      </button>
      <button type="button" class="sim-button sim-button--quiet" @click="emit('openBottom')">
        الإعداد والتحقق الخام
      </button>
    </form>

    <div
      v-else-if="section === 'runs'"
      class="sim-runs-toolbar"
      data-testid="run-workspace-toolbar"
    >
      <div class="sim-run-mode-tabs" role="tablist" aria-label="أوضاع مساحة التشغيل">
        <button
          type="button"
          role="tab"
          class="sim-results-mode-tab"
          :class="{ 'is-active': runMode === 'preflight' }"
          :aria-selected="runMode === 'preflight'"
          :tabindex="runMode === 'preflight' ? 0 : -1"
          :disabled="pending"
          @click="emit('setRunMode', 'preflight')"
        >
          Run Preflight
        </button>
        <button
          type="button"
          role="tab"
          class="sim-results-mode-tab"
          :class="{ 'is-active': runMode === 'operations' }"
          :aria-selected="runMode === 'operations'"
          :tabindex="runMode === 'operations' ? 0 : -1"
          :disabled="pending"
          @click="emit('setRunMode', 'operations')"
        >
          Active Operations
        </button>
      </div>
      <div
        v-if="runMode === 'operations' && run"
        class="sim-toolbar__controls"
        data-testid="run-actions"
      >
        <button
          v-for="action in run.available_actions.filter((item) => item !== 'operate')"
          :key="action"
          type="button"
          class="sim-button"
          :class="{
            'sim-button--danger': action === 'stop',
            'sim-button--quiet': action === 'snapshot',
          }"
          :disabled="pending"
          @click="emit('runAction', action)"
        >
          {{ actionLabels[action] ?? action }}
        </button>
        <button type="button" class="sim-button sim-button--quiet" @click="emit('openBottom')">
          السجل العميق
        </button>
      </div>
      <span v-else-if="runMode === 'operations'" class="sim-toolbar__idle">
        اختر تشغيلًا لإظهار الإجراءات المتاحة.
      </span>
    </div>

    <div
      v-else-if="section === 'results' && result"
      class="sim-results-toolbar"
      data-testid="result-actions"
    >
      <div class="sim-results-mode-tabs" role="tablist" aria-label="أوضاع تحليل النتيجة">
        <button
          v-for="(item, index) in resultModes"
          :key="item.key"
          :ref="(element) => setModeTabRef(element, index)"
          type="button"
          role="tab"
          class="sim-results-mode-tab"
          :class="{ 'is-active': resultsMode === item.key }"
          :aria-selected="resultsMode === item.key"
          :tabindex="resultsMode === item.key ? 0 : -1"
          :disabled="resultsLoading"
          @click="activateResultMode(item.key)"
          @keydown="moveResultMode($event, index)"
        >
          {{ item.label }}
        </button>
      </div>
      <span class="sim-target-lock sim-results-revision-lock">
        <small>EFFECTIVE REVISION</small>
        <code>{{
          result.analytics.overview.effective?.id ?? result.analytics.overview.status
        }}</code>
      </span>
      <button type="button" class="sim-button sim-button--quiet" @click="emit('openBottom')">
        الفحص الخام
      </button>
    </div>

    <div v-else-if="section === 'enterprise'" class="sim-toolbar__controls">
      <span v-if="twinRevision" class="sim-target-lock">
        <small>Digital Twin Revision</small>
        <code>REV {{ twinRevision.revision }} - {{ twinRevision.status ?? 'PUBLISHED' }}</code>
      </span>
      <button
        v-if="twinRevision?.status === 'DRAFT'"
        type="button"
        class="sim-button"
        :disabled="pending"
        @click="emit('definitionAction', 'digital-twin', 'validate')"
      >
        Validate definition
      </button>
      <button
        v-if="twinRevision?.status === 'VALIDATED'"
        type="button"
        class="sim-button"
        :disabled="pending"
        @click="emit('definitionAction', 'digital-twin', 'publish')"
      >
        Publish immutable revision
      </button>
      <button
        v-if="twinRevision?.status === 'PUBLISHED'"
        type="button"
        class="sim-button sim-button--quiet"
        :disabled="pending"
        @click="emit('definitionAction', 'digital-twin', 'clone')"
      >
        Clone as new revision
      </button>
      <button type="button" class="sim-button sim-button--quiet" @click="emit('openBottom')">
        فحص البنية الخام
      </button>
    </div>
    <span v-else class="sim-toolbar__idle">اختر سجلًا من لوحة البنية لتفعيل أدواته.</span>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

import type {
  DigitalTwinRevisionItem,
  LabItem,
  ResultItem,
  RunItem,
  ScenarioItem,
  SimulationSection,
} from '../types';

const props = defineProps<{
  section: SimulationSection;
  scenario: ScenarioItem | null;
  lab: LabItem | null;
  twinRevision: DigitalTwinRevisionItem | null;
  run: RunItem | null;
  result: ResultItem | null;
  pending: boolean;
}>();

const emit = defineEmits<{
  prepareScenario: [payload: { baseline_id: string; seed: number; mode: string }];
  prepareLab: [payload: { seed: number; mode: string }];
  runAction: [action: string];
  definitionAction: [target: 'lab' | 'digital-twin', action: string];
  replay: [];
  openBottom: [];
}>();

const seed = ref(20260814);
const mode = ref('GUIDED');
const baselineId = ref('');

watch(
  () => props.scenario,
  (scenario) => {
    baselineId.value = scenario?.preparation_targets[0]?.baseline_id ?? '';
  },
  { immediate: true },
);

const actionLabels: Record<string, string> = {
  ready: 'اعتماد الجاهزية',
  start: 'بدء التشغيل',
  pause: 'إيقاف مؤقت',
  resume: 'استئناف',
  complete: 'إكمال',
  snapshot: 'حفظ Snapshot',
  stop: 'إيقاف',
};
</script>

<template>
  <div class="sim-toolbar" data-testid="workspace-toolbar">
    <div class="sim-toolbar__identity">
      <span class="sim-domain-mark" aria-hidden="true" />
      <div><small>Simulation &amp; Enterprise</small><strong>تنفيذ داخلي عالي الدقة</strong></div>
    </div>

    <form
      v-if="section === 'scenarios' && scenario"
      class="sim-toolbar__controls"
      data-testid="scenario-prepare-controls"
      @submit.prevent="emit('prepareScenario', { baseline_id: baselineId, seed, mode })"
    >
      <label
        ><span>Execution target</span
        ><select
          v-model="baselineId"
          required
          :disabled="pending || !scenario.preparation_targets.length"
        >
          <option
            v-for="target in scenario.preparation_targets"
            :key="target.baseline_id"
            :value="target.baseline_id"
          >
            {{ target.enterprise_name_ar }} / {{ target.digital_twin_name_ar }} · B{{
              target.baseline_revision
            }}
          </option>
        </select></label
      >
      <label
        ><span>Seed</span
        ><input
          v-model.number="seed"
          class="sim-technical"
          type="number"
          min="0"
          :disabled="pending"
      /></label>
      <label
        ><span>Mode</span
        ><select v-model="mode" :disabled="pending">
          <option>GUIDED</option>
          <option>UNGUIDED</option>
          <option>SOLO</option>
          <option>TEAM</option>
          <option>ROLE_BASED</option>
        </select></label
      >
      <button class="sim-button" type="submit" :disabled="pending || !baselineId">
        تهيئة التشغيل المحدد
      </button>
      <button type="button" class="sim-button sim-button--quiet" @click="emit('openBottom')">
        العقد وOrchestration الخام
      </button>
    </form>

    <form
      v-else-if="section === 'labs' && lab"
      class="sim-toolbar__controls"
      data-testid="lab-prepare-controls"
      @submit.prevent="
        lab.can_prepare !== false &&
        (lab.status === undefined || lab.status === 'PUBLISHED') &&
        emit('prepareLab', { seed, mode })
      "
    >
      <span class="sim-target-lock">
        <small>{{ lab.environment_binding_mode ?? 'Baseline' }}</small>
        <code>{{ lab.baseline_id ?? 'LAB-LOCAL PROFILE' }}</code>
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
      <label
        ><span>Seed</span
        ><input
          v-model.number="seed"
          class="sim-technical"
          type="number"
          min="0"
          :disabled="pending"
      /></label>
      <label
        ><span>Mode</span
        ><select v-model="mode" :disabled="pending">
          <option>GUIDED</option>
          <option>UNGUIDED</option>
          <option>SOLO</option>
          <option>TEAM</option>
          <option>ROLE_BASED</option>
        </select></label
      >
      <button
        class="sim-button"
        type="submit"
        :disabled="
          pending ||
          lab.can_prepare === false ||
          (lab.status !== undefined && lab.status !== 'PUBLISHED')
        "
      >
        تهيئة مختبر مستقل
      </button>
      <button type="button" class="sim-button sim-button--quiet" @click="emit('openBottom')">
        الإعداد والتحقق الخام
      </button>
    </form>

    <div
      v-else-if="section === 'runs' && run"
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

    <div
      v-else-if="section === 'results' && result"
      class="sim-toolbar__controls"
      data-testid="result-actions"
    >
      <span class="sim-target-lock"
        ><small>SEALED RESULT</small><code>REV {{ result.result_revision }}</code></span
      >
      <button type="button" class="sim-button" :disabled="pending" @click="emit('replay')">
        إعادة البناء والمقارنة
      </button>
      <button type="button" class="sim-button sim-button--quiet" @click="emit('openBottom')">
        الحمولة الخام
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

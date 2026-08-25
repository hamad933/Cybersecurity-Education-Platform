<script setup lang="ts">
import { ref, watch } from 'vue';

import type { LabItem, ResultItem, RunItem, ScenarioItem, SimulationSection } from '../types';

const props = defineProps<{
  section: SimulationSection;
  scenario: ScenarioItem | null;
  lab: LabItem | null;
  run: RunItem | null;
  result: ResultItem | null;
  pending: boolean;
}>();

const emit = defineEmits<{
  prepareScenario: [payload: { baseline_id: string; seed: number; mode: string }];
  prepareLab: [payload: { seed: number; mode: string }];
  runAction: [action: string];
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
      <span class="sim-live-dot" aria-hidden="true" />
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
    </form>

    <form
      v-else-if="section === 'labs' && lab"
      class="sim-toolbar__controls"
      data-testid="lab-prepare-controls"
      @submit.prevent="emit('prepareLab', { seed, mode })"
    >
      <span class="sim-target-lock"
        ><small>Baseline</small><code>{{ lab.baseline_id }}</code></span
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
      <button class="sim-button" type="submit" :disabled="pending">تهيئة مختبر مستقل</button>
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
        Replay الكامل
      </button>
    </div>

    <button
      v-else-if="section === 'enterprise'"
      type="button"
      class="sim-button sim-button--quiet"
      @click="emit('openBottom')"
    >
      فحص البنية الخام
    </button>
    <span v-else class="sim-toolbar__idle">اختر سجلًا من لوحة البنية لتفعيل أدواته.</span>
  </div>
</template>

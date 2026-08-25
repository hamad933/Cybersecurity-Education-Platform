<script setup lang="ts">
import { ref } from 'vue';

import LifecycleBadge from './LifecycleBadge.vue';
import { shortDigest } from '../formatters';
import type { RunItem } from '../types';

defineProps<{ run: RunItem | null; pending: boolean; outcomes: string[] }>();
const emit = defineEmits<{
  operate: [value: boolean];
  seal: [payload: { outcome: string; summary_ar: string; score: number | null }];
}>();

const operationValue = ref(false);
const resultOutcome = ref('NOT_EVALUATED');
const resultSummary = ref('لم يُطبّق تقييم نهائي بعد؛ تم ختم الحقائق التشغيلية كما هي.');
const resultScore = ref<number | null>(null);
</script>

<template>
  <div class="sim-context" data-testid="run-right">
    <div class="sim-panel-heading">
      <p class="sim-kicker">RIGHT · INTERPRETATION / ACTIONS</p>
      <h2>سياق القرار التشغيلي</h2>
    </div>
    <template v-if="run">
      <LifecycleBadge :value="run.lifecycle" />
      <dl class="sim-facts">
        <div>
          <dt>Run type</dt>
          <dd class="sim-technical">{{ run.run_type }}</dd>
        </div>
        <div>
          <dt>Baseline</dt>
          <dd class="sim-technical">{{ shortDigest(run.baseline_id) }}</dd>
        </div>
        <div>
          <dt>Provenance</dt>
          <dd class="sim-technical">{{ run.provenance }}</dd>
        </div>
      </dl>

      <section class="sim-context-section" data-testid="run-interpretation">
        <h3>حدود التفسير</h3>
        <p class="sim-context-copy">
          حقائق الآلة والأحداث وTelemetry معروضة في مساحة العمليات المركزية. هذه اللوحة مخصصة
          للإجراءات المتاحة وسياق القرار فقط.
        </p>
      </section>

      <form
        v-if="run.available_actions.includes('operate')"
        class="sim-action-form action-form"
        @submit.prevent="emit('operate', operationValue)"
      >
        <h3>عملية داخل التشغيل</h3>
        <label>
          <span>IDENTITY_MFA</span>
          <select v-model="operationValue" :disabled="pending">
            <option :value="true">ENABLED</option>
            <option :value="false">DISABLED</option>
          </select>
        </label>
        <button type="submit" class="sim-button" :disabled="pending">
          تطبيق SET_CONTROL_STATE
        </button>
      </form>

      <form
        v-if="['COMPLETED', 'STOPPED', 'FAILED'].includes(run.lifecycle) && !run.result_id"
        class="sim-action-form"
        @submit.prevent="
          emit('seal', { outcome: resultOutcome, summary_ar: resultSummary, score: resultScore })
        "
      >
        <h3>ختم Result تاريخية</h3>
        <label
          ><span>Outcome</span
          ><select v-model="resultOutcome" :disabled="pending">
            <option v-for="outcome in outcomes" :key="outcome">{{ outcome }}</option>
          </select></label
        >
        <label
          ><span>التفسير</span><textarea v-model="resultSummary" rows="3" :disabled="pending" />
        </label>
        <label
          ><span>Score</span
          ><input
            v-model.number="resultScore"
            type="number"
            min="0"
            max="100"
            step="0.01"
            :disabled="pending"
        /></label>
        <button type="submit" class="sim-button" :disabled="pending">ختم النتيجة</button>
      </form>
      <div v-else-if="run.result_id" class="sim-sealed-note">
        لهذا التشغيل Result مختومة؛ لا يمكن إعادة كتابة التاريخ.
      </div>
    </template>
    <p v-else class="sim-muted">اختر تشغيلًا لعرض سياق القرار وإجراءاته المتاحة.</p>
  </div>
</template>

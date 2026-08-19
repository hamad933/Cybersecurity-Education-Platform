<script setup lang="ts">
import { computed, ref } from 'vue';
import type { RunItem } from '../types';
import { fieldEntries, runTypeLabel } from '../utils';

const props = defineProps<{ run: RunItem; outcomes: string[] }>();
const emit = defineEmits<{
  action: [action: string];
  seal: [payload: { outcome: string; summary_ar: string; score: number | null }];
}>();

const mode = ref<'runtime' | 'operations'>('runtime');
const outcome = ref('NOT_EVALUATED');
const summary = ref('لم يُطبّق تقييم نهائي بعد؛ تم ختم الحقائق التشغيلية كما هي.');
const score = ref<number | null>(null);
const runtimeFields = computed(() => fieldEntries(props.run.runtime_state, ['telemetry', 'validation']));
const telemetry = computed(() => fieldEntries(props.run.runtime_state.telemetry));
const validation = computed(() => fieldEntries(props.run.runtime_state.validation));

function seal(): void {
  emit('seal', { outcome: outcome.value, summary_ar: summary.value, score: score.value });
}
</script>

<template>
  <section class="surface-panel" data-testid="run-runtime-console">
    <header class="section-heading">
      <div>
        <p class="rail-kicker">Active Run</p>
        <h2>{{ run.definition_title_ar }}</h2>
        <small class="technical technical-id" dir="ltr">{{ run.id }}</small>
      </div>
      <div class="mode-tabs" aria-label="وضع التشغيل">
        <button type="button" :class="{ active: mode === 'runtime' }" @click="mode = 'runtime'">Runtime</button>
        <button
          type="button"
          data-testid="run-operations-mode"
          :class="{ active: mode === 'operations' }"
          @click="mode = 'operations'"
        >Operations</button>
      </div>
    </header>

    <template v-if="mode === 'runtime'">
      <div class="machine-facts">
        <article><small>نوع التشغيل</small><strong class="technical" dir="ltr">{{ runTypeLabel(run.run_type) }}</strong></article>
        <article><small>الحالة</small><strong class="technical" dir="ltr">{{ run.lifecycle }}</strong></article>
        <article><small>Seed</small><strong class="technical" dir="ltr">{{ run.seed }}</strong></article>
        <article><small>Input Digest</small><strong class="technical digest" dir="ltr">{{ run.input_digest }}</strong></article>
      </div>

      <section class="subsurface">
        <p class="rail-kicker">Machine State</p>
        <div v-if="runtimeFields.length" class="field-grid">
          <div v-for="field in runtimeFields" :key="field.key" class="field-cell">
            <small class="technical" dir="ltr">{{ field.key }}</small><strong>{{ field.value }}</strong>
          </div>
        </div>
        <p v-else class="truthful-unavailable">لا توجد حقول Runtime State إضافية مستلمة.</p>
      </section>

      <div class="runtime-columns">
        <article>
          <p class="rail-kicker">Telemetry</p>
          <div v-if="telemetry.length" class="kv-list">
            <div v-for="field in telemetry" :key="field.key">
              <span class="technical" dir="ltr">{{ field.key }}</span><strong>{{ field.value }}</strong>
            </div>
          </div>
          <p v-else class="truthful-unavailable">لا توجد Telemetry منظّمة مستلمة.</p>
        </article>
        <article>
          <p class="rail-kicker">Runtime Validation</p>
          <div v-if="validation.length" class="kv-list">
            <div v-for="field in validation" :key="field.key">
              <span class="technical" dir="ltr">{{ field.key }}</span><strong>{{ field.value }}</strong>
            </div>
          </div>
          <p v-else class="truthful-unavailable">لا توجد Validation منفصلة مستلمة.</p>
        </article>
      </div>
    </template>

    <section v-else class="operations-mode" data-testid="run-operations-panel">
      <div class="operations-header">
        <div><p class="rail-kicker">Operations</p><h3>إجراءات الحالة المصرّح بها لهذا التشغيل</h3></div>
        <span class="technical state-chip" dir="ltr">{{ run.lifecycle }}</span>
      </div>
      <div v-if="run.available_actions.length" class="operation-actions">
        <button v-if="run.available_actions.includes('ready')" type="button" @click="emit('action', 'ready')">اعتماد الجاهزية</button>
        <button v-if="run.available_actions.includes('start')" type="button" @click="emit('action', 'start')">بدء التشغيل</button>
        <button v-if="run.available_actions.includes('pause')" type="button" @click="emit('action', 'pause')">إيقاف مؤقت</button>
        <button v-if="run.available_actions.includes('resume')" type="button" @click="emit('action', 'resume')">استئناف</button>
        <button v-if="run.available_actions.includes('complete')" type="button" @click="emit('action', 'complete')">إكمال المحاكاة الداخلية</button>
        <button v-if="run.available_actions.includes('snapshot')" type="button" @click="emit('action', 'snapshot')">حفظ Snapshot</button>
        <button v-if="run.available_actions.includes('stop')" class="button-muted" type="button" @click="emit('action', 'stop')">إيقاف التشغيل</button>
      </div>
      <p v-else class="truthful-unavailable">لا يعلن الخادم إجراءات انتقال متاحة لهذه الحالة.</p>

      <form v-if="['COMPLETED', 'STOPPED', 'FAILED'].includes(run.lifecycle) && !run.result_id" class="seal-form" @submit.prevent="seal">
        <p class="rail-kicker">Seal Result</p><h3>ختم النتيجة التاريخية</h3>
        <label><span>Outcome</span><select v-model="outcome" class="technical"><option v-for="item in outcomes" :key="item">{{ item }}</option></select></label>
        <label><span>التفسير المسجل</span><textarea v-model="summary" rows="4" maxlength="2000" /></label>
        <label><span>Score — اختياري</span><input v-model.number="score" type="number" min="0" max="100" step="0.01" /></label>
        <button class="primary-action" type="submit">ختم Result</button>
      </form>
      <p v-else-if="run.result_id" class="sealed-note">يوجد Result مختوم لهذا التشغيل؛ لا تعرض Operations إجراءً لإعادة كتابة التاريخ.</p>
    </section>
  </section>
</template>

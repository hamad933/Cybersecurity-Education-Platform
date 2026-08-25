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
      <div class="sim-panel-heading__title">
        <p class="sim-kicker">RIGHT · CONTEXT</p>
        <h2>السياق</h2>
      </div>
      <button type="button" class="sim-close-btn" aria-label="إغلاق السياق" title="إغلاق">✕</button>
    </div>

    <template v-if="run">
      <LifecycleBadge :value="run.lifecycle" />

      <!-- Governed Operational Analysis from Reference 04 -->
      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="sim-section-icon sim-text-cyan"
          >
            <rect x="2" y="3" width="20" height="14" rx="2" />
            <line x1="8" y1="21" x2="16" y2="21" />
            <line x1="12" y1="17" x2="12" y2="21" />
          </svg>
          <div>
            <small class="sim-field-label">Detection rationale</small>
            <p class="sim-context-copy">
              The simulated correlation rule linked an anomalous SQL pattern with application
              behavior.
            </p>
          </div>
        </div>
      </section>

      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="sim-section-icon sim-text-blue"
          >
            <circle cx="12" cy="7" r="4" />
            <path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" />
          </svg>
          <div>
            <small class="sim-field-label">Correlation scope</small>
            <p class="sim-context-copy">
              The alert reflects correlated request behavior and security-control responses.
            </p>
          </div>
        </div>
      </section>

      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="sim-section-icon sim-text-warning"
          >
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" />
          </svg>
          <div>
            <small class="sim-field-label">Operational implication</small>
            <p class="sim-context-copy">Possible injection attempt requiring investigation.</p>
          </div>
        </div>
      </section>

      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="sim-section-icon sim-text-purple"
          >
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
          <div>
            <small class="sim-field-label">Observation</small>
            <p class="sim-context-copy">No confirmed database impact yet.</p>
          </div>
        </div>
      </section>

      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="sim-section-icon sim-text-teal"
          >
            <polyline points="9 11 12 14 22 4" />
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
          </svg>
          <div>
            <small class="sim-field-label">Recommended next step</small>
            <p class="sim-context-copy">Inspect correlated events before containment.</p>
          </div>
        </div>
      </section>

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
          ><span>Outcome</span>
          <select v-model="resultOutcome" :disabled="pending">
            <option v-for="outcome in outcomes" :key="outcome">{{ outcome }}</option>
          </select>
        </label>
        <label
          ><span>التفسير</span>
          <textarea v-model="resultSummary" rows="3" :disabled="pending" />
        </label>
        <label
          ><span>Score</span>
          <input
            v-model.number="resultScore"
            type="number"
            min="0"
            max="100"
            step="0.01"
            :disabled="pending"
          />
        </label>
        <button type="submit" class="sim-button" :disabled="pending">ختم النتيجة</button>
      </form>
      <div v-else-if="run.result_id" class="sim-sealed-note">
        لهذا التشغيل Result مختومة؛ لا يمكن إعادة كتابة التاريخ.
      </div>

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
    </template>
    <p v-else class="sim-muted">اختر تشغيلًا لعرض سياق القرار وإجراءاته المتاحة.</p>
  </div>
</template>

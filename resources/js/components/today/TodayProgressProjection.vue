<script setup lang="ts">
import CepEmptyState from '../shared/CepEmptyState.vue';
import TechnicalText from '../shared/TechnicalText.vue';
import type { OrchestrationNode, TodayProgressProjection } from './types';

defineProps<{
  projection: OrchestrationNode<TodayProgressProjection>;
}>();
</script>

<template>
  <section class="today-section" aria-labelledby="today-progress-title">
    <div class="today-section-header">
      <div>
        <h2 id="today-progress-title" class="cep-section__title" data-today-level="5">
          توقعات التقدم
        </h2>
        <p class="cep-section__desc">التوقع المبني على الأدلة المثبتة للمرحلة الحالية.</p>
      </div>
    </div>

    <div
      v-if="projection.status === 'AVAILABLE' && projection.data"
      class="today-projection-card"
      data-testid="today-projection-active"
    >
      <div class="today-projection-card__header">
        <h3 class="today-projection-card__title">{{ projection.data.milestoneTitle }}</h3>
        <span class="today-projection-count" dir="ltr">
          {{ projection.data.verifiedCount }} / {{ projection.data.totalCount }}
        </span>
      </div>

      <div class="today-projection-card__body">
        <p class="today-projection-status">{{ projection.data.statusSummary }}</p>

        <div v-if="projection.data.evidenceRequirement" class="today-projection-req">
          <svg class="today-req-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
              fill-rule="evenodd"
              d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <span class="today-req-label">متطلب المرحلة:</span>
            <span class="today-req-value">{{ projection.data.evidenceRequirement }}</span>
          </div>
        </div>

        <div v-if="projection.data.targetHorizon" class="today-projection-horizon">
            <span class="today-req-label">الأفق الزمني:</span>
            <span class="today-req-value"><TechnicalText :value="projection.data.targetHorizon" /></span>
        </div>
      </div>

      <div class="today-projection-card__footer">
        <div class="today-law-reminder">
          <svg
            class="today-law-icon"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
              clip-rule="evenodd"
            />
          </svg>
          <span>الإنجاز لا يعني الإتقان (Completion != Mastery)</span>
        </div>
      </div>
    </div>

    <div v-else-if="projection.status === 'UNAVAILABLE'" class="today-empty-wrapper">
      <div class="today-empty-icon-box" aria-hidden="true">
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          class="today-empty-svg"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="توقعات التقدم غير متوفرة"
        description="تعذر الاتصال بمجال التقدم لمعرفة التوقعات."
        data-testid="today-projection-unavailable"
      />
    </div>

    <div v-else-if="projection.status === 'ERROR'" class="today-empty-wrapper">
      <div class="today-empty-icon-box" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05);" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="today-empty-svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="حدث خطأ في جلب البيانات"
        :description="projection.message || 'تعذر تحميل هذه البيانات بسبب خطأ غير معروف.'"
        data-testid="today-error-state"
      >
        <template v-if="projection.diagnosticId">
            <span class="today-diagnostic-id" dir="ltr">{{ projection.diagnosticId }}</span>
        </template>
      </CepEmptyState>
    </div>

    <div v-else-if="projection.status === 'STALE'" class="today-empty-wrapper">
      <div class="today-empty-icon-box" style="color: #f59e0b; border-color: rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.05);" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="today-empty-svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="البيانات غير محدّثة (قديمة)"
        :description="projection.message || 'هذه البيانات قديمة ولم نتمكن من تحديثها الآن ولا توجد نسخة محفوظة صالحة للعرض.'"
        data-testid="today-stale-empty-state"
      >
        <template v-if="projection.observedAt">
            <span class="today-stale-time" dir="ltr">{{ projection.observedAt }}</span>
            <span v-if="projection.freshUntil" class="today-stale-time today-stale-time--until" dir="ltr" style="margin-right: 0.5rem; color: #9ca3af;">until: {{ projection.freshUntil }}</span>
        </template>
      </CepEmptyState>
    </div>

    <div v-else class="today-empty-wrapper">
      <div class="today-empty-icon-box" aria-hidden="true">
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          class="today-empty-svg"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125z"
          />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="لا توجد توقعات حالية"
        description="توقعات التقدم تتطلب الشروع في مسار معرفي مستهدف. الإنجاز لا يعني الإتقان (Completion != Mastery)."
        data-testid="today-projection-empty"
      />
    </div>
  </section>
</template>

<style scoped>
.today-section {
  scroll-margin-top: 6.5rem;
}

.today-section-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 0.75rem;
}

.today-projection-card {
  display: grid;
  gap: 0.95rem;
  margin-top: 0.9rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel);
  padding: 1.25rem;
}

.today-projection-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.today-projection-card__title {
  margin: 0;
  color: var(--cep-text);
  font-size: 1.05rem;
  font-weight: 780;
}

.today-projection-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 3.5rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel-strong);
  padding: 0.35rem 0.65rem;
  color: var(--cep-accent);
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  box-shadow: inset 0 0 0 1px var(--cep-border-strong);
}

.today-projection-status {
  margin: 0;
  color: var(--cep-text-muted);
  font-size: 0.88rem;
  line-height: 1.6;
}

.today-projection-req {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  margin-top: 0.85rem;
  border-radius: var(--cep-radius-sm);
  background: rgba(34, 211, 238, 0.05);
  padding: 0.75rem;
  border: 1px solid rgba(34, 211, 238, 0.15);
}

.today-projection-horizon {
    margin-top: 0.5rem;
    padding-left: 0.5rem;
}

.today-req-icon {
  width: 1.1rem;
  height: 1.1rem;
  flex: 0 0 1.1rem;
  color: var(--cep-accent);
  margin-top: 0.15rem;
}

.today-req-label {
  display: block;
  color: var(--cep-text-muted);
  font-size: 0.76rem;
  font-weight: 700;
}

.today-req-value {
  display: block;
  margin-top: 0.15rem;
  color: var(--cep-accent);
  font-size: 0.86rem;
  font-weight: 750;
  line-height: 1.4;
}

.today-projection-card__footer {
  margin-top: 0.5rem;
  border-top: 1px dashed var(--cep-border-strong);
  padding-top: 0.85rem;
}

.today-law-reminder {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #10b981;
  font-size: 0.76rem;
  font-weight: 750;
}

.today-law-icon {
  width: 0.85rem;
  height: 0.85rem;
}

.today-empty-wrapper {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-top: 0.9rem;
  border: 1px dashed var(--cep-border-strong);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1.25rem;
}

.today-empty-icon-box {
  display: grid;
  flex: 0 0 2.75rem;
  width: 2.75rem;
  height: 2.75rem;
  place-items: center;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-canvas);
  color: var(--cep-accent);
}

.today-empty-svg {
  width: 1.35rem;
  height: 1.35rem;
}

.today-empty-content {
  flex: 1;
  min-width: 0;
  border: none !important;
  background: transparent !important;
  padding: 0 !important;
}

.today-diagnostic-id {
  display: inline-block;
  margin-top: 0.5rem;
  font-family: monospace;
  font-size: 0.8rem;
  color: #ef4444;
}

.today-stale-time {
  display: inline-block;
  margin-top: 0.5rem;
  font-size: 0.8rem;
  color: #f59e0b;
}

@media (max-width: 48rem) {
  .today-empty-wrapper {
    flex-direction: column;
  }
}
</style>

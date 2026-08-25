<script setup lang="ts">
import CepEmptyState from '../shared/CepEmptyState.vue';
import TechnicalText from '../shared/TechnicalText.vue';
import type { TodayProgressProjection } from './types';

defineProps<{
  projection?: TodayProgressProjection | null;
}>();
</script>

<template>
  <section
    id="progress-projection"
    class="cep-section today-section"
    aria-labelledby="progress-projection-title"
    data-today-level="6"
  >
    <p class="cep-kicker">المسار المرحلي الحقيقي</p>
    <h2 id="progress-projection-title" class="cep-section-title">التوقعات المرحلية</h2>

    <div v-if="projection" class="today-projection-card" data-testid="today-projection-active">
      <div class="today-projection-card__header">
        <h3 class="today-projection-card__title">{{ projection.milestoneTitle }}</h3>
        <span v-if="projection.targetHorizon" class="today-horizon-pill">
          الأفق: <TechnicalText :value="projection.targetHorizon" />
        </span>
      </div>

      <div class="today-projection-card__body">
        <p class="today-projection-summary">{{ projection.statusSummary }}</p>

        <dl class="cep-fact-list">
          <div class="cep-fact-list__row">
            <dt>الوحدات المعرفية المحققة بأدلة</dt>
            <dd>
              <TechnicalText :value="`${projection.verifiedCount} / ${projection.totalCount}`" />
            </dd>
          </div>
          <div v-if="projection.evidenceRequirement" class="cep-fact-list__row">
            <dt>متطلب الإثبات القادم</dt>
            <dd>{{ projection.evidenceRequirement }}</dd>
          </div>
        </dl>
      </div>

      <div class="today-projection-card__notice">
        <p class="today-law-note">
          * الإنجاز لا يساوي الإتقان (Completion != Mastery). تقاس الكفاءة بالأدلة المثبتة، ولا
          نستخدم نسبًا مئوية افتراضية.
        </p>
      </div>
    </div>

    <CepEmptyState
      v-else
      class="cep-section__body"
      title="التوقعات المرحلية تتطلب أدلة تقييم مثبتة"
      description="لا تُعرض تقديرات التقدم إلا بناءً على أدلة تقييم محققة في مساحة التقدم والأدلة، دون استخدام نسب مئوية تقديرية أو أشرطة تقدم افتراضية."
      data-testid="today-projection-empty"
    />
  </section>
</template>

<style scoped>
.today-section {
  scroll-margin-top: 6.5rem;
}

.today-projection-card {
  display: grid;
  gap: 0.85rem;
  margin-top: 0.9rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1.15rem;
}

.today-projection-card__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.today-projection-card__title {
  margin: 0;
  color: var(--cep-text);
  font-size: 1.05rem;
  font-weight: 760;
}

.today-horizon-pill {
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
  padding: 0.15rem 0.5rem;
  color: var(--cep-accent);
  font-size: 0.74rem;
  font-weight: 700;
}

.today-projection-summary {
  margin: 0;
  color: var(--cep-text-muted);
  font-size: 0.86rem;
  line-height: 1.7;
}

.today-projection-card__notice {
  padding-top: 0.65rem;
  border-top: 1px dashed var(--cep-border);
}

.today-law-note {
  margin: 0;
  color: var(--cep-text-muted);
  font-size: 0.76rem;
  line-height: 1.6;
}
</style>

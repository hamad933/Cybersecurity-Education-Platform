<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import CepEmptyState from '../shared/CepEmptyState.vue';
import TechnicalText from '../shared/TechnicalText.vue';
import type { TodayNextActionItem } from './types';

defineProps<{
  action?: TodayNextActionItem | null;
}>();
</script>

<template>
  <section
    id="next-action"
    class="cep-section today-section"
    aria-labelledby="next-action-title"
    data-today-level="2"
  >
    <p class="cep-kicker">التوصية الموجهة</p>
    <h2 id="next-action-title" class="cep-section-title">الإجراء التالي الموصى به</h2>

    <div v-if="action" class="today-action-card" data-testid="today-next-action-active">
      <div class="today-action-card__header">
        <span class="today-domain-tag">{{ action.domainLabel }}</span>
        <div class="today-action-card__meta">
          <span v-if="action.timeCommitment" class="today-meta-pill">
            المدة المقدرة: <TechnicalText :value="action.timeCommitment" />
          </span>
          <span v-if="action.difficulty" class="today-meta-pill">
            المستوى: <TechnicalText :value="action.difficulty" />
          </span>
        </div>
      </div>

      <div class="today-action-card__body">
        <h3 class="today-action-card__title">{{ action.title }}</h3>
        <p class="today-action-card__desc">{{ action.description }}</p>
      </div>

      <div class="today-action-card__footer">
        <Link
          :href="action.href"
          class="cep-text-button today-action-button focus-ring"
          data-testid="today-next-action-link"
        >
          {{ action.actionLabel || 'بدء الإجراء الموصى به' }} ◀
        </Link>
      </div>
    </div>

    <CepEmptyState
      v-else
      class="cep-section__body"
      title="لا توجد توصية مجدولة حاليًا"
      description="لا يتوفر إجراء تالٍ موصى به في الوقت الراهن. تتاح التوصيات فور استيفاء متطلبات المسار المعرفي والتقييمات."
      data-testid="today-next-action-empty"
    />
  </section>
</template>

<style scoped>
.today-section {
  scroll-margin-top: 6.5rem;
}

.today-action-card {
  display: grid;
  gap: 0.85rem;
  margin-top: 0.9rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1.15rem;
}

.today-action-card__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.today-domain-tag {
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent-soft);
  padding: 0.15rem 0.5rem;
  color: var(--cep-accent);
  font-size: 0.76rem;
  font-weight: 700;
}

.today-action-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.today-meta-pill {
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
  padding: 0.15rem 0.45rem;
  color: var(--cep-text-muted);
  font-size: 0.74rem;
}

.today-action-card__title {
  margin: 0;
  color: var(--cep-text);
  font-size: 1.05rem;
  font-weight: 760;
}

.today-action-card__desc {
  margin: 0.4rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.86rem;
  line-height: 1.7;
}

.today-action-card__footer {
  margin-top: 0.25rem;
}

.today-action-button {
  background: var(--cep-bg-panel);
  color: var(--cep-accent);
  font-weight: 750;
  text-decoration: none;
}

.today-action-button:hover {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
}
</style>

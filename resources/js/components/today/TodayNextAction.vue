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
    <div class="today-section-header">
      <div>
        <p class="cep-kicker">التوصية الموجهة</p>
        <h2 id="next-action-title" class="cep-section-title">الإجراء التالي الموصى به</h2>
      </div>
      <span v-if="action" class="today-recommendation-rank">
        <svg class="today-rank-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path
            fill-rule="evenodd"
            d="M10 1a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 1zm0 15a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 16zm9-6a.75.75 0 01-.75.75h-1.5a.75.75 0 010-1.5h1.5A.75.75 0 0119 10zM5 10a.75.75 0 01-.75.75h-1.5a.75.75 0 010-1.5h1.5A.75.75 0 015 10zm11.364-5.364a.75.75 0 010 1.06l-1.06 1.06a.75.75 0 01-1.06-1.06l1.06-1.06a.75.75 0 011.06 0zM6.757 13.243a.75.75 0 010 1.06l-1.06 1.06a.75.75 0 11-1.06-1.06l1.06-1.06a.75.75 0 011.06 0zm0-7.486a.75.75 0 011.06 0l1.06 1.06a.75.75 0 11-1.06 1.06l-1.06-1.06a.75.75 0 010-1.06zm7.486 7.486a.75.75 0 011.06 0l1.06 1.06a.75.75 0 01-1.06 1.06l-1.06-1.06a.75.75 0 010-1.06z"
            clip-rule="evenodd"
          />
        </svg>
        أولوية المسار الحالي
      </span>
    </div>

    <div v-if="action" class="today-action-card" data-testid="today-next-action-active">
      <div class="today-action-card__header">
        <div class="today-action-card__domain-group">
          <span class="today-domain-tag">
            <svg class="today-tag-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path
                d="M10 2a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 2zM4.75 4a.75.75 0 000 1.5h10.5a.75.75 0 000-1.5H4.75zM3 8a2 2 0 012-2h10a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm2 0v7h10V8H5z"
              />
            </svg>
            {{ action.domainLabel }}
          </span>
        </div>

        <div class="today-action-card__meta">
          <span v-if="action.timeCommitment" class="today-meta-pill">
            <svg class="today-pill-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z"
                clip-rule="evenodd"
              />
            </svg>
            المدة المقدرة: <TechnicalText :value="action.timeCommitment" />
          </span>
          <span v-if="action.difficulty" class="today-meta-pill today-meta-pill--difficulty">
            <svg class="today-pill-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path
                fill-rule="evenodd"
                d="M12.315 2c2.43 0 4.29 2.01 4.29 4.43 0 3.01-2.93 5.75-6.605 8.91L10 15.34l-.605-.53C5.72 11.65 2.79 8.91 2.79 5.9 2.79 3.48 4.65 2 7.08 2c1.38 0 2.71.65 3.525 1.68C11.415 2.65 12.745 2 12.315 2z"
                clip-rule="evenodd"
              />
            </svg>
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
          <span>{{ action.actionLabel || 'بدء الإجراء الموصى به' }}</span>
          <span class="today-action-btn-arrow" aria-hidden="true">◀</span>
        </Link>
      </div>
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
            d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"
          />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="لا توجد توصية مجدولة حاليًا"
        description="لا يتوفر إجراء تالٍ موصى به في الوقت الراهن. تتاح التوصيات فور استيفاء متطلبات المسار المعرفي والتقييمات."
        data-testid="today-next-action-empty"
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

.today-recommendation-rank {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel-strong);
  padding: 0.22rem 0.55rem;
  color: var(--cep-text-muted);
  font-size: 0.74rem;
  font-weight: 700;
}

.today-rank-icon {
  width: 0.85rem;
  height: 0.85rem;
  color: var(--cep-accent);
}

.today-action-card {
  display: grid;
  gap: 0.95rem;
  margin-top: 0.9rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1.25rem;
  box-shadow: var(--cep-shadow);
  transition: border-color 150ms ease;
}

.today-action-card:hover {
  border-color: rgba(34, 211, 238, 0.3);
}

.today-action-card__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.65rem;
}

.today-domain-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1px solid rgba(34, 211, 238, 0.25);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent-soft);
  padding: 0.2rem 0.55rem;
  color: var(--cep-accent);
  font-size: 0.76rem;
  font-weight: 750;
}

.today-tag-icon {
  width: 0.8rem;
  height: 0.8rem;
}

.today-action-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
}

.today-meta-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
  padding: 0.18rem 0.55rem;
  color: var(--cep-text-muted);
  font-size: 0.74rem;
}

.today-meta-pill--difficulty {
  border-color: rgba(34, 211, 238, 0.2);
}

.today-pill-icon {
  width: 0.75rem;
  height: 0.75rem;
  color: var(--cep-accent);
}

.today-action-card__title {
  margin: 0;
  color: var(--cep-text);
  font-size: 1.1rem;
  font-weight: 780;
  line-height: 1.4;
}

.today-action-card__desc {
  margin: 0.45rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.88rem;
  line-height: 1.75;
}

.today-action-card__footer {
  display: flex;
  align-items: center;
  margin-top: 0.35rem;
  padding-top: 0.65rem;
  border-top: 1px solid var(--cep-border);
}

.today-action-button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border: 1px solid var(--cep-border-strong);
  background: var(--cep-bg-panel);
  padding: 0.6rem 1.15rem;
  color: var(--cep-accent);
  font-weight: 780;
  text-decoration: none;
  transition:
    border-color 150ms ease,
    background-color 150ms ease,
    transform 150ms ease;
}

.today-action-button:hover {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
  transform: translateY(-1px);
}

.today-action-btn-arrow {
  display: inline-block;
  transition: transform 150ms ease;
}

.today-action-button:hover .today-action-btn-arrow {
  transform: translateX(-3px);
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

@media (max-width: 48rem) {
  .today-action-card__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .today-action-button {
    width: 100%;
    justify-content: center;
  }

  .today-empty-wrapper {
    flex-direction: column;
  }
}
</style>

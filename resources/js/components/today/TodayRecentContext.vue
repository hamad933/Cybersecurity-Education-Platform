<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import CepEmptyState from '../shared/CepEmptyState.vue';
import TechnicalText from '../shared/TechnicalText.vue';
import type { TodayRecentContextItem } from './types';

defineProps<{
  items?: TodayRecentContextItem[] | null;
}>();
</script>

<template>
  <section
    id="recent-context"
    class="cep-section today-section"
    aria-labelledby="recent-context-title"
    data-today-level="5"
  >
    <p class="cep-kicker">السجل والنشاط</p>
    <h2 id="recent-context-title" class="cep-section-title">السياق والنشاط الحديث</h2>

    <div
      v-if="items && items.length > 0"
      class="today-recent-stack"
      data-testid="today-recent-list"
    >
      <article v-for="item in items" :key="item.id" class="today-recent-card">
        <div class="today-recent-card__meta">
          <span class="today-recent-domain">{{ item.domainLabel }}</span>
          <span class="today-recent-time">
            <TechnicalText :value="item.timestamp" />
          </span>
        </div>

        <div class="today-recent-card__body">
          <h3 class="today-recent-card__title">{{ item.title }}</h3>
          <p class="today-recent-card__summary">{{ item.summary }}</p>
        </div>

        <div class="today-recent-card__footer">
          <Link :href="item.href" class="today-recent-link focus-ring">
            عرض التفاصيل في مساحة العمل ◀
          </Link>
        </div>
      </article>
    </div>

    <CepEmptyState
      v-else
      class="cep-section__body"
      title="لا يوجد سجل نشاط حديث مسجل"
      description="لا يتلقى هذا السطح حاليًا أحداثًا مسجلة من مساحات العمل. ستظهر هنا السجلات والأنشطة فور توثيقها من المجالات الأساسية."
      data-testid="today-recent-empty"
    />
  </section>
</template>

<style scoped>
.today-section {
  scroll-margin-top: 6.5rem;
}

.today-recent-stack {
  display: grid;
  gap: 0.75rem;
  margin-top: 0.9rem;
}

.today-recent-card {
  display: grid;
  gap: 0.65rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1rem;
}

.today-recent-card__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.today-recent-domain {
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent-soft);
  padding: 0.15rem 0.45rem;
  color: var(--cep-accent);
  font-size: 0.76rem;
  font-weight: 700;
}

.today-recent-time {
  color: var(--cep-text-muted);
  font-size: 0.78rem;
}

.today-recent-card__title {
  margin: 0;
  color: var(--cep-text);
  font-size: 0.95rem;
  font-weight: 750;
}

.today-recent-card__summary {
  margin: 0.25rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.84rem;
  line-height: 1.7;
}

.today-recent-link {
  color: var(--cep-accent);
  font-size: 0.8rem;
  font-weight: 750;
  text-decoration: none;
}

.today-recent-link:hover {
  text-decoration: underline;
}
</style>

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
    <div class="today-section-header">
      <div>
        <p class="cep-kicker">السجل والنشاط</p>
        <h2 id="recent-context-title" class="cep-section-title">السياق والنشاط الحديث</h2>
      </div>
      <span v-if="items && items.length > 0" class="today-recent-badge">
        <svg class="today-recent-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z"
            clip-rule="evenodd"
          />
        </svg>
        سجل الأحداث الأخيرة
      </span>
    </div>

    <div
      v-if="items && items.length > 0"
      class="today-recent-stack"
      data-testid="today-recent-list"
    >
      <div class="today-timeline-rail">
        <article v-for="item in items" :key="item.id" class="today-recent-card">
          <div class="today-timeline-dot" aria-hidden="true" />

          <div class="today-recent-card__inner">
            <div class="today-recent-card__meta">
              <span class="today-recent-domain">
                <svg
                  class="today-domain-mini-icon"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                  aria-hidden="true"
                >
                  <path
                    fill-rule="evenodd"
                    d="M2 4.75C2 3.784 2.784 3 3.75 3h12.5c.966 0 1.75.784 1.75 1.75v10.5A1.75 1.75 0 0116.25 17H3.75A1.75 1.75 0 012 15.25V4.75zm1.75-.25a.25.25 0 00-.25.25v10.5c0 .138.112.25.25.25h12.5a.25.25 0 00.25-.25V4.75a.25.25 0 00-.25-.25H3.75z"
                    clip-rule="evenodd"
                  />
                </svg>
                {{ item.domainLabel }}
              </span>
              <span class="today-recent-time">
                <svg
                  class="today-time-mini-icon"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                  aria-hidden="true"
                >
                  <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z"
                    clip-rule="evenodd"
                  />
                </svg>
                <TechnicalText :value="item.timestamp" />
              </span>
            </div>

            <div class="today-recent-card__body">
              <h3 class="today-recent-card__title">{{ item.title }}</h3>
              <p class="today-recent-card__summary">{{ item.summary }}</p>
            </div>

            <div class="today-recent-card__footer">
              <Link :href="item.href" class="today-recent-link focus-ring">
                <span>عرض التفاصيل في مساحة العمل</span>
                <span class="today-link-arrow" aria-hidden="true">◀</span>
              </Link>
            </div>
          </div>
        </article>
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
            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
          />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="لا يوجد سجل نشاط حديث مسجل"
        description="لا يتلقى هذا السطح حاليًا أحداثًا مسجلة من مساحات العمل. ستظهر هنا السجلات والأنشطة فور توثيقها من المجالات الأساسية."
        data-testid="today-recent-empty"
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

.today-recent-badge {
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

.today-recent-icon {
  width: 0.85rem;
  height: 0.85rem;
  color: var(--cep-accent);
}

.today-recent-stack {
  margin-top: 0.9rem;
}

.today-timeline-rail {
  position: relative;
  display: grid;
  gap: 0.85rem;
  padding-inline-start: 1rem;
}

.today-timeline-rail::before {
  content: '';
  position: absolute;
  top: 0.75rem;
  bottom: 0.75rem;
  inset-inline-start: 0.25rem;
  width: 2px;
  background: var(--cep-border-strong);
}

.today-recent-card {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.today-timeline-dot {
  position: absolute;
  top: 1.25rem;
  inset-inline-start: -1rem;
  width: 0.6rem;
  height: 0.6rem;
  border-radius: 50%;
  border: 2px solid var(--cep-bg-panel);
  background: var(--cep-accent);
  box-shadow: 0 0 6px var(--cep-accent);
}

.today-recent-card__inner {
  flex: 1;
  display: grid;
  gap: 0.65rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1.15rem;
  box-shadow: var(--cep-shadow);
  transition:
    transform 150ms ease,
    border-color 150ms ease;
}

.today-recent-card__inner:hover {
  border-color: rgba(34, 211, 238, 0.3);
  transform: translateY(-1px);
}

.today-recent-card__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.today-recent-domain {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1px solid rgba(34, 211, 238, 0.25);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent-soft);
  padding: 0.18rem 0.5rem;
  color: var(--cep-accent);
  font-size: 0.76rem;
  font-weight: 750;
}

.today-domain-mini-icon,
.today-time-mini-icon {
  width: 0.75rem;
  height: 0.75rem;
}

.today-recent-time {
  color: var(--cep-text-muted);
  font-size: 0.78rem;
}

.today-recent-card__title {
  margin: 0;
  color: var(--cep-text);
  font-size: 1rem;
  font-weight: 780;
  line-height: 1.35;
}

.today-recent-card__summary {
  margin: 0.3rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.86rem;
  line-height: 1.75;
}

.today-recent-card__footer {
  margin-top: 0.35rem;
  padding-top: 0.55rem;
  border-top: 1px solid var(--cep-border);
}

.today-recent-link {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  color: var(--cep-accent);
  font-size: 0.82rem;
  font-weight: 750;
  text-decoration: none;
  transition:
    color 150ms ease,
    transform 150ms ease;
}

.today-recent-link:hover {
  color: var(--cep-accent-hover);
}

.today-link-arrow {
  display: inline-block;
  transition: transform 150ms ease;
}

.today-recent-link:hover .today-link-arrow {
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
  .today-timeline-rail {
    padding-inline-start: 0;
  }

  .today-timeline-rail::before,
  .today-timeline-dot {
    display: none;
  }

  .today-empty-wrapper {
    flex-direction: column;
  }
}
</style>

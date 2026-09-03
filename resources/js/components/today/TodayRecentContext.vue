<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CepEmptyState from '../shared/CepEmptyState.vue';
import type { OrchestrationNode, TodayRecentContextItem } from './types';

defineProps<{
  items: OrchestrationNode<TodayRecentContextItem[]>;
}>();
</script>

<template>
  <section class="today-section" aria-labelledby="today-recent-title">
    <div class="today-section-header">
      <div>
        <h2 id="today-recent-title" class="cep-section__title" data-today-level="4">
          السياق الأخير
        </h2>
        <p class="cep-section__desc">أحدث التطورات أو التحديثات في مساحات عملك.</p>
      </div>
    </div>

    <ul
      v-if="items.status === 'AVAILABLE' && items.data && items.data.length > 0"
      class="today-recent-list"
      data-testid="today-recent-list"
    >
      <li v-for="item in items.data" :key="item.id" class="today-recent-item">
        <div class="today-recent-item__header">
          <span class="today-recent-domain">{{ item.domainLabel }}</span>
          <span class="today-recent-time" dir="ltr">{{ item.timestamp }}</span>
        </div>
        <h3 class="today-recent-item__title">
          <Link :href="item.href" class="today-recent-item__link focus-ring">
            {{ item.title }}
          </Link>
        </h3>
        <p class="today-recent-item__summary">{{ item.summary }}</p>
      </li>
    </ul>

    <div v-else-if="items.status === 'UNAVAILABLE'" class="today-empty-wrapper">
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
        title="السياق الأخير غير متوفر"
        description="تعذر الاتصال بالمجال لمعرفة أحدث التطورات."
        data-testid="today-recent-unavailable"
      />
    </div>

    <div v-else-if="items.status === 'ERROR'" class="today-empty-wrapper">
      <div class="today-empty-icon-box" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05);" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="today-empty-svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="حدث خطأ في جلب البيانات"
        :description="items.message || 'تعذر تحميل هذه البيانات بسبب خطأ غير معروف.'"
        data-testid="today-error-state"
      >
        <template v-if="items.diagnosticId">
            <span class="today-diagnostic-id" dir="ltr">{{ items.diagnosticId }}</span>
        </template>
      </CepEmptyState>
    </div>

    <div v-else-if="items.status === 'STALE'" class="today-empty-wrapper">
      <div class="today-empty-icon-box" style="color: #f59e0b; border-color: rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.05);" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="today-empty-svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="البيانات غير محدّثة (قديمة)"
        :description="items.message || 'هذه البيانات قديمة ولم نتمكن من تحديثها الآن ولا توجد نسخة محفوظة صالحة للعرض.'"
        data-testid="today-stale-empty-state"
      >
        <template v-if="items.observedAt">
            <span class="today-stale-time" dir="ltr">{{ items.observedAt }}</span>
            <span v-if="items.freshUntil" class="today-stale-time today-stale-time--until" dir="ltr" style="margin-right: 0.5rem; color: #9ca3af;">until: {{ items.freshUntil }}</span>
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
            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
          />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="لا توجد أحداث سياقية حديثة"
        description="لم يتم رصد أحداث أو تغييرات حديثة في مساحات عملك حتى الآن."
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

.today-recent-list {
  display: grid;
  gap: 0.75rem;
  margin: 0.9rem 0 0;
  padding: 0;
  list-style: none;
}

.today-recent-item {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel);
  padding: 1rem;
  transition:
    border-color 150ms ease,
    background-color 150ms ease;
}

.today-recent-item:hover {
  border-color: var(--cep-border-strong);
  background: var(--cep-bg-panel-strong);
}

.today-recent-item__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.today-recent-domain {
  color: var(--cep-accent);
  font-size: 0.76rem;
  font-weight: 750;
}

.today-recent-time {
  color: var(--cep-text-muted);
  font-size: 0.76rem;
}

.today-recent-item__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 750;
  line-height: 1.4;
}

.today-recent-item__link {
  color: var(--cep-text);
  text-decoration: none;
}

.today-recent-item__link:hover {
  text-decoration: underline;
}

.today-recent-item__link::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.today-recent-item__summary {
  margin: 0.2rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.88rem;
  line-height: 1.6;
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

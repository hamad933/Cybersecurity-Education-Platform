<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import CepEmptyState from '../shared/CepEmptyState.vue';
import type { TodayAttentionItem } from './types';

defineProps<{
  items?: TodayAttentionItem[] | null;
}>();
</script>

<template>
  <section
    id="attention-items"
    class="cep-section today-section"
    aria-labelledby="attention-title"
    data-today-level="4"
  >
    <p class="cep-kicker">متطلبات التدخل والمراجعة</p>
    <h2 id="attention-title" class="cep-section-title">ما يحتاج انتباهك</h2>

    <div
      v-if="items && items.length > 0"
      class="today-attention-stack"
      data-testid="today-attention-list"
    >
      <article
        v-for="item in items"
        :key="item.id"
        class="today-attention-card"
        :class="`today-attention-card--${item.severity}`"
      >
        <div class="today-attention-card__header">
          <span class="today-severity-badge" :class="`today-severity-badge--${item.severity}`">
            {{
              item.severity === 'urgent'
                ? 'عاجل'
                : item.severity === 'warning'
                  ? 'مراجعة مطلوبة'
                  : 'ملاحظة'
            }}
          </span>
          <span class="today-domain-label">{{ item.domainLabel }}</span>
        </div>

        <div class="today-attention-card__body">
          <h3 class="today-attention-card__title">{{ item.title }}</h3>
          <p class="today-attention-card__reason">{{ item.reason }}</p>
        </div>

        <div class="today-attention-card__footer">
          <Link :href="item.href" class="cep-text-button today-attention-action focus-ring">
            {{ item.actionLabel || 'معالجة البند في مساحة العمل' }} ◀
          </Link>
        </div>
      </article>
    </div>

    <CepEmptyState
      v-else
      class="cep-section__body"
      title="لا توجد بنود عالقة أو متطلبات مراجعة حاليًا"
      description="لا يتلقى هذا السطح حاليًا تنبيهات حظر أو طلبات مراجعة معلقة. كافة مساراتك تعمل دون عوائق مسجلة."
      data-testid="today-attention-empty"
    />
  </section>
</template>

<style scoped>
.today-section {
  scroll-margin-top: 6.5rem;
}

.today-attention-stack {
  display: grid;
  gap: 0.75rem;
  margin-top: 0.9rem;
}

.today-attention-card {
  display: grid;
  gap: 0.75rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1.1rem;
}

.today-attention-card--urgent {
  border-color: #f43f5e;
  background: rgb(244 63 94 / 0.08);
}

.today-attention-card--warning {
  border-color: #f59e0b;
  background: rgb(245 158 11 / 0.08);
}

.today-attention-card__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.today-severity-badge {
  border-radius: var(--cep-radius-sm);
  padding: 0.15rem 0.5rem;
  font-size: 0.74rem;
  font-weight: 750;
}

.today-severity-badge--urgent {
  background: #f43f5e;
  color: #ffffff;
}

.today-severity-badge--warning {
  background: #f59e0b;
  color: #020914;
}

.today-severity-badge--info {
  background: var(--cep-accent-soft);
  color: var(--cep-accent);
}

.today-domain-label {
  color: var(--cep-text-muted);
  font-size: 0.78rem;
}

.today-attention-card__title {
  margin: 0;
  color: var(--cep-text);
  font-size: 1rem;
  font-weight: 750;
}

.today-attention-card__reason {
  margin: 0.35rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.85rem;
  line-height: 1.7;
}

.today-attention-action {
  background: var(--cep-bg-panel);
  text-decoration: none;
}

.today-attention-action:hover {
  border-color: var(--cep-border-strong);
}
</style>

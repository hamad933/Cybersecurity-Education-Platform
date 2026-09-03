<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CepEmptyState from '../shared/CepEmptyState.vue';
import type { OrchestrationNode, TodayAttentionItem } from './types';

defineProps<{
  items: OrchestrationNode<TodayAttentionItem[]>;
}>();
</script>

<template>
  <section class="today-context-card" aria-labelledby="today-attention-title">
    <div class="today-context-card__header">
      <svg
        class="today-context-card__icon"
        viewBox="0 0 20 20"
        fill="currentColor"
        aria-hidden="true"
      >
        <path
          d="M10 2a.75.75 0 01.75.75v5.59l1.95-2.1a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 111.1-1.02l1.95 2.1V2.75A.75.75 0 0110 2zM3 15a.75.75 0 01.75-.75h12.5a.75.75 0 010 1.5H3.75A.75.75 0 013 15z"
        />
      </svg>
      <div>
        <div class="today-attention-header-row">
          <h2 id="today-attention-title" class="cep-context-title" data-today-level="3">بنود الانتباه</h2>
        </div>
        <p class="cep-context-copy">متطلبات وإجراءات فورية تستدعي تدخلك المباشر.</p>
      </div>
    </div>

    <ul
      v-if="items.status === 'AVAILABLE' && items.data && items.data.length > 0"
      class="today-attention-list"
      data-testid="today-attention-list"
    >
      <li
        v-for="item in items.data"
        :key="item.id"
        class="today-attention-item"
        :class="`today-attention-item--${item.severity}`"
      >
        <div class="today-attention-item__main">
          <h3 class="today-attention-item__title">
            <Link :href="item.href" class="today-attention-item__link focus-ring">
              {{ item.title }}
            </Link>
          </h3>
          <p class="today-attention-item__reason">{{ item.reason }}</p>
        </div>
        <div class="today-attention-item__meta">
          <span class="today-attention-severity">
            <svg
              class="today-severity-icon"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.75a.75.75 0 00-1.5 0v5a.75.75 0 001.5 0v-5zm-.75 8a1 1 0 100-2 1 1 0 000 2z"
                clip-rule="evenodd"
              />
            </svg>
            {{
              item.severity === 'urgent' ? 'عاجل' : item.severity === 'warning' ? 'تحذير' : 'معلومة'
            }}
          </span>
          <span class="today-attention-domain">{{ item.domainLabel }}</span>
        </div>
      </li>
    </ul>

    <div v-else-if="items.status === 'UNAVAILABLE'" class="today-empty-inline">
      <CepEmptyState
        class="today-empty-inline-state"
        title="البيانات غير متوفرة"
        description="تعذر تحميل بنود الانتباه حالياً."
        data-testid="today-attention-unavailable"
      />
    </div>

    <div v-else-if="items.status === 'ERROR'" class="today-empty-inline">
      <CepEmptyState
        class="today-empty-inline-state"
        title="حدث خطأ في جلب البيانات"
        :description="items.message || 'تعذر تحميل هذه البيانات بسبب خطأ غير معروف.'"
        data-testid="today-error-state"
      >
        <template v-if="items.diagnosticId">
            <span class="today-diagnostic-id" dir="ltr">{{ items.diagnosticId }}</span>
        </template>
      </CepEmptyState>
    </div>

    <div v-else-if="items.status === 'STALE'" class="today-empty-inline">
      <ul v-if="items.data && items.data.length > 0" class="today-attention-list" data-testid="today-attention-list">
        <li v-for="item in items.data" :key="item.id" class="today-attention-item" :class="`today-attention-item--${item.severity}`">
            <div class="today-attention-item__main">
              <h3 class="today-attention-item__title">
                <Link :href="item.href" class="today-attention-item__link focus-ring">
                  {{ item.title }}
                </Link>
              </h3>
              <p class="today-attention-item__reason">{{ item.reason }}</p>
            </div>
            <div class="today-attention-item__meta">
              <span class="today-attention-severity">
                <svg
                  class="today-severity-icon"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                  aria-hidden="true"
                >
                  <path
                    fill-rule="evenodd"
                    d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.75a.75.75 0 00-1.5 0v5a.75.75 0 001.5 0v-5zm-.75 8a1 1 0 100-2 1 1 0 000 2z"
                    clip-rule="evenodd"
                  />
                </svg>
                {{
                  item.severity === 'urgent' ? 'عاجل' : item.severity === 'warning' ? 'تحذير' : 'معلومة'
                }}
              </span>
              <span class="today-attention-domain">{{ item.domainLabel }}</span>
            </div>
        </li>
      </ul>
      <CepEmptyState
        v-else
        class="today-empty-inline-state"
        title="البيانات غير محدّثة (قديمة)"
        :description="items.message || 'هذه البيانات قديمة ولم نتمكن من تحديثها الآن ولا توجد نسخة محفوظة صالحة للعرض.'"
        data-testid="today-stale-empty-state"
      >
        <template v-if="items.observedAt">
            <span class="today-stale-time" dir="ltr">{{ items.observedAt }}</span>
            <span v-if="items.freshUntil" class="today-stale-time today-stale-time--until" dir="ltr" style="margin-right: 0.5rem; color: #9ca3af;">until: {{ items.freshUntil }}</span>
        </template>
      </CepEmptyState>
      <div v-if="items.data && items.data.length > 0" class="today-stale-badge-container">
        <span class="today-stale-badge" data-testid="today-stale-badge">
            <svg class="today-stale-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.75a.75.75 0 00-1.5 0v5a.75.75 0 001.5 0v-5zm-.75 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
            </svg>
            قديمة
        </span>
        <span v-if="items.observedAt" class="today-stale-time" dir="ltr" style="margin-top: 0; margin-right: 0.5rem;">{{ items.observedAt }}</span>
        <span v-if="items.freshUntil" class="today-stale-time today-stale-time--until" dir="ltr" style="margin-top: 0; margin-right: 0.5rem; color: #9ca3af;">until: {{ items.freshUntil }}</span>
      </div>
    </div>

    <div v-else class="today-empty-inline">
      <CepEmptyState
        class="today-empty-inline-state"
        title="لا توجد بنود انتباه واردة حاليًا"
        description="لا يتلقى سطح اليوم حاليًا أي بنود انتباه أو متطلبات مراجعة معلقة من مساحات العمل. تابع الإجراءات الموصى بها في السطح الرئيسي."
        data-testid="today-attention-empty"
      />
    </div>
  </section>
</template>

<style scoped>
.today-context-card {
  display: grid;
  gap: 0.65rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1rem;
}

.today-context-card__header {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
}

.today-context-card__icon {
  width: 1.15rem;
  height: 1.15rem;
  flex: 0 0 1.15rem;
  color: var(--cep-accent);
  margin-top: 0.15rem;
}

.today-attention-header-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.today-attention-count-badge {
  display: inline-flex;
  align-items: center;
  border-radius: var(--cep-radius-sm);
  background: rgba(239, 68, 68, 0.15);
  padding: 0.15rem 0.5rem;
  color: #ef4444;
  font-size: 0.72rem;
  font-weight: 800;
}

.today-context-copy {
  margin: 0.35rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.84rem;
  line-height: 1.75;
}

.today-attention-list {
  display: grid;
  gap: 0.5rem;
  margin: 0.5rem 0 0;
  padding: 0;
  list-style: none;
}

.today-attention-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel);
  padding: 0.85rem;
  transition:
    border-color 150ms ease,
    background-color 150ms ease;
}

.today-attention-item:hover {
  border-color: var(--cep-border-strong);
  background: var(--cep-bg-panel-strong);
}

.today-attention-item--urgent {
  border-right: 3px solid #ef4444;
}

.today-attention-item--warning {
  border-right: 3px solid #f59e0b;
}

.today-attention-item--info {
  border-right: 3px solid var(--cep-accent);
}

.today-attention-item__title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 750;
  line-height: 1.4;
}

.today-attention-item__link {
  color: var(--cep-text);
  text-decoration: none;
}

.today-attention-item__link:hover {
  text-decoration: underline;
}

.today-attention-item__link::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.today-attention-item {
  position: relative;
}

.today-attention-item__reason {
  margin: 0.25rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.82rem;
  line-height: 1.6;
}

.today-attention-item__meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.today-attention-severity {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.74rem;
  font-weight: 700;
}

.today-attention-item--urgent .today-attention-severity {
  color: #ef4444;
}

.today-attention-item--warning .today-attention-severity {
  color: #f59e0b;
}

.today-attention-item--info .today-attention-severity {
  color: var(--cep-accent);
}

.today-severity-icon {
  width: 0.75rem;
  height: 0.75rem;
}

.today-attention-domain {
  color: var(--cep-text-muted);
  font-size: 0.72rem;
  padding-right: 0.5rem;
  border-right: 1px solid var(--cep-border);
}

.today-empty-inline {
  margin-top: 0.5rem;
}

.today-empty-inline-state {
  border: 1px dashed var(--cep-border);
  background: var(--cep-bg-canvas);
  padding: 1.25rem 1rem !important;
}

.today-diagnostic-id {
  display: inline-block;
  margin-top: 0.5rem;
  font-family: monospace;
  font-size: 0.8rem;
  color: #ef4444;
}

.today-stale-badge-container {
  display: flex;
  align-items: center;
  margin-top: 0.5rem;
}

.today-stale-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border: 1px solid rgba(245, 158, 11, 0.35);
  border-radius: var(--cep-radius-sm);
  background: rgba(245, 158, 11, 0.08);
  padding: 0.22rem 0.55rem;
  color: #f59e0b;
  font-size: 0.74rem;
  font-weight: 700;
}

.today-stale-icon {
  width: 0.85rem;
  height: 0.85rem;
}

.today-stale-time {
  display: inline-block;
  margin-top: 0.5rem;
  font-size: 0.8rem;
  color: #f59e0b;
}
</style>

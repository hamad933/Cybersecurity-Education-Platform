<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

import CepEmptyState from '../shared/CepEmptyState.vue';
import type { TodayAttentionItem, OrchestrationNode, OrchestrationStatus } from './types';

const props = defineProps<{
  items?: OrchestrationNode<TodayAttentionItem[]> | TodayAttentionItem[] | null;
}>();

const node = computed<OrchestrationNode<TodayAttentionItem[]>>(() => {
  const it = props.items;
  if (!it) return { status: 'EMPTY', data: [] };
  if (typeof it === 'object' && !Array.isArray(it) && 'status' in it) {
    return it as OrchestrationNode<TodayAttentionItem[]>;
  }
  if (Array.isArray(it)) {
    return {
      status: (it.length > 0 ? 'AVAILABLE' : 'EMPTY') as OrchestrationStatus,
      data: it as TodayAttentionItem[],
    };
  }
  return { status: 'EMPTY', data: [] };
});
</script>

<template>
  <section
    id="attention-items"
    class="cep-section today-section"
    aria-labelledby="attention-title"
    data-today-level="4"
  >
    <div class="today-section-header">
      <div>
        <p class="cep-kicker">متطلبات التدخل والمراجعة</p>
        <h2 id="attention-title" class="cep-section-title">ما يحتاج انتباهك</h2>
      </div>
      <span v-if="node.status === 'STALE'" class="today-stale-badge" data-testid="today-stale-badge">
        <svg class="today-stale-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        بيانات غير محدثة
      </span>

      <span
        v-if="node.status === 'AVAILABLE' && node.data && node.data.length > 0"
        class="today-attention-count-badge"
      >
        <svg
          class="today-attention-count-icon"
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
            clip-rule="evenodd"
          />
        </svg>
        {{ node.data.length }}
        {{ node.data.length === 1 ? 'بند يتطلب المتابعة' : 'بنود تتطلب المتابعة' }}
      </span>
    </div>

    <div
      v-if="(node.status === 'AVAILABLE' || node.status === 'STALE') && node.data && node.data.length > 0"
      class="today-attention-stack"
      data-testid="today-attention-list"
    >
      <article
        v-for="item in node.data"
        :key="item.id"
        class="today-attention-card"
        :class="`today-attention-card--${item.severity}`"
      >
        <div class="today-attention-card__header">
          <div class="today-attention-card__badges">
            <span class="today-severity-badge" :class="`today-severity-badge--${item.severity}`">
              <svg
                v-if="item.severity === 'urgent'"
                class="today-severity-icon"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
              >
                <path
                  fill-rule="evenodd"
                  d="M13.5 4.938a7 7 0 11-9.006 1.737c.2-.026.4-.05.606-.072.775-.086 1.503-.26 2.176-.511a5.503 5.503 0 006.224-1.154zM10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z"
                  clip-rule="evenodd"
                />
              </svg>
              <svg
                v-else-if="item.severity === 'warning'"
                class="today-severity-icon"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
              >
                <path
                  fill-rule="evenodd"
                  d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 8a1 1 0 100-2 1 1 0 000 2z"
                  clip-rule="evenodd"
                />
              </svg>
              <svg
                v-else
                class="today-severity-icon"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
              >
                <path
                  fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                  clip-rule="evenodd"
                />
              </svg>
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
        </div>

        <div class="today-attention-card__body">
          <h3 class="today-attention-card__title">{{ item.title }}</h3>
          <p class="today-attention-card__reason">{{ item.reason }}</p>
        </div>

        <div class="today-attention-card__footer">
          <Link :href="item.href" class="cep-text-button today-attention-action focus-ring">
            <span>{{ item.actionLabel || 'معالجة البند في مساحة العمل' }}</span>
            <span class="today-btn-arrow" aria-hidden="true">◀</span>
          </Link>
        </div>
      </article>
    </div>

    <div v-else-if="node.status === 'UNAVAILABLE'" class="today-empty-wrapper">
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
        title="تنبيهات غير متوفرة"
        description="تعذر الاتصال بالمجال لمعرفة بنود الانتباه."
        data-testid="today-attention-unavailable"
      />
    </div>

    
    <div v-else-if="node.status === 'ERROR'" class="today-empty-wrapper">
      <div class="today-empty-icon-box" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05);" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="today-empty-svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="حدث خطأ في جلب البيانات"
        :description="node.message || 'تعذر تحميل هذه البيانات بسبب خطأ غير معروف.'"
        data-testid="today-error-state"
      />
    </div>

    <div v-else-if="node.status === 'STALE' && (!node.data || node.data.length === 0)" class="today-empty-wrapper">
      <div class="today-empty-icon-box" style="color: #f59e0b; border-color: rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.05);" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="today-empty-svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="البيانات غير محدّثة (قديمة)"
        :description="node.message || 'هذه البيانات قديمة ولم نتمكن من تحديثها الآن ولا توجد نسخة محفوظة صالحة للعرض.'"
        data-testid="today-stale-empty-state"
      />
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
            d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
          />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="لا توجد بنود انتباه واردة حاليًا"
        description="لا يتلقى سطح اليوم حاليًا أي بنود انتباه أو متطلبات مراجعة معلقة من مساحات العمل. لا تُعرض تنبيهات إلا عند ورود بنود محددة من المجالات المعنية."
        data-testid="today-attention-empty"
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

.today-attention-count-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border: 1px solid rgba(245, 158, 11, 0.35);
  border-radius: var(--cep-radius-sm);
  background: rgba(245, 158, 11, 0.08);
  padding: 0.22rem 0.6rem;
  color: #f59e0b;
  font-size: 0.76rem;
  font-weight: 700;
}

.today-attention-count-icon {
  width: 0.85rem;
  height: 0.85rem;
}

.today-attention-stack {
  display: grid;
  gap: 0.85rem;
  margin-top: 0.9rem;
}

.today-attention-card {
  display: grid;
  gap: 0.85rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1.25rem;
  box-shadow: var(--cep-shadow);
  transition:
    transform 150ms ease,
    border-color 150ms ease;
}

.today-attention-card:hover {
  transform: translateY(-1px);
}

.today-attention-card--urgent {
  border-color: rgba(244, 63, 94, 0.45);
  background: linear-gradient(135deg, rgba(35, 10, 20, 0.7) 0%, rgba(20, 8, 15, 0.9) 100%);
  box-shadow: 0 4px 20px -4px rgba(244, 63, 94, 0.15);
}

[data-theme='light'] .today-attention-card--urgent {
  border-color: rgba(244, 63, 94, 0.4);
  background: linear-gradient(135deg, #fff1f2 0%, #ffffff 100%);
}

.today-attention-card--warning {
  border-color: rgba(245, 158, 11, 0.45);
  background: linear-gradient(135deg, rgba(35, 25, 10, 0.7) 0%, rgba(20, 15, 8, 0.9) 100%);
  box-shadow: 0 4px 20px -4px rgba(245, 158, 11, 0.15);
}

[data-theme='light'] .today-attention-card--warning {
  border-color: rgba(245, 158, 11, 0.4);
  background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);
}

.today-attention-card--info {
  border-color: rgba(34, 211, 238, 0.35);
  background: var(--cep-bg-panel-strong);
}

.today-attention-card__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.65rem;
}

.today-attention-card__badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.today-severity-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border-radius: var(--cep-radius-sm);
  padding: 0.2rem 0.6rem;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.02em;
}

.today-severity-icon {
  width: 0.8rem;
  height: 0.8rem;
}

.today-severity-badge--urgent {
  border: 1px solid #f43f5e;
  background: #f43f5e;
  color: #ffffff;
}

.today-severity-badge--warning {
  border: 1px solid #f59e0b;
  background: #f59e0b;
  color: #020914;
}

.today-severity-badge--info {
  border: 1px solid var(--cep-accent);
  background: var(--cep-accent-soft);
  color: var(--cep-accent);
}

.today-domain-label {
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
  padding: 0.18rem 0.5rem;
  color: var(--cep-text-muted);
  font-size: 0.76rem;
}

.today-attention-card__title {
  margin: 0;
  color: var(--cep-text);
  font-size: 1.05rem;
  font-weight: 780;
  line-height: 1.35;
}

.today-attention-card__reason {
  margin: 0.4rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.88rem;
  line-height: 1.75;
}

.today-attention-card__footer {
  display: flex;
  align-items: center;
  margin-top: 0.35rem;
  padding-top: 0.65rem;
  border-top: 1px solid var(--cep-border);
}

.today-attention-action {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border: 1px solid var(--cep-border-strong);
  background: var(--cep-bg-panel);
  padding: 0.55rem 1.1rem;
  color: var(--cep-text);
  font-weight: 750;
  text-decoration: none;
  transition:
    border-color 150ms ease,
    background-color 150ms ease,
    transform 150ms ease;
}

.today-attention-action:hover {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
  color: var(--cep-accent);
  transform: translateY(-1px);
}

.today-btn-arrow {
  display: inline-block;
  transition: transform 150ms ease;
}

.today-attention-action:hover .today-btn-arrow {
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
  .today-attention-action {
    width: 100%;
    justify-content: center;
  }

  .today-empty-wrapper {
    flex-direction: column;
  }
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
</style>

<script setup lang="ts">
import { computed } from 'vue';
import CepEmptyState from '../shared/CepEmptyState.vue';
import TechnicalText from '../shared/TechnicalText.vue';
import type { TodayProgressProjection, OrchestrationNode } from './types';

const props = defineProps<{
  projection?: OrchestrationNode<TodayProgressProjection> | TodayProgressProjection | null;
}>();

const node = computed<OrchestrationNode<TodayProgressProjection>>(() => {
  const p = props.projection;
  if (!p) return { status: 'EMPTY', data: null };
  if (typeof p === 'object' && 'status' in p) {
    return p as OrchestrationNode<TodayProgressProjection>;
  }
  return { status: 'AVAILABLE', data: p as TodayProgressProjection };
});
</script>

<template>
  <section
    id="progress-projection"
    class="cep-section today-section"
    aria-labelledby="progress-projection-title"
    data-today-level="6"
  >
    <div class="today-section-header">
      <div>
        <p class="cep-kicker">المسار المرحلي الحقيقي</p>
        <h2 id="progress-projection-title" class="cep-section-title">التوقعات المرحلية</h2>
      </div>
      <span v-if="node.status === 'STALE'" class="today-stale-badge" data-testid="today-stale-badge">
        <svg class="today-stale-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        بيانات غير محدثة
      </span>

      <span v-if="node.status === 'AVAILABLE' && node.data" class="today-projection-badge">
        <svg
          class="today-projection-icon"
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
        إثبات مبني على الأدلة
      </span>
    </div>

    <div
      v-if="(node.status === 'AVAILABLE' || node.status === 'STALE') && node.data"
      class="today-projection-card"
      data-testid="today-projection-active"
    >
      <div class="today-projection-card__header">
        <div class="today-projection-card__title-group">
          <h3 class="today-projection-card__title">{{ node.data.milestoneTitle }}</h3>
          <p class="today-projection-summary">{{ node.data.statusSummary }}</p>
        </div>
        <span v-if="node.data.targetHorizon" class="today-horizon-pill">
          <svg
            class="today-horizon-icon"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z"
              clip-rule="evenodd"
            />
          </svg>
          الأفق: <TechnicalText :value="node.data.targetHorizon" />
        </span>
      </div>

      <div class="today-projection-card__body">
        <dl class="cep-fact-list today-projection-fact-list">
          <div class="cep-fact-list__row today-fact-row">
            <dt class="today-fact-dt">
              <span class="today-fact-dot" aria-hidden="true" />
              الوحدات المعرفية المحققة بأدلة
            </dt>
            <dd class="today-fact-dd">
              <span class="today-verified-badge">
                <TechnicalText :value="`${node.data.verifiedCount} / ${node.data.totalCount}`" />
              </span>
            </dd>
          </div>
          <div v-if="node.data.evidenceRequirement" class="cep-fact-list__row today-fact-row">
            <dt class="today-fact-dt">
              <span class="today-fact-dot today-fact-dot--pending" aria-hidden="true" />
              متطلب الإثبات القادم
            </dt>
            <dd class="today-fact-dd today-fact-dd--text">{{ node.data.evidenceRequirement }}</dd>
          </div>
        </dl>
      </div>

      <div class="today-projection-card__notice">
        <div class="today-law-banner">
          <svg class="today-law-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z"
              clip-rule="evenodd"
            />
          </svg>
          <p class="today-law-note">
            * الإنجاز لا يساوي الإتقان (Completion != Mastery). تقاس الكفاءة بالأدلة المثبتة، ولا
            نستخدم نسبًا مئوية افتراضية.
          </p>
        </div>
      </div>
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
        title="توقعات التقدم غير متوفرة"
        description="تعذر الاتصال بالمجال لمعرفة توقعات التقدم."
        data-testid="today-projection-unavailable"
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

    <div v-else-if="node.status === 'STALE' && !node.data" class="today-empty-wrapper">
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
            d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"
          />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="التوقعات المرحلية تتطلب أدلة تقييم مثبتة"
        description="لا تُعرض تقديرات التقدم إلا بناءً على أدلة تقييم محققة في مساحة التقدم والأدلة، دون استخدام نسب مئوية تقديرية أو أشرطة تقدم افتراضية (Completion != Mastery)."
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

.today-projection-badge {
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

.today-projection-icon {
  width: 0.85rem;
  height: 0.85rem;
  color: var(--cep-accent);
}

.today-projection-card {
  display: grid;
  gap: 1rem;
  margin-top: 0.9rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1.25rem;
  box-shadow: var(--cep-shadow);
}

.today-projection-card__header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
}

.today-projection-card__title-group {
  display: grid;
  gap: 0.35rem;
}

.today-projection-card__title {
  margin: 0;
  color: var(--cep-text);
  font-size: 1.1rem;
  font-weight: 780;
  line-height: 1.35;
}

.today-projection-summary {
  margin: 0;
  color: var(--cep-text-muted);
  font-size: 0.88rem;
  line-height: 1.7;
}

.today-horizon-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1px solid rgba(34, 211, 238, 0.3);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent-soft);
  padding: 0.22rem 0.65rem;
  color: var(--cep-accent);
  font-size: 0.76rem;
  font-weight: 750;
}

.today-horizon-icon {
  width: 0.8rem;
  height: 0.8rem;
}

.today-projection-fact-list {
  margin: 0;
  background: var(--cep-bg-panel);
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-sm);
  padding: 0.5rem 0.85rem;
}

.today-fact-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-block: 0.65rem;
}

.today-fact-dt {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  color: var(--cep-text-muted);
  font-size: 0.84rem;
}

.today-fact-dot {
  width: 0.4rem;
  height: 0.4rem;
  border-radius: 50%;
  background: #10b981;
}

.today-fact-dot--pending {
  background: var(--cep-accent);
}

.today-fact-dd {
  margin: 0;
  color: var(--cep-text);
  font-size: 0.86rem;
  font-weight: 700;
}

.today-fact-dd--text {
  color: var(--cep-text);
  font-weight: 600;
}

.today-verified-badge {
  display: inline-flex;
  align-items: center;
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: var(--cep-radius-sm);
  background: rgba(16, 185, 129, 0.08);
  padding: 0.15rem 0.55rem;
  color: #10b981;
  font-weight: 800;
}

.today-projection-card__notice {
  padding-top: 0.75rem;
  border-top: 1px dashed var(--cep-border);
}

.today-law-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  border: 1px solid rgba(34, 211, 238, 0.2);
  border-radius: var(--cep-radius-sm);
  background: rgba(34, 211, 238, 0.04);
  padding: 0.65rem 0.85rem;
}

.today-law-icon {
  width: 1rem;
  height: 1rem;
  flex: 0 0 1rem;
  color: var(--cep-accent);
  margin-top: 0.1rem;
}

.today-law-note {
  margin: 0;
  color: var(--cep-text-muted);
  font-size: 0.78rem;
  line-height: 1.65;
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
  .today-fact-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.35rem;
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

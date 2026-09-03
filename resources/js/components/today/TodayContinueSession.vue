<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import CepEmptyState from '../shared/CepEmptyState.vue';
import type { OrchestrationNode, TodaySessionItem } from './types';

const props = defineProps<{
  node?: OrchestrationNode<TodaySessionItem>;
}>();

const nodeValue = props.node || { status: 'UNAVAILABLE', data: null };
</script>

<template>
  <section class="today-section" aria-labelledby="today-session-title">
    <div class="today-section-header">
      <div>
        <h2 id="today-session-title" class="cep-section__title" data-today-level="1">
          استئناف الجلسة
        </h2>
        <p class="cep-section__desc">متابعة مسارك الحالي من النقطة التي توقفت عندها.</p>
      </div>
      <div v-if="nodeValue.status === 'AVAILABLE' && nodeValue.data" class="today-live-pulse-badge">
        <span class="today-live-pulse-dot" aria-hidden="true" />
        قيد التقدم
      </div>
    </div>

    <div
      v-if="nodeValue.status === 'AVAILABLE' && nodeValue.data"
      class="today-session-card"
      data-testid="today-session-active"
    >
      <div class="today-session-card__header">
        <div class="today-session-card__tags">
          <span class="today-domain-badge">
            <svg
              class="today-badge-icon"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"
              />
            </svg>
            {{ nodeValue.data.domainLabel }}
          </span>
          <span v-if="nodeValue.data.moduleName" class="today-code-pill" dir="ltr">
            <span class="today-code-pill__prefix">#</span>{{ nodeValue.data.moduleName }}
          </span>
        </div>
        <div v-if="nodeValue.data.lastActivityAt" class="today-meta-time">
          <svg class="today-time-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z"
              clip-rule="evenodd"
            />
          </svg>
          آخر نشاط: {{ nodeValue.data.lastActivityAt }}
        </div>
      </div>

      <h3 class="today-session-card__title">{{ nodeValue.data.title }}</h3>

      <div v-if="nodeValue.data.currentStep" class="today-session-card__step-row">
        <span class="today-step-label">المرحلة الحالية:</span>
        <span class="today-step-value">{{ nodeValue.data.currentStep }}</span>
      </div>

      <div class="today-session-card__actions">
        <Link
          :href="nodeValue.data.href"
          class="today-hero-button focus-ring"
          data-testid="today-session-resume"
        >
          <span>{{ nodeValue.data.actionLabel || 'استئناف الجلسة الآن' }}</span>
          <span class="today-hero-btn-arrow" aria-hidden="true">◀</span>
        </Link>
        <span class="today-hero-subnote"
          >الاستئناف يحفظ حالة التقدم والبيئة التشغيلية دون إعادة تعيين.</span
        >
      </div>
    </div>

    <div v-else-if="nodeValue.status === 'UNAVAILABLE'" class="today-empty-wrapper">
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
        title="حالة الجلسة غير متوفرة"
        description="تعذر الاتصال بمجال المصدر لمعرفة الجلسة الحالية. هذا قد يحدث إذا لم يكن المجال قيد التشغيل أو هناك اعتماديات مفقودة."
        data-testid="today-session-unavailable"
      />
    </div>

    
    <div v-else-if="nodeValue.status === 'ERROR'" class="today-empty-wrapper">
      <div class="today-empty-icon-box" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05);" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="today-empty-svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="حدث خطأ في جلب البيانات"
        :description="nodeValue.message || 'تعذر تحميل هذه البيانات بسبب خطأ داخلي.'"
        data-testid="today-error-state"
      >
        <template v-if="nodeValue.diagnosticId">
            <span class="today-diagnostic-id" dir="ltr">{{ nodeValue.diagnosticId }}</span>
        </template>
      </CepEmptyState>
    </div>

    <div v-else-if="nodeValue.status === 'STALE'" class="today-empty-wrapper">
      <div class="today-empty-icon-box" style="color: #f59e0b; border-color: rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.05);" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="today-empty-svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="البيانات غير محدّثة (قديمة)"
        :description="nodeValue.message || 'هذه البيانات قديمة ولم نتمكن من تحديثها الآن ولا توجد نسخة محفوظة صالحة للعرض.'"
        data-testid="today-stale-empty-state"
      >
        <template v-if="nodeValue.observedAt">
            <span class="today-stale-time" dir="ltr">{{ nodeValue.observedAt }}</span>
            <span v-if="nodeValue.freshUntil" class="today-stale-time today-stale-time--until" dir="ltr" style="margin-right: 0.5rem; color: #9ca3af;">until: {{ nodeValue.freshUntil }}</span>
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
            d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"
          />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="لا توجد جلسة عمل نشطة حاليًا"
        description="لا توجد جلسة قيد التنفيذ لاستئنافها. يمكنك استكشاف مساحات العمل للبدء بنشاط جديد أو اختيار الإجراء التالي الموصى به أدناه."
        data-testid="today-session-empty"
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

.today-live-pulse-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  border: 1px solid rgba(34, 211, 238, 0.35);
  border-radius: var(--cep-radius-sm);
  background: rgba(34, 211, 238, 0.08);
  padding: 0.25rem 0.6rem;
  color: var(--cep-accent);
  font-size: 0.76rem;
  font-weight: 700;
}

.today-live-pulse-dot {
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 50%;
  background-color: var(--cep-accent);
  box-shadow: 0 0 8px var(--cep-accent);
  animation: pulse-glow 2s infinite ease-in-out;
}

@keyframes pulse-glow {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.4;
    transform: scale(0.85);
  }
}

.today-session-card {
  position: relative;
  display: grid;
  gap: 1.1rem;
  margin-top: 0.9rem;
  border: 1px solid rgba(34, 211, 238, 0.35);
  border-radius: var(--cep-radius-md);
  background: linear-gradient(135deg, rgba(7, 26, 42, 0.98) 0%, rgba(14, 42, 68, 0.85) 100%);
  padding: clamp(1.15rem, 2vw, 1.5rem);
  box-shadow:
    0 12px 32px -8px rgba(0, 0, 0, 0.6),
    inset 0 1px 0 rgba(34, 211, 238, 0.2);
  overflow: hidden;
}

[data-theme='light'] .today-session-card {
  border-color: rgba(8, 145, 178, 0.4);
  background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%);
  box-shadow:
    0 8px 24px -6px rgba(8, 145, 178, 0.12),
    inset 0 1px 0 rgba(8, 145, 178, 0.15);
}

.today-session-card__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.today-session-card__tags {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.today-domain-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1px solid var(--cep-accent);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent-soft);
  padding: 0.22rem 0.65rem;
  color: var(--cep-accent);
  font-size: 0.78rem;
  font-weight: 750;
}

.today-badge-icon,
.today-time-icon {
  width: 0.85rem;
  height: 0.85rem;
}

.today-code-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel-strong);
  padding: 0.2rem 0.55rem;
  font-size: 0.76rem;
}

.today-code-pill__prefix {
  color: var(--cep-text-muted);
}

.today-meta-time {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--cep-text-muted);
  font-size: 0.8rem;
}

.today-session-card__title {
  margin: 0;
  color: var(--cep-text);
  font-size: clamp(1.15rem, 2vw, 1.4rem);
  font-weight: 800;
  line-height: 1.35;
}

.today-session-card__step-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.65rem;
  border-top: 1px solid rgba(255, 255, 255, 0.07);
  padding-top: 0.65rem;
}

[data-theme='light'] .today-session-card__step-row {
  border-top-color: rgba(0, 0, 0, 0.07);
}

.today-step-label {
  color: var(--cep-text-muted);
  font-size: 0.84rem;
}

.today-step-value {
  color: var(--cep-accent);
  font-size: 0.86rem;
  font-weight: 700;
}

.today-session-card__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
  margin-top: 0.35rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

[data-theme='light'] .today-session-card__actions {
  border-top-color: rgba(0, 0, 0, 0.08);
}

.today-hero-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  border: 1px solid var(--cep-accent);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent);
  padding: 0.7rem 1.5rem;
  color: #020914;
  font-size: 0.92rem;
  font-weight: 800;
  text-decoration: none;
  box-shadow: 0 4px 14px rgba(34, 211, 238, 0.35);
  transition:
    background 150ms ease,
    transform 150ms ease,
    box-shadow 150ms ease;
}

.today-hero-button:hover {
  background: var(--cep-accent-hover);
  box-shadow: 0 6px 20px rgba(34, 211, 238, 0.5);
  transform: translateY(-1px);
}

.today-hero-btn-arrow {
  display: inline-block;
  transition: transform 150ms ease;
}

.today-hero-button:hover .today-hero-btn-arrow {
  transform: translateX(-3px);
}

.today-hero-subnote {
  color: var(--cep-text-muted);
  font-size: 0.78rem;
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
  .today-session-card__actions {
    flex-direction: column;
    align-items: stretch;
  }

  .today-hero-button {
    width: 100%;
  }

  .today-empty-wrapper {
    flex-direction: column;
  }
}
</style>

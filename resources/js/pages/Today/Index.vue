<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import CepWorkspaceLayout from '../../layouts/CepWorkspaceLayout.vue';
import TechnicalText from '../../components/shared/TechnicalText.vue';

import TodayAttentionItems from '../../components/today/TodayAttentionItems.vue';
import TodayContinueSession from '../../components/today/TodayContinueSession.vue';
import TodayRecommendation from '../../components/today/TodayRecommendation.vue';
import TodayProgressProjection from '../../components/today/TodayProgressProjection.vue';
import TodayRecentContext from '../../components/today/TodayRecentContext.vue';
import type { TodayOrchestrationPayload } from '../../components/today/types';

interface PageEnvironment {
  name: string;
  profile: string;
}

const props = defineProps<{
  orchestration: TodayOrchestrationPayload;
}>();

const isDiagnosticsOpen = ref(false);

const toggleDiagnostics = () => {
  isDiagnosticsOpen.value = !isDiagnosticsOpen.value;
};

const routeRegistrationSummary = computed(() => {
  return `${props.orchestration.registeredDomainEntries}/${props.orchestration.expectedDomainEntries}`;
});

const isReadyForWork = computed(() => {
  return props.orchestration.registeredDomainEntries === props.orchestration.expectedDomainEntries;
});

const environmentName = computed(() => {
  return usePage<{ environment?: PageEnvironment }>().props.environment?.name ?? 'غير مرصود';
});

const environmentProfile = computed(() => {
  return usePage<{ environment?: PageEnvironment }>().props.environment?.profile ?? 'غير مرصود';
});
</script>

<template>
  <Head title="اليوم" />

  <CepWorkspaceLayout active-destination="today" :is-bottom-panel-open="isDiagnosticsOpen">
    <template #left>
      <nav class="cep-structure-nav" aria-label="تنقل سطح اليوم">
        <ul class="cep-structure-nav__list">
          <li class="cep-structure-nav__item">
            <a href="#continue-session" class="cep-structure-nav__link">
              <span class="today-nav-index">01</span>
              <span class="today-nav-label">استئناف الجلسة</span>
            </a>
          </li>
          <li class="cep-structure-nav__item">
            <a href="#recommendation" class="cep-structure-nav__link">
              <span class="today-nav-index">02</span>
              <span class="today-nav-label">التوصية</span>
            </a>
          </li>
          <li class="cep-structure-nav__item">
            <a href="#attention-items" class="cep-structure-nav__link">
              <span class="today-nav-index">03</span>
              <span class="today-nav-label">بنود الانتباه</span>
            </a>
          </li>
          <li class="cep-structure-nav__item">
            <a href="#recent-context" class="cep-structure-nav__link">
              <span class="today-nav-index">04</span>
              <span class="today-nav-label">السياق الأخير</span>
            </a>
          </li>
          <li class="cep-structure-nav__item">
            <a href="#progress-projection" class="cep-structure-nav__link">
              <span class="today-nav-index">05</span>
              <span class="today-nav-label">توقعات التقدم</span>
            </a>
          </li>
        </ul>
      </nav>
    </template>

    <div class="today-command-bar cep-action-bar">
      <div class="today-command-bar__title-row">
        <div class="today-command-bar__beacon" aria-hidden="true" />
        <h1 id="today-title" class="today-command-bar__title">سطح قيادة وتنسيق اليوم</h1>
      </div>
      <div class="today-command-bar__actions">
        <span
          v-if="!isReadyForWork"
          class="today-routes-badge"
          title="لم يتم تسجيل كافة مداخل المجالات المطلوبة"
        >
          <svg class="today-btn-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
              fill-rule="evenodd"
              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z"
              clip-rule="evenodd"
            />
          </svg>
          <TechnicalText :value="routeRegistrationSummary" /> مسار مسجل
        </span>
        <button
          type="button"
          class="today-action-btn"
          :class="{ 'today-action-btn--active': isDiagnosticsOpen }"
          :aria-expanded="isDiagnosticsOpen"
          aria-controls="today-diagnostics"
          data-testid="today-diagnostics-toggle"
          @click="toggleDiagnostics"
        >
          <svg
            class="today-btn-icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M10.34 15.84c-.688-.06-1.386-.054-2.066.05A6 6 0 0 1 15 6v2m0 0a6 6 0 0 1 5.27 8.91M15 8l-3 3m3-3l3 3m-8.59 4.41L4.5 21l3.5-3.5"
            />
          </svg>
          التشخيص
        </button>
      </div>
    </div>

    <header id="today-header" class="today-main-header">
      <div class="today-header-topline">
        <span class="today-kicker-badge">
          <span class="today-kicker-dot" aria-hidden="true" />
          تنسيق عبر المجالات
        </span>
      </div>
      <h2 class="cep-hero-title">اليوم</h2>
      <p class="cep-hero-desc">
        نقطة الانطلاق اليومية لتوجيه تركيزك المعرفي والعملي عبر كافة مساحات منصة التعليم السيبراني.
      </p>
    </header>

    <!-- Level 1: Continue Session -->
    <TodayContinueSession :node="orchestration.continueSession" />

    <!-- Level 2: Recommendation -->
    <TodayRecommendation :node="orchestration.recommendation" />

    <!-- Level 3: Recent Context -->
    <TodayRecentContext :items="orchestration.recentContext" />

    <!-- Level 4: Progress Projection only where truthful -->
    <TodayProgressProjection :projection="orchestration.progressProjection" />

    <template #right>
      <div class="cep-context-stack">
        <!-- Attention Items strictly once in RIGHT -->
        <TodayAttentionItems :items="orchestration.attentionItems" />

        <section class="today-context-card" aria-labelledby="today-scope-title">
          <div class="today-context-card__header">
            <svg
              class="today-context-card__icon"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.75a.75.75 0 00-1.5 0v5.25a.75.75 0 001.5 0V6.75zm-.75 9a1 1 0 100-2 1 1 0 000 2z"
                clip-rule="evenodd"
              />
            </svg>
            <div>
              <p class="cep-kicker">حدود سلطة سطح اليوم</p>
              <h2 id="today-scope-title" class="cep-context-title">ما الذي يملكه سطح اليوم؟</h2>
            </div>
          </div>
          <ul class="today-context-list">
            <li>
              <span class="today-bullet-icon" aria-hidden="true">◆</span>
              <span>التوجيه والتنسيق التشغيلي بين مساحات العمل الأربع.</span>
            </li>
            <li>
              <span class="today-bullet-icon" aria-hidden="true">◆</span>
              <span>عدم احتواء أدوات تعديل وحدات معرفية أو مراجعة أدلة داخلية.</span>
            </li>
            <li>
              <span class="today-bullet-icon" aria-hidden="true">◆</span>
              <span>عرض الحالة العابرة فقط عندما يوجد مصدر تطبيق موثوق لها.</span>
            </li>
          </ul>
        </section>

        <section
          class="today-context-card today-context-card--law"
          aria-labelledby="today-law-title"
        >
          <div class="today-context-card__header">
            <svg
              class="today-context-card__icon today-context-card__icon--law"
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
            <div>
              <p class="cep-kicker">قوانين التقييم والقياس</p>
              <h2 id="today-law-title" class="cep-context-title">الإنجاز لا يعني الإتقان</h2>
            </div>
          </div>
          <ul class="today-context-list">
            <li>
              <span class="today-bullet-icon" aria-hidden="true">◆</span>
              <span>الإتقان لا يُقاس بنسب مئوية خادعة ولا يُكافأ بنقاط تفاعلية شكلية.</span>
            </li>
            <li>
              <span class="today-bullet-icon" aria-hidden="true">◆</span>
              <span>كل قفل معرفي يُفتح حصريًا بالأدلة الموثوقة المحققة في مجالها الأصلي.</span>
            </li>
            <li>
              <span class="today-bullet-icon" aria-hidden="true">◆</span>
              <span>التوقعات المرحلية تُعرض فقط إذا كانت مستندة إلى بيانات مثبتة.</span>
            </li>
          </ul>
        </section>

      </div>
    </template>

    <template #bottom>
      <div id="today-diagnostics" class="today-diagnostics">
        <section aria-labelledby="route-registration-title">
          <div class="today-diagnostics-header">
            <div>
              <p class="cep-kicker">تشخيص تقني</p>
              <h2 id="route-registration-title" class="cep-context-title">ربط مساحات العمل</h2>
            </div>
            <span class="today-diag-status-pill">
              <span class="today-diag-status-dot" aria-hidden="true" />
              تشخيص محلي
            </span>
          </div>
          <p class="cep-context-copy">
            قراءة مباشرة من مسارات Laravel المسجلة حاليًا. هذه المعلومة تشخيصية ولا تعني اكتمال
            المنتج أو جاهزية أي مجال.
          </p>
          <dl class="cep-fact-list today-diagnostics-facts">
            <div class="cep-fact-list__row">
              <dt>مداخل المجالات المسجلة</dt>
              <dd><TechnicalText :value="routeRegistrationSummary" /></dd>
            </div>
            <div class="cep-fact-list__row">
              <dt>بيئة التطبيق</dt>
              <dd><TechnicalText :value="environmentName" /></dd>
            </div>
            <div class="cep-fact-list__row">
              <dt>ملف التشغيل</dt>
              <dd><TechnicalText :value="environmentProfile" /></dd>
            </div>
          </dl>
        </section>
      </div>
    </template>
  </CepWorkspaceLayout>
</template>

<style scoped>
#today-header {
  scroll-margin-top: 6.5rem;
}

.today-command-bar {
  display: flex;
  width: 100%;
  min-width: 0;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.today-command-bar__copy {
  min-width: 0;
}

.today-command-bar__title-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.today-command-bar__beacon {
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 50%;
  background: var(--cep-accent);
  box-shadow: 0 0 6px var(--cep-accent);
}

.today-command-bar__title {
  margin: 0;
  color: var(--cep-text);
  font-size: 0.92rem;
  font-weight: 780;
}

.today-command-bar__meta {
  margin: 0.2rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.78rem;
  line-height: 1.6;
}

.today-command-bar__actions {
  display: flex;
  flex: 0 0 auto;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.today-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel-strong);
  padding: 0.5rem 0.85rem;
  color: var(--cep-text);
  font-size: 0.82rem;
  font-weight: 700;
  transition:
    border-color 140ms ease,
    background-color 140ms ease;
}

.today-action-btn:hover {
  border-color: var(--cep-accent);
  background: var(--cep-bg-panel);
}

.today-action-btn--active {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
  color: var(--cep-accent);
}

.today-action-btn:disabled {
  cursor: progress;
  opacity: 0.58;
}

.today-btn-icon {
  width: 0.9rem;
  height: 0.9rem;
  color: var(--cep-accent);
}

.today-btn-icon--spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.today-routes-badge {
  display: inline-flex;
  align-items: center;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent-soft);
  padding: 0.1rem 0.4rem;
  color: var(--cep-accent);
  font-size: 0.72rem;
  font-weight: 800;
}

.today-nav-index {
  display: inline-block;
  margin-inline-end: 0.4rem;
  color: var(--cep-accent);
  font-size: 0.75rem;
  font-weight: 800;
  opacity: 0.85;
}

.today-nav-label {
  font-weight: 700;
}

.today-main-header {
  display: grid;
  gap: 0.35rem;
}

.today-header-topline {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.today-kicker-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border: 1px solid rgba(34, 211, 238, 0.25);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent-soft);
  padding: 0.18rem 0.55rem;
  color: var(--cep-accent);
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.04em;
}

.today-kicker-dot {
  width: 0.35rem;
  height: 0.35rem;
  border-radius: 50%;
  background: var(--cep-accent);
}

.today-context-card {
  display: grid;
  gap: 0.65rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1rem;
}

.today-context-card--law {
  border-color: rgba(34, 211, 238, 0.25);
  background: linear-gradient(135deg, var(--cep-bg-panel-strong) 0%, rgba(34, 211, 238, 0.04) 100%);
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

.today-context-card__icon--law {
  color: #10b981;
}

.today-context-list {
  display: grid;
  gap: 0.55rem;
  margin: 0.35rem 0 0;
  padding: 0;
  list-style: none;
}

.today-context-list li {
  display: flex;
  align-items: flex-start;
  gap: 0.45rem;
  color: var(--cep-text-muted);
  font-size: 0.82rem;
  line-height: 1.65;
}

.today-bullet-icon {
  color: var(--cep-accent);
  font-size: 0.6rem;
  margin-top: 0.3rem;
  flex: 0 0 0.6rem;
}

.today-context-copy {
  margin: 0.35rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.84rem;
  line-height: 1.75;
}

.today-diagnostics {
  max-width: 48rem;
}

.today-diagnostics-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.today-diag-status-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
  padding: 0.2rem 0.55rem;
  color: var(--cep-text-muted);
  font-size: 0.74rem;
  font-weight: 750;
}

.today-diag-status-dot {
  width: 0.4rem;
  height: 0.4rem;
  border-radius: 50%;
  background: var(--cep-text-muted);
}

.today-diagnostics-facts {
  margin-top: 0.85rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
  padding: 0.5rem 0.85rem;
}

@media (max-width: 48rem) {
  .today-command-bar {
    align-items: stretch;
    flex-direction: column;
  }

  .today-command-bar__actions {
    width: 100%;
  }

  .today-command-bar__actions button {
    flex: 1 1 10rem;
    justify-content: center;
  }
}
</style>

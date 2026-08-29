<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import TodayAttentionItems from '../../components/today/TodayAttentionItems.vue';
import TodayContinueSession from '../../components/today/TodayContinueSession.vue';
import TodayNextAction from '../../components/today/TodayNextAction.vue';
import TodayProgressProjection from '../../components/today/TodayProgressProjection.vue';
import TodayRationale from '../../components/today/TodayRationale.vue';
import TodayRecentContext from '../../components/today/TodayRecentContext.vue';
import type { TodayOrchestrationPayload } from '../../components/today/types';
import TechnicalText from '../../components/shared/TechnicalText.vue';
import CepWorkspaceLayout from '../../layouts/CepWorkspaceLayout.vue';

interface PageEnvironment {
  name?: string;
  profile?: string;
  localOnly?: boolean;
}

const props = withDefaults(
  defineProps<{
    orchestration?: TodayOrchestrationPayload;
  }>(),
  {
    orchestration: () => ({
      registeredDomainEntries: 0,
      expectedDomainEntries: 4,
      continueSession: { status: 'UNAVAILABLE', data: null },
      nextAction: { status: 'UNAVAILABLE', data: null },
      rationale: { status: 'UNAVAILABLE', data: null },
      attentionItems: { status: 'UNAVAILABLE', data: [] },
      recentContext: { status: 'UNAVAILABLE', data: [] },
      progressProjection: { status: 'UNAVAILABLE', data: null },
    }),
  },
);

const page = usePage<{
  environment?: PageEnvironment;
}>();

const diagnosticsOpen = ref(false);
const refreshing = ref(false);

const environmentName = computed(() => {
  const name = page.props.environment?.name;
  return name && name.trim().length > 0 ? name.trim() : 'غير مرصود';
});

const environmentProfile = computed(() => {
  const profile = page.props.environment?.profile;
  return profile && profile.trim().length > 0 ? profile.trim() : 'غير مرصود';
});

const routeRegistrationSummary = computed(
  () =>
    `${props.orchestration.registeredDomainEntries}/${props.orchestration.expectedDomainEntries}`,
);

function refreshOrchestration() {
  refreshing.value = true;
  router.reload({
    only: ['orchestration'],
    onFinish: () => {
      refreshing.value = false;
    },
  });
}
</script>

<template>
  <Head title="اليوم | منصة تعليم الأمن السيبراني" />

  <CepWorkspaceLayout
    active-destination="today"
    :temporary-workspace-open="diagnosticsOpen"
    temporary-workspace-label="تشخيص ربط مساحات العمل"
    @close-temporary-workspace="diagnosticsOpen = false"
  >
    <template #top>
      <div class="today-command-bar">
        <div class="today-command-bar__copy">
          <div class="today-command-bar__title-row">
            <span class="today-command-bar__beacon" aria-hidden="true" />
            <p class="today-command-bar__title">سطح قيادة وتنسيق اليوم</p>
          </div>
          <p class="today-command-bar__meta">إدارة أولويات المسار والتوجيه بين مساحات المنصة</p>
        </div>
        <div class="today-command-bar__actions">
          <button
            type="button"
            class="cep-text-button today-action-btn focus-ring"
            :disabled="refreshing"
            @click="refreshOrchestration"
          >
            <svg
              class="today-btn-icon"
              :class="{ 'today-btn-icon--spin': refreshing }"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.451a.75.75 0 000-1.5H4.5a.75.75 0 00-.75.75v3.75a.75.75 0 001.5 0v-2.148l.43.43a7 7 0 1010.598-8.232.75.75 0 00-1.168.94 5.5 5.5 0 01.102 4.305z"
                clip-rule="evenodd"
              />
            </svg>
            <span>{{ refreshing ? 'جارٍ التحديث…' : 'إعادة قراءة الحالة' }}</span>
          </button>
          <button
            type="button"
            class="cep-text-button today-action-btn today-action-btn--toggle focus-ring"
            :class="{ 'today-action-btn--active': diagnosticsOpen }"
            data-testid="today-diagnostics-toggle"
            :aria-expanded="diagnosticsOpen"
            @click="diagnosticsOpen = !diagnosticsOpen"
          >
            <svg class="today-btn-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path
                fill-rule="evenodd"
                d="M14.5 10a4.5 4.5 0 004.284-3.116 1.75 1.75 0 00-1.042-2.186l-2.073-.83a.75.75 0 01-.469-.694V1.75A1.75 1.75 0 0013.45.025a4.5 4.5 0 00-6.9 0A1.75 1.75 0 004.8 1.75v1.424a.75.75 0 01-.469.694l-2.073.83A1.75 1.75 0 001.216 6.884 4.5 4.5 0 005.5 10c0 .942.29 1.817.784 2.54a1.75 1.75 0 00.316 2.4l1.458 1.167a.75.75 0 01.282.589v1.554A1.75 1.75 0 0010.1 20h-.2a1.75 1.75 0 001.75-1.75v-1.554a.75.75 0 01.282-.589l1.458-1.167a1.75 1.75 0 00.316-2.4c.494-.723.784-1.598.784-2.54zm-4.5 2a2 2 0 100-4 2 2 0 000 4z"
                clip-rule="evenodd"
              />
            </svg>
            <span>{{ diagnosticsOpen ? 'إخفاء التشخيص' : 'تشخيص الربط' }}</span>
          </button>
        </div>
      </div>
    </template>

    <template #left>
      <nav class="cep-structure-nav" aria-label="بنية سطح اليوم">
        <p class="cep-kicker">بنية السطح</p>
        <a class="cep-structure-nav__link focus-ring" href="#continue-session">
          <span class="today-nav-index">01</span>
          <span class="today-nav-label">الجلسة الحالية</span>
        </a>
        <a class="cep-structure-nav__link focus-ring" href="#next-action">
          <span class="today-nav-index">02</span>
          <span class="today-nav-label">الإجراء التالي</span>
        </a>
        <a class="cep-structure-nav__link focus-ring" href="#rationale">
          <span class="today-nav-index">03</span>
          <span class="today-nav-label">مسوغ التوصية</span>
        </a>
        <a class="cep-structure-nav__link focus-ring" href="#attention-items">
          <span class="today-nav-index">04</span>
          <span class="today-nav-label">بنود تحتاج انتباهك</span>
        </a>
        <a class="cep-structure-nav__link focus-ring" href="#recent-context">
          <span class="today-nav-index">05</span>
          <span class="today-nav-label">السياق الحديث</span>
        </a>
        <a class="cep-structure-nav__link focus-ring" href="#progress-projection">
          <span class="today-nav-index">06</span>
          <span class="today-nav-label">التوقعات المرحلية</span>
        </a>
        <a class="cep-structure-nav__link focus-ring" href="#workspace-handoffs">
          <span class="today-nav-index">07</span>
          <span class="today-nav-label">التوجيه للمجالات</span>
        </a>
      </nav>
    </template>

    <!-- CENTER PRIMARY SURFACE: Sequential Orchestration Hierarchy -->
    <header id="today-header" class="today-main-header">
      <div class="today-header-topline">
        <span class="today-kicker-badge">
          <span class="today-kicker-dot" aria-hidden="true" />
          سطح قيادة وتنسيق مركزي
        </span>
      </div>
      <h1 id="today-title" class="cep-page-title">اليوم</h1>
      <p class="cep-lede">
        نقطة بدء مركزية للعمل عبر المنصة. ينسق هذا السطح الأولويات التشغيلية الحقيقية فقط، ويوجّهك
        إلى مساحة العمل الأساسية المعتمدة بدل إنشاء نسخ مكررة من سجلات المجالات.
      </p>
    </header>

    <!-- Level 1: Continue Current Session -->
    <TodayContinueSession :session="orchestration.continueSession" />

    <!-- Level 2: Next Recommended Action -->
    <TodayNextAction :action="orchestration.nextAction" />

    <!-- Level 3: Why / rationale / what it unlocks -->
    <TodayRationale :rationale="orchestration.rationale" />

    <!-- Level 4: Attention / Review Required / blockers -->
    <TodayAttentionItems :items="orchestration.attentionItems" />

    <!-- Level 5: Recent Context -->
    <TodayRecentContext :items="orchestration.recentContext" />

    <!-- Level 6: Progress Projection only where truthful -->
    <TodayProgressProjection :projection="orchestration.progressProjection" />

    <template #right>
      <div class="cep-context-stack">
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

        <section
          id="workspace-handoffs"
          class="today-context-card"
          aria-labelledby="today-handoff-title"
        >
          <div class="today-context-card__header">
            <svg
              class="today-context-card__icon"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M4.25 2A2.25 2.25 0 002 4.25v11.5A2.25 2.25 0 004.25 18h11.5A2.25 2.25 0 0018 15.75V4.25A2.25 2.25 0 0015.75 2H4.25zm4.03 6.28a.75.75 0 00-1.06-1.06L4.97 9.47a.75.75 0 000 1.06l2.25 2.25a.75.75 0 001.06-1.06L7.06 10.5h5.88l-1.22 1.22a.75.75 0 101.06 1.06l2.25-2.25a.75.75 0 000-1.06l-2.25-2.25a.75.75 0 10-1.06 1.06l1.22 1.22H7.06l1.22-1.22z"
                clip-rule="evenodd"
              />
            </svg>
            <div>
              <p class="cep-kicker">استقلالية المجالات</p>
              <h2 id="today-handoff-title" class="cep-context-title">التوجيه للمالك المعتمد</h2>
            </div>
          </div>
          <p class="cep-context-copy today-context-copy">
            تنتقل كافة الإجراءات فورًا إلى مساحة العمل المعتمدة لضمان اتساق البيانات.
          </p>
          <div class="today-canonical-links">
            <Link href="/knowledge" data-today-workspace="knowledge" class="today-context-link">
              <span>المعرفة والتعلّم</span>
            </Link>
            <Link href="/simulation" data-today-workspace="simulation" class="today-context-link">
              <span>المحاكاة والمؤسسات</span>
            </Link>
            <Link href="/progress" data-today-workspace="progress" class="today-context-link">
              <span>التقدم والأدلة</span>
            </Link>
            <Link href="/system" data-today-workspace="system" class="today-context-link">
              <span>إدارة وتشغيل المنظومة</span>
            </Link>
          </div>
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

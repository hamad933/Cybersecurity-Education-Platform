<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import TodayAttentionItems from '../../components/today/TodayAttentionItems.vue';
import TodayContinueSession from '../../components/today/TodayContinueSession.vue';
import TodayNextAction from '../../components/today/TodayNextAction.vue';
import TodayProgressProjection from '../../components/today/TodayProgressProjection.vue';
import TodayRationale from '../../components/today/TodayRationale.vue';
import TodayRecentContext from '../../components/today/TodayRecentContext.vue';
import type { TodayOrchestrationPayload } from '../../components/today/types';
import TodayWorkspaceHandoffs from '../../components/today/TodayWorkspaceHandoffs.vue';
import TechnicalText from '../../components/shared/TechnicalText.vue';
import CepWorkspaceLayout from '../../layouts/CepWorkspaceLayout.vue';

interface PageEnvironment {
  name: string;
  profile: string;
  localOnly: boolean;
}

const props = withDefaults(
  defineProps<{
    orchestration?: TodayOrchestrationPayload;
  }>(),
  {
    orchestration: () => ({
      registeredDomainEntries: 0,
      expectedDomainEntries: 4,
      continueSession: null,
      nextAction: null,
      rationale: null,
      attentionItems: [],
      recentContext: [],
      progressProjection: null,
    }),
  },
);

const page = usePage<{
  environment: PageEnvironment;
}>();

const diagnosticsOpen = ref(false);
const refreshing = ref(false);

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
          <p class="today-command-bar__title">سطح قيادة وتنسيق اليوم</p>
          <p class="today-command-bar__meta">إدارة أولويات المسار والتوجيه بين مساحات المنصة</p>
        </div>
        <div class="today-command-bar__actions">
          <button
            type="button"
            class="cep-text-button focus-ring"
            :disabled="refreshing"
            @click="refreshOrchestration"
          >
            {{ refreshing ? 'جارٍ التحديث…' : 'إعادة قراءة الحالة' }}
          </button>
          <button
            type="button"
            class="cep-text-button focus-ring"
            data-testid="today-diagnostics-toggle"
            :aria-expanded="diagnosticsOpen"
            @click="diagnosticsOpen = !diagnosticsOpen"
          >
            {{ diagnosticsOpen ? 'إخفاء التشخيص' : 'تشخيص الربط' }}
          </button>
        </div>
      </div>
    </template>

    <template #left>
      <nav class="cep-structure-nav" aria-label="بنية سطح اليوم">
        <p class="cep-kicker">بنية السطح</p>
        <a class="cep-structure-nav__link focus-ring" href="#continue-session"> الجلسة الحالية </a>
        <a class="cep-structure-nav__link focus-ring" href="#next-action"> الإجراء التالي </a>
        <a class="cep-structure-nav__link focus-ring" href="#rationale"> مسوغ التوصية </a>
        <a class="cep-structure-nav__link focus-ring" href="#attention-items">
          بنود تحتاج انتباهك
        </a>
        <a class="cep-structure-nav__link focus-ring" href="#recent-context"> السياق الحديث </a>
        <a class="cep-structure-nav__link focus-ring" href="#progress-projection">
          التوقعات المرحلية
        </a>
        <a class="cep-structure-nav__link focus-ring" href="#workspace-handoffs"> مساحات العمل </a>
      </nav>
    </template>

    <!-- CENTER PRIMARY SURFACE: Sequential Orchestration Hierarchy -->
    <header id="today-header">
      <p class="cep-kicker">سطح قيادة وتنسيق</p>
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

    <!-- Domain Ownership Handoffs -->
    <TodayWorkspaceHandoffs />

    <template #right>
      <div class="cep-context-stack">
        <section aria-labelledby="today-scope-title">
          <p class="cep-kicker">حدود سلطة سطح اليوم</p>
          <h2 id="today-scope-title" class="cep-context-title">ما الذي يملكه سطح اليوم؟</h2>
          <ul class="today-context-list">
            <li>التوجيه والتنسيق التشغيلي بين مساحات العمل الأربع.</li>
            <li>عدم احتواء أدوات تعديل وحدات معرفية أو مراجعة أدلة داخلية.</li>
            <li>عرض الحالة العابرة فقط عندما يوجد مصدر تطبيق موثوق لها.</li>
          </ul>
        </section>

        <section aria-labelledby="today-law-title">
          <p class="cep-kicker">قوانين التقييم والقياس</p>
          <h2 id="today-law-title" class="cep-context-title">الإنجاز لا يعني الإتقان</h2>
          <ul class="today-context-list">
            <li>الإتقان لا يُقاس بنسب مئوية خادعة ولا يُكافأ بنقاط تفاعلية شكلية.</li>
            <li>كل قفل معرفي يُفتح حصريًا بالأدلة الموثوقة المحققة في مجالها الأصلي.</li>
            <li>التوقعات المرحلية تُعرض فقط إذا كانت مستندة إلى بيانات مثبتة.</li>
          </ul>
        </section>

        <section aria-labelledby="today-handoff-title">
          <p class="cep-kicker">استقلالية المجالات</p>
          <h2 id="today-handoff-title" class="cep-context-title">التوجيه للمالك المعتمد</h2>
          <p class="cep-context-copy">
            تنتقل كافة الإجراءات فورًا إلى مساحة العمل المعتمدة (<TechnicalText
              value="/knowledge"
            />، <TechnicalText value="/simulation" />، <TechnicalText value="/progress" />،
            <TechnicalText value="/system" />) لضمان اتساق البيانات.
          </p>
        </section>
      </div>
    </template>

    <template #bottom>
      <div id="today-diagnostics" class="today-diagnostics">
        <section aria-labelledby="route-registration-title">
          <p class="cep-kicker">تشخيص تقني</p>
          <h2 id="route-registration-title" class="cep-context-title">ربط مساحات العمل</h2>
          <p class="cep-context-copy">
            قراءة مباشرة من مسارات Laravel المسجلة حاليًا. هذه المعلومة تشخيصية ولا تعني اكتمال
            المنتج أو جاهزية أي مجال.
          </p>
          <dl class="cep-fact-list">
            <div class="cep-fact-list__row">
              <dt>مداخل المجالات المسجلة</dt>
              <dd><TechnicalText :value="routeRegistrationSummary" /></dd>
            </div>
            <div class="cep-fact-list__row">
              <dt>بيئة التطبيق</dt>
              <dd><TechnicalText :value="page.props.environment?.name || 'local'" /></dd>
            </div>
            <div class="cep-fact-list__row">
              <dt>ملف التشغيل</dt>
              <dd><TechnicalText :value="page.props.environment?.profile || 'development'" /></dd>
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

.today-command-bar__title {
  margin: 0;
  color: var(--cep-text);
  font-size: 0.9rem;
  font-weight: 760;
}

.today-command-bar__meta {
  margin: 0.15rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.78rem;
  line-height: 1.6;
}

.today-command-bar__actions {
  display: flex;
  flex: 0 0 auto;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.today-command-bar__actions button:disabled {
  cursor: progress;
  opacity: 0.58;
}

.today-context-list {
  display: grid;
  gap: 0.65rem;
  margin: 0.75rem 0 0;
  padding-inline-start: 1.1rem;
  color: var(--cep-text-muted);
  font-size: 0.84rem;
  line-height: 1.75;
}

.today-diagnostics {
  max-width: 44rem;
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
  }
}
</style>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { CEP_GLOBAL_DESTINATIONS } from '../../components/cep/navigation';
import CepEmptyState from '../../components/shared/CepEmptyState.vue';
import TechnicalText from '../../components/shared/TechnicalText.vue';
import CepWorkspaceLayout from '../../layouts/CepWorkspaceLayout.vue';
import type { SharedProps } from '../../types';

const props = defineProps<{
  orchestration: {
    registeredDomainEntries: number;
    expectedDomainEntries: number;
  };
}>();

const page = usePage<SharedProps>();
const diagnosticsOpen = ref(false);
const refreshing = ref(false);

const routeRegistrationSummary = computed(
  () =>
    `${props.orchestration.registeredDomainEntries}/${props.orchestration.expectedDomainEntries}`,
);

const workspaceDescriptions = {
  today: '',
  knowledge: 'الوصول إلى المعرفة والتعلّم والتصور والبحث والجودة ضمن ملكيتها الكنسية.',
  simulation: 'الانتقال إلى المؤسسة والسيناريوهات والمختبرات والتشغيل والنتائج.',
  progress: 'الانتقال إلى الأدلة والمراجعات والإتقان والمحفظة دون نسخ سجلاتها هنا.',
  system: 'الانتقال إلى الصحة والمعالجة والتحقق والجسر اليدوي والنسخ والتدقيق والإصدارات.',
} as const;

const workspaceReferences = CEP_GLOBAL_DESTINATIONS.filter(
  (destination) => destination.key !== 'today',
).map((destination) => ({
  ...destination,
  description: workspaceDescriptions[destination.key],
}));

function refreshOrchestration(): void {
  router.reload({
    only: ['orchestration'],
    preserveScroll: true,
    onStart: () => {
      refreshing.value = true;
    },
    onFinish: () => {
      refreshing.value = false;
    },
  });
}
</script>

<template>
  <Head title="اليوم" />

  <CepWorkspaceLayout
    active-destination="today"
    :temporary-workspace-open="diagnosticsOpen"
    temporary-workspace-label="تشخيص ربط سطح اليوم"
    @close-temporary-workspace="diagnosticsOpen = false"
  >
    <template #top>
      <div class="today-command-bar">
        <div class="today-command-bar__copy">
          <p class="today-command-bar__title">أوامر سطح اليوم</p>
          <p class="today-command-bar__meta">
            حدّث الحالة الحالية أو افتح التشخيص المؤقت دون مغادرة سياق العمل.
          </p>
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
        <p class="cep-kicker">اليوم</p>
        <a
          class="cep-structure-nav__link cep-structure-nav__link--active focus-ring"
          href="#today-focus"
        >
          الآن
        </a>
        <a class="cep-structure-nav__link focus-ring" href="#workspace-handoffs">
          مساحات العمل
        </a>
      </nav>
    </template>

    <section id="today-focus" aria-labelledby="today-title">
      <p class="cep-kicker">سطح قيادة وتنسيق</p>
      <h1 id="today-title" class="cep-page-title">اليوم</h1>
      <p class="cep-lede">
        نقطة بدء للعمل عبر المنصة. يعرض هذا السطح فقط الحالة التي تصل من مصادر حقيقية، ويوجّهك
        إلى مساحة العمل الكنسية المناسبة بدل إنشاء نسخ محلية من سجلات المجالات.
      </p>
    </section>

    <section class="cep-section" aria-labelledby="attention-title">
      <p class="cep-kicker">ما يحتاج انتباهك</p>
      <h2 id="attention-title" class="cep-section-title">الحالة التشغيلية المتاحة الآن</h2>
      <CepEmptyState
        class="cep-section__body"
        title="لا توجد بنود تشغيلية موثوقة مربوطة بسطح اليوم"
        description="لا يتلقى هذا السطح حاليًا مهامًا أو تنبيهات أو مواعيد أو نشاط مجال. لن تنشئ المنصة أرقامًا أو تقدمًا أو نشاطًا افتراضيًا لملء هذه المساحة."
      />
    </section>

    <section
      id="workspace-handoffs"
      class="cep-section"
      aria-labelledby="workspace-handoffs-title"
    >
      <p class="cep-kicker">انتقال يحفظ الملكية</p>
      <h2 id="workspace-handoffs-title" class="cep-section-title">اذهب إلى مساحة العمل المناسبة</h2>
      <p class="cep-context-copy today-workspace-intro">
        هذه روابط انتقال فقط. لا يعرض سطح اليوم نسخًا من السجلات الكنسية ولا يفسر حالة مجال لم
        يزوّده ببيانات فعلية.
      </p>

      <div class="today-workspace-grid" aria-label="مساحات العمل الرئيسية">
        <Link
          v-for="workspace in workspaceReferences"
          :key="workspace.key"
          :href="workspace.href"
          class="today-workspace-card focus-ring"
          :data-today-workspace="workspace.key"
        >
          <span class="today-workspace-card__title">{{ workspace.label }}</span>
          <span class="today-workspace-card__description">{{ workspace.description }}</span>
          <span class="today-workspace-card__action">فتح مساحة العمل</span>
        </Link>
      </div>
    </section>

    <template #right>
      <div class="cep-context-stack">
        <section aria-labelledby="today-context-title">
          <p class="cep-kicker">حدود السياق</p>
          <h2 id="today-context-title" class="cep-context-title">ما الذي يملكه سطح اليوم؟</h2>
          <ul class="today-context-list">
            <li>التوجيه والتنسيق بين مساحات العمل، لا ملكية السجلات الكنسية.</li>
            <li>عرض الحالة العابرة للمجالات فقط عندما يوجد مصدر تطبيق موثوق لها.</li>
            <li>تفاصيل البيئة وربط المسارات موجودة في التشخيص المؤقت، وليست معلومات دائمة.</li>
          </ul>
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
              <dd><TechnicalText :value="page.props.environment.name" /></dd>
            </div>
            <div class="cep-fact-list__row">
              <dt>ملف التشغيل</dt>
              <dd><TechnicalText :value="page.props.environment.profile" /></dd>
            </div>
          </dl>
        </section>
      </div>
    </template>
  </CepWorkspaceLayout>
</template>

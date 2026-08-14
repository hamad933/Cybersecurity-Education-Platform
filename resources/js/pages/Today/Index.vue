<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
const routeRegistrationSummary = computed(
  () => `${props.orchestration.registeredDomainEntries}/${props.orchestration.expectedDomainEntries}`,
);
</script>

<template>
  <Head title="اليوم" />

  <CepWorkspaceLayout active-destination="today">
    <template #left>
      <nav class="cep-structure-nav" aria-label="بنية مساحة التنسيق">
        <p class="cep-kicker">التنسيق</p>
        <a
          class="cep-structure-nav__link cep-structure-nav__link--active focus-ring"
          href="#orchestration"
          aria-current="page"
        >
          مساحة العمل
        </a>
      </nav>
    </template>

    <section id="orchestration" aria-labelledby="orchestration-title">
      <p class="cep-kicker">سطح توجيه</p>
      <h1 id="orchestration-title" class="cep-page-title">مساحة التنسيق</h1>
      <p class="cep-lede">
        يعرض هذا السطح فقط الحالة التي يملك التطبيق مصدرًا حقيقيًا لها، ويترك بقية المساحات
        فارغة حتى تتصل بها بيانات المجال الفعلية.
      </p>
    </section>

    <section class="cep-section" aria-labelledby="available-now-title">
      <p class="cep-kicker">المصادر الموثوقة</p>
      <h2 id="available-now-title" class="cep-section-title">ما يمكن عرضه الآن</h2>
      <CepEmptyState
        class="cep-section__body"
        title="لا توجد بيانات تشغيلية موثوقة لعرضها بعد"
        description="لم تُربط بسطح التنسيق الحالي مهام أو تنبيهات أو مواعيد أو نشاط مجال. لن تنشئ المنصة أرقامًا أو تقدمًا أو نشاطًا افتراضيًا لملء هذه المساحة."
      />
    </section>

    <template #right>
      <div class="cep-context-stack">
        <section aria-labelledby="route-registration-title">
          <p class="cep-kicker">حالة الربط</p>
          <h2 id="route-registration-title" class="cep-context-title">
            تسجيل مسارات المجالات
          </h2>
          <p class="cep-context-copy">
            قراءة مباشرة من مسارات Laravel المسجلة حاليًا، وليست مؤشرًا على اكتمال المنتج أو
            جاهزيته.
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

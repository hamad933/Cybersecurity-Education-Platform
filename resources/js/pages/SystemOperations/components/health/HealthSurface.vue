<script setup lang="ts">
import { computed, ref } from 'vue';

import type { Counts, WorkspaceState } from '../../types';
import StatusPill from '../StatusPill.vue';

type SubsystemKey = 'validation' | 'processing' | 'ai-bridge' | 'backups' | 'releases';

const props = withDefaults(
  defineProps<{
    state: WorkspaceState;
    selectedSubsystem?: SubsystemKey;
  }>(),
  {
    selectedSubsystem: 'validation',
  },
);

const emit = defineEmits<{
  'update:selectedSubsystem': [value: SubsystemKey];
  selectSubsystem: [value: SubsystemKey];
}>();

const internalSelected = ref<SubsystemKey>(props.selectedSubsystem);

const activeSubsystem = computed<SubsystemKey>({
  get: () => props.selectedSubsystem ?? internalSelected.value,
  set: (val: SubsystemKey) => {
    internalSelected.value = val;
    emit('update:selectedSubsystem', val);
    emit('selectSubsystem', val);
  },
});

const count = (counts: Counts | undefined, key: string): number => counts?.[key] ?? 0;
const displayCount = (counts: Counts | undefined, key: string): number | string =>
  !hasKeys(counts) ? '—' : count(counts, key);

const packageCounts = computed<Counts | undefined>(() => {
  if (Array.isArray(props.state.packages)) {
    const counts: Counts = {};
    for (const p of props.state.packages) {
      counts[p.status] = (counts[p.status] ?? 0) + 1;
    }
    return counts;
  }
  return props.state.packages?.counts;
});

const hasKeys = (obj: Record<string, unknown> | undefined | null) =>
  Boolean(obj && Object.keys(obj).length > 0);

const foundationChecks = computed(() => props.state.foundation?.checks);
const hasFoundationChecks = computed(() => hasKeys(foundationChecks.value));
const foundationAttention = computed(
  () =>
    hasFoundationChecks.value &&
    (props.state.foundation?.healthy === false ||
      (props.state.foundation?.failed_checks && props.state.foundation.failed_checks.length > 0)),
);

const processingHasCounts = computed(() => hasKeys(props.state.processing?.counts));
const processingFailed = computed(() => count(props.state.processing?.counts, 'failed'));
const processingRunning = computed(() => count(props.state.processing?.counts, 'running'));
const processingPending = computed(() => count(props.state.processing?.counts, 'pending'));

const packagesHasCounts = computed(() => hasKeys(packageCounts.value));
const packagesRejected = computed(() => count(packageCounts.value, 'rejected'));
const packagesExported = computed(() => count(packageCounts.value, 'exported'));

const releaseGate = computed(() => props.state.release_gate ?? props.state.readiness);
const checksObj = computed(() => releaseGate.value?.checks);
const releaseHasChecks = computed(() => hasKeys(checksObj.value));
const releaseReady = computed(() => releaseHasChecks.value && releaseGate.value?.ready === true);

const backupsAvailable = computed(() => props.state.backups !== undefined);
const backupFailed = computed(
  () => props.state.backups?.filter((backup) => backup.status === 'failed').length ?? 0,
);
const backupVerified = computed(
  () => props.state.backups?.filter((backup) => backup.status === 'verified').length ?? 0,
);
const backupHasRecords = computed(() => (props.state.backups?.length ?? 0) > 0);
const backupsHealthy = computed(
  () =>
    backupHasRecords.value &&
    backupFailed.value === 0 &&
    backupVerified.value === props.state.backups?.length,
);

const subsystems = computed(() => [
  {
    id: 'processing' as const,
    name: 'معالجة المحتوى',
    status:
      processingFailed.value > 0
        ? 'يتطلب انتباهاً'
        : processingRunning.value > 0 || processingPending.value > 0
          ? 'قيد المعالجة'
          : processingHasCounts.value
            ? 'سليم'
            : 'غير متاح',
    statusVariant:
      processingFailed.value > 0
        ? ('danger' as const)
        : processingRunning.value > 0 || processingPending.value > 0
          ? ('info' as const)
          : processingHasCounts.value
            ? ('ok' as const)
            : ('neutral' as const),
    lastCheck: 'لم تتم ملاحظته',
    note:
      processingFailed.value > 0
        ? `توجد ${processingFailed.value} معالجة فاشلة مسجلة.`
        : processingRunning.value > 0 || processingPending.value > 0
          ? `توجد ${processingRunning.value} جارية و ${processingPending.value} بانتظار المعالجة.`
          : processingHasCounts.value
            ? 'لا توجد معالجات فاشلة في الطابور.'
            : 'لم تتوفر بيانات طابور المعالجة.',
    actionLabel: 'عرض التفاصيل',
    href: '/system/processing',
  },
  {
    id: 'ai-bridge' as const,
    name: 'جسر الذكاء الاصطناعي',
    status:
      props.state.policy?.execution || props.state.prompts || props.state.results
        ? 'تشغيل يدوي'
        : 'غير متاح',
    statusVariant:
      props.state.policy?.execution || props.state.prompts || props.state.results
        ? ('info' as const)
        : ('neutral' as const),
    lastCheck: 'لم تتم ملاحظته',
    note: !(props.state.policy?.execution || props.state.prompts || props.state.results)
      ? 'بيانات الجسر غير متوفرة.'
      : props.state.policy?.automatic_provider_enabled || props.state.ai_network_provider_enabled
        ? 'المزود الآلي مفعّل في الإعدادات.'
        : 'تبادل يدوي للملفات فقط.',
    actionLabel: 'عرض المهام',
    href: '/system/ai-bridge',
  },
  {
    id: 'validation' as const,
    name: 'خدمة التحقق',
    status:
      packagesRejected.value > 0 ? 'يتطلب انتباهاً' : packagesHasCounts.value ? 'سليم' : 'غير متاح',
    statusVariant:
      packagesRejected.value > 0
        ? ('danger' as const)
        : packagesHasCounts.value
          ? ('ok' as const)
          : ('neutral' as const),
    lastCheck: 'لم تتم ملاحظته',
    note:
      packagesRejected.value > 0
        ? `توجد ${packagesRejected.value} حزم مرفوضة في السجل.`
        : packagesHasCounts.value
          ? 'جميع الحزم المسجلة مطابقة ولم تسجل أي مخالفات.'
          : 'لم تتوفر بيانات حالة الحزم.',
    actionLabel: 'عرض التقرير',
    href: '/system/validation',
  },
  {
    id: 'backups' as const,
    name: 'حالة النسخ الاحتياطي',
    status: !backupsAvailable.value
      ? 'غير متاح'
      : backupFailed.value > 0
        ? 'يتطلب انتباهاً'
        : backupsHealthy.value
          ? 'سليم'
          : backupHasRecords.value
            ? 'غير مكتمل'
            : 'لم تسجل نسخ',
    statusVariant: !backupsAvailable.value
      ? ('neutral' as const)
      : backupFailed.value > 0
        ? ('danger' as const)
        : backupsHealthy.value
          ? ('ok' as const)
          : ('neutral' as const),
    lastCheck: 'لم تتم ملاحظته',
    note: !backupsAvailable.value
      ? 'بيانات النسخ الاحتياطي غير متوفرة.'
      : backupFailed.value > 0
        ? `توجد ${backupFailed.value} نسخة احتياطية فاشلة في السجل.`
        : backupsHealthy.value
          ? `تم رصد ${backupVerified.value} نسخة احتياطية بحالة verified.`
          : backupHasRecords.value
            ? 'توجد بيانات نسخ احتياطية دون اكتمال حالة verified لجميع السجلات.'
            : 'تمت ملاحظة سجل النسخ الاحتياطي ولم تسجل فيه نسخ.',
    actionLabel: 'فتح النسخ الاحتياطي',
    href: '/system/backups',
  },
  {
    id: 'releases' as const,
    name: 'التحقق من الإصدار',
    status: releaseReady.value ? 'جاهز' : releaseHasChecks.value ? 'خامل' : 'غير متاح',
    statusVariant: releaseReady.value
      ? ('ok' as const)
      : releaseHasChecks.value
        ? ('neutral' as const)
        : ('neutral' as const),
    lastCheck: 'لم تتم ملاحظته',
    note: releaseReady.value
      ? 'جاهزية التحقق مكتملة لجميع الفحوص.'
      : releaseHasChecks.value
        ? 'بانتظار استيفاء فحوص الجاهزية.'
        : 'لا توجد عمليات تحقق مسجلة.',
    actionLabel: 'عرض التحقق',
    href: '/system/releases',
  },
]);

// Reactive Inspector Details bound to actual projected state for activeSubsystem
const inspectorDetails = computed(() => {
  switch (activeSubsystem.value) {
    case 'validation':
      return {
        subsystemName: 'خدمة التحقق',
        card1Title: 'ملخص الفحص',
        card1Result:
          packagesRejected.value > 0
            ? `توجد حزم مرفوضة (${packagesRejected.value})`
            : packagesExported.value > 0
              ? `اجتياز الفحص (${packagesExported.value} حزمة)`
              : packagesHasCounts.value
                ? 'لا توجد حزم مسجلة'
                : 'غير متاح',
        card1Badges: [
          ...(packagesRejected.value > 0
            ? [{ text: `✕ ${packagesRejected.value} مرفوضة`, variant: 'danger' }]
            : []),
          ...(packagesExported.value > 0
            ? [{ text: `✓ ${packagesExported.value} مقبولة`, variant: 'ok' }]
            : []),
          ...(packagesRejected.value === 0 && packagesExported.value === 0
            ? [
                {
                  text: packagesHasCounts.value ? 'لم تسجل حالات' : 'لم تتم ملاحظته',
                  variant: 'neutral',
                },
              ]
            : []),
        ],
        card2Title: 'حالة الحجب',
        card2Tag: !packagesHasCounts.value
          ? 'غير متاح'
          : packagesRejected.value > 0
            ? 'نشطة'
            : 'غير نشطة',
        card2TagVariant: !packagesHasCounts.value
          ? 'neutral'
          : packagesRejected.value > 0
            ? 'danger'
            : 'ok',
        card2Type: !packagesHasCounts.value
          ? 'غير متاح — لم تتم ملاحظة حالة الحجب'
          : packagesRejected.value > 0
            ? 'حجب الحزم المرفوضة'
            : 'لا يوجد حجب نشط',
        card2Source: 'سجل الحزم (portable_packages)',
        card3Details:
          packagesRejected.value > 0
            ? `تم تسجيل عدد ${packagesRejected.value} من الحزم المرفوضة في سجل التحقق التقني.`
            : packagesHasCounts.value
              ? 'جميع الحزم المسجلة مطابقة ولم تسجل أي مخالفات.'
              : 'بيانات المكوّن المحدد غير متوفرة في الحالة الحالية.',
        card3NextAction:
          packagesRejected.value > 0
            ? 'مراجعة سبب رفض الحزم في واجهة التحقق التقني.'
            : packagesHasCounts.value
              ? 'لا يتطلب أي إجراء في الوقت الحالي.'
              : 'تعذر تحديد الإجراء المطلوب قبل توفر بيانات التحقق.',
      };

    case 'processing':
      return {
        subsystemName: 'معالجة المحتوى',
        card1Title: 'ملخص الفحص',
        card1Result:
          processingFailed.value > 0
            ? `توجد معالجات فاشلة (${processingFailed.value})`
            : processingRunning.value > 0
              ? `معالجة جارية (${processingRunning.value})`
              : processingHasCounts.value
                ? 'الطابور في حالة طبيعية'
                : 'غير متاح',
        card1Badges: [
          ...(processingFailed.value > 0
            ? [{ text: `✕ ${processingFailed.value} فاشلة`, variant: 'danger' }]
            : []),
          ...(processingRunning.value > 0
            ? [{ text: `▶ ${processingRunning.value} جارية`, variant: 'info' }]
            : []),
          ...(processingPending.value > 0
            ? [{ text: `⏳ ${processingPending.value} معلقة`, variant: 'warning' }]
            : []),
          ...(processingFailed.value === 0 &&
          processingRunning.value === 0 &&
          processingPending.value === 0
            ? [
                {
                  text: processingHasCounts.value ? 'لا توجد مهام نشطة' : 'لم تتم ملاحظته',
                  variant: 'neutral',
                },
              ]
            : []),
        ],
        card2Title: 'حالة الحجب',
        card2Tag: !processingHasCounts.value
          ? 'غير متاح'
          : processingFailed.value > 0
            ? 'نشطة'
            : 'غير نشطة',
        card2TagVariant: !processingHasCounts.value
          ? 'neutral'
          : processingFailed.value > 0
            ? 'danger'
            : 'ok',
        card2Type: !processingHasCounts.value
          ? 'غير متاح — لم تتم ملاحظة حالة الحجب'
          : processingFailed.value > 0
            ? 'تعثر مهام في الطابور'
            : 'لا يوجد حجب نشط',
        card2Source: 'طابور المعالجة (processing_runs)',
        card3Details:
          processingFailed.value > 0
            ? `تم رصد عدد ${processingFailed.value} مهام معالجة فاشلة في قاعدة البيانات.`
            : processingHasCounts.value
              ? 'طوابير المعالجة والرسائل تعمل بصورة طبيعية دون إخفاقات.'
              : 'بيانات المكوّن المحدد غير متوفرة في الحالة الحالية.',
        card3NextAction:
          processingFailed.value > 0
            ? 'فحص سجلات الأخطاء في واجهة المعالجة والطوابير.'
            : processingHasCounts.value
              ? 'لا يتطلب أي إجراء في الوقت الحالي.'
              : 'تعذر تحديد الإجراء المطلوب قبل توفر بيانات المعالجة.',
      };

    case 'releases':
      return {
        subsystemName: 'التحقق من الإصدار',
        card1Title: 'ملخص الفحص',
        card1Result: releaseReady.value
          ? 'اجتياز كامل لفحوص الجاهزية'
          : releaseHasChecks.value
            ? 'بانتظار استيفاء الفحوص'
            : 'غير متاح',
        card1Badges: [
          releaseReady.value
            ? { text: '✓ جاهز', variant: 'ok' }
            : releaseHasChecks.value
              ? { text: '▲ بانتظار التحقق', variant: 'neutral' }
              : { text: 'لم تتم ملاحظته', variant: 'neutral' },
        ],
        card2Title: 'حالة الحجب',
        card2Tag: !releaseHasChecks.value
          ? 'غير متاح'
          : releaseReady.value
            ? 'غير نشطة'
            : 'حظر الإطلاق',
        card2TagVariant: releaseReady.value ? 'ok' : 'neutral',
        card2Type: !releaseHasChecks.value
          ? 'غير متاح — لم تتم ملاحظة حالة البوابة'
          : releaseReady.value
            ? 'لا يوجد حجب نشط'
            : 'سياسة حظر الإطلاق التلقائي',
        card2Source: 'بوابة الجاهزية (release_gate)',
        card3Details: releaseReady.value
          ? 'تم التحقق من جاهزية الإصدار وحزم الأدلة المرفقة.'
          : releaseHasChecks.value
            ? 'لا يمكن اعتماد الإصدار قبل استيفاء كامل الفحوص.'
            : 'بيانات المكوّن المحدد غير متوفرة في الحالة الحالية.',
        card3NextAction: releaseReady.value
          ? 'مراجعة حزم الأدلة في مركز الإصدار.'
          : releaseHasChecks.value
            ? 'استيفاء الفحوص غير المكتملة في واجهة الإصدار.'
            : 'تعذر تحديد الإجراء المطلوب قبل توفر فحوص الجاهزية.',
      };

    case 'ai-bridge': {
      const hasAi = Boolean(
        props.state.policy?.execution || props.state.prompts || props.state.results,
      );
      return {
        subsystemName: 'جسر الذكاء الاصطناعي',
        card1Title: 'ملخص الفحص',
        card1Result: hasAi ? 'تبادل ملفات يدوي' : 'غير متاح',
        card1Badges: [
          {
            text: hasAi ? 'يدوي فقط (Manual Only)' : 'لم تتم ملاحظته',
            variant: hasAi ? 'info' : 'neutral',
          },
        ],
        card2Title: 'حالة الحجب',
        card2Tag:
          props.state.policy?.automatic_provider_enabled || props.state.ai_network_provider_enabled
            ? 'اتصال مفعّل'
            : 'حظر الاتصال الآلي',
        card2TagVariant:
          props.state.policy?.automatic_provider_enabled || props.state.ai_network_provider_enabled
            ? 'info'
            : 'ok',
        card2Type:
          props.state.policy?.automatic_provider_enabled || props.state.ai_network_provider_enabled
            ? 'لا يوجد'
            : 'حظر الطلبات الشبكية التلقائية',
        card2Source: 'سياسة الجسر (policy.execution)',
        card3Details: hasAi
          ? 'يقتصر عمل الجسر على استيراد وتصدير ملفات Prompts مع مراجعة بشرية إلزامية.'
          : 'بيانات المكوّن المحدد غير متوفرة في الحالة الحالية.',
        card3NextAction: hasAi
          ? 'متابعة حزم Prompts أو مراجعة النتائج المستوردة عبر واجهة الجسر.'
          : 'تعذر تحديد الإجراء المطلوب قبل توفر بيانات الجسر.',
      };
    }

    case 'backups':
      return {
        subsystemName: 'حالة النسخ الاحتياطي',
        card1Title: 'ملخص الفحص',
        card1Result: !backupsAvailable.value
          ? 'غير متاح'
          : backupFailed.value > 0
            ? `توجد نسخ فاشلة (${backupFailed.value})`
            : backupsHealthy.value
              ? `نسخ متحققة (${backupVerified.value})`
              : backupHasRecords.value
                ? 'حالة النسخ غير مكتملة'
                : 'لم تسجل نسخ احتياطية',
        card1Badges: [
          backupFailed.value > 0
            ? { text: `✕ ${backupFailed.value} فاشلة`, variant: 'danger' }
            : backupsHealthy.value
              ? { text: `✓ ${backupVerified.value} verified`, variant: 'ok' }
              : {
                  text: backupsAvailable.value ? 'لم تسجل حالة صحية' : 'لم تتم ملاحظته',
                  variant: 'neutral',
                },
        ],
        card2Title: 'حالة الحجب',
        card2Tag: 'حظر الاستعادة عبر الويب',
        card2TagVariant: 'ok',
        card2Type: 'حظر الاستعادة عبر HTTP (CLI Only)',
        card2Source: 'ضوابط أمان المنصة (STAGE_AND_VERIFY_ONLY)',
        card3Details: backupsAvailable.value
          ? 'تخضع الاستعادة للفحص المرحلي المستقل وتتطلب التفعيل عبر سطر الأوامر CLI.'
          : 'بيانات المكوّن المحدد غير متوفرة في الحالة الحالية.',
        card3NextAction: backupsAvailable.value
          ? 'إدارة النسخ الاحتياطية وإجراء الفحص المرحلي عبر واجهة النسخ.'
          : 'تعذر تحديد الإجراء المطلوب قبل توفر بيانات النسخ الاحتياطي.',
      };

    default:
      return {
        subsystemName: 'مكوّن النظام',
        card1Title: 'ملخص الفحص',
        card1Result: 'غير متاح',
        card1Badges: [{ text: 'لم تتم ملاحظته', variant: 'neutral' }],
        card2Title: 'حالة الحجب',
        card2Tag: 'غير متاح',
        card2TagVariant: 'neutral',
        card2Type: 'غير متاح — لم تتم ملاحظة حالة الحجب',
        card2Source: 'سجل النظام',
        card3Details: 'بيانات المكوّن المحدد غير متوفرة في الحالة الحالية.',
        card3NextAction: 'تعذر تحديد الإجراء المطلوب قبل توفر بيانات المكوّن.',
      };
  }
});
</script>

<template>
  <div class="health-surface">
    <!-- Hero Status Banner -->
    <section class="hero-state" data-testid="health-hero">
      <div class="hero-state__info">
        <span class="cep-kicker">الحالة التقنية</span>
        <h2 class="hero-state__headline">
          {{
            !hasFoundationChecks
              ? 'حالة المكونات الأساسية غير متوفرة'
              : foundationAttention
                ? 'توجد فحوص أساسية تتطلب الانتباه'
                : 'المكوّنات الأساسية اجتازت فحوص الصحة'
          }}
        </h2>
        <p class="hero-state__desc">
          {{
            !hasFoundationChecks
              ? 'تعذر تحديد الحالة التشغيلية للمنصة قبل توفر نتائج الفحوص الأساسية.'
              : 'حالة المكونات وقواعد البيانات بناءً على الفحوص المسجلة للنظام.'
          }}
        </p>
      </div>
      <div class="hero-state__badge">
        <StatusPill v-if="!hasFoundationChecks" status="UNAVAILABLE" variant="neutral" />
        <StatusPill v-else-if="foundationAttention" status="ATTENTION" variant="danger" />
        <StatusPill v-else status="HEALTHY" variant="ok" />
      </div>
    </section>

    <!-- Platform Checks Grid -->
    <section v-if="hasFoundationChecks" class="cep-section">
      <h3 class="cep-section-title">فحوص المنصة</h3>
      <div class="status-grid">
        <article v-for="(status, name) in state.foundation!.checks" :key="name" class="status-card">
          <span class="status-card__name"
            ><bdi dir="ltr">{{ name }}</bdi></span
          >
          <StatusPill :status="status" />
        </article>
      </div>
    </section>

    <!-- Subsystem Health Overview Table -->
    <section class="cep-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">مراقبة الخدمات التشغيلية</h3>
        <span class="section-badge">الفحوص المسجلة</span>
      </div>

      <div class="subsystem-table-wrapper">
        <table class="subsystem-table" aria-label="جدول الخدمات التشغيلية">
          <thead>
            <tr>
              <th scope="col">المكوّن</th>
              <th scope="col">الحالة التشغيلية</th>
              <th scope="col">آخر فحص</th>
              <th scope="col">الملاحظة المختصرة</th>
              <th scope="col">الإجراء التالي</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="sub in subsystems"
              :key="sub.id"
              :class="{ 'row--selected': activeSubsystem === sub.id }"
              tabindex="0"
              role="button"
              @click="activeSubsystem = sub.id"
              @keydown.enter="activeSubsystem = sub.id"
              @keydown.space.prevent="activeSubsystem = sub.id"
            >
              <td class="cell--component">
                <span class="component-name">{{ sub.name }}</span>
              </td>
              <td>
                <StatusPill :status="sub.status" :variant="sub.statusVariant" />
              </td>
              <td class="cell--muted">{{ sub.lastCheck }}</td>
              <td class="cell--note">{{ sub.note }}</td>
              <td>
                <a :href="sub.href" class="table-action-btn" @click.stop>{{ sub.actionLabel }}</a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Component Detail Inspector (Direct Binary Fidelity - Purely Driven by Projected State) -->
    <section class="cep-section detail-inspector-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">
          تفاصيل المكوّن المحدد: {{ inspectorDetails.subsystemName }}
        </h3>
        <span class="section-subtext">
          عرض تفصيلي للحالة المرصودة من السجل الفعلي للمكوّن المحدد.
        </span>
      </div>

      <div class="detail-cards-grid">
        <!-- Card 1: Check summary -->
        <article class="detail-card">
          <h4 class="detail-card__title">{{ inspectorDetails.card1Title }}</h4>
          <dl class="detail-fact-list">
            <div class="detail-fact-row">
              <dt>تاريخ الفحص</dt>
              <dd>غير متاح</dd>
            </div>
            <div class="detail-fact-row">
              <dt>النتيجة</dt>
              <dd>{{ inspectorDetails.card1Result }}</dd>
            </div>
            <div class="detail-fact-row">
              <dt>الحالات</dt>
              <dd class="pill-states">
                <span
                  v-for="(b, i) in inspectorDetails.card1Badges"
                  :key="i"
                  :class="`micro-badge micro-badge--${b.variant}`"
                >
                  {{ b.text }}
                </span>
              </dd>
            </div>
            <div class="detail-fact-row">
              <dt>المدة</dt>
              <dd>غير متاح</dd>
            </div>
          </dl>
        </article>

        <!-- Card 2: Blocking state -->
        <article class="detail-card">
          <div class="detail-card__header-flex">
            <h4 class="detail-card__title">{{ inspectorDetails.card2Title }}</h4>
            <span :class="`micro-tag micro-tag--${inspectorDetails.card2TagVariant}`">
              {{ inspectorDetails.card2Tag }}
            </span>
          </div>
          <dl class="detail-fact-list">
            <div class="detail-fact-row">
              <dt>نوع الحجب</dt>
              <dd>{{ inspectorDetails.card2Type }}</dd>
            </div>
            <div class="detail-fact-row">
              <dt>تاريخ البداية</dt>
              <dd>لم تتم ملاحظته</dd>
            </div>
            <div class="detail-fact-row">
              <dt>المصدر</dt>
              <dd>{{ inspectorDetails.card2Source }}</dd>
            </div>
          </dl>
        </article>

        <!-- Card 3: Details & Suggested Next Action -->
        <article class="detail-card detail-card--action">
          <h4 class="detail-card__title">تفاصيل مختصرة</h4>
          <p class="detail-card__body-text">
            {{ inspectorDetails.card3Details }}
          </p>

          <div class="suggested-action-box">
            <h5 class="suggested-action-title">الإجراء المقترح</h5>
            <p class="suggested-action-desc">
              {{ inspectorDetails.card3NextAction }}
            </p>
          </div>
        </article>
      </div>
    </section>

    <!-- Operational Load Metric Strip -->
    <section class="cep-section">
      <h3 class="cep-section-title">الحمل التشغيلي المرصود</h3>
      <div class="metric-strip">
        <div class="metric-card">
          <span class="metric-label">قيد المعالجة</span>
          <strong class="metric-value" data-testid="metric-processing-running">{{
            displayCount(state.processing?.counts, 'running')
          }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">بانتظار المعالجة</span>
          <strong class="metric-value" data-testid="metric-processing-pending">{{
            displayCount(state.processing?.counts, 'pending')
          }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">فشل معالجة</span>
          <strong class="metric-value" data-testid="metric-processing-failed">{{
            displayCount(state.processing?.counts, 'failed')
          }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">رسائل Outbox فاشلة</span>
          <strong class="metric-value" data-testid="metric-outbox-failed">{{
            displayCount(state.outbox?.counts, 'failed')
          }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">حزم مرفوضة</span>
          <strong class="metric-value" data-testid="metric-packages-rejected">{{
            displayCount(packageCounts, 'rejected')
          }}</strong>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.health-surface {
  display: grid;
  gap: 1.5rem;
}

.hero-state {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  padding: 1.35rem 1.6rem;
  border-radius: var(--cep-radius-lg);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  box-shadow: var(--cep-shadow);
  position: relative;
  overflow: hidden;
}

.hero-state::after {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 6rem;
  height: 100%;
  background: linear-gradient(to left, rgba(34, 211, 238, 0.05), transparent);
  pointer-events: none;
}

.hero-state__info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.hero-state__headline {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--cep-text);
  letter-spacing: -0.01em;
}

.hero-state__desc {
  margin: 0;
  font-size: 0.88rem;
  color: var(--cep-text-muted);
  line-height: 1.6;
}

.hero-state__badge {
  flex-shrink: 0;
}

.status-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(13.5rem, 1fr));
  gap: 0.75rem;
  margin-top: 0.85rem;
}

.status-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.85rem 1.1rem;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  transition: all 140ms ease;
}

.status-card:hover {
  border-color: var(--cep-border-strong);
  background: var(--cep-bg-panel);
}

.status-card__name {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--cep-text);
}

.section-header-flex {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.85rem;
}

.section-badge {
  font-size: 0.72rem;
  font-weight: 750;
  color: var(--cep-accent);
  background: var(--cep-accent-soft);
  padding: 0.25rem 0.6rem;
  border-radius: var(--cep-radius-sm);
  border: 1px solid rgba(34, 211, 238, 0.2);
}

.section-subtext {
  font-size: 0.82rem;
  color: var(--cep-text-muted);
}

.subsystem-table-wrapper {
  overflow-x: auto;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-lg);
  background: var(--cep-bg-panel-strong);
  box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.25);
}

.subsystem-table {
  width: 100%;
  border-collapse: collapse;
  text-align: right;
  font-size: 0.88rem;
}

.subsystem-table th {
  padding: 0.9rem 1.1rem;
  background: var(--cep-bg-panel);
  color: var(--cep-text-muted);
  font-weight: 750;
  font-size: 0.8rem;
  border-bottom: 1px solid var(--cep-border);
  letter-spacing: 0.02em;
}

.subsystem-table td {
  padding: 0.95rem 1.1rem;
  border-bottom: 1px solid var(--cep-border);
  color: var(--cep-text);
  vertical-align: middle;
}

.subsystem-table tr:last-child td {
  border-bottom: none;
}

.subsystem-table tbody tr {
  cursor: pointer;
  transition: background 140ms ease;
}

.subsystem-table tbody tr:hover {
  background: rgba(34, 211, 238, 0.06);
}

.subsystem-table tr.row--selected {
  background: rgba(34, 211, 238, 0.1);
  border-inline-start: 3px solid var(--cep-accent);
}

.cell--component {
  font-weight: 750;
  color: var(--cep-text);
}

.component-name {
  font-size: 0.9rem;
}

.cell--muted {
  color: var(--cep-text-muted);
  font-size: 0.82rem;
}

.cell--note {
  font-size: 0.84rem;
  color: var(--cep-text-muted);
  max-width: 22rem;
  line-height: 1.5;
}

.table-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.85rem;
  border-radius: var(--cep-radius-sm);
  border: 1px solid var(--cep-border-strong);
  background: var(--cep-bg-panel);
  color: var(--cep-accent);
  font-size: 0.8rem;
  font-weight: 750;
  text-decoration: none;
  transition: all 140ms ease;
  white-space: nowrap;
}

.table-action-btn:hover {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
  box-shadow: 0 0 10px rgba(34, 211, 238, 0.2);
}

.detail-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr));
  gap: 1rem;
  margin-top: 0.85rem;
}

.detail-card {
  padding: 1.25rem;
  border-radius: var(--cep-radius-lg);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  display: flex;
  flex-direction: column;
  gap: 0.95rem;
  box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.2);
}

.detail-card__title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--cep-text);
}

.detail-card__header-flex {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.micro-tag {
  font-size: 0.72rem;
  font-weight: 800;
  padding: 0.18rem 0.55rem;
  border-radius: var(--cep-radius-sm);
  text-transform: uppercase;
}

.micro-tag--danger {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.micro-tag--ok {
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.micro-tag--neutral {
  background: rgba(148, 163, 184, 0.12);
  color: #94a3b8;
  border: 1px solid rgba(148, 163, 184, 0.25);
}

.detail-fact-list {
  display: grid;
  gap: 0.65rem;
  margin: 0;
}

.detail-fact-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.5rem;
  font-size: 0.84rem;
  padding-bottom: 0.4rem;
  border-bottom: 1px dashed rgba(51, 65, 85, 0.4);
}

.detail-fact-row:last-child {
  padding-bottom: 0;
  border-bottom: none;
}

.detail-fact-row dt {
  color: var(--cep-text-muted);
  font-size: 0.82rem;
}

.detail-fact-row dd {
  margin: 0;
  font-weight: 750;
  color: var(--cep-text);
}

.pill-states {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.micro-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  font-size: 0.72rem;
  font-weight: 750;
  padding: 0.15rem 0.45rem;
  border-radius: var(--cep-radius-sm);
}

.micro-badge--danger {
  background: rgba(239, 68, 68, 0.18);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.micro-badge--warning {
  background: rgba(245, 158, 11, 0.18);
  color: #fbbf24;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.micro-badge--ok {
  background: rgba(34, 197, 94, 0.18);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.micro-badge--info {
  background: rgba(34, 211, 238, 0.18);
  color: #38bdf8;
  border: 1px solid rgba(34, 211, 238, 0.3);
}

.micro-badge--neutral {
  background: rgba(148, 163, 184, 0.12);
  color: #94a3b8;
  border: 1px solid rgba(148, 163, 184, 0.25);
}

.detail-card__body-text {
  margin: 0;
  font-size: 0.86rem;
  color: var(--cep-text-muted);
  line-height: 1.65;
}

.suggested-action-box {
  margin-top: auto;
  padding: 0.85rem 1rem;
  border-radius: var(--cep-radius-md);
  background: var(--cep-accent-soft);
  border: 1px dashed var(--cep-border-strong);
}

.suggested-action-title {
  margin: 0 0 0.3rem;
  font-size: 0.82rem;
  font-weight: 800;
  color: var(--cep-accent);
}

.suggested-action-desc {
  margin: 0;
  font-size: 0.82rem;
  color: var(--cep-text);
  line-height: 1.5;
}

.metric-strip {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(9.5rem, 1fr));
  gap: 0.85rem;
  margin-top: 0.85rem;
}

.metric-card {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding: 0.95rem 1.1rem;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  transition: all 140ms ease;
}

.metric-card:hover {
  border-color: var(--cep-border-strong);
  transform: translateY(-1px);
}

.metric-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--cep-text-muted);
}

.metric-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--cep-text);
  letter-spacing: -0.02em;
}
</style>

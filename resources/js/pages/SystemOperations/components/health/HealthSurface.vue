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

const packageCounts = computed<Counts>(() => {
  if (Array.isArray(props.state.packages)) {
    const counts: Counts = {};
    for (const p of props.state.packages) {
      counts[p.status] = (counts[p.status] ?? 0) + 1;
    }
    return counts;
  }
  return props.state.packages?.counts ?? {};
});

const isHealthy = computed(() => props.state.foundation?.healthy ?? false);

const processingFailed = computed(() => count(props.state.processing?.counts, 'failed'));
const processingRunning = computed(() => count(props.state.processing?.counts, 'running'));
const processingPending = computed(() => count(props.state.processing?.counts, 'pending'));
const packagesRejected = computed(() => count(packageCounts.value, 'rejected'));
const packagesExported = computed(() => count(packageCounts.value, 'exported'));
const releaseReady = computed(
  () => props.state.release_gate?.ready ?? props.state.readiness?.ready ?? false,
);
const releaseHasChecks = computed(() =>
  Boolean(props.state.release_gate?.checks || props.state.readiness?.checks),
);
const storageOk = computed(() => props.state.foundation?.checks?.storage === 'ok');

const subsystems = computed(() => [
  {
    id: 'processing' as const,
    name: 'معالجة المحتوى',
    status:
      processingFailed.value > 0
        ? 'يتطلب انتباهاً'
        : processingRunning.value > 0 || processingPending.value > 0
          ? 'قيد المعالجة'
          : props.state.processing?.counts
            ? 'سليم'
            : 'غير متاح',
    statusVariant:
      processingFailed.value > 0
        ? ('danger' as const)
        : processingRunning.value > 0 || processingPending.value > 0
          ? ('info' as const)
          : props.state.processing?.counts
            ? ('ok' as const)
            : ('neutral' as const),
    lastCheck: 'لم تتم ملاحظته',
    note:
      processingFailed.value > 0
        ? `توجد ${processingFailed.value} معالجة فاشلة مسجلة.`
        : processingRunning.value > 0 || processingPending.value > 0
          ? `توجد ${processingRunning.value} جارية و ${processingPending.value} بانتظار المعالجة.`
          : props.state.processing?.counts
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
        : 'خامل',
    statusVariant:
      props.state.policy?.execution || props.state.prompts || props.state.results
        ? ('info' as const)
        : ('neutral' as const),
    lastCheck: 'لم تتم ملاحظته',
    note:
      props.state.policy?.automatic_provider_enabled || props.state.ai_network_provider_enabled
        ? 'المزود الآلي مفعّل في الإعدادات.'
        : 'تبادل يدوي للملفات فقط.',
    actionLabel: 'عرض المهام',
    href: '/system/ai-bridge',
  },
  {
    id: 'validation' as const,
    name: 'خدمة التحقق',
    status:
      packagesRejected.value > 0
        ? 'يتطلب انتباهاً'
        : packagesExported.value > 0 || props.state.packages
          ? 'سليم'
          : 'غير متاح',
    statusVariant:
      packagesRejected.value > 0
        ? ('danger' as const)
        : packagesExported.value > 0 || props.state.packages
          ? ('ok' as const)
          : ('neutral' as const),
    lastCheck: 'لم تتم ملاحظته',
    note:
      packagesRejected.value > 0
        ? `توجد ${packagesRejected.value} حزم مرفوضة في السجل.`
        : packagesExported.value > 0
          ? `توجد ${packagesExported.value} حزم مجازة بدون إخفاقات.`
          : 'لا توجد حزم مرفوضة مسجلة.',
    actionLabel: 'عرض التقرير',
    href: '/system/validation',
  },
  {
    id: 'backups' as const,
    name: 'حالة النسخ الاحتياطي',
    status:
      props.state.foundation?.checks?.storage !== undefined
        ? storageOk.value
          ? 'سليم'
          : 'يتطلب انتباهاً'
        : 'غير متاح',
    statusVariant:
      props.state.foundation?.checks?.storage !== undefined
        ? storageOk.value
          ? ('ok' as const)
          : ('danger' as const)
        : ('neutral' as const),
    lastCheck: 'لم تتم ملاحظته',
    note:
      props.state.foundation?.checks?.storage === 'failed'
        ? 'فحص وحدة التخزين المحلي غير سليم.'
        : storageOk.value
          ? 'جاهزية التخزين المحلي للنسخ الاحتياطي.'
          : 'بيانات التخزين غير متوفرة في الحالة الحالية.',
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
              : 'لا توجد حزم مسجلة',
        card1Badges: [
          ...(packagesRejected.value > 0
            ? [{ text: `✕ ${packagesRejected.value} مرفوضة`, variant: 'danger' }]
            : []),
          ...(packagesExported.value > 0
            ? [{ text: `✓ ${packagesExported.value} مقبولة`, variant: 'ok' }]
            : []),
          ...(packagesRejected.value === 0 && packagesExported.value === 0
            ? [{ text: 'لم تسجل حالات', variant: 'neutral' }]
            : []),
        ],
        card2Title: 'حالة الحجب',
        card2Tag: packagesRejected.value > 0 ? 'نشطة' : 'غير نشطة',
        card2TagVariant: packagesRejected.value > 0 ? 'danger' : 'ok',
        card2Type: packagesRejected.value > 0 ? 'حجب الحزم المرفوضة' : 'لا يوجد حجب نشط',
        card2Source: 'سجل الحزم (portable_packages)',
        card3Details:
          packagesRejected.value > 0
            ? `تم تسجيل عدد ${packagesRejected.value} من الحزم المرفوضة في سجل التحقق التقني.`
            : 'جميع الحزم المسجلة مطابقة ولم تسجل أي مخالفات.',
        card3NextAction:
          packagesRejected.value > 0
            ? 'مراجعة سبب رفض الحزم في واجهة التحقق التقني.'
            : 'لا يتطلب أي إجراء في الوقت الحالي.',
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
              : 'الطابور في حالة طبيعية',
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
            ? [{ text: 'لا توجد مهام نشطة', variant: 'neutral' }]
            : []),
        ],
        card2Title: 'حالة الحجب',
        card2Tag: processingFailed.value > 0 ? 'نشطة' : 'غير نشطة',
        card2TagVariant: processingFailed.value > 0 ? 'danger' : 'ok',
        card2Type: processingFailed.value > 0 ? 'تعثر مهام في الطابور' : 'لا يوجد حجب نشط',
        card2Source: 'طابور المعالجة (processing_runs)',
        card3Details:
          processingFailed.value > 0
            ? `تم رصد عدد ${processingFailed.value} مهام معالجة فاشلة في قاعدة البيانات.`
            : 'طوابير المعالجة والرسائل تعمل بصورة طبيعية دون إخفاقات.',
        card3NextAction:
          processingFailed.value > 0
            ? 'فحص سجلات الأخطاء في واجهة المعالجة والطوابير.'
            : 'لا يتطلب أي إجراء في الوقت الحالي.',
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
            : { text: '▲ بانتظار التحقق', variant: 'neutral' },
        ],
        card2Title: 'حالة الحجب',
        card2Tag: releaseReady.value ? 'غير نشطة' : 'حظر الإطلاق',
        card2TagVariant: releaseReady.value ? 'ok' : 'neutral',
        card2Type: releaseReady.value ? 'لا يوجد حجب نشط' : 'سياسة حظر الإطلاق التلقائي',
        card2Source: 'بوابة الجاهزية (release_gate)',
        card3Details: releaseReady.value
          ? 'تم التحقق من جاهزية الإصدار وحزم الأدلة المرفقة.'
          : 'لا يمكن اعتماد الإصدار قبل استيفاء كامل الفحوص.',
        card3NextAction: releaseReady.value
          ? 'مراجعة حزم الأدلة في مركز الإصدار.'
          : 'استيفاء الفحوص غير المكتملة في واجهة الإصدار.',
      };

    case 'ai-bridge':
      return {
        subsystemName: 'جسر الذكاء الاصطناعي',
        card1Title: 'ملخص الفحص',
        card1Result: 'تبادل ملفات يدوي',
        card1Badges: [{ text: 'يدوي فقط (Manual Only)', variant: 'info' }],
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
        card3Details: 'يقتصر عمل الجسر على استيراد وتصدير ملفات Prompts مع مراجعة بشرية إلزامية.',
        card3NextAction: 'متابعة حزم Prompts أو مراجعة النتائج المستوردة عبر واجهة الجسر.',
      };

    case 'backups':
      return {
        subsystemName: 'حالة النسخ الاحتياطي',
        card1Title: 'ملخص الفحص',
        card1Result: storageOk.value
          ? 'وحدة التخزين متاحة'
          : props.state.foundation?.checks?.storage === 'failed'
            ? 'فحص التخزين غير سليم'
            : 'غير متاح',
        card1Badges: [
          storageOk.value
            ? { text: '✓ سليم', variant: 'ok' }
            : { text: 'لم تتم ملاحظته', variant: 'neutral' },
        ],
        card2Title: 'حالة الحجب',
        card2Tag: 'حظر الاستعادة عبر الويب',
        card2TagVariant: 'ok',
        card2Type: 'حظر الاستعادة عبر HTTP (CLI Only)',
        card2Source: 'ضوابط أمان المنصة (STAGE_AND_VERIFY_ONLY)',
        card3Details: 'تخضع الاستعادة للفحص المرحلي المستقل وتتطلب التفعيل عبر سطر الأوامر CLI.',
        card3NextAction: 'إدارة النسخ الاحتياطية وإجراء الفحص المرحلي عبر واجهة النسخ.',
      };

    default:
      return {
        subsystemName: 'مكوّن النظام',
        card1Title: 'ملخص الفحص',
        card1Result: 'غير متاح',
        card1Badges: [{ text: 'لم تتم ملاحظته', variant: 'neutral' }],
        card2Title: 'حالة الحجب',
        card2Tag: 'غير نشطة',
        card2TagVariant: 'neutral',
        card2Type: 'لا يوجد حجب نشط',
        card2Source: 'سجل النظام',
        card3Details: 'بيانات المكوّن المحدد غير متوفرة في الحالة الحالية.',
        card3NextAction: 'لا يلزم أي إجراء.',
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
            isHealthy ? 'المكوّنات الأساسية اجتازت فحوص الصحة' : 'توجد فحوص أساسية تتطلب الانتباه'
          }}
        </h2>
        <p class="hero-state__desc">
          حالة المكونات وقواعد البيانات بناءً على الفحوص المسجلة للنظام.
        </p>
      </div>
      <div class="hero-state__badge">
        <StatusPill
          :status="isHealthy ? 'HEALTHY' : 'ATTENTION'"
          :variant="isHealthy ? 'ok' : 'danger'"
        />
      </div>
    </section>

    <!-- Platform Checks Grid -->
    <section v-if="state.foundation?.checks" class="cep-section">
      <h3 class="cep-section-title">فحوص المنصة</h3>
      <div class="status-grid">
        <article v-for="(status, name) in state.foundation.checks" :key="name" class="status-card">
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
          <strong class="metric-value">{{ count(state.processing?.counts, 'running') }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">بانتظار المعالجة</span>
          <strong class="metric-value">{{ count(state.processing?.counts, 'pending') }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">فشل معالجة</span>
          <strong class="metric-value">{{ count(state.processing?.counts, 'failed') }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">رسائل Outbox فاشلة</span>
          <strong class="metric-value">{{ count(state.outbox?.counts, 'failed') }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">حزم مرفوضة</span>
          <strong class="metric-value">{{ count(packageCounts, 'rejected') }}</strong>
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
  padding: 1.25rem 1.5rem;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
}

.hero-state__headline {
  margin: 0.25rem 0 0.4rem;
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--cep-text);
}

.hero-state__desc {
  margin: 0;
  font-size: 0.88rem;
  color: var(--cep-text-muted);
  line-height: 1.6;
}

.status-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr));
  gap: 0.75rem;
  margin-top: 0.85rem;
}

.status-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.85rem 1rem;
  border-radius: var(--cep-radius-sm);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
}

.status-card__name {
  font-size: 0.85rem;
  font-weight: 650;
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
  font-weight: 700;
  color: var(--cep-accent);
  background: var(--cep-accent-soft);
  padding: 0.2rem 0.5rem;
  border-radius: var(--cep-radius-sm);
}

.section-subtext {
  font-size: 0.82rem;
  color: var(--cep-text-muted);
}

.subsystem-table-wrapper {
  overflow-x: auto;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
}

.subsystem-table {
  width: 100%;
  border-collapse: collapse;
  text-align: right;
  font-size: 0.88rem;
}

.subsystem-table th {
  padding: 0.8rem 1rem;
  background: var(--cep-bg-panel);
  color: var(--cep-text-muted);
  font-weight: 700;
  font-size: 0.8rem;
  border-bottom: 1px solid var(--cep-border);
}

.subsystem-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--cep-border);
  color: var(--cep-text);
}

.subsystem-table tr:last-child td {
  border-bottom: none;
}

.subsystem-table tr:hover,
.subsystem-table tr.row--selected {
  background: var(--cep-accent-soft);
}

.cell--component {
  font-weight: 750;
}

.cell--muted {
  color: var(--cep-text-muted);
  font-size: 0.82rem;
}

.cell--note {
  font-size: 0.84rem;
  color: var(--cep-text-muted);
}

.table-action-btn {
  display: inline-block;
  padding: 0.35rem 0.75rem;
  border-radius: var(--cep-radius-sm);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel);
  color: var(--cep-accent);
  font-size: 0.8rem;
  font-weight: 700;
  text-decoration: none;
  transition: all 140ms ease;
}

.table-action-btn:hover {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
}

.detail-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
  gap: 1rem;
  margin-top: 0.85rem;
}

.detail-card {
  padding: 1.1rem;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.detail-card__title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 750;
  color: var(--cep-text);
}

.detail-card__header-flex {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.micro-tag {
  font-size: 0.7rem;
  font-weight: 800;
  padding: 0.15rem 0.45rem;
  border-radius: var(--cep-radius-sm);
}

.micro-tag--danger {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
}

.detail-fact-list {
  display: grid;
  gap: 0.5rem;
  margin: 0;
}

.detail-fact-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.5rem;
  font-size: 0.84rem;
}

.detail-fact-row dt {
  color: var(--cep-text-muted);
}

.detail-fact-row dd {
  margin: 0;
  font-weight: 700;
  color: var(--cep-text);
}

.pill-states {
  display: flex;
  gap: 0.35rem;
}

.micro-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  font-size: 0.72rem;
  font-weight: 750;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
}

.micro-badge--danger {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
}

.micro-badge--warning {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
}

.micro-badge--ok {
  background: rgba(34, 197, 94, 0.2);
  color: #4ade80;
}

.detail-card__body-text {
  margin: 0;
  font-size: 0.85rem;
  color: var(--cep-text-muted);
  line-height: 1.6;
}

.suggested-action-box {
  margin-top: auto;
  padding: 0.75rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent-soft);
  border: 1px dashed var(--cep-border-strong);
}

.suggested-action-title {
  margin: 0 0 0.25rem;
  font-size: 0.8rem;
  font-weight: 750;
  color: var(--cep-accent);
}

.suggested-action-desc {
  margin: 0;
  font-size: 0.8rem;
  color: var(--cep-text);
  line-height: 1.4;
}

.metric-strip {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
  gap: 0.75rem;
  margin-top: 0.85rem;
}

.metric-card {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding: 0.85rem 1rem;
  border-radius: var(--cep-radius-sm);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
}

.metric-label {
  font-size: 0.78rem;
  color: var(--cep-text-muted);
}

.metric-value {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--cep-text);
}
</style>

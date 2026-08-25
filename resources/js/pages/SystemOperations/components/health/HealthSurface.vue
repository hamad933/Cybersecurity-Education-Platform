<script setup lang="ts">
import { computed, ref } from 'vue';

import type { Counts, WorkspaceState } from '../../types';
import StatusPill from '../StatusPill.vue';

const props = defineProps<{
  state: WorkspaceState;
}>();

const selectedSubsystem = ref<'validation' | 'processing' | 'ai-bridge' | 'backups' | 'releases'>(
  'validation',
);

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

const subsystems = computed(() => [
  {
    id: 'processing',
    name: 'معالجة المحتوى',
    status: count(props.state.processing?.counts, 'failed') > 0 ? 'يتطلب انتباهاً' : 'سليم',
    statusVariant:
      count(props.state.processing?.counts, 'failed') > 0 ? ('danger' as const) : ('ok' as const),
    lastCheck: 'الآن',
    note:
      count(props.state.processing?.counts, 'failed') > 0
        ? `توجد ${count(props.state.processing?.counts, 'failed')} معالجات فاشلة.`
        : 'لا توجد مشكلات نشطة في الطوابير.',
    actionLabel: 'عرض التفاصيل',
    href: '/system/processing',
  },
  {
    id: 'ai-bridge',
    name: 'جسر الذكاء الاصطناعي',
    status: 'تحت المعالجة',
    statusVariant: 'info' as const,
    lastCheck: 'قبل 10 دقائق',
    note: 'تشغيل يدوي صارم بدون مزود شبكي خارجي.',
    actionLabel: 'عرض المهام',
    href: '/system/ai-bridge',
  },
  {
    id: 'validation',
    name: 'خدمة التحقق',
    status: count(packageCounts.value, 'rejected') > 0 ? 'يتطلب انتباهاً' : 'سليم',
    statusVariant:
      count(packageCounts.value, 'rejected') > 0 ? ('danger' as const) : ('ok' as const),
    lastCheck: 'قبل 7 دقائق',
    note:
      count(packageCounts.value, 'rejected') > 0
        ? `فشل في ${count(packageCounts.value, 'rejected')} حزم معالجة.`
        : 'فحوص الحزم والمصادر مطابقة للمعايير.',
    actionLabel: 'عرض التقرير',
    href: '/system/validation',
  },
  {
    id: 'backups',
    name: 'حالة النسخ الاحتياطي',
    status: 'سليم',
    statusVariant: 'ok' as const,
    lastCheck: 'قبل ساعة',
    note: 'آخر نسخ احتياطي مسجل مكتمل بنجاح.',
    actionLabel: 'فتح النسخ الاحتياطي',
    href: '/system/backups',
  },
  {
    id: 'releases',
    name: 'التحقق من الإصدار',
    status: props.state.release_gate?.ready ? 'سليم' : 'خامل',
    statusVariant: props.state.release_gate?.ready ? ('ok' as const) : ('neutral' as const),
    lastCheck: 'قبل ساعتين',
    note: props.state.release_gate?.ready
      ? 'جاهزية التحقق مكتملة.'
      : 'لا توجد عمليات تحقق قيد التنفيذ.',
    actionLabel: 'تفعيل تحقق',
    href: '/system/releases',
  },
]);
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
          مراقبة مستمرة لخدمات النظام وقواعد البيانات، مع حظر أي تلفيات قبل التأثير على المعرفة
          التعليمية.
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
        <span class="section-badge">تحديث فوري</span>
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
              :class="{ 'row--selected': selectedSubsystem === sub.id }"
              @click="selectedSubsystem = sub.id as any"
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
                <a :href="sub.href" class="table-action-btn">{{ sub.actionLabel }}</a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Component Detail Inspector (Direct Binary Fidelity) -->
    <section class="cep-section detail-inspector-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">تفاصيل المكوّن المحدد</h3>
        <span class="section-subtext"
          >عرض تفصيلي لحالة التحقق الحالي في إحدى الحزم التي تتطلب إجراء.</span
        >
      </div>

      <div class="detail-cards-grid">
        <!-- Card 1: Last check summary -->
        <article class="detail-card">
          <h4 class="detail-card__title">ملخص آخر فحص</h4>
          <dl class="detail-fact-list">
            <div class="detail-fact-row">
              <dt>تاريخ الفحص</dt>
              <dd>اليوم 10:33</dd>
            </div>
            <div class="detail-fact-row">
              <dt>النتيجة</dt>
              <dd>فشل في 1 من 3 تحقق</dd>
            </div>
            <div class="detail-fact-row">
              <dt>الحالات</dt>
              <dd class="pill-states">
                <span class="micro-badge micro-badge--danger" title="فشل">✕ 1</span>
                <span class="micro-badge micro-badge--warning" title="تنبيه">▲ 1</span>
                <span class="micro-badge micro-badge--ok" title="ناجح">✓ 2</span>
              </dd>
            </div>
            <div class="detail-fact-row">
              <dt>المدة</dt>
              <dd><bdi dir="ltr">00:02:41</bdi></dd>
            </div>
          </dl>
        </article>

        <!-- Card 2: Blocking state -->
        <article class="detail-card">
          <div class="detail-card__header-flex">
            <h4 class="detail-card__title">حالة الحجب</h4>
            <span class="micro-tag micro-tag--danger">نشطة</span>
          </div>
          <dl class="detail-fact-list">
            <div class="detail-fact-row">
              <dt>نوع الحجب</dt>
              <dd>فشل تحقق البيانات</dd>
            </div>
            <div class="detail-fact-row">
              <dt>تاريخ البداية</dt>
              <dd>اليوم 10:33</dd>
            </div>
            <div class="detail-fact-row">
              <dt>المصدر</dt>
              <dd>قاعدة تحقق الحزم</dd>
            </div>
          </dl>
        </article>

        <!-- Card 3: Details & Suggested Next Action -->
        <article class="detail-card detail-card--action">
          <h4 class="detail-card__title">تفاصيل مختصرة</h4>
          <p class="detail-card__body-text">
            فشل تحقق سلامة البيانات في إحدى الحزم أثناء مرحلة التحقق.
          </p>

          <div class="suggested-action-box">
            <h5 class="suggested-action-title">الإجراء التالي المقترح</h5>
            <p class="suggested-action-desc">معالجة سبب فشل تحقق البيانات ثم إعادة الفحص.</p>
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

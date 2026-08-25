<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import type { Counts, DeepSection, ProcessingRun, WorkspaceState } from '../../types';
import StatusPill from '../StatusPill.vue';

defineProps<{
  state: WorkspaceState;
}>();

const emit = defineEmits<{
  openDeep: [title: string, sections: DeepSection[]];
}>();

const count = (counts: Counts | undefined, key: string): number | string =>
  !counts || Object.keys(counts).length === 0 ? '—' : (counts[key] ?? 0);
const when = (value: string | null | undefined): string =>
  value ? new Date(value).toLocaleString('ar-YE') : '—';

const canCancel = (status: string): boolean => status === 'pending' || status === 'running';

const cancelProcessing = (runId: string) => {
  router.post(`/system/processing/runs/${runId}/cancel`, {}, { preserveScroll: true });
};

const inspectRun = (run: ProcessingRun) => {
  emit('openDeep', `تشخيص المعالجة — ${run.type}`, [
    { label: 'معرّف المعالجة (Run ID)', value: run.id },
    { label: 'نوع المعالجة (Type)', value: run.type },
    { label: 'الحالة الحالية (Status)', value: run.status },
    { label: 'بصمة المدخلات (Input Digest)', value: run.input_digest },
    { label: 'معرّف العامل (Worker Identifier)', value: run.worker_identifier ?? '—' },
    { label: 'المحاولات (Attempts)', value: `${run.attempt_count} / ${run.max_attempts}` },
    { label: 'فئة الخطأ (Error Category)', value: run.error_category ?? 'لا يوجد' },
    {
      label: 'رسالة الخطأ الآمنة (Safe Error Message)',
      value: run.safe_error_message ?? 'لا يوجد أخطاء مسجلة',
    },
    { label: 'تاريخ البدء (Started At)', value: when(run.started_at) },
    { label: 'تاريخ الانتهاء (Completed At)', value: when(run.completed_at) },
    { label: 'تاريخ الإلغاء (Cancelled At)', value: when(run.cancelled_at) },
  ]);
};
</script>

<template>
  <div class="processing-surface">
    <!-- Header Section -->
    <section class="cep-section-top">
      <span class="cep-kicker" dir="ltr">REQUESTED → EXECUTED / CURRENT STATE</span>
      <h2 class="cep-page-title-md">المعالجة الفعلية مع ربط الطلب بالتنفيذ</h2>
      <p class="cep-lede-sm">
        تتبع مسار المهام التشغيلية من وقت الطلب حتى اكتمال التنفيذ أو الفشل، مع إتاحة الإلغاء المقيد
        للحالات القابلة للإجراء.
      </p>

      <!-- Compact Metric Strip -->
      <div class="metric-strip compact">
        <div class="metric-card">
          <span class="metric-label">Pending</span>
          <strong class="metric-value" data-testid="processing-count-pending">{{
            count(state.processing?.counts, 'pending')
          }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">Running</span>
          <strong class="metric-value" data-testid="processing-count-running">{{
            count(state.processing?.counts, 'running')
          }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">Completed</span>
          <strong class="metric-value" data-testid="processing-count-completed">{{
            count(state.processing?.counts, 'completed')
          }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">Failed</span>
          <strong class="metric-value" data-testid="processing-count-failed">{{
            count(state.processing?.counts, 'failed')
          }}</strong>
        </div>
      </div>
    </section>

    <!-- Processing Runs List -->
    <section class="cep-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">سجل المعالجات التشغيلية (Processing Runs)</h3>
        <span class="section-subtext">أحدث 30 تشغيل مسجل في النظام</span>
      </div>

      <div
        v-if="!state.processing?.runs || state.processing.runs.length === 0"
        class="cep-empty-state"
      >
        <p class="cep-empty-state__title">لا توجد معالجات تشغيلية مسجلة حالياً</p>
      </div>

      <div v-else class="record-list">
        <article v-for="run in state.processing.runs" :key="run.id" class="trace-card">
          <div class="trace-heading">
            <div class="trace-identity">
              <strong class="trace-type"
                ><bdi dir="ltr">{{ run.type }}</bdi></strong
              >
              <small class="trace-id"
                ><bdi dir="ltr">{{ run.id }}</bdi></small
              >
            </div>
            <StatusPill :status="run.status" />
          </div>

          <dl class="trace-grid">
            <div class="trace-fact">
              <dt>REQUESTED · input digest</dt>
              <dd class="mono break-all" dir="ltr">{{ run.input_digest }}</dd>
            </div>
            <div class="trace-fact">
              <dt>EXECUTED · attempts</dt>
              <dd dir="ltr">{{ run.attempt_count }} / {{ run.max_attempts }}</dd>
            </div>
            <div class="trace-fact">
              <dt>CURRENT · worker</dt>
              <dd>
                <bdi dir="ltr">{{ run.worker_identifier ?? '—' }}</bdi>
              </dd>
            </div>
            <div class="trace-fact">
              <dt>TIMESTAMPS · created / completed</dt>
              <dd>{{ when(run.created_at) }} / {{ when(run.completed_at) }}</dd>
            </div>
          </dl>

          <div class="trace-actions">
            <button type="button" class="cep-text-button btn-inspect" @click="inspectRun(run)">
              فتح التشخيص
            </button>

            <button
              v-if="canCancel(run.status)"
              type="button"
              class="cep-text-button btn-cancel danger-button"
              @click="cancelProcessing(run.id)"
            >
              إلغاء التشغيل
            </button>
          </div>
        </article>
      </div>
    </section>

    <!-- Outbox Queue State -->
    <section class="cep-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">رسائل Outbox التشغيلية</h3>
        <span class="section-subtext">توزيع رسائل النشر غير التزامنية</span>
      </div>

      <div
        v-if="!state.outbox?.messages || state.outbox.messages.length === 0"
        class="cep-empty-state"
      >
        <p class="cep-empty-state__title">طابور Outbox خالٍ من الرسائل المعلقة</p>
      </div>

      <div v-else class="outbox-table-wrapper">
        <table class="subsystem-table" aria-label="جدول رسائل Outbox">
          <thead>
            <tr>
              <th scope="col">النوع (Type)</th>
              <th scope="col">المصدر (Producer)</th>
              <th scope="col">حالة الإرسال</th>
              <th scope="col">المحاولات</th>
              <th scope="col">التاريخ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="msg in state.outbox.messages" :key="msg.id">
              <td>
                <bdi dir="ltr">{{ msg.type }}</bdi>
              </td>
              <td>
                <bdi dir="ltr">{{ msg.producer_module }}</bdi>
              </td>
              <td><StatusPill :status="msg.dispatch_state" /></td>
              <td dir="ltr">{{ msg.attempts }}</td>
              <td class="cell--muted">{{ when(msg.occurred_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Operational Policy Summary -->
    <section class="cep-section">
      <h3 class="cep-section-title">سياسات المعالجة التشغيلية</h3>
      <div class="policy-grid">
        <article class="policy-card">
          <span class="policy-card__icon" aria-hidden="true">🛑</span>
          <div>
            <h4 class="policy-card__title">حدود الإلغاء</h4>
            <p class="policy-card__desc">
              مسموح فقط للحالات المعلقة (<bdi dir="ltr">pending</bdi>) أو قيد التشغيل (<bdi
                dir="ltr"
                >running</bdi
              >).
            </p>
          </div>
        </article>
        <article class="policy-card">
          <span class="policy-card__icon" aria-hidden="true">🔁</span>
          <div>
            <h4 class="policy-card__title">إعادة المحاولة</h4>
            <p class="policy-card__desc">لا يوجد Retry تلقائي غير منضبط لتفادي تكرار الأخطاء.</p>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<style scoped>
.processing-surface {
  display: grid;
  gap: 1.5rem;
}

.cep-page-title-md {
  margin: 0.25rem 0 0.4rem;
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--cep-text);
}

.cep-lede-sm {
  margin: 0 0 1rem;
  font-size: 0.88rem;
  color: var(--cep-text-muted);
  line-height: 1.6;
}

.metric-strip.compact {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
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

.section-header-flex {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.85rem;
}

.section-subtext {
  font-size: 0.82rem;
  color: var(--cep-text-muted);
}

.record-list {
  display: grid;
  gap: 0.85rem;
}

.trace-card {
  padding: 1.1rem;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  display: grid;
  gap: 0.85rem;
}

.trace-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-bottom: 0.65rem;
  border-bottom: 1px solid var(--cep-border);
}

.trace-identity {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.trace-type {
  font-size: 0.95rem;
  font-weight: 750;
  color: var(--cep-text);
}

.trace-id {
  font-size: 0.78rem;
  color: var(--cep-text-muted);
  font-family: ui-monospace, monospace;
}

.trace-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
  gap: 0.75rem;
  margin: 0;
}

.trace-fact dt {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--cep-accent);
  letter-spacing: 0.04em;
  margin-bottom: 0.2rem;
}

.trace-fact dd {
  margin: 0;
  font-size: 0.84rem;
  color: var(--cep-text);
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.break-all {
  word-break: break-all;
}

.trace-actions {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-top: 0.35rem;
}

.btn-cancel {
  border-color: rgba(239, 68, 68, 0.4);
  color: #f87171;
}

.btn-cancel:hover {
  background: rgba(239, 68, 68, 0.15);
  border-color: #f87171;
}

.outbox-table-wrapper {
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

.policy-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
  gap: 0.85rem;
  margin-top: 0.85rem;
}

.policy-card {
  display: flex;
  gap: 0.75rem;
  padding: 0.85rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
}

.policy-card__icon {
  font-size: 1.2rem;
  flex-shrink: 0;
}

.policy-card__title {
  margin: 0 0 0.25rem;
  font-size: 0.88rem;
  font-weight: 750;
  color: var(--cep-text);
}

.policy-card__desc {
  margin: 0;
  font-size: 0.8rem;
  color: var(--cep-text-muted);
  line-height: 1.5;
}
</style>

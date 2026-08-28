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
const canRetry = (status: string): boolean => status === 'failed' || status === 'running';

const cancelProcessing = (runId: string) => {
  router.post(`/system/processing/runs/${runId}/cancel`, {}, { preserveScroll: true });
};

const retryProcessing = (runId: string) => {
  router.post(`/system/processing/runs/${runId}/retry`, {}, { preserveScroll: true });
};

const inspectRun = (run: ProcessingRun) => {
  emit('openDeep', `تشخيص المعالجة — ${run.type}`, [
    { label: 'معرّف المعالجة (Run ID)', value: run.id },
    { label: 'نوع المعالجة (Type)', value: run.type },
    { label: 'الحالة الحالية (Status)', value: run.status },
    { label: 'بصمة المدخلات (Input Digest)', value: run.input_digest },
    { label: 'معرّف العامل (Worker Identifier)', value: run.worker_identifier ?? '—' },
    { label: 'المحاولات (Attempts)', value: `${run.attempt_count} / ${run.max_attempts}` },
    { label: 'تاريخ المحاولة التالية (Next Attempt)', value: when(run.next_attempt_at) },
    { label: 'حجز العامل (Leased Until)', value: when(run.leased_until) },
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

      <div class="header-counts" dir="ltr">
        <span class="count-item">
          <span class="count-label">Pending:</span>
          <strong class="count-value" data-testid="processing-count-pending">{{
            count(state.processing?.counts, 'pending')
          }}</strong>
        </span>
        <span class="count-item">
          <span class="count-label">Running:</span>
          <strong class="count-value" data-testid="processing-count-running">{{
            count(state.processing?.counts, 'running')
          }}</strong>
        </span>
        <span class="count-item">
          <span class="count-label">Completed:</span>
          <strong class="count-value" data-testid="processing-count-completed">{{
            count(state.processing?.counts, 'completed')
          }}</strong>
        </span>
        <span class="count-item">
          <span class="count-label">Failed:</span>
          <strong class="count-value" data-testid="processing-count-failed">{{
            count(state.processing?.counts, 'failed')
          }}</strong>
        </span>
      </div>
    </section>

    <!-- Processing Runs List -->
    <section class="cep-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">سجل المعالجات التشغيلية (Processing Runs)</h3>
        <span class="section-subtext">أحدث 30 تشغيل مسجل في النظام</span>
      </div>

      <div v-if="state.processing?.runs === undefined" class="cep-empty-state">
        <p class="cep-empty-state__title">غير متاح — لم تتم ملاحظة سجل المعالجات</p>
      </div>

      <div v-else-if="state.processing.runs.length === 0" class="cep-empty-state">
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
              v-if="canRetry(run.status)"
              type="button"
              class="cep-text-button btn-inspect"
              @click="retryProcessing(run.id)"
            >
              إعادة التشغيل
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

      <div v-if="state.outbox?.messages === undefined" class="cep-empty-state">
        <p class="cep-empty-state__title">غير متاح — لم تتم ملاحظة رسائل Outbox</p>
      </div>

      <div v-else-if="state.outbox.messages.length === 0" class="cep-empty-state">
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

.cep-section-top {
  padding: 1.35rem 1.6rem;
  border-radius: var(--cep-radius-lg);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  box-shadow: var(--cep-shadow);
  position: relative;
  overflow: hidden;
}

.cep-page-title-md {
  margin: 0.25rem 0 0.35rem;
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--cep-text);
  letter-spacing: -0.01em;
}

.cep-lede-sm {
  margin: 0 0 1.25rem;
  font-size: 0.88rem;
  color: var(--cep-text-muted);
  line-height: 1.6;
}

.header-counts {
  display: flex;
  gap: 1.25rem;
  margin-top: 0.5rem;
  flex-wrap: wrap;
}

.count-item {
  display: inline-flex;
  align-items: baseline;
  gap: 0.35rem;
  background: var(--cep-bg-panel);
  padding: 0.4rem 0.8rem;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border);
}

.count-label {
  font-size: 0.76rem;
  font-weight: 750;
  color: var(--cep-text-muted);
  text-transform: uppercase;
}

.count-value {
  font-size: 1.1rem;
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
  padding: 1.25rem;
  border-radius: var(--cep-radius-lg);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  display: grid;
  gap: 0.95rem;
  box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.2);
  transition: all 140ms ease;
}

.trace-card:hover {
  border-color: var(--cep-border-strong);
}

.trace-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--cep-border);
}

.trace-identity {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.trace-type {
  font-size: 0.96rem;
  font-weight: 800;
  color: var(--cep-text);
}

.trace-id {
  font-size: 0.76rem;
  color: var(--cep-text-muted);
  font-family: ui-monospace, monospace;
}

.trace-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
  gap: 0.85rem;
  margin: 0;
}

.trace-fact dt {
  font-size: 0.7rem;
  font-weight: 800;
  color: var(--cep-accent);
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 0.25rem;
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
  justify-content: flex-end;
  gap: 0.65rem;
  margin-top: 0.35rem;
}

.btn-inspect {
  padding: 0.4rem 0.85rem;
  border-radius: var(--cep-radius-sm);
  border: 1px solid var(--cep-border-strong);
  background: var(--cep-bg-panel);
  color: var(--cep-accent);
  font-size: 0.8rem;
  font-weight: 750;
  transition: all 140ms ease;
}

.btn-inspect:hover {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
  box-shadow: 0 0 10px rgba(34, 211, 238, 0.2);
}

.btn-cancel {
  padding: 0.4rem 0.85rem;
  border-radius: var(--cep-radius-sm);
  border: 1px solid rgba(239, 68, 68, 0.4);
  background: rgba(239, 68, 68, 0.1);
  color: #f87171;
  font-size: 0.8rem;
  font-weight: 750;
  transition: all 140ms ease;
}

.btn-cancel:hover {
  background: rgba(239, 68, 68, 0.2);
  border-color: #f87171;
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.2);
}

.outbox-table-wrapper {
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

.policy-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
  gap: 0.85rem;
  margin-top: 0.85rem;
}

.policy-card {
  display: flex;
  gap: 0.85rem;
  padding: 1rem 1.15rem;
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.policy-card__icon {
  font-size: 1.3rem;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.35rem;
  height: 2.35rem;
  border-radius: var(--cep-radius-sm);
  background: rgba(34, 211, 238, 0.08);
  border: 1px solid rgba(34, 211, 238, 0.2);
}

.policy-card__title {
  margin: 0 0 0.25rem;
  font-size: 0.9rem;
  font-weight: 750;
  color: var(--cep-text);
}

.policy-card__desc {
  margin: 0;
  font-size: 0.82rem;
  color: var(--cep-text-muted);
  line-height: 1.55;
}
</style>

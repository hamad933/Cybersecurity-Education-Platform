<script setup lang="ts">
import type { AuditRecord, DeepSection, WorkspaceState } from '../../types';
import StatusPill from '../StatusPill.vue';

defineProps<{
  state: WorkspaceState;
}>();

const emit = defineEmits<{
  openDeep: [title: string, sections: DeepSection[]];
}>();

const when = (value: string | null | undefined): string =>
  value ? new Date(value).toLocaleString('ar-YE') : '—';

const inspectAudit = (rec: AuditRecord) => {
  emit('openDeep', `فحص سجل التدقيق وسلسلة التجزئة — #${rec.sequence_no}`, [
    { label: 'رقم التسلسل (Sequence #)', value: rec.sequence_no },
    { label: 'الفاعل (Actor Identifier)', value: rec.actor_identifier ?? 'نظام (System)' },
    { label: 'الإجراء (Action)', value: rec.action },
    { label: 'نوع الهدف (Target Type)', value: rec.target_type },
    { label: 'معرّف الهدف (Target ID)', value: rec.target_identifier ?? '—' },
    { label: 'معرّف الارتباط (Correlation ID)', value: rec.correlation_id },
    { label: 'النتيجة (Outcome)', value: rec.outcome },
    { label: 'البيانات الوصفية الآمنة (Safe Metadata)', value: rec.safe_metadata },
    { label: 'بصمة السجل السابق (Previous Hash)', value: rec.previous_hash ?? '—' },
    { label: 'بصمة السجل الحالي (Record Hash)', value: rec.record_hash },
    { label: 'تاريخ الحدث (Occurred At)', value: when(rec.occurred_at) },
  ]);
};
</script>

<template>
  <div class="audit-surface">
    <!-- Top Hash Chain Status Section -->
    <section class="cep-section-top header-status-flex">
      <div>
        <span class="cep-kicker">الحقيقة التشغيلية وسلسلة التدقيق</span>
        <h2 class="cep-page-title-md">سجل التدقيق غير القابل للتعديل (Append-Only)</h2>
        <p class="cep-lede-sm">
          توثيق كافة الإجراءات التشغيلية والقرارات البشرية في سلسلة مشفرة ومترابطة تضمن النزاهة
          التامة.
        </p>
      </div>

      <div class="chain-badge-group">
        <div class="chain-status-card">
          <span class="chain-status-label">سلسلة التجزئة المشفرة:</span>
          <StatusPill
            :status="state.chain?.valid ? 'VALID_CHAIN' : 'CHAIN_INVALID'"
            :variant="state.chain?.valid ? 'ok' : 'danger'"
          />
          <small class="chain-count">({{ state.chain?.count ?? 0 }} سجلات موثقة)</small>
        </div>
      </div>
    </section>

    <!-- Audit Records Table -->
    <section class="cep-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">سجل الأحداث التشغيلية (Audit Trail)</h3>
        <span class="section-subtext">أحدث 50 حدثاً مرتبة بالتسلسل العكسي</span>
      </div>

      <div v-if="!state.records || state.records.length === 0" class="cep-empty-state">
        <p class="cep-empty-state__title">لا توجد سجلات تدقيق مسجلة</p>
      </div>

      <div v-else class="audit-table-wrapper">
        <table class="subsystem-table" aria-label="جدول سجل التدقيق">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">الفاعل (Actor)</th>
              <th scope="col">الإجراء (Action)</th>
              <th scope="col">الهدف (Target)</th>
              <th scope="col">معرّف الارتباط</th>
              <th scope="col">النتيجة</th>
              <th scope="col">التاريخ</th>
              <th scope="col">الإجراء</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rec in state.records" :key="rec.id">
              <td class="cell--seq">#{{ rec.sequence_no }}</td>
              <td>
                <bdi dir="ltr">{{ rec.actor_identifier ?? 'System' }}</bdi>
              </td>
              <td>
                <strong class="action-name"
                  ><bdi dir="ltr">{{ rec.action }}</bdi></strong
                >
              </td>
              <td>
                <bdi dir="ltr">{{ rec.target_identifier ?? rec.target_type }}</bdi>
              </td>
              <td>
                <small class="mono" dir="ltr">{{ rec.correlation_id }}</small>
              </td>
              <td><StatusPill :status="rec.outcome" /></td>
              <td class="cell--muted">{{ when(rec.occurred_at) }}</td>
              <td>
                <button
                  type="button"
                  class="cep-text-button btn-inspect"
                  @click="inspectAudit(rec)"
                >
                  فتح سياق التحقيق
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<style scoped>
.audit-surface {
  display: grid;
  gap: 1.5rem;
}

.header-status-flex {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.cep-page-title-md {
  margin: 0.25rem 0 0.4rem;
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--cep-text);
}

.cep-lede-sm {
  margin: 0;
  font-size: 0.88rem;
  color: var(--cep-text-muted);
  line-height: 1.6;
}

.chain-status-card {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 1rem;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
}

.chain-status-label {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--cep-text-muted);
}

.chain-count {
  font-size: 0.78rem;
  color: var(--cep-text-muted);
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

.audit-table-wrapper {
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

.cell--seq {
  font-weight: 800;
  color: var(--cep-accent);
  font-family: ui-monospace, monospace;
}

.cell--muted {
  color: var(--cep-text-muted);
  font-size: 0.82rem;
}

.action-name {
  color: var(--cep-text);
  font-size: 0.84rem;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.btn-inspect {
  font-size: 0.78rem;
  padding: 0.35rem 0.65rem;
}
</style>

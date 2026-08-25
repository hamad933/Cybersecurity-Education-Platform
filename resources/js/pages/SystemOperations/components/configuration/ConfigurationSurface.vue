<script setup lang="ts">
import type { WorkspaceState } from '../../types';
import StatusPill from '../StatusPill.vue';

defineProps<{
  state: WorkspaceState;
}>();

const formatBytes = (bytes: number | undefined): string => {
  if (!bytes) return '—';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
};
</script>

<template>
  <div class="configuration-surface">
    <!-- Top Policy Section -->
    <section class="cep-section-top header-config-flex">
      <div>
        <span class="cep-kicker" dir="ltr">READ_ONLY_WHITELIST</span>
        <h2 class="cep-page-title-md">تهيئة تشغيلية مقروءة فقط</h2>
        <p class="cep-lede-sm">
          عرض معايير التهيئة التشغيلية المعتمدة للمنصة؛ بلا mutation runtime ولا مفاتيح API مكشوفة
          لضمان أمان النظام.
        </p>

        <div class="config-badges">
          <span class="config-badge config-badge--active">✓ قائمة بيضاء مقروءة فقط</span>
          <span class="config-badge">✕ لا تعديل ديناميكي أثناء التشغيل</span>
          <span class="config-badge">✕ لا كشف للمفاتيح السرية</span>
        </div>
      </div>
    </section>

    <!-- Operational Parameters Grid -->
    <section class="cep-section">
      <h3 class="cep-section-title">معلمات التهيئة التشغيلية الأساسية</h3>

      <div class="params-grid">
        <!-- Environment Profile -->
        <article class="param-card">
          <span class="param-label">بيئة التشغيل (Profile)</span>
          <strong class="param-value"
            ><bdi dir="ltr">{{ state.profile ?? 'local' }}</bdi></strong
          >
          <small class="param-hint">الملف التعريفي للبيئة</small>
        </article>

        <!-- Queue Connection -->
        <article class="param-card">
          <span class="param-label">اتصال الطوابير (Queue Driver)</span>
          <strong class="param-value"
            ><bdi dir="ltr">{{ state.queue_connection ?? 'database' }}</bdi></strong
          >
          <small class="param-hint">محرك المعالجة الخلفية</small>
        </article>

        <!-- Blob Storage Disk -->
        <article class="param-card">
          <span class="param-label">قرص التخزين (Blob Disk)</span>
          <strong class="param-value"
            ><bdi dir="ltr">{{ state.blob_disk ?? 'local' }}</bdi></strong
          >
          <small class="param-hint">تخزين الحزم والمصادر</small>
        </article>

        <!-- Release Loopback -->
        <article class="param-card">
          <span class="param-label">عزل الإطلاق المحلي (Loopback Only)</span>
          <div class="param-value-flex">
            <StatusPill
              :status="state.release_loopback_only ? 'ENABLED' : 'DISABLED'"
              :variant="state.release_loopback_only ? 'ok' : 'warning'"
            />
          </div>
          <small class="param-hint">حصر التحقق في النطاق المحلي</small>
        </article>

        <!-- AI Network Provider Status -->
        <article class="param-card">
          <span class="param-label">مزود شبكة الذكاء الاصطناعي</span>
          <div class="param-value-flex">
            <StatusPill
              :status="state.ai_network_provider_enabled ? 'ENABLED' : 'DISABLED'"
              :variant="state.ai_network_provider_enabled ? 'danger' : 'ok'"
              :label="state.ai_network_provider_enabled ? 'مفعل (خطر)' : 'معطل (آمن)'"
            />
          </div>
          <small class="param-hint">حظر الاتصال بمزود خارجي</small>
        </article>

        <!-- Force HTTPS -->
        <article class="param-card">
          <span class="param-label">فرض HTTPS</span>
          <div class="param-value-flex">
            <StatusPill
              :status="state.force_https ? 'ENABLED' : 'DISABLED'"
              :variant="state.force_https ? 'ok' : 'neutral'"
            />
          </div>
          <small class="param-hint">تشفير القنوات</small>
        </article>
      </div>
    </section>

    <!-- Operational Limits Section -->
    <section v-if="state.limits" class="cep-section">
      <h3 class="cep-section-title">السقوف التشغيلية وأحجام الحزم (Operational Limits)</h3>

      <div class="limits-table-wrapper">
        <table class="subsystem-table" aria-label="جدول السقوف التشغيلية">
          <thead>
            <tr>
              <th scope="col">المحدد التشغيلي (Limit Name)</th>
              <th scope="col">السقف المعتمد (Max Size)</th>
              <th scope="col">القيمة بالبايت</th>
              <th scope="col">الحالة</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <strong
                  >الحد الأقصى لاستيراد المصادر (<bdi dir="ltr">source_import_max_bytes</bdi
                  >)</strong
                >
              </td>
              <td dir="ltr">
                <strong>{{ formatBytes(state.limits.source_import_max_bytes) }}</strong>
              </td>
              <td class="mono" dir="ltr">{{ state.limits.source_import_max_bytes ?? '—' }}</td>
              <td><StatusPill status="LOCKED" variant="neutral" /></td>
            </tr>
            <tr>
              <td>
                <strong
                  >الحد الأقصى لنتائج AI (<bdi dir="ltr">manual_ai_result_max_bytes</bdi>)</strong
                >
              </td>
              <td dir="ltr">
                <strong>{{ formatBytes(state.limits.manual_ai_result_max_bytes) }}</strong>
              </td>
              <td class="mono" dir="ltr">{{ state.limits.manual_ai_result_max_bytes ?? '—' }}</td>
              <td><StatusPill status="LOCKED" variant="neutral" /></td>
            </tr>
            <tr>
              <td>
                <strong
                  >الحد الأقصى لبيانات التدقيق (<bdi dir="ltr">audit_metadata_max_bytes</bdi
                  >)</strong
                >
              </td>
              <td dir="ltr">
                <strong>{{ formatBytes(state.limits.audit_metadata_max_bytes) }}</strong>
              </td>
              <td class="mono" dir="ltr">{{ state.limits.audit_metadata_max_bytes ?? '—' }}</td>
              <td><StatusPill status="LOCKED" variant="neutral" /></td>
            </tr>
            <tr>
              <td>
                <strong
                  >الحد الأقصى لحمولة Outbox (<bdi dir="ltr">outbox_payload_max_bytes</bdi>)</strong
                >
              </td>
              <td dir="ltr">
                <strong>{{ formatBytes(state.limits.outbox_payload_max_bytes) }}</strong>
              </td>
              <td class="mono" dir="ltr">{{ state.limits.outbox_payload_max_bytes ?? '—' }}</td>
              <td><StatusPill status="LOCKED" variant="neutral" /></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<style scoped>
.configuration-surface {
  display: grid;
  gap: 1.5rem;
}

.header-config-flex {
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

.config-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.65rem;
}

.config-badge {
  font-size: 0.76rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
  color: var(--cep-text-muted);
}

.config-badge--active {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
  color: var(--cep-accent);
}

.params-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
  gap: 0.85rem;
  margin-top: 0.85rem;
}

.param-card {
  padding: 1rem 1.1rem;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.param-label {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--cep-text-muted);
}

.param-value {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--cep-text);
}

.param-value-flex {
  display: flex;
  align-items: center;
  margin-block: 0.15rem;
}

.param-hint {
  font-size: 0.74rem;
  color: var(--cep-text-muted);
}

.limits-table-wrapper {
  overflow-x: auto;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  margin-top: 0.85rem;
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

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
</style>

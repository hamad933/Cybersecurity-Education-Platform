<script setup lang="ts">
import type { WorkspaceState } from '../../types';
import StatusPill from '../StatusPill.vue';

defineProps<{
  state: WorkspaceState;
}>();

const formatBytes = (bytes: number | undefined): string => {
  if (typeof bytes !== 'number' || isNaN(bytes)) return '—';
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
};

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
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
            ><bdi dir="ltr">{{ state.profile ?? '—' }}</bdi></strong
          >
          <small class="param-hint">الملف التعريفي للبيئة</small>
        </article>

        <!-- Queue Connection -->
        <article class="param-card">
          <span class="param-label">اتصال الطوابير (Queue Driver)</span>
          <strong class="param-value"
            ><bdi dir="ltr">{{ state.queue_connection ?? '—' }}</bdi></strong
          >
          <small class="param-hint">محرك المعالجة الخلفية</small>
        </article>

        <!-- Blob Storage Disk -->
        <article class="param-card">
          <span class="param-label">قرص التخزين (Blob Disk)</span>
          <strong class="param-value"
            ><bdi dir="ltr">{{ state.blob_disk ?? '—' }}</bdi></strong
          >
          <small class="param-hint">تخزين الحزم والمصادر</small>
        </article>

        <!-- Release Loopback -->
        <article class="param-card">
          <span class="param-label">عزل الإطلاق المحلي (Loopback Only)</span>
          <div class="param-value-flex">
            <StatusPill
              v-if="typeof state.release_loopback_only === 'boolean'"
              :status="state.release_loopback_only ? 'ENABLED' : 'DISABLED'"
              :variant="state.release_loopback_only ? 'ok' : 'warning'"
            />
            <StatusPill v-else status="UNAVAILABLE" variant="neutral" />
          </div>
          <small class="param-hint">حصر التحقق في النطاق المحلي</small>
        </article>

        <!-- AI Network Provider Status -->
        <article class="param-card">
          <span class="param-label">مزود شبكة الذكاء الاصطناعي</span>
          <div class="param-value-flex">
            <StatusPill
              v-if="typeof state.ai_network_provider_enabled === 'boolean'"
              :status="state.ai_network_provider_enabled ? 'ENABLED' : 'DISABLED'"
              :variant="state.ai_network_provider_enabled ? 'danger' : 'ok'"
              :label="state.ai_network_provider_enabled ? 'مفعل (خطر)' : 'معطل (آمن)'"
            />
            <StatusPill v-else status="UNAVAILABLE" variant="neutral" />
          </div>
          <small class="param-hint">حظر الاتصال بمزود خارجي</small>
        </article>

        <!-- Force HTTPS -->
        <article class="param-card">
          <span class="param-label">فرض HTTPS</span>
          <div class="param-value-flex">
            <StatusPill
              v-if="typeof state.force_https === 'boolean'"
              :status="state.force_https ? 'ENABLED' : 'DISABLED'"
              :variant="state.force_https ? 'ok' : 'neutral'"
            />
            <StatusPill v-else status="UNAVAILABLE" variant="neutral" />
          </div>
          <small class="param-hint">تشفير القنوات</small>
        </article>
      </div>
    </section>

    <!-- User Local Settings Section -->
    <section v-if="state.local_settings" class="cep-section">
      <h3 class="cep-section-title">إعدادات الواجهة المحلية (Local UI Settings)</h3>
      <p class="cep-lede-sm">هذه الإعدادات تنطبق على الجلسة الحالية فقط ولا تؤثر على التهيئة التشغيلية للمنصة.</p>
      
      <div class="params-grid" style="margin-bottom: 2rem;">
        <article class="param-card">
          <span class="param-label">اللغة (Language)</span>
          <strong class="param-value"><bdi dir="ltr">{{ state.local_settings.effective?.language ?? 'ar' }}</bdi></strong>
        </article>
        
        <article class="param-card">
          <span class="param-label">الاتجاه (Direction)</span>
          <strong class="param-value"><bdi dir="ltr">{{ state.local_settings.effective?.direction ?? 'rtl' }}</bdi></strong>
        </article>
        
        <article class="param-card">
          <span class="param-label">المظهر (Appearance)</span>
          <strong class="param-value"><bdi dir="ltr">{{ state.local_settings.effective?.appearance ?? 'system' }}</bdi></strong>
        </article>
        
        <article class="param-card">
          <span class="param-label">حالة الحفظ (Status)</span>
          <div class="param-value-flex">
            <StatusPill :status="state.local_settings.status" variant="ok" />
          </div>
        </article>
      </div>
      
      <form method="POST" action="/system/configuration/settings" class="local-settings-form">
        <!-- In a real app this would use Inertia form helper, but we are just demonstrating structural presence -->
        <input type="hidden" name="_token" :value="csrfToken" />
        <div style="display: flex; gap: 1rem; align-items: center;">
            <select name="language" class="cep-select">
                <option value="ar">العربية (Arabic)</option>
                <option value="en">English (الإنجليزية)</option>
            </select>
            <select name="appearance" class="cep-select">
                <option value="system">النظام (System)</option>
                <option value="light">فاتح (Light)</option>
                <option value="dark">داكن (Dark)</option>
            </select>
            <button type="submit" class="cep-button cep-button-primary">حفظ الإعدادات المحلية</button>
        </div>
      </form>
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

.cep-section-top {
  padding: 1.35rem 1.6rem;
  border-radius: var(--cep-radius-lg);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  box-shadow: var(--cep-shadow);
  position: relative;
  overflow: hidden;
}

.header-config-flex {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.cep-page-title-md {
  margin: 0.25rem 0 0.35rem;
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--cep-text);
  letter-spacing: -0.01em;
}

.cep-lede-sm {
  margin: 0 0 0.85rem;
  font-size: 0.88rem;
  color: var(--cep-text-muted);
  line-height: 1.6;
}

.config-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.config-badge {
  font-size: 0.76rem;
  font-weight: 750;
  padding: 0.25rem 0.65rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
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
  grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
  gap: 0.85rem;
  margin-top: 0.85rem;
}

.param-card {
  padding: 1.15rem 1.25rem;
  border-radius: var(--cep-radius-lg);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transition: all 140ms ease;
}

.param-card:hover {
  border-color: var(--cep-border-strong);
  transform: translateY(-1px);
}

.param-label {
  font-size: 0.8rem;
  font-weight: 750;
  color: var(--cep-text-muted);
}

.param-value {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--cep-text);
  letter-spacing: -0.01em;
}

.param-value-flex {
  display: flex;
  align-items: center;
  margin-block: 0.15rem;
}

.param-hint {
  font-size: 0.76rem;
  color: var(--cep-text-muted);
}

.limits-table-wrapper {
  overflow-x: auto;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-lg);
  background: var(--cep-bg-panel-strong);
  box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.25);
  margin-top: 0.85rem;
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

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
</style>

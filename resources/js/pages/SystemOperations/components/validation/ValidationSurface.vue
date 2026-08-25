<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import type { Counts, DeepSection, PackageRecord, WorkspaceState } from '../../types';
import StatusPill from '../StatusPill.vue';

const props = defineProps<{
  state: WorkspaceState;
}>();

const emit = defineEmits<{
  openDeep: [title: string, sections: DeepSection[]];
}>();

const sourceForm = useForm<{ source: File | null }>({ source: null });

const pick = (event: Event): File | null => (event.target as HTMLInputElement).files?.[0] ?? null;

const handleFileSelect = (event: Event) => {
  sourceForm.source = pick(event);
};

const submitSource = () => {
  if (!sourceForm.source) return;
  sourceForm.post('/system/validation/sources/import', {
    preserveScroll: true,
    onSuccess: () => sourceForm.reset(),
  });
};

const count = (counts: Counts | undefined, key: string): number | string =>
  !counts || Object.keys(counts).length === 0 ? '—' : (counts[key] ?? 0);
const when = (value: string | null | undefined): string =>
  value ? new Date(value).toLocaleString('ar-YE') : '—';
const formatBytes = (bytes: number): string => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
};

const packageRecords = computed<PackageRecord[]>(() =>
  Array.isArray(props.state.packages)
    ? props.state.packages
    : (props.state.packages?.records ?? []),
);

const packageCounts = computed<Counts | undefined>(() =>
  Array.isArray(props.state.packages) ? undefined : props.state.packages?.counts,
);

const inspectPackage = (pkg: PackageRecord) => {
  emit('openDeep', `فحص الحزمة المحمولة — ${pkg.id}`, [
    { label: 'معرّف الحزمة (Package ID)', value: pkg.id },
    { label: 'نوع الحزمة (Package Type)', value: pkg.package_type },
    { label: 'إصدار المخطط (Schema Version)', value: pkg.schema_version ?? '—' },
    { label: 'الوحدة المالكة (Owner Module)', value: pkg.owner_module },
    { label: 'بصمة الحزمة (Package Digest)', value: pkg.package_digest },
    { label: 'حالة التحقق (Status)', value: pkg.status },
    { label: 'نطاق الحزمة (Scope)', value: pkg.scope ?? '—' },
    { label: 'بيان الملفات (Manifest)', value: pkg.manifest ?? '—' },
    { label: 'تاريخ الإنشاء (Created At)', value: when(pkg.created_at) },
  ]);
};
</script>

<template>
  <div class="validation-surface">
    <!-- Top Intake & Scope Section -->
    <section class="cep-section-top">
      <span class="cep-kicker">الفحص والتحقق التقني</span>
      <h2 class="cep-page-title-md">التحقق الهيكلي للحزم والملفات المصدرية</h2>
      <p class="cep-lede-sm">
        فحص البصمات والمخططات وتطابق البيانات دون التدخل في أحكام جودة المعرفة الموكلة للجان
        التقييم.
      </p>

      <!-- Scope Chip Badges -->
      <div class="scope-badges">
        <span class="scope-badge scope-badge--active">✓ فحص تقني فقط</span>
        <span class="scope-badge">✕ لا أحكام جودة معرفية</span>
        <span class="scope-badge">✕ لا نشر معرفي تلقائي</span>
      </div>
    </section>

    <!-- Source Import Upload Form -->
    <section id="source-import" class="cep-section upload-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">استيراد ملف مصدري للتحقق</h3>
        <span class="section-subtext">فحص سلامة الملف وتوليد البصمة</span>
      </div>

      <form class="import-form" @submit.prevent="submitSource">
        <div class="form-row">
          <input
            type="file"
            class="form-file-input"
            aria-label="اختر ملفاً مصدرrollاً للتحقق"
            @change="handleFileSelect"
          />
          <button
            type="submit"
            class="cep-text-button btn-primary"
            :disabled="!sourceForm.source || sourceForm.processing"
          >
            {{ sourceForm.processing ? 'جاري التحقق...' : 'بدء التحقق المصدري' }}
          </button>
        </div>
        <p v-if="sourceForm.errors.source" class="form-error">{{ sourceForm.errors.source }}</p>
      </form>
    </section>

    <!-- Portable Packages Verification Table -->
    <section class="cep-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">الحزم المحمولة (Portable Packages)</h3>
        <div class="header-counts">
          <span
            >المقبولة:
            <strong data-testid="validation-count-accepted">{{
              packageCounts === undefined || Object.keys(packageCounts).length === 0
                ? '—'
                : Number(count(packageCounts, 'exported')) + Number(count(packageCounts, 'valid'))
            }}</strong></span
          >
          <span
            >المرفوضة:
            <strong data-testid="validation-count-rejected">{{
              count(packageCounts, 'rejected')
            }}</strong></span
          >
        </div>
      </div>

      <div v-if="packageRecords.length === 0" class="cep-empty-state">
        <p class="cep-empty-state__title">لا توجد حزم محمولة مسجلة للتحقق</p>
      </div>

      <div v-else class="package-list">
        <article v-for="pkg in packageRecords" :key="pkg.id" class="package-card">
          <div class="package-card__header">
            <div class="package-card__id-group">
              <strong class="package-type"
                ><bdi dir="ltr">{{ pkg.package_type }}</bdi></strong
              >
              <small class="package-id"
                ><bdi dir="ltr">{{ pkg.id }}</bdi></small
              >
            </div>
            <StatusPill :status="pkg.status" />
          </div>

          <dl class="package-facts">
            <div class="package-fact">
              <dt>Package Digest</dt>
              <dd class="mono break-all" dir="ltr">{{ pkg.package_digest }}</dd>
            </div>
            <div class="package-fact">
              <dt>Owner Module</dt>
              <dd>
                <bdi dir="ltr">{{ pkg.owner_module }}</bdi>
              </dd>
            </div>
            <div class="package-fact">
              <dt>Created At</dt>
              <dd>{{ when(pkg.created_at) }}</dd>
            </div>
          </dl>

          <div class="package-actions">
            <button type="button" class="cep-text-button" @click="inspectPackage(pkg)">
              فتح سياق الحزمة
            </button>
          </div>
        </article>
      </div>
    </section>

    <!-- Source Imports Table -->
    <section class="cep-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">سجل استيراد المصادر (Source Imports)</h3>
        <span class="section-subtext">أحدث المصادر المفحوصة</span>
      </div>

      <div
        v-if="!state.source_imports?.records || state.source_imports.records.length === 0"
        class="cep-empty-state"
      >
        <p class="cep-empty-state__title">لا توجد ملفات مصدرية مسجلة</p>
      </div>

      <div v-else class="sources-table-wrapper">
        <table class="subsystem-table" aria-label="جدول المصادر المفحوصة">
          <thead>
            <tr>
              <th scope="col">اسم الملف</th>
              <th scope="col">النوع / الامتداد</th>
              <th scope="col">الحجم</th>
              <th scope="col">الحالة</th>
              <th scope="col">بصمة SHA-256</th>
              <th scope="col">التاريخ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="src in state.source_imports.records" :key="src.id">
              <td>
                <strong>{{ src.original_name }}</strong>
              </td>
              <td>
                <bdi dir="ltr">{{ src.detected_media_type }}</bdi>
              </td>
              <td dir="ltr">{{ formatBytes(src.size_bytes) }}</td>
              <td>
                <StatusPill :status="src.status" />
                <span v-if="src.rejection_code" class="rejection-code"
                  ><bdi dir="ltr">{{ src.rejection_code }}</bdi></span
                >
              </td>
              <td>
                <bdi class="mono" dir="ltr">{{ src.sha256.slice(0, 16) }}...</bdi>
              </td>
              <td class="cell--muted">{{ when(src.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<style scoped>
.validation-surface {
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

.scope-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.65rem;
}

.scope-badge {
  font-size: 0.76rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
  color: var(--cep-text-muted);
}

.scope-badge--active {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
  color: var(--cep-accent);
}

.upload-section {
  padding: 1.1rem;
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
}

.import-form {
  margin-top: 0.75rem;
}

.form-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.form-file-input {
  flex: 1;
  min-width: 15rem;
  padding: 0.5rem 0.75rem;
  border-radius: var(--cep-radius-sm);
  border: 1px solid var(--cep-border-strong);
  background: var(--cep-bg-panel);
  color: var(--cep-text);
  font-size: 0.85rem;
}

.btn-primary {
  background: var(--cep-accent);
  color: #020617;
  border-color: var(--cep-accent);
  font-weight: 750;
}

.btn-primary:hover:not(:disabled) {
  background: var(--cep-accent-hover);
}

.form-error {
  margin: 0.5rem 0 0;
  font-size: 0.8rem;
  color: #f87171;
}

.section-header-flex {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.85rem;
}

.header-counts {
  display: flex;
  gap: 0.85rem;
  font-size: 0.82rem;
  color: var(--cep-text-muted);
}

.package-list {
  display: grid;
  gap: 0.85rem;
}

.package-card {
  padding: 1rem 1.1rem;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  display: grid;
  gap: 0.75rem;
}

.package-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-bottom: 0.55rem;
  border-bottom: 1px solid var(--cep-border);
}

.package-card__id-group {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.package-type {
  font-size: 0.92rem;
  font-weight: 750;
  color: var(--cep-text);
}

.package-id {
  font-size: 0.76rem;
  color: var(--cep-text-muted);
  font-family: ui-monospace, monospace;
}

.package-facts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
  gap: 0.75rem;
  margin: 0;
}

.package-fact dt {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--cep-accent);
  margin-bottom: 0.2rem;
}

.package-fact dd {
  margin: 0;
  font-size: 0.84rem;
  color: var(--cep-text);
}

.package-actions {
  display: flex;
  justify-content: flex-end;
}

.sources-table-wrapper {
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

.rejection-code {
  display: block;
  font-size: 0.72rem;
  color: #f87171;
  font-family: ui-monospace, monospace;
  margin-top: 0.2rem;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.break-all {
  word-break: break-all;
}
</style>

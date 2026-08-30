<script setup lang="ts">
import { computed } from 'vue';

import type { DeepSection, PackageRecord, WorkspaceState } from '../../types';
import StatusPill from '../StatusPill.vue';

const props = defineProps<{
  state: WorkspaceState;
}>();

const emit = defineEmits<{
  openDeep: [title: string, sections: DeepSection[]];
}>();

const when = (value: string | null | undefined): string =>
  value ? new Date(value).toLocaleString('ar-YE') : '—';

const packagesAvailable = computed<boolean>(() => {
  if (props.state.packages === undefined) return false;
  if (Array.isArray(props.state.packages)) return true;
  return props.state.packages.records !== undefined;
});

const packageRecords = computed<PackageRecord[]>(() =>
  Array.isArray(props.state.packages)
    ? props.state.packages
    : (props.state.packages?.records ?? []),
);

const hasObservedChecks = computed(() =>
  Boolean(props.state.readiness?.checks && Object.keys(props.state.readiness.checks).length > 0),
);
const isReady = computed(() => hasObservedChecks.value && props.state.readiness?.ready === true);

const inspectPackage = (pkg: PackageRecord) => {
  emit('openDeep', `فحص حزمة أدلة الإصدار — ${pkg.id}`, [
    { label: 'معرّف الحزمة (Package ID)', value: pkg.id },
    { label: 'نوع الحزمة (Package Type)', value: pkg.package_type },
    { label: 'إصدار المخطط (Schema Version)', value: pkg.schema_version ?? '—' },
    { label: 'الوحدة المالكة (Owner Module)', value: pkg.owner_module },
    { label: 'بصمة الحزمة (Package Digest)', value: pkg.package_digest },
    { label: 'حالة الحزمة (Status)', value: pkg.status },
    { label: 'نطاق الإصدار (Scope)', value: pkg.scope ?? '—' },
    { label: 'بيان الأدلة (Manifest)', value: pkg.manifest ?? '—' },
    { label: 'تاريخ الإنشاء (Created At)', value: when(pkg.created_at) },
  ]);
};
</script>

<template>
  <div class="releases-surface">
    <!-- Top Readiness Status Section -->
    <section class="cep-section-top header-readiness-flex">
      <div>
        <span class="cep-kicker">التحقق من الإصدارات والأدلة</span>
        <h2 class="cep-page-title-md">بوابة التحقق من جاهزية الإصدار</h2>
        <p class="cep-lede-sm">
          التحقق الشامل من استيفاء كافة شروط حزم الإصدار والأدلة التقنية المرتبطة قبل الاعتماد
          النهائي.
        </p>

        <div class="scope-badges">
          <span class="scope-badge scope-badge--active">✓ فحص أدلة وجاهزية</span>
          <span class="scope-badge">✕ لا Deployment</span>
          <span class="scope-badge">✕ لا إطلاق تلقائي</span>
        </div>
      </div>

      <div class="readiness-card">
        <span class="readiness-card__label">حالة بوابة الإصدار:</span>
        <StatusPill
          :status="!hasObservedChecks ? 'UNAVAILABLE' : isReady ? 'READY' : 'NOT_READY'"
          :variant="!hasObservedChecks ? 'neutral' : isReady ? 'ok' : 'warning'"
        />
      </div>
      <div class="readiness-card" style="margin-top: 0.5rem;">
        <span class="readiness-card__label">حالة التحقق التقني:</span>
        <StatusPill
          :status="state.technical_verification_status ?? 'UNAVAILABLE'"
          :variant="state.technical_verification_status === 'VERIFIED_TECHNICALLY' ? 'ok' : 'warning'"
        />
      </div>
      <div class="readiness-card" style="margin-top: 0.5rem;">
        <span class="readiness-card__label">حالة القبول:</span>
        <StatusPill
          :status="state.owner_acceptance_status ?? 'UNAVAILABLE'"
          variant="warning"
        />
      </div>
      <div class="readiness-card" style="margin-top: 0.5rem;">
        <span class="readiness-card__label">تصريح النشر (Deployment):</span>
        <StatusPill
          :status="state.authorization?.deployment_authorized ? 'AUTHORIZED' : 'UNAUTHORIZED'"
          :variant="state.authorization?.deployment_authorized ? 'ok' : 'danger'"
        />
      </div>
    </section>

    <!-- Release Checks Checklist Grid -->
    <section
      v-if="state.readiness?.checks && Object.keys(state.readiness.checks).length > 0"
      class="cep-section"
    >
      <h3 class="cep-section-title">فحوصات بوابة الجاهزية (Release Gate Checks)</h3>
      <div class="checks-grid">
        <article v-for="(chk, key) in state.readiness.checks" :key="key" class="check-card">
          <div class="check-card__top">
            <span class="check-card__name"
              ><bdi dir="ltr">{{ key }}</bdi></span
            >
            <StatusPill :status="chk.status" />
          </div>
          <p class="check-card__detail">{{ chk.detail }}</p>
        </article>
      </div>
    </section>

    <!-- Release Evidence Packages List -->
    <section class="cep-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">حزم أدلة الإصدار (Release Evidence Packages)</h3>
        <span class="section-subtext">حزم الأدلة التقنية المصدّرة</span>
      </div>

      <div v-if="!packagesAvailable" class="cep-empty-state">
        <p class="cep-empty-state__title">غير متاح — لم تتم ملاحظة حزم الأدلة</p>
      </div>

      <div v-else-if="packageRecords.length === 0" class="cep-empty-state">
        <p class="cep-empty-state__title">لا توجد حزم أدلة إصدار مسجلة</p>
      </div>

      <div v-else class="package-list">
        <article v-for="pkg in packageRecords" :key="pkg.id" class="package-card">
          <div class="package-card__header">
            <div>
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
              <dt>Created At</dt>
              <dd>{{ when(pkg.created_at) }}</dd>
            </div>
          </dl>

          <div class="package-actions">
            <a :href="`/system/packages/${pkg.id}`" class="cep-text-button btn-download" download>
              تحميل الحزمة ⬇
            </a>
            <button type="button" class="cep-text-button" @click="inspectPackage(pkg)">
              فتح سياق الحزمة
            </button>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<style scoped>
.releases-surface {
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

.header-readiness-flex {
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

.scope-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.scope-badge {
  font-size: 0.76rem;
  font-weight: 750;
  padding: 0.25rem 0.65rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
  border: 1px solid var(--cep-border);
  color: var(--cep-text-muted);
}

.scope-badge--active {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
  color: var(--cep-accent);
}

.readiness-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1.25rem;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.readiness-card__label {
  font-size: 0.84rem;
  font-weight: 750;
  color: var(--cep-text-muted);
}

.checks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
  gap: 0.85rem;
  margin-top: 0.85rem;
}

.check-card {
  padding: 1rem 1.15rem;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  display: grid;
  gap: 0.45rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transition: all 140ms ease;
}

.check-card:hover {
  border-color: var(--cep-border-strong);
}

.check-card__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.check-card__name {
  font-size: 0.86rem;
  font-weight: 800;
  color: var(--cep-text);
}

.check-card__detail {
  margin: 0;
  font-size: 0.8rem;
  color: var(--cep-text-muted);
  line-height: 1.5;
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

.package-list {
  display: grid;
  gap: 0.85rem;
}

.package-card {
  padding: 1.25rem;
  border-radius: var(--cep-radius-lg);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  display: grid;
  gap: 0.95rem;
  box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.2);
  transition: all 140ms ease;
}

.package-card:hover {
  border-color: var(--cep-border-strong);
}

.package-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--cep-border);
}

.package-type {
  font-size: 0.96rem;
  font-weight: 800;
  color: var(--cep-text);
}

.package-id {
  display: block;
  font-size: 0.76rem;
  color: var(--cep-text-muted);
  font-family: ui-monospace, monospace;
}

.package-facts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
  gap: 0.85rem;
  margin: 0;
}

.package-fact dt {
  font-size: 0.7rem;
  font-weight: 800;
  color: var(--cep-accent);
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 0.25rem;
}

.package-fact dd {
  margin: 0;
  font-size: 0.84rem;
  color: var(--cep-text);
}

.package-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.65rem;
}

.btn-download {
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8rem;
  font-weight: 750;
  padding: 0.4rem 0.85rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent);
  color: #020617;
  border-color: var(--cep-accent);
  box-shadow: 0 0 14px rgba(34, 211, 238, 0.2);
  transition: all 140ms ease;
}

.btn-download:hover {
  background: var(--cep-accent-hover);
  border-color: var(--cep-accent-hover);
  box-shadow: 0 0 20px rgba(34, 211, 238, 0.35);
}

:root[data-theme='light'] .btn-download {
  background: var(--cep-accent);
  color: #ffffff;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.break-all {
  word-break: break-all;
}
</style>

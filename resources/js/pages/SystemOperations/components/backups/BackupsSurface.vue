<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import type { Backup, DeepSection, Restore, WorkspaceState } from '../../types';
import StatusPill from '../StatusPill.vue';

defineProps<{
  state: WorkspaceState;
}>();

const emit = defineEmits<{
  openDeep: [title: string, sections: DeepSection[]];
}>();

const backupForm = useForm({});
const restoreForm = useForm<{ package: File | null }>({ package: null });

const pick = (event: Event): File | null => (event.target as HTMLInputElement).files?.[0] ?? null;

const handleRestoreSelect = (event: Event) => {
  restoreForm.package = pick(event);
};

const createBackup = () => {
  backupForm.post('/system/backups', {
    preserveScroll: true,
  });
};

const stageRestore = () => {
  if (!restoreForm.package) return;
  restoreForm.post('/system/backups/restores/stage', {
    preserveScroll: true,
    onSuccess: () => restoreForm.reset(),
  });
};

const when = (value: string | null | undefined): string =>
  value ? new Date(value).toLocaleString('ar-YE') : '—';

const inspectBackup = (backup: Backup) => {
  emit('openDeep', `فحص بيان النسخ الاحتياطي — ${backup.id}`, [
    { label: 'معرّف النسخة (Backup ID)', value: backup.id },
    { label: 'معرّف الحزمة المحمولة (Portable Package ID)', value: backup.portable_package_id },
    { label: 'حالة النسخة (Status)', value: backup.status },
    { label: 'محرك قاعدة البيانات (Database Driver)', value: backup.database_driver },
    { label: 'بصمة المحتوى (Content Digest)', value: backup.content_digest },
    { label: 'تاريخ الإنشاء (Created At)', value: when(backup.created_at) },
  ]);
};

const inspectRestore = (restore: Restore) => {
  emit('openDeep', `فحص تشغيل الاستعادة — ${restore.id}`, [
    { label: 'معرّف الاستعادة (Restore ID)', value: restore.id },
    { label: 'بيان النسخة المعتمدة (Backup Manifest ID)', value: restore.backup_manifest_id },
    { label: 'قاعدة البيانات المستهدفة (Target DB)', value: restore.target_database },
    { label: 'الحالة (Status)', value: restore.status },
    { label: 'تقرير التحقق (Verification Report)', value: restore.verification },
    { label: 'تاريخ البدء (Started At)', value: when(restore.started_at) },
    { label: 'تاريخ الانتهاء (Completed At)', value: when(restore.completed_at) },
  ]);
};
</script>

<template>
  <div class="backups-surface">
    <!-- Top Header & Create Backup Action -->
    <section class="cep-section-top header-action-flex">
      <div>
        <span class="cep-kicker">الاستمرارية والتعافي</span>
        <h2 class="cep-page-title-md">النسخ الاحتياطي والاستعادة المرحلية</h2>
        <p class="cep-lede-sm">
          توليد نسخ احتياطية معزولة ومشفرة البصمات، مع حماية الاستعادة بمرحلة التدقيق والتحقق قبل أي
          تفعيل.
        </p>
      </div>

      <div id="backup-create" class="header-action-btn-group">
        <button
          type="button"
          class="cep-text-button btn-primary"
          :disabled="backupForm.processing"
          @click="createBackup"
        >
          {{ backupForm.processing ? 'جاري الإنشاء...' : 'إنشاء Backup جديد' }}
        </button>
      </div>
    </section>

    <!-- Backup Manifests List -->
    <section class="cep-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">سجل النسخ الاحتياطية (Backup Manifests)</h3>
        <span class="section-subtext">النسخ المحفوظة محلياً</span>
      </div>

      <div v-if="!state.backups || state.backups.length === 0" class="cep-empty-state">
        <p class="cep-empty-state__title">لا توجد نسخ احتياطية مسجلة</p>
      </div>

      <div v-else class="backup-list">
        <article v-for="b in state.backups" :key="b.id" class="backup-card">
          <div class="backup-card__header">
            <div>
              <strong class="backup-id"
                ><bdi dir="ltr">{{ b.id }}</bdi></strong
              >
              <small class="backup-driver"
                >Driver: <bdi dir="ltr">{{ b.database_driver }}</bdi></small
              >
            </div>
            <StatusPill :status="b.status" />
          </div>

          <dl class="backup-facts">
            <div class="backup-fact">
              <dt>Content Digest</dt>
              <dd class="mono break-all" dir="ltr">{{ b.content_digest }}</dd>
            </div>
            <div class="backup-fact">
              <dt>Created At</dt>
              <dd>{{ when(b.created_at) }}</dd>
            </div>
          </dl>

          <div class="backup-actions">
            <button type="button" class="cep-text-button" @click="inspectBackup(b)">
              عرض التفاصيل
            </button>
          </div>
        </article>
      </div>
    </section>

    <!-- Staged Restore Disclosure (Danger Zone / Stage & Verify Only) -->
    <section class="cep-section">
      <details class="danger-zone" :open="false">
        <summary class="danger-zone__summary">
          <div class="summary-left">
            <span class="danger-icon" aria-hidden="true">⚠️</span>
            <div>
              <strong class="danger-title">الاستعادة والتحقق المرحلي (Stage &amp; Verify)</strong>
              <p class="danger-sub">تجهيز واختبار ملف الاستعادة في قاعدة بيانات منفصلة</p>
            </div>
          </div>
          <span class="summary-toggle-badge">إجراء محمي ▾</span>
        </summary>

        <div class="danger-zone__content">
          <div class="safety-banner">
            <h4 class="safety-banner__title">ضمانات الأمان الصارمة:</h4>
            <ul class="safety-banner__list">
              <li>
                <strong>لا يوجد تفعيل Restore عبر HTTP</strong> لمنع الحذف أو الاستبدال العرضي
                لبيانات الإنتاج.
              </li>
              <li>
                الاستعادة عبر الواجهة تقتصر على وضع <bdi dir="ltr">STAGE_AND_VERIFY_ONLY</bdi>.
              </li>
              <li>
                التفعيل النهائي يتطلب أمراً صريحاً عبر سطر الأوامر (CLI) بعد التحقق من سلامة
                البصمات.
              </li>
            </ul>
          </div>

          <!-- Staging Form -->
          <form class="stage-form" @submit.prevent="stageRestore">
            <label class="form-label">اختر ملف حزمة النسخة الاحتياطية للاختبار المرحلي:</label>
            <div class="form-row">
              <input
                type="file"
                class="form-file-input"
                aria-label="ملف النسخة الاحتياطية للاختبار المرحلي"
                @change="handleRestoreSelect"
              />
              <button
                type="submit"
                class="cep-text-button btn-warning"
                :disabled="!restoreForm.package || restoreForm.processing"
              >
                {{ restoreForm.processing ? 'جاري الفحص المرحلي...' : 'بدء الفحص المرحلي' }}
              </button>
            </div>
          </form>

          <!-- Restore Runs History -->
          <div v-if="state.restores && state.restores.length > 0" class="restores-history">
            <h5 class="restores-history__title">سجل عمليات الفحص المرحلي السابقة:</h5>
            <div class="restores-list">
              <div v-for="r in state.restores" :key="r.id" class="restore-item">
                <div>
                  <strong
                    >Target: <bdi dir="ltr">{{ r.target_database }}</bdi></strong
                  >
                  <small class="mono break-all" dir="ltr"
                    >Manifest: {{ r.backup_manifest_id }}</small
                  >
                </div>
                <div class="restore-item__side">
                  <StatusPill :status="r.status" />
                  <button type="button" class="cep-text-button" @click="inspectRestore(r)">
                    فحص التقرير
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </details>
    </section>
  </div>
</template>

<style scoped>
.backups-surface {
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

.header-action-flex {
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
  margin: 0;
  font-size: 0.88rem;
  color: var(--cep-text-muted);
  line-height: 1.6;
}

.btn-primary {
  background: var(--cep-accent);
  color: #020617;
  border-color: var(--cep-accent);
  font-weight: 750;
  padding: 0.6rem 1.2rem;
  border-radius: var(--cep-radius-sm);
  box-shadow: 0 0 14px rgba(34, 211, 238, 0.2);
  transition: all 140ms ease;
}

.btn-primary:hover:not(:disabled) {
  background: var(--cep-accent-hover);
  border-color: var(--cep-accent-hover);
  box-shadow: 0 0 20px rgba(34, 211, 238, 0.35);
}

:root[data-theme='light'] .btn-primary {
  background: var(--cep-accent);
  color: #ffffff;
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

.backup-list {
  display: grid;
  gap: 0.85rem;
}

.backup-card {
  padding: 1.25rem;
  border-radius: var(--cep-radius-lg);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  display: grid;
  gap: 0.95rem;
  box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.2);
  transition: all 140ms ease;
}

.backup-card:hover {
  border-color: var(--cep-border-strong);
}

.backup-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--cep-border);
}

.backup-id {
  font-size: 0.96rem;
  font-weight: 800;
  color: var(--cep-text);
}

.backup-driver {
  display: block;
  font-size: 0.76rem;
  color: var(--cep-text-muted);
  margin-top: 0.15rem;
}

.backup-facts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
  gap: 0.85rem;
  margin: 0;
}

.backup-fact dt {
  font-size: 0.7rem;
  font-weight: 800;
  color: var(--cep-accent);
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 0.25rem;
}

.backup-fact dd {
  margin: 0;
  font-size: 0.84rem;
  color: var(--cep-text);
}

.backup-actions {
  display: flex;
  justify-content: flex-end;
}

.danger-zone {
  border: 1px solid rgba(239, 68, 68, 0.35);
  border-radius: var(--cep-radius-lg);
  background: rgba(239, 68, 68, 0.04);
  overflow: hidden;
}

.danger-zone__summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.1rem 1.35rem;
  cursor: pointer;
  background: rgba(239, 68, 68, 0.06);
}

.summary-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.danger-icon {
  font-size: 1.4rem;
}

.danger-title {
  display: block;
  font-size: 0.96rem;
  font-weight: 800;
  color: #f87171;
}

.danger-sub {
  margin: 0.15rem 0 0;
  font-size: 0.8rem;
  color: var(--cep-text-muted);
}

.summary-toggle-badge {
  font-size: 0.78rem;
  font-weight: 800;
  color: #f87171;
  padding: 0.25rem 0.65rem;
  border-radius: var(--cep-radius-sm);
  background: rgba(239, 68, 68, 0.14);
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.danger-zone__content {
  padding: 1.35rem;
  border-top: 1px solid rgba(239, 68, 68, 0.25);
  display: grid;
  gap: 1.1rem;
}

.safety-banner {
  padding: 1rem 1.2rem;
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
}

.safety-banner__title {
  margin: 0 0 0.4rem;
  font-size: 0.86rem;
  font-weight: 800;
  color: var(--cep-accent);
}

.safety-banner__list {
  margin: 0;
  padding-right: 1.2rem;
  font-size: 0.82rem;
  color: var(--cep-text-muted);
  line-height: 1.65;
}

.stage-form {
  display: grid;
  gap: 0.5rem;
}

.form-label {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--cep-text);
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
  padding: 0.55rem 0.85rem;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border-strong);
  background: var(--cep-bg-panel);
  color: var(--cep-text);
  font-size: 0.85rem;
  box-sizing: border-box;
}

.form-file-input:focus {
  outline: 2px solid var(--cep-accent);
  border-color: var(--cep-accent);
}

.btn-warning {
  background: rgba(245, 158, 11, 0.18);
  color: #fbbf24;
  border-color: rgba(245, 158, 11, 0.4);
  font-weight: 750;
  padding: 0.55rem 1.1rem;
  border-radius: var(--cep-radius-sm);
  transition: all 140ms ease;
}

.btn-warning:hover:not(:disabled) {
  background: rgba(245, 158, 11, 0.3);
  box-shadow: 0 0 10px rgba(245, 158, 11, 0.25);
}

.restores-history__title {
  margin: 0 0 0.65rem;
  font-size: 0.88rem;
  font-weight: 800;
  color: var(--cep-text);
}

.restores-list {
  display: grid;
  gap: 0.65rem;
}

.restore-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.85rem 1.1rem;
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
}

.restore-item__side {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.break-all {
  word-break: break-all;
}
</style>

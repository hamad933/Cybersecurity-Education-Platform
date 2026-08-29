<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import CepWorkspaceLayout from '../../layouts/CepWorkspaceLayout.vue';
import AiBridgeContext from './components/ai-bridge/AiBridgeContext.vue';
import AiBridgeSurface from './components/ai-bridge/AiBridgeSurface.vue';
import AuditContext from './components/audit/AuditContext.vue';
import AuditSurface from './components/audit/AuditSurface.vue';
import BackupsContext from './components/backups/BackupsContext.vue';
import BackupsSurface from './components/backups/BackupsSurface.vue';
import ConfigurationContext from './components/configuration/ConfigurationContext.vue';
import ConfigurationSurface from './components/configuration/ConfigurationSurface.vue';
import HealthContext from './components/health/HealthContext.vue';
import HealthSurface from './components/health/HealthSurface.vue';
import ProcessingContext from './components/processing/ProcessingContext.vue';
import ProcessingSurface from './components/processing/ProcessingSurface.vue';
import ReleasesContext from './components/releases/ReleasesContext.vue';
import ReleasesSurface from './components/releases/ReleasesSurface.vue';
import SystemNavRail from './components/SystemNavRail.vue';
import ValidationContext from './components/validation/ValidationContext.vue';
import ValidationSurface from './components/validation/ValidationSurface.vue';
import type { DeepSection, DeepWorkspace, Surface, WorkspaceState } from './types';

const props = defineProps<{
  surface: Surface;
  state: WorkspaceState;
}>();

const titles: Record<Surface, string> = {
  health: 'الصحة التشغيلية',
  processing: 'المعالجة والطوابير',
  validation: 'التحقق التقني',
  'ai-bridge': 'جسر الذكاء الاصطناعي اليدوي',
  backups: 'النسخ الاحتياطي والاستعادة',
  audit: 'الحقيقة وسجل التدقيق',
  releases: 'التحقق من الإصدار',
  configuration: 'تهيئة المنتج المحلية',
};

const subtitles: Record<Surface, string> = {
  health: 'مراقبة متواصلة لخدمات النظام وعملياتها وضمان الاستمرارية وسلامة البيانات.',
  processing: 'تتبع مسار المهام التشغيلية من وقت الطلب حتى اكتمال التنفيذ أو الفشل.',
  validation: 'فحص البصمات والمخططات وتطابق البيانات دون التدخل في أحكام جودة المعرفة.',
  'ai-bridge': 'تجهيز حزم الموجهات واستيراد النتائج للمراجعة البشرية الواعية قبل الاعتماد.',
  backups: 'توليد نسخ احتياطية مشفرة وحماية الاستعادة بالمرحلة المعزولة والتدقيق.',
  audit: 'سجل أحداث تشغيلي غير قابل للتعديل مترابط بسلسلة تجزئة قطعية.',
  releases: 'بوابة التحقق الشامل من جاهزية حزم الأدلة والوثائق التقنية قبل الإصدار.',
  configuration: 'عرض معايير التهيئة التشغيلية المقروءة فقط بلا كشف لأي أسرار أو مفاتيح.',
};

const title = computed(() => titles[props.surface]);
const subtitle = computed(() => subtitles[props.surface]);

const selectedHealthSubsystem = ref<
  'validation' | 'processing' | 'ai-bridge' | 'backups' | 'releases'
>('validation');

const deepWorkspace = ref<DeepWorkspace | null>(null);

const openDeepWorkspace = (titleValue: string, sections: DeepSection[]) => {
  deepWorkspace.value = { title: titleValue, sections };
};

const closeDeepWorkspace = () => {
  deepWorkspace.value = null;
};

const refreshSurface = () => {
  router.reload();
};

const formatDeepValue = (val: unknown): string => {
  if (val === null || val === undefined) return '—';
  if (typeof val === 'string') return val;
  if (typeof val === 'number' || typeof val === 'boolean') return String(val);
  return JSON.stringify(val, null, 2);
};
</script>

<template>
  <Head :title="`النظام والعمليات — ${title}`" />

  <CepWorkspaceLayout
    active-destination="system"
    :temporary-workspace-open="deepWorkspace !== null"
    :temporary-workspace-label="deepWorkspace?.title ?? 'مساحة العمل التشخيصية'"
    @close-temporary-workspace="closeDeepWorkspace"
  >
    <!-- TOP SLOT: Action Bar & Operational Title -->
    <template #top>
      <div class="workspace-top-bar">
        <div class="workspace-top-bar__titles">
          <div class="top-title-row">
            <h1 class="workspace-top-title">{{ title }}</h1>
            <span class="eyebrow-tag" dir="ltr">OPERATIONAL / V-GOVERNED</span>
          </div>
          <p class="workspace-top-subtitle">{{ subtitle }}</p>
        </div>

        <div class="workspace-top-bar__actions" aria-label="أدوات المساحة الحالية">
          <!-- Primary & Secondary Action Buttons Matching Governed Dashboard -->
          <template v-if="surface === 'health'">
            <button
              type="button"
              class="cep-text-button btn-top-primary"
              title="تحديث بيانات الصحة التشغيلية من الخادم"
              @click="refreshSurface"
            >
              <span class="btn-icon">🔄</span>
              <span>تحديث البيانات</span>
            </button>
            <a href="/system/audit" class="cep-text-button btn-top-secondary">
              <span class="btn-icon">📄</span>
              <span>فتح التقرير</span>
            </a>
          </template>

          <template v-else-if="surface === 'validation'">
            <a href="#source-import" class="cep-text-button btn-top-primary">
              <span class="btn-icon">📥</span>
              <span>استيراد للتحقق</span>
            </a>
            <button type="button" class="cep-text-button btn-top-secondary" @click="refreshSurface">
              <span class="btn-icon">🔄</span>
              <span>تحديث الفحوص</span>
            </button>
          </template>

          <template v-else-if="surface === 'ai-bridge'">
            <a href="#manual-ai-export" class="cep-text-button btn-top-primary">
              <span class="btn-icon">📝</span>
              <span>تجهيز Prompt</span>
            </a>
            <button type="button" class="cep-text-button btn-top-secondary" @click="refreshSurface">
              <span class="btn-icon">🔄</span>
              <span>تحديث النتائج</span>
            </button>
          </template>

          <template v-else-if="surface === 'backups'">
            <a href="#backup-create" class="cep-text-button btn-top-primary">
              <span class="btn-icon">💾</span>
              <span>إنشاء Backup</span>
            </a>
            <button type="button" class="cep-text-button btn-top-secondary" @click="refreshSurface">
              <span class="btn-icon">🔄</span>
              <span>تحديث النسخ</span>
            </button>
          </template>

          <template v-else>
            <button type="button" class="cep-text-button btn-top-primary" @click="refreshSurface">
              <span class="btn-icon">🔄</span>
              <span>تحديث البيانات</span>
            </button>
            <span class="read-only-chip">فحص تشغيلي مقروء</span>
          </template>
        </div>
      </div>
    </template>

    <!-- LEFT SLOT: System Navigation Rail -->
    <template #left>
      <SystemNavRail :active-surface="surface" />
    </template>

    <!-- DEFAULT (CENTER) SLOT: Surface Dominant Operational Content -->
    <div class="system-surface-container">
      <HealthSurface
        v-if="surface === 'health'"
        :state="state"
        :selected-subsystem="selectedHealthSubsystem"
        @select-subsystem="selectedHealthSubsystem = $event"
      />
      <ProcessingSurface
        v-else-if="surface === 'processing'"
        :state="state"
        @open-deep="openDeepWorkspace"
      />
      <ValidationSurface
        v-else-if="surface === 'validation'"
        :state="state"
        @open-deep="openDeepWorkspace"
      />
      <AiBridgeSurface
        v-else-if="surface === 'ai-bridge'"
        :state="state"
        @open-deep="openDeepWorkspace"
      />
      <BackupsSurface
        v-else-if="surface === 'backups'"
        :state="state"
        @open-deep="openDeepWorkspace"
      />
      <AuditSurface v-else-if="surface === 'audit'" :state="state" @open-deep="openDeepWorkspace" />
      <ReleasesSurface
        v-else-if="surface === 'releases'"
        :state="state"
        @open-deep="openDeepWorkspace"
      />
      <ConfigurationSurface v-else-if="surface === 'configuration'" :state="state" />
    </div>

    <!-- RIGHT SLOT: Contextual Inspection Panel -->
    <template #right>
      <HealthContext
        v-if="surface === 'health'"
        :state="state"
        :selected-subsystem="selectedHealthSubsystem"
      />
      <ProcessingContext v-else-if="surface === 'processing'" :state="state" />
      <ValidationContext v-else-if="surface === 'validation'" :state="state" />
      <AiBridgeContext v-else-if="surface === 'ai-bridge'" :state="state" />
      <BackupsContext v-else-if="surface === 'backups'" :state="state" />
      <AuditContext v-else-if="surface === 'audit'" :state="state" />
      <ReleasesContext v-else-if="surface === 'releases'" :state="state" />
      <ConfigurationContext v-else-if="surface === 'configuration'" :state="state" />
    </template>

    <!-- BOTTOM SLOT: Temporary Workspace Deep Inspection -->
    <template #bottom>
      <div
        v-if="deepWorkspace"
        class="workspace-bottom deep-detail-wrapper"
        data-testid="deep-workspace"
      >
        <div class="deep-detail-header">
          <div class="deep-detail-header__title-group">
            <span class="deep-detail-kicker">تشخيص تفصيلي معزول</span>
            <h3 class="deep-detail-title">{{ deepWorkspace.title }}</h3>
          </div>
          <button
            type="button"
            class="cep-text-button btn-close-deep"
            aria-label="إغلاق مساحة التفاصيل"
            @click="closeDeepWorkspace"
          >
            إغلاق ✕
          </button>
        </div>

        <div class="deep-sections-grid">
          <div v-for="(sec, idx) in deepWorkspace.sections" :key="idx" class="deep-section-card">
            <span class="deep-section-label">{{ sec.label }}</span>
            <pre
              v-if="typeof sec.value === 'object' && sec.value !== null"
              class="deep-section-pre"
              dir="ltr"
              >{{ formatDeepValue(sec.value) }}</pre>
            <div v-else class="deep-section-value mono break-all" dir="ltr">
              {{ formatDeepValue(sec.value) }}
            </div>
          </div>
        </div>
      </div>
    </template>
  </CepWorkspaceLayout>
</template>

<style scoped>
.workspace-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  width: 100%;
  padding-block: 0.15rem;
}

.workspace-top-bar__titles {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.top-title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.workspace-top-title {
  margin: 0;
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--cep-text);
  letter-spacing: -0.01em;
}

.eyebrow-tag {
  font-size: 0.68rem;
  font-weight: 800;
  color: var(--cep-accent);
  letter-spacing: 0.06em;
  background: var(--cep-accent-soft);
  padding: 0.15rem 0.5rem;
  border-radius: var(--cep-radius-sm);
  border: 1px solid rgba(34, 211, 238, 0.25);
}

.workspace-top-subtitle {
  margin: 0;
  font-size: 0.82rem;
  color: var(--cep-text-muted);
  line-height: 1.45;
}

.workspace-top-bar__actions {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.btn-top-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.5rem 1rem;
  border-radius: var(--cep-radius-md);
  background: var(--cep-accent);
  color: #020617;
  border: 1px solid var(--cep-accent);
  font-weight: 750;
  font-size: 0.82rem;
  text-decoration: none;
  box-shadow: 0 0 14px rgba(34, 211, 238, 0.25);
  transition: all 140ms ease;
}

.btn-top-primary:hover {
  background: var(--cep-accent-hover);
  border-color: var(--cep-accent-hover);
  box-shadow: 0 0 20px rgba(34, 211, 238, 0.4);
}

:root[data-theme='light'] .btn-top-primary {
  background: var(--cep-accent);
  color: #ffffff;
}

.btn-top-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.5rem 0.85rem;
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  color: var(--cep-text);
  border: 1px solid var(--cep-border);
  font-weight: 650;
  font-size: 0.82rem;
  text-decoration: none;
  transition: all 140ms ease;
}

.btn-top-secondary:hover {
  border-color: var(--cep-border-strong);
  background: var(--cep-bg-panel);
}

.btn-icon {
  font-size: 0.88rem;
  line-height: 1;
}

.read-only-chip {
  font-size: 0.74rem;
  font-weight: 750;
  color: var(--cep-text-muted);
  background: var(--cep-bg-panel-strong);
  padding: 0.35rem 0.75rem;
  border-radius: var(--cep-radius-sm);
  border: 1px solid var(--cep-border);
  letter-spacing: 0.02em;
}

.system-surface-container {
  min-height: 100%;
}

.deep-detail-wrapper {
  display: grid;
  gap: 1.1rem;
}

.deep-detail-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--cep-border);
}

.deep-detail-header__title-group {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.deep-detail-kicker {
  font-size: 0.68rem;
  font-weight: 800;
  color: var(--cep-accent);
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.deep-detail-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--cep-text);
}

.btn-close-deep {
  font-size: 0.78rem;
  padding: 0.35rem 0.65rem;
}

.deep-sections-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr));
  gap: 0.85rem;
}

.deep-section-card {
  padding: 0.9rem 1.1rem;
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.deep-section-label {
  font-size: 0.74rem;
  font-weight: 750;
  color: var(--cep-accent);
  letter-spacing: 0.02em;
}

.deep-section-value {
  font-size: 0.84rem;
  color: var(--cep-text);
}

.deep-section-pre {
  margin: 0;
  padding: 0.75rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
  border: 1px solid var(--cep-border);
  font-size: 0.78rem;
  color: var(--cep-text);
  overflow-x: auto;
  max-height: 15rem;
  line-height: 1.5;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.break-all {
  word-break: break-all;
}
</style>

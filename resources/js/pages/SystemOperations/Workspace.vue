<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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

const title = computed(() => titles[props.surface]);

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
          <span class="eyebrow" dir="ltr">CEP / SYSTEM &amp; OPERATIONS</span>
          <h1 class="workspace-top-title">{{ title }}</h1>
        </div>

        <div class="workspace-top-bar__actions" aria-label="أدوات المساحة الحالية">
          <a
            v-if="surface === 'validation'"
            href="#source-import"
            class="cep-text-button tool-link"
          >
            استيراد للتحقق
          </a>
          <a
            v-else-if="surface === 'ai-bridge'"
            href="#manual-ai-export"
            class="cep-text-button tool-link"
          >
            تجهيز Prompt
          </a>
          <a
            v-else-if="surface === 'backups'"
            href="#backup-create"
            class="cep-text-button tool-link"
          >
            إنشاء Backup
          </a>
          <span v-else class="read-only-chip">فحص تشغيلي</span>
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
          <h3 class="deep-detail-title">{{ deepWorkspace.title }}</h3>
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
  gap: 1rem;
  width: 100%;
}

.workspace-top-bar__titles {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.eyebrow {
  margin: 0;
  font-size: 0.72rem;
  font-weight: 800;
  color: var(--cep-accent);
  letter-spacing: 0.08em;
}

.workspace-top-title {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--cep-text);
}

.workspace-top-bar__actions {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.tool-link {
  text-decoration: none;
  font-weight: 750;
  color: var(--cep-accent);
}

.read-only-chip {
  font-size: 0.76rem;
  font-weight: 750;
  color: var(--cep-text-muted);
  background: var(--cep-bg-panel-strong);
  padding: 0.35rem 0.65rem;
  border-radius: var(--cep-radius-sm);
  border: 1px solid var(--cep-border);
}

.system-surface-container {
  min-height: 100%;
}

.deep-detail-wrapper {
  display: grid;
  gap: 1rem;
}

.deep-detail-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 0.65rem;
  border-bottom: 1px solid var(--cep-border);
}

.deep-detail-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 750;
  color: var(--cep-text);
}

.deep-sections-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
  gap: 0.85rem;
}

.deep-section-card {
  padding: 0.85rem 1rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
  border: 1px solid var(--cep-border);
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.deep-section-label {
  font-size: 0.76rem;
  font-weight: 750;
  color: var(--cep-accent);
}

.deep-section-value {
  font-size: 0.82rem;
  color: var(--cep-text);
}

.deep-section-pre {
  margin: 0;
  padding: 0.65rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
  font-size: 0.78rem;
  color: var(--cep-text);
  overflow-x: auto;
  max-height: 14rem;
  line-height: 1.45;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.break-all {
  word-break: break-all;
}
</style>

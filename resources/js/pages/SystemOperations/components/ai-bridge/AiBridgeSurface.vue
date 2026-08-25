<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

import type { AiResult, DeepSection, PromptRevision, WorkspaceState } from '../../types';
import StatusPill from '../StatusPill.vue';

defineProps<{
  state: WorkspaceState;
}>();

const emit = defineEmits<{
  openDeep: [title: string, sections: DeepSection[]];
}>();

const promptForm = useForm({
  purpose: '',
  knowledge_unit_id: '',
  instruction: '',
});

const aiImportForm = useForm<{ package: File | null }>({ package: null });
const decisionRationales = reactive<Record<string, string>>({});

const pick = (event: Event): File | null => (event.target as HTMLInputElement).files?.[0] ?? null;

const handlePackageSelect = (event: Event) => {
  aiImportForm.package = pick(event);
};

const submitPrompt = () => {
  promptForm.post('/system/ai-bridge/prompts/export', {
    preserveScroll: true,
    onSuccess: () => promptForm.reset(),
  });
};

const submitImport = () => {
  if (!aiImportForm.package) return;
  aiImportForm.post('/system/ai-bridge/results/import', {
    preserveScroll: true,
    onSuccess: () => aiImportForm.reset(),
  });
};

const decideAi = (resultId: string, decision: 'ACCEPT_AS_DRAFT' | 'REJECT') => {
  const rationale = (decisionRationales[resultId] ?? '').trim();
  if (!rationale) return;
  router.post(
    `/system/ai-bridge/results/${resultId}/decide`,
    { decision, rationale },
    { preserveScroll: true },
  );
};

const when = (value: string | null | undefined): string =>
  value ? new Date(value).toLocaleString('ar-YE') : '—';

const jsonText = (value: unknown): string => {
  if (value === null || value === undefined) return '—';
  if (typeof value === 'string') return value;
  return JSON.stringify(value, null, 2) ?? String(value);
};

const inspectPrompt = (rev: PromptRevision) => {
  emit('openDeep', `فحص حزمة طلب AI — ${rev.id}`, [
    { label: 'معرّف المراجعة (Revision ID)', value: rev.id },
    { label: 'رقم المراجعة (Revision #)', value: rev.revision },
    { label: 'الغرض (Purpose)', value: rev.prompt_purpose },
    { label: 'بصمة المدخلات (Input Digest)', value: rev.input_digest },
    { label: 'النطاق المعلن (Declared Scope)', value: rev.declared_scope },
    { label: 'بصمة الحزمة (Package Digest)', value: rev.package_digest },
    { label: 'بيان الحزمة (Package Manifest)', value: rev.package_manifest },
    { label: 'تاريخ التصدير (Exported At)', value: when(rev.exported_at) },
  ]);
};

const inspectResult = (res: AiResult) => {
  emit('openDeep', `فحص نتيجة AI المستوردة — ${res.id}`, [
    { label: 'معرّف النتيجة (Result ID)', value: res.id },
    { label: 'بصمة النتيجة (Result Digest)', value: res.result_digest },
    { label: 'الحالة (Status)', value: res.status },
    { label: 'النتيجة الهيكلية الكاملة (Structured Result)', value: res.structured_result },
    { label: 'تاريخ الاستيراد (Imported At)', value: when(res.imported_at) },
  ]);
};
</script>

<template>
  <div class="ai-bridge-surface">
    <!-- Top Governance & Policy Section -->
    <section class="cep-section-top">
      <span class="cep-kicker" dir="ltr">MANUAL_ONLY / PROVIDER-NEUTRAL</span>
      <h2 class="cep-page-title-md">جسر الذكاء الاصطناعي اليدوي غير المعتمد على مزودين</h2>
      <p class="cep-lede-sm">
        تجهيز حزم الموجهات (Prompts) واستيراد النتائج في ملفات معزولة لمراجعتها بشرياً وقبولها
        كمسودات أو رفضها.
      </p>

      <!-- Governance Assurance Chips -->
      <div class="governance-chips">
        <span class="chip-item chip-item--accent">✓ تشغيل يدوي بالكامل</span>
        <span class="chip-item">✕ لا API provider</span>
        <span class="chip-item">✕ لا polling</span>
        <span class="chip-item">✕ لا embeddings</span>
        <span class="chip-item">✕ لا نشر تلقائي</span>
      </div>
    </section>

    <!-- Export & Import Forms Grid -->
    <section class="forms-grid">
      <!-- Form 1: Export Prompt Package -->
      <article id="manual-ai-export" class="action-box">
        <h3 class="action-box__title">تصدير حزمة موجه (Prompt Package)</h3>
        <p class="action-box__desc">إنشاء حزمة موجه لطلب مقترحات مسودة لمعالجة وحدة معرفية.</p>

        <form class="box-form" @submit.prevent="submitPrompt">
          <div class="form-group">
            <label for="prompt-purpose" class="form-label">الغرض من الطلب</label>
            <input
              id="prompt-purpose"
              v-model="promptForm.purpose"
              type="text"
              class="form-input"
              placeholder="مثال: مراجعة وصياغة KU-42"
              required
            />
          </div>

          <div class="form-group">
            <label for="prompt-ku" class="form-label">معرّف الوحدة المعرفية</label>
            <input
              id="prompt-ku"
              v-model="promptForm.knowledge_unit_id"
              type="text"
              class="form-input mono"
              placeholder="KU-001"
              dir="ltr"
              required
            />
          </div>

          <div class="form-group">
            <label for="prompt-instruction" class="form-label">التعليمات التشغيلية</label>
            <textarea
              id="prompt-instruction"
              v-model="promptForm.instruction"
              class="form-textarea"
              rows="3"
              placeholder="التعليمات الموجهة للذكاء الاصطناعي الخارجي..."
              required
            />
          </div>

          <button
            type="submit"
            class="cep-text-button btn-primary"
            :disabled="promptForm.processing"
          >
            {{ promptForm.processing ? 'جاري التصدير...' : 'تصدير حزمة الموجه' }}
          </button>
        </form>
      </article>

      <!-- Form 2: Import Result Package -->
      <article class="action-box">
        <h3 class="action-box__title">استيراد نتيجة الذكاء الاصطناعي</h3>
        <p class="action-box__desc">استيراد حزمة النتيجة الخارجية للمراجعة قبل أي إدماج كمسودة.</p>

        <form class="box-form" @submit.prevent="submitImport">
          <div class="form-group">
            <label class="form-label">ملف حزمة النتيجة (JSON Package)</label>
            <input
              type="file"
              class="form-file-input"
              accept=".json"
              aria-label="ملف حزمة نتيجة الذكاء الاصطناعي"
              @change="handlePackageSelect"
            />
          </div>

          <button
            type="submit"
            class="cep-text-button btn-primary"
            :disabled="!aiImportForm.package || aiImportForm.processing"
          >
            {{ aiImportForm.processing ? 'جاري الاستيراد...' : 'استيراد النتيجة للمراجعة' }}
          </button>
        </form>
      </article>
    </section>

    <!-- Imported Results for Human Review -->
    <section class="cep-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">النتائج المستوردة بانتظار المراجعة والقرار</h3>
        <span class="section-subtext">المراجعة البشرية الكاملة للمقترح قبل القرار</span>
      </div>

      <div v-if="state.results === undefined" class="cep-empty-state">
        <p class="cep-empty-state__title">غير متاح — لم تتم ملاحظة نتائج الذكاء الاصطناعي</p>
      </div>

      <div v-else-if="state.results.length === 0" class="cep-empty-state">
        <p class="cep-empty-state__title">لا توجد نتائج ذكاء اصطناعي بانتظار المراجعة</p>
      </div>

      <div v-else class="results-list">
        <article v-for="res in state.results" :key="res.id" class="result-card">
          <div class="result-card__header">
            <div>
              <strong class="result-title">{{ res.prompt_purpose }}</strong>
              <small class="result-id"
                ><bdi dir="ltr">{{ res.id }}</bdi></small
              >
            </div>
            <StatusPill :status="res.status" />
          </div>

          <dl class="result-facts">
            <div class="result-fact">
              <dt>Input Digest</dt>
              <dd class="mono break-all" dir="ltr">{{ res.prompt_input_digest }}</dd>
            </div>
            <div class="result-fact">
              <dt>Result Digest</dt>
              <dd class="mono break-all" dir="ltr">{{ res.result_digest }}</dd>
            </div>
            <div class="result-fact">
              <dt>Imported At</dt>
              <dd>{{ when(res.imported_at) }}</dd>
            </div>
          </dl>

          <div class="result-actions">
            <button type="button" class="cep-text-button" @click="inspectResult(res)">
              فحص سياق النتيجة
            </button>
          </div>

          <!-- Progressive Disclosure for Proposal Review and Decision Controls -->
          <details class="proposal-review" :open="false">
            <summary class="proposal-summary">
              <span>عرض المقترح واتخاذ القرار البشري</span>
              <span class="summary-hint">اضغط للفتح</span>
            </summary>

            <div class="proposal-review__content">
              <h4 class="payload-title">محتوى المقترح الهيكلي (Structured Proposal Payload):</h4>
              <pre class="proposal-payload" dir="ltr">{{ jsonText(res.structured_result) }}</pre>

              <!-- Human Decision Controls with Required Rationale -->
              <div class="decision-form">
                <label :for="`rationale-${res.id}`" class="form-label">
                  مبرر القرار البشري (إلزامي للتوثيق والتدقيق):
                </label>
                <textarea
                  :id="`rationale-${res.id}`"
                  v-model="decisionRationales[res.id]"
                  class="form-textarea"
                  rows="2"
                  placeholder="اكتب مبرر قبول المقترح كمسودة أو رفضه..."
                />

                <div class="decision-buttons">
                  <button
                    type="button"
                    class="cep-text-button accept-button"
                    :disabled="!decisionRationales[res.id]?.trim()"
                    @click="decideAi(res.id, 'ACCEPT_AS_DRAFT')"
                  >
                    قبول كمسودة
                  </button>
                  <button
                    type="button"
                    class="cep-text-button danger-button"
                    :disabled="!decisionRationales[res.id]?.trim()"
                    @click="decideAi(res.id, 'REJECT')"
                  >
                    رفض
                  </button>
                </div>
              </div>
            </div>
          </details>
        </article>
      </div>
    </section>

    <!-- Prompt Revisions History -->
    <section class="cep-section">
      <div class="section-header-flex">
        <h3 class="cep-section-title">سجل مراجعات الموجهات المصدّرة</h3>
        <span class="section-subtext">حزم الموجهات التاريخية</span>
      </div>

      <div
        v-if="state.prompt_revisions === undefined"
        class="cep-empty-state"
      >
        <p class="cep-empty-state__title">غير متاح — لم تتم ملاحظة مراجعات الموجهات</p>
      </div>

      <div
        v-else-if="state.prompt_revisions.length === 0"
        class="cep-empty-state"
      >
        <p class="cep-empty-state__title">لا توجد مراجعات موجهات سابقة</p>
      </div>

      <div v-else class="revisions-list">
        <article v-for="rev in state.prompt_revisions" :key="rev.id" class="revision-card">
          <div class="revision-card__header">
            <div>
              <strong class="rev-purpose"
                >{{ rev.prompt_purpose }} (المراجعة #{{ rev.revision }})</strong
              >
              <small class="rev-id"
                ><bdi dir="ltr">{{ rev.id }}</bdi></small
              >
            </div>
            <StatusPill :status="rev.package_status" />
          </div>

          <dl class="revision-facts">
            <div class="revision-fact">
              <dt>Input Digest</dt>
              <dd class="mono break-all" dir="ltr">{{ rev.input_digest }}</dd>
            </div>
            <div class="revision-fact">
              <dt>Exported At</dt>
              <dd>{{ when(rev.exported_at) }}</dd>
            </div>
          </dl>

          <div class="revision-actions">
            <button type="button" class="cep-text-button" @click="inspectPrompt(rev)">
              فتح سياق الحزمة
            </button>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<style scoped>
.ai-bridge-surface {
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
  margin: 0 0 0.85rem;
  font-size: 0.88rem;
  color: var(--cep-text-muted);
  line-height: 1.6;
}

.governance-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.chip-item {
  font-size: 0.76rem;
  font-weight: 750;
  padding: 0.25rem 0.65rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
  border: 1px solid var(--cep-border);
  color: var(--cep-text-muted);
}

.chip-item--accent {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
  color: var(--cep-accent);
}

.forms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr));
  gap: 1rem;
}

.action-box {
  padding: 1.25rem;
  border-radius: var(--cep-radius-lg);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
  box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.2);
}

.action-box__title {
  margin: 0 0 0.25rem;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--cep-text);
}

.action-box__desc {
  margin: 0 0 1rem;
  font-size: 0.84rem;
  color: var(--cep-text-muted);
  line-height: 1.55;
}

.box-form {
  display: grid;
  gap: 0.85rem;
}

.form-group {
  display: grid;
  gap: 0.35rem;
}

.form-label {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--cep-text);
}

.form-input,
.form-textarea {
  width: 100%;
  border-radius: var(--cep-radius-md);
  border: 1px solid var(--cep-border-strong);
  background: var(--cep-bg-panel);
  padding: 0.6rem 0.85rem;
  color: var(--cep-text);
  font-size: 0.85rem;
  box-sizing: border-box;
  transition: all 140ms ease;
}

.form-input:focus,
.form-textarea:focus {
  outline: 2px solid var(--cep-accent);
  border-color: var(--cep-accent);
}

.form-file-input {
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

.btn-primary {
  background: var(--cep-accent);
  color: #020617;
  border-color: var(--cep-accent);
  font-weight: 750;
  padding: 0.55rem 1.1rem;
  margin-top: 0.35rem;
  box-shadow: 0 0 14px rgba(34, 211, 238, 0.2);
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

.results-list,
.revisions-list {
  display: grid;
  gap: 0.85rem;
}

.result-card,
.revision-card {
  padding: 1.25rem;
  border-radius: var(--cep-radius-lg);
  border: 1px solid var(--cep-border);
  background: var(--cep-bg-panel-strong);
  display: grid;
  gap: 0.95rem;
  box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.2);
  transition: all 140ms ease;
}

.result-card:hover,
.revision-card:hover {
  border-color: var(--cep-border-strong);
}

.result-card__header,
.revision-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--cep-border);
}

.result-title,
.rev-purpose {
  font-size: 0.96rem;
  font-weight: 800;
  color: var(--cep-text);
}

.result-id,
.rev-id {
  display: block;
  font-size: 0.76rem;
  color: var(--cep-text-muted);
  font-family: ui-monospace, monospace;
}

.result-facts,
.revision-facts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
  gap: 0.85rem;
  margin: 0;
}

.result-fact dt,
.revision-fact dt {
  font-size: 0.7rem;
  font-weight: 800;
  color: var(--cep-accent);
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 0.25rem;
}

.result-fact dd,
.revision-fact dd {
  margin: 0;
  font-size: 0.84rem;
  color: var(--cep-text);
}

.proposal-review {
  margin-top: 0.5rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel);
  overflow: hidden;
}

.proposal-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.85rem 1.1rem;
  font-weight: 750;
  font-size: 0.88rem;
  color: var(--cep-accent);
  cursor: pointer;
  background: rgba(34, 211, 238, 0.05);
}

.summary-hint {
  font-size: 0.75rem;
  color: var(--cep-text-muted);
  font-weight: 600;
}

.proposal-review__content {
  padding: 1.1rem;
  border-top: 1px solid var(--cep-border);
  display: grid;
  gap: 0.85rem;
}

.payload-title {
  margin: 0;
  font-size: 0.84rem;
  font-weight: 750;
  color: var(--cep-text);
}

.proposal-payload {
  margin: 0;
  padding: 0.95rem;
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
  font-size: 0.8rem;
  color: var(--cep-text);
  overflow-x: auto;
  max-height: 18rem;
  line-height: 1.5;
}

.decision-form {
  display: grid;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.decision-buttons {
  display: flex;
  gap: 0.75rem;
}

.accept-button {
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
  border-color: rgba(34, 197, 94, 0.4);
  font-weight: 750;
  padding: 0.45rem 0.95rem;
  border-radius: var(--cep-radius-sm);
  transition: all 140ms ease;
}

.accept-button:hover:not(:disabled) {
  background: rgba(34, 197, 94, 0.25);
  box-shadow: 0 0 10px rgba(34, 197, 94, 0.2);
}

.danger-button {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border-color: rgba(239, 68, 68, 0.4);
  font-weight: 750;
  padding: 0.45rem 0.95rem;
  border-radius: var(--cep-radius-sm);
  transition: all 140ms ease;
}

.danger-button:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.25);
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.2);
}

.result-actions,
.revision-actions {
  display: flex;
  justify-content: flex-end;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.break-all {
  word-break: break-all;
}
</style>

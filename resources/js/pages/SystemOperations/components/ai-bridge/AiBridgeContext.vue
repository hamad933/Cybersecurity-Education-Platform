<script setup lang="ts">
import { computed } from 'vue';

import type { WorkspaceState } from '../../types';

const props = defineProps<{
  state: WorkspaceState;
}>();

const executionMode = computed<string | undefined>(() => props.state.policy?.execution);
const observedProvider = computed<boolean | undefined>(
  () => props.state.policy?.automatic_provider_enabled ?? props.state.ai_network_provider_enabled,
);
const observedAutoPublish = computed<boolean | undefined>(
  () => props.state.policy?.automatic_publish,
);
const observedPolling = computed<boolean | undefined>(() => props.state.policy?.polling);
const observedEmbeddings = computed<boolean | undefined>(() => props.state.policy?.embeddings);
</script>

<template>
  <div class="ai-bridge-context cep-context-stack">
    <div class="context-header">
      <span class="cep-kicker">حوكمة الذكاء الاصطناعي</span>
      <h3 class="cep-context-title">ضوابط تشغيل الجسر</h3>
    </div>

    <!-- 1. Execution Workflow Block -->
    <article
      class="context-block"
      :class="{ 'context-block--danger': executionMode && executionMode !== 'MANUAL_ONLY' }"
    >
      <div
        class="context-block__icon-box"
        :class="
          executionMode === undefined
            ? 'icon-box--neutral'
            : executionMode === 'MANUAL_ONLY'
              ? 'icon-box--info'
              : 'icon-box--danger'
        "
        aria-hidden="true"
      >
        {{ executionMode === undefined ? '○' : executionMode === 'MANUAL_ONLY' ? '📑' : '🚨' }}
      </div>
      <div class="context-block__content">
        <h4 class="context-block__heading">نمط التنفيذ: {{ executionMode ?? 'غير متاح' }}</h4>
        <p class="context-block__body">
          <template v-if="executionMode === undefined">
            سياسة المنصة تفرض نمط التنفيذ اليدوي (MANUAL_ONLY)، ولكن لم تتم ملاحظة تكوين نمط التنفيذ
            الفعلي في البيئة الحالية.
          </template>
          <template v-else-if="executionMode === 'MANUAL_ONLY'">
            يعتمد سير عمل هذا الجسر على تصدير ملفات Prompts واستيراد النتائج يدوياً.
          </template>
          <template v-else>
            انتهاك لسياسة الحوكمة: تم رصد نمط تنفيذ ({{ executionMode }}) يتعارض مع سياسة الحصر
            اليدوي.
          </template>
        </p>
      </div>
    </article>

    <!-- 2. Provider Configuration Block (Bound to automatic_provider_enabled) -->
    <article class="context-block" :class="{ 'context-block--danger': observedProvider === true }">
      <div
        class="context-block__icon-box"
        :class="
          observedProvider === undefined
            ? 'icon-box--neutral'
            : observedProvider
              ? 'icon-box--danger'
              : 'icon-box--accent'
        "
        aria-hidden="true"
      >
        {{ observedProvider === undefined ? '○' : observedProvider ? '🌐' : '🔒' }}
      </div>
      <div class="context-block__content">
        <h4 class="context-block__heading">
          المزود الشبكي:
          {{
            observedProvider === undefined
              ? 'غير متاح'
              : observedProvider
                ? 'مفعّل في الإعدادات'
                : 'معطّل (Off)'
          }}
        </h4>
        <p class="context-block__body">
          <template v-if="observedProvider === undefined">
            سياسة الحوكمة تحظر استخدام المزود الشبكي التلقائي، ولكن لم تتم ملاحظة حالة تفعيل المزود
            في الإعدادات الحالية.
          </template>
          <template v-else-if="observedProvider">
            تم تمكين الاتصال التلقائي بمزود الشبكة في إعدادات البيئة (automatic_provider_enabled:
            true).
          </template>
          <template v-else>
            المزود التلقائي معطّل في تكوين هذه البيئة (automatic_provider_enabled: false). لا يتم
            إجراء طلبات شبكية تلقائية من هذا الجسر.
          </template>
        </p>
      </div>
    </article>

    <!-- 3. Human Decision Gate Block (Bound to automatic_publish) -->
    <article
      class="context-block"
      :class="{ 'context-block--danger': observedAutoPublish === true }"
    >
      <div
        class="context-block__icon-box"
        :class="
          observedAutoPublish === undefined
            ? 'icon-box--neutral'
            : observedAutoPublish
              ? 'icon-box--danger'
              : 'icon-box--warning'
        "
        aria-hidden="true"
      >
        {{ observedAutoPublish === undefined ? '○' : observedAutoPublish ? '🚨' : '👤' }}
      </div>
      <div class="context-block__content">
        <h4 class="context-block__heading">
          بوابة القرار البشري{{ observedAutoPublish === undefined ? ': غير متاح' : '' }}
        </h4>
        <p class="context-block__body">
          <template v-if="observedAutoPublish === undefined">
            تفرض السياسة مراجعة المشغل البشري قبل أي اعتماد، ولكن لم تتم ملاحظة حالة النشر التلقائي
            في الإعدادات.
          </template>
          <template v-else-if="observedAutoPublish">
            النشر التلقائي مفعّل بموجب السياسة التشغيلية (automatic_publish: true).
          </template>
          <template v-else>
            النشر التلقائي معطّل (automatic_publish: false)؛ تتطلب النتائج المستوردة مراجعة المشغل
            البشري وكتابة مبرر موثق قبل التحويل لمسودة.
          </template>
        </p>
      </div>
    </article>

    <!-- 4. Polling and Embeddings Governance Block -->
    <article
      class="context-block"
      :class="{
        'context-block--danger': observedPolling === true || observedEmbeddings === true,
      }"
    >
      <div
        class="context-block__icon-box"
        :class="
          observedPolling === true || observedEmbeddings === true
            ? 'icon-box--danger'
            : 'icon-box--neutral'
        "
        aria-hidden="true"
      >
        {{ observedPolling === true || observedEmbeddings === true ? '⚠️' : '⚙️' }}
      </div>
      <div class="context-block__content">
        <h4 class="context-block__heading">سياسات الاستطلاع والتضمين</h4>
        <p class="context-block__body">
          الاستطلاع التلقائي (Polling):
          {{ observedPolling === undefined ? 'غير متاح' : observedPolling ? 'مفعّل' : 'معطّل' }}
          | توليد التضمينات (Embeddings):
          {{
            observedEmbeddings === undefined ? 'غير متاح' : observedEmbeddings ? 'مفعّل' : 'معطّل'
          }}
        </p>
      </div>
    </article>
  </div>
</template>

<style scoped>
.ai-bridge-context {
  display: grid;
  gap: 0.85rem;
}

.context-header {
  padding-bottom: 0.65rem;
  border-bottom: 1px solid var(--cep-border);
}

.context-block {
  display: flex;
  gap: 0.85rem;
  padding: 0.95rem 1rem;
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transition: all 140ms ease;
}

.context-block:hover {
  border-color: var(--cep-border-strong);
}

.context-block__icon-box {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.35rem;
  height: 2.35rem;
  border-radius: var(--cep-radius-sm);
  font-size: 1.15rem;
  flex-shrink: 0;
  border: 1px solid transparent;
}

.icon-box--info {
  background: rgba(34, 211, 238, 0.12);
  border-color: rgba(34, 211, 238, 0.3);
}

.icon-box--accent {
  background: rgba(168, 85, 247, 0.12);
  border-color: rgba(168, 85, 247, 0.3);
}

.icon-box--warning {
  background: rgba(245, 158, 11, 0.12);
  border-color: rgba(245, 158, 11, 0.3);
}

.icon-box--danger {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.35);
}

.icon-box--neutral {
  background: rgba(148, 163, 184, 0.12);
  border-color: rgba(148, 163, 184, 0.25);
}

.context-block__content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.context-block__heading {
  margin: 0;
  font-size: 0.88rem;
  font-weight: 750;
  color: var(--cep-text);
  letter-spacing: -0.01em;
}

.context-block__body {
  margin: 0;
  font-size: 0.82rem;
  color: var(--cep-text-muted);
  line-height: 1.55;
}
</style>

<script setup lang="ts">
import type { WorkspaceState } from '../../types';

defineProps<{
  state: WorkspaceState;
}>();
</script>

<template>
  <div class="configuration-context cep-context-stack">
    <div class="context-header">
      <span class="cep-kicker">سياق التهيئة</span>
      <h3 class="cep-context-title">ضوابط أمان التهيئة</h3>
    </div>

    <!-- Whitelist Immutability -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">🔒</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">قائمة بيضاء معتمدة</h4>
        <p class="context-block__body">
          تُعرض القيم التشغيلية المعتمدة فقط وفق قائمة بيضاء صارمة (<bdi dir="ltr"
            >READ_ONLY_WHITELIST</bdi
          >) دون السماح بأي تعديل ديناميكي عبر الواجهة.
        </p>
      </div>
    </article>

    <!-- Zero Secret Exposure -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">🛡️</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">حماية الأسرار والمفاتيح</h4>
        <p class="context-block__body">
          لا تتضمن هذه الواجهة أي مفاتيح تشفير أو كلمات مرور أو أسرار، وتُدار كافة المفاتيح حصراً
          عبر متغيرات البيئة المعزولة.
        </p>
      </div>
    </article>

    <!-- Operational Bounding -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">📐</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">انضباط السقوف التشغيلية</h4>
        <p class="context-block__body">
          تمنع السقوف التشغيلية المحددة استهلاك الذاكرة العالي أو تحميل ملفات تتجاوز السعة المعتمدة
          لاستقرار النظام.
        </p>
      </div>
    </article>
  </div>
</template>

<style scoped>
.configuration-context {
  display: grid;
  gap: 1rem;
}

.context-header {
  padding-bottom: 0.65rem;
  border-bottom: 1px solid var(--cep-border);
}

.context-block {
  display: flex;
  gap: 0.75rem;
  padding: 0.85rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
}

.context-block__icon {
  font-size: 1.2rem;
  flex-shrink: 0;
  line-height: 1.2;
}

.context-block__content {
  flex: 1;
}

.context-block__heading {
  margin: 0 0 0.25rem;
  font-size: 0.88rem;
  font-weight: 750;
  color: var(--cep-text);
}

.context-block__body {
  margin: 0;
  font-size: 0.8rem;
  color: var(--cep-text-muted);
  line-height: 1.55;
}
</style>

<script setup lang="ts">
import type { WorkspaceState } from '../../types';

defineProps<{
  state: WorkspaceState;
}>();
</script>

<template>
  <div class="validation-context cep-context-stack">
    <div class="context-header">
      <span class="cep-kicker">سياق التحقق</span>
      <h3 class="cep-context-title">حدود التحقق التقني</h3>
    </div>

    <!-- Technical Scope Invariant -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">🛡️</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">فحص تقني فقط</h4>
        <p class="context-block__body">
          يقتصر التحقق في هذا السطح على فحص البصمات، مخططات JSON Schema، وسلامة ملفات المصدر دون
          إصدار أحكام جودة تربوية أو علمية.
        </p>
      </div>
    </article>

    <!-- Source Custody -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">🔒</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">حراسة المصادر</h4>
        <p class="context-block__body">
          تُحفظ الملفات المصدرية الأصلية بصورة غير قابلة للتعديل وتُسجل بصمتها المشفرة (<bdi
            dir="ltr"
            >SHA-256</bdi
          >) كمرجع قطعي.
        </p>
      </div>
    </article>

    <!-- Separation of Concerns -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">⚖️</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">فصل المسؤوليات</h4>
        <p class="context-block__body">
          لا يمكن تجاوز متطلبات التحقق التقني إلا عبر تصحيح المصادر وإعادة توليد الحزم وفق المعايير
          المعتمدة.
        </p>
      </div>
    </article>
  </div>
</template>

<style scoped>
.validation-context {
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

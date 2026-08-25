<script setup lang="ts">
import type { WorkspaceState } from '../../types';

defineProps<{
  state: WorkspaceState;
}>();
</script>

<template>
  <div class="ai-bridge-context cep-context-stack">
    <div class="context-header">
      <span class="cep-kicker">حوكمة الذكاء الاصطناعي</span>
      <h3 class="cep-context-title">ضوابط التشغيل اليدوي</h3>
    </div>

    <!-- Manual Exchange Assurance -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">📑</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">تبادل ملفات يدوي فقط</h4>
        <p class="context-block__body">
          لا تتصل المنصة بأي مزود سحابي أو محلي تلقائياً. يتم التبادل حصراً عبر ملفات JSON المصدرة
          والمستوردة يدوياً.
        </p>
      </div>
    </article>

    <!-- Human Decision Gate -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">👤</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">القرار البشري الإلزامي</h4>
        <p class="context-block__body">
          لا يتم اعتماد أي محتوى مولد كمسودة إلا بعد مراجعة المشغل البشري وكتابة مبرر موثق في سجل
          التدقيق.
        </p>
      </div>
    </article>

    <!-- Zero Automated Leakage -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">🔒</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">حماية البيانات والخصوصية</h4>
        <p class="context-block__body">
          يضمن التصميم المعزول عدم تسريب أي بيانات تعليمية خاصة أو سجلات طلاب إلى شبكات خارجية.
        </p>
      </div>
    </article>
  </div>
</template>

<style scoped>
.ai-bridge-context {
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

<script setup lang="ts">
import type { WorkspaceState } from '../../types';

defineProps<{
  state: WorkspaceState;
}>();
</script>

<template>
  <div class="health-context cep-context-stack">
    <div class="context-header">
      <span class="cep-kicker">سياق الحالة</span>
      <h3 class="cep-context-title">السياق التشغيلي</h3>
    </div>

    <!-- Impact Block -->
    <article class="context-block context-block--impact">
      <div class="context-block__icon" aria-hidden="true">🚨</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">التأثير</h4>
        <p class="context-block__body">
          تعطل قبول حزم المعرفة الجديدة حتى نجاح التحقق وسلامة الروابط.
        </p>
      </div>
    </article>

    <!-- Dependencies Block -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">📁</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">الاعتمادات</h4>
        <p class="context-block__body">
          مرحلة توثيق حزم المعرفة وفحص القيود الهيكلية والبيانات المرتبطة.
        </p>
      </div>
    </article>

    <!-- Last Validation Context Block -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">⏱️</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">آخر سياق تحقق</h4>
        <p class="context-block__body">
          أُسند آخر تحقق ناجح في قائمة الانتظار ومخططات التوجيه الحالية.
        </p>
      </div>
    </article>

    <!-- Configuration Scope Block -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">⚙️</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">نطاق التكوين</h4>
        <p class="context-block__body">
          تتبع قواعد التحقق التالية على جميع حزم المعرفة المرتبطة بهذا الإنتاج.
        </p>
      </div>
    </article>
  </div>
</template>

<style scoped>
.health-context {
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

.context-block--impact {
  border-color: rgba(239, 68, 68, 0.3);
  background: rgba(239, 68, 68, 0.05);
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

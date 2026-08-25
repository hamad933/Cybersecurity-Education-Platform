<script setup lang="ts">
import type { WorkspaceState } from '../../types';

defineProps<{
  state: WorkspaceState;
}>();
</script>

<template>
  <div class="releases-context cep-context-stack">
    <div class="context-header">
      <span class="cep-kicker">سياق الإصدارات</span>
      <h3 class="cep-context-title">ضوابط جاهزية الإصدار</h3>
    </div>

    <!-- No Deployment Authorization -->
    <article class="context-block context-block--notice">
      <div class="context-block__icon" aria-hidden="true">🚫</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">حظر الإطلاق التلقائي</h4>
        <p class="context-block__body">
          هذه الواجهة مخصصة للتحقق من جاهزية الأدلة والحزم فقط (<bdi dir="ltr">VALIDATION_ONLY</bdi
          >) ولا تمنح أي صلاحية للنشر أو تعديل بيئة الإنتاج.
        </p>
      </div>
    </article>

    <!-- Evidence Integrity -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">📦</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">حزم الأدلة الموثقة</h4>
        <p class="context-block__body">
          تحتوي حزم أدلة الإصدار على مخططات الفحص وبصمات التحقق لضمان قابلية المراجعة المستقلة قبل
          أي ترقية.
        </p>
      </div>
    </article>

    <!-- Release Loopback Isolation -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">🔒</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">عزل النطاق</h4>
        <p class="context-block__body">
          تلتزم عمليات التحقق ببيئة معزولة تضمن عدم التأثير على استمرارية المنصة والخدمات التعليمية
          الحية.
        </p>
      </div>
    </article>
  </div>
</template>

<style scoped>
.releases-context {
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

.context-block--notice {
  border-color: rgba(245, 158, 11, 0.35);
  background: rgba(245, 158, 11, 0.05);
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

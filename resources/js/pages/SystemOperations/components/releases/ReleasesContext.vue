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
      <div class="context-block__icon-box icon-box--warning" aria-hidden="true">🚫</div>
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
      <div class="context-block__icon-box icon-box--info" aria-hidden="true">📦</div>
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
      <div class="context-block__icon-box icon-box--accent" aria-hidden="true">🔒</div>
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

.context-block--notice {
  border-color: rgba(245, 158, 11, 0.35);
  background: rgba(245, 158, 11, 0.05);
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

.icon-box--warning {
  background: rgba(245, 158, 11, 0.12);
  border-color: rgba(245, 158, 11, 0.3);
}

.icon-box--accent {
  background: rgba(168, 85, 247, 0.12);
  border-color: rgba(168, 85, 247, 0.3);
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

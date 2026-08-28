<script setup lang="ts">
import type { WorkspaceState } from '../../types';

defineProps<{
  state: WorkspaceState;
}>();
</script>

<template>
  <div class="processing-context cep-context-stack">
    <div class="context-header">
      <span class="cep-kicker">سياق المعالجة</span>
      <h3 class="cep-context-title">ضوابط الطوابير والتنفيذ</h3>
    </div>

    <!-- Cancellation Boundary -->
    <article class="context-block">
      <div class="context-block__icon-box icon-box--danger" aria-hidden="true">🛑</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">سياسة الإلغاء</h4>
        <p class="context-block__body">
          الإلغاء محصور تقنياً في حالات <bdi dir="ltr">pending</bdi> أو
          <bdi dir="ltr">running</bdi>، ويتم توثيقه فورياً في سجل التدقيق غير القابل للتعديل.
        </p>
      </div>
    </article>

    <!-- PostgreSQL Queue Engine -->
    <article class="context-block">
      <div class="context-block__icon-box icon-box--info" aria-hidden="true">🐘</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">محرك الطوابير</h4>
        <p class="context-block__body">
          يعتمد النظام على جداول PostgreSQL الموثوقة محلياً لضمان عدم فقدان أي مهمة أثناء انقطاع
          الاتصال أو إعادة التشغيل.
        </p>
      </div>
    </article>

    <!-- Outbox Reliability -->
    <article class="context-block">
      <div class="context-block__icon-box icon-box--warning" aria-hidden="true">📬</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">نمط Outbox</h4>
        <p class="context-block__body">
          تُحفظ الأحداث التشغيلية غير التزامنية في قاعدة البيانات مع المعاملة الأصلية قبل بثها
          للمستمعين لضمان الاتساق النهائي.
        </p>
      </div>
    </article>
  </div>
</template>

<style scoped>
.processing-context {
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

.icon-box--danger {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.35);
}

.icon-box--info {
  background: rgba(34, 211, 238, 0.12);
  border-color: rgba(34, 211, 238, 0.3);
}

.icon-box--warning {
  background: rgba(245, 158, 11, 0.12);
  border-color: rgba(245, 158, 11, 0.3);
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

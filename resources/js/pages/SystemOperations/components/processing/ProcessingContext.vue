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
      <div class="context-block__icon" aria-hidden="true">🛑</div>
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
      <div class="context-block__icon" aria-hidden="true">🐘</div>
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
      <div class="context-block__icon" aria-hidden="true">📬</div>
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

<script setup lang="ts">
import type { WorkspaceState } from '../../types';

defineProps<{
  state: WorkspaceState;
}>();
</script>

<template>
  <div class="audit-context cep-context-stack">
    <div class="context-header">
      <span class="cep-kicker">سياق التدقيق</span>
      <h3 class="cep-context-title">سلسلة النزاهة المشفرة</h3>
    </div>

    <!-- Append-Only Guarantee -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">🔒</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">سجل إضافة فقط (Append-Only)</h4>
        <p class="context-block__body">
          لا يمكن تعديل أو حذف أي سجل تدقيق بعد إنشائه؛ تضمن قاعدة البيانات منع أي عمليات تحديث أو
          حذف نهائياً.
        </p>
      </div>
    </article>

    <!-- Cryptographic Hash Chaining -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">🔗</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">ترابط التجزئة (Hash Chaining)</h4>
        <p class="context-block__body">
          يرتبط كل سجل بالسجل السابق عبر بصمة مشفرة (<bdi dir="ltr">SHA-256</bdi>) لكشف أي محاولة
          تلاعب بسجلات النظام فوراً.
        </p>
      </div>
    </article>

    <!-- Correlation & Actor Attribution -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">🆔</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">إسناد المسؤولية التشغيلية</h4>
        <p class="context-block__body">
          يُوثق كل حدث مع معرف الفاعل الأصلي ومعرّف الارتباط الشامل لتتبع سلسلة المعاملات كاملة عبر
          الخدمات.
        </p>
      </div>
    </article>
  </div>
</template>

<style scoped>
.audit-context {
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

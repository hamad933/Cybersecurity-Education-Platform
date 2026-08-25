<script setup lang="ts">
import type { WorkspaceState } from '../../types';

defineProps<{
  state: WorkspaceState;
}>();
</script>

<template>
  <div class="backups-context cep-context-stack">
    <div class="context-header">
      <span class="cep-kicker">ضوابط التعافي</span>
      <h3 class="cep-context-title">سياسة الاستعادة الآمنة</h3>
    </div>

    <!-- Isolated Staging Guarantee -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">🛡️</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">الفحص المرحلي المعزول</h4>
        <p class="context-block__body">
          تُستعاد النسخ في بيئة فحص مرحلية معزولة للتحقق من سلامة الجداول دون مساس بالبيانات الحالية
          للإنتاج.
        </p>
      </div>
    </article>

    <!-- Digest Verification -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">🔑</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">التحقق من البصمات</h4>
        <p class="context-block__body">
          تُطابق بصمة المحتوى المشفرة (<bdi dir="ltr">SHA-256</bdi>) مع بيان النسخة المعتمدة لمنع أي
          تلاعب أو تلف في البيانات.
        </p>
      </div>
    </article>

    <!-- CLI-Only Final Activation -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">💻</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">التفعيل عبر CLI فقط</h4>
        <p class="context-block__body">
          التبديل النهائي لقاعدة البيانات يتطلب تدخلاً صريحاً من المشغل المعتمد عبر سطر الأوامر بعد
          اكتمال مرحلة التدقيق.
        </p>
      </div>
    </article>
  </div>
</template>

<style scoped>
.backups-context {
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

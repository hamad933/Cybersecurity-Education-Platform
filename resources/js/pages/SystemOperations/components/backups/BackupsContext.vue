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
      <div class="context-block__icon-box icon-box--info" aria-hidden="true">🛡️</div>
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
      <div class="context-block__icon-box icon-box--warning" aria-hidden="true">🔑</div>
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
      <div class="context-block__icon-box icon-box--accent" aria-hidden="true">💻</div>
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

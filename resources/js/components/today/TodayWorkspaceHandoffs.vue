<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface WorkspaceReference {
  key: string;
  label: string;
  href: string;
  description: string;
}

const workspaceReferences: WorkspaceReference[] = [
  {
    key: 'knowledge',
    label: 'المعرفة والتعلّم',
    href: '/knowledge',
    description: 'الوحدات المعرفية والمناهج التعليمية وسجل الدروس المعتمدة دون تكرار أو ازدواجية.',
  },
  {
    key: 'simulation',
    label: 'المحاكاة والمؤسسات',
    href: '/simulation',
    description: 'المختبرات التفاعلية والسيناريوهات التطبيقية وحوكمة البنى المؤسسية.',
  },
  {
    key: 'progress',
    label: 'التقدم والأدلة',
    href: '/progress',
    description:
      'سجل الأدلة المثبتة، مسارات التقييم، وتوثيق الإتقان الحقيقي غير المبني على التخمين.',
  },
  {
    key: 'system',
    label: 'النظام والعمليات',
    href: '/system',
    description: 'التشخيص التشغيلي وإدارة الهوية والوصول وتكوين البيئات التحتية للمنصة.',
  },
];
</script>

<template>
  <section
    id="workspace-handoffs"
    class="cep-section today-section"
    aria-labelledby="workspace-handoffs-title"
  >
    <p class="cep-kicker">انتقال يحفظ الملكية</p>
    <h2 id="workspace-handoffs-title" class="cep-section-title">اذهب إلى مساحة العمل المناسبة</h2>
    <p class="cep-context-copy today-workspace-intro">
      هذه روابط انتقال موجهة للمجالات الأصلية فقط. لا يعرض سطح اليوم نسخًا من السجلات الأساسية ولا
      يفسر حالة مجال دون بيانات فعلية.
    </p>

    <div class="today-workspace-grid" aria-label="مساحات العمل الرئيسية">
      <Link
        v-for="workspace in workspaceReferences"
        :key="workspace.key"
        :href="workspace.href"
        class="today-workspace-card focus-ring"
        :data-today-workspace="workspace.key"
      >
        <span class="today-workspace-card__title">{{ workspace.label }}</span>
        <span class="today-workspace-card__description">{{ workspace.description }}</span>
        <span class="today-workspace-card__action">فتح مساحة العمل ◀</span>
      </Link>
    </div>
  </section>
</template>

<style scoped>
.today-section {
  scroll-margin-top: 6.5rem;
}

.today-workspace-intro {
  max-width: 54rem;
}

.today-workspace-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
  margin-top: 1rem;
}

.today-workspace-card {
  display: grid;
  min-width: 0;
  gap: 0.55rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1rem;
  color: inherit;
  text-decoration: none;
  transition:
    border-color 140ms ease,
    background 140ms ease,
    transform 140ms ease;
}

.today-workspace-card:hover {
  border-color: var(--cep-border-strong);
  background: var(--cep-bg-panel);
  transform: translateY(-1px);
}

.today-workspace-card__title {
  color: var(--cep-text);
  font-size: 0.95rem;
  font-weight: 780;
}

.today-workspace-card__description {
  color: var(--cep-text-muted);
  font-size: 0.82rem;
  line-height: 1.75;
}

.today-workspace-card__action {
  color: var(--cep-accent);
  font-size: 0.78rem;
  font-weight: 750;
}

@media (max-width: 48rem) {
  .today-workspace-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>

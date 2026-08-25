<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import TechnicalText from '../shared/TechnicalText.vue';

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
    <div class="today-section-header">
      <div>
        <p class="cep-kicker">انتقال يحفظ الملكية</p>
        <h2 id="workspace-handoffs-title" class="cep-section-title">
          اذهب إلى مساحة العمل المناسبة
        </h2>
      </div>
      <span class="today-handoff-pill">
        <svg
          class="today-handoff-pill-icon"
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M4.25 2A2.25 2.25 0 002 4.25v11.5A2.25 2.25 0 004.25 18h11.5A2.25 2.25 0 0018 15.75V4.25A2.25 2.25 0 0015.75 2H4.25zm4.03 6.28a.75.75 0 00-1.06-1.06L4.97 9.47a.75.75 0 000 1.06l2.25 2.25a.75.75 0 001.06-1.06L7.06 10.5h5.88l-1.22 1.22a.75.75 0 101.06 1.06l2.25-2.25a.75.75 0 000-1.06l-2.25-2.25a.75.75 0 10-1.06 1.06l1.22 1.22H7.06l1.22-1.22z"
            clip-rule="evenodd"
          />
        </svg>
        المجالات الأربعة الأساسية
      </span>
    </div>

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
        <div class="today-workspace-card__top">
          <div class="today-workspace-card__icon-box">
            <!-- Knowledge Icon -->
            <svg
              v-if="workspace.key === 'knowledge'"
              class="today-workspace-icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"
              />
            </svg>
            <!-- Simulation Icon -->
            <svg
              v-else-if="workspace.key === 'simulation'"
              class="today-workspace-icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M5.25 14.25v4.5m4.5-4.5v4.5m4.5-4.5v4.5m4.5-4.5v4.5M3 9.75l9-6 9 6M4.5 10.5v8.25a.75.75 0 0 0 .75.75h13.5a.75.75 0 0 0 .75-.75V10.5"
              />
            </svg>
            <!-- Progress Icon -->
            <svg
              v-else-if="workspace.key === 'progress'"
              class="today-workspace-icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"
              />
            </svg>
            <!-- System Icon -->
            <svg
              v-else
              class="today-workspace-icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
              />
            </svg>
          </div>

          <div class="today-workspace-card__title-meta">
            <span class="today-workspace-card__title">{{ workspace.label }}</span>
            <span class="today-workspace-path">
              <TechnicalText :value="workspace.href" />
            </span>
          </div>
        </div>

        <p class="today-workspace-card__description">{{ workspace.description }}</p>

        <div class="today-workspace-card__footer">
          <span class="today-workspace-card__action">
            <span>فتح مساحة العمل</span>
            <span class="today-action-arrow" aria-hidden="true">◀</span>
          </span>
        </div>
      </Link>
    </div>
  </section>
</template>

<style scoped>
.today-section {
  scroll-margin-top: 6.5rem;
}

.today-section-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 0.75rem;
}

.today-handoff-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel-strong);
  padding: 0.22rem 0.55rem;
  color: var(--cep-text-muted);
  font-size: 0.74rem;
  font-weight: 700;
}

.today-handoff-pill-icon {
  width: 0.85rem;
  height: 0.85rem;
  color: var(--cep-accent);
}

.today-workspace-intro {
  max-width: 54rem;
  margin-top: 0.5rem;
}

.today-workspace-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem;
  margin-top: 1.15rem;
}

.today-workspace-card {
  display: grid;
  min-width: 0;
  gap: 0.75rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1.25rem;
  color: inherit;
  text-decoration: none;
  box-shadow: var(--cep-shadow);
  transition:
    border-color 150ms ease,
    background-color 150ms ease,
    transform 150ms ease,
    box-shadow 150ms ease;
}

.today-workspace-card:hover {
  border-color: var(--cep-accent);
  background: var(--cep-bg-panel);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.4);
}

[data-theme='light'] .today-workspace-card:hover {
  box-shadow: 0 8px 20px -4px rgba(8, 145, 178, 0.15);
}

.today-workspace-card__top {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.today-workspace-card__icon-box {
  display: grid;
  flex: 0 0 2.4rem;
  width: 2.4rem;
  height: 2.4rem;
  place-items: center;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-canvas);
  color: var(--cep-accent);
  transition:
    border-color 150ms ease,
    background-color 150ms ease;
}

.today-workspace-card:hover .today-workspace-card__icon-box {
  border-color: var(--cep-accent);
  background: var(--cep-accent-soft);
}

.today-workspace-icon {
  width: 1.25rem;
  height: 1.25rem;
}

.today-workspace-card__title-meta {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}

.today-workspace-card__title {
  color: var(--cep-text);
  font-size: 1.02rem;
  font-weight: 800;
  line-height: 1.3;
}

.today-workspace-path {
  font-size: 0.74rem;
}

.today-workspace-card__description {
  margin: 0;
  color: var(--cep-text-muted);
  font-size: 0.85rem;
  line-height: 1.75;
}

.today-workspace-card__footer {
  margin-top: 0.25rem;
  padding-top: 0.6rem;
  border-top: 1px solid var(--cep-border);
}

.today-workspace-card__action {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: var(--cep-accent);
  font-size: 0.82rem;
  font-weight: 750;
  transition: color 150ms ease;
}

.today-action-arrow {
  display: inline-block;
  transition: transform 150ms ease;
}

.today-workspace-card:hover .today-action-arrow {
  transform: translateX(-3px);
}

@media (max-width: 48rem) {
  .today-workspace-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>

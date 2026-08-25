<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import CepEmptyState from '../shared/CepEmptyState.vue';
import TechnicalText from '../shared/TechnicalText.vue';
import type { TodaySessionItem } from './types';

defineProps<{
  session?: TodaySessionItem | null;
}>();
</script>

<template>
  <section
    id="continue-session"
    class="cep-section today-section"
    aria-labelledby="continue-session-title"
    data-today-level="1"
  >
    <p class="cep-kicker">الأولوية التشغيلية القصوى</p>
    <h2 id="continue-session-title" class="cep-section-title">متابعة الجلسة الحالية</h2>

    <div v-if="session" class="today-session-card" data-testid="today-session-active">
      <div class="today-session-card__header">
        <span class="today-domain-badge">{{ session.domainLabel }}</span>
        <span v-if="session.lastActivityAt" class="today-meta-text">
          آخر نشاط: <TechnicalText :value="session.lastActivityAt" />
        </span>
      </div>

      <div class="today-session-card__body">
        <h3 class="today-session-card__title">{{ session.title }}</h3>
        <p v-if="session.moduleName" class="today-session-card__module">
          الوحدة: <TechnicalText :value="session.moduleName" />
        </p>
        <p v-if="session.currentStep" class="today-session-card__step">
          الخطوة الحالية: <TechnicalText :value="session.currentStep" />
        </p>
      </div>

      <div class="today-session-card__actions">
        <Link
          :href="session.href"
          class="today-hero-button focus-ring"
          data-testid="today-session-resume"
        >
          {{ session.actionLabel || 'استئناف الجلسة الآن' }} ◀
        </Link>
      </div>
    </div>

    <CepEmptyState
      v-else
      class="cep-section__body"
      title="لا توجد جلسة عمل نشطة حاليًا"
      description="لا توجد جلسة قيد التنفيذ لاستئنافها. يمكنك استكشاف مساحات العمل للبدء بنشاط جديد أو اختيار الإجراء التالي الموصى به أدناه."
      data-testid="today-session-empty"
    />
  </section>
</template>

<style scoped>
.today-section {
  scroll-margin-top: 6.5rem;
}

.today-session-card {
  display: grid;
  gap: 1rem;
  margin-top: 0.9rem;
  border: 1px solid var(--cep-accent);
  border-radius: var(--cep-radius-md);
  background: linear-gradient(135deg, var(--cep-bg-panel-strong) 0%, var(--cep-accent-soft) 100%);
  padding: 1.25rem;
  box-shadow: var(--cep-shadow);
}

.today-session-card__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.today-domain-badge {
  display: inline-flex;
  align-items: center;
  border: 1px solid var(--cep-accent);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent-soft);
  padding: 0.2rem 0.55rem;
  color: var(--cep-accent);
  font-size: 0.76rem;
  font-weight: 750;
}

.today-meta-text {
  color: var(--cep-text-muted);
  font-size: 0.78rem;
}

.today-session-card__title {
  margin: 0;
  color: var(--cep-text);
  font-size: 1.15rem;
  font-weight: 780;
}

.today-session-card__module,
.today-session-card__step {
  margin: 0.35rem 0 0;
  color: var(--cep-text-muted);
  font-size: 0.86rem;
}

.today-session-card__actions {
  display: flex;
  align-items: center;
  margin-top: 0.35rem;
}

.today-hero-button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent);
  padding: 0.65rem 1.25rem;
  color: #020914;
  font-size: 0.9rem;
  font-weight: 780;
  text-decoration: none;
  transition:
    background 140ms ease,
    transform 140ms ease;
}

.today-hero-button:hover {
  background: var(--cep-accent-hover);
  transform: translateY(-1px);
}
</style>

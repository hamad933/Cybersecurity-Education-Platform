<script setup lang="ts">
import type { Surface } from '../types';

defineProps<{
  activeSurface: Surface;
}>();

const navigation: Array<{
  key: Surface;
  label: string;
  href: string;
  icon: string;
}> = [
  { key: 'health', label: 'الصحة التشغيلية', href: '/system', icon: '🫀' },
  { key: 'processing', label: 'المعالجة والطوابير', href: '/system/processing', icon: '⚙️' },
  { key: 'validation', label: 'التحقق التقني', href: '/system/validation', icon: '🛡️' },
  { key: 'ai-bridge', label: 'جسر AI اليدوي', href: '/system/ai-bridge', icon: '🧩' },
  { key: 'backups', label: 'النسخ والاستعادة', href: '/system/backups', icon: '🔄' },
  { key: 'audit', label: 'سجل التدقيق', href: '/system/audit', icon: '📜' },
  { key: 'releases', label: 'التحقق من الإصدار', href: '/system/releases', icon: '🚀' },
  { key: 'configuration', label: 'التهيئة التشغيلية', href: '/system/configuration', icon: '🔧' },
];
</script>

<template>
  <nav class="cep-structure-nav" aria-label="بنية النظام والعمليات">
    <div class="system-nav-header">
      <span class="system-nav-header__kicker">نطاق العمليات</span>
      <h2 class="system-nav-header__title">عمليات النظام</h2>
    </div>

    <div class="system-nav-list">
      <a
        v-for="item in navigation"
        :key="item.key"
        :href="item.href"
        :aria-current="activeSurface === item.key ? 'page' : undefined"
        :class="[
          'cep-structure-nav__link',
          'rail-link',
          { 'cep-structure-nav__link--active active': activeSurface === item.key },
        ]"
      >
        <span class="system-nav-icon" aria-hidden="true">{{ item.icon }}</span>
        <span class="system-nav-label">{{ item.label }}</span>
      </a>
    </div>
  </nav>
</template>

<style scoped>
.system-nav-header {
  margin-bottom: 0.85rem;
  padding-bottom: 0.65rem;
  border-bottom: 1px solid var(--cep-border);
}

.system-nav-header__kicker {
  display: block;
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--cep-accent);
  letter-spacing: 0.05em;
  margin-bottom: 0.2rem;
}

.system-nav-header__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 800;
  color: var(--cep-text);
}

.system-nav-list {
  display: grid;
  gap: 0.35rem;
}

.rail-link {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.6rem 0.75rem;
  border-radius: var(--cep-radius-sm);
  color: var(--cep-text-muted);
  font-size: 0.88rem;
  font-weight: 650;
  text-decoration: none;
  border: 1px solid transparent;
  transition: all 140ms ease;
}

.rail-link:hover {
  background: var(--cep-accent-soft);
  color: var(--cep-text);
}

.rail-link.active,
.rail-link--active {
  border-color: var(--cep-border-strong);
  background: var(--cep-accent-soft);
  color: var(--cep-accent);
  font-weight: 750;
}

.system-nav-icon {
  font-size: 1rem;
  line-height: 1;
}

.system-nav-label {
  flex: 1;
}
</style>

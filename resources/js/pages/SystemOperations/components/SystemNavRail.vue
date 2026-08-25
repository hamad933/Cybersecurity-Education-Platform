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
        <span v-if="activeSurface === item.key" class="active-indicator" aria-hidden="true" />
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
  font-size: 0.68rem;
  font-weight: 800;
  color: var(--cep-accent);
  letter-spacing: 0.06em;
  text-transform: uppercase;
  margin-bottom: 0.2rem;
}

.system-nav-header__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--cep-text);
  letter-spacing: -0.01em;
}

.system-nav-list {
  display: grid;
  gap: 0.35rem;
}

.rail-link {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 0.85rem;
  border-radius: var(--cep-radius-md);
  color: var(--cep-text-muted);
  font-size: 0.88rem;
  font-weight: 650;
  text-decoration: none;
  border: 1px solid transparent;
  transition: all 140ms ease;
  position: relative;
}

.rail-link:hover {
  background: var(--cep-accent-soft);
  color: var(--cep-text);
  border-color: rgba(34, 211, 238, 0.15);
}

.rail-link.active,
.rail-link--active {
  border-color: rgba(34, 211, 238, 0.35);
  background: rgba(34, 211, 238, 0.12);
  color: var(--cep-accent);
  font-weight: 800;
  box-shadow: inset 0 0 12px rgba(34, 211, 238, 0.06);
}

:root[data-theme='light'] .rail-link.active,
:root[data-theme='light'] .rail-link--active {
  border-color: rgba(8, 145, 178, 0.35);
  background: rgba(8, 145, 178, 0.1);
  color: var(--cep-accent);
}

.system-nav-icon {
  font-size: 1.05rem;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  flex-shrink: 0;
}

.system-nav-label {
  flex: 1;
}

.active-indicator {
  width: 4px;
  height: 1.1rem;
  border-radius: 9999px;
  background-color: var(--cep-accent);
  box-shadow: 0 0 8px var(--cep-accent);
}
</style>

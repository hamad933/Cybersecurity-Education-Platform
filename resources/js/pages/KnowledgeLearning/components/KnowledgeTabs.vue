<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
  active: 'library' | 'learn' | 'visualize' | 'research-quality';
  objectId?: string | null;
}>();

const withObject = (path: string) =>
  props.objectId ? `${path}?object=${encodeURIComponent(props.objectId)}` : path;

const tabs = [
  {
    key: 'library',
    ar: 'المكتبة',
    en: 'Library',
    icon: '📖',
    href: '/knowledge',
  },
  {
    key: 'learn',
    ar: 'التعلّم',
    en: 'Learn',
    icon: '🎓',
    href: '/knowledge/learn',
  },
  {
    key: 'visualize',
    ar: 'التصوّر',
    en: 'Visualize',
    icon: '🕸️',
    href: '/knowledge/visualize',
  },
  {
    key: 'research-quality',
    ar: 'البحث والجودة',
    en: 'Research & Quality',
    icon: '⚖️',
    href: '/knowledge/research-quality',
  },
] as const;
</script>

<template>
  <nav
    data-testid="gateways"
    aria-label="بوابات المعرفة والتعلّم"
    class="flex min-w-0 flex-wrap items-center gap-2"
  >
    <Link
      v-for="tab in tabs"
      :key="tab.key"
      :href="withObject(tab.href)"
      class="focus-ring group inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-semibold whitespace-nowrap transition-all duration-150"
      :class="
        active === tab.key
          ? 'border-cyan-500/50 bg-[var(--cep-accent-soft)] text-[var(--cep-accent)] shadow-sm'
          : 'border-[var(--cep-border)] bg-[var(--cep-bg-panel)] text-[var(--cep-text-muted)] hover:border-[var(--cep-border-strong)] hover:bg-[var(--cep-bg-panel-strong)] hover:text-[var(--cep-text)]'
      "
    >
      <span class="text-xs opacity-80 select-none group-hover:opacity-100">
        {{ tab.icon }}
      </span>
      <span class="font-bold">{{ tab.ar }}</span>
      <bdi dir="ltr" class="font-mono text-[10px] opacity-60 group-hover:opacity-80">
        {{ tab.en }}
      </bdi>
    </Link>
  </nav>
</template>

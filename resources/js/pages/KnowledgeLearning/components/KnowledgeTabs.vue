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
    ar: 'المعرفة / المحتوى',
    en: 'Knowledge / Content',
    icon: '📖',
    href: '/knowledge',
  },
  {
    key: 'learn',
    ar: 'التعلم المرتبط',
    en: 'Related Learning',
    icon: '🎓',
    href: '/knowledge/learn',
  },
  {
    key: 'visualize',
    ar: 'السياق المرتبط',
    en: 'Related Context',
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
      class="focus-ring group inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-semibold transition-all duration-150"
      :class="
        active === tab.key
          ? 'border-cyan-500/50 bg-cyan-500/15 text-cyan-200 shadow-sm shadow-cyan-950/50 dark:border-cyan-400/60 dark:bg-cyan-950/40 dark:text-cyan-100'
          : 'border-slate-800 bg-slate-900/60 text-slate-400 hover:border-slate-700 hover:bg-slate-800/80 hover:text-slate-200 dark:border-slate-800/90 dark:bg-slate-900/40 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:text-slate-100'
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

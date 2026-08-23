<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
  active: 'library' | 'learn' | 'visualize' | 'research-quality';
  objectId?: string | null;
}>();

const withObject = (path: string) =>
  props.objectId ? `${path}?object=${encodeURIComponent(props.objectId)}` : path;

const tabs = [
  { key: 'library', ar: 'المكتبة', en: 'Library', href: '/knowledge' },
  { key: 'learn', ar: 'التعلّم', en: 'Learn', href: '/knowledge/learn' },
  { key: 'visualize', ar: 'التصوّر', en: 'Visualize', href: '/knowledge/visualize' },
  {
    key: 'research-quality',
    ar: 'البحث والجودة',
    en: 'Research & Quality',
    href: '/knowledge/research-quality',
  },
] as const;
</script>

<template>
  <nav aria-label="المناطق الأساسية للمعرفة والتعلّم" class="flex min-w-0 flex-wrap gap-2">
    <Link
      v-for="tab in tabs"
      :key="tab.key"
      :href="withObject(tab.href)"
      class="focus-ring rounded-lg border px-3 py-2 text-sm transition"
      :class="
        active === tab.key
          ? 'border-cyan-400 bg-cyan-400/10 text-cyan-100'
          : 'border-slate-700 bg-slate-900/60 text-slate-300 hover:border-slate-500'
      "
    >
      <span class="font-bold">{{ tab.ar }}</span>
      <bdi dir="ltr" class="mr-2 text-xs text-slate-500">{{ tab.en }}</bdi>
    </Link>
  </nav>
</template>

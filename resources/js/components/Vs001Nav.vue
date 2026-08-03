<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const links = [
  { href: '/vs001/sources', label: 'مراجعة المصادر' },
  { href: '/vs001/lesson/editor', label: 'محرر الدرس' },
  { href: '/vs001/lesson', label: 'الدرس' },
  { href: '/vs001/practice', label: 'تدريب مصغر' },
  { href: '/vs001/lab', label: 'المختبر الموجّه' },
  { href: '/vs001/evidence', label: 'الأدلة والإتقان' },
];
</script>

<template>
  <nav aria-label="مساحات عمل VS-001" class="mb-7 overflow-x-auto pb-2">
    <div class="flex min-w-max gap-2">
      <Link
        v-for="link in links"
        :key="link.href"
        :href="link.href"
        class="focus-ring rounded-lg border px-3 py-2 text-sm font-semibold"
        :class="
          page.url === link.href
            ? 'border-cyan-400 bg-cyan-950 text-cyan-100'
            : 'border-slate-700 bg-slate-900 text-slate-300 hover:border-slate-500'
        "
        >{{ link.label }}</Link
      >
    </div>
  </nav>
</template>

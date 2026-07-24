<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
const page = usePage();
const links = [
  ['/vs002/sources', 'سلطة المصادر'],
  ['/vs002/lesson/editor', 'محرر الدرس'],
  ['/vs002/lesson', 'الدرس'],
  ['/vs002/practice', 'تدريب مصغر'],
  ['/vs002/lab', 'مختبر الطلب'],
  ['/vs002/evidence', 'الأدلة والإتقان'],
];
</script>

<template>
  <nav aria-label="مساحات عمل VS-002" class="mb-7 overflow-x-auto pb-2">
    <div class="flex min-w-max gap-2">
      <Link
        v-for="link in links"
        :key="link[0]"
        :href="link[0]"
        class="focus-ring rounded-lg border px-3 py-2 text-sm font-semibold"
        :class="
          page.url === link[0]
            ? 'border-fuchsia-400 bg-fuchsia-950 text-fuchsia-100'
            : 'border-slate-700 bg-slate-900 text-slate-300 hover:border-slate-500'
        "
        >{{ link[1] }}</Link
      >
    </div>
  </nav>
</template>

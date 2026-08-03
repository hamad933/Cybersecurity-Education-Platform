<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import Vs002Nav from '../../components/Vs002Nav.vue';
defineProps<{
  lesson: {
    revision: number;
    blocks: { type: string; body: string }[];
    authority_baseline_id: string;
    content_digest: string;
  };
}>();
</script>

<template>
  <Head title="درس حدود الثقة" /><AppShell
    ><Vs002Nav />
    <article class="mx-auto max-w-4xl">
      <header class="border-b border-slate-800 pb-6">
        <p class="text-sm font-bold text-fuchsia-300">
          KU-D05-004 · PUBLISHED REV {{ lesson.revision }}
        </p>
        <h1 class="mt-3 text-3xl font-black sm:text-5xl">اختبار التفويض على مستوى كائن Web وAPI</h1>
        <p class="direction-ltr mt-4 font-mono text-xs text-slate-400">
          {{ lesson.authority_baseline_id }}
        </p>
      </header>
      <div class="mt-7 space-y-6">
        <section
          v-for="(block, index) in lesson.blocks"
          :key="index"
          :class="
            ['callout', 'boundaries'].includes(block.type)
              ? 'rounded-2xl border border-amber-800 bg-amber-950/30 p-5'
              : ''
          "
        >
          <h2 v-if="block.type === 'heading'" class="text-2xl font-black">{{ block.body }}</h2>
          <pre
            v-else-if="['code', 'request', 'response', 'log'].includes(block.type)"
            class="direction-ltr overflow-x-auto rounded-xl border border-slate-700 bg-slate-950 p-4 text-left font-mono text-sm whitespace-pre-wrap"
            >{{ block.body }}</pre>
          <p v-else class="text-lg leading-9">{{ block.body }}</p>
        </section>
      </div>
      <footer class="mt-8 border-t border-slate-800 pt-6">
        <p class="text-sm text-amber-200">المختبر اصطناعي ومحلي، وجميع أدلته موسومة SIMULATED.</p>
        <Link
          href="/vs002/practice"
          class="focus-ring mt-4 inline-flex rounded-lg bg-fuchsia-400 px-5 py-3 font-bold text-slate-950"
          >ابدأ التدريب المصغر</Link
        >
      </footer>
    </article></AppShell
  >
</template>

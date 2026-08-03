<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import Vs001Nav from '../../components/Vs001Nav.vue';
type Lesson = {
  revision: number;
  blocks: { type: string; body: string }[];
  citations: string[];
  authority_baseline_id: string;
  content_digest: string;
};
defineProps<{ lesson: Lesson }>();
</script>

<template>
  <Head title="درس قرارات Windows" />
  <AppShell
    ><Vs001Nav />
    <article class="mx-auto max-w-3xl">
      <header class="border-b border-slate-800 pb-6">
        <p class="text-sm font-bold text-cyan-300">KU-AD-02 · LESSON REV {{ lesson.revision }}</p>
        <h1 class="mt-3 text-3xl leading-tight font-black sm:text-5xl">
          تحليل قرارات صلاحيات ملفات Windows
        </h1>
        <p class="mt-4 text-sm text-slate-400">
          خط أساس محدد:
          <bdi class="direction-ltr font-mono text-cyan-300">{{
            lesson.authority_baseline_id
          }}</bdi>
        </p>
      </header>
      <div class="mt-7 space-y-6">
        <section
          v-for="(block, index) in lesson.blocks"
          :key="index"
          :class="
            block.type === 'callout' || block.type === 'boundaries'
              ? 'rounded-2xl border border-amber-800 bg-amber-950/30 p-5'
              : ''
          "
        >
          <h2 v-if="block.type === 'heading'" class="text-2xl font-black">{{ block.body }}</h2>
          <p v-else class="text-lg leading-9 text-slate-200">{{ block.body }}</p>
        </section>
      </div>
      <footer class="mt-8 border-t border-slate-800 pt-6">
        <p class="text-xs text-slate-500">جميع المخرجات التعليمية اللاحقة موسومة SIMULATED.</p>
        <Link
          href="/vs001/practice"
          class="focus-ring mt-4 inline-flex rounded-lg bg-cyan-400 px-5 py-3 font-bold text-slate-950"
          >ابدأ التدريب المصغر</Link
        >
      </footer>
    </article></AppShell
  >
</template>

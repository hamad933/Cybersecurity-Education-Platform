<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppShell from '../components/AppShell.vue';
import BidiText from '../components/BidiText.vue';

defineProps<{ health: { database: string; queue: string; storage: string; migrations: string } }>();
</script>

<template>
  <Head title="لوحة الأساس" />
  <AppShell>
    <section aria-labelledby="dashboard-title">
      <p class="text-sm font-semibold text-cyan-300">FOUNDATION / الأساس</p>
      <h1 id="dashboard-title" class="mt-2 text-3xl font-bold sm:text-4xl">حالة المنصة المحلية</h1>
      <p class="mt-3 max-w-3xl leading-7 text-slate-400">
        اكتملت الدورات الرأسية <BidiText value="VS-001 — VS-003" /> وأصبح مركز إصدار
        <BidiText value="V1" /> متاحاً للتحقق والتشغيل المحلي.
      </p>
    </section>

    <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="ملخص الصحة">
      <article
        v-for="(value, key) in health"
        :key="key"
        class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/70 p-5"
      >
        <p class="text-sm text-slate-400 uppercase">{{ key }}</p>
        <p class="mt-3 text-lg font-bold text-emerald-300">{{ value }}</p>
      </article>
    </section>

    <section class="mt-8 rounded-xl border border-slate-800 bg-slate-900/60 p-6">
      <h2 class="text-xl font-bold">حدود التنفيذ</h2>
      <dl class="mt-5 grid gap-5 sm:grid-cols-2">
        <div>
          <dt class="text-sm text-slate-400">الوحدات النشطة</dt>
          <dd class="mt-1">
            <BidiText
              value="MOD-IAM, MOD-PLT, MOD-SRC, MOD-KNO, MOD-CUR, MOD-ENT, MOD-SIM, MOD-EVD, MOD-LRN"
            />
          </dd>
        </div>
        <div>
          <dt class="text-sm text-slate-400">ملف البيئة</dt>
          <dd class="mt-1"><BidiText value="local / NO_DEVELOPMENT_AUTH_BYPASS" /></dd>
        </div>
      </dl>
    </section>
    <div class="mt-8 flex flex-wrap gap-3">
      <a
        href="/vs001/sources"
        class="focus-ring inline-flex rounded-lg border border-cyan-400 px-5 py-3 font-bold text-cyan-200"
        >افتح دورة VS-001</a
      >
      <a
        href="/release"
        class="focus-ring inline-flex rounded-lg bg-cyan-400 px-5 py-3 font-bold text-slate-950"
        >افتح مركز إصدار V1</a
      >
    </div>
  </AppShell>
</template>

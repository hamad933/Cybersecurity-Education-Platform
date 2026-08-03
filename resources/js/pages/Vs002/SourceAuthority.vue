<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import Vs002Nav from '../../components/Vs002Nav.vue';
type Claim = {
  claim_id: string;
  segment_ref: string;
  supported_scope: string;
  excluded_semantics: string;
};
type Source = {
  id: string;
  title: string;
  exact_url: string;
  review_status: string;
  sha256: string;
  claims: Claim[];
};
defineProps<{ sources: Source[]; baseline: string }>();
</script>

<template>
  <Head title="سلطة مصادر VS-002" /><AppShell
    ><Vs002Nav />
    <header>
      <p class="text-sm font-bold text-fuchsia-300">MOD-SRC · TECHNICAL AUTHORITY</p>
      <h1 class="mt-2 text-3xl font-black sm:text-4xl">سلطة Web وAPI المراجَعة</h1>
      <p class="mt-3 max-w-3xl leading-7 text-slate-400">
        ادعاءات محددة بمرساة ونطاق مدعوم واستبعاد صريح. المصادر الأكاديمية سياق تعليمي وليست بديلاً
        عن السلطة التقنية الحالية.
      </p>
      <p class="direction-ltr mt-4 font-mono text-sm text-fuchsia-200">{{ baseline }}</p>
    </header>
    <section class="mt-7 grid gap-4 lg:grid-cols-2" aria-label="سجل السلطة">
      <article
        v-for="source in sources"
        :key="source.id"
        class="min-w-0 rounded-2xl border border-slate-800 bg-slate-900/70 p-5"
      >
        <div class="flex flex-wrap justify-between gap-3">
          <h2 class="font-bold">{{ source.title }}</h2>
          <bdi class="font-mono text-xs text-emerald-300">{{ source.review_status }}</bdi>
        </div>
        <a
          :href="source.exact_url"
          target="_blank"
          rel="noreferrer"
          class="focus-ring direction-ltr mt-3 block rounded text-left text-sm break-all text-fuchsia-300 underline"
          >{{ source.exact_url }}</a
        >
        <div
          v-for="claim in source.claims"
          :key="claim.claim_id"
          class="mt-4 border-t border-slate-800 pt-4"
        >
          <p class="direction-ltr font-mono text-xs text-fuchsia-200">
            {{ claim.claim_id }} · {{ claim.segment_ref }}
          </p>
          <p class="mt-2 text-sm leading-6">{{ claim.supported_scope }}</p>
          <p class="mt-2 text-sm text-amber-200">مستبعد: {{ claim.excluded_semantics }}</p>
        </div>
        <p class="direction-ltr mt-4 font-mono text-[10px] break-all text-slate-500">
          sha256:{{ source.sha256 }}
        </p>
      </article>
    </section>
  </AppShell>
</template>

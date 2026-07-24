<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import Vs001Nav from '../../components/Vs001Nav.vue';

type Claim = {
  claim_id: string;
  segment_ref: string;
  supported_scope: string;
  excluded_semantics: string;
  assessment: string;
};
type Source = {
  id: string;
  title: string;
  authority_class: string;
  exact_url: string | null;
  relative_path: string | null;
  sha256: string;
  review_status: string;
  claims: Claim[];
};
defineProps<{ sources: Source[]; baseline: string }>();
</script>

<template>
  <Head title="مراجعة مصادر VS-001" />
  <AppShell>
    <Vs001Nav />
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="text-sm font-bold text-cyan-300">MOD-SRC · REVIEWED AUTHORITY</p>
        <h1 class="mt-2 text-3xl font-black sm:text-4xl">سجل مصادر قرارات Windows</h1>
        <p class="mt-3 max-w-3xl leading-7 text-slate-400">
          كل ادعاء مرتبط بصفحة Microsoft رسمية ومرساة مراجعة ونطاق مستبعد صريح. لا تُرفع المصادر
          الداخلية تلقائيًا إلى سلطة تقنية.
        </p>
      </div>
      <div
        class="direction-ltr rounded-xl border border-cyan-800 bg-cyan-950/50 p-4 font-mono text-sm"
      >
        <p class="text-cyan-300">TARGET BASELINE</p>
        <p class="mt-1 text-white">{{ baseline }}</p>
      </div>
    </header>
    <section class="mt-8 grid gap-4 lg:grid-cols-2" aria-label="المصادر المعتمدة">
      <article
        v-for="source in sources"
        :key="source.id"
        class="min-w-0 rounded-2xl border border-slate-800 bg-slate-900/70 p-5"
      >
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs font-bold text-emerald-300">
              {{ source.authority_class }} · {{ source.review_status }}
            </p>
            <h2 class="mt-2 text-lg font-bold">{{ source.title }}</h2>
          </div>
          <span class="direction-ltr rounded bg-slate-950 px-2 py-1 font-mono text-xs">{{
            source.claims[0]?.claim_id
          }}</span>
        </div>
        <a
          v-if="source.exact_url"
          :href="source.exact_url"
          class="focus-ring direction-ltr mt-3 block rounded text-left text-sm break-all text-cyan-300 underline"
          target="_blank"
          rel="noreferrer"
          >{{ source.exact_url }}</a
        >
        <p v-else class="direction-ltr mt-3 font-mono text-sm break-all text-cyan-300">
          {{ source.relative_path }}
        </p>
        <div
          v-for="claim in source.claims"
          :key="claim.claim_id"
          class="mt-4 border-t border-slate-800 pt-4 text-sm leading-6"
        >
          <p><span class="text-slate-500">المرساة:</span> {{ claim.segment_ref }}</p>
          <p class="mt-2 text-slate-300">{{ claim.supported_scope }}</p>
          <p class="mt-2 text-amber-200">
            <span class="text-slate-500">مستبعد:</span> {{ claim.excluded_semantics }}
          </p>
        </div>
        <p class="direction-ltr mt-4 font-mono text-[11px] break-all text-slate-500">
          sha256:{{ source.sha256 }}
        </p>
      </article>
    </section>
  </AppShell>
</template>

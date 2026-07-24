<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import DecisionTrace from '../../components/DecisionTrace.vue';
import OutcomeBadge from '../../components/OutcomeBadge.vue';
import Vs001Nav from '../../components/Vs001Nav.vue';
type Case = { case_id: string; title_ar: string; expected: string };
type Trace = {
  final_outcome: string;
  decisive_rule_id: string;
  remaining_unresolved_mask: string;
  output_digest: string;
  evidence_origin: string;
  ordered_ace_steps: {
    index: number;
    type: string;
    trustee_sid: string;
    reason: string;
    mask_before: string;
    mask_effect: string;
    mask_after: string;
  }[];
};
type Run = {
  id: string;
  case_id: string;
  seed: number;
  outcome: string;
  status: string;
  trace: Trace | null;
};
defineProps<{
  scenario: { scenario_id: string; revision: number; digest: string; cases: Case[] };
  runs: Run[];
}>();
const page = usePage<{ flash?: { status?: string } }>();
const form = useForm({ case_id: 'CASE-003-DENY-BEFORE-ALLOW', seed: 7007, idempotency_key: '' });
const run = () => form.post('/vs001/lab/run', { preserveScroll: true });
const replay = (id: string) => router.post(`/vs001/lab/${id}/replay`, {}, { preserveScroll: true });
</script>

<template>
  <Head title="المختبر الموجّه" />
  <AppShell
    ><Vs001Nav />
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="text-sm font-bold text-cyan-300">INSTITUTIONAL SIMULATOR · GUIDED LAB</p>
        <h1 class="mt-2 text-3xl font-black sm:text-4xl">مختبر قرار DACL المحدود</h1>
        <p class="mt-3 max-w-3xl leading-7 text-slate-400">
          محاكاة داخل العملية من مدخلات محفوظة فقط؛ لا عملية مضيفة، ولا جهاز، ولا موصل Windows، ولا
          AccessCheck حقيقي.
        </p>
      </div>
      <div class="rounded-xl border border-amber-700 bg-amber-950/40 p-4 text-amber-100">
        <bdi class="font-mono font-bold">SIMULATED</bdi>
        <p class="mt-1 text-sm">مصدر الدليل الوحيد</p>
      </div>
    </header>
    <p
      v-if="page.props.flash?.status"
      class="mt-5 rounded-xl border border-cyan-800 bg-cyan-950 p-4"
      role="status"
    >
      {{ page.props.flash.status }}
    </p>
    <div class="mt-7 grid gap-6 lg:grid-cols-[340px_minmax(0,1fr)]">
      <form
        class="h-fit rounded-2xl border border-slate-800 bg-slate-900/70 p-5"
        @submit.prevent="run"
      >
        <label class="font-bold" for="case">الحالة المنشورة</label
        ><select id="case" v-model="form.case_id" class="form-input focus-ring mt-2">
          <option v-for="item in scenario.cases" :key="item.case_id" :value="item.case_id">
            {{ item.title_ar }}
          </option></select
        ><label class="mt-5 block font-bold" for="seed">البذرة الحتمية</label
        ><input
          id="seed"
          v-model="form.seed"
          type="number"
          min="1"
          max="4294967295"
          class="form-input focus-ring direction-ltr mt-2 text-left"
        /><label class="mt-5 block font-bold" for="key">مفتاح التكرار (اختياري)</label
        ><input
          id="key"
          v-model="form.idempotency_key"
          class="form-input focus-ring direction-ltr mt-2 text-left"
          placeholder="lab.case.001"
        /><button
          class="focus-ring mt-6 w-full rounded-lg bg-cyan-400 px-5 py-3 font-black text-slate-950 disabled:opacity-50"
          :disabled="form.processing"
        >
          شغّل الحالة
        </button>
        <dl
          class="direction-ltr mt-6 space-y-2 text-left font-mono text-xs break-all text-slate-500"
        >
          <div>
            <dt>scenario</dt>
            <dd>{{ scenario.scenario_id }}@{{ scenario.revision }}</dd>
          </div>
          <div>
            <dt>digest</dt>
            <dd>{{ scenario.digest }}</dd>
          </div>
        </dl>
      </form>
      <section class="min-w-0">
        <div v-if="runs[0]" class="space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-xs text-slate-500">LATEST RUN</p>
              <p class="direction-ltr mt-1 font-mono text-sm">
                {{ runs[0].case_id }} · seed {{ runs[0].seed }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <OutcomeBadge :value="runs[0].outcome" /><button
                class="focus-ring rounded-lg border border-slate-600 px-3 py-2 text-sm font-bold"
                @click="replay(runs[0].id)"
              >
                إعادة حتمية
              </button>
            </div>
          </div>
          <DecisionTrace v-if="runs[0].trace" :trace="runs[0].trace" />
        </div>
        <div
          v-else
          class="rounded-2xl border border-dashed border-slate-700 p-10 text-center text-slate-400"
        >
          اختر حالة وشغّلها لعرض الأثر المنظم.
        </div>
        <h2 class="mt-7 font-bold">الحالات المنشورة ({{ scenario.cases.length }})</h2>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
          <article
            v-for="item in scenario.cases"
            :key="item.case_id"
            class="rounded-xl border border-slate-800 bg-slate-900/50 p-3"
          >
            <p class="text-sm font-bold">{{ item.title_ar }}</p>
            <div class="mt-2 flex items-center justify-between gap-2">
              <bdi class="direction-ltr font-mono text-[10px] text-slate-500">{{
                item.case_id
              }}</bdi
              ><OutcomeBadge :value="item.expected" />
            </div>
          </article>
        </div>
      </section></div
  ></AppShell>
</template>

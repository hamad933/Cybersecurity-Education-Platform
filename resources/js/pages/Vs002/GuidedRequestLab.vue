<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import OutcomeBadge from '../../components/OutcomeBadge.vue';
import Vs002Nav from '../../components/Vs002Nav.vue';
import WebDecisionTrace from '../../components/WebDecisionTrace.vue';
type Case = { case_id: string; title_ar: string; expected: string };
type Trace = {
  request_id: string;
  correlation_id: string;
  decision: string;
  actor_id: string;
  target_resource_id: string;
  authentication_result: string;
  decisive_rule_id: string;
  response_status: number;
  response_shape_id: string;
  redaction_result: {
    included_fields: string[];
    excluded_fields: string[];
    secrets_stored: boolean;
  };
  trace_digest: string;
  trust_boundary_steps: { boundary_id: string; boundary: string; result: string }[];
};
type Run = {
  id: string;
  case_id: string;
  seed: number;
  outcome: string;
  policy_revision_id: string;
  trace: Trace | null;
};
type Policy = { id: string; revision: number; mode: string; state: string };
type Contract = { id: string; method: string; route_template: string };
const props = defineProps<{
  scenario: { scenario_id: string; revision: number; digest: string; cases: Case[] };
  policies: Policy[];
  contract: Contract;
  runs: Run[];
}>();
const page = usePage<{ flash?: { status?: string }; errors?: Record<string, string> }>();
const form = useForm({ case_id: 'CASE-WEB-002', seed: 8002, idempotency_key: '' });
const securePolicy = () => props.policies.find((p) => p.mode === 'secure');
</script>

<template>
  <Head title="مختبر طلب Web وAPI" /><AppShell
    ><Vs002Nav />
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="text-sm font-bold text-fuchsia-300">
          INSTITUTIONAL SIMULATOR · SYNTHETIC REQUEST
        </p>
        <h1 class="mt-2 text-3xl font-black sm:text-4xl">مختبر حدود طلب Case File</h1>
        <p class="mt-3 max-w-3xl leading-7 text-slate-400">
          طلب اصطناعي محفوظ داخل العملية؛ لا target حي، لا scanner، لا enumeration، ولا ادعاء لسلوك
          متصفح أو شبكة غير مختبر.
        </p>
      </div>
      <div class="rounded-xl border border-amber-700 bg-amber-950/40 p-4">
        <bdi class="font-mono font-bold">SIMULATED</bdi>
        <p class="text-sm">GET /api/case-files/{caseFileId}</p>
      </div>
    </header>
    <p
      v-if="page.props.flash?.status"
      role="status"
      class="mt-5 rounded-xl border border-fuchsia-800 bg-fuchsia-950 p-4"
    >
      {{ page.props.flash.status }}
    </p>
    <p
      v-if="page.props.errors && Object.keys(page.props.errors).length"
      role="alert"
      class="mt-5 rounded-xl border border-rose-800 bg-rose-950 p-4"
    >
      تعذر الإجراء: {{ Object.values(page.props.errors)[0] }}
    </p>
    <div class="mt-7 grid gap-6 lg:grid-cols-[350px_minmax(0,1fr)]">
      <aside class="space-y-4">
        <form
          class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5"
          @submit.prevent="form.post('/vs002/lab/run', { preserveScroll: true })"
        >
          <label for="vs2-case">الحالة المنشورة</label
          ><select id="vs2-case" v-model="form.case_id" class="form-input focus-ring mt-2">
            <option v-for="item in scenario.cases" :key="item.case_id" :value="item.case_id">
              {{ item.title_ar }}
            </option></select
          ><label for="vs2-seed" class="mt-4 block">البذرة</label
          ><input
            id="vs2-seed"
            v-model="form.seed"
            type="number"
            min="1"
            max="4294967295"
            class="form-input focus-ring direction-ltr mt-2 text-left"
          /><label for="vs2-key" class="mt-4 block">مفتاح التكرار (اختياري)</label
          ><input
            id="vs2-key"
            v-model="form.idempotency_key"
            class="form-input focus-ring direction-ltr mt-2 text-left"
          /><button
            class="focus-ring mt-5 w-full rounded-lg bg-fuchsia-400 px-4 py-3 font-black text-slate-950"
          >
            شغّل الطلب
          </button>
        </form>
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
          <h2 class="font-bold">السياسة والإصلاح</h2>
          <ul class="direction-ltr mt-3 space-y-2 text-left font-mono text-xs">
            <li v-for="policy in policies" :key="policy.id">
              rev {{ policy.revision }} · {{ policy.mode }} · {{ policy.state }}
            </li>
          </ul>
          <button
            class="focus-ring mt-4 w-full rounded-lg border border-emerald-600 px-4 py-2 font-bold text-emerald-200"
            @click="router.post('/vs002/remediation', {}, { preserveScroll: true })"
          >
            إنشاء Policy Revision آمنة
          </button>
        </section>
      </aside>
      <main class="min-w-0">
        <div v-if="runs[0]" class="space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="direction-ltr font-mono text-xs">
                {{ runs[0].case_id }} · seed {{ runs[0].seed }}
              </p>
              <p class="direction-ltr mt-1 font-mono text-[10px] break-all text-slate-500">
                run {{ runs[0].id }}
              </p>
            </div>
            <div class="flex gap-2">
              <OutcomeBadge :value="runs[0].outcome" /><button
                class="focus-ring rounded-lg border border-slate-600 px-3 py-2 text-sm"
                @click="
                  router.post(`/vs002/lab/${runs[0].id}/replay`, {}, { preserveScroll: true })
                "
              >
                إعادة مثبتة
              </button>
            </div>
          </div>
          <WebDecisionTrace v-if="runs[0].trace" :trace="runs[0].trace" />
        </div>
        <div
          v-else
          class="rounded-2xl border border-dashed border-slate-700 p-10 text-center text-slate-400"
        >
          شغّل حالة لعرض أثر القرار.
        </div>
        <h2 class="mt-7 font-bold">مصفوفة الحالات الاثنتي عشرة</h2>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
          <article
            v-for="item in scenario.cases"
            :key="item.case_id"
            class="rounded-xl border border-slate-800 bg-slate-900/50 p-3"
          >
            <p class="text-sm font-bold">{{ item.title_ar }}</p>
            <div class="mt-2 flex items-center justify-between gap-2">
              <bdi class="font-mono text-[10px] text-slate-500">{{ item.case_id }}</bdi
              ><OutcomeBadge :value="item.expected" />
            </div>
          </article>
        </div>
        <section
          v-if="securePolicy() && runs.find((run) => run.case_id === 'CASE-WEB-002')"
          class="mt-6 rounded-2xl border border-emerald-800 bg-emerald-950/20 p-5"
        >
          <h2 class="font-bold text-emerald-200">تحقق الإصلاح</h2>
          <p class="mt-2 text-sm text-slate-300">
            انتقل إلى سجل الأدلة لاختيار Finding وربطه بالتشغيل الضعيف وسياسة الإصلاح.
          </p>
          <a
            href="/vs002/evidence"
            class="focus-ring mt-3 inline-block rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950"
            >فتح الأدلة والتحقق</a
          >
        </section>
      </main>
    </div>
  </AppShell>
</template>

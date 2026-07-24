<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import OutcomeBadge from '../../components/OutcomeBadge.vue';
import Vs002Nav from '../../components/Vs002Nav.vue';
type Decision = { decision: string; rationale: string };
type Evidence = {
  id: string;
  case_id: string;
  origin: string;
  result: string;
  run_id: string;
  policy_revision_id: string;
  remediation_revision_id: string | null;
  trace_digest: string;
  content_digest: string;
  decision: Decision | null;
};
type Finding = {
  id: string;
  category: string;
  scenario_run_id: string;
  policy_revision_id: string;
  decisive_missing_check: string;
  status: string;
  verification: { status: string } | null;
};
type Mastery = { status: string; evaluation_digest: string };
type Trigger = { id: string; failure_class: string; source_reference: string };
type Policy = { id: string; mode: string };
const props = defineProps<{
  evidence: Evidence[];
  findings: Finding[];
  mastery: Mastery | null;
  triggers: Trigger[];
  policies: Policy[];
}>();
const page = usePage<{ flash?: { status?: string }; errors?: Record<string, string> }>();
const decision = useForm({
  decision: 'ACCEPTED',
  rationale: 'الدليل المحاكى مثبت بالمراجعات والأثر وحدوده مناسبة للادعاء.',
});
const verify = useForm({
  vulnerable_run_id: '',
  remediation_policy_revision_id: '',
  idempotency_key: '',
});
const configureVerification = (finding: Finding) => {
  verify.vulnerable_run_id = finding.scenario_run_id;
  const fixed = props.policies.find((policy) => policy.mode === 'secure');
  verify.remediation_policy_revision_id = fixed?.id ?? '';
};
</script>

<template>
  <Head title="أدلة VS-002 وإتقانها" /><AppShell
    ><Vs002Nav />
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="text-sm font-bold text-fuchsia-300">MOD-EVD + MOD-LRN</p>
        <h1 class="mt-2 text-3xl font-black">الكشف والإصلاح والتحقق والإتقان</h1>
        <p class="mt-3 max-w-3xl text-slate-400">
          Finding مقيد، Policy Revision جديدة، وتشغيل تحقق يربط الحالة الضعيفة بالقرار المصحح؛
          القبول البشري لا يتحول تلقائياً إلى إتقان.
        </p>
      </div>
      <div class="rounded-xl border border-slate-700 bg-slate-900 p-4">
        <p class="text-xs text-slate-500">BALANCED MASTERY</p>
        <div class="mt-2 flex gap-3">
          <OutcomeBadge :value="mastery?.status ?? 'NOT_MASTERED'" /><button
            class="focus-ring rounded-lg border border-fuchsia-600 px-3 py-2"
            @click="router.post('/vs002/mastery/evaluate', {}, { preserveScroll: true })"
          >
            إعادة التقييم
          </button>
        </div>
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
      {{ Object.values(page.props.errors)[0] }}
    </p>
    <div class="mt-7 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
      <main class="space-y-4">
        <h2 class="text-xl font-bold">الأدلة المحاكية</h2>
        <article
          v-for="record in evidence"
          :key="record.id"
          class="min-w-0 rounded-2xl border border-slate-800 bg-slate-900/70 p-5"
        >
          <div class="flex flex-wrap justify-between gap-3">
            <div>
              <bdi class="font-mono text-xs text-slate-500">{{ record.case_id }}</bdi>
              <div class="mt-2 flex gap-2">
                <OutcomeBadge :value="record.result" /><span
                  class="rounded border border-amber-800 px-2 py-1 font-mono text-xs text-amber-200"
                  >{{ record.origin }}</span
                >
              </div>
            </div>
            <span v-if="record.decision" class="text-emerald-300">{{
              record.decision.decision
            }}</span>
          </div>
          <dl class="direction-ltr mt-4 text-left font-mono text-[10px] break-all text-slate-500">
            <dt>trace</dt>
            <dd>{{ record.trace_digest }}</dd>
            <dt class="mt-2">evidence</dt>
            <dd>{{ record.content_digest }}</dd>
          </dl>
          <form
            v-if="!record.decision"
            class="mt-4 grid gap-3 sm:grid-cols-[160px_minmax(0,1fr)_auto]"
            @submit.prevent="
              decision.post(`/vs002/evidence/${record.id}/decision`, { preserveScroll: true })
            "
          >
            <select v-model="decision.decision" class="form-input focus-ring">
              <option>ACCEPTED</option>
              <option>REJECTED</option>
              <option>NEEDS_REVIEW</option></select
            ><input
              v-model="decision.rationale"
              minlength="12"
              maxlength="1000"
              required
              class="form-input focus-ring"
            /><button
              class="focus-ring rounded-lg bg-fuchsia-400 px-4 py-2 font-bold text-slate-950"
            >
              قرار
            </button>
          </form>
        </article>
        <p
          v-if="evidence.length === 0"
          class="rounded-xl border border-dashed border-slate-700 p-8 text-center text-slate-400"
        >
          شغّل حالات المختبر أولاً.
        </p>
      </main>
      <aside class="space-y-5">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
          <h2 class="font-bold">Security Findings</h2>
          <article
            v-for="finding in findings"
            :key="finding.id"
            class="mt-3 rounded-xl border border-slate-700 p-3"
          >
            <p class="direction-ltr font-mono text-xs text-amber-200">
              {{ finding.category }} · {{ finding.status }}
            </p>
            <p class="mt-2 text-sm">{{ finding.decisive_missing_check }}</p>
            <p v-if="finding.verification" class="mt-2 text-sm text-emerald-300">VERIFIED_FIXED</p>
            <button
              v-else
              class="focus-ring mt-3 rounded-lg border border-emerald-600 px-3 py-2 text-sm"
              @click="configureVerification(finding)"
            >
              تهيئة التحقق
            </button>
            <form
              v-if="!finding.verification && verify.vulnerable_run_id === finding.scenario_run_id"
              class="mt-3 space-y-2"
              @submit.prevent="
                verify.post(`/vs002/findings/${finding.id}/verify`, { preserveScroll: true })
              "
            >
              <input
                v-model="verify.vulnerable_run_id"
                readonly
                class="form-input direction-ltr text-left text-xs"
              /><input
                v-model="verify.remediation_policy_revision_id"
                required
                placeholder="Remediation policy UUID"
                class="form-input focus-ring direction-ltr text-left text-xs"
              /><button
                class="focus-ring w-full rounded-lg bg-emerald-400 px-3 py-2 font-bold text-slate-950"
              >
                تشغيل وربط التحقق
              </button>
            </form>
          </article>
        </section>
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
          <h2 class="font-bold">مراجعة مبنية على الفشل</h2>
          <ul class="mt-3 space-y-2">
            <li
              v-for="trigger in triggers"
              :key="trigger.id"
              class="rounded-lg border border-slate-700 p-3"
            >
              <bdi class="font-mono text-xs text-amber-200">{{ trigger.failure_class }}</bdi>
              <p class="mt-1 text-xs text-slate-500">{{ trigger.source_reference }}</p>
            </li>
          </ul>
          <p v-if="triggers.length === 0" class="mt-3 text-sm text-slate-500">
            لا توجد محفزات بعد.
          </p>
        </section>
      </aside>
    </div>
  </AppShell>
</template>

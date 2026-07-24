<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import OutcomeBadge from '../../components/OutcomeBadge.vue';
import Vs001Nav from '../../components/Vs001Nav.vue';
type Evidence = {
  id: string;
  case_id: string;
  origin: string;
  result: string;
  trace_digest: string;
  content_digest: string;
  locked: boolean;
  decision: { decision: string; rationale: string } | null;
};
type Mastery = { status: string; evaluation_digest: string; evidence_record_ids: string[] };
type Trigger = { id: string; failure_class: string; source_reference: string; status: string };
defineProps<{ evidence: Evidence[]; mastery: Mastery | null; triggers: Trigger[] }>();
const page = usePage<{ flash?: { status?: string } }>();
const form = useForm({
  decision: 'ACCEPTED',
  rationale: 'الدليل مرتبط بالمراجعات والمصدر ومناسب لحدود المحاكاة.',
});
const decide = (id: string) =>
  form.post(`/vs001/evidence/${id}/decision`, { preserveScroll: true });
const evaluate = () => router.post('/vs001/mastery/evaluate', {}, { preserveScroll: true });
</script>

<template>
  <Head title="الأدلة والإتقان" />
  <AppShell
    ><Vs001Nav />
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="text-sm font-bold text-cyan-300">MOD-EVD + MOD-LRN</p>
        <h1 class="mt-2 text-3xl font-black">قرار الدليل والإتقان</h1>
        <p class="mt-3 max-w-3xl leading-7 text-slate-400">
          الإتقان قاعدة إصدار مؤقتة: دليل إيجابي وسلبي وحالة غير مدعومة ومصدر محفوظ وإعادة تشغيل
          مطابقة. لا تكفي نتيجة ناجحة وحدها.
        </p>
      </div>
      <div class="rounded-2xl border border-slate-700 bg-slate-900 p-5">
        <p class="text-xs text-slate-500">MASTERY STATE</p>
        <div class="mt-2 flex items-center gap-3">
          <OutcomeBadge :value="mastery?.status ?? 'NOT_MASTERED'" /><button
            class="focus-ring rounded-lg border border-cyan-700 px-3 py-2 text-sm font-bold text-cyan-200"
            @click="evaluate"
          >
            إعادة التقييم
          </button>
        </div>
      </div>
    </header>
    <p
      v-if="page.props.flash?.status"
      class="mt-5 rounded-xl border border-cyan-800 bg-cyan-950 p-4"
      role="status"
    >
      {{ page.props.flash.status }}
    </p>
    <div class="mt-7 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
      <section class="space-y-4">
        <h2 class="text-xl font-bold">سجل الأدلة المحاكية</h2>
        <article
          v-for="record in evidence"
          :key="record.id"
          class="min-w-0 rounded-2xl border border-slate-800 bg-slate-900/70 p-5"
        >
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="direction-ltr font-mono text-xs text-slate-500">{{ record.case_id }}</p>
              <div class="mt-2 flex items-center gap-2">
                <OutcomeBadge :value="record.result" /><bdi
                  class="rounded border border-amber-800 px-2 py-1 font-mono text-xs text-amber-200"
                  >{{ record.origin }}</bdi
                >
              </div>
            </div>
            <span v-if="record.locked" class="text-sm text-emerald-300">مقفل بعد القبول</span>
          </div>
          <dl class="direction-ltr mt-4 text-left font-mono text-[11px] break-all text-slate-500">
            <div>
              <dt>trace</dt>
              <dd>{{ record.trace_digest }}</dd>
            </div>
            <div class="mt-2">
              <dt>evidence</dt>
              <dd>{{ record.content_digest }}</dd>
            </div>
          </dl>
          <div v-if="record.decision" class="mt-4 rounded-lg border border-slate-700 p-3">
            <p class="font-bold text-cyan-300">{{ record.decision.decision }}</p>
            <p class="mt-1 text-sm text-slate-300">{{ record.decision.rationale }}</p>
          </div>
          <form
            v-else
            class="mt-4 grid gap-3 sm:grid-cols-[180px_minmax(0,1fr)_auto]"
            @submit.prevent="decide(record.id)"
          >
            <select v-model="form.decision" class="form-input focus-ring">
              <option>ACCEPTED</option>
              <option>REJECTED</option>
              <option>NEEDS_REVIEW</option></select
            ><input
              v-model="form.rationale"
              class="form-input focus-ring"
              minlength="12"
              maxlength="1000"
              required
            /><button class="focus-ring rounded-lg bg-cyan-400 px-4 py-2 font-bold text-slate-950">
              سجّل القرار
            </button>
          </form>
        </article>
        <p
          v-if="evidence.length === 0"
          class="rounded-xl border border-dashed border-slate-700 p-8 text-center text-slate-400"
        >
          لم تُصدر أدلة بعد. شغّل حالات المختبر أولًا.
        </p>
      </section>
      <aside class="space-y-5">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
          <h2 class="font-bold">قاعدة الإتقان المؤقتة</h2>
          <ul class="mt-4 space-y-2 text-sm text-slate-300">
            <li>✓ قرار ALLOW مقبول</li>
            <li>✓ قرار DENY مقبول</li>
            <li>✓ تعامل صحيح مع UNSUPPORTED_STATE</li>
            <li>✓ مرجع سلطة محفوظ</li>
            <li>✓ إعادة تشغيل مطابقة</li>
          </ul>
          <p
            v-if="mastery"
            class="direction-ltr mt-4 font-mono text-[10px] break-all text-slate-500"
          >
            {{ mastery.evaluation_digest }}
          </p>
        </section>
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
          <h2 class="font-bold">مراجعة مبنية على الفشل</h2>
          <ul class="mt-3 space-y-3">
            <li
              v-for="trigger in triggers"
              :key="trigger.id"
              class="rounded-lg border border-slate-700 p-3"
            >
              <bdi class="direction-ltr font-mono text-xs text-amber-200">{{
                trigger.failure_class
              }}</bdi>
              <p class="mt-1 text-xs text-slate-500">{{ trigger.source_reference }}</p>
            </li>
          </ul>
          <p v-if="triggers.length === 0" class="mt-3 text-sm text-slate-500">
            لا توجد محفزات مفتوحة.
          </p>
        </section>
      </aside>
    </div></AppShell
  >
</template>

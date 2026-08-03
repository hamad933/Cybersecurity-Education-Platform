<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import Vs002Nav from '../../components/Vs002Nav.vue';
type PracticeDefinition = { prompt_ar: string; case_id: string; answer_key_version: number };
type Attempt = { outcome: string; failure_class: string | null };
defineProps<{ practice: { definition: PracticeDefinition }; latestAttempt: Attempt | null }>();
const page = usePage<{ flash?: { status?: string } }>();
const form = useForm({
  actor: 'SIM-BOB',
  resource_owner: 'SIM-ALICE',
  requested_action: 'case_file.read',
  missing_trust_boundary: 'authorization_policy',
  expected_policy_decision: 'DENY',
  expected_http_response_class: '4xx',
  decisive_rule: 'WEB-RULE-CROSS-OWNER-DENY',
  safe_detection_field: 'trace_digest',
  rationale: '',
});
</script>

<template>
  <Head title="تدريب VS-002" /><AppShell
    ><Vs002Nav />
    <div class="mx-auto max-w-4xl">
      <header>
        <p class="text-sm font-bold text-fuchsia-300">MOD-LRN · VERSIONED ANSWER KEY</p>
        <h1 class="mt-2 text-3xl font-black">حلّل طلباً عابراً للملكية</h1>
        <p class="mt-3 leading-7 text-slate-400">{{ practice.definition.prompt_ar }}</p>
        <p class="direction-ltr mt-2 font-mono text-xs">
          {{ practice.definition.case_id }} · answer key rev
          {{ practice.definition.answer_key_version }}
        </p>
      </header>
      <p
        v-if="page.props.flash?.status"
        role="status"
        class="mt-5 rounded-xl border border-fuchsia-800 bg-fuchsia-950 p-4"
      >
        {{ page.props.flash.status }}
      </p>
      <form
        class="mt-7 grid gap-4 rounded-2xl border border-slate-800 bg-slate-900/70 p-5 sm:grid-cols-2"
        @submit.prevent="form.post('/vs002/practice', { preserveScroll: true })"
      >
        <label
          >الممثل<select v-model="form.actor" class="form-input focus-ring mt-2">
            <option>SIM-ALICE</option>
            <option>SIM-BOB</option>
            <option>SIM-ADMIN</option>
          </select></label
        >
        <label
          >مالك المورد<select v-model="form.resource_owner" class="form-input focus-ring mt-2">
            <option>SIM-ALICE</option>
            <option>SIM-BOB</option>
          </select></label
        >
        <label
          >الفعل<select v-model="form.requested_action" class="form-input focus-ring mt-2">
            <option>case_file.read</option>
            <option>case_file.update</option>
          </select></label
        >
        <label
          >حد الثقة المفقود<select
            v-model="form.missing_trust_boundary"
            class="form-input focus-ring mt-2"
          >
            <option>authentication_context</option>
            <option>resource_lookup</option>
            <option>authorization_policy</option>
            <option>response_serialization</option>
          </select></label
        >
        <label
          >قرار السياسة<select
            v-model="form.expected_policy_decision"
            class="form-input focus-ring mt-2"
          >
            <option>ALLOW</option>
            <option>DENY</option>
            <option>ALLOW_AUTHENTICATED_ONLY</option>
          </select></label
        >
        <label
          >فئة HTTP<select
            v-model="form.expected_http_response_class"
            class="form-input focus-ring mt-2"
          >
            <option>2xx</option>
            <option>4xx</option>
            <option>5xx</option>
          </select></label
        >
        <label
          >القاعدة الحاسمة<input
            v-model="form.decisive_rule"
            required
            class="form-input focus-ring direction-ltr mt-2 text-left"
        /></label>
        <label
          >حقل الكشف الآمن<select
            v-model="form.safe_detection_field"
            class="form-input focus-ring mt-2"
          >
            <option>trace_digest</option>
            <option>password</option>
            <option>session_token</option>
            <option>request_body</option>
          </select></label
        >
        <label class="sm:col-span-2"
          >التعليل<textarea
            v-model="form.rationale"
            required
            minlength="12"
            maxlength="1000"
            class="form-input focus-ring mt-2 min-h-28"
            placeholder="اربط الملكية بقرار الخادم الافتراضي بالرفض."
          />
        </label>
        <button
          class="focus-ring rounded-lg bg-fuchsia-400 px-5 py-3 font-bold text-slate-950 sm:col-span-2"
        >
          تحقق وسجّل المحاولة
        </button>
      </form>
      <section v-if="latestAttempt" class="mt-5 rounded-xl border border-slate-700 p-4">
        <h2 class="font-bold">آخر محاولة: {{ latestAttempt.outcome }}</h2>
        <p v-if="latestAttempt.failure_class" class="direction-ltr mt-2 font-mono text-amber-300">
          {{ latestAttempt.failure_class }}
        </p>
      </section>
    </div></AppShell
  >
</template>

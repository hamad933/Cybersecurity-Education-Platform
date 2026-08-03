<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import OutcomeBadge from '../../components/OutcomeBadge.vue';
import Vs001Nav from '../../components/Vs001Nav.vue';
type Practice = {
  definition: {
    prompt_ar: string;
    case_id: string;
    choices: string[];
    requires_rationale: boolean;
  };
};
type Attempt = {
  outcome: string;
  answer: { selected_outcome: string; rationale: string };
  failure_class: string | null;
};
defineProps<{ practice: Practice; latestAttempt: Attempt | null }>();
const page = usePage<{ flash?: { status?: string } }>();
const form = useForm({
  selected_outcome: '',
  decisive_step_id: '',
  decisive_ace_id: '',
  relevant_requested_mask: '0x00000001',
  remaining_mask: '0x00000001',
  rationale: '',
});
const submit = () => form.post('/vs001/practice', { preserveScroll: true });
</script>

<template>
  <Head title="التدريب المصغر" />
  <AppShell
    ><Vs001Nav />
    <div class="mx-auto max-w-3xl">
      <header>
        <p class="text-sm font-bold text-cyan-300">MOD-LRN · MICRO PRACTICE</p>
        <h1 class="mt-2 text-3xl font-black">قرار واحد، وتعليل قابل للفحص</h1>
        <p class="mt-3 leading-7 text-slate-400">{{ practice.definition.prompt_ar }}</p>
      </header>
      <p
        v-if="page.props.flash?.status"
        class="mt-5 rounded-xl border border-cyan-800 bg-cyan-950 p-4"
        role="status"
      >
        {{ page.props.flash.status }}
      </p>
      <form
        class="mt-7 rounded-2xl border border-slate-800 bg-slate-900/70 p-5 sm:p-7"
        @submit.prevent="submit"
      >
        <p class="direction-ltr font-mono text-sm text-slate-400">
          {{ practice.definition.case_id }}
        </p>
        <h2 class="mt-4 text-xl font-bold">رفض صريح للمستخدم يسبق سماحًا لاحقًا. ما النتيجة؟</h2>
        <fieldset class="mt-6 grid gap-3 sm:grid-cols-2">
          <legend class="sr-only">اختر النتيجة</legend>
          <label
            v-for="choice in practice.definition.choices"
            :key="choice"
            class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-700 bg-slate-950 p-4 focus-within:ring-3 focus-within:ring-cyan-400"
            ><input
              v-model="form.selected_outcome"
              type="radio"
              name="decision"
              :value="choice"
              required
            /><bdi class="font-mono font-bold">{{ choice }}</bdi></label
          >
        </fieldset>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
          <label class="font-bold" for="step"
            >معرّف الخطوة الحاسمة<input
              id="step"
              v-model="form.decisive_step_id"
              required
              maxlength="80"
              class="form-input focus-ring direction-ltr mt-2 text-left"
              placeholder="ace-step-1"
          /></label>
          <label class="font-bold" for="ace"
            >معرّف ACE الحاسمة<input
              id="ace"
              v-model="form.decisive_ace_id"
              required
              maxlength="80"
              class="form-input focus-ring direction-ltr mt-2 text-left"
              placeholder="ACE-DENY-001"
          /></label>
          <label class="font-bold" for="requested-mask"
            >القناع المطلوب ذي الصلة<input
              id="requested-mask"
              v-model="form.relevant_requested_mask"
              required
              class="form-input focus-ring direction-ltr mt-2 text-left"
          /></label>
          <label class="font-bold" for="remaining-mask"
            >القناع المتبقي<input
              id="remaining-mask"
              v-model="form.remaining_mask"
              required
              class="form-input focus-ring direction-ltr mt-2 text-left"
          /></label>
        </div>
        <label class="mt-6 block font-bold" for="rationale">التعليل والقاعدة أو ACE الحاسمة</label
        ><textarea
          id="rationale"
          v-model="form.rationale"
          required
          minlength="12"
          maxlength="1000"
          class="form-input focus-ring mt-2 min-h-32"
          placeholder="اذكر ACE الحاسمة ولماذا لا يصل الفحص إلى السماح اللاحق."
        />
        <p v-if="form.errors.rationale" class="mt-2 text-rose-300">{{ form.errors.rationale }}</p>
        <button
          class="focus-ring mt-5 rounded-lg bg-cyan-400 px-5 py-3 font-bold text-slate-950 disabled:opacity-50"
          :disabled="form.processing"
        >
          تحقق وسجّل المحاولة
        </button>
      </form>
      <section v-if="latestAttempt" class="mt-6 rounded-2xl border border-slate-800 p-5">
        <div class="flex items-center justify-between">
          <h2 class="font-bold">آخر محاولة</h2>
          <OutcomeBadge :value="latestAttempt.answer.selected_outcome" />
        </div>
        <p class="mt-3 text-slate-300">{{ latestAttempt.answer.rationale }}</p>
        <p
          class="mt-3 text-sm"
          :class="latestAttempt.outcome === 'correct' ? 'text-emerald-300' : 'text-amber-300'"
        >
          {{ latestAttempt.outcome }}
        </p>
      </section>
    </div></AppShell
  >
</template>

<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import Vs001Nav from '../../components/Vs001Nav.vue';

type Revision = {
  id: string;
  revision: number;
  state: string;
  lock_version: number;
  blocks: { type: string; body: string }[];
  citations: string[];
  content_digest: string;
  review_rationale: string | null;
  published_at: string | null;
};
const props = defineProps<{ revisions: Revision[]; baseline: string }>();
const page = usePage<{ flash?: { status?: string }; errors?: Record<string, string> }>();
const current = props.revisions[0];
const form = useForm({
  lock_version: current?.lock_version ?? 1,
  blocks: current?.blocks.map((block) => ({ ...block })) ?? [{ type: 'paragraph', body: '' }],
  citations: current?.citations.slice() ?? [],
});
const review = useForm({ rationale: '' });
const restore = (id: string) => router.post(`/vs001/lesson/${id}/restore`);
const update = (id: string) => form.post(`/vs001/lesson/${id}/update`, { preserveScroll: true });
const submit = (id: string) =>
  router.post(`/vs001/lesson/${id}/submit`, {}, { preserveScroll: true });
const approve = (id: string) =>
  router.post(`/vs001/lesson/${id}/approve`, {}, { preserveScroll: true });
const publish = (id: string) =>
  router.post(`/vs001/lesson/${id}/publish`, {}, { preserveScroll: true });
const returnDraft = (id: string) =>
  review.post(`/vs001/lesson/${id}/return`, { preserveScroll: true });
</script>

<template>
  <Head title="محرر الدرس" />
  <AppShell>
    <Vs001Nav />
    <header>
      <p class="text-sm font-bold text-cyan-300">MOD-KNO · REVISION WORKSPACE</p>
      <h1 class="mt-2 text-3xl font-black">مسار تأليف ومراجعة الدرس</h1>
      <p class="mt-3 max-w-3xl leading-7 text-slate-400">
        مسودة بقفل تفاؤلي، ثم إرسال ومراجعة وقرار صريح ونشر غير قابل للتعديل. الاستعادة تنشئ مسودة
        جديدة ولا تعيد فتح النسخة المنشورة.
      </p>
    </header>
    <p
      v-if="page.props.flash?.status"
      class="mt-5 rounded-xl border border-emerald-700 bg-emerald-950 p-4 text-emerald-100"
      role="status"
    >
      {{ page.props.flash.status }}
    </p>
    <div class="mt-7 grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
      <aside class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
        <p class="text-xs text-slate-500">AUTHORITY BASELINE</p>
        <p class="direction-ltr mt-2 font-mono text-sm break-all text-cyan-300">{{ baseline }}</p>
        <h2 class="mt-6 font-bold">سجل المراجعات</h2>
        <ol class="mt-3 space-y-2">
          <li
            v-for="revision in revisions"
            :key="revision.id"
            class="rounded-lg border border-slate-700 p-3"
          >
            <div class="flex justify-between gap-3">
              <bdi class="font-mono">rev {{ revision.revision }}</bdi>
              <span class="text-xs text-emerald-300">{{ revision.state }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">lock {{ revision.lock_version }}</p>
          </li>
        </ol>
      </aside>
      <section
        v-if="current"
        class="min-w-0 rounded-2xl border border-slate-800 bg-slate-900/70 p-5 sm:p-7"
      >
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <p class="text-xs text-slate-500">المحتوى المنظم</p>
            <h2 class="mt-1 text-xl font-bold">KU-AD-02 · مراجعة {{ current.revision }}</h2>
          </div>
          <button
            v-if="current.state === 'published'"
            type="button"
            class="focus-ring rounded-lg bg-cyan-400 px-4 py-2 font-bold text-slate-950"
            @click="restore(current.id)"
          >
            إنشاء مسودة من المنشور
          </button>
        </div>

        <form
          v-if="current.state === 'draft'"
          class="mt-6 space-y-4"
          @submit.prevent="update(current.id)"
        >
          <label
            v-for="(block, index) in form.blocks"
            :key="index"
            class="block rounded-xl border border-slate-700 bg-slate-950 p-4"
          >
            <span class="direction-ltr text-xs font-bold text-cyan-300">{{ block.type }}</span>
            <textarea
              v-model="block.body"
              required
              maxlength="4000"
              class="form-input focus-ring mt-2 min-h-28"
            />
          </label>
          <p
            v-if="page.props.errors && Object.keys(page.props.errors).length"
            class="text-rose-300"
            role="alert"
          >
            تعذر الحفظ؛ راجع الحقول وحدود المحتوى المنظم.
          </p>
          <div class="flex flex-wrap gap-3">
            <button class="focus-ring rounded-lg bg-cyan-400 px-4 py-2 font-bold text-slate-950">
              حفظ بالقفل التفاؤلي
            </button>
            <button
              type="button"
              class="focus-ring rounded-lg border border-slate-600 px-4 py-2 font-bold"
              @click="submit(current.id)"
            >
              إرسال للمراجعة
            </button>
          </div>
        </form>

        <div v-else class="mt-6 space-y-4">
          <article
            v-for="(block, index) in current.blocks"
            :key="index"
            class="rounded-xl border border-slate-700 bg-slate-950 p-4"
          >
            <p class="direction-ltr text-xs font-bold text-cyan-300">{{ block.type }}</p>
            <p class="mt-2 leading-7">{{ block.body }}</p>
          </article>
        </div>

        <form
          v-if="current.state === 'under_review'"
          class="mt-6 rounded-xl border border-amber-800 p-4"
          @submit.prevent="returnDraft(current.id)"
        >
          <label class="font-bold" for="review-rationale">تعليل الإعادة إلى المسودة</label>
          <textarea
            id="review-rationale"
            v-model="review.rationale"
            minlength="12"
            maxlength="1000"
            class="form-input focus-ring mt-2 min-h-24"
          />
          <div class="mt-3 flex flex-wrap gap-3">
            <button class="focus-ring rounded-lg border border-amber-600 px-4 py-2 font-bold">
              إعادة مع التعليل
            </button>
            <button
              type="button"
              class="focus-ring rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950"
              @click="approve(current.id)"
            >
              اعتماد
            </button>
          </div>
        </form>
        <button
          v-if="current.state === 'reviewed'"
          class="focus-ring mt-6 rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950"
          @click="publish(current.id)"
        >
          نشر النسخة غير القابلة للتعديل
        </button>
        <p class="direction-ltr mt-6 font-mono text-xs break-all text-slate-500">
          content sha256:{{ current.content_digest }}
        </p>
      </section>
    </div>
  </AppShell>
</template>

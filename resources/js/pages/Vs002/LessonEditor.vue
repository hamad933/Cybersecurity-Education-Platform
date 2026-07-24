<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import Vs002Nav from '../../components/Vs002Nav.vue';
type Revision = {
  id: string;
  revision: number;
  state: string;
  lock_version: number;
  blocks: { type: string; body: string }[];
  citations: string[];
  content_digest: string;
  review_rationale: string | null;
};
const props = defineProps<{ revisions: Revision[]; baseline: string }>();
const page = usePage<{ flash?: { status?: string }; errors?: Record<string, string> }>();
const current = props.revisions[0];
const form = useForm({
  lock_version: current?.lock_version ?? 1,
  blocks: current?.blocks.map((b) => ({ ...b })) ?? [],
  citations: current?.citations.slice() ?? [],
});
const review = useForm({ rationale: '' });
const post = (action: string) =>
  router.post(`/vs002/lesson/${current.id}/${action}`, {}, { preserveScroll: true });
</script>

<template>
  <Head title="محرر درس VS-002" /><AppShell
    ><Vs002Nav />
    <header>
      <p class="text-sm font-bold text-fuchsia-300">MOD-KNO · REVISION WORKFLOW</p>
      <h1 class="mt-2 text-3xl font-black">تأليف ومراجعة درس حدود الثقة</h1>
      <p class="mt-3 text-slate-400">
        قفل تفاؤلي، مراجعة صريحة، وخط أساس سلطة مثبت قبل نشر نسخة غير قابلة للتعديل.
      </p>
    </header>
    <p
      v-if="page.props.flash?.status"
      role="status"
      class="mt-5 rounded-xl border border-emerald-700 bg-emerald-950 p-4"
    >
      {{ page.props.flash.status }}
    </p>
    <div class="mt-7 grid gap-6 lg:grid-cols-[270px_minmax(0,1fr)]">
      <aside class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
        <p class="direction-ltr font-mono text-xs break-all text-fuchsia-300">{{ baseline }}</p>
        <ol class="mt-5 space-y-2">
          <li
            v-for="revision in revisions"
            :key="revision.id"
            class="rounded-lg border border-slate-700 p-3"
          >
            <span class="font-mono">rev {{ revision.revision }}</span
            ><span class="float-left text-xs text-emerald-300">{{ revision.state }}</span>
            <p class="text-xs text-slate-500">lock {{ revision.lock_version }}</p>
          </li>
        </ol>
      </aside>
      <section
        v-if="current"
        class="min-w-0 rounded-2xl border border-slate-800 bg-slate-900/70 p-5"
      >
        <div class="flex flex-wrap justify-between gap-3">
          <h2 class="text-xl font-bold">KU-D05-004 · مراجعة {{ current.revision }}</h2>
          <button
            v-if="current.state === 'published'"
            class="focus-ring rounded-lg bg-fuchsia-400 px-4 py-2 font-bold text-slate-950"
            @click="post('restore')"
          >
            إنشاء مسودة
          </button>
        </div>
        <form
          v-if="current.state === 'draft'"
          class="mt-5 space-y-3"
          @submit.prevent="
            form.post(`/vs002/lesson/${current.id}/update`, { preserveScroll: true })
          "
        >
          <label
            v-for="(block, index) in form.blocks"
            :key="index"
            class="block rounded-xl border border-slate-700 bg-slate-950 p-4"
            ><span class="direction-ltr text-xs text-fuchsia-300">{{ block.type }}</span
            ><textarea
              v-model="block.body"
              maxlength="4000"
              required
              class="form-input focus-ring mt-2 min-h-24"
            />
          </label>
          <p
            v-if="page.props.errors && Object.keys(page.props.errors).length"
            role="alert"
            class="text-rose-300"
          >
            تعذر الحفظ؛ راجع نوع الكتلة وحدود المحتوى.
          </p>
          <div class="flex flex-wrap gap-3">
            <button class="focus-ring rounded-lg bg-fuchsia-400 px-4 py-2 font-bold text-slate-950">
              حفظ</button
            ><button
              type="button"
              class="focus-ring rounded-lg border border-slate-600 px-4 py-2"
              @click="post('submit')"
            >
              إرسال للمراجعة
            </button>
          </div>
        </form>
        <div v-else class="mt-5 space-y-3">
          <article
            v-for="(block, index) in current.blocks"
            :key="index"
            class="rounded-xl border border-slate-700 bg-slate-950 p-4"
          >
            <p class="direction-ltr text-xs text-fuchsia-300">{{ block.type }}</p>
            <pre
              v-if="['code', 'request', 'response', 'log'].includes(block.type)"
              class="direction-ltr mt-2 overflow-x-auto text-left font-mono text-sm whitespace-pre-wrap"
              >{{ block.body }}</pre>
            <p v-else class="mt-2 leading-7">{{ block.body }}</p>
          </article>
        </div>
        <form
          v-if="current.state === 'under_review'"
          class="mt-5 rounded-xl border border-amber-800 p-4"
          @submit.prevent="
            review.post(`/vs002/lesson/${current.id}/return`, { preserveScroll: true })
          "
        >
          <label for="vs2-rationale">تعليل الإعادة</label
          ><textarea
            id="vs2-rationale"
            v-model="review.rationale"
            required
            minlength="12"
            maxlength="1000"
            class="form-input focus-ring mt-2"
          />
          <div class="mt-3 flex gap-3">
            <button class="focus-ring rounded-lg border border-amber-600 px-4 py-2">إعادة</button
            ><button
              type="button"
              class="focus-ring rounded-lg bg-emerald-400 px-4 py-2 text-slate-950"
              @click="post('approve')"
            >
              اعتماد
            </button>
          </div>
        </form>
        <button
          v-if="current.state === 'reviewed'"
          class="focus-ring mt-5 rounded-lg bg-emerald-400 px-4 py-2 font-bold text-slate-950"
          @click="post('publish')"
        >
          نشر النسخة
        </button>
        <p class="direction-ltr mt-5 font-mono text-[10px] break-all text-slate-500">
          {{ current.content_digest }}
        </p>
      </section>
    </div>
  </AppShell>
</template>

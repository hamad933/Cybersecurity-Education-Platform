<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import KnowledgeTabs from './components/KnowledgeTabs.vue';

type CatalogItem = {
  id: string;
  title_ar: string;
  title_en: string;
  latest_revision: number | null;
  latest_state: string | null;
};
type Revision = {
  id: string;
  revision: number;
  state: string;
  lock_version: number;
  blocks: { type: string; body: string }[];
  citations: string[];
  authority_baseline_id: string | null;
  content_digest: string;
  derived_from_revision_id: string | null;
  published_at: string | null;
  updated_at: string | null;
  editable: boolean;
};
type RevisionSummary = {
  id: string;
  revision: number;
  state: string;
  lock_version: number;
  derived_from_revision_id: string | null;
  published_at: string | null;
  updated_at: string | null;
};
type ActiveUnit = {
  id: string;
  title_ar: string;
  title_en: string;
  revision: Revision | null;
  revisions: RevisionSummary[];
};
type StructureGroup = { capability_id: string | null; items: CatalogItem[] };
type Source = {
  id: string;
  title: string;
  authority_class: string;
  review_status: string;
  claims: { claim_id: string; assessment: string }[];
};
type Placement = {
  id: string;
  capability_id: string;
  knowledge_unit_id: string;
  revision: number;
  lifecycle: Record<string, unknown>;
};

const props = defineProps<{
  catalog: CatalogItem[];
  structure: StructureGroup[];
  active: ActiveUnit | null;
  context: {
    placements: Placement[];
    sources: Source[];
    unresolved_citation_count: number;
  };
}>();

const page = usePage<{ flash?: { status?: string }; errors?: Record<string, string> }>();
const lenses = ['overview', 'sources', 'history'] as const;
type Lens = (typeof lenses)[number];
const lens = ref<Lens>('overview');
const setLens = (value: Lens) => { lens.value = value; };
const blockTypes = ['heading', 'paragraph', 'callout', 'rules', 'boundaries', 'code', 'request', 'response', 'log'];
const technicalTypes = new Set(['code', 'request', 'response', 'log']);

const form = useForm({
  lock_version: props.active?.revision?.lock_version ?? 1,
  blocks: props.active?.revision?.blocks.map((block) => ({ ...block })) ?? [],
  citations: props.active?.revision?.citations.slice() ?? [],
});

const revisionKey = computed(() => props.active?.revision?.id ?? 'none');
watch(revisionKey, () => {
  form.lock_version = props.active?.revision?.lock_version ?? 1;
  form.blocks = props.active?.revision?.blocks.map((block) => ({ ...block })) ?? [];
  form.citations = props.active?.revision?.citations.slice() ?? [];
  form.clearErrors();
});

const save = () => {
  if (!props.active?.revision?.editable) return;
  form.patch(`/knowledge/library/revisions/${props.active.revision.id}`, {
    preserveScroll: true,
  });
};

const restore = () => {
  if (!props.active?.revision || props.active.revision.state !== 'published') return;
  router.post(`/knowledge/library/revisions/${props.active.revision.id}/restore`, {}, { preserveScroll: true });
};

const addBlock = () => form.blocks.push({ type: 'paragraph', body: '' });
const removeBlock = (index: number) => {
  if (form.blocks.length > 1) form.blocks.splice(index, 1);
};
const moveBlock = (index: number, delta: number) => {
  const target = index + delta;
  if (target < 0 || target >= form.blocks.length) return;
  const [block] = form.blocks.splice(index, 1);
  if (!block) return;
  form.blocks.splice(target, 0, block);
};
</script>

<template>
  <Head title="المعرفة والتعلّم — المكتبة" />
  <div dir="rtl" class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto max-w-[1600px] px-4 py-5 sm:px-6">
      <header class="border-b border-slate-800 pb-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <KnowledgeTabs active="library" :object-id="active?.id" />
          <div class="flex items-center gap-2">
            <button
              v-if="active?.revision?.editable"
              type="submit"
              form="knowledge-editor"
              class="focus-ring rounded-lg bg-cyan-400 px-4 py-2 text-sm font-bold text-slate-950 disabled:opacity-50"
              :disabled="form.processing"
            >
              حفظ / تطبيق
            </button>
            <button
              v-else-if="active?.revision?.state === 'published'"
              type="button"
              class="focus-ring rounded-lg border border-cyan-500 px-4 py-2 text-sm font-bold text-cyan-100"
              @click="restore"
            >
              إنشاء مسودة جديدة
            </button>
          </div>
        </div>
      </header>

      <p
        v-if="page.props.flash?.status"
        role="status"
        class="mt-4 rounded-xl border border-emerald-800 bg-emerald-950/60 px-4 py-3 text-sm text-emerald-100"
      >
        {{ page.props.flash.status }}
      </p>

      <div class="mt-4 grid min-h-[720px] gap-4 xl:grid-cols-[260px_minmax(0,1fr)_280px]">
        <aside class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/50 p-4" aria-label="بنية المكتبة">
          <h2 class="text-xs font-bold tracking-wide text-slate-400">بنية المكتبة</h2>
          <div v-if="structure.length" class="mt-4 space-y-5">
            <section v-for="group in structure" :key="group.capability_id ?? 'unplaced'">
              <bdi v-if="group.capability_id" dir="ltr" class="font-mono text-xs text-cyan-300">{{ group.capability_id }}</bdi>
              <p v-else class="text-xs text-amber-300">غير موضوع في Capability حاليًا</p>
              <ul class="mt-2 space-y-1">
                <li v-for="item in group.items" :key="item.id">
                  <Link
                    :href="`/knowledge?object=${encodeURIComponent(item.id)}`"
                    class="focus-ring block rounded-lg px-3 py-2 text-sm"
                    :class="item.id === active?.id ? 'bg-cyan-400/10 text-cyan-100' : 'text-slate-300 hover:bg-slate-800'"
                  >
                    {{ item.title_ar }}
                  </Link>
                </li>
              </ul>
            </section>
          </div>
          <p v-else class="mt-4 text-sm leading-7 text-slate-500">لا توجد وحدات معرفة محفوظة في قاعدة البيانات.</p>
        </aside>

        <main class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/35 p-5 sm:p-7">
          <div v-if="active" class="min-w-0">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-800 pb-5">
              <div class="min-w-0">
                <p class="text-xs font-bold text-cyan-300">وحدة المعرفة القانونية</p>
                <h1 class="mt-2 text-2xl font-black sm:text-3xl">{{ active.title_ar }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-400">
                  <bdi dir="ltr" class="font-mono text-cyan-200">{{ active.id }}</bdi>
                  <span aria-hidden="true">·</span>
                  <bdi dir="ltr">{{ active.title_en }}</bdi>
                </div>
              </div>
              <div v-if="active.revision" class="text-left text-xs text-slate-400">
                <bdi dir="ltr" class="block font-mono text-slate-200">revision {{ active.revision.revision }}</bdi>
                <bdi dir="ltr" class="mt-1 block font-mono text-emerald-300">{{ active.revision.state }}</bdi>
              </div>
            </div>

            <form v-if="active.revision?.editable" id="knowledge-editor" class="mt-6 space-y-4" @submit.prevent="save">
              <article
                v-for="(block, index) in form.blocks"
                :key="`${revisionKey}:${index}`"
                class="rounded-xl border border-slate-800 bg-slate-950/60 p-4"
              >
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <select v-model="block.type" class="form-input focus-ring max-w-44 text-sm" aria-label="نوع الكتلة">
                    <option v-for="type in blockTypes" :key="type" :value="type">{{ type }}</option>
                  </select>
                  <div class="flex gap-1">
                    <button type="button" class="focus-ring rounded border border-slate-700 px-2 py-1 text-xs" @click="moveBlock(index, -1)">↑</button>
                    <button type="button" class="focus-ring rounded border border-slate-700 px-2 py-1 text-xs" @click="moveBlock(index, 1)">↓</button>
                    <button type="button" class="focus-ring rounded border border-rose-900 px-2 py-1 text-xs text-rose-300" @click="removeBlock(index)">حذف</button>
                  </div>
                </div>
                <textarea
                  v-model="block.body"
                  required
                  maxlength="4000"
                  class="form-input focus-ring mt-3 min-h-32 leading-7"
                  :dir="technicalTypes.has(block.type) ? 'ltr' : 'rtl'"
                />
              </article>
              <button type="button" class="focus-ring rounded-lg border border-dashed border-slate-600 px-4 py-2 text-sm text-slate-300" @click="addBlock">
                إضافة كتلة
              </button>
              <p v-if="page.props.errors?.revision" role="alert" class="text-sm text-rose-300">{{ page.props.errors.revision }}</p>
            </form>

            <div v-else-if="active.revision" class="mt-6 space-y-4">
              <article
                v-for="(block, index) in active.revision.blocks"
                :key="index"
                class="rounded-xl border border-slate-800 bg-slate-950/50 p-5"
              >
                <bdi dir="ltr" class="font-mono text-xs text-cyan-300">{{ block.type }}</bdi>
                <pre
                  v-if="technicalTypes.has(block.type)"
                  dir="ltr"
                  class="mt-3 overflow-x-auto whitespace-pre-wrap text-left font-mono text-sm leading-6 text-slate-200"
                >{{ block.body }}</pre>
                <p v-else class="mt-3 whitespace-pre-wrap leading-8 text-slate-200">{{ block.body }}</p>
              </article>
            </div>
            <div v-else class="mt-10 rounded-xl border border-dashed border-slate-700 p-8 text-center">
              <h2 class="font-bold">لا توجد مراجعة محتوى لهذه الوحدة بعد.</h2>
              <p class="mt-2 text-sm text-slate-500">يعرض النظام الحالة الفعلية ولا ينشئ محتوى افتراضيًا.</p>
            </div>

            <section v-if="active.revision?.citations.length" class="mt-7 border-t border-slate-800 pt-5">
              <h2 class="text-sm font-bold">مراجع المحتوى</h2>
              <div class="mt-3 flex flex-wrap gap-2">
                <bdi
                  v-for="citation in active.revision.citations"
                  :key="citation"
                  dir="ltr"
                  class="rounded-md border border-slate-700 bg-slate-950 px-2 py-1 font-mono text-xs text-slate-300"
                >{{ citation }}</bdi>
              </div>
            </section>
          </div>
          <div v-else class="grid min-h-[420px] place-items-center text-center text-slate-500">
            <div><h1 class="text-xl font-bold text-slate-300">المكتبة فارغة</h1><p class="mt-2">لا توجد Knowledge Units مؤهلة للعرض.</p></div>
          </div>
        </main>

        <aside class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/50 p-4" aria-label="السياق">
          <div class="flex gap-1 rounded-lg bg-slate-950 p-1 text-xs">
            <button v-for="item in lenses" :key="item" type="button" class="focus-ring flex-1 rounded px-2 py-2" :class="lens === item ? 'bg-slate-800 text-cyan-200' : 'text-slate-500'" @click="setLens(item)">
              {{ item === 'overview' ? 'نظرة' : item === 'sources' ? 'المصادر' : 'التاريخ' }}
            </button>
          </div>

          <div v-if="lens === 'overview'" class="mt-5 space-y-5 text-sm">
            <section>
              <h2 class="text-xs font-bold text-slate-500">سلطة المراجعة المرتبطة</h2>
              <bdi v-if="active?.revision?.authority_baseline_id" dir="ltr" class="mt-2 block break-all font-mono text-xs text-slate-300">{{ active.revision.authority_baseline_id }}</bdi>
              <p v-else class="mt-2 text-slate-500">لا توجد سلطة مرتبطة بهذه المراجعة.</p>
            </section>
            <section>
              <h2 class="text-xs font-bold text-slate-500">سلامة provenance</h2>
              <p class="mt-2 text-slate-300">مراجع غير محلولة: <bdi dir="ltr" class="font-mono">{{ context.unresolved_citation_count }}</bdi></p>
            </section>
            <section v-if="context.placements.length">
              <h2 class="text-xs font-bold text-slate-500">سبب الظهور البنيوي</h2>
              <p class="mt-2 leading-6 text-slate-300">الوحدة مرتبطة بمواضع Curriculum حقيقية؛ التفاصيل البنيوية تبقى في شجرة المكتبة.</p>
            </section>
          </div>

          <div v-else-if="lens === 'sources'" class="mt-5 space-y-3">
            <article v-for="source in context.sources" :key="source.id" class="rounded-lg border border-slate-800 p-3">
              <h2 class="text-sm font-bold">{{ source.title }}</h2>
              <p class="mt-1 text-xs text-slate-500">{{ source.authority_class }}</p>
              <bdi dir="ltr" class="mt-2 block font-mono text-[11px] text-emerald-300">{{ source.review_status }}</bdi>
            </article>
            <p v-if="!context.sources.length" class="text-sm leading-7 text-slate-500">لا توجد Source Claims محلولة للمراجعة الحالية.</p>
          </div>

          <ol v-else class="mt-5 space-y-3">
            <li v-for="revision in active?.revisions ?? []" :key="revision.id">
              <Link
                :href="`/knowledge?object=${encodeURIComponent(active?.id ?? '')}&revision=${encodeURIComponent(revision.id)}`"
                class="focus-ring block rounded-lg border border-slate-800 p-3 hover:border-slate-600"
              >
                <div class="flex justify-between gap-3 text-xs">
                  <bdi dir="ltr" class="font-mono">revision {{ revision.revision }}</bdi>
                  <bdi dir="ltr" class="font-mono text-slate-500">{{ revision.state }}</bdi>
                </div>
              </Link>
            </li>
          </ol>
        </aside>
      </div>

      <details class="mt-4 rounded-xl border border-slate-800 bg-slate-900/30 px-4 py-3">
        <summary class="cursor-pointer text-sm font-bold text-slate-400">مساحة العمل المؤقتة — مغلقة افتراضيًا</summary>
        <p class="mt-3 text-sm leading-7 text-slate-500">تُفتح هنا لاحقًا مهام المقارنة أو فحص الفرق دون تحويلها إلى لوحة دائمة.</p>
      </details>
    </div>
  </div>
</template>

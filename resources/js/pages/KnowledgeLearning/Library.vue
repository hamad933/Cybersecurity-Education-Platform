<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import KnowledgeTabs from './components/KnowledgeTabs.vue';

type CatalogItem = {
  id: string;
  title_ar: string;
  title_en: string;
  latest_revision: number | null;
  latest_state: string | null;
};
type EditorBlock = { type: string; body: string };
type Revision = {
  id: string;
  revision: number;
  state: string;
  lock_version: number;
  blocks: EditorBlock[];
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
type EditorSnapshot = { blocks: EditorBlock[]; citations: string[] };
type RecoveryRecord = {
  revision_id: string;
  lock_version: number;
  saved_at: string;
  snapshot: EditorSnapshot;
};
type InertiaRevisionPayload = { props?: { active?: ActiveUnit | null } };

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
const setLens = (value: Lens) => {
  lens.value = value;
};
const blockTypes = [
  'heading',
  'paragraph',
  'callout',
  'rules',
  'boundaries',
  'code',
  'request',
  'response',
  'log',
];
const technicalTypes = new Set(['code', 'request', 'response', 'log']);

const form = useForm({
  lock_version: props.active?.revision?.lock_version ?? 1,
  blocks: props.active?.revision?.blocks.map((block) => ({ ...block })) ?? [],
  citations: props.active?.revision?.citations.slice() ?? [],
});

const cloneSnapshot = (snapshot: EditorSnapshot): EditorSnapshot => ({
  blocks: snapshot.blocks.map((block) => ({ ...block })),
  citations: snapshot.citations.slice(),
});
const currentSnapshot = (): EditorSnapshot => ({
  blocks: form.blocks.map((block) => ({ ...block })),
  citations: form.citations.slice(),
});
const snapshotsEqual = (left: EditorSnapshot, right: EditorSnapshot) =>
  JSON.stringify(left) === JSON.stringify(right);

const revisionKey = computed(() => props.active?.revision?.id ?? 'none');
const historicalRevisions = computed(() =>
  (props.active?.revisions ?? []).filter((revision) => revision.id !== props.active?.revision?.id),
);
const displayedBlocks = computed(() =>
  props.active?.revision?.editable ? form.blocks : (props.active?.revision?.blocks ?? []),
);
const canAutosave = computed(
  () =>
    Boolean(props.active?.revision?.editable) &&
    form.blocks.length > 0 &&
    form.blocks.every((block) => block.body.trim().length > 0),
);

const undoStack = ref<EditorSnapshot[]>([]);
const redoStack = ref<EditorSnapshot[]>([]);
const recoveryCandidate = ref<RecoveryRecord | null>(null);
const recoverySavedAt = ref<string | null>(null);
const autosaveState = ref<'idle' | 'pending' | 'saving' | 'saved' | 'error'>('idle');
let lastSnapshot = currentSnapshot();
let suppressHistory = false;
let historyTimer: ReturnType<typeof setTimeout> | undefined;
let autosaveTimer: ReturnType<typeof setTimeout> | undefined;

const recoveryKey = () =>
  props.active?.revision ? `cep:knowledge-editor:${props.active.revision.id}` : null;
const removeRecovery = () => {
  const key = recoveryKey();
  if (key && typeof window !== 'undefined') window.localStorage.removeItem(key);
  recoveryCandidate.value = null;
  recoverySavedAt.value = null;
};
const persistRecovery = (snapshot: EditorSnapshot) => {
  const revision = props.active?.revision;
  const key = recoveryKey();
  if (!revision?.editable || !key || typeof window === 'undefined') return;

  const savedAt = new Date().toISOString();
  const record: RecoveryRecord = {
    revision_id: revision.id,
    lock_version: form.lock_version,
    saved_at: savedAt,
    snapshot: cloneSnapshot(snapshot),
  };
  window.localStorage.setItem(key, JSON.stringify(record));
  recoverySavedAt.value = savedAt;
};
const loadRecovery = () => {
  recoveryCandidate.value = null;
  recoverySavedAt.value = null;
  const revision = props.active?.revision;
  const key = recoveryKey();
  if (!revision?.editable || !key || typeof window === 'undefined') return;

  const raw = window.localStorage.getItem(key);
  if (!raw) return;

  try {
    const record = JSON.parse(raw) as RecoveryRecord;
    if (
      record.revision_id === revision.id &&
      record.lock_version === revision.lock_version &&
      !snapshotsEqual(record.snapshot, currentSnapshot())
    ) {
      recoveryCandidate.value = record;
      recoverySavedAt.value = record.saved_at;
      return;
    }
    window.localStorage.removeItem(key);
  } catch {
    window.localStorage.removeItem(key);
  }
};

const applySnapshot = (snapshot: EditorSnapshot) => {
  suppressHistory = true;
  form.blocks = snapshot.blocks.map((block) => ({ ...block }));
  form.citations = snapshot.citations.slice();
  lastSnapshot = cloneSnapshot(snapshot);
  void nextTick(() => {
    suppressHistory = false;
  });
};
const commitHistoryCheckpoint = () => {
  if (suppressHistory) return;
  const current = currentSnapshot();
  if (snapshotsEqual(current, lastSnapshot)) return;
  undoStack.value.push(cloneSnapshot(lastSnapshot));
  if (undoStack.value.length > 50) undoStack.value.shift();
  redoStack.value = [];
  lastSnapshot = cloneSnapshot(current);
  persistRecovery(current);
};
const undo = () => {
  commitHistoryCheckpoint();
  const previous = undoStack.value.pop();
  if (!previous) return;
  redoStack.value.push(currentSnapshot());
  applySnapshot(previous);
  persistRecovery(previous);
};
const redo = () => {
  const next = redoStack.value.pop();
  if (!next) return;
  undoStack.value.push(currentSnapshot());
  applySnapshot(next);
  persistRecovery(next);
};
const recoverDraft = () => {
  if (!recoveryCandidate.value) return;
  undoStack.value.push(currentSnapshot());
  applySnapshot(recoveryCandidate.value.snapshot);
  recoveryCandidate.value = null;
  autosaveState.value = 'pending';
};
const discardRecovery = () => {
  removeRecovery();
};

const revisionUrl = () =>
  props.active?.revision ? `/knowledge/library/revisions/${props.active.revision.id}` : null;
const submitRevision = (mode: 'manual' | 'auto') => {
  const url = revisionUrl();
  if (!url || !props.active?.revision?.editable || form.processing) return;
  if (mode === 'auto' && !canAutosave.value) return;

  if (mode === 'auto') autosaveState.value = 'saving';
  form.patch(url, {
    preserveScroll: true,
    onSuccess: () => {
      form.lock_version = props.active?.revision?.lock_version ?? form.lock_version;
      lastSnapshot = currentSnapshot();
      removeRecovery();
      autosaveState.value = 'saved';
    },
    onError: () => {
      if (mode === 'auto') autosaveState.value = 'error';
    },
  });
};
const save = () => submitRevision('manual');

watch(
  () => JSON.stringify({ blocks: form.blocks, citations: form.citations }),
  () => {
    if (suppressHistory || !props.active?.revision?.editable) return;
    if (historyTimer) clearTimeout(historyTimer);
    if (autosaveTimer) clearTimeout(autosaveTimer);
    autosaveState.value = 'pending';
    historyTimer = setTimeout(commitHistoryCheckpoint, 450);
    autosaveTimer = setTimeout(() => submitRevision('auto'), 1800);
  },
);
watch(revisionKey, () => {
  if (historyTimer) clearTimeout(historyTimer);
  if (autosaveTimer) clearTimeout(autosaveTimer);
  suppressHistory = true;
  form.lock_version = props.active?.revision?.lock_version ?? 1;
  form.blocks = props.active?.revision?.blocks.map((block) => ({ ...block })) ?? [];
  form.citations = props.active?.revision?.citations.slice() ?? [];
  form.clearErrors();
  undoStack.value = [];
  redoStack.value = [];
  lastSnapshot = currentSnapshot();
  autosaveState.value = 'idle';
  void nextTick(() => {
    suppressHistory = false;
    loadRecovery();
  });
});
void nextTick(loadRecovery);
onBeforeUnmount(() => {
  if (historyTimer) clearTimeout(historyTimer);
  if (autosaveTimer) clearTimeout(autosaveTimer);
});

const restore = () => {
  if (!props.active?.revision || props.active.revision.state !== 'published') return;
  router.post(
    `/knowledge/library/revisions/${props.active.revision.id}/restore`,
    {},
    { preserveScroll: true },
  );
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
const indentBlock = (index: number) => {
  const block = form.blocks[index];
  if (!block) return;
  const lines = block.body.split('\n');
  const minimumIndent = Math.min(
    ...lines.filter((line) => line.length > 0).map((line) => line.match(/^ */)?.[0].length ?? 0),
    6,
  );
  if (Number.isFinite(minimumIndent) && minimumIndent >= 6) return;
  block.body = lines.map((line) => (line.length ? `  ${line}` : line)).join('\n');
};
const outdentBlock = (index: number) => {
  const block = form.blocks[index];
  if (!block) return;
  block.body = block.body
    .split('\n')
    .map((line) => line.replace(/^ {1,2}/, ''))
    .join('\n');
};
const replaceSelection = (index: number, before: string, after = before, fallback = '') => {
  const block = form.blocks[index];
  const input = document.getElementById(`knowledge-block-${index}`) as HTMLTextAreaElement | null;
  if (!block || !input) return;
  const start = input.selectionStart;
  const end = input.selectionEnd;
  const selected = block.body.slice(start, end) || fallback;
  block.body = `${block.body.slice(0, start)}${before}${selected}${after}${block.body.slice(end)}`;
  void nextTick(() => {
    const cursorStart = start + before.length;
    input.focus();
    input.setSelectionRange(cursorStart, cursorStart + selected.length);
  });
};
const insertLink = (index: number) => {
  if (typeof window === 'undefined') return;
  const href = window.prompt('أدخل رابط HTTPS المرجعي:', 'https://');
  if (!href) return;
  replaceSelection(index, '[', `](${href.trim()})`, 'نص الرابط');
};
const insertReference = (index: number) => {
  if (typeof window === 'undefined') return;
  const reference = window.prompt('أدخل معرّف المرجع أو الاستشهاد:');
  const normalized = reference?.trim();
  if (!normalized) return;
  if (!form.citations.includes(normalized)) form.citations.push(normalized);
  replaceSelection(index, '', '', `[@${normalized}]`);
};
const removeCitation = (citation: string) => {
  form.citations = form.citations.filter((item) => item !== citation);
};

const compareRevisionId = ref('');
const compareRevision = ref<Revision | null>(null);
const compareLoading = ref(false);
const compareError = ref('');
const compareOpen = ref(false);
const comparisonRows = computed(() => {
  const right = compareRevision.value?.blocks ?? [];
  const count = Math.max(displayedBlocks.value.length, right.length);
  return Array.from({ length: count }, (_, index) => ({
    current: displayedBlocks.value[index] ?? null,
    compared: right[index] ?? null,
  }));
});
const loadComparison = async () => {
  if (!props.active || !compareRevisionId.value || typeof window === 'undefined') return;
  compareLoading.value = true;
  compareError.value = '';
  try {
    const url = `/knowledge?object=${encodeURIComponent(props.active.id)}&revision=${encodeURIComponent(compareRevisionId.value)}`;
    const response = await window.fetch(url, {
      credentials: 'same-origin',
      headers: {
        Accept: 'text/html, application/xhtml+xml',
        'X-Inertia': 'true',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const payload = (await response.json()) as InertiaRevisionPayload;
    const revision = payload.props?.active?.revision;
    if (!revision || revision.id !== compareRevisionId.value) {
      throw new Error('Revision payload unavailable');
    }
    compareRevision.value = revision;
    compareOpen.value = true;
  } catch {
    compareRevision.value = null;
    compareOpen.value = false;
    compareError.value = 'تعذّر تحميل المراجعة للمقارنة دون تغيير السجل القانوني.';
  } finally {
    compareLoading.value = false;
  }
};
</script>

<template>
  <Head title="المعرفة والتعلّم — المكتبة" />
  <div dir="rtl" class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto max-w-[1600px] px-4 py-5 sm:px-6">
      <header class="border-b border-slate-800 pb-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <KnowledgeTabs active="library" :object-id="active?.id" />
          <div class="flex flex-wrap items-center gap-2">
            <template v-if="active?.revision?.editable">
              <button
                type="button"
                class="focus-ring rounded-lg border border-slate-700 px-3 py-2 text-xs font-bold text-slate-300 disabled:opacity-40"
                :disabled="!undoStack.length || form.processing"
                @click="undo"
              >
                تراجع
              </button>
              <button
                type="button"
                class="focus-ring rounded-lg border border-slate-700 px-3 py-2 text-xs font-bold text-slate-300 disabled:opacity-40"
                :disabled="!redoStack.length || form.processing"
                @click="redo"
              >
                إعادة
              </button>
              <span class="px-1 text-[11px] text-slate-500" role="status">
                {{
                  autosaveState === 'saving'
                    ? 'حفظ تلقائي…'
                    : autosaveState === 'saved'
                      ? 'محفوظ تلقائيًا'
                      : autosaveState === 'error'
                        ? 'تعذّر الحفظ التلقائي — نسخة الاسترداد محفوظة محليًا'
                        : autosaveState === 'pending'
                          ? 'تغييرات قيد الحفظ'
                          : 'المسودة متزامنة'
                }}
              </span>
              <button
                type="submit"
                form="knowledge-editor"
                class="focus-ring rounded-lg bg-cyan-400 px-4 py-2 text-sm font-bold text-slate-950 disabled:opacity-50"
                :disabled="form.processing"
              >
                حفظ / تطبيق
              </button>
            </template>
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

      <section
        v-if="recoveryCandidate"
        class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-700/70 bg-amber-950/30 px-4 py-3"
        role="status"
      >
        <div>
          <p class="text-sm font-bold text-amber-100">توجد نسخة استرداد محلية أحدث لهذه المسودة.</p>
          <bdi dir="ltr" class="mt-1 block font-mono text-[11px] text-amber-300">
            {{ recoveryCandidate.saved_at }}
          </bdi>
        </div>
        <div class="flex gap-2">
          <button
            type="button"
            class="focus-ring rounded-lg bg-amber-300 px-3 py-2 text-xs font-bold text-slate-950"
            @click="recoverDraft"
          >
            استرداد
          </button>
          <button
            type="button"
            class="focus-ring rounded-lg border border-amber-800 px-3 py-2 text-xs text-amber-100"
            @click="discardRecovery"
          >
            تجاهل
          </button>
        </div>
      </section>

      <div class="mt-4 grid min-h-[720px] gap-4 xl:grid-cols-[260px_minmax(0,1fr)_280px]">
        <aside
          class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="بنية المكتبة"
        >
          <h2 class="text-xs font-bold tracking-wide text-slate-400">بنية المكتبة</h2>
          <div v-if="structure.length" class="mt-4 space-y-5">
            <section v-for="group in structure" :key="group.capability_id ?? 'unplaced'">
              <bdi v-if="group.capability_id" dir="ltr" class="font-mono text-xs text-cyan-300">
                {{ group.capability_id }}
              </bdi>
              <p v-else class="text-xs text-amber-300">غير موضوع في Capability حاليًا</p>
              <ul class="mt-2 space-y-1">
                <li v-for="item in group.items" :key="item.id">
                  <Link
                    :href="`/knowledge?object=${encodeURIComponent(item.id)}`"
                    class="focus-ring block rounded-lg px-3 py-2 text-sm"
                    :class="
                      item.id === active?.id
                        ? 'bg-cyan-400/10 text-cyan-100'
                        : 'text-slate-300 hover:bg-slate-800'
                    "
                  >
                    {{ item.title_ar }}
                  </Link>
                </li>
              </ul>
            </section>
          </div>
          <p v-else class="mt-4 text-sm leading-7 text-slate-500">
            لا توجد وحدات معرفة محفوظة في قاعدة البيانات.
          </p>
        </aside>

        <main class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/35 p-5 sm:p-7">
          <div v-if="active" class="min-w-0">
            <div
              class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-800 pb-5"
            >
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
                <bdi dir="ltr" class="block font-mono text-slate-200">
                  revision {{ active.revision.revision }}
                </bdi>
                <bdi dir="ltr" class="mt-1 block font-mono text-emerald-300">
                  {{ active.revision.state }}
                </bdi>
              </div>
            </div>

            <form
              v-if="active.revision?.editable"
              id="knowledge-editor"
              class="mt-6 space-y-4"
              @submit.prevent="save"
            >
              <article
                v-for="(block, index) in form.blocks"
                :key="`${revisionKey}:${index}`"
                class="rounded-xl border border-slate-800 bg-slate-950/60 p-4"
              >
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <div class="flex flex-wrap items-center gap-2">
                    <select
                      v-model="block.type"
                      class="form-input focus-ring max-w-44 text-sm"
                      aria-label="نوع الكتلة"
                    >
                      <option v-for="type in blockTypes" :key="type" :value="type">
                        {{ type }}
                      </option>
                    </select>
                    <div class="flex flex-wrap gap-1" aria-label="تنسيق الكتلة">
                      <button
                        type="button"
                        class="focus-ring rounded border border-slate-700 px-2 py-1 text-xs font-bold"
                        title="عريض"
                        @click="replaceSelection(index, '**', '**', 'نص')"
                      >
                        B
                      </button>
                      <button
                        type="button"
                        class="focus-ring rounded border border-slate-700 px-2 py-1 text-xs italic"
                        title="مائل"
                        @click="replaceSelection(index, '_', '_', 'نص')"
                      >
                        I
                      </button>
                      <button
                        type="button"
                        class="focus-ring rounded border border-slate-700 px-2 py-1 font-mono text-xs"
                        title="Inline code"
                        @click="replaceSelection(index, '`', '`', 'code')"
                      >
                        &lt;/&gt;
                      </button>
                      <button
                        type="button"
                        class="focus-ring rounded border border-slate-700 px-2 py-1 text-xs"
                        @click="insertLink(index)"
                      >
                        رابط
                      </button>
                      <button
                        type="button"
                        class="focus-ring rounded border border-slate-700 px-2 py-1 text-xs"
                        @click="insertReference(index)"
                      >
                        مرجع
                      </button>
                    </div>
                  </div>
                  <div class="flex flex-wrap gap-1">
                    <button
                      type="button"
                      class="focus-ring rounded border border-slate-700 px-2 py-1 text-xs"
                      title="تعشيق داخل المستوى التالي"
                      @click="indentBlock(index)"
                    >
                      تعشيق ←
                    </button>
                    <button
                      type="button"
                      class="focus-ring rounded border border-slate-700 px-2 py-1 text-xs"
                      title="إلغاء مستوى تعشيق"
                      @click="outdentBlock(index)"
                    >
                      → إلغاء
                    </button>
                    <button
                      type="button"
                      class="focus-ring rounded border border-slate-700 px-2 py-1 text-xs"
                      @click="moveBlock(index, -1)"
                    >
                      ↑
                    </button>
                    <button
                      type="button"
                      class="focus-ring rounded border border-slate-700 px-2 py-1 text-xs"
                      @click="moveBlock(index, 1)"
                    >
                      ↓
                    </button>
                    <button
                      type="button"
                      class="focus-ring rounded border border-rose-900 px-2 py-1 text-xs text-rose-300"
                      @click="removeBlock(index)"
                    >
                      حذف
                    </button>
                  </div>
                </div>
                <textarea
                  :id="`knowledge-block-${index}`"
                  v-model="block.body"
                  required
                  maxlength="4000"
                  class="form-input focus-ring mt-3 min-h-32 leading-7"
                  :dir="technicalTypes.has(block.type) ? 'ltr' : 'rtl'"
                />
              </article>

              <div class="flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  class="focus-ring rounded-lg border border-dashed border-slate-600 px-4 py-2 text-sm text-slate-300"
                  @click="addBlock"
                >
                  إضافة كتلة
                </button>
                <span v-if="recoverySavedAt" class="text-[11px] text-slate-600">
                  آخر نسخة استرداد: <bdi dir="ltr">{{ recoverySavedAt }}</bdi>
                </span>
              </div>

              <section class="rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                <h2 class="text-xs font-bold text-slate-400">مراجع المسودة</h2>
                <div v-if="form.citations.length" class="mt-3 flex flex-wrap gap-2">
                  <span
                    v-for="citation in form.citations"
                    :key="citation"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-700 px-2 py-1"
                  >
                    <bdi dir="ltr" class="font-mono text-xs text-slate-300">{{ citation }}</bdi>
                    <button
                      type="button"
                      class="focus-ring text-xs text-rose-300"
                      :aria-label="`حذف المرجع ${citation}`"
                      @click="removeCitation(citation)"
                    >
                      ×
                    </button>
                  </span>
                </div>
                <p v-else class="mt-2 text-xs text-slate-600">
                  أضف مرجعًا من شريط أي كتلة لإبقائه ضمن بيانات المراجعة القانونية.
                </p>
              </section>

              <p v-if="page.props.errors?.revision" role="alert" class="text-sm text-rose-300">
                {{ page.props.errors.revision }}
              </p>
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
                  class="mt-3 overflow-x-auto text-left font-mono text-sm leading-6 whitespace-pre-wrap text-slate-200"
                  >{{ block.body }}</pre>
                <p v-else class="mt-3 leading-8 whitespace-pre-wrap text-slate-200">
                  {{ block.body }}
                </p>
              </article>
            </div>
            <div
              v-else
              class="mt-10 rounded-xl border border-dashed border-slate-700 p-8 text-center"
            >
              <h2 class="font-bold">لا توجد مراجعة محتوى لهذه الوحدة بعد.</h2>
              <p class="mt-2 text-sm text-slate-500">
                يعرض النظام الحالة الفعلية ولا ينشئ محتوى افتراضيًا.
              </p>
            </div>

            <section
              v-if="active.revision?.citations.length && !active.revision.editable"
              class="mt-7 border-t border-slate-800 pt-5"
            >
              <h2 class="text-sm font-bold">مراجع المحتوى</h2>
              <div class="mt-3 flex flex-wrap gap-2">
                <bdi
                  v-for="citation in active.revision.citations"
                  :key="citation"
                  dir="ltr"
                  class="rounded-md border border-slate-700 bg-slate-950 px-2 py-1 font-mono text-xs text-slate-300"
                >
                  {{ citation }}
                </bdi>
              </div>
            </section>
          </div>
          <div v-else class="grid min-h-[420px] place-items-center text-center text-slate-500">
            <div>
              <h1 class="text-xl font-bold text-slate-300">المكتبة فارغة</h1>
              <p class="mt-2">لا توجد Knowledge Units مؤهلة للعرض.</p>
            </div>
          </div>
        </main>

        <aside
          class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="السياق"
        >
          <div class="flex gap-1 rounded-lg bg-slate-950 p-1 text-xs">
            <button
              v-for="item in lenses"
              :key="item"
              type="button"
              class="focus-ring flex-1 rounded px-2 py-2"
              :class="lens === item ? 'bg-slate-800 text-cyan-200' : 'text-slate-500'"
              @click="setLens(item)"
            >
              {{ item === 'overview' ? 'نظرة' : item === 'sources' ? 'المصادر' : 'التاريخ' }}
            </button>
          </div>

          <div v-if="lens === 'overview'" class="mt-5 space-y-5 text-sm">
            <section>
              <h2 class="text-xs font-bold text-slate-500">سلطة المراجعة المرتبطة</h2>
              <bdi
                v-if="active?.revision?.authority_baseline_id"
                dir="ltr"
                class="mt-2 block font-mono text-xs break-all text-slate-300"
              >
                {{ active.revision.authority_baseline_id }}
              </bdi>
              <p v-else class="mt-2 text-slate-500">لا توجد سلطة مرتبطة بهذه المراجعة.</p>
            </section>
            <section>
              <h2 class="text-xs font-bold text-slate-500">سلامة provenance</h2>
              <p class="mt-2 text-slate-300">
                مراجع غير محلولة:
                <bdi dir="ltr" class="font-mono">{{ context.unresolved_citation_count }}</bdi>
              </p>
            </section>
            <section v-if="context.placements.length">
              <h2 class="text-xs font-bold text-slate-500">سبب الظهور البنيوي</h2>
              <p class="mt-2 leading-6 text-slate-300">
                الوحدة مرتبطة بمواضع Curriculum حقيقية؛ التفاصيل البنيوية تبقى في شجرة المكتبة.
              </p>
            </section>
          </div>

          <div v-else-if="lens === 'sources'" class="mt-5 space-y-3">
            <article
              v-for="source in context.sources"
              :key="source.id"
              class="rounded-lg border border-slate-800 p-3"
            >
              <h2 class="text-sm font-bold">{{ source.title }}</h2>
              <p class="mt-1 text-xs text-slate-500">{{ source.authority_class }}</p>
              <bdi dir="ltr" class="mt-2 block font-mono text-[11px] text-emerald-300">
                {{ source.review_status }}
              </bdi>
            </article>
            <p v-if="!context.sources.length" class="text-sm leading-7 text-slate-500">
              لا توجد Source Claims محلولة للمراجعة الحالية.
            </p>
          </div>

          <div v-else class="mt-5 space-y-4">
            <ol class="space-y-3">
              <li v-for="revision in historicalRevisions" :key="revision.id">
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
              <li v-if="!historicalRevisions.length" class="text-sm leading-7 text-slate-500">
                لا توجد مراجعات تاريخية أخرى لهذا الكائن.
              </li>
            </ol>

            <section v-if="historicalRevisions.length" class="border-t border-slate-800 pt-4">
              <h2 class="text-xs font-bold text-slate-500">مقارنة المراجعات</h2>
              <select
                v-model="compareRevisionId"
                class="form-input focus-ring mt-2 text-xs"
                aria-label="المراجعة المراد مقارنتها"
              >
                <option value="">اختر مراجعة تاريخية</option>
                <option
                  v-for="revision in historicalRevisions"
                  :key="revision.id"
                  :value="revision.id"
                >
                  revision {{ revision.revision }} — {{ revision.state }}
                </option>
              </select>
              <button
                type="button"
                class="focus-ring mt-2 w-full rounded-lg border border-cyan-800 px-3 py-2 text-xs font-bold text-cyan-200 disabled:opacity-40"
                :disabled="!compareRevisionId || compareLoading"
                @click="loadComparison"
              >
                {{ compareLoading ? 'تحميل المقارنة…' : 'فتح المقارنة' }}
              </button>
              <p v-if="compareError" role="alert" class="mt-2 text-xs leading-6 text-rose-300">
                {{ compareError }}
              </p>
            </section>
          </div>
        </aside>
      </div>

      <section
        v-if="compareOpen && active?.revision && compareRevision"
        class="mt-4 rounded-xl border border-slate-800 bg-slate-900/30 p-4"
        aria-label="مقارنة المراجعات"
      >
        <div
          class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3"
        >
          <div>
            <h2 class="font-bold">مقارنة مراجعتين دون تعديل السجل المنشور</h2>
            <p class="mt-1 text-xs text-slate-500">
              المقارنة للقراءة فقط؛ أي استعادة لمراجعة منشورة تستمر عبر إنشاء مسودة جديدة.
            </p>
          </div>
          <button
            type="button"
            class="focus-ring rounded-lg border border-slate-700 px-3 py-2 text-xs"
            @click="compareOpen = false"
          >
            إغلاق
          </button>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
          <div class="rounded-lg border border-cyan-900/70 p-3">
            <bdi dir="ltr" class="font-mono text-xs text-cyan-300">
              revision {{ active.revision.revision }} — {{ active.revision.state }}
            </bdi>
          </div>
          <div class="rounded-lg border border-indigo-900/70 p-3">
            <bdi dir="ltr" class="font-mono text-xs text-indigo-300">
              revision {{ compareRevision.revision }} — {{ compareRevision.state }}
            </bdi>
          </div>
        </div>

        <div class="mt-3 space-y-3">
          <div
            v-for="(row, index) in comparisonRows"
            :key="index"
            class="grid gap-3 md:grid-cols-2"
          >
            <article class="min-w-0 rounded-lg border border-slate-800 bg-slate-950/50 p-3">
              <bdi v-if="row.current" dir="ltr" class="font-mono text-[11px] text-cyan-300">
                {{ row.current.type }}
              </bdi>
              <p
                v-if="row.current"
                class="mt-2 text-sm leading-7 whitespace-pre-wrap text-slate-300"
              >
                {{ row.current.body }}
              </p>
              <p v-else class="text-xs text-slate-600">لا توجد كتلة مقابلة.</p>
            </article>
            <article class="min-w-0 rounded-lg border border-slate-800 bg-slate-950/50 p-3">
              <bdi v-if="row.compared" dir="ltr" class="font-mono text-[11px] text-indigo-300">
                {{ row.compared.type }}
              </bdi>
              <p
                v-if="row.compared"
                class="mt-2 text-sm leading-7 whitespace-pre-wrap text-slate-300"
              >
                {{ row.compared.body }}
              </p>
              <p v-else class="text-xs text-slate-600">لا توجد كتلة مقابلة.</p>
            </article>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

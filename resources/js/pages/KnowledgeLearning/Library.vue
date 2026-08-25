<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import KnowledgeTabs from './components/KnowledgeTabs.vue';
import LibraryHierarchyTree from './components/library/LibraryHierarchyTree.vue';
import type { LibraryHierarchyProjection, LibraryDomainNode, LibraryCapabilityClusterNode, LibraryCapabilityNode, LibraryUnresolvedCapability, LibraryProjectionItem } from './components/library/libraryHierarchy';

type CatalogItem = {
  id: string;
  title_ar: string;
  title_en: string;
  latest_revision: number | null;
  latest_state: string | null;
};
type RevisionBlock = { type: string; body: string; depth?: number };
type EditorBlock = { type: string; body: string; depth: number };
type Revision = {
  id: string;
  revision: number;
  state: string;
  lock_version: number;
  blocks: RevisionBlock[];
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
type InlineToken = {
  kind: 'text' | 'strong' | 'emphasis' | 'code' | 'link';
  text: string;
  href?: string;
};

const props = defineProps<{
  catalog: CatalogItem[];
  structure: LibraryHierarchyProjection;
  active: ActiveUnit | null;
  context: {
    placements: Placement[];
    sources: Source[];
    unresolved_citation_count: number;
  };
}>();

const page = usePage<{ flash?: { status?: string }; errors?: Record<string, string> }>();
const lenses = ['overview', 'sources'] as const;
type Lens = (typeof lenses)[number];
const lens = ref<Lens>('overview');
const setLens = (value: Lens) => {
  lens.value = value;
};

const searchQuery = ref('');

const filterItems = (items: LibraryProjectionItem[], query: string) =>
  items.filter(
    (item) =>
      item.title_ar.toLowerCase().includes(query) ||
      item.title_en.toLowerCase().includes(query) ||
      item.canonical_ref.id.toLowerCase().includes(query),
  );

const filteredStructure = computed<LibraryHierarchyProjection>(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return props.structure;

  const domains = props.structure.domains
    .map((domain) => {
      const clusters = domain.clusters
        .map((cluster) => {
          const capabilities = cluster.capabilities
            .map((cap) => ({ ...cap, items: filterItems(cap.items, query) }))
            .filter((cap) => cap.items.length > 0);
          return { ...cluster, capabilities };
        })
        .filter((cluster) => cluster.capabilities.length > 0);
      return { ...domain, clusters };
    })
    .filter((domain) => domain.clusters.length > 0);

  const unresolved_capabilities = props.structure.unresolved_capabilities
    .map((cap) => ({ ...cap, items: filterItems(cap.items, query) }))
    .filter((cap) => cap.items.length > 0);

  const unplaced = filterItems(props.structure.unplaced, query);

  return { domains, unresolved_capabilities, unplaced };
});
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
const MAX_BLOCK_DEPTH = 3;

const structuralDepth = (block: RevisionBlock): number =>
  Number.isInteger(block.depth) ? (block.depth as number) : 0;
const normalizeBlock = (block: RevisionBlock): EditorBlock => ({
  type: block.type,
  body: block.body,
  depth: structuralDepth(block),
});
const normalizeBlocks = (blocks: RevisionBlock[]): EditorBlock[] => blocks.map(normalizeBlock);
const normalizeSnapshot = (snapshot: {
  blocks: RevisionBlock[];
  citations: string[];
}): EditorSnapshot => ({
  blocks: normalizeBlocks(snapshot.blocks),
  citations: snapshot.citations.slice(),
});

const form = useForm({
  lock_version: props.active?.revision?.lock_version ?? 1,
  blocks: normalizeBlocks(props.active?.revision?.blocks ?? []),
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

const isValidHierarchy = (blocks: EditorBlock[]): boolean => {
  if (!blocks.length || blocks[0]?.depth !== 0) return false;

  return blocks.every((block, index) => {
    if (!Number.isInteger(block.depth) || block.depth < 0 || block.depth > MAX_BLOCK_DEPTH) {
      return false;
    }
    if (index === 0) return block.depth === 0;

    const previous = blocks[index - 1];
    return Boolean(previous) && block.depth <= previous.depth + 1;
  });
};

const revisionKey = computed(() => props.active?.revision?.id ?? 'none');
const historicalRevisions = computed(() =>
  (props.active?.revisions ?? []).filter((revision) => revision.id !== props.active?.revision?.id),
);
const displayedBlocks = computed<EditorBlock[]>(() =>
  props.active?.revision?.editable
    ? form.blocks
    : normalizeBlocks(props.active?.revision?.blocks ?? []),
);
const canAutosave = computed(
  () =>
    Boolean(props.active?.revision?.editable) &&
    form.blocks.length > 0 &&
    form.blocks.every((block) => block.body.trim().length > 0) &&
    isValidHierarchy(form.blocks),
);

const totalWordCount = computed(() => {
  const text = displayedBlocks.value.map((b) => b.body).join(' ');
  const words = text.trim().split(/\s+/).filter(Boolean);
  return words.length;
});
const readingTimeMinutes = computed(() => {
  const words = totalWordCount.value;
  return Math.max(1, Math.ceil(words / 150));
});
const unitSummary = computed(() => {
  const firstParagraph = displayedBlocks.value.find(
    (b) => b.type === 'paragraph' || b.type === 'callout',
  );
  if (firstParagraph?.body) {
    const trimmed = firstParagraph.body.trim();
    return trimmed.length > 180 ? `${trimmed.slice(0, 180)}…` : trimmed;
  }
  return props.active?.title_ar ?? 'لا يوجد ملخص متاح للوحدة.';
});

const undoStack = ref<EditorSnapshot[]>([]);
const redoStack = ref<EditorSnapshot[]>([]);
const recoveryCandidate = ref<RecoveryRecord | null>(null);
const recoverySavedAt = ref<string | null>(null);
const autosaveState = ref<'idle' | 'pending' | 'saving' | 'saved' | 'error'>('idle');
const linkValidationError = ref('');
const copiedBlockIndex = ref<number | null>(null);
const copyBlockText = (text: string, index: number) => {
  if (typeof navigator !== 'undefined' && navigator.clipboard) {
    void navigator.clipboard.writeText(text);
    copiedBlockIndex.value = index;
    setTimeout(() => {
      if (copiedBlockIndex.value === index) copiedBlockIndex.value = null;
    }, 2000);
  }
};
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
    const normalizedSnapshot = normalizeSnapshot(record.snapshot);
    if (
      record.revision_id === revision.id &&
      record.lock_version === revision.lock_version &&
      isValidHierarchy(normalizedSnapshot.blocks) &&
      !snapshotsEqual(normalizedSnapshot, currentSnapshot())
    ) {
      recoveryCandidate.value = { ...record, snapshot: normalizedSnapshot };
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
  if (!isValidHierarchy(form.blocks)) {
    autosaveState.value = 'error';
    return;
  }

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
  form.blocks = normalizeBlocks(props.active?.revision?.blocks ?? []);
  form.citations = props.active?.revision?.citations.slice() ?? [];
  form.clearErrors();
  undoStack.value = [];
  redoStack.value = [];
  lastSnapshot = currentSnapshot();
  autosaveState.value = 'idle';
  linkValidationError.value = '';
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

const subtreeEnd = (blocks: EditorBlock[], index: number): number => {
  const block = blocks[index];
  if (!block) return index;

  let end = index + 1;
  while (end < blocks.length && (blocks[end]?.depth ?? 0) > block.depth) end += 1;

  return end;
};
const previousSiblingIndex = (blocks: EditorBlock[], index: number): number | null => {
  const block = blocks[index];
  if (!block) return null;

  for (let candidate = index - 1; candidate >= 0; candidate -= 1) {
    const depth = blocks[candidate]?.depth ?? 0;
    if (depth < block.depth) return null;
    if (depth === block.depth) return candidate;
  }

  return null;
};
const nextSiblingIndex = (blocks: EditorBlock[], index: number): number | null => {
  const block = blocks[index];
  if (!block) return null;

  const candidate = subtreeEnd(blocks, index);
  if (candidate < blocks.length && blocks[candidate]?.depth === block.depth) return candidate;

  return null;
};
const parentIndex = (blocks: EditorBlock[], index: number): number | null => {
  const block = blocks[index];
  if (!block || block.depth === 0) return null;

  for (let candidate = index - 1; candidate >= 0; candidate -= 1) {
    const depth = blocks[candidate]?.depth ?? 0;
    if (depth < block.depth) return depth === block.depth - 1 ? candidate : null;
  }

  return null;
};
const canIndentBlock = (index: number) => {
  const block = form.blocks[index];
  if (!block || previousSiblingIndex(form.blocks, index) === null) return false;

  const end = subtreeEnd(form.blocks, index);
  return form.blocks.slice(index, end).every((item) => item.depth < MAX_BLOCK_DEPTH);
};
const canOutdentBlock = (index: number) => (form.blocks[index]?.depth ?? 0) > 0;
const canMoveBlock = (index: number, delta: number) =>
  delta < 0
    ? previousSiblingIndex(form.blocks, index) !== null
    : nextSiblingIndex(form.blocks, index) !== null;

const addBlock = () => form.blocks.push({ type: 'paragraph', body: '', depth: 0 });
const removeBlock = (index: number) => {
  const end = subtreeEnd(form.blocks, index);
  const count = end - index;
  if (count < 1 || form.blocks.length - count < 1) return;
  form.blocks.splice(index, count);
};
const moveBlock = (index: number, delta: number) => {
  if (delta < 0) {
    const previous = previousSiblingIndex(form.blocks, index);
    if (previous === null) return;
    const end = subtreeEnd(form.blocks, index);
    const segment = form.blocks.splice(index, end - index);
    form.blocks.splice(previous, 0, ...segment);
    return;
  }

  const next = nextSiblingIndex(form.blocks, index);
  if (next === null) return;
  const end = subtreeEnd(form.blocks, index);
  const nextEnd = subtreeEnd(form.blocks, next);
  const nextLength = nextEnd - next;
  const segment = form.blocks.splice(index, end - index);
  form.blocks.splice(index + nextLength, 0, ...segment);
};
const indentBlock = (index: number) => {
  if (!canIndentBlock(index)) return;
  const end = subtreeEnd(form.blocks, index);
  for (let cursor = index; cursor < end; cursor += 1) {
    const block = form.blocks[cursor];
    if (block) block.depth += 1;
  }
};
const outdentBlock = (index: number) => {
  const block = form.blocks[index];
  const parent = parentIndex(form.blocks, index);
  if (!block || block.depth === 0 || parent === null) return;

  const end = subtreeEnd(form.blocks, index);
  const parentEnd = subtreeEnd(form.blocks, parent);
  const segment = form.blocks.slice(index, end).map((item) => ({ ...item, depth: item.depth - 1 }));
  const count = end - index;

  form.blocks.splice(index, count);
  form.blocks.splice(parentEnd - count, 0, ...segment);
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
const safeHttpsUrl = (candidate: string): string | null => {
  try {
    const parsed = new URL(candidate);
    return parsed.protocol === 'https:' ? parsed.toString() : null;
  } catch {
    return null;
  }
};
const insertLink = (index: number) => {
  if (typeof window === 'undefined') return;
  linkValidationError.value = '';
  const href = window.prompt('أدخل رابط HTTPS المرجعي:', 'https://');
  if (!href) return;

  const safeHref = safeHttpsUrl(href.trim());
  if (!safeHref) {
    linkValidationError.value = 'يُسمح فقط بروابط HTTPS صحيحة.';
    return;
  }

  replaceSelection(index, '[', `](${safeHref})`, 'نص الرابط');
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

const inlineTokens = (body: string): InlineToken[] => {
  // prettier-ignore
  const pattern = /(\*\*[^*\n]+\*\*|_[^_\n]+_|`[^`\n]+`|\[[^\]\n]+\]\(https:\/\/[^)\s]+\))/g;
  const tokens: InlineToken[] = [];
  let offset = 0;

  for (const match of body.matchAll(pattern)) {
    const index = match.index ?? 0;
    if (index > offset) tokens.push({ kind: 'text', text: body.slice(offset, index) });

    const raw = match[0];
    const link = raw.match(/^\[([^\]]+)\]\((https:\/\/[^)\s]+)\)$/);
    const href = link?.[2] ? safeHttpsUrl(link[2]) : null;
    if (link?.[1] && href) {
      tokens.push({ kind: 'link', text: link[1], href });
    } else if (raw.startsWith('**') && raw.endsWith('**')) {
      tokens.push({ kind: 'strong', text: raw.slice(2, -2) });
    } else if (raw.startsWith('_') && raw.endsWith('_')) {
      tokens.push({ kind: 'emphasis', text: raw.slice(1, -1) });
    } else if (raw.startsWith('`') && raw.endsWith('`')) {
      tokens.push({ kind: 'code', text: raw.slice(1, -1) });
    } else {
      tokens.push({ kind: 'text', text: raw });
    }

    offset = index + raw.length;
  }

  if (offset < body.length) tokens.push({ kind: 'text', text: body.slice(offset) });
  return tokens.length ? tokens : [{ kind: 'text', text: body }];
};

const shelfOpen = ref(false);
const shelfTab = ref<'compare' | 'diagnostics'>('compare');
const toggleShelf = () => {
  shelfOpen.value = !shelfOpen.value;
};

const compareRevisionId = ref('');
const compareRevision = ref<Revision | null>(null);
const compareLoading = ref(false);
const compareError = ref('');
const compareOpen = ref(false);
const comparisonRows = computed(() => {
  const right = normalizeBlocks(compareRevision.value?.blocks ?? []);
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
    // prettier-ignore
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
    shelfOpen.value = true;
    shelfTab.value = 'compare';
  } catch {
    compareRevision.value = null;
    compareOpen.value = false;
    compareError.value = 'تعذّر تحميل المراجعة للمقارنة دون تغيير السجل القانوني.';
  } finally {
    compareLoading.value = false;
  }
};
</script>

<!-- prettier-ignore -->
<template>
  <Head title="المعرفة والتعلّم — المكتبة" />
  <div dir="rtl" class="flex min-h-screen flex-col bg-slate-950 text-slate-100 antialiased">
    <!-- Top Bar: Navigation & Primary Actions -->
    <header class="border-b border-slate-800/80 bg-slate-950/90 px-4 py-3 sm:px-6">
      <div class="mx-auto flex max-w-[1720px] flex-wrap items-center justify-end gap-4">
        <div class="flex flex-wrap items-center gap-2">
          <template v-if="active?.revision?.editable">
            <button
              type="button"
              class="focus-ring rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:bg-slate-800 disabled:opacity-40"
              :disabled="!undoStack.length || form.processing"
              title="تراجع عن آخر تعديل"
              aria-label="تراجع عن آخر تعديل"
              @click="undo"
            >
              تراجع
            </button>
            <button
              type="button"
              class="focus-ring rounded-lg border border-slate-700 bg-slate-900/80 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:bg-slate-800 disabled:opacity-40"
              :disabled="!redoStack.length || form.processing"
              title="إعادة التعديل المتراجع عنه"
              aria-label="إعادة التعديل المتراجع عنه"
              @click="redo"
            >
              إعادة
            </button>
            <span
              class="flex items-center gap-1.5 px-2 text-xs"
              :class="
                autosaveState === 'error'
                  ? 'text-rose-400'
                  : autosaveState === 'saved'
                    ? 'text-emerald-400'
                    : 'text-slate-400'
              "
              role="status"
            >
              <span
                class="inline-block h-2 w-2 rounded-full"
                :class="
                  autosaveState === 'saving'
                    ? 'animate-pulse bg-cyan-400'
                    : autosaveState === 'saved'
                      ? 'bg-emerald-400'
                      : autosaveState === 'error'
                        ? 'bg-rose-400'
                        : autosaveState === 'pending'
                          ? 'bg-amber-400'
                          : 'bg-slate-600'
                "
              />
              {{
                autosaveState === 'saving'
                  ? 'حفظ تلقائي…'
                  : autosaveState === 'saved'
                    ? 'مسودة محفوظة تلقائيًا'
                    : autosaveState === 'error'
                      ? 'تعذّر الحفظ التلقائي'
                      : autosaveState === 'pending'
                        ? 'تغييرات قيد الحفظ…'
                        : 'المسودة متزامنة'
              }}
            </span>
            <button
              type="submit"
              form="knowledge-editor"
              class="focus-ring rounded-lg bg-cyan-400 px-4 py-1.5 text-xs font-bold text-slate-950 shadow-sm transition hover:bg-cyan-300 disabled:opacity-50"
              :disabled="form.processing"
              aria-label="حفظ وتطبيق التعديلات على المسودة"
            >
              حفظ / تطبيق
            </button>
          </template>
          <button
            v-else-if="active?.revision?.state === 'published'"
            type="button"
            class="focus-ring rounded-lg border border-cyan-500/80 bg-cyan-950/40 px-4 py-1.5 text-xs font-bold text-cyan-200 transition hover:bg-cyan-900/50"
            aria-label="إنشاء مسودة جديدة من هذه المراجعة المنشورة"
            @click="restore"
          >
            إنشاء مسودة جديدة
          </button>
        </div>
      </div>
    </header>

    <!-- Flash & Recovery Notifications -->
    <div class="mx-auto w-full max-w-[1720px] px-4 pt-3 sm:px-6">
      <p
        v-if="page.props.flash?.status"
        role="status"
        class="rounded-xl border border-emerald-700/60 bg-emerald-950/50 px-4 py-2.5 text-xs font-medium text-emerald-200"
      >
        {{ page.props.flash.status }}
      </p>

      <section
        v-if="recoveryCandidate"
        class="mt-2 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-600/60 bg-amber-950/40 px-4 py-2.5"
        role="status"
      >
        <div class="flex items-center gap-2">
          <span class="text-amber-400">⚠️</span>
          <div>
            <p class="text-xs font-bold text-amber-200">
              توجد نسخة استرداد محلية أحدث لهذه المسودة.
            </p>
            <bdi dir="ltr" class="font-mono text-[10px] text-amber-300/80">
              {{ recoveryCandidate.saved_at }}
            </bdi>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            type="button"
            class="focus-ring rounded-lg bg-amber-300 px-3 py-1 text-xs font-bold text-slate-950 hover:bg-amber-200"
            aria-label="استرداد المسودة المحلية المحفوظة"
            @click="recoverDraft"
          >
            استرداد
          </button>
          <button
            type="button"
            class="focus-ring rounded-lg border border-amber-700/80 px-3 py-1 text-xs text-amber-200 hover:bg-amber-900/40"
            aria-label="تجاهل المسودة المحلية المحفوظة"
            @click="discardRecovery"
          >
            تجاهل
          </button>
        </div>
      </section>
    </div>

    <!-- 3-Column Desktop Workspace Surface -->
    <!-- The outer grid uses dir="ltr" so Column 1 is visual LEFT, Column 2 is CENTER, Column 3 is visual RIGHT -->
    <div class="mx-auto w-full max-w-[1720px] flex-1 px-4 py-4 sm:px-6">
      <div
        dir="ltr"
        class="grid min-h-[740px] grid-cols-1 gap-4 xl:grid-cols-[280px_minmax(0,1fr)_300px]"
      >
        <!-- Visual LEFT: Structure / Hierarchy Tree -->
        <aside
          dir="rtl"
          class="flex min-w-0 flex-col rounded-xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-sm"
          aria-label="بنية المكتبة"
        >
          <!-- Search Filter -->
          <div class="relative">
            <input
              v-model="searchQuery"
              type="search"
              placeholder="البحث في المكتبة…"
              class="form-input focus-ring w-full rounded-lg border-slate-700/80 bg-slate-950/80 py-2 pr-3 pl-8 text-xs text-slate-200 placeholder:text-slate-500"
              aria-label="البحث في وحدات المعرفة"
            />
            <span
              class="pointer-events-none absolute top-1/2 left-2.5 -translate-y-1/2 text-slate-500"
            >
              🔍
            </span>
          </div>

          <!-- Hierarchy Tree -->
          <div class="mt-4 flex-1 space-y-4 overflow-y-auto pr-0.5">
            <LibraryHierarchyTree
              :projection="filteredStructure"
              :active-id="active?.id"
            />
          </div>

          <!-- Tree Footer: Library Info -->
          <div
            class="mt-4 flex items-center justify-between border-t border-slate-800/80 pt-3 text-xs text-slate-400"
          >
            <span class="flex items-center gap-1.5">
              <span>⚙️</span>
              <span>إدارة المكتبة</span>
            </span>
            <span class="font-mono text-[10px] text-slate-500">{{ catalog.length }} كائن</span>
          </div>
        </aside>

        <!-- Visual CENTER: Canonical Document Work Surface -->
        <main
          dir="rtl"
          class="flex min-w-0 flex-col rounded-xl border border-slate-800/80 bg-slate-900/30 p-5 shadow-sm sm:p-7"
          aria-label="وحدة المعرفة القانونية"
        >
          <div v-if="active" class="flex min-w-0 flex-1 flex-col">
            <!-- Center Grouped Gateways -->
            <div class="mb-5 border-b border-slate-800/80 pb-4">
              <KnowledgeTabs active="library" :object-id="active?.id" />
            </div>

            <!-- Document Meta Header -->
            <div class="border-b border-slate-800/80 pb-5">
              <!-- Breadcrumbs & Actions Row -->
              <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <nav
                  aria-label="مسار الوحدة"
                  class="flex items-center gap-1.5 font-mono text-slate-400"
                >
                  <bdi dir="ltr" class="text-cyan-400">
                    {{ context.placements[0]?.capability_id ?? 'Curriculum' }}
                  </bdi>
                  <span>&gt;</span>
                  <bdi dir="ltr" class="text-slate-300">{{ active.id }}</bdi>
                </nav>
                <div class="flex items-center gap-2 text-slate-400">
                  <button
                    type="button"
                    class="focus-ring rounded p-1 hover:bg-slate-800 hover:text-slate-200"
                    title="نسخ المعرف"
                    aria-label="نسخ معرّف الوحدة"
                    @click="copyBlockText(active.id, -1)"
                  >
                    🔗
                  </button>
                </div>
              </div>

              <!-- Main Title & Status Badge -->
              <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-black text-slate-100 sm:text-3xl">
                      {{ active.title_ar }}
                    </h1>
                    <span
                      v-if="active.revision"
                      class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                      :class="
                        active.revision.state === 'published'
                          ? 'border border-emerald-700/60 bg-emerald-950/80 text-emerald-300'
                          : 'border border-amber-700/60 bg-amber-950/80 text-amber-300'
                      "
                    >
                      {{ active.revision.state === 'published' ? 'منشور' : 'مسودة' }}
                    </span>
                  </div>
                  <p class="mt-1 font-mono text-xs text-slate-400">
                    <bdi dir="ltr">{{ active.title_en }}</bdi>
                  </p>
                </div>

                <!-- Revision Details Card -->
                <div
                  v-if="active.revision"
                  class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 text-left text-xs"
                >
                  <div class="flex items-center gap-2 font-mono">
                    <bdi dir="ltr" class="font-semibold text-cyan-300">
                      rev {{ active.revision.revision }}
                    </bdi>
                    <span class="text-slate-600">·</span>
                    <bdi dir="ltr" class="text-slate-400">v{{ active.revision.lock_version }}</bdi>
                  </div>
                  <bdi
                    v-if="active.revision.updated_at || active.revision.published_at"
                    dir="ltr"
                    class="mt-1 block font-mono text-[10px] text-slate-500"
                  >
                    {{ (active.revision.updated_at ?? active.revision.published_at)?.slice(0, 10) }}
                  </bdi>
                </div>
              </div>

              <!-- Metadata & Citation Badges Row -->
              <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                <span
                  v-if="active.revision?.authority_baseline_id"
                  class="inline-flex items-center gap-1 rounded-md border border-cyan-800/60 bg-cyan-950/30 px-2 py-1 font-mono text-[11px] text-cyan-200"
                >
                  <span>🏛️</span>
                  <bdi dir="ltr">{{ active.revision.authority_baseline_id }}</bdi>
                </span>
                <span
                  v-for="citation in active.revision?.citations ?? []"
                  :key="citation"
                  class="inline-flex items-center gap-1 rounded-md border border-slate-700/80 bg-slate-950/80 px-2 py-1 font-mono text-[11px] text-slate-300"
                >
                  <bdi dir="ltr">{{ citation }}</bdi>
                </span>
              </div>
            </div>

            <!-- Editor Document Surface (Document-First Editable Draft) -->
            <form
              v-if="active.revision?.editable"
              id="knowledge-editor"
              class="mt-6 flex-1 space-y-4"
              @submit.prevent="save"
            >
              <article
                v-for="(block, index) in form.blocks"
                :key="`${revisionKey}:${index}`"
                class="group relative rounded-xl transition-all duration-150"
                :class="[
                  block.type === 'callout'
                    ? 'rounded-xl border-r-4 border-cyan-500 bg-cyan-950/20 p-3.5 shadow-sm'
                    : block.type === 'rules'
                      ? 'rounded-xl border-r-4 border-indigo-500 bg-indigo-950/20 p-3.5 shadow-sm'
                      : block.type === 'boundaries'
                        ? 'rounded-xl border-r-4 border-amber-500 bg-amber-950/20 p-3.5 shadow-sm'
                        : technicalTypes.has(block.type)
                          ? 'overflow-hidden rounded-xl border border-slate-800/90 bg-slate-950/90 shadow-inner'
                          : block.type === 'heading'
                            ? 'border-b border-slate-800/80 pb-3 pt-1'
                            : 'rounded-xl border border-transparent p-2 hover:border-slate-800/70 focus-within:border-cyan-600/40 focus-within:bg-slate-950/40',
                ]"
                :style="{ marginInlineStart: `${block.depth * 1.25}rem` }"
              >
                <!-- Progressive Disclosure Contextual Toolbar (reveals on block hover / focus-within) -->
                <div
                  class="mb-2 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-800/90 bg-slate-900/95 px-3 py-1.5 text-xs shadow-md backdrop-blur transition-all duration-150 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100"
                  role="toolbar"
                  aria-label="شريط أدوات تحرير الكتلة"
                >
                  <div class="flex flex-wrap items-center gap-2">
                    <select
                      v-model="block.type"
                      class="form-input focus-ring rounded-md border-slate-700 bg-slate-950 py-0.5 pr-2 pl-6 text-xs font-semibold text-cyan-200"
                      aria-label="نوع الكتلة"
                    >
                      <option v-for="type in blockTypes" :key="type" :value="type">
                        {{ type }}
                      </option>
                    </select>

                    <bdi
                      v-if="block.depth > 0"
                      dir="ltr"
                      class="rounded bg-slate-950 px-1.5 py-0.5 font-mono text-[10px] text-cyan-400"
                    >
                      depth {{ block.depth }}
                    </bdi>

                    <!-- Text Formatting Shortcuts -->
                    <div
                      class="flex items-center gap-0.5 rounded border border-slate-800 bg-slate-950 p-0.5"
                      role="toolbar"
                      aria-label="أدوات تنسيق النص"
                    >
                      <button
                        type="button"
                        class="focus-ring rounded px-1.5 py-0.5 text-[11px] font-bold text-slate-300 hover:bg-slate-800 hover:text-white"
                        title="عريض"
                        aria-label="تنسيق عريض"
                        @click="replaceSelection(index, '**', '**', 'نص')"
                      >
                        B
                      </button>
                      <button
                        type="button"
                        class="focus-ring rounded px-1.5 py-0.5 text-[11px] italic text-slate-300 hover:bg-slate-800 hover:text-white"
                        title="مائل"
                        aria-label="تنسيق مائل"
                        @click="replaceSelection(index, '_', '_', 'نص')"
                      >
                        I
                      </button>
                      <button
                        type="button"
                        class="focus-ring rounded px-1.5 py-0.5 font-mono text-[11px] text-slate-300 hover:bg-slate-800 hover:text-white"
                        title="كود مضمن"
                        aria-label="تنسيق كود مضمن"
                        @click="replaceSelection(index, '`', '`', 'code')"
                      >
                        &lt;/&gt;
                      </button>
                      <button
                        type="button"
                        class="focus-ring rounded px-1.5 py-0.5 text-[11px] text-slate-300 hover:bg-slate-800 hover:text-white"
                        title="إدراج رابط مرجعي"
                        aria-label="إدراج رابط مرجعي"
                        @click="insertLink(index)"
                      >
                        رابط
                      </button>
                      <button
                        type="button"
                        class="focus-ring rounded px-1.5 py-0.5 text-[11px] text-slate-300 hover:bg-slate-800 hover:text-white"
                        title="إدراج استشهاد"
                        aria-label="إدراج استشهاد مرجعي"
                        @click="insertReference(index)"
                      >
                        مرجع
                      </button>
                    </div>
                  </div>

                  <!-- Hierarchy & Ordering Actions -->
                  <div class="flex items-center gap-1">
                    <button
                      type="button"
                      class="focus-ring rounded border border-slate-700 bg-slate-950 px-2 py-0.5 text-[11px] text-slate-300 transition hover:bg-slate-800 disabled:opacity-30"
                      title="تعشيق بنيوي داخل الشقيق السابق"
                      aria-label="تعشيق بنيوي داخل الشقيق السابق"
                      :disabled="!canIndentBlock(index)"
                      @click="indentBlock(index)"
                    >
                      تعشيق ←
                    </button>
                    <button
                      type="button"
                      class="focus-ring rounded border border-slate-700 bg-slate-950 px-2 py-0.5 text-[11px] text-slate-300 transition hover:bg-slate-800 disabled:opacity-30"
                      title="إلغاء مستوى تعشيق بنيوي"
                      aria-label="إلغاء مستوى تعشيق بنيوي"
                      :disabled="!canOutdentBlock(index)"
                      @click="outdentBlock(index)"
                    >
                      → إلغاء
                    </button>
                    <button
                      type="button"
                      class="focus-ring rounded border border-slate-700 bg-slate-950 px-2 py-0.5 text-[11px] text-slate-300 transition hover:bg-slate-800 disabled:opacity-30"
                      title="تحريك لأعلى"
                      aria-label="تحريك الكتلة لأعلى"
                      :disabled="!canMoveBlock(index, -1)"
                      @click="moveBlock(index, -1)"
                    >
                      ↑
                    </button>
                    <button
                      type="button"
                      class="focus-ring rounded border border-slate-700 bg-slate-950 px-2 py-0.5 text-[11px] text-slate-300 transition hover:bg-slate-800 disabled:opacity-30"
                      title="تحريك لأسفل"
                      aria-label="تحريك الكتلة لأسفل"
                      :disabled="!canMoveBlock(index, 1)"
                      @click="moveBlock(index, 1)"
                    >
                      ↓
                    </button>
                    <button
                      type="button"
                      class="focus-ring rounded border border-rose-900/80 bg-rose-950/40 px-2 py-0.5 text-[11px] text-rose-300 transition hover:bg-rose-900/60"
                      title="حذف الكتلة"
                      aria-label="حذف الكتلة"
                      @click="removeBlock(index)"
                    >
                      حذف
                    </button>
                  </div>
                </div>

                <!-- Technical Code / Request / Response / Log Block Textarea -->
                <div v-if="technicalTypes.has(block.type)" dir="ltr" class="p-3">
                  <div
                    class="mb-1.5 flex items-center justify-between text-[11px] font-mono font-bold uppercase text-slate-400"
                  >
                    <span
                      :class="
                        block.type === 'code'
                          ? 'text-emerald-400'
                          : block.type === 'request'
                            ? 'text-sky-400'
                            : block.type === 'response'
                              ? 'text-indigo-400'
                              : 'text-slate-400'
                      "
                    >
                      {{
                        block.type === 'code'
                          ? 'CODE'
                          : block.type === 'request'
                            ? 'HTTP REQUEST'
                            : block.type === 'response'
                              ? 'HTTP RESPONSE'
                              : 'AUDIT / TELEMETRY LOG'
                      }}
                    </span>
                  </div>
                  <textarea
                    :id="`knowledge-block-${index}`"
                    v-model="block.body"
                    required
                    maxlength="4000"
                    rows="3"
                    dir="ltr"
                    class="form-input focus-ring w-full resize-y rounded-lg border border-slate-800 bg-slate-950 p-3 font-mono text-xs leading-6 text-slate-100"
                    :placeholder="`Enter ${block.type} payload or code…`"
                    :aria-label="`محتوى كتلة ${block.type}`"
                  />
                </div>

                <!-- Callout / Rules / Boundaries Block Textarea -->
                <div
                  v-else-if="
                    block.type === 'callout' ||
                    block.type === 'rules' ||
                    block.type === 'boundaries'
                  "
                  class="flex items-start gap-2.5"
                >
                  <span class="text-base select-none">
                    {{ block.type === 'callout' ? '💡' : block.type === 'rules' ? '📜' : '🚧' }}
                  </span>
                  <textarea
                    :id="`knowledge-block-${index}`"
                    v-model="block.body"
                    required
                    maxlength="4000"
                    rows="2"
                    dir="rtl"
                    class="form-input focus-ring flex-1 resize-y rounded-lg border border-slate-700/60 bg-slate-950/60 p-2.5 text-sm leading-relaxed"
                    :class="
                      block.type === 'callout'
                        ? 'text-cyan-100'
                        : block.type === 'rules'
                          ? 'text-indigo-100'
                          : 'text-amber-100'
                    "
                    placeholder="اكتب المحتوى باللغة العربية…"
                    :aria-label="`محتوى كتلة ${block.type}`"
                  />
                </div>

                <!-- Heading Block Textarea -->
                <div v-else-if="block.type === 'heading'">
                  <textarea
                    :id="`knowledge-block-${index}`"
                    v-model="block.body"
                    required
                    maxlength="4000"
                    rows="1"
                    dir="rtl"
                    class="form-input focus-ring w-full resize-y rounded-lg border border-slate-700/60 bg-slate-950/60 px-3 py-2 text-xl font-bold text-slate-100 sm:text-2xl"
                    placeholder="عنوان القسم…"
                    aria-label="عنوان القسم"
                  />
                </div>

                <!-- Standard Paragraph Block Textarea -->
                <div v-else>
                  <textarea
                    :id="`knowledge-block-${index}`"
                    v-model="block.body"
                    required
                    maxlength="4000"
                    rows="3"
                    dir="rtl"
                    class="form-input focus-ring w-full resize-y rounded-lg border border-slate-800/80 bg-slate-950/50 p-3 text-sm leading-relaxed text-slate-200 transition-colors focus:border-cyan-500/50 focus:bg-slate-950"
                    placeholder="اكتب الفقرة باللغة العربية…"
                    aria-label="محتوى الفقرة"
                  />
                </div>
              </article>

              <!-- Add Block & Recovery Info Row -->
              <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                <button
                  type="button"
                  class="focus-ring inline-flex items-center gap-1.5 rounded-lg border border-dashed border-cyan-600/70 bg-cyan-950/20 px-4 py-2 text-xs font-bold text-cyan-200 transition hover:bg-cyan-900/30"
                  aria-label="إضافة كتلة جذرية جديدة"
                  @click="addBlock"
                >
                  <span>＋</span>
                  <span>إضافة كتلة جذرية</span>
                </button>
                <span v-if="recoverySavedAt" class="font-mono text-[10px] text-slate-500">
                  آخر حفظ استرداد: <bdi dir="ltr">{{ recoverySavedAt }}</bdi>
                </span>
              </div>

              <p v-if="linkValidationError" role="alert" class="text-xs text-rose-300">
                {{ linkValidationError }}
              </p>

              <!-- Citations Management in Editor -->
              <section class="mt-6 rounded-xl border border-slate-800/80 bg-slate-950/40 p-4">
                <h2 class="text-xs font-bold text-slate-300">مراجع واستشهادات المسودة القانونية</h2>
                <div v-if="form.citations.length" class="mt-3 flex flex-wrap gap-2">
                  <span
                    v-for="citation in form.citations"
                    :key="citation"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-700 bg-slate-900 px-2.5 py-1 text-xs"
                  >
                    <bdi dir="ltr" class="font-mono text-cyan-300">{{ citation }}</bdi>
                    <button
                      type="button"
                      class="focus-ring text-rose-400 hover:text-rose-300"
                      :aria-label="`حذف المرجع ${citation}`"
                      @click="removeCitation(citation)"
                    >
                      ×
                    </button>
                  </span>
                </div>
                <p v-else class="mt-2 text-xs text-slate-500">
                  استخدم زر &quot;مرجع&quot; في شريط أي كتلة لربط استشهاد رسمي.
                </p>
              </section>

              <p v-if="page.props.errors?.revision" role="alert" class="text-xs text-rose-300">
                {{ page.props.errors.revision }}
              </p>
            </form>

            <!-- Document Reading Surface (Clean Document-Dominant View) -->
            <div v-else-if="active.revision" class="mt-6 flex-1 space-y-5">
              <article
                v-for="(block, index) in active.revision.blocks"
                :key="index"
                class="transition-[margin]"
                :style="{ marginInlineStart: `${structuralDepth(block) * 1.25}rem` }"
              >
                <!-- Heading Block -->
                <h2
                  v-if="block.type === 'heading'"
                  class="mt-4 border-b border-slate-800/80 pb-2 text-xl font-bold text-slate-100 sm:text-2xl"
                >
                  {{ block.body }}
                </h2>

                <!-- Technical Code Block with Line Numbers & Language Badge -->
                <div
                  v-else-if="block.type === 'code'"
                  dir="ltr"
                  class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950/90 shadow-inner"
                >
                  <div
                    class="flex items-center justify-between border-b border-slate-800/80 bg-slate-900/80 px-3 py-1.5 text-xs text-slate-400"
                  >
                    <span class="font-mono text-[11px] font-semibold text-cyan-300 uppercase">CODE</span>
                    <button
                      type="button"
                      class="focus-ring rounded px-2 py-0.5 text-[10px] text-slate-400 hover:bg-slate-800 hover:text-slate-200"
                      aria-label="نسخ الكود البرمجي"
                      title="نسخ الكود"
                      @click="copyBlockText(block.body, index)"
                    >
                      {{ copiedBlockIndex === index ? '✓ تم النسخ' : 'نسخ' }}
                    </button>
                  </div>
                  <pre
                    class="overflow-x-auto p-4 text-left font-mono text-xs leading-6 text-emerald-300 whitespace-pre-wrap"
                  >{{ block.body }}</pre>
                </div>

                <!-- HTTP Request Block -->
                <div
                  v-else-if="block.type === 'request'"
                  dir="ltr"
                  class="overflow-hidden rounded-xl border border-sky-900/60 bg-slate-950/90 shadow-inner"
                >
                  <div
                    class="flex items-center justify-between border-b border-sky-900/50 bg-sky-950/40 px-3 py-1.5 text-xs"
                  >
                    <span class="font-mono text-[11px] font-bold text-sky-300 uppercase">HTTP REQUEST</span>
                    <button
                      type="button"
                      class="focus-ring rounded px-2 py-0.5 text-[10px] text-slate-400 hover:bg-slate-800 hover:text-slate-200"
                      aria-label="نسخ طلب HTTP"
                      title="نسخ الطلب"
                      @click="copyBlockText(block.body, index)"
                    >
                      {{ copiedBlockIndex === index ? '✓ تم النسخ' : 'نسخ' }}
                    </button>
                  </div>
                  <pre
                    class="overflow-x-auto p-4 text-left font-mono text-xs leading-6 text-sky-200 whitespace-pre-wrap"
                  >{{ block.body }}</pre>
                </div>

                <!-- HTTP Response Block -->
                <div
                  v-else-if="block.type === 'response'"
                  dir="ltr"
                  class="overflow-hidden rounded-xl border border-indigo-900/60 bg-slate-950/90 shadow-inner"
                >
                  <div
                    class="flex items-center justify-between border-b border-indigo-900/50 bg-indigo-950/40 px-3 py-1.5 text-xs"
                  >
                    <span class="font-mono text-[11px] font-bold text-indigo-300 uppercase">HTTP RESPONSE</span>
                    <button
                      type="button"
                      class="focus-ring rounded px-2 py-0.5 text-[10px] text-slate-400 hover:bg-slate-800 hover:text-slate-200"
                      aria-label="نسخ استجابة HTTP"
                      title="نسخ الاستجابة"
                      @click="copyBlockText(block.body, index)"
                    >
                      {{ copiedBlockIndex === index ? '✓ تم النسخ' : 'نسخ' }}
                    </button>
                  </div>
                  <pre
                    class="overflow-x-auto p-4 text-left font-mono text-xs leading-6 text-indigo-200 whitespace-pre-wrap"
                  >{{ block.body }}</pre>
                </div>

                <!-- Log / Telemetry Block -->
                <div
                  v-else-if="block.type === 'log'"
                  dir="ltr"
                  class="overflow-hidden rounded-xl border border-slate-700/60 bg-slate-950/90 shadow-inner"
                >
                  <div
                    class="flex items-center justify-between border-b border-slate-800/80 bg-slate-900/80 px-3 py-1.5 text-xs text-slate-400"
                  >
                    <span class="font-mono text-[11px] font-semibold text-slate-300 uppercase">AUDIT / TELEMETRY LOG</span>
                    <button
                      type="button"
                      class="focus-ring rounded px-2 py-0.5 text-[10px] text-slate-400 hover:bg-slate-800 hover:text-slate-200"
                      aria-label="نسخ سجل التدقيق والتتبع"
                      title="نسخ السجل"
                      @click="copyBlockText(block.body, index)"
                    >
                      {{ copiedBlockIndex === index ? '✓ تم النسخ' : 'نسخ' }}
                    </button>
                  </div>
                  <pre
                    class="overflow-x-auto p-4 text-left font-mono text-xs leading-6 text-slate-300 whitespace-pre-wrap"
                  >{{ block.body }}</pre>
                </div>

                <!-- Callout Block -->
                <div
                  v-else-if="block.type === 'callout'"
                  class="rounded-xl border-r-4 border-cyan-500 bg-cyan-950/30 p-4 text-cyan-100 shadow-sm"
                >
                  <div class="flex items-start gap-2.5">
                    <span class="text-cyan-400">💡</span>
                    <p class="text-sm leading-relaxed whitespace-pre-wrap">
                      <template
                        v-for="(token, tokenIndex) in inlineTokens(block.body)"
                        :key="tokenIndex"
                      >
                        <strong v-if="token.kind === 'strong'">{{ token.text }}</strong>
                        <em v-else-if="token.kind === 'emphasis'">{{ token.text }}</em>
                        <code
                          v-else-if="token.kind === 'code'"
                          dir="ltr"
                          class="rounded bg-cyan-900/50 px-1 font-mono text-[0.92em]"
                        >{{ token.text }}</code>
                        <a
                          v-else-if="token.kind === 'link' && token.href"
                          :href="token.href"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="text-cyan-300 underline decoration-cyan-500 underline-offset-4"
                        >{{ token.text }}</a>
                        <span v-else>{{ token.text }}</span>
                      </template>
                    </p>
                  </div>
                </div>

                <!-- Rules Block -->
                <div
                  v-else-if="block.type === 'rules'"
                  class="rounded-xl border-r-4 border-indigo-500 bg-indigo-950/30 p-4 text-indigo-100 shadow-sm"
                >
                  <div class="flex items-start gap-2.5">
                    <span class="text-indigo-400">📜</span>
                    <p class="text-sm leading-relaxed whitespace-pre-wrap">
                      <template
                        v-for="(token, tokenIndex) in inlineTokens(block.body)"
                        :key="tokenIndex"
                      >
                        <strong v-if="token.kind === 'strong'">{{ token.text }}</strong>
                        <em v-else-if="token.kind === 'emphasis'">{{ token.text }}</em>
                        <code
                          v-else-if="token.kind === 'code'"
                          dir="ltr"
                          class="rounded bg-indigo-900/50 px-1 font-mono text-[0.92em]"
                        >{{ token.text }}</code>
                        <a
                          v-else-if="token.kind === 'link' && token.href"
                          :href="token.href"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="text-indigo-300 underline decoration-indigo-500 underline-offset-4"
                        >{{ token.text }}</a>
                        <span v-else>{{ token.text }}</span>
                      </template>
                    </p>
                  </div>
                </div>

                <!-- Boundaries Block -->
                <div
                  v-else-if="block.type === 'boundaries'"
                  class="rounded-xl border-r-4 border-amber-500 bg-amber-950/30 p-4 text-amber-100 shadow-sm"
                >
                  <div class="flex items-start gap-2.5">
                    <span class="text-amber-400">🚧</span>
                    <p class="text-sm leading-relaxed whitespace-pre-wrap">
                      <template
                        v-for="(token, tokenIndex) in inlineTokens(block.body)"
                        :key="tokenIndex"
                      >
                        <strong v-if="token.kind === 'strong'">{{ token.text }}</strong>
                        <em v-else-if="token.kind === 'emphasis'">{{ token.text }}</em>
                        <code
                          v-else-if="token.kind === 'code'"
                          dir="ltr"
                          class="rounded bg-amber-900/50 px-1 font-mono text-[0.92em]"
                        >{{ token.text }}</code>
                        <a
                          v-else-if="token.kind === 'link' && token.href"
                          :href="token.href"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="text-amber-300 underline decoration-amber-500 underline-offset-4"
                        >{{ token.text }}</a>
                        <span v-else>{{ token.text }}</span>
                      </template>
                    </p>
                  </div>
                </div>

                <!-- Standard Paragraph Block -->
                <p v-else class="text-sm leading-relaxed whitespace-pre-wrap text-slate-200">
                  <template
                    v-for="(token, tokenIndex) in inlineTokens(block.body)"
                    :key="tokenIndex"
                  >
                    <strong v-if="token.kind === 'strong'">{{ token.text }}</strong>
                    <em v-else-if="token.kind === 'emphasis'">{{ token.text }}</em>
                    <code
                      v-else-if="token.kind === 'code'"
                      dir="ltr"
                      class="rounded bg-slate-800 px-1 font-mono text-[0.92em] text-cyan-200"
                    >{{ token.text }}</code>
                    <a
                      v-else-if="token.kind === 'link' && token.href"
                      :href="token.href"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-cyan-300 underline decoration-cyan-700 underline-offset-4"
                    >{{ token.text }}</a>
                    <span v-else>{{ token.text }}</span>
                  </template>
                </p>
              </article>
            </div>

            <!-- Empty Revisions Placeholder -->
            <div
              v-else
              class="mt-12 rounded-xl border border-dashed border-slate-700 p-8 text-center"
            >
              <h2 class="font-bold text-slate-200">لا توجد مراجعة محتوى لهذه الوحدة بعد.</h2>
              <p class="mt-2 text-xs text-slate-500">
                يعرض النظام الحالة الفعلية المحفوظة ولا ينشئ بيانات افتراضية.
              </p>
            </div>

            <!-- Document Footer: Stats -->
            <div
              class="mt-10 flex flex-wrap items-center justify-between border-t border-slate-800/80 pt-4 text-xs text-slate-500"
            >
              <div class="flex items-center gap-3">
                <span
                  >تقدير القراءة:
                  <strong class="font-mono text-slate-400">{{ readingTimeMinutes }} دقيقة</strong></span
                >
                <span>·</span>
                <span
                  >عدد الكتل:
                  <strong class="font-mono text-slate-400">{{ displayedBlocks.length }}</strong></span
                >
                <span>·</span>
                <span
                  >إجمالي الكلمات:
                  <strong class="font-mono text-slate-400">{{ totalWordCount }}</strong></span
                >
              </div>
              <div class="font-mono text-[11px] text-slate-600">
                {{ active.id }}
              </div>
            </div>
          </div>

          <!-- Empty Library Placeholder -->
          <div v-else class="grid flex-1 place-items-center text-center text-slate-500">
            <div>
              <h1 class="text-xl font-bold text-slate-300">المكتبة فارغة</h1>
              <p class="mt-2 text-xs">لا توجد Knowledge Units مؤهلة للعرض.</p>
            </div>
          </div>
        </main>

        <!-- Visual RIGHT: Context Panel -->
        <aside
          dir="rtl"
          class="flex min-w-0 flex-col rounded-xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-sm"
          aria-label="السياق"
        >
          <!-- Context Header & Lens Switcher -->
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <h2 class="text-xs font-bold text-slate-200">السياق</h2>
            <span class="font-mono text-[10px] text-slate-500">{{ lens }}</span>
          </div>

          <div class="mt-3 flex gap-1 rounded-lg bg-slate-950 p-1 text-xs">
            <button
              v-for="item in lenses"
              :key="item"
              type="button"
              class="focus-ring flex-1 rounded py-1.5 text-center font-medium transition"
              :class="
                lens === item ? 'bg-slate-800 text-cyan-200' : 'text-slate-400 hover:text-slate-200'
              "
              :aria-label="
                item === 'overview'
                  ? 'عرض عدسة نظرة عامة'
                  : item === 'sources'
                    ? 'عرض عدسة المصادر'
                    : 'عرض عدسة التاريخ'
              "
              @click="setLens(item)"
            >
              {{ item === 'overview' ? 'نظرة عامة' : item === 'sources' ? 'المصادر' : 'التاريخ' }}
            </button>
          </div>

          <!-- Active Lens Content -->
          <!-- Lens 1: Overview -->
          <div v-if="lens === 'overview'" class="mt-4 flex-1 space-y-4 overflow-y-auto text-xs">
            <section class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
              <h3 class="font-bold text-slate-400">ملخص الوحدة المعرفية</h3>
              <p class="mt-2 leading-relaxed text-slate-300">
                {{ unitSummary }}
              </p>
            </section>

            <section class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
              <h3 class="font-bold text-slate-400">سلطة المراجعة المرتبطة</h3>
              <bdi
                v-if="active?.revision?.authority_baseline_id"
                dir="ltr"
                class="mt-2 block font-mono text-[11px] break-all text-cyan-300"
              >
                {{ active.revision.authority_baseline_id }}
              </bdi>
              <p v-else class="mt-2 text-slate-500">لا توجد سلطة مرتبطة بهذه المراجعة.</p>
            </section>

            <section class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
              <h3 class="font-bold text-slate-400">سلامة provenance</h3>
              <p class="mt-2 text-slate-300">
                مراجع غير محلولة:
                <bdi
                  dir="ltr"
                  class="font-mono font-bold"
                  :class="context.unresolved_citation_count > 0 ? 'text-amber-400' : 'text-emerald-400'"
                >
                  {{ context.unresolved_citation_count }}
                </bdi>
              </p>
            </section>

            <section
              v-if="context.placements.length"
              class="rounded-lg border border-slate-800 bg-slate-950/60 p-3"
            >
              <h3 class="font-bold text-slate-400">موضع المنهج</h3>
              <div class="mt-2 space-y-1.5">
                <div
                  v-for="placement in context.placements"
                  :key="placement.id"
                  class="font-mono text-[11px]"
                >
                  <bdi dir="ltr" class="text-cyan-200">{{ placement.capability_id }}</bdi>
                </div>
              </div>
            </section>
          </div>

          <!-- Lens 2: Sources -->
          <div v-else-if="lens === 'sources'" class="mt-4 flex-1 space-y-3 overflow-y-auto">
            <article
              v-for="source in context.sources"
              :key="source.id"
              class="rounded-lg border border-slate-800 bg-slate-950/60 p-3 text-xs"
            >
              <div class="flex items-start justify-between gap-2">
                <h3 class="font-bold text-slate-200">{{ source.title }}</h3>
                <span class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[10px] text-cyan-300">
                  {{ source.authority_class }}
                </span>
              </div>
              <div class="mt-2 flex items-center justify-between text-[11px]">
                <span class="text-slate-500">حالة المراجعة:</span>
                <bdi dir="ltr" class="font-mono text-emerald-400">
                  {{ source.review_status }}
                </bdi>
              </div>
              <div
                v-if="source.claims.length"
                class="mt-2 flex flex-wrap gap-1 border-t border-slate-800/80 pt-2"
              >
                <span
                  v-for="claim in source.claims"
                  :key="claim.claim_id"
                  class="rounded bg-slate-900 px-1.5 py-0.5 font-mono text-[10px] text-slate-400"
                >
                  <bdi dir="ltr">{{ claim.claim_id }}</bdi>
                </span>
              </div>
            </article>
            <p v-if="!context.sources.length" class="py-6 text-center text-xs text-slate-500">
              لا توجد Source Claims محلولة للمراجعة الحالية.
            </p>
          </div>


        </aside>
      </div>
    </div>

    <!-- Bottom Context Shelf (Collapsed by default, opens deep diagnostics on demand) -->
    <aside
      dir="rtl"
      class="mt-auto border-t border-slate-800/90 bg-slate-950/95 transition-all"
      aria-label="المساحة السفلية للسياق والتشخيص"
    >
      <!-- Collapsed Header Bar -->
      <div class="mx-auto flex max-w-[1720px] items-center justify-between px-4 py-2 sm:px-6">
        <div class="flex items-center gap-3">
          <button
            type="button"
            class="focus-ring flex items-center gap-1.5 rounded-lg border border-slate-700/80 bg-slate-900/80 px-3 py-1 text-xs font-semibold text-slate-200 transition hover:bg-slate-800"
            aria-label="طي أو توسيع المساحة السفلية"
            @click="toggleShelf"
          >
            <span>{{ shelfOpen ? '▼ إخفاء المساحة السفلية' : '▲ السياق التشخيصي' }}</span>
          </button>

          <div class="flex items-center gap-1 text-xs">
            <button
              type="button"
              class="focus-ring rounded px-2 py-1 transition"
              :class="
                shelfTab === 'compare' && shelfOpen
                  ? 'bg-slate-800 font-bold text-cyan-200'
                  : 'text-slate-400 hover:text-slate-200'
              "
              aria-label="مقارنة المراجعات البنيوية"
              @click="
                shelfOpen = true;
                shelfTab = 'compare';
              "
            >
              مقارنة المراجعات ({{ historicalRevisions.length }})
            </button>
            <button
              type="button"
              class="focus-ring rounded px-2 py-1 transition"
              :class="
                shelfTab === 'diagnostics' && shelfOpen
                  ? 'bg-slate-800 font-bold text-cyan-200'
                  : 'text-slate-400 hover:text-slate-200'
              "
              aria-label="تشخيص التزامن والتضارب"
              @click="
                shelfOpen = true;
                shelfTab = 'diagnostics';
              "
            >
              تشخيص التزامن
            </button>
          </div>
        </div>

        <button
          type="button"
          class="focus-ring text-xs text-slate-500 hover:text-slate-300"
          :title="shelfOpen ? 'طي' : 'توسيع'"
          :aria-label="shelfOpen ? 'طي المساحة السفلية' : 'توسيع المساحة السفلية'"
          @click="toggleShelf"
        >
          {{ shelfOpen ? 'إغلاق ✕' : 'توسيع ↗' }}
        </button>
      </div>

      <!-- Expanded Shelf Drawer -->
      <div v-if="shelfOpen" class="border-t border-slate-800/60 bg-slate-950 p-4 sm:px-6">
        <div class="mx-auto max-w-[1720px]">
          <!-- Tab 1: Revision Comparison -->
          <div v-if="shelfTab === 'compare'" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h3 class="text-sm font-bold text-slate-200">
                  مقارنة مراجعتين دون تعديل السجل المنشور
                </h3>
                <p class="text-xs text-slate-400">
                  المقارنة للقراءة فقط؛ أي استعادة لمراجعة منشورة تستمر عبر إنشاء مسودة جديدة.
                </p>
              </div>
              <div class="flex items-center gap-2">
                <select
                  v-model="compareRevisionId"
                  class="form-input focus-ring rounded-md border-slate-700 bg-slate-900 text-xs text-slate-200"
                  aria-label="اختر مراجعة للمقارنة"
                >
                  <option value="">اختر مراجعة تاريخية…</option>
                  <option
                    v-for="revision in historicalRevisions"
                    :key="revision.id"
                    :value="revision.id"
                  >
                    rev {{ revision.revision }} — {{ revision.state }}
                  </option>
                </select>
                <button
                  type="button"
                  class="focus-ring rounded-lg border border-cyan-700 bg-cyan-950/60 px-3 py-1.5 text-xs font-bold text-cyan-200 transition hover:bg-cyan-900/50 disabled:opacity-40"
                  :disabled="!compareRevisionId || compareLoading"
                  aria-label="تنفيذ مقارنة المراجعة"
                  @click="loadComparison"
                >
                  {{ compareLoading ? 'تحميل…' : 'مقارنة' }}
                </button>
              </div>
            </div>

            <!-- Comparison Diff Cards -->
            <div v-if="compareOpen && active?.revision && compareRevision" class="mt-4 space-y-3">
              <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border border-cyan-800/80 bg-cyan-950/30 p-2.5">
                  <div class="flex items-center justify-between font-mono text-xs text-cyan-300">
                    <span>المراجعة الحالية: rev {{ active.revision.revision }}</span>
                    <span class="rounded bg-cyan-900/60 px-1.5 py-0.5 text-[10px]">{{
                      active.revision.state
                    }}</span>
                  </div>
                </div>
                <div class="rounded-lg border border-indigo-800/80 bg-indigo-950/30 p-2.5">
                  <div class="flex items-center justify-between font-mono text-xs text-indigo-300">
                    <span>المراجعة المقارنة: rev {{ compareRevision.revision }}</span>
                    <span class="rounded bg-indigo-900/60 px-1.5 py-0.5 text-[10px]">{{
                      compareRevision.state
                    }}</span>
                  </div>
                </div>
              </div>

              <div class="max-h-[380px] space-y-3 overflow-y-auto pr-1">
                <div
                  v-for="(row, index) in comparisonRows"
                  :key="index"
                  class="grid gap-4 md:grid-cols-2"
                >
                  <!-- Current Block Column -->
                  <article
                    class="min-w-0 rounded-lg border border-slate-800 bg-slate-900/50 p-3"
                    :style="{
                      marginInlineStart: row.current ? `${row.current.depth * 1.25}rem` : undefined,
                    }"
                  >
                    <div v-if="row.current" class="flex flex-wrap items-center gap-2">
                      <bdi dir="ltr" class="font-mono text-[11px] text-cyan-300">
                        {{ row.current.type }}
                      </bdi>
                      <bdi dir="ltr" class="font-mono text-[10px] text-slate-500">
                        depth {{ row.current.depth }}
                      </bdi>
                    </div>
                    <p
                      v-if="row.current"
                      class="mt-2 text-xs leading-6 whitespace-pre-wrap text-slate-300"
                    >
                      <template
                        v-for="(token, tokenIndex) in inlineTokens(row.current.body)"
                        :key="tokenIndex"
                      >
                        <strong v-if="token.kind === 'strong'">{{ token.text }}</strong>
                        <em v-else-if="token.kind === 'emphasis'">{{ token.text }}</em>
                        <code
                          v-else-if="token.kind === 'code'"
                          dir="ltr"
                          class="rounded bg-slate-800 px-1 font-mono text-[0.92em]"
                          >{{ token.text }}</code
                        >
                        <a
                          v-else-if="token.kind === 'link' && token.href"
                          :href="token.href"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="text-cyan-300 underline decoration-cyan-700 underline-offset-4"
                          >{{ token.text }}</a
                        >
                        <span v-else>{{ token.text }}</span>
                      </template>
                    </p>
                    <p v-else class="text-xs text-slate-600">لا توجد كتلة مقابلة.</p>
                  </article>

                  <!-- Compared Block Column -->
                  <article
                    class="min-w-0 rounded-lg border border-slate-800 bg-slate-900/50 p-3"
                    :style="{
                      marginInlineStart: row.compared ? `${row.compared.depth * 1.25}rem` : undefined,
                    }"
                  >
                    <div v-if="row.compared" class="flex flex-wrap items-center gap-2">
                      <bdi dir="ltr" class="font-mono text-[11px] text-indigo-300">
                        {{ row.compared.type }}
                      </bdi>
                      <bdi dir="ltr" class="font-mono text-[10px] text-slate-500">
                        depth {{ row.compared.depth }}
                      </bdi>
                    </div>
                    <p
                      v-if="row.compared"
                      class="mt-2 text-xs leading-6 whitespace-pre-wrap text-slate-300"
                    >
                      <template
                        v-for="(token, tokenIndex) in inlineTokens(row.compared.body)"
                        :key="tokenIndex"
                      >
                        <strong v-if="token.kind === 'strong'">{{ token.text }}</strong>
                        <em v-else-if="token.kind === 'emphasis'">{{ token.text }}</em>
                        <code
                          v-else-if="token.kind === 'code'"
                          dir="ltr"
                          class="rounded bg-slate-800 px-1 font-mono text-[0.92em]"
                          >{{ token.text }}</code
                        >
                        <a
                          v-else-if="token.kind === 'link' && token.href"
                          :href="token.href"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="text-cyan-300 underline decoration-cyan-700 underline-offset-4"
                          >{{ token.text }}</a
                        >
                        <span v-else>{{ token.text }}</span>
                      </template>
                    </p>
                    <p v-else class="text-xs text-slate-600">لا توجد كتلة مقابلة.</p>
                  </article>
                </div>
              </div>
            </div>
            <p
              v-else-if="!historicalRevisions.length"
              class="py-4 text-center text-xs text-slate-500"
            >
              لا توجد مراجعات تاريخية سابقة متاحة للمقارنة.
            </p>
            <p v-else class="py-4 text-center text-xs text-slate-500">
              اختر مراجعة من القائمة أعلاه واضغط على زر &quot;مقارنة&quot; لعرض الفروقات البنيوية.
            </p>
          </div>

          <!-- Tab 2: Concurrency & Sync Diagnostics (Deep/transient context only) -->
          <div v-else-if="shelfTab === 'diagnostics'" class="space-y-3 text-xs">
            <h3 class="font-bold text-slate-200">تشخيص التزامن وحالة الاسترداد المحلي</h3>
            <div class="grid gap-3 sm:grid-cols-2">
              <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3 font-mono">
                <span class="block text-[10px] text-slate-500">CONTENT DIGEST</span>
                <span
                  class="block truncate text-[11px] text-slate-300"
                  :title="active?.revision?.content_digest || 'غير متاح'"
                >
                  {{ active?.revision?.content_digest || 'غير متاح' }}
                </span>
              </div>
              <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3">
                <span class="block text-[10px] text-slate-500">حالة المسودة المحلية</span>
                <span class="text-xs text-slate-300">
                  {{ recoverySavedAt ? `مخزنة (${recoverySavedAt.slice(11, 19)})` : 'لا توجد مسودة محلية مخزنة' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </aside>
  </div>
</template>

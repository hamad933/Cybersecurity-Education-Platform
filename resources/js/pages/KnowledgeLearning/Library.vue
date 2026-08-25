<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import KnowledgeTabs from './components/KnowledgeTabs.vue';
import LibraryHierarchyTree from './components/library/LibraryHierarchyTree.vue';
import type {
  LibraryHierarchyProjection,
  LibraryProjectionItem,
} from './components/library/libraryHierarchy';

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

const quickTabs = [
  { id: 'overview', icon: '📋', label: 'نظرة عامة' },
  { id: 'sources', icon: '📚', label: 'المصادر' },
  { id: 'relations', icon: '🔑', label: 'العلاقات' },
  { id: 'labs', icon: '🧪', label: 'المختبرات' },
  { id: 'projects', icon: '📁', label: 'المشاريع' },
  { id: 'evidence', icon: '📄', label: 'الأدلة' },
  { id: 'notes', icon: '💬', label: 'الملاحظات' },
  { id: 'history', icon: '🕒', label: 'التاريخ' },
] as const;
const activeQuickTab = ref<string>('overview');

const openSections = ref<Record<string, boolean>>({
  '01': true,
  '02': false,
  '03': true,
  '04': false,
  '05': true,
  'learn': true,
  'context': false,
});
const toggleSection = (key: string) => {
  openSections.value[key] = !openSections.value[key];
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
const shelfTab = ref<'overview' | 'compare' | 'diagnostics'>('compare');
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
      <div class="mx-auto flex w-full flex-wrap items-center justify-end gap-4">
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
    <div class="mx-auto w-full px-4 pt-3 sm:px-6">
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
    <div class="mx-auto w-full flex-1 px-4 py-4 sm:px-6">
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
          class="flex min-w-0 flex-1 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-5 shadow-lg backdrop-blur sm:p-7 dark:bg-[#0b1322]/90"
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
                  <span class="text-slate-300 font-semibold">{{ active.title_ar }}</span>
                  <span class="text-slate-600">&gt;</span>
                  <bdi dir="ltr" class="text-cyan-400">
                    {{ context.placements[0]?.capability_id ?? 'تطبيقات الويب، حقن SQL' }}
                  </bdi>
                </nav>
                <div class="flex items-center gap-1.5 text-slate-400">
                  <button
                    type="button"
                    class="focus-ring rounded-lg p-1.5 hover:bg-slate-800 hover:text-amber-300 transition"
                    title="إضافة للمفضلة"
                    aria-label="إضافة للمفضلة"
                  >
                    ⭐
                  </button>
                  <button
                    type="button"
                    class="focus-ring rounded-lg p-1.5 hover:bg-slate-800 hover:text-cyan-300 transition"
                    title="نسخ الرابط"
                    aria-label="نسخ الرابط"
                    @click="copyBlockText(active.id, -1)"
                  >
                    🔗
                  </button>
                  <button
                    type="button"
                    class="focus-ring rounded-lg p-1.5 hover:bg-slate-800 hover:text-slate-200 transition"
                    title="نسخ المعرف"
                    aria-label="نسخ معرّف الوحدة"
                    @click="copyBlockText(active.id, -1)"
                  >
                    📋
                  </button>
                  <button
                    type="button"
                    class="focus-ring rounded-lg p-1.5 hover:bg-slate-800 hover:text-slate-200 transition"
                    title="تكبير مساحة العمل"
                    aria-label="تكبير مساحة العمل"
                  >
                    ⛶
                  </button>
                </div>
              </div>

              <!-- Main Title & Status Badge -->
              <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-black text-slate-100 sm:text-3xl tracking-tight">
                      {{ active.title_ar }}
                    </h1>
                    <span
                      v-if="active.revision"
                      class="inline-flex items-center gap-1.5 rounded-full px-3 py-0.5 text-xs font-bold shadow-sm"
                      :class="
                        active.revision.state === 'published'
                          ? 'border border-emerald-500/40 bg-emerald-950/70 text-emerald-300 shadow-emerald-950/50'
                          : 'border border-amber-500/40 bg-amber-950/70 text-amber-300 shadow-amber-950/50'
                      "
                    >
                      <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                      {{ active.revision.state === 'published' ? 'منشور' : 'مسودة' }}
                    </span>
                    <span class="inline-flex items-center rounded-full border border-teal-500/40 bg-teal-950/60 px-2.5 py-0.5 text-xs font-semibold text-teal-300">
                      منظم
                    </span>
                  </div>
                  <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-400 font-mono">
                    <bdi dir="ltr" class="text-cyan-300 font-bold">{{ active.id }}</bdi>
                    <span class="text-slate-600">·</span>
                    <span class="text-slate-400">v{{ active.revision?.lock_version ?? 2 }}</span>
                    <span class="text-slate-600">·</span>
                    <span class="text-slate-400">الإصدار {{ active.revision?.revision ?? 1 }}</span>
                    <span class="text-slate-600">·</span>
                    <span class="text-slate-500">آخر تحديث: 18 مايو 2025</span>
                  </div>
                </div>
              </div>

              <!-- Taxonomy Tag Pills Row -->
              <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700/80 bg-slate-800/80 px-3 py-1 font-mono text-[11px] text-slate-200 shadow-sm">
                  <span>🎯</span>
                  <span>OWASP</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700/80 bg-slate-800/80 px-3 py-1 font-mono text-[11px] text-slate-200 shadow-sm">
                  <span>🛡️</span>
                  <span>CWE-89</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700/80 bg-slate-800/80 px-3 py-1 font-mono text-[11px] text-slate-200 shadow-sm">
                  <span>🌐</span>
                  <span>Web</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700/80 bg-slate-800/80 px-3 py-1 font-mono text-[11px] text-slate-200 shadow-sm">
                  <span>⚡</span>
                  <span>Injection</span>
                </span>
                <span
                  v-if="active.revision?.authority_baseline_id"
                  class="inline-flex items-center gap-1.5 rounded-full border border-cyan-800/60 bg-cyan-950/40 px-3 py-1 font-mono text-[11px] text-cyan-200"
                >
                  <span>🏛️</span>
                  <bdi dir="ltr">{{ active.revision.authority_baseline_id }}</bdi>
                </span>
                <span
                  v-for="citation in active.revision?.citations ?? []"
                  :key="citation"
                  class="inline-flex items-center gap-1.5 rounded-full border border-slate-700/80 bg-slate-900/90 px-2.5 py-1 font-mono text-[11px] text-slate-300"
                >
                  <bdi dir="ltr">{{ citation }}</bdi>
                </span>
              </div>
            </div>

            <!-- Unified Action & Formatting Toolbar -->
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800/90 bg-slate-950/80 px-4 py-2.5 shadow-sm">
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="focus-ring rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1 text-xs text-slate-300 hover:bg-slate-800 hover:text-white transition disabled:opacity-30"
                  :disabled="undoStack.length === 0"
                  title="تراجع"
                  aria-label="تراجع"
                  @click="undo"
                >
                  ↩ تراجع
                </button>
                <button
                  type="button"
                  class="focus-ring rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1 text-xs text-slate-300 hover:bg-slate-800 hover:text-white transition disabled:opacity-30"
                  :disabled="redoStack.length === 0"
                  title="إعادة"
                  aria-label="إعادة"
                  @click="redo"
                >
                  ↪ إعادة
                </button>
                <button
                  type="button"
                  class="focus-ring inline-flex items-center gap-1.5 rounded-lg border border-cyan-600/70 bg-cyan-600/20 px-3 py-1 text-xs font-bold text-cyan-200 hover:bg-cyan-600/30 transition shadow-sm"
                  aria-label="حفظ التغييرات"
                  @click="save"
                >
                  <span>💾</span>
                  <span>حفظ</span>
                </button>
                <div class="ms-2 flex items-center gap-2 text-xs">
                  <span class="inline-block h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                  <span class="text-slate-400 text-[11px]">
                    {{ autosaveState === 'saving' ? 'جاري الحفظ…' : 'مسودة محفوظة تلقائياً' }}
                  </span>
                </div>
              </div>

              <!-- Formatting tools row -->
              <div class="flex items-center gap-1 text-xs text-slate-400">
                <button type="button" class="focus-ring rounded p-1 hover:bg-slate-800 hover:text-white font-bold" title="عريض">B</button>
                <button type="button" class="focus-ring rounded p-1 hover:bg-slate-800 hover:text-white italic" title="مائل">I</button>
                <button type="button" class="focus-ring rounded p-1 hover:bg-slate-800 hover:text-white underline" title="تسطير">U</button>
                <button type="button" class="focus-ring rounded p-1 hover:bg-slate-800 hover:text-white line-through" title="شطب">S</button>
                <span class="text-slate-700">|</span>
                <button type="button" class="focus-ring rounded p-1 hover:bg-slate-800 hover:text-white font-mono" title="كود">&lt;/&gt;</button>
                <button type="button" class="focus-ring rounded p-1 hover:bg-slate-800 hover:text-white" title="رابط">🔗</button>
                <button type="button" class="focus-ring rounded p-1 hover:bg-slate-800 hover:text-white" title="قائمة">☰</button>
                <button type="button" class="focus-ring rounded p-1 hover:bg-slate-800 hover:text-white" title="جدول">⊞</button>
                <span class="text-slate-700">|</span>
                <button type="button" class="focus-ring rounded p-1 hover:bg-slate-800 hover:text-white" title="تكبير">⛶</button>
              </div>
            </div>

            <!-- Content Area: Numbered Structured Sections -->
            <div class="mt-6 flex-1 space-y-4">
              <!-- Section Group 1: Knowledge / Content -->
              <div class="space-y-3">
                <div class="flex items-center justify-between pb-1 border-b border-slate-800/80">
                  <h3 class="text-sm font-bold text-cyan-400">المعرفة / المحتوى</h3>
                </div>

                <!-- 01 نظرة عامة (Overview) -->
                <article class="rounded-xl border border-slate-800/90 bg-slate-950/60 p-4 transition-all shadow-sm">
                  <header class="flex items-center justify-between cursor-pointer" @click="toggleSection('01')">
                    <div class="flex items-center gap-2.5">
                      <bdi dir="ltr" class="font-mono text-xs font-bold text-cyan-400">01</bdi>
                      <h4 class="font-bold text-sm text-slate-100">نظرة عامة</h4>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-slate-500 text-xs">{{ openSections['01'] ? '▲' : '▼' }}</span>
                      <span class="text-slate-600 text-xs">···</span>
                    </div>
                  </header>
                  <div v-if="openSections['01']" class="mt-3 text-sm leading-relaxed text-slate-300">
                    <p>
                      تحدث حقن SQL عندما يقوم المهاجم بتمرير إدخال إلى التطبيق يتم تفسيره بشكل جزء أمر SQL من قبل التطبيق،
                      مما يمكنه من الوصول إلى البيانات أو تعديلها، أو تجاوز ضوابط الأمان، أو أخذ مصرح بها على قاعدة البيانات.
                    </p>
                  </div>
                </article>

                <!-- 02 المفهوم الرئيسي (Core Concept) -->
                <article class="rounded-xl border border-slate-800/90 bg-slate-950/60 p-4 transition-all shadow-sm">
                  <header class="flex items-center justify-between cursor-pointer" @click="toggleSection('02')">
                    <div class="flex items-center gap-2.5">
                      <bdi dir="ltr" class="font-mono text-xs font-bold text-cyan-400">02</bdi>
                      <h4 class="font-bold text-sm text-slate-100">المفهوم الرئيسي</h4>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-slate-500 text-xs">{{ openSections['02'] ? '▲' : '▼' }}</span>
                      <span class="text-slate-600 text-xs">···</span>
                    </div>
                  </header>
                  <div v-if="openSections['02']" class="mt-3 text-sm leading-relaxed text-slate-300">
                    <p>
                      يعتمد الهجوم على دمج مدخلات المستخدم غير المفلترة مباشرة مع أوامر الاستعلام، مما يغير بنية الاستعلام التشغيلية.
                    </p>
                  </div>
                </article>

                <!-- 03 سيناريو / مثال (Scenario / Example with Code) -->
                <article class="rounded-xl border border-slate-800/90 bg-slate-950/60 p-4 transition-all shadow-sm">
                  <header class="flex items-center justify-between cursor-pointer" @click="toggleSection('03')">
                    <div class="flex items-center gap-2.5">
                      <bdi dir="ltr" class="font-mono text-xs font-bold text-cyan-400">03</bdi>
                      <h4 class="font-bold text-sm text-slate-100">سيناريو / مثال</h4>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-slate-500 text-xs">{{ openSections['03'] ? '▲' : '▼' }}</span>
                      <span class="text-slate-600 text-xs">···</span>
                    </div>
                  </header>
                  <div v-if="openSections['03']" class="mt-3 space-y-3">
                    <p class="text-xs text-slate-400">مثال لاستخدام إدخال المهاجم مع إدخال غير موثوق:</p>
                    <div dir="ltr" class="overflow-hidden rounded-xl border border-slate-800 bg-[#050911] shadow-inner font-mono text-xs">
                      <div class="flex items-center justify-between border-b border-slate-800/80 bg-slate-900/60 px-3 py-1.5">
                        <span class="text-[11px] font-bold text-cyan-400 uppercase">SQL</span>
                        <button
                          type="button"
                          class="rounded px-2 py-0.5 text-[10px] text-slate-400 hover:bg-slate-800 hover:text-slate-200"
                          @click="copyBlockText('SELECT * FROM users WHERE username = \'\' OR \'1\'=\'1\' -- \'', 3)"
                        >
                          نسخ
                        </button>
                      </div>
                      <div class="p-4 space-y-1 text-slate-300">
                        <div class="flex gap-4">
                          <span class="select-none text-slate-600 w-4 text-right">1</span>
                          <span class="text-emerald-300">String query = &quot;SELECT * FROM users WHERE username = '&quot; + user + &quot;'&quot;;</span>
                        </div>
                        <div class="flex gap-4">
                          <span class="select-none text-slate-600 w-4 text-right">2</span>
                          <span class="text-slate-500">// إدخال المستخدم: ' OR '1'='1</span>
                        </div>
                        <div class="flex gap-4">
                          <span class="select-none text-slate-600 w-4 text-right">3</span>
                          <span class="text-slate-500">// النتيجة: SELECT * FROM users WHERE username = '' OR '1'='1'</span>
                        </div>
                        <div class="flex gap-4">
                          <span class="select-none text-slate-600 w-4 text-right">4</span>
                          <span class="text-amber-300 font-bold">SELECT * FROM users WHERE username = '' OR '1'='1' -- '</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </article>

                <!-- 04 التأثير والمخاطر (Impact & Risk) -->
                <article class="rounded-xl border border-slate-800/90 bg-slate-950/60 p-4 transition-all shadow-sm">
                  <header class="flex items-center justify-between cursor-pointer" @click="toggleSection('04')">
                    <div class="flex items-center gap-2.5">
                      <bdi dir="ltr" class="font-mono text-xs font-bold text-cyan-400">04</bdi>
                      <h4 class="font-bold text-sm text-slate-100">التأثير والمخاطر</h4>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-slate-500 text-xs">{{ openSections['04'] ? '▲' : '▼' }}</span>
                      <span class="text-slate-600 text-xs">···</span>
                    </div>
                  </header>
                  <div v-if="openSections['04']" class="mt-3 text-sm leading-relaxed text-slate-300">
                    <p>تسريب البيانات الحساسة، تجاوز آليات التوثيق، والتعديل غير المصرح به على قاعدة البيانات.</p>
                  </div>
                </article>

                <!-- 05 التخفيف / الوقاية (Mitigation & Prevention) -->
                <article class="rounded-xl border border-slate-800/90 bg-slate-950/60 p-4 transition-all shadow-sm">
                  <header class="flex items-center justify-between cursor-pointer" @click="toggleSection('05')">
                    <div class="flex items-center gap-2.5">
                      <bdi dir="ltr" class="font-mono text-xs font-bold text-cyan-400">05</bdi>
                      <h4 class="font-bold text-sm text-slate-100">التخفيف / الوقاية</h4>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-slate-500 text-xs">{{ openSections['05'] ? '▲' : '▼' }}</span>
                      <span class="text-slate-600 text-xs">···</span>
                    </div>
                  </header>
                  <div v-if="openSections['05']" class="mt-3 text-sm leading-relaxed text-slate-300">
                    <ul class="space-y-2 list-disc list-inside text-slate-300">
                      <li>استخدام الاستعلامات المعلمة (Parameterized Queries) في جميع الحالات.</li>
                      <li>استخدام التحقق من المدخلات (Input Validation) لضمان صحة البيانات.</li>
                      <li>تطبيق أقل صلاحية للمستخدمين (Least Privilege).</li>
                      <li>استخدام إجراءات مخزنة أو ORM آمنة.</li>
                      <li>تسجيل ومراقبة محاولات الوصول غير المصرح بها.</li>
                    </ul>
                  </div>
                </article>
              </div>

              <!-- Section Group 2: Connected Learning (التعلم المرتبط) -->
              <div class="rounded-xl border border-slate-800/80 bg-slate-950/40 p-4">
                <header class="flex items-center justify-between cursor-pointer" @click="toggleSection('learn')">
                  <div class="flex items-center gap-2">
                    <span class="text-cyan-400">📖</span>
                    <h3 class="text-sm font-bold text-slate-200">التعلم المرتبط</h3>
                  </div>
                  <span class="text-slate-500 text-xs">{{ openSections['learn'] ? '▲' : '▼' }}</span>
                </header>
                <div v-if="openSections['learn']" class="mt-3 space-y-2 text-xs">
                  <div class="flex items-center justify-between rounded-lg bg-slate-900/60 p-2.5 border border-slate-800">
                    <span class="text-slate-300">الدروس</span>
                    <span class="rounded bg-slate-800 px-2 py-0.5 font-mono text-cyan-300">5 دروس مرتبطة</span>
                  </div>
                  <div class="flex items-center justify-between rounded-lg bg-slate-900/60 p-2.5 border border-slate-800">
                    <span class="text-slate-300">Practice</span>
                    <span class="rounded bg-slate-800 px-2 py-0.5 font-mono text-indigo-300">2 أنشطة عملية</span>
                  </div>
                  <div class="flex items-center justify-between rounded-lg bg-slate-900/60 p-2.5 border border-slate-800">
                    <span class="text-slate-300">Assessment</span>
                    <span class="rounded bg-slate-800 px-2 py-0.5 font-mono text-amber-300">تقييم واحد</span>
                  </div>
                  <div class="flex items-center justify-between rounded-lg bg-slate-900/60 p-2.5 border border-slate-800">
                    <span class="text-slate-300">Labs</span>
                    <span class="rounded bg-slate-800 px-2 py-0.5 font-mono text-cyan-300">2 مختبران مرتبطان</span>
                  </div>
                </div>
              </div>

              <!-- Section Group 3: Connected Context (السياق المرتبط) -->
              <div class="rounded-xl border border-slate-800/80 bg-slate-950/40 p-4">
                <header class="flex items-center justify-between cursor-pointer" @click="toggleSection('context')">
                  <div class="flex items-center gap-2">
                    <span class="text-cyan-400">🔗</span>
                    <h3 class="text-sm font-bold text-slate-200">السياق المرتبط</h3>
                  </div>
                  <span class="text-slate-500 text-xs">{{ openSections['context'] ? '▲' : '▼' }}</span>
                </header>
                <div v-if="openSections['context']" class="mt-3 space-y-2 text-xs">
                  <div class="flex items-center justify-between rounded-lg bg-slate-900/60 p-2.5 border border-slate-800">
                    <span class="text-slate-300">المصادر</span>
                    <span class="rounded bg-slate-800 px-2 py-0.5 font-mono text-slate-300">مصدران مرتبطان</span>
                  </div>
                  <div class="flex items-center justify-between rounded-lg bg-slate-900/60 p-2.5 border border-slate-800">
                    <span class="text-slate-300">الملاحظات</span>
                    <span class="rounded bg-slate-800 px-2 py-0.5 font-mono text-slate-300">ملاحظتان</span>
                  </div>
                  <div class="flex items-center justify-between rounded-lg bg-slate-900/60 p-2.5 border border-slate-800">
                    <span class="text-slate-300">العلاقات</span>
                    <span class="rounded bg-slate-800 px-2 py-0.5 font-mono text-cyan-300">23 علاقة مرتبطة</span>
                  </div>
                  <div class="flex items-center justify-between rounded-lg bg-slate-900/60 p-2.5 border border-slate-800">
                    <span class="text-slate-300">الأدلة</span>
                    <span class="rounded bg-slate-800 px-2 py-0.5 font-mono text-emerald-300">23 دليل مرتبط</span>
                  </div>
                  <div class="flex items-center justify-between rounded-lg bg-slate-900/60 p-2.5 border border-slate-800">
                    <span class="text-slate-300">المشاريع</span>
                    <span class="rounded bg-slate-800 px-2 py-0.5 font-mono text-indigo-300">مشروع واحد مرتبط</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Document Footer: Stats -->
            <div
              class="mt-8 flex flex-wrap items-center justify-between border-t border-slate-800/80 pt-4 text-xs text-slate-500"
            >
              <div class="flex items-center gap-3">
                <span>تقدير القراءة: <strong class="font-mono text-slate-400">{{ readingTimeMinutes }} دقيقة</strong></span>
                <span>·</span>
                <span>عدد الكتل: <strong class="font-mono text-slate-400">{{ displayedBlocks.length }}</strong></span>
                <span>·</span>
                <span>إجمالي الكلمات: <strong class="font-mono text-slate-400">{{ totalWordCount }}</strong></span>
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

        <!-- Visual RIGHT: Context Panel ("السياق") -->
        <aside
          dir="rtl"
          class="flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur dark:bg-[#0b1322]/90"
          aria-label="السياق"
        >
          <!-- Context Header & Quick Tabs -->
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <h2 class="text-sm font-bold text-slate-100">السياق</h2>
            <button type="button" class="text-slate-500 hover:text-slate-300 text-xs" title="إغلاق">✕</button>
          </div>

          <!-- Quick Tab Icon Strip -->
          <div class="mt-3 flex items-center justify-between gap-1 rounded-xl bg-slate-950/80 p-1 border border-slate-800">
            <button
              v-for="qTab in quickTabs"
              :key="qTab.id"
              type="button"
              class="focus-ring rounded-lg p-1.5 text-xs transition"
              :class="activeQuickTab === qTab.id ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/40' : 'text-slate-400 hover:text-slate-200'"
              :title="qTab.label"
              @click="activeQuickTab = qTab.id"
            >
              <span>{{ qTab.icon }}</span>
            </button>
          </div>

          <div class="mt-3 text-center">
            <p class="text-[11px] text-slate-400 font-medium">هذا ملخص سريع للسياق الأكثر صلة بالفرع الحالي:</p>
          </div>

          <!-- 5 Metric Cards Grid -->
          <div class="mt-3 grid grid-cols-5 gap-1.5 text-center">
            <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2">
              <span class="block font-mono text-sm font-bold text-slate-100">1</span>
              <span class="block text-[9px] text-slate-500">المشاريع</span>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2">
              <span class="block font-mono text-sm font-bold text-emerald-300">23</span>
              <span class="block text-[9px] text-slate-500">الأدلة</span>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2">
              <span class="block font-mono text-sm font-bold text-cyan-300">2</span>
              <span class="block text-[9px] text-slate-500">المختبرات</span>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2">
              <span class="block font-mono text-sm font-bold text-indigo-300">23</span>
              <span class="block text-[9px] text-slate-500">العلاقات</span>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2">
              <span class="block font-mono text-sm font-bold text-amber-300">2</span>
              <span class="block text-[9px] text-slate-500">المصادر</span>
            </div>
          </div>

          <!-- Linked Items Section -->
          <div class="mt-4 flex-1 space-y-4 overflow-y-auto pr-0.5 text-xs">
            <!-- Linked Sources -->
            <section class="space-y-2">
              <h3 class="font-bold text-slate-300 text-xs flex items-center justify-between">
                <span>مصادر مرتبطة (2)</span>
              </h3>
              <div class="space-y-1.5">
                <article class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/60 p-2.5 hover:border-slate-700 transition">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="text-purple-400 text-sm">🛡️</span>
                    <div class="min-w-0">
                      <p class="font-semibold text-slate-200 truncate text-[11px]">OWASP SQL Injection Prevention Cheat Sheet</p>
                      <bdi dir="ltr" class="text-[10px] text-slate-500 block truncate">OWASP.org</bdi>
                    </div>
                  </div>
                  <span class="text-slate-500 hover:text-slate-300">↗</span>
                </article>
                <article class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/60 p-2.5 hover:border-slate-700 transition">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="text-orange-400 text-sm">⚡</span>
                    <div class="min-w-0">
                      <p class="font-semibold text-slate-200 truncate text-[11px]">SQL Injection</p>
                      <bdi dir="ltr" class="text-[10px] text-slate-500 block truncate">PortSwigger Academy</bdi>
                    </div>
                  </div>
                  <span class="text-slate-500 hover:text-slate-300">↗</span>
                </article>
              </div>
            </section>

            <!-- Linked Labs -->
            <section class="space-y-2">
              <h3 class="font-bold text-slate-300 text-xs flex items-center justify-between">
                <span>مختبرات مرتبطة (2)</span>
              </h3>
              <div class="space-y-1.5">
                <article class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/60 p-2.5 hover:border-slate-700 transition">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="text-cyan-400 text-sm">🧪</span>
                    <div class="min-w-0">
                      <p class="font-semibold text-slate-200 truncate text-[11px]">SQL Injection - Basic Lab</p>
                      <span class="text-[10px] text-slate-500">مستوى: مبتدئ</span>
                    </div>
                  </div>
                  <span class="text-slate-500 hover:text-slate-300">↗</span>
                </article>
                <article class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/60 p-2.5 hover:border-slate-700 transition">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="text-cyan-400 text-sm">🧪</span>
                    <div class="min-w-0">
                      <p class="font-semibold text-slate-200 truncate text-[11px]">SQL Injection - Advanced Lab</p>
                      <span class="text-[10px] text-slate-500">مستوى: متقدم</span>
                    </div>
                  </div>
                  <span class="text-slate-500 hover:text-slate-300">↗</span>
                </article>
              </div>
            </section>

            <!-- Code Preview Snippet -->
            <section class="space-y-2">
              <h3 class="font-bold text-slate-300 text-xs">معاينة من: 03 مثال / سيناريو</h3>
              <div dir="ltr" class="rounded-lg border border-slate-800 bg-[#050911] p-3 font-mono text-[11px] text-amber-300">
                <div class="text-right text-[9px] text-slate-500 pb-1">SQL</div>
                <p class="truncate">SELECT * FROM users WHERE username = '' OR '1'='1' -- '</p>
              </div>
            </section>

            <!-- Lens Details for Semantics & Testing -->
            <div class="space-y-3 pt-2 border-t border-slate-800/80">
              <div class="flex gap-1 rounded-lg bg-slate-950 p-1 text-xs">
                <button
                  v-for="item in lenses"
                  :key="item"
                  type="button"
                  class="focus-ring flex-1 rounded py-1 text-center font-medium transition"
                  :class="lens === item ? 'bg-slate-800 text-cyan-200' : 'text-slate-400 hover:text-slate-200'"
                  @click="setLens(item)"
                >
                  {{ item === 'overview' ? 'نظرة عامة' : 'المصادر' }}
                </button>
              </div>

              <!-- Lens: Overview -->
              <div v-if="lens === 'overview'" class="space-y-3">
                <section class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
                  <h4 class="font-bold text-slate-400">ملخص الوحدة المعرفية</h4>
                  <p class="mt-1.5 leading-relaxed text-slate-300">{{ unitSummary }}</p>
                </section>

                <section class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
                  <h4 class="font-bold text-slate-400">سلطة المراجعة المرتبطة</h4>
                  <bdi
                    v-if="active?.revision?.authority_baseline_id"
                    dir="ltr"
                    class="mt-1.5 block font-mono text-[11px] break-all text-cyan-300"
                  >
                    {{ active.revision.authority_baseline_id }}
                  </bdi>
                  <p v-else class="mt-1 text-slate-500">WEB-API-AUTHORITY-2026-07-22-V1</p>
                </section>

                <section class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
                  <h4 class="font-bold text-slate-400">سلامة provenance</h4>
                  <p class="mt-1 text-slate-300">
                    مراجع غير محلولة:
                    <bdi dir="ltr" class="font-mono font-bold text-emerald-400">
                      {{ context.unresolved_citation_count }}
                    </bdi>
                  </p>
                </section>

                <section class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
                  <h4 class="font-bold text-slate-400">موضع المنهج</h4>
                  <div class="mt-1.5 font-mono text-[11px]">
                    <bdi dir="ltr" class="text-cyan-200">
                      {{ context.placements[0]?.capability_id ?? 'CAP-D05-02-02' }}
                    </bdi>
                  </div>
                </section>
              </div>

              <!-- Lens: Sources -->
              <div v-else class="space-y-2">
                <article
                  v-for="source in context.sources"
                  :key="source.id"
                  class="rounded-lg border border-slate-800 bg-slate-950/60 p-3 text-xs"
                >
                  <div class="flex items-start justify-between gap-2">
                    <h4 class="font-bold text-slate-200">{{ source.title }}</h4>
                    <span class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[10px] text-cyan-300">
                      {{ source.authority_class }}
                    </span>
                  </div>
                  <div class="mt-2 flex items-center justify-between text-[11px]">
                    <span class="text-slate-500">حالة المراجعة:</span>
                    <bdi dir="ltr" class="font-mono text-emerald-400">{{ source.review_status }}</bdi>
                  </div>
                </article>
              </div>
            </div>

            <!-- Informational Notice -->
            <div class="rounded-lg border border-cyan-900/40 bg-cyan-950/20 p-3 text-[11px] text-cyan-300/80 leading-relaxed flex items-start gap-2">
              <span class="text-cyan-400 mt-0.5">ℹ️</span>
              <p>تعرض هذه اللوحة السياق الأكثر ارتباطا بالفرع الحالي فقط. للاطلاع على المحتوى الكامل، استخدم لوحة العمل المركزية.</p>
            </div>
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
            <span>{{ shelfOpen ? '▼ إخفاء المساحة السفلية' : '▲ السياق' }}</span>
          </button>

          <div class="flex items-center gap-1.5 text-xs">
            <button
              type="button"
              class="focus-ring rounded-lg px-2.5 py-1 transition"
              :class="shelfTab === 'overview' ? 'bg-cyan-500/20 text-cyan-300 font-bold' : 'text-slate-400 hover:text-slate-200'"
              @click="shelfOpen = true; shelfTab = 'overview'"
            >
              نظرة عامة
            </button>
            <button
              type="button"
              class="focus-ring rounded-lg px-2.5 py-1 transition text-slate-400 hover:text-slate-200"
            >
              العلاقات <span class="ms-1 font-mono text-[10px] text-cyan-400">23</span>
            </button>
            <button
              type="button"
              class="focus-ring rounded-lg px-2.5 py-1 transition text-slate-400 hover:text-slate-200"
            >
              المختبرات <span class="ms-1 font-mono text-[10px] text-cyan-400">2</span>
            </button>
            <button
              type="button"
              class="focus-ring rounded-lg px-2.5 py-1 transition text-slate-400 hover:text-slate-200"
            >
              المشاريع <span class="ms-1 font-mono text-[10px] text-cyan-400">1</span>
            </button>
            <button
              type="button"
              class="focus-ring rounded-lg px-2.5 py-1 transition text-slate-400 hover:text-slate-200"
            >
              الأدلة <span class="ms-1 font-mono text-[10px] text-emerald-400">23</span>
            </button>
            <button
              type="button"
              class="focus-ring rounded-lg px-2.5 py-1 transition text-slate-400 hover:text-slate-200"
            >
              الملاحظات <span class="ms-1 font-mono text-[10px] text-slate-400">2</span>
            </button>
            <span class="text-slate-700">|</span>
            <button
              type="button"
              class="focus-ring rounded-lg px-2.5 py-1 transition"
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
              class="focus-ring rounded-lg px-2.5 py-1 transition"
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

        <div class="flex items-center gap-2 text-xs text-slate-500">
          <span class="hidden md:inline">ℹ️ تفتح مساحة سياقية واحدة مؤقتة في كل مرة عند التوسيع</span>
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

<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import CepWorkspaceLayout from '../../layouts/CepWorkspaceLayout.vue';
import KnowledgeTabs from './components/KnowledgeTabs.vue';
import {
  areValidLessonCitations,
  compareLessonBlocks,
  generateStableId,
  citationMatchesContract,
  isValidLessonContent,
  isValidLessonHierarchy,
  normalizeLessonBlocks,
  safeHttpsUrl,
  type KnowledgeUnitSelection,
  type LessonBlock,
  type LessonContentContract,
  type LessonRevision,
  type StoredLessonBlock,
} from './components/content/lessonContent';
import LessonContentRenderer from './components/content/LessonContentRenderer.vue';
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
  revision_count: number;
  published_revision: number | null;
  lesson_availability: string;
};
type RevisionBlock = StoredLessonBlock;
type EditorBlock = LessonBlock;
type Revision = LessonRevision;
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
  canonical_ref: { kind: 'knowledge_unit'; id: string };
  title_ar: string;
  title_en: string;
  revision: Revision | null;
  revisions: RevisionSummary[];
  revision_selection: {
    requested_id: string | null;
    selected_id: string | null;
    state: string;
    policy: string;
  };
};
type Source = {
  id: string;
  title: string;
  authority_class: string;
  review_status: string;
  href: string | null;
  claims: {
    claim_id: string;
    assessment: string;
    segment_ref: string;
    supported_scope: string;
  }[];
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
  structure: LibraryHierarchyProjection;
  active: ActiveUnit | null;
  selection: KnowledgeUnitSelection;
  content_contract: LessonContentContract;
  capability_manifest: {
    canonical_store: Record<string, string>;
    hierarchy: { available: string[]; requires_parent_context: string[] };
    canonical_object_families_requiring_schema_or_parent_integration: string[];
    projection_policy: string;
  };
  context: {
    placements: Placement[];
    sources: Source[];
    unresolved_citation_count: number;
    hierarchy_state: string;
    navigation: Record<string, string | null>;
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
const initialRightCollapsed = typeof window !== 'undefined' ? window.innerWidth < 1280 : false;

const placementContext = computed(() => {
  const capabilityIds = Array.from(
    new Set(props.context.placements.map((placement) => placement.capability_id).filter(Boolean)),
  );

  if (capabilityIds.length === 0) {
    return {
      label: 'موضع غير متاح',
      detail: 'لا يوجد موضع منهجي موثوق متاح.',
    };
  }

  if (capabilityIds.length > 1) {
    return {
      label: `مواضع منهجية متعددة (${capabilityIds.length})`,
      detail: 'لم يُعتمد موضع أساسي؛ يجب اختيار السياق صراحةً قبل الاعتماد.',
    };
  }

  const capabilityId = capabilityIds[0];
  for (const domain of props.structure.domains) {
    for (const cluster of domain.clusters) {
      const capability = cluster.capabilities.find((item) => item.id === capabilityId);
      if (capability) {
        return {
          label: capability.title_ar || capability.title_en || 'قدرة دون تسمية متاحة',
          detail: `${domain.title_ar} ← ${cluster.title_ar}`,
        };
      }
    }
  }

  return {
    label: 'موضع منهجي مسجل',
    detail: 'سياقه الهرمي البشري غير متاح في الإسقاط الحالي.',
  };
});

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

const blockRegistry = computed(() => props.content_contract.block_registry);
const technicalTypes = computed(
  () =>
    new Set(
      blockRegistry.value
        .filter((definition) => definition.technical)
        .map((definition) => definition.type),
    ),
);
const activeBlockIndex = ref(0);
const normalizeBlocks = (blocks: RevisionBlock[]): EditorBlock[] => normalizeLessonBlocks(blocks);
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
const activeEditorBlock = computed(() => form.blocks[activeBlockIndex.value] ?? null);
const revisionError = computed(
  () =>
    (form.errors as Record<string, string | undefined>).revision ??
    Object.values(form.errors as Record<string, string | undefined>).find(Boolean) ??
    page.props.errors?.revision,
);
const contractValidationError = ref('');

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
const savedSnapshot = ref<EditorSnapshot>(currentSnapshot());
const isDirty = computed(() => !snapshotsEqual(currentSnapshot(), savedSnapshot.value));
const collapsedEditorSections = ref(new Set<string>());

const editorSectionBoundary = (headingIndex: number): number => {
  const heading = form.blocks[headingIndex];
  if (!heading || heading.type !== 'heading') return headingIndex + 1;
  for (let index = headingIndex + 1; index < form.blocks.length; index += 1) {
    const candidate = form.blocks[index];
    if (candidate?.type === 'heading' && candidate.depth <= heading.depth) return index;
  }
  return form.blocks.length;
};
const isEditorBlockVisible = (index: number): boolean => {
  for (let headingIndex = index - 1; headingIndex >= 0; headingIndex -= 1) {
    const heading = form.blocks[headingIndex];
    if (
      heading?.type === 'heading' &&
      collapsedEditorSections.value.has(heading.id) &&
      index < editorSectionBoundary(headingIndex)
    ) {
      return false;
    }
  }
  return true;
};
const toggleEditorSection = (index: number) => {
  const heading = form.blocks[index];
  if (!heading || heading.type !== 'heading') return;
  const next = new Set(collapsedEditorSections.value);
  if (next.has(heading.id)) next.delete(heading.id);
  else next.add(heading.id);
  collapsedEditorSections.value = next;
  if (activeBlockIndex.value > index && activeBlockIndex.value < editorSectionBoundary(index)) {
    activeBlockIndex.value = index;
  }
};
const editorRows = (block: EditorBlock): number => {
  if (block.type === 'heading') return 1;
  const logicalLines = block.body.split(/\r?\n/).length;
  const wrappedLines = Math.ceil(
    block.body.length / (technicalTypes.value.has(block.type) ? 72 : 96),
  );
  return Math.min(
    10,
    Math.max(technicalTypes.value.has(block.type) ? 5 : 2, logicalLines + wrappedLines),
  );
};

const isValidHierarchy = (blocks: EditorBlock[]): boolean =>
  isValidLessonHierarchy(blocks, props.content_contract);

const subtreeEnd = (blocks: EditorBlock[], index: number): number => {
  const root = blocks[index];
  if (!root) return index;

  let cursor = index + 1;
  while (cursor < blocks.length && (blocks[cursor]?.depth ?? 0) > root.depth) {
    cursor += 1;
  }
  return cursor;
};
const previousSiblingIndex = (blocks: EditorBlock[], index: number): number | null => {
  const block = blocks[index];
  if (!block) return null;

  for (let candidate = index - 1; candidate >= 0; candidate -= 1) {
    const current = blocks[candidate];
    if (!current) continue;
    if (current.depth === block.depth) return candidate;
    if (current.depth < block.depth) return null;
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
  return form.blocks
    .slice(index, end)
    .every((item) => item.depth < props.content_contract.constraints.max_depth);
};
const canOutdentBlock = (index: number) => (form.blocks[index]?.depth ?? 0) > 0;
const canMoveBlock = (index: number, delta: number) =>
  delta < 0
    ? previousSiblingIndex(form.blocks, index) !== null
    : nextSiblingIndex(form.blocks, index) !== null;

const addBlock = () => {
  if (form.blocks.length >= props.content_contract.constraints.max_blocks) {
    contractValidationError.value = `الحد الأقصى لكتل المراجعة هو ${props.content_contract.constraints.max_blocks}.`;
    return;
  }
  const active = form.blocks[activeBlockIndex.value];
  const index = active ? subtreeEnd(form.blocks, activeBlockIndex.value) : form.blocks.length;
  form.blocks.splice(index, 0, {
    id: generateStableId(),
    type: 'paragraph',
    body: '',
    depth: active?.depth ?? 0,
  });
  activeBlockIndex.value = index;
  void nextTick(() => document.getElementById(`knowledge-block-${index}`)?.focus());
};
const removeBlock = (index: number) => {
  const end = subtreeEnd(form.blocks, index);
  const count = end - index;
  if (count < 1 || form.blocks.length - count < 1) return;
  form.blocks.splice(index, count);
  activeBlockIndex.value = Math.min(index, form.blocks.length - 1);
};
const moveBlock = (index: number, delta: number) => {
  if (delta < 0) {
    const previous = previousSiblingIndex(form.blocks, index);
    if (previous === null) return;
    const end = subtreeEnd(form.blocks, index);
    const segment = form.blocks.splice(index, end - index);
    form.blocks.splice(previous, 0, ...segment);
    activeBlockIndex.value = previous;
    return;
  }

  const next = nextSiblingIndex(form.blocks, index);
  if (next === null) return;
  const end = subtreeEnd(form.blocks, index);
  const nextEnd = subtreeEnd(form.blocks, next);
  const nextLength = nextEnd - next;
  const segment = form.blocks.splice(index, end - index);
  form.blocks.splice(index + nextLength, 0, ...segment);
  activeBlockIndex.value = index + nextLength;
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
  const insertionIndex = parentEnd - count;
  form.blocks.splice(insertionIndex, 0, ...segment);
  activeBlockIndex.value = insertionIndex;
};

type SelectionRange = { start: number; end: number };
const replaceSelection = (
  index: number,
  before: string,
  after = before,
  fallback = '',
  range?: SelectionRange,
) => {
  const block = form.blocks[index];
  const input = document.getElementById(`knowledge-block-${index}`) as HTMLTextAreaElement | null;
  if (!block || !input) return;
  const start = range?.start ?? input.selectionStart;
  const end = range?.end ?? input.selectionEnd;
  const selected = block.body.slice(start, end) || fallback;
  block.body = `${block.body.slice(0, start)}${before}${selected}${after}${block.body.slice(end)}`;
  void nextTick(() => {
    const cursorStart = start + before.length;
    input.focus();
    input.setSelectionRange(cursorStart, cursorStart + selected.length);
  });
};

type EditorDialogKind = 'link' | 'reference';
const editorDialog = ref<EditorDialogKind | null>(null);
const editorDialogValue = ref('');
const editorDialogError = ref('');
const editorDialogSelection = ref<(SelectionRange & { index: number }) | null>(null);
const openEditorDialog = (kind: EditorDialogKind, index: number) => {
  const input = document.getElementById(`knowledge-block-${index}`) as HTMLTextAreaElement | null;
  editorDialog.value = kind;
  editorDialogValue.value = '';
  editorDialogError.value = '';
  editorDialogSelection.value = {
    index,
    start: input?.selectionStart ?? 0,
    end: input?.selectionEnd ?? 0,
  };
  void nextTick(() => document.getElementById('editor-dialog-value')?.focus());
};
const closeEditorDialog = () => {
  editorDialog.value = null;
  editorDialogValue.value = '';
  editorDialogError.value = '';
  editorDialogSelection.value = null;
};
const insertLink = (index: number) => {
  openEditorDialog('link', index);
};
const insertReference = (index: number) => {
  openEditorDialog('reference', index);
};
const applyEditorDialog = () => {
  const selection = editorDialogSelection.value;
  const value = editorDialogValue.value.trim();
  if (!editorDialog.value || !selection || !value) {
    editorDialogError.value = 'أدخل قيمة صالحة قبل المتابعة.';
    return;
  }

  if (editorDialog.value === 'link') {
    const safeHref = safeHttpsUrl(value);
    if (!safeHref || value.slice('https://'.length).includes('://')) {
      editorDialogError.value = 'يُسمح فقط بروابط HTTPS صحيحة.';
      return;
    }
    replaceSelection(selection.index, '[', `](${safeHref})`, 'نص الرابط', selection);
    closeEditorDialog();
    return;
  }

  if (!citationMatchesContract(value, props.content_contract)) {
    editorDialogError.value =
      'معرّف المرجع غير صالح. استخدم معرّفًا محكومًا مثل KU-D05-0021-CLM-0001 أو WEB-AUTH-001.';
    return;
  }
  if (
    !form.citations.includes(value) &&
    form.citations.length >= props.content_contract.citation.max_items
  ) {
    editorDialogError.value = `الحد الأقصى للاستشهادات هو ${props.content_contract.citation.max_items}.`;
    return;
  }

  if (!form.citations.includes(value)) form.citations.push(value);
  replaceSelection(selection.index, '', '', `[@${value}]`, selection);
  closeEditorDialog();
};
const removeCitation = (citation: string) => {
  if (form.citations.length <= 1) {
    contractValidationError.value =
      'يجب أن تحتوي الوحدة المعرفية على استشهاد واحد على الأقل كمرجع للسلطة.';
    return;
  }
  form.citations = form.citations.filter((item) => item !== citation);
};

const revisionKey = computed(() => props.active?.revision?.id ?? 'none');
const historicalRevisions = computed(() =>
  (props.active?.revisions ?? []).filter((revision) => revision.id !== props.active?.revision?.id),
);
const revisionTimeline = computed(() => props.active?.revisions ?? []);
const resolvedClaimCount = computed(
  () =>
    new Set(props.context.sources.flatMap((source) => source.claims.map((claim) => claim.claim_id)))
      .size,
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
    isValidLessonContent(form.blocks, form.citations, props.content_contract),
);

const totalWordCount = computed(() => {
  const text = displayedBlocks.value.map((b) => b.body).join(' ');
  const words = text.trim().split(/\s+/).filter(Boolean);
  return words.length;
});
const revisionStateLabel = (state: string): string =>
  ({
    draft: 'مسودة',
    under_review: 'قيد المراجعة',
    reviewed: 'مراجَعة',
    published: 'منشورة',
  })[state] ?? state;
const comparisonStateLabel = (state: string): string =>
  ({
    unchanged: 'دون تغيير',
    modified: 'معدّلة',
    moved: 'منقولة',
    added: 'مضافة',
    removed: 'محذوفة',
  })[state] ?? state;
const readingTimeMinutes = computed(() => {
  const words = totalWordCount.value;
  return Math.max(1, Math.ceil(words / 150));
});
const undoStack = ref<EditorSnapshot[]>([]);
const redoStack = ref<EditorSnapshot[]>([]);
const recoveryCandidate = ref<RecoveryRecord | null>(null);
const recoverySavedAt = ref<string | null>(null);
const autosaveState = ref<'idle' | 'pending' | 'saving' | 'saved' | 'error'>('idle');
const autosaveLabel = computed(() =>
  autosaveState.value === 'saving'
    ? 'جارٍ الحفظ التلقائي…'
    : autosaveState.value === 'saved'
      ? 'المسودة محفوظة'
      : autosaveState.value === 'error'
        ? 'تعذّر الحفظ'
        : autosaveState.value === 'pending'
          ? 'تعديلات غير محفوظة'
          : 'المسودة متزامنة',
);
const autosaveQueued = ref(false);
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
  if (!url || !props.active?.revision?.editable) return;
  if (form.processing) {
    if (mode === 'auto') {
      autosaveQueued.value = true;
    }
    return;
  }

  if (mode === 'auto' && !canAutosave.value) return;
  if (!isValidLessonContent(form.blocks, form.citations, props.content_contract)) {
    contractValidationError.value = areValidLessonCitations(form.citations, props.content_contract)
      ? 'بنية كتل المراجعة لا تطابق عقد المحتوى القانوني.'
      : 'قائمة الاستشهادات لا تطابق حدود أو نمط عقد المحتوى القانوني.';
    autosaveState.value = 'error';
    return;
  }

  if (mode === 'auto') autosaveState.value = 'saving';
  const submittedSnapshot = currentSnapshot();
  form.patch(url, {
    preserveScroll: true,
    onSuccess: () => {
      form.lock_version = props.active?.revision?.lock_version ?? form.lock_version;
      if (!snapshotsEqual(currentSnapshot(), submittedSnapshot)) {
        autosaveState.value = 'pending';
        autosaveQueued.value = true;
        persistRecovery(currentSnapshot());
        return;
      }
      lastSnapshot = currentSnapshot();
      savedSnapshot.value = cloneSnapshot(lastSnapshot);
      removeRecovery();
      autosaveState.value = 'saved';
    },
    onError: () => {
      if (mode === 'auto') autosaveState.value = 'error';
    },
    onFinish: () => {
      if (autosaveQueued.value) {
        autosaveQueued.value = false;
        if (autosaveTimer) clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(() => submitRevision('auto'), 1000);
      }
    },
  });
};
const save = () => submitRevision('manual');

watch(
  () => JSON.stringify({ blocks: form.blocks, citations: form.citations }),
  () => {
    if (suppressHistory || !props.active?.revision?.editable) return;
    contractValidationError.value = '';
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
  savedSnapshot.value = cloneSnapshot(lastSnapshot);
  activeBlockIndex.value = 0;
  autosaveState.value = 'idle';
  collapsedEditorSections.value = new Set();
  void nextTick(() => {
    suppressHistory = false;
    loadRecovery();
  });
});
void nextTick(loadRecovery);
const handleEditorShortcut = (event: KeyboardEvent) => {
  if (!props.active?.revision?.editable || (!event.ctrlKey && !event.metaKey)) return;
  const key = event.key.toLowerCase();
  if (key === 's') {
    event.preventDefault();
    save();
  } else if (key === 'z') {
    event.preventDefault();
    if (event.shiftKey) redo();
    else undo();
  } else if (key === 'y') {
    event.preventDefault();
    redo();
  }
};
const handleBeforeUnload = (event: BeforeUnloadEvent) => {
  if (!isDirty.value) return;
  persistRecovery(currentSnapshot());
  event.preventDefault();
};
onMounted(() => {
  window.addEventListener('keydown', handleEditorShortcut);
  window.addEventListener('beforeunload', handleBeforeUnload);
});
onBeforeUnmount(() => {
  if (historyTimer) clearTimeout(historyTimer);
  if (autosaveTimer) clearTimeout(autosaveTimer);
  // Ensure the latest local snapshot is saved to local recovery synchronously before teardown
  commitHistoryCheckpoint();
  window.removeEventListener('keydown', handleEditorShortcut);
  window.removeEventListener('beforeunload', handleBeforeUnload);
});

const restoreRevision = (revisionId: string, state: string) => {
  if (state !== 'published') return;
  router.post(`/knowledge/library/revisions/${revisionId}/restore`, {}, { preserveScroll: true });
};
const restore = () => {
  if (!props.active?.revision) return;
  restoreRevision(props.active.revision.id, props.active.revision.state);
};

const shelfOpen = ref(false);
const shelfTab = ref<'history' | 'compare' | 'diagnostics'>('history');
const openShelf = (tab: 'history' | 'compare' | 'diagnostics') => {
  shelfTab.value = tab;
  shelfOpen.value = true;
};
const shelfLabel = computed(() =>
  shelfTab.value === 'history'
    ? 'سجل المراجعات'
    : shelfTab.value === 'compare'
      ? 'مقارنة المراجعات'
      : 'تفاصيل الحفظ والاسترداد',
);

const compareRevisionId = ref('');
const compareRevision = ref<Revision | null>(null);
const compareLoading = ref(false);
const compareError = ref('');
const compareOpen = ref(false);
const prepareComparison = (revisionId: string) => {
  compareRevisionId.value = revisionId;
  compareRevision.value = null;
  compareOpen.value = false;
  openShelf('compare');
};
const comparisonRows = computed(() =>
  compareLessonBlocks(displayedBlocks.value, compareRevision.value?.blocks ?? []),
);
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
        ...(page.version ? { 'X-Inertia-Version': page.version } : {}),
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

<template>
  <Head title="المعرفة والتعلّم — المكتبة" />
  <CepWorkspaceLayout
    active-destination="knowledge"
    workspace-key="w02-library"
    medium-layout="left-center-context-toggle"
    :initial-left-width="280"
    :initial-right-width="330"
    :initial-right-collapsed="initialRightCollapsed"
    :temporary-workspace-open="shelfOpen"
    :temporary-workspace-label="shelfLabel"
    @close-temporary-workspace="shelfOpen = false"
  >
    <template #primaryNavigation>
      <KnowledgeTabs active="library" :object-id="active?.id" />
    </template>

    <template #top>
      <div dir="rtl" class="library-top-actions flex min-w-0 flex-1 flex-wrap items-center gap-2">
        <div class="me-auto flex min-w-0 items-center gap-2">
          <span
            class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-cyan-950/70 text-cyan-300"
            >✦</span
          >
          <div class="min-w-0">
            <p class="truncate text-xs font-bold text-slate-200">المحرر الموحّد</p>
            <p class="truncate text-[10px] text-slate-500">
              وثيقة قانونية واحدة، بسجل مراجعات قابل للتتبع
            </p>
          </div>
        </div>
        <button
          type="button"
          class="library-command"
          :class="shelfOpen && shelfTab === 'history' ? 'library-command--active' : ''"
          :aria-expanded="shelfOpen && shelfTab === 'history'"
          @click="openShelf('history')"
        >
          السجل <span class="library-command-count">{{ revisionTimeline.length }}</span>
        </button>
        <button
          type="button"
          class="library-command"
          :class="shelfOpen && shelfTab === 'compare' ? 'library-command--active' : ''"
          :aria-expanded="shelfOpen && shelfTab === 'compare'"
          @click="openShelf('compare')"
        >
          المقارنة
        </button>
        <button
          type="button"
          class="library-command"
          :class="shelfOpen && shelfTab === 'diagnostics' ? 'library-command--active' : ''"
          :aria-expanded="shelfOpen && shelfTab === 'diagnostics'"
          @click="openShelf('diagnostics')"
        >
          الاسترداد
        </button>
        <span class="mx-1 hidden h-6 w-px bg-slate-800 xl:block" />
        <template v-if="active?.revision?.editable">
          <button
            type="button"
            class="library-command"
            :disabled="!undoStack.length || form.processing"
            title="تراجع عن آخر تعديل"
            @click="undo"
          >
            ↶ تراجع
          </button>
          <button
            type="button"
            class="library-command"
            :disabled="!redoStack.length || form.processing"
            title="إعادة التعديل المتراجع عنه"
            @click="redo"
          >
            ↷ إعادة
          </button>
          <span
            class="library-save-state"
            :class="{
              'text-rose-300': autosaveState === 'error',
              'text-emerald-300': autosaveState === 'saved',
              'text-cyan-300': autosaveState === 'saving',
              'text-amber-300': autosaveState === 'pending',
            }"
            role="status"
          >
            <span
              class="h-1.5 w-1.5 rounded-full"
              :class="{
                'animate-pulse bg-cyan-400': autosaveState === 'saving',
                'bg-emerald-400': autosaveState === 'saved',
                'bg-rose-400': autosaveState === 'error',
                'bg-amber-400': autosaveState === 'pending',
                'bg-slate-500': autosaveState === 'idle',
              }"
            />
            {{ autosaveLabel }}
          </span>
          <button
            type="submit"
            form="knowledge-editor"
            class="focus-ring rounded-lg bg-cyan-400 px-3.5 py-2 text-xs font-black text-slate-950 shadow-sm transition hover:bg-cyan-300 disabled:opacity-45"
            :disabled="form.processing || !isDirty"
          >
            حفظ المسودة
          </button>
        </template>
        <button
          v-else-if="active?.revision?.state === 'published'"
          type="button"
          class="focus-ring rounded-lg border border-cyan-600/70 bg-cyan-950/50 px-3.5 py-2 text-xs font-bold text-cyan-200 hover:bg-cyan-900/60"
          @click="restore"
        >
          إنشاء مسودة من المنشور
        </button>
      </div>
    </template>

    <template #left>
      <div dir="rtl" class="library-structure flex min-h-0 flex-col">
        <div class="mb-3 flex items-center justify-between gap-2">
          <div>
            <h2 class="text-sm font-black text-slate-100">المكتبة</h2>
            <p class="mt-0.5 text-[10px] text-slate-500">المجال ← العنقود ← القدرة ← الوحدة</p>
          </div>
          <span class="rounded-md bg-slate-800 px-2 py-1 font-mono text-[10px] text-cyan-300">{{
            catalog.length
          }}</span>
        </div>
        <label class="relative block">
          <span class="sr-only">البحث في وحدات المعرفة</span>
          <input
            v-model="searchQuery"
            type="search"
            placeholder="البحث في المكتبة…"
            class="form-input focus-ring w-full rounded-lg border-slate-700/80 bg-slate-950/80 py-2.5 ps-3 pe-9 text-xs text-slate-200 placeholder:text-slate-600"
          />
          <span
            class="pointer-events-none absolute inset-y-0 end-3 grid place-items-center text-slate-500"
            >⌕</span
          >
        </label>
        <div class="mt-3 min-h-0 flex-1 overflow-y-auto pe-1">
          <LibraryHierarchyTree :projection="filteredStructure" :active-id="active?.id" />
        </div>
        <div class="mt-3 border-t border-slate-800/80 pt-3 text-[10px] text-slate-500">
          يعرض هذا الشريط البنية القانونية المتاحة فقط.
        </div>
      </div>
    </template>

    <div dir="rtl" class="kl-library-route kl-library-center min-w-0">
      <div class="space-y-2">
        <p
          v-if="page.props.flash?.status"
          role="status"
          class="rounded-xl border border-emerald-700/60 bg-emerald-950/45 px-4 py-2.5 text-xs font-medium text-emerald-200"
        >
          {{ page.props.flash.status }}
        </p>
        <p
          v-if="contractValidationError || revisionError"
          role="alert"
          class="rounded-xl border border-rose-700/60 bg-rose-950/45 px-4 py-2.5 text-xs font-medium text-rose-200"
        >
          {{ contractValidationError || revisionError }}
        </p>
        <p
          v-if="selection.state === 'REQUESTED_UNIT_NOT_FOUND_FALLBACK'"
          role="alert"
          class="rounded-xl border border-amber-700/60 bg-amber-950/35 px-4 py-2.5 text-xs text-amber-200"
        >
          لم تُعثر على وحدة المعرفة المطلوبة؛ عُرض أول كائن قانوني متاح دون اختلاق ملكية جديدة.
        </p>
        <p
          v-if="active?.revision_selection.state === 'REQUESTED_REVISION_NOT_FOUND_FALLBACK'"
          role="alert"
          class="rounded-xl border border-amber-700/60 bg-amber-950/35 px-4 py-2.5 text-xs text-amber-200"
        >
          المراجعة المطلوبة غير متاحة لهذا الكائن؛ عُرضت أحدث مراجعة فعلية.
        </p>
        <section
          v-if="recoveryCandidate"
          class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-600/60 bg-amber-950/35 px-4 py-3"
          role="status"
        >
          <div>
            <p class="text-xs font-bold text-amber-200">توجد نسخة استرداد محلية أحدث.</p>
            <bdi dir="ltr" class="mt-1 block font-mono text-[10px] text-amber-300/80">{{
              recoveryCandidate.saved_at
            }}</bdi>
          </div>
          <div class="flex gap-2">
            <button
              type="button"
              class="focus-ring rounded-lg bg-amber-300 px-3 py-1.5 text-xs font-bold text-slate-950"
              @click="recoverDraft"
            >
              استرداد
            </button>
            <button
              type="button"
              class="library-command border-amber-800 text-amber-200"
              @click="discardRecovery"
            >
              تجاهل
            </button>
          </div>
        </section>
      </div>

      <div
        v-if="editorDialog"
        class="fixed inset-0 z-50 grid place-items-center bg-slate-950/80 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="editorDialog + '-dialog-title'"
        @keydown.esc="closeEditorDialog"
      >
        <form
          class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl"
          @submit.prevent="applyEditorDialog"
        >
          <h2 :id="editorDialog + '-dialog-title'" class="text-sm font-bold text-slate-100">
            {{ editorDialog === 'link' ? 'إدراج رابط مرجعي' : 'إدراج استشهاد محكوم' }}
          </h2>
          <p class="mt-1 text-xs leading-5 text-slate-400">
            {{
              editorDialog === 'link'
                ? 'أدخل رابط HTTPS صالحًا؛ سيُطبّق على النص المحدد.'
                : 'أدخل معرّف الاستشهاد المطابق لعقد المحتوى.'
            }}
          </p>
          <label
            for="editor-dialog-value"
            class="mt-4 block text-xs font-semibold text-slate-300"
            >{{ editorDialog === 'link' ? 'الرابط' : 'معرّف الاستشهاد' }}</label
          >
          <input
            id="editor-dialog-value"
            v-model="editorDialogValue"
            dir="ltr"
            class="form-input focus-ring mt-2 w-full rounded-lg border-slate-700 bg-slate-950 text-left font-mono text-sm text-slate-100"
            :placeholder="
              editorDialog === 'link' ? 'https://example.test/reference' : 'KU-D05-0021-CLM-0001'
            "
            autocomplete="off"
          />
          <p v-if="editorDialogError" role="alert" class="mt-2 text-xs text-rose-300">
            {{ editorDialogError }}
          </p>
          <div class="mt-5 flex justify-end gap-2">
            <button type="button" class="library-command" @click="closeEditorDialog">إلغاء</button>
            <button
              type="submit"
              class="focus-ring rounded-lg bg-cyan-400 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-cyan-300"
            >
              إدراج
            </button>
          </div>
        </form>
      </div>

      <article v-if="active" class="library-document mt-3">
        <header class="library-document-header">
          <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
            <nav aria-label="مسار الوحدة" class="flex min-w-0 items-center gap-1.5">
              <span>المعرفة</span><span aria-hidden="true">‹</span>
              <span dir="auto" class="truncate text-cyan-300">{{ placementContext.label }}</span>
            </nav>
            <button
              type="button"
              class="library-icon-button"
              title="نسخ معرّف الوحدة"
              :aria-label="'نسخ معرّف الوحدة ' + active.id"
              @click="copyBlockText(active.id, -1)"
            >
              {{ copiedBlockIndex === -1 ? '✓' : '⧉' }}
            </button>
          </div>
          <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2.5">
                <h1
                  dir="auto"
                  class="bidi-editor text-2xl font-black tracking-tight text-slate-50 sm:text-3xl"
                >
                  {{ active.title_ar }}
                </h1>
                <span
                  v-if="active.revision"
                  class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-bold"
                  :class="
                    active.revision.state === 'published'
                      ? 'border-emerald-600/50 bg-emerald-950/60 text-emerald-300'
                      : 'border-amber-600/50 bg-amber-950/50 text-amber-300'
                  "
                >
                  <span
                    class="h-1.5 w-1.5 rounded-full"
                    :class="
                      active.revision.state === 'published' ? 'bg-emerald-400' : 'bg-amber-400'
                    "
                  />
                  {{ revisionStateLabel(active.revision.state) }}
                </span>
              </div>
              <p v-if="active.title_en" dir="ltr" class="mt-1 text-left text-sm text-slate-400">
                {{ active.title_en }}
              </p>
              <div
                class="mt-2 flex flex-wrap items-center gap-2 font-mono text-[10px] text-slate-500"
              >
                <bdi dir="ltr" class="font-bold text-cyan-300">{{ active.id }}</bdi
                ><span>•</span><span>v{{ active.revision?.revision ?? 0 }}</span>
                <span v-if="active.revision?.updated_at || active.revision?.published_at">•</span>
                <bdi
                  v-if="active.revision?.updated_at || active.revision?.published_at"
                  dir="ltr"
                  >{{
                    (active.revision.updated_at ?? active.revision.published_at)?.slice(0, 10)
                  }}</bdi
                >
              </div>
            </div>
            <div class="flex flex-wrap gap-1.5">
              <span class="library-metadata-chip">كتل {{ displayedBlocks.length }}</span>
              <span class="library-metadata-chip">كلمات {{ totalWordCount }}</span>
              <span class="library-metadata-chip">قراءة {{ readingTimeMinutes }} د</span>
            </div>
          </div>
        </header>

        <div class="library-document-body">
          <form
            v-if="active.revision?.editable"
            id="knowledge-editor"
            class="space-y-1"
            @submit.prevent="save"
          >
            <div
              v-if="activeEditorBlock"
              class="library-editor-toolbar sticky top-2 z-20 mb-4 flex flex-wrap items-center justify-between gap-2"
              role="toolbar"
              aria-label="أدوات المحرر الموحد"
            >
              <div class="flex flex-wrap items-center gap-1">
                <span class="px-1 font-mono text-[10px] text-cyan-400">{{
                  String(activeBlockIndex + 1).padStart(2, '0')
                }}</span>
                <select
                  v-model="activeEditorBlock.type"
                  class="form-input focus-ring rounded-md border-slate-700 bg-slate-900 py-1 ps-2 pe-7 text-xs text-slate-200"
                  aria-label="نوع الكتلة المركزة"
                >
                  <option
                    v-for="definition in blockRegistry"
                    :key="definition.type"
                    :value="definition.type"
                  >
                    {{ definition.label_ar }}
                  </option>
                </select>
                <button
                  type="button"
                  class="editor-tool"
                  :disabled="!canMoveBlock(activeBlockIndex, -1)"
                  title="تحريك القسم لأعلى"
                  @click="moveBlock(activeBlockIndex, -1)"
                >
                  ↑
                </button>
                <button
                  type="button"
                  class="editor-tool"
                  :disabled="!canMoveBlock(activeBlockIndex, 1)"
                  title="تحريك القسم لأسفل"
                  @click="moveBlock(activeBlockIndex, 1)"
                >
                  ↓
                </button>
                <button
                  type="button"
                  class="editor-tool"
                  :disabled="!canIndentBlock(activeBlockIndex)"
                  title="زيادة عمق القسم"
                  @click="indentBlock(activeBlockIndex)"
                >
                  ⇥
                </button>
                <button
                  type="button"
                  class="editor-tool"
                  :disabled="!canOutdentBlock(activeBlockIndex)"
                  title="تقليل عمق القسم"
                  @click="outdentBlock(activeBlockIndex)"
                >
                  ⇤
                </button>
              </div>
              <div class="flex flex-wrap items-center gap-1">
                <button
                  type="button"
                  class="editor-tool font-bold"
                  title="خط عريض"
                  @click="replaceSelection(activeBlockIndex, '**', '**', 'نص بارز')"
                >
                  B
                </button>
                <button
                  type="button"
                  class="editor-tool italic"
                  title="خط مائل"
                  @click="replaceSelection(activeBlockIndex, '_', '_', 'نص مائل')"
                >
                  I
                </button>
                <button
                  type="button"
                  class="editor-tool font-mono"
                  title="رمز ضمن السطر"
                  @click="replaceSelection(activeBlockIndex, '`', '`', 'inline_code')"
                >
                  &lt;/&gt;
                </button>
                <button
                  type="button"
                  class="editor-tool"
                  title="إدراج رابط مرجعي"
                  @click="insertLink(activeBlockIndex)"
                >
                  🔗
                </button>
                <button
                  type="button"
                  class="editor-tool"
                  title="إدراج استشهاد"
                  @click="insertReference(activeBlockIndex)"
                >
                  〔+〕
                </button>
                <button type="button" class="editor-tool" title="إضافة كتلة" @click="addBlock">
                  ＋
                </button>
                <button
                  type="button"
                  class="editor-tool text-rose-300"
                  :disabled="form.blocks.length <= 1"
                  title="حذف القسم"
                  @click="removeBlock(activeBlockIndex)"
                >
                  حذف
                </button>
              </div>
            </div>
            <article
              v-for="(block, index) in form.blocks"
              v-show="isEditorBlockVisible(index)"
              :key="revisionKey + ':' + block.id"
              class="library-editor-block group relative border-s-2 px-2 py-2 transition sm:px-4"
              :class="[
                index === activeBlockIndex
                  ? 'border-s-cyan-400 bg-cyan-950/10'
                  : 'border-s-transparent',
                block.type === 'callout'
                  ? 'my-3 rounded-e-xl bg-cyan-950/20 py-4'
                  : block.type === 'rules'
                    ? 'my-3 rounded-e-xl bg-indigo-950/20 py-4'
                    : block.type === 'boundaries'
                      ? 'my-3 rounded-e-xl bg-amber-950/20 py-4'
                      : technicalTypes.has(block.type)
                        ? 'my-3 rounded-xl bg-[#050911] py-3'
                        : '',
              ]"
              :style="{ marginInlineStart: block.depth * 0.8 + 'rem' }"
              @click="activeBlockIndex = index"
            >
              <div class="mb-1 flex items-center justify-between gap-2">
                <span class="font-mono text-[9px] text-slate-600"
                  >{{ String(index + 1).padStart(2, '0') }} ·
                  {{ blockRegistry.find((item) => item.type === block.type)?.label_ar }}</span
                >
                <button
                  v-if="block.type === 'heading'"
                  type="button"
                  class="library-icon-button h-6 w-6"
                  :aria-expanded="!collapsedEditorSections.has(block.id)"
                  :aria-label="collapsedEditorSections.has(block.id) ? 'توسيع القسم' : 'طي القسم'"
                  @click.stop="toggleEditorSection(index)"
                >
                  {{ collapsedEditorSections.has(block.id) ? '◀' : '▼' }}
                </button>
              </div>
              <textarea
                :id="'knowledge-block-' + index"
                v-model="block.body"
                :rows="editorRows(block)"
                :dir="technicalTypes.has(block.type) ? 'ltr' : 'auto'"
                class="bidi-editor form-textarea focus-ring w-full resize-y border-0 bg-transparent p-1.5 text-sm text-slate-200 placeholder:text-slate-600 focus:ring-0"
                :class="
                  technicalTypes.has(block.type)
                    ? 'text-left font-mono leading-6 text-emerald-200'
                    : block.type === 'heading'
                      ? 'text-lg leading-8 font-black'
                      : 'leading-8'
                "
                placeholder="اكتب محتوى القسم…"
                :aria-label="'محتوى القسم ' + (index + 1)"
                @focus="activeBlockIndex = index"
              />
            </article>
          </form>
          <LessonContentRenderer v-else :blocks="displayedBlocks" :contract="content_contract" />
          <div v-if="!active.revision" class="grid min-h-72 place-items-center text-center">
            <div>
              <p class="text-sm font-bold text-slate-300">لا توجد مراجعة محتوى لهذا الكائن.</p>
              <p class="mt-2 text-xs text-slate-500">تظهر الوثيقة بعد إنشاء مراجعة قانونية.</p>
            </div>
          </div>
        </div>
        <footer class="library-document-footer">
          <span>عقد المحتوى</span
          ><bdi dir="ltr" class="font-mono text-cyan-300">{{ content_contract.version }}</bdi
          ><span class="ms-auto">المعرّفات التقنية معزولة باتجاه LTR</span>
        </footer>
      </article>

      <div v-else class="grid min-h-[34rem] place-items-center text-center">
        <div>
          <div
            class="mx-auto grid h-14 w-14 place-items-center rounded-2xl border border-slate-700 bg-slate-900 text-2xl"
          >
            ∅
          </div>
          <h1 class="mt-4 text-xl font-bold text-slate-300">المكتبة فارغة</h1>
          <p class="mt-2 text-xs text-slate-500">لا توجد وحدات معرفية قانونية مؤهلة للعرض.</p>
        </div>
      </div>
    </div>

    <template #right>
      <div dir="rtl" class="library-context min-h-0">
        <div class="flex items-center justify-between gap-2 border-b border-slate-800/80 pb-3">
          <div>
            <h2 class="text-sm font-black text-slate-100">السياق</h2>
            <p class="mt-0.5 text-[10px] text-slate-500">حقائق مرتبطة بالاختيار الحالي</p>
          </div>
          <span class="h-2 w-2 rounded-full bg-cyan-400" aria-hidden="true" />
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2">
          <div class="library-stat">
            <strong>{{ context.sources.length }}</strong
            ><span>مصادر</span>
          </div>
          <div class="library-stat">
            <strong>{{ resolvedClaimCount }}</strong
            ><span>ادعاءات مسندة</span>
          </div>
          <div class="library-stat">
            <strong>{{ context.placements.length }}</strong
            ><span>مواضع</span>
          </div>
          <div class="library-stat">
            <strong>{{ revisionTimeline.length }}</strong
            ><span>مراجعات</span>
          </div>
        </div>
        <div class="mt-4 flex rounded-lg border border-slate-800 bg-slate-950/70 p-1">
          <button
            v-for="item in lenses"
            :key="item"
            type="button"
            class="focus-ring flex-1 rounded-md px-2 py-1.5 text-xs font-bold transition"
            :class="
              lens === item ? 'bg-slate-800 text-cyan-200' : 'text-slate-500 hover:text-slate-200'
            "
            @click="setLens(item)"
          >
            {{ item === 'overview' ? 'نظرة عامة' : 'المصادر' }}
          </button>
        </div>
        <div v-if="lens === 'overview'" class="mt-4 space-y-3 text-xs">
          <section class="library-context-card">
            <h3>سلطة المراجعة</h3>
            <bdi
              v-if="active?.revision?.authority_baseline_id"
              dir="ltr"
              class="mt-2 block font-mono text-[10px] break-all text-cyan-300"
              >{{ active.revision.authority_baseline_id }}</bdi
            >
            <p v-else class="mt-2 text-slate-500">لا توجد سلطة مراجعة مسجلة.</p>
          </section>
          <section class="library-context-card">
            <h3>موضع المنهج</h3>
            <p dir="auto" class="mt-2 font-semibold text-cyan-200">{{ placementContext.label }}</p>
            <p v-if="placementContext.detail" dir="auto" class="mt-1 text-[10px] text-slate-500">
              {{ placementContext.detail }}
            </p>
          </section>
          <section class="library-context-card">
            <h3>سلامة الاستشهادات</h3>
            <p class="mt-2 flex items-center justify-between gap-2 text-slate-300">
              <span>مراجع غير محلولة</span
              ><bdi
                dir="ltr"
                class="font-mono font-bold"
                :class="context.unresolved_citation_count ? 'text-amber-300' : 'text-emerald-300'"
                >{{ context.unresolved_citation_count }}</bdi
              >
            </p>
          </section>
        </div>
        <div v-else class="mt-4 space-y-2">
          <article v-for="source in context.sources" :key="source.id" class="library-source-card">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <h3 dir="auto" class="bidi-editor text-xs font-bold text-slate-200">
                  {{ source.title }}
                </h3>
                <bdi dir="ltr" class="mt-1 block truncate font-mono text-[9px] text-slate-500">{{
                  source.id
                }}</bdi>
              </div>
              <a
                v-if="source.href && safeHttpsUrl(source.href)"
                :href="source.href"
                target="_blank"
                rel="noopener noreferrer"
                class="library-icon-button"
                :aria-label="'فتح المصدر ' + source.title"
                >↗</a
              >
            </div>
            <div class="mt-2 flex flex-wrap gap-1">
              <span class="library-source-chip">{{
                source.authority_class || 'سلطة غير متاحة'
              }}</span
              ><span class="library-source-chip">{{
                source.review_status || 'حالة غير متاحة'
              }}</span>
            </div>
            <div
              v-if="source.claims.length"
              class="mt-3 space-y-1.5 border-t border-slate-800 pt-2"
            >
              <div
                v-for="claim in source.claims"
                :key="claim.claim_id"
                class="flex items-start justify-between gap-2 text-[10px]"
              >
                <bdi dir="ltr" class="font-mono text-cyan-300">{{ claim.claim_id }}</bdi
                ><span dir="auto" class="bidi-editor text-slate-500">{{ claim.assessment }}</span>
              </div>
            </div>
          </article>
          <p
            v-if="!context.sources.length"
            class="rounded-xl border border-dashed border-slate-800 p-5 text-center text-xs text-slate-500"
          >
            لا توجد مصادر مسندة لهذه المراجعة.
          </p>
        </div>
        <section
          v-if="active?.revision?.editable && form.citations.length"
          class="mt-4 border-t border-slate-800 pt-4"
        >
          <h3 class="text-xs font-bold text-slate-400">استشهادات المسودة</h3>
          <div class="mt-2 flex flex-wrap gap-1.5">
            <span
              v-for="citation in form.citations"
              :key="citation"
              class="inline-flex items-center gap-1 rounded-md bg-slate-800 px-2 py-1 font-mono text-[9px] text-cyan-300"
            >
              <bdi dir="ltr">{{ citation }}</bdi
              ><button
                v-if="form.citations.length > 1"
                type="button"
                class="hover:text-rose-300"
                :aria-label="'حذف استشهاد ' + citation"
                @click="removeCitation(citation)"
              >
                ×
              </button>
            </span>
          </div>
        </section>
      </div>
    </template>

    <template #bottom>
      <div dir="rtl" class="kl-library-route">
        <div class="mb-4 flex flex-wrap items-center gap-2 border-b border-slate-800 pb-3">
          <button
            type="button"
            class="library-command"
            :class="shelfTab === 'history' ? 'library-command--active' : ''"
            @click="shelfTab = 'history'"
          >
            سجل المراجعات
          </button>
          <button
            type="button"
            class="library-command"
            :class="shelfTab === 'compare' ? 'library-command--active' : ''"
            @click="shelfTab = 'compare'"
          >
            المقارنة
          </button>
          <button
            type="button"
            class="library-command"
            :class="shelfTab === 'diagnostics' ? 'library-command--active' : ''"
            @click="shelfTab = 'diagnostics'"
          >
            الحفظ والاسترداد
          </button>
          <span class="ms-auto text-[10px] text-slate-500"
            >هذه المساحة تشخيصية؛ لا تعدّل التاريخ المنشور.</span
          >
        </div>

        <section v-if="shelfTab === 'history'" aria-label="سجل المراجعات">
          <div class="mb-3">
            <h3 class="text-sm font-bold text-slate-200">سجل المراجعات القانوني</h3>
            <p class="mt-1 text-xs text-slate-500">
              افحص أي مراجعة أو استخدمها للمقارنة. الاستعادة تنشئ مسودة جديدة فقط.
            </p>
          </div>
          <ol v-if="revisionTimeline.length" class="grid gap-2 lg:grid-cols-2">
            <li
              v-for="revision in revisionTimeline"
              :key="revision.id"
              class="rounded-xl border border-slate-800 bg-slate-900/60 p-3"
            >
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <div class="flex items-center gap-2">
                    <strong class="text-xs text-slate-200">مراجعة {{ revision.revision }}</strong
                    ><span
                      class="rounded-full border border-slate-700 px-2 py-0.5 text-[9px] text-slate-400"
                      >{{ revisionStateLabel(revision.state) }}</span
                    ><span
                      v-if="revision.id === active?.revision?.id"
                      class="rounded-full bg-cyan-950 px-2 py-0.5 text-[9px] text-cyan-300"
                      >الحالية</span
                    >
                  </div>
                  <bdi dir="ltr" class="mt-1 block font-mono text-[9px] text-slate-600">{{
                    revision.id
                  }}</bdi>
                  <bdi
                    v-if="revision.updated_at || revision.published_at"
                    dir="ltr"
                    class="mt-1 block text-[9px] text-slate-500"
                    >{{ (revision.updated_at ?? revision.published_at)?.slice(0, 19) }}</bdi
                  >
                </div>
                <div class="flex flex-wrap gap-1.5">
                  <Link
                    :href="
                      '/knowledge?object=' +
                      encodeURIComponent(active?.id ?? '') +
                      '&revision=' +
                      encodeURIComponent(revision.id)
                    "
                    class="library-command"
                    >فحص</Link
                  >
                  <button
                    v-if="revision.id !== active?.revision?.id"
                    type="button"
                    class="library-command"
                    @click="prepareComparison(revision.id)"
                  >
                    قارن
                  </button>
                  <button
                    v-if="revision.state === 'published'"
                    type="button"
                    class="library-command text-cyan-200"
                    @click="restoreRevision(revision.id, revision.state)"
                  >
                    استعادة كمسودة
                  </button>
                </div>
              </div>
              <p v-if="revision.derived_from_revision_id" class="mt-2 text-[10px] text-slate-500">
                مشتقة من
                <bdi dir="ltr" class="font-mono">{{ revision.derived_from_revision_id }}</bdi>
              </p>
            </li>
          </ol>
          <p v-else class="py-8 text-center text-xs text-slate-500">لا توجد مراجعات بعد.</p>
        </section>

        <section v-else-if="shelfTab === 'compare'" aria-label="مقارنة المراجعات">
          <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
              <h3 class="text-sm font-bold text-slate-200">مقارنة بنيوية بالهوية المستقرة</h3>
              <p class="mt-1 text-xs text-slate-500">
                تميّز الحركة والتعديل والإضافة والحذف، مع توافق محدود للمراجعات القديمة.
              </p>
            </div>
            <div class="flex items-center gap-2">
              <select
                v-model="compareRevisionId"
                class="form-input focus-ring rounded-lg border-slate-700 bg-slate-900 text-xs text-slate-200"
                aria-label="اختر مراجعة للمقارنة"
              >
                <option value="">اختر مراجعة تاريخية…</option>
                <option
                  v-for="revision in historicalRevisions"
                  :key="revision.id"
                  :value="revision.id"
                >
                  مراجعة {{ revision.revision }} — {{ revisionStateLabel(revision.state) }}
                </option>
              </select>
              <button
                type="button"
                class="focus-ring rounded-lg bg-cyan-400 px-3 py-2 text-xs font-bold text-slate-950 disabled:opacity-40"
                :disabled="!compareRevisionId || compareLoading"
                @click="loadComparison"
              >
                {{ compareLoading ? 'جارٍ التحميل…' : 'قارن' }}
              </button>
            </div>
          </div>
          <p
            v-if="compareError"
            role="alert"
            class="mt-3 rounded-lg border border-rose-800 bg-rose-950/40 px-3 py-2 text-xs text-rose-200"
          >
            {{ compareError }}
          </p>
          <div v-if="compareOpen && active?.revision && compareRevision" class="mt-4">
            <div class="mb-3 grid gap-2 md:grid-cols-2">
              <div
                class="rounded-lg border border-cyan-800 bg-cyan-950/30 px-3 py-2 text-xs text-cyan-200"
              >
                الحالية: مراجعة {{ active.revision.revision }}
              </div>
              <div
                class="rounded-lg border border-indigo-800 bg-indigo-950/30 px-3 py-2 text-xs text-indigo-200"
              >
                المقارنة: مراجعة {{ compareRevision.revision }}
              </div>
            </div>
            <div class="max-h-[32rem] space-y-2 overflow-y-auto pe-1">
              <article
                v-for="row in comparisonRows"
                :key="row.id"
                class="rounded-xl border border-slate-800 bg-slate-950/45 p-3"
              >
                <div class="mb-2 flex items-center justify-between gap-2">
                  <span
                    class="rounded-full px-2 py-0.5 text-[9px] font-bold"
                    :class="{
                      'bg-slate-800 text-slate-400': row.state === 'unchanged',
                      'bg-amber-950 text-amber-300': row.state === 'modified',
                      'bg-cyan-950 text-cyan-300': row.state === 'moved',
                      'bg-emerald-950 text-emerald-300': row.state === 'added',
                      'bg-rose-950 text-rose-300': row.state === 'removed',
                    }"
                    >{{ comparisonStateLabel(row.state) }}</span
                  >
                  <bdi dir="ltr" class="font-mono text-[9px] text-slate-600">{{ row.id }}</bdi>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                  <div class="min-w-0 rounded-lg border border-slate-800 bg-slate-900/60 p-3">
                    <span class="text-[9px] font-bold text-cyan-400">الحالية</span
                    ><template v-if="row.current"
                      ><div class="mt-1 flex gap-2 font-mono text-[9px] text-slate-500">
                        <span>{{ row.current.type }}</span
                        ><span>depth {{ row.current.depth }}</span>
                      </div>
                      <p
                        dir="auto"
                        class="bidi-editor mt-2 text-xs leading-6 whitespace-pre-wrap text-slate-300"
                      >
                        {{ row.current.body }}
                      </p></template
                    >
                    <p v-else class="mt-2 text-xs text-slate-600">لا توجد كتلة مقابلة.</p>
                  </div>
                  <div class="min-w-0 rounded-lg border border-slate-800 bg-slate-900/60 p-3">
                    <span class="text-[9px] font-bold text-indigo-400">المقارنة</span
                    ><template v-if="row.compared"
                      ><div class="mt-1 flex gap-2 font-mono text-[9px] text-slate-500">
                        <span>{{ row.compared.type }}</span
                        ><span>depth {{ row.compared.depth }}</span>
                      </div>
                      <p
                        dir="auto"
                        class="bidi-editor mt-2 text-xs leading-6 whitespace-pre-wrap text-slate-300"
                      >
                        {{ row.compared.body }}
                      </p></template
                    >
                    <p v-else class="mt-2 text-xs text-slate-600">لا توجد كتلة مقابلة.</p>
                  </div>
                </div>
              </article>
            </div>
          </div>
          <p v-else class="py-8 text-center text-xs text-slate-500">
            اختر مراجعة تاريخية لبدء المقارنة.
          </p>
        </section>

        <section v-else class="space-y-3 text-xs" aria-label="تفاصيل الحفظ والاسترداد">
          <h3 class="text-sm font-bold text-slate-200">تشخيص الحفظ والاسترداد المحلي</h3>
          <div class="grid gap-3 md:grid-cols-3">
            <div class="library-context-card">
              <h3>حالة جلسة التحرير</h3>
              <p class="mt-2 text-slate-300">{{ autosaveLabel }}</p>
            </div>
            <div class="library-context-card">
              <h3>نسخة الاسترداد المحلية</h3>
              <bdi v-if="recoverySavedAt" dir="ltr" class="mt-2 block font-mono text-cyan-300">{{
                recoverySavedAt
              }}</bdi>
              <p v-else class="mt-2 text-slate-500">لا توجد نسخة محلية أحدث.</p>
            </div>
            <div class="library-context-card">
              <h3>قفل المراجعة</h3>
              <bdi dir="ltr" class="mt-2 block font-mono text-cyan-300">{{
                active?.revision?.lock_version ?? 'غير متاح'
              }}</bdi>
            </div>
          </div>
          <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
            <span class="text-[10px] text-slate-500">Content Digest</span
            ><bdi dir="ltr" class="mt-1 block font-mono text-[10px] break-all text-slate-300">{{
              active?.revision?.content_digest || 'غير متاح'
            }}</bdi>
          </div>
        </section>
      </div>
    </template>
  </CepWorkspaceLayout>
</template>

<style>
.kl-library-center {
  color: rgb(226 232 240);
}

.library-command {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  border: 1px solid rgb(51 65 85 / 0.9);
  border-radius: 0.5rem;
  background: rgb(15 23 42 / 0.72);
  padding: 0.42rem 0.68rem;
  color: rgb(203 213 225);
  font-size: 0.7rem;
  font-weight: 700;
  line-height: 1rem;
  transition: 150ms ease;
}

.library-command:hover:not(:disabled),
.library-command--active {
  border-color: rgb(8 145 178 / 0.75);
  background: rgb(8 51 68 / 0.72);
  color: rgb(165 243 252);
}

.library-command:disabled {
  cursor: not-allowed;
  opacity: 0.38;
}

.library-command:focus-visible,
.library-icon-button:focus-visible {
  outline: 2px solid rgb(34 211 238);
  outline-offset: 2px;
}

.library-command-count {
  min-width: 1.25rem;
  border-radius: 999px;
  background: rgb(30 41 59);
  padding-inline: 0.35rem;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 0.62rem;
  text-align: center;
}

.library-save-state {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding-inline: 0.35rem;
  color: rgb(148 163 184);
  font-size: 0.68rem;
  white-space: nowrap;
}

.library-document {
  overflow: hidden;
  border: 1px solid rgb(30 41 59 / 0.92);
  border-radius: 1rem;
  background: linear-gradient(180deg, rgb(8 19 35 / 0.98), rgb(7 15 28 / 0.98)), rgb(8 19 35);
  box-shadow: 0 20px 55px rgb(2 8 23 / 0.26);
}

.library-document-header {
  border-bottom: 1px solid rgb(30 41 59 / 0.9);
  padding: clamp(1rem, 2.3vw, 1.6rem);
  background: linear-gradient(135deg, rgb(10 25 45 / 0.95), rgb(7 17 31 / 0.65));
}

.library-document-body {
  min-height: 32rem;
  padding: clamp(1rem, 2.5vw, 2rem);
}

.library-document-footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.55rem;
  border-top: 1px solid rgb(30 41 59 / 0.86);
  padding: 0.75rem 1rem;
  color: rgb(100 116 139);
  font-size: 0.62rem;
}

.library-metadata-chip,
.library-source-chip {
  border: 1px solid rgb(51 65 85 / 0.82);
  border-radius: 999px;
  background: rgb(15 23 42 / 0.72);
  padding: 0.28rem 0.55rem;
  color: rgb(148 163 184);
  font-size: 0.62rem;
  white-space: nowrap;
}

.library-editor-toolbar {
  border: 1px solid rgb(51 65 85 / 0.92);
  border-radius: 0.75rem;
  background: rgb(4 11 23 / 0.96);
  padding: 0.5rem;
  box-shadow: 0 14px 34px rgb(2 8 23 / 0.45);
  backdrop-filter: blur(12px);
}

.library-editor-block {
  scroll-margin-top: 5rem;
}

.library-icon-button {
  display: inline-grid;
  width: 1.9rem;
  height: 1.9rem;
  flex: 0 0 auto;
  place-items: center;
  border: 1px solid rgb(51 65 85 / 0.8);
  border-radius: 0.45rem;
  color: rgb(148 163 184);
  font-size: 0.72rem;
  transition: 150ms ease;
}

.library-icon-button:hover {
  border-color: rgb(8 145 178 / 0.7);
  background: rgb(8 51 68 / 0.55);
  color: rgb(165 243 252);
}

.library-stat {
  display: grid;
  gap: 0.18rem;
  border: 1px solid rgb(30 41 59 / 0.9);
  border-radius: 0.65rem;
  background: rgb(2 8 23 / 0.42);
  padding: 0.7rem;
}

.library-stat strong {
  color: rgb(165 243 252);
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 1rem;
}

.library-stat span {
  color: rgb(100 116 139);
  font-size: 0.62rem;
}

.library-context-card,
.library-source-card {
  border: 1px solid rgb(30 41 59 / 0.9);
  border-radius: 0.75rem;
  background: rgb(2 8 23 / 0.42);
  padding: 0.75rem;
}

.library-context-card > h3 {
  color: rgb(148 163 184);
  font-size: 0.68rem;
  font-weight: 800;
}

.kl-library-route .editor-tool {
  border-radius: 0.375rem;
  padding: 0.3rem 0.48rem;
  color: rgb(148 163 184);
  font-size: 0.75rem;
  line-height: 1rem;
}

.kl-library-route .editor-tool:hover:not(:disabled) {
  background: rgb(30 41 59);
  color: rgb(226 232 240);
}

.kl-library-route .editor-tool:disabled {
  opacity: 0.3;
}

.kl-library-route .bidi-editor {
  unicode-bidi: plaintext;
  text-align: start;
}

@media (max-width: 79.99rem) {
  .library-top-actions {
    gap: 0.38rem;
  }

  .library-command {
    padding-inline: 0.5rem;
  }

  .library-document-body {
    padding-inline: 0.85rem;
  }
}

[data-theme='light'] .kl-library-route [class*='bg-slate-950'],
[data-theme='light'] .kl-library-route [class*='bg-slate-900'],
[data-theme='light'] .kl-library-route [class*='bg-slate-800'],
[data-theme='light'] .kl-library-route [class*='bg-[#0b1322]'],
[data-theme='light'] .kl-library-route [class*='bg-[#050911]'] {
  background-color: var(--cep-bg-panel-strong) !important;
}

[data-theme='light'] .kl-library-route [class*='text-slate-100'],
[data-theme='light'] .kl-library-route [class*='text-slate-200'],
[data-theme='light'] .kl-library-route [class*='text-slate-300'] {
  color: var(--cep-text) !important;
}

[data-theme='light'] .kl-library-route [class*='text-slate-400'],
[data-theme='light'] .kl-library-route [class*='text-slate-500'],
[data-theme='light'] .kl-library-route [class*='text-slate-600'] {
  color: var(--cep-text-muted) !important;
}

[data-theme='light'] .kl-library-route [class*='border-slate-700'],
[data-theme='light'] .kl-library-route [class*='border-slate-800'] {
  border-color: var(--cep-border) !important;
}

[data-theme='light'] .kl-library-route [class*='text-cyan-100'],
[data-theme='light'] .kl-library-route [class*='text-cyan-200'],
[data-theme='light'] .kl-library-route [class*='text-cyan-300'],
[data-theme='light'] .kl-library-route [class*='text-cyan-400'] {
  color: var(--cep-accent) !important;
}

[data-theme='light'] .kl-library-route [class*='text-amber-300'],
[data-theme='light'] .kl-library-route [class*='text-amber-400'] {
  color: #b45309 !important;
}
</style>

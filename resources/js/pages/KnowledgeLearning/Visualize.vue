<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import CepWorkspaceLayout from '../../layouts/CepWorkspaceLayout.vue';
import KnowledgeTabs from './components/KnowledgeTabs.vue';
import OverlayPanel from './components/visualize/OverlayPanel.vue';
import VisualizationSurface from './components/visualize/VisualizationSurface.vue';
import { parseVisualizeLocation, serializeVisualizeState, selectionToken } from './components/visualize/routeState';
import type {
  GraphState,
  MapState,
  OverlayName,
  OverlayState,
  ViewMode,
  VisualFilter,
  VisualPoint,
  VisualSelection,
  VisualizeRouteState,
} from './components/visualize/types';
import {
  edgeSupportsView,
  filteredProjection,
  selectionExists,
} from './components/visualize/viewModels';

type CatalogItem = { id: string; title_ar: string; title_en: string };

const props = defineProps<{
  catalog: CatalogItem[];
  active: { id: string; title_ar: string; title_en: string } | null;
  map: MapState;
  view: { implemented: string[]; not_implemented: string[]; default?: string };
  overlay: OverlayState;
  graph: GraphState;
  state?: VisualizeRouteState;
}>();

const supportedViews: ViewMode[] = ['Tree', 'Path', 'Graph', 'Canvas'];
const views = computed<ViewMode[]>(() =>
  supportedViews.filter((viewName) => props.view.implemented.includes(viewName)),
);
const defaultState = computed<VisualizeRouteState>(() => ({
  map: props.map.saved ? props.map.id : null,
  view:
    props.state?.view ??
    (views.value.includes(props.map.default_view ?? 'Tree')
      ? (props.map.default_view ?? 'Tree')
      : 'Tree'),
  overlay: props.state?.overlay ?? null,
  filter: props.state?.filter ?? 'all',
  selection: props.state?.selection ?? null,
  notice: props.state?.notice ?? null,
}));

const activeView = ref<ViewMode>(defaultState.value.view);
const activeMap = ref<string | null>(defaultState.value.map);
const selectedOverlay = ref<OverlayName | null>(defaultState.value.overlay);
const activeFilter = ref<VisualFilter>(defaultState.value.filter);
const selection = ref<VisualSelection | null>(defaultState.value.selection);
const notice = ref<string | null>(defaultState.value.notice ?? null);
const contextOpen = ref(false);
const cameraPercent = ref(100);
const surface = ref<{
  zoomIn: () => void;
  zoomOut: () => void;
  fit: () => void;
} | null>(null);
const visualPositions = ref<Record<string, VisualPoint>>({ ...(props.map.visual_positions ?? {}) });

const projection = computed(() =>
  filteredProjection(props.graph.nodes, props.graph.edges, activeFilter.value),
);
const viewEdges = computed(() =>
  projection.value.edges.filter((edge) => edgeSupportsView(edge, activeView.value)),
);
const selectedLayer = computed(() =>
  selectedOverlay.value ? (props.overlay.layers?.[selectedOverlay.value] ?? null) : null,
);
const selectedNode = computed(() =>
  selection.value?.kind === 'node'
    ? (props.graph.nodes.find((node) => node.id === selection.value?.id) ?? null)
    : null,
);
const selectedEdge = computed(() =>
  selection.value?.kind === 'edge'
    ? (props.graph.edges.find((edge) => edge.id === selection.value?.id) ?? null)
    : null,
);
const selectedEdgeEndpoints = computed(() => {
  if (!selectedEdge.value) return null;
  return {
    from: props.graph.nodes.find((node) => node.id === selectedEdge.value?.from) ?? null,
    to: props.graph.nodes.find((node) => node.id === selectedEdge.value?.to) ?? null,
  };
});
const selectedRelationships = computed(() => {
  if (!selectedNode.value) return { inbound: 0, outbound: 0 };
  return props.graph.edges.reduce(
    (counts, edge) => ({
      inbound: counts.inbound + (edge.to === selectedNode.value?.id ? 1 : 0),
      outbound: counts.outbound + (edge.from === selectedNode.value?.id ? 1 : 0),
    }),
    { inbound: 0, outbound: 0 },
  );
});
const memberUnits = computed(() =>
  props.graph.nodes.filter((node) => node.kind === 'knowledge_unit'),
);
const mapScope = computed(() => props.map.scope?.id ?? props.active?.id ?? null);
const routeState = computed<VisualizeRouteState>(() => ({
  map: activeMap.value,
  view: activeView.value,
  overlay: selectedOverlay.value,
  filter: activeFilter.value,
  selection: selection.value,
  notice: notice.value,
}));
const spatialControlsAvailable = computed(
  () => activeView.value === 'Graph' || activeView.value === 'Canvas',
);

const syncHistory = (mode: 'push' | 'replace' = 'push') => {
  if (typeof window === 'undefined') return;
  const href = serializeVisualizeState(props.active?.id ?? null, routeState.value);
  const token = selectionToken(routeState.value.selection);
  window.history[mode === 'push' ? 'pushState' : 'replaceState'](
    { selection: token },
    '',
    href,
  );
};
const reconcileSelection = () => {
  if (!selectionExists(selection.value, projection.value.nodes, projection.value.edges)) {
    selection.value = null;
    notice.value = 'أُزيل التحديد لأنه لم يعد مرئيًا أو صالحًا في طريقة العرض الحالية.';
  }
};
const normalizeOverlayForView = () => {
  if (!selectedOverlay.value) return;
  const layer = props.overlay.layers?.[selectedOverlay.value];
  if (!layer?.available || !layer.supported_views.includes(activeView.value)) {
    selectedOverlay.value = null;
    notice.value = 'أُوقفت الطبقة لأنها لا تملك تمثيلًا دلاليًا في طريقة العرض الحالية.';
  }
};
const selectView = (viewName: ViewMode) => {
  if (!views.value.includes(viewName) || activeView.value === viewName) return;
  activeView.value = viewName;
  cameraPercent.value = 100;
  normalizeOverlayForView();
  reconcileSelection();
  syncHistory();
};
const selectOverlay = (overlay: OverlayName | null) => {
  selectedOverlay.value = overlay;
  notice.value = null;
  syncHistory();
};
const selectFilter = (filter: VisualFilter) => {
  if (activeFilter.value === filter) return;
  activeFilter.value = filter;
  reconcileSelection();
  syncHistory();
};
const selectTarget = (next: VisualSelection) => {
  selection.value =
    selection.value?.kind === next.kind && selection.value.id === next.id ? null : next;
  notice.value = null;
  if (
    selection.value &&
    typeof window !== 'undefined' &&
    window.matchMedia('(max-width: 1279px)').matches
  ) {
    contextOpen.value = true;
  }
  syncHistory();
};
const moveNode = (payload: {
  id: string;
  x: number;
  y: number;
  method: 'pointer' | 'keyboard';
}) => {
  visualPositions.value = {
    ...visualPositions.value,
    [payload.id]: { x: payload.x, y: payload.y },
  };
  notice.value =
    payload.method === 'keyboard'
      ? 'تم تحديث موضع العرض بلوحة المفاتيح داخل الجلسة فقط.'
      : 'تم تحديث موضع العرض داخل الجلسة فقط.';
};
const browseHref = (unitId: string) =>
  serializeVisualizeState(unitId, {
    ...routeState.value,
    map: null,
    selection: null,
  });
const restoreFromLocation = () => {
  const restored = parseVisualizeLocation(
    window.location.search,
    defaultState.value,
    props.graph.nodes,
    props.graph.edges,
    props.overlay,
  );
  activeView.value = restored.view;
  activeMap.value = restored.map;
  selectedOverlay.value = restored.overlay;
  activeFilter.value = restored.filter;
  selection.value = restored.selection;
  notice.value = restored.notice ?? null;
};

onMounted(() => {
  syncHistory('replace');
  window.addEventListener('popstate', restoreFromLocation);
});
onBeforeUnmount(() => window.removeEventListener('popstate', restoreFromLocation));
</script>

<template>
  <Head title="المعرفة والتعلّم — التصوّر" />
  <CepWorkspaceLayout active-destination="knowledge">
    <template #primaryNavigation>
      <KnowledgeTabs active="visualize" :object-id="active?.id" />
    </template>

    <div
      dir="rtl"
      class="kl-visualize-route min-h-full bg-[var(--cep-bg-canvas)] text-[var(--cep-text)]"
    >
      <div class="w-full px-0 py-3 sm:px-4 xl:px-5">
        <header class="mb-3 rounded-xl border border-slate-800/90 bg-[#081322]/95 p-3 shadow-xl">
          <div class="flex flex-wrap items-center gap-2">
            <div class="me-auto min-w-0">
              <p class="text-[9px] font-bold tracking-[0.2em] text-cyan-400" dir="ltr">VISUALIZE</p>
              <p dir="auto" class="truncate text-sm font-black text-slate-100">
                {{ active?.title_ar ?? 'لا يوجد عالم عرض نشط' }}
              </p>
            </div>

            <div
              class="flex max-w-full items-center gap-1 overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/80 p-1"
            >
              <button
                v-for="viewName in views"
                :key="viewName"
                type="button"
                class="focus-ring shrink-0 rounded-md px-3 py-1.5 text-[11px] font-bold transition"
                :class="
                  activeView === viewName
                    ? 'bg-cyan-500/20 text-cyan-100 ring-1 ring-cyan-500/45'
                    : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200'
                "
                :aria-pressed="activeView === viewName"
                @click="selectView(viewName)"
              >
                <bdi dir="ltr">{{ viewName }}</bdi>
              </button>
            </div>

            <div
              class="flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-950/80 p-1"
            >
              <button
                type="button"
                class="focus-ring rounded px-2 py-1 text-slate-400 enabled:hover:text-white disabled:opacity-35"
                aria-label="تصغير عالم العرض"
                :disabled="!spatialControlsAvailable"
                @click="surface?.zoomOut()"
              >
                −
              </button>
              <bdi dir="ltr" class="min-w-10 text-center font-mono text-[9px] text-slate-500">
                {{ cameraPercent }}%
              </bdi>
              <button
                type="button"
                class="focus-ring rounded px-2 py-1 text-slate-400 enabled:hover:text-white disabled:opacity-35"
                aria-label="تكبير عالم العرض"
                :disabled="!spatialControlsAvailable"
                @click="surface?.zoomIn()"
              >
                +
              </button>
              <button
                type="button"
                class="focus-ring rounded border-s border-slate-800 px-2 py-1 font-mono text-[9px] text-slate-400 enabled:hover:text-white disabled:opacity-35"
                aria-label="ملاءمة عالم العرض ضمن المساحة المتاحة"
                :disabled="!spatialControlsAvailable"
                @click="surface?.fit()"
              >
                FIT
              </button>
            </div>

            <button
              type="button"
              class="focus-ring rounded-lg border border-slate-700 px-3 py-2 text-[10px] font-bold text-slate-300 xl:hidden"
              :aria-expanded="contextOpen"
              @click="contextOpen = true"
            >
              السياق
            </button>
          </div>

          <div class="mt-3 flex flex-wrap items-start gap-3 border-t border-slate-800/80 pt-3">
            <div
              class="flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-950/65 p-1"
            >
              <button
                type="button"
                class="focus-ring rounded px-2 py-1 text-[10px]"
                :class="activeFilter === 'all' ? 'bg-slate-800 text-white' : 'text-slate-500'"
                @click="selectFilter('all')"
              >
                عالم العرض
              </button>
              <button
                type="button"
                class="focus-ring rounded px-2 py-1 text-[10px]"
                :class="
                  activeFilter === 'knowledge' ? 'bg-cyan-950 text-cyan-200' : 'text-slate-500'
                "
                @click="selectFilter('knowledge')"
              >
                وحدات المعرفة
              </button>
              <button
                type="button"
                class="focus-ring rounded px-2 py-1 text-[10px]"
                :class="
                  activeFilter === 'structure' ? 'bg-violet-950 text-violet-200' : 'text-slate-500'
                "
                @click="selectFilter('structure')"
              >
                الهيكل
              </button>
            </div>
            <div class="min-w-0 flex-1">
              <OverlayPanel
                mode="controls"
                :overlay="overlay"
                :selected="selectedOverlay"
                :current-view="activeView"
                @select="selectOverlay"
              />
            </div>
          </div>
          <p
            v-if="notice"
            role="status"
            class="mt-2 rounded-lg border border-amber-800/40 bg-amber-950/20 px-3 py-2 text-[10px] text-amber-200"
          >
            {{ notice }}
          </p>
        </header>

        <div
          dir="ltr"
          class="grid min-h-[720px] grid-cols-1 gap-3 md:grid-cols-[220px_minmax(0,1fr)] xl:grid-cols-[260px_minmax(0,1fr)_300px]"
        >
          <aside
            dir="rtl"
            class="order-2 min-w-0 rounded-xl border border-slate-800/90 bg-[#081322]/92 p-3 md:order-1 md:max-h-[760px] md:overflow-y-auto"
            aria-label="بنية عالم العرض والتنقل في المكتبة"
          >
            <section class="border-b border-slate-800/80 pb-3">
              <div class="flex items-center justify-between gap-2">
                <h2 class="text-xs font-black text-slate-100">عالم العرض الحالي</h2>
                <bdi dir="ltr" class="font-mono text-[9px] text-cyan-400">MAP</bdi>
              </div>
              <div
                class="mt-2 rounded-lg border border-slate-800 bg-slate-950/60 p-2.5 text-[10px]"
              >
                <p class="font-bold" :class="map.saved ? 'text-emerald-300' : 'text-amber-300'">
                  {{ map.state_label ?? (map.saved ? 'خريطة محفوظة' : 'عرض مشتق غير محفوظ') }}
                </p>
                <dl class="mt-2 space-y-1.5 text-slate-500">
                  <div class="flex items-center justify-between gap-2">
                    <dt>النطاق</dt>
                    <dd>
                      <bdi dir="ltr" class="font-mono text-slate-300">{{ mapScope ?? '—' }}</bdi>
                    </dd>
                  </div>
                  <div class="flex items-center justify-between gap-2">
                    <dt>عضوية الخريطة</dt>
                    <dd class="text-slate-300">{{ graph.nodes.length }} عقدة</dd>
                  </div>
                  <div class="flex items-center justify-between gap-2">
                    <dt>وحدات المعرفة</dt>
                    <dd class="text-slate-300">{{ memberUnits.length }}</dd>
                  </div>
                </dl>
              </div>
              <bdi dir="ltr" class="mt-2 block font-mono text-[8px] break-all text-slate-600">
                {{ map.world?.recipe ?? graph.recipe ?? 'bounded_curriculum_neighborhood_v1' }}
              </bdi>
            </section>

            <section class="py-3">
              <h2 class="text-[10px] font-bold text-slate-400">أعضاء عالم العرض</h2>
              <ul class="mt-2 space-y-1">
                <li
                  v-for="node in memberUnits"
                  :key="node.id"
                  class="rounded-lg border border-slate-800/70 bg-slate-950/40 px-2.5 py-2"
                >
                  <span dir="auto" class="block truncate text-[10px] font-bold text-slate-200">{{
                    node.label
                  }}</span>
                  <bdi dir="ltr" class="mt-0.5 block truncate font-mono text-[8px] text-slate-600">
                    {{ node.technical_label }}
                  </bdi>
                </li>
              </ul>
            </section>

            <section class="border-t border-slate-800/80 pt-3">
              <h2 class="text-[10px] font-bold text-slate-400">استكشاف المكتبة</h2>
              <p class="mt-1 text-[9px] leading-5 text-slate-600">
                عناصر للتنقل وليست أعضاءً ضمن الخريطة الحالية.
              </p>
              <ul class="mt-2 space-y-1">
                <li v-for="unit in catalog" :key="unit.id">
                  <Link
                    :href="browseHref(unit.id)"
                    class="focus-ring block rounded-lg border px-2.5 py-2 text-[10px] transition"
                    :class="
                      unit.id === active?.id
                        ? 'border-cyan-700/60 bg-cyan-950/30 text-cyan-100'
                        : 'border-transparent text-slate-400 hover:border-slate-800 hover:bg-slate-950/60'
                    "
                  >
                    <span dir="auto" class="block truncate font-semibold">{{ unit.title_ar }}</span>
                    <bdi
                      dir="ltr"
                      class="mt-0.5 block truncate font-mono text-[8px] text-slate-600"
                      >{{ unit.id }}</bdi
                    >
                  </Link>
                </li>
              </ul>
            </section>
          </aside>

          <main
            dir="rtl"
            class="order-1 min-w-0 rounded-xl border border-slate-800/90 bg-[#081322]/92 p-3 shadow-xl md:order-2"
            aria-label="مساحة التصوّر الرئيسية"
          >
            <div
              class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-slate-800/80 pb-3"
            >
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <bdi
                    dir="ltr"
                    class="rounded-full bg-cyan-950/80 px-2 py-1 font-mono text-[9px] font-bold text-cyan-300"
                  >
                    VIEW · {{ activeView }}
                  </bdi>
                  <span class="text-[9px] text-slate-500">
                    المجموعة الظاهرة: {{ projection.nodes.length }} عقدة · {{ viewEdges.length }}
                    علاقة مؤهلة
                  </span>
                </div>
                <bdi
                  v-if="active"
                  dir="ltr"
                  class="mt-1.5 block font-mono text-[9px] text-slate-600"
                  >{{ active.id }}</bdi
                >
              </div>
              <span
                v-if="selectedOverlay"
                class="rounded-full border border-emerald-500/40 bg-emerald-950/40 px-2 py-1 text-[9px] font-bold text-emerald-300"
              >
                طبقة تحليلية نشطة
              </span>
            </div>

            <VisualizationSurface
              v-if="projection.nodes.length"
              ref="surface"
              :view="activeView"
              :nodes="projection.nodes"
              :edges="projection.edges"
              :active-overlay="selectedOverlay"
              :overlay-layer="selectedLayer"
              :visual-positions="visualPositions"
              :selection="selection"
              @select="selectTarget"
              @move-node="moveNode"
              @camera-change="cameraPercent = $event"
            />
            <div
              v-else
              class="grid min-h-[560px] place-items-center rounded-xl border border-dashed border-slate-800 text-center"
            >
              <div>
                <p class="text-sm font-bold text-slate-300">لا توجد عقد ضمن المرشح الحالي.</p>
                <p class="mt-2 text-xs text-slate-500">لن يختلق السطح بيانات بديلة.</p>
              </div>
            </div>
          </main>

          <button
            v-if="contextOpen"
            type="button"
            class="fixed inset-0 z-30 bg-slate-950/65 xl:hidden"
            aria-label="إغلاق لوحة السياق"
            @click="contextOpen = false"
          />
          <aside
            dir="rtl"
            class="z-40 min-w-0 flex-col border border-slate-800/90 bg-[#081322] p-4 shadow-2xl xl:static xl:order-3 xl:flex xl:rounded-xl"
            :class="
              contextOpen
                ? 'fixed inset-y-3 right-3 flex w-[min(340px,calc(100vw-1.5rem))] overflow-y-auto rounded-xl'
                : 'hidden'
            "
            aria-label="السياق الفريد للتحديد الحالي"
          >
            <div class="flex items-center justify-between gap-2">
              <div>
                <p class="font-mono text-[9px] font-bold text-cyan-400" dir="ltr">CONTEXT</p>
                <h2 class="mt-1 text-sm font-black text-slate-100">سياق التحديد</h2>
              </div>
              <button
                type="button"
                class="focus-ring rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-400 xl:hidden"
                @click="contextOpen = false"
              >
                إغلاق
              </button>
            </div>

            <section
              v-if="selectedNode"
              class="mt-4 space-y-3 rounded-xl border border-cyan-500/25 bg-cyan-950/15 p-3 text-xs"
            >
              <span
                class="rounded-full bg-cyan-500/15 px-2 py-1 font-mono text-[9px] text-cyan-300"
              >
                {{ selectedNode.kind }}
              </span>
              <p dir="auto" class="font-bold text-slate-100">{{ selectedNode.label }}</p>
              <bdi dir="ltr" class="block font-mono text-[10px] break-all text-slate-400">{{
                selectedNode.technical_label
              }}</bdi>
              <dl class="grid grid-cols-2 gap-2 border-t border-cyan-500/20 pt-3 text-center">
                <div class="rounded-lg bg-slate-950/50 p-2">
                  <dt class="text-[9px] text-slate-500">واردة</dt>
                  <dd class="mt-1 font-mono text-cyan-300">{{ selectedRelationships.inbound }}</dd>
                </div>
                <div class="rounded-lg bg-slate-950/50 p-2">
                  <dt class="text-[9px] text-slate-500">صادرة</dt>
                  <dd class="mt-1 font-mono text-cyan-300">{{ selectedRelationships.outbound }}</dd>
                </div>
              </dl>
              <bdi dir="ltr" class="block font-mono text-[8px] break-all text-slate-600">{{
                selectedNode.provenance
              }}</bdi>
            </section>

            <section
              v-else-if="selectedEdge"
              class="mt-4 space-y-3 rounded-xl border border-violet-500/25 bg-violet-950/15 p-3 text-xs"
            >
              <div class="flex items-center justify-between gap-2">
                <div>
                  <h3 class="font-bold text-slate-100">سياق الرابط المعرفي المرجعي</h3>
                  <p class="mt-1 text-slate-300">رابط {{ selectedEdge.type }}</p>
                </div>
                <bdi
                  dir="ltr"
                  class="rounded-full bg-violet-500/15 px-2 py-1 font-mono text-[9px] text-violet-300"
                >
                  {{ selectedEdge.id }}
                </bdi>
              </div>
              <div class="space-y-2 border-t border-violet-500/20 pt-3">
                <div>
                  <span class="text-[9px] text-slate-500">من</span>
                  <p dir="auto" class="mt-1 font-bold text-slate-200">
                    {{ selectedEdgeEndpoints?.from?.label ?? selectedEdge.from }}
                  </p>
                  <bdi dir="ltr" class="font-mono text-[8px] text-slate-500">{{ selectedEdge.from }}</bdi>
                </div>
                <div>
                  <span class="text-[9px] text-slate-500">إلى</span>
                  <p dir="auto" class="mt-1 font-bold text-slate-200">
                    {{ selectedEdgeEndpoints?.to?.label ?? selectedEdge.to }}
                  </p>
                  <bdi dir="ltr" class="font-mono text-[8px] text-slate-500">{{ selectedEdge.to }}</bdi>
                </div>
              </div>
              <bdi dir="ltr" class="block font-mono text-[8px] break-all text-slate-600">{{
                selectedEdge.provenance
              }}</bdi>
            </section>

            <p
              v-else
              class="mt-4 rounded-xl border border-dashed border-slate-800 p-3 text-xs leading-6 text-slate-500"
            >
              لم يُحدّد رابط مرجعي. انقر على علاقة في الرسم البياني لعرض سياقها.
            </p>

            <div class="my-5 h-px bg-slate-800/80" />
            <OverlayPanel
              mode="context"
              :overlay="overlay"
              :selected="selectedOverlay"
              :current-view="activeView"
            />
          </aside>
        </div>

        <details class="mt-3 rounded-xl border border-slate-800/90 bg-[#081322]/92 px-4 py-3">
          <summary
            class="focus-ring flex cursor-pointer items-center justify-between rounded text-xs font-bold text-slate-300"
          >
            <span>أثر العلاقات القانونية — مساحة مؤقتة</span>
            <bdi dir="ltr" class="font-mono text-[9px] text-cyan-400"
              >{{ graph.edges.length }} EDGES</bdi
            >
          </summary>
          <div class="mt-3 max-h-52 space-y-1.5 overflow-y-auto">
            <bdi
              v-for="edge in graph.edges"
              :key="edge.id"
              dir="ltr"
              class="block rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 font-mono text-[9px] text-slate-500"
            >
              {{ edge.from }} → {{ edge.type }} → {{ edge.to }} · revision {{ edge.revision }}
            </bdi>
          </div>
        </details>
      </div>
    </div>
  </CepWorkspaceLayout>
</template>

<style>
[data-theme='light'] .kl-visualize-route [class*='bg-[#081322]'],
[data-theme='light'] .kl-visualize-route [class*='bg-[#07101d]'],
[data-theme='light'] .kl-visualize-route [class*='bg-[#050d18]'] {
  background-color: var(--cep-bg-panel-strong) !important;
}

[data-theme='light'] .kl-visualize-route [class*='text-slate-100'],
[data-theme='light'] .kl-visualize-route [class*='text-slate-200'],
[data-theme='light'] .kl-visualize-route [class*='text-slate-300'] {
  color: var(--cep-text) !important;
}

[data-theme='light'] .kl-visualize-route [class*='text-slate-400'],
[data-theme='light'] .kl-visualize-route [class*='text-slate-500'],
[data-theme='light'] .kl-visualize-route [class*='text-slate-600'] {
  color: var(--cep-text-muted) !important;
}
</style>

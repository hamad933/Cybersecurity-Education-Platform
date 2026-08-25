<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import KnowledgeTabs from './components/KnowledgeTabs.vue';
import OverlayPanel from './components/visualize/OverlayPanel.vue';
import VisualizationSurface from './components/visualize/VisualizationSurface.vue';
import type {
  MapState,
  OverlayName,
  OverlayState,
  ViewMode,
  VisualEdge,
  VisualNode,
} from './components/visualize/types';

type CatalogItem = { id: string; title_ar: string; title_en: string };

const props = defineProps<{
  catalog: CatalogItem[];
  active: { id: string; title_ar: string; title_en: string } | null;
  map: MapState;
  view: { implemented: string[]; not_implemented: string[] };
  overlay: OverlayState;
  graph: { nodes: VisualNode[]; edges: VisualEdge[]; source: string };
}>();

const supportedViews: ViewMode[] = ['Tree', 'Path', 'Graph', 'Canvas'];
const views = computed<ViewMode[]>(() =>
  supportedViews.filter((viewName) => props.view.implemented.includes(viewName)),
);
const activeView = ref<ViewMode>(
  views.value.includes('Graph') ? 'Graph' : (views.value[0] ?? 'Tree'),
);
const selectedOverlay = ref<OverlayName | null>(
  props.overlay.active && props.overlay.available.includes(props.overlay.active)
    ? (props.overlay.active as OverlayName)
    : null,
);

const selectedLayer = computed(() => {
  if (!selectedOverlay.value) return null;
  return props.overlay.layers?.[selectedOverlay.value] ?? null;
});

const selectView = (viewName: ViewMode) => {
  if (views.value.includes(viewName)) activeView.value = viewName;
};

const selectOverlay = (overlay: OverlayName | null) => {
  selectedOverlay.value = overlay;
};

const mapScope = computed(() => props.map.scope?.id ?? props.active?.id ?? null);
const activeFilter = ref('all');
const zoomLevel = ref(100);
const handleZoomIn = () => {
  zoomLevel.value = Math.min(zoomLevel.value + 10, 150);
};
const handleZoomOut = () => {
  zoomLevel.value = Math.max(zoomLevel.value - 10, 50);
};
const handleZoomReset = () => {
  zoomLevel.value = 100;
};
</script>

<template>
  <Head title="المعرفة والتعلّم — التصوّر" />
  <div
    dir="rtl"
    class="min-h-screen bg-slate-950 text-slate-100 dark:bg-[#070c14] dark:text-slate-100"
  >
    <div class="w-full px-4 py-4 sm:px-6 xl:px-8">
      <!-- TOP Tools & Modes Bar -->
      <header
        class="mb-4 rounded-2xl border border-slate-800/80 bg-slate-900/50 p-3.5 shadow-lg backdrop-blur dark:bg-[#0b1322]/90"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <!-- View Switcher Tabs -->
          <div
            class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950/80 p-1"
          >
            <button
              v-for="viewName in views"
              :key="viewName"
              type="button"
              class="focus-ring flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold shadow-sm transition"
              :class="
                activeView === viewName
                  ? 'border border-cyan-500/40 bg-cyan-500/20 text-cyan-200 shadow-cyan-950/50'
                  : 'text-slate-400 hover:text-slate-200'
              "
              :aria-pressed="activeView === viewName"
              @click="selectView(viewName)"
            >
              <span>{{
                viewName === 'Graph'
                  ? '🕸️'
                  : viewName === 'Tree'
                    ? '🌲'
                    : viewName === 'Path'
                      ? '🛣️'
                      : '🎨'
              }}</span>
              <bdi dir="ltr">{{ viewName }}</bdi>
            </button>
          </div>

          <!-- Canvas / View Controls -->
          <div class="flex flex-wrap items-center gap-2 text-xs">
            <!-- Filter Pills -->
            <div
              class="flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-950 p-0.5"
            >
              <button
                type="button"
                class="rounded px-2 py-1 text-[11px] transition"
                :class="
                  activeFilter === 'all'
                    ? 'bg-slate-800 font-semibold text-slate-100'
                    : 'text-slate-400 hover:text-slate-200'
                "
                @click="activeFilter = 'all'"
              >
                الكل
              </button>
              <button
                type="button"
                class="rounded px-2 py-1 text-[11px] transition"
                :class="
                  activeFilter === 'units'
                    ? 'bg-slate-800 font-semibold text-cyan-300'
                    : 'text-slate-400 hover:text-slate-200'
                "
                @click="activeFilter = 'units'"
              >
                الوحدات المعرفية
              </button>
              <button
                type="button"
                class="rounded px-2 py-1 text-[11px] transition"
                :class="
                  activeFilter === 'capabilities'
                    ? 'bg-slate-800 font-semibold text-indigo-300'
                    : 'text-slate-400 hover:text-slate-200'
                "
                @click="activeFilter = 'capabilities'"
              >
                القدرات
              </button>
            </div>

            <!-- Zoom & Fit Controls -->
            <div
              class="flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-950 p-0.5 font-mono text-[11px]"
            >
              <button
                type="button"
                class="focus-ring rounded px-2 py-1 text-slate-300 hover:bg-slate-800"
                title="تصغير"
                @click="handleZoomOut"
              >
                −
              </button>
              <span class="px-1 text-slate-400">{{ zoomLevel }}%</span>
              <button
                type="button"
                class="focus-ring rounded px-2 py-1 text-slate-300 hover:bg-slate-800"
                title="تكبير"
                @click="handleZoomIn"
              >
                ＋
              </button>
              <span class="text-slate-700">|</span>
              <button
                type="button"
                class="focus-ring rounded px-2 py-1 text-slate-400 hover:text-white"
                title="ملاءمة"
                @click="handleZoomReset"
              >
                Fit
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- 3-Column Work Surface -->
      <div class="grid min-h-[740px] gap-4 xl:grid-cols-[280px_minmax(0,1fr)_320px]">
        <!-- LEFT: Map Scope & Catalog Structure -->
        <aside
          class="flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur dark:bg-[#0b1322]/90"
          aria-label="MAP scope"
        >
          <div class="border-b border-slate-800/80 pb-4">
            <div class="flex items-center justify-between">
              <h2 class="text-xs font-bold text-slate-200">النطاق المحفوظ وعالم العرض</h2>
              <span
                class="font-mono text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                dir="ltr"
                >MAP</span
              >
            </div>
            <div class="mt-3 space-y-2 rounded-xl border border-slate-800 bg-slate-950/60 p-3">
              <div class="flex items-center justify-between gap-2 text-xs">
                <span class="text-slate-400">الحالة</span>
                <bdi
                  dir="ltr"
                  class="font-mono font-bold"
                  :class="map.saved ? 'text-emerald-400' : 'text-amber-400'"
                >
                  {{ map.state }}
                </bdi>
              </div>
              <div class="flex items-center justify-between gap-2 text-xs">
                <span class="text-slate-400">Map ID</span>
                <bdi dir="ltr" class="font-mono text-slate-300">{{ map.id ?? '—' }}</bdi>
              </div>
              <div class="flex items-center justify-between gap-2 text-xs">
                <span class="text-slate-400">Canonical scope</span>
                <bdi dir="ltr" class="font-mono font-bold text-cyan-300">{{ mapScope ?? '—' }}</bdi>
              </div>
            </div>
            <p class="mt-3 text-[11px] leading-relaxed text-slate-400">
              <bdi dir="ltr" class="font-bold text-slate-300">MAP</bdi> يحفظ النطاق ومعلومات التمثيل
              فقط، ولا يصبح مخزنًا ثانيًا للعقد أو العلاقات القانونية.
            </p>
          </div>

          <h2 class="mt-4 text-xs font-bold text-slate-400">وحدات المعرفة في النطاق</h2>
          <ul v-if="catalog.length" class="mt-2.5 flex-1 space-y-1.5 overflow-y-auto pr-0.5">
            <li v-for="unit in catalog" :key="unit.id">
              <Link
                :href="`/knowledge/visualize?object=${encodeURIComponent(unit.id)}`"
                class="focus-ring block rounded-xl border px-3 py-2 text-xs transition"
                :class="
                  unit.id === active?.id
                    ? 'border-cyan-500/40 bg-cyan-500/10 text-cyan-100 shadow-sm'
                    : 'border-transparent text-slate-300 hover:border-slate-800 hover:bg-slate-900/60'
                "
              >
                <span class="block font-semibold">{{ unit.title_ar }}</span>
                <bdi dir="ltr" class="mt-1 block font-mono text-[10px] text-slate-500">{{
                  unit.id
                }}</bdi>
              </Link>
            </li>
          </ul>
          <p v-else class="mt-3 text-xs leading-6 text-slate-500">
            لا توجد وحدات canonical في النطاق الحالي.
          </p>
        </aside>

        <!-- CENTER: Visualization Canvas Workspace -->
        <main
          class="flex min-w-0 flex-1 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-5 shadow-lg backdrop-blur sm:p-7 dark:bg-[#0b1322]/90"
        >
          <div class="mb-5 border-b border-slate-800/80 pb-4">
            <KnowledgeTabs active="visualize" :object-id="active?.id" />
          </div>

          <header
            class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-800/80 pb-5"
          >
            <div>
              <div class="flex items-center gap-2">
                <span
                  class="inline-flex items-center gap-1 rounded-full border border-cyan-500/40 bg-cyan-950/60 px-2.5 py-0.5 text-xs font-semibold text-cyan-300"
                >
                  <bdi dir="ltr">VIEW = {{ activeView }}</bdi>
                </span>
                <span
                  class="rounded-full bg-slate-800 px-2 py-0.5 font-mono text-[10px] text-slate-400"
                >
                  {{ graph.nodes.length }} Nodes · {{ graph.edges.length }} Edges
                </span>
              </div>
              <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-100 sm:text-3xl">
                {{ active?.title_ar ?? 'لا يوجد نطاق نشط' }}
              </h1>
              <bdi v-if="active" dir="ltr" class="mt-1.5 block font-mono text-xs text-slate-400">
                {{ active.id }}
              </bdi>
            </div>
            <div class="text-left font-mono text-[11px] text-slate-400">
              <span class="block text-[10px] text-slate-500 uppercase">Projection source</span>
              <bdi dir="ltr" class="text-slate-300">{{ graph.source }}</bdi>
            </div>
          </header>

          <!-- Visualization Surface -->
          <div v-if="active && graph.nodes.length" class="mt-6 flex-1">
            <VisualizationSurface
              :view="activeView"
              :nodes="graph.nodes"
              :edges="graph.edges"
              :active-overlay="selectedOverlay"
              :overlay-layer="selectedLayer"
              :visual-positions="map.visual_positions"
            />
          </div>
          <div v-else class="grid min-h-[480px] place-items-center text-center text-slate-500">
            <div>
              <h2 class="text-lg font-bold text-slate-300">لا توجد علاقات قابلة للتصوّر.</h2>
              <p class="mx-auto mt-2 max-w-sm text-xs leading-relaxed">
                المشهد لا يختلق <bdi dir="ltr">Nodes</bdi> أو <bdi dir="ltr">Edges</bdi> عند غياب
                البيانات القانونية.
              </p>
            </div>
          </div>
        </main>

        <!-- RIGHT: Overlay Analysis & Rules -->
        <aside
          class="flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur dark:bg-[#0b1322]/90"
          aria-label="OVERLAY analysis"
        >
          <OverlayPanel :overlay="overlay" :selected="selectedOverlay" @select="selectOverlay" />

          <section class="mt-6 border-t border-slate-800/80 pt-4">
            <h2 class="text-xs font-bold text-slate-300">قواعد التمثيل</h2>
            <dl class="mt-3 space-y-2.5 text-xs leading-relaxed">
              <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                <dt dir="ltr" class="font-bold text-cyan-300">MAP</dt>
                <dd class="mt-1 text-[11px] text-slate-400">Saved visualization scope / world.</dd>
              </div>
              <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                <dt dir="ltr" class="font-bold text-indigo-300">VIEW</dt>
                <dd class="mt-1 text-[11px] text-slate-400">
                  Tree, Path, Graph, Canvas — تمثيلات لنفس العلاقات.
                </dd>
              </div>
              <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                <dt dir="ltr" class="font-bold text-emerald-300">OVERLAY</dt>
                <dd class="mt-1 text-[11px] text-slate-400">
                  طبقة تحليلية مرصودة، وليست كيانًا قانونيًا جديدًا.
                </dd>
              </div>
            </dl>
          </section>
        </aside>
      </div>

      <!-- BOTTOM: Trace Telemetry Drawer -->
      <details
        class="mt-4 rounded-2xl border border-slate-800/80 bg-slate-900/40 px-4 py-3 shadow-lg"
      >
        <summary
          class="flex cursor-pointer items-center justify-between text-xs font-bold text-slate-300"
        >
          <span>أثر العلاقات canonical — مساحة مؤقتة</span>
          <span class="font-mono text-[10px] text-cyan-400">{{ graph.edges.length }} edges</span>
        </summary>
        <div class="mt-4 max-h-48 space-y-2 overflow-y-auto pr-0.5">
          <bdi
            v-for="edge in graph.edges"
            :key="edge.id"
            dir="ltr"
            class="block rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 font-mono text-[11px] text-slate-400"
          >
            {{ edge.from }} → {{ edge.type }} → {{ edge.to }} · revision {{ edge.revision }}
          </bdi>
          <p v-if="!graph.edges.length" class="text-xs text-slate-500">لا يوجد أثر علاقات محفوظ.</p>
        </div>
      </details>
    </div>
  </div>
</template>

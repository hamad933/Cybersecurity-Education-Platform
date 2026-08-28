<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CepWorkspaceLayout from '../../layouts/CepWorkspaceLayout.vue';
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
const visibleNodes = computed(() => {
  if (activeFilter.value === 'units') {
    return props.graph.nodes.filter((node) => node.kind === 'knowledge_unit');
  }
  if (activeFilter.value === 'capabilities') {
    return props.graph.nodes.filter((node) => node.kind === 'capability');
  }
  return props.graph.nodes;
});
const visibleNodeIds = computed(() => new Set(visibleNodes.value.map((node) => node.id)));
const visibleEdges = computed(() =>
  props.graph.edges.filter(
    (edge) => visibleNodeIds.value.has(edge.from) && visibleNodeIds.value.has(edge.to),
  ),
);
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
  <CepWorkspaceLayout active-destination="knowledge">
    <template #primaryNavigation>
      <KnowledgeTabs active="visualize" :object-id="active?.id" />
    </template>

    <div dir="rtl" class="kl-visualize-route min-h-full bg-[var(--cep-bg-canvas)] text-[var(--cep-text)]">
    <div class="w-full px-0 py-3 sm:px-4 xl:px-6">
      <!-- TOP Tools & Modes Bar -->
      <header
        class="mb-4 rounded-2xl border border-slate-800/80 bg-slate-900/50 p-3.5 shadow-lg backdrop-blur dark:bg-[#0b1322]/90"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <!-- View Switcher Tabs -->
          <div
            class="flex max-w-full flex-nowrap items-center gap-1 overflow-x-auto rounded-xl border border-slate-800 bg-slate-950/80 p-1"
          >
            <button
              v-for="viewName in views"
              :key="viewName"
              type="button"
              class="focus-ring flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold whitespace-nowrap shadow-sm transition"
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
              class="flex flex-wrap items-center gap-1 rounded-lg border border-slate-800 bg-slate-950 p-0.5"
            >
              <button
                type="button"
                class="focus-ring rounded px-2 py-1 text-[11px] whitespace-nowrap transition"
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
                class="focus-ring rounded px-2 py-1 text-[11px] whitespace-nowrap transition"
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
                class="focus-ring rounded px-2 py-1 text-[11px] whitespace-nowrap transition"
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
                aria-label="تصغير"
                @click="handleZoomOut"
              >
                −
              </button>
              <span class="px-1 text-slate-400">{{ zoomLevel }}%</span>
              <button
                type="button"
                class="focus-ring rounded px-2 py-1 text-slate-300 hover:bg-slate-800"
                title="تكبير"
                aria-label="تكبير"
                @click="handleZoomIn"
              >
                ＋
              </button>
              <span class="text-slate-700">|</span>
              <button
                type="button"
                class="focus-ring rounded px-2 py-1 text-slate-400 hover:text-white"
                title="ملاءمة العرض"
                aria-label="ملاءمة العرض"
                @click="handleZoomReset"
              >
                Fit
              </button>
            </div>
          </div>
        </div>
        <div class="mt-3 border-t border-slate-800/80 pt-3">
          <OverlayPanel
            mode="controls"
            :overlay="overlay"
            :selected="selectedOverlay"
            @select="selectOverlay"
          />
        </div>
      </header>

      <!-- 3-Column Work Surface with strict physical orientation -->
      <div
        dir="ltr"
        class="grid min-h-[740px] grid-cols-1 gap-4 md:grid-cols-[220px_minmax(0,1fr)] xl:grid-cols-[280px_minmax(0,1fr)_320px]"
      >
        <!-- LEFT: Map Scope & Catalog Structure (Visual LEFT) -->
        <aside
          dir="rtl"
          class="order-2 flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur md:order-1 md:max-h-[calc(100vh-12rem)] xl:max-h-none dark:bg-[#0b1322]/90"
          aria-label="نطاق الخريطة والوحدات"
        >
          <div class="border-b border-slate-800/80 pb-4">
            <div class="flex items-center justify-between">
              <h2 class="text-xs font-bold text-slate-200">النطاق المحفوظ وعالم العرض</h2>
              <span
                class="font-mono text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                dir="ltr"
              >
                MAP
              </span>
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
                <span class="text-slate-400">معرّف الخريطة</span>
                <bdi dir="ltr" class="font-mono text-slate-300">{{ map.id ?? '—' }}</bdi>
              </div>
              <div class="flex items-center justify-between gap-2 text-xs">
                <span class="text-slate-400">النطاق القانوني</span>
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
            لا توجد وحدات قانونية في النطاق الحالي.
          </p>
        </aside>

        <!-- CENTER: Visualization Canvas Workspace (Visual CENTER) -->
        <main
          dir="rtl"
          class="order-1 flex min-w-0 flex-1 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur sm:p-5 md:order-2 xl:p-7 dark:bg-[#0b1322]/90"
          aria-label="مساحة التمثيل والتصوّر"
        >
          <header
            class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-800/80 pb-5"
          >
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <span
                  class="inline-flex items-center gap-1 rounded-full border border-cyan-500/40 bg-cyan-950/60 px-2.5 py-0.5 text-xs font-semibold text-cyan-300"
                >
                  <bdi dir="ltr">VIEW = {{ activeView }}</bdi>
                </span>
                <span
                  class="rounded-full bg-slate-800 px-2 py-0.5 font-mono text-[10px] text-slate-400"
                >
                  {{ visibleNodes.length }} عقدة · {{ visibleEdges.length }} رابط
                </span>
              </div>
              <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-100 sm:text-3xl">
                {{ active?.title_ar ?? 'لا يوجد نطاق نشط' }}
              </h1>
              <bdi v-if="active" dir="ltr" class="mt-1.5 block font-mono text-xs text-slate-400">
                {{ active.id }}
              </bdi>
            </div>
          </header>

          <!-- Visualization Surface -->
          <div v-if="active && visibleNodes.length" class="mt-6 flex-1 overflow-auto">
            <div
              class="min-w-0 transition-transform duration-150 motion-reduce:transition-none"
              :style="{
                transform: `scale(${zoomLevel / 100})`,
                transformOrigin: 'top center',
              }"
            >
              <VisualizationSurface
                :view="activeView"
                :nodes="visibleNodes"
                :edges="visibleEdges"
                :active-overlay="selectedOverlay"
                :overlay-layer="selectedLayer"
                :visual-positions="map.visual_positions"
              />
            </div>
          </div>
          <div v-else class="grid min-h-[480px] place-items-center text-center text-slate-500">
            <div>
              <h2 class="text-lg font-bold text-slate-300">لا توجد علاقات قابلة للتصوّر.</h2>
              <p class="mx-auto mt-2 max-w-sm text-xs leading-relaxed">
                المشهد لا يختلق عقدًا أو روابط عند غياب البيانات القانونية.
              </p>
            </div>
          </div>
        </main>

        <!-- RIGHT: Overlay Analysis & Rules (Visual RIGHT) -->
        <aside
          dir="rtl"
          class="order-3 flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur md:col-span-2 xl:col-span-1 dark:bg-[#0b1322]/90"
          aria-label="تحليل الطبقات المرصودة"
        >
          <OverlayPanel mode="context" :overlay="overlay" :selected="selectedOverlay" />

          <section class="mt-6 border-t border-slate-800/80 pt-4">
            <h2 class="text-xs font-bold text-slate-300">مصدر الإسقاط</h2>
            <bdi dir="ltr" class="mt-2 block break-all font-mono text-[11px] text-slate-400">
              {{ graph.source }}
            </bdi>
          </section>
        </aside>
      </div>

      <!-- BOTTOM: Trace Telemetry Drawer (Closed by default) -->
      <details
        dir="rtl"
        class="mt-4 rounded-2xl border border-slate-800/80 bg-slate-900/40 px-4 py-3 shadow-lg"
      >
        <summary
          class="flex cursor-pointer items-center justify-between text-xs font-bold text-slate-300"
        >
          <span>أثر العلاقات القانونية — مساحة مؤقتة</span>
          <span class="font-mono text-[10px] text-cyan-400">{{ graph.edges.length }} روابط</span>
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
  </CepWorkspaceLayout>
</template>

<style>
[data-theme='light'] .kl-visualize-route [class*='bg-slate-950'],
[data-theme='light'] .kl-visualize-route [class*='bg-slate-900'],
[data-theme='light'] .kl-visualize-route [class*='bg-slate-800'],
[data-theme='light'] .kl-visualize-route [class*='bg-[#0b1322]'],
[data-theme='light'] .kl-visualize-route [class*='bg-[#070c14]'] {
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

[data-theme='light'] .kl-visualize-route [class*='border-slate-700'],
[data-theme='light'] .kl-visualize-route [class*='border-slate-800'] {
  border-color: var(--cep-border) !important;
}

[data-theme='light'] .kl-visualize-route [class*='text-cyan-100'],
[data-theme='light'] .kl-visualize-route [class*='text-cyan-200'],
[data-theme='light'] .kl-visualize-route [class*='text-cyan-300'],
[data-theme='light'] .kl-visualize-route [class*='text-cyan-400'] {
  color: var(--cep-accent) !important;
}

[data-theme='light'] .kl-visualize-route [class*='text-amber-300'],
[data-theme='light'] .kl-visualize-route [class*='text-amber-400'] {
  color: #b45309 !important;
}
</style>

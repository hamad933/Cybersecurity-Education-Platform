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
</script>

<template>
  <Head title="المعرفة والتعلّم — التصوّر" />
  <div dir="rtl" class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto max-w-[1600px] px-4 py-5 sm:px-6">
      <header class="border-b border-slate-800 pb-4">
          <div class="flex-1"></div>
          <div class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
            <button
              v-for="viewName in views"
              :key="viewName"
              type="button"
              class="focus-ring rounded-lg px-3 py-2 text-xs transition"
              :class="
                activeView === viewName
                  ? 'bg-cyan-400/10 text-cyan-200'
                  : 'text-slate-400 hover:text-slate-200'
              "
              :aria-pressed="activeView === viewName"
              @click="selectView(viewName)"
            >
              <bdi dir="ltr">{{ viewName }}</bdi>
            </button>
          </div>
        </div>
      </header>

      <div class="mt-4 grid min-h-[720px] gap-4 xl:grid-cols-[270px_minmax(0,1fr)_310px]">
        <aside
          class="rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="MAP scope"
        >
          <div class="border-b border-slate-800 pb-4">
            <p class="text-[10px] font-bold tracking-[0.2em] text-slate-600" dir="ltr">MAP</p>
            <h2 class="mt-1 text-sm font-black">النطاق المحفوظ أو عالم العرض</h2>
            <div class="mt-3 rounded-lg border border-slate-800 bg-slate-950/40 p-3">
              <div class="flex items-center justify-between gap-2 text-xs">
                <span class="text-slate-500">الحالة</span>
                <bdi
                  dir="ltr"
                  class="font-mono"
                  :class="map.saved ? 'text-emerald-300' : 'text-amber-300'"
                >
                  {{ map.state }}
                </bdi>
              </div>
              <div class="mt-2 flex items-center justify-between gap-2 text-xs">
                <span class="text-slate-500">Map ID</span>
                <bdi dir="ltr" class="font-mono text-slate-300">{{ map.id ?? '—' }}</bdi>
              </div>
              <div class="mt-2 flex items-center justify-between gap-2 text-xs">
                <span class="text-slate-500">Canonical scope</span>
                <bdi dir="ltr" class="font-mono text-cyan-200">{{ mapScope ?? '—' }}</bdi>
              </div>
            </div>
            <p class="mt-3 text-xs leading-6 text-slate-500">
              <bdi dir="ltr">MAP</bdi> يحفظ النطاق ومعلومات التمثيل فقط، ولا يصبح مخزنًا ثانيًا
              للعقد أو العلاقات القانونية.
            </p>
          </div>

          <h2 class="mt-5 text-xs font-bold text-slate-400">وحدات المعرفة</h2>
          <ul v-if="catalog.length" class="mt-3 space-y-1">
            <li v-for="unit in catalog" :key="unit.id">
              <Link
                :href="`/knowledge/visualize?object=${encodeURIComponent(unit.id)}`"
                class="focus-ring block rounded-lg px-3 py-2 text-sm"
                :class="
                  unit.id === active?.id
                    ? 'bg-cyan-400/10 text-cyan-100'
                    : 'text-slate-300 hover:bg-slate-800'
                "
              >
                {{ unit.title_ar }}
                <bdi dir="ltr" class="mt-1 block text-[10px] text-slate-600">{{ unit.id }}</bdi>
              </Link>
            </li>
          </ul>
          <p v-else class="mt-3 text-sm leading-7 text-slate-500">
            لا توجد وحدات canonical في النطاق الحالي.
          </p>
        </aside>

        <main class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/35 p-5 sm:p-7">
          <div class="mb-5 border-b border-slate-800/80 pb-4">
            <KnowledgeTabs active="visualize" :object-id="active?.id" />
          </div>
          <header
            class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-800 pb-5"
          >
            <div>
              <p class="text-xs font-bold text-cyan-300">
                <bdi dir="ltr">VIEW</bdi> = <bdi dir="ltr">{{ activeView }}</bdi>
              </p>
              <h1 class="mt-2 text-2xl font-black">{{ active?.title_ar ?? 'لا يوجد نطاق نشط' }}</h1>
              <bdi v-if="active" dir="ltr" class="mt-2 block font-mono text-xs text-slate-500">
                {{ active.id }}
              </bdi>
            </div>
            <div class="text-left text-[10px] text-slate-600">
              <span class="block">Projection source</span>
              <bdi dir="ltr" class="font-mono text-slate-500">{{ graph.source }}</bdi>
            </div>
          </header>

          <div v-if="active && graph.nodes.length" class="mt-6">
            <VisualizationSurface
              :view="activeView"
              :nodes="graph.nodes"
              :edges="graph.edges"
              :active-overlay="selectedOverlay"
              :overlay-layer="selectedLayer"
              :visual-positions="map.visual_positions"
            />
          </div>
          <div v-else class="grid min-h-[480px] place-items-center text-center">
            <div>
              <h2 class="font-bold text-slate-300">لا توجد علاقات قابلة للتصوّر.</h2>
              <p class="mt-2 text-sm text-slate-500">
                المشهد لا يختلق <bdi dir="ltr">Nodes</bdi> أو <bdi dir="ltr">Edges</bdi> عند غياب
                البيانات القانونية.
              </p>
            </div>
          </div>
        </main>

        <aside
          class="rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="OVERLAY analysis"
        >
          <OverlayPanel :overlay="overlay" :selected="selectedOverlay" @select="selectOverlay" />

          <section class="mt-6 border-t border-slate-800 pt-5">
            <h2 class="text-xs font-bold text-slate-400">قواعد التمثيل</h2>
            <dl class="mt-3 space-y-3 text-xs leading-6">
              <div class="rounded-lg border border-slate-800 p-3">
                <dt dir="ltr" class="font-bold text-cyan-300">MAP</dt>
                <dd class="mt-1 text-slate-500">Saved visualization scope / world.</dd>
              </div>
              <div class="rounded-lg border border-slate-800 p-3">
                <dt dir="ltr" class="font-bold text-indigo-300">VIEW</dt>
                <dd class="mt-1 text-slate-500">
                  Tree, Path, Graph, Canvas — تمثيلات لنفس العلاقات.
                </dd>
              </div>
              <div class="rounded-lg border border-slate-800 p-3">
                <dt dir="ltr" class="font-bold text-emerald-300">OVERLAY</dt>
                <dd class="mt-1 text-slate-500">
                  طبقة تحليلية مرصودة، وليست كيانًا قانونيًا جديدًا.
                </dd>
              </div>
            </dl>
          </section>
        </aside>
      </div>

      <details class="mt-4 rounded-xl border border-slate-800 bg-slate-900/30 px-4 py-3">
        <summary class="cursor-pointer text-sm font-bold text-slate-400">
          أثر العلاقات canonical — مساحة مؤقتة
        </summary>
        <div class="mt-4 space-y-2">
          <bdi
            v-for="edge in graph.edges"
            :key="edge.id"
            dir="ltr"
            class="block rounded border border-slate-800 bg-slate-950/40 px-3 py-2 font-mono text-[11px] text-slate-500"
          >
            {{ edge.from }} → {{ edge.type }} → {{ edge.to }} · revision {{ edge.revision }}
          </bdi>
          <p v-if="!graph.edges.length" class="text-xs text-slate-600">لا يوجد أثر علاقات محفوظ.</p>
        </div>
      </details>
    </div>
  </div>
</template>

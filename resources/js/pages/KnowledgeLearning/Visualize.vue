<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import KnowledgeTabs from './components/KnowledgeTabs.vue';

type CatalogItem = { id: string; title_ar: string; title_en: string };
type Node = {
  id: string;
  kind: 'knowledge_unit' | 'capability';
  label: string;
  technical_label: string;
};
type Edge = {
  id: string;
  from: string;
  to: string;
  type: string;
  revision: number;
  lifecycle: Record<string, unknown>;
};
type ViewMode = 'Tree' | 'Path' | 'Graph' | 'Canvas';

const props = defineProps<{
  catalog: CatalogItem[];
  active: { id: string; title_ar: string; title_en: string } | null;
  map: { saved: boolean; id: string | null; state: string };
  view: { implemented: string[]; not_implemented: string[] };
  overlay: { active: string | null; available: string[] };
  graph: { nodes: Node[]; edges: Edge[]; source: string };
}>();

const views: ViewMode[] = ['Tree', 'Path', 'Graph', 'Canvas'];
const activeView = ref<ViewMode>('Tree');
const selectView = (viewName: ViewMode) => {
  activeView.value = viewName;
};
const capabilities = computed(() => props.graph.nodes.filter((node) => node.kind === 'capability'));
const orderedCapabilities = computed(() =>
  capabilities.value.slice().sort((left, right) =>
    left.technical_label.localeCompare(right.technical_label, 'en'),
  ),
);
const unitNode = computed(
  () => props.graph.nodes.find((node) => node.kind === 'knowledge_unit') ?? null,
);
const nodeById = computed(() => new Map(props.graph.nodes.map((node) => [node.id, node])));
const edgeRows = computed(() =>
  props.graph.edges.map((edge) => ({
    ...edge,
    fromNode: nodeById.value.get(edge.from) ?? null,
    toNode: nodeById.value.get(edge.to) ?? null,
  })),
);
</script>

<template>
  <Head title="المعرفة والتعلّم — التصوّر" />
  <div dir="rtl" class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto max-w-[1600px] px-4 py-5 sm:px-6">
      <header class="border-b border-slate-800 pb-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <KnowledgeTabs active="visualize" :object-id="active?.id" />
          <div class="flex items-center gap-1 rounded-lg bg-slate-900 p-1 text-xs" aria-label="أنماط التصوّر">
            <button
              v-for="viewName in views"
              :key="viewName"
              type="button"
              class="focus-ring rounded px-3 py-2"
              :class="activeView === viewName ? 'bg-cyan-400/10 text-cyan-200' : 'text-slate-400'"
              :aria-pressed="activeView === viewName"
              @click="selectView(viewName)"
            >
              <bdi dir="ltr">{{ viewName }}</bdi>
            </button>
          </div>
        </div>
      </header>

      <div class="mt-4 grid min-h-[700px] gap-4 xl:grid-cols-[260px_minmax(0,1fr)_280px]">
        <aside
          class="rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="بنية التصوّر والتنقل"
        >
          <h2 class="text-xs font-bold text-slate-400">النطاق القانوني الحالي</h2>
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
              </Link>
            </li>
          </ul>
          <p v-else class="mt-3 text-sm leading-7 text-slate-500">
            لا توجد وحدات في نطاق التنقل الحالي.
          </p>
        </aside>

        <main class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/35 p-5 sm:p-7">
          <header v-if="active" class="border-b border-slate-800 pb-5">
            <p class="text-xs font-bold text-cyan-300">
              <bdi dir="ltr">VIEW</bdi> = <bdi dir="ltr">{{ activeView }}</bdi>
            </p>
            <h1 class="mt-2 text-2xl font-black">{{ active.title_ar }}</h1>
            <bdi dir="ltr" class="mt-2 block font-mono text-sm text-cyan-200">
              {{ active.id }}
            </bdi>
          </header>

          <section v-if="active && graph.nodes.length" class="mt-8">
            <div
              v-if="activeView === 'Tree'"
              class="flex min-h-80 flex-col items-center justify-center gap-4"
              aria-label="عرض شجري للعلاقات"
            >
              <div v-if="capabilities.length" class="flex flex-wrap justify-center gap-3">
                <article
                  v-for="capability in capabilities"
                  :key="capability.id"
                  class="rounded-xl border border-indigo-700/70 bg-indigo-950/30 px-5 py-3 text-center"
                >
                  <p class="text-xs text-slate-500"><bdi dir="ltr">Capability</bdi></p>
                  <bdi dir="ltr" class="mt-1 block font-mono text-sm text-indigo-200">
                    {{ capability.technical_label }}
                  </bdi>
                </article>
              </div>
              <div v-if="capabilities.length" class="h-8 w-px bg-slate-700" aria-hidden="true"></div>
              <div
                v-if="unitNode"
                class="rounded-xl border border-cyan-600 bg-cyan-950/25 px-6 py-4 text-center"
              >
                <p class="font-bold">{{ unitNode.label }}</p>
                <bdi dir="ltr" class="mt-1 block font-mono text-xs text-cyan-200">
                  {{ unitNode.technical_label }}
                </bdi>
                <p v-if="!capabilities.length" class="mt-3 text-xs text-slate-500">
                  لا يوجد <bdi dir="ltr">Curriculum Placement</bdi> محفوظ لهذه الوحدة.
                </p>
              </div>
            </div>

            <div
              v-else-if="activeView === 'Path'"
              class="mx-auto flex min-h-80 max-w-3xl flex-col items-stretch justify-center"
              aria-label="عرض مسار للعلاقات"
            >
              <template v-for="(capability, index) in orderedCapabilities" :key="capability.id">
                <article class="rounded-xl border border-indigo-800 bg-indigo-950/20 px-5 py-4">
                  <div class="flex flex-wrap items-center justify-between gap-3">
                    <span class="text-xs text-slate-500">{{ index + 1 }} — Capability</span>
                    <bdi dir="ltr" class="font-mono text-sm text-indigo-200">
                      {{ capability.technical_label }}
                    </bdi>
                  </div>
                </article>
                <div class="mx-auto h-7 w-px bg-slate-700" aria-hidden="true"></div>
              </template>
              <article
                v-if="unitNode"
                class="rounded-xl border border-cyan-700 bg-cyan-950/25 px-5 py-4 text-center"
              >
                <p class="text-xs text-slate-500">Knowledge Unit</p>
                <p class="mt-1 font-bold">{{ unitNode.label }}</p>
                <bdi dir="ltr" class="mt-1 block font-mono text-xs text-cyan-200">
                  {{ unitNode.technical_label }}
                </bdi>
              </article>
              <p v-if="!orderedCapabilities.length" class="text-center text-sm text-slate-500">
                لا يوجد مسار Capability محفوظ لهذه الوحدة؛ تظهر الوحدة القانونية وحدها.
              </p>
            </div>

            <div
              v-else-if="activeView === 'Graph'"
              class="grid min-h-80 gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(260px,0.7fr)]"
              aria-label="عرض شبكي للعلاقات"
            >
              <div class="grid content-center gap-3 sm:grid-cols-2">
                <article
                  v-for="capability in capabilities"
                  :key="capability.id"
                  class="rounded-xl border border-indigo-800 bg-indigo-950/20 p-4 text-center"
                >
                  <bdi dir="ltr" class="font-mono text-sm text-indigo-200">
                    {{ capability.technical_label }}
                  </bdi>
                </article>
                <article
                  v-if="unitNode"
                  class="rounded-xl border border-cyan-700 bg-cyan-950/20 p-5 text-center sm:col-span-2"
                >
                  <p class="font-bold">{{ unitNode.label }}</p>
                  <bdi dir="ltr" class="mt-1 block font-mono text-xs text-cyan-200">
                    {{ unitNode.technical_label }}
                  </bdi>
                </article>
              </div>
              <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                <h2 class="text-xs font-bold text-slate-500">Edges</h2>
                <ol v-if="edgeRows.length" class="mt-3 space-y-3">
                  <li
                    v-for="edge in edgeRows"
                    :key="edge.id"
                    class="rounded-lg border border-slate-800 p-3 text-xs"
                  >
                    <bdi dir="ltr" class="block font-mono text-slate-300">
                      {{ edge.fromNode?.technical_label ?? edge.from }}
                    </bdi>
                    <bdi dir="ltr" class="my-1 block font-mono text-amber-300">→ {{ edge.type }} →</bdi>
                    <bdi dir="ltr" class="block font-mono text-slate-300">
                      {{ edge.toNode?.technical_label ?? edge.to }}
                    </bdi>
                  </li>
                </ol>
                <p v-else class="mt-3 text-xs text-slate-500">لا توجد Edges محفوظة.</p>
              </div>
            </div>

            <div
              v-else
              class="min-h-96 rounded-2xl border border-dashed border-slate-700 bg-slate-950/35 p-5"
              aria-label="لوحة Canvas للعلاقات"
            >
              <div class="grid min-h-80 gap-4 md:grid-cols-2 md:content-center">
                <article
                  v-for="(capability, index) in capabilities"
                  :key="capability.id"
                  class="rounded-xl border border-indigo-800/80 bg-indigo-950/20 p-4"
                  :class="index % 2 === 0 ? 'md:translate-y-3' : 'md:-translate-y-3'"
                >
                  <p class="text-[11px] text-slate-500">Capability node</p>
                  <bdi dir="ltr" class="mt-1 block font-mono text-sm text-indigo-200">
                    {{ capability.technical_label }}
                  </bdi>
                </article>
                <article
                  v-if="unitNode"
                  class="rounded-xl border border-cyan-600 bg-cyan-950/30 p-5 text-center md:col-span-2 md:mx-auto md:w-2/3"
                >
                  <p class="text-[11px] text-slate-500">Canonical Knowledge Unit</p>
                  <p class="mt-1 font-bold">{{ unitNode.label }}</p>
                  <bdi dir="ltr" class="mt-1 block font-mono text-xs text-cyan-200">
                    {{ unitNode.technical_label }}
                  </bdi>
                </article>
              </div>
              <div v-if="edgeRows.length" class="mt-4 flex flex-wrap justify-center gap-2 border-t border-slate-800 pt-4">
                <bdi
                  v-for="edge in edgeRows"
                  :key="edge.id"
                  dir="ltr"
                  class="rounded-full border border-slate-700 px-3 py-1 font-mono text-[11px] text-slate-400"
                >
                  {{ edge.fromNode?.technical_label ?? edge.from }} → {{ edge.type }} →
                  {{ edge.toNode?.technical_label ?? edge.to }}
                </bdi>
              </div>
            </div>
          </section>

          <div v-else class="grid min-h-[420px] place-items-center text-center">
            <div>
              <h1 class="font-bold text-slate-300">لا توجد علاقات قابلة للتصوّر.</h1>
              <p class="mt-2 text-sm text-slate-500">المشهد لا يختلق Nodes أو Edges.</p>
            </div>
          </div>
        </main>

        <aside
          class="rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="سياق التصوّر"
        >
          <section>
            <h2 class="text-xs font-bold text-slate-500">
              <bdi dir="ltr">MAP</bdi><span> — حالة النطاق</span>
            </h2>
            <bdi v-if="map.id" dir="ltr" class="mt-2 block font-mono text-xs text-slate-300">
              {{ map.id }}
            </bdi>
            <p class="mt-2 text-sm leading-7 text-slate-400">
              {{ map.saved ? 'النطاق الحالي مرتبط بخريطة محفوظة.' : 'المشهد الحالي مشتق من العلاقات المحفوظة دون ادعاء وجود Map محفوظة.' }}
            </p>
            <bdi dir="ltr" class="mt-2 block font-mono text-[10px] text-slate-600">
              {{ map.state }}
            </bdi>
          </section>

          <section class="mt-6 border-t border-slate-800 pt-5">
            <h2 class="text-xs font-bold text-slate-500">
              <bdi dir="ltr">OVERLAY</bdi><span> — طبقة تحليلية</span>
            </h2>
            <p v-if="overlay.active" class="mt-3 text-sm">{{ overlay.active }}</p>
            <p v-else class="mt-3 text-sm leading-7 text-slate-400">
              لا توجد طبقة تحليلية فعالة أو مصرّح بها ضمن بيانات W02 الحالية.
            </p>
          </section>

          <section class="mt-6 border-t border-slate-800 pt-5">
            <h2 class="text-xs font-bold text-slate-500">مصدر العلاقات</h2>
            <bdi dir="ltr" class="mt-2 block font-mono text-xs text-slate-300">
              {{ graph.source }}
            </bdi>
            <p class="mt-3 text-xs leading-6 text-slate-500">
              تغيير View يغيّر التمثيل البنيوي فقط. لا توجد عملية كتابة تغيّر canonical containment.
            </p>
          </section>

          <section class="mt-6 border-t border-slate-800 pt-5 text-sm">
            <p>
              العقد: <bdi dir="ltr" class="font-mono">{{ graph.nodes.length }}</bdi>
            </p>
            <p class="mt-2">
              العلاقات: <bdi dir="ltr" class="font-mono">{{ graph.edges.length }}</bdi>
            </p>
            <p class="mt-2">
              الأنماط البنيوية: <bdi dir="ltr" class="font-mono">{{ views.length }}</bdi>
            </p>
          </section>
        </aside>
      </div>
    </div>
  </div>
</template>

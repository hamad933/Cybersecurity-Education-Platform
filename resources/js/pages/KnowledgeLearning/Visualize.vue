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

const props = defineProps<{
  catalog: CatalogItem[];
  active: { id: string; title_ar: string; title_en: string } | null;
  map: { saved: boolean; id: string | null; state: string };
  view: { implemented: string[]; not_implemented: string[] };
  overlay: { active: string | null; available: string[] };
  graph: { nodes: Node[]; edges: Edge[]; source: string };
}>();

const activeView = ref<'Tree' | 'Graph'>('Tree');
const selectView = (viewName: string) => {
  if (viewName === 'Tree' || viewName === 'Graph') activeView.value = viewName;
};
const capabilities = computed(() =>
  props.graph.nodes.filter((node) => node.kind === 'capability'),
);
const unitNode = computed(
  () => props.graph.nodes.find((node) => node.kind === 'knowledge_unit') ?? null,
);
</script>

<template>
  <Head title="المعرفة والتعلّم — التصوّر" />
  <div dir="rtl" class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto max-w-[1600px] px-4 py-5 sm:px-6">
      <header class="border-b border-slate-800 pb-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <KnowledgeTabs active="visualize" :object-id="active?.id" />
          <div class="flex items-center gap-1 rounded-lg bg-slate-900 p-1 text-xs">
            <button
              v-for="viewName in view.implemented"
              :key="viewName"
              type="button"
              class="focus-ring rounded px-3 py-2"
              :class="
                activeView === viewName ? 'bg-cyan-400/10 text-cyan-200' : 'text-slate-400'
              "
              @click="selectView(viewName)"
            >
              <bdi dir="ltr">{{ viewName }}</bdi>
            </button>
            <button
              v-for="viewName in view.not_implemented"
              :key="viewName"
              type="button"
              disabled
              class="rounded px-3 py-2 text-slate-700"
              :title="`${viewName} غير منفّذ في Wave 1`"
            >
              <bdi dir="ltr">{{ viewName }}</bdi>
            </button>
          </div>
        </div>
      </header>

      <div class="mt-4 grid min-h-[700px] gap-4 xl:grid-cols-[260px_minmax(0,1fr)_280px]">
        <aside
          class="rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="الخريطة والنطاق"
        >
          <h2 class="text-xs font-bold text-slate-400">
            <bdi dir="ltr">MAP</bdi><span> — نطاق محفوظ</span>
          </h2>
          <div class="mt-3 rounded-lg border border-dashed border-slate-700 p-3">
            <p class="text-sm font-bold">لا توجد Map محفوظة في نموذج Wave 1 الحالي.</p>
            <p class="mt-2 text-xs leading-6 text-slate-500">
              المشهد الحالي تمثيل مباشر للعلاقات persisted ولا يُسوَّق على أنه Map محفوظ.
            </p>
            <bdi dir="ltr" class="mt-2 block font-mono text-[10px] text-slate-600">
              {{ map.state }}
            </bdi>
          </div>

          <h2 class="mt-6 text-xs font-bold text-slate-400">النطاق القانوني الحالي</h2>
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
              <div
                v-if="capabilities.length"
                class="h-8 w-px bg-slate-700"
                aria-hidden="true"
              ></div>
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

            <div v-else class="grid gap-4 md:grid-cols-[1fr_auto_1fr] md:items-center">
              <div class="space-y-3">
                <article
                  v-for="capability in capabilities"
                  :key="capability.id"
                  class="rounded-xl border border-indigo-800 bg-indigo-950/20 p-4 text-center"
                >
                  <bdi dir="ltr" class="font-mono text-sm text-indigo-200">
                    {{ capability.technical_label }}
                  </bdi>
                </article>
                <p v-if="!capabilities.length" class="text-center text-sm text-slate-500">
                  لا توجد عقد Capability مرتبطة.
                </p>
              </div>
              <div class="px-4 text-center text-slate-600" aria-hidden="true">⟷</div>
              <article
                v-if="unitNode"
                class="rounded-xl border border-cyan-700 bg-cyan-950/20 p-5 text-center"
              >
                <p class="font-bold">{{ unitNode.label }}</p>
                <bdi dir="ltr" class="mt-1 block font-mono text-xs text-cyan-200">
                  {{ unitNode.technical_label }}
                </bdi>
              </article>
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
          <h2 class="text-xs font-bold text-slate-500">
            <bdi dir="ltr">OVERLAY</bdi><span> — طبقة تحليلية</span>
          </h2>
          <p v-if="overlay.active" class="mt-3 text-sm">{{ overlay.active }}</p>
          <p v-else class="mt-3 text-sm leading-7 text-slate-400">
            لا توجد طبقة تحليلية فعالة أو مصرّح بها ضمن بيانات W02 الحالية.
          </p>

          <section class="mt-6 border-t border-slate-800 pt-5">
            <h2 class="text-xs font-bold text-slate-500">مصدر العلاقات</h2>
            <bdi dir="ltr" class="mt-2 block font-mono text-xs text-slate-300">
              {{ graph.source }}
            </bdi>
            <p class="mt-3 text-xs leading-6 text-slate-500">
              تغيير View يغيّر التمثيل فقط. لا توجد عملية كتابة تغيّر canonical containment.
            </p>
          </section>

          <section class="mt-6 border-t border-slate-800 pt-5 text-sm">
            <p>العقد: <bdi dir="ltr" class="font-mono">{{ graph.nodes.length }}</bdi></p>
            <p class="mt-2">
              العلاقات: <bdi dir="ltr" class="font-mono">{{ graph.edges.length }}</bdi>
            </p>
          </section>
        </aside>
      </div>
    </div>
  </div>
</template>

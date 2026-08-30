<script setup lang="ts">
import { computed } from 'vue';
import type { OverlayLayer, OverlayName, ViewMode, VisualEdge, VisualNode } from './types';

const props = defineProps<{
  view: ViewMode;
  nodes: VisualNode[];
  edges: VisualEdge[];
  activeOverlay: OverlayName | null;
  overlayLayer: OverlayLayer | null;
  visualPositions?: Record<string, { x: number; y: number }>;
  selectedNodeId?: string | null;
}>();

const emit = defineEmits<{
  selectNode: [id: string | null];
}>();

const nodeById = computed(() => new Map(props.nodes.map((node) => [node.id, node])));
const unitNodes = computed(() => props.nodes.filter((node) => node.kind === 'knowledge_unit'));
const capabilityNodes = computed(() => props.nodes.filter((node) => node.kind === 'capability'));

const observationFor = (nodeId: string): unknown => {
  if (!props.activeOverlay || !props.overlayLayer?.available) return null;
  const observations = props.overlayLayer.observations;
  if (!observations || typeof observations !== 'object' || Array.isArray(observations)) return null;
  return (observations as Record<string, unknown>)[nodeId] ?? null;
};

const observationLabel = (value: unknown): string | null => {
  if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
    return String(value);
  }
  if (!value || typeof value !== 'object' || Array.isArray(value)) return null;
  const record = value as Record<string, unknown>;
  const candidate = record.label ?? record.value ?? record.state;
  return typeof candidate === 'string' ||
    typeof candidate === 'number' ||
    typeof candidate === 'boolean'
    ? String(candidate)
    : null;
};
</script>

<template>
  <section aria-live="polite">
    <!-- View 1: Tree View -->
    <div v-if="view === 'Tree'" class="space-y-4" aria-label="Tree canonical relationship view">
      <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
        <p class="mb-3 text-xs font-medium text-slate-400">
          احتواء قانوني قابل للطي؛ كل فرع مشتق من علاقة مسجلة واحدة.
        </p>
        <div class="space-y-3">
          <details
            v-for="edge in edges"
            :key="edge.id"
            open
            class="rounded-xl border border-slate-800/80 bg-slate-900/60 shadow-sm transition hover:border-slate-700"
          >
            <summary
              class="focus-ring flex cursor-pointer items-center justify-between gap-3 rounded-xl p-3 text-xs"
            >
              <span class="min-w-0">
                <span class="block truncate font-bold text-slate-200">
                  {{ nodeById.get(edge.from)?.label ?? edge.from }}
                </span>
                <bdi dir="ltr" class="mt-1 block truncate font-mono text-[10px] text-indigo-300">
                  {{ nodeById.get(edge.from)?.technical_label ?? edge.from }}
                </bdi>
              </span>
              <span
                class="shrink-0 rounded-full border border-amber-500/40 bg-amber-950/60 px-2.5 py-1 font-mono text-[10px] font-bold text-amber-300"
              >
                <bdi dir="ltr">{{ edge.type }}</bdi>
              </span>
            </summary>
            <div class="ms-5 border-s border-cyan-500/40 px-4 pb-4">
              <div class="rounded-xl border border-cyan-500/40 bg-cyan-950/30 p-3">
                <p class="text-sm font-bold text-slate-100">
                  {{ nodeById.get(edge.to)?.label ?? edge.to }}
                </p>
                <bdi dir="ltr" class="mt-1 block font-mono text-xs text-cyan-300">
                  {{ nodeById.get(edge.to)?.technical_label ?? edge.to }}
                </bdi>
                <bdi dir="ltr" class="mt-2 block font-mono text-[10px] text-slate-500">
                  relationship revision {{ edge.revision }}
                </bdi>
              </div>
            </div>
          </details>
        </div>
      </div>
      <p v-if="!edges.length" class="py-16 text-center text-sm text-slate-500">
        لا توجد علاقات قانونية محفوظة لبناء الشجرة.
      </p>
    </div>

    <!-- View 2: Path View (Staged Progressive Path View matching CEP_VISUALIZE_PATH_VIEW_COMPONENT_REFERENCE.png) -->
    <div
      v-else-if="view === 'Path'"
      class="space-y-4"
      aria-label="Path canonical relationship view"
    >
      <!-- Progressive Path Chains -->
      <div class="space-y-3">
        <article
          v-for="(edge, index) in edges"
          :key="edge.id"
          class="rounded-2xl border border-slate-800/80 bg-slate-950/60 p-5 shadow-lg"
        >
          <div class="mb-4 flex items-center justify-between border-b border-slate-800/80 pb-3">
            <div class="flex items-center gap-2">
              <span
                class="flex h-5 w-5 items-center justify-center rounded-full bg-cyan-500 text-xs font-bold text-slate-950"
                >{{ index + 1 }}</span
              >
              <p class="text-xs font-bold text-slate-300">خطوة مسار مسجلة {{ index + 1 }}</p>
            </div>
            <span class="font-mono text-[10px] text-slate-500">rev {{ edge.revision }}</span>
          </div>

          <div
            dir="ltr"
            class="grid items-center gap-4 xl:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]"
          >
            <div
              dir="rtl"
              class="rounded-xl border border-indigo-500/40 bg-indigo-950/30 p-4 shadow-sm"
            >
              <span
                class="mb-1 block text-[10px] font-bold tracking-wider text-indigo-400 uppercase"
                >المنطلق</span
              >
              <p class="text-sm font-bold text-slate-200">
                {{ nodeById.get(edge.from)?.label ?? edge.from }}
              </p>
              <bdi dir="ltr" class="mt-1 block font-mono text-xs text-indigo-300">
                {{ nodeById.get(edge.from)?.technical_label ?? edge.from }}
              </bdi>
            </div>

            <div class="flex flex-col items-center gap-1 py-2">
              <span
                class="rounded-full border border-amber-500/40 bg-amber-950/60 px-3 py-1 font-mono text-xs font-bold text-amber-300 shadow-sm"
              >
                → {{ edge.type }} →
              </span>
            </div>

            <div
              dir="rtl"
              class="rounded-xl border border-cyan-500/40 bg-cyan-950/30 p-4 shadow-sm"
            >
              <span class="mb-1 block text-[10px] font-bold tracking-wider text-cyan-400 uppercase"
                >المصب / الهدف</span
              >
              <p class="text-sm font-bold text-slate-200">
                {{ nodeById.get(edge.to)?.label ?? edge.to }}
              </p>
              <bdi dir="ltr" class="mt-1 block font-mono text-xs text-cyan-300">
                {{ nodeById.get(edge.to)?.technical_label ?? edge.to }}
              </bdi>
            </div>
          </div>
        </article>
      </div>

      <p v-if="!edges.length" class="py-16 text-center text-sm text-slate-500">
        لا يوجد مسار canonical محفوظ؛ لن يتم تركيب مسار افتراضي.
      </p>
    </div>

    <!-- View 3: Focused Graph View (matching CEP_VISUALIZE_FOCUSED_GRAPH_RELATIONSHIP_COMPONENT_REFERENCE.png) -->
    <div v-else-if="view === 'Graph'" class="space-y-4" aria-label="Graph canonical projection">
      <div class="rounded-2xl border border-slate-800/80 bg-slate-950/60 p-5 shadow-lg">
        <div class="mb-4 flex items-center justify-between border-b border-slate-800/80 pb-3">
          <div class="flex items-center gap-2">
            <span class="text-sm text-cyan-400">🕸️</span>
            <h3 class="text-xs font-bold text-slate-200">
              التمثيل البصري للعقد والعلاقات (Focused Graph Canvas)
            </h3>
          </div>
          <span
            class="rounded-full border border-slate-800 bg-slate-900 px-2.5 py-0.5 font-mono text-[10px] text-slate-400"
          >
            {{ nodes.length }} Nodes · {{ edges.length }} Edges
          </span>
        </div>

        <!-- VIS-GRAPH-01: focus graph topology around active/selected canonical node -->
        <div class="mb-4 text-xs text-slate-400">
          انقر على أي عقدة لتركيز الرسم البياني عليها وعرض السياق (VIS-CONTEXT-01).
        </div>

        <div class="space-y-6">
          <article
            v-for="edge in edges"
            :key="edge.id"
            dir="ltr"
            class="grid items-stretch gap-3 transition-opacity duration-200 xl:grid-cols-[minmax(0,1fr)_140px_minmax(0,1fr)]"
            :class="[
              selectedNodeId && edge.from !== selectedNodeId && edge.to !== selectedNodeId
                ? 'opacity-30 grayscale'
                : 'opacity-100',
            ]"
          >
            <button
              dir="rtl"
              type="button"
              class="focus-ring cursor-pointer rounded-2xl border p-4 text-start shadow-md transition hover:border-indigo-400/60 hover:bg-indigo-900/40"
              :class="
                edge.from === selectedNodeId
                  ? 'border-indigo-400 bg-indigo-900/50 ring-2 ring-indigo-500/50'
                  : 'border-indigo-500/40 bg-indigo-950/30'
              "
              :aria-pressed="edge.from === selectedNodeId"
              @click="emit('selectNode', edge.from === selectedNodeId ? null : edge.from)"
            >
              <span
                class="rounded-full bg-indigo-500/20 px-2 py-0.5 font-mono text-[9px] font-bold text-indigo-300"
              >
                {{ nodeById.get(edge.from)?.kind ?? 'node' }}
              </span>
              <p class="mt-3 text-sm font-bold text-slate-100">
                {{ nodeById.get(edge.from)?.label ?? edge.from }}
              </p>
              <bdi dir="ltr" class="mt-1 block font-mono text-xs text-slate-400">
                {{ nodeById.get(edge.from)?.technical_label ?? edge.from }}
              </bdi>
              <span
                v-if="observationLabel(observationFor(edge.from))"
                class="mt-3 inline-block rounded-full border border-emerald-500/40 bg-emerald-950/70 px-2 py-0.5 text-xs text-emerald-300"
              >
                {{ observationLabel(observationFor(edge.from)) }}
              </span>
            </button>

            <div class="flex flex-col items-center justify-center gap-2 text-center">
              <div class="h-px w-full bg-gradient-to-r from-indigo-500 via-amber-400 to-cyan-500" />
              <bdi
                dir="ltr"
                class="rounded-full border border-amber-500/40 bg-amber-950/70 px-3 py-1 font-mono text-[10px] font-bold text-amber-300"
              >
                {{ edge.type }}
              </bdi>
              <bdi dir="ltr" class="font-mono text-[9px] text-slate-500"
                >rev {{ edge.revision }}</bdi
              >
            </div>

            <button
              dir="rtl"
              type="button"
              class="focus-ring cursor-pointer rounded-2xl border p-4 text-start shadow-md transition hover:border-cyan-400/60 hover:bg-cyan-900/40"
              :class="
                edge.to === selectedNodeId
                  ? 'border-cyan-400 bg-cyan-900/50 ring-2 ring-cyan-500/50'
                  : 'border-cyan-500/40 bg-cyan-950/30'
              "
              :aria-pressed="edge.to === selectedNodeId"
              @click="emit('selectNode', edge.to === selectedNodeId ? null : edge.to)"
            >
              <span
                class="rounded-full bg-cyan-500/20 px-2 py-0.5 font-mono text-[9px] font-bold text-cyan-300"
              >
                {{ nodeById.get(edge.to)?.kind ?? 'node' }}
              </span>
              <p class="mt-3 text-sm font-bold text-slate-100">
                {{ nodeById.get(edge.to)?.label ?? edge.to }}
              </p>
              <bdi dir="ltr" class="mt-1 block font-mono text-xs text-slate-400">
                {{ nodeById.get(edge.to)?.technical_label ?? edge.to }}
              </bdi>
              <span
                v-if="observationLabel(observationFor(edge.to))"
                class="mt-3 inline-block rounded-full border border-emerald-500/40 bg-emerald-950/70 px-2 py-0.5 text-xs text-emerald-300"
              >
                {{ observationLabel(observationFor(edge.to)) }}
              </span>
            </button>
          </article>
        </div>
      </div>
      <div v-if="!edges.length" class="rounded-xl border border-dashed border-slate-800 p-4">
        <div class="grid gap-3 sm:grid-cols-2">
          <article
            v-for="node in nodes"
            :key="node.id"
            class="rounded-xl border border-slate-800 bg-slate-950/50 p-3"
          >
            <p class="text-sm font-bold text-slate-200">{{ node.label }}</p>
            <bdi dir="ltr" class="mt-1 block font-mono text-xs text-slate-400">
              {{ node.technical_label }}
            </bdi>
          </article>
        </div>
        <p class="mt-3 text-center text-xs text-slate-500">
          لا توجد علاقة كاملة ضمن المرشح الحالي؛ تظهر العقد دون إنشاء وصلات افتراضية.
        </p>
      </div>
    </div>

    <!-- View 4: Canvas Representation -->
    <div
      v-else
      class="rounded-2xl border border-dashed border-slate-700 bg-slate-950/40 p-6 shadow-inner"
      aria-label="Canvas representation of canonical nodes"
    >
      <div class="mb-4 flex items-center justify-between border-b border-slate-800/80 pb-3">
        <h3 class="text-xs font-bold text-slate-300">
          مواقع العقد على لوحة العرض (Canvas Coordinates)
        </h3>
        <span class="font-mono text-[10px] text-indigo-400">CANVAS MODE</span>
      </div>
      <div class="grid min-h-80 content-center gap-4 md:grid-cols-2">
        <article
          v-for="node in capabilityNodes"
          :key="node.id"
          class="rounded-xl border border-indigo-800/60 bg-indigo-950/20 p-4 shadow-sm"
        >
          <p class="font-mono text-[10px] font-bold text-slate-500" dir="ltr">
            {{ visualPositions?.[node.id] ? 'CANVAS POSITION' : 'POSITION UNAVAILABLE' }}
          </p>
          <bdi dir="ltr" class="mt-1 block font-mono text-sm font-bold text-indigo-200">
            {{ node.technical_label }}
          </bdi>
          <bdi
            v-if="visualPositions?.[node.id]"
            dir="ltr"
            class="mt-2 inline-block rounded border border-slate-800 bg-slate-900 px-2 py-1 font-mono text-[11px] text-cyan-300"
          >
            x={{ visualPositions[node.id].x }}, y={{ visualPositions[node.id].y }}
          </bdi>
        </article>
        <article
          v-for="node in unitNodes"
          :key="node.id"
          class="rounded-xl border border-cyan-800 bg-cyan-950/25 p-5 text-center md:col-span-2"
        >
          <p class="font-bold">{{ node.label }}</p>
          <bdi dir="ltr" class="mt-1 block font-mono text-xs text-cyan-200">
            {{ node.technical_label }}
          </bdi>
        </article>
      </div>
      <p class="mt-5 border-t border-slate-800 pt-4 text-xs leading-6 text-slate-500">
        تحريك عقدة داخل <bdi dir="ltr">Canvas</bdi> يغيّر موضع العرض فقط؛ ولا يغيّر
        <bdi dir="ltr">canonical containment</bdi> أو العلاقات القانونية ضمنيًا.
      </p>
    </div>
  </section>
</template>

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

const pathStages = computed(() => [
  { id: 'foundations', title_ar: 'المتطلبات والأساسيات', title_en: '01 Foundations' },
  { id: 'core', title_ar: 'القدرة الأساسية', title_en: '02 Core Capability' },
  { id: 'advanced', title_ar: 'المفاهيم المتقدمة والتخفيف', title_en: '03 Advanced / Mitigation' },
  { id: 'validation', title_ar: 'التحقق والمختبرات', title_en: '04 Validation / Labs' },
]);
</script>

<template>
  <section aria-live="polite">
    <!-- View 1: Tree View -->
    <div v-if="view === 'Tree'" class="space-y-4" aria-label="Tree canonical relationship view">
      <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
        <p class="text-xs text-slate-400 font-medium mb-3">شجرة العلاقات الهيكلية القانونية (Canonical Hierarchy):</p>
        <div class="space-y-3">
          <article
            v-for="edge in edges"
            :key="edge.id"
            class="grid items-center gap-4 rounded-xl border border-slate-800/80 bg-slate-900/60 p-4 shadow-sm md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] hover:border-slate-700 transition"
          >
            <div class="rounded-xl border border-indigo-500/40 bg-indigo-950/30 p-3 text-center">
              <div class="flex items-center justify-center gap-1.5 text-xs text-indigo-300 font-semibold mb-1">
                <span>📁</span>
                <span>المصدر (From)</span>
              </div>
              <bdi dir="ltr" class="font-mono text-sm font-bold text-indigo-200 block">
                {{ nodeById.get(edge.from)?.technical_label ?? edge.from }}
              </bdi>
              <span
                v-if="observationLabel(observationFor(edge.from))"
                class="mt-2 inline-block rounded-full bg-emerald-950/80 border border-emerald-500/40 px-2.5 py-0.5 text-xs text-emerald-300 font-medium"
              >
                {{ observationLabel(observationFor(edge.from)) }}
              </span>
            </div>
            <div class="text-center py-2">
              <span class="inline-flex items-center gap-1 rounded-full border border-amber-500/40 bg-amber-950/60 px-3 py-1 text-xs font-mono font-bold text-amber-300 shadow-sm">
                <span>⚡</span>
                <bdi dir="ltr">{{ edge.type }}</bdi>
              </span>
              <bdi dir="ltr" class="mt-1.5 block font-mono text-[10px] text-slate-500">
                rev {{ edge.revision }}
              </bdi>
            </div>
            <div class="rounded-xl border border-cyan-500/40 bg-cyan-950/30 p-3 text-center">
              <div class="flex items-center justify-center gap-1.5 text-xs text-cyan-300 font-semibold mb-1">
                <span>🎯</span>
                <span>الهدف (To)</span>
              </div>
              <p class="font-bold text-slate-100 text-sm">{{ nodeById.get(edge.to)?.label ?? edge.to }}</p>
              <bdi dir="ltr" class="mt-1 block font-mono text-xs text-cyan-300">
                {{ nodeById.get(edge.to)?.technical_label ?? edge.to }}
              </bdi>
              <span
                v-if="observationLabel(observationFor(edge.to))"
                class="mt-2 inline-block rounded-full bg-emerald-950/80 border border-emerald-500/40 px-2.5 py-0.5 text-xs text-emerald-300 font-medium"
              >
                {{ observationLabel(observationFor(edge.to)) }}
              </span>
            </div>
          </article>
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
      <!-- Staged Columns Header -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div
          v-for="stage in pathStages"
          :key="stage.id"
          class="rounded-xl border border-slate-800 bg-slate-950/70 p-3"
        >
          <span class="font-mono text-[10px] font-bold text-cyan-400 block" dir="ltr">{{ stage.title_en }}</span>
          <h4 class="text-xs font-bold text-slate-200 mt-1">{{ stage.title_ar }}</h4>
        </div>
      </div>

      <!-- Progressive Path Chains -->
      <div class="space-y-3">
        <article
          v-for="(edge, index) in edges"
          :key="edge.id"
          class="rounded-2xl border border-slate-800/80 bg-slate-950/60 p-5 shadow-lg"
        >
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3 mb-4">
            <div class="flex items-center gap-2">
              <span class="flex h-5 w-5 items-center justify-center rounded-full bg-cyan-500 text-slate-950 text-xs font-bold">{{ index + 1 }}</span>
              <p class="text-xs font-bold text-slate-300">
                مسار قانوني مستقل {{ index + 1 }} — مشتق من علاقة canonical واحدة، وليس سلسلة مخترعة.
              </p>
            </div>
            <span class="font-mono text-[10px] text-slate-500">rev {{ edge.revision }}</span>
          </div>

          <div class="grid items-center gap-4 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">
            <div class="rounded-xl border border-indigo-500/40 bg-indigo-950/30 p-4 shadow-sm">
              <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider block mb-1">المنطلق</span>
              <p class="font-bold text-slate-200 text-sm">{{ nodeById.get(edge.from)?.label ?? edge.from }}</p>
              <bdi dir="ltr" class="mt-1 block font-mono text-xs text-indigo-300">
                {{ nodeById.get(edge.from)?.technical_label ?? edge.from }}
              </bdi>
            </div>

            <div class="flex flex-col items-center gap-1 py-2">
              <span class="rounded-full border border-amber-500/40 bg-amber-950/60 px-3 py-1 font-mono text-xs font-bold text-amber-300 shadow-sm">
                → {{ edge.type }} →
              </span>
            </div>

            <div class="rounded-xl border border-cyan-500/40 bg-cyan-950/30 p-4 shadow-sm">
              <span class="text-[10px] font-bold text-cyan-400 uppercase tracking-wider block mb-1">المصب / الهدف</span>
              <p class="font-bold text-slate-200 text-sm">{{ nodeById.get(edge.to)?.label ?? edge.to }}</p>
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
    <div
      v-else-if="view === 'Graph'"
      class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]"
      aria-label="Graph canonical projection"
    >
      <!-- Interactive Graph Layout Canvas -->
      <div class="rounded-2xl border border-slate-800/80 bg-slate-950/60 p-5 shadow-lg">
        <div class="flex items-center justify-between border-b border-slate-800/80 pb-3 mb-4">
          <div class="flex items-center gap-2">
            <span class="text-cyan-400 text-sm">🕸️</span>
            <h3 class="text-xs font-bold text-slate-200">التمثيل البصري للعقد والعلاقات (Focused Graph Canvas)</h3>
          </div>
          <span class="rounded-full bg-slate-900 border border-slate-800 px-2.5 py-0.5 text-[10px] font-mono text-slate-400">
            {{ nodes.length }} Nodes · {{ edges.length }} Edges
          </span>
        </div>

        <div class="grid content-start gap-3 sm:grid-cols-2">
          <article
            v-for="node in nodes"
            :key="node.id"
            class="group relative rounded-2xl border p-4 transition-all duration-200 hover:scale-[1.01] shadow-md"
            :class="
              node.kind === 'knowledge_unit'
                ? 'border-cyan-500/40 bg-gradient-to-br from-cyan-950/30 to-slate-950/80 hover:border-cyan-400'
                : 'border-indigo-500/40 bg-gradient-to-br from-indigo-950/30 to-slate-950/80 hover:border-indigo-400'
            "
          >
            <div class="flex items-start justify-between gap-2">
              <span
                class="rounded-full px-2 py-0.5 font-mono text-[9px] font-bold uppercase tracking-wider"
                :class="node.kind === 'knowledge_unit' ? 'bg-cyan-500/20 text-cyan-300' : 'bg-indigo-500/20 text-indigo-300'"
              >
                {{ node.kind === 'knowledge_unit' ? 'Knowledge Unit' : 'Capability' }}
              </span>
              <span class="text-slate-600 group-hover:text-slate-400 transition text-xs">⛶</span>
            </div>
            <p class="mt-2.5 font-bold text-slate-100 text-sm">{{ node.label }}</p>
            <bdi dir="ltr" class="mt-1 block font-mono text-xs text-slate-400">
              {{ node.technical_label }}
            </bdi>
            <span
              v-if="observationLabel(observationFor(node.id))"
              class="mt-3 inline-block rounded-full border border-emerald-500/40 bg-emerald-950/80 px-2.5 py-0.5 text-xs text-emerald-300 font-medium"
            >
              {{ observationLabel(observationFor(node.id)) }}
            </span>
          </article>
        </div>
      </div>

      <!-- Right Graph Relationships List -->
      <div class="rounded-2xl border border-slate-800/80 bg-slate-950/60 p-4 shadow-lg">
        <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
          <h3 class="text-xs font-bold text-slate-300" dir="ltr">Canonical edges</h3>
          <span class="font-mono text-[10px] text-cyan-400 font-bold">{{ edges.length }}</span>
        </div>
        <ol class="mt-3 space-y-2.5 max-h-[500px] overflow-y-auto pr-0.5">
          <li
            v-for="edge in edges"
            :key="edge.id"
            class="rounded-xl border border-slate-800/80 bg-slate-900/60 p-3 hover:border-slate-700 transition"
          >
            <div class="flex items-center justify-between text-[11px] mb-1">
              <span class="font-semibold text-amber-300 font-mono">{{ edge.type }}</span>
              <span class="font-mono text-[9px] text-slate-500">rev {{ edge.revision }}</span>
            </div>
            <bdi dir="ltr" class="block font-mono text-[11px] text-slate-300 truncate">
              {{ edge.from }} → {{ edge.to }}
            </bdi>
          </li>
        </ol>
        <p v-if="!edges.length" class="mt-4 text-xs text-center text-slate-500">لا توجد Edges محفوظة.</p>
      </div>
    </div>

    <!-- View 4: Canvas Representation -->
    <div
      v-else
      class="rounded-2xl border border-dashed border-slate-700 bg-slate-950/40 p-6 shadow-inner"
      aria-label="Canvas representation of canonical nodes"
    >
      <div class="flex items-center justify-between border-b border-slate-800/80 pb-3 mb-4">
        <h3 class="text-xs font-bold text-slate-300">مواقع العقد على لوحة العرض (Canvas Coordinates)</h3>
        <span class="font-mono text-[10px] text-indigo-400">CANVAS MODE</span>
      </div>
      <div class="grid min-h-80 content-center gap-4 md:grid-cols-2">
        <article
          v-for="node in capabilityNodes"
          :key="node.id"
          class="rounded-xl border border-indigo-800/60 bg-indigo-950/20 p-4 shadow-sm"
        >
          <p class="text-[10px] font-bold text-slate-500 uppercase font-mono" dir="ltr">CANVAS POSITION</p>
          <bdi dir="ltr" class="mt-1 block font-mono text-sm font-bold text-indigo-200">
            {{ node.technical_label }}
          </bdi>
          <bdi
            v-if="visualPositions?.[node.id]"
            dir="ltr"
            class="mt-2 inline-block rounded bg-slate-900 border border-slate-800 px-2 py-1 font-mono text-[11px] text-cyan-300"
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

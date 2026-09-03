<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { OverlayLayer, VisualEdge, VisualNode, VisualSelection } from '../types';
import { layoutFocusedGraph, observationTargets } from '../viewModels';
import { useSvgViewport } from '../useSvgViewport';

const props = defineProps<{
  nodes: VisualNode[];
  edges: VisualEdge[];
  selection: VisualSelection | null;
  overlayLayer: OverlayLayer | null;
}>();

const emit = defineEmits<{
  select: [selection: VisualSelection];
  cameraChange: [percent: number];
}>();

const selectedNodeId = computed(() =>
  props.selection?.kind === 'node' ? props.selection.id : null,
);
const layout = computed(() => layoutFocusedGraph(props.nodes, props.edges, selectedNodeId.value));
const bounds = computed(() => layout.value.bounds);
const { scale, viewBox, zoomIn, zoomOut, fit, pan, onKeydown, onWheel } = useSvgViewport(bounds);
const overlayTargets = computed(() => observationTargets(props.overlayLayer, 'Graph'));
const nodeById = computed(() => new Map(props.nodes.map((node) => [node.id, node])));
const dragging = ref<{ x: number; y: number } | null>(null);

watch(scale, (value) => emit('cameraChange', Math.round(value * 100)), { immediate: true });

const edgePath = (edge: VisualEdge) => {
  const from = layout.value.positions[edge.from];
  const to = layout.value.positions[edge.to];
  if (!from || !to) return '';
  const bend = Math.max(40, Math.abs(to.x - from.x) * 0.35);
  return `M ${from.x} ${from.y} C ${from.x + bend} ${from.y}, ${to.x - bend} ${to.y}, ${to.x} ${to.y}`;
};
const edgeMidpoint = (edge: VisualEdge) => {
  const from = layout.value.positions[edge.from] ?? { x: 0, y: 0 };
  const to = layout.value.positions[edge.to] ?? { x: 0, y: 0 };
  return { x: (from.x + to.x) / 2, y: (from.y + to.y) / 2 };
};
const edgeColor = (edge: VisualEdge) => {
  if (overlayTargets.value.edges.has(edge.id)) return '#34d399';
  if (edge.semantic === 'prerequisite') return '#a78bfa';
  if (edge.semantic === 'containment') return '#22d3ee';
  return '#64748b';
};
const selectNode = (id: string) => emit('select', { kind: 'node', id });
const onPanStart = (event: PointerEvent) => {
  if (event.target !== event.currentTarget) return;
  dragging.value = { x: event.clientX, y: event.clientY };
  (event.currentTarget as SVGElement).setPointerCapture?.(event.pointerId);
};
const onPanMove = (event: PointerEvent) => {
  if (!dragging.value) return;
  pan(dragging.value.x - event.clientX, dragging.value.y - event.clientY);
  dragging.value = { x: event.clientX, y: event.clientY };
};
const onPanEnd = () => {
  dragging.value = null;
};

defineExpose({ zoomIn, zoomOut, fit });
</script>

<template>
  <section class="min-h-[560px]" aria-label="Graph typed relationship world">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
      <div>
        <p class="text-xs font-bold text-slate-200">عالم العلاقات المركّز</p>
        <p class="mt-1 text-[10px] text-slate-500">
          عقدة مرئية واحدة لكل هوية قانونية؛ الأسهم تتبع <bdi dir="ltr">from → to</bdi> ولا تنعكس مع
          اتجاه الواجهة.
        </p>
      </div>
      <bdi
        dir="ltr"
        class="rounded-full border border-slate-800 px-2 py-1 font-mono text-[9px] text-slate-400"
      >
        GRAPH · {{ nodes.length }} NODES · {{ edges.length }} EDGES
      </bdi>
    </div>
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-[#050d18] shadow-inner">
      <svg
        class="h-[560px] w-full touch-none outline-none"
        :viewBox="`${viewBox.x} ${viewBox.y} ${viewBox.width} ${viewBox.height}`"
        preserveAspectRatio="xMidYMid meet"
        tabindex="0"
        role="application"
        aria-label="رسم علاقات تفاعلي؛ استخدم الأسهم للتحريك و زائد أو ناقص للتكبير والتصغير و صفر للملاءمة"
        @keydown="onKeydown"
        @wheel="onWheel"
        @pointerdown="onPanStart"
        @pointermove="onPanMove"
        @pointerup="onPanEnd"
        @pointercancel="onPanEnd"
      >
        <defs>
          <marker
            id="visualize-arrow"
            markerWidth="8"
            markerHeight="8"
            refX="7"
            refY="4"
            orient="auto"
            markerUnits="strokeWidth"
          >
            <path d="M0,0 L8,4 L0,8 z" fill="context-stroke" />
          </marker>
          <radialGradient id="graph-halo">
            <stop offset="0" stop-color="#0e7490" stop-opacity=".18" />
            <stop offset="1" stop-color="#020617" stop-opacity="0" />
          </radialGradient>
        </defs>
        <circle
          v-if="selectedNodeId && layout.positions[selectedNodeId]"
          :cx="layout.positions[selectedNodeId].x"
          :cy="layout.positions[selectedNodeId].y"
          r="190"
          fill="url(#graph-halo)"
          aria-hidden="true"
        />

        <g v-for="edge in edges" :key="edge.id">
          <path
            :d="edgePath(edge)"
            fill="none"
            :stroke="edgeColor(edge)"
            :stroke-width="
              overlayTargets.edges.has(edge.id) ||
              (selection?.kind === 'edge' && selection.id === edge.id)
                ? 4
                : 2
            "
            :stroke-dasharray="edge.semantic === 'prerequisite' ? '7 5' : undefined"
            marker-end="url(#visualize-arrow)"
            opacity=".88"
          />
          <path
            :d="edgePath(edge)"
            fill="none"
            stroke="transparent"
            stroke-width="18"
            tabindex="0"
            role="button"
            :aria-label="`${nodeById.get(edge.from)?.label ?? edge.from}، ${edge.type}، ${nodeById.get(edge.to)?.label ?? edge.to}`"
            :aria-pressed="selection?.kind === 'edge' && selection.id === edge.id"
            @click="emit('select', { kind: 'edge', id: edge.id })"
            @keydown.enter.prevent="emit('select', { kind: 'edge', id: edge.id })"
            @keydown.space.prevent="emit('select', { kind: 'edge', id: edge.id })"
          />
          <g
            :transform="`translate(${edgeMidpoint(edge).x}, ${edgeMidpoint(edge).y})`"
            pointer-events="none"
          >
            <rect
              x="-48"
              y="-11"
              width="96"
              height="22"
              rx="11"
              fill="#0b1322"
              :stroke="edgeColor(edge)"
              stroke-opacity=".7"
            />
            <text
              text-anchor="middle"
              dominant-baseline="middle"
              fill="#cbd5e1"
              font-size="9"
              font-family="monospace"
            >
              {{ edge.type }}
            </text>
          </g>
        </g>

        <foreignObject
          v-for="node in nodes"
          :key="node.id"
          :x="(layout.positions[node.id]?.x ?? 0) - 86"
          :y="(layout.positions[node.id]?.y ?? 0) - 39"
          width="172"
          height="78"
        >
          <button
            xmlns="http://www.w3.org/1999/xhtml"
            dir="rtl"
            type="button"
            class="focus-ring h-full w-full overflow-hidden rounded-xl border px-3 py-2 text-start shadow-xl transition"
            :class="[
              selection?.kind === 'node' && selection.id === node.id
                ? 'border-cyan-300 bg-cyan-950/95 ring-2 ring-cyan-400/50'
                : node.kind === 'knowledge_unit'
                  ? 'border-violet-500/60 bg-violet-950/90 hover:border-violet-300'
                  : 'border-slate-600 bg-[#101d31]/95 hover:border-cyan-500',
              overlayTargets.nodes.has(node.id) ? 'shadow-[0_0_24px_rgba(52,211,153,.35)]' : '',
            ]"
            :aria-pressed="selection?.kind === 'node' && selection.id === node.id"
            @click="selectNode(node.id)"
          >
            <span dir="auto" class="block truncate text-[11px] font-bold text-slate-100">{{
              node.label
            }}</span>
            <bdi dir="ltr" class="mt-1 block truncate font-mono text-[8px] text-slate-400">
              {{ node.technical_label }}
            </bdi>
            <span class="mt-1 block text-[8px] font-bold text-cyan-300">{{ node.kind }}</span>
          </button>
        </foreignObject>
      </svg>
    </div>
  </section>
</template>

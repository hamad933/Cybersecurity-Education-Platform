<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type {
  OverlayLayer,
  VisualBounds,
  VisualEdge,
  VisualNode,
  VisualPoint,
  VisualSelection,
} from '../types';
import { layoutFocusedGraph, observationTargets } from '../viewModels';
import { useSvgViewport } from '../useSvgViewport';

const props = defineProps<{
  nodes: VisualNode[];
  edges: VisualEdge[];
  selection: VisualSelection | null;
  overlayLayer: OverlayLayer | null;
  visualPositions: Record<string, VisualPoint>;
}>();

const emit = defineEmits<{
  select: [selection: VisualSelection];
  moveNode: [payload: { id: string; x: number; y: number; method: 'pointer' | 'keyboard' }];
  cameraChange: [percent: number];
}>();

const defaultLayout = computed(() => layoutFocusedGraph(props.nodes, props.edges, null));
const positions = computed<Record<string, VisualPoint>>(() =>
  Object.fromEntries(
    props.nodes.map((node) => [
      node.id,
      props.visualPositions[node.id] ??
        defaultLayout.value.positions[node.id] ?? { x: 480, y: 280 },
    ]),
  ),
);
const bounds = computed<VisualBounds>(() => {
  const values = Object.values(positions.value);
  if (!values.length) return { x: 0, y: 0, width: 960, height: 560 };
  const xs = values.map((point) => point.x);
  const ys = values.map((point) => point.y);
  const minX = Math.min(...xs) - 130;
  const minY = Math.min(...ys) - 90;
  return {
    x: minX,
    y: minY,
    width: Math.max(760, Math.max(...xs) - minX + 130),
    height: Math.max(520, Math.max(...ys) - minY + 100),
  };
});
const { scale, viewBox, zoomIn, zoomOut, fit, onKeydown, onWheel } = useSvgViewport(bounds);
const overlayTargets = computed(() => observationTargets(props.overlayLayer, 'Canvas'));
const svg = ref<SVGElement | null>(null);
const drag = ref<{ id: string; clientX: number; clientY: number; origin: VisualPoint } | null>(
  null,
);

watch(scale, (value) => emit('cameraChange', Math.round(value * 100)), { immediate: true });

const line = (edge: VisualEdge) => ({
  from: positions.value[edge.from] ?? { x: 0, y: 0 },
  to: positions.value[edge.to] ?? { x: 0, y: 0 },
});
const beginDrag = (node: VisualNode, event: PointerEvent) => {
  const origin = positions.value[node.id];
  if (!origin) return;
  drag.value = { id: node.id, clientX: event.clientX, clientY: event.clientY, origin };
  (event.currentTarget as Element).setPointerCapture?.(event.pointerId);
};
const moveDrag = (event: PointerEvent) => {
  if (!drag.value || !svg.value) return;
  const rect = svg.value.getBoundingClientRect();
  const ratioX = viewBox.value.width / Math.max(1, rect.width);
  const ratioY = viewBox.value.height / Math.max(1, rect.height);
  emit('moveNode', {
    id: drag.value.id,
    x: Math.round(drag.value.origin.x + (event.clientX - drag.value.clientX) * ratioX),
    y: Math.round(drag.value.origin.y + (event.clientY - drag.value.clientY) * ratioY),
    method: 'pointer',
  });
};
const endDrag = () => {
  drag.value = null;
};
const moveWithKeyboard = (node: VisualNode, event: KeyboardEvent) => {
  const current = positions.value[node.id];
  if (!current) return;
  const step = event.shiftKey ? 48 : 16;
  let x = current.x;
  let y = current.y;
  if (event.key === 'ArrowLeft') x -= step;
  else if (event.key === 'ArrowRight') x += step;
  else if (event.key === 'ArrowUp') y -= step;
  else if (event.key === 'ArrowDown') y += step;
  else return;
  event.preventDefault();
  emit('moveNode', { id: node.id, x, y, method: 'keyboard' });
};

defineExpose({ zoomIn, zoomOut, fit });
</script>

<template>
  <section class="min-h-[560px]" aria-label="Canvas spatial representation view">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
      <div>
        <p class="text-xs font-bold text-slate-200">لوحة التمثيل المكاني</p>
        <p class="mt-1 text-[10px] text-slate-500">
          السحب أو مفاتيح الأسهم يغيّران موضع العرض في الجلسة فقط، ولا يغيّران الاحتواء أو العلاقات
          القانونية.
        </p>
      </div>
      <bdi
        dir="ltr"
        class="rounded-full border border-slate-800 px-2 py-1 font-mono text-[9px] text-slate-400"
      >
        CANVAS · REPRESENTATION ONLY
      </bdi>
    </div>
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-[#050d18] shadow-inner">
      <svg
        ref="svg"
        class="h-[560px] w-full touch-none outline-none"
        :viewBox="`${viewBox.x} ${viewBox.y} ${viewBox.width} ${viewBox.height}`"
        tabindex="0"
        role="application"
        aria-label="لوحة مكانية؛ استخدم الأسهم بعد تحديد العقدة لتحريكها، أو الأسهم على خلفية اللوحة لتحريك المشهد"
        @keydown="onKeydown"
        @wheel="onWheel"
        @pointermove="moveDrag"
        @pointerup="endDrag"
        @pointercancel="endDrag"
      >
        <defs>
          <pattern id="canvas-grid" width="32" height="32" patternUnits="userSpaceOnUse">
            <path
              d="M 32 0 L 0 0 0 32"
              fill="none"
              stroke="#1e293b"
              stroke-width="1"
              opacity=".45"
            />
          </pattern>
          <marker
            id="canvas-arrow"
            markerWidth="8"
            markerHeight="8"
            refX="7"
            refY="4"
            orient="auto"
          >
            <path d="M0,0 L8,4 L0,8 z" fill="context-stroke" />
          </marker>
        </defs>
        <rect
          :x="viewBox.x"
          :y="viewBox.y"
          :width="viewBox.width"
          :height="viewBox.height"
          fill="url(#canvas-grid)"
        />
        <g v-for="edge in edges" :key="edge.id">
          <line
            :x1="line(edge).from.x"
            :y1="line(edge).from.y"
            :x2="line(edge).to.x"
            :y2="line(edge).to.y"
            :stroke="
              overlayTargets.edges.has(edge.id)
                ? '#34d399'
                : edge.semantic === 'prerequisite'
                  ? '#a78bfa'
                  : '#475569'
            "
            :stroke-width="overlayTargets.edges.has(edge.id) ? 4 : 2"
            :stroke-dasharray="edge.semantic === 'prerequisite' ? '7 5' : undefined"
            marker-end="url(#canvas-arrow)"
          />
          <line
            :x1="line(edge).from.x"
            :y1="line(edge).from.y"
            :x2="line(edge).to.x"
            :y2="line(edge).to.y"
            stroke="transparent"
            stroke-width="18"
            tabindex="0"
            role="button"
            :aria-label="`تحديد علاقة ${edge.type}`"
            @click="emit('select', { kind: 'edge', id: edge.id })"
            @keydown.enter.prevent="emit('select', { kind: 'edge', id: edge.id })"
          />
        </g>
        <foreignObject
          v-for="node in nodes"
          :key="node.id"
          :x="positions[node.id].x - 82"
          :y="positions[node.id].y - 37"
          width="164"
          height="74"
        >
          <button
            xmlns="http://www.w3.org/1999/xhtml"
            dir="rtl"
            type="button"
            class="focus-ring h-full w-full cursor-move overflow-hidden rounded-xl border px-3 py-2 text-start shadow-xl"
            :class="[
              selection?.kind === 'node' && selection.id === node.id
                ? 'border-cyan-300 bg-cyan-950/95 ring-2 ring-cyan-400/50'
                : 'border-slate-600 bg-[#101d31]/95 hover:border-cyan-500',
              overlayTargets.nodes.has(node.id) ? 'shadow-[0_0_24px_rgba(52,211,153,.35)]' : '',
            ]"
            :aria-pressed="selection?.kind === 'node' && selection.id === node.id"
            :aria-label="`${node.label}؛ موضع العرض x ${positions[node.id].x} و y ${positions[node.id].y}`"
            @click="emit('select', { kind: 'node', id: node.id })"
            @pointerdown="beginDrag(node, $event)"
            @keydown.stop="moveWithKeyboard(node, $event)"
          >
            <span dir="auto" class="block truncate text-[11px] font-bold text-slate-100">{{
              node.label
            }}</span>
            <bdi dir="ltr" class="mt-1 block truncate font-mono text-[8px] text-slate-400">
              {{ node.technical_label }}
            </bdi>
            <bdi dir="ltr" class="mt-1 block font-mono text-[8px] text-cyan-300">
              x={{ positions[node.id].x }} · y={{ positions[node.id].y }}
            </bdi>
          </button>
        </foreignObject>
      </svg>
    </div>
  </section>
</template>

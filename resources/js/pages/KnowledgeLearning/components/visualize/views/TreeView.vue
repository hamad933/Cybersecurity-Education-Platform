<script setup lang="ts">
import { computed } from 'vue';
import type { OverlayLayer, VisualEdge, VisualNode, VisualSelection } from '../types';
import { buildTree, observationsForView } from '../viewModels';
import TreeBranch from './TreeBranch.vue';

const props = defineProps<{
  nodes: VisualNode[];
  edges: VisualEdge[];
  selection: VisualSelection | null;
  overlayLayer: OverlayLayer | null;
}>();

const emit = defineEmits<{
  select: [selection: VisualSelection];
}>();

const branches = computed(() => buildTree(props.nodes, props.edges));
const overlaySummary = computed(() => {
  const summary: Record<string, { inbound: number; outbound: number }> = {};
  const edgeById = new Map(props.edges.map((edge) => [edge.id, edge]));
  for (const observation of observationsForView(props.overlayLayer, 'Tree')) {
    if (observation.target.kind !== 'edge') continue;
    const edge = edgeById.get(observation.target.id);
    if (!edge) continue;
    summary[edge.from] ??= { inbound: 0, outbound: 0 };
    summary[edge.to] ??= { inbound: 0, outbound: 0 };
    summary[edge.from].outbound += 1;
    summary[edge.to].inbound += 1;
  }

  return Object.fromEntries(
    Object.entries(summary).map(([id, counts]) => [
      id,
      counts.inbound ? `${counts.inbound} متطلبات سابقة` : `متطلب لـ ${counts.outbound}`,
    ]),
  );
});
</script>

<template>
  <section class="min-h-[560px]" aria-label="Tree canonical containment view">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
      <div>
        <p class="text-xs font-bold text-slate-200">الهيكل القانوني المتدرج</p>
        <p class="mt-1 text-[10px] text-slate-500">
          فروع الاحتواء فقط؛ علاقات المتطلبات تظهر كطبقة تحليلية ولا تصبح آباءً للشجرة.
        </p>
      </div>
      <bdi
        dir="ltr"
        class="rounded-full border border-slate-800 px-2 py-1 font-mono text-[9px] text-slate-400"
      >
        TREE · {{ nodes.length }} UNIQUE NODES
      </bdi>
    </div>
    <ul
      v-if="branches.length"
      class="space-y-2 rounded-xl border border-slate-800 bg-[#07101d]/80 p-3"
    >
      <TreeBranch
        v-for="branch in branches"
        :key="branch.node.id"
        :branch="branch"
        :selected="selection"
        :overlay-summary="overlaySummary"
        @select="emit('select', $event)"
      />
    </ul>
    <div
      v-else
      class="grid min-h-[460px] place-items-center rounded-xl border border-dashed border-slate-800 text-center"
    >
      <div>
        <p class="text-sm font-bold text-slate-300">لا توجد علاقات احتواء حالية.</p>
        <p class="mt-2 text-xs text-slate-500">لن تُحوّل علاقات أخرى إلى بنية شجرية بديلة.</p>
      </div>
    </div>
  </section>
</template>

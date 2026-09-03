<script setup lang="ts">
import { computed } from 'vue';
import type { OverlayLayer, VisualEdge, VisualNode, VisualSelection } from '../types';
import { derivePathStages, observationTargets } from '../viewModels';

const props = defineProps<{
  nodes: VisualNode[];
  edges: VisualEdge[];
  selection: VisualSelection | null;
  overlayLayer: OverlayLayer | null;
}>();

const emit = defineEmits<{
  select: [selection: VisualSelection];
}>();

const stages = computed(() => derivePathStages(props.nodes, props.edges));
const overlayTargets = computed(() => observationTargets(props.overlayLayer, 'Path'));
</script>

<template>
  <section class="min-h-[560px]" aria-label="Path prerequisite progression view">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
      <div>
        <p class="text-xs font-bold text-slate-200">المسار المشتق من المتطلبات السابقة</p>
        <p class="mt-1 text-[10px] text-slate-500">
          ترتيب قانوني من علاقات <bdi dir="ltr">prerequisite</bdi> فقط؛ غياب بيانات الإنجاز لا يعني
          أن المرحلة مكتملة أو محجوبة.
        </p>
      </div>
      <bdi
        dir="ltr"
        class="rounded-full border border-slate-800 px-2 py-1 font-mono text-[9px] text-slate-400"
      >
        PATH · {{ stages.length }} STAGES
      </bdi>
    </div>

    <div
      v-if="stages.length"
      class="overflow-x-auto rounded-xl border border-slate-800 bg-[#07101d]/80 p-4"
    >
      <ol
        dir="ltr"
        class="grid min-w-[720px] items-stretch gap-3"
        :style="{ gridTemplateColumns: `repeat(${stages.length}, minmax(180px, 1fr))` }"
      >
        <li v-for="stage in stages" :key="stage.index" class="relative min-w-0">
          <div class="mb-3 flex items-center gap-2">
            <span
              class="grid h-6 w-6 place-items-center rounded-full bg-cyan-500 text-xs font-black text-[#04111d]"
            >
              {{ stage.index + 1 }}
            </span>
            <bdi dir="ltr" class="font-mono text-[9px] font-bold text-cyan-300">
              STAGE {{ stage.index + 1 }}
            </bdi>
          </div>
          <div
            v-if="stage.index > 0"
            class="absolute top-3 -left-3 h-px w-3 bg-cyan-500/70"
            aria-hidden="true"
          />
          <div class="space-y-2">
            <button
              v-for="node in stage.nodes"
              :key="node.id"
              dir="rtl"
              type="button"
              class="focus-ring block w-full rounded-xl border p-3 text-start transition"
              :class="[
                selection?.kind === 'node' && selection.id === node.id
                  ? 'border-cyan-400 bg-cyan-950/45 ring-1 ring-cyan-500/50'
                  : 'border-slate-700/80 bg-slate-900/80 hover:border-slate-600',
                overlayTargets.nodes.has(node.id) ? 'shadow-[0_0_0_1px_rgba(16,185,129,.55)]' : '',
              ]"
              :aria-pressed="selection?.kind === 'node' && selection.id === node.id"
              @click="emit('select', { kind: 'node', id: node.id })"
            >
              <span dir="auto" class="block text-xs font-bold text-slate-100">{{
                node.label
              }}</span>
              <bdi dir="ltr" class="mt-1 block font-mono text-[9px] text-slate-500">
                {{ node.technical_label }}
              </bdi>
            </button>
          </div>
          <div v-if="stage.incoming.length" class="mt-3 space-y-1.5 border-t border-slate-800 pt-2">
            <button
              v-for="edge in stage.incoming"
              :key="edge.id"
              type="button"
              class="focus-ring block w-full rounded-md border px-2 py-1.5 text-start font-mono text-[9px] transition"
              :class="
                overlayTargets.edges.has(edge.id) ||
                (selection?.kind === 'edge' && selection.id === edge.id)
                  ? 'border-emerald-500/60 bg-emerald-950/45 text-emerald-200'
                  : 'border-slate-800 bg-slate-950 text-slate-500 hover:text-slate-300'
              "
              :aria-pressed="selection?.kind === 'edge' && selection.id === edge.id"
              @click="emit('select', { kind: 'edge', id: edge.id })"
            >
              <bdi dir="ltr">{{ edge.from }} → {{ edge.to }}</bdi>
            </button>
          </div>
        </li>
      </ol>
    </div>

    <div
      v-else
      class="grid min-h-[460px] place-items-center rounded-xl border border-dashed border-slate-800 text-center"
    >
      <div>
        <p class="text-sm font-bold text-slate-300">لا يوجد مسار قانوني مرتب.</p>
        <p class="mt-2 max-w-sm text-xs leading-6 text-slate-500">
          لا تُرقّم علاقات الاحتواء أو العرض كخطوات تعلم عند غياب علاقة
          <bdi dir="ltr">prerequisite/pathway</bdi> صريحة.
        </p>
      </div>
    </div>
  </section>
</template>

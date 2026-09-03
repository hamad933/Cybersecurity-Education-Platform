<script setup lang="ts">
import type { TreeBranchModel } from '../viewModels';
import type { VisualSelection } from '../types';

defineOptions({ name: 'TreeBranch' });

defineProps<{
  branch: TreeBranchModel;
  selected: VisualSelection | null;
  overlaySummary: Record<string, string>;
  depth?: number;
}>();

const emit = defineEmits<{
  select: [selection: VisualSelection];
}>();
</script>

<template>
  <li class="relative">
    <div
      class="group flex min-w-0 items-center gap-2 rounded-lg border px-2.5 py-2 transition"
      :class="[
        selected?.kind === 'node' && selected.id === branch.node.id
          ? 'border-cyan-400/70 bg-cyan-950/45 ring-1 ring-cyan-500/40'
          : 'border-slate-800/80 bg-[#0a1424]/85 hover:border-slate-700',
        (depth ?? 0) > 0 ? 'ms-5' : '',
      ]"
    >
      <span
        class="h-2 w-2 shrink-0 rounded-sm"
        :class="
          branch.node.kind === 'knowledge_unit'
            ? 'bg-cyan-400'
            : branch.node.kind === 'capability'
              ? 'bg-violet-400'
              : 'bg-slate-500'
        "
        aria-hidden="true"
      />
      <button
        type="button"
        class="focus-ring min-w-0 flex-1 rounded-md px-1.5 py-1 text-start"
        :aria-pressed="selected?.kind === 'node' && selected.id === branch.node.id"
        @click="emit('select', { kind: 'node', id: branch.node.id })"
      >
        <span dir="auto" class="block truncate text-xs font-bold text-slate-100">
          {{ branch.node.label }}
        </span>
        <bdi dir="ltr" class="mt-0.5 block truncate font-mono text-[9px] text-slate-500">
          {{ branch.node.technical_label }}
        </bdi>
      </button>
      <button
        v-if="branch.edge"
        type="button"
        class="focus-ring rounded-full border border-slate-700 px-2 py-1 font-mono text-[9px] text-slate-400 hover:border-cyan-600 hover:text-cyan-200"
        :aria-pressed="selected?.kind === 'edge' && selected.id === branch.edge.id"
        :aria-label="`تحديد علاقة ${branch.edge.type}`"
        @click="emit('select', { kind: 'edge', id: branch.edge.id })"
      >
        {{ branch.edge.type }}
      </button>
      <span
        v-if="overlaySummary[branch.node.id]"
        class="rounded-full border border-emerald-500/40 bg-emerald-950/60 px-2 py-1 text-[9px] font-bold text-emerald-300"
      >
        {{ overlaySummary[branch.node.id] }}
      </span>
    </div>

    <details v-if="branch.children.length" open class="ms-3 border-s border-slate-800/90 ps-2">
      <summary
        class="focus-ring my-1 w-fit cursor-pointer rounded px-2 py-1 text-[10px] text-slate-500 hover:text-slate-300"
      >
        {{ branch.children.length }} فروع
      </summary>
      <ul class="space-y-1.5">
        <TreeBranch
          v-for="child in branch.children"
          :key="child.node.id"
          :branch="child"
          :selected="selected"
          :overlay-summary="overlaySummary"
          :depth="(depth ?? 0) + 1"
          @select="emit('select', $event)"
        />
      </ul>
    </details>
  </li>
</template>

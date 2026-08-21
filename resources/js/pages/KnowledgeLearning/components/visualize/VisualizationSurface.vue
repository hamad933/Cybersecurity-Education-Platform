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
  return typeof candidate === 'string' || typeof candidate === 'number' || typeof candidate === 'boolean'
    ? String(candidate)
    : null;
};
</script>

<template>
  <section aria-live="polite">
    <div v-if="view === 'Tree'" class="space-y-3" aria-label="Tree canonical relationship view">
      <article
        v-for="edge in edges"
        :key="edge.id"
        class="grid items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/35 p-4 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]"
      >
        <div class="rounded-lg border border-indigo-900 bg-indigo-950/25 p-3 text-center">
          <bdi dir="ltr" class="font-mono text-sm text-indigo-200">
            {{ nodeById.get(edge.from)?.technical_label ?? edge.from }}
          </bdi>
          <span
            v-if="observationLabel(observationFor(edge.from))"
            class="mt-2 block rounded bg-slate-900 px-2 py-1 text-xs text-emerald-300"
          >
            {{ observationLabel(observationFor(edge.from)) }}
          </span>
        </div>
        <div class="text-center">
          <bdi dir="ltr" class="font-mono text-[11px] text-amber-300">{{ edge.type }}</bdi>
          <bdi dir="ltr" class="mt-1 block font-mono text-[10px] text-slate-600">
            revision {{ edge.revision }}
          </bdi>
        </div>
        <div class="rounded-lg border border-cyan-800 bg-cyan-950/25 p-3 text-center">
          <p class="font-bold">{{ nodeById.get(edge.to)?.label ?? edge.to }}</p>
          <bdi dir="ltr" class="mt-1 block font-mono text-xs text-cyan-200">
            {{ nodeById.get(edge.to)?.technical_label ?? edge.to }}
          </bdi>
          <span
            v-if="observationLabel(observationFor(edge.to))"
            class="mt-2 block rounded bg-slate-900 px-2 py-1 text-xs text-emerald-300"
          >
            {{ observationLabel(observationFor(edge.to)) }}
          </span>
        </div>
      </article>
      <p v-if="!edges.length" class="py-16 text-center text-sm text-slate-500">
        لا توجد علاقات قانونية محفوظة لبناء الشجرة.
      </p>
    </div>

    <div v-else-if="view === 'Path'" class="space-y-4" aria-label="Path canonical relationship view">
      <article
        v-for="(edge, index) in edges"
        :key="edge.id"
        class="rounded-xl border border-slate-800 bg-slate-950/35 p-4"
      >
        <p class="mb-3 text-xs text-slate-500">
          مسار قانوني مستقل {{ index + 1 }} — مشتق من علاقة canonical واحدة، وليس سلسلة مخترعة.
        </p>
        <div class="grid items-center gap-3 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">
          <bdi
            dir="ltr"
            class="rounded-lg border border-indigo-900 px-3 py-3 text-center font-mono text-sm text-indigo-200"
          >
            {{ nodeById.get(edge.from)?.technical_label ?? edge.from }}
          </bdi>
          <bdi dir="ltr" class="text-center font-mono text-xs text-amber-300">
            → {{ edge.type }} →
          </bdi>
          <bdi
            dir="ltr"
            class="rounded-lg border border-cyan-900 px-3 py-3 text-center font-mono text-sm text-cyan-200"
          >
            {{ nodeById.get(edge.to)?.technical_label ?? edge.to }}
          </bdi>
        </div>
      </article>
      <p v-if="!edges.length" class="py-16 text-center text-sm text-slate-500">
        لا يوجد مسار canonical محفوظ؛ لن يتم تركيب مسار افتراضي.
      </p>
    </div>

    <div
      v-else-if="view === 'Graph'"
      class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_300px]"
      aria-label="Graph canonical projection"
    >
      <div class="grid content-start gap-3 sm:grid-cols-2">
        <article
          v-for="node in nodes"
          :key="node.id"
          class="rounded-xl border p-4"
          :class="
            node.kind === 'knowledge_unit'
              ? 'border-cyan-800 bg-cyan-950/20'
              : 'border-indigo-900 bg-indigo-950/20'
          "
        >
          <p class="font-bold">{{ node.label }}</p>
          <bdi dir="ltr" class="mt-1 block font-mono text-xs text-slate-400">
            {{ node.technical_label }}
          </bdi>
          <span
            v-if="observationLabel(observationFor(node.id))"
            class="mt-3 inline-block rounded border border-emerald-900 px-2 py-1 text-xs text-emerald-300"
          >
            {{ observationLabel(observationFor(node.id)) }}
          </span>
        </article>
      </div>
      <div class="rounded-xl border border-slate-800 bg-slate-950/40 p-4">
        <h3 class="text-xs font-bold text-slate-500" dir="ltr">Canonical edges</h3>
        <ol class="mt-3 space-y-3">
          <li v-for="edge in edges" :key="edge.id" class="rounded-lg border border-slate-800 p-3">
            <bdi dir="ltr" class="block font-mono text-[11px] text-slate-300">
              {{ edge.from }} → {{ edge.type }} → {{ edge.to }}
            </bdi>
          </li>
        </ol>
        <p v-if="!edges.length" class="mt-3 text-xs text-slate-500">لا توجد Edges محفوظة.</p>
      </div>
    </div>

    <div
      v-else
      class="rounded-2xl border border-dashed border-slate-700 bg-slate-950/35 p-5"
      aria-label="Canvas representation of canonical nodes"
    >
      <div class="grid min-h-80 content-center gap-4 md:grid-cols-2">
        <article
          v-for="node in capabilityNodes"
          :key="node.id"
          class="rounded-xl border border-indigo-900 bg-indigo-950/20 p-4"
        >
          <p class="text-[10px] text-slate-600" dir="ltr">CANVAS POSITION</p>
          <bdi dir="ltr" class="mt-1 block font-mono text-sm text-indigo-200">
            {{ node.technical_label }}
          </bdi>
          <bdi
            v-if="visualPositions?.[node.id]"
            dir="ltr"
            class="mt-2 block font-mono text-[10px] text-slate-500"
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

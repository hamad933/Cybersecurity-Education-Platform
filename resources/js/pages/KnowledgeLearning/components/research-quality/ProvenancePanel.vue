<script setup lang="ts">
import { computed } from 'vue';
import type { ProvenanceRow, Source } from './types';

const props = defineProps<{
  source: Source | null;
  provenance: ProvenanceRow[];
}>();

const row = computed(
  () => props.provenance.find((item) => item.source_id === props.source?.id) ?? null,
);
</script>

<template>
  <section aria-labelledby="provenance-heading">
    <div class="border-b border-slate-800/80 pb-3">
      <span
        class="font-mono text-[10px] font-bold tracking-widest text-slate-500 uppercase"
        dir="ltr"
      >
        PROVENANCE
      </span>
      <h2 id="provenance-heading" class="mt-1 text-xs font-bold text-slate-200">
        تتبّع المصدر المحدد
      </h2>
    </div>

    <div v-if="source" class="mt-4 space-y-4 text-xs">
      <section class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
        <p class="text-[11px] font-bold text-slate-400">Locator</p>
        <a
          v-if="source.exact_url"
          :href="source.exact_url"
          target="_blank"
          rel="noreferrer"
          dir="ltr"
          class="focus-ring mt-1.5 block text-left font-mono text-xs break-all text-cyan-300 underline"
        >
          {{ source.exact_url }}
        </a>
        <bdi
          v-else-if="source.relative_path"
          dir="ltr"
          class="mt-1.5 block font-mono text-xs break-all text-slate-300"
        >
          {{ source.relative_path }}
        </bdi>
        <p v-else class="mt-1.5 text-xs text-slate-500">لا يوجد locator محفوظ.</p>
      </section>

      <section class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
        <p class="text-[11px] font-bold text-slate-400">Integrity digest</p>
        <bdi dir="ltr" class="mt-1.5 block font-mono text-[10px] break-all text-emerald-400">
          sha256:{{ source.sha256 || 'missing' }}
        </bdi>
      </section>

      <section class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
        <div class="flex items-center justify-between gap-2">
          <p class="text-[11px] font-bold text-slate-400">Claim anchors</p>
          <span class="rounded bg-slate-900 px-2 py-0.5 font-mono text-[10px] text-cyan-400">
            {{ row?.traceability_state ?? 'unknown' }}
          </span>
        </div>
        <ul v-if="row?.anchors.length" class="mt-2.5 space-y-2">
          <li
            v-for="anchor in row.anchors"
            :key="`${anchor.claim_id}:${anchor.segment_ref}`"
            class="rounded-lg border border-slate-800/90 bg-slate-900/60 p-2.5"
          >
            <bdi dir="ltr" class="block font-mono text-[11px] font-bold text-cyan-300">
              {{ anchor.claim_id }}
            </bdi>
            <bdi dir="ltr" class="mt-1 block font-mono text-[10px] text-slate-400">
              {{ anchor.segment_ref }}
            </bdi>
          </li>
        </ul>
        <p v-else class="mt-2 text-xs text-slate-500">لا توجد anchors محفوظة لهذا المصدر.</p>
      </section>
    </div>
    <p v-else class="mt-4 text-xs text-slate-500">لا يوجد مصدر محدد.</p>
  </section>
</template>

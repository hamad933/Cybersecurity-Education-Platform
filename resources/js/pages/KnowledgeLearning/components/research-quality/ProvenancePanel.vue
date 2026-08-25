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
    <p class="text-[10px] font-bold tracking-[0.2em] text-slate-600" dir="ltr">PROVENANCE</p>
    <h2 id="provenance-heading" class="mt-1 text-sm font-black">تتبّع المصدر المحدد</h2>

    <div v-if="source" class="mt-4 space-y-5 text-sm">
      <section>
        <p class="text-xs text-slate-500">Locator</p>
        <a
          v-if="source.exact_url"
          :href="source.exact_url"
          target="_blank"
          rel="noreferrer"
          dir="ltr"
          class="focus-ring mt-2 block text-left text-xs break-all text-cyan-300 underline"
        >
          {{ source.exact_url }}
        </a>
        <bdi
          v-else-if="source.relative_path"
          dir="ltr"
          class="mt-2 block font-mono text-xs break-all text-slate-300"
        >
          {{ source.relative_path }}
        </bdi>
        <p v-else class="mt-2 text-xs text-slate-600">لا يوجد locator محفوظ.</p>
      </section>

      <section>
        <p class="text-xs text-slate-500">Integrity digest</p>
        <bdi dir="ltr" class="mt-2 block font-mono text-[10px] break-all text-slate-400">
          sha256:{{ source.sha256 || 'missing' }}
        </bdi>
      </section>

      <section>
        <div class="flex items-center justify-between gap-2">
          <p class="text-xs text-slate-500">Claim anchors</p>
          <bdi dir="ltr" class="text-[10px] text-slate-600">
            {{ row?.traceability_state ?? 'unknown' }}
          </bdi>
        </div>
        <ul v-if="row?.anchors.length" class="mt-2 space-y-2">
          <li
            v-for="anchor in row.anchors"
            :key="`${anchor.claim_id}:${anchor.segment_ref}`"
            class="rounded border border-slate-800 p-2"
          >
            <bdi dir="ltr" class="block font-mono text-[10px] text-cyan-200">
              {{ anchor.claim_id }}
            </bdi>
            <bdi dir="ltr" class="mt-1 block font-mono text-[10px] text-slate-500">
              {{ anchor.segment_ref }}
            </bdi>
          </li>
        </ul>
        <p v-else class="mt-2 text-xs text-slate-600">لا توجد anchors محفوظة لهذا المصدر.</p>
      </section>
    </div>
    <p v-else class="mt-4 text-xs text-slate-600">لا يوجد مصدر محدد.</p>
  </section>
</template>

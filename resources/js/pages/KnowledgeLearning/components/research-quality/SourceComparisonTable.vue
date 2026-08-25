<script setup lang="ts">
import type { ComparisonRow } from './types';

defineProps<{ rows: ComparisonRow[] }>();
</script>

<template>
  <section aria-labelledby="source-comparison-heading">
    <div
      class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-800/80 pb-3"
    >
      <div>
        <span
          class="font-mono text-[10px] font-bold tracking-widest text-slate-500 uppercase"
          dir="ltr"
        >
          COMPARE
        </span>
        <h2 id="source-comparison-heading" class="mt-1 text-base font-bold text-slate-100">
          مقارنة المصادر
        </h2>
      </div>
      <p class="text-xs text-slate-400">مقارنة وصفية، وليست ترتيبًا للحقيقة.</p>
    </div>

    <div
      v-if="rows.length"
      class="mt-4 overflow-x-auto rounded-2xl border border-slate-800/80 bg-slate-950/60 shadow-lg"
    >
      <table class="min-w-full divide-y divide-slate-800/80 text-xs">
        <thead class="bg-slate-900/80 text-[11px] font-bold text-slate-400">
          <tr>
            <th class="px-4 py-3.5 text-right">المصدر</th>
            <th class="px-4 py-3.5 text-right">Authority</th>
            <th class="px-4 py-3.5 text-right">Claims</th>
            <th class="px-4 py-3.5 text-right">Active revision</th>
            <th class="px-4 py-3.5 text-right">Anchors</th>
            <th class="px-4 py-3.5 text-right">Digest</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <tr v-for="row in rows" :key="row.source_id" class="transition hover:bg-slate-900/40">
            <td class="px-4 py-3">
              <p class="text-sm font-bold text-slate-200">{{ row.title }}</p>
              <bdi dir="ltr" class="mt-0.5 block font-mono text-[10px] text-slate-500">
                {{ row.source_id }}
              </bdi>
            </td>
            <td class="px-4 py-3">
              <span
                class="rounded-full border border-slate-800 bg-slate-900 px-2 py-0.5 font-mono text-[10px] text-slate-300"
              >
                {{ row.authority_class }}
              </span>
            </td>
            <td class="px-4 py-3 font-mono font-semibold text-slate-300">
              {{ row.claim_count }}
            </td>
            <td class="px-4 py-3 font-mono font-bold text-cyan-300">
              {{ row.active_revision_claim_count }}
            </td>
            <td class="px-4 py-3 font-mono text-slate-300">{{ row.anchor_count }}</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-0.5 font-mono text-[10px] font-semibold"
                :class="
                  row.has_integrity_digest
                    ? 'border border-emerald-500/40 bg-emerald-950/80 text-emerald-300'
                    : 'border border-amber-500/40 bg-amber-950/80 text-amber-300'
                "
              >
                {{ row.has_integrity_digest ? 'present' : 'missing' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p
      v-else
      class="mt-5 rounded-2xl border border-dashed border-slate-800 bg-slate-950/40 p-8 text-center text-xs text-slate-500"
    >
      لا توجد مصادر كافية للمقارنة.
    </p>
  </section>
</template>

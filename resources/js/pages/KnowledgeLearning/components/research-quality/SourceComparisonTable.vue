<script setup lang="ts">
import type { ComparisonRow } from './types';

defineProps<{ rows: ComparisonRow[] }>();
</script>

<template>
  <section aria-labelledby="source-comparison-heading">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="text-[10px] font-bold tracking-[0.2em] text-slate-600" dir="ltr">COMPARE</p>
        <h2 id="source-comparison-heading" class="mt-1 text-lg font-black">مقارنة المصادر</h2>
      </div>
      <p class="text-xs text-slate-500">مقارنة وصفية، وليست ترتيبًا للحقيقة.</p>
    </div>

    <div v-if="rows.length" class="mt-4 overflow-x-auto rounded-xl border border-slate-800">
      <table class="min-w-full divide-y divide-slate-800 text-sm">
        <thead class="bg-slate-950/50 text-xs text-slate-500">
          <tr>
            <th class="px-4 py-3 text-right">المصدر</th>
            <th class="px-4 py-3 text-right">Authority</th>
            <th class="px-4 py-3 text-right">Claims</th>
            <th class="px-4 py-3 text-right">Active revision</th>
            <th class="px-4 py-3 text-right">Anchors</th>
            <th class="px-4 py-3 text-right">Digest</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr v-for="row in rows" :key="row.source_id" class="bg-slate-900/20">
            <td class="px-4 py-3">
              <p class="font-bold text-slate-200">{{ row.title }}</p>
              <bdi dir="ltr" class="mt-1 block font-mono text-[10px] text-slate-600">
                {{ row.source_id }}
              </bdi>
            </td>
            <td class="px-4 py-3">
              <bdi dir="ltr" class="text-xs text-slate-400">{{ row.authority_class }}</bdi>
            </td>
            <td class="px-4 py-3 font-mono text-slate-300">{{ row.claim_count }}</td>
            <td class="px-4 py-3 font-mono text-cyan-300">{{ row.active_revision_claim_count }}</td>
            <td class="px-4 py-3 font-mono text-slate-300">{{ row.anchor_count }}</td>
            <td class="px-4 py-3">
              <span :class="row.has_integrity_digest ? 'text-emerald-300' : 'text-amber-300'">
                {{ row.has_integrity_digest ? 'present' : 'missing' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p
      v-else
      class="mt-5 rounded-xl border border-dashed border-slate-700 p-8 text-center text-sm text-slate-500"
    >
      لا توجد مصادر كافية للمقارنة.
    </p>
  </section>
</template>

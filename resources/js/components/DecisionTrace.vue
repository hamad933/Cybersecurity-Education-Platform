<script setup lang="ts">
import OutcomeBadge from './OutcomeBadge.vue';

type Step = {
  index: number;
  type: string;
  trustee_sid: string;
  reason: string;
  mask_before: string;
  mask_effect: string;
  mask_after: string;
};
defineProps<{
  trace: {
    final_outcome: string;
    decisive_rule_id: string;
    remaining_unresolved_mask: string;
    output_digest: string;
    evidence_origin: string;
    ordered_ace_steps: Step[];
  };
}>();
</script>

<template>
  <section
    class="rounded-xl border border-slate-700 bg-slate-950 p-4"
    aria-label="أثر القرار الحتمي"
  >
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-xs font-semibold text-cyan-300">DECISION TRACE / أثر القرار</p>
        <p class="mt-1 font-mono text-sm text-slate-300" dir="ltr">{{ trace.decisive_rule_id }}</p>
      </div>
      <OutcomeBadge :value="trace.final_outcome" />
    </div>
    <div class="direction-ltr mt-4 overflow-x-auto" tabindex="0" aria-label="جدول خطوات ACE">
      <table class="w-full min-w-[700px] border-collapse text-left font-mono text-xs">
        <thead class="text-slate-400">
          <tr>
            <th class="p-2">#</th>
            <th class="p-2">ACE</th>
            <th class="p-2">Trustee</th>
            <th class="p-2">Before</th>
            <th class="p-2">Effect</th>
            <th class="p-2">After</th>
            <th class="p-2">Reason</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="step in trace.ordered_ace_steps"
            :key="step.index"
            class="border-t border-slate-800"
          >
            <td class="p-2">{{ step.index }}</td>
            <td class="p-2">{{ step.type }}</td>
            <td class="p-2">{{ step.trustee_sid }}</td>
            <td class="p-2">{{ step.mask_before }}</td>
            <td class="p-2 text-cyan-300">{{ step.mask_effect }}</td>
            <td class="p-2">{{ step.mask_after }}</td>
            <td class="p-2">{{ step.reason }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <dl
      class="direction-ltr mt-4 grid gap-2 text-left font-mono text-xs text-slate-400 sm:grid-cols-2"
    >
      <div>
        <dt>origin</dt>
        <dd class="text-amber-200">{{ trace.evidence_origin }}</dd>
      </div>
      <div>
        <dt>remaining mask</dt>
        <dd>{{ trace.remaining_unresolved_mask }}</dd>
      </div>
      <div class="sm:col-span-2">
        <dt>SHA-256</dt>
        <dd class="break-all text-slate-300">{{ trace.output_digest }}</dd>
      </div>
    </dl>
  </section>
</template>

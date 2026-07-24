<script setup lang="ts">
import OutcomeBadge from './OutcomeBadge.vue';
type BoundaryStep = { boundary_id: string; boundary: string; result: string };
type Trace = {
  request_id: string;
  correlation_id: string;
  decision: string;
  actor_id: string;
  target_resource_id: string;
  authentication_result: string;
  decisive_rule_id: string;
  response_status: number;
  response_shape_id: string;
  redaction_result: {
    included_fields: string[];
    excluded_fields: string[];
    secrets_stored: boolean;
  };
  trace_digest: string;
  trust_boundary_steps: BoundaryStep[];
};
defineProps<{ trace: Trace }>();
</script>

<template>
  <section
    class="min-w-0 rounded-2xl border border-slate-700 bg-slate-950 p-4"
    aria-label="أثر قرار Web وAPI"
  >
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-xs font-bold text-fuchsia-300">WEB/API DECISION TRACE · SIMULATED</p>
        <p class="direction-ltr mt-1 font-mono text-xs break-all">
          {{ trace.request_id }} · {{ trace.correlation_id }}
        </p>
      </div>
      <OutcomeBadge :value="trace.decision" />
    </div>
    <div class="direction-ltr mt-4 overflow-x-auto" tabindex="0" aria-label="خطوات حدود الثقة">
      <table class="w-full min-w-[720px] text-left font-mono text-xs">
        <thead class="text-slate-400">
          <tr>
            <th class="p-2">boundary</th>
            <th class="p-2">name</th>
            <th class="p-2">result</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="step in trace.trust_boundary_steps"
            :key="`${step.boundary_id}-${step.result}`"
            class="border-t border-slate-800"
          >
            <td class="p-2 text-fuchsia-300">{{ step.boundary_id }}</td>
            <td class="p-2">{{ step.boundary }}</td>
            <td class="p-2">{{ step.result }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <dl class="direction-ltr mt-4 grid gap-3 text-left font-mono text-xs sm:grid-cols-2">
      <div>
        <dt class="text-slate-500">actor / resource</dt>
        <dd>{{ trace.actor_id }} / {{ trace.target_resource_id }}</dd>
      </div>
      <div>
        <dt class="text-slate-500">authentication</dt>
        <dd>{{ trace.authentication_result }}</dd>
      </div>
      <div>
        <dt class="text-slate-500">decisive rule</dt>
        <dd class="text-fuchsia-300">{{ trace.decisive_rule_id }}</dd>
      </div>
      <div>
        <dt class="text-slate-500">HTTP / shape</dt>
        <dd>{{ trace.response_status }} / {{ trace.response_shape_id }}</dd>
      </div>
      <div class="sm:col-span-2">
        <dt class="text-slate-500">redaction</dt>
        <dd class="break-all">
          included={{ trace.redaction_result?.included_fields }} · excluded={{
            trace.redaction_result?.excluded_fields
          }}
          · secrets={{ trace.redaction_result?.secrets_stored }}
        </dd>
      </div>
      <div class="sm:col-span-2">
        <dt class="text-slate-500">trace sha256</dt>
        <dd class="break-all text-slate-300">{{ trace.trace_digest }}</dd>
      </div>
    </dl>
  </section>
</template>

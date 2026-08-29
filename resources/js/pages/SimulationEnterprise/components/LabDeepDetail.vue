<script setup lang="ts">
import { jsonText } from '../formatters';
import type { LabItem } from '../types';

defineProps<{ lab: LabItem | null }>();
</script>

<template>
  <div v-if="lab" class="sim-deep-grid" data-testid="lab-bottom">
    <section class="sim-deep-section">
      <h3>Configuration</h3>
      <pre class="sim-json">{{ jsonText(lab.configuration) }}</pre>
    </section>
    <section class="sim-deep-section">
      <h3>Validation</h3>
      <pre class="sim-json">{{ jsonText(lab.validation) }}</pre>
    </section>
    <section class="sim-deep-section">
      <h3>Environment Contract</h3>
      <pre class="sim-json">{{ jsonText(lab.environment_contract ?? {}) }}</pre>
      <h3>Task Graph</h3>
      <pre class="sim-json">{{
        jsonText({ tasks: lab.tasks ?? [], dependencies: lab.task_dependencies ?? [] })
      }}</pre>
    </section>
    <section class="sim-deep-section">
      <h3>Published identity</h3>
      <code v-if="lab.lab_id" class="sim-technical sim-wrap">{{ lab.lab_id }}</code>
      <code class="sim-technical sim-wrap">{{ lab.id }}</code>
      <code class="sim-technical sim-wrap">{{ lab.baseline_id ?? 'LAB-LOCAL PROFILE' }}</code>
      <code class="sim-technical sim-wrap">{{ lab.digest }}</code>
    </section>
  </div>
</template>

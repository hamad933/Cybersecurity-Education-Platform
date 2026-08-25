<script setup lang="ts">
import { jsonText } from '../formatters';
import type { ScenarioItem } from '../types';

defineProps<{ scenario: ScenarioItem | null }>();
</script>

<template>
  <div v-if="scenario" class="sim-deep-grid" data-testid="scenario-bottom">
    <section class="sim-deep-section">
      <h3>Portable Environment Contract</h3>
      <pre class="sim-json">{{ jsonText(scenario.environment_contract) }}</pre>
    </section>
    <section class="sim-deep-section">
      <h3>Orchestration</h3>
      <pre class="sim-json">{{ jsonText(scenario.orchestration) }}</pre>
    </section>
    <section class="sim-deep-section">
      <h3>Validation</h3>
      <pre class="sim-json">{{ jsonText(scenario.validation) }}</pre>
    </section>
    <section class="sim-deep-section">
      <h3>Definition identity</h3>
      <code class="sim-technical sim-wrap">{{ scenario.id }}</code>
      <code class="sim-technical sim-wrap">{{ scenario.digest }}</code>
    </section>
  </div>
</template>

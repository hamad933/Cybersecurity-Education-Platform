<script setup lang="ts">
import { jsonText } from '../formatters';
import type { EnterpriseItem } from '../types';

defineProps<{ enterprise: EnterpriseItem | null }>();
</script>

<template>
  <div class="sim-deep-grid" data-testid="enterprise-bottom">
    <template v-if="enterprise">
      <section class="sim-deep-section">
        <h3>Enterprise Definition</h3>
        <pre class="sim-json">{{ jsonText(enterprise.definition) }}</pre>
      </section>
      <section v-for="twin in enterprise.digital_twins" :key="twin.id" class="sim-deep-section">
        <h3>{{ twin.name_ar }} · Topology / Behavior / Baselines</h3>
        <div v-for="revision in twin.revisions" :key="revision.id" class="sim-deep-block">
          <h4>Revision {{ revision.revision }}</h4>
          <code class="sim-technical sim-wrap">{{ revision.digest }}</code>
          <h5>Topology</h5>
          <pre class="sim-json">{{ jsonText(revision.topology) }}</pre>
          <h5>Behavior model</h5>
          <pre class="sim-json">{{ jsonText(revision.behavior_model) }}</pre>
          <div v-for="baseline in revision.baselines" :key="baseline.id">
            <h5>Baseline {{ baseline.revision }}</h5>
            <code class="sim-technical sim-wrap">{{ baseline.digest }}</code>
            <pre class="sim-json">{{ jsonText(baseline.state) }}</pre>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

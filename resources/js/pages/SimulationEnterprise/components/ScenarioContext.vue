<script setup lang="ts">
import type { ScenarioItem } from '../types';

defineProps<{ scenario: ScenarioItem | null }>();
</script>

<template>
  <div class="sim-context" data-testid="scenario-right">
    <div class="sim-panel-heading">
      <p class="sim-kicker">RIGHT · SELECTED CONTEXT</p>
      <h2>سياق السيناريو</h2>
    </div>
    <template v-if="scenario">
      <dl class="sim-facts">
        <div>
          <dt>Revision</dt>
          <dd>{{ scenario.revision }}</dd>
        </div>
        <div>
          <dt>Slug</dt>
          <dd class="sim-technical">{{ scenario.slug }}</dd>
        </div>
        <div>
          <dt>Provenance</dt>
          <dd class="sim-technical">{{ scenario.provenance }}</dd>
        </div>
        <div>
          <dt>Compatible targets</dt>
          <dd>{{ scenario.preparation_targets.length }}</dd>
        </div>
      </dl>
      <section class="sim-context-section">
        <h3>Required capabilities</h3>
        <div class="sim-chip-list">
          <span
            v-for="capability in scenario.environment_contract.required_capabilities ?? []"
            :key="capability"
            class="sim-chip"
          >
            {{ capability }}
          </span>
        </div>
      </section>
      <div class="sim-rule-note">
        Scenario ≠ Lab. مرجع المختبر هنا وحدة داخل orchestration، ولا يتحول إلى Standalone Lab Run.
      </div>
    </template>
    <p v-else class="sim-muted">اختر سيناريو لعرض عقده البيئي.</p>
  </div>
</template>

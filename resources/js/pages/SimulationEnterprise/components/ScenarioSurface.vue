<script setup lang="ts">
import { computed } from 'vue';

import ScenarioBoundaryNote from './ScenarioBoundaryNote.vue';
import { orderedItems } from '../projections';
import type { ScenarioItem } from '../types';

const props = defineProps<{ scenario: ScenarioItem | null }>();

const phases = computed(() => orderedItems(props.scenario?.orchestration.phases, 'Phase'));
</script>

<template>
  <section class="sim-surface sim-canvas-surface" data-testid="scenario-center">
    <header class="sim-workbench-header">
      <div>
        <p class="sim-kicker">SCENARIO STUDIO · ORCHESTRATION</p>
        <h1>{{ scenario?.title_ar ?? 'استوديو السيناريو' }}</h1>
      </div>
    </header>

    <ScenarioBoundaryNote />

    <div v-if="!scenario" class="sim-empty">
      <strong>لا يوجد سيناريو محدد</strong>
    </div>

    <div v-else class="sim-flow-workbench" data-testid="scenario-orchestration">
      <div class="sim-canvas-caption">
        <div>
          <strong>التدفق التشغيلي المحمول</strong>
          <code class="sim-technical">REV {{ scenario.revision }}</code>
        </div>
        <div class="sim-canvas-counts">
          <span>{{ phases.length }} PHASES</span>
          <span>{{ scenario.lab_module_references.length }} LAB REFERENCES</span>
        </div>
      </div>

      <ol v-if="phases.length" class="sim-phase-flow">
        <li v-for="phase in phases" :key="phase.id" data-testid="scenario-phase">
          <div class="sim-phase-index">
            <span>{{ String(phase.ordinal).padStart(2, '0') }}</span>
          </div>
          <article class="sim-flow-node">
            <small>PHASE {{ phase.ordinal }}</small>
            <strong>{{ phase.label }}</strong>
          </article>
          <div class="sim-phase-module-lane">
            <article
              v-for="module in scenario.lab_module_references.filter(
                (item) => item.ordinal === phase.ordinal,
              )"
              :key="module.reference_id"
              class="sim-flow-node sim-flow-node--module"
              data-testid="scenario-module-node"
            >
              <small>LAB MODULE REFERENCE · ORDER {{ module.ordinal }}</small>
              <strong>{{ module.lab_title_ar }}</strong>
              <code class="sim-technical">{{ module.module_key }}</code>
            </article>
          </div>
        </li>
      </ol>
      <p v-else class="sim-empty">لا تحتوي Orchestration المنشورة على مراحل.</p>

      <div
        v-if="
          scenario.lab_module_references.some(
            (module) => !phases.some((phase) => phase.ordinal === module.ordinal),
          )
        "
        class="sim-unplaced-modules"
      >
        <p class="sim-kicker">ORDERED LAB REFERENCES</p>
        <article
          v-for="module in scenario.lab_module_references.filter(
            (item) => !phases.some((phase) => phase.ordinal === item.ordinal),
          )"
          :key="module.reference_id"
          class="sim-flow-node sim-flow-node--module"
          data-testid="scenario-module-node"
        >
          <strong>{{ module.lab_title_ar }}</strong>
          <code class="sim-technical">{{ module.module_key }}</code>
        </article>
      </div>

      <footer class="sim-canvas-legend">
        <span><i class="sim-legend-line" />تسلسل phase منشور</span>
        <span><i class="sim-legend-module" />مرجع Lab داخل orchestration</span>
        <span>Scenario ≠ Lab · target materializes only during Run Preparation</span>
      </footer>
    </div>
  </section>
</template>

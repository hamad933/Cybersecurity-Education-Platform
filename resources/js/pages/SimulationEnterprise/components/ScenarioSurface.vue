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
        <p class="sim-kicker">SCENARIO STUDIO · TIMELINE ORCHESTRATION</p>
        <h1>{{ scenario?.title_ar ?? 'استوديو السيناريو' }}</h1>
      </div>
    </header>

    <ScenarioBoundaryNote />

    <div v-if="!scenario" class="sim-empty">
      <strong>لا يوجد سيناريو محدد</strong>
    </div>

    <div v-else class="sim-flow-workbench" data-testid="scenario-orchestration">
      <div class="sim-rule-note">عرض تعريف محكوم للقراءة؛ تحرير orchestration غير متاح هنا.</div>

      <!-- Canvas Banner / Title -->
      <div class="sim-canvas-caption">
        <div class="sim-caption-main">
          <strong>{{ scenario.title_ar }}</strong>
          <span class="sim-badge">Revision {{ scenario.revision }}</span>
        </div>
        <div class="sim-canvas-counts">
          <span>{{ phases.length }} PHASES</span>
          <span>{{ scenario.lab_module_references.length }} LAB REFERENCES</span>
        </div>
      </div>

      <!-- Vertical Phase Timeline Matrix -->
      <div class="sim-phase-flow-container">
        <ol v-if="phases.length" class="sim-phase-flow">
          <li
            v-for="phase in phases"
            :key="phase.id"
            class="sim-phase-row"
            data-testid="scenario-phase"
          >
            <!-- Phase Ordinal Node -->
            <div class="sim-phase-index-col">
              <div class="sim-phase-index">
                <span>{{ String(phase.ordinal).padStart(2, '0') }}</span>
              </div>
            </div>

            <!-- Primary Phase Step Card -->
            <article class="sim-flow-node sim-flow-node--primary">
              <div class="sim-node-header">
                <svg
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  class="sim-text-cyan"
                >
                  <circle cx="12" cy="12" r="10" />
                  <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                  <path d="M2 12h20" />
                </svg>
                <small>PHASE 0{{ phase.ordinal }}</small>
              </div>
              <strong>{{ phase.label }}</strong>
              <code class="sim-technical">{{ phase.id }}</code>
            </article>

            <!-- Connector Arrow -->
            <div class="sim-flow-arrow" aria-hidden="true">
              <svg
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <line x1="5" y1="12" x2="19" y2="12" />
                <polyline points="12 5 19 12 12 19" />
              </svg>
            </div>

            <!-- Governed module-reference lane -->
            <div class="sim-phase-module-lane">
              <!-- Governed Lab Module Reference if present for this phase -->
              <template
                v-if="scenario.lab_module_references.some((item) => item.ordinal === phase.ordinal)"
              >
                <article
                  v-for="module in scenario.lab_module_references.filter(
                    (item) => item.ordinal === phase.ordinal,
                  )"
                  :key="module.reference_id"
                  class="sim-flow-node sim-flow-node--module"
                  data-testid="scenario-module-node"
                >
                  <div class="sim-node-header">
                    <svg
                      width="14"
                      height="14"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      class="sim-text-success"
                    >
                      <path d="M10 2v7.31M14 2v7.31M8.5 2h7M14 9.3a6.5 6.5 0 1 1-4 0" />
                    </svg>
                    <small>LAB MODULE REFERENCE · ORDER {{ module.ordinal }}</small>
                  </div>
                  <strong>{{ module.lab_title_ar }}</strong>
                  <code class="sim-technical">{{ module.module_key }}</code>
                </article>
              </template>

              <!-- Dynamic nodes should be derived from governed orchestration. No mock data. -->
              <!-- Empty state for Phase without Lab Modules -->
              <div
                v-else
                class="sim-empty-slot"
                style="
                  padding: 1rem;
                  border: 1px dashed var(--sim-border);
                  border-radius: 4px;
                  text-align: center;
                "
              >
                <span class="sim-muted">لم يتم العثور على عقد مرتبطة</span>
              </div>
            </div>
          </li>
        </ol>
        <p v-else class="sim-empty">لا تحتوي Orchestration المنشورة على مراحل.</p>
      </div>

      <!-- Unplaced Modules if any -->
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

      <!-- Flow Legend -->
      <footer class="sim-canvas-legend">
        <span><i class="sim-legend-line sim-legend-line--solid" />Sequence / Flow</span>
        <span>Scenario ≠ Lab · target materializes only during Run Preparation</span>
      </footer>
    </div>
  </section>
</template>

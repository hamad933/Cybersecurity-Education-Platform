<script setup lang="ts">
import { computed } from 'vue';

import { shortDigest } from '../formatters';
import { orderedItems } from '../projections';
import type { LabItem } from '../types';

const props = defineProps<{ lab: LabItem | null }>();

const steps = computed(() => orderedItems(props.lab?.configuration.steps, 'Task'));
</script>

<template>
  <section class="sim-surface sim-canvas-surface" data-testid="lab-center">
    <header class="sim-workbench-header">
      <div>
        <p class="sim-kicker">LAB DEFINITION STUDIO · TASK GRAPH</p>
        <h1>{{ lab?.title_ar ?? 'المختبرات' }}</h1>
      </div>
    </header>

    <div v-if="!lab" class="sim-empty">
      <strong>لا يوجد مختبر محدد</strong>
    </div>

    <div v-else class="sim-task-workbench" data-testid="lab-task-graph">
      <div class="sim-canvas-caption">
        <div>
          <strong>{{ lab.title_ar }}</strong>
          <code class="sim-technical">REV {{ lab.revision }} · {{ lab.slug }}</code>
        </div>
        <div class="sim-canvas-counts">
          <span>{{ steps.length }} TASKS</span><span>BASELINE PINNED</span>
        </div>
      </div>

      <div v-if="steps.length" class="sim-task-graph" role="list" aria-label="مسار مهام المختبر">
        <template v-for="(step, index) in steps" :key="step.id">
          <article class="sim-task-node" role="listitem" data-testid="lab-task-node">
            <span class="sim-task-ordinal">{{ step.ordinal }}</span>
            <small>TASK {{ String(step.ordinal).padStart(2, '0') }}</small>
            <strong>{{ step.label }}</strong>
          </article>
          <span v-if="index < steps.length - 1" class="sim-task-connector" aria-hidden="true"
            >→</span
          >
        </template>
      </div>
      <p v-else class="sim-empty">لا يحتوي تعريف المختبر على خطوات منشورة.</p>

      <div class="sim-definition-anchors">
        <article>
          <span class="sim-anchor-icon" aria-hidden="true">B</span>
          <div>
            <small>BASELINE LINKAGE</small
            ><strong class="sim-technical">{{ shortDigest(lab.baseline_id) }}</strong>
          </div>
        </article>
        <span class="sim-anchor-rail" aria-hidden="true" />
        <article>
          <span class="sim-anchor-icon" aria-hidden="true">V</span>
          <div>
            <small>VALIDATION CONTRACT</small
            ><strong>{{ Object.keys(lab.validation).length }} governed fields</strong>
          </div>
        </article>
      </div>

      <footer class="sim-canvas-legend">
        <span><i class="sim-legend-node" />مهمة من configuration.steps</span>
        <span><i class="sim-legend-line" />ترتيب تعريف منشور</span>
        <span>Lab definition · standalone execution path</span>
      </footer>
    </div>
  </section>
</template>

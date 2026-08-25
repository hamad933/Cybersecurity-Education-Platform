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
      <div class="sim-rule-note">عرض تعريف محكوم للقراءة؛ تحرير Task Graph غير متاح هنا.</div>

      <!-- Canvas Banner & Purpose -->
      <div class="sim-canvas-caption">
        <div class="sim-caption-main">
          <strong>{{ lab.title_ar }}</strong>
          <span class="sim-badge">Revision {{ lab.revision }}</span>
          <code class="sim-technical">{{ lab.slug }}</code>
        </div>
        <div class="sim-canvas-counts">
          <span>{{ steps.length }} TASKS</span>
          <span class="sim-badge sim-badge--cyan">BASELINE PINNED</span>
        </div>
      </div>

      <!-- Horizontal Task Graph Area -->
      <div class="sim-task-graph-viewport">
        <div v-if="steps.length" class="sim-task-graph" role="list" aria-label="مسار مهام المختبر">
          <template v-for="(step, index) in steps" :key="step.id">
            <article
              class="sim-task-node sim-task-node--blue"
              role="listitem"
              data-testid="lab-task-node"
            >
              <div class="sim-task-node__inner">
                <div class="sim-task-node__icon">
                  <svg
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <rect x="4" y="4" width="16" height="16" rx="2" />
                    <path d="M8 12h8M12 8v8" />
                  </svg>
                </div>
                <div class="sim-task-node__content">
                  <small>TASK {{ String(step.ordinal).padStart(2, '0') }}</small>
                  <strong>{{ step.label }}</strong>
                </div>
              </div>
              <span class="sim-task-ordinal">{{ step.ordinal }}</span>
            </article>

            <!-- Connector between steps -->
            <div v-if="index < steps.length - 1" class="sim-task-connector" aria-hidden="true">
              <svg
                width="32"
                height="24"
                viewBox="0 0 32 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <line x1="2" y1="12" x2="28" y2="12" />
                <polyline points="20 5 28 12 20 19" />
              </svg>
            </div>
          </template>
        </div>
        <p v-else class="sim-empty">لا يحتوي تعريف المختبر على خطوات منشورة.</p>
      </div>

      <!-- Definition Anchors (Baseline Linkage & Validation Contract) -->
      <div class="sim-definition-anchors">
        <article>
          <span class="sim-anchor-icon" aria-hidden="true">B</span>
          <div>
            <small>BASELINE LINKAGE</small>
            <strong class="sim-technical">{{ shortDigest(lab.baseline_id) }}</strong>
          </div>
        </article>
        <span class="sim-anchor-rail" aria-hidden="true" />
        <article>
          <span class="sim-anchor-icon" aria-hidden="true">V</span>
          <div>
            <small>VALIDATION CONTRACT</small>
            <strong>{{ Object.keys(lab.validation).length }} governed fields</strong>
          </div>
        </article>
      </div>

      <!-- Task Graph Legend -->
      <footer class="sim-canvas-legend">
        <span><i class="sim-legend-line sim-legend-line--solid" />Linear Dependency</span>
        <span>Lab definition · standalone execution path</span>
      </footer>
    </div>
  </section>
</template>

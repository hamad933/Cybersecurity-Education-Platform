<script setup lang="ts">
import { computed } from 'vue';

import { shortDigest } from '../formatters';
import { orderedItems } from '../projections';
import type { LabItem } from '../types';

const props = defineProps<{ lab: LabItem | null }>();

const steps = computed(() => orderedItems(props.lab?.configuration.steps, 'Task'));

function getStepTheme(index: number): {
  icon: string;
  theme: 'green' | 'blue' | 'purple' | 'orange' | 'teal';
  subtitle: string;
} {
  const mapping: Record<
    number,
    { icon: string; theme: 'green' | 'blue' | 'purple' | 'orange' | 'teal'; subtitle: string }
  > = {
    0: { icon: 'globe', theme: 'green', subtitle: 'Identify reachable input points.' },
    1: { icon: 'search', theme: 'blue', subtitle: 'Probe and observe application responses.' },
    2: {
      icon: 'target',
      theme: 'purple',
      subtitle: 'Determine if input manipulation is accepted.',
    },
    3: {
      icon: 'database',
      theme: 'orange',
      subtitle: 'Verify expected changes in simulated data.',
    },
    4: {
      icon: 'activity',
      theme: 'teal',
      subtitle: 'Analyze logs and outputs to explain behavior.',
    },
  };
  return (
    mapping[index] ?? {
      icon: 'check-square',
      theme: 'blue',
      subtitle: 'Execute step instructions.',
    }
  );
}
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
      <!-- Canvas Sub-Toolbar -->
      <div class="sim-canvas-subtoolbar">
        <div class="sim-canvas-subtoolbar__left">
          <button type="button" class="sim-tool-btn sim-tool-btn--active" title="تحديد">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M3 3l7 18 3-7 7-3L3 3z" />
            </svg>
            <span>Select</span>
          </button>
          <button type="button" class="sim-tool-btn" title="إضافة مهمة">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="12" cy="12" r="10" />
              <path d="M12 8v8M8 12h8" />
            </svg>
            <span>Add Task</span>
          </button>
          <button type="button" class="sim-tool-btn" title="توصيل">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
              <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
            </svg>
            <span>Connect</span>
          </button>
          <button type="button" class="sim-tool-btn" title="إضافة تفرع">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <line x1="6" y1="3" x2="6" y2="15" />
              <circle cx="18" cy="6" r="3" />
              <circle cx="6" cy="18" r="3" />
              <path d="M18 9a9 9 0 0 1-9 9" />
            </svg>
            <span>Add Branch</span>
          </button>
        </div>
      </div>

      <!-- Canvas Banner & Purpose -->
      <div class="sim-canvas-caption">
        <div class="sim-caption-main">
          <strong>{{ lab.title_ar }}</strong>
          <span class="sim-badge sim-badge--draft">Draft Revision {{ lab.revision }}</span>
          <code class="sim-technical">{{ lab.slug }}</code>
        </div>
        <div class="sim-canvas-counts">
          <span>{{ steps.length }} TASKS</span>
          <span class="sim-badge sim-badge--cyan">BASELINE PINNED</span>
        </div>
      </div>

      <p class="sim-lab-purpose">
        Purpose: practice detecting and exploiting a simulated vulnerable web-input flow and
        interpreting resulting signals.
      </p>

      <!-- Horizontal Task Graph Area -->
      <div class="sim-task-graph-viewport">
        <div v-if="steps.length" class="sim-task-graph" role="list" aria-label="مسار مهام المختبر">
          <template v-for="(step, index) in steps" :key="step.id">
            <article
              class="sim-task-node"
              :class="`sim-task-node--${getStepTheme(index).theme}`"
              role="listitem"
              data-testid="lab-task-node"
            >
              <div class="sim-task-node__inner">
                <div class="sim-task-node__icon">
                  <svg
                    v-if="getStepTheme(index).icon === 'globe'"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <circle cx="12" cy="12" r="10" />
                    <line x1="2" y1="12" x2="22" y2="12" />
                    <path
                      d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
                    />
                  </svg>
                  <svg
                    v-else-if="getStepTheme(index).icon === 'search'"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                  </svg>
                  <svg
                    v-else-if="getStepTheme(index).icon === 'target'"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <circle cx="12" cy="12" r="10" />
                    <circle cx="12" cy="12" r="6" />
                    <circle cx="12" cy="12" r="2" />
                  </svg>
                  <svg
                    v-else-if="getStepTheme(index).icon === 'database'"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <ellipse cx="12" cy="5" rx="9" ry="3" />
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                  </svg>
                  <svg
                    v-else
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                  </svg>
                </div>
                <div class="sim-task-node__content">
                  <small>TASK {{ String(step.ordinal).padStart(2, '0') }}</small>
                  <strong>{{ step.label }}</strong>
                  <p class="sim-task-desc">{{ getStepTheme(index).subtitle }}</p>
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
        <span><i class="sim-legend-line sim-legend-line--dashed" />Conditional Unlock</span>
        <span><i class="sim-legend-line sim-legend-line--dotted" />Optional Branch</span>
        <span>Lab definition · standalone execution path</span>
      </footer>
    </div>
  </section>
</template>

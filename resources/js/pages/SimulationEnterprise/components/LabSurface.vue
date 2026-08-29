<script setup lang="ts">
import { computed } from 'vue';

import { shortDigest } from '../formatters';
import { orderedItems } from '../projections';
import type { JsonMap, LabItem } from '../types';

const props = defineProps<{
  lab: LabItem | null;
  selectedTaskId: string | null;
}>();
const emit = defineEmits<{ selectTask: [id: string] }>();

type DisplayTask = {
  id: string;
  taskKey: string;
  label: string;
  objective: string;
  optional: boolean;
};

const steps = computed<DisplayTask[]>(() => {
  if (props.lab?.tasks?.length) {
    return props.lab.tasks.map((task) => ({
      id: task.id,
      taskKey: task.task_key,
      label: task.title_ar,
      objective: task.objective,
      optional: task.is_optional,
    }));
  }

  return orderedItems(props.lab?.configuration.steps, 'Task').map((step) => ({
    id: step.id,
    taskKey: step.id,
    label: step.label,
    objective: step.label,
    optional: false,
  }));
});

const dependencies = computed(() => {
  if (props.lab?.task_dependencies?.length) return props.lab.task_dependencies;
  return steps.value.slice(1).map((step, index) => ({
    id: `legacy-edge-${index + 1}`,
    predecessor_task_id: steps.value[index].id,
    successor_task_id: step.id,
    dependency_type: 'REQUIRED' as const,
    condition: null as JsonMap | null,
  }));
});

function taskLabel(taskId: string): string {
  return steps.value.find((task) => task.id === taskId)?.label ?? taskId;
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
      <div class="sim-rule-note">عرض تعريف محكوم للقراءة؛ تحرير Task Graph غير متاح هنا.</div>

      <!-- Canvas Banner & Purpose -->
      <div class="sim-canvas-caption">
        <div class="sim-caption-main">
          <strong>{{ lab.title_ar }}</strong>
          <span class="sim-badge">Revision {{ lab.revision }}</span>
          <span v-if="lab.status" class="sim-badge">{{ lab.status }}</span>
          <code class="sim-technical">{{ lab.slug }}</code>
        </div>
        <div class="sim-canvas-counts">
          <span>{{ steps.length }} TASKS</span>
          <span class="sim-badge sim-badge--cyan">
            {{ lab.environment_binding_mode === 'LAB_LOCAL' ? 'LAB-LOCAL' : 'BASELINE PINNED' }}
          </span>
        </div>
      </div>

      <!-- Horizontal Task Graph Area -->
      <div class="sim-task-graph-viewport">
        <div v-if="steps.length" class="sim-task-graph" role="list" aria-label="مسار مهام المختبر">
          <template v-for="(step, index) in steps" :key="step.id">
            <article
              class="sim-task-node sim-task-node--blue"
              :class="{ 'is-selected': step.id === selectedTaskId }"
              role="button"
              tabindex="0"
              :aria-pressed="step.id === selectedTaskId"
              data-testid="lab-task-node"
              @click="emit('selectTask', step.id)"
              @keydown.enter="emit('selectTask', step.id)"
              @keydown.space.prevent="emit('selectTask', step.id)"
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
                  <small>TASK {{ String(index + 1).padStart(2, '0') }}</small>
                  <strong>{{ step.label }}</strong>
                  <span class="sim-task-desc">{{ step.objective }}</span>
                  <small class="sim-technical">{{ step.taskKey }}</small>
                </div>
              </div>
              <span class="sim-task-ordinal">{{ index + 1 }}</span>
            </article>
          </template>
        </div>
        <p v-else class="sim-empty">لا يحتوي تعريف المختبر على خطوات منشورة.</p>
      </div>

      <ol
        v-if="dependencies.length"
        class="sim-task-dependencies"
        data-testid="lab-task-dependencies"
      >
        <li v-for="dependency in dependencies" :key="dependency.id">
          <span>{{ taskLabel(dependency.predecessor_task_id) }}</span>
          <b aria-hidden="true">-&gt;</b>
          <span>{{ taskLabel(dependency.successor_task_id) }}</span>
          <small class="sim-technical">{{ dependency.dependency_type }}</small>
        </li>
      </ol>

      <!-- Definition Anchors (Baseline Linkage & Validation Contract) -->
      <div class="sim-definition-anchors">
        <article>
          <span class="sim-anchor-icon" aria-hidden="true">B</span>
          <div>
            <small>BASELINE LINKAGE</small>
            <strong class="sim-technical">
              {{ lab.baseline_id ? shortDigest(lab.baseline_id) : 'LAB-LOCAL PROFILE' }}
            </strong>
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
        <span><i class="sim-legend-line sim-legend-line--solid" />Typed Task Dependency</span>
        <span>Lab definition · standalone execution path</span>
      </footer>
    </div>
  </section>
</template>

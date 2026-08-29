<script setup lang="ts">
import { computed } from 'vue';

import { shortDigest } from '../formatters';
import { displayValue } from '../projections';
import type { LabItem, LabTaskItem } from '../types';

const props = defineProps<{
  lab: LabItem | null;
  selectedTask: LabTaskItem | null;
}>();

const contextualConfiguration = computed(() =>
  Object.fromEntries(
    Object.entries(props.lab?.configuration ?? {}).filter(([key]) => key !== 'steps'),
  ),
);
</script>

<template>
  <div class="sim-context" data-testid="lab-right">
    <div class="sim-panel-heading">
      <div class="sim-panel-heading__title">
        <p class="sim-kicker">RIGHT · CONTEXT</p>
        <h2>السياق</h2>
      </div>
    </div>

    <template v-if="lab">
      <section v-if="selectedTask" class="sim-context-section" data-testid="lab-selected-task">
        <h3>Selected task</h3>
        <p class="sim-context-copy">{{ selectedTask.objective }}</p>
        <dl class="sim-facts">
          <div>
            <dt>Stable task key</dt>
            <dd class="sim-technical">{{ selectedTask.task_key }}</dd>
          </div>
          <div>
            <dt>Role</dt>
            <dd class="sim-technical">{{ selectedTask.required_role ?? 'ANY' }}</dd>
          </div>
          <div>
            <dt>Tools</dt>
            <dd class="sim-technical">{{ displayValue(selectedTask.permitted_tools) }}</dd>
          </div>
          <div>
            <dt>Required capabilities</dt>
            <dd class="sim-technical">{{ displayValue(selectedTask.required_capabilities) }}</dd>
          </div>
          <div>
            <dt>Expected signals</dt>
            <dd class="sim-technical">{{ displayValue(selectedTask.expected_signals) }}</dd>
          </div>
          <div>
            <dt>Validation rule</dt>
            <dd class="sim-technical sim-wrap">{{ displayValue(selectedTask.validation_rule) }}</dd>
          </div>
        </dl>
      </section>

      <section class="sim-context-section">
        <h3>Governed configuration</h3>
        <dl v-if="Object.keys(contextualConfiguration).length" class="sim-facts">
          <div v-for="(value, key) in contextualConfiguration" :key="String(key)">
            <dt class="sim-technical">configuration.{{ key }}</dt>
            <dd class="sim-technical">{{ displayValue(value) }}</dd>
          </div>
        </dl>
        <p v-else class="sim-muted">لا يتضمن تعريف المختبر حقول configuration.</p>
      </section>

      <section class="sim-context-section">
        <h3>Governed validation contract</h3>
        <dl v-if="Object.keys(lab.validation).length" class="sim-facts">
          <div v-for="(value, key) in lab.validation" :key="String(key)">
            <dt class="sim-technical">validation.{{ key }}</dt>
            <dd class="sim-technical">{{ displayValue(value) }}</dd>
          </div>
        </dl>
        <p v-else class="sim-muted">لا يتضمن تعريف المختبر حقول validation.</p>
      </section>

      <section class="sim-context-section">
        <h3>حدود التعريف</h3>
        <p class="sim-context-copy">
          تعرض هذه اللوحة حقول التعريف المحكومة كما وردت فقط، دون إضافة خصائص أو توقعات أو دلالات
          إكمال غير موجودة في البيانات.
        </p>
      </section>

      <section class="sim-context-section">
        <h3>Target Baseline</h3>
        <code class="sim-technical sim-wrap">{{ lab.baseline_id ?? 'LAB-LOCAL PROFILE' }}</code>
      </section>

      <dl class="sim-facts">
        <div>
          <dt>Revision</dt>
          <dd>{{ lab.revision }}</dd>
        </div>
        <div v-if="lab.status">
          <dt>Lifecycle</dt>
          <dd class="sim-technical">{{ lab.status }}</dd>
        </div>
        <div v-if="lab.environment_binding_mode">
          <dt>Environment binding</dt>
          <dd class="sim-technical">{{ lab.environment_binding_mode }}</dd>
        </div>
        <div>
          <dt>Slug</dt>
          <dd class="sim-technical">{{ lab.slug }}</dd>
        </div>
        <div>
          <dt>Provenance</dt>
          <dd class="sim-technical">{{ lab.provenance }}</dd>
        </div>
        <div>
          <dt>Digest</dt>
          <dd class="sim-technical">{{ shortDigest(lab.digest) }}</dd>
        </div>
      </dl>

      <div class="sim-rule-note">
        Baseline ≠ Runtime Snapshot ≠ Checkpoint. التهيئة تشتق حالة تشغيل جديدة.
      </div>
    </template>
    <p v-else class="sim-muted">اختر مختبرًا لعرض سياق تعريفه.</p>
  </div>
</template>

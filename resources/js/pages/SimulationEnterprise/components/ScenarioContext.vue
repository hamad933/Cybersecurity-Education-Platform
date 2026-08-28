<script setup lang="ts">
import type { ScenarioItem } from '../types';

defineProps<{ scenario: ScenarioItem | null }>();
</script>

<template>
  <div class="sim-context" data-testid="scenario-right">
    <div class="sim-panel-heading">
      <div class="sim-panel-heading__title">
        <p class="sim-kicker">RIGHT · CONTEXT</p>
        <h2>السياق</h2>
      </div>
    </div>

    <template v-if="scenario">
      <section class="sim-context-section">
        <h3>Portable Environment Contract</h3>
        <dl class="sim-facts">
          <div>
            <dt>Schema</dt>
            <dd class="sim-technical">{{ scenario.environment_contract.schema ?? 'غير متاح' }}</dd>
          </div>
          <div>
            <dt>Execution model</dt>
            <dd class="sim-technical">
              {{ scenario.environment_contract.execution_model ?? 'غير متاح' }}
            </dd>
          </div>
        </dl>
      </section>

      <section class="sim-context-section">
        <h3>Required capabilities</h3>
        <div
          v-if="scenario.environment_contract.required_capabilities?.length"
          class="sim-chip-list"
        >
          <span
            v-for="capability in scenario.environment_contract.required_capabilities"
            :key="capability"
            class="sim-chip"
          >
            {{ capability }}
          </span>
        </div>
        <p v-else class="sim-muted">لا يحدد Environment Contract قدرات مطلوبة.</p>
      </section>

      <section class="sim-context-section">
        <h3>حدود التعريف</h3>
        <p class="sim-context-copy">
          يعرض السياق العقد المحكوم فقط، ولا يضيف عناصر أو أدوارًا أو آثارًا غير موجودة في
          orchestration المنشورة.
        </p>
      </section>

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
          <dt>Preparation targets</dt>
          <dd>{{ scenario.preparation_targets.length }}</dd>
        </div>
      </dl>

      <div class="sim-rule-note">
        Scenario ≠ Lab. مرجع المختبر هنا وحدة داخل orchestration، ولا يتحول إلى Standalone Lab Run.
      </div>
    </template>
    <p v-else class="sim-muted">اختر سيناريو لعرض عقده البيئي.</p>
  </div>
</template>

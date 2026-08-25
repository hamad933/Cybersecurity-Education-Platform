<script setup lang="ts">
import ScenarioBoundaryNote from './ScenarioBoundaryNote.vue';
import type { ScenarioItem } from '../types';

defineProps<{ scenario: ScenarioItem | null }>();
const emit = defineEmits<{ openDeepDetail: [] }>();
</script>

<template>
  <section class="sim-surface" data-testid="scenario-center">
    <header class="sim-surface-header">
      <div>
        <p class="sim-kicker">SCENARIO STUDIO · PORTABLE CONTRACT</p>
        <h1>{{ scenario?.title_ar ?? 'استوديو السيناريو' }}</h1>
        <p>راجع العقد البيئي وتتابع الوحدات قبل تهيئة تشغيل داخلي على هدف متوافق تختاره أنت.</p>
      </div>
      <button
        v-if="scenario"
        type="button"
        class="sim-button sim-button--quiet"
        @click="emit('openDeepDetail')"
      >
        فحص العقد الخام
      </button>
    </header>

    <ScenarioBoundaryNote />

    <div v-if="!scenario" class="sim-empty">
      <strong>لا يوجد سيناريو محدد</strong>
      <p>اختر سيناريو منشورًا من لوحة البنية.</p>
    </div>

    <template v-else>
      <div class="sim-contract-strip">
        <div>
          <small>Schema</small
          ><strong class="sim-technical">{{ scenario.environment_contract.schema ?? '—' }}</strong>
        </div>
        <div>
          <small>Execution model</small
          ><strong class="sim-technical">{{
            scenario.environment_contract.execution_model ?? '—'
          }}</strong>
        </div>
        <div>
          <small>الأهداف المتوافقة</small><strong>{{ scenario.preparation_targets.length }}</strong>
        </div>
      </div>

      <section class="sim-section-block">
        <div class="sim-section-heading">
          <div>
            <p class="sim-kicker">ORCHESTRATION FLOW</p>
            <h2>وحدات السيناريو المرتبة</h2>
          </div>
          <span class="sim-chip">{{ scenario.lab_module_references.length }} MODULES</span>
        </div>
        <ol v-if="scenario.lab_module_references.length" class="sim-module-flow">
          <li v-for="module in scenario.lab_module_references" :key="module.reference_id">
            <span class="sim-module-flow__ordinal">{{ module.ordinal }}</span>
            <div>
              <strong>{{ module.lab_title_ar }}</strong>
              <code class="sim-technical">{{ module.module_key }}</code>
            </div>
            <small>Reference → Lab Definition</small>
          </li>
        </ol>
        <p v-else class="sim-muted">لا توجد مراجع Lab Module ضمن هذا السيناريو.</p>
      </section>
    </template>
  </section>
</template>

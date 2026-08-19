<script setup lang="ts">
import { computed } from "vue";
import type { ScenarioItem } from "../types";
import { stringList } from "../utils";

const props = defineProps<{ scenario: ScenarioItem }>();
const emit = defineEmits<{ prepare: [id: string] }>();
const phases = computed(() => stringList(props.scenario.orchestration.phases));
</script>

<template>
  <section class="surface-panel" data-testid="scenario-orchestration-canvas">
    <header class="section-heading">
      <div>
        <p class="rail-kicker">Scenario Orchestration</p>
        <h2>{{ scenario.title_ar }}</h2>
      </div>
      <button
        class="primary-action"
        type="button"
        @click="emit('prepare', scenario.id)"
      >
        تهيئة Scenario Run
      </button>
    </header>

    <div v-if="phases.length" class="flow-track" aria-label="مراحل السيناريو">
      <template v-for="(phase, index) in phases" :key="`${phase}-${index}`">
        <article class="flow-node">
          <span>{{ index + 1 }}</span>
          <strong class="technical" dir="ltr">{{ phase }}</strong>
        </article>
        <i v-if="index < phases.length - 1" class="flow-arrow">←</i>
      </template>
    </div>
    <p v-else class="truthful-unavailable">
      لا يرسل تعريف السيناريو قائمة Phases منظّمة للعرض الزمني.
    </p>

    <section class="subsurface">
      <p class="rail-kicker">Lab References</p>
      <h3>العُقد المرتبطة بالتنفيذ</h3>
      <div v-if="scenario.lab_module_references.length" class="module-grid">
        <article
          v-for="module in scenario.lab_module_references"
          :key="module.reference_id"
          class="module-card"
        >
          <span class="ordinal">{{ module.ordinal }}</span>
          <div>
            <strong>{{ module.lab_title_ar }}</strong>
            <small class="technical" dir="ltr">{{ module.module_key }}</small>
          </div>
        </article>
      </div>
      <p v-else class="truthful-unavailable">
        لا توجد Lab Module References في هذا التعريف.
      </p>
    </section>
  </section>
</template>

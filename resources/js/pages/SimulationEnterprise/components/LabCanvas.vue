<script setup lang="ts">
import { computed } from "vue";
import type { LabItem } from "../types";
import { fieldEntries, stringList } from "../utils";

const props = defineProps<{ lab: LabItem }>();
const emit = defineEmits<{ prepare: [id: string] }>();
const steps = computed(() => stringList(props.lab.configuration.steps));
</script>

<template>
  <section class="surface-panel" data-testid="lab-task-graph">
    <header class="section-heading">
      <div>
        <p class="rail-kicker">Lab Definition</p>
        <h2>{{ lab.title_ar }}</h2>
      </div>
      <button
        class="primary-action"
        type="button"
        @click="emit('prepare', lab.id)"
      >
        تهيئة Standalone Lab Run
      </button>
    </header>

    <div v-if="steps.length" class="task-graph" aria-label="مخطط مهام المختبر">
      <article
        v-for="(step, index) in steps"
        :key="`${step}-${index}`"
        class="task-node"
      >
        <span class="task-index">{{ String(index + 1).padStart(2, "0") }}</span>
        <div>
          <small>مهمة</small
          ><strong class="technical" dir="ltr">{{ step }}</strong>
        </div>
      </article>
    </div>
    <p v-else class="truthful-unavailable">
      لا يرسل تعريف المختبر قائمة Steps منظّمة يمكن تمثيلها كمخطط مهام.
    </p>

    <div
      v-if="fieldEntries(lab.configuration, ['steps']).length"
      class="definition-strip"
    >
      <div
        v-for="field in fieldEntries(lab.configuration, ['steps'])"
        :key="field.key"
        class="definition-item"
      >
        <small class="technical" dir="ltr">{{ field.key }}</small
        ><strong>{{ field.value }}</strong>
      </div>
    </div>
  </section>
</template>

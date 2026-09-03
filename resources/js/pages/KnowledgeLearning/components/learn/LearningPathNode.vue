<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  title: string;
  state: 'locked' | 'available' | 'completed';
  active?: boolean;
}>();

const stateClasses = computed(() => {
  switch (props.state) {
    case 'completed':
      return 'bg-green-100 text-green-800 border-green-300 dark:bg-green-900 dark:text-green-200 dark:border-green-700';
    case 'available':
      return 'bg-blue-50 text-blue-800 border-blue-300 dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700';
    default:
      return 'bg-gray-100 text-gray-500 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 opacity-75';
  }
});
</script>

<template>
  <div
    :class="[
      'rounded-lg border p-4 transition-colors',
      stateClasses,
      { 'shadow-md ring-2 ring-blue-500': active },
    ]"
    role="listitem"
  >
    <div class="flex items-center justify-between">
      <h3 class="font-arabic text-right font-medium">{{ title }}</h3>
      <div v-if="state === 'completed'" aria-label="مكتمل">✓</div>
      <div v-else-if="state === 'locked'" aria-label="مغلق">🔒</div>
    </div>
  </div>
</template>

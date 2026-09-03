<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  total: number;
  current: number;
  rtl?: boolean;
}>();

const progressPercentage = computed(() => {
  if (props.total === 0) return 0;
  return (props.current / props.total) * 100;
});
</script>

<template>
  <div
    class="h-2.5 w-full rounded-full bg-slate-800 dark:bg-gray-700"
    :dir="rtl ? 'rtl' : 'ltr'"
    role="progressbar"
    :aria-valuenow="progressPercentage"
    aria-valuemin="0"
    aria-valuemax="100"
  >
    <div
      class="h-2.5 rounded-full bg-blue-600 transition-all duration-300"
      :style="{ width: `${progressPercentage}%` }"
    ></div>
  </div>
</template>

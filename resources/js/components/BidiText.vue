<script setup lang="ts">
defineProps<{ value: string }>();
</script>

<template>
  <bdi class="font-mono text-cyan-200" dir="auto">{{ value }}</bdi>
</template>

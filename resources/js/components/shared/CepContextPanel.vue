<script setup lang="ts">
withDefaults(defineProps<{ label?: string }>(), {
  label: 'السياق',
});
</script>

<template>
  <aside class="cep-context-panel" data-cep-region="right" :aria-label="label">
    <slot />
  </aside>
</template>

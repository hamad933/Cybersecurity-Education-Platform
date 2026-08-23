<script setup lang="ts">
withDefaults(defineProps<{ label?: string }>(), {
  label: 'أدوات العمل',
});
</script>

<template>
  <section class="cep-action-bar" data-cep-region="top" :aria-label="label">
    <slot />
  </section>
</template>

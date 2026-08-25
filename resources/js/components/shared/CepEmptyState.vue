<script setup lang="ts">
defineProps<{
  title: string;
  description?: string;
}>();
</script>

<template>
  <div class="cep-empty-state">
    <h3 class="cep-empty-state__title">{{ title }}</h3>
    <p v-if="description" class="cep-empty-state__description">{{ description }}</p>
    <div v-if="$slots.default" class="cep-empty-state__action">
      <slot />
    </div>
  </div>
</template>

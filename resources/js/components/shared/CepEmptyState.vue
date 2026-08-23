<script setup lang="ts">
defineProps<{
  title: string;
  description: string;
}>();
</script>

<template>
  <section class="cep-empty-state" :aria-label="title">
    <p class="cep-empty-state__title">{{ title }}</p>
    <p class="cep-empty-state__description">{{ description }}</p>
  </section>
</template>

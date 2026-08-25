<script setup lang="ts">
defineProps<{
  value?: string;
}>();
</script>

<template>
  <bdi dir="ltr" class="cep-technical-text"
    ><slot>{{ value }}</slot></bdi
  >
</template>

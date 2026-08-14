<script setup lang="ts">
defineProps<{ value?: string }>();
</script>

<template>
  <bdi dir="ltr" class="cep-ltr" translate="no"><slot>{{ value }}</slot></bdi>
</template>

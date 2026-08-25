<script setup lang="ts">
defineProps<{ pendingAction: string | null; error?: string | null }>();
</script>

<template>
  <div v-if="error" class="sim-notice sim-notice--error" role="alert">
    <span aria-hidden="true">!</span>
    {{ error }}
  </div>
  <div v-else-if="pendingAction" class="sim-notice sim-notice--pending" role="status">
    <span class="sim-pulse" aria-hidden="true" />
    جارٍ تنفيذ الإجراء وحفظ الحالة الفعلية…
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    open?: boolean;
    label?: string;
  }>(),
  {
    open: false,
    label: 'مساحة العمل المؤقتة',
  },
);

const emit = defineEmits<{
  close: [];
}>();
</script>

<template>
  <section
    v-if="open"
    class="cep-temporary-workspace"
    data-cep-region="bottom"
    :aria-label="label"
  >
    <header class="cep-temporary-workspace__header">
      <span>{{ label }}</span>
      <button type="button" class="cep-text-button focus-ring" @click="emit('close')">
        إغلاق
      </button>
    </header>
    <div class="cep-temporary-workspace__body">
      <slot />
    </div>
  </section>
</template>

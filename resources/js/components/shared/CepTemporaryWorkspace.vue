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
  <div
    v-if="open"
    class="cep-temporary-workspace"
    data-cep-region="bottom"
    dir="rtl"
    aria-label="مساحة العمل التشخيصية المؤقتة"
  >
    <div class="cep-temporary-workspace__header">
      <span>{{ label }}</span>
      <button
        type="button"
        class="cep-text-button"
        aria-label="إغلاق مساحة العمل المؤقتة"
        @click="emit('close')"
      >
        إغلاق ✕
      </button>
    </div>
    <div class="cep-temporary-workspace__body">
      <slot />
    </div>
  </div>
</template>

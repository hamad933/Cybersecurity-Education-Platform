<script setup lang="ts">
const props = defineProps<{ status: string }>();

const label = (status: string): string => {
  const labels: Record<string, string> = {
    PASS: 'ناجح',
    WARN: 'تنبيه',
    FAIL: 'فشل',
    accepted: 'مقبول',
    rejected: 'مرفوض',
    pending_review: 'بانتظار المراجعة',
    verified: 'موثّق',
    staged: 'مرحلي',
  };
  return labels[status] ?? status;
};
</script>

<template>
  <span
    class="inline-flex items-center gap-2 rounded-full border border-slate-600 px-3 py-1 text-sm"
  >
    <span aria-hidden="true">{{
      props.status === 'PASS' || props.status === 'accepted' || props.status === 'verified'
        ? '✓'
        : props.status === 'FAIL' || props.status === 'rejected'
          ? '✕'
          : '!'
    }}</span>
    <span>{{ label(props.status) }}</span>
    <bdi dir="ltr" class="text-xs text-slate-400">{{ props.status }}</bdi>
  </span>
</template>

<script setup lang="ts">
defineProps<{ value: string }>();
</script>

<template>
  <bdi
    class="inline-flex rounded-full border px-3 py-1 font-mono text-xs font-bold tracking-wide"
    :class="{
      'border-emerald-700 bg-emerald-950 text-emerald-200':
        value === 'ALLOW' || value === 'MASTERED',
      'border-rose-700 bg-rose-950 text-rose-200': value === 'DENY',
      'border-amber-700 bg-amber-950 text-amber-100':
        value === 'INSUFFICIENT_STATE' || value === 'IN_PROGRESS',
      'border-violet-700 bg-violet-950 text-violet-100': value === 'UNSUPPORTED_STATE',
      'border-slate-700 bg-slate-900 text-slate-300': ![
        'ALLOW',
        'DENY',
        'INSUFFICIENT_STATE',
        'UNSUPPORTED_STATE',
        'MASTERED',
        'IN_PROGRESS',
      ].includes(value),
    }"
    >{{ value }}</bdi
  >
</template>

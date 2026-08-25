<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    status: string;
    variant?: 'auto' | 'ok' | 'danger' | 'warning' | 'info' | 'neutral';
    label?: string;
  }>(),
  {
    variant: 'auto',
    label: undefined,
  },
);

const normalizedVariant = computed(() => {
  if (props.variant !== 'auto') {
    return props.variant;
  }
  const s = props.status.toLowerCase().trim();
  if (['ok', 'healthy', 'completed', 'valid', 'success', 'exported', 'accepted'].includes(s)) {
    return 'ok';
  }
  if (['failed', 'danger', 'error', 'rejected', 'attention', 'invalid'].includes(s)) {
    return 'danger';
  }
  if (['running', 'processing', 'pending_review', 'staged'].includes(s)) {
    return 'info';
  }
  if (['pending', 'warning', 'attention_required'].includes(s)) {
    return 'warning';
  }
  return 'neutral';
});

const displayText = computed(() => props.label ?? props.status);
</script>

<template>
  <span :class="['state-pill', `state-pill--${normalizedVariant}`, normalizedVariant]" dir="ltr">
    <span
      v-if="normalizedVariant === 'ok'"
      class="state-pill__dot state-pill__dot--ok"
      aria-hidden="true"
      >●</span
    >
    <span
      v-else-if="normalizedVariant === 'danger'"
      class="state-pill__dot state-pill__dot--danger"
      aria-hidden="true"
      >✕</span
    >
    <span
      v-else-if="normalizedVariant === 'warning'"
      class="state-pill__dot state-pill__dot--warning"
      aria-hidden="true"
      >▲</span
    >
    <span
      v-else-if="normalizedVariant === 'info'"
      class="state-pill__dot state-pill__dot--info"
      aria-hidden="true"
      >◐</span
    >
    <span v-else class="state-pill__dot state-pill__dot--neutral" aria-hidden="true">○</span>
    <span>{{ displayText }}</span>
  </span>
</template>

<style scoped>
.state-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  border: 1px solid transparent;
  line-height: 1.2;
}

.state-pill--ok,
.state-pill.ok {
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
  border-color: rgba(34, 197, 94, 0.35);
}

.state-pill--danger,
.state-pill.danger {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border-color: rgba(239, 68, 68, 0.35);
}

.state-pill--warning {
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
  border-color: rgba(245, 158, 11, 0.35);
}

.state-pill--info {
  background: rgba(34, 211, 238, 0.15);
  color: #38bdf8;
  border-color: rgba(34, 211, 238, 0.35);
}

.state-pill--neutral {
  background: rgba(148, 163, 184, 0.12);
  color: #94a3b8;
  border-color: rgba(148, 163, 184, 0.25);
}

.state-pill__dot {
  font-size: 0.7rem;
}
</style>

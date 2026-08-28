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
  if (
    ['ok', 'healthy', 'completed', 'valid', 'valid_chain', 'success', 'exported', 'accepted', 'ready', 'pass'].includes(s)
  ) {
    return 'ok';
  }
  if (['failed', 'danger', 'error', 'rejected', 'attention', 'invalid', 'chain_invalid', 'not_ready', 'fail', 'degraded'].includes(s)) {
    return 'danger';
  }
  if (['running', 'processing', 'pending_review', 'staged', 'enabled'].includes(s)) {
    return 'info';
  }
  if (['pending', 'warning', 'warn', 'attention_required'].includes(s)) {
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
    <span class="state-pill__text">{{ displayText }}</span>
  </span>
</template>

<style scoped>
.state-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.22rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 750;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  border: 1px solid transparent;
  line-height: 1.25;
  white-space: nowrap;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  transition: all 120ms ease;
}

.state-pill__text {
  display: inline-block;
}

.state-pill--ok,
.state-pill.ok {
  background: rgba(34, 197, 94, 0.12);
  color: #4ade80;
  border-color: rgba(34, 197, 94, 0.3);
}

:root[data-theme='light'] .state-pill--ok,
:root[data-theme='light'] .state-pill.ok {
  background: rgba(34, 197, 94, 0.15);
  color: #15803d;
  border-color: rgba(34, 197, 94, 0.4);
}

.state-pill--danger,
.state-pill.danger {
  background: rgba(239, 68, 68, 0.12);
  color: #f87171;
  border-color: rgba(239, 68, 68, 0.3);
}

:root[data-theme='light'] .state-pill--danger,
:root[data-theme='light'] .state-pill.danger {
  background: rgba(239, 68, 68, 0.15);
  color: #b91c1c;
  border-color: rgba(239, 68, 68, 0.4);
}

.state-pill--warning {
  background: rgba(245, 158, 11, 0.12);
  color: #fbbf24;
  border-color: rgba(245, 158, 11, 0.3);
}

:root[data-theme='light'] .state-pill--warning {
  background: rgba(245, 158, 11, 0.15);
  color: #b45309;
  border-color: rgba(245, 158, 11, 0.4);
}

.state-pill--info {
  background: rgba(34, 211, 238, 0.12);
  color: #38bdf8;
  border-color: rgba(34, 211, 238, 0.3);
}

:root[data-theme='light'] .state-pill--info {
  background: rgba(8, 145, 178, 0.12);
  color: #0369a1;
  border-color: rgba(8, 145, 178, 0.35);
}

.state-pill--neutral {
  background: rgba(148, 163, 184, 0.1);
  color: #94a3b8;
  border-color: rgba(148, 163, 184, 0.22);
}

:root[data-theme='light'] .state-pill--neutral {
  background: rgba(100, 116, 139, 0.12);
  color: #475569;
  border-color: rgba(100, 116, 139, 0.3);
}

.state-pill__dot {
  font-size: 0.68rem;
  line-height: 1;
}

.state-pill__dot--ok {
  color: #4ade80;
  text-shadow: 0 0 6px rgba(74, 222, 128, 0.5);
}
:root[data-theme='light'] .state-pill__dot--ok {
  color: #16a34a;
  text-shadow: none;
}

.state-pill__dot--danger {
  color: #f87171;
  text-shadow: 0 0 6px rgba(248, 113, 113, 0.5);
}
:root[data-theme='light'] .state-pill__dot--danger {
  color: #dc2626;
  text-shadow: none;
}

.state-pill__dot--warning {
  color: #fbbf24;
  text-shadow: 0 0 6px rgba(251, 191, 36, 0.5);
}
:root[data-theme='light'] .state-pill__dot--warning {
  color: #d97706;
  text-shadow: none;
}

.state-pill__dot--info {
  color: #38bdf8;
  text-shadow: 0 0 6px rgba(56, 189, 248, 0.5);
}
:root[data-theme='light'] .state-pill__dot--info {
  color: #0284c7;
  text-shadow: none;
}

.state-pill__dot--neutral {
  color: #94a3b8;
}
</style>

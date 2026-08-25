<script setup lang="ts">
import { computed } from 'vue';

import { lifecycleTone } from '../formatters';

const props = defineProps<{ value: string; sealed?: boolean }>();
const tone = computed(() => (props.sealed ? 'sealed' : lifecycleTone(props.value)));
</script>

<template>
  <span class="sim-state" :class="`sim-state--${tone}`" :data-state="value">
    <span class="sim-state__dot" aria-hidden="true" />
    {{ value }}
  </span>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import type { NavigationItem, SimulationSection } from '../types';

defineProps<{ navigation: NavigationItem[]; section: SimulationSection }>();
</script>

<template>
  <Link
    v-for="item in navigation"
    :key="item.key"
    :href="item.href"
    class="sim-primary-link"
    :class="{ 'sim-primary-link--active': item.key === section }"
    :aria-current="item.key === section ? 'page' : undefined"
  >
    {{ item.label }}
  </Link>
</template>

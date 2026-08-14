<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

import type { SharedProps } from '../../types';
import { CEP_GLOBAL_DESTINATIONS, type CepDestinationKey } from './navigation';

defineProps<{
  activeDestination: CepDestinationKey;
}>();

const page = usePage<SharedProps>();
</script>

<template>
  <header class="cep-global-header">
    <div class="cep-global-header__inner">
      <div class="cep-brand" aria-label="Cybersecurity Education Platform">
        <span class="cep-brand__mark" dir="ltr">CEP</span>
        <span class="cep-brand__name">منصة تعليم الأمن السيبراني</span>
      </div>

      <nav class="cep-global-nav" aria-label="التنقل العالمي">
        <Link
          v-for="destination in CEP_GLOBAL_DESTINATIONS"
          :key="destination.key"
          :href="destination.href"
          class="cep-global-nav__link focus-ring"
          :class="{ 'cep-global-nav__link--active': destination.key === activeDestination }"
          :aria-current="destination.key === activeDestination ? 'page' : undefined"
        >
          {{ destination.label }}
        </Link>
      </nav>

      <div v-if="page.props.auth.owner" class="cep-session">
        <span class="cep-session__owner">{{ page.props.auth.owner.display_name }}</span>
        <Link
          href="/logout"
          method="post"
          as="button"
          class="cep-session__action focus-ring"
        >
          تسجيل الخروج
        </Link>
      </div>
    </div>
  </header>
</template>

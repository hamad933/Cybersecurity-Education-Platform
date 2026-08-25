<script setup lang="ts">
import { onMounted } from 'vue';

import { useTheme } from '../../composables/cep/useTheme';
import { CEP_GLOBAL_DESTINATIONS, type CepDestinationKey } from './navigation';

defineProps<{
  activeDestination: CepDestinationKey;
  userSession?: string;
}>();

const { theme, initTheme, toggleTheme } = useTheme();

onMounted(() => {
  initTheme();
});
</script>

<template>
  <header class="cep-global-header" data-cep-header>
    <div class="cep-global-header__inner">
      <div class="cep-brand">
        <span class="cep-brand__mark">CEP</span>
        <span class="cep-brand__name">منصة التعليم والتدريب السيبراني</span>
      </div>

      <nav class="cep-global-nav" aria-label="التنقل الرئيسي">
        <a
          v-for="destination in CEP_GLOBAL_DESTINATIONS"
          :key="destination.key"
          :href="destination.href"
          class="cep-global-nav__link"
          :class="{
            'cep-global-nav__link--active': activeDestination === destination.key,
          }"
          :aria-current="activeDestination === destination.key ? 'page' : undefined"
        >
          {{ destination.label }}
        </a>
      </nav>

      <div class="cep-session">
        <button
          type="button"
          class="cep-theme-toggle cep-text-button"
          :aria-label="theme === 'dark' ? 'التحويل إلى المظهر الفاتح' : 'التحويل إلى المظهر الداكن'"
          @click="toggleTheme"
        >
          <span v-if="theme === 'dark'">☀️ فاتح</span>
          <span v-else>🌙 داكن</span>
        </button>

        <slot name="session">
          <span v-if="userSession" class="cep-session__owner" dir="ltr">{{ userSession }}</span>
        </slot>
      </div>
    </div>
  </header>
</template>

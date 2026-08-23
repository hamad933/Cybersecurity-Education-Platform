<script setup lang="ts">
import { computed, useSlots } from 'vue';

import CepGlobalNavigation from '../components/cep/CepGlobalNavigation.vue';
import type { CepDestinationKey } from '../components/cep/navigation';
import CepActionBar from '../components/shared/CepActionBar.vue';
import CepContextPanel from '../components/shared/CepContextPanel.vue';
import CepTemporaryWorkspace from '../components/shared/CepTemporaryWorkspace.vue';

withDefaults(
  defineProps<{
    activeDestination: CepDestinationKey;
    temporaryWorkspaceOpen?: boolean;
    temporaryWorkspaceLabel?: string;
  }>(),
  {
    temporaryWorkspaceOpen: false,
    temporaryWorkspaceLabel: 'مساحة العمل المؤقتة',
  },
);

const emit = defineEmits<{
  closeTemporaryWorkspace: [];
}>();

const slots = useSlots();
const hasPrimaryNavigation = computed(() => Boolean(slots.primaryNavigation));
const hasTop = computed(() => Boolean(slots.top));
const hasLeft = computed(() => Boolean(slots.left));
const hasRight = computed(() => Boolean(slots.right));
</script>

<template>
  <div class="cep-app-shell" dir="rtl" lang="ar">
    <a class="skip-link" href="#main-content">تجاوز إلى المحتوى</a>
    <CepGlobalNavigation :active-destination="activeDestination" />

    <nav
      v-if="hasPrimaryNavigation"
      class="cep-primary-navigation"
      aria-label="التنقل داخل مساحة العمل"
    >
      <slot name="primaryNavigation" />
    </nav>

    <div class="cep-workspace">
      <CepActionBar v-if="hasTop">
        <slot name="top" />
      </CepActionBar>

      <div
        class="cep-workspace-grid"
        dir="ltr"
        :class="{
          'cep-workspace-grid--without-left': !hasLeft && hasRight,
          'cep-workspace-grid--without-right': hasLeft && !hasRight,
          'cep-workspace-grid--center-only': !hasLeft && !hasRight,
        }"
      >
        <aside
          v-if="hasLeft"
          class="cep-structure-panel"
          data-cep-region="left"
          dir="rtl"
          aria-label="البنية"
        >
          <slot name="left" />
        </aside>

        <main
          id="main-content"
          class="cep-primary-surface"
          data-cep-region="center"
          dir="rtl"
          tabindex="-1"
        >
          <slot />
        </main>

        <CepContextPanel v-if="hasRight">
          <slot name="right" />
        </CepContextPanel>
      </div>

      <CepTemporaryWorkspace
        :open="temporaryWorkspaceOpen"
        :label="temporaryWorkspaceLabel"
        @close="emit('closeTemporaryWorkspace')"
      >
        <slot name="bottom" />
      </CepTemporaryWorkspace>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, useSlots, watch } from 'vue';

import CepGlobalNavigation from '../components/cep/CepGlobalNavigation.vue';
import type { CepDestinationKey } from '../components/cep/navigation';
import CepActionBar from '../components/shared/CepActionBar.vue';
import CepContextPanel from '../components/shared/CepContextPanel.vue';
import CepTemporaryWorkspace from '../components/shared/CepTemporaryWorkspace.vue';

const DEFAULT_LEFT_WIDTH = 280;
const MIN_LEFT_WIDTH = 200;
const MAX_LEFT_WIDTH = 480;

const DEFAULT_RIGHT_WIDTH = 320;
const MIN_RIGHT_WIDTH = 220;
const MAX_RIGHT_WIDTH = 500;

function clampLeftWidth(w: number): number {
  return Math.min(Math.max(MIN_LEFT_WIDTH, w), MAX_LEFT_WIDTH);
}

function clampRightWidth(w: number): number {
  return Math.min(Math.max(MIN_RIGHT_WIDTH, w), MAX_RIGHT_WIDTH);
}

const props = withDefaults(
  defineProps<{
    activeDestination: CepDestinationKey;
    userSession?: string;
    temporaryWorkspaceOpen?: boolean;
    temporaryWorkspaceLabel?: string;
    initialLeftWidth?: number;
    initialRightWidth?: number;
    initialLeftCollapsed?: boolean;
    initialRightCollapsed?: boolean;
  }>(),
  {
    temporaryWorkspaceOpen: false,
    temporaryWorkspaceLabel: 'مساحة العمل المؤقتة',
  },
);

const emit = defineEmits<{
  closeTemporaryWorkspace: [];
  'update:leftCollapsed': [value: boolean];
  'update:rightCollapsed': [value: boolean];
  'update:leftWidth': [value: number];
  'update:rightWidth': [value: number];
}>();

const slots = useSlots();
const hasPrimaryNavigation = computed(() => Boolean(slots.primaryNavigation));
const hasTop = computed(() => Boolean(slots.top));
const hasLeft = computed(() => Boolean(slots.left));
const hasRight = computed(() => Boolean(slots.right));

// Reactive Panel State - Initialized with bounded clamping
const leftWidth = ref<number>(
  props.initialLeftWidth !== undefined
    ? clampLeftWidth(props.initialLeftWidth)
    : DEFAULT_LEFT_WIDTH,
);
const rightWidth = ref<number>(
  props.initialRightWidth !== undefined
    ? clampRightWidth(props.initialRightWidth)
    : DEFAULT_RIGHT_WIDTH,
);
const leftCollapsed = ref<boolean>(props.initialLeftCollapsed ?? false);
const rightCollapsed = ref<boolean>(props.initialRightCollapsed ?? false);

const isDraggingLeft = ref(false);
const isDraggingRight = ref(false);

// LocalStorage helpers
function safeGetStorage(key: string): string | null {
  try {
    return localStorage.getItem(key);
  } catch {
    return null;
  }
}

function safeSetStorage(key: string, value: string) {
  try {
    localStorage.setItem(key, value);
  } catch {
    // Ignore storage errors
  }
}

onMounted(() => {
  if (props.initialLeftWidth === undefined) {
    const savedLeftWidth = safeGetStorage('cep-left-width');
    if (savedLeftWidth) {
      const parsed = parseInt(savedLeftWidth, 10);
      if (!isNaN(parsed)) {
        leftWidth.value = clampLeftWidth(parsed);
      }
    }
  }

  if (props.initialRightWidth === undefined) {
    const savedRightWidth = safeGetStorage('cep-right-width');
    if (savedRightWidth) {
      const parsed = parseInt(savedRightWidth, 10);
      if (!isNaN(parsed)) {
        rightWidth.value = clampRightWidth(parsed);
      }
    }
  }

  if (props.initialLeftCollapsed === undefined) {
    const savedLeftCollapsed = safeGetStorage('cep-left-collapsed');
    if (savedLeftCollapsed !== null) {
      leftCollapsed.value = savedLeftCollapsed === 'true';
    }
  }

  if (props.initialRightCollapsed === undefined) {
    const savedRightCollapsed = safeGetStorage('cep-right-collapsed');
    if (savedRightCollapsed !== null) {
      rightCollapsed.value = savedRightCollapsed === 'true';
    }
  }
});

watch(leftWidth, (val) => {
  safeSetStorage('cep-left-width', val.toString());
  emit('update:leftWidth', val);
});

watch(rightWidth, (val) => {
  safeSetStorage('cep-right-width', val.toString());
  emit('update:rightWidth', val);
});

watch(leftCollapsed, (val) => {
  safeSetStorage('cep-left-collapsed', val.toString());
  emit('update:leftCollapsed', val);
});

watch(rightCollapsed, (val) => {
  safeSetStorage('cep-right-collapsed', val.toString());
  emit('update:rightCollapsed', val);
});

function toggleLeftPanel() {
  leftCollapsed.value = !leftCollapsed.value;
}

function toggleRightPanel() {
  rightCollapsed.value = !rightCollapsed.value;
}

function resetLeftWidth() {
  leftWidth.value = DEFAULT_LEFT_WIDTH;
}

function resetRightWidth() {
  rightWidth.value = DEFAULT_RIGHT_WIDTH;
}

// Drag logic for LEFT handle
let dragStartX = 0;
let dragStartWidth = 0;

function startDragLeft(e: PointerEvent) {
  if (e.button !== 0) return;
  e.preventDefault();
  isDraggingLeft.value = true;
  dragStartX = e.clientX;
  dragStartWidth = leftWidth.value;

  if (typeof document !== 'undefined') {
    document.body.style.userSelect = 'none';
    document.body.style.cursor = 'col-resize';
  }

  window.addEventListener('pointermove', onDragLeft);
  window.addEventListener('pointerup', stopDragLeft);
  window.addEventListener('pointercancel', stopDragLeft);
}

function onDragLeft(e: PointerEvent) {
  if (!isDraggingLeft.value) return;
  const deltaX = e.clientX - dragStartX;
  const newWidth = clampLeftWidth(dragStartWidth + deltaX);
  leftWidth.value = newWidth;
}

function stopDragLeft() {
  if (!isDraggingLeft.value) return;
  isDraggingLeft.value = false;

  if (typeof document !== 'undefined') {
    document.body.style.userSelect = '';
    document.body.style.cursor = '';
  }

  window.removeEventListener('pointermove', onDragLeft);
  window.removeEventListener('pointerup', stopDragLeft);
  window.removeEventListener('pointercancel', stopDragLeft);
}

// Drag logic for RIGHT handle
function startDragRight(e: PointerEvent) {
  if (e.button !== 0) return;
  e.preventDefault();
  isDraggingRight.value = true;
  dragStartX = e.clientX;
  dragStartWidth = rightWidth.value;

  if (typeof document !== 'undefined') {
    document.body.style.userSelect = 'none';
    document.body.style.cursor = 'col-resize';
  }

  window.addEventListener('pointermove', onDragRight);
  window.addEventListener('pointerup', stopDragRight);
  window.addEventListener('pointercancel', stopDragRight);
}

function onDragRight(e: PointerEvent) {
  if (!isDraggingRight.value) return;
  const deltaX = e.clientX - dragStartX;
  // Physical LTR grid: Right handle moving right (positive delta) decreases right panel width
  const newWidth = clampRightWidth(dragStartWidth - deltaX);
  rightWidth.value = newWidth;
}

function stopDragRight() {
  if (!isDraggingRight.value) return;
  isDraggingRight.value = false;

  if (typeof document !== 'undefined') {
    document.body.style.userSelect = '';
    document.body.style.cursor = '';
  }

  window.removeEventListener('pointermove', onDragRight);
  window.removeEventListener('pointerup', stopDragRight);
  window.removeEventListener('pointercancel', stopDragRight);
}

onUnmounted(() => {
  stopDragLeft();
  stopDragRight();
});

// Keyboard navigation on resize handles
function handleLeftKeydown(e: KeyboardEvent) {
  if (e.key === 'ArrowRight') {
    e.preventDefault();
    leftWidth.value = clampLeftWidth(leftWidth.value + 10);
  } else if (e.key === 'ArrowLeft') {
    e.preventDefault();
    leftWidth.value = clampLeftWidth(leftWidth.value - 10);
  } else if (e.key === 'Home') {
    e.preventDefault();
    leftWidth.value = MIN_LEFT_WIDTH;
  } else if (e.key === 'End') {
    e.preventDefault();
    leftWidth.value = MAX_LEFT_WIDTH;
  } else if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault();
    resetLeftWidth();
  }
}

function handleRightKeydown(e: KeyboardEvent) {
  if (e.key === 'ArrowLeft') {
    e.preventDefault();
    rightWidth.value = clampRightWidth(rightWidth.value + 10);
  } else if (e.key === 'ArrowRight') {
    e.preventDefault();
    rightWidth.value = clampRightWidth(rightWidth.value - 10);
  } else if (e.key === 'Home') {
    e.preventDefault();
    rightWidth.value = MIN_RIGHT_WIDTH;
  } else if (e.key === 'End') {
    e.preventDefault();
    rightWidth.value = MAX_RIGHT_WIDTH;
  } else if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault();
    resetRightWidth();
  }
}

// Compute dynamic CSS grid column template
const isLeftVisible = computed(() => hasLeft.value && !leftCollapsed.value);
const isRightVisible = computed(() => hasRight.value && !rightCollapsed.value);

const gridStyle = computed(() => {
  let templateCols = 'minmax(0, 1fr)';
  if (isLeftVisible.value && isRightVisible.value) {
    templateCols = `${leftWidth.value}px auto minmax(0, 1fr) auto ${rightWidth.value}px`;
  } else if (isLeftVisible.value && !isRightVisible.value) {
    templateCols = `${leftWidth.value}px auto minmax(0, 1fr)`;
  } else if (!isLeftVisible.value && isRightVisible.value) {
    templateCols = `minmax(0, 1fr) auto ${rightWidth.value}px`;
  }
  return {
    gridTemplateColumns: templateCols,
    '--cep-left-panel-width': `${leftWidth.value}px`,
    '--cep-right-panel-width': `${rightWidth.value}px`,
  };
});
</script>

<template>
  <div class="cep-app-shell" dir="rtl" lang="ar">
    <a class="skip-link" href="#main-content">تجاوز إلى المحتوى</a>
    <CepGlobalNavigation :active-destination="activeDestination" :user-session="userSession">
      <template v-if="$slots.session" #session>
        <slot name="session" />
      </template>
    </CepGlobalNavigation>

    <nav
      v-if="hasPrimaryNavigation"
      class="cep-primary-navigation"
      aria-label="التنقل داخل مساحة العمل"
    >
      <slot name="primaryNavigation" />
    </nav>

    <div class="cep-workspace">
      <!-- Render action bar whenever top slot OR left/right side panels exist -->
      <CepActionBar v-if="hasTop || hasLeft || hasRight">
        <slot name="top" />

        <div v-if="hasLeft || hasRight" class="cep-panel-controls">
          <button
            v-if="hasLeft"
            type="button"
            class="cep-text-button cep-panel-toggle"
            :aria-expanded="!leftCollapsed"
            aria-label="تبديل عرض لوحة البنية (الجانب الأيسر)"
            @click="toggleLeftPanel"
          >
            {{ leftCollapsed ? 'إظهار البنية ◀' : 'إخفاء البنية ▶' }}
          </button>
          <button
            v-if="hasRight"
            type="button"
            class="cep-text-button cep-panel-toggle"
            :aria-expanded="!rightCollapsed"
            aria-label="تبديل عرض لوحة السياق (الجانب الأيمن)"
            @click="toggleRightPanel"
          >
            {{ rightCollapsed ? 'إظهار السياق ▶' : 'إخفاء السياق ◀' }}
          </button>
        </div>
      </CepActionBar>

      <div
        class="cep-workspace-grid"
        dir="ltr"
        :style="gridStyle"
        :class="{
          'cep-workspace-grid--dragging': isDraggingLeft || isDraggingRight,
        }"
      >
        <!-- LEFT PANEL (Structure/Navigation, physical LEFT) -->
        <aside
          v-if="isLeftVisible"
          class="cep-structure-panel"
          data-cep-region="left"
          dir="rtl"
          aria-label="البنية (الجانب الأيسر)"
        >
          <slot name="left" />
        </aside>

        <!-- LEFT RESIZE HANDLE -->
        <div
          v-if="isLeftVisible"
          class="cep-resize-handle cep-resize-handle--left"
          :class="{ 'cep-resize-handle--active': isDraggingLeft }"
          role="separator"
          tabindex="0"
          aria-orientation="vertical"
          :aria-valuenow="leftWidth"
          :aria-valuemin="MIN_LEFT_WIDTH"
          :aria-valuemax="MAX_LEFT_WIDTH"
          aria-label="تغيير عرض لوحة البنية (الجانب الأيسر)"
          @pointerdown="startDragLeft"
          @dblclick="resetLeftWidth"
          @keydown="handleLeftKeydown"
        >
          <span class="cep-resize-handle__indicator" />
        </div>

        <!-- CENTER PRIMARY SURFACE -->
        <main
          id="main-content"
          class="cep-primary-surface"
          data-cep-region="center"
          dir="rtl"
          tabindex="-1"
        >
          <slot />
        </main>

        <!-- RIGHT RESIZE HANDLE -->
        <div
          v-if="isRightVisible"
          class="cep-resize-handle cep-resize-handle--right"
          :class="{ 'cep-resize-handle--active': isDraggingRight }"
          role="separator"
          tabindex="0"
          aria-orientation="vertical"
          :aria-valuenow="rightWidth"
          :aria-valuemin="MIN_RIGHT_WIDTH"
          :aria-valuemax="MAX_RIGHT_WIDTH"
          aria-label="تغيير عرض لوحة السياق (الجانب الأيمن)"
          @pointerdown="startDragRight"
          @dblclick="resetRightWidth"
          @keydown="handleRightKeydown"
        >
          <span class="cep-resize-handle__indicator" />
        </div>

        <!-- RIGHT PANEL (Unique Contextual Info, physical RIGHT) -->
        <CepContextPanel v-if="isRightVisible">
          <slot name="right" />
        </CepContextPanel>
      </div>

      <!-- BOTTOM REGION (Temporary Workspace) -->
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

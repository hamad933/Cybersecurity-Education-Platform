<script setup lang="ts">
import { computed, ref } from 'vue';
import type { OverlayName, OverlayState, ViewMode } from './types';

const props = defineProps<{
  overlay: OverlayState;
  selected: OverlayName | null;
  currentView: ViewMode;
  mode?: 'controls' | 'context';
}>();

const emit = defineEmits<{
  select: [overlay: OverlayName | null];
}>();

const overlays: Array<{ key: OverlayName; ar: string; en: string }> = [
  { key: 'prerequisite', ar: 'المتطلبات السابقة', en: 'Prerequisite' },
  { key: 'coverage', ar: 'التغطية', en: 'Coverage' },
  { key: 'progress', ar: 'التقدم', en: 'Progress' },
  { key: 'evidence', ar: 'الأدلة', en: 'Evidence' },
  { key: 'mastery', ar: 'الإتقان', en: 'Mastery' },
];
const reasonLabels: Record<string, string> = {
  NO_DATA: 'لا توجد بيانات قانونية مرصودة ضمن عالم العرض الحالي.',
  NO_AUTHORITY: 'لا يوجد موفر قراءة مصرح به لهذه الحقيقة في هذا السطح.',
  INVALID_PROVIDER_SCHEMA: 'رفضت الطبقة لأن بيانات الموفر لا تطابق العقد الموثوق.',
  OUT_OF_SCOPE: 'بيانات الطبقة خارج عالم العرض الحالي.',
  NOT_SUPPORTED_IN_VIEW: 'طريقة العرض الحالية لا تملك تمثيلًا دلاليًا لهذه الطبقة.',
};

const inspected = ref<OverlayName | null>(null);
const layer = (key: OverlayName) => props.overlay.layers[key];
const canActivate = (key: OverlayName) => {
  const current = layer(key);
  return current?.available === true && current.supported_views.includes(props.currentView);
};
const reasonFor = (key: OverlayName) => {
  const current = layer(key);
  if (current?.available && !current.supported_views.includes(props.currentView)) {
    return reasonLabels.NOT_SUPPORTED_IN_VIEW;
  }
  return reasonLabels[current?.reason ?? 'NO_DATA'] ?? 'الطبقة غير متاحة في السياق الحالي.';
};
const activateOrInspect = (key: OverlayName) => {
  if (!canActivate(key)) {
    inspected.value = inspected.value === key ? null : key;
    return;
  }
  inspected.value = null;
  emit('select', props.selected === key ? null : key);
};
const selectedItem = computed(() => overlays.find((item) => item.key === props.selected) ?? null);
const selectedLayer = computed(() => (props.selected ? layer(props.selected) : null));
const inspectedItem = computed(() => overlays.find((item) => item.key === inspected.value) ?? null);
</script>

<template>
  <section v-if="mode === 'controls'" aria-labelledby="overlay-controls-title">
    <div class="flex max-w-full flex-nowrap items-center gap-2 overflow-x-auto pb-1">
      <span
        id="overlay-controls-title"
        class="font-mono text-[10px] font-bold text-slate-500"
        dir="ltr"
      >
        OVERLAY
      </span>
      <button
        v-for="item in overlays"
        :key="item.key"
        type="button"
        class="focus-ring inline-flex shrink-0 items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-bold transition"
        :class="[
          selected === item.key
            ? 'border-emerald-400 bg-emerald-500/15 text-emerald-100'
            : 'border-slate-800 bg-slate-950/50 text-slate-400',
          canActivate(item.key)
            ? 'hover:border-slate-600 hover:text-slate-200'
            : 'opacity-65 hover:border-amber-700/70 hover:text-amber-200',
        ]"
        :aria-pressed="selected === item.key"
        :aria-disabled="!canActivate(item.key)"
        :aria-describedby="inspected === item.key ? 'overlay-inspection-reason' : undefined"
        @click="activateOrInspect(item.key)"
      >
        <span>{{ item.ar }}</span>
        <bdi dir="ltr" class="hidden font-mono text-[9px] opacity-70 sm:inline">{{ item.en }}</bdi>
        <span
          class="h-1.5 w-1.5 rounded-full"
          :class="canActivate(item.key) ? 'bg-emerald-400' : 'bg-amber-500/70'"
          aria-hidden="true"
        />
      </button>
    </div>
    <p
      v-if="inspectedItem"
      id="overlay-inspection-reason"
      role="status"
      class="mt-2 rounded-lg border border-amber-800/50 bg-amber-950/25 px-3 py-2 text-[10px] text-amber-200"
    >
      <span class="font-bold">{{ inspectedItem.ar }}:</span>
      {{ reasonFor(inspectedItem.key) }}
    </p>
  </section>

  <section v-else aria-labelledby="overlay-title">
    <p class="text-[10px] font-bold tracking-[0.2em] text-slate-600" dir="ltr">OVERLAY</p>
    <h2 id="overlay-title" class="mt-1 text-sm font-black">سياق الطبقة التحليلية</h2>
    <div
      v-if="selectedItem && selectedLayer?.available"
      class="mt-4 space-y-3 rounded-xl border border-emerald-500/25 bg-emerald-950/15 p-3.5 text-xs"
    >
      <div>
        <span class="font-bold text-slate-200">{{ selectedItem.ar }}</span>
        <bdi dir="ltr" class="ms-2 font-mono text-[10px] text-emerald-300">{{
          selectedItem.en
        }}</bdi>
      </div>
      <div class="border-t border-emerald-500/20 pt-2">
        <span class="block text-[10px] text-slate-500">المصدر القانوني</span>
        <bdi dir="ltr" class="mt-1 block font-mono break-all text-slate-300">
          {{ selectedLayer.source }}
        </bdi>
      </div>
      <p class="leading-5 text-slate-400">
        {{ selectedLayer.observations.length }} ملاحظة مهيكلة تنطبق دلاليًا على
        <bdi dir="ltr">{{ currentView }}</bdi
        >.
      </p>
    </div>
    <p
      v-else
      class="mt-4 rounded-xl border border-dashed border-slate-800 p-3 text-xs leading-6 text-slate-500"
    >
      لا توجد طبقة نشطة. غياب البيانات لا يتحول إلى صفر أو حكم سلبي أو حالة إتقان مفترضة.
    </p>
  </section>
</template>

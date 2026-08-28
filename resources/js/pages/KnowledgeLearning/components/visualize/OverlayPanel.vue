<script setup lang="ts">
import { computed } from 'vue';
import type { OverlayName, OverlayState } from './types';

const props = defineProps<{
  overlay: OverlayState;
  selected: OverlayName | null;
  mode?: 'controls' | 'context';
}>();

const emit = defineEmits<{
  select: [overlay: OverlayName | null];
}>();

const overlays: Array<{ key: OverlayName; ar: string; en: string }> = [
  { key: 'coverage', ar: 'التغطية', en: 'Coverage' },
  { key: 'prerequisite', ar: 'المتطلبات السابقة', en: 'Prerequisite' },
  { key: 'progress', ar: 'التقدم', en: 'Progress' },
  { key: 'evidence', ar: 'الأدلة', en: 'Evidence' },
  { key: 'mastery', ar: 'الإتقان', en: 'Mastery' },
];

const isAvailable = (key: OverlayName) => props.overlay.available.includes(key);
const selectedItem = computed(() => overlays.find((item) => item.key === props.selected) ?? null);
const selectedLayer = computed(() =>
  props.selected ? (props.overlay.layers?.[props.selected] ?? null) : null,
);
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
            ? 'border-cyan-500 bg-cyan-500/15 text-cyan-200'
            : 'border-slate-800 bg-slate-950/50 text-slate-400',
          isAvailable(item.key)
            ? 'hover:border-slate-600 hover:text-slate-200'
            : 'cursor-not-allowed opacity-55',
        ]"
        :disabled="!isAvailable(item.key)"
        :aria-pressed="selected === item.key"
        :aria-label="`${item.ar} (${item.en}) — ${isAvailable(item.key) ? 'بيانات مرصودة' : 'لا توجد بيانات مرصودة'}`"
        @click="emit('select', selected === item.key ? null : item.key)"
      >
        <span>{{ item.ar }}</span>
        <bdi dir="ltr" class="hidden font-mono text-[9px] opacity-70 sm:inline">{{ item.en }}</bdi>
        <span
          class="h-1.5 w-1.5 rounded-full"
          :class="isAvailable(item.key) ? 'bg-emerald-400' : 'bg-slate-600'"
        />
      </button>
    </div>
  </section>

  <section v-else aria-labelledby="overlay-title">
    <div class="flex items-center justify-between gap-3">
      <div>
        <p class="text-[10px] font-bold tracking-[0.2em] text-slate-600" dir="ltr">OVERLAY</p>
        <h2 id="overlay-title" class="mt-1 text-sm font-black">سياق الطبقة المحددة</h2>
      </div>
    </div>

    <div
      v-if="selectedItem && selectedLayer?.available"
      class="mt-4 space-y-3 rounded-xl border border-slate-800 bg-slate-950/50 p-3.5 text-xs"
    >
      <div>
        <span class="font-bold text-slate-200">{{ selectedItem.ar }}</span>
        <bdi dir="ltr" class="ms-2 font-mono text-[10px] text-cyan-300">{{ selectedItem.en }}</bdi>
      </div>
      <div v-if="selectedLayer.source" class="border-t border-slate-800 pt-2">
        <span class="block text-[10px] text-slate-500">مصدر الرصد</span>
        <bdi dir="ltr" class="mt-1 block font-mono break-all text-slate-300">
          {{ selectedLayer.source }}
        </bdi>
      </div>
      <p v-if="selectedLayer.reason" class="leading-6 text-slate-400">
        {{ selectedLayer.reason }}
      </p>
    </div>

    <p
      v-else
      class="mt-4 rounded-xl border border-dashed border-slate-800 p-3 text-xs leading-6 text-slate-500"
    >
      لم تُحدّد طبقة مرصودة. اختيار الطبقات متاح في شريط الأدوات العلوي فقط، وغياب البيانات لا يتحول
      إلى نسبة أو حالة مفترضة.
    </p>
  </section>
</template>

<script setup lang="ts">
import type { OverlayName, OverlayState } from './types';

const props = defineProps<{
  overlay: OverlayState;
  selected: OverlayName | null;
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
</script>

<template>
  <section aria-labelledby="overlay-title">
    <div class="flex items-center justify-between gap-3">
      <div>
        <p class="text-[10px] font-bold tracking-[0.2em] text-slate-600" dir="ltr">OVERLAY</p>
        <h2 id="overlay-title" class="mt-1 text-sm font-black">طبقات التحليل المرصودة</h2>
      </div>
      <button
        v-if="selected"
        type="button"
        class="focus-ring rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-400"
        @click="emit('select', null)"
      >
        إزالة الطبقة
      </button>
    </div>

    <div class="mt-4 space-y-2">
      <button
        v-for="item in overlays"
        :key="item.key"
        type="button"
        class="focus-ring flex w-full items-center justify-between gap-3 rounded-xl border px-3 py-3 text-right transition"
        :class="[
          selected === item.key
            ? 'border-cyan-500 bg-cyan-950/30'
            : 'border-slate-800 bg-slate-950/35',
          isAvailable(item.key)
            ? 'text-slate-200 hover:border-slate-600'
            : 'cursor-not-allowed text-slate-600',
        ]"
        :disabled="!isAvailable(item.key)"
        :aria-pressed="selected === item.key"
        @click="emit('select', item.key)"
      >
        <span>
          <span class="block text-sm font-bold">{{ item.ar }}</span>
          <bdi dir="ltr" class="mt-1 block text-[10px] text-slate-500">{{ item.en }}</bdi>
        </span>
        <span
          class="rounded-full border px-2 py-1 text-[10px]"
          :class="
            isAvailable(item.key)
              ? 'border-emerald-800 text-emerald-300'
              : 'border-slate-800 text-slate-600'
          "
        >
          {{ isAvailable(item.key) ? 'مرصودة' : 'لا توجد بيانات مرصودة' }}
        </span>
      </button>
    </div>

    <p class="mt-4 text-xs leading-6 text-slate-500">
      الطبقة لا تظهر إلا عندما يقدّم التطبيق بيانات مرصودة فعلًا. غياب البيانات يبقى غيابًا، ولا
      يتحول إلى نسبة أو حالة افتراضية.
    </p>
  </section>
</template>

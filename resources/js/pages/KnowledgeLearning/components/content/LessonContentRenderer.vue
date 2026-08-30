<script setup lang="ts">
import { computed } from 'vue';
import {
  inlineLessonTokens,
  isTechnicalLessonBlock,
  lessonBlockDefinition,
  normalizeLessonBlocks,
  type LessonContentContract,
  type StoredLessonBlock,
} from './lessonContent';

const props = defineProps<{
  blocks: StoredLessonBlock[];
  contract: LessonContentContract;
  activeIndex?: number | null;
}>();

const emit = defineEmits<{ select: [index: number] }>();
const normalizedBlocks = computed(() => normalizeLessonBlocks(props.blocks));
</script>

<template>
  <section class="space-y-3" aria-label="محتوى مراجعة الدرس المنشورة">
    <article
      v-for="(block, index) in normalizedBlocks"
      :id="`lesson-block-${index}`"
      :key="`${index}:${block.type}:${block.depth}`"
      class="scroll-mt-6 rounded-xl border p-4 transition"
      :class="[
        activeIndex === index
          ? 'border-cyan-500/60 bg-cyan-950/20 shadow-sm shadow-cyan-950/40'
          : 'border-slate-800/80 bg-slate-950/50',
        block.type === 'callout'
          ? 'border-cyan-800/80'
          : block.type === 'rules'
            ? 'border-indigo-800/80'
            : block.type === 'boundaries'
              ? 'border-amber-800/80'
              : '',
      ]"
      :style="{ marginInlineStart: `${block.depth * 1.1}rem` }"
    >
      <header class="mb-2 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <span class="font-mono text-[10px] font-bold text-cyan-400">
            {{ String(index + 1).padStart(2, '0') }}
          </span>
          <span class="text-[10px] font-semibold text-slate-500">
            {{ lessonBlockDefinition(contract, block.type)?.label_ar ?? block.type }}
          </span>
        </div>
        <button
          type="button"
          class="focus-ring rounded-md px-2 py-1 text-[10px] text-slate-500 hover:bg-slate-800 hover:text-slate-200"
          :aria-label="`تعيين الكتلة ${index + 1} كموضع القراءة الحالي`"
          :aria-current="activeIndex === index ? 'location' : undefined"
          @click="emit('select', index)"
        >
          {{ activeIndex === index ? 'الموضع الحالي' : 'تعيين الموضع' }}
        </button>
      </header>

      <pre
        v-if="isTechnicalLessonBlock(contract, block.type)"
        dir="ltr"
        class="overflow-x-auto text-left font-mono text-xs leading-6 whitespace-pre-wrap text-slate-200"
      ><code>{{ block.body }}</code></pre>
      <h2
        v-else-if="block.type === 'heading'"
        class="text-base leading-8 font-black text-slate-100"
      >
        {{ block.body }}
      </h2>
      <p v-else class="text-sm leading-7 whitespace-pre-wrap text-slate-200">
        <template
          v-for="(token, tokenIndex) in inlineLessonTokens(block.body)"
          :key="`${index}:${tokenIndex}`"
        >
          <strong v-if="token.kind === 'strong'">{{ token.text }}</strong>
          <em v-else-if="token.kind === 'emphasis'">{{ token.text }}</em>
          <code
            v-else-if="token.kind === 'code'"
            dir="ltr"
            class="rounded bg-slate-800 px-1 font-mono text-[0.92em]"
            >{{ token.text }}</code
          >
          <a
            v-else-if="token.kind === 'link' && token.href"
            :href="token.href"
            target="_blank"
            rel="noopener noreferrer"
            class="focus-ring text-cyan-300 underline decoration-cyan-700 underline-offset-4"
            >{{ token.text }}</a
          >
          <span v-else>{{ token.text }}</span>
        </template>
      </p>
    </article>

    <p
      v-if="!normalizedBlocks.length"
      class="rounded-xl border border-dashed border-slate-800 bg-slate-950/30 p-6 text-center text-xs text-slate-500"
    >
      المراجعة المنشورة لا تحتوي كتل محتوى قابلة للعرض.
    </p>
  </section>
</template>

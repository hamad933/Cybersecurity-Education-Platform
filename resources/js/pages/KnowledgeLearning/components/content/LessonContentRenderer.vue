<script setup lang="ts">
import { computed } from 'vue';
import {
  inlineLessonTokens,
  isTechnicalLessonBlock,
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
const headingTag = (depth: number) => (depth <= 0 ? 'h2' : depth === 1 ? 'h3' : 'h4');
</script>

<template>
  <section class="lesson-document space-y-1" aria-label="محتوى الدرس">
    <article
      v-for="(block, index) in normalizedBlocks"
      :id="`lesson-block-${index}`"
      :key="`${index}:${block.type}:${block.depth}`"
      class="lesson-document-block scroll-mt-24 border-s-2 px-3 py-2 transition sm:px-5"
      :class="[
        activeIndex === index ? 'border-cyan-400 bg-cyan-950/10' : 'border-transparent',
        block.type === 'callout'
          ? 'my-4 rounded-e-xl border-s-cyan-500 bg-cyan-950/25 py-4'
          : block.type === 'rules'
            ? 'my-4 rounded-e-xl border-s-indigo-500 bg-indigo-950/20 py-4'
            : block.type === 'boundaries'
              ? 'my-4 rounded-e-xl border-s-amber-500 bg-amber-950/20 py-4'
              : '',
      ]"
      :style="{ marginInlineStart: `${block.depth * 0.8}rem` }"
      @click="emit('select', index)"
      @focusin="emit('select', index)"
    >
      <pre
        v-if="isTechnicalLessonBlock(contract, block.type)"
        dir="ltr"
        class="my-2 overflow-x-auto rounded-xl border border-slate-800 bg-[#050911] p-4 text-left font-mono text-xs leading-6 whitespace-pre-wrap text-emerald-200"
      ><code>{{ block.body }}</code></pre>
      <component
        :is="headingTag(block.depth)"
        v-else-if="block.type === 'heading'"
        dir="auto"
        class="bidi-plaintext mt-5 mb-2 font-black tracking-tight text-slate-100"
        :class="
          block.depth === 0 ? 'text-xl sm:text-2xl' : block.depth === 1 ? 'text-lg' : 'text-base'
        "
      >
        {{ block.body }}
      </component>
      <p
        v-else
        dir="auto"
        class="bidi-plaintext text-sm leading-8 whitespace-pre-wrap text-slate-200"
      >
        <template
          v-for="(token, tokenIndex) in inlineLessonTokens(block.body)"
          :key="`${index}:${tokenIndex}`"
        >
          <strong v-if="token.kind === 'strong'" dir="auto" class="bidi-isolate">{{
            token.text
          }}</strong>
          <em v-else-if="token.kind === 'emphasis'" dir="auto" class="bidi-isolate">{{
            token.text
          }}</em>
          <code
            v-else-if="token.kind === 'code'"
            dir="ltr"
            class="bidi-isolate rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[0.92em] text-cyan-100"
            >{{ token.text }}</code
          >
          <a
            v-else-if="token.kind === 'link' && token.href"
            :href="token.href"
            dir="auto"
            target="_blank"
            rel="noopener noreferrer"
            class="bidi-isolate focus-ring text-cyan-300 underline decoration-cyan-700 underline-offset-4"
            >{{ token.text }}</a
          >
          <span v-else dir="auto" class="bidi-plaintext">{{ token.text }}</span>
        </template>
      </p>
    </article>

    <div
      v-if="!normalizedBlocks.length"
      class="rounded-2xl border border-dashed border-slate-700 bg-slate-950/25 p-10 text-center"
      role="status"
    >
      <h3 class="text-sm font-bold text-slate-300">لا يوجد محتوى بعد</h3>
      <p class="mt-2 text-xs text-slate-500">ستظهر أقسام الوثيقة هنا عند توفر مراجعة محتوى.</p>
    </div>
  </section>
</template>

<style scoped>
.bidi-plaintext {
  unicode-bidi: plaintext;
  text-align: start;
}

.bidi-isolate {
  unicode-bidi: isolate;
}
</style>

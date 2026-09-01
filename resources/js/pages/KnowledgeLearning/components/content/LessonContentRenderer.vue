<script setup lang="ts">
import { computed, ref, watch } from 'vue';
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
const collapsedSections = ref(new Set<string>());
const headingTag = (depth: number) => (depth <= 0 ? 'h2' : depth === 1 ? 'h3' : 'h4');

const sectionBoundary = (headingIndex: number): number => {
  const heading = normalizedBlocks.value[headingIndex];
  if (!heading || heading.type !== 'heading') return headingIndex + 1;
  for (let index = headingIndex + 1; index < normalizedBlocks.value.length; index += 1) {
    const candidate = normalizedBlocks.value[index];
    if (candidate?.type === 'heading' && candidate.depth <= heading.depth) return index;
  }
  return normalizedBlocks.value.length;
};

const isVisible = (index: number): boolean => {
  for (let headingIndex = index - 1; headingIndex >= 0; headingIndex -= 1) {
    const heading = normalizedBlocks.value[headingIndex];
    if (
      heading?.type === 'heading' &&
      collapsedSections.value.has(heading.id) &&
      index < sectionBoundary(headingIndex)
    ) {
      return false;
    }
  }
  return true;
};

const toggleCollapse = (id: string) => {
  const next = new Set(collapsedSections.value);
  if (next.has(id)) next.delete(id);
  else next.add(id);
  collapsedSections.value = next;
};

const revealActiveBlock = (activeIndex: number | null | undefined) => {
  if (activeIndex === null || activeIndex === undefined || activeIndex < 0) return;
  if (activeIndex >= normalizedBlocks.value.length) return;

  const next = new Set(collapsedSections.value);
  let changed = false;
  for (let headingIndex = 0; headingIndex < activeIndex; headingIndex += 1) {
    const heading = normalizedBlocks.value[headingIndex];
    if (
      heading?.type === 'heading' &&
      next.has(heading.id) &&
      activeIndex < sectionBoundary(headingIndex)
    ) {
      next.delete(heading.id);
      changed = true;
    }
  }
  if (changed) collapsedSections.value = next;
};

watch(
  () => [props.activeIndex, normalizedBlocks.value.map((block) => block.id).join('|')] as const,
  ([activeIndex]) => revealActiveBlock(activeIndex),
  { immediate: true },
);
</script>

<template>
  <section class="lesson-document space-y-1" aria-label="محتوى الدرس">
    <article
      v-for="(block, index) in normalizedBlocks"
      v-show="isVisible(index)"
      :id="`lesson-block-${index}`"
      :key="block.id"
      :data-block-id="block.id"
      class="lesson-document-block relative scroll-mt-24 border-s-2 px-3 py-2 transition sm:px-5"
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
      <div v-if="block.type === 'heading'" class="flex items-start gap-2">
        <button
          type="button"
          class="focus-ring mt-5 grid h-6 w-6 shrink-0 place-items-center rounded-md text-slate-500 transition hover:bg-slate-800 hover:text-cyan-300"
          :aria-expanded="!collapsedSections.has(block.id)"
          :aria-label="collapsedSections.has(block.id) ? 'توسيع القسم' : 'طي القسم'"
          :title="collapsedSections.has(block.id) ? 'توسيع القسم' : 'طي القسم'"
          @click.stop="toggleCollapse(block.id)"
        >
          <span
            aria-hidden="true"
            class="text-[11px] transition-transform"
            :class="collapsedSections.has(block.id) ? '-rotate-90' : ''"
          >
            ▼
          </span>
        </button>
        <component
          :is="headingTag(block.depth)"
          dir="auto"
          class="bidi-plaintext mt-5 mb-2 min-w-0 font-black tracking-tight text-slate-100"
          :class="
            block.depth === 0 ? 'text-xl sm:text-2xl' : block.depth === 1 ? 'text-lg' : 'text-base'
          "
        >
          {{ block.body }}
        </component>
      </div>

      <pre
        v-else-if="isTechnicalLessonBlock(contract, block.type)"
        dir="ltr"
        class="my-2 overflow-x-auto rounded-xl border border-slate-800 bg-[#050911] p-4 text-left font-mono text-xs leading-6 whitespace-pre-wrap text-emerald-200"
      ><code>{{ block.body }}</code></pre>

      <p
        v-else
        dir="auto"
        class="bidi-plaintext text-sm leading-8 whitespace-pre-wrap text-slate-200"
      >
        <template
          v-for="(token, tokenIndex) in inlineLessonTokens(block.body)"
          :key="`${block.id}:${tokenIndex}`"
        >
          <strong v-if="token.kind === 'strong'" dir="auto" class="bidi-isolate">
            {{ token.text }}
          </strong>
          <em v-else-if="token.kind === 'emphasis'" dir="auto" class="bidi-isolate">
            {{ token.text }}
          </em>
          <code
            v-else-if="token.kind === 'code'"
            dir="ltr"
            class="bidi-isolate rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[0.92em] text-cyan-100"
          >
            {{ token.text }}
          </code>
          <a
            v-else-if="token.kind === 'link' && token.href"
            :href="token.href"
            dir="auto"
            target="_blank"
            rel="noopener noreferrer"
            class="bidi-isolate focus-ring text-cyan-300 underline decoration-cyan-700 underline-offset-4"
          >
            {{ token.text }}
          </a>
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

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import CepWorkspaceLayout from '../../layouts/CepWorkspaceLayout.vue';
import KnowledgeTabs from './components/KnowledgeTabs.vue';
import LessonContentRenderer from './components/content/LessonContentRenderer.vue';
import {
  lessonBlockDefinition,
  normalizeLessonBlocks,
  type KnowledgeUnitSelection,
  type LessonContentContract,
  type LessonDelivery,
} from './components/content/lessonContent';

type CatalogItem = {
  id: string;
  title_ar: string;
  title_en: string;
  latest_revision: number | null;
  latest_state: string | null;
  revision_count: number;
  published_revision: number | null;
  lesson_availability: string;
};

type ActiveUnit = {
  id: string;
  canonical_ref: { kind: 'knowledge_unit'; id: string };
  title_ar: string;
  title_en: string;
};

type HandoffBoundary = {
  target_workspace?: string;
  target_area?: string;
  state?: string;
  reason?: string;
  executable?: boolean;
  href?: string | null;
};

type LabReference = {
  id: string;
  title_ar?: string;
  title_en?: string;
  summary_ar?: string;
  preview_state?: string;
  canonical_owner?: string;
  prepare_run_handoff?: HandoffBoundary;
};

type PracticeDefinition = {
  title_ar?: string;
  title_en?: string;
  estimated_minutes?: number;
  lab_reference?: LabReference;
  [key: string]: unknown;
};

type AssessmentState = {
  state: string;
  integration_state?: string;
  semantic_owner?: string | null;
  fake_fallback_allowed?: boolean;
  executable?: boolean;
  href?: string | null;
  definitions?: unknown[];
  results?: unknown[];
};

type JourneyItem = {
  id: string;
  practice_id: string;
  revision: number;
  capability_id: string;
  attempt_count: number;
  successful_attempt_count: number;
  latest_outcome: string | null;
  latest_activity_at: string | null;
  activity_state: string;
  activity_completed: boolean;
  completion_semantics: string;
  definition: PracticeDefinition;
};

type SourceContext = {
  id: string;
  title: string;
  authority_class: string;
  review_status: string;
};

const props = defineProps<{
  catalog: CatalogItem[];
  active: ActiveUnit | null;
  lesson: LessonDelivery;
  selection: KnowledgeUnitSelection;
  content_contract: LessonContentContract;
  journey: {
    state: string;
    items: JourneyItem[];
    labs: LabReference[];
    assessments: AssessmentState;
    next: { state: string; practice_id: string | null; completion_is_mastery?: boolean };
    activity: {
      practice_count: number;
      attempt_count: number;
      completed_practice_count: number;
      started_practice_count: number;
      completion_is_mastery: boolean;
      latest_activity_at: string | null;
      semantic_scope: string;
    };
  };
  context: {
    placements: Array<{
      id: string;
      capability_id: string;
      knowledge_unit_id: string;
      revision: number;
      lifecycle: Record<string, unknown>;
    }>;
    sources: SourceContext[];
    prerequisites: {
      state: string;
      items: unknown[];
      availability_may_be_inferred: boolean;
    };
    navigation: Record<string, string | null>;
    resume: {
      storage: string;
      server_persisted: boolean;
      semantic_scope: string;
    };
  };
  semantic_boundary: {
    progress: string;
    completion: string;
    mastery: string;
  };
}>();

const selectedStepId = ref<string | null>(props.journey.items[0]?.id ?? null);
const selectedStep = computed<JourneyItem | null>(
  () =>
    props.journey.items.find((item) => item.id === selectedStepId.value) ??
    props.journey.items[0] ??
    null,
);
const selectedLab = computed<LabReference | null>(() => {
  const labId = selectedStep.value?.definition.lab_reference?.id;
  return props.journey.labs.find((lab) => lab.id === labId) ?? null;
});
const selectStep = (id: string) => {
  selectedStepId.value = id;
};

watch(
  () => props.journey.items.map((item) => item.id).join(':'),
  () => {
    if (!props.journey.items.some((item) => item.id === selectedStepId.value)) {
      selectedStepId.value = props.journey.items[0]?.id ?? null;
    }
  },
);

const lessonBlocks = computed(() => normalizeLessonBlocks(props.lesson.revision?.blocks ?? []));
const lessonAvailable = computed(
  () =>
    props.lesson.availability === 'AVAILABLE_PUBLISHED_REVISION' && props.lesson.revision !== null,
);
const selectedBlockIndex = ref(0);
const resumeRestored = ref(false);
const resumeKey = () =>
  props.lesson.revision ? `cep:knowledge-learn:position:${props.lesson.revision.id}` : null;

const restoreReadingPosition = () => {
  selectedBlockIndex.value = 0;
  resumeRestored.value = false;
  const key = resumeKey();
  if (!key || typeof window === 'undefined') return;

  const stored = Number.parseInt(window.localStorage.getItem(key) ?? '', 10);
  if (Number.isInteger(stored) && stored >= 0 && stored < lessonBlocks.value.length) {
    selectedBlockIndex.value = stored;
    resumeRestored.value = stored > 0;
  }
};

const selectLessonBlock = (index: number) => {
  if (index < 0 || index >= lessonBlocks.value.length) return;
  selectedBlockIndex.value = index;
  resumeRestored.value = false;
  const key = resumeKey();
  if (key && typeof window !== 'undefined') window.localStorage.setItem(key, String(index));
  void nextTick(() => {
    document.getElementById(`lesson-block-${index}`)?.scrollIntoView({
      behavior: 'smooth',
      block: 'nearest',
    });
  });
};

watch(() => props.lesson.revision?.id, restoreReadingPosition);
onMounted(restoreReadingPosition);

const lessonOutline = computed(() =>
  lessonBlocks.value.map((block, index) => ({
    index,
    depth: block.depth,
    label:
      block.type === 'heading'
        ? block.body
        : (lessonBlockDefinition(props.content_contract, block.type)?.label_ar ?? block.type),
  })),
);

const shelfOpen = ref(false);
const toggleShelf = () => {
  shelfOpen.value = !shelfOpen.value;
};

const visualizeHref = computed(() => props.context.navigation.visualize ?? '/knowledge/visualize');
const libraryHref = computed(() => props.context.navigation.library ?? '/knowledge');
const qualityHref = computed(
  () => props.context.navigation.research_quality ?? '/knowledge/research-quality',
);
</script>

<template>
  <Head title="المعرفة والتعلّم — التعلّم والدروس" />
  <CepWorkspaceLayout active-destination="knowledge">
    <template #primaryNavigation>
      <KnowledgeTabs active="learn" :object-id="active?.id" />
    </template>

    <div
      dir="rtl"
      class="kl-learn-route min-h-full bg-[var(--cep-bg-canvas)] text-[var(--cep-text)]"
    >
      <div class="w-full px-0 py-3 sm:px-4 xl:px-6">
        <p
          v-if="selection.state === 'REQUESTED_UNIT_NOT_FOUND_FALLBACK'"
          role="alert"
          class="mb-3 rounded-xl border border-amber-700/60 bg-amber-950/40 px-4 py-2.5 text-xs text-amber-200"
        >
          لم تُعثر على وحدة المعرفة المطلوبة. عُرض أول كائن قانوني متاح مع إبقاء حالة الاختيار
          صريحة.
        </p>
        <div
          dir="ltr"
          class="grid min-h-[740px] grid-cols-1 gap-4 md:grid-cols-[220px_minmax(0,1fr)] xl:grid-cols-[280px_minmax(0,1fr)_300px]"
        >
          <!-- LEFT: Learning Journey (Visual LEFT) -->
          <aside
            dir="rtl"
            class="order-2 flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur md:order-1 md:max-h-[calc(100vh-10rem)] xl:max-h-none dark:bg-[#0b1322]/90"
            aria-label="رحلة التعلّم"
          >
            <div class="border-b border-slate-800/80 pb-3">
              <div class="flex items-center justify-between gap-2">
                <h2 class="text-xs font-bold text-slate-200">مسار التعلّم</h2>
                <bdi dir="ltr" class="font-mono text-[9px] text-slate-500">
                  {{ lesson.availability }}
                </bdi>
              </div>
              <p class="mt-2.5 truncate text-xs font-semibold text-slate-300">
                {{ active?.title_ar ?? 'لا توجد وحدة معرفة نشطة' }}
              </p>
            </div>

            <div class="mt-3 flex-1 space-y-4 overflow-y-auto pr-0.5 text-xs">
              <section v-if="lessonOutline.length" aria-labelledby="lesson-outline-heading">
                <div class="flex items-center justify-between gap-2">
                  <p id="lesson-outline-heading" class="text-[10px] font-bold text-slate-500">
                    بنية مراجعة الدرس المنشورة
                  </p>
                  <span class="font-mono text-[9px] text-slate-500">
                    {{ selectedBlockIndex + 1 }}/{{ lessonOutline.length }}
                  </span>
                </div>
                <ol class="mt-2 space-y-1">
                  <li v-for="item in lessonOutline" :key="item.index">
                    <button
                      type="button"
                      class="focus-ring flex w-full items-start gap-2 rounded-lg px-2 py-1.5 text-right transition"
                      :class="
                        item.index === selectedBlockIndex
                          ? 'border border-cyan-500/40 bg-cyan-500/10 text-cyan-100'
                          : 'border border-transparent text-slate-400 hover:bg-slate-900/60 hover:text-slate-200'
                      "
                      :style="{ paddingInlineStart: `${0.5 + item.depth * 0.7}rem` }"
                      :aria-current="item.index === selectedBlockIndex ? 'location' : undefined"
                      @click="selectLessonBlock(item.index)"
                    >
                      <span class="font-mono text-[9px] text-cyan-500">
                        {{ String(item.index + 1).padStart(2, '0') }}
                      </span>
                      <span class="line-clamp-2 leading-5">{{ item.label }}</span>
                    </button>
                  </li>
                </ol>
              </section>

              <section class="border-t border-slate-800/80 pt-3" aria-labelledby="practice-heading">
                <p id="practice-heading" class="text-[10px] font-bold text-slate-500">
                  Practice — انتقالات سياقية
                </p>
                <div v-if="journey.items.length" class="mt-2 space-y-1.5">
                  <button
                    v-for="(item, index) in journey.items"
                    :key="item.id"
                    type="button"
                    class="focus-ring block w-full rounded-lg px-2.5 py-1.5 text-right text-xs transition"
                    :class="
                      item.id === selectedStep?.id
                        ? 'border border-cyan-500/40 bg-cyan-500/10 text-cyan-100'
                        : 'text-slate-400 hover:bg-slate-900/60 hover:text-slate-200'
                    "
                    @click="selectStep(item.id)"
                  >
                    <div class="flex items-center justify-between gap-2">
                      <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                          <span>{{ index + 1 }}.</span>
                          <span class="truncate font-semibold">
                            {{ item.definition.title_ar ?? item.practice_id }}
                          </span>
                        </div>
                        <bdi
                          dir="ltr"
                          class="mt-0.5 block truncate font-mono text-[9px] opacity-70"
                        >
                          {{ item.practice_id }} · {{ item.activity_state }}
                        </bdi>
                      </div>
                    </div>
                  </button>
                </div>

                <div
                  v-else
                  class="mt-2 rounded-xl border border-dashed border-slate-800 bg-slate-950/30 p-3 text-center text-slate-500"
                >
                  لا توجد أنشطة Practice مسجلة لهذه الوحدة؛ لم تُنشأ بدائل وهمية.
                </div>
              </section>

              <section class="grid grid-cols-2 gap-2 border-t border-slate-800/80 pt-3 text-[10px]">
                <div class="rounded-lg border border-slate-800 bg-slate-950/50 p-2">
                  <span class="block text-slate-500">Assessment</span>
                  <bdi dir="ltr" class="mt-1 block font-mono break-all text-amber-300">
                    {{ journey.assessments.integration_state ?? journey.assessments.state }}
                  </bdi>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-950/50 p-2">
                  <span class="block text-slate-500">Lab</span>
                  <bdi dir="ltr" class="mt-1 block font-mono text-slate-300">
                    {{ journey.labs.length ? 'REFERENCE_ONLY' : 'NO_REFERENCE' }}
                  </bdi>
                </div>
              </section>
            </div>

            <div class="mt-3 border-t border-slate-800/80 pt-3">
              <Link
                :href="visualizeHref"
                class="focus-ring flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700/80 bg-slate-800/60 px-3 py-2 text-xs font-bold text-slate-200 shadow-sm transition hover:bg-slate-800 hover:text-white"
              >
                <span>🗺️</span>
                <span>عرض خريطة المسار</span>
              </Link>
            </div>
          </aside>

          <!-- CENTER: Lesson Surface (Visual CENTER) -->
          <main
            dir="rtl"
            class="order-1 flex min-w-0 flex-1 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur sm:p-5 md:order-2 xl:p-7 dark:bg-[#0b1322]/90"
            aria-label="سطح التعلّم"
          >
            <div v-if="active" class="flex min-w-0 flex-1 flex-col">
              <header class="border-b border-slate-800/80 pb-5">
                <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                  <nav
                    aria-label="سياق وحدة المعرفة"
                    class="flex min-w-0 items-center gap-1.5 text-slate-400"
                  >
                    <span class="font-semibold text-slate-300">{{ active.title_ar }}</span>
                  </nav>
                  <div
                    class="rounded-lg border border-slate-800 bg-slate-950/60 px-2.5 py-1 text-xs font-medium text-slate-400"
                    :class="lessonAvailable ? 'text-emerald-300' : 'text-amber-300'"
                  >
                    {{ lessonAvailable ? 'مراجعة منشورة متاحة' : 'الدرس غير متوفر' }}
                  </div>
                </div>

                <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-cyan-300">سطح الدرس والمحتوى التعليمي</p>
                    <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-100 sm:text-3xl">
                      {{
                        lessonAvailable
                          ? active.title_ar
                          : 'لا يتوفر درس منشور لهذه الوحدة المعرفية'
                      }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-400">
                      <template v-if="lessonAvailable">
                        هذا المحتوى إسقاط تعلّم للنسخة المنشورة من كائن المعرفة نفسه؛ لا توجد نسخة
                        Lesson مكررة ولا تُعرض مسودة Library للمتعلّم.
                      </template>
                      <template v-else>
                        الوحدة النشطة هي
                        <span class="font-semibold text-slate-200">{{ active.title_ar }}</span
                        >. لا توجد مراجعة منشورة صالحة للتسليم، لذلك بقي السطح في حالة عدم توفر
                        صريحة.
                      </template>
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                      <bdi
                        dir="ltr"
                        class="rounded-full border border-cyan-900/60 bg-cyan-950/20 px-3 py-1 font-mono text-[11px] font-bold text-cyan-300"
                      >
                        {{ active.id }}
                      </bdi>
                      <span
                        class="rounded-full border border-slate-700/80 bg-slate-800/80 px-3 py-1 text-[11px] text-slate-300"
                      >
                        وحدة معرفية (Knowledge Unit)
                      </span>
                      <span
                        v-if="lesson.revision"
                        class="rounded-full border border-slate-700/80 bg-slate-800/80 px-3 py-1 font-mono text-[11px] text-slate-300"
                      >
                        مراجعة {{ lesson.revision.revision }} · {{ lesson.revision.state }}
                      </span>
                    </div>
                  </div>
                </div>

                <div
                  class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800/90 bg-slate-950/80 px-4 py-3 shadow-sm"
                >
                  <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span
                      class="h-2 w-2 rounded-full"
                      :class="lessonAvailable ? 'bg-emerald-400' : 'bg-amber-400'"
                    ></span>
                    <span v-if="lessonAvailable">
                      موضع القراءة {{ selectedBlockIndex + 1 }} من {{ lessonBlocks.length }}
                      <template v-if="resumeRestored">· استُعيد محليًا على هذا الجهاز</template>
                    </span>
                    <span v-else>{{ lesson.unavailable_reason }}</span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span>حد التقدم:</span>
                    <bdi dir="ltr" class="font-mono text-slate-400">{{
                      semantic_boundary.progress || 'غير محدد'
                    }}</bdi>
                  </div>
                </div>
              </header>

              <div class="mt-6 flex-1 space-y-4">
                <section v-if="lessonAvailable" aria-labelledby="published-lesson-heading">
                  <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                      <p class="text-[10px] font-bold tracking-wide text-slate-500">
                        PUBLISHED LESSON REVISION
                      </p>
                      <h2
                        id="published-lesson-heading"
                        class="mt-1 text-sm font-bold text-slate-200"
                      >
                        محتوى الدرس القانوني
                      </h2>
                    </div>
                    <div class="flex items-center gap-2">
                      <Link
                        :href="libraryHref"
                        class="focus-ring rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-800"
                      >
                        فتح الكائن في Library
                      </Link>
                      <Link
                        :href="qualityHref"
                        class="focus-ring rounded-lg border border-cyan-800 bg-cyan-950/30 px-3 py-1.5 text-xs font-semibold text-cyan-200 hover:bg-cyan-900/40"
                      >
                        فحص المصادر
                      </Link>
                    </div>
                  </div>
                  <LessonContentRenderer
                    :blocks="lesson.revision?.blocks ?? []"
                    :contract="content_contract"
                    :active-index="selectedBlockIndex"
                    @select="selectLessonBlock"
                  />
                </section>

                <section
                  v-else
                  class="rounded-2xl border border-dashed border-amber-800/70 bg-amber-950/20 p-6 text-center"
                  role="status"
                >
                  <h2 class="text-base font-bold text-amber-200">لا توجد مراجعة منشورة للتعلّم</h2>
                  <p class="mx-auto mt-2 max-w-xl text-xs leading-6 text-amber-100/75">
                    يمكن أن توجد مسودة أو مراجعة داخل Library، لكنها ليست محتوى تسليم منشورًا. لن
                    يعرض Learn محتوى غير منشور أو ينشئ درسًا بديلًا.
                  </p>
                  <Link
                    :href="libraryHref"
                    class="focus-ring mt-4 inline-flex rounded-lg border border-amber-700 px-3 py-2 text-xs font-bold text-amber-200 hover:bg-amber-900/30"
                  >
                    مراجعة الكائن وسجل نسخه في Library
                  </Link>
                </section>

                <div class="flex items-center gap-3 pt-2">
                  <span class="h-px flex-1 bg-slate-800"></span>
                  <span class="text-[10px] font-bold tracking-wide text-slate-500">
                    TRANSITIONS — NOT CONTENT OWNERSHIP
                  </span>
                  <span class="h-px flex-1 bg-slate-800"></span>
                </div>

                <section class="grid gap-4 lg:grid-cols-2">
                  <article class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4">
                    <div class="flex items-center justify-between gap-3">
                      <div>
                        <p class="text-[10px] font-bold tracking-wide text-slate-500">
                          الأنشطة الفعلية
                        </p>
                        <h3 class="mt-1 text-sm font-bold text-slate-200">الأنشطة المرتبطة</h3>
                      </div>
                      <span class="font-mono text-xs text-slate-400">{{
                        journey.items.length
                      }}</span>
                    </div>

                    <div v-if="journey.items.length" class="mt-3 space-y-2">
                      <button
                        v-for="item in journey.items"
                        :key="item.id"
                        type="button"
                        class="focus-ring w-full rounded-lg border px-3 py-2 text-right transition"
                        :class="
                          item.id === selectedStep?.id
                            ? 'border-cyan-500/40 bg-cyan-500/10'
                            : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'
                        "
                        @click="selectStep(item.id)"
                      >
                        <div class="flex items-center justify-between gap-3">
                          <bdi
                            dir="ltr"
                            class="min-w-0 truncate font-mono text-xs font-bold text-slate-200"
                          >
                            {{ item.practice_id }}
                          </bdi>
                          <bdi dir="ltr" class="shrink-0 font-mono text-[10px] text-slate-500">
                            {{ item.activity_state }}
                          </bdi>
                        </div>
                        <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-slate-500">
                          <span
                            >المحاولات:
                            <strong class="font-mono text-slate-400">{{
                              item.attempt_count
                            }}</strong></span
                          >
                          <span
                            >الناجحة:
                            <strong class="font-mono text-slate-400">{{
                              item.successful_attempt_count
                            }}</strong></span
                          >
                          <span
                            >النتيجة:
                            <bdi dir="ltr" class="font-mono text-slate-400">{{
                              item.latest_outcome ?? '—'
                            }}</bdi></span
                          >
                        </div>
                      </button>
                    </div>
                    <p v-else class="mt-3 text-xs text-slate-500">
                      لا توجد أنشطة مسجلة ضمن البيانات الحالية.
                    </p>
                  </article>

                  <article class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4">
                    <div class="flex items-center justify-between gap-3">
                      <div>
                        <p class="text-[10px] font-bold tracking-wide text-slate-500">التقييم</p>
                        <h3 class="mt-1 text-sm font-bold text-slate-200">حالة التقييم الفعلية</h3>
                      </div>
                      <bdi
                        dir="ltr"
                        class="max-w-full font-mono text-xs font-bold break-all text-amber-300"
                      >
                        {{ journey.assessments?.state || 'لا يوجد تقييم مسجل' }}
                      </bdi>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                      <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2.5">
                        <dt class="text-slate-500">التعريفات</dt>
                        <dd class="mt-1 font-mono text-slate-200">
                          {{ journey.assessments?.definitions?.length ?? 0 }}
                        </dd>
                      </div>
                      <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2.5">
                        <dt class="text-slate-500">النتائج</dt>
                        <dd class="mt-1 font-mono text-slate-200">
                          {{ journey.assessments?.results?.length ?? 0 }}
                        </dd>
                      </div>
                    </dl>
                    <div
                      class="mt-2 rounded-lg border border-amber-900/50 bg-amber-950/20 p-2 text-[11px] text-amber-200"
                    >
                      لا يوجد عقد Assessment مجمّد متوافق أو مالك تنفيذ متاح؛ لذلك لا يوجد زر بدء أو
                      نتيجة مصطنعة.
                      <bdi dir="ltr" class="mt-1 block font-mono break-all text-amber-300">
                        {{ journey.assessments.integration_state }}
                      </bdi>
                    </div>
                  </article>
                </section>

                <section class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4">
                  <div class="flex items-center justify-between gap-3">
                    <div>
                      <p class="text-[10px] font-bold tracking-wide text-slate-500">المختبرات</p>
                      <h3 class="mt-1 text-sm font-bold text-slate-200">مراجع المختبر الفعلية</h3>
                    </div>
                    <span class="font-mono text-xs text-slate-400">{{ journey.labs.length }}</span>
                  </div>

                  <div v-if="journey.labs.length" class="mt-3 grid gap-2 sm:grid-cols-2">
                    <div
                      v-for="lab in journey.labs"
                      :key="lab.id"
                      class="rounded-lg border border-slate-800 bg-slate-950/60 p-3"
                    >
                      <p v-if="lab.title_ar" class="mb-1 text-xs font-bold text-slate-200">
                        {{ lab.title_ar }}
                      </p>
                      <bdi dir="ltr" class="block font-mono text-xs font-bold text-cyan-300">{{
                        lab.id
                      }}</bdi>
                      <div class="mt-1 space-y-1 text-[10px] text-slate-500">
                        <div v-if="lab.preview_state">
                          المعاينة:
                          <bdi dir="ltr" class="font-mono text-slate-400">{{
                            lab.preview_state
                          }}</bdi>
                        </div>
                        <div v-if="lab.canonical_owner">
                          المالك:
                          <bdi dir="ltr" class="font-mono text-slate-400">{{
                            lab.canonical_owner
                          }}</bdi>
                        </div>
                        <div v-if="lab.prepare_run_handoff?.state">
                          التسليم:
                          <bdi dir="ltr" class="font-mono text-slate-400">{{
                            lab.prepare_run_handoff.state
                          }}</bdi>
                        </div>
                        <bdi
                          v-if="lab.prepare_run_handoff?.reason"
                          dir="ltr"
                          class="block font-mono break-all text-amber-300"
                        >
                          {{ lab.prepare_run_handoff.reason }}
                        </bdi>
                      </div>
                    </div>
                  </div>
                  <p v-else class="mt-3 text-xs text-slate-500">
                    لا توجد مختبرات مسجلة ضمن البيانات الحالية.
                  </p>
                </section>
              </div>
            </div>

            <div v-else class="grid min-h-[420px] place-items-center text-center text-slate-500">
              <div>
                <h1 class="text-xl font-bold text-slate-300">
                  لا توجد وحدة معرفة نشطة قابلة للعرض.
                </h1>
                <p class="mt-2 text-xs">اختر وحدة معرفة من المكتبة لعرض حالتها الفعلية.</p>
              </div>
            </div>
          </main>

          <!-- RIGHT: Context (Visual RIGHT) -->
          <aside
            dir="rtl"
            class="order-3 flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur md:col-span-2 xl:col-span-1 dark:bg-[#0b1322]/90"
            aria-label="سياق النشاط والمختبرات"
          >
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
              <h2 class="text-sm font-bold text-slate-100">سياق النشاط المحدد</h2>
              <span class="font-mono text-[10px] text-slate-500">الحالة المسجلة</span>
            </div>

            <div class="mt-3 flex-1 space-y-4 overflow-y-auto pr-0.5 text-xs">
              <section
                v-if="selectedLab"
                class="space-y-2 rounded-xl border border-indigo-900/60 bg-indigo-950/30 p-3.5"
              >
                <div class="flex items-center gap-1.5 font-semibold text-indigo-300">
                  <span>🧪</span>
                  <h4>مرجع المختبر (Lab Reference)</h4>
                </div>
                <bdi dir="ltr" class="block font-mono text-[11px] font-bold text-indigo-200">
                  {{ selectedLab.id }}
                </bdi>
                <p v-if="selectedLab.title_ar" class="text-xs font-bold text-indigo-100">
                  {{ selectedLab.title_ar }}
                </p>
                <p v-if="selectedLab.summary_ar" class="text-[11px] leading-5 text-indigo-200/80">
                  {{ selectedLab.summary_ar }}
                </p>
                <div class="space-y-1 text-[10px] text-indigo-300/80">
                  <div v-if="selectedLab.preview_state">
                    المعاينة:
                    <bdi dir="ltr" class="font-mono">{{ selectedLab.preview_state }}</bdi>
                  </div>
                  <div v-if="selectedLab.canonical_owner">
                    المالك:
                    <bdi dir="ltr" class="font-mono">{{ selectedLab.canonical_owner }}</bdi>
                  </div>
                  <div v-if="selectedLab.prepare_run_handoff?.state">
                    التسليم:
                    <bdi dir="ltr" class="font-mono">{{
                      selectedLab.prepare_run_handoff.state
                    }}</bdi>
                  </div>
                  <bdi
                    v-if="selectedLab.prepare_run_handoff?.reason"
                    dir="ltr"
                    class="block font-mono break-all text-amber-300"
                  >
                    {{ selectedLab.prepare_run_handoff.reason }}
                  </bdi>
                </div>
              </section>

              <section class="space-y-2 rounded-xl border border-slate-800 bg-slate-950/60 p-3.5">
                <div class="flex items-center gap-1.5 font-semibold text-slate-400">
                  <span>🛡️</span>
                  <h4>الوحدة والمراجعة القانونية</h4>
                </div>
                <bdi dir="ltr" class="block font-mono text-[11px] font-bold text-cyan-300">
                  {{ active?.canonical_ref.id ?? 'NO_CANONICAL_UNIT' }}
                </bdi>
                <bdi
                  v-if="lesson.revision"
                  dir="ltr"
                  class="block font-mono text-[10px] text-slate-400"
                >
                  lesson_revision:{{ lesson.revision.id }} · r{{ lesson.revision.revision }}
                </bdi>
                <p class="text-[10px] leading-5 text-slate-500">
                  لا يملك Learn نسخة محتوى مستقلة؛ يعرض المراجعة المنشورة المملوكة لـ Knowledge.
                </p>
              </section>

              <section class="space-y-2 rounded-xl border border-slate-800 bg-slate-950/60 p-3.5">
                <div class="flex items-center gap-1.5 font-semibold text-slate-400">
                  <span>📚</span>
                  <h4>المتطلبات والمصادر</h4>
                </div>
                <bdi dir="ltr" class="block font-mono text-[10px] break-all text-amber-300">
                  {{ context.prerequisites.state }}
                </bdi>
                <p class="text-[10px] leading-5 text-slate-500">
                  لا تُستنتج الإتاحة أو المتطلبات من موضع منهجي أو نسبة محلية بلا عقد مرجعي.
                </p>
                <ul v-if="context.sources.length" class="space-y-1.5 pt-1">
                  <li
                    v-for="source in context.sources"
                    :key="source.id"
                    class="rounded-lg border border-slate-800 bg-slate-900/60 p-2"
                  >
                    <span class="block text-[11px] font-semibold text-slate-300">{{
                      source.title
                    }}</span>
                    <bdi dir="ltr" class="mt-0.5 block font-mono text-[9px] text-slate-500">
                      {{ source.authority_class }} · {{ source.review_status }}
                    </bdi>
                  </li>
                </ul>
                <p v-else class="text-[10px] text-slate-500">
                  لا توجد مصادر مطابقة لاستشهادات المراجعة المنشورة.
                </p>
              </section>

              <section class="space-y-2 rounded-xl border border-slate-800 bg-slate-950/60 p-3.5">
                <div class="flex items-center gap-1.5 font-semibold text-slate-400">
                  <span>🧭</span>
                  <h4>حدود المعنى والملكية</h4>
                </div>
                <dl class="space-y-2 text-[11px]">
                  <div>
                    <dt class="text-slate-500">سياق التقدم</dt>
                    <dd class="mt-0.5 font-mono break-all text-slate-300" dir="ltr">
                      {{ semantic_boundary.progress || 'غير محدد' }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-slate-500">حد الإكمال</dt>
                    <dd class="mt-0.5 font-mono break-all text-slate-300" dir="ltr">
                      {{ semantic_boundary.completion || 'غير محدد' }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-slate-500">ملكية الإتقان</dt>
                    <dd class="mt-0.5 font-mono break-all text-slate-300" dir="ltr">
                      {{ semantic_boundary.mastery || 'غير محدد' }}
                    </dd>
                  </div>
                </dl>
              </section>
            </div>
          </aside>
        </div>
      </div>

      <!-- Bottom Drawer (Closed by default) -->
      <aside
        dir="rtl"
        class="mt-auto border-t border-slate-800/90 bg-slate-950/95 transition-all"
        aria-label="المساحة السفلية"
      >
        <div class="mx-auto flex max-w-[1720px] items-center justify-between px-4 py-2 sm:px-6">
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="focus-ring flex items-center gap-1.5 rounded-lg border border-slate-700/80 bg-slate-900/80 px-3 py-1 text-xs font-semibold text-slate-200 transition hover:bg-slate-800"
              :aria-expanded="shelfOpen"
              aria-controls="learn-bottom-drawer"
              aria-label="طي أو توسيع المساحة السفلية"
              @click="toggleShelf"
            >
              <span>{{ shelfOpen ? '▼ إخفاء المساحة السفلية' : '▲ مساحة السياق المؤقتة' }}</span>
            </button>
            <span class="text-xs text-slate-500">تفاصيل النشاط التقنية — مغلقة افتراضيًا</span>
          </div>
        </div>

        <div
          v-if="shelfOpen"
          id="learn-bottom-drawer"
          class="border-t border-slate-800/80 px-4 py-3 sm:px-6"
        >
          <div
            class="mx-auto grid max-w-[1720px] gap-3 text-xs text-slate-400 sm:grid-cols-2 xl:grid-cols-4"
          >
            <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3">
              <span class="block text-[10px] font-bold text-slate-500">النطاق الدلالي للنشاط</span>
              <bdi dir="ltr" class="mt-1 block font-mono break-all text-cyan-300">
                {{ journey.activity.semantic_scope || '—' }}
              </bdi>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3">
              <span class="block text-[10px] font-bold text-slate-500">آخر نشاط مسجل</span>
              <bdi dir="ltr" class="mt-1 block font-mono text-slate-300">
                {{ journey.activity.latest_activity_at ?? '—' }}
              </bdi>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3">
              <span class="block text-[10px] font-bold text-slate-500">إكمال Practice الفعلي</span>
              <bdi dir="ltr" class="mt-1 block font-mono text-slate-300">
                {{ journey.activity.completed_practice_count }}/{{
                  journey.activity.practice_count
                }}
              </bdi>
              <span class="mt-1 block text-[10px] text-amber-300">Completion != Mastery</span>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3">
              <span class="block text-[10px] font-bold text-slate-500">استمرارية موضع القراءة</span>
              <bdi dir="ltr" class="mt-1 block font-mono break-all text-cyan-300">
                {{ context.resume.semantic_scope }}
              </bdi>
              <span class="mt-1 block text-[10px] text-slate-500">
                محلي على الجهاز · غير محفوظ في الخادم
              </span>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </CepWorkspaceLayout>
</template>

<style>
[data-theme='light'] .kl-learn-route [class*='bg-slate-950'],
[data-theme='light'] .kl-learn-route [class*='bg-slate-900'],
[data-theme='light'] .kl-learn-route [class*='bg-slate-800'],
[data-theme='light'] .kl-learn-route [class*='bg-[#0b1322]'],
[data-theme='light'] .kl-learn-route [class*='bg-[#070c14]'] {
  background-color: var(--cep-bg-panel-strong) !important;
}

[data-theme='light'] .kl-learn-route [class*='text-slate-100'],
[data-theme='light'] .kl-learn-route [class*='text-slate-200'],
[data-theme='light'] .kl-learn-route [class*='text-slate-300'] {
  color: var(--cep-text) !important;
}

[data-theme='light'] .kl-learn-route [class*='text-slate-400'],
[data-theme='light'] .kl-learn-route [class*='text-slate-500'],
[data-theme='light'] .kl-learn-route [class*='text-slate-600'] {
  color: var(--cep-text-muted) !important;
}

[data-theme='light'] .kl-learn-route [class*='border-slate-700'],
[data-theme='light'] .kl-learn-route [class*='border-slate-800'] {
  border-color: var(--cep-border) !important;
}

[data-theme='light'] .kl-learn-route [class*='text-cyan-100'],
[data-theme='light'] .kl-learn-route [class*='text-cyan-200'],
[data-theme='light'] .kl-learn-route [class*='text-cyan-300'],
[data-theme='light'] .kl-learn-route [class*='text-cyan-400'] {
  color: var(--cep-accent) !important;
}

[data-theme='light'] .kl-learn-route [class*='text-amber-300'],
[data-theme='light'] .kl-learn-route [class*='text-amber-400'] {
  color: #b45309 !important;
}
</style>

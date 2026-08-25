<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import KnowledgeTabs from './components/KnowledgeTabs.vue';

type CatalogItem = {
  id: string;
  title_ar: string;
  title_en: string;
  latest_revision: number | null;
  latest_state: string | null;
};

type ActiveUnit = {
  id: string;
  title_ar: string;
  title_en: string;
  revision: { id: string; revision: number; state: string } | null;
};

type LabReference = {
  id: string;
  preview_state?: string;
  canonical_owner?: string;
  prepare_run_handoff?: {
    target_workspace?: string;
    target_area?: string;
    state?: string;
    href?: string | null;
  };
};

type PracticeDefinition = {
  lab_reference?: LabReference;
  [key: string]: unknown;
};

type AssessmentState = {
  state: string;
  semantic_owner?: string;
  fake_fallback_allowed?: boolean;
  definitions?: unknown[];
  results?: unknown[];
};

type LabItem = {
  id: string;
  preview_state?: string;
  canonical_owner?: string;
  prepare_run_handoff?: {
    target_workspace?: string;
    target_area?: string;
    state?: string;
    href?: string | null;
  };
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
  definition: PracticeDefinition;
};

const props = defineProps<{
  catalog: CatalogItem[];
  active: ActiveUnit | null;
  journey: {
    items: JourneyItem[];
    labs: LabItem[];
    assessments: AssessmentState;
    activity: {
      attempt_count: number;
      completed_practice_count: number;
      latest_activity_at: string | null;
      semantic_scope: string;
    };
  };
  semantic_boundary: {
    progress: string;
    mastery: string;
  };
}>();

const selectedStepId = ref<string | null>(props.journey?.items?.[0]?.id ?? null);
const selectStep = (id: string) => {
  selectedStepId.value = id;
};

const selectedStep = computed<JourneyItem | null>(() => {
  if (!props.journey?.items?.length) return null;

  return (
    props.journey.items.find((item) => item.id === selectedStepId.value) ??
    props.journey.items[0] ??
    null
  );
});

const shelfOpen = ref(false);
const toggleShelf = () => {
  shelfOpen.value = !shelfOpen.value;
};
</script>

<template>
  <Head title="المعرفة والتعلّم — التعلّم والدروس" />
  <div
    dir="rtl"
    class="min-h-screen bg-slate-950 text-slate-100 dark:bg-[#070c14] dark:text-slate-100"
  >
    <div class="w-full px-4 py-4 sm:px-6 xl:px-8">
      <div
        dir="ltr"
        class="grid min-h-[740px] grid-cols-1 gap-4 xl:grid-cols-[280px_minmax(0,1fr)_300px]"
      >
        <!-- LEFT: Learning Journey -->
        <aside
          dir="rtl"
          class="flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur dark:bg-[#0b1322]/90"
          aria-label="رحلة التعلّم"
        >
          <div class="border-b border-slate-800/80 pb-3">
            <div class="flex items-center justify-between">
              <h2 class="text-xs font-bold text-slate-200">مسار التعلّم</h2>
              <span class="font-mono text-[11px] font-bold text-slate-400">النسبة غير متوفرة</span>
            </div>
            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-800">
              <div class="h-full rounded-full bg-slate-700" style="width: 0%"></div>
            </div>
            <p class="mt-2.5 truncate text-xs font-semibold text-slate-300">
              {{ selectedStep?.capability_id ?? 'لا توجد Capability محددة' }}
            </p>
          </div>

          <div class="mt-3 flex-1 space-y-2 overflow-y-auto pr-0.5 text-xs">
            <div
              class="rounded-xl border border-dashed border-slate-800 bg-slate-950/40 p-4 text-center text-slate-500"
            >
              <span class="mb-2 block text-xl">⏳</span>
              <p>عقد الصفحة لا يوفّر نسبة تقدم أو حالة إكمال لدرس.</p>
            </div>

            <div
              v-if="journey.items.length"
              class="mt-4 space-y-1.5 border-t border-slate-800 pt-3"
            >
              <p class="font-mono text-[10px] font-bold text-slate-500 uppercase">
                الأنشطة المسجلة
              </p>
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
                      <bdi dir="ltr" class="truncate font-mono font-semibold">{{
                        item.practice_id
                      }}</bdi>
                    </div>
                    <bdi
                      dir="ltr"
                      class="mt-0.5 block truncate font-mono text-[10px] text-slate-500"
                    >
                      {{ item.activity_state }}
                    </bdi>
                  </div>
                  <span
                    v-if="item.successful_attempt_count > 0"
                    class="shrink-0 font-mono text-[10px] text-emerald-400"
                  >
                    {{ item.successful_attempt_count }} ✓
                  </span>
                </div>
              </button>
            </div>

            <div
              v-else
              class="mt-4 rounded-xl border border-dashed border-slate-800 bg-slate-950/30 p-3 text-center text-slate-500"
            >
              لا توجد عناصر Journey مسجلة لهذه الوحدة.
            </div>
          </div>

          <div class="mt-3 border-t border-slate-800/80 pt-3">
            <Link
              href="/knowledge/visualize"
              class="focus-ring flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700/80 bg-slate-800/60 px-3 py-2 text-xs font-bold text-slate-200 shadow-sm transition hover:bg-slate-800 hover:text-white"
            >
              <span>🗺️</span>
              <span>عرض خريطة المسار</span>
            </Link>
          </div>
        </aside>

        <!-- CENTER: Lesson Surface -->
        <main
          dir="rtl"
          class="flex min-w-0 flex-1 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-5 shadow-lg backdrop-blur sm:p-7 dark:bg-[#0b1322]/90"
        >
          <div v-if="active" class="flex min-w-0 flex-1 flex-col">
            <div class="mb-5 border-b border-slate-800/80 pb-4">
              <KnowledgeTabs active="learn" :object-id="active.id" />
            </div>

            <header class="border-b border-slate-800/80 pb-5">
              <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <nav
                  aria-label="سياق وحدة المعرفة"
                  class="flex min-w-0 items-center gap-1.5 text-slate-400"
                >
                  <span class="font-semibold text-slate-300">{{ active.title_ar }}</span>
                  <template v-if="selectedStep">
                    <span class="text-slate-600">&gt;</span>
                    <bdi dir="ltr" class="truncate font-mono text-cyan-400">
                      {{ selectedStep.capability_id }}
                    </bdi>
                  </template>
                </nav>
                <div
                  class="rounded-lg border border-slate-800 bg-slate-950/60 px-2.5 py-1 font-mono text-[10px] text-slate-500"
                >
                  LESSON_UNAVAILABLE
                </div>
              </div>

              <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                  <p class="text-xs font-bold text-cyan-300">سطح الدرس والمحتوى التعليمي</p>
                  <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-100 sm:text-3xl">
                    لا يوجد Lesson متاح لهذه الوحدة
                  </h1>
                  <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-400">
                    الوحدة النشطة هي
                    <span class="font-semibold text-slate-200">{{ active.title_ar }}</span
                    >. العقد الحالي يقدّم Knowledge Unit وحالة Journey/Activity فقط، ولا يقدّم كائن
                    Lesson أو محتوى Lesson حاكمًا.
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
                      Knowledge Unit
                    </span>
                    <span
                      v-if="active.revision"
                      class="rounded-full border border-slate-700/80 bg-slate-800/80 px-3 py-1 font-mono text-[11px] text-slate-300"
                    >
                      rev {{ active.revision.revision }} · {{ active.revision.state }}
                    </span>
                  </div>
                </div>
              </div>

              <div
                class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800/90 bg-slate-950/80 px-4 py-3 shadow-sm"
              >
                <div class="flex items-center gap-2 text-xs text-slate-400">
                  <span class="h-2 w-2 rounded-full bg-slate-600"></span>
                  <span>لا تتوفر حالة حفظ أو Autosave لدرس ضمن البيانات الحالية.</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                  <span>حد التقدم:</span>
                  <bdi dir="ltr" class="font-mono">{{ semantic_boundary.progress }}</bdi>
                </div>
              </div>
            </header>

            <div class="mt-6 flex-1 space-y-4">
              <section
                class="rounded-2xl border border-dashed border-slate-700/80 bg-slate-950/50 p-6 sm:p-8"
              >
                <div class="mx-auto max-w-2xl text-center">
                  <span class="text-4xl">📖</span>
                  <h2 class="mt-4 text-lg font-bold text-slate-200">No Lesson State</h2>
                  <p class="mt-2 text-sm leading-relaxed text-slate-400">
                    لا يمكن إسناد درس أو تصنيف أو خطوات محتوى إلى الوحدة النشطة من دون Lesson صريح
                    في العقد. لذلك تبقى مساحة المحتوى غير متاحة بدل عرض محتوى ثابت على أنه درس
                    المستخدم الحالي.
                  </p>
                </div>
              </section>

              <section class="grid gap-4 lg:grid-cols-2">
                <article class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4">
                  <div class="flex items-center justify-between gap-3">
                    <div>
                      <p class="text-[10px] font-bold tracking-wide text-slate-500 uppercase">
                        Journey
                      </p>
                      <h3 class="mt-1 text-sm font-bold text-slate-200">
                        الأنشطة الفعلية المرتبطة
                      </h3>
                    </div>
                    <span class="font-mono text-xs text-slate-400">{{ journey.items.length }}</span>
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
                      <div
                        class="mt-1 flex flex-wrap gap-x-3 gap-y-1 font-mono text-[10px] text-slate-500"
                      >
                        <span>attempts: {{ item.attempt_count }}</span>
                        <span>successful: {{ item.successful_attempt_count }}</span>
                        <span>outcome: {{ item.latest_outcome ?? 'N/A' }}</span>
                      </div>
                    </button>
                  </div>
                  <p v-else class="mt-3 text-xs text-slate-500">
                    لا توجد Journey items ضمن البيانات الحالية.
                  </p>
                </article>

                <article class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4">
                  <div class="flex items-center justify-between gap-3">
                    <div>
                      <p class="text-[10px] font-bold tracking-wide text-slate-500 uppercase">
                        Assessment
                      </p>
                      <h3 class="mt-1 text-sm font-bold text-slate-200">حالة التقييم الفعلية</h3>
                    </div>
                    <bdi dir="ltr" class="font-mono text-xs font-bold text-amber-300">
                      {{ journey.assessments?.state || 'NO_ASSESSMENT_STATE' }}
                    </bdi>
                  </div>
                  <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2.5">
                      <dt class="text-slate-500">Definitions</dt>
                      <dd class="mt-1 font-mono text-slate-200">
                        {{ journey.assessments?.definitions?.length ?? 0 }}
                      </dd>
                    </div>
                    <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-2.5">
                      <dt class="text-slate-500">Results</dt>
                      <dd class="mt-1 font-mono text-slate-200">
                        {{ journey.assessments?.results?.length ?? 0 }}
                      </dd>
                    </div>
                  </dl>
                  <div
                    v-if="journey.assessments?.semantic_owner"
                    class="mt-2 text-[11px] text-slate-500"
                  >
                    Semantic owner:
                    <bdi dir="ltr" class="font-mono text-slate-400">{{
                      journey.assessments.semantic_owner
                    }}</bdi>
                  </div>
                </article>
              </section>

              <section class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <p class="text-[10px] font-bold tracking-wide text-slate-500 uppercase">Labs</p>
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
                    <bdi dir="ltr" class="block font-mono text-xs font-bold text-cyan-300">{{
                      lab.id
                    }}</bdi>
                    <div class="mt-1 space-y-1 font-mono text-[10px] text-slate-500">
                      <div v-if="lab.preview_state">preview: {{ lab.preview_state }}</div>
                      <div v-if="lab.canonical_owner">owner: {{ lab.canonical_owner }}</div>
                      <div v-if="lab.prepare_run_handoff?.state">
                        handoff: {{ lab.prepare_run_handoff.state }}
                      </div>
                    </div>
                  </div>
                </div>
                <p v-else class="mt-3 text-xs text-slate-500">
                  لا توجد Lab items ضمن البيانات الحالية.
                </p>
              </section>

              <section
                class="rounded-xl border border-slate-800 bg-slate-950/50 p-4 text-xs leading-relaxed text-slate-400"
              >
                <span class="mb-1 block font-bold text-slate-300">حدود المعنى</span>
                <div class="grid gap-2 sm:grid-cols-2">
                  <div>
                    Progress:
                    <bdi dir="ltr" class="font-mono text-slate-300">{{
                      semantic_boundary.progress
                    }}</bdi>
                  </div>
                  <div>
                    Mastery:
                    <bdi dir="ltr" class="font-mono text-slate-300">{{
                      semantic_boundary.mastery
                    }}</bdi>
                  </div>
                </div>
              </section>
            </div>
          </div>

          <div v-else class="grid min-h-[420px] place-items-center text-center text-slate-500">
            <div>
              <h1 class="text-xl font-bold text-slate-300">لا توجد وحدة معرفة نشطة قابلة للعرض.</h1>
              <p class="mt-2 text-xs">اختر وحدة معرفة من المكتبة لعرض حالتها الفعلية.</p>
            </div>
          </div>
        </main>

        <!-- RIGHT: Context -->
        <aside
          dir="rtl"
          class="flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur dark:bg-[#0b1322]/90"
          aria-label="سياق الخطوة"
        >
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <h2 class="text-sm font-bold text-slate-100">السياق</h2>
            <span class="font-mono text-[10px] text-slate-500">ACTUAL STATE</span>
          </div>

          <div class="mt-3 flex-1 space-y-4 overflow-y-auto pr-0.5 text-xs">
            <section
              class="rounded-xl border border-dashed border-slate-800 bg-slate-950/40 p-3.5 text-slate-500"
            >
              <div class="flex items-center gap-1.5 font-bold">
                <span>📖</span>
                <h3>حالة Lesson</h3>
              </div>
              <p class="mt-1.5 text-[11px] leading-relaxed">
                غير متاح في عقد الصفحة الحالي. لا يتم اشتقاق Lesson من Knowledge Unit أو Journey.
              </p>
            </section>

            <section
              v-if="active"
              class="space-y-1 rounded-xl border border-slate-800 bg-slate-950/60 p-3.5"
            >
              <div class="flex items-center gap-1.5 font-semibold text-slate-400">
                <span>🛡️</span>
                <h4>الوحدة المعرفية الأساسية</h4>
              </div>
              <p class="font-bold text-slate-200">{{ active.title_ar }}</p>
              <p dir="ltr" class="text-left text-[11px] text-slate-500">{{ active.title_en }}</p>
              <bdi dir="ltr" class="block font-mono text-[10px] text-cyan-300">{{ active.id }}</bdi>
              <div
                v-if="active.revision"
                class="mt-2 border-t border-slate-800/80 pt-2 font-mono text-[10px] text-slate-500"
              >
                rev {{ active.revision.revision }} · {{ active.revision.state }}
              </div>
            </section>

            <section
              v-if="selectedStep"
              class="space-y-2.5 rounded-xl border border-slate-800 bg-slate-950/60 p-3.5"
            >
              <div>
                <h4 class="font-bold text-slate-400">سياق النشاط المحدد</h4>
                <bdi dir="ltr" class="mt-1 block font-mono text-xs font-bold text-cyan-300">
                  {{ selectedStep.practice_id }}
                </bdi>
              </div>
              <div class="space-y-1.5 border-t border-slate-800/80 pt-2 text-[11px]">
                <div class="flex justify-between gap-3">
                  <span class="text-slate-500">Capability:</span>
                  <bdi dir="ltr" class="text-right font-mono text-slate-300">{{
                    selectedStep.capability_id
                  }}</bdi>
                </div>
                <div class="flex justify-between gap-3">
                  <span class="text-slate-500">Revision:</span>
                  <span class="font-mono text-slate-300">{{ selectedStep.revision }}</span>
                </div>
                <div class="flex justify-between gap-3">
                  <span class="text-slate-500">Activity state:</span>
                  <bdi dir="ltr" class="font-mono text-slate-300">{{
                    selectedStep.activity_state
                  }}</bdi>
                </div>
                <div class="flex justify-between gap-3">
                  <span class="text-slate-500">إجمالي المحاولات:</span>
                  <span class="font-mono text-slate-300">{{ selectedStep.attempt_count }}</span>
                </div>
                <div class="flex justify-between gap-3">
                  <span class="text-slate-500">المحاولات الناجحة:</span>
                  <span class="font-mono text-slate-300">{{
                    selectedStep.successful_attempt_count
                  }}</span>
                </div>
                <div class="flex justify-between gap-3">
                  <span class="text-slate-500">آخر نتيجة:</span>
                  <bdi dir="ltr" class="font-mono text-slate-300">{{
                    selectedStep.latest_outcome ?? 'N/A'
                  }}</bdi>
                </div>
                <div class="flex justify-between gap-3">
                  <span class="text-slate-500">آخر نشاط:</span>
                  <bdi dir="ltr" class="text-right font-mono text-slate-300">
                    {{ selectedStep.latest_activity_at ?? 'N/A' }}
                  </bdi>
                </div>
              </div>
            </section>

            <section
              v-if="selectedStep?.definition?.lab_reference"
              class="space-y-2 rounded-xl border border-indigo-900/60 bg-indigo-950/30 p-3.5"
            >
              <div class="flex items-center gap-1.5 font-semibold text-indigo-300">
                <span>🧪</span>
                <h4>Lab Reference</h4>
              </div>
              <bdi dir="ltr" class="block font-mono text-[11px] font-bold text-indigo-200">
                {{ selectedStep.definition.lab_reference.id }}
              </bdi>
              <div class="space-y-1 font-mono text-[10px] text-indigo-300/80">
                <div v-if="selectedStep.definition.lab_reference.preview_state">
                  preview: {{ selectedStep.definition.lab_reference.preview_state }}
                </div>
                <div v-if="selectedStep.definition.lab_reference.canonical_owner">
                  owner: {{ selectedStep.definition.lab_reference.canonical_owner }}
                </div>
                <div v-if="selectedStep.definition.lab_reference.prepare_run_handoff?.state">
                  handoff: {{ selectedStep.definition.lab_reference.prepare_run_handoff.state }}
                </div>
              </div>
            </section>

            <section class="space-y-2 rounded-xl border border-slate-800 bg-slate-950/60 p-3.5">
              <div class="flex items-center gap-1.5 font-semibold text-slate-400">
                <span>📊</span>
                <h4>نشاط Journey الفعلي</h4>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div class="rounded-lg border border-slate-800 bg-slate-950/70 p-2">
                  <div class="text-[10px] text-slate-500">Attempts</div>
                  <div class="mt-1 font-mono text-slate-200">
                    {{ journey.activity.attempt_count }}
                  </div>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-950/70 p-2">
                  <div class="text-[10px] text-slate-500">Completed practices</div>
                  <div class="mt-1 font-mono text-slate-200">
                    {{ journey.activity.completed_practice_count }}
                  </div>
                </div>
              </div>
              <div class="space-y-1 border-t border-slate-800/80 pt-2 text-[10px] text-slate-500">
                <div>
                  scope:
                  <bdi dir="ltr" class="font-mono text-slate-400">{{
                    journey.activity.semantic_scope
                  }}</bdi>
                </div>
                <div>
                  latest:
                  <bdi dir="ltr" class="font-mono text-slate-400">
                    {{ journey.activity.latest_activity_at ?? 'N/A' }}
                  </bdi>
                </div>
              </div>
            </section>

            <section class="space-y-2 rounded-xl border border-slate-800 bg-slate-950/60 p-3.5">
              <div class="flex items-center gap-1.5 font-semibold text-slate-400">
                <span>📝</span>
                <h4>Assessment State</h4>
              </div>
              <bdi dir="ltr" class="block font-mono text-[11px] font-bold text-amber-300">
                {{ journey.assessments?.state || 'NO_ASSESSMENT_STATE' }}
              </bdi>
            </section>
          </div>
        </aside>
      </div>
    </div>

    <!-- Bottom Drawer -->
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
            @click="toggleShelf"
          >
            <span>{{ shelfOpen ? '▼ إخفاء المساحة السفلية' : '▲ السياق' }}</span>
          </button>
          <div class="flex items-center gap-1.5 text-xs">
            <span class="rounded-lg bg-cyan-500/20 px-2.5 py-1 font-bold text-cyan-300"
              >نظرة عامة</span
            >
            <span class="rounded-lg px-2.5 py-1 text-slate-400">
              Journey
              <span class="ms-1 font-mono text-[10px] text-cyan-400">{{
                journey.items.length
              }}</span>
            </span>
            <span class="rounded-lg px-2.5 py-1 text-slate-400">
              Labs
              <span class="ms-1 font-mono text-[10px] text-cyan-400">{{
                journey.labs.length
              }}</span>
            </span>
            <span class="rounded-lg px-2.5 py-1 text-slate-400">
              Assessment
              <bdi dir="ltr" class="ms-1 font-mono text-[10px] text-amber-400">
                {{ journey.assessments?.state || 'N/A' }}
              </bdi>
            </span>
          </div>
        </div>
      </div>

      <div v-if="shelfOpen" class="border-t border-slate-800/80 px-4 py-3 sm:px-6">
        <div class="mx-auto grid max-w-[1720px] gap-3 text-xs text-slate-400 sm:grid-cols-3">
          <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3">
            <span class="block text-[10px] font-bold text-slate-500 uppercase">Knowledge Unit</span>
            <bdi v-if="active" dir="ltr" class="mt-1 block font-mono text-cyan-300">{{
              active.id
            }}</bdi>
            <span v-else class="mt-1 block text-slate-500">غير متاحة</span>
          </div>
          <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3">
            <span class="block text-[10px] font-bold text-slate-500 uppercase"
              >Selected activity</span
            >
            <bdi dir="ltr" class="mt-1 block font-mono text-slate-300">
              {{ selectedStep?.practice_id ?? 'N/A' }}
            </bdi>
          </div>
          <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3">
            <span class="block text-[10px] font-bold text-slate-500 uppercase">Lesson</span>
            <span class="mt-1 block text-slate-300">غير متاح ضمن العقد الحالي</span>
          </div>
        </div>
      </div>
    </aside>
  </div>
</template>

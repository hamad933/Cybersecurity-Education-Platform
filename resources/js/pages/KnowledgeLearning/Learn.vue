<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
</script>

<template>
  <Head title="المعرفة والتعلّم — التعلّم" />
  <div dir="rtl" class="flex min-h-screen flex-col bg-slate-950 text-slate-100 antialiased">
    <div class="mx-auto w-full max-w-[1720px] flex-1 px-4 py-4 sm:px-6">
      <div
        dir="ltr"
        class="grid min-h-[740px] grid-cols-1 gap-4 xl:grid-cols-[280px_minmax(0,1fr)_300px]"
      >
        <!-- LEFT: Learning Journey -->
        <aside
          dir="rtl"
          class="flex min-w-0 flex-col rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="رحلة التعلّم"
        >
          <div class="mb-4">
            <h2 class="text-xs font-bold text-slate-400">رحلة التعلّم</h2>
          </div>
          <div v-if="journey.items.length" class="flex-1 space-y-2 overflow-y-auto">
            <button
              v-for="(item, index) in journey.items"
              :key="item.id"
              type="button"
              class="focus-ring block w-full rounded-lg px-3 py-2 text-right text-sm transition"
              :class="
                item.id === selectedStep?.id
                  ? 'border-r-2 border-cyan-400 bg-cyan-400/10 text-cyan-100'
                  : 'text-slate-300 hover:bg-slate-800'
              "
              @click="selectStep(item.id)"
            >
              <div class="flex items-center gap-2">
                <span class="text-xs">{{ index + 1 }}.</span>
                <bdi dir="ltr" class="font-mono">{{ item.practice_id }}</bdi>
              </div>
              <div class="mt-1 flex items-center justify-between text-[10px]">
                <span class="text-slate-500">{{ item.activity_state }}</span>
                <span v-if="item.successful_attempt_count > 0" class="text-emerald-400">✓</span>
              </div>
            </button>
          </div>
          <p v-else class="mt-4 text-sm leading-7 text-slate-500">
            لا توجد رحلة تعلم مسجلة لهذه الوحدة.
          </p>
        </aside>

        <!-- CENTER: Lesson / Content Surface -->
        <main
          dir="rtl"
          class="flex min-w-0 flex-col rounded-xl border border-slate-800 bg-slate-900/35 p-5 sm:p-7"
        >
          <div v-if="active" class="flex min-w-0 flex-1 flex-col">
            <div class="mb-5 border-b border-slate-800/80 pb-4">
              <KnowledgeTabs active="learn" :object-id="active?.id" />
            </div>

            <header class="border-b border-slate-800 pb-5">
              <p class="text-xs font-bold text-cyan-300">سطح الدرس والمحتوى التعليمي</p>
              <h1 class="mt-2 text-2xl font-black sm:text-3xl">{{ active.title_ar }}</h1>
              <div class="mt-2 flex flex-wrap gap-2 text-sm text-slate-400">
                <bdi dir="ltr" class="font-mono text-cyan-200">{{ active.id }}</bdi>
              </div>
            </header>

            <section class="mt-8 grid flex-1 place-items-center">
              <div class="text-center">
                <span class="text-4xl">📝</span>
                <h2 class="mt-4 text-lg font-bold text-slate-300">
                  لا يوجد درس تعليمي مخصص (No Lesson State)
                </h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                  في هذه البنية المعمارية، وحدة المعرفة (Knowledge Unit) ليست درسًا تعليميًا (KU !=
                  Lesson).<br />
                  حاليًا لا توجد كائنات "Lesson" مسجلة أو تقييمات معيارية في قاعدة البيانات.
                </p>
              </div>
            </section>

            <section class="mt-8 border-t border-slate-800/80 pt-5">
              <h3 class="text-sm font-bold text-slate-400">التقييم المستقل</h3>
              <div class="mt-3 rounded-lg border border-amber-900/40 bg-amber-950/20 p-4">
                <p class="font-mono text-xs text-amber-200">
                  {{ journey.assessments?.state || 'NO_ASSESSMENT' }}
                </p>
                <p class="mt-1 text-xs text-amber-400">
                  لا توجد تقييمات تمثل إتقاناً (Mastery). إكمال الأنشطة لا يعني الإتقان.
                </p>
              </div>
            </section>
          </div>
          <div v-else class="grid min-h-[420px] place-items-center text-center text-slate-500">
            <div>
              <h1 class="text-xl font-bold text-slate-300">لا توجد رحلة تعلم قابلة للعرض.</h1>
              <p class="mt-2">يرجى اختيار وحدة معرفة من المكتبة أولاً.</p>
            </div>
          </div>
        </main>

        <!-- RIGHT: Context & Lab Readiness -->
        <aside
          dir="rtl"
          class="flex min-w-0 flex-col rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="سياق الخطوة"
        >
          <div v-if="selectedStep" class="flex-1 space-y-6 overflow-y-auto">
            <div>
              <h2 class="text-xs font-bold text-slate-500">سياق الخطوة المحددة</h2>
              <bdi dir="ltr" class="mt-2 block font-mono text-sm font-bold text-cyan-200">
                {{ selectedStep.practice_id }}
              </bdi>
            </div>

            <div class="space-y-3 border-t border-slate-800 pt-4 text-xs">
              <div class="flex justify-between">
                <span class="text-slate-400">Capability:</span>
                <bdi dir="ltr" class="font-mono text-slate-300">{{
                  selectedStep.capability_id
                }}</bdi>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">إجمالي المحاولات:</span>
                <span class="font-mono text-slate-300">{{ selectedStep.attempt_count }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">آخر نتيجة:</span>
                <bdi
                  dir="ltr"
                  class="font-mono"
                  :class="
                    selectedStep.latest_outcome === 'correct'
                      ? 'text-emerald-400'
                      : 'text-amber-400'
                  "
                >
                  {{ selectedStep.latest_outcome || 'N/A' }}
                </bdi>
              </div>
            </div>

            <div class="border-t border-slate-800 pt-4">
              <h3 class="text-xs font-bold text-slate-500">جاهزية المعمل (Lab Readiness)</h3>
              <div
                v-if="selectedStep.definition?.lab_reference"
                class="mt-3 rounded-lg border border-indigo-900/50 bg-indigo-950/30 p-3"
              >
                <div class="flex items-center gap-2">
                  <span>🧪</span>
                  <bdi dir="ltr" class="font-mono text-[11px] text-indigo-300">
                    {{ selectedStep.definition.lab_reference.id }}
                  </bdi>
                </div>
                <p class="mt-2 text-[10px] leading-relaxed text-indigo-400">
                  هذا النشاط يعتمد على معمل معزول. (INTEGRATION_REQUIRED)
                </p>
              </div>
              <p v-else class="mt-3 text-xs text-slate-500">لا يوجد معمل مرتبط بهذه الخطوة.</p>
            </div>

            <div class="border-t border-slate-800 pt-4">
              <h3 class="text-xs font-bold text-slate-500">حدود المعنى (Semantics)</h3>
              <div
                class="mt-3 rounded border border-slate-700 bg-slate-900/60 p-2 text-[10px] leading-5 text-slate-400"
              >
                التقدم هنا يعكس "إكمال النشاط" (Completion) ولا يمثل الإتقان (Mastery). لا توجد نسبة
                إتقان.
              </div>
            </div>
          </div>
          <div v-else class="py-10 text-center text-xs text-slate-500">حدد خطوة لعرض السياق.</div>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CepWorkspaceLayout from '../../layouts/CepWorkspaceLayout.vue';
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

const visualizeHref = computed(() =>
  props.active ? `/knowledge/visualize?object=${encodeURIComponent(props.active.id)}` : '/knowledge/visualize',
);
</script>

<template>
  <Head title="المعرفة والتعلّم — التعلّم والدروس" />
  <CepWorkspaceLayout active-destination="knowledge">
    <template #primaryNavigation>
      <KnowledgeTabs active="learn" :object-id="active?.id" />
    </template>

    <div dir="rtl" class="kl-learn-route min-h-full bg-[var(--cep-bg-canvas)] text-[var(--cep-text)]">
    <div class="w-full px-0 py-3 sm:px-4 xl:px-6">
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
            <h2 class="text-xs font-bold text-slate-200">مسار التعلّم</h2>
            <p class="mt-2.5 truncate text-xs font-semibold text-slate-300">
              {{ selectedStep?.capability_id ?? 'لا توجد قدرة محددة' }}
            </p>
          </div>

          <div class="mt-3 flex-1 space-y-2 overflow-y-auto pr-0.5 text-xs">
            <div
              v-if="journey.items.length"
              class="space-y-1.5"
            >
              <p class="text-[10px] font-bold text-slate-500">الأنشطة المتاحة للتنقّل</p>
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
                  </div>
                </div>
              </button>
            </div>

            <div
              v-else
              class="mt-4 rounded-xl border border-dashed border-slate-800 bg-slate-950/30 p-3 text-center text-slate-500"
            >
              لا توجد عناصر رحلة تعلّم مسجلة لهذه الوحدة.
            </div>
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
                >
                  الدرس غير متوفر
                </div>
              </div>

              <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                  <p class="text-xs font-bold text-cyan-300">سطح الدرس والمحتوى التعليمي</p>
                  <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-100 sm:text-3xl">
                    لا يتوفر درس مسجل لهذه الوحدة المعرفية
                  </h1>
                  <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-400">
                    الوحدة النشطة هي
                    <span class="font-semibold text-slate-200">{{ active.title_ar }}</span
                    >. العقد الحالي يقدّم وحدة المعرفة (Knowledge Unit) وحالة الأنشطة فقط، ولا يقدّم
                    كائن درس مسجل.
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
                      v-if="active.revision"
                      class="rounded-full border border-slate-700/80 bg-slate-800/80 px-3 py-1 font-mono text-[11px] text-slate-300"
                    >
                      مراجعة {{ active.revision.revision }} · {{ active.revision.state }}
                    </span>
                  </div>
                </div>
              </div>

              <div
                class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800/90 bg-slate-950/80 px-4 py-3 shadow-sm"
              >
                <div class="flex items-center gap-2 text-xs text-slate-400">
                  <span class="h-2 w-2 rounded-full bg-slate-600"></span>
                  <span>لا تتوفر حالة حفظ أو مسودة لدرس ضمن البيانات الحالية.</span>
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
              <section class="grid gap-4 lg:grid-cols-2">
                <article class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4">
                  <div class="flex items-center justify-between gap-3">
                    <div>
                      <p class="text-[10px] font-bold tracking-wide text-slate-500">
                        الأنشطة الفعلية
                      </p>
                      <h3 class="mt-1 text-sm font-bold text-slate-200">الأنشطة المرتبطة</h3>
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
                    <bdi dir="ltr" class="max-w-full break-all font-mono text-xs font-bold text-amber-300">
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
                    v-if="journey.assessments?.semantic_owner"
                    class="mt-2 text-[11px] text-slate-500"
                  >
                    الجهة المسؤولة دلاليًا:
                    <bdi dir="ltr" class="font-mono text-slate-400">{{
                      journey.assessments.semantic_owner
                    }}</bdi>
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
              <h1 class="text-xl font-bold text-slate-300">لا توجد وحدة معرفة نشطة قابلة للعرض.</h1>
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
              v-if="selectedStep?.definition?.lab_reference"
              class="space-y-2 rounded-xl border border-indigo-900/60 bg-indigo-950/30 p-3.5"
            >
              <div class="flex items-center gap-1.5 font-semibold text-indigo-300">
                <span>🧪</span>
                <h4>مرجع المختبر (Lab Reference)</h4>
              </div>
              <bdi dir="ltr" class="block font-mono text-[11px] font-bold text-indigo-200">
                {{ selectedStep.definition.lab_reference.id }}
              </bdi>
              <div class="space-y-1 text-[10px] text-indigo-300/80">
                <div v-if="selectedStep.definition.lab_reference.preview_state">
                  المعاينة:
                  <bdi dir="ltr" class="font-mono">{{
                    selectedStep.definition.lab_reference.preview_state
                  }}</bdi>
                </div>
                <div v-if="selectedStep.definition.lab_reference.canonical_owner">
                  المالك:
                  <bdi dir="ltr" class="font-mono">{{
                    selectedStep.definition.lab_reference.canonical_owner
                  }}</bdi>
                </div>
                <div v-if="selectedStep.definition.lab_reference.prepare_run_handoff?.state">
                  التسليم:
                  <bdi dir="ltr" class="font-mono">{{
                    selectedStep.definition.lab_reference.prepare_run_handoff.state
                  }}</bdi>
                </div>
              </div>
            </section>

            <section class="space-y-2 rounded-xl border border-slate-800 bg-slate-950/60 p-3.5">
              <div class="flex items-center gap-1.5 font-semibold text-slate-400">
                <span>🧭</span>
                <h4>حدود المعنى والملكية</h4>
              </div>
              <dl class="space-y-2 text-[11px]">
                <div>
                  <dt class="text-slate-500">سياق التقدم</dt>
                  <dd class="mt-0.5 break-all font-mono text-slate-300" dir="ltr">
                    {{ semantic_boundary.progress || 'غير محدد' }}
                  </dd>
                </div>
                <div>
                  <dt class="text-slate-500">ملكية الإتقان</dt>
                  <dd class="mt-0.5 break-all font-mono text-slate-300" dir="ltr">
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
        <div class="mx-auto grid max-w-[1720px] gap-3 text-xs text-slate-400 sm:grid-cols-2">
          <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3">
            <span class="block text-[10px] font-bold text-slate-500">النطاق الدلالي للنشاط</span>
            <bdi dir="ltr" class="mt-1 block break-all font-mono text-cyan-300">
              {{ journey.activity.semantic_scope || '—' }}
            </bdi>
          </div>
          <div class="rounded-lg border border-slate-800 bg-slate-900/50 p-3">
            <span class="block text-[10px] font-bold text-slate-500">آخر نشاط مسجل</span>
            <bdi dir="ltr" class="mt-1 block font-mono text-slate-300">
              {{ journey.activity.latest_activity_at ?? '—' }}
            </bdi>
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

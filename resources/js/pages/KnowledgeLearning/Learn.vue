<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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

type PracticeDefinition = {
  kind: string | null;
  title_ar: string | null;
  title_en: string | null;
  prompt_ar: string | null;
  prompt_en: string | null;
  difficulty: string | null;
  estimated_minutes: number | null;
  mode: string | null;
  tags: string[];
};

type RecentAttempt = {
  id: string;
  case_id: string;
  outcome: string;
  rationale_valid: boolean;
  failure_class: string | null;
  created_at: string | null;
};

type LabPreview = {
  id: string;
  title_ar: string | null;
  title_en: string | null;
  summary_ar: string | null;
  preview_state: string;
  canonical_owner: string;
  prepare_run_handoff: {
    target_workspace: string;
    target_area: string;
    state: string;
    href: string | null;
  };
};

type JourneyItem = {
  id: string;
  practice_id: string;
  revision: number;
  capability_id: string;
  definition: PracticeDefinition;
  attempt_count: number;
  successful_attempt_count: number;
  latest_outcome: string | null;
  latest_activity_at: string | null;
  activity_state: string;
  activity_completed: boolean;
  completion_semantics: string;
  recent_attempts: RecentAttempt[];
  lab_preview: LabPreview | null;
};

type Journey = {
  items: JourneyItem[];
  activity: {
    attempt_count: number;
    practice_count: number;
    started_practice_count: number;
    completed_practice_count: number;
    latest_activity_at: string | null;
    semantic_scope: string;
    completion_is_mastery: boolean;
  };
  next: {
    state: string;
    practice_id: string | null;
    reason: string;
  };
  today_projection: {
    knowledge_unit_id: string | null;
    recommended_practice_id: string | null;
    state: string;
    reason: string;
    source: string;
    projection_ready: boolean;
    mastery_included: boolean;
  };
  assessments: {
    definitions: unknown[];
    results: unknown[];
    state: string;
    semantic_owner: string;
    fake_fallback_allowed: boolean;
  };
  labs: LabPreview[];
  evidence_context: {
    state: string;
    formal_review: string;
    mastery_judgment: string;
  };
};

defineProps<{
  catalog: CatalogItem[];
  active: ActiveUnit | null;
  journey: Journey;
  semantic_boundary: {
    progress: string;
    mastery: string;
  };
}>();

const activityLabel = (state: string): string => {
  if (state === 'ACTIVITY_COMPLETED') return 'اكتمل نشاط الممارسة';
  if (state === 'IN_PROGRESS') return 'قيد المتابعة';

  return 'لم يبدأ';
};

const nextLabel = (state: string): string => {
  if (state === 'START_PRACTICE') return 'ابدأ الممارسة التالية';
  if (state === 'CONTINUE_PRACTICE') return 'تابع الممارسة الحالية';
  if (state === 'REVIEW_ACTIVITY') return 'راجع نشاطك السابق';

  return 'لا يوجد نشاط تالٍ محفوظ';
};

const outcomeLabel = (outcome: string | null): string => {
  if (outcome === 'correct') return 'صحيحة';
  if (outcome === 'incorrect') return 'غير صحيحة';

  return outcome ?? 'لا توجد نتيجة';
};
</script>

<template>
  <Head title="المعرفة والتعلّم — التعلّم" />
  <div dir="rtl" class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto max-w-[1600px] px-4 py-5 sm:px-6">
      <header class="border-b border-slate-800 pb-4">
        <KnowledgeTabs active="learn" :object-id="active?.id" />
      </header>

      <div class="mt-4 grid min-h-[700px] gap-4 xl:grid-cols-[270px_minmax(0,1fr)_300px]">
        <aside
          class="rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="بنية كائنات التعلّم القانونية"
        >
          <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-600">
              Canonical objects
            </p>
            <h2 class="mt-2 text-sm font-black text-slate-300">وحدات المعرفة</h2>
          </div>

          <ul v-if="catalog.length" class="mt-4 space-y-1.5">
            <li v-for="unit in catalog" :key="unit.id">
              <Link
                :href="`/knowledge/learn?object=${encodeURIComponent(unit.id)}`"
                class="focus-ring block rounded-lg border px-3 py-2.5 text-sm transition"
                :class="
                  unit.id === active?.id
                    ? 'border-cyan-800 bg-cyan-400/10 text-cyan-100'
                    : 'border-transparent text-slate-300 hover:border-slate-800 hover:bg-slate-900'
                "
              >
                <span class="block font-bold">{{ unit.title_ar }}</span>
                <span class="mt-1 flex items-center gap-2 text-[11px] text-slate-500">
                  <bdi dir="ltr">rev {{ unit.latest_revision ?? '—' }}</bdi>
                  <span v-if="unit.latest_state">{{ unit.latest_state }}</span>
                </span>
              </Link>
            </li>
          </ul>

          <p v-else class="mt-4 text-sm leading-7 text-slate-500">
            لا توجد وحدات معرفة محفوظة يمكن إسقاط رحلة تعلم عليها.
          </p>
        </aside>

        <main class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/35 p-5 sm:p-7">
          <div v-if="active">
            <header class="border-b border-slate-800 pb-5">
              <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p class="text-xs font-bold text-cyan-300">
                    رحلة المتعلم فوق نفس الكائن القانوني في Library
                  </p>
                  <h1 class="mt-2 text-2xl font-black sm:text-3xl">{{ active.title_ar }}</h1>
                  <div class="mt-2 flex flex-wrap gap-2 text-sm text-slate-400">
                    <bdi dir="ltr" class="font-mono text-cyan-200">{{ active.id }}</bdi>
                    <span aria-hidden="true">·</span>
                    <bdi dir="ltr">{{ active.title_en }}</bdi>
                    <template v-if="active.revision">
                      <span aria-hidden="true">·</span>
                      <bdi dir="ltr">revision {{ active.revision.revision }}</bdi>
                      <span>{{ active.revision.state }}</span>
                    </template>
                  </div>
                </div>

                <Link
                  :href="`/knowledge?object=${encodeURIComponent(active.id)}`"
                  class="focus-ring rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs font-bold text-slate-200 hover:border-cyan-700"
                >
                  فتح الكائن نفسه في Library
                </Link>
              </div>
            </header>

            <section
              class="mt-6 rounded-xl border border-cyan-900/70 bg-cyan-950/20 p-4 sm:p-5"
              aria-labelledby="next-learning-heading"
            >
              <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-cyan-400">
                    Next learning context
                  </p>
                  <h2 id="next-learning-heading" class="mt-2 text-lg font-black text-cyan-50">
                    {{ nextLabel(journey.next.state) }}
                  </h2>
                  <p class="mt-2 text-sm leading-7 text-cyan-100/70">
                    التوصية مشتقة من تعريفات Practice ومحاولاتك المحفوظة فقط، ولا تحتوي حكم Mastery.
                  </p>
                </div>
                <bdi
                  v-if="journey.next.practice_id"
                  dir="ltr"
                  class="rounded-lg bg-slate-950/70 px-3 py-2 font-mono text-sm font-bold text-cyan-200"
                >
                  {{ journey.next.practice_id }}
                </bdi>
              </div>
            </section>

            <section class="mt-8" aria-labelledby="practice-heading">
              <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                  <p class="text-xs font-bold text-slate-500">Practice</p>
                  <h2 id="practice-heading" class="mt-1 text-lg font-black">التعريفات والمحاولات</h2>
                </div>
                <p class="text-xs text-slate-500">
                  أحدث revision قانونية لكل Practice فقط.
                </p>
              </div>

              <div v-if="journey.items.length" class="mt-4 space-y-4">
                <article
                  v-for="item in journey.items"
                  :key="item.id"
                  class="rounded-xl border border-slate-800 bg-slate-950/55 p-4 sm:p-5"
                >
                  <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                      <div class="flex flex-wrap items-center gap-2">
                        <span
                          class="rounded-full border px-2.5 py-1 text-[11px] font-bold"
                          :class="
                            item.activity_state === 'ACTIVITY_COMPLETED'
                              ? 'border-emerald-900 bg-emerald-950/40 text-emerald-300'
                              : item.activity_state === 'IN_PROGRESS'
                                ? 'border-amber-900 bg-amber-950/40 text-amber-200'
                                : 'border-slate-700 bg-slate-900 text-slate-400'
                          "
                        >
                          {{ activityLabel(item.activity_state) }}
                        </span>
                        <bdi dir="ltr" class="font-mono text-xs text-cyan-300">
                          {{ item.practice_id }} · r{{ item.revision }}
                        </bdi>
                      </div>

                      <h3 class="mt-3 text-base font-black text-slate-100">
                        {{ item.definition.title_ar ?? item.practice_id }}
                      </h3>
                      <bdi
                        v-if="item.definition.title_en"
                        dir="ltr"
                        class="mt-1 block text-xs text-slate-500"
                      >
                        {{ item.definition.title_en }}
                      </bdi>
                    </div>

                    <div class="text-left text-xs leading-6 text-slate-400">
                      <p>
                        المحاولات:
                        <bdi dir="ltr" class="font-mono text-slate-200">{{ item.attempt_count }}</bdi>
                      </p>
                      <p>
                        الصحيحة:
                        <bdi dir="ltr" class="font-mono text-slate-200">
                          {{ item.successful_attempt_count }}
                        </bdi>
                      </p>
                    </div>
                  </div>

                  <p
                    v-if="item.definition.prompt_ar"
                    class="mt-4 max-w-4xl text-sm leading-7 text-slate-300"
                  >
                    {{ item.definition.prompt_ar }}
                  </p>

                  <div class="mt-4 flex flex-wrap gap-2 text-[11px] text-slate-400">
                    <span
                      v-if="item.definition.kind"
                      class="rounded border border-slate-800 px-2 py-1"
                    >
                      <bdi dir="ltr">{{ item.definition.kind }}</bdi>
                    </span>
                    <span
                      v-if="item.definition.difficulty"
                      class="rounded border border-slate-800 px-2 py-1"
                    >
                      الصعوبة: <bdi dir="ltr">{{ item.definition.difficulty }}</bdi>
                    </span>
                    <span
                      v-if="item.definition.estimated_minutes !== null"
                      class="rounded border border-slate-800 px-2 py-1"
                    >
                      زمن تقديري:
                      <bdi dir="ltr">{{ item.definition.estimated_minutes }}</bdi> دقيقة
                    </span>
                    <span
                      v-for="tag in item.definition.tags"
                      :key="tag"
                      class="rounded border border-slate-800 px-2 py-1"
                    >
                      <bdi dir="ltr">{{ tag }}</bdi>
                    </span>
                  </div>

                  <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                    <div class="rounded-lg border border-slate-800 bg-slate-900/45 p-3">
                      <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                        <span class="font-bold text-slate-300">آخر نتيجة محفوظة</span>
                        <span
                          :class="
                            item.latest_outcome === 'correct' ? 'text-emerald-300' : 'text-amber-200'
                          "
                        >
                          {{ outcomeLabel(item.latest_outcome) }}
                        </span>
                      </div>
                      <p v-if="item.latest_activity_at" class="mt-2 text-xs text-slate-500">
                        آخر نشاط: <bdi dir="ltr">{{ item.latest_activity_at }}</bdi>
                      </p>
                      <p v-else class="mt-2 text-xs text-slate-500">لا توجد محاولة محفوظة بعد.</p>

                      <details v-if="item.recent_attempts.length" class="mt-3 border-t border-slate-800 pt-3">
                        <summary class="cursor-pointer text-xs font-bold text-cyan-300">
                          سجل المحاولات الأخير
                        </summary>
                        <ol class="mt-3 space-y-2">
                          <li
                            v-for="attempt in item.recent_attempts"
                            :key="attempt.id"
                            class="flex flex-wrap items-center justify-between gap-2 rounded bg-slate-950/60 px-3 py-2 text-xs"
                          >
                            <span>
                              <bdi dir="ltr" class="font-mono text-slate-400">{{ attempt.case_id }}</bdi>
                              <span class="mx-2 text-slate-700">·</span>
                              <span>{{ outcomeLabel(attempt.outcome) }}</span>
                            </span>
                            <bdi v-if="attempt.created_at" dir="ltr" class="text-slate-600">
                              {{ attempt.created_at }}
                            </bdi>
                          </li>
                        </ol>
                      </details>
                    </div>

                    <aside
                      v-if="item.lab_preview"
                      class="rounded-lg border border-violet-900/70 bg-violet-950/20 p-3"
                      aria-label="معاينة Lab سياقية"
                    >
                      <p class="text-[11px] font-bold text-violet-300">Lab preview</p>
                      <h4 class="mt-2 font-bold text-violet-50">
                        {{ item.lab_preview.title_ar ?? item.lab_preview.id }}
                      </h4>
                      <bdi dir="ltr" class="mt-1 block font-mono text-[11px] text-violet-300/80">
                        {{ item.lab_preview.id }}
                      </bdi>
                      <p v-if="item.lab_preview.summary_ar" class="mt-3 text-xs leading-6 text-violet-100/70">
                        {{ item.lab_preview.summary_ar }}
                      </p>
                      <button
                        type="button"
                        disabled
                        class="mt-4 w-full cursor-not-allowed rounded-lg border border-violet-800 px-3 py-2 text-xs font-bold text-violet-300 opacity-70"
                      >
                        إعداد التشغيل — يتطلب دمج W03
                      </button>
                      <p class="mt-2 text-[11px] leading-5 text-violet-300/60">
                        المعاينة تبقى هنا، أما Prepare/Start Run فينتقل إلى Simulation & Enterprise بعد wiring الأب.
                      </p>
                    </aside>
                  </div>

                  <div
                    class="mt-4 border-t border-slate-800 pt-3 text-[11px] leading-6 text-slate-500"
                  >
                    اكتمال هذا النشاط يعني وجود محاولة Practice صحيحة فقط؛ لا ينتج عنه Mastery State.
                  </div>
                </article>
              </div>

              <div
                v-else
                class="mt-5 rounded-xl border border-dashed border-slate-700 p-8 text-center"
              >
                <h2 class="font-bold">لا توجد Practice Definitions مرتبطة بهذه الوحدة.</h2>
                <p class="mt-2 text-sm leading-7 text-slate-500">
                  Learn لا ينشئ نسخة بديلة من محتوى Library ولا نشاطًا افتراضيًا لتعويض غياب البيانات.
                </p>
              </div>
            </section>

            <section class="mt-8 border-t border-slate-800 pt-6" aria-labelledby="assessment-heading">
              <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p class="text-xs font-bold text-slate-500">Assessment</p>
                  <h2 id="assessment-heading" class="mt-1 text-lg font-black">التقييمات والنتائج</h2>
                </div>
                <bdi dir="ltr" class="rounded bg-slate-950 px-2 py-1 text-[11px] text-slate-500">
                  {{ journey.assessments.state }}
                </bdi>
              </div>
              <div class="mt-4 rounded-xl border border-slate-800 bg-slate-950/45 p-4">
                <p class="text-sm leading-7 text-slate-300">
                  البنية الحالية لا تحتوي مخزنًا قانونيًا مستقلًا لتعريفات Assessment ونتائجها يمكن لـ Learn قراءته دون اختراع نموذج جديد. لذلك لا تُعرض تقييمات وهمية أو نتائج مشتقة من Practice على أنها Assessment.
                </p>
              </div>
            </section>
          </div>

          <div v-else class="grid min-h-[420px] place-items-center text-center text-slate-500">
            <div>
              <h1 class="text-xl font-bold text-slate-300">لا توجد رحلة تعلم قابلة للعرض.</h1>
              <p class="mt-2">لا توجد Knowledge Units محفوظة.</p>
            </div>
          </div>
        </main>

        <aside
          class="rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="سياق الرحلة الفريد"
        >
          <section>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-600">Today handoff</p>
            <h2 class="mt-2 text-sm font-black text-slate-300">السياق التالي</h2>
            <p class="mt-3 text-sm leading-7 text-slate-400">
              هذه البيانات جاهزة للإسقاط في Today بوصفها توصية نشاط، لا حكم تقدم قانوني أو Mastery.
            </p>
            <div class="mt-3 rounded-lg border border-slate-800 bg-slate-950/55 p-3 text-xs">
              <p class="text-slate-500">الحالة</p>
              <bdi dir="ltr" class="mt-1 block font-mono text-cyan-200">
                {{ journey.today_projection.state }}
              </bdi>
              <template v-if="journey.today_projection.recommended_practice_id">
                <p class="mt-3 text-slate-500">Practice المقترحة</p>
                <bdi dir="ltr" class="mt-1 block font-mono text-slate-200">
                  {{ journey.today_projection.recommended_practice_id }}
                </bdi>
              </template>
            </div>
          </section>

          <section class="mt-6 border-t border-slate-800 pt-5">
            <h2 class="text-xs font-bold text-slate-500">نشاط محفوظ</h2>
            <dl class="mt-4 space-y-4 text-sm">
              <div class="flex items-end justify-between gap-4">
                <dt class="text-slate-500">Practice Definitions</dt>
                <dd dir="ltr" class="font-mono text-lg text-slate-200">
                  {{ journey.activity.practice_count }}
                </dd>
              </div>
              <div class="flex items-end justify-between gap-4">
                <dt class="text-slate-500">بدأت</dt>
                <dd dir="ltr" class="font-mono text-lg text-slate-200">
                  {{ journey.activity.started_practice_count }}
                </dd>
              </div>
              <div class="flex items-end justify-between gap-4">
                <dt class="text-slate-500">اكتملت كنشاط</dt>
                <dd dir="ltr" class="font-mono text-lg text-slate-200">
                  {{ journey.activity.completed_practice_count }}
                </dd>
              </div>
              <div class="flex items-end justify-between gap-4">
                <dt class="text-slate-500">إجمالي المحاولات</dt>
                <dd dir="ltr" class="font-mono text-lg text-slate-200">
                  {{ journey.activity.attempt_count }}
                </dd>
              </div>
            </dl>
          </section>

          <section class="mt-6 border-t border-slate-800 pt-5">
            <h2 class="text-xs font-bold text-slate-500">حدود الملكية</h2>
            <div class="mt-3 space-y-3 text-xs leading-6 text-slate-400">
              <p>
                <span class="font-bold text-slate-300">Progress:</span>
                journey/activity context داخل Learn.
              </p>
              <p>
                <span class="font-bold text-slate-300">Mastery:</span>
                مملوك لـ Progress & Evidence ولا يُحسب هنا.
              </p>
              <p>
                <span class="font-bold text-slate-300">Evidence:</span>
                يمكن عرض سياق موجز فقط؛ Formal Review خارج W02.
              </p>
            </div>
          </section>

          <div
            class="mt-6 rounded-lg border border-amber-900/70 bg-amber-950/20 p-3 text-xs leading-6 text-amber-100"
          >
            Completion ≠ Mastery. عدد الأنشطة الصحيحة أو المكتملة لا يتحول إلى نسبة Mastery ولا إلى حالة إتقان مزيفة.
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

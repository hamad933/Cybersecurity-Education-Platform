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

const openLessonSections = ref<Record<string, boolean>>({
  '01': false,
  '02': true,
  '03': false,
  '04': false,
  '05': false,
  'practice': false,
  'assessment': false,
  'lab': false,
});

const toggleLessonSection = (key: string) => {
  openLessonSections.value[key] = !openLessonSections.value[key];
};

const shelfOpen = ref(false);
const toggleShelf = () => {
  shelfOpen.value = !shelfOpen.value;
};
</script>

<template>
  <Head title="المعرفة والتعلّم — التعلّم والدروس" />
  <div dir="rtl" class="min-h-screen bg-slate-950 text-slate-100 dark:bg-[#070c14] dark:text-slate-100">
    <div class="w-full px-4 py-4 sm:px-6 xl:px-8">
      <!-- TOP Tools & Modes Bar -->
      <div
        dir="ltr"
        class="grid min-h-[740px] grid-cols-1 gap-4 xl:grid-cols-[280px_minmax(0,1fr)_300px]"
      >
        <!-- LEFT: Learning Journey ("مسار التعلم") -->
        <aside
          dir="rtl"
          class="flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur dark:bg-[#0b1322]/90"
          aria-label="رحلة التعلّم"
        >
          <!-- Track Overall Progress Header -->
          <div class="border-b border-slate-800/80 pb-3">
            <div class="flex items-center justify-between">
              <h2 class="text-xs font-bold text-slate-200">مسار التعلم</h2>
              <span class="font-mono text-[11px] font-bold text-slate-400">النسبة غير متوفرة</span>
            </div>
            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-800">
              <div class="h-full rounded-full bg-slate-700 transition-all duration-300" style="width: 0%"></div>
            </div>
            <p class="mt-2.5 text-xs font-semibold text-slate-300 truncate">
              {{ selectedStep?.capability_id ?? 'أمن تطبيقات الويب' }}
            </p>
          </div>

          <!-- Timeline Stepper List (Neutral State) -->
          <div class="mt-3 flex-1 space-y-2 overflow-y-auto pr-0.5 text-xs">
            <div class="rounded-xl border border-dashed border-slate-800 bg-slate-950/40 p-4 text-center text-slate-500">
              <span class="text-xl block mb-2">⏳</span>
              <p>لا يوجد تقدم مسجل في مسار الدرس الحالي.</p>
            </div>

            <!-- Dynamic Journey Items from Props -->
            <div v-if="journey.items.length" class="mt-4 pt-3 border-t border-slate-800 space-y-1.5">
              <p class="text-[10px] font-bold uppercase text-slate-500 font-mono">الأنشطة المسجلة</p>
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
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-1.5">
                    <span>{{ index + 1 }}.</span>
                    <bdi dir="ltr" class="font-mono font-semibold">{{ item.practice_id }}</bdi>
                  </div>
                  <span v-if="item.successful_attempt_count > 0" class="text-emerald-400 text-xs">✓</span>
                </div>
              </button>
            </div>
          </div>

          <!-- Bottom Action: View Path Map -->
          <div class="mt-3 pt-3 border-t border-slate-800/80">
            <Link
              href="/knowledge/visualize"
              class="focus-ring flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700/80 bg-slate-800/60 px-3 py-2 text-xs font-bold text-slate-200 hover:bg-slate-800 hover:text-white transition shadow-sm"
            >
              <span>🗺️</span>
              <span>عرض خريطة المسار</span>
            </Link>
          </div>
        </aside>

        <!-- CENTER: Lesson Surface / Learning Content -->
        <main
          dir="rtl"
          class="flex min-w-0 flex-1 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-5 shadow-lg backdrop-blur sm:p-7 dark:bg-[#0b1322]/90"
        >
          <div v-if="active" class="flex min-w-0 flex-1 flex-col">
            <!-- Gateways -->
            <div class="mb-5 border-b border-slate-800/80 pb-4">
              <KnowledgeTabs active="learn" :object-id="active?.id" />
            </div>

            <!-- Lesson Header -->
            <header class="border-b border-slate-800/80 pb-5">
              <!-- Breadcrumbs & Actions Row -->
              <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <nav
                  aria-label="مسار الدرس"
                  class="flex items-center gap-1.5 font-mono text-slate-400"
                >
                  <span class="text-slate-300 font-semibold">{{ active.title_ar }}</span>
                  <span class="text-slate-600">&gt;</span>
                  <span class="text-slate-400">أمن تطبيقات الويب</span>
                  <span class="text-slate-600">&gt;</span>
                  <bdi dir="ltr" class="text-cyan-400">
                    {{ selectedStep?.capability_id ?? 'أمن تطبيقات الويب' }}
                  </bdi>
                </nav>
                <div class="flex items-center gap-1.5 text-slate-400">
                  <button type="button" class="focus-ring rounded-lg p-1.5 hover:bg-slate-800 hover:text-amber-300" title="إضافة للمفضلة">⭐</button>
                  <button type="button" class="focus-ring rounded-lg p-1.5 hover:bg-slate-800 hover:text-cyan-300" title="نسخ الرابط">🔗</button>
                  <button type="button" class="focus-ring rounded-lg p-1.5 hover:bg-slate-800 hover:text-slate-200" title="نسخ المعرف">📋</button>
                  <button type="button" class="focus-ring rounded-lg p-1.5 hover:bg-slate-800 hover:text-slate-200" title="تكبير">⛶</button>
                </div>
              </div>

              <!-- Main Title & Badges -->
              <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                  <p class="text-xs font-bold text-cyan-300">سطح الدرس والمحتوى التعليمي</p>
                  <div class="mt-1 flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-black text-slate-100 sm:text-3xl tracking-tight">
                      {{ active.title_ar }}
                    </h1>
                  </div>
                  <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-400 font-mono">
                    <bdi dir="ltr" class="text-cyan-300 font-bold">{{ active.id }}</bdi>
                  </div>
                </div>
              </div>

              <!-- Taxonomy Tags -->
              <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700/80 bg-slate-800/80 px-3 py-1 font-mono text-[11px] text-slate-200 shadow-sm">
                  <span>🎯</span>
                  <span>OWASP</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700/80 bg-slate-800/80 px-3 py-1 font-mono text-[11px] text-slate-200 shadow-sm">
                  <span>🛡️</span>
                  <span>CWE-89</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700/80 bg-slate-800/80 px-3 py-1 font-mono text-[11px] text-slate-200 shadow-sm">
                  <span>🌐</span>
                  <span>Web</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-700/80 bg-slate-800/80 px-3 py-1 font-mono text-[11px] text-slate-200 shadow-sm">
                  <span>⚡</span>
                  <span>Injection</span>
                </span>
              </div>

              <!-- Notes & Lesson Toolbar -->
              <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800/90 bg-slate-950/80 px-4 py-2 shadow-sm">
                <div class="flex items-center gap-2 text-xs">
                  <button type="button" class="focus-ring rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1 text-slate-300 hover:bg-slate-800 transition">↩ تراجع</button>
                  <button type="button" class="focus-ring rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1 text-slate-300 hover:bg-slate-800 transition">↪ إعادة</button>
                  <button type="button" class="focus-ring inline-flex items-center gap-1 rounded-lg border border-cyan-600/70 bg-cyan-600/20 px-3 py-1 font-bold text-cyan-200 hover:bg-cyan-600/30 transition">
                    <span>💾</span>
                    <span>حفظ</span>
                  </button>
                  <div class="ms-2 flex items-center gap-1.5 text-slate-400 text-[11px]">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    <span>سجلت الملاحظات محفوظة تلقائياً</span>
                  </div>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-400 font-mono">
                  <span>التقدم في الدرس</span>
                  <span class="text-slate-500">-/-</span>
                  <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-800">
                    <div class="h-full rounded-full bg-slate-700" style="width: 0%"></div>
                  </div>
                </div>
              </div>
            </header>

            <!-- Structured Lesson Cards -->
            <div class="mt-6 flex-1 space-y-4">
              <!-- Card 01: Introduction -->
              <article class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4 transition">
                <header class="flex items-center justify-between cursor-pointer" @click="toggleLessonSection('01')">
                  <div class="flex items-center gap-2.5">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-800 text-slate-400 text-[10px] font-bold">-</span>
                    <bdi dir="ltr" class="font-mono text-xs font-bold text-slate-400">01</bdi>
                    <h3 class="font-bold text-sm text-slate-200">SQL Injection</h3>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500">فهم المفهوم وأمثلة مبسطة</span>
                    <span class="text-slate-500 text-xs">{{ openLessonSections['01'] ? '▲' : '▼' }}</span>
                  </div>
                </header>
              </article>

              <!-- Card 02: Understanding Vulnerable Queries -->
              <article class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4 transition">
                <header class="flex items-center justify-between cursor-pointer border-b border-slate-800/80 pb-3" @click="toggleLessonSection('02')">
                  <div class="flex items-center gap-2.5">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-800 text-slate-400 text-[10px] font-black">-</span>
                    <bdi dir="ltr" class="font-mono text-xs font-bold text-slate-400">02</bdi>
                    <h3 class="font-bold text-sm text-slate-200">فهم الاستعلامات الضعيفة</h3>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 font-medium">كيف تنشأ نقاط الضعف في الاستعلامات</span>
                    <span class="text-slate-400 text-xs">{{ openLessonSections['02'] ? '▲' : '▼' }}</span>
                  </div>
                </header>

                <div v-if="openLessonSections['02']" class="mt-4 space-y-4 text-sm leading-relaxed text-slate-300">
                  <p>
                    يحدث حقن SQL عندما يتم إدخال بيانات غير موثوقة من المستخدم في الاستعلام، مما قد يسمح للمهاجم بتغيير المنطق إلى الوضيعات
                    حساسة، أو تنفيذ أوامر غير متوقعة.
                  </p>

                  <div class="text-xs">
                    <a href="#code-sample" class="text-cyan-300 underline font-medium">مثال على استعلام ضعيف في Java</a>
                  </div>

                  <!-- Code Block Sample with Line Numbers and SQL Tag -->
                  <div dir="ltr" class="overflow-hidden rounded-xl border border-slate-800 bg-[#050911] shadow-inner font-mono text-xs">
                    <div class="flex items-center justify-between border-b border-slate-800/80 bg-slate-900/60 px-3 py-1.5">
                      <span class="text-[11px] font-bold text-cyan-400 uppercase">SQL</span>
                    </div>
                    <div class="p-4 space-y-1 text-slate-300">
                      <div class="flex gap-4">
                        <span class="select-none text-slate-600 w-4 text-right">1</span>
                        <span class="text-emerald-300">String query = &quot;SELECT * FROM users WHERE username = '&quot; + user + &quot;'&quot;;</span>
                      </div>
                      <div class="flex gap-4">
                        <span class="select-none text-slate-600 w-4 text-right">2</span>
                        <span class="text-slate-500">// إدخال المستخدم: ' OR '1'='1</span>
                      </div>
                      <div class="flex gap-4">
                        <span class="select-none text-slate-600 w-4 text-right">3</span>
                        <span class="text-slate-500">// النتيجة: SELECT * FROM users WHERE username = '' OR '1'='1'</span>
                      </div>
                      <div class="flex gap-4">
                        <span class="select-none text-slate-600 w-4 text-right">4</span>
                        <span class="text-amber-300 font-bold">SELECT * FROM users WHERE username = '' OR '1'='1' -- '</span>
                      </div>
                    </div>
                  </div>

                  <!-- Information Tip Callout -->
                  <div class="rounded-xl border border-cyan-900/50 bg-cyan-950/20 p-4 text-xs text-cyan-200/90 leading-relaxed flex items-start gap-3">
                    <span class="text-cyan-400 text-base">💡</span>
                    <div>
                      <p>أي إدخال غير مصفى من قبل المستخدم مباشرة داخل الاستعلام دون تحقق أو إعدادات مسبقة يُعتبر شرطاً لنقاط الضعف.</p>
                      <a href="#lesson-details" class="mt-2 inline-block text-cyan-300 underline font-medium">
                        عرض القسم: المفاهيم، أمثلة الاستعلامات، الأسئلة
                      </a>
                    </div>
                  </div>
                </div>
              </article>

              <!-- Card 03: Injection Patterns (Collapsed) -->
              <article class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4 transition">
                <header class="flex items-center justify-between cursor-pointer" @click="toggleLessonSection('03')">
                  <div class="flex items-center gap-2.5">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-800 text-slate-400 text-[10px]">○</span>
                    <bdi dir="ltr" class="font-mono text-xs font-bold text-slate-400">03</bdi>
                    <h3 class="font-bold text-sm text-slate-200">أنماط الإدخال الضار</h3>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500">أمثلة على بايلودات شائعة</span>
                    <span class="text-slate-500 text-xs">{{ openLessonSections['03'] ? '▲' : '▼' }}</span>
                  </div>
                </header>
              </article>

              <!-- Card 04: Impact (Collapsed) -->
              <article class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4 transition">
                <header class="flex items-center justify-between cursor-pointer" @click="toggleLessonSection('04')">
                  <div class="flex items-center gap-2.5">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-800 text-slate-400 text-[10px]">○</span>
                    <bdi dir="ltr" class="font-mono text-xs font-bold text-slate-400">04</bdi>
                    <h3 class="font-bold text-sm text-slate-200">الأثر وإساءة الاستخدام</h3>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500">ما الذي يمكن للمهاجم فعله؟</span>
                    <span class="text-slate-500 text-xs">{{ openLessonSections['04'] ? '▲' : '▼' }}</span>
                  </div>
                </header>
              </article>

              <!-- Card 05: Mitigation (Collapsed) -->
              <article class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4 transition">
                <header class="flex items-center justify-between cursor-pointer" @click="toggleLessonSection('05')">
                  <div class="flex items-center gap-2.5">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-800 text-slate-400 text-[10px]">○</span>
                    <bdi dir="ltr" class="font-mono text-xs font-bold text-slate-400">05</bdi>
                    <h3 class="font-bold text-sm text-slate-200">التخفيف</h3>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500">أفضل الممارسات للحماية</span>
                    <span class="text-slate-500 text-xs">{{ openLessonSections['05'] ? '▲' : '▼' }}</span>
                  </div>
                </header>
              </article>

              <!-- Connected Activities -->
              <!-- Practice Card -->
              <article class="rounded-xl border border-purple-900/60 bg-purple-950/20 p-4 transition">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2.5">
                    <span class="text-purple-400 text-base">⚡</span>
                    <h3 class="font-bold text-sm text-purple-200">Practice</h3>
                  </div>
                  <span class="text-xs text-purple-300">تدريب تفاعلي لتطبيق المفاهيم وتحديد الاستعلامات الضعيفة</span>
                </div>
              </article>

              <!-- Assessment Card -->
              <article class="rounded-xl border border-blue-900/60 bg-blue-950/20 p-4 transition">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2.5">
                    <span class="text-blue-400 text-base">📝</span>
                    <h3 class="font-bold text-sm text-blue-200">Assessment</h3>
                  </div>
                  <span class="text-xs text-blue-300">اختبار لتقييم الفهم والتحقق من الاستيعاب</span>
                </div>
              </article>

              <!-- Lab Card -->
              <article class="rounded-xl border border-cyan-900/60 bg-cyan-950/20 p-4 transition">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2.5">
                    <span class="text-cyan-400 text-base">🧪</span>
                    <h3 class="font-bold text-sm text-cyan-200">Lab</h3>
                  </div>
                  <span class="text-xs text-cyan-300">تطبيق عملي في بيئة معملية آمنة</span>
                </div>
              </article>

              <!-- Semantic Truthful No Lesson Notice -->
              <section class="mt-6 rounded-xl border border-dashed border-slate-800 bg-slate-950/40 p-6 text-center">
                <span class="text-3xl">📝</span>
                <h2 class="mt-3 text-base font-bold text-slate-300">
                  لا يوجد درس تعليمي مخصص (No Lesson State)
                </h2>
                <p class="mx-auto mt-2 max-w-md text-xs text-slate-500 leading-relaxed">
                  في هذه البنية المعمارية، وحدة المعرفة (Knowledge Unit) ليست درسًا تعليميًا (KU != Lesson).
                  حاليًا لا توجد كائنات "Lesson" مسجلة أو تقييمات معيارية في قاعدة البيانات.
                </p>
              </section>

              <!-- Independent Assessment Section -->
              <section class="mt-4 border-t border-slate-800/80 pt-4">
                <h3 class="text-xs font-bold text-slate-400">التقييم المستقل</h3>
                <div class="mt-2 rounded-xl border border-amber-900/40 bg-amber-950/20 p-3.5">
                  <p class="font-mono text-xs text-amber-200">
                    {{ journey.assessments?.state || 'NO_ASSESSMENT' }}
                  </p>
                  <p class="mt-1 text-xs text-amber-400">
                    لا توجد تقييمات تمثل إتقاناً (Mastery). إكمال الأنشطة لا يعني الإتقان.
                  </p>
                </div>
              </section>
            </div>
          </div>
          <div v-else class="grid min-h-[420px] place-items-center text-center text-slate-500">
            <div>
              <h1 class="text-xl font-bold text-slate-300">لا توجد رحلة تعلم قابلة للعرض.</h1>
              <p class="mt-2 text-xs">يرجى اختيار وحدة معرفة من المكتبة أولاً.</p>
            </div>
          </div>
        </main>

        <!-- RIGHT: Context & Lab Readiness ("السياق") -->
        <aside
          dir="rtl"
          class="flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur dark:bg-[#0b1322]/90"
          aria-label="سياق الخطوة"
        >
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <h2 class="text-sm font-bold text-slate-100">السياق</h2>
            <button type="button" class="text-slate-500 hover:text-slate-300 text-xs">✕</button>
          </div>

          <div class="mt-3 flex-1 space-y-4 overflow-y-auto pr-0.5 text-xs">
            <!-- Goal Section -->
            <section class="rounded-xl border border-dashed border-slate-800 bg-slate-950/40 p-3.5 space-y-1.5 text-slate-500">
              <div class="flex items-center gap-1.5 font-bold">
                <span>🎯</span>
                <h3>الهدف الحالي</h3>
              </div>
              <p class="text-[11px]">
                لم يتم تحديد هدف للمسار الحالي.
              </p>
            </section>

            <!-- Canonical KU Card -->
            <section class="rounded-xl border border-slate-800 bg-slate-950/60 p-3.5 space-y-1">
              <div class="flex items-center gap-1.5 text-slate-400 font-semibold">
                <span>🛡️</span>
                <h4>الوحدة المعرفية الأساسية</h4>
              </div>
              <p class="font-bold text-slate-200">{{ active?.title_ar ?? 'SQL Injection' }}</p>
              <bdi dir="ltr" class="font-mono text-[10px] text-cyan-300 block">{{ active?.id ?? 'KU-APPSEC-SQLI' }}</bdi>
            </section>

            <!-- Selected Step Specific Context -->
            <section v-if="selectedStep" class="rounded-xl border border-slate-800 bg-slate-950/60 p-3.5 space-y-2.5">
              <div>
                <h4 class="font-bold text-slate-400">سياق الخطوة المحددة</h4>
                <bdi dir="ltr" class="mt-1 block font-mono text-xs font-bold text-cyan-300">
                  {{ selectedStep.practice_id }}
                </bdi>
              </div>
              <div class="space-y-1.5 border-t border-slate-800/80 pt-2 text-[11px]">
                <div class="flex justify-between">
                  <span class="text-slate-400">Capability:</span>
                  <bdi dir="ltr" class="font-mono text-slate-300">{{ selectedStep.capability_id }}</bdi>
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
                    :class="selectedStep.latest_outcome === 'correct' ? 'text-emerald-400' : 'text-amber-400'"
                  >
                    {{ selectedStep.latest_outcome || 'N/A' }}
                  </bdi>
                </div>
              </div>
            </section>

            <!-- Prerequisites Card -->
            <section class="rounded-xl border border-slate-800 bg-slate-950/60 p-3.5 space-y-1">
              <div class="flex items-center gap-1.5 text-slate-400 font-semibold">
                <span>📚</span>
                <h4>المتطلبات السابقة</h4>
              </div>
              <p class="text-slate-300 font-medium">HTTP Basics • SQL Basics</p>
            </section>

            <!-- Suggested Practice Card -->
            <section class="rounded-xl border border-slate-800 bg-slate-950/60 p-3.5 space-y-1">
              <div class="flex items-center gap-1.5 text-slate-400 font-semibold">
                <span>🔗</span>
                <h4>الممارسة المقترحة</h4>
              </div>
              <p class="text-purple-300 font-medium">Practice — Identify vulnerable query</p>
              <span class="text-[10px] text-slate-500">موصى بها قبل الاختبار وقبل المختبر</span>
            </section>

            <!-- Lab Readiness Card -->
            <section class="rounded-xl border border-indigo-900/60 bg-indigo-950/30 p-3.5 space-y-2">
              <div class="flex items-center gap-1.5 text-indigo-300 font-semibold">
                <span>🧪</span>
                <h4>جاهزية المعمل (Lab Readiness)</h4>
              </div>
              <p class="text-[11px] text-indigo-200 leading-relaxed">
                ستكون جاهزاً لتطبيق المفاهيم عملياً بعد إكمال هذا الدرس والممارسة.
              </p>
              <div v-if="selectedStep?.definition?.lab_reference" class="mt-2">
                <bdi dir="ltr" class="font-mono text-[10px] text-indigo-300 block">
                  lab: {{ selectedStep.definition.lab_reference.id }}
                </bdi>
              </div>
              <div class="text-[11px]">
                <a href="#lab-preview" class="text-cyan-300 underline font-medium">
                  معاينة مختبر في {{ active?.title_ar ?? 'SQL Injection' }}
                </a>
              </div>
            </section>

            <!-- Quick Access Links -->
            <section class="space-y-1.5 rounded-xl border border-slate-800 bg-slate-950/40 p-3">
              <h4 class="font-bold text-slate-400 text-[11px] mb-1">الوصول السريع</h4>
              <Link href="/knowledge" class="flex items-center justify-between text-slate-300 hover:text-cyan-300 py-1">
                <span>فتح الوحدة المعرفية</span>
                <span>↗</span>
              </Link>
              <Link href="/knowledge" class="flex items-center justify-between text-slate-300 hover:text-cyan-300 py-1">
                <span>الملاحظات</span>
                <span class="text-[10px] text-slate-500">محفوظة تلقائياً</span>
              </Link>
              <Link href="/knowledge" class="flex items-center justify-between text-slate-300 hover:text-cyan-300 py-1">
                <span>المصادر</span>
                <span class="text-[10px] text-slate-500">3 مصادر موثوقة</span>
              </Link>
            </section>

            <!-- Semantic Boundaries Notice -->
            <section class="rounded-xl border border-slate-700 bg-slate-950/80 p-3 text-[10px] leading-relaxed text-slate-400">
              <span class="font-bold text-slate-300 block mb-1">حدود المعنى (Semantics)</span>
              التقدم هنا يعكس "إكمال النشاط" (Completion) ولا يمثل الإتقان (Mastery). لا توجد نسبة إتقان.
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
            <button type="button" class="focus-ring rounded-lg px-2.5 py-1 bg-cyan-500/20 text-cyan-300 font-bold">
              نظرة عامة
            </button>
            <button type="button" class="focus-ring rounded-lg px-2.5 py-1 text-slate-400 hover:text-slate-200">
              المعرفة <span class="ms-1 font-mono text-[10px] text-cyan-400">-</span>
            </button>
            <button type="button" class="focus-ring rounded-lg px-2.5 py-1 text-slate-400 hover:text-slate-200">
              العلاقات <span class="ms-1 font-mono text-[10px] text-cyan-400">-</span>
            </button>
            <button type="button" class="focus-ring rounded-lg px-2.5 py-1 text-slate-400 hover:text-slate-200">
              Practice <span class="ms-1 font-mono text-[10px] text-purple-400">-</span>
            </button>
            <button type="button" class="focus-ring rounded-lg px-2.5 py-1 text-slate-400 hover:text-slate-200">
              Assessment <span class="ms-1 font-mono text-[10px] text-blue-400">-</span>
            </button>
            <button type="button" class="focus-ring rounded-lg px-2.5 py-1 text-slate-400 hover:text-slate-200">
              Labs <span class="ms-1 font-mono text-[10px] text-cyan-400">-</span>
            </button>
            <button type="button" class="focus-ring rounded-lg px-2.5 py-1 text-slate-400 hover:text-slate-200">
              الأدلة <span class="ms-1 font-mono text-[10px] text-emerald-400">-</span>
            </button>
            <button type="button" class="focus-ring rounded-lg px-2.5 py-1 text-slate-400 hover:text-slate-200">
              الشواهد <span class="ms-1 font-mono text-[10px] text-amber-400">-</span>
            </button>
          </div>
        </div>
      </div>
    </aside>
  </div>
</template>


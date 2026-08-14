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
type JourneyItem = {
  id: string;
  practice_id: string;
  revision: number;
  capability_id: string;
  attempt_count: number;
  successful_attempt_count: number;
  latest_outcome: string | null;
  latest_activity_at: string | null;
};

defineProps<{
  catalog: CatalogItem[];
  active: ActiveUnit | null;
  journey: {
    items: JourneyItem[];
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
</script>

<template>
  <Head title="المعرفة والتعلّم — التعلّم" />
  <div dir="rtl" class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto max-w-[1600px] px-4 py-5 sm:px-6">
      <header class="border-b border-slate-800 pb-4">
        <KnowledgeTabs active="learn" :object-id="active?.id" />
      </header>

      <div class="mt-4 grid min-h-[700px] gap-4 xl:grid-cols-[260px_minmax(0,1fr)_280px]">
        <aside
          class="rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="مسار الوحدات القانونية"
        >
          <h2 class="text-xs font-bold text-slate-400">الوحدات المتاحة للتعلّم</h2>
          <ul v-if="catalog.length" class="mt-4 space-y-1">
            <li v-for="unit in catalog" :key="unit.id">
              <Link
                :href="`/knowledge/learn?object=${encodeURIComponent(unit.id)}`"
                class="focus-ring block rounded-lg px-3 py-2 text-sm"
                :class="
                  unit.id === active?.id
                    ? 'bg-cyan-400/10 text-cyan-100'
                    : 'text-slate-300 hover:bg-slate-800'
                "
              >
                {{ unit.title_ar }}
              </Link>
            </li>
          </ul>
          <p v-else class="mt-4 text-sm leading-7 text-slate-500">
            لا توجد وحدات معرفة قانونية لعرض رحلة تعلم عليها.
          </p>
        </aside>

        <main class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/35 p-5 sm:p-7">
          <div v-if="active">
            <header class="border-b border-slate-800 pb-5">
              <p class="text-xs font-bold text-cyan-300">
                إسقاط رحلة تعلّم على نفس الكائن القانوني
              </p>
              <h1 class="mt-2 text-2xl font-black sm:text-3xl">{{ active.title_ar }}</h1>
              <div class="mt-2 flex flex-wrap gap-2 text-sm text-slate-400">
                <bdi dir="ltr" class="font-mono text-cyan-200">{{ active.id }}</bdi>
                <span aria-hidden="true">·</span>
                <bdi dir="ltr">{{ active.title_en }}</bdi>
              </div>
            </header>

            <section class="mt-6">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-bold">نشاطات التعلّم الفعلية</h2>
                <p class="text-xs text-slate-500">هذه أرقام نشاط، وليست Mastery.</p>
              </div>
              <div v-if="journey.items.length" class="mt-4 space-y-3">
                <article
                  v-for="item in journey.items"
                  :key="item.id"
                  class="rounded-xl border border-slate-800 bg-slate-950/50 p-4"
                >
                  <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                      <bdi dir="ltr" class="font-mono text-sm font-bold text-cyan-200">
                        {{ item.practice_id }}
                      </bdi>
                      <p class="mt-1 text-xs text-slate-500">
                        Capability:
                        <bdi dir="ltr" class="font-mono">{{ item.capability_id }}</bdi>
                      </p>
                    </div>
                    <div class="text-left text-xs text-slate-400">
                      <p>
                        المحاولات:
                        <bdi dir="ltr" class="font-mono">{{ item.attempt_count }}</bdi>
                      </p>
                      <p class="mt-1">
                        المحاولات الصحيحة:
                        <bdi dir="ltr" class="font-mono">
                          {{ item.successful_attempt_count }}
                        </bdi>
                      </p>
                    </div>
                  </div>
                  <div class="mt-4 flex flex-wrap gap-4 border-t border-slate-800 pt-3 text-xs">
                    <span class="text-slate-500">آخر نتيجة:</span>
                    <bdi
                      dir="ltr"
                      class="font-mono"
                      :class="
                        item.latest_outcome === 'correct' ? 'text-emerald-300' : 'text-amber-300'
                      "
                    >
                      {{ item.latest_outcome ?? 'NO_ATTEMPT' }}
                    </bdi>
                    <span v-if="item.latest_activity_at" class="text-slate-500">
                      آخر نشاط: <bdi dir="ltr">{{ item.latest_activity_at }}</bdi>
                    </span>
                  </div>
                </article>
              </div>
              <div
                v-else
                class="mt-5 rounded-xl border border-dashed border-slate-700 p-8 text-center"
              >
                <h2 class="font-bold">لا توجد Practice Definitions مرتبطة بهذه الوحدة.</h2>
                <p class="mt-2 text-sm text-slate-500">
                  لا ينشئ Learn نسخة بديلة من محتوى Library أو نشاطًا افتراضيًا.
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
          aria-label="سياق الرحلة"
        >
          <h2 class="text-xs font-bold text-slate-500">حدود هذا السياق</h2>
          <p class="mt-3 text-sm leading-7 text-slate-300">
            Learn يقرأ الكائن القانوني نفسه ويضيف سياق الرحلة والنشاط فقط. لا توجد نسخة ثانية من
            Knowledge Unit.
          </p>
          <div class="mt-6 space-y-4 border-t border-slate-800 pt-5 text-sm">
            <div>
              <p class="text-xs text-slate-500">إجمالي المحاولات</p>
              <bdi dir="ltr" class="mt-1 block font-mono text-lg text-slate-200">
                {{ journey.activity.attempt_count }}
              </bdi>
            </div>
            <div>
              <p class="text-xs text-slate-500">Practice مكتملة بنشاط صحيح</p>
              <bdi dir="ltr" class="mt-1 block font-mono text-lg text-slate-200">
                {{ journey.activity.completed_practice_count }}
              </bdi>
            </div>
          </div>
          <div
            class="mt-6 rounded-lg border border-amber-900/70 bg-amber-950/20 p-3 text-xs leading-6 text-amber-100"
          >
            Progress هنا = journey/activity context. لا يتم استيراد أو حساب Mastery State في W02.
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

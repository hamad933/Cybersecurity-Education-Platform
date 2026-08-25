<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import KnowledgeTabs from './components/KnowledgeTabs.vue';
import ProvenancePanel from './components/research-quality/ProvenancePanel.vue';
import ResearchQualityWorkbench from './components/research-quality/ResearchQualityWorkbench.vue';
import SourceComparisonTable from './components/research-quality/SourceComparisonTable.vue';
import type { ResearchAnalysis, Source } from './components/research-quality/types';

type CatalogItem = { id: string; title_ar: string; title_en: string };
type Mode = 'claims' | 'compare' | 'conflicts' | 'revision';

defineProps<{
  catalog: CatalogItem[];
  active: {
    id: string;
    title_ar: string;
    title_en: string;
    revision: { id: string; revision: number; state: string; citations: string[] } | null;
  } | null;
  quality: {
    sources: Source[];
    active_source: Source | null;
    canonical_claim_ids: string[];
    review_semantics: string;
    analysis?: ResearchAnalysis;
  };
  semantic_boundary: {
    review: string;
    evidence_review: string;
    mastery_judgment: string;
  };
}>();

const mode = ref<Mode>('claims');
const workbenchMode = computed<'claims' | 'conflicts' | 'revision'>(() =>
  mode.value === 'compare' ? 'claims' : mode.value,
);
const modes: Array<{ key: Mode; ar: string; en: string; icon: string }> = [
  { key: 'claims', ar: 'الادعاءات', en: 'Claims', icon: '📋' },
  { key: 'compare', ar: 'المقارنة', en: 'Compare', icon: '⚖️' },
  { key: 'conflicts', ar: 'التعارضات', en: 'Conflicts', icon: '⚠️' },
  { key: 'revision', ar: 'المراجعة', en: 'Revision', icon: '🔄' },
];
</script>

<template>
  <Head title="المعرفة والتعلّم — البحث والجودة" />
  <div
    dir="rtl"
    class="min-h-screen bg-slate-950 text-slate-100 dark:bg-[#070c14] dark:text-slate-100"
  >
    <div class="w-full px-4 py-4 sm:px-6 xl:px-8">
      <!-- Top Modes Toolbar -->
      <header
        class="mb-4 rounded-2xl border border-slate-800/80 bg-slate-900/50 p-3.5 shadow-lg backdrop-blur dark:bg-[#0b1322]/90"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2 text-xs font-semibold text-slate-400">
            <span>🔬</span>
            <span>بيئة فحص وتدقيق جودة المصادر والمنشأ (Research & Quality Workbench)</span>
          </div>

          <div
            class="flex flex-wrap items-center gap-1 rounded-xl border border-slate-800 bg-slate-950/80 p-1"
          >
            <button
              v-for="item in modes"
              :key="item.key"
              type="button"
              class="focus-ring flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold whitespace-nowrap shadow-sm transition"
              :class="
                mode === item.key
                  ? 'border border-cyan-500/40 bg-cyan-500/20 text-cyan-200 shadow-cyan-950/50'
                  : 'text-slate-400 hover:text-slate-200'
              "
              :aria-pressed="mode === item.key"
              @click="mode = item.key"
            >
              <span>{{ item.icon }}</span>
              <span>{{ item.ar }}</span>
              <bdi dir="ltr" class="text-[10px] text-slate-500">{{ item.en }}</bdi>
            </button>
          </div>
        </div>
      </header>

      <!-- 3-Column Layout with strict physical region ownership -->
      <div dir="ltr" class="grid min-h-[740px] gap-4 xl:grid-cols-[285px_minmax(0,1fr)_315px]">
        <!-- LEFT: Source Set Sidebar (Visual LEFT) -->
        <aside
          dir="rtl"
          class="flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur dark:bg-[#0b1322]/90"
          aria-label="مصادر البحث والجودة"
        >
          <div class="border-b border-slate-800/80 pb-4">
            <div class="flex items-center justify-between">
              <h2 class="text-xs font-bold text-slate-200">مصادر المراجعة الحالية</h2>
              <span
                class="font-mono text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                dir="ltr"
              >
                SOURCE SET
              </span>
            </div>
            <p class="mt-2 text-[11px] leading-relaxed text-slate-400">
              اختيار المصدر يغيّر سياق الفحص فقط؛ ولا يمنحه أفضلية حقيقة تلقائية.
            </p>
          </div>

          <ul v-if="quality.sources.length" class="mt-4 flex-1 space-y-2 overflow-y-auto pr-0.5">
            <li v-for="source in quality.sources" :key="source.id">
              <Link
                :href="`/knowledge/research-quality?${active ? `object=${encodeURIComponent(active.id)}&` : ''}source=${encodeURIComponent(source.id)}`"
                class="focus-ring block rounded-xl border p-3 text-xs transition"
                :class="
                  source.id === quality.active_source?.id
                    ? 'border-cyan-500/40 bg-cyan-500/10 text-cyan-100 shadow-sm'
                    : 'border-slate-800/80 bg-slate-950/40 text-slate-300 hover:border-slate-700'
                "
              >
                <span class="block text-sm leading-snug font-bold">{{ source.title }}</span>
                <div class="mt-2.5 flex flex-wrap items-center gap-1.5 text-[10px]">
                  <span class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-cyan-300">
                    {{ source.authority_class }}
                  </span>
                  <span class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-emerald-400">
                    {{ source.review_status }}
                  </span>
                </div>
              </Link>
            </li>
          </ul>
          <p v-else class="mt-4 text-xs leading-6 text-slate-500">
            لا توجد مصادر محفوظة؛ لن تُنشأ مصادر وهمية.
          </p>
        </aside>

        <!-- CENTER: Research & Quality Workspace (Visual CENTER) -->
        <main
          dir="rtl"
          class="flex min-w-0 flex-1 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-5 shadow-lg backdrop-blur sm:p-7 dark:bg-[#0b1322]/90"
          aria-label="مساحة فحص الجودة والمقارنة"
        >
          <div class="mb-5 border-b border-slate-800/80 pb-4">
            <KnowledgeTabs active="research-quality" :object-id="active?.id" />
          </div>

          <header
            class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-800/80 pb-5"
          >
            <div>
              <p class="text-xs font-bold text-cyan-300">
                مراجعة جودة معرفة — ليست <bdi dir="ltr">Evidence Review</bdi>
              </p>
              <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-100 sm:text-3xl">
                {{
                  quality.active_source?.title ?? active?.title_ar ?? 'لا يوجد عمل جودة معرفة حالي'
                }}
              </h1>
              <bdi v-if="active" dir="ltr" class="mt-1.5 block font-mono text-xs text-slate-400">
                {{ active.id }}
              </bdi>
            </div>
            <div
              v-if="quality.analysis"
              class="rounded-xl border border-slate-800 bg-slate-950/60 p-2.5 text-left font-mono text-[11px]"
            >
              <span class="block text-[9px] text-slate-500">سلطة القرار</span>
              <bdi dir="ltr" class="font-bold text-emerald-400">
                {{ quality.analysis.review.decision_authority }}
              </bdi>
            </div>
          </header>

          <div class="mt-6 flex-1">
            <SourceComparisonTable
              v-if="mode === 'compare'"
              :rows="quality.analysis?.comparison.rows ?? []"
            />
            <ResearchQualityWorkbench
              v-else
              :mode="workbenchMode"
              :source="quality.active_source"
              :analysis="quality.analysis ?? null"
            />
          </div>
        </main>

        <!-- RIGHT: Provenance & Review Boundary (Visual RIGHT) -->
        <aside
          dir="rtl"
          class="flex min-w-0 flex-col rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 shadow-lg backdrop-blur dark:bg-[#0b1322]/90"
          aria-label="تتبّع المنشأ وحدود المراجعة"
        >
          <ProvenancePanel
            :source="quality.active_source"
            :provenance="quality.analysis?.provenance.sources ?? []"
          />

          <section class="mt-6 border-t border-slate-800/80 pt-4">
            <div class="flex items-center justify-between">
              <h2 class="text-xs font-bold text-slate-200">حدود الحكم والمراجعة</h2>
              <span
                class="font-mono text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                dir="ltr"
              >
                REVIEW BOUNDARY
              </span>
            </div>
            <div class="mt-3 space-y-2.5 text-xs leading-relaxed">
              <p
                class="rounded-xl border border-rose-900/60 bg-rose-950/20 p-3 text-[11px] text-rose-200"
              >
                <bdi dir="ltr" class="font-bold">Research & Quality Review != Evidence Review</bdi>.
                هذا المجال لا يصدر قرارات Evidence أو Mastery.
              </p>
              <p
                class="rounded-xl border border-amber-900/60 bg-amber-950/20 p-3 text-[11px] text-amber-200"
              >
                النظام لا يقرر حقيقة المعرفة. يمكنه كشف التعارضات وتجميع المنشأ (provenance) فقط؛
                أما التوفيق النهائي (reconciliation) فحكم بشري.
              </p>
              <dl
                class="rounded-xl border border-slate-800 bg-slate-950/60 p-3 text-[11px] text-slate-400"
              >
                <div class="flex justify-between gap-3">
                  <dt>دلالات المراجعة:</dt>
                  <dd>
                    <bdi dir="ltr" class="font-mono text-cyan-300">{{
                      quality.review_semantics
                    }}</bdi>
                  </dd>
                </div>
                <div class="mt-2 flex justify-between gap-3 border-t border-slate-800/80 pt-2">
                  <dt>التعارضات المعلقة:</dt>
                  <dd class="font-mono font-bold text-amber-300">
                    {{ quality.analysis?.reconciliation.pending_conflict_count ?? 0 }}
                  </dd>
                </div>
              </dl>
            </div>
          </section>
        </aside>
      </div>

      <!-- BOTTOM: Trace Telemetry Drawer (Closed by default) -->
      <details
        dir="rtl"
        class="mt-4 rounded-2xl border border-slate-800/80 bg-slate-900/40 px-4 py-3 shadow-lg"
      >
        <summary
          class="flex cursor-pointer items-center justify-between text-xs font-bold text-slate-300"
        >
          <span>أثر التوفيق والمراجعة (Reconciliation & Revision) — مساحة مقارنة مؤقتة</span>
          <span class="font-mono text-[10px] text-cyan-400">تفاصيل التتبع</span>
        </summary>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <section>
            <h3 class="text-xs font-bold text-slate-400">ادعاءات قانونية غير محلولة</h3>
            <div class="mt-2 space-y-1.5">
              <bdi
                v-for="claimId in quality.analysis?.revision_reasoning.unresolved_claim_ids ?? []"
                :key="claimId"
                dir="ltr"
                class="block rounded-lg border border-amber-900/50 bg-amber-950/20 px-3 py-2 font-mono text-[11px] text-amber-200"
              >
                {{ claimId }}
              </bdi>
              <p
                v-if="!quality.analysis?.revision_reasoning.unresolved_claim_ids.length"
                class="text-xs text-slate-500"
              >
                لا توجد ادعاءات قانونية بلا منشأ مرصود.
              </p>
            </div>
          </section>
          <section>
            <h3 class="text-xs font-bold text-slate-400">الأدوات التالية المسموحة</h3>
            <div class="mt-2 flex flex-wrap gap-1.5">
              <bdi
                v-for="tool in quality.analysis?.reconciliation.allowed_next_tools ?? []"
                :key="tool"
                dir="ltr"
                class="rounded-lg border border-slate-800 bg-slate-950/60 px-2.5 py-1 font-mono text-[10px] text-slate-400"
              >
                {{ tool }}
              </bdi>
            </div>
          </section>
        </div>
      </details>
    </div>
  </div>
</template>

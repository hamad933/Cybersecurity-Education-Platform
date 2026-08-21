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
const modes: Array<{ key: Mode; ar: string; en: string }> = [
  { key: 'claims', ar: 'الادعاءات', en: 'Claims' },
  { key: 'compare', ar: 'المقارنة', en: 'Compare' },
  { key: 'conflicts', ar: 'التعارضات', en: 'Conflicts' },
  { key: 'revision', ar: 'المراجعة', en: 'Revision' },
];
</script>

<template>
  <Head title="المعرفة والتعلّم — البحث والجودة" />
  <div dir="rtl" class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto max-w-[1600px] px-4 py-5 sm:px-6">
      <header class="border-b border-slate-800 pb-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <KnowledgeTabs active="research-quality" :object-id="active?.id" />
          <div class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
            <button
              v-for="item in modes"
              :key="item.key"
              type="button"
              class="focus-ring rounded-lg px-3 py-2 text-xs transition"
              :class="
                mode === item.key
                  ? 'bg-cyan-400/10 text-cyan-200'
                  : 'text-slate-400 hover:text-slate-200'
              "
              :aria-pressed="mode === item.key"
              @click="mode = item.key"
            >
              <span class="font-bold">{{ item.ar }}</span>
              <bdi dir="ltr" class="mr-1 text-[10px] text-slate-600">{{ item.en }}</bdi>
            </button>
          </div>
        </div>
      </header>

      <div class="mt-4 grid min-h-[720px] gap-4 xl:grid-cols-[285px_minmax(0,1fr)_315px]">
        <aside
          class="rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="مصادر البحث والجودة"
        >
          <div class="border-b border-slate-800 pb-4">
            <p class="text-[10px] font-bold tracking-[0.2em] text-slate-600" dir="ltr">
              SOURCE SET
            </p>
            <h2 class="mt-1 text-sm font-black">مصادر المراجعة الحالية</h2>
            <p class="mt-2 text-xs leading-6 text-slate-500">
              اختيار المصدر يغيّر سياق الفحص فقط؛ ولا يمنحه أفضلية حقيقة تلقائية.
            </p>
          </div>

          <ul v-if="quality.sources.length" class="mt-4 space-y-2">
            <li v-for="source in quality.sources" :key="source.id">
              <Link
                :href="`/knowledge/research-quality?${active ? `object=${encodeURIComponent(active.id)}&` : ''}source=${encodeURIComponent(source.id)}`"
                class="focus-ring block rounded-xl border px-3 py-3 text-sm transition"
                :class="
                  source.id === quality.active_source?.id
                    ? 'border-cyan-800 bg-cyan-950/20 text-cyan-100'
                    : 'border-slate-800 bg-slate-950/25 text-slate-300 hover:border-slate-600'
                "
              >
                <span class="block font-bold">{{ source.title }}</span>
                <span class="mt-2 flex flex-wrap items-center gap-2 text-[10px]">
                  <bdi dir="ltr" class="text-slate-500">{{ source.authority_class }}</bdi>
                  <bdi dir="ltr" class="text-slate-600">{{ source.review_status }}</bdi>
                </span>
              </Link>
            </li>
          </ul>
          <p v-else class="mt-4 text-sm leading-7 text-slate-500">
            لا توجد <bdi dir="ltr">Source Records</bdi> محفوظة؛ لن تُنشأ مصادر وهمية.
          </p>
        </aside>

        <main class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/35 p-5 sm:p-7">
          <header
            class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-800 pb-5"
          >
            <div>
              <p class="text-xs font-bold text-cyan-300">
                مراجعة جودة معرفة — ليست <bdi dir="ltr">Evidence Review</bdi>
              </p>
              <h1 class="mt-2 text-2xl font-black">
                {{ quality.active_source?.title ?? active?.title_ar ?? 'لا يوجد عمل جودة معرفة حالي' }}
              </h1>
              <bdi v-if="active" dir="ltr" class="mt-2 block font-mono text-xs text-slate-500">
                {{ active.id }}
              </bdi>
            </div>
            <div v-if="quality.analysis" class="text-left text-[10px] text-slate-600">
              <span class="block">Decision authority</span>
              <bdi dir="ltr" class="font-mono text-emerald-300">
                {{ quality.analysis.review.decision_authority }}
              </bdi>
            </div>
          </header>

          <div class="mt-6">
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

        <aside
          class="rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="Provenance and review boundary"
        >
          <ProvenancePanel
            :source="quality.active_source"
            :provenance="quality.analysis?.provenance.sources ?? []"
          />

          <section class="mt-6 border-t border-slate-800 pt-5">
            <p class="text-[10px] font-bold tracking-[0.2em] text-slate-600" dir="ltr">
              REVIEW BOUNDARY
            </p>
            <h2 class="mt-1 text-sm font-black">حدود الحكم</h2>
            <div class="mt-3 space-y-3 text-xs leading-6">
              <p class="rounded-lg border border-rose-900/60 bg-rose-950/15 p-3 text-rose-100">
                <bdi dir="ltr">Research & Quality Review != Evidence Review</bdi>. هذا المجال لا
                يصدر قرارات Evidence أو Mastery.
              </p>
              <p class="rounded-lg border border-amber-900/60 bg-amber-950/15 p-3 text-amber-100">
                النظام لا يقرر حقيقة المعرفة. يمكنه كشف التعارضات وتجميع provenance فقط؛ أما
                reconciliation النهائي فحكم بشري.
              </p>
              <dl class="rounded-lg border border-slate-800 p-3 text-slate-500">
                <div class="flex justify-between gap-3">
                  <dt>Review semantics</dt>
                  <dd><bdi dir="ltr">{{ quality.review_semantics }}</bdi></dd>
                </div>
                <div class="mt-2 flex justify-between gap-3">
                  <dt>Pending conflicts</dt>
                  <dd>{{ quality.analysis?.reconciliation.pending_conflict_count ?? 0 }}</dd>
                </div>
              </dl>
            </div>
          </section>
        </aside>
      </div>

      <details class="mt-4 rounded-xl border border-slate-800 bg-slate-900/30 px-4 py-3">
        <summary class="cursor-pointer text-sm font-bold text-slate-400">
          أثر reconciliation و revision — مساحة مقارنة مؤقتة
        </summary>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <section>
            <h2 class="text-xs font-bold text-slate-500">Unresolved canonical claims</h2>
            <div class="mt-2 space-y-2">
              <bdi
                v-for="claimId in quality.analysis?.revision_reasoning.unresolved_claim_ids ?? []"
                :key="claimId"
                dir="ltr"
                class="block rounded border border-amber-900/50 px-3 py-2 font-mono text-[11px] text-amber-200"
              >
                {{ claimId }}
              </bdi>
              <p
                v-if="!quality.analysis?.revision_reasoning.unresolved_claim_ids.length"
                class="text-xs text-slate-600"
              >
                لا توجد Claims canonical بلا provenance مرصود.
              </p>
            </div>
          </section>
          <section>
            <h2 class="text-xs font-bold text-slate-500">Allowed next tools</h2>
            <div class="mt-2 flex flex-wrap gap-2">
              <bdi
                v-for="tool in quality.analysis?.reconciliation.allowed_next_tools ?? []"
                :key="tool"
                dir="ltr"
                class="rounded border border-slate-800 px-2 py-1 font-mono text-[10px] text-slate-500"
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

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import KnowledgeTabs from './components/KnowledgeTabs.vue';

type CatalogItem = { id: string; title_ar: string; title_en: string };
type Claim = {
  id: string;
  claim_id: string;
  segment_ref: string;
  supported_scope: string;
  excluded_semantics: string;
  assessment: string;
  used_by_active_revision: boolean;
};
type Source = {
  id: string;
  authority_class: string;
  title: string;
  exact_url: string | null;
  relative_path: string | null;
  sha256: string;
  review_status: string;
  metadata: Record<string, unknown>;
  claims: Claim[];
};

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
  };
  semantic_boundary: {
    review: string;
    evidence_review: string;
    mastery_judgment: string;
  };
}>();
</script>

<template>
  <Head title="المعرفة والتعلّم — البحث والجودة" />
  <div dir="rtl" class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto max-w-[1600px] px-4 py-5 sm:px-6">
      <header class="border-b border-slate-800 pb-4">
        <KnowledgeTabs active="research-quality" :object-id="active?.id" />
      </header>

      <div class="mt-4 grid min-h-[700px] gap-4 xl:grid-cols-[280px_minmax(0,1fr)_300px]">
        <aside
          class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="مصادر البحث والجودة"
        >
          <h2 class="text-xs font-bold text-slate-400">مصادر البحث والجودة</h2>
          <ul v-if="quality.sources.length" class="mt-4 space-y-1">
            <li v-for="source in quality.sources" :key="source.id">
              <Link
                :href="`/knowledge/research-quality?${active ? `object=${encodeURIComponent(active.id)}&` : ''}source=${encodeURIComponent(source.id)}`"
                class="focus-ring block rounded-lg px-3 py-2 text-sm"
                :class="
                  source.id === quality.active_source?.id
                    ? 'bg-cyan-400/10 text-cyan-100'
                    : 'text-slate-300 hover:bg-slate-800'
                "
              >
                {{ source.title }}
              </Link>
            </li>
          </ul>
          <p v-else class="mt-4 text-sm leading-7 text-slate-500">لا توجد Source Records محفوظ�.</p>
        </aside>

        <main class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/35 p-5 sm:p-7">
          <div v-if="quality.active_source">
            <header class="border-b border-slate-800 pb-5">
              <p class="text-xs font-bold text-cyan-300">
                مراجعة جودة معرفة — ليست Evidence Review
              </p>
              <h1 class="mt-2 text-2xl font-black">{{ quality.active_source.title }}</h1>
              <div class="mt-3 flex flex-wrap gap-2 text-xs">
                <bdi
                  dir="ltr"
                  class="rounded border border-slate-700 px-2 py-1 font-mono text-emerald-300"
                >
                  {{ quality.active_source.review_status }}
                </bdi>
                <span class="rounded border border-slate-700 px-2 py-1 text-slate-400">
                  {{ quality.active_source.authority_class }}
                </span>
              </div>
            </header>

            <section class="mt-6">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-bold">Claims ومواضع الدعم</h2>
                <p class="text-xs text-slate-500">Compare = أداة؛ Review = workflow جودة معرفة.</p>
              </div>
              <div v-if="quality.active_source.claims.length" class="mt-4 space-y-3">
                <article
                  v-for="claim in quality.active_source.claims"
                  :key="claim.id"
                  class="rounded-xl border border-slate-800 bg-slate-950/50 p-4"
                >
                  <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                      <bdi dir="ltr" class="font-mono text-sm font-bold text-cyan-200">
                        {{ claim.claim_id }}
                      </bdi>
                      <span
                        v-if="claim.used_by_active_revision"
                        class="rounded bg-cyan-950 px-2 py-1 text-[10px] text-cyan-200"
                      >
                        مستخدم في المراجعة النشطة
                      </span>
                    </div>
                    <bdi
                      dir="ltr"
                      class="font-mono text-xs"
                      :class="
                        claim.assessment === 'supported' ? 'text-emerald-300' : 'text-amber-300'
                      "
                    >
                      {{ claim.assessment }}
                    </bdi>
                  </div>
                  <bdi dir="ltr" class="mt-3 block font-mono text-xs text-slate-500">
                    {{ claim.segment_ref }}
                  </bdi>
                  <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div class="rounded-lg border border-emerald-900/70 bg-emerald-950/15 p-3">
                      <h3 class="text-xs font-bold text-emerald-300">النطاق المدعوم</h3>
                      <p class="mt-2 text-sm leading-7 text-slate-300">
                        {{ claim.supported_scope }}
                      </p>
                    </div>
                    <div class="rounded-lg border border-amber-900/70 bg-amber-950/15 p-3">
                      <h3 class="text-xs font-bold text-amber-300">الدلالة المستبعدة</h3>
                      <p class="mt-2 text-sm leading-7 text-slate-300">
                        {{ claim.excluded_semantics }}
                      </p>
                    </div>
                  </div>
                </article>
              </div>
              <p
                v-else
                class="mt-5 rounded-xl border border-dashed border-slate-700 p-6 text-center text-sm text-slate-500"
              >
                هذا المصدر لا يملك Claims محفوظة.
              </p>
            </section>
          </div>
          <div v-else class="grid min-h-[420px] place-items-center text-center">
            <div>
              <h1 class="font-bold text-slate-300">لا يوجد عمل جودة معرفة حالي.</h1>
              <p class="mt-2 text-sm text-slate-500">لا توجد مصادر مخزنة، ولن تُنشأ سجلات وهمية.</p>
            </div>
          </div>
        </main>

        <aside
          class="min-w-0 rounded-xl border border-slate-800 bg-slate-900/50 p-4"
          aria-label="Provenance المصدر"
        >
          <h2 class="text-xs font-bold text-slate-500">Provenance الفريد للمصدر المحدد</h2>
          <div v-if="quality.active_source" class="mt-4 space-y-5 text-sm">
            <section>
              <p class="text-xs text-slate-500">المسار أو الرابط</p>
              <a
                v-if="quality.active_source.exact_url"
                :href="quality.active_source.exact_url"
                target="_blank"
                rel="noreferrer"
                dir="ltr"
                class="focus-ring mt-2 block text-left text-xs break-all text-cyan-300 underline"
              >
                {{ quality.active_source.exact_url }}
              </a>
              <bdi
                v-else-if="quality.active_source.relative_path"
                dir="ltr"
                class="mt-2 block font-mono text-xs break-all text-slate-300"
              >
                {{ quality.active_source.relative_path }}
              </bdi>
              <p v-else class="mt-2 text-slate-500">لا يوجد مسار مصدر محفوظ.</p>
            </section>
            <section>
              <p class="text-xs text-slate-500">Integrity digest</p>
              <bdi dir="ltr" class="mt-2 block font-mono text-[10px] break-all text-slate-400">
                sha256:{{ quality.active_source.sha256 }}
              </bdi>
            </section>
          </div>

          <div
            class="mt-6 rounded-lg border border-rose-900/60 bg-rose-950/15 p-3 text-xs leading-6 text-rose-100"
          >
            هذا المجال لا يستورد <bdi dir="ltr">Evidence Decisions</bdi> ولا
            <bdi dir="ltr">Mastery States</bdi>، ولا يصدر أحكام <bdi dir="ltr">A03</bdi>.
          </div>
        </aside>
      </div>

      <details class="mt-4 rounded-xl border border-slate-800 bg-slate-900/30 px-4 py-3">
        <summary class="cursor-pointer text-sm font-bold text-slate-400">
          مساحة مقارنة مؤقتة — مغلقة افتراضيًا
        </summary>
        <p class="mt-3 text-sm leading-7 text-slate-500">
          يمكن أن تستضيف Compare أو Revision diff لاحقًا دون تكرار العمل الدائم في CENTER.
        </p>
      </details>
    </div>
  </div>
</template>

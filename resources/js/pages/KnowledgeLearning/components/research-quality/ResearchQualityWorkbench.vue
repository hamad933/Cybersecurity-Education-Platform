<script setup lang="ts">
import type { ResearchAnalysis, Source } from './types';

defineProps<{
  mode: 'claims' | 'conflicts' | 'revision';
  source: Source | null;
  analysis: ResearchAnalysis | null;
}>();
</script>

<template>
  <section v-if="mode === 'claims'" aria-label="Claims and anchors">
    <div v-if="source">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <p class="text-[10px] font-bold tracking-[0.2em] text-slate-600" dir="ltr">CLAIMS</p>
          <h2 class="mt-1 text-lg font-black">Claims ومواضع الدعم</h2>
        </div>
        <p class="text-xs text-slate-500">
          الـ assessment حالة مصدر؛ وليست حكمًا آليًا على حقيقة المعرفة.
        </p>
      </div>
      <div v-if="source.claims.length" class="mt-4 space-y-3">
        <article
          v-for="claim in source.claims"
          :key="claim.id"
          class="rounded-xl border border-slate-800 bg-slate-950/40 p-4"
        >
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <bdi dir="ltr" class="font-mono text-sm font-bold text-cyan-200">
                {{ claim.claim_id }}
              </bdi>
              <bdi dir="ltr" class="mt-1 block font-mono text-[10px] text-slate-600">
                {{ claim.segment_ref }}
              </bdi>
            </div>
            <bdi
              dir="ltr"
              class="rounded border border-slate-700 px-2 py-1 font-mono text-xs text-slate-300"
            >
              {{ claim.assessment }}
            </bdi>
          </div>
          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div class="rounded-lg border border-emerald-900/60 bg-emerald-950/10 p-3">
              <h3 class="text-xs font-bold text-emerald-300">النطاق المدعوم</h3>
              <p class="mt-2 text-sm leading-7 text-slate-300">{{ claim.supported_scope }}</p>
            </div>
            <div class="rounded-lg border border-amber-900/60 bg-amber-950/10 p-3">
              <h3 class="text-xs font-bold text-amber-300">الدلالة المستبعدة</h3>
              <p class="mt-2 text-sm leading-7 text-slate-300">{{ claim.excluded_semantics }}</p>
            </div>
          </div>
        </article>
      </div>
      <p
        v-else
        class="mt-5 rounded-xl border border-dashed border-slate-700 p-8 text-center text-sm text-slate-500"
      >
        هذا المصدر لا يملك Claims محفوظة.
      </p>
    </div>
    <p v-else class="py-16 text-center text-sm text-slate-500">لا يوجد مصدر محدد.</p>
  </section>

  <section v-else-if="mode === 'conflicts'" aria-label="Knowledge claim conflicts">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="text-[10px] font-bold tracking-[0.2em] text-slate-600" dir="ltr">CONFLICTS</p>
        <h2 class="mt-1 text-lg font-black">التعارض وإعادة التوفيق</h2>
      </div>
      <p class="text-xs text-slate-500">
        {{ analysis?.conflicts.length ?? 0 }} تعارضات مرصودة تحتاج حكمًا بشريًا.
      </p>
    </div>

    <div v-if="analysis?.conflicts.length" class="mt-4 space-y-4">
      <article
        v-for="conflict in analysis.conflicts"
        :key="conflict.claim_id"
        class="rounded-xl border border-amber-900/60 bg-amber-950/10 p-4"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <bdi dir="ltr" class="font-mono text-sm font-bold text-amber-200">
            {{ conflict.claim_id }}
          </bdi>
          <bdi dir="ltr" class="text-[10px] text-amber-300">{{ conflict.status }}</bdi>
        </div>
        <div class="mt-4 grid gap-3 lg:grid-cols-2">
          <section
            v-for="variant in conflict.variants"
            :key="`${variant.source_id}:${variant.segment_ref}`"
            class="rounded-lg border border-slate-800 bg-slate-950/35 p-3"
          >
            <p class="font-bold text-slate-200">{{ variant.source_title }}</p>
            <bdi dir="ltr" class="mt-1 block font-mono text-[10px] text-slate-600">
              {{ variant.segment_ref }}
            </bdi>
            <p class="mt-3 text-xs leading-6 text-slate-400">{{ variant.supported_scope }}</p>
            <p class="mt-2 text-xs leading-6 text-slate-500">
              مستبعد: {{ variant.excluded_semantics }}
            </p>
            <bdi dir="ltr" class="mt-2 block text-[10px] text-slate-500">
              assessment={{ variant.assessment }}
            </bdi>
          </section>
        </div>
        <p class="mt-4 border-t border-amber-900/40 pt-3 text-xs leading-6 text-amber-100">
          لا يختار النظام مصدرًا مفضلًا ولا يصدر <bdi dir="ltr">system_truth_decision</bdi>.
          قرار reconciliation من اختصاص المراجع البشري.
        </p>
      </article>
    </div>
    <p
      v-else
      class="mt-5 rounded-xl border border-dashed border-slate-700 p-8 text-center text-sm text-slate-500"
    >
      لا توجد تعارضات مرصودة بين Claim variants الحالية.
    </p>
  </section>

  <section v-else aria-label="Revision and provenance reasoning">
    <p class="text-[10px] font-bold tracking-[0.2em] text-slate-600" dir="ltr">
      REVISION REASONING
    </p>
    <h2 class="mt-1 text-lg font-black">استدلال المراجعة والمنشأ</h2>
    <div class="mt-4 grid gap-4 md:grid-cols-3">
      <article class="rounded-xl border border-slate-800 bg-slate-950/35 p-4">
        <p class="text-xs text-slate-500">Canonical claims</p>
        <p class="mt-2 text-2xl font-black">
          {{ analysis?.revision_reasoning.canonical_claim_ids.length ?? 0 }}
        </p>
      </article>
      <article class="rounded-xl border border-emerald-900/60 bg-emerald-950/10 p-4">
        <p class="text-xs text-emerald-400">Resolved to sources</p>
        <p class="mt-2 text-2xl font-black text-emerald-200">
          {{ analysis?.revision_reasoning.resolved_claim_ids.length ?? 0 }}
        </p>
      </article>
      <article class="rounded-xl border border-amber-900/60 bg-amber-950/10 p-4">
        <p class="text-xs text-amber-400">Unresolved provenance</p>
        <p class="mt-2 text-2xl font-black text-amber-200">
          {{ analysis?.revision_reasoning.unresolved_claim_ids.length ?? 0 }}
        </p>
      </article>
    </div>

    <div v-if="analysis?.revision_reasoning.canonical_claim_ids.length" class="mt-5 space-y-2">
      <div
        v-for="claimId in analysis.revision_reasoning.canonical_claim_ids"
        :key="claimId"
        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-800 px-3 py-3"
      >
        <bdi dir="ltr" class="font-mono text-xs text-cyan-200">{{ claimId }}</bdi>
        <div class="flex flex-wrap gap-2">
          <bdi
            v-for="sourceId in analysis.revision_reasoning.claim_sources[claimId] ?? []"
            :key="sourceId"
            dir="ltr"
            class="rounded bg-slate-900 px-2 py-1 font-mono text-[10px] text-slate-500"
          >
            {{ sourceId }}
          </bdi>
          <span
            v-if="!analysis.revision_reasoning.claim_sources[claimId]?.length"
            class="text-xs text-amber-300"
          >
            لا يوجد source provenance محفوظ
          </span>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { ResearchAnalysis, Source } from './types';

defineProps<{
  mode: 'claims' | 'conflicts' | 'revision';
  source: Source | null;
  analysis: ResearchAnalysis | null;
}>();

const reconciliationNotes = ref<Record<string, string>>({});
const copiedClaimId = ref<string | null>(null);

const copyReconciliationNote = async (claimId: string) => {
  const note = reconciliationNotes.value[claimId]?.trim();
  if (!note || typeof navigator === 'undefined' || !navigator.clipboard) return;

  await navigator.clipboard.writeText(note);
  copiedClaimId.value = claimId;
  setTimeout(() => {
    if (copiedClaimId.value === claimId) copiedClaimId.value = null;
  }, 1600);
};
</script>

<template>
  <!-- Mode 1: Claims -->
  <section v-if="mode === 'claims'" aria-label="Claims and anchors">
    <div v-if="source">
      <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-800/80 pb-3">
        <div>
          <span
            class="font-mono text-[10px] font-bold tracking-widest text-slate-500 uppercase"
            dir="ltr"
            >CLAIMS</span
          >
          <h2 class="mt-1 text-base font-bold text-slate-100">الادعاءات ومواضع الدعم للمصدر</h2>
        </div>
        <p class="text-xs text-slate-400">التقييم حالة مصدر؛ وليس حكمًا آليًا على حقيقة المعرفة.</p>
      </div>

      <div v-if="source.claims.length" class="mt-4 space-y-3">
        <article
          v-for="claim in source.claims"
          :key="claim.id"
          class="rounded-2xl border border-slate-800/80 bg-slate-950/60 p-4 shadow-sm transition hover:border-slate-700"
        >
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <bdi dir="ltr" class="font-mono text-sm font-bold text-cyan-300">
                {{ claim.claim_id }}
              </bdi>
              <bdi dir="ltr" class="mt-1 block font-mono text-[10px] text-slate-500">
                {{ claim.segment_ref }}
              </bdi>
            </div>
            <span
              dir="ltr"
              class="rounded-full border border-slate-700/80 bg-slate-900 px-3 py-1 font-mono text-xs font-semibold text-slate-300 shadow-sm"
            >
              {{ claim.assessment }}
            </span>
          </div>

          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div class="rounded-xl border border-emerald-900/60 bg-emerald-950/20 p-3.5 shadow-sm">
              <div class="mb-1 flex items-center gap-1.5 text-xs font-bold text-emerald-400">
                <span>✓</span>
                <h3>النطاق المدعوم</h3>
              </div>
              <p class="text-xs leading-relaxed text-slate-200">{{ claim.supported_scope }}</p>
            </div>
            <div class="rounded-xl border border-amber-900/60 bg-amber-950/20 p-3.5 shadow-sm">
              <div class="mb-1 flex items-center gap-1.5 text-xs font-bold text-amber-400">
                <span>✕</span>
                <h3>الدلالة المستبعدة</h3>
              </div>
              <p class="text-xs leading-relaxed text-slate-200">{{ claim.excluded_semantics }}</p>
            </div>
          </div>
        </article>
      </div>
      <p
        v-else
        class="mt-5 rounded-2xl border border-dashed border-slate-800 bg-slate-950/40 p-8 text-center text-xs text-slate-500"
      >
        هذا المصدر لا يملك ادعاءات محفوظة.
      </p>
    </div>
    <p v-else class="py-16 text-center text-xs text-slate-500">لا يوجد مصدر محدد.</p>
  </section>

  <!-- Mode 2: Conflicts -->
  <section v-else-if="mode === 'conflicts'" aria-label="Knowledge claim conflicts">
    <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-800/80 pb-3">
      <div>
        <span
          class="font-mono text-[10px] font-bold tracking-widest text-amber-400 uppercase"
          dir="ltr"
          >CONFLICTS</span
        >
        <h2 class="mt-1 text-base font-bold text-slate-100">التعارض وإعادة التوفيق</h2>
      </div>
      <p class="font-mono text-xs text-slate-400">
        {{ analysis?.conflicts.length ?? 0 }} تعارضات مرصودة تحتاج حكمًا بشريًا.
      </p>
    </div>

    <div v-if="analysis?.conflicts.length" class="mt-4 space-y-4">
      <article
        v-for="conflict in analysis.conflicts"
        :key="conflict.claim_id"
        class="rounded-2xl border border-amber-500/40 bg-amber-950/15 p-5 shadow-lg shadow-amber-950/20"
      >
        <div
          class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-amber-900/40 pb-3"
        >
          <div class="flex items-center gap-2">
            <span class="text-sm text-amber-400">⚠️</span>
            <bdi dir="ltr" class="font-mono text-sm font-bold text-amber-200">
              {{ conflict.claim_id }}
            </bdi>
          </div>
          <span
            class="rounded-full border border-amber-500/40 bg-amber-950/80 px-2.5 py-0.5 font-mono text-xs font-bold text-amber-300"
          >
            {{ conflict.status }}
          </span>
        </div>

        <div class="grid gap-3 lg:grid-cols-2">
          <section
            v-for="variant in conflict.variants"
            :key="`${variant.source_id}:${variant.segment_ref}`"
            class="rounded-xl border border-slate-800 bg-slate-950/60 p-4 shadow-sm"
          >
            <p class="text-sm font-bold text-slate-200">{{ variant.source_title }}</p>
            <bdi dir="ltr" class="mt-1 block font-mono text-[10px] text-slate-500">
              {{ variant.segment_ref }}
            </bdi>
            <p class="mt-3 text-xs leading-relaxed text-slate-300">{{ variant.supported_scope }}</p>
            <p class="mt-2 text-xs leading-relaxed text-amber-300/80">
              مستبعد: {{ variant.excluded_semantics }}
            </p>
            <bdi dir="ltr" class="mt-2.5 block font-mono text-[10px] text-slate-400">
              تقييم المصدر = {{ variant.assessment }}
            </bdi>
          </section>
        </div>

        <p class="mt-4 border-t border-amber-900/40 pt-3 text-xs leading-relaxed text-amber-200">
          لا يختار النظام مصدرًا مفضلًا ولا يصدر
          <bdi dir="ltr" class="font-bold">system_truth_decision</bdi>. قرار التوفيق
          (reconciliation) من اختصاص المراجع البشري.
        </p>

        <section class="mt-4 rounded-xl border border-slate-800 bg-slate-950/60 p-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <label
              :for="`reconciliation-note-${conflict.claim_id}`"
              class="text-xs font-bold text-slate-300"
            >
              مذكرة توفيق بشرية مؤقتة
            </label>
            <bdi dir="ltr" class="font-mono text-[9px] text-amber-300">
              RQ_PERSISTENT_RECONCILIATION_OWNER_REQUIRED
            </bdi>
          </div>
          <textarea
            :id="`reconciliation-note-${conflict.claim_id}`"
            v-model="reconciliationNotes[conflict.claim_id]"
            rows="3"
            maxlength="2000"
            class="focus-ring mt-2 w-full rounded-lg border border-slate-700 bg-slate-900 p-2.5 text-xs leading-6 text-slate-200 placeholder:text-slate-600"
            placeholder="دوّن أسئلة المراجعة أو نقاط المقارنة هنا. ستُفقد عند مغادرة الصفحة ولن تصبح قرارًا قانونيًا."
          ></textarea>
          <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
            <p class="text-[10px] leading-5 text-slate-500">
              ذاكرة واجهة مؤقتة فقط؛ لا تُكتب في metadata أو assessment أو Evidence.
            </p>
            <button
              type="button"
              class="focus-ring rounded-lg border border-slate-700 px-2.5 py-1 text-[10px] font-bold text-slate-300 hover:bg-slate-800 disabled:opacity-40"
              :disabled="!reconciliationNotes[conflict.claim_id]?.trim()"
              @click="copyReconciliationNote(conflict.claim_id)"
            >
              {{ copiedClaimId === conflict.claim_id ? 'نُسخت المذكرة' : 'نسخ المذكرة المؤقتة' }}
            </button>
          </div>
        </section>
      </article>
    </div>
    <p
      v-else
      class="mt-5 rounded-2xl border border-dashed border-slate-800 bg-slate-950/40 p-8 text-center text-xs text-slate-500"
    >
      لا توجد تعارضات مرصودة بين تنويعات الادعاءات الحالية.
    </p>
  </section>

  <!-- Mode 3: Revision Reasoning -->
  <section v-else aria-label="Revision and provenance reasoning">
    <div class="border-b border-slate-800/80 pb-3">
      <span
        class="font-mono text-[10px] font-bold tracking-widest text-slate-500 uppercase"
        dir="ltr"
      >
        REVISION REASONING
      </span>
      <h2 class="mt-1 text-base font-bold text-slate-100">استدلال المراجعة والمنشأ</h2>
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-3">
      <article class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 shadow-sm">
        <p class="text-xs text-slate-400">ادعاءات قانونية (Canonical Claims)</p>
        <p class="mt-2 font-mono text-2xl font-black text-slate-100">
          {{ analysis?.revision_reasoning.canonical_claim_ids.length ?? 0 }}
        </p>
      </article>
      <article class="rounded-2xl border border-emerald-900/60 bg-emerald-950/20 p-4 shadow-sm">
        <p class="text-xs text-emerald-400">مربوطة بمصادر (Resolved)</p>
        <p class="mt-2 font-mono text-2xl font-black text-emerald-300">
          {{ analysis?.revision_reasoning.resolved_claim_ids.length ?? 0 }}
        </p>
      </article>
      <article class="rounded-2xl border border-amber-900/60 bg-amber-950/20 p-4 shadow-sm">
        <p class="text-xs text-amber-400">بلا منشأ مرصود (Unresolved)</p>
        <p class="mt-2 font-mono text-2xl font-black text-amber-300">
          {{ analysis?.revision_reasoning.unresolved_claim_ids.length ?? 0 }}
        </p>
      </article>
    </div>

    <div v-if="analysis?.revision_reasoning.canonical_claim_ids.length" class="mt-5 space-y-2">
      <div
        v-for="claimId in analysis.revision_reasoning.canonical_claim_ids"
        :key="claimId"
        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/60 px-4 py-3 shadow-sm transition hover:border-slate-700"
      >
        <bdi dir="ltr" class="font-mono text-xs font-bold text-cyan-300">{{ claimId }}</bdi>
        <div class="flex flex-wrap gap-1.5">
          <bdi
            v-for="sourceId in analysis.revision_reasoning.claim_sources[claimId] ?? []"
            :key="sourceId"
            dir="ltr"
            class="rounded-lg border border-slate-800 bg-slate-900 px-2 py-1 font-mono text-[10px] text-slate-400"
          >
            {{ sourceId }}
          </bdi>
          <span
            v-if="!analysis.revision_reasoning.claim_sources[claimId]?.length"
            class="text-xs text-amber-300"
          >
            لا يوجد منشأ مصدر محفوظ
          </span>
        </div>
      </div>
    </div>
  </section>
</template>

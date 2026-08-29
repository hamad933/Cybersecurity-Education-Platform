<script setup lang="ts">
import { ref } from 'vue';

import { shortDigest } from '../formatters';
import type { ResultItem } from '../types';

defineProps<{ result: ResultItem | null; pending: boolean }>();
const emit = defineEmits<{ createHandoff: [claim: string] }>();

const handoffClaim = ref(
  'مرشح دليل مشتق من نتيجة المحاكاة المختومة؛ يخضع لاحقًا لعملية Intake في Progress & Evidence.',
);
</script>

<template>
  <div class="sim-context" data-testid="result-right">
    <div class="sim-panel-heading">
      <div class="sim-panel-heading__title">
        <p class="sim-kicker">RIGHT · CONTEXT</p>
        <h2>السياق</h2>
      </div>
    </div>

    <template v-if="result">
      <div class="sim-sealed-note">
        <strong>IMMUTABLE</strong>
        <span>الهوية والمراجعة والـ digest مقفلة تاريخيًا.</span>
      </div>

      <!-- Semantic Replay / Compare -->
      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="sim-section-icon sim-text-purple"
          >
            <path d="M16 3h5v5" />
            <path d="M4 20L21 3" />
            <path d="M21 16v5h-5" />
            <path d="M15 15l6 6" />
            <path d="M4 4l5 5" />
          </svg>
          <h3>Semantic Replay / Compare</h3>
        </div>
        <div
          v-if="result.replay_compare"
          class="sim-integrity"
          :class="{ 'sim-integrity--mismatch': !result.replay_compare.integrity_match }"
        >
          <strong class="sim-technical">{{
            result.replay_compare.integrity_match ? 'INTEGRITY_MATCH' : 'INTEGRITY_MISMATCH'
          }}</strong>
          <code class="sim-technical sim-wrap">{{
            result.replay_compare.reconstructed_state_digest
          }}</code>
          <small class="sim-technical">{{ result.replay_compare.compared_at }}</small>
        </div>
        <p v-else class="sim-muted">لم تُحفظ مقارنة Replay لهذه النتيجة بعد.</p>
      </section>

      <section class="sim-context-section" data-testid="result-interpretation-boundary">
        <h3>حدود النتيجة</h3>
        <p class="sim-context-copy">
          Outcome والملخص حقول Result محكومة، وليسا Evidence أو ضمانًا لتفسير أمني. تظهر مقارنة
          إعادة البناء فقط عند وجود replay_compare محفوظة.
        </p>
      </section>

      <!-- Candidate Evidence Handoff -->
      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="sim-section-icon sim-text-success"
          >
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
            <polyline points="22 4 12 14.01 9 11.01" />
          </svg>
          <h3>Candidate Evidence Handoff</h3>
        </div>
        <p class="sim-context-copy">
          هذا تسليم Candidate فقط؛ ليس قبولًا في Evidence canonical ولا يبدأ Review أو Mastery.
        </p>
        <div v-if="result.candidate_evidence_handoff" class="sim-handoff">
          <span class="sim-chip">{{ result.candidate_evidence_handoff.status }}</span>
          <code class="sim-technical sim-wrap">{{
            result.candidate_evidence_handoff.manifest_digest
          }}</code>
          <p>هذا Candidate Handoff فقط؛ ليس قبولًا في Evidence canonical ولا قرار Mastery.</p>
        </div>
        <form v-else class="sim-action-form" @submit.prevent="emit('createHandoff', handoffClaim)">
          <label>
            <span>Candidate claim</span>
            <textarea v-model="handoffClaim" rows="4" :disabled="pending" />
          </label>
          <button type="submit" class="sim-button" :disabled="pending">
            إنشاء Candidate Handoff
          </button>
        </form>
      </section>

      <dl class="sim-facts">
        <div>
          <dt>Result</dt>
          <dd class="sim-technical">{{ shortDigest(result.id) }}</dd>
        </div>
        <div>
          <dt>Revision</dt>
          <dd>{{ result.result_revision }}</dd>
        </div>
        <div>
          <dt>Sealed by</dt>
          <dd class="sim-technical">{{ result.sealed_by }}</dd>
        </div>
        <div>
          <dt>Provenance</dt>
          <dd class="sim-technical">{{ result.provenance }}</dd>
        </div>
      </dl>

      <div class="sim-rule-note">
        Result ≠ Evidence. القبول والمراجعة وMastery خارج ملكية Simulation & Enterprise.
      </div>
    </template>
    <p v-else class="sim-muted">اختر نتيجة لعرض سياقها التاريخي.</p>
  </div>
</template>

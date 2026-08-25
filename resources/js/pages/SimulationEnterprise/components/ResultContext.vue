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
      <p class="sim-kicker">RIGHT · SELECTED RESULT</p>
      <h2>سياق النتيجة المختومة</h2>
    </div>
    <template v-if="result">
      <div class="sim-sealed-note">
        <strong>IMMUTABLE</strong><span>الهوية والمراجعة والـ digest مقفلة تاريخيًا.</span>
      </div>
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

      <section class="sim-context-section">
        <h3>Semantic Replay / Compare</h3>
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

      <section class="sim-context-section">
        <h3>Candidate Evidence Handoff</h3>
        <div v-if="result.candidate_evidence_handoff" class="sim-handoff">
          <span class="sim-chip">{{ result.candidate_evidence_handoff.status }}</span>
          <code class="sim-technical sim-wrap">{{
            result.candidate_evidence_handoff.manifest_digest
          }}</code>
          <p>هذا Candidate Handoff فقط؛ ليس قبولًا في Evidence canonical ولا قرار Mastery.</p>
        </div>
        <form v-else class="sim-action-form" @submit.prevent="emit('createHandoff', handoffClaim)">
          <label
            ><span>Candidate claim</span
            ><textarea v-model="handoffClaim" rows="4" :disabled="pending" />
          </label>
          <button type="submit" class="sim-button" :disabled="pending">
            إنشاء Candidate Handoff
          </button>
        </form>
      </section>
      <div class="sim-rule-note">
        Result ≠ Evidence. القبول والمراجعة وMastery خارج ملكية Simulation & Enterprise.
      </div>
    </template>
    <p v-else class="sim-muted">اختر نتيجة لعرض سياقها التاريخي.</p>
  </div>
</template>

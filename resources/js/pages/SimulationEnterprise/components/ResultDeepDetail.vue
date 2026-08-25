<script setup lang="ts">
import { jsonText } from '../formatters';
import type { ResultItem } from '../types';

defineProps<{ result: ResultItem | null }>();
</script>

<template>
  <div v-if="result" class="sim-deep-grid" data-testid="result-bottom">
    <section class="sim-deep-section">
      <h3>Frozen Result Payload</h3>
      <pre class="sim-json">{{ jsonText(result.sealed_payload) }}</pre>
    </section>
    <section class="sim-deep-section">
      <h3>Artifacts</h3>
      <pre class="sim-json">{{ jsonText(result.artifacts) }}</pre>
    </section>
    <section class="sim-deep-section">
      <h3>Replay Reconstruction</h3>
      <pre class="sim-json">{{ jsonText(result.replay_compare?.reconstruction ?? {}) }}</pre>
    </section>
    <section v-if="result.candidate_evidence_handoff" class="sim-deep-section">
      <h3>Candidate Handoff Manifest</h3>
      <pre class="sim-json">{{
        jsonText(result.candidate_evidence_handoff.candidate_manifest)
      }}</pre>
    </section>
  </div>
</template>

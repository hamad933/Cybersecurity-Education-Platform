<script setup lang="ts">
import { jsonText } from '../formatters';
import type { RunItem } from '../types';

defineProps<{ run: RunItem | null }>();
</script>

<template>
  <div v-if="run" class="sim-deep-grid" data-testid="run-bottom">
    <section class="sim-deep-section">
      <h3>Runtime Snapshots</h3>
      <article v-for="snapshot in run.snapshots" :key="snapshot.id" class="sim-deep-block">
        <div class="sim-card__topline">
          <strong>Snapshot {{ snapshot.sequence }}</strong
          ><span class="sim-chip">{{ snapshot.snapshot_kind }}</span>
        </div>
        <code class="sim-technical sim-wrap">{{ snapshot.state_digest }}</code>
        <pre class="sim-json">{{ jsonText(snapshot.state) }}</pre>
      </article>
    </section>
    <section class="sim-deep-section">
      <h3>Prepared Checkpoints</h3>
      <article v-for="checkpoint in run.checkpoints" :key="checkpoint.id" class="sim-deep-block">
        <div class="sim-card__topline">
          <strong>Checkpoint {{ checkpoint.sequence }}</strong
          ><span class="sim-chip">{{ checkpoint.restorable ? 'RESTORABLE' : 'LOCKED' }}</span>
        </div>
        <small class="sim-technical">Source Snapshot {{ checkpoint.source_snapshot_id }}</small>
        <code class="sim-technical sim-wrap">{{ checkpoint.state_digest }}</code>
        <pre class="sim-json">{{ jsonText(checkpoint.state) }}</pre>
      </article>
    </section>
    <section class="sim-deep-section">
      <h3>Operations / Policies / Runtime State</h3>
      <pre class="sim-json">{{
        jsonText({
          operations: run.operations,
          execution_policies: run.execution_policies,
          runtime_state: run.runtime_state,
        })
      }}</pre>
    </section>
  </div>
</template>

<script setup lang="ts">
import { shortDigest } from '../formatters';
import type { LabItem } from '../types';

defineProps<{ lab: LabItem | null }>();
</script>

<template>
  <div class="sim-context" data-testid="lab-right">
    <div class="sim-panel-heading">
      <p class="sim-kicker">RIGHT · SELECTED CONTEXT</p>
      <h2>سياق المختبر</h2>
    </div>
    <template v-if="lab">
      <dl class="sim-facts">
        <div>
          <dt>Revision</dt>
          <dd>{{ lab.revision }}</dd>
        </div>
        <div>
          <dt>Slug</dt>
          <dd class="sim-technical">{{ lab.slug }}</dd>
        </div>
        <div>
          <dt>Provenance</dt>
          <dd class="sim-technical">{{ lab.provenance }}</dd>
        </div>
        <div>
          <dt>Digest</dt>
          <dd class="sim-technical">{{ shortDigest(lab.digest) }}</dd>
        </div>
      </dl>
      <section class="sim-context-section">
        <h3>Target Baseline</h3>
        <code class="sim-technical sim-wrap">{{ lab.baseline_id }}</code>
      </section>
      <div class="sim-rule-note">
        Baseline ≠ Runtime Snapshot ≠ Checkpoint. التهيئة تشتق حالة تشغيل جديدة.
      </div>
    </template>
    <p v-else class="sim-muted">اختر مختبرًا لعرض سياقه.</p>
  </div>
</template>

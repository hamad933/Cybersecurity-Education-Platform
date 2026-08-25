<script setup lang="ts">
import LifecycleBadge from './LifecycleBadge.vue';
import { runTypeLabel, shortDigest } from '../formatters';
import type { RunItem } from '../types';

defineProps<{ run: RunItem | null }>();
const emit = defineEmits<{ openDeepDetail: [] }>();

const lifecycleSteps = ['PREPARED', 'READY', 'RUNNING', 'PAUSED', 'COMPLETED'];

function reached(run: RunItem, step: string): boolean {
  const current = lifecycleSteps.indexOf(run.lifecycle);
  const target = lifecycleSteps.indexOf(step);
  if (['STOPPED', 'FAILED'].includes(run.lifecycle)) return target <= Math.max(current, 2);
  return current >= target;
}
</script>

<template>
  <section class="sim-surface" data-testid="run-center">
    <header class="sim-surface-header">
      <div>
        <p class="sim-kicker">RUNS · OPERATIONAL LIFECYCLE</p>
        <h1>{{ run?.definition_title_ar ?? 'التشغيلات' }}</h1>
        <p>
          أدر دورة التشغيل المحدد. حقائق telemetry تبقى في سياق التشغيل على اليمين والتفاصيل الخام
          في الأسفل.
        </p>
      </div>
      <button
        v-if="run"
        type="button"
        class="sim-button sim-button--quiet"
        @click="emit('openDeepDetail')"
      >
        فتح السجل التشغيلي
      </button>
    </header>

    <div v-if="!run" class="sim-empty">
      <strong>لا يوجد تشغيل محدد</strong>
      <p>اختر تشغيلًا من لوحة البنية.</p>
    </div>

    <template v-else>
      <article class="sim-run-hero">
        <div class="sim-card__topline">
          <span class="sim-chip">{{ runTypeLabel(run.run_type) }}</span>
          <span v-if="run.source_fixture" class="sim-chip sim-chip--fixture">FIXTURE SOURCE</span>
          <LifecycleBadge :value="run.lifecycle" />
        </div>
        <code class="sim-technical sim-wrap">{{ run.id }}</code>
        <div class="sim-lifecycle-track" aria-label="دورة حياة التشغيل">
          <div
            v-for="step in lifecycleSteps"
            :key="step"
            :class="{ 'is-reached': reached(run, step) }"
          >
            <span aria-hidden="true" />
            <small>{{ step }}</small>
          </div>
        </div>
      </article>

      <div class="sim-metric-grid">
        <article>
          <small>Seed</small><strong class="sim-technical">{{ run.seed }}</strong>
          <p>حتمية التهيئة</p>
        </article>
        <article>
          <small>Events</small><strong>{{ run.events.length }}</strong>
          <p>سجل سببي append-only</p>
        </article>
        <article>
          <small>Runtime Snapshots</small><strong>{{ run.snapshots.length }}</strong>
          <p>حالات تشغيل ملتقطة</p>
        </article>
        <article>
          <small>Checkpoints</small><strong>{{ run.checkpoints.length }}</strong>
          <p>مشتقة من prepared state</p>
        </article>
      </div>

      <section class="sim-section-block">
        <div class="sim-section-heading">
          <div>
            <p class="sim-kicker">PREPARED LINEAGE</p>
            <h2>هدف التشغيل المادي</h2>
          </div>
        </div>
        <div class="sim-lineage-chain">
          <div>
            <small>Digital Twin</small
            ><strong class="sim-technical">{{ shortDigest(run.digital_twin_id) }}</strong>
          </div>
          <i aria-hidden="true">←</i>
          <div>
            <small>Baseline</small
            ><strong class="sim-technical">{{ shortDigest(run.baseline_id) }}</strong>
          </div>
          <i aria-hidden="true">←</i>
          <div>
            <small>Input Digest</small
            ><strong class="sim-technical">{{ shortDigest(run.input_digest) }}</strong>
          </div>
        </div>
      </section>
    </template>
  </section>
</template>

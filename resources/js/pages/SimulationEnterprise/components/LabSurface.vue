<script setup lang="ts">
import { shortDigest } from '../formatters';
import type { LabItem } from '../types';

defineProps<{ lab: LabItem | null }>();
const emit = defineEmits<{ openDeepDetail: [] }>();
</script>

<template>
  <section class="sim-surface" data-testid="lab-center">
    <header class="sim-surface-header">
      <div>
        <p class="sim-kicker">LABS · STANDALONE EXECUTION</p>
        <h1>{{ lab?.title_ar ?? 'المختبرات' }}</h1>
        <p>راجع تعريف المختبر المستقل المثبت على Baseline قبل تهيئة تشغيله التشغيلي.</p>
      </div>
      <button
        v-if="lab"
        type="button"
        class="sim-button sim-button--quiet"
        @click="emit('openDeepDetail')"
      >
        فحص الإعداد الخام
      </button>
    </header>

    <div v-if="!lab" class="sim-empty">
      <strong>لا يوجد مختبر محدد</strong>
      <p>اختر مختبرًا منشورًا من لوحة البنية.</p>
    </div>

    <template v-else>
      <div class="sim-hero-card">
        <div class="sim-card__topline">
          <span class="sim-chip">STANDALONE LAB</span>
          <span class="sim-chip sim-chip--fixed">BASELINE PINNED</span>
        </div>
        <h2>{{ lab.title_ar }}</h2>
        <p class="sim-technical">{{ lab.slug }}</p>
        <div class="sim-definition-path" aria-label="سلسلة تعريف المختبر">
          <span>Lab Definition</span><i aria-hidden="true">←</i><span>Baseline</span
          ><i aria-hidden="true">←</i><span>Prepared Run</span>
        </div>
        <div class="sim-contract-strip">
          <div>
            <small>Revision</small><strong>{{ lab.revision }}</strong>
          </div>
          <div>
            <small>Baseline</small
            ><strong class="sim-technical">{{ shortDigest(lab.baseline_id) }}</strong>
          </div>
          <div>
            <small>Digest</small
            ><strong class="sim-technical">{{ shortDigest(lab.digest) }}</strong>
          </div>
        </div>
      </div>
      <div class="sim-rule-note sim-rule-note--wide">
        المختبر المستقل يملك تعريفه ومسار تشغيله. وجود مرجع له داخل Scenario لا ينشئ Standalone Lab
        Run.
      </div>
    </template>
  </section>
</template>

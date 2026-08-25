<script setup lang="ts">
import LifecycleBadge from './LifecycleBadge.vue';
import { runTypeLabel, shortDigest } from '../formatters';
import type { ResultItem } from '../types';

defineProps<{ result: ResultItem | null }>();
const emit = defineEmits<{ openDeepDetail: [] }>();
</script>

<template>
  <section class="sim-surface" data-testid="result-center">
    <header class="sim-surface-header">
      <div>
        <p class="sim-kicker">RESULTS · HISTORICAL TRUTH</p>
        <h1>النتائج وإعادة العرض</h1>
        <p>
          اقرأ الحقيقة التاريخية المختومة. Replay يقارن reconstruction ولا يغيّر التشغيل أو النتيجة.
        </p>
      </div>
      <button
        v-if="result"
        type="button"
        class="sim-button sim-button--quiet"
        @click="emit('openDeepDetail')"
      >
        فتح Replay الكامل
      </button>
    </header>

    <div v-if="!result" class="sim-empty">
      <strong>لا توجد نتيجة محددة</strong>
      <p>اختر نتيجة مختومة من لوحة البنية.</p>
    </div>

    <template v-else>
      <article class="sim-result-seal" data-testid="result-immutable-indicator">
        <div class="sim-result-seal__icon" aria-hidden="true">✓</div>
        <div>
          <p class="sim-kicker">SEALED · IMMUTABLE · REVISION {{ result.result_revision }}</p>
          <h2>حقيقة تاريخية مختومة</h2>
          <p>
            هذه النتيجة للقراءة والمقارنة فقط؛ أي Replay لاحق يضيف سجل مقارنة ولا يعدّل Result أو
            Run.
          </p>
        </div>
        <LifecycleBadge value="SEALED" sealed />
      </article>

      <div class="sim-metric-grid sim-metric-grid--three">
        <article>
          <small>Outcome</small><strong class="sim-technical">{{ result.outcome }}</strong>
          <p>{{ runTypeLabel(result.run_type) }}</p>
        </article>
        <article>
          <small>Score</small><strong class="sim-technical">{{ result.score ?? '—' }}</strong>
          <p>القيمة المختومة</p>
        </article>
        <article>
          <small>Events</small><strong>{{ result.replay_timeline.length }}</strong>
          <p>Timeline محفوظة</p>
        </article>
      </div>

      <section class="sim-section-block">
        <div class="sim-section-heading">
          <div>
            <p class="sim-kicker">SEALED SUMMARY</p>
            <h2>تفسير النتيجة</h2>
          </div>
          <span class="sim-chip">{{ result.run_lifecycle }}</span>
        </div>
        <blockquote class="sim-summary">{{ result.summary_ar }}</blockquote>
        <div class="sim-contract-strip">
          <div>
            <small>Run</small
            ><strong class="sim-technical">{{ shortDigest(result.run_id) }}</strong>
          </div>
          <div>
            <small>Result digest</small
            ><strong class="sim-technical">{{ shortDigest(result.result_digest) }}</strong>
          </div>
          <div>
            <small>Sealed at</small><strong class="sim-technical">{{ result.sealed_at }}</strong>
          </div>
        </div>
      </section>
    </template>
  </section>
</template>

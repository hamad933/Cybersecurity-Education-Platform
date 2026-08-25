<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import LifecycleBadge from './LifecycleBadge.vue';
import { displayValue } from '../projections';
import type { ResultItem } from '../types';

const props = defineProps<{ result: ResultItem | null }>();

const selectedIndex = ref(0);
const selectedEvent = computed(() => props.result?.replay_timeline[selectedIndex.value] ?? null);

watch(
  () => props.result,
  () => {
    selectedIndex.value = 0;
  },
  { immediate: true },
);

function step(offset: number): void {
  const length = props.result?.replay_timeline.length ?? 0;
  selectedIndex.value = Math.min(
    Math.max(selectedIndex.value + offset, 0),
    Math.max(length - 1, 0),
  );
}
</script>

<template>
  <section class="sim-surface sim-canvas-surface" data-testid="result-center">
    <header class="sim-workbench-header">
      <div>
        <p class="sim-kicker">RESULTS · SEALED REPLAY</p>
        <h1>النتائج وإعادة العرض</h1>
      </div>
    </header>

    <div v-if="!result" class="sim-empty"><strong>لا توجد نتيجة محددة</strong></div>

    <template v-else>
      <div class="sim-replay-header" data-testid="result-immutable-indicator">
        <div class="sim-seal-mark" aria-hidden="true">◆</div>
        <div>
          <p class="sim-kicker">SEALED · IMMUTABLE · REVISION {{ result.result_revision }}</p>
          <strong>{{ result.summary_ar }}</strong>
        </div>
        <div class="sim-replay-head-facts">
          <span
            ><small>OUTCOME</small><strong class="sim-technical">{{ result.outcome }}</strong></span
          >
          <span
            ><small>SEALED AT</small
            ><strong class="sim-technical">{{ result.sealed_at }}</strong></span
          >
        </div>
        <LifecycleBadge value="SEALED" sealed />
      </div>

      <div class="sim-replay-controls" aria-label="تنقل محلي في سجل Replay">
        <button
          type="button"
          class="sim-button sim-button--quiet"
          :disabled="selectedIndex === 0"
          @click="step(-1)"
        >
          السابق
        </button>
        <span class="sim-technical"
          >EVENT {{ selectedIndex + 1 }} / {{ result.replay_timeline.length }}</span
        >
        <button
          type="button"
          class="sim-button sim-button--quiet"
          :disabled="selectedIndex >= result.replay_timeline.length - 1"
          @click="step(1)"
        >
          التالي
        </button>
        <span class="sim-readonly-control">READ-ONLY INSPECTION · NO RUN MUTATION</span>
      </div>

      <div class="sim-replay-workbench" data-testid="result-replay-timeline">
        <section class="sim-replay-rail">
          <header class="sim-pane-heading">
            <div>
              <p class="sim-kicker">SEALED TIMELINE</p>
              <h2>الحقيقة التاريخية</h2>
            </div>
            <span class="sim-chip">{{ result.replay_timeline.length }} EVENTS</span>
          </header>
          <ol v-if="result.replay_timeline.length">
            <li
              v-for="(event, index) in result.replay_timeline"
              :key="event.sequence"
              data-testid="replay-event"
            >
              <button
                type="button"
                :class="{ 'is-selected': index === selectedIndex }"
                :aria-pressed="index === selectedIndex"
                @click="selectedIndex = index"
              >
                <time class="sim-technical">{{ event.occurred_at }}</time>
                <span class="sim-replay-dot" aria-hidden="true" />
                <span
                  ><strong class="sim-technical">{{ event.event_type }}</strong
                  ><small>SEQ {{ event.sequence }}</small></span
                >
              </button>
            </li>
          </ol>
          <p v-else class="sim-muted">لا يحتوي Result المختوم على أحداث Replay.</p>
        </section>

        <section class="sim-replay-inspector">
          <header class="sim-pane-heading">
            <div>
              <p class="sim-kicker">HISTORICAL EVENT</p>
              <h2>تفاصيل النقطة المحددة</h2>
            </div>
            <span v-if="selectedEvent" class="sim-chip">FROZEN</span>
          </header>
          <template v-if="selectedEvent">
            <h3 class="sim-technical">{{ selectedEvent.event_type }}</h3>
            <dl class="sim-operational-facts">
              <div>
                <dt>Sequence</dt>
                <dd>{{ selectedEvent.sequence }}</dd>
              </div>
              <div>
                <dt>Actor</dt>
                <dd class="sim-technical">{{ selectedEvent.actor_id }}</dd>
              </div>
              <div v-for="(value, key) in selectedEvent.payload" :key="String(key)">
                <dt class="sim-technical">{{ key }}</dt>
                <dd class="sim-technical">{{ displayValue(value) }}</dd>
              </div>
            </dl>
          </template>
          <div class="sim-sealed-state-block">
            <span class="sim-seal-mark" aria-hidden="true">✓</span>
            <div>
              <strong>Immutable historical state</strong>
              <p>التنقل يغيّر نقطة الفحص المحلية فقط؛ لا يكتب إلى Run أو Result.</p>
            </div>
          </div>
        </section>
      </div>

      <div class="sim-result-truth-band">
        <span
          ><small>RESULT DIGEST</small
          ><code class="sim-technical">{{ result.result_digest }}</code></span
        >
        <span
          ><small>ARTIFACTS</small><strong>{{ result.artifacts.length }}</strong></span
        >
        <span
          ><small>REPLAY COMPARE</small
          ><strong class="sim-technical">{{
            result.replay_compare
              ? result.replay_compare.integrity_match
                ? 'INTEGRITY_MATCH'
                : 'INTEGRITY_MISMATCH'
              : 'NOT_COMPARED'
          }}</strong></span
        >
      </div>
    </template>
  </section>
</template>

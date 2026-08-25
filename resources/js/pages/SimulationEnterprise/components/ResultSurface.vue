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

function getEventCategory(eventType: string): string {
  if (eventType.includes('PREPARED') || eventType.includes('READY')) return 'Runtime Preparation';
  if (eventType.includes('START') || eventType.includes('RUNNING')) return 'Lifecycle Transition';
  if (eventType.includes('OPERATION') || eventType.includes('CONTROL')) return 'Runtime Operation Applied';
  if (eventType.includes('STATE') || eventType.includes('TELEMETRY')) return 'State Mutation';
  if (eventType.includes('COMPLETED') || eventType.includes('FINAL')) return 'Finalization & Sealing';
  return 'Operational Event';
}
</script>

<template>
  <section class="sim-surface sim-canvas-surface" data-testid="result-center">
    <header class="sim-workbench-header">
      <div>
        <p class="sim-kicker">RESULTS · SEALED REPLAY DASHBOARD</p>
        <h1>النتائج وإعادة العرض</h1>
      </div>
    </header>

    <div v-if="!result" class="sim-empty"><strong>لا توجد نتيجة محددة</strong></div>

    <template v-else>
      <!-- Result Top Banner from Reference 05 -->
      <div class="sim-replay-header" data-testid="result-immutable-indicator">
        <div class="sim-replay-header__main">
          <div class="sim-replay-header__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sim-text-cyan"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/><path d="M12 12h.01M16 12h.01M8 12h.01"/></svg>
          </div>
          <div>
            <strong>{{ result.summary_ar || 'Web Application Breach & Response' }}</strong>
            <div class="sim-flex-row">
              <span class="sim-kicker">SEALED · IMMUTABLE · REVISION {{ result.result_revision }}</span>
              <code class="sim-technical">RUN-{{ result.run_id.slice(0, 8) }}</code>
            </div>
          </div>
        </div>

        <div class="sim-replay-head-facts">
          <LifecycleBadge value="COMPLETED" />
          <span class="sim-badge sim-badge--warning">PARTIAL</span>
          <span class="sim-date-badge">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            {{ result.sealed_at }}
          </span>
          <LifecycleBadge value="SEALED" sealed />
        </div>
      </div>

      <!-- Replay Player Bar with Controls from Reference 05 -->
      <div class="sim-replay-player-bar">
        <div class="sim-player-title">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sim-text-cyan"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <strong>الخط الزمني لإعادة التشغيل</strong>
        </div>

        <div class="sim-replay-controls" aria-label="تنقل محلي في سجل Replay">
          <button type="button" class="sim-player-btn sim-player-btn--primary" title="إيقاف / تشغيل Replay">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
            <span>Pause Replay</span>
          </button>
          <button
            type="button"
            class="sim-player-btn"
            :disabled="selectedIndex === 0"
            title="خطوة للخلف"
            @click="step(-1)"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="19 20 9 12 19 4 19 20"/><line x1="5" y1="19" x2="5" y2="5"/></svg>
            <span>Step Back</span>
          </button>
          <button
            type="button"
            class="sim-player-btn"
            :disabled="selectedIndex >= result.replay_timeline.length - 1"
            title="خطوة للأمام"
            @click="step(1)"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 4 15 12 5 20 5 4"/><line x1="19" y1="5" x2="19" y2="19"/></svg>
            <span>Step Forward</span>
          </button>
          <button type="button" class="sim-tool-icon-btn" title="إعادة التعيين" @click="selectedIndex = 0">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
          </button>
          <span class="sim-speed-tag">1x ▾</span>
          <span class="sim-marker-tag">Jump to Marker ▾</span>
        </div>

        <span class="sim-readonly-control">READ-ONLY INSPECTION · NO RUN MUTATION</span>
      </div>

      <!-- Main Replay Matrix (Side-by-Side Timeline & Detail Inspector) -->
      <div class="sim-replay-workbench" data-testid="result-replay-timeline">
        <!-- Left: Event Timeline Rail -->
        <section class="sim-replay-rail">
          <header class="sim-pane-heading">
            <div>
              <p class="sim-kicker">SEALED TIMELINE</p>
              <h2>الحقيقة التاريخية</h2>
            </div>
            <span class="sim-chip">{{ result.replay_timeline.length }} EVENTS</span>
          </header>

          <ol v-if="result.replay_timeline.length" class="sim-replay-list">
            <li
              v-for="(event, index) in result.replay_timeline"
              :key="event.sequence"
              class="sim-replay-list-item"
              data-testid="replay-event"
            >
              <button
                type="button"
                class="sim-replay-event-card"
                :class="{ 'is-selected': index === selectedIndex }"
                :aria-pressed="index === selectedIndex"
                @click="selectedIndex = index"
              >
                <time class="sim-technical">{{ event.occurred_at.slice(11, 19) || event.occurred_at }}</time>
                <span class="sim-replay-dot" :class="{ 'is-active': index === selectedIndex }" aria-hidden="true" />
                <div class="sim-replay-card-body">
                  <div class="sim-flex-between">
                    <strong class="sim-technical">{{ event.event_type }}</strong>
                    <small>SEQ {{ event.sequence }}</small>
                  </div>
                  <span v-if="index === selectedIndex" class="sim-active-indicator">Current inspection point</span>
                </div>
              </button>
            </li>
          </ol>
          <p v-else class="sim-muted">لا يحتوي Result المختوم على أحداث Replay.</p>
        </section>

        <!-- Right: Event Detail Box ("تفاصيل الحدث") from Reference 05 -->
        <section class="sim-replay-inspector">
          <header class="sim-pane-heading">
            <div class="sim-flex-row">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sim-text-purple"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
              <h2>تفاصيل الحدث</h2>
            </div>
            <span v-if="selectedEvent" class="sim-chip">FROZEN · SEQ {{ selectedEvent.sequence }}</span>
          </header>

          <template v-if="selectedEvent">
            <div class="sim-event-details-box">
              <div class="sim-detail-row">
                <dt>فئة الحدث</dt>
                <dd>{{ getEventCategory(selectedEvent.event_type) }}</dd>
              </div>
              <div class="sim-detail-row">
                <dt>نوع الحدث</dt>
                <dd class="sim-technical">{{ selectedEvent.event_type }}</dd>
              </div>
              <div class="sim-detail-row">
                <dt>المرجع</dt>
                <dd class="sim-technical">EVT-RUN-{{ selectedEvent.sequence.toString().padStart(5, '0') }}</dd>
              </div>
            </div>

            <!-- Additional Payload Fields -->
            <dl v-if="selectedEvent.payload && Object.keys(selectedEvent.payload).length" class="sim-operational-facts">
              <div v-for="(value, key) in selectedEvent.payload" :key="String(key)">
                <dt class="sim-technical">{{ key }}</dt>
                <dd class="sim-technical">{{ displayValue(value) }}</dd>
              </div>
            </dl>
            <p v-else class="sim-muted" style="padding: 1rem;">لا توجد تفاصيل إضافية للحمولة (Payload) في هذا الحدث.</p>
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

      <!-- State at this Point ("الحالة في هذه النقطة") from Reference 05 -->
      <section class="sim-point-state-section">
        <header class="sim-point-state-header">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sim-text-cyan"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
          <h3>الحالة في هذه النقطة</h3>
        </header>

        <div class="sim-point-state-grid">
          <div class="sim-empty-slot" style="padding: 1.5rem; text-align: center; border: 1px dashed var(--sim-border); border-radius: 4px; width: 100%;">
            <span class="sim-muted">لا تتوفر حالة العقد (Node State) في هذا الحدث التاريخي</span>
          </div>
        </div>
      </section>

      <!-- Bottom Truth Band -->
      <div class="sim-result-truth-band">
        <span>
          <small>RESULT DIGEST</small>
          <code class="sim-technical">{{ result.result_digest }}</code>
        </span>
        <span>
          <small>ARTIFACTS</small>
          <strong>{{ result.artifacts.length }}</strong>
        </span>
        <span>
          <small>REPLAY COMPARE</small>
          <strong class="sim-technical">{{
            result.replay_compare
              ? result.replay_compare.integrity_match
                ? 'INTEGRITY_MATCH'
                : 'INTEGRITY_MISMATCH'
              : 'NOT_COMPARED'
          }}</strong>
        </span>
      </div>
    </template>
  </section>
</template>

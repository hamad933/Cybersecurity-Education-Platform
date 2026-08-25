<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import LifecycleBadge from './LifecycleBadge.vue';
import { runTypeLabel } from '../formatters';
import { displayValue } from '../projections';
import type { EventItem, RunItem } from '../types';

const props = defineProps<{ run: RunItem | null }>();

const lifecycleSteps = ['PREPARED', 'READY', 'RUNNING', 'PAUSED', 'COMPLETED'];
const selectedSequence = ref<number | null>(null);
const selectedEvent = computed<EventItem | null>(
  () => props.run?.events.find((event) => event.sequence === selectedSequence.value) ?? null,
);

watch(
  () => props.run,
  (run) => {
    selectedSequence.value = run?.events.at(-1)?.sequence ?? null;
  },
  { immediate: true },
);

function reached(run: RunItem, step: string): boolean {
  const current = lifecycleSteps.indexOf(run.lifecycle);
  const target = lifecycleSteps.indexOf(step);
  if (['STOPPED', 'FAILED'].includes(run.lifecycle)) return target <= Math.max(current, 2);
  return current >= target;
}
</script>

<template>
  <section class="sim-surface sim-canvas-surface" data-testid="run-center">
    <header class="sim-workbench-header">
      <div>
        <p class="sim-kicker">RUNS · OPERATIONS</p>
        <h1>{{ run?.definition_title_ar ?? 'التشغيلات' }}</h1>
      </div>
    </header>

    <div v-if="!run" class="sim-empty"><strong>لا يوجد تشغيل محدد</strong></div>

    <template v-else>
      <div class="sim-operation-header">
        <div>
          <span class="sim-chip">{{ runTypeLabel(run.run_type) }}</span>
          <code class="sim-technical">{{ run.id }}</code>
        </div>
        <LifecycleBadge :value="run.lifecycle" />
        <div class="sim-runtime-summary">
          <span
            ><small>ENGINE</small
            ><strong class="sim-technical">{{
              run.runtime_state.engine ?? 'INTERNAL'
            }}</strong></span
          >
          <span
            ><small>EVENTS</small><strong>{{ run.events.length }}</strong></span
          >
          <span
            ><small>OPERATIONS</small><strong>{{ run.operations.length }}</strong></span
          >
        </div>
      </div>

      <div class="sim-lifecycle-track sim-lifecycle-track--compact" aria-label="دورة حياة التشغيل">
        <div
          v-for="step in lifecycleSteps"
          :key="step"
          :class="{ 'is-reached': reached(run, step) }"
        >
          <span aria-hidden="true" /><small>{{ step }}</small>
        </div>
      </div>

      <div class="sim-operations-workbench" data-testid="run-operational-truth">
        <section class="sim-event-stream">
          <header class="sim-pane-heading">
            <div>
              <p class="sim-kicker">EVENT STREAM</p>
              <h2>التسلسل التشغيلي</h2>
            </div>
            <span class="sim-chip">APPEND-ONLY</span>
          </header>
          <ol v-if="run.events.length">
            <li v-for="event in run.events" :key="event.sequence">
              <button
                type="button"
                :class="{ 'is-selected': event.sequence === selectedSequence }"
                :aria-pressed="event.sequence === selectedSequence"
                data-testid="run-event"
                @click="selectedSequence = event.sequence"
              >
                <span class="sim-event-sequence">{{ event.sequence }}</span>
                <span
                  ><strong class="sim-technical">{{ event.event_type }}</strong
                  ><small class="sim-technical">{{ event.occurred_at }}</small></span
                >
              </button>
            </li>
          </ol>
          <p v-else class="sim-muted">لا توجد أحداث تشغيل مسجلة.</p>
        </section>

        <section class="sim-event-inspector">
          <header class="sim-pane-heading">
            <div>
              <p class="sim-kicker">SELECTED EVENT</p>
              <h2>حقيقة الحدث</h2>
            </div>
            <span v-if="selectedEvent" class="sim-chip">SEQ {{ selectedEvent.sequence }}</span>
          </header>
          <template v-if="selectedEvent">
            <h3 class="sim-technical">{{ selectedEvent.event_type }}</h3>
            <dl class="sim-operational-facts">
              <div>
                <dt>Occurred at</dt>
                <dd class="sim-technical">{{ selectedEvent.occurred_at }}</dd>
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
          <p v-else class="sim-muted">اختر حدثًا من التسلسل.</p>

          <div class="sim-operation-list">
            <div class="sim-pane-subheading">
              <strong>Applied operations</strong><span>{{ run.operations.length }}</span>
            </div>
            <article
              v-for="operation in run.operations"
              :key="operation.id"
              data-testid="run-operation"
            >
              <span class="sim-node-marker" aria-hidden="true" />
              <div>
                <strong class="sim-technical">{{ operation.verb }}</strong
                ><small class="sim-technical"
                  >{{ operation.target }} · {{ operation.operation_key }}</small
                >
              </div>
            </article>
            <p v-if="!run.operations.length" class="sim-muted">لا توجد عمليات مطبقة.</p>
          </div>
        </section>
      </div>

      <section class="sim-runtime-band" data-testid="run-runtime-telemetry">
        <header class="sim-pane-heading">
          <div>
            <p class="sim-kicker">RUNTIME STATE · TELEMETRY</p>
            <h2>حقيقة الآلة الحالية</h2>
          </div>
          <span class="sim-chip">{{ run.provenance }}</span>
        </header>
        <div class="sim-telemetry sim-telemetry--center">
          <div v-for="(value, key) in run.runtime_state.telemetry ?? {}" :key="String(key)">
            <small class="sim-technical">{{ key }}</small
            ><strong class="sim-technical">{{ displayValue(value) }}</strong>
          </div>
          <p v-if="!Object.keys(run.runtime_state.telemetry ?? {}).length" class="sim-muted">
            لا توجد Telemetry محفوظة بعد.
          </p>
        </div>
      </section>

      <div class="sim-runtime-lineage">
        <section data-testid="run-snapshots">
          <header>
            <strong>Runtime Snapshots</strong><span>{{ run.snapshots.length }}</span>
          </header>
          <article v-for="snapshot in run.snapshots" :key="snapshot.id">
            <span class="sim-runtime-sequence">S{{ snapshot.sequence }}</span>
            <div>
              <strong class="sim-technical">{{ snapshot.snapshot_kind }}</strong
              ><small class="sim-technical"
                >EVENT {{ snapshot.event_sequence }} · {{ snapshot.captured_at }}</small
              >
            </div>
          </article>
        </section>
        <section data-testid="run-checkpoints">
          <header>
            <strong>Prepared Checkpoints</strong><span>{{ run.checkpoints.length }}</span>
          </header>
          <article v-for="checkpoint in run.checkpoints" :key="checkpoint.id">
            <span class="sim-runtime-sequence">C{{ checkpoint.sequence }}</span>
            <div>
              <strong>{{ checkpoint.restorable ? 'RESTORABLE' : 'LOCKED' }}</strong
              ><small class="sim-technical">SOURCE {{ checkpoint.source_snapshot_id }}</small>
            </div>
          </article>
        </section>
      </div>
    </template>
  </section>
</template>

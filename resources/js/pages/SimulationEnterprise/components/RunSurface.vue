<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import LifecycleBadge from './LifecycleBadge.vue';
import { displayValue } from '../projections';
import type {
  EventItem,
  RunItem,
  RunPreflightDefinition,
  RunPreflightWorkspace,
  RunWorkspaceMode,
} from '../types';

const props = defineProps<{
  run: RunItem | null;
  mode: RunWorkspaceMode;
  preflightWorkspace: RunPreflightWorkspace | null;
  preflight: RunPreflightDefinition | null;
  preflightType: 'scenario' | 'standalone-lab';
  pending: boolean;
}>();
const emit = defineEmits<{
  selectPreflight: [payload: { type: 'scenario' | 'standalone-lab'; definitionId: string }];
  prepareScenario: [
    payload: { definition_id: string; baseline_id: string; seed: number; mode: string },
  ];
  prepareLab: [payload: { definition_id: string; seed: number; mode: string }];
}>();

const seed = ref(props.preflightWorkspace?.default_seed ?? 20260814);
const executionMode = ref(props.preflightWorkspace?.execution_modes[0] ?? 'GUIDED');
const baselineId = ref('');

const selectedTarget = computed(
  () =>
    props.preflight?.targets?.find((target) => target.baseline_id === baselineId.value) ??
    props.preflight?.target ??
    null,
);
const availableCapabilities = computed(
  () => selectedTarget.value?.capabilities ?? props.preflight?.available_capabilities ?? [],
);
const canPrepare = computed(
  () =>
    props.preflight?.status === 'READY' &&
    (props.preflight.run_type === 'Standalone Lab Run' || baselineId.value !== ''),
);

watch(
  () => props.preflight,
  (preflight) => {
    baselineId.value = preflight?.targets?.[0]?.baseline_id ?? preflight?.target?.baseline_id ?? '';
  },
  { immediate: true },
);

const lifecycleSteps = computed(() => {
  const terminalState =
    props.run && ['COMPLETED', 'STOPPED', 'FAILED'].includes(props.run.lifecycle)
      ? props.run.lifecycle
      : 'COMPLETED / STOPPED / FAILED';

  return ['PREPARING', 'READY', 'RUNNING', 'PAUSED', terminalState];
});
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
  return run.lifecycle === step;
}

function selectType(type: 'scenario' | 'standalone-lab'): void {
  const definitions =
    type === 'scenario'
      ? props.preflightWorkspace?.scenario_definitions
      : props.preflightWorkspace?.lab_definitions;
  const definition = definitions?.[0];
  if (definition) emit('selectPreflight', { type, definitionId: definition.definition_id });
}

function prepare(): void {
  if (!props.preflight || !canPrepare.value || props.pending) return;
  if (props.preflight.run_type === 'Scenario Run') {
    emit('prepareScenario', {
      definition_id: props.preflight.definition_id,
      baseline_id: baselineId.value,
      seed: seed.value,
      mode: executionMode.value,
    });
  } else {
    emit('prepareLab', {
      definition_id: props.preflight.definition_id,
      seed: seed.value,
      mode: executionMode.value,
    });
  }
}
</script>

<template>
  <section class="sim-surface sim-canvas-surface" data-testid="run-center">
    <header class="sim-workbench-header">
      <div>
        <p class="sim-kicker">
          {{
            mode === 'preflight'
              ? 'RUNS · SERVER-OWNED PREPARATION PREFLIGHT'
              : 'RUNS · ACTIVE OPERATIONS'
          }}
        </p>
        <h1>
          {{
            mode === 'preflight'
              ? (preflight?.definition_title_ar ?? 'تهيئة تشغيل جديد')
              : (run?.definition_title_ar ?? 'التشغيلات')
          }}
        </h1>
      </div>
    </header>

    <template v-if="mode === 'preflight'">
      <div class="sim-preflight-type-switch" role="tablist" aria-label="نوع التشغيل">
        <button
          type="button"
          role="tab"
          :aria-selected="preflightType === 'scenario'"
          :class="{ 'is-active': preflightType === 'scenario' }"
          @click="selectType('scenario')"
        >
          Scenario Run
        </button>
        <button
          type="button"
          role="tab"
          :aria-selected="preflightType === 'standalone-lab'"
          :class="{ 'is-active': preflightType === 'standalone-lab' }"
          @click="selectType('standalone-lab')"
        >
          Standalone Lab Run
        </button>
      </div>

      <div
        v-if="!preflightWorkspace || preflightWorkspace.status === 'UNAVAILABLE'"
        class="sim-state-panel sim-state-panel--unavailable"
        data-testid="run-preflight-unavailable"
      >
        <strong>UNAVAILABLE</strong>
        <p>حقيقة Run Preflight غير متاحة من الخادم.</p>
      </div>
      <div
        v-else-if="preflightWorkspace.status === 'EMPTY' || !preflight"
        class="sim-state-panel"
        data-testid="run-preflight-empty"
      >
        <strong>EMPTY</strong>
        <p>لا توجد تعريفات قابلة للفحص في هذا النطاق.</p>
      </div>

      <div v-else class="sim-preflight-workbench" data-testid="run-preflight">
        <header class="sim-preflight-identity">
          <div>
            <span class="sim-chip" :class="`is-${preflight.status.toLowerCase()}`">
              {{ preflight.status }}
            </span>
            <strong>{{ preflight.run_type }}</strong>
            <small class="sim-technical">{{ preflight.execution_model }}</small>
          </div>
          <div class="sim-provenance-strip">
            <span>{{ preflight.provenance ?? 'UNAVAILABLE' }}</span>
            <span :class="{ 'is-warning': preflight.source_fixture }">
              {{ preflight.source_fixture ? 'SOURCE FIXTURE' : 'NON-FIXTURE' }}
            </span>
          </div>
        </header>

        <div class="sim-preflight-grid">
          <section class="sim-preflight-card">
            <p class="sim-kicker">PINNED DEFINITION</p>
            <h2>{{ preflight.definition_title_ar }}</h2>
            <dl class="sim-operational-facts">
              <div>
                <dt>Definition</dt>
                <dd class="sim-technical">{{ preflight.definition_id }}</dd>
              </div>
              <div>
                <dt>Revision</dt>
                <dd class="sim-technical">{{ preflight.definition_revision ?? 'UNAVAILABLE' }}</dd>
              </div>
              <div>
                <dt>Definition digest</dt>
                <dd class="sim-technical sim-wrap">
                  {{ preflight.definition_digest ?? 'UNAVAILABLE' }}
                </dd>
              </div>
              <div>
                <dt>Environment digest</dt>
                <dd class="sim-technical sim-wrap">
                  {{ preflight.environment_contract_digest ?? 'UNAVAILABLE' }}
                </dd>
              </div>
            </dl>
          </section>

          <section class="sim-preflight-card" data-testid="preflight-capabilities">
            <p class="sim-kicker">CAPABILITY COMPATIBILITY</p>
            <h2>المطلوب مقابل المتاح</h2>
            <div class="sim-capability-matrix">
              <div
                v-for="capability in preflight.required_capabilities ?? []"
                :key="capability"
                :class="{ 'is-missing': !availableCapabilities.includes(capability) }"
              >
                <code>{{ capability }}</code>
                <span>
                  {{ availableCapabilities.includes(capability) ? 'AVAILABLE' : 'MISSING' }}
                </span>
              </div>
              <p v-if="!(preflight.required_capabilities ?? []).length" class="sim-muted">
                UNAVAILABLE — لم يصدر الخادم قائمة قدرات مطلوبة.
              </p>
            </div>
          </section>
        </div>

        <section class="sim-preflight-target">
          <div>
            <p class="sim-kicker">EXACT EXECUTION TARGET</p>
            <h2>Baseline / Environment</h2>
          </div>
          <label v-if="preflight.run_type === 'Scenario Run'">
            <span>Compatible target</span>
            <select v-model="baselineId" :disabled="pending || preflight.status !== 'READY'">
              <option
                v-for="target in preflight.targets ?? []"
                :key="target.baseline_id"
                :value="target.baseline_id"
              >
                {{ target.enterprise_name_ar }} / {{ target.digital_twin_name_ar }} · B{{
                  target.baseline_revision
                }}
              </option>
            </select>
          </label>
          <dl v-if="selectedTarget" class="sim-operational-facts">
            <div>
              <dt>Baseline</dt>
              <dd class="sim-technical">{{ selectedTarget.baseline_id }}</dd>
            </div>
            <div>
              <dt>Twin revision</dt>
              <dd class="sim-technical">{{ selectedTarget.digital_twin_revision_id }}</dd>
            </div>
            <div>
              <dt>Baseline digest</dt>
              <dd class="sim-technical sim-wrap">{{ selectedTarget.baseline_digest }}</dd>
            </div>
          </dl>
          <p v-else class="sim-muted">UNAVAILABLE — لا يوجد هدف متوافق صادر من الخادم.</p>
        </section>

        <div
          v-if="preflight.blocking_reason"
          class="sim-state-panel sim-state-panel--error"
          data-testid="preflight-blocking-reason"
        >
          <strong>BLOCKED</strong>
          <code class="sim-technical">{{ preflight.blocking_reason }}</code>
        </div>

        <form class="sim-preflight-submit" data-testid="preflight-submit" @submit.prevent="prepare">
          <label>
            <span>Deterministic seed</span>
            <input v-model.number="seed" type="number" min="0" :disabled="pending" />
          </label>
          <label>
            <span>Execution mode / policy</span>
            <select v-model="executionMode" :disabled="pending">
              <option
                v-for="policy in preflightWorkspace.execution_modes"
                :key="policy"
                :value="policy"
              >
                {{ policy }}
              </option>
            </select>
          </label>
          <span class="sim-target-lock">
            <small>Input digest</small>
            <code>UNAVAILABLE UNTIL RUN CREATION</code>
          </span>
          <button type="submit" class="sim-button" :disabled="pending || !canPrepare">
            إنشاء PREPARING Run
          </button>
        </form>
      </div>
    </template>

    <div v-else-if="!run" class="sim-empty"><strong>لا يوجد تشغيل محدد</strong></div>

    <template v-else>
      <!-- Top Operational Status Banner matching Reference 04 -->
      <div class="sim-ops-banner">
        <div class="sim-ops-banner__primary">
          <div class="sim-ops-banner__icon">
            <svg
              width="22"
              height="22"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              class="sim-text-cyan"
            >
              <rect x="2" y="3" width="20" height="14" rx="2" />
              <line x1="8" y1="21" x2="16" y2="21" />
              <line x1="12" y1="17" x2="12" y2="21" />
              <polyline points="7 10 10 13 17 6" />
            </svg>
          </div>
          <div>
            <strong>{{ run.definition_title_ar }}</strong>
            <code class="sim-technical">{{ run.id }}</code>
          </div>
          <LifecycleBadge :value="run.lifecycle" />
        </div>

        <div
          v-if="
            run.runtime_state?.telemetry?.current_phase !== undefined ||
            run.runtime_state?.telemetry?.operational_role !== undefined ||
            run.runtime_state?.telemetry?.current_task !== undefined
          "
          class="sim-ops-banner__metrics"
        >
          <div
            v-if="run.runtime_state?.telemetry?.current_phase !== undefined"
            class="sim-metric-block"
          >
            <small>CURRENT PHASE</small>
            <strong class="sim-text-cyan">{{ run.runtime_state.telemetry.current_phase }}</strong>
          </div>
          <div
            v-if="
              run.runtime_state?.telemetry?.current_phase !== undefined &&
              run.runtime_state?.telemetry?.operational_role !== undefined
            "
            class="sim-metric-divider"
          />
          <div
            v-if="run.runtime_state?.telemetry?.operational_role !== undefined"
            class="sim-metric-block"
          >
            <small>OPERATIONAL ROLE</small>
            <strong>{{ run.runtime_state.telemetry.operational_role }}</strong>
          </div>
          <div
            v-if="
              run.runtime_state?.telemetry?.current_task !== undefined &&
              (run.runtime_state?.telemetry?.current_phase !== undefined ||
                run.runtime_state?.telemetry?.operational_role !== undefined)
            "
            class="sim-metric-divider"
          />
          <div
            v-if="run.runtime_state?.telemetry?.current_task !== undefined"
            class="sim-metric-block"
          >
            <small>CURRENT TASK</small>
            <strong>{{ run.runtime_state.telemetry.current_task }}</strong>
          </div>
        </div>
      </div>

      <!-- Lifecycle Progress Track -->
      <div class="sim-lifecycle-track sim-lifecycle-track--compact" aria-label="دورة حياة التشغيل">
        <div
          v-for="step in lifecycleSteps"
          :key="step"
          :class="{ 'is-reached': reached(run, step) }"
        >
          <span aria-hidden="true" />
          <small>{{ step }}</small>
        </div>
      </div>

      <div
        class="sim-operations-workbench"
        data-testid="run-operational-truth"
        aria-label="مساحة أحداث التشغيل الوحيدة"
      >
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
                  ><strong class="sim-technical">{{ event.event_type }}</strong>
                  <small class="sim-technical">{{ event.occurred_at }}</small></span
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
                <strong class="sim-technical">{{ operation.verb }}</strong>
                <small class="sim-technical"
                  >{{ operation.target }} · {{ operation.operation_key }}</small
                >
              </div>
            </article>
            <p v-if="!run.operations.length" class="sim-muted">لا توجد عمليات مطبقة.</p>
          </div>
        </section>
      </div>

      <!-- Runtime State & Telemetry Band -->
      <section class="sim-runtime-band" data-testid="run-runtime-telemetry">
        <header class="sim-pane-heading">
          <div>
            <p class="sim-kicker">RUNTIME STATE · OBSERVED TELEMETRY</p>
            <h2>الحالة التشغيلية المرصودة</h2>
          </div>
          <span class="sim-chip">{{ run.provenance }}</span>
        </header>
        <div class="sim-telemetry sim-telemetry--center">
          <div v-for="(value, key) in run.runtime_state.telemetry ?? {}" :key="String(key)">
            <small class="sim-technical">{{ key }}</small>
            <strong class="sim-technical">{{ displayValue(value) }}</strong>
          </div>
          <p v-if="!Object.keys(run.runtime_state.telemetry ?? {}).length" class="sim-muted">
            لا توجد Telemetry محفوظة بعد.
          </p>
        </div>
      </section>

      <!-- Lineage: Snapshots & Checkpoints -->
      <div class="sim-runtime-lineage">
        <section data-testid="run-snapshots">
          <header>
            <strong>Runtime Snapshots</strong>
            <span>{{ run.snapshots.length }}</span>
          </header>
          <article v-for="snapshot in run.snapshots" :key="snapshot.id">
            <span class="sim-runtime-sequence">S{{ snapshot.sequence }}</span>
            <div>
              <strong class="sim-technical">{{ snapshot.snapshot_kind }}</strong>
              <small class="sim-technical"
                >EVENT {{ snapshot.event_sequence }} · {{ snapshot.captured_at }}</small
              >
            </div>
          </article>
        </section>
        <section data-testid="run-checkpoints">
          <header>
            <strong>Prepared Checkpoints</strong>
            <span>{{ run.checkpoints.length }}</span>
          </header>
          <article v-for="checkpoint in run.checkpoints" :key="checkpoint.id">
            <span class="sim-runtime-sequence">C{{ checkpoint.sequence }}</span>
            <div>
              <strong>{{ checkpoint.restorable ? 'RESTORABLE' : 'LOCKED' }}</strong>
              <small class="sim-technical">SOURCE {{ checkpoint.source_snapshot_id }}</small>
            </div>
          </article>
        </section>
      </div>
    </template>
  </section>
</template>

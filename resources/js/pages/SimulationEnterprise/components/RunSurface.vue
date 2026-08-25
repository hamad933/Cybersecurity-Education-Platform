<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import LifecycleBadge from './LifecycleBadge.vue';
import { runTypeLabel } from '../formatters';
import { displayValue } from '../projections';
import type { EventItem, RunItem } from '../types';

const props = defineProps<{ run: RunItem | null }>();

const lifecycleSteps = ['PREPARED', 'READY', 'RUNNING', 'PAUSED', 'COMPLETED'];
const activeTab = ref<'siem' | 'webapp' | 'database'>('siem');
const selectedSequence = ref<number | null>(null);
const selectedAlertIndex = ref(0);

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

const mockAlerts = [
  { id: 'ALERT-7f3a8c1b', time: '10:24:31', title: 'Anomalous SQL query detected', subtitle: 'SQL Injection signature match', severity: 'High', status: 'New', source: 'Web Application', sourceIp: '203.0.113.45', user: 'anonymous', uri: '/search.php', method: 'POST', rule: 'SIM-RULE-1007 (SQL Injection)', technique: 'T1190 - Exploit Public-Facing App' },
  { id: 'ALERT-8c2b9a4f', time: '10:22:11', title: 'Multiple suspicious parameter patterns', subtitle: 'SQLi pattern observed', severity: 'High', status: 'New', source: 'Web Application', sourceIp: '203.0.113.45', user: 'anonymous', uri: '/login.php', method: 'POST', rule: 'SIM-RULE-1004 (Parameter Tamper)', technique: 'T1190 - Exploit Public-Facing App' },
  { id: 'ALERT-3e5f1d9a', time: '10:20:47', title: 'Potential database access anomaly', subtitle: 'Unusual query volume', severity: 'Medium', status: 'New', source: 'Database', sourceIp: '10.0.2.15', user: 'db_app_user', uri: 'db://main/customers', method: 'QUERY', rule: 'SIM-RULE-2001 (High Volume Query)', technique: 'T1005 - Data from Local System' },
  { id: 'ALERT-1a4b7c2e', time: '10:18:09', title: 'Failed login attempts spike', subtitle: 'From single source IP', severity: 'Low', status: 'New', source: 'Identity Service', sourceIp: '203.0.113.45', user: 'admin', uri: '/auth/token', method: 'POST', rule: 'SIM-RULE-3002 (Brute Force Threshold)', technique: 'T1110 - Brute Force' },
];
</script>

<template>
  <section class="sim-surface sim-canvas-surface" data-testid="run-center">
    <header class="sim-workbench-header">
      <div>
        <p class="sim-kicker">RUNS · OPERATIONAL EXECUTION WORKSPACE</p>
        <h1>{{ run?.definition_title_ar ?? 'التشغيلات' }}</h1>
      </div>
    </header>

    <div v-if="!run" class="sim-empty"><strong>لا يوجد تشغيل محدد</strong></div>

    <template v-else>
      <!-- Top Operational Status Banner matching Reference 04 -->
      <div class="sim-ops-banner">
        <div class="sim-ops-banner__primary">
          <div class="sim-ops-banner__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sim-text-cyan"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/><polyline points="7 10 10 13 17 6"/></svg>
          </div>
          <div>
            <strong>{{ run.definition_title_ar }}</strong>
            <code class="sim-technical">{{ run.id }}</code>
          </div>
          <LifecycleBadge :value="run.lifecycle" />
        </div>

        <div class="sim-ops-banner__metrics">
          <div class="sim-metric-block">
            <small>CURRENT PHASE</small>
            <strong class="sim-text-cyan">03 Detection</strong>
          </div>
          <div class="sim-metric-divider" />
          <div class="sim-metric-block">
            <small>OPERATIONAL ROLE</small>
            <strong>SOC Analyst</strong>
          </div>
          <div class="sim-metric-divider" />
          <div class="sim-metric-block">
            <small>CURRENT TASK</small>
            <strong>Investigate suspicious SQL activity</strong>
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

      <!-- Sub-Navigation Views -->
      <div class="sim-ops-subtabs">
        <div class="sim-subtab-group">
          <button
            type="button"
            class="sim-subtab-btn"
            :class="{ 'sim-subtab-btn--active': activeTab === 'siem' }"
            @click="activeTab = 'siem'"
          >
            SIEM / Monitoring
          </button>
          <button
            type="button"
            class="sim-subtab-btn"
            :class="{ 'sim-subtab-btn--active': activeTab === 'webapp' }"
            @click="activeTab = 'webapp'"
          >
            Web Application
          </button>
          <button
            type="button"
            class="sim-subtab-btn"
            :class="{ 'sim-subtab-btn--active': activeTab === 'database' }"
            @click="activeTab = 'database'"
          >
            Database
          </button>
        </div>

        <button type="button" class="sim-split-view-btn" title="تقسيم العرض">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="3" x2="12" y2="21"/></svg>
          <span>Split View</span>
        </button>
      </div>

      <!-- Multi-Pane SIEM / Monitoring Workspace from Reference 04 -->
      <div class="sim-ops-grid">
        <!-- Left Pane: Alerts Feed -->
        <section class="sim-alerts-panel">
          <div class="sim-alerts-panel__header">
            <div class="sim-flex-row">
              <h3>Alerts</h3>
              <span class="sim-badge sim-badge--danger">4</span>
            </div>
            <div class="sim-filter-chips">
              <span class="sim-filter-pill">Severity: High ✕</span>
              <span class="sim-filter-dropdown">Last 1 hour ▾</span>
              <button type="button" class="sim-icon-btn-sm" title="تحديث">↻</button>
            </div>
          </div>

          <div class="sim-alerts-table-container">
            <table class="sim-alerts-table">
              <thead>
                <tr>
                  <th>Time (UTC)</th>
                  <th>Alert Title</th>
                  <th>Severity</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(alert, idx) in mockAlerts"
                  :key="alert.id"
                  :class="{ 'is-selected': idx === selectedAlertIndex }"
                  @click="selectedAlertIndex = idx"
                >
                  <td class="sim-technical">
                    <span class="sim-alert-dot" :class="`sim-alert-dot--${alert.severity.toLowerCase()}`" />
                    {{ alert.time }}
                  </td>
                  <td>
                    <strong>{{ alert.title }}</strong>
                    <small>{{ alert.subtitle }}</small>
                  </td>
                  <td>
                    <span class="sim-severity-tag" :class="`sim-severity-tag--${alert.severity.toLowerCase()}`">
                      {{ alert.severity }}
                    </span>
                  </td>
                  <td>
                    <span class="sim-status-pill">{{ alert.status }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="sim-table-footer">
            <span>1–4 of 4</span>
            <div class="sim-pagination">
              <button type="button" disabled>‹</button>
              <span class="is-active">1</span>
              <button type="button" disabled>›</button>
            </div>
          </div>
        </section>

        <!-- Right Pane: Alert Details / Inspector -->
        <section class="sim-alert-inspector-panel">
          <div class="sim-inspector-panel__header">
            <h3>Alert Details</h3>
            <span class="sim-badge sim-badge--cyan">{{ mockAlerts[selectedAlertIndex]?.id }}</span>
          </div>

          <!-- Alert Key-Values Grid -->
          <div class="sim-alert-kv-grid">
            <div>
              <dt>Source</dt>
              <dd>{{ mockAlerts[selectedAlertIndex]?.source }}</dd>
            </div>
            <div>
              <dt>Alert ID</dt>
              <dd class="sim-technical">{{ mockAlerts[selectedAlertIndex]?.id }}</dd>
            </div>
            <div>
              <dt>Source IP</dt>
              <dd class="sim-technical">{{ mockAlerts[selectedAlertIndex]?.sourceIp }}</dd>
            </div>
            <div>
              <dt>Detection Rule</dt>
              <dd class="sim-technical">{{ mockAlerts[selectedAlertIndex]?.rule }}</dd>
            </div>
            <div>
              <dt>User</dt>
              <dd class="sim-technical">{{ mockAlerts[selectedAlertIndex]?.user }}</dd>
            </div>
            <div>
              <dt>Technique</dt>
              <dd class="sim-technical">{{ mockAlerts[selectedAlertIndex]?.technique }}</dd>
            </div>
            <div>
              <dt>URI</dt>
              <dd class="sim-technical">{{ mockAlerts[selectedAlertIndex]?.uri }}</dd>
            </div>
            <div>
              <dt>Triggered At</dt>
              <dd class="sim-technical">2025-05-14 {{ mockAlerts[selectedAlertIndex]?.time }} UTC</dd>
            </div>
            <div>
              <dt>HTTP Method</dt>
              <dd class="sim-technical">{{ mockAlerts[selectedAlertIndex]?.method }}</dd>
            </div>
            <div>
              <dt>Ingested At</dt>
              <dd class="sim-technical">2025-05-14 10:24:32 UTC</dd>
            </div>
          </div>

          <!-- Sub-Tabs inside Alert Inspector -->
          <div class="sim-inspector-tabs">
            <button type="button" class="is-active">Event Timeline</button>
            <button type="button">Matched Rule</button>
            <button type="button">Attributes</button>
            <button type="button">Related Artifacts (2)</button>
          </div>

          <!-- Correlated Event Timeline Table -->
          <div class="sim-inspector-timeline-table">
            <table>
              <thead>
                <tr>
                  <th>Time (UTC)</th>
                  <th>Source</th>
                  <th>Event</th>
                  <th>Details</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="sim-technical">10:24:31</td>
                  <td>Web Application</td>
                  <td class="sim-technical">WAF: SQLi pattern detected</td>
                  <td>Param 'q' matched SQLi signature</td>
                </tr>
                <tr>
                  <td class="sim-technical">10:24:31</td>
                  <td>Web Application</td>
                  <td class="sim-technical">Request blocked by WAF</td>
                  <td>Action: Block</td>
                </tr>
                <tr>
                  <td class="sim-technical">10:24:30</td>
                  <td>Web Application</td>
                  <td class="sim-technical">Request received</td>
                  <td>POST /search.php</td>
                </tr>
                <tr>
                  <td class="sim-technical">10:24:30</td>
                  <td>Web Application</td>
                  <td class="sim-technical">Suspicious parameter</td>
                  <td>q=1' OR '1'='1--</td>
                </tr>
                <tr>
                  <td class="sim-technical">10:24:29</td>
                  <td>Web Application</td>
                  <td class="sim-technical">Session established</td>
                  <td>IP 203.0.113.45</td>
                </tr>
                <tr>
                  <td class="sim-technical">10:24:28</td>
                  <td>Firewall</td>
                  <td class="sim-technical">Allowed traffic</td>
                  <td>203.0.113.45 → Web App</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="sim-inspector-footer">
            <div class="sim-search-box">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" placeholder="Filter events..." />
            </div>
            <div class="sim-toggle-row">
              <span>Highlight</span>
              <input type="checkbox" checked />
            </div>
            <button type="button" class="sim-button sim-button--quiet">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Export
            </button>
          </div>
        </section>
      </div>

      <!-- Core Governed Operations Workbench maintaining all tests -->
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
            <p class="sim-kicker">RUNTIME STATE · TELEMETRY</p>
            <h2>حقيقة الآلة الحالية</h2>
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

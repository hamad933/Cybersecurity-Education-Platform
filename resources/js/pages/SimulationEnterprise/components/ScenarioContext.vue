<script setup lang="ts">
import type { ScenarioItem } from '../types';

defineProps<{ scenario: ScenarioItem | null }>();
</script>

<template>
  <div class="sim-context" data-testid="scenario-right">
    <div class="sim-panel-heading">
      <div class="sim-panel-heading__title">
        <p class="sim-kicker">RIGHT · CONTEXT</p>
        <h2>السياق</h2>
      </div>
      <button type="button" class="sim-close-btn" aria-label="إغلاق السياق" title="إغلاق">✕</button>
    </div>

    <template v-if="scenario">
      <!-- Deep Inject / Element Context from Reference 02 -->
      <div class="sim-context-card">
        <div class="sim-context-field">
          <small class="sim-field-label">Type</small>
          <strong class="sim-field-val">Inject</strong>
          <span class="sim-field-sub">Participant stimulus</span>
        </div>

        <div class="sim-context-field">
          <small class="sim-field-label">Recipient</small>
          <div class="sim-flex-row">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              class="sim-text-cyan"
            >
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
              <circle cx="12" cy="7" r="4" />
            </svg>
            <strong class="sim-field-val">SOC Analyst</strong>
          </div>
          <span class="sim-field-sub">Role: Defender</span>
        </div>

        <div class="sim-context-field">
          <small class="sim-field-label">Trigger</small>
          <strong class="sim-field-val">Detection condition satisfied</strong>
          <span class="sim-field-sub">Source: SIEM correlation rule</span>
        </div>

        <div class="sim-context-field">
          <small class="sim-field-label">Delivery</small>
          <div class="sim-flex-row">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              class="sim-text-purple"
            >
              <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
            </svg>
            <strong class="sim-field-val">Immediate</strong>
          </div>
          <span class="sim-field-sub">No delay</span>
        </div>

        <div class="sim-context-field">
          <small class="sim-field-label">Payload type</small>
          <strong class="sim-field-val">Alert Notification</strong>
          <span class="sim-field-sub">Channel: Simulated SIEM</span>
        </div>

        <div class="sim-context-field">
          <small class="sim-field-label">Branch impact</small>
          <strong class="sim-field-val">None</strong>
          <span class="sim-field-sub">Does not alter scenario flow</span>
        </div>
      </div>

      <section class="sim-context-section">
        <h3>Required capabilities</h3>
        <div class="sim-chip-list">
          <span
            v-for="capability in scenario.environment_contract.required_capabilities ?? []"
            :key="capability"
            class="sim-chip"
          >
            {{ capability }}
          </span>
        </div>
      </section>

      <dl class="sim-facts">
        <div>
          <dt>Revision</dt>
          <dd>{{ scenario.revision }}</dd>
        </div>
        <div>
          <dt>Slug</dt>
          <dd class="sim-technical">{{ scenario.slug }}</dd>
        </div>
        <div>
          <dt>Provenance</dt>
          <dd class="sim-technical">{{ scenario.provenance }}</dd>
        </div>
        <div>
          <dt>Compatible targets</dt>
          <dd>{{ scenario.preparation_targets.length }}</dd>
        </div>
      </dl>

      <div class="sim-rule-note">
        Scenario ≠ Lab. مرجع المختبر هنا وحدة داخل orchestration، ولا يتحول إلى Standalone Lab Run.
      </div>
    </template>
    <p v-else class="sim-muted">اختر سيناريو لعرض عقده البيئي.</p>
  </div>
</template>

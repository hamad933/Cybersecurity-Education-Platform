<script setup lang="ts">
import type { EnterpriseItem } from '../types';

defineProps<{ enterprise: EnterpriseItem | null }>();
</script>

<template>
  <div class="sim-context" data-testid="enterprise-right">
    <div class="sim-panel-heading">
      <div class="sim-panel-heading__title">
        <p class="sim-kicker">RIGHT · CONTEXT</p>
        <h2>السياق</h2>
      </div>
      <button type="button" class="sim-close-btn" aria-label="إغلاق السياق" title="إغلاق">✕</button>
    </div>

    <template v-if="enterprise">
      <p class="sim-context-copy">
        {{
          enterprise.description_ar ||
          'تعريف مؤسسة محاكاة داخلي عالي الدقة، مثبت وقابل لإعادة التنفيذ.'
        }}
      </p>

      <!-- Capabilities Section -->
      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="sim-section-icon"
          >
            <polygon points="12 2 2 7 12 12 22 7 12 2" />
            <polyline points="2 17 12 22 22 17" />
            <polyline points="2 12 12 17 22 12" />
          </svg>
          <h3>Capabilities</h3>
        </div>
        <ul class="sim-feature-list">
          <li>HTTP / HTTPS interaction</li>
          <li>User authentication</li>
          <li>Session management</li>
          <li>Application logging</li>
        </ul>
      </section>

      <!-- Simulation State Source -->
      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="sim-section-icon sim-text-success"
          >
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
            <polyline points="22 4 12 14.01 9 11.01" />
          </svg>
          <h3>Simulation-state source</h3>
        </div>
        <div class="sim-source-status">
          <span class="sim-status-dot sim-status-dot--live" />
          <strong>Enterprise-backed</strong>
        </div>
        <p class="sim-context-note">
          يرتبط هذا الكائن بكيان مؤسسي قياسي دون وجود تعديلات خارج العقد.
        </p>
      </section>

      <!-- Behavior Section -->
      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="sim-section-icon"
          >
            <polyline points="16 18 22 12 16 6" />
            <polyline points="8 6 2 12 8 18" />
          </svg>
          <h3>Behavior</h3>
        </div>
        <ul class="sim-feature-list">
          <li>Input handling simulation</li>
          <li>Logging behavior</li>
        </ul>
      </section>

      <!-- Telemetry Generation -->
      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="sim-section-icon sim-text-warning"
          >
            <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
          </svg>
          <div class="sim-flex-between">
            <h3>Telemetry generation</h3>
            <span class="sim-badge sim-badge--success">Enabled</span>
          </div>
        </div>
        <p class="sim-context-note">
          Generates application and access logs dynamically during simulation runs.
        </p>
      </section>

      <!-- Validation -->
      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="sim-section-icon sim-text-cyan"
          >
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
          </svg>
          <div class="sim-flex-between">
            <h3>Validation</h3>
            <span class="sim-badge sim-badge--cyan">Compatible</span>
          </div>
        </div>
        <p class="sim-context-note">
          Fully compatible with the current Digital Twin configuration.
        </p>
      </section>

      <dl class="sim-facts">
        <div>
          <dt>المعرّف</dt>
          <dd class="sim-technical">{{ enterprise.slug }}</dd>
        </div>
        <div>
          <dt>Digital Twins</dt>
          <dd>{{ enterprise.digital_twins.length }}</dd>
        </div>
        <div>
          <dt>Provenance</dt>
          <dd class="sim-technical">{{ enterprise.provenance }}</dd>
        </div>
        <div>
          <dt>مصدر تجريبي</dt>
          <dd>{{ enterprise.is_fixture ? 'نعم' : 'لا' }}</dd>
        </div>
      </dl>

      <div class="sim-rule-note">
        Digital Twin وBaseline تعريفان منشوران مثبتان بالـ digest؛ لا يُشتقان من Runtime State.
      </div>
    </template>
    <p v-else class="sim-muted">اختر مؤسسة لعرض سياقها الفريد.</p>
  </div>
</template>

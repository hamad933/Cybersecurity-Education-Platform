<script setup lang="ts">
import { shortDigest } from '../formatters';
import type { LabItem } from '../types';

defineProps<{ lab: LabItem | null }>();
</script>

<template>
  <div class="sim-context" data-testid="lab-right">
    <div class="sim-panel-heading">
      <div class="sim-panel-heading__title">
        <p class="sim-kicker">RIGHT · CONTEXT</p>
        <h2>السياق</h2>
      </div>
      <button type="button" class="sim-close-btn" aria-label="إغلاق السياق" title="إغلاق">✕</button>
    </div>

    <template v-if="lab">
      <!-- Governed Lab Context from Reference 03 -->
      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sim-section-icon sim-text-purple"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
          <div>
            <small class="sim-field-label">Objective type</small>
            <strong class="sim-field-val">State confirmation</strong>
          </div>
        </div>
      </section>

      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sim-section-icon sim-text-blue"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
          <div>
            <small class="sim-field-label">Required capability</small>
            <strong class="sim-field-val">HTTP interaction</strong>
          </div>
        </div>
      </section>

      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sim-section-icon sim-text-cyan"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
          <div>
            <small class="sim-field-label">Permitted tools</small>
            <div class="sim-tag-group">
              <span class="sim-tool-tag">Browser</span>
              <span class="sim-tool-tag">Request Inspector</span>
            </div>
          </div>
        </div>
      </section>

      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sim-section-icon sim-text-teal"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          <div>
            <small class="sim-field-label">Expected signal</small>
            <strong class="sim-field-val">Vulnerable response behavior</strong>
          </div>
        </div>
      </section>

      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sim-section-icon sim-text-purple"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
          <div>
            <small class="sim-field-label">Validation link</small>
            <strong class="sim-field-val">Injection-condition rule</strong>
          </div>
        </div>
      </section>

      <section class="sim-context-section">
        <div class="sim-context-section__header">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sim-section-icon sim-text-success"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <div>
            <small class="sim-field-label">Completion contribution</small>
            <strong class="sim-field-val sim-text-success">Required</strong>
          </div>
        </div>
      </section>

      <section class="sim-context-section">
        <h3>Target Baseline</h3>
        <code class="sim-technical sim-wrap">{{ lab.baseline_id }}</code>
      </section>

      <dl class="sim-facts">
        <div>
          <dt>Revision</dt>
          <dd>{{ lab.revision }}</dd>
        </div>
        <div>
          <dt>Slug</dt>
          <dd class="sim-technical">{{ lab.slug }}</dd>
        </div>
        <div>
          <dt>Provenance</dt>
          <dd class="sim-technical">{{ lab.provenance }}</dd>
        </div>
        <div>
          <dt>Digest</dt>
          <dd class="sim-technical">{{ shortDigest(lab.digest) }}</dd>
        </div>
      </dl>

      <div class="sim-rule-note">
        Baseline ≠ Runtime Snapshot ≠ Checkpoint. التهيئة تشتق حالة تشغيل جديدة.
      </div>
    </template>
    <p v-else class="sim-muted">اختر مختبرًا لعرض سياقه.</p>
  </div>
</template>

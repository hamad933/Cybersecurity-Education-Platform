<script setup lang="ts">
import { jsonText } from '../formatters';
import type { ResultCompareProjection, ResultItem, ResultMode } from '../types';

defineProps<{
  result: ResultItem | null;
  mode: ResultMode;
  compare: ResultCompareProjection | null;
}>();
</script>

<template>
  <div v-if="result" class="sim-deep-grid" data-testid="result-bottom">
    <section class="sim-deep-section">
      <h3>Canonical sealed Result payload</h3>
      <p class="sim-muted">حقيقة خام مختومة — ليست إسقاطًا تحليليًا.</p>
      <pre class="sim-json">{{ jsonText(result.sealed_payload) }}</pre>
    </section>
    <section class="sim-deep-section">
      <h3>Canonical artifacts</h3>
      <pre class="sim-json">{{ jsonText(result.artifacts) }}</pre>
    </section>
    <section class="sim-deep-section">
      <h3>Typed analytical projection · {{ mode }}</h3>
      <pre class="sim-json">{{ jsonText(result.analytics) }}</pre>
    </section>
    <section v-if="mode === 'compare'" class="sim-deep-section">
      <h3>Backend-owned Compare projection</h3>
      <pre class="sim-json">{{ jsonText(compare) }}</pre>
    </section>
    <section class="sim-deep-section sim-deep-section--wide">
      <h3>Historical compatibility rows · read-only</h3>
      <p class="sim-muted">
        السجلات القديمة محفوظة للرجوع التاريخي فقط؛ ليست مصدر Compare أو Candidate Evidence الحالي.
      </p>
      <pre class="sim-json">{{ jsonText(result.legacy_history) }}</pre>
    </section>
  </div>
</template>

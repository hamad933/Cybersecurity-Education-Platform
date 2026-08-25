<script setup lang="ts">
import type { EnterpriseItem } from '../types';

defineProps<{ enterprise: EnterpriseItem | null }>();
</script>

<template>
  <div class="sim-context" data-testid="enterprise-right">
    <div class="sim-panel-heading">
      <p class="sim-kicker">RIGHT · INTERPRETATION</p>
      <h2>سياق المؤسسة</h2>
    </div>
    <template v-if="enterprise">
      <p class="sim-context-copy">
        {{
          enterprise.description_ar ||
          'تعريف مؤسسة محاكاة داخلي عالي الدقة، مثبت وقابل لإعادة التنفيذ.'
        }}
      </p>
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

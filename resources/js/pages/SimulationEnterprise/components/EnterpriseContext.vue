<script setup lang="ts">
import { computed } from 'vue';

import type { EnterpriseItem } from '../types';

const props = defineProps<{ enterprise: EnterpriseItem | null }>();

const revisionCount = computed(
  () =>
    props.enterprise?.digital_twins.reduce((count, twin) => count + twin.revisions.length, 0) ?? 0,
);
const baselineCount = computed(
  () =>
    props.enterprise?.digital_twins.reduce(
      (count, twin) =>
        count +
        twin.revisions.reduce(
          (revisionCount, revision) => revisionCount + revision.baselines.length,
          0,
        ),
      0,
    ) ?? 0,
);
</script>

<template>
  <div class="sim-context" data-testid="enterprise-right">
    <div class="sim-panel-heading">
      <div class="sim-panel-heading__title">
        <p class="sim-kicker">RIGHT · CONTEXT</p>
        <h2>السياق</h2>
      </div>
    </div>

    <template v-if="enterprise">
      <p v-if="enterprise.description_ar" class="sim-context-copy">
        {{ enterprise.description_ar }}
      </p>
      <p v-else class="sim-muted">لا يتضمن تعريف المؤسسة وصفًا منشورًا.</p>

      <section class="sim-context-section">
        <h3>حدود الحقيقة</h3>
        <p class="sim-context-copy">
          تعرض هذه المساحة هوية التعريف وبنيته المنشورة فقط. لا يثبت تعريف المؤسسة حالة Runtime أو
          Telemetry أو توافقًا أو قدرات تشغيلية ما لم تحملها بيانات محكومة صراحةً.
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
          <dt>Revisions</dt>
          <dd>{{ revisionCount }}</dd>
        </div>
        <div>
          <dt>Baselines</dt>
          <dd>{{ baselineCount }}</dd>
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
    <p v-else class="sim-muted">اختر مؤسسة لعرض سياق تعريفها.</p>
  </div>
</template>

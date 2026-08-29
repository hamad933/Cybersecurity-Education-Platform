<script setup lang="ts">
import { computed } from 'vue';

import { displayValue } from '../projections';
import type { EnterpriseItem, JsonMap } from '../types';

const props = defineProps<{
  enterprise: EnterpriseItem | null;
  selectedContext: JsonMap | null;
}>();

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
      <section
        v-if="selectedContext"
        class="sim-context-section"
        data-testid="enterprise-selected-context"
      >
        <h3>Selected object</h3>
        <dl class="sim-facts">
          <div>
            <dt>Context type</dt>
            <dd class="sim-technical">{{ selectedContext.context_type }}</dd>
          </div>
          <div v-if="selectedContext.name_ar || selectedContext.label">
            <dt>Name</dt>
            <dd>{{ selectedContext.name_ar ?? selectedContext.label }}</dd>
          </div>
          <div
            v-if="selectedContext.entity_key || selectedContext.component_key || selectedContext.id"
          >
            <dt>Stable key / ID</dt>
            <dd class="sim-technical sim-wrap">
              {{
                selectedContext.entity_key ?? selectedContext.component_key ?? selectedContext.id
              }}
            </dd>
          </div>
          <div v-if="selectedContext.ownership_scope">
            <dt>Ownership scope</dt>
            <dd class="sim-technical">{{ selectedContext.ownership_scope }}</dd>
          </div>
          <div v-if="selectedContext.entity_type">
            <dt>Entity type</dt>
            <dd class="sim-technical">{{ selectedContext.entity_type }}</dd>
          </div>
          <div v-if="selectedContext.raw">
            <dt>Definition</dt>
            <dd class="sim-technical sim-wrap">{{ displayValue(selectedContext.raw) }}</dd>
          </div>
        </dl>
      </section>

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

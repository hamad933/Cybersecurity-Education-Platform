<script setup lang="ts">
import { computed } from "vue";
import type { EnterpriseItem } from "../types";
import { asMap, fieldEntries, valueText } from "../utils";

const props = defineProps<{ enterprise: EnterpriseItem }>();

const nodes = computed(() =>
  (Array.isArray(asMap(props.enterprise.digital_twin_revision?.topology).nodes)
    ? (asMap(props.enterprise.digital_twin_revision?.topology)
        .nodes as unknown[])
    : []
  )
    .map(asMap)
    .filter((node) => typeof node.id === "string")
    .map((node) => ({ id: String(node.id), kind: valueText(node.kind) })),
);

const links = computed(() =>
  (Array.isArray(asMap(props.enterprise.digital_twin_revision?.topology).links)
    ? (asMap(props.enterprise.digital_twin_revision?.topology)
        .links as unknown[])
    : []
  )
    .map(asMap)
    .filter(
      (link) => typeof link.from === "string" && typeof link.to === "string",
    )
    .map((link) => ({ from: String(link.from), to: String(link.to) })),
);
</script>

<template>
  <section class="surface-panel" data-testid="enterprise-topology-canvas">
    <header class="section-heading">
      <div>
        <p class="rail-kicker">النموذج التشغيلي</p>
        <h2>{{ enterprise.name_ar }}</h2>
      </div>
      <span v-if="enterprise.is_fixture" class="fixture-badge"
        >بيانات تجريبية محكومة</span
      >
    </header>

    <div class="lineage-strip" dir="ltr">
      <span>Enterprise</span><i>→</i><span>Digital Twin Revision</span><i>→</i
      ><span>Baseline</span>
    </div>

    <div v-if="nodes.length" class="topology-board">
      <article v-for="node in nodes" :key="node.id" class="topology-node">
        <span class="node-dot" />
        <strong class="technical" dir="ltr">{{ node.id }}</strong>
        <small class="technical" dir="ltr">{{ node.kind }}</small>
      </article>
    </div>
    <p v-else class="truthful-unavailable">
      لا يرسل الإصدار المحدد عقد Topology منظّمة يمكن رسمها.
    </p>

    <div v-if="links.length" class="edge-ledger">
      <p class="rail-kicker">الوصلات المثبتة</p>
      <div class="edge-list" dir="ltr">
        <span
          v-for="link in links"
          :key="`${link.from}-${link.to}`"
          class="edge-chip"
        >
          <b>{{ link.from }}</b
          ><i>→</i><b>{{ link.to }}</b>
        </span>
      </div>
    </div>

    <section class="subsurface">
      <p class="rail-kicker">Baseline State</p>
      <h3>الحالة التشغيلية المثبتة</h3>
      <div
        v-if="fieldEntries(enterprise.baseline?.state).length"
        class="field-grid"
      >
        <div
          v-for="field in fieldEntries(enterprise.baseline?.state)"
          :key="field.key"
          class="field-cell"
        >
          <small class="technical" dir="ltr">{{ field.key }}</small>
          <strong>{{ field.value }}</strong>
        </div>
      </div>
      <p v-else class="truthful-unavailable">
        لا توجد Baseline State منظّمة مستلمة لهذا السجل.
      </p>
    </section>
  </section>
</template>

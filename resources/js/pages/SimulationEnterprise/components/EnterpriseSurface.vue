<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import { topologyLinks, topologyNodes } from '../projections';
import type { DigitalTwinRevisionItem, EnterpriseItem } from '../types';

const props = defineProps<{ enterprise: EnterpriseItem | null }>();

const selectedTwinId = ref('');
const selectedRevisionId = ref('');
const selectedNodeId = ref('');

const selectedTwin = computed(
  () =>
    props.enterprise?.digital_twins.find((twin) => twin.id === selectedTwinId.value) ??
    props.enterprise?.digital_twins[0] ??
    null,
);
const selectedRevision = computed<DigitalTwinRevisionItem | null>(
  () =>
    selectedTwin.value?.revisions.find((revision) => revision.id === selectedRevisionId.value) ??
    selectedTwin.value?.revisions.at(-1) ??
    null,
);
const nodes = computed(() => topologyNodes(selectedRevision.value));
const links = computed(() => topologyLinks(selectedRevision.value));

watch(
  () => props.enterprise,
  (enterprise) => {
    selectedTwinId.value = enterprise?.digital_twins[0]?.id ?? '';
  },
  { immediate: true },
);
watch(
  selectedTwin,
  (twin) => {
    selectedRevisionId.value = twin?.revisions.at(-1)?.id ?? '';
  },
  { immediate: true },
);
watch(
  nodes,
  (items) => {
    if (!items.some((node) => node.id === selectedNodeId.value)) {
      selectedNodeId.value = items[0]?.id ?? '';
    }
  },
  { immediate: true },
);

function point(nodeId: string): { x: number; y: number } {
  const index = Math.max(
    nodes.value.findIndex((node) => node.id === nodeId),
    0,
  );
  const columns = Math.min(Math.max(nodes.value.length, 1), 3);
  const column = index % columns;
  const row = Math.floor(index / columns);
  const spacing = 760 / columns;
  return { x: 95 + spacing / 2 + column * spacing, y: 100 + row * 170 };
}
</script>

<template>
  <section class="sim-surface sim-canvas-surface" data-testid="enterprise-center">
    <header class="sim-workbench-header">
      <div>
        <p class="sim-kicker">ENTERPRISE · TOPOLOGY</p>
        <h1>{{ enterprise?.name_ar ?? 'المؤسسة والنسخة الرقمية' }}</h1>
      </div>
      <div v-if="enterprise" class="sim-view-controls" aria-label="اختيار النسخة الرقمية">
        <label>
          <span>Digital Twin</span>
          <select v-model="selectedTwinId">
            <option v-for="twin in enterprise.digital_twins" :key="twin.id" :value="twin.id">
              {{ twin.name_ar }}
            </option>
          </select>
        </label>
        <label v-if="selectedTwin">
          <span>Revision</span>
          <select v-model="selectedRevisionId">
            <option
              v-for="revision in selectedTwin.revisions"
              :key="revision.id"
              :value="revision.id"
            >
              REV {{ revision.revision }}
            </option>
          </select>
        </label>
      </div>
    </header>

    <div v-if="!enterprise || !selectedRevision" class="sim-empty">
      <strong>لا توجد Topology منشورة للاختيار الحالي</strong>
    </div>

    <div v-else class="sim-topology-workbench" data-testid="enterprise-topology">
      <div class="sim-canvas-caption">
        <div>
          <strong>{{ selectedTwin?.name_ar }}</strong>
          <code class="sim-technical">REV {{ selectedRevision.revision }}</code>
        </div>
        <div class="sim-canvas-counts">
          <span>{{ nodes.length }} NODES</span><span>{{ links.length }} LINKS</span>
          <span class="sim-provenance">{{ selectedTwin?.provenance }}</span>
        </div>
      </div>

      <svg
        v-if="nodes.length"
        class="sim-topology-svg"
        viewBox="0 0 950 470"
        role="img"
        aria-label="مخطط topology مبني من العقد والروابط المنشورة"
      >
        <defs>
          <pattern id="sim-dot-grid" width="24" height="24" patternUnits="userSpaceOnUse">
            <circle cx="1" cy="1" r="1" class="sim-grid-dot" />
          </pattern>
          <marker id="sim-arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
            <path d="M0,0 L8,4 L0,8 Z" class="sim-link-arrow" />
          </marker>
        </defs>
        <rect width="950" height="470" class="sim-grid-fill" />
        <g class="sim-links">
          <line
            v-for="(link, index) in links"
            :key="`${link.from}-${link.to}-${index}`"
            :x1="point(link.from).x"
            :y1="point(link.from).y + 42"
            :x2="point(link.to).x"
            :y2="point(link.to).y - 42"
            marker-end="url(#sim-arrow)"
            data-testid="topology-link"
          />
        </g>
        <g
          v-for="node in nodes"
          :key="node.id"
          class="sim-topology-node"
          :class="{ 'is-selected': node.id === selectedNodeId }"
          :transform="`translate(${point(node.id).x - 92} ${point(node.id).y - 38})`"
          role="button"
          tabindex="0"
          data-testid="topology-node"
          @click="selectedNodeId = node.id"
          @keydown.enter="selectedNodeId = node.id"
        >
          <rect width="184" height="76" rx="8" />
          <circle cx="24" cy="25" r="8" />
          <text x="42" y="30" class="sim-node-title">{{ node.label }}</text>
          <text x="18" y="57" class="sim-node-kind">{{ node.kind }}</text>
        </g>
      </svg>

      <div v-if="nodes.length" class="sim-graph-mobile" aria-label="Topology مبسطة للشاشة الضيقة">
        <article v-for="node in nodes" :key="node.id" data-testid="topology-node-mobile">
          <span class="sim-node-marker" aria-hidden="true" />
          <div>
            <strong>{{ node.label }}</strong
            ><small class="sim-technical">{{ node.kind }}</small>
          </div>
          <div class="sim-mobile-links">
            <span v-for="link in links.filter((item) => item.from === node.id)" :key="link.to">
              → {{ link.to }}
            </span>
          </div>
        </article>
      </div>
      <p v-if="!nodes.length" class="sim-empty">لا تحتوي هذه المراجعة على عقد Topology.</p>

      <footer class="sim-canvas-legend">
        <span><i class="sim-legend-line" />رابط منشور</span>
        <span><i class="sim-legend-node" />عقدة من Digital Twin</span>
        <span>Enterprise-backed definition · runtime state remains separate</span>
      </footer>
    </div>
  </section>
</template>

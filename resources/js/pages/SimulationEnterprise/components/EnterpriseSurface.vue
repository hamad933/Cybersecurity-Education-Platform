<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import { topologyLinks, topologyNodes } from '../projections';
import type { DigitalTwinRevisionItem, EnterpriseItem, TopologyLink, TopologyNode } from '../types';

const props = defineProps<{
  enterprise: EnterpriseItem | null;
  selectedContextId: string | null;
}>();
const emit = defineEmits<{ selectContext: [id: string] }>();

const selectedTwinId = ref('');
const selectedRevisionId = ref('');
const selectedNodeId = ref('');
const zoomLevel = ref(100);

const selectedTwin = computed(
  () =>
    props.enterprise?.digital_twins.find((twin) => twin.id === selectedTwinId.value) ??
    props.enterprise?.digital_twins[0] ??
    null,
);
const selectedRevision = computed<DigitalTwinRevisionItem | null>(
  () =>
    selectedTwin.value?.revisions.find((revision) => revision.id === selectedRevisionId.value) ??
    selectedTwin.value?.revisions[0] ??
    null,
);
const nodes = computed<TopologyNode[]>(() => {
  if (selectedRevision.value?.components?.length) {
    return selectedRevision.value.components.map((component) => ({
      id: component.id,
      label: component.name_ar,
      kind: component.ownership_scope,
      raw: {
        component_key: component.component_key,
        ownership_scope: component.ownership_scope,
        enterprise_entity_id: component.enterprise_entity_id ?? null,
        device_template_revision_id: component.device_template_revision_id ?? null,
        simulation_definition: component.simulation_definition,
      },
    }));
  }

  return topologyNodes(selectedRevision.value);
});
const links = computed<TopologyLink[]>(() => {
  if (selectedRevision.value?.components?.length) {
    return (selectedRevision.value.relationships ?? []).map((relationship) => ({
      from: relationship.source_component_id,
      to: relationship.target_component_id,
      label: relationship.relationship_type,
    }));
  }

  return topologyLinks(selectedRevision.value);
});

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
    selectedRevisionId.value = twin?.revisions[0]?.id ?? '';
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
watch(
  () => props.selectedContextId,
  (contextId) => {
    if (contextId && nodes.value.some((node) => node.id === contextId)) {
      selectedNodeId.value = contextId;
    }
  },
);

function selectNode(nodeId: string): void {
  selectedNodeId.value = nodeId;
  emit('selectContext', nodeId);
}

function getNodeRole(node: TopologyNode): {
  icon: string;
  theme: 'blue';
  badge?: string;
  displayType: string;
} {
  return { icon: 'server', theme: 'blue', displayType: node.kind };
}

function point(nodeId: string): { x: number; y: number } {
  const index = Math.max(
    nodes.value.findIndex((node) => node.id === nodeId),
    0,
  );

  const customPositions: Record<number, { x: number; y: number }> = {
    0: { x: 190, y: 150 },
    1: { x: 480, y: 120 },
    2: { x: 760, y: 120 },
    3: { x: 480, y: 280 },
    4: { x: 740, y: 280 },
    5: { x: 480, y: 410 },
  };

  if (nodes.value.length === 3) {
    const p3: Record<number, { x: number; y: number }> = {
      0: { x: 180, y: 220 },
      1: { x: 480, y: 160 },
      2: { x: 770, y: 220 },
    };
    return p3[index] ?? { x: 150 + index * 260, y: 220 };
  }

  if (customPositions[index]) {
    return customPositions[index];
  }

  const columns = Math.min(Math.max(nodes.value.length, 1), 3);
  const column = index % columns;
  const row = Math.floor(index / columns);
  const spacingX = 700 / columns;
  return { x: 120 + spacingX / 2 + column * spacingX, y: 130 + row * 160 };
}

function getLinkLabel(link: { from: string; to: string; label: string | null }): string {
  return link.label ?? 'UNLABELED';
}
</script>

<template>
  <section class="sim-surface sim-canvas-surface" data-testid="enterprise-center">
    <header class="sim-workbench-header">
      <div>
        <p class="sim-kicker">ENTERPRISE · DIGITAL TWIN TOPOLOGY</p>
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
      <div class="sim-canvas-subtoolbar">
        <div class="sim-canvas-subtoolbar__left">
          <button
            type="button"
            class="sim-tool-btn"
            disabled
            title="تحرير topology غير متاح في مساحة القراءة"
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M3 3l7 18 3-7 7-3L3 3z" />
            </svg>
            <span>Select</span>
          </button>
          <button
            type="button"
            class="sim-tool-btn"
            disabled
            title="إضافة العقد غير متاحة في مساحة القراءة"
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="12" cy="12" r="10" />
              <path d="M12 8v8M8 12h8" />
            </svg>
            <span>Add</span>
          </button>
          <button
            type="button"
            class="sim-tool-btn"
            disabled
            title="تحرير الروابط غير متاح في مساحة القراءة"
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
              <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
            </svg>
            <span>Connect</span>
          </button>
          <div class="sim-subtoolbar-divider" />
          <button type="button" class="sim-tool-icon-btn" disabled title="التراجع غير متاح">
            <svg
              width="13"
              height="13"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M3 7v6h6" />
              <path d="M21 17a9 9 0 00-9-9 9 9 0 00-6 2.3L3 13" />
            </svg>
          </button>
          <button type="button" class="sim-tool-icon-btn" disabled title="الإعادة غير متاحة">
            <svg
              width="13"
              height="13"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M21 7v6h-6" />
              <path d="M3 17a9 9 0 019-9 9 9 0 016 2.3l3 2.7" />
            </svg>
          </button>
          <button type="button" class="sim-tool-icon-btn" disabled title="الحذف غير متاح">
            <svg
              width="13"
              height="13"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"
              />
            </svg>
          </button>
        </div>

        <div class="sim-canvas-subtoolbar__right">
          <button
            type="button"
            class="sim-tool-icon-btn"
            disabled
            title="تغيير شبكة المحاذاة غير متاح"
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <rect x="3" y="3" width="7" height="7" />
              <rect x="14" y="3" width="7" height="7" />
              <rect x="14" y="14" width="7" height="7" />
              <rect x="3" y="14" width="7" height="7" />
            </svg>
          </button>
          <button
            type="button"
            class="sim-tool-icon-btn"
            title="تصغير"
            @click="zoomLevel = Math.max(50, zoomLevel - 10)"
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
              <line x1="8" y1="11" x2="14" y2="11" />
            </svg>
          </button>
          <span class="sim-zoom-value">{{ zoomLevel }}%</span>
          <button
            type="button"
            class="sim-tool-icon-btn"
            title="تكبير"
            @click="zoomLevel = Math.min(150, zoomLevel + 10)"
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
              <line x1="11" y1="8" x2="11" y2="14" />
              <line x1="8" y1="11" x2="14" y2="11" />
            </svg>
          </button>
          <button type="button" class="sim-tool-icon-btn" disabled title="ملء الشاشة غير متاح">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"
              />
            </svg>
          </button>
        </div>
      </div>

      <div class="sim-canvas-caption">
        <div class="sim-caption-main">
          <strong>{{ selectedTwin?.name_ar }} — {{ enterprise.name_ar }}</strong>
          <span class="sim-badge">Revision {{ selectedRevision.revision }}</span>
          <span v-if="selectedRevision.status" class="sim-badge">{{
            selectedRevision.status
          }}</span>
        </div>
        <div class="sim-canvas-counts">
          <span>{{ nodes.length }} NODES</span>
          <span>{{ links.length }} LINKS</span>
          <span class="sim-provenance">{{ selectedTwin?.provenance }}</span>
        </div>
      </div>

      <div class="sim-canvas-viewport">
        <svg
          v-if="nodes.length"
          class="sim-topology-svg"
          viewBox="0 0 950 490"
          role="img"
          aria-label="مخطط topology مبني من العقد والروابط المنشورة"
          :style="{ transform: `scale(${zoomLevel / 100})`, transformOrigin: 'center' }"
        >
          <defs>
            <pattern id="sim-dot-grid" width="20" height="20" patternUnits="userSpaceOnUse">
              <circle cx="1.5" cy="1.5" r="1.2" class="sim-grid-dot" />
            </pattern>
            <marker
              id="sim-arrow-cyan"
              markerWidth="9"
              markerHeight="9"
              refX="8"
              refY="4.5"
              orient="auto"
            >
              <path d="M0,1 L8,4.5 L0,8 Z" class="sim-arrow-cyan" />
            </marker>
          </defs>

          <rect width="950" height="490" class="sim-grid-fill" />

          <g class="sim-links">
            <g
              v-for="(link, index) in links"
              :key="`${link.from}-${link.to}-${index}`"
              data-testid="topology-link"
            >
              <line
                :x1="point(link.from).x"
                :y1="point(link.from).y"
                :x2="point(link.to).x"
                :y2="point(link.to).y"
                marker-end="url(#sim-arrow-cyan)"
              />
              <g
                :transform="`translate(${(point(link.from).x + point(link.to).x) / 2}, ${(point(link.from).y + point(link.to).y) / 2})`"
              >
                <rect
                  :x="-Math.max(getLinkLabel(link).length * 3.8 + 8, 38)"
                  y="-10"
                  :width="Math.max(getLinkLabel(link).length * 7.6 + 16, 76)"
                  height="20"
                  rx="4"
                  class="sim-link-tag-bg"
                />
                <text y="3" text-anchor="middle" class="sim-link-tag-text">
                  {{ getLinkLabel(link) }}
                </text>
              </g>
            </g>
          </g>

          <g
            v-for="node in nodes"
            :key="node.id"
            class="sim-topology-node"
            :class="[
              `sim-node--${getNodeRole(node).theme}`,
              { 'is-selected': node.id === selectedNodeId },
            ]"
            :transform="`translate(${point(node.id).x - 100}, ${point(node.id).y - 40})`"
            role="button"
            tabindex="0"
            data-testid="topology-node"
            @click="selectNode(node.id)"
            @keydown.enter="selectNode(node.id)"
            @keydown.space.prevent="selectNode(node.id)"
          >
            <rect width="200" height="80" rx="10" class="sim-node-card-bg" />
            <circle cx="32" cy="40" r="16" class="sim-node-icon-circle" />

            <g transform="translate(24, 32)">
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                class="sim-node-icon-svg"
              >
                <path v-if="getNodeRole(node).icon === 'terminal'" d="M4 17l6-6-6-6m8 14h8" />
                <path
                  v-else-if="getNodeRole(node).icon === 'shield'"
                  d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
                />
                <path
                  v-else-if="getNodeRole(node).icon === 'database'"
                  d="M12 3c-4.97 0-9 1.34-9 3v12c0 1.66 4.03 3 9 3s9-1.34 9-3V6c0-1.66-4.03-3-9-3z M3 10c0 1.66 4.03 3 9 3s9-1.34 9-3 M3 15c0 1.66 4.03 3 9 3s9-1.34 9-3"
                />
                <path
                  v-else-if="getNodeRole(node).icon === 'user-check'"
                  d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M8.5 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z M17 11l2 2 4-4"
                />
                <path
                  v-else-if="getNodeRole(node).icon === 'activity'"
                  d="M22 12h-4l-3 9L9 3l-3 9H2"
                />
                <path
                  v-else
                  d="M21 12a9 9 0 0 1-9 9m9-9a9 9 0 0 0-9-9m9 9H3m9 9a9 9 0 0 1-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"
                />
              </svg>
            </g>

            <text x="56" y="32" class="sim-node-title">{{ node.label }}</text>
            <text x="56" y="50" class="sim-node-kind">
              Type: {{ getNodeRole(node).displayType }}
            </text>

            <g v-if="getNodeRole(node).badge" transform="translate(56, 56)">
              <rect width="90" height="16" rx="3" class="sim-node-badge-bg" />
              <text x="45" y="11" text-anchor="middle" class="sim-node-badge-text">
                {{ getNodeRole(node).badge }}
              </text>
            </g>
          </g>
        </svg>
      </div>

      <div v-if="nodes.length" class="sim-graph-mobile" aria-label="Topology مبسطة للشاشة الضيقة">
        <article v-for="node in nodes" :key="node.id" data-testid="topology-node-mobile">
          <span class="sim-node-marker" aria-hidden="true" />
          <div>
            <strong>{{ node.label }}</strong>
            <small class="sim-technical">{{ node.kind }}</small>
          </div>
          <div class="sim-mobile-links">
            <span v-for="link in links.filter((item) => item.from === node.id)" :key="link.to">
              → {{ link.to }}
            </span>
          </div>
        </article>
      </div>

      <footer class="sim-canvas-legend">
        <span><i class="sim-legend-line" />رابط topology منشور</span>
        <span><i class="sim-legend-node" />عقدة Digital Twin</span>
        <span>Published definition · runtime state remains separate</span>
      </footer>
    </div>
  </section>
</template>

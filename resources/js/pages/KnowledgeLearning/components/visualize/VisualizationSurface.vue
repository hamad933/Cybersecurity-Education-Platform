<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type {
  OverlayLayer,
  OverlayName,
  ViewMode,
  VisualEdge,
  VisualNode,
  VisualPoint,
  VisualSelection,
} from './types';
import TreeView from './views/TreeView.vue';
import PathView from './views/PathView.vue';
import GraphView from './views/GraphView.vue';
import CanvasView from './views/CanvasView.vue';
import { edgesForView } from './viewModels';

const props = defineProps<{
  view: ViewMode;
  nodes: VisualNode[];
  edges: VisualEdge[];
  activeOverlay: OverlayName | null;
  overlayLayer: OverlayLayer | null;
  visualPositions: Record<string, VisualPoint>;
  selection: VisualSelection | null;
}>();

const emit = defineEmits<{
  select: [selection: VisualSelection];
  moveNode: [payload: { id: string; x: number; y: number; method: 'pointer' | 'keyboard' }];
  cameraChange: [percent: number];
}>();

const spatialView = ref<{
  zoomIn?: () => void;
  zoomOut?: () => void;
  fit?: () => void;
} | null>(null);
const eligibleEdges = computed(() => edgesForView(props.edges, props.view));

watch(
  () => props.view,
  () => emit('cameraChange', 100),
);

const zoomIn = () => spatialView.value?.zoomIn?.();
const zoomOut = () => spatialView.value?.zoomOut?.();
const fit = () => spatialView.value?.fit?.();

defineExpose({ zoomIn, zoomOut, fit });
</script>

<template>
  <div class="min-w-0">
    <TreeView
      v-if="view === 'Tree'"
      :nodes="nodes"
      :edges="edges"
      :selection="selection"
      :overlay-layer="overlayLayer"
      @select="emit('select', $event)"
    />
    <PathView
      v-else-if="view === 'Path'"
      :nodes="nodes"
      :edges="eligibleEdges"
      :selection="selection"
      :overlay-layer="overlayLayer"
      @select="emit('select', $event)"
    />
    <GraphView
      v-else-if="view === 'Graph'"
      ref="spatialView"
      :nodes="nodes"
      :edges="eligibleEdges"
      :selection="selection"
      :overlay-layer="overlayLayer"
      @select="emit('select', $event)"
      @camera-change="emit('cameraChange', $event)"
    />
    <CanvasView
      v-else
      ref="spatialView"
      :nodes="nodes"
      :edges="eligibleEdges"
      :selection="selection"
      :overlay-layer="overlayLayer"
      :visual-positions="visualPositions"
      @select="emit('select', $event)"
      @move-node="emit('moveNode', $event)"
      @camera-change="emit('cameraChange', $event)"
    />
  </div>
</template>

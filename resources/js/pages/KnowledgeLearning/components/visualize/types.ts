export type ViewMode = 'Tree' | 'Path' | 'Graph' | 'Canvas';
export type OverlayName = 'coverage' | 'prerequisite' | 'progress' | 'evidence' | 'mastery';

export type VisualNode = {
  id: string;
  kind: 'knowledge_unit' | 'capability' | string;
  label: string;
  technical_label: string;
};

export type VisualEdge = {
  id: string;
  from: string;
  to: string;
  type: string;
  revision: number;
  lifecycle: Record<string, unknown>;
};

export type OverlayLayer = {
  available: boolean;
  observations?: unknown;
  source?: string;
  reason?: string;
};

export type MapState = {
  saved: boolean;
  id: string | null;
  state: string;
  scope?: { kind: string; id: string } | null;
  canonical_node_ids?: string[];
  visual_positions?: Record<string, { x: number; y: number }>;
};

export type OverlayState = {
  active: OverlayName | null | string;
  available: string[];
  layers?: Partial<Record<OverlayName, OverlayLayer>>;
};

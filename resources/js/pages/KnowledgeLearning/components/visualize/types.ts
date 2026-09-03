export type ViewMode = 'Tree' | 'Path' | 'Graph' | 'Canvas';
export type OverlayName = 'coverage' | 'prerequisite' | 'progress' | 'evidence' | 'mastery';
export type VisualFilter = 'all' | 'knowledge' | 'structure';

export type VisualNode = {
  id: string;
  kind: string;
  label: string;
  technical_label: string;
  label_source?: 'canonical' | 'technical_fallback';
  provenance?: string;
};

export type VisualEdge = {
  id: string;
  from: string;
  to: string;
  type: string;
  semantic?: 'containment' | 'prerequisite' | 'related' | 'pathway';
  revision: number;
  lifecycle: Record<string, unknown>;
  supported_views?: ViewMode[];
  provenance?: string;
};

export type OverlayTarget = {
  kind: 'node' | 'edge' | 'map';
  id: string;
};

export type OverlayObservation = {
  id: string;
  target: OverlayTarget;
  state: string;
  label: string;
  supported_views: ViewMode[];
  provenance: {
    source: string;
    version?: string | null;
  };
};

export type OverlayLayer = {
  available: boolean;
  observations: OverlayObservation[];
  supported_views: ViewMode[];
  source?: string;
  reason?: 'NO_DATA' | 'NO_AUTHORITY' | 'INVALID_PROVIDER_SCHEMA' | 'OUT_OF_SCOPE';
};

export type MapState = {
  saved: boolean;
  id: string | null;
  name?: string | null;
  state: string;
  state_label?: string;
  reason?: string | null;
  scope?: { kind: string; id: string } | null;
  world?: {
    recipe: string;
    membership: string[];
  } | null;
  canonical_node_ids?: string[];
  visual_positions?: Record<string, { x: number; y: number }>;
  default_view?: ViewMode;
};

export type OverlayState = {
  active: OverlayName | null | string;
  available: OverlayName[];
  layers: Partial<Record<OverlayName, OverlayLayer>>;
};

export type VisualSelection = { kind: 'node'; id: string } | { kind: 'edge'; id: string };

export type VisualizeRouteState = {
  map: string | null;
  view: ViewMode;
  overlay: OverlayName | null;
  filter: VisualFilter;
  selection: VisualSelection | null;
  notice?: string | null;
};

export type GraphState = {
  nodes: VisualNode[];
  edges: VisualEdge[];
  source: string;
  recipe?: string;
};

export type VisualPoint = { x: number; y: number };

export type VisualBounds = {
  x: number;
  y: number;
  width: number;
  height: number;
};

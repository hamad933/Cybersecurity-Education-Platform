import type {
  OverlayName,
  OverlayState,
  ViewMode,
  VisualEdge,
  VisualFilter,
  VisualNode,
  VisualSelection,
  VisualizeRouteState,
} from './types';
import { edgesForView, filteredProjection, selectionExists } from './viewModels';

const views: ViewMode[] = ['Tree', 'Path', 'Graph', 'Canvas'];
const filters: VisualFilter[] = ['all', 'knowledge', 'structure'];

export const selectionToken = (selection: VisualSelection | null): string | null =>
  selection ? `${selection.kind}:${selection.id}` : null;

export const serializeVisualizeState = (
  objectId: string | null,
  state: VisualizeRouteState,
): string => {
  const params = new URLSearchParams();
  if (objectId) params.set('object', objectId);
  if (state.map) params.set('map', state.map);
  params.set('view', state.view);
  if (state.overlay) params.set('overlay', state.overlay);
  if (state.filter !== 'all') params.set('filter', state.filter);
  const token = selectionToken(state.selection);
  if (token) params.set('selection', token);
  return `/knowledge/visualize?${params.toString()}`;
};

export const parseVisualizeLocation = (
  search: string,
  fallback: VisualizeRouteState,
  nodes: VisualNode[],
  edges: VisualEdge[],
  overlay: OverlayState,
): VisualizeRouteState => {
  const params = new URLSearchParams(search);
  const requestedView = params.get('view');
  const view = views.includes(requestedView as ViewMode)
    ? (requestedView as ViewMode)
    : fallback.view;
  const requestedFilter = params.get('filter');
  const filter = filters.includes(requestedFilter as VisualFilter)
    ? (requestedFilter as VisualFilter)
    : 'all';
  const requestedOverlay = params.get('overlay') as OverlayName | null;
  const layer = requestedOverlay ? overlay.layers[requestedOverlay] : null;
  const selectedOverlay =
    requestedOverlay && layer?.available && layer.supported_views.includes(view)
      ? requestedOverlay
      : null;
  const token = params.get('selection');
  const match = token?.match(/^(node|edge):(.+)$/);
  const candidate = match ? ({ kind: match[1], id: match[2] } as VisualSelection) : null;
  const projection = filteredProjection(nodes, edgesForView(edges, view), filter);
  const selection = selectionExists(candidate, projection.nodes, projection.edges)
    ? candidate
    : null;

  return {
    map: params.get('map') === fallback.map ? fallback.map : null,
    view,
    overlay: selectedOverlay,
    filter,
    selection,
    notice: null,
  };
};

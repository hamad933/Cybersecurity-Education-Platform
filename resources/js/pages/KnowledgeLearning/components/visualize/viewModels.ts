import type {
  OverlayLayer,
  OverlayObservation,
  ViewMode,
  VisualBounds,
  VisualEdge,
  VisualFilter,
  VisualNode,
  VisualPoint,
  VisualSelection,
} from './types';

const structuralKinds = new Set(['domain', 'capability_cluster', 'capability']);

export const edgeSupportsView = (edge: VisualEdge, view: ViewMode): boolean => {
  if (edge.supported_views?.length) return edge.supported_views.includes(view);
  if (view === 'Tree') return edge.semantic === 'containment';
  if (view === 'Path') return edge.semantic === 'prerequisite' || edge.semantic === 'pathway';
  return true;
};

export const edgesForView = (edges: VisualEdge[], view: ViewMode): VisualEdge[] =>
  edges.filter((edge) => edgeSupportsView(edge, view));

export const nodeMatchesFilter = (node: VisualNode, filter: VisualFilter): boolean => {
  if (filter === 'knowledge') return node.kind === 'knowledge_unit';
  if (filter === 'structure') return structuralKinds.has(node.kind);
  return true;
};

export const filteredProjection = (
  nodes: VisualNode[],
  edges: VisualEdge[],
  filter: VisualFilter,
): { nodes: VisualNode[]; edges: VisualEdge[] } => {
  const filteredNodes = nodes.filter((node) => nodeMatchesFilter(node, filter));
  const ids = new Set(filteredNodes.map((node) => node.id));
  return {
    nodes: filteredNodes,
    edges: edges.filter((edge) => ids.has(edge.from) && ids.has(edge.to)),
  };
};

export const selectionExists = (
  selection: VisualSelection | null,
  nodes: VisualNode[],
  edges: VisualEdge[],
): boolean => {
  if (!selection) return true;
  return selection.kind === 'node'
    ? nodes.some((node) => node.id === selection.id)
    : edges.some((edge) => edge.id === selection.id);
};

export type TreeBranchModel = {
  node: VisualNode;
  edge: VisualEdge | null;
  children: TreeBranchModel[];
};

export const buildTree = (nodes: VisualNode[], edges: VisualEdge[]): TreeBranchModel[] => {
  const nodeById = new Map(nodes.map((node) => [node.id, node]));
  const structuralEdges = edgesForView(edges, 'Tree');
  const incoming = new Set<string>();
  const childEdges = new Map<string, VisualEdge[]>();

  for (const edge of structuralEdges) {
    if (!nodeById.has(edge.from) || !nodeById.has(edge.to) || incoming.has(edge.to)) continue;
    incoming.add(edge.to);
    childEdges.set(edge.from, [...(childEdges.get(edge.from) ?? []), edge]);
  }

  const build = (
    node: VisualNode,
    edge: VisualEdge | null,
    trail: Set<string>,
  ): TreeBranchModel => {
    if (trail.has(node.id)) return { node, edge, children: [] };
    const nextTrail = new Set(trail).add(node.id);
    const children = (childEdges.get(node.id) ?? [])
      .map((childEdge) => {
        const child = nodeById.get(childEdge.to);
        return child ? build(child, childEdge, nextTrail) : null;
      })
      .filter((branch): branch is TreeBranchModel => branch !== null);
    return { node, edge, children };
  };

  return nodes.filter((node) => !incoming.has(node.id)).map((node) => build(node, null, new Set()));
};

export type PathStage = {
  index: number;
  nodes: VisualNode[];
  incoming: VisualEdge[];
};

export const derivePathStages = (nodes: VisualNode[], edges: VisualEdge[]): PathStage[] => {
  const pathEdges = edgesForView(edges, 'Path');
  if (!pathEdges.length) return [];

  const nodeById = new Map(nodes.map((node) => [node.id, node]));
  const memberIds = new Set(pathEdges.flatMap((edge) => [edge.from, edge.to]));
  const indegree = new Map([...memberIds].map((id) => [id, 0]));
  const outgoing = new Map<string, VisualEdge[]>();
  for (const edge of pathEdges) {
    indegree.set(edge.to, (indegree.get(edge.to) ?? 0) + 1);
    outgoing.set(edge.from, [...(outgoing.get(edge.from) ?? []), edge]);
  }

  let frontier = [...memberIds].filter((id) => (indegree.get(id) ?? 0) === 0).sort();
  const visited = new Set<string>();
  const stages: PathStage[] = [];

  while (frontier.length) {
    const current = frontier;
    frontier = [];
    const currentSet = new Set(current);
    stages.push({
      index: stages.length,
      nodes: current.map((id) => nodeById.get(id)).filter((node): node is VisualNode => !!node),
      incoming: pathEdges.filter((edge) => currentSet.has(edge.to)),
    });
    for (const id of current) {
      visited.add(id);
      for (const edge of outgoing.get(id) ?? []) {
        const next = (indegree.get(edge.to) ?? 1) - 1;
        indegree.set(edge.to, next);
        if (next === 0) frontier.push(edge.to);
      }
    }
    frontier = [...new Set(frontier)].sort();
  }

  const cyclic = [...memberIds].filter((id) => !visited.has(id));
  if (cyclic.length) {
    stages.push({
      index: stages.length,
      nodes: cyclic.map((id) => nodeById.get(id)).filter((node): node is VisualNode => !!node),
      incoming: pathEdges.filter((edge) => cyclic.includes(edge.to)),
    });
  }
  return stages;
};

export type GraphLayout = {
  positions: Record<string, VisualPoint>;
  bounds: VisualBounds;
};

export const layoutFocusedGraph = (
  nodes: VisualNode[],
  edges: VisualEdge[],
  selectedNodeId: string | null,
): GraphLayout => {
  const focus =
    nodes.find((node) => node.id === selectedNodeId) ??
    nodes.find((node) => node.kind === 'knowledge_unit') ??
    nodes[0];
  const positions: Record<string, VisualPoint> = {};
  if (!focus) return { positions, bounds: { x: 0, y: 0, width: 960, height: 560 } };

  const inbound = [
    ...new Set(edges.filter((edge) => edge.to === focus.id).map((edge) => edge.from)),
  ];
  const outbound = [
    ...new Set(edges.filter((edge) => edge.from === focus.id).map((edge) => edge.to)),
  ];
  const connected = new Set([focus.id, ...inbound, ...outbound]);
  const remaining = nodes.filter((node) => !connected.has(node.id));
  const spread = (ids: string[], x: number, top: number, height: number) => {
    ids.forEach((id, index) => {
      positions[id] = { x, y: top + ((index + 1) * height) / (ids.length + 1) };
    });
  };

  positions[focus.id] = { x: 480, y: 250 };
  spread(inbound, 130, 20, 430);
  spread(outbound, 830, 20, 430);
  remaining.forEach((node, index) => {
    positions[node.id] = { x: 220 + (index % 4) * 190, y: 500 + Math.floor(index / 4) * 120 };
  });

  const maxY = Math.max(560, ...Object.values(positions).map((point) => point.y + 90));
  return { positions, bounds: { x: 0, y: 0, width: 960, height: maxY } };
};

export const observationsForView = (
  layer: OverlayLayer | null,
  view: ViewMode,
): OverlayObservation[] => {
  if (!layer?.available || !layer.supported_views.includes(view)) return [];
  return layer.observations.filter((observation) => observation.supported_views.includes(view));
};

export const observationTargets = (
  layer: OverlayLayer | null,
  view: ViewMode,
): { nodes: Set<string>; edges: Set<string> } => {
  const observations = observationsForView(layer, view);
  return {
    nodes: new Set(
      observations.filter((item) => item.target.kind === 'node').map((item) => item.target.id),
    ),
    edges: new Set(
      observations.filter((item) => item.target.kind === 'edge').map((item) => item.target.id),
    ),
  };
};

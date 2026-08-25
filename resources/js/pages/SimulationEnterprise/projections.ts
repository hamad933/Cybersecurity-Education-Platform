import type {
  DigitalTwinRevisionItem,
  JsonMap,
  OrderedDefinitionItem,
  TopologyLink,
  TopologyNode,
} from './types';

export function asMap(value: unknown): JsonMap {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
    ? (value as JsonMap)
    : {};
}

export function asList(value: unknown): unknown[] {
  return Array.isArray(value) ? value : [];
}

function textValue(value: unknown, fallback: string): string {
  return typeof value === 'string' || typeof value === 'number' ? String(value) : fallback;
}

export function topologyNodes(revision: DigitalTwinRevisionItem | null): TopologyNode[] {
  if (!revision) return [];

  return asList(revision.topology.nodes).map((value, index) => {
    const node = asMap(value);
    const id = textValue(node.id, `node-${index + 1}`);
    return {
      id,
      label: textValue(node.label ?? node.name, id),
      kind: textValue(node.kind ?? node.type, 'node'),
      raw: node,
    };
  });
}

export function topologyLinks(revision: DigitalTwinRevisionItem | null): TopologyLink[] {
  if (!revision) return [];

  return asList(revision.topology.links)
    .map((value) => {
      const link = asMap(value);
      const from = textValue(link.from ?? link.source, '');
      const to = textValue(link.to ?? link.target, '');
      if (!from || !to) return null;
      const label = textValue(link.label ?? link.kind ?? link.type, '');
      return { from, to, label: label || null };
    })
    .filter((link): link is TopologyLink => link !== null);
}

export function orderedItems(value: unknown, prefix: string): OrderedDefinitionItem[] {
  return asList(value).map((entry, index) => {
    const item = asMap(entry);
    const ordinal = index + 1;
    const label = textValue(
      typeof entry === 'object'
        ? (item.label ?? item.name ?? item.title ?? item.key ?? item.id)
        : entry,
      `${prefix} ${ordinal}`,
    );
    return {
      id: textValue(item.id ?? item.key, `${prefix.toLowerCase()}-${ordinal}`),
      label,
      ordinal,
      raw: entry,
    };
  });
}

export function displayValue(value: unknown): string {
  if (value === null || value === undefined) return '—';
  if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
    return String(value);
  }
  return JSON.stringify(value);
}

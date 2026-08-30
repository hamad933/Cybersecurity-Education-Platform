export type LibraryProjectionItem = {
  canonical_ref: {
    kind: 'knowledge_unit';
    id: string;
  };
  title_ar: string;
  title_en: string;
  latest_revision: number | null;
  latest_state: string | null;
  revision_count: number;
  published_revision: number | null;
  lesson_availability: string;
  projection_reason: 'curriculum_placement' | 'unplaced_canonical_object';
  placement: {
    id: string | null;
    revision: number | null;
    lifecycle: Record<string, unknown>;
  } | null;
};

export type LibraryCapabilityNode = {
  id: string;
  title_ar: string;
  title_en: string | null;
  items: LibraryProjectionItem[];
};

export type LibraryCapabilityClusterNode = {
  id: string;
  title_ar: string;
  title_en: string | null;
  capabilities: LibraryCapabilityNode[];
};

export type LibraryDomainNode = {
  id: string;
  title_ar: string;
  title_en: string | null;
  clusters: LibraryCapabilityClusterNode[];
};

export type LibraryUnresolvedCapability = {
  capability_id: string;
  integration_state: 'missing_hierarchy_context';
  items: LibraryProjectionItem[];
};

export type LibraryHierarchyProjection = {
  domains: LibraryDomainNode[];
  unresolved_capabilities: LibraryUnresolvedCapability[];
  unplaced: LibraryProjectionItem[];
};

export type LessonBlock = {
  type: string;
  body: string;
  depth: number;
};

export type StoredLessonBlock = {
  type: string;
  body: string;
  depth?: number;
};

export type LessonRevision = {
  id: string;
  revision: number;
  state: string;
  lock_version: number;
  blocks: StoredLessonBlock[];
  citations: string[];
  authority_baseline_id: string | null;
  content_digest: string;
  derived_from_revision_id: string | null;
  published_at: string | null;
  updated_at: string | null;
  editable: boolean;
};

export type LessonBlockDefinition = {
  type: string;
  label_ar: string;
  label_en: string;
  semantic_role: string;
  direction: string;
  technical: boolean;
};

export type LessonContentContract = {
  version: string;
  canonical_owner: 'knowledge';
  identity: {
    canonical_object: 'knowledge_unit';
    content_record: 'lesson_revision';
    lesson_projection: string;
  };
  block_registry: LessonBlockDefinition[];
  constraints: {
    max_blocks: number;
    max_body_length: number;
    max_depth: number;
    first_block_depth: number;
    max_depth_step: number;
  };
  citation: {
    pattern: string;
    examples: string[];
    min_items: number;
    max_items: number;
    max_length: number;
  };
  revision_semantics: {
    states: string[];
    mutable_states: string[];
    learn_delivery_states: string[];
    library_selection_policy: string;
    learn_selection_policy: string;
    published_history_mutation: string;
    restore_policy: string;
  };
};

export type KnowledgeUnitSelection = {
  requested_id: string | null;
  resolved_id: string | null;
  state: string;
};

export type LessonDelivery = {
  availability: string;
  selection_policy: string;
  revision: LessonRevision | null;
  unavailable_reason: string | null;
};

export type InlineToken = {
  kind: 'text' | 'strong' | 'emphasis' | 'code' | 'link';
  text: string;
  href?: string;
};

export const normalizeLessonBlock = (block: StoredLessonBlock): LessonBlock => ({
  type: block.type,
  body: block.body,
  depth: Number.isInteger(block.depth) ? (block.depth as number) : 0,
});

export const normalizeLessonBlocks = (blocks: StoredLessonBlock[]): LessonBlock[] =>
  blocks.map(normalizeLessonBlock);

export const lessonBlockDefinition = (
  contract: LessonContentContract,
  type: string,
): LessonBlockDefinition | null =>
  contract.block_registry.find((definition) => definition.type === type) ?? null;

export const isTechnicalLessonBlock = (contract: LessonContentContract, type: string): boolean =>
  lessonBlockDefinition(contract, type)?.technical ?? false;

export const isValidLessonHierarchy = (
  blocks: LessonBlock[],
  contract: LessonContentContract,
): boolean => {
  if (
    !blocks.length ||
    blocks.length > contract.constraints.max_blocks ||
    blocks[0]?.depth !== contract.constraints.first_block_depth
  ) {
    return false;
  }

  const registeredTypes = new Set(contract.block_registry.map((definition) => definition.type));

  return blocks.every((block, index) => {
    if (!registeredTypes.has(block.type)) return false;
    if (
      !Number.isInteger(block.depth) ||
      block.depth < 0 ||
      block.depth > contract.constraints.max_depth ||
      block.body.trim().length < 1 ||
      block.body.length > contract.constraints.max_body_length
    ) {
      return false;
    }
    if (index === 0) return block.depth === contract.constraints.first_block_depth;

    const previous = blocks[index - 1];
    return Boolean(previous) && block.depth <= previous.depth + contract.constraints.max_depth_step;
  });
};

export const citationMatchesContract = (
  citation: string,
  contract: LessonContentContract,
): boolean => {
  try {
    return (
      citation.length <= contract.citation.max_length &&
      new RegExp(contract.citation.pattern, 'u').test(citation)
    );
  } catch {
    return false;
  }
};

export const areValidLessonCitations = (
  citations: string[],
  contract: LessonContentContract,
): boolean =>
  citations.length >= contract.citation.min_items &&
  citations.length <= contract.citation.max_items &&
  new Set(citations).size === citations.length &&
  citations.every((citation) => citationMatchesContract(citation, contract));

export const isValidLessonContent = (
  blocks: LessonBlock[],
  citations: string[],
  contract: LessonContentContract,
): boolean =>
  isValidLessonHierarchy(blocks, contract) && areValidLessonCitations(citations, contract);

export const safeHttpsUrl = (candidate: string): string | null => {
  try {
    const parsed = new URL(candidate);
    return parsed.protocol === 'https:' ? parsed.toString() : null;
  } catch {
    return null;
  }
};

export const inlineLessonTokens = (body: string): InlineToken[] => {
  // This bounded presentation grammar never interprets HTML or active content.
  // prettier-ignore
  const pattern = /(\*\*[^*\n]+\*\*|_[^_\n]+_|`[^`\n]+`|\[[^\]\n]+\]\(https:\/\/[^)\s]+\))/g;
  const tokens: InlineToken[] = [];
  let offset = 0;

  for (const match of body.matchAll(pattern)) {
    const index = match.index ?? 0;
    if (index > offset) tokens.push({ kind: 'text', text: body.slice(offset, index) });

    const raw = match[0];
    const link = raw.match(/^\[([^\]]+)\]\((https:\/\/[^)\s]+)\)$/);
    const href = link?.[2] ? safeHttpsUrl(link[2]) : null;
    if (link?.[1] && href) {
      tokens.push({ kind: 'link', text: link[1], href });
    } else if (raw.startsWith('**') && raw.endsWith('**')) {
      tokens.push({ kind: 'strong', text: raw.slice(2, -2) });
    } else if (raw.startsWith('_') && raw.endsWith('_')) {
      tokens.push({ kind: 'emphasis', text: raw.slice(1, -1) });
    } else if (raw.startsWith('`') && raw.endsWith('`')) {
      tokens.push({ kind: 'code', text: raw.slice(1, -1) });
    } else {
      tokens.push({ kind: 'text', text: raw });
    }

    offset = index + raw.length;
  }

  if (offset < body.length) tokens.push({ kind: 'text', text: body.slice(offset) });
  return tokens.length ? tokens : [{ kind: 'text', text: body }];
};

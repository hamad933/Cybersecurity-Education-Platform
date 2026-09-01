// Generates a 24-character string using alphanumeric characters, underscore, and dash
export const generateStableId = (): string => {
  const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_-';
  const bytes = new Uint8Array(24);
  if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
    crypto.getRandomValues(bytes);
  } else {
    for (let index = 0; index < bytes.length; index += 1) {
      bytes[index] = Math.floor(Math.random() * 256);
    }
  }
  let id = '';
  for (const byte of bytes) {
    id += chars.charAt(byte % chars.length);
  }
  return id;
};

export type LessonBlock = {
  id: string;
  type: string;
  body: string;
  depth: number;
};

export type StoredLessonBlock = {
  id?: string;
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

const legacyBlockId = (block: StoredLessonBlock, index: number): string => {
  const input = `${index}\u001f${block.type}\u001f${block.depth ?? 0}\u001f${block.body}`;
  let first = 0x811c9dc5;
  let second = 0x9e3779b9;
  let third = 0x85ebca6b;
  for (let cursor = 0; cursor < input.length; cursor += 1) {
    const value = input.charCodeAt(cursor);
    first = Math.imul(first ^ value, 0x01000193) >>> 0;
    second = Math.imul(second ^ value, 0x5bd1e995) >>> 0;
    third = Math.imul(third ^ value, 0x27d4eb2d) >>> 0;
  }
  const token = `${first.toString(16).padStart(8, '0')}${second
    .toString(16)
    .padStart(8, '0')}${third.toString(16).padStart(8, '0')}`;
  return `legacy_${token.slice(0, 17)}`;
};

export const normalizeLessonBlock = (block: StoredLessonBlock, index = 0): LessonBlock => ({
  id: block.id ?? legacyBlockId(block, index),
  type: block.type,
  body: block.body,
  depth: Number.isInteger(block.depth) ? (block.depth as number) : 0,
});

export const normalizeLessonBlocks = (blocks: StoredLessonBlock[]): LessonBlock[] =>
  blocks.map((block, index) => normalizeLessonBlock(block, index));

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
  const blockIds = new Set<string>();

  return blocks.every((block, index) => {
    if (!registeredTypes.has(block.type)) return false;
    if (!/^[0-9a-zA-Z_-]{24}$/.test(block.id) || blockIds.has(block.id)) return false;
    blockIds.add(block.id);
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

export type LessonBlockComparisonState = 'unchanged' | 'modified' | 'moved' | 'added' | 'removed';

export type LessonBlockComparisonRow = {
  id: string;
  current: LessonBlock | null;
  compared: LessonBlock | null;
  currentIndex: number | null;
  comparedIndex: number | null;
  state: LessonBlockComparisonState;
};

export const compareLessonBlocks = (
  currentBlocks: StoredLessonBlock[],
  comparedBlocks: StoredLessonBlock[],
): LessonBlockComparisonRow[] => {
  const current = normalizeLessonBlocks(currentBlocks);
  const compared = normalizeLessonBlocks(comparedBlocks);
  const comparedById = new Map(compared.map((block, index) => [block.id, { block, index }]));
  const matchedCompared = new Set<number>();
  const rows: LessonBlockComparisonRow[] = [];
  const unmatchedCurrent: Array<{ block: LessonBlock; index: number }> = [];

  current.forEach((block, index) => {
    const match = comparedById.get(block.id);
    if (!match) {
      unmatchedCurrent.push({ block, index });
      return;
    }
    matchedCompared.add(match.index);
    const contentChanged =
      block.type !== match.block.type ||
      block.body !== match.block.body ||
      block.depth !== match.block.depth;
    rows.push({
      id: block.id,
      current: block,
      compared: match.block,
      currentIndex: index,
      comparedIndex: match.index,
      state: contentChanged ? 'modified' : index !== match.index ? 'moved' : 'unchanged',
    });
  });

  const unmatchedCompared = compared
    .map((block, index) => ({ block, index }))
    .filter(({ index }) => !matchedCompared.has(index));

  // Legacy revisions have no shared persisted ID. Pair bounded unmatched rows
  // by semantic type and nearest order before reporting additions/removals.
  unmatchedCurrent.forEach(({ block, index }) => {
    const candidateIndex = block.id.startsWith('legacy_')
      ? unmatchedCompared.findIndex(
          ({ block: candidate }) =>
            candidate.id.startsWith('legacy_') && candidate.type === block.type,
        )
      : -1;
    if (candidateIndex < 0) {
      rows.push({
        id: block.id,
        current: block,
        compared: null,
        currentIndex: index,
        comparedIndex: null,
        state: 'added',
      });
      return;
    }
    const [match] = unmatchedCompared.splice(candidateIndex, 1);
    if (!match) return;
    rows.push({
      id: block.id,
      current: block,
      compared: match.block,
      currentIndex: index,
      comparedIndex: match.index,
      state:
        block.body !== match.block.body || block.depth !== match.block.depth
          ? 'modified'
          : index !== match.index
            ? 'moved'
            : 'unchanged',
    });
  });

  unmatchedCompared.forEach(({ block, index }) => {
    rows.push({
      id: block.id,
      current: null,
      compared: block,
      currentIndex: null,
      comparedIndex: index,
      state: 'removed',
    });
  });

  return rows.sort(
    (left, right) =>
      (left.currentIndex ?? Number.MAX_SAFE_INTEGER) -
        (right.currentIndex ?? Number.MAX_SAFE_INTEGER) ||
      (left.comparedIndex ?? Number.MAX_SAFE_INTEGER) -
        (right.comparedIndex ?? Number.MAX_SAFE_INTEGER),
  );
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

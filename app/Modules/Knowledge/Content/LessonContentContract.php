<?php

namespace App\Modules\Knowledge\Content;

use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * The single Knowledge-owned contract for canonical lesson revision content.
 *
 * Library edits this content, Learn projects published content from it, and
 * contextual surfaces reference its stable Knowledge Unit / revision identity.
 */
final class LessonContentContract
{
    public const VERSION = 'CEP_W02_LESSON_CONTENT_V2';

    public const MAX_BLOCKS = 64;

    public const MAX_BLOCK_BODY_LENGTH = 4000;

    public const MAX_BLOCK_DEPTH = 3;

    public const MIN_CITATIONS = 1;

    public const MAX_CITATIONS = 64;

    public const MAX_CITATION_LENGTH = 80;

    public const CITATION_PATTERN = '/^(?:(?:WIN|WEB|VS3)-AUTH-\d{3}|KU-D\d{2}-\d{4}-CLM-\d{4}|VS\d{3}-SRC-\d{3}R?-\d{2})$/';

    public const CITATION_PATTERN_JAVASCRIPT = '^(?:(?:WIN|WEB|VS3)-AUTH-\\d{3}|KU-D\\d{2}-\\d{4}-CLM-\\d{4}|VS\\d{3}-SRC-\\d{3}R?-\\d{2})$';

    /** @var list<array{type: string, label_ar: string, label_en: string, semantic_role: string, direction: string, technical: bool}> */
    private const BLOCK_REGISTRY = [
        ['type' => 'heading', 'label_ar' => 'عنوان فرعي', 'label_en' => 'Heading', 'semantic_role' => 'structure', 'direction' => 'auto', 'technical' => false],
        ['type' => 'paragraph', 'label_ar' => 'فقرة', 'label_en' => 'Paragraph', 'semantic_role' => 'explanation', 'direction' => 'auto', 'technical' => false],
        ['type' => 'callout', 'label_ar' => 'ملاحظة بارزة', 'label_en' => 'Callout', 'semantic_role' => 'emphasis', 'direction' => 'auto', 'technical' => false],
        ['type' => 'rules', 'label_ar' => 'قواعد وضوابط', 'label_en' => 'Rules', 'semantic_role' => 'governed_rules', 'direction' => 'auto', 'technical' => false],
        ['type' => 'boundaries', 'label_ar' => 'حدود وسياق', 'label_en' => 'Boundaries', 'semantic_role' => 'scope_boundary', 'direction' => 'auto', 'technical' => false],
        ['type' => 'code', 'label_ar' => 'شفرة برمجية', 'label_en' => 'Code', 'semantic_role' => 'technical_example', 'direction' => 'ltr', 'technical' => true],
        ['type' => 'request', 'label_ar' => 'طلب', 'label_en' => 'Request', 'semantic_role' => 'technical_request', 'direction' => 'ltr', 'technical' => true],
        ['type' => 'response', 'label_ar' => 'استجابة', 'label_en' => 'Response', 'semantic_role' => 'technical_response', 'direction' => 'ltr', 'technical' => true],
        ['type' => 'log', 'label_ar' => 'سجل', 'label_en' => 'Log', 'semantic_role' => 'technical_log', 'direction' => 'ltr', 'technical' => true],
    ];

    /** @return list<string> */
    public function blockTypes(): array
    {
        return array_column(self::BLOCK_REGISTRY, 'type');
    }

    /** @return array<string, mixed> */
    public function manifest(): array
    {
        return [
            'version' => self::VERSION,
            'canonical_owner' => 'knowledge',
            'identity' => [
                'canonical_object' => 'knowledge_unit',
                'content_record' => 'lesson_revision',
                'lesson_projection' => 'knowledge_unit_revision_not_independent_canonical_copy',
            ],
            'block_registry' => self::BLOCK_REGISTRY,
            'constraints' => [
                'max_blocks' => self::MAX_BLOCKS,
                'max_body_length' => self::MAX_BLOCK_BODY_LENGTH,
                'max_depth' => self::MAX_BLOCK_DEPTH,
                'first_block_depth' => 0,
                'max_depth_step' => 1,
            ],
            'citation' => [
                'pattern' => self::CITATION_PATTERN_JAVASCRIPT,
                'examples' => ['KU-D05-0021-CLM-0001', 'WEB-AUTH-001', 'VS3-AUTH-001'],
                'min_items' => self::MIN_CITATIONS,
                'max_items' => self::MAX_CITATIONS,
                'max_length' => self::MAX_CITATION_LENGTH,
            ],
            'revision_semantics' => [
                'states' => ['draft', 'under_review', 'reviewed', 'published'],
                'mutable_states' => ['draft'],
                'learn_delivery_states' => ['published'],
                'library_selection_policy' => 'explicit_revision_or_latest_revision',
                'learn_selection_policy' => 'latest_published_revision_only',
                'published_history_mutation' => 'prohibited',
                'restore_policy' => 'restore_as_new_draft_revision',
            ],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public function requestValidationRules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:1'],
            'blocks' => ['required', 'array', 'min:1', 'max:'.self::MAX_BLOCKS],
            'blocks.*' => ['array:type,body,depth,id'],
            // Legacy callers may omit the V2 identity. validateAndNormalize()
            // deterministically upgrades those blocks without rewriting history.
            'blocks.*.id' => ['sometimes', 'string', 'size:24', 'regex:/^[0-9a-zA-Z_-]{24}$/'],
            'blocks.*.type' => ['required', Rule::in($this->blockTypes())],
            'blocks.*.body' => ['required', 'string', 'max:'.self::MAX_BLOCK_BODY_LENGTH],
            'blocks.*.depth' => ['required', 'integer', 'min:0', 'max:'.self::MAX_BLOCK_DEPTH],
            'citations' => ['required', 'array', 'min:'.self::MIN_CITATIONS, 'max:'.self::MAX_CITATIONS],
            // Semantic format and duplicate checks stay in validateAndNormalize()
            // so the frozen Editor keeps its reviewed revision-level error path.
            'citations.*' => ['required', 'string', 'max:'.self::MAX_CITATION_LENGTH],
        ];
    }

    /**
     * @param  array<mixed>  $blocks
     * @return list<array<string, mixed>>
     */
    public function normalizeStoredBlocks(array $blocks): array
    {
        $normalized = [];
        $usedIds = [];
        foreach ($blocks as $index => $block) {
            if (! is_array($block)) {
                throw new InvalidArgumentException('Lesson block must be an object.');
            }

            // V1 compatibility: derive a repeatable presentation identity. The
            // stored historical revision and its digest remain untouched; a
            // restored/edited draft persists this identity under Contract V2.
            if (! array_key_exists('id', $block)) {
                $block['id'] = $this->legacyBlockId($block, $index);
            }

            if (is_string($block['id']) && isset($usedIds[$block['id']])) {
                throw new InvalidArgumentException('Duplicate lesson block ID.');
            }
            if (is_string($block['id'])) {
                $usedIds[$block['id']] = true;
            }

            // Historical revisions created before structural editing may omit
            // depth. Preserve every supplied value so validation cannot turn a
            // malformed value into an apparently valid integer.
            $block['depth'] = array_key_exists('depth', $block) ? $block['depth'] : 0;
            $normalized[] = $block;
        }

        return $normalized;
    }

    /** @param array<string, mixed> $block */
    private function legacyBlockId(array $block, int $index): string
    {
        $fingerprint = json_encode([
            'index' => $index,
            'type' => $block['type'] ?? null,
            'body' => $block['body'] ?? null,
            'depth' => $block['depth'] ?? 0,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $token = rtrim(strtr(base64_encode(hash('sha256', $fingerprint, true)), '+/', '-_'), '=');

        return 'legacy_'.substr($token, 0, 17);
    }

    /**
     * @param  array<mixed>  $blocks
     * @param  array<mixed>  $citations
     * @return array{blocks: list<array<string, mixed>>, citations: list<string>}
     */
    public function validateAndNormalize(array $blocks, array $citations): array
    {
        if (! array_is_list($blocks) || count($blocks) < 1 || count($blocks) > self::MAX_BLOCKS) {
            throw new InvalidArgumentException('Lesson blocks must be a bounded non-empty list.');
        }
        if (! array_is_list($citations) || count($citations) < self::MIN_CITATIONS || count($citations) > self::MAX_CITATIONS) {
            throw new InvalidArgumentException('Lesson citations must be a bounded non-empty list.');
        }

        $normalizedBlocks = $this->normalizeStoredBlocks($blocks);
        $previousDepth = -1;

        foreach ($normalizedBlocks as $index => $block) {
            if (! in_array($block['type'] ?? null, $this->blockTypes(), true)) {
                throw new InvalidArgumentException('Unregistered lesson block type.');
            }
            if (array_diff(array_keys($block), ['type', 'body', 'depth', 'id']) !== []) {
                throw new InvalidArgumentException('Unknown lesson block key.');
            }
            if (! is_string($block['body'] ?? null) || trim($block['body']) === '' || mb_strlen($block['body']) > self::MAX_BLOCK_BODY_LENGTH) {
                throw new InvalidArgumentException('Lesson block body is invalid or too large.');
            }

            if (! is_string($block['id'] ?? null) || preg_match('/^[0-9a-zA-Z_-]{24}$/', $block['id']) !== 1) {
                throw new InvalidArgumentException('Lesson block ID must be a 24-character stable string.');
            }
            $depth = $block['depth'];
            if (! is_int($depth) || $depth < 0 || $depth > self::MAX_BLOCK_DEPTH) {
                throw new InvalidArgumentException('Lesson block depth must be an integer between 0 and 3.');
            }
            if ($index === 0 && $depth !== 0) {
                throw new InvalidArgumentException('First block must have depth 0.');
            }
            if ($index > 0 && $depth > $previousDepth + 1) {
                throw new InvalidArgumentException('Block depth cannot jump by more than 1 from previous block.');
            }
            $previousDepth = $depth;

            $proseBlock = in_array($block['type'], ['heading', 'paragraph', 'callout', 'rules', 'boundaries'], true);
            if ($proseBlock && preg_match('/<\s*script\b|\bon[a-z]+\s*=|javascript\s*:/iu', $block['body']) === 1) {
                throw new InvalidArgumentException('Unsafe active lesson content is rejected.');
            }
        }

        $normalizedCitations = [];
        foreach ($citations as $claimId) {
            if (! is_string($claimId) || mb_strlen($claimId) > self::MAX_CITATION_LENGTH || preg_match(self::CITATION_PATTERN, $claimId) !== 1) {
                throw new InvalidArgumentException('Citation claim ID is invalid.');
            }
            if (in_array($claimId, $normalizedCitations, true)) {
                throw new InvalidArgumentException('Duplicate citation claim IDs are not allowed.');
            }
            $normalizedCitations[] = $claimId;
        }

        return ['blocks' => $normalizedBlocks, 'citations' => $normalizedCitations];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  list<string>  $citations
     */
    public function contentDigest(array $blocks, array $citations): string
    {
        return hash('sha256', json_encode(
            ['blocks' => $blocks, 'citations' => $citations],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ));
    }
}

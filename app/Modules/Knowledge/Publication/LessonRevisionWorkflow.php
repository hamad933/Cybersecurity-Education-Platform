<?php

namespace App\Modules\Knowledge\Publication;

use App\Modules\Knowledge\Models\LessonRevision;
use App\Modules\Platform\Audit\AuditWriter;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

final class LessonRevisionWorkflow
{
    public function __construct(private readonly AuditWriter $audit) {}

    /**
     * @param  array<mixed>  $blocks
     * @param  array<mixed>  $citations
     */
    public function createDraft(string $knowledgeUnitId, array $blocks, array $citations, ?string $derivedFrom = null, ?string $actorId = null, ?string $authorityBaselineId = null): LessonRevision
    {
        $blocks = $this->normalizeBlocks($blocks);
        $this->validateContent($blocks, $citations);
        $revision = ((int) LessonRevision::query()->where('knowledge_unit_id', $knowledgeUnitId)->max('revision')) + 1;

        $draft = LessonRevision::query()->create([
            'knowledge_unit_id' => $knowledgeUnitId,
            'revision' => $revision,
            'state' => 'draft',
            'lock_version' => 1,
            'blocks' => $blocks,
            'citations' => $citations,
            'content_digest' => $this->contentDigest($blocks, $citations),
            'derived_from_revision_id' => $derivedFrom,
            'authority_baseline_id' => $authorityBaselineId,
        ]);
        $this->recordAudit('lesson.draft.created', $draft, $actorId, ['revision' => $revision, 'derived' => $derivedFrom !== null]);

        return $draft;
    }

    /**
     * @param  array<mixed>  $blocks
     * @param  array<mixed>  $citations
     */
    public function updateDraft(string $id, int $expectedLockVersion, array $blocks, array $citations, ?string $actorId = null): LessonRevision
    {
        $blocks = $this->normalizeBlocks($blocks);
        $this->validateContent($blocks, $citations);
        $updated = LessonRevision::query()
            ->whereKey($id)
            ->where('state', 'draft')
            ->where('lock_version', $expectedLockVersion)
            ->update([
                'blocks' => json_encode($blocks, JSON_THROW_ON_ERROR),
                'citations' => json_encode($citations, JSON_THROW_ON_ERROR),
                'content_digest' => $this->contentDigest($blocks, $citations),
                'lock_version' => $expectedLockVersion + 1,
                'updated_at' => now(),
            ]);
        if ($updated !== 1) {
            throw new RuntimeException('Optimistic concurrency conflict or non-draft revision.');
        }

        $revision = LessonRevision::query()->findOrFail($id);
        $this->recordAudit('lesson.draft.updated', $revision, $actorId, ['lock_version' => $revision->lock_version]);

        return $revision;
    }

    public function submitForReview(string $id, ?string $actorId = null): LessonRevision
    {
        return DB::transaction(function () use ($id, $actorId): LessonRevision {
            $revision = LessonRevision::query()->lockForUpdate()->findOrFail($id);
            if ($revision->state !== 'draft') {
                throw new LogicException('Only a draft can be submitted.');
            }
            $revision->forceFill(['state' => 'under_review', 'lock_version' => $revision->lock_version + 1])->save();
            $this->recordAudit('lesson.review.submitted', $revision, $actorId);

            return $revision;
        });
    }

    public function bindAuthorityBaseline(string $id, int $expectedLockVersion, string $authorityBaselineId, ?string $actorId = null): LessonRevision
    {
        $updated = LessonRevision::query()
            ->whereKey($id)
            ->where('state', 'draft')
            ->where('lock_version', $expectedLockVersion)
            ->update([
                'authority_baseline_id' => $authorityBaselineId,
                'lock_version' => $expectedLockVersion + 1,
                'updated_at' => now(),
            ]);
        if ($updated !== 1) {
            throw new RuntimeException('Optimistic concurrency conflict or non-draft revision.');
        }

        $revision = LessonRevision::query()->findOrFail($id);
        $this->recordAudit('lesson.authority.bound', $revision, $actorId, ['authority_baseline_id' => $authorityBaselineId]);

        return $revision;
    }

    public function review(string $id, string $decision, string $reviewerId, ?string $rationale = null): LessonRevision
    {
        if (! in_array($decision, ['APPROVED', 'REJECTED', 'RETURNED'], true)) {
            throw new InvalidArgumentException('Unsupported review decision.');
        }
        if ($decision === 'RETURNED' && (trim((string) $rationale) === '' || mb_strlen((string) $rationale) > 1000)) {
            throw new InvalidArgumentException('A bounded rationale is required when returning a revision.');
        }

        return DB::transaction(function () use ($id, $decision, $reviewerId, $rationale): LessonRevision {
            $revision = LessonRevision::query()->lockForUpdate()->findOrFail($id);
            if ($revision->state !== 'under_review') {
                throw new LogicException('Only an under-review revision can be decided.');
            }
            $revision->forceFill([
                'state' => $decision === 'APPROVED' ? 'reviewed' : 'draft',
                'review_decision' => $decision,
                'review_rationale' => $rationale !== null ? trim($rationale) : null,
                'reviewed_by' => $reviewerId,
                'lock_version' => $revision->lock_version + 1,
            ])->save();
            $this->recordAudit('lesson.review.decided', $revision, $reviewerId, ['decision' => $decision]);

            return $revision;
        });
    }

    /** @param list<string> $approvedTechnicalClaims */
    public function publish(string $id, string $publisherId, array $approvedTechnicalClaims): LessonRevision
    {
        return DB::transaction(function () use ($id, $publisherId, $approvedTechnicalClaims): LessonRevision {
            $revision = LessonRevision::query()->lockForUpdate()->findOrFail($id);
            if ($revision->state !== 'reviewed' || $revision->review_decision !== 'APPROVED') {
                throw new LogicException('Publication requires an approved reviewed revision.');
            }
            $isVs002 = $revision->knowledge_unit_id === config('vs002.knowledge_unit_id');
            $expectedBaseline = $isVs002 ? config('vs002.authority_baseline_id') : config('vs001.authority_baseline_id');
            if ($revision->authority_baseline_id !== $expectedBaseline) {
                throw new LogicException('Publication is blocked until the approved authority baseline is bound.');
            }
            $required = $isVs002
                ? config('vs002.required_claim_ids')
                : ['WIN-AUTH-002', 'WIN-AUTH-003', 'WIN-AUTH-004', 'WIN-AUTH-005', 'WIN-AUTH-006', 'WIN-AUTH-007'];
            foreach ($required as $claim) {
                if (! in_array($claim, $revision->citationIds(), true) || ! in_array($claim, $approvedTechnicalClaims, true)) {
                    throw new LogicException("Publication is blocked by missing technical authority claim {$claim}.");
                }
            }
            $revision->forceFill([
                'state' => 'published',
                'published_by' => $publisherId,
                'published_at' => now(),
                'lock_version' => $revision->lock_version + 1,
            ])->save();
            $this->recordAudit('lesson.published', $revision, $publisherId, ['revision' => $revision->revision]);

            return $revision;
        });
    }

    public function restoreAsDraft(string $publishedRevisionId, ?string $actorId = null): LessonRevision
    {
        $published = LessonRevision::query()->findOrFail($publishedRevisionId);
        if ($published->state !== 'published') {
            throw new LogicException('Only a published revision can be restored as a new draft.');
        }

        return $this->createDraft(
            $published->knowledge_unit_id,
            $published->blockList(),
            $published->citationIds(),
            $published->id,
            $actorId,
            $published->authority_baseline_id,
        );
    }

    /**
     * @param  array<mixed>  $blocks
     * @return array<mixed>
     */
    private function normalizeBlocks(array $blocks): array
    {
        return array_map(function ($block) {
            if (is_array($block)) {
                $block['depth'] = isset($block['depth']) ? (int) $block['depth'] : 0;
            }

            return $block;
        }, $blocks);
    }

    /**
     * @param  array<mixed>  $blocks
     * @param  array<mixed>  $citations
     */
    private function validateContent(array $blocks, array $citations): void
    {
        if ($blocks === [] || ! array_is_list($blocks) || ! array_is_list($citations) || $citations === []) {
            throw new InvalidArgumentException('Lesson blocks and citations are required lists.');
        }
        $previousDepth = -1;
        foreach ($blocks as $i => $block) {
            if (! is_array($block) || ! in_array($block['type'] ?? null, ['heading', 'paragraph', 'callout', 'rules', 'boundaries', 'code', 'request', 'response', 'log'], true)) {
                throw new InvalidArgumentException('Unregistered lesson block type.');
            }
            if (array_diff(array_keys($block), ['type', 'body', 'depth']) !== []) {
                throw new InvalidArgumentException('Unknown lesson block key.');
            }
            if (! is_string($block['body'] ?? null) || mb_strlen($block['body']) > 4000) {
                throw new InvalidArgumentException('Lesson block body is invalid or too large.');
            }
            $depth = $block['depth'] ?? 0;
            if (! is_int($depth) || $depth < 0 || $depth > 3) {
                throw new InvalidArgumentException('Lesson block depth must be an integer between 0 and 3.');
            }
            if ($i === 0 && $depth !== 0) {
                throw new InvalidArgumentException('First block must have depth 0.');
            }
            if ($i > 0 && $depth > $previousDepth + 1) {
                throw new InvalidArgumentException('Block depth cannot jump by more than 1 from previous block.');
            }
            $previousDepth = $depth;

            $proseBlock = in_array($block['type'], ['heading', 'paragraph', 'callout', 'rules', 'boundaries'], true);
            if ($proseBlock && preg_match('/<\s*script\b|\bon[a-z]+\s*=|javascript\s*:/iu', $block['body']) === 1) {
                throw new InvalidArgumentException('Unsafe active lesson content is rejected.');
            }
        }
        foreach ($citations as $claimId) {
            if (! is_string($claimId) || preg_match('/^(?:WIN|WEB|VS3)-AUTH-\d{3}$/', $claimId) !== 1) {
                throw new InvalidArgumentException('Citation claim ID is invalid.');
            }
        }
    }

    /**
     * @param  array<mixed>  $blocks
     * @param  array<mixed>  $citations
     */
    private function contentDigest(array $blocks, array $citations): string
    {
        return hash('sha256', json_encode(['blocks' => $blocks, 'citations' => $citations], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @param array<string,mixed> $metadata */
    private function recordAudit(string $action, LessonRevision $revision, ?string $actorId, array $metadata = []): void
    {
        if ($actorId === null) {
            return;
        }
        $this->audit->append([
            'actor_identifier' => $actorId,
            'action' => $action,
            'target_type' => 'lesson_revision',
            'target_identifier' => (string) $revision->id,
            'correlation_id' => (string) $revision->id,
            'outcome' => 'success',
            'safe_metadata' => $metadata,
        ]);
    }
}

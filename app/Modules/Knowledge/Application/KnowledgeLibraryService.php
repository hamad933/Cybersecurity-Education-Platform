<?php

namespace App\Modules\Knowledge\Application;

use App\Modules\Knowledge\Application\Library\LibraryCapabilityManifest;
use App\Modules\Knowledge\Application\Library\LibraryHierarchyProjector;
use App\Modules\Knowledge\Content\LessonContentContract;
use App\Modules\Knowledge\Models\KnowledgeUnit;
use App\Modules\Knowledge\Models\LessonRevision;
use App\Modules\Knowledge\Publication\LessonRevisionWorkflow;
use DateTimeInterface;

final class KnowledgeLibraryService
{
    public function __construct(
        private readonly LessonRevisionWorkflow $workflow,
        private readonly LessonContentContract $content,
    ) {}

    /** @return list<array<string, mixed>> */
    public function catalog(): array
    {
        $units = KnowledgeUnit::query()->orderBy('title_ar')->orderBy('id')->get();
        $revisionGroups = $this->revisionGroups($units->pluck('id')->all());

        return $units->map(function (KnowledgeUnit $unit) use ($revisionGroups): array {
            $revisions = $revisionGroups[(string) $unit->id] ?? [];
            $latest = $revisions[0] ?? null;
            $latestPublished = null;
            foreach ($revisions as $revision) {
                if ($revision->state === 'published') {
                    $latestPublished = $revision;
                    break;
                }
            }

            return [
                'id' => (string) $unit->id,
                'title_ar' => (string) $unit->title_ar,
                'title_en' => (string) $unit->title_en,
                'latest_revision' => $latest?->revision,
                'latest_state' => $latest?->state,
                'revision_count' => count($revisions),
                'published_revision' => $latestPublished?->revision,
                'lesson_availability' => $latestPublished instanceof LessonRevision
                    ? 'PUBLISHED_LESSON_AVAILABLE'
                    : 'NO_PUBLISHED_LESSON',
            ];
        })->values()->all();
    }

    /** @return array{requested_id: string|null, resolved_id: string|null, state: string} */
    public function resolveUnitSelection(?string $requested): array
    {
        if ($requested !== null && KnowledgeUnit::query()->whereKey($requested)->exists()) {
            return [
                'requested_id' => $requested,
                'resolved_id' => $requested,
                'state' => 'REQUESTED_CANONICAL_UNIT',
            ];
        }

        $first = KnowledgeUnit::query()->orderBy('title_ar')->orderBy('id')->value('id');
        $resolved = is_string($first) ? $first : null;

        return [
            'requested_id' => $requested,
            'resolved_id' => $resolved,
            'state' => $resolved === null
                ? 'EMPTY_CANONICAL_LIBRARY'
                : ($requested === null ? 'DEFAULTED_TO_FIRST_CANONICAL_UNIT' : 'REQUESTED_UNIT_NOT_FOUND_FALLBACK'),
        ];
    }

    public function resolveUnitId(?string $requested): ?string
    {
        return $this->resolveUnitSelection($requested)['resolved_id'];
    }

    /** @return array<string, mixed>|null */
    public function unit(?string $unitId, ?string $revisionId = null): ?array
    {
        if ($unitId === null) {
            return null;
        }

        $unit = KnowledgeUnit::query()->find($unitId);
        if (! $unit instanceof KnowledgeUnit) {
            return null;
        }

        $revisions = LessonRevision::query()
            ->where('knowledge_unit_id', $unitId)
            ->orderByDesc('revision')
            ->get();

        $selected = null;
        $revisionSelectionState = 'NO_REVISIONS_AVAILABLE';
        if ($revisionId !== null) {
            $candidate = $revisions->firstWhere('id', $revisionId);
            if ($candidate instanceof LessonRevision) {
                $selected = $candidate;
                $revisionSelectionState = 'REQUESTED_REVISION';
            } else {
                $revisionSelectionState = 'REQUESTED_REVISION_NOT_FOUND_FALLBACK';
            }
        }
        if (! $selected instanceof LessonRevision) {
            $selected = $revisions->first();
            if ($selected instanceof LessonRevision && $revisionId === null) {
                $revisionSelectionState = 'LATEST_REVISION';
            }
        }

        $revisionItems = [];
        foreach ($revisions as $revision) {
            $revisionItems[] = [
                'id' => (string) $revision->id,
                'revision' => (int) $revision->revision,
                'state' => (string) $revision->state,
                'lock_version' => (int) $revision->lock_version,
                'derived_from_revision_id' => $revision->derived_from_revision_id !== null ? (string) $revision->derived_from_revision_id : null,
                'published_at' => $this->dateTimeValue($revision->getAttribute('published_at')),
                'updated_at' => $this->dateTimeValue($revision->getAttribute('updated_at')),
            ];
        }

        return [
            'id' => (string) $unit->id,
            'canonical_ref' => ['kind' => 'knowledge_unit', 'id' => (string) $unit->id],
            'title_ar' => (string) $unit->title_ar,
            'title_en' => (string) $unit->title_en,
            'revision' => $selected instanceof LessonRevision ? $this->revision($selected) : null,
            'revisions' => $revisionItems,
            'revision_selection' => [
                'requested_id' => $revisionId,
                'selected_id' => $selected instanceof LessonRevision ? (string) $selected->id : null,
                'state' => $revisionSelectionState,
                'policy' => 'explicit_revision_or_latest_revision',
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public function learningUnit(?string $unitId): ?array
    {
        if ($unitId === null) {
            return null;
        }

        $unit = KnowledgeUnit::query()->find($unitId);
        if (! $unit instanceof KnowledgeUnit) {
            return null;
        }

        $published = LessonRevision::query()
            ->where('knowledge_unit_id', $unitId)
            ->where('state', 'published')
            ->orderByDesc('revision')
            ->first();

        return [
            'id' => (string) $unit->id,
            'canonical_ref' => ['kind' => 'knowledge_unit', 'id' => (string) $unit->id],
            'title_ar' => (string) $unit->title_ar,
            'title_en' => (string) $unit->title_en,
            'lesson' => [
                'availability' => $published instanceof LessonRevision
                    ? 'AVAILABLE_PUBLISHED_REVISION'
                    : 'UNAVAILABLE_NO_PUBLISHED_REVISION',
                'selection_policy' => 'latest_published_revision_only',
                'revision' => $published instanceof LessonRevision ? $this->revision($published) : null,
                'unavailable_reason' => $published instanceof LessonRevision
                    ? null
                    : 'A canonical Knowledge Unit exists, but no published lesson revision is available for learning delivery.',
            ],
        ];
    }

    /**
     * Build the complete structural projection only when the parent supplies
     * authoritative Domain / Capability Cluster context for each capability.
     *
     * @param  list<array<string, mixed>>  $placements
     * @param  list<array<string, mixed>>  $capabilityContexts
     * @return array<string, mixed>
     */
    public function hierarchyProjection(array $placements, array $capabilityContexts): array
    {
        return (new LibraryHierarchyProjector)->project(
            $this->catalog(),
            $placements,
            $capabilityContexts,
        );
    }

    /** @return array<string, mixed> */
    public function capabilityManifest(): array
    {
        return (new LibraryCapabilityManifest)->current();
    }

    /** @return array<string, mixed> */
    public function contentContract(): array
    {
        return $this->content->manifest();
    }

    /**
     * @param  array<mixed>  $blocks
     * @param  array<mixed>  $citations
     */
    public function updateRevision(string $revisionId, int $expectedLockVersion, array $blocks, array $citations, string $actorId): void
    {
        $this->workflow->updateDraft($revisionId, $expectedLockVersion, $blocks, $citations, $actorId);
    }

    /** @return array{id: string, knowledge_unit_id: string} */
    public function restoreRevision(string $revisionId, string $actorId): array
    {
        $draft = $this->workflow->restoreAsDraft($revisionId, $actorId);

        return [
            'id' => (string) $draft->id,
            'knowledge_unit_id' => (string) $draft->knowledge_unit_id,
        ];
    }

    /**
     * @param  list<string>  $unitIds
     * @return array<string, list<LessonRevision>>
     */
    private function revisionGroups(array $unitIds): array
    {
        if ($unitIds === []) {
            return [];
        }

        /** @var array<string, list<LessonRevision>> $groups */
        $groups = [];
        foreach (LessonRevision::query()
            ->whereIn('knowledge_unit_id', $unitIds)
            ->orderByDesc('revision')
            ->get() as $revision) {
            $groups[(string) $revision->knowledge_unit_id][] = $revision;
        }

        return $groups;
    }

    /** @return array<string, mixed> */
    private function revision(LessonRevision $revision): array
    {
        return [
            'id' => (string) $revision->id,
            'revision' => (int) $revision->revision,
            'state' => (string) $revision->state,
            'lock_version' => (int) $revision->lock_version,
            'blocks' => $this->content->normalizeStoredBlocks($revision->blockList()),
            'citations' => $revision->citationIds(),
            'authority_baseline_id' => $revision->authority_baseline_id !== null ? (string) $revision->authority_baseline_id : null,
            'content_digest' => (string) $revision->content_digest,
            'derived_from_revision_id' => $revision->derived_from_revision_id !== null ? (string) $revision->derived_from_revision_id : null,
            'published_at' => $this->dateTimeValue($revision->getAttribute('published_at')),
            'updated_at' => $this->dateTimeValue($revision->getAttribute('updated_at')),
            'editable' => $revision->state === 'draft',
        ];
    }

    private function dateTimeValue(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : null;
    }
}

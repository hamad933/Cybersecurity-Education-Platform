<?php

namespace App\Modules\Knowledge\Application;

use App\Modules\Knowledge\Models\KnowledgeUnit;
use App\Modules\Knowledge\Models\LessonRevision;
use Illuminate\Support\Collection;

final class KnowledgeLibraryService
{
    /** @return list<array<string, mixed>> */
    public function catalog(): array
    {
        $units = KnowledgeUnit::query()->orderBy('title_ar')->orderBy('id')->get();
        $revisionGroups = $this->revisionGroups($units->pluck('id')->all());

        return $units->map(function (KnowledgeUnit $unit) use ($revisionGroups): array {
            /** @var Collection<int, LessonRevision>|null $revisions */
            $revisions = $revisionGroups->get((string) $unit->id);
            $latest = $revisions?->first();

            return [
                'id' => (string) $unit->id,
                'title_ar' => (string) $unit->title_ar,
                'title_en' => (string) $unit->title_en,
                'latest_revision' => $latest?->revision,
                'latest_state' => $latest?->state,
            ];
        })->values()->all();
    }

    public function resolveUnitId(?string $requested): ?string
    {
        if ($requested !== null && KnowledgeUnit::query()->whereKey($requested)->exists()) {
            return $requested;
        }

        $first = KnowledgeUnit::query()->orderBy('title_ar')->orderBy('id')->value('id');

        return is_string($first) ? $first : null;
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
        if ($revisionId !== null) {
            $candidate = $revisions->firstWhere('id', $revisionId);
            if ($candidate instanceof LessonRevision) {
                $selected = $candidate;
            }
        }
        if (! $selected instanceof LessonRevision) {
            $selected = $revisions->first();
        }

        return [
            'id' => (string) $unit->id,
            'title_ar' => (string) $unit->title_ar,
            'title_en' => (string) $unit->title_en,
            'revision' => $selected instanceof LessonRevision ? $this->revision($selected) : null,
            'revisions' => $revisions->map(fn (LessonRevision $revision): array => [
                'id' => (string) $revision->id,
                'revision' => (int) $revision->revision,
                'state' => (string) $revision->state,
                'lock_version' => (int) $revision->lock_version,
                'derived_from_revision_id' => $revision->derived_from_revision_id !== null ? (string) $revision->derived_from_revision_id : null,
                'published_at' => $revision->published_at?->toIso8601String(),
                'updated_at' => $revision->updated_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /** @param list<string> $unitIds
     *  @return Collection<string, Collection<int, LessonRevision>>
     */
    private function revisionGroups(array $unitIds): Collection
    {
        if ($unitIds === []) {
            return collect();
        }

        return LessonRevision::query()
            ->whereIn('knowledge_unit_id', $unitIds)
            ->orderByDesc('revision')
            ->get()
            ->groupBy('knowledge_unit_id');
    }

    /** @return array<string, mixed> */
    private function revision(LessonRevision $revision): array
    {
        return [
            'id' => (string) $revision->id,
            'revision' => (int) $revision->revision,
            'state' => (string) $revision->state,
            'lock_version' => (int) $revision->lock_version,
            'blocks' => $revision->blockList(),
            'citations' => $revision->citationIds(),
            'authority_baseline_id' => $revision->authority_baseline_id !== null ? (string) $revision->authority_baseline_id : null,
            'content_digest' => (string) $revision->content_digest,
            'derived_from_revision_id' => $revision->derived_from_revision_id !== null ? (string) $revision->derived_from_revision_id : null,
            'published_at' => $revision->published_at?->toIso8601String(),
            'updated_at' => $revision->updated_at?->toIso8601String(),
            'editable' => $revision->state === 'draft',
        ];
    }
}

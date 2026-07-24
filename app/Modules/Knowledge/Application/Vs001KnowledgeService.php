<?php

namespace App\Modules\Knowledge\Application;

use App\Modules\Knowledge\Models\LessonRevision;

final class Vs001KnowledgeService
{
    /** @return list<array<string,mixed>> */
    public function revisions(): array
    {
        return LessonRevision::query()->where('knowledge_unit_id', config('vs001.knowledge_unit_id'))->latest('revision')->get()
            ->map(fn (LessonRevision $revision): array => $revision->toArray())
            ->all();
    }

    /** @return array<string,mixed> */
    public function publishedLesson(): array
    {
        return LessonRevision::query()
            ->where('knowledge_unit_id', config('vs001.knowledge_unit_id'))
            ->where('state', 'published')
            ->latest('revision')
            ->firstOrFail()
            ->toArray();
    }
}

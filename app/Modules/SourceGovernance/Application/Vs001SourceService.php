<?php

namespace App\Modules\SourceGovernance\Application;

use App\Modules\SourceGovernance\Models\SourceRecord;

final class Vs001SourceService
{
    /** @return list<array<string,mixed>> */
    public function reviewedSources(): array
    {
        return SourceRecord::query()->with('claims')->orderBy('title')->get()
            ->map(fn (SourceRecord $record): array => $record->toArray())
            ->all();
    }
}

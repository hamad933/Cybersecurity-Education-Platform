<?php

namespace App\Modules\SourceGovernance\Application;

use App\Modules\SourceGovernance\Models\SourceRecord;

final class Vs002SourceService
{
    /** @return list<array<string,mixed>> */
    public function reviewedSources(): array
    {
        return SourceRecord::query()->with(['claims' => fn ($query) => $query->whereIn('claim_id', config('vs002.required_claim_ids'))])
            ->whereHas('claims', fn ($query) => $query->whereIn('claim_id', config('vs002.required_claim_ids')))
            ->orderBy('title')->get()->map(fn (SourceRecord $record): array => $record->toArray())->all();
    }
}

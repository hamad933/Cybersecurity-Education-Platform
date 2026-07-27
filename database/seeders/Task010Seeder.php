<?php

namespace Database\Seeders;

use App\Modules\Knowledge\Models\KnowledgeUnit;
use App\Modules\Platform\Search\SearchService;
use Illuminate\Database\Seeder;

final class Task010Seeder extends Seeder
{
    public function run(SearchService $search): void
    {
        foreach (KnowledgeUnit::query()->orderBy('id')->get() as $unit) {
            $search->index([
                'document_type' => 'knowledge_unit',
                'document_identifier' => (string) $unit->id,
                'title_ar' => (string) $unit->title_ar,
                'title_en' => (string) $unit->title_en,
                'body_ar' => (string) $unit->title_ar,
                'body_en' => (string) $unit->title_en,
                'facets' => ['knowledge_unit_id' => (string) $unit->id, 'release' => 'V1'],
            ]);
        }
    }
}

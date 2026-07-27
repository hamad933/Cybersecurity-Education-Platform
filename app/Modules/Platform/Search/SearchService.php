<?php

namespace App\Modules\Platform\Search;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SearchService
{
    /** @param array<string,mixed> $document */
    public function index(array $document): SearchDocument
    {
        foreach (['document_type', 'document_identifier'] as $required) {
            if (! is_string($document[$required] ?? null) || trim($document[$required]) === '') {
                throw new InvalidArgumentException('Search document identity is required.');
            }
        }

        return SearchDocument::query()->updateOrCreate(
            [
                'document_type' => mb_substr($document['document_type'], 0, 80),
                'document_identifier' => mb_substr($document['document_identifier'], 0, 160),
            ],
            [
                'title_ar' => mb_substr((string) ($document['title_ar'] ?? ''), 0, 500),
                'title_en' => mb_substr((string) ($document['title_en'] ?? ''), 0, 500),
                'body_ar' => mb_substr((string) ($document['body_ar'] ?? ''), 0, 50_000),
                'body_en' => mb_substr((string) ($document['body_en'] ?? ''), 0, 50_000),
                'facets' => is_array($document['facets'] ?? null) ? $document['facets'] : [],
                'indexed_at' => now(),
            ],
        );
    }

    /** @return Collection<int,SearchDocument> */
    public function search(string $query, int $limit = 20): Collection
    {
        $query = trim($query);
        if ($query === '' || mb_strlen($query) > 200) {
            throw new InvalidArgumentException('Search query is empty or too long.');
        }
        $limit = max(1, min($limit, 50));
        $builder = SearchDocument::query();
        if (DB::connection()->getDriverName() === 'pgsql') {
            return $builder
                ->select('*')
                ->selectRaw("ts_rank_cd(search_vector, websearch_to_tsquery('simple', ?)) AS relevance", [$query])
                ->whereRaw("search_vector @@ websearch_to_tsquery('simple', ?)", [$query])
                ->orderByDesc('relevance')
                ->orderByDesc('indexed_at')
                ->limit($limit)
                ->get();
        }
        $needle = '%'.mb_strtolower($query).'%';

        return $builder
            ->whereRaw('LOWER(title_ar || title_en || body_ar || body_en) LIKE ?', [$needle])
            ->orderByDesc('indexed_at')
            ->limit($limit)
            ->get();
    }
}

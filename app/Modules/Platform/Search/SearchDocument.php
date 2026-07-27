<?php

namespace App\Modules\Platform\Search;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class SearchDocument extends Model
{
    use UsesUuidV7;

    protected $fillable = [
        'document_type', 'document_identifier', 'title_ar', 'title_en',
        'body_ar', 'body_en', 'facets', 'indexed_at',
    ];

    protected function casts(): array
    {
        return ['facets' => 'array', 'indexed_at' => 'immutable_datetime'];
    }
}

<?php

namespace App\Modules\SourceGovernance\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class SourceImport extends Model
{
    use UsesUuidV7;

    protected $fillable = [
        'actor_id', 'blob_object_id', 'source_record_id', 'original_name',
        'detected_media_type', 'extension', 'size_bytes', 'sha256', 'status',
        'rejection_code', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'reviewed_at' => 'immutable_datetime'];
    }
}

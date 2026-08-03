<?php

namespace App\Modules\Platform\Blobs;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class BlobObject extends Model
{
    use UsesUuidV7;

    public $timestamps = false;

    protected $fillable = [
        'storage_key', 'size_bytes', 'sha256', 'media_type', 'status',
        'owner_module', 'purpose', 'owner_identifier', 'created_at',
    ];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'created_at' => 'immutable_datetime'];
    }
}

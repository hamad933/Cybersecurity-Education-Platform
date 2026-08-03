<?php

namespace App\Modules\Platform\Backup;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class BackupManifest extends Model
{
    use UsesUuidV7;

    public $timestamps = false;

    protected $fillable = ['actor_id', 'portable_package_id', 'status', 'database_driver', 'table_counts', 'blob_inventory', 'content_digest', 'created_at'];

    protected function casts(): array
    {
        return ['table_counts' => 'array', 'blob_inventory' => 'array', 'created_at' => 'immutable_datetime'];
    }
}

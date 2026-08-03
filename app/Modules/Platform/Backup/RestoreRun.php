<?php

namespace App\Modules\Platform\Backup;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class RestoreRun extends Model
{
    use UsesUuidV7;

    public $timestamps = false;

    protected $fillable = ['actor_id', 'backup_manifest_id', 'target_database', 'status', 'verification', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return ['verification' => 'array', 'started_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime'];
    }
}

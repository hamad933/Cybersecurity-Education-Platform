<?php

namespace App\Modules\Simulator\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class ReplayRecord extends Model
{
    use UsesUuidV7;

    protected $fillable = ['original_run_id', 'replay_run_id', 'digest_match', 'original_digest', 'replay_digest'];

    protected function casts(): array
    {
        return ['digest_match' => 'boolean'];
    }
}

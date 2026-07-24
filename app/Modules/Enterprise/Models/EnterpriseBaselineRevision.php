<?php

namespace App\Modules\Enterprise\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use App\Modules\Platform\Support\ImmutableWhenFinal;
use Illuminate\Database\Eloquent\Model;

class EnterpriseBaselineRevision extends Model
{
    use ImmutableWhenFinal, UsesUuidV7;

    protected $fillable = ['baseline_id', 'revision', 'state', 'snapshot', 'snapshot_digest', 'published_at'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'published_at' => 'immutable_datetime'];
    }

    protected function wasFinalBeforeUpdate(): bool
    {
        return $this->getOriginal('state') === 'published';
    }
}

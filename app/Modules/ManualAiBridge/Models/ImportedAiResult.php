<?php

namespace App\Modules\ManualAiBridge\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use App\Modules\Platform\Support\ImmutableWhenFinal;
use Illuminate\Database\Eloquent\Model;

class ImportedAiResult extends Model
{
    use ImmutableWhenFinal, UsesUuidV7;

    public $timestamps = false;

    protected $fillable = ['actor_id', 'prompt_package_revision_id', 'portable_package_id', 'result_digest', 'structured_result', 'status', 'imported_at'];

    protected function casts(): array
    {
        return ['structured_result' => 'array', 'imported_at' => 'immutable_datetime'];
    }

    protected function wasFinalBeforeUpdate(): bool
    {
        return in_array($this->getOriginal('status'), ['accepted', 'rejected'], true);
    }
}

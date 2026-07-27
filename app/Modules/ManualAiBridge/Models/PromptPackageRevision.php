<?php

namespace App\Modules\ManualAiBridge\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class PromptPackageRevision extends Model
{
    use UsesUuidV7;

    public $timestamps = false;

    protected $fillable = ['prompt_package_id', 'revision', 'portable_package_id', 'input_digest', 'declared_scope', 'exported_at'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'declared_scope' => 'array', 'exported_at' => 'immutable_datetime'];
    }
}

<?php

namespace App\Modules\Learning\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use App\Modules\Platform\Support\ImmutableWhenFinal;
use Illuminate\Database\Eloquent\Model;

class MasteryRuleRevision extends Model
{
    use ImmutableWhenFinal, UsesUuidV7;

    protected $fillable = ['rule_id', 'revision', 'requirements', 'digest', 'state'];

    protected function casts(): array
    {
        return ['requirements' => 'array'];
    }

    protected function wasFinalBeforeUpdate(): bool
    {
        return $this->getOriginal('state') === 'approved';
    }
}

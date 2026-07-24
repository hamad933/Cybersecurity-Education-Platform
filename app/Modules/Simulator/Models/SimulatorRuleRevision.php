<?php

namespace App\Modules\Simulator\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use App\Modules\Platform\Support\ImmutableWhenFinal;
use Illuminate\Database\Eloquent\Model;

class SimulatorRuleRevision extends Model
{
    use ImmutableWhenFinal, UsesUuidV7;

    protected $fillable = ['rule_set_id', 'revision', 'authority_baseline_id', 'state', 'rules', 'digest', 'approved_at'];

    protected function casts(): array
    {
        return ['rules' => 'array', 'approved_at' => 'immutable_datetime'];
    }

    protected function wasFinalBeforeUpdate(): bool
    {
        return $this->getOriginal('state') === 'approved';
    }
}

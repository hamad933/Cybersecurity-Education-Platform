<?php

namespace App\Modules\Learning\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class PracticeAttempt extends Model
{
    use UsesUuidV7;

    protected $fillable = ['micro_practice_id', 'actor_id', 'case_id', 'answer', 'outcome', 'rationale_valid', 'failure_class'];

    protected function casts(): array
    {
        return ['answer' => 'array', 'rationale_valid' => 'boolean'];
    }
}

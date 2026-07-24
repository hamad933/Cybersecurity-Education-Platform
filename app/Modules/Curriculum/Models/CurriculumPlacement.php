<?php

namespace App\Modules\Curriculum\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class CurriculumPlacement extends Model
{
    use UsesUuidV7;

    protected $fillable = ['capability_id', 'knowledge_unit_id', 'revision', 'lifecycle'];

    protected function casts(): array
    {
        return ['lifecycle' => 'array'];
    }
}

<?php

namespace App\Modules\Learning\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class MasteryState extends Model
{
    use UsesUuidV7;

    protected $fillable = ['actor_id', 'knowledge_unit_id', 'mastery_rule_revision_id', 'status', 'evidence_record_ids', 'evaluation_digest', 'evaluated_at'];

    protected function casts(): array
    {
        return ['evidence_record_ids' => 'array', 'evaluated_at' => 'immutable_datetime'];
    }
}

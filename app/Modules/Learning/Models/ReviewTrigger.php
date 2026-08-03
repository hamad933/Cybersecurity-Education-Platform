<?php

namespace App\Modules\Learning\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class ReviewTrigger extends Model
{
    use UsesUuidV7;

    protected $fillable = ['actor_id', 'knowledge_unit_id', 'case_id', 'failure_class', 'source_reference', 'source_type', 'source_id', 'rule_revision_id', 'schedule_reason', 'status', 'scheduled_at'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'immutable_datetime'];
    }
}

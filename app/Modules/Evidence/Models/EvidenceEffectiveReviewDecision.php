<?php

namespace App\Modules\Evidence\Models;

use Illuminate\Database\Eloquent\Model;

final class EvidenceEffectiveReviewDecision extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'evidence_effective_review_decisions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'decided_at' => 'immutable_datetime',
            'projected_at' => 'immutable_datetime',
        ];
    }
}

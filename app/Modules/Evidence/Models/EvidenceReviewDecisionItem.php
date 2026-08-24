<?php

namespace App\Modules\Evidence\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceReviewDecisionItem extends Model
{
    protected $table = 'evidence_review_decision_items';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}

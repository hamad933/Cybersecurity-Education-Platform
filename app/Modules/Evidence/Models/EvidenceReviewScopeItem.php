<?php

namespace App\Modules\Evidence\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceReviewScopeItem extends Model
{
    protected $table = 'evidence_review_scope_items';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}

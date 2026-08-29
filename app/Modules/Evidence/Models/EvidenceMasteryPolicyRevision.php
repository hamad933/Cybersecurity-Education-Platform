<?php

namespace App\Modules\Evidence\Models;

use Illuminate\Database\Eloquent\Model;

final class EvidenceMasteryPolicyRevision extends Model
{
    public $incrementing = false;

    protected $table = 'evidence_mastery_policy_revisions';

    protected $keyType = 'string';

    protected $guarded = [];
}

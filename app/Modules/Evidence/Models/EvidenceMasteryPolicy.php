<?php

namespace App\Modules\Evidence\Models;

use Illuminate\Database\Eloquent\Model;

final class EvidenceMasteryPolicy extends Model
{
    public $incrementing = false;

    protected $table = 'evidence_mastery_policies';

    protected $keyType = 'string';

    protected $guarded = [];
}

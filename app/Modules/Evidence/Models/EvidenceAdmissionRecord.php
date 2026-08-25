<?php

namespace App\Modules\Evidence\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceAdmissionRecord extends Model
{
    protected $table = 'evidence_admission_records';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}

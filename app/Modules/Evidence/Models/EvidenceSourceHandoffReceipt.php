<?php

namespace App\Modules\Evidence\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceSourceHandoffReceipt extends Model
{
    protected $table = 'evidence_source_handoff_receipts';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}

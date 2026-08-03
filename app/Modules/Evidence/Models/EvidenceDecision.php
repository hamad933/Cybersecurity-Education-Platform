<?php

namespace App\Modules\Evidence\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class EvidenceDecision extends Model
{
    use UsesUuidV7;

    protected $fillable = ['evidence_record_id', 'decision', 'rationale', 'decided_by', 'decided_at'];

    protected function casts(): array
    {
        return ['decided_at' => 'immutable_datetime'];
    }
}

<?php

namespace App\Modules\SourceGovernance\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;

class SourceClaim extends Model
{
    use UsesUuidV7;

    protected $fillable = ['source_record_id', 'claim_id', 'segment_ref', 'supported_scope', 'excluded_semantics', 'assessment'];
}

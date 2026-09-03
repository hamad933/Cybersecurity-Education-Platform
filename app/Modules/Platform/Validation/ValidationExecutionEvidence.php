<?php

namespace App\Modules\Platform\Validation;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Platform\Identifiers\UsesUuidV7;

class ValidationExecutionEvidence extends Model
{
    use UsesUuidV7;

    protected $table = 'validation_execution_evidence';

    protected $fillable = [
        'execution_id', // Binds directly to the processing run or outer operation ID
        'artifact_type',
        'technical_findings_count',
        'knowledge_findings_count',
        'findings_digest',
        'created_at',
    ];

    public $timestamps = false;
    
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'technical_findings_count' => 'integer',
            'knowledge_findings_count' => 'integer',
        ];
    }
}

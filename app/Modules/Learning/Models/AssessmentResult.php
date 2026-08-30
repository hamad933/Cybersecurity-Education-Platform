<?php

namespace App\Modules\Learning\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AssessmentResult extends Model
{
    use UsesUuidV7;

    protected $fillable = [
        'assessment_attempt_id',
        'outcome',
        'score_details',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'score_details' => 'array',
            'evaluated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Assessment results are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new LogicException('Assessment results are immutable and cannot be deleted.');
        });
    }
}

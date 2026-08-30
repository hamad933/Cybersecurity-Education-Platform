<?php

namespace App\Modules\Learning\Models;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AssessmentAttempt extends Model
{
    use UsesUuidV7;

    protected $fillable = [
        'assessment_definition_id',
        'actor_id',
        'status',
        'answers',
        'started_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (AssessmentAttempt $attempt) {
            if ($attempt->getOriginal('status') === 'submitted') {
                throw new LogicException('Submitted assessment attempts are terminal and immutable.');
            }
        });

        static::deleting(function () {
            throw new LogicException('Assessment attempts cannot be deleted.');
        });
    }
}
